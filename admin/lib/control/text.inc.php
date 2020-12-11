<?php
/******************************************************************************
Implementing a simple text-input
***************************************************************************//**

Some words to the placeholder:

We simply use the placeholder attribute, this is spreaded about 75% (1/2014)
and is available in all modern browsers, see http://caniuse.com/#search=placeholder .
If this feature is missing for a user, the user should switch the browser.

@author Björn Petersen, http://b44t.com

******************************************************************************/

class CONTROL_TEXT_CLASS extends CONTROL_BASE_CLASS
{
	public function get_default_dbval($use_tracked_defaults)
	{
		return $this->row_def->default_value=='0'? '' : $this->row_def->default_value;
	}

	public function set_dbval_from_user_input($user_input, &$errors, &$warnings, $addparam)
	{
		$this->dbval = $user_input;
		
		// validate must/recommented
		if( $this->dbval == '' )
		{
			if( $this->row_def->flags&TABLE_MUST ) { $errors[] = htmlconstant('_EDIT_ERREMPTYTEXTFIELD'); }
			if( $this->row_def->flags&TABLE_RECOMMENTED ) { $warnings[] = htmlconstant('_EDIT_WARNEMPTYTEXTFIELD'); }
		}
		
		// validate against mask "<humanReadable>###<errRule>###<warnRule>###<search1>###<repl1>###<search2>###...
		$rules = explode('###', $this->row_def->addparam);
		if( $this->dbval != '' && sizeof($rules)>=2 )
		{
			for( $i = 3; $i < sizeof($rules); $i += 2 )
			{
				$this->dbval = preg_replace($rules[$i], $rules[$i+1], $this->dbval);
			}
			
			if( $rules[1] )
			{
				if( !preg_match($rules[1], $this->dbval) ) {
					$errors[] = htmlconstant('_EDIT_ERRVALUENOTINMASK', trim($rules[0]));
				}
			}

			if( $rules[2] )
			{
				if( !preg_match($rules[2], $this->dbval) )
				{
					$warnings[] = htmlconstant('_EDIT_WARNVALUENOTINMASK', trim($rules[0]));
				}
			}
		}		
		
		// error / warning: unique?
		if( (($this->row_def->flags&TABLE_UNIQUE) || ($this->row_def->flags&TABLE_UNIQUE_RECOMMENTED)) && $this->dbval != '' ) {
			$dba = $addparam['dba'];
			$dba->query("SELECT id FROM " . $this->table_def->name . " WHERE " . $this->row_def->name . "=" . $dba->quote($this->dbval) . " AND id!=".intval($addparam['id']));
			if( $dba->next_record() ) {
				$href = '<a href="edit.php?table=' . $this->table_def->name . '&amp;id=' .$dba->f('id'). '" target="_blank">' . $dba->f('id') . '</a>';
				if( $this->row_def->flags & TABLE_UNIQUE ) {
					$errors[] = htmlconstant('_EDIT_ERRFIELDNOTUNIQUE', $href);
				}
				else {
					$warnings[] = htmlconstant('_EDIT_WARNFIELDNOTUNIQUE', $href);
				}
			}
		}
		

	}

	public function render_html($addparam)
	{
		// init values
		$width = -1;
		if( $this->row_def->prop['ctrl.size'] ) {
			$temp = explode('-', $this->row_def->prop['ctrl.size']);
			$min_width = intval($temp[0]);
			$max_width = intval($temp[sizeof($temp)-1]);
			$def_width = sizeof($temp)==3? intval($temp[1]) : $min_width;
			if( $this->dbval !== '' ) {
				$width = $min_width;
				if( strlen($this->dbval) >= $width ) {
					$width = strlen($this->dbval);
					if( $width > $max_width ) {
						$width = $max_width;
					}
				} 
			}
			else {
				$width = $def_width;
			}
		}
		else if( preg_match('/^\d+$/', $this->row_def->addparam) ) {
			$width = intval($this->row_def->addparam);
		}
		else {
			$rules = explode('###', $this->row_def->addparam);
			if( sizeof($rules)>=2 && substr($rules[0], -1)!=' ' ) {
				$width = strlen($rules[0]); // the first field contains the mask, a trailing space indicates no max. length
			}
		}

		$html = '';	
		

		// render
		$value = isohtmlspecialchars($this->dbval);
		
		if( is_array($this->row_def->prop['value.replace']) ) {
		    $value = str_replace($this->row_def->prop['value.replace'][0], $this->row_def->prop['value.replace'][1], $value);
		}
		
		if( is_array($this->row_def->prop['value.table_key']) ) { // defines table, key row and value row to look up: e.g. array( 'table' => '...', 'key' => 'id', 'value' => '...')
		    $dba = $addparam['dba'];
		    
		    $table = strval( $this->row_def->prop['value.table_key']['table'] ) ;
		    $values = explode('###', $value); // if string is array => php array
		    $value_strheap = array();
		    foreach($values AS $val) {
		        $key = strval( $this->row_def->prop['value.table_key']['key'] );
		        $value_ref = strval( $this->row_def->prop['value.table_key']['value'] );
		        if(trim($value_ref) && trim($key) && trim($val)) {
		            $dba->query("SELECT ".$value_ref." FROM " . $table . " WHERE " . $key . "=" . addslashes($val));
		            if( $dba->next_record() ) {
		                $value_strheap[$val] = $dba->fs($value_ref); // if one of the values found in defined table add to output value
		            }
		            
		            // check if value from displayed table (cell) is present in second table, e.g. tag suggestion in actual tags
		            $dba->query("SELECT * FROM kurse_stichwort WHERE primary_id=".$addparam['id']." AND attr_id=" . addslashes($val)); 
		            if( $dba->next_record() ) {
		                $matched_str = "&#10004; "; // tick
		                $value_strheap[$val] = $matched_str.$value_strheap[$val]; // converts stored values via key to values from table
		            }
		        }
		    }
		    
		    if( is_array($value_strheap) && count($value_strheap) && $this->row_def->prop['layout.value.aslabel'] ) // don't output (converted?) values as input values but as lable + hide actual values => no change in CMS
		        $label = implode("; ", $value_strheap);
		        elseif( is_array($value_strheap) && count($value_strheap) )
		        $value = implode("; ", $value_strheap);
		}
		
		$html .= ($label ? '<label for="#' . strval( $this->name ) . '" class="'.$this->row_def->prop['layout.descr.class'].'">'.$label.'</label>' : '')
		.'<input id="#' . strval( $this->name ) . '" name="' . $this->name . '" type="'.( $this->row_def->prop['layout.input.hide'] ? 'hidden' : 'text' ).'" value="'.$value.'"' . $this->tooltip_attr() . $this->readonly_attr();
		
		if( ($placeholder=strval($this->row_def->prop['ctrl.placeholder']))!='' ) {
		    if( $placeholder == '1' ) $placeholder = trim($this->row_def->descr);
		    $html .= ' placeholder="'.$placeholder.'" ';
		}
		
		$css_classes = '';
			
			if( $this->row_def->flags & (TABLE_ACNEST|TABLE_ACNESTSTART|TABLE_ACNORMAL) ) {
				$html .= ' data-acdata="'. $this->table_def->name . '.' . $this->row_def->name .'" ';
				$css_classes = 'acclass';
				if( $this->row_def->flags & (TABLE_ACNEST|TABLE_ACNESTSTART) ) {
					$css_classes = 'acnest '.$css_classes;
				}
			}
			else {
				$html .= ' autocomplete="off"'; // disables browser-based autocomplete (if we use our internal autocomplete, this is done in the JavaScript part)
			}

			if( isset($this->row_def->prop['ctrl.class']) ) {
				$css_classes = $this->row_def->prop['ctrl.class'] . ' ' . $css_classes;
			}
			
			$html .= ' class="'.$css_classes.'"';
			
			
			
			if( $width != -1 ) {
				$html .= ' size="'.$width.'" ';
			}
			
		$html .= '/>';
		
		if( ($this->row_def->flags & TABLE_URL) && $this->dbval !== '' ) 
		{
			make_clickable_url($this->dbval, $ahref);
			$html .= $ahref.'&nbsp;&#8599;</a>';
		}
						
		return $html;
	}
	
	function get_tooltip_text()
	{
		$arr = array();
		$temp = parent::get_tooltip_text(); if($temp) $arr[] = $temp;
		$temp = $this->get_mask();		if($temp) $arr[] = $temp;
		return implode(' - ', $arr);
	}	
	
	private function get_mask()
	{
		$rules = explode('###', $this->row_def->addparam);
		if( sizeof($rules)>=2 )
		{
			return $rules[0];
		}
		return '';
	}
	

};

