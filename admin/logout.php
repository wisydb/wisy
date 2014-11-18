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
regSet('login.lastpage', $_REQUEST['page'], '');
regSave();


// set 'errors' for the user to null
if( regGet('settings.editable', 1) ) {
	$db = new DB_Admin;
	$db->query("UPDATE user SET num_login_errors=0 WHERE id=" . $_SESSION['g_session_userid']);
}

// destroy session
$logwriter = new LOG_WRITER_CLASS;
$logwriter->log('user', $_SESSION['g_session_userid'], $_SESSION['g_session_userid'], 'logout');
$_SESSION = array(); // safe destroy $g_session_userid and the rest
session_destroy();

// show login screen
redirect($_REQUEST['logout']? "index.php?logout=".$_REQUEST['logout'] : "index.php?logout=1");


