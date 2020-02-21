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
	
	function __construct($title, $url, $aparam, $level, $prefix='', $a11Type='simple')
	{
		$this->title 	= $title;
		$this->url   	= $url;
		$this->aparam   = $aparam;
		$this->level 	= $level;
		$this->prefix	= $prefix;
		$this->a11Type  = $a11Type;
		$this->children = array();
	}
	
	function getHtml($toplevel=false)
	{
		$liTag = '<li';
		if( sizeof((array) $this->children) ) $liTag .= ' class="dir has-subnav'.($this->title == "OhneName" ? " ohneName" : "").'" aria-haspopup="true"';
		elseif($this->title == "OhneName") $liTag .= ' class="ohneName"';
		$liTag .= ($this->a11Type == 'complex') ? ' role="presentation">' : '>';
		
		$submenuId = uniqid('submenu-'.$this->prefix.'-'.$this->level.'-');
		
		$ret = $liTag;
			if( cs8($this->url) ) {
				$ret .= '<a href="'.htmlspecialchars(cs8($this->url)).'"';
				$ret .= $this->aparam.($toplevel ? ' tabindex="-1"' : '');
				$ret .= sizeof((array) $this->children) ? ' data-submenu-id="'.$submenuId.'"' : '';
				$ret .= ($this->a11Type == 'complex') ? ' role="menuitem">' : '>';
				$ret .= $this->title;
				$ret .= '</a>';
			} else {
				$ret .= '<span class="nav_no_link"';
				$ret .= sizeof((array) $this->children) ? ' data-submenu-id="'.$submenuId.'"' : '';
				$ret .= ($this->a11Type == 'complex') ? ' role="menuitem">' : '>';
				$ret .= $this->title;
				$ret .= '</span>';
			}

			if( sizeof((array) $this->children) )
			{
				$ret .= '<ul id="'.$submenuId.'"';
				if($this->a11Type == 'complex') {
					$ret .= ' class="vertical menu hidden" role="menu"';
				} else {
					$ret .= ' data-test="true" aria-hidden="true"';
				}
				$ret .= '>';
				for( $i = 0; $i < sizeof((array) $this->children); $i++ )
				{
					$ret .= $this->children[$i]->getHtml(true);
				}
				$ret .= '</ul>';
			}
			
		$ret .= '</li>';
		
		return $ret;
	}
	
	function microtime_int()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((int)$usec + (int)$sec);
	}
};



class WISY_MENU_CLASS
{
	var $framework;
	var $prefix;
	var $root;
	var $a11Type;

	function __construct(&$framework, $param)
	{
		// constructor
		$this->framework =& $framework;
		$this->prefix = $param['prefix'];
		$this->db = new DB_Admin;
		$this->start_s = $this->framework->microtime_float();
		$this->a11Type = 'simple';
	}
	
	// Themengebiet Items erzeugen (deprecated)
	// --------------------------------------------------------------------

	function handleLevel($startIndex, $level, $aparam)
	{
		global $g_themen;

		// add all children
		
		$thema = cs8($g_themen[$startIndex]['thema']);
		
		$title = htmlspecialchars($thema);
		
		$q = g_sync_removeSpecialChars($thema);
		
		$parent = new WISY_MENU_ITEM($title, 'search?q='.urlencode($q), $aparam, $level, $this->prefix, $this->a11Type);
		
		$startKuerzel = $g_themen[$startIndex]['kuerzel_sorted'];
		$startKuerzelLen = strlen($startKuerzel);
		for( $i = 0; $i < sizeof((array) $g_themen); $i++ )
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
		for( $i = 0; $i < sizeof((array) $g_themen); $i++ )
		{
			if( ( $hasPoint && $g_themen[$i]['kuerzel_sorted'] == $startIdOrKuerzel_natsort) 
			 || (!$hasPoint && $g_themen[$i]['id'] == $startIdOrKuerzel) )
			{
				return $i;
			}
		}
		
		return -1;
	}

	function &createRootThemenItems($title, $startIdOrKuerzel, $aparam, $level)
	{	
		global $g_themen;
	
		// alle themengebiete laden ...
		if( !is_array($g_themen) )
		{
			$g_themen = array();
			
			$sql = "SELECT id, kuerzel_sorted, thema FROM themen ORDER BY kuerzel_sorted;";
			$this->db->query($sql); 
			while( $this->db->next_record() ) 
				$g_themen[] = $this->db->Record;
		}

		// find starting thema (id has the form <int>, kuerzel has the form "<level>.[<level> ...]"
		if( ($p=strpos($startIdOrKuerzel, '-'))!==false )
		{
			$startIndex = $this->idOrKuerzel2Index(substr($startIdOrKuerzel, 0, $p));
			$endIndex = $this->idOrKuerzel2Index(substr($startIdOrKuerzel, $p+1));
			if( $endIndex == -1 ) return array( new WISY_MENU_ITEM("Thema $startIdOrKuerzel nicht gefunden", '', '', $level, $this->prefix, $this->a11Type) );
		}
		else
		{
			$startIndex = $this->idOrKuerzel2Index($startIdOrKuerzel);
			$endIndex = -1;
		}
		
		if( $startIndex == -1 ) 
			return array( new WISY_MENU_ITEM("Thema $startIdOrKuerzel nicht gefunden", '', '', $level, $this->prefix, $this->a11Type) );

		if( $endIndex == -1 )
			$endIndex = $startIndex;
		
		$ret = array();
		for( $i = $startIndex; $i <= $endIndex; $i++ )
		{
			if( strlen($g_themen[$i]['kuerzel_sorted']) == strlen($g_themen[$startIndex]['kuerzel_sorted']) )
			{
				$ret[] = $this->handleLevel($i, $level, $aparam);
				
	 			if( $title != '' ) { $ret[sizeof((array) $ret)-1]->title = $title; $title = ''; }
				$ret[sizeof((array) $ret)-1]->level = $level;
			}
		}
		
		return $ret;
	}
	
	// Stichwort Items erzeugen
	// --------------------------------------------------------------------
	
	protected function &addKeywordsRecursive($manualTitle, $keywordId, $level, $addChildren)
	{
		global $g_keywords;
		
		// check for timeout
		$timeout_after_s = 5.000;
		if( $this->framework->microtime_float() - $this->start_s > $timeout_after_s ) {
			return new WISY_MENU_ITEM('Timeout error', '', '', $level);
		}		
		
		// add the item itself
		if( strpos($keywordId, '&') !== false ) 
		{
			$autoTitle = '';
			$url = '';
			$temp = explode('&', $keywordId);
			for( $i = 0; $i < sizeof((array) $temp); $i++ ) {
				$currKeywordId = intval($temp[$i]);
				if( $currKeywordId > 0 ) {
					$autoTitle .= $autoTitle==''? '' : ' ';
					$autoTitle = cs8($g_keywords[ $keywordId ]);
					$url .= $url==''? '' : urlencode(', ');
					$url .= urlencode(g_sync_removeSpecialChars($g_keywords[ $currKeywordId ]));
				}
			}
			$url = 'search?q=' . $url;
			$addChildren = false;
		}
		else
		{
			$keywordId = intval($keywordId);
			$autoTitle = cs8($g_keywords[ $currKeywordId ]);
			$url = 'search?q=' . urlencode(g_sync_removeSpecialChars($g_keywords[ $keywordId ]));
		}
		
		$item = new WISY_MENU_ITEM($manualTitle!=''? $manualTitle : $autoTitle, $url, '', $level, $this->prefix, $this->a11Type);
		
		// check, if there are child items
		if( $addChildren > 0 ) 
		{
			$attr_ids = array();
			$this->db->query("SELECT attr_id FROM stichwoerter_verweis2 WHERE primary_id=$keywordId ORDER BY structure_pos;");
			while( $this->db->next_record() ) {
				$attr_ids[] = $this->db->f8('attr_id');
			}
		
			for( $a = 0; $a < sizeof((array) $attr_ids); $a++ ) {
				$item->children[] =& $this->addKeywordsRecursive('', $attr_ids[$a], $level+1, $addChildren-1);
			}
		}		
		
		return $item;
	}
	
	protected function &createKeywordItems($title, $keywordIds, $level) // $title: may be empty, $keywordIds: comma separated list of keywords, a `+` indicates that children should be added, too 
	{
		$keywordIds = str_replace(' ', '', $keywordIds); // remove all spaces for easier parsing
		if( ($p=strpos($keywordIds, ';'))!==false ) { $keywordIds = substr($keywordIds, 0, $p); } // allow comments after a `;` (this is undocumented stuff!)
		$keywordIds = explode(',', $keywordIds);
		$ret_items = array();
		for( $k = 0; $k < sizeof((array) $keywordIds); $k++ ) 
		{
			$addChildren = 0;
			$keywordId = $keywordIds[$k];
			if( ($p=strpos($keywordId, '+')) !== false ) { $addChildren = intval(substr($keywordId, $p+1)); if($addChildren<=0) {$addChildren=5 /*avoid too deep recursions*/;} $keywordId = substr($keywordId, 0, $p); }
			
			$ret_items[] =& $this->addKeywordsRecursive($k==0? $title : '', $keywordId, $level, $addChildren);
		}
		
		return $ret_items;
	
		
	}
	
	// Misc.
	// --------------------------------------------------------------------
			
	function explodeMenuParam($param, &$title, &$url, &$aparam)
	{
		$title = '';
		$url = '';
		$aparam = '';
		$param = explode('|', $param);
		if( sizeof((array) $param) == 3 )
		{
			// parameters are in the format "title | url | target='_blank'",  "title | url | onclick='...'" etc.
			$title = cs8(trim($param[0]));
			$url = trim($param[1]);
			$aparam = ' ' . $param[2] . ' ';
		}
		else if( sizeof((array) $param) == 2 )
		{
			// parameters are in the format "title | url"
			$title = cs8(trim($param[0]));
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
				$title = cs8(trim($param[0]));
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
				$this->db->query("SELECT id, stichwort FROM stichwoerter;");
				while( $this->db->next_record() ) {
					$g_keywords[ $this->db->f8('id') ] = $this->db->f8('stichwort');
				}
			}
			
			$keywordIds = substr($url, 8); // comma separated list of keywords, a `+` indicates that children should be added, too 
			return $this->createKeywordItems($title, $keywordIds, $level);
		}

		if( $title == '' )
		{
			$title = $url;
			if( $title == '' ) $title = 'OhneName';
		}
		
		return array( new WISY_MENU_ITEM($title, $url, $aparam, $level, $this->prefix, $this->a11Type) );
	}
	
	function getHtml()
	{
		global $wisyPortalEinstellungen;
		global $wisyPortalModified;
		
		$cacheKey = $wisyPortalModified . ' ' .strftime('%Y-%m-%d %H:00:00'). ' v7'; // the key changes if the portal record is updated or at least every hour (remember __DATE__ etc.)
		if( $this->framework->cacheRead("menu.{$this->prefix}.key", '')==$cacheKey )
		{
			// read the menu from the cache ...
			$ret = '<!-- dropdown read from cache -->' . $this->framework->cacheRead("menu.{$this->prefix}.cache");
		}
		else
		{
			// build menu tree ...
			$root = new WISY_MENU_ITEM('', '', '', 0, $this->prefix, $this->a11Type);
			
			reset($wisyPortalEinstellungen);
			$allPrefix = $this->prefix . '.';
			$allPrefixLen = strlen($allPrefix);
			
			foreach($wisyPortalEinstellungen as $key => $value)
			{
				if( substr($key, 0, $allPrefixLen)==$allPrefix )
				{
					$levels = explode('.', substr($key, $allPrefixLen));
					
					// Read optional setting for accessibility type this menu should use
					//	simple -> tab-navigation
					//	complex -> arrow-navigation
					if(count((array) $levels) && $levels[0] == 'type' && $value != '') {
						$this->a11Type = $value;
						continue;
					}
					
					// find the correct parent ...
					$parent =& $root;
					for( $l = 0; $l < sizeof((array) $levels)-1; $l++ )
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
							$parent->children[] = new WISY_MENU_ITEM('OhneName', '', '', $level, $this->prefix, $this->a11Type);
							$parent =& $parent->children[sizeof($parent->children)-1];;
						}
					}
					
					// add item to parent
					$addChildren =& $this->createItems($value, intval($levels[sizeof((array) $levels)-1]));
					for( $a = 0; $a < sizeof((array) $addChildren); $a++ )
						$parent->children[] =& $addChildren[$a];
				}
			}
			
			// get the menu as HTML
			$navClass = 'wisyr_menu wisyr_menu_' . $this->a11Type;
			$nav = '<nav class="nav_' . $this->prefix . ' ' . $navClass . ' clearfix"';
			$ul = '<ul id="wisyr_menu_' . $this->prefix . '" aria-hidden="false"';
			if($this->a11Type == 'complex') {
				$nav .= ' role="application" aria-label="MenuBar">';
				$ul .= ' class="dropdown dropdown-horizontal horizontal menubar ' . $navClass . '_level1" role="menubar">';
			} else {
				$nav .= '>';
				$ul .= ' class="dropdown dropdown-horizontal ' . $navClass . '_level1">';
			}
			$ret = $nav.$ul;
			
			for( $i = 0; $i < sizeof($root->children); $i++ )
			{
				$ret .= $root->children[$i]->getHtml();
			}
			$ret .= '</ul></nav>';
			
			// add time
			$secneeded = $this->framework->microtime_float() - $this->start_s;
			$ret = sprintf("<!-- dropdown creation time: %1.3f s -->", $secneeded) . $ret;
			
			
			// write the complete menu to the cache
			$this->framework->cacheWrite("menu.{$this->prefix}.key", $cacheKey);
			$this->framework->cacheWrite("menu.{$this->prefix}.cache", $ret);
		}
		
		// handle the menu placeholders
		$ret = $this->framework->replacePlaceholders($ret);
		
		return $ret;
	}
};



