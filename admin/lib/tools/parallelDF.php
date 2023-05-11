<?php

// Call this script by cronjob, e.g. daily:
// HTML output of this database search is saved into HTML file: 
// b/c direct call to this script = too much load on server and daily update = sufficient

$head  = "<head>
            <script src=\"/admin/lib/jquery/js/jquery-1.10.2.min.js\"></script>
          </head>
          <body>
         ";

$servername = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';

require_once("../../sql_curr.inc.php");
require_once("../../config/config.inc.php");

$db = new DB_Admin;

$DF_id_dubl = array();
$DF_id_dubl2 = array();

$DF_id_dubl_beginn = array();
$DF_id_dubl_beginn2 = array();

$DF_id_dubl_kurse = array();
$DF_id_dubl_kurse_done = array();

// nr
/* 
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

// beginn
$sql = "SELECT id, beginn, COUNT(*) AS Anzahl FROM `durchfuehrung` WHERE DATE(beginn) >= DATE(NOW()) OR beginn = '0000-00-00 00:00:00' GROUP BY beginn HAVING Anzahl > 1";
$db->query($sql);

while( $db->next_record() ) {
		$did = $db->f('id');
		$d_beginn = $db->fs('beginn');
		
		$DF_id_dubl_beginn[$did] = $d_beginn;
}

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
			
			$von = date("d.m.Y H:i", strtotime($value));
			$bis = date("d.m.Y H:i", strtotime($ende));
			
			$von = str_replace('01.01.1970', '--', $von);
			$bis = str_replace('01.01.1970', '--', $bis);
		
			$DF_id_dubl_beginn2[$usr_grp][$did] = "Beginn: ".substr($von, 0, strpos($von, ' '))." ".$zeit_von."h"
			                                     ."<br>Ende: ".substr($bis, 0, strpos($bis, ' '))." ".$zeit_bis."h<br>Adresse: ".$db->f('strasse')." ".$db->f('ort');
	}
}

$usr_grps = array();
$html = "";

$html .= "<table>"."\n";

foreach($DF_id_dubl_beginn2 AS $usr_grp) {
	
	foreach($usr_grp AS $key => $value) {
			$sql = "SELECT kurse.id AS id, kurse.titel, kurse.user_grp, kurse.anbieter, anbieter.suchname FROM kurse, anbieter, kurse_durchfuehrung WHERE kurse.id = kurse_durchfuehrung.primary_id AND kurse_durchfuehrung.secondary_id = "
			.$key." AND anbieter.id = kurse.anbieter AND kurse.freigeschaltet IN(0,1,4)";
			$db->query($sql);
			
			while( $db->next_record() ) {
					$k_id = $db->f('id');
					$usr_grp = $db->f('user_grp');
					
					
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
						$DF_id_dubl_kurse[$k_id] = $value;
					}
			}
	}
	
}


$usr_grps = array_unique($usr_grps);
$html .= "</table>"."\n";

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

$html .= "</body>";

file_put_contents("parallel_df.html", $head.$html);

echo "Done."

?>