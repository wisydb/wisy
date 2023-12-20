<?php
require_once("WisyKi/wisykistart.php");

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
define('CMS_VERSION', '8.1');


// PHP 5.0.0 is needed for: __construct(), public, private, protected, microtime($get_as_float), Exceptions, object-copying by reference by default
// PHP 5.1.2 is needed for: spl_autoload_register()
// not (yet) needed: 5.3.0 for str_getcsv() and for anonymous functions
if( version_compare(PHP_VERSION, '5.1.2', '<') ) die('PHP version too old.'); 


// PHP 7 changes the default characters set to UTF-8; we still prefer ISO-8859-1
if(substr(PHP_VERSION, 0, 1) > 6)
    @ini_set('default_charset', 'ISO-8859-1'); // ISO-8859-15 possible?


// set an absolute path that should be prefixed to all includes.
define('CMS_PATH', dirname(__FILE__).'/');


// wrappers for PHP >= 5.4 with changed defaults for some functions
if( !function_exists('isohtmlspecialchars') ) {
    
    function isohtmlspecialchars( $a, $f = ENT_COMPAT ) { 
        return htmlspecialchars( strval($a), $f, 'ISO-8859-1'); 
    }
    function isohtmlentities( $a, $f = ENT_COMPAT ) { 
        return htmlentities( strval($a), $f, 'ISO-8859-1'); 
    }
    
}

// load classes as SCOPE_NAME_CLASS from /lib/scope/name.php or from /config/scope/name.php
spl_autoload_register('cms_autoload');
function cms_autoload($classname)
{
    $filename = strtr(strtolower($classname), array('_class' => '', '_' => '/')) . '.inc.php';
    //load WisyKi Special-Classes
    if (isset($GLOBALS['WisyKi'])) {

         $path = dirname(__FILE__) . '/WisyKi/lib/' . $filename;
        if (file_exists($path)) {
            require_once($path);
            return;
        }

        $path = dirname(__FILE__) . '/WisyKi/config/' . $filename;
        if (file_exists($path)) {
            require_once($path);
            return;
        }
   } 
        $path = dirname(__FILE__) . '/lib/' . $filename;
        if (file_exists($path)) {
            require_once($path);
            return;
        }

        $path = dirname(__FILE__) . '/config/' . $filename;
        if (file_exists($path)) {
            require_once($path);
            return;
        }
    
}


// for backward compatibility (PHP < 5.4.0) automatic stripslashes() if get_magic_quotes_gpc is enabled
if( function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc() ) {  // @ PHP7.4
	G_STRIPSLASHES_CLASS::stripAll(); 
}


/**
 * Locale-formatted strftime using \IntlDateFormatter (PHP 8.1 compatible)
 * This provides a cross-platform alternative to strftime() for when it will be removed from PHP.
 * Note that output can be slightly different between libc sprintf and this function as it is using ICU.
 *
 * Usage:
 * use function \PHP81_BC\strftime;
 * echo strftime('%A %e %B %Y %X', new \DateTime('2021-09-28 00:00:00'), 'fr_FR');
 *
 * Original use:
 * \setlocale('fr_FR.UTF-8', LC_TIME);
 * echo \strftime('%A %e %B %Y %X', strtotime('2021-09-28 00:00:00'));
 *
 * @param  string $format Date format
 * @param  integer|string|DateTime $timestamp Timestamp
 * @return string
 * @author BohwaZ <https://bohwaz.net/>
 */
function ftime( string $format, $timestamp = null, ?string $locale = 'de_DE' ): string
{
    if (null === $timestamp) {
        $timestamp = new \DateTime;
    }
    elseif (is_numeric($timestamp)) {
        $timestamp = date_create('@' . $timestamp);
        
        if ($timestamp) {
            $timestamp->setTimezone(new \DateTimezone(date_default_timezone_get()));
        }
    }
    elseif (is_string($timestamp)) {
        $timestamp = date_create($timestamp);
    }
    
    if (!($timestamp instanceof \DateTimeInterface)) {
        throw new \InvalidArgumentException('$timestamp argument is neither a valid UNIX timestamp, a valid date-time string or a DateTime object.');
    }
    
    $locale = substr((string) $locale, 0, 5);
    
    $intl_formats = [
        '%a' => 'EEE',	// An abbreviated textual representation of the day	Sun through Sat
        '%A' => 'EEEE',	// A full textual representation of the day	Sunday through Saturday
        '%b' => 'MMM',	// Abbreviated month name, based on the locale	Jan through Dec
        '%B' => 'MMMM',	// Full month name, based on the locale	January through December
        '%h' => 'MMM',	// Abbreviated month name, based on the locale (an alias of %b)	Jan through Dec
    ];
    
    $intl_formatter = function (\DateTimeInterface $timestamp, string $format) use ($intl_formats, $locale) {
        $tz = $timestamp->getTimezone();
        $date_type = \IntlDateFormatter::FULL;
        $time_type = \IntlDateFormatter::FULL;
        $pattern = '';
        
        // %c = Preferred date and time stamp based on locale
        // Example: Tue Feb 5 00:45:10 2009 for February 5, 2009 at 12:45:10 AM
        if ($format == '%c') {
            $date_type = \IntlDateFormatter::LONG;
            $time_type = \IntlDateFormatter::SHORT;
        }
        // %x = Preferred date representation based on locale, without the time
        // Example: 02/05/09 for February 5, 2009
        elseif ($format == '%x') {
            $date_type = \IntlDateFormatter::SHORT;
            $time_type = \IntlDateFormatter::NONE;
        }
        // Localized time format
        elseif ($format == '%X') {
            $date_type = \IntlDateFormatter::NONE;
            $time_type = \IntlDateFormatter::MEDIUM;
        }
        else {
            $pattern = $intl_formats[$format];
        }
        
        return (new \IntlDateFormatter($locale, $date_type, $time_type, $tz, null, $pattern))->format($timestamp);
    };
    
    // Same order as https://www.php.net/manual/en/function.strftime.php
    $translation_table = [
        // Day
        '%a' => $intl_formatter,
        '%A' => $intl_formatter,
        '%d' => 'd',
        '%e' => function ($timestamp) {
        return sprintf('% 2u', $timestamp->format('j'));
        },
        '%j' => function ($timestamp) {
        // Day number in year, 001 to 366
        return sprintf('%03d', $timestamp->format('z')+1);
        },
        '%u' => 'N',
        '%w' => 'w',
        
        // Week
        '%U' => function ($timestamp) {
        // Number of weeks between date and first Sunday of year
        $day = new \DateTime(sprintf('%d-01 Sunday', $timestamp->format('Y')));
        return sprintf('%02u', 1 + ($timestamp->format('z') - $day->format('z')) / 7);
        },
        '%V' => 'W',
        '%W' => function ($timestamp) {
        // Number of weeks between date and first Monday of year
        $day = new \DateTime(sprintf('%d-01 Monday', $timestamp->format('Y')));
        return sprintf('%02u', 1 + ($timestamp->format('z') - $day->format('z')) / 7);
        },
        
        // Month
        '%b' => $intl_formatter,
        '%B' => $intl_formatter,
        '%h' => $intl_formatter,
        '%m' => 'm',
        
        // Year
        '%C' => function ($timestamp) {
        // Century (-1): 19 for 20th century
        return floor($timestamp->format('Y') / 100);
        },
        '%g' => function ($timestamp) {
        return substr($timestamp->format('o'), -2);
        },
        '%G' => 'o',
        '%y' => 'y',
        '%Y' => 'Y',
        
        // Time
        '%H' => 'H',
        '%k' => function ($timestamp) {
        return sprintf('% 2u', $timestamp->format('G'));
        },
        '%I' => 'h',
        '%l' => function ($timestamp) {
        return sprintf('% 2u', $timestamp->format('g'));
        },
        '%M' => 'i',
        '%p' => 'A', // AM PM (this is reversed on purpose!)
        '%P' => 'a', // am pm
        '%r' => 'h:i:s A', // %I:%M:%S %p
        '%R' => 'H:i', // %H:%M
        '%S' => 's',
        '%T' => 'H:i:s', // %H:%M:%S
        '%X' => $intl_formatter, // Preferred time representation based on locale, without the date
        
        // Timezone
        '%z' => 'O',
        '%Z' => 'T',
        
        // Time and Date Stamps
        '%c' => $intl_formatter,
        '%D' => 'm/d/Y',
        '%F' => 'Y-m-d',
        '%s' => 'U',
        '%x' => $intl_formatter,
        ];
    
    $out = preg_replace_callback('/(?<!%)(%[a-zA-Z])/', function ($match) use ($translation_table, $timestamp) {
        if ($match[1] == '%n') {
            return "\n";
        }
        elseif ($match[1] == '%t') {
            return "\t";
        }
        
        if (!isset($translation_table[$match[1]])) {
            throw new \InvalidArgumentException(sprintf('Format "%s" ist ein unbekanntes Zeit-Format', $match[1]));
        }
        
        $replace = $translation_table[$match[1]];
        
        if (is_string($replace)) {
            return $timestamp->format($replace);
        }
        else {
            return $replace($timestamp, $match[1]);
        }
    }, $format);
        
        $out = str_replace('%%', '%', $out);
        return $out;
}