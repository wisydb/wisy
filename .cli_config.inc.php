<?php
 
 $domain_set = ( isset($argv) && is_array($argv) && isset($argv[1]) && strlen($argv[1]) > 3 && strpos($argv[1], '.') !== FALSE );
 $apikey_set = ( isset($argv) && is_array($argv) && isset($argv[2]) && strlen($argv[2]) > 1 );
 
 if( $domain_set ) {
     $_SERVER['SERVER_NAME'] = $argv[1];
     $_SERVER['HTTP_HOST']   = $argv[1];
 }
 else {
     $_SERVER['SERVER_NAME'] = "example.com"; // example: replace
     $_SERVER['HTTP_HOST']   = "example.com"; // example: replace
 }
 
 if( $apikey_set )
     $apikey = $argv[2];
 else
     $apikey = "123456789"; // example: replace
         
 $_SERVER['REQUEST_URI'] = "/sync?apikey=".$apikey;
 
 $_GET['apikey'] = $apikey;
 $_GET['kurseSlow'] = "1";
         
?>