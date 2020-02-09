<?php

class G_SUMMARIZER_CLASS
{
	private $db; // may be sqlite or mysql

	function __construct($db)
	{
		$this->db = $db;
	}

	private function get_text($table, $id, $field)
	{
		$sql = "SELECT $field FROM $table WHERE id=$id";
		$this->db->query($sql);
		if( $this->db->next_record() ) {
			$ret = $this->db->fs($field);
		}
		else {
			$ret = $id;
		}
		
		return $ret;
	}

	function get_summary($table, $id, $sep = '', $force_TABLE_LIST = 0)
	{	
		$ret = '';
		
		$tableDef = Table_Find_Def($table, 0);

		$list_or_summary = TABLE_LIST; // if no summary rows are specified, TABLE_LIST is used
		if( !$force_TABLE_LIST ) {
		    for( $r = 0; $r < sizeof((array) $tableDef->rows); $r++ )
			{
				if( $tableDef->rows[$r]->flags & TABLE_SUMMARY ) {
					$list_or_summary = TABLE_SUMMARY;
					break;
				}
			}
		}
		
		for( $r = 0; $r < sizeof((array) $tableDef->rows); $r++ )
		{
			if( $tableDef->rows[$r]->flags & $list_or_summary )
			{
				switch( $tableDef->rows[$r]->flags & TABLE_ROW )
				{
					case TABLE_SECONDARY:
						$temp = array();
						$this->db->query("SELECT secondary_id FROM $table" . '_' . $tableDef->rows[$r]->name . " WHERE primary_id=$id ORDER BY structure_pos");
						while( $this->db->next_record() )
						{
							$temp[] = $this->db->fs('secondary_id');
						}
						
						for( $t = 0; $t < sizeof($temp); $t++ )
						{
							$curr = $this->get_summary($tableDef->rows[$r]->addparam->name, $temp[$t], '/');
							if( trim($curr) )
							{
								if( $ret ) $ret .= $sep;
								$ret .= $curr;
								if( !$sep ) {
									return $ret; // we only want the first value
								}
							}
						}
						break;
					
					case TABLE_MATTR:
						$temp = array();
						$this->db->query("SELECT attr_id FROM $table" . '_' . $tableDef->rows[$r]->name . " WHERE primary_id=$id");
						while( $this->db->next_record() )
						{
							$temp[] = $this->db->fs('attr_id');
						}
						
						for( $t = 0; $t < sizeof($temp); $t++ )
						{
							$curr = $this->get_summary($tableDef->rows[$r]->addparam->name, $temp[$t], ' / '/*value seperator*/);
							if( trim($curr) )
							{
								if( $ret ) $ret .= $sep;
								$ret .= $curr;
								if( !$sep ) {
									return $ret; // we only want the first value
								}
							}
						}
						break;
					
					case TABLE_SATTR:
						$curr = $this->get_summary($tableDef->rows[$r]->addparam->name, $this->get_text($table, $id, $tableDef->rows[$r]->name), ' / '/*value seperator*/);
						if( trim($curr) )
						{
							if( $ret ) $ret .= $sep;
							$ret .= $curr;
							if( !$sep ) {
								return $ret; // we only want the first value
							}
						}
						break;
					
					case TABLE_TEXT:
					case TABLE_TEXTAREA:
						$curr = $this->get_text($table, $id, $tableDef->rows[$r]->name);
						if( trim($curr) )
						{
							$curr = smart_truncate($curr);
							if( $ret ) $ret .= $sep;
							$ret .= $curr;
						}
						break;

					case TABLE_BLOB:
						$curr = explode(';', $this->get_text($table, $id, $tableDef->rows[$r]->name));
						if( trim($curr[0]) )
						{
							if( $ret ) $ret .= $sep;
							$ret .= $curr[0];
						}
						break;
						
					default:
						$curr = $this->get_text($table, $id, $tableDef->rows[$r]->name);
						if( $curr!='' )
						{
							$curr = $tableDef->formatField($tableDef->rows[$r]->name, $curr);
							if( $curr ) /*may be emptied by format*/
								$ret .= ($ret? $sep : '') . $curr;
						}
						break;
				}
				
				if( $ret && !$sep ) {
					return $ret; // we want only the first value
				}
			}
		}
		
		if( !$ret && !$force_TABLE_LIST ) {	
			// nothing found for TABLE_SUMMARY, use TABLE_LIST instead
			$ret = $this->get_summary($table, $id, $sep /*value seperator*/, 1 /*force_TABLE_LIST*/);
		}
		
		return $ret;
	}
};