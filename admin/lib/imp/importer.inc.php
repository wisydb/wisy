<?php

/*=============================================================================
Do an Import
===============================================================================

Author:
	Bjoern Petersen

===============================================================================
Ein Import wird gestartet durch aufruf der Funktion 
	
	import_do(<mix_datei>, <option_overwrite>, <option_delete>, [<option_further_options>])

<option_overwrite> legt dabei fest, ob bestehende Datensätze überschrieben werden sollen, oder nicht:
	
	IMP_OVERWRITE_NEVER		bestehende Datensätze nie überschreiben
	IMP_OVERWRITE_ALWAYS	bestehende Datensätze immer überschrieben
	IMP_OVERWRITE_OLDER		bestehende Datensätze überschreiben, wenn er älter als der zu importierende ist
	
<option_delete> delegt fest, ob bestehende Datensätze auch im Bestand gelöscht werden sollen, wenn Sie in der Mix-Datei gelöscht sind:

	IMP_DELETE_NEVER		besteheden Datensätze nie löschen
	IMP_DELETE_DELETED		bestehende Datensätze löschen, wenn sie in der Mix-Datei gelöscht wurden
	
mit <fieldOptions> kann man zudem noch einzelne Felder beim Import schützen; hierbei müssen die Optionen als Zeichenkette 
in der folgenden Form angegeben werden:
	
	<tabelle>.<feld> = <aktion>; [<tabelle>.<feld> = <aktion>;]
	
wobei <aktion> eines der folgenden Kommandos ist:

	protect 				nur für Attribut-Mehrfachfelder sinnvoll: Eigene Attribute werden nicht angetastet,
							andere Attribute werden übernommen und bei Bedarf auch gelöscht
	<andereKommandos>		reserviert für zukünftige Erweiterungen

Beispiel:

	kurse.stichwort = protect


=============================================================================*/



class IMP_IMPORTER_CLASS
{
	/**************************************************************************
	 * Misc.
	 *************************************************************************/

	function __construct()
	{
		$this->user = 0;
		$this->user_grp = 0;
		$this->user_access = 508;
		$this->mysqlDb = new DB_Admin;
	}
	
	function set_user($user, $user_grp = 0, $user_access = 508)
	{
		$this->user = intval($user);
		$this->user_grp = intval($user_grp);
		$this->user_access = intval($user_access);
	}
	function set_progress_callback($function)
	{
		$this->callback = $function;
	}
	private function _progress($msg, $force_output = false)
	{
		if( isset($this->callback) )
			call_user_func($this->callback, $msg, $force_output);
	}

	private function _get_fine_user($in_user_created, $in_user_modified, $in_user_grp, $in_user_access,
									&$out_user_created, &$out_user_modified, &$out_user_grp, &$out_user_access)
	{
		if( 0 )
		{
			$out_user_created	= $this->user;
			$out_user_modified	= $this->user;
			$out_user_grp		= $this->user_grp;
			$out_user_access	= $this->user_access;
		}
		else
		{
			$out_user_created	= $in_user_created;
			$out_user_modified	= $in_user_modified;
			$out_user_grp		= $in_user_grp;
			$out_user_access	= $in_user_access;
		}
	}
	
	private function _log_full($msg, $table = '', $ids = '')
	{
		$this->_log($msg, $table, $ids, true);
	}
	private function _log($msg, $table = '', $ids = '', $full = false)
	{
		// show the message
		$this->_progress($msg, true /* force_output */ );
		
		// log to file
		$logwriter = new LOG_WRITER_CLASS;
		$logwriter->addData('msg', 				$msg);
		if( $full )
		{
			$logwriter->addData('file',				$this->mix_fullpath);
			if( is_object($this->mixfile) )
			{
				$logwriter->addData('export_host',		$this->mixfile->ini_read('export_host'));
				$logwriter->addData('export_user',		$this->mixfile->ini_read('export_user'));
				$logwriter->addData('export_start_time',$this->mixfile->ini_read('export_start_time'));
				$logwriter->addData('q',				$this->mixfile->ini_read('base_table') . ': ' . $this->mixfile->ini_read('query'));
			}
			
			$logwriter->addData('overwrite',		$this->option_overwrite);
			$logwriter->addData('delete',			$this->option_delete);	
			$logwriter->addData('further_options',	$this->option_further_options_str);		
		}
		
		$logwriter->log($table, $ids, $this->user, 'import');
	}
	
	
	/**************************************************************************
	 * Import or Delete a Single Record
	 *************************************************************************/

	private function _is_id_protected($table, $field, $id)
	{
		return $this->protect[$table][$field][$id]? true : false;
	}
	
	private function _import_record($table, $id)
	{
		$mysqlDb  = $this->mysqlDb;
		$sqliteDb = $this->mixfile->sqliteDb2;

		// function imports a single record to the MySQL database
		$table_def = Table_Find_Def($table, 0);
		if( !is_object($table_def) ) { return false; }
		
		// read the basic record from SQLite
		$sqliteDb->query("SELECT * FROM $table WHERE id=$id");
		if( !$sqliteDb->next_record() ) { $this->_log("Kann $table.$id nicht aus Mix-Datei lesen.", $table, $id); return false; }
		$date_created  = $sqliteDb->fs('date_created');
		$date_modified = $sqliteDb->fs('date_modified');
		$this->_get_fine_user($sqliteDb->fs('user_created'), $sqliteDb->fs('user_modified'), $sqliteDb->fs('user_grp'), $sqliteDb->fs('user_access'),
				$user_created, $user_modified, $user_grp, $user_access);
		$sync_src = intval($sqliteDb->fs('sync_src'));
		
		// make sure, the record exists is MySQL
		$mysqlDb->query("SELECT id FROM $table WHERE id=$id;");
		if( !$mysqlDb->next_record() ) {
			$sql = "INSERT INTO $table (id, user_created, date_created) VALUES ($id, $user_created, '$date_created');";
			$mysqlDb->query($sql);
		}
		
		// update the basics values
		$sql = "UPDATE $table SET sync_src=$sync_src, user_modified=$user_modified, user_grp=$user_grp, user_access=$user_access, date_modified='$date_modified'";
		$rows  = $table_def->rows;
		for( $r = 0; $r < sizeof($rows); $r++ )
		{
			$row = $rows[$r];
			switch( $row->flags&TABLE_ROW ) 
			{
					case TABLE_ENUM:
					case TABLE_BITFIELD:
					case TABLE_SATTR:
					case TABLE_INT:
						$sql .= ', ' . $row->name . '=' . $sqliteDb->fs($row->name);
						break;
					
					case TABLE_MATTR:
					case TABLE_SECONDARY:
						break; // no default processing!
					
					default:
						$sql .= ', ' . $row->name . '=' . $mysqlDb->quote($sqliteDb->fs($row->name));
						if( ($row->flags&TABLE_ROW)==TABLE_TEXT && $mysqlDb->column_exists($table, $row->name.'_sorted') ) {
							require_once('eql.inc.php');
							$sql .= ', ' . $row->name.'_sorted=' . $mysqlDb->quote(g_eql_normalize_natsort($sqliteDb->fs($row->name)));							
						}
						break;
			}
		}
		$sql .= " WHERE id=$id;";
		$mysqlDb->query($sql);
		
		// update attribute and secondary tables
		for( $r = 0; $r < sizeof($rows); $r++ ) 
		{
			$row = $rows[$r];
			switch( $row->flags&TABLE_ROW ) 
			{
				case TABLE_MATTR:
				case TABLE_SECONDARY:
					$old_ids = array(); $old_sp = array();
					$new_ids = array();
					$attr_id_name = ($row->flags&TABLE_ROW)==TABLE_MATTR? 'attr_id' : 'secondary_id';
					
					// get all old IDs, mark them as "1" for deletion
					$mysqlDb->query("SELECT $attr_id_name, structure_pos FROM {$table}_{$row->name} WHERE primary_id=$id;");
					while( $mysqlDb->next_record() ) {
						$old_id 			= $mysqlDb->fs($attr_id_name);
						$old_ids[ $old_id ] = 1;
						$old_sp [ $old_id ] = $mysqlDb->fs('structure_pos');
					}
					
					// update/insert into the attribute/secondary link table
					$sqliteDb->query("SELECT $attr_id_name, structure_pos FROM {$table}_{$row->name} WHERE primary_id=$id;");
					while( $sqliteDb->next_record() )
					{
						$attr_id = $sqliteDb->fs($attr_id_name); 
						$new_ids[] = $attr_id;
						$structure_pos = $sqliteDb->fs('structure_pos');
						
						if( $old_ids[ $attr_id ] ) {
							$old_ids[ $attr_id ] = 0;
							if( $old_sp[ $attr_id ] != $structure_pos ) {
								$mysqlDb->query("UPDATE {$table}_{$row->name} SET structure_pos=$structure_pos WHERE primary_id=$id AND $attr_id_name=$attr_id;");
							}
						}
						else {
							$mysqlDb->query("INSERT INTO {$table}_{$row->name} (primary_id, $attr_id_name, structure_pos) VALUES ($id, $attr_id, $structure_pos);");
						}
					}
					
					// delete additional IDs from the attribute/secondary link table
					reset($old_ids);
					while( list($attr_id, $del_do) = each($old_ids) ) {
						if( $del_do ) {
							if( $attr_id_name == 'secondary_id' || !$this->_is_id_protected($table, $row->name, $attr_id) ) {
								$mysqlDb->query("DELETE FROM {$table}_{$row->name} WHERE primary_id=$id AND $attr_id_name=$attr_id;");
							}
							
							if( $attr_id_name == 'secondary_id' ) {
								$mysqlDb->query("DELETE FROM {$row->addparam->name} WHERE id=$attr_id;");
							}
						}
					}
						
					// update the secondary table
					if( $attr_id_name == 'secondary_id' ) {
						for( $i = 0; $i < sizeof($new_ids); $i++ ) {
							$this->_import_record($row->addparam->name, $new_ids[$i]); // errors may be logged ... no abort ...
						}
					}
					break;
			}
		}
		
		return true;
	}

	
	/**************************************************************************
	 * The Import Logic
	 *************************************************************************/
	
	function import_do($mix_fullpath, $option_overwrite, $option_delete, $option_further_options_str = '')
	{
		$imported_ids						= array();
		$todel_ids 							= array();
		$deleted_ids						= array();
		
		$this->mix_fullpath					= $mix_fullpath;
		$this->option_overwrite				= $option_overwrite;
		$this->option_delete				= $option_delete;
		$this->option_further_options_str	= $option_further_options_str;
		$this->protect 						= array();
	
		$sync_tools 		= new SYNC_TOOLS_CLASS;
		$system_sync_src	= $sync_tools->get_sync_src();
	
		// parse the "further options"
		$cnt = preg_match_all("/([\w\.]+)\s*=\s*(\w+)\s*;*/", $option_further_options_str, $matches);
		for( $m = 0; $m < $cnt; $m++ )
		{
			$option = $matches[1][$m]; $temp = explode('.', $option); $table = $temp[0]; $field = $temp[1];
			$value  = $matches[2][$m];
			$table_def = Table_Find_Def($table, 0);
			
			if( $value == 'protect' ) 
			{
				if( $system_sync_src == 0 )  { $this->_log_full('"protect" kann nur zusammen mit einer eigenen Datenbankkennung verwendet werden.'); return false; }
				if( !is_object($table_def) ) { $this->_log_full("$table - ungültige Tabellenangabe."); return false; }
				$linked_table = '';
				for( $r = 0; $r < sizeof($table_def->rows); $r++ ) {
					if( $table_def->rows[$r]->name == $field ) {
						$linked_table = $table_def->rows[$r]->addparam->name;
						break;
					}
				}
				if( $linked_table=='' ) { $this->_log_full("$field - Feld nicht gefunden oder es ist kein Attributfeld mit Mehrfachauswahl."); return false; }
				
				$sql = "SELECT id FROM $linked_table WHERE sync_src=$system_sync_src;";
				$this->mysqlDb->query($sql);
				while( $this->mysqlDb->next_record() ) {
					$this->protect[ $table ][ $field ][ $this->mysqlDb->f('id') ] = 1;
				}
			}
			else
			{
				$this->_log_full('"' . $option_further_options_str . '" enthält ungültige Angaben.');
				return false;
			}
		}
	
		// open the mixfile
		$this->mixfile = new IMP_MIXFILE_CLASS;
		if( !$this->mixfile->open($mix_fullpath) ) {
			$this->_log_full("Kann $mix_fullpath nicht öffnen - ".$this->mixfile->error_str);
			return false;
		}
		$this->mixfile->create_db_object_to_use();
		
		// INSERT / UPDATE - go through all records
		$totalCnt = 0;
		$tables = $this->mixfile->get_tables();
		for( $t = 0; $t < sizeof($tables); $t++ )
		{
			$table   = $tables[$t];
			$records = $this->mixfile->get_records($table, GET_UPDATES|GET_DELETE);
			reset($records);
			while( list($id, $currRecord) = each($records) )
			{	
				// import / delete this record?
				$import   = false;
				$deleteit = false;
				if( !isset($currRecord['src_date_modified']) )
				{
					// delete?
					if( $option_delete == IMP_DELETE_DELETED ) 
						$deleteit = true;
				}
				else if( isset($currRecord['dest_date_modified']) )
				{
					// import?
					if( $currRecord['dest_date_modified'] == $currRecord['src_date_modified'] ) {
						;
					}
					else if( $currRecord['dest_date_modified'] > $currRecord['src_date_modified'] ) {
						if( $option_overwrite == IMP_OVERWRITE_ALWAYS ) {
							$import = true;
						}
					}
					else {
						if( $option_overwrite != IMP_OVERWRITE_NEVER ) {
							$import = true;
						}
					}
				}
				else 
				{
					$import = true;
				}
				
				if( $import || $deleteit ) {
					if( sizeof($imported_ids[$table])==0 && sizeof($todel_ids[$table])==0 ) {
						$this->_log_full('Import gestarted.', $table);
					}
				}
				
				// do the import
				if( $import )
				{
					$this->_import_record($table, $id);
					$imported_ids[ $table ][] = $id;
				}
				else if( $deleteit )
				{
					$todel_ids[ $table ][$id] = 1; // delete itself is delayed to allow easy reference checking
				}
				
				// progress info
				$totalCnt++;
				if( $totalCnt % 10 == 0 )
					$this->_progress("$totalCnt Datensätze bearbeitet ...");				
			}
		}
		
		if( sizeof($imported_ids)==0 && sizeof($todel_ids)==0 ) {
			$this->_log_full('Import gestarted, keine zu importierenden oder zu löschenden Datensätze gefunden.');
		}

		// DELETE - go through all records--
		$totalCnt = 0;
		reset($todel_ids);
		while( list($table, $ids) = each($todel_ids) )
		{
			$table_def = Table_Find_Def($table, 0 /*no access check*/ );
			if( is_object($table_def) ) {
				reset($ids);
				while( list($id, $dummy) = each($ids) )
				{
					// delete the record, if possible
					$id_ref_cnt = $table_def->num_references($id, $id_references);
					if( $id_ref_cnt > 0 ) {
						$this->_log("Datensatz kann nicht gelöscht werden, da er noch referenziert wird.", $table, $id);
					}
					else {
						$table_def->destroy_record_dependencies($id);
						$this->mysqlDb->query("DELETE FROM $table WHERE id=$id");
						$deleted_ids[$table][] = $id;
					}
					
					// progress info
					$totalCnt++;
					if( $totalCnt % 10 == 0 )
						$this->_progress("$totalCnt Datensätze gelöscht ...");	
				}
			}
		}
		reset($deleted_ids);
		while( list($table, $ids) = each($deleted_ids) ) {
			$this->_log('Datensätze gelöscht.', $table, implode(',', $ids));
		}
		
		// FINAL log
		for( $t = 0; $t < sizeof($tables); $t++ ) {
			$table = $tables[$t];
			if( sizeof($imported_ids[$table]) || sizeof($todel_ids[$table]) ) {
				$this->_log('Import beendet.', $table, sizeof($imported_ids[$table])? implode(',', $imported_ids[$table]) : '');
			}
		}
		if( sizeof($imported_ids)==0 && sizeof($todel_ids)==0 ) {
			$this->_log('Import beendet.');
		} 

		// success
		$this->mixfile->ini_write('import_end_time', strftime("%Y-%m-%d %H:%M:%S"));
		$this->mixfile->close();

		return true;
	}
};