<?php

require_once("sql_curr.inc.php");
require_once("config/config.inc.php");

// wrappers for PHP >= 5.4 with changed defaults for some functions
// function isohtmlspecialchars($a, $f=ENT_COMPAT) { return htmlspecialchars($a, $f, 'ISO-8859-1'); }
// function isohtmlentities    ($a, $f=ENT_COMPAT) { return htmlentities    ($a, $f, 'ISO-8859-1'); }

function explodeSettings__($in, &$out, $follow_includes)
{
	$in = strtr($in, "\r\t", "\n ");
	$in = explode("\n", $in);
	for ($i = 0; $i < sizeof($in); $i++) {
		$equalPos = strpos($in[$i], '=');
		if ($equalPos) {
			$regKey = trim(substr($in[$i], 0, $equalPos));
			if ($regKey != '') {
				$regValue = trim(substr($in[$i], $equalPos + 1));
				if ($regKey == 'include') {
					if (!$follow_includes) {
						echo 'ERROR: includes inside includes are not allowed!'; // a die() would be too harsh ...
					} else if (!@file_exists($regValue)) {
						echo "ERROR: the following include-file does not exists: $regValue"; // a die() would be too harsh ...
					} else {
						$infile = file_get_contents($regValue);
						explodeSettings__($infile, $out, false);
					}
				} else {
					$out[$regKey] = $regValue; // the key may be set with an empty value!
				}
			}
		}
	}
}
function explodeSettings($in)
{
	$out = array();
	explodeSettings__($in, $out, true);
	return $out;
}

function error404($msg = "")
{
	global $wisyCore;
	header("HTTP/1.1 404 Not Found");
	header('Content-Type: text/html; charset=ISO8859-15');

	echo '<html>
			<head>
				<title>Fehler 404 - Seite nicht gefunden</title>
			</head>
			<body>
				<h1>Fehler 404 - Seite nicht gefunden</h1>
                <h2 style="color: darkgreen;">' . $msg . '</h2>
				<p>Entschuldigung, aber die von Ihnen gew&uuml;nschte Seite (<i>' . isohtmlspecialchars($_SERVER['REQUEST_URI']) . '</i> in <i>/' . isohtmlspecialchars($wisyCore) . '</i> auf <i>' . $_SERVER['HTTP_HOST'] . '</i>) konnte leider nicht gefunden werden. Sie k&ouml;nnen jedoch ...
				<ul>
					<li><a href="http://' . $_SERVER['HTTP_HOST'] . '">Die Startseite von ' . $_SERVER['HTTP_HOST'] . ' aufrufen ...</a></li>
					<li><a href="javascript:history.back();">Zur&uuml;ck zur zuletzt besuchten Seite wechseln ...</a></li>
				</ul>
			</body>
		  </html>';
	exit();
}
//START
$wisyPortalEinstellungen = null;
$db = new DB_Admin;
$ist_domain = strtolower($_SERVER['HTTP_HOST']);
if (substr($ist_domain, 0, 7) == 'wisyisy') {
	$ist_domain = substr($ist_domain, 7 + 1);
}
// find all matching domains with status = "1" - in this case 404 on purpose (mainly for SEO)
$sql = "SELECT * FROM portale WHERE status=1 AND domains LIKE '" . addslashes(str_replace('www.', '', $ist_domain)) . "';";
$db->query($sql);
if ($db->next_record()) {
	$wisyPortalEinstellungen = explodeSettings($db->fs('einstellungen'));
} else {
	error404();
}
if (strval($wisyPortalEinstellungen['wisyki'] != '')) {
	$GLOBALS['WisyKi'] = true;
}
if (strval($wisyPortalEinstellungen['minrel'] != '')) {
	$GLOBALS['MinRel'] = $wisyPortalEinstellungen['minrel'];
	if (strval($wisyPortalEinstellungen['maxpop'] != '')) {
		$GLOBALS['MaxPop'] = $wisyPortalEinstellungen['maxpop'];
	}
}
