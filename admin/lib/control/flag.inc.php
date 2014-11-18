<?php

class CONTROL_FLAG_CLASS extends CONTROL_BASE_CLASS
{
	public function get_default_dbval($use_tracked_defaults)
	{
		return intval($this->row_def->default_value)? 1 : 0;
	}
	
	public function set_dbval_from_user_input($user_input, &$errors, &$warnings, $addparam)
	{
		$this->dbval = $user_input? 1 : 0;
	}	
	
	public function render_html($addparam)
	{
		$html = '';

		$selected = $this->dbval? 'checked="checked" ' : '';
		$html .= '<input type="checkbox" name="'.$this->name.'" value="1" '.$selected.'/>';
		
		return $html;
	}	
};

