<?php if( !defined('IN_WISY') ) die('!IN_WISY');



global $g_themen;
$g_themen = 0;

global $g_keywords;
$g_keywords = 0;



class WISY_MENU_ITEM
{
	var $title;
	var $url;
	var $aparam;
	var $level;
	var $children;
	
	function WISY_MENU_ITEM($title, $url, $aparam, $level)
	{
		$this->title 	= $title;
		$this->url   	= $url;
		$this->aparam   = $aparam;
		$this->level 	= $level;
		$this->children = array();
	}
	
	function getHtml()
	{
		$liClass = '';
		if( sizeof($this->children) ) $liClass = ' class="dir"';
		$ret = "<li$liClass>";
		
			if( $this->url ) $ret .= '<a href="'.isohtmlspecialchars($this->url). /*convert "&" in URLs to "&amp;" in HTML*/
									'"'.$this->aparam.'>'; 
			$ret .= $this->title;
			if( $this->url ) $ret .= '</a>';

			if( sizeof($this->children) )
			{
				$ret .= '<ul>';
					for( $i = 0; $i < sizeof($this->children); $i++ )
					{
						$ret .= $this->children[$i]->getHtml();
					}
				$ret .= '</ul>';
			}
			
		$ret .= '</li>';
		
		return $ret;
	}
};



class WISY_MENU_CLASS
{
	var $framework;
	var $prefix;
	var $root;

	function __construct(&$framework, $param)
	{
		// constructor
		$this->framework =& $framework;
		$this->prefix = $param['prefix'];
	}
	
	// Themengebiet Item erzeugen - Tools
	// --------------------------------------------------------------------

	function handleLevel($startIndex, $level, $aparam)
	{
		global $g_themen;

		// add all children
		
		$thema = $g_themen[$startIndex]['thema'];
		
		$title = isohtmlspecialchars($thema);
		
		$q = g_sync_removeSpecialChars($thema);
		
		$parent = new WISY_MENU_ITEM($title, 'search?q='.urlencode($q), $aparam, $level);
		
		$startKuerzel = $g_themen[$startIndex]['kuerzel_sorted'];
		$startKuerzelLen = strlen($startKuerzel);
		for( $i = 0; $i < sizeof($g_themen); $i++ )
		{
			if( substr($g_themen[$i]['kuerzel_sorted'], 0, $startKuerzelLen) == $startKuerzel 
			 && strlen($g_themen[$i]['kuerzel_sorted']) == $startKuerzelLen+10 )
			{
				$parent->children[] = $this->handleLevel($i, $level, $aparam);
			}
		}
		
		return $parent;	
	}
	
	function idOrKuerzel2Index($startIdOrKuerzel)
	{
		global $g_themen;
	
		// find id or kuerzel (id has the form <int>, kuerzel has the form "<level>.[<level> ...]"
		$startIdOrKuerzel = trim($startIdOrKuerzel);
		$startIdOrKuerzel_natsort = $this->framework->normalizeNatsort($startIdOrKuerzel);
		$hasPoint = strpos($startIdOrKuerzel, '.')!==false;
		for( $i = 0; $i < sizeof($g_themen); $i++ )
		{
			if( ( $hasPoint && $g_themen[$i]['kuerzel_sorted'] == $startIdOrKuerzel_natsort) 
			 || (!$hasPoint && $g_themen[$i]['id'] == $startIdOrKuerzel) )
			{
				return $i;
			}
		}
		
		return -1;
	}

	// Themengebiet Item erzeugen
	// --------------------------------------------------------------------

	function &createRootThemenItems($title, $startIdOrKuerzel, $aparam, $level)
	{	
		global $g_themen;
	
		// alle themengebiete laden ...
		if( !is_array($g_themen) )
		{
			$g_themen = array();
			$db = new DB_Admin;
			$sql = "SELECT id, kuerzel_sorted, thema FROM themen ORDER BY kuerzel_sorted;";
			$db->query($sql); 
			while( $db->next_record() ) 
				$g_themen[] = $db->Record;
		}

		// find starting thema (id has the form <int>, kuerzel has the form "<level>.[<level> ...]"
		if( ($p=strpos($startIdOrKuerzel, '-'))!==false )
		{
			$startIndex = $this->idOrKuerzel2Index(substr($startIdOrKuerzel, 0, $p));
			$endIndex = $this->idOrKuerzel2Index(substr($startIdOrKuerzel, $p+1));
			if( $endIndex == -1 ) return array( new WISY_MENU_ITEM("Thema $startIdOrKuerzel nicht gefunden", '', '', $level) );
		}
		else
		{
			$startIndex = $this->idOrKuerzel2Index($startIdOrKuerzel);
			$endIndex = -1;
		}
		
		if( $startIndex == -1 ) 
			return array( new WISY_MENU_ITEM("Thema $startIdOrKuerzel nicht gefunden", '', '', $level) );

		if( $endIndex == -1 )
			$endIndex = $startIndex;
		
		$ret = array();
		for( $i = $startIndex; $i <= $endIndex; $i++ )
		{
			if( strlen($g_themen[$i]['kuerzel_sorted']) == strlen($g_themen[$startIndex]['kuerzel_sorted']) )
			{
				$ret[] = $this->handleLevel($i, $level, $aparam);
				
	 			if( $title != '' ) { $ret[sizeof($ret)-1]->title = $title; $title = ''; }
				$ret[sizeof($ret)-1]->level = $level;
			}
		}
		
		return $ret;
	}
	
	function explodeMenuParam($param, &$title, &$url, &$aparam)
	{
		$title = '';
		$url = '';
		$aparam = '';
		$param = explode('|', $param);
		if( sizeof($param) == 3 )
		{
			// parameters are in the format "title | url | target='_blank'",  "title | url | onclick='...'" etc.
			$title = trim($param[0]);
			$url = trim($param[1]);
			$aparam = ' ' . $param[2] . ' ';
		}
		else if( sizeof($param) == 2 )
		{
			// parameters are in the format "title | url"
			$title = trim($param[0]);
			$url = trim($param[1]);
		}
		else
		{
			$param[0] = trim($param[0]);
			if( substr($param[0], 0, 6) == 'thema:' || substr($param[0], 0, 8) == 'keyword:' )
			{
				// parameters are in the format "url"
				$url = $param[0];
			}
			else
			{
				// parameters are in the format "title"
				$title = $param[0];
			}
		}
	}
	
	function &createItems($param, $level)
	{
		// normales Item erzeugen
		// --------------------------------------------------------------------
		
		// check parameters
		$this->explodeMenuParam($param, $title, $url, $aparam);

		if( substr($url, 0, 6) == 'thema:' )
		{
			$rootIdOrKuerzel = substr($url, 6);
			$ret =& $this->createRootThemenItems($title, $rootIdOrKuerzel, $aparam, $level);
			return $ret;
		}
		else if( substr($url, 0, 8) == 'keyword:' )
		{
			global $g_keywords;
			if( !is_array($g_keywords) )
			{
				$g_keywords = array();
				$db = new DB_Admin;
				$db->query("SELECT id, stichwort FROM stichwoerter;");
				while( $db->next_record() )
					$g_keywords[ $db->f('id') ] = $db->fs('stichwort');
			}
			
			$keywordId = intval(substr($url, 8));
			$title = $g_keywords[ $keywordId ];
			$url = 'search?q=' . urlencode(g_sync_removeSpecialChars($title));
		}

		if( $title == '' )
		{
			$title = $url;
			if( $title == '' ) $title = 'OhneName';
		}
		
		return array( new WISY_MENU_ITEM($title, $url, $aparam, $level) );
	}
	
	function getHtml()
	{
		global $wisyPortalEinstellungen;
		global $wisyPortalModified;
		
		$cacheKey = $wisyPortalModified . ' ' .strftime('%Y-%m-%d %H:00:00'). ' v5'; // the key changes if the portal record is updated or at least every hour (remember __DATE__ etc.)
		if( $this->framework->cacheRead("menu.{$this->prefix}.key", '')==$cacheKey )
		{
			// read the menu from the cache ...
			$ret = $this->framework->cacheRead("menu.{$this->prefix}.cache");
		}
		else
		{
			// build menu tree ...
			$root = new WISY_MENU_ITEM('', '', '', 0);
			
			reset($wisyPortalEinstellungen);
			$allPrefix = $this->prefix . '.';
			$allPrefixLen = strlen($allPrefix);
			while( list($key, $value) = each($wisyPortalEinstellungen) )
			{
				if( substr($key, 0, $allPrefixLen)==$allPrefix )
				{
					$levels = explode('.', substr($key, $allPrefixLen));
					
					// find the correct parent ...
					$parent =& $root;
					for( $l = 0; $l < sizeof($levels)-1; $l++ )
					{
						$level = intval($levels[$l]);
						$levelFound = false;
						for( $c = 0; $c < sizeof($parent->children); $c++ )
						{
							if( $parent->children[$c]->level == $level )
								{ $parent =& $parent->children[$c]; $levelFound = true; break; }
						}
						
						if( !$levelFound )
						{
							$parent->children[] = new WISY_MENU_ITEM('OhneName', '', '', $level);
							$parent =& $parent->children[sizeof($parent->children)-1];;
						}
					}
					
					// add item to parent
					$addChildren =& $this->createItems($value, intval($levels[sizeof($levels)-1]));
					for( $a = 0; $a < sizeof($addChildren); $a++ )
						$parent->children[] =& $addChildren[$a];
				}
			}
			
			// get the menu as HTML		
			$ret = '<ul class="dropdown dropdown-horizontal">';
				for( $i = 0; $i < sizeof($root->children); $i++ )
				{
					$ret .= $root->children[$i]->getHtml();
				}
			$ret .= '</ul>';
			
			// write the complete menu to the cache
			$this->framework->cacheWrite("menu.{$this->prefix}.key", $cacheKey);
			$this->framework->cacheWrite("menu.{$this->prefix}.cache", $ret);
		}
		
		// handle the menu placeholders
		$ret = $this->framework->replacePlaceholders($ret);
		
		return $ret;
	}
};



