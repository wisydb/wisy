<?php if( !defined('IN_WISY') ) die('!IN_WISY');



require_once('admin/wiki2html8.inc.php');
require_once('admin/classes.inc.php');



loadWisyClass('WISY_ANBIETER_RENDERER_CLASS');



class WISY_ANBIETER_NEW_RENDERER_CLASS extends WISY_ANBIETER_RENDERER_CLASS
{
	var $framework;
	var $unsecureOnly = true;

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

	
	public function renderCard(&$db, $anbieterId, $kursId, $param) // called from kurs renderer
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
		$postname		= $db->f8('postname');
		$strasse		= $db->f8('strasse');
		$plz			= $db->f8('plz');
		$ort			= $db->f8('ort');
		$stadtteil		= $db->f8('stadtteil');
		$land			= $db->f8('land');
		$anspr_tel		= $db->f8('anspr_tel');
		$anspr_fax		= $db->f8('anspr_fax');
		$anspr_name		= $db->f8('anspr_name');
		$anspr_email	= $db->f8('anspr_email');
		$anspr_zeit		= $db->f8('anspr_zeit');
		$homepage		= $db->f8('homepage');
		
		$ob = new G_BLOB_CLASS($db->f8('logo'));
		$logo_name		= $ob->name;
		$logo_w			= $ob->w;
		$logo_h			= $ob->h;
		
		// do what to do ...
		$ret .= '<div class="wisyr_anbieter_name" itemprop="name">'. htmlentities($postname? $postname : $suchname) . '</div>'
		$ret .= '<div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">';

		if( $strasse )
			$ret .= '<div class="wisyr_anbieter_strasse" itemprop="streetAddress">' . htmlentities($strasse) . '</div>';

		if( $plz || $ort || $stadtteil || $land )
			$ret .= '<div class="wisyr_anbieter_ort">';
			
		if( $plz )
			$ret .= '<span class="wisyr_anbieter_plz" itemprop="postalCode">' . htmlentities($plz) . '</span>';	

		if( $ort )
			$ret .= ' <span class="wisyr_anbieter_ort" itemprop="addressLocality">' . htmlentities($ort) . '</span>';

		if( $stadtteil ) {
			$ret .= ($plz||$ort)? '-' : '';
			$ret .= ' <span class="wisyr_anbieter_stadtteil">' . htmlentities($stadtteil) . '</span>';
		}

		if( $land ) {
			$ret .= ($plz||$ort||$stadtteil)? ', ' : '';
			$ret .= ' <span class="wisyr_anbieter_land" itemprop="adressRegion">' . htmlentities($land) . '</span>';
		}
		
		if( $plz || $ort || $stadtteil || $land )
			$ret .= '</div>';	
		
		$ret .= '</div>';

		if( $anspr_tel )
			$ret .= '<div class="wisyr_anbieter_telefon">Tel:&nbsp;<span itemprop="telephone">'.htmlentities($anspr_tel) . '</span></div>';

		if( $anspr_fax )
			$ret .= '<div class="wisyr_anbieter_fax">Fax:&nbsp;<span itemprop="faxNumber">'.htmlentities($anspr_fax) . '</span></div>';

		if( $anspr_name || $anspr_zeit )
		{
			$ret .= '<div class="wisyr_anbieter_ansprechpartner" itemprop="contactPoint" itemscope itemtype="http://schema.org/ContactPoint">';
				if( $anspr_name )
					$ret .= '<span>Kontakt: ' . htmlentities($anspr_name) . '</span>';
				if( $anspr_zeit )
				{
					$ret .= $anspr_name? ', ' : '';
					$ret .= '<span itemprop="hoursAvailable">' . htmlentities($anspr_zeit) . '</span>';
				}
			$ret .= '</div>';
		}
			
		$MAX_URL_LEN = 31;
		if( $homepage )
		{
			if( substr($homepage, 0, 5) != 'http:' && substr($homepage, 0, 6) != 'https:' ) {
			 	$homepage = 'http:/'.'/'.$homepage;
			}
			
			$ret .= "<div class=\"wisyr_anbieter_homepage\" itemprop=\"url\"><a href=\"$homepage\" target=\"_blank\">" .htmlentities($this->trimLength($homepage, $MAX_URL_LEN)). '</a></div>';
		}
		
		/* email*/
		if( $anspr_email )
			$ret .= "<div class=\"wisyr_anbieter_email\" itemprop=\"email\"><a href=\"".$this->createMailtoLink($anspr_email, $kursId)."\">" .htmlentities($this->trimLength($anspr_email, $MAX_URL_LEN  )). '</a></div>';
		
		/* logo */
		if( $param['logo'] )
		{
			$ret .= '<div class="wisyr_anbieter_logo">';
			
			if( $param['logoLinkToAnbieter'] )
				$ret .= '<a href="'.$this->framework->getUrl('a', array('id'=>$anbieterId)).'">';
			
			if( $logo_w && $logo_h && $logo_name != '' )
			{
				$this->fit_to_rect($logo_w, $logo_h, 128, 64, $logo_w, $logo_h);
				$ret .= "<span itemprop=\"logo\"><img src=\"{$wisyPortal}admin/media.php/logo/anbieter/$anbieterId/".urlencode($logo_name)."\" style=\"width: ".$logo_w."px; height: ".$logo_h."px;\" alt=\"Anbieter Logo\" title=\"\" id=\"anbieterlogo\"/></span>";
			}
			
			if( $param['logoLinkToAnbieter'] )
				$ret .= 'Anbieterdetails anzeigen...</a>';
			
			$ret .= '</div>';
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
			$html .= $html==''? '' : '<br />';
		
			$tag_id = $db->f8('tag_id');
			$tag_name = $db->f8('tag_name');
			$tag_type = $db->f8('tag_type');
			$tag_descr = $db->f8('tag_descr');
			$tag_help = $db->f8('tag_help');
			
			$freq = $this->tagsuggestorObj->getTagFreq(array($this->tag_suchname_id, $tag_id));
			
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
		if( !$searcher->ok() ) { echo 'WTF';return; } // error - offerer not found
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
			echo '<h1>Abschl&uuml;sse - aktuelle Angebote</h1>';
			echo '<p>';
				echo $html;
			echo '<p>';
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
												, array('showtagtype'=>1, 'qprefix'=>"$tag_suchname, "));
		if( $html )
		{
			echo '<div class="wisy_besondere_kursarten">';
			echo '<h1>Besondere Kursarten - aktuelle Angebote</h1>';
			echo '<p>';
				echo $html;
			echo '</p>';
			echo '</div>';
		}

		if( $this->framework->getEditAnbieterId() == $anbieter_id )
		{
			echo '<div class="wisy_edittoolbar">';
				echo '<p>Hinweis f&uuml;r den Anbieter:</p><p>Die Werte werden ca. <b>einmal t&auml;glich</b> neu berechnet.</p>';
			echo '</div>';
		}
	} 
	
	
	protected function renderSealsOverview($anbieter_id, $pruefsiegel_seit)
	{
		$img_seals = '';
		$txt_seals = '';

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
			}
			else
			{
				$txt_seals .= $txt_seals==''? '' : '<br />';
				$txt_seals .= $title . $glossarLink;
			}
						
		}
	
		$ret = $img_seals;

		if( $txt_seals!= '' ) {
			$ret .= '<br />' . $txt_seals;
		}

		return $ret;



	
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
		$din_nr			= $db->f8('din_nr');
		$suchname		= $db->f8('suchname');
		$typ            = intval($db->f8('typ'));
		$firmenportraet	= trim($db->f8('firmenportraet'));
		$date_created	= $db->f8('date_created');
		$date_modified	= $db->f8('date_modified');
		//$stichwoerter	= $this->framework->loadStichwoerter($db, 'anbieter', $anbieter_id);
		$vollst			= $db->f8('vollstaendigkeit');
		$anbieter_settings = explodeSettings($db->f8('settings'));
		$pruefsiegel_seit = $db->f8('pruefsiegel_seit');
		
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
		$this->tag_suchname_id = $this->tagsuggestorObj->getTagId($tag_suchname);

		// start the result area
		// --------------------------------------------------------------------
		
		echo '<div id="wisy_resultarea"><div id="wisy_resultcol1">';
		
		echo '<p class="noprint">';
			echo '<a href="javascript:history.back();">&laquo; Zur&uuml;ck</a>';
		echo '</p>';
		echo '<h1>';
			if( $typ == 2 ) echo '<span class="wisy_icon_beratungsstelle">Beratungsstelle<span class="dp">:</span></span> ';
			echo htmlentities($suchname);
		echo '</h1>';
		flush();

		if( $firmenportraet != '' ) {
			$wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this->framework);
			echo $wiki2html->run($firmenportraet);
		}

		// link "show all offers"
		$freq = $this->tagsuggestorObj->getTagFreq(array($this->tag_suchname_id)); if( $freq <= 0 ) $freq = '';
		echo '<h1>'.$freq.($freq==1? ' aktuelles Angebot' : ' aktuelle Angebote').'</h1>'
		.	'<p>'
		.		'<a href="' .$this->framework->getUrl('search', array('q'=>$tag_suchname)). '">'
		.			"Zeige alle aktuellen Angebote des Anbieters..."
		.		'</a>'
		. 	'</p>';		

		// current offers overview


		$this->writeOffersOverview($anbieter_id, $tag_suchname);
					
		echo '<p class="wisy_anbieter_footer '.$this->framework->getAllowFeedbackClass().'">';
		 // no content, but must be present as the feedback stuff is created here via JavaScript
		echo '</p>';		

		// break the result area: start second column!
		// --------------------------------------------------------------------
		
		echo '</div><div id="wisy_resultcol2">';
		
		echo '<div class="wisy_vcard">';
			echo '<div class="wisy_vcardtitle">Anbieteradresse</div>';
			echo '<div class="wisy_vcardcontent" itemscope itemtype="http://schema.org/Organization">';
				echo $this->renderCard($db, $anbieter_id, 0, array('logo'=>true, 'logoLinkToAnbieter'=>false));
			echo '</div>';
		echo '</div>';

		// vollst.
		$title = '';
		if( $vollst >= 50 && $this->framework->iniRead('details.complseal', 1) )
		{
			if( $vollst >= 90 ) {
				$img = "core20/img/compl90.png";
				$title = 'Die Kursbeschreibungen dieses Anbieters &uuml;bertreffen die WISY-Kriterien zur Vollst&auml;ndigkeit der Kursdaten';
			}
			else {
				$img = "core20/img/compl50.png";
				$title = 'Die Kursbeschreibungen dieses Anbieters erf&uuml;llen die WISY-Kriterien zur Vollst&auml;ndigkeit der Kursdaten';
			}
		}
		if( $title )
		{
			echo '<table><tr><td>';
				echo '<img src="'.$img.'" alt="" width="55" height="55" title="" />';
			echo '</td><td style="word-break: normal !important;">'; // <- this is a hack, the hamburg CSS is really out of order ...
				echo $title;
				echo '&nbsp;<a href="' . $this->framework->getHelpUrl(3369) . '" class="wisy_help" title="Hilfe">i</a>';
			echo '</td></tr></table>';
		}
		
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
		
		// seals
		$seals = $this->renderSealsOverview($anbieter_id, $pruefsiegel_seit);			
		if( $seals )
		{
			echo '<div class="wisy_vcard">';
				echo '<div class="wisy_vcardtitle">Qualit&auml;tsmerkmale</div>';
				echo '<div class="wisy_vcardcontent">';
					echo '<div style="text-align:center;">';
						echo $seals;
					echo '</div>';
				echo '</div>';
			echo '</div>';		
		}			


		
		// map
		$this->renderMap($anbieter_id);
		
		// meta			
		echo '<div class="wisy_vcard">';
			$anbieter_nr = $din_nr? htmlentities($din_nr) : $anbieter_id;
			echo '<div class="wisy_vcardtitle">Anbieternummer: '.$anbieter_nr.'</div>';
			echo '<div class="wisy_vcardcontent">';
				$fmt_created = $this->framework->formatDatum($date_created);
				$fmt_modified = $this->framework->formatDatum($date_modified);
				echo 'Erstellt:&nbsp;' . ($fmt_created? $fmt_created : 'k.A.') . ', Ge&auml;ndert:&nbsp;' . ($fmt_modified? $fmt_modified : 'k.A.');
			echo '</div>';
		echo '</div>';
								

							
		// end the result area
		// --------------------------------------------------------------------
		
		
		echo '</div></div>';

		
		
		$copyrightClass =& createWisyObject('WISY_COPYRIGHT_CLASS', $this->framework);
		$copyrightClass->renderCopyright($db, 'anbieter', $anbieter_id);
		
		echo $this->framework->getEpilogue();
	}
};



registerWisyClass('WISY_ANBIETER_NEW_RENDERER_CLASS');

