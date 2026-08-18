<?php
/*******************************************************************************
 NOW-Einwilligung - Redaktions-Plugin in der Anbieter-Maske
 ******************************************************************************
 Erscheint als Menuepunkt "NOW-Einwilligung" in der Anbieter-Bearbeitung und
 oeffnet ein Popup (module.php?module=edit_plugin_anbieter_0&id=<anbieterId>).

 Zeigt fuer den jeweiligen Anbieter:
  - den aktuellen Einwilligungs-Status (anbieter.now_zustimmung),
  - die personalisierten Links zum Einwilligungs- und Widerrufsformular
    (fuer den einzelnen, manuellen Versand),
  - die revisionssichere Einwilligungs-Historie (Tabelle anbieter_now_einwilligung),
  - einen CSV-Export der Historie dieses Anbieters (inkl. Textkopien).

 Hinweis: Das Backend laeuft in ISO-8859-1 (latin1); Ausgaben daher latin1.
 *******************************************************************************/

require_once('functions.inc.php'); // Admin-Umgebung: Auth (Login erzwungen), $site, DB_Admin
require_once('eql.inc.php');

// NOW-Helfer (Token/Links/Decode) laden - ohne den Frontend-Bootstrap.
define('NOW_EINW_IN', true);
$nowRoot = dirname(dirname(__DIR__)); // .../wisy
require_once($nowRoot . '/now-einwilligung/inc/config.inc.php');
require_once($nowRoot . '/now-einwilligung/inc/functions.inc.php');
require_once($nowRoot . '/now-einwilligung/inc/now-infomail.inc.php');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$db = new DB_Admin;

// latin1-sichere Ausgabe-Escapes (Backend ist ISO-8859-1)
function nowadmin_h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'ISO-8859-1'); }

// Name der angemeldeten Redakteurin / des Redakteurs (latin1, fuer Protokoll)
function nowadmin_bearbeiter()
{
    $name = 'Redaktion';
    if (isset($_SESSION['g_session_userid']) && intval($_SESSION['g_session_userid']) > 0) {
        $dbU = new DB_Admin;
        $dbU->query("SELECT name, loginname FROM user WHERE id=" . intval($_SESSION['g_session_userid']));
        if ($dbU->next_record()) {
            $name = trim((string)$dbU->fs('name')) !== '' ? trim((string)$dbU->fs('name')) : trim((string)$dbU->fs('loginname'));
        }
    }
    return $name;
}

// --- Anbieter-Stammdaten ----------------------------------------------------
$db->query("SELECT suchname, pflege_email, anspr_email, now_zustimmung, now_zustimmung_fix FROM anbieter WHERE id=" . $id);
$anbieterOk   = $db->next_record();
$suchname     = $anbieterOk ? $db->fs('suchname')       : '';
$pflegeEmail  = $anbieterOk ? trim((string)$db->fs('pflege_email')) : '';
$ansprEmail   = $anbieterOk ? trim((string)$db->fs('anspr_email'))  : '';
$nowFlag      = $anbieterOk ? intval($db->f('now_zustimmung')) : 0;
$nowFix       = $anbieterOk ? intval($db->f('now_zustimmung_fix')) : 0;
$kontaktEmail = $pflegeEmail !== '' ? $pflegeEmail : $ansprEmail;

$linkEinw    = now_build_link($id);
$linkWiderr  = now_build_widerruf_link($id);

// --- Aktion: Anschreiben (HTML-Mail mit persoenlichem Link) senden ----------
$sendResult = null; // null = keine Aktion, true = gesendet, false = Fehler
if ($anbieterOk && isset($_GET['do']) && $_GET['do'] === 'anschreiben') {
    require_once($nowRoot . '/now-einwilligung/inc/now-anschreiben.inc.php');
    $sendResult = now_send_anschreiben(array(
        'id'           => $id,
        'pflege_email' => $pflegeEmail,
        'anspr_email'  => $ansprEmail,
    ));
}

// --- Aktion: Zustimmung redaktionell erteilen + Informations-Mail -----------
// (Einwilligung wurde auf anderem Wege erteilt; die Mail informiert nur und
// ruft hoechstens zur Gegenkontrolle auf. Ohne hinterlegten Info-Mail-Text
// oder ohne gueltige Pflege-Adresse unterbleibt die komplette Aktion.)
$redResult = null; // null = keine Aktion, sonst Rueckgabe von now_set_zustimmung_redaktionell()
if ($anbieterOk && isset($_GET['do']) && $_GET['do'] === 'redaktionell_erteilen') {
    $redResult = now_set_zustimmung_redaktionell(
        $id,
        true /*erteilen*/,
        now_to_utf8(nowadmin_bearbeiter()),
        true /*mit Info-Mail*/,
        'Anbieter-Maske'
    );
    // Status fuer die Anzeige neu laden
    $db->query("SELECT now_zustimmung, now_zustimmung_fix FROM anbieter WHERE id=" . $id);
    if ($db->next_record()) {
        $nowFlag = intval($db->f('now_zustimmung'));
        $nowFix  = intval($db->f('now_zustimmung_fix'));
    }
}

// --- Historie laden ---------------------------------------------------------
$hist = array();
$db->query("SELECT id, datum, aktion, vorname, nachname, email_bestaetigung,
                   version_paket, version_now_rechte, version_agb, version_datenschutz, hash,
                   text_now_rechte, text_agb, text_datenschutz
              FROM anbieter_now_einwilligung
             WHERE anbieter=" . $id . "
          ORDER BY datum DESC, id DESC");
while ($db->next_record()) {
    $hist[] = array(
        'id'        => intval($db->f('id')),
        'datum'     => $db->fs('datum'),
        'aktion'    => $db->fs('aktion'),
        'person'    => trim($db->fs('vorname') . ' ' . $db->fs('nachname')),
        'email'     => $db->fs('email_bestaetigung'),
        'v_paket'   => $db->fs('version_paket'),
        'v_now'     => $db->fs('version_now_rechte'),
        'v_agb'     => $db->fs('version_agb'),
        'v_dsg'     => $db->fs('version_datenschutz'),
        'hash'      => $db->fs('hash'),
        't_now'     => $db->fs('text_now_rechte'),
        't_agb'     => $db->fs('text_agb'),
        't_dsg'     => $db->fs('text_datenschutz'),
    );
}

// --- CSV-Export (vor jeglicher HTML-Ausgabe) --------------------------------
if (isset($_GET['format']) && $_GET['format'] === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="now_einwilligung_anbieter_' . $id . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, "\xEF\xBB\xBF");
    fputcsv($out, array('id','anbieter_id','anbieter','datum','aktion','person','email',
                        'version_paket','version_now','version_agb','version_datenschutz','hash',
                        'text_now_rechte','text_agb','text_datenschutz'), ';');
    foreach ($hist as $h) {
        fputcsv($out, array(
            $h['id'], $id, now_to_utf8($suchname), $h['datum'], $h['aktion'],
            now_to_utf8($h['person']), $h['email'],
            $h['v_paket'], $h['v_now'], $h['v_agb'], $h['v_dsg'], $h['hash'],
            now_html_to_text(now_archive_to_utf8($h['t_now'])),
            now_html_to_text(now_archive_to_utf8($h['t_agb'])),
            now_html_to_text(now_archive_to_utf8($h['t_dsg'])),
        ), ';');
    }
    fclose($out);
    exit;
}

// --- Seite -----------------------------------------------------------------
$site->pageStart(array('popfit' => 1));
$site->skin->workspaceStart();
?>
<style>
 .now-wrap { font-family: Arial, sans-serif; font-size: 13px; color:#222; }
 .now-wrap h2 { font-size: 1.15em; color:#004d71; margin: 0 0 .4em; }
 .now-status { padding:6px 10px; border-radius:4px; display:inline-block; font-weight:bold; }
 .now-ja  { background:#e8f5e9; color:#2e7d32; border:1px solid #2e7d32; }
 .now-nein{ background:#f3f3f3; color:#666;    border:1px solid #bbb; }
 .now-links td { padding:4px 8px; vertical-align:top; }
 .now-links input { width:520px; font-family:monospace; font-size:11px; }
 .now-tbl { border-collapse:collapse; width:100%; font-size:12px; margin-top:8px; }
 .now-tbl th, .now-tbl td { border:1px solid #ccc; padding:4px 6px; text-align:left; vertical-align:top; }
 .now-tbl th { background:#004d71; color:#fff; }
 .now-tbl tr:nth-child(even){ background:#f6f6f6; }
 .now-act-erteilt { color:#2e7d32; font-weight:bold; }
 .now-act-widerrufen { color:#c62828; font-weight:bold; }
 .now-act-erteilt_redaktion { color:#2e7d32; font-weight:bold; }
 .now-act-entzogen_redaktion { color:#c62828; font-weight:bold; }
 .now-fix { background:#fff3e0; color:#e65100; border:1px solid #e65100; }
 .now-hint { color:#666; font-size:11px; }
 .now-btn, a.now-btn, a.now-btn:link, a.now-btn:visited, a.now-btn:hover, a.now-btn:active {
   display:inline-block;margin:6px 0;padding:5px 10px;background:#004d71;
   color:#fff !important;text-decoration:none !important;border-radius:4px;border:1px solid #003a55; }
 .now-btn.redaktionell, a.now-btn.redaktionell, a.now-btn.redaktionell:link, a.now-btn.redaktionell:visited, a.now-btn.redaktionell:hover, a.now-btn.redaktionell:active {
   background-color:orange !important; color: black !important; font-weight: bold !important;}
 a.now-btn:hover { background:#00658f; }
</style>
<div class="now-wrap">

<?php if (!$anbieterOk): ?>
    <p><b>Anbieter (ID <?= (int)$id ?>) nicht gefunden.</b></p>
<?php else: ?>

    <h2>mein NOW - Einwilligung: <?= nowadmin_h($suchname) ?> (ID <?= (int)$id ?>)</h2>

    <p>Aktueller Status:
        <?php if ($nowFlag === 1): ?>
            <span class="now-status now-ja">Einwilligung erteilt - Daten werden an mein&nbsp;NOW &uuml;bermittelt</span>
        <?php else: ?>
            <span class="now-status now-nein">keine Einwilligung - keine &Uuml;bermittlung</span>
        <?php endif; ?>
        <?php if ($nowFix > 0): ?>
            <span class="now-status now-fix">fixiert</span>
        <?php endif; ?>
    </p>
    <?php if ($nowFix > 0): ?>
        <p class="now-hint">Der Status ist <b>fixiert</b> (H&auml;kchen &quot;NOW-Status fixiert&quot; in der
           Anbieter-Maske): Weder das Anbieter-Formular noch MultiEdit oder die redaktionelle Vergabe
           k&ouml;nnen ihn &auml;ndern. Zum &Auml;ndern zun&auml;chst die Fixierung in der Maske entfernen.</p>
    <?php else: ?>
        <p class="now-hint">Der Status ist &uuml;ber die folgenden Formular-Links oder die redaktionelle
           Vergabe (unten) &auml;nderbar - nicht direkt in der Maske.</p>
    <?php endif; ?>

    <table class="now-links">
        <tr>
            <td><b>Einwilligungs-Link:</b></td>
            <td><input type="text" readonly onclick="this.select()" value="<?= nowadmin_h($linkEinw) ?>"></td>
        </tr>
        <tr>
            <td><b>Widerrufs-Link:</b></td>
            <td><input type="text" readonly onclick="this.select()" value="<?= nowadmin_h($linkWiderr) ?>"></td>
        </tr>
        <tr>
            <td><b>Pflege-E-Mail:</b></td>
            <td><?= nowadmin_h($kontaktEmail !== '' ? $kontaktEmail : '(keine hinterlegt)') ?></td>
        </tr>
    </table>

    <?php if ($sendResult === true): ?>
        <p class="now-status now-ja">Anschreiben wurde per E-Mail an <?= nowadmin_h($kontaktEmail) ?> gesendet.</p>
    <?php elseif ($sendResult === false): ?>
        <p class="now-status now-nein">Anschreiben konnte NICHT gesendet werden
           (keine g&uuml;ltige Pflege-/Kontakt-E-Mail oder Versandfehler &ndash; siehe Server-Log).</p>
    <?php endif; ?>

    <?php if ($kontaktEmail !== ''): ?>
        <a class="now-btn" href="module.php?module=edit_plugin_anbieter_0&amp;id=<?= (int)$id ?>&amp;do=anschreiben"
           onclick="return confirm('Anschreiben (HTML-Mail mit pers&ouml;nlichem Link) jetzt an <?= nowadmin_h($kontaktEmail) ?> senden?');">
            Anschreiben jetzt an diesen Anbieter senden
        </a>
        <p class="now-hint">Sendet die HTML-Einladung mit dem pers&ouml;nlichen Einwilligungs-Link an die
           Pflege-Adresse des Anbieters (UTF-8, inkl. Fettdruck). Eine Kopie der bisherigen Einwilligungen
           wird dadurch nicht ber&uuml;hrt.</p>
    <?php else: ?>
        <p class="now-hint">Kein Versand m&ouml;glich: F&uuml;r diesen Anbieter ist keine Pflege-/Kontakt-E-Mail hinterlegt.</p>
    <?php endif; ?>

    <h2 style="margin-top:18px;">Zustimmung redaktionell erteilen</h2>

    <?php if ($redResult !== null): ?>
        <?php if ($redResult['status'] === 'ok'): ?>
            <p class="now-status now-ja">Zustimmung redaktionell erteilt und protokolliert.
               <?= (int)$redResult['kurse'] ?> Kurs(e) auf &Uuml;bermittlung gesetzt.
               <?php if ($redResult['mail_ok']): ?>
                   Informations-Mail an <?= nowadmin_h($redResult['email']) ?> gesendet.
               <?php else: ?>
                   ACHTUNG: Die Informations-Mail an <?= nowadmin_h($redResult['email']) ?> konnte NICHT
                   gesendet werden - bitte den Anbieter anderweitig informieren.
                   <?php if ($redResult['mail_error'] !== ''): ?>
                       <br>Grund: <?= nowadmin_h(now_to_latin1($redResult['mail_error'])) ?>
                   <?php endif; ?>
               <?php endif; ?>
               <?php if ($redResult['kopie_ok'] === true): ?>
                   <br>Nachweis-Kopie an <?= nowadmin_h(NOW_ADMIN_EMAIL) ?> gesendet.
               <?php elseif ($redResult['kopie_ok'] === false): ?>
                   <br>ACHTUNG: Die Nachweis-Kopie an <?= nowadmin_h(NOW_ADMIN_EMAIL) ?> konnte NICHT
                   gesendet werden<?= $redResult['kopie_error'] !== '' ? ' - Grund: ' . nowadmin_h(now_to_latin1($redResult['kopie_error'])) : '' ?>.
               <?php elseif ($redResult['kopie_ok'] === null && $redResult['mail_ok'] !== null): ?>
                   <br>Hinweis: Keine Nachweis-Kopie versendet - NOW_ADMIN_EMAIL ist nicht gesetzt.
               <?php endif; ?>
            </p>
            <?php if ($redResult['mail_transport'] === 'mail'): ?>
                <p class="now-status now-fix">
                    <b>ACHTUNG - Versandweg:</b> Die Mails gingen NICHT &uuml;ber das externe Postfach (SMTP),
                    sondern &uuml;ber das lokale Sendmail des Webservers <b>ohne Authentifizierung</b>.
                    Solche Mails scheitern an SPF/DMARC; strenge Empf&auml;nger (z.&nbsp;B. Gmail) weisen sie
                    hart zur&uuml;ck - trotz &quot;versendet&quot;-Meldung.
                    <?php if ($redResult['mail_warning'] !== ''): ?>
                        <br>Grund: <?= nowadmin_h(now_to_latin1($redResult['mail_warning'])) ?>
                    <?php endif; ?>
                    <br>Zu pr&uuml;fen: Portaleinstellungen <tt>mail.extern*</tt> des Portals, das zu diesem
                    Host geh&ouml;rt, sowie <tt>PHPMAILER_PATH</tt> in der Server-Konfiguration.
                </p>
            <?php endif; ?>
        <?php elseif ($redResult['status'] === 'fix'): ?>
            <p class="now-status now-nein">Nicht ausgef&uuml;hrt: Der Status ist fixiert (now_zustimmung_fix).</p>
        <?php elseif ($redResult['status'] === 'schon'): ?>
            <p class="now-status now-nein">Nicht ausgef&uuml;hrt: Die Zustimmung ist bereits erteilt.</p>
        <?php elseif ($redResult['status'] === 'kein_text'): ?>
            <p class="now-status now-nein">Nicht ausgef&uuml;hrt: Die Informations-Mail ist nicht vollst&auml;ndig
               hinterlegt (Text in now-einwilligung/inc/now-infomail.inc.php sowie NOW_INFOMAIL_FRIST und
               NOW_INFOMAIL_SIGNATUR in now-einwilligung/inc/config.inc.php).</p>
        <?php elseif ($redResult['status'] === 'keine_email'): ?>
            <p class="now-status now-nein">Nicht ausgef&uuml;hrt: Keine g&uuml;ltige Pflege-/Kontakt-E-Mail hinterlegt.</p>
        <?php else: ?>
            <p class="now-status now-nein">Nicht ausgef&uuml;hrt (<?= nowadmin_h($redResult['status']) ?>).</p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($nowFix > 0): ?>
        <p class="now-hint">Nicht m&ouml;glich: Der Status ist fixiert (now_zustimmung_fix). Zum &Auml;ndern
           zun&auml;chst das H&auml;kchen &quot;NOW-Status fixiert&quot; in der Anbieter-Maske entfernen.</p>
    <?php elseif ($nowFlag === 1): ?>
        <p class="now-hint">Die Zustimmung ist bereits erteilt - eine redaktionelle Vergabe ist nicht n&ouml;tig.</p>
    <?php elseif (!now_infomail_hinterlegt()): ?>
        <p class="now-hint">Nicht m&ouml;glich: Die <b>Informations-Mail ist nicht vollst&auml;ndig hinterlegt</b>.
           Ben&ouml;tigt werden der Text in <tt>now-einwilligung/inc/now-infomail.inc.php</tt> sowie in
           <tt>now-einwilligung/inc/config.inc.php</tt> die Werte <tt>NOW_INFOMAIL_FRIST</tt>
           (Widerspruchsfrist) und <tt>NOW_INFOMAIL_SIGNATUR</tt>. Die redaktionelle Vergabe setzt voraus,
           dass die Informations-Mail vollst&auml;ndig an den Anbieter versendet werden kann.</p>
    <?php elseif ($kontaktEmail === ''): ?>
        <p class="now-hint">Nicht m&ouml;glich: F&uuml;r diesen Anbieter ist keine Pflege-/Kontakt-E-Mail
           hinterlegt - die Informations-Mail k&ouml;nnte nicht zugestellt werden.</p>
    <?php else: ?>
        <a class="now-btn redaktionell" href="module.php?module=edit_plugin_anbieter_0&amp;id=<?= (int)$id ?>&amp;do=redaktionell_erteilen"
           onclick="return confirm('Zustimmung jetzt redaktionell erteilen (now_zustimmung=1, alle Kurse ohne Fixierung erhalten die Kurs-Zustimmung) und die Informations-E-Mail an <?= nowadmin_h($kontaktEmail) ?> senden (Nachweis-Kopie an <?= nowadmin_h(NOW_ADMIN_EMAIL) ?>)?');">
            Zustimmung jetzt erteilen + Informations-E-Mail senden
        </a>
        <p class="now-hint">F&uuml;r den Fall, dass die Einwilligung auf anderem Wege (z.&nbsp;B. schriftlich)
           erteilt wurde: setzt anbieter.now_zustimmung=1, protokolliert den Vorgang revisionssicher
           (Historie + Journal) und sendet die <b>Informations-Mail</b> (zweiter, eigener Text - informiert
           nur und ruft zur Gegenkontrolle auf) an die Pflege-Adresse. Die Redaktion
           (<?= nowadmin_h(NOW_ADMIN_EMAIL) ?>) erh&auml;lt zus&auml;tzlich eine gekennzeichnete
           <b>Nachweis-Kopie</b> mit Anbieter, Empf&auml;nger, Zeitpunkt, ausl&ouml;sender Person,
           Zustellergebnis und vollst&auml;ndigem Wortlaut - als Beleg des Versands, da der Anbieter
           hierauf nicht zwangsl&auml;ufig reagiert.</p>
    <?php endif; ?>

    <h2 style="margin-top:18px;">Einwilligungs-Historie
        (<?= count($hist) ?>)
        <?php if ($hist): ?>
            &nbsp;<a class="now-btn" href="module.php?module=edit_plugin_anbieter_0&amp;id=<?= (int)$id ?>&amp;format=csv">CSV-Export</a>
        <?php endif; ?>
    </h2>

    <?php if (!$hist): ?>
        <p class="now-hint">Bisher keine dokumentierte Einwilligung/Widerruf f&uuml;r diesen Anbieter.</p>
    <?php else: ?>
        <table class="now-tbl">
            <thead>
                <tr>
                    <th>Datum</th><th>Vorgang</th><th>Person</th><th>E-Mail</th>
                    <th>Versionen (Paket / NOW / AGB / DSG)</th><th>Hash</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($hist as $h): ?>
                <tr>
                    <td><?= nowadmin_h($h['datum']) ?></td>
                    <td class="now-act-<?= nowadmin_h($h['aktion']) ?>"><?= nowadmin_h($h['aktion']) ?></td>
                    <td><?= nowadmin_h($h['person']) ?></td>
                    <td><?= nowadmin_h($h['email']) ?></td>
                    <td><?= nowadmin_h($h['v_paket'] . ' / ' . $h['v_now'] . ' / ' . $h['v_agb'] . ' / ' . $h['v_dsg']) ?></td>
                    <td title="<?= nowadmin_h($h['hash']) ?>"><?= nowadmin_h(substr($h['hash'], 0, 12)) ?>&hellip;</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="now-hint">Die vollst&auml;ndigen, bei der Best&auml;tigung angezeigten Texte sind im CSV-Export enthalten.</p>
    <?php endif; ?>

<?php endif; ?>

</div>
<?php
$site->skin->workspaceEnd();
$site->pageEnd();
