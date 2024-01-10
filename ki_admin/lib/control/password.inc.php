<?php

/******************************************************************************
Implementing a password control
***************************************************************************//**

@author Bjoern Petersen, http://b44t.com

******************************************************************************/



class CONTROL_PASSWORD_CLASS extends CONTROL_BASE_CLASS
{
	private $dbval_plain = '';

	public function get_default_dbval($use_tracked_defaults)
	{
		return '';	// "no password", however, this is not savable
	}

	// Cave: $user_input ist not always the same as $_REQUEST[$this->name] - esp. for secondary tables, the caller regards the index!
	public function set_dbval_from_user_input($user_input, &$errors, &$warnings, $addparam)
	{
	    global $salt;
	    
		$this->dbval_from_post = true;

		// by default, keep the password
		$this->dbval = $user_input;

		// change the password?
		$pw_change = $this->x_user_input('x_change_');
		if( $pw_change != '' ) {
			if( crypt($pw_change, $this->dbval) != $this->dbval ) {  // as multiple crypt() for the same password result in different strings; before using crypt(), check if the password was really changed
			    $this->dbval = crypt($pw_change, $salt);
			}
			
			$this->dbval_plain = $pw_change;
		}
		
		// do not accept empty passwords! this is always a security risk!
		if( !isset( $this->dbval ) || $this->dbval == '' )
		{
			$errors[] = htmlconstant('_EDIT_ERREMPTYTEXTFIELD');
		}
	}	
	
	// create the user input controls - the data from here is normally forwarded to set_dbval_from_user_input() after submit
	public function render_html($addparam)
	{
	    if( !isset( $this->dbval_from_post ) || !$this->dbval_from_post )
		{
			require_once("genpassword.inc.php");
			$this->dbval_plain = $this->dbval==''? (genpassword().genpassword()) : '';
		}
	
		$html = '';
		
		// the hidden field with the encrypted password - always needed to differ between database changes
		$html .= '<input type="hidden" name="' . $this->name . '" value="' . isohtmlspecialchars($this->dbval) . '" />';
	
		// the "password edit" control - if the user types sth in here, this will overwrite the control above
		$html .= '<input name="x_change_' . $this->name. '" type="text" autocomplete="off" value="'.$this->dbval_plain.'" placeholder="nicht &auml;ndern" title="Um das Passwort zu &auml;ndern, geben Sie ein neues Passwort ein" size="24" autocomplete="off" />';
	
		return $html;
	}
	
};