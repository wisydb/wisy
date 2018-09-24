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
		
		if(preg_match('#^\/\w+\/([^\/]+)#', $_SERVER['REQUEST_URI'], $temp)) {
			$ortsname = trim(utf8_decode(urldecode($temp[1])));
			
			if($ortsname != '') {

				// TODO
				
			} else {

			}
			
		} else {

		}
		
		echo $this->framework->getPrologue(array(
											'title'		=>	$title,
											'bodyClass'	=>	'wisyp_search wisyp_landingpage_orte',
										));
		echo $this->framework->getSearchField();
		flush();
		
		echo '<h1>Landingpage ' . $ortsname . '</h1>';
		
		$queryString = $queryString = $this->framework->Q;
		$offset = htmlspecialchars(intval($_GET['offset'])); if( $offset < 0 ) $offset = 0;
		$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
		$searchRenderer = createWisyObject('WISY_SEARCH_RENDERER_CLASS', $this->framework);
		// TODO
		//$searchRenderer->renderKursliste($searcher, $queryString, $offset);

		
		echo $this->framework->replacePlaceholders( $this->framework->iniRead('spalten.below', '') );

		echo $this->framework->getEpilogue();
	}
}
