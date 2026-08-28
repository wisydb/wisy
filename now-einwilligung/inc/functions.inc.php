<?php
/*******************************************************************************
 NOW-Einwilligung – Hilfsfunktionen
 ******************************************************************************
 Konventionen:
 - Das Formular/Seitenausgabe ist UTF-8.
 - Die WISY-Datenbank ist latin1 (ISO-8859-1). Daher: beim Lesen latin1->UTF-8,
   beim Schreiben UTF-8->latin1.
 *******************************************************************************/

if (!defined('NOW_EINW_IN')) {
    die('Direktaufruf nicht erlaubt.');
}

/*------------------------------------------------------------------
 Encoding-Helfer
------------------------------------------------------------------*/
function now_to_utf8($s)   { return mb_convert_encoding((string)$s, 'UTF-8', 'ISO-8859-1'); }
function now_h($s)         { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/**
 * UTF-8 -> ISO-8859-1 für kurze Felder/Journal. Nicht in latin1 darstellbare
 * Zeichen (z. B. typografische Anführungszeichen „ " – €) werden transliteriert,
 * damit nichts verloren geht und keine Mojibake entsteht.
 */
function now_to_latin1($s)
{
    $s = (string)$s;
    if (function_exists('iconv')) {
        $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $s);
        if ($out !== false) {
            return $out;
        }
    }
    return mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
}

/**
 * Erzeugt aus UTF-8-HTML eine verlustfreie, ASCII-sichere Archiv-Kopie:
 * alle Nicht-ASCII-Zeichen werden in numerische HTML-Entities (&#nnn;)
 * umgewandelt. So lässt sich der HTML-Text unverfälscht in einer latin1-Spalte
 * ablegen und später identisch wieder darstellen.
 */
function now_archive($html)
{
    return mb_encode_numericentity((string)$html, array(0x80, 0xffff, 0, 0xffff), 'UTF-8');
}

/** Umkehrung von now_archive(): ASCII-Entity-HTML -> UTF-8-HTML (für Anzeige/Export). */
function now_archive_to_utf8($stored)
{
    return html_entity_decode((string)$stored, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/*------------------------------------------------------------------
 Personalisierte, signierte Links
------------------------------------------------------------------*/
function now_token($anbieterId)
{
    return substr(hash_hmac('sha256', 'now-einw|' . intval($anbieterId), NOW_LINK_SECRET), 0, 16);
}

function now_token_valid($anbieterId, $token)
{
    if ($token === '' || $token === null) {
        return false;
    }
    return hash_equals(now_token($anbieterId), (string)$token);
}

/**
 * Liefert die Basis-URL (Schema + Host) für die zu erzeugenden Formular-Links.
 * Standard: abgeleitet vom AKTUELLEN Request-Host – so spiegeln sich Sandbox-
 * Aufrufe (z. B. sandbox.<ihre-domain>) automatisch im erzeugten Link wider.
 * Fallback (z. B. CLI, oder wenn
 * NOW_BASEURL_FROM_REQUEST === false): die konfigurierte NOW_PUBLIC_BASEURL.
 */
function now_public_baseurl()
{
    $forceConfig = (defined('NOW_BASEURL_FROM_REQUEST') && NOW_BASEURL_FROM_REQUEST === false);
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

    if (!$forceConfig && $host !== '' && preg_match('/^[A-Za-z0-9.\-:]+$/', $host)) {
        $https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
              || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
              || (isset($_SERVER['SERVER_PORT']) && intval($_SERVER['SERVER_PORT']) === 443);
        return ($https ? 'https' : 'http') . '://' . $host;
    }
    return defined('NOW_PUBLIC_BASEURL') ? NOW_PUBLIC_BASEURL : '';
}

/** Erzeugt den vollständigen, personalisierten Einwilligungs-Link für einen Anbieter. */
function now_build_link($anbieterId, $baseUrl = null)
{
    $anbieterId = intval($anbieterId);
    if ($baseUrl === null) { $baseUrl = now_public_baseurl(); }
    return rtrim($baseUrl, '/') . '/now-einwilligung/?a=' . $anbieterId . '&t=' . now_token($anbieterId);
}

/** Erzeugt den personalisierten Widerrufs-Link für einen Anbieter. */
function now_build_widerruf_link($anbieterId, $baseUrl = null)
{
    $anbieterId = intval($anbieterId);
    if ($baseUrl === null) { $baseUrl = now_public_baseurl(); }
    return rtrim($baseUrl, '/') . '/now-einwilligung/widerruf.php?a=' . $anbieterId . '&t=' . now_token($anbieterId);
}

/*------------------------------------------------------------------
 Anbieter-Daten
------------------------------------------------------------------*/
/** Liest einen freigeschalteten Anbieter; gibt assoziatives Array (UTF-8) oder null. */
function now_load_anbieter($anbieterId)
{
    $db = $GLOBALS['now_db'];
    $db->query("SELECT id, suchname, pflege_email, anspr_email, now_zustimmung, now_zustimmung_fix
                  FROM anbieter
                 WHERE freigeschaltet=1 AND id=" . intval($anbieterId));
    if (!$db->next_record()) {
        return null;
    }
    return array(
        'id'                 => intval($db->f('id')),
        'suchname'           => now_to_utf8($db->fs('suchname')),
        'pflege_email'       => trim((string)$db->fs('pflege_email')),
        'anspr_email'        => trim((string)$db->fs('anspr_email')),
        'now_zustimmung'     => intval($db->f('now_zustimmung')),
        'now_zustimmung_fix' => intval($db->f('now_zustimmung_fix')),
    );
}

/**
 * Liste der für die NOW-Übermittlung in Frage kommenden, freigeschalteten
 * Anbieter für das Dropdown (id => suchname, UTF-8).
 * Vorauswahl per Verwaltungsstichwort "NOW ja" (sofern NOW_FILTER_BY_STICHWORT).
 */
function now_list_anbieter()
{
    $db  = $GLOBALS['now_db'];
    $out = array();

    if (NOW_FILTER_BY_STICHWORT) {
        $sql = "SELECT a.id, a.suchname
                  FROM anbieter a
                  JOIN anbieter_stichwort s ON s.primary_id = a.id AND s.attr_id = " . intval(NOW_STICHWORT_JA) . "
                 WHERE a.freigeschaltet = 1
              ORDER BY a.suchname_sorted, a.suchname";
    } else {
        $sql = "SELECT id, suchname FROM anbieter WHERE freigeschaltet=1 ORDER BY suchname_sorted, suchname";
    }
    $db->query($sql);
    while ($db->next_record()) {
        $out[intval($db->f('id'))] = now_to_utf8($db->fs('suchname'));
    }
    return $out;
}

/**
 * Kommt der Anbieter für die NOW-Übermittlung in Frage?
 * true, wenn Stichwort "NOW ja" gesetzt ist (bzw. Filter deaktiviert ist).
 */
function now_anbieter_eligible($anbieterId)
{
    if (!NOW_FILTER_BY_STICHWORT) {
        return true;
    }
    $db = $GLOBALS['now_db'];
    $db->query("SELECT 1 FROM anbieter_stichwort
                 WHERE primary_id=" . intval($anbieterId) . " AND attr_id=" . intval(NOW_STICHWORT_JA) . " LIMIT 1");
    return (bool) $db->next_record();
}

/*------------------------------------------------------------------
 Glossar-/Ratgeber-Seite rendern (Wiki -> HTML), Rückgabe als UTF-8.
 Liefert array('html'=>..., 'version'=>date_modified, 'begriff'=>...) oder null.
------------------------------------------------------------------*/
function now_render_glossar($glossarId)
{
    $db = $GLOBALS['now_db'];
    $db->query("SELECT begriff, erklaerung, wikipedia, date_modified
                  FROM glossar
                 WHERE status=1 AND id=" . intval($glossarId));
    if (!$db->next_record()) {
        return null;
    }

    $begriff    = $db->fcs8('begriff');       // latin1
    $erklaerung = $db->fcs8('erklaerung');    // latin1, alte Wiki-Syntax
    $version    = (string)$db->fs('date_modified');

    $html = '';
    $framework = isset($GLOBALS['now_framework']) ? $GLOBALS['now_framework'] : null;
    if ($framework && $erklaerung !== '') {
        $wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $framework, array('selfGlossarId' => intval($glossarId)));
        $html = $wiki2html->run($framework->encode_windows_chars($erklaerung)); // latin1-HTML
    } else {
        // Fallback ohne Framework: Wiki-Quelle wenigstens lesbar darstellen
        $html = '<pre>' . htmlspecialchars($erklaerung, ENT_QUOTES, 'ISO-8859-1') . '</pre>';
    }

    return array(
        'begriff' => now_to_utf8($begriff),
        'html'    => now_to_utf8($html),   // -> UTF-8 für die Seitenausgabe
        'version' => $version,
    );
}

/*------------------------------------------------------------------
 Einwilligung speichern (revisionssicher) + Live-Flag + Journal.
 $data: array mit utf8-Strings:
   anbieter (int), anbieter_suchname, vorname, nachname, email_bestaetigung,
   aktion ('erteilt'|'widerrufen'),
   text_now_rechte, text_agb, text_datenschutz,
   version_now_rechte, version_agb, version_datenschutz
 Gibt die insert_id der Historien-Tabelle zurück.
------------------------------------------------------------------*/
function now_store_consent($data)
{
    $db = $GLOBALS['now_db'];

    $anbieter = intval($data['anbieter']);
    $aktion   = ($data['aktion'] === 'widerrufen') ? 'widerrufen' : 'erteilt';
    $now      = date('Y-m-d H:i:s');
    // IP-Adresse und User-Agent werden bewusst NICHT gespeichert (Datensparsamkeit).
    // Die Wiedererkennung/Auffälligkeit erfolgt über die Bestätigungs-E-Mail an die
    // hinterlegte Pflege-Adresse des Anbieters.

    // Integritäts-Hash über die bestätigten Texte (UTF-8, vor latin1-Konvertierung)
    $hash = hash('sha256',
        NOW_PAKET_VERSION . "\n" .
        $data['version_now_rechte'] . "\n" . $data['text_now_rechte'] . "\n" .
        $data['version_agb'] . "\n" . $data['text_agb'] . "\n" .
        $data['version_datenschutz'] . "\n" . $data['text_datenschutz']
    );

    // --- 1) Historien-Datensatz (latin1) ------------------------------------
    // Verwendet query()+quote() (mit allen DB_Admin-Versionen kompatibel).
    // Werte sind bereits latin1 (now_to_latin1) bzw. ASCII (now_archive).
    $sql = "INSERT INTO anbieter_now_einwilligung
              (anbieter, anbieter_suchname, datum, aktion, vorname, nachname,
               email_bestaetigung,
               version_paket, version_now_rechte, version_agb, version_datenschutz,
               text_now_rechte, text_agb, text_datenschutz, hash)
            VALUES ("
        . intval($anbieter) . ", "
        . $db->quote(now_to_latin1($data['anbieter_suchname'])) . ", "
        . $db->quote($now) . ", "
        . $db->quote($aktion) . ", "
        . $db->quote(now_to_latin1($data['vorname'])) . ", "
        . $db->quote(now_to_latin1($data['nachname'])) . ", "
        . $db->quote(now_to_latin1($data['email_bestaetigung'])) . ", "
        . $db->quote(NOW_PAKET_VERSION) . ", "
        . $db->quote($data['version_now_rechte']) . ", "
        . $db->quote($data['version_agb']) . ", "
        . $db->quote($data['version_datenschutz']) . ", "
        . $db->quote(now_archive($data['text_now_rechte'])) . ", "
        . $db->quote(now_archive($data['text_agb'])) . ", "
        . $db->quote(now_archive($data['text_datenschutz'])) . ", "
        . $db->quote($hash) . ")";
    $db->query($sql);
    $insertId = $db->insert_id();

    // --- 2) Live-Flag setzen (das liest der mein-NOW-Adapter) ---------------
    // Sicherheitsnetz: Hat die Redaktion den Status fixiert
    // (anbieter.now_zustimmung_fix), bleibt das Live-Flag unveraendert.
    // (Das Formular blockiert fixierte Anbieter bereits vor dem Speichern.)
    $flag = ($aktion === 'widerrufen') ? 0 : 1;
    $db->query("UPDATE anbieter SET now_zustimmung=" . $flag . " WHERE id=" . $anbieter . " AND now_zustimmung_fix=0");

    // Standard-Vergabe: Mit der Anbieter-Einwilligung erhalten alle seine
    // Kurse kurse.now_zustimmung=1 (sofern nicht per kurse.now_zustimmung_fix
    // fixiert). Ein Widerruf wird NICHT auf die Kurse übertragen – die
    // Übermittlung prüft ohnehin immer zusätzlich die Anbieter-Zustimmung.
    if ($flag === 1) {
        now_propagate_kurse_zustimmung($anbieter);
    }

    // --- 3) Journal-Eintrag in anbieter.notizen (latin1, voranstellen) ------
    now_append_journal($anbieter, $data, $aktion, $now);

    return $insertId;
}

/** Stellt eine Zusammenfassung dem Feld anbieter.notizen voran (WISY-Konvention). */
function now_append_journal($anbieterId, $data, $aktion, $now)
{
    $db = $GLOBALS['now_db'];

    $person = trim($data['vorname'] . ' ' . $data['nachname']);
    $datum  = date('d.m.Y H:i', strtotime($now));

    $verb = ($aktion === 'widerrufen')
        ? 'NOW-Übermittlung WIDERRUFEN'
        : 'NOW-Übermittlung EINGEWILLIGT';

    $eintrag =
        "$datum: $verb durch \"$person\" (E-Mail-Bestätigung an: {$data['email_bestaetigung']}). "
        . "Bestätigt: NOW-Übermittlungsbedingungen v" . $data['version_now_rechte']
        . ", WISY-AGB (g" . NOW_GLOSSAR_AGB_ID . ", Stand " . $data['version_agb'] . ")"
        . ", Datenschutz (g" . NOW_GLOSSAR_DATENSCHUTZ_ID . ", Stand " . $data['version_datenschutz'] . ")"
        . ". Paket-Version: " . NOW_PAKET_VERSION . ".\n";

    // bestehende Notizen lesen (latin1) und neuen Eintrag voranstellen
    $db->query("SELECT notizen FROM anbieter WHERE id=" . intval($anbieterId));
    $alt = $db->next_record() ? (string)$db->fs('notizen') : '';

    $neu = now_to_latin1($eintrag) . $alt;
    $db->query("UPDATE anbieter SET notizen=" . $db->quote($neu) . " WHERE id=" . intval($anbieterId));
}

/*------------------------------------------------------------------
 Bestätigungs-E-Mail (UTF-8) an die Anbieter-Pflege-Adresse UND immer
 zusätzlich als Kopie an die Redaktions-/Admin-Adresse (NOW_ADMIN_EMAIL).
 Versand bevorzugt per SMTP/PHPMailer (Portaleinstellungen), sonst mail().
 Gibt true zurück, wenn mindestens die Anbieter-Mail erfolgreich war.
------------------------------------------------------------------*/
function now_send_confirmation($to, $data)
{
    $to = trim($to);

    $person  = trim($data['vorname'] . ' ' . $data['nachname']);
    $datum   = date('d.m.Y H:i');
    $aktion  = ($data['aktion'] === 'widerrufen') ? 'Widerruf' : 'Einwilligung';

    $subjectRaw = "Ihre $aktion zur Übermittlung an mein NOW – " . $data['anbieter_suchname'];

    // Reiner Text der bestätigten Inhalte (HTML grob in Text wandeln)
    $now_txt  = now_html_to_text($data['text_now_rechte']);
    $agb_txt  = now_html_to_text($data['text_agb']);
    $dsg_txt  = now_html_to_text($data['text_datenschutz']);

    $body =
"Sehr geehrte Damen und Herren,

dies ist die automatische Bestätigung Ihrer $aktion zur Übermittlung Ihrer
Anbieter- und Kursdaten aus dem " . NOW_PORTAL_NAME . " an das nationale
Portal \"mein NOW\" (mein-now.de).

Anbieter:        {$data['anbieter_suchname']} (ID {$data['anbieter']})
Erklärt durch:   $person
Zeitpunkt:       $datum
Vorgang:         $aktion
Paket-Version:   " . NOW_PAKET_VERSION . "

WICHTIG: Diese E-Mail geht an die in WISY hinterlegte Pflege-Adresse Ihres
Anbieter-Eintrags (sowie als Kopie an die Redaktion). Falls Sie diesen Vorgang
NICHT selbst veranlasst haben, wenden Sie sich bitte umgehend an die Redaktion
des \"" . NOW_OPERATOR_NAME . "\".

Sie können die Übermittlung an \"mein NOW\" jederzeit und ohne Frist widerrufen.

================================================================
BESTÄTIGTE INHALTE (Kopie, Stand der Bestätigung)
================================================================

--- 1) NOW-Übermittlungsbedingungen (v{$data['version_now_rechte']}) ---

$now_txt

--- 2) WISY-AGB (g" . NOW_GLOSSAR_AGB_ID . ", Stand {$data['version_agb']}) ---

$agb_txt

--- 3) Datenschutzerklärung (g" . NOW_GLOSSAR_DATENSCHUTZ_ID . ", Stand {$data['version_datenschutz']}) ---

$dsg_txt

================================================================

Mit freundlichen Grüßen
" . NOW_MAIL_FROMNAME . "
";

    // Admin-/Redaktions-Kopie als BCC mitschicken, falls Anbieter-Adresse gültig.
    $adminBcc = (defined('NOW_ADMIN_EMAIL') && NOW_ADMIN_EMAIL !== '') ? NOW_ADMIN_EMAIL : '';

    $okAnbieter = false;
    if ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $okAnbieter = now_send_mail($to, $subjectRaw, $body, $adminBcc);
    } elseif ($adminBcc !== '') {
        // Keine (gültige) Anbieter-Adresse -> wenigstens die Redaktion informieren.
        now_send_mail($adminBcc, '[KEINE ANBIETER-ADRESSE] ' . $subjectRaw, $body, '');
    }

    return $okAnbieter;
}

/*------------------------------------------------------------------
 Portaleinstellung lesen – framework-unabhängig, damit der Mailversand
 sowohl im Frontend (Bootstrap setzt $wisyPortalEinstellungen) als auch im
 Redaktions-Plugin (kein Framework) funktioniert. Quelle in dieser Reihenfolge:
 1) $GLOBALS['wisyPortalEinstellungen'] (falls befüllt),
 2) Portal NOW_PORTAL_ID direkt aus der DB (inkl. include-Auflösung).
------------------------------------------------------------------*/
function now_iniread($key, $default = '')
{
    static $settings = null;
    if ($settings === null) {
        $settings = array();
        $GLOBALS['now_settings_source'] = 'keine Quelle';
        if (isset($GLOBALS['wisyPortalEinstellungen']) && is_array($GLOBALS['wisyPortalEinstellungen']) && $GLOBALS['wisyPortalEinstellungen']) {
            $settings = $GLOBALS['wisyPortalEinstellungen'];
            $GLOBALS['now_settings_source'] = 'Bootstrap/Framework (wisyPortalEinstellungen)';
        } elseif (class_exists('DB_Admin')) {
            // Eigene Verbindung: kein laufendes Result-Set einer anderen Abfrage stören.
            $db = new DB_Admin;

            // 1) Fest konfigurierte Portal-ID
            if (defined('NOW_PORTAL_ID') && intval(NOW_PORTAL_ID) > 0) {
                $db->query("SELECT einstellungen FROM portale WHERE id=" . intval(NOW_PORTAL_ID));
                if ($db->next_record()) {
                    now_parse_settings__($db->fs('einstellungen'), $settings, true);
                    $GLOBALS['now_settings_source'] = 'Portal-ID ' . intval(NOW_PORTAL_ID) . ' (NOW_PORTAL_ID)';
                }
            }

            // 2) Fallback: Host-Erkennung – identisch zu bootstrap.inc.php.
            //    Unverzichtbar im Redaktionssystem (module.php/edit_plugin_*, MultiEdit):
            //    Dort läuft KEIN Frontend-Bootstrap, $wisyPortalEinstellungen ist leer.
            //    Ohne diesen Zweig blieben die mail.extern.*-Einstellungen unsichtbar und
            //    der Versand fiele still auf das lokale PHP mail() zurück (SPF-Fehler!).
            if (!$settings) {
                $host = isset($_SERVER['SERVER_NAME']) ? strtolower(str_replace('www.', '', $_SERVER['SERVER_NAME'])) : '';
                if ($host !== '') {
                    $db->query("SELECT id, einstellungen FROM portale WHERE status=1 AND domains LIKE " . $db->quote('%' . $host . '%'));
                    if ($db->next_record()) {
                        $portalId = intval($db->f('id'));
                        now_parse_settings__($db->fs('einstellungen'), $settings, true);
                        $GLOBALS['now_settings_source'] = 'Host "' . $host . '" -> Portal-ID ' . $portalId;
                    } else {
                        $GLOBALS['now_settings_source'] = 'kein Portal gefunden (Host "' . $host . '", NOW_PORTAL_ID='
                            . (defined('NOW_PORTAL_ID') ? intval(NOW_PORTAL_ID) : 0) . ')';
                    }
                }
            }
        }
    }
    return isset($settings[$key]) ? $settings[$key] : $default;
}

/** Woher die Portaleinstellungen stammen – für die Diagnose des Mailversands. */
function now_settings_source()
{
    return isset($GLOBALS['now_settings_source']) ? (string)$GLOBALS['now_settings_source'] : 'unbekannt';
}

/** Einstellungs-Parser inkl. include-Auflösung (eigene, kollisionsfreie Variante). */
function now_parse_settings__($in, &$out, $follow_includes)
{
    $in = strtr((string)$in, "\r\t", "\n ");
    $in = explode("\n", $in);
    foreach ($in as $line) {
        $eq = strpos($line, '=');
        if ($eq) {
            $key = trim(substr($line, 0, $eq));
            if ($key != '') {
                $val = trim(substr($line, $eq + 1));
                if ($key == 'include') {
                    if ($follow_includes && @file_exists($val)) {
                        now_parse_settings__(file_get_contents($val), $out, false);
                    }
                } else {
                    $out[$key] = $val;
                }
            }
        }
    }
}

/*------------------------------------------------------------------
 "Antwort an" (Reply-To) für alle Mails des Moduls.
 Der Absender ist das SMTP-Postfach der Portaldomain (meist no-reply@...) und
 damit für Rückfragen unbrauchbar. Antworten sollen die Redaktion erreichen.
 Reihenfolge: NOW_MAIL_REPLYTO, NOW_CONTACT_EMAIL, NOW_ADMIN_EMAIL - die erste
 Adresse, die syntaktisch gültig ist (ein Tippfehler in einer Konstanten kostet
 so nicht die gesamte Antwortmöglichkeit).
 @return array(adresse, anzeigename) - Adresse leer = kein Reply-To setzen.
------------------------------------------------------------------*/
function now_mail_replyto()
{
    $addr = '';
    foreach (array('NOW_MAIL_REPLYTO', 'NOW_CONTACT_EMAIL', 'NOW_ADMIN_EMAIL') as $konstante) {
        if (!defined($konstante)) {
            continue;
        }
        $kandidat = trim((string)constant($konstante));
        if ($kandidat !== '' && filter_var($kandidat, FILTER_VALIDATE_EMAIL)) {
            $addr = $kandidat;
            break;
        }
    }
    if ($addr === '') {
        return array('', '');
    }
    $name = (defined('NOW_MAIL_REPLYTONAME') && trim((string)NOW_MAIL_REPLYTONAME) !== '')
        ? trim((string)NOW_MAIL_REPLYTONAME)
        : (string)NOW_MAIL_FROMNAME;

    return array($addr, $name);
}

/**
 * Anzeigename für einen Mail-Header aufbereiten (nur für den mail()-Fallback,
 * PHPMailer erledigt das selbst): nicht-ASCII wird nach RFC 2047 kodiert, ASCII
 * in Anführungszeichen gesetzt. Letzteres ist nötig, weil Klammern und Kommas
 * im Anzeigenamen (z. B. "Kursportal (WISY)") sonst als Kommentar bzw. als
 * Adresstrenner gelesen werden.
 */
function now_mail_header_name__($name)
{
    $name = trim((string)$name);
    if ($name === '') {
        return '';
    }
    if (preg_match('/[^\x20-\x7E]/', $name)) {
        return '=?UTF-8?B?' . base64_encode($name) . '?=';
    }
    return '"' . str_replace(array('\\', '"'), array('\\\\', '\\"'), $name) . '"';
}

/*------------------------------------------------------------------
 Zentraler Mailversand (UTF-8). Bevorzugt SMTP/PHPMailer anhand der
 Portaleinstellungen (mail.extern.*). Fällt auf die PHP-mail()-Funktion
 zurück, wenn PHPMailer nicht konfiguriert ist.
 $bodyHtml != null  -> HTML-Mail (multipart: HTML + Text-Alternative).
 Im lokalen Test (.local / CLI) wird nichts versendet (nur Logeintrag).
------------------------------------------------------------------*/
function now_send_mail($to, $subjectRaw, $body, $bcc = '', $bodyHtml = null)
{
    $GLOBALS['now_mail_last_error']     = '';
    $GLOBALS['now_mail_last_warning']   = '';
    $GLOBALS['now_mail_last_transport'] = '';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

    // DEV: nicht wirklich senden
    if (substr($host, -6) === '.local' || PHP_SAPI === 'cli') {
        error_log("[NOW-Einwilligung] (DEV) Mail an $to" . ($bcc ? " (BCC $bcc)" : '') . " – Betreff: $subjectRaw" . ($bodyHtml !== null ? ' [HTML]' : ''));
        $GLOBALS['now_mail_last_transport'] = 'dev';
        return true;
    }

    if (trim((string)$to) === '' || !filter_var(trim((string)$to), FILTER_VALIDATE_EMAIL)) {
        $GLOBALS['now_mail_last_error'] = 'Keine gültige Empfänger-Adresse: "' . $to . '"';
        error_log('[NOW-Einwilligung] ' . $GLOBALS['now_mail_last_error']);
        return false;
    }

    $usePHPMailer = now_iniread('mail.extern', '');

    if ($usePHPMailer && defined('PHPMAILER_PATH') && file_exists(PHPMAILER_PATH)) {
        require_once(PHPMAILER_PATH);
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->CharSet  = 'UTF-8';                                  // wichtig: UTF-8, kein utf8_decode
            $mail->Host     = now_iniread('mail.extern.host', '');
            $mail->Port     = now_iniread('mail.extern.port', '');

            $smtpSecure = now_iniread('mail.extern.smtpSecure', '');
            if ($smtpSecure) { $mail->SMTPSecure = $smtpSecure; }

            $smtpAuth = now_iniread('mail.extern.smtpAuth', '');
            $mail->SMTPAuth = (bool) $smtpAuth;
            if ($smtpAuth == 'true' || $smtpAuth === true || intval($smtpAuth) === 1) {
                $mail->Username = now_iniread('mail.extern.username', '');
                $mail->Password = now_iniread('mail.extern.password', '');
            }

            $from     = now_iniread('mail.extern.from', '');
            $fromName = now_iniread('mail.extern.fromName', '');
            $mail->setFrom($from ?: NOW_MAIL_FROM, $fromName ?: NOW_MAIL_FROMNAME);

            // Antworten gehen an die Redaktion, nicht an das no-reply-Postfach.
            list($replyAddr, $replyName) = now_mail_replyto();
            if ($replyAddr !== '') { $mail->addReplyTo($replyAddr, $replyName); }

            $mail->addAddress($to);
            if ($bcc !== '') { $mail->addBCC($bcc); }

            $mail->Subject = $subjectRaw;
            if ($bodyHtml !== null) {
                $mail->isHTML(true);
                $mail->Body    = $bodyHtml;
                $mail->AltBody = $body;
            } else {
                $mail->Body    = $body;
            }
            if ($mail->send()) {
                $GLOBALS['now_mail_last_transport'] = 'smtp';
                return true;
            }
            $GLOBALS['now_mail_last_warning'] = 'SMTP-Versand abgelehnt: ' . $mail->ErrorInfo;
        } catch (Throwable $e) {
            $GLOBALS['now_mail_last_warning'] = 'PHPMailer-Fehler: ' . $e->getMessage();
            // Fallback unten
        }
        error_log('[NOW-Einwilligung] ' . $GLOBALS['now_mail_last_warning']);
    } elseif (!$usePHPMailer) {
        $GLOBALS['now_mail_last_warning'] = 'Portaleinstellung "mail.extern" ist leer/nicht gesetzt (Quelle der Einstellungen: '
            . now_settings_source() . ')';
    } else {
        $GLOBALS['now_mail_last_warning'] = 'PHPMAILER_PATH ' . (defined('PHPMAILER_PATH') ? 'zeigt auf eine nicht vorhandene Datei' : 'ist nicht definiert');
    }

    /*------------------------------------------------------------------
     Fallback: PHP mail() – lokales Sendmail OHNE Authentifizierung.
     ACHTUNG: Dieser Weg ist nur ein Notnagel. Die Mail verlässt den
     Webserver mit dessen Standard-Absender; SPF/DKIM/DMARC der
     Portal-Domain schlagen dadurch fehl, strenge Empfänger (z. B. Gmail)
     weisen sie hart zurück. mail() meldet trotzdem Erfolg, weil es die
     Nachricht nur an das lokale Mailsystem übergibt – deshalb bleibt die
     Warnung oben erhalten und wird an den Aufrufer durchgereicht.
    ------------------------------------------------------------------*/
    $GLOBALS['now_mail_last_transport'] = 'mail';
    $subject  = '=?UTF-8?B?' . base64_encode($subjectRaw) . '?=';
    $fromHeaderName = now_mail_header_name__(NOW_MAIL_FROMNAME);
    $headers  = 'From: ' . ($fromHeaderName !== '' ? $fromHeaderName . ' ' : '') . '<' . NOW_MAIL_FROM . ">\r\n";

    list($replyAddr, $replyName) = now_mail_replyto();
    if ($replyAddr !== '') {
        $replyHeaderName = now_mail_header_name__($replyName);
        $headers .= 'Reply-To: ' . ($replyHeaderName !== '' ? $replyHeaderName . ' ' : '') . '<' . $replyAddr . ">\r\n";
    }
    if ($bcc !== '') { $headers .= 'Bcc: ' . $bcc . "\r\n"; }
    $headers .= "MIME-Version: 1.0\r\n";

    if ($bodyHtml !== null) {
        $boundary = 'now_' . md5($to . '|' . $subjectRaw);
        $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
        $msg  = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . $body . "\r\n";
        $msg .= "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . $bodyHtml . "\r\n";
        $msg .= "--$boundary--\r\n";
    } else {
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";
        $msg = $body;
    }

    // Envelope-Absender (-f) auf die eigene Adresse setzen: sonst nimmt der
    // Server seine Vertrags-/Standardadresse, wodurch Unzustellbarkeits-
    // meldungen dorthin laufen und SPF gegen eine fremde Domain geprüft wird.
    $envelope = '-f' . NOW_MAIL_FROM;
    $sent = @mail($to, $subject, $msg, $headers, $envelope);
    if (!$sent) {
        $sent = @mail($to, $subject, $msg, $headers); // Host erlaubt kein -f
    }

    if ($sent) {
        error_log('[NOW-Einwilligung] Versand an ' . $to . ' NUR ueber lokales mail(): ' . $GLOBALS['now_mail_last_warning']);
        return true;
    }

    $GLOBALS['now_mail_last_error'] = trim(($GLOBALS['now_mail_last_warning'] !== '' ? $GLOBALS['now_mail_last_warning'] . ' | ' : '')
        . 'PHP mail() konnte die Nachricht nicht übergeben.');
    error_log('[NOW-Einwilligung] Versand an ' . $to . ' fehlgeschlagen: ' . $GLOBALS['now_mail_last_error']);
    return false;
}

/**
 * Grund des letzten FEHLGESCHLAGENEN Mailversands (leer = kein Fehler bzw.
 * erfolgreich versendet). Für die Anzeige im Redaktionssystem/Journal, damit
 * Versandprobleme nicht nur im Server-Log stehen.
 */
function now_last_mail_error()
{
    return isset($GLOBALS['now_mail_last_error']) ? (string)$GLOBALS['now_mail_last_error'] : '';
}

/**
 * Versandweg der letzten Mail: 'smtp' (authentifiziert über die
 * mail.extern.*-Einstellungen), 'mail' (lokales Sendmail – SPF/DMARC schlagen
 * bei externen Empfängern fehl!) oder 'dev' (Testumgebung, nicht versendet).
 */
function now_last_mail_transport()
{
    return isset($GLOBALS['now_mail_last_transport']) ? (string)$GLOBALS['now_mail_last_transport'] : '';
}

/**
 * Warnung zum letzten Versand – gesetzt, wenn NICHT über SMTP versendet wurde
 * (nennt den Grund). Bleibt auch dann erhalten, wenn mail() „Erfolg" meldet:
 * mail() übergibt die Nachricht nur lokal, die Zustellung kann später scheitern.
 */
function now_last_mail_warning()
{
    return isset($GLOBALS['now_mail_last_warning']) ? (string)$GLOBALS['now_mail_last_warning'] : '';
}

/** Versandweg als Klartext für Anzeige, Journal und Nachweis-Kopie. */
function now_transport_text($transport, $warning = '')
{
    switch ($transport) {
        case 'smtp':
            return 'SMTP (authentifiziert über ' . now_iniread('mail.extern.host', 'externes Postfach') . ')';
        case 'dev':
            return 'Testumgebung – nicht wirklich versendet';
        case 'mail':
            return 'ACHTUNG: lokales Sendmail OHNE Authentifizierung – SPF/DMARC schlagen bei externen '
                 . 'Empfängern fehl, Gmail & Co. weisen die Mail zurück'
                 . ($warning !== '' ? ' (Grund: ' . $warning . ')' : '');
    }
    return 'unbekannt';
}

/*------------------------------------------------------------------
 Anschreiben (Einladung zur Einwilligung) an einen Anbieter senden.
 Sendet die HTML-Mail aus inc/now-anschreiben.inc.php (mit personalisierten
 Links). $anbieter: array mit id, pflege_email, anspr_email.
------------------------------------------------------------------*/
function now_send_anschreiben($anbieter, $bcc = '')
{
    if (!function_exists('now_anschreiben')) {
        require_once(__DIR__ . '/now-anschreiben.inc.php');
    }
    $to = (isset($anbieter['pflege_email']) && $anbieter['pflege_email'] !== '')
        ? $anbieter['pflege_email']
        : (isset($anbieter['anspr_email']) ? $anbieter['anspr_email'] : '');
    $to = trim((string)$to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $a = now_anschreiben($anbieter);
    $ok = now_send_mail($to, $a['subject'], $a['text'], $bcc, $a['html']);

    // Bei Erfolg: Journaleintrag + Verwaltungsstichwort setzen (Übersicht,
    // wer bereits angeschrieben wurde).
    if ($ok) {
        now_mark_anschreiben_sent(intval($anbieter['id']));
    }
    return $ok;
}

/**
 * Vermerkt das versendete Anschreiben am Anbieter:
 *  - Journaleintrag in anbieter.notizen (Datum ohne Uhrzeit),
 *  - Verwaltungsstichwort "NOW Anschreiben initial" (NOW_STICHWORT_ANSCHREIBEN),
 *    sofern noch nicht gesetzt.
 * Nutzt $GLOBALS['now_db'] (Frontend) bzw. eine eigene DB_Admin-Instanz (Admin).
 */
function now_mark_anschreiben_sent($anbieterId)
{
    $anbieterId = intval($anbieterId);
    if ($anbieterId <= 0) {
        return;
    }
    $db = isset($GLOBALS['now_db']) ? $GLOBALS['now_db'] : new DB_Admin;

    // 1) Journaleintrag voranstellen (latin1, ohne Uhrzeit)
    $eintrag = date('d.m.y') . ": NOW-Anbieteranschreiben gesendet (WISY)\n";
    $db->query("SELECT notizen FROM anbieter WHERE id=" . $anbieterId);
    $alt = $db->next_record() ? (string)$db->fs('notizen') : '';
    $db->query("UPDATE anbieter SET notizen=" . $db->quote(now_to_latin1($eintrag) . $alt) . " WHERE id=" . $anbieterId);

    // 2) Stichwort setzen, falls noch nicht vorhanden
    if (defined('NOW_STICHWORT_ANSCHREIBEN') && intval(NOW_STICHWORT_ANSCHREIBEN) > 0) {
        $sw = intval(NOW_STICHWORT_ANSCHREIBEN);
        $db->query("SELECT 1 FROM anbieter_stichwort WHERE primary_id=" . $anbieterId . " AND attr_id=" . $sw . " LIMIT 1");
        if (!$db->next_record()) {
            $db->query("INSERT INTO anbieter_stichwort (primary_id, attr_id, structure_pos) VALUES (" . $anbieterId . ", " . $sw . ", 0)");
        }
    }
}

/*------------------------------------------------------------------
 Nachweis-Kopie der Informations-Mail an die Redaktion
------------------------------------------------------------------*/
/**
 * Sendet eine als solche gekennzeichnete Kopie der Informations-Mail an die
 * Redaktions-Adresse (NOW_ADMIN_EMAIL) – dieselbe Adresse, die auch die Kopien
 * der Einwilligungs- und Widerrufsbestätigungen erhält.
 *
 * Hintergrund: Anders als bei Einwilligung/Widerruf löst die Informations-Mail
 * keine Rückmeldung des Anbieters aus. Die Kopie ist damit der unmittelbar
 * vorzeigbare Beleg dafür, DASS und MIT WELCHEM WORTLAUT informiert wurde.
 * Sie wird deshalb als eigene Nachricht (nicht als BCC) versendet, trägt einen
 * Kopf mit Anbieter, Empfänger, Zeitpunkt, auslösender Person und
 * Zustellergebnis und darunter den vollständigen Original-Wortlaut.
 * Sie geht auch dann raus, wenn der Versand an den Anbieter fehlgeschlagen ist
 * – dann als Beleg des Versuchs inkl. Fehlergrund.
 *
 * @param array $mail  Rückgabe von now_infomail() (subject/html/text)
 * @param array $meta  anbieter, suchname, empfaenger, zeitpunkt, bearbeiter,
 *                     quelle, zustellung_ok, zustellung_fehler
 * @return bool|null   true/false = versendet/fehlgeschlagen,
 *                     null = keine Redaktions-Adresse konfiguriert
 */
function now_send_infomail_kopie($mail, $meta)
{
    $to = (defined('NOW_ADMIN_EMAIL') && NOW_ADMIN_EMAIL !== '') ? NOW_ADMIN_EMAIL : '';
    if ($to === '') {
        return null; // ohne NOW_ADMIN_EMAIL keine Kopie möglich
    }

    $zustellung = $meta['zustellung_ok']
        ? 'erfolgreich versendet an ' . $meta['empfaenger']
        : 'FEHLGESCHLAGEN – der Anbieter wurde NICHT erreicht'
          . ($meta['zustellung_fehler'] !== '' ? ' (' . $meta['zustellung_fehler'] . ')' : '');

    $frist = defined('NOW_INFOMAIL_FRIST') ? trim((string)NOW_INFOMAIL_FRIST) : '';

    // Adresse, an die eine Antwort des Anbieters (z. B. ein Widerspruch) läuft.
    $replyTo = now_mail_replyto();

    $felder = array(
        'Anbieter'          => $meta['suchname'] . ' (ID ' . intval($meta['anbieter']) . ')',
        'Empf&auml;nger'    => $meta['empfaenger'],
        'Zeitpunkt'         => $meta['zeitpunkt'],
        'Ausgel&ouml;st durch' => $meta['bearbeiter'] . ($meta['quelle'] !== '' ? ' (' . $meta['quelle'] . ')' : ''),
        'Zustellung'        => $zustellung,
        // Versandweg gehört in den Nachweis: über das lokale Sendmail versandte
        // Mails gelten bei strengen Empfängern als nicht zugestellt.
        'Versandweg'        => isset($meta['versandweg']) ? $meta['versandweg'] : 'unbekannt',
        // Antworten des Anbieters gehen NICHT an das Absender-Postfach:
        'Antwort an'        => $replyTo[0] !== ''
                                 ? $replyTo[0]
                                 : '(kein Reply-To gesetzt - Antworten gehen an ' . NOW_MAIL_FROM . ')',
        'Widerspruchsfrist' => $frist !== '' ? $frist : '(nicht gesetzt)',
        'Textversion'       => 'Info-Mail ' . NOW_INFOMAIL_VERSION . ', Paket ' . NOW_PAKET_VERSION,
    );

    $subject = '[Nachweis] mein NOW - Informations-Mail an ' . $meta['suchname']
             . ' (ID ' . intval($meta['anbieter']) . ')';

    $h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

    // -------- Text-Fassung --------
    $kopfText = '';
    foreach ($felder as $k => $v) {
        $label = html_entity_decode($k, ENT_QUOTES, 'UTF-8') . ':';
        // str_pad() zählt Bytes – bei Umlauten um die Differenz zur Zeichenlänge korrigieren
        $kopfText .= str_pad($label, 20 + (strlen($label) - mb_strlen($label, 'UTF-8'))) . $v . "\n";
    }
    $trenner = str_repeat('=', 70);
    $text =
"NACHWEIS-KOPIE fuer die Redaktion
Diese Nachricht dokumentiert den Versand der Informations-Mail zur
Uebermittlung an \"mein NOW\". Diese Meta-Infos wurden NICHT an den Anbieter gesendet (nur der Text ganz unten).

$kopfText
Der Vorgang ist zusaetzlich revisionssicher protokolliert (Tabelle
anbieter_now_einwilligung) und im Journal des Anbieters vermerkt.

$trenner
WORTLAUT DER VERSENDETEN INFORMATIONS-MAIL
Betreff: {$mail['subject']}
$trenner

" . $mail['text'] . "\n";

    // -------- HTML-Fassung --------
    $kopfHtml = '';
    foreach ($felder as $k => $v) {
        $kopfHtml .= '<tr><td style="padding:2px 10px 2px 0;vertical-align:top;color:#555;white-space:nowrap;">'
                   . $k . ':</td><td style="padding:2px 0;vertical-align:top;"><b>' . $h($v) . '</b></td></tr>';
    }
    $html =
'<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#222;line-height:1.5;">'
. '<p style="background:#fff3e0;border:1px solid #e65100;color:#e65100;padding:8px 10px;margin:0 0 12px;">'
. '<b>Nachweis-Kopie f&uuml;r die Redaktion.</b><br>Diese Nachricht dokumentiert den Versand der '
. 'Informations-Mail zur &Uuml;bermittlung an &bdquo;mein&nbsp;NOW&ldquo;. Diese Meta-Infos wurden <b>nicht</b> an den Anbieter gesendet (nur der Text ganz unten).</p>'
. '<table style="font-size:13px;border-collapse:collapse;margin-bottom:12px;">' . $kopfHtml . '</table>'
. '<p style="font-size:12px;color:#666;">Der Vorgang ist zus&auml;tzlich revisionssicher protokolliert '
. '(Tabelle <tt>anbieter_now_einwilligung</tt>) und im Journal des Anbieters vermerkt.</p>'
. '<hr style="border:none;border-top:2px solid #004d71;margin:16px 0 8px;">'
. '<p style="font-size:12px;color:#666;margin:0 0 12px;">Wortlaut der versendeten Informations-Mail &ndash; '
. 'Betreff: <b>' . $h($mail['subject']) . '</b></p>'
. $mail['html']
. '</div>';

    return now_send_mail($to, $subject, $text, '', $html);
}

/*------------------------------------------------------------------
 Redaktionelle Vergabe / Entzug der NOW-Zustimmung
 (Anbieter-Maske "NOW-Einwilligung" und MultiEdit im Redaktionssystem)
------------------------------------------------------------------*/

/** DB-Handle: Frontend-Bootstrap ($GLOBALS['now_db']) oder Admin (DB_Admin). */
function now_db_handle()
{
    static $own = null;
    if (isset($GLOBALS['now_db']) && $GLOBALS['now_db']) {
        return $GLOBALS['now_db'];
    }
    if ($own === null) {
        $own = new DB_Admin;
    }
    return $own;
}

/** true, wenn die Redaktion den NOW-Status dieses Anbieters fixiert hat. */
function now_zustimmung_fix_gesetzt($anbieterId)
{
    $db = now_db_handle();
    $db->query("SELECT now_zustimmung_fix FROM anbieter WHERE id=" . intval($anbieterId));
    return $db->next_record() && intval($db->f('now_zustimmung_fix')) > 0;
}

/**
 * Standard-Vergabe an die Kurse: setzt kurse.now_zustimmung=1 für alle Kurse
 * des Anbieters, die noch auf 0 stehen und nicht per kurse.now_zustimmung_fix
 * fixiert sind. date_modified wird gesetzt, damit die Änderung für
 * Delta-Abgleiche (Sync/Adapter) sichtbar ist.
 * Gibt die Anzahl der geänderten Kurse zurück.
 */
function now_propagate_kurse_zustimmung($anbieterId)
{
    $db = now_db_handle();
    $db->query("UPDATE kurse SET now_zustimmung=1, date_modified='" . date('Y-m-d H:i:s') . "'
                 WHERE anbieter=" . intval($anbieterId) . "
                   AND now_zustimmung=0 AND now_zustimmung_fix=0");
    return intval($db->affected_rows());
}

/**
 * Redaktionelle Vergabe ($erteilen=true) bzw. Entzug ($erteilen=false) der
 * NOW-Zustimmung für einen Anbieter – der Weg NEBEN dem Einwilligungsformular
 * (z. B. wenn die Einwilligung schriftlich/auf anderem Wege erteilt wurde).
 *
 *  - respektiert anbieter.now_zustimmung_fix (keine Änderung, wenn fixiert)
 *  - protokolliert revisionssicher in anbieter_now_einwilligung
 *    (aktion 'erteilt_redaktion' / 'entzogen_redaktion') und im Journal
 *  - Vergabe: Standard-Vergabe an alle Kurse (now_propagate_kurse_zustimmung)
 *  - Vergabe mit $mitInfomail: Informations-Mail (inc/now-infomail.inc.php)
 *    an die Pflege-Adresse, Kopie (BCC) an die Redaktion (NOW_ADMIN_EMAIL).
 *    Die Vergabe unterbleibt KOMPLETT, wenn kein Info-Mail-Text hinterlegt
 *    oder keine gültige Empfänger-Adresse vorhanden ist (Mail ist Pflicht).
 *
 * @param int    $anbieterId
 * @param bool   $erteilen     true = Zustimmung erteilen, false = entziehen
 * @param string $bearbeiter   Name der Redakteurin/des Redakteurs (UTF-8)
 * @param bool   $mitInfomail  Info-Mail versenden (nur bei Vergabe relevant)
 * @param string $quelle       fürs Journal, z. B. 'Anbieter-Maske', 'MultiEdit'
 * @return array ('status' => 'ok'|'nicht_gefunden'|'fix'|'schon'|'kein_text'|'keine_email',
 *                'mail_ok' => bool|null, 'mail_error' => string,
 *                'mail_transport' => string, 'mail_warning' => string,
 *                'kopie_ok' => bool|null, 'kopie_error' => string,
 *                'kurse' => int, 'email' => string)
 */
function now_set_zustimmung_redaktionell($anbieterId, $erteilen, $bearbeiter, $mitInfomail = true, $quelle = '')
{
    $anbieterId = intval($anbieterId);
    $erteilen   = (bool)$erteilen;
    $ret        = array('status' => 'ok', 'mail_ok' => null, 'mail_error' => '',
                        'mail_transport' => '', 'mail_warning' => '',
                        'kopie_ok' => null, 'kopie_error' => '', 'kurse' => 0, 'email' => '');

    // --- Anbieter laden (bewusst OHNE freigeschaltet-Filter: die Redaktion
    // --- kann den Status auch für nicht freigeschaltete Anbieter pflegen) ---
    $db = now_db_handle();
    $db->query("SELECT id, suchname, pflege_email, anspr_email, now_zustimmung, now_zustimmung_fix
                  FROM anbieter WHERE id=" . $anbieterId);
    if (!$db->next_record()) {
        $ret['status'] = 'nicht_gefunden';
        return $ret;
    }
    $suchname     = now_to_utf8($db->fs('suchname'));
    $pflegeEmail  = trim((string)$db->fs('pflege_email'));
    $ansprEmail   = trim((string)$db->fs('anspr_email'));
    $kontaktEmail = $pflegeEmail !== '' ? $pflegeEmail : $ansprEmail;
    $flagAlt      = intval($db->f('now_zustimmung'));
    $fix          = intval($db->f('now_zustimmung_fix'));
    $flagNeu      = $erteilen ? 1 : 0;
    $ret['email'] = $kontaktEmail;

    if ($fix > 0) {
        $ret['status'] = 'fix';
        return $ret;
    }
    if ($flagAlt === $flagNeu) {
        $ret['status'] = 'schon';
        return $ret;
    }

    // --- Vorbedingungen Mailversand (nur bei Vergabe) ------------------------
    $mail = null;
    if ($erteilen && $mitInfomail) {
        if (!function_exists('now_infomail')) {
            require_once(__DIR__ . '/now-infomail.inc.php');
        }
        if (!now_infomail_hinterlegt()) {
            $ret['status'] = 'kein_text';
            return $ret;
        }
        if ($kontaktEmail === '' || !filter_var($kontaktEmail, FILTER_VALIDATE_EMAIL)) {
            $ret['status'] = 'keine_email';
            return $ret;
        }
        $mail = now_infomail(array('id' => $anbieterId, 'suchname' => $suchname));
    }

    // --- 1) Live-Flag setzen (Guard nochmals in der WHERE-Klausel);
    // ---    date_modified mitsetzen, damit die Änderung für Delta-Abgleiche
    // ---    (Sync/Adapter) sichtbar ist --------------------------------------
    $userModified = (isset($_SESSION['g_session_userid']) && intval($_SESSION['g_session_userid']) > 0)
        ? ", user_modified=" . intval($_SESSION['g_session_userid']) : '';
    $db->query("UPDATE anbieter SET now_zustimmung=" . $flagNeu
        . ", date_modified='" . date('Y-m-d H:i:s') . "'" . $userModified
        . " WHERE id=" . $anbieterId . " AND now_zustimmung_fix=0");

    // --- 2) Standard-Vergabe an die Kurse (nur bei Vergabe) ------------------
    if ($erteilen) {
        $ret['kurse'] = now_propagate_kurse_zustimmung($anbieterId);
    }

    // --- 3) Revisionssicherer Protokoll-Eintrag ------------------------------
    $aktion = $erteilen ? 'erteilt_redaktion' : 'entzogen_redaktion';
    $now    = date('Y-m-d H:i:s');
    $mailArchiv  = ($mail !== null) ? now_archive($mail['subject'] . "\n\n" . $mail['text']) : '';
    $mailVersion = ($mail !== null) ? ('Info-Mail ' . NOW_INFOMAIL_VERSION) : '';
    $hash = hash('sha256',
        NOW_PAKET_VERSION . "\n" . $aktion . "\n" . $anbieterId . "\n" . $now . "\n"
        . $bearbeiter . "\n" . $mailVersion . "\n" . $mailArchiv
    );
    $db->query("INSERT INTO anbieter_now_einwilligung
                  (anbieter, anbieter_suchname, datum, aktion, vorname, nachname,
                   email_bestaetigung,
                   version_paket, version_now_rechte, version_agb, version_datenschutz,
                   text_now_rechte, text_agb, text_datenschutz, hash)
                VALUES ("
        . $anbieterId . ", "
        . $db->quote(now_to_latin1($suchname)) . ", "
        . $db->quote($now) . ", "
        . $db->quote($aktion) . ", "
        . $db->quote('') . ", "
        . $db->quote(now_to_latin1($bearbeiter)) . ", "
        . $db->quote($erteilen && $mitInfomail ? $kontaktEmail : '') . ", "
        . $db->quote(NOW_PAKET_VERSION) . ", "
        . $db->quote($mailVersion) . ", "
        . $db->quote('') . ", "
        . $db->quote('') . ", "
        . $db->quote($mailArchiv) . ", "
        . $db->quote('') . ", "
        . $db->quote('') . ", "
        . $db->quote($hash) . ")");

    // --- 4) Informations-Mail senden (nach dem Schreiben, Ergebnis kommt ins
    // ---    Journal; BCC immer an die Redaktion) -----------------------------
    if ($mail !== null) {
        // 4a) Die eigentliche Informations-Mail an den Anbieter (ohne BCC –
        //     die Redaktion erhält stattdessen die gekennzeichnete Nachweis-Kopie)
        $ret['mail_ok'] = now_send_mail(
            $kontaktEmail,
            $mail['subject'],
            $mail['text'],
            '',
            (trim((string)$mail['html']) !== '') ? $mail['html'] : null
        );
        // Diagnose sichern, BEVOR der nächste Versand die Globals überschreibt
        $ret['mail_transport'] = now_last_mail_transport();
        $ret['mail_warning']   = now_last_mail_warning();
        if (!$ret['mail_ok']) {
            $ret['mail_error'] = now_last_mail_error();
        }

        // 4b) Nachweis-Kopie an die Redaktion – auch bei fehlgeschlagenem
        //     Anbieter-Versand (dann als Beleg des Versuchs inkl. Grund)
        $ret['kopie_ok'] = now_send_infomail_kopie($mail, array(
            'anbieter'          => $anbieterId,
            'suchname'          => $suchname,
            'empfaenger'        => $kontaktEmail,
            'zeitpunkt'         => date('d.m.Y H:i'),
            'bearbeiter'        => $bearbeiter,
            'quelle'            => $quelle,
            'zustellung_ok'     => (bool)$ret['mail_ok'],
            'zustellung_fehler' => $ret['mail_error'],
            'versandweg'        => now_transport_text($ret['mail_transport'], $ret['mail_warning']),
        ));
        if ($ret['kopie_ok'] === false) {
            $ret['kopie_error'] = now_last_mail_error();
        }
    }

    // --- 5) Journal-Eintrag in anbieter.notizen (latin1, voranstellen) -------
    $datum = date('d.m.Y H:i', strtotime($now));
    if ($erteilen) {
        $eintrag = "$datum: NOW-Übermittlung redaktionell ERTEILT durch \"$bearbeiter\""
            . ($quelle !== '' ? " ($quelle)" : '') . ". ";
        if ($mail !== null) {
            $eintrag .= $ret['mail_ok']
                ? "Info-Mail an: $kontaktEmail. "
                : "ACHTUNG: Info-Mail an $kontaktEmail FEHLGESCHLAGEN"
                  . ($ret['mail_error'] !== '' ? " [{$ret['mail_error']}]" : '') . ". ";
            if ($ret['mail_transport'] === 'mail') {
                $eintrag .= "ACHTUNG: Versand nur ueber lokales Sendmail ohne Authentifizierung"
                          . ($ret['mail_warning'] !== '' ? " [{$ret['mail_warning']}]" : '')
                          . " - Zustellung bei strengen Empfaengern unwahrscheinlich. ";
            }
            if ($ret['kopie_ok'] === true) {
                $eintrag .= "Nachweis-Kopie an " . NOW_ADMIN_EMAIL . ". ";
            } elseif ($ret['kopie_ok'] === false) {
                $eintrag .= "ACHTUNG: Nachweis-Kopie an " . NOW_ADMIN_EMAIL . " FEHLGESCHLAGEN"
                          . ($ret['kopie_error'] !== '' ? " [{$ret['kopie_error']}]" : '') . ". ";
            } else {
                $eintrag .= "Keine Nachweis-Kopie (NOW_ADMIN_EMAIL nicht gesetzt). ";
            }
        }
        $eintrag .= $ret['kurse'] . " Kurs(e) auf Übermittlung gesetzt.\n";
    } else {
        $eintrag = "$datum: NOW-Übermittlung redaktionell ENTZOGEN durch \"$bearbeiter\""
            . ($quelle !== '' ? " ($quelle)" : '') . ".\n";
    }
    $db->query("SELECT notizen FROM anbieter WHERE id=" . $anbieterId);
    $alt = $db->next_record() ? (string)$db->fs('notizen') : '';
    $db->query("UPDATE anbieter SET notizen=" . $db->quote(now_to_latin1($eintrag) . $alt)
        . " WHERE id=" . $anbieterId);

    return $ret;
}

/** Grobe HTML->Text-Konvertierung für die E-Mail. */
function now_html_to_text($html)
{
    $t = $html;
    $t = preg_replace('#<li[^>]*>#i', "\n  - ", $t);
    $t = preg_replace('#</(p|div|h\d|tr|ol|ul)>#i', "\n\n", $t);
    $t = preg_replace('#<br\s*/?>#i', "\n", $t);
    $t = strip_tags($t);
    $t = html_entity_decode($t, ENT_QUOTES, 'UTF-8');
    $t = preg_replace("/[ \t]+\n/", "\n", $t);
    $t = preg_replace("/\n{3,}/", "\n\n", $t);
    return trim($t);
}
