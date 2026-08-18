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
		$selected = $this->dbval? 'checked="checked" ' : '';

		// Feld-Fixierung (prop 'edit.protectedBy', z.B. now_zustimmung_fix):
		// Ist das Sperr-Feld im aktuellen Datensatz gesetzt, wird die Checkbox
		// deaktiviert angezeigt; ein Hidden-Feld reicht den gespeicherten Wert
		// durch. Ein kleines Script koppelt die Anzeige live an die Sperr-
		// Checkbox im selben Formular. Serverseitig schuetzt zusaetzlich
		// save_record_() (lib/edit/data.inc.php) vor dem Ueberschreiben.
		$protectedBy = isset($this->row_def->prop['edit.protectedBy'])? trim((string)$this->row_def->prop['edit.protectedBy']) : '';
		if( $protectedBy != '' && !$this->is_in_secondary() && !$this->is_readonly() )
		{
			$isProtected = false;
			$id = isset($addparam['id'])? intval($addparam['id']) : -1;
			if( $id > 0 && is_object($this->table_def) && isset($addparam['dba']) && is_object($addparam['dba']) )
			{
				$addparam['dba']->query('SELECT '.$protectedBy.' AS protector FROM '.$this->table_def->name.' WHERE id='.$id);
				if( $addparam['dba']->next_record() && intval($addparam['dba']->f('protector')) > 0 ) {
					$isProtected = true;
				}
			}

			$boxId = 'ctlflag_' . preg_replace('/[^a-zA-Z0-9_]/', '', $this->name);
			$html  = '<input type="checkbox" name="'.$this->name.'" value="1" id="'.$boxId.'_box" '.$selected
					.($isProtected? 'disabled="disabled" ' : '').$this->tooltip_attr().'/>';
			$html .= '<input type="hidden" name="'.$this->name.'" value="'.($this->dbval? 1 : 0).'" id="'.$boxId.'_hid" '
					.($isProtected? '' : 'disabled="disabled" ').'/>';
			$html .= '<script type="text/javascript">'
					.'document.addEventListener("DOMContentLoaded", function() {'
						.'var box = document.getElementById("'.$boxId.'_box");'
						.'var hid = document.getElementById("'.$boxId.'_hid");'
						.'var prot = document.getElementsByName("f_'.$protectedBy.'");'
						.'var protBox = null;'
						.'for( var i = 0; i < prot.length; i++ ) { if( prot[i].type == "checkbox" ) { protBox = prot[i]; break; } }'
						.'if( !box || !hid || !protBox ) return;'
						.'var upd = function() { box.disabled = protBox.checked; hid.disabled = !protBox.checked; };'
						.'box.addEventListener("change", function() { hid.value = box.checked? 1 : 0; });'
						.'protBox.addEventListener("change", upd);'
						.'upd();'
					.'});'
					.'</script>';
			return $html;
		}

		return '<input type="checkbox" name="'.$this->name.'" value="1" '.$selected.$this->tooltip_attr().'/>';
	}
};

