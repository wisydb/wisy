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
		
		$this->domain = $_SERVER['HTTP_HOST'];
		$protocol = $this->framework->iniRead('portal.https', '') ? "https" : "http";
		$this->absPath = $protocol.':/' . '/' . $this->domain . '/';
	}
	
	/*
	 * Landingpage Renderer aufrufen anhand des Typs ($param['type']) der von der Framework Class uebergeben wird
	 */
	function render() {
		
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
			case 'sitemap-landingpages.xml':
				$this->renderLandingpageSitemap();
				break;
			case 'sitemap-landingpages.xml.gz':
				$this->renderLandingpageSitemapGz();
				break;
		}
		// ! $this->db->close();
	}
	
	/* Orte ---------------------------- */
	
	/*
	 * Landing page fuer Orte
	 * 
	 * Bei Aufruf von /orte/ eine Liste aller Orte die in diesem Portal verwendet werden ausgeben
	 * Bei Aufruf von /orte/[ortsname] eine Liste aller Kurse fuer diesen Ort in diesem Portal ausgeben
	*/
	function renderLandingpageOrte() {
		
		// Ort und Stadtteil aus request URI extrahieren
		$urlparts = $this->getUrlParts();
		if($urlparts) {
			$ortsname = trim(utf8_decode(urldecode($urlparts[1])));
			$stadtteilname = trim(urldecode($urlparts[2]));
			
			if($ortsname != '') {

				$this->db->query("SELECT ort FROM plz_ortscron WHERE ort LIKE ".$this->db->quote($ortsname). " LIMIT 1");
				
				$ortsname = '';
				while( $this->db->next_record() ) {
					$ortsname = $this->db->fcs8('ort');
				}
				$this->db->free();
			}
		}
		
		if($ortsname != '') {
			
			// Kursliste fuer einen bestimmten Ort
			$title = 'Alle Kurse in ' . $ortsname;
			if(trim($stadtteilname) != '') {
				$title .= ' - ' . $stadtteilname;
			}
			echo $this->framework->getPrologue(array(
				'title'		=>	$title,
				'bodyClass'	=>	'wisyp_search wisyp_landingpage_' . $this->type,
			));
			echo '<div id="wisy_resultarea" class="wisy_landingpage">';
			flush();
			$this->renderOrtsDetail($ortsname, $stadtteilname);
			echo '</div><!-- /#wisy_resultarea -->';
			
		} else {
			
			// Liste aller Orte
			echo $this->framework->getPrologue(array(
				'title'		=>	'Alle Orte',
				'bodyClass'	=>	'wisyp_search wisyp_landingpage_' . $this->type,
			));
			echo '<div id="wisy_resultarea" class="wisy_landingpage">';
			$sql = $this->getOrtslisteSql();
			$this->renderOrtsliste($sql);
			echo '</div><!-- /#wisy_resultarea -->';
		}
		echo $this->framework->replacePlaceholders( $this->framework->iniRead('spalten.below', '') );
		echo $this->framework->getEpilogue();
	}
	
	/*
	 * Liste aller Orte ausgeben
	 */
	function renderOrtsliste($sql) {
		$this->db->query($sql);
		
		$this->renderListtitle();
		echo '<section class="wisyr_landingpage_list wisyr_landingpage_list' . $this->type . '"><ul>';
		while( $this->db->next_record() ) {
			$this->renderOrt($this->db->fcs8('ort'), $this->db->fcs8('stadtteil'));
		}
		$this->db->free();
		echo '</ul></section>';	
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
	 * Details fuer einzelnen Ort ausgeben
	 * Kursliste
	 */
	function renderOrtsDetail($ortsname, $stadtteilname) {
		
		$title = $ortsname;
		$querystring = $ortsname;
		if(trim($stadtteilname) != '') {
			$title .= '-' . $stadtteilname;
			$querystring .= ',' . $stadtteilname;
		}
	
		$offset = intval( $this->framework->getParam('offset') ); if( $offset < 0 ) $offset = 0;
		
		// $baseurl is being used in link in renderKursliste, link may contain quotes for XSS
		$baseurl = $this->framework->sanitizeLinkHref( $_SERVER['REQUEST_URI'] );

		$this->renderDetailtitle($title);
	
		$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
		$searcher->prepare($this->framework->mysql_escape_mimic($querystring));
	
		$searchRenderer = createWisyObject('WISY_SEARCH_RENDERER_CLASS', $this->framework);
		$searchRenderer->renderKursliste($searcher, $querystring, $offset, false, $baseurl);
	}
	
	/*
	 * SQL fuer Abfrage der Ortsliste zusammenbauen
	 * Optional mit Beruecksichtigung der stichworte fuer "Themen & Orte"
	 *
	 * Alle Orte finden, die:
	 *	* Via stdkursfilter in diesem Portal erlaubt sind
	 *	* Via PLZ in diesem Portal erlaubt sind
	 *	* In plz_ortscron vorkommen (also der offiziellen Schreibweise entsprechen)
	 *	* In durchfuehrungen genutzt werden die:
	 *		* Nicht abgelaufen sind
	 *		* Einem freigeschalteten Kurs angehoeren -> 1 oder 4
	 *
	 * Falls ein Array mit Stichworten uebergeben wird, werden nur Orte gefunden
	 * wo Kurse mit diesen Stichworten stattfinden
	 *
	 */
	private function getOrtslisteSql($stichworte=array()) {
		$portal_tag_id = $this->getPortaltagId();
		
		// Allowed / denied PLZ fuer Portal
		$plz_allow = str_replace(',empty', '', $this->framework->iniRead('durchf.plz.allow', ''));
		$plz_deny = str_replace(',empty', '', $this->framework->iniRead('durchf.plz.deny', ''));
				
		// Nach Themen filtern?
		if(count($stichworte)) {
			$select_fields = 'stichwoerter.stichwort, stichwoerter.stichwort_sorted, durchfuehrung.ort';
			$select_fields_outer = 'df.stichwort, df.stichwort_sorted, df.ort';
		} else {
			$select_fields = 'durchfuehrung.ort';
			$select_fields_outer = 'df.ort';
		}
		
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
		}
		if(count($stichworte)) {
			$select .= " LEFT JOIN kurse_stichwort ON kurse_stichwort.primary_id=kurse.id";
			$select .= " LEFT JOIN stichwoerter ON stichwoerter.id=kurse_stichwort.attr_id";
		}
		$select .= " AND (kurse.freigeschaltet = 1 OR kurse.freigeschaltet = 4)";
		$select .= " AND durchfuehrung.ort != ''";
		$select .= " AND (durchfuehrung.ende = '0000-00-00 00:00:00' OR durchfuehrung.ende >= '". ftime("%Y-%m-%d 00:00:00") . "')";
		if(strlen(trim($plz_allow))) $select .= " AND durchfuehrung.plz IN (" . $plz_allow . ")";
		if(strlen(trim($plz_deny))) $select .= " AND durchfuehrung.plz NOT IN (" . $plz_deny . ")";
		if(strlen(trim($portal_tag_id))) $select .= " WHERE x_kurse_tags.tag_id=$portal_tag_id";
		if(count($stichworte)) $select .= " AND stichwoerter.id IN(" . implode(',', $stichworte) . ")";
		$select .= " GROUP BY $select_fields";
		
		// Filtere Orte anhand plz_ortscron
		$select = "SELECT $select_fields_outer FROM ($select) AS df INNER JOIN plz_ortscron ON df.ort=plz_ortscron.ort GROUP BY $select_fields_outer";
		
		return $select;
	}
	
	
	/* Themen & Orte ---------------------------- */
	
	/*
	 * Landing page fuer Themen & Orte
	 *
	 */
	function renderLandingpageThemen() {
		
		// Stichwort, Ort und Stadtteil aus request URI extrahieren
		$urlparts = $this->getUrlParts();
		if($urlparts) {
			$thema_und_ortsname = trim(utf8_decode(urldecode($urlparts[1])));
			$stadtteilname = trim(urldecode($urlparts[2]));

			$thema = '';
			$ortsname = '';
			if($thema_und_ortsname != '') {
				$parts = explode('-in-', $thema_und_ortsname);
				if(count($parts) == 2) {
					$thema = $parts[0];
					$ortsname = $parts[1];
				}
			}
			
			if($thema != '') {
				$this->db->query("SELECT stichwort FROM stichwoerter WHERE stichwort_sorted LIKE ".$this->db->quote($thema). " LIMIT 1");
				
				$thema = '';
				while( $this->db->next_record() ) {
					$thema = $this->db->fcs8('stichwort');
				}
				$this->db->free();
			}
			
			if($ortsname != '') {

				$this->db->query("SELECT ort FROM plz_ortscron WHERE ort LIKE ".$this->db->quote($ortsname). " LIMIT 1");
				
				$ortsname = '';
				while( $this->db->next_record() ) {
					$ortsname = $this->db->fcs8('ort');
				}
				$this->db->free();
			}
		}
		if($thema != '' && $ortsname != '') {
			// Details eines einzelnen Themas in Ort
			$title = $thema . ' in ' . $ortsname;
			if($stadtteilname != '') $title .= '-' . $stadtteilname;
			echo $this->framework->getPrologue(array(
				'title'	=> $title,
				'bodyClass' => 'wisyp_search wisyp_landingpage_' . $this->type,
			));
			echo '<div id="wisy_resultarea" class="wisy_landingpage">';
			$this->renderThemaDetail($thema, $ortsname, $stadtteilname);
			echo '</div><!-- /#wisy_resultarea -->';
			
		} else {
			// Liste aller Themen in Orten
			if(trim($this->framework->iniRead('seo.themen.stichworte')) == '') {
				$this->framework->error404();
			} else {
			
				echo $this->framework->getPrologue(array(
					'title'		=>	'Alle Themen & Orte',
					'bodyClass'	=>	'wisyp_search wisyp_landingpage_' . $this->type,
				));
				echo '<div id="wisy_resultarea" class="wisy_landingpage">';
			
				$stichworte = $this->quotedArrayFromList(trim($this->framework->iniRead('seo.themen.stichworte')));
				if(count($stichworte)) {
					$sql = $this->getOrtslisteSql($stichworte);
					$this->renderThemenliste($sql);
				}
			
				echo '</div><!-- /#wisy_resultarea -->';
				echo $this->framework->replacePlaceholders( $this->framework->iniRead('spalten.below', '') );
				echo $this->framework->getEpilogue();
			}
		}
	}
	
	/*
	 * Liste aller Themen an Orten ausgeben
	 */
	function renderThemenliste($sql) {
		$this->db->query($sql);
		
		$this->renderListtitle();
		
		echo '<section class="wisyr_landingpage_list wisyr_landingpage_list' . $this->type . '">';
		echo '<ul>';
		$themaort = '';
		while( $this->db->next_record() ) {
		    // Fuer Orte mit Stadtteil zusätzlich einen Eintrag ohne Stadtteil ausgeben
			if($this->db->fcs8('stichwort_sorted') != NULL && $this->db->fcs8('stichwort') != NULL) {
				if($this->db->fcs8('stichwort_sorted') . $this->db->fcs8('ort') != $themaort) {
					$themaort = $this->db->fcs8('stichwort_sorted') . $this->db->fcs8('ort');
					if(trim($this->db->fcs8('stadtteil')) != '') {
						$this->renderThema($this->db->fcs8('stichwort'), $this->db->fcs8('stichwort_sorted'), $this->db->fcs8('ort'));
					}
				}
				$this->renderThema($this->db->fcs8('stichwort'), $this->db->fcs8('stichwort_sorted'), $this->db->fcs8('ort'), $this->db->fcs8('stadtteil'));
			}
		}
		echo '</ul>';
		echo '</section>';
		$this->db->free();	
	}
	
	/*
	 * Einzelnes Thema an Ort fuer Liste ausgeben
	 */
	function renderThema($stichwort, $stichwort_sorted, $ort, $stadtteil='') {
		if(trim($stadtteil) != '') {
			echo '<li class="wisyr_landingpage_list_thema_ort_stadtteil"><a href="/themen/' . urlencode($stichwort_sorted) . '-in-' . urlencode($ort) . '/' . urlencode($stadtteil) . '/">' . $stichwort . ' in ' . $ort . '-' . $stadtteil . '</a></li>';
		} else {
			echo '<li class="wisyr_landingpage_list_thema_ort"><a href="/themen/' . urlencode($stichwort_sorted) . '-in-' . urlencode($ort) . '/">' . $stichwort . ' in ' . $ort . '</a></li>';
		}
	}
	
	/*
	 * Detail fuer Thema an Ort ausgeben
	 * -> Kursliste
	 */
	function renderThemaDetail($thema, $ortsname, $stadtteilname) {
		$title = $thema . ' in ' . $ortsname;
		$querystring = $thema . ',' . $ortsname;
		if(trim($stadtteilname) != '') {
			$title .= '-' . $stadtteilname;
			$querystring .= ',' . $stadtteilname;
		}
	
		$offset = intval( $this->framework->getParam('offset') ); if( $offset < 0 ) $offset = 0;
		$baseurl = $this->framework->sanitizeLinkHref( $_SERVER['REQUEST_URI'] ) ;
		
		$this->renderDetailtitle($title);
	
		$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
		$searcher->prepare($this->framework->mysql_escape_mimic($querystring));
	
		$searchRenderer = createWisyObject('WISY_SEARCH_RENDERER_CLASS', $this->framework);
		$searchRenderer->renderKursliste($searcher, $querystring, $offset, false, $baseurl);
	}
	
	/*
	 * Kommaseparierte Liste in Array umwandeln,
	 * dabei db->quote() darauf anwenden
	 */
	private function quotedArrayFromList($input) {
		$output = array();
		foreach(explode(',', $input) as $i) {
			if(trim($i) != '') {
				$output[] = $this->db->quote(trim($i));
			}
		}
		return $output;
	}
	
	/* Abschluesse ---------------------------- */
	
	/*
	 * Landing page fuer Abschluesse
	 */
	function renderLandingpageAbschluesse() {
	    
		// Abschluss aus request URI extrahieren
		$urlparts = $this->getUrlParts();
		if($urlparts) {
			$abschluss = trim(utf8_decode(urldecode($urlparts[1])));
			
			if($abschluss != '') {

				$this->db->query("SELECT stichwort, glossar FROM stichwoerter WHERE stichwort_sorted LIKE ".$this->db->quote($abschluss). " AND eigenschaften = 1 LIMIT 1");
				
				$abschluss = '';
				$glossar = '';
				while( $this->db->next_record() ) {
					$abschluss = $this->db->fcs8('stichwort');
					$glossar = $this->db->fcs8('glossar');
				}
				$this->db->free();
			}
		}
		
		if($abschluss != '') {
			// Details eines einzelnen Abschlusses
			echo $this->framework->getPrologue(array(
				'title'	=> 'Abschluß ' . $abschluss,
				'bodyClass' => 'wisyp_search wisyp_landingpage_' . $this->type,
			));
			echo '<div id="wisy_resultarea" class="wisy_landingpage">';
			$this->renderAbschlussDetail($abschluss, $glossar);
			echo '</div><!-- /#wisy_resultarea -->';
			
		} else {
			
		    // Liste aller Abschluesse
			echo $this->framework->getPrologue(array(
				'title'	=>	'Alle Abschl&uuml;sse',
				'bodyClass' => 'wisyp_search wisyp_landingpage_' . $this->type,
			));
			echo '<div id="wisy_resultarea" class="wisy_landingpage">';
			$sql = $this->getAbschlusslisteSql();
			$this->renderAbschlussliste($sql);
			echo '</div><!-- /#wisy_resultarea -->';
		}
		echo $this->framework->replacePlaceholders( $this->framework->iniRead('spalten.below', '') );
		echo $this->framework->getEpilogue();
	}
	
	/*
	 * Einzelnen Abschluss ausgeben mit, soweit vorhanden, Glossarseite und Kursliste
	 */
	function renderAbschlussDetail($abschluss, $glossar) {
		
		$title = $abschluss;
		$querystring = $abschluss;
		
		$this->renderDetailtitle($title);
		
		// Glossarseite
		if($glossar > 0) {
			$glossarRenderer = createWisyObject('WISY_GLOSSAR_RENDERER_CLASS', $this->framework);
			$glossareintrag = $glossarRenderer->getGlossareintrag($glossar);
			$glossarRenderer->renderGlossareintrag($glossar, $glossareintrag);
		}
		
		// Kursliste
		$offset = intval( $this->framework->getParam('offset') ); if( $offset < 0 ) $offset = 0;
		$baseurl = $this->framework->sanitizeLinkHref( $_SERVER['REQUEST_URI'] );
	
		$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
		$searcher->prepare($this->framework->mysql_escape_mimic($querystring));
	
		$searchRenderer = createWisyObject('WISY_SEARCH_RENDERER_CLASS', $this->framework);
		$searchRenderer->renderKursliste($searcher, $querystring, $offset, true, $baseurl);
	}
	
	/*
	 * Liste aller Abschlüsse ausgeben
	 */
	function renderAbschlussliste($sql) {
		$this->db->query($sql);
		
		$this->renderListtitle();
		
		echo '<section class="wisyr_landingpage_list wisyr_landingpage_list' . $this->type . '">';
		echo '<ul>';
		while( $this->db->next_record() ) {
			$this->renderAbschluss($this->db->fcs8('stichwort'), $this->db->fcs8('stichwort_sorted'));
		}
		echo '</ul>';
		echo '</section>';
		$this->db->free();
	}
	
	/*
	 * Einzelnen Abschluss fuer Liste ausgeben
	 */
	function renderAbschluss($stichwort, $stichwort_sorted) {
		echo '<li class="wisyr_landingpage_list_abschluss"><a href="/abschluesse/' . urlencode($stichwort_sorted) . '/">' . $stichwort . '</a></li>';
	}
	
	/*
	 * SQL fuer Abschlussliste zusammenstellen
	 *
	 * Alle Stichworte finden die:
	 *	* Die Abschluesse sind (eigenschaften=1)
	 *	* Kursen in diesem Portal zugeordnet sind
	 *	* Via stdkursfilter in diesem Portal erlaubt sind
	 */
	private function getAbschlusslisteSql() {
		$portal_tag_id = $this->getPortaltagId();
		
		$select = "SELECT stichwort, stichwort_sorted FROM stichwoerter";
		$select .= " LEFT JOIN kurse_stichwort ON stichwoerter.id=kurse_stichwort.attr_id";
		if(strlen(trim($portal_tag_id))) {
			$select .= " LEFT JOIN x_kurse_tags ON kurse_stichwort.primary_id=x_kurse_tags.kurs_id";
			$select .= " WHERE x_kurse_tags.tag_id=$portal_tag_id";
		} else {
			$select .= " WHERE 1=1";
		}
		$select .= " AND eigenschaften=1";
		$select .= " GROUP BY stichwort";
		
		return $select;
	}
	
	/* Sitemap ---------------------- */
	
	function addUrl($url, $lastmod, $changefreq)
	{
		$this->urlsAdded ++;
		return "<url><loc>{$this->absPath}$url</loc><lastmod>" .ftime("%Y-%m-%d", $lastmod). "</lastmod><changefreq>$changefreq</changefreq></url>\n";
	}

	function createSitemapXml(&$sitemap /*by reference to save some MB*/)
	{
		// sitemap start
		$sitemap =  "<" . "?xml version=\"1.0\" encoding=\"UTF-8\" ?" . ">\n";
		$sitemap .= "<urlset xmlns=\"https:/" . "/www.sitemaps.org/schemas/sitemap/0.9\">\n";
		$this->urlsAdded = 0;
		$maxUrls = 25000;
		
		// Orte
		$sitemap .= "<!-- Orte -->\n";
		$sitemap .= $this->addUrl('orte/', time(), 'daily');
		$this->db->query($this->getOrtslisteSql());
		while( $this->db->next_record() ) {
			if($this->urlsAdded >= $maxUrls) {
				$sitemap .= "<!-- stop adding URLs, max of $maxUrls reached -->\n";
				break;
			}
			
			$url = 'orte/' . urlencode($this->db->fcs8('ort')) . '/';
			$stadtteil = $this->db->fcs8('stadtteil');
			if(trim($stadtteil) != '') $url .= urlencode($stadtteil) . '/';
			
			$sitemap .= $this->addUrl($url, time(), 'weekly');
		}
		$this->db->free();
		
		// Abschluesse
		$sitemap .= "<!-- Abschluesse -->\n";
		$sitemap .= $this->addUrl('abschluesse/', time(), 'daily');
		$this->db->query($this->getAbschlusslisteSql());
		while( $this->db->next_record() ) {
			if($this->urlsAdded >= $maxUrls) {
				$sitemap .= "<!-- stop adding URLs, max of $maxUrls reached -->\n";
				break;
			}
			
			$url = 'abschluesse/' . urlencode($this->db->fcs8('stichwort_sorted')) . '/';
			$sitemap .= $this->addUrl($url, time(), 'weekly');
		}
		$this->db->free();
		
		// Themen
		if(trim($this->framework->iniRead('seo.themen.stichworte')) != '')
		{
			$stichworte = $this->quotedArrayFromList(trim($this->framework->iniRead('seo.themen.stichworte')));
			if(count($stichworte)) {
			
				$sitemap .= "<!-- Themen -->\n";
				$sitemap .= $this->addUrl('themen/', time(), 'daily');
				$this->db->query($this->getOrtslisteSql($stichworte));
				$themaort = '';
				while( $this->db->next_record() ) {
					if($this->urlsAdded >= $maxUrls) {
						$sitemap .= "<!-- stop adding URLs, max of $maxUrls reached -->\n";
						break;
					}
					$stichwort_sorted = $this->db->fcs8('stichwort_sorted');
					$ort = $this->db->fcs8('ort');
					$stadtteil = $this->db->fcs8('stadtteil');
					// Fuer Orte mit Stadtteil zusaetzlich einen Eintrag ohne Stadtteil ausgeben
					if($stichwort_sorted . $ort != $themaort) {
						$themaort = $stichwort_sorted . $ort;
						if(trim($stadtteil) != '') {
							$url = 'themen/' . urlencode($stichwort_sorted) . '-in-' . urlencode($ort) . '/';
							$sitemap .= $this->addUrl($url, time(), 'weekly');
						}
					}
					$url = 'themen/' . urlencode($stichwort_sorted) . '-in-' . urlencode($ort) . '/';
					if(trim($stadtteil) != '') $url .= urlencode($stadtteil) . '/';
					$sitemap .= $this->addUrl($url, time(), 'weekly');
				}
				$this->db->free();
			}
		}

		// sitemap end
		$sitemap .= "<!-- $this->urlsAdded URLs added -->\n";
		$sitemap .= "<!-- timestamp: ".ftime("%Y-%m-%d %H:%M:%S")." -->\n";
		$sitemap .= "</urlset>\n";
	}
	
	function renderLandingpageSitemap()
	{
		header('Content-Type: text/xml');
		headerDoCache();

		$sitemap = '';
		$this->createSitemapXml($sitemap);

		echo $sitemap;
	}
	
	function renderLandingpageSitemapGz()
	{
	    header('content-type: application/x-gzip');
	    header('Content-disposition: attachment; filename="sitemap-landingpage.xml.gz"');
		headerDoCache();
		
		$this->createSitemapXml($temp);
		$sitemap_gz = gzencode($temp, 9);
		$temp = ''; // free *lots* of data

		echo $sitemap_gz;
	}
	
	/* Hilfsfunktionen ---------------------- */
	
	/*
	 * URL mittels REGEX in Bestandteile zerlegen entlang slashes "/"
	 */
	private function getUrlParts() {
		preg_match('/^\/?[^\/]+\/?([^\/]+)\/?([^\/]*)/', $_SERVER['REQUEST_URI'], $temp);
		return $temp;
	}
	
	/*
	 * ID des Portaltags aus Datenbank holen
	 */
	private function getPortaltagId() {
		global $wisyPortalId;
		$portal_tag_id = '';
		$this->db->query("SELECT tag_id, tag_type FROM x_tags WHERE tag_name='" . addslashes(".portal$wisyPortalId") . "'");
		if( $this->db->next_record() ) {
			$portal_tag_id = $this->db->fcs8('tag_id');
		}
		$this->db->free();
		return $portal_tag_id;
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
			    echo '<h1 class="wisyr_landingpage_list_title">Alle Abschl&uuml;sse</h1>';
				break;
		}
	}
	
	/*
	 * Titel der Detailseite ausgeben
	 */
	private function renderDetailtitle($detail) {
		switch($this->type) {
			case 'orte':
				echo '<h1 class="wisyr_landingpage_detail_title">Alle Kurse in ' . htmlspecialchars($detail) . '</h1>';
				break;
			case 'themen':
			    echo '<h1 class="wisyr_landingpage_detail_title">Alle Kurse f&uuml;r ' . htmlspecialchars($detail) . '</h1>';
			    break;
			case 'abschluesse':
			    echo '<h1 class="wisyr_landingpage_detail_title">Abschlu&szlig; ' . htmlspecialchars($detail) . '</h1>';
			    break;
		}
	}
}
