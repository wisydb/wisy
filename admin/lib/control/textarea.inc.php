<?php

class CONTROL_TEXTAREA_CLASS extends CONTROL_TEXT_CLASS // not: CONTROL_BASE_CLASS!
{
	public function render_html($addparam)
	{
		$rows = 5;
		if( $this->row_def->prop['ctrl.rows'] ) {
			$rows = intval($this->row_def->prop['ctrl.rows']);
		}
	
		return	"<textarea name=\"{$this->name}\" style=\"width: 90%; max-width:900px;\" rows=\"$rows\"".$this->tooltip_attr().$this->readonly_attr().">"
			.		isohtmlspecialchars($this->dbval)
			.	"</textarea>";
	}
};

