<?php
/*******************************************************************************
 NOW-Einwilligung - Reparatur verlorener Journaleintraege
 ******************************************************************************
 Einmal-Werkzeug. Stellt die Journalzeilen in anbieter.notizen wieder her, die
 durch ein Speichern der Anbieter-Maske nach einer Aktion im NOW-Popup
 ueberschrieben wurden (Lost Update, s. README).

 Quelle ist die WISY-Protokolldatei des betreffenden Tages
 (<g_logs_dir>/JJJJ-MM-TT-cms-<datenbank>.txt). Sie enthaelt den alten Wert des
 Feldes als gekuerzten Diff; die Journalzeile selbst steht darin vollstaendig,
 weil sie der abweichende Teil ist. Aus der Zeile wird alles bis einschliesslich
 "<n> Kurs(e) auf" uebernommen und der feste Rest ergaenzt - der Satz ist im
 erzeugenden Code deterministisch.

 Jede Wiederherstellung wird gegen anbieter_now_einwilligung geprueft: ohne
 passenden Protokolleintrag (aktion='erteilt_redaktion', gleiche Minute) wird
 nichts geschrieben.

 date_modified bleibt bewusst unangetastet. Das Journal ist redaktionsintern;
 ein Anfassen wuerde fuer alle betroffenen Anbieter einen Delta-Abgleich
 (Sync/Export) ausloesen.

 Aufruf (Trockenlauf, schreibt nichts):
   php now-einwilligung/tools/journal-reparatur.php --log=/pfad/YYYY-MM-dd-cms-db######_##.txt
 Schreiben:
   php now-einwilligung/tools/journal-reparatur.php --log=... --apply

 Datei in UTF-8; Protokolldatei und Datenbank sind ISO-8859-1. Es findet
 bewusst KEINE Umcodierung statt: die Bytes wandern unveraendert aus der
 Protokolldatei in die Spalte. Alle Suchmuster sind ASCII bzw. byteweise.
 *******************************************************************************/

if (PHP_SAPI !== 'cli') {
    die('Nur ueber die Kommandozeile.');
}

// --- Argumente --------------------------------------------------------------
$opt = getopt('', array('log:', 'apply', 'limit::', 'ignore-db-check'));
if (!isset($opt['log'])) {
    fwrite(STDERR, "Aufruf: php journal-reparatur.php --log=<protokolldatei> [--apply] [--limit=N]\n");
    exit(1);
}
$logDatei = $opt['log'];
$schreiben = isset($opt['apply']);
$limit     = isset($opt['limit']) ? intval($opt['limit']) : 0;

if (!is_readable($logDatei)) {
    fwrite(STDERR, "Protokolldatei nicht lesbar: $logDatei\n");
    exit(1);
}

// --- WISY-Datenbank ---------------------------------------------------------
$wisyRoot = dirname(dirname(__DIR__));            
chdir($wisyRoot);                                 // WISY-Klassen nutzen relative Pfade
if (!defined('IN_WISY')) { define('IN_WISY', true); }
if (!defined('PHP7'))    { define('PHP7', (PHP_VERSION_ID >= 70000)); }
require_once($wisyRoot . '/admin/sql_curr.inc.php');
require_once($wisyRoot . '/admin/config/config.inc.php');
if (!class_exists('DB_Admin')) {
    fwrite(STDERR, "DB_Admin nicht gefunden - WISY-Konfiguration pruefen.\n");
    exit(1);
}
$db  = new DB_Admin;

// --- Gegenprobe: gehoeren Protokolldatei und Datenbank zusammen? -----------
// Der Dateiname des WISY-Protokolls endet auf den Datenbanknamen
// (writer.inc.php: <datum>-cms-<datenbank>.txt). Weicht er von der Datenbank
// dieser Installation ab, wuerde in den falschen Bestand geschrieben - etwa
// wenn das Skript in der Sandbox liegt, die Datei aber vom Livesystem stammt.
if (preg_match('/-cms-(.+)\.txt$/', basename($logDatei), $mdb)) {
    if ($mdb[1] !== $db->Database && !isset($opt['ignore-db-check'])) {
        fwrite(STDERR, "Abbruch: Die Protokolldatei gehoert zur Datenbank \"{$mdb[1]}\",\n"
                     . "diese Installation verbindet sich mit \"{$db->Database}\".\n"
                     . "Passendes Verzeichnis waehlen oder --ignore-db-check setzen.\n");
        exit(1);
    }
} else {
    fwrite(STDERR, "Hinweis: Dateiname nennt keine Datenbank, Gegenprobe entfaellt.\n");
}
$db2 = new DB_Admin;   // zweiter Ergebnispuffer (dieselbe physische Verbindung,
                       // DB_Sql teilt sie ueber $GLOBALS['g_link_id'])

// --- Protokolldatei auswerten ----------------------------------------------
$zeilen = file($logDatei, FILE_IGNORE_NEW_LINES);
$faelle = array();
$auffaellig = array();

foreach ($zeilen as $nr => $zeile) {
    $f = explode("\t", $zeile);
    if (count($f) < 7 || $f[1] !== 'anbieter' || $f[4] !== 'edit') {
        continue;
    }
    $anbieterId = intval($f[2]);
    if ($anbieterId <= 0) {
        continue;
    }

    // Feld "notizen" suchen; der ALTE Wert steht unmittelbar dahinter.
    $alt = null;
    for ($i = 5; $i < count($f) - 1; $i++) {
        if ($f[$i] === 'notizen') { $alt = $f[$i + 1]; break; }
    }
    if ($alt === null || strpos($alt, 'redaktionell ERTEILT') === false) {
        continue;
    }

    // Plausibilitaet: genau ein Eintrag, kein abgeschnittener Anfang.
    if (substr_count($alt, 'redaktionell ERTEILT') !== 1) {
        $auffaellig[] = array($anbieterId, 'mehrere ERTEILT-Eintraege in einer Zeile', $nr + 1);
        continue;
    }
    if (substr($alt, 0, 3) === '...') {
        $auffaellig[] = array($anbieterId, 'Protokollwert vorn gekuerzt', $nr + 1);
        continue;
    }
    if (!preg_match('/^(\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}): NOW-.bermittlung redaktionell ERTEILT/', $alt, $m)) {
        $auffaellig[] = array($anbieterId, 'Zeilenanfang unerwartet', $nr + 1);
        continue;
    }
    $datumAnzeige = $m[1];                                   // 24.08.2026 10:44

    // Feste Satzende ergaenzen: der erzeugende Code schreibt immer
    // "<n> Kurs(e) auf Uebermittlung gesetzt." + Zeilenumbruch.
    if (!preg_match('/[0-9]+ Kurs\(e\) auf/', $alt, $mk, PREG_OFFSET_CAPTURE)) {
        $auffaellig[] = array($anbieterId, 'Kurszahl im Protokoll nicht mehr enthalten', $nr + 1);
        continue;
    }
    $ende    = $mk[0][1] + strlen($mk[0][0]);
    $eintrag = substr($alt, 0, $ende) . " \xDCbermittlung gesetzt.\n";

    $faelle[] = array(
        'id'      => $anbieterId,
        'datum'   => $datumAnzeige,
        'eintrag' => $eintrag,
        'zeile'   => $nr + 1,
    );
}

// --- Abgleich mit Historie und Datenbestand ---------------------------------
$stat = array('wiederhergestellt' => 0, 'schon_vorhanden' => 0, 'ohne_historie' => 0,
              'anbieter_fehlt' => 0, 'uebersprungen' => count($auffaellig));

printf("%s\n", $schreiben ? 'MODUS: SCHREIBEN' : 'MODUS: Trockenlauf (keine Aenderung)');
printf("Protokolldatei: %s\nGefundene Faelle: %d\n\n", $logDatei, count($faelle));
printf("%-10s %-17s %-8s %s\n", 'Anbieter', 'Zeitpunkt', 'Kurse', 'Ergebnis');

$n = 0;
foreach ($faelle as $fall) {
    if ($limit > 0 && $n >= $limit) { break; }
    $n++;

    $id = $fall['id'];
    preg_match('/([0-9]+) Kurs\(e\)/', $fall['eintrag'], $mk);
    $kurse = isset($mk[1]) ? $mk[1] : '?';

    // 1) Anbieter vorhanden?
    $db->query("SELECT suchname, notizen FROM anbieter WHERE id=" . $id);
    if (!$db->next_record()) {
        printf("%-10d %-17s %-8s %s\n", $id, $fall['datum'], $kurse, 'Anbieter nicht gefunden');
        $stat['anbieter_fehlt']++;
        continue;
    }
    $notizen = (string)$db->fs('notizen');

    // 2) schon vorhanden? (idempotent)
    if (strpos($notizen, $fall['datum'] . ': NOW-') !== false) {
        printf("%-10d %-17s %-8s %s\n", $id, $fall['datum'], $kurse, 'bereits vorhanden, uebersprungen');
        $stat['schon_vorhanden']++;
        continue;
    }

    // 3) Gegenprobe in der revisionssicheren Historie: gleiche Minute, gleiche Aktion
    $ts = DateTime::createFromFormat('d.m.Y H:i', $fall['datum']);
    if (!$ts) {
        printf("%-10d %-17s %-8s %s\n", $id, $fall['datum'], $kurse, 'Zeitstempel unlesbar');
        $stat['ohne_historie']++;
        continue;
    }
    $von = $ts->format('Y-m-d H:i:00');
    $bis = $ts->format('Y-m-d H:i:59');
    $db2->query("SELECT COUNT(*) AS n FROM anbieter_now_einwilligung
                  WHERE anbieter=" . $id . " AND aktion='erteilt_redaktion'
                    AND datum BETWEEN " . $db2->quote($von) . " AND " . $db2->quote($bis));
    $inHistorie = $db2->next_record() ? intval($db2->f('n')) : 0;
    if ($inHistorie < 1) {
        printf("%-10d %-17s %-8s %s\n", $id, $fall['datum'], $kurse, 'KEIN Historieneintrag - nicht geschrieben');
        $stat['ohne_historie']++;
        continue;
    }

    // 4) Voranstellen
    if ($schreiben) {
        $db2->query("UPDATE anbieter SET notizen=" . $db2->quote($fall['eintrag'] . $notizen)
                    . " WHERE id=" . $id);
        printf("%-10d %-17s %-8s %s\n", $id, $fall['datum'], $kurse, 'wiederhergestellt');
    } else {
        printf("%-10d %-17s %-8s %s\n", $id, $fall['datum'], $kurse, 'wuerde wiederhergestellt');
    }
    $stat['wiederhergestellt']++;
}

// --- Auffaelligkeiten und Bilanz -------------------------------------------
if ($auffaellig) {
    printf("\nNicht ausgewertete Protokollzeilen:\n");
    foreach ($auffaellig as $a) {
        printf("  Anbieter %-10d Zeile %-6d %s\n", $a[0], $a[2], $a[1]);
    }
}

printf("\nBilanz\n");
foreach ($stat as $k => $v) {
    printf("  %-20s %d\n", $k, $v);
}
if (!$schreiben) {
    printf("\nZum Schreiben denselben Aufruf mit --apply wiederholen.\n");
}
