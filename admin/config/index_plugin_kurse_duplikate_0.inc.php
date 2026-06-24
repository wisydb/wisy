<?php

/**
 * Plugin "Loeschen" (gefilterte Zeilen) fuer die Tabelle kurse_duplikate.
 *
 * Loescht genau diejenigen Zeilen, die durch den aktuellen Filter / die aktuelle
 * Suche in der Uebersicht ausgewaehlt sind. WISY legt das passende SELECT
 * dafuer unter $_SESSION['g_session_index_sql']['kurse_duplikate'] ab; das
 * verwenden u.a. auch print.php und das Paging.
 *
 * Zwei-Stufen-UI:
 *   - Erstaufruf zeigt Anzahl + Beschreibung
 *   - Nach Klick auf "Ja, jetzt loeschen" wird per POST + JS-confirm tatsaechlich
 *     geloescht.
 *
 * Hinweis: Wir muessen die Paare hier NICHT als "zur Neu-Pruefung markieren" -
 * sobald die Zeilen geloescht sind, taucht das Paar im naechsten KI-Lauf
 * wieder auf, sofern es weiterhin ein Duplikat-Kandidat ist.
 */

require_once('functions.inc.php');

@set_time_limit(0);

$tableName = 'kurse_duplikate';
$sessionSql = isset($_SESSION['g_session_index_sql'][$tableName])
    ? $_SESSION['g_session_index_sql'][$tableName]
    : '';

// IDs der aktuell gefilterten Zeilen einsammeln
$ids = array();
if ($sessionSql !== '') {
    $db = new DB_Admin();
    $db->query($sessionSql);
    while ($db->next_record()) {
        $rowId = intval($db->f('id'));
        if ($rowId > 0) {
            $ids[] = $rowId;
        }
    }
}
$cntBefore = count($ids);

$confirmed = isset($_POST['confirm_filtered_delete']) && $_POST['confirm_filtered_delete'] === '1';
$deleted   = 0;

if ($confirmed && $cntBefore > 0) {
    // In Bloecken loeschen, falls sehr viele IDs gefiltert wurden.
    $dbDel = new DB_Admin();
    foreach (array_chunk($ids, 500) as $chunk) {
        $dbDel->query(
            "DELETE FROM kurse_duplikate WHERE id IN (" . implode(',', $chunk) . ")"
        );
        $deleted += count($chunk);
    }
}

$site->pageStart(array('popfit' => 1));

$site->skin->submenuStart();
echo "Gefilterte Zeilen l&ouml;schen";
$site->skin->submenuBreak();
echo "&nbsp;";
$site->skin->submenuEnd();

$site->skin->workspaceStart();

if ($sessionSql === '') {
    echo "<p>Es wurden keine gefilterten Datens&auml;tze gefunden. ";
    echo "Bitte erst &uuml;ber die <b>Suche</b> die gew&uuml;nschten Zeilen ";
    echo "einschr&auml;nken und diesen Men&uuml;punkt anschlie&szlig;end erneut aufrufen.</p>";
}
else if ($confirmed) {
    if ($deleted > 0) {
        echo "<p><span style=\"color:#18a058;font-weight:bold;\">Fertig. Gel&ouml;schte Zeilen: " . $deleted . "</span></p>";
    }
    else {
        echo "<p>Es waren keine passenden Zeilen zum L&ouml;schen vorhanden.</p>";
    }
    echo "<p>Hinweis: Beim n&auml;chsten Lauf des Erschlie&szlig;ungsvergleichs werden diese Paare ggf. erneut betrachtet, sofern sie weiterhin als Duplikat-Kandidaten gelten. Eine separate Markierung &quot;zur Neu-Pr&uuml;fung&quot; ist daher nicht n&ouml;tig.</p>";
}
else {
    echo "<p>Hier k&ouml;nnen alle Eintr&auml;ge gel&ouml;scht werden, die <b>aktuell durch die Suche/den Filter in der &Uuml;bersicht ausgew&auml;hlt</b> sind.</p>";
    echo "<p>Aktuell sind <b>" . $cntBefore . "</b> Zeile" . ($cntBefore === 1 ? "" : "n") . " durch den aktiven Filter ausgew&auml;hlt.</p>";
    if ($cntBefore > 0) {
        echo "<p style=\"color:#b80000;\"><b>Achtung:</b> Auch redaktionelle Korrekturen gehen in den gefilterten Zeilen verloren. Diese Aktion l&auml;sst sich nicht r&uuml;ckg&auml;ngig machen.</p>";
        $jsConfirm = "return confirm('Wirklich " . $cntBefore . " gefilterte Zeile" . ($cntBefore === 1 ? "" : "n") . " unwiderruflich l\\u00f6schen?');";
        echo "<form method=\"post\" action=\"\">";
        echo "<input type=\"hidden\" name=\"confirm_filtered_delete\" value=\"1\">";
        echo "<p>";
        echo "<input class=\"button\" type=\"submit\" value=\"Ja, jetzt l&ouml;schen\" onclick=\"" . $jsConfirm . "\" />";
        echo "&nbsp;&nbsp;";
        echo "<input class=\"button\" type=\"button\" value=\"Abbrechen\" onclick=\"window.close();return false;\" />";
        echo "</p>";
        echo "</form>";
    }
}

$site->skin->workspaceEnd();

$site->skin->buttonsStart();
form_button('cancel', htmlconstant('_OK'), 'window.close();return false;');
$site->skin->buttonsEnd();

$site->pageEnd();
