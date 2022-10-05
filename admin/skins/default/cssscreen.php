<?php

// make sure, the file is cached
header("Cache-Control: public");
header('Expires: ' . gmdate("D, d M Y H:i:s", intval(time()+43200)) . ' GMT'); // in 12 hours
header('Content-type: text/css');



$menubg		= '#FFE190';
$menuborder = '#FFC62D';
$link		= '#0030CE';
$hover 		= '#CE3000'; // should unique in all color schemes - until we have a solution for the linked images
$evencell	= '#E7EFFF';
$msgbox		= '#E9E9E9';
$inputborder= '#BBB';

$colors = isset( $_REQUEST['colors'] ) ? $_REQUEST['colors'] : null;

switch( $colors )
{
	case 'lime':
		$menubg		= '#DEEC73';
		$menuborder	= '#C9DD1F';
		$evencell	= '#EEEEEE'; 
		
		break;

	case 'steel':
		$menubg 	= '#D6D6D6';
		$menuborder = '#ADBBD7';
		$msgbox		= '#EEEEEE';
		
		break;
}

// Herausfinden, wieviel Prozent der User welche Schrift aus font-family zu Gesicht bekommen:
// http://www.codestyle.org/servlets/FontStack 

?>



/*** common ***/


body {
	font-family:Verdana,Arial,sans-serif; font-size:9pt;
	background-color:white; 
	margin:0px; 
	border-width:0px;
	padding:0px; 
}

p, td { 
	/*font-family:Verdana,Arial,sans-serif; font-size:9pt;*/
}

h1, h2 {
	font-size:13pt; font-weight:bold;
}

a:link, a:visited, a:active {
	font-style:normal; text-decoration:none;
	color:<?php echo $link; ?>; 
}

a:hover {
	font-style:normal; text-decoration:none;
	color:<?php echo $hover; ?>;
}

img {
	border: 0px;
}

b.border {
	border:1px solid <?php echo $link; ?>;
	padding-left:0.2em; padding-right:0.2em;
}

i.hintw {
	font-style:normal; text-decoration:none; 
	border-bottom:2px dashed #FFBC2B; 
	cursor:help;
}

i.hinte {
	font-style:normal; text-decoration:none; 
	border-bottom:2px dashed #FF4040;
	cursor:help;
}

form {
	 margin:0px;
}



/*** mainmenu ***/

table.mm {
	margin:0px;
	border:0px; border-collapse:collapse; border-spacing:0px;
	width:100%;
}

td.mml {
	text-align: left;
	vertical-align: bottom;
	padding: 0.3em 0em 0em 2em;
}

td.mmr {
	text-align:right;
	padding:0.1em;
}

td.mml a {
	background-color:white; 
	margin-right:0.3em;
	border-left:1px solid <?php echo $menuborder; ?>; border-top:1px solid <?php echo $menuborder; ?>; border-right:1px solid <?php echo $menuborder; ?>;
	padding-left:0.5em; padding-top:0.3em; padding-right:0.5em;
	height:1.5em;
	white-space:nowrap;
}

td.mml a.mms {
	background-color:<?php echo $menubg; ?>; 
	margin-right:0.3em;
	border-left:1px solid <?php echo $menubg; ?>; border-top:1px solid <?php echo $menubg; ?>; border-right:1px solid <?php echo $menubg; ?>;
	padding-left:0.5em; padding-top:0.3em; padding-right:0.5em;
	height:1.5em; 
	white-space:nowrap;
}

td.mml a.mmbc {
	border-right:0px !important;
	margin-right: 0px !important;
	padding-right: 0px !important;
}


/*** submenu ***/

table.sm {
	margin:0px;
	border:0px; border-collapse:collapse; border-spacing:0px;
	width:100%;
}

td.sml, td.smr {
	background-color:<?php echo $menubg; ?>; 
	padding: 0.3em;
	vertical-align:middle;
}

td.sml {
	text-align:left; 
}

td.smr {
	text-align:right; 
}


/*** messages ***/

table.msg {
	margin:0px;
	border:0px; border-collapse:collapse; border-spacing:0px;
	padding:0px;
	width:100%;
}

table.msg td.msgi { /* message icon */
	background-color:<?php echo $msgbox; ?>; width:1%;
	padding:0.4em;
	vertical-align:top;
	border-top:1px solid white; border-bottom:1px solid white;
}

table.msg td.msgt {	/* message text */
	background-color:<?php echo $msgbox; ?>; width:70%;
	padding:0.4em;
	vertical-align:middle; 
	border-top:1px solid white; border-bottom:1px solid white;
}

table.msg td.msgc {	/* message close */
	background-color:<?php echo $msgbox; ?>;  width:29%;
	padding:0.4em;
	vertical-align:top; text-align:right;
	border-top:1px solid white; border-bottom:1px solid white;
}




/*** workspace ***/

div.ws {
	margin-left:0.3em; margin-top:0.5em; margin-right:0.3em; margin-bottom:0.5em;
	padding:0px;
}



/*** table ***/

table.tb {
	margin: 0px;
	border: 0px; 
	border-collapse:separate; border-spacing:0px; /*equals to old cellspacing; for cellpadding see td*/
	empty-cells: show;
	width: 100%;
}

table.tb > thead > tr > th {
	text-align:left; white-space:nowrap; background-color:<?php echo $menubg; ?>;
	border-right: 1px solid white;
	padding: 0.3em;
	font-weight: normal;
}

table.tb > tbody > tr > td {
	text-align:left; 
	vertical-align:top;	
	background-color:white;
	border-top:1px solid white; 
	border-bottom:1px solid white; 
	padding: 0.3em;
}

table.tb > tbody > tr:nth-child(even) > td {
	background-color:<?php echo $evencell; ?> !important;
}

table.tb > tbody > tr:hover > td {
	border-top:1px solid <?php echo $hover; ?> !important; 
	border-bottom:1px solid <?php echo $hover; ?> !important;
	cursor: default;
}

table.tb > tbody > tr.justedited > td:first-child {
	font-weight: bold;
	letter-spacing: 1px;
}

td.nw {
	white-space: nowrap;
}



/*** dialog ***/

table.dl {
	margin-left:0px; margin-top:0.5em; margin-right:0px; margin-bottom:0.5em;
	border:0px; border-collapse:collapse; border-spacing:0px;
	padding:0px;
}

td.dll {
	text-align:right; vertical-align:top; color:#808080;
	padding-left:0.2em; padding-top:0.3em; padding-right:1em; padding-bottom:0.2em;
	white-space:nowrap;
}

span.dllcontinue {
	color:#808080;
}


td.dlr {
	/* vertical-align: top; */
	padding-top:0.2em; padding-bottom:0.2em;
}



/*** buttons ***/

.bt { /* used for a div with several buttons */
	padding-top: 0.1em !important;
	padding-bottom: 0.1em !important;
}

.bt input {
	min-width: 9em;
	margin-right:0.8em;
	font-family:Verdana,Arial,sans-serif; font-size: 9pt;
}

input.button { /* used for a single button */
	font-family:Verdana,Arial,sans-serif; font-size: 9pt;
} 

/*** input ***/


input, select {
	font-family:Verdana,Arial,sans-serif; font-size: 9pt;
}

input.addvalues {
	font-size:1em; font-family:Verdana,Arial,sans-serif; color:<?php echo $link; ?>;
	border-left:0px solid white; border-top:0px solid white; border-right:0px solid white; border-bottom:1px solid <?php echo $link; ?>;
	background-color:white;
}

textarea.monospc {
	font-size:1em; font-family:"Courier New",monospace;
}

textarea.prop {
	font-size:1em; font-family:Verdana,Arial,sans-serif;
}

.emptyreadonlyval {
	color: #bbb;
}




/*** fixed stuff ***/


#fheader {
	position: fixed;
	left: 0px;
	top: 0px;
	width: 100%;
	height: 68px;
	background: white;
}
#fheader2 {
	position: absolute;
	width: 100%;
	left: 0; bottom: 1px;
}

#fcontent1 {
	height: 68px;
}

#fcontent2 {
	height: 43px;
}

#ffooter {
	position: fixed;
	left: 0px;
	bottom: 0px;
	width: 100%;
	height: 43px;
	background: white;
}



/*** autocomplete stuff ***/

span.achref {
	cursor: pointer;
}


/*** new edit stuff ***/

div.e_secondary {
}
div.e_object {
	/* border-bottom: 2px solid <?php echo $menubg; ?>; */
	margin-bottom: 0em; 
	padding: 0em 0 0em 0;
}
div.e_template {
	display: none; 
}
div.e_toolbar, div.e_section {
	background-color:<?php echo $menubg; ?>; 
	padding: 0.3em;
	margin: 0em 0 0 0;
	border-bottom: 2px solid white; 
}

.e_tb {
	padding:0;
	width:100%;
	line-height: 2.1em; /* without this, padding gets overlapped on linewraps*/
	border-spacing: 0;
}
.e_tb td.e_cll { 
	vertical-align: top;
	padding: 5px 0.3em;
	text-align: right; 
	width:140px;
}
.e_tb td.e_clr {
	vertical-align: top;
	padding: 5px 0.3em;
	
}


.e_tb td.e_lite {
	background-color: #F4F4F4;
}

.e_tb input[type=text], .e_tb input[type=password] {
	border-top: 0; border-left: 0; border-right: 0;
	border-bottom: 1px solid <?php echo $inputborder; ?>;
	background-color: transparent;
} 
/*
.e_tb textarea, .e_tb select {
	border: 1px solid <?php echo $inputborder; ?>;
}
*/


.e_bold		{ font-weight:bold; }
.e_bolder	{ font-weight:bold; font-size:11pt;  }
.e_bglite	{ background-color:<?php echo $evencell; ?>; }
.e_bgliter	{ background-color:#F6F6F6; }
.e_bgbottom	{ vertical-align: bottom !important; }

.e_blobimg	{ border: 1px solid <?php echo $inputborder; ?>; padding:2px; }

/* bitfields */
.e_bitfieldborder { /*may or may not be used together with e_bitfield*/
	border: 1px solid <?php echo $inputborder; ?>;
	padding: 1px;
}
.e_bitfield span {
	padding: 0 4px;
	border-right: 1px solid <?php echo $inputborder; ?>;
	cursor: pointer;
	background-color: #fff;
}
.e_bitfield span:last-child {
	border-right: 0;
}
.e_bitfield span.sel {
	background-color: #666;
	color: #fff;
}

/*hideable*/
.e_hidden {
	display: none;
}

/*attributes*/
.e_attr {
	border-bottom: 1px solid <?php echo $inputborder; ?>;
	padding: 2px 0;	
	background-color: transparent;
}
.e_attrinner {
	background-color: #F4F4F4;
	padding: 0 0 0 5px;
	/*border: 1px solid <?php echo $inputborder; ?>;*/
	border-right: 5px solid #fff;
}
.e_attrdel {
	cursor: pointer;
}
.e_attr input[type=text] {
	border:0;
	background-color: transparent;
	outline: none; /*get rid of the chrome focus rect*/
}
.e_attrdndplaceholder {
	border-left: 3px solid #333;
	border-right: 5px solid transparent;
}


/*attributes multiline*/
.e_attrmultiline  .e_attritem {
	display: block;
}


/*attributes: stichworttypen*/
.e_attractype1 { /*Abschluss: Gruen*/
	background-color: #C0FFC0;
}
.e_attractype2, .e_attractype4 { /*Foerderungsart, Qualitaetszertifikat: Dunkleres Grau*/
	/*background-color: #E0E0E0;*/
}
.e_attractype2048 { /*Verwaltungsstichwort: Gelb*/
	background-color: #FFFFC0;
}


/*attributes: references*/
.e_attrref {
	/*font-size: 8pt;*/
	color: #000;
}
.e_attrref a {
	color: #000;	
}
.e_attrref a:hover {
	color: <?php echo $hover; ?>
}

/* show DF modify dates upon hover */
.df_aenderungsdatum_descr, .e_clr.df_aenderungsdatum_bg {
	opacity: 0;
	font-size: '.7em';
	font-weight: normal;
	transition: opacity 1.7s ease-in-out;
	text-align: right;
}

tr .e_clr.df_aenderungsdatum_bg:before {
	transition: opacity 1.7s ease-in-out;
  content: 'Letztes Änderungsdatum: ';
	color: #aaa;
  font-size: '.7em';
	opacity: 0;
}

tr .e_clr.df_aenderungsdatum_bg input[readonly] {
	color: #aaa !important;
  font-size: '.7em' !important;
}

tr:hover .e_clr.df_aenderungsdatum_bg {
	opacity: .8;
	cursor: default;
}

tr .e_clr.df_aenderungsdatum_bg:before {
		opacity: .8;
}



/*defhide*/
a.e_defhide_more {
	color: #BBB;
}
a.e_defhide_more:hover {
	color:<?php echo $hover; ?>;
}


/*rights*/
#e_rights {
	padding: 1em 3em 1em 1em;
	display: none;
	border-top: 2px solid #fff;
	border-left: 2px solid #fff; 
	border-bottom: 2px dashed #fff;
	z-index: 2;
	position: fixed; right: 0; bottom: 43px;
	background-color: <?php echo $menubg ?>;
}
#e_rights .e_attrinner {
	border-right: 5px solid <?php echo $menubg ?>;
}
#e_rights table td {
	padding: 2px;
}
#e_rights table td.e_cll {
	white-space:nowrap;
	text-align: right; 
}

code {
 background-color: beige;
 padding: 2px;
 line-height: 20px;
}

/* textarea[name=f_beschreibung], textarea[name=f_firmenportraet] {
	height: 400px;
} */

input[name=f_titel], input[name=f_suchname] {
	width: 90%;
}

tr.archiv td {
	color: #999;
}

tr.gesperrt td {
	color: #999;
}

input[readonly] {
	color: #999;
	border: none !important;
}

input.anbieter_bezirk,
input.df_bezirk {
	display: none;
}

.additionalload {
	font-weight: normal;
}

.blink_me {
  animation: blinker 1s linear infinite;
		background-color: #FF0;
		box-shadow: #FF0 0 -1px 5px 1px, inset #930 0 -1px 6px, #930 0 2px 8px; 
		/* background-color: #A90;
		box-shadow: #fff 0 -1px 7px 1px, inset #660 0 -1px 9px, #DD0 0 2px 12px; */
		/* background-color: #00FFFF;
		box-shadow: #fff 0 -1px 7px 1px, inset #00FFFF 0 -1px 9px, #00FFFF 0 2px 12px; */
		border-radius: 50%;
		width: 8px;
		height: 8px;
		opacity: .8;
		display: block;
		position: absolute;
		margin-top: 5px;
		margin-left: 4px;
}

@keyframes blinker {
  50% {
    opacity: 0;
  }
}

label[for='resetlogin'], input[name='resetlogin'] {
	float: right;
	margin-top: 1px;
}

label[for='resetlogin'] {
	padding-right: 5px;
}

input[name='resetlogin'] {
	min-width: 0px;
	margin-top: 3px;
}

.vorschlag_label {
	color: #666 !important;
}

.vorschlag {
	color: #777 !important;
}

div[data-descr="Durchführung"] + .e_tb {
  border-top: 1px solid #eee;
  margin-bottom: 1em;
}

.df_lastinsertedDescr,
.df_lastdeletedDescr,
.df_lastmodifiedDescr {
	color: #777;
	font-size: 1em;
}

/* div[data-descr="Durchführung"] + .e_tb {
  line-height: 1em;
}

div[data-descr="Durchführung"] + .e_tb * {
	padding: 0px;
} */