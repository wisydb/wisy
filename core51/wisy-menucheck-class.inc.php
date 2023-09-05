<?php if( !defined('IN_WISY') ) die('!IN_WISY');

/*****************************************************************************
 * WISY_MENUCHECK_CLASS
 * automatische Überprüfung der Portal-Menüs auf tote Links
 *****************************************************************************
 
 Aufruf dieses Scripts:
 menucheck?all 		-	Überprüft alle Menülinks in dem aktiven Portal das
   						am längsten nicht mehr überprüft wurde
   
 menucheck?updated	-	Überprüft alle Menülinks in dem aktiven Portal das
 						am längsten nicht mehr überprüft wurde, aber limitiert
						auf Portale die seit dem letzten Menucheck noch mal
						geändert wurden

Zusaetzliche Parameter:
 &apikey=<apikey>	-	evtl. notwendiges Passwort, kann in den Portaleinstellungen
						der Domain unter apikey= definiert werden.
						Standardpasswort: none
 ?portal=<portalid>		optional ID des zu überprüfenden Portals. Parameter "updated"
 						und "all" werden in dem Fall ignoriert.

 */

require_once('admin/config/codes.inc.php');

loadWisyClass('WISY_SYNC_RENDERER_CLASS');
loadWisyClass('WISY_MENU_CLASS');

class WISY_MENUCHECK_CLASS
{
	function __construct(&$framework, $param)
	{   
		$this->framework		=& $framework;
		$this->statetable		= new WISY_SYNC_STATETABLE_CLASS($this->framework);
		$this->menuclass		= new WISY_MENU_CLASS($this->framework, array('prefix'=>''));
		$this->today_datetime   = ftime("%Y-%m-%d %H:%M:%S");
		
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
		
		if( isset($_GET['portal']) && intval($_GET['portal']) > 0 )
		{
			$this->doMenucheck('one', intval($_GET['portal']));
		}
		else if( isset($_GET['all']) )
		{
			$this->doMenucheck('all');
		}
		else if( isset($_GET['updated']) )
		{
			$this->doMenucheck('updated');
		}
		else
		{
			$this->log("********** ERROR: $host: unknown menucheck option, use one of \"all\" or \"updated\".");
		}
	}
	
	function doMenucheck($typeOfCheck, $portalId=0)
	{
		global $wisyPortalId;
		global $wisyPortalFilter;
		
		$db = new DB_Admin;
		$db2 = new DB_Admin;
		
		// make sure, this script does not abort too soon
		set_time_limit(2*60*60 /*2 hours ...*/);
		ignore_user_abort(true);
		
		// allocate exclusive access
		if( !$this->statetable->allocateUpdatestick() ) { $this->log("********** ERROR: $host: cannot menucheck now, update stick in use, please try again in about 10 minutes."); return; }
		
		$lastcheck = $this->statetable->readState('lastcheck.menucheck', '0000-00-00 00:00:00');
		$limit = 1;
		
		// Select portal settings
		if($typeOfCheck == 'one') {
			$sql = "SELECT id, einstellungen, einstellungen_hinweise, bodystart, filter FROM portale WHERE status='1' AND id='" . $portalId . "' LIMIT 1";
		} else if($typeOfCheck == 'all') {
			$sql = "SELECT id, einstellungen, einstellungen_hinweise, bodystart, filter FROM portale WHERE status='1' ORDER BY date_modified ASC LIMIT $limit;";
		} else {
			$sql = "SELECT id, einstellungen, einstellungen_hinweise, bodystart, filter FROM portale WHERE date_modified>='$lastcheck' AND status='1' ORDER BY date_modified ASC LIMIT $limit;";
		}
		$db->query( $sql );
		
		while( $db->next_record() )
		{
			$portal_id 		= intval($db->f8('id'));
			$einstellungen  = $db->f8('einstellungen');
			$einstellungen_hinweise = $db->f8('einstellungen_hinweise');
			$bodystart = $db->f8('bodystart');
			
			// Set global portal ID for correct search context
			$wisyPortalId = $portal_id;
			$wisyPortalFilter = explodeSettings($db->fs('filter'));
			
			$einstellungen_exploded = array_map("trim", explodeSettings($einstellungen));
			$hinweise = "++ MENUCHECK $this->today_datetime ++\n";
			

			$this->log("-------------------- Portal $portal_id überprüfen ----------------------");
			
			// Collect menu items sorted by type
			$itemsToCheck = ['externalUrl' => [], 'glossar' => [], 'search' => []];
			
			// Loop over menus for which placeholders exist
			$additional_hints = array();
			preg_match_all('/__MENU[A-Z0-9_]*__/', $bodystart, $menus);
			foreach($menus[0] as $menu) {
				$prefix = strtolower(str_replace('_', '', $menu));
				$allPrefix = $prefix . '.';
				$allPrefixLen = strlen($allPrefix);
				
				$this->log("\n+ Menu \"$prefix\" überprüfen ++++++++++++++");
				$hinweise .= "\n$prefix:";
				
				// Loop over menu entries
				foreach($einstellungen_exploded as $key => $value) {
				    
				    if($key == "intellisearch.fulltext" && (intval($value) === 1 || intval($value) === 2)) {
				        $additional_hints['intellisearch'] = "\n\n(HINWEIS: In diesem Portal ist die automatische Weiterleitung auf Volltextsuche aktiv!)\n";
				    }
				    
					if( substr($key, 0, $allPrefixLen)==$allPrefix ) {
						$items = $this->menuclass->createItems($value, 0);
						foreach($items as $item) {
							$type = $this->getMenuType($item->url);
							if($type) {
								if($type != 'ignore') $itemsToCheck[$type][$key] = $item->url;
							} else {
								$this->log("!!! Unbekannter Menutype für $key: '$item->title' / '$item->url'");
							}
							if(count($item->children)) {
								foreach($item->children as $item) {
									$type = $this->getMenuType($item->url);
									if($type) {
										if($type != 'ignore') $itemsToCheck[$type][$key] = $item->url;
									} else {
										$this->log("!!! Unbekannter Menutype für $key: '$item->title' / '$item->url'");
									}
								}
							}
						}
					}
				}
			}
			
			$this->log( implode("\n", $additional_hints) );
			$hinweise = str_replace(' ++', ' ++'.implode("\n", $additional_hints), $hinweise);
			
			$hinweise .= $this->checkExternalUrls($itemsToCheck['externalUrl']);
			
			$hinweise .= $this->checkGlossarEntries($itemsToCheck['glossar']);
			
			$hinweise .= $this->checkSearchRequests($itemsToCheck['search']);
			
			// Update einstellungen_hinweise
			$journal_entry = date('d.m.y').": ".utf8_decode('Automatische Menü-Prüfung (WISY)'); // prepend to journal
			$sql = "UPDATE portale SET einstellungen_hinweise='" . $hinweise . "', date_modified='" . $this->today_datetime . "', notizen=CONCAT('".$journal_entry."\n',notizen), user_modified=".USER_MENUCHECK." WHERE id=" . $portal_id; 
			$db2->query($sql);
			
			$this->statetable->updateUpdatestick();
		}
		
		$this->statetable->writeState('lastcheck.menucheck', $this->today_datetime);
		
		// release exclusive access
		$this->statetable->releaseUpdatestick();
	}
	
	function getMenuType($url) {
		$url = trim($url);

		if($url == '' || $url == ';' || $url == '/' || $url == 'search' || $url == 'edit') {
			return 'ignore';
		}
		
		// determine type of $url
		if(substr($url, 0, 1) == '#') {
			// #
			return 'ignore';
			
		} else if(substr($url, 0, 11) == 'javascript:') {
			// javascript:
			return 'ignore';
			
		} else if(substr($url, 0, 7) == 'http://' || substr($url, 0, 8) == 'https://') {
			// External URL
			return 'externalUrl';
		
		} else if( (substr($url, 0, 7) == 'search?' && strpos($url, 'q=') !== FALSE ) // substr($url, 0, 9) == 'search?q=' || substr($url, 0, 10) == '/search?q=' ||
		    || ( substr($url, 0, 8) == '/search?' && strpos($url, 'q=') !== FALSE )
		    || ( substr($url, 0, 7) == 'search?' && strpos($url, 'qs=') !== FALSE ) 
		    || ( substr($url, 0, 8) == '/search?' && strpos($url, 'qs=') !== FALSE )
		    || ( substr($url, 0, 7) == 'search?' && strpos($url, 'qf=') !== FALSE ) 
		    || ( substr($url, 0, 8) == '/search?' && strpos($url, 'qf=') !== FALSE )
		    ) {
		    // needs to be treated as External URL
		    return 'search';
		    
		} else if(preg_match('/^\/?g\d+/', $url)) {
			// Glossar
			return 'glossar';
		}
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
				$this->log("$key / ".utf8_encode($url)." liefert keine Ergebnisse.");
				$hinweise .= "\n - $key / ".$url." liefert keine Ergebnisse.";
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
			preg_match('/^\/?(g\d+)/', $url, $matches);
			$glossar_ids[] = intval(substr($matches[1], 1));
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
				$this->log("$key / ".utf8_encode($url)." liefert keine Ergebnisse..");
				$hinweise .= "\n - $key / ".$url." liefert keine Ergebnisse..";
			}
		}
		return $hinweise;
	}
	
	function checkSearchRequests($urls) {
		$this->log("-> checking search requests");
		
		$hinweise = '';
		$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
		foreach($urls as $key => $url) {
			$url = urldecode(trim($url));
			if(substr($url, 0, 1) == '/') {
				$search = urldecode(trim(substr($url, 8)));
			} else {
				$search = urldecode(trim(substr($url, 7)));
			}
			
			parse_str($search, $params);
			
			if(isset($params['q']))
			 $search_query = $params['q'];
			elseif(isset($params['qs']))
			 $search_query = $params['qs'];

			$searcher->prepare($search_query);
			 
			$count = $searcher->getKurseCount();
			if($count == 0) {
			    $search = preg_replace("/^q=/i", '', $search);
			    $this->log("$key / ".utf8_encode($search)." liefert keine Ergebnisse...");
				$hinweise .= "\n - $key / ".$search." liefert keine Ergebnisse...";
			}
		}
		return $hinweise;
	}
	
	function log($str)
	{
	    echo utf8_decode( $str ) . "\n";
		flush();
		$this->framework->log('menucheck', $str);
	}
}
