<?php

require_once('functions.inc.php');

define('KURSE_DUPLIKATE_MAINTENANCE_NO_AUTORUN', 1);
require_once('kurse_duplikate_maintenance.php');

/**
 * Liefert den Anzeige-Namen (loginname) des aktuell eingeloggten Benutzers
 * fuer den Journal-Eintrag. Fallback: "WISY".
 */
function kurse_duplikate_current_loginname($db)
{
    if (isset($_SESSION['g_session_userloginname']) && trim((string)$_SESSION['g_session_userloginname']) !== '') {
        return trim((string)$_SESSION['g_session_userloginname']);
    }
    $uid = isset($_SESSION['g_session_userid']) ? intval($_SESSION['g_session_userid']) : 0;
    if ($uid > 0) {
        $db->query("SELECT loginname FROM user WHERE id=" . $uid . " LIMIT 1");
        if ($db->next_record()) {
            $name = trim((string)$db->fs('loginname'));
            if ($name !== '') {
                return $name;
            }
        }
    }
    return 'WISY';
}

/**
 * Uebertraegt die Erschliessung (Thema + fehlende Stichwoerter) von $source auf $target,
 * setzt einen Journal-Eintrag im Zielkurs und synchronisiert die betroffenen
 * Duplikat-Zeilen neu.
 *
 * Rueckgabe (assoziatives Array):
 *   success, [message], source, target, thema_transferred, keywords_added, changed, updated_duplicate_rows
 */
function kurse_duplikate_transfer_one($db, $source, $target, $loginname)
{
    $source = intval($source);
    $target = intval($target);

    if ($source <= 0 || $target <= 0) {
        return array('success' => false, 'message' => 'Ungueltige Kurs-IDs.', 'target' => $target);
    }
    if ($source === $target) {
        return array('success' => false, 'message' => 'Quelle und Ziel duerfen nicht identisch sein.', 'target' => $target);
    }

    // Quelle laden
    $db->query("SELECT id, thema FROM kurse WHERE id=" . $source . " LIMIT 1");
    if (!$db->next_record()) {
        return array('success' => false, 'message' => 'Quellkurs nicht gefunden.', 'target' => $target);
    }
    $sourceThema = intval($db->f('thema'));

    // Ziel laden
    $db->query("SELECT id, thema FROM kurse WHERE id=" . $target . " LIMIT 1");
    if (!$db->next_record()) {
        return array('success' => false, 'message' => 'Zielkurs nicht gefunden.', 'target' => $target);
    }
    $targetThema = intval($db->f('thema'));
    $themaChanged = ($targetThema !== $sourceThema);

    // Stichwoerter Quelle
    $sourceKeywordIds = array();
    $db->query("SELECT attr_id FROM kurse_stichwort WHERE primary_id=" . $source);
    while ($db->next_record()) {
        $sourceKeywordIds[intval($db->f('attr_id'))] = true;
    }

    // Stichwoerter Ziel + hoechste structure_pos
    $targetKeywordIds = array();
    $maxStructurePos = 0;
    $db->query("SELECT attr_id, structure_pos FROM kurse_stichwort WHERE primary_id=" . $target . " ORDER BY structure_pos ASC");
    while ($db->next_record()) {
        $attrId = intval($db->f('attr_id'));
        $targetKeywordIds[$attrId] = true;
        $currPos = intval($db->f('structure_pos'));
        if ($currPos > $maxStructurePos) {
            $maxStructurePos = $currPos;
        }
    }

    $insertedKeywords = 0;
    foreach ($sourceKeywordIds as $attrId => $_) {
        if (!isset($targetKeywordIds[$attrId])) {
            $maxStructurePos++;
            $db->query(
                "INSERT INTO kurse_stichwort (primary_id, attr_id, structure_pos) VALUES ("
                . $target . ", " . intval($attrId) . ", " . $maxStructurePos . ")"
            );
            $insertedKeywords++;
        }
    }

    $wasChanged = ($themaChanged || $insertedKeywords > 0);

    if ($wasChanged) {
        $userId = isset($_SESSION['g_session_userid']) ? intval($_SESSION['g_session_userid']) : 0;
        // Statischer (deutscher) Teil als UTF-8 -> latin1; loginname (aus DB/Session, bereits latin1) separat anhaengen
        $prefix = date('d.m.y') . ': Duplikat: Erschliessung automatisch übertragen von Kurs-ID ' . $source . ' (';
        $noteLine = utf8_decode($prefix) . $loginname . ')' . "\n";
        $noteLineSql = addslashes($noteLine);

        $db->query(
            "UPDATE kurse SET "
            . "thema=" . $sourceThema . ", "
            . "notizen=CONCAT('" . $noteLineSql . "', IFNULL(notizen, '')), "
            . "user_modified=" . $userId . ", "
            . "date_modified=NOW() "
            . "WHERE id=" . $target
        );
    }

    // Relevante Duplikat-Zeilen fuer dieses Paar vollstaendig neu synchronisieren
    // (Rueckgabe ist ein Array: 'updated' + 'deleted')
    $syncResult = kurse_duplikate_maintenance_run(array('id1' => $source, 'id2' => $target));

    return array(
        'success' => true,
        'source' => $source,
        'target' => $target,
        'thema_transferred' => $sourceThema,
        'keywords_added' => $insertedKeywords,
        'changed' => $wasChanged,
        'updated_duplicate_rows' => intval($syncResult['updated']),
    );
}

// ===== Auto-Run als AJAX-Endpoint (1:1-Uebertragung Quelle -> Ziel) =====
if (!defined('KURSE_DUPLIKATE_TAGTRANSFER_NO_AUTORUN')) {

    header('Content-Type: application/json; charset=latin1');

    $db = new DB_Admin();

    // Variante A (robust): row = kurse_duplikate-Zeilen-ID, dir = 'l2r' | 'r2l'.
    // Quelle/Ziel werden serverseitig aufgeloest -> unabhaengig von eingeblendeten Spalten.
    // Variante B (abwaertskompatibel): direkt source + target (Kurs-IDs).
    $row = isset($_REQUEST['row']) ? intval($_REQUEST['row']) : 0;
    $dir = isset($_REQUEST['dir']) ? (string)$_REQUEST['dir'] : '';
    $source = isset($_REQUEST['source']) ? intval($_REQUEST['source']) : 0;
    $target = isset($_REQUEST['target']) ? intval($_REQUEST['target']) : 0;

    if ($row > 0 && ($dir === 'l2r' || $dir === 'r2l')) {
        $db->query("SELECT kurse_id1, kurse_id2 FROM kurse_duplikate WHERE id=" . $row . " LIMIT 1");
        if (!$db->next_record()) {
            http_response_code(404);
            echo json_encode(array('success' => false, 'message' => 'Duplikat-Zeile nicht gefunden.'));
            exit;
        }
        $rid1 = intval($db->f('kurse_id1'));
        $rid2 = intval($db->f('kurse_id2'));
        $source = ($dir === 'l2r') ? $rid1 : $rid2;
        $target = ($dir === 'l2r') ? $rid2 : $rid1;
    }

    if ($source <= 0 || $target <= 0) {
        http_response_code(400);
        echo json_encode(array('success' => false, 'message' => 'Ungueltige Kurs-IDs uebergeben.'));
        exit;
    }
    if ($source === $target) {
        http_response_code(400);
        echo json_encode(array('success' => false, 'message' => 'Quelle und Ziel duerfen nicht identisch sein.'));
        exit;
    }

    $loginname = kurse_duplikate_current_loginname($db);
    $result = kurse_duplikate_transfer_one($db, $source, $target, $loginname);

    if (empty($result['success'])) {
        $msg = isset($result['message']) ? $result['message'] : '';
        http_response_code(($msg === 'Quellkurs nicht gefunden.' || $msg === 'Zielkurs nicht gefunden.') ? 404 : 400);
    }
    echo json_encode($result);
}
