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


require_once('functions.inc.php');
require_once('eql.inc.php');


function form_control_text__($name, $value, $width, $maxlen) // the parameter order has changed with admin-5, if admin-5 gets the default, we can get rid of this crappy function
{
    if( defined(CMS_VERSION) && intval(CMS_VERSION) >= 5 )
	{
		form_control_text($name, $value, $width, $maxlen);
	}
	else
	{	
		form_control_text($name, $value, $width, -1, $maxlen);
	}
}


class MULTIEDIT_PLUGIN_CLASS
{
	var $tableName;
	var $allIdsCount;
	
	
	function getFieldActions($table, $prefix)
	{
		global $site;
		$ret = '';
		$table_def = Table_Find_Def($table);
		for( $r = 0; $r < sizeof((array) $table_def->rows); $r++ )
		{
			$rowflags = $table_def->rows[$r]->flags;
			$rowname = $table_def->rows[$r]->name;
			$rowdescr = $site->htmldeentities(trim($table_def->rows[$r]->descr));
			/* if( strlen($rowdescr) > 16) $rowdescr = substr($rowdescr, 0, 14).'..'; */
			if( !($rowflags & TABLE_READONLY) )
			{
				switch( $rowflags & TABLE_ROW )
				{
					case TABLE_TEXT:
					case TABLE_TEXTAREA:
					    $cmdPrefix = isset($cmdPrefix) ? $cmdPrefix : ''; // is never being defined / set => what's the point??
					    
						if( $rowname != 'notizen' )
						{
							$ret .= $cmdPrefix."{$prefix}__{$rowname}__settext###$rowdescr: setze auf 'Parameter2'###";
							$ret .= $cmdPrefix."{$prefix}__{$rowname}__settext2###$rowdescr: 'Parameter1' durch 'Parameter2' ersetzen###";
							$ret .= $cmdPrefix."{$prefix}__{$rowname}__settext3###$rowdescr: L&Ouml;SCHEN###";
						}
						break;

					case TABLE_DATE:
					    $cmdPrefix = isset($cmdPrefix) ? $cmdPrefix : ''; // is never being defined / set => what's the point??
					    
						$ret .= $cmdPrefix."{$prefix}__{$rowname}__setdate###$rowdescr: setze auf 'Parameter2'###"; 
						$ret .= $cmdPrefix."{$prefix}__{$rowname}__setdate2###$rowdescr: 'Parameter1' durch 'Parameter2' ersetzen###";
						$ret .= $cmdPrefix."{$prefix}__{$rowname}__setdate3###$rowdescr: L&Ouml;SCHEN###";
						break;
						
					case TABLE_INT:
					    $cmdPrefix = isset($cmdPrefix) ? $cmdPrefix : ''; // is never being defined / set => what's the point??
					    
						$ret .= $cmdPrefix."{$prefix}__{$rowname}__settext###$rowdescr: setze auf 'Parameter2'###";
						break;
						
					case TABLE_ENUM:
					    $cmdPrefix = isset($cmdPrefix) ? $cmdPrefix : ''; // is never being defined / set => what's the point??
					    
						$ret .= $cmdPrefix."{$prefix}__{$rowname}__setenum###$rowdescr: setze auf 'Parameter2'###";
						break;
					
					case TABLE_SATTR:
					    $cmdPrefix = isset($cmdPrefix) ? $cmdPrefix : ''; // is never being defined / set => what's the point??
					    
						$ret .= $cmdPrefix."{$prefix}__{$rowname}__setsattr###$rowdescr: setze auf 'Parameter2'###";
						break;

					case TABLE_MATTR:
					    $cmdPrefix = isset($cmdPrefix) ? $cmdPrefix : ''; // is never being defined / set => what's the point??
					    
						$valueName = 'Wert';
						if( $rowname == 'stichwort' ) $valueName = '';
						if( $rowname == 'verweis' && $table == 'kurse' ) $valueName = 'Kurs';
						if( $rowname == 'verweis' && $table == 'anbieter' ) $valueName = 'Anbieter';
						$ret .= $cmdPrefix."{$prefix}__{$rowname}__addmattr###$rowdescr: $valueName aus 'Parameter2' hinzuf&uuml;gen###";
						$ret .= $cmdPrefix."{$prefix}__{$rowname}__delmattr###$rowdescr: $valueName aus 'Parameter2' L&Ouml;SCHEN###";
						break;
				}
			}
		}
		
		return $ret;
	}
	
	
	function renderDefaultPage($msg, $msg_type = 'e' /*error*/)
	{
		global $site;
		
		$site->pageStart(array('popfit'=>1));
		form_tag('plugin_multiedit_form', 'module.php', '', '', 'get');
		$module = isset( $_REQUEST['module'] ) ? $_REQUEST['module'] : null;
		form_hidden('module', $module );
		
			$site->skin->submenuStart();
				echo "MultiEdit-Aktion f&uuml;r <b>alle {$this->allIdsCount}</b> ausgew&auml;hlten {$this->tableDescr} ausf&uuml;hren";
			$site->skin->submenuBreak();
				echo "&nbsp;";
			$site->skin->submenuEnd();
			
			if( $msg )
			{
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
					if( isset($this->tableName) && $this->tableName == 'kurse' )
					{
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
					
					if( isset($this->tableName) && $this->tableName == 'kurse' )
					{
						$options .= "nop2######";
						$options .= $this->getFieldActions('durchfuehrung', 'dfield');
						$options .= "del_old_durchf###Abgelaufene Durchf&uuml;hrungen L&Ouml;SCHEN###";
					}
					
					$options .= "field__user_grp__settext###Benutzergruppe: setze auf 'Parameter2' (nur ID) ###";
					$options .= "field__user_access__settext###Rechte: setze auf 'Parameter2'###";
					
					$options .= "nop2######";
					$options .= "duplikat_kurse###Kurse duplizieren + Anbieter aus 'Parameter2' verwenden###";
					$options .= "nop2######";
					$options .= "add_journal###Journaleintrag hinzuf&uuml;gen###";
					$options .= "nop2######";
					$options .= "del_sel###{$this->allIdsCount} {$this->tableDescr} L&Ouml;SCHEN###";
					$options .= "nop2###";
					
					$sel = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : 'nop';
					if( $sel == 'nop2' ) $sel = 'nop';
					
					// Text clues for Multiedit options
					// Array key = name of multiedit option (see above)
					$clue['duplikat_kurse'] = "<br>Ein manueller Journaleintrag wird an das neue UND an das Original-Angebot vergeben.<br>";
					$clue['duplikat_kurse'] .= "Das neue, duplizierte Angebote erh&auml;lt aber auch einen automatischen Journal-Eintrag.<br>";
					$clue['duplikat_kurse'] .= "Erstelldatum und &Auml;nderungsdatum wird bei den neuen, duplizierten Angeboten auf jetzt gesetzt.<br>";
					$clue['duplikat_kurse'] .= "Am Ende des Vorgangs muss die Anzahl der duplizierten Angebote erscheinen.<br>";
					$clue['duplikat_kurse'] .= "Vorsicht beim Arbeiten zwei Reitern: Multiedit bezieht sich immer auf die letzte Suchanfrage (egal welcher Reiter gerade geklickt ist).<br><br>";
					
					$onchangeJS = '';
					
					foreach( $clue AS $key => $text ) {
					    $onchangeJS .= " if( $(this).val() == '".$key."' ) { ";
					    $onchangeJS .= "$('table.sm:last-child').before('<div class=hinweis style=\\'padding: 5px; background-color: #ededed\\'>Hinweis:";
					    $onchangeJS .= $text;
					    $onchangeJS .= "</div>'); } else { if( $('.hinweis').length ) { $('.hinweis').remove(); } } ";
					}
					
					form_control_enum('action', $sel, $options, 0, '', $onchangeJS);
					
				$site->skin->controlEnd();
				
				$site->skin->controlStart();
					echo "Parameter1 (optional):";
				$site->skin->controlBreak();
				$param1 = isset( $_REQUEST['param1'] ) ? $_REQUEST['param1'] : null;
					form_control_text__('param1', $param1, 64 /*width*/, 1024 /*maxlen*/);
				$site->skin->controlEnd();

				$site->skin->controlStart();
					echo "Parameter2 (optional):";
				$site->skin->controlBreak();
				$param2 = isset( $_REQUEST['param2'] ) ? $_REQUEST['param2'] : null;
					form_control_text__('param2', $param2, 64 /*width*/, 1024 /*maxlen*/);
				$site->skin->controlEnd();

				$site->skin->controlStart();
					echo "Journaleintrag (optional):";
				$site->skin->controlBreak();
				$journal_entry = isset( $_REQUEST['journal_entry'] ) ? trim($_REQUEST['journal_entry']) : null;
					form_control_text__('journal_entry', $journal_entry, 64 /*width*/, 1024 /*maxlen*/);
				$site->skin->controlEnd();
				
				$site->skin->controlStart();
					$mult1 = rand(3, 9);
					$mult2 = rand(2, 9);
					echo "Sicherheitsabfrage:";
				$site->skin->controlBreak();
					echo "$mult1 &#215; $mult2 = ";
					form_hidden('correct_answer', $mult1*$mult2);
					form_control_text__('user_answer', '', 2 /*width*/, 2 /*maxlen*/);
				$site->skin->controlEnd();
			$site->skin->dialogEnd();

			if( isset( $this->allIdsCount ) && $this->allIdsCount > 10 )
			{
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
		
		$site->pageStart(array('popfit'=>1));
		
			if( $popdown )
			{
				echo "<script type=\"text/javascript\"><!--\n";
					echo "if( window.opener && !window.opener.closed )\n";
					echo "{\n";
					echo "   window.opener.location.href = 'index.php?table={$this->tableName}';\n";
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
		for( $i = 0; $i < sizeof((array) $all); $i+=2 )
		{
			if( strtolower(trim($value)) == strtolower(trim($site->htmldeentities($all[$i+1]))) )
				return $all[$i];
			$allowed .= ($allowed? ', ' : '') . $site->htmldeentities($all[$i+1]);
		}
		$this->renderDefaultPage('Unbekannter Wert <i>'.isohtmlspecialchars($value).'</i> in Parameter2. Erlaubte Werte sind <i>'.isohtmlspecialchars($allowed).'</i>');
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
		
		$sql = "SELECT id FROM $linked_table WHERE id=" . ($this->is_integer2($value)? intval($value) : 0);
		$sql_descr = "{$linked_table_def->descr}.ID";

		// additionally, search for the value in the first summary column
		for( $r = 0; $r < sizeof((array) $linked_table_def->rows); $r++ )
		{
		    $rowsFlags = isset( $linked_table_def->rows[$r]->flags ) ? $linked_table_def->rows[$r]->flags : null;
		    $rowsName = isset( $linked_table_def->rows[$r]->name ) ? $linked_table_def->rows[$r]->name : null;
		    $tableDefDescr = isset( $linked_table_def->descr ) ? $linked_table_def->descr : null;
		    $rowsDescr = isset( $linked_table_def->rows[$r]->descr ) ? $linked_table_def->rows[$r]->descr : null;
			if( $rowsFlags & TABLE_SUMMARY )
			{
				$sql .= " OR " . $rowsName . " LIKE '" . addslashes($value) . "'";
				$sql_descr .= " oder {$tableDefDescr}.".$rowsDescr;
				break;
			}
		}
		
		// finally, for table "themen", allow the input of the kurzel as "1.1" (at least one dot is required in the value to distinguish from the ID)
		if( $linked_table == 'themen' && strpos($value, '.')!==false )
		{	
			$sql .= " OR kuerzel='" . addslashes($value) . "' OR kuerzel='" .addslashes($value).  ".'";
			$sql_descr .= " oder {$linked_table_def->descr}.K&uuml;rzel";			
		}
		
		// try by get_id_from_txt(), allow multiple values ... (changes for RLP, 19.08.2013)
		$seperator = regGet("edit.seperator.{$this->tableName}", ';'); 
		if( $seperator == '' ) $seperator = ';';
		$sep_arr = explode($seperator, $value);
		$ret = array(); $error = '';
		for( $a = 0; $a < sizeof((array) $sep_arr); $a++ ) 
		{
			$curr_attr = trim($sep_arr[$a]);
			if( $curr_attr !== '' )
			{
				$curr_id = $linked_table_def->get_id_from_txt($curr_attr, $attr_error);
				if( $curr_id ) {
					$ret[] = $curr_id;
				}
				else {
					$error = "Unbekannter Wert <i>".isohtmlspecialchars($curr_attr)."</i> in Parameter2. Bitte geben Sie hier einen g&uuml;ltigen Wert aus <i>{$sql_descr}</i> ein.";
				}
			}
		}
		
		if( sizeof($ret) ) {
			if( $error == '' )
				return $ret;
			else {
				$this->renderDefaultPage($error);
				exit();
			}
		}
		
		// search!
		$db->query($sql);
		if( $db->next_record() )
		{
			$ret = $db->f('id');
			if( $db->next_record() )
			{
				$this->renderDefaultPage("Nicht eindeutiger Wert <i>".isohtmlspecialchars($value)."</i> in Parameter2. Bitte geben Sie hier einen g&uuml;ltigen Wert aus <i>{$sql_descr}</i> ein.");
				exit();
			}
			return array($ret);
		}
		else
		{
			$this->renderDefaultPage("Unbekannter Wert <i>".isohtmlspecialchars($value)."</i> in Parameter2. Bitte geben Sie hier einen g&uuml;ltigen Wert aus <i>{$sql_descr}</i> ein.");
			exit();
		}
	}
	
	
	private function do_field_action($localTableName, $allIdsStr, $action, $param1, $param2)
	{
		$db = new DB_Admin;
		$db2 = new DB_Admin;
		$temp = explode('__', $action);
		$field  = isset( $temp[1] ) ? $temp[1] : null;
		$action = isset( $temp[2] ) ? $temp[2] : null;

		$table_def = Table_Find_Def($localTableName);
		
		$rowdescr = '';
		
		if( $field == 'user_grp' ) {
			$rowdescr = 'Benutzergruppe';
		}
		if( $field == 'user_access' ) {
			$rowdescr = 'Rechte';
		}
		else for( $r = 0; $r < sizeof((array) $table_def->rows); $r++ ) {
		    $rowsName = isset( $table_def->rows[$r]->name ) ? $table_def->rows[$r]->name : null;
			if( $rowsName == $field ) { 
			    $rowdescr = isset( $table_def->rows[$r]->descr ) ? trim($table_def->rows[$r]->descr) : null; 
			    break; 
			}
		}
		
		if( $rowdescr == '' ) {
			die ('bad row.');
		}
		
		switch( $action )
		{
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
				if( $action == 'setenum' ) 
				{
					if( $param1 != '' ) { $this->renderDefaultPage('Parameter1 wird bei dieser Aktion nicht verwendet, bitte &uuml;berpr&uuml;fen Sie Ihre Eingaben.'); exit(); }
					$param2 = $this->getFieldEnum($table_def->rows[$r], $param2);
				}
				else if( $action == 'setsattr' )
				{
					if( $param1 != '' ) { $this->renderDefaultPage('Parameter1 wird bei dieser Aktion nicht verwendet, bitte &uuml;berpr&uuml;fen Sie Ihre Eingaben.'); exit(); }
					$temp = $this->getFieldAttrs($table_def->rows[$r], $param2);
					$param2 = $temp[0];
				}
				else if( $action == 'settext' || $action == 'setdate' )
				{
					if( $param1 != '' ) { $this->renderDefaultPage('Parameter1 wird bei dieser Aktion nicht verwendet, bitte &uuml;berpr&uuml;fen Sie Ihre Eingaben.'); exit(); }
					if( $param2 == '' ) { $this->renderDefaultPage('Bitte geben Sie den zu setzenden Wert in Parameter2 an.'); exit(); }
					if( $action == 'setdate' ) { $param2 = sql_date_from_human($param2, 'date'); if($param2=='0000-00-00 00:00:00'){$this->renderDefaultPage('Ung&uuml;ltige Datumsangabe.');exit();} }
				}
				else if( $action == 'settext2' || $action == 'setdate2' )
				{
					if( $param1 == '' ) { $this->renderDefaultPage('Bitte geben Sie die zu suchende Zeichenkette in Parameter1 an.'); exit(); }
					if( $action == 'setdate2' ) { $param1 = sql_date_from_human($param1, 'date'); if($param1=='0000-00-00 00:00:00'){$this->renderDefaultPage('Ung&uuml;ltige Datumsangabe.');exit();} }
					if( $action == 'setdate2' ) { $param2 = sql_date_from_human($param2, 'date'); if($param2=='0000-00-00 00:00:00'){$this->renderDefaultPage('Ung&uuml;ltige Datumsangabe.');exit();} }
				}
				else if( $action == 'settext3' || $action == 'setdate3' )
				{
					if( $param1 != '' || $param2 != '' ) { $this->renderDefaultPage('Parameter1 und Parameter2 werden bei dieser Aktion nicht verwendet, bitte &uuml;berpr&uuml;fen Sie Ihre Eingaben.'); exit(); }
					if( $action == 'setdate3' ) { $param2='0000-00-00 00:00:00'; }
				}
				
				$all_changes = 0;
				
				if( $param1 != '' )
				    $sql = "SELECT id, $field FROM {$localTableName} WHERE id IN($allIdsStr) AND $field LIKE '%".addslashes($param1)."%';";
				else
				    $sql = "SELECT id, $field FROM {$localTableName} WHERE id IN($allIdsStr);";
				    
				$db->query($sql);
				while( $db->next_record() )
				{
					$id = intval($db->f('id'));
					$content = $db->fs($field);
					$changes = 0;
					if( $param1=='' )
					{
						if( $content != $param2 )
							$changes++;
						
						$content = $param2;
					}
					else
					{
						$content = str_replace($param1, $param2, $content, $changes);
					}
					if( $changes > 0 )
					{
					    $sql2 = "UPDATE {$localTableName} SET $field='".addslashes($content)."' WHERE id=$id;";
					    $db2->query($sql2);
						$all_changes += $changes;
					}
				}
				
				if( $all_changes == 0 )
				{
					$this->renderDefaultPage('Keine &Auml;nderungen notwendig.', 'i');
					exit(); // no log
				}
				
				if( $localTableName == 'durchfuehrung' )  {
				    $sql = "SELECT primary_id FROM kurse_durchfuehrung WHERE secondary_id = $id";   // get course id
				    $db->query($sql);
				    if( $db->next_record() ) {
				        $trigger_param = array( 'action'=>'afterupdate', 'id'=>$id, 'primary_id' => $db->f('primary_id'), 'origin'=>'Multiedit'  );
				        call_plugin($table_def->trigger_script, $trigger_param);  // DF trigger
				    }
				}
				
				if( $param1=='' )
					return "Feld $rowdescr wurde auf '".isohtmlspecialchars($param2_org)."' gesetzt; dabei wurden $all_changes Aenderungen vorgenommen. ";
				else
					return "'".isohtmlspecialchars($param1)."' durch '".isohtmlspecialchars($param2_org)."' im Feld $rowdescr ersetzt; dabei wurden $all_changes Aenderungen vorgenommen. ";
				
			case 'addmattr':
				if( $param1 != '' ) { $this->renderDefaultPage('Parameter1 wird bei dieser Aktion nicht verwendet, bitte &uuml;berpr&uuml;fen Sie Ihre Eingaben.'); exit(); }
				$param2_org = $param2;
				$param2 = $this->getFieldAttrs($table_def->rows[$r], $param2);
				$allIdsArr = explode(',', $allIdsStr);
				$all_changes = 0;
				for( $a = 0; $a < sizeof($allIdsArr); $a++ )
				{
					for( $param2_i = 0; $param2_i < sizeof((array) $param2); $param2_i++ )
					{
						$db->query("SELECT attr_id FROM {$localTableName}_{$table_def->rows[$r]->name} WHERE primary_id=$allIdsArr[$a] AND attr_id=".$param2[$param2_i]);
						if( !$db->next_record() )
						{
							$all_changes++;
							$db->query("INSERT INTO {$localTableName}_{$table_def->rows[$r]->name} (primary_id, attr_id) VALUES ($allIdsArr[$a], ".$param2[$param2_i].");");
						}
					}
				}
				if( $all_changes == 0 )
				{
					$this->renderDefaultPage('Keine &Auml;nderungen notwendig.', 'i');
					exit(); // no log
				}
				return "'".isohtmlspecialchars($param2_org)."' zu Feld $rowdescr hinzugef&uuml;gt; dabei wurden $all_changes &Auml;nderungen vorgenommen. ";
			
			case 'delmattr':
				if( $param1 != '' ) { $this->renderDefaultPage('Parameter1 wird bei dieser Aktion nicht verwendet, bitte &uuml;berpr&uuml;fen Sie Ihre Eingaben.'); exit(); }
				$param2_org = $param2;
				$temp = $this->getFieldAttrs($table_def->rows[$r], $param2);
				$param2 = implode(',', $temp);
				
				$db->query("SELECT attr_id FROM {$localTableName}_{$table_def->rows[$r]->name} WHERE attr_id IN ($param2) AND primary_id IN ($allIdsStr)");
				if( !$db->next_record() )
				{
					$this->renderDefaultPage('Keine &Auml;nderungen notwendig.', 'i');
					exit(); // no log
				}
				
				$db->query("DELETE FROM {$localTableName}_{$table_def->rows[$r]->name} WHERE attr_id IN ($param2) AND primary_id IN ($allIdsStr)");
				return "'".isohtmlspecialchars($param2_org)."' aus Feld $rowdescr entfernt. ";
			
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
	
		if( !acl_check_access("SYSTEM.MULTIEDIT", -1, ACL_EDIT) )
		{
			$this->renderStatusPage('Mit der Funktion &quot;MultiEdit&quot; k&ouml;nnen Aktionen f&uuml;r mehrere Datens&auml;tze '
							.'gleichzeitig ausgef&uuml;hrt werden. Um diese Funktion zu '
							.'verwenden mu&szlig; der Systemadministrator Ihnen zuvor das Recht &quot;SYSTEM.MULTIEDIT&quot; einr&auml;umen.');
			exit();
		}

		$user_answer = isset( $_REQUEST['user_answer'] ) ? $_REQUEST['user_answer'] : null;
		$correct_answer = isset( $_REQUEST['correct_answer'] ) ? $_REQUEST['correct_answer'] : null;
		
		if( isset( $_SERVER['HTTP_HOST'] ) && substr($_SERVER['HTTP_HOST'], -6)!='.local' 
		 && intval( $user_answer ) != intval( $correct_answer ) )
		{
			$this->renderDefaultPage('Sie haben die Sicherheitsabfrage falsch beantwortet.');
			exit();
		}
		
		// ignore all aborts
		ignore_user_abort(1);
		set_time_limit(0);
		
		// IDs der Datensaetze laden
		if( !isset($_SESSION['g_session_index_sql'][$this->tableName]) ) { $this->renderStatusPage("Keine Datens&auml;tze ausgew&auml;hlt?"); exit(); }
		
		$eql2sql = new EQL2SQL_CLASS($this->tableName);
		$eql = !isset( $_SESSION['g_session_index_eql'][$this->tableName] ) || $_SESSION['g_session_index_eql'][$this->tableName] == '' ? '*' : $_SESSION['g_session_index_eql'][$this->tableName];
		$sql = $eql2sql->eql2sql($eql, 'id', acl_get_sql(ACL_READ, 0, 1, $this->tableName), 'id');

		$allIdsStr = '';
		$this->allIdsCount = 0;
		$db = new DB_Admin;
		$db->query($sql);
		while( $db->next_record() )
		{
			if( $allIdsStr != '' ) $allIdsStr .= ',';
			$allIdsStr .= $db->f('id');
			$this->allIdsCount++;
		}

		if( $this->allIdsCount <= 0 )
		{
			$this->renderStatusPage('Bitte w&auml;hlen Sie zun&auml;chst die zu bearbeitenden Datens&auml;tze im Hauptfenster aus.');
			exit();
		}

		if( !isset( $_REQUEST['ok'] ) || $_REQUEST['ok'] != 'OK' )
		{
			$this->renderDefaultPage('');
			exit();
		}

		// befehl auslesen
		$action			= isset( $_REQUEST['action'] ) ? trim( $_REQUEST['action'] ) : null;
		$param1			= isset( $_REQUEST['param1'] ) ? $_REQUEST['param1'] : null; // Leerzeichen sind relevant!
		$param2			= isset( $_REQUEST['param2'] ) ? $_REQUEST['param2'] : null; // Leerzeichen sind relevant!
		$add_msg		= '';
		$journal_entry	= isset( $_REQUEST['journal_entry'] ) ? trim( $_REQUEST['journal_entry'] ) : null;
		
		// bei Bedarf die IDs der sekundaeren Tabelle (Durchfuehrungen) laden
		if( substr($action, 0, 8) == 'dfield__' ||  $action == 'del_old_durchf'  )
		{
			$allDurchfIdsStr = '';
			$db->query("SELECT secondary_id FROM {$this->tableName}_durchfuehrung WHERE primary_id IN($allIdsStr);");
			while( $db->next_record() )
			{
				if( $allDurchfIdsStr != '' ) $allDurchfIdsStr .= ',';
				$allDurchfIdsStr .= $db->f('secondary_id');
			}
			if( $allDurchfIdsStr == '' )	
				$this->renderDefaultPage('Keine Durchf&uuml;hrungen in der Auswahl.', 'i');
		}

		// see what to do ...
		if( substr($action, 0, 8) == 'dfield__' )
		{
			$add_msg .= $this->do_field_action('durchfuehrung', $allDurchfIdsStr, $action, $param1, $param2);
		}
		else if( substr($action, 0, 7) == 'field__' )
		{
			$add_msg .= $this->do_field_action($this->tableName, $allIdsStr, $action, $param1, $param2);
		}
		else if( $action == 'del_sel' )
		{
			// ... delete a number of records
			$allIdsArr = explode(',', $allIdsStr);
			for( $a = 0; $a < sizeof($allIdsArr); $a++ )
			{
				$id = $allIdsArr[$a];

				// access?
				if( !acl_check_access("{$table_def->name}.COMMON", $id, ACL_DELETE) )
					{ $this->renderStatusPage("Sie haben nicht die Berechtigung, Datensatz #$id zu l&ouml;schen.", 1 /*1=update main window*/); exit(); }
			}
			
			for( $a = 0; $a < sizeof($allIdsArr); $a++ )
			{
				$id = $allIdsArr[$a];
				
				// delete in DB
				$table_def->destroy_record_dependencies($id);
				$db->query("DELETE FROM {$table_def->name} WHERE id=$id");
				
				// trigger?
				if( isset( $table_def->trigger_script ) && $table_def->trigger_script )
				{
					$trigger_param = array('action'=>'afterdelete', 'id'=>$id);
					call_plugin($table_def->trigger_script, $trigger_param);
				}
			}
			$add_msg .= "Insgesamt " . sizeof($allIdsArr) . " Datens&auml;tze gel&ouml;scht. ";
		}
		else if( $action == 'duplikat_kurse' ) {
		    
		    // duplicate kurse along with stichwoerter and durchfuehrungen
		    // set automated journal entry (in addition to manual entry) for old and duplicated new kurse
		    // set date_changed and date_created to: today
		    
		    // if new anbieter id was not set as parameter 2 exit.
		    if( !isset($param2) || !$param2 ) {
		        $this->renderDefaultPage('Abgebrochen. Sie m&uuml;ssen eine neue Anbieter-ID als Parameter 2 eingeben.');
		        exit();
		    }
		    
		    // Create a new database connection (to be safe)
		    $db2 = new DB_Admin;
		    $db3 = new DB_Admin;
		    $db4 = new DB_Admin;
		    
		    $db2->query( "SELECT id FROM anbieter WHERE id = " . intval($param2) );
		    
		    if( !$db2->next_record() ) {
		        $this->renderDefaultPage('Die in Parameter 2 definierte Anbieter-ID existiert nicht!');
		        exit();
		    }
		    
		    // Set new provider from the second parameter
		    $neuer_anbieter   = $param2;
		    $columns          = array();
		    $dupl_angebote    = array();
		    $dupl_durchf      = array();
		    
		    
		    // Query the database for all column names and data types of the current table
		    $db2->query( "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'kurse' AND table_schema = '{$db2->Database}' ORDER BY ordinal_position" );
		    
		    // Loop through the database results and store each column's data type and name
		    while( $db2->next_record() )
		    {
		        $columns[] = array( 'type' => $db2->fs('data_type'), 'name' => $db2->fs( 'column_name' ) );
		    }
		    
		    // Query the database to select all records from the current table by IDs
		    $from = "FROM kurse WHERE id IN($allIdsStr)";
		    $db->query("SELECT *, (SELECT count(*) $from) AS cnt $from;");
		    
		    $cnt_kurse = 0;
		    
		    // Loop through each record in the result
		    while( $db->next_record() )
		    {
		        $cnt_kurse++;
		        
		        $kID_old = $db->f('id');
		        $aID_old = $db->f('anbieter');
		        $total_kurse = $db->f('cnt');
		        
		        if( $aID_old == intval($param2) ){
		            $this->renderDefaultPage('Abgebrochen. Sie d&uuml;rfen als neue Anbieter-ID in Parameter 2 nicht die bisherige Anbieter-ID eingeben.');
		            exit();
		        }
		        
		        // Initialize variables for SQL SET clause and a counter
		        $set = "";
		        $cnt = 0;
		        
		        // Iterate over each column
		        foreach( $columns AS $col ) {
		            $cnt++;
		            
		            // Skip the 'id' column for insertion into new kurs, because auto increment
		            if( $col['name'] == 'id' ) {
		                continue;
		            }
		            else
		                $set .= $col['name'] . " = ";
		                
		                // Check the data type and prepare the SQL SET clause accordingly
		                if( $col['type'] == 'bigint' || $col['type'] == 'int' || $col['type'] == 'mediumint' || $col['type'] == 'float' || $col['type'] == 'decimal' ) {
		                    
		                    if( $col['name'] == 'anbieter' )
		                        $set .= $param2;   // Use the new anbieter as set in Multiedit Param 2
		                    else if( $col['name'] == 'user_created' || $col['name'] == 'user_modified' )
		                        $set .= (isset($_SESSION['g_session_userid']) ? intval($_SESSION['g_session_userid']) : null);
		                    else if( $col['type'] == 'int' && intval($db->f($col['name'])) == 0 )
		                        $set .= intval($db->f($col['name'])); // Set thema/freigeschaltet to 0 if empty b/c NULL not always a valid value
		                    else
		                        $set .= ($db->f( $col['name'] ) ? $db->f( $col['name'] ) : 'NULL'); // Use the value from the database or NULL
		                                                
		                } else {
		                    
		                    // Set journal entry
		                    if( $col['name'] == 'notizen' )
		                        $set .= "'" . $journal_entry . "\n". date('d.m.y') . ": Duplikat von " . $kID_old . " via Multiedit\n". $db->fs( $col['name'] ) . "'";
		                    else if( $col['name'] == 'date_created' || $col['name'] == 'date_modified' )
		                        $set .= "'" . date('Y-m-d H:i:s') . "'"; // date_created + date_modified = today
		                    else
		                        $set .= "'" . str_replace("'", "\'", $db->fs( $col['name'] ) ) . "'"; // Standard
		                }
		                
		                // Append comma for all columns except the last
		                if( $cnt < count($columns) )
		                    $set .= ", ";
		        }
		        
		        $sql = "INSERT INTO kurse SET " . $set;
		        $db2->query( $sql );
		        
		        $kID_new = $db2->insert_id();
		        
		        if( $kID_new ) {
		            
		            // duplicate stichwoerter
		            
		            // only look up table entries need to be "duplicated" not stichwoerter
		            $cnt_sw = 0;
		            $from = "FROM kurse_stichwort WHERE primary_id = " . $kID_old;
		            $db3->query( "SELECT *, (SELECT count(*) $from) AS cnt $from");
		            
		            // loop over all sw of this kurs
		            while( $db3->next_record() ) {
		                
		                $cnt_sw++;
		                
		                // insert new look up table entry with same sw but new kurse id
		                $sql = "INSERT INTO kurse_stichwort SET primary_id = " . $kID_new . ", attr_id = " . $db3->f('attr_id') . ", structure_pos = " . $db3->f('structure_pos');
		                $db4->query( $sql );
		                
		            }
		            
		            // query the database for all column names and data types durchfuehrung table
		            $db4->query( "SELECT column_name, data_type FROM information_schema.columns "
		                . "WHERE table_name = 'durchfuehrung' AND table_schema = '{$db2->Database}' ORDER BY ordinal_position" );
		            
		            $columns_df = array();
		            // Loop through the database results and store each column's data type and name
		            while( $db4->next_record() )
		            {
		                $columns_df[] = $db4->fs( 'column_name' );
		            }
		            
		            // unset id element from columns b/c that is auto increment
		            foreach ($columns_df as $key => $value) {
		                if ($value == "id") {
		                    unset($columns_df[$key]);
		                }
		            }
		            
		            // duplicate durchfuehrungen and update look up table
		            
		            $cnt_df = 0;
		            $from = "FROM kurse_durchfuehrung WHERE primary_id = " . $kID_old;
		            $sql = "SELECT *, (SELECT count(*) $from) AS cnt $from";
		            $db3->query( $sql );
		            
		            while( $db3->next_record() ) {
		                $colStr_df = implode(', ', $columns_df);
		                $structure_pos = $db3->f('structure_pos');
		                
		                $sql = "INSERT INTO durchfuehrung (".$colStr_df.") SELECT ".$colStr_df." FROM durchfuehrung WHERE id = " . $db3->f('secondary_id');
		                $db4->query( $sql );
		                $dfID_new = $db4->insert_id();
		                
		                if( $dfID_new )
		                    // echo "-> Neue DF : " . $dfID_new . "<br>";
		                    $sql = "INSERT INTO kurse_durchfuehrung SET primary_id = " . $kID_new . ", secondary_id = " . $dfID_new . ", structure_pos = " . $structure_pos;
		                    $db4->query( $sql );
		            }
		            
		        } // end: new kurs
		        
		    } // end: while kurse
		    
		    
		    $add_msg .= $total_kurse . ' Angebote dupliziert. <br>';
		    
		} // end: duplikat_kurse
		else if( $action == 'del_old_durchf' )
		{
			// alte durchfuehrungen loeschen
			$today = ftime("%Y-%m-%d 00:00:00");
			$toMod = '';
			$toModCnt = 0;
			$dfIDs = array();
			$db->query("SELECT id FROM durchfuehrung WHERE id IN($allDurchfIdsStr) AND beginnoptionen=0 AND beginn!='0000-00-00' AND beginn<'$today';");
			while( $db->next_record() ) 
			{
				if( $toMod != '' ) $toMod .= ',';
				$toMod .= $db->f('id');
				$dfIDs[] = $db->f('id');
				$toModCnt ++;
			}
			
			if( $toModCnt == 0 )
				{ $this->renderDefaultPage("Keine abgelaufenen Durchf&uuml;hrungen in der Auswahl.", 'i'); exit(); }

			foreach( $dfIDs AS $dfID ) {
                $sql = "SELECT primary_id FROM kurse_durchfuehrung WHERE secondary_id = $dfID";   // get course id
				$db->query($sql);

				if( $db->next_record() ) {
				    $trigger_param = array( 'action'=>'afterdelete', 'id'=>8, 'primary_id'=>$db->f('primary_id'), 'origin'=>'Multiedit'  );
				    $table_defSec = Table_Find_Def('durchfuehrung');
				    call_plugin($table_defSec->trigger_script, $trigger_param);  // DF trigger
				}
			}
								
			$db->query("DELETE FROM durchfuehrung WHERE id IN($toMod);");		    
			$db->query("DELETE FROM {$this->tableName}_durchfuehrung WHERE secondary_id IN($toMod);");
			$add_msg .= "Insgesamt $toModCnt abgelaufene Durchf&uuml;hrungen gel&ouml;scht. ";
			
		}
		else if( $action == 'trigger_kurse' ) 
		{
			require_once('config/trigger_kurse.inc.php');
			$allIdsArr = explode(',', $allIdsStr);
			foreach( $allIdsArr as $currId ) {
				$uks = update_kurs_state($currId, array('set_plz_stadtteil'=>1, 'write'=>1));
				if( isset( $uks['returnmsg'] ) && $uks['returnmsg'] ) {	
					$add_msg .= "<a href=\"edit.php?table=kurse&id={$currId}\" target=\"_blank\">Kurs ID {$currId}</a>: {$uks['returnmsg']}<br />";
				}
			}
			$add_msg .= sizeof($allIdsArr)." Kurse &uuml;berpr&uuml;ft. ";
		}
		else if( $action == 'add_journal' )
		{
			// nur journaleintrag hinzufuegen
			if( $param1 != '' || $param2 != '' ) { $this->renderDefaultPage('Parameter1 und Parameter2 werden bei dieser Aktion nicht verwendet, bitte &uuml;berpr&uuml;fen Sie Ihre Eingaben.'); exit(); }
			if( $journal_entry == '' )
			{
				$this->renderDefaultPage('Bitte geben Sie den hinzuzuf&uuml;genden Journaleintrag ein.');
				exit();
			}
		}
		else
		{
			$this->renderDefaultPage('Bitte w&auml;hlen Sie eine Aktion aus.');
			exit();
		}

		// write journal + date_modified (only if not already set above)
		$journal_sql = '';
		if( $journal_entry != '' )
		{
			$journal_sql = ", notizen=CONCAT('".addslashes($journal_entry)."\n',notizen)";
			$add_msg .= 'Journaleintrag: '.$journal_entry.' ';
		}
		
		if( $action == 'duplikat_kurse' && (!isset($journal_entry) || strlen($journal_entry) == 0 ) )
		    ; // journal + dates already set
		else {
		  // Default
		  $sql = "UPDATE {$this->tableName}
				  SET date_modified='" . ftime("%Y-%m-%d %H:%M:%S")."'
					, user_modified=" . (isset($_SESSION['g_session_userid']) ? intval($_SESSION['g_session_userid']) : null)."
					  $journal_sql
				  WHERE id IN ($allIdsStr);
	      ";
		        
		  $db->query($sql);  
		}

		$add_msg . " Die Aktion wurde f&uuml;r {$this->allIdsCount} $this->tableDescr durchgef&uuml;hrt.";
		
		$logwriter = new LOG_WRITER_CLASS;
		$logwriter->addData('query', $eql);
		$logwriter->addData('action', $add_msg);
		$logwriter->log($this->tableName, $allIdsStr, (isset($_SESSION['g_session_userid']) ? $_SESSION['g_session_userid'] : null), 'multiedit');
		
		$this->renderStatusPage($add_msg, 1 /*1=update main window*/);
		exit();
	}
};