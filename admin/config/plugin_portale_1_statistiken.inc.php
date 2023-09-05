<?php

require_once('functions.inc.php');
require_once('eql.inc.php');

global $portal_id;
$portal_id  = isset($_GET['id'])        ? intval($_GET['id']) : null;
$modul      = isset($_GET['module'])    ? addslashes( strval( $_GET['module'] )) : null;

// Read portal settings:
$db = new DB_Admin;
$db->query("SELECT kurzname, einstcache, einstellungen FROM portale WHERE id=".$portal_id);
$db->next_record();
$kurzname = $db->fs('kurzname');
$portalEinstellungen = $db->fs('einstellungen');

// Heading
$site->pageStart(array('popfit'=>1));
$site->skin->submenuStart();
echo '<b>Statistiken f&uuml;r Portal "'.$kurzname.'"</b>';
$site->skin->submenuBreak();
echo "&nbsp;";
$site->skin->submenuEnd();
$site->skin->workspaceStart();

// stroke color of statistic plotting
$strokeColor = isset($_GET['strokeColor']) ? $_GET['strokeColor'] : null;
// target after filter button pushed
$phpSelf = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '#';

?>

<style type="text/css">
 /* styling all statistic page elements: */
 th, td, tf {text-align: left;vertical-align: top;padding: .5em;}
 .grund, .url, .google {padding-right: 30px;}
 small {color: #555;margin-bottom: 10px;display: block;}
 input {font-weight: bold;}
 p {max-width: 450px;text-align: justify;}
 h2 {display: inline-block;}
 form {display: inline-block;margin-right: 2em;}#
 
 .csv_link {
 /* RRZEicons, CC BY-SA 3.0 <https://creativecommons.org/licenses/by-sa/3.0>, via Wikimedia Commons */
 background-image: url(/admin/skins/default/img/csv.jpg);
 font-weight: bold;margin-top: 20px;margin-bottom: 20px;height: 30px;padding-left: 40px;background-size: 20px !important;background-repeat: no-repeat !important;background-position-y: 50% !important;background-position-x: 10px !important;
 }
 .csv_link:hover {cursor: pointer;}
 body {min-height: 100vH !important;min-width: 100vW !important;}
 
 .stat_left, .stat_right {float: left; width: 48%;}
 .stat_left img, .stat_right img {width: 95%;max-width: 600px;display: block;}
 .block {border: 1px solid red;float: left;width: 48%;background-color: grey;padding: 1em;}
 .clear {float: none;clear: both;}
 .heading {font-size: 1.5em;}
 .stat_left .heading, .stat_right .heading { margin-top: 1em; margin-bottom: 1em; }
 .stat_left table, .stat_left table { margin-bottom: 2em; }
 .searchTags .clear:last-child hr { display: none; }
 .stat_left, .stat_right, .sml, .smr { box-shadow: 2px 2px 2px 2px silver; padding: 10px; }
 .stat_left, .stat_right { margin-top: 0.5em; }
 table.sm { margin-top: 2em; }
 table.sm:first-child { margin-top: 0.5em; }
 
 .availabilityResponsiveness .stat_left, .availabilityResponsiveness .stat_right { border-bottom: 1em; }
  .keineAngabe { color: #BBB !important; }
 label { margin-left: 1em; }
 select { font-weight: bold; margin-top: 1em; margin-bottom: 1em; padding: 5px; text-align: right !important; }
 .searchTags table { border-collapse: collapse; }
 .searchTags table tr:nth-child(even) { background: #eee; border-bottom: 1px solid #ccc; }
 .searchTags table tr:nth-child(odd) { background: #fff; border-bottom: 1px solid #ccc; }
 .searchTags table { width: 100%; }
 .searchTags th { background-color: #fffaee; }
</style>

<?php

/* ************************************ */
/* search tag & zero result statistics: */

/* outputs inner table content of tag statistic list */
function outputTagStatisticsTableContent( &$db, $heading ) {
    echo '<div class="heading">' . $heading . '</div>';
	   echo '<table>'; 
	
        $thead =  "<tr> <th>1.&nbsp;Suchwort(e)</th> <th>2.&nbsp;Suchwort(e)</th> <th>Anzahl</th> <!-- <th>Datum</th> --> </tr>";
    
        echo $thead;
    
        while( $db->next_record() ) {
        
            $timestamp = explode(',', $db->fs('timestamp'));
            $tout = '';
        
            foreach( $timestamp AS $t ) {
                $tout .= date( 'd.m.Y', strtotime($t) ) . ', ';
            }
        
            $query1 = $db->fs('query1');
            $query2 = $db->fs('q2');
            $query2 = preg_replace('/((dauer:)|(datum:)|(preis:)).*<hr>/i', '', $query2);
        
            if( $query1 == 'zeige:kurse' )
                continue; // !! woher??
            
            echo "<tr>
                    <td>
                        <a href='//"
                        . $db->fs('domain') . "/search?qs="
                        . $db->fs('query1') . ","
                        . $db->fs('query2') . ","
                        . $db->fs('query3') . "&q=&qf=&qsrc=s&qtrigger=h' target='_blank'>"
                        . ucfirst( $query1 )
                        . "</a>
                   </td> <!-- 1. Suchwort -->
                    <td>" . ucfirst( $query2 ) . "</td> <!-- 2. Suchwort -->
                    <td>" . $db->fs('cntQ1') . "</td> <!-- Anzahl -->
               <!-- <td> <small>" . $tout . "</small> </td> --> <!-- Datum -->
                 </tr>";
    }
    
    echo '</table>';
}

$db1 = new DB_Admin;
$limit  = ( !isset($_GET['limit']) )    ? 10           : intval($_GET['limit']); // top $limit list of common search tags etc.
$month  = ( !isset($_GET['month']) )    ? date("m")    : intval($_GET['month']);
$year   = ( !isset($_GET['year'])  )     ? date("Y")    : intval($_GET['year']);

$statCats = array(
                    array(
                        'heading' => 'Top <b>' . $limit . ' Suchbegriffe</b> in <b>Suchfeld</b>:',
                        'src' => 'h',
                        'statusIn' => "'neu','aus Cache'"
                    ),
                    array(
                        'heading' => 'Top <b>' . $limit . ' Suchbegriffe</b>  &uuml;ber <b>Men&uuml;</b>:',
                        'src' => 'm',
                        'statusIn' => "'neu','aus Cache'"
                    ),
                    array(
                        'heading' => 'Top <b>' . $limit . ' Suchbegriffe</b> in <b>Suchfeld mit Fehlern</b>:',
                        'src' => 'h',
                        'statusIn' => "'Fehler'"
                    ),
                    array(
                        'heading' => 'Top <b>' . $limit . ' Suchbegriffe</b>  &uuml;ber <b>Men&uuml; mit Fehlern</b>:',
                        'src' => 'm',
                        'statusIn' => "'Fehler'"
                    )
);
?>
<form action='<?php echo htmlspecialchars( $_SERVER['PHP_SELF'] ); ?>' id="statisticConf" method="GET">
 <?php 
    foreach( $_GET as $key => $value ) {
        if( !in_array($key, array( 'limit', 'month', 'year' ) ))
            echo '<input type="hidden" name="' . htmlspecialchars(  $key ) . '" value="'.htmlspecialchars( $value ).'" >';
    }
 ?>

 <label for="limit">Anzahl der Eintr&auml;ge:</label>
 <select name="limit" id="limitSelect" form="statisticConf" onchange='jQuery(this).parent().submit();'>
  <option value=5 <?php if($limit == 5) echo 'selected'; ?> 	>&nbsp;&nbsp;5</option>
  <option value=10 <?php if($limit == 10) echo 'selected'; ?> 	>&nbsp;10</option>
  <option value=20 <?php if($limit == 20) echo 'selected'; ?> 	>&nbsp;20</option>
  <option value=50 <?php if($limit == 50) echo 'selected'; ?> 	>&nbsp;50</option>
  <option value=100 <?php if($limit == 100) echo 'selected'; ?> >100</option>
</select>
&nbsp;&nbsp;&nbsp;

 <label for="year">Jahr:</label>
<select name="year" id="yearSelect" form="statisticConf" onchange='$("#monthSelect").val($("#monthSelect option:first").val()); jQuery(this).parent().submit();'>
  <option value=-1 <?php if($limit == -1) echo 'selected'; ?> 	>Keine Einschr&auml;nkung</option>
  <?php 
  for( $i=2023; $i <= 2033; $i++ ) { 
    echo '<option value=' . $i . ' ' . (($year == $i) ? "selected" : "") . '>&nbsp;&nbsp;' . $i . '</option>';  
  }
  ?>
</select>

<label for="month">Monat:</label>
<select name="month" id="monthSelect" form="statisticConf" onchange='jQuery(this).parent().submit();'>
  <option value=-1 <?php if($limit == -1) echo 'selected'; ?> 	>Keine Einschr&auml;nkung</option>
  <?php 
  for( $i=1; $i <= 12; $i++ ) { 
     echo '<option value=' . $i . ' ' . (($month == $i) ? "selected" : "") . '>&nbsp;&nbsp;' . $i . '</option>';  
  }
  ?>
</select>


</form>
<div class="searchTags">
	<?php 
        for( $i=0; $i < count($statCats); $i++ ) {  
    ?>
			<div class="<?php echo ( $i%2 == 0 ) ? 'stat_left' : 'stat_right'; ?>">
			<?php
			     
			 if( $month != -1 && $year != -1 ) { // both year and month defined
			     $date       = date( 'Y-m-d H:i:s', strtotime( str_pad($year, 4, "0", STR_PAD_LEFT)."-".str_pad($month, 2, "0", STR_PAD_LEFT). "-01 00:00:00" ) );
			     $maxDate    = date( 'Y-m-d H:i:s', strtotime( $date.' +1 month' ) );
			 }
			 elseif( $year != -1 ) { // only year defined
			     $date       = date( 'Y-m-d H:i:s', strtotime( $year . "-01-01 00:00:00" ) );
			     $maxDate    = date( 'Y-m-d H:i:s', strtotime( $year . "-12-31 00:00:00" ) );
			 }
			 else // neither year nor month defined
                $date = -1;
                
             $dateWhere = ( $date != -1 ? " AND datum_uhrzeit >= '" . $date . "' AND datum_uhrzeit < '" . $maxDate . "' " : " " );
             
	         $sql = "SELECT query1, GROUP_CONCAT(DISTINCT query2 SEPARATOR '<hr>') as q2, domain,
                     GROUP_CONCAT(DISTINCT datum_uhrzeit SEPARATOR ',') as timestamp, COUNT(query1) AS cntQ1
                     FROM x_searchqueries WHERE portal_id = $portal_id AND src = '" . $statCats[$i]['src'] . "'
                     AND status IN (" . $statCats[$i]['statusIn'] . ")"
                   . $dateWhere
                   . "GROUP BY query1 ORDER BY cntQ1 DESC, query1 ASC LIMIT $limit";
                     
             $db1->query( $sql );
                
             outputTagStatisticsTableContent( $db1, $statCats[$i]['heading'] );
            
            ?>
			</div>
			<?php 
            if( $i%2 == 1 ) echo '<div class="clear"></div>';
        }
    ?>
</div>
<?php 
/* end: search tag & zero result statistics: */
/* ***************************************** */


/* ********************************************************************* */
/* ********* Portal availability and responsiveness statistics: ******** */

$site->skin->submenuStart();
echo '<b>Portal-Verf&uuml;gbarkeit</b>';
$site->skin->submenuEnd();
?>
<br>

<?php 
$portalEinstellungen = explode("\n", $portalEinstellungen);
foreach($portalEinstellungen AS $einst) {
    $keyVal = array_map('trim', explode("=", $einst, 2));
    if( $keyVal[0] == "statistik.uptime.html")
        $uptimeHTML = $keyVal[1];
    if( $keyVal[0] == "statistik.responsetime.html")
        $responsetimeHTML = $keyVal[1];
}
?>
<div class="availabilityResponsiveness">
	<div class="stat_left" >
 		<?php
         if( isset($uptimeHTML) && $uptimeHTML != "" )
             echo '<h2>Uptime letzte 30 Tage:</h2>';
    
         echo $uptimeHTML; 
        ?>
	</div>
	<div class="stat_right">
 	<?php
        if( isset($uptimeHTML) && $uptimeHTML != "" )
            echo '<h2>Response time letzte 30 Tage:</h2>';
    
        echo $responsetimeHTML;
    ?>
	</div>
	<div class="clear"></div>
</div>

<?php


/* **** end: Portal availability and responsiveness statistics: ******** */
/* ********************************************************************* */


$site->skin->submenuStart();
echo '<b id="kursstatistiken">Kurs-Statistiken</b>';
$site->skin->submenuEnd();

// For stroke colors check: https://gist.github.com/d3noob/11313583 etc.

?>
<br>
<form action='<?php echo $phpSelf; ?>' method="GET">
 <input type="hidden" name="strokeColor" value="tomato" >
 <input type="hidden" name="id" value="<? echo $portal_id; ?>" >
 <input type="hidden" name="module" value="<? echo $modul; ?>" >
 <input type="hidden" name="what" value="Durchfuehrungen" >
 <input type="submit" value="Durchfuehrungen" style="color:tomato;" >
</form>

<form action='<?php echo $phpSelf; ?>' method="GET">
 <input type="hidden" name="strokeColor" value="saddlebrown" >
 <input type="hidden" name="id" value="<? echo $portal_id; ?>" >
 <input type="hidden" name="module" value="<? echo $modul; ?>" >
 <input type="hidden" name="what" value="Kurse" >
 <input type="submit" value="Kurse" style="color:saddlebrown;" >
</form>

<form action='<?php echo $phpSelf; ?>' method="GET">
 <input type="hidden" name="strokeColor" value="indianred" >
 <input type="hidden" name="id" value="<? echo $portal_id; ?>" >
 <input type="hidden" name="module" value="<? echo $modul; ?>" >
 <input type="hidden" name="what" value="ELearning" >
 <input type="submit" value="E-Learning" style="color:indianred;" >
</form>

<form action='<?php echo $phpSelf; ?>' method="GET">
 <input type="hidden" name="strokeColor" value="brown" >
 <input type="hidden" name="id" value="<? echo $portal_id; ?>" >
 <input type="hidden" name="module" value="<? echo $modul; ?>" >
 <input type="hidden" name="what" value="BlendedOrWeb" >
 <input type="submit" value="Blended Learning od. Web-Seminar" style="color:brown;" >
</form>

<form action='<?php echo $phpSelf; ?>' method="GET">
 <input type="hidden" name="strokeColor" value="darkblue" >
 <input type="hidden" name="id" value="<? echo $portal_id; ?>" >
 <input type="hidden" name="module" value="<? echo $modul; ?>" >
 <input type="hidden" name="what" value="Abschluesse" >
 <input type="submit" value="Abschluesse" style="color:darkblue;" >
</form>

<form action='<?php echo $phpSelf; ?>' method="GET">
 <input type="hidden" name="strokeColor" value="dodgerblue" >
 <input type="hidden" name="id" value="<? echo $portal_id; ?>" >
 <input type="hidden" name="module" value="<? echo $modul; ?>" >
 <input type="hidden" name="what" value="Zertifikate" >
 <input type="submit" value="Zertifikate" style="color:dodgerblue;" >
</form>

<form action='<?php echo $phpSelf; ?>' method="GET">
 <input type="hidden" name="strokeColor" value="teal" >
 <input type="hidden" name="id" value="<? echo $portal_id; ?>" >
 <input type="hidden" name="module" value="<? echo $modul; ?>" >
 <input type="hidden" name="what" value="Anbieter" >
 <input type="submit" value="Anbieter" style="color:teal;" >
</form>
	
<?php
// generate separate reports

if( !isset($_GET['what']) )
	; // Select what
else {
 $what_selected = addslashes( strval( $_GET['what'] ));
 $output_path = "stats/";
 $ist_domain = strtolower($_SERVER['HTTP_HOST']);
 $sandbox_prefix = substr($ist_domain, 0, 7) != 'sandbox' ? '' : substr($ist_domain, 0, strpos($ist_domain, '.'))."_";
 $sandbox_prefix_sync = substr($ist_domain, 0, 7) != 'sandbox' ? '' : substr($ist_domain, 0, strpos($ist_domain, '.'))."-sync_";
 $file_inputdata = "stats/".$sandbox_prefix."statistiken_".$portal_id.".csv";
 
 if( is_file($file_inputdata) )
    ;
 else 
     $file_inputdata =$file_inputdata = "stats/".$sandbox_prefix_sync."statistiken_".$portal_id.".csv";;


 $csv['Durchfuehrungen']['data'] = array();
 $csv['Kurse']['data'] = array();
 $csv['ELearning']['data'] = array();
 $csv['BlendedOrWeb']['data'] = array();
 $csv['Abschluesse']['data'] = array();
 $csv['Zertifikate']['data'] = array();
 $csv['Anbieter']['data'] = array();
			
 foreach($csv AS $what => $file) {
	$file_output = $output_path.$what."_".$portal_id.".csv";
						
	if( ($handle_csv = fopen($file_output, "w")) !== FALSE) {
					
	 if (($handle_data = fopen($file_inputdata, "r")) !== FALSE) {
		while (($data = fgetcsv($handle_data, 1000, ",")) !== FALSE) {
			if($data[0] == $what)
				fputcsv_eol( $handle_csv, array($data[1], $data[2]), ',', chr(127), "\r\n");
		}
		fclose($handle_data);
	 }
					
	}
				
	// Column title - fputcsv not working b/c "no enclosure" instead of " not working!
	$data = file_get_contents($file_output);
	$data = "Datum,Anzahl\r\n".$data;
	file_put_contents($file_output, $data);
 } 
 fclose($handle_csv);

?>

 <script>
  /*	window.lib_path = "/admin/lib/linestat/"; */
  window.grtitle = "Anzahl freigeschalteter <span style='color: <?php echo $strokeColor; ?>;'><?php echo $what_selected; ?></span>";
  window.inputCSV = "<?php echo 'https://'.(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '').'/admin/'.$output_path.$what_selected.'_'.$portal_id.'.csv'; ?>";
  window.strokeColor = "<?php echo $strokeColor; ?>";
 </script>

 <?php

 include("lib/linestat/index.html");
	
} // end: if what set

$site->skin->workspaceEnd();
	
$site->pageEnd();

/** ** **/

// Writes an array to an open CSV file with a custom end of line.
//
// $fp: a seekable file pointer. Most file pointers are seekable, 
//   but some are not. example: fopen('php://output', 'w') is not seekable.
// $eol: probably one of "\r\n", "\n", or for super old macs: "\r"
function fputcsv_eol($fp, $array, $delimiter, $str_encloser, $eol) {
  fputcsv($fp, $array, $delimiter, $str_encloser);
  if("\n" != $eol && 0 === fseek($fp, -1, SEEK_CUR)) {
    fwrite($fp, $eol);
  }
}



?>