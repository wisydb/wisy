<?php

class CONTROL_BITFIELD_CLASS extends CONTROL_BASE_CLASS
{
	public function get_default_dbval($use_tracked_defaults)
	{
		return intval($this->row_def->default_value);
	}

	public function set_dbval_from_user_input($user_input, &$errors, &$warnings, $addparam)
	{	
		$this->dbval = intval($user_input);
		
		$valid_values = $this->get_attr();
		for( $bit = 0; $bit < 31; $bit++ ) {
			if( $this->dbval & (1<<$bit) ) {
				if( !isset($valid_values[ (1<<$bit) ]) ) {
					$errors[] = htmlconstant('_EDIT_ERRUNKNOWNVALUE', (1<<$bit));
				}
			}
		}

	}	
	
	/* returns possible attributes as an hash key=>descr
	 */
	private function get_attr()
	{
		$ret = array();

		$temp = explode('###', $this->row_def->addparam);
		for( $t = 0; $t < sizeof($temp); $t += 2 ) {
			$ret[ $temp[$t] ] = $temp[$t+1];
		}

		return $ret;
	}
	
	static $label_id = 1;
	public function render_html($addparam)
	{
		$html = '';

		$attr = $this->get_attr();
		
		if( $this->is_readonly() )
		{
			$html .= '<input type="hidden" name="'.$this->name.'" value="'.$this->dbval.'" />';
			$out = 0;
			foreach( $attr as $value=>$descr ) {
				if( $this->dbval & $value ) {
					$html .= $out? ', ' : '';
					$html .= trim($descr);
					$out ++;
				}
			}
		}
		else
		{
			$use_checkboxes = $this->row_def->prop['ctrl.checkboxes']? true : false;
			
			$class = 'e_bitfield';// the e_bitfield class is only used to keep the items together
			if( !$use_checkboxes ) $class .= ' e_bitfieldborder';
			$html .= '<span class="'.$class.'"'.$this->tooltip_attr().'>'; 
				$html .= '<input type="hidden" name="'.$this->name.'" value="'.$this->dbval.'" />'; // the position is important, must be child of span above!
									//	^^^	  when changing the type, the JavaScript-part must be adapted
				$i = 0;
				foreach( $attr as $value=>$descr )
				{
					if( $use_checkboxes ) {
						$selected = ($this->dbval & $value)? ' checked="checked"' : '';
						$html .= $i? '<br />' : '';
						
						$label = 'bitfieldlabel'.CONTROL_BITFIELD_CLASS::$label_id; CONTROL_BITFIELD_CLASS::$label_id++; // these labels won't work on secondary tables! please do not use ctrl.checkboxes there!
						
						$html .= '<input class="e_bitfield_item" data-bit="'.$value.'" type="checkbox" '.$selected.' id="'.$label.'" /> <label for="'.$label.'">' . trim($descr) . '</label>';
					}
					else {
						$selected = ($this->dbval & $value)? ' sel' : '';
						$html .= '<span class="e_bitfield_item'.$selected.'" data-bit="'.$value.'">' . trim($descr) . '</span>';
					}
					$i++;
				}
			$html .= '</span>';
		}
		
		return $html;
	}	
};

