<?php



/*=============================================================================
Language Stuff
===============================================================================

file:	
	lang.inc.php
	
author:	
	Bjoern Petersen

parameters:
	none, only function definitions in this file

=============================================================================*/




// returns an array with language ID / language name pairs found in the
// given folder
function get_avail_lang_from_folder($onlyTheseLang = '')
{
	// prepare "onlyTheseLang"
	$onlyTheseLang = str_replace(' ', '', strtolower($onlyTheseLang));
	$onlyTheseLang = str_replace(';', ',', $onlyTheseLang);
	if( $onlyTheseLang ) {
		$onlyTheseLang = explode(',', $onlyTheseLang);
	}
	else {
		$onlyTheseLang = array();
	}

	// get folder for available language check
	if( file_exists('lang/basic') ) {
		$folder = 'lang/basic';
	}
	else {
		$folder = 'lang';
	}

	// get available languages
	$availLang = array();
	
	$old_level = error_reporting(0);
		$dirhandle = opendir($folder);
		if( $dirhandle ) {
			while( $folderentry = readdir($dirhandle) )
			{
		 		$p = strrpos($folderentry, '.');
		 		if( $p && substr($folderentry, $p+1)=='php' && !is_dir("$folder/$folderentry") ) {
					$filehandle = fopen("$folder/$folderentry", 'r');
					if( $filehandle ) {
			 			$abbr = strtolower(substr($folderentry, 0, $p));
			 			if( sizeof($onlyTheseLang)==0 || in_array($abbr, $onlyTheseLang) )
			 			{
							$name = $abbr;
							while( !feof($filehandle) )
							{
								$line = trim(fgets($filehandle, 4096));
								$line = str_replace("'", "\"", stripslashes($line));
								if( substr($line, 0, 25) == "define(\"_CONST_LANGNAME\"," ) {
									$line = substr($line, 25);
									$p = strpos($line, "\"");  if( $p ) { $line = substr($line, $p+1); }
									$p = strrpos($line, "\""); if( $p ) { $line = substr($line, 0, $p); }
									if( $line ) { $name = $line; }
									break;
								}
							}
							
				 			$availLang[$abbr] = $name;
				 		}

						fclose($filehandle);
					}
		 		}
			}
			
			closedir($dirhandle);
		}
	error_reporting($old_level);
	
	if( !sizeof($availLang) ) {
		$availLang['en'] = 'English';
	}

	ksort($availLang);
	
	// done
	return $availLang;
}



function check_wanted_lang($availLang, $wantedLang)
{
	$wantedLangArray = explode(',', strtolower(str_replace(';', ',', $wantedLang)));
	$wantedLang = '';
	
	for( $wl = 0; $wl < sizeof($wantedLangArray); $wl++ ) {
		$testLang = trim($wantedLangArray[$wl]);
		if( $availLang[$testLang] ) {
			$wantedLang = $testLang;
			break;
		}
	}
	
	if( !$wantedLang ) {
		for( $wl = 0; $wl < sizeof($wantedLangArray); $wl++ ) {
			$testLang = explode('-', $wantedLangArray[$wl]);
			$testLang = trim($testLang[0]);
			if( $availLang[$testLang] ) {
				$wantedLang = $testLang;
				break;
			}
		}
		
		if( !$wantedLang ) {
			if( $availLang['en'] ) {
				$wantedLang = 'en';
			}
			else {
				reset($availLang);
				list($wantedLang, $dummy) = each($availLang);
			}
		}
	}

	return $wantedLang;
}

function require_lang_file($folder)
{
	if( file_exists("$folder/" . $_SESSION['g_session_language'] . ".php") ) {
		return "$folder/" . $_SESSION['g_session_language'] . ".php";
	}
	else if( file_exists("$folder/en.php") ) {
		return "$folder/en.php";
	}
	else if( file_exists("$folder/" . $_SESSION['g_session_language'] . ".js") ) {
		return "$folder/" . $_SESSION['g_session_language'] . ".js";
	}
	else if( file_exists("$folder/en.js") ) {
		return "$folder/en.js";
	}
	else  {
		return "";
	}
}



function require_lang($folder)
{
	$file = require_lang_file($folder);
	if( $file ) {
		require_once($file);
	}
}



// converts $str to the output character set and returns the new string.
function htmlwrite($str, $strCharset = '')
{
	return isohtmlentities($str);
}



// gets a constant, the constant should be defined as valid HTML-Code
// using plain ASCII or UTF-8. This means, at least '<', '>', '&' and '='
// should be written as '&lt;', '&gt;', '&amp;' and '&quot;'.
// $1, $2, $3 etc. in the constant are replaced by additional parameters.
function htmlconstant($constant)
{
	$ret = '';
	
	if( defined($constant) ) {
		$ret = constant($constant);
	}
	
	if( !$ret ) {
		$ret = $constant;
	}
	
	$args = func_get_args();
	for( $i = 1; $i < sizeof($args); $i++ ) {
		$ret = str_replace('$'.$i, $args[$i], $ret);
	}

	return $ret;
}




