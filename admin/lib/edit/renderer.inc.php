<?php

/******************************************************************************
Edit or View a Record
 ***************************************************************************/ /**

parameters on first call:

- `db` - the database to use, defaults to the database defined in 
			config.inc.php

- `table` - the table to edit/view

- `id - the record to edit - if -1 or unset, a new record will be created

- `nodefaults` - if set, the new record will be created empty - otherwise, 
			the defaults are used

the new `layout.*` options that may be adde to `$addparam` of each control:

- `layout.*, help.*` - control-independent options as:
- 'layout.value.aslabel' = 0|1 display stored values as label for the input field   
- `layout.defhide = 0|1|2` - Hides controls by default - either if the 
			control is still default (1) or always, even if set by the 
			user (2). To show the control, the user must click the link `>` 
			or `>~` (the tilde is used to indicate there are values hidden)
								
- `layout.defhide.title` - Title or tooltip of the link to show controls 
			hidden by `layout.defhide`; if missing, the description of the 
			first hidden row is used

- 'layout.input.hide' = 0|1 hide input field and therefore values (e.g. show labels only)

- `layout.join = 0|1` - Add the control to the end of the _previous_ line 
			instead of starting a new one (adding a space at the end of the 
			description will join the _next_ control to the current line, 
			however, this approach is deprecated)

- `layout.descr` - set the description of the row, defaults to the value given 
			as the third paramter to add_row(), (technically, the first
			description is shown in a separate <td>, subsequent descriptions
			are just added as plain text)

- `layout.descr.hide = 0|1` - hide the description; by default, the description 
			is visible
		
- `layout.descr.class` - add the given class(es) to the description

- `layout.section = 1|<title>` - start a new section simelar to TABLE_SECTION 
			in the old editor.  The title of the section can be set using 
			`layout.section.title`.
		
- `layout.after` - html text to be display right after the control; if nothing 
			is given, ` &nbsp; ` is added.

- `help.url` - if set, a little help icon is shown next to the control; a click 
			on the icon will open the given URL.

- `help.tooltip` - tooltip to show if the mouse is over the control; we simply 
			use the title-attribute for this purpose

- `ctrl.*` - control-depending options as:

- `ctrl.rows` - the number of rows for an textarea fields.

- `ctrl.size` - the width for normal text fields as <min>-<default>-<max> or
			<minAndDefault>-<max>; the minimum and maximum are used to set up a
			width corresponding to the real number of charactes; the default is
			used if the field is still empty

- `ctrl.placeholder` - text to be shown if a text field ist empty, we simply
			use the placeholder-attribute for this purpose

- `ctrl.class` - CSS class to be added to attribute or text fields

- `ctrl.phpclass` - PHP class to use, should be sth. like CONTROL_FOOBAR_CLASS,
			if unset, a default class for each TABLE_ROW-Type is used (which may
			also be used as a base class for ctrl.phpclass)
			
- `showIfSetting` - don't display row if dependent upon setting and setting is 0
			
- 'value.replace' - replace (multiple) string(s) for string(s) before output, e.g. => array( array(','), array(';') )
- 'value.table_key'=>array( 'table' => 'stichwoerter', 'key' => 'id', 'value' => 'stichwort'),


 ******************************************************************************/

if (isset($GLOBALS['WisyKi'])) {
	require_once("WisyKi/wisykicompetence.php");
	require_lang('WisyKi/lang/basic');
}
// the JSON callback
require_once('functions.inc.php'); // this will also make the file uncacheable, maybe we should implement a little cache of a few seconds?


require_once('index_tools.inc.php');





// global $codes_beginnoptionen, $codes_dauer, $codes_stichwort_eigenschaften, $codes_rechtsform, $codes_kurstage, $codes_tagescode;


class EDIT_RENDERER_CLASS
{

	private $columnsHash;
	private $site;
	private $data;
	private $rows;
	private $columnsCount;
	private $ob;
	private $table_def;
	private $esco_type;




	function __construct()
	{
		if (@file_exists('WisyKi/config/codes.inc.php')) {
			require_once('WisyKi/config/codes.inc.php');
		} else
			die('Verzeichnis unerwartet.');
		global $codes_stichwort_eigenschaften;
		$tp = explode("###",$codes_stichwort_eigenschaften);
		for ($c1 = 1; $c1 < sizeof((array) $tp); $c1 += 2)
		{
			if ($tp[$c1] == "ESCO-Kompetenz")
			   $this->esco_type = $tp[$c1-1];
		}
		$this->ob = new AUTOCOMPLETE_JSON_CLASS;
	}

	// private function find_code($name){
	// 	$test = $codes_stichwort_eigenschaften;
	// }
	/**************************************************************************
	 * render page frame
	 **************************************************************************/


	private function render_page_start_()
	{
		$site = $GLOBALS['site'];

		// page start
		$site->title = $this->data->get_title();
		$site->addScript('WisyKi/lib/edit/edit.js');
		$site->addScript('WisyKi/lib/edit/suggest_comp_level.js');
		$site->pageStart();

		// menu
		$site->menuItem('mmainmenu', $this->data->table_def ? $this->data->table_def->descr : $this->data->table_name,  "<a href=\"index.php?table={$this->data->table_name}\">");

		// menu: prev/next
		// (we try to get the ID directly, this saves us about 50 ms on each paging)
		$paging_ob = new EDIT_PAGING_CLASS;
		$can_prev_next = ($this->data->db_name == '' && $this->data->id != -1) ? true : false;

		$prev_url = '';
		$can_prev = ($can_prev_next && isset($this->no_paging) && $this->no_paging != 'prev');
		if ($can_prev) {
			if (($prev_id = $paging_ob->search_id($this->data->table_name, $this->data->id, 'prev')) != 0) {
				$prev_url = "<a href=\"edit.php?table={$this->data->table_name}&amp;id={$prev_id}\">";
			} else {
				$prev_url = "<a href=\"edit.php?table={$this->data->table_name}&amp;id={$this->data->id}&amp;paging=prev\">";
			}
		}
		$site->menuItem('mprev', htmlconstant('_PREVIOUS'), $prev_url);

		$next_url = '';
		$can_next = ($can_prev_next && isset($this->no_paging) && $this->no_paging != 'next');
		if ($can_next) {
			if (($next_id = $paging_ob->search_id($this->data->table_name, $this->data->id, 'next')) != 0) {
				$next_url = "<a href=\"edit.php?table={$this->data->table_name}&amp;id={$next_id}\">";
			} else {
				$next_url = "<a href=\"edit.php?table={$this->data->table_name}&amp;id={$this->data->id}&amp;paging=next\">";
			}
		}
		$site->menuItem('mnext', htmlconstant('_NEXT'), $next_url);

		// menu: search
		$can_search = ($this->data->db_name == '') ? true : false;
		$site->menuItem('msearch', htmlconstant('_SEARCH'), $can_search ? "<a href=\"index.php?table={$this->data->table_name}\">" : '');

		// menu: new/empty/copy
		$can_new = ($this->data->db_name == '' && ($this->data->table_def->acl & ACL_NEW) && !$this->data->only_secondary) ? true : false;
		$site->menuItem('mnew', htmlconstant('_NEW'), $can_new ? "<a href=\"edit.php?table={$this->data->table_name}\">" : '');
		if (isset($this->data->table_def) && $this->data->table_def && $this->data->table_def->uses_track_defaults()) {
			$site->menuItem('mempty', htmlconstant('_EMPTY'), $can_new ? "<a href=\"edit.php?table={$this->data->table_name}&amp;nodefaults=1\">" : '');
		}

		$can_duplicate = ($can_new && $this->data->id != -1);
		$site->menuItem('mcopy', htmlconstant('_EDIT_COPY'), $can_duplicate ? "<a href=\"edit.php?table={$this->data->table_name}&amp;id={$this->data->id}&amp;copy_record=1\">" : '');

		// menu: delete (for speed reasons, we do not disable this item if the record hsa references; we check the number of references if the user clicks "delete")
		$can_delete = ($this->data->table_def->acl & ACL_DELETE && $this->data->id != -1 && $this->data->db_name == '') ? true : false;
		$site->menuItem('mdel', htmlconstant('_DELETE'), $can_delete ? "<a href=\"edit.php?table={$this->data->table_name}&amp;id={$this->data->id}&amp;delete_record=1\" onclick=\"return confirm('" . htmlconstant('_EDIT_REALLYDELETERECORD') . "')\">" : '');

		// menu: view
		if (isset($this->data->table_name) && ($this->data->table_name == 'user' || $this->data->table_name == 'user_grp')) {
			$viewurl = 'user_access_view.php?showfields=1' . ($this->data->table_name == 'user' ? "&user={$this->data->id}" : "");
		} else if (@file_exists("config/view_{$this->data->table_name}.inc.php")) {
			$viewurl = 'module.php?module=view_' . $this->data->table_name . '&id=' . $this->data->id;
		} else {
			$viewurl = '';
		}
		if ($viewurl) {
			$can_view = ($this->data->id != -1 && $this->data->db_name == '');
			$site->menuItem('mview', htmlconstant('_VIEW'), ($can_view) ? "<a href=\"" . isohtmlspecialchars($viewurl) . "\" target=\"_blank\" rel=\"noopener noreferrer\">" : '');
		}

		// start page: menu link to edit plugin(s)
		for ($i = 0; $i <= 3; $i++) {
			if (@file_exists("config/edit_plugin_{$this->data->table_def->name}_{$i}.inc.php")) {
				$site->menuItem(
					"mplugin$i",
					htmlconstant(strtoupper("_edit_plugin_{$this->data->table_def->name}_{$i}")),
					($this->data->id != -1) ? "<a href=\"module.php?module=edit_plugin_{$this->data->table_def->name}_{$i}&amp;id={$this->data->id}\" target=\"edit_plugin_{$this->data->table_def->name}_{$i}\" onclick=\"return popup(this,750,550);\">" : ''
				);
			} else {
				break;
			}
		}

		// menu: right
		$site->menuHelpScope	= $this->data->table_name . '.ieditrecords';
		$site->menuLogoutUrl	= "edit.php?table={$this->data->table_name}&id={$this->data->id}" . ($this->data->db_name == '' ? '' : "&db={$this->data->db_name}");

		$site->menuSettingsUrl = "settings.php?table={$this->data->table_name}&scope=edit&reload=" . urlencode("edit.php?table={$this->data->table_name}" . ($this->data->id != -1 ? "&id={$this->data->id}" : "") . ($this->data->db_name == '' ? '' : "&db={$this->data->db_name}"));

		$can_print = ($this->data->db_name == '' && $this->data->id != -1) ? true : false;
		$site->menuPrintUrl = $can_print ? "print.php?table={$this->data->table_name}&id={$this->data->id}" : '';

		// menu: out
		$site->menuOut();

		// start formular
		echo "\n";
		form_tag('edit', 'edit.php', '', $this->data->can_contain_blobs ? 'multipart/form-data' : '');
		if (isset($this->data->can_contain_blobs) && $this->data->can_contain_blobs) {
			$uploader = new IMP_UPLOADER_CLASS;
			form_hidden('MAX_FILE_SIZE', $uploader->get_max_upload_bytes());
		}
		form_hidden('subseq', 1);
		form_hidden('db', $this->data->db_name);
		form_hidden('table', $this->data->table_name);
		form_hidden('id', $this->data->id);
		echo "\n";
	}

	private function render_page_end_()
	{
		$site = $GLOBALS['site'];

		// button area
		$site->skin->fixedFooterStart();
		$site->skin->buttonsStart();
		if (isset($this->data->table_def->acl) && $this->data->table_def->acl & ACL_EDIT || $this->data->id == -1) {
			// the submit_ok/submit apply buttons also habe the purpose to validate the end of the
			// vars stream.  If they're not present, we know there is an error with max_input_vars, see [1]
			form_button('submit_ok', htmlconstant('_OK'));
			form_clickbutton("index.php?table={$this->data->table_name}&justedited={$this->data->id}#id{$this->data->id}", htmlconstant('_CANCEL'));
			form_button('submit_apply', htmlconstant('_APPLY'));
		} else {
			form_clickbutton("index.php?table={$this->data->table_name}", htmlconstant('_CANCEL'));
		}


		$site->skin->buttonsBreak();
		echo '<a href="#" onclick="return e_rightstoggle();" title="Rechte ein-/ausblenden"><span id="e_rightsicon">&#x25ba;</span> ';
		echo htmlconstant('_EDIT_CREATED') . ': ' . isohtmlentities(strval(sql_date_to_human($this->data->date_created, 'datetime')));
		echo ' | ';
		echo htmlconstant('_EDIT_MODIFIED') . ': ' . isohtmlentities(strval(sql_date_to_human($this->data->date_modified, 'datetime')));
		$dataID = isset($this->data->id) ? $this->data->id : null;
		if ($dataID != -1) {
			echo ' ' .  htmlconstant('_EDIT_BY') . ' ' . user_html_name($this->data->user_modified);
		}
		echo '</a>';

		$log_url = 'log.php?table=' . $this->data->table_name;
		if ($dataID != -1) {
			$log_url .= '&id=' . $this->data->id;
		}
		echo " | <a href=\"" . isohtmlspecialchars($log_url) . "\" target=\"_blank\" rel=\"noopener noreferrer\" title=\"Protokoll anzeigen\" rel=\"noopener noreferrer\">" . htmlconstant('_LOG') . '</a>';

		$site->skin->buttonsEnd();
		$site->skin->fixedFooterEnd();

		// end formular
		echo "</form>\n";

		// page end
		$site->pageEnd();
	}

	private function halt($msg)
	{
		$GLOBALS['site']->abort(__FILE__, __LINE__, $msg);
		exit();
	}



	/**************************************************************************
	 * render data
	 **************************************************************************/

	private function render_defhide_link()
	{
		if (isset($this->sth_hidden) && $this->sth_hidden) {
			$tooltip = $this->sth_hidden_tooltip;
			if (isset($this->sth_hidden_has_content) && $this->sth_hidden_has_content) {
				$tooltip .= ' (enth&auml;lt Daten)';
			}

			$line = '<a href="#" class="e_defhide_more" data-defhide-id="' . $this->defhide_id . '" title="' . $tooltip . ' ein-/ausblenden">';
			$line .= isset($this->sth_hidden_has_content) && $this->sth_hidden_has_content ? '&#x25ba;~' : '&#x25ba;'; // ARROW_RIGHT (BLACK RIGHT-POINTING TRIANGLE)
			$line .= ' &nbsp;</a> ';

			echo $line;
		}
		$this->sth_hidden = false;
		$this->defhide_id = isset($this->defhide_id) ? $this->defhide_id++ : 1;
		$this->sth_hidden_tooltip = '';
		$this->sth_hidden_has_content = false;
	}

	private $control_table_open = false;
	private function open_control_table()
	{
		if (isset($this->control_table_open) && $this->control_table_open) return;
		$this->control_table_open = true;

		echo '<table class="e_tb">';
	}
	private function close_control_table()
	{
		if (!isset($this->control_table_open) || !$this->control_table_open) return;
		$this->control_table_open = false;

		$this->close_control_row();
		echo '</table>';
		echo "\n";
	}

	private $control_row_open = false;
	private function open_control_row($label_html, $bg_class = '')
	{
		if (isset($this->control_row_open) && $this->control_row_open) return;
		$this->control_row_open = true;
		$this->open_control_table();

		$class = "e_cll"; // cell left
		if ($bg_class) $class .= ' ' . $bg_class;
		if ($class) $class = ' class="' . $class . '"';
		$line =  '<tr><td' . $class . '>' . $label_html . '</td>';

		$class = "e_clr"; // cell right
		if ($bg_class) $class .= ' ' . $bg_class;
		if ($class) $class = ' class="' . $class . '"';
		$line .= '<td' . $class . '>';

		echo $line;
	}
	private function close_control_row()
	{
		if (!isset($this->control_row_open) || !$this->control_row_open) return;
		$this->control_row_open = false;

		$this->render_defhide_link();

		echo '</td></tr>';
		echo "\n";
	}
	//show or hide controls kategory dependent
	private function kategory_layout($control)
	{

		$temp = explode('###', $control->row_def->addparam);
		for ($f = 0; $f < sizeof((array) $temp); $f++) {
			if ($temp[$f] == $control->dbval) {
				$selkat = $temp[$f + 1];
				break;
			}
		}
		if (isset($selkat)) {
			if ($selkat != "Berufliche Bildung")
				regSet("hideifnojob", 0, '');
			else
				regSet("hideifnojob", 1, '');
			if ($selkat != "Sprache")
				regSet("hideifnospeech", 0, '');
			else
				regSet("hideifnospeech", 1, '');
			if ($selkat != "Sprache" && $selkat != "Berufliche Bildung")
				regSet("hideifnoscout", 0, '');
			else
				regSet("hideifnoscout", 1, '');
		}
	}
	private function correct_controls_ifpresent($stichworte)
	{
		$selected_level = 0;
		$selected_category = 0;
		$selected_speech = 0;
		$selected_abschluss = 0;
		$selected_permentry = 0;
		$selected_foerderungsart = array();
		$selected_kompetenz = array();
		$selected_vorschlaege = array();

		foreach ($this->data->controls as $control) {

			// Don't show level stichwoerter in stichwort field. 
			if ($control->name == 'f_level') {
				$selected_level = $control->dbval;
			} else if ($control->name == 'f_kategorie') {
				$selected_category = $control->dbval;
			} else if ($control->name == 'f_speech') {
				$selected_speech = $control->dbval;
			} else if ($control->name == 'f_abschluss') {
				$selected_abschluss = $control->dbval;
			} else if ($control->name == 'f_lernform') {
				$selected_lernform = explode(",", $control->dbval);
			} else if ($control->name == 'f_foerderungsart') {
				$selected_foerderungsart = explode(",", $control->dbval);
			} else if ($control->name == 'f_permentry') {
				$test = $control->row_def->addparam->rows[0]->addparam[0];
				if (str_starts_with($test, "Id"))
					$selected_permentry = substr($test, 2);
				else
					$selected_permentry = $test;
			} else if ($control->name == 'f_kompetenz') {
				$selected_kompetenz	= explode(",", $control->dbval);
			} else if ($control->name == 'f_vorschlaege') {
				$selected_vorschlaege	= explode(",", $control->dbval);
			}
		}


		if (($key = array_search($selected_level, $stichworte)) !== false) {
			unset($stichworte[$key]);
		}
		if (($key = array_search($selected_category, $stichworte)) !== false) {
			unset($stichworte[$key]);
		}
		if (($key = array_search($selected_speech, $stichworte)) !== false) {
			unset($stichworte[$key]);
		}
		if (($key = array_search($selected_abschluss, $stichworte)) !== false) {
			unset($stichworte[$key]);
		}
		if (($key = array_search($selected_permentry, $stichworte)) !== false) {
			unset($stichworte[$key]);
		}
		if (isset($selected_lernform[0]))
			foreach ($selected_lernform as $lernf)
				if (($key = array_search($lernf, $stichworte)) !== false) {
					unset($stichworte[$key]);
				}

		if (isset($selected_foerderungsart[0]))
			foreach ($selected_foerderungsart as $lernf)
				if (($key = array_search($lernf, $stichworte)) !== false) {
					unset($stichworte[$key]);
				}
		if (isset($selected_kompetenz[0]))
			foreach ($selected_kompetenz as $komp)
				if (($key = array_search($komp, $stichworte)) !== false) {
					unset($stichworte[$key]);
				}
		if (isset($selected_vorschlaege[0]))
			foreach ($selected_vorschlaege as $komp)
				if (($key = array_search($komp, $stichworte)) !== false) {
					unset($stichworte[$key]);
				}
		return $stichworte;
	}


	private function render_data_()
	{
		$bin = '';

		if (isset($this->data->id) && $this->data->id > 0 && regGet('toolbar.bin', 1) && !isset($_GET['getasajax'])) {
			$bin = '<div style="float:left;">' . bin_render($this->data->table_def->name, $this->data->id) . '</div>';
		}

		$this->open_control_row($bin . htmlconstant('_ID') . ':');
		$value = $this->data->id == -1 ? htmlconstant(_NA) : $this->data->id;
		if (isset($this->data->db_name) && $this->data->db_name != '') {
			$value = '<b>' . $value . '@' . $this->data->db_name . '</b>';
		}
		echo  $value;

		if (!isset($this->data->controls[0]->row_def->prop['layout.join']) || !$this->data->controls[0]->row_def->prop['layout.join']) {
			$this->close_control_row();
		} else {
			echo ' &nbsp; ';
		}



		$this->data->connect_to_db_();
		$this->defhide_id = 1; // note: the ID is only guaranteed to be unique inside the same object! if objects are duplicated, they will have the same IDs!
		for ($f = 0; $f < sizeof((array) $this->data->controls); $f++) {
			// don't display row if dependent upon setting and setting is 0
			$showIfSetting = isset($this->data->controls[$f]->row_def->prop['showIfSetting']) ? $this->data->controls[$f]->row_def->prop['showIfSetting'] : false;
			if (!($showIfSetting === false))
				if (regGet($showIfSetting, 1) != 1)
					continue;

			$control = $this->data->controls[$f];

			if (($control->row_def->flags & TABLE_ROW) == 0) {
				$this->close_control_table();

				$attr = '';

				if (isset($control->row_def->prop['__META__'])) {
					switch ($control->row_def->prop['__META__']) {
						case 'secondary_start':
							echo "<div class=\"e_secondary\" data-descr=\"" . $control->row_def->descr . "\">\n";
							break;
						case 'object_start':
							echo "<div class=\"e_object\" data-descr=\"" . $control->row_def->descr . "\">\n";
							$attr = 'data-meta="' . $control->row_def->prop['__META__'] . '" ';
							break;
						case 'template_start':
							echo "<div class=\"e_template\">\n";
							break;
					}
				}

				echo "<input name=\"{$control->name}\" {$attr}type=\"hidden\" value=\"" . isohtmlspecialchars($control->dbval) . "\" />\n";

				if (isset($control->row_def->prop['__META__'])) {
					switch ($control->row_def->prop['__META__']) {
						case 'template_end':
							echo "</div>\n";
							break;
						case 'object_end':
							echo "</div>\n";
							break;
						case 'secondary_end':
							echo "</div>\n";
							break;
					}
				}
			} else {
				// check, if the control should be hidden by default
				$defhide = '';
				$defhide_end = '';
				if (isset($control->row_def->prop['layout.defhide']) && $control->row_def->prop['layout.defhide']) {
					if (!isset($this->sth_hidden_tooltip) || !$this->sth_hidden_tooltip) {
						$this->sth_hidden_tooltip = isset($control->row_def->prop['layout.defhide.tooltip']) && $control->row_def->prop['layout.defhide.tooltip'] ? $control->row_def->prop['layout.defhide.tooltip'] : trim($control->row_def->descr);
					}

					$is_default = $control->is_default_display(array('id' => $this->data->id));
					if (
						$is_default
						|| $control->row_def->prop['layout.defhide'] == 2
					) {
						$defhide = '<span class="e_defhide_' . $this->defhide_id . ' e_hidden">';
						$defhide_end = '</span>';
						$this->sth_hidden = true;
						if (!$is_default) {
							$this->sth_hidden_has_content = true;
						}
					}
				} else if (isset($this->sth_hidden) && $this->sth_hidden) {
					$this->render_defhide_link();
				}

				// render an optional section title
				if (isset($control->row_def->prop['layout.section'])) {
					$this->close_control_table();
					$line = '<div class="e_section">';
					$line .= $control->row_def->prop['layout.section'] != '1' ? $control->row_def->prop['layout.section'] : trim($control->row_def->descr);
					$line .= '</div>';
					echo $line;
				}

				// render control
				$control_descr = (isset($control->row_def->prop['layout.descr']) ?
					$control->row_def->prop['layout.descr'] : trim($control->row_def->descr)) . ':';
				if (isset($control->row_def->prop['layout.descr.class'])) {
					$control_descr = '<span class="' . $control->row_def->prop['layout.descr.class'] . '">' . $control_descr . '</span>';
				}

				if (!isset($this->control_row_open) || !$this->control_row_open) {
					$layoutBgClass = isset($control->row_def->prop['layout.bg.class']) ? $control->row_def->prop['layout.bg.class'] : null;
					$this->open_control_row($control_descr, $layoutBgClass);
					echo $defhide;
				} else {
					echo $defhide;
					if (!isset($control->row_def->prop['layout.descr.hide']) || !$control->row_def->prop['layout.descr.hide']) {
						echo $control_descr . ' ';
					}
				}
				if ($control->name == 'f_stichwort') {
					$stichworte = explode(',', $control->dbval);
					$stichworte = $this->correct_controls_ifpresent($stichworte);
					$control->dbval = implode(',', array_filter($stichworte));
				}


				echo $control->render_html(array(
					'dba'	=>	$this->data->dba,
					'id'	=>	$this->data->id	// may be -1, 0 or invalid otherwise
				));

				// Einbau des ESCO-Automaten
				if ($control->name == 'f_vorschlaege') {

					echo '<table class="sm">';
					echo '<tbody>';
					echo '<tr>';
					echo '<td class="sml" >';
					echo '<span class="e_hint" title="Dieser Vorgang kann laenger als 60 Sekunden dauern und wird nach 120 Sekunden abgebrochen.">';
					echo '<input    style="background-color: #FFE190; border-style: none"   type="submit"  name="submit_esco" value="ESCO-Kompetenzvorschlaege">&nbsp&nbsp';
					echo '</span>';
					//echo '<div onclick="rem_esco_proposual(index.php?table=escocategories&rtypes=ESCO&orderby=kategorie ASC);" > ESCO-Suche nach Kompetenzen </div>';
					echo '<span class="e_hint" title="Ermoeglicht die manuelle Suche nach Kompetenzen.">';
					echo '<a onclick="rem_esco_proposual(this); return;"  href="#index.php?table=escocategories&rtypes=ESCO&escolevel=1&orderby=kategorie ASC"> ESCO-Suche nach Kompetenzen </a>';
					echo '</span>';
					echo '<span class="e_hint" title="Alle angezeigten Kompetenzvorschlaege werden als Kompetenz uebernommen.">';
					echo '<input  style="background-color: #FFE190; border-style: none"  type="submit" name="submit_vorschlaege" value="Vorschlaege uebernehmen">&nbsp&nbsp';
					echo '</span>';
					echo '<span class="e_hint" title="Alle angezeigten Kompetenzvorschlaege werden geloescht.">';
					echo '<input  style="background-color: #FFE190; border-style: none"  type="submit" name="submit_discard" value="Vorschlaege ablehnen">&nbsp&nbsp';
					echo '</span>';
					echo '</td>';
					echo '</tr>';
					echo '</tbody>';
					echo '</table>';
				}
				if ($control->name == "f_kategorie") {
					$this->kategory_layout($control);
				}
				// stuff after the control
				if (isset($control->row_def->prop['layout.after'])) {
					echo $control->row_def->prop['layout.after'];
				}

				$helpattr = '';
				if (isset($control->row_def->prop['help.url']) && $control->row_def->prop['help.url']) {
					$helpattr = 'href="' . $control->row_def->prop['help.url'] . '" target="_blank" rel="noopener noreferrer"';
				} else if ($control->row_def->flags & TABLE_WIKI && ($control->row_def->flags & TABLE_ROW) == TABLE_TEXTAREA) {
					$helpattr = 'href="help.php?id=iwiki" target="help" onclick="return popup(this,500,380);"';
				}
				if ($helpattr) {
					echo '<a ' . $helpattr . ' title="' . htmlconstant('_HELP') . '">&nbsp;?</a>';
				}

				// break
				if (
					substr(strval($control->row_def->descr), -1) != ' '
					&& (!isset($this->data->controls[$f + 1]) || !isset($this->data->controls[$f + 1]->row_def->prop['layout.join']) || !$this->data->controls[$f + 1]->row_def->prop['layout.join'])
				) {
					if (isset($this->sth_hidden) && $this->sth_hidden) {
						echo ' &nbsp; ';
					}
					echo $defhide_end;
					$this->close_control_row();
				} else {
					if (!isset($control->row_def->prop['layout.after'])) {
						echo ' &nbsp; ';
					}
					echo $defhide_end;
				}
			}
		}

		$this->close_control_table();

		// render date etc.
		form_hidden('date_created',		$this->data->date_created);
		form_hidden('date_modified',	$this->data->date_modified);
		form_hidden('user_modified',	$this->data->user_modified);
		$html = '<div id="e_rights"><table>';

		$html .= '<tr><td class="e_cll">' . htmlconstant('_EDIT_GRANTSOWNER') . ':</td><td>&nbsp;';
		$html .= $this->data->control_user_created->render_html(array('dba' => $this->data->dba)) . '<br />';
		$html .= '</td></tr><tr><td>&nbsp;</td><td>';
		$rights = explode('<BR>', $this->data->control_user_access->render_html(array('dba' => $this->data->dba, 'break' => '<BR>')));
		$html .= $rights[0];
		$html .= '</td></tr>';

		$html .= '<tr><td class="e_cll">' . htmlconstant('_EDIT_GRANTSGROUP') . ':</td><td>&nbsp;';
		$html .= $this->data->control_user_grp->render_html(array('dba' => $this->data->dba)) . '<br />';
		$html .= '</td></tr><tr><td>&nbsp;</td><td>';
		$html .= $rights[1];
		$html .= '</td></tr>';

		$html .= '<tr><td class="e_cll">Rechte ' . htmlconstant('_EDIT_GRANTSOTHER') . ':</td><td>';
		$html .= $rights[2];
		$html .= '</td></tr>';

		$html .= '<tr><td class="e_cll">Referenzen:</td><td>&nbsp;';
		$html .= '<span id="e_refcontainer" data-table="' . $this->data->table_def->name . '" data-id="' . $this->data->id . '">z&auml;hle...</span>';
		$html .= '</td></tr>';

		/*
			$html .= '<tr><td class="e_cll">'.htmlconstant('_EDIT_CREATED').':</td><td>&nbsp;';
				$html .= isohtmlentities( strval( sql_date_to_human($this->data->date_created, 'datetime') ) );
			$html .= '</td></tr>';
			*/

		$html .= '</table></div>';
		echo $html;

		echo " \n";
	}


	/**************************************************************************
	 * main
	 **************************************************************************/

	private function add_errors_n_warnings_from_data_()
	{
		for ($i = 0; $i < sizeof((array) $this->data->errors); $i++) {
			$GLOBALS['site']->msgAdd($this->data->errors[$i], 'e');
		}
		$this->data->errors = array();

		if (is_array($this->data->warnings)) {
			for ($i = 0; $i < sizeof((array) $this->data->warnings); $i++) {
				$GLOBALS['site']->msgAdd($this->data->warnings[$i], 'w');
			}
		}
		$this->data->warnings = array();
	}

	private function create_ref($table, $id, $keyid, $db, $url = null)
	{
		$sql = 'SELECT primary_id, attr_id, structure_pos FROM ' . $table . ' WHERE ' . $table . '.primary_id = "' . $id . '"';
		$kurseStichwort = array();
		$db->query($sql);
		$index = 0;
		while ($db->next_record()) {
			$kurseStichwort[$index]['primary_id'] = $db->Record['primary_id'];
			$kurseStichwort[$index]['attr_id'] = $db->Record['attr_id'];
			$kurseStichwort[$index]['structure_pos'] = $db->Record['structure_pos'];
			$index++;
		}
		$nothingtodo = false;
		$maxpos = -1;
		if (sizeof((array) $kurseStichwort) > 0) { //Some keywords for this course registered
			for ($c1 = 0; $c1 < sizeof((array) $kurseStichwort); $c1++) {
				foreach ($kurseStichwort[$c1] as $pos => $val) {
					if ($pos == 'attr_id' && $val == $keyid) {
						$nothingtodo = true;
						break;
					} else {
						if ($pos == 'structure_pos' && $maxpos < intval($val)) {
							$maxpos = intval($val);
						}
					}
				}
				if ($nothingtodo)
					break;
			}
		}
		if (!$nothingtodo) {
			if ($table == "kurse_stichwort") {
				$sql = "INSERT INTO kurse_stichwort (primary_id, attr_id, structure_pos) VALUES (" . $id . ", " . $keyid . ", " . $maxpos + 1 . ")";
				$db->query($sql);
			} else {
				$sql = "INSERT INTO kurse_kompetenz (primary_id, attr_id, attr_url, suggestion, structure_pos) VALUES (" . $id . ", " . $keyid . ", '" . $url . "', 1, "  . $maxpos + 1 . ")";
				$db->query($sql);
			}
		}
	}


	private function reorg_keywords($db, $id)
	{
		$courseKeywordRef = array();
		$courseKeywordComp = array();
		$escokeywords = array();
		$maxpos = -1;
		$db->query("SELECT attr_id, structure_pos FROM kurse_stichwort");
		while ($db->next_record()) {
			$courseKeywordRef[] = $db->Record['attr_id'];
			$pos = $db->Record['structure_pos'];
			if ($maxpos < $pos)
				$maxpos = $pos;
		}
		$db->query("SELECT attr_id FROM kurse_kompetenz");
		while ($db->next_record()) {
			$courseKeywordComp[] = $db->Record['attr_id'];
		}
		if (isset($courseKeywordRef[0]) != "") {
			$db->query("SELECT id FROM stichwoerter WHERE stichwoerter.eigenschaften=" . $this->esco_type);
			while ($db->next_record()) {
				$escokeywords[] = $db->Record['id'];
			}
			if (isset($escokeywords[0]) != "") {
				foreach ($escokeywords as $keyw) {
					if (!in_array($keyw, $courseKeywordComp)) {
						$db->query("DELETE FROM stichwoerter WHERE id=" . $keyw);
						$db->query("DELETE FROM kurse_stichwort WHERE attr_id=" . $keyw);
						//	$db->query("DELETE FROM kurse_kompetenz WHERE attr_id=" . $keyw);
					}
				}
			}
			if (isset($courseKeywordComp[0]) != "") {
				foreach ($courseKeywordComp as $com) {
					if (!in_array($com, $escokeywords))
						$db->query("DELETE FROM kurse_kompetenz WHERE attr_id=" . $com);
				}
			}

			// foreach($courseKeywordComp as $ref){
			// 	if (!in_array($ref, $courseKeywordRef)){
			// 		$maxpos++;
			// 		$db->query("INSERT INTO kurse_stichwort (primary_id, attr_id, structure_pos) VALUES (" . $id . ", " . $ref . ", " . $maxpos . ")");
			// 	}
			//}
		}
	}


	public function handle_request()
	{
		require_lang('lang/edit');

		// MAIN LOGIC: see what to do ...
		/////////////////////////////////////////////

		$db = isset($_REQUEST['db']) ? $_REQUEST['db'] :  null;
		$table = isset($_REQUEST['table']) ? $_REQUEST['table'] : null;
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : -1;


		$this->data = new EDIT_DATA_CLASS(
			$db,
			$table,
			$id
		);
		// get base URL

		$baseurl = "index.php?table=$table";
		if (isset($_REQUEST['selectobject'])) {
			$baseurl .= "&selectobject";
			$settingsPrefix = 'index.select';
		} else {
			$settingsPrefix = 'index.view';
		}
		$load_from_db = false;
		$site = isset($GLOBALS['site']) ? $GLOBALS['site'] : null;

		if (isset($_REQUEST['subseq'])) {
			// ... subsequent call, not canceled (this is done without posting data)
			if (isset($_REQUEST['submit_apply']) || isset($_REQUEST['submit_ok']) || isset($_REQUEST['submit_esco']) || isset($_REQUEST['submit_vorschlaege']) || isset($_REQUEST['submit_discard']) || isset($_REQUEST['inputescoskill'])) {
				//ESCO-Vorschlaege ermitteln
				if (isset($_REQUEST['submit_esco']) || isset($_REQUEST['inputescoskill'])) {
					$db = new DB_Admin();
					$res = array();
					if (isset($_REQUEST['submit_esco'])) {
						$table = "stichwoerter";
						$load_from_post_ok = $this->data->load_from_post();
						$this->add_errors_n_warnings_from_data_(); // show errors from load_from_post()
						if (!$load_from_post_ok && is_object($site)) {
							$site->msgAdd("\n" . htmlconstant('_EDIT_RECORDNOTSAVED'), 'e');
						}


						$comp = new WISYKI_COMPETENCE_CLASS();

						$res = $comp->WisyKi_competence_search($this->data->controls, $id);
					} else {
						$sql = "SELECT kategorie, url FROM $table WHERE id=" . $id;
						$db->query($sql);
						while ($db->next_record()) {
							$r['title'] = $db->Record['kategorie'];
							$r['uri'] = $db->Record['url'];
							$res[] = $r;
						}
						$id = $_SESSION['kursid'];
					}
					if (is_array($res)) {               //create keywords
						$newComp = new EDIT_DATA_CLASS(
							null,
							"stichwoerter",
							-1
						);
						$dummy = new ROW_DUMMY_CLASS('Neue ', array('__META__' => 'object_start'));
						$dummy->acl = ACL_EDIT;
						$dummy->flags = 0;
						$newComp->controls[0] = new CONTROL_TEXT_CLASS("f_stichwort", $dummy, null);
						$newComp->controls[4] = new CONTROL_ENUM_CLASS("f_eigenschaften", $dummy, null);
						$newComp->controls[4]->dbval = $this->esco_type;
						
						foreach ($res as $r) {

							$newComp->id = -1;
							$newComp->controls[0]->dbval = isohtmlspecialchars(utf8_decode($r['title']));
							//already defined?
							$sql = 'SELECT id FROM stichwoerter WHERE stichwoerter.stichwort = "' . $newComp->controls[0]->dbval . '" AND stichwoerter.eigenschaften="' . $newComp->controls[4]->dbval . '"';
							$db->query($sql);
							if ($db->next_record())
								$keyid = $db->Record['id'];
							else {
								$newComp->save_to_db();
								$keyid = $newComp->id;
							}
							$this->create_ref("kurse_stichwort", $id, $keyid, $db);
							$this->create_ref("kurse_kompetenz", $id, $keyid, $db, $r['uri']);
						}
					}
					header('Location: \admin\edit.php?table=kurse&id=' . $id);
					exit;
				} else if (isset($_REQUEST['submit_vorschlaege'])) {
					$load_from_post_ok = $this->data->load_from_post();
					$this->add_errors_n_warnings_from_data_(); // show errors from load_from_post()

					if (!$load_from_post_ok && is_object($site)) {
						$site->msgAdd("\n" . htmlconstant('_EDIT_RECORDNOTSAVED'), 'e');
					} else {
						$comp = new WISYKI_COMPETENCE_CLASS();
						$new_ids = $comp->WisyKi_find_competence_proposuals($this->data->controls);
						$db1 = new DB_Admin();
						$old_ids = $comp->read_simple_list_($db1, "SELECT attr_id AS ret FROM kurse_kompetenz WHERE primary_id=$id ORDER BY structure_pos;");
						if (isset($old_ids[0]) || isset($new_ids[0])) {
							$max_pos = -1;
							$db1->query("SELECT structure_pos FROM kurse_kompetenz WHERE primary_id=$id ORDER BY structure_pos");
							while ($db1->next_record()) {
								$vgl = $db1->Record['structure_pos'];
								if ($max_pos < $vgl)
									$max_pos = $vgl;
							}
							$max_pos2 = -1;
							$db1->query("SELECT structure_pos FROM kurse_stichwort WHERE primary_id=$id ORDER BY structure_pos");
							while ($db1->next_record()) {
								$vgl = $db1->Record['structure_pos'];
								if ($max_pos2 < $vgl)
									$max_pos2 = $vgl;
							}
							$newcomp = array();
							$db1->query(("SELECT attr_id, attr_url FROM kurse_kompetenz WHERE suggestion=1 AND primary_id=$id;"));
							while ($db1->next_record()) {
								$vgl = $db1->Record['attr_id'];
								if (in_array($vgl, $new_ids)) {
									$newel = array();
									$newel['attr_id'] = $vgl;
									$newel['attr_url'] = $db1->Record['attr_url'];
									$newcomp[] = $newel;
								}
							}
							$db1->query("DELETE FROM kurse_kompetenz WHERE suggestion=1 AND primary_id=$id;");

							if (sizeof((array) $old_ids) && $old_ids[0] != "") {
								$sqlattr = "DELETE FROM kurse_stichwort WHERE primary_id = $id AND attr_id IN (";
								for ($i = 0; $i < sizeof((array) $old_ids); $i++) {
									$sqlattr .= $i ? ', ' : '';
									$max_pos++;
									$sqlattr .=  intval($old_ids[$i]);
								}
								$sqlattr .= ");";
								$db1->query($sqlattr);
							}
							if (sizeof((array) $newcomp) && $new_ids[0] != "") {
								$sqlattr = "INSERT INTO kurse_kompetenz (primary_id,attr_id, attr_url, suggestion, structure_pos) VALUES ";
								$sqlattr2 = "INSERT INTO kurse_stichwort (primary_id,attr_id, structure_pos) VALUES ";
								for ($i = 0; $i < sizeof((array) $newcomp); $i++) {
									$sqlattr .= $i ? ', ' : '';
									$sqlattr2 .= $i ? ', ' : '';
									$max_pos++;
									$sqlattr .= "($id," . intval($newcomp[$i]['attr_id']) . ", '" . $newcomp[$i]['attr_url'] .  "', 0, $max_pos)";
									$sqlattr2 .= "($id," . intval($newcomp[$i]['attr_id']) . ", $max_pos2)";
								}
								$db1->query($sqlattr);
								$db1->query($sqlattr2);
							}
						}
						$this->reorg_keywords($db1, $id);
						header('Location: \admin\edit.php?table=kurse&id=' . $id);

						exit;
					}
				} else if (isset($_REQUEST['submit_discard'])) {
					$db1 = new DB_Admin();
					$db2 = new DB_Admin();
					$sqlattr = "SELECT attr_id FROM kurse_kompetenz WHERE primary_id = $id AND suggestion=1";
					$db1->query($sqlattr);
					while ($db1->next_record()) {
						$attr_id = $db1->Record['attr_id'];
						$db2->query("DELETE FROM kurse_kompetenz WHERE primary_id = $id AND attr_id=" . $attr_id . " AND suggestion=1");
						$db2->query("DELETE FROM kurse_stichwort WHERE primary_id = $id AND attr_id=" . $attr_id);
					}
					$this->reorg_keywords($db1, $id);
					header('Location: \admin\edit.php?table=kurse&id=' . $id);

					exit;
				} else {
					$load_from_post_ok = $this->data->load_from_post();
					$this->add_errors_n_warnings_from_data_(); // show errors from load_from_post()

					if (!$load_from_post_ok && is_object($site)) {
						$site->msgAdd("\n" . htmlconstant('_EDIT_RECORDNOTSAVED'), 'e');
					} else {
						$save_to_db_ok = $this->data->save_to_db();
						$this->add_errors_n_warnings_from_data_(); // show errors from save_to_db()

						if ($save_to_db_ok && is_object($site)) {
							$site->msgAdd("\n" . htmlconstant($this->data->already_up_to_date ? '_EDIT_RECORDUPTODATE' : '_EDIT_RECORDSAVED'), 'i');

							if (isset($_REQUEST['submit_ok'])) {
								redirect('index.php?table=' . $this->data->table_name . "&justedited={$this->data->id}#id{$this->data->id}");
							} else if (isset($this->data->returnreload) && $this->data->returnreload) {
								//redirect('edit.php?table='.$this->data->table_name.'&id='.$this->data->id. ($this->data->db_name==''? '' : "&db={$this->data->db_name}"));
								$load_from_db = true;
							}
						} else {
							if (is_object($site))
								$site->msgAdd("\n" . htmlconstant('_EDIT_RECORDNOTSAVED'), 'e');
						}
					}
				}
			} else {
				// ... subsequent all but ok/apply not set - this should not happen, check against max_input_vars, see also [1]
				$used_input_vars = 0;
				foreach ($_REQUEST as $reqName => $reqVal) {
					$used_input_vars += is_array($reqVal) ? sizeof($reqVal) : 1;
				}

				if (is_object($site))
					$site->msgAdd("Fatal error: Corrupted or too many POST data, this should not happen. Using ~$used_input_vars vars of " . ini_get('max_input_vars') . ' allowed vars. Consider to increase max_input_vars in the php.ini file.', 'e');
				$load_from_db = true;
			}
		} else if (isset($_REQUEST['delete_record']) && $_REQUEST['delete_record']) {
			// delete the record
			$dataID = isset($this->data->id) ? $this->data->id : null;
			if ($dataID != -1) {
				if ($this->data->delete_from_db()) {
					$this->add_errors_n_warnings_from_data_();
					if (is_object($site))
						$site->msgAdd(htmlconstant('_EDIT_RECORDDELETED'), 'i');
					redirect('index.php?table=' . $this->data->table_name);
				} else {
					$this->add_errors_n_warnings_from_data_();
					$load_from_db = true;
				}
			}
		} else if (isset($_REQUEST['paging'])) {
			// go to previous/next record from the current one, do not save
			$paging = new EDIT_PAGING_CLASS;
			$fwd_id = $paging->paginate($this->data->table_name, $this->data->id, $_REQUEST['paging']);
			if ($fwd_id) {
				redirect('edit.php?table=' . $this->data->table_name . '&id=' . $fwd_id);
			} else {
				$this->no_paging = $_REQUEST['paging'];

				if (is_object($site))
					$site->msgAdd("Keine weiteren Datens&auml;tze.", 'w');
				$load_from_db = true;
			}
		} else if (isset($_GET['getreferencesstring'])) {
			// ajax request to get the number of references
			$html = '';
			if (isset($this->data->table_def) && $this->data->table_def->num_references($this->data->id, $references)) {
				$html .= $this->data->references2string($references);
			} else {
				$html .= '0';
			}
			echo utf8_encode($html);
			exit();
		} else if (isset($_GET['getasajax'])) {

			$this->data->load_from_db();
			ob_start();
			$this->render_data_();
			$html = ob_get_contents();
			ob_end_clean();
			echo utf8_encode($html);
			exit();
		} else {
			$load_from_db = true;
		}
		if (isset($_REQUEST['table'])) {
			if ($_REQUEST['table'] == "kurse") {
				$GLOBALS['kategory'] = array();
				$GLOBALS['kategory']['table'] = $table;
				$GLOBALS['kategory']['id'] = (isset($_REQUEST['id'])) ? $_REQUEST['id'] : null;
				$GLOBALS['kategory']['kat'] = (isset($_REQUEST['kat'])) ? $_REQUEST['kat'] : null;
				if (isset($_REQUEST['id']))
					$_SESSION['kursid'] = $_REQUEST['id'];
			}
		}
		//Params in case of Kategory has changed

		if ($load_from_db) {
			// first call
			$dataID = isset($this->data->id) ? $this->data->id : null;
			if ($dataID == -1) {
				$_SESSION['g_session_track_defaults']['stichwoerter'] = '';
				$nodefaults = isset($_REQUEST['nodefaults']) ? $_REQUEST['nodefaults'] : null;
				$this->data->load_blank(intval($nodefaults) ? false : true /*use_tracked_defaults?*/);
			} else {
				$copyRecord = isset($_REQUEST['copy_record']) ? $_REQUEST['copy_record'] : null;
				$this->data->load_from_db($copyRecord ? true : false);
			}
			$this->add_errors_n_warnings_from_data_();
		}

		$this->render_page_start_();
		$this->render_data_();
		$this->render_page_end_();
		//$this->data->save_to_db();
	}
}
