<?php
/*******************************************************************************
 NOW-Einwilligung – Link-Generator (Redaktions-/Admin-Werkzeug)
 ******************************************************************************
 Erzeugt für das Anbieter-Anschreiben die personalisierten, signierten Links
 zum Einwilligungs- und Widerrufsformular und listet die Pflege-E-Mail-Adressen.

 Aufruf (nur mit korrektem Passwort aus config.inc.php):
   /now-einwilligung/linkgenerator.php?pw=<NOW_ADMIN_PW>
 Optionen:
   &format=csv     CSV-Ausgabe (für Serienbrief/Mail-Merge)
   &only=offen     nur Anbieter (aus der Vorauswahl) ohne bestehende Einwilligung
   &all=1          Vorauswahl-Filter ("NOW ja") ignorieren, alle anzeigen
 *******************************************************************************/

define('NOW_EINW_IN', true);

require_once(__DIR__ . '/inc/config.inc.php');
require_once(__DIR__ . '/inc/bootstrap.inc.php');
require_once(__DIR__ . '/inc/functions.inc.php');

// --- Zugriffsschutz ---------------------------------------------------------
$pw = isset($_GET['pw']) ? (string)$_GET['pw'] : '';
if (NOW_ADMIN_PW === '' || NOW_ADMIN_PW === 'BITTE-AENDERN-admin-passwort' || !hash_equals(NOW_ADMIN_PW, $pw)) {
    header('HTTP/1.1 403 Forbidden');
    die('Zugriff verweigert. Bitte mit g&uuml;ltigem ?pw=... aufrufen (und NOW_ADMIN_PW in config.inc.php setzen).');
}

$baseUrl   = now_public_baseurl(); // spiegelt Sandbox-Host wider (Fallback: NOW_PUBLIC_BASEURL)
$onlyOpen  = (isset($_GET['only']) && $_GET['only'] === 'offen');
$csv       = (isset($_GET['format']) && $_GET['format'] === 'csv');
$showAll   = (isset($_GET['all']) && $_GET['all'] === '1');
$useFilter = (NOW_FILTER_BY_STICHWORT && !$showAll);

// --- Daten laden ------------------------------------------------------------
$db = $GLOBALS['now_db'];
if ($useFilter) {
    $sql = "SELECT a.id, a.suchname, a.pflege_email, a.anspr_email, a.now_zustimmung
              FROM anbieter a
              JOIN anbieter_stichwort s ON s.primary_id = a.id AND s.attr_id = " . intval(NOW_STICHWORT_JA) . "
             WHERE a.freigeschaltet = 1";
    if ($onlyOpen) { $sql .= " AND a.now_zustimmung = 0"; }
    $sql .= " ORDER BY a.suchname_sorted, a.suchname";
} else {
    $sql = "SELECT id, suchname, pflege_email, anspr_email, now_zustimmung
              FROM anbieter WHERE freigeschaltet = 1";
    if ($onlyOpen) { $sql .= " AND now_zustimmung = 0"; }
    $sql .= " ORDER BY suchname_sorted, suchname";
}
$db->query($sql);

$rows = array();
while ($db->next_record()) {
    $id    = intval($db->f('id'));
    $email = trim((string)$db->fs('pflege_email'));
    if ($email === '') { $email = trim((string)$db->fs('anspr_email')); }
    $rows[] = array(
        'id'       => $id,
        'name'     => now_to_utf8($db->fs('suchname')),
        'email'    => $email,
        'consent'  => intval($db->f('now_zustimmung')),
        'link'     => now_build_link($id, $baseUrl),
        'widerruf' => now_build_widerruf_link($id, $baseUrl),
    );
}

// --- CSV --------------------------------------------------------------------
if ($csv) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="now_einwilligung_links.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, "\xEF\xBB\xBF"); // UTF-8 BOM für Excel
    fputcsv($out, array('anbieter_id', 'anbieter_name', 'pflege_email', 'eingewilligt', 'einwilligung_link', 'widerruf_link'), ';');
    foreach ($rows as $r) {
        fputcsv($out, array($r['id'], $r['name'], $r['email'], $r['consent'] ? 'ja' : 'nein', $r['link'], $r['widerruf']), ';');
    }
    fclose($out);
    exit;
}

// --- HTML -------------------------------------------------------------------
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>NOW-Einwilligung – Link-Generator</title>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
    h1 { font-size: 1.3em; }
    table { border-collapse: collapse; width: 100%; font-size: .88em; }
    th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; vertical-align: top; }
    th { background: #004d71; color: #fff; }
    tr:nth-child(even) { background: #f5f5f5; }
    .yes { color: #2e7d32; font-weight: bold; }
    .no  { color: #999; }
    .link { font-family: monospace; font-size: .82em; word-break: break-all; }
    .toolbar { margin-bottom: 14px; }
    .toolbar a { margin-right: 14px; }
    .warn { background:#fdecea;border:1px solid #c62828;padding:8px 12px;border-radius:5px; }
    .info { background:#e7f1f7;border:1px solid #004d71;padding:8px 12px;border-radius:5px;margin-bottom:12px; }
</style>
</head>
<body>
<h1>NOW-Einwilligung – personalisierte Links (<?= count($rows) ?> Anbieter)</h1>

<?php if (NOW_LINK_SECRET === 'BITTE-AENDERN-zufaelliges-geheimnis'): ?>
    <p class="warn"><strong>Hinweis:</strong> NOW_LINK_SECRET ist noch der Platzhalter.
       Bitte vor dem Versand der Anschreiben in <code>inc/config.inc.php</code> ein echtes Geheimnis setzen,
       sonst sind die Links nicht fälschungssicher.</p>
<?php endif; ?>

<p class="info">
    <?php if ($useFilter): ?>
        Vorauswahl: nur Anbieter mit Verwaltungsstichwort <strong>„NOW ja“</strong> (ID <?= (int)NOW_STICHWORT_JA ?>).
    <?php else: ?>
        Filter deaktiviert – es werden <strong>alle</strong> freigeschalteten Anbieter angezeigt.
    <?php endif; ?>
</p>

<div class="toolbar">
    <a href="?pw=<?= now_h($pw) ?>&format=csv<?= $onlyOpen ? '&only=offen' : '' ?><?= $showAll ? '&all=1' : '' ?>">⬇ Als CSV herunterladen</a>
    <?php if ($onlyOpen): ?>
        <a href="?pw=<?= now_h($pw) ?><?= $showAll ? '&all=1' : '' ?>">alle (auch eingewilligte) anzeigen</a>
    <?php else: ?>
        <a href="?pw=<?= now_h($pw) ?>&only=offen<?= $showAll ? '&all=1' : '' ?>">nur offene (ohne Einwilligung)</a>
    <?php endif; ?>
    <?php if (NOW_FILTER_BY_STICHWORT): ?>
        <?php if ($showAll): ?>
            <a href="?pw=<?= now_h($pw) ?><?= $onlyOpen ? '&only=offen' : '' ?>">nur Vorauswahl „NOW ja“</a>
        <?php else: ?>
            <a href="?pw=<?= now_h($pw) ?>&all=1<?= $onlyOpen ? '&only=offen' : '' ?>">alle Anbieter (Filter aus)</a>
        <?php endif; ?>
    <?php endif; ?>
</div>

<table>
    <thead>
        <tr><th>ID</th><th>Anbieter</th><th>Pflege-E-Mail</th><th>Eingew.</th><th>Einwilligungs-Link</th><th>Widerrufs-Link</th></tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= now_h($r['name']) ?></td>
            <td><?= now_h($r['email']) ?></td>
            <td><?= $r['consent'] ? '<span class="yes">ja</span>' : '<span class="no">–</span>' ?></td>
            <td class="link"><a href="<?= now_h($r['link']) ?>"><?= now_h($r['link']) ?></a></td>
            <td class="link"><a href="<?= now_h($r['widerruf']) ?>"><?= now_h($r['widerruf']) ?></a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
