# NOW-Einwilligung – Formular & Werkzeuge

Formular, über das Weiterbildungsanbieter aus einem WISY-Kursportal der
**Übermittlung ihrer Anbieter- und Kursdaten an das nationale Portal
„mein NOW“** (mein-now.de) zustimmen bzw. diese **widerrufen** können.

Dient der Anbindung eines WISY-Kursportals an `mein-now.de` im Rahmen der
Kooperationsvereinbarung zwischen dem KoopV-Partner und der Bundesagentur für
Arbeit (BA); ausführende Stelle ist der jeweilige WISY-Betreiber.

> Betreiberindividuelle Werte (Portalname, Adressen, IDs, Geheimnisse) stehen
> ausschließlich in `inc/config.inc.php`. Stellen mit
> `###WISY-BETREIBER-INDIVIDUELL-GGF-TEXT-ERSETZEN###` vor dem Einsatz anpassen.

---

## Inhalt

```
now-einwilligung/
├── index.php              Einwilligungs-Formular (+ POST-Verarbeitung)
├── widerruf.php           Widerrufs-Formular (+ POST-Verarbeitung)
├── linkgenerator.php      Admin-Werkzeug: personalisierte Links & CSV fürs Anschreiben
├── assets/
│   ├── style.css
│   ├── form.js            Tab-Logik + Aktivierung des Absenden-Buttons (Einwilligung)
│   └── widerruf.js        Aktivierung des Absenden-Buttons (Widerruf)
├── inc/
│   ├── config.inc.php     >>> HIER vor Produktivbetrieb Werte setzen <<<
│   ├── bootstrap.inc.php   Minimale WISY-Laufzeit (DB, Framework, Wiki→HTML)
│   ├── now-anschreiben.inc.php  Versioniertes Anschreiben (HTML+Text) mit Links
│   └── functions.inc.php   Encoding, Glossar-Rendering, Speichern, Mail (SMTP), Journal
└── README.md
```

Erreichbar unter `https://<ihre-domain>/now-einwilligung/`.

**Im WISY-Redaktionssystem (admin/) zusätzlich:**
- `admin/config/edit_plugin_anbieter_0.inc.php` – Panel „NOW-Einwilligung“ in der
  Anbieter-Maske (Status, Links, Historie, CSV-Export pro Anbieter).
- `admin/config/db.inc.php` – Feld `now_zustimmung` als **READONLY** in der Maske
  (gesperrt; nur per Formular änderbar).
- `admin/config/lang/basic/de.php` – Menü-Label `_EDIT_PLUGIN_ANBIETER_0`.

---

## Vor dem ersten Einsatz: `inc/config.inc.php`

| Konstante | Bedeutung |
|---|---|
| `NOW_LINK_SECRET` | **Pflicht.** Langes Zufallsgeheimnis zum Signieren der personalisierten Links. Erzeugen: `php -r "echo bin2hex(random_bytes(32));"` |
| `NOW_ADMIN_PW` | Passwort für `linkgenerator.php` (Aufruf `?pw=…`). |
| `NOW_ADMIN_EMAIL` | **Redaktions-Adresse des WISY-Betreibers**, die bei **jeder** Einwilligung **und** jedem Widerruf eine Kopie erhält (als BCC). |
| `NOW_CONTACT_EMAIL` | Kontaktadresse für Rückfragen, die im **Anschreiben** genannt wird. |
| `NOW_BASEURL_FROM_REQUEST` | `true` (Standard): erzeugte Links übernehmen den aktuellen Request-Host → Sandbox-Aufrufe liefern Sandbox-Links. `false`: immer `NOW_PUBLIC_BASEURL`. |
| `NOW_PUBLIC_BASEURL` | Fallback-Basis-URL für die erzeugten Links (CLI / wenn `NOW_BASEURL_FROM_REQUEST=false`). |
| `NOW_PORTAL_ID` | Portal-ID des eigenen Portals, aus der die Einstellungen inkl. SMTP (`mail.extern.*`) geladen werden (0 = nur Host-Erkennung). |
| `NOW_PORTAL_NAME` / `NOW_OPERATOR_NAME` / `NOW_LOGO_PATH` | Anzeigename des Portals / Name der betreibenden Stelle / Logo-URL (alles betreiberindividuell). |
| `NOW_GLOSSAR_AGB_ID` / `NOW_GLOSSAR_DATENSCHUTZ_ID` | Ratgeber-IDs (679 / 11721). |
| `NOW_GLOSSAR_RECHTE_ID` | Ratgeber-ID der mein-NOW-Übermittlungsbedingungen (1. Reiter), 14931. |
| `NOW_PAKET_VERSION` | Versionskennung des Einwilligungs-Pakets. Bei Textänderungen erhöhen. |
| `NOW_STICHWORT_JA` / `NOW_STICHWORT_NEIN` | Verwaltungsstichwort-IDs „NOW ja“ (1812461) / „NOW nein“ (1812471). |
| `NOW_STICHWORT_ANSCHREIBEN` | Stichwort „NOW Anschreiben initial“ (2128841), wird beim Anschreiben-Versand automatisch gesetzt. |
| `NOW_FILTER_BY_STICHWORT` | `true` = nur Anbieter mit „NOW ja“ in Auswahl/Generator. |
| `NOW_MAIL_FROM` / `NOW_MAIL_FROMNAME` | Absender-Fallback (wenn keine SMTP-Einstellungen). |

---

## E-Mail-Versand (SMTP / PHPMailer)

Da direkt per `mail()` versandte Nachrichten häufig nicht zugestellt werden,
versendet `now_send_mail()` bevorzugt **per SMTP über PHPMailer** – mit denselben
**Portaleinstellungen** (wie der WISY-Kern):

```
mail.extern, mail.extern.host, mail.extern.port, mail.extern.smtpSecure,
mail.extern.smtpAuth, mail.extern.username, mail.extern.password,
mail.extern.from, mail.extern.fromName
```

Voraussetzung: Konstante `PHPMAILER_PATH` ist gesetzt (serverseitige
`admin/config/config.inc.php`) und die Datei existiert. Andernfalls Fallback auf
`mail()`. Im lokalen Test (`*.local` / CLI) wird **nicht** versendet (nur Logeintrag).
Anders als im Kern wird `CharSet = UTF-8` gesetzt (kein `utf8_decode`), damit
typografische Zeichen erhalten bleiben.

Jede Bestätigung geht an die **Pflege-E-Mail** des Anbieters **und** als BCC an
`NOW_ADMIN_EMAIL` – so fällt auf, wenn jemand Fremdes den Vorgang ausfüllt.

---

## Ablauf

1. **Anschreiben:** `linkgenerator.php?pw=…` erzeugt je Anbieter den
   Einwilligungs- **und** Widerrufs-Link plus Pflege-E-Mail (CSV via `&format=csv`).
   Standardmäßig nur Anbieter mit Stichwort **„NOW ja“** (`&all=1` hebt das auf,
   `&only=offen` zeigt nur noch nicht eingewilligte).
2. **Einwilligung** (`index.php`): Anbieter-Erkennung über `?a=<id>&t=<token>`
   (signiert) oder per **Dropdown** (nur „NOW ja“-Anbieter). Drei Texte zur
   Bestätigung: NOW-Übermittlungsbedingungen, AGB (g679), Datenschutz (g11721).
   Nach Absenden: Eintrag in `anbieter_now_einwilligung`, `now_zustimmung=1`,
   Journaleintrag, Bestätigungs-E-Mails (Anbieter + Redaktion).
3. **Widerruf** (`widerruf.php`): analog, setzt `now_zustimmung=0`, Eintrag mit
   `aktion='widerrufen'`, ebenfalls E-Mails an Anbieter + Redaktion.

---

## Redaktionssystem (admin/)

- **Status gesperrt:** `anbieter.now_zustimmung` erscheint in der Anbieter-Maske
  als READONLY-Feld (Sektion „mein NOW“). Es ist **nicht** per Klick änderbar –
  Änderungen ausschließlich über die Formular-Links (vermeidet doppelte Logik /
  versehentliche Änderungen). Der Save-Pfad schreibt READONLY-Felder nie.
- **Panel „NOW-Einwilligung“** (Menüpunkt in der Anbieter-Bearbeitung,
  `edit_plugin_anbieter_0`): zeigt Status, **Einwilligungs- und Widerrufs-Link**
  (zum einzelnen manuellen Versand) plus den Knopf **„Anschreiben jetzt an diesen
  Anbieter senden“** (versendet die HTML-Einladung aus `inc/now-anschreiben.inc.php`
  mit persönlichem Link per SMTP an die Pflege-Adresse). Bei erfolgreichem Versand
  werden automatisch ein **Journaleintrag** („…: NOW-Anbieter anschreiben gesendet
  (WISY)“) und das **Stichwort „NOW Anschreiben initial“** gesetzt. Weiter: die **Historie** der
  Einwilligungen/Widerrufe und einen **CSV-Export** (inkl. Textkopien) für genau
  diesen Anbieter.

---

## Kodierung (wichtig)

- WISY-DB ist **ISO-8859-1 (latin1)**; Ratgeberseiten liegen in **alter
  Wiki-Syntax** vor → Rendering via `WISY_WIKI2HTML_CLASS`
  (`encode_windows_chars()` + `->run()`).
- Frontend-Ausgabe ist **UTF-8**, Backend (admin) ist **latin1**.
- WISY setzt global `default_charset = ISO-8859-1` (in `admin/sql_curr.inc.php`),
  wodurch PHP sonst einen latin1-`Content-Type` sendet. `index.php`, `widerruf.php`
  und `linkgenerator.php` setzen deshalb **explizit** `header('Content-Type: text/html; charset=UTF-8')`
  (vor jeder Ausgabe) – der explizite Header schlägt `default_charset` und das `<meta charset>`.
- Helfer in `functions.inc.php`: `now_to_utf8()`, `now_to_latin1()` (mit
  Transliteration), `now_archive()` (HTML-Kopien als ASCII-Entities → verlustfrei
  in latin1-Spalte), `now_archive_to_utf8()` (Rück-Dekodierung für Export).

---

## Änderungen am WISY-Kern (separat dokumentiert, NICHT committet)

| Datei | Änderung |
|---|---|
| `admin/config/db.sql` | **Neue Tabelle** `anbieter_now_einwilligung` (revisionssichere Historie). |
| `admin/config/db.inc.php` | `now_zustimmung` als READONLY-Feld in der Anbieter-Maske. |
| `admin/config/lang/basic/de.php` | Menü-Label `_EDIT_PLUGIN_ANBIETER_0` = „NOW-Einwilligung“. |
| `admin/config/edit_plugin_anbieter_0.inc.php` | **Neu** – Redaktions-Panel (Status/Links/Historie/Export). |

Das vorhandene Feld `anbieter.now_zustimmung` (REST-API v2) bleibt das **Live-Flag**,
das der mein-NOW-Adapter auswertet.

> Diese Änderungen sind **nicht** für den Commit vorgemerkt (kein `git add`).

Migration auf einer bestehenden Datenbank:

```sql
CREATE TABLE `anbieter_now_einwilligung` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `anbieter` int(11) NOT NULL DEFAULT 0,
  `anbieter_suchname` varchar(200) NOT NULL DEFAULT '',
  `datum` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `aktion` varchar(20) NOT NULL DEFAULT 'erteilt',
  `vorname` varchar(200) NOT NULL DEFAULT '',
  `nachname` varchar(200) NOT NULL DEFAULT '',
  `email_bestaetigung` varchar(200) NOT NULL DEFAULT '',
  `version_paket` varchar(40) NOT NULL DEFAULT '',
  `version_now_rechte` varchar(40) NOT NULL DEFAULT '',
  `version_agb` varchar(40) NOT NULL DEFAULT '',
  `version_datenschutz` varchar(40) NOT NULL DEFAULT '',
  `text_now_rechte` longtext NOT NULL,
  `text_agb` longtext NOT NULL,
  `text_datenschutz` longtext NOT NULL,
  `hash` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `anbieter` (`anbieter`),
  KEY `datum` (`datum`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
```

---

## Quelle der NOW-Übermittlungsbedingungen

Die mein-NOW-Übermittlungsbedingungen (1. Reiter im Formular) liegen als
**Ratgeber-/Glossar-Beitrag** (`NOW_GLOSSAR_RECHTE_ID`) in der WISY-DB und werden –
wie AGB/Datenschutz – aus der DB gerendert (alte Wiki-Syntax → HTML). So muss der
Text **nicht im Code** gepflegt werden. Inhaltliche Grundlage ist die jeweilige
Kooperationsvereinbarung des Betreibers mit der Bundesagentur für Arbeit.

> Rechtlicher Hinweis: Die Bedingungen sind je Betreiber/Kooperationsvereinbarung
> unterschiedlich und im Ratgeber-Beitrag zu pflegen. Vor Versand rechtlich
> gegenprüfen / mit dem KoopV-Partner abstimmen.
