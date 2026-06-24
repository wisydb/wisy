<?php

/**
 * Liefert Titel + Beschreibung von Kursen als JSON (UTF-8) fuer den
 * Beschreibungs-Textvergleich ("Lupe") in der Kurs-Duplikate-Uebersicht.
 *
 * Zwei Aufrufvarianten:
 *   A) robust:           ?row=<kurse_duplikate-Zeilen-ID>
 *      -> loest kurse_id1/kurse_id2 serverseitig auf und liefert beide Texte direkt
 *         als beschreibung1/beschreibung2 (unabhaengig von eingeblendeten Spalten).
 *   B) abwaertskompatibel: ?ids=<id1,id2,...>
 *      -> { courses: { "<id>": { titel, beschreibung } } }
 *
 * Es werden die LIVE-Texte aus der kurse-Tabelle gelesen.
 */

require_once('functions.inc.php');

header('Content-Type: application/json; charset=utf-8');

function courseinfo_fail($message, $status = 400)
{
    http_response_code($status);
    echo json_encode(array('success' => false, 'message' => $message));
    exit;
}

$db = new DB_Admin();

// ----- Variante A: row -----
$row = isset($_REQUEST['row']) ? intval($_REQUEST['row']) : 0;
if ($row > 0) {
    $db->query("SELECT kurse_id1, kurse_id2 FROM kurse_duplikate WHERE id=" . $row . " LIMIT 1");
    if (!$db->next_record()) {
        courseinfo_fail('Duplikat-Zeile nicht gefunden.', 404);
    }
    $id1 = intval($db->f('kurse_id1'));
    $id2 = intval($db->f('kurse_id2'));

    $info = array(); // id => array(titel, beschreibung)
    $idsForQuery = array();
    if ($id1 > 0) { $idsForQuery[$id1] = $id1; }
    if ($id2 > 0) { $idsForQuery[$id2] = $id2; }
    if (count($idsForQuery) > 0) {
        $db->query("SELECT id, titel, beschreibung FROM kurse WHERE id IN (" . implode(',', array_map('intval', $idsForQuery)) . ")");
        while ($db->next_record()) {
            $cid = intval($db->f('id'));
            $info[$cid] = array('titel' => $db->f8('titel'), 'beschreibung' => $db->f8('beschreibung'));
        }
    }

    echo json_encode(array(
        'success'       => true,
        'id1'           => $id1,
        'titel1'        => isset($info[$id1]) ? $info[$id1]['titel'] : '',
        'beschreibung1' => isset($info[$id1]) ? $info[$id1]['beschreibung'] : '',
        'id2'           => $id2,
        'titel2'        => isset($info[$id2]) ? $info[$id2]['titel'] : '',
        'beschreibung2' => isset($info[$id2]) ? $info[$id2]['beschreibung'] : '',
    ));
    exit;
}

// ----- Variante B: ids -----
$idsRaw = isset($_REQUEST['ids']) ? (string)$_REQUEST['ids'] : '';
$ids = array();
foreach (explode(',', $idsRaw) as $part) {
    $v = intval(trim($part));
    if ($v > 0) {
        $ids[$v] = $v;
    }
}
if (count($ids) === 0) {
    courseinfo_fail('Keine gueltigen Kurs-IDs uebergeben.');
}

$db->query("SELECT id, titel, beschreibung FROM kurse WHERE id IN (" . implode(',', array_map('intval', $ids)) . ")");
$courses = array();
while ($db->next_record()) {
    $cid = intval($db->f('id'));
    $courses[(string)$cid] = array(
        'titel'        => $db->f8('titel'),
        'beschreibung' => $db->f8('beschreibung'),
    );
}

echo json_encode(array('success' => true, 'courses' => $courses));
