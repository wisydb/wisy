<?php if( !defined('IN_WISY') ) die('!IN_WISY');

/******************************************************************************
 WISY 2.0
 ******************************************************************************
 Globale Seite, hiervon existiert genau 1 Objekt
 ******************************************************************************/



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

	function __construct($baseObject, $addParam)
	{
		// constructor
		$this->includeVersion = '?iv=115'; // change the number on larger changes in included CSS and/or JS files.  May be empty.
		
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
			$value = $wisyPortalEinstellungen[ $key ];
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

			$title = 'Fehler 404 - Seite nicht gefunden';

			echo $this->getPrologue(array('title'=>$title, 'bodyClass'=>'wisyp_error'));
			echo $this->getSearchField();

			echo '
						<div class="wisy_topnote">
							<p><b>Fehler 404 - Seite nicht gefunden</b></p>
							<p>Entschuldigung, aber die von Ihnen gewünschte Seite konnte leider nicht gefunden werden. Sie können jedoch ...
							<ul>
								<li><a href="http://'.$_SERVER['HTTP_HOST'].'">Die Startseite von '.$_SERVER['HTTP_HOST'].' aufrufen ...</a></li>
								<li><a href="javascript:history.back();">Zur&uuml;ck zur zuletzt besuchten Seite wechseln ...</a></li>
							</ul>
						</div>
						<p>
							(die angeforderte Seite war <i>'.isohtmlspecialchars($uri).'</i> in <i>/'.isohtmlspecialchars($wisyCore).'</i> auf <i>' .$_SERVER['HTTP_HOST']. '</i>)
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
		$str = strtr($str,	'\'\\!"§$%&/(){}[]=?+*~#,;.:-_<>|@€©®£¥  ',
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
				'q'		=>	$this->getParam('q', ''),
			));
	}

	function replacePlaceholders_Callback($matches)
	{
		global $wisyPortalName;
		global $wisyPortalKurzname;

		$placeholder = $matches[0];
		if( $placeholder == '__NAME__' )
		{
			return $wisyPortalName;
		}
		else if( $placeholder == '__KURZNAME__' )
		{
			return $wisyPortalKurzname;
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
		else if( $placeholder == '__ANZAHL_ABSCHLUESSE__' )
		{
			return intval($this->cacheRead('stats.anzahl_abschluesse')); // Anzahl von Kursen, die zu einem Abschluss fuehren
		}
		else if( $placeholder == '__ANZAHL_ZERTIFIKATE__' )
		{
			return intval($this->cacheRead('stats.anzahl_zertifikate')); // Anzahl verschiedener Abschluesse
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
			return isohtmlspecialchars( rtrim( $this->getParam('q', ''), ', ') );
		}
		else if( $placeholder == '__Q_URLENCODE__' )
		{
			return urlencode( rtrim( $this->getParam('q', ''), ', ') );
		}
		else if( $placeholder == '__CONTENT__' )
		{
			return '__CONTENT__'; // just leave  this as it is, __CONTENT__ is handled separately
		}
		
		return "Unbekannt: $placeholder";
	}

	function replacePlaceholders($snippet)
	{
		return preg_replace_callback('/__[A-Z0-9_]+?__/', array($this, 'replacePlaceholders_Callback'), $snippet);
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
						$ret .= "<img ".html3('align="right"')." src=\"$img\" ".html3('border="0"')." alt=\"Pr&uuml;siegel\" title=\"$title\" class=\"seal_small\"/>";
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
					$ret .= "<img src=\"$img\" ".html3('border="0"')." alt=\"Pr&uuml;siegel\" title=\"$title\" class=\"seal\" />";
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
				$db->query("SELECT id FROM glossar WHERE begriff='" .addslashes($db->fs($field)). "'");
				if( $db->next_record() ) {
					$glossarId = $db->f('id');
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

	function writeStichwoerter($db, $table, $stichwoerter)
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
				if( !$anythingOfThisCode ) {
						$ret .= '<tr class="wisy_stichwtyp'.$stichwoerter[$s]['eigenschaften'].'"><td'.html3(' valign="top"').'><span class="text_keyword">' . $codes_array[$c+1]
						. '<span class="dp">:</span></span>&nbsp;</td><td'.html3(' valign="top"').'>';
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
					
					$ret .= $stichwoerter[$s]['stichwort'];
					
					if( $writeAend ) {
						$ret .= '</a>';
					}
					
					if( $stichwoerter[$s]['zusatzinfo'] != '' ) {
						$ret .= ' <span class="ac_tag_type">(' . isohtmlspecialchars($stichwoerter[$s]['zusatzinfo']) . ')</span>';
					}

					$ret .= $glossarLink;
					
					$anythingOfThisCode	= 1;
				}
			}
			
			if( $anythingOfThisCode ) {
				$ret .= '</td></tr>';
			}
		}
		
		return $ret;
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
	
		$settings			= explodeSettings($db->fs('s'));
		$vollstaendigkeit	= intval($db->f('v'));  if( $vollstaendigkeit <= 0 ) return;
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
	
	function getTitleString($pageTitleNoHtml)
	{
		// get the title as a no-html-string
		global $wisyPortalKurzname;
		$fullTitleNoHtml  = $pageTitleNoHtml;
		$fullTitleNoHtml .= $fullTitleNoHtml? ' - ' : '';
		$fullTitleNoHtml .= $wisyPortalKurzname;
		return $fullTitleNoHtml;
	}
	
	function getTitleTags($pageTitleNoHtml)
	{
		// get the <title> tag
		return "<title>" .isohtmlspecialchars($this->getTitleString($pageTitleNoHtml)). "</title>\n";
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
			$ret .= '<link rel="search" type="application/opensearchdescription+xml" href="' . $opensearchFile . '" title="' .isohtmlspecialchars($wisyPortalKurzname). '" />' . "\n";
		}
		
		return $ret;
	}

	function getRSSFile()
	{
		// get the main RSS file
		$q = rtrim($this->getParam('q', ''), ', ');
		return 'rss?q=' . urlencode($q);
	}

	function getRSSTags()
	{
		// get the RSS tag (if there is no query, "alle Kurse" is returned)
		$ret = '';
	
		if( $this->iniRead('rsslink', 1) )
		{
			global $wisyPortalKurzname;
			$q = rtrim($this->getParam('q', ''), ', ');
			$title = $wisyPortalKurzname . ' - ' . ($q==''? 'aktuelle Kurse' : $q);
			$ret .= '<link rel="alternate" type="application/rss+xml" title="'.isohtmlspecialchars($title).'" href="' .$this->getRSSFile(). '" />' . "\n";
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

		// core 2.0 styles
		$ret[] = 'core.css' . $this->includeVersion;
		
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
		
		if($this->iniRead('search.suggest.v2') == 1)
		{
			$ret[] = '/admin/lib/jquery/js/jquery-1.10.2.min.js';
			$ret[] = '/admin/lib/jquery/js/jquery-ui-1.10.4.custom.min.js';
		}
		else
		{
			$ret[] = 'jquery-1.4.3.min.js';
			$ret[] = 'jquery.autocomplete.min.js';
		}
		$ret[] = 'jquery.wisy.js' . $this->includeVersion;
		
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
			$ret .= '<script type="text/javascript" src="'.$js[$i].'"></script>' . "\n";
		}
		
		return $ret;
		
		return '';
	}
	
	function getJSOnload()
	{
		// stuff to add to <body onload=...> - if possible, please prefer jQuery's onload functionality instead of <body onload=...>
		$ret = '';
		if( ($onload=$this->iniRead('onload')) != '' ) { $ret .= ' onload="' .$onload. '" '; }
		
		if( !$this->askfwd ) { $this->askfwd = strval($_REQUEST['askfwd']); }
		if(  $this->askfwd ) { $ret .= ' data-askfwd="' . isohtmlspecialchars($this->askfwd) . '" '; }
		
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
	
		if(str_replace("http://", "", $mobile_url) != "")
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
		$q_org = $this->getParam('q', '');
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
		
		// done
		return $ret;
	}
	
	function getPrologue($param = 0)
	{
		if( !is_array($param) ) $param = array();
		
		// prepare the HTML-Page
		$bodyStart = $GLOBALS['wisyPortalBodyStart'];
		if( strpos($bodyStart, '<html') === false )
		{
			// we got only an HTML-Snippet (part of the the body part), create a more complete HTML-page from this
			$bodyStart	= '<!DOCTYPE html>' . "\n"
						. '<html lang="de">' . "\n"
						. '<head>' . "\n"
						. '<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1" />' . "\n"
						. '__HEADTAGS__' 
						. '</head>' . "\n"
						. '<body__BODYATTR__>' . "\n"
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
		$bodyStart = str_replace('__HEADTAGS__', $this->getTitleTags($param['title']) . $this->getFaviconTags() . $this->getOpensearchTags() . $this->getRSSTags() . $this->getCSSTags() . $this->getCanonicalTag($param['canonical']) . $this->getMobileAlternateTag($param['canonical']) . $this->getJSHeadTags(). $this->getAdditionalHeadTags(), $bodyStart);
		$bodyStart = str_replace('__BODYATTR__', ' ' . $this->getJSOnload(). ' class="' . $this->getBodyClasses($param['bodyClass']) . '"', $bodyStart);
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

		// analytics stuff abottom to avoid analytics slow down
		// the whole site ...
		$uacct = $this->iniRead('analytics.uacct', '');
		if( $uacct != '' )
		{
			$ret .= '
				<script type="text/javascript">
				var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
				document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
				</script>
				<script type="text/javascript">
				var pageTracker = _gat._getTracker("' .$uacct. '");
				_gat._anonymizeIp();
				pageTracker._initData();
				pageTracker._trackPageview();
				</script>
			';
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
</script>
<noscript><p><img src=\"//".$piwik_site."/piwik.php?idsite=".$piwik_id."\" style=\"border:0;\" alt=\"\" /></p></noscript>
<!-- /analytics.piwik -->
";
		}
		
		// iwwb specials
		if( $this->iniRead('iwwbumfrage', 'unset')!='unset' && $_SERVER['HTTPS']!='on' )
		{
			require_once('files/iwwbumfrage.php');
		}
		
		$ret .= '</body>' . "\n";
		$ret .= '</html>' . "\n";
		
		return $ret;
	}
	
	function getSearchField()
	{
		// get the query
		$q = $this->getParam('q', '');
		$q_orig = $q;
		
		// radius search?
		if( $this->iniRead('searcharea.radiussearch', 0) )
		{
			// extract radius search parameters from query string
			$km_arr = array('' =>	'Umkreis', '1' => '1 km', '2' => '2 km', '5' => '5 km', '7' => '7 km', '10' => '10 km', '20' => '20 km', '50' => '50 km');
			$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this);
			$tokens = $searcher->tokenize($q);
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
			if( isset($tokens['show']) && $tokens['show'] == 'anbieter' ) {
				$q .= $q ? ', ' : '';
				$q .= 'Zeige:Anbieter';
			}
		}

		// if the query is not empty, add a comma and a space		
		$q = trim($q);
		if( $q != '' )
		{
			if( substr($q, -1) != ',' )
				$q .= ',';
			$q .= ' ';
		}

		// link to send favourites
		$mailfav = '';
		if( $this->iniRead('fav.mail', '1') ) {
			$mailsubject = $this->iniRead('fav.mail.subject', 'Kursliste von __HOST__');
			$mailsubject = str_replace('__HOST__', $_SERVER['HTTP_HOST'], $mailsubject);
			$mailbody = $this->iniRead('fav.mail.body', "Das ist meine Kursliste zum Ausdrucken von __HOST__:\n\nhttp://__HOST__/");
			$mailbody = str_replace('__HOST__', $_SERVER['HTTP_HOST'], $mailbody);
			$mailfav = 'mailto:?subject='.rawurlencode($mailsubject).'&body='.rawurlencode($mailbody);
		}

		// echo the search field
		$DEFAULT_PLACEHOLDER	= '';
		$DEFAULT_ADVLINK_HTML	= '<a href="advanced?q=__Q_URLENCODE__" id="wisy_advlink">Erweitern</a>';
		$DEFAULT_RIGHT_HTML		= '| <a href="javascript:window.print();">Drucken</a>';
		$DEFAULT_BOTTOM_HINT	= 'bitte <strong>Suchwörter</strong> eingeben - z.B. Englisch, VHS, Bildungsurlaub, ...';
		
		echo "\n";
		echo '<div id="wisy_searcharea">' . "\n";
			echo '<form action="search" method="get">' . "\n";
				echo '<input type="text" id="wisy_searchinput" class="ac_keyword" name="q" value="' .isohtmlspecialchars($q). '" placeholder="' . $this->iniRead('searcharea.placeholder', $DEFAULT_PLACEHOLDER) . '" />' . "\n";
				if( $this->iniRead('searcharea.radiussearch', 0) )
				{
					echo '<input type="text" id="wisy_beiinput" class="ac_plzort" name="bei" value="' .$bei. '" placeholder="PLZ/Ort" />' . "\n";
					echo '<select id="wisy_kmselect" name="km" >' . "\n";
						foreach( $km_arr as $value=>$descr ) {
							$selected = strval($km)==strval($value)? ' selected="selected"' : '';
							echo "<option value=\"$value\"$selected>$descr</option>";
						}
					echo '</select>' . "\n";
				}
				echo '<input type="submit" id="wisy_searchbtn" value="Suche" />' . "\n";
				if( $this->iniRead('searcharea.advlink', 1) )
				{
					echo '' . "\n";
				}
				
				echo $this->replacePlaceholders($this->iniRead('searcharea.advlink', $DEFAULT_ADVLINK_HTML)) . "\n";
				echo $this->replacePlaceholders($this->iniRead('searcharea.html', $DEFAULT_RIGHT_HTML)) . "\n";
			echo '</form>' . "\n";
			echo '<div class="wisy_searchhints" data-favlink="' . isohtmlspecialchars($mailfav) . '">' .  $this->replacePlaceholders($this->iniRead('searcharea.hint', $DEFAULT_BOTTOM_HINT)) . '</div>' . "\n";
		echo '</div>' . "\n\n";
	
		echo $this->replacePlaceholders( $this->iniRead('searcharea.below', '') ); // deprecated!
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
			// (in WISY 2.0 gibt es keine Datei "index.php", diese wird vom Framework aber als Synonym für "Homepage" verwendet)
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
				return createWisyObject('WISY_RSS_RENDERER_CLASS', $this, array('q'=>$this->getParam('q')));

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
			if( $renderer->unsecureOnly && $_SERVER['HTTPS']=='on' )
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




