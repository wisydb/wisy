<?php if( !defined('IN_WISY') ) die('!IN_WISY');



require_once("admin/wiki2html.inc.php");



class WISY_WIKI2HTML_CLASS extends WIKI2HTML_CLASS
{
	function __construct(&$framework, $addparam)
	{
		$this->db = new DB_Admin;
		$this->framework =& $framework;
		if( !is_array($addparam) ) { $addparam = array(); }
		$this->selfGlossarId = intval($addparam['selfGlossarId']); // may be 0 if the page is not a glossar entry
		parent::__construct();
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
			$ob =& createWisyObject('WISY_KEYWORDTABLE_CLASS', $this->framework, array('args'=>$param, 'selfGlossarId'=>$this->selfGlossarId));
			$ret = $ob->getHtml();
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
			return	"<a href=\"$href\" target=\"_blank\"><i>".str_replace("/)", "/", $text)."</i></a>";
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
