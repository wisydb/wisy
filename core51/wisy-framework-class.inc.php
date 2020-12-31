<?php if( !defined('IN_WISY') ) die('!IN_WISY');

/******************************************************************************
 WISY 5.0
 ******************************************************************************
 Globale Seite, hiervon existiert genau 1 Objekt
 ******************************************************************************/

// necessary for G_BLOB_CLASS / logo output
require_once('admin/classes.inc.php');
require_once('admin/config/codes.inc.php');

// Funktionen, die ohne irgendwelche instanzen laufen sollten
function g_sync_removeSpecialChars($str)
{
	$str = strtr($str, ',:', '  ');
	while( strpos($str, '  ')!==false ) $str = str_replace('  ', ' ', $str);
	$str = trim($str);
	return $str;
}



// die globale Framework-Klasse
class WISY_FRAMEWORK_CLASS
{
	var $includeVersion;
	
	var $editCookieName;
	var $editSessionStarted;
	
	var $encode_windows_chars_map = array(
	    "\xc2\x80" => "\xe2\x82\xac", /* EURO SIGN */
	    "\xc2\x82" => "\xe2\x80\x9a", /* SINGLE LOW-9 QUOTATION MARK */
	    "\xc2\x83" => "\xc6\x92",     /* LATIN SMALL LETTER F WITH HOOK */
	    "\xc2\x84" => "\xe2\x80\x9e", /* DOUBLE LOW-9 QUOTATION MARK */
	    "\xc2\x85" => "\xe2\x80\xa6", /* HORIZONTAL ELLIPSIS */
	    "\xc2\x86" => "\xe2\x80\xa0", /* DAGGER */
	    "\xc2\x87" => "\xe2\x80\xa1", /* DOUBLE DAGGER */
	    "\xc2\x88" => "\xcb\x86",     /* MODIFIER LETTER CIRCUMFLEX ACCENT */
	    "\xc2\x89" => "\xe2\x80\xb0", /* PER MILLE SIGN */
	    "\xc2\x8a" => "\xc5\xa0",     /* LATIN CAPITAL LETTER S WITH CARON */
	    "\xc2\x8b" => "\xe2\x80\xb9", /* SINGLE LEFT-POINTING ANGLE QUOTATION */
	    "\xc2\x8c" => "\xc5\x92",     /* LATIN CAPITAL LIGATURE OE */
	    "\xc2\x8e" => "\xc5\xbd",     /* LATIN CAPITAL LETTER Z WITH CARON */
	    "\xc2\x91" => "\xe2\x80\x98", /* LEFT SINGLE QUOTATION MARK */
	    "\xc2\x92" => "\xe2\x80\x99", /* RIGHT SINGLE QUOTATION MARK */
	    "\xc2\x93" => "\xe2\x80\x9c", /* LEFT DOUBLE QUOTATION MARK */
	    "\xc2\x94" => "\xe2\x80\x9d", /* RIGHT DOUBLE QUOTATION MARK */
	    "\xc2\x95" => "\xe2\x80\xa2", /* BULLET */
	    "\xc2\x96" => "\xe2\x80\x93", /* EN DASH */
	    "\xc2\x97" => "\xe2\x80\x94", /* EM DASH */

	    "\xc2\x98" => "\xcb\x9c",     /* SMALL TILDE */
	    "\xc2\x99" => "\xe2\x84\xa2", /* TRADE MARK SIGN */
	    "\xc2\x9a" => "\xc5\xa1",     /* LATIN SMALL LETTER S WITH CARON */
	    "\xc2\x9b" => "\xe2\x80\xba", /* SINGLE RIGHT-POINTING ANGLE QUOTATION*/
	    "\xc2\x9c" => "\xc5\x93",     /* LATIN SMALL LIGATURE OE */
	    "\xc2\x9e" => "\xc5\xbe",     /* LATIN SMALL LETTER Z WITH CARON */
	    "\xc2\x9f" => "\xc5\xb8"      /* LATIN CAPITAL LETTER Y WITH DIAERESIS*/
	);

	var $filterer;
	var $Q;
	var $QS;
	var $QF;
	var $order;
	
	var $tokensQ;
	var $tokensQS;
	var $tokensQF;
	
	// remove tags from Q that we invented just for simplified search, listed in $this->framework->filterTokens
	var $filterTokens = array(
	    'anbieter',
	    'zielgruppe',
	    'thema',
	    'foerderung',
	    'qualitaetszertifikat',
	    'zertifikat',
	    'sonstigesmerkmal',
	    'abschluesse',
	    'abschlussart',
	    'metaabschlussart',
	    'unterrichtsart',
	    'sonstigesmerkmal',
	    'tageszeit',
	    'stadtteil',
	    'bezirk'
	);
	
	var $filterValueSeparator = ',';
	
	function __construct($baseObject, $addParam)
	{
	    // ini_set("default_charset", "UTF-8");
	    
		// constructor
	    $this->includeVersion = ''; //'?iv=511'; // change the number on larger changes in included CSS and/or JS files.  May be empty.
		
		// init edit stuff
		$this->editCookieName		= 'wisyEdit20';
		$this->editSessionStarted	= false;
		
		// spalten initialisiern (aus historischen Gruenden so ...)
		$GLOBALS['wisyPortalSpalten'] = 0;
		$temp = $this->iniRead('spalten'); if( $temp == '' ) $temp = 'anbieter,termin,dauer,art,preis,ort';
		$temp = str_replace(' ', '', $temp) . ',';
		if( strpos($temp, 'anbieter,'			)!==false ) $GLOBALS['wisyPortalSpalten'] += 1;
		if( strpos($temp, 'termin,'				)!==false ) $GLOBALS['wisyPortalSpalten'] += 2;
		if( strpos($temp, 'dauer,'				)!==false ) $GLOBALS['wisyPortalSpalten'] += 4;
		if( strpos($temp, 'art,'				)!==false ) $GLOBALS['wisyPortalSpalten'] += 8;
		if( strpos($temp, 'preis,'				)!==false ) $GLOBALS['wisyPortalSpalten'] += 16;
		if( strpos($temp, 'ort,'				)!==false ) $GLOBALS['wisyPortalSpalten'] += 32;
		if( strpos($temp, 'kursnummer,'			)!==false ) $GLOBALS['wisyPortalSpalten'] += 64;
		if( strpos($temp, 'bemerkungen,'			)!==false ) $GLOBALS['wisyPortalSpalten'] += 128;
		/* if( strpos($temp, 'bunummer,'			)!==false ) $GLOBALS['wisyPortalSpalten'] += 256; */
		if( strpos($temp, 'entfernung,'			)!==false ) $GLOBALS['wisyPortalSpalten'] += 512;
        
		// Spalten fuer Durchfuehrungs-Detailansicht initialisieren falls gesetzt
		$GLOBALS['wisyPortalSpaltenDurchf'] = 0;
		$temp = $this->iniRead('spalten.durchf');
        if( $temp != '' )
        {
    		$temp = str_replace(' ', '', $temp) . ',';
    		if( strpos($temp, 'anbieter,'			)!==false ) $GLOBALS['wisyPortalSpaltenDurchf'] += 1;
    		if( strpos($temp, 'termin,'				)!==false ) $GLOBALS['wisyPortalSpaltenDurchf'] += 2;
    		if( strpos($temp, 'dauer,'				)!==false ) $GLOBALS['wisyPortalSpaltenDurchf'] += 4;
    		if( strpos($temp, 'art,'				)!==false ) $GLOBALS['wisyPortalSpaltenDurchf'] += 8;
    		if( strpos($temp, 'preis,'				)!==false ) $GLOBALS['wisyPortalSpaltenDurchf'] += 16;
    		if( strpos($temp, 'ort,'				)!==false ) $GLOBALS['wisyPortalSpaltenDurchf'] += 32;
    		if( strpos($temp, 'kursnummer,'			)!==false ) $GLOBALS['wisyPortalSpaltenDurchf'] += 64;
    		if( strpos($temp, 'bemerkungen,'			)!==false ) $GLOBALS['wisyPortalSpaltenDurchf'] += 128;
    		/* if( strpos($temp, 'bunummer,'			)!==false ) $GLOBALS['wisyPortalSpaltenDurchf'] += 256; */
    		if( strpos($temp, 'entfernung,'			)!==false ) $GLOBALS['wisyPortalSpaltenDurchf'] += 512;
        }
				
        $this->order = $this->iniRead('kurse.sortierung', false);
        
        $searcher =& createWisyObject('WISY_SEARCH_CLASS', $this);
		
		// Simple Search
		$this->simplified = $this->iniRead('search.simplified', 0);
		
		if($this->simplified)
		{
			$this->filterer =& createWisyObject('WISY_FILTER_CLASS', $this);
			
			// Todo: better solution?
			// This makes sure that a q parameter that is set by a google link and encoded in UTF-8 (like T%C3%B6pfern) is convertet to ISO-8859-15 for search
			// While this also converts non-Umlaaut-strings as well, like "deutsch", that doesn't matter
			// results: "deutsch" (= ASCII) => ISO = 1, UTF-8 = 1 // Töpfern => ISO = 1, UTF-8 = 0 // Tπpfern = ISO = 1, UTF-8 =  1
			if((strpos($_SERVER["HTTP_REFERER"], "google.") !== FALSE) && trim($this->getParam('q', '')) != "" && mb_check_encoding(rawurldecode($this->getParam('q', '')), "ISO-8859-15") && mb_check_encoding(rawurldecode($this->getParam('q', '')), "UTF-8")) // deutsch oder T%C3%B6pfern, nicht (wie es korrekt w√§re ):T%F6pfern (ISO-8859)
			    $this->Q = utf8_decode($this->Q);
			
			$this->tokens = $searcher->tokenize($this->Q);
		}
		else
		{
			$this->tokens = $searcher->tokenize($this->getParam('q', ''));
		}
	}

	/******************************************************************************
	 Read/Write Settings, Cache & Co.
	 ******************************************************************************/

	function iniRead($key, $default='', $html = false)
	{
	    global $wisyPortalEinstellungen;
	    $value = $default;
	    if( isset( $wisyPortalEinstellungen[ $key ] ) )
	    {
	        $value = cs8($wisyPortalEinstellungen[ $key ]);
	    }
	    
	    if($html)
	        $value = htmlentities(html_entity_decode($value));
	        
	        return $value;
	}

	function cacheRead($key, $default='')
	{
		global $wisyPortalEinstcache;
		$value = $default;
		if( isset( $wisyPortalEinstcache[ $key ] ) )
		{
			$value = $wisyPortalEinstcache[ $key ];
		}
		return $value;
	}
	
	function cacheWrite($key, $value)
	{
		global $wisyPortalEinstcache;
		global $s_cacheModified;
		if( $wisyPortalEinstcache[ $key ] != $value )
		{
			$wisyPortalEinstcache[ $key ] = $value;
			$s_cacheModified = true;
		}
	}
	
	function cacheFlush()
	{
		global $s_cacheModified;
		if( $s_cacheModified )
		{
			global $wisyPortalEinstcache;
			global $wisyPortalId;
			$this->cacheFlushInt($wisyPortalEinstcache, $wisyPortalId);
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
		// $db->free();
		// $db->close();
	}
	
	/******************************************************************************
	 SEO
	 ******************************************************************************/
	
	/**
	* #metadescription
	* #socialmedia
	* Outputs and enriches description as useful per page type
	* otherwise default portal description
	**/
    function getMetaDescription($title = "", $description = "") {
		$ret = '';
		
		if(intval(trim($this->iniRead('meta.description'))) != 1)
				return $ret;
		
        $description_parsed = "";
		$skip_contentdescription = false;

		switch($this->getPageType()) {
				case 'kurs':
						$description_parsed = $this->shorten_description($description, 160);
						break;
				case 'anbieter':
                        $description_parsed = $this->shorten_description($description, 160);
						break;
				case 'suche':
						$skip_contentdescription = true;
						break;
				case 'glossar':
                        $description_parsed = $this->shorten_description($description, 160);
						break;
				case 'startseite':
				    $description_parsed = cs8(trim($this->iniRead('meta.description_default', "")));
					break;
                default:
                    $description_parsed = cs8(trim($this->iniRead('meta.description_default', "")));
        }
		
        if($skip_contentdescription) {
            ;
        } else {
            $metadesct_default = cs8(trim($this->iniRead('meta.description_default', "")));
            $ret .= ($description_parsed == "") ? "\n".'<meta name="description" content="'.$metadesct_default.'">'."\n" : "\n".'<meta name="description" content="'.$description_parsed.'">'."\n";
        }
		
		return $ret;
	}
	
	/**
	 * #socialmedia
	 * #metadescription
	*/
	function wiki2HTML($description) {
		$wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this);
        return html_entity_decode(preg_replace("/<br.{0,5}>/i", ". ", $wiki2html->run($description)));
	}
	
	function neutralizeWikiText($description) {
		// If wiki text (Glossar): parse first -> HTML
		// Convert html line feeds to "."; then: strip tags
		$description = $this->wiki2HTML($description);
		return html_entity_decode(preg_replace("/<br.{0,5}>/i", ". ", $description));
	}
	
	function shorten_description($description, $charlength) {
		$description = $this->neutralizeWikiText($description);
	
		// Convert line feeds into simple white spaces
		$description = trim(preg_replace("/[\n\r]/", " ", strip_tags(html_entity_decode($description))));
		
		// 1 white space at a time max.
		$description = preg_replace('/\s+/', ' ', $description);
		
		return mb_substr(trim($description), 0, $charlength);
	}
	
    function generate_page_description($description, $charlength) {
		
		$description = $this->neutralizeWikiText($description);
		
		// Convert line feeds into simple white spaces
		$description = preg_replace("/[\n\r]/", " ", strip_tags(html_entity_decode($description)));
		
		// Keep allowed chars only
		// alphanum. + Umlaut + less special chars + sentence ending chars. Hex-No. -> ISO8859
		$description = preg_replace("/[^a-z\xA4\xBD\xC4\xC5\xC6\xC7\xC8\xC9\xCA\xCB\xCC\xCD\xCE\xD6\xD8\xDC\xDF\xE0\xE1\xE4\xE5\xE6\xE7\xE8\xE9\xEA\xEB\xF1\xF6\xF8\xFC0-9.:,\- ]+/i", "", $description);
		
		// 1 white space at a time max.
		$description = preg_replace('/\s+/', ' ', $description);
		
		return mb_substr(trim($description), 0, $charlength);
	}
	
	// #richtext
	// #metasocialmedia
	public function getAnbieterLogo($anbieterId = -1, $default_symbol_q = false, $default_symbol_43 = false) {
		
		$cacheKey = $anbieterId.intval($default_symbol_q).intval($default_symbol_43);
		
		// Seiten-Cache
		if(is_array($this->getAnbieterLogoCache) && $this->getAnbieterLogoCache[$cacheKey]) 
								return $this->getAnbieterLogoCache[$cacheKey];
							
							
		// Metainfo noch nicht im Seiten-Cache:
		
		if($this->anbieterlogos != null && is_array($this->anbieterlogos) && $this->anbieterlogos[$anbieterId] != null)
			return $this->anbieterlogos[$anbieterId];
	
		$db = new DB_Admin();
	
		// load anbieter							
		if($anbieterId > 0 && $anbieterId != "")
			$db->query("SELECT * FROM anbieter WHERE id=$anbieterId LIMIT 1");
		
		$next_record = $db->next_record(); // Zeiger auf ersten Eintrag...
		
		$logo = $db->fcs8('logo');
				
		if($anbieterId < 0 || $anbieterId == "" || !$next_record || $db->f('freigeschaltet') != 1 || !$logo) {
			if($default_symbol_q)
								return trim($this->iniRead('meta.portallogo_quadrat', ""));
			elseif($default_symbol_43)
								return trim($this->iniRead('meta.portallogo_43', ""));
			else
								return trim($this->iniRead('meta.portallogo', ""));	// return 'Dieser Anbieterdatensatz existiert nicht oder nicht mehr oder ist nicht freigeschaltet.';
		}
		
		if(!is_array($this->anbieterlogos)) { $this->anbieterlogos = array(); }
		
		if(class_exists("G_BLOB_CLASS"))
		  $this->anbieterlogos[$anbieterId] = new G_BLOB_CLASS($logo);
		
		// Seiten-Cache um mehrfache frische Abfrage innerhalb einer Seite zu verhindern
		if(!is_array($this->getAnbieterLogoCache))
			$this->getAnbieterLogoCache = array();
								
		$this->getAnbieterLogoCache[$cacheKey] = $this->anbieterlogos[$anbieterId];
					
		return $this->getAnbieterLogoCache[$cacheKey];
		$db->close();
    }
	
	function pUrl($url) {
		$protocol = $this->iniRead('portal.https', '') ? "https" : "http";
		return str_ireplace(array("https:", "http:"), $protocol.":", $url);
	}
	
	// #socialmedia
	function getSocialMediaTags($pageTitleNoHtml, $ort = "", $anbieter_name = "", $anbieter_ID = -1, $beschreibung = "", $canonicalurl = "") {
        $ret = '';
		
		if(intval(trim($this->iniRead('meta.socialmedia'))) != 1)
				return $ret;
		
        // Facebook
		$ret .= "\n".'<meta property="og:title" content="'.$this->getTitleString($pageTitleNoHtml, $ort, $anbieter_name).'">'."\n";
		
		switch($this->getPageType()) {
				case 'kurs':
					$ret .= '<meta property="og:type" content="product">'."\n";
					break;
				case 'anbieter':
				    $ret .= '<!-- Anbieter -->';
                    $ret .= '<meta property="og:type" content="profile">'."\n";
					break;
				case 'glossar':
                    $ret .= '<meta property="og:type" content="article">'."\n";
					break;
				case 'suche':
                    $ret .= '<meta property="og:type" content="product.group">'."\n";
					$canonicalurl = "search?".$_SERVER['QUERY_STRING']; // explizit angeben, hat aber keine canonical URL
					break;
				case 'startseite':
				    $beschreibung = cs8(trim($this->iniRead('meta.description_default', "")));
					$ret .= '<meta property="og:type" content="article">'."\n";
					$canonicalurl = ""; // no canonical URL in > 5.0
					break;
                default:
                    $beschreibung = cs8(trim($this->iniRead('meta.description_default', "")));
                    $ret .= '<meta property="og:type" content="article">'."\n";
                    $canonicalurl = ""; // no canonical URL in > 5.0
        }
		
		if($beschreibung == "")
		    $beschreibung = cs8(trim($this->iniRead('meta.description_default', "")));
		
		$protocol = $this->iniRead('portal.https', '') ? "https" : "http";
		
		 $logo = ""; $logo_src = ""; 
		 $logo = $this->getAnbieterLogo($anbieter_ID, true, false);	// quadrat = true, 4:3 = false
         if($logo != "") {
		      if(!is_object($logo)) {
		          $logo_src = $logo;
				    $ret .= '<meta property="og:image" content="'.$this->pUrl($logo_src).'">'."\n";
				    $ret .= '<meta property="og:image:secure_url" content="'.$this->pUrl($logo_src).'">'."\n";
				    $ret .= '<meta property="og:image:width" content="200">'."\n";
			        $ret .= '<meta property="og:image:height" content="200">'."\n";
				} else {
				    $logo_src = $protocol."://".$_SERVER['SERVER_NAME']."/admin/media.php/logo/anbieter/".$anbieter_ID."/".$logo->name;
				    $ret .= '<meta property="og:image" content="'.$this->pUrl($logo_src).'">'."\n";
			        $ret .= '<meta property="og:image:secure_url" content="'.$this->pUrl($logo_src).'">'."\n";
				    $ret .= '<meta property="og:image:type" content="'.$logo->mime.'">'."\n";
					$ret .= '<meta property="og:image:width" content="'.$logo->w.'">'."\n";
					$ret .= '<meta property="og:image:height" content="'.$logo->h.'">'."\n";
				}
		}
			
		// GGf. in Bitly aendern, weil Query String ignoriert wird
		$url = $protocol."://".$_SERVER['SERVER_NAME'].'/'.$canonicalurl;
		$url_fb = $url; // sonst muss canonical url uebereinstimmen, um nicht ignoriert zu werden!
		// $url_fb .= (strpos($url, '?') === FALSE) ? $url.'?pk_campaign=Facebook' : $url.'&pk_campaign=Facebook';
		$ret .= '<meta property="og:url" content="'.$url_fb.'">'."\n";
								
		if($beschreibung != "") {
		  $beschreibung = $this->shorten_description($beschreibung, 300);
		  $beschreibung = (strlen($beschreibung) > 297) ? $beschreibung."..." : $beschreibung;
		  $ret .= '<meta property="og:description" content="'.htmlentities($beschreibung).'">'."\n";
		} else {
		  $ret .= '<meta property="og:description" content="...">'."\n";
		}
			
        $ret .= '<meta property="og:locale" content="de_DE">'."\n";
		/* $ret .= '<meta property="fb:app_id" content="<app_id>">'."\n"; */
				
		// Twitter
		$ret .= '<meta name="twitter:card" content="summary">'."\n";
		/* $ret .= '<meta name="twitter:site" content="<@twitterhandle>">'."\n"; // $ret .= '<meta name="twitter:creator" content="<twitterhandle>">'."\n"; */
			
		// GGf. in Bitly aendern, weil Query String ignoriert wird
		$url_twitter = $url; // sonst muss canonical url uebereinstimmen, um nicht ignoriert zu werden!
		// $url_twitter .= (strpos($url, '?') === FALSE) ? $url.'?pk_campaign=Twitter' : $url.'&pk_campaign=Twitter';
		$ret .= '<meta name="twitter:url" content="'.$url_twitter.'">'."\n";
			
		$ret .= '<meta name="twitter:title" content="'.$this->getTitleString($pageTitleNoHtml, $ort, $anbieter_name).'">'."\n";
			
		if($beschreibung != "") {
		  $beschreibung = $this->shorten_description($beschreibung, 200);
		  $beschreibung = (strlen($beschreibung) > 197) ? $beschreibung."..." : $beschreibung;
		}
			
		$ret .= '<meta name="twitter:description" content="'.htmlentities($beschreibung).'">'."\n";
			
		$logo = ""; $logo_src = ""; 
		$logo = $this->getAnbieterLogo($anbieter_ID, false, true);	// quadrat = false, 4:3 = true
   
        if($logo != "") {
		  if(!is_object($logo)) {
		      $logo_src = $logo;
		      $url_logosrc = cs8($this->pUrl($logo_src));
		         // Default Logo Symbol
		         $ret .= '<meta name="twitter:image" content="'.$url_logosrc.'">'."\n";
			     $ret .= '<meta name="twitter:image:width" content="120">'."\n";
			     $ret .= '<meta name="twitter:image:height" content="90">'."\n";
		  } else {
		         $logo_src = $this->purl("https://".$_SERVER['SERVER_NAME']."/admin/media.php/logo/anbieter/".$anbieter_ID."/".$logo->name);
		         $url_logosrc = cs8($this->pUrl($logo_src));
		         $ret .= '<meta name="twitter:image" content="'.$url_logosrc.'">'."\n";
				 $ret .= '<meta name="twitter:image:width" content="'.$logo->w.'">'."\n";
				 $ret .= '<meta name="twitter:image:height" content="'.$logo->h.'">'."\n";
		  }
	    }
			
		
		return $ret;
	}
	
	// #languagedefintion
    function getHreflangTags() {
		$ret = '';
				
		if(intval(trim($this->iniRead('seo.enablelanguagedefinition'))) != 1)
				return $ret;
		
		$defaultlang = trim($this->iniRead('seo.defaultlanguage', "de"));
		
		if(!$defaultlang)
				return $ret;
		
		$ret .= '<link rel="alternate" hreflang="'.$defaultlang.'" href="//'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'">';
		
		return $ret;
	}
	
	/******************************************************************************
	 Various Tools
	 ******************************************************************************/

	function error404($custom_title = "", $add_info = "")
	{
	    /* show an error page. For simple errors, eg. a bad or expired ID, we use an error message in the layout of the portal.
	     However, these messages only work in the root directory (remember, we use relative paths, so whn requesting /abc/def/ghi.html all images, css etc. won't work).
	     So, for errors outside the root directory, we use the global error404() function declared in /index.php */
	    $uri = $_SERVER['REQUEST_URI']; //
	    if( substr_count($uri, '/') == 1 )
	    {
	        global $wisyCore;
	        header("HTTP/1.1 404 Not Found");
	        if(PHP7) ; else header('Content-Type: text/html; charset=utf-8');
	        
	        $title = $custom_title ? $custom_title : 'Fehler 404 - Seite nicht gefunden';
	        
	        echo $this->getPrologue(array('title'=>$title, 'bodyClass'=>'wisyp_error'));
	        echo $this->getSearchField();
	        
	        echo '
						<div class="wisy_topnote">
							<p><b>'.$title.'</b></p>
							<p>Entschuldigung, aber die von Ihnen gew&uuml;nschte Seite konnte leider nicht gefunden werden. Sie k&ouml;nnen jedoch:
								<ul>
									<li><a href="//'.$_SERVER['HTTP_HOST'].'">Die Startseite von '.$_SERVER['HTTP_HOST'].' aufrufen ...</a></li>
									<li><a href="javascript:history.back();">Zur&uuml;ck zur zuletzt besuchten Seite wechseln ...</a></li>
								</ul>
								'.$add_info.'
							</p>
							<p><br><br><small>(Technischer Hinweis: die angeforderte Seite war <i>'.htmlspecialchars($uri).'</i> in <i>/'.htmlspecialchars($wisyCore).'</i> auf <i>' .$_SERVER['HTTP_HOST']. '</i>)</small></p>
						</div>
				';
	        
	        echo $this->getEpilogue();
	        exit();
	    }
	    else
	    {
	        error404();
	    }
	}

	function log($file, $msg)
	{
		// open the file
		$fullfilename = 'files/logs/' . strftime("%Y-%m-%d") . '-' . $file . '.txt';
		$fd = @fopen($fullfilename, 'a');
		
		if( $fd )
		{
			$line = strftime("%Y-%m-%d %H:%M:%S") . ": " . $msg . "\n";	
			@fwrite($fd, $line);
			@fclose($fd);
		}
	}

	function microtime_float()
	{
		// returns the number of seconds needed (as float)
	    list($usec, $sec) = explode(" ", microtime());
	    return ((float)$usec + (float)$sec);
	}

	function stopItem($title)
	{	
		$now = $this->microtime_float();
		$secneeded = isset($this->start_sec)? $now - $this->start_sec : 0.0;
		$this->start_sec = $now;
		echo "----------- stopItem: $title: ".sprintf("%1.3f", $secneeded)." -----------<br />";
	}

	function normalizeNatsortCallback($matches)
	{
		// add leading zeros to a number to allow a 'natural' ordering
		if( strlen($matches[0]) < 10 ) {
			return str_pad($matches[0], 10, '0', STR_PAD_LEFT);
		}
		else {
			return $matches[0];
		}
	}
	function normalizeNatsort($str)
	{
		// lower string
		$str = strtolower($str);
	
		// convert accented characters
		$str = strtr($str,	'áàâåãæçéèêëíìîïñóòôõøúùûýÿ',
							'aaaaaaceeeeiiiinooooouuuyy');
	
		// convert german umlaute
		$str = strtr($str,	array('ä'=>'ae', 'ö'=>'oe', 'ü'=>'ue', 'ß'=>'ss'));
	
		// convert numbers to a 'natural' sorting order
		$str = preg_replace_callback('/[0-9]+/', array($this, 'normalizeNatsortCallback'), $str);
	
		// strip special characters
		$str = strtr($str,	'\'\\!"§$%&/(){}[]=?+*~#,;.:-_<>|@©®£¥  ',
							'                                        ');
	
		// remove spaces
		$str = str_replace(' ', '', $str);
	
		// done
		return $str;
	}

	function getParam($name, $default = false)
	{
		if( isset($_GET[$name]) )
		{
			$param = $_GET[$name];
			if( strtoupper($_GET['ie'])=='UTF-8' )
				$param = utf8_decode($param);
			return $param;
		}
		
		return $default;
	}

	function getUrl($page, $param = 0, $rel = false)
	{
		// create any url; addparam is an array of additional parameters 
		// parameters are encoded using urlencode, however, the whole URL is _not_ HTML-save, you need to call htmlentities() to convert & to &amp;
		
		// if $param is no array, create an empty one
		if( !is_array($param) )
		{
			$param = array();
		}

		// base base page; page names with only one character are followed directly by the ID (true for k12345, a123, g456 etc.)
		$ret = $page;
		if( strlen($page) == 1 && isset($param['id']) )
		{
			$ret .= intval($param['id']);
			unset($param['id']);
		}
		
		// append all additional parameters, for the parameter q= we remove trailing spaces and commas 
		$i = 0;
		reset($param);
		foreach($param as $key => $value)
		{
			if( $key == 'q' )
			{	
				if( $value == '' )
					continue;
				$value = rtrim($value, ', '); // remove trailing ", "
			}
			
			$ret .= ($i? '&' : '?') . $key . '=' . urlencode($value);
			$i++;
		}
		
		if(strpos($ret, 'offset=') === FALSE) {
		    // human trigger of page
		    // code not beautiful:
		    if($i > 0)
		        $ret .= (isset($_GET['qtrigger']) ? '&qtrigger='.$_GET['qtrigger'] : '') . (isset($_GET['force']) ? '&force='.$_GET['force'] : '') . (isset($_GET['showcol']) ? '&showcol='.$_GET['showcol'] : '');
		    else
		        $ret .= (isset($_GET['qtrigger']) ? '?qtrigger='.$_GET['qtrigger'] : '') . (isset($_GET['force']) && !isset($_GET['qtrigger']) ? '?force='.$_GET['force'] : '')  . (!isset($_GET['force']) && !isset($_GET['qtrigger']) && isset($_GET['showcol']) ? '?showcol='.$_GET['showcol'] : '');
		} else {
		    if(isset($_GET['qtrigger']))
		        $ret = str_replace('offset=', 'qtrigger='.$_GET['qtrigger'].'&offset=', $ret);
		    if(isset($_GET['force']))
		        $ret = str_replace('offset=', 'force='.$_GET['force'].'&offset=', $ret);
		    if(isset($_GET['showcol']))
		        $ret = str_replace('offset=', 'showcol='.$_GET['showcol'].'&offset=', $ret);
		}
		
		if($rel)
		  return $ret;
		else
		  return "/".$ret; // #landing
	}

	function getHelpUrl($id)
	{
		// calls getUrl() together with q= parameter -- you should not use this for 
		// retrieving the canoncial URL, use getUrl('g', array('id'=>$id)) instead!
		return $this->getUrl('g',
			array(
				'id'	=>	$id,
				'q'		=>	$this->simplified ? $this->Q : $this->getParam('q', ''),
			));
	}

	function replacePlaceholders_Callback($matches)
	{
		global $wisyPortalName;
		global $wisyPortalKurzname;

		$placeholder = $matches[0];
		if( $placeholder == '__NAME__' )
		{
		    return cs8($wisyPortalName);
		}
		else if( $placeholder == '__KURZNAME__' )
		{
		    return cs8($wisyPortalKurzname);
		}
		else if( $placeholder == '__ANZAHL_KURSE__' || $placeholder == '__ANZAHL_KURSE_G__' )
		{
		    return number_format(intval($this->cacheRead('stats.anzahl_kurse')), 0, ",", ".");
		}
		else if( $placeholder == '__ANZAHL_DURCHFUEHRUNGEN__' )
		{
		    return number_format(intval($this->cacheRead('stats.anzahl_durchfuehrungen')), 0, ",", ".");
		}
		else if( $placeholder == '__ANZAHL_ANBIETER__' || $placeholder == '__ANZAHL_ANBIETER_G__' )
		{
		    return number_format(intval($this->cacheRead('stats.anzahl_anbieter')), 0, ",", ".");
		}
		else if( $placeholder == '__STATISTIK_STAND__')
		{
		    return $this->cacheRead('stats.statistik_stand');
		}
		else if( $placeholder == '__A_PRINT__' )
		{
			return ' href="javascript:window.print();"';
		}
		else if( $placeholder == '__DATUM__' )
		{
			$format = '%u, %d.%m.%Y';
			$weekdays = array('Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag');
			$format = str_replace('%u', $weekdays[ date('w') /*strftime() does not support %u on all system*/ ], $format);
			return strftime($format);
		}
		else if( substr($placeholder, 0, 6) == '__MENU' )
		{
			$prefix = strtolower(str_replace('_', '', $placeholder));
			$menuClass =& createWisyObject('WISY_MENU_CLASS', $this, array('prefix'=>$prefix));
			return $menuClass->getHtml();
		}
		else if( $placeholder == '__Q_HTMLENCODE__' )
		{
			return htmlspecialchars( rtrim( $this->simplified ? $this->Q : $this->getParam('q', ''), ', ') );
		}
		else if( $placeholder == '__Q_URLENCODE__' )
		{
			return urlencode( rtrim( $this->simplified ? $this->Q : $this->getParam('q', ''), ', ') );
		}
		else if( $placeholder == '__CONTENT__' )
		{
			return '__CONTENT__'; // just leave  this as it is, __CONTENT__ is handled separately
		}
		else if( $placeholder == '__FAVLISTLINK__' )
		{
			if( $this->iniRead('fav.use', 0) )
			{
				// link to send favourites
				$mailfav = '';
				if( $this->iniRead('fav.mail', '1') ) {
					$mailsubject = $this->iniRead('fav.mail.subject', 'Kursliste von __HOST__');
					$mailsubject = str_replace('__HOST__', $_SERVER['HTTP_HOST'], $mailsubject);
					$protocol = $this->iniRead('portal.https', '') ? "https" : "http";
					$mailbody = $this->iniRead('fav.mail.body', "Das ist meine Kursliste zum Ausdrucken von __HOST__:\n\n".$protocol."://__HOST__/");
					$mailbody = str_replace('__HOST__', $_SERVER['HTTP_HOST'], $mailbody);
					$mailfav = 'mailto:?subject='.rawurlencode($mailsubject).'&body='.rawurlencode($mailbody);
				}
				return '<span id="favlistlink" data-favlink="' . htmlspecialchars($mailfav) . '"></span>';
			}
			else {
				return '';
			}
		}
		else if( $placeholder == '__MOBILNAVLINK__')
		{
			return '<div id="nav-link"><span>Men&uuml; &ouml;ffnen</span></div>';
		}
		
		return "Unbekannt: $placeholder";
	}

	function replacePlaceholders($snippet)
	{
		return preg_replace_callback('/__[A-Z0-9_]+?__/', array($this, 'replacePlaceholders_Callback'), $snippet);
	}	
	
	function cleanClassname($input, $allowNumbers=false)
	{
	    $output = strtolower($input);
	    $output = strtr($output, array('ä'=>'ae', 'ö'=>'oe', 'ü'=>'ue', 'ß'=>'ss'));
	    if($allowNumbers) {
	        $output = preg_replace('/[^a-z0-9,]/', '', $output);
	    } else {
	        $output = preg_replace('/[^a-z,]/', '', $output);
	    }
	    return $output;
	}


	/******************************************************************************
	 Edit Tools
	 ******************************************************************************/

	function startEditSession()
	{
		if( !$this->editSessionStarted )
		{
			ini_set('session.use_cookies', 1);
			session_name($this->editCookieName);
			session_start();
			if( intval($_SESSION['loggedInAnbieterId']) )
			{
				$this->editSessionStarted = true;
			}
		}
	}
	
	function getEditAnbieterId()
	{
		return $this->editSessionStarted? intval($_SESSION['loggedInAnbieterId']) : -1;
				// nicht "0" zurueckgeben, da es kurse gibt, die "0" als anbieter haben;
				// ein Vergleich mit kursId==getEditAnbieterId() wuerde dann eine unerwartete Uebereinstimmung bringen ...
	}
	

	/******************************************************************************
	 Formatting Tools
	 ******************************************************************************/


	function formatDatum($sqldatum)
	{
		// Datum formatieren
		if( $sqldatum == '' || $sqldatum == '0000-00-00 00:00:00' )
		{
			return '';
		}
		else
		{
			$sqldatum = explode(' ', strtr($sqldatum, '-:', '  '));
			return $sqldatum[2] . '.' . $sqldatum[1] . '.' . substr($sqldatum[0], 2, 2);
		}
	}
	
	function formatTelUrl($tel)
	{
	    return preg_replace("/[^0-9]/", "", $tel);
	}

	function getSeals(&$db, $vars)
	{
		// get all seals
		$seals = array();
		$db->query("SELECT a.attr_id AS sealId, s.glossar AS glossarId FROM anbieter_stichwort a, stichwoerter s WHERE a.primary_id=" . intval($vars['anbieterId']) . " AND a.attr_id=s.id AND s.eigenschaften=" .DEF_STICHWORTTYP_QZERTIFIKAT. " ORDER BY a.structure_pos;");
		while( $db->next_record() )
		    $seals[] = array($db->f('sealId'), $db->f('glossarId'));
	
		// no seals? -> done.
		if( sizeof($seals) == 0 )
			return '';
	
		// get common seal information
		$seit = intval(substr($vars['seit'], 0, 4));
		if( $seit == 0 )
		{
			$db->query("SELECT pruefsiegel_seit FROM anbieter WHERE id=" . intval($vars['anbieterId']));
			$db->next_record();
			$seit = intval(substr($db->f('pruefsiegel_seit'), 0, 4));
		}
		$title = $seit? "Gepr&uuml;fte Weiterbildungseinrichtung seit $seit" : "Gepr&uuml;fte Weiterbildungseinrichtung";
	
		// render the seals
		if( !isset($vars['break']) ) $vars['break'] = '<br />&nbsp;<br />';
		
		$ret = '';
		$sealsOut = 0;
		for( $i = 0; $i < sizeof($seals); $i++ )
		{
			$sealId    = $seals[$i][0];
			$glossarId = $seals[$i][1];
	
			if( $vars['size'] == 'small' )
			{
				$img = "files/seals/$sealId-small.gif";
				if( @file_exists($img) )
				{
					$ret .= '<a href="' . $this->getHelpUrl($glossarId) . '" class="help">';
						$ret .= "<img src=\"$img\" alt=\"Pr&uuml;siegel\" title=\"$title\" class=\"seal_small\"/>";
					$ret .= '</a>';
					$sealsOut++;
					break; // only one logo in small view
				}
			}
			else
			{
				$img = "files/seals/$sealId-large.gif";
				if( @file_exists($img) )
				{
					$ret .= $sealsOut? $vars['break'] : '';
					$ret .= "<img src=\"$img\" alt=\"Pr&uuml;siegel\" title=\"$title\" class=\"seal\" />";
					$sealsOut++;
				}
			}
		}
	
		return $ret;
	}

	function glossarDb(&$db, $table, $id)
	{
	    // get Glossary ID from a database entry
	    $glossarId = 0;
	    $field = $table=='stichwoerter'? 'stichwort' : 'thema';
	    $db->query("SELECT glossar, $field FROM $table WHERE id=$id");
	    if( $db->next_record() ) {
	        if( !($glossarId=$db->f('glossar')) ) {
	            /* $db->query("SELECT id FROM glossar WHERE begriff='" .addslashes($db->fcs8($field)). "'");
	            if( $db->next_record() ) {
	                $glossarId = $db->f('id');
	            } */
	        }
	    }
	    
	    return $glossarId;
	}


	function loadStichwoerter(&$db, $table, $id)
	{
	    // Stichwoerter laden
	    $ret = array();
	    
	    require_once('admin/config/codes.inc.php'); // fuer hidden_stichwort_eigenschaften
	    global $hidden_stichwort_eigenschaften;
	    
	    $sql = "SELECT id, stichwort, eigenschaften, zusatzinfo FROM stichwoerter LEFT JOIN {$table}_stichwort ON id=attr_id WHERE primary_id=$id AND (eigenschaften & $hidden_stichwort_eigenschaften)=0 ORDER BY structure_pos;";
	    $db->query($sql);
	    while( $db->next_record() )
	    {
	        $ret[] = $db->Record;
	    }
	    
	    return $ret;
	}
	
	function loadDerivedTags(&$db, $tags_id, &$distinct_tags, $type)
	{
	    $ret = array();
	    
	    if(strtolower($type) == "synonyme") // also hidden synonyms! just checks if mapped to other tag
	        $sql = 'SELECT DISTINCT id, stichwort, eigenschaften, zusatzinfo FROM stichwoerter, stichwoerter_verweis WHERE (stichwoerter_verweis.primary_id = stichwoerter.id and stichwoerter_verweis.attr_id = '.$tags_id.') OR (stichwoerter_verweis.attr_id = stichwoerter.id and stichwoerter_verweis.primary_id = '.$tags_id.')'; // last part applies if search is based on synonym instead of original tag
	        elseif(strtolower($type) == "unterbegriffe")
	        $sql = "SELECT DISTINCT id, stichwort, eigenschaften, zusatzinfo FROM stichwoerter, stichwoerter_verweis2 WHERE stichwoerter_verweis2.attr_id = stichwoerter.id and stichwoerter_verweis2.primary_id = ".$tags_id;
	        elseif(strtolower($type) == "oberbegriffe")
	        $sql = "SELECT DISTINCT id, stichwort, eigenschaften, zusatzinfo FROM stichwoerter, stichwoerter_verweis2 WHERE stichwoerter_verweis2.primary_id = stichwoerter.id and stichwoerter_verweis2.attr_id = ".$tags_id;
	        else
	            return false;
	            
	            $db->query($sql);
	            while( $db->next_record() )
	            {
	                if(!in_array($db->Record['stichwort'], $distinct_tags)) {
	                    $ret[] = $db->Record;
	                    array_push($distinct_tags,$db->Record['stichwort']);
	                }
	            }
	            
	            return $ret;
	}
	
	function writeDerivedTags($derivedStichwoerter, $filtersw, $typ_name, $originalsw) {
	    $ret = '';
	    for($i = 0; $i < count($derivedStichwoerter); $i++)
	    {
	        
	        $derivedStichwort = $derivedStichwoerter[$i];
	        if(!in_array($derivedStichwort['eigenschaften'], $filtersw)) {
	            $derivedStichwort8 = cs8($derivedStichwort['stichwort']);
	            $ret .= '<span class="typ_'.$derivedStichwort['eigenschaften'].'  orginal_'.$originalsw.' '.strtolower($typ_name).'_raw"><a href="/search?q='.urlencode($derivedStichwort8).(isset($_GET['qtrigger']) ? '&qtrigger='.$_GET['qtrigger'] : '').(isset($_GET['force']) ? '&force='.$_GET['force'] : '').'">'.$derivedStichwort8.'</a></span>, ';
	        }
	    }
	    return $ret;
	}
	
	function getTagFreq(&$db, $tag) {
	    $db->query("SELECT tag_freq FROM x_tags, x_tags_freq WHERE x_tags.tag_name = \"".$tag."\" AND x_tags.tag_id = x_tags_freq.tag_id");
	    if( $db->next_record() )
	        return $db->f('tag_freq');
	}

	#richtext
	function writeStichwoerter($db, $table, $tags, $richtext = false)
	{
		// Stichwoerter ausgeben
		// load codes
		$ret = '';
		global $codes_stichwort_eigenschaften;
		global $hidden_stichwort_eigenschaften;
		require_once("admin/config/codes.inc.php");
		$codes_array = explode('###', $codes_stichwort_eigenschaften);
		
		// go through codes and stichwoerter
		for( $c = 0; $c < sizeof($codes_array); $c += 2 ) 
		{
			if( $codes_array[$c] == 0 )
				continue; // sachstichwoerter nicht darstellen - aenderung vom 30.03.2010 (bp)
			
			if( $codes_array[$c] & $hidden_stichwort_eigenschaften )
				continue; // explizit verborgene Stichworttypen nicht darstellen
				
			$anythingOfThisCode = 0;
			
			for( $s = 0; $s < sizeof((array) $stichwoerter); $s++ )
			{
				$glossarLink = '';
				$glossarId = $this->glossarDb($db, 'stichwoerter', $tags[$s]['id']);
				if( $glossarId ) {
					$glossarLink = ' <a href="' . $this->getHelpUrl($glossarId) . '" class="wisy_help" title="Ratgeber">i</a>';
				}
				
				if( ($tags[$s]['eigenschaften']==0 && intval($codes_array[$c])==0 && $glossarLink)
				 || ($tags[$s]['eigenschaften'] & intval($codes_array[$c])) )
				{
				    // #richtext
				    if(stripos($codes_array[$c+1], "Qualit") !== FALSE) {
				        $award_sw = ($richtext) ? preg_replace("/.Anbietermerkmal./i", "", $tags[$s]['stichwort']) : $tags[$s]['stichwort'];
				        $award1 = ($richtext) ? '<span itemprop="award" content="'.$award_sw.'">' : '';
				        $award2 = ($richtext) ? '</span>' : '';
				    }
				    
				    $strike_tag = array_map("trim", explode(',', $this->iniRead('sw.aussetzen', -1)));
				    $comment_tag = array_map("trim", explode(',', $this->iniRead('sw.kommentieren', -1)));
				    
					if( !$anythingOfThisCode ) {
						$ret .= '<dt class="wisy_stichwtyp'.$tags[$s]['eigenschaften'].'">' . $codes_array[$c+1] . '</dt><dd>';
					}
					else {
						$ret .= '<br />';
					}
					
					if( in_array($stichwoerter[$s]['id'], $strike_tag) && $this->iniRead('sw.aussetzen.text', '') != '')
					 $ret .= '<small style="display: block;">'.$this->iniRead('sw.aussetzen.text', '').'</small>';
					elseif( in_array($stichwoerter[$s]['id'], $comment_tag) && $this->iniRead('sw.kommentieren.text', '') != '')
					 $ret .= '<small style="display: block;">'.$this->iniRead('sw.kommentieren.text', '').'</small>';
					
					$writeAend = false;
					/* 
					// lt. Liste "WISY-Baustellen" vom 5.9.2007, Punkt 8. in "Kursdetails", sollen hier kein Link angezeigt werden.
					// Zitat: "Anzeige der Stichworte ohne Link einblenden" (bp)
					$ret .= '<a title="alle Kurse mit diesem Stichwort anzeigen" href="' .wisy_param('index.php', array('sst'=>"\"{$tags[$s]['stichwort']}\"", 'skipdefaults'=>1, 'snew'=>2)). '">';
					$writeAend = true;
					*/
					
					// #richtext
					$ret .= $award1.$tags[$s]['stichwort'].$award2;
					
					if( $writeAend ) {
						$ret .= '</a>';
					}
					
					if( $tags[$s]['zusatzinfo'] != '' ) {
						$ret .= ' <span class="ac_tag_type">(' . $tags[$s]['zusatzinfo'] . ')</span>';
					}

					$ret .= $glossarLink;
					
					$anythingOfThisCode	= 1;
				}
			}
			
			if( $anythingOfThisCode ) {
				$ret .= '</dd>';
			}
		}
		
		return cs8($ret);
	}
	
	function encode_windows_chars($input) {
		return strtr($input, $this->encode_windows_chars_map);
	}

	function getVollstaendigkeitMsg(&$db, $recordId, $scope = '')
	{
		// Einstellungen der zug. Gruppe und Kursvollstaendigkeit laden
		// die Einstellungen koennen etwa wie folgt aussehen:
		/*
		quality.portal.warn.percent= 80
		quality.portal.warn.msg    = Informationen l&uuml;ckenhaft (nur __PERCENT__% Vollst&auml;ndigkeit)
		quality.portal.bad.percent = 50
		quality.portal.bad.msg     = Informationen unzureichend (nur __PERCENT__% Vollst&auml;ndigkeit)
		quality.edit.warn.percent  = 80
		quality.edit.warn.msg      = Informationen l&uuml;ckenhaft (nur __PERCENT__% Vollst&auml;ndigkeit)
		quality.edit.bad.percent   = 50
		quality.edit.bad.msg       = Informationen unzureichend (nur __PERCENT__% Vollst&auml;ndigkeit)
		quality.edit.bad.banner    = Informationen unzureichend (nur __PERCENT__% Vollst&auml;ndigkeit) - gelistet aus Gr&uuml;nden der Markt&uuml;bersicht
		*/
	
		
		$sql = "SELECT settings s, vollstaendigkeit v FROM user_grp g, kurse k
				WHERE k.user_grp=g.id AND k.id=$recordId";
		$db->query($sql); if( !$db->next_record() ) return array();
	
		$settings			= $db->fcs8('s');
		$settings			= explodeSettings($settings);
		$vollstaendigkeit	= intval($db->fcs8('v'));  if( $vollstaendigkeit <= 0 ) return;
		$ret				= array();
	
		if( $vollstaendigkeit <= intval($settings["$scope.bad.percent"]) )
		{
			$ret['msg'] = $settings["$scope.bad.msg"];
			$ret['banner'] = $settings["$scope.bad.banner"];
		}
		else if( $vollstaendigkeit <= intval($settings["$scope.warn.percent"]) )
		{
			$ret['msg'] = $settings["$scope.warn.msg"];
		}
		
		if( $ret['msg'] != '' ) { $ret['msg'] = str_replace('__PERCENT__', $vollstaendigkeit, $ret['msg']); }
		if( $ret['banner'] != '' ) { $ret['banner'] = str_replace('__PERCENT__', $vollstaendigkeit, $ret['banner']); }
		
		$ret['percent'] = $vollstaendigkeit;
		
		return $ret;
	}

	/******************************************************************************
	 Construct pages
	 ******************************************************************************/
	
	function getAllowFeedbackClass()
	{
		if( !$this->iniRead('feedback.disable', 0) 
		 && !$this->editSessionStarted /*keine Feedback-Funktion fuer angemeldete Anbieter - die Anbieter sind die Adressaten, nicht die Absender*/ )
		{
			return 'wisy_allow_feedback';
		}
		else
		{	
			return '';
		}
	}
	
	function getLinkList($iniPrefix, $sep)
	{
		$ret = '';
		$testI = 1;
		$menuClass = 0;
		while( 1 )
		{
			$value = $this->iniRead("$iniPrefix$testI", '');
			if( $value == '' )
			{
				break;
			}
			else
			{
				if( !is_object($menuClass) )
					$menuClass =& createWisyObject('WISY_MENU_CLASS', $this);
				$menuClass->explodeMenuParam($value, $title, $url, $aparam);
			
				if( $title == '' )
					$title = $url;
				
				$ret .= " $sep <a href=\"$url\"$aparam>$title</a>";
			}
			
			$testI++;
		}
		
		return $ret;
	}
	
	// #enrichtitles
	function getTitleString($pageTitleNoHtml = "", $ort = "", $anbietername = "", $force = false) {
	    
	    $enrichtitles = (intval(trim($this->iniRead('seo.enrich_titles'))) == 1);
	    
	    $title_pre = "";
	    $title_post = "";
	    
	    switch($this->getPageType()) {
	        case 'kurs':
	            $title_pre = "";
	            $title_post = ($ort != "") ? " in ".$ort : "";
	            $title_post .= ($anbietername != "") ? ". Ein Angebot von ".$anbietername : "";
	            break;
	        case 'anbieter':
	            $title_pre = "";
	            $title_post = ($ort != "") ? " in ".$ort : "";
	            $title_post .= ", Anbieterdetails";
	            break;
	        case 'suche':
	            $title_pre = "Suche nach: ";
	            $title_post = "";
	            
	            // different titles according to sort order:
	            $orderby = strtolower($this->getParam('order'));
	            
	            if($orderby != "") {
	                
	                $validOrders = array();
	                
	                if(strpos(strtolower($this->getParam('q')), 'zeige:anbieter') !== FALSE) {
	                    $validOrders = array('a' => 'Anbieter',
	                        'ad' => 'Anbieter umgekehrt alphabetisch',
	                        's' => 'Strasse',
	                        'sd' => 'Strasse umgekehrt alphabetisch',
	                        'p' => 'Postleitzahlen',
	                        'pd' => 'Postleitzahlen absteigend',
	                        'o' => 'Ort',
	                        'od' => 'Ort umgekehrt alphabetisch',
	                        'h' => 'Homepage',
	                        'hd' => 'Homepage umgekehrt alphabetisch',
	                        'e' => 'Kontakt - E-Mail',
	                        'ed' => 'Kontakt - E-Mail umgekehrt alphabetisch',
	                        't' => 'Telefonnummern',
	                        'td' => 'Telefonnummern absteigend',
	                        'creat' => 'Erstellungsdatum von alt nach neu',
	                        'creatd' => 'Erstellungsdatum von neu nach alt');
	                } else {
	                    $validOrders = array('a' => 'Anbieter',
	                        'ad' => 'Anbieter umgekehrt alphabetisch',
	                        't' => 'Angebot',
	                        'td' => 'Angebot umgekehrt alphabetisch',
	                        'b' => 'Termin',
	                        'bd' => 'Termin absteigend',
	                        'd' => 'Dauer',
	                        'dd' => 'Dauer von lang nach kurz',
	                        'p' => 'Preis',
	                        'pd' => 'Preis absteigend',
	                        'o' => 'Ort',
	                        'od' => 'Ort umgekehrt alphabetisch',
	                        'creat' => 'Erstellungsdatum von alt nach neu',
	                        'creatd' => 'Erstellungsdatum von neu nach alt',
	                        'rand' => 'Zufallsprinzip');
	                }
	                
	                if(array_key_exists($orderby, $validOrders)) {
	                    $title_post = ", sortiert nach ".$validOrders[$orderby];
	                } else {
	                    $title_post = ", sortiert nach '".$orderby."'";
	                }
	                
	            }
	            
	            // offset=0 = same title as no offset. Needs different title because Google doenst understand difference.
	            $offsetzusatz = (isset($_GET['offset']) && $_GET['offset'] == 0) ? " gewaehlt " : "";
	            
	            // Doesn't work for searches that lead to internal full text redirect
	            $maxPageNumber = $this->getMaxPageNumber();
	            $maxPageNumber = ($maxPageNumber == 0) ? 1 : $maxPageNumber;
	            
	            $title_post .= ", Seite: ".$this->getCurrPageNumber()." von ".$maxPageNumber.$offsetzusatz;
	            
	            break;
	        case 'glossar':
	            $title_pre = "Ratgeber: ";
	            $title_post = "";
	            break;
	    }
	    
	    
	    if(!$enrichtitles && !$force) { $title_pre = ""; $title_post = ""; }
	    
	    // get the title as a no-html-string
	    global $wisyPortalKurzname;
	    $fullTitleNoHtml  = $title_pre;
	    $fullTitleNoHtml .= $pageTitleNoHtml;
	    $fullTitleNoHtml .= $title_post;
	    $fullTitleNoHtml .= $fullTitleNoHtml? ' - ' : '';
	    $fullTitleNoHtml .= cs8($wisyPortalKurzname); // = default for home page
	    
	    return $fullTitleNoHtml;
	}
	
	// #enrichtitles
	function getTitleTags($pageTitleNoHtml, $ort = "", $anbieter_name = "")
	{
	    return "<title>" .isohtmlspecialchars($this->getTitleString($pageTitleNoHtml, $ort, $anbieter_name)). "</title>"."\n";
	}
	
	function getSqlCount() {
	    $searcher =& createWisyObject('WISY_SEARCH_CLASS', $this);
	    $queryString = $this->getParam('q', '');
	    $searcher->prepare($queryString);
	    if(stripos($queryString, "zeige:Anbieter") === FALSE)
	        return $searcher->getKurseCount();
	        else
	            return $searcher->getAnbieterCount();
	}
	
	// #richtext
	function getMaxPageNumber() {
	    $sqlCount = $this->getSqlCount();
	    $currRowsPerPage = 20;
	    return ceil($sqlCount / $currRowsPerPage);
	}
	
	function getCurrPageNumber() {
	    $currRowsPerPage = 20;
	    $offset = intval($this->getParam('offset'));
	    return ceil(intval($offset / $currRowsPerPage))+1;
	}
	
	function getFaviconFile()
	{
		// get the favicon file
		return $this->iniRead('img.favicon', '');
	}
	
	function getFaviconTags()
	{
		// get the favicon tag(s) (if any)
		$ret = '';
		
		$favicon = $this->getFaviconFile();
		if( $favicon != '' ) 
		{
			$ret .= '<link rel="shortcut icon" type="image/ico" href="' .$favicon. '" />' . "\n"; 
		}
		
		return $ret;
	}

	function getOpensearchFile()
	{
		// get the OpenSearchDescription file
		return 'opensearch';
	}

	function getOpensearchTags()
	{
		// get the OpenSearchDescription Tags (if any)
		global $wisyPortalKurzname;
		$ret = '';
		
		$opensearchFile = $this->getOpensearchFile();
		if( $opensearchFile )
		{
			$ret .= '<link rel="search" type="application/opensearchdescription+xml" href="' . $opensearchFile . '" title="' .htmlspecialchars($wisyPortalKurzname). '" />' . "\n";
		}
		
		return $ret;
	}

	function getRSSFile()
	{
		// get the main RSS file
		$q = rtrim($this->simplified ? $this->Q : $this->getParam('q', ''), ', ');
		return 'rss?q=' . urlencode($q);
	}

	function getRSSTags()
	{
		// get the RSS tag (if there is no query, "alle Kurse" is returned)
		$ret = '';
	
		if( $this->iniRead('rsslink', 0) )
		{
			global $wisyPortalKurzname;
			$q = rtrim($this->simplified ? $this->Q : $this->getParam('q', ''), ', ');
			
			if($q == '' || strpos($q, 'volltext') !== FALSE) // don't allow rss-feed subscriptions for full text searches
			    return false;
			
			$title = $wisyPortalKurzname . ' - ' . ($q==''? 'aktuelle Kurse' : $q);
			$ret .= '<link rel="alternate" type="application/rss+xml" title="'.htmlspecialchars($title).'" href="' .$this->getRSSFile(). '" />' . "\n";
		}
		
		return $ret;
	}

	function getRSSLink()
	{
	    $ret = '';
	    
	    if($this->getRSSFile() == '' || strpos($this->getRSSFile(), 'volltext') !== FALSE) // don't allow rss-feed subscriptions for full text searches
	        return false;
	        
	        if( $this->iniRead('rsslink', 0) )
	        {
	            $ret .= ' <a href="'.$this->getRSSFile().'" class="wisy_rss_link" title="Suchauftrag als RSS-Feed abonnieren">Updates abonnieren</a> ';
	            
	            $glossarId = intval($this->iniRead('rsslink.help', 2953));
	            if( $glossarId )
	            {
	                $ret .= ' <a href="' .$this->getHelpUrl($glossarId). '" class="wisy_help" title="Hilfe">i</a>';
	            }
	        }
	        
	        return $ret;
	}


	function getCSSFiles()
	{
	    // return all CSS as an array
	    global $wisyPortalCSS;
	    global $wisyPortalId;
	    global $wisyCore; // > 51!
	    $coreAbsPath = $_SERVER['DOCUMENT_ROOT'].'/'.$wisyCore.'/';
	    
	    $ret = array();
	    
	    // core styles
	    $date_modified = filectime($coreAbsPath.'core.css');
	    $ret[] = '/core.css' . '?ver='.date("Y-m-d_h-i-s", $date_modified);
	    
	    // core responsive styles
	    $date_modified = filectime($coreAbsPath.'core.responsive.css');
	    $ret[] = '/core.responsive.css' . '?ver='.date("Y-m-d_h-i-s", $date_modified);
	    
	    $date_modified = filectime($coreAbsPath.'/lib/jquery/jquery-ui-1.12.1.custom.min.css');
	    $ret[] = $wisyCore.'/lib/jquery/jquery-ui-1.12.1.custom.min.css' . '?ver='.date("Y-m-d_h-i-s", $date_modified);
	    
	    $date_modified = filectime($coreAbsPath.'/lib/zebra-datepicker/zebra_datepicker.min.css');
	    $ret[] = $wisyCore.'/lib/zebra-datepicker/zebra_datepicker.min.css' . '?ver='.date("Y-m-d_h-i-s", $date_modified);
	    
	    if($this->iniRead('cookiebanner', '') == 1) {
	        $date_modified = filectime($coreAbsPath.'/lib/cookieconsent/cookieconsent.min.css');
	        $ret[] = $wisyCore.'/lib/cookieconsent/cookieconsent.min.css' . '?ver='.date("Y-m-d_h-i-s", $date_modified);
	    }
	    
	    // the portal may overwrite everything ...
	    if( $wisyPortalCSS )
	    {
	        $db = new DB_Admin;
	        $db->query("SELECT date_modified FROM portale WHERE id=$wisyPortalId;");
	        if($db->next_record())
	            $date_modified = $db->f('date_modified');
	            $db->free();
	            $ret[] = '/portal.css'. '?ver='.date("Y-m-d_h-i-s", strtotime($date_modified));
	            // $db->close();
	    }
	    
	    if( ($tempCSS=$this->iniRead('head.css', '')) != '')
	    {
	        $addCss = explode(",", $tempCSS);
	        
	        foreach($addCss AS $cssFile) {
	            $ret[] = trim($cssFile);
	        }
	    }
	    
	    return $ret;
	}

	function getCSSTags()
	{
		// get CSS tags
		$ret = '';
		
		$css = $this->getCSSFiles();
		for( $i = 0; $i < sizeof((array) $css); $i++ )
		{	
			$ret .= '<link rel="stylesheet" type="text/css" href="'.$css[$i].'" />' . "\n";
		}
		
		return $ret;
	}
	
	function getJSFiles()
	{
	    // return all JavaScript files as an array
	    $ret = array();
	    global $wisyCore; // > 51!
	    $coreAbsPath = $_SERVER['DOCUMENT_ROOT'].'/'.$wisyCore.'/';
	    
	    $date_modified = filectime($coreAbsPath.'lib/jquery/jquery-1.12.4.min.js');
	    $ret[] = $wisyCore.'/lib/jquery/jquery-1.12.4.min.js' . '?ver='.date("Y-m-d_h-i-s", $date_modified);
	    
	    $date_modified = filectime($coreAbsPath.'lib/jquery/jquery-ui-1.12.1.custom.min.js');
	    $ret[] = $wisyCore.'/lib/jquery/jquery-ui-1.12.1.custom.min.js' . '?ver='.date("Y-m-d_h-i-s", $date_modified);
	    
	    if($this->simplified)
	    {
	        $date_modified = filectime($coreAbsPath.'jquery.wisy.simplified.js');
	        $ret[] = 'jquery.wisy.simplified.js' . '?ver='.date("Y-m-d_h-i-s", $date_modified);
	    }
	    else
	    {
	        $date_modified = filectime($coreAbsPath.'jquery.wisy.js');
	        $ret[] = 'jquery.wisy.js' . '?ver='.date("Y-m-d_h-i-s", $date_modified);
	    }
	    
	    if( ($tempJS=$this->iniRead('head.js', '')) != '')
	    {
	        $addJs = explode(",", $tempJS);
	        
	        foreach($addJs AS $jsFile) {
	            $ret[] = trim($jsFile);
	        }
	    }
	    
	    return $ret;
	}
	
	function getDeferedJSFiles()
	{
	    // return defered JavaScript files as an array
	    $ret = array();
	    global $wisyCore; // > 51!
	    $coreAbsPath = $_SERVER['DOCUMENT_ROOT'].'/'.$wisyCore.'/';
	    
	    $date_modified = filectime($coreAbsPath.'lib/zebra-datepicker/zebra_datepicker.min.js');
	    $ret[] = $wisyCore.'/lib/zebra-datepicker/zebra_datepicker.min.js' . '?ver='.date("Y-m-d_h-i-s", $date_modified);
	    
	    if($this->iniRead('cookiebanner', '') == 1) {
	        $date_modified = filectime($coreAbsPath.'lib/cookieconsent/cookieconsent.min.js');
	        $ret[] = $wisyCore.'/lib/cookieconsent/cookieconsent.min.js' . '?ver='.date("Y-m-d_h-i-s", $date_modified);
	    }
	    
	    if( ($tempJS=$this->iniRead('head.defered.js', '')) != '')
	    {
	        $addJs = explode(",", $tempJS);
	        
	        foreach($addJs AS $jsFile) {
	            $ret[] = trim($jsFile);
	        }
	    }
	    
	    return $ret;
	}
	
	function addCConsentOption($name, $cookieOptions) {
	    $cookie_essentiell = intval($this->iniRead("cookiebanner.zustimmung.{$name}.essentiell", 0));
	    $expiration = $cookieOptions['cookie']['expiryDays'];
	    $details = "<span class='cookies_techdetails inactive'><br>Speicherdauer:".$expiration." Tage, Name: cconsent_{$name}".($name == 'analytics' ? ', Name: _pk_ref (Speicherdauer: 6 Monate), Name: _pk_cvar (Speicherdauer: 30min.), Name: _pk_id (Speicherdauer: 13 Monate), Name: _pk_ses (Speicherdauer: 30min.)': '').'</span>';
	    // print_r($cookieOptions['cookie']); die("ok");
	    return "<li class='{$name} ".($cookie_essentiell == 2 ? "disabled" : "")."'>
    				<input type='checkbox' name='cconsent_{$name}' "
    				.(($cookie_essentiell || $_COOKIE['cconsent_'.$name] == 'allow') ? "checked='checked'" : "")
    				.($cookie_essentiell == 2 ? "disabled" : "")
    				."> "
    				."<div class='consent_option_infos'>"
    				.$cookieOptions["content"]["zustimmung_{$name}"]
    				."<span class='importance'>"
    				.($cookie_essentiell === 1 ? '<br>(essentiell)' : ($cookie_essentiell == 2 ? '<br>(technisch notwendig)' : '<br><b>(optional'.($_COOKIE['cconsent_'.$name] == 'allow' ? ' - aktiv zugestimmt' : '').')</b>')).$details.'</span>'
    				.'</div>'
    				."</li>";
	}
	
	function getJSHeadTags()
	{
	    // JavaScript tags to include to the header (if any)
	    $ret = '';
	    
	    $js = $this->getJSFiles();
	    for( $i = 0; $i < sizeof((array) $js); $i++ )
	    {
	        $ret .= '<script src="'.$js[$i].'"></script>' . "\n";
	    }
	    
	    $js_defered = $this->getDeferedJSFiles();
	    for( $i = 0; $i < sizeof((array) $js_defered); $i++ )
	    {
	        $ret .= '<script src="'.$js_defered[$i].'" defer></script>' . "\n";
	    }
	    
	    
	    // various global parameters
	    $ret .= "<script>\n";
	    
	    if($this->iniRead('ajax.infoi', '') == 1)
	        $ret .= "window.ajax_infoi = 1;";
	        
	    $searcher =& createWisyObject('WISY_SEARCH_CLASS', $this);
	    if($searcher->getMinChars())
	        $ret .= "window.search_minchars = ".$searcher->getMinChars().";";
	            
	    $ret .= "</script>\n";
	            
	            
	    if(isset($_GET['qtrigger']))
	        $ret .= "<script>window.qtrigger = '".$_GET['qtrigger']."';</script>"."\n";
	        
	    if(isset($_GET['force']))
	        $ret .= "<script> window.force = '".$_GET['force']."';</script>"."\n";
	    
	    // Cookie Banner settings
		if($this->iniRead('cookiebanner', '') == 1) {
			
			$ret .= "<script>\n";
			$ret .= "window.cookiebanner = {};\n";
			$ret .= "window.cookiebanner.optoutCookies = \"{$this->iniRead('cookiebanner.cookies.optout', '')},fav,fav_init_hint\";\n";
			$ret .= "window.cookiebanner.optedOut = false;\n";
			$ret .= "window.cookiebanner.favOptoutMessage = \"{$this->iniRead('cookiebanner.fav.optouthinweis', 'Ihr Favorit konnte auf diesem Computer nicht gespeichert gewerden da Sie die Speicherung von Cookies abgelehnt haben. Sie k&ouml;nnen Ihre Cookie-Einstellungen in den Datenschutzhinweisen anpassen.')}\";\n";
			$ret .= "window.cookiebanner.piwik = \"{$this->iniRead('analytics.piwik', '')}\";\n";
			$ret .= "window.cookiebanner.uacct = \"{$this->iniRead('analytics.uacct', '')}\";\n";

			$ret .= 'window.addEventListener("load",function(){window.cookieconsent.initialise({';

			$cookieOptions = array();
			$cookieOptions['type'] = 'opt-out';
			$cookieOptions['revokeBtn'] = '<div style="display:none;"></div>'; // Workaround for cookieconsent bug. Revoke cannot be disabled correctly at the moment
			$cookieOptions['position'] = $this->iniRead('cookiebanner.position', 'bottom-left');
			
			$cookieOptions['law'] = array();
			$cookieOptions['law']['countryCode'] = 'DE';
			
			$cookieOptions['cookie'] = array();
			$cookieOptions['cookie']['expiryDays'] = intval($this->iniRead('cookiebanner.cookiegueltigkeit', 7));
			
			$cookieOptions['content'] = array();
			$cookieOptions['content']['message'] = $this->iniRead('cookiebanner.hinweis.text', 'Wir verwenden Cookies, um Ihnen eine Merkliste sowie eine Seiten&uuml;bersetzung anzubieten und um Kursanbietern die Pflege ihrer Kurse zu erm&ouml;glichen. Indem Sie unsere Webseite nutzen, erkl&auml;ren Sie sich mit der Verwendung der Cookies einverstanden. Weitere Details finden Sie in unserer Datenschutzerkl&auml;rung.');
			
			
			$this->detailed_cookie_settings_einstellungen = boolval(strlen(trim($this->iniRead('cookiebanner.zustimmung.einstellungen', ''))) > 3); // legacy compatibility
			$cookieOptions['content']['zustimmung_einstellungen'] = $this->iniRead('cookiebanner.zustimmung.einstellungen', false);
			
			if(strlen($cookieOptions['content']['zustimmung_einstellungen']) > 3 && $this->iniRead('cookiebanner.zeige.speicherdauer', ''))
			//			 $cookieOptions['content']['zustimmung_einstellungen'] .= " (".$cookieOptions['cookie']['expiryDays']." Tage)";
			    
			$this->detailed_cookie_settings_popuptext = boolval(strlen(trim($this->iniRead('cookiebanner.zustimmung.popuptext', ''))) > 3); // legacy compatibility
			$cookieOptions['content']['zustimmung_popuptext'] = $this->iniRead('cookiebanner.zustimmung.popuptext', false);
			    
			$this->detailed_cookie_settings_merkliste = boolval(strlen(trim($this->iniRead('cookiebanner.zustimmung.merkliste', ''))) > 3); // legacy compatibility
			$cookieOptions['content']['zustimmung_merkliste'] = $this->iniRead('cookiebanner.zustimmung.merkliste', false);
			    
			$this->detailed_cookie_settings_onlinepflege = boolval(strlen(trim($this->iniRead('cookiebanner.zustimmung.onlinepflege', ''))) > 3); // legacy compatibility
			$cookieOptions['content']['zustimmung_onlinepflege'] = $this->iniRead('cookiebanner.zustimmung.onlinepflege', false);
			    
			$this->detailed_cookie_settings_translate = boolval(strlen(trim($this->iniRead('cookiebanner.zustimmung.translate', ''))) > 3); // legacy compatibility
			$cookieOptions['content']['zustimmung_translate'] = $this->iniRead('cookiebanner.zustimmung.translate', false);
			    
			$this->detailed_cookie_settings_analytics = boolval(strlen(trim($this->iniRead('cookiebanner.zustimmung.analytics', ''))) > 3); // legacy compatibility
			$cookieOptions['content']['zustimmung_analytics'] = $this->iniRead('cookiebanner.zustimmung.analytics', false);
			    
			$toggle_details = "javascript:toggle_cookiedetails();";
			    
			$cookieOptions['content']['message'] = str_ireplace('__ZUSTIMMUNGEN__',
			        '<ul class="cc-consent-details">'
			        .($cookieOptions['content']['zustimmung_einstellungen'] ? $this->addCConsentOption("einstellungen", $cookieOptions) : '')
			        .($cookieOptions['content']['zustimmung_popuptext'] ? $this->addCConsentOption("popuptext", $cookieOptions) : '')
			        .($cookieOptions['content']['zustimmung_onlinepflege'] ? $this->addCConsentOption("onlinepflege", $cookieOptions) : '')
			        .($cookieOptions['content']['zustimmung_merkliste'] ? $this->addCConsentOption("merkliste", $cookieOptions) : '')
			        .($cookieOptions['content']['zustimmung_translate'] ? $this->addCConsentOption("translate", $cookieOptions) : '')
			        .($cookieOptions['content']['zustimmung_analytics'] ? $this->addCConsentOption("analytics", $cookieOptions) : '')
			        .'__ZUSTIMMUNGEN_SONST__'
			        .'</ul>'."<br><a href='".$toggle_details."' class='toggle_cookiedetails inactive'>Cookie-Details</a><br>",
			        $cookieOptions['content']['message']
			        );
			    
			global $wisyPortalEinstellungen;
			reset($wisyPortalEinstellungen);
			$allPrefix = 'cookiebanner.zustimmung.sonst';
			$allPrefixLen = strlen($allPrefix);
			foreach($wisyPortalEinstellungen as $key => $value)
			{
			    if( substr($key, 0, $allPrefixLen)==$allPrefix )
			    {
			        $cookieOptions['content']['message'] = str_replace('__ZUSTIMMUNGEN_SONST__',
			            $this->addCConsentOption("analytics", $key).'__ZUSTIMMUNGEN_SONST__',
			            $cookieOptions['content']['message']);
			    }
			}
			$cookieOptions['content']['message'] = str_replace('__ZUSTIMMUNGEN_SONST__', '', $cookieOptions['content']['message']);
			
			
			$cookieOptions['content']['message'] = str_ireplace('__HINWEIS_ABWAHL__',
			    '<span class="hinweis_abwahl">'
			    .$this->iniRead('cookiebanner.hinweis.abwahl', '(Option abw&auml;hlen, wenn nicht einverstanden)')
			    .'</span>',
			    $cookieOptions['content']['message']);
			
			$cookieOptions['content']['allow'] = $this->iniRead('cookiebanner.erlauben.text', 'Speichern', 1);
			$cookieOptions['content']['allowall'] = $this->iniRead('cookiebanner.erlauben.alles.text', 'inactive', 1); // only display if defined
			$cookieOptions['content']['deny'] = $this->iniRead('cookiebanner.ablehnen.text', 'Ablehnen', 1);
			$cookieOptions['content']['link'] = $this->iniRead('cookiebanner.datenschutz.text', 'Mehr erfahren...', 1);
			$cookieOptions['content']['href'] = $this->iniRead('cookiebanner.datenschutz.link', '');
			
			$cookieOptions['palette'] = array();
			$cookieOptions['palette']['popup'] = array();
			$cookieOptions['palette']['popup']['background'] = $this->iniRead('cookiebanner.hinweis.hintergrundfarbe', '#EEE');
			$cookieOptions['palette']['popup']['text'] = $this->iniRead('cookiebanner.hinweis.textfarbe', '#000');
			$cookieOptions['palette']['popup']['link'] = $this->iniRead('cookiebanner.hinweis.linkfarbe', '#3E7AB8');
			
			$cookieOptions['palette']['button']['background'] = $this->iniRead('cookiebanner.erlauben.buttonfarbe', '#3E7AB8');
			$cookieOptions['palette']['button']['text'] = $this->iniRead('cookiebanner.erlauben.buttontextfarbe', '#FFF');
			
			$cookieOptions['palette']['highlight']['background'] = $this->iniRead('cookiebanner.ablehnen.buttonfarbe', '#FFF');
			$cookieOptions['palette']['highlight']['text'] = $this->iniRead('cookiebanner.ablehnen.buttontextfarbe', '#000');
			
			$ret .= trim(json_encode($cookieOptions, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), '{}') . ',';
			
			// Callbacks for enabling / disabling Cookies
			$ret .= 'onInitialise: function(status) {
						var didConsent = this.hasConsented();
						if(!didConsent) {
							window.cookiebanner.optedOut = true;
							updateCookieSettings();
						}
                        callCookieDependantFunctions();
					},
					onStatusChange: function(status) {
						var didConsent = this.hasConsented();
						if(!didConsent) {
							window.cookiebanner.optedOut = true;
							updateCookieSettings();
						}
                        callCookieDependantFunctions();
					}';
					
			// Hide Revoke Button and enable custom revoke function in e.g. "Datenschutzhinweise"
			// Add an <a> tag with ID #wisy_cookieconsent_settings anywhere on your site. It will re-open the cookieconsent popup when clicked
			$ret .= '},
					function(popup){
						popup.toggleRevokeButton(false);
						window.cookieconsent.popup = popup;
						$("#wisy_cookieconsent_settings").on("click", function() {
							window.cookieconsent.popup.open();
							window.cookiebanner.optedOut = false;
							updateCookieSettings();
							return false;
						});
					}';
			
			$ret .= ');
			    
			/* save detailed cookie consent status */

                jQuery(".cc-btn.cc-allow-all").click(function(){ 
				 jQuery(".cc-consent-details input[type=checkbox]").each(function(){ jQuery(this).attr("checked", "checked") });
				 jQuery(".cc-btn.cc-allow").trigger("click");
 				});

				jQuery(".cc-btn.cc-allow").click(function(){
					jQuery(".cc-consent-details input[type=checkbox]").each(function(){
						var cname = jQuery(this).attr("name");
						$.removeCookie(cname, { path: "/" });';
						
						// if einstellungen in use, einstellungen must be checked to save all other settings
						if(trim($cookieOptions['content']['zustimmung_einstellungen']) != "" && $this->iniRead("cookiebanner.zustimmung.analytics.essentiell", false) !== false)
							$ret .='if( jQuery(this).is(":checked") && jQuery(".cc-consent-details .einstellungen input[type=checkbox]").is(":checked") ) {
								setCookieSafely(cname, "allow", { expires:'.$cookieOptions['cookie']['expiryDays'].'});';
						else // if einstellungen not in use, safe setting as expected
							$ret .='if( jQuery(this).is(":checked") ) {
								setCookieSafely(cname, "allow", { expires:'.$cookieOptions['cookie']['expiryDays'].'});';
							
							// if not autoload: load homepage, with analytics set in order to count this page view AFTER settings saved. If autoload in use, this page view was already counted
							// Cookie check not working b/c matomo opt-out = 3rd party, but: also nor necessary b/c already respected by matomo script
							// & track here only executed once upon save with explicit consent, other pages: no - until de-selected { && '.boolval( !isset($_COOKIE['piwik_ignore']) ).' }
							if(!$this->iniRead("cookiebanner.zustimmung.analytics.autoload", 0)) {
							    $ret .= '
										if(cname == "cconsent_analytics") {
											/* Calling analystics url by calling script in script-tag. Calling via ajax() would not execute script withou eval. */
							        
											if( jQuery("#ga_script").length )
												eval(jQuery("#ga_script").text());
							        
											if( jQuery("#matomo_script").length )
												embedMatomoTracking();
							        
										}';
							}
							
				$ret .= '
						}'; // End: is:checked
						
				// if einstellungen in use and einstellungen not checked delete the fact, that cookie window was interacted with - in addition to not savong all settings
				if(trim($cookieOptions['content']['zustimmung_einstellungen']) != "" && $this->iniRead("cookiebanner.zustimmung.analytics.essentiell", false) !== false)
				    $ret .='if( jQuery(".cc-consent-details .einstellungen input[type=checkbox]").is(":checked") == false)
													setTimeout(function(){ jQuery.cookie("cookieconsent_status", null, { path: "/", sameSite: "Strict" }); } , 500);';
				    
				    $ret .= '});
				});
				
			});
			
		    '.($this->detailed_cookie_settings_einstellungen ? "" : "window.cookiebanner_zustimmung_einstellungen_legacy = 1;").'
			'.($this->detailed_cookie_settings_popuptext ? "" : "window.cookiebanner_zustimmung_popuptext_legacy = 1;").'
			'.($this->detailed_cookie_settings_merkliste ? "" : "window.cookiebanner_zustimmung_merkliste_legacy = 1;").'
			'.($this->detailed_cookie_settings_onlinepflege ? "" : "window.cookiebanner_zustimmung_onlinepflege_legacy = 1;").'
			'.($this->detailed_cookie_settings_translate ? "" : "window.cookiebanner_zustimmung_translate_legacy = 1;").'
			    
			</script>'."\n"; // end initialization of cookie consent window
			
			// already set by script block
			/* // count first visit / page view without interaction
			if( $this->iniRead("cookiebanner.zustimmung.analytics.essentiell", 0) && $this->iniRead("cookiebanner.zustimmung.analytics.autoload", 0) && !isset($_COOKIE['cookieconsent_status']) ) {
			 $ret .= '<script>';
			 $ret .= 'setCookieSafely("cconsent_analytics", "allow", { expires:'.$cookieOptions['cookie']['expiryDays'].' });'." \n";
			 $ret .= 'jQuery.ajax({ url: window.location.href, dataType: \'html\'});'." \n"; // call same page with analytics allowed to count this page view
			 $ret .= '</script>';
			} */
		}
		
		
		// Don't allow for empty searches
		$homepage = trim($this->iniRead('homepage', ''), '/');
		$homepage = ($homepage == "") ? '/' : '/'.$homepage;
		
		$ret .= "<script>\n";
		$ret .= "var homepage = '".$homepage."'"; // neu: jQueryWisy
		// $ret .= "jQuery(document).ready(function() { preventEmptySearch('".$homepage."'); });"; // <-> onload maybe in use already
		$ret .= "</script>";
		
		return $ret;
	}
	
	function match_loginid(&$db, $hoursago) {
	    $visitor_login_id = berechne_loginid();
	    $add_cond = $hoursago > 0 ? "AND last_login >= now() - INTERVAL $hoursago HOUR" : "";
	    $db->query("SELECT id FROM user WHERE last_login_id='$visitor_login_id' ".$add_cond);
	    return $db->num_rows();
	}
	
	function is_editor_active(&$db, $hoursago = 0) {
	    return $this->match_loginid($db, $hoursago); // default: false = normal visitor
	}
	
	function is_frondendeditor_active() {
	    return $this->editSessionStarted; // default: false = normal visitor
	}
	
	function getJSOnload()
	{
		// stuff to add to <body onload=...> - if possible, please prefer jQuery's onload functionality instead of <body onload=...>
		$ret = '';
		if( ($onload=$this->iniRead('onload')) != '' ) { $ret .= ' onload="' .$onload. '" '; }
		
		if( !$this->askfwd ) { $this->askfwd = strval($_REQUEST['askfwd']); }
		if(  $this->askfwd ) { $ret .= ' data-askfwd="' . htmlspecialchars($this->askfwd) . '" '; }
		
		return $ret;
	}
	
	function getCanonicalTag($canocicalUrl)
	{
		// optionally, for SEO, we support canonical urls here
		$ret = '';
		if( $canocicalUrl )
		{
			$ret .= '<link rel="canonical" href="'.$canocicalUrl.'" />' . "\n";
		}
		return $ret;
	}
	
	function getMobileAlternateTag($requestedPage = "")
	{
		$mobile_url = array_map("trim", (explode("|", $this->iniRead('meta.mobile_url', ""))));
		$mobile_maxresolution = (intval($mobile_url[1]) > 0) ? $mobile_url[1] : 640;
		$mobile_url = $mobile_url[0];
	
		if(str_replace("http://", "", $mobile_url) != "" && str_replace("https://", "", $mobile_url) != "")
			$ret .= '<link rel="alternate" media="only screen and (max-width: '.$mobile_maxresolution.'px)" href="'.$mobile_url.'/'.$requestedPage.'" >' . "\n"; // $requestedPage may be empty for homepage
			
		return $ret;
	}
	
	function getAdditionalHeadTags()
	{
		return $this->iniRead('head.additionalTags', '');
	}
	
	function getBodyId($relevantID = 0) {
	    if($relevantID > 0)
	        return 'id'.$relevantID;
	    else
	        return '';
	}
	
	function getBodyClasses($bodyClass)
	{
		// we assign one or more classes to the body tag;
		// this behaviour may be used to emulate simple templates via CSS. In detail, we use the following classes:
		// wisyp_homepage	-	 for the homepage, may be combined with the other wisyp_-classes:
		// wisyp_search, wisyp_kurs, wisyp_anbieter, wisyp_glossar, wisyp_edit, wisyp_error
		
		// add base blass
		$ret = $bodyClass;
		
		// add wisyq_ classes ...
		$added = array();
		$q_org = $this->simplified ? $this->Q : $this->getParam('q', '');
		$q = strtolower($q_org);
		$q = strtr($q, array('ä'=>'ae', 'ö'=>'oe', 'ü'=>'ue', 'ß'=>'ss'));
		$q = preg_replace('/[^a-z,]/', '', $q);
		$q = explode(',', $q);
		for( $i = 0; $i < sizeof($q); $i++ )
		{
			if( $q[$i] != '' && !$added[ $q[$i] ] )
			{
				$ret .= $ret==''? '' : ' ';
				$ret .= 'wisyq_' . $q[$i];
				
				if(strpos($q[$i], 'volltext') === 0)
				    $ret .= ' wisyq_volltext';
				
				$added[ $q[$i] ] = true;
			}
		}
		
		// add homepage class
		$is_homepage = false;
		if( ($temp=$this->iniRead('homepage', '')) != '' && strpos($temp, 'index.php')===false )
		{
			if( substr($_SERVER['REQUEST_URI'], strlen($temp)*-1) == $temp )
				$is_homepage = true;
		}
		else
		{
			if( $bodyClass == 'wisyp_search' && $q_org == '' )
				$is_homepage = true;
		}

		if( $is_homepage )
		{
			$ret .= $ret==''? '' : ' ';
			$ret .= 'wisyp_homepage';
		}
		
		foreach($_GET as $key => $value) {
		    if(is_array($value))
		        continue;
		    
		    $value = trim($value);
		    if(preg_match("/^filter_/", $key) && $value != "") {
		        $ret .= strtolower(' '.$key.'_'.$this->deXSS($value));
		    }
		}
		
		// add nojs class
		$ret .= ' nojs';
		
		// done
		return $ret;
	}
	
	function deXSS($value) {
	    $value = strip_tags(html_entity_decode(urldecode($value)));
	    $value = $this->removeCroco($value);
	    return $value;
	}
	
	function removeCroco($value) {
	    $value = htmlspecialchars(str_replace(array(">", "<", "&lt;", "&gt;"), array("", "", "", ""), $value));
	    return $value;
	}
	
	function getPrologue($param = 0, $relevantID = 0)
	{
		if( !is_array($param) ) $param = array();
		
		// prepare the HTML-Page
		$bodyStart = cs8($GLOBALS['wisyPortalBodyStart']);
		if( strpos($bodyStart, '<html') === false )
		{
		    if($this->iniRead('portal.inframe', '') != 1)
		        header('X-Frame-Options: SAMEORIGIN');
		    
			// we got only an HTML-Snippet (part of the the body part), create a more complete HTML-page from this
			$bodyStart	= '<!DOCTYPE html>' . "\n"
						. '<!--[if !IE]><!--><html lang="de"><!--<![endif]-->' . "\n"
						. '<!--[if gte IE 9]><html class="ie ie9up" lang="de"><![endif]-->' . "\n"
						. '<!--[if IE 8]><html class="ie ie8 ie-old" lang="de"><![endif]-->' . "\n"
						. '<!--[if lte IE 7]><html class="ie ie7down ie-old" lang="de"><![endif]-->' . "\n"							
						. '<head>' . "\n"
						. '<meta charset="UTF-8">' . "\n"
						. '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n"
						. '__HEADTAGS__' 
						. '</head>' . "\n"
						. '<body__BODYATTR__>' . "\n"
						. '<script>document.body.className = document.body.className.replace("nojs","yesjs");</script>' . "\n"
						. '<div class="acclink"><a href="#wisy_contentareaAnchor">Zum Inhalt</a></div>'
						. $bodyStart;
			if( strpos($bodyStart, '__CONTENT__') === false )
			{
				$bodyStart .= '__CONTENT__';
			}
		}
		else
		{
			// we got a full HTML-Page - remove the closing body- and html-tags; we will add them ourselves
			$bodyStart = str_replace('</body>', '', $bodyStart);
			$bodyStart = str_replace('</html>', '', $bodyStart);
		}
		
		// replace ALL placeholders
		// $this->getRSSTags() . 
		$bodyStart = str_replace('__HEADTAGS__', $this->getTitleTags($param['title'], $param['ort'], $param['anbieter_name']) . $this->getFaviconTags() . $this->getOpensearchTags() . $this->getCSSTags() . $this->getCanonicalTag($param['canonical']) . $this->getMobileAlternateTag($param['canonical']) . $this->getJSHeadTags() . $this->getMetaDescription($param['title'], $param['beschreibung']) . $this->getHreflangTags() . $this->getSocialMediaTags($param['title'], $param['ort'], $param['anbieter_name'], $param['anbieter_id'], $param['beschreibung'], $param['canonical']), $bodyStart);
		$bodyStart = str_replace('__BODYATTR__', ' ' . $this->getJSOnload() . (isset($param['id']) ? ' id="' . $this->getBodyID($param['id']) .'"' : '') . ' class="' . $this->getBodyClasses($param['bodyClass']) . ( $this->editSessionStarted ? ' wisyp_edit' : '') . '" ', $bodyStart);
		$bodyStart = $this->replacePlaceholders($bodyStart);
		$i1 = strpos($bodyStart, "<!-- include ");
		if( $i1!==false && ($i2=strpos($bodyStart, "-->", $i1))!==false )
		{
			$before    = substr($bodyStart, 0, $i1);
			$mid       = trim(substr($bodyStart, $i1+12, $i2-$i1-12));
			$after     = substr($bodyStart, $i2+3);
			$bodyStart = $before . file_get_contents($mid) . $after;
		}
		
		// split body end (stuff after __CONTENT__) from bodyStart
		$this->bodyEnd = '';
		if( ($p=strpos($bodyStart, '__CONTENT__')) !== false )
		{
			$this->bodyEnd = substr($bodyStart, $p+11);
			$bodyStart = substr($bodyStart, 0, $p);
		}
		
		// start page
		$ret = $bodyStart . "\n";
		
		$ret .= "\n<!-- content area -->\n";
		$ret .= '<a id="wisy_contentareaAnchor"></a><div id="wisy_contentarea">' . "\n";

		// anbieter-Toolbar
		if( $this->editSessionStarted )
		{
			$editor =& createWisyObject('WISY_EDIT_RENDERER_CLASS', $this);
			$ret .= $editor->getToolbar();
		}	
		
		return $ret;
	}
	
	function getEpilogue()
	{	
		// get page epilogue
		$ret .= "\n";

		$ret .= '</div>' . "\n"; // /wisy_contentarea
		$ret .= "<!-- /content area -->\n";
		
		// footer (wrap in __CONTENT__)
		$ret .= "\n<!-- after content -->\n";
		$ret .= $this->bodyEnd? ($this->bodyEnd . "\n") : '';
		$ret .= "<!-- /after content -->\n\n";

		// analytics stuff at bottom to avoid analytics slowing down
		// the whole site ...
		$ret .= $this->getAnalytics();
		
		$ret .= $this->getPopup();
		
		// iwwb specials
		if( $this->iniRead('iwwbumfrage', 'unset')!='unset' && $_SERVER['HTTPS']!='on')
		{
			require_once('files/iwwbumfrage.php');
		}
		
		$ret .= '</body>' . "\n";
		$ret .= '</html>' . "\n";
		
		return $ret;
	}
	
	function getPopup() {
	    $ret = "";
	    
	    // if cookie popuptext denied or not set (first page view) show text popup if activated and text available
	    if( $this->iniRead('popup', false) && strlen(trim($this->iniRead('popup.text', ''))) && ( (isset($_COOKIE['cconsent_popuptext']) && $_COOKIE['cconsent_popuptext'] == 'deny') || !isset($_COOKIE['cconsent_popuptext'])) )
	        $ret = '
				<div class="hover_bkgr_fricc">
						<span class="helper"></span>
							<div>
        <div class="popupCloseButton">&times;</div>
        <p>'.trim($this->iniRead('popup.text', '')).'</p>
							</div>
				</div>';
	        
	        return $ret;
	}
	
	function getAnalytics() {
		$ret = "\n";
		
		$uacct = $this->iniRead('analytics.uacct', '');
		if( $uacct != '' )
		{
			$ret .= '
				<script id="ga_script">
				'.($this->detailed_cookie_settings_analytics ? 'var optedOut = (jQuery.cookie("cconsent_analytics") != "allow");' : ' var optedOut = (document.cookie.indexOf("cookieconsent_status=deny") > -1);').'
				
				
				var gaProperty = "' . $uacct . '";
				var disableStr = "ga-disable-" + gaProperty;
				// Set Optout-Array if opt-out cookie already set
				if (document.cookie.indexOf(disableStr + "=true") > -1) {
						window[disableStr] = true;
				}

				// Opt-out funtion sets cookie + and opt-out-array
				function gaOptout() {
					document.cookie = disableStr + "=true; expires=Thu, 31 Dec 2099 23:59:59 UTC; path=/";
					window[disableStr] = true;					
				}

				if (!optedOut) {				
					(function(i,s,o,g,r,a,m){i["GoogleAnalyticsObject"]=r;i[r]=i[r]||function(){ 
						(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o), 
						m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m) 
					})(window,document,"script","https://www.google-analytics.com/analytics.js","ga"); 
					ga("create", "' . $uacct . '", { cookieFlags: "max-age='. ( $expiryDays * 24 * 3600 ) .';secure;samesite=none" });
					ga("set", "anonymizeIp", true); 
					ga("send", "pageview"); 
				} else {
					/* console.log("No Analytics: opted out"); */
				}
				</script>';
		}
		
		$piwik = '';
		if($this->detailed_cookie_settings_analytics && $_COOKIE['cconsent_analytics'] != 'allow')
		    $piwik = '';
		else
		    $piwik = $this->iniRead('analytics.piwik', '');
		        
		if( $piwik != '' )
		{
			if( strpos($piwik, ',')!==false ) {
				list($piwik_site, $piwik_id) = explode(',', $piwik);
			}
			else {
				$piwik_site = 'statistik.kursportal.info';
				$piwik_id = $piwik;
			}
			
			$ret .= "
				<!-- Matomo -->
				<!-- analytics.piwik -->
				<script type=\"text/javascript\" id=\"matomo_script\">
						var _paq = window._paq || [];
						_paq.push(['trackPageView']);
						_paq.push(['enableLinkTracking']);
			    
						function embedMatomoTracking() {
								var u=\"//".$piwik_site."/\";
								_paq.push(['setTrackerUrl', u+'matomo.php']);
								_paq.push(['setSiteId', ".$piwik_id."]);
								var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
								g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
						};
								    
						/* console.log('".($_COOKIE['cconsent_analytics'] != 'allow')."'); */
				</script>
				<!-- /analytics.piwik -->
				<!-- End Matomo Code -->
				";
		}
		
		$do_track_matomo = ( $piwik && $this->detailed_cookie_settings_analytics && $_COOKIE['cconsent_analytics'] == 'allow' ) || ( $piwik && $this->detailed_cookie_settings_analytics && $this->iniRead("cookiebanner.zustimmung.analytics.autoload", 0) );
		
		// Load if piwik defined and cookie consent allow OR piwik defined and autoload
		// Cannot check for !isset($_COOKIE['piwik_ignore'] b/c thrd.party cookie statistik..., but not necessary either, b/c cookie resepekted by matomoscript
		// Alternative https://developer.matomo.org/guides/tracking-javascript-guide#optional-creating-a-custom-opt-out-form
		if( $do_track_matomo ) {
		    $ret .= "
				<!-- Execute Matomo Tracking-->
				<script type=\"text/javascript\">
					setTimeout(function () {
							embedMatomoTracking();
					}, 5);
				</script>
				";
		    
		}
		
		return $ret;
	}
	
	function getSearchField()
	{
		// get the query
		$q = $this->simplified ? $this->Q : $this->getParam('q', '');
		$q_orig = $q;
		$tokens = $this->tokens;
		
		// radius search?
		if( $this->iniRead('searcharea.radiussearch', 0) )
		{
			// extract radius search parameters from query string
			$km_arr = array('' =>	'Umkreis', '10' => '10 km', '25' => '25 km', '50' => '50 km', '500' => '>50 km');
			
			$q = '';
			$bei = '';
			$km = '';			
			for( $i = 0; $i < sizeof((array) $tokens['cond']); $i++ ) {
				switch( $tokens['cond'][$i]['field'] ) {
					case 'bei':	
						$bei = $tokens['cond'][$i]['value']; 
						break;
						
					case 'km':	
						$km =  intval($tokens['cond'][$i]['value']);
						if( $km <= 0 ) $km = '';
						if( !$km_arr[$km] ) $km_arr[$km] = "$km km";
						break;
					
					default:
						$q .= $q ? ', ' : '';
						$q .= $tokens['cond'][$i]['field'] != 'tag' ? ($tokens['cond'][$i]['field'].':') : '';
						$q .= $tokens['cond'][$i]['value'];
						break;
				}
			}
		}
			if( isset($tokens['show']) && $tokens['show'] == 'anbieter' ) {
				$q .= $q ? ', ' : '';
				$q .= 'Zeige:Anbieter';
			}

		// if the query is not empty, add a comma and a space		
		$q = trim($q);
		if( $q != '' )
		{
			if( substr($q, -1) != ',' )
				$q .= ',';
			$q .= ' ';
		}

		// echo the search field
		$DEFAULT_PLACEHOLDER	= '';
		$DEFAULT_ADVLINK_HTML	= '<a href="advanced?q=__Q_URLENCODE__" id="wisy_advlink">Erweitern</a>';
		$DEFAULT_RIGHT_HTML		= '|&nbsp;<a href="javascript:window.print();">Drucken</a>';
		$DEFAULT_BOTTOM_HINT	= 'bitte <strong>Suchw&ouml;rter</strong> eingeben - z.B. Englisch, VHS, Bildungsurlaub, ...';
		
		echo "\n";
		
		// #richtext
		$richtext = (intval(trim($this->iniRead('meta.richtext'))) === 1);
		$aboutpage = intval(trim($this->iniRead('meta.aboutpage')));
		$contactpage = intval(trim($this->iniRead('meta.contactpage')));
		
		global $wisyRequestedFile;
		
		$schema = "https://schema.org/WebSite";
		$pagetype = $this->getPageType();
		$schema = ($pagetype == "suche") ? "https://schema.org/SearchResultsPage" : $schema;
		$schema = ($pagetype == "glossar" || $pagetype == "anbieter" || $pagetype == "kurs") ? "https://schema.org/ItemPage" : $schema;
		$schema = ($wisyRequestedFile == "g".$aboutpage) ? "https://schema.org/AboutPage" : $schema;
		$schema = ($wisyRequestedFile == "g".$contactpage) ? "https://schema.org/ContactPage" : $schema;
		
		if($richtext) {
		    echo '<div itemscope itemtype="'.$schema.'">';
		    
		    $websiteurl .= trim($this->iniRead('meta.portalurl', ""));
		    
		    if($websiteurl)
		        $metatags .= '<meta itemprop="url" content="'.$websiteurl.'">'."\n";
		        
		}
		
		if($pagetype != "suche") {
		    $searchAction = ($richtext) ? 'itemprop="potentialAction" itemscope itemtype="https://schema.org/SearchAction"' : '';
		    $target = ($richtext) ? '<meta itemprop="target" content="https://'.$_SERVER['SERVER_NAME'].'/search?qs={qs}"/>' : '';
		    if($pagetype == "startseite") { $q = $this->iniRead('searcharea.placeholder', $DEFAULT_PLACEHOLDER); }
		    $queryinput = ($richtext) ? 'itemprop="query-input" ': ''; // placeholder="'.$q.'"
		    $q = ""; // sonst aendert sich mit jedr Seite der DefaultValue
		} else {
		    $searchAction = ($richtext) ? 'itemscope itemtype="https://schema.org/FindAction"' : '';
		    $target = ($richtext) ? '
				<meta itemprop="target" content="https://'.$_SERVER['SERVER_NAME'].'/search?qs={qs}"/>
				<link itemprop="actionStatus" href="https://schema.org/CompletedActionStatus">' : '';
		    $queryinput = '';
		}
		
		echo $this->getSchemaWebsite();
		// Ende: #richtext
		
		// Kurse oder Anbieter?
		
		$searchinput_placeholder = $this->iniRead('searcharea.placeholder', $DEFAULT_PLACEHOLDER);
		$searchbutton_value = $this->iniRead('searcharea.searchlabel', 'Suche');
		$autocomplete_class = 'ac_keyword';
		
		if( isset($tokens['show']) && $tokens['show'] == 'anbieter' ) {
		    $searchinput_placeholder = $this->iniRead('searcharea.anbieter.placeholder', $searchinput_placeholder);
		    $searchbutton_value = $this->iniRead('searcharea.anbieter.searchlabel', $searchbutton_value);
		    $autocomplete_class = 'ac_keyword_anbieter';
		    $hint = $this->iniRead('searcharea.anbieter.hint', $searchinput_placeholder);
		}
		
		echo "\n" . '<div id="wisy_searcharea" class="activefilters_cnt'.$this->filterer->getActiveFiltersCount().'">' . "\n";
		echo '<div class="inner">' . "\n";
		echo '<form action="search" method="get" '.$searchAction.'>' . "\n" . $target; // #richtext
		echo '<div class="formrow wisyr_searchinput">';
		echo '<label for="wisy_searchinput">' . $this->iniRead('searcharea.placeholder', $DEFAULT_PLACEHOLDER) . '</label>';
		if($this->simplified)
		{
		    // #richtext
		    // Todo: better solution?
		    // This makes sure that a q parameter that is set by a google link and encoded in UTF-8 (like T%C3%B6pfern) is convertet to ISO-8859-15 for search
		    // While this also converts non-Umlaaut-strings as well, like "deutsch", that doesn't matter
		    // results: "deutsch" (= ASCII) => ISO = 1, UTF-8 = 1 // Töpfern => ISO = 1, UTF-8 = 0 // Tπpfern = ISO = 1, UTF-8 =  1
		    if((strpos($_SERVER["HTTP_REFERER"], "google.") !== FALSE) && trim($this->getParam('q', '')) != "" && mb_check_encoding(rawurldecode($this->getParam('q', '')), "ISO-8859-15") && mb_check_encoding(rawurldecode($this->getParam('q', '')), "UTF-8")) // deutsch oder T%C3%B6pfern, nicht (wie es korrekt w√§re ):T%F6pfern (ISO-8859)
		        $this->QS = utf8_decode($this->QS);
		        
		        $qs = $this->QS;
		        echo '<input '.$queryinput.' type="text" id="wisy_searchinput" class="' . $autocomplete_class . '" name="qs" value="' .$qs. '" placeholder="' . $searchinput_placeholder . '" data-onemptyvalue="' . $this->iniRead('search.emptyvalue', '') . '"/>' . "\n";
		        echo '<input type="hidden" id="wisy_searchinput_q" name="q" value="' . $this->Q . '" />' . "\n";
		        echo '<input type="hidden" id="wisy_searchinput_qf" name="qf" value="' . $this->QF . '" />' . "\n";
		        
		        // if(isset($_GET['qtrigger']))
		        //    echo '<input type="hidden" id="qtrigger" name="qtrigger" value="' . $_GET['qtrigger'] . '" />' . "\n";
		        
		        // if(isset($_GET['force']))
		        //    echo '<input type="hidden" id="force" name="force" value="' . $_GET['force'] . '" />' . "\n";
		        
		        if( isset($tokens['show']) && $tokens['show'] == 'anbieter' ) {
		            echo '<input type="hidden" name="filter_zeige" value="Anbieter" />';
		        }
		        $active_filters = $this->filterer->getActiveFilters();
		        $hintwithfilters = $this->iniRead('searcharea.hintwithfilters', 0);
		        if($active_filters == '' || $hintwithfilters) {
		            $hint = ($hintwithfilters && $active_filters) ? $hintwithfilters : ($hint =="") ? $this->replacePlaceholders($this->iniRead('searcharea.hint', $DEFAULT_BOTTOM_HINT)) : $hint;
		            echo '<div class="wisy_searchhints">' .  $hint;
		            
		            if($_GET['anbieterRedirect'] == 1)
		                echo "<br><br><b>Ihre Suche hat zu genau 1 Anbieter-Datensatz gef&uuml;hrt:</b>";
		                
		                echo "</div>\n";
		        }
		}
		else
		{
		    echo '<input type="text" id="wisy_searchinput" class="' . $autocomplete_class . '" name="q" value="' .$q. '" placeholder="' . $searchinput_placeholder . '" />' . "\n";
		}
		echo '</div>';
		
		if($active_filters != '') {
		    echo '<ul class="wisyr_activefilters">' . $active_filters . '</ul>';
		}
					
					if( !$this->simplified && $this->iniRead('searcharea.radiussearch', 0) )
					{
						echo '<div class="formrow wisyr_beiinput">';
							echo '<label for="bei">bei</label>';
							echo '<input type="text" id="wisy_beiinput" class="ac_keyword_ort" name="filter_bei" value="' .$bei. '" placeholder="Ort" />' . "\n";
							echo '<input type="hidden" name="km" value="' . $km . '" />';
						echo '</div>';
                		echo '<div class="formrow wisyr_kmselect">';
							echo '<label for="km">km</label>';
							echo '<select id="wisy_kmselect" name="km" >' . "\n";
								foreach( $km_arr as $value=>$descr ) {
									$selected = strval($km)==strval($value)? ' selected="selected"' : '';
									echo "<option value=\"$value\"$selected>$descr</option>";
								}
							echo '</select>' . "\n";
						echo '</div>';
					}
					echo '<input type="submit" id="wisy_searchbtn" value="' . $searchbutton_value . '" />' . "\n";
					if( $this->iniRead('searcharea.advlink', 1) )
					{
						echo '' . "\n";
					}
				
					echo $this->replacePlaceholders($this->iniRead('searcharea.advlink', $DEFAULT_ADVLINK_HTML)) . "\n";
					echo $this->replacePlaceholders($this->iniRead('searcharea.html', $DEFAULT_RIGHT_HTML)) . "\n";
				echo '</form>' . "\n";
			echo "\n</div><!-- /.inner -->";
		echo "\n</div><!-- /#wisy_searcharea -->\n\n";
		
		if($richtext) {
		    echo "</div> <!-- / itemscope itemtype Website -->\n\n";
		}
	
		echo $this->replacePlaceholders( $this->iniRead('searcharea.below', '') ); // deprecated!
	}
	
	// #richtext
	function getSchemaWebsite() {
	    $websitename = ''; $websiteurl = ""; $metatags = "";
	    
	    if(intval(trim($this->iniRead('meta.richtext'))) === 1) {
	        $websitename .= trim($this->iniRead('meta.portalname', ""));
	        $websiteurl .= trim($this->iniRead('meta.portalurl', ""));
	    }
	    
	    if($websitename)
	        $metatags .= '<meta itemprop="name" content="'.strtoupper($websitename).'">'."\n";
	        
	        if($websiteurl)
	            $metatags .= '<meta itemprop="url" content="'.$websiteurl.'">'."\n";
	            
	            // Datum des DF1-Startdatums, um SERP-Eintrag-Datum in Eingklang zu bekommen
	            // if($websiteurl)
	            // $metatags .= "<meta name='datePublished' itemprop='datePublished' content='".$YDP."-".$mDP."-".dDP."."'>"."\n";
	            
	            return $metatags;
	}
	
	function getPageType() {
		
		// Der Konstruktor ist jeweils sehr leichtgewichtig,
		// darum koennen ruhig neue Objekte erzeugt werden.
		// Andernfalls muesste man hier den getRenderercode mehr oder weniger duplizieren...
		$result = $this->getRenderer();
		
		if(!is_object($result))
			return false;

		if($_SERVER['REQUEST_URI'] == "/") {
			return 'startseite';
		}
		
		// Dieser sollte beim Ueberschreiben von Kernfunktionen immer gleich sein:
		switch(str_replace(array("CUSTOM_", "DEV_", "ALPHA_", "BETA_"), "", get_class($result))) {
			case 'WISY_SEARCH_RENDERER_CLASS':
			case 'SEARCH_RENDERER_CLASS':
				return "suche";
			case 'WISY_ADVANCED_RENDERER_CLASS':
			case 'ADVANCED_RENDERER_CLASS':
				return "advanced";
			case 'WISY_KURS_RENDERER_CLASS':
			case 'KURS_RENDERER_CLASS':
				return "kurs";
			case 'WISY_ANBIETER_RENDERER_CLASS':
			case 'ANBIETER_RENDERER_CLASS':
				return "anbieter";
			case 'WISY_GLOSSAR_RENDERER_CLASS':
			case 'GLOSSAR_RENDERER_CLASS':
			    return "glossar";
			case 'WISY_EDIT_RENDERER_CLASS':
			case 'EDIT_RENDERER_CLASS':
			    return "edit"; // never reached?
			default:
			    return false;
		}
	}

	/******************************************************************************
	 main()
	 ******************************************************************************/

	function &getRenderer()
	{
		// this function returns the renderer object to use _or_ a string with the URL to forward to
		global $wisyRequestedFile;

		switch( trim($wisyRequestedFile, '/') )
		{
			// homepage
			// (in WISY 5.0 gibt es keine Datei "index.php", diese wird vom Framework aber als Synonym fuer "Homepage" verwendet)
			case 'index.php':
				for( $i = 1; $i <= 9; $i++ ) 
				{
					$prefix = $i==1? 'switch' : "switch{$i}";
					$switch_dest = $this->iniRead("$prefix.dest", '');
					$if_browser = $this->iniRead("$prefix.if.browser", '');
					if( $switch_dest && $if_browser && preg_match("/". $if_browser ."/i", $_SERVER['HTTP_USER_AGENT'])) {
						if( $this->iniRead("$prefix.ask", 1) ) {
							$this->askfwd = $switch_dest;
							break;
						}
						else {
							return $switch_dest;
						}
					}
					else {
						break;
					}
				}

				if( ($temp=$this->iniRead('homepage', '')) != '' && strpos($temp, 'index.php')===false )
				{
					if( $this->askfwd ) {
						$temp .= (strpos($temp, '?')!==false? '&' : '?') . 'askfwd=' . urlencode($this->askfwd);
					}
					return $temp;
				}
				else
				{
					return createWisyObject('WISY_SEARCH_RENDERER_CLASS', $this);
				}

			// search
			case 'search':
				return createWisyObject('WISY_SEARCH_RENDERER_CLASS', $this);
			
			case 'advanced':
				return createWisyObject('WISY_ADVANCED_RENDERER_CLASS', $this);
				
			case 'filter':
				return createWisyObject('WISY_FILTER_RENDERER_CLASS', $this);
	
			case 'tree':
				return createWisyObject('WISY_TREE_RENDERER_CLASS', $this);
	
			case 'geocode':
				return createWisyObject('WISY_OPENSTREETMAP_CLASS', $this);
	
			// view
			default:
				$firstLetter = substr($wisyRequestedFile, 0, 1);
				$_GET['id'] = intval(substr($wisyRequestedFile, 1));
	
				if( $firstLetter=='k' && $_GET['id'] > 0 )
				{
					return createWisyObject('WISY_KURS_RENDERER_CLASS', $this);
				}
				else if( $firstLetter=='a' && $_GET['id'] > 0 )
				{
					return createWisyObject('WISY_ANBIETER_RENDERER_CLASS', $this);
				}
				else if( $firstLetter=='g' && $_GET['id'] > 0 )
				{
					return createWisyObject('WISY_GLOSSAR_RENDERER_CLASS', $this);
				}
				else if( ($content=$this->iniRead('fakefile.'.$wisyRequestedFile, '0'))!='0' )
				{
					echo str_replace('<br />', '\n', $content);
					exit();
				}
				// #vanityurl
				else if( ($gid=$this->iniRead('glossaralias.'.$wisyRequestedFile, '0'))!='0' )
				{
				    // Wenn sinnvolle Glossar-ID: Ist max. 20-stellige Zahl, die nicht mit 0 anfaengt
				    if(preg_match("/^[1-9][0-9]{1,20}$/", $gid))
				    {
				        $_GET['id'] = trim($gid);	// unschoen, aber hier nicht sinnvoll anders moeglich?.
				        return createWisyObject('WISY_GLOSSAR_RENDERER_CLASS', $this);
				    }
				}
				break;

			// misc
			case 'sync':
				return createWisyObject('WISY_SYNC_RENDERER_CLASS', $this);
		    
			case 'menucheck':
			    return createWisyObject('WISY_MENUCHECK_CLASS', $this);
			
			case 'autosuggest':
				return createWisyObject('WISY_AUTOSUGGEST_RENDERER_CLASS', $this);

			case 'autosuggestplzort':
				return createWisyObject('WISY_AUTOSUGGESTPLZORT_RENDERER_CLASS', $this);
				
			case 'opensearch':
				return createWisyObject('WISY_OPENSEARCH_RENDERER_CLASS', $this);

			case 'rss':
				; // return createWisyObject('WISY_RSS_RENDERER_CLASS', $this, array('q'=>$this->simplified ? $this->Q : $this->getParam('q', '')));

			case 'portal.css':
				return createWisyObject('WISY_DUMP_RENDERER_CLASS', $this, array('src'=>$wisyRequestedFile));

			case 'feedback':
				return createWisyObject('WISY_FEEDBACK_RENDERER_CLASS', $this);

			case 'edit':
				return createWisyObject('WISY_EDIT_RENDERER_CLASS', $this);
				
			case 'robots.txt':
			case 'sitemap.xml':
			case 'sitemap.xml.gz':
			case 'terrapin':
				return createWisyObject('WISY_ROBOTS_RENDERER_CLASS', $this, array('src'=>$wisyRequestedFile));

			case 'paypalok':	 //  paypal does not forward any url-parameters, so we need a "real" file as kursportal.info/paypalok
			case 'paypalcancel': //   - " -
				return 'edit?action=kt';

			case 'paypalipn':
				return createWisyObject('WISY_BILLING_RENDERER_CLASS', $this);
				
			case 'surveyresult':
			     $insert_surveyresult = 'INSERT IGNORE INTO tickets SET '
			         .'msgid="'.md5(microtime()).'", '
			         .'date_created="'.date("Y-m-d H:i:s").'", '
			         .'date_modified="'.date("Y-m-d H:i:s").'", '
			         .'von_name="--", '
			         .'von_email="'.utf8_decode($_POST['E-Mail']).'", '
			         .'antwortan_name = "--", '
			         .'antwortan_email="'.utf8_decode($_POST['E-Mail']).'", '
			         .'betreff="'.$this->iniRead('survey.betreff', "").'", '
			         .'nachricht_txt="'
			         .$this->iniRead('survey.f1.label', "").'\n'.utf8_decode($_POST['survey.f1.postname']).'\n\n'
			         .$this->iniRead('survey.f2.label', "").'\n'.utf8_decode($_POST['survey.f2.postname']).'\n\n'
			         .$this->iniRead('survey.f3.label', "").'\n'.utf8_decode($_POST['survey.f3.postname']).'\n\n'
			         .$this->iniRead('survey.f4.label', "").'\n'.utf8_decode($_POST['survey.f4.postname']).'\n\n'
			         .$this->iniRead('survey.f5.label', "").'\n'.utf8_decode($_POST['survey.f5.postname']).'\n\n'
			         .$this->iniRead('survey.f6.label', "").'\n'.utf8_decode($_POST['survey.f6.postname']).'\n\n'
			         .$this->iniRead('survey.f7.label', "").'\n'.utf8_decode($_POST['survey.f7.postname']).'\n\n'
			         .$this->iniRead('survey.f8.label', "").'\n'.utf8_decode($_POST['survey.f8.postname']).'\n\n'
			         .$this->iniRead('survey.f9.label', "").'\n'.utf8_decode($_POST['survey.f9.postname']).'\n\n'
			         .$this->iniRead('survey.f10.label', "").'\n'.utf8_decode($_POST['survey.f10.postname']).'\n\n'
			         .'", '
			         .'nachricht_html="", '
			         .'groesse="1kB", '
			         .'notizen="", '
                     .'status=0, '
                     .'user_created='.$this->iniRead('survey.user_nr', "").', '
			         .'user_modified='.$this->iniRead('survey.user_nr', "").', '
			         .'user_grp='.$this->iniRead('survey.benutzergruppe', "").', '
			         .'user_access=56';
			                                                                                                                     
                     $db = new DB_Admin;
                     $db->query($insert_surveyresult);
                     $db->close();
                     exit(0);
			
			case 'orte':
			case 'themen':
			case 'abschluesse':
			case 'sitemap-landingpages.xml':
			case 'sitemap-landingpages.xml.gz':
			    return createWisyObject('WISY_LANDINGPAGE_RENDERER_CLASS', $this, array('type'=>$wisyRequestedFile));
			
			// deprecated URLs
			case 'kurse.php':
			case 'anbieter.php':
			case 'glossar.php':
				$firstLetter = substr($wisyRequestedFile, 0, 1);
				return $firstLetter . $_GET['id'];
		}
		
		return false;
	}

	function mysql_escape_mimic($inp) {
	    if(is_array($inp))
	        return array_map(__METHOD__, $inp);
	        
	        if(!empty($inp) && is_string($inp)) {
	            return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp);
	        }
	        
	        return $inp;
	} 
	
	function main()
	{
		// authentication required?
		if( $this->iniRead('auth.use', 0) == 1 )
		{
			$auth =& createWisyObject('WISY_AUTH_CLASS', $this);
			$auth->check();
		}
		
		/* Don't allow search request parameters to be set, if search isn't valid for page type -> don't let search engines and hackers consume unecessary ressources ! */
		global $wisyRequestedFile;
		$valid_searchrequests = array('rss', 'search', 'advanced', 'filter', 'tree', 'geocode', 'autosuggest', 'autosuggestplzort', 'opensearch', 'kurse.php', 'anbieter.php', 'glossar.php');
		if(
		    (isset($_GET['q']) || isset($_GET['qs']) || isset($_GET['qf']) || isset($_GET['qsrc']) || isset($_GET['offset']))
		    && !in_array($wisyRequestedFile, $valid_searchrequests)
		    && stripos($wisyRequestedFile, 'k') !== 0 && strpos($wisyRequestedFile, 'a') !== 0 && strpos($wisyRequestedFile, 'g') !== 0 ){
		        $this->error404("Anfrage nicht erlaubt: q, qs, wf, qsrc, qtrigger, offset f&uuml;r  ".trim($wisyRequestedFile, '.php'));
		} elseif( isset($_GET['offset']) && !isset($_GET['q']) && !isset($_GET['qs']) && !isset($_GET['qf']) ) { // offset without query
		    $this->error404("Anfrage nicht erlaubt: offset ohne q, qs f&uuml;r  ".trim($wisyRequestedFile, '.php'));
		} elseif( (!isset($_GET['q']) && !isset($_GET['qs']) && !isset($_GET['qf']) && !isset($_GET['qsrc']) && !isset($_GET['offset'])) && stripos($wisyRequestedFile, 'search') === 0) {
		    $this->error404("Diese Suchanfrage ist nicht zugelassen.");
		}
		
		foreach($_GET AS $get) {
		    if(!is_array($get) && strpos($get, 'volltext') !== FALSE && $_GET['qtrigger'] != 'h' && $_GET['force'] != 1) // qtrigger = h -> human search (click/return), force = link from unsuccessful searches
		        $this->error404("Volltextanfrage per direkter Verlinkung aus Ressourcengr&uuml;nden nicht erlaubt.<br><br>Bitte geben Sie Ihre Volltextanfrage unbedingt manuell in das Suchfeld ein - bzw. klicken Sie noch mal selbst auf 'Kurse finden' !<br><br>");
		}

		// see what to do
		$renderer =& $this->getRenderer();
		if( is_object($renderer) )
		{
			// start the edit session, if needed
			if( isset($_COOKIE[ $this->editCookieName ]) )
			{
				$this->startEditSession();
			}
			
			// for "normal pages" as kurse, anbieter, search etc. switch back to non-secure
			if( $renderer->unsecureOnly && $_SERVER['HTTPS']=='on' && !$this->iniRead('portal.https', '') )
			{
				$redirect = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"];
				fwd301($redirect);
			}
			
			// render
			$renderer->render();
			$this->cacheFlush();
		}
		else if( $renderer != '' )
		{
			fwd301($renderer);
		}
		else
		{
			$this->error404();
		}
	} // end: main
	
	// replace words in str from array
	// explode str, array_diff -> implode would work as well
	function replaceWords($filterTerms, $strToBeFiltered) {
	    $strToBeFiltered = trim($strToBeFiltered);
	    foreach($filterTerms AS $filterTerm) {
	        $strToBeFiltered = str_ireplace(' '.$filterTerm.' ', ' ', $strToBeFiltered); // between words
	        $strToBeFiltered = preg_replace("/^".preg_quote($filterTerm, '/')." /i", ' ', $strToBeFiltered); // at beginning of search string
	        $strToBeFiltered = trim(preg_replace("/ ".preg_quote($filterTerm, '/')."[\.!]{0,1}$/i", ' ', $strToBeFiltered)); // at end of search string
	        $strToBeFiltered = trim(preg_replace("/volltext:".preg_quote($filterTerm, '/')."[\.!]{0,1}$/i", ' ', $strToBeFiltered)); // at end of search string
	        $strToBeFiltered = trim(preg_replace("/^".preg_quote($filterTerm, '/')."[\.!]{0,1}$/i", ' ', $strToBeFiltered)); // needed b/c leading words may have now been removd
	    }
	    return trim($strToBeFiltered);
	}
	
};




