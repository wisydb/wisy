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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$db = new DB_Admin;

// latin1-sichere Ausgabe-Escapes (Backend ist ISO-8859-1)
function nowadmin_h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'ISO-8859-1'); }

// --- Anbieter-Stammdaten ----------------------------------------------------
$db->query("SELECT suchname, pflege_email, anspr_email, now_zustimmung FROM anbieter WHERE id=" . $id);
$anbieterOk   = $db->next_record();
$suchname     = $anbieterOk ? $db->fs('suchname')       : '';
$pflegeEmail  = $anbieterOk ? trim((string)$db->fs('pflege_email')) : '';
$ansprEmail   = $anbieterOk ? trim((string)$db->fs('anspr_email'))  : '';
$nowFlag      = $anbieterOk ? intval($db->f('now_zustimmung')) : 0;
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
 .now-hint { color:#666; font-size:11px; }
 .now-btn, a.now-btn, a.now-btn:link, a.now-btn:visited, a.now-btn:hover, a.now-btn:active {
   display:inline-block;margin:6px 0;padding:5px 10px;background:#004d71;
   color:#fff !important;text-decoration:none !important;border-radius:4px;border:1px solid #003a55; }
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
    </p>
    <p class="now-hint">Der Status ist nur &uuml;ber die folgenden Formular-Links &auml;nderbar (nicht in der Maske).</p>

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
