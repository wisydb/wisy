<?php

/*
	Hinweise:
	
	- fuer MultiEdit ist das Recht "SYSTEM.MULTIEDIT" erforderlich. Derzeit ist dies
	  der Einfachheit halber *eine* Einstellung fuer *alle* Tabellen 
	
	- Der Menuepunkt "MultiEdit" ergibt sich aus den Systemlokalisierung unter "_INDEX_PLUGIN_<tableName>_0"
	
	- Wenn es beim Benutzer "template" die Einstellung "index_plugin_<tableName>_0.access=kurse.MULTIEDIT" gibt, 
	  wird der Menuepunkt nur angezeigt, wenn der jeweilige Benutzer das Recht "kurse.MULTIEDIT" hat
	  (sollte sich der Name der Rechte aendern, muss dies natuerlich auch im Template angepasst werden)
	  
	- "Loeschen" bei Nummern-Felder waere zu "gefaehrlich", weil bei Preis u. Sonderpreis "Loeschen" = "-1" bedeuten wuerde. Bei Dauer waere "Loeschen" aber "0"m bei Tagescode dagegen "1" etc.
*/


require_once($_SERVER['DOCUMENT_ROOT'] . '/admin/functions.inc.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/admin/eql.inc.php');


function form_control_text__($name, $value, $width, $maxlen) // the parameter order has changed with admin-5, if admin-5 gets the default, we can get rid of this crappy function
{
	if (defined(CMS_VERSION) && intval(CMS_VERSION) >= 5) {
		form_control_text($name, $value, $width, $maxlen);
	} else {
		form_control_text($name, $value, $width, -1, $maxlen);
	}
}


class MULTIEDIT_PLUGIN_CLASS
{
	var $tableName;
	var $allIdsCount;
	var $blacklist = false;


	function getFieldActions($table, $prefix)
	{
		global $site;
		$ret = '';
		$table_def = Table_Find_Def($table);
		for ($r = 0; $r < sizeof((array) $table_def->rows); $r++) {
			$rowflags = $table_def->rows[$r]->flags;
			$rowname = $table_def->rows[$r]->name;
			$rowdescr = $site->htmldeentities(trim($table_def->rows[$r]->descr));
			/* if( strlen($rowdescr) > 16) $rowdescr = substr($rowdescr, 0, 14).'..'; */
			if (!($rowflags & TABLE_READONLY)) {
				switch ($rowflags & TABLE_ROW) {
					case TABLE_TEXT:
					case TABLE_TEXTAREA:
						$cmdPrefix = isset($cmdPrefix) ? $cmdPrefix : ''; // is never being defined / set => what's the point??

						if ($rowname != 'notizen') {
							$ret .= $cmdPrefix . "{$prefix}__{$rowname}__settext###$rowdescr: setze auf 'Parameter2'###";
							$ret .= $cmdPrefix . "{$prefix}__{$rowname}__settext2###$rowdescr: 'Parameter1' durch 'Parameter2' ersetzen###";
							$ret .= $cmdPrefix . "{$prefix}__{$rowname}__settext3###$rowdescr: L&Ouml;SCHEN###";
						}
						break;

					case TABLE_DATE:
						$cmdPrefix = isset($cmdPrefix) ? $cmdPrefix : ''; // is never being defined / set => what's the point??

						$ret .= $cmdPrefix . "{$prefix}__{$rowname}__setdate###$rowdescr: setze auf 'Parameter2'###";
						$ret .= $cmdPrefix . "{$prefix}__{$rowname}__setdate2###$rowdescr: 'Parameter1' durch 'Parameter2' ersetzen###";
						$ret .= $cmdPrefix . "{$prefix}__{$rowname}__setdate3###$rowdescr: L&Ouml;SCHEN###";
						break;

					case TABLE_INT:
						$cmdPrefix = isset($cmdPrefix) ? $cmdPrefix : ''; // is never being defined / set => what's the point??

						$ret .= $cmdPrefix . "{$prefix}__{$rowname}__settext###$rowdescr: setze auf 'Parameter2'###";
						break;

					case TABLE_ENUM:
						$cmdPrefix = isset($cmdPrefix) ? $cmdPrefix : ''; // is never being defined / set => what's the point??

						$ret .= $cmdPrefix . "{$prefix}__{$rowname}__setenum###$rowdescr: setze auf 'Parameter2'###";
						break;

					case TABLE_SATTR:
						$cmdPrefix = isset($cmdPrefix) ? $cmdPrefix : ''; // is never being defined / set => what's the point??

						$ret .= $cmdPrefix . "{$prefix}__{$rowname}__setsattr###$rowdescr: setze auf 'Parameter2'###";
						break;

					case TABLE_MATTR:
						$cmdPrefix = isset($cmdPrefix) ? $cmdPrefix : ''; // is never being defined / set => what's the point??

						$valueName = 'Wert';
						if ($rowname == 'stichwort') $valueName = '';
						if ($rowname == 'verweis' && $table == 'kurse') $valueName = 'Kurs';
						if ($rowname == 'verweis' && $table == 'anbieter') $valueName = 'Anbieter';
						$ret .= $cmdPrefix . "{$prefix}__{$rowname}__addmattr###$rowdescr: $valueName aus 'Parameter2' hinzuf&uuml;gen###";
						$ret .= $cmdPrefix . "{$prefix}__{$rowname}__delmattr###$rowdescr: $valueName aus 'Parameter2' L&Ouml;SCHEN###";
						break;
				}
			}
		}

		return $ret;
	}

	function renderBlacklistPage($msg, $msg_type = 'e', $ok = false /*error*/)
	{
		global $site;

		$site->pageStart(array('popfit' => 1));
		form_tag('plugin_multiedit_form', 'module.php', '', '', 'get');
		$module = isset($_REQUEST['module']) ? $_REQUEST['module'] : null;
		form_hidden('module', $module);

		$site->skin->submenuStart();
		echo "Sperren f&uuml;r <b>alle {$this->allIdsCount}</b> ausgew&auml;hlten {$this->tableDescr} ausf&uuml;hren";
		$site->skin->submenuBreak();
		echo "&nbsp;";
		$site->skin->submenuEnd();

		if ($msg) {
			$site->skin->msgStart($msg_type);
			echo $msg;
			$site->skin->msgEnd();
		}
		echo "<br><b>Achtung: Wenn Sie die Sicherheitsabfrage korrekt beantworten und den OK-Button bet&auml;tigen,</b><br>";
		echo  "<b>werden alle {$this->allIdsCount} <b>vorausgew&auml;hlten {$this->tableDescr} in allen Kursen und Dabenbanktabellen gel&ouml;scht.</b><br>";
		echo "<b>Eine Zurordnung dieser {$this->tableDescr} zu einem Kurs ist zuk&uuml;nftig solange nicht mehr m&ouml;glich, bis die<b></br>";
		echo "<b>  {$this->tableDescr} durch einen Redakteur wieder entsperrt werden.</b>";
		$site->skin->dialogStart();

		$site->skin->controlStart();
		$mult1 = rand(3, 9);
		$mult2 = rand(2, 9);
		echo "Sicherheitsabfrage:";
		$site->skin->controlBreak();
		echo "$mult1 &#215; $mult2 = ";
		form_hidden('correct_answer', $mult1 * $mult2);
		form_control_text__('user_answer', '', 2 /*width*/, 2 /*maxlen*/);
		$site->skin->controlEnd();
		$site->skin->dialogEnd();

		if (isset($this->allIdsCount) && $this->allIdsCount > 10) {
			$site->skin->submenuStart();
			echo "<b>Warnung:</b> Durch Klick auf OK &auml;ndern oder L&ouml;schen Sie {$this->allIdsCount} Datens&auml;tze!";
			$site->skin->submenuBreak();
			echo "&nbsp;";
			$site->skin->submenuEnd();
		}

		$site->skin->buttonsStart();
		if (!$ok)
		   form_button('ok', htmlconstant('_OK'));
		// else{
		// 	header("edit.php");
		// 	exit();
		// 	form_button('ok', htmlconstant('_OK'), 'window.close();return false;');
		// }
			
	    form_button('cancel', htmlconstant('_CANCEL'), 'window.close();return false;');
		$site->skin->buttonsEnd();

		echo '</form>';
		$site->pageEnd();
	}

	function renderDefaultPage($msg, $msg_type = 'e' /*error*/)
	{
		global $site;

		$site->pageStart(array('popfit' => 1));
		form_tag('plugin_multiedit_form', 'module.php', '', '', 'get');
		$module = isset($_REQUEST['module']) ? $_REQUEST['module'] : null;
		form_hidden('module', $module);

		$site->skin->submenuStart();
		echo "MultiEdit-Aktion f&uuml;r <b>alle {$this->allIdsCount}</b> ausgew&auml;hlten {$this->tableDescr} ausf&uuml;hren";
		$site->skin->submenuBreak();
		echo "&nbsp;";
		$site->skin->submenuEnd();

		if ($msg) {
			$site->skin->msgStart($msg_type);
			echo $msg;
			$site->skin->msgEnd();
		}

		$site->skin->dialogStart();
		$site->skin->controlStart();
		echo "Aktion:";
		$site->skin->controlBreak();

		$options = "nop######";

		// haeufige Aktionen (fpr RLP, 12:42 23.08.2013)
		if (isset($this->tableName) && $this->tableName == 'kurse') {
			$options .= "nop2###- - - H&auml;ufige Aktionen - - -###";
			$options .= "nop2######";
			$options .= "field__stichwort__addmattr###Stichw&ouml;rter:  aus 'Parameter2' hinzuf&uuml;gen###";
			$options .= "field__stichwort__delmattr###Stichw&ouml;rter:  aus 'Parameter2' L&Ouml;SCHEN###";
			$options .= "field__thema__setsattr###Thema: setze auf 'Parameter2'###";
			$options .= "field__anbieter__setsattr###Anbieter: setze auf 'Parameter2'###";
			$options .= "nop2######";

			$options .= "field__freigeschaltet__setenum###Status: setze auf 'Parameter2'###";
			$options .= "add_journal###Journaleintrag hinzuf&uuml;gen###";
			$options .= "dfield__beginn__setdate###Beginn: setze Beginn aller DF auf 'Parameter2'###";
			$options .= "del_old_durchf###Abgelaufene Durchf&uuml;hrungen L&Ouml;SCHEN###";

			$options .= "nop2######";
			$options .= "nop2###- - - Alle Aktionen - - -###";
			$options .= "nop2######";

			// $options .= "trigger_kurse###PLZ, Stadtteil etc. erg&auml;nzen###"; // <- just a headline / not fitting / useless?
		}

		// create possible actions list ...
		$options .= $this->getFieldActions($this->tableName, 'field');

		if (isset($this->tableName) && $this->tableName == 'kurse') {
			$options .= "nop2######";
			$options .= $this->getFieldActions('durchfuehrung', 'dfield');
			$options .= "del_old_durchf###Abgelaufene Durchf&uuml;hrungen L&Ouml;SCHEN###";
		}

		$options .= "field__user_grp__settext###Benutzergruppe: setze auf 'Parameter2' (nur ID) ###";
		$options .= "field__user_access__settext###Rechte: setze auf 'Parameter2'###";

		$options .= "nop2######";
		$options .= "add_journal###Journaleintrag hinzuf&uuml;gen###";
		$options .= "nop2######";
		$options .= "del_sel###{$this->allIdsCount} {$this->tableDescr} L&Ouml;SCHEN###";
		$options .= "nop2###";

		$sel = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'nop';
		if ($sel == 'nop2') $sel = 'nop';

		form_control_enum('action', $sel, $options);
		$site->skin->controlEnd();

		$site->skin->controlStart();
		echo "Parameter1 (optional):";
		$site->skin->controlBreak();
		$param1 = isset($_REQUEST['param1']) ? $_REQUEST['param1'] : null;
		form_control_text__('param1', $param1, 64 /*width*/, 1024 /*maxlen*/);
		$site->skin->controlEnd();

		$site->skin->controlStart();
		echo "Parameter2 (optional):";
		$site->skin->controlBreak();
		$param2 = isset($_REQUEST['param2']) ? $_REQUEST['param2'] : null;
		form_control_text__('param2', $param2, 64 /*width*/, 1024 /*maxlen*/);
		$site->skin->controlEnd();

		$site->skin->controlStart();
		echo "Journaleintrag (optional):";
		$site->skin->controlBreak();
		$journal_entry = isset($_REQUEST['journal_entry']) ? trim($_REQUEST['journal_entry']) : null;
		form_control_text__('journal_entry', $journal_entry, 64 /*width*/, 1024 /*maxlen*/);
		$site->skin->controlEnd();

		$site->skin->controlStart();
		$mult1 = rand(3, 9);
		$mult2 = rand(2, 9);
		echo "Sicherheitsabfrage:";
		$site->skin->controlBreak();
		echo "$mult1 &#215; $mult2 = ";
		form_hidden('correct_answer', $mult1 * $mult2);
		form_control_text__('user_answer', '', 2 /*width*/, 2 /*maxlen*/);
		$site->skin->controlEnd();
		$site->skin->dialogEnd();

		if (isset($this->allIdsCount) && $this->allIdsCount > 10) {
			$site->skin->submenuStart();
			echo "<b>Warnung:</b> Durch Klick auf OK &auml;ndern oder L&ouml;schen Sie {$this->allIdsCount} Datens&auml;tze!";
			$site->skin->submenuBreak();
			echo "&nbsp;";
			$site->skin->submenuEnd();
		}

		$site->skin->buttonsStart();
		form_button('ok', htmlconstant('_OK'));
		form_button('cancel', htmlconstant('_CANCEL'), 'window.close();return false;');
		$site->skin->buttonsEnd();

		echo '</form>';
		$site->pageEnd();
	}


	function renderStatusPage($msg, $popdown = 0)
	{
		global $site;

		$site->pageStart(array('popfit' => 1));

		if ($popdown) {
			echo "<script type=\"text/javascript\"><!--\n";
			echo "if( window.opener && !window.opener.closed )\n";
			echo "{\n";
			if (!$this->blacklist)
				echo "   window.opener.location.href = 'index.php?table={$this->tableName}';\n";
			else
				echo "   window.opener.location.href = '{$_SERVER['DOCUMENT_ROOT']}/admin/edit.php?table=kurse&id={$_SESSION['g_session_index_sql'][$this->tableName]}';\n";
			echo "}\n";
			echo "//--></script>\n";
		}
		$site->skin->submenuStart();
		echo "&nbsp;";
		$site->skin->submenuBreak();
		echo "&nbsp;";
		$site->skin->submenuEnd();

		$site->skin->workspaceStart();

		echo $msg;

		$site->skin->workspaceEnd();

		$site->skin->buttonsStart();
		form_button('ok', htmlconstant('_OK'), 'window.close();return false;');
		$site->skin->buttonsEnd();

		$site->pageEnd();
	}


	function getFieldEnum($rowdef, $value)
	{
		global $site;
		$all = explode('###', $rowdef->addparam);
		$allowed = '';
		for ($i = 0; $i < sizeof((array) $all); $i += 2) {
			if (strtolower(trim($value)) == strtolower(trim($site->htmldeentities($all[$i + 1]))))
				return $all[$i];
			$allowed .= ($allowed ? ', ' : '') . $site->htmldeentities($all[$i + 1]);
		}
		$this->renderDefaultPage('Unbekannter Wert <i>' . isohtmlspecialchars($value) . '</i> in Parameter2. Erlaubte Werte sind <i>' . isohtmlspecialchars($allowed) . '</i>');
		exit();
	}


	private function is_integer2($v)
	{
		$i = intval($v);
		if ("$i" == "$v") {
			return TRUE;
		} else {
			return FALSE;
		}
	}


	private function getFieldAttrs($rowdef, $value)
	{
		// function always returns an array of attribute IDs, halts on errors
		$db = new DB_Admin;

		$linked_table = $rowdef->addparam->name;
		$linked_table_def = Table_Find_Def($linked_table);
		// if value is an ID, search for it

		$sql = "SELECT id FROM $linked_table WHERE id=" . ($this->is_integer2($value) ? intval($value) : 0);
		$sql_descr = "{$linked_table_def->descr}.ID";

		// additionally, search for the value in the first summary column
		for ($r = 0; $r < sizeof((array) $linked_table_def->rows); $r++) {
			$rowsFlags = isset($linked_table_def->rows[$r]->flags) ? $linked_table_def->rows[$r]->flags : null;
			$rowsName = isset($linked_table_def->rows[$r]->name) ? $linked_table_def->rows[$r]->name : null;
			$tableDefDescr = isset($linked_table_def->descr) ? $linked_table_def->descr : null;
			$rowsDescr = isset($linked_table_def->rows[$r]->descr) ? $linked_table_def->rows[$r]->descr : null;
			if ($rowsFlags & TABLE_SUMMARY) {
				$sql .= " OR " . $rowsName . " LIKE '" . addslashes($value) . "'";
				$sql_descr .= " oder {$tableDefDescr}." . $rowsDescr;
				break;
			}
		}

		// finally, for table "themen", allow the input of the kurzel as "1.1" (at least one dot is required in the value to distinguish from the ID)
		if ($linked_table == 'themen' && strpos($value, '.') !== false) {
			$sql .= " OR kuerzel='" . addslashes($value) . "' OR kuerzel='" . addslashes($value) .  ".'";
			$sql_descr .= " oder {$linked_table_def->descr}.K&uuml;rzel";
		}

		// try by get_id_from_txt(), allow multiple values ... (changes for RLP, 19.08.2013)
		$seperator = regGet("edit.seperator.{$this->tableName}", ';');
		if ($seperator == '') $seperator = ';';
		$sep_arr = explode($seperator, $value);
		$ret = array();
		$error = '';
		for ($a = 0; $a < sizeof((array) $sep_arr); $a++) {
			$curr_attr = trim($sep_arr[$a]);
			if ($curr_attr !== '') {
				$curr_id = $linked_table_def->get_id_from_txt($curr_attr, $attr_error);
				if ($curr_id) {
					$ret[] = $curr_id;
				} else {
					$error = "Unbekannter Wert <i>" . isohtmlspecialchars($curr_attr) . "</i> in Parameter2. Bitte geben Sie hier einen g&uuml;ltigen Wert aus <i>{$sql_descr}</i> ein.";
				}
			}
		}

		if (sizeof($ret)) {
			if ($error == '')
				return $ret;
			else {
				$this->renderDefaultPage($error);
				exit();
			}
		}

		// search!
		$db->query($sql);
		if ($db->next_record()) {
			$ret = $db->f('id');
			if ($db->next_record()) {
				$this->renderDefaultPage("Nicht eindeutiger Wert <i>" . isohtmlspecialchars($value) . "</i> in Parameter2. Bitte geben Sie hier einen g&uuml;ltigen Wert aus <i>{$sql_descr}</i> ein.");
				exit();
			}
			return array($ret);
		} else {
			$this->renderDefaultPage("Unbekannter Wert <i>" . isohtmlspecialchars($value) . "</i> in Parameter2. Bitte geben Sie hier einen g&uuml;ltigen Wert aus <i>{$sql_descr}</i> ein.");
			exit();
		}
	}


	private function do_field_action($localTableName, $allIdsStr, $action, $param1, $param2)
	{
		$db = new DB_Admin;
		$db2 = new DB_Admin;
		$temp = explode('__', $action);
		$field  = isset($temp[1]) ? $temp[1] : null;
		$action = isset($temp[2]) ? $temp[2] : null;

		$table_def = Table_Find_Def($localTableName);

		$rowdescr = '';

		if ($field == 'user_grp') {
			$rowdescr = 'Benutzergruppe';
		}
		if ($field == 'user_access') {
			$rowdescr = 'Rechte';
		} else for ($r = 0; $r < sizeof((array) $table_def->rows); $r++) {
			$rowsName = isset($table_def->rows[$r]->name) ? $table_def->rows[$r]->name : null;
			if ($rowsName == $field) {
				$rowdescr = isset($table_def->rows[$r]->descr) ? trim($table_def->rows[$r]->descr) : null;
				break;
			}
		}

		if ($rowdescr == '') {
			die('bad row.');
		}

		switch ($action) {
			case 'setenum':
			case 'setsattr':
			case 'settext':
			case 'settext2':
			case 'settext3':
			case 'setdate':
			case 'setdate2':
			case 'setdate3':
				// suchen / ersetzen im Datensatz
				$param2_org = $param2;
				if ($action == 'setenum') {
					if ($param1 != '') {
						$this->renderDefaultPage('Parameter1 wird bei dieser Aktion nicht verwendet, bitte &uuml;berpr&uuml;fen Sie Ihre Eingaben.');
						exit();
					}
					$param2 = $this->getFieldEnum($table_def->rows[$r], $param2);
				} else if ($action == 'setsattr') {
					if ($param1 != '') {
						$this->renderDefaultPage('Parameter1 wird bei dieser Aktion nicht verwendet, bitte &uuml;berpr&uuml;fen Sie Ihre Eingaben.');
						exit();
					}
					$temp = $this->getFieldAttrs($table_def->rows[$r], $param2);
					$param2 = $temp[0];
				} else if ($action == 'settext' || $action == 'setdate') {
					if ($param1 != '') {
						$this->renderDefaultPage('Parameter1 wird bei dieser Aktion nicht verwendet, bitte &uuml;berpr&uuml;fen Sie Ihre Eingaben.');
						exit();
					}
					if ($param2 == '') {
						$this->renderDefaultPage('Bitte geben Sie den zu setzenden Wert in Parameter2 an.');
						exit();
					}
					if ($action == 'setdate') {
						$param2 = sql_date_from_human($param2, 'date');
						if ($param2 == '0000-00-00 00:00:00') {
							$this->renderDefaultPage('Ung&uuml;ltige Datumsangabe.');
							exit();
						}
					}
				} else if ($action == 'settext2' || $action == 'setdate2') {
					if ($param1 == '') {
						$this->renderDefaultPage('Bitte geben Sie die zu suchende Zeichenkette in Parameter1 an.');
						exit();
					}
					if ($action == 'setdate2') {
						$param1 = sql_date_from_human($param1, 'date');
						if ($param1 == '0000-00-00 00:00:00') {
							$this->renderDefaultPage('Ung&uuml;ltige Datumsangabe.');
							exit();
						}
					}
					if ($action == 'setdate2') {
						$param2 = sql_date_from_human($param2, 'date');
						if ($param2 == '0000-00-00 00:00:00') {
							$this->renderDefaultPage('Ung&uuml;ltige Datumsangabe.');
							exit();
						}
					}
				} else if ($action == 'settext3' || $action == 'setdate3') {
					if ($param1 != '' || $param2 != '') {
						$this->renderDefaultPage('Parameter1 und Parameter2 werden bei dieser Aktion nicht verwendet, bitte &uuml;berpr&uuml;fen Sie Ihre Eingaben.');
						exit();
					}
					if ($action == 'setdate3') {
						$param2 = '0000-00-00 00:00:00';
					}
				}

				$all_changes = 0;
				$sql = "SELECT id, $field FROM {$localTableName} WHERE id IN($allIdsStr) AND $field LIKE '%" . addslashes($param1) . "%';";
				$db->query($sql);
				while ($db->next_record()) {
					$id = intval($db->f('id'));
					$content = $db->fs($field);
					$changes = 0;
					if ($param1 == '') {
						if ($content != $param2)
							$changes++;
						$content = $param2;
					} else {
						$content = str_replace($param1, $param2, $content, $changes);
					}
					if ($changes > 0) {
						$db2->query("UPDATE {$localTableName} SET $field='" . addslashes($content) . "' WHERE id=$id;");
						$all_changes += $changes;
					}
				}

				if ($all_changes == 0) {
					$this->renderDefaultPage('Keine &Auml;nderungen notwendig.', 'i');
					exit(); // no log
				}

				if ($localTableName == 'durchfuehrung') {
					$sql = "SELECT primary_id FROM kurse_durchfuehrung WHERE secondary_id = $id";   // get course id
					$db->query($sql);
					if ($db->next_record()) {
						$trigger_param = array('action' => 'afterupdate', 'id' => $id, 'primary_id' => $db->f('primary_id'), 'origin' => 'Multiedit');
						call_plugin($table_def->trigger_script, $trigger_param);  // DF trigger
					}
				}

				if ($param1 == '')
					return "Feld $rowdescr wurde auf '" . isohtmlspecialchars($param2_org) . "' gesetzt; dabei wurden $all_changes Aenderungen vorgenommen. ";
				else
					return "'" . isohtmlspecialchars($param1) . "' durch '" . isohtmlspecialchars($param2_org) . "' im Feld $rowdescr ersetzt; dabei wurden $all_changes Aenderungen vorgenommen. ";

			case 'addmattr':
				if ($param1 != '') {
					$this->renderDefaultPage('Parameter1 wird bei dieser Aktion nicht verwendet, bitte &uuml;berpr&uuml;fen Sie Ihre Eingaben.');
					exit();
				}
				$param2_org = $param2;
				$param2 = $this->getFieldAttrs($table_def->rows[$r], $param2);
				$allIdsArr = explode(',', $allIdsStr);
				$all_changes = 0;
				for ($a = 0; $a < sizeof($allIdsArr); $a++) {
					for ($param2_i = 0; $param2_i < sizeof((array) $param2); $param2_i++) {
						$db->query("SELECT attr_id FROM {$localTableName}_{$table_def->rows[$r]->name} WHERE primary_id=$allIdsArr[$a] AND attr_id=" . $param2[$param2_i]);
						if (!$db->next_record()) {
							$all_changes++;
							$db->query("INSERT INTO {$localTableName}_{$table_def->rows[$r]->name} (primary_id, attr_id) VALUES ($allIdsArr[$a], " . $param2[$param2_i] . ");");
						}
					}
				}
				if ($all_changes == 0) {
					$this->renderDefaultPage('Keine &Auml;nderungen notwendig.', 'i');
					exit(); // no log
				}
				return "'" . isohtmlspecialchars($param2_org) . "' zu Feld $rowdescr hinzugef&uuml;gt; dabei wurden $all_changes &Auml;nderungen vorgenommen. ";

			case 'delmattr':
				if ($param1 != '') {
					$this->renderDefaultPage('Parameter1 wird bei dieser Aktion nicht verwendet, bitte &uuml;berpr&uuml;fen Sie Ihre Eingaben.');
					exit();
				}
				$param2_org = $param2;
				$temp = $this->getFieldAttrs($table_def->rows[$r], $param2);
				$param2 = implode(',', $temp);

				$db->query("SELECT attr_id FROM {$localTableName}_{$table_def->rows[$r]->name} WHERE attr_id IN ($param2) AND primary_id IN ($allIdsStr)");
				if (!$db->next_record()) {
					$this->renderDefaultPage('Keine &Auml;nderungen notwendig.', 'i');
					exit(); // no log
				}

				$db->query("DELETE FROM {$localTableName}_{$table_def->rows[$r]->name} WHERE attr_id IN ($param2) AND primary_id IN ($allIdsStr)");
				return "'" . isohtmlspecialchars($param2_org) . "' aus Feld $rowdescr entfernt. ";

			default:
				die('unknown action.');
				break;
		}
	}


	function main($tableName)
	{
		$this->tableName = $tableName;
		$table_def = Table_Find_Def($this->tableName);
		$this->tableDescr = $table_def->descr;
		$this->blacklist = ($this->tableName == 'kompetenz_blacklist');
		if (!acl_check_access("SYSTEM.MULTIEDIT", -1, ACL_EDIT)) {
			if (!$this->blacklist) {
				$this->renderStatusPage('Mit der Funktion &quot;MultiEdit&quot; k&ouml;nnen Aktionen f&uuml;r mehrere Datens&auml;tze '
					. 'gleichzeitig ausgef&uuml;hrt werden. Um diese Funktion zu '
					. 'verwenden mu&szlig; der Systemadministrator Ihnen zuvor das Recht &quot;SYSTEM.MULTIEDIT&quot; einr&auml;umen.');
			} else {
				$this->renderStatusPage('Mit der Funktion &quot;Vorausgew&auml;hlte sperren&quot; k&ouml;nnen Aktionen f&uuml;r mehrere Datens&auml;tze '
					. 'gleichzeitig ausgef&uuml;hrt werden. Um diese Funktion zu '
					. 'verwenden mu&szlig; der Systemadministrator Ihnen zuvor das Recht &quot;SYSTEM.MULTIEDIT&quot; einr&auml;umen.');
			}
			exit();
		}

		$user_answer = isset($_REQUEST['user_answer']) ? $_REQUEST['user_answer'] : null;
		$correct_answer = isset($_REQUEST['correct_answer']) ? $_REQUEST['correct_answer'] : null;

		if (
			isset($_SERVER['HTTP_HOST']) && substr($_SERVER['HTTP_HOST'], -6) != '.local'
			&& intval($user_answer) != intval($correct_answer)
		) {
			if (!$this->blacklist)
				$this->renderDefaultPage('Sie haben die Sicherheitsabfrage falsch beantwortet.');
			else
				$this->renderBlacklistPage('Sie haben die Sicherheitsabfrage falsch beantwortet.');
			exit();
		}

		// ignore all aborts
		ignore_user_abort(1);
		set_time_limit(0);

		// IDs der Datensaetze laden
		if (!isset($_SESSION['g_session_index_sql'][$this->tableName])) {
			$this->renderStatusPage("Keine Datens&auml;tze ausgew&auml;hlt?");
			exit();
		}

		$eql2sql = new EQL2SQL_CLASS($this->tableName);
		$eql = !isset($_SESSION['g_session_index_eql'][$this->tableName]) || $_SESSION['g_session_index_eql'][$this->tableName] == '' ? '*' : $_SESSION['g_session_index_eql'][$this->tableName];

		if (!$this->blacklist) {
			$eql2sql = new EQL2SQL_CLASS($this->tableName);
			$eql = !isset($_SESSION['g_session_index_eql'][$this->tableName]) || $_SESSION['g_session_index_eql'][$this->tableName] == '' ? '*' : $_SESSION['g_session_index_eql'][$this->tableName];
			$sql = $eql2sql->eql2sql($eql, 'id', acl_get_sql(ACL_READ, 0, 1, $this->tableName), 'id');
		} else {
			//Find preselected Items in kurse_kompetenz
			$sql = 'SELECT kurse_kompetenz.attr_id, kurse_kompetenz.attr_url, stichwoerter.stichwort FROM kurse_kompetenz LEFT JOIN stichwoerter ON kurse_kompetenz.attr_id=stichwoerter.id  WHERE kurse_kompetenz.primary_id=' . $_SESSION['g_session_index_sql'][$this->tableName]
				. ' AND kurse_kompetenz.preselected=1'
				. ' ORDER BY kurse_kompetenz.attr_id';
		}
		$allIdsStr = '';
		$blacklistinput = array();
		$this->allIdsCount = 0;
		$db = new DB_Admin;
		$db->query($sql);
		while ($db->next_record()) {
			if ($allIdsStr != '') $allIdsStr .= ',';
			$allIdsStr .= (!$this->blacklist) ? $db->f('id') : $db->f('attr_id');
			if (isset($_REQUEST['ok']) && $_REQUEST['ok'] == 'OK')
				$blacklistinput[] = array('id' => $db->f('attr_id'), 'uri' => $db->f('attr_url'), 'title' => $db->f('stichwort'));
			$this->allIdsCount++;
		}

		if ($this->allIdsCount <= 0) {
			$this->renderStatusPage('Bitte w&auml;hlen Sie zun&auml;chst die zu bearbeitenden Datens&auml;tze im Hauptfenster aus.');
			exit();
		}

		if (!isset($_REQUEST['ok']) || $_REQUEST['ok'] != 'OK') {
			if (!$this->blacklist)
				$this->renderDefaultPage('');
			else
				$this->renderBlacklistPage('');
			exit();
		}
		$journal_entry	= isset($_REQUEST['journal_entry']) ? trim($_REQUEST['journal_entry']) : null;
		$add_msg		= '';
		// befehl auslesen
		if (!$this->blacklist) {
			$action			= isset($_REQUEST['action']) ? trim($_REQUEST['action']) : null;
			$param1			= isset($_REQUEST['param1']) ? $_REQUEST['param1'] : null; // Leerzeichen sind relevant!
			$param2			= isset($_REQUEST['param2']) ? $_REQUEST['param2'] : null; // Leerzeichen sind relevant!



			// bei Bedarf die IDs der sekundaeren Tabelle (Durchfuehrungen) laden
			if (substr($action, 0, 8) == 'dfield__' ||  $action == 'del_old_durchf') {
				$allDurchfIdsStr = '';
				$db->query("SELECT secondary_id FROM {$this->tableName}_durchfuehrung WHERE primary_id IN($allIdsStr);");
				while ($db->next_record()) {
					if ($allDurchfIdsStr != '') $allDurchfIdsStr .= ',';
					$allDurchfIdsStr .= $db->f('secondary_id');
				}
				if ($allDurchfIdsStr == '')
					$this->renderDefaultPage('Keine Durchf&uuml;hrungen in der Auswahl.', 'i');
			}

			// see what to do ...
			if (substr($action, 0, 8) == 'dfield__') {
				$add_msg .= $this->do_field_action('durchfuehrung', $allDurchfIdsStr, $action, $param1, $param2);
			} else if (substr($action, 0, 7) == 'field__') {
				$add_msg .= $this->do_field_action($this->tableName, $allIdsStr, $action, $param1, $param2);
			} else if ($action == 'del_sel') {
				// ... delete a number of records
				$allIdsArr = explode(',', $allIdsStr);
				for ($a = 0; $a < sizeof($allIdsArr); $a++) {
					$id = $allIdsArr[$a];

					// access?
					if (!acl_check_access("{$table_def->name}.COMMON", $id, ACL_DELETE)) {
						$this->renderStatusPage("Sie haben nicht die Berechtigung, Datensatz #$id zu l&ouml;schen.", 1 /*1=update main window*/);
						exit();
					}
				}

				for ($a = 0; $a < sizeof($allIdsArr); $a++) {
					$id = $allIdsArr[$a];

					// delete in DB
					$table_def->destroy_record_dependencies($id);
					$db->query("DELETE FROM {$table_def->name} WHERE id=$id");

					// trigger?
					if (isset($table_def->trigger_script) && $table_def->trigger_script) {
						$trigger_param = array('action' => 'afterdelete', 'id' => $id);
						call_plugin($table_def->trigger_script, $trigger_param);
					}
				}
				$add_msg .= "Insgesamt " . sizeof($allIdsArr) . " Datens&auml;tze gel&ouml;scht. ";
			} else if ($action == 'del_old_durchf') {
				// alte durchfuehrungen loeschen
				$today = ftime("%Y-%m-%d 00:00:00");
				$toMod = '';
				$toModCnt = 0;
				$dfIDs = array();
				$db->query("SELECT id FROM durchfuehrung WHERE id IN($allDurchfIdsStr) AND beginnoptionen=0 AND beginn!='0000-00-00' AND beginn<'$today';");
				while ($db->next_record()) {
					if ($toMod != '') $toMod .= ',';
					$toMod .= $db->f('id');
					$dfIDs[] = $db->f('id');
					$toModCnt++;
				}

				if ($toModCnt == 0) {
					$this->renderDefaultPage("Keine abgelaufenen Durchf&uuml;hrungen in der Auswahl.", 'i');
					exit();
				}

				foreach ($dfIDs as $dfID) {
					$sql = "SELECT primary_id FROM kurse_durchfuehrung WHERE secondary_id = $dfID";   // get course id
					$db->query($sql);

					if ($db->next_record()) {
						$trigger_param = array('action' => 'afterdelete', 'id' => 8, 'primary_id' => $db->f('primary_id'), 'origin' => 'Multiedit');
						$table_defSec = Table_Find_Def('durchfuehrung');
						call_plugin($table_defSec->trigger_script, $trigger_param);  // DF trigger
					}
				}

				$db->query("DELETE FROM durchfuehrung WHERE id IN($toMod);");
				$db->query("DELETE FROM {$this->tableName}_durchfuehrung WHERE secondary_id IN($toMod);");
				$add_msg .= "Insgesamt $toModCnt abgelaufene Durchf&uuml;hrungen gel&ouml;scht. ";
			} else if ($action == 'trigger_kurse') {
				require_once('config/trigger_kurse.inc.php');
				$allIdsArr = explode(',', $allIdsStr);
				foreach ($allIdsArr as $currId) {
					$uks = update_kurs_state($currId, array('set_plz_stadtteil' => 1, 'write' => 1));
					if (isset($uks['returnmsg']) && $uks['returnmsg']) {
						$add_msg .= "<a href=\"edit.php?table=kurse&id={$currId}\" target=\"_blank\">Kurs ID {$currId}</a>: {$uks['returnmsg']}<br />";
					}
				}
				$add_msg .= sizeof($allIdsArr) . " Kurse &uuml;berpr&uuml;ft. ";
			} else if ($action == 'add_journal') {
				// nur journaleintrag hinzufuegen
				if ($param1 != '' || $param2 != '') {
					$this->renderDefaultPage('Parameter1 und Parameter2 werden bei dieser Aktion nicht verwendet, bitte &uuml;berpr&uuml;fen Sie Ihre Eingaben.');
					exit();
				}
				if ($journal_entry == '') {
					$this->renderDefaultPage('Bitte geben Sie den hinzuzuf&uuml;genden Journaleintrag ein.');
					exit();
				}
			} else {
				$this->renderDefaultPage('Bitte w&auml;hlen Sie eine Aktion aus.');
				exit();
			}
		} else {

			for ($r = 0; $r < sizeof((array) $blacklistinput); $r++) {
				//Complete esco_url's if possible
				if ($blacklistinput[$r]['uri'] == null) {
					$sql = 'Select stichwoerter.esco_url FROM stichwoerter WHERE id = ' . $blacklistinput[$r]['id'];
					$db->query($sql);
					if ($db->next_record()) {
						if ($db->f('esco_url') != null && $db->f('esco_url') != "")
							$blacklistinput[$r]['uri'] = $db->f('esco_url');
					}
				}
				$db->query("DELETE FROM  kompetenz_blacklist WHERE url='{$blacklistinput[$r]['uri']}'");
				$db->query("INSERT INTO kompetenz_blacklist(url, title) VALUES('{$blacklistinput[$r]['uri']}', '{$blacklistinput[$r]['title']}')");
				//Delete keyword in all tables
				$db->query("DELETE FROM kurse_stichwort WHERE attr_id=" . $blacklistinput[$r]['id']);
				$test = $db->affected_rows();
				$db->query("DELETE FROM kurse_kompetenz WHERE attr_id=" . $blacklistinput[$r]['id']);
				$test = $db->affected_rows();
				$db->query("DELETE FROM stichwoerter WHERE id=" . $blacklistinput[$r]['id']);
				$test = $db->affected_rows();
			}
			$this->renderBlacklistPage('Ihre Anfrage wird bearbeitet.', 'i', true);

		}
		// write journal
		$journal_sql = '';
		if ($journal_entry != '') {
			$journal_sql = ", notizen=CONCAT('" . addslashes($journal_entry) . "\n',notizen)";
			$add_msg .= 'Journaleintrag: ' . $journal_entry . ' ';
		}

		// $sql = "UPDATE {$this->tableName}
		// 		   SET date_modified='" . ftime("%Y-%m-%d %H:%M:%S") . "'
		// 			 , user_modified=" . (isset($_SESSION['g_session_userid']) ? intval($_SESSION['g_session_userid']) : null) . "
		// 			   $journal_sql
		// 		   WHERE id IN ($allIdsStr);
		// 	   ";
		// $db->query($sql);

		$add_msg . " Die Aktion wurde f&uuml;r {$this->allIdsCount} $this->tableDescr durchgef&uuml;hrt.";

		$logwriter = new LOG_WRITER_CLASS;
		$logwriter->addData('query', $eql);
		$logwriter->addData('action', $add_msg);
		$logwriter->log($this->tableName, $allIdsStr, (isset($_SESSION['g_session_userid']) ? $_SESSION['g_session_userid'] : null), 'multiedit');
		if (!$this->blacklist) {
			$this->renderStatusPage($add_msg, 1 /*1=update main window*/);
			exit();
	
		} else {
			// echo "<script type='text/javascript' src='" . $_SERVER['DOCUMENT_ROOT'] . "/admin/WisyKi/functions.js?iv=" . CMS_VERSION . "'>escopopupclose(" . $_SESSION['g_session_index_sql'][$this->tableName] . ", 'kurse');return false;</script>";
			echo "<script type=\"text/javascript\"><!--\n";
			echo "if( window.opener && !window.opener.closed )\n";
			echo "{\n";
			echo "   window.opener.location.href = '{$_SERVER['DOCUMENT_ROOT']}/admin/edit.php?table=kurse&id={$_SESSION['g_session_index_sql'][$this->tableName]}';\n";
			echo "window.close();\n";
			
			echo "window.opener.location.reload();";
			echo "}\n";
			echo "</script>\n";
            exit();
		}
	}
};
