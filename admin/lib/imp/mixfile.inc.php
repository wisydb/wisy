<?php

define('PREPARE_FOR_BROWSING_VERSION', 	2);

define ('GET_UPDATES', 1);
define ('GET_DELETE', 2);

define('IMP_OVERWRITE_NEVER',		0);
define('IMP_OVERWRITE_ALWAYS',		1);
define('IMP_OVERWRITE_OLDER',		2);

define('IMP_DELETE_NEVER',			0);
define('IMP_DELETE_DELETED',		1); // In Mix-Datei geloeschte Datensaetze auch im Bestand loeschen



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
			$this->error_str = 'Kann Datei nicht &ouml;ffnen.' . ($this->sqliteDb->error_str? " ({$this->sqliteDb->error_str})" : "");
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
			$this->error_str = "Ung&uuml;ltige Versionsnummer ($file_version anstelle von $code_version).";
			return false; // error, ok() will return false
		}
	
		$this->all_tables = array($base_table);
		$temp = array();
		
		// Only add an array element, if other tables are defined
		if( $this->ini['other_tables'] != '' )
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
	    if( isset( $this->sqliteDb ) && is_object($this->sqliteDb) )
		{
			$this->sqliteDb->close();
			unset($this->sqliteDb);
		}

		if( isset( $this->sqliteDb2 ) && is_object($this->sqliteDb2) )
		{
			$this->sqliteDb2->close();
			unset($this->sqliteDb2);
		}
	}	
	function create_db_object_to_use()
	{
	    if( !isset( $this->sqliteDb2 ) || !is_object($this->sqliteDb2) ) {
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
	    if( isset( $this->ini['prepared_for_browsing'] ) && intval($this->ini['prepared_for_browsing']) >= PREPARE_FOR_BROWSING_VERSION )
			return; // already done
	
		ignore_user_abort(1);
		set_time_limit(0);
	
		// create all index
		$indexToCreate = array();
		global $Table_Def;
		for( $t = 0; $t < sizeof((array) $Table_Def); $t++ )
		{
			$tableDef = $Table_Def[$t];
			if( isset( $tableDef->name ) && isset( $this->all_tables ) && in_array($tableDef->name, $this->all_tables) )
			{
				$indexToCreate[] = array($tableDef->name, 'id');
				$rows  = $tableDef->rows;
				for( $r = 0; $r < sizeof((array) $rows); $r++ )
				{
				    $flags = isset( $rows[$r]->flags ) ? $rows[$r]->flags : null;
					switch( $flags & TABLE_ROW ) 
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
		$GLOBALS['site']->msgAdd('Die Mix-Datei <i>'.isohtmlspecialchars($this->full_path).'</i> wurde f&uuml;r die Bearbeitung vorbereitet. Sie k&ouml;nnen jetzt Ihre Einstellungen vornehmen; diese werden dann direkt in der Datei f&uuml;r einen folgenden Import gespeichert.', 'i');
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
	    if( !isset( $this->ini ) || !is_array($this->ini) )
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
	function get_records($table, $get_flags, $mixfile_options)
	{
	    $ret = array();
	    
	    if( $get_flags & GET_UPDATES )
	    {
	        // INSERT/UPDATE: read source
	        if( isset( $this->all_tables ) && in_array($table, $this->all_tables) )
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
	        
	        echo "<br>"."Potenzielle L&ouml;schvorg&auml;nge vorbereiten:" . "<br>";
	        echo "Sync-Src: Mix-Datei/Quell-System = <b>" . $file_sync_src . "</b> <-> Ziel-System = <b>". $system_sync_src . "</b> ";
	        
	        if( $file_sync_src==0 || $system_sync_src==0 /*|| $system_sync_src==$file_sync_src*/ ) return $ret;
	        // ^^^ wenn wir nur unterschiedliche Sync-Sourcen loeschen, ist dies zwar sicherer, erlaubt aber bspw. kein Backup mit demselben Sync-Src!
	        //     man muss nur aufpassen, dann sollte das kein Problem sein
	        
	        echo "=> Sync-Src sind jeweils nicht 0".( (intval($file_sync_src) != intval($system_sync_src)) ? ", aber unterschiedlich" : " und gleich" ) . ".<br>";
	        
	        // DELETE: get all records existant in the source
	        // echo "Ermittle alle ids der Tablle {$table} in der Mix-Datei..."."<br>";
	        $sql = "SELECT ids FROM x_ids WHERE tble='$table'";
	        $this->sqliteDb->query($sql);
	        if( !$this->sqliteDb->next_record() ) return $ret;
	        $all_ids = $this->sqliteDb->fs('ids');
	        if( $all_ids == '' ) return $ret;
	        
	        // DELETE: any additional records in the system with the same sync_src can be marked for deletion:
	        // these records come from older imports and were deleted on the source systems in the meanwhile
	        
	        // If sync options don't sepcifically specify data rows of which sync_src may be deleted
	        // assume, import is only allowed to delete those rows, which match the sync_src in the mixfiles(/source).
	        // This is only one integer value (eventhough source system may contain data from other WISY-systems with different sync sources)
	        // || (isset($mixfile_options['sync_src']) && isset($mixfile_options['sync_src']['table']) && trim($mixfile_options['sync_src']['table']) != $table)
	        if( !isset($mixfile_options['sync_src']) )  {
	            
	            // Default
	            echo "Ermittle alle ids im Zielsystem, die, wie das Quellsystem {$file_sync_src} als Sync-Src haben und nicht in der Quelle (Mixdatei) vorhanden sind..."."<br>";
	            
	            $sql = "SELECT id, date_modified FROM $table WHERE sync_src=$file_sync_src AND id NOT IN(".$all_ids.") ORDER BY id;";
	            $this->mysqlDb->query($sql);
	            
	        } else {
	            
	            $specific_sync_srces = implode(',', $mixfile_options['sync_src']['srces']);
	            
	            // With syn_src option set
	            echo "<br>Sync-Srces, welche gel&ouml;scht werden d&uuml;rfen wurden spezifisch gesetzt:<br>Ermittle alle IDs aller Zeilen der Tabelle <b>'{$table}'</b> im Zielsystem, die als sync_src eines dieser Werte haben: <b>{$specific_sync_srces}</b> und gleichzeitig nicht in der Quelle (Mixdatei) vorhanden sind..."."<br>";
	            
	            /* // Warum werden bei SW-Loeschung auch Glossar und Themen betrachtet (verstaendlich), geloescht (nicht verstaendlich)?
	             if( !isset($mixfile_options['sync_src']['table']) || trim($mixfile_options['sync_src']['table']) != $table ) {
	             echo("Fehler: Spezifizierte Tabelle '".$mixfile_options['sync_src']['table']."' in Syncjob-Optionen stimmt nicht mit Tabelle in Mix-Datei '".$table."' &uuml;berein. ");
	             return $ret;
	             }
	             
	             if( !isset($mixfile_options['sync_src']['srces']) ) {
	             echo("Fehler: Es fehlen konkrete Sync_src-Eintr&auml;ge in den Syncjob-Optionen. ");
	             return $ret;
	             } */
	            
	            $sql = "SELECT id, date_modified FROM $table WHERE sync_src IN (".$specific_sync_srces.") AND id NOT IN(".$all_ids.") ORDER BY id;";
	            $this->mysqlDb->query($sql);
	            
	        }
	        
	        // mark data row for deletion if ids where found in target system not present in mixfile
	        // "marking" is done by just NOT setting src_date_modified for this id - that's it (compare to importer.in.php "delete?")
	        $cnt_delete = 0;
	        while( $this->mysqlDb->next_record() ) {
	            $mysqlId = $this->mysqlDb->fs('id');
	            if( !isset($ret[ $mysqlId ]) /* check is normally only needed if the ids list is corrupted for some reasons  ... */ ) {
	                $cnt_delete++;
	                $ret[ $mysqlId ] = array('dest_date_modified' => $this->mysqlDb->fs('date_modified'));
	            }
	        }
	        
	        if($cnt_delete)
	            echo $cnt_delete." zu l&ouml;schende Datens&auml;tze im Zielsystem gefunden."."<br><br>";
	            else
	                echo "Keine zu l&ouml;schende Datens&auml;tze im Zielsystem gefunden."."<br><br>";
	                
	    }
	    
	    return $ret;
	}
	


};