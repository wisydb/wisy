<?php

/**
 * Gemeinsame Logik fuer das popup-basierte Loeschen von Eintraegen aus kurse_duplikate.
 *
 * Erwartet eine vorher gesetzte Variable $kurseDuplikateDeleteMode:
 *   - 'auto'      : loescht alle KI-Auto-Eintraege (user_modified = TEMPLATE_USER_ID)
 *   - 'editorial' : loescht alle redaktionell veraenderten Eintraege (user_modified != TEMPLATE_USER_ID)
 *
 * Wird aus admin/config/index_plugin_kurse_duplikate_1.inc.php bzw. _2.inc.php heraus eingebunden
 * (also unter dem Wrapper module.php). Erfordert Login - kein G_SKIP_LOGIN.
 *
 * Korrespondiert mit ki/main.py:
 *   - TEMPLATE_USER_ID = 7
 *   - Auto-Eintraege bekommen user_created=user_modified=TEMPLATE_USER_ID;
 *     redaktionell bearbeitete Eintraege haben einen anderen user_modified.
 */

require_once('functions.inc.php');

@set_time_limit(0);

if (!isset($kurseDuplikateDeleteMode)
    || ($kurseDuplikateDeleteMode !== 'auto' && $kurseDuplikateDeleteMode !== 'editorial')) {
    die('Ungueltiger Aufruf.');
}

$KURSE_DUPLIKATE_TEMPLATE_USER_ID = 7;
$isAuto = ($kurseDuplikateDeleteMode === 'auto');

if ($isAuto) {
    $whereSql   = "user_modified = " . intval($KURSE_DUPLIKATE_TEMPLATE_USER_ID);
    $titleHtml  = "Alle automatisch erstellten Vergleiche l&ouml;schen";
    $descrHtml  = "Hier k&ouml;nnen alle Eintr&auml;ge in der Duplikat-Tabelle gel&ouml;scht werden, die <b>automatisch von der KI</b> erzeugt und seitdem <b>nicht von der Redaktion ver&auml;ndert</b> wurden.";
    $confirmKey = 'confirm_auto';
}
else {
    $whereSql   = "user_modified <> " . intval($KURSE_DUPLIKATE_TEMPLATE_USER_ID);
    $titleHtml  = "Alle redaktionell ver&auml;nderten Zeilen l&ouml;schen";
    $descrHtml  = "Hier k&ouml;nnen alle Eintr&auml;ge in der Duplikat-Tabelle gel&ouml;scht werden, die <b>von der Redaktion bearbeitet oder erstellt</b> wurden. Achtung: <b>Redaktionelle Korrekturen gehen unwiderruflich verloren.</b>";
    $confirmKey = 'confirm_editorial';
}

$db = new DB_Admin();
$db->query("SELECT COUNT(*) AS cnt FROM kurse_duplikate WHERE " . $whereSql);
$cntBefore = 0;
if ($db->next_record()) {
    $cntBefore = intval($db->f('cnt'));
}

$confirmed = isset($_POST[$confirmKey]) && $_POST[$confirmKey] === '1';
$deleted   = 0;

if ($confirmed && $cntBefore > 0) {
    $dbDel = new DB_Admin();
    $dbDel->query("DELETE FROM kurse_duplikate WHERE " . $whereSql);
    $deleted = $cntBefore;
}

$site->pageStart(array('popfit' => 1));

$site->skin->submenuStart();
echo $titleHtml;
$site->skin->submenuBreak();
echo "&nbsp;";
$site->skin->submenuEnd();

$site->skin->workspaceStart();

if ($confirmed) {
    if ($deleted > 0) {
        echo "<p><span style=\"color:#18a058;font-weight:bold;\">Fertig. Gel&ouml;schte Zeilen: " . $deleted . "</span></p>";
    }
    else {
        echo "<p>Es waren keine passenden Zeilen zum L&ouml;schen vorhanden.</p>";
    }
    if ($isAuto) {
        echo "<p>Hinweis: Beim n&auml;chsten Lauf des Erschlie&szlig;ungsvergleichs werden die automatischen Eintr&auml;ge ggf. wieder neu erzeugt.</p>";
    }
    else {
        echo "<p>Hinweis: Bereits redaktionell entschiedene Paare erscheinen beim n&auml;chsten KI-Lauf wieder als unbewertete Auto-Eintr&auml;ge.</p>";
    }
}
else {
    echo "<p>" . $descrHtml . "</p>";
    echo "<p>Aktuell sind in der Duplikat-Tabelle <b>" . $cntBefore . "</b> passende Zeile" . ($cntBefore === 1 ? "" : "n") . " vorhanden.</p>";
    if ($cntBefore > 0) {
        $jsConfirm = "return confirm('Wirklich " . $cntBefore . " Zeile" . ($cntBefore === 1 ? "" : "n") . " unwiderruflich l\\u00f6schen?');";
        echo "<form method=\"post\" action=\"\">";
        echo "<input type=\"hidden\" name=\"" . htmlspecialchars($confirmKey) . "\" value=\"1\">";
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
