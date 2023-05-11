<?php

require_once('functions.inc.php');
require_once('eql.inc.php');

define( 'DEBUG', false );

if( !isset($_SESSION['g_session_index_sql']['feedback']) )
{
	echo "Keine Feedbacks ausgew&auml;hlt?";
	exit();
}

$site->pageStart(array('popfit'=>1));

$site->skin->submenuStart();
echo 'Die 20 unbeliebtesten und 20 beliebtesten Seiten gem&auml;&szlig; User-Bewertungen';
$site->skin->submenuBreak();
echo "&nbsp;";
$site->skin->submenuEnd();

$site->skin->workspaceStart();

$db = new DB_Admin;

echo "
<style>
    table th:first-child, table td:first-child {
        text-align: left;
        width: 40vW;
    }
    table th:last-child, table td:last-child {
        text-align: left;
        width: 40vW;
    }
    table td {
        padding: 1em;
        background-color: #fcfcfc;
        border: 1px solid #eee;
    }
</style>
";

$thead =  "<tr> <th>URL</th> <th>Anz. Bew.</th> <th>Anmerkungen</th> </tr>";

echo "<br><br><b>Unbeliebteste:</b><br><br>";
echo "<table>";
echo $thead;
$sql = "SELECT url, GROUP_CONCAT(DISTINCT descr SEPARATOR '<hr>') as descrAggr, COUNT(url) AS cntURL FROM feedback WHERE rating = 0 GROUP BY url ORDER BY cntURL DESC LIMIT 20";
$db->query( $sql );

while( $db->next_record() ) {
    echo "<tr> <td><a href='" . $db->fs('url') . "' target='_blank'>" . $db->fs('url') . "</a></td> <td>" . $db->fs('cntURL') . "</td> <td>" . substr_replace($db->fs('descrAggr'), '', 0, 4) . "</td> </tr>";
}

echo "</table>";


echo "<br><b>Beliebteste:</b><br><br>";
echo "<table>";
echo $thead;
$sql = "SELECT url, GROUP_CONCAT(DISTINCT descr SEPARATOR '<hr>') as descrAggr, COUNT(url) AS cntURL FROM feedback WHERE rating = 1 GROUP BY url ORDER BY cntURL DESC LIMIT 20";
$db->query( $sql );

while( $db->next_record() ) {
    echo "<tr> <td><a href='" . $db->fs('url') . "' target='_blank'>" . $db->fs('url') . "</a></td> <td>" . $db->fs('cntURL') . "</td> <td>" . substr_replace($db->fs('descrAggr'), '', 0, 4) . "</td> </tr>";
}

echo "</table>";


$site->skin->workspaceEnd();
	
$site->skin->buttonsStart();
    form_button('cancel', htmlconstant('_OK'), 'window.close();return false;');
$site->skin->buttonsEnd();
		
$site->pageEnd();


?>