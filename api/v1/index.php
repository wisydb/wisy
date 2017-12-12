<?php

/* ============================================================================
The REST API Class
===============================================================================
Search records:			
	GET /api/v1/?scope=<table>.search.<query>[<limit>][<order>]
	JSON return on success:
		{ "ids": [123, 456, 789, ...] }
	
	Hint 1: in table 'kurse' you can access 'durchfuehrung.*' and in table
		'durchfuehrung' you can access 'kurse.*' - JOINs are added automatically
		as needed.
	Hint 2: In tables 'kurse' and 'anbieter', you can access keywords by using
		'stichwoerter.id' - JOINs are added automatically as needed.
		
Read Records:
	GET /api/v1/?scope=<table>[.<id>[.<secondaryId]]
	JSON return on success:
		{ "field1": "value1", "field2": "value2", ... }

Modify Records:
	POST|UPDATE|DELETE /api/v1/?scope=<table>[.<id>[.<secondaryId]]
	JSON return on success:
		{ "id": 123 }

Errors:
	JSON return on errors:
		{ "error": "message" }
	HTTP status codes on errors:
		200 - OK - no error
		400 - Bad request
		403 - Forbidden
		503 - Service unavailable
		
Additional Parameters:
	&apikey=<apikey>
	&client=<clientname and -version>
	&method=GET|POST|DELETE|UPDATE (alternatively, you can set the HTTP request method directly)
===============================================================================
Mit (***) markierte Felder sind an das Portal gebunden!
D.h. wenn sich die Implementation von core20 aendert, muss auch hier Hand angelegt werden!
Die anderen Felder sind nativ und werden direkt so aus der Datenbank/dem Redaktionssystem ausgelesen.
===============================================================================
Some JSON hints:
	- arrays use the syntax [ value, value, ... ]
	- objects use the syntax { "prop": value, "prop": value, ... }
	- empty objects and arrays are okay
	- values may be int, boolean, strings and object
	- JSON uses UTF-8 by default - so we do the same
===============================================================================
Ideen, um den Gebrauch / Missbrauch zu begrenzen
	- IP loggen und nur eine best. Anzahl Verbindungen in einem best. Zeitraum zulassen
	- *vor* der Ausgabe ein kleines "sleep" einlegen, vll. 500 ms (evtl. konfigurierbar pro API-Key)
============================================================================ */



define('REST_READONLY', 			0x1000);
define('REST_PREPENDONLY', 			0x2000);
define('REST_SORTED', 				0x4000);
define('REST_SILENT', 				0x8000); /* do not issue a warning for readonly fields */
define('REST_REFONLY',             0x10000);

define('REST_INT', 					0x0001);
define('REST_INT_READONLY',			0x1001); /* REST_INT|REST_READONLY */
define('REST_INT_READONLY_SILENT',	0x9001); /* REST_INT|REST_READONLY|REST_SILENT */
define('REST_STRING', 				0x0002);
define('REST_STRING_PREPENDONLY',	0x2002); /* REST_STRING|REST_PREPENDONLY */
define('REST_STRING_SORTED',		0x4002); /* REST_STRING|REST_SORTED */
define('REST_MATTR',				0x0004);
define('REST_MATTR_REFONLY',	   0x10004);
define('REST_SECONDARY', 			0x0008);
define('REST_SPECIAL', 				0x0010);


define('APIKEY_ACTIVE',		0x01);
define('APIKEY_SECUREONLY',	0x02);
define('APIKEY_WRITE',		0x04);

function htmlconstant($a) { return $a;}



class REST_API_CLASS
{
	/* ========================================================================
	strcuture
	======================================================================== */

	var $fields = array
	(
		'kurse'	=> array
		(
			'id'=>array('flags'=>REST_INT_READONLY), 'user_created'=>array('flags'=>REST_INT),'user_modified'=>array('flags'=>REST_INT),'user_grp'=>array('flags'=>REST_INT),'user_access'=>array('flags'=>REST_INT),'date_created'=>array('flags'=>REST_STRING),'date_modified'=>array('flags'=>REST_STRING),
			'titel'				=>	array('flags'=>REST_STRING_SORTED,		),
			'org_titel'			=>	array('flags'=>REST_STRING,				),
			'freigeschaltet'	=>	array('flags'=>REST_INT,				),
			'vollstaendigkeit'	=>	array('flags'=>REST_INT,				),
			'anbieter'			=>	array('flags'=>REST_INT,				),
			'beschreibung'		=>	array('flags'=>REST_STRING,				),
			'thema'				=>	array('flags'=>REST_INT,				),
			'stichwoerter'		=>	array('flags'=>REST_MATTR_REFONLY,		'attr_table'=>'kurse_stichwort', 'attr_field'=>'attr_id', 'primary_table'=>'stichwoerter'	),
			'verweis'			=>	array('flags'=>REST_MATTR,				'attr_table'=>'kurse_verweis', 'attr_field'=>'attr_id'	),
			'durchfuehrung'		=>	array('flags'=>REST_SECONDARY,			'attr_table'=>'kurse_durchfuehrung', 'attr_field'=>'secondary_id'	),
			'bu_nummer'			=>	array('flags'=>REST_STRING,				),
			'res_nummer'		=>	array('flags'=>REST_STRING,				), // deprecated
			'fu_knr'			=>	array('flags'=>REST_STRING,				),
			'foerder_knr'		=>	array('flags'=>REST_STRING,				),
			'azwv_knr'			=>	array('flags'=>REST_STRING,				),
			'notizen'			=>	array('flags'=>REST_STRING_PREPENDONLY,	),
		),
		'durchfuehrung'	=> array
		(
			'id'=>array('flags'=>REST_INT_READONLY), 'user_created'=>array('flags'=>REST_INT),'user_modified'=>array('flags'=>REST_INT),'user_grp'=>array('flags'=>REST_INT),'user_access'=>array('flags'=>REST_INT),'date_created'=>array('flags'=>REST_STRING),'date_modified'=>array('flags'=>REST_STRING),
			'nr'				=>	array('flags'=>REST_STRING_SORTED,		),
			'stunden'			=>	array('flags'=>REST_INT,				),
			'teilnehmer'		=>	array('flags'=>REST_INT,				),
			'preis'				=>	array('flags'=>REST_INT,				),
			'preishinweise'		=>	array('flags'=>REST_STRING,				),
			'sonderpreis'		=>	array('flags'=>REST_INT,				),
			'sonderpreistage'	=>	array('flags'=>REST_INT,				),
			'beginn'			=>	array('flags'=>REST_STRING,				),
			'ende'				=>	array('flags'=>REST_STRING,				),
			'beginnoptionen'	=>	array('flags'=>REST_INT,				),
			'dauer'				=>	array('flags'=>REST_INT_READONLY_SILENT,), // deprecated
			'zeit_von'			=>	array('flags'=>REST_STRING,				),
			'zeit_bis'			=>	array('flags'=>REST_STRING,				),
			'kurstage'			=>	array('flags'=>REST_INT,				),
			'tagescode'			=>	array('flags'=>REST_INT_READONLY_SILENT,), // deprecated
			'strasse'			=>	array('flags'=>REST_STRING,				),
			'plz'				=>	array('flags'=>REST_STRING,				),
			'ort'				=>	array('flags'=>REST_STRING,				),
			'stadtteil'			=>	array('flags'=>REST_STRING,				),
			'land'				=>	array('flags'=>REST_STRING,				),
			'rollstuhlgerecht'	=>	array('flags'=>REST_INT,				),
			'bemerkungen'		=>	array('flags'=>REST_STRING,				),
			'herkunft'			=>	array('flags'=>REST_INT,				),
			'herkunftsID'		=>	array('flags'=>REST_STRING,				),
		),
		'anbieter' => array
		(
			'id'=>array('flags'=>REST_INT_READONLY), 'user_created'=>array('flags'=>REST_INT),'user_modified'=>array('flags'=>REST_INT),'user_grp'=>array('flags'=>REST_INT),'user_access'=>array('flags'=>REST_INT),'date_created'=>array('flags'=>REST_STRING),'date_modified'=>array('flags'=>REST_STRING),
			'suchname'			=>	array('flags'=>REST_STRING_SORTED,		),
			'freigeschaltet'	=>	array('flags'=>REST_INT,				),
			'verweis'			=>	array('flags'=>REST_MATTR,				'attr_table'=>'anbieter_verweis', 'attr_field'=>'attr_id'	),
			'typ'				=>	array('flags'=>REST_INT,				),
			'postname'			=>	array('flags'=>REST_STRING_SORTED,		),
			'strasse'			=>	array('flags'=>REST_STRING,				),
			'plz'				=>	array('flags'=>REST_STRING,				),
			'ort'				=>	array('flags'=>REST_STRING,				),
			'stadtteil'			=>	array('flags'=>REST_STRING,				),
			'land'				=>	array('flags'=>REST_STRING,				),
			'rollstuhlgerecht'	=>	array('flags'=>REST_INT,				),
			'leitung_name'		=>	array('flags'=>REST_STRING,				),
			'leitung_tel'		=>	array('flags'=>REST_STRING,				),
			'thema'				=>	array('flags'=>REST_INT,				),
			'stichwoerter'		=>	array('flags'=>REST_MATTR,				'attr_table'=>'anbieter_stichwort', 'attr_field'=>'attr_id'	),
			'din_nr'			=>	array('flags'=>REST_STRING_SORTED,		),
			'wisy_annr'			=>	array('flags'=>REST_STRING,				),
			'bu_annr'			=>	array('flags'=>REST_STRING,				),
			'foerder_annr'		=>	array('flags'=>REST_STRING,				),
			'fu_annr'			=>	array('flags'=>REST_STRING,				),
			'azwv_annr'			=>	array('flags'=>REST_STRING,				),
			'gruendungsjahr'	=>	array('flags'=>REST_INT,				),
			'rechtsform'		=>	array('flags'=>REST_INT,				),
			'firmenportraet'	=>	array('flags'=>REST_STRING,				),
			'homepage'			=>	array('flags'=>REST_STRING,				),
			'pruefsiegel_seit'	=>	array('flags'=>REST_STRING,				),
			'anspr_name'		=>	array('flags'=>REST_STRING,				),
			'anspr_zeit'		=>	array('flags'=>REST_STRING,				),
			'anspr_tel'			=>	array('flags'=>REST_STRING,				),
			'anspr_fax'			=>	array('flags'=>REST_STRING,				),
			'anspr_email'		=>	array('flags'=>REST_STRING,				),
			'settings'			=>	array('flags'=>REST_STRING,				), // Achtung, dass hier keine sensiblen Infos drin stehen!
			'in_wisy_seit'		=>	array('flags'=>REST_STRING,				),
			'herkunft'			=>	array('flags'=>REST_INT,				),
			'herkunftsID'		=>	array('flags'=>REST_STRING,				),
		),
		'themen' => array
		(
			'id'=>array('flags'=>REST_INT_READONLY), 'user_created'=>array('flags'=>REST_INT),'user_modified'=>array('flags'=>REST_INT),'user_grp'=>array('flags'=>REST_INT),'user_access'=>array('flags'=>REST_INT),'date_created'=>array('flags'=>REST_STRING),'date_modified'=>array('flags'=>REST_STRING),
			'thema'				=>	array('flags'=>REST_STRING_SORTED,		),
			'kuerzel'			=>	array('flags'=>REST_STRING_SORTED,		),
		),
		'stichwoerter' => array
		(
			'id'=>array('flags'=>REST_INT_READONLY), 'user_created'=>array('flags'=>REST_INT),'user_modified'=>array('flags'=>REST_INT),'user_grp'=>array('flags'=>REST_INT),'user_access'=>array('flags'=>REST_INT),'date_created'=>array('flags'=>REST_STRING),'date_modified'=>array('flags'=>REST_STRING),
			'stichwort'			=>	array('flags'=>REST_STRING_SORTED,		),
			'zusatzinfo'		=>	array('flags'=>REST_STRING,				),
			'verweis'			=>	array('flags'=>REST_MATTR,				'attr_table'=>'stichwoerter_verweis', 'attr_field'=>'attr_id'	),
			'verweis2'			=>	array('flags'=>REST_MATTR,				'attr_table'=>'stichwoerter_verweis2', 'attr_field'=>'attr_id'	),
			'eigenschaften'		=>	array('flags'=>REST_INT,				),
			'thema'				=>	array('flags'=>REST_INT,				),
		),
		'glossar' => array
		(
			'id'=>array('flags'=>REST_INT_READONLY), 'user_created'=>array('flags'=>REST_INT),'user_modified'=>array('flags'=>REST_INT),'user_grp'=>array('flags'=>REST_INT),'user_access'=>array('flags'=>REST_INT),'date_created'=>array('flags'=>REST_STRING),'date_modified'=>array('flags'=>REST_STRING),
			'begriff'			=>	array('flags'=>REST_STRING_SORTED,		),
			'erklaerung'		=>	array('flags'=>REST_STRING,				),
			'wikipedia'			=>	array('flags'=>REST_STRING,				),
		),
		'portale' => array
		(
			'id'=>array('flags'=>REST_INT_READONLY), 'user_created'=>array('flags'=>REST_INT),'user_modified'=>array('flags'=>REST_INT),'user_grp'=>array('flags'=>REST_INT),'user_access'=>array('flags'=>REST_INT),'date_created'=>array('flags'=>REST_STRING),'date_modified'=>array('flags'=>REST_STRING),
			'name'					=>	array('flags'=>REST_STRING,				),
			'kurzname'				=>	array('flags'=>REST_STRING,				),
			'domains'				=>	array('flags'=>REST_STRING,				),
			//'filter'				=>	array('flags'=>REST_STRING,				), // ausgeschaltet, da das Format proprietaer ist und sich u.U. noch aendern wird
			'anzahl_kurse'			=>	array('flags'=>REST_SPECIAL				), // (***)
			'anzahl_durchfuehrungen'=>	array('flags'=>REST_SPECIAL				), // (***)
			'anzahl_anbieter'		=>	array('flags'=>REST_SPECIAL				), // (***)
		),
		'user' => array
		(
			'id'=>array('flags'=>REST_INT_READONLY), 'user_created'=>array('flags'=>REST_INT),'user_modified'=>array('flags'=>REST_INT),'user_grp'=>array('flags'=>REST_INT),'user_access'=>array('flags'=>REST_INT),'date_created'=>array('flags'=>REST_STRING),'date_modified'=>array('flags'=>REST_STRING),
			'loginname'			=>	array('flags'=>REST_STRING,				),
			'name'				=>	array('flags'=>REST_STRING,				),
			'groups'			=>	array('flags'=>REST_MATTR,				'attr_table'=>'user_attr_grp', 'attr_field'=>'attr_id'	),
		),
		'user_grp' => array
		(
			'id'=>array('flags'=>REST_INT_READONLY), 'user_created'=>array('flags'=>REST_INT),'user_modified'=>array('flags'=>REST_INT),'user_grp'=>array('flags'=>REST_INT),'user_access'=>array('flags'=>REST_INT),'date_created'=>array('flags'=>REST_STRING),'date_modified'=>array('flags'=>REST_STRING),
			'shortname'			=>	array('flags'=>REST_STRING,				),
			'name'				=>	array('flags'=>REST_STRING,				),
		),
	);
	
	/* ========================================================================
	Common
	======================================================================== */

	var $last_warning = '';

	function __construct()
	{
		if (!extension_loaded('mbstring')) {
			if (!dl('mbstring.so')) {
				die('cannot load mbstring...');
			}
		}
	}
	
	function log($file, $msg)
	{
		// open the file
		$fullfilename = '../../files/logs/' . strftime("%Y-%m-%d") . '-' . $file . '.txt';
		$fd = @fopen($fullfilename, 'a');
		if( $fd )
		{
			$line = strftime("%Y-%m-%d %H:%M:%S") . "\t" . $msg . "\n";	
			@fwrite($fd, $line);
			@fclose($fd);
		}
	}
	function logApiState($state)
	{
		$protocol = $_SERVER['SERVER_PROTOCOL'];
		if( $_SERVER['HTTPS']=='on' ) $protocol = str_replace('HTTP', 'HTTPS', $protocol);
		$this->log('api', $_SERVER['REMOTE_ADDR']  . "\t" . $protocol . "\t" . $_SERVER['REQUEST_METHOD'] . "\t" . $_SERVER['HTTP_HOST'] . "\t" . $_SERVER['REQUEST_URI'] . "\t" . $state);
	}
	
	function halt($code, $msg)
	{
		$this->logApiState("error: $msg");
		header("Content-type: application/json", true, $code);
		$json = '{"error": { ';
		$json .= '"statuscode": '.$code.', ';
		$json .= '"message": "'.$this->utf82Json($msg).'" ';
		$json .= '} }';
		die($json);
	}

	function haltOnBadGrp($wanted_grp_id)
	{
		if( sizeof($this->apikeygrps)==0 ) {
			return; // no group restrictions specified -> access always granted -> continue
		}
		else if( $this->apikeygrps[ intval($wanted_grp_id) ] ) {
			return; // explicit access granted to the specific group -> continue
		}

		// no access -> halt
		if( intval($wanted_grp_id)==0 ) {
			$this->halt(403, 'no group specified');
		}
		$this->halt(403, 'no access to group '.intval($wanted_grp_id));
	}

	function haltOnBadRecord($table, $id, $check_grp_id)
	{
		// validate input
		if( !isset($this->fields[$table]) ) return false;
		$id = intval($id);

		// check for existance
		$db = new DB_Admin;
		$db->query("SELECT user_grp FROM $table WHERE id=$id;");
		if( !$db->next_record() ) {
			$this->halt(400, "cannot find $table.$id");
		}

		if( $check_grp_id ) {
			$record_grp_id = $db->f('user_grp');
			$this->haltOnBadGrp($record_grp_id);
		}
	}
	
	function utf82Json($in_str)
	{
		// convert an UTF-8-String to a JSON-encoded string
		$out_str = "";
		if( $in_str != '' )
		{
			$oldEnc = mb_internal_encoding();
			mb_internal_encoding("UTF-8");
				static $convmap = array(0x80, 0xFFFF, 0, 0xFFFF);
				static $jsonReplaces = array(array("\\", /*"/", TODO: muss "/" escaped werden? */ "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', /*'\\/',*/ '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
				for($i=mb_strlen($in_str)-1; $i>=0; $i--)
				{
					$mb_char = mb_substr($in_str, $i, 1);
					if( mb_ereg("&#(\\d+);", mb_encode_numericentity($mb_char, $convmap, "UTF-8"), $match) )
					{
						$out_str = sprintf("\\u%04x", $match[1]) . $out_str;
					}
					else
					{
						$mb_char = str_replace($jsonReplaces[0], $jsonReplaces[1], $mb_char);
						$out_str = $mb_char . $out_str;
					}
				}
			mb_internal_encoding($oldEnc);
		}
		return $out_str;
	}

	function explodeSettings($in)
	{
		// this is a copy from the global explodeSetting function defined in index.php. 
		// we need a copy here as the REST API does not use the normal entry point for performance reasons.
		$in = strtr($in, "\r\t", "\n "); $in = explode("\n", $in); $out = array();
		for( $i = 0; $i < sizeof($in); $i++ ) {
			$equalPos = strpos($in[$i], '=');
			if( $equalPos ) {
				$regKey = trim(substr($in[$i], 0, $equalPos));
				if( $regKey != '' ) {
					$out[$regKey] = trim(substr($in[$i], $equalPos+1)); // the key may be set with an empty value!
				}
			}
		}
		return $out;
	}
	
	function handleRequest()
	{
		header("Content-type: application/json");
		
		// check apikey
		$apikey = trim($_GET['apikey']);
		if( $apikey == '' ) $this->halt(403, 'apikey missing');
		if( strlen($apikey) < 8 ) $this->halt(403, 'apikey too short'); // as we allow manual apikeys, force a minimal length here
		
		$db = new DB_Admin;
		$db->query("SELECT id, flags FROM apikeys WHERE apikey='".addslashes($apikey)."'");
		if( !$db->next_record() ) $this->halt(403, 'bad apikey');
		
		$apikeyid = intval($db->f('id'));
		$this->apikeyflags = intval($db->f('flags'));
		if( !($this->apikeyflags&APIKEY_ACTIVE) ) $this->halt(403, 'apikey is inactive');
		
		if( ($this->apikeyflags&APIKEY_SECUREONLY) && $_SERVER['HTTPS']!='on' ) $this->halt(403, 'apikey requires a secure connection');
	
		// loads groups the apikey is restricted to
		$this->apikeygrps = array();
		$db->query("SELECT attr_id FROM apikeys_usergrp WHERE primary_id=".intval($apikeyid));
		while( $db->next_record() ) {
			$this->apikeygrps[ intval($db->f('attr_id')) ] = 1;
		}

		// get client - needed for debugging and statistics, the client is in the GET url; this should be just fine
		$client = $_GET['client'];
		if( $client == '' ) $this->halt(403, 'client missing - please specify the client program name and the clients version number.');
	
		// get method
		$method = $_SERVER['REQUEST_METHOD'];
		if( isset($_GET['method']) )
			$method = $_GET['method'];
		if( !($this->apikeyflags&APIKEY_WRITE) && $method!='GET' ) $this->halt(403, 'apikey has no write access');
		
		// get scope
		$scope = explode('.', $_GET['scope'], 3);

		// see what to do
		if( $method == 'GET' )
		{
			if( $scope[1] == 'search' ) 							
			{														// GET <table>.search.<query>
				echo $this->search($scope[0], $scope[2]);
			}
			else if( $scope[0] == 'durchfuehrung' && sizeof($scope)==2 )
			{														// GET durchfuehrung.<durchfId> -- gibt einen kompletten kurs inkl. durchfuehrungen zurueck
				$db = new DB_Admin;
				$db->query("SELECT primary_id FROM kurse_durchfuehrung WHERE secondary_id=" . intval($scope[1]));
				if( !$db->next_record() ) $this->halt(400, 'GET durchfuehrung: bad durchf id');
				echo $this->get('kurse', $db->f('primary_id'));
			}
			else if( $scope[0] == 'kurse' && sizeof($scope)==3 )	
			{														// GET kurse.<kursid>.<durchfId>
				$this->haltOnBadRecord('kurse', $scope[1], false);
				echo $this->get('durchfuehrung', $scope[2]);
			}
			else			
			{														// GET <table>.<id>
				echo $this->get($scope[0], $scope[1]);				
			}
		}
		else if( $method == 'POST' )
		{
			if( $scope[0] == 'kurse' && sizeof($scope)==2 )
			{														// POST kurse.<kursId> -- durchf. hinzufuegen -- die durchf darf einen beliebige gruppe haben, entscheidend ist nur die kursgruppe
				$this->haltOnBadRecord('kurse', $scope[1], true);
				echo $this->post('durchfuehrung', $insert_id);		
				$db = new DB_Admin;
				$db->Halt_On_Error = 'no';
				$sql = "INSERT INTO kurse_durchfuehrung (primary_id, secondary_id, structure_pos) VALUES ({$scope[1]}, $insert_id, $insert_id)";
				$db->query($sql);
				if( $db->Errno ) $this->halt(400, "search: bad condition, mysql says: ".$db->Error . " -- full sql: " . $sql);
			}
			else if( $scope[0] == 'kurse' )
			{														// POST kurse
				$this->haltOnBadGrp($_REQUEST['user_grp']);
				echo $this->post('kurse', $insert_id /*ret*/);		
			}
			else
			{
				$this->halt(400, "bad scope for $method");
			}
		}
		else if( $method == 'UPDATE' )
		{
			if( $scope[0] == 'kurse' && sizeof($scope)==3 )
			{														// UPDATE kurse.<kursId>.<durchfId> -- durchf aktualisieren
				$this->haltOnBadRecord('kurse', $scope[1], true);
				echo $this->update('durchfuehrung', $scope[2]);		
			}
			else if( $scope[0] == 'kurse' && sizeof($scope)==2 )
			{
				$this->haltOnBadRecord('kurse', $scope[1], true);
				echo $this->update('kurse', $scope[1]);				// UPDATE kurse.<kursId>
			}
			else
			{
				$this->halt(400, "bad scope for $method");
			}
		}
		else if( $method == 'DELETE' )
		{
			if( $scope[0] == 'kurse' && sizeof($scope)==3 )
			{														// DELETE kurse.<kursId>.<durchfId> -- durchf loeschen -- entscheiden für die Berechtigung ist die Kursgruppe, nicht die Durchfuehrungsgruppe
				$this->haltOnBadRecord('kurse', $scope[1], true);
				echo $this->deleteRecord('durchfuehrung', $scope[2]);
				$db = new DB_Admin;
				$db->Halt_On_Error = 'no';
				$sql = "DELETE FROM kurse_durchfuehrung WHERE primary_id=".intval($scope[1])." AND secondary_id=".intval($scope[2]);
				$db->query($sql);
			}
			else if( $scope[0] == 'kurse' && sizeof($scope)==2 )
			{
				$this->haltOnBadRecord('kurse', $scope[1], true);
				echo $this->deleteRecord('kurse', $scope[1]);				// DELETE kurse.<kursId>
			}
			else
			{
				$this->halt(400, "bad scope for $method");
			}
		}
		else
		{
			$this->halt(400, 'bad method');
		}
		
		// no error - do log the command
		$this->logApiState('OK');
	}	

	/* ========================================================================
	GET a record as JSON
	======================================================================== */

	function getSpecial($table, $id, $field, &$dbResult)
	{
		if( $table == 'portale' ) // (***)
		{
			if( !isset($this->anzahl_kurse) )
			{
				$einstcache = $this->explodeSettings($dbResult->fs('einstcache'));
				$this->anzahl_kurse				= intval($einstcache['stats.anzahl_kurse']);
				$this->anzahl_durchfuehrungen	= intval($einstcache['stats.anzahl_durchfuehrungen']);
				$this->anzahl_anbieter			= intval($einstcache['stats.anzahl_anbieter']);
			}
			if( $field == 'anzahl_kurse' ) 				return $this->anzahl_kurse;
			if( $field == 'anzahl_durchfuehrungen' )	return $this->anzahl_durchfuehrungen;
			if( $field == 'anzahl_anbieter' )			return $this->anzahl_anbieter;
		}
		return 'null';
	}
	
	function get($table, $id)
	{
		// validate input
		if( !isset($this->fields[$table]) ) $this->halt(400, 'bad scope for GET');
		$id = intval($id);
	
		// query record
		$db = new DB_Admin;
		$dba = new DB_Admin;
		
		$db->query("SELECT * FROM $table WHERE id=$id;");
		if( !$db->next_record() )
			$this->halt(400, "$table: bad id");
		
		$out = 0;
		$ret = '{';
		reset($this->fields[$table]);
		while( list($name, $prop) = each($this->fields[$table]) )
		{
			$ret .= $out? ",\n" : '';
			$ret .= '"' . $name . '": ';
			$out++;

			if( $prop['flags']&REST_INT )
			{
				$ret .= intval($db->f($name));
			}
			else if( $prop['flags']&REST_STRING )
			{
				if( $prop['flags']&REST_PREPENDONLY )
					$ret .= '""'; // can't read, but data may be prepended on write
				else
					$ret .= '"' . $this->utf82Json(utf8_encode($db->fs($name))) . '"';
			}
			else if( $prop['flags']&REST_MATTR )
			{
				$ret .= '[';
					$dba->query("SELECT ".$prop['attr_field']." FROM ".$prop['attr_table']." WHERE primary_id=".$db->f('id'));
					$outa = 0; while( $dba->next_record() ) { $ret .= $outa? ', ' : ''; $outa++; $ret .= intval($dba->f($prop['attr_field'])); }
				$ret .= ']';
			}
			else if( $prop['flags']&REST_SECONDARY )
			{
				$ret .= '[';
					$dba->query("SELECT ".$prop['attr_field']." FROM ".$prop['attr_table']." WHERE primary_id=".$db->f('id'));
					$outa = 0; while( $dba->next_record() ) { $ret .= $outa? ",\n" : ''; $outa++; $ret .= $this->get('durchfuehrung', $dba->f($prop['attr_field'])); }
				$ret .= ']';
			}
			else if( $prop['flags']&REST_SPECIAL )
			{
				$ret .= $this->getSpecial($table, $id, $name, $db);
			}
			else
			{
				$ret .= 'null';
			}
			
		}
		$ret .= '}';
		
		return $ret;
	}

	/* ========================================================================
	SEARCH for some data and return the IDs as JSON
	======================================================================== */

	function containsSqlInjection($sql, &$ret_bad_sql)
	{
		// check for bad SQL
		// bad command would be 'alter', 'call', 'create', 'delete', 'drop', 'grant', 'handler', 'insert', 'load', 'replace', 'update' -
		// however, it is better to avoid using different commands at all ...
		// see also http://www.symantec.com/connect/articles/detection-sql-injection-and-cross-site-scripting-attacks
		
		// TODO: We should preserve the strings themselves!
		
		$sql_lower = strtolower($sql);
		$bad_sql = array('--', ';', '#', '/*', '*/'); // when strings are preserved, '%' should  be added
		for( $i = sizeof($bad_sql)-1; $i >= 0 ; $i-- )
		{
			if( strpos($sql_lower, $bad_sql[$i])!==false )
			{
				$ret_bad_sql = $bad_sql[$i];
				return true;
			}
		}
		
		// sql is okay :-)
		return false;
	}
	
	function search($table, $query)
	{
		// validate input
		if( !isset($this->fields[$table]) ) $this->halt(400, "search: bad table");
		$query = trim($query);
		if( $query == '' ) $this->halt(400, "search: query missing");
		if( $this->containsSqlInjection($query, $ret_bad_sql) ) $this->halt(400, "search: query must not contain '" . $ret_bad_sql . "'");

		// build the SQL command
		$distinct = '';
		$joins = '';
		if( $table == 'kurse' && strpos($query, 'durchfuehrung.')!==false  )
		{
			$distinct = 'DISTINCT';
			$joins .= "LEFT JOIN kurse_durchfuehrung temp ON kurse.id=temp.primary_id LEFT JOIN durchfuehrung ON temp.secondary_id=durchfuehrung.id ";
		}
		else if( $table == 'durchfuehrung' && strpos($query, 'kurse.')!==false  )
		{
			$distinct = 'DISTINCT';
			$joins .= "LEFT JOIN kurse_durchfuehrung temp ON durchfuehrung.id=temp.secondary_id LEFT JOIN kurse ON temp.primary_id=kurse.id ";
		}
		
		if( ($table == 'kurse' || $table == 'anbieter') && strpos($query, 'stichwoerter.id')!==false )
		{
			$distinct = 'DISTINCT';
			$joins .= "LEFT JOIN {$table}_stichwort ON {$table}.id={$table}_stichwort.primary_id ";
			$query = str_replace('stichwoerter.id', "{$table}_stichwort.attr_id", $query);
		}
		
		$sql = "SELECT $distinct $table.id FROM $table $joins WHERE $query;";
		$db = new DB_Admin;
		$db->Halt_On_Error = 'no';
		$db->query($sql);
		if( $db->Errno ) $this->halt(400, "search: bad condition, mysql says: ".$db->Error . " -- full sql: " . $sql);
		
		// success - dump all IDs
		$ret = '{';
		//$ret .= '"debug": "' .$this->utf82Json($sql). '",' . "\n";
		$ret .= '"ids": [';
		$out = 0;
		while( $db->next_record() )
		{
			$ret .= $out? ', ' : '';
			$ret .= intval($db->f('id'));
			$out++;
		}
		$ret .= ']}';
		
		return $ret;
	}

	/* ========================================================================
	POST/UPDATE records
	======================================================================== */
	
	function post($table, &$insert_id)
	{
		// add a new record to the given table
		$today     	= strftime("%Y-%m-%d %H:%M:%S");
		
		$db = new DB_Admin;
		$db->Halt_On_Error = 'no';
		$db->query("INSERT INTO $table (date_created, date_modified, user_access) VALUES('$today', '$today', 508);");
		
		if( $db->Errno ) $this->halt(400, "post: bad condition, mysql says: ".$db->Error . " -- full sql: " . $sql);
		
		$insert_id = $db->insert_id();
		if( $insert_id <= 0 ) $this->halt(400, 'bad insert id');
		
		// update the record 
		return $this->update($table, $insert_id);
	}
	
	function update($table, $id)
	{
		// validate input
		if( !isset($this->fields[$table]) ) $this->halt(400, 'bad scope for GET');
		$id = intval($id);

		$db = new DB_Admin;
		$db->Halt_On_Error = 'no';
		
		$sql = '';
		reset($this->fields[$table]);
		while( list($name, $prop) = each($this->fields[$table]) )
		{
			if( isset($_REQUEST[$name]) )
			{
				if( $prop['flags']&REST_READONLY )
				{
					if( !($prop['flags']&REST_SILENT) )
						$this->halt(400, "field $name is read only and cannot be modified");
				}
				else if( $prop['flags']&REST_SECONDARY )
				{
					$this->halt(400, "secondary tables cannot be modified this way");
				}
				else if( $prop['flags']&REST_INT )
				{
					$sql .= $sql? ', ' : '';
					$sql .= "$name=" . intval($_REQUEST[$name]);
				}
				else if( $prop['flags']&REST_STRING )
				{
					$temp = utf8_decode($_REQUEST[$name]);
				
					$sql .= $sql? ', ' : '';
					
					if( $prop['flags']&REST_PREPENDONLY ) 
						$sql .= "$name=CONCAT('" . addslashes(trim($temp)) . "\n', notizen)";
					else
						$sql .= "$name='" . addslashes($temp) . "'";
					
					if( $prop['flags']&REST_SORTED ) {
						require_once('../../admin/classes.inc.php');
						require_once('../../admin/eql.inc.php');
						$sql .= ', ' . $name . "_sorted='" . g_eql_normalize_natsort($temp) . "'";
					}
				}
				else if( $prop['flags']&REST_MATTR )
				{
					$sql2 = "DELETE FROM {$prop['attr_table']} WHERE primary_id=$id;";
					$db->query($sql2);
					if( $db->Errno ) $this->halt(400, "post: bad condition, mysql says: ".$db->Error . " -- full sql: " . $sql2);
					
					$ids = explode(',', $_REQUEST[$name]);
					if( $prop['flags']&REST_MATTR_REFONLY )
					{
						// check if all given IDs exist and can be references
						$temp = '';
						for( $i = 0; $i < sizeof($ids); $i++ ) { $temp .= ($i?', ' : '') . intval($ids[$i]); }
						if( !isset($prop['primary_table']) ) $this->halt(400, "{$prop['attr_table']}: when using REST_MATTR_REFONLY, please also specify primary_table.");
						$sql2 = "SELECT id FROM {$prop['primary_table']} WHERE id IN($temp) and user_access&73"; // 73 = 0111 = %001001001 => all bits indicating referanceble records
						
						$referenceable_ids = array();
						$db->query($sql2); while( $db->next_record() ) { $referenceable_ids[ $db->f('id') ] = 1; }
					}

					$sql2 = '';
					for( $i = 0; $i < sizeof($ids); $i++ ) {
						$temp = intval($ids[$i]); 
						if( $temp <= 0 ) { $this->halt(400, "bad id for $name in scope $table"); } 
						if( $referenceable_ids[ $temp ] ) {
							$sql2 .= ($sql2!=''? ', ' : '') . "($id, $temp, $i)"; 
						}
						else {
							$this->last_warning = "{$name}={$temp} is not executed as the given ID is not referenceable.";
						}
					}
					
					if( $sql2 != '' )
					{
						$sql2 = "INSERT INTO {$prop['attr_table']} (primary_id, {$prop['attr_field']}, structure_pos) VALUES $sql2";
						$db->query($sql2);
						if( $db->Errno ) $this->halt(400, "post: bad condition, mysql says: ".$db->Error . " -- full sql: " . $sql2);
					}
				}
			}
		}
		
		if( $sql != '' )
		{
			$sql = "UPDATE $table SET $sql WHERE id=$id";
			$db->query($sql);
			if( $db->Errno ) $this->halt(400, "post: bad condition, mysql says: ".$db->Error . " -- full sql: " . $sql);
		}
		
		if( $this->last_warning != '' ) {
			return '{"id": ' . $id . ', "warning": "'.$this->utf82Json($this->last_warning).'"}';
		}
		else {
			return '{"id": ' . $id . '}';
		}
	}
	
	/* ========================================================================
	DELETE records
	======================================================================== */
	
	function deleteRecord($table, $id)
	{
		// validate input
		if( !isset($this->fields[$table]) ) $this->halt(400, 'bad scope for DELETE');
		$id = intval($id);

		// delete all linked records
		$db = new DB_Admin;
		$db->Halt_On_Error = 'no';
		reset($this->fields[$table]);
		while( list($name, $prop) = each($this->fields[$table]) )
		{
		
			if( $prop['flags']&REST_MATTR )
			{
				$db->query("DELETE FROM {$prop['attr_table']} WHERE primary_id=$id;");
			}
			else if( $prop['flags']&REST_SECONDARY )
			{
			
				$db->query("SELECT {$prop['attr_field']} FROM {$prop['attr_table']} WHERE primary_id=$id;");
				while($db->next_record() )
					$this->deleteRecord($name, $db->f($prop['attr_field']));
				$db->query("DELETE FROM {$prop['attr_table']} WHERE primary_id=$id;");
			}
		
		}

		// delete record itself
		$db->query("DELETE FROM $table WHERE id=$id;");
		
		// success
		return '{"id": ' . $id . '}';
	}
};



/* ============================================================================
Global Part
============================================================================ */

// connect to db
require('../../admin/sql_curr.inc.php');
require('../../admin/config/config.inc.php');

// do the REST :-)
$obj = new REST_API_CLASS();
$obj->handleRequest();

