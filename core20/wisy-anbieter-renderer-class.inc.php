<?php if( !defined('IN_WISY') ) die('!IN_WISY');



require_once('admin/wiki2html.inc.php');
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
		if( !$db->next_record() || $db->f('freigeschaltet')!=1 ) {
			echo 'Dieser Anbieterdatensatz ist nicht freigeschaltet.'; // it exists, however, we've checked this [here]
			return;
		}
		
		$din_nr			= $db->fs('din_nr');
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
		$typ            = intval($db->f('typ'));
		
		$ob = new G_BLOB_CLASS($db->fs('logo'));
		$logo_name		= $ob->name;
		$logo_w			= $ob->w;
		$logo_h			= $ob->h;

		$firmenportraet	= trim($db->fs('firmenportraet'));
		$date_created	= $db->fs('date_created');
		$date_modified	= $db->fs('date_modified');
		$homepage		= $db->fs('homepage');

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
			$anspr_mail_link = "<a href=\"" . $this->createMailtoLink($anspr_email) . "\"><i>" .isohtmlentities($anspr_email). '</i></a>';
		}
	

		// render head
		echo '<p class="noprint">';
			echo '<a href="javascript:history.back();">&laquo; Zur&uuml;ck</a>';
		echo '</p>';
		
		
		
		flush();
			
		
		// do what to do ...

					echo '<h1>';
						if( $typ == 2 ) echo '<span class="wisy_icon_beratungsstelle">Beratungsstelle<span class="dp">:</span></span> ';
						echo isohtmlentities($suchname);
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
					
					echo '<table cellpadding="0" cellspacing="0" border="0" class="">';
					
						/* adresse */
						echo '<tr>';
							echo '<td valign="top">Adresse:&nbsp;</td>';
							echo '<td valign="top">';
							
								if( $postname ) {
									echo '<i>' . isohtmlentities($postname) . '</i>';
								}
								else {
									echo '<i>' . isohtmlentities($suchname) . '</i>';
								}
								
								if( $strasse ) {
									echo '<br />';
									echo isohtmlentities($strasse);
								}
								
								if( $plz || $ort ) {
									echo '<br />';
									echo isohtmlentities($plz) . ' ' . isohtmlentities($ort);
									if( $stadtteil ) {
										echo '-' . isohtmlentities($stadtteil);
									}
								}
					
								if( $land ) {
									echo '<br /><i>' . isohtmlentities($land) . '</i>';
								}
			
							echo '</td>';
						echo '</tr>';
						
						/* telefon */
						if( $anspr_tel )
						{
							echo '<tr>';
								echo '<td valign="top">Telefon:&nbsp;</td>';
								echo '<td valign="top">' .isohtmlentities($anspr_tel). '</td>';
							echo '</tr>';
						}
	
						/* fax */
						if( $anspr_fax )
						{
							echo '<tr>';
								echo '<td valign="top">Fax:&nbsp;</td>';
								echo '<td valign="top">' .isohtmlentities($anspr_fax). '</td>';
							echo '</tr>';
						}
	
						/* ansprechpartner */
						if( $anspr_name )
						{
							echo '<tr>';
								echo '<td valign="top">Kontakt:&nbsp;</td>';
								echo '<td valign="top">' .isohtmlentities($anspr_name). '</td>';
							echo '</tr>';
						}
						
						if( $anspr_zeit )
						{
							echo '<tr>';
								echo '<td valign="top">Sprechzeiten:&nbsp;</td>';
								echo '<td valign="top">' .isohtmlentities($anspr_zeit). '</td>';
							echo '</tr>';
						}
	
						/* email*/
						if( $anspr_mail_link )
						{
							echo '<tr>';
								echo '<td valign="top">EMail:&nbsp;</td>';
								echo "<td valign=\"top\">" .$anspr_mail_link. '</td>';
							echo '</tr>';
						}
						
						/* internet */
						if( $homepage )
						{
							echo '<tr>';
								echo '<td valign="top" nowrap="nowrap">Der Anbieter im Internet:&nbsp;</td>';
								echo "<td valign=\"top\"><a href=\"$homepage\" target=\"_blank\"><i>" .isohtmlspecialchars($homepage). '</i></a></td>';
							echo '</tr>';
						}
		
						/* stichwoerter */
						if( sizeof($stichwoerter) ) {
							echo $this->framework->writeStichwoerter($db, 'anbieter', $stichwoerter);
						}
	
						/* anbieter nr. */
						echo '<tr>';
							echo '<td valign="top">Anbieter-Nr.:&nbsp;</td>';
							echo "<td valign=\"top\">";
								if( $din_nr ) {
									echo isohtmlentities($din_nr);
								}
								else {
									echo $id;
								}
							echo '</td>';
						echo '</tr>';
						
					echo '</table>';
					
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
			$anbieter_id = intval($db->f('attr_id'));
		}

		// check for existance, get title
		$db->query("SELECT suchname, typ FROM anbieter WHERE id=$anbieter_id");
		if( !$db->next_record() ) {
			$this->framework->error404(); // record does not exist, reporta normal 404 error, not a "Soft 404", see  http://goo.gl/IKMnm -- für nicht-freigeschaltete Datensätze, s. [here]
		}
		$anbieter_suchname = $db->fs('suchname');
		$typ               = intval($db->f('typ'));


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
