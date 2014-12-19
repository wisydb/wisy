<?php if( !defined('IN_WISY') ) die('!IN_WISY');



require_once('admin/wiki2html.inc.php');
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
		if( !$db->next_record() || $db->f('freigeschaltet')!=1 ) {
			return 'Dieser Anbieterdatensatz existiert nicht oder nicht mehr oder ist nicht freigeschaltet.';
		}
		
		$kursId			= intval($kursId);
		$suchname		= $db->fs('suchname');
		$postname		= $db->fs('postname');
		$strasse		= $db->fs('strasse');
		$plz			= $db->fs('plz');
		$ort			= $db->fs('ort');
		$stadtteil		= $db->fs('stadtteil');
		$land			= $db->fs('land');
		$anspr_tel		= $db->fs('anspr_tel');
		$anspr_fax		= $db->fs('anspr_fax');
		$anspr_name		= $db->fs('anspr_name');
		$anspr_email	= $db->fs('anspr_email');
		$anspr_zeit		= $db->fs('anspr_zeit');
		$homepage		= $db->fs('homepage');
		
		$ob = new G_BLOB_CLASS($db->fs('logo'));
		$logo_name		= $ob->name;
		$logo_w			= $ob->w;
		$logo_h			= $ob->h;
		
		// do what to do ...
		$ret  = '';
		$ret .= '<i>'. isohtmlentities($postname? $postname : $suchname) . '</i>';

		if( $strasse )
			$ret .= '<br />' . isohtmlentities($strasse);

		if( $plz || $ort )
			$ret .= '<br />' . isohtmlentities($plz) . ' ' . isohtmlentities($ort);

		if( $stadtteil ) {
			$ret .= ($plz||$ort)? '-' : '<br />';
			$ret .= isohtmlentities($stadtteil);
		}

		if( $land ) {
			$ret .= ($plz||$ort||$stadtteil)? ', ' : '<br />';
			$ret .= isohtmlentities($land);
		}

		if( $anspr_tel )
			$ret .= '<br />Tel:&nbsp;'.isohtmlentities($anspr_tel);

		if( $anspr_fax )
			$ret .= '<br />Fax:&nbsp;'.isohtmlentities($anspr_fax);

		if( $anspr_name || $anspr_zeit )
		{
			$ret .= '<br /><small>';
				if( $anspr_name )
					$ret .= 'Kontakt: ' . isohtmlentities($anspr_name);
				if( $anspr_zeit )
				{
					$ret .= $anspr_name? ', ' : '';
					$ret .= isohtmlentities($anspr_zeit);
				}
			$ret .= '</small>';
		}
			
		$MAX_URL_LEN = 31;
		if( $homepage )
		{
			if( substr($homepage, 0, 5) != 'http:' && substr($homepage, 0, 6) != 'https:' ) {
			 	$homepage = 'http:/'.'/'.$homepage;
			}
			
			$ret .= "<br /><a href=\"$homepage\" target=\"_blank\"><i>" .isohtmlentities($this->trimLength($homepage, $MAX_URL_LEN)). '</i></a>';
		}
		
		/* email*/
		if( $anspr_email )
		{ 
			$ret .= "<br /><a href=\"".$this->createMailtoLink($anspr_email, $kursId)."\">" .isohtmlentities($this->trimLength($anspr_email, $MAX_URL_LEN  )). '</a>';
		}
		
		/* logo */
		if( $param['logo'] )
		{
			$ret .= '<br />';
			
			if( $param['logoLinkToAnbieter'] )
				$ret .= '<a href="'.$this->framework->getUrl('a', array('id'=>$anbieterId)).'">';
			
			if( $logo_w && $logo_h && $logo_name != '' )
			{
				$this->fit_to_rect($logo_w, $logo_h, 128, 64, $logo_w, $logo_h);
				$ret .= "<img vspace=\"5\" src=\"{$wisyPortal}admin/media.php/logo/anbieter/$anbieterId/".urlencode($logo_name)."\" width=\"$logo_w\" height=\"$logo_h\" border=\"0\" alt=\"Anbieter Logo\" title=\"\" />";
				
				if( $param['logoLinkToAnbieter'] ) 
					$ret .= '<span class="noprint"><br /></span>';
			}
			
			if( $param['logoLinkToAnbieter'] )
				$ret .= '<i class="noprint">Anbieterdetails anzeigen...</i></a>';
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
		
			$tag_id = $db->f('tag_id');
			$tag_name = $db->f('tag_name');
			$tag_type = $db->f('tag_type');
			$tag_descr = $db->f('tag_descr');
			$tag_help = $db->f('tag_help');
			
			$freq = $this->tagsuggestorObj->getTagFreq(array($this->tag_suchname_id, $tag_id));
			
			$html .= $this->searchRenderer->formatItem($tag_name, $tag_descr, $tag_type, $tag_help, $freq, $addparam);

			
		}
		return $html;
	}
	
	protected function writeOffersOverview($tag_suchname)
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
												& ~32 & ~64		// Synonyme
												& ~128			// Thema
												& ~256			// Anbieter (ist natuerlich immer derselbe)
												& ~512			// Ort
												, array('showtagtype'=>1, 'qprefix'=>"$tag_suchname, "));
		if( $html )
		{
			echo '<h1>Besondere Kursarten - aktuelle Angebote</h1>';
			echo '<p>';
				echo $html;
			echo '</p>';
		}

	} 
	
	public function render()
	{
		$id = intval($_GET['id']);

		$db = new DB_Admin();

		// link to another anbieter?
		$db->query("SELECT attr_id FROM anbieter_verweis WHERE primary_id=$id ORDER BY structure_pos");
		if( $db->next_record() ) {
			$id = intval($db->f('attr_id'));
		}

		// load anbieter
		$db->query("SELECT * FROM anbieter WHERE id=$id");
		if( !$db->next_record() || $db->f('freigeschaltet')!=1 ) {
			$this->framework->error404(); // record does not exist/is not active, report a normal 404 error, not a "Soft 404", see  http://goo.gl/IKMnm -- für nicht-freigeschaltete Datensätze, s. [here]
		}
		$din_nr			= $db->fs('din_nr');
		$suchname		= $db->fs('suchname');
		$firmenportraet	= trim($db->fs('firmenportraet'));
		$date_created	= $db->fs('date_created');
		$date_modified	= $db->fs('date_modified');
		//$stichwoerter	= $this->framework->loadStichwoerter($db, 'anbieter', $id);
		$seals			= $this->framework->getSeals($db, array('anbieterId'=>$id, 'break'=>' '));
		
		// promoted?
		if( intval($_GET['promoted']) > 0 )
		{
			$promoter =& createWisyObject('WISY_PROMOTE_CLASS', $this->framework);
			$promoter->logPromotedRecordClick(intval($_GET['promoted']), $id);
		}
		
		// page out
		headerDoCache();
		echo $this->framework->getPrologue(array(	
													'title'		=>	$suchname,  
													'canonical'	=>	$this->framework->getUrl('a', array('id'=>$id)),
													'bodyClass'	=>	'wisyp_anbieter',
											));
		echo $this->framework->getSearchField();

		// start the result area
		// --------------------------------------------------------------------
		
		echo '<div id="wisy_resultarea"><div id="wisy_resultcol1">';
		
		echo '<p class="noprint">';
			echo '<a href="javascript:history.back();">&laquo; Zur&uuml;ck</a>';
		echo '</p>';
		echo '<h1>' . isohtmlentities($suchname) . '</h1>';
		flush();
		
		if( $firmenportraet != '' ) {
			$wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this->framework);
			echo $wiki2html->run($firmenportraet);
		}

		// current offers overview
		$this->tagsuggestorObj =& createWisyObject('WISY_TAGSUGGESTOR_CLASS', $this->framework); 
		$tag_suchname = $this->tagsuggestorObj->keyword2tagName($suchname);
		$this->tag_suchname_id = $this->tagsuggestorObj->getTagId($tag_suchname);

		$this->writeOffersOverview($tag_suchname);

		// keyword overview
		/*
		if( sizeof($stichwoerter) ) {
			echo '<table cellpadding="0" cellspacing="0" border="0" class="">';
				echo $this->framework->writeStichwoerter($db, 'anbieter', $stichwoerter);
			echo '</table>';
		}
		*/
								
		echo '<p class="wisy_anbieter_footer '.$this->framework->getAllowFeedbackClass().'">';
		 // no content, but must be present as the feedback stuff is created here via JavaScript
		echo '</p>';		

		// break the result area: start second column!
		// --------------------------------------------------------------------
		
		echo '</div><div id="wisy_resultcol2">';
		
		echo '<div class="wisy_vcard">';
			echo '<div class="wisy_vcardtitle">Anbieteradresse</div>';
			echo '<div class="wisy_vcardcontent">';
				echo $this->renderCard($db, $id, 0, array('logo'=>true, 'logoLinkToAnbieter'=>false));
			echo '</div>';
		echo '</div>';
		
		if( $seals )
		{
			echo '<div style="text-align:center;">';
				echo $seals;
			echo '</div>';
		}

		echo '<div style="text-align:center; padding-top:1em;">';
				$freq = $this->tagsuggestorObj->getTagFreq(array($this->tag_suchname_id)); if( $freq <= 0 ) $freq = '';
					echo '<a class="wisy_showalloffers" href="' .$this->framework->getUrl('search', array('q'=>$tag_suchname)). '">'
						. "Zeige alle $freq Angebote"
						. '</a>';
		echo '</div>';
							
		echo '<div class="wisy_vcard">';
			$anbieter_nr = $din_nr? isohtmlentities($din_nr) : $id;
			echo '<div class="wisy_vcardtitle">Anbieternummer: '.$anbieter_nr.'</div>';
			echo '<div class="wisy_vcardcontent">';
				echo 'Erstellt:&nbsp;' . $this->framework->formatDatum($date_created) . ', Ge&auml;ndert:&nbsp;' . $this->framework->formatDatum($date_modified);
			echo '</div>';
		echo '</div>';
								

							
		// end the result area
		// --------------------------------------------------------------------
		
		echo '</div></div>';

		
		
		$copyrightClass =& createWisyObject('WISY_COPYRIGHT_CLASS', $this->framework);
		$copyrightClass->renderCopyright($db, 'anbieter', $id);
		
		echo $this->framework->getEpilogue();
	}
};



registerWisyClass('WISY_ANBIETER_NEW_RENDERER_CLASS');

