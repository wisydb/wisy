<?php if( !defined('IN_WISY') ) die('!IN_WISY');

class WISY_LANDINGPAGE_RENDERER_CLASS 
{
	var $framework;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
	}
	function render() {
		
		echo $this->framework->getPrologue(array(
			'title'		=>	$title,
			'bodyClass'	=>	'wisyp_search wisyp_landingpage_orte',
		));
		echo $this->framework->getSearchField();
		flush();
		
		$db = new DB_Admin;
		
		if(preg_match('#^\/\w+\/([^\/]+)#', $_SERVER['REQUEST_URI'], $temp)) {
			$ortsname = trim(utf8_decode(urldecode($temp[1])));
			
			if($ortsname != '') {

				$db->query("SELECT ort FROM plz_ortscron WHERE ort LIKE ".$db->quote($ortsname). " LIMIT 1");
				
				$ortsname = '';
				while( $db->next_record() ) {
					$ortsname = $db->f8('ort');
				}
			}
		}
		
		if($ortsname == '') {
			// List of all "orte" / "orte" + "stadtteile"
			$plz_allow = str_replace(',empty', '', $this->framework->iniRead('durchf.plz.allow', ''));
			$plz_deny = str_replace(',empty', '', $this->framework->iniRead('durchf.plz.deny', ''));
			
			// TODO: Optional "stadtteil"
		
			// Alle Orte finden die:
			//	1. Via PLZ in diesem Portal erlaubt sind
			//	2. In plz_ortscron vorkommen (also der offiziellen Schreibweise entsprechen)
			//	3. In durchfuehrungen genutzt werden die:
			//		1. Nicht abgelaufen sind
			//		2. Einem freigeschalteten Kurs angehören -> 1 oder 4	
			$select = "SELECT ort FROM durchfuehrung LEFT JOIN kurse_durchfuehrung ON durchfuehrung.id=kurse_durchfuehrung.secondary_id LEFT JOIN kurse ON kurse.id=kurse_durchfuehrung.primary_id";
			$select .= " WHERE (kurse.freigeschaltet = 1 OR kurse.freigeschaltet = 4)";
			$select .= " AND durchfuehrung.ort IN";
				$select .= " (SELECT ort FROM durchfuehrung WHERE ort != '' AND (ende = '0000-00-00 00:00:00' OR ende <= '". strftime("%Y-%m-%d 00:00:00") . "')";
				if(strlen(trim($plz_allow))) $select .= " AND plz IN (" . $plz_allow . ")";
				if(strlen(trim($plz_deny))) $select .= " AND plz NOT IN (" . $plz_deny . ")";
				$select .= " GROUP BY ort)";
			$select .= " GROUP BY ort";
			
			$db->query($select);
			
			echo '<h1>Landingpage für ' . $db->ResultNumRows . ' Orte</h1>';
			
			// TODO: HTML vervollständigen
			while( $db->next_record() ) {
				echo '<li><a href="/orte/' . urlencode($db->f8('ort')) . '/">' . $db->f8('ort') . '</a></li>';
			}
			
			echo '<ul>';
			echo '</ul>';
			
		} else {
			// Kursliste for given "ort"
			echo '<h1>Landingpage für Ort ' . htmlspecialchars($ortsname) . '</h1>';
		
			$queryString = $ortsname;
			$offset = htmlspecialchars(intval($_GET['offset'])); if( $offset < 0 ) $offset = 0;
			$urlbase = $_SERVER['REQUEST_URI'];
		
			$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
			$searcher->prepare(mysql_real_escape_string($queryString));
		
			$searchRenderer = createWisyObject('WISY_SEARCH_RENDERER_CLASS', $this->framework);
			$searchRenderer->renderKursliste($searcher, $queryString, $offset, $urlbase);
		}
		
		echo $this->framework->replacePlaceholders( $this->framework->iniRead('spalten.below', '') );
		echo $this->framework->getEpilogue();
	}
}
