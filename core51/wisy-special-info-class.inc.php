<?php if( !defined('IN_WISY') ) die('!IN_WISY');

/******************************************************************************
 WISY 5.0
 ******************************************************************************
 Spezielle Informationen berechnen und cachen.
 Hierzu gehören z.B. die Förderungsmöglichkeiten, die für ein bestimmtes
 Portal tatsächlich zur Verfügung stehen
 ******************************************************************************/



class WISY_SPECIAL_INFO_CLASS
{
	var $framework;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
	}

	function recalcAdvStichw($magic, $flag)
	{
		$ids_str = '';		

		$db = new DB_Admin;
		$db->query("SELECT stichwort, id FROM stichwoerter WHERE eigenschaften=$flag;");
		while( $db->next_record() )
		{
			$stichw = $db->fcs8('stichwort');
			$stichw = trim(strtr($stichw, array(': '=>' '	,	
												':'	=>' '	,
												', '=>' '	,
												','	=>' '		)));
	
			$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);		
			$searcher->prepare("$stichw, Datum:Alles");
			if( $searcher->getKurseCount() )
			{
				$ids_str .= $ids_str == ''? '' : ', ';
				$ids_str .= intval($db->fcs8('id'));
			}
		}
		
		if( $ids_str == '' ) 
			$ids_str = '0';
		
		$this->framework->cacheWrite("adv_stichw.$flag.magic",		$magic);
		$this->framework->cacheWrite("adv_stichw.$flag.ids",		$ids_str);
	}

};
