<?php



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
	for( $i = 0; $i < sizeof($in); $i++ )
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
		
		if( $old_avg_vollst != $new_avg_vollst
		 || strval($settings['vollstaendigkeit.min'])    != strval($new_min_vollst) // compare against strval to differ between "" and "0" and force settings such values to "0"
		 || strval($settings['vollstaendigkeit.min.id']) != strval($new_min_vollst_id)
		 || strval($settings['vollstaendigkeit.max'])    != strval($new_max_vollst)  )
		{
			$settings['vollstaendigkeit.min']    = $new_min_vollst;
			$settings['vollstaendigkeit.min.id'] = $new_min_vollst_id;
			$settings['vollstaendigkeit.max']    = $new_max_vollst;
			$db2->query("UPDATE anbieter SET vollstaendigkeit=$new_avg_vollst, settings=".$db2->quote(implode_settings($settings))." WHERE id=$anbieter_id");
		}
		
		$total_anbieter++;
		$total_kurse += $kurse_cnt;
	}
	
	//$param['returnmsg'] .= "<br />$total_anbieter Anbietervollstaendigkeiten berechnet, $total_kurse Kurse beruecksichtigt"; // DEBUG ONLY
}



/*****************************************************************************
 * Kursstatus / Kursvollstaendigkeit bestimmen
 *****************************************************************************/


/* update_kurs_state() ...
 * ... schaltet Kurse abhängig vom Beginndatum automatisch frei
 *     oder entfernt diesen Status wieder
 * ... berechnet aufgrund des Beginndatums die Wochentage
 * ... setzt die PLZ und/oder den Stadtteil auf Grundlage von Strasse und Ort
 *
 *   !!  Diese Funktion wird auch aus dem Portal von edit.php aufgerufen  !!
 *   !!  Nicht davon ausgehen, daß das komplette CMS zur Verfügung steht  !!
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
	$test_datum = strftime("%Y-%m-%d 00:00:00", (time()-$tage*24*60*60)); // a data of a df must be _larger_ that this data to be "freigeschaltet" 
	$heute_datum = strftime("%Y-%m-%d 00:00:00");

	// alle durchfuehrungs IDs holen
	$durchf_ids = array();
	$db->query("SELECT secondary_id FROM kurse_durchfuehrung WHERE primary_id=$kurs_id ORDER BY structure_pos;");
	while( $db->next_record() )
	{
		$durchf_ids[] = $db->f('secondary_id');
	}
	$anz_durchf = sizeof($durchf_ids);

	// stichw. holen
	$anz_stichw = 0;
	$has_fernunterricht_stichw = false;
	$has_bildungsgutschein_stichw  = false;
	$has_aktivierungsgutschein_stichw  = false;
	$has_umschulung_stichw  = false;
	$einstieg_bis_kursende_moeglich = false;
	$db->query("SELECT attr_id FROM kurse_stichwort WHERE primary_id=$kurs_id;");
	while ( $db->next_record() )
	{
		switch( $db->f('attr_id') )
		{
			case 4112: case 7721:	$has_fernunterricht_stichw			= true; break;
			case 3207: 				$has_bildungsgutschein_stichw		= true; break;
			case 16311: 			$has_aktivierungsgutschein_stichw	= true; break;
			case 6013: 				$has_umschulung_stichw				= true; break;
			case 315:  				$einstieg_bis_kursende_moeglich		= true; break;
		}
		$anz_stichw ++;
	}

	if( $bu_nummer != '' ) $anz_stichw ++;
	if( $azwv_knr != '' )  $anz_stichw ++;
	if( $thema != 0 )	   $anz_stichw ++;
	
	// alle durchfuehrungen checken
	$baD					= array();
	$neuer_status			= 3; /*abgelaufen*/
	/*$neuer_status_gueltig	= true;*/
	for( $i = 0; $i < $anz_durchf; $i++ )
	{
		$db->query("SELECT beginn, beginnoptionen, ende, kurstage, preis, strasse, plz, ort, stadtteil, bemerkungen, dauer, dauer_fix, tagescode, nr, stunden, teilnehmer, zeit_von, zeit_bis FROM durchfuehrung WHERE id={$durchf_ids[$i]};");
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
			
			$update = '';
			
			// beginnstatus überprüfen
			/* -- 11:54 18.12.2012: Fehlende Beginndaten führen nicht mehr zu freigeschalteten Kursen!
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
			 /* || ($beginnoptionen >= 4) -- 11:55 18.12.2012: Irgendwelche Optionen führen nicht mehr zu freigeschalteten Kursen! */ )
			{
				$neuer_status = 1; /*freigeschaltet*/
			}
			else if( $beginn == '' || $beginn == '0000-00-00 00:00:00' )
			{
				if( $beginnoptionen >= 1 )
					$neuer_status = 1; /*freigeschaltet*/ /*Änderung vom 4.01.2013: Kurse ohne Datum mit Option sind freigeschaletet, das ist mit beate noch nicht geklärt*/
			}
			else if( $einstieg_bis_kursende_moeglich ) 
			{
				/*EDIT 22:54 24.04.2013: Kurse mit dem Stichwort 315/"Einstieg bis Kursende möglich" sind freigeschaltet solange der Kurse nicht zu Ende ist; 
					gibt es kein Endedatum, hat die Option keine Auswirkung */
				if( $ende != '' && $ende != '0000-00-00 00:00:00' ) {
					if( $ende >= $heute_datum ) {
						$neuer_status = 1;
					}
				}
			}
		
			// wochentage überprüfen
			if( $wochentage == 0 )
			{
				$wochentage = berechne_wochentage($beginn, $ende);
				if( $wochentage )
				{
					$update .= ($update==''? '' : ', ') . " kurstage=$wochentage ";
					$ret['returnmsg'] .= ($ret['returnmsg']? '<br />' : '') . 'Durchf&uuml;hrung '.($i+1).': Wochentage anhand des Beginn-/Endedatums gesetzt.';
					$ret['returnreload'] = true;
				}
			}
			
			// tagescode überprüfen
			$neuer_tagescode = berechne_tagescode($zeit_von, $zeit_bis, $wochentage);
			if( $neuer_tagescode != 0 && $neuer_tagescode != $alter_tagescode )
			{
				$update .= ($update==''? '' : ', ') . " tagescode=$neuer_tagescode ";
				//if( $alter_tagescode != 0 ) 20:32 30.01.2014: die entsprechende Nachricht wird immer ausgegeben. Warum auch nicht? Man wundert sich sonst, warum ein eigentlich nicht geänderter Datensatz be Klick auf "Übernehmen" tatsächlich gespeichert wird ... [**]
					$ret['returnmsg'] .= ($ret['returnmsg']? '<br />' : '') . 'Tagescode anhand Uhrzeit/Wochentage korrigiert. <a href="https://b2b.kursportal.info/index.php?title=Berechnung_des_Tagescode" target="_blank">Weitere Informationen hierzu...</a>';
				$ret['returnreload'] = true;
			}

			// dauer überprüfen (06.12.2012: wenn kein Wert berechnet werden konnte, alten (evtl. manuell gesetzten) Wert lassen)
			$neue_dauer = berechne_dauer(str_replace("00:00:00", $zeit_von.":00", $beginn), str_replace("00:00:00", $zeit_bis.":00", $ende));
			if( $neue_dauer != 0 && $neue_dauer != $alte_dauer && !$dauer_fix)
			{
				$update .= ($update==''? '' : ', ') . " dauer=$neue_dauer ";
				//if( $alte_dauer != 0 ) 20:32 30.01.2014: die entsprechende Nachricht wird immer ausgegeben, s. [**]
					$ret['returnmsg'] .= ($ret['returnmsg']? '<br />' : '') . 'Dauer anhand Beginn-/Endedatum korrigiert. <a href="https://b2b.kursportal.info/index.php?title=Berechnung_der_Dauer" target="_blank">Weitere Informationen hierzu...</a>';
				$ret['returnreload'] = true;
			}
			
			// PLZ/Stadtteil ueberpruefen
			if( $param['set_plz_stadtteil'] )
			{
				if( !is_object($GLOBALS['plztool']) ) { $GLOBALS['plztool'] = new PLZTOOL_CLASS(); } 				
				$plzetc = $GLOBALS['plztool']->search_plzstadtteil_by_strort($strasse, $ort);
				
				if( is_array( $plzetc ) )
				{
					$setmsg = '';
					$cannotsetmsg = '';
					
					if( $plz == ''
					 || ($param['set_plz_stadtteil']==2 && $plzetc['plz']!='' && $plz!=$plzetc['plz']) )
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

					if( $stadtteil == ''
					 || ($param['set_plz_stadtteil']==2 && $plzetc['stadtteil']!='' && $plz!=$plzetc['stadtteil']) )
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

					if( $setmsg )		{ $ret['returnmsg'] .= ($ret['returnmsg']? '<br />' : '') . 'Durchf&uuml;hrung '.($i+1).': '.$setmsg.' anhand von Strasse/Ort gesetzt.'; $ret['returnreload'] = true; }
					if( $cannotsetmsg )	{ $ret['returnmsg'] .= ($ret['returnmsg']? '<br />' : '') . 'Durchf&uuml;hrung '.($i+1).': Für die gegeben Strasse/Ort sind unterschiedliche Werte für '.$cannotsetmsg.' möglich, die nicht automatisch ermittelt werden konnten.'; }
				}
			}
			
			// do update ...
			if( $update )
			{
				if( $param['write'] )
				{
					$db->query("UPDATE durchfuehrung SET $update WHERE id={$durchf_ids[$i]}");
				}
			}
			
			// vollstaendigkeit berechnen
			if( ($beginn != '' && $beginn != '0000-00-00 00:00:00') 
			 || ($beginnoptionen >= 1) 
			 || $has_fernunterricht_stichw )								$baD['beginn']++;
			if( ($ende != '' && $ende != '0000-00-00 00:00:00') 
			 || $has_fernunterricht_stichw )								$baD['ende']++;
			if( $plz != '' || $ort != '' || $has_fernunterricht_stichw )	$baD['plz']++;
			
			if( $preis != -1 || $has_bildungsgutschein_stichw || $has_aktivierungsgutschein_stichw || $has_umschulung_stichw )				
																			$baD['preis']++;

			if( $nr != '' )													$baD['nr']++;
			if( $strasse != '' || $has_fernunterricht_stichw )				$baD['strasse']++;
			if( $stunden > 0 )												$baD['stunden']++;
			if( $teilnehmer > 0 )											$baD['teilnehmer']++;
			if( $zeit_von != '' )											$baD['zeit_von']++;
			if( $zeit_bis != '' )											$baD['zeit_bis']++;
		}
	}
	
	// vollstaendigkeit berechnen
	$vmsg = '';
	$punkte_erreicht = 0.0;
	if( $anz_durchf > 0 )
	{																	// 50 punkte
		if( strlen($beschreibung)>strlen($titel) )	{ $punkte_erreicht += 12.5; } else { $vmsg .= "<br />- Um die Mindestvollständigkeit zu erreichen, stellen Sie bitte eine <b>Mindesttextlänge</b> sicher."; }	
		if( $baD['beginn']	== $anz_durchf )		{ $punkte_erreicht += 12.5; } else { $missing = $anz_durchf-$baD['beginn'];		$vmsg .= "<br />- Um die Mindestvollständigkeit zu erreichen, geben Sie bitte <b>alle Beginndaten</b> ein. Es fehlen aktuell $missing Beginndaten."; }
		if( $baD['plz'] 	== $anz_durchf )		{ $punkte_erreicht += 12.5; } else { $missing = $anz_durchf-$baD['plz']; 		$vmsg .= "<br />- Um die Mindestvollständigkeit zu erreichen, machen Sie bitte Angaben zu den <b>PLZ in allen Durchführungen</b>. Es fehlen aktuell $missing PLZ."; }
		if( $baD['preis']	== $anz_durchf ) 		{ $punkte_erreicht += 12.5; } else { $missing = $anz_durchf-$baD['preis']; 		$vmsg .= "<br />- Um die Mindestvollständigkeit zu erreichen, geben Sie bitte <b>alle Preise</b> ein. Es fehlen aktuell $missing Preise."; }
		if( $punkte_erreicht >= 50 )
		{																// 45 punkte
			if( $baD['nr'] 	== $anz_durchf )		{ $punkte_erreicht +=  2.0; } else { $missing = $anz_durchf-$baD['nr'];			$vmsg .= "<br />- Um die Vollständigkeit zu erhöhen, geben Sie bitte <b>alle Kursnummern</b> ein. Es fehlen aktuell $missing Kursnummern."; }
			if( $baD['stunden'] == $anz_durchf )	{ $punkte_erreicht += 10.0; } else { $missing = $anz_durchf-$baD['stunden'];	$vmsg .= "<br />- Um die Vollständigkeit zu erhöhen, geben Sie bitte <b>alle Stundenanzahlen</b> ein. Es fehlen aktuell $missing Angaben hierzu."; }
			if( $baD['ende'] == $anz_durchf )		{ $punkte_erreicht += 10.0; } else { $missing = $anz_durchf-$baD['ende'];		$vmsg .= "<br />- Um die Vollständigkeit zu erhöhen, geben Sie bitte <b>alle Enddaten</b> ein. Es fehlen aktuell $missing Daten."; }
			if( $baD['teilnehmer'] == $anz_durchf )	{ $punkte_erreicht += 10.0; } else { $missing = $anz_durchf-$baD['teilnehmer'];	$vmsg .= "<br />- Um die Vollständigkeit zu erhöhen, geben Sie bitte <b>alle Teilnehmendenanzahlen</b> ein. Es fehlen aktuell $missing Angaben hierzu."; }
			if( $baD['zeit_von'] == $anz_durchf )	{ $punkte_erreicht +=  5.0; } else { $missing = $anz_durchf-$baD['zeit_von'];	$vmsg .= "<br />- Um die Vollständigkeit zu erhöhen, geben Sie bitte <b>alle Beginnzeiten</b> ein. Es fehlen aktuell $missing Angaben hierzu."; }
			if( $baD['zeit_bis'] == $anz_durchf )	{ $punkte_erreicht +=  5.0; } else { $missing = $anz_durchf-$baD['zeit_bis'];	$vmsg .= "<br />- Um die Vollständigkeit zu erhöhen, geben Sie bitte <b>alle Endezeiten</b> ein. Es fehlen aktuell $missing Angaben hierzu."; }
			if( $baD['strasse'] == $anz_durchf )	{ $punkte_erreicht +=  3.0; } else { $missing = $anz_durchf-$baD['strasse'];	$vmsg .= "<br />- Um die Vollständigkeit zu erhöhen, machen Sie bitte Angaben zu den <b>Strassen in allen Durchführungen</b>. Es fehlen aktuell $missing Strassen."; }
																		
																		// 5 Punkte nach Freischaltung durch die Redaktion
			if( $anz_stichw >= 2 )					{ $punkte_erreicht +=  5.0; } else { if($vmsg=='') { $vmsg .= '<br />- Sie haben alle notwendigen Angaben zur Vollständigkeit gemacht; der Kurs wird als 100%-vollständig gelistet, sobald er von der Redaktion freigeschaltet wird.'; }	}
		}
	}
	else
	{
		$vmsg .= '<br />- Um die Mindestvollständigkeit zu erreichen, legen Sie bitte eine <b>Durchführung</b> an.';
	}
	$ret['vmsg'] = $vmsg;
	
	//if( $ret['vmsg'] ) { $ret['returnmsg'] .= ($ret['returnmsg']? '<br />' : '') . 'Informationen zur Vollst&auml;ndigkeit:' . $ret['vmsg']; }
	
	// neuen status schreiben
	if( ($alter_status == 1 /*freigesch.*/ || $alter_status == 3 /*abgel.*/)
	 /*&&  $neuer_status_gueltig */
	 &&  $neuer_status != $alter_status )
	{
		if( $param['write'] )
		{
			$db->query("UPDATE kurse SET freigeschaltet=$neuer_status WHERE id=$kurs_id;");
			$ret['returnreload'] = true;
		}
	}
	
	// neue vollständigkeit schreiben
	$punkte_erreicht = intval($punkte_erreicht);
	if( $punkte_erreicht < 1   ) $punkte_erreicht = 1;
	if( $punkte_erreicht > 100 ) $punkte_erreicht = 100;
	if( $punkte_erreicht != $alte_vollstaendigkeit )
	{
		if( $param['write'] )
		{
			$db->query("UPDATE kurse SET vollstaendigkeit=$punkte_erreicht WHERE id=$kurs_id;");
		}
		
		$ret['returnreload'] = true;
	}
	
	return $ret;
}



/*****************************************************************************
 * Trigger "main"
 *****************************************************************************/



function alle_freischaltungen_ueberpruefen()
{
	$db = new DB_Admin();
	$oneWeekAgo = strftime("%Y-%m-%d 00:00:00", time()-7*24*60*60); // 14:46 03.06.2014 wir überprüfen auch abgelaufene Kurse, die kürzlich geändert wurden - mag sein, dass z.B. duch MultiEdit etwas geändert wurde, das den Kurs wieder freigeschaltet werden lässt
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
	if( $param['action'] == 'afterinsert'
	 || $param['action'] == 'afterupdate' )
	{
		$uks = update_kurs_state($param['id'], array('from_cms'=>1, 'set_plz_stadtteil'=>1, 'write'=>1));
		if( $uks['returnmsg'] ) 	$param['returnmsg'] .= ($param['returnmsg']? '<br />' : '') . $uks['returnmsg'];
		if( $uks['returnreload'] )	$param['returnreload'] = true;

		// once a day, we're calling alle_freischaltungen_ueberpruefen() from here; however, since 12/2014 we're calling this function
		// also from wisy-sync-renderer-class.inc.php, so, if everything works well, the part below may be removed
		$lastUpdate = regGet('daily.freischaltung_ueberpruefen', '0000-00-00', 'template');
		if( $lastUpdate != strftime('%Y-%m-%d') )
		{
			set_time_limit(2*60*60 /*2 hours ...*/);
			ignore_user_abort(1);
			
			regSet('daily.freischaltung_ueberpruefen', strftime('%Y-%m-%d'), '0000-00-00', 'template');
			regSave();

			alle_freischaltungen_ueberpruefen();
			
			$param['returnmsg'] .= ($param['returnmsg']? '<br />' : '') . 'Alle Freischaltungen &uuml;berpr&uuml;ft.';
		}
	}
	
	return 1;
}


