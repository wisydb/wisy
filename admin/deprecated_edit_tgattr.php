<?php


/*=============================================================================
Toggle a multiple Attribut in an edit object
===============================================================================

file:	
	deprecated_edit_tgattr.php
	
author:	
	Bjoern Petersen

parameters:
	object		- the object currently in edit, will be modified
	tg_control	- the control to toggle
	tg_id		- the id to toggle
	img			- if set, the script returns a GIF-File with the new state.
				  otherwise, a little hint to close the window is printed.

=============================================================================*/




//
// includes
//
require_once('deprecated_edit_class.php');		// must be included BEFORE the session is
									// started in functions.inc.php has the edit
									// instances may be stored in the session
require_once('functions.inc.php');



//
// get all attributes
//
$new_state_img = "{$site->skin->imgFolder}/noaccess.gif";
if( isset($_REQUEST['object']) && is_object($_SESSION[$_REQUEST['object']]) ) {
	$toggle = $_SESSION[$_REQUEST['object']]->toggle_attr($_REQUEST['tg_control'], $_REQUEST['tg_id']);
	if( $toggle ) {
		$new_state_img = $toggle==1? "{$site->skin->imgFolder}/check0.gif" : "{$site->skin->imgFolder}/check2.gif";
	}
}



if( $_REQUEST['img'] ) {
	header('Content-type: image/gif');
	header('Content-length: ' . filesize($new_state_img));
	readfile($new_state_img);
}
else {
	echo '<html>';
		echo '<head>';
			echo '<title>Attribute changed</title>';
		echo '</head>';
		echo '<body>';
			echo '<p>&nbsp;Attribute changed, you can close this window now.</p>';
			echo '<p>&nbsp;<i>Attribute selection is much smarter if JavaScript is enabled.</i></p>';
		echo '</body>';
	echo '</html>';
}

?>