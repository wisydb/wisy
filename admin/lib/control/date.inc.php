<?php

/******************************************************************************
Implementing a date/datetime control
*******************************************************************************
				
@author Bjoern Petersen

******************************************************************************/



class CONTROL_DATE_CLASS extends CONTROL_BASE_CLASS
{
	public function __construct($name, $row_def, $table_def)
	{
		parent::__construct($name, $row_def, $table_def);
		if( ($this->row_def->flags&TABLE_ROW)==TABLE_DATE )
		{
			$this->datetype = $this->row_def->flags&TABLE_DAYMONTHOPT? 'dateopt' : 'date';
		}
		else
		{
			$this->datetype = 'datetime';
		}		
	}

	public function get_default_dbval($use_tracked_defaults)
	{	
	    /* unreachable: $table_def and $r not defined!
	    if( isset($table_def->rows[$r]) && is_object( $table_def->rows[$r] ) && $table_def->rows[$r]->default_value == 'today' )
		{
			return ftime("%Y-%m-%d %H:%M:%S");
		}
		else
		{ */
			return '0000-00-00 00:00:00';	
		// ! }
	}

	// Cave: $user_input ist not always the same as $_REQUEST[$this->name] - esp. for secondary tables, the caller regards the index!
	public function set_dbval_from_user_input($user_input, &$errors, &$warnings, $addparam)
	{
		$this->dbval = sql_date_from_human($user_input, $this->datetype);		
		
		if( ($this->row_def->flags&TABLE_MUST) || $this->dbval != '0000-00-00 00:00:00' ) 
		{
			$temperr = check_sql_date($this->dbval);
			if( $temperr )
			{
				$errors[] = $temperr;
			}
		}
	}	
	
	// create the user input controls - the data from here is normally forwarded to set_dbval_from_user_input() after submit
	public function render_html($addparam)
	{
		$html_val = sql_date_to_human($this->dbval, $this->datetype.' editable');
	
		$size = $this->datetype=='datetime'? 20 /*TT.MM.JJJJ, HH:MM:SS*/ : 10 /*TT.MM.JJJJ*/;
			
		$html = "<input size=\"$size\" name=\"{$this->name}\" type=\"text\" value=\"".isohtmlspecialchars($html_val)."\" ".$this->tooltip_attr().$this->readonly_attr()."";

		$html .= '/>';
		
		return $html;
	}
	
	function get_tooltip_text()
	{
		$tttext = parent::get_tooltip_text();
		if( $tttext )
			$tttext .= ' - ';
			$tttext .= htmlconstant( isset( $type ) && $type=='dateopt' ? '_DATEFORMATOPTHINT' : '_DATEFORMATHINT' );
			if( isset( $this->datetype ) && $this->datetype == 'datetime' ) {
			$tttext .= ', ' . htmlconstant('_TIMEFORMATHINT');
		}
		return $tttext;
	}
};