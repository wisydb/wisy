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

		$valid_values = $this->get_attr()['options'];
		if (!isset($valid_values[$this->dbval])) {
			$errors[] = htmlconstant('_EDIT_ERRUNKNOWNVALUE', isohtmlspecialchars($user_input));
		}
	}

	private function get_attr()
	{

		$ret = array();
		$opt = array();
		$temp = explode('###', $this->row_def->addparam);
		$start = 0;
		if ($temp[0] == '-1' || !isset($temp[2]))
			$start = 3;
		for ($t = $start; $t < sizeof($temp); $t += 2) {
			if (isset($temp[$t]))
				$ret[$temp[$t]] = isset($temp[$t + 1]) ? $temp[$t + 1] : null;
		}
		$opt['options'] = array();
		$opt['options'] = $ret;
		if ($start == 3)
			$opt['handler1'] = $temp[1];
		if (isset( $temp[2]))
		    $opt['handler2'] = $temp[2];
		else
		    $opt['handler2'] = "";
		return $opt;
	}

	public function render_html($addparam)
	{
		$html = '';

		$descr_hide = false;
		if (isset($this->row_def->prop['layout.descr.hide']) && $this->row_def->prop['layout.descr.hide']) {
			$descr_hide = true; // if the descripion is hidden, we'll add a tooltip or sth. like that
		}

		$opt = $this->get_attr();
		$attr = $opt['options'];

		$first_option = true;
		$sth_selected = false;
		$handle = isset($opt['handler1']) ? ' ' . $opt['handler1'] : '';
		if ($handle != '') {
			if (isset($GLOBALS['kategory']) && isset($GLOBALS['kategory']['id'])) {
				$handle .= "&id=" . $_REQUEST['id'];
			}

			$handle .= $opt['handler2'];
			if (isset($GLOBALS['kategory']) && isset($GLOBALS['kategory']['kat']))
				$this->dbval = $GLOBALS['kategory']['kat'];
		}
		$html .= '<select name="' . $this->name . '" size="1"' . $handle . $this->tooltip_attr() . $this->readonly_attr() . '>';


		foreach ($attr as $value => $descr) {
			$html .= '<option value="' . $value . '"';
			if ($value == $this->dbval) {
				$html .= ' selected="selected"';
				$sth_selected = true;
			}
			$html .= '>';
			if ($first_option && $descr_hide && $descr == '') {
				$html .= trim($this->row_def->descr) . '...';
			} else {
				$html .= $descr;
			}
			$html .= '</option>';
			$first_option = false;
		}

		if (!$sth_selected) {
			$html .= '<option value="' . $this->get_default_dbval(false) . '" selected="selected">Ung&uuml;ltiger Wert: ' . $this->dbval . '</option>';
		}

		$html .= '</select>';

		return $html;
	}
};
