<?php if( !defined('IN_WISY') ) die('!IN_WISY');

class WISY_LANDINGPAGE_RENDERER_CLASS 
{
	var $framework;
	var $type;
	var $db;

	function __construct(&$framework, $param)
	{
		// constructor
		$this->framework =& $framework;
		$this->type = $param['type'];
		$this->db = new DB_Admin;
	}
	
	/*
	 * Landingpage Renderer aufrufen anhand des Typs ($param['type']) der von der Framework Class übergeben wird
	 */
	function render() {
		
		echo $this->framework->getPrologue(array(
			'title'		=>	$title,
			'bodyClass'	=>	'wisyp_search wisyp_landingpage_' . $this->type,
		));
		flush();
		
		switch( $this->type ) {
			case 'orte':
				$this->renderLandingpageOrte();
				break;
			case 'themen':
				$this->renderLandingpageThemen();
				break;
			case 'abschluesse':
				$this->renderLandingpageAbschluesse();
				break;
		}
		
		echo $this->framework->replacePlaceholders( $this->framework->iniRead('spalten.below', '') );
		echo $this->framework->getEpilogue();
	}
	
	/*
	 * Landing page für Orte
	 * 
	 * Bei Aufruf von /orte/ eine Liste aller Orte die in diesem Portal verwendet werden ausgeben
	 * Bei Aufruf von /orte/[ortsname] eine Liste aller Kurse für diesen Ort in diesem Portal ausgeben
	*/
	function renderLandingpageOrte() {
		
		// Ort und Stadtteil aus request URI extrahieren
		if(preg_match('/^\/?[^\/]+\/?([^\/]+)\/?([^\/]*)/', $_SERVER['REQUEST_URI'], $temp)) {
			$ortsname = trim(utf8_decode(urldecode($temp[1])));
			
			if($ortsname != '') {

				$this->db->query("SELECT ort FROM plz_ortscron WHERE ort LIKE ".$this->db->quote($ortsname). " LIMIT 1");
				
				$ortsname = '';
				while( $this->db->next_record() ) {
					$ortsname = $this->db->f8('ort');
				}
				$this->db->free();
			}
			
			$stadtteilname = trim(urldecode($temp[2]));
		}
		
		if($ortsname != '') {
			
			// Kursliste für einen bestimmten Ort
			$this->renderOrtsKursliste($ortsname, $stadtteilname);
			
		} else {
			
			// Liste aller Orte
			$sql = $this->getOrtslisteSql($ortsname, $stadtteilname);
			$this->renderOrtsliste($sql);		
		}
	}
	
	/*
	 * Landing page für Themen & Orte
	 *
	 *
	 */
	function renderLandingpageThemen() {
		echo $this->framework->getPrologue(array(
			'title'		=>	$title,
			'bodyClass'	=>	'wisyp_search wisyp_landingpage_themen',
		));
		flush();
		
		echo 'TODO Themen';
		
		echo $this->framework->replacePlaceholders( $this->framework->iniRead('spalten.below', '') );
		echo $this->framework->getEpilogue();
	}
	
	/*
	 * Landing page für Abschlüsse
	 */
	function renderLandingpageAbschluesse() {
		echo $this->framework->getPrologue(array(
			'title'		=>	$title,
			'bodyClass'	=>	'wisyp_search wisyp_landingpage_abschluesse',
		));
		flush();
		
		echo 'TODO Abschluesse';
		
		echo $this->framework->replacePlaceholders( $this->framework->iniRead('spalten.below', '') );
		echo $this->framework->getEpilogue();
	}
	
	/*
	 * Liste aller Orte ausgeben
	 */
	function renderOrtsliste($sql) {
		$this->db->query($sql);
		
		$this->renderListtitle();
		
		echo '<section class="wisyr_landingpage_list wisyr_landingpage_list' . $this->type . '">';
		echo '<ul>';
		while( $this->db->next_record() ) {
			$this->renderOrt($this->db->f8('ort'), $this->db->f8('stadtteil'));
		}
		echo '</ul>';
		echo '</section>';
		$this->db->free();	
	}
		
	/*
	 * Kursliste für einzelnen Ort ausgeben
	 */
	function renderOrtsKursliste($ortsname, $stadtteilname) {
		
		$title = $ortsname;
		$querystring = $ortsname;
		if(trim($stadtteilname) != '') {
			$title .= ' - ' . $stadtteilname;
			$querystring .= ',' . $stadtteilname;
		}
	
		$offset = htmlspecialchars(intval($_GET['offset'])); if( $offset < 0 ) $offset = 0;
		$baseurl = $_SERVER['REQUEST_URI'];
		
		$this->renderDetailtitle($title);
	
		$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
		$searcher->prepare(mysql_real_escape_string($querystring));
	
		$searchRenderer = createWisyObject('WISY_SEARCH_RENDERER_CLASS', $this->framework);
		$searchRenderer->renderKursliste($searcher, $querystring, $offset, false, $baseurl);
	}
	
	/*
	 * Einzelnen Ort ausgeben
	 */
	function renderOrt($ort, $stadtteil='') {
		if(trim($stadtteil) != '') {
			echo '<li class="wisyr_landingpage_list_ort_stadtteil"><a href="/orte/' . urlencode($ort) . '/' . urlencode($stadtteil) . '/">' . $ort . '-' . $stadtteil . '</a></li>';
		} else {
			echo '<li class="wisyr_landingpage_list_ort"><a href="/orte/' . urlencode($ort) . '/">' . $ort . '</a></li>';
		}
	}
	
	/*
	 * Titel der Listenseite ausgeben
	 */
	private function renderListtitle() {
		switch($this->type) {
			case 'orte':
				echo '<h1 class="wisyr_landingpage_list_title">Alle Orte</h1>';
				break;
			case 'themen':
				echo '<h1 class="wisyr_landingpage_list_title">Alle Themen & Orte</h1>';
				break;
			case 'abschluesse':
				echo '<h1 class="wisyr_landingpage_list_title">Alle Abschlüsse</h1>';
				break;
		}
	}
	
	/*
	 * Titel der Detailseite ausgeben
	 */
	private function renderDetailtitle($detail) {
		switch($this->type) {
			case 'orte':
				echo '<h1 class="wisyr_landingpage_detail_title">Alle Kurse für Ort ' . htmlspecialchars($detail) . '</h1>';
				break;
			case 'themen':
				echo '<h1 class="wisyr_landingpage_detail_title">Alle Kurse für Thema & Ort ' . htmlspecialchars($detail) . '</h1>';
				break;
			case 'abschluesse':
				echo '<h1 class="wisyr_landingpage_detail_title">Alle Kurse für Abschluß ' . htmlspecialchars($detail) . '</h1>';
				break;
		}
	}
	
	/*
	 * ID des Portaltags aus Datenbank holen
	 */
	private function getPortaltagId() {
		global $wisyPortalId;
		$portal_tag_id = '';
		$this->db->query("SELECT tag_id, tag_type FROM x_tags WHERE tag_name='" . addslashes(".portal$wisyPortalId") . "'");
		if( $this->db->next_record() ) {
			$portal_tag_id = $this->db->f8('tag_id');
		}
		$this->db->free();
		return $portal_tag_id;
	}
	
	/*
	 * SQL für Abfrage der Ortsliste zusammenbauen
	 *
	 * Alle Orte finden, die:
	 *	* Via stdkursfilter in diesem Portal erlaubt sind
	 *	* Via PLZ in diesem Portal erlaubt sind
	 *	* In plz_ortscron vorkommen (also der offiziellen Schreibweise entsprechen)
	 *	* In durchfuehrungen genutzt werden die:
	 *		* Nicht abgelaufen sind
	 *		* Einem freigeschalteten Kurs angehören -> 1 oder 4
	 */
	private function getOrtslisteSql($ortsname, $stadtteilname) {
		$portal_tag_id = $this->getPortaltagId();
		
		// Allowed / denied PLZ für Portal
		$plz_allow = str_replace(',empty', '', $this->framework->iniRead('durchf.plz.allow', ''));
		$plz_deny = str_replace(',empty', '', $this->framework->iniRead('durchf.plz.deny', ''));
		
		$select_fields = 'durchfuehrung.ort';
		$select_fields_outer = 'df.ort';
		
		// Stadtteile anzeigen?
		$stadtteile = false;
		if(intval(trim($this->framework->iniRead('seo.ortsliste.stadtteile'))) == 1)
		{
			$stadtteile = true;
			$select_fields .= ', durchfuehrung.stadtteil';
			$select_fields_outer .= ', df.stadtteil';
		}
				
		// Finde alle Orte im Portal
		$select = "SELECT $select_fields FROM durchfuehrung";
		$select .= " LEFT JOIN kurse_durchfuehrung ON durchfuehrung.id=kurse_durchfuehrung.secondary_id";
		$select .= " LEFT JOIN kurse ON kurse.id=kurse_durchfuehrung.primary_id";
		if(strlen(trim($portal_tag_id))) {
			$select .= " LEFT JOIN x_kurse_tags ON kurse.id=x_kurse_tags.kurs_id";
			$select .= " WHERE x_kurse_tags.tag_id=$portal_tag_id";
		} else {
			$select .= " WHERE 1=1";
		}
		$select .= " AND (kurse.freigeschaltet = 1 OR kurse.freigeschaltet = 4)";
		$select .= " AND durchfuehrung.ort != ''";
		$select .= " AND (durchfuehrung.ende = '0000-00-00 00:00:00' OR durchfuehrung.ende >= '". strftime("%Y-%m-%d 00:00:00") . "')";
		if(strlen(trim($plz_allow))) $select .= " AND durchfuehrung.plz IN (" . $plz_allow . ")";
		if(strlen(trim($plz_deny))) $select .= " AND durchfuehrung.plz NOT IN (" . $plz_deny . ")";
		$select .= " GROUP BY $select_fields";
		
		// Filtere Orte anhand plz_ortscron
		$select = "SELECT $select_fields_outer FROM ($select) AS df INNER JOIN plz_ortscron ON df.ort=plz_ortscron.ort GROUP BY $select_fields_outer";
		
		return $select;
	}
}