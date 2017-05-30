<?php


global $g_def_anschreiben;
$g_def_anschreiben = <<<__EOT__
To: [[pflege_email]]
From: ihre@e-mail.de
Subject: Ihre Kursangebote bei WISY-hamburg.de

An
[[postname]]
[[strasse]]
[[plz]] [[ort]]
                                          Hamburg, im [[aktmonat]] [[aktjahr]]

Sehr geehrte(r) [[pflege_name]],

wie vereinbart bitten wir Sie in diesem Monat, ...

[[pflege_msg]]

Mit freundlichen Grüßen

Ihr Name

[[kursliste]]
__EOT__;



/*****************************************************************************
 * Funktionen aus dem Portal, Rueckgabe ASCII anstelle von HTML
 *****************************************************************************/



function wisy_datum($sqldatum)
{
	if( $sqldatum == '' || $sqldatum == '0000-00-00 00:00:00' )
	{
		return '';
	}
	else
	{
		$sqldatum = explode(' ', strtr($sqldatum, '-:', '  '));
		return $sqldatum[2] . '.' . $sqldatum[1] . '.' . substr($sqldatum[0], 2, 2);
	}
} 



function wisy_preis($preis, $sonderpreis, $sonderpreistage, $beginn, $preishinweise)
{
	if( $preis == -1 ) 
	{
		$ret = 'k.A.';
	}
	else if( $preis == 0 )
	{
		$ret = 'kostenlos';
	}
	else 
	{
		$ret = "$preis EUR";
		
		if( $preis>0
		 && $sonderpreis>0 
		 && $sonderpreis<$preis )
		{
			$ret .= ", {$sonderpreis} EUR ab {$sonderpreistage} Tage(n) vor Kursbeginn";
		}
	}

	if( $preishinweise )
	{
		$ret .= ", $preishinweise";
	}

	return $ret;
}


function wisy_durchfuehrung_tagescode($tagescode /*id or 'bu'*/, $addText = '')
{
	global $site;
	
	if( $tagescode )
	{
		global $codes_tagescode_array;
		if( !is_array($codes_tagescode_array) ) 
		{	
			global $codes_tagescode;
			if( !is_string($codes_tagescode) ) {
				require_once('admin/config/codes.inc.php');
			}
			
			$codes_tagescode_array = array();
			$temp = explode('###', $codes_tagescode);
			for( $i = 0; $i < sizeof($temp); $i+=2 ) {
				$codes_tagescode_array[$temp[$i]] = $site->htmldeentities($temp[$i+1]);
			}
			$codes_tagescode_array['bu'] = 'Bildungsurlaub';
		}
		
		$title = $codes_tagescode_array[$tagescode];
		
		return trim("$addText $title");
	}
	else
	{
		return "k.A.";
	}
}




function wisy_durchfuehrung_beginnoptionen($opt)
{
	global $codes_beginnoptionen_array;
	global $site;
	
	if( !is_array($codes_beginnoptionen_array) ) 
	{	
		global $codes_beginnoptionen;
		if( !is_string($codes_beginnoptionen) ) {
			require_once('admin/config/codes.inc.php');
		}
		
		$codes_beginnoptionen_array = array();
		$temp = explode('###', $codes_beginnoptionen);
		for( $i = 0; $i < sizeof($temp); $i+=2 ) {
			$codes_beginnoptionen_array[$temp[$i]] = $site->htmldeentities($temp[$i+1]);
		}
	}

	if( $opt <= 0 ) {
		return '';
	}
	else if( $codes_beginnoptionen_array[$opt] ) {
		return $codes_beginnoptionen_array[$opt];
	}
	else {
		return '';
	}
}



function wisy_dauer($dauer, $stunden) // return as ASCII
{
	// dauer
	global $site;
	global $codes_dauer_array;
	if( !is_array($codes_dauer_array) ) 
	{	
		global $codes_dauer;
		$codes_dauer_array = array();
		$temp = explode('###', $codes_dauer);
		for( $i = 0; $i < sizeof($temp); $i+=2 ) {
			$codes_dauer_array[$temp[$i]] = $site->htmldeentities($temp[$i+1]);
		}
	}

	if( $dauer <= 0 ) {
		$dauer = '';
	}
	else if( $codes_dauer_array[$dauer] ) {
		$dauer = $codes_dauer_array[$dauer];
	}
	else {
		$dauer = "$dauer Tage";
	}
	
	// stunden
	if( $stunden > 0 ) {
		$stunden = "$stunden Std.";
	}	
	else {
		$stunden = '';
	}
	
	// done
	if( $dauer != '' && $stunden != '' ) {
		return "$dauer ($stunden)";
	}
	else if( $dauer != '' ) {
		return $dauer;
	}
	else if( $stunden != '' ) {
		return $stunden;
	}
	else {
		return 'k.A.';
	}
}



function wisy_durchfuehrung_kurstage($kurstage)
{
	global $codes_kurstage_array;
	
	if( !is_array($codes_kurstage_array) ) 
	{	
		global $codes_kurstage;
		if( !is_string($codes_kurstage) ) {
			require_once('admin/config/codes.inc.php');
		}
		
		$codes_kurstage_array = array();
		$temp = explode('###', $codes_kurstage);
		for( $i = 0; $i < sizeof($temp); $i+=2 ) {
			$codes_kurstage_array[intval($temp[$i])] = trim($temp[$i+1]);
		}
	}

	$c = 0;
	reset($codes_kurstage_array);
	while( list($value, $descr) = each($codes_kurstage_array) ) {
		if( $kurstage & $value ) {
			$c++;
		}
	}

	$ret = '';
	reset($codes_kurstage_array);
	while( list($value, $descr) = each($codes_kurstage_array) ) {
		if( $kurstage & $value ) {
			$ret .= $ret? ($c==1? ' und ' : ', ') : '';
			$ret .= $descr;
			$c--;
		}
	}
	
	return $ret;
}



/*****************************************************************************
 * WIKI 2 Text (schneller, aber fuer diese Faelle ausreichender Hack)
 *****************************************************************************/



function wiki2txt($wiki, $linew)
{
	$wiki = str_replace("[[", "", $wiki);
	$wiki = str_replace("]]", "", $wiki);
	$wiki = str_replace("''''", "", $wiki);
	$wiki = str_replace("'''", "", $wiki);
	$wiki = str_replace("''", "", $wiki);
	$wiki = str_replace("\r", "", $wiki);
	
	$wiki = trim($wiki);
	while( !(strpos($wiki, "\n\n")===false) )
	{
		$wiki = str_replace("\n\n", "\n", $wiki);
		$wiki = trim($wiki);
	}

	while( !(strpos($wiki, "  ")===false) )
	{
		$wiki = str_replace("  ", " ", $wiki);
		$wiki = trim($wiki);
	}
	
	$wiki = wordwrap($wiki, $linew);
	
	return $wiki;
}



/*****************************************************************************
 * Sonstige Formatierungen
 *****************************************************************************/




global $g_monate;
$g_monate =   '1###Januar###'
			 .'2###Februar###'
			 .'3###Maerz###'
			 .'4###April###'
			 .'5###Mai###'
			 .'6###Juni###'
			 .'7###Juli###'
			 .'8###August###'
			 .'9###September###'
			 .'10###Oktober###'
			 .'11###November###'
			 .'12###Dezember';

global $g_pflege_wege;
$g_pflege_wege =  '0###alle###'
				.'1###nur Redaktionelle Eingabe###'
				.'2###nur E-Mail###'
				.'4###nur Online###'
				.'8###nur Import###'
				.'-8###nicht Import';

global $g_nonFilenameChars;
$g_nonFilenameChars = array(
	"ä" =>"ae", 
	"ö" =>"oe", 
	"ü" =>"ue", 
	"Ä" =>"Ae", 
	"Ö" =>"Oe", 
	"Ü" =>"Ue", 
	"ß" =>"ss",
	"-" =>" ",
	"+" =>" ",
	"*" =>" ",
	"?" =>" ",
	"!" =>" ",
	"<" =>" ",
	">" =>" ",
	"(" =>" ",
	")" =>" ",
	"[" =>" ",
	"]" =>" ",
	"{" =>" ",
	"}" =>" ",
	"|" =>" ",
	"\""=>" ",
	"'" =>" ",
	"." =>" ",
	"," =>" ",
	":" =>" ",
	";" =>" ",
	"&" =>" ",
	"/" =>" ",
	"\\"=>" ",
);



function getFilename($str)
{
	global $g_nonFilenameChars;
	$str = strtr($str, $g_nonFilenameChars);
	
	$str = trim($str);
	while( !(strpos($str, "  ")===false) )
	{
		$str = str_replace("  ", " ", $str);
		$str = trim($str);
	}
	
	$str = str_replace(" ", "_", $str);
	$str = strtolower($str);
	
	return $str;
}



/*****************************************************************************
 * Die Export-Klasse
 *****************************************************************************/




class EXP_FORMATPFLEGEPROT_CLASS extends EXP_PLUGIN_CLASS
{
	function __construct()
	{
		parent::__construct();
	
		global $g_monate;
		global $g_def_anschreiben;
		global $g_pflege_wege;

		$this->linewidth = 75;

		$this->remark = 'Dieses Exportmodul erzeugt ein Archiv mit Anschreiben für alle Anbieter, die in dem ausgewählten Monat angeschrieben werden sollen (Wert aus <i>Anbieter.Pfegekontakt.Aktualisieren</i>).<br /><br />'
		.	'Optional k&ouml;nnen die Anschreiben auch gleich per EMail an die unter <i>Anbieter.Pflegekontakt.Pflege EMail</i> angegebene EMail-Adresse versandt werden.';
	
		
		$this->options['anschreiben']	=	array('textarea', 'Anschreiben', $g_def_anschreiben);
		$this->options['dummy1']		=	array('remark', '<a href="https://b2b.kursportal.info/index.php?title=Pflegeprotokoll" target="_blank">Beispiel und <b>wichtige Hinweise</b> zur Formatierung ...</a>');
		$this->options['monat'] 		=	array('enum', 'Monat', strftime("%m"), $g_monate);
		$this->options['pflege_weg']	=	array('enum', 'Pflegeweg', -8, $g_pflege_wege);	
		$this->options['abgel']			=	array('check', 'Abgelaufene Kurse hinzufügen', 1);
		$this->options['baseurl']		=	array('text', 'Basis-URL für Verweise', 'http:/'.'/hamburg.kursportal.info', 50);
		$this->options['dummy2']		=	array('remark', 'Verwenden Sie für die Basis-URL bitte die kurzmöglichste funktionierende Form.');
		
		$allgroups = acl_get_all_groups();
		for( $i = 0; $i < sizeof($allgroups); $i++ )
		{
			$this->options["group{$allgroups[$i][0]}"] = array('check', 
				$i==sizeof($allgroups)-1? $allgroups[$i][1] : "{$allgroups[$i][1]} " /*trailing space->no wrap*/,
				1, 
				$i==0? 'Anbietergruppen' : '');
		}		
		
		$this->options['send']		=	array('enum', 'E-Mails versenden', 0, '0###Nein, keine E-Mails versenden###1###Ja, E-Mails an ALLE ANBIETER versenden');
		
	}

	function log($txt1, $txt2 = '')
	{
		global $site;
		if( $txt2==='' ) {
			$this->dumps[0] .= $site->htmldeentities($txt1) . "\n";
		}
		else {
			$txt1 = substr($site->htmldeentities($txt1), 0, 35);
			$this->dumps[0] .= str_pad($txt1.' ', 38, '.') . ' '. $site->htmldeentities($txt2) . "\n";
		}
	}


	//
	// process the template
	// 
	function processTemplateReplace($matches)
	{
		$var = $matches[1];
		
		if( isset($this->vars[$var]) )
		{
			return $this->vars[$var];
		}
		else
		{
			$this->log("Unbekanntes Feld [[$var]].");
			return "[[$var]]";
		}
	}
	function processTemplate()
	{
		return preg_replace_callback("/\[\[(.*?)\]\]/", array(&$this, "processTemplateReplace"), $this->template);
	}


	//
	// get kurs information
	//
	function getAnbieterKurseAsArray($anbieterId)
	{
		$ret = array();
		$k = 0;
		$db1 = new DB_Admin;
		$db = new DB_Admin;

		if( $this->param['abgel'] )
			$freigeschaltet = '(freigeschaltet=1 OR freigeschaltet=4 OR freigeschaltet=3)';
		else
			$freigeschaltet = '(freigeschaltet=1 OR freigeschaltet=4)';
		
		$db1->query("SELECT id, titel, beschreibung, freigeschaltet FROM kurse WHERE anbieter=$anbieterId AND $freigeschaltet ORDER BY titel_sorted");
		while( $db1->next_record() )
		{
			$ret[$k] = array();
			$ret[$k]['id'] = $db1->f('id');
			$ret[$k]['titel'] = $db1->fs('titel');
			$ret[$k]['beschreibung'] = trim($db1->fs('beschreibung'));
			$ret[$k]['freigeschaltet'] = $db1->f('freigeschaltet');
			
			$durchf = array();
			$db->query("SELECT secondary_id FROM kurse_durchfuehrung WHERE primary_id={$ret[$k]['id']}");
			while( $db->next_record() )
			{
				$durchf[] = $db->f('secondary_id');
			}

			// stichwoerter - Abschluss (1), Foerderungsart (2), Qualitaetszertifikat (4)
			$bu = 0;
			$stichwortIds = array();
			$db->query("SELECT attr_id FROM kurse_stichwort WHERE primary_id={$ret[$k]['id']}");
			while( $db->next_record() )
			{
				$stichwortId = $db->f('attr_id');
				if( $stichwortId == 1 ) {
					$bu = 1;
				}
				else {
					$stichwortIds[] = $stichwortId;
				}
			}
			
			$ret[$k]['abschluss'] = '';
			$ret[$k]['foerderung'] = '';
			$ret[$k]['qzertifikat'] = '';
			for( $i = 0; $i < sizeof($stichwortIds); $i++ )
			{
				$db->query("SELECT stichwort, eigenschaften FROM stichwoerter WHERE id={$stichwortIds[$i]}");
				$db->next_record();
				$stichwort = $db->fs('stichwort');
				$eigenschaften = intval($db->f('eigenschaften'));
				if( $eigenschaften == 1 )
				{
					$ret[$k]['abschluss'] .= ($ret[$k]['abschluss']? ', ' : '') . $stichwort;
				}
				else if( $eigenschaften == 2 )
				{
					$ret[$k]['foerderung'] .= ($ret[$k]['foerderung']? ', ' : '') . $stichwort;
				}
				else if( $eigenschaften == 4 )
				{
					$ret[$k]['qzertifikat'] .= ($ret[$k]['qzertifikat']? ', ' : '') . $stichwort;
				}
			}
			
			// durchfuehrungen
			$ret[$k]['durchf'] = array();
			for( $d = 0; $d < sizeof($durchf); $d++ )
			{
				$db->query("SELECT beginn, beginnoptionen, ende, zeit_von, zeit_bis, dauer, stunden, kurstage, tagescode, preis, preishinweise, sonderpreis, sonderpreistage, strasse, plz, ort, stadtteil, land, teilnehmer, nr FROM durchfuehrung WHERE id={$durchf[$d]}");
				$db->next_record();
				$ret[$k]['durchf'][$d] = array();
				
				// make NR readable
				$nr = $db->f('nr');
				if( $nr=="" )
				{
					$nr = "k.A.";
				}
				$ret[$k]['durchf'][$d]['nr'] = $nr;
				

				// make TERMIN readable
				$termin = "";
				$beginnsql		= $db->f('beginn');
				$beginn			= wisy_datum($beginnsql);
				$beginnoptionen = wisy_durchfuehrung_beginnoptionen($db->f('beginnoptionen'));
				$ende			= wisy_datum($db->f('ende'));
				$zeit_von		= $db->f('zeit_von'); if( $zeit_von=='00:00' ) $zeit_von = '';
				$zeit_bis		= $db->f('zeit_bis'); if( $zeit_bis=='00:00' ) $zeit_bis = '';
				
				if( $beginn )
				{
					$termin .= $ende? "$beginn-$ende" : $beginn;
					if( $beginnsql<strftime("%Y-%m-%d 00:00:00") ) {
						$termin .= " (Termin ist abgelaufen)";
					}
					if( $beginnoptionen ) { $termin .= ", ($beginnoptionen)"; }
				}
				else if( $beginnoptionen )
				{
					$termin .= $beginnoptionen;
				}
				else
				{
					$termin .= 'k.A.';
				}
					
				if( $zeit_von && $zeit_bis ) {
					$termin .= ", $zeit_von-$zeit_bis Uhr"; 
				}
				else if( $zeit_von ) {
					$termin .= ", $zeit_von Uhr"; 
				}
				
				$ret[$k]['durchf'][$d]['termin'] = $termin;

				// make DAUER readable
				$ret[$k]['durchf'][$d]['dauer'] = wisy_dauer($db->f('dauer'), $db->f('stunden'));
				
				// make ART readable
				$kurstage = wisy_durchfuehrung_kurstage(intval($db->f('kurstage')));
				$ret[$k]['durchf'][$d]['art'] = wisy_durchfuehrung_tagescode($bu? 'bu' : $db->f('tagescode'), $kurstage);
				
				// make PREIS readable
				$ret[$k]['durchf'][$d]['preis'] = wisy_preis($db->f('preis'), $db->f('sonderpreis'), $db->f('sonderpreistage'), $db->f('beginn'), $db->fs('preishinweise'));
				
				// make ORT readable
				$ort		= $db->fs('ort');
				$stadtteil	= $db->fs('stadtteil');
				if( $ort && $stadtteil ) {
					if( strpos($ort, $stadtteil)===false ) {
						$ort = $ort . '-' . $stadtteil;
					}
					else {
						$ort = $ort;
					}
				}
				else if( $ort ) {
					$ort = $ort;
				}
				else if( $stadtteil ) {
					$ort = isohtmlentities($stadtteil);
				}
				else {
					$ort = '';
				}
				
				$cell = '';
				
				if( $db->f('strasse') ) {
					$cell = $db->fs('strasse');
				}
				
				if( $ort ) {
					$plz = $db->fs('plz');
					$cell .= $cell? ', ' : '';
					$cell .= trim("$plz $ort");
				}
	
				if( $db->f('land') ) {
					$cell .= $cell? ', ' : '';
					$cell .= $db->fs('land');
				}
	
				if( $db->f('teilnehmer') ) {
					$cell .= $cell? ', ' : '';
					$cell .= 'max. ' . intval($db->f('teilnehmer')) . ' Teilnehmende';
				}
	
				$ret[$k]['durchf'][$d]['ort'] = $cell? $cell : 'k. A.';
			}	
			
			$k++;
		}
		
		return $ret;
	}
	
	function getAnbieterKurseAsText($anbieterId, $details)
	{
		$ret = "";
		
		$kurse = $this->getAnbieterKurseAsArray($anbieterId);
		for( $k = 0; $k < sizeof($kurse); $k++ )
		{
			if( $k )
			{
				$ret .= "\n\n\n";
			}
		
			// titel...
			$titel = $kurse[$k]['titel'];
			if( $kurse[$k]['freigeschaltet'] == 3 )
				$titel .= ' (Abgelaufen)';
			$ret .= wordwrap($titel, $this->linewidth);
			
			// url...
			$url = $this->param['baseurl'] . "/k{$kurse[$k]['id']}";
			$line = " $url ==";
			$line = str_repeat("=", $this->linewidth-strlen($line)) . $line;
			$ret .= "\n" . $line;
			
			// text...
			if( $details ) {
				$ret .= "\n";
				$ret .= wiki2txt($kurse[$k]['beschreibung'], $this->linewidth);
				$ret .= "\n";
			}
			
			// stichwoerter...
			if( $kurse[$k]['abschluss'] ) {
				$ret .= "\nAbschluss:   " . $kurse[$k]['abschluss'];
			}

			if( $kurse[$k]['qzertifikat'] ) {
				$ret .= "\nQualitätsz.: " . $kurse[$k]['qzertifikat'];
			}

			if( $kurse[$k]['foerderung'] ) {
				$ret .= "\nFörderung:   " . $kurse[$k]['foerderung'];
			}

			// durchfuehrungen...
			for( $d = 0; $d < sizeof($kurse[$k]['durchf']); $d++ )
			{
				$ret .= "\nAng.-Nr:     " . $kurse[$k]['durchf'][$d]['nr'];
				$ret .= "\n  Termin:    " . $kurse[$k]['durchf'][$d]['termin'];
				$ret .= "\n  Dauer:     " . $kurse[$k]['durchf'][$d]['dauer'];
				$ret .= "\n  Art:       " . $kurse[$k]['durchf'][$d]['art'];
				$ret .= "\n  Preis:     " . $kurse[$k]['durchf'][$d]['preis'];
				$ret .= "\n  Ort:       " . $kurse[$k]['durchf'][$d]['ort'];
			}
			
			$ret .=     "\nArbeitsziel: [ ] keine Änderung  [ ] löschen  [ ] online geändert";
			$ret .=     "\n             [ ] bitte wie hier notiert ändern  [ ] lt. Anlage ändern";
		}
		
		return $ret;
	}


	//
	// process a single anbieter
	// 
	function processAnbieter($anbieterId)
	{
		global $g_monate;
		$monateArray = explode('###', $g_monate);
	
		// get common information
		$this->vars = array();
		$db1 = new DB_Admin;
		$db1->query("SELECT pflege_prot, pflege_weg, pflege_akt, user_grp, suchname, postname, strasse, plz, ort, pflege_name, pflege_email, pflege_msg, freigeschaltet FROM anbieter WHERE id=$anbieterId");
		$db1->next_record();
		
		$this->vars['suchname']			= $db1->fs('suchname');
		$this->vars['postname']			= $db1->fs('postname');
		$this->vars['strasse']			= $db1->fs('strasse');
		$this->vars['plz']				= $db1->fs('plz');
		$this->vars['ort']				= $db1->fs('ort');
		$this->vars['pflege_name']		= $db1->fs('pflege_name');
		$this->vars['pflege_email']		= $db1->fs('pflege_email');
		$this->vars['pflege_msg']		= $db1->fs('pflege_msg');
		$this->vars['aktmonat'] 		= $monateArray[(strftime("%m") * 2)-1];
		$this->vars['aktjahr'] 			= strftime("%Y");
		$this->vars['kursliste']		= $this->getAnbieterKurseAsText($anbieterId, intval($db1->f('pflege_prot'))==2? 1 : 0 /*details?*/);
		$this->vars['verweis_anbieter']	= $this->param['baseurl'] . "/a$anbieterId";
		$this->vars['verweis_login']	= $this->param['baseurl'] . "/edit?as=$anbieterId";
		
		$monatsnamen = array('Jan.', 'Feb.', 'Maerz', 'Apr.', 'Mai', 'Juni', 'Juli', 'Aug.', 'Sept.', 'Okt.', 'Nov.', 'Dez.');
		$pflege_akt = $db1->fs('pflege_akt');
		$this->vars['monate'] = '';
		for( $i = 0; $i < 12; $i++ ) {
			if( $pflege_akt & (1<<$i) ) {
				$this->vars['monate'] .= ($this->vars['monate']? ', ' : '') . $monatsnamen[$i];
			}
		}
		
		$record_pflege_weg = intval($db1->f('pflege_weg'));
		
		if( intval($this->param['group'.$db1->f('user_grp')])==0 )
		{
			$this->log("Anbieter ID $anbieterId nicht über die Anbietergruppe ausgewählt -> kein Protokoll.");
			return 0;
		}
		else if( intval($db1->f('freigeschaltet')) != 1 && intval($db1->f('freigeschaltet')) != 4 )
		{
			$this->log("Anbieter ID $anbieterId nicht freigeschaltet -> kein Protokoll.");
			return 0;
		}
		else if( $this->vars['kursliste'] == "" )
		{
			$this->log("Anbieter ID $anbieterId hat keine Kurse -> kein Protokoll.");
			return 0;
		}

		switch( $this->param['pflege_weg'] )
		{
			case -8: /*NICHT import*/
				if( $record_pflege_weg==8  ) 
				{
					$this->log("Anbieter ID $anbieterId hat Pflegeweg \"Import\" -> kein Protokoll.");
					return 0;
				}
				break;
				
			case 0: /*alle*/
				break;
			
			default: /*NUR spezieller pflegeweg*/
				if( $record_pflege_weg!=$this->param['pflege_weg']  ) 
				{
					$this->log("Anbieter ID $anbieterId hat nicht den ausgewaehten Pflegeweg -> kein Protokoll.");
					return 0;
				}
				break;
		}
		
		if( $this->vars['pflege_name'] == "" )
		{
			$this->vars['pflege_name'] = "Damen und Herren";
		}
		
		$this->names[$this->d] = getFilename($this->vars['suchname']) . "_id$anbieterId.txt";
	
		$this->dumps[$this->d] = $this->processTemplate();
		
		if( $this->param['send'] )
		{
			// get the text to send, this contains the header with all information needed
			$text = $this->dumps[$this->d];
			$text = str_replace("\r", "", $text);
			
			// split the header as an array from the text
			if( ($emptylinepos=strpos($text, "\n\n"))===false )
			{
				$this->log("FEHLER: Ungueltiger Header im Template (Header muss durch eine Leerzeile abgeschlossen sein).");
				return 0;
			}
			
			$header = explode("\n", substr($text, 0, $emptylinepos));
			$text = trim(substr($text, $emptylinepos));
			
			// see what's in the header
			$to = '';
			$from = '';
			$subject = '';
			for( $i = 0; $i < sizeof($header); $i++ )
			{
				$currline = trim($header[$i]);
				if( ($doubleptpos=strpos($currline, ':'))!==false )
				{
					$currtype = strtolower(substr($currline, 0, $doubleptpos));
					$currline = trim(substr($currline, $doubleptpos+1));
					if( $currtype == 'to' ) $to = $currline;
					if( $currtype == 'from' ) $from = $currline;
					if( $currtype == 'subject' ) $subject = $currline;
				}
			}
			
			if( $to == '' || $from == '' || $subject == '' )
			{
				$this->log("FEHLER: Ungueltiger Header im Template (\"From:\", \"To:\" oder \"Subject:\" nicht gefunden).");
				return 0;
			}
			
			if( !@mail($to, $subject, $text, "From: {$from}\r\nReply-To: {$from}") )
			{
				$this->log("FEHLER: E-Mail an $to wurde nicht zur Auslieferung akzeptiert.");
				return 0;
			}
		}
		
		return 1; // at least one kurs written
	}
	

	//
	// start the export
	//
	function export($param)
	{
		$this->param = $param;
		
		// init log
		global $g_monate;
		global $site;
		
		$temp = explode('###', $g_monate);
		$monatName = $temp[($this->param['monat'] * 2)-1];

		$query = "SELECT id FROM anbieter WHERE pflege_akt & " . (1<<($this->param['monat']-1)) . " AND typ=0";
		
		$this->dumps[0] = "";
		$this->names[0] = "_protokoll.txt";
		$this->log('Pflegeprotokoll vom '.strftime("%d.%m.%Y, %H:%M Uhr"));
		$this->log('Ausgewaehlter Monat: ' . $monatName);
		$this->log('Abgelaufene Kurse hinzufuegen: ' . ($this->param['abgel']? 'Ja' : 'Nein'));
		$this->log('EMails versenden: ' . ($this->param['send']? 'Ja' : 'Nein'));
		$this->log('SQL: ' . $query);
		$this->log('Programm von Bjoern Petersen - http://b44t.com');
		$this->log('=======================================================');
		$this->log('');
		
		// load template
		/*
		$fileName = "../files/hh/exp_format_pflegeprot/anschreiben.txt";
		$fileHandle = @fopen($fileName, "r");
		if( !$fileHandle )
		{
			$this->progress_abort( "Kann &quot;$fileName&quot; nicht &ouml;ffnen." );
		}
		$this->template = fread($fileHandle, 16000);//max. 16k
		fclose($fileHandle);
		*/
		$this->template = $this->param['anschreiben'];
		
		
		// go through all records
		
		$db1 = new DB_Admin;
		$db1->query($query);
		$this->d = 1;
		while( $db1->next_record() )
		{
			if( $this->processAnbieter($db1->f('id')) )
			{
				$this->d++;
			}
			
			$this->progress_info("{$this->d} Anbieter bearbeitet...");
		}
		
		// create the ZIP-file
		$zipfile = new EXP_ZIPWRITER_CLASS($this->allocateFileName('pflegeprotokoll-' . getFilename($site->htmldeentities($monatName)) . '.zip'));
		for( $i = 0; $i < sizeof($this->names); $i++ ) 
		{
			if( !$zipfile->add_data($this->dumps[$i], $this->names[$i]) )
				$this->progress_abort('cannot write zip');
		}
		if( !$zipfile->close() )
			$this->progress_abort('cannot close zip');
			
	}
}


