<?php if( !defined('IN_WISY') ) die('!IN_WISY');



class WISY_KURS_RENDERER_CLASS
{
	var $framework;
	var $unsecureOnly = false;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
	}

	function render()
	{
		global $wisyPortalSpalten;
        global $wisyPortalSpaltenDurchf;
		global $wisyPortalId;

		$kursId = intval($_GET['id']);
		
		$this->checkKursFilter($wisyPortalId, $kursId);

		// query DB
		$db = new DB_Admin();
		$db->query("SELECT k.freigeschaltet, k.titel, k.org_titel, k.beschreibung, k.anbieter, k.date_created, k.date_modified, k.bu_nummer, k.fu_knr, k.azwv_knr,
                           a.pflege_pweinst, a.suchname, a.strasse, a.plz, a.ort, a.stadtteil, a.land, a.anspr_name, a.postname, a.anspr_zeit, a.anspr_tel, a.anspr_fax, a.anspr_email
						  FROM kurse k
						  LEFT JOIN anbieter a ON a.id=k.anbieter
						  WHERE k.id=$kursId"); // "a.suchname" etc. kann mit "LEFT JOIN anbieter a ON a.id=k.anbieter" zus. abgefragt werden						

		if( !$db->next_record() )
			$this->framework->error404();
		
		$title 				= $db->f8('titel');
		$originaltitel		= $db->f8('org_titel');
		$freigeschaltet 	= intval($db->f('freigeschaltet'));
		$beschreibung		= $db->f8('beschreibung');
		$anbieterId			= intval($db->f('anbieter'));
		$date_created		= $db->f('date_created');
		$date_modified		= $db->f('date_modified');
		$bu_nummer 			= $db->f8('bu_nummer');
		$pflege_pweinst		= intval($db->f8('pflege_pweinst'));
		
		$anbieter_name = $db->f8('suchname');
		$anbieterdetails['suchname'] = $anbieter_name;
		$anbieterdetails['postname'] = $db->f8('postname');
		$anbieterdetails['strasse'] = $db->f8('strasse');
		$anbieterdetails['plz'] = $db->f8('plz');
		$anbieterdetails['ort'] = $db->f8('ort');
		$anbieterdetails['stadtteil'] = $db->f8('stadtteil');
		$anbieterdetails['land'] = $db->f8('land');
		$anbieterdetails['anspr_name'] = $db->f8('anspr_name');
		$anbieterdetails['anspr_zeit'] = $db->f8('anspr_zeit');
		$anbieterdetails['anspr_tel'] = $db->f8('anspr_tel');
		$anbieterdetails['anspr_fax'] = $db->f8('anspr_fax');
		$anbieterdetails['anspr_email'] = $db->f8('anspr_email');
		    
		$record				= $db->Record;
		
		// #enrichtitles
		$ort = "";
		
		// #enrichtitles
		// #richtext
		// #socialmedia
		$showAllDurchf = intval($_GET['showalldurchf'])==1? 1 : 0;
		$durchfClass =& createWisyObject('WISY_DURCHF_CLASS', $this->framework);
		$durchfuehrungenIds = $durchfClass->getDurchfuehrungIds($db, $kursId, $showAllDurchf);	// bereits PLZ-überprüft
		
		if(sizeof($durchfuehrungenIds) == 0)
		    $richtext = false;	// In dem fall kann der Richtext (EducationEvent) nicht vollständig sein und kann/sollte so nicht beworben werden.
		    
		if(intval(trim($this->framework->iniRead('seo.enrich_titles'))) == 1) {
		  // Nur EIN Ort EINER Durchführung wird verwendet - ähnlich Suchergebnisse -> "x weitere..."
		  if(is_array($durchfuehrungenIds) && count($durchfuehrungenIds) > 0) {
		  $db->query("SELECT ort FROM durchfuehrung WHERE id={$durchfuehrungenIds[0]} LIMIT 1"); // id, plz, strasse, land, stadtteil, beginn,
    		      if( $db->next_record() ) {
    		          $df = $db->Record;
    		          if(trim($df['ort']) != "") {
        		          $ort = $df['ort'];	// $df['plz'], $df['strasse'], $df['land'], $df['stadtteil'], $df['beginn'],
        		      }
    		      }
		  }
		}
		
		// promoted?
		if( intval($_GET['promoted']) == $kursId )
		{
			$promoter =& createWisyObject('WISY_PROMOTE_CLASS', $this->framework);
			$promoter->logPromotedRecordClick($kursId, $anbieterId);
		}

		// #404gesperrteseiten
		$freigeschaltet404 = array_map("trim", explode(",", $this->framework->iniRead('seo.set404_kurs_freigeschaltet', "")));
		
		if(in_array($freigeschaltet, $freigeschaltet404) && !$_SESSION['loggedInAnbieterId'])
			$this->framework->error404();
		
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
		
		echo $this->framework->getPrologue(array('title'=>$title, 'ort'=>$ort, 'anbieter_name'=>$anbieter_name, 'anbieter_id'=>$anbieterId, 'beschreibung'=>$beschreibung, 'canonical'=>$this->framework->getUrl('k', array('id'=>$kursId)), 'bodyClass'=>$bodyClass));
		echo $this->framework->getSearchField();
		
		// start the result area
		// --------------------------------------------------------------------
		
		echo '<div id="wisy_resultarea" class="' .$this->framework->getAllowFeedbackClass(). '">';
		
		
			// headline + flush() (loading the rest may take some seconds)
			$h1class = '';

			
			echo '<p class="noprint">' 
			.	 	'<a class="wisyr_zurueck" href="javascript:history.back();">&laquo; Zur&uuml;ck</a>'
			.	 '</p>';

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
			
			echo '<section class="wisyr_kursinfos clearfix">';
			echo '<h1 class="wisyr_kurstitel">';
			if( $anbieter_typ == 2 ) echo '<span class="wisy_icon_beratungsstelle">Beratung<span class="dp">:</span></span> ';
			if( $displayAbschluss && $isAbschluss ) echo '<span class="wisy_icon_abschluss">Abschluss<span class="dp">:</span></span> ';
			echo htmlentities($this->framework->encode_windows_chars($title));
			if( $this->framework->iniRead('fav.use', 0) ) {
			    echo '<span class="fav_add" data-favid="'.$kursId.'"></span>';
			}
			echo '</h1>';
			
			if( $originaltitel != '' && $originaltitel != $title )
			{
			    echo '<h2 class="wisy_originaltitel">' . /*'Originaltitel: ' .*/ htmlspecialchars($originaltitel) . '</h2>';
			}
				echo '<article class="wisy_kurs_inhalt"><h1>Inhalt</h1>';
			
				if( $beschreibung != '' ) {
					$wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this->framework);
					echo $wiki2html->run($this->framework->encode_windows_chars($beschreibung));
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
			
				echo '</article><!-- /.wisy_kurs_inhalt -->';
			
				echo '<article class="wisy_kurs_anbieter"><h1>Anbieter</h1>';
				// visitenkarte des anbieters
				$anbieterRenderer =& createWisyObject('WISY_ANBIETER_RENDERER_CLASS', $this->framework);
				echo '<div class="wisy_vcard">';
					echo '<div class="wisy_vcardtitle">Anbieteradresse</div>';
					echo '<div class="wisy_vcardcontent" itemscope itemtype="https://schema.org/Organization">';
						echo $anbieterRenderer->renderCard($db, $anbieterId, $kursId, array('logo'=>true, 'logoLinkToAnbieter'=>true));
					echo '</div>';
				echo '</div>';
				echo '</article><!-- /.wisy_kurs_anbieter -->';

				// Durchfuehrungen vorbereiten
				echo '<article class="wisy_kurs_durchf"><h1 class="wisy_df_headline">Termine</h1>';
                
                $spalten = $wisyPortalSpalten;
                if($wisyPortalSpaltenDurchf != '') $spalten = $wisyPortalSpaltenDurchf;
			
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
							if (($spalten & 2) > 0)	{ echo '<th>Zeiten</th>';			}
							if (($spalten & 4) > 0)	{ echo '<th>Dauer</th>';			}
							if (($spalten & 8) > 0)	{ echo '<th>Art</th>';				}
							if (($spalten & 16) > 0)	{ echo '<th>Preis</th>';			}
							if (($spalten & 32) > 0)	{ echo '<th>Ort</th>';	}
							if (($spalten & 64) > 0)	{ echo '<th>Ang.-Nr.</th>';			}
							if (($spalten & 128) > 0)	{ echo '<th>Bemerkungen</th>';
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
						echo "<p class=\"wisyr_showalldurchf_link noprint\"><a href=\"".$this->framework->getUrl('k', array('id'=>$kursId, 'showalldurchf'=>1))."#showalldurchf\">$linkText...</a></p>";
					}
				}
				echo '</article><!-- /.wisy_kurs_durchf -->';
			echo '</section><!-- /.wisyr_kursinfos -->';
			
			
	
			// vollständigkeit feedback, editieren etc.
			echo '<footer class="wisy_kurs_footer">';
				
			if($this->framework->iniRead('sw_cloud.kurs_anzeige', 0)) {
			    
			    global $wisyPortalId;
			    $cacheKey = "sw_cloud_p".$wisyPortalId."_k".$kursId;
			    $this->dbCache		=& createWisyObject('WISY_CACHE_CLASS', $this->framework, array('table'=>'x_cache_tagcloud', 'itemLifetimeSeconds'=>60*60*24));
			    
			    if( ($temp=$this->dbCache->lookup($cacheKey))!='' )
			    {
			        $tag_cloud = $temp." <!-- tag cloud from cache -->";
			    }
			    else
			    {
			        $filtersw = array_map("trim", explode(",", $this->framework->iniRead('sw_cloud.filtertyp', "32, 2048, 8192")));
			        $distinct_tags = array();
			        $tags = $this->framework->loadStichwoerter($db, 'kurse', $kursId);
			        $tag_cloud = '<div id="sw_cloud"><h3>'.$this->framework->iniRead('sw_cloud.bezeichnung_kurs', 'Suchbegriffe').'</h3> ';
			        //$tag_cloud .= '<h4>Suchbegriffe</h4>';
			        
			        for($i = 0; $i < count($tags); $i++)
			        {
			            $tag = array_map("utf8_encode", $tags[$i]);
			            
			            if($this->framework->iniRead('sw_cloud.kurs_gewichten', 0)) {
			                $tag_freq = $this->framework->getTagFreq($db, $tag['stichwort']);
			                $weight = (floor($tag_freq/50) > 15) ? 15 : floor($tag_freq/50);
			            }
			            
			            if($tag['eigenschaften'] != $filtersw && $tag_freq > 0); {
			                if($this->framework->iniRead('sw_cloud.kurs_stichwoerter', 1))
			                    $tag_cloud .= '<span class="sw_raw typ_'.$tag['eigenschaften'].'" data-weight="'.$weight.'"><a href="/?q='.urlencode($tag['stichwort']).'">'.$tag['stichwort'].'</a></span>, ';
			                    
			                if($this->framework->iniRead('sw_cloud.kurs_synonyme', 0))
			                    $tag_cloud .= $this->framework->writeDerivedTags($this->framework->loadDerivedTags($db, $tag['id'], $distinct_tags, "Synonyme"), $filtersw, "Synonym", $tag['stichwort']);
			                        
			                if($this->framework->iniRead('sw_cloud.kurs_oberbegriffe', 1))
			                    $tag_cloud .= $this->framework->writeDerivedTags($this->framework->loadDerivedTags($db, $tag['id'], $distinct_tags, "Oberbegriffe"), $filtersw, "Oberbegriff", $tag['stichwort']);
			                            
			                if($this->framework->iniRead('sw_cloud.kurs_unterbegriffe', 0))
			                    $tag_cloud .= $this->framework->writeDerivedTags($this->framework->loadDerivedTags($db, $tag['id'], $distinct_tags, "Unterbegriffe"), $filtersw, "Unterbegriff", $tag['stichwort']);
			            }
			            
			        } // end: for
			        
			        $tag_cloud = trim($tag_cloud, ", ");
			        $tag_cloud .= '</div>';
			        
			        $this->dbCache->insert($cacheKey, utf8_decode($tag_cloud));
			    }
			    
			    echo $tag_cloud;
			} // end: tag cloud
			
			$kerst = $this->framework->iniRead('kursinfo.erstellt', 1);
			$kaend = $this->framework->iniRead('kursinfo.geaendert', 1);
			$kvollst = $this->framework->iniRead('kursinfo.vollstaendigkeit', 1);
			echo '<div class="wisyr_kurs_meta">';
			if($kerst || $kaend || $kvollst) {
			    echo 'Kursinformation: ';
			    if($kerst)
			        echo 'erstellt am ' . $this->framework->formatDatum($date_created).', ';
			    if($kaend)
			        echo 'zuletzt ge&auml;ndert am ' . $this->framework->formatDatum($date_modified).', ';
			    if($kvollst) {
			        echo $vollst['percent'] . '% Vollst&auml;ndigkeit';
			        echo '<div class="wisyr_vollst_info"><span class="info">Hinweise zur f&ouml;rmlichen Vollst&auml;ndigkeit der Kursinfos sagen nichts aus &uuml;ber die Qualit&auml;t der Kurse selbst. <a href="' . $this->framework->getHelpUrl(3369) . '">Mehr erfahren</a></span></div>';
			    }
			}
					$copyrightClass->renderCopyright($db, 'kurse', $kursId);
				echo '</div><!-- /.wisyr_kurs_meta -->';
								
				echo '<div class="wisyr_kurs_edit">';
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
							echo '<span class="noprint">';
								$target = $editurl==''? '' : 'target="_blank"';
								echo $class? "<span class=\"$class\">" : '';
									echo "<a class=\"wisyr_angebot_editlink\" href=\"" . 
										$editurl
									 .	$this->framework->getUrl('edit', array('action'=>'ek', 'id'=>$kursId))."\" $target title=\"$tooltip\">Angebot bearbeiten (für Anbieter)</a>";
								echo $class? "</span>" : '';
							echo '</span>';
						}
					} 
				echo '</div><!-- /.wisyr_kurs_edit -->';
			echo '</footer><!-- /.wisy_kurs_footer -->';


		// end the result area
		// --------------------------------------------------------------------
	
	
	} // freigeschaltet
	
		echo "\n</div><!-- /#wisy_resultarea -->";
		
		echo $this->framework->getEpilogue();
	}
	
	function checkKursFilter($wisyPortalId, $kursId) {
		
		// If no filter, display course
		if(!$GLOBALS['wisyPortalFilter']['stdkursfilter'] || trim($GLOBALS['wisyPortalFilter']['stdkursfilter']) == '')
			return true;
		
		$portaltag = ".portal".$wisyPortalId;
		
		$db = new DB_Admin();
		$tagsql = 'SELECT tag_id FROM x_tags WHERE tag_name="'.$portaltag.'"';
		$db->query($tagsql);
		
		if( !$db->next_record() )
			$this->framework->error404();
			
		$tagId_portal = $db->f8('tag_id');
			
		if( !$tagId_portal )
			$this->framework->error404();
				
		$kurssql = "SELECT DISTINCT kurse.id FROM kurse LEFT JOIN x_kurse ON x_kurse.kurs_id=kurse.id LEFT JOIN x_kurse_tags j0 ON x_kurse.kurs_id=j0.kurs_id "
				  ."LEFT JOIN x_kurse_tags j1 ON x_kurse.kurs_id=j1.kurs_id  WHERE kurse.id = $kursId AND j1.tag_id=$tagId_portal";
						
		$db->query($kurssql);
						
		if( trim($this->framework->iniRead('seo.set404_fremdkurse', "")) == 1 && (!$db->next_record() || !$db->f8('id'))) // && ini.read(fremdekurseausschliessen)
		$this->framework->error404();
	}
};


