<?php


/*=============================================================================
(Un-)remember a given Record in the default list 
===============================================================================

file:	
	bin_toggle.php
	
author:	
	Bjoern Petersen

parameters:
	table	- the table to toggle
	id		- the record to toggle
	img		- if set, an image is returned to indicate the new state

=============================================================================*/



// includes
require_once('functions.inc.php');



//
// (un-)remember record, image out
// ============================================================================
//

$table = $_REQUEST['table'];
$id = intval($_REQUEST['id']);
$img = intval($_REQUEST['img']);

if( $_SESSION['g_session_bin']->recordExists($table, $id) ) 
{
	if( $_SESSION['g_session_bin']->recordDelete($table, $id) ) {
		$new_state_img = "{$site->skin->imgFolder}/bin0.gif";
	}
	else {
		$new_state_img = "{$site->skin->imgFolder}/bin1.gif";
	}
}
else 
{
	if( $_SESSION['g_session_bin']->recordAdd($table, $id) ) {
		$new_state_img = "{$site->skin->imgFolder}/bin1.gif";
	}
	else {
		$new_state_img = "{$site->skin->imgFolder}/bin0.gif";
	}
}

if( $img ) 
{
	// return the image to reflect the state of the record
	header('Content-type: image/gif');
	header('Content-length: ' . filesize($new_state_img));
	readfile($new_state_img);
}
else 
{
	// should normally not happen
	echo 'Record rembembered, you can close this window now.';
}


