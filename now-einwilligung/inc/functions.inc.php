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
    $db->query("SELECT id, suchname, pflege_email, anspr_email, now_zustimmung
                  FROM anbieter
                 WHERE freigeschaltet=1 AND id=" . intval($anbieterId));
    if (!$db->next_record()) {
        return null;
    }
    return array(
        'id'             => intval($db->f('id')),
        'suchname'       => now_to_utf8($db->fs('suchname')),
        'pflege_email'   => trim((string)$db->fs('pflege_email')),
        'anspr_email'    => trim((string)$db->fs('anspr_email')),
        'now_zustimmung' => intval($db->f('now_zustimmung')),
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
    $flag = ($aktion === 'widerrufen') ? 0 : 1;
    $db->query("UPDATE anbieter SET now_zustimmung=" . $flag . " WHERE id=" . $anbieter);

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
        if (isset($GLOBALS['wisyPortalEinstellungen']) && is_array($GLOBALS['wisyPortalEinstellungen']) && $GLOBALS['wisyPortalEinstellungen']) {
            $settings = $GLOBALS['wisyPortalEinstellungen'];
        } else {
            $settings = array();
            if (defined('NOW_PORTAL_ID') && intval(NOW_PORTAL_ID) > 0 && class_exists('DB_Admin')) {
                $db = isset($GLOBALS['now_db']) ? $GLOBALS['now_db'] : new DB_Admin;
                $db->query("SELECT einstellungen FROM portale WHERE id=" . intval(NOW_PORTAL_ID));
                if ($db->next_record()) {
                    now_parse_settings__($db->fs('einstellungen'), $settings, true);
                }
            }
        }
    }
    return isset($settings[$key]) ? $settings[$key] : $default;
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
 Zentraler Mailversand (UTF-8). Bevorzugt SMTP/PHPMailer anhand der
 Portaleinstellungen (mail.extern.*). Fällt auf die PHP-mail()-Funktion
 zurück, wenn PHPMailer nicht konfiguriert ist.
 $bodyHtml != null  -> HTML-Mail (multipart: HTML + Text-Alternative).
 Im lokalen Test (.local / CLI) wird nichts versendet (nur Logeintrag).
------------------------------------------------------------------*/
function now_send_mail($to, $subjectRaw, $body, $bcc = '', $bodyHtml = null)
{
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

    // DEV: nicht wirklich senden
    if (substr($host, -6) === '.local' || PHP_SAPI === 'cli') {
        error_log("[NOW-Einwilligung] (DEV) Mail an $to" . ($bcc ? " (BCC $bcc)" : '') . " – Betreff: $subjectRaw" . ($bodyHtml !== null ? ' [HTML]' : ''));
        return true;
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
            return $mail->send();
        } catch (Throwable $e) {
            error_log('[NOW-Einwilligung] PHPMailer-Fehler: ' . $e->getMessage());
            // Fallback unten
        }
    }

    // Fallback: PHP mail()
    $subject  = '=?UTF-8?B?' . base64_encode($subjectRaw) . '?=';
    $headers  = 'From: ' . NOW_MAIL_FROMNAME . ' <' . NOW_MAIL_FROM . ">\r\n";
    $headers .= 'Reply-To: ' . NOW_MAIL_FROM . "\r\n";
    if ($bcc !== '') { $headers .= 'Bcc: ' . $bcc . "\r\n"; }
    $headers .= "MIME-Version: 1.0\r\n";

    if ($bodyHtml !== null) {
        $boundary = 'now_' . md5($to . '|' . $subjectRaw);
        $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
        $msg  = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . $body . "\r\n";
        $msg .= "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . $bodyHtml . "\r\n";
        $msg .= "--$boundary--\r\n";
        return @mail($to, $subject, $msg, $headers);
    }

    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit\r\n";
    return @mail($to, $subject, $body, $headers);
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
