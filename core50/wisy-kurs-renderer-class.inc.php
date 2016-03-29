<?php if( !defined('IN_WISY') ) die('!IN_WISY');




class WISY_KURS_RENDERER_CLASS
{
	var $framework;
	var $unsecureOnly = true;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
	}

	function render()
	{
		global $wisyPortalSpalten;

		$kursId = intval($_GET['id']);

		// query DB
		$db = new DB_Admin();
		$db->query("SELECT	k.freigeschaltet, k.titel, k.org_titel, k.beschreibung, k.anbieter, k.date_created, k.date_modified, k.bu_nummer, k.fu_knr, k.azwv_knr, a.pflege_pweinst, a.typ
						FROM kurse k
						LEFT JOIN anbieter a ON a.id=k.anbieter
						WHERE k.id=$kursId"); // "a.suchname" etc. kann mit "LEFT JOIN anbieter a ON a.id=k.anbieter" zus. abgefragt werden
		if( !$db->next_record() )
			$this->framework->error404();
		$title 				= $db->f8('titel');
		$originaltitel		= $db->f8('org_titel');
		$freigeschaltet 	= intval($db->f8('freigeschaltet'));
		$beschreibung		= $db->f8('beschreibung');
		$anbieterId			= intval($db->f8('anbieter'));
		$date_created		= $db->f8('date_created');
		$date_modified		= $db->f8('date_modified');
		$bu_nummer 			= $db->f8('bu_nummer');
		$pflege_pweinst		= intval($db->f8('pflege_pweinst'));
		$anbieter_typ		= intval($db->f8('typ'));
		$record				= $db->Record;
		
		// promoted?
		if( intval($_GET['promoted']) == $kursId )
		{
			$promoter =& createWisyObject('WISY_PROMOTE_CLASS', $this->framework);
			$promoter->logPromotedRecordClick($kursId, $anbieterId);
		}

		// page start
		headerDoCache();
		
		$displayAbschluss = $this->framework->iniRead('label.abschluss', 0);
		if($displayAbschluss) {
			$kursAnalyzer =& createWisyObject('WISY_KURS_ANALYZER_CLASS', $this->framework);
			$isAbschluss = count($kursAnalyzer->loadKeywordsAbschluss($db, 'kurse', $kursId));
		}
		
		$bodyClass = 'wisyp_kurs';
		if( $anbieter_typ == 2 )
		{
			$bodyClass .= ' wisyp_kurs_beratungsstelle';
		} elseif($displayAbschluss && $isAbschluss) {
			$bodyClass .= ' wisyp_kurs_abschluss';	
		}	
		
		echo $this->framework->getPrologue(array('title'=>$title, 'canonical' => $this->framework->getUrl('k', array('id'=>$kursId)), 'bodyClass'=>$bodyClass));
		echo $this->framework->getSearchField();
		
		// start the result area
		// --------------------------------------------------------------------
		
		echo '<div id="wisy_resultarea"><div id="wisy_resultcol1">';
		
		
			// headline + flush() (loading the rest may take some seconds)
			$h1class = '';

			
			echo '<p class="noprint">' 
			.	 	'<a href="javascript:history.back();">&laquo; Zur&uuml;ck</a>'
			.	 '</p>';
			
			echo '<h1>';
				if( $anbieter_typ == 2 ) echo '<span class="wisy_icon_beratungsstelle">Beratung<span class="dp">:</span></span> ';
				if( $displayAbschluss && $isAbschluss ) echo '<span class="wisy_icon_abschluss">Abschluss<span class="dp">:</span></span> ';
				echo htmlentities($title);
				if( $this->framework->iniRead('fav.use', 0) ) {
					echo '<span class="fav_add" data-favid="'.$kursId.'"></span>';
				}		
			echo '</h1>';
			
			if( $originaltitel != '' && $originaltitel != $title )
			{
				echo '<p><i>' . /*'Originaltitel: ' .*/ htmlspecialchars($originaltitel) . '</i></p>';
			}
		

			flush();
			
			// Beschreibung ausgeben
				 if ($freigeschaltet==0) { echo '<p><i>Dieses Angebot ist in Vorbereitung.</i></p>';	}
			else if ($freigeschaltet==3) { echo '<p><i>Dieses Angebot ist abgelaufen.</i></p>';			}
			else if ($freigeschaltet==2) { echo '<p><i>Dieses Angebot ist gesperrt.</i></p>';			}

	$copyrightClass =& createWisyObject('WISY_COPYRIGHT_CLASS', $this->framework);

	if( $freigeschaltet!=2 || $_REQUEST['showinactive']==1 )
	{
			
			$vollst = $this->framework->getVollstaendigkeitMsg($db, $kursId, 'quality.portal');
			
			if( $vollst['banner'] != '' )
			{
				echo '<p class="wisy_badqualitybanner">'.$vollst['banner'].'</p>';
			}
			
			
			if( $beschreibung != '' ) {
				$wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this->framework);
				echo $wiki2html->run($beschreibung);
			}
			
			// Tabellarische Infos ...
			$rows = '';
			
			// ... Stichwoerter
			$stichwoerter = $this->framework->loadStichwoerter($db, 'kurse', $kursId);
			if( sizeof($stichwoerter) )
			{
				$rows .= $this->framework->writeStichwoerter($db, 'kurse', $stichwoerter);
			}
						
			// ... Bildungsurlaubsnummer 
			if (($wisyPortalSpalten & 128) > 0)
			{
				$rows .= '<dt>Bildungsurlaubsnummer:&nbsp;</dt>';
				$rows .= '<dd>' .($bu_nummer? 'Ja' : 'Nein'). '</dd>';
			}

			if( $rows != '' ) 
			{
				echo '<dl class="wisy_stichwlist">' . $rows . '</dl>';
			}

			// Durchfuehrungen vorbereiten
			echo '<h1 class="wisy_df_headline">Zeiten, Orte</h1>';
			
			$showAllDurchf = intval($_GET['showalldurchf'])==1? 1 : 0;
			if( $showAllDurchf )
				echo '<a id="showalldurchf"></a>';
			
			$durchfClass =& createWisyObject('WISY_DURCHF_CLASS', $this->framework);
			$durchfuehrungenIds = $durchfClass->getDurchfuehrungIds($db, $kursId, $showAllDurchf);
			echo '<p>';
				if( sizeof($durchfuehrungenIds)==0 ) {
					echo $this->framework->iniRead('durchf.msg.keinedf', 'F&uuml;r dieses Angebot ist momentan keine Zeit und kein Ort bekannt.');
				}
				else if( sizeof($durchfuehrungenIds) == 1 ) {
					echo 'F&uuml;r dieses Angebot ist momentan eine Zeit bzw. Ort bekannt:';
				}
				else {
					echo 'F&uuml;r dieses Angebot sind momentan ' .sizeof($durchfuehrungenIds). ' Zeiten bzw. Orte bekannt:';
				}
			echo '</p>';
		
			// Durchfuehrungen: init map (global $this->framework->map is used in formatDurchfuehrung())
			$this->framework->map =& createWisyObject('WISY_OPENSTREETMAP_CLASS', $this->framework);
		
			// Durchfuehrungen ausgeben
			if( sizeof($durchfuehrungenIds) )
			{
				echo '<table class="wisy_list wisyr_durchfuehrungen"><thead>';
					echo '<tr>';
						if (($wisyPortalSpalten & 2) > 0)	{ echo '<th>Zeiten</th>';			}
						if (($wisyPortalSpalten & 4) > 0)	{ echo '<th>Dauer</th>';			}
						if (($wisyPortalSpalten & 8) > 0)	{ echo '<th>Art</th>';				}
						if (($wisyPortalSpalten & 16) > 0)	{ echo '<th>Preis</th>';			}
						if (($wisyPortalSpalten & 32) > 0)	{ echo '<th>Ort, Bemerkungen</th>';	}
						if (($wisyPortalSpalten & 64) > 0)	{ echo '<th>Ang.-Nr.</th>';			}
					echo '</tr></thead>';
					
					/*
					$maxDurchf = intval($this->framework->iniRead('details.durchf.max'));
					if( $maxDurchf <= 0 || $showAllDurchf )
						$maxDurchf = 1000;
					*/
					
					$renderedDurchf = 0;
					for( $d = 0; $d < sizeof($durchfuehrungenIds); $d++ )
					{
						$class = ($d%2)==1? ' class="wisy_even"' : '';
						echo "  <tr$class>\n";
							$durchfClass->formatDurchfuehrung($db, $kursId, $durchfuehrungenIds[$d],  
														1,  /*1=add details*/
														$anbieterId,
														$showAllDurchf,
														'', /*addText*/
														array(
															'record'=>$record,
															'stichwoerter'=>$stichwoerter
														)
													);
							$renderedDurchf++;
							/*
							if( $renderedDurchf >= $maxDurchf )
							{
								break;
							}
							*/
						echo '</tr>';
					}
				echo '</table>';
				
				$allAvailDurchfCnt = sizeof($durchfClass->getDurchfuehrungIds($db, $kursId, true));
				if( $allAvailDurchfCnt > $renderedDurchf )
				{
					$missinglDurchfCnt = $allAvailDurchfCnt-$renderedDurchf;
					$linkText = $missinglDurchfCnt==1? "1 abgelaufene Durchf&uuml;hrung einblenden" : "$missinglDurchfCnt abgelaufene Durchf&uuml;hrungen einblenden"; // 'einblenden' ist besser als 'anzeigen', da dies impliziert, dass die aktuellen Kurse auch in der Liste bleiben
					echo "<p class=\"noprint\"><a href=\"".$this->framework->getUrl('k', array('id'=>$kursId, 'showalldurchf'=>1))."#showalldurchf\">$linkText...</a></p>";
				}
			}
	
			// vollständigkeit feedback, editieren etc.
			echo '<p class="wisy_kurse_footer ' .$this->framework->getAllowFeedbackClass(). '">';
				
				if( $vollst['msg'] != '' )
				{
					echo $vollst['msg'] . ' -  ';
				}

				/*
				if( $originaltitel != '' && $originaltitel != $title )
				{
					echo 'Originaltitel: ' . htmlspecialchars($originaltitel) . ' &ndash; ';
				}
				*/
				
				echo 'Erstellt:&nbsp;' . $this->framework->formatDatum($date_created) . ', Ge&auml;ndert:&nbsp;' . $this->framework->formatDatum($date_modified);
				if( $this->framework->iniRead('useredit') )
				{
					if( $pflege_pweinst&1 )
					{
						$loggedInAnbieterId = $this->framework->getEditAnbieterId();
						if( $loggedInAnbieterId==$anbieterId ) {
							// der eingeloggte Anbieter _entspricht_ dem Anbieter des Kurses
							$class = 'wisy_edittoolbar';	
							$tooltip = '';	
							$editurl = '';
						}
						else if( $loggedInAnbieterId > 0 ) {
							// der eingeloggte Anbieter entspricht _nicht_ dem Anbieter des Kurses
							$class = '';
							$tooltip = 'um diesen Kurs zu bearbeiten, ist ein erneuter Anbieterlogin erforderlich';
							$editurl = $copyrightClass->getEditUrl($db, 'kurse', $kursId);
						}
						else {
							// kein Anbieter eingeloggt
							$class = '';
							$tooltip = 'Login f&uuml;r Anbieter';
							$editurl = $copyrightClass->getEditUrl($db, 'kurse', $kursId);
						}
						echo '<span class="noprint"> - ';
							$target = $editurl==''? '' : 'target="_blank"';
							echo $class? "<span class=\"$class\">" : '';
								echo "<a href=\"" . 
									$editurl
								 .	$this->framework->getUrl('edit', array('action'=>'ek', 'id'=>$kursId))."\" $target title=\"$tooltip\">Bearbeiten</a>";
							echo $class? "</span>" : '';
						echo '</span>';
					}
				} 
			echo '</p>';

		// break the result area: start second column!
		// --------------------------------------------------------------------
		
		echo '</div><div id="wisy_resultcol2">';
		
			// visitenkarte des anbieters
			$anbieterRenderer =& createWisyObject('WISY_ANBIETER_RENDERER_CLASS', $this->framework);
			echo '<div class="wisy_vcard">';
				echo '<div class="wisy_vcardtitle">Anbieteradresse</div>';
				echo '<div class="wisy_vcardcontent">';
					echo $anbieterRenderer->renderCard($db, $anbieterId, $kursId, array('logo'=>true, 'logoLinkToAnbieter'=>true));
				echo '</div>';
			echo '</div>';

			// map
			if( $this->framework->map->hasPoints() 
			 && $_SERVER['HTTPS']!='on' )
			{
				echo $this->framework->map->render();
			}
			
			// visitenkarte des portalbetreibers
			$betreiber_id = intval($this->framework->iniRead('visitenkarte.betreiber'));
			$betreiber_html = $this->framework->iniRead('visitenkarte.betreiber.html');
			if( $betreiber_id || $betreiber_html )
			{
				echo '<div class="wisy_vcard">';
					echo '<div class="wisy_vcardtitle">Allgemeine Fragen zur Weiterbildung</div>';
					echo '<div class="wisy_vcardcontent">';
						if( $betreiber_id )   echo $anbieterRenderer->renderCard($db, $betreiber_id, $kursId, array('logo'=>true));
						if( $betreiber_html ) echo $betreiber_html;
					echo '</div>';
				echo '</div>';
			}


		// end the result area
		// --------------------------------------------------------------------
	
	
	} // freigeschaltet
	
		echo '</div></div>';
	
		
		$copyrightClass->renderCopyright($db, 'kurse', $kursId);
		
		echo $this->framework->getEpilogue();
	}
};
