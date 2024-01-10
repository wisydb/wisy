<?php
/******************************************************************************
The Rights Controls
***************************************************************************//**

Implementing the "rights control" (edit the user_access bits), normally they're
rendered in the lower right corner of every record editor.
Do not mix up this with the "user access controls" in the user editor.

@author Björn Petersen, http://b44t.com

******************************************************************************/



class CONTROL_RIGHTS_CLASS extends CONTROL_BASE_CLASS
{
	public function get_default_dbval($use_tracked_defaults)
	{
		return strval($this->row_def->default_value);
	}

	public function set_dbval_from_user_input($user_input, &$errors, &$warnings, $addparam)
	{
		if( $this->is_readonly() ) 
		{
			// set form hidden formular field
			$this->dbval = intval($user_input);
		}
		else
		{
			// set from the checkboxes
			$this->dbval = 0;

			for( $i = 0; $i < 9; $i++ ) {
				if( $this->x_user_input("x_grant{$i}_") ) {
					$this->dbval |= 1<<$i;
				}
			}

			if( isset( $this->dbval ) && $this->dbval & 0300 ) { $this->dbval |= 0400; }
			if( isset( $this->dbval ) && $this->dbval & 0030 ) { $this->dbval |= 0040; }
			if( isset( $this->dbval ) && $this->dbval & 0003 ) { $this->dbval |= 0004; }			
		}
	}


	private function render_check($name, $value, $descr)
	{
		$html = '<input type="checkbox" name="' .$name. '" id="' .$name. '" value="1"'; // if checked, 'name' is set to 'value' on submit. otherwise, 'name' is unset.
			if( $value ) {
				$html .= ' checked="checked"';
			}
		$html .= ' />';
		$html .= '<label for="' . $name . '">' . $descr . '</label>&nbsp; ';
		return $html;
	}
	
	private function render_grants($grantBit)
	{
		$html = '';
	
		if( $this->is_readonly() ) 
		{
			$anythingWritten = 0;
			
			if( isset( $this->dbval ) && $this->dbval & (1<<$grantBit) ) {
				$html .= htmlconstant('_READ');
				$anythingWritten = 1;
			}
			$grantBit--;

			if( isset( $this->dbval ) && $this->dbval & (1<<$grantBit) ) {
				if( $anythingWritten ) $html .= ', ';
				$html .= htmlconstant('_EDIT') . '/' . htmlconstant('_DELETE');
				$anythingWritten = 1;
			}
			$grantBit--;

			if( isset( $this->dbval ) && $this->dbval & (1<<$grantBit) ) {
				if( $anythingWritten ) $html .= ', ';
				$html .= htmlconstant('_EDIT_GRANTREF');
			}
		}
		else 
		{
			$html .= $this->render_check("x_grant{$grantBit}_{$this->name}", $this->dbval & (1<<$grantBit), htmlconstant('_READ'));
			$grantBit--;
		
			$html .= $this->render_check("x_grant{$grantBit}_{$this->name}", $this->dbval & (1<<$grantBit), htmlconstant('_EDIT').'/'.htmlconstant('_DELETE'));
			$grantBit--;
		
			$html .= $this->render_check("x_grant{$grantBit}_{$this->name}", $this->dbval & (1<<$grantBit), htmlconstant('_EDIT_GRANTREF'));
		}
		
		return $html;
	}
	
	public function render_html($addparam)
	{
		$html = '';
	
		if( $this->is_readonly() ) {
			$html .= "<input name=\"{$this->name}\" type=\"hidden\" value=\"".isohtmlspecialchars($this->dbval)."\" />"; 
		}
		
		$html .= $this->render_grants(8);
		$html .= $addparam['break'];
		$html .= $this->render_grants(5);
		$html .= $addparam['break'];
		$html .= $this->render_grants(2);
		
		return $html;
	}
}
