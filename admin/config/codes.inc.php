<?php


global $codes_tagescode;
$codes_tagescode =
	 '0######'					// 0=Berechnung noch nicht erfolgt ODER Berechnung ohne Ergebnis
	.'1###Ganztägig###'
	.'2###Vormittags###'
	.'3###Nachmittags###'
	.'4###Abends###'
	.'5###Wochenende';


global $codes_kurstage;
$codes_kurstage =
	 '1###Mo. ###'
	.'2###Di. ###'
	.'4###Mi. ###'
	.'8###Do. ###'
	.'16###Fr. ###'
	.'32###Sa. ###'
	.'64###So.';


global $codes_beginnoptionen;
$codes_beginnoptionen =
	 '0######'
	.'1###Beginnt laufend###'
	.'2###Beginnt wöchentlich###'
	.'4###Beginnt monatlich###'
	.'8###Beginnt zweimonatlich###'
	.'16###Beginnt quartalsweise###'
	.'32###Beginnt halbjährlich###'		// war vor 10/2011: 32: "Beginnt semesterweise"
	.'64###Beginnt jährlich###'
	//.'128###Laufender Einstieg###'		// war vor 10/2011: 128: "Beginn vereinbar"
	.'256###Termin noch offen###'		// war vor 10/2011: 256: "Beginn erfragen"
	.'512###Startgarantie';				// war vor 10/2011: 512: "Beginnt garantiert", vor 10/2012: Abgeschafft und ab 10/2012 wieder "Startgarantie" ...
	


global $codes_stichwort_eigenschaften;
$codes_stichwort_eigenschaften =
	 '0###Sachstichwort###'
	.'1###Abschluss###'
	.'2###Förderungsart###'
	.'4###Qualitätszertifikat###'
	.'8###Zielgruppe###'
	.'32768###Unterrichtsart###'		// hinzugefügt 2014-10-29 10:30 
	.'16###Abschlussart###'
	.'32###verstecktes Synonym###'
	.'64###Synonym###'
	.'128###Veranstaltungsort###'		// wird von der Redaktion/von Juergen verwendet - aber: wozu soll das sein? (bp)
	//.'256###Termin###'				// wird nicht verwendet - wozu soll das sein? (bp)
	.'256###Volltext Titel###'
	.'512###Volltext Beschreibung###'
	.'1024###Sonstiges Merkmal###'
	.'2048###Verwaltungsstichwort###'
	.'4096###Thema###'
	.'8192###Schlagwort nicht verwenden###'		// 8192 war mal "Hierarchie", "Schlagwort nicht verwenden" war mal bit 32 -- in beiden Fällen: wozu soll das sein? (bp)
	.'16384###Anbieterstichwort';

global $hidden_stichwort_eigenschaften;
$hidden_stichwort_eigenschaften = 32 + 128 + 256 + 512 + 2048 + 4096 + 8192;


global $codes_dauer;
$codes_dauer =	 
	 '0######'
	.'1###1 Tag###'
	.'2###2 Tage###'
	.'3###3 Tage###'
	.'4###4 Tage###'
	.'5###5 Tage###'
	.'6###6 Tage###'
	.'7###1 Woche###'
	.'14###2 Wochen###'
	.'21###3 Wochen###'
	.'28###4 Wochen###'
	.'35###5 Wochen###'
	.'42###6 Wochen###'
	.'49###7 Wochen###'
	.'56###8 Wochen###'
	.'63###9 Wochen###'
	.'70###10 Wochen###'
	.'77###11 Wochen###'
	.'84###12 Wochen###'
	.'30###1 Monat###'      // skipped by (*)
	.'60###2 Monate###'     // skipped
	.'90###3 Monate###' 
	.'120###4 Monate###'
	.'150###5 Monate###'
	.'180###6 Monate###'
	.'210###7 Monate###'
	.'240###8 Monate###'
	.'270###9 Monate###'
	.'300###10 Monate###'
	.'330###11 Monate###'
	.'360###12 Monate###'
	.'390###13 Monate###'
	.'420###14 Monate###'
	.'450###15 Monate###'
	.'480###16 Monate###'
	.'510###17 Monate###'
	.'540###18 Monate###'
	.'570###19 Monate###'
	.'600###20 Monate###'
	.'630###21 Monate###'
	.'660###22 Monate###'
	.'690###23 Monate###'
	.'720###24 Monate###'
	.'365###1 Jahr###'      // skipped
	.'730###2 Jahre###' 
	.'1095###3 Jahre###'
	.'1460###4 Jahre###'
	.'1825###5 Jahre###'
	.'2190###6 Jahre###'
	.'2555###7 Jahre###'
	.'2920###8 Jahre###'
	.'182###1 Semester###'  // skipped
	.'364###2 Semester###'  // skipped
	.'546###3 Semester###'  // skipped
	.'728###4 Semester###'  // skipped
	.'910###5 Semester###'  // skipped
	.'1092###6 Semester###' // skipped
	.'1274###7 Semester###' // skipped
	.'1456###8 Semester';   // skipped
				

/******************************************************************************
 * Berechnungen
 ******************************************************************************/
				

				
function berechne_tagescode($von, $bis, $kurstage)
{
	$tagescode = 0;
	
	if( $von!='' && $von!='00:00' && $bis!='' && $bis!='00:00' )
	{
		if( $von < '12:00' )
		{
			if( $bis <= '14:00' )
				$tagescode = 2; // vormittags
			else
				$tagescode = 1; // ganzer tag
		}
		else if( $von >= '17:00' )
		{
			$tagescode = 4; // abends
		}
		else
		{
			$tagescode = 3; // nachmittags
		}
	}

	if( !($kurstage&(1+2+4+8)) /*nicht: mo-do*/
	 && (($kurstage&32) || ($kurstage&64)) /*muss: sa oder so*/ )
	{
		$tagescode = 5; // wochenende
	}
	
	return $tagescode;
}

function berechne_wochentag($d)
{
	$d = strtr($d, ' :', '--');
	$d = explode('-', $d);
	$timestamp = mktime(0, 0, 0, intval($d[1]), intval($d[2]), intval($d[0]));
	if( $timestamp === -1 || $timestamp === false || intval($d[1])==0 || intval($d[2])==0 || intval($d[0])==0 )
		return 0;
	$d = getdate($timestamp);
	switch( intval($d['wday']) )
	{
		case 0:/*so*/	return 64;
		case 1:/*mo*/	return 1;
		case 2:/*di*/	return 2;
		case 3:/*mi*/	return 4;
		case 4:/*do*/	return 8;
		case 5:/*fr*/	return 16;
		case 6:/*sa*/	return 32;
		default:		return 0;
	}
}

function berechne_wochentage($beginn, $ende)
{
	$wochentag1 = berechne_wochentag($beginn);
	$wochentag2 = berechne_wochentag($ende=='0000-00-00 00:00:00'? $beginn : $ende);
	return $wochentag1|$wochentag2; /*einfache Berechnung - für  die komplexe, diese Zeile einfach auskommentieren*/ 
	
	if( $wochentag1==0 || $wochentag2 == 0 ) { return 0; /*fehler*/ }
	if( $wochentag1&(32+64) || $wochentag2&(32+64) ) { return $wochentag1|$wochentag2; /*wochenende-spezialfall*/ }
	for( $wochentage = 0, $curr = $wochentag1, $i = 0; $i < 7; $i++ )
	{
		$wochentage |= $curr;
		if( $curr == $wochentag2 ) break; /*done*/
		$curr = $curr * 2; /*nächstes wochentagssbit*/
		if( $curr > 64 ) $curr = 1; /*nach sonntag kommt montag*/
	}
	return $wochentage;
}

function berechne_dauer($start, $ende)
{
	// anzahl tage berechnen
	$d = strtr($start, ' :', '--');
	$d = explode('-', $d);
	$timestamp1 = mktime(0, 0, 0, intval($d[1]), intval($d[2]), intval($d[0]));
	if( $timestamp1 === -1 || $timestamp1 === false || intval($d[1])==0 || intval($d[2])==0 || intval($d[0])==0 )
		return 0;

	$d = strtr($ende, ' :', '--');
	$d = explode('-', $d);
	$timestamp2 = mktime(0, 0, 0, intval($d[1]), intval($d[2]), intval($d[0]));
	if( $timestamp2 === -1 || $timestamp2 === false || intval($d[1])==0 || intval($d[2])==0 || intval($d[0])==0 )
		return 0;
	
	if( $timestamp1 > $timestamp2 )
		return 0;
	
	$days = intval(($timestamp2 - $timestamp1)/86400) + 1;
	if( $days <= 0 )
		return 0;
	
	// auf die vorgegebenen Werte runden
	if( $days > 8 * 365.25 ) 
	{
		return 8 * 365; // ... 8 Jahre
	}
	else if( $days > 24 * 30.4 ) 
	{
		$years = round($days / 365.25);
		return $years * 365; // ... x Jahre
	}
	else if( $days > 12 * 7 ) 
	{
		$months = round($days / 30.4);
		return $months * 30; // x Monate
	}
	else if( $days > 7 )
	{
		$weeks = round($days / 7);
		return $weeks * 7;
	}
	else
	{
		return $days;
	}
}

