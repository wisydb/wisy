<?php

/**
 * Setzt den Duplikat-Status einer Zeile in kurse_duplikate per AJAX.
 *
 * Erwartet POST/GET-Parameter:
 *   - id1    : Kurs-ID 1 (eines der beiden Paar-Kurse)
 *   - id2    : Kurs-ID 2 (das andere)
 *   - value  : 0 (kein Duplikat) oder 1 (Duplikat)
 *
 * Aktualisiert NUR die Spalte duplikat sowie user_modified und date_modified.
 * Dadurch greift der Template-User-Mechanismus aus ki/main.py: sobald eine Redakteurin
 * den Status aendert, ist user_modified != TEMPLATE_USER_ID (=7) und der Eintrag
 * gilt als "redaktionell" - kann also nicht mehr durch einen automatischen KI-Lauf
 * ueberschrieben werden, wenn die Loesch-Automatik wieder aktiviert wird.
 *
 * Liefert JSON.
 */

require_once('functions.inc.php');

header('Content-Type: application/json; charset=latin1');

function fail_response($message, $status = 400)
{
    http_response_code($status);
    echo json_encode(array(
        'success' => false,
        'message' => $message,
    ));
    exit;
}

$id1   = isset($_REQUEST['id1'])   ? intval($_REQUEST['id1'])   : 0;
$id2   = isset($_REQUEST['id2'])   ? intval($_REQUEST['id2'])   : 0;
$value = isset($_REQUEST['value']) ? intval($_REQUEST['value']) : -1;

if ($id1 <= 0 || $id2 <= 0) {
    fail_response('Ungueltige Kurs-IDs uebergeben.');
}

if ($id1 === $id2) {
    fail_response('Die beiden Kurs-IDs duerfen nicht identisch sein.');
}

if ($value !== 0 && $value !== 1) {
    fail_response('Ungueltiger Wert fuer Duplikat-Status (nur 0 oder 1 erlaubt).');
}

$userId = isset($_SESSION['g_session_userid']) ? intval($_SESSION['g_session_userid']) : 0;
if ($userId <= 0) {
    fail_response('Nicht eingeloggt.', 401);
}

$db = new DB_Admin();

// Passende Zeile finden (Reihenfolge der Kurs-IDs egal).
$db->query(
    "SELECT id, duplikat FROM kurse_duplikate "
    . "WHERE (kurse_id1=" . $id1 . " AND kurse_id2=" . $id2 . ") "
    . "   OR (kurse_id1=" . $id2 . " AND kurse_id2=" . $id1 . ") "
    . "LIMIT 1"
);

if (!$db->next_record()) {
    fail_response('Kein passender Duplikat-Eintrag gefunden.', 404);
}

$rowId        = intval($db->f('id'));
$previousFlag = intval($db->f('duplikat'));

if ($previousFlag === $value) {
    // Schon im Zielzustand: trotzdem user_modified/date_modified aktualisieren,
    // damit klar ist, dass die Redaktion den Status (re-)bestaetigt hat.
    $db->query(
        "UPDATE kurse_duplikate SET "
        . "user_modified=" . $userId . ", "
        . "date_modified=NOW() "
        . "WHERE id=" . $rowId
    );
}
else {
    $db->query(
        "UPDATE kurse_duplikate SET "
        . "duplikat=" . $value . ", "
        . "user_modified=" . $userId . ", "
        . "date_modified=NOW() "
        . "WHERE id=" . $rowId
    );
}

echo json_encode(array(
    'success'       => true,
    'row_id'        => $rowId,
    'id1'           => $id1,
    'id2'           => $id2,
    'previous'      => $previousFlag,
    'value'         => $value,
    'unchanged'     => ($previousFlag === $value),
));
