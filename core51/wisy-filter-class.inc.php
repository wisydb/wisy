<?php if( !defined('IN_WISY') ) die('!IN_WISY');

class WISY_FILTER_CLASS
{
	
	// all private!
	var $db;
	var $framework;
	
	var $tokens;
	var $filtered;
	
	var $DEBUG = false;
	
	function __construct(&$framework, $param)
	{
		global $wisyPortalId;
	
		$this->db			=  new DB_Admin;
		$this->framework	=& $framework;
		
		$this->setPresets();
		$this->parseFilterForm();
		$this->constructTokens();
	}
	
	function setPresets()
	{
		// TODO db: unten aufräumen, überschreibungen zusammenlegen
		$dates = $this->getDates();
		$this->presets = array();
		$this->presets['q'] = array
			(
				'type'			=> 'text',
			    'descr'			=> '<strong>Suchw&ouml;rter:</strong>',
				'autocomplete'	=>	'ac_keyword',
			);
		$this->presets['datum'] = array
			(
				'type'		=> 'function',
				'function'	=> 'datum:',
				'descr'		=> '<strong>Beginndatum:</strong>',
				'options' 	=> array(
				    'Alles' 				=> 'auch abgelaufene Angebote ber&uuml;cksichtigen',
				    $dates['vorgestern']	=> 'ab vorgestern',
				    $dates['gestern']		=> 'ab gestern',
				    ''						=> 'ab heute',
				    $dates['morgen']		=> 'ab morgen',
				    $dates['uebermorgen']	=> 'ab &uuml;bermorgen',
				    $dates['montag1']		=> 'n&auml;chste Woche &ndash; ab Montag, ' . $dates['montag1'],
				    $dates['montag2']		=> '&uuml;bern&auml;chste Woche &ndash; ab Montag, ' . $dates['montag2'],
				    $dates['montag3']       => 'in 3 Wochen &ndash; ab Montag, ' . $dates['montag3'],
					$dates['montag4']		=> 'in 4 Wochen &ndash; ab Montag, ' . $dates['montag4'],
					$dates['montag5']		=> 'in 5 Wochen &ndash; ab Montag, ' . $dates['montag5'],
					$dates['montag6']		=> 'in 6 Wochen &ndash; ab Montag, ' . $dates['montag6'],
					$dates['montag7']		=> 'in 7 Wochen &ndash; ab Montag, ' . $dates['montag7'],
					$dates['montag8']		=> 'in 8 Wochen &ndash; ab Montag, ' . $dates['montag8'],
					$dates['montag9']		=> 'in 9 Wochen &ndash; ab Montag, ' . $dates['montag9'],
				),
				'decoration' => array(
					'headline_left' => '',
				),
			);
		$this->presets['dauer'] = array
			(
				'type'		=> 'function',
				'function'	=> 'dauer:',
				'descr'		=> 'Dauer:',
				'options' 	=> array(
					''			=> '',
					'3'			=> 'max. 3 Tage',
					'7'			=> 'max. 1 Woche',
					'30'		=> 'max. 1 Monat',
					'90'		=> 'max. 3 Monate',
					'180'		=> 'max. 6 Monate',
					'365'		=> 'max. 1 Jahr',
					'2-3'		=> 'ca. 3 Tage',
					'4-7'		=> 'ca. 1 Woche',
					'20-31'		=> 'ca. 1 Monat',
					'80-100'	=> 'ca. 3 Monate',
					'160-200'	=> 'ca. 6 Monate',
					'340-380'	=> 'ca. 1 Jahr',
				),
			);
		$this->presets['tageszeit'] = array
			(
				'type'		=> 'taglist',
				'descr'		=> 'Tageszeit/Fernunterr.:',
				'function'	=> 'tageszeit:',
				'options'	=> array(
					''					=> '',
					'Ganztags'			=> 'Ganztags',
					'Vormittags'		=> 'Vormittags',
					'Nachmittags'		=> 'Nachmittags',
					'Abends'			=> 'Abends',
					'Wochenende'		=> 'Wochenende',
				    'Fernunterricht'	=> 'nur Fernunterricht',
				    'Fernunterricht2'	=> 'ohne Fernunterricht', // '-Fernunterricht'
				    'Datum:alles'	=> 'Auch abgelaufene Termine'
				),
			);
		    /* TODO: content as portal setting */
			$this->presets['stadtteil'] = array
			(
			    'type'		=> 'taglist',
			    'descr'		=> 'Stadtteil:',
			    'function'	=> 'stadtteil:',
			    'options'	=> array(
			        ''					=> ''
			    ),
			);
			/* TODO: content as portal setting */
			$this->presets['bezirk'] = array
			(
			    'type'		=> 'taglist',
			    'descr'		=> 'Bezirk:',
			    'function'	=> 'bezirk:',
			    'options'	=> array(
			        ''					=> ''
			    ),
			);
			$this->presets['metaabschlussart'] = array
			(
			    'type'		=> 'taglist_unfiltered',
			    'descr'		=> 'Abschlussart:',
			    'function'	=> 'metaabschlussart:',
			    'options'	=> array(
			        ''					=> '',
			        'Abschluss'			=> 'Abschluss',
			        'Zertifikate'		=> 'Zertifikate'
			    ),
			);
			
			// umkreissuche
		if( !$this->framework->iniRead('radiussearch.disable', 0) )
		{
			$this->presets['bei'] = array
				(
					'type'				=>	'text',
					'function'			=>	'bei:',
					'descr'				=>	'Stra&szlig;e/Ort:',
					'autocomplete'		=>	'ac_plzort',
					'comma_to_slash'	=>	true,
					'decoration' => array(
						'headline_left' => '<strong>Umkreissuche</strong>',
						'headline_right'	=> 'suche Kurse im Umkreis der folgenden Adresse:',
					),
				);
			$this->presets['km'] = array
				(
					'type'		=> 'function',
					'function'	=> 'km:',
					'descr'		=> 'max. Entfernung:',
					'options'	=> array(
						''			=>	'automatisch',
						'1'			=> '1 km',
						'2'			=> '2 km',
						'5'			=> '5 km',
						'7'			=> '7 km',
						'10'		=> '10 km',
						'20'		=> '20 km',
						'50'		=> '50 km',
					),
				);
				$this->presets['volltext'] = array
				(
				    'type'		=> 'text',
				    'function'	=> 'volltext:',
				    'descr'		=> 'Volltext:'
				);
		}

			// plz
		$decoration = array('headline_left' => '<strong>Weitere Optionen</strong>');
		if( !$this->framework->iniRead('plzsearch.disable', 0) )
		{
			$this->presets['plz'] = array
			(
				'type'		=>	'text',
				'function'	=>	'plz:',
				'descr'		=>	'PLZ &ndash; 1-5 Ziffern:',
				'decoration' => $decoration,
			);
			$decoration = array();
		}
			
			// weitere Optionen
		$this->presets['preis'] = array
			(
				'type'		=> 'function',
				'function'	=> 'preis:',
				'descr'		=> 'Preis:',
				'decoration' => $decoration,
				'options'	=> array(
					''			=>	'',
					'0'			=> 'muss kostenlos sein',
					'100'		=> 'max. 100 &euro;',
					'200'		=> 'max. 200 &euro;',
					'500'		=> 'max. 500 &euro;',
					'1000'		=> 'max. 1000 &euro;',
					'2000'		=> 'max. 2000 &euro;',
					'5000'		=> 'max. 5000 &euro;',
				),
			);
			$decoration = array();
			
		$foerderungen = $this->getSpezielleStichw(2);
		if( sizeof((array) $foerderungen) > 1 )
		{
			$this->presets['foerderung'] = array
				(
					'type'		=> 'taglist',
				    'descr'		=> 'F&ouml;rderung:',
					'function'	=> 'foerderung:',
					'options'	=>	$foerderungen
				);
		}
		
		$zielgruppen = $this->getSpezielleStichw(8);
		if( sizeof((array) $zielgruppen) > 1 )
		{
			$this->presets['zielgruppe'] = array
				(
					'type'		=> 'taglist',
					'descr'		=> 'Zielgruppe:',
					'function'	=> 'zielgruppe:',
					'options'	=>	$zielgruppen
				);
		}

		$qualitaetszertifikate = $this->getSpezielleStichw(4);
		if( sizeof((array) $qualitaetszertifikate) > 1 )
		{
			$this->presets['qualitaetszertifikat'] = array
				(
					'type'		=> 'taglist',
				    'descr'		=> 'Qualit&auml;tszertifikat:',
					'function'	=> 'qualitaetszertifikat:',
					'options'	=>	$qualitaetszertifikate
				);
		}
		
		$zertifikate = $this->getSpezielleStichw(65536);
		if( sizeof((array) $zertifikate) > 1 )
		{
		    $this->presets['zertifikat'] = array
		    (
		        'type'		=> 'taglist',
		        'descr'		=> 'Zertifikat:',
		        'function'	=> 'zertifikat:',
		        'options'	=>	$zertifikate
		    );
		}
		
		$sonstigemerkmale = $this->getSpezielleStichw(1024);
		if( sizeof((array) $sonstigemerkmale) > 1 )
		{
		    $this->presets['sonstigesmerkmal'] = array
		    (
		        'type'		=> 'taglist',
		        'descr'		=> 'Sonstiges Merkmal:',
		        'function'	=> 'sonstigesmerkmal:',
		        'options'	=>	$sonstigemerkmale
		    );
		}
		
		$abschluesse = $this->getSpezielleStichw(1);
		if( sizeof((array) $abschluesse) > 1 )
		{
		    $this->presets['abschluesse'] = array
		    (
		        'type'		=> 'taglist',
		        'descr'		=> 'Abschluss:',
		        'function'	=> 'abschluesse:',
		        'options'	=>	$abschluesse
		    );
		}
		
		$abschlussarten = $this->getSpezielleStichw(16);
		if( sizeof((array) $abschlussarten) > 1 )
		{
		    $this->presets['abschlussarten'] = array
		    (
		        'type'		=> 'taglist',
		        'descr'		=> 'Abschlussart:',
		        'function'	=> 'abschlussarten:',
		        'options'	=>	$abschlussarten
		    );
		}
		
		$unterrichtsarten = $this->getSpezielleStichw(32768);
		if( sizeof((array) $unterrichtsarten) > 1 )
		{
			$this->presets['unterrichtsart'] = array
				(
					'type'		=> 'taglist',
					'descr'		=> 'Unterrichtsart:',
					'function'	=> 'unterrichtsart:',
					'options'	=>	$unterrichtsarten
				);
		}
				
		if( $this->framework->iniRead('search.adv.fulltext', 1)!=0 )
		{
			$this->presets['volltext'] = array
				(
					'type'		=>	'text',
					'function'	=>	'volltext:',
					'descr'		=>	'Volltext:',
				);
		}
		
		// Hauptsuchfeld ausblenden
		$this->presets['q']['type'] = 'hidden';
		
		// Anbietersuche
		$this->presets['zeige'] = array
			(
				'type'			=> 'text',
				'descr'			=> 'Zeige:',
				'function'		=> 'zeige:'
		);
				
		// Kurszeitpunkt
		$this->presets['datum']['decoration']['headline_left'] = 'Kurszeitpunkt';
		$this->presets['datum']['descr'] = 'Beginn';
		$this->presets['datum']['classes'] = 'wisyr_c2_3 break';
		$this->presets['dauer']['classes'] = 'wisyr_c1_3';
		$this->presets['tageszeit']['descr'] = 'Tageszeit';
		$this->presets['tageszeit']['classes'] = 'wisyr_c1_3';
		
		// Volltext
		$this->presets['volltext']['decoration']['headline_left'] = '';
		$this->presets['volltext']['descr'] = 'Volltext';
		$this->presets['volltext']['classes'] = 'wisyr_c1_3';
		
		// Umkreissuche
		$this->presets['bei']['decoration']['headline_right'] = '';
		$this->presets['bei']['descr'] = 'PLZ oder Ort';
		$this->presets['bei']['classes'] = 'wisyr_c2_3';
		$this->presets['km']['descr'] = 'Umkreis';
		$this->presets['km']['classes'] = 'wisyr_c1_3';
		
		$this->presets['stadtteil']['descr'] = 'Stadtteil';
		$this->presets['stadtteil']['classes'] = 'wisyr_c1_3';
		$this->presets['bezirk']['descr'] = 'Bezirk';
		$this->presets['bezirk']['classes'] = 'wisyr_c1_3';
		
		// Weitere Optionen
		$this->presets['preis']['decoration']['headline_left'] = 'Weitere Optionen';
		$this->presets['preis']['descr'] = 'Preis';
		$this->presets['preis']['classes'] = 'wisyr_c1_3';
		$this->presets['foerderung']['descr'] = 'F&ouml;rderung';
		$this->presets['foerderung']['classes'] = 'wisyr_c2_3';
		
		$this->presets['zielgruppe']['descr'] = 'Zielgruppe';
		$this->presets['zielgruppe']['classes'] = 'wisyr_c1_3';
		$this->presets['qualitaetszertifikat']['descr'] = 'Qualit&auml;tszertifikat';
		$this->presets['qualitaetszertifikat']['classes'] = 'wisyr_c2_3';
		
		$this->presets['zertifikat']['descr'] = 'Zertifikat';
		$this->presets['zertifikat']['classes'] = 'wisyr_c2_3';
		
		$this->presets['sonstigesmerkmal']['descr'] = 'Sonstiges Merkmal';
		$this->presets['sonstigesmerkmal']['classes'] = 'wisyr_c2_3';
		
		$this->presets['abschluesse']['descr'] = 'Abschluss';
		$this->presets['abschluesse']['classes'] = 'wisyr_c3_3';
		
		$this->presets['abschlussarten']['descr'] = 'Abschlussart';
		$this->presets['abschlussarten']['classes'] = 'wisyr_c4_3';
		
		$this->presets['metaabschlussart']['descr'] = 'MetaAbschlussart';
		$this->presets['metaabschlussart']['classes'] = 'wisyr_c4_3';
		
		$this->presets['unterrichtsart']['descr'] = 'Unterrichtsart';
		$this->presets['unterrichtsart']['classes'] = 'wisyr_c1_3';
		
		// Volltext, PLZ entfernen
		//unset($this->presets['volltext']);
		unset($this->presets['plz']);
		
		/* Pseudo-Tags für vereinfachte Filtersuche */
		
		// Themen
		$this->presets['themen'] = array
			(
				'type'			=> 'text',
				'descr'			=> 'Themen:',
				'function'		=> 'thema:'
			);
			
		// Anbieter
		$this->presets['anbieter'] = array
			(
				'type'			=> 'text',
				'descr'			=> 'Anbieter:',
				'function'		=> 'anbieter:'
			);
	}
	
	// TODO db: Die Funktion ist jetzt gedoppelt, aufräumen
	function getDates()
	{
		$onedaysec = 24*60*60;
		$heute = mktime(0, 0, 0);
		
		$ret = array();
		$ret['vorgestern'] = strftime('%d.%m.%Y', $heute-$onedaysec*2);
		$ret['gestern'] = strftime('%d.%m.%Y', $heute-$onedaysec);
		$ret['heute'] = strftime('%d.%m.%Y', $heute);
		$ret['morgen'] = strftime('%d.%m.%Y', $heute+$onedaysec);
		$ret['uebermorgen'] = strftime('%d.%m.%Y', $heute+$onedaysec*2);
		
		// nächsten Montag herausfinden
		$test = $heute + $onedaysec;
		while( 1 )
		{
			$info = getdate($test);
			if( $info['wday'] == 1 )
				{ break; }
			$test += $onedaysec;
		}
		for( $i = 1; $i <= 9 /* s.a. (1) */; $i++ )
			$ret['montag'.$i] = strftime('%d.%m.%Y', $test + $onedaysec * 7 * ($i-1));
		
		return $ret;
	}
	
	// TODO db: Die Funktion ist jetzt gedoppelt, aufräumen
	private function getSpezielleStichw($flag)
	{
		// nur die stichwörter zurückgeben, die im aktuellem Portal auch verwendet werden!
		$keyPrefix = "advStichw.$flag";
		$magic = strftime("%Y-%m-%d-v5-").md5($GLOBALS['wisyPortalFilter']['stdkursfilter']);
		if( $this->framework->cacheRead("adv_stichw.$flag.magic") != $magic )
		{
			$specialInfo =& createWisyObject('WISY_SPECIAL_INFO_CLASS', $this->framework);
			$specialInfo->recalcAdvStichw($magic, $flag);
		}
		$ids_str = $this->framework->cacheRead("adv_stichw.$flag.ids");
	
		// query!
		$ret = array(''=>'');
		$db = new DB_Admin;
		//$db->query("SELECT stichwort FROM stichwoerter WHERE eigenschaften=$flag ORDER BY stichwort_sorted;");
		$db->query("SELECT stichwort FROM stichwoerter WHERE id IN ($ids_str) ORDER BY stichwort_sorted;");
		while( $db->next_record() )
		{
		    $stichw = htmlspecialchars($db->fcs8('stichwort'));
			$stichw = trim(strtr($stichw, array(': '=>' ', ':'=>' ', ', '=>' ', ','=>' ')));
			
			$ret[ $stichw ] = $stichw;
		}
		return $ret;
	}

	function parseFilterForm() {
		
		$queryfilters = array();
		reset($this->presets);
		
		$this->filtered = false;
		
		$preis = false;
		$volltext = false;
		$datum = false;
		$km = false;
		
		if($this->DEBUG) echo 'parseFilterForm()';
		
		
		foreach($this->presets as $field_name => $preset)
		{
		    $field_name = mb_strtolower($field_name);
		    if($this->DEBUG) echo $field_name;
		    
		    if( !$this->framework->getParam('filter_' . $field_name) ) continue;
		    
		    $this->filtered = true;
		    
		    $field = '';
		    $value = $this->implodeArray( $this->framework->getParam('filter_' . $field_name) );
		    $value = htmlspecialchars(trim($value)); // '&' -> '&amp;', '"' -> '&quot;', wenn n. ENT_NOQUOTES, "'" -> '&#039;' (oder &apos;), wenn n. ENT_QUOTES, '<' -> '&lt;', '>' -> '&gt;'
		    
		    $value = str_replace("&amp;", "&", $value);
			if( $value != '' )
			{
				
				if($field_name == 'preis')
				{
					$preis = $value;
					continue;
				}
				
				if($field_name == 'volltext')
				{
				    $volltext = $value;
				    continue;
				}
                
                if($field_name == 'datum')
                {
                    $datum = $value;
                    continue;
                }
				
				if($field_name == 'bei')
				{
					$km = true;
				}

				
				if( $preset['comma_to_slash'] )
				{
					$value = str_replace(', ', '/', $value);
					$value = str_replace(',', '/', $value);
				}
	
				if( $preset['function'] ) $field = trim($preset['function'], ':');
				
				$queryfilters[] = array('field' => $field, 'value' => $value);
			}
		}
		
		// Sonderfall Preisspanne. TODO db: Ueber Presets umsetzen?
		$filter_preis = trim($this->implodeArray( $this->framework->getParam('filter_preis') ));
		$filter_preis_von = trim($this->implodeArray( $this->framework->getParam('filter_preis_von') ));
		$filter_preis_bis = trim($this->implodeArray( $this->framework->getParam('filter_preis_bis') ));
		
		if($filter_preis != '' || $filter_preis_von != '' || $filter_preis_bis != '')
		{
		    if($filter_preis != '')
		    {
		        $preis = $filter_preis;
		    }
		    
		    $preis_von = false;
		    if($filter_preis_von != '')
		    {
		        $preis_von = intval($filter_preis_von);
		    }
		    
		    $preis_bis = false;
		    if($filter_preis_bis != '')
		    {
		        $preis_bis = intval($filter_preis_bis);
		    }
			
			if($preis_von !== false && $preis_bis !== false)
			{
				$preis = $preis_von . '-' . $preis_bis;
			}
			else if($preis_von !== false)
			{
				$preis = $preis_von . '-' . 999999;
			}
			else if($preis_bis !== false)
			{
				$preis = $preis_bis;
			}
			
			if($preis !== false)
			{
				$queryfilters[] = array('field' => 'preis', 'value' => $preis);
			}
		}
        
		if( $this->framework->getParam('filter_volltext', false) && is_array( $this->framework->getParam('filter_volltext') ))
		{
		    $filter_volltext = $this->framework->getParam('filter_volltext');
		    $volltext_q = trim( strval( $filter_volltext[0]) );
		    
		    if( strlen($volltext_q) != "" && $this->match_validFulltext( $volltext_q ) ) {
		        $volltext = $volltext_q;
		        $queryfilters[] = array('field' => 'volltext', 'value' =>  $volltext  );
		    }
		}
		
		// Sonderfall Kursbeginn. TODO db: optimieren?
		$filter_datum_von = trim($this->implodeArray( $this->framework->getParam('filter_datum_von') ));
		
		if($filter_datum_von != '')
		{
		    if( $this->match_validDate($filter_datum_von) )
		        $datum = $filter_datum_von;
		    else
		        $datum = utf8_decode("Wert ungültig");
		}
		if($datum != '')
		{
		    if( $this->match_validDate($datum) )
		        $queryfilters[] = array( 'field' => 'datum', 'value' => $datum );
		    else
		        $queryfilters[] = array( 'field' => 'datum', 'value' => utf8_decode("Wert ungültig") );
		}
		
		// Sonderfall Dauer. TODO db: ueber Presets umsetzen?
		$filter_dauer_von = trim($this->implodeArray( $this->framework->getParam('filter_dauer_von') ));
		$filter_dauer_bis = trim($this->implodeArray( $this->framework->getParam('filter_dauer_bis') ));
		
		if($filter_dauer_von != '' || $filter_dauer_bis != '')
		{
		    $dauer = '';
		    $dauer_von = false;
		    if($filter_dauer_von != '')
		    {
		        $dauer_von = intval($filter_dauer_von);
		    }
		    
		    $dauer_bis = false;
		    if($filter_dauer_bis != '')
		    {
		        $dauer_bis = intval($filter_dauer_bis);
		    }
			
			if($dauer_von && $dauer_bis)
			{
				$dauer = $dauer_von . '-' . $dauer_bis;
			}
			else if($dauer_von)
			{
				$dauer = $dauer_von . '-9999';
			}
			else if($dauer_bis)
			{
				$dauer = $dauer_bis;
			}
			
			if($dauer != '')
			{
			    $queryfilters[] = array('field' => 'dauer', 'value' => $dauer);
			}
		}
		
		$this->framework->order = trim( strval( $this->framework->getParam('order') ) );
		$filter_order = trim( strval( $this->framework->getParam('filter_order') ) );
		if( $filter_order != '') $this->framework->order = $filter_order;
		
		
		if($this->DEBUG) echo 'count(queryfilters): ' . count((array) $queryfilters);
		
		$this->framework->tokensQF = $queryfilters;
	}
	
	function match_validFulltext($str) {
	    // anti-xss & sanity => matches wisy-search-class
	    // may not be enough for q, qs, qf - but enough for fulltext
	    // match a single character NOT (^) present in the list below
	    return ( !preg_match('/[^a-zA-Z0-9äÄöÖüÜß., ]/', $str) );
	}
	
	function match_validDate($str) {
	    // anti-xss & sanity => matches wisy-search-class
	    return( strtolower($str) == 'alles' || preg_match('/^heute([+-][0-9]{1,5})?$/i', $str) || preg_match('/^([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{2,4})$/', $str) );
	}
	
	function implodeArray($input) {
	    if(is_array($input)) {
	        $input = array_filter($input, function($value) { return !is_null($value) && $value !== ''; });
	        $input = array_unique($input);
	        $input = implode($this->framework->filterValueSeparator, $input);
	    }
	    return $input;
	}
	
	function constructTokens() {
	    
	    // $q =  htmlspecialchars(trim(cs8($this->framework->getParam('q',  '')), ', '));
	    $q =  htmlspecialchars(trim( strval( $this->framework->getParam('q',  '') ), ', '));
	    $qs = htmlspecialchars(trim( strval( $this->framework->getParam('qs', '') ), ', '));
	    $qf = htmlspecialchars(trim( strval( $this->framework->getParam('qf', '') ), ', '));
	    
	    if($this->DEBUG) echo 'Vorher' . "<br />\n";
	    if($this->DEBUG) echo 'q: ' .  $q . "<br />\n";
	    if($this->DEBUG) echo 'qs: ' . $qs . "<br />\n";
	    if($this->DEBUG) echo 'qf: ' . $qf . "<br />\n";
	    if($this->DEBUG) echo "<br />\n<br />\n";
	    
	    if(strlen($q) && !strlen($qs) && !strlen($qf) && (is_array($this->framework->QF) && !count($this->framework->QF) || !is_array($this->framework->QF && $this->framework->QF == "")) ) { // #php7
	        // Case 4: Only q is filled, qs and qf are empty
	        
	        // Q
			$this->framework->Q = $q;
			$this->framework->tokensQ = $this->tokensFromString($q);
			
			// QS and QF
			$this->constructTokensFromQ();
			$this->framework->QS = $this->stringFromTokens($this->framework->tokensQS);
			$this->framework->QF = $this->stringFromTokens($this->framework->tokensQF);
			
		} else if(strlen($q) || strlen($qs) || strlen($qf) || $this->filtered) {
			// Case 2 or Case 3: At least some values are filled and not q alone
			
			// QS and QF
			$this->framework->QS = $qs;
			$this->framework->tokensQS = $this->tokensFromString($qs);
			
			if($this->filtered) {
			    if($this->DEBUG) echo "Filtered!<br>";
			    
				// Use tokens to generate string if tokens were found
				if(count($this->framework->tokensQF)) {
					$this->framework->QF = $this->stringFromTokens($this->framework->tokensQF);
				}	
			} else {
				// Use string to generate tokens if no tokens were found
				$this->framework->QF = $qf;
				$this->framework->tokensQF = $this->tokensFromString($qf);
			}
			
			// Q
			$this->constructTokensFromQSandQF();
			$this->framework->Q = $this->stringFromTokens($this->framework->tokensQ);
		}
		
		if($this->DEBUG) echo "<br />\n<br />\n";
		if($this->DEBUG) echo 'Nachher' . "<br />\n";
		if($this->DEBUG) echo 'tokensQ: ' . "<br />\n";
		if($this->DEBUG) var_dump($this->framework->tokensQ);
		if($this->DEBUG) echo  "<br />\n" . 'tokensQS: ' . "<br />\n";
		if($this->DEBUG) var_dump($this->framework->tokensQS);
		if($this->DEBUG) echo  "<br />\n" . 'tokensQF: ' . "<br />\n";
		if($this->DEBUG) var_dump($this->framework->tokensQF);

		if($this->DEBUG) echo "<br />\n" . 'Q: ' .  $this->framework->Q . "<br />\n";
		if($this->DEBUG) echo 'QS: ' .  $this->framework->QS . "<br />\n";
		if($this->DEBUG) echo 'QF: ' .  $this->framework->QF . "<br />\n";
		
	}
		
	function constructTokensFromQ() {
		if($this->DEBUG) echo 'constructTokensFromQ()' . "<br />\n";
		
		// Loop through Q and add all tagged items to QF and non-tagged items to QS
		$qs_array = array();
		$qf_array = array();
		
		foreach($this->framework->tokensQ as $item) {
			if($this->DEBUG) echo ' Q item: ' . implode(', ', $item) . "<br />\n";
			
			if(trim($item['field']) == '' || $item['field'] == 'tag') {
				$qs_array[] = $item;
			} else {
				$qf_array[] = $item;
			}
		}
		
		$this->framework->tokensQS = $qs_array;
		$this->framework->tokensQF = $qf_array;
	}
		
	function constructTokensFromQSandQF() {
		if($this->DEBUG) echo 'constructTokensFromQSandQF()' . "<br />\n";
		
		// Loop through qs and qf and add all items to q
		$tokens = array();
		foreach($this->framework->tokensQS as $item) {
			$tokens[] = $item;
		}
		foreach($this->framework->tokensQF as $item) {
			if($item['field'] != '' && $item['value'] != '' && in_array($item['field'], $this->framework->filterTokens)) {
				// remove tags from Q that we invented just for simplified search, listed in $this->framework->filterTokens
				$item['field'] = '';
			}
			$tokens[] = $item;
		}
		
		$this->framework->tokensQ = $tokens;
	}
	
	function tokensFromString($string)
	{
		
		if($this->DEBUG) echo 'tokensFromString(' . $string . ')' ."<br />\n";
				
		$ret = array();

		$queryArr = $this->stringToArray($string);
		for( $i = 0; $i < sizeof((array) $queryArr); $i++ )
		{
			// get initial value to search tags for, remove multiple spaces
			$field = '';
			$value = trim($queryArr[$i]);
			while( strpos($value, '  ')!==false )
				$value = str_replace('  ', ' ', $value);
			
			// find out the field to search the value in
			if( ($p=strpos($value, ':'))!==false )
			{
				$field = strtolower(trim(substr($value, 0, $p)));
				$value = trim(substr($value, $p+1));
			}

			// any token?
			if( $field!='' || $value!='' )
			{
				$ret[] = array('field'=>$field, 'value'=>$value);
			}
		}
		
		return $ret;
	}
	
	function stringFromTokens($tokens)
	{
		
		if($this->DEBUG) echo 'stringFromTokens()' ."<br />\n";
		if($this->DEBUG) var_dump($tokens);
		
		$q = '';
		foreach($tokens as $token) {
			if(is_array($token)) {
				$q .= $q ? ', ' : '';
				$q .= ($token['field'] != 'tag' && $token['field'] != '') ? ($token['field'].':') : '';
				$q .= $token['value'];
			}
		}
		return $q;
	}
	
	function findTokencondition($value, $key, $array)
	{
		foreach($array as $k => $val)
		{
			if($val[$key] == $value) return $k;
		}
		return null;
	}
	
	function stringToArray($querystring) {
		$querystring = trim($querystring, ', ');
		if($querystring != '')
		{
			$queryarray = explode(',', $querystring);
			return array_map(function($item) { return trim($item, ', '); }, $queryarray);
		}
		return array();
	}
	
	function getSearchUrlWithoutFilters() {
		return 'search?qs=' . urlencode($this->framework->QS);
	}
	
	function getSearchUrlWithFilters() {
		return 'search?qs=' . urlencode($this->framework->QS) . '&qf=' . urlencode($this->framework->QF);
	}
	
	function getUrlRemoveFilterByName($removefilter, $value=false) {
	    if($value) {
	        global $extraTokens;
	        $extraTokens = array();
	        $tokens = array_filter($this->framework->tokensQF, function($filter) use($removefilter, $value) {
	            global $extraTokens;
	            if($filter['field'] == $removefilter) {
	                $filteredValue = array();
	                foreach(explode($this->framework->filterValueSeparator, $filter['value']) as $val) {
	                    if($val != $value) {
	                        $filteredValue[] = $val;
	                    }
	                }
	                if(count($filteredValue)) {
	                    $extraTokens[] = array(
	                        'field' => $filter['field'],
	                        'value' => implode($this->framework->filterValueSeparator, $filteredValue)
	                    );
	                }
	                return false;
	            } else {
	                return true;
	            }
	        });
	            $tokens = array_merge($tokens, $extraTokens);
	    } else if($removefilter == 'bei') {
	        $tokens = array_filter($this->framework->tokensQF, function($filter) use($removefilter) {
	            return ($filter['field'] != $removefilter && $filter['field'] != 'km');
	        });
	    } else {
	        $tokens = array_filter($this->framework->tokensQF, function($filter) use($removefilter) {
	            return $filter['field'] != $removefilter;
	        });
	    }
	    
	    return $this->framework->getUrl('search', array('qs' => $this->framework->QS, 'qf' => $this->stringFromTokens($tokens), 'qsrc' => 's'));
	}
	
	function getUrlRemoveFilterByValue($tokenconditions, $removevalue) {
		$query = array();
		for( $i = 0; $i < sizeof((array) $tokenconditions); $i++ ) {
            if($tokenconditions[$i]['field'] == 'tag') 
            {
				if($tokenconditions[$i]['value'] != $removevalue)
				{
					$query[] = $tokenconditions[$i]['value'];
				}
            }
            else if($tokenconditions[$i]['field'] != $removefield)
            {
                $query[] = $tokenconditions[$i]['field'] . ':' . $tokenconditions[$i]['value'];
            }
		}
                
        return $this->framework->getUrl('search', array('q' => implode(',', $query)));
	}
	function getUrlAddFilter($tokenconditions, $addfilter) {
		$query = array();
		for( $i = 0; $i < sizeof((array) $tokenconditions); $i++ ) {
            if($tokenconditions[$i]['field'] == 'tag') 
            {
                $query[] = $tokenconditions[$i]['value'];
            }
            else
            {
                $query[] = $tokenconditions[$i]['field'] . ':' . $tokenconditions[$i]['value'];
            }
		}
		$query[] = $addfilter;
                
        return $this->framework->getUrl('search', array('q' => implode(',', $query)));
	}
	
	function getActiveFilters() {
	    $active_filters = '';
	    foreach($this->framework->tokensQF as $token) {
	        foreach(explode($this->framework->filterValueSeparator, $token['value']) as $value) {
	            
	            $value = trim( strval($value) );
	            $token['field'] = trim( strval( $token['field']) );
	            
	            if($value !== '' && $token['field'] != 'tag') {
	                $ignore = false;
	                
	                switch(strtolower($token['field'])) {
	                    case 'preis':
	                        $filterlabel = number_format( intval( $value ), 0, ',', '.') . ' EUR';
	                        if($value > 0 && strpos($value, '-') === false) {
	                            $filterlabel = 'bis ' . $filterlabel;
	                        } else if($value === 0) {
	                            $filterlabel = 'kostenlos';
	                        }
	                        break;
	                        
	                    case 'bei':
	                        $filterlabel = str_replace('/', ',', $value);
	                        break;
	                        
	                    case 'km':
	                        $filterlabel = $value . ' km Umkreis';
	                        if($value > 400) $filterlabel = '> 50 km Umkreis';
	                        break;
	                        
	                    case 'datum':
	                        $filterlabel = 'Beginn: ' . $value;
	                        break;
	                        
	                    case 'volltext':
	                        $filterlabel = 'Volltext: ' . $value;
	                        break;
	                        
	                    case 'dauer':
	                        if(strpos($value, "-") === FALSE && $this->framework->getParam('filter_dauer_bis') > 0)
	                            $filterlabel = 'Dauer max: ';
	                            else
	                                $filterlabel = 'Dauer: ';
	                                
	                                if($value === 1)
	                                {
	                                    $filterlabel .= $value . ' Tag';
	                                }
	                                else if($value < 7)
	                                {
	                                    $filterlabel .= $value . ' Tage';
	                                }
	                                else if($value < 32)
	                                {
	                                    $filterlabel .= floor($value / 7) . ' Wochen';
	                                    
	                                    if(strpos($filterlabel, "-") === FALSE && $this->framework->getParam('filter_dauer_von') > 0 && !$this->framework->getParam('filter_dauer_bis') )
	                                        $filterlabel = str_replace('Dauer', 'Dauer min', $filterlabel);
	                                }
	                                else
	                                {
	                                    $filterlabel .= floor($value / 31) . ' Monate';
	                                    
	                                    if(strpos($filterlabel, "-") === FALSE && $this->framework->getParam('filter_dauer_von') > 0 && !$this->framework->getParam('filter_dauer_bis'))
	                                        $filterlabel = str_replace('Dauer', 'Dauer min', $filterlabel);
	                                }
	                                break;
	                                
	                    case 'fav':
	                        $ignore = true;
	                        break;
	                        
	                    case 'favprint':
	                        $ignore = true;
	                        break;
	                        
	                    case 'zeige':
	                        $ignore = true;
	                        break;
	                        
	                    default:
	                        $filterlabel = $value;
	                        break;
	                }
	            } else { // only label, no value
	                switch(strtolower($token['field'])) {
	                    case 'fav':
	                        $ignore = true;
	                        break;
	                        
	                    case 'favprint':
	                        $ignore = true;
	                        break;
	                        
	                    default:
	                        $ignore = false;
	                        break;
	                }
	            }
	            
	            // if radius search adress removed, remove radius, too
	            if( strtolower($token['field'])  == 'bei') {
	                $url_removedFilter = $this->getUrlRemoveFilterByName($token['field'], $value);
	                $url_removedFilter = preg_replace('/qf=km.*&/i', '', $url_removedFilter);
	                $url_removedFilter = preg_replace('/qf=km.*$/i', '', $url_removedFilter);
	            }
	            else
	                $url_removedFilter = $this->getUrlRemoveFilterByName($token['field'], $value);
	                
	            if(!$ignore) {
	                $active_filters .= '<li class="wisyr_filter ' . strtolower($token['field']) . '"><a href="' . $url_removedFilter . '">' . str_replace("#", " ", $filterlabel) . '</a></li>';
	            }
	        }
	    }
	    return $active_filters;
	}
	
	function getActiveFiltersCount()
	{
		return count($this->framework->tokensQF);
	}
	
	function getThemenByIdList($idList)
	{
		
		$ret = array();

		// create complete SQL query
		// TODO db: caching?
		if(count((array) $idList))
		{
			$sql =  "SELECT thema FROM themen WHERE id IN(" . implode(',', $idList) . ")";
			$this->db->query($sql);
			while( $this->db->next_record() )
			    $ret['records'][] = cs8($this->db->Record['thema']);
			$this->db->free();
	
		}
		
		return $ret;
		
	}
	
	function getThemenFilters($tokenconditions, $idList) {
		$filters = '';
		$removelink = '';
		
		$themenliste = $this->getThemenByIdList($idList);
        $themen = $themenliste['records'];
        natcasesort($themen);
				
		foreach($themen as $thema)
		{
			$remove = false;
			for( $i = 0; $i < sizeof((array) $tokenconditions); $i++ ) {
	            if($tokenconditions[$i]['field'] == 'tag' && $tokenconditions[$i]['value'] == g_sync_removeSpecialChars($thema)) 
	            {
					$remove = true;
					break;
				}
			}
			if($remove)
			{
				$filters .= '<li><input class="filter_checkbox" type="checkbox" checked="checked" /><a class="wisyr_filter_selected" href="' . $this->getUrlRemoveFilterByValue($tokenconditions, g_sync_removeSpecialChars($thema)) . '">' . $thema . '</a></li>';
				$removelink = $this->getUrlRemoveFilterByValue($tokenconditions, g_sync_removeSpecialChars($thema));
			}
			else {
				$filters .= '<li><input class="filter_checkbox" type="checkbox" /><a href="' . $this->getUrlAddFilter($tokenconditions, g_sync_removeSpecialChars($thema)) . '">' . $thema . '</a></li>';	
			}
		}
		
		if($removelink != '')
		{
			// Merke: Das funktioniert so nur richtig solange nur 1 Thema in obiger Schleife gefunden wurde.
			$filters .= '<li><a href="' . $removelink . '">Auswahl aufheben</a></li>';
		}
		
		return $filters;
	}
	
	function getAnbieterByIdList($idList)
	{
		
		$ret = array();

		// create complete SQL query
		// TODO db: caching?
		if(count((array) $idList))
		{
			$sql =  "SELECT suchname FROM anbieter WHERE id IN(" . implode(',', $idList) . ")";
			$this->db->query($sql);
			while( $this->db->next_record() )
			    $ret['records'][] = cs8($this->db->Record['suchname']);
			$this->db->free();
	
		}
		
		return $ret;
		
	}
	
	function getAnbieterFilters($tokenconditions, $idList) {
		$anbieterliste = $this->getAnbieterByIdList($idList);
        $anbieters = $anbieterliste['records'];
        if(count((array) $anbieters))
		{
			natcasesort($anbieters);
		}
		if(is_array($anbieters))
		{
			array_unshift($anbieters, 'Alle');
		}
		else
		{
			$anbieters = array('Alle');
		}
		return $anbieters;
	}
}