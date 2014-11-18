<?php

	// make sure, the file is cached
	header("Cache-Control: public");
	header('Expires: ' . gmdate("D, d M Y H:i:s", intval(time()+43200)) . ' GMT'); // in 12 hours
	header('Content-type: text/css');

	// calcualte the fontsize
	$fontsize = $_REQUEST['pt'];
	
	if( $fontsize < 4 || $fontsize > 40 ) {
		$fontsize = 10;
	}
	
	$fontsizep			= strval(round($fontsize * 1))		.	'pt';
	$fontsizeh1			= strval(round($fontsize * 2))		.	'pt';
	$fontsizeh2			= strval(round($fontsize * 1.7))	.	'pt';
	$fontsizeh3			= strval(round($fontsize * 1.3))	.	'pt';
	$fontsizeborder		= strval($fontsize/10)				.	'pt';

?>



body {
	font-family:Arial,sans-serif; font-size:<?php echo $fontsizep; ?>;
	background-color:white;
	margin:0px; margin-top:0px; margin-bottom:0px; margin-left:0px; margin-right:0px; 
	border-width:0px;
	padding:0px;
}



p, td {
	font-family:Arial,sans-serif; font-size:<?php echo $fontsizep; ?>;
}



h1 {
	font-size:<?php echo $fontsizeh1; ?>; font-weight:bold;
	page-break-after:avoid;
}

h2 {
	font-size:<?php echo $fontsizeh2; ?>; font-weight:bold;
	page-break-after:avoid;
}

h3 {
	font-size:<?php echo $fontsizeh3; ?>; font-weight:bold;
	page-break-after:avoid;
}

h4, h5, h6 {
	font-size:<?php echo $fontsizep; ?>; font-weight:bold;
	page-break-after:avoid;
}



hr {
	page-break-before:avoid;
}



a:link, a:visited, a:active, a:hover {
	color:#000000; text-decoration:none; font-style:normal; font-weight:normal; 
}



div.prtitle {
	border-bottom:1pt solid black;
	font-weight:bold;
}



td.prhd {
	padding-left:1pt; padding-top:0pt; padding-right:3pt; padding-bottom:1pt;
	font-weight:bold;
	vertical-align:bottom;
}



td.prcl {
	border-top:1pt solid black;
	padding:1pt;
	vertical-align:top;
}

