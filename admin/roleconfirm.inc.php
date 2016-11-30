<?php



/*=============================================================================
confirming (new) role texts, can be used eg. for "AGB"
===============================================================================

file:	
	roleconfirm.inc.php
	
author:	
	Bjoern Petersen

parameters:
	none

usage:
	check_roleconfirm();

=============================================================================*/



function roleconfirm_check($user_about_to_log_in)
{
	global $site;
	
	$db = new DB_Admin;
	$db->query("SELECT attr_role FROM user WHERE id=".$user_about_to_log_in);
	if( !$db->next_record() ) {
		return;
	}
	$role_id = intval($db->f('attr_role'));
	
	$db->query("SELECT text_to_confirm FROM user_roles WHERE id=".$role_id);	
	if( !$db->next_record() ) {
		return;
	}
	$text_to_confirm = $db->f('text_to_confirm');
	
	//
	// has the user already confirmed the text?
	//
	if( isset($_REQUEST['role_confirm_ok']) ) {
		return;
	}
	
	//
	// show page with text to confirm
	//

	$site->pageStart();
	$site->menuHelpScope = '.';
	$site->menuOut();

		form_tag('form_enter', 'index.php');
		form_hidden('enter_subsequent', 1);
		form_hidden('enter_skip_env_tests', 1);
		form_hidden('enter_loginname', $_REQUEST['enter_loginname']);
		form_hidden('enter_password', $_REQUEST['enter_password']);
	
		echo '<div style="padding: 1em;">';
			echo $text_to_confirm;
		echo '</div>';

		$site->skin->buttonsStart();
			form_button('role_confirm_ok', "OK, Einverstanden");
			form_button('cancel', htmlconstant('_CANCEL'));
		$site->skin->buttonsEnd();
		
		echo '</form>';
			
	$site->pageEnd();
	exit();
}


