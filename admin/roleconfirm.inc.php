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

=============================================================================*/


function roleconfirm_after_login($user_about_to_log_in)
{
	if( !isset($GLOBALS['role_just_confirmed']) ) {
		return;
	}

	$db = new DB_Admin;
	$db->query("SELECT r.id, r.text_to_confirm, r.email_notify, u.name FROM user u LEFT JOIN user_roles r ON r.id=u.attr_role WHERE u.id=".$user_about_to_log_in);
	if( !$db->next_record() ) {
		return;
	}
	$role_id         = $db->fs('id');
	$text_to_confirm = $db->fs('text_to_confirm');
	$email_notify    = strval($db->fs('email_notify'));
	$user_name       = strval($db->fs('name'));
	$md5_confirmed = md5($text_to_confirm);

	// save state in registry
	regSet('role.confirmed', $md5_confirmed, '');
	regSave();
	
	// send a mail, if needed
	$logwriter = new LOG_WRITER_CLASS;
	if( $email_notify != '' )
	{
		$email_subject = "Rollentext akzeptiert von ".$user_name;
		$email_body    = "Der folgende Rollentext wurde akzeptiert von ".$user_name.":\n\n".$text_to_confirm;
		if( @mail($email_notify, $email_subject, $email_body) ) {
			$logwriter->addData('notify', $email_notify);
		}
		else {
			$logwriter->addData('notify_error', $email_notify);
			
		}
	}
	
	// log
	$logwriter->log('user_roles', $role_id,              $user_about_to_log_in, 'confirmed');
	$logwriter->log('user',       $user_about_to_log_in, $user_about_to_log_in, 'confirmed');
}


function roleconfirm_check($user_about_to_log_in)
{
	global $site;
	
	$db = new DB_Admin;
	$db->query("SELECT r.text_to_confirm FROM user u LEFT JOIN user_roles r ON r.id=u.attr_role WHERE u.id=".$user_about_to_log_in);
	if( !$db->next_record() ) {
		return;
	}
	$text_to_confirm = $db->fs('text_to_confirm');
	
	//
	// has the user already confirmed the text?
	//
	if( isset($_REQUEST['role_confirm_ok']) )
	{
		// we'll save the data and do more stuff in roleconfirm_after_login() which is called when the session is completely working
		$GLOBALS['role_just_confirmed'] = 1;
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
		$_SESSION['g_role_confirm_login_credential_pw'] = $_REQUEST['enter_password']; // For security reasons, do not write the password to an HTML-file.  Instead, read it from the $_SESSION['g_role_confirm_login_credential_pw'] on submit.
	
		echo '<div style="padding: 1em;">';
			echo nl2br($text_to_confirm);
		echo '</div>';

		$site->skin->buttonsStart();
			form_button('role_confirm_ok', "OK, Einverstanden");
			form_button('role_confirm_cancel', htmlconstant('_CANCEL'));
		$site->skin->buttonsEnd();
		
		echo '</form>';
			
	$site->pageEnd();
	exit();
}


