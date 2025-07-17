<?php

require_once('functions.inc.php');
require_once('eql.inc.php');

global $portal_id;
$portal_id = isset($_GET['id']) ? intval($_GET['id']) : -1;

$db = new DB_Admin;
$db->query("SELECT kurzname, einstcache FROM portale WHERE id=".$portal_id);
$db->next_record();
$kurzname = $db->fs('kurzname');

// render page

$site->pageStart(array('popfit'=>1));

$site->skin->submenuStart();
echo '<h2>&#9776;</h2> <b>Men&uuml;-Cache l&ouml;schen f&uuml;r Portal "'.$kurzname.'"</b>';
$site->skin->submenuBreak();
echo "&nbsp;";
$site->skin->submenuEnd();

$site->skin->workspaceStart();


?>
		<style type="text/css">
			th, td, tf {
				text-align: left;
				vertical-align: top;
			}
			
			.grund, .url, .google {
				padding-right: 30px;
			}
			
			small {
				color: #555;
				margin-bottom: 10px;
				display: block;
			}
			
			input {
				font-weight: bold;
			}
			
			form {
				padding: 10px;
			}
			
			p {
				max-width: 450px;
				text-align: justify;
			}
			
			h2 {
				display: inline-block;
			}
		</style>
	
		<form action="<?php echo (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '') . "?module=" . (isset($_GET['module']) ? $_GET['module'] : '') . "&menucache=delete&id=".$portal_id; ?>" method="POST">
			<p>Den Cache f&uuml;r Men&uuml;s zu l&ouml;schen sorgt daf&uuml;r, dass bisherige &Auml;nderungen an Men&uuml;s in Portaleinstellungen sofort aktuell auf der Website zur Verf&uuml;gung stehen.</p>
			<br>
			<input type="submit" value="Men&uuml;-Cache jetzt l&ouml;schen">
			<br><br><br>
		</form>
		
	<?php
	
	// Delete Menu Cache
	global $wisyPortalEinstcache;
	global $s_cacheModified;
	$s_cacheModified = false;
	
	if(isset($_GET['menucache']) && $_GET['menucache'] == "delete") {
		$wisyPortalEinstcache	= explodeSettings($db->fs('einstcache'));
		
		foreach($wisyPortalEinstcache AS $key => $value) {
			if( preg_match('/^menu.*key$/i', $key) ) {
				// echo "<b>".$key.":</b><br>";
				// echo $value."<br>";
				$wisyPortalEinstcache[$key] = "1970-01-01 00:00:00 1970-01-01 00:00:00 v7";
				$s_cacheModified = true;
			}
		}
		
		echo "<hr>";
		
		cacheFlush(); // write to portal einstcache
		
		echo "<h2 style='color: darkgreen;'>Men&uuml;-Cache gel&ouml;scht!</h2><br><br>";
	}
	
	?>
		
	<?php
	$site->skin->workspaceEnd();
	
	
	// SUCH-CACHE FUER DIESES PORTAL LOESCHEN:
	$db = new DB_Admin;
	$db->query("SELECT COUNT(*) AS cnt FROM x_cache_search WHERE ckey LIKE 'wisysearch.".$portal_id.".%'");
	$cnt = -1;
	if($db->next_record())
	    $cnt = $db->f("cnt");
	
	$site->skin->submenuStart();
	echo '<h2>&#128270;</h2> <b>Such-Cache l&ouml;schen f&uuml;r Portal "'.$kurzname.'" ('.$cnt.' Cache-Eintr&auml;ge)</b>';
	$site->skin->submenuBreak();
		echo "&nbsp;";
	$site->skin->submenuEnd();
	
	$site->skin->workspaceStart();
	?>
		
		<form action="<?php echo (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '') ."?module=" . (isset($_GET['module']) ? $_GET['module'] : '') . "&searchcache=delete&id=".$portal_id; ?>" method="POST">
			<p>Den Such-Cache zu l&ouml;schen sorgt daf&uuml;r, dass Suchergebnisse in Portalen neu erstellt werden (also nicht einfach die Angebote / Anbieter von der ersten Suche des Tages verwendet werden).</p>
			<p>Das ist <b>nicht</b> die komplette Neuerstellung des Suchindexes mit der Aufnahme neuer Angebote, Abgelaufen-Schalten von Angeboten, Berechnung von Dauer etc. !</p>
			<br><br>
			<input type="submit" value="Such-Cache jetzt l&ouml;schen">
			<br><br><br>
		</form>
		
	<?php
	
	// Delete Search Cache
	
	if(isset($_GET['searchcache']) && $_GET['searchcache'] == "delete") {
		$db = new DB_Admin;
		$db->query("DELETE FROM x_cache_search WHERE ckey LIKE 'wisysearch.".$portal_id.".%'");

		echo "<hr>";
		
		echo "<h2 style='color: darkgreen;'>Such-Cache gel&ouml;scht!</h2>";
	}
	
	?>
		
	<?php
	
	$site->skin->workspaceEnd();
		
	
    // GEOKODIERUNG-FEHLER LOESCHEN: PORTALUEBERGREIFEND!

	$db = new DB_Admin;
	$db->query("SELECT COUNT(*) AS cnt FROM `x_cache_latlng_search` WHERE cvalue LIKE '%Error%';");
	
	$cnt = -1;
	if($db->next_record())
	    $cnt = $db->f("cnt");
	
	$site->skin->submenuStart();
	echo '<h2>&#128270;</h2> <b>Geocoding-Fehler l&ouml;schen &uuml;ber alle Portale dieser Datenbank ('.$cnt.' Fehler)</b>';
	$site->skin->submenuBreak();
	echo "&nbsp;";
	$site->skin->submenuEnd();
	
	$site->skin->workspaceStart();
	?>
		
		<form action="<?php echo (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '') ."?module=" . (isset($_GET['module']) ? $_GET['module'] : '') . "&geocache=delete"; ?>" method="POST">
			<p>T&auml;glich werden Adressen aus Angeboten als auch Standorte, die bei der Umkreissuche eingegeben werden, geokodiert. D.h. die Adressen werden in L&auml;ngen- und Breitengrad &uuml;bersetzt, um Umkreissuchen ausf&uuml;hren zu k&ouml;nnen.</p>
			<p>Kann eine Adresse nicht geokodiert werden, weil sie falsch oder im falschen Format ist oder noch unbekannt oder der Geokodierung-Server ausgefallen etc. wird f&uuml;r diese Adresse ein Fehler gespeichert.</p>
			<p>(Vermeintlich) fehlerhafte Adressen werden aus Effizienz- und Limit-Gr&uuml;nden nicht erneut geokodiert.</p>
			<p>Diese Funktion f&uuml;hrt zu einem "Reset" der "fehlerhaften" Adressen, um diese erneut gekodieren zu k&ouml;nnen - etwa nach einem Ausfall des Geokodierung-Server.</p>
			<p>Im Anschluss (ggf. Fenster erneut &ouml;ffnen) muss noch der Such-Cache gel&ouml;scht werden!</p>
			<br><br>
			<input type="submit" value="Geocoding-Fehler jetzt l&ouml;schen">
			<br><br><br>
		</form>
		
	<?php
	
	// Delete geocoding mistakes - not portal specific!
	
	if(isset($_GET['geocache']) && $_GET['geocache'] == "delete") {
		$db = new DB_Admin;
		$db->query("DELETE FROM `x_cache_latlng_search` WHERE cvalue LIKE '%Error%';");

		echo "<hr>";
		
		echo "<h2 style='color: darkgreen;'>Geocoding-Fehler gel&ouml;scht!</h2>";
	}
	
	?>
		
	<?php
	
	$site->skin->workspaceEnd();
	
	$site->skin->buttonsStart();
		form_button('cancel', 'Fenster schlie&szlig;en.', 'window.close();return false;');
	$site->skin->buttonsEnd();

$site->pageEnd();

?>
<?php
function explodeSettings__($in, &$out, $follow_includes)
{
	$in = strtr($in, "\r\t", "\n ");
	$in = explode("\n", $in);
	for( $i = 0; $i < sizeof($in); $i++ )
	{
		$equalPos = strpos($in[$i], '=');
		if( $equalPos )
		{
			$regKey = trim(substr($in[$i], 0, $equalPos));
			if( $regKey != '' )
			{
				$regValue = trim(substr($in[$i], $equalPos+1));
				if( $regKey == 'include' ) 
				{
					if( !$follow_includes ) {
						echo 'ERROR: includes inside includes are not allowed!'; // a die() would be too harsh ...
					}
					else if( !@file_exists($regValue) ) {
						echo "ERROR: the following include-file does not exists: $regValue"; // a die() would be too harsh ...
					}
					else {
						$infile = file_get_contents($regValue);
						explodeSettings__($infile, $out, false);
					}
				}
				else
				{
					$out[$regKey] = $regValue; // the key may be set with an empty value!
				}
			}
		}
	}
}

function explodeSettings($in)
{
	$out = array();
	explodeSettings__($in, $out, true);
	return $out;
}

function cacheFlush()
{
	global $s_cacheModified;
	if( $s_cacheModified )
	{
		global $wisyPortalEinstcache;
		global $portal_id;
		cacheFlushInt($wisyPortalEinstcache, $portal_id);
		$s_cacheModified = false;
	}
}
	
function cacheFlushInt(&$values, $portalId)
{
	$ret = '';
	ksort($values);
	reset($values);
	foreach($values as $regKey => $regValue)
	{
		$regKey		= strval($regKey);
		$regValue	= strval($regValue);
		if( $regKey!='' ) 
		{
			$regValue = strtr($regValue, "\n\r\t", "   ");
			$ret .= "$regKey=$regValue\n";
		}
	}
	
	$db = new DB_Admin;
	$db->query("UPDATE portale SET einstcache='".addslashes($ret)."' WHERE id=$portalId;");
}
?>