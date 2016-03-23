<?php if( !defined('IN_WISY') ) die('!IN_WISY');



class WISY_AUTOSUGGESTPLZORT_RENDERER_CLASS
{
	var $framework;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
		$this->plzfilterObj = createWisyObject('WISY_PLZFILTER_CLASS', $this->framework, array(
			'durchf.plz.allow' => $this->framework->iniRead('durchf.plz.allow', ''),
			'durchf.plz.deny'  => $this->framework->iniRead('durchf.plz.deny',  ''),
			'durchf.plz.order' => $this->framework->iniRead('durchf.plz.order', '')
		));
	}

	private function combinePlz($plz1, $plz2)
	{
		// function takes a PLZ-Range and combines it to a readable string
		if( substr($plz1,0,4) == substr($plz2,0,4) ) {
			return substr($plz1,0,4) . '?';
		}
		else if( substr($plz1,0,3) == substr($plz2,0,3) ) {
			return substr($plz1,0,3) . '??';
		}
		else {
			return $plz1.'-'.$plz2;
		}
	}

	function render()
	{
		$querystring = utf8_decode($_GET["q"]);
		$db = new DB_Admin;
		
		// collect all PLZ/Ort into an array as ort=>array(plz1, plz2, ...)
		$orte = array();
		$db->query("SELECT plz, ort FROM plztool2 WHERE plz LIKE ".$db->quote($querystring.'%')." OR ort LIKE ".$db->quote($querystring.'%'));
		while( $db->next_record() ) {
			$plz = $db->f('plz');
			$ort = $db->f('ort');
			if( $this->plzfilterObj->is_valid_plz($plz) ) {
				if( isset($orte[$ort]) ) {
					$orte[$ort][] = $plz;
				}
				else {
					$orte[$ort] = array($plz);
				}
			}
		}

		// convert to array as plz=>ort
		$make_unique = 1;
		$tags = array();
		foreach( $orte as $ort=>$plzArr )
		{
			$plzStr = $plzArr[0];
			if( sizeof($plzArr) > 1 ) {
				sort($plzArr);
				$plzStr = $this->combinePlz($plzArr[0], $plzArr[sizeof($plzArr)-1]);
			}
			
			$tags[$plzStr.'/'.$make_unique] = utf8_encode($plzStr) . '|' . utf8_encode($ort); // add a unique string to the plz to allow multiple ORTs with the same PLZs
			$make_unique++;
		}

		ksort($tags);

		// render as simple text, one tag per line, used by the site's AutoSuggest
		if( SEARCH_CACHE_ITEM_LIFETIME_SECONDS > 0 )
			headerDoCache(SEARCH_CACHE_ITEM_LIFETIME_SECONDS);
					
		foreach( $tags as $key=>$value )
		{
			echo $value . "\n";
		}
	}
}



