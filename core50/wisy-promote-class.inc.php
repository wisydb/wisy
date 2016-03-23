<?php if( !defined('IN_WISY') ) die('!IN_WISY');

/*
	event_type for anbieter_promote_log		date_created	lparam
	--------------------------------------------------------------
	1001	record viewed					day				count
	1002	record clicked					day				count
	
	2001	current credits					-				count
	2201	out of credits					-				-

	event_type for anbieter_billing
	--------------------------------------------------------------
	9001	credits added via PayPal
	9002	credits added manually
	9099    credits adding error, manual investigation required
*/

class WISY_PROMOTE_CLASS
{
	var $framework;

	function __construct(&$framework)
	{
		// constructor
		$this->framework	=& $framework;
		$this->db			= new DB_Admin;
		$this->secneeded	= 0.0;
		
		$this->ipCache		=& createWisyObject('WISY_CACHE_CLASS', $this->framework, array('table'=>'x_cache_promoteips', 'itemLifetimeSeconds'=>15*60));
	}
	
	function logPromotedRecordViews(&$records, $queryString)
	{
		$now = $this->framework->microtime_float();

			// log an array of views
			reset($records['records']);
			while( list($i, $record) = each($records['records']) )
			{
				$this->logPromotedRecordView($record['id'], $record['anbieter'], $queryString);
			}
		
		$this->secneeded = $this->framework->microtime_float() - $now;
	}
	
	function logPromotedRecordView($kursId, $anbieterId, $queryString)
	{
		// this function is called for EVERY promoted record and has about 6-7 sql commands ...
	
		global $wisyPortalId;
		$todayHour     = strftime("%Y-%m-%d %H:%M:%S");
		//$cleanupCache	= false;

		// check if the ip was not used in the last time ...
		$ipLookupKey = $_SERVER['REMOTE_ADDR']."/$wisyPortalId/$kursId/$anbieterId/$queryString";
		if( $this->ipCache->Lookup($ipLookupKey) )
		{
			$this->framework->log('promote', "no double counting for $ipLookupKey");
			return;
		}
		$this->ipCache->insert($ipLookupKey, 1);
		
		// increase the view counter
		$this->incLparam($kursId, $anbieterId, 1001);

		// decrease the credit counter
		$credits = $this->getCredits($anbieterId);
		if( $credits > 0 )
		{
			$credits--;
			$this->setCredits($anbieterId, $credits);
			if( $credits == 0 )
			{
				// out of credits :-(
				$this->db->query("UPDATE anbieter_promote SET promote_active=0 WHERE anbieter_id=$anbieterId AND portal_id=$wisyPortalId;");
				//$cleanupCache = true;
				
				$db->query("INSERT INTO anbieter_promote_log (date_created, date_modified, anbieter_id, portal_id, kurs_id, event_type, lparam) VALUES ('$todayHour', '$todayHour', $anbieterId, $wisyPortalId, 0, 2201, 0)");
			}
		}
		else
		{
			// this should not happen ... if there are no credits, promote_active should be null! force it to null again!
			$this->db->query("UPDATE anbieter_promote SET promote_active=0 WHERE anbieter_id=$anbieterId AND portal_id=$wisyPortalId;");
		}
		
		// decrease the promotion counter or check the ending date
		$this->db->query("SELECT promote_mode, promote_param FROM anbieter_promote WHERE anbieter_id=$anbieterId AND portal_id=$wisyPortalId AND promote_active=1;");
		if( $this->db->next_record() )
		{
			$promote_mode = $this->db->f8('promote_mode');
			$promote_param = $this->db->f8('promote_param');
			$promote_active = 1;
			switch( $promote_mode )
			{
				case 'times':
					$promote_param = $promote_param-1; if( $promote_param < 0 ) $promote_param = 0;
					if( $promote_param == 0 ) { $promote_active = 0; /*$cleanupCache = true;*/ }
					$this->db->query("UPDATE anbieter_promote SET promote_param=$promote_param, promote_active=$promote_active, date_modified='$todayHour' WHERE anbieter_id=$anbieterId AND portal_id=$wisyPortalId AND promote_active=1;");
					break;
				
				case 'date':
					if( $promote_param < strftime("%Y-%m-%d") )
					{
						$promote_active = 0; /*$cleanupCache = true;*/
						$this->db->query("UPDATE anbieter_promote SET promote_active=$promote_active, date_modified='$todayHour' WHERE anbieter_id=$anbieterId AND portal_id=$wisyPortalId AND promote_active=1;");
					}
					break;
			}
		}

		// DONE
		/* - EDIT 05.03.2011: cache objekte werde nach ca. 30 minuten wieder freigegeben; wenn in dieser Zeit noch Kurse zusätzlich promoted werden ist es halt glück für die Anbieter ...
		if( $cleanupCache )
		{
			$dbCache =& createWisyObject('WISY_CACHE_CLASS', $this->framework, array('table' => 'x_cache_search'));
			$dbCache->cleanup(); 
		}
		*/
	}
	
	function logPromotedRecordClick($kursId, $anbieterId)
	{
		// log a single click
		$this->incLparam($kursId, $anbieterId, 1002);
	}



	/**************************************************************************
	 * db tools
	 **************************************************************************/
	 
	function incLparam($kursId, $anbieterId, $event_type)
	{
		global $wisyPortalId;
		$todayMidnight = strftime("%Y-%m-%d 00:00:00");
		$todayHour     = strftime("%Y-%m-%d %H:%M:%S");
		$this->db->query("UPDATE anbieter_promote_log SET lparam=lparam+1, date_modified='$todayHour' WHERE kurs_id=$kursId AND date_created='$todayMidnight' AND portal_id=$wisyPortalId AND event_type=$event_type;");
		if( $this->db->affected_rows() == 0 )
		{
			$this->db->query("INSERT INTO anbieter_promote_log (date_created, date_modified, anbieter_id, portal_id, kurs_id, event_type, lparam) VALUES ('$todayMidnight', '$todayHour', $anbieterId, $wisyPortalId, $kursId, $event_type, 1)");
		}
	}

	function getCredits($anbieterId)
	{
		global $wisyPortalId;
		$this->db->query("SELECT lparam FROM anbieter_promote_log WHERE anbieter_id=$anbieterId AND portal_id=$wisyPortalId AND event_type=2001;");
		$this->db->next_record();
		$credits = intval($this->db->f8('lparam'));
		return $credits;
	}
	
	function setCredits($anbieterId, $credits)
	{
		global $wisyPortalId;
		$todayHour     = strftime("%Y-%m-%d %H:%M:%S");
		$this->db->query("UPDATE anbieter_promote_log SET lparam=$credits, date_modified='$todayHour' WHERE anbieter_id=$anbieterId AND portal_id=$wisyPortalId AND event_type=2001;");
		if( $this->db->affected_rows() == 0 )
		{
			$this->db->query("INSERT INTO anbieter_promote_log (date_created, date_modified, anbieter_id, portal_id, kurs_id, event_type, lparam) VALUES ('$todayHour', '$todayHour', $anbieterId, $wisyPortalId, 0, 2001, $credits)");
		}
	}

	function setAllPromotionsActive($anbieterId, $active)
	{
		// should be called after credits are available again ...
		global $wisyPortalId;
		$today = strftime("%Y-%m-%d");
		if( $active )
		{
			$this->db->query( "UPDATE anbieter_promote SET promote_active=1 WHERE anbieter_id=$anbieterId AND portal_id=$wisyPortalId "
					.	" AND ((promote_mode='times' AND 0+promote_param>1) OR (promote_mode='date' AND promote_param>='$today'))" 
						);
		}
		else
		{
			$this->db->query( "UPDATE anbieter_promote SET promote_active=0 WHERE anbieter_id=$anbieterId AND portal_id=$wisyPortalId "
						);
		}
	}
};