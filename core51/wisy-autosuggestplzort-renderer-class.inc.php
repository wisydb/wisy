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

	function render()
	{
		$querystring = utf8_decode($_GET["q"]);
		$db = new DB_Admin;
		
		// collect all PLZ/Ort into an array as ort=>array(plz1, plz2, ...)
		$plzorte = array();
		$orte = array();
		$startsWithNumber = $this->startsWithNumber(trim($querystring));
		$db->query("SELECT plz, ort FROM plz_ortscron WHERE plz LIKE ".$db->quote($querystring.'%')." OR ort LIKE ".$db->quote($querystring.'%'));
		while( $db->next_record() ) {
			$plz = $db->f8('plz');
			$ort = $db->f8('ort');
			
            // Filtern nach PLZ die in diesem Portal erlaubt sind
			if( $this->plzfilterObj->is_valid_plz($plz) ) {
                
    			// Nur Ort
    			if(!$startsWithNumber)
    			{
    				if( !isset($orte[urlencode($ort)]) ) {
    					$orte[urlencode($ort)] = $ort;
    				}
    			}
                
                // PLZ und Ort
				if( !isset($plzorte[urlencode($plz . $ort)]) ) {
					$plzorte[urlencode($plz . $ort)] = $plz . ' ' . $ort;
				}
			}
		}
		ksort($orte);
		ksort($plzorte);

		// render as simple text, one tag per line, used by the site's AutoSuggest
		if( SEARCH_CACHE_ITEM_LIFETIME_SECONDS > 0 )
			headerDoCache(SEARCH_CACHE_ITEM_LIFETIME_SECONDS);
			
		if(count((array) $plzorte) == 0 && count((array) $orte) == 0)
		{
			echo 'Keine Ortsvorschläge möglich| ' . "\n";
		}
		else
		{

			foreach( $orte as $key=>$value )
			{
				echo $value . "|" . $value . "\n";
			}
			if(count((array) $orte) && count((array) $plzorte))
			{
				echo "headline|PLZ\n";
			}
			
			foreach( $plzorte as $key=>$value )
			{
				echo $value . "|" . $value . "\n";
			}
		}
	}
	
	function startsWithNumber($string) {
		return strlen($string) > 0 && ctype_digit(substr($string, 0, 1));
	}
}



