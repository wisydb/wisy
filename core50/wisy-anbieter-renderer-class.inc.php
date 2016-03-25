<?php if( !defined('IN_WISY') ) die('!IN_WISY');



require_once('admin/wiki2html8.inc.php');
require_once('admin/classes.inc.php');



class WISY_ANBIETER_RENDERER_CLASS
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

	public function createMailtoLink($adr, $kursId=0)
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

	
	public function renderCard(&$db, $anbieterId, $kursId, $param)
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
		$ret  = '';
		$ret .= '<i>'. htmlentities($postname? $postname : $suchname) . '</i>';

		if( $strasse )
			$ret .= '<br />' . htmlentities($strasse);

		if( $plz || $ort )
			$ret .= '<br />' . htmlentities($plz) . ' ' . htmlentities($ort);

		if( $stadtteil ) {
			$ret .= ($plz||$ort)? '-' : '<br />';
			$ret .= htmlentities($stadtteil);
		}

		if( $land ) {
			$ret .= ($plz||$ort||$stadtteil)? ', ' : '<br />';
			$ret .= htmlentities($land);
		}

		if( $anspr_tel )
			$ret .= '<br />Tel:&nbsp;'.htmlentities($anspr_tel);

		if( $anspr_fax )
			$ret .= '<br />Fax:&nbsp;'.htmlentities($anspr_fax);

		if( $anspr_name || $anspr_zeit )
		{
			$ret .= '<br /><small>';
				if( $anspr_name )
					$ret .= 'Kontakt: ' . htmlentities($anspr_name);
				if( $anspr_zeit )
				{
					$ret .= $anspr_name? ', ' : '';
					$ret .= htmlentities($anspr_zeit);
				}
			$ret .= '</small>';
		}
			
		$MAX_URL_LEN = 31;
		if( $homepage )
		{
			if( substr($homepage, 0, 5) != 'http:' && substr($homepage, 0, 6) != 'https:' ) {
			 	$homepage = 'http:/'.'/'.$homepage;
			}
			
			$ret .= "<br /><a href=\"$homepage\" target=\"_blank\"><i>" .htmlentities($this->trimLength($homepage, $MAX_URL_LEN)). '</i></a>';
		}
		
		/* email*/
		if( $anspr_email )
		{ 
			$ret .= "<br /><a href=\"".$this->createMailtoLink($anspr_email, $kursId)."\">" .htmlentities($this->trimLength($anspr_email, $MAX_URL_LEN  )). '</a>';
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
				$ret .= "<img ".html3('vspace="5"')." src=\"{$wisyPortal}admin/media.php/logo/anbieter/$anbieterId/".urlencode($logo_name)."\" style=\"width: ".$logo_w."px; height: ".$logo_h."px;\" ".html3('border="0"')." alt=\"Anbieter Logo\" title=\"\" id=\"anbieterlogo\"/>";
				
				if( $param['logoLinkToAnbieter'] ) 
					$ret .= '<span class="noprint"><br /></span>';
			}
			
			if( $param['logoLinkToAnbieter'] )
				$ret .= '<i class="noprint">Anbieterdetails anzeigen...</i></a>';
		}
		
		return $ret;
	


	}
	
	function renderDetails(&$db, $id)
	{
		global $wisyPortal;
		global $wisyPortalEinstellungen;
		
		// load anbieter
		$db->query("SELECT * FROM anbieter WHERE id=$id");
		if( !$db->next_record() || $db->f8('freigeschaltet')!=1 ) {
			echo 'Dieser Anbieterdatensatz ist nicht freigeschaltet.'; // it exists, however, we've checked this [here]
			return;
		}
		
		$din_nr			= $db->f8('din_nr');
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
		$typ            = intval($db->f8('typ'));
		
		$ob = new G_BLOB_CLASS($db->f8('logo'));
		$logo_name		= $ob->name;
		$logo_w			= $ob->w;
		$logo_h			= $ob->h;

		$firmenportraet	= trim($db->f8('firmenportraet'));
		$date_created	= $db->f8('date_created');
		$date_modified	= $db->f8('date_modified');
		$homepage		= $db->f8('homepage');

		if( $homepage ) {
			if( substr($homepage, 0, 5) != 'http:'
			 && substr($homepage, 0, 6) != 'https:' ) {
			 	$homepage = 'http:/'.'/'.$homepage;
			}
		}

		
		$stichwoerter = $this->framework->loadStichwoerter($db, 'anbieter', $id);
		
		$seals = $this->framework->getSeals($db, array('anbieterId'=>$id));
	
		// prepare contact link
		if( $anspr_email )
		{
			$anspr_mail_link = "<a href=\"" . $this->createMailtoLink($anspr_email) . "\"><i>" .htmlentities($anspr_email). '</i></a>';
		}
	

		// render head
		echo '<p class="noprint">';
			echo '<a href="javascript:history.back();">&laquo; Zur&uuml;ck</a>';
		echo '</p>';
		
		
		
		flush();
			
		
		// do what to do ...

					echo '<h1>';
						if( $typ == 2 ) echo '<span class="wisy_icon_beratungsstelle">Beratungsstelle<span class="dp">:</span></span> ';
						echo htmlentities($suchname);
					echo '</h1>';

				
					//if( ($logo_w && $logo_h && $logo_name != '')
					// || $seals )
					{
						echo '<table border="0" align="right"><tr><td align="center">';
						
							if( $logo_w && $logo_h && $logo_name != '' )
							{
								$title = "";
								/* - aufgrund der inneren konsistenz das logo nicht anklickbar gestalten - es ist ansonsten verwirrend, ob es zum portait oder zur homepage führt ...
								if( $homepage ) {
									echo "<a href=\"$homepage\" target=\"_blank\">";
									$title = "Der Anbieter im Internet";
								}
								*/
								
								echo "<img src=\"{$wisyPortal}admin/media.php/logo/anbieter/$id/".urlencode($logo_name)."\" width=\"$logo_w\" height=\"$logo_h\" border=\"0\" alt=\"Anbieter Logo\" title=\"$title\" />";
			
								/*
								if( $homepage ) {
									echo '</a>';
								}
								*/
									
								echo '<br />&nbsp;<br />';
							}

							$qsuchname = strtr($suchname, ':,', '  ');
							while( strpos($qsuchname, '  ')!==false ) $qsuchname = str_replace('  ', ' ', $qsuchname);
							echo '<a class="wisy_showalloffers" href="' .$this->framework->getUrl('search', array('q'=>$qsuchname)). '">'
								. 'Zeige alle Angebote'
								. '</a>';
							echo '<br />&nbsp;<br />';
							
							if( $seals )
							{
								echo $seals;
								echo '<br />&nbsp;<br />';
							}
							

							
						
						echo '</td></tr></table>';
					}
					
					
					if( $firmenportraet != '' ) 
					{
						$wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this->framework);
						echo $wiki2html->run($firmenportraet);
					}
					
					echo '<dl class="wisyr_anbieteradresse">';
					
						/* adresse */
						echo '<dt>Adresse</dt>';
						echo '<dd>';
							echo $postname ? htmlentities($postname) : htmlentities($suchname);
							if( $strasse ) { echo '<br />' . htmlentities($strasse); }
							if( $plz || $ort ) { echo '<br />' . htmlentities($plz) . ' ' . htmlentities($ort); }
							if( $stadtteil ) { echo '-' . htmlentities($stadtteil); }
							if( $land ) { echo '<br />' . htmlentities($land); }
						echo '</dd>';
						
						/* telefon */
						if( $anspr_tel )
						{
							echo '<dt>Telefon</dt>';
							echo '<dd>' .htmlentities($anspr_tel). '</dd>';
						}
	
						/* fax */
						if( $anspr_fax )
						{
							echo '<dt>Fax</dt>';
							echo '<dd>' .htmlentities($anspr_fax). '</dd>';
						}
	
						/* ansprechpartner */
						if( $anspr_name )
						{
							echo '<dt>Kontakt</dt>';
							echo '<dd>' .htmlentities($anspr_name). '</d>';
						}
						
						if( $anspr_zeit )
						{
							echo '<dt>Sprechzeiten</dt>';
							echo '<dd>' .htmlentities($anspr_zeit). '</dd>';
						}
	
						/* email*/
						if( $anspr_mail_link )
						{
							echo '<dt>EMail</dt>';
							echo "<dd>" .$anspr_mail_link. '</dd>';
						}
						
						/* internet */
						if( $homepage )
						{
							echo '<dt>Der Anbieter im Internet</dt>';
							echo "<dd><a href=\"$homepage\" target=\"_blank\">" .htmlspecialchars($homepage). '</a></dd>';
						}
		
						/* stichwoerter */
						if( sizeof($stichwoerter) ) {
							echo $this->framework->writeStichwoerter($db, 'anbieter', $stichwoerter);
						}
	
						/* anbieter nr. */
						echo '<dt>Anbieter-Nr.</dt>';
						echo "<dd>";
							if( $din_nr ) {
								echo htmlentities($din_nr);
							}
							else {
								echo $id;
							}
						echo '</dd>';
						
					echo '</dl>';
					
					// -- 12:43 26.05.2014 der Link "zeige alle anbieter" ist nun oben unter dem Logo
					//$qsuchname = strtr($suchname, ':,', '  ');
					//while( strpos($qsuchname, '  ')!==false ) $qsuchname = str_replace('  ', ' ', $qsuchname);
					//echo '&nbsp;<br />Sie m&ouml;chten alle Angebote dieses Anbieters sehen? <a href="' .$this->framework->getUrl('search', array('q'=>$qsuchname)). '">Hier klicken</a>!<br />';
					

			
			echo '<p class="wisy_anbieter_footer '.$this->framework->getAllowFeedbackClass().'">';
				echo 'Erstellt:&nbsp;' . $this->framework->formatDatum($date_created) . ', Ge&auml;ndert:&nbsp;' . $this->framework->formatDatum($date_modified);
			echo '</p>';
	
	}
	
	function render()
	{
		$anbieter_id = intval($_GET['id']);

		$db = new DB_Admin();

		// link to another anbieter?
		$db->query("SELECT attr_id FROM anbieter_verweis WHERE primary_id=$anbieter_id ORDER BY structure_pos");
		if( $db->next_record() )
		{
			$anbieter_id = intval($db->f8('attr_id'));
		}

		// check for existance, get title
		$db->query("SELECT suchname, typ FROM anbieter WHERE id=$anbieter_id");
		if( !$db->next_record() ) {
			$this->framework->error404(); // record does not exist, reporta normal 404 error, not a "Soft 404", see  http://goo.gl/IKMnm -- für nicht-freigeschaltete Datensätze, s. [here]
		}
		$anbieter_suchname = $db->f8('suchname');
		$typ               = intval($db->f8('typ'));


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
													'title'		=>	$anbieter_suchname,  
													'canonical'	=>	$this->framework->getUrl('a', array('id'=>$anbieter_id)),
													'bodyClass'	=>	$bodyClass,
											));
		echo $this->framework->getSearchField();

			echo '<div id="wisy_resultarea">';
				$this->renderDetails($db, $anbieter_id);
			echo '</div>';

		
		
		$copyrightClass =& createWisyObject('WISY_COPYRIGHT_CLASS', $this->framework);
		$copyrightClass->renderCopyright($db, 'anbieter', $anbieter_id);
		
		echo $this->framework->getEpilogue();
	}
};
