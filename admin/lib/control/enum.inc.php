<?php

class CONTROL_ENUM_CLASS extends CONTROL_BASE_CLASS
{
	public function get_default_dbval($use_tracked_defaults)
	{
		return intval($this->row_def->default_value);
	}

	public function set_dbval_from_user_input($user_input, &$errors, &$warnings, $addparam)
	{
		$this->dbval = intval($user_input);
		
		$valid_values = $this->get_attr();
		if( !isset($valid_values[ $this->dbval ]) )
		{
			$errors[] = htmlconstant('_EDIT_ERRUNKNOWNVALUE', isohtmlspecialchars($user_input));
		}
	}	
	
	private function get_attr()
	{	
		$ret = array();
		$temp = explode('###', $this->row_def->addparam);
		for( $t = 0; $t < sizeof($temp); $t += 2 ) {
			$ret[ $temp[$t] ] = $temp[$t+1];
		}
		return $ret;
	}
	
	public function render_html($addparam)
	{
		$html = '';
		
		$descr_hide = false;
		if( $this->row_def->prop['layout.descr.hide'] ) {
			$descr_hide = true; // if the descripion is hidden, we'll add a tooltip or sth. like that
		}

		$attr = $this->get_attr();
		$first_option = true;
		$sth_selected = false;
		
		$html .= '<select name="'.$this->name.'" size="1"'.$this->tooltip_attr().$this->readonly_attr().'>';
			
			foreach( $attr as $value=>$descr )
			{
				$html .= '<option value="' .$value. '"';
				if( $value == $this->dbval ) {
					$html .= ' selected="selected"';
					$sth_selected = true;
				}
				$html .= '>';
				if( $first_option && $descr_hide && $descr == '' ) {
					$html .= trim($this->row_def->descr) . '...';
				}
				else {
					$html .= $descr;
				}
				$html .= '</option>';
				$first_option = false;
			}
			
			if( !$sth_selected )
			{
				$html .= '<option value="' . $this->get_default_dbval(false). '" selected="selected">Ungültiger Wert: ' . $this->dbval . '</option>';
			}
			
		$html .= '</select>';
		
		return $html;
	}	
	

};

