<?php

/**
 * Uebertraegt die Erschliessung eines Quell-Kurses auf ALLE seine erkannten
 * Duplikate (kurse_duplikate.duplikat=1, egal ob der Quellkurs dort als
 * kurse_id1 oder kurse_id2 steht).
 *
 * Erwartet POST/GET:
 *   - source : Kurs-ID, deren Erschliessung verteilt werden soll
 *
 * Pro Ziel wird kurse_duplikate_transfer_one() aufgerufen (Thema setzen,
 * fehlende Stichwoerter ergaenzen, Journal-Eintrag, Re-Sync der Duplikat-Zeile).
 *
 * Liefert JSON mit einer Zusammenfassung.
 */

require_once('functions.inc.php');

define('KURSE_DUPLIKATE_TAGTRANSFER_NO_AUTORUN', 1);
require_once('kurse_duplikate_tagtransfer.php');

header('Content-Type: application/json; charset=latin1');

$db = new DB_Admin();

// Variante A (robust): row = kurse_duplikate-Zeilen-ID, side = '1' (linker Kurs) | '2' (rechter Kurs).
// Variante B (abwaertskompatibel): direkt source (Kurs-ID).
$row    = isset($_REQUEST['row']) ? intval($_REQUEST['row']) : 0;
$side   = isset($_REQUEST['side']) ? (string)$_REQUEST['side'] : '';
$source = isset($_REQUEST['source']) ? intval($_REQUEST['source']) : 0;

if ($row > 0 && ($side === '1' || $side === '2')) {
    $db->query("SELECT kurse_id1, kurse_id2 FROM kurse_duplikate WHERE id=" . $row . " LIMIT 1");
    if (!$db->next_record()) {
        http_response_code(404);
        echo json_encode(array('success' => false, 'message' => 'Duplikat-Zeile nicht gefunden.'));
        exit;
    }
    $source = ($side === '1') ? intval($db->f('kurse_id1')) : intval($db->f('kurse_id2'));
}

if ($source <= 0) {
    http_response_code(400);
    echo json_encode(array('success' => false, 'message' => 'Ungueltige Quell-Kurs-ID.'));
    exit;
}

// Alle ECHTEN Duplikat-Partner des Quellkurses ermitteln (duplikat=1)
$targets = array();
$db->query(
    "SELECT kurse_id1, kurse_id2 FROM kurse_duplikate "
    . "WHERE duplikat=1 AND (kurse_id1=" . $source . " OR kurse_id2=" . $source . ")"
);
while ($db->next_record()) {
    $a = intval($db->f('kurse_id1'));
    $b = intval($db->f('kurse_id2'));
    $partner = ($a === $source) ? $b : $a;
    if ($partner > 0 && $partner !== $source) {
        $targets[$partner] = $partner;
    }
}

if (count($targets) === 0) {
    echo json_encode(array(
        'success'         => true,
        'source'          => $source,
        'targets_total'   => 0,
        'targets_changed' => 0,
        'keywords_added'  => 0,
        'failed'          => 0,
    ));
    exit;
}

$loginname = kurse_duplikate_current_loginname($db);

$changed  = 0;
$keywords = 0;
$failed   = 0;
$failures = array(); // je Fehlschlag: target-ID + Grund (zur Diagnose)
foreach ($targets as $t) {
    $r = kurse_duplikate_transfer_one($db, $source, $t, $loginname);
    if (!empty($r['success'])) {
        if (!empty($r['changed'])) {
            $changed++;
        }
        $keywords += intval($r['keywords_added']);
    } else {
        $failed++;
        $failures[] = array(
            'target'  => isset($r['target']) ? intval($r['target']) : intval($t),
            'message' => isset($r['message']) ? $r['message'] : 'Unbekannter Fehler.',
        );
    }
}

echo json_encode(array(
    'success'         => true,
    'source'          => $source,
    'targets_total'   => count($targets),
    'targets_changed' => $changed,
    'keywords_added'  => $keywords,
    'failed'          => $failed,
    'failures'        => $failures,
));
