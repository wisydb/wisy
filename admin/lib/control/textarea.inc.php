<?php

class CONTROL_TEXTAREA_CLASS extends CONTROL_TEXT_CLASS // not: CONTROL_BASE_CLASS!
{
	public function render_html($addparam)
	{
		// get the number of rows to display initially
		// (the intial number can be modified in the settings and the the global config, for temporary sizing of the textarea, we rely on the browser)
		$rows = 5;
		if( $this->row_def->prop['ctrl.rows'] ) {
			$rows = intval($this->row_def->prop['ctrl.rows']);
		}
		
		$temp = str_replace(' ', '', regGet("edit.field.{$this->table_def->name}.{$this->row_def->name}.size", "40 x $rows"));
		list($dummy, $rows) = explode('x', $temp);
		$rows = intval($rows);
		if( $rows < 2 ) $rows = 2;
		if( $rows > 32 ) $rows = 32;
								
		return	"<textarea name=\"{$this->name}\" style=\"width: 90%; max-width:2000px;\" rows=\"$rows\"".$this->tooltip_attr().$this->readonly_attr().">"
			.		isohtmlspecialchars($this->dbval)
			.	"</textarea>";
	}
};

