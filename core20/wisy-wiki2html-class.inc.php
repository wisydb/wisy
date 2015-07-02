<?php if( !defined('IN_WISY') ) die('!IN_WISY');



require_once("admin/wiki2html.inc.php");



class WISY_WIKI2HTML_CLASS extends WIKI2HTML_CLASS
{
	/* create keywords overviews
	 **************************************************************************/
	
	protected function renderKeywordsLink($keywordId, $format)
	{
		$title = $this->keywords[ $keywordId ];
		$url = 'search?q=' . urlencode(g_sync_removeSpecialChars($title));
		return str_replace('%s', '<a href="'.$url.'">' . isohtmlspecialchars($title) .  '</a>', $format);
	}
	
	protected function renderKeywordsDivRecursive($keywordId, $level, $addChildren)
	{
		switch( $level )
		{
			case 0:
				$format = '<b>%s</b>';
				
				break;
			
			case 1:
				$format = '%s';
				
				break;
			
			default:
				$format = '<small>%s</small>';
				
				break;
		}
	
		// add the item itself
		
		
		$ret .= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);

		$ret .= $this->renderKeywordsLink($keywordId, $format);

		$ret .= '<br />';
		

		// check, if there are child items
		if( $addChildren > 0 ) 
		{
			$attr_ids = array();
			$this->db->query("SELECT attr_id FROM stichwoerter_verweis2 WHERE primary_id=$keywordId ORDER BY structure_pos;");
			while( $this->db->next_record() ) {
				$attr_ids[] = $this->db->f('attr_id');
			}
		
			for( $a = 0; $a < sizeof($attr_ids); $a++ ) {
				$ret .= $this->renderKeywordsDivRecursive($attr_ids[$a], $level+1, $addChildren-1);
				
			}
		}
		
		return $ret;
	}
	
	protected function renderKeywordsDiv($keywordIdsOrg) // $keywordIds: comma separated list of keywords, a `+` indicates that children should be added, too 
	{
		$ret = '';
		
		if( !is_array($this->keywords) )
		{
			$this->keywords = array();
			$this->db->query("SELECT id, stichwort FROM stichwoerter;");
			while( $this->db->next_record() ) {
				$this->keywords[ $this->db->f('id') ] = $this->db->fs('stichwort');
			}
		}
		

		$keywordIds = str_replace(' ', '', $keywordIdsOrg); // remove all spaces for easier parsing
		if( ($p=strpos($keywordIds, ';'))!==false ) { $keywordIds = substr($keywordIds, 0, $p); } // allow comments after a `;` (this is undocumented stuff!)
		$keywordIds = explode(',', $keywordIds);
		$ret_items = array();
		for( $k = 0; $k < sizeof($keywordIds); $k++ ) 
		{
			$addChildren = 0;
			$keywordId = $keywordIds[$k];
			if( ($p=strpos($keywordId, '+')) !== false ) { $addChildren = intval(substr($keywordId, $p+1)); if($addChildren<=0) {$addChildren=666;} $keywordId = substr($keywordId, 0, $p); }
			$keywordId = intval($keywordId);
			
			$temp = $this->renderKeywordsDivRecursive($keywordId, 0, $addChildren);
			if( $temp != '' ) {
				$ret .= '<p>'.$temp.'</p>';
			}

		}		
		
		if( $ret == '' ) 
		{
			$ret = '<p>Keine Stichw. gefunden f&uuml;r <i>[[keyword('.htmlspecialchars($keywordIdsOrg).')]]</i></p>';
		}
		
		return $ret;
	}
	
	/* misc.
	 **************************************************************************/
	 
	function __construct(&$framework)
	{
		$this->db = new DB_Admin;
		$this->framework =& $framework;
		parent::WIKI2HTML_CLASS();
	}

	function pageExists($title)
	{
		$this->db->query("SELECT id FROM glossar WHERE begriff='".addslashes($title)."' OR id=".intval($title));
		if( $this->db->next_record() )
		{
			return 1;
		}
		else
		{
			return 'Diese Seite existiert nicht.';
		}
	}

	function pageUrl($title, $pageExists)
	{
		if( $pageExists )
		{
			$this->db->query("SELECT id FROM glossar WHERE begriff='".addslashes($title)."' OR id=".intval($title));
			$this->db->next_record();
			return $this->framework->getHelpUrl(intval($this->db->f('id')));
		}
		else
		{
			return $this->framework->getUrl('search');
		}
	}

	function pageFunction($name, $param, &$state)
	{
		$ret = '';
		
		if( $name == 'index' )
		{
			$lastChar = '%';
			$pStarted = false;
			$db = new DB_Admin;
			$db->query("SELECT begriff, begriff_sorted, id FROM glossar WHERE erklaerung!='' AND freigeschaltet=1 ORDER BY begriff_sorted");
			while( $db->next_record() )
			{
				$thisChar = strtoupper(substr($db->fs('begriff_sorted'), 0, 1));
				if( $thisChar >= 'A' && $thisChar != $lastChar )
				{
					if( $pStarted ) {$ret .= '</p>'; $pStarted = false;}
					
					$ret .= '<p><big><b>'.$thisChar.'</b></big></p>';
					$lastChar = $thisChar;
				}
				$begriff = isohtmlentities($db->fs('begriff'));
				$idtemp = $db->f('id');
				
				$ret .= $pStarted? '<br />' : '<p>';
				$ret .= "<a href=\"".$this->framework->getHelpUrl($idtemp)."\">$begriff</a>";
				$pStarted = true;
			}
			if( $pStarted ) {$ret .= '</p>'; $pStarted = false;}
			$state = 2; // returned value is a paragraph
		}
		else if( $name == 'keyword' )
		{
			$keywordIds = $param; // comma separated list of keywords, a `+` indicates that children should be added, too 
			$ret = $this->renderKeywordsDiv($keywordIds);
			$state = 2; // returned value is a paragraph
		}
		else
		{
			$state = 0; // return value is invalid
		}

		return $ret;
	}

	function renderEmph($emph, $open)
	{
		if ( $emph=='wild' ) {
			return $open? '<b class="wikiwild">' : '</b>';
		}
		else {
			return parent::renderEmph($emph, $open);
		}
	}

	function renderA($text, $type, $href, $tooltip, $pageExists)
	{
		if( $this->forceBlankTarget ) {
			$blank = " target=\"_blank\"";
		}
			
		if( $type == 'internal' ) {
			return	"<a href=\"$href\"$blank>$text</a>";
		}
		else if( $type == 'http' || $type == 'https' ) {
			return	"<a href=\"$href\" target=\"_blank\"><i>$text</i></a>";
		}
		else if( $type == 'mailto' ) {
			return	"<a href=\"$href\"$blank><i>$text</i></a>";
		}
		else {
			return parent::renderA($text, $type, $href, $tooltip, $pageExists);
		}
	}

	function renderBox($level, $open)
	{
		switch( $level )
		{
			case 1:		return $open? '<table style="margin-bottom:0.8em;" cellpadding="4" cellspacing="0" border="0" width="100%"><tr><td style="border:1px solid #000000;">' : '</td></tr></table>';
			default:	return $open? '<table style="margin-bottom:0.8em;" cellpadding="4" cellspacing="0" border="0" width="100%"><tr><td class="wikibox">' : '</td></tr></table>';
		}
	}
}
