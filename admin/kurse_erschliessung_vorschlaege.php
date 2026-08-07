<?php

/**
 * Liefert zu einer Kurs-ID statistisch ermittelte Erschliessungs-Vorschlaege
 * (Themen + Stichwoerter) als JSON - OHNE Aufruf eines KI-Modells.
 *
 * Wird von der Kurs-Edit-Maske (admin/functions.js) per AJAX aufgerufen, um
 * dort eine "Erschliessungsvorschlaege"-Sektion anzuzeigen. Die Uebernahme
 * erfolgt dort clientseitig per attr_add (wirksam erst beim Speichern!).
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
 *     stichwoerter: [ { id, name, actype, quellen:[kursIds], imtext: bool,
 *                       dim: {grund}|null, dim_wenn: [ {sw:[], thema:[], ausser:[], grund}, ... ] }, ... ]  (max. 20)
 *     regeln:       { aktiv: bool, max_sach: {limit, ids:[], grund}|null }   (nur wenn Regeldatei aktiv)
 *   }
 *
 * Hinweise:
 *   - Bereits am Kurs gesetzte Stichwoerter / das gesetzte Thema werden nicht
 *     erneut vorgeschlagen.
 *   - Text-Treffer auf Synonyme/versteckte Synonyme werden ueber
 *     stichwoerter_verweis auf ihren Deskriptor aufgeloest (an Kursen sollen
 *     nur Deskriptoren haengen, Synonyme deckt die Frontend-Suche ohnehin ab).
 *   - Fehlt der Volltext-Index (aeltere Installation), faellt der Endpoint
 *     still auf Quelle B zurueck (keine Fehlermeldung, nur weniger Vorschlaege).
 *
 * Redaktionsregeln (optional):
 *   Liegt die separate Konfigurationsdatei config/erschliessung_regeln.inc.php
 *   vor (Vorlage: erschliessung_regeln.inc.php-example) UND ist die
 *   Benutzereinstellung "Redaktionsregeln anwenden" aktiv, werden Vorschlaege
 *   zusaetzlich bewertet: fachlich vermutlich unpassende Vorschlaege erhalten
 *   ein "dim"-Feld (mit Begruendung) bzw. "dim_wenn"-Bedingungen, die die
 *   Maske live gegen die aktuell im Formular gesetzte Erschliessung prueft.
 *   Die Mechanik hier ist bewusst generisch; saemtliche konkreten Regeln,
 *   IDs, Muster und Begruendungstexte liegen ausschliesslich in der (nicht
 *   veroeffentlichten) Regeldatei "lokal"/auf dem Server vor - wenn überhauot. 
 *   Fehlt die Datei, aendert sich nichts an Verhalten und Ausgabeformat (die Zusatzfelder entfallen).
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

    // Treffer auf Synonyme (64) / versteckte Synonyme (32) auf ihren Deskriptor
    // aufloesen: an Kursen sollen nur Deskriptoren haengen (Synonyme findet die
    // Frontend-Suche von selbst). stichwoerter_verweis: primary_id = Synonym,
    // attr_id = Deskriptor.
    $synIds = array();
    foreach ($textHits as $swId => $hit) {
        $eig = intval($hit['actype']);
        if ($eig === 32 || $eig === 64) {
            $synIds[] = $swId;
        }
    }
    if (count($synIds) > 0) {
        $synTarget = array();   // synonymId => array(id, name, actype) des Deskriptors
        $db->query(
            "SELECT v.primary_id, s.id, s.stichwort, s.eigenschaften "
            . "FROM stichwoerter_verweis v LEFT JOIN stichwoerter s ON s.id = v.attr_id "
            . "WHERE v.primary_id IN (" . implode(',', array_map('intval', $synIds)) . ") "
            . "ORDER BY v.primary_id, v.structure_pos"
        );
        while ($db->next_record()) {
            $pid = intval($db->f('primary_id'));
            $did = intval($db->f('id'));
            if ($did <= 0 || isset($synTarget[$pid])) {
                continue;   // verwaist bzw. nur den ersten Deskriptor verwenden
            }
            $synTarget[$pid] = array(
                'id'     => $did,
                'name'   => $db->f8('stichwort'),
                'actype' => $db->f('eigenschaften') !== null ? (string)$db->f('eigenschaften') : '',
            );
        }
        foreach ($synIds as $swId) {
            unset($textHits[$swId]);
            if (isset($synTarget[$swId])) {
                $t = $synTarget[$swId];
                if (!isset($haveSw[$t['id']]) && !isset($textHits[$t['id']])) {
                    $textHits[$t['id']] = array('name' => $t['name'], 'actype' => $t['actype']);
                }
            }
            // ohne Deskriptor: Treffer verwerfen (ein Synonym direkt zu
            // verknuepfen waere fachlich falsch)
        }
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

// ------------------------------------------------------------------
// 3b) Optionale Redaktionsregeln: Vorschlaege bewerten ("dimmen")
// ------------------------------------------------------------------
// Die Engine hier ist generisch (Regel-TYPEN); die konkreten Regeln - IDs,
// Muster, Grenzwerte, Begruendungen - kommen ausschliesslich aus der separaten,
// nicht veroeffentlichten Datei config/erschliessung_regeln.inc.php (Struktur:
// s. erschliessung_regeln.inc.php-example). Ergebnis pro Vorschlag:
//   dim      = { grund } : serverseitig fest ausgegraut
//   dim_wenn = [ { sw:[ids], thema:[ids], ausser:[ids], grund }, ... ] :
//              von der Maske LIVE gegen die aktuell im Formular gesetzte
//              Erschliessung geprueft (greift/entfaellt also auch nach einer
//              Uebernahme, ohne weiteren Serveraufruf).
// Die Vorschlaege bleiben in jedem Fall anklickbar/uebernehmbar.

// Texte aus der Regeldatei (latin1, wie alle admin-Dateien) fuer die
// UTF-8-JSON-Ausgabe konvertieren; bereits gueltiges UTF-8 bleibt unveraendert.
function vorschlaege_utf8($s)
{
    $s = strval($s);
    if ($s === '' || mb_check_encoding($s, 'UTF-8')) {
        return $s;
    }
    return mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
}

$regelwerk = null;
if (intval(regGet('edit.kurse.showvorschlaege.regeln', 1)) === 1) {
    $regelDatei = dirname(__FILE__) . '/config/erschliessung_regeln.inc.php';
    if (@file_exists($regelDatei)) {
        include($regelDatei);   // definiert $erschliessung_regeln
        if (isset($erschliessung_regeln) && is_array($erschliessung_regeln)) {
            $regelwerk = $erschliessung_regeln;
        }
    }
}

$regelnOut = null;
if ($regelwerk !== null && count($swOut) > 0) {
    // Stammdaten (Typ, Name, Scope Note) fuer Vorschlaege + bereits am Kurs
    // gesetzte Stichwoerter in einem Rutsch laden ("Universum" der Regeln)
    $uniIds = array_keys($haveSw);
    foreach ($swOut as $s) { $uniIds[] = $s['id']; }
    $uniIds = array_values(array_unique(array_map('intval', $uniIds)));
    $uniEig = array(); $uniName = array(); $uniScope = array();
    if (count($uniIds) > 0) {
        $db->query(
            "SELECT id, stichwort, eigenschaften, scope_note FROM stichwoerter "
            . "WHERE id IN (" . implode(',', $uniIds) . ")"
        );
        while ($db->next_record()) {
            $i = intval($db->f('id'));
            $uniEig[$i]   = intval($db->f('eigenschaften'));
            $uniName[$i]  = strval($db->fs('stichwort'));       // roh (latin1)
            $uniScope[$i] = strval($db->fs('scope_note'));      // roh (latin1)
        }
    }

    // Unterbegriffe der Vorschlaege ermitteln (transitiv, stichwoerter_verweis2:
    // primary_id = Oberbegriff, attr_id = Unterbegriff).
    
    $descMap = array();     // vorschlagId => array(unterbegriffIds)
    $obRegel = isset($regelwerk['oberbegriff']) && is_array($regelwerk['oberbegriff'])
        && !empty($regelwerk['oberbegriff']['aktiv']) ? $regelwerk['oberbegriff'] : null;
    if ($obRegel !== null) {
        $DESC_MAX_DEPTH = isset($obRegel['max_tiefe']) ? intval($obRegel['max_tiefe']) : 6;
        $DESC_MAX_PER   = isset($obRegel['max_unterbegriffe']) ? intval($obRegel['max_unterbegriffe']) : 300;
        $frontier = array();    // id => array(rootIds)
        foreach ($swOut as $s) {
            $descMap[$s['id']] = array();
            $frontier[$s['id']][] = $s['id'];
        }
        for ($depth = 0; $depth < $DESC_MAX_DEPTH && count($frontier) > 0; $depth++) {
            $db->query(
                "SELECT primary_id, attr_id FROM stichwoerter_verweis2 "
                . "WHERE primary_id IN (" . implode(',', array_map('intval', array_keys($frontier))) . ")"
            );
            $edges = array();
            while ($db->next_record()) {
                $edges[] = array(intval($db->f('primary_id')), intval($db->f('attr_id')));
            }
            $next = array();
            foreach ($edges as $e) {
                if (!isset($frontier[$e[0]])) { continue; }
                foreach ($frontier[$e[0]] as $root) {
                    if ($e[1] === $root || isset($descMap[$root][$e[1]])) { continue; }
                    if (count($descMap[$root]) >= $DESC_MAX_PER) { continue; }
                    $descMap[$root][$e[1]] = true;
                    $next[$e[1]][] = $root;
                }
            }
            $frontier = $next;
        }
    }

    // Alle Abschluss-Stichwoerter (Typ 1), abzueglich konfigurierter Namens-Ausnahmen. Live-Abgleich erfolgt clientseitig.
    $abRegel = isset($regelwerk['abschluss_ohne_sachstichwort']) && is_array($regelwerk['abschluss_ohne_sachstichwort'])
        ? $regelwerk['abschluss_ohne_sachstichwort'] : null;
    $abTrigger = array();
    if ($abRegel !== null) {
        foreach ($uniEig as $i => $eig) {
            if ($eig !== 1) { continue; }
            if (!empty($abRegel['ausnahme_muster'])
             && @preg_match($abRegel['ausnahme_muster'], $uniName[$i])) {
                continue;
            }
            $abTrigger[] = $i;
        }
    }

    // Regeln pro Vorschlag anwenden
    foreach ($swOut as $k => $s) {
        $eig = intval($s['actype']);
        $dimGrund = null;
        $dimWenn  = array();

        // Typ-Regel: bestimmte Stichwort-Typen generell dimmen
        if (isset($regelwerk['typ_dim'][$eig])) {
            $dimGrund = vorschlaege_utf8($regelwerk['typ_dim'][$eig]);
        }

        // Paar-Regeln: einzelne Stichwoerter (oder ganze Typen) dimmen -
        // unbedingt oder abhaengig von anderen gesetzten Stichwoertern/Themen
        if (isset($regelwerk['paare']) && is_array($regelwerk['paare'])) {
            foreach ($regelwerk['paare'] as $p) {
                if (!is_array($p)) { continue; }
                $matches = (isset($p['dim_sw']) && intval($p['dim_sw']) === $s['id'])
                        || (isset($p['dim_typ']) && intval($p['dim_typ']) === $eig);
                if (!$matches) { continue; }
                $grund = vorschlaege_utf8(isset($p['grund']) ? $p['grund'] : '');
                $hatBedingung = !empty($p['wenn_sw']) || !empty($p['wenn_thema']);
                if (!$hatBedingung) {
                    if ($dimGrund === null) { $dimGrund = $grund; }
                } else {
                    $dimWenn[] = array(
                        'sw'     => !empty($p['wenn_sw']) ? array_map('intval', (array)$p['wenn_sw']) : array(),
                        'thema'  => !empty($p['wenn_thema']) ? array_map('intval', (array)$p['wenn_thema']) : array(),
                        'ausser' => !empty($p['ausser_sw']) ? array_map('intval', (array)$p['ausser_sw']) : array(),
                        'grund'  => $grund,
                    );
                }
            }
        }

        // Scope-Note-Muster: Hinweise der Redaktion in der Scope Note
        // (Regex auf den DB-Rohtext; Muster kommen aus der Regeldatei)
        if ($dimGrund === null && isset($regelwerk['scope_note_muster']) && is_array($regelwerk['scope_note_muster'])
         && isset($uniScope[$s['id']]) && $uniScope[$s['id']] !== '') {
            foreach ($regelwerk['scope_note_muster'] as $m) {
                if (!is_array($m) || empty($m['muster'])) { continue; }
                $hit = array();
                if (@preg_match($m['muster'], $uniScope[$s['id']], $hit)) {
                    $dimGrund = vorschlaege_utf8(isset($m['grund']) ? $m['grund'] : '');
                    if (!empty($m['zitat']) && isset($hit[0])) {
                        // kurzen Kontext um die Fundstelle mitgeben (nur Redaktionsansicht)
                        $pos = strpos($uniScope[$s['id']], $hit[0]);
                        $zitat = trim(substr($uniScope[$s['id']], max(0, $pos - 40), strlen($hit[0]) + 100));
                        $dimGrund .= ' | Scope Note: "...' . vorschlaege_utf8($zitat) . '..."';
                    }
                    break;
                }
            }
        }

        // Herkunfts-Regel: bestimmte Stichwort-Typen nur dann ungedimmt vorschlagen, wenn sie im Kurstext belegt sind:
        // " von Nachbar-Kursen "geerbt" " ist fuer diese Typen zu unsicher.
        if ($dimGrund === null && isset($regelwerk['quelle_ohne_textbeleg']['typen'])
         && !$s['imtext']
         && in_array($eig, array_map('intval', (array)$regelwerk['quelle_ohne_textbeleg']['typen']), true)) {
            $dimGrund = vorschlaege_utf8(isset($regelwerk['quelle_ohne_textbeleg']['grund']) ? $regelwerk['quelle_ohne_textbeleg']['grund'] : '');
        }

        // Frei definierbare Zusatzbewertung aus der Regeldatei (Callable)
        if ($dimGrund === null && isset($regelwerk['extra_bewertung']) && is_callable($regelwerk['extra_bewertung'])) {
            $g = call_user_func($regelwerk['extra_bewertung'], array(
                'kurs_id' => $id, 'titel' => $curTitel, 'beschreibung' => $curBeschr,
                'thema' => $curThema, 'gesetzte_sw' => array_keys($haveSw),
            ), $s);
            if (is_string($g) && $g !== '') { $dimGrund = vorschlaege_utf8($g); }
        }

        // Oberbegriff-Regel: dimmen, sobald ein (transitiver) Unterbegriff des Vorschlags im Formular gesetzt ist
        if ($obRegel !== null && !empty($descMap[$s['id']])) {
            $dimWenn[] = array(
                'sw'     => array_map('intval', array_keys($descMap[$s['id']])),
                'thema'  => array(),
                'ausser' => array(),
                'grund'  => vorschlaege_utf8(isset($obRegel['grund']) ? $obRegel['grund'] : ''),
            );
        }

        // Abschluss-Regel: Sachstichwoerter (Typ 0) dimmen, sobald ein Abschluss-Stichwort gesetzt ist (mit konfigurierbaren Ausnahmen)
        if ($abRegel !== null && $eig === 0 && count($abTrigger) > 0) {
            $dimWenn[] = array(
                'sw'     => $abTrigger,
                'thema'  => array(),
                'ausser' => !empty($abRegel['aussetzen_wenn_sw']) ? array_map('intval', (array)$abRegel['aussetzen_wenn_sw']) : array(),
                'grund'  => vorschlaege_utf8(isset($abRegel['grund']) ? $abRegel['grund'] : ''),
            );
        }

        $swOut[$k]['dim']      = ($dimGrund !== null) ? array('grund' => $dimGrund) : null;
        $swOut[$k]['dim_wenn'] = $dimWenn;
    }

    // Mengen-Regel: ab N gesetzten Sachstichwoertern weitere Sachstichwort-Vorschlaege dimmen (Zaehlung erfolgt clientseitig, live)
    $maxSach = null;
    if (isset($regelwerk['max_sachstichwoerter']['limit'])) {
        $sachIds = array();
        foreach ($uniEig as $i => $eig) {
            if ($eig === 0) { $sachIds[] = $i; }
        }
        $maxSach = array(
            'limit' => intval($regelwerk['max_sachstichwoerter']['limit']),
            'ids'   => $sachIds,
            'grund' => vorschlaege_utf8(isset($regelwerk['max_sachstichwoerter']['grund']) ? $regelwerk['max_sachstichwoerter']['grund'] : ''),
        );
    }

    $regelnOut = array('aktiv' => true, 'max_sach' => $maxSach);
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

$out = array(
    'success'      => true,
    'id'           => $id,
    'aehnliche'    => $similarOut,
    'themen'       => $themaOut,
    'stichwoerter' => $swOut,
);
if ($regelnOut !== null) {
    $out['regeln'] = $regelnOut;
}
echo json_encode($out);
