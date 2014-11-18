<?php



/*****************************************************************************
 
 Einfache Postleitzahlensuche (für Hamburg)
 ------------------------------------------
 
 Mit der Funktion search_plz() kann die Postleitzahl und der Stadtteil
 zu einer gegebenen Strasse gesucht werden.
 
 Die Funktion gibt ein Array zurück:
 
 array(
 	'plz'		=>	'22767',
 	'stadtteil'	=>	'Altona'
 );
 
 Ist die Postleitzahl und/oder der Ort nicht eindeutig einer Strasse zuweisbar,
 wird für den jeweiligen Wert ein LEERER STRING zurückgegeben.  Können keine
 Daten gefunden werden (falsche Schreibweise der Strasse?), wird FALSE 
 zurückgegeben.
 
 
 Für eine Aktualisierung, muß dieses Skript direkt als
 /admin/config/plztool.inc.php?initplz 
 aufgerufen werden,  wobei die folgende Zeile auszukommentieren ist: */
 // define('DO_PLZ_INITIALISATION', true); 
 
 /****************************************************************************/




/*****************************************************************************
 * STRASSE SUCHEN
 *****************************************************************************/



global $plz_normalize_array;
$plz_normalize_array = array
(
	  'hamburg'		=>  'hh'
	, 'straáe'		=>	'str'
	, 'straße'		=>	'str'
	, 'strasse'		=>	'str'
	, 'ä'			=>	'a'
	, 'ö'			=>	'o'
	, 'ü'			=>	'u'
	, 'ß'			=>	'ss'
	, '\''			=>	'' // this also makes addslashes() unnecessary
	, '.'			=>	''
	, '-'			=>	''
	, ' '			=>	''
);
function plz_normalize($strasse)
{
	global $plz_normalize_array;
	return strtr(strtolower($strasse), $plz_normalize_array);
}

function search_plz(&$db, $strasse_hsnr, $ort)
{
	$strasse = trim(preg_replace('/(\d+.*)/', '', $strasse_hsnr));

	$ort_norm = plz_normalize($ort);
	$strasse_norm = plz_normalize($strasse);
	$sql = "SELECT plz, stadtteil FROM plztool WHERE strasse_norm='$strasse_norm' AND ort_norm='$ort_norm'";
	$db->query($sql);
	if( !$db->next_record() )
		return false; // no record found

	$plz = $db->fs('plz');
	$stadtteil = $db->fs('stadtteil');

	return array('plz'=>$plz, 'stadtteil'=>$stadtteil);
}




/*****************************************************************************
 * INITIALISATION / TESTS 
 *****************************************************************************/


function add_to_plz($strasse, $plz, $ort, $stadtteil)
{
	// ein paar korrekturen für den Stadtteil
    if( substr($stadtteil, 0, 5)=='Hamb.' ) {
    	$stadtteil = substr($stadtteil, 5);
    }

    if( substr($stadtteil, 0, strlen($ort)) == $ort ) {
    	$stadtteil = substr($stadtteil, strlen($ort));
    }

    if( substr($stadtteil, 0, 1)=='-' ) {
    	$stadtteil = substr($stadtteil, 1);
    }


	// hash berechnung
	global $g_plzinfo;
	global $g_plzinfo_mehrdeutigkeiten;

	$strasse_norm = plz_normalize($strasse);
	$ort_norm = plz_normalize($ort);
	if( $strasse_norm == '' || $ort_norm == '' ) echo "FEHLER: Strasse und/oder Ort nicht angegeben für add_to_plz($strasse, $plz, $ort, $stadtteil)<br />";
	
	$hash = "$ort_norm/$strasse_norm";
	if( !is_array( $g_plzinfo[ $hash ] ) )
	{
		$g_plzinfo[ $hash ] = array(
				'ort_norm'		=>	$ort_norm
			,	'strasse_norm'	=>	$strasse_norm
			,	'plz'			=>	$plz
			,	'stadtteil'		=>	$stadtteil
		);
	}
	else
	{
		if( $g_plzinfo[$hash]['plz'] != $plz )
		{
			$g_plzinfo[$hash]['plz'] = '';
			$g_plzinfo_mehrdeutigkeiten['plz'] ++;
			//echo "WARNUNG: PLZ mehrdeutig für add_to_plz($strasse, $plz, $ort, $stadtteil)<br />";
		}

		if( $g_plzinfo[$hash]['stadtteil'] != $stadtteil )
		{
			$g_plzinfo[$hash]['stadtteil'] = '';
			$g_plzinfo_mehrdeutigkeiten['stadtteil'] ++;
			//echo "WARNUNG: Stadtteil mehrdeutig für add_to_plz($strasse, $plz, $ort, $stadtteil)<br />";
		}
	}
}

function init_plz(&$db)
{
	// drop old tabel
	$sql = "DROP TABLE IF EXISTS plztool;";
	$db->query($sql);
	
	// create new table
	$sql = "CREATE TABLE plztool(
		  id int(11) NOT NULL auto_increment,
		  strasse_norm varchar(255) collate latin1_general_ci NOT NULL,
		  ort_norm varchar(255) collate latin1_general_ci NOT NULL,
		  plz varchar(255) collate latin1_general_ci NOT NULL,
		  stadtteil varchar(255) collate latin1_general_ci NOT NULL,
		  PRIMARY KEY  (id),
		  KEY strasse_norm (strasse_norm)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1;";
	$db->query($sql);

	// source configuration
	$filename = './sgv_2012_12.csv';
	
	/*
	if( $filename == './sgv0302.asc' )
	{
		$cols = array(6,40,   115,5,   89,21); // strasse,strasselen,   plz,plzlen,   stadtteil,stadtteillen
	}
	else
	{
		$cols = array(9,40,   124,5,   95,21); // strasse,strasselen,   plz,plzlen,   stadtteil,stadtteillen
	}
	*/

	// read data from source
	global $g_plzinfo;
	global $g_plzinfo_mehrdeutigkeiten;
	$g_plzinfo_mehrdeutigkeiten['plz'] = 0;
	$g_plzinfo_mehrdeutigkeiten['stadtteil'] = 0;
	
	$g_plzinfo = array();
	echo "Lese Hamburg Plz aus $filename ...<br />\n";
	flush();
	$hhinfofile = fopen($filename, 'r');
	if( $hhinfofile ) 
	{
		while (!feof ($hhinfofile))
		{
		    $buffer = trim(fgets($hhinfofile, 256));
		    if( $buffer )
		    {
				$buffer = explode(';', $buffer);
		    	$strasse   = trim($buffer[3]);
		    	$plz	   = intval($buffer[9]); if( $plz <= 0 ) $plz = '';
		    	$ort	   = 'Hamburg';
		    	$stadtteil = trim($buffer[6]);
				add_to_plz($strasse, $plz, $ort, $stadtteil);
			}
		}
		
		fclose($hhinfofile);
		echo sizeof($g_plzinfo) . " Strassen, davon<br />";
		echo $g_plzinfo_mehrdeutigkeiten['plz'] . " Strassen, deren Postleitzahl nicht eindeutig zugewiesen werden kann<br />";
		echo $g_plzinfo_mehrdeutigkeiten['stadtteil'] . " Strassen, deren Stadtteil nicht eindeutig zugewiesen werden kann<br />";
		// print_r($g_plzinfo);
	}
	else 
	{
		echo "FEHLER: Kann $filename nicht oeffnen.\n";
	}
	
	// daten in tabelle einfügen
	reset($g_plzinfo);
	while( list($hash, $v) = each($g_plzinfo) )
	{
		$ort_norm = addslashes($v['ort_norm']);
		$strasse_norm = addslashes($v['strasse_norm']);
		$plz = addslashes($v['plz']);
		$stadtteil = addslashes($v['stadtteil']);
		$sql = "INSERT INTO plztool (ort_norm, strasse_norm, plz, stadtteil) VALUES('$ort_norm', '$strasse_norm', '$plz', '$stadtteil')";
		$db->query($sql);
	}
	echo 'Daten in Datenbank geschrieben.<br />';
}

if( defined('DO_PLZ_INITIALISATION') && isset($_GET['initplz']) )
{
	require_once("../sql_curr.inc.php");
	require_once("./config.inc.php");
	$db = new DB_Admin;	
	
	if( isset($_GET['q']) )
	{
		echo 'Testing...<br />';
		$ret = search_plz($db, $_GET['q'], 'Hamburg');
		print_r($ret);
	}
	else
	{
		echo 'Initializing...<br />';
		init_plz($db);
		echo 'Init done.<br />';
	}
	exit();
}


