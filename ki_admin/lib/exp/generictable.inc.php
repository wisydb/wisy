<?php

define('TABLE_EXP_TYPE_NORMALDATA',		1);
define('TABLE_EXP_TYPE_ENUM',			2);
define('TABLE_EXP_TYPE_EXTRA', 			3);

// > PHP7 !
define('NO_MANUAL_DECLARATION', array('sync_src', 
                                      'id', 
                                      'user_created', 
                                      'user_modified', 
                                      'user_grp', 
                                      'user_access'. 
                                      'date_created', 
                                      'date_modified', 
                                      'primary_id', 
                                      'structure_pos', 
                                      'primary_id', 
                                      'name', 
                                      'tble', 
                                      'ids')
       );



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
	function tableStart($name, $type) { die('tableStart() missing'); }
	function tableEnd() { die('tableEnd() missing'); }
	function declareStart() { die('declareStart() missing'); }
	function declareField($name, $type, $ignoreAsDuplicate = false) { die('declareField() missing'); }
	function declareEnd() { die('declareEnd() missing'); }
	function recordStart() { die('recordStart() missing'); }
	function recordField($data) { die('recordField() missing'); }
	function recordEnd() { die('recordEnd() missing'); }
	

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
				$rowFlags = isset($row->flags) ? $row->flags : null;
				$rowName = isset($row->name) ? $row->name : null;
				$addParamName = isset($row->addparam->name) ? $row->addparam->name : null;
				$tableName = isset($table->name) ? $table->name : '';
				
				switch( $rowFlags&TABLE_ROW ) {
					case TABLE_SATTR:
					    if( isset($tdl[ $tableName ]['fields']) )
					       $tdl[ $tableName ]['fields'] .= ',' . $rowName;
					    else 
					       $tdl[ $tableName ]['fields'] = ',' . $rowName;
					    
					    $tdl[ $tableName ]['sattr'][] = array($addParamName, '', $rowName);
						break;
					case TABLE_MATTR:
					    $tdl[ $tableName ]['mattr'][] = array($addParamName, $tableName.'_'.$rowName, 'attr_id');
						break;
					case TABLE_SECONDARY:
					    $tdl[ $tableName ]['mattr'][] = array($addParamName, $tableName.'_'.$rowName, 'secondary_id');
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
		  $this->declareField('id', TABLE_INT, true);
		
			for( $r = 0; $r < $rowsCount; $r++ ) {
			    $rownames[$r] = isset( $rows[$r]->name ) ? $rows[$r]->name : null;
			    $rowflags[$r] = isset( $rows[$r]->flags ) ? $rows[$r]->flags : null;
			    $flags = isset( $rows[$r]->flags ) ? $rows[$r]->flags : null;
				$rowtypes[$r] = $flags & TABLE_ROW;
				
				if( in_array($rownames[$r], NO_MANUAL_DECLARATION) ) {
				        // a field is being declared in db.inc.php, while also being declared in this file => duplicate => add to NO_MANUAL_DECLARATION
				        $msg = "<br>Das Feld: <b>{$rownames[$r]}</b> in der Tabelle <b>{$table}</b> wurde manuell in db.inc.php deklariert. Deklariere hier noch nicht. <b>&Uuml;berspringe, da sonst doppelt...</b><br>";
				        // echo $msg;
				        $this->evenmore_info( strip_tags(html_entity_decode($msg)) );
				        continue;
				}
	
				switch( $rowtypes[$r] ) 
				{
					case TABLE_ENUM:
					case TABLE_BITFIELD:
					    if( isset( $this->attrasids ) && $this->attrasids ) {
					        $this->enums[] = array("{$table}_{$rownames[$r]}", ( isset($rows[$r]->addparam) ? $rows[$r]->addparam : null) ); 
							$this->declareField($rownames[$r], TABLE_INT);
						}
						else {
							$this->declareField($rownames[$r], TABLE_TEXT);
						}
						break;
					
					case TABLE_SATTR:
					    if( isset( $this->attrasids ) && $this->attrasids )
						{
							$this->declareField($rownames[$r], TABLE_INT);
						}
						else
						{
							$this->declareField($rownames[$r], TABLE_TEXT);
						}			
						break;
					
					case TABLE_MATTR:
					    if( isset( $this->attrasids ) && $this->attrasids )
						{
							$this->links[] = array("{$table}_{$rownames[$r]}", 'attr_id');
						}
						else
						{
							$this->declareField($rownames[$r], TABLE_TEXT);
						}						
						break;

					case TABLE_SECONDARY:
					    if( isset( $this->attrasids ) && $this->attrasids )
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
		
			if( isset( $this->export_sync_src ) && $this->export_sync_src )
				$this->declareField('sync_src', 		TABLE_INT, true);
			
			$this->declareField('user_created', 	TABLE_INT, true);
			$this->declareField('user_modified', 	TABLE_INT, true);
			$this->declareField('user_grp', 		TABLE_INT, true);
			$this->declareField('user_access', 		TABLE_INT, true);
			$this->declareField('date_created', 	TABLE_DATETIME, true);
			$this->declareField('date_modified', 	TABLE_DATETIME, true);
			
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
					    if( in_array($rownames[$r], NO_MANUAL_DECLARATION) ) {
					        // a field is being declared in db.inc.php, while also being declared in this file => duplicate => add to NO_MANUAL_DECLARATION
					        $msg = "<br>Speichere das Datum '<b>{$db->f($rownames[$r])}</b>' des Feldes <b>{$rownames[$r]}</b> in der Tabelle <b>{$table}</b> noch nicht, denn <b>{$rownames[$r]}</b> "
					             . "wurde manuell in db.inc.php deklariert. <b>&Uuml;berspringe, da sonst doppelt...</b><br>";
					        // echo $msg;
					        $this->evenmore_info( strip_tags(html_entity_decode($msg)) );
					        continue;
					    }
					    
						switch( $rowtypes[$r] ) 
						{
							case TABLE_ENUM:
							    if( isset( $this->attrasids ) && $this->attrasids ) {
							        $this->recordField($db->f( (isset($rownames[$r]) ? $rownames[$r] : '') ));
								}
								else {
									$this->recordField(trim($tableDef->get_enum_summary($r, $db->f($rownames[$r]))));
								}
								break;
							
							case TABLE_BITFIELD:
							    if( isset( $this->attrasids ) && $this->attrasids ) {
							        $this->recordField($db->f( (isset($rownames[$r]) ? $rownames[$r] : '') ));
								}
								else {
									$this->recordField( trim( $tableDef->get_bitfield_summary($r, $db->f($rownames[$r]), $dummy) ) );
								}
								break;
							
							case TABLE_SATTR:
							    if( isset( $this->attrasids ) && $this->attrasids ) {
							        $this->recordField($db->f( (isset($rownames[$r]) ? $rownames[$r] : '') ));
								}
								else {
								    $attrid = $db->f( (isset($rownames[$r]) ? $rownames[$r] : '') );
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
							    if( !isset( $this->attrasids ) || !$this->attrasids ) {
									$temp = '';
									$dba->query("SELECT attr_id FROM {$tableDef->name}_{$rownames[$r]} WHERE primary_id=" . $db->f('id'));
									while( $dba->next_record() ) {
										$attrid = $dba->f('attr_id');
										if( $temp != '' ) $temp .= ', ';
										
										if( isset($tableDef->rows[$r]) )
										  $temp .= $tableDef->rows[$r]->addparam->get_summary($attrid, '/' /*value seperator*/);
									}
									$this->recordField($temp);
								}
								break;

							case TABLE_SECONDARY:
							    if( !isset( $this->attrasids ) || !$this->attrasids ) {
									$temp = '';
									$dba->query("SELECT secondary_id FROM {$tableDef->name}_{$rownames[$r]} WHERE primary_id=" . $db->f('id'));
									while( $dba->next_record() ) {
										$attrid = $dba->f('secondary_id');
										if( $temp != '' ) $temp .= ', ';
										
										if( isset($tableDef->rows[$r]) )
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
					
					if( isset( $this->export_sync_src ) && $this->export_sync_src ) {
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
		    if( !isset($table_started) || !$table_started )
			{
				$this->tableStart($table_name, TABLE_EXP_TYPE_NORMALDATA);
				$this->declareStart();
				$this->declareField('primary_id', TABLE_INT, true);
					$this->declareField($attr_name, TABLE_INT);
					$this->declareField('structure_pos', TABLE_INT, true);
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
		
		if( isset($table_started) && $table_started ) {
			$this->tableEnd();
		}
	}
	
	
	
	//
	// export start routine
	//
	function export($param)
	{
		// initialisation
	    $this->attrasids		= isset( $param['attrasids'] ) ? $param['attrasids'] : null;
	    $this->export_passwords	= isset( $param['export_passwords'] ) ? $param['export_passwords'] : null;
	    $this->export_sync_src	= isset( $param['export_sync_src'] ) ? $param['export_sync_src'] : null;
		$this->tdl          	= $this->getTableDefLinks();
		$this->enums			= array();
		$query_queue 			= array();
		$stuff_to_write			= array();
		
		// init query queue with the base table
		$db = $this->db_phys;
		$table = isset( $param['table'] ) ? $param['table'] : null; 
		if( !Table_Find_Def($table, false /*no access checkt*/) ) $this->progress_abort('Ung'.ueJS.'ltige Tabelle.');
		require_once('eql.inc.php'); 
		require_lang('lang/dbsearch');
		$eql2sql = new EQL2SQL_CLASS($table);
		$tableFields = isset( $this->tdl[$table]['fields'] ) ? $this->tdl[$table]['fields'] : '';
		$sql = $eql2sql->eql2sql( ( isset( $param['q'] ) ? $param['q'] : null ), 'id'.$tableFields);
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
						if( !isset($stuff_to_write[$table][$id]) || !$stuff_to_write[$table][$id] ) {
							$cntAll++;
							$idsHere[] = $id;
							$stuff_to_write[$table][$id] = 1;
							
							if( isset($this->tdl[$table]['sattr']) ) {
    							for( $i = 0; $i < sizeof((array) $this->tdl[$table]['sattr']); $i++ ) {
    							    $currTable = isset( $this->tdl[$table]['sattr'][$i][0] ) ? $this->tdl[$table]['sattr'][$i][0] : null;
    							    $currId    = $db->f( ( isset( $this->tdl[$table]['sattr'][$i][2] ) ? $this->tdl[$table]['sattr'][$i][2] : null ) );
    							    if( $currId && (!isset($stuff_to_write[$currTable][$currId]) || !$stuff_to_write[$currTable][$currId]) ) {
    									$stuff_to_check[ $currTable ][] = $currId;
    								}
    							}
							}
						}
					}
					
					if( sizeof((array) $idsHere) ) {
					    $idsHere = implode(',', $idsHere);
					    
					    if( isset($this->tdl[$table]['mattr']) ) {
					      for( $i = 0; $i < sizeof((array) $this->tdl[$table]['mattr']); $i++ )
						  {
						    $currTable = isset( $this->tdl[$table]['mattr'][$i][0] ) ? $this->tdl[$table]['mattr'][$i][0] : null;
						    $field     = isset( $this->tdl[$table]['mattr'][$i][2] ) ? $this->tdl[$table]['mattr'][$i][2] : null;
							$db->query("SELECT $field FROM {$this->tdl[$table]['mattr'][$i][1]} WHERE primary_id IN ($idsHere);");
							while( $db->next_record() ) {
								$currId = $db->f($field);
								if( $currId && (!isset($stuff_to_write[$currTable][$currId]) || !$stuff_to_write[$currTable][$currId]) ) {
									$stuff_to_check[ $currTable ][] = $currId;
								}
							}
						  }
					    }
					}
					
					// add queries to the query queue for the new stuff to check
					if( isset( $this->attrasids ) && $this->attrasids ) {
					    reset($stuff_to_check);
					    foreach($stuff_to_check as $currTable => $currIds) {
					        if( sizeof((array) $currIds) ) {
					            $currIds = implode(',', $currIds);
					            
					            $addFields = $this->tdl[$currTable]['fields'] ?? '';
					            $sql = "SELECT id{$addFields} FROM $currTable WHERE id IN ($currIds);";
					            array_push($query_queue, array($currTable, $sql));
					            
					        }
					    }
					}
					
					$this->progress_info($cntAll . ' Datens'.aeJS.'tze gesammelt ...');
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
				if( isset( $this->attrasids ) && $this->attrasids && isset($this->tdl[$table]['mattr']) ) {
				    for( $i = 0; $i < sizeof((array) $this->tdl[$table]['mattr']); $i++ ) {
						$this->exportAttrTable($this->tdl[$table]['mattr'][$i][1], $this->tdl[$table]['mattr'][$i][2], $ids);
					}
				}
			}
		}
		
		/* -- continue nevertheless - there may  be records to delete below ...
		if( !$any_records_written ) {
			$this->progress_abort("Keine Datensaetze zum Exportieren gefunden.");
		}
		*/
		
		// write enums
		if( isset($param['enums']) && $param['enums'] )
		{
		    for( $t = 0; $t < sizeof((array) $this->enums); $t++ )
			{
				$this->tableStart($this->enums[$t][0], TABLE_EXP_TYPE_ENUM);
					$this->declareStart();
					    $this->declareField('id', TABLE_INT, true);
					    $this->declareField('name',  TABLE_TEXT, true);
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
		if( isset($param['export_ids']) && $param['export_ids'] )
		{
			$idsOut = 0;
			$this->tableStart('ids', TABLE_EXP_TYPE_EXTRA);
				$this->declareStart();
				    $this->declareField('tble',  TABLE_TEXT, true);
				    $this->declareField('ids',  TABLE_TEXT, true);
				$this->declareEnd();
				global $Table_Def;
				for( $t = 0; $t < sizeof((array) $Table_Def); $t++ )
				{
		
				    $table = isset($Table_Def[$t]->name) ? $Table_Def[$t]->name : '';
					if( (isset($param['table']) && $table==$param['table'] /*always write the IDs of the base table, 
					    this allows deletion of records even if there are no new records*/ || isset($affected_tables[ $table ]) && $affected_tables[ $table ]) && !$Table_Def[$t]->is_only_secondary($dummy, $dummy) ) { 
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