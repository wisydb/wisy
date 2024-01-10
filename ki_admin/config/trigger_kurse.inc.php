<?php

//üöä (ISO8859!)

require_once("codes.inc.php");

/*****************************************************************************
 * Tools
 *****************************************************************************/


/* two simple functions to convert "key=value" pairs to/from arrays
 */
function explode_settings($in)
{
	$out = array();
	$in = strtr($in, "\r\t", "\n ");
	$in = explode("\n", $in);
	for( $i = 0; $i < sizeof((array) $in); $i++ )
	{
		$equalPos = strpos($in[$i], '=');
		if( $equalPos )
		{
			$regKey = trim(substr($in[$i], 0, $equalPos));
			if( $regKey != '' )
			{
				$regValue = trim(substr($in[$i], $equalPos+1));
				$out[$regKey] = $regValue; // the key may be set with an empty value!
			}
		}
	}
	return $out;
}
function implode_settings($arr)
{
	$str = '';
	foreach( $arr as $key=>$value ) {
		$str .= "$key=$value\n";
	}
	return $str;
}



/*****************************************************************************
 * Anbietervollstaendigkeit bestimmen
 *****************************************************************************/


/* alle anbieter vollstaendigkeiten neu berechnen, wird indirekt von 
 * alle_freischaltungen_ueberpruefen() aufgerufen.
 */
function update_alle_anbieter_vollst(&$param)
{
	$total_anbieter = 0;
	$total_kurse = 0; 
	
	$db = new DB_Admin;
	$db2 = new DB_Admin;
	$db->query("SELECT id, vollstaendigkeit, settings FROM anbieter;");
	while( $db->next_record() )
	{
		$anbieter_id = $db->f('id');
		$settings = explode_settings($db->f('settings'));
		$old_avg_vollst = intval($db->f('vollstaendigkeit'));
		
		$kurse_cnt = 0;
		$kurse_sum_vollst = 0;
		$new_min_vollst_id = 0;
		$new_min_vollst =  666;
		$new_max_vollst = -666;
		$db2->query("SELECT id, vollstaendigkeit FROM kurse WHERE anbieter=$anbieter_id AND (freigeschaltet=1 OR freigeschaltet=4) AND vollstaendigkeit>=1 AND vollstaendigkeit<=100");
		while( $db2->next_record() ) {
			$kurs_vollst = intval($db2->f('vollstaendigkeit'));
			$kurse_sum_vollst += $kurs_vollst;
			$kurse_cnt++;
			if( $kurs_vollst < $new_min_vollst ) {
				$new_min_vollst = $kurs_vollst;
				$new_min_vollst_id = $db2->f('id');
			}
			if( $kurs_vollst > $new_max_vollst ) {
				$new_max_vollst = $kurs_vollst;
			}
		}
		
		if( $kurse_cnt ) {
			$new_avg_vollst = intval($kurse_sum_vollst/$kurse_cnt);
			if( $new_avg_vollst < 1 ) $new_avg_vollst = 1;
			if( $new_avg_vollst > 100 ) $new_avg_vollst = 100;
		}
		else {
			$new_avg_vollst = 0; 		// all unset
			$new_min_vollst = 0;
			$new_min_vollst_id = 0;
			$new_max_vollst = 0;
		}
		
		$vollstMin = isset($settings['vollstaendigkeit.min']) ? $settings['vollstaendigkeit.min'] : null;
		$volstMinID = isset($settings['vollstaendigkeit.min.id']) ? $settings['vollstaendigkeit.min.id'] : null;
		$vollstMax = isset($settings['vollstaendigkeit.max']) ? $settings['vollstaendigkeit.max'] : null;
		
		if( $old_avg_vollst != $new_avg_vollst
		 || strval($vollstMin)    != strval($new_min_vollst) // compare against strval to differ between "" and "0" and force settings such values to "0"
		 || strval($volstMinID)   != strval($new_min_vollst_id)
		    || strval($vollstMax)  != strval($new_max_vollst)  )
		{
			$settings['vollstaendigkeit.min']    = $new_min_vollst;
			$settings['vollstaendigkeit.min.id'] = $new_min_vollst_id;
			$settings['vollstaendigkeit.max']    = $new_max_vollst;
			$db2->query("UPDATE anbieter SET vollstaendigkeit=$new_avg_vollst, settings=".$db2->quote(implode_settings($settings))." WHERE id=$anbieter_id");
		}
		
		$total_anbieter++;
		$total_kurse += $kurse_cnt;
	}
	
	//$param['returnmsg'] .= "<br>$total_anbieter Anbietervollstaendigkeiten berechnet, $total_kurse Kurse beruecksichtigt"; // DEBUG ONLY
}



/*****************************************************************************
 * Kursstatus / Kursvollstaendigkeit bestimmen
 *****************************************************************************/


/* update_kurs_state() ...
 * ... schaltet Kurse abhaengig vom Beginndatum automatisch frei
 *     oder entfernt diesen Status wieder
 * ... berechnet aufgrund des Beginndatums die Wochentage
 * ... setzt die PLZ und/oder den Stadtteil auf Grundlage von Strasse und Ort
 *
 *   !!  Diese Funktion wird auch aus dem Portal von edit.php aufgerufen  !!
 *   !!  Nicht davon ausgehen, dass das komplette CMS zur Verfuegung steht  !!
 *
 * Aufrufparameter:
 * $param['from_cms']				- Aufruf durch das CMS?
 * $param['set_plz_stadtteil']		- 1=yes, 2=also update
 * $param['write']					- really write?
 */
function update_kurs_state($kurs_id, $param)
{
	// alten status holen
	$ret = array();
	$db = new DB_Admin;
	$db->query("SELECT freigeschaltet, vollstaendigkeit, titel, beschreibung, thema, bu_nummer, azwv_knr FROM kurse WHERE id=$kurs_id;");
	$db->next_record();
	$alter_status			= intval($db->f('freigeschaltet'));
	$alte_vollstaendigkeit	= intval($db->f('vollstaendigkeit'));
	$titel					= $db->fs('titel');
	$beschreibung			= $db->fs('beschreibung');
	$thema					= intval($db->f('thema'));
	$bu_nummer				= $db->fs('bu_nummer');
	$azwv_knr				= $db->fs('azwv_knr');

	// formatiertes datum fuer heute vor zwei wochen
	$tage  = 1;
	$test_datum = ftime("%Y-%m-%d 00:00:00", (time()-$tage*24*60*60)); // a data of a df must be _larger_ that this data to be "freigeschaltet" 
	$heute_datum = ftime("%Y-%m-%d 00:00:00");

	// alle durchfuehrungs IDs holen
	$durchf_ids = array();
	$db->query("SELECT secondary_id FROM kurse_durchfuehrung WHERE primary_id=$kurs_id ORDER BY structure_pos;");
	while( $db->next_record() )
	{
		$durchf_ids[] = $db->f('secondary_id');
	}
	$anz_durchf = sizeof((array) $durchf_ids);

	
	global $controlTags;
	
	// stichw. holen
	$anz_stichw = 0;
	$has_fernunterricht_stichw = false;
	$has_elearning_stichw = false;
	$has_bildungsgutschein_stichw  = false;
	$has_aktivierungsgutschein_stichw  = false;
	$has_umschulung_stichw  = false;
	$einstieg_bis_kursende_moeglich = false;
	$has_orientierungskurs_stichw = false;
	$has_integrationskurs_intensiv_stichw = false;
    $has_integrationskurs_speziell_stichw = false;
	$has_integrationskurs_stichw = false;
	$has_integrationskurs_alpha_stichw = false;
	$has_deufoev_stichw = false;
	$has_integrationskurs_zweitschrift_stichw = false;
	$has_preiskomplex_stichw = false;

	$db->query("SELECT attr_id FROM kurse_stichwort WHERE primary_id=$kurs_id;");
	while ( $db->next_record() )
	{
	    $attr_id = $db->f('attr_id');
	    
	    if( isset($controlTags['E-Learning']) && $controlTags['E-Learning'] == $attr_id )
	       $has_elearning_stichw = true;
	        
	    if( isset($controlTags['Bildungsgutschein']) && $controlTags['Bildungsgutschein'] == $attr_id )
	       $has_bildungsgutschein_stichw = true;
	            
	    if( isset($controlTags['Aktivierungsgutschein']) && $controlTags['Aktivierungsgutschein'] == $attr_id )
	       $has_aktivierungsgutschein_stichw = true;
	                
	    if( isset($controlTags['Umschulung']) && $controlTags['Umschulung'] == $attr_id )
	       $has_umschulung_stichw = true;
	                    
	    if( isset($controlTags['Orientierungskurs']) && $controlTags['Orientierungskurs'] == $attr_id )
	       $has_orientierungskurs_stichw = true;
	                        
	    if( isset($controlTags['Integrationskurs Intensivkurs']) && $controlTags['Integrationskurs Intensivkurs'] == $attr_id )
	       $has_integrationskurs_intensiv_stichw = true;
	                            
	    if( isset($controlTags['Integrationskurs spezielle Kursarten']) && $controlTags['Integrationskurs spezielle Kursarten'] == $attr_id )
	       $has_integrationskurs_speziell_stichw = true;
	                                
	    if( isset($controlTags['Integrationskurs allgemein']) && $controlTags['Integrationskurs allgemein'] == $attr_id )
	       $has_integrationskurs_stichw = true;
	                                    
	    if( isset($controlTags['Integrationskurs mit Alphabetisierung']) && $controlTags['Integrationskurs mit Alphabetisierung'] == $attr_id )
	       $has_integrationskurs_alpha_stichw = true;
	                                        
	    if( isset($controlTags['DeuFoeV']) && $controlTags['DeuFoeV'] == $attr_id )
	       $has_deufoev_stichw = true;
	                                            
	    if( isset($controlTags['Integrationskurs fuer Zweitschriftlernende']) && $controlTags['Integrationskurs fuer Zweitschriftlernende'] == $attr_id )
	       $has_integrationskurs_zweitschrift_stichw = true;
	                                                
	    if( isset($controlTags['Preis komplex']) && $controlTags['Preis komplex'] == $attr_id )
	       $has_preiskomplex_stichw = true;
	                                                    
	    if( isset($controlTags['Einstieg bis Kursende moeglich']) && $controlTags['Einstieg bis Kursende moeglich'] == $attr_id )
	       $einstieg_bis_kursende_moeglich = true;
	                                                        
	    $anz_stichw++;
	                                                        
	}

	if( $bu_nummer != '' ) $anz_stichw ++;
	if( $azwv_knr != '' )  $anz_stichw ++;
	if( $thema != 0 )	   $anz_stichw ++;
	
	remove_tagsToBeCalculated( $kurs_id );
	
	// alle durchfuehrungen checken
	$baD					= array();
	$neuer_status			= 3; /*abgelaufen*/
	/*$neuer_status_gueltig	= true;*/
	for( $i = 0; $i < $anz_durchf; $i++ )
	{
	    $db->query("SELECT beginn, beginnoptionen, ende, kurstage, preis, strasse, plz, ort, stadtteil, bemerkungen, dauer, dauer_fix, tagescode, nr, "
	        . "stunden, teilnehmer, zeit_von, zeit_bis, rollstuhlgerecht "
	        . "FROM durchfuehrung "
	        . "WHERE id={$durchf_ids[$i]};");
		
		if( $db->next_record() )
		{
			$beginn			= $db->f('beginn');
			$beginnoptionen = intval($db->f('beginnoptionen'));
			$ende       	= $db->f('ende');
			$wochentage 	= intval($db->f('kurstage'));
			$preis 			= intval($db->f('preis'));
			$strasse 		= $db->fs('strasse');
			$plz 			= $db->fs('plz');
			$ort 			= $db->fs('ort');
			$stadtteil		= $db->fs('stadtteil');
			$bemerkungen 	= $db->fs('bemerkungen');
			$alte_dauer 	= intval($db->f('dauer'));
			$dauer_fix 	= intval($db->f('dauer_fix'));
			$alter_tagescode= intval($db->f('tagescode'));
			$nr 			= $db->fs('nr');
			$stunden		= intval($db->f('stunden'));
			$teilnehmer		= intval($db->f('teilnehmer'));
			$zeit_von		= $db->fs('zeit_von'); if( $zeit_von == '00:00' ) $zeit_von = '';
			$zeit_bis		= $db->fs('zeit_bis'); if( $zeit_bis == '00:00' ) $zeit_bis = '';
			$rollstuhlgerecht = $db->f('rollstuhlgerecht');
			$ret['returnmsg'] = isset( $ret['returnmsg'] ) ? $ret['returnmsg'] : '';
			
			$update = '';
			
			global $controlTags;
			
			// Wenn Datum in Zukunft oder oefter beginnend oder Kurs ist dauerhaft => SW Rollstuhlgerecht anhaengen solang eine DF die entspr. Option hat.
			if( intval($rollstuhlgerecht) != 0 ) {
			    if( $alter_status == 4 || strtotime($beginn) >= strtotime(date('Y-m-d H:i:s')) || $beginnoptionen > 0  )
			        add_tagToCourse( $kurs_id, array( $controlTags['rollstuhlgerecht'] ) );
			}
			
			// beginnstatus ueberpruefen
			/* -- 11:54 18.12.2012: Fehlende Beginndaten fuehren nicht mehr zu freigeschalteten Kursen!
			if( $beginn == '' || $beginn == '0000-00-00 00:00:00' )
			{
				$neuer_status = 1;
				if( $param['from_cms'] )	// nur wenn alle beginndaten gesetzt sind, koennen wir wirklich 
				{							// unterscheiden, ob der kurs abgelaufen ist oder nicht;
											// im redaktionssystem ueberlassen wir diese aufgabe dem Redakteur
					$neuer_status_gueltig = false;
				}
			}
			*/
			
			if( ($beginn > $test_datum)
			 /* || ($beginnoptionen >= 4) -- 11:55 18.12.2012: Irgendwelche Optionen fuehren nicht mehr zu freigeschalteten Kursen! */ )
			{
				$neuer_status = 1; /*freigeschaltet*/
			}
			else if( $beginn == '' || $beginn == '0000-00-00 00:00:00' )
			{
				if( $beginnoptionen >= 1 )
					$neuer_status = 1; /*freigeschaltet*/ /*Aenderung vom 4.01.2013: Kurse ohne Datum mit Option sind freigeschaletet, das ist mit beate noch nicht geklaert*/
			}
			else if( $einstieg_bis_kursende_moeglich ) 
			{
				/*EDIT 22:54 24.04.2013: Kurse mit dem Stichwort 315/"Einstieg bis Kursende moeglich" sind freigeschaltet solange der Kurse nicht zu Ende ist; 
					gibt es kein Endedatum, hat die Option keine Auswirkung */
				if( $ende != '' && $ende != '0000-00-00 00:00:00' ) {
					if( $ende >= $heute_datum ) {
						$neuer_status = 1;
					}
				}
			}
		
			// Wochentage ueberpruefen
			if( $wochentage == 0 )
			{
				$wochentage = berechne_wochentage($beginn, $ende);
				if( $wochentage )
				{
					$update .= ($update==''? '' : ', ') . " kurstage=$wochentage ";
					$ret['returnmsg'] .= ( isset( $ret['returnmsg'] ) && $ret['returnmsg'] ? '<br>' : '') . 'Durchf&uuml;hrung '.($i+1).': Wochentage anhand des Beginn-/Endedatums gesetzt.';
					$ret['returnreload'] = true;
				}
			}
			
			// Tagescode ueberpruefen
			$neuer_tagescode = berechne_tagescode($zeit_von, $zeit_bis, $wochentage);
			if( $neuer_tagescode != 0 && $neuer_tagescode != $alter_tagescode )
			{
				$update .= ($update==''? '' : ', ') . " tagescode=$neuer_tagescode ";
				//if( $alter_tagescode != 0 ) 20:32 30.01.2014: die entsprechende Nachricht wird immer ausgegeben. Warum auch nicht? Man wundert sich sonst, warum ein eigentlich nicht geaenderter Datensatz be Klick auf "Uebernehmen" tatsaechlich gespeichert wird ... [**]
				
				$returnMsg = ( isset( $ret['returnmsg'] ) && $ret['returnmsg'] ? '<br>' : '') . 'Tagescode anhand Uhrzeit/Wochentage korrigiert. <a href="https://b2b.kursportal.info/index.php?title=Berechnung_des_Tagescode" target="_blank" rel="noopener noreferrer">Weitere Informationen hierzu...</a>';
				
				if( isset( $ret['returnmsg'] ) )
				    $ret['returnmsg'] .= $returnMsg;
				else
				    $ret['returnmsg'] = $returnMsg;
				
				$ret['returnreload'] = true;
			}

			// Dauer ueberpruefen (06.12.2012: wenn kein Wert berechnet werden konnte, alten (evtl. manuell gesetzten) Wert lassen)
			$neue_dauer = berechne_dauer(str_replace("00:00:00", $zeit_von.":00", $beginn), str_replace("00:00:00", $zeit_bis.":00", $ende));
			if( $neue_dauer != 0 && $neue_dauer != $alte_dauer && !$dauer_fix)
			{
				$update .= ($update==''? '' : ', ') . " dauer=$neue_dauer ";
				//if( $alte_dauer != 0 ) 20:32 30.01.2014: die entsprechende Nachricht wird immer ausgegeben, s. [**]
				
				$returnMsg = "";
				if( isset($ret['returnmsg']) ) $returnMsg = $ret['returnmsg'] . '<br>' ;
				$ret['returnmsg'] = $returnMsg . 'Dauer anhand Beginn-/Endedatum korrigiert. <a href="https://b2b.kursportal.info/index.php?title=Berechnung_der_Dauer" target="_blank" rel="noopener noreferrer">Weitere Informationen hierzu...</a>';
				                  
				$ret['returnreload'] = true;
			}
			
			// PLZ/Stadtteil ueberpruefen
			if( isset( $param['set_plz_stadtteil'] ) && $param['set_plz_stadtteil'] )
			{
			    if( !isset( $GLOBALS['plztool'] ) || !is_object($GLOBALS['plztool']) ) { $GLOBALS['plztool'] = new PLZTOOL_CLASS(); } 				
				$plzetc = $GLOBALS['plztool']->search_plzstadtteil_by_strort($strasse, $ort);
				
				if( is_array( $plzetc ) )
				{
					$setmsg = '';
					$cannotsetmsg = '';
					
					if( $plz == '' || ( isset($param['set_plz_stadtteil']) && $param['set_plz_stadtteil']==2 && isset($plzetc['plz']) && $plzetc['plz'] != '' && $plz != $plzetc['plz']) )
					{
					    if( $plzetc['plz'] != '' )
						{
							$plz = $plzetc['plz'];
							$update .= ($update==''? '' : ', ') . " plz='".addslashes($plz)."' ";
							$setmsg = 'PLZ';
						}
						else
						{
							$cannotsetmsg = 'PLZ';
						}
						
					}

					if( $stadtteil == '' || (isset($param['set_plz_stadtteil']) && $param['set_plz_stadtteil']==2 && isset($plzetc['stadtteil']) && $plzetc['stadtteil'] != '' && $plz != $plzetc['stadtteil']) )
					{
						if( $plzetc['stadtteil'] != '' )
						{
							$stadtteil = $plzetc['stadtteil'];
							$update .= ($update==''? '' : ', ') . " stadtteil='".addslashes($stadtteil)."' ";
							$setmsg .= ($setmsg==''? '' : ' und ') . 'Stadtteil';
						}
						else
						{
							$cannotsetmsg .= ($cannotsetmsg==''? '' : '/') . 'Stadtteil';
						}
					}

					if( isset($setmsg) && $setmsg )		          
					   { $ret['returnmsg'] .= ( isset($ret['returnmsg']) && $ret['returnmsg'] ? '<br>' : '') . 'Durchf&uuml;hrung '.($i+1).': '.$setmsg.' anhand von Strasse/Ort gesetzt.'; $ret['returnreload'] = true; }
					
					if( isset($cannotsetmsg) && $cannotsetmsg )	  
					   { $ret['returnmsg'] .= ( isset($ret['returnmsg']) && $ret['returnmsg'] ? '<br>' : '') . 'Durchf&uuml;hrung '.($i+1).': F&uuml;r die gegeben Strasse/Ort sind unterschiedliche Werte f&uuml;r '.$cannotsetmsg.' m&ouml;glich, die nicht automatisch ermittelt werden konnten.'; }
				}
			}
			
			// do update ...
			if( isset($update) && $update )
			{
			    if( isset( $param['write'] ) && $param['write'] )
				{
					$db->query("UPDATE durchfuehrung SET $update WHERE id={$durchf_ids[$i]}");
				}
			}
			
			// vollstaendigkeit berechnen
			if( ($beginn != '' && $beginn != '0000-00-00 00:00:00') 
			 || ($beginnoptionen >= 1) 
			 || $has_fernunterricht_stichw )
			     { isset( $baD['beginn'] ) ? $baD['beginn']++ : $baD['beginn'] = 1; }
			     
			
			if( ($ende != '' && $ende != '0000-00-00 00:00:00') 
			 || $has_fernunterricht_stichw )
			     { isset( $baD['ende'] ) ? $baD['ende']++ : $baD['ende'] = 1; }
			
			// if( $plz != '' || $ort != '' || $has_fernunterricht_stichw || $has_elearning_stichw)	$baD['plz']++;
			if( $ort != '' || $has_fernunterricht_stichw || $has_elearning_stichw)
			     { isset( $baD['ort'] ) ? $baD['ort']++ : $baD['ort'] = 1; }
	

	
			// Wenn Preis vorhanden oder ein SW keinen Preis notwendig macht:
			if( $preis != -1
						|| $has_bildungsgutschein_stichw
						|| $has_aktivierungsgutschein_stichw
						|| $has_umschulung_stichw
						|| $has_orientierungskurs_stichw
						|| $has_integrationskurs_intensiv_stichw
						|| $has_integrationskurs_speziell_stichw
						|| $has_integrationskurs_stichw
						|| $has_integrationskurs_alpha_stichw
						|| $has_deufoev_stichw
						|| $has_integrationskurs_zweitschrift_stichw
			    || $has_preiskomplex_stichw) {
			        isset( $baD['preis'] ) ? $baD['preis']++ : $baD['preis'] = 1;
			    }

			if( $nr != '' ) 
			 { isset( $baD['nr'] ) ? $baD['nr']++ : $baD['nr'] = 1; }
			if( $strasse != '' || $has_fernunterricht_stichw || $has_elearning_stichw)
			 { isset( $baD['strasse'] ) ? $baD['strasse']++ : $baD['strasse'] = 1; }
			if( $stunden > 0 )
			 { isset( $baD['stunden'] ) ? $baD['stunden']++ : $baD['stunden'] = 1; }
			if( $teilnehmer > 0 )
			 { isset( $baD['teilnehmer'] ) ? $baD['teilnehmer']++ : $baD['teilnehmer'] = 1; }
			if( $zeit_von != '' )
			 { isset( $baD['zeit_von'] ) ? $baD['zeit_von']++ : $baD['zeit_von'] = 1; }
			if( $zeit_bis != '' )
			 { isset( $baD['zeit_bis'] ) ? $baD['zeit_bis']++ : $baD['zeit_bis'] = 1; }
		}
	}
	
	// vollstaendigkeit berechnen
	$vmsg = '';
	$punkte_erreicht = 0.0;
	if( $anz_durchf > 0 )
	{
	    // 50 punkte
	    if( strlen($beschreibung)>strlen($titel) )	{ 
	        $punkte_erreicht += 12.5; 
	    } else { 
	        $vmsg .= "<br><span class='missing_textlaenge'>&bull; Um die Mindestvollst&auml;ndigkeit zu erreichen, stellen Sie bitte eine <b>Mindesttextl&auml;nge</b> sicher.</span>"; 
	    }
	    
	    $baD_beginn = isset($baD['beginn']) ? $baD['beginn'] : null;
	    
	    if( $baD_beginn == $anz_durchf ) {  
	        $punkte_erreicht += 12.5; } else { $missing = $anz_durchf - $baD_beginn;		
	       $vmsg .= "<br><span class='missing_beginn'>&bull; Um die Mindestvollst&auml;ndigkeit zu erreichen, geben Sie bitte <b>alle Beginndaten</b> ein. Es fehlen aktuell $missing Beginndaten.</span>"; 
	    }

	    $baD_ort = isset($baD['ort']) ? $baD['ort'] : null;
	    
	    if( $baD_ort == $anz_durchf ) { 
	        $punkte_erreicht += 12.5; } else { $missing = $anz_durchf - $baD_ort; 		
	        $vmsg .= "<br><span class='missing_ort'>&bull; Um die Mindestvollst&auml;ndigkeit zu erreichen, machen Sie bitte Angaben zum <b>Ort in allen Durchf&uuml;hrungen</b>. Es fehlen aktuell $missing Orte.</span>"; 
	    }
	    
	    $baD_preis = isset($baD['preis']) ? $baD['preis'] : null;
	    
	    if( $baD_preis == $anz_durchf ) { 
	        $punkte_erreicht += 12.5; } else { $missing = $anz_durchf - $baD_preis; 		
	        $vmsg .= "<br><span class='missing_preis'>&bull;Um die Mindestvollst&auml;ndigkeit zu erreichen, geben Sie bitte <b>alle Preise</b> ein. Es fehlen aktuell $missing Preise.</span>"; 
	    }
	    
	    if( $punkte_erreicht >= 50 )
	    {
	        // 45 punkte
	        $baD_nr = isset($baD['nr']) ? $baD['nr'] : null;
	        
	        if( $baD_nr == $anz_durchf )   { 
	            $punkte_erreicht +=  2.0; 
	        } else { 
	            $missing = $anz_durchf - $baD_nr;			
	            $vmsg .= "<br><span class='missing_nr'>&bull; Um die Vollst&auml;ndigkeit zu erh&ouml;hen, geben Sie bitte <b>alle Kursnummern</b> ein. Es fehlen aktuell $missing Kursnummern.</span>"; 
	        }
	        
	        $baD_stunden = isset($baD['stunden']) ? $baD['stunden'] : null;
	        
	        if( $baD_stunden == $anz_durchf ) { 
	            $punkte_erreicht += 10.0; 
	        } else { 
	            $missing = $anz_durchf - $baD_stunden;	
	            $vmsg .= "<br><span class='missing_stunden'>&bull; Um die Vollst&auml;ndigkeit zu erh&ouml;hen, geben Sie bitte <b>alle Stundenanzahlen</b> ein. Es fehlen aktuell $missing Angaben hierzu.</span>"; 
	        }
	        
	        $baD_ende = isset($baD['ende']) ? $baD['ende'] : null;
	        
	        if( $baD_ende == $anz_durchf ) { 
	            $punkte_erreicht += 10.0; 
	        } else { 
	            $missing = $anz_durchf - $baD_ende;		
	            $vmsg .= "<br><span class='missing_ende'>&bull; Um die Vollst&auml;ndigkeit zu erh&ouml;hen, geben Sie bitte <b>alle Enddaten</b> ein. Es fehlen aktuell $missing Daten.</span>"; 
	        }
	        
	        $baD_teilnehmer = isset($baD['teilnehmer']) ? $baD['teilnehmer'] : null;
	        
	        if(  $baD_teilnehmer == $anz_durchf ) { 
	            $punkte_erreicht += 10.0; 
	        } else { 
	            $missing = $anz_durchf - $baD_teilnehmer;	
	            $vmsg .= "<br><span class='missing_teilnehmer'>&bull; Um die Vollst&auml;ndigkeit zu erh&ouml;hen, geben Sie bitte <b>alle Teilnehmendenanzahlen</b> ein. Es fehlen aktuell $missing Angaben hierzu.</span>"; 
	        }
	        
	        $baD_zeit_von = isset($baD['zeit_von']) ? $baD['zeit_von'] : null;
	        
	        if( $baD_zeit_von == $anz_durchf ) { 
	            $punkte_erreicht +=  5.0; 
	        } else { 
	            $missing = $anz_durchf - $baD_zeit_von;	
	            $vmsg .= "<br><span class='missing_zeit_von'>&bull; Um die Vollst&auml;ndigkeit zu erh&ouml;hen, geben Sie bitte <b>alle Beginnzeiten</b> ein. Es fehlen aktuell $missing Angaben hierzu.</span>"; 
	        }
	        
	        $baD_zeit_bis = isset($baD['zeit_bis']) ? $baD['zeit_bis'] : null;
	        
	        if( $baD_zeit_bis == $anz_durchf ) { 
	            $punkte_erreicht +=  5.0; 
	        } else { 
	            $missing = $anz_durchf - $baD_zeit_bis;	
	            $vmsg .= "<br><span class='missing_zeit_bis'>&bull; Um die Vollst&auml;ndigkeit zu erh&ouml;hen, geben Sie bitte <b>alle Endezeiten</b> ein. Es fehlen aktuell $missing Angaben hierzu.</span>"; 
	        }
	        
	        $baD_strasse = isset($baD['strasse']) ? $baD['strasse'] : null;
	        
	        if( $baD_strasse == $anz_durchf ) { 
	            $punkte_erreicht +=  3.0; 
	        } else { 
	            $missing = $anz_durchf - $baD_strasse;	
	            $vmsg .= "<br><span class='missing_strasse'>&bull; Um die Vollst&auml;ndigkeit zu erh&ouml;hen, machen Sie bitte Angaben zu den <b>Strassen in allen Durchf&uuml;hrungen</b>. Es fehlen aktuell $missing Strassen.</span>"; 
	        }
	        
	        // 5 Punkte nach Freischaltung durch die Redaktion
	        if( $anz_stichw >= 2 ) { 
	            $punkte_erreicht +=  5.0; 
	        } else { 
	            if($vmsg=='') { 
	                $vmsg .= '<br><span class="alles_vollstaendig">&bull; Sie haben alle notwendigen Angaben zur Vollst&auml;ndigkeit gemacht; der Kurs wird als 100%-vollst&auml;ndig gelistet, sobald er von der Redaktion freigeschaltet wird.</span>'; 
	            }	
	        }
	    }
	}
	else
	{
	    $vmsg .= '<br><span class="alles_vollstaendig">&bull; Um die Mindestvollst&auml;ndigkeit zu erreichen, legen Sie bitte eine <b>Durchf&uuml;hrung</b> an.</span>';
	}
	$ret['vmsg'] = $vmsg;
	
	//if( $ret['vmsg'] ) { $ret['returnmsg'] .= ($ret['returnmsg']? '<br>' : '') . 'Informationen zur Vollst&auml;ndigkeit:' . $ret['vmsg']; }
	
	// neuen status schreiben
	if( ($alter_status == 1 /*freigesch.*/ || $alter_status == 3 /*abgel.*/)
	 /*&&  $neuer_status_gueltig */
	 &&  $neuer_status != $alter_status )
	{
	    if( isset( $param['write'] ) && $param['write'] )
		{
			$db->query("UPDATE kurse SET freigeschaltet=$neuer_status WHERE id=$kurs_id;");
			$ret['returnreload'] = true;
		}
	}
	
	// Neue Vollstaendigkeit schreiben
	$punkte_erreicht = intval($punkte_erreicht);
	if( $punkte_erreicht < 1   ) $punkte_erreicht = 1;
	if( $punkte_erreicht > 100 ) $punkte_erreicht = 100;
	if( $punkte_erreicht != $alte_vollstaendigkeit )
	{
	    if( isset( $param['write'] ) && $param['write'] )
		{
			$db->query("UPDATE kurse SET vollstaendigkeit=$punkte_erreicht WHERE id=$kurs_id;");
		}
		
		$ret['returnreload'] = true;
	}
	
	
	// Selektiere groessŸtes modified DF-Datum fuer diesen Kurs nach allen Updates
/*	$db->query( 'SELECT MAX(durchfuehrung.date_modified) AS lastModified FROM kurse_durchfuehrung, durchfuehrung WHERE kurse_durchfuehrung.secondary_id = durchfuehrung.id AND kurse_durchfuehrung.primary_id = ' . $kurs_id );
	
	if( $db->next_record() ) {
	    // don't update that here, otherwise every change of course leads to "change of DF"
	    // $db->query( 'UPDATE kurse SET x_df_lastmodified = ' . '"'.$db->f( 'lastModified' ).'" WHERE kurse.id = ' . $kurs_id );
	    
	    $lastModified = $db->f( 'lastModified' );
	    $ret['returnmsg'] .= ($lastModified > 0 && $lastModified != '0000-00-00 00:00:00') ? "Letzte DF-Bearbeitung: " . $lastModified;
	    
	    $lastModified = $db->f( 'lastModified' );
	    $ret['returnmsg'] .= ($lastModified > 0 && $lastModified != '0000-00-00 00:00:00') ? "Letzte DF-Bearbeitung: " . $lastModified;
	    
	    $ret['returnreload'] = true; // makes sure value is updated in currently saved mask
	} */
	
	return $ret;
}



/*****************************************************************************
 * Trigger "main"
 *****************************************************************************/



function alle_freischaltungen_ueberpruefen()
{
	$db = new DB_Admin();
	$oneWeekAgo = ftime("%Y-%m-%d 00:00:00", time()-7*24*60*60); // 14:46 03.06.2014 wir ueberpruefen auch abgelaufene Kurse, die kuerzlich geaendert wurden - mag sein, dass z.B. duch MultiEdit etwas geaendert wurde, das den Kurs wieder freigeschaltet werden laesst
	$sql = "SELECT id FROM kurse WHERE freigeschaltet=1 OR (freigeschaltet=3 AND date_modified>='$oneWeekAgo');";
	$db->query($sql); 
	while( $db->next_record() )
	{
		update_kurs_state($db->f('id'), array('from_cms'=>0, 'set_plz_stadtteil'=>0, 'write'=>1));
	}
	
	$dummy = array();
	update_alle_anbieter_vollst($dummy);
}



$pluginfunc = 'trigger_kurse';
function trigger_kurse(&$param)
{
    if( isset( $param['action'] ) && $param['action'] == 'afterinsert'
     || isset( $param['action'] ) && $param['action'] == 'afterupdate' )
	{

	    $uks = update_kurs_state( (isset($param['id']) ? $param['id'] : null), array('from_cms'=>1, 'set_plz_stadtteil'=>1, 'write'=>1));
		
	    if( isset( $uks['returnmsg'] ) && $uks['returnmsg'] ) 	 {
		    $paramReturnMsg   = isset( $param['returnmsg'] ) ? '<br>' : '';
		    $uksReturnMsg     = isset( $uks['returnmsg'] ) ? $uks['returnmsg'] : '';
		    
		    if( isset( $param['returnmsg'] ) )
		      $param['returnmsg'] .=  $paramReturnMsg . $uksReturnMsg;
		    else
		      $param['returnmsg'] =  $paramReturnMsg . $uksReturnMsg;
	    }
		
		if( isset( $uks['returnreload'] ) && $uks['returnreload'] )	
		    $param['returnreload'] = true;

		// once a day, we're calling alle_freischaltungen_ueberpruefen() from here; however, since 12/2014 we're calling this function
		// also from wisy-sync-renderer-class.inc.php, so, if everything works well, the part below may be removed
		$lastUpdate = regGet('daily.freischaltung_ueberpruefen', '0000-00-00', 'template');
		if( $lastUpdate != ftime('%Y-%m-%d') )
		{
			set_time_limit(2*60*60 /*2 hours ...*/);
			ignore_user_abort(1);
			
			regSet('daily.freischaltung_ueberpruefen', ftime('%Y-%m-%d'), '0000-00-00', 'template');
			regSave();

			alle_freischaltungen_ueberpruefen();
			
			$param['returnmsg']  = isset($param['returnmsg']) ? $param['returnmsg'] : '';
			$param['returnmsg'] .= ( isset( $param['returnmsg'] ) && $param['returnmsg'] ? '<br>' : '') . 'Alle Freischaltungen &uuml;berpr&uuml;ft.';
		}
		
		update_titel_sorted($param['id']);
	}
	
	return 1;
}

function update_titel_sorted($kurs_id, $titel = "") {
		$db = new DB_Admin;
		if($titel == "") {
			$db->query("SELECT titel FROM kurse WHERE id=".$kurs_id);
			$db->next_record();
			$titel					= $db->fs('titel');
		}
		$db->query("UPDATE kurse SET titel_sorted = '".g_eql_normalize_natsort($titel)."' WHERE id=".$kurs_id);
}

function add_tagToCourse($kursId, $newStichwoerter)
{
    if( !is_array($newStichwoerter) )
        return;
        
    $db = new DB_Admin;
        
    // write all tags as a whole (this includes the new ones)
    foreach($newStichwoerter as $newStichwort) {
        $db->query("SELECT * FROM kurse_stichwort WHERE primary_id=$kursId AND attr_id=$newStichwort");
            
        if( $db->next_record() )
            return; // tag already attached to course
                
        $db->query("SELECT MAX(structure_pos) AS sp FROM kurse_stichwort WHERE primary_id=$kursId");
        $db->next_record();
        $structurePos = intval($db->f('sp'))+1;
                
        $db->query("INSERT INTO kurse_stichwort (primary_id, attr_id, structure_pos) VALUES($kursId, $newStichwort, $structurePos);");
    }
}

function remove_tagsToBeCalculated($kursId) {
    global $controlTags;
    
    if( $kursId == 0 || $kursId == null )
        return;
        
    $id_rollstuhlgerecht = $controlTags['rollstuhlgerecht'];
        
    if( $id_rollstuhlgerecht == 0 || $id_rollstuhlgerecht == null )
        return;
            
    $db = new DB_Admin;
    $db->query( "DELETE FROM kurse_stichwort WHERE primary_id = $kursId AND attr_id = $id_rollstuhlgerecht" );
}