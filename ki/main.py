import pandas as pd
import mysql.connector
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import re
import subprocess
import time
import warnings
import os
import socket
from datetime import datetime
import json

from config import SSH_CONFIG, DB_CONFIG

import atexit
import sys
import fcntl

LOGFILE = "kursduplikate.log"
LOCKFILE = "kursduplikate.lock"

def log(msg):
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    with open(LOGFILE, "a", encoding="utf-8") as f:
        f.write(f"[{timestamp}] {msg}\n")
    print(msg)

# ===== Sperre gegen Parallel-Laeufe (z.B. mehrmals taeglich per Cron) =====
# Wir nutzen einen echten Datei-Lock (fcntl.flock). Vorteile gegenueber einem
# blossen "Datei existiert?"-Check:
#   - Der Kernel gibt den Lock automatisch frei, wenn der Prozess endet -
#     auch bei Absturz, kill, SSH-Abbruch oder Server-Neustart. Es bleibt also
#     KEIN verwaistes Lock zurueck, das einen kuenftigen Lauf blockiert.
#   - Laeuft bereits eine Instanz, schlaegt das (nicht-blockierende) Sperren
#     fehl -> wir beenden uns einfach, OHNE das fremde Lock anzutasten.
# Die Lockdatei selbst wird bewusst NICHT geloescht (sie ist nur der Anker fuer
# den Lock + enthaelt Diagnose-Infos); das vermeidet eine Race-Condition beim
# Loeschen/Neuanlegen.
_lock_fp = None  # haelt den flock fuer die gesamte Prozesslaufzeit offen

def acquire_lock_or_exit():
    global _lock_fp
    # Datei anlegen (falls noch nicht vorhanden) und zum Lesen/Schreiben oeffnen.
    if not os.path.exists(LOCKFILE):
        try:
            open(LOCKFILE, "a").close()
        except Exception as e:
            log(f"⚠️ Lockdatei konnte nicht angelegt werden: {e}")
    try:
        _lock_fp = open(LOCKFILE, "r+", encoding="utf-8")
    except Exception as e:
        log(f"⚠️ Lockdatei konnte nicht geoeffnet werden: {e}")
        sys.exit(1)

    try:
        fcntl.flock(_lock_fp.fileno(), fcntl.LOCK_EX | fcntl.LOCK_NB)
    except (IOError, OSError):
        # Lock wird bereits gehalten -> es laeuft bereits eine Instanz.
        # NICHTS tun und das fremde Lock NICHT anfassen.
        log(f"🚫 '{LOCKFILE}' ist gesperrt - es laeuft bereits eine Instanz. Breche ab (kein Parallel-Lauf).")
        try:
            _lock_fp.close()
        except Exception:
            pass
        _lock_fp = None
        sys.exit(0)

    # Lock erhalten -> Diagnose-Infos hineinschreiben
    try:
        _lock_fp.seek(0)
        _lock_fp.truncate(0)
        _lock_fp.write(f"PID: {os.getpid()}\n")
        _lock_fp.write(f"Start: {datetime.now().isoformat()}\n")
        _lock_fp.write(f"Host: {os.uname().nodename if hasattr(os, 'uname') else 'unknown'}\n")
        _lock_fp.flush()
    except Exception:
        pass

def release_lock():
    # Gibt NUR den eigenen Lock frei (Datei bleibt als Anker bestehen).
    global _lock_fp
    if _lock_fp is not None:
        try:
            fcntl.flock(_lock_fp.fileno(), fcntl.LOCK_UN)
        except Exception:
            pass
        try:
            _lock_fp.close()
        except Exception:
            pass
        _lock_fp = None

atexit.register(release_lock)
acquire_lock_or_exit()


warnings.filterwarnings("ignore", message=".*joblib will operate.*")

MAX_LLM_CHECKS = 20000    # Maximale Anzahl KI-Checks pro Aufruf (wird als Limit genutzt)
MAX_BATCH_SIZE = 5        # Speichert nach X Vergleichen in die DB, kann nach Bedarf angepasst werden
PROCESSED_FILE = "already_processed.json"  # Zwischenspeicherung der bereits bearbeiteten Paare
WORT_AEHNLICHKEIT_VORAUSWAHL = 0.70 # Texte müssen mind. 70% Wort-Ähnlichkeit (egal welche Reihenfolge) aufweisen, um der KI zum Vergleich präsentiert zu werden
RUN_STATS_FILE = "dupe_stats.json"


TEMPLATE_USER_ID = 7
USER_ACCESS = 504


print(f"Bis zu {MAX_LLM_CHECKS} Kurspaare werden per KI verglichen werden... (Batch-Größe: {MAX_BATCH_SIZE})")


# ===== SSH-Tunnel-Konfiguration (nur wenn nicht lokal) =====
REMOTE_USER = SSH_CONFIG["ssh_username"]
REMOTE_HOST = SSH_CONFIG["ssh_host"]
REMOTE_PORT = SSH_CONFIG["ssh_port"]
SSH_KEY_PATH = SSH_CONFIG["ssh_key"]

REMOTE_OLLAMA_PORT = 11434
LOCAL_TUNNEL_PORT = 11434

def start_ssh_tunnel():
    if not is_port_open("127.0.0.1", LOCAL_TUNNEL_PORT):
        log("🔌 Starte SSH-Tunnel zu Ollama...")
        subprocess.Popen([
            "ssh", "-f", "-N",
            "-L", f"{LOCAL_TUNNEL_PORT}:127.0.0.1:{REMOTE_OLLAMA_PORT}",
            f"{REMOTE_USER}@{REMOTE_HOST}",
            "-i", os.path.expanduser(SSH_KEY_PATH)
        ])
        time.sleep(2)  # kurze Wartezeit zum Aufbau

def is_port_open(host, port):
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.settimeout(1)
        try:
            s.connect((host, port))
            return True
        except Exception:
            return False

# ===== LLM-Vergleich via Ollama API ueber Tunnel =====
def llm_check_duplikat(titel1, beschr1, titel2, beschr2):
    import requests
    import json

    prompt = f"""
[PROMPT ergänzen...]
"""
	
# llama3.3:latest
    
    try:
        response = requests.post(
            f"http://127.0.0.1:{LOCAL_TUNNEL_PORT}/api/generate",
            json={"model": "gemma4:31b", "prompt": prompt, "stream": True},
            stream=True,
            timeout=300
        )
        text_chunks = []
        for line in response.iter_lines():
            if line:
                data = json.loads(line.decode("utf-8"))
                if "response" in data:
                    text_chunks.append(data["response"])
        full_response = "".join(text_chunks).strip()
        response_lower = full_response.lower()
        if response_lower.startswith("::gleich::"):
            decision = "gleich"
        elif response_lower.startswith("::unterschiedlich::"):
            decision = "unterschiedlich"
        else:
            decision = "unterschiedlich"
        return decision, full_response
    except Exception as e:
        log(f"⚠️ Fehler beim LLM-Check (stream): {e}")
        return "unterschiedlich", f"Fehler beim LLM-Check: {e}"

# ===== DB-Verbindung konfigurieren =====
db_config = {
    'unix_socket': DB_CONFIG["db_socket"],
    'user':  DB_CONFIG["db_user"],
    'password': DB_CONFIG["db_password"],
    'database': DB_CONFIG["db_name"],
    'charset': 'latin1'
}

# ===== Stopwords laden =====
german_stopwords = set([
    "für", "in", "aber", "abermals", "abgerufen", "abgerufene", "abgerufener", "abgerufenes", "ähnlich", "alle", "allein", "allem", "allemal", "allen", "allenfalls", "allenthalben", "aller", "allerdings", "allerlei", "alles", "allesamt", "allgemein", "allmählich", "allzu", "als", "alsbald", "also", "alt", "am", "an", "andauernd", "andere", "anderem", "anderen", "anderer", "andererseits", "anderes", "andern", "andernfalls", "anders", "anerkannt", "anerkannte", "anerkannter", "anerkanntes", "angesetzt", "angesetzte", "angesetzter", "anscheinend", "anstatt", "auch", "auf", "auffallend", "aufgrund", "aufs", "augenscheinlich", "aus", "ausdrücklich", "ausdrückt", "ausdrückte", "ausgedrückt", "ausgenommen", "ausgerechnet", "ausnahmslos", "außen", "außer", "außerdem", "außerhalb", "äußerst", "bald", "beide", "beiden", "beiderlei", "beides", "beim", "beinahe", "bekannt", "bekannte", "bekannter", "bekanntlich", "bereits", "besonders", "besser", "bestenfalls", "bestimmt", "beträchtlich", "bevor", "bezüglich", "bin", "bisher", "bislang", "bist", "bloß", "Bsp", "bzw", "ca", "Co", "da", "dabei", "dadurch", "dafür", "dagegen", "daher", "dahin", "damals", "damit", "danach", "daneben", "dank", "danke", "dann", "dannen", "daran", "darauf", "daraus", "darf", "darfst", "darin", "darüber", "darum", "darunter", "das", "dass", "dasselbe", "davon", "davor", "dazu", "dein", "deine", "deinem", "deinen", "deiner", "deines", "dem", "demgegenüber", "demgemäß", "demnach", "demselben", "den", "denen", "denkbar", "denn", "dennoch", "denselben", "der", "derart", "derartig", "deren", "derer", "derjenige", "derjenigen", "derselbe", "derselben", "derzeit", "des", "deshalb", "desselben", "dessen", "desto", "deswegen", "dich", "die", "diejenige", "dies", "diese", "dieselbe", "dieselben", "diesem", "diesen", "dieser", "dieses", "diesmal", "diesseits", "dir", "direkt", "direkte", "direkten", "direkter", "doch", "dort", "dorther", "dorthin", "drin", "drüber", "drunter", "du", "dunklen", "durch", "durchaus", "durchweg", "eben", "ebenfalls", "ebenso", "ehe", "eher", "eigenen", "eigenes", "eigentlich", "ein", "eine", "einem", "einen", "einer", "einerseits", "eines", "einfach", "einig", "einige", "einigem", "einigen", "einiger", "einigermaßen", "einiges", "einmal", "einseitig", "einseitige", "einseitigen", "einseitiger", "einst", "einstmals", "einzig", "e. K.", "entsprechend", "entweder", "er", "ergo", "erhält", "erheblich", "erneut", "erst", "ersten", "es", "etc", "etliche", "etwa", "etwas", "euch", "euer", "eure", "eurem", "euren", "eurer", "eures", "falls", "fast", "ferner", "folgende", "folgenden", "folgender", "folgendermaßen", "folgendes", "folglich", "förmlich", "fortwährend", "fraglos", "frei", "freie", "freies", "freilich", "gab", "gängig", "gängige", "gängigen", "gängiger", "gängiges", "ganz", "ganze", "ganzem", "ganzen", "ganzer", "ganzes", "gänzlich", "gar", "GbR", "GbdR", "geehrte", "geehrten", "geehrter", "gefälligst", "gegen", "gehabt", "gekonnt", "gelegentlich", "gemacht", "gemäß", "gemeinhin", "gemocht", "genau", "genommen", "genügend", "genug", "geradezu", "gern", "gestrige", "getan", "geteilt", "geteilte", "getragen", "gewesen", "gewiss", "gewisse", "gewissermaßen", "gewollt", "geworden", "ggf", "gib", "gibt", "gleich", "gleichsam", "gleichwohl", "gleichzeitig", "glücklicherweise", "GmbH", "Gott sei Dank", "größtenteils", "Grunde", "gute", "guten", "hab", "habe", "halb", "hallo", "halt", "hast", "hat", "hatte", "hätte", "hätte", "hätten", "hattest", "hattet", "häufig", "heraus", "herein", "heute", "heutige", "hier", "hiermit", "hiesige", "hin", "hinein", "hingegen", "hinlänglich", "hinten", "hinter", "hinterher", "hoch", "höchst", "höchstens", "ich", "ihm", "ihn", "ihnen", "ihr", "ihre", "ihrem", "ihren", "ihrer", "ihres", "im", "immer", "immerhin", "immerzu", "indem", "indessen", "infolge", "infolgedessen", "innen", "innerhalb", "ins", "insbesondere", "insofern", "insofern", "inzwischen", "irgend", "irgendein", "irgendeine", "irgendjemand", "irgendwann", "irgendwas", "irgendwen", "irgendwer", "irgendwie", "irgendwo", "ist", "ja", "jährig", "jährige", "jährigen", "jähriges", "je", "jede", "jedem", "jeden", "jedenfalls", "jeder", "jederlei", "jedes", "jedoch", "jemals", "jemand", "jene", "jenem", "jenen", "jener", "jenes", "jenseits", "jetzt", "kam", "kann", "kannst", "kaum", "kein", "keine", "keinem", "keinen", "keiner", "keinerlei", "keines", "keines", "keinesfalls", "keineswegs", "KG", "klar", "klare", "klaren", "klares", "klein", "kleinen", "kleiner", "kleines", "konkret", "konkrete", "konkreten", "konkreter", "konkretes", "können", "könnt", "konnte", "könnte", "konnten", "könnten", "künftig", "lag", "lagen", "langsam", "längst", "längstens", "lassen", "laut", "lediglich", "leer", "leicht", "leider", "lesen", "letzten", "letztendlich", "letztens", "letztes", "letztlich", "lichten", "links", "Ltd", "mag", "magst", "mal", "man", "manche", "manchem", "manchen", "mancher", "mancherorts", "manches", "manchmal", "mehr", "mehrere", "mehrfach", "mein", "meine", "meinem", "meinen", "meiner", "meines", "meinetwegen", "meist", "meiste", "meisten", "meistens", "meistenteils", "meta", "mich", "mindestens", "mir", "mit", "mithin", "mitunter", "möglich", "mögliche", "möglichen", "möglicher", "möglicherweise", "möglichst", "morgen", "morgige", "muss", "müssen", "musst", "müsst", "musste", "müsste", "müssten", "nach", "nachdem", "nachher", "nachhinein", "nächste", "nämlich", "naturgemäß", "natürlich", "neben", "nebenan", "nebenbei", "nein", "neu", "neue", "neuem", "neuen", "neuer", "neuerdings", "neuerlich", "neues", "neulich", "nicht", "nichts", "nichtsdestotrotz", "nichtsdestoweniger", "nie", "niemals", "niemand", "nimm", "nimmer", "nimmt", "nirgends", "nirgendwo", "noch", "nötigenfalls", "nun", "nunmehr", "nur", "ob", "oben", "oberhalb", "obgleich", "obschon", "obwohl", "offenbar", "offenkundig", "offensichtlich", "oft", "ohne", "ohnedies", "OHG", "OK", "partout", "per", "persönlich", "plötzlich", "praktisch", "pro", "quasi", "recht", "rechts", "regelmäßig", "reichlich", "relativ", "restlos", "richtiggehend", "riesig", "rund", "rundheraus", "rundum", "sämtliche", "sattsam", "schätzen", "schätzt", "schätzte", "schätzten", "schlechter", "schlicht", "schlichtweg", "schließlich", "schlussendlich", "schnell", "schon", "schwerlich", "schwierig", "sehr", "sei", "seid", "sein", "seine", "seinem", "seinen", "seiner", "seines", "seit", "seitdem", "Seite", "Seiten", "seither", "selber", "selbst", "selbstredend", "selbstverständlich", "selten", "seltsamerweise", "sich", "sicher", "sicherlich", "sie", "siehe", "sieht", "sind", "so", "sobald", "sodass", "soeben", "sofern", "sofort", "sog", "sogar", "solange", "solch", "solche", "solchem", "solchen", "solcher", "solches", "soll", "sollen", "sollst", "sollt", "sollte", "sollten", "solltest", "somit", "sondern", "sonders", "sonst", "sooft", "soviel", "soweit", "sowie", "sowieso", "sowohl", "sozusagen", "später", "spielen", "startet", "startete", "starteten", "statt", "stattdessen", "steht", "stellenweise", "stets", "tat", "tatsächlich", "tatsächlichen", "tatsächlicher", "tatsächliches", "teile", "total", "trotzdem", "übel", "über", "überall", "überallhin", "überaus", "überdies", "überhaupt", "üblicher", "übrig", "übrigens", "um", "umso", "umstandshalber", "umständehalber", "unbedingt", "unbeschreiblich", "und", "unerhört", "ungefähr", "ungemein", "ungewöhnlich", "ungleich", "unglücklicherweise", "unlängst", "unmaßgeblich", "unmöglich", "unmögliche", "unmöglichen", "unmöglicher", "unnötig", "uns", "unsagbar", "unsäglich", "unser", "unsere", "unserem", "unseren", "unserer", "unseres", "unserm", "unstreitig", "unten", "unter", "unterbrach", "unterbrechen", "unterhalb", "unwichtig", "unzweifelhaft", "usw", "vergleichsweise", "vermutlich", "viel", "viele", "vielen", "vieler", "vieles", "vielfach", "vielleicht", "vielmals", "voll", "vollends", "völlig", "vollkommen", "vollständig", "vom", "von", "vor", "voran", "vorbei", "vorher", "vorne", "vorüber", "während", "währenddessen", "wahrscheinlich", "wann", "war", "wäre", "waren", "wären", "warst", "warum", "was", "weder", "weg", "wegen", "weidlich", "weil", "Weise", "weiß", "weitem", "weiter", "weitere", "weiterem", "weiteren", "weiterer", "weiteres", "weiterhin", "weitgehend", "welche", "welchem", "welchen", "welcher", "welches", "wem", "wen", "wenig", "wenige", "weniger", "wenigstens", "wenn", "wenngleich", "wer", "werde", "werden", "werdet", "weshalb", "wessen", "wichtig", "wie", "wieder", "wiederum", "wieso", "wiewohl", "will", "willst", "wir", "wird", "wirklich", "wirst", "wo", "wodurch", "wogegen", "woher", "wohin", "wohingegen", "wohl", "wohlgemerkt", "wohlweislich", "wollen", "wollt", "wollte", "wollten", "wolltest", "wolltet", "womit", "womöglich", "woraufhin", "woraus", "worin", "wurde", "würde", "würden", "z.B.", "z. B.", "zahlreich", "zeitweise", "ziemlich", "zu", "zudem", "zuerst", "zufolge", "zugegeben", "zugleich", "zuletzt", "zum", "zumal", "zumeist", "zur", "zurück", "zusammen", "zusehends", "zuvor", "zuweilen", "zwar", "zweifellos", "zweifelsfrei", "zweifelsohne", "zwischen"
])

# ===== Textvorverarbeitung =====
def normalize(text):
    if not text:
        return ''
    text = text.lower()
    text = re.sub(r'[^\w\s]', '', text)  # Satzzeichen entfernen
    words = text.split()
    words = [w for w in words if w not in german_stopwords]
    return ' '.join(words)

def safe_int(val, fallback=0):
    try:
        return int(val)
    except (ValueError, TypeError):
        return fallback

def get_anbieter_namen(anbieter_ids, cursor):
    anbieter_ids = set(anbieter_ids)
    format_strings = ','.join(['%s'] * len(anbieter_ids))
    cursor.execute(f"SELECT id, suchname FROM anbieter WHERE id IN ({format_strings})", tuple(anbieter_ids))
    result = cursor.fetchall()
    return {row['id']: row['suchname'] for row in result}

def get_kurse_erschliessung(kurse_ids, cursor):
    kurse_ids = set(kurse_ids)
    format_strings = ','.join(['%s'] * len(kurse_ids))
    cursor.execute(f"""
        SELECT ks.primary_id AS kurs_id, ks.attr_id AS stichwort_id, s.stichwort 
        FROM kurse_stichwort ks
        JOIN stichwoerter s ON ks.attr_id = s.id
        WHERE ks.primary_id IN ({format_strings})
    """, tuple(kurse_ids))
    erschliessung_stichwoerter = {}
    erschliessung_stichwoerter_ids = {}
    for row in cursor.fetchall():
        erschliessung_stichwoerter.setdefault(row['kurs_id'], set()).add(row['stichwort'])
        erschliessung_stichwoerter_ids.setdefault(row['kurs_id'], set()).add(row['stichwort_id'])
    return erschliessung_stichwoerter, erschliessung_stichwoerter_ids

def get_themen_mapping(cursor):
    cursor.execute("SELECT id, thema FROM themen")
    return {row['id']: row['thema'] for row in cursor.fetchall()}

# ===== Kurspaare laden, Ähnlichkeiten berechnen, und Zwischenspeicher vorbereiten =====
log("🚀 Start des Duplikatscans")
log("\n\nVerbinde zur Datenbank...\n")
conn = mysql.connector.connect(**db_config)
cursor = conn.cursor(dictionary=True)

sql = """
    SELECT id, anbieter, titel, beschreibung, sync_src, user_created, user_modified, user_grp, user_access, date_created, date_modified, thema, freigeschaltet
    FROM kurse
    WHERE kurse.anbieter IN (
        SELECT DISTINCT id
        FROM anbieter
        JOIN anbieter_stichwort ON anbieter.id = anbieter_stichwort.primary_id
        WHERE user_grp IN (1, 49)
    ) AND kurse.freigeschaltet IN (0,1,4) 
"""
cursor.execute(sql)
kurse = cursor.fetchall()
df = pd.DataFrame(kurse)
log(f"✅ {len(df)} Kurse aus der Datenbank geladen und zur Analyse vorbereitet.\n")

anbieter_ids = df['anbieter'].unique().tolist()
kurs_ids = df['id'].unique().tolist()

anbieter_namen = get_anbieter_namen(anbieter_ids, cursor)
kurse_erschliessung, kurse_erschliessung_ids = get_kurse_erschliessung(kurs_ids, cursor)
themen_mapping = get_themen_mapping(cursor)

log("Normalisiere Texte...\n")
df['norm'] = (df['titel'] + ' ' + df['beschreibung']).fillna('').apply(normalize)

log("Berechne Ähnlichkeiten...\n")
vectorizer = TfidfVectorizer()
tfidf_matrix = vectorizer.fit_transform(df['norm'])
similarities = cosine_similarity(tfidf_matrix)

threshold = WORT_AEHNLICHKEIT_VORAUSWAHL
dups = []
log("Vergleiche paarweise...\n")
for i in range(len(df)):
    for j in range(i + 1, len(df)):
        score = similarities[i, j]
        if score > threshold:
            dups.append((int(df.iloc[i]['id']), int(df.iloc[j]['id']), round(score * 100)))

log(f"🔍 {len(dups)} Kurspaare mit Textähnlichkeit über {threshold * 100:.0f}% gefunden.\n")
start_ssh_tunnel()

# ======== Batch-Verarbeitung und Speicherung vorbereiten ========
def load_run_stats():
    # Kumulierte Stats über Aufrufe hinweg
    if os.path.exists(RUN_STATS_FILE):
        try:
            with open(RUN_STATS_FILE, "r", encoding="utf-8") as f:
                return json.load(f)
        except Exception:
            pass
    return {"total_equal": 0, "total_unequal": 0, "total_checked": 0}

def save_run_stats(stats):
    # atomisch schreiben
    tmp = RUN_STATS_FILE + ".tmp"
    with open(tmp, "w", encoding="utf-8") as f:
        json.dump(stats, f)
    os.replace(tmp, RUN_STATS_FILE)

def safe_str(val, maxlen=None):
    if val is None:
        return ""
    s = str(val)
    if maxlen and len(s) > maxlen:
        return s[:maxlen]
    return s


def latin1_safe(text):
    if not isinstance(text, str):
        return ""
    # Ersetzt alle Zeichen, die nicht in latin1 darstellbar sind, durch einen leeren String
    return text.encode("latin1", "ignore").decode("latin1")


def save_to_db(duplikate_list, initial_truncate=False):
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor()
    timestamp_now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    if initial_truncate:
        log(f"⚡️ Entferne alte Auto-Einträge (user_modified={TEMPLATE_USER_ID}) aus kurse_duplikate, die von Redaktion nicht verändert wurden ...")
        # cursor.execute("DELETE FROM kurse_duplikate WHERE user_modified = %s", (TEMPLATE_USER_ID,))
    for d in duplikate_list:
        values = (
            safe_int(d['kurse_id1']),
            safe_str(d['kurse_titel1'], 500),
            safe_str(d['anbieter_name1'], 500),
            safe_int(d['freigeschaltet1'], -1),
            safe_str(d['kurse_erschliessung1'], 10000),
            safe_str(d['kurse_beschreibung1'], 10000),
            safe_int(d['kurse_id2']),
            safe_str(d['kurse_titel2'], 500),
            safe_str(d['anbieter_name2'], 500),
            safe_int(d['freigeschaltet2'], -1),
            safe_str(d['kurse_erschliessung2'], 10000),
            safe_str(d['kurse_beschreibung2'], 10000),
            safe_str(latin1_safe(d['duplikat_grund']), 10000), # KI-String manchmal mit UTF-8-Zeichen
            safe_int(d['aehnlichkeit_score']),
            safe_int(d['duplikat']),
            safe_int(d['anbieter_gleich']),
            safe_int(d['erschliessung_gleich']),
            safe_int(d['sync_src']),
            safe_int(d['user_created']),
            safe_int(d['user_modified']),
            safe_int(d['user_grp']),
            safe_int(d['user_access']),
            safe_str(timestamp_now, 19),
            safe_str(timestamp_now, 19)
        )
        try:
            cursor.execute(
                """
                INSERT INTO kurse_duplikate (
                    kurse_id1, kurse_titel1, anbieter_name1, freigeschaltet1, kurse_erschliessung1, kurse_beschreibung1,
                    kurse_id2, kurse_titel2, anbieter_name2, freigeschaltet2, kurse_erschliessung2, kurse_beschreibung2,
                    duplikat_grund, aehnlichkeit_score, duplikat, anbieter_gleich, erschliessung_gleich,
                    sync_src, user_created, user_modified, user_grp, user_access,
                    date_created, date_modified
                ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                """,
                values
            )
        except Exception as e:
            log("❗️ Fehler beim DB-Insert:")
            log(f"  Exception: {e}")
            log(f"  Werte: {repr(values)}")
            raise

    conn.commit()
    cursor.close()
    conn.close()




# ========== Bereits verglichene Paare bestimmen (Wiederaufnahme + reines Inkrement) ==========
# Reihenfolge der IDs ist egal:
def pair_key(pair):
    # (kleinste_id, groesste_id) -> ignoriert die Reihenfolge
    return tuple(sorted([pair[0], pair[1]]))

# (a) Autoritative Quelle: bereits in der DB vorhandene Vergleichspaare.
#     Verhindert Doppel-Eintraege, ermoeglicht reines Inkrement (nur NEUE Paare)
#     und sorgt dafuer, dass bei einem erneuten Lauf NICHTS geloescht / NICHT von
#     vorne begonnen wird. Neu hinzugekommene Kurse erzeugen neue Paare (auch
#     gegen den Bestand) und werden dann verglichen.
existing_pairs = set()
try:
    cursor.execute("SELECT kurse_id1, kurse_id2 FROM kurse_duplikate")
    for row in cursor.fetchall():
        a = safe_int(row.get('kurse_id1'))
        b = safe_int(row.get('kurse_id2'))
        if a > 0 and b > 0:
            existing_pairs.add(pair_key((a, b)))
    log(f"🗃️ Bereits in der DB vorhandene Vergleichspaare: {len(existing_pairs)}")
except Exception as e:
    # Ohne diese Liste koennten Doppel-Eintraege entstehen -> lieber abbrechen.
    log(f"❌ Konnte vorhandene Vergleichspaare nicht aus der DB laden: {e}. Breche ab, um Doppel-Eintraege zu vermeiden.")
    sys.exit(1)

# Die DB-Paare sind die autoritative Quelle dafuer, was schon verglichen wurde.
# (Der lokale Zwischenspeicher wird bewusst NICHT mehr zum Ueberspringen genutzt,
#  damit nach "Alle automatisch erstellten Vergleiche loeschen" wieder neu
#  verglichen werden kann.)
already_processed = set(existing_pairs)

# Alle (text-aehnlichen) Duplikat-Paare als key-Liste
dups_keyed = [ (pair_key((k1, k2)), (k1, k2, score)) for k1, k2, score in dups ]

# Nur Paare, die noch NICHT verglichen wurden; Doppel-Paare ausschliessen
pending_pairs = []
seen_pending = set()
for pk, orig in dups_keyed:
    if pk in already_processed or pk in seen_pending:
        continue
    seen_pending.add(pk)
    pending_pairs.append(orig)

total_pairs = len(dups)
pending_count = len(pending_pairs)
log(f"📝 Bereits verglichen: {len(already_processed)}, text-aehnliche Paare gesamt: {total_pairs}, neu zu vergleichen: {pending_count}")

# Nichts Neues -> NICHTS tun (nichts loeschen, nicht von vorne anfangen).
if pending_count == 0:
    log("✅ Keine neuen Kurspaare zu vergleichen - es ist nichts zu tun.")
    sys.exit(0)

# Die Ergebnistabelle wird NIE automatisch geleert: der Bestand bleibt erhalten,
# es werden ausschliesslich neue Paare ergaenzt (Doppel-Eintraege sind durch den
# Abgleich mit existing_pairs ausgeschlossen).
truncate_table = False
log("⏩ Es werden ausschliesslich neue Vergleichspaare ergaenzt (Bestand bleibt erhalten).")

# ======= Batch-Verarbeitung und LLM-Check =========
alle_duplikate = []
batch_processed = []

# Laufende Zähler nur für diesen Skriptlauf
run_equal = 0
run_unequal = 0
run_checked = 0

# Kumulierte Zähler über mehrere Aufrufe
stats = load_run_stats()

max_checks = min(MAX_LLM_CHECKS, len(pending_pairs))
for index, (kurse_id1, kurse_id2, score) in enumerate(pending_pairs[:max_checks], 1):
    kurs1 = df[df['id'] == kurse_id1].iloc[0]
    kurs2 = df[df['id'] == kurse_id2].iloc[0]

    log(f"{index} / {max_checks} (gesamt {total_pairs}):")
    log(f"🔍 Vergleiche: ID {kurse_id1} vs {kurse_id2}")

    decision, begruendung = llm_check_duplikat(
        kurs1['titel'], kurs1['beschreibung'],
        kurs2['titel'], kurs2['beschreibung']
    )

    log(f"🔍 Entscheidung: {decision}")
    log(f"🔍 Begründung: {begruendung}\n")

    duplikat_flag = 1 if decision == "gleich" else 0

    # Laufende Zähler aktualisieren  ⬅️ MUSS im Loop stehen
    run_checked += 1
    if duplikat_flag == 1:
        run_equal += 1
    else:
        run_unequal += 1

    # Anbieter
    anbieter_name1 = anbieter_namen.get(kurs1['anbieter'], "")
    anbieter_name2 = anbieter_namen.get(kurs2['anbieter'], "")
    anbieter_gleich = 1 if kurs1['anbieter'] == kurs2['anbieter'] else 0

    # Erschließung
    erschliessung1 = []
    if kurse_erschliessung.get(kurse_id1):
        erschliessung1.extend(sorted(list(kurse_erschliessung[kurse_id1])))
    if kurs1['thema'] and kurs1['thema'] in themen_mapping and themen_mapping[kurs1['thema']]:
        erschliessung1.append(f"Thema: {themen_mapping[kurs1['thema']]}")
    erschliessung1_str = ', '.join(erschliessung1)

    erschliessung2 = []
    if kurse_erschliessung.get(kurse_id2):
        erschliessung2.extend(sorted(list(kurse_erschliessung[kurse_id2])))
    if kurs2['thema'] and kurs2['thema'] in themen_mapping and themen_mapping[kurs2['thema']]:
        erschliessung2.append(f"Thema: {themen_mapping[kurs2['thema']]}")
    erschliessung2_str = ', '.join(erschliessung2)

    # Erschließung gleich: gleiches Thema + identische Stichwort-Menge (unabhängig von Reihenfolge)
    thema1_id = safe_int(kurs1['thema'])
    thema2_id = safe_int(kurs2['thema'])
    stichwort_ids1 = kurse_erschliessung_ids.get(kurse_id1, set())
    stichwort_ids2 = kurse_erschliessung_ids.get(kurse_id2, set())
    erschliessung_gleich = 1 if (thema1_id == thema2_id and stichwort_ids1 == stichwort_ids2) else 0
    
    # Eintrag erzeugen
    dupl = {
        'kurse_id1': kurse_id1,
        'kurse_titel1': kurs1['titel'],
        'anbieter_name1': anbieter_name1,
        'kurse_erschliessung1': erschliessung1_str,
        'kurse_beschreibung1': kurs1['beschreibung'],
        'freigeschaltet1': safe_int(kurs1['freigeschaltet'], -1),
        'kurse_id2': kurse_id2,
        'kurse_titel2': kurs2['titel'],
        'anbieter_name2': anbieter_name2,
        'kurse_erschliessung2': erschliessung2_str,
        'kurse_beschreibung2': kurs2['beschreibung'],
        'freigeschaltet2': safe_int(kurs2['freigeschaltet'], -1),
        'duplikat_grund': begruendung,
        'aehnlichkeit_score': score,
        'duplikat': duplikat_flag,
        'anbieter_gleich': anbieter_gleich,
        'erschliessung_gleich': erschliessung_gleich,
        'sync_src': safe_int(kurs1['sync_src']),
        'user_created': TEMPLATE_USER_ID,
        'user_modified': TEMPLATE_USER_ID,
        'user_grp': safe_int(kurs1['user_grp']),
        'user_access': USER_ACCESS
    }
    alle_duplikate.append(dupl)
    batch_processed.append(pair_key((kurse_id1, kurse_id2)))

    # Speichere Batch in DB, wenn Batchgröße erreicht  ⬅️ MUSS im Loop stehen
    if len(alle_duplikate) >= MAX_BATCH_SIZE:
        # Batch-Stats berechnen, bevor die Liste geleert wird
        batch_equal = sum(1 for d in alle_duplikate if d['duplikat'] == 1)
        batch_unequal = len(alle_duplikate) - batch_equal

        # Kumulierte Zähler (über Aufrufe) aktualisieren
        stats["total_checked"] += len(alle_duplikate)
        stats["total_equal"] += batch_equal
        stats["total_unequal"] += batch_unequal
        save_run_stats(stats)

        log(f"💾 Speichere Batch mit {len(alle_duplikate)} Ergebnissen in die Datenbank ...")
        save_to_db(alle_duplikate, initial_truncate=truncate_table)
        truncate_table = False  # nur beim ersten Batch

        # Bereits verarbeitete Paare updaten
        already_processed.update(batch_processed)
        with open(PROCESSED_FILE, "w", encoding="utf-8") as f:
            json.dump([list(x) for x in already_processed], f)
        alle_duplikate.clear()
        batch_processed.clear()
        log("✅ Batch gespeichert.\n")


# Speichere verbleibenden Rest (falls kleiner als Batch)
if alle_duplikate:
    # Rest-Stats berechnen
    batch_equal = sum(1 for d in alle_duplikate if d['duplikat'] == 1)
    batch_unequal = len(alle_duplikate) - batch_equal

    stats["total_checked"] += len(alle_duplikate)
    stats["total_equal"] += batch_equal
    stats["total_unequal"] += batch_unequal
    save_run_stats(stats)

    log(f"💾 Speichere abschließenden Batch mit {len(alle_duplikate)} Ergebnissen ...")
    save_to_db(alle_duplikate, initial_truncate=truncate_table)
    # processed-File updaten
    already_processed.update(batch_processed)
    with open(PROCESSED_FILE, "w", encoding="utf-8") as f:
        json.dump([list(x) for x in already_processed], f)
    log("✅ Finaler Batch gespeichert.\n")

# Zusammenfassung
log("📊 Zusammenfassung (dieser Lauf):")
log(f"- Kurse insgesamt: {len(df)}")
log(f"- Textähnliche Paare (über {threshold * 100:.0f}%): {total_pairs}")
log(f"- Davon in diesem Lauf geprüft: {run_checked}")
log(f"- Als gleich erkannt (Lauf): {run_equal}")
log(f"- Als unterschiedlich erkannt (Lauf): {run_unequal}\n")

log("📊 Kumulierte Zusammenfassung (seit Start dieses Zyklus):")
log(f"- Insgesamt geprüft: {stats['total_checked']}")
log(f"- Als gleich erkannt (kumuliert): {stats['total_equal']}")
log(f"- Als unterschiedlich erkannt (kumuliert): {stats['total_unequal']}\n")


# Reines Inkrement: es wird NICHTS geloescht und NICHT zurueckgesetzt.
# Noch nicht verglichene Paare (z.B. wegen MAX_LLM_CHECKS-Limit pro Lauf) werden
# beim naechsten Aufruf automatisch ergaenzt - der Abgleich mit den DB-Paaren
# stellt sicher, dass nichts doppelt verglichen oder eingetragen wird.
remaining = pending_count - run_checked
if remaining > 0:
    log(f"ℹ️ Noch offen fuer kuenftige Laeufe: {remaining} neue Paar(e) (Limit pro Lauf: {MAX_LLM_CHECKS}).\n\nFertig.")
else:
    log("✅ Alle aktuell neuen Vergleichspaare wurden verglichen.\n\nFertig.")
sys.exit(0)

