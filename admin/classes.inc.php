<?php

/*=============================================================================
Autoload for all classes, common environment.
Can be used from any directory that need some /admin/ functionality.
===============================================================================
To start a real /admin/ page, call functions.inc.php object.
===============================================================================

Author:	
	Bjoern Petersen

=============================================================================*/



// changing the CMS version will force reloading of .js and .css files  
define('CMS_VERSION', '5.89');


// PHP 5.0.0 is needed for: __construct(), public, private, protected, microtime($get_as_float), Exceptions, object-copying by reference by default
// PHP 5.1.2 is needed for: spl_autoload_register()
// not (yet) needed: 5.3.0 for str_getcsv() and for anonymous functions
if( version_compare(PHP_VERSION, '5.1.2', '<') ) die('PHP version too old.'); 


// PHP 7 changes the default characters set to UTF-8; we still prefer ISO-8859-1
ini_set('default_charset', 'ISO-8859-1');


// set an absolute path that should be prefixed to all includes.
define('CMS_PATH', dirname(__FILE__).'/');



// wrappers for PHP >= 5.4 with changed defaults for some functions
if( !function_exists('isohtmlspecialchars') ) {
	function isohtmlspecialchars($a, $f=ENT_COMPAT) { return htmlspecialchars($a, $f, 'ISO-8859-1'); }
	function isohtmlentities    ($a, $f=ENT_COMPAT) { return htmlentities    ($a, $f, 'ISO-8859-1'); }
}


// load classes as SCOPE_NAME_CLASS from /lib/scope/name.php or from /config/scope/name.php
spl_autoload_register('cms_autoload');
function cms_autoload($classname)
{ 
	$filename = strtr(strtolower($classname), array('_class'=>'', '_'=>'/')) . '.inc.php';
	$path = dirname(__FILE__) . '/lib/' . $filename;
	if( file_exists($path) )
		{ require_once($path); return; }
	
	$path = dirname(__FILE__) . '/config/' . $filename;
	if( file_exists($path) )
		{ require_once($path); return; }
} 


// for backward compatibility (PHP < 5.4.0) automatic stripslashes() if get_magic_quotes_gpc is enabled
if( function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc() ) { 
	G_STRIPSLASHES_CLASS::stripAll(); 
}


