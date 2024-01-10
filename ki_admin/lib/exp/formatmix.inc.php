<?php

// actual write into .mix-file

class EXP_FORMATMIX_CLASS extends EXP_GENERICTABLE_CLASS
{
	private $sqliteOb;
	
	private $currTable;
	private $currPrefixNTable;
	private $currTableType;
	private $currTableCnt;
	private $currFieldNames;
	private $currValues;
	private $otherTables;
	private $tableFieldsSummary; // to check for redundant declarations

	function __construct()
	{
		parent::__construct();
		
		$this->options['table']	= array('enum',   '_EXP_TABLETOEXPORT', '', 'tables');
		$this->options['q']		= array('text',   '_EXP_RECORDSQUERY', '', 60);
		$this->options['dummy']	= array('remark', '_EXP_RECORDSQREMARK');
		$this->tableFieldsSummary[$this->currPrefixNTable] = array();
	}

	function tableStart($tableName, $type)
	{
		// create database, if not yet done (we do this here to avoid an database without tables)
		if( !is_object($this->sqliteOb) )
		{
			$this->sqliteOb = new G_SQLITE_CLASS;
			$simplified_host = strtr($_SERVER['HTTP_HOST'], array('www.'=>'', ':'=>'-', '.'=>'-'));
			$filename = $this->allocateFileName($simplified_host /*$this->base_table*/.'.mix'); // it is better to use the host name (the source) in the file name; in the import overview, the tables are shown automatically in detail
			if( !$this->sqliteOb->open($filename) ) { 
				$this->progress_abort('Cannot open .mix file (' . $this->sqliteOb->get_last_error() . ')');
			}
		}
		// create table
		switch( $type ) {
			case TABLE_EXP_TYPE_EXTRA:	$prefix = 'x_';	break;
			default:					$prefix = '';	break;
		}
		$this->currTable		= $tableName;
		$this->currPrefixNTable	= $prefix.$this->currTable;
		$this->currTableType	= $type;
		$this->currTableCnt		= 0;
		$this->sqliteOb->beginTransaction(); // without the transaction, exporting is about 60 times [sic!] slower - 120 minutes instead of 2 minutes for the whole database (table kurse)
	}
	
	function tableEnd()
	{	    
		$this->sqliteOb->commit();
		
		$currTableType = isset( $this->currTableType ) ? $this->currTableType : null;
		$currTable = isset( $this->currTable ) ? $this->currTable : null;
		$baseTable = isset( $this->base_table ) ? $this->base_table : null;
		$currTableCnt = isset( $this->currTableCnt ) ? $this->currTableCnt : null;
		
		if( $currTableType == TABLE_EXP_TYPE_NORMALDATA ) {
		    if( $currTable == $baseTable ) {
		        $this->record_cnt_base = $currTableCnt;
			}
			else {
				$test = Table_Find_Def($this->currTable, false);
				if( $test && !$test->is_only_secondary($temp, $temp) ) {
				    $this->other_tables[] = $currTable;
				    $this->record_cnt_others = isset($this->record_cnt_others) ? $this->record_cnt_others : 0;
				    $this->record_cnt_others += $currTableCnt;
				}
			}
		}
	}



	function declareStart()
	{
		$this->currFieldNames   = '';
		$this->currFieldDeclare = '';
	}
	
	// collect all fields in table to be written in a comma concat. string
	function declareField($name, $rowtype, $removeIfDuplicate = false)
	{
	    $newFieldName = ($this->currFieldNames?   ', ' : '') . $name;
	    $newFieldDeclare = ($this->currFieldDeclare? ', ' : '') . $name . ' ' . ($rowtype==TABLE_INT? 'INTEGER' : 'TEXT');
	    
		// make sure, no duplicate fields in table declaration query,
		// b/c this would mean, table wouldn't be written!
	    $duplArr = isset( $this->tableFieldsSummary[$this->currPrefixNTable]) ? $this->tableFieldsSummary[$this->currPrefixNTable] : null;
	    $duplicateField = is_array( $duplArr ) ? array_search( $name, $duplArr ) : false;
		if( $duplicateField && $removeIfDuplicate ) {
		    // Why not just skip: b/c writing of actual values expects certain fields, like date_modified, to be at the end of the row, not being declared before manually via db.inc.php
		    $msg = "Es wurde ein Feld (<b>{$name}</b>) manuell deklariert (db.inc.php), welches mit einem im Export-Code (generictable.inc.php) deklarierten Meta-Feld kollidiert! Es m&uuml;sste in NO_MANUAL_DECLARATION hinzugef&uuml;gt werden.";
// !		    if( defined('ADMIN_MAIL') ) mail( ADMIN_MAIL, 'Export-Fehler', $msg ); // define in config.inc.php
		    die( $msg . ' -- Fehlermeldung an Admin-Mailadresse gesendet: ' . (defined('ADMIN_MAIL') ? ADMIN_MAIL : 'nein, nicht definiert.' ) ); 
		}
		elseif( $duplicateField ) {
		    $msg = "Fehler: Das Feld <b>".$name."</b> ist bereits f&uuml;r die Tabelle " . $this->currPrefixNTable . " deklariert!";
// !		    if( defined('ADMIN_MAIL') ) mail( ADMIN_MAIL, 'Export-Fehler', $msg ); // define in config.inc.php
		    die( $msg . ' -- Fehlermeldung an Admin-Mailadresse gesendet: ' . (defined('ADMIN_MAIL') ? ADMIN_MAIL : 'nein, nicht definiert.' ) );
		}
		elseif( trim($name) != "" ) {
		    if( !isset( $this->tableFieldsSummary[ $this->currPrefixNTable ] ) || !is_array( $this->tableFieldsSummary[ $this->currPrefixNTable ]) )
		        $this->tableFieldsSummary[ $this->currPrefixNTable ] = array();
		}
		
		array_push( $this->tableFieldsSummary[ $this->currPrefixNTable ], $name ); // keep track of now declared fields, so no duplicates break declaration query
		
		$this->currFieldNames   .= $newFieldName;
		$this->currFieldDeclare .= $newFieldDeclare;
	}
	
	function declareEnd()
	{
		$sql = "CREATE TABLE {$this->currPrefixNTable} ({$this->currFieldDeclare});";

        $result = $this->sqliteOb->query($sql);
		
        if( !$result ) {
            $msg = "Fehler: Die Tabelle <b>{$this->currTable}</b> konnte in der Exportdatei nicht angelegt werden! ";
// !            if( defined('ADMIN_MAIL') ) mail( ADMIN_MAIL, 'Export-Fehler', $msg ); // define in config.inc.php
            die( $msg . ' -- Fehlermeldung an Admin-Mailadresse gesendet: ' . (defined('ADMIN_MAIL') ? ADMIN_MAIL : 'nein, nicht definiert.' ) );
        }
	}

    // beginns row
	function recordStart()
	{
		$this->currValues = '';
	}
	
	// writes (collects) specific field of to be written row 
	function recordField($data)
	{
		$this->currValues .= ($this->currValues? ', ' : '');
		$this->currValues .= $this->sqliteOb->quote($data);		
	}
	
	// actually writes (query) of collected / to be written row
	function recordEnd()
	{
		$sql = "INSERT INTO {$this->currPrefixNTable} ({$this->currFieldNames}) VALUES ({$this->currValues});";
		$result = $this->sqliteOb->query($sql);
		$this->currTableCnt++;
		
		if( !$result ) {
		    $msg = "Fehler: Es konnten die Abfrage:<br><small>{$sql}</small><br>nicht in die Exportdatei geschrieben werden! ";
// !		    if( defined('ADMIN_MAIL') ) mail( ADMIN_MAIL, 'Export-Fehler', $msg ); // define in config.inc.php
		    die( $msg . ' -- Fehlermeldung an Admin-Mailadresse gesendet: ' . (defined('ADMIN_MAIL') ? ADMIN_MAIL : 'nein, nicht definiert.' ) );
		}
	}


	private function recordIni($ini_key, $ini_value)
	{
		$this->recordStart();
			$this->recordField($ini_key);
			$this->recordField($ini_value);
		$this->recordEnd();
	}
	
	
	
	function export($param)
	{
		$export_start_time = time();
		$this->base_table = $param['table'];
		$this->other_tables = array();
		if( !Table_Find_Def($this->base_table, false /*no access check*/) ) $this->progress_abort('Ung'.ueJS.'ltige Tabelle.');
	
		// do the export
		$param['attrasids']			= 1;
		$param['export_ids']		= 1; // write all IDs; this allows synchronisation of deleted records
		$param['export_passwords']	= 1; // yes, export (crypted) passwords
		$param['export_sync_src']	= 1;
		parent::export($param);
		
		// close the sqlite file
		if( is_object($this->sqliteOb) )
		{
			$sync_tools = new SYNC_TOOLS_CLASS;
		
			$mixfile = new IMP_MIXFILE_CLASS;
		
			$this->tableStart('ini', TABLE_EXP_TYPE_EXTRA);
				$this->declareStart();
					$this->declareField('ini_key', TABLE_TEXT, true);
					$this->declareField('ini_value', TABLE_TEXT, true);
				$this->declareEnd();
				$this->recordIni('version',				$mixfile->get_code_version());
				$this->recordIni('base_table',			$this->base_table);
				$this->recordIni('other_tables',		implode(',', $this->other_tables));
				$this->recordIni('query', 				$param['q']);
				$this->recordIni('sync_src', 			$sync_tools->get_sync_src());
				$this->recordIni('record_cnt_base',		(isset($this->record_cnt_base) ? intval($this->record_cnt_base) : null) );
				$this->recordIni('record_cnt_others',	(isset($this->record_cnt_others) ? intval($this->record_cnt_others) : null) );
				$this->recordIni('export_host',			isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
				$this->recordIni('export_user',			isset($_SESSION['g_session_userid']) ? user_ascii_name(intval($_SESSION['g_session_userid'])) : null);
				$this->recordIni('export_start_time',	ftime("%Y-%m-%d %H:%M:%S", $export_start_time));
			$this->tableEnd();
		
			$this->sqliteOb->close();
		}
	}
};