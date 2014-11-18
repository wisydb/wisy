<?php


class AUTOCOMPLETE_JSON_CLASS
{	
	private $debug = false;
	
	function __construct()
	{
		$this->json  = new G_JSON_CLASS;
		
		//if( substr($_SERVER['HTTP_HOST'], -6)=='.local' ) { $this->debug = true; }
	}
	
	// main class: tools
	// ------------------------------------------------------------------------
	
	private function _die($msg)
	{
		if( $this->debug ) {
			echo "[\"debug: " .$this->json->utf8_to_json($msg). "\"]";
		}
		else {
			echo "[]"; 
		}
		exit();
	}

	private function _find_first_summary_field($table, &$ret_field, &$ret_rowtype)
	{
		$this->_find_table_def($table, 'id', $temp_table_def, $dummy);
		for( $r = 0; $r < sizeof($temp_table_def->rows); $r++ ) {
			if( $temp_table_def->rows[$r]->flags & TABLE_SUMMARY ) {
				$ret_field = $temp_table_def->rows[$r]->name;
				$ret_rowtype = $temp_table_def->rows[$r]->flags & TABLE_ROW;
				return; // success
			}
		}

		$this->_die("no summary field defined for table $table."); 
	}		
	
	// add the LIKE-wildcard "%" at the end/beginning of a value and quote the string properly
	private function _quote_LIKE($hash_before = '', $value, $hash_after = '')
	{
		$value = strtr($value, array('%'=>"\\%", '_'=>"\\_"));
		if( $hash_before != '' ) $value  = '%' . $value;
		if( $hash_after  != '' ) $value .= '%';
		return $this->db->quote($value) . " ESCAPE " . $this->db->quote("\\");
	}
	
	// find a table definition and a row index, abort on errors
	private function _find_table_def($table, $field, &$ret_table_def, &$ret_row_index)
	{
		$ret_table_def = Table_Find_Def($table, 0 /*no ACL checks*/); 
		if( !$ret_table_def ) {
			$this->_die("bad table: $table");
		}
		
		$ret_row_index = -1;
		if( $field == 'id' // needed in _find_first_summary_field()
         || $field == 'createdby' || $field == 'modifiedby' || $field == 'group' ) {
			return; // no row index possible
		}
		
		require_once('eql.inc.php');
		
		for( $r = 0; $r < sizeof($ret_table_def->rows); $r++ ) {
			if( $ret_table_def->rows[$r]->name == $field
             || g_eql_normalize_func_name($ret_table_def->rows[$r]->name, 0) == $field	) {
				$ret_row_index = $r;
				break;
			}
		}

		if( $ret_row_index == -1 ) {
			$this->_die("bad or generic field $table.$field"); 
		}
	}
	
	
	// main class: fields of type TEXT
	// ------------------------------------------------------------------------

	function get_acnest_fields($table, $field)
	{
		$fields = array($field);
		$this->_find_table_def($table, $field, $table_def, $row_index);
		if( ($table_def->rows[$row_index]->flags & TABLE_ROW)==TABLE_TEXT
		 && $table_def->rows[$row_index]->flags & TABLE_ACNESTSTART  )
		{
			for( $r = $row_index + 1; $r < sizeof($table_def->rows); $r++ ) {
				if( ($table_def->rows[$r]->flags & TABLE_ROW)==TABLE_TEXT
				 &&  $table_def->rows[$r]->flags & TABLE_ACNEST ) {
					$fields[] = $table_def->rows[$r]->name;
				}
				else {
					break; // nest end
				}
			}
		}
		return $fields;
	}	
	
	private function _handle_TABLE_TEXT($table, $field, $referencable_only)
	{
		// query
		$fields = $this->get_acnest_fields($table, $field);
		$suggestions = array();

		if( preg_match('/^\d+$/', trim($this->term))) {
			$sql_cond = " id=" . intval($this->term) . ' ';
		}
		else {	
			$sql_cond = " $field LIKE " . $this->_quote_LIKE('%',$this->term,'%') . ' ';
		}
		
		$actypefield  = '';
		if( $table == 'stichwoerter' ) { // very special handling for this table, we make this more generic some time
			$actypefield = ', eigenschaften AS actype';
		}

		$sql_referencable = '';
		if( $referencable_only ) {
			$sql_referencable = ' AND user_access&73 '; // 73=0111
		}
		
		$sql = "SELECT " . implode(',', $fields) . $actypefield . ", id FROM $table 
					WHERE " . $sql_cond . $sql_referencable ." 
					GROUP BY ".implode(',', $fields)." 
					ORDER BY INSTR($field, '$this->term'), $field  
					LIMIT $this->limit;"; // 11:59 04.02.2014 "ORDER BY field" hinzugefügt; der andere Ansatz, die letzten Ergebnisse via "ORDER BY date_modified DESC" anzuzeigen ist zu unübersichtlich und nicht vorhersehbar
		$this->db->query($sql);	// LIKE and INSTR are not case sensitive!
		while( $this->db->next_record() ) 
		{
			// get ID and value
			$id = $this->db->fs('id');
			$value = $this->db->fs($field);
			
			// get label
			$href = "autocomplete.php?acdata=$table.$field"; 
			$label = '';
			$nest = array();
			for( $r = 0; $r < sizeof($fields); $r++ ) {
				$href .= "&v$r=".urlencode($this->db->fs($fields[$r]));
				$temp = $this->db->fs($fields[$r]);
				if( $temp!='' ) {
					// add separator
					if( $fields[$r-1]=='plz' && $this->db->fs('plz')!='' )	{ $label .= ' ';	}
					else 													{ $label .= $label==''? '' : ', '; 	}
					// add label
					$label .= isohtmlspecialchars($temp);
				}
				
				if( $r >= 1 ) {
					$nest[] = $temp; 
				}
			}
			$label .= '<span class="achref" onclick="return ac_href(\''.$href.'\');">&nbsp;&#8599;&nbsp;</span>';
			
			// add this suggestion
			$suggestions[] = array('label'=>$label, 'value'=>$value, 'id'=>$id, 'nest'=>$nest);
			if( $actypefield != '' ) {
				$actype = $this->db->fs('actype');
				if( $actype ) {
					$suggestions[sizeof($suggestions)-1]['actype'] = $this->db->fs('actype');
				}
			}
		}
	
		return $suggestions;
	}
	
	// main class: fields of type ENUM
	// ------------------------------------------------------------------------

	// _sort_callback() is used as an callback in usort() and sorts string with correct beginnings atop of the list
	private function _sort_callback($a, $b)
	{
		// prepend X if the string beginning matches the entered term (we want these hits at the beginning of the list)
		// prepend Y if not
		$atext = (strtolower(substr($a['value'], 0, strlen($this->term))) == strtolower($this->term)? 'X' : 'Y') . $a['value'];
		$btext = (strtolower(substr($b['value'], 0, strlen($this->term))) == strtolower($this->term)? 'X' : 'Y') . $b['value'];
		// normal comarision
		return strcasecmp($atext, $btext);
	}
	
	private function _handle_TABLE_ENUM($table, $field)
	{
		$suggestions = array();
		
		$this->_find_table_def($table, $field, $table_def, $row_index);
		$values = explode('###', $table_def->rows[$row_index]->addparam);

		for( $v = 0; $v < sizeof($values); $v+=2 ) 
		{
			$test = $values[$v+1];
			if( $test !='' 
			 && ($try==1 || strpos(strtolower($test), strtolower($this->term))!==false) )
			{
				$suggestions[] = array('label'=>isohtmlspecialchars($test), 'value'=>$test);
			}
		}
		
		// man könnte an dieser Stelle bei einer leeren Liste einfach alle Möglichen Optionen ausgeben; wir tun dies aber nicht, 
		// um konsistent zu TABLE_TEXT etc. zu bleiben
		
		usort($suggestions, array($this, '_sort_callback'));
		
		return $suggestions;
	}

	// main class: handling a request
	// ------------------------------------------------------------------------

	function handle_request()
	{
		header('Content-type: application/json');

		// some settings
		$this->db		= new DB_Admin;
		$this->limit	= 8;
	
		// get query entered by the user
		$this->term = trim(utf8_decode($_REQUEST['term']));
		if( $this->term=='' ) {
			$this->_die('nothing entered.');
		}
		
		// get the request scope from acdata
		$temp = explode('.', $_REQUEST['acdata']);
		$acdata_table		= $temp[0];
		$acdata_field		= $temp[1];
		
		if( $acdata_field == 'seeselect' )
		{
			$select_fields = explode('.', $_REQUEST['select']);
			if( $select_fields[0]=='' || $select_fields[0]=='ANY' || $select_fields[0]=='DUMMY' || $select_fields[0]=='OPTIONS' || $select_fields[0]=='job' )
			{
				$this->_die('cannot handle '.$select_fields[0]); 
			} 
			else if( sizeof($select_fields) == 2 || sizeof($select_fields) == 3 )
			{
				// search in secondary / attribute tables, max. 2 iterations needed
				$this->_find_table_def($acdata_table, $select_fields[0], $temp_table_def, $temp_row_index);
				$temp_table_def2 = $temp_table_def->rows[$temp_row_index]->addparam;
				if( sizeof($select_fields) == 3 )
				{
					$this->_find_table_def($temp_table_def2->name, $select_fields[1], $temp_table_def, $temp_row_index);
					$temp_table_def2 = $temp_table_def->rows[$temp_row_index]->addparam;
				}
				$suggest_table = $temp_table_def2->name;
				$suggest_field = $select_fields[sizeof($select_fields)-1];
			}
			else 
			{
				$suggest_table		= $acdata_table;
				$suggest_field		= $select_fields[0];
			}
		}
		else if( $acdata_field == 'user_created' || $acdata_field == 'user_modified' )   // needed in edit.php
		{
			$suggest_table		= 'user';
			$suggest_field		= 'name';
		}
		else if( $acdata_field == 'user_grp' )   // needed in edit.php
		{
			$suggest_table		= 'user_grp';
			$suggest_field		= 'name';
		}
		else
		{
			$suggest_table		= $acdata_table;
			$suggest_field		= $acdata_field;
		}
		
		// convert attribute tables to normal field lookups 
		$referencable_only = false;
		$this->_find_table_def($suggest_table, $suggest_field, $temp_table_def, $temp_row_index);
		$suggest_row_type = $temp_table_def->rows[$temp_row_index]->flags & TABLE_ROW;
		if( $suggest_row_type == TABLE_SECONDARY || $suggest_row_type == TABLE_SATTR || $suggest_row_type == TABLE_MATTR )
		{
			$referencable_only = true;
			$suggest_table = $temp_table_def->rows[$temp_row_index]->addparam->name;
			$this->_find_first_summary_field($suggest_table, $suggest_field, $suggest_row_type);
		}
		else if( $suggest_field == 'createdby' || $suggest_field == 'modifiedby' ) // needed in index.php
		{
			$suggest_table = 'user';
			$this->_find_first_summary_field($suggest_table, $suggest_field, $suggest_row_type);
		}
		else if( $suggest_field == 'group' ) // needed in index.php
		{
			$suggest_table = 'user_grp';
			$this->_find_first_summary_field($suggest_table, $suggest_field, $suggest_row_type);
		}
		
		// now, lookup for suggestions 
		$suggestions = array();
		switch( $suggest_row_type )
		{
			case TABLE_TEXT:
			case TABLE_INT:
				$suggestions = $this->_handle_TABLE_TEXT($suggest_table, $suggest_field, $referencable_only);
				break;
			
			case TABLE_ENUM:
			case TABLE_BITFIELD:
				$suggestions = $this->_handle_TABLE_ENUM($suggest_table, $suggest_field);
				break;
			
			default:
				$this->_die("type of $suggest_table.$suggest_field (row_type=$suggest_row_type) not supported");
				break;
		}
		
		// show "nothing found" on debug
		if( $this->debug && sizeof($suggestions) == 0 ) {
			$this->_die("no suggestions found for $suggest_table.$suggest_field (row_type=$suggest_row_type)");
		}
		
		// result output - format: [{"label":"label1","value":"value1"},{"label":"label2","value":"value2"}]
		$json = '[';
		for( $i = 0; $i < sizeof($suggestions); $i++ ) {
			$json .= $i? ', ' : '';
			$json .= '{';
				$inner = 0;
				reset($suggestions[$i]);
				while( list($cnt1, $cnt2) = each($suggestions[$i]) ) 
				{
					if( is_array($cnt2) ) {
						if( $cnt1 == 'nest' && sizeof($cnt2) > 0 ) {
							$json .= $inner? ', ' : '';
							$json .= '"nest": [';
								for( $n = 0; $n < sizeof($cnt2); $n++ ) {
									$json .= $n? ', ' : '';
									$json .= '"' . $this->json->utf8_to_json(utf8_encode($cnt2[$n])) . '"';
								}
							$json .= ']';
							$inner++;
						} 
					}
					else {
						$json .= $inner? ', ' : '';
						$json .= '"' . $this->json->utf8_to_json(utf8_encode($cnt1)) . '": "' . $this->json->utf8_to_json(utf8_encode($cnt2)) . '"';
						$inner++;
					}
				}
			$json .= '}';			
		}
		$json .= ']';
		echo $json;
	}
};

