<?php if( !defined('IN_WISY') ) die('!IN_WISY');




class WISY_KURS_RENDERER_CLASS
{
    var $framework;
    var $unsecureOnly = false;
    var $h_before_coursefilter = 27; // we want to ignore GMT time zone + daylight saving time complications + usually not in Google index yet
    var $h_before_dontshowteditorforeign_k = 27; // we want to ignore GMT time zone + daylight saving time complications + usually not in Google index yet

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
	}

	function render()
	{
		global $wisyPortalSpalten;
		global $wisyPortalId;

		$kursId = intval($_GET['id']);
		
		$this->checkKursFilter($wisyPortalId, $kursId);

		// query DB
		$db = new DB_Admin();
		$db->query("SELECT	k.freigeschaltet, k.titel, k.org_titel, k.beschreibung, k.anbieter, k.date_created, k.date_modified, k.bu_nummer, k.fu_knr, k.azwv_knr, a.pflege_pweinst, a.typ
						FROM kurse k
						LEFT JOIN anbieter a ON a.id=k.anbieter
						WHERE k.id=$kursId && a.freigeschaltet=1"); // "a.suchname" etc. kann mit "LEFT JOIN anbieter a ON a.id=k.anbieter" zus. abgefragt werden
		if( !$db->next_record() )
			$this->framework->error404();
		$title 				= $db->fs('titel');
		$originaltitel		= $db->fs('org_titel');
		$freigeschaltet 	= intval($db->f('freigeschaltet'));
		$beschreibung		= $db->fs('beschreibung');
		$anbieterId			= intval($db->f('anbieter'));
		$date_created		= $db->f('date_created');
		$date_modified		= $db->f('date_modified');
		$bu_nummer 			= $db->f('bu_nummer');
		$pflege_pweinst		= intval($db->f('pflege_pweinst'));
		$anbieter_typ		= intval($db->f('typ'));
		$record				= $db->Record;
				
		$this->filter_foreign_k($db, $wisyPortalId, $kursId, $date_created);
		
		// promoted?
		if( intval($_GET['promoted']) == $kursId )
		{
			$promoter =& createWisyObject('WISY_PROMOTE_CLASS', $this->framework);
			$promoter->logPromotedRecordClick($kursId, $anbieterId);
		}

		// #404gesperrteseiten
		$freigeschaltet404 = array_map("trim", explode(",", $this->framework->iniRead('seo.set404_kurs_freigeschaltet', "")));
		
		$cms_loggedin = false;
		foreach(array_keys($_COOKIE) AS $key) {
		    if(strpos($key, "pk_ses") !== FALSE) // just for SEO - no security relevance!
		        $cms_loggedin = true;
		}
		
		if(in_array($freigeschaltet, $freigeschaltet404) && !$_SESSION['loggedInAnbieterId'] && !$cms_loggedin)
		    $this->framework->error404();
		
		// page start
		headerDoCache();
		
		$displayAbschluss = $this->framework->iniRead('label.abschluss', 0);
		if($displayAbschluss) {
			$kursAnalyzer =& createWisyObject('WISY_KURS_ANALYZER_CLASS', $this->framework);
			$isAbschluss = count($kursAnalyzer->loadKeywordsAbschluss($db, 'kurse', $kursId));
		}
		
		$displayZertifikat = $this->framework->iniRead('label.zertifikat', 0);
		if($displayZertifikat) {
			if(!is_object($kursAnalyzer)) { $kursAnalyzer =& createWisyObject('WISY_KURS_ANALYZER_CLASS', $this->framework); }
			$isZertifikat = count($kursAnalyzer->loadKeywordsZertifikat($db, 'kurse', $kursId));
		}
		
		$bodyClass = 'wisyp_kurs';
		if( $anbieter_typ == 2 )
		{
			$bodyClass .= ' wisyp_kurs_beratungsstelle';
		} elseif($displayAbschluss && $isAbschluss) {
			$bodyClass .= ' wisyp_kurs_abschluss';	
		} elseif($displayZertifikat && $isZertifikat) {
			$bodyClass .= ' wisyp_kurs_zertifikat';	
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
				if( $displayZertifikat && $isZertifikat ) echo '<span class="wisy_icon_zertifikat">Zertifikat<span class="dp">:</span></span> ';
				echo isohtmlentities($title);
				if( $this->framework->iniRead('fav.use', 0) ) {
					echo '<span class="fav_add" data-favid="'.$kursId.'"></span>';
				}		
			echo '</h1>';
			
			if( $originaltitel != '' && $originaltitel != $title )
			{
				echo '<p><i>' . /*'Originaltitel: ' .*/ isohtmlspecialchars($originaltitel) . '</i></p>';
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
			$tags = $this->framework->loadStichwoerter($db, 'kurse', $kursId);
			if( sizeof((array) $tags) )
			{
				$rows .= $this->framework->writeStichwoerter($db, 'kurse', $tags);
			}
						
			/* // ... Bildungsurlaubsnummer 
			if (($wisyPortalSpalten & 128) > 0)
			{
				$rows .= '<tr>';
					$rows .= '<td' . html3(' valign="top"') . '>Bildungsurlaubsnummer:&nbsp;</td>';
					$rows .= '<td' . html3(' valign="top"') . '>' .($bu_nummer? 'Ja' : 'Nein'). '</td>';
				$rows .= '</tr>';
			} */

			if( $rows != '' ) 
			{
				echo '<table class="wisy_stichwlist"' . html3(' cellpadding="0" cellspacing="0" border="0"') . '>' . $rows . '</table>';
			}

			// Durchfuehrungen vorbereiten
			echo '<h1 class="wisy_df_headline">Zeiten, Orte</h1>';
			
			$showAllDurchf = intval($_GET['showalldurchf'])==1? 1 : 0;
			if( $showAllDurchf )
				echo '<a id="showalldurchf"></a>';
			
			$durchfClass =& createWisyObject('WISY_DURCHF_CLASS', $this->framework);
			$durchfuehrungenIds = $durchfClass->getDurchfuehrungIds($db, $kursId, $showAllDurchf);
			echo '<p>';
			    if( sizeof((array) $durchfuehrungenIds)==0 ) {
					echo $this->framework->iniRead('durchf.msg.keinedf', 'F&uuml;r dieses Angebot ist momentan keine Zeit und kein Ort bekannt.');
				}
				else if( sizeof((array) $durchfuehrungenIds) == 1 ) {
					echo 'F&uuml;r dieses Angebot ist momentan eine Zeit bzw. Ort bekannt:';
				}
				else {
				    echo 'F&uuml;r dieses Angebot sind momentan ' .sizeof((array) $durchfuehrungenIds). ' Durchf&uuml;hrungen bekannt:';
				}
			echo '</p>';
		
			// Durchfuehrungen: init map (global $this->framework->map is used in formatDurchfuehrung())
			$this->framework->map =& createWisyObject('WISY_OPENSTREETMAP_CLASS', $this->framework);
		
			// Durchfuehrungen ausgeben
			if( sizeof((array) $durchfuehrungenIds) )
			{
				echo '<table class="wisy_list"' . html3(' cellpadding="0" cellspacing="0" border="0"') . '>';
					echo '<tr>';
						if (($wisyPortalSpalten & 2) > 0)	{ echo '<th>Zeiten</th>';			}
						if (($wisyPortalSpalten & 4) > 0)	{ echo '<th>Dauer</th>';			}
						if (($wisyPortalSpalten & 8) > 0)	{ echo '<th>Art</th>';				}
						if (($wisyPortalSpalten & 16) > 0)	{ echo '<th>Preis</th>';			}
						if (($wisyPortalSpalten & 32) > 0)	{ echo '<th>Ort, Bemerkungen</th>';	}
						if (($wisyPortalSpalten & 64) > 0)	{ echo '<th>Ang.-Nr.</th>';			}
						if (($wisyPortalSpalten & 128) > 0)	{ echo '<th>Bemerkungen</th>';	    }
					echo '</tr>';
					
					/*
					$maxDurchf = intval($this->framework->iniRead('details.durchf.max'));
					if( $maxDurchf <= 0 || $showAllDurchf )
						$maxDurchf = 1000;
					*/
					
					$renderedDurchf = 0;
					for( $d = 0; $d < sizeof((array) $durchfuehrungenIds); $d++ )
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
															'stichwoerter'=>$tags
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
				
				$allAvailDurchfCnt = sizeof((array) $durchfClass->getDurchfuehrungIds($db, $kursId, true));
				if( $allAvailDurchfCnt > $renderedDurchf )
				{
					$missinglDurchfCnt = $allAvailDurchfCnt-$renderedDurchf;
					$linkText = $missinglDurchfCnt==1? "1 abgelaufene Durchf&uuml;hrung einblenden" : "$missinglDurchfCnt abgelaufene Durchf&uuml;hrungen einblenden"; // 'einblenden' ist besser als 'anzeigen', da dies impliziert, dass die aktuellen Kurse auch in der Liste bleiben
					echo "<p class=\"noprint\"><a href=\"".$this->framework->getUrl('k', array('id'=>$kursId, 'showalldurchf'=>1))."#showalldurchf\">$linkText...</a></p>";
				}
			}
	
			// vollständigkeit feedback, editieren etc.
			echo '<p class="wisy_kurse_footer ' .$this->framework->getAllowFeedbackClass(). '">';
			
			if($this->framework->iniRead('sw_cloud.kurs_anzeige', 0)) {
			    $filtersw = array_map("trim", explode(",", $this->framework->iniRead('sw_cloud.filtertyp', "32, 2048, 8192")));
			    
			    $tags = $this->framework->loadStichwoerter($db, 'kurse', $kursId);
			    $tag_cloud = '<div id="sw_cloud">Suchbegriffe: ';
			    $tag_cloud .= '<h4>Suchbegriffe</h4>';
			    
			    for($i = 0; $i < count($tags); $i++)
			    {
			        $tag = $tags[$i];
			        
			        if($this->framework->iniRead('sw_cloud.kurs_gewichten', 0)) {
			            $tag_freq = $this->framework->getTagFreq($db, $tag['stichwort']);
			            $weight = (floor($tag_freq/50) > 15) ? 15 : floor($tag_freq/50);
			        }
			        
			        if($tag['eigenschaften'] != $filtersw && $tag_freq > 0); {
			            if($this->framework->iniRead('sw_cloud.kurs_stichwoerter', 1))
			                $tag_cloud .= '<span class="sw_raw typ_'.$tag['eigenschaften'].'" data-weight="'.$weight.'"><a href="/?q='.$tag['stichwort'].'">'.$tag['stichwort'].'</a></span>, ';
			                
			            if($this->framework->iniRead('sw_cloud.kurs_synonyme', 0))
			                $tag_cloud .= $this->framework->writeDerivedStichwoerter($this->framework->loadSynonyme($db, $tag['id']), $filtersw, "Synonym", $tag['stichwort']);
			                    
			            if($this->framework->iniRead('sw_cloud.kurs_oberbegriffe', 1))
			                $tag_cloud .= $this->framework->writeDerivedStichwoerter($this->framework->loadAncestors($db, $tag['id']), $filtersw, "Oberbegriff", $tag['stichwort']);
			                        
			            if($this->framework->iniRead('sw_cloud.kurs_unterbegriffe', 0))
			                $tag_cloud .= $this->framework->writeDerivedStichwoerter($this->framework->loadDescendants($db, $tag['id']), $filtersw, "Unterbegriff", $tag['stichwort']);
			        }
			        
			    } // end: for
			    
			    $tag_cloud = trim($tag_cloud, ", ");
			    $tag_cloud .= '</div>';
			    echo $tag_cloud;
			}
				
				if( $vollst['msg'] != '' )
				{
					// echo $vollst['msg'] . ' -  ';
				}

				/*
				if( $originaltitel != '' && $originaltitel != $title )
				{
					echo 'Originaltitel: ' . isohtmlspecialchars($originaltitel) . ' &ndash; ';
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
								 .	$this->framework->getUrl('edit', array('action'=>'ek', 'id'=>$kursId))."\" $target title=\"$tooltip\">Kurs bearbeiten</a>";
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
	
	function filter_foreign_k(&$db, $wisyPortalId, $kursId, $date_created) {
	    $info = array();
	    
	    // if portal has no filter, display course
	    // if(!$GLOBALS['wisyPortalFilter']['stdkursfilter'] || trim($GLOBALS['wisyPortalFilter']['stdkursfilter']) == '')
	    //	return true;
	    
	    
	    if( trim($this->framework->iniRead('disable.kurse', false)) && !$this->framework->is_editor_active($db, $this->h_before_dontshowteditorforeign_k) && !$this->framework->is_frondendeditor_active() ) {
	        $info[0] = array("Einstellung: disble.kurse", "ein", array("Login-Status: Redakteur/in", "abgemeldet", array("Login-Status: Anbieter-Onlinepflege", "abgemeldet")));
	        $add_msg = "";
	        $relevant_portals = $this->framework->matchingportalby_k($db, $kursId);
	        
	        if(count($relevant_portals) > 0) {
	            $add_msg .= "<h4>Dieser Kurs steht nur <b>in folgenden Portalen</b> zur Verf&uuml;gung:</br></h4>";
	            
	            foreach($relevant_portals AS $portal) {
	                $show_portallink = true;
	                
	                $domains = explode(",", $portal['domains']);
	                $main_domain = $domains[0];
	                
	                $show_portallink = !preg_match("/\nauth.use.*=.*1.*/i", $portal['einstellungen'])
	                && !preg_match("/\nseo.portal_blockieren.*=.*1.*/i", $portal['einstellungen'])
	                && !preg_match("/\ndisable.kurse.*=.*1.*/i", $portal['einstellungen'])
	                && trim($main_domain) != ""
	                    && stripos($main_domain, "m.") === FALSE
	                    && stripos($main_domain, "m.") === FALSE
	                    && stripos($main_domain, "frame") === FALSE
	                    && stripos($main_domain, "glossar") === FALSE
	                    && stripos($main_domain, "ratgeber") === FALSE
	                    && stripos($main_domain, "test") === FALSE;
	                    
	                    if($show_portallink) {
	                        $url = 'http://'.$main_domain.'/k'.$kursId;
	                        $add_msg .= '<a href="'.$url.'">'.$url.'</a>'.'<br>';
	                    }
	            }
	            
	        } // end: if relevant p > 0
	        
	        // '.$this->framework->decision_tree_simple($info).'
	        $this->framework->error404("Fehler 404 - Seite <i>in diesem Portal</i> nicht gefunden", "<div class='portal_index'>".$add_msg.'</div>'
	            .'<div class="decision_tree_simple" style="margin-top: 20px;"><a href="#" onclick="$(\'.details\').toggle()">Technische Details anzeigen...</a><div class="details" style="display: none; margin-top: 20px;">Warum wird diese Seite nicht angezeigt:<ul><li>Einstellung "disable.kurse": ein</li><li>Login-Status Redaktionssystem: abgemeldet</li><li>Login-Status Anbieter-Onlinepflege: abgemeldet</li></div></div>'
	            .'</li></ul>', true);
	    }
	    
	    // check if course in search index (=allowed by portal filter)
	    $searcher2 =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
	    $searcher2->prepare('kid:' . $kursId);
	    $anzahlKurse = $searcher2->getKurseCount();
	    
	    if($_GET['debug'] == 10 && $anzahlKurse == 1) {
	        echo "<br>Seite portaleigen!<br>";
	    }
	    
	    if($anzahlKurse == 1)
	        return false;
	        
	        // throw 404 error if filter active & visitor not logged in & course ceated one day ago or earlier
	        $k_created = strtotime($date_created);
	        $k_min_lifespan = strtotime(date("Y-m-d H:i:s"))-(60*60*$this->h_before_coursefilter); // now - 27 hours (we want to ignore GMT time zone + daylight saving time)
	        $k_oldenough = $k_created < $k_min_lifespan;
	        
	        $exclude_foreign_k = trim($this->framework->iniRead('seo.set404_fremdkurse', true));
	        $filter_active = $exclude_foreign_k && $k_oldenough;
	        
	        if($_GET['debug'] == 10) {
	            echo "Anzahl Kurse: ".$anzahlKurse."<br>Exclude foreign Kurse:".$exclude_foreign_k."<br>Alt genug: ".$k_oldenough." <small><br>[created: ".date("d.m.Y H:i", $k_created)."<br>sp&auml;testens: ".date("d.m.Y H:i", $k_min_lifespan)."]</small><br>Editor active ".$this->framework->is_editor_active($db, $this->h_before_dontshowteditorforeign_k)."<br>Online-Pflege: ".intval($this->framework->is_frondendeditor_active())."<br>";
	        }
	        
	        
	        if($filter_active && !$this->framework->is_editor_active($db, $this->h_before_dontshowteditorforeign_k) && !$this->framework->is_frondendeditor_active()) // now - 27 hours (we want to ignore GMT time zone + daylight saving time)
	            $this->framework->error404("Fehler 404 - Seite <i>in diesem Portal</i> nicht gefunden", "<ul><li><a href='/edit?action=ek&id=0'>Zur Seite wechseln: \"Onlinepflege-Login f&uuml;r Anbieter\" ...</a></li></ul>");
	            
	}
};
