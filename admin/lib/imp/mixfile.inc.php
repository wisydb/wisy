<?php



define('PREPARE_FOR_BROWSING_VERSION', 	2);

define ('GET_UPDATES', 1);
define ('GET_DELETE', 2);

define('IMP_OVERWRITE_NEVER',		0);
define('IMP_OVERWRITE_ALWAYS',		1);
define('IMP_OVERWRITE_OLDER',		2);

define('IMP_DELETE_NEVER',			0);
define('IMP_DELETE_DELETED',		1); // In Mix-Datei gelöschte Datensätze auch im Bestand löschen



class IMP_MIXFILE_CLASS
{
	private $sqliteDb;
	private $mysqlDb;
	private $ini;
	private $all_tables;

	public $error_str;
	public $sqliteDb2; // valid after a call to create_db_object_to_use()
	
	
	//
	// open / close the mix file
	//
	function open($full_path)
	{
		if( isset($this->full_path) ) die('please do not reuse IMP_MIXFILE_CLASS objects, please create a new instance of this class');
	
		$this->full_path = $full_path;
		
		$this->sqliteDb = new G_SQLITE_CLASS;
		if( !$this->sqliteDb->open($this->full_path) ) {
			$this->error_str = 'Kann Datei nicht öffnen.' . ($this->sqliteDb->error_str? " ({$this->sqliteDb->error_str})" : "");
			return false;
		}
			
		$base_table = $this->ini_read('base_table');
		if( $base_table == '' ) {
			$this->error_str = 'Basistabelle fehlt.';
			return false;
		}
	
		$file_version = $this->ini_read('version', 0);
		$code_version = $this->get_code_version();
		if( $file_version != $code_version ) {
			$this->error_str = "Ungültige Versionsnummer ($file_version anstelle von $code_version).";
			return false; // error, ok() will return false
		}
	
		$this->all_tables = array($base_table);
		$temp = explode(',', $this->ini['other_tables']);
		for( $i = 0; $i < sizeof($temp); $i++ ) {
			$this->all_tables[] = trim($temp[$i]);
		}
		
		$this->mysqlDb = new DB_Admin;
		
		// success, so far
		return true;
	}
	function close()
	{
		if( is_object($this->sqliteDb) )
		{
			$this->sqliteDb->close();
			unset($this->sqliteDb);
		}

		if( is_object($this->sqliteDb2) )
		{
			$this->sqliteDb2->close();
			unset($this->sqliteDb2);
		}
	}	
	function create_db_object_to_use()
	{
		if( !is_object($this->sqliteDb2) ) {
			$this->sqliteDb2 = new G_SQLITE_CLASS;
			$this->sqliteDb2->open($this->full_path);
		}
	}
	
	
	//
	// just after the export, the file does not contain any index or tables for settings.
	// if we want to browser and import, we should create them.
	//
	function prepare_for_browse()
	{
		if( intval($this->ini['prepared_for_browsing']) >= PREPARE_FOR_BROWSING_VERSION )
			return; // already done
	
		ignore_user_abort(1);
		set_time_limit(0);
	
		// create all index
		$indexToCreate = array();
		global $Table_Def;
		for( $t = 0; $t < sizeof((array) $Table_Def); $t++ )
		{
			$tableDef = $Table_Def[$t];
			if( in_array($tableDef->name, $this->all_tables) )
			{
				$indexToCreate[] = array($tableDef->name, 'id');
				$rows  = $tableDef->rows;
				for( $r = 0; $r < sizeof((array) $rows); $r++ )
				{
					switch( $rows[$r]->flags & TABLE_ROW ) 
					{
						case TABLE_SECONDARY:
							$indexToCreate[] = array($tableDef->name . '_' . $rows[$r]->name, 'primary_id');
							$indexToCreate[] = array($rows[$r]->addparam->name, 'id');
							break;
						case TABLE_MATTR:
							$indexToCreate[] = array($tableDef->name . '_' . $rows[$r]->name, 'primary_id');
							break;
					}
				}
			}
		}

		for( $i = 0; $i < sizeof($indexToCreate); $i++ )
		{
			$tableName = $indexToCreate[$i][0];
			$fieldName = $indexToCreate[$i][1];
			$indexName = "i_{$tableName}_{$fieldName}";
			if( $this->sqliteDb->table_exists($tableName) ) {
				$sql = "CREATE INDEX IF NOT EXISTS $indexName ON $tableName ($fieldName)";
				$this->sqliteDb->query($sql);
			}
		}

		
		// done
		$GLOBALS['site']->msgAdd('Die Mix-Datei <i>'.isohtmlspecialchars($this->full_path).'</i> wurde für die Bearbeitung vorbereitet. Sie können jetzt Ihre Einstellungen vornehmen; diese werden dann direkt in der Datei für einen folgenden Import gespeichert.', 'i');
		$this->ini_write('prepared_for_browsing', PREPARE_FOR_BROWSING_VERSION);
	}
	
	
	
	
	//
	// misc. 
	//
	function get_code_version()
	{
		return 5;
	}
	function get_tables()
	{
		return $this->all_tables;
	}
	function get_total_record_cnt()
	{
		return intval($this->ini['record_cnt_base']) + intval($this->ini['record_cnt_others']);
	}


	
	//
	// ini functionality
	//
	function ini_read($ini_key, $ini_default = '')
	{
		if( !is_array($this->ini) )
		{
			$this->ini = array();
			$this->sqliteDb->query("SELECT ini_key, ini_value FROM x_ini;");
			while( $this->sqliteDb->next_record() )
			{
				$this->ini[ $this->sqliteDb->fs('ini_key') ] = $this->sqliteDb->fs('ini_value');
			}
		}
		
		return isset($this->ini[$ini_key])? $this->ini[$ini_key] : $ini_default;
	}
	function ini_write($ini_key, $ini_value)
	{
		if( isset($this->ini[$ini_key]) ) {
			if( $this->ini[$ini_key] != $ini_value )
				$this->sqliteDb->query("UPDATE x_ini SET ini_value=".$this->sqliteDb->quote($ini_value)." WHERE ini_key=".$this->sqliteDb->quote($ini_key).";");
		}
		else {
			$this->sqliteDb->query("INSERT INTO x_ini (ini_key, ini_value) VALUES (".$this->sqliteDb->quote($ini_key).",".$this->sqliteDb->quote($ini_value).");");
		}
		$this->ini[$ini_key] = $ini_value;
	}
	

	
	//
	// go through all records
	//
	function get_records($table, $get_flags)
	{
		$ret = array();	

		if( $get_flags & GET_UPDATES )
		{
			// INSERT/UPDATE: read source
			if( in_array($table, $this->all_tables) ) 
			{
				$this->sqliteDb->query("SELECT id, date_modified FROM {$table} ORDER BY id;");
				while( $this->sqliteDb->next_record() ) {
					$ret[ $this->sqliteDb->fs('id') ] = array(
						'src_date_modified' => $this->sqliteDb->fs('date_modified')
					);
				}
			}
			
			// INSERT/UPDATE: read destitation
			if( sizeof($ret) && Table_Find_Def($table, 0) )
			{
				$this->mysqlDb->query("SELECT id, date_modified FROM {$table} WHERE id IN (".implode(',', array_keys($ret)).");");
				while( $this->mysqlDb->next_record() ) {
					$ret[ $this->mysqlDb->fs('id') ]['dest_date_modified'] = $this->mysqlDb->fs('date_modified');
				}
			}
		}
		
		if( $get_flags & GET_DELETE )
		{
			// DELETE: we can identify records to delete ONLY IF sync_src of file and system are different
			$sync_tools = new SYNC_TOOLS_CLASS;
			$system_sync_src = $sync_tools->get_sync_src();
			$file_sync_src   = intval($this->ini['sync_src']);
			if( $file_sync_src==0 || $system_sync_src==0 /*|| $system_sync_src==$file_sync_src*/ ) return $ret;
														 // ^^^ wenn wir nur unterschiedliche Sync-Sourcen löschen, ist dies zwar sicherer, erlaubt aber bspw. kein Backup mit demselben Sync-Src!
														 //     man muß nur aufpassen, dann sollte das kein Problem sein
														 
			// DELETE: get all records existant in the source
			$this->sqliteDb->query("SELECT ids FROM x_ids WHERE tble='$table'");
			if( !$this->sqliteDb->next_record() ) return $ret;
			$all_ids = $this->sqliteDb->fs('ids');
			if( $all_ids == '' ) return $ret;
			
			// DELETE: any additional records in the system with the same sync_src can be marked for deletion: 
			// these records come from older imports and are deleted on the source systems meanwhile
			$this->mysqlDb->query("SELECT id, date_modified FROM $table WHERE sync_src=$file_sync_src AND id NOT IN(".$all_ids.") ORDER BY id;");
			while( $this->mysqlDb->next_record() ) {
				$mysqlId = $this->mysqlDb->fs('id');
				if( !isset($ret[ $mysqlId ]) /* check is normally only needed if the ids list is corrupted for some reasons  ... */ ) {
					$ret[ $mysqlId ] = array('dest_date_modified' => $this->mysqlDb->fs('date_modified'));
				}
			}
		}
		
		return $ret;
	}


	

	

};