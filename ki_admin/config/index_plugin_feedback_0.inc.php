<?php

require_once('functions.inc.php');
require_once('eql.inc.php');

define( 'DEBUG', false );

if( !isset($_SESSION['g_session_index_sql']['feedback']) )
{
	echo "Keine Feedbacks ausgew&auml;hlt?";
	exit();
}

$site->pageStart( array('popfit'=>1) );

$site->skin->submenuStart();
echo 'Feedbacks von Sicherheits-Scans und alten Feebacks bereinigen.';
$site->skin->submenuBreak();
echo "&nbsp;";
$site->skin->submenuEnd();

$site->skin->workspaceStart();


/* ***************************************** */
/* Clean Feedbacks of unwanted entries by IP */

$pattern        = "dtfy";
$sqlstoClean    = array();
$sqlstoClean[]  = "SELECT ip, url, COUNT(url) AS cntURLPattern FROM feedback WHERE url LIKE '%".$pattern."%'";

foreach( $sqlstoClean AS $sqlToClean) {
    
    $db = new DB_Admin;
    $sql = $sqlToClean;
    $db->query( $sql );
    $cntURLPattern = 0;
    $cntURL = 0;
    
    if( $db->next_record() )
    {
    	$url = trim($db->fs('url'));
    	$ip = trim($db->fs('ip'));
    	$cntURLPattern = $db->f('cntURLPattern');
    	
    	$queryAllUrlsWithIP = "SELECT COUNT(url) AS cntURL FROM feedback WHERE ip = '$ip'";
    	$db->query( $queryAllUrlsWithIP );
    	if( $db->next_record() ) {
    	   $cntURL = $db->f('cntURL');
    	
    	   $found = '<b>' . $cntURL . ' Eintr&auml;ge gefunden wie:</b>' . '<br><br>'
    	          . 'URL:<br>"' . $url . '",'. '<br><br>'
    	          . 'IP:<br>' . $ip . '<br>';
    	}
    }

    if( $cntURL > 0 ) {
        if( DEBUG ) echo $found;
        
        if( strpos($ip, '0.') === 0 ) {
            
            echo '<br>L&ouml;sche ' . $cntURL . ' Eintr&auml;ge mit der IP ' . $ip . '...' . '<br><br>';
            
            $sql = "DELETE FROM feedback WHERE ip = '" . $ip . "'";
            $db->query( $sql );
            
            if( DEBUG ) echo $sql . '<br><br>';
            
            echo '<span style="color: darkgreen; font-weight: bold;">Erledigt.</span>'. '<br><br>';
            
        } else  {
            echo '<span style="color: darkred; font-weight: bold;">Unbekannter Fehler. IP-Pattern ok?</span>'. '<br><br>';   
        }
        
	} else {
	    echo '<br><span style="color: darkgreen; font-weight: bold;">Es gibt keine Eintr&auml;ge zu l&ouml;schen, die durch bekannte Sicherheitsscans erzeugt wurden.</span>'. '<br><br>';   
	}
	
}

/* End: clean Feedbacks of unwanted entries by IP */
/* ********************************************** */


/* ******************************************* */
/* Clean Feedbacks of unwanted entries by date */

$cutOffDate_raw = strtotime("-420 days"); // 14 months
$cutOffDate     = date("Y-m-d 00:00:00", $cutOffDate_raw);

$db = new DB_Admin;
$sql = "SELECT ip, url, date_created, count(url) AS cntURL FROM feedback WHERE date_created < '" . $cutOffDate . "'";
$db->query( $sql );

if( $db->next_record() ) {
    $cntURL = $db->fs('cntURL');
    
    if( $cntURL > 0) {
        echo '<br>L&ouml;sche ' . $cntURL . ' Eintr&auml;ge &auml;lter als ' . date("d.m.Y", $cutOffDate_raw) . '...' . '<br><br>';
    
        $sql = "DELETE FROM feedback WHERE date_created < '" . $cutOffDate . "'";
        $db->query( $sql );
    
        if( DEBUG ) echo $sql . '<br><br>';
    
        echo '<span style="color: darkgreen; font-weight: bold;">Erledigt.</span>'. '<br><br>';
    } else {
        echo '<br><span style="color: darkgreen; font-weight: bold;">Es gibt keine Eintr&auml;ge zu l&ouml;schen, die &auml;lter als ' . date("d.m.Y", $cutOffDate_raw) . ' sind.</span>'. '<br><br>';
    }
    
} else {
    echo '<br><span style="color: darkgreen; font-weight: bold;">Fehler? Oder es gibt keine Eintr&auml;ge zu l&ouml;schen, die &auml;lter als ' . date("d.m.Y", $cutOffDate_raw) . ' sind.</span>'. '<br><br>';
}


/* End: Clean Feedbacks of unwanted entries by date */
/* ************************************************ */

$site->skin->workspaceEnd();
	
$site->skin->buttonsStart();
    form_button('cancel', htmlconstant('_OK'), 'window.close();return false;');
$site->skin->buttonsEnd();
		
$site->pageEnd();


?>