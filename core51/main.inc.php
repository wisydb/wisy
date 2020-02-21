<?php if( !defined('IN_WISY') ) die('!IN_WISY');

/******************************************************************************
 WISY 5.0
 ******************************************************************************
 Kodierungung dieses Quelltextes ist ISO 8859-1, _nicht_ UTF-8!
 
 Alle Anfragen laufen über /core20/main.inc.php in der folgenden Form:
 
 search?q=<query>				Suche Starten und erste Ergebnisseite anzeigen
 search?q=<query>&offset=<o>	Ergebnisses ab Offset anzeigen, <o>=0, 20 ...
 k<id>							Kurs <id> anzeigen
 a<id>							Anbieter <id> anzeigen
 g<id>							Ratgeberseite <id> anzeigen
 sync?<type>&pw=				Index synchronisieren
 
 Weitere, nicht unmittelbar sichtbare Anfragen:
 autosuggest?q=<query>
 core.css						gibt core20/core.css aus
 portal.css						gibt den Inhalt des Datenbankfeldes Portal/CSS aus
 
 Weitere Details zu den URLs unter https://b2b.kursportal.info/index.php?title=Neue_URLs
 ******************************************************************************/


 
/******************************************************************************
 some global config options
 ******************************************************************************/


// the following defines the lifetime of items in the search cache.
// the search index is updated every hour, so a lifetime of 30 minutes should 
// be just fine here.
// optionally, you can use a lifetime of 0 - this means an infinite lifetime
// and the cache is cleared just after the index sync. however this may result
// in a heavy server load after a sync.
define('SEARCH_CACHE_ITEM_LIFETIME_SECONDS', 29*60 /*29 minutes*/);	

 

/******************************************************************************
 Class factory / Modulsystem
 ******************************************************************************
 Alle Objekte aller Klassen müssen mit folgendem Aufruf erzeugt werden:
 
 $object =& createWisyObject('CLASS_NAME', $framework, array('param1'=>'value1', ...);
  
 Man beachte das =& bei der Zuweisung; PHP erfordert diese Schreibweise bei
 der By-Reference-Rückgabe zwingend (bei By-Reference-Parametern ist das "&"
 nicht notwendig).  Wie auch immer, diese Vorgehensweise erlaubt Modulen
 Funktionen und Klassen beliebig zu Überschreiben, indem die abgeleitete Klasse 
 in der Moduldatei einfach wie folgt deklariert wird:
 
 registerWisyClass('CLASS_NAME');
 
 Details unter https://b2b.kursportal.info/index.php?title=Portalmodule
 ******************************************************************************/

global $wisyClasses;
$wisyClasses = array();

function registerWisyClass($className)
{
	// if you derive from WISY classes, you can make the new class default using the function
	global $wisyClasses;
	
	$rootName = $className;
	while( ($test=get_parent_class($rootName)) !== false )
	{
		$rootName = strtoupper($test); // strtoupper() ist notwendig, da PHP 4 Klassennamen intern immer in Kleinschreibung verwaltet
	}
		
	if( $rootName == $className || substr($rootName, 0, 5)!='WISY_' )
	{
		die("Fehler in registerWisyClass(): Die Klasse &quot;$className&quot; ist nicht von einer WISY-Basisklasse abgeleitet.");
	}
	
	$wisyClasses[ $rootName ] = $className;
}

function loadWisyClass($className)
{
	// 1: load the original base class;  for this purpose, convert WISY_ANYNAME_CLASS to wisy-anyname-class.inc.php
	global $wisyCore;
	require_once($wisyCore . '/' . str_replace('_', '-', strtolower($className)) . '.inc.php');
	
	// 2: as this function is also used in "class bar extends loadWisyClass('bar')", also return the class name (which may be already overwritten)
	global $wisyClasses;
	
	if( isset($wisyClasses[$className]) )
	{
		$className = $wisyClasses[$className];
	}
	
	return $className;
}

function &createWisyObject($className, &$anyobject, $anyparam = 0)
{
	// load modules, if not yet done
	if( !defined('WISY_MODULES_LOADED') )
	{
		define('WISY_MODULES_LOADED', true);
		global $wisyPortalEinstellungen;
		if( $wisyPortalEinstellungen['module'] != '' )
		{
			$module = explode(',', $wisyPortalEinstellungen['module']);
			for( $m = 0; $m < sizeof((array) $module); $m++ )
			{
				$file = trim($module[$m]);
				if( $file != '' )
				{
					if( !file_exists($file) )
					{
						// no error at the moment ... we're just moving and paths will change ...
						die("Fehler in createWisyObject(): Kann Modul &quot;$file&quot; nicht &ouml;ffnen. Bitte ueberpruefen Sie Ihre Portaleinstellungen oder kontaktieren Sie den zustaendigen Administrator.");
					}
					else
					{
						require_once($file);
					}
				}
			}
		}
	}

	// load the class, get the "real" class name (modules may derive any class)
	$className = loadWisyClass($className);
	
	// create and construct: always use the __construct stuff introduced in PHP 5
	if( version_compare(PHP_VERSION, '5', '>=') )
	{
		$obj = new $className($anyobject, $anyparam); // PHP 5 contructor/destructor
	}
	else
	{
		$obj = new $className; // PHP 4; __destruct is faked, eg. unset($obj) won't work, so be careful!
		if( method_exists($obj, '__construct') ) $obj->__construct($anyobject, $anyparam);
		if( method_exists($obj, '__destruct' ) ) register_shutdown_function(array($obj, '__destruct'));
	}

	return $obj;
}





/******************************************************************************
 main()
 ******************************************************************************/

function main()
{
	// for backward compatibility (PHP < 5.4.0) automatic stripslashes() if get_magic_quotes_gpc is enabled
	if( function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc() ) { 
		require_once('admin/lib/g/stripslashes.inc.php');
		G_STRIPSLASHES_CLASS::stripAll(); 
	}
	
	// this function guarantees, nothing is distributed to the global namespace as an accident
	$null = 0;
	$framework =& createWisyObject('WISY_FRAMEWORK_CLASS', $null);
	$framework->main();
}

main();
