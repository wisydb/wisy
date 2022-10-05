<?php

/******************************************************************************
Implementing a single control
***************************************************************************//**

Implementing a single control, derived classes are used by EDIT_RENDERER_CLASS
and EDIT_DATA_CLASS

@author Bjoern Petersen, http://b44t.com

******************************************************************************/



class CONTROL_BASE_CLASS
{
	public	$name;		// name as used in an <input /> element, differs from row->name
	public	$dbval;		// this is the value as stored in the db; for attributes, this is a comma-separated list
	public	$row_def;	// object Row_Def_Class or ROW_DUMMY_CLASS
	
	public function __construct($name, $row_def, $table_def)
	{	
		if( (get_class($row_def)!='ROW_DUMMY_CLASS' || $table_def!=0)
		 && (get_class($row_def)!='Row_Def_Class' || get_class($table_def)!='Table_Def_Class')  ) {
			die('bad parameter for CONTROL_BASE_CLASS constuctor.');
		}
		
		$this->name			= $name;
		$this->dbval		= '';
		$this->table_def	= $table_def; // may be null for ROW_DUMMY_CLASS
		$this->row_def		= $row_def;
	}
	
	public function is_default()
	{
	    if( isset( $this->dbval ) && $this->dbval == $this->get_default_dbval(false) ) {
			return true;
		}
		return false;
	}
	
	public function is_default_display($addparam)
	{
		return $this->is_default();
	}

	/**********************************************************************//**
	The function checks, the the flag TABLE_READONLY is set for the given 
	control. If so, the caller should render the control as being "read-only".
	However, the caller must not forget to add a hidden control with the value 
	even in this case (otherwise the value gets lost eg. when hitting the 
	"Apply"-Button)
	**************************************************************************/
	public function is_readonly()
	{
	    $rowDefACL = isset( $this->row_def->acl ) ? $this->row_def->acl : null;
	    if( !($rowDefACL&ACL_EDIT) ) {
			return true;
		}
		
		$rowDefFlags = isset( $this->row_def->flags ) ? $this->row_def->flags : null;
		if( $rowDefFlags&TABLE_READONLY ) {
			return true;
		}
		return false;
	}
	

	
	public function get_default_dbval($use_tracked_defaults)
	{
		return '';	
	}

	/**********************************************************************//**
	Called when the user hits "ok" or "apply" for a formular. The function
	should read the posted value (either $user_input or via x_user_input()
	
	Cave: $user_input ist not always the same as $_REQUEST[$this->name] - esp.
	for secondary tables, the caller regards the index! So, for the primary
	input field, just use $user_input.
	For extra user input fields, use x_user_input($prefix);
	**************************************************************************/
	public function set_dbval_from_user_input($user_input, &$errors, &$warnings, $addparam)
	{
		$errors[] = 'Please implement set_dbval_from_user_input() for derived classes! ('.get_class($this).'/'.$this->name.')';
	}	
	
	/**********************************************************************//**
	Function reads a user input value.  The name must follow the following 
	convention: `<input name="x_myfield_' . $this->name . '" />` and can be 
	read by `$this->x_user_input('x_myfield_')` then.
	
	Do not use `$_REQUEST['x_myfield_'.$this->name]` as this would not worked 
	for secondary areas.
	**************************************************************************/
	public function x_user_input($prefix)
	{
		if( substr($this->name, -2) == '[]' ) {
			if( !isset($this->x_secondary_index) ) die('x_user_input() may only be called from within set_dbval_from_user_input()!');
			
			if( isset( $_REQUEST[ $prefix . substr($this->name, 0, -2) ][ $this->x_secondary_index ] ) )
			 return $_REQUEST[ $prefix . substr($this->name, 0, -2) ][ $this->x_secondary_index ];
		    else
		     return false;
		}
		else {
		    if( isset( $_REQUEST[ $prefix . $this->name ] ) )
			 return $_REQUEST[ $prefix . $this->name ];
		    else
		     return false;
		}
	}
	
	public function is_in_secondary()
	{
		return substr($this->name, -2)=='[]'? true : false;
	}
	
	// create the user input controls - the data from here is normally forwarded to set_dbval_from_user_input() after submit
	public function render_html($addparam)
	{
		return 'Please implement render_html() for derived classes! (' . get_class($this) . '/' . $this->name . ')' 
			. '<input name="' . $this->name . '" type="hidden" value="' . isohtmlspecialchars($this->dbval) . '" />';
	}
	
	public function get_tooltip_text()
	{
		$tooltip = '';
		
		if( isset($this->row_def->prop['help.tooltip']) && $this->row_def->prop['help.tooltip'] ) {
			$tooltip = $this->row_def->prop['help.tooltip']; 
		}		
		else if( isset( $this->row_def->prop['layout.descr.hide'] ) && $this->row_def->prop['layout.descr.hide'] 
		      || isset( $this->row_def->prop['layout.descr'] ) && $this->row_def->prop['layout.descr'] ) {
			$tooltip = htmlconstant(trim($this->row_def->descr)); 
		}
		
		if( $this->is_readonly()
		 && !($this->row_def->flags&TABLE_READONLY) ) { // for TABLE_READONLY, we do not use a special tooltip, this can be done in db.inc.php
			if( $tooltip == '' ) $tooltip = htmlconstant(trim($this->row_def->descr)); 
			$tooltip .= '; nur lesen';
		}
		
		return $tooltip;
	}
	
	/**********************************************************************//**
	Function returns a title (tooltip) attribute, if any.
	**************************************************************************/
	public function tooltip_attr()
	{
		$tooltiptext = $this->get_tooltip_text();
		if( $tooltiptext != '' ) {
			return ' title="' . $tooltiptext . '" '; 
		}		
		return '';
	}
	
	/**********************************************************************//**
	If the control is readonly (is_readonly() returns true), the function
	returns a attributes that will make the control disabled
	**************************************************************************/
	public function readonly_attr()
	{
	    if( null !== $this->is_readonly() && $this->is_readonly() ) {
			return ' readonly="readonly" ';
			//return ' disabled="disabled" '; // readonly="readonly" does allow too much activity - eg. changing checkmarks is allowed, see http://www.htmlcodetutorial.com/forms/_INPUT_DISABLED.html
			// EDIT 14:56 15.04.2014: disabled="disabled" does not forward the values to the browser and back again, so `readonly` is the more little problem
		}
		return '';
	}
};