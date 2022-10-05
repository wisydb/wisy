<?php

/*=============================================================================
Tips'n'Tricks for the program
===============================================================================

file:	
	tipsntricks.php
	
author:	
	Bjoern Petersen

parameters:
	showtip		-	if set, a complete page with the given tip is created,

usage:
	$next_tip = tipsntricks_get_next();

=============================================================================*/



require('wiki2html.inc.php');




function help_define($currKey, $currValue)
{
	global $g_tntheadline1;
	global $g_tntheadline2;
	global $g_tipsntricks;
	
	if( $currKey == '_TIP_TIPSNTRICKS' ) {
		$g_tntheadline1 = $currValue;
	}
	else if( $currKey == '_TIP_DIDYOUKNOW' ) {
		$g_tntheadline2 = $currValue;
	}
	else if( preg_match('/_TIP_([0-9]*)/', $currKey, $matches) ) {
		$g_tipsntricks[intval($matches[1])] = $currValue;
	}
}



function tipsntricks_load()
{
	global $g_tntheadline1;
	global $g_tntheadline2;
	global $g_tipsntricks;
	
	$g_tntheadline1	= "Tips'n'Tricks";
	$g_tntheadline2	= 'Did you know...';
	$g_tipsntricks	= array();
	
	require_lang('lang/tipsntricks');
	require_lang('config/lang/tipsntricks');
	
	ksort($g_tipsntricks);
}



function tipsntricks_get_next()
{
	global $g_tntheadline2;
	global $g_tipsntricks;

	// load tips'n'tricks
	tipsntricks_load();
	
	// load settings
	$settings_ = explode(',', str_replace(' ', '', regGet('login.tipsntricks.position', '')));
	$settings  = array();
	for( $i = 0; $i < sizeof($settings_); $i += 2 ) {
		if( $settings_[$i]!='' ) {
			$settings[$settings_[$i]] = intval($settings_[$i+1]);
		}
	}

	if( !isset($_SESSION['g_session_language']) 
	 || !isset($settings[ $_SESSION['g_session_language'] ]) 
	 || strval($settings[ $_SESSION['g_session_language'] ]) == '' ) {
		$settings[ $_SESSION['g_session_language'] ] = -1;
	}
	
	// get next tip defined after $settings[<lang>]
	$tipNum = 0;
	$nextTip = '';
	reset($g_tipsntricks);
	foreach($g_tipsntricks as $key => $value) {
		if( $key > $settings[ $_SESSION['g_session_language'] ] ) {
			$nextTip = $value;
			$settings[ $_SESSION['g_session_language'] ] = $key;
			break; // next tip found
		}
		$tipNum++;
	}
	
	if( !$nextTip ) {
		reset($g_tipsntricks);
		$key = array_keys($g_tipsntricks);
		$key = $key[0]; // array_key_first() only > php7
		$nextTip = array_values($g_tipsntricks);
		$nextTip = $nextTip[0];
		$settings[ $_SESSION['g_session_language'] ] = $key;
		$tipNum = 0;
	}
	
	// save settings
	reset($settings);
	$settings_ = '';
	foreach($settings as $key => $value) {
		if( $settings_ ) $settings_ .= ', ';
		$settings_ .= "$key, $value";
	}
	regSet('login.tipsntricks.position', $settings_, '');
	
	// get 'more...' link
	$more = '';
	if( $tipNum!=sizeof($g_tipsntricks)-1 ) {
		$nextTip .= ' <a href="tipsntricks.php?showtip=' .($tipNum+1). "\" target=\"tntall\" onclick=\"r=popup(this,500,300);return r;\">" . htmlconstant('_MORE___') . '</a>';
	}
	
	// done
	$tipsntricks2html = new WIKI2HTML_CLASS;
	return str_replace("\n", " ", $tipsntricks2html->run("$g_tntheadline2\n\n$nextTip"));
}



if( isset($_REQUEST['showtip']) )
{
	require_once('functions.inc.php');
	
	tipsntricks_load();

	$site->title = $g_tntheadline1;
	$site->pageStart(array('popfit'=>1));

		reset($g_tipsntricks);
		$tipNum = 0;
		foreach($g_tipsntricks as $key => $value)
		{
		    $showTip = isset($_REQUEST['showtip']) ? $_REQUEST['showtip'] : null;
			if( $tipNum == $showTip 
			    || ( $showTip >= 100000 && $showTip -100000 == intval($key)) ) 
			{
				$site->skin->msgStart('i', 'no close');
					$tipsntricks2html = new WIKI2HTML_CLASS;
					echo $tipsntricks2html->run("$g_tntheadline2\n\n$value");
					if( $tipNum!=0 ) {
						echo '<a href="tipsntricks.php?showtip=' .($tipNum-1). '">' . htmlconstant('_PREVIOUS') . '</a>&nbsp;&nbsp;&nbsp;';
					}
					if( $tipNum!=sizeof($g_tipsntricks)-1 ) {
						echo '<a href="tipsntricks.php?showtip=' .($tipNum+1). '">' . htmlconstant('_NEXT') . '</a>';
					}
				$site->skin->msgEnd();
				
				break;
			}
			
			$tipNum++;
		}
		
	$site->pageEnd();
}