<?php

/**
 * Maintenance-Skript fuer kurse_duplikate:
 * - aktualisiert lokale Kontextfelder anhand aktueller Kursdaten
 *   (kurse_titel1/2, anbieter_name1/2, kurse_beschreibung1/2, kurse_erschliessung1/2)
 * - vergleicht Thema + Stichwoerter je Paar und setzt erschliessung_gleich (1/0)
 *
 * Aufruf:
 *   php admin/kurse_duplikate_maintenance.php
 *   php admin/kurse_duplikate_maintenance.php --id1=123
 *   php admin/kurse_duplikate_maintenance.php --id1=123 --id2=456
 * oder per Web (intern/cron):
 *   /admin/kurse_duplikate_maintenance.php
 *   /admin/kurse_duplikate_maintenance.php?id1=123
 *   /admin/kurse_duplikate_maintenance.php?id1=123&id2=456
 */

define('G_SKIP_LOGIN', 1);
require_once('functions.inc.php');

@set_time_limit(0);

function chunked_ids($ids, $chunkSize = 500)
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    $ids = array_filter($ids, function($v) { return $v > 0; });
    return array_chunk($ids, $chunkSize);
}

function sql_in_list($ids)
{
    return implode(',', array_map('intval', $ids));
}

function build_erschliessung_text($courseId, $courseThemaIdMap, $themenNameMap, $keywordMap)
{
    $parts = array();

    if (isset($keywordMap[$courseId]) && is_array($keywordMap[$courseId])) {
        $keywords = $keywordMap[$courseId];
        sort($keywords, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($keywords as $kw) {
            if ($kw !== '') {
                $parts[] = $kw;
            }
        }
    }

    $themaId = isset($courseThemaIdMap[$courseId]) ? intval($courseThemaIdMap[$courseId]) : 0;
    if ($themaId > 0 && isset($themenNameMap[$themaId]) && $themenNameMap[$themaId] !== '') {
        $parts[] = "Thema: " . $themenNameMap[$themaId];
    }

    return implode(', ', $parts);
}

function is_same_erschliessung($id1, $id2, $courseThemaIdMap, $keywordIdSetMap)
{
    $thema1 = isset($courseThemaIdMap[$id1]) ? intval($courseThemaIdMap[$id1]) : 0;
    $thema2 = isset($courseThemaIdMap[$id2]) ? intval($courseThemaIdMap[$id2]) : 0;
    $sameThema = ($thema1 === $thema2);

    $set1 = isset($keywordIdSetMap[$id1]) ? $keywordIdSetMap[$id1] : array();
    $set2 = isset($keywordIdSetMap[$id2]) ? $keywordIdSetMap[$id2] : array();

    if (count($set1) !== count($set2)) {
        return false;
    }
    foreach ($set1 as $kwId => $_) {
        if (!isset($set2[$kwId])) {
            return false;
        }
    }

    return $sameThema;
}

function get_request_or_cli_value($key)
{
    if (isset($_REQUEST[$key])) {
        return $_REQUEST[$key];
    }

    if (PHP_SAPI === 'cli' && isset($GLOBALS['argv']) && is_array($GLOBALS['argv'])) {
        foreach ($GLOBALS['argv'] as $arg) {
            if (strpos($arg, '--' . $key . '=') === 0) {
                return substr($arg, strlen('--' . $key . '='));
            }
        }
    }

    return null;
}

function kurse_duplikate_maintenance_run($options = array())
{
    $db = new DB_Admin();

    $id1 = isset($options['id1']) ? intval($options['id1']) : intval(get_request_or_cli_value('id1'));
    $id2 = isset($options['id2']) ? intval($options['id2']) : intval(get_request_or_cli_value('id2'));

    $pairFilterSql = '';
    if ($id1 > 0 && $id2 > 0) {
        $pairFilterSql = " WHERE ((kurse_id1=" . $id1 . " AND kurse_id2=" . $id2 . ") OR (kurse_id1=" . $id2 . " AND kurse_id2=" . $id1 . "))";
    }
    else if ($id1 > 0) {
        $pairFilterSql = " WHERE (kurse_id1=" . $id1 . " OR kurse_id2=" . $id1 . ")";
    }
    else if ($id2 > 0) {
        $pairFilterSql = " WHERE (kurse_id1=" . $id2 . " OR kurse_id2=" . $id2 . ")";
    }

    // 1) Alle relevanten Duplikatpaare lesen
    $pairs = array();
    $courseIds = array();
    $db->query("SELECT id, kurse_id1, kurse_id2 FROM kurse_duplikate" . $pairFilterSql);
    while ($db->next_record()) {
        $rowId = intval($db->f('id'));
        $courseId1 = intval($db->f('kurse_id1'));
        $courseId2 = intval($db->f('kurse_id2'));
        $pairs[] = array('id' => $rowId, 'id1' => $courseId1, 'id2' => $courseId2);
        if ($courseId1 > 0) { $courseIds[] = $courseId1; }
        if ($courseId2 > 0) { $courseIds[] = $courseId2; }
    }

    if (count($pairs) === 0) {
        return array('updated' => 0, 'deleted' => 0);
    }

    // 2) Themennamen laden
    $themenNameMap = array();
    $db->query("SELECT id, thema FROM themen");
    while ($db->next_record()) {
        $themenNameMap[intval($db->f('id'))] = trim((string)$db->f('thema'));
    }

    // 3) Kursdaten laden (Titel, Beschreibung, Thema, Anbietername, Status)
    $courseDataMap = array();
    $courseThemaIdMap = array();
    foreach (chunked_ids($courseIds) as $chunk) {
        $db->query(
            "SELECT k.id, k.titel, k.beschreibung, k.thema, k.freigeschaltet, a.suchname AS anbieter_name "
            . "FROM kurse k "
            . "LEFT JOIN anbieter a ON a.id = k.anbieter "
            . "WHERE k.id IN (" . sql_in_list($chunk) . ")"
        );
        while ($db->next_record()) {
            $courseId = intval($db->f('id'));
            $courseDataMap[$courseId] = array(
                'titel' => (string)$db->f('titel'),
                'beschreibung' => (string)$db->f('beschreibung'),
                'anbieter_name' => (string)$db->f('anbieter_name'),
                'freigeschaltet' => intval($db->f('freigeschaltet')),
            );
            $courseThemaIdMap[$courseId] = intval($db->f('thema'));
        }
    }

    // 4) Stichwoerter je Kurs laden
    $keywordIdSetMap = array(); // courseId => [keywordId => true]
    $keywordNameMap = array();  // courseId => [keywordName, ...]
    foreach (chunked_ids($courseIds) as $chunk) {
        $db->query(
            "SELECT ks.primary_id, ks.attr_id, s.stichwort "
            . "FROM kurse_stichwort ks "
            . "LEFT JOIN stichwoerter s ON s.id = ks.attr_id "
            . "WHERE ks.primary_id IN (" . sql_in_list($chunk) . ")"
        );
        while ($db->next_record()) {
            $courseId = intval($db->f('primary_id'));
            $attrId = intval($db->f('attr_id'));
            $stichwort = trim((string)$db->f('stichwort'));

            if (!isset($keywordIdSetMap[$courseId])) {
                $keywordIdSetMap[$courseId] = array();
            }
            if (!isset($keywordNameMap[$courseId])) {
                $keywordNameMap[$courseId] = array();
            }

            $keywordIdSetMap[$courseId][$attrId] = true;
            if ($stichwort !== '') {
                $keywordNameMap[$courseId][$stichwort] = $stichwort;
            }
        }
    }

    foreach ($keywordNameMap as $courseId => $nameAssoc) {
        $keywordNameMap[$courseId] = array_values($nameAssoc);
    }

    // 5) Je Paar lokale Kontextfelder + erschliessung_gleich aktualisieren.
    //    Verwaiste Paare (mindestens ein Kurs existiert nicht mehr) werden geloescht,
    //    damit keine bedeutungslosen Vergleiche bestehen bleiben und keine Erschliessung
    //    auf nicht (mehr) existierende Kurse uebertragen werden kann.
    $updated = 0;
    $deleteRowIds = array();
    foreach ($pairs as $p) {
        $rowId = intval($p['id']);
        $courseId1 = intval($p['id1']);
        $courseId2 = intval($p['id2']);

        // courseDataMap enthaelt nur tatsaechlich existierende Kurse (beliebigen Status).
        $exists1 = ($courseId1 > 0 && isset($courseDataMap[$courseId1]));
        $exists2 = ($courseId2 > 0 && isset($courseDataMap[$courseId2]));
        if (!$exists1 || !$exists2) {
            $deleteRowIds[] = $rowId;
            continue;
        }

        $text1 = build_erschliessung_text($courseId1, $courseThemaIdMap, $themenNameMap, $keywordNameMap);
        $text2 = build_erschliessung_text($courseId2, $courseThemaIdMap, $themenNameMap, $keywordNameMap);
        $equal = is_same_erschliessung($courseId1, $courseId2, $courseThemaIdMap, $keywordIdSetMap) ? 1 : 0;

        $titel1 = isset($courseDataMap[$courseId1]) ? addslashes($courseDataMap[$courseId1]['titel']) : '';
        $beschreibung1 = isset($courseDataMap[$courseId1]) ? addslashes($courseDataMap[$courseId1]['beschreibung']) : '';
        $anbieterName1 = isset($courseDataMap[$courseId1]) ? addslashes($courseDataMap[$courseId1]['anbieter_name']) : '';
        // Status: -1, falls der Kurs nicht (mehr) existiert
        $freigeschaltet1 = isset($courseDataMap[$courseId1]) ? intval($courseDataMap[$courseId1]['freigeschaltet']) : -1;

        $titel2 = isset($courseDataMap[$courseId2]) ? addslashes($courseDataMap[$courseId2]['titel']) : '';
        $beschreibung2 = isset($courseDataMap[$courseId2]) ? addslashes($courseDataMap[$courseId2]['beschreibung']) : '';
        $anbieterName2 = isset($courseDataMap[$courseId2]) ? addslashes($courseDataMap[$courseId2]['anbieter_name']) : '';
        $freigeschaltet2 = isset($courseDataMap[$courseId2]) ? intval($courseDataMap[$courseId2]['freigeschaltet']) : -1;

        $text1Sql = addslashes($text1);
        $text2Sql = addslashes($text2);

        $db->query(
            "UPDATE kurse_duplikate SET "
            . "erschliessung_gleich=" . $equal . ", "
            . "kurse_titel1='" . $titel1 . "', "
            . "anbieter_name1='" . $anbieterName1 . "', "
            . "freigeschaltet1=" . $freigeschaltet1 . ", "
            . "kurse_beschreibung1='" . $beschreibung1 . "', "
            . "kurse_erschliessung1='" . $text1Sql . "', "
            . "kurse_titel2='" . $titel2 . "', "
            . "anbieter_name2='" . $anbieterName2 . "', "
            . "freigeschaltet2=" . $freigeschaltet2 . ", "
            . "kurse_beschreibung2='" . $beschreibung2 . "', "
            . "kurse_erschliessung2='" . $text2Sql . "', "
            . "date_modified=NOW() "
            . "WHERE id=" . $rowId
        );
        $updated++;
    }

    // Verwaiste Zeilen blockweise loeschen
    $deleted = 0;
    if (count($deleteRowIds) > 0) {
        foreach (array_chunk($deleteRowIds, 500) as $chunk) {
            $db->query("DELETE FROM kurse_duplikate WHERE id IN (" . sql_in_list($chunk) . ")");
        }
        $deleted = count($deleteRowIds);
    }

    return array('updated' => $updated, 'deleted' => $deleted);
}

if (!defined('KURSE_DUPLIKATE_MAINTENANCE_NO_AUTORUN')) {
    $result = kurse_duplikate_maintenance_run();
    $updated = intval($result['updated']);
    $deleted = intval($result['deleted']);
    if ($updated === 0 && $deleted === 0) {
        echo "Keine passenden Datensaetze in kurse_duplikate gefunden.\n";
    }
    else {
        echo "Fertig.<br>Aktualisierte Zeilen in Kurse-Duplikate-Tabelle: " . $updated . "\n";
        if ($deleted > 0) {
            echo "<br>Gel&ouml;schte verwaiste Zeilen (nicht mehr existierende Kurse): " . $deleted . "\n";
        }
    }
}
