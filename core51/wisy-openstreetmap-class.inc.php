<?php


/*****************************************************************************
 * OpenStreetMap for WISY & Co
 * This implementation allows only one map per page, however, this map
 * can have any number of locations.
 *****************************************************************************

usage: 
	$obj = new WISY_OPENSTREETMAP_CLASS()
	$obj->addPoint2(array(<ort>), <durchfuehrung_id>);
	echo $obj->render();
	
for geocoding, the following will work:
	$obj = new WISY_OPENSTREETMAP_CLASS()
	$ret = $obj->geocode2(array('free'=><query>); // result in $ret['lat']/$ret['lng']/$ret['error']
	
 
 *****************************************************************************
 * Written By Bjoern Petersen
 * Copyright (C) 2006-2010 by Bjoern Petersen Software Design and Development
 *****************************************************************************/


// do not, - NEVER - use  G_GEOCODE in modules!!
// instead use WISY_OPENSTREETMAP_CLASS as described in the header.
class G_GEOCODE
{
    private $nominatim_url;
    private $nominatim_params;
    private $framework;
    
    function __construct(&$framework) {
        $this->framework = $framework;
    }
	
	function xml_elem_start($parser, $name, $attribs)
	{
		if( $name == 'PLACE' ) {
			if( !is_array($this->geocode_ret) ) {
				$this->geocode_ret = array();
				$this->geocode_ret['lat'] = $attribs['LAT'];
				$this->geocode_ret['lng'] = $attribs['LON'];
			}
		}
		
	}
	
	function xml_elem_end($parser, $name)
	{
	}
	
	function set_nominatim_url($url)
	{
		$this->nominatim_url = $url;
	}
	
	function get_nominatim_url()
	{
		return $this->nominatim_url;
	}
		
	// function returns ... 
	//		... array('lat=>53.55, 'lng'=>10.00) on success
	//		... array('error'=>'short error message') on failure
	function geocode($q_arr)
	{
	    $this->nominatim_url = 'https://nominatim.openstreetmap.org/search';
	    $this->nominatim_params = '?format=xml&limit=3&accept-language=de';
	    
	    $alternate_geocoder = $this->framework->iniRead('nominatim.alternate.geocoder', '');
	    $nominatim_key = $this->framework->iniRead('nominatim.key', '');
	    $nominatim_url = $this->framework->iniRead('nominatim.url', '');
	    $this->nominatim_explicit_city = $this->framework->iniRead('nominatim.explicit.city', '');
	    
	    if(!$alternate_geocoder || !$nominatim_url)
	        return array('error'=>'err_geocode_missing_good_geocoder', 'url'=>'not called');
	        
	    $this->nominatim_url = $nominatim_url;
	    $this->nominatim_params .= '&key='.$nominatim_key;
	        
	    if( $GLOBALS['geocode_called'] >= 5500 ) { return array('error'=>'err_geocode_toomanycalls ('.$GLOBALS['geocode_called'].')', 'url'=>'not called'); }
	    $GLOBALS['geocode_called']++;
		
	    // build query parameters string
	    $param = '';
	    if( $q_arr['street']!='' || $q_arr['postalcode']!='' || $q_arr['city']!='' || $q_arr['country']!='' ) {
	        if( $q_arr['street']    !='' ) { $param .= '&street='     . urlencode(cs8($q_arr['street']    )); }
	        if( $q_arr['postalcode']!='' ) { $param .= '&postalcode=' . urlencode(cs8($q_arr['postalcode'])); }
	        if( $q_arr['city']      !='' ) { $param .= '&city='       . urlencode(cs8($q_arr['city']      )); }
	        if( $q_arr['country']   !='' ) { $param .= '&country='    . urlencode(cs8($q_arr['country']   )); }
	    }
	    else if( $q_arr['free']!='' ) {
	        $place_search = urlencode(cs8($q_arr['free']));
	        if($this->nominatim_explicit_city && stripos($place_search, $this->nominatim_explicit_city) === FALSE && stripos($place_search, ",") === FALSE)
	            $place_search = $place_search.",+".$this->nominatim_explicit_city;
	            
	            $param .= '&q=' . $place_search;
	    }
	    else {
	        return array('error'=>'err_geocode_param', 'url'=>'none');
	    }
	
		// read data - Usage Policy: https://wiki.openstreetmap.org/wiki/Nominatim_usage_policy
		//	"Usage triggered by searches of the users are okay, 
		//	Howver, no Heavy usage: max. 1 Request/s (for all users together)"
		// to determinate the standard zoom, we could use the bounding box or sth. as the type (the XML-result gives much more information than just the lat/lng)
		if($this->nominatim_explicit_city && stripos($param, $this->nominatim_explicit_city) === FALSE && stripos($param, ",") === FALSE)
		  $param = $param.",+".$this->nominatim_explicit_city;
		    
		$url = $this->nominatim_url . $this->nominatim_params.'&q='.$param;
		$url = str_replace('&q=&q=', '&q=', $url);
		    
		if (!($fp = @fopen($url, "r"))) {
		  return array('error'=>'err_geocode_fopen ('.$url.')', 'url'=>$url);
		}
      
		$data = '';
		$loopprotect = 0;
		while( $chunk=@fread($fp, 4096) ) {
			$data .= $chunk;
			$loopprotect ++;
			if( $loopprotect > 1000 ) { return array('error'=>'err_geocode_fread', 'url'=>$url); }
		}

		// parse XML result
		$this->geocode_ret = false;
		$parser = xml_parser_create('UTF-8');
		xml_set_object($parser, $this);
		xml_set_element_handler($parser, 'xml_elem_start', 'xml_elem_end');
		if( !@xml_parse($parser, $data, true) ) {
			xml_parser_free($parser);
			return array('error'=>'err_geocode_xmlparse', 'url'=>$url);
		}
		xml_parser_free($parser);
		
		if( !is_array($this->geocode_ret) ) {
		    return array('error'=>'err_geocode_badxml', 'url'=>$url);
		}
		
		// succes - example returns:
		return $this->geocode_ret;
	}
	
	
};




 

// the main maps class
// usage:
// $o = new WISY_OPENSTREETMAP_CLASS()
// $o->addPoint('Lange Reihe 81', '', 'Hamburg', 'Deutschland', 1234 /*Df Id*/);
// echo $o->render()
class WISY_OPENSTREETMAP_CLASS
{
	var $framework;			// global framework object
	var $points;			// $this->points will keep all adresses/points to mark
	
	const MAX_EXT_MARKER_LOOKUP = 1;
	
	function __construct(&$framework)
	{
		$this->framework	=& $framework;
		$this->points		= array();
	}
	
	function normaliseCountry($country)
	{
		// as there are very inconsistent town/country values in WISY, 
		// here is a little helper array to correct the most common mistakes.
		// if there is nothing to correct, the empty string is returned.
		global $g_countryTransl;
		if( !is_array($g_countryTransl) )
		{
			$g_countryTransl = array
			(
				/*lower abbr*/	/*full engl., sorted by this column*/
				'australien'	=>	'Australia',
				'kuba'			=>	'Cuba',
				'ägypten'		=>	'Egypt',
				'f'				=>	'France',
				'd'				=>	'Germany',
				'i'				=>	'Italy',
				'j'				=>	'Japan',
				'libyen'		=>	'Libya',
				'mexiko'		=>	'Mexico',
				'neuseeland'	=>	'New Zealand',
				'pl'			=>	'Poland',
				'polen'			=>	'Poland',
				'p'				=>	'Portugal',
				'r'				=>	'Russia',
				'russl.'		=>	'Russia',
				'russland'		=>	'Russia',
				'spanien'		=>	'Spain',
				'e'				=>	'Spain',
				's'				=>	'Sweden',
				'tr'			=>	'Turkey',
				'türkei'		=>	'Turkey',
				'gb'			=>	'UK',
				'großbrit'		=>	'UK',
				'großbrit.'		=>	'UK',
				'großbritanien'	=>	'UK',
				'us'			=>	'USA',
			);
			$temp = $g_countryTransl;
			reset($temp);
			foreach($temp as $n => $v)
			{
				$g_countryTransl[ strtolower($v) ] = $v; // also add the english names in lower-case to the table
			}
		}
		
		if( isset($g_countryTransl[ strtolower($country) ]) )
			return $g_countryTransl[ strtolower($country) ];
		else
			return '';	
	}
	
	function clearPoints()
	{
		$this->points = array();
	}	
	
	function addPoint2($adr_arr, $dfid=0)
	{
		if( $dfid ) {
			$adr_arr['dfid'] = $dfid;
		}
		$this->points[] = $adr_arr;
	}

	function hasPoints()
	{
	    return count((array) $this->points)>0;
	}
	
	function render()
	{
		// a call for geocoding an address, return it as JSON (for security reasons, we do not allow to geocode any address but only addresses defined by a Durchfuehrungs-ID (dfid))
		// (we get here if cached geocoding does not work at [*] below)
		// --------------------------------------------------------------------------------
	
		if( isset($_REQUEST['geocodedfid']) ) {
			$db = new DB_Admin;
			$db->query("SELECT strasse,plz,ort,stadtteil,land FROM durchfuehrung WHERE id=".intval($_REQUEST['geocodedfid']));
			if( !$db->next_record() ) {
				echo '{"error": "dfid_not_found"}'; 
				exit();
			}
			$coord = $this->geocode2__($db->Record, 'perm', true /*allow external call, see marks below*/);
			if( $coord['error'] || !isset($coord['lat']) || !isset($coord['lng']) ) {
				echo '{"error": "geocode"}'; 
				exit();
			}
			echo '{"lat": '.floatval($coord['lat']).',"lng": '.floatval($coord['lng']).'}'; 
			
			$db->close();
			return;
		}
		
		// a call for embedding a map, return a JavaScript/HTML snippet that does the stuff
		// --------------------------------------------------------------------------------
		// we do not allow geocode2__() to call the external geocoder here because:
		// - if sites are visited by robots, this will lead to heavy load on the external geocoder
		// - the usage policy only allows "user requests" being decoded
		// - for speed reasons - so the user need not to wait until the geocoder is ready
		//
		// on the other hand, if we have alredy  cached the geocoding result, we can save a call though javascript
		// - faster loading of the page in this case as one http-call less
		$markers = array();
		$added_hash = array();
		$ext_marker_lookup = 0;
		foreach( $this->points as $point )
		{
			$html = $point['strasse']; 
			if( $point['ort'] ) { $html .= ($html?', ':'') . $point['ort']; }
		
			$coord = $this->geocode2__($point, 'perm', false /*no external call, see marks above and [*] */);
			if( $coord['error']=='err_notincache' ) {
				if( $ext_marker_lookup < WISY_OPENSTREETMAP_CLASS::MAX_EXT_MARKER_LOOKUP
				 && $point['dfid'] ) {
					array_unshift($markers, array('html'=>$html, 'dfid'=>$point['dfid'], 'lat'=>0, 'lng'=>0)); // js expects missing coordinates being first
					$ext_marker_lookup++;
				}
			}
			else if( !$coord['error'] && isset($coord['lat']) && isset($coord['lng']) ) {
				if( !$added_hash[ $coord['lat'].$coord['lng'] ] ) {
					$markers[] = array('html'=>$html, 'dfid'=>0, 'lat'=>$coord['lat'], 'lng'=>$coord['lng']);
					$added_hash[ $coord['lat'].$coord['lng'] ] = 1;
				}
			}
		}

		if( $this->framework->iniRead('map.disable', '') || count((array) $markers) == 0 ) {
			return '';
		}
		
		$marker_js = '';
		foreach( $markers as $i => $marker ) {
				$marker_js	.=	"osm_mark_dfid[$i]=".intval($marker['dfid'])."; "
							.	"osm_mark_lat[$i]=".floatval($marker['lat'])."; "
							.	"osm_mark_lng[$i]=".floatval($marker['lng'])."; "
							.	"osm_mark_html[$i]='".strtr($marker['html'], "'\"<>&", "     ")."'; ";
		}

		// decide, which library to use
		$ret  = "\n"
			.	"<link property=\"stylesheet\" rel=\"stylesheet\" href=\"core20/lib/leaflet-0.7/leaflet.css\" />\n"
			.	"<link property=\"stylesheet\" rel=\"stylesheet\" href=\"core20/wisy-openstreetmap-3.css\" />\n"
			.	"<script src=\"core20/lib/leaflet-0.7/leaflet.js\"></script>\n" // type is required in html4 but not in html5
			.	"<script src=\"core20/wisy-openstreetmap-3.js\"></script>\n"
			.	"<script>\n  $marker_js\n  osm_map_here();\n</script>\n";

		return $ret;
	}

	// geocode2() may be used from external, however, use this function ONLY by user requests (robots crawling and stating this function are no user requests!)
	function geocode2($adr, $call_external = true)
	{
		return $this->geocode2__($adr, 'temp', $call_external);
	}
	/*
	function geocode2perm($adr, $call_external = true) // only needed to fill up the database; do not forget to comment out the err_geocode_toomanycalls above
	{
		return $this->geocode2__($adr, 'perm', $call_external);
	}
	*/
		
	// strict private, may not be overwritten or used in modules
	private function geocode2__($adr, $addto, $call_external)
	{
		if( !is_array($adr) ) { die('the query must be given as an array!'); }

		// convert $adr=array('strasse'=>, 'plz'=>, 'ort'=>, 'stadtteil'=>, 'land'=>), optional $adr=array('free'=>)
		//      to $q_arr=array('street'=>, 'postalcode'=>, 'city'=>, 'country'=>''), optional $q_arr=array('free'=>)
		$q_arr = array();
		if( $adr['free'] ) {
			$q_arr['free'] = $adr['free'];
		}
		else {
			$strasse	= $adr['strasse'];
			$plz		= $adr['plz'];		
			$ort		= $adr['ort'];
			$stadtteil	= $adr['stadtteil'];
			$land		= $adr['land'];
			if( $strasse=='' && $ort=='' ) { return array('error'=>'err_nostreetnocity'); }

			// Remove venue descriptions (i.e. "Ecke ...strasse")
			$p = strpos($strasse, '('); if( $p ) { $strasse = trim(substr($strasse, 0, $p)); }
			
			// or additions like "Weser" from "Nienburg/Weser"
			$p = strpos($ort, '/'); if( $p ) { $ort = trim(substr($ort, 0, $p)); }

			// ... remove zip codes like 00000
			if( intval($plz) == 0 ) { $plz = ''; }

			// Addition not always country, i.e. Quito/Ecuador 
			/*
			if( substr_count($ort, '/')==1 ) {
				$temp = explode('/', $ort);
				if( $land == '' ) {
					$ort = $temp[0];
					$land = $temp[1];
				}
				else if( $this->normaliseCountry($temp[1]) ) {
					$ort = $temp[0];
					$land = $this->normaliseCountry($temp[1]);
				}
			} */
			
			// correct ort, land, exchange if necessary
			if( $this->normaliseCountry($ort) ) { $land  = $this->normaliseCountry($ort); $ort = ''; }
			if( $this->normaliseCountry($land) ) { $land = $this->normaliseCountry($land); }
			if( $land == '' ) { $land = 'Deutschland'; }	
			if( $ort == '' && $stadtteil != '' ) { $ort = $stadtteil; $stadtteil = ''; }
			
			// all converted
			$q_arr = array('street'=>$strasse, 'postalcode'=>$plz, 'city'=>$ort, 'country'=>$land);
		}
		
		// build a unique key as "street=foo;city=bar" or "free=foo,bar;"
		$q_key = '';
		ksort($q_arr);
		foreach($q_arr as $key=>$val) {
			if( $val!= '' ) {
				$q_key .= "$key=$val;";
			}
		}

		// create cache object, if not yet done
		if( !is_array($this->cache) )
		{
			$this->cache = array();
			$this->cache['perm'] =& createWisyObject('WISY_CACHE_CLASS', $this->framework, array('table'=>'x_cache_latlng_perm', 
					'itemLifetimeSeconds'=>0 ));			// forever lifetime
			$this->cache['temp'] =& createWisyObject('WISY_CACHE_CLASS', $this->framework, array('table'=>'x_cache_latlng_search', 
					'itemLifetimeSeconds'=>60*60*24*14 ));	// 14 days lifetime
		}

		// is the query in one of the cache objects?
		$test = $this->cache['perm']->lookup($q_key);
		if( $test == '' ) {
			$test = $this->cache['temp']->lookup($q_key);
		}
		
		if( $test != '' ) {
			if( ($test = unserialize($test))!==false ) {
				if( (isset($test['lat'])&&isset($test['lng'])) || isset($test['error']) ) {
					$test['cache'] = 1;
					return $test; // successfully read from cache
				}
			}
		}
	
		if( !$call_external ) {
			return array('error'=>'err_notincache');
		}
	
		// call geocoder
		$obj = new G_GEOCODE($this->framework);
		if( $addto == 'temp' ) {
			//$obj->set_nominatim_url('https://open.mapquestapi.com/nominatim/v1/search'); -- 2015-09-28 15:48 this server has problems, stay on default
		}
		$coord = $obj->geocode($q_arr);
		//$coord['dbg_geocoder'] = $obj->get_nominatim_url();
		
		// add result to cache object
		if( $coord['error'] ) {
			$addto = 'temp';
		}
		$this->cache[$addto]->insert($q_key, serialize($coord));
		
		// done
		return $coord;
	}
	
	// deprecated stuff:
	function addPoint($strasse, $plz, $ort, $land, $addInfo='') {$this->addPoint2(array('strasse'=>$strasse, 'plz'=>$plz, 'ort'=>$ort, 'land'=>$land), $addInfo);}
	function geocodeCached($q) { $x=$this->geocode2(array('free'=>$q)); return $x['error']? array('status'=>404) : array('status'=>200, 'latlng'=>($x['lat'].','.$x['lng'])); }
	function geocode($q) {return geocodeCached($q);}

}

