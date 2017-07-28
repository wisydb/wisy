<?php


	// deprecated
	$use_neweditor = true;
	if( $_COOKIE['oldeditor'] ) {	
		$use_neweditor = false;
	}
	// /deprecated


if( @file_exists('config/codes.inc.php') ) {
	require_once('config/codes.inc.php');
}
else if( @file_exists("../admin/config/codes.inc.php") ) {
	require_once('../admin/config/codes.inc.php');
}
else if( @file_exists("../../admin/config/codes.inc.php") ) {
	require_once('../../admin/config/codes.inc.php');
}
else {
	require_once('admin/config/codes.inc.php');  
}

define('USE_ROLES', 1);


//
// Table Definitions - This Area must be modified for Customisation
//
//////////////////////////////////////////////////////////////////////////////




/*** RATGEBER ***/
$ratgeber = new Table_Def_Class(TABLE_PRIMARY,				'glossar',			'Ratgeber');
if( $use_neweditor ) {
	$ratgeber->add_row(TABLE_ENUM,								'freigeschaltet',	'ABC-Index', 2, '2###Nein###1###Ja', '', array('layout.join'=>1));
}
$ratgeber->add_row(TABLE_TEXT|TABLE_SUMMARY|TABLE_LIST|TABLE_MUST|TABLE_UNIQUE,
															'begriff',			'Begriff', '', '', '', array('ctrl.size'=>'10-20-60', 'layout.bg.class'=>'e_bglite', 'layout.descr.class'=>'e_bolder', 'ctrl.class'=>'e_bolder'));
if( !$use_neweditor ) {
	$ratgeber->add_row(TABLE_ENUM,								'freigeschaltet',	'ABC-Index', 2, '2###Nein###1###Ja');
}
$ratgeber->add_row(TABLE_TEXTAREA|TABLE_WIKI|TABLE_NEWSECTION,'erklaerung',		'Erklärung', '', '', '', array('ctrl.rows'=>20));
$ratgeber->add_row(TABLE_TEXT,								'wikipedia',		'Stichw. Wikipedia', '', '', '', array('ctrl.size'=>'10-20-60'));
$ratgeber->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION,			'notizen',			'Journal', '', '', '', array('layout.section'=>1));



/*** THEMEN ***/
$themen = new Table_Def_Class(TABLE_SYNCABLE,				'themen',			'Themen');
$themen->add_row(TABLE_TEXT|TABLE_SUMMARY|TABLE_LIST|TABLE_MUST,
															'thema',			'Thema', '', '', '', array('ctrl.size'=>'10-20-60', 'layout.bg.class'=>'e_bglite', 'layout.descr.class'=>'e_bolder', 'ctrl.class'=>'e_bolder'));
$themen->add_row(TABLE_TEXT|TABLE_LIST|TABLE_MUST|TABLE_UNIQUE|TABLE_NEWSECTION,
															'kuerzel',			'Kürzel', 0, 0, 'Klassifizierung', array('ctrl.size'=>'4-10-40', 'layout.section'=>'Klassifizierung'));
$themen->add_row(TABLE_SATTR,								'glossar',			'Ratgeberseite', 0, $ratgeber);
$themen->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION,			'scope_note',		'Scope note');
$themen->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION,			'algorithmus',		'Algorithmus');
$themen->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION,			'notizen',			'Journal', '', '', '', array('layout.section'=>1));


/*** STICHWOERTER ***/
$stichwoerter = new Table_Def_Class(TABLE_PRIMARY|TABLE_SYNCABLE,			'stichwoerter',		'Stichwörter');
$stichwoerter->add_row(TABLE_TEXT|TABLE_SUMMARY|TABLE_LIST|TABLE_MUST|TABLE_UNIQUE,
																			'stichwort',		'Deskriptor', 0, 0, '', array('ctrl.size'=>'20-70', 'help.tooltip'=>'Deskriptor, ohne Erklärungen und so kurz und so einfach wie möglich', 'layout.bg.class'=>'e_bglite', 'layout.descr.class'=>'e_bolder', 'ctrl.class'=>'e_bolder'));
$stichwoerter->add_row(TABLE_TEXT,											'zusatzinfo',		'Zusatzinfo', 0, 0, '', array('ctrl.size'=>'20-70', 'help.tooltip'=>'Kurze Informationen zur evtl. notwendigen Erklärung'));
$stichwoerter->add_row(TABLE_MATTR|TABLE_SHOWREF,							'verweis',			'Synonym für', 0, 0 /*set below*/, '', array('ref.name'=>'Synonym von'));
$stichwoerter->add_row(TABLE_MATTR|TABLE_SHOWREF,							'verweis2',			'Oberbegriff für', 0, 0 /*set below*/, '', array('help.tooltip'=>'Das aktuelle Stichwort wird automatisch vergeben, wenn eines der hier angegebenen Stichwörter vergeben wird', 'ref.name'=>'Unterbegriff von'));
$stichwoerter->add_row(TABLE_ENUM|TABLE_SUMMARY|TABLE_LIST|TABLE_NEWSECTION,'eigenschaften',	'Typ', 0, $codes_stichwort_eigenschaften, 'Klassifizierung', array('layout.section'=>'Klassifizierung'));
$stichwoerter->add_row(TABLE_SATTR,											'thema',			'Thema', 0, $themen);
$stichwoerter->add_row(TABLE_SATTR,											'glossar',			'Ratgeberseite', 0, $ratgeber);
$stichwoerter->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION,						'scope_note',		'Scope note');
$stichwoerter->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION,						'algorithmus',		'Algorithmus');
$stichwoerter->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION,						'notizen',			'Journal', '', '', '', array('layout.section'=>1));
$stichwoerter->rows[2]->addparam = $stichwoerter;
$stichwoerter->rows[3]->addparam = $stichwoerter;




/*** ANBIETER ***/
$anbieter = new Table_Def_Class(TABLE_PRIMARY|TABLE_SYNCABLE,'anbieter',		'Anbieter');
if( $use_neweditor ) {
	$anbieter->add_row(TABLE_TEXT,								'din_nr',			'Wisy-Kundennr.', 0, 0, '',		array('ctrl.size'=>'4-40', 'layout.defhide'=>2, 'layout.join'=>1, 'layout.defhide.tooltip'=>'weitere Verwaltungsnummern'));
	$anbieter->add_row(TABLE_TEXT, 								'wisy_annr', 		'Kursnet-Nr.', 0, 0, '',		array('ctrl.size'=>'10-40', 'layout.defhide'=>2, 'layout.join'=>1));
	$anbieter->add_row(TABLE_TEXT, 								'bu_annr', 			'BU-Anbieternr.', 0, 0, '',		array('ctrl.size'=>'10-40', 'layout.defhide'=>2, 'layout.join'=>1));
	$anbieter->add_row(TABLE_TEXT, 								'foerder_annr', 	'Amtl. Gemeindeschl.',0,0, '',	array('ctrl.size'=>'10-40', 'layout.defhide'=>2, 'layout.join'=>1));
	$anbieter->add_row(TABLE_TEXT, 								'fu_annr', 			'FU-Anbieternr.', 0, 0, '',		array('ctrl.size'=>'10-40', 'layout.defhide'=>2, 'layout.join'=>1));
	$anbieter->add_row(TABLE_TEXT, 								'azwv_annr', 		'AZAV-Anbieternr.', 0, 0,'', 	array('ctrl.size'=>'10-40', 'layout.defhide'=>2, 'layout.join'=>1));
	$anbieter->add_row(TABLE_ENUM,								'freigeschaltet',	'Status', 1, '1###Freigegeben###2###Gesperrt', '', array('layout.join'=>1, 'layout.descr.hide'=>1));
	$anbieter->add_row(TABLE_INT|TABLE_EMPTYONNULL|TABLE_READONLY|TABLE_PERCENT,
																'vollstaendigkeit',	'Vollständigkeit', 0, '0###100', 0, array('layout.defhide'=>1, 'layout.after'=>'%', 'layout.join'=>1));
																// new field, add with: ALTER TABLE `anbieter` ADD `vollstaendigkeit` int(11) NOT NULL DEFAULT '0' AFTER `date_modified` ;
}
$anbieter->add_row(TABLE_TEXT|TABLE_SUMMARY|TABLE_LIST|TABLE_MUST|TABLE_UNIQUE,
															'suchname',			($use_neweditor?'Suchname':'Suchname '), '', '', '', array('ctrl.size'=>'20-80', 'layout.descr.class'=>'e_bolder', 'layout.bg.class'=>'e_bglite', 'ctrl.class'=>'e_bolder'));
if( !$use_neweditor ) {
	$anbieter->add_row(TABLE_ENUM,							'freigeschaltet',	'Status', 1, '1###Freigegeben###2###Gesperrt');
	$anbieter->add_row(TABLE_MATTR|TABLE_SHOWREF,			'verweis',			'Namensverweisung', 0, 0 /*set below*/, '', array('layout.join'=>1, 'layout.defhide'=>1));
}
$anbieter->add_row(TABLE_ENUM|TABLE_SUMMARY|TABLE_LIST|TABLE_NEWSECTION,
															'typ',				 'Typ', 0,
																				 '0###Anbieter###'
																				/*.'1###Trainer###' - entfernt, s. WISY_2014_TODO, 4-14*/
																				.'2###Beratungsstelle###'
																				.'64###Namensverweisung', 'Allgemein');
if( $use_neweditor ) {
	$anbieter->add_row(TABLE_MATTR|TABLE_SHOWREF,			'verweis',			'Namensverweisung', 0, 0 /*set below*/, '', array('layout.join'=>1, 'layout.defhide'=>1));
}
$anbieter->add_row(TABLE_TEXT,								'postname',			'Originalname',  '', '', '', array('ctrl.size'=>'20-80'));
$anbieter->add_row(TABLE_TEXT|TABLE_ACNESTSTART,			'strasse',			'Straße ', '', '', '', array('layout.descr'=>'Ort','ctrl.size'=>'8-16-48', 'ctrl.placeholder'=>1));
$anbieter->add_row(TABLE_TEXT|TABLE_ACNESTSTART|TABLE_ACNEST,'plz',				'PLZ ', '', 5, '', array('layout.after'=>' ', 'layout.descr.hide'=>1, 'ctrl.placeholder'=>1));
$anbieter->add_row(TABLE_TEXT|TABLE_ACNEST,					'ort',				'Ort ', '', '', '', array('layout.descr.hide'=>1, 'ctrl.placeholder'=>1, 'ctrl.size'=>'8-16-48'));
$anbieter->add_row(TABLE_TEXT|TABLE_ACNEST,					'stadtteil',		'Stadtteil ', '', '', '', array('ctrl.size'=>'8-16-48', 'layout.defhide'=>1, 'layout.defhide.tooltip'=>'weitere Ortsangaben'));
$anbieter->add_row(TABLE_TEXT|TABLE_ACNEST,					'land',				'Land ', '', 3, '', array('layout.defhide'=>1));
$anbieter->add_row(TABLE_FLAG,								'rollstuhlgerecht',	'Rollstuhlgerecht', '', '', '', array('layout.defhide'=>1));
$anbieter->add_row(TABLE_TEXT,								'leitung_name',		'Leitung', '', '', '', array('ctrl.placeholder'=>'Name'));
$anbieter->add_row(TABLE_TEXT|TABLE_TEL,					'leitung_tel',		'Leitung Telefon', '', '', '', array('layout.join'=>1, 'layout.descr.hide'=>1, 'ctrl.placeholder'=>'Telefon'));
if( !$use_neweditor ) {
	$anbieter->add_row(TABLE_SATTR|TABLE_TRACKDEFAULTS,			'thema',			'Thema', 0, $themen);
}
$anbieter->add_row(TABLE_MATTR|TABLE_TRACKDEFAULTS,			'stichwort',		'Stichwörter', 0, $stichwoerter, '', array());
if( !$use_neweditor ) {
	$anbieter->add_row(TABLE_TEXT,								'din_nr',			'Wisy-Kundennummer');
	$anbieter->add_row(TABLE_TEXT, 								'wisy_annr', 		'Kursnet-Nummer');
	$anbieter->add_row(TABLE_TEXT, 								'bu_annr', 			'BU-Anbieternummer');
	$anbieter->add_row(TABLE_TEXT, 								'foerder_annr', 	'Amtl. Gemeindeschl.');
	$anbieter->add_row(TABLE_TEXT, 								'fu_annr', 			'FU-Anbieternummer');
	$anbieter->add_row(TABLE_TEXT, 								'azwv_annr', 		'AZAV-Anbieternummer');
}
$anbieter->add_row(TABLE_INT|TABLE_EMPTYONNULL|TABLE_NEWSECTION,
															'gruendungsjahr',	'Gründungsjahr', 0, '0###2200' /*0=leer, daher muss der Bereich ab dort derzeit (11/2013) erlaubt sein*/, 'Firmenporträt', array('layout.section'=>'Firmenporträt'));
$anbieter->add_row(TABLE_ENUM,								'rechtsform',		'Rechtsform', 0,
																				 $codes_rechtsform, '', array('layout.join'=>1));
$anbieter->add_row(TABLE_TEXTAREA|TABLE_WIKI,				'firmenportraet',	'Firmenporträt');
$anbieter->add_row(TABLE_BLOB,								'logo',				'Logo', '', '', '', array('layout.bg.class'=>'e_bgbottom'));
$anbieter->add_row(TABLE_TEXT|TABLE_URL,					'homepage',			'Homepage', '', '', '', array('ctrl.size'=>'10-20-50'));
$anbieter->add_row(TABLE_DATE|TABLE_DAYMONTHOPT,			'pruefsiegel_seit',	'Prüfsiegel seit');
$anbieter->add_row(TABLE_TEXT|TABLE_NEWSECTION,				'anspr_name',		'Kundenberater', 0, 0, 'Kundenkontakt', array('ctrl.placeholder'=>'Name', 'layout.section'=>'Kundenkontakt'));
$anbieter->add_row(TABLE_TEXTAREA,							'anspr_zeit',		'Sprechzeiten', '', '', '', array('ctrl.rows'=>2));
$anbieter->add_row(TABLE_TEXT|TABLE_TEL,					'anspr_tel',		'Kunden Telefon');
$anbieter->add_row(TABLE_TEXT,								'anspr_fax',		'Kunden Fax', 0, 0, '', array('layout.join'=>1));
$anbieter->add_row(TABLE_TEXT|TABLE_URL,					'anspr_email',		'Kunden EMail', 0, 0, '', array('layout.join'=>1));
//$anbieter->add_row(TABLE_FLAG,								'kursplatzanfrage',	'Kursplatzanfrage'); // -- 11:53 04.02.2014 nicht mehr notwendig
//$anbieter->add_row(TABLE_FLAG,								'partnernetz',		'Partnernetz');  // -- 11:53 04.02.2014 nicht mehr notwendig
$anbieter->add_row(TABLE_TEXT|TABLE_NEWSECTION,				'pflege_name',		'Pflegepartner', 0, 0, 'Pflegekontakt', array('ctrl.placeholder'=>'Name', 'layout.section'=>'Pflegekontakt'));
$anbieter->add_row(TABLE_TEXT|TABLE_TEL,					'pflege_tel',		'Pflege Telefon');
$anbieter->add_row(TABLE_TEXT,								'pflege_fax',		'Pflege Fax', 0, 0, '', array('layout.join'=>1));
$anbieter->add_row(TABLE_TEXT|TABLE_URL,					'pflege_email',		'Pflege EMail', 0, 0, '', array('layout.join'=>1));
$anbieter->add_row(TABLE_ENUM,								'pflege_weg',		'Pflegeweg ', 1,
																				 '0######'
																				.'1###Redaktionelle Eingabe###'
																				.'2###EMail###'
																				.'4###Online###'
																				.'8###Import');
$anbieter->add_row(TABLE_ENUM,								'pflege_prot',		'Korrekturausdrucke', 1,
																				 '0######'
																				.'1###Listenausdruck###'
																				.'2###Seitenausdruck');
$anbieter->add_row(TABLE_BITFIELD,							'pflege_akt',		'Aktualisieren', 0,
																				 '1###Jan. ###'
																				.'2###Feb. ###'
																				.'4###März ###'
																				.'8###Apr. ###'
																				.'16###Mai ###'
																				.'32###Juni ###'
																				.'64###Juli ###'
																				.'128###Aug. ###'
																				.'256###Sep. ###'
																				.'512###Okt. ###'
																				.'1024###Nov. ###'
																				.'2048###Dez.');
$anbieter->add_row(TABLE_TEXTAREA,							'settings',			'Einstellungen');
$anbieter->add_row(TABLE_BITFIELD|TABLE_NEWSECTION,			'pflege_pweinst',	$use_neweditor?'Onlinepflegeopt.':'Onlinepflegeoptionen', 0,
																				 '1###Zugang zur Onlinepflege erlauben###'
																				.'2###der Anbieter kann seine '.($use_neweditor?'Angebote':'Kurse').' bewerben###'
																				.'4###nur Bagatelländerungen zulassen',
																				 'Onlinepflege', array('layout.section'=>'Onlinepflege', 'ctrl.checkboxes'=>1));
$anbieter->add_row(TABLE_PASSWORD,							'pflege_passwort',	'Passwort', 0, '');
$anbieter->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION,			'notizen',			'Journal', '', '', '',  array('layout.section'=>1));
$anbieter->add_row(TABLE_TEXTAREA|TABLE_WIKI,				'pflege_msg',		'Nachricht an den Anbieter', 0, 0, '', array('ctrl.rows'=>3, 'help.tooltip'=>'die Nachricht wird dem Anbieter immer angezeigt, wenn er sich in der Onlinepflege einloggt'));
$anbieter->add_row(TABLE_DATE|TABLE_DAYMONTHOPT,			'in_wisy_seit',		'in WISY seit ');
$anbieter->add_row(TABLE_ENUM,								'aufnahme_durch',	'Aufnahme durch', 0,
																				 '0######'
																				.'1###Datenpflegewunsch###'
																				.'2###ohne Anbieterwunsch###'
																				.'4###Anbieterwunsch');
$anbieter->add_row(TABLE_TEXT,								'herkunftsID',		'Herkunfts ID', '', '', '', array('layout.join'=>1, 'layout.defhide'=>2, 'layout.defhide.title'=>'weitere Angaben'));
$anbieter->add_row(TABLE_INT|TABLE_EMPTYONNULL,				'herkunft',			'Herkunft', 0, '', '', array('layout.join'=>1, 'layout.defhide'=>2));
if( $use_neweditor ) {
	$anbieter->add_row(TABLE_SATTR|TABLE_TRACKDEFAULTS,			'thema',			'Thema', 0, $themen, '', array('layout.join'=>1, 'layout.defhide'=>2)); // 1/2014: Verwendung der Anbieter-Themen unklar, wird nur in 88 Kursen verwendet. 
}

$anbieter->rows[$use_neweditor? 10 : 2]->addparam = $anbieter;



/*** DURCHFUEHRUNGEN ***/
$timecheck_reg = 'ss:mm###/^[012]\d:[0123456]\d{1,1}$/######/\./###:###/\s/######/^(\d):/###0\1:###/(\d\d)(\d\d)/###\1:\2###/(\d)(\d\d)/###0\1:\2';
$durchfuehrung = new Table_Def_Class(TABLE_SYNCABLE,		'durchfuehrung',	'Durchführungen');
$durchfuehrung->add_row(TABLE_TEXT|TABLE_LIST|TABLE_SUMMARY,'nr',				'Durchführungs-Nr.', '', '', '', array('ctrl.size'=>'10-40'));
if( $use_neweditor )
{
	$durchfuehrung->add_row(TABLE_TEXT|TABLE_NEWSECTION,		'bg_nummer',	'Maßnahmen-Nr.','','', 'Durchführungs-IDs',	array('layout.defhide'=>2, 'layout.join'=>1, 'ctrl.size'=>'10-40', 'layout.defhide.tooltip'=>'weitere Verwaltungsnummern'));
	$durchfuehrung->add_row(TABLE_TEXT, 						'bu_dnummer', 	'BU-Durchführungsnr.', '', '', '', 			array('layout.defhide'=>2, 'layout.join'=>1, 'ctrl.size'=>'10-40'));
	$durchfuehrung->add_row(TABLE_TEXT, 						'wisy_dnr', 	'Wisy-Durchführungsnr.', '', '', '',		array('layout.defhide'=>2, 'layout.join'=>1, 'ctrl.size'=>'10-40'));
	$durchfuehrung->add_row(TABLE_TEXT, 						'fu_dnr', 		'FU-Durchführungsnr.', '', '', '',			array('layout.defhide'=>2, 'layout.join'=>1, 'ctrl.size'=>'10-40'));
	$durchfuehrung->add_row(TABLE_TEXT, 						'foerder_dnr', 	'Förder-Durchführungsnr.', '', '', '',		array('layout.defhide'=>2, 'layout.join'=>1, 'ctrl.size'=>'10-40'));
	$durchfuehrung->add_row(TABLE_TEXT, 						'azwv_dnr', 	'AZAV-Durchführungsnr.', '', '', '',		array('layout.defhide'=>2, 'layout.join'=>1, 'ctrl.size'=>'10-40'));
}
$durchfuehrung->add_row(TABLE_INT|TABLE_EMPTYONNULL,		'stunden',			'Stunden ', 0, '0###9999', '', array('layout.join'=>1, 'layout.descr.class'=>'e_bold'));
$durchfuehrung->add_row(TABLE_INT|TABLE_EMPTYONNULL,		'teilnehmer',		'max. Teilnehmende', 0, '0###999', '', array('layout.join'=>1));
$durchfuehrung->add_row(TABLE_INT|TABLE_EMPTYONMINUSONE,	'preis',			'Gesamtpreis/EUR ', -1, '-1###99999', '', array('layout.join'=>1, 'layout.descr.class'=>'e_bold', 'help.tooltip'=>'Gesamtpreis inkl. MwSt in ganzen Euro; 0=kostenlos'));
$durchfuehrung->add_row(TABLE_TEXT|TABLE_ACNORMAL,			'preishinweise',	'Preishinweise', '', '', '',					array('layout.defhide'=>1, 'layout.defhide.tooltip'=>'weitere Preisangaben', 'layout.join'=>1, 'help.tooltip'=>'Preishinweise, aber keine Angaben über die MwSt; Preise müssen immer inkl. MwSt. angegeben werden!', 'ctrl.size'=>'10-20-80'));
$durchfuehrung->add_row(TABLE_INT|TABLE_EMPTYONMINUSONE,	'sonderpreis',		'Sonderpreis/EUR ', -1, '-1###99999', '',		array('layout.defhide'=>2, 'layout.join'=>1, 'help.tooltip'=>'Sonderpreis, der ein paar Tage vor Kursbeginn gelten soll, inkl. MwSt. in ganzen Euro; optional; 0=kostenlos'));
$durchfuehrung->add_row(TABLE_INT|TABLE_EMPTYONNULL,		'sonderpreistage',	'Sonderpreis/Tage vor Beginn', 0, '0###999','', array('layout.defhide'=>2, 'layout.join'=>1, 'help.tooltip'=>'Anzahl Tage vor Kursbeginn, aber der der Sonderpreis gelten soll; optional'));
$durchfuehrung->add_row(TABLE_DATE|TABLE_DAYMONTHOPT|TABLE_SUMMARY|TABLE_LIST|TABLE_NEWSECTION,
															'beginn',			'Beginn ', '', '', 'Termin', array('layout.after'=>'&ndash;', 'layout.descr'=>'Datum', 'layout.descr.class'=>'e_bold', 'layout.bg.class'=>'e_bgliter'));
$durchfuehrung->add_row(TABLE_DATE|TABLE_DAYMONTHOPT,		'ende',				'Ende ', '', '', '', array('layout.descr.hide'=>1));
if( !$use_neweditor )
{
	$durchfuehrung->add_row(TABLE_ENUM|TABLE_SUMMARY,		'beginnoptionen',	'Terminoption ', 0, $codes_beginnoptionen);
	$durchfuehrung->add_row(TABLE_ENUM,							'dauer',			'Dauer', 0, $codes_dauer, '', array('layout.descr.hide'=>1, 'layout.join'=>1, 'help.tooltip'=>'Die Dauer wird aus Beginn- und Endedatum automatisch berechnet; nur wenn dort keine Angaben gemacht werden, gilt der direkt eingestellte Wert'));
}
$durchfuehrung->add_row(TABLE_TEXT, 						'zeit_von',			'Uhrzeit von ', '', $timecheck_reg, '', array('layout.join'=>1, 'layout.descr.hide'=>1, 'layout.after'=>'&ndash;'));
$durchfuehrung->add_row(TABLE_TEXT, 						'zeit_bis',			($use_neweditor?'Uhrzeit bis':'bis '), '', $timecheck_reg, '', array('layout.join'=>1, 'layout.descr.hide'=>1, 'layout.after'=>' Uhr &nbsp; '));
$durchfuehrung->add_row(TABLE_BITFIELD,						'kurstage',			'Kurstage ', 0, $codes_kurstage, '', array('layout.join'=>1, 'layout.descr.hide'=>1));
$durchfuehrung->add_row(TABLE_ENUM,							'tagescode',		'Tagescode', 0, $codes_tagescode, '', array('layout.descr.hide'=>1, 'layout.defhide'=>1, 'layout.defhide.tooltip'=>'weitere Datumsangaben', 'help.tooltip'=>'Der Tagescode wird aus der Uhrzeit und den Kurstagen automatisch berechnet; nur wenn dort keine Angaben gemacht werden, gilt der direkt eingestellte Wert'));
if( $use_neweditor )
{
	$durchfuehrung->add_row(TABLE_ENUM,						'dauer',			'Dauer', 0, $codes_dauer, '', array('layout.defhide'=>1, 'layout.descr.hide'=>1, 'layout.join'=>1, 'help.tooltip'=>'Die Dauer wird aus Beginn- und Endedatum automatisch berechnet; nur wenn dort keine Angaben gemacht werden, gilt der direkt eingestellte Wert'));
	$durchfuehrung->add_row(TABLE_ENUM|TABLE_SUMMARY,		'beginnoptionen',	'Terminoption', 0, $codes_beginnoptionen, '', array('layout.join'=>1, 'layout.defhide'=>1, 'layout.descr.hide'=>1)); // nur ca. 10% der Kurse haben eine Terminoption, kann man also verstecken ...
}
$durchfuehrung->add_row(TABLE_TEXT|TABLE_NEWSECTION|TABLE_ACNESTSTART,
															'strasse',			'Straße ', '', '', 'Veranstaltungsort', array('layout.descr'=>'Veranstaltungsort', 'layout.descr.hide'=>1, 'ctrl.placeholder'=>1, 'ctrl.size'=>'8-16-48'));
$durchfuehrung->add_row(TABLE_TEXT|TABLE_ACNESTSTART|TABLE_ACNEST,				'plz',				'PLZ ', '', 5, '', array('layout.after'=>' ', 'layout.descr.hide'=>1, 'ctrl.placeholder'=>1));
$durchfuehrung->add_row(TABLE_TEXT|TABLE_ACNEST,			'ort',				'Ort ', '', '', '', array('ctrl.size'=>'8-16-48', 'layout.descr.hide'=>1, 'ctrl.placeholder'=>1));
$durchfuehrung->add_row(TABLE_TEXT|TABLE_ACNEST,			'stadtteil',		'Stadtteil ', '', '', '', array('ctrl.size'=>'8-16-48', 'layout.defhide'=>1, 'layout.defhide.tooltip'=>'weitere Ortsangaben'));
$durchfuehrung->add_row(TABLE_TEXT|TABLE_ACNEST,			'land',				'Land ', '', 3, '', array('layout.defhide'=>1));
$durchfuehrung->add_row(TABLE_FLAG,							'rollstuhlgerecht',	'Rollstuhlgerecht', '', '', '', array('layout.defhide'=>1));
$durchfuehrung->add_row(TABLE_TEXTAREA|TABLE_WIKI,			'bemerkungen',		'Bemerkungen', '', '', '', array('layout.defhide'=>1, 'ctrl.rows'=>2));
if( !$use_neweditor )
{
	$durchfuehrung->add_row(TABLE_TEXT|TABLE_NEWSECTION,		'bg_nummer',	'Maßnahmen-Nummer','','', 'Durchführungs-IDs');
	$durchfuehrung->add_row(TABLE_TEXT, 						'bu_dnummer', 	'BU-Durchführungsnummer');
	$durchfuehrung->add_row(TABLE_TEXT, 						'wisy_dnr', 	'Wisy-Durchführungsnummer');
	$durchfuehrung->add_row(TABLE_TEXT, 						'fu_dnr', 		'FU-Durchführungsnummer');
	$durchfuehrung->add_row(TABLE_TEXT, 						'foerder_dnr', 	'Förder-Durchführungsnummer');
	$durchfuehrung->add_row(TABLE_TEXT, 						'azwv_dnr', 	'AZAV-Durchführungsnummer');
}



/*** PORTALE ***/
/*** HINWEIS: Einstellungen gehören als INI-Wert in das Feld portale.einstellungen - sonst haben wir hier ganz schnell Chaos! (bp) ***/
$portale = new Table_Def_Class(TABLE_PRIMARY,						'portale',			'Portale');
$portale->add_row(TABLE_TEXT|TABLE_SUMMARY|TABLE_LIST|TABLE_MUST,	'name',				'Name', 0, 0, '', array('ctrl.size'=>'10-20-80', 'layout.bg.class'=>'e_bglite', 'layout.descr.class'=>'e_bolder', 'ctrl.class'=>'e_bolder'));
$portale->add_row(TABLE_TEXT|TABLE_MUST,							'kurzname',			'Kurzname', 0, 0, '', array('layout.join'=>1, 'ctrl.size'=>'7-15-40'));
$portale->add_row(TABLE_TEXT|TABLE_LIST|TABLE_MUST,					'domains',			'Domains', 0, 0, '', array('ctrl.size'=>'10-20-100'));
$portale->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION, 					'einstellungen',	'Einstellungen', 0, 0, 'Einstellungen', array('help.url'=>'https://b2b.kursportal.info/index.php?title=Portaleinstellungen', 'ctrl.rows'=>10)); 
$portale->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION, 					'bodystart',		'HTML', 0, 0, 'Layout', array('help.url'=>'https://b2b.kursportal.info/index.php?title=Portallayout', 'ctrl.rows'=>3, 'layout.section'=>'Layout'));
$portale->add_row(TABLE_TEXTAREA, 									'css',				'CSS', 0, 0, 'Layout', array('help.url'=>'https://b2b.kursportal.info/index.php?title=Portallayout', 'ctrl.rows'=>3));
$portale->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION, 					'filter',			'Filter', 0, 0, 'Filter', array('help.url'=>'https://b2b.kursportal.info/index.php?title=Portaleinstellungen#Filtereinstellungen', 'ctrl.rows'=>3, 'layout.section'=>'Filter'));
$portale->add_row(TABLE_TEXTAREA|TABLE_READONLY,					'einstcache',		'Cache', 0, 0, '', array('layout.defhide'=>2, 'layout.descr.hide'=>1, 'layout.join'=>1));
$portale->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION,					'notizen',			'Journal', '', '', '', array('layout.section'=>1));
/*** /HINWEIS: Einstellungen gehören als INI-Wert in das Feld portale.einstellungen - sonst haben wir hier ganz schnell Chaos! (bp) ***/
/*** Verworfene Werte: skindir, iwwb, iwwb_filter, iwwb_style, logo_1, logo_1_href, logo_2, logo_2_href, menuswitch, print_img, spalten, themen_erlauben, themen_verbieten, qual_logo, qual_logo_gloss, qual_logo_stich, horizont, horizontende, betreiberID (bp) ***/


/*** KURSE ***/
$kurse = new Table_Def_Class(TABLE_PRIMARY|TABLE_SYNCABLE,		'kurse',			$use_neweditor? 'Angebote' : 'Kurse');
if($use_neweditor) {
	$kurse->add_row(TABLE_TEXT, 									'azwv_knr', 		'AZAV-Kursnr.', '','', '',		array('ctrl.size'=>'3-10-40', 'layout.defhide'=>1, 'layout.join'=>1, 'layout.defhide.tooltip'=>'weitere Verwaltungsnummern'));
	$kurse->add_row(TABLE_TEXT|TABLE_NEWSECTION,					'bu_nummer',		'BU-Kursnr.', '','', 'Kurs-IDs',	array('ctrl.size'=>'10-40', 'layout.defhide'=>2, 'layout.join'=>1));
	//$kurse->add_row(TABLE_TEXT, 									'res_nummer', 		'BU-Kursnr.', '','', '',		array('ctrl.size'=>'10-40', 'layout.defhide'=>2, 'layout.join'=>1));
	$kurse->add_row(TABLE_TEXT, 									'fu_knr', 		    'FU-Kursnr.', '','', '',		array('ctrl.size'=>'10-40', 'layout.defhide'=>2, 'layout.join'=>1));
	$kurse->add_row(TABLE_TEXT, 									'foerder_knr', 		'Förder-Kursnr.', '','', '',	array('ctrl.size'=>'10-40', 'layout.defhide'=>2, 'layout.join'=>1));
	$kurse->add_row(TABLE_ENUM,										'freigeschaltet',	'Status ', 1, '0###In Vorbereitung###1###Freigegeben###3###Abgelaufen###4###Dauerhaft###2###Gesperrt', '', array('layout.join'=>1, 'layout.descr.hide'=>1));
	$kurse->add_row(TABLE_INT|TABLE_EMPTYONNULL|TABLE_READONLY|TABLE_PERCENT,
																	'vollstaendigkeit',	'Vollständigkeit', 0, '0###100', 0, array('layout.defhide'=>1, 'layout.after'=>'%'));
}
$kurse->add_row(TABLE_TEXT|TABLE_SUMMARY|TABLE_LIST|TABLE_MUST,	'titel',			'Titel ', '', '', 0, array('ctrl.size'=>'10-30-100', 'ctrl.class'=>'e_bolder', 'layout.descr.class'=>'e_bolder', 'layout.bg.class'=>'e_bglite'));
if($use_neweditor) {
	$kurse->add_row(TABLE_TEXT, 									'org_titel', 		'Originaltitel', 0, 0, '', array('layout.join'=>1, 'ctrl.size'=>'10-80', 'layout.defhide'=>2));
}
if(!$use_neweditor) {
	$kurse->add_row(TABLE_ENUM,										'freigeschaltet',	'Status ', 1, '0###In Vorbereitung###1###Freigegeben###3###Abgelaufen###4###Dauerhaft###2###Gesperrt', '', array('layout.join'=>1));
	$kurse->add_row(TABLE_INT|TABLE_EMPTYONNULL|TABLE_READONLY|TABLE_PERCENT,
																	'vollstaendigkeit',	'Vollständigkeit', 0, '0###100', 0, array('layout.defhide'=>1, 'layout.after'=>'%'));
}
if(!$use_neweditor) {
	$kurse->add_row(TABLE_TEXT, 									'org_titel', 		'Originaltitel', 0, 0, '');
}
$kurse->add_row(TABLE_SATTR|TABLE_LIST|TABLE_TRACKDEFAULTS|TABLE_MUST,
																'anbieter',			'Anbieter', 0, $anbieter);
$kurse->add_row(TABLE_TEXTAREA|TABLE_WIKI|TABLE_NEWSECTION,		'beschreibung',		'Beschreibung', 0, 0, 'Kursporträt');
$kurse->add_row(TABLE_SATTR|TABLE_TRACKDEFAULTS,				'thema',			'Thema', 0, $themen, '', array());
$kurse->add_row(TABLE_MATTR|TABLE_TRACKDEFAULTS,				'stichwort',		'Stichwörter', 0, $stichwoerter, '', array('layout.join'=>0));
$kurse->add_row(TABLE_TEXT,										'msgtooperator',	'Stichwortvorschläge', 0, 0, '', array('layout.join'=>1, 'layout.defhide'=>1, 'help.tooltip'=>'Stichwortvorschläge vom Anbieter', 'ctrl.size'=>'10-20-60'));
$kurse->add_row(TABLE_SECONDARY|TABLE_TRACKDEFAULTS,			'durchfuehrung',	'Durchführung', 1, $durchfuehrung);
if(!$use_neweditor) {
	$kurse->add_row(TABLE_TEXT|TABLE_NEWSECTION,					'bu_nummer',		'BU-Kursnummer', '','', 'Kurs-IDs', array('layout.join'=>1));
	//$kurse->add_row(TABLE_TEXT, 									'res_nummer', 		'BU-Kursnummer', '','', '', array('layout.join'=>1));
	$kurse->add_row(TABLE_TEXT, 									'fu_knr', 		    'FU-Kursnummer', '','', '', array('layout.join'=>1));
	$kurse->add_row(TABLE_TEXT, 									'foerder_knr', 		'Förder-Kursnummer', '','', '', array('layout.join'=>1));
	$kurse->add_row(TABLE_TEXT, 									'azwv_knr', 		'AZAV-Kursnummer', '','', '', array('layout.join'=>1));
}
$kurse->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION,				'notizen',			'Journal', '', '', '', array('layout.section'=>1));
$kurse->set_trigger('config/trigger_kurse.inc.php');


/*** FEEDBACK ***/
$feedback = new Table_Def_Class(0,											'feedback',			'Feedback');
$feedback->add_row(TABLE_TEXT|TABLE_LIST|TABLE_MUST|TABLE_READONLY,			'ip',				'Feedback von');
$feedback->add_row(TABLE_TEXT|TABLE_SUMMARY|TABLE_LIST|TABLE_MUST|TABLE_READONLY|TABLE_URL,
																			'url',				'Bewertete URL', '', '', '', array('ctrl.size'=>'10-80'));
$feedback->add_row(TABLE_ENUM|TABLE_LIST|TABLE_READONLY,					'rating',			'Wertung', 0, '0###nicht hilfreich###1###hilfreich');
$feedback->add_row(TABLE_TEXTAREA|TABLE_LIST|TABLE_READONLY,				'descr',			'Kommentar');
$feedback->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION, 						'notizen', 			'Journal', '', '', '', array('layout.section'=>1));


/*** anbieter_promote etc. (fuer die Werbung) ***/
$anbieter_promote = new Table_Def_Class(0,			'anbieter_promote',	'Anbieterwerbung');
$anbieter_promote->add_row(TABLE_ENUM |TABLE_LIST,	'promote_active',		'Status', 0, '0###Inaktiv###1###Aktiv');
$anbieter_promote->add_row(TABLE_TEXT |TABLE_LIST,	'promote_mode',			'Modus');
$anbieter_promote->add_row(TABLE_TEXT |TABLE_LIST,	'promote_param',		'Wert');
$anbieter_promote->add_row(TABLE_SATTR|TABLE_LIST,	'anbieter_id',			'Anbieter', 0, $anbieter);
$anbieter_promote->add_row(TABLE_SATTR|TABLE_LIST,	'portal_id',			'Portal', 0, $portale);
$anbieter_promote->add_row(TABLE_SATTR|TABLE_LIST,	'kurs_id',				'Kurs', 0, $kurse);

$anbieter_promote_log = new Table_Def_Class(0,			'anbieter_promote_log',	'Anbieterlog');
$anbieter_promote_log->add_row(TABLE_ENUM |TABLE_LIST,	'event_type',			'Aktion', 0, '1001###Einblendungen###1002###Klicks###2001###aktueller Kredit');
$anbieter_promote_log->add_row(TABLE_INT  |TABLE_LIST,	'lparam',				'Wert');
$anbieter_promote_log->add_row(TABLE_SATTR|TABLE_LIST,	'anbieter_id',			'Anbieter', 0, $anbieter);
$anbieter_promote_log->add_row(TABLE_SATTR|TABLE_LIST,	'portal_id',			'Portal', 0, $portale);
$anbieter_promote_log->add_row(TABLE_SATTR|TABLE_LIST,	'kurs_id',				'Kurs', 0, $kurse);

$anbieter_billing = new Table_Def_Class(0,			'anbieter_billing',	'Anbieterrechnungen');
$anbieter_billing->add_row(TABLE_ENUM |TABLE_LIST|TABLE_READONLY,	'bill_type',			'Rechnungstyp', 0, '9001###automatisch erstellte Paypal-Rechnung###9002###manuell erstellte Rechnung###9099###fehlerhafte Rechnung');
$anbieter_billing->add_row(TABLE_TEXT |TABLE_LIST|TABLE_READONLY|TABLE_NEWSECTION,	'eur',	'Euro', '', 0, 'Rechnung');
$anbieter_billing->add_row(TABLE_INT  |TABLE_LIST|TABLE_READONLY,	'credits',				'Anzahl Kredite');
$anbieter_billing->add_row(TABLE_SATTR|TABLE_LIST|TABLE_READONLY,	'anbieter_id',			'Anbieter', 0, $anbieter);
$anbieter_billing->add_row(TABLE_SATTR|TABLE_LIST|TABLE_READONLY,	'portal_id',			'Portal', 0, $portale);
$anbieter_billing->add_row(TABLE_TEXTAREA |TABLE_READONLY,			'raw_data',				'Details');
$anbieter_billing->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION,			'notizen',				'Journal', '', '', '', array('layout.section'=>1));



/*** APIKEYS ***/
$apikeys = new Table_Def_Class(0,								'apikeys',			'API-Keys');
$apikeys->add_row(TABLE_TEXT|TABLE_LIST|TABLE_MUST,				'name',				'Name', '', '', '', array('ctrl.size'=>'10-80', 'layout.bg.class'=>'e_bglite', 'layout.descr.class'=>'e_bolder', 'ctrl.class'=>'e_bolder'));
$apikeys->add_row(TABLE_TEXT|TABLE_LIST|TABLE_UNIQUE,			'apikey',			'persönlicher API-Key', 'wird automatisch erzeugt', '', '', array('ctrl.size'=>'25-80'));
$apikeys->add_row(TABLE_BITFIELD|TABLE_LIST,					'flags',			'Optionen', 1+2, '1###Freigeschaltet###2###Verschlüsselte Verbindung###4###Schreibzugriff erlauben', '', array('ctrl.checkboxes'=>1));
$apikeys->add_row(TABLE_MATTR,			                        'usergrp',  		'Zugriffsbeschränkung', 0, 'user_grp', '', array('layout.after'=>'<br>Wenn hier Gruppen eingetragen werden, können neue Datensätze nur mit diesen Gruppen erstellt werden und bestehende können nur bearbeitet/gelöscht werden, wenn sie einer der Gruppen angehören.'));
$apikeys->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION, 				'notizen', 			'Journal', '', '', '', array('layout.section'=>1));
$apikeys->set_trigger('config/trigger_apikeys.inc.php'); 




$Table_Def[] = $kurse; // the order may be changed and is only important for layout reasons
$Table_Def[] = $durchfuehrung;
$Table_Def[] = $anbieter;
$Table_Def[] = $anbieter_billing;
$Table_Def[] = $anbieter_promote;
$Table_Def[] = $anbieter_promote_log;
$Table_Def[] = $ratgeber;
$Table_Def[] = $stichwoerter;
$Table_Def[] = $themen;
$Table_Def[] = $portale;
$Table_Def[] = $feedback;
$Table_Def[] = $apikeys;



$Table_Shortnames = array(
	'Stunden'						=>	'Std.',
	'Gesamtpreis/EUR'				=>	'Preis',
	'Sonderpreis/EUR'				=>	'Sonderpr.',
	'Sonderpreis/Tage vor Beginn'	=>	'Tage',
	'Durchführungs-Nr.'				=>	'Nr.',
	'Stunden'						=>	'Std.',
	'Uhrzeit von'					=>	'Zeit',
	'max. Teilnehmende'				=>	'Teiln.',
	'Rollstuhlgerecht'				=>	'Rollst.'
);



// do not remove the following line, it creates the user table with the access
// rights depending on $Table_Def above. this line must be last after your
// table definitions
Table_Def_Finish(array(
					'user.settings.help.url'=>'https://b2b.kursportal.info/index.php?title=Benutzereinstellungen',
					'user_grp.settings.help.url'=>'https://b2b.kursportal.info/index.php?title=Gruppeneinstellungen',
				));



// zusätzliche Einstellungen
$g_addsettings_names = array("Domain für 'Ansicht'", 'view.domain');

// in dieses Verzeichnis werden zu exportierende Dateien temporär gelagert; andere Dateien in diesem Verzeichnis werden gelöscht!
$g_temp_dir = '../temp';

