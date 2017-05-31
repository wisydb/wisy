<?php if( !defined('IN_WISY') ) die('!IN_WISY');



require_once('admin/wiki2html8.inc.php');
require_once('admin/classes.inc.php');



class WISY_ANBIETER_RENDERER_CLASS
{
	var $framework;
	var $unsecureOnly = false;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
	}
	
	protected function trimLength($str, $max_length)
	{
		if( substr($str, 0, 7)=='http://' )
			$str = substr($str, 7);
		return shortenurl($str, $max_length);
	}

	public function createMailtoLink($adr, $kursId=0) // called from search renderer
	{
		// create base
		$link = 'mailto:'.$adr;
		
		// add a fine subject
		$subject = $this->framework->iniRead('mailto.anbieter.subject', '');
		if( $subject == '' )
		{
			global $wisyPortalKurzname;
			$subject = "Anfrage via $wisyPortalKurzname";
		}
		if( $kursId )
		{
			$subject .= ' [Kurs #'.$kursId.']';
		}
		$subject .= ' [Ticket #'.time().']';
		$link .= '?subject='.rawurlencode($subject);
		
		// add cc, if needed
		$cc = $this->framework->iniRead('mailto.anbieter.cc', '');
		if( $cc != '' )
		{
			$link .= '&cc='.rawurlencode($cc);
		}
		
		return $link;
	}
	
	protected function fit_to_rect($orgw, $orgh, $maxw, $maxh, &$scaledw, &$scaledh)
	{
		$scaledw = $orgw;
		$scaledh = $orgh;
		
		if( $scaledw > $maxw ) {
			$scaledh = intval(($scaledh*$maxw) / $scaledw);
			$scaledw = $maxw;
		}

		if( $scaledh > $maxh ) {
			$scaledw = intval(($scaledw*$maxh) / $scaledh);
			$scaledh = $maxh;
		}
	}
	
	public function renderCard(&$db, $anbieterId, $kursId, $param, $steckbrief=false) // called from kurs renderer
	{
		global $wisyPortal;
		global $wisyPortalEinstellungen;
		
		// load anbieter
		$db->query("SELECT * FROM anbieter WHERE id=$anbieterId");
		if( !$db->next_record() || $db->f8('freigeschaltet')!=1 ) {
			return 'Dieser Anbieterdatensatz existiert nicht oder nicht mehr oder ist nicht freigeschaltet.';
		}
		
		$kursId			= intval($kursId);
		$suchname		= $db->f8('suchname');
		$postname		= htmlentities($db->f8('postname'));
		$strasse		= htmlentities($db->f8('strasse'));
		$plz			= htmlentities($db->f8('plz'));
		$ort			= htmlentities($db->f8('ort'));
		$stadtteil		= htmlentities($db->f8('stadtteil'));
		$land			= htmlentities($db->f8('land'));
		$anspr_tel		= htmlentities($db->f8('anspr_tel'));
		$anspr_fax		= htmlentities($db->f8('anspr_fax'));
		$anspr_name		= htmlentities($db->f8('anspr_name'));
		$anspr_email	= htmlentities($db->f8('anspr_email'));
		$anspr_zeit		= htmlentities($db->f8('anspr_zeit'));
		$homepage		= htmlentities($db->f8('homepage'));
		$din_nr			= htmlentities($db->f8('din_nr'));
		$leitung_name   = htmlentities($db->f8('leitung_name'));
		$gruendungsjahr = intval($db->f8('gruendungsjahr'));
		$rechtsform     = intval($db->f8('rechtsform'));
		$pruefsiegel_seit = $db->f8('pruefsiegel_seit');
		
		$ob = new G_BLOB_CLASS($db->f8('logo'));
		$logo_name		= $ob->name;
		$logo_w			= $ob->w;
		$logo_h			= $ob->h;
		
		// Bestandteile der vCard initialisieren
		$vc = array(
			'Adresse'  				=> '',
			'Kontakt'  				=> '',
			'Telefon'  				=> '',
			'Fax' 	   				=> '',
			'Sprechzeiten'			=> '',
			'E-Mail'				=> '',
			'Website'				=> '',
			'Anbieternummer'		=> '',
			'Logo'					=> '',
			'Link'					=> '',
			'Leitung'				=> '',
			'Rechtsform'			=> '',
			'Gegründet'				=> '',
			'Alle Angebote'			=> '',
			'Qualitätszertifikate'	=> ''
		);		
		
		// Name und Adresse
		$vc['Adresse'] .= "\n" . '<div class="wisyr_anbieter_name" itemprop="name">'. ($postname? $postname : htmlentities($suchname)) . '</div>';
		$vc['Adresse'] .= "\n" . '<div class="wisyr_anbieter_adresse" itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">';

		$map_URL = 'http://maps.google.com/?q=' . urlencode($strasse . ', ' . $plz . ' ' . $ort . ', ' . $land);

		if( $strasse )
		{
			if( $steckbrief )
			{
				$vc['Adresse'] .= "\n" . '<div class="wisyr_anbieter_strasse" itemprop="streetAddress"><a href="' . $map_URL . '">' . $strasse . '</a></div>';
			} else {
				$vc['Adresse'] .= "\n" . '<div class="wisyr_anbieter_strasse" itemprop="streetAddress">' . $strasse . '</div>';
			}
		}

		if( $plz || $ort || $stadtteil || $land )
		{
			if( $steckbrief )
			{
				$vc['Adresse'] .= "\n" . '<div class="wisyr_anbieter_ort"><a href="' . $map_URL . '">';
			} else {
				$vc['Adresse'] .= "\n" . '<div class="wisyr_anbieter_ort">';
			}
		}
			
		if( $plz )
			$vc['Adresse'] .= '<span class="wisyr_anbieter_plz" itemprop="postalCode">' . $plz . '</span>';	

		if( $ort )
			$vc['Adresse'] .= ' <span class="wisyr_anbieter_ort" itemprop="addressLocality">' . $ort . '</span>';

		if( $stadtteil ) {
			$vc['Adresse'] .= ($plz||$ort)? '-' : '';
			$vc['Adresse'] .= ' <span class="wisyr_anbieter_stadtteil">' . $stadtteil . '</span>';
		}

		if( $land ) {
			$vc['Adresse'] .= ($plz||$ort||$stadtteil)? ', ' : '';
			$vc['Adresse'] .= ' <span class="wisyr_anbieter_land" itemprop="adressRegion">' . $land . '</span>';
		}
		
		if( $plz || $ort || $stadtteil || $land )
		{
			if( $steckbrief )
			{
				$vc['Adresse'] .= '</a></div>';	
			} else {
				$vc['Adresse'] .= '</div>';	
			}
		}
		
		$vc['Adresse'] .= "\n</div><!-- /.adress -->";
		
		// Telefonnummer
		if( $anspr_tel )
			$vc['Telefon'] .= "\n" . '<div class="wisyr_anbieter_telefon"><a itemprop="telephone" href="tel:' . $anspr_tel . '">'. $anspr_tel . '</a></div>';

		if( $anspr_fax )
			$vc['Fax'] .= "\n" . '<div class="wisyr_anbieter_fax"><span itemprop="faxNumber">'. $anspr_fax . '</span></div>';

		if( $anspr_name )
			$vc['Kontakt'] .= "\n" . '<div class="wisyr_anbieter_ansprechpartner" itemprop="contactPoint" itemscope itemtype="http://schema.org/ContactPoint">' . $anspr_name . '</div>';
		
		if( $anspr_zeit )
			$vc['Sprechzeiten'] .= "\n" . '<div class="wisyr_anbieter_sprechzeiten" itemprop="hoursAvailable">' . $anspr_zeit . '</div>';
			
		$MAX_URL_LEN = 31;
		if( $homepage )
		{
			if( substr($homepage, 0, 5) != 'http:' && substr($homepage, 0, 6) != 'https:' ) {
			 	$homepage = 'http:/'.'/'.$homepage;
			}
			
			$vc['Website'] .= "\n<div class=\"wisyr_anbieter_homepage\" itemprop=\"url\"><a href=\"$homepage\" target=\"_blank\">" . $this->trimLength($homepage, $MAX_URL_LEN). '</a></div>';
		}
		
		/* email*/
		if( $anspr_email )
			$vc['E-Mail'] .= "\n<div class=\"wisyr_anbieter_email\" itemprop=\"email\"><a href=\"".$this->createMailtoLink($anspr_email, $kursId)."\">" . $this->trimLength($anspr_email, $MAX_URL_LEN  ). '</a></div>';
		
		/* Anbieternummer */
		$anbieter_nr = $din_nr? $din_nr : $anbieterId;
		if( $anbieter_nr )
			$vc['Anbieternummer'] .= "\n" . '<div class="wisyr_anbieter_nummer">'.$anbieter_nr.'</div>';

		/* logo */
		if( $param['logo'] )
		{
			if( $logo_w && $logo_h && $logo_name != '' )
			{
				$vc['Logo'] = "\n" . '<div class="wisyr_anbieter_logo">';
				$this->fit_to_rect($logo_w, $logo_h, 128, 64, $logo_w, $logo_h);
				$vc['Logo'] .= "<span itemprop=\"logo\"><img src=\"{$wisyPortal}admin/media.php/logo/anbieter/$anbieterId/".urlencode($logo_name)."\" style=\"width: ".$logo_w."px; height: ".$logo_h."px;\" alt=\"Anbieter Logo\" title=\"\" id=\"anbieterlogo\"/></span>";
				$vc['Logo'] .= '</div>';
			}
		}
		
		/* Link zum Anbieter */
		$vc['Link'] .= "\n" . '<div class="wisyr_anbieter_link">';
		$vc['Link'] .= '<a href="/'.$this->framework->getUrl('a', array('id'=>$anbieterId)).'">';
		$vc['Link'] .= 'Details zum Anbieter</a>';
		$vc['Link'] .= '</div>';
		
		/* Leitung */
		if( $leitung_name )
		{
			$vc['Leitung'] = $leitung_name;
		}

		/* Rechtsform */
		if( $rechtsform > 0 )
		{
			require_once('admin/config/codes.inc.php'); // needed for $codes_rechtsform
			$codes_array = explode('###', $GLOBALS['codes_rechtsform']);
			for( $c = 0; $c < sizeof($codes_array); $c += 2 ) {
				if( $codes_array[$c] == $rechtsform ) {
					$vc['Rechtsform'] = utf8_encode($codes_array[$c+1]);
					break;
				}
			}
		}

		/* Gruendungsjahr */
		if( $gruendungsjahr > 0 )
		{
			$vc['Gegründet'] = intval($gruendungsjahr);
		}
		
		/* Alle Angebote */
		$this->tagsuggestorObj =& createWisyObject('WISY_TAGSUGGESTOR_CLASS', $this->framework); 
		$tag_suchname = $this->tagsuggestorObj->keyword2tagName($suchname);
		$this->tag_suchname_id = $this->tagsuggestorObj->getTagId(utf8_decode($tag_suchname));
		$freq = $this->tagsuggestorObj->getTagFreq(array($this->tag_suchname_id)); if( $freq <= 0 ) $freq = '';
		$vc['Alle Angebote'] = '<a class="wisy_showalloffers" href="' .$this->framework->getUrl('search', array('q' => $suchname)). '">Alle ' . $freq . ' Angebote des Anbieters</a>';
		
		/* Qualitätszertifikate */
		$seals = $this->renderSealsOverview($anbieterId, $pruefsiegel_seit, true);			
		if( $seals )
		{
			$vc['Qualitätszertifikate'] .= $seals;
		}
		
		$ret = '';
		if($steckbrief) {
			// Steckbrief: vCard als Definition List ausgeben
			$ret .= '<dl>';
			foreach($vc as $key => $value) {
				if(trim($value) != '' &&
							$key != 'Logo' &&
							$key != 'Alle Angebote' &&
							$key != 'Link' &&
							$key != 'Anbieternummer' &&
							$key != 'Fax' &&
							$key != 'Leitung' &&
							$key != 'Rechtsform') {
					$ret .= '<dt>' . $key . '</dt><dd>' . $value . '</dd>';
				}
			}
			$ret .= '</dl>';
		} else {
			foreach($vc as $key => $value) {
				if(trim($value) != '' && 
							$key != 'Anbieternummer' &&
							$key != 'Qualitätszertifikate' &&
							$key != 'Fax' &&
							$key != 'Leitung' &&
							$key != 'Rechtsform' &&
							$key != 'Gegründet' &&
							$key != 'Qualitätszertifikate' &&
							$key != 'Website' &&
							$key != 'Logo') {
					$ret .= $value;
				}
			}
		}
		
		return $ret;
	}
	
	protected function getOffersOverviewPart($sql, $tag_type_bits, $addparam)
	{
		$html = '';
		$db = new DB_Admin();
		$db->query(str_replace('__BITS__', $tag_type_bits, $sql));
		while( $db->next_record() )
		{
			$tag_id = $db->f8('tag_id');
			if( is_array($addparam['filter_tag_ids']) && !in_array($tag_id, $addparam['filter_tag_ids']) ) {
				continue;
			}
			
			$tag_name = $db->f('tag_name');
			$tag_type = $db->f8('tag_type');
			$tag_descr = $db->f8('tag_descr');
			$tag_help = $db->f8('tag_help');
			
			$freq = $this->tagsuggestorObj->getTagFreq(array($this->tag_suchname_id, $tag_id));
			
			$html .= $html==''? '' : '<br />';
			$html .= $this->searchRenderer->formatItem($tag_name, $tag_descr, $tag_type, $tag_help, $freq, $addparam);

			
		}
		return $html;
	}
	
	protected function writeOffersOverview($anbieter_id, $tag_suchname)
	{

		$this->searchRenderer = createWisyObject('WISY_SEARCH_RENDERER_CLASS', $this->framework);
		
		// get SQL query to read all current offers
		$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);		
		$searcher->prepare($tag_suchname);
		if( !$searcher->ok() ) { echo 'Fehler: Anbieter nicht gefunden.'; return; } // error - offerer not found
		$sql = $searcher->getKurseRecordsSql('kurse.id');
		
		// create SQL query to get all unique keywords
		$sql = "SELECT DISTINCT k.tag_id, t.tag_name, t.tag_type, t.tag_descr, t.tag_help 
		                   FROM x_kurse_tags k 
		              LEFT JOIN x_tags t ON k.tag_id=t.tag_id 
		                  WHERE k.kurs_id IN($sql) AND t.tag_type&__BITS__
		               ORDER BY t.tag_name";

		// render ...
		$html = $this->getOffersOverviewPart($sql, 1 // Abschluesse
												, array('hidetagtypestr'=>1, 'qprefix'=>"$tag_suchname, "));
		if( $html )
		{
			echo '<h3>Abschl&uuml;sse - aktuelle Angebote</h2>';
			echo '<p>';
				echo $html;
			echo '<p>';
		}
		
		$html = $this->getOffersOverviewPart($sql, 65536 // Zertifikate
												, array('hidetagtypestr'=>1, 'qprefix'=>"$tag_suchname, "));
		if( $html )
		{
			echo '<h3>Zertifikate - aktuelle Angebote</h3>';
			echo '<p>';
				echo $html;
			echo '<p>';
		}
		
		// besondere Kursarten - diese Liste enthält nur eine Auswahl von Stichworten, definiert von einer Liste von Stichwort-IDs
		// (die zunächst in Tag-IDs konvertiert werden müssen)

		$db = new DB_Admin;
		$db->query("SELECT stichwort FROM stichwoerter WHERE id IN (16311,2827,2826,16851,3207,1,6013,7721,7720,810701,810691,810681,810671,810661,810611,810641,810651,806441,5469,1472)");
		$temp = ''; while( $db->next_record() ) { $temp .= ($temp==''?'':', ') . $db->quote($db->f8('stichwort')); }
		$filter_tag_ids = array();
		if( sizeof($temp) ) {
			$db->query("SELECT tag_id FROM x_tags WHERE tag_name IN(".$temp.")");
			while( $db->next_record() ) { $filter_tag_ids[] = $db->f8('tag_id'); }
		}

		
		
		$html = $this->getOffersOverviewPart($sql, 0x0000FFFF	// alles, ausser Sachstichworten (0, implizit ausgeschlossen) und ausser
												& ~1 			// Abschluesse
												& ~4			// Qualitaetszertifikate (werden rechts als Bild dargestellt)
												& ~8			// Zielgruppe (2014-12-21 18:32 ausgeschlossen)
												& ~16			// Abschlussart (2014-12-21 18:32 ausgeschlossen)
												& ~32 & ~64		// Synonyme
												& ~128			// Thema
												& ~256			// Anbieter (ist natuerlich immer derselbe)
												& ~512			// Ort
												& 65536			// Zertifikate
												, array('showtagtype'=>1, 'qprefix'=>"$tag_suchname, ", 'filter_tag_ids'=>$filter_tag_ids));

		if( $html )
		{
			echo "\n" . '<div class="wisy_besondere_kursarten">';
			echo '<h2>Besondere Kursarten - aktuelle Angebote</h2>';
			echo '<p>';
				echo $html;
			echo '</p>';
			echo "\n</div><!-- /.wisy_besondere_kursarten -->\n\n";
		}

		if( $this->framework->getEditAnbieterId() == $anbieter_id )
		{
			echo "\n" . '<div class="wisy_edittoolbar">';
				echo '<p>Hinweis f&uuml;r den Anbieter:</p><p>Die Werte werden ca. <b>einmal t&auml;glich</b> neu berechnet.</p>';
			echo '</div>';
		}
	} 
	
	
	protected function renderSealsOverview($anbieter_id, $pruefsiegel_seit, $steckbrief=false)
	{
		$img_seals = '';
		$txt_seals = '';
		$seals_steckbrief = '';

		$seit = intval(substr($pruefsiegel_seit, 0, 4));
		$seit = $seit>1900? "Gepr&uuml;fte Weiterbildungseinrichtung seit $seit" : "Gepr&uuml;fte Weiterbildungseinrichtung";
		
		// get all seals

		$db = new DB_Admin;
		$db->query("SELECT a.attr_id AS sealId, s.stichwort AS title, s.glossar AS glossarId FROM anbieter_stichwort a, stichwoerter s WHERE a.primary_id=" . $anbieter_id . " AND a.attr_id=s.id AND s.eigenschaften=" .DEF_STICHWORTTYP_QZERTIFIKAT. " ORDER BY a.structure_pos;");
		while( $db->next_record() )
		{
			$sealId = $db->f8('sealId');
			$glossarId = $db->f8('glossarId');
			$glossarLink = $glossarId>0? (' <a href="' . $this->framework->getHelpUrl($glossarId) . '" class="wisy_help" title="Hilfe">i</a>') : '';
			$title = $db->f8('title');

			$img = "files/seals/$sealId-large.gif";
			if( @file_exists($img) )
			{
				$img_seals .= $img_seals==''? '' : '<br /><br />';
				$img_seals .= "<img src=\"$img\" border=\"0\" alt=\"Pr&uuml;siegel\" title=\"$title\" /><br />";
				$img_seals .= $title . $glossarLink;
				if( $seit ) { $img_seals .= '<br />'  . $seit; $seit = ''; }
				
				$seals_steckbrief .= "<img src=\"$img\" border=\"0\" alt=\"Pr&uuml;siegel\" title=\"$title\" />";
			}
			else
			{
				$txt_seals .= $txt_seals==''? '' : '<br />';
				$txt_seals .= $title . $glossarLink;
			}
						
		}
		
		if($steckbrief) return $seals_steckbrief;
	
		$ret = $img_seals;

		if( $txt_seals!= '' ) {
			$ret .= '<br />' . $txt_seals;
		}
		
		return $ret;
	}
	
	
	protected function renderMap($anbieter_id)
	{
		$map =& createWisyObject('WISY_OPENSTREETMAP_CLASS', $this->framework); // die Karte zeigt auch Orte abgelaufener Angebote, man koennte das Aendern, aber ob das Probleme loest oder neue aufwirft ist unklar ... ;-)
		
		$unique = array();
		$db = new DB_Admin();
		$sql = "SELECT * 
		          FROM durchfuehrung 
		         WHERE id IN(SELECT secondary_id FROM kurse_durchfuehrung WHERE primary_id IN(SELECT id FROM kurse WHERE anbieter=".intval($anbieter_id)."))";
		$db->query($sql);
		while( $db->next_record() ) {
			$record = $db->Record;
			$unique_id = $record['strasse'].'-'.$record['plz'].'-'.$record['ort'].'-'.$record['land'];
			if( !isset($unique_adr[$unique_id]) ) {
				$unique_adr[$unique_id] = $record;
			}
		}
		
		if(!is_array($unique_adr))
			return;

		foreach( $unique_adr as $unique_id=>$record ) {
			$map->addPoint2($record, 0);
		}
		
		echo $map->render();
	}
	
	public function render()
	{
		$anbieter_id = intval($_GET['id']);

		$db = new DB_Admin();

		// link to another anbieter?
		$db->query("SELECT attr_id FROM anbieter_verweis WHERE primary_id=$anbieter_id ORDER BY structure_pos");
		if( $db->next_record() ) {
			$anbieter_id = intval($db->f8('attr_id'));
		}

		// load anbieter
		$db->query("SELECT * FROM anbieter WHERE id=$anbieter_id");
		if( !$db->next_record() || $db->f8('freigeschaltet')!=1 ) {
			$this->framework->error404(); // record does not exist/is not active, report a normal 404 error, not a "Soft 404", see  http://goo.gl/IKMnm -- fuer nicht-freigeschaltete Datensaetze, s. [here]
		}
		$din_nr			= htmlentities($db->f8('din_nr'));
		$suchname		= $db->f8('suchname');
		$typ            = intval($db->f8('typ'));
		$firmenportraet	= trim($db->f8('firmenportraet'));
		$date_created	= $db->f8('date_created');
		$date_modified	= $db->f8('date_modified');
		//$stichwoerter	= $this->framework->loadStichwoerter($db, 'anbieter', $anbieter_id);
		$vollst			= $db->f8('vollstaendigkeit');
		$anbieter_settings = explodeSettings($db->f8('settings'));
		
		$ob = new G_BLOB_CLASS($db->f8('logo'));
		$logo_name		= $ob->name;
		$logo_w			= $ob->w;
		$logo_h			= $ob->h;
		
		// promoted?
		if( intval($_GET['promoted']) > 0 )
		{
			$promoter =& createWisyObject('WISY_PROMOTE_CLASS', $this->framework);
			$promoter->logPromotedRecordClick(intval($_GET['promoted']), $anbieter_id);
		}
		
		// page out
		headerDoCache();

		$bodyClass = 'wisyp_anbieter';
		if( $typ == 2 ) 
		{
			$bodyClass .= ' wisyp_anbieter_beratungsstelle';
		}
		
		echo $this->framework->getPrologue(array(	
													'title'		=>	$suchname,  
													'canonical'	=>	$this->framework->getUrl('a', array('id'=>$anbieter_id)),
													'bodyClass'	=>	$bodyClass,
											));
		echo $this->framework->getSearchField();

		$this->tagsuggestorObj =& createWisyObject('WISY_TAGSUGGESTOR_CLASS', $this->framework); 
		$tag_suchname = $this->tagsuggestorObj->keyword2tagName($suchname);
		$this->tag_suchname_id = $this->tagsuggestorObj->getTagId(utf8_decode($tag_suchname));
		
		echo "\n\n" . '<div id="wisy_resultarea" class="'.$this->framework->getAllowFeedbackClass().'">';
		
		echo '<p class="noprint"><a class="wisyr_zurueck" href="javascript:history.back();">&laquo; Zur&uuml;ck</a></p>';
		
		echo '<div class="wisyr_anbieter_kopf">';
		echo "\n\n" . '<h1 class="wisyr_anbietertitel">';
			if( $typ == 2 ) echo '<span class="wisy_icon_beratungsstelle">Beratungsstelle<span class="dp">:</span></span> ';
			echo htmlentities($suchname);
		echo '</h1>';
		
		
		if( $logo_w && $logo_h && $logo_name != '' )
		{
			echo "\n" . '<div class="wisyr_anbieter_logo">';
			$this->fit_to_rect($logo_w, $logo_h, 128, 64, $logo_w, $logo_h);
			echo "<div class=\"logo\"><img src=\"{$wisyPortal}admin/media.php/logo/anbieter/$anbieter_id/".urlencode($logo_name)."\" style=\"width: ".$logo_w."px; height: ".$logo_h."px;\" alt=\"Anbieter Logo\" title=\"\" id=\"anbieterlogo\"/></div>";
			echo '</div>';
		}
		echo '</div><!-- /#wisyr_anbieter_kopf -->';
		
		flush();
		
		echo "\n\n" . '<section class="wisyr_anbieterinfos clearfix">';
		echo "\n" . '<article class="wisyr_anbieter_firmenportraet wisy_anbieter_inhalt" data-tabtitle="Über">' . "\n";
		echo '<h1>Über den Anbieter</h1>';

		// firmenportraet
		if( $firmenportraet != '' ) {
			$wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this->framework);
			echo $wiki2html->run($firmenportraet);
		}
		
		echo "\n</article><!-- /.wisyr_anbieter_firmenportraet -->\n\n";
		echo "\n" . '<article class="wisyr_anbieter_steckbrief" data-tabtitle="Kontakt">' . "\n";
		
		echo "\n" . '<div class="wisy_steckbrief clearfix">';
			echo '<div class="wisy_steckbriefcontent" itemscope itemtype="http://schema.org/Organization">';
				echo $this->renderCard($db, $anbieter_id, 0, array(), true);
			echo '</div>';
		echo "\n</div><!-- /.wisy_steckbrief -->\n";
		
		echo "\n</article><!-- /.wisyr_anbieter_steckbrief -->\n\n";
		
		echo "\n\n" . '<article class="wisy_anbieter_kursangebot" data-tabtitle="Kurse"><h1>Kurs&shy;angebot</h1>' . "\n";
		
		// link "show all offers"
		$freq = $this->tagsuggestorObj->getTagFreq(array($this->tag_suchname_id)); if( $freq <= 0 ) $freq = '';
		echo '<h2>'.$freq.($freq==1? ' aktuelles Angebot' : ' aktuelle Angebote').'</h2>'
		.	'<p>'
		.		'<a class="wisyr_anbieter_kurselink" href="' .$this->framework->getUrl('search', array('q'=>$tag_suchname)). '">'
		.			"Alle $freq Kurse des Anbieters"
		.		'</a>'
		. 	'</p>';		

		// current offers overview
		$this->writeOffersOverview($anbieter_id, $tag_suchname);
				
		echo "\n</article><!-- /.wisy_anbieter_kursangebot -->\n\n";
		
		echo "\n</section><!-- /.wisyr_anbieterinfos -->\n\n";
		

		echo "\n" . '<footer class="wisy_anbieter_footer">';
			echo "\n" . '<div class="wisyr_anbieter_meta">';
				echo ' Anbieterinformation erstellt am ' . $this->framework->formatDatum($date_created);
				echo ', zuletzt ge&auml;ndert am ' . $this->framework->formatDatum($date_modified);
				echo ', ' . $vollst . '% Vollständigkeit';
				echo '<div class="wisyr_vollst_info"><span class="info">Hinweise zur förmlichen Vollständigkeit der Kursinfos sagen nichts aus über die Qualität der Kurse selbst. <a href="' . $this->framework->getHelpUrl(3369) . '">Mehr erfahren</a></span></div>';
				
				$copyrightClass =& createWisyObject('WISY_COPYRIGHT_CLASS', $this->framework);
				$copyrightClass->renderCopyright($db, 'anbieter', $anbieter_id);
			echo "\n</div><!-- /.wisyr_anbieter_meta -->\n\n";
			
			echo "\n" . '<div class="wisyr_anbieter_edit">';
				if( $this->framework->getEditAnbieterId() == $anbieter_id )
				{
					echo '<br /><div class="wisy_edittoolbar">';
						if( $vollst >= 1 ) {
							echo '<p>Hinweis für den Anbieter:</p><p>Die <b>Vollst&auml;ndigkeit</b> Ihrer '.$freq.' aktuellen Angebote liegt durchschnittlich bei <b>'.$vollst.'%</b> ';
				
							$min_vollst = intval($anbieter_settings['vollstaendigkeit.min']);
							$max_vollst = intval($anbieter_settings['vollstaendigkeit.max']);
							if( $min_vollst >= 1 && $max_vollst >= 1 ) {
								echo ' im <b>Bereich von ' . $min_vollst .'-'.$max_vollst . '%</b>';
							}
							echo '.';
						}
						echo ' Um die Vollst&auml;ndigkeit zu erh&ouml;hen klicken Sie oben links auf &quot;alle Kurse&quot; und bearbeiten 
						 Sie die Angebote, v.a. die mit den schlechteren Vollst&auml;ndigkeiten.</p>';
						echo '<p>Die Vollst&auml;ndigkeiten werden ca. einmal t&auml;glich berechnet; ab 50% Vollst&auml;ndigkeit werden entspr. Logos an dieser Stelle eingeblendet.</p>';
					echo '</div>';
				}
			echo "\n</div><!-- /.wisyr_anbieter_edit -->\n\n";
		echo "\n</footer><!-- /.wisy_anbieter_footer -->\n\n";
		
		echo "\n</div><!-- /#wisy_resultarea -->";
		
		echo $this->framework->getEpilogue();
	}
};
