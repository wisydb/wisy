<?php

class CONTROL_INT_CLASS extends CONTROL_BASE_CLASS
{
	public function get_default_dbval($use_tracked_defaults)
	{
		return strval($this->row_def->default_value);
	}

	public function set_dbval_from_user_input($user_input, &$errors, &$warnings, $addparam)
	{
		$this->dbval = trim($user_input);
		
		$minmax = explode('###', $this->row_def->addparam);
		if( (!isset( $this->dbval ) || $this->dbval == '') && $this->row_def->flags&TABLE_EMPTYONNULL ) {
			$this->dbval = 0;
		}
		else if( (!isset( $this->dbval ) || $this->dbval == '') && $this->row_def->flags&TABLE_EMPTYONMINUSONE ) {
			$this->dbval = -1;
		}
		else if( !isset( $out['db_val'] ) || !preg_match('/^-?\d+$/', $out['db_val'] ) ) {
			$out['errors'][] = htmlconstant('_EDIT_ERRENTERANUMBER');
		}
		else if( isset( $out['db_val'] ) && $out['db_val'] < $minmax[0] || isset( $out['db_val'] ) && $out['db_val'] > $minmax[1] ) {
			$out['errors'][] = htmlconstant('_EDIT_ERRVALUENOTINRANGE', $minmax[0], $minmax[1]);
		}
		else {
			$this->dbval = intval($this->dbval);
		}
	}
	
	public function render_html($addparam)
	{
	    $html_val = strval($this->dbval);
	    if( ($this->row_def->flags&TABLE_EMPTYONNULL && $html_val=='0') || ($this->row_def->flags&TABLE_EMPTYONMINUSONE && $html_val=='-1') ) {
	        $html_val = '';
	    }
	    
	    // contains DF-ID if this a DF field
		if(array_key_exists('controlTableRowID', $addparam)) {
	    	$id = "input_" . $this->row_def->name . $addparam['controlTableRowID'];
		} else {
	    	$id = "input_" . $this->row_def->name;
		}
	    $props = $this->row_def->prop;
	    $intAsCheck = false;
	    
	    if( is_array($props) && isset($props['ctrl.intAsCheck']) )
	        $intAsCheck = $props['ctrl.intAsCheck'];
	        
	        $width = 2;
	        $minmax = explode('###', $this->row_def->addparam);
	        
	        $min = $minmax[0];
	        $max = $minmax[1] ?? null;
	        
	        if( isset($min[0]) && strlen($min[0]) > $width) { $width = strlen($min); }
	        if( sizeof($minmax)>1 && strlen($max) > $width) { $width = strlen($max); }
	        
	        if( $this->is_readonly() ) {
	            return  "<input name=\"{$this->name}\" type=\"hidden\" value=\"".isohtmlspecialchars($html_val)."\" />"
	                . 	($html_val==''? '<span class="emptyreadonlyval">'.htmlconstant('_NA').'</span>' : isohtmlspecialchars($html_val));
	        }
	        else {
	            $ret =  "<input id=\"".$id."\" min=".$min." max=".$max." name=\"{$this->name}\" "
	                   ."onchange=\" javascript: if (isNaN(this.value) || parseInt(this.value) < this.min || parseInt(this.value) > this.max) alert(this.title); \""
	                   ."title=\"Ihre Eingabe f&uuml;r '".$this->row_def->descr."' muss zwischen ".$min." und ".$max." liegen!\" "
	                   ."type=\"text\" value=\"".isohtmlspecialchars($html_val)."\" size=\"$width\" ".$this->tooltip_attr()." class=\"".($intAsCheck ? 'e_hidden' : '')."\" >";
	                    
	            if( $intAsCheck ) {
	               $ret .= "<input type=\"checkbox\" "
	                     . "onchange=\"if(this.checked) document.getElementById('".$id."').value = 1; else document.getElementById('".$id."').value = 0; \" "
	                     . ( intval($html_val) != 0 ? "checked" : "" ) . " >";
	            }
	                    
	            return $ret;
	        }
	}
}
