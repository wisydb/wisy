<?php


/*=============================================================================
Password generator
===============================================================================

file:	
	genpassword.inc.php
	
author:	
	Bjoern Petersen

parameters:
	none, only function definitions in this file

=============================================================================*/


// function generates a random password which is 'readable'
function genpassword()
{
    $seed = (double)microtime()*1000000;
    srand( (int) $seed ); 
	$length = rand(6, 9);
    $vowels = array("a", "e", "i", "o", "u"); 
    $cons = array("b", "c", "d", "g", "h", "j", "k", "l", "m", "n", "p", "r", "s", "t", "v", "w", "tr", 
    "cr", "br", "fr", "th", "dr", "ch", "ph", "wr", "st", "sp", "sw", "pr", "sl", "cl"); 
     
    $num_vowels = count($vowels); 
    $num_cons = count($cons); 
    $password = '';
    
    for($i = 0; $i < $length; $i++){ 
        $password .= $cons[rand(0, $num_cons - 1)] . $vowels[rand(0, $num_vowels - 1)]; 
    } 
     
    $password = substr($password, 0, $length); 
    
    switch( rand(0,2) ) {
    	case 0: $password = $password . rand(1, 99); break;
    	case 1: $password = rand(1, 99) . $password; break;
    	case 2: $password = rand(1, 9) . $password . rand(1, 9); break;
    }
    
    return $password;
} 

function issimplepassword($password, $loginname)
{
	if( strlen($password) < 4 ) {
		return 1; // too simple - at last 4 characters required
	}
	
	if( $password == $loginname 
	 || $password == strrev($loginname) ) {
		return 1; // too simple - equal to loginname / reversed loginname
	}
	
	$keyboard = " 12345678901234567890 qwertzuiopqwertzuiop qwertyuiopqwertyuiop asdfghjkl yxcvbnmyxcvbnm zxcvbnmzxcvbnm ";
	if( strpos($keyboard, $password) || strpos($keyboard, strrev($password)) ) {
		return 1; // too simple - a keyboard pattern
	}
	
	$stat = count_chars($password, 1);
	if( sizeof($stat) < 3 ) {
		return 1; // too simple - at least 3 different characters required
	}
	
	return 0;
}
