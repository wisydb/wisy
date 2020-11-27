<?php if( !defined('IN_WISY') ) die('!IN_WISY');


/*****************************************************************************
 * WISY_MENUCHECK_CLASS
 * automatische Überprüfung der Portal-Menüs auf tote Links
 *****************************************************************************
 
 Aufruf dieses Scripts:
 menucheck&apikey=<apikey>	-	evtl. notwendiges Passwort, kann in den Portaleinstellungen
								der Domain unter apikey= definiert werden.
								Standardpasswort: none

 */

loadWisyClass('WISY_SYNC_RENDERER_CLASS');

class WISY_MENUCHECK_CLASS
{
	function __construct(&$framework, $param)
	{
		$this->framework		=& $framework;
		$this->statetable		= new WISY_SYNC_STATETABLE_CLASS($this->framework);
		$this->today_datetime   = strftime("%Y-%m-%d %H:%M:%S");
	}
	
	function render()
	{
		$overall_time = microtime(true);
		headerDoCache(0);
		header("Content-type: text/plain");
		
		$host = $_SERVER['HTTP_HOST'];
		
		// print common information
		$this->log(sprintf("menucheck script started"));
		
		// check the apikey
		// the apikey must be set in the portal settings of the domain this script is executed on.
		if( $_GET['apikey'] != $this->framework->iniRead('apikey') )
		{
			$this->log("********** ERROR: $host: bad apikey. ");
			return;
		}
		
		// make sure, this script does not abort too soon
		set_time_limit(2*60*60 /*2 hours ...*/);
		ignore_user_abort(true);
		
		// allocate exclusive access
		if( !$this->statetable->allocateUpdatestick() ) { $this->log("********** ERROR: $host: cannot menucheck now, update stick in use, please try again in about 10 minutes."); return; }
		
		$this->doMenucheck();
		
		// release exclusive access
		$this->statetable->releaseUpdatestick();
	}
	
	function doMenucheck()
	{
		$db = new DB_Admin;
		$db2 = new DB_Admin;
		
		$lastcheck = $this->statetable->readState('lastcheck.menucheck', '0000-00-00 00:00:00');
		
		// Select portal settings
		$sql = "SELECT id, einstellungen, einstellungen_hinweise FROM portale WHERE date_modified>='$lastcheck' AND status='1';";
		$db->query( $sql );
		
		while( $db->next_record() )
		{
			$portal_id 		= intval($db->f8('id'));
			$einstellungen  = $db->f8('einstellungen');
			$einstellungen_hinweise = $db->f8('einstellungen_hinweise');
			
			// TODO: Menu Einstellungen in Einstellungen finden und durcharbeiten
			// TODO: URLs finden, aufrufen und Rückgabewert auswerten
			// TODO: Andere Einträge von Menurenderer rendern lassen und Ergebnis überprüfen
			
			
			// Update einstellungen_hinweise
			$sql = "UPDATE portale SET einstellungen_hinweise='MENUCHECK " . $this->today_datetime . ": TODO', date_modified='" . $this->today_datetime . "' WHERE id=" . $portal_id;
			$db2->query($sql);
			
			$this->statetable->updateUpdatestick();
		}
		
		$this->statetable->writeState('lastcheck.menucheck', $this->today_datetime);
	}
	
	function log($str)
	{
		echo $str . "\n";
		flush();
		$this->framework->log('menucheck', $str);
	}
}
