<?php 



class print_list_class extends print_plugin_class
{
	var $options;			// the options to use
	var $param;				// parameters from the options
	
	function print_list_class()
	{
		$this->options['fontsize']	= array();
		$this->options['pagebreak'] = array();
		$this->options['repeathead']= array('check', htmlconstant('_PRINT_PAGEBREAK_REPEATHEAD'), 1);
	}

	function print_table_list_start($table)
	{
		$ret = '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
		return $ret;
	}

	function print_table_list_end($table)
	{
		$ret = '</table>';
		return $ret;
	}

	function print_table_list_row__(&$db1, &$db2, $table, $id, &$heads, &$cells, $prefix = '')
	{
		// get reocord
		$tableDef = Table_Find_Def($table);
		$db1->query("SELECT * FROM $table WHERE id=$id");
		$db1->next_record(); // as the head is also created in this function, no check for FALSE

		// special rows (1)
		if( $prefix == '' ) {
			//if( $this->columns['id'] ) { -- the ID is no longer stored in index.view.<table>.columns, the ID column is always visible instead
				$heads[] = htmlconstant('_ID');
				$cells[] = $db1->f('id');
			//}
		}
		
		// go through all rows		
		for( $r = 0; $r < sizeof((array) $tableDef->rows); $r++ ) 
		{
			$rowflags		= intval($tableDef->rows[$r]->flags);
			$rowtype		= $rowflags&TABLE_ROW;
			
			if( $rowtype == TABLE_SECONDARY )
			{
				// get all secondary ids
				$secondaryIds = array();
				$db2->query("SELECT secondary_id FROM $tableDef->name" . '_' . $tableDef->rows[$r]->name . " WHERE primary_id=$id ORDER BY structure_pos");
				while( $db2->next_record() ) {
					$secondaryIds[] = $db2->f('secondary_id');
				}
				
				$tempCells = array();
				$this->print_table_list_row__($db2, $dummy, $tableDef->rows[$r]->addparam->name, 0, $heads, $tempCells, "{$tableDef->rows[$r]->name}.");
				for( $i = 0; $i < sizeof($tempCells); $i++ ) {
					$tempCells[$i] = array();
				}
				
				for( $i = 0; $i < sizeof($secondaryIds); $i++ ) {
					$currCells = array();
					$this->print_table_list_row__($db2, $dummy, $tableDef->rows[$r]->addparam->name, $secondaryIds[$i], $dummy, $currCells, "{$tableDef->rows[$r]->name}.");
					for( $c = 0; $c < sizeof($currCells); $c++ ) {
						if( $currCells[$c]!='' && !in_array($currCells[$c], $tempCells[$c]) ) {
							$tempCells[$c][] = $currCells[$c];
						}
					}
				}

				for( $i = 0; $i < sizeof($tempCells); $i++ ) {
					$cells[] = implode('; ', $tempCells[$i]);
				}
			}
			else
			{
			    if( ($this->columns[$prefix.$tableDef->rows[$r]->name])
			     || (sizeof((array) $this->columns)==0 && $rowflags&TABLE_LIST) )
				{
					$heads[] = $tableDef->rows[$r]->descr;
					$cells[] = preview_field($tableDef, $r, $db1, 0);
				}
			}
		}
		
		// special rows (2)
		if( $prefix == '' ) {
			if( $this->columns['date_created'] ) {
				$heads[] = htmlconstant('_OVERVIEW_CREATED');
				$cells[] = isohtmlentities(sql_date_to_human($db1->f('date_created'), 'datetime'));
			}
			
			if( $this->columns['user_created'] ) {
				$heads[] = htmlconstant($this->columns['date_created']? '_OVERVIEW_BY' : '_OVERVIEW_CREATEDBY');
				$cells[] = user_html_name($db1->f('user_created'));
			}			

			if( $this->columns['date_modified'] ) {
				$heads[] = htmlconstant('_OVERVIEW_MODIFIED');
				$cells[] = isohtmlentities(sql_date_to_human($db1->f('date_modified'), 'datetime'));
			}

			if( $this->columns['user_modified'] ) {
				$heads[] = htmlconstant($this->columns['date_modified']? '_OVERVIEW_BY' : '_OVERVIEW_MODIFIEDBY');
				$cells[] = user_html_name($db1->f('user_modified'));
			}
			
			if( $this->columns['user_grp'] ) {
				$heads[] = htmlconstant('_GROUP');
				$cells[] = grp_html_name($db1->f('user_grp'));
			}

			if( $this->columns['user_access'] ) {
				$heads[] = htmlconstant('_RIGHTS');
				$cells[] = access_to_human($db1->f('user_access'));
			}
			
			if( $this->columns['REFERENCES'] ) {
				$heads[] = htmlconstant('_REFABBR');
				$cells[] = $tableDef->num_references($id, $dummy);
			}
		}
	}

	function print_table_list_row(&$db1, &$db2, $table, $id, $addHead)
	{
		$heads = array();
		$cells = array();
		$row .= $this->print_table_list_row__($db1, $db2, $table, $id, $heads, $cells);

		$ret = '';
		if( $addHead ) {
			global $Table_Shortnames;
			$ret .= '<tr>';
				for( $i = 0; $i < sizeof($heads); $i++ ) {
					$head = trim($heads[$i]);
					if( $Table_Shortnames[$head] ) {
						$head = $Table_Shortnames[$head];
					}
					$ret .= '<td class="prhd">' . $head . '</td>';
				}
			$ret .= '</tr>';
		}

		$ret .= '<tr>';
			for( $i = 0; $i < sizeof($cells); $i++ ) {
				$ret .= '<td class="prcl">' . ($cells[$i]===''? '&nbsp;' : $cells[$i]) . '</td>';
			}
		$ret .= '</tr>';
		
		return $ret;
	}
	
	function printdo()
	{
		global $site;
		
		require('print_tools.inc.php');
		require_lang('lang/overview');
		$table		= $this->param['table'];
		$pagebreak	= $this->param['pagebreak'];

		// get the columns to show
		$this->columns = array();
		$temp = str_replace(' ', '', regGet("index.view.$table.columns", ''));
		if( $temp != '' ) {
			$temp = explode(',', $temp);
			for( $i = 0; $i < sizeof($temp); $i++ ) {
				$this->columns[$temp[$i]] = 1;
			}
		}

		$site->addScript('print.js');
		$site->pageStart(array('css'=>'print', 'pt'=>$this->param['fontsize']));
			$dbs1 = new DB_Admin();
			$dbs2 = new DB_Admin();
			echo $this->print_table_list_start($table);
				$records = 0;
				$addHead = 1;
				$db = new DB_Admin();
				$db->query($this->param['sql']);
				while( $db->next_record() ) {
					if( $records ) {
						if( $pagebreak && $records%$pagebreak==0 ) {
							echo $this->print_table_list_end($table);
							echo '<br style="page-break-after:always;" />';
							echo $this->print_table_list_start($table);
							$addHead = $this->param['repeathead']? 1 : 0;
						}
					}
					echo $this->print_table_list_row($dbs1, $dbs2, $table, $db->f('id'), $addHead);
					$records++;
					$addHead = 0;
				}
			echo $this->print_table_list_end($table);
			
			if( $this->param['cnt']!=1 ) {
				echo '&nbsp;<br />' . htmlconstant('_PRINT_QUERY', isohtmlentities($this->param['eql']), $this->param['cnt']);
				if( $this->param['rows'] != $this->param['cnt'] ) {
					echo ' ' . htmlconstant('_PRINT_QUERY_RANGE', $this->param['offset']+1, $this->param['offset']+$this->param['rows']);
				}
			}
			
		$site->pageEnd();
	}
}

