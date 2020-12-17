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
loadWisyClass('WISY_MENU_CLASS');

class WISY_MENUCHECK_CLASS
{
	function __construct(&$framework, $param)
	{
		$this->framework		=& $framework;
		$this->statetable		= new WISY_SYNC_STATETABLE_CLASS($this->framework);
		$this->menuclass		= new WISY_MENU_CLASS($this->framework, array('prefix'=>''));
		$this->today_datetime   = strftime("%Y-%m-%d %H:%M:%S");
	}
	
	function render()
	{
		$overall_time = microtime(true);
		headerDoCache(0);
		header("Content-type: text/plain");
		
		$host = $_SERVER['HTTP_HOST'];
		
		// print common information
		$this->log(sprintf("menucheck script started\n"));
		
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
		$sql = "SELECT id, einstellungen, einstellungen_hinweise, bodystart FROM portale WHERE date_modified>='$lastcheck' AND status='1';";
		$db->query( $sql );
		
		while( $db->next_record() )
		{
			$portal_id 		= intval($db->f8('id'));
			$einstellungen  = $db->f8('einstellungen');
			$einstellungen_hinweise = $db->f8('einstellungen_hinweise');
			$bodystart = $db->f8('bodystart');
			
			$einstellungen_exploded = explodeSettings($einstellungen);
			$hinweise = "MENUCHECK $this->today_datetime\n";

			$this->log("-------------------- Portal $portal_id überprüfen ----------------------");
			
			// Collect menu items sorted by type
			$itemsToCheck = ['externalUrl' => [], 'glossar' => [], 'search' => []];
			
			// Loop over menus for which placeholders exist
			preg_match_all('/__MENU[A-Z0-9_]*__/', $bodystart, $menus);
			foreach($menus[0] as $menu) {
				$prefix = strtolower(str_replace('_', '', $menu));
				$allPrefix = $prefix . '.';
				$allPrefixLen = strlen($allPrefix);
				
				$this->log("\n+ Menu \"$prefix\" überprüfen ++++++++++++++");
				$hinweise .= "\n$prefix:";
				
				// Loop over menu entries
				foreach($einstellungen_exploded as $key => $value) {
					if( substr($key, 0, $allPrefixLen)==$allPrefix ) {
						$items = $this->menuclass->createItems($value, 0);
						foreach($items as $item) {
								$type = $this->getMenuType($item->url);
								if($type) {
									$itemsToCheck[$type][$key] = $item->url;
								} else {
									$this->log("!!! Unbekannter Menutype für $item->url");
								}
							if(count($item->children)) {
								foreach($item->children as $item) {
									$type = $this->getMenuType($item->url);
									if($type) {
										$itemsToCheck[$type][$key] = $item->url;
									} else {
										$this->log("!!! Unbekannter Menutype für $item->url");
									}
								}
							}
						}
					}
				}
			}
			
			$hinweise .= $this->checkExternalUrls($itemsToCheck['externalUrl']);
			
			$hinweise .= $this->checkGlossarEntries($itemsToCheck['glossar']);
			
			$hinweise .= $this->checkSearchRequests($itemsToCheck['search']);
			
			// Update einstellungen_hinweise
			$sql = "UPDATE portale SET einstellungen_hinweise='" . $hinweise . "', date_modified='" . $this->today_datetime . "' WHERE id=" . $portal_id;
			$db2->query($sql);
			
			$this->statetable->updateUpdatestick();
			// TODO: evtl. hier kurze Verzögerung vor nächstem Schleifendurchlauf einbauen
		}
		
		$this->statetable->writeState('lastcheck.menucheck', $this->today_datetime);
	}
	
	function getMenuType($url) {
		$link = trim($url);
		if($url == '') return false;
		
		// determine type of $link
		if(substr($url, 0, 7) == 'http://' || substr($url, 0, 8) == 'https://') {
			// External URL
			return 'externalUrl';
		
		} else if(substr($url, 0, 9) == 'search?q=') {
			// Search
			return 'search';
		
		} else if(preg_match('/^g\d+$/', $url)) {
			// Glossar
			return 'glossar';
		}
		
		$this->log("!!! Error: unknown MenuType: $url");
		return false;
	}
	
	function checkExternalUrls($urls) {
		$this->log("-> checking external URLs");
		$hinweise = '';
		foreach(array_unique($urls) as $key => $url) {
			// get HTTP headers for URL
			$headers = @get_headers($url);
			if($headers !== false) {
				foreach($headers as $header) {
					// corrects $url when 301/302 redirect(s) lead(s) to 200:
					if(preg_match("/^Location: (http.+)$/", $header, $m)) $url = $m[1];
					// grabs the last $header $code, in case of redirect(s):
					if(preg_match("/^HTTP.+\s(\d\d\d)\s/", $header, $m)) $code = $m[1];
				}
				if($code == '200') continue;
			}
			foreach(array_keys($urls, $url) as $key) {
				$this->log("$key / $url liefert keine Ergebnisse");
				$hinweise .= "\n - $key / $url liefert keine Ergebnisse";
			}
		}
		return $hinweise;
	}
	
	function checkGlossarEntries($urls) {
		$db = new DB_Admin;
		$this->log("-> checking glossar entries");
		
		$hinweise = '';
		$glossar_ids = [];
		foreach($urls as $key => $url) {
			$glossar_ids[] = intval(substr($url, 1));
		}
		$glossar_ids = array_unique($glossar_ids);
		
		if(count($glossar_ids)) {
			$db->query("SELECT id FROM glossar WHERE status=1 AND (erklaerung != '' OR wikipedia != '') AND id IN(" . implode(',', $glossar_ids) . ")");
			while($db->next_record()) {
				array_splice($glossar_ids, array_search($db->f8('id'), $glossar_ids), 1);
			}
			$db->free();
		}
		foreach($glossar_ids as $glossar_id) {
			$url = "g" . $glossar_id;
			foreach(array_keys($urls, $url) as $key) {
				$this->log("$key / $url liefert keine Ergebnisse");
				$hinweise .= "\n - $key / $url liefert keine Ergebnisse";
			}
		}
		return $hinweise;
	}
	
	function checkSearchRequests($urls) {
		$this->log("-> checking search requests");
		
		$hinweise = '';
		$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
		foreach($urls as $key => $url) {
			$search = urldecode(trim(substr($url, 9)));
			$searcher->prepare($search);
			$count = $searcher->getKurseCount();
			if($count == 0) {
				$this->log("$key / $search liefert keine Ergebnisse");
				$hinweise .= "\n - $key / $search liefert keine Ergebnisse";
			}
		}
		return $hinweise;
	}
	
	function log($str)
	{
		echo $str . "\n";
		flush();
		$this->framework->log('menucheck', $str);
	}
}
