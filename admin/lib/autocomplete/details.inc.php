<?php

class AUTOCOMPLETE_DETAILS_CLASS
{
	function handle_request()
	{
		// get parameters
		$temp = explode('.', $_REQUEST['acdata']);
		$table	= $temp[0];
		$field	= $temp[1];
		
		$acjson = new AUTOCOMPLETE_JSON_CLASS;
		$fields = $acjson->get_acnest_fields($table, $field);
		
		// is secondary table? forward to primary table!
		$is_only_secondary = false;
		$index_table  = $table;
		$index_prefix = '';
		$table_def = Table_Find_Def($table, 0 /*no ACL checks*/); if(!$table_def) die('bad table');
		if( $table_def->is_only_secondary($primary_table_name, $primary_table_field) )
		{
			$is_only_secondary = true;
			$index_table  = $primary_table_name;
			$index_prefix = $primary_table_field . '.';
		}
		
		// create parameters for index.php and for SQL testing
		$db = new DB_Admin;
		$eql = "";
		$sql = "SELECT id FROM $table WHERE ";
		for( $f = 0; $f < sizeof($fields); $f++ ) 
		{
			$value = $_REQUEST['v'.$f];
			
			$eql .= $f? ' AND ' : '';
			$eql .= $index_prefix . $fields[$f] . '("' . strtr($value, array('"'=>"''")) . '")';

			$sql .= $f? ' AND ' : '';
			$sql .= $fields[$f] . "=".$db->quote($value);
		}
		$sql .= ';';
		$url = "index.php?table=$index_table&searchoffset=0&f0=ANY&o0=&v0=".urlencode($eql);
		
		// if we have will get one result, we will show edit.php instead of index.php
		$db->query($sql);
		if( $db->next_record() ) {
			$id = $db->fs('id');
			if( !$db->next_record() ) 
			{
				// ... only one result - very fine, forward to edit.php
				$url = "edit.php?table=$table&id=$id";
				if( $is_only_secondary )
				{
					$db->query("SELECT primary_id FROM $primary_table_name"."_$primary_table_field WHERE secondary_id=$id;");
					if( $db->next_record() ) {
						$id = $db->fs('primary_id');
						$url = "edit.php?table=$primary_table_name&id=$id";
					}
				}
			}
		}

		// forward to the calcualted URL
		redirect($url);
	}
};
