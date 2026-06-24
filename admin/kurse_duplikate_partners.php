<?php

/**
 * Liefert zu einer Kurs-ID alle als echtes Duplikat (duplikat=1) erkannten
 * Partner-Kurse samt deren aktueller Erschliessung als JSON.
 *
 * Wird von der Kurs-Edit-Maske (admin/functions.js) per AJAX aufgerufen, um
 * dort eine "Duplikate"-Sektion anzuzeigen.
 *
 * Parameter:
 *   - id : Kurs-ID, deren Duplikate gesucht werden
 *
 * Rueckgabe (UTF-8 JSON):
 *   {
 *     success: true,
 *     id: <kursId>,
 *     partners: [
 *       {
 *         id, titel, anbieter_id, anbieter_name,
 *         thema: { id, kuerzel, name } | null,
 *         stichwoerter: [ { id, name, actype }, ... ]
 *       }, ...
 *     ]
 *   }
 *
 * Hinweis: Es werden bewusst die LIVE-Daten aus kurse/anbieter/themen/stichwoerter
 * gelesen (nicht die ggf. veralteten Kontextspalten aus kurse_duplikate), damit
 * die Anzeige und eine spaetere Erschliessungs-Uebernahme aktuelle IDs/Titel nutzt.
 */

require_once('functions.inc.php');

header('Content-Type: application/json; charset=utf-8');

function partners_fail($message, $status = 400)
{
    http_response_code($status);
    echo json_encode(array('success' => false, 'message' => $message));
    exit;
}

$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
if ($id <= 0) {
    partners_fail('Ungueltige Kurs-ID.');
}

// Anzeige der Duplikate-Sektion ist per Benutzereinstellung abschaltbar (Standard: an).
// Vgl. Einstellungen -> Ansicht (settings.php?table=kurse&scope=edit).
if (intval(regGet('edit.kurse.showduplikate', 1)) === 0) {
    echo json_encode(array('success' => true, 'id' => $id, 'partners' => array(), 'disabled' => true));
    exit;
}

$db = new DB_Admin();

// 1) Partner-IDs aus den echten Duplikat-Paaren ermitteln
$partnerIds = array();
$db->query(
    "SELECT kurse_id1, kurse_id2 FROM kurse_duplikate "
    . "WHERE duplikat=1 AND (kurse_id1=" . $id . " OR kurse_id2=" . $id . ")"
);
while ($db->next_record()) {
    $cid1 = intval($db->f('kurse_id1'));
    $cid2 = intval($db->f('kurse_id2'));
    $partnerId = ($cid1 === $id) ? $cid2 : $cid1;
    if ($partnerId > 0 && $partnerId !== $id) {
        $partnerIds[$partnerId] = $partnerId;
    }
}

if (count($partnerIds) === 0) {
    echo json_encode(array('success' => true, 'id' => $id, 'partners' => array()));
    exit;
}

$inList = implode(',', array_map('intval', $partnerIds));

// Status-Namen + Sortier-Prioritaet (vgl. kurse.freigeschaltet in config/db.inc.php)
// 0=In Vorbereitung, 1=Freigegeben, 2=Gesperrt, 3=Abgelaufen, 4=Dauerhaft
$statusNames = array(
    0 => 'In Vorbereitung',
    1 => 'Freigegeben',
    2 => 'Gesperrt',
    3 => 'Abgelaufen',
    4 => 'Dauerhaft',
);
// gewuenschte Reihenfolge: Freigegeben, Dauerhaft, In Vorbereitung, Abgelaufen, Gesperrt
$statusSortPrio = array(
    1 => 0, // Freigegeben
    4 => 1, // Dauerhaft
    0 => 2, // In Vorbereitung
    3 => 3, // Abgelaufen
    2 => 4, // Gesperrt
);

// 2) Kursdaten + Anbieter laden
$courses = array();      // id => array(titel, anbieter_id, anbieter_name, thema_id)
$themaIds = array();
$db->query(
    "SELECT k.id, k.titel, k.anbieter, k.thema, k.freigeschaltet, a.suchname AS anbieter_name "
    . "FROM kurse k LEFT JOIN anbieter a ON a.id = k.anbieter "
    . "WHERE k.id IN (" . $inList . ")"
);
while ($db->next_record()) {
    $cid = intval($db->f('id'));
    $themaId = intval($db->f('thema'));
    $status = intval($db->f('freigeschaltet'));
    $courses[$cid] = array(
        'titel'         => $db->f8('titel'),
        'anbieter_id'   => intval($db->f('anbieter')),
        'anbieter_name' => $db->f8('anbieter_name'),
        'thema_id'      => $themaId,
        'thema'         => null,
        'status'        => $status,
        'status_name'   => isset($statusNames[$status]) ? $statusNames[$status] : ('Status ' . $status),
        'sortprio'      => isset($statusSortPrio[$status]) ? $statusSortPrio[$status] : 99,
        'stichwoerter'  => array(),
    );
    if ($themaId > 0) {
        $themaIds[$themaId] = $themaId;
    }
}

// 3) Themen (Kuerzel + Name) laden
$themen = array();
if (count($themaIds) > 0) {
    $db->query(
        "SELECT id, kuerzel, thema FROM themen WHERE id IN (" . implode(',', array_map('intval', $themaIds)) . ")"
    );
    while ($db->next_record()) {
        $tid = intval($db->f('id'));
        $themen[$tid] = array(
            'id'      => $tid,
            'kuerzel' => $db->f8('kuerzel'),
            'name'    => $db->f8('thema'),
        );
    }
}

// 4) Stichwoerter je Partner-Kurs laden (in Reihenfolge der Erschliessung)
$db->query(
    "SELECT ks.primary_id, ks.attr_id, s.stichwort, s.eigenschaften "
    . "FROM kurse_stichwort ks LEFT JOIN stichwoerter s ON s.id = ks.attr_id "
    . "WHERE ks.primary_id IN (" . $inList . ") "
    . "ORDER BY ks.primary_id, ks.structure_pos"
);
while ($db->next_record()) {
    $cid = intval($db->f('primary_id'));
    if (!isset($courses[$cid])) {
        continue;
    }
    $courses[$cid]['stichwoerter'][] = array(
        'id'     => intval($db->f('attr_id')),
        'name'   => $db->f8('stichwort'),
        'actype' => $db->f('eigenschaften') !== null ? (string)$db->f('eigenschaften') : '',
    );
}

// 5) Themen den Kursen zuordnen + Ausgabeliste bauen
//    Sortierung: zuerst nach Status-Prioritaet, dann stabil nach ID
$partners = array();
ksort($courses);
foreach ($courses as $cid => $c) {
    $thema = null;
    if ($c['thema_id'] > 0 && isset($themen[$c['thema_id']])) {
        $thema = $themen[$c['thema_id']];
    }
    $partners[] = array(
        'id'            => $cid,
        'titel'         => $c['titel'] !== null ? $c['titel'] : '',
        'anbieter_id'   => $c['anbieter_id'],
        'anbieter_name' => $c['anbieter_name'] !== null ? $c['anbieter_name'] : '',
        'status'        => $c['status'],
        'status_name'   => $c['status_name'],
        'sortprio'      => $c['sortprio'],
        'thema'         => $thema,
        'stichwoerter'  => $c['stichwoerter'],
    );
}

// nach Status-Prioritaet sortieren (ID-Reihenfolge als stabiler Sekundaerschluessel,
// da usort in PHP 8 zwar stabil ist, wir aber explizit absichern)
usort($partners, function ($a, $b) {
    if ($a['sortprio'] !== $b['sortprio']) {
        return $a['sortprio'] - $b['sortprio'];
    }
    return $a['id'] - $b['id'];
});

echo json_encode(array(
    'success'  => true,
    'id'       => $id,
    'partners' => $partners,
));
