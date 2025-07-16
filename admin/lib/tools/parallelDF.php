<?php

// This script is meant to be called by a cronjob (e.g., daily).
// It generates a report of potentially duplicate "durchfuehrung" (execution/instance) records in the WISY system.
// The HTML output is saved into a static file to avoid heavy load from direct live queries.
// Motivation: Automate repetitive checks, save time, and keep data clean! 

// HTML HEAD (with jQuery for UI interactions later)
$head  = "<head><script src=\"/admin/lib/jquery/js/jquery-1.10.2.min.js\"></script></head>
          <body>
         ";

// Get server name for building links in the output table
$servername = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';

// INCLUDE DATABASE CONNECTIONS & CONFIG
require_once("../../sql_curr.inc.php");
require_once("../../config/config.inc.php");


// Use DB_Admin wrapper (WISY internal DB abstraction)
$db = new DB_Admin;

// ARRAYS FOR DUPLICATES / INTERMEDIATE STORAGE
$DF_id_dubl = array();
$DF_id_dubl2 = array();

$DF_id_dubl_beginn = array();
$DF_id_dubl_beginn2 = array();

$DF_id_dubl_kurse = array();
$DF_id_dubl_kurse_done = array();

/* 
// DUPLICATE CHECK BY "nr" FIELD
$sql = "SELECT id, nr, COUNT(*) AS Anzahl FROM `durchfuehrung` WHERE LENGTH(nr) > 0 GROUP BY nr HAVING Anzahl > 1";
$db->query($sql);

while( $db->next_record() ) {
 $did = $db->f('id');
 $d_nr = strip_tags($db->fs('nr'));
 $DF_id_dubl[$did] = $d_nr;
}

foreach($DF_id_dubl AS $key => $value) {
 $sql = "SELECT id, nr FROM `durchfuehrung` WHERE nr='".$value."'";
 $db->query($sql);
	
 while( $db->next_record() ) {
  $did = $db->f('id');
  $d_nr = strip_tags($db->fs('nr'));
  $DF_id_dubl2[$did] = $d_nr;
 }
} */

// DUPLICATE CHECK BY "beginn" (START DATE/TIME)
// Find all "durchfuehrung" records that have the same start datetime and are either in the future or undated.
$sql = "SELECT id, beginn, COUNT(*) AS Anzahl FROM `durchfuehrung` WHERE DATE(beginn) >= DATE(NOW()) OR beginn = '0000-00-00 00:00:00' GROUP BY beginn HAVING Anzahl > 1";
$db->query($sql);

// Save all IDs with their shared "beginn"
while( $db->next_record() ) {
		$did = $db->f('id');
		$d_beginn = $db->fs('beginn');
		
		$DF_id_dubl_beginn[$did] = $d_beginn;
}


// For each duplicate "beginn", fetch all related "durchfuehrung" records
foreach($DF_id_dubl_beginn AS $key => $value) {
	$sql = "SELECT id, beginn, zeit_von, zeit_bis, ende, strasse, ort, beginnoptionen FROM `durchfuehrung` WHERE beginn='".$value."' ORDER BY user_grp ASC";
	$db->query($sql);
	
		while( $db->next_record() ) {
			$did = $db->f('id');
			$ende = $db->f('ende');
			$usr_grp = $db->f('user_grp');
			$zeit_von = $db->fs('zeit_von');
			$zeit_bis = $db->fs('zeit_bis');
			$beginnoptionen = $db->fs('beginnoptionen');
			
			// Format dates, handling zero-date fallback
			$von = date("d.m.Y H:i", strtotime($value));
			$bis = date("d.m.Y H:i", strtotime($ende));
			
			$von = str_replace('01.01.1970', '--', $von);
			$bis = str_replace('01.01.1970', '--', $bis);
		
			// Build display string for each "durchfuehrung"
			$DF_id_dubl_beginn2[$usr_grp][$did] = "Beginn: ".substr($von, 0, strpos($von, ' '))." ".$zeit_von."h"
			                                     ."<br>Ende: ".substr($bis, 0, strpos($bis, ' '))." ".$zeit_bis."h<br>Adresse: ".$db->f('strasse')." ".$db->f('ort');
	}
}

// BUILD HTML TABLE WITH DUPLICATES
// Table to display all found duplicates, grouped by user group
$usr_grps = array();
$html = "";

$html .= "<table>"."\n";

// Iterate over each user group, and for each, each duplicate "durchfuehrung"
foreach($DF_id_dubl_beginn2 AS $usr_grp) {
	
	foreach($usr_grp AS $key => $value) {
	    
	        // Find course ("kurse") for each duplicate "durchfuehrung"
			$sql = "SELECT kurse.id AS id, kurse.titel, kurse.user_grp, kurse.anbieter, anbieter.suchname FROM kurse, anbieter, kurse_durchfuehrung WHERE kurse.id = kurse_durchfuehrung.primary_id AND kurse_durchfuehrung.secondary_id = "
			.$key." AND anbieter.id = kurse.anbieter AND kurse.freigeschaltet IN(0,1,4)";
			$db->query($sql);
			
			while( $db->next_record() ) {
					$k_id = $db->f('id');
					$usr_grp = $db->f('user_grp');
					
					// Only show unique pairings; skip if already shown
					if($DF_id_dubl_kurse[$k_id] == $value && $DF_id_dubl_kurse_done[$k_id] != $value) {
						$html .= "<tr class='grp_".$usr_grp."' id='".$k_id."'>"."\n";
						$html .= "<td><a href='https://".$servername."/admin/edit.php?table=kurse&id=".$k_id."' target='_blank' rel='noopener noreferrer'>".$k_id."</a></td>";
						$html .= "<td><a href='https://".$servername."/k".$k_id."' target='_blank' rel='noopener noreferrer'>".$db->f('titel')."</a><br><small>".$value."</small></td>"."\n";
						$html .= "<td>".$db->f('suchname')."<td>";
						$html .= "</tr>"."\n";
						array_push($usr_grps, $usr_grp);
						$DF_id_dubl_kurse_done[$k_id] = $value;
					}
					else {
					    // Mark this duplicate for later
						$DF_id_dubl_kurse[$k_id] = $value;
					}
			}
	}
	
}


$usr_grps = array_unique($usr_grps);
$html .= "</table>"."\n";

// BUILD FILTER DROPDOWN FOR USER GROUPS
$head .= "<b>Benutzergruppe</b><br>";
$head .= "<select id='usr_grpselect'>";
$head .= "<option value='alle' selected>Alle</option>";

foreach($usr_grps AS $usr_grp) {
	$sql = "SELECT name FROM user_grp WHERE id = ".$usr_grp;
	$db->query($sql);
	$db->next_record();
	$head .= "<option value='grp_".$usr_grp."'>".$usr_grp.") ".$db->fs('name')."</option>";
}

$head .= "</select><br><br>";


// JAVASCRIPT: FILTER TABLE BY USER GROUP
$head .= "<script>";
$head .= "
 jQuery('#usr_grpselect').on('change', function() {
  jQuery('tr').hide();
		
  if(this.value == 'alle')
   jQuery('tr').show();
  else
   jQuery('tr.'+this.value).show();
});";
$head .= "</script>";

// $a_int = array_intersect_key($DF_id_dubl2, $DF_id_dubl_beginn2);

// FINALIZE HTML
$html .= "</body>";

// SAVE TO HTML FILE
// Motivation: Fast access for admins, no runtime DB load!
file_put_contents("parallel_df.html", $head.$html);

echo "Done."

?>