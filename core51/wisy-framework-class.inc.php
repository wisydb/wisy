<?php if( !defined('IN_WISY') ) die('!IN_WISY');

/******************************************************************************
 WISY 5.0
 ******************************************************************************
 Globale Seite, hiervon existiert genau 1 Objekt
 ******************************************************************************/

// necessary for G_BLOB_CLASS / logo output
require_once('admin/classes.inc.php'); 

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
	
	var $filterTokens = array(
		'anbieter',
		'zielgruppe',
		'thema',
		'foerderung',
		'qualitaetszertifikat',
		'unterrichtsart',
		'tageszeit');

	function __construct($baseObject, $addParam)
	{
		// constructor
		$this->includeVersion = '?iv=201'; // change the number on larger changes in included CSS and/or JS files.  May be empty.
		
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
		if( strpos($temp, 'bunummer,'			)!==false ) $GLOBALS['wisyPortalSpalten'] += 128;
		if( strpos($temp, 'entfernung,'			)!==false ) $GLOBALS['wisyPortalSpalten'] += 256;
        
		// Spalten für Durchführungs-Detailansicht initialisieren falls gesetzt
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
    		if( strpos($temp, 'bunummer,'			)!==false ) $GLOBALS['wisyPortalSpaltenDurchf'] += 128;
    		if( strpos($temp, 'entfernung,'			)!==false ) $GLOBALS['wisyPortalSpaltenDurchf'] += 256;
        }
				
		$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this);
				
		// Simple Search
		$this->simplified = $this->iniRead('search.simplified', 0);
		
		if($this->simplified)
		{
			$this->filterer =& createWisyObject('WISY_FILTER_CLASS', $this);
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

	function iniRead($key, $default='')
	{
		global $wisyPortalEinstellungen;
		$value = $default;
		if( isset( $wisyPortalEinstellungen[ $key ] ) )
		{
			$value = utf8_encode($wisyPortalEinstellungen[ $key ]);
		}
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
		while( list($regKey, $regValue) = each($values) )
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
		$db->free();
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
					$description_parsed = utf8_encode(trim($this->iniRead('meta.description_default', "")));
					break;
                default:
                    $description_parsed = utf8_encode(trim($this->iniRead('meta.description_default', "")));
        }
		
		if($skip_contentdescription) {
			;
		} else {		
			$ret .= ($description_parsed == "") ? "\n".'<meta name="description" content="'.utf8_encode(trim($this->iniRead('meta.description_default', ""))).'">'."\n" : "\n".'<meta name="description" content="'.$description_parsed.'">'."\n";
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
		
		$logo = $db->f8('logo');
				
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
					$beschreibung = utf8_encode(trim($this->iniRead('meta.description_default', "")));
					$ret .= '<meta property="og:type" content="article">'."\n";
					$canonicalurl = ""; // no canonical URL in > 5.0
					break;
                default:
					$beschreibung = utf8_encode(trim($this->iniRead('meta.description_default', "")));
                    $ret .= '<meta property="og:type" content="article">'."\n";
                    $canonicalurl = ""; // no canonical URL in > 5.0
        }
		
		if($beschreibung == "")
			$beschreibung = utf8_encode(trim($this->iniRead('meta.description_default', "")));
		
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
			
		// GGf. in Bitly änder, weil Query String ignoriert wird
		$url = $protocol."://".$_SERVER['SERVER_NAME'].'/'.$canonicalurl;
		$url_fb = $url; // sonst muss canonical url übereinstimmen, um nicht ignoriert zu werden!
		// $url_fb .= (strpos($url, '?') === FALSE) ? $url.'?pk_campaign=Facebook' : $url.'&pk_campaign=Facebook';
		$ret .= '<meta property="og:url" content="'.$url_fb.'">'."\n";
								
		if($beschreibung != "") {
		  $beschreibung = $this->shorten_description($beschreibung, 300);
		  $beschreibung = (strlen($beschreibung) > 297) ? $beschreibung."..." : $beschreibung;
		  $ret .= '<meta property="og:description" content="'.$beschreibung.'">'."\n";
		} else {
		  $ret .= '<meta property="og:description" content="...">'."\n";
		}
			
        $ret .= '<meta property="og:locale" content="de_DE">'."\n";
		$ret .= '<meta property="fb:app_id" content="100940970255996">'."\n";
				
		// Twitter
		$ret .= '<meta name="twitter:card" content="summary">'."\n";
		$ret .= '<meta name="twitter:site" content="@weiterbrlp">'."\n"; // $ret .= '<meta name="twitter:creator" content="weiterbrlp">'."\n";
			
		// GGf. in Bitly änder, weil Query String ignoriert wird
		$url_twitter = $url; // sonst muss canonical url übereinstimmen, um nicht ignoriert zu werden!
		// $url_twitter .= (strpos($url, '?') === FALSE) ? $url.'?pk_campaign=Twitter' : $url.'&pk_campaign=Twitter';
		$ret .= '<meta name="twitter:url" content="'.$url_twitter.'">'."\n";
			
		$ret .= '<meta name="twitter:title" content="'.$this->getTitleString($pageTitleNoHtml, $ort, $anbieter_name).'">'."\n";
			
		if($beschreibung != "") {
		  $beschreibung = $this->shorten_description($beschreibung, 200);
		  $beschreibung = (strlen($beschreibung) > 197) ? $beschreibung."..." : $beschreibung;
		}
			
		$ret .= '<meta name="twitter:description" content="'.$beschreibung.'">'."\n";
			
		$logo = ""; $logo_src = ""; 
		$logo = $this->getAnbieterLogo($anbieter_ID, false, true);	// quadrat = false, 4:3 = true
   
        if($logo != "") {
		  if(!is_object($logo)) {
		      $logo_src = $logo;
		      // Default Logo Symbol
			     $ret .= '<meta name="twitter:image" content="'.utf8_encode($this->pUrl($logo_src)).'">'."\n";
			     $ret .= '<meta name="twitter:image:width" content="120">'."\n";
			     $ret .= '<meta name="twitter:image:height" content="90">'."\n";
		  } else {
			     $logo_src = $this->purl("https://".$_SERVER['SERVER_NAME']."/admin/media.php/logo/anbieter/".$anbieter_ID."/".$logo->name);
				 $ret .= '<meta name="twitter:image" content="'.utf8_encode($this->pUrl($logo_src)).'">'."\n";
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

	function error404()
	{
		/* show an error page. For simple errors, eg. a bad or expired ID, we use an error message in the layout of the portal.
		However, these messages only work in the root directory (remember, we use relative paths, so whn requesting /abc/def/ghi.html all images, css etc. won't work).
		So, for errors outside the root directory, we use the global error404() function declared in /index.php */
		$uri = $_SERVER['REQUEST_URI']; // 
		if( substr_count($uri, '/') == 1 )
		{
			global $wisyCore;
			header("HTTP/1.1 404 Not Found");
			header('Content-Type: text/html; charset=utf-8');

			$title = 'Fehler 404 - Seite nicht gefunden';

			echo $this->getPrologue(array('title'=>$title, 'bodyClass'=>'wisyp_error'));
			echo $this->getSearchField();

			echo '
						<div class="wisy_topnote">
							<p><b>Fehler 404 - Seite nicht gefunden</b></p>
							<p>Entschuldigung, aber die von Ihnen gewünschte Seite konnte leider nicht gefunden werden. Sie können jedoch ...
							<ul>
								<li><a href="//'.$_SERVER['HTTP_HOST'].'">Die Startseite von '.$_SERVER['HTTP_HOST'].' aufrufen ...</a></li>
								<li><a href="javascript:history.back();">Zur&uuml;ck zur zuletzt besuchten Seite wechseln ...</a></li>
							</ul>
						</div>
						<p>
							(die angeforderte Seite war <i>'.htmlspecialchars($uri).'</i> in <i>/'.htmlspecialchars($wisyCore).'</i> auf <i>' .$_SERVER['HTTP_HOST']. '</i>)
						</p>
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

	function getUrl($page, $param = 0)
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
		while( list($key, $value) = each($param) )
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
		
		return $ret;
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
		    return utf8_encode($wisyPortalName);
		}
		else if( $placeholder == '__KURZNAME__' )
		{
		    return utf8_encode($wisyPortalKurzname);
		}
		else if( $placeholder == '__ANZAHL_KURSE__' || $placeholder == '__ANZAHL_KURSE_G__' )
		{
			return intval($this->cacheRead('stats.anzahl_kurse'));
		}
		else if( $placeholder == '__ANZAHL_DURCHFUEHRUNGEN__' )
		{
			return intval($this->cacheRead('stats.anzahl_durchfuehrungen'));
		}
		else if( $placeholder == '__ANZAHL_ANBIETER__' || $placeholder == '__ANZAHL_ANBIETER_G__' )
		{
			return intval($this->cacheRead('stats.anzahl_anbieter'));
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
			return '<div id="nav-link"><span>Menü öffnen</span></div>';
		}
		
		return "Unbekannt: $placeholder";
	}

	function replacePlaceholders($snippet)
	{
		return preg_replace_callback('/__[A-Z0-9_]+?__/', array($this, 'replacePlaceholders_Callback'), $snippet);
	}	

	function cleanClassname($input)
	{
		$output = strtolower($input);
		$output = strtr($output, array('ä'=>'ae', 'ö'=>'oe', 'ü'=>'ue', 'ß'=>'ss'));
		$output = preg_replace('/[^a-z,]/', '', $output);
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
				// nicht "0" zurückgeben, da es kurse gibt, die "0" als anbieter haben;
				// ein Vergleich mit kursId==getEditAnbieterId() würde dann eine unerwartete Übereinstimmung bringen ...
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

	function getSeals(&$db, $vars)
	{
		// get all seals
		$seals = array();
		$db->query("SELECT a.attr_id AS sealId, s.glossar AS glossarId FROM anbieter_stichwort a, stichwoerter s WHERE a.primary_id=" . intval($vars['anbieterId']) . " AND a.attr_id=s.id AND s.eigenschaften=" .DEF_STICHWORTTYP_QZERTIFIKAT. " ORDER BY a.structure_pos;");
		while( $db->next_record() )
			$seals[] = array($db->f8('sealId'), $db->f8('glossarId'));
	
		// no seals? -> done.
		if( sizeof($seals) == 0 )
			return '';
	
		// get common seal information
		$seit = intval(substr($vars['seit'], 0, 4));
		if( $seit == 0 )
		{
			$db->query("SELECT pruefsiegel_seit FROM anbieter WHERE id=" . intval($vars['anbieterId']));
			$db->next_record();
			$seit = intval(substr($db->f8('pruefsiegel_seit'), 0, 4));
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
			if( !($glossarId=$db->f8('glossar')) ) {
				$db->query("SELECT id FROM glossar WHERE begriff='" .addslashes($db->f8($field)). "'");
				if( $db->next_record() ) {
					$glossarId = $db->f8('id');
				}
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

	#richtext
	function writeStichwoerter($db, $table, $stichwoerter, $richtext = false)
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
			
			for( $s = 0; $s < sizeof($stichwoerter); $s++ )
			{
				$glossarLink = '';
				$glossarId = $this->glossarDb($db, 'stichwoerter', $stichwoerter[$s]['id']);
				if( $glossarId ) {
					$glossarLink = ' <a href="' . $this->getHelpUrl($glossarId) . '" class="wisy_help" title="Ratgeber">i</a>';
				}
				
				if( ($stichwoerter[$s]['eigenschaften']==0 && intval($codes_array[$c])==0 && $glossarLink)
				 || ($stichwoerter[$s]['eigenschaften'] & intval($codes_array[$c])) )
				{
				    // #richtext
				    if(stripos($codes_array[$c+1], "Qualit") !== FALSE) {
				        $award_sw = ($richtext) ? preg_replace("/.Anbietermerkmal./i", "", $stichwoerter[$s]['stichwort']) : $stichwoerter[$s]['stichwort'];
				        $award1 = ($richtext) ? '<span itemprop="award" content="'.$award_sw.'">' : '';
				        $award2 = ($richtext) ? '</span>' : '';
				    }
				    
					if( !$anythingOfThisCode ) {
						$ret .= '<dt class="wisy_stichwtyp'.$stichwoerter[$s]['eigenschaften'].'">' . $codes_array[$c+1] . '</dt><dd>';
					}
					else {
						$ret .= '<br />';
					}
					
					$writeAend = false;
					/* 
					// lt. Liste "WISY-Baustellen" vom 5.9.2007, Punkt 8. in "Kursdetails", sollen hier kein Link angezeigt werden.
					// Zitat: "Anzeige der Stichworte ohne Link einblenden" (bp)
					$ret .= '<a title="alle Kurse mit diesem Stichwort anzeigen" href="' .wisy_param('index.php', array('sst'=>"\"{$stichwoerter[$s]['stichwort']}\"", 'skipdefaults'=>1, 'snew'=>2)). '">';
					$writeAend = true;
					*/
					
					// #richtext
					$ret .= $award1.$stichwoerter[$s]['stichwort'].$award2;
					
					if( $writeAend ) {
						$ret .= '</a>';
					}
					
					if( $stichwoerter[$s]['zusatzinfo'] != '' ) {
						$ret .= ' <span class="ac_tag_type">(' . htmlspecialchars($stichwoerter[$s]['zusatzinfo']) . ')</span>';
					}

					$ret .= $glossarLink;
					
					$anythingOfThisCode	= 1;
				}
			}
			
			if( $anythingOfThisCode ) {
				$ret .= '</dd>';
			}
		}
		
		return utf8_encode($ret); // UTF-8 encode because the source file (admin/config/codes.inc.php) is still ISO-encoded
	}
	
	function encode_windows_chars($input) {
		return strtr($input, $this->encode_windows_chars_map);
	}

	function getVollstaendigkeitMsg(&$db, $recordId, $scope = '')
	{
		// Einstellungen der zug. Gruppe und Kursvollstaendigkeit laden
		// die Einstellungen können etwa wie folgt aussehen:
		/*
		quality.portal.warn.percent= 80
		quality.portal.warn.msg    = Informationen lückenhaft (nur __PERCENT__% Vollständigkeit)
		quality.portal.bad.percent = 50
		quality.portal.bad.msg     = Informationen unzureichend (nur __PERCENT__% Vollständigkeit)
		quality.edit.warn.percent  = 80
		quality.edit.warn.msg      = Informationen lückenhaft (nur __PERCENT__% Vollständigkeit)
		quality.edit.bad.percent   = 50
		quality.edit.bad.msg       = Informationen unzureichend (nur __PERCENT__% Vollständigkeit)
		quality.edit.bad.banner    = Informationen unzureichend (nur __PERCENT__% Vollständigkeit) - gelistet aus Gründen der Marktübersicht
		*/
	
		
		$sql = "SELECT settings s, vollstaendigkeit v FROM user_grp g, kurse k
				WHERE k.user_grp=g.id AND k.id=$recordId";
		$db->query($sql); if( !$db->next_record() ) return array();
	
		$settings			= explodeSettings($db->f8('s'));
		$vollstaendigkeit	= intval($db->f8('v'));  if( $vollstaendigkeit <= 0 ) return;
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
		 && !$this->editSessionStarted /*keine Feedback-Funktion für angemeldete Anbieter - die Anbieter sind die Adressaten, nicht die Absender*/ )
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
	    $fullTitleNoHtml .= utf8_encode($wisyPortalKurzname); // = default for home page
	    
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
	
		if( $this->iniRead('rsslink', 1) )
		{
			global $wisyPortalKurzname;
			$q = rtrim($this->simplified ? $this->Q : $this->getParam('q', ''), ', ');
			$title = $wisyPortalKurzname . ' - ' . ($q==''? 'aktuelle Kurse' : $q);
			$ret .= '<link rel="alternate" type="application/rss+xml" title="'.htmlspecialchars($title).'" href="' .$this->getRSSFile(). '" />' . "\n";
		}
		
		return $ret;
	}

	function getRSSLink()
	{
		$ret = '';
	
		if( $this->iniRead('rsslink', 1) )
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
		
		$ret = array();

		// core styles
		$ret[] = 'core.css' . $this->includeVersion;
		
		// core responsive styles
		$ret[] = 'core.responsive.css' . $this->includeVersion;
		$ret[] = 'core51/lib/jquery/jquery-ui-1.12.1.custom.min.css' . $this->includeVersion;
		$ret[] = 'core51/lib/zebra-datepicker/zebra_datepicker.min.css' . $this->includeVersion;
		
		if($this->iniRead('cookiebanner', '') == 1) {
			$ret[] = 'core51/lib/cookieconsent/cookieconsent.min.css';
		}
		
		// the portal may overwrite everything ...
		if( $wisyPortalCSS )
		{
			$ret[] = 'portal.css'. $this->includeVersion;
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
		for( $i = 0; $i < sizeof($css); $i++ )
		{	
			$ret .= '<link rel="stylesheet" type="text/css" href="'.$css[$i].'" />' . "\n";
		}
		
		return $ret;
	}
	
	function getJSFiles()
	{
		// return all JavaScript files as an array
		$ret = array();
		
		$ret[] = 'core51/lib/jquery/jquery-1.12.4.min.js';
		$ret[] = 'core51/lib/jquery/jquery-ui-1.12.1.custom.min.js';
		$ret[] = 'core51/lib/zebra-datepicker/zebra_datepicker.min.js';
		
		if($this->simplified)
		{
			$ret[] = 'jquery.wisy.simplified.js' . $this->includeVersion;
		}
		else
		{
			$ret[] = 'jquery.wisy.js' . $this->includeVersion;
		}
		
		if($this->iniRead('cookiebanner', '') == 1) {
			$ret[] = 'core51/lib/cookieconsent/cookieconsent.min.js';
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
	
	function getJSHeadTags()
	{
		// JavaScript tags to include to the header (if any)
		$ret = '';
		
		$js = $this->getJSFiles();
		for( $i = 0; $i < sizeof($js); $i++ )
		{	
			$ret .= '<script type="text/javascript" src="'.$js[$i].'" charset="utf-8"></script>' . "\n";
		}
		
		// Cookie Banner settings
		if($this->iniRead('cookiebanner', '') == 1) {
			
			$ret .= "<script type=\"text/javascript\">\n";
			$ret .= "window.cookiebanner = {};\n";
			$ret .= "window.cookiebanner.optoutCookies = \"{$this->iniRead('cookiebanner.cookies.optout', '')},fav,fav_init_hint\";\n";
			$ret .= "window.cookiebanner.optedOut = false;\n";
			$ret .= "window.cookiebanner.favOptoutMessage = \"{$this->iniRead('cookiebanner.fav.optouthinweis', 'Ihr Favorit konnte auf diesem Computer nicht gespeichert gewerden da Sie die Speicherung von Cookies abgelehnt haben. Sie können Ihre Cookie-Einstellungen in den Datenschutzhinweisen anpassen.')}\";\n";
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
			$cookieOptions['content']['message'] = $this->iniRead('cookiebanner.hinweis.text', 'Wir verwenden Cookies, um Ihnen eine Merkliste sowie eine Seitenübersetzung anzubieten und um Kursanbietern die Pflege ihrer Kurse zu ermöglichen. Indem Sie unsere Webseite nutzen, erklären Sie sich mit der Verwendung der Cookies einverstanden. Weitere Details finden Sie in unserer Datenschutzerklärung.');
			$cookieOptions['content']['allow'] = $this->iniRead('cookiebanner.erlauben.text', 'Akzeptieren');
			$cookieOptions['content']['deny'] = $this->iniRead('cookiebanner.ablehnen.text', 'Ablehnen');
			$cookieOptions['content']['link'] = $this->iniRead('cookiebanner.datenschutz.text', 'Mehr erfahren');
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
					},
					onStatusChange: function(status) {
						var didConsent = this.hasConsented();
						if(!didConsent) {
							window.cookiebanner.optedOut = true;
							updateCookieSettings();
						}
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
			
			$ret .= ')});</script>'."\n";
		}
		
		return $ret;
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
		
		// add nojs class
		$ret .= ' nojs';
		
		// done
		return $ret;
	}
	
	function getPrologue($param = 0)
	{
		if( !is_array($param) ) $param = array();
		
		// prepare the HTML-Page
		$bodyStart = utf8_encode($GLOBALS['wisyPortalBodyStart']);
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
						. '<script type="text/javascript">document.body.className = document.body.className.replace("nojs","yesjs");</script>' . "\n"
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
		$bodyStart = str_replace('__HEADTAGS__', $this->getTitleTags($param['title'], $param['ort'], $param['anbieter_name']) . $this->getFaviconTags() . $this->getOpensearchTags() . $this->getRSSTags() . $this->getCSSTags() . $this->getCanonicalTag($param['canonical']) . $this->getMobileAlternateTag($param['canonical']) . $this->getJSHeadTags() . $this->getMetaDescription($param['title'], $param['beschreibung']) . $this->getHreflangTags() . $this->getSocialMediaTags($param['title'], $param['ort'], $param['anbieter_name'], $param['anbieter_id'], $param['beschreibung'], $param['canonical']), $bodyStart);
		$bodyStart = str_replace('__BODYATTR__', ' ' . $this->getJSOnload(). ' class="' . $this->getBodyClasses($param['bodyClass']) . '" x-ms-format-detection="none"', $bodyStart);
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
		
		// iwwb specials
		if( $this->iniRead('iwwbumfrage', 'unset')!='unset' && $_SERVER['HTTPS']!='on')
		{
			require_once('files/iwwbumfrage.php');
		}
		
		$ret .= '</body>' . "\n";
		$ret .= '</html>' . "\n";
		
		return $ret;
	}
	
	function getAnalytics() {
		$ret = "\n";
		
		$uacct = $this->iniRead('analytics.uacct', '');
		if( $uacct != '' )
		{
			$ret .= '
				<script type="text/javascript">
				var optedOut = document.cookie.indexOf("cookieconsent_status=deny") > -1;
				if (!optedOut) {					
					(function(i,s,o,g,r,a,m){i["GoogleAnalyticsObject"]=r;i[r]=i[r]||function(){ 
						(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o), 
						m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m) 
					})(window,document,"script","https://www.google-analytics.com/analytics.js","ga"); 
					ga("create", "' . $uacct . '", "none"); 
					ga("set", "anonymizeIp", true); 
					ga("send", "pageview"); 
				}
				</script>';
		}
		
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
				<!-- analytics.piwik -->
				<script type=\"text/javascript\">
                var optedOut = document.cookie.indexOf(\"cookieconsent_status=deny\") > -1;
				if (!optedOut) {
					var _paq = _paq || [];
					_paq.push(['trackPageView']);
					_paq.push(['enableLinkTracking']);
					(function() {
						var u=\"//".$piwik_site."/\";
						_paq.push(['setTrackerUrl', u+'piwik.php']);
						_paq.push(['setSiteId', ".$piwik_id."]);
						var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
						g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
					})();
				}
				</script>
				<noscript><p><img src=\"//".$piwik_site."/piwik.php?idsite=".$piwik_id."\" style=\"border:0;\" alt=\"\" /></p></noscript>
				<!-- /analytics.piwik -->";
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
			for( $i = 0; $i < sizeof($tokens['cond']); $i++ ) {
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
		$DEFAULT_RIGHT_HTML		= '| <a href="javascript:window.print();">Drucken</a>';
		$DEFAULT_BOTTOM_HINT	= 'bitte <strong>Suchwörter</strong> eingeben - z.B. Englisch, VHS, Bildungsurlaub, ...';
		
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
		    $queryinput = ($richtext) ? 'itemprop="query-input" placeholder="'.$q.'"': '';
		    $q = ""; // sonst ändert sich mit jedr Seite der DefaultValue
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
		}
		
		echo "\n" . '<div id="wisy_searcharea">' . "\n";
			echo '<div class="inner">' . "\n";
				echo '<form action="search" method="get" '.$searchAction.'>' . "\n" . $target; // #richtext
					echo '<div class="formrow wisyr_searchinput">';
						echo '<label for="q">' . $this->iniRead('searcharea.placeholder', $DEFAULT_PLACEHOLDER) . '</label>';
						if($this->simplified)
						{
							echo '<input type="text" id="wisy_searchinput" class="' . $autocomplete_class . '" name="qs" value="' .$this->QS. '" placeholder="' . $searchinput_placeholder . '" />' . "\n";
							echo '<input type="hidden" id="wisy_searchinput_q" name="q" value="' . $this->Q . '" />' . "\n";
							echo '<input type="hidden" id="wisy_searchinput_qf" name="qf" value="' . $this->QF . '" />' . "\n";
							if( isset($tokens['show']) && $tokens['show'] == 'anbieter' ) {
								echo '<input type="hidden" name="filter_zeige" value="Anbieter" />';
							}
							$active_filters = $this->filterer->getActiveFilters();
							$hintwithfilters = $this->iniRead('searcharea.hintwithfilters', 0);
							if($active_filters == '' || $hintwithfilters) {
							    $hint = ($hintwithfilters && $active_filters) ? $hintwithfilters : $this->replacePlaceholders($this->iniRead('searcharea.hint', $DEFAULT_BOTTOM_HINT));
							    echo '<div class="wisy_searchhints">' .  $hint . '</div>' . "\n";
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
	            
	            // DF1 start date, to harmonize SERP entry date with DF
	            // if($websiteurl)
	            // $metatags .= "<meta name='datePublished' itemprop='datePublished' content='".$YDP."-".$mDP."-".dDP."."'>"."\n";
	            
	            return $metatags;
	}
	
	function getPageType() {
		
		// Der Konstruktor ist jeweils sehr leichtgewichtig,
		// darum können ruhig neue Objekte erzeugt werden.
		// Andernfalls müsste man hier den getRenderercode mehr oder weniger duplizieren...
		$result = $this->getRenderer();
		
		if(!is_object($result))
			return false;

		if($_SERVER['REQUEST_URI'] == "/") {
			return 'startseite';
		}
		
		// Dieser sollte beim Überschreiben von Kernfunktionen immer gleich sein:
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

		switch( $wisyRequestedFile )
		{
			// homepage
			// (in WISY 5.0 gibt es keine Datei "index.php", diese wird vom Framework aber als Synonym für "Homepage" verwendet)
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
				    // Wenn sinnvolle Glossar-ID: Ist max. 20-stellige Zahl, die nicht mit 0 anfängt
				    if(preg_match("/^[1-9][0-9]{1,20}$/", $gid))
				    {
				        $_GET['id'] = trim($gid);	// unschön, aber hier nicht sinnvoll anders möglich?.
				        return createWisyObject('WISY_GLOSSAR_RENDERER_CLASS', $this);
				    }
				}
				break;

			// misc
			case 'sync':
				return createWisyObject('WISY_SYNC_RENDERER_CLASS', $this);
			
			case 'autosuggest':
				return createWisyObject('WISY_AUTOSUGGEST_RENDERER_CLASS', $this);

			case 'autosuggestplzort':
				return createWisyObject('WISY_AUTOSUGGESTPLZORT_RENDERER_CLASS', $this);
				
			case 'opensearch':
				return createWisyObject('WISY_OPENSEARCH_RENDERER_CLASS', $this);

			case 'rss':
				return createWisyObject('WISY_RSS_RENDERER_CLASS', $this, array('q'=>$this->simplified ? $this->Q : $this->getParam('q', '')));

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
			
			// deprecated URLs
			case 'kurse.php':
			case 'anbieter.php':
			case 'glossar.php':
				$firstLetter = substr($wisyRequestedFile, 0, 1);
				return $firstLetter . $_GET['id'];
		}
		
		return false;
	}

	function main()
	{
		// authentication required?
		if( $this->iniRead('auth.use', 0) == 1 )
		{
			$auth =& createWisyObject('WISY_AUTH_CLASS', $this);
			$auth->check();
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
	}
	
};




