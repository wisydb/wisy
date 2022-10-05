<?php

/*=============================================================================
Prepare to Logout
===============================================================================

file:	
	logout.php
	
author:	
	Bjoern Petersen

parameters:
	page	-	the URL of the page to go on next login
	logout	-	after logout information

=============================================================================*/



require('functions.inc.php');


// save URL of last lage
$page = isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '';
regSet('login.lastpage', $page, '');
regSave();

$gSessionUserID = isset($_SESSION['g_session_userid']) ? $_SESSION['g_session_userid'] : null;

// set 'errors' for the user to null
if( regGet('settings.editable', 1) ) {
	$db = new DB_Admin;
	$db->query("UPDATE user SET last_login_id='', num_login_errors=0 WHERE id=" . $gSessionUserID );
}

// destroy session
$logwriter = new LOG_WRITER_CLASS;
$logwriter->log('user', $gSessionUserID, $gSessionUserID, 'logout');
$_SESSION = array(); // safe destroy $g_session_userid and the rest
session_destroy();

// show login screen
redirect( isset( $_REQUEST['logout'] ) && $_REQUEST['logout'] ? "index.php?logout=".$_REQUEST['logout'] : "index.php?logout=1");