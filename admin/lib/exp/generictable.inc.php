<?php



define('TABLE_EXP_TYPE_NORMALDATA',		1);
define('TABLE_EXP_TYPE_ENUM',			2);
define('TABLE_EXP_TYPE_EXTRA', 			3);




class EXP_GENERICTABLE_CLASS extends EXP_PLUGIN_CLASS
{
	private $attrasids;		// whether to export attributes etc. into additional tables
	private $export_passwords;


	function __construct()
	{
		$this->db_phys = new DB_Admin('use_phys_connection');
		parent::__construct();
	}
	
	
	// functions that should be implemented in derived classes
	function tableStart($name, $type)		{ die('tableStart() missing');	}
	function tableEnd()						{ die('tableEnd() missing');	}

	function declareStart()					{ die('declareStart() missing');}
	function declareField($name, $type)		{ die('declareField() missing');}
	function declareEnd()					{ die('declareEnd() missing');	}

	function recordStart()					{ die('recordStart() missing');	}
	function recordField($data)				{ die('recordField() missing');	}
	function recordEnd()					{ die('recordEnd() missing');	}
	

	// getTableDefLinks() returns an array with information about how tables are linked with each other
	private function getTableDefLinks()
	{
		$tdl = array();
		global $Table_Def;
		for( $t = 0; $t < sizeof((array) $Table_Def); $t++ ) {
			$table = $Table_Def[$t];
			$rows  = $table->rows;
			for( $r = 0; $r < sizeof((array) $rows); $r++ ) {
				$row = $rows[$r];
				switch( $row->flags&TABLE_ROW ) {
					case TABLE_SATTR:
						$tdl[ $table->name ]['fields']  .= ',' . $row->name;
						$tdl[ $table->name ]['sattr'][] = array($row->addparam->name, '', $row->name);
						break;
					case TABLE_MATTR:
						$tdl[ $table->name ]['mattr'][] = array($row->addparam->name, $table->name.'_'.$row->name, 'attr_id');
						break;
					case TABLE_SECONDARY:
						$tdl[ $table->name ]['mattr'][] = array($row->addparam->name, $table->name.'_'.$row->name, 'secondary_id');
						break;
				}
			}
		}
		return $tdl;
	}
	
	//
	// export a normal table
	//
	private function exportNormalTable($table, $select)
	{
		// get table definition
		$tableDef	= Table_Find_Def($table);
		$rows		= $tableDef->rows;
		$rowsCount	= sizeof((array) $rows);

		// start table
		$this->tableStart($table, TABLE_EXP_TYPE_NORMALDATA);

		// collect row information, declare start
		$rowflags = array();
		$rowtypes = array();
		$rownames = array();
		$this->declareStart();
			$this->declareField('id', TABLE_INT);
		
			for( $r = 0; $r < $rowsCount; $r++ ) {
				$rownames[$r] = $rows[$r]->name;
				$rowflags[$r] = $rows[$r]->flags;
				$rowtypes[$r] = $rows[$r]->flags & TABLE_ROW;
	
				switch( $rowtypes[$r] ) 
				{
					case TABLE_ENUM:
					case TABLE_BITFIELD:
						if( $this->attrasids ) {
							$this->enums[] = array("{$table}_{$rownames[$r]}", $rows[$r]->addparam); 
							$this->declareField($rownames[$r], TABLE_INT);
						}
						else {
							$this->declareField($rownames[$r], TABLE_TEXT);
						}
						break;
					
					case TABLE_SATTR:
						if( $this->attrasids )
						{
							$this->declareField($rownames[$r], TABLE_INT);
						}
						else
						{
							$this->declareField($rownames[$r], TABLE_TEXT);
						}			
						break;
					
					case TABLE_MATTR:
						if( $this->attrasids )
						{
							$this->links[] = array("{$table}_{$rownames[$r]}", 'attr_id');
						}
						else
						{
							$this->declareField($rownames[$r], TABLE_TEXT);
						}						
						break;

					case TABLE_SECONDARY:
						if( $this->attrasids )
						{
							$this->links[] = array("{$table}_{$rownames[$r]}", 'secondary_id');
						}
						else
						{
							$this->declareField($rownames[$r], TABLE_TEXT);
						}						
						break;
					
					default:
						$this->declareField($rownames[$r], $rowtypes[$r]);
						break;
				}
			}
		
			if( $this->export_sync_src ) {
				$this->declareField('sync_src', 		TABLE_INT);
			}
			
			$this->declareField('user_created', 	TABLE_INT);
			$this->declareField('user_modified', 	TABLE_INT);
			$this->declareField('user_grp', 		TABLE_INT);
			$this->declareField('user_access', 		TABLE_INT);
			$this->declareField('date_created', 	TABLE_DATETIME);
			$this->declareField('date_modified', 	TABLE_DATETIME);
		$this->declareEnd();
		
		// write data
		
		$db  = $this->db_phys;
		$dba = new DB_Admin;
		
		
			$limit_records_this_loop = 0;
			$db->query($select);
			while( $db->next_record() ) 
			{
				$limit_records_this_loop++;
				$this->recordStart();
					$this->recordField($db->f('id'));
					
					for( $r = 0; $r < $rowsCount; $r++ ) 
					{
						switch( $rowtypes[$r] ) 
						{
							case TABLE_ENUM:
								if( $this->attrasids ) {
									$this->recordField($db->f($rownames[$r]));
								}
								else {
									$this->recordField(trim($tableDef->get_enum_summary($r, $db->f($rownames[$r]))));
								}
								break;
							
							case TABLE_BITFIELD:
								if( $this->attrasids ) {
									$this->recordField($db->f($rownames[$r]));
								}
								else {
									$this->recordField( trim( $tableDef->get_bitfield_summary($r, $db->f($rownames[$r]), $dummy) ) );
								}
								break;
							
							case TABLE_SATTR:
								if( $this->attrasids ) {
									$this->recordField($db->f($rownames[$r]));
								}
								else {
									$attrid = $db->f($rownames[$r]);
									if( $attrid ) {
										$temp = $tableDef->rows[$r]->addparam->get_summary($attrid, '/' /*value seperator*/);
									}
									else {
										$temp = '';
									}
									$this->recordField($temp);
								}							
								break;
							
							case TABLE_MATTR:
								if( !$this->attrasids ) {
									$temp = '';
									$dba->query("SELECT attr_id FROM {$tableDef->name}_{$rownames[$r]} WHERE primary_id=" . $db->f('id'));
									while( $dba->next_record() ) {
										$attrid = $dba->f('attr_id');
										if( $temp != '' ) $temp .= ', ';
										$temp .= $tableDef->rows[$r]->addparam->get_summary($attrid, '/' /*value seperator*/);
									}
									$this->recordField($temp);
								}
								break;

							case TABLE_SECONDARY:
								if( !$this->attrasids ) {
									$temp = '';
									$dba->query("SELECT secondary_id FROM {$tableDef->name}_{$rownames[$r]} WHERE primary_id=" . $db->f('id'));
									while( $dba->next_record() ) {
										$attrid = $dba->f('secondary_id');
										if( $temp != '' ) $temp .= ', ';
										$temp .= $tableDef->rows[$r]->addparam->get_summary($attrid, '/' /*value seperator*/);
									}
									$this->recordField($temp);
								}
								break;
							
							case TABLE_PASSWORD:
								$this->recordField($this->export_passwords? 
									$db->fs($rownames[$r])
								  : '' ); // default: protect the user passwords by just not exporting them 
								break;
							
							default:
								$this->recordField($db->fs($rownames[$r]));
								break;
						}
					}
					
					if( $this->export_sync_src ) {
						$this->recordField($db->f('sync_src'));
					}
					
					$this->recordField($db->f('user_created'));
					$this->recordField($db->f('user_modified'));
					$this->recordField($db->f('user_grp'));
					$this->recordField($db->f('user_access'));
					$this->recordField($db->f('date_created'));
					$this->recordField($db->f('date_modified'));
				
				$this->recordEnd();

				if( ( $limit_records_this_loop % 200 ) == 0 )
					$this->progress_info(htmlconstant('_EXP_NRECORDSDONE___', $tableDef->descr, $limit_records_this_loop));
				
			}

			
		
		// end table
		$this->tableEnd();
	}
	
	private function exportAttrTable($table_name, $attr_name, $idsStr)
	{
		$table_started = false;
		$limit_records_this_loop = 0;
		$db = $this->db_phys;
		$db->query("SELECT * FROM $table_name WHERE primary_id IN ($idsStr) ORDER BY primary_id, structure_pos");
		while( $db->next_record() )
		{
			if( !$table_started )
			{
				$this->tableStart($table_name, TABLE_EXP_TYPE_NORMALDATA);
				$this->declareStart();
					$this->declareField('primary_id', TABLE_INT);
					$this->declareField($attr_name, TABLE_INT);
					$this->declareField('structure_pos', TABLE_INT);
				$this->declareEnd();
				$table_started = true;
			}
		
			$limit_records_this_loop++;
			$this->recordStart();
				$this->recordField($db->f('primary_id'));
				$this->recordField($db->f($attr_name));
				$this->recordField($db->f('structure_pos'));
			$this->recordEnd();

			if( ($limit_records_this_loop % 200) == 0 )
				$this->progress_info(htmlconstant('_EXP_NRECORDSDONE___', $table_name, $limit_records_this_loop));
		}
		
		if( $table_started ) {
			$this->tableEnd();
		}
	}
	
	
	
	//
	// export start routine
	//
	function export($param)
	{
		// initialisation
		$this->attrasids		= $param['attrasids'];
		$this->export_passwords	= $param['export_passwords'];
		$this->export_sync_src	= $param['export_sync_src'];
		$this->tdl          	= $this->getTableDefLinks();
		$this->enums			= array();
		$query_queue 			= array();
		$stuff_to_write			= array();
		
		// init query queue with the base table
		$db = $this->db_phys;
		$table = $param['table']; if( !Table_Find_Def($table, false /*no access checkt*/) ) $this->progress_abort('Ungültige Tabelle.');
		require_once('eql.inc.php'); 
		require_lang('lang/dbsearch');
		$eql2sql = new EQL2SQL_CLASS($table);
		$sql = $eql2sql->eql2sql($param['q'], 'id'.$this->tdl[$table]['fields']);
		if( $sql == '0' ) {
			$this->progress_abort($eql2sql->lastError);
		}
		array_push($query_queue, array($table, $sql));

		// loop through query queue until it is empty
		$affected_tables = array();
		$cntAll = 0;
		while( sizeof((array) $query_queue) )
		{
			list($table, $sql) = array_shift($query_queue);

					$stuff_to_check = array();
					$idsHere = array();
					$db->query($sql);
					while( $db->next_record() ) {
						$id = $db->f('id');
						if( !$stuff_to_write[$table][$id] ) {
							$cntAll++;
							$idsHere[] = $id;
							$stuff_to_write[$table][$id] = 1;
							for( $i = 0; $i < sizeof((array) $this->tdl[$table]['sattr']); $i++ ) {
								$currTable = $this->tdl[$table]['sattr'][$i][0];
								$currId    = $db->f($this->tdl[$table]['sattr'][$i][2]);
								if( $currId && !$stuff_to_write[$currTable][$currId] ) {
									$stuff_to_check[ $currTable ][] = $currId;
								}
							}
						}
					}
					
					if( sizeof((array) $idsHere) ) {
					    $idsHere = implode(',', $idsHere);
					    for( $i = 0; $i < sizeof((array) $this->tdl[$table]['mattr']); $i++ )
						{
							$currTable = $this->tdl[$table]['mattr'][$i][0];
							$field     = $this->tdl[$table]['mattr'][$i][2];
							$db->query("SELECT $field FROM {$this->tdl[$table]['mattr'][$i][1]} WHERE primary_id IN ($idsHere);");
							while( $db->next_record() ) {
								$currId = $db->f($field);
								if( $currId && !$stuff_to_write[$currTable][$currId] ) {
									$stuff_to_check[ $currTable ][] = $currId;
								}
							}
						}
					}
					
					// add queries to the query queue for the new stuff to check
					if( $this->attrasids ) {
						reset($stuff_to_check);
						foreach($stuff_to_check as $currTable => $currIds) {
						    if( sizeof((array) $currIds) ) {
								$currIds = implode(',', $currIds);
								$sql = "SELECT id{$this->tdl[$currTable]['fields']} FROM $currTable WHERE id IN ($currIds);";
								array_push($query_queue, array($currTable, $sql));
							}
						}
					}
					
					$this->progress_info($cntAll . ' Datensätze gesammelt ...');
		}
		
		// write $stuff_to_write
		$any_records_written = false;
		reset($stuff_to_write);
		foreach($stuff_to_write as $table => $ids) {
		    if( sizeof((array) $ids) ) {
				$any_records_written = true;
				$ids = implode(',', array_keys($ids));
				$sql = "SELECT * FROM $table WHERE id IN ($ids);";
				$affected_tables[$table] = 1;
				$this->exportNormalTable($table, $sql);
				if( $this->attrasids ) {
				    for( $i = 0; $i < sizeof((array) $this->tdl[$table]['mattr']); $i++ ) {
						$this->exportAttrTable($this->tdl[$table]['mattr'][$i][1], $this->tdl[$table]['mattr'][$i][2], $ids);
					}
				}
			}
		}
		
		/* -- continue nevertheless - there may  be records to delete below ...
		if( !$any_records_written ) {
			$this->progress_abort("Keine Datensätze zum Exportieren gefunden.");
		}
		*/
		
		// write enums
		if( $param['enums'] )
		{
		    for( $t = 0; $t < sizeof((array) $this->enums); $t++ )
			{
				$this->tableStart($this->enums[$t][0], TABLE_EXP_TYPE_ENUM);
					$this->declareStart();
						$this->declareField('id', TABLE_INT);
						$this->declareField('name',  TABLE_TEXT);
					$this->declareEnd();
					
					$temp = explode('###', $this->enums[$t][1]);
					for( $i = 0; $i < sizeof($temp); $i += 2 )
					{
						$this->recordStart();
							$this->recordField($temp[$i]);
							$this->recordField(trim($temp[$i+1]));
						$this->recordEnd();
					}
				$this->tableEnd($this->enums[$t][0]);
			}
		}
		
		// export a list id all IDs (needed on the destination to find out deleted records)
		$sync_tools = new SYNC_TOOLS_CLASS();
		if( $param['export_ids'] )
		{
			$idsOut = 0;
			$this->tableStart('ids', TABLE_EXP_TYPE_EXTRA);
				$this->declareStart();
					$this->declareField('tble',  TABLE_TEXT);
					$this->declareField('ids',  TABLE_TEXT);
				$this->declareEnd();
				global $Table_Def;
				for( $t = 0; $t < sizeof((array) $Table_Def); $t++ )
				{
		
					$table = $Table_Def[$t]->name;
					if( ($table==$param['table'] /*always write the IDs of the base table, 
							this allows deletion of records even if there are no new records*/ || $affected_tables[ $table ]) && !$Table_Def[$t]->is_only_secondary($dummy, $dummy) ) { 
						$ids = '';
						$db->query("SELECT id FROM $table ORDER BY id;");
						while( $db->next_record() ) 
						{
							$ids .= ($ids==''? '' : ',') . $db->f('id');
							
							$idsOut++;
							if( $idsOut % 1000 == 0 )
								$this->progress_info($idsOut . ' IDs geschrieben ...');
							
							
						}
						$this->recordStart();
							$this->recordField($table);
							$this->recordField($ids);
						$this->recordEnd();
					}
				}
			$this->tableEnd();
		}
		
	}
};


