<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/ki_admin/sql_curr.inc.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/ki_admin/config/config.inc.php");

function selectPortalOrFwd301($db)
{



	$ist_domain = strtolower(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');

	// do forward by default - however, we skip forwarding on special domains
	$do_fwd = true;

	// some special domain handling
	if (substr($ist_domain, 0, 7) == 'sandbox') // remove sandbox prefix
	{
		if (preg_match("/^sandbox1/i", substr($ist_domain, 0, 8)) && !preg_match("/^sandbox[1][0-9]/i", substr($ist_domain, 0, 9))) {
			$ist_domain = substr($ist_domain, 8 + 1 /*dot or minus*/);
		} elseif (preg_match("/sandbox[2-9]/i", substr($ist_domain, 0, 8)) && !preg_match("/^sandbox[2-9][0-9]/i", substr($ist_domain, 0, 9))) {
			$ist_domain = substr($ist_domain, 8 + 1 /*dot or minus*/);
		} elseif (preg_match("/sandbox[1-9][0-9]/i", substr($ist_domain, 0, 9))) {
			$ist_domain = substr($ist_domain, 9 + 1 /*dot or minus*/);
		} else {
			$ist_domain = substr($ist_domain, 7 + 1 /*dot or minus*/);
		}

		$do_fwd = false;
	} else if (substr($ist_domain, 0, 6) == 'backup') // remove backup prefix -- backup domains may have the form backup-org-domain-de.host-domain.de
	{
		$ist_domain = substr($ist_domain, 6 + 1 /*dot or minus*/);
		$ist_domain = explode('.', $ist_domain);
		$ist_domain = strtr($ist_domain[0], array('-' => '.'));
		$do_fwd = false;
	} else if (substr($ist_domain, -6) == '.local') // ... special domain needed for development
	{
		$ist_domain = str_replace('.local', '.info', $ist_domain);
		$do_fwd = false;
	} else if (isset($_SERVER['REQUEST_URI']) && substr($_SERVER['REQUEST_URI'], 0, 5) == '/sync') // ... do not forward on sync as we may use special domains with more CPU-Time (as kursportal.domainfactory-kunde.de with 9 additional minutes CPU time)
	{
		$do_fwd = false;
	}

	// find all matching domains
	$sql = "SELECT * FROM portale WHERE status=1 AND domains LIKE '%" . addslashes(str_replace('www.', '', $ist_domain)) . "%';";
	$db->query($sql);
	while ($db->next_record()) {
		// as the LIKE above may give us by far too many results, we have to inspect the result carefully
		$domains = strtr($db->fs('domains'), ';,/*', '    '); // allow `:` for ports in domain names 
		$domains = explode(' ', $domains);
		$first_domain = '';
		for ($i = 0; $i < sizeof($domains); $i++) {
			$domain = strtolower($domains[$i]);
			if ($domain != '') {
				if ($first_domain == '') {
					$first_domain = $domain;
				}

				if ($domain == $first_domain && $domain == $ist_domain) {
					return; // success - $db contains a pointer to the current portal now
				} else if (str_replace('www.', '', $domain) == str_replace('www.', '', $ist_domain)) {
					if ($do_fwd)
						fwd301("http://" . $first_domain . (isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : ''));
					else
						return; // success - $db contains a pointer to the current portal now
				}
			}
		}
	}

	// find all matching domains with other status than "1" - in this case 404 on purpose (mainly for SEO)
	$sql = "SELECT * FROM portale WHERE status<>1 AND domains LIKE '%" . addslashes(str_replace('www.', '', $ist_domain)) . "%';";
	$db->query($sql);
	if ($db->next_record()) {
		$wisyPortalEinstellungen = explodeSettings($db->fs('einstellungen'));
		error404($wisyPortalEinstellungen['error404.msg']);
	}

	// nothing found at all - go to fallback (domain containing an "*") or show an error
	$sql = "SELECT * FROM portale WHERE status=1 AND domains LIKE '%*%';";
	$db->query($sql);
	if ($db->next_record()) {
		$domains = strtr($db->fs('domains'), ';,/*', '    '); // allow `:` for ports in domain names
		$domains = explode(' ', $domains);
		for ($i = 0; $i < sizeof($domains); $i++) {
			$domain = strtolower($domains[$i]);
			if ($domain != '') {
				if ($do_fwd)
					fwd301("http://" . $domain . (isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : ''));
				else
					return; // success - $db contains a pointer to the current portal now
			}
		}
	}

	error404();
}


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
/*******************************************************************************
 Connect to the database
 *******************************************************************************/
if (!class_exists('DB_Admin'))
	die("Verzeichnis ung&uuml;ltig.");

$db = new DB_Admin;
selectPortalOrFwd301($db);
$wisyPortalId				= intval($db->f('id'));
$wisyPortalModified			= $db->fs('date_modified');
$wisyPortalName				= $db->fs('name');
$wisyPortalKurzname			= $db->fs('kurzname');
$wisyPortalCSS				= trim($db->fs('css')) == '' ? 0 : 1;
$wisyPortalBodyStart		= stripslashes($db->f('bodystart'));
$wisyPortalEinstellungen	= explodeSettings($db->fs('einstellungen'));
$wisyPortalFilter			= explodeSettings($db->fs('filter'));
$wisyPortalEinstcache		= explodeSettings($db->fs('einstcache'));
$wisyPortalUserGrp          = $db->fs('user_grp');

// $wisyPortalEinstellungen = null;
// $db = new DB_Admin;
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
if (strval($wisyPortalEinstellungen['kibot'] != '')) {
	$GLOBALS['KiBot'] = $wisyPortalEinstellungen['kibot'];
}
if (strval($wisyPortalEinstellungen['minrel'] != '')) {
	$GLOBALS['MinRel'] = $wisyPortalEinstellungen['minrel'];
	if (strval($wisyPortalEinstellungen['maxpop'] != '')) {
		$GLOBALS['MaxPop'] = $wisyPortalEinstellungen['maxpop'];
	}
	/***************************************************************
Collect all needable keyword-types
	 *************************************************************** */
	if (@file_exists($_SERVER['DOCUMENT_ROOT'] . '/ki_admin/WisyKi/config/codes.inc.php')) {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/ki_admin/WisyKi/config/codes.inc.php');
	} else
		die('Verzeichnis unerwartet.');
	global $codes_stichwort_eigenschaften;
	global $wisyki_keywordtypes;
	$tp = explode("###", $codes_stichwort_eigenschaften);
	for ($c1 = 1; $c1 < sizeof((array) $tp); $c1 += 2) {
		switch ($tp[$c1]) {
			case 'ESCO-Kompetenz':
				$wisyki_keywordtypes['ESCO-Kompetenz']  = $tp[$c1 - 1];
				break;
			case 'ESCO-Synonym':
				$wisyki_keywordtypes['ESCO-Synonym']  = $tp[$c1 - 1];
				break;
			case 'ESCO-Beruf':
				$wisyki_keywordtypes['ESCO-Beruf']  = $tp[$c1 - 1];
				break;

			default:
				break;
		}
	}
}
