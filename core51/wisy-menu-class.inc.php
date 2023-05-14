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
    
    function __construct($title, $url, $aparam, $level)
    {
        $this->title 	= $title;
        $this->url   	= $url;
        $this->aparam   = $aparam;
        $this->level 	= $level;
        $this->children = array();
    }
    
    function filterProblemStrings($str) {
        
        // don't parameters designating human triggers in menu-links
        $str = str_replace( array('?qsrc', '&qsrc', '&amp;qsrc'), array( '?replaced', '&replaced', '&amp;replaced' ), $str);
        $str = str_replace( array('?qtrigger', '&qtrigger', '&amp;qtrigger'), array( '?replaced', '&replaced', '&amp;replaced' ), $str);
        
        $str = str_replace( ' ', '%20', $str);
        
        return $str;
    }
    
    function getHtml()
    {
        $liClass = '';
        if( sizeof((array) $this->children) ) $liClass = ' class="dir '.($this->title == "OhneName" ? "ohneName" : "").'"';
        elseif($this->title == "OhneName") $liClass = ' class="ohneName"';
        
        $ret = "<li$liClass>";
        if( cs8($this->url) ) $ret .= '<a href="'.$this->filterProblemStrings(htmlspecialchars( cs8($this->url) )). // htmlspecialchars: convert "&" in URLs to "&amp;" in HTML
        '"'.$this->aparam.'>';
        $ret .= $this->title;
        if( $this->url ) $ret .= '</a>';
        
        if( sizeof((array) $this->children) )
        {
            $ret .= '<ul>';
            for( $i = 0; $i < sizeof((array) $this->children); $i++ )
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
        $this->db = new DB_Admin;
        $this->start_s = $this->framework->microtime_float();
    }
    
    // Themengebiet Items erzeugen (deprecated)
    // --------------------------------------------------------------------
    
    function handleLevel($startIndex, $level, $aparam, $nochildren = false)
    {
        global $g_themen;
        
        // add all children
        $thema = "";
        if(is_array($startIndex)) {
            foreach($startIndex AS $i) {
                $thema .= str_replace(',', ' ', cs8($g_themen[$i]['thema']))." ODER "; // Thema may contain comma
                $q .= str_replace(',', ' ', cs8($g_themen[$i]['thema']))." ODER "; // g_sync_removeSpecialChars()
            }
            $thema = preg_replace('/ ODER $/', '', $thema);
            $title = htmlspecialchars($thema);
            $q = $thema;
        } else {
            $thema = str_replace(',', ' ', cs8($g_themen[$startIndex]['thema']));
            $title = htmlspecialchars($thema);
            $q = g_sync_removeSpecialChars($thema);
        }
        
        $parent = new WISY_MENU_ITEM($title, 'search?q='.urlencode($q), $aparam, $level);
        
        if($nochildren)
            ;
        else {
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
            if( $endIndex == -1 ) return array( new WISY_MENU_ITEM("Thema $startIdOrKuerzel nicht gefunden", '', '', $level) );
        }
        else
        {
            if(stripos($startIdOrKuerzel, "#ODER#") !== FALSE){
                $startIdOrKuerzelArr = explode("#ODER#", $startIdOrKuerzel);
                foreach($startIdOrKuerzelArr AS $kuerzel) {
                    $multiThemaIndices[] = $this->idOrKuerzel2Index($kuerzel);
                }
            }
            else
                $startIndex = $this->idOrKuerzel2Index($startIdOrKuerzel);
                
                $endIndex = -1;
        }
        
        if( $startIndex == -1 && count((array) $multiThemaIndices) == 0)
            return array( new WISY_MENU_ITEM("Thema $startIdOrKuerzel nicht gefunden", '', '', $level) );
            
            if( $endIndex == -1 )
                $endIndex = $startIndex;
                
                $ret = array();
                if(count((array) $multiThemaIndices) && strpos($startIdOrKuerzel, '!') !== FALSE ) {
                    
                    $nochildren = true;
                    $ret[] = $this->handleLevel($multiThemaIndices, $level, $aparam, $nochildren);
                    
                    if( $title != '' ) { $ret[sizeof((array) $ret)-1]->title = $title; $title = ''; }
                    $ret[sizeof((array) $ret)-1]->level = $level;
                    
                } else {
                    
                    for( $i = $startIndex; $i <= $endIndex; $i++ )
                    {
                        if( strlen($g_themen[$i]['kuerzel_sorted']) == strlen($g_themen[$startIndex]['kuerzel_sorted']) )
                        {
                            if(strpos($startIdOrKuerzel, '!') === FALSE) {
                                $ret[] = $this->handleLevel($i, $level, $aparam);
                            }
                            else {
                                $nochildren = true;
                                $ret[] = $this->handleLevel($i, $level, $aparam, $nochildren);
                            }
                            
                            if( $title != '' ) { $ret[sizeof((array) $ret)-1]->title = $title; $title = ''; }
                            $ret[sizeof((array) $ret)-1]->level = $level;
                        }
                    }
                }
                
                return $ret;
    }
    
    // Stichwort Items erzeugen
    // --------------------------------------------------------------------
    
    protected function &addKeywordsRecursive($manualTitle, $keywordId, $level, $addChildren, $additionalqStr = "", $aparam = "")
    {
        global $g_keywords;
        
        // check for timeout
        $timeout_after_s = 5.000;
        if( $this->framework->microtime_float() - $this->start_s > $timeout_after_s ) {
            return new WISY_MENU_ITEM('Timeout error', '', '', $level);;
        }
        
        // add the item itself
        if( strpos($keywordId, '&') !== false )
        {
            $autoTitle = '';
            $url = '';
            $temp = explode('&', $keywordId);
            for( $i = 0; $i < sizeof($temp); $i++ ) {
                $currKeywordId = intval($temp[$i]);
                if( $currKeywordId > 0 ) {
                    $autoTitle = cs8($g_keywords[ $keywordId ]);
                    $autoTitle .= $g_keywords[ $currKeywordId ];
                    $url .= $url==''? '' : urlencode(', ');
                    $url .= urlencode( (PHP7 ? utf8_decode(g_sync_removeSpecialChars($g_keywords[ $currKeywordId ])) : g_sync_removeSpecialChars($g_keywords[ $currKeywordId ])) );
                }
            }
            $url = 'search?q=' . $url . $additionalqStr;
            $addChildren = false;
        }
        elseif( stripos($keywordId, '#ODER#') !== false )
        {
            $autoTitle = '';
            $url = '';
            $temp = explode('#ODER#', strtoupper($keywordId));
            for( $i = 0; $i < sizeof($temp); $i++ ) {
                $currKeywordId = intval($temp[$i]);
                if( $currKeywordId > 0 ) {
                    $autoTitle .= $autoTitle==''? '' : ' ';
                    $autoTitle = cs8($g_keywords[ $currKeywordId ]);
                    $url .= $url==''? '' : urlencode(' ODER ');
                    $url .= urlencode( (PHP7 ? utf8_decode(g_sync_removeSpecialChars($g_keywords[ $currKeywordId ])) : g_sync_removeSpecialChars($g_keywords[ $currKeywordId ])) );
                }
            }
            $url = 'search?q=' . $url . $additionalqStr;
            $addChildren = false;
        }
        else
        {
            
            $keywordId = intval($keywordId);
            $autoTitle = $g_keywords[ $keywordId ];
            
            $url = 'search?q=' . urlencode( (PHP7 ? utf8_decode(g_sync_removeSpecialChars($g_keywords[ $keywordId ])) : g_sync_removeSpecialChars($g_keywords[ $keywordId ])) ) . $additionalqStr;
        }
        
        $item = new WISY_MENU_ITEM($manualTitle!=''? $manualTitle : $autoTitle, $url, $aparam, $level);
        
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
    
    protected function &createKeywordItems($title, $keywordIds, $level, $aparam) // $title: may be empty, $keywordIds: comma separated list of keywords, a `+` indicates that children should be added, too
    {
        if( ($p=strpos($keywordIds, ';'))!==false ) { $keywordIds = substr($keywordIds, 0, $p); } // allow comments after a `;` (this is undocumented stuff!)
        $keywordIds = explode(',', $keywordIds);
        $ret_items = array();
        for( $k = 0; $k < sizeof($keywordIds); $k++ )
        {
            $addChildren = 0;
            $keywordId = $keywordIds[$k];
            
            // Identify additions to keyword entry like zeige:anbieter
            $additionalqStr = "";
            foreach($keywordIds AS $addStrComponent) {
                if(!is_numeric($addStrComponent) && strpos($addStrComponent, '&') === FALSE && strpos($addStrComponent, '#') === FALSE)
                    $additionalqStr .= ',%20'.$addStrComponent;
            }
            $keywordId = str_replace(' ', '', $keywordId); // remove all spaces for easier parsing
            
            if( ($p=strpos($keywordId, '+')) !== false ) { $addChildren = intval(substr($keywordId, $p+1)); if($addChildren<=0) {$addChildren=5 /*avoid too deep recursions*/;} $keywordId = substr($keywordId, 0, $p); }
            
            if(is_numeric($keywordId) || strpos($keywordId, '&') !== FALSE || strpos($keywordId, '#') !== FALSE)
                $ret_items[] =& $this->addKeywordsRecursive($k==0? $title : '', $keywordId, $level, $addChildren, $additionalqStr, $aparam);
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
            return $this->createKeywordItems($title, $keywordIds, $level, $aparam);
        }
        
        if( $title == '' )
        {
            $title = $url;
            if( $title == '' ) $title = 'OhneName';
        }
        
        $wisy_menu_item = new WISY_MENU_ITEM($title, $url, $aparam, $level);
        return array( $wisy_menu_item );
    }
    
    function getHtml()
    {
        global $wisyPortalEinstellungen;
        global $wisyPortalModified;
        
        $cacheKey = $wisyPortalModified . ' ' .ftime('%Y-%m-%d %H:00:00'). ' v7'; // the key changes if the portal record is updated or at least every hour (remember __DATE__ etc.)
        if( $this->framework->cacheRead("menu.{$this->prefix}.key", '')==$cacheKey )
        {
            // read the menu from the cache ...
            $ret = '<!-- dropdown read from cache -->' . $this->framework->cacheRead("menu.{$this->prefix}.cache");
        }
        else
        {
            // build menu tree ...
            $root = new WISY_MENU_ITEM('', '', '', 0);
            
            reset($wisyPortalEinstellungen);
            $allPrefix = $this->prefix . '.';
            $allPrefixLen = strlen($allPrefix);
            foreach($wisyPortalEinstellungen as $key => $value)
            {
                if( substr($key, 0, $allPrefixLen)==$allPrefix )
                {
                    $levels = explode('.', substr($key, $allPrefixLen));
                    
                    // find the correct parent ...
                    $parent =& $root;
                    for( $l = 0; $l < sizeof((array) $levels)-1; $l++ )
                    {
                        $level = intval($levels[$l]);
                        $levelFound = false;
                        for( $c = 0; $c < sizeof((array) $parent->children); $c++ )
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
                    for( $a = 0; $a < sizeof((array) $addChildren); $a++ )
                        $parent->children[] =& $addChildren[$a];
                }
            }
            
            // get the menu as HTML
            $ret = '<ul class="dropdown dropdown-horizontal">';
            for( $i = 0; $i < sizeof((array) $root->children); $i++ )
            {
                $ret .= $root->children[$i]->getHtml();
            }
            $ret .= '</ul>';
            
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