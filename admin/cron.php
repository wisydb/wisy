<?php

/*=============================================================================
 Synchronisation
 ===============================================================================
 
 This file should be called regularly (eg. every hour) to perform CRON jobs.
 
 file:
 cron.php
 
 parameters:
 apikey		the apikey as defined at export.apikey
 
 =============================================================================*/

echo "Start Cron..." . "<br>\n";

// make sure Webserver doesn't time b/c of no output
$outputBufferingValue = intval(ini_get('output_buffering'));
if( $outputBufferingValue > 0 ) {
    // Generate a string of $outputBufferingValue spaces (or any other character)
    $padding = str_repeat('.', $outputBufferingValue);
    
    // Output the padding string
    echo $padding;
    ob_flush();
    flush();
}

define('G_SKIP_LOGIN', 1); // skip the normal login, instead perform a check of export.apikey in handle_request()
require('functions.inc.php');

$GLOBALS['g_cron'] = new G_CRON_CLASS;
$GLOBALS['g_cron']->handle_request();