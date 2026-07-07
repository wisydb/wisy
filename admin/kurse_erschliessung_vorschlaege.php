<?php

/**
 * Liefert zu einer Kurs-ID statistisch ermittelte Erschliessungs-Vorschlaege
 * (Themen + Stichwoerter) als JSON - OHNE Aufruf eines KI-Modells.
 *
 * Wird von der Kurs-Edit-Maske (admin/functions.js) per AJAX aufgerufen, um
 * dort eine "Erschliessungsvorschlaege"-Sektion anzuzeigen. Die Uebernahme
 * erfolgt dort clientseitig per attr_add (wirksam erst beim Speichern).
 *
 * Zwei Vorschlagsquellen, die kombiniert und gemeinsam gerankt werden:
 *
 *   A) Nachbarschafts-Vorschlaege ("more like this"):
 *      Ueber den bestehenden Volltext-Index `titel_beschreibung` der Tabelle
 *      kurse werden die inhaltlich aehnlichsten, bereits erschlossenen und
 *      freigegebenen/dauerhaften Kurse ermittelt (MATCH..AGAINST mit dem
 *      Titel+Beschreibungstext des aktuellen Kurses als Anfrage). Deren
 *      Themen/Stichwoerter werden - gewichtet nach Aehnlichkeit - als
 *      Vorschlaege eingesammelt ("Erschliessung der Nachbarn erben").
 *
 *   B) Text-Treffer:
 *      Stichwoerter, deren Name woertlich (mit Wortgrenzen) im Titel oder
 *      der Beschreibung des aktuellen Kurses vorkommt (min. 4 Zeichen).
 *
 * Parameter:
 *   - id : Kurs-ID, fuer die Vorschlaege gesucht werden
 *
 * Rueckgabe (UTF-8 JSON):
 *   {
 *     success: true,
 *     id: <kursId>,
 *     aehnliche:    [ { id, titel, prozent }, ... ]              (max. 5, fuer Herkunftszeile)
 *     themen:       [ { id, kuerzel, name, quellen:[kursIds] }, ... ]   (max. 3)
 *     stichwoerter: [ { id, name, actype, quellen:[kursIds], imtext: bool }, ... ]  (max. 20)
 *   }
 *
 * Hinweise:
 *   - Bereits am Kurs gesetzte Stichwoerter / das gesetzte Thema werden nicht
 *     erneut vorgeschlagen.
 *   - Fehlt der Volltext-Index (aeltere Installation), faellt der Endpoint
 *     still auf Quelle B zurueck (keine Fehlermeldung, nur weniger Vorschlaege).
 *   - Ein spaeterer Umstieg der Nachbarsuche auf Embedding-Vektoren ist geplant, 
*	   ohne dass sich Rückgabeformat oder Maske aendern muessen.
 */

require_once('functions.inc.php');

header('Content-Type: application/json; charset=utf-8');

function vorschlaege_fail($message, $status = 400)
{
    http_response_code($status);
    echo json_encode(array('success' => false, 'message' => $message));
    exit;
}

$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
if ($id <= 0) {
    vorschlaege_fail('Ungueltige Kurs-ID.');
}

// Anzeige der Vorschlaege-Sektion ist per Benutzereinstellung abschaltbar (Standard: an).
// Vgl. Einstellungen -> Ansicht (settings.php?table=kurse&scope=edit).
if (intval(regGet('edit.kurse.showvorschlaege', 1)) === 0) {
    echo json_encode(array('success' => true, 'id' => $id, 'disabled' => true,
        'aehnliche' => array(), 'themen' => array(), 'stichwoerter' => array()));
    exit;
}

$db = new DB_Admin();

// Begrenzungen (bewusst konservativ, damit der AJAX-Aufruf leicht bleibt)
$NEIGHBOR_LIMIT   = 12;    // wie viele aehnlichste Kurse als Erschliessungs-Quelle dienen
$SIMILAR_SHOWN    = 5;     // wie viele davon in der Herkunftszeile angezeigt werden
$MAX_SW_SUGGEST   = 20;    // max. Stichwort-Vorschlaege
$MAX_THEMA_SUGGEST= 3;     // max. Themen-Vorschlaege
$QUERYTEXT_MAX    = 4000;  // Zeichen des eigenen Texts fuer die MATCH-Anfrage
$MATCHTEXT_MAX    = 20000; // Zeichen des eigenen Texts fuer die Wort-Treffersuche
$MIN_SW_LEN       = 4;     // min. Laenge eines Stichworts fuer die Wort-Treffersuche

// ------------------------------------------------------------------
// 0) Aktuellen Kurs + dessen vorhandene Erschliessung laden
// ------------------------------------------------------------------
$db->query("SELECT titel, beschreibung, thema FROM kurse WHERE id=" . $id);
if (!$db->next_record()) {
    vorschlaege_fail('Kurs nicht gefunden.', 404);
}
$curThema  = intval($db->f('thema'));
$curTitel  = strval($db->fs('titel'));                                   // latin1 (Roh-Kodierung der DB)
$curBeschr = strval($db->fs('beschreibung'));
$curBeschr = html_entity_decode(strip_tags($curBeschr), ENT_QUOTES, 'ISO-8859-1');

$ownText = trim($curTitel . ' ' . $curBeschr);

$haveSw = array();  // bereits gesetzte Stichwoerter -> nicht erneut vorschlagen
$db->query("SELECT attr_id FROM kurse_stichwort WHERE primary_id=" . $id);
while ($db->next_record()) {
    $haveSw[intval($db->f('attr_id'))] = true;
}

// ------------------------------------------------------------------
// 1) Quelle A: aehnlichste erschlossene Kurse ueber den Volltext-Index
// ------------------------------------------------------------------
// Volltext-Index vorhanden? (graceful degradation fuer Alt-Installationen,
// siehe db.sql: ALTER TABLE kurse ADD FULLTEXT KEY titel_beschreibung ...)
$hasFulltext = false;
$db->query("SHOW INDEX FROM kurse");
while ($db->next_record()) {
    if ($db->f('Key_name') === 'titel_beschreibung') {
        $hasFulltext = true;
    }
}

$neighbors = array();   // kursId => array(titel, thema, score)
if ($hasFulltext && $ownText !== '') {
    $queryText = substr($ownText, 0, $QUERYTEXT_MAX);
    $q = $db->quote($queryText);
    // Natural-Language-Modus = klassisches "more like this": haeufige Woerter
    // (in >50% der Kurse) und sehr kurze Woerter ignoriert MySQL von selbst.
    // Nur freigegebene/dauerhafte Kurse, die selbst Erschliessung besitzen,
    // dienen als Quelle (sonst wuerden z.B. frisch importierte, noch nicht
    // erschlossene Schwester-Kurse die vorderen Plaetze belegen).
    $db->query(
        "SELECT k.id, k.titel, k.thema, MATCH(k.titel, k.beschreibung) AGAINST(" . $q . ") AS score "
        . "FROM kurse k "
        . "WHERE MATCH(k.titel, k.beschreibung) AGAINST(" . $q . ") "
        . "AND k.id<>" . $id . " "
        . "AND k.freigeschaltet IN (1,4) "
        . "AND (k.thema>0 OR EXISTS(SELECT 1 FROM kurse_stichwort ks WHERE ks.primary_id=k.id)) "
        . "ORDER BY score DESC "
        . "LIMIT " . intval($NEIGHBOR_LIMIT)
    );
    while ($db->next_record()) {
        $neighbors[intval($db->f('id'))] = array(
            'titel' => $db->f8('titel'),
            'thema' => intval($db->f('thema')),
            'score' => floatval($db->f('score')),
        );
    }
}

// Stichwoerter der Nachbarn laden
$neighborSw = array();  // kursId => array( array(id, name, actype), ... )
if (count($neighbors) > 0) {
    $inList = implode(',', array_map('intval', array_keys($neighbors)));
    $db->query(
        "SELECT ks.primary_id, ks.attr_id, s.stichwort, s.eigenschaften "
        . "FROM kurse_stichwort ks LEFT JOIN stichwoerter s ON s.id = ks.attr_id "
        . "WHERE ks.primary_id IN (" . $inList . ") "
        . "ORDER BY ks.primary_id, ks.structure_pos"
    );
    while ($db->next_record()) {
        $cid  = intval($db->f('primary_id'));
        $swId = intval($db->f('attr_id'));
        $name = $db->f8('stichwort');
        if ($swId <= 0 || $name === null || $name === '') {
            continue;   // verwaiste Zuordnung
        }
        $neighborSw[$cid][] = array(
            'id'     => $swId,
            'name'   => $name,
            'actype' => $db->f('eigenschaften') !== null ? (string)$db->f('eigenschaften') : '',
        );
    }
}

// Nachbarn nach Aehnlichkeit gewichten und deren Erschliessung einsammeln
$maxScore   = 0.0;
foreach ($neighbors as $n) {
    if ($n['score'] > $maxScore) { $maxScore = $n['score']; }
}
$swSuggest    = array();  // swId => array(name, actype, score, quellen[], imtext)
$themaSuggest = array();  // themaId => array(score, quellen[])
foreach ($neighbors as $cid => $n) {
    $w = ($maxScore > 0) ? ($n['score'] / $maxScore) : 0;   // 0..1
    if ($n['thema'] > 0 && $n['thema'] !== $curThema) {
        if (!isset($themaSuggest[$n['thema']])) {
            $themaSuggest[$n['thema']] = array('score' => 0.0, 'quellen' => array());
        }
        $themaSuggest[$n['thema']]['score'] += $w;
        $themaSuggest[$n['thema']]['quellen'][] = $cid;
    }
    if (isset($neighborSw[$cid])) {
        foreach ($neighborSw[$cid] as $sw) {
            if (isset($haveSw[$sw['id']])) {
                continue;   // hat der Kurs schon
            }
            if (!isset($swSuggest[$sw['id']])) {
                $swSuggest[$sw['id']] = array(
                    'name' => $sw['name'], 'actype' => $sw['actype'],
                    'score' => 0.0, 'quellen' => array(), 'imtext' => false,
                );
            }
            $swSuggest[$sw['id']]['score'] += $w;
            $swSuggest[$sw['id']]['quellen'][] = $cid;
        }
    }
}

// ------------------------------------------------------------------
// 2) Quelle B: Stichwort-Namen, die woertlich im Kurstext vorkommen
// ------------------------------------------------------------------
$textHits = array();   // swId => array(name, actype)
if ($ownText !== '') {
    $matchText = ' ' . mb_strtolower(substr($ownText, 0, $MATCHTEXT_MAX), 'ISO-8859-1') . ' ';
    $db->query(
        "SELECT id, stichwort, eigenschaften FROM stichwoerter "
        . "WHERE CHAR_LENGTH(stichwort) >= " . intval($MIN_SW_LEN)
    );
    while ($db->next_record()) {
        $swId = intval($db->f('id'));
        if ($swId <= 0 || isset($haveSw[$swId]) || isset($textHits[$swId])) {
            continue;
        }
        $needle = mb_strtolower(strval($db->fs('stichwort')), 'ISO-8859-1');
        if ($needle === '' || strpos($matchText, $needle) === false) {
            continue;   // billiger Vorfilter
        }
        // Wortgrenzen pruefen (latin1: Kleinbuchstaben a-z + 0xDF-0xFF fuer Umlaute/sz),
        // damit z.B. "Bau" nicht in "Baum" trifft. Mehrwort-Stichwoerter funktionieren
        // dabei automatisch mit ("soziale arbeit").
        $pattern = '/(?<![a-z0-9\xdf-\xff])' . preg_quote($needle, '/') . '(?![a-z0-9\xdf-\xff])/';
        if (!preg_match($pattern, $matchText)) {
            continue;
        }
        $textHits[$swId] = array(
            'name'   => $db->f8('stichwort'),
            'actype' => $db->f('eigenschaften') !== null ? (string)$db->f('eigenschaften') : '',
        );
    }
}

// Nutzungs-Haeufigkeit der reinen Text-Treffer (fuer deren Ranking): ein im
// Bestand oft vergebenes Stichwort ist als Vorschlag verlaesslicher als ein
// exotisches.
$usage = array();
$textOnlyIds = array();
foreach ($textHits as $swId => $hit) {
    if (!isset($swSuggest[$swId])) {
        $textOnlyIds[] = $swId;
    }
}
if (count($textOnlyIds) > 0) {
    $db->query(
        "SELECT attr_id, COUNT(*) AS cnt FROM kurse_stichwort "
        . "WHERE attr_id IN (" . implode(',', array_map('intval', $textOnlyIds)) . ") "
        . "GROUP BY attr_id"
    );
    while ($db->next_record()) {
        $usage[intval($db->f('attr_id'))] = intval($db->f('cnt'));
    }
}

// ------------------------------------------------------------------
// 3) Quellen zusammenfuehren und ranken
// ------------------------------------------------------------------
foreach ($textHits as $swId => $hit) {
    if (isset($swSuggest[$swId])) {
        // kommt aus Nachbarn UND im Text vor -> deutlicher Bonus
        $swSuggest[$swId]['score'] += 0.75;
        $swSuggest[$swId]['imtext'] = true;
    } else {
        // reiner Text-Treffer: Grundwert + Nutzungs-Haeufigkeit als Feinsortierung
        $cnt = isset($usage[$swId]) ? $usage[$swId] : 0;
        $swSuggest[$swId] = array(
            'name' => $hit['name'], 'actype' => $hit['actype'],
            'score' => 0.5 + (min($cnt, 40) / 100.0),
            'quellen' => array(), 'imtext' => true,
        );
    }
}

uasort($swSuggest, function ($a, $b) {
    if ($a['score'] === $b['score']) { return strcmp($a['name'], $b['name']); }
    return ($a['score'] < $b['score']) ? 1 : -1;
});
$swOut = array();
foreach ($swSuggest as $swId => $s) {
    $swOut[] = array(
        'id'      => $swId,
        'name'    => $s['name'],
        'actype'  => $s['actype'],
        'quellen' => array_slice(array_values(array_unique($s['quellen'])), 0, 5),
        'imtext'  => $s['imtext'],
    );
    if (count($swOut) >= $MAX_SW_SUGGEST) { break; }
}

// Themen-Vorschlaege: Namen laden + nach Gewicht sortieren
$themaOut = array();
if (count($themaSuggest) > 0) {
    uasort($themaSuggest, function ($a, $b) {
        if ($a['score'] === $b['score']) { return 0; }
        return ($a['score'] < $b['score']) ? 1 : -1;
    });
    $themaIds = array_slice(array_keys($themaSuggest), 0, $MAX_THEMA_SUGGEST);
    $themaNames = array();
    $db->query(
        "SELECT id, kuerzel, thema FROM themen WHERE id IN (" . implode(',', array_map('intval', $themaIds)) . ")"
    );
    while ($db->next_record()) {
        $themaNames[intval($db->f('id'))] = array(
            'kuerzel' => $db->f8('kuerzel'),
            'name'    => $db->f8('thema'),
        );
    }
    foreach ($themaIds as $tid) {
        if (!isset($themaNames[$tid])) {
            continue;   // Thema zwischenzeitlich geloescht
        }
        $themaOut[] = array(
            'id'      => $tid,
            'kuerzel' => $themaNames[$tid]['kuerzel'],
            'name'    => $themaNames[$tid]['name'],
            'quellen' => array_slice(array_values(array_unique($themaSuggest[$tid]['quellen'])), 0, 5),
        );
    }
}

// Herkunftszeile: die aehnlichsten Kurse mit relativer Aehnlichkeit in Prozent
$similarOut = array();
foreach ($neighbors as $cid => $n) {
    $similarOut[] = array(
        'id'      => $cid,
        'titel'   => $n['titel'],
        'prozent' => ($maxScore > 0) ? intval(round(100.0 * $n['score'] / $maxScore)) : 0,
    );
    if (count($similarOut) >= $SIMILAR_SHOWN) { break; }
}

echo json_encode(array(
    'success'      => true,
    'id'           => $id,
    'aehnliche'    => $similarOut,
    'themen'       => $themaOut,
    'stichwoerter' => $swOut,
));
