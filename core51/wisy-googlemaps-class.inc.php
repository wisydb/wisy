<?php

/*  Google Maps support is deprecated;
please refer to WISY_OPENSTREETMAP_CLASS instead! (bp, 14.11.2013) */		
		

/*****************************************************************************
 * Google Maps API for WISY & Co
 * This implementation allows only one map per page, however, this map
 * can have any number of locations.
 *****************************************************************************
 * You can configure the map using
 * map.disable = 1
 * map.apikey.<host> = <apikey>
 *****************************************************************************
 * Written By Bjoern Petersen
 * Copyright (C) 2006-2010 by Bjoern Petersen Software Design and Development
 *****************************************************************************/



// the main google maps class
// usage:
// $o = new WISY_GOOGLEMAPS_CLASS()
// $o->addPoint('Lange Reihe 81', '', 'Hamburg', '');
// echo $o->render()
class WISY_GOOGLEMAPS_CLASS
{
	var $framework;			// global framework object
	var $adr;				// $this->adr will keep all adresses and points
	var $apiKey;			// the Google API key
	
	function __construct(&$framework)
	{
		$this->framework	=& $framework;
		$this->adr			= array();

		$host = $_SERVER['HTTP_HOST'];
		$this->apiKey = $this->framework->iniRead("map.apikey.$host", '');
		if( $this->apiKey == '' )
		{
			if( substr($host, -15) == 'kursportal.info' ) // matches all subdomains from kursportal.info
			{
				$this->apiKey = 'ABQIAAAA6EL8ji24reUydgRZQV2PFxT9v4tf-t5f7Fo7w4DP1Op3q2FhrRSvCepUyxkGE6UpfWWxwTuaOYpIAQ';
			}
			else if( substr($host, 0, 11) == 'diskstation' ) // matches diskstation without port
			{
				$this->apiKey = 'ABQIAAAA6EL8ji24reUydgRZQV2PFxTMneKC0PkbnJ0qkvAXJtt8n1BxqxTzQqYqL9XmbNbuGGZW8vr1m6V3CA';
			}
			else if( substr($host, 0, 33) == 'kursportal.domainfactory-kunde.de' ) // matches the subdomain used for our sync
			{
				$this->apiKey = 'ABQIAAAA6EL8ji24reUydgRZQV2PFxQsL6ErBOLrJxjZT2oCdEnaJJu5RBTRfO3lmrLpnsRw7pYY1ENuRRBxfw';
			}
		}
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
	
	function cleanStr($str)
	{
		$str = strtr($str, array("\""=>" ", "'"=>" "));
		return $str;
	}
	
	function shortenStr($str)
	{
		$maxLen = 28;
		if( strlen($str) > $maxLen+4 )
		{
			$temp = explode(' ', $str);
			$str = '';
			for( $i = 0; $i < sizeof($temp); $i++ )
			{
				$str .= $temp[$i] . ' ';
				if( strlen($str) >= $maxLen && $i!=sizeof($temp)-1 )
				{
					$str .= '...';
					break;
				}
			}
		}
		return $str;
	}
	
	function clearPoints()
	{
		$this->adr = array();
	}	
	
	function addPoint($strasse, $plz, $stadt, $land, $addInfo='')
	{
		if( $strasse || $stadt || $land )
		{
			// the different zoom levels
			$zoomCountryLevel	= 4;
			$zoomTownLevel		= 7;
			$zoomPlzLevel		= 11;
			$zoomStreetLevel	= 15;
			
			// init
			$highQ 		= '';
			$midQ 		= '';
			$lowQ 		= '';
			$highQZoom	= $zoomCountryLevel; // lowQ zoom - country level

			// some basic corrections
			
			// zusätze bei der straße weglassen (etwa "Ecke Mateos Gag")
			$p = strpos($strasse, '(');
			if( $p )
			{
				$strasse = trim(substr($strasse, 0, $p));
			}
			
			// ... manchmal wird die PLZ als 00000 angegeben
			if( intval($plz) == 0 )
				$plz = '';
			
			// Quito/Ecuador o.ä. als Stadt ...
			if( substr_count($stadt, '/')==1 )
			{
				$temp = explode('/', $stadt);
				if( $land == '' )
				{
					$stadt = $temp[0];
					$land = $temp[1];
				}
				else if( $this->normaliseCountry($temp[1]) )
				{
					$stadt = $temp[0];
					$land = $this->normaliseCountry($temp[1]);
				}
			}
			
			if( $this->normaliseCountry($stadt) )
			{
				$land  = $this->normaliseCountry($stadt);
				$stadt = '';
			}
	
			// ... einige Abk. auflösen
			if( $this->normaliseCountry($land) )
				$land = $this->normaliseCountry($land);

			// Create "High Quality" Address
			if( $strasse )
			{
				$highQ = $strasse;
				$highQ = str_replace('Willy-Brandt-'/*kennt google noch nicht*/, 'Ost-West-', $highQ);
				
				$descr = $strasse;
				$highQZoom = $zoomStreetLevel;
			}
			
			if( $stadt )
			{
				$highQ .= $highQ? ', ' : '';
				if($plz)
				{
					$highQ .= $plz;
					$highQ .= $highQ? ' ' : '';
					if( $highQZoom < $zoomPlzLevel )
						$highQZoom = $zoomPlzLevel;
				}
				$highQ .= $stadt;
				if( $highQZoom < $zoomTownLevel )
					$highQZoom = $zoomTownLevel;
				
				$descr .= $descr? ', ' : '';
				$descr .= $stadt;
			}

			if( $land )
			{
				$highQ .= $highQ? ', ' : '';
				$highQ .= $land;
				
				if( $descr == '' )
				{
					$descr = $land;
				}
			}
			
			if( $highQ == '' )
			{
				return;
			}
			
			// Create "Mid" and "Low Quality" Addresses
			$midQZoom = $zoomTownLevel;
			if( $stadt && $land )
			{
				$midQ = "$stadt, $land";
				$lowQ = "$land";
				if( $midQ == $highQ )
				{
					$midQ = $lowQ;
					$lowQ = '';
				}
			}
			else if( $land )
			{
				$midQ = $land;
				if( $midQ == $highQ )
				{
					$midQ = '';
				}
			}
			else
			{
				$midQ = "$highQ, Germany";
				$midQZoom = $highQZoom;
			}
			
			// already added? if so, just add the additional information to the existing point
			for( $i = 0; $i < sizeof((array) $this->adr); $i++ )
			{
				if( $this->adr[$i]['highQ'] == $this->cleanStr($highQ) )
				{
					if( $addInfo )
					{
						if( substr_count($this->adr[$i]['descr'], '<br />') >= 2 )
						{
							$more = 'Weitere Termine: s.o.';
							if( !strpos($this->adr[$i]['descr'], $more) )
								$this->adr[$i]['descr'] .= '<br />' . $more;
							return;
						}
						else
						{
							$this->adr[$i]['descr'] .= $this->adr[$i]['descr']? '<br />' : '';
							$this->adr[$i]['descr'] .= $this->cleanStr($addInfo);
						}
					}
					return;
				}
			}
			
			// add additional information to description, done.
			if( $addInfo )
			{
				$descr .= $descr? '<br />' : '';
				$descr .= $this->cleanStr($addInfo);
			}
			
			$this->adr[] = array(
									'highQ'		=>	$this->cleanStr($highQ),
									'highQZoom'	=>	$highQZoom,
									'midQ'		=>	$this->cleanStr($midQ),
									'midQZoom'	=>	$midQZoom,
									'lowQ'		=>	$this->cleanStr($lowQ),
									'lowQZoom'	=>	$zoomTownLevel,
									'descr'		=>	$this->cleanStr($descr),
								);
		}
	}

	function hasPoints()
	{
	    return sizeof((array) $this->adr)>0;
	}
	
	function render()
	{
		if( $this->framework->iniRead('map.disable', '') )
			return '';
		
		$ret = "";
		if( sizeof((array) $this->adr) == 0 )
		{
			return '';
		}
		else
		{
			$allInfo = '';
			for( $i = 0; $i < sizeof((array) $this->adr); $i++ )
			{
				$nexti = $i+1;
				if( $nexti >= sizeof((array) $this->adr) ) $nexti = 0;
				
				$nextShortDescr = isset($this->adr[$nexti]['descr']) ? strval($this->adr[$nexti]['descr']) : '';
				$p=strpos($nextShortDescr, '<br />');
				if($p!==false)
					$nextShortDescr = substr($nextShortDescr, 0, $p);
				
				if( sizeof((array) $this->adr) > 1 )
				{
				    $this->adr[$i]['descr'] .= '<br /><br /><small><a title="'.htmlentities($nextShortDescr).'" href="javascript:gm_panToNext();">N&auml;chster Ort...</small></a>';
				}
				
				$allInfo .= "gm_allAdr[$i]='{$this->adr[$i]['highQ']}';gm_allDescr[$i]='{$this->adr[$i]['descr']}';";
			}
		}

		$ret .= "
				<script src=\"https://maps.google.com/maps?file=api&amp;v=2&amp;key={$this->apiKey}\" type=\"text/javascript\"></script>
				<script src=\"wisy-googlemaps.js\" type=\"text/javascript\"></script>
				<script type=\"text/javascript\"><!--
					var gm_allAdr=new Array;var gm_allDescr=new Array;$allInfo
					var gm_initAdr=new Array;gm_initAdr[0]=gm_allAdr[0];gm_initAdr[1]='{$this->adr[0]['midQ']}';gm_initAdr[2]='{$this->adr[0]['lowQ']}';
					var gm_initZoom=new Array;gm_initZoom[0]={$this->adr[0]['highQZoom']};gm_initZoom[1]={$this->adr[0]['midQZoom']};gm_initZoom[2]={$this->adr[0]['lowQZoom']};
					gm_mapHere();
				//--></script>
				";

		return $ret;
	}
	
	
	
	// geocoding
	// -----------------------------------------------------------------------

	function geocodeCached($q)
	{
		// same functionality as geocode() but with the use of an 2-hour-cache
		if( !is_object($this->geocode_cache) )
			$this->geocode_cache =& createWisyObject('WISY_CACHE_CLASS', $this->framework, array('table'=>'x_cache_latlng_search', 'itemLifetimeSeconds'=>48*60*60 ));

		$ret = $this->geocode_cache->lookup($q);
		if( $ret == '' )
		{
			$ret = $this->geocode($q);
			$this->geocode_cache->insert($q, serialize($ret));
		}
		else
		{
			$ret = unserialize($ret);
		}
		
		return $ret;
	}
	
	function geocode($q)
	{	
		$this->ret = array();
		
		$this->inCoordinates	= false;
		$this->inStatusCode		= false;
		
		$this->xml_obj = xml_parser_create('UTF-8');
		xml_set_object($this->xml_obj, $this);
		xml_set_character_data_handler($this->xml_obj, 'geocode_dataHandler');
		xml_set_element_handler($this->xml_obj, 'geocode_startHandler', 'geocode_endHandler');
		
		//$q = "scheplerstr. 4, hamburg";
		$url = 'https://maps.google.com/maps/geo?q='.urlencode(cs8($q)).'&output=xml&key=' . $this->apiKey;
		if (!($fp = @fopen($url, "r"))) {
			return false;
		}
      
		while ($data = @fread($fp, 4096)) {
			if (!@xml_parse($this->xml_obj, $data, feof($fp))) {
				xml_parser_free($this->xml_obj);

				if( !isset($this->ret['latlng']) )
				{
					if( preg_match('#<coordinates[^>]*>(.*)</coordinates[^>]*>#is', $data, $matches) )
						$this->ret['latlng'] = trim($matches[1]);
				}
				
				if( !isset($this->ret['latlng']) )
				{
					if( $this->ret['status'] == 200 ) $this->ret['status'] = 500;
					return $this->ret;
				}
				else
				{
					$this->ret['status'] = 200; 
					return $this->ret;
				}
			}
		}
      
      	xml_parser_free($this->xml_obj);
      	
      	return $this->ret;
	}

	function geocode_startHandler($parser, $name, $attribs)
	{
		if( $name == 'CODE' )
		{
			$this->inStatusCode = true;
		}
		else if( $name == 'ADDRESSDETAILS' )
		{
			$this->ret['accuracy'] = $attribs['ACCURACY'];
		}
		else if( $name == 'COORDINATES' )
		{
			$this->inCoordinates = true;
		}
	}

	function geocode_dataHandler($parser, $data)
	{
		if( empty($data) )
		{
			return;
		}
		else if( $this->inStatusCode )
		{
			$this->ret['status'] .= $data;
		}
		else if( $this->inCoordinates )
		{
			$this->ret['latlng'] .= $data;
		}
	}

	function geocode_endHandler($parser, $name)
	{
		if( $name == 'CODE' )
		{
			$this->inStatusCode = false;
		}
		else if( $name == 'COORDINATES' )
		{
			$this->inCoordinates = false;
		}
	}
}



?>