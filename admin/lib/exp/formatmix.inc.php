<?php





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

	function __construct()
	{
		parent::__construct();
		
		$this->options['table']	= array('enum',   '_EXP_TABLETOEXPORT', '', 'tables');
		$this->options['q']		= array('text',   '_EXP_RECORDSQUERY', '', 60);
		$this->options['dummy']	= array('remark', '_EXP_RECORDSQREMARK');
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
		
		if( $this->currTableType == TABLE_EXP_TYPE_NORMALDATA ) {
			if( $this->currTable == $this->base_table ) {
				$this->record_cnt_base = $this->currTableCnt;
			}
			else {
				$test = Table_Find_Def($this->currTable, false);
				if( $test && !$test->is_only_secondary($temp, $temp) ) {
					$this->other_tables[] = $this->currTable;
					$this->record_cnt_others += $this->currTableCnt;
				}
			}
		}
	}



	function declareStart()
	{
		$this->currFieldNames   = '';
		$this->currFieldDeclare = '';
	}
	function declareField($name, $rowtype)
	{
		$this->currFieldNames   .= ($this->currFieldNames?   ', ' : '') . $name;
		$this->currFieldDeclare .= ($this->currFieldDeclare? ', ' : '') . $name . ' ' . ($rowtype==TABLE_INT? 'INTEGER' : 'TEXT');
	}
	function declareEnd()
	{
		$sql = "CREATE TABLE {$this->currPrefixNTable} ({$this->currFieldDeclare});";
		$this->sqliteOb->query($sql);
	}



	function recordStart()
	{
		$this->currValues = '';
	}
	function recordField($data)
	{
		$this->currValues .= ($this->currValues? ', ' : '');
		$this->currValues .= $this->sqliteOb->quote($data);
	}
	function recordEnd()
	{
		$sql = "INSERT INTO {$this->currPrefixNTable} ({$this->currFieldNames}) VALUES ({$this->currValues});";
		$this->sqliteOb->query($sql);
		$this->currTableCnt++;
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
		if( !Table_Find_Def($this->base_table, false /*no access check*/) ) $this->progress_abort('Ungültige Tabelle.');
	
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
					$this->declareField('ini_key', TABLE_TEXT);
					$this->declareField('ini_value', TABLE_TEXT);
				$this->declareEnd();
				$this->recordIni('version',				$mixfile->get_code_version());
				$this->recordIni('base_table',			$this->base_table);
				$this->recordIni('other_tables',		implode(',', $this->other_tables));
				$this->recordIni('query', 				$param['q']);
				$this->recordIni('sync_src', 			$sync_tools->get_sync_src());
				$this->recordIni('record_cnt_base',		intval($this->record_cnt_base));
				$this->recordIni('record_cnt_others',	intval($this->record_cnt_others));
				$this->recordIni('export_host',			$_SERVER['HTTP_HOST']);
				$this->recordIni('export_user',			user_ascii_name(intval($_SESSION['g_session_userid'])));
				$this->recordIni('export_start_time',	strftime("%Y-%m-%d %H:%M:%S", $export_start_time));
			$this->tableEnd();
		
			$this->sqliteOb->close();
		}
	}
};

