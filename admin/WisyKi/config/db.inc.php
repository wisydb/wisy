<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Encoding: ISO8859-15, Test:: Ä&ouml&uuml

// deprecated
$use_neweditor = true;
if (isset($_COOKIE['oldeditor']) && $_COOKIE['oldeditor']) {
	$use_neweditor = false;
}
// /deprecated


if (@file_exists(dirname(__FILE__) . '/codes.inc.php')) {
	require_once(dirname(__FILE__) . '/codes.inc.php');
} else
	die('Verzeichnis unerwartet.');

define('USE_ROLES', 1);

global $codes_beginnoptionen, $codes_dauer, $codes_stichwort_eigenschaften, $codes_rechtsform, $codes_kurstage, $codes_tagescode;
global $esco_competence, $esco_profession;
//
// Table Definitions - This Area must be modified for Customisation
//
//////////////////////////////////////////////////////////////////////////////




/*** RATGEBER ***/
$ratgeber = new Table_Def_Class(TABLE_PRIMARY,				'glossar',			'Ratgeber');
$ratgeber->add_row(TABLE_ENUM,								'status',	        'Status ', 1, '0###In Vorbereitung###1###Freigegeben###3###Archiv', '', array('layout.join' => 1, 'layout.descr.hide' => 1));
if ($use_neweditor) {
	$ratgeber->add_row(TABLE_ENUM,								'freigeschaltet',	'ABC-Index', 2, '2###Nein###1###Ja', '', array('layout.join' => 1));
}
$ratgeber->add_row(
	TABLE_TEXT | TABLE_SUMMARY | TABLE_LIST | TABLE_MUST | TABLE_UNIQUE,
	'begriff',
	'Begriff',
	'',
	'',
	'',
	array('ctrl.size' => '10-20-60', 'layout.bg.class' => 'e_bglite', 'layout.descr.class' => 'e_bolder', 'ctrl.class' => 'e_bolder')
);
if (!$use_neweditor) {
	$ratgeber->add_row(TABLE_ENUM,								'freigeschaltet',	'ABC-Index', 2, '2###Nein###1###Ja');
}
$ratgeber->add_row(TABLE_TEXTAREA | TABLE_WIKI | TABLE_NEWSECTION, 'erklaerung',		'Erkl&aumlrung', '', '', '', array('ctrl.rows' => 20));
$ratgeber->add_row(TABLE_TEXT,								'wikipedia',		'Stichw. Wikipedia', '', '', '', array('ctrl.size' => '10-20-60'));
$ratgeber->add_row(TABLE_TEXTAREA | TABLE_NEWSECTION,			'notizen_fix',			'Anmerkungen', '', '', '',  array('layout.section' => 1));
$ratgeber->add_row(TABLE_TEXTAREA,			                'notizen',			'Journal', '', '', '');

/*** ABSCHLUSS ***/
$abschluss = new Table_Def_Class(TABLE_SYNCABLE,				'stichwoerter',			'abschluss');
$abschluss->add_row(
	TABLE_TEXT | TABLE_SUMMARY | TABLE_LIST | TABLE_MUST,
	'stichwort',
	'Stichwort',
	'',
	array('16', '1'),  //keywort-Types to be found
	'',
	array('ctrl.size' => '10-20-60', 'layout.bg.class' => 'e_bglite', 'layout.descr.class' => 'e_bolder', 'ctrl.class' => 'e_bolder')
);


/***Kompetenz ***/
$kompetenz = new Table_Def_Class(TABLE_SYNCABLE,				'stichwoerter',			'kompetenz');
$kompetenz->add_row(
	TABLE_TEXT | TABLE_SUMMARY | TABLE_LIST | TABLE_MUST,
	'stichwort',
	'Stichwort',
	'',
	array($esco_competence, $esco_profession),  //keywort-Types, and Ids to be found
	'',
	array('ctrl.size' => '10-20-60', 'layout.bg.class' => 'e_bglite', 'layout.descr.class' => 'e_bolder', 'ctrl.class' => 'e_bolder')
);

/***Einstieg ***/
$permentry = new Table_Def_Class(TABLE_SYNCABLE,				'stichwoerter',			'permentry');
$permentry->add_row(
	TABLE_TEXT | TABLE_SUMMARY | TABLE_LIST | TABLE_MUST,
	'stichwort',
	'Stichwort',
	'',
	array('Id315'),  //keywort-Types to be found	
	'',
	array('ctrl.size' => '10-20-60', 'layout.bg.class' => 'e_bglite', 'layout.descr.class' => 'e_bolder', 'ctrl.class' => 'e_bolder')
);

/***Foerderungsart ***/
$foerderungsart = new Table_Def_Class(TABLE_SYNCABLE,				'stichwoerter',			'foerderungsart');
$foerderungsart->add_row(
	TABLE_TEXT | TABLE_SUMMARY | TABLE_LIST | TABLE_MUST,
	'stichwort',
	'Stichwort',
	'',
	array('2'),  //keywort-Types to be found	
	'',
	array('ctrl.size' => '10-20-60', 'layout.bg.class' => 'e_bglite', 'layout.descr.class' => 'e_bolder', 'ctrl.class' => 'e_bolder')
);

/***Lernform ***/
$lernform = new Table_Def_Class(TABLE_SYNCABLE,				'stichwoerter',			'lernform');
$lernform->add_row(
	TABLE_TEXT | TABLE_SUMMARY | TABLE_LIST | TABLE_MUST,
	'stichwort',
	'Stichwort',
	'',
	array('32768'),  //keywort-Types to be found	
	'',
	array('ctrl.size' => '10-20-60', 'layout.bg.class' => 'e_bglite', 'layout.descr.class' => 'e_bolder', 'ctrl.class' => 'e_bolder')
);

/*** THEMEN ***/
$themen = new Table_Def_Class(TABLE_SYNCABLE,				'themen',			'Themen');
$themen->add_row(
	TABLE_TEXT | TABLE_SUMMARY | TABLE_LIST | TABLE_MUST,
	'thema',
	'Thema',
	'',
	'',
	'',
	array('ctrl.size' => '10-20-60', 'layout.bg.class' => 'e_bglite', 'layout.descr.class' => 'e_bolder', 'ctrl.class' => 'e_bolder')
);

/**Suche nach ESCO Kompetenzen */
$escocategories = new Table_Def_Class(TABLE_SYNCABLE,				'escocategories',			'ESCO');
$escocategories->add_row(
	TABLE_TEXT | TABLE_SUMMARY | TABLE_LIST | TABLE_MUST,
	'kategorie',
	'ESCO',
	'',
	array('ESCO'),
	'',
	array('ctrl.size' => '10-20-60', 'layout.bg.class' => 'e_bglite', 'layout.descr.class' => 'e_bolder', 'ctrl.class' => 'e_bolder')
);
$escoskills = new Table_Def_Class(TABLE_SYNCABLE,				'escoskills',			'ESCO');
$escoskills->add_row(
	TABLE_TEXT | TABLE_SUMMARY | TABLE_LIST | TABLE_MUST,
	'kategorie',
	'ESCO',
	'',
	array('ESCO'),
	'',
	array('ctrl.size' => '10-20-60', 'layout.bg.class' => 'e_bglite', 'layout.descr.class' => 'e_bolder', 'ctrl.class' => 'e_bolder')
);

$themen->add_row(
	TABLE_TEXT | TABLE_LIST | TABLE_MUST | TABLE_UNIQUE | TABLE_NEWSECTION,
	'kuerzel',
	'K&aumlrzel',
	0,
	0,
	'Klassifizierung',
	array('ctrl.size' => '4-10-40', 'layout.section' => 'Klassifizierung')
);
$themen->add_row(TABLE_SATTR,								'glossar',			'Ratgeberseite', 0, $ratgeber);
$themen->add_row(TABLE_TEXTAREA | TABLE_NEWSECTION,			'scope_note',		'Scope note');
$themen->add_row(TABLE_TEXTAREA | TABLE_NEWSECTION,			'algorithmus',		'Algorithmus');
$themen->add_row(TABLE_TEXTAREA | TABLE_NEWSECTION,			'notizen',			'Journal', '', '', '', array('layout.section' => 1));


/*** STICHWOERTER ***/
$stichwoerter = new Table_Def_Class(TABLE_PRIMARY | TABLE_SYNCABLE,			'stichwoerter',		'Stichw&oumlrter');
$stichwoerter->add_row(
	TABLE_TEXT | TABLE_SUMMARY | TABLE_LIST | TABLE_MUST | TABLE_UNIQUE,
	'stichwort',
	'Deskriptor',
	0,
	0,
	'',
	array('ctrl.size' => '20-70', 'help.tooltip' => 'Deskriptor, ohne Erkl&aumlrungen und so kurz und so einfach wie m&aumlglich', 'layout.bg.class' => 'e_bglite', 'layout.descr.class' => 'e_bolder', 'ctrl.class' => 'e_bolder')
);
$stichwoerter->add_row(TABLE_TEXT,											'zusatzinfo',		'Zusatzinfo', 0, 0, '', array('ctrl.size' => '20-70', 'help.tooltip' => 'Kurze Informationen zur evtl. notwendigen Erkl&aumlrung'));
$stichwoerter->add_row(TABLE_MATTR | TABLE_SHOWREF,							'verweis',			'Synonym f&aumlr', 0, 0 /*set below*/, '', array('ref.name' => 'Synonym von'));
$stichwoerter->add_row(TABLE_MATTR | TABLE_SHOWREF,							'verweis2',			'Oberbegriff f&aumlr', 0, 0 /*set below*/, '', array('help.tooltip' => 'Das aktuelle Stichwort wird automatisch vergeben, wenn eines der hier angegebenen Stichw&oumlrter vergeben wird', 'ref.name' => 'Unterbegriff von'));
$stichwoerter->add_row(TABLE_ENUM | TABLE_SUMMARY | TABLE_LIST | TABLE_NEWSECTION, 'eigenschaften',	'Typ', 0, $codes_stichwort_eigenschaften, 'Klassifizierung', array('layout.section' => 'Klassifizierung'));
$stichwoerter->add_row(TABLE_SATTR,											'thema',			'Thema', 0, $themen);
$stichwoerter->add_row(TABLE_SATTR,											'glossar',			'Ratgeberseite', 0, $ratgeber);
$stichwoerter->add_row(TABLE_TEXTAREA | TABLE_NEWSECTION,						'scope_note',		'Scope note');
$stichwoerter->add_row(TABLE_TEXTAREA | TABLE_NEWSECTION,						'algorithmus',		'Algorithmus');
$stichwoerter->add_row(TABLE_TEXTAREA | TABLE_NEWSECTION,			            'notizen_fix',			'Anmerkungen', '', '', '',  array('layout.section' => 1));
$stichwoerter->add_row(TABLE_TEXTAREA,						                'notizen',			'Journal', '', '', '');
$stichwoerter->add_row(TABLE_TEXT | TABLE_READONLY,							'esco_url',		        'Link zur ESCO-Kompetenz', 0, 0, '', array('ctrl.size' => '20-70', 'help.tooltip' => 'Bitte in die Zwischenablage kopieren und neuem Browserfenster oeffnen.'));
$stichwoerter->rows[2]->addparam = $stichwoerter;
$stichwoerter->rows[3]->addparam = $stichwoerter;





/*** ANBIETER ***/
$anbieter = new Table_Def_Class(TABLE_PRIMARY | TABLE_SYNCABLE, 'anbieter',		'Anbieter');
if ($use_neweditor) {
	$anbieter->add_row(TABLE_TEXT,								'din_nr',			'Wisy-Kundennr.', 0, 0, '',		array('ctrl.size' => '4-40', 'layout.defhide' => 2, 'layout.join' => 1, 'layout.defhide.tooltip' => 'weitere Verwaltungsnummern'));
	$anbieter->add_row(TABLE_TEXT, 								'wisy_annr', 		'Kursnet-Nr.', 0, 0, '',		array('ctrl.size' => '10-40', 'layout.defhide' => 2, 'layout.join' => 1));
	$anbieter->add_row(TABLE_TEXT, 								'bu_annr', 			'BU-Anbieternr.', 0, 0, '',		array('ctrl.size' => '10-40', 'layout.defhide' => 2, 'layout.join' => 1));
	$anbieter->add_row(TABLE_TEXT, 								'foerder_annr', 	'Amtl. Gemeindeschl.', 0, 0, '',	array('ctrl.size' => '10-40', 'layout.defhide' => 2, 'layout.join' => 1));
	$anbieter->add_row(TABLE_TEXT, 								'fu_annr', 			'FU-Anbieternr.', 0, 0, '',		array('ctrl.size' => '10-40', 'layout.defhide' => 2, 'layout.join' => 1));
	$anbieter->add_row(TABLE_TEXT, 								'azwv_annr', 		'AZAV-Anbieternr.', 0, 0, '', 	array('ctrl.size' => '10-40', 'layout.defhide' => 2, 'layout.join' => 1));
	$anbieter->add_row(TABLE_ENUM,								'freigeschaltet',	'Status', 1, '1###Freigegeben###2###Gesperrt', '', array('layout.join' => 1, 'layout.descr.hide' => 1));
	$anbieter->add_row(
		TABLE_INT | TABLE_EMPTYONNULL | TABLE_READONLY | TABLE_PERCENT,
		'vollstaendigkeit',
		'Vollst&aumlndigkeit',
		0,
		'0###100',
		0,
		array('layout.defhide' => 1, 'layout.after' => '%', 'layout.join' => 1)
	);
	// new field, add with: ALTER TABLE `anbieter` ADD `vollstaendigkeit` int(11) NOT NULL DEFAULT '0' AFTER `date_modified` ;
}
$anbieter->add_row(
	TABLE_TEXT | TABLE_SUMMARY | TABLE_LIST | TABLE_MUST | TABLE_UNIQUE,
	'suchname',
	($use_neweditor ? 'Suchname' : 'Suchname '),
	'',
	'',
	'',
	array('ctrl.size' => '20-80', 'layout.descr.class' => 'e_bolder', 'layout.bg.class' => 'e_bglite', 'ctrl.class' => 'e_bolder')
);
if (!$use_neweditor) {
	$anbieter->add_row(TABLE_ENUM,							'freigeschaltet',	'Status', 1, '1###Freigegeben###2###Gesperrt');
	$anbieter->add_row(TABLE_MATTR | TABLE_SHOWREF,			'verweis',			'Namensverweisung', 0, 0 /*set below*/, '', array('layout.join' => 1, 'layout.defhide' => 1));
}

$anbieter->add_row(
	TABLE_ENUM | TABLE_SUMMARY | TABLE_LIST | TABLE_NEWSECTION,
	'typ',
	'Typ',
	0,
	'0###Anbieter###'
		/*.'1###Trainer###' - entfernt, s. WISY_2014_TODO, 4-14*/
		. '2###Beratungsstelle###'
		// .'64###Namensverweisung (ALT)###'
		. '65###Versteckte Namensverweisung (z.Z. nicht verwenden)###'
		. '262144###Namensverweisung###',
	'Allgemein'
);

if ($use_neweditor) {
	$anbieter->add_row(TABLE_MATTR | TABLE_SHOWREF,			'verweis',			'Namensverweisung', 0, 0 /*set below*/, '', array('layout.join' => 1, 'layout.defhide' => 1));
}
$anbieter->add_row(TABLE_TEXTAREA,								'postname',			'Originalname',  '', '', '', array('ctrl.rows' => '2'));
$anbieter->add_row(TABLE_TEXT | TABLE_ACNESTSTART,			'strasse',			'Stra&szlig;e ', '', '', '', array('layout.descr' => 'Ort', 'ctrl.size' => '8-16-48', 'ctrl.placeholder' => 1));
$anbieter->add_row(TABLE_TEXT | TABLE_ACNESTSTART | TABLE_ACNEST, 'plz',				'PLZ ', '', 5, '', array('layout.after' => ' ', 'layout.descr.hide' => 1, 'ctrl.placeholder' => 1));
$anbieter->add_row(TABLE_TEXT | TABLE_ACNEST,					'bezirk',			'Bezirk ', '', '', '', array('layout.descr.hide' => 1, 'ctrl.placeholder' => 1, 'ctrl.class' => 'anbieter_bezirk', 'ctrl.size' => '8-16-48', 'layout.defhide' => 1));
$anbieter->add_row(TABLE_TEXT | TABLE_ACNEST,					'ort',				'Ort ', '', '', '', array('layout.descr.hide' => 1, 'ctrl.placeholder' => 1, 'ctrl.size' => '8-16-48'));
$anbieter->add_row(TABLE_TEXT | TABLE_ACNEST,					'stadtteil',		'Stadtteil ', '', '', '', array('ctrl.size' => '8-16-48', 'layout.defhide' => 1, 'layout.defhide.tooltip' => 'weitere Ortsangaben'));
$anbieter->add_row(TABLE_TEXT | TABLE_ACNEST,					'land',				'Land ', '', 3, '', array('layout.defhide' => 1));
$anbieter->add_row(TABLE_TEXT | TABLE_ACNEST,					'adresszusatz',		'Adresszusatz ', '', 3, '', array('ctrl.size' => '16-32-62', 'layout.defhide' => 1));
$anbieter->add_row(TABLE_FLAG,								'rollstuhlgerecht',	'Barrierefreier Zugang', '', '', '', array('layout.defhide' => 1));
$anbieter->add_row(TABLE_TEXT,								'leitung_name',		'Leitung', '', '', '', array('ctrl.placeholder' => 'Name'));
$anbieter->add_row(TABLE_TEXT | TABLE_TEL,					'leitung_tel',		'Leitung Telefon', '', '', '', array('layout.join' => 1, 'layout.descr.hide' => 1, 'ctrl.placeholder' => 'Telefon'));
if (!$use_neweditor) {
	$anbieter->add_row(TABLE_SATTR | TABLE_TRACKDEFAULTS,			'thema',			'Thema', 0, $themen);
}
$anbieter->add_row(TABLE_MATTR | TABLE_TRACKDEFAULTS,			'stichwort',		'Stichw&oumlrter', 0, $stichwoerter, '', array());
if (!$use_neweditor) {
	$anbieter->add_row(TABLE_TEXT,								'din_nr',			'Wisy-Kundennummer');
	$anbieter->add_row(TABLE_TEXT, 								'wisy_annr', 		'Kursnet-Nummer');
	$anbieter->add_row(TABLE_TEXT, 								'bu_annr', 			'BU-Anbieternummer');
	$anbieter->add_row(TABLE_TEXT, 								'foerder_annr', 	'Amtl. Gemeindeschl.');
	$anbieter->add_row(TABLE_TEXT, 								'fu_annr', 			'FU-Anbieternummer');
	$anbieter->add_row(TABLE_TEXT, 								'azwv_annr', 		'AZAV-Anbieternummer');
}
$anbieter->add_row(
	TABLE_INT | TABLE_EMPTYONNULL | TABLE_NEWSECTION,
	'gruendungsjahr',
	'Gr&aumlndungsjahr',
	0,
	'0###2200' /*0=leer, daher muss der Bereich ab dort derzeit (11/2013) erlaubt sein*/,
	'Firmenportrï¿½t',
	array('layout.section' => 'Firmenportr&aumlt')
);
$anbieter->add_row(
	TABLE_ENUM,
	'rechtsform',
	'Rechtsform',
	0,
	$codes_rechtsform,
	'',
	array('layout.join' => 1)
);
$anbieter->add_row(TABLE_TEXTAREA | TABLE_WIKI,				'firmenportraet',	'Firmenportr&aumlt');
$anbieter->add_row(TABLE_BLOB,								'logo',				'Logo / Bild', '', '', '', array('layout.bg.class' => 'e_bgbottom'));
$anbieter->add_row(TABLE_TEXT | TABLE_URL,					'logo_rechte',			'Bildrechte', '', '', '', array('ctrl.size' => '10-20-50'));
$anbieter->add_row(TABLE_FLAG,					            'logo_position',			'&aumlber Inhalt positionieren', '', '', '', array('layout.join' => 1));
$anbieter->add_row(TABLE_TEXT | TABLE_URL,					'homepage',			'Homepage', '', '', '', array('ctrl.size' => '10-20-50'));
$anbieter->add_row(TABLE_DATE | TABLE_DAYMONTHOPT,			'pruefsiegel_seit',	'Pr&aumlfsiegel seit');
$anbieter->add_row(TABLE_TEXT | TABLE_NEWSECTION,				'anspr_name',		'Kundenberater', 0, 0, 'Kundenkontakt', array('ctrl.placeholder' => 'Name', 'layout.section' => 'Kundenkontakt'));
$anbieter->add_row(TABLE_TEXTAREA,							'anspr_zeit',		'Sprechzeiten', '', '', '', array('ctrl.rows' => 2));
$anbieter->add_row(TABLE_TEXT | TABLE_TEL,					'anspr_tel',		'Kunden Telefon');
$anbieter->add_row(TABLE_TEXT,								'anspr_fax',		'Kunden Fax', 0, 0, '', array('layout.join' => 1));
$anbieter->add_row(TABLE_TEXT | TABLE_URL,					'anspr_email',		'Kunden EMail', 0, 0, '', array('layout.join' => 1));
//$anbieter->add_row(TABLE_FLAG,								'kursplatzanfrage',	'Kursplatzanfrage'); // -- 11:53 04.02.2014 nicht mehr notwendig
//$anbieter->add_row(TABLE_FLAG,								'partnernetz',		'Partnernetz');  // -- 11:53 04.02.2014 nicht mehr notwendig
$anbieter->add_row(TABLE_TEXT | TABLE_NEWSECTION,				'pflege_name',		'Pflegepartner', 0, 0, 'Pflegekontakt', array('ctrl.placeholder' => 'Name', 'layout.section' => 'Pflegekontakt'));
$anbieter->add_row(TABLE_TEXT | TABLE_TEL,					'pflege_tel',		'Pflege Telefon');
$anbieter->add_row(TABLE_TEXT,								'pflege_fax',		'Pflege Fax', 0, 0, '', array('layout.join' => 1));
$anbieter->add_row(TABLE_TEXT | TABLE_URL,					'pflege_email',		'Pflege EMail', 0, 0, '', array('layout.join' => 1));
$anbieter->add_row(
	TABLE_ENUM,
	'pflege_weg',
	'Pflegeweg ',
	1,
	'0######'
		. '1###Redaktionelle Eingabe###'
		. '2###EMail###'
		. '4###Online###'
		. '8###Import'
);
$anbieter->add_row(
	TABLE_ENUM,
	'pflege_prot',
	'Korrekturausdrucke',
	1,
	'0######'
		. '1###Listenausdruck###'
		. '2###Seitenausdruck'
);
$anbieter->add_row(
	TABLE_BITFIELD,
	'pflege_akt',
	'Aktualisieren',
	0,
	'1###Jan. ###'
		. '2###Feb. ###'
		. '4###M&aumlrz ###'
		. '8###Apr. ###'
		. '16###Mai ###'
		. '32###Juni ###'
		. '64###Juli ###'
		. '128###Aug. ###'
		. '256###Sep. ###'
		. '512###Okt. ###'
		. '1024###Nov. ###'
		. '2048###Dez.'
);
$anbieter->add_row(TABLE_TEXTAREA,							'settings',			'Einstellungen');
$anbieter->add_row(
	TABLE_BITFIELD | TABLE_NEWSECTION,
	'pflege_pweinst',
	$use_neweditor ? 'Onlinepflegeopt.' : 'Onlinepflegeoptionen',
	0,
	'1###Zugang zur Onlinepflege erlauben###'
		. '2###der Anbieter kann seine ' . ($use_neweditor ? 'Angebote' : 'Kurse') . ' bewerben###'
		. '4###nur Bagatell&aumlnderungen zulassen',
	'Onlinepflege',
	array('layout.section' => 'Onlinepflege', 'ctrl.checkboxes' => 1)
);
$anbieter->add_row(TABLE_PASSWORD,							'pflege_passwort',	'Passwort', 0, '');
$anbieter->add_row(TABLE_TEXTAREA | TABLE_NEWSECTION,			'notizen_fix',			'Anmerkungen', '', '', '',  array('layout.section' => 1));
$anbieter->add_row(TABLE_TEXTAREA,			'notizen',			'Journal', '', '', '');
$anbieter->add_row(TABLE_TEXTAREA | TABLE_WIKI,				'pflege_msg',		'Nachricht an den Anbieter', 0, 0, '', array('ctrl.rows' => 3, 'help.tooltip' => 'die Nachricht wird dem Anbieter immer angezeigt, wenn er sich in der Onlinepflege einloggt'));
$anbieter->add_row(TABLE_DATE | TABLE_DAYMONTHOPT,			'in_wisy_seit',		'in WISY seit ');
$anbieter->add_row(
	TABLE_ENUM,
	'aufnahme_durch',
	'Aufnahme durch',
	0,
	'0######'
		. '1###Datenpflegewunsch###'
		. '2###ohne Anbieterwunsch###'
		. '4###Anbieterwunsch'
);
$anbieter->add_row(TABLE_TEXT,								'herkunftsID',		'Herkunfts ID', '', '', '', array('layout.join' => 1, 'layout.defhide' => 2, 'layout.defhide.title' => 'weitere Angaben'));
$anbieter->add_row(TABLE_INT | TABLE_EMPTYONNULL,				'herkunft',			'Herkunft', 0, '', '', array('layout.join' => 1, 'layout.defhide' => 2));

/* if( $use_neweditor ) {
	$anbieter->add_row(TABLE_SATTR|TABLE_TRACKDEFAULTS,			'thema',			'Thema', 0, $themen, '', array('layout.join'=>1, 'layout.defhide'=>2)); // 1/2014: Verwendung der Anbieter-Themen unklar, wird nur in 88 Kursen verwendet. 
} */

$anbieter->rows[$use_neweditor ? 10 : 2]->addparam = $anbieter;



/*** DURCHFUEHRUNGEN ***/
$timecheck_reg = 'ss:mm###/^[012]\d:[0123456]\d{1,1}$/######/\./###:###/\s/######/^(\d):/###0\1:###/(\d\d)(\d\d)/###\1:\2###/(\d)(\d\d)/###0\1:\2';
$durchfuehrung = new Table_Def_Class(TABLE_SYNCABLE,		'durchfuehrung',	'Durchf&uumlhrungen', 0, 0, true); // last parameter: delete DF as secondary entry in lookup table (adter deleting in course view)
$durchfuehrung->add_row(TABLE_TEXT | TABLE_LIST | TABLE_SUMMARY, 'nr',				'Durchf&uumlhrungs-Nr.', '', '', '', array('ctrl.size' => '7-10-40'));
if ($use_neweditor) {
	$durchfuehrung->add_row(TABLE_TEXT | TABLE_NEWSECTION,		'bg_nummer',	'Ma&szlig;nahmen-Nr.', '', '', 'Durchf&uumlhrungs-IDs',	array('layout.defhide' => 1, 'layout.join' => 1, 'ctrl.size' => '10-40', 'layout.defhide.tooltip' => 'weitere Verwaltungsnummern'));
	$durchfuehrung->add_row(TABLE_TEXT, 						'bu_dnummer', 	'BU-Durchf&uumlhrungsnr.', '', '', '', 			array('layout.defhide' => 2, 'layout.join' => 1, 'ctrl.size' => '10-40'));
	$durchfuehrung->add_row(TABLE_TEXT, 						'wisy_dnr', 	'Wisy-Durchf&uumlhrungsnr.', '', '', '',		array('layout.defhide' => 2, 'layout.join' => 1, 'ctrl.size' => '10-40'));
	$durchfuehrung->add_row(TABLE_TEXT, 						'fu_dnr', 		'FU-Durchf&uumlhrungsnr.', '', '', '',			array('layout.defhide' => 2, 'layout.join' => 1, 'ctrl.size' => '10-40'));
	$durchfuehrung->add_row(TABLE_TEXT, 						'foerder_dnr', 	'F&oumlrder-Durchf&uumlhrungsnr.', '', '', '',		array('layout.defhide' => 2, 'layout.join' => 1, 'ctrl.size' => '10-40'));
	$durchfuehrung->add_row(TABLE_TEXT, 						'azwv_dnr', 	'AZAV-Durchf&uumlhrungsnr.', '', '', '',		array('layout.defhide' => 2, 'layout.join' => 1, 'ctrl.size' => '10-40'));
}
$durchfuehrung->add_row(TABLE_INT | TABLE_EMPTYONNULL,		'stunden',			'Stunden ', 0, '0###9999', '', array('layout.descr.class' => 'e_bold'));
$durchfuehrung->add_row(TABLE_INT | TABLE_EMPTYONNULL,		'teilnehmer',		'max. Teilnehmende', 0, '0###999', '', array('layout.join' => 1));
$durchfuehrung->add_row(TABLE_INT | TABLE_EMPTYONMINUSONE,	'preis',			'Gesamtpreis/EUR ', -1, '-1###99999', '', array('layout.join' => 1, 'layout.descr.class' => 'e_bold', 'help.tooltip' => 'Gesamtpreis inkl. MwSt in ganzen Euro; 0=kostenlos'));
$durchfuehrung->add_row(TABLE_TEXT | TABLE_ACNORMAL,			'preishinweise',	'Preishinweise', '', '', '',					array('layout.defhide' => 1, 'layout.defhide.tooltip' => 'weitere Preisangaben', 'layout.join' => 1, 'help.tooltip' => 'Preishinweise, aber keine Angaben &aumlber die MwSt; Preise mï¿½ssen immer inkl. MwSt. angegeben werden!', 'ctrl.size' => '10-20-80'));
$durchfuehrung->add_row(TABLE_INT | TABLE_EMPTYONMINUSONE,	'sonderpreis',		'Sonderpreis/EUR ', -1, '-1###99999', '',		array('layout.defhide' => 2, 'layout.join' => 1, 'help.tooltip' => 'Sonderpreis, der ein paar Tage vor Kursbeginn gelten soll, inkl. MwSt. in ganzen Euro; optional; 0=kostenlos'));
$durchfuehrung->add_row(TABLE_INT | TABLE_EMPTYONNULL,		'sonderpreistage',	'Sonderpreis/Tage vor Beginn', 0, '0###999', '', array('layout.defhide' => 2, 'layout.join' => 1, 'help.tooltip' => 'Anzahl Tage vor Kursbeginn, aber der der Sonderpreis gelten soll; optional'));
$durchfuehrung->add_row(
	TABLE_DATE | TABLE_DAYMONTHOPT | TABLE_SUMMARY | TABLE_LIST | TABLE_NEWSECTION,
	'beginn',
	'Beginn ',
	'',
	'',
	'Termin',
	array('layout.after' => '&ndash;', 'layout.descr' => 'Datum', 'layout.descr.class' => 'e_bold', 'layout.bg.class' => 'e_bgliter')
);
$durchfuehrung->add_row(TABLE_DATE | TABLE_DAYMONTHOPT,		'ende',				'Ende ', '', '', '', array('layout.descr.hide' => 1));
if (!$use_neweditor) {
	$durchfuehrung->add_row(TABLE_ENUM | TABLE_SUMMARY,		'beginnoptionen',	'Terminoption ', 0, $codes_beginnoptionen);
	$durchfuehrung->add_row(TABLE_ENUM,							'dauer',			'Dauer', 0, $codes_dauer, '', array('layout.descr.hide' => 1, 'layout.join' => 1, 'help.tooltip' => 'Die Dauer wird aus Beginn- und Endedatum automatisch berechnet; nur wenn dort keine Angaben gemacht werden, gilt der direkt eingestellte Wert'));
}
$durchfuehrung->add_row(TABLE_TEXT, 						'zeit_von',			'Uhrzeit von ', '', $timecheck_reg, '', array('layout.join' => 1, 'layout.descr.hide' => 1, 'layout.after' => '&ndash;'));
$durchfuehrung->add_row(TABLE_TEXT, 						'zeit_bis', ($use_neweditor ? 'Uhrzeit bis' : 'bis '), '', $timecheck_reg, '', array('layout.join' => 1, 'layout.descr.hide' => 1, 'layout.after' => ' Uhr &nbsp; '));
$durchfuehrung->add_row(TABLE_BITFIELD,						'kurstage',			'Kurstage ', 0, $codes_kurstage, '', array('layout.join' => 1, 'layout.descr.hide' => 1));
$durchfuehrung->add_row(TABLE_ENUM,							'tagescode',		'Tagescode', 0, $codes_tagescode, '', array('layout.descr.hide' => 1, 'layout.defhide' => 1, 'layout.defhide.tooltip' => 'weitere Datumsangaben', 'help.tooltip' => 'Der Tagescode wird aus der Uhrzeit und den Kurstagen automatisch berechnet; nur wenn dort keine Angaben gemacht werden, gilt der direkt eingestellte Wert'));
if ($use_neweditor) {
	$durchfuehrung->add_row(TABLE_ENUM,						'dauer',			'Dauer', 0, $codes_dauer, '', array('layout.defhide' => 1, 'layout.descr.hide' => 1, 'layout.join' => 1, 'help.tooltip' => 'Die Dauer wird aus Beginn- und Endedatum automatisch berechnet; nur wenn dort keine Angaben gemacht werden, gilt der direkt eingestellte Wert'));
	$durchfuehrung->add_row(TABLE_BITFIELD | TABLE_LIST,		'dauer_fix',		'Dauer Fix', 0, '1###', '', array('layout.defhide' => 1, 'layout.join' => 1, 'ctrl.checkboxes' => 1, 'layout.descr.class' => 'dauer_fix_label', 'ctrl.class' => 'dauer_fix'));
	$durchfuehrung->add_row(TABLE_ENUM | TABLE_SUMMARY,		'beginnoptionen',	'Terminoption', 0, $codes_beginnoptionen, '', array('layout.join' => 1, 'layout.defhide' => 1, 'layout.descr.hide' => 1)); // nur ca. 10% der Kurse haben eine Terminoption, kann man also verstecken ...
}
$durchfuehrung->add_row(
	TABLE_TEXT | TABLE_NEWSECTION | TABLE_ACNESTSTART,
	'strasse',
	'Stra&szlig;e ',
	'',
	'',
	'Veranstaltungsort',
	array('layout.descr' => 'Veranstaltungsort', 'layout.descr.hide' => 1, 'ctrl.placeholder' => 1, 'ctrl.size' => '8-16-48')
);
$durchfuehrung->add_row(TABLE_TEXT | TABLE_ACNESTSTART | TABLE_ACNEST,				'plz',				'PLZ ', '', 5, '', array('layout.after' => ' ', 'layout.descr.hide' => 1, 'ctrl.placeholder' => 1));
$durchfuehrung->add_row(TABLE_TEXT | TABLE_ACNEST,			'ort',				'Ort ', '', '', '', array('ctrl.size' => '8-16-48', 'layout.descr.hide' => 1, 'ctrl.placeholder' => 1));
$durchfuehrung->add_row(TABLE_TEXT | TABLE_ACNEST,			'stadtteil',		'Stadtteil ', '', '', '', array('ctrl.size' => '8-16-48', 'layout.defhide.tooltip' => 'weitere Ortsangaben')); // , 'layout.defhide'=>1
$durchfuehrung->add_row(TABLE_TEXT | TABLE_ACNEST,			'bezirk',			'Bezirk ', '', '', '', array('layout.descr.hide' => 1, 'ctrl.class' => 'df_bezirk', 'layout.defhide' => 1, 'ctrl.placeholder' => 1));
$durchfuehrung->add_row(TABLE_TEXT | TABLE_ACNEST,			'land',				'Land ', '', 3, '', array('layout.defhide' => 1));
$durchfuehrung->add_row(TABLE_FLAG,							'rollstuhlgerecht',	'Barrierefreier Zugang', '', '', '', array('layout.defhide' => 1));
$durchfuehrung->add_row(TABLE_TEXTAREA | TABLE_WIKI,			'bemerkungen',		'Bemerkungen', '', '', '', array('layout.defhide' => 1, 'ctrl.rows' => 2));

$durchfuehrung->add_row(TABLE_TEXT | TABLE_URL,							'anbieterurl',	'Link zum Angebot', '', '', '',  array('ctrl.size' => '10-20-50', 'layout.join' => 0));
if (!$use_neweditor) {
	$durchfuehrung->add_row(TABLE_TEXT | TABLE_NEWSECTION,		'bg_nummer',	'Ma&szlig;nahmen-Nummer', '', '', 'Ma&szlig;nahmen-Nummers');
	$durchfuehrung->add_row(TABLE_TEXT, 						'bu_dnummer', 	'BU-Durchf&uumlhrungsnummer');
	$durchfuehrung->add_row(TABLE_TEXT, 						'wisy_dnr', 	'Wisy-Durchf&uumlhrungsnummer');
	$durchfuehrung->add_row(TABLE_TEXT, 						'fu_dnr', 		'FU-Durchf&uumlhrungsnummer');
	$durchfuehrung->add_row(TABLE_TEXT, 						'foerder_dnr', 	'F&oumlrder-Durchf&uumlhrungsnummer');
	$durchfuehrung->add_row(TABLE_TEXT, 						'azwv_dnr', 	'AZAV-Durchf&uumlhrungsnummerr');
}

// $durchfuehrung->add_row(TABLE_TEXT|TABLE_ACNEST,			'url',				'URL ', '', 3, '', array('layout.defhide'=>1));

// $durchfuehrung->add_row(TABLE_DATETIME|TABLE_LIST|TABLE_READONLY, 'date_modified',			'Aenderungsdatum', '', '', '', array('layout.descr'=>'Aenderungsdatum', 'ctrl.class'=>'df_aenderungsdatum_ctrl', 'layout.descr.class'=>'df_aenderungsdatum_descr', 'layout.bg.class'=>'df_aenderungsdatum_bg'));
$durchfuehrung->set_trigger('config/trigger_durchfuehrung.inc.php');    // make sure changes to DF trigger calculations, like last df change date

/*** PORTALE ***/
/*** HINWEIS: Einstellungen geh&aumlren als INI-Wert in das Feld portale.einstellungen - sonst haben wir hier ganz schnell Chaos! (bp) ***/
$portale = new Table_Def_Class(TABLE_PRIMARY,						'portale',			'Portale');
$portale->add_row(TABLE_ENUM,								        'status',	        'Status', 1, '1###Freigegeben###3###Archiv', '', array('layout.join' => 1, 'layout.descr.hide' => 1));
$portale->add_row(TABLE_TEXT | TABLE_SUMMARY | TABLE_LIST | TABLE_MUST,	'name',				'Name', 0, 0, '', array('ctrl.size' => '10-20-80', 'layout.bg.class' => 'e_bglite', 'layout.descr.class' => 'e_bolder', 'ctrl.class' => 'e_bolder'));
$portale->add_row(TABLE_TEXT | TABLE_MUST,							'kurzname',			'Kurzname', 0, 0, '', array('layout.join' => 1, 'ctrl.size' => '7-15-40'));
$portale->add_row(TABLE_TEXT | TABLE_LIST | TABLE_MUST,					'domains',			'Domains', 0, 0, '', array('ctrl.size' => '10-20-100'));
$portale->add_row(TABLE_TEXTAREA | TABLE_NEWSECTION, 					'einstellungen',	'Einstellungen', 0, 0, 'Einstellungen', array('help.url' => 'https://b2b.kursportal.info/index.php?title=Portaleinstellungen', 'ctrl.rows' => 10));
$portale->add_row(TABLE_TEXTAREA | TABLE_READONLY, 					'einstellungen_hinweise', 'Hinweise', 0, 0, '', array('help.url' => 'https://b2b.kursportal.info/index.php?title=Portaleinstellungen_Hinweise', 'ctrl.rows' => 3));
$portale->add_row(TABLE_TEXTAREA | TABLE_NEWSECTION, 					'bodystart',		'HTML', 0, 0, 'Layout', array('help.url' => 'https://b2b.kursportal.info/index.php?title=Portallayout', 'ctrl.rows' => 3, 'layout.section' => 'Layout'));
$portale->add_row(TABLE_TEXTAREA, 									'css',				'CSS', 0, 0, 'Layout', array('help.url' => 'https://b2b.kursportal.info/index.php?title=Portallayout', 'ctrl.rows' => 3));
$portale->add_row(TABLE_TEXTAREA | TABLE_NEWSECTION, 					'filter',			'Filter', 0, 0, 'Filter', array('help.url' => 'https://b2b.kursportal.info/index.php?title=Portaleinstellungen#Filtereinstellungen', 'ctrl.rows' => 3, 'layout.section' => 'Filter'));
$portale->add_row(TABLE_TEXTAREA | TABLE_READONLY,					'einstcache',		'Cache', 0, 0, '', array('layout.defhide' => 2, 'layout.descr.hide' => 1, 'layout.join' => 1));
$portale->add_row(TABLE_TEXTAREA | TABLE_NEWSECTION,			'notizen_fix',			'Anmerkungen', '', '', '',  array('layout.section' => 1));
$portale->add_row(TABLE_TEXTAREA,					'notizen',			'Journal', '', '', '');
/*** /HINWEIS: Einstellungen geh&aumlren als INI-Wert in das Feld portale.einstellungen - sonst haben wir hier ganz schnell Chaos! (bp) ***/
/*** Verworfene Werte: skindir, iwwb, iwwb_filter, iwwb_style, logo_1, logo_1_href, logo_2, logo_2_href, menuswitch, print_img, spalten, themen_erlauben, themen_verbieten, qual_logo, qual_logo_gloss, qual_logo_stich, horizont, horizontende, betreiberID (bp) ***/


/*** KURSE ***/
$kurse = new Table_Def_Class(TABLE_PRIMARY | TABLE_SYNCABLE,		'kurse',			$use_neweditor ? 'Angebote' : 'Kurse');
if ($use_neweditor) {
	$kurse->add_row(TABLE_TEXT, 									'azwv_knr', 		'AZAV-Kursnr.', '', '', '',		array('ctrl.size' => '3-10-40', 'layout.defhide' => 1, 'layout.join' => 1, 'layout.defhide.tooltip' => 'weitere Verwaltungsnummern'));
	$kurse->add_row(TABLE_TEXT | TABLE_NEWSECTION,					'bu_nummer',		'BU-Kursnr.', '', '', 'Kurs-IDs',	array('ctrl.size' => '10-40', 'layout.defhide' => 1, 'layout.join' => 1));
	//$kurse->add_row(TABLE_TEXT, 									'res_nummer', 		'BU-Kursnr.', '','', '',		array('ctrl.size'=>'10-40', 'layout.defhide'=>2, 'layout.join'=>1));
	$kurse->add_row(TABLE_TEXT, 									'fu_knr', 		    'FU-Kursnr.', '', '', '',		array('ctrl.size' => '10-40', 'layout.defhide' => 2, 'layout.join' => 1));
	$kurse->add_row(TABLE_TEXT, 									'foerder_knr', 		'F&oumlrder-Kursnr.', '', '', '',	array('ctrl.size' => '10-40', 'layout.defhide' => 2, 'layout.join' => 1));
	
	//Kategorien der Kurse (Karl Weber)
	$db = new DB_Admin();
	$level_selct_kat_values = '-1###onchange=
	 "var bestaetigung = window.confirm(\'Beim Kategoriewechsel werden alle nicht gespeicherten Eingaben geloescht. Wollen Sie das?\'); 
	 if (bestaetigung) {window.location=\'edit.php?table=kurse###&kat=\' + this.value;return false;}
	 else { return false;}"';
	$levels_kat = ['Andere', 'Berufliche Bildung', 'Sprache'];
	$kat_selected = 0;
	foreach ($levels_kat as $level) {
		$sql = 'SELECT id FROM stichwoerter WHERE stichwoerter.stichwort = "' . $level . '"';
		$db->query($sql);
		if ($db->next_record()) {
			if ($level == "Andere")
				$kat_selected = $db->Record['id'];
			$level_selct_kat_values .= '###' . $db->Record['id'] . '###' . $level;
		}
	}
	$kurse->add_row(TABLE_ENUM | TABLE_DB_IGNORE | TABLE_MUST,					'kategorie',	        'Kategorie ', $kat_selected, $level_selct_kat_values, '', array('layout.join' => 1));
	//------Ende Kategorien

	$kurse->add_row(TABLE_ENUM,										'freigeschaltet',	'Status ', 1, '0###In Vorbereitung###1###Freigegeben###3###Abgelaufen###4###Dauerhaft###2###Gesperrt', '', array('layout.join' => 1, 'layout.descr.hide' => 1));


	$kurse->add_row(
		TABLE_INT | TABLE_EMPTYONNULL | TABLE_READONLY | TABLE_PERCENT,
		'vollstaendigkeit',
		'Vollst&aumlndigkeit',
		0,
		'0###100',
		0,
		array('layout.defhide' => 1, 'layout.after' => '%')
	);
	
}
$title_warning =
'-1###onclick="window.confirm(\'Bitte vor der Titeleingabe eine Kategorie waehlen.\')"';
$kurse->add_row(TABLE_TEXT | TABLE_SUMMARY | TABLE_LIST | TABLE_MUST,	'titel',			'Titel ', 0,$title_warning  , '', array('ctrl.size' => '10-30-100', 'ctrl.class' => 'e_bolder', 'layout.descr.class' => 'e_bolder', 'layout.bg.class' => 'e_bglite'));
if ($use_neweditor) {
	$kurse->add_row(TABLE_TEXT, 									'org_titel', 		'Originaltitel', 0, 0, '', array('layout.join' => 1, 'ctrl.size' => '10-80', 'layout.defhide' => 2));
	$kurse->add_row(TABLE_TEXT | TABLE_READONLY, 						'titel_sorted', 		'Sortier-Titel', 0, 0, '', array('layout.join' => 1, 'ctrl.size' => '10-80', 'layout.defhide' => 2));
}
if (!$use_neweditor) {
	$kurse->add_row(TABLE_ENUM,										'freigeschaltet',	'Status ', 1, '0###In Vorbereitung###1###Freigegeben###3###Abgelaufen###4###Dauerhaft###2###Gesperrt', '', array('layout.join' => 1));
	$kurse->add_row(
		TABLE_INT | TABLE_EMPTYONNULL | TABLE_READONLY | TABLE_PERCENT,
		'vollstaendigkeit',
		'Vollst&aumlndigkeit',
		0,
		'0###100',
		0,
		array('layout.defhide' => 1, 'layout.after' => '%')
	);
}
if (!$use_neweditor) {
	$kurse->add_row(TABLE_TEXT, 									'org_titel', 		'Originaltitel', 0, 0, '');
}
$kurse->add_row(
	TABLE_SATTR | TABLE_LIST | TABLE_TRACKDEFAULTS | TABLE_MUST,
	'anbieter',
	'Anbieter',
	0,
	$anbieter
);
$kurse->add_row(TABLE_TEXTAREA | TABLE_WIKI | TABLE_NEWSECTION,		'beschreibung',		'Beschreibung', 0, 0, 'Kursportrï¿½t', array('layout.join' => 0));
$kurse->add_row(TABLE_TEXTAREA | TABLE_WIKI | TABLE_NEWSECTION,		'lernziele',		'Lernziele', 0, 0, 'Lernziele');
$kurse->add_row(TABLE_TEXTAREA | TABLE_WIKI | TABLE_NEWSECTION,		'vorraussetzungen',		'Vorraussetzungen', 0, 0, 'Vorraussetzungen');
$kurse->add_row(TABLE_TEXTAREA | TABLE_WIKI | TABLE_NEWSECTION,		'zielgruppe',		'Zielgruppe', 0, 0, 'Zielgruppe');
$kurse->add_row(TABLE_MATTR_BR | TABLE_SATTR | TABLE_TRACKDEFAULTS,				'thema',			'Thema', 0, $themen, '');

// Dynammically get the respective stichwort ids for the competency levels from the database.

$level_selct_values = '0###keine Angabe';
$levels = ['Niveau A', 'Niveau B', 'Niveau C'];
foreach ($levels as $level) {
	$sql = 'SELECT id FROM stichwoerter WHERE stichwoerter.stichwort = "' . $level . '"';
	$db->query($sql);
	if ($db->next_record()) {
		$level_selct_values .= '###' . $db->Record['id'] . '###' . $level;
	}
}
// Adds a dropdown for selecting a competency niveau to the edit page for 'kurse'.
$kurse->add_row(TABLE_ENUM | TABLE_DB_IGNORE,						'level',	        'Kompetenzniveau', 0, $level_selct_values, '', array('showIfSetting' => 'hideifnojob', 
'help.tooltip' => 'A Grundstufe: Erf&uuml;llung grundlegender Aufgaben nach Anleitung | B Aufbaustufe: &uuml;berwiegend selbstst&auml;ndige Umsetzung erweiterter Aufgaben | C: Fortgeschrittenenstufe, Expert*innenstufe: eigenverantwortlichen Umsetzung komplexer Aufgaben ', 'layout.join' => 0));
$speech_selct_values = '0###keine Angabe';
$levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
foreach ($levels as $level) {
	$sql = 'SELECT id FROM stichwoerter WHERE stichwoerter.stichwort = "' . $level . '"';
	$db->query($sql);
	if ($db->next_record()) {
		$speech_selct_values .= '###' . $db->Record['id'] . '###' . $level;
	}
}
// Adds a dropdown for selecting a competency niveau to the edit page for 'kurse'.
$kurse->add_row(TABLE_ENUM | TABLE_DB_IGNORE,						'speech',	        'Sprachniveau', 0, $speech_selct_values, '', array('showIfSetting' => 'hideifnospeech', 'layout.join' => 0));
$kurse->add_row(TABLE_MATTR  |  TABLE_TRACKDEFAULTS,				'kompetenz',			'Kompetenzen', 0, $kompetenz, '', array('layout.join' => 0) );
$kurse->add_row(TABLE_MATTR  |  TABLE_TRACKDEFAULTS  |  TABLE_DB_IGNORE,      					'vorschlaege',			'Kompetenzvorschl&aumlge ', 0,$kompetenz  , '', array( 'layout.join' => 0));
$kurse->add_row(TABLE_TEXT, 									'num_prop', 		'Anzahl Kompetenzvorschl&aumlge', '', '', '',		array('layout.join' => 1));
$kurse->add_row(TABLE_TEXT, 									'rel_prop', 		'Minimale Relevanz der Kompetenzvorschl&aumlge', '', '', '',		array('layout.join' => 1));

$kurse->add_row( TABLE_DB_IGNORE, 									'dummy', 		'', 0, 0, '', array('layout.join' => 0));
//$kurse->add_row(TABLE_MATTR_BR | TABLE_SATTR | TABLE_TRACKDEFAULTS | TABLE_DB_IGNORE | TABLE_UNIQUE,	'sucheesco',			'Suche nach ESCO-Kompetenzen', 0, $esco, '', array('layout.join' => 1));
$kurse->add_row(TABLE_MATTR | TABLE_TRACKDEFAULTS,				'stichwort',		'Stichw&oumlrter', 0, $stichwoerter, '', array('layout.join' => 0));
$kurse->add_row(
	TABLE_TEXT,
	'msgtooperator',
	'Stichwortvorschl&aumlge',
	0,
	0,
	'',
	array(
		'layout.join' => 0, 'layout.defhide' => 0,
		'help.tooltip' => 'Stichwortvorschl&aumlge vom Anbieter',
		'ctrl.size' => '10-20-60',
		'ctrl.class' => 'vorschlag',
		'layout.descr.class' => 'vorschlag_label'
	)
);

$kurse->add_row(
	TABLE_TEXT | TABLE_READONLY,
	'msgtooperator_unterrichtsart',
	'Vorschl&aumlge Unterrichtsart',
	0,
	0,
	'',
	array(
		'layout.join' => 1, 'layout.defhide' => 0,
		'help.tooltip' => 'Unterrichtsart-Vorschlï&aumlge vom Anbieter. Diese sind zur Bearbeitung gesperrt, weil sie bei der Onlinepflege mit existierenden SW abgeglichen werden m&aumlssen.',
		'ctrl.size' => '60-60-200',
		'ctrl.class' => 'vorschlag',
		'layout.descr.class' => 'vorschlag_label',
		'value.table_key' => array('table' => 'stichwoerter', 'key' => 'id', 'value' => 'stichwort'),
		'layout.value.aslabel' => 1,
		'layout.input.hide' => 1
	)
);  //'value.replace'=>array( array('###'), array(',') )
$kurse->add_row(TABLE_MATTR_BR | TABLE_SATTR | TABLE_TRACKDEFAULTS | TABLE_DB_IGNORE | TABLE_UNIQUE,				'abschluss',			'Abschluss', 0, $abschluss, '');
$kurse->add_row(TABLE_MATTR  |  TABLE_TRACKDEFAULTS,				'lernform',			'Lernform', 0, $lernform, '');
$kurse->add_row(TABLE_MATTR  |  TABLE_TRACKDEFAULTS,				'foerderung',			'F&oumlrderungsart', 0, $foerderungsart, '');
$kurse->add_row(TABLE_FLAG | TABLE_DB_IGNORE,							'permentry',	'Einstieg bis Kursende m&oumlglich', '', $permentry, '',  array('layout.join' => 0));
$kurse->add_row(TABLE_SECONDARY | TABLE_TRACKDEFAULTS,			'durchfuehrung',	'Durchf&uumlhrung', 1, $durchfuehrung);
if (!$use_neweditor) {
	$kurse->add_row(TABLE_TEXT | TABLE_NEWSECTION,					'bu_nummer',		'BU-Kursnummer', '', '', 'Kurs-IDs', array('layout.join' => 1));
	//$kurse->add_row(TABLE_TEXT, 									'res_nummer', 		'BU-Kursnummer', '','', '', array('layout.join'=>1));
	$kurse->add_row(TABLE_TEXT, 									'fu_knr', 		    'FU-Kursnummer', '', '', '', array('layout.join' => 1));
	$kurse->add_row(TABLE_TEXT, 									'foerder_knr', 		'Fï¿½rder-Kursnummer', '', '', '', array('layout.join' => 1));
	$kurse->add_row(TABLE_TEXT, 									'azwv_knr', 		'AZAV-Kursnummer', '', '', '', array('layout.join' => 1));
}

if ($use_neweditor) {
	$kurse->add_row(
		TABLE_DATETIME | TABLE_DAYMONTHOPT | TABLE_SUMMARY | TABLE_LIST | TABLE_NEWSECTION | TABLE_READONLY,
		'x_df_lastinserted',
		'Letzte DF-Erstellung',
		'',
		'',
		'Termin',
		array('cmsSearchMsg' => 'Ber%FCcksichtigt:\n-Redaktionssystem (Detail+Multiedit)\n-REST\n-Onlinpflege.\n\nAchtung: Immer auch ein Minimaldatum angeben - nicht nur ein Maximaldatum - um leere Felder auszuschlie%DFen / keine Kurse zu l%F6schen, %FCber die man nichts wei%DF!\n\nAchtung: DBMix-Importe noch nicht ber%FCcksichtigt!\n\nAchtung: GGf. Portale mit aktiven Module bzgl. Onlinepflege-Datum noch nicht ber%FCcksichtigt.', 'showIfSetting' => 'edit.df.showchanges', 'layout.descr' => 'Letzte DF-Erstellung', 'layout.after' => '', 'layout.bg.class' => '', 'layout.descr.class' => 'df_lastinsertedDescr', 'ctrl.class' => 'df_lastinsertedCtrl')
	);

	$kurse->add_row(
		TABLE_TEXT | TABLE_READONLY,
		'x_df_lastinserted_origin',
		'Letzte DF-Erstellung Ursache',
		'',
		'',
		'Termin',
		array('showIfSetting' => 'edit.df.showchanges', 'layout.join' => 1, 'layout.descr.hide' => 1, 'layout.descr' => 'DFInsertedChangeOrigin', 'layout.after' => '', 'layout.bg.class' => '', 'layout.descr.class' => 'df_lastinsertedOrigin', 'ctrl.class' => 'df_lastinsertedOriginCtrl')
	);

	$kurse->add_row(
		TABLE_DATETIME | TABLE_DAYMONTHOPT | TABLE_SUMMARY | TABLE_LIST | TABLE_NEWSECTION | TABLE_READONLY,
		'x_df_lastmodified',
		'Letzte DF-&Auml;nderung',
		'',
		'',
		'Termin',
		array('cmsSearchMsg' => 'Ber%FCcksichtigt:\n-Redaktionssystem (Detail+Multiedit)\n-REST\n-Onlinpflege.\n\nAchtung: Immer auch ein Minimaldatum angeben - nicht nur ein Maximaldatum - um leere Felder auszuschlie%DFen / keine Kurse zu l%F6schen, %FCber die man nichts wei%DF!\n\nAchtung: DBMix-Importe noch nicht ber%FCcksichtigt!\n\nAchtung: GGf. Portale mit aktiven Module bzgl. Onlinepflege-Datum noch nicht ber%FCcksichtigt.', 'showIfSetting' => 'edit.df.showchanges', 'layout.join' => 1, 'layout.descr' => 'Letzte DF-&Auml;nderung', 'layout.after' => '', 'layout.bg.class' => '', 'layout.descr.class' => 'df_lastmodifiedDescr', 'ctrl.class' => 'df_lastmodifiedCtrl')
	);

	$kurse->add_row(
		TABLE_TEXT | TABLE_READONLY,
		'x_df_lastmodified_origin',
		'Letzte DF-&Auml;nderung Ursache',
		'',
		'',
		'Termin',
		array('showIfSetting' => 'edit.df.showchanges', 'layout.join' => 1, 'layout.descr.hide' => 1, 'layout.descr' => 'DFInsertedChangeOrigin', 'layout.after' => '', 'layout.bg.class' => '', 'layout.descr.class' => 'df_lastmodifiedOrigin', 'ctrl.class' => 'df_lastmodifiedOriginCtrl')
	);

	$kurse->add_row(
		TABLE_DATETIME | TABLE_DAYMONTHOPT | TABLE_SUMMARY | TABLE_LIST | TABLE_NEWSECTION | TABLE_READONLY,
		'x_df_lastdeleted',
		'Letzte DF-L&ouml;schung',
		'',
		'',
		'Termin',
		array('cmsSearchMsg' => 'Ber%FCcksichtigt:\n-Redaktionssystem (Detail+Multiedit)\n-REST\n-Onlinpflege.\n\nAchtung: Immer auch ein Minimaldatum angeben - nicht nur ein Maximaldatum - um leere Felder auszuschlie%DFen / keine Kurse zu l%F6schen, %FCber die man nichts wei%DF!\n\nAchtung: DBMix-Importe noch nicht ber%FCcksichtigt!\n\nAchtung: GGf. Portale mit aktiven Module bzgl. Onlinepflege-Datum noch nicht ber%FCcksichtigt.', 'showIfSetting' => 'edit.df.showchanges', 'layout.join' => 1, 'layout.descr' => 'Letzte DF-L&ouml;schung', 'layout.bg.class' => '', 'layout.descr.class' => 'df_lastdeletedDescr', 'ctrl.class' => 'df_lastdeletedCtrl')
	);

	$kurse->add_row(
		TABLE_TEXT | TABLE_READONLY,
		'x_df_lastdeleted_origin',
		'Letzte DF-L&ouml;schung Ursache',
		'',
		'',
		'Termin',
		array('showIfSetting' => 'edit.df.showchanges', 'layout.join' => 1, 'layout.descr.hide' => 1, 'layout.descr' => 'DFInsertedChangeOrigin', 'layout.after' => '', 'layout.bg.class' => '', 'layout.descr.class' => 'df_lastdeletedOrigin', 'ctrl.class' => 'df_lastdeletedOriginCtrl')
	);
}


$kurse->add_row(TABLE_TEXTAREA | TABLE_NEWSECTION,			'notizen_fix',			'Anmerkungen', '', '', '',  array('layout.section' => 1));
$kurse->add_row(TABLE_TEXTAREA,				'notizen',			'Journal', '', '', '');
$kurse->set_trigger('config/trigger_kurse.inc.php');


/*** FEEDBACK ***/
$feedback = new Table_Def_Class(0,											'feedback',			'Feedback');
$feedback->add_row(TABLE_TEXT | TABLE_LIST | TABLE_MUST | TABLE_READONLY,			'ip',				'Feedback von');
$feedback->add_row(
	TABLE_TEXT | TABLE_SUMMARY | TABLE_LIST | TABLE_MUST | TABLE_READONLY | TABLE_URL,
	'url',
	'Bewertete URL',
	'',
	'',
	'',
	array('ctrl.size' => '10-80')
);
$feedback->add_row(TABLE_ENUM | TABLE_LIST | TABLE_READONLY,					'rating',			'Wertung', 0, '0###nicht hilfreich###1###hilfreich');
$feedback->add_row(TABLE_TEXTAREA | TABLE_LIST | TABLE_READONLY,				'descr',			'Kommentar');
$feedback->add_row(TABLE_TEXT | TABLE_LIST | TABLE_READONLY,					'name',			'Name');
$feedback->add_row(TABLE_TEXT | TABLE_LIST | TABLE_READONLY,					'email',			'Email', '', '', '', array('ctrl.size' => '1-300'));
$feedback->add_row(TABLE_TEXTAREA | TABLE_NEWSECTION, 						'notizen', 			'Journal', '', '', '', array('layout.section' => 1));

/*** Ticketing-System ***/
$tickets = new Table_Def_Class(0,											'tickets',			'Tickets (Kunden-Anfragen)');
$tickets->add_row(TABLE_ENUM,								'status',	        'Status ', 1, '0###Neu###1###In Bearbeitung###3###Erledigt###4###Archiviert', '', array('layout.join' => 1));

$tickets->add_row(TABLE_TEXT | TABLE_LIST | TABLE_READONLY,					'von_name',			'Von', 	'', '', '', array('layout.section' => 'Absender'));
$tickets->add_row(TABLE_TEXT | TABLE_LIST | TABLE_READONLY,					'von_email',			'E-Mail', 	'', '', '',	array('ctrl.size' => '10-200', 'layout.join' => 1));
$tickets->add_row(TABLE_TEXT | TABLE_LIST | TABLE_READONLY,					'antwortan_name',			'Antwort an', 	'', '', '');
$tickets->add_row(TABLE_TEXT | TABLE_LIST | TABLE_READONLY,					'antwortan_email',			'E-mail', 	'', '', '',	array('ctrl.size' => '10-200', 'layout.join' => 1));
$tickets->add_row(
	TABLE_TEXT | TABLE_SUMMARY | TABLE_LIST | TABLE_READONLY,
	'betreff',
	'Betreff',
	'',
	'',
	'',
	array('layout.section' => 'Nachricht', 'layout.bg.class' => 'betreff', 'layout.descr.class' => 'e_bolder', 'ctrl.class' => 'e_bolder')
);
$tickets->add_row(TABLE_TEXTAREA | TABLE_LIST | TABLE_READONLY,		'nachricht_txt',			'Nachricht');
$tickets->add_row(
	TABLE_DATETIME | TABLE_LIST | TABLE_READONLY,
	'date_created',
	'Eingang',
	'',
	'',
	'',
	array('layout.descr' => 'Eingang')
);
// $tickets->add_row(TABLE_TEXTAREA|TABLE_LIST|TABLE_READONLY,		'nachricht_html',			'Nachricht');
$tickets->add_row(TABLE_TEXT | TABLE_LIST | TABLE_READONLY,					'groesse',			'Grï¿½ï¿½e');
$tickets->add_row(TABLE_TEXT | TABLE_LIST | TABLE_MUST | TABLE_READONLY,			'msgid',				'Nachrichten-ID');
$tickets->add_row(TABLE_TEXTAREA | TABLE_NEWSECTION, 						'notizen', 			'Journal', '', '', '', array('layout.section' => 1));



/*** anbieter_promote etc. (fuer die Werbung) ***/
$anbieter_promote = new Table_Def_Class(0,			'anbieter_promote',	'Anbieterwerbung');
$anbieter_promote->add_row(TABLE_ENUM | TABLE_LIST,	'promote_active',		'Status', 0, '0###Inaktiv###1###Aktiv');
$anbieter_promote->add_row(TABLE_TEXT | TABLE_LIST,	'promote_mode',			'Modus');
$anbieter_promote->add_row(TABLE_TEXT | TABLE_LIST,	'promote_param',		'Wert');
$anbieter_promote->add_row(TABLE_SATTR | TABLE_LIST,	'anbieter_id',			'Anbieter', 0, $anbieter);
$anbieter_promote->add_row(TABLE_SATTR | TABLE_LIST,	'portal_id',			'Portal', 0, $portale);
$anbieter_promote->add_row(TABLE_SATTR | TABLE_LIST,	'kurs_id',				'Kurs', 0, $kurse);

$anbieter_promote_log = new Table_Def_Class(0,			'anbieter_promote_log',	'Anbieterlog');
$anbieter_promote_log->add_row(TABLE_ENUM | TABLE_LIST,	'event_type',			'Aktion', 0, '1001###Einblendungen###1002###Klicks###2001###aktueller Kredit');
$anbieter_promote_log->add_row(TABLE_INT  | TABLE_LIST,	'lparam',				'Wert');
$anbieter_promote_log->add_row(TABLE_SATTR | TABLE_LIST,	'anbieter_id',			'Anbieter', 0, $anbieter);
$anbieter_promote_log->add_row(TABLE_SATTR | TABLE_LIST,	'portal_id',			'Portal', 0, $portale);
$anbieter_promote_log->add_row(TABLE_SATTR | TABLE_LIST,	'kurs_id',				'Kurs', 0, $kurse);

$anbieter_billing = new Table_Def_Class(0,			'anbieter_billing',	'Anbieterrechnungen');
$anbieter_billing->add_row(TABLE_ENUM | TABLE_LIST | TABLE_READONLY,	'bill_type',			'Rechnungstyp', 0, '9001###automatisch erstellte Paypal-Rechnung###9002###manuell erstellte Rechnung###9099###fehlerhafte Rechnung');
$anbieter_billing->add_row(TABLE_TEXT | TABLE_LIST | TABLE_READONLY | TABLE_NEWSECTION,	'eur',	'Euro', '', 0, 'Rechnung');
$anbieter_billing->add_row(TABLE_INT  | TABLE_LIST | TABLE_READONLY,	'credits',				'Anzahl Kredite');
$anbieter_billing->add_row(TABLE_SATTR | TABLE_LIST | TABLE_READONLY,	'anbieter_id',			'Anbieter', 0, $anbieter);
$anbieter_billing->add_row(TABLE_SATTR | TABLE_LIST | TABLE_READONLY,	'portal_id',			'Portal', 0, $portale);
$anbieter_billing->add_row(TABLE_TEXTAREA | TABLE_READONLY,			'raw_data',				'Details');
$anbieter_billing->add_row(TABLE_TEXTAREA | TABLE_NEWSECTION,			'notizen',				'Journal', '', '', '', array('layout.section' => 1));



/*** APIKEYS ***/
$apikeys = new Table_Def_Class(0,								'apikeys',			'API-Keys');
$apikeys->add_row(TABLE_TEXT | TABLE_LIST | TABLE_MUST,				'name',				'Name', '', '', '', array('ctrl.size' => '10-80', 'layout.bg.class' => 'e_bglite', 'layout.descr.class' => 'e_bolder', 'ctrl.class' => 'e_bolder'));
$apikeys->add_row(TABLE_TEXT | TABLE_LIST | TABLE_UNIQUE,			'apikey',			'persï¿½nlicher API-Key', 'wird automatisch erzeugt', '', '', array('ctrl.size' => '25-80'));
$apikeys->add_row(TABLE_BITFIELD | TABLE_LIST,					'flags',			'Optionen', 1 + 2, '1###Freigeschaltet###2###Verschl&uuml;sselte Verbindung###4###Schreibzugriff erlauben###8###Journal Lesezugriff', '', array('ctrl.checkboxes' => 1));
$apikeys->add_row(TABLE_MATTR,			                        'usergrp',  		'Zugriffsbeschrï¿½nkung', 0, 'user_grp', '', array('layout.after' => '<br>Wenn hier Gruppen eingetragen werden, kï¿½nnen neue Datensï¿½tze nur mit diesen Gruppen erstellt werden und bestehende kï¿½nnen nur bearbeitet/gelï¿½scht werden, wenn sie einer der Gruppen angehï¿½ren.'));
$apikeys->add_row(TABLE_TEXTAREA | TABLE_NEWSECTION, 				'notizen', 			'Journal', '', '', '', array('layout.section' => 1));
$apikeys->set_trigger('config/trigger_apikeys.inc.php');




$Table_Def[] = $tickets;
$Table_Def[] = $feedback;
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
$Table_Def[] = $apikeys;
$Table_Def[] = $escocategories;
$Table_Def[] = $escoskills;



$Table_Shortnames = array(
	'Stunden'						=>	'Std.',
	'Gesamtpreis/EUR'				=>	'Preis',
	'Sonderpreis/EUR'				=>	'Sonderpr.',
	'Sonderpreis/Tage vor Beginn'	=>	'Tage',
	'Durchf&uumlhrungs-Nr.'				=>	'Nr.',
	'Stunden'						=>	'Std.',
	'Uhrzeit von'					=>	'Zeit',
	'max. Teilnehmende'				=>	'Teiln.',
	'Rollstuhlgerecht'				=>	'Barrierefr.Z..'
);



// do not remove the following line, it creates the user table with the access
// rights depending on $Table_Def above. this line must be last after your
// table definitions
Table_Def_Finish(array(
	'user.settings.help.url' => 'https://b2b.kursportal.info/index.php?title=Benutzereinstellungen',
	'user_grp.settings.help.url' => 'https://b2b.kursportal.info/index.php?title=Gruppeneinstellungen',
));



// Additional settings ( CMS => Einstellungen => Ansicht)
// Text fields for settings:
// Every first value (%1) = Field Name
// Every second value (%2) = Setting Name in DB
$g_addsettings_view             = array("Domain f&aumlr 'Ansicht'", 'view.domain');                   // if no sepcific value is set for this user it will fall back / show the value of user "template" in CMS!
$g_addsettings_misc             = array("Export.ApiKey f&uuml;r Synchronisation<br>Kann nur 'template' setzen", 'export.apikey');    // if no sepcific value is set for this user it will fall back / show the value of user "template" in CMS!
$g_addsettings_userTemplateOnly = array('export.apikey');                                         // any of these settings are to be displayed for and can only be set by user "template" only

// in dieses Verzeichnis werden zu exportierende Dateien tempor&aumlr gelagert; andere Dateien in diesem Verzeichnis werden gel&aumlscht!
$g_temp_dir = '../temp';