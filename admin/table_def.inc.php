<?php


/*=============================================================================
Table Class Definition
===============================================================================

file:	
	table_def.php
	
author:	
	Bjoern Petersen

parameters:
	none, only function definitions in this file

=============================================================================*/



// row types
define("TABLE_ROW",				0x000000FF); 
define("TABLE_FLAG",			0x00000001);
define("TABLE_INT",				0x00000002);
define("TABLE_TEXT",			0x00000003); // 'addparam' can contain:
											 // 'mask###regex error rule###regex warn rule###regex search 1###regex replacement 1###regex search 2###...'; 'mask' is a readable expression as 'hh:mm';
											 // a trailing space in the mask indicates no max. length; alternatively you can simply specify a max. numeric length
define("TABLE_TEXTAREA",		0x00000004);
define("TABLE_ENUM",			0x00000007);
define("TABLE_DATE",			0x00000008);
define("TABLE_DATETIME",		0x00000009);
define("TABLE_BITFIELD",		0x00000010);
define("TABLE_PASSWORD",		0x00000011);
define("TABLE_MATTR",			0x00000012); 
define("TABLE_SATTR",			0x00000013); 
define("TABLE_SECONDARY",		0x00000014);
define("TABLE_BLOB",			0x00000015);


// row flags
define("TABLE_LIST",				0x00000100); // TABLE_*: show this field in the list by default
define("TABLE_SUMMARY",				0x00000200); // TABLE_*: include this field into the summary
define("TABLE_PERCENT",				0x00000400); // TABLE_*: add unit "percent", 28.01.2014: DEPRECATED, used in the old editor only 
define("TABLE_MUST",				0x00000800); // TABLE_*: invokes an error if the field is not set
define("TABLE_RECOMMENTED",			0x00001000); // TABLE_*: warn if the field is not set
define("TABLE_UNIQUE",				0x00002000); // TABLE_*: invokes an error if the field is set and is not unique
define("TABLE_UNIQUE_RECOMMENTED",	0x00004000); // TABLE_*: warn if the field is set and is not unique
define("TABLE_TRACKDEFAULTS",		0x00008000); // TABLE_*: track default values
define("TABLE_USERDEF",				0x00010000); // TABLE_*: a user defined control, PHP script in 'addparam', 2014-11-12 DEPRECATED, used in the old editor only 
define("TABLE_NEWSECTION",			0x00020000); // TABLE_*: start a new section, 28.01.2014: DEPRECATED: used in the old editor only
define("TABLE_READONLY",			0x00040000); // TABLE_*: control is always read-only, whatever ACL says
define("TABLE_HTML",				0x00100000); // TABLE_TEXTAREA: this flag will allow the HTML editor
define("TABLE_WIKI",				0x00200000); // TABLE_TEXTAREA: this flag indicates WIKI formatted text
define("TABLE_DAYMONTHOPT",			0x00100000); // TABLE_DATE: day/month is optional
define("TABLE_SHOWID",				0x00100000); // TABLE_MATTR: show the ID in the list
define("TABLE_MATTR_BR",			0x00200000); // TABLE_MATTR: break after each attr
define("TABLE_HIDESORT",			0x00400000); // TABLE_MATTR: hide "sort" funtion
define("TABLE_SHOWREF",				0x01000000); // TABLE_MATTR, TABLE_SATTR: show references
define("TABLE_URL",					0x00400000); // TABLE_TEXT: text fiels is an URL
define("TABLE_TEL",					0x02000000); // TABLE_TEXT: text fiels is an telephone number
define("TABLE_EMPTYONNULL",			0x00100000); // TABLE_INT: the value 0 is equal to empty, empty becomes 0
define("TABLE_EMPTYONMINUSONE",		0x00200000); // TABLE_INT: the value -1 is equal to empty, empty becomes -1
define("TABLE_SUM",					0x00400000); // TABLE_INT: show the sum at the end of the index
define("TABLE_ACNESTSTART",			0x01000000); // TABLE_TEXT: autocomplete nesting
define("TABLE_ACNEST",				0x02000000); // TABLE_TEXT: autocomplete nesting
define("TABLE_ACNORMAL",			0x04000000); // TABLE_TEXT: autocomplete 


// void stuff
define("TABLE_INDEX",				0x00000000); // <no longer needed, we'll get the information from the db>



// table flags
define("TABLE_PRIMARY",			0x00000001); // the table is a primary table that should appear in the rider bar
define("TABLE_SYNCABLE",		0x00000004); // the table may be synched and has globally unique IDs calculated from sync_src


// function truncates a string to a given length
// for URLs and files, we remove a part in the middle
function smart_truncate($text, $l = 80)
{
	if( strlen($text) > $l ) 
	{
		$sep = strpos($text, "\\")? "\\" : "/";
		$p1  = strpos($text, ':'.$sep);
		if( $p1 && $p1 < ($l/2) ) 
		{
			// truncate a file or URL
			$protocol = substr($text, 0, $p1+2);
			$text = substr($text, $p1+2);
			$p2 = strrpos($text, $sep);
			if( $p2 ) {
				$file = substr($text, $p2);
				$path = substr($text, 0, $l-(strlen($protocol)+strlen(file)+3)) . '...';

				$text = $protocol . $path . $file;
			}
			else {
				$text = $protocol . '...' . substr($text, ($l-(strlen($protocol)+3))*-1);
			}
		}
		else 
		{
			// truncate plain text
			$text = substr($text, 0, $l-3) . '...';
		}
	}
	
	return $text;
}


class Row_Def_Class
{
	var $flags;
	var $name;	// or table definiton object as instance of Table_Def_Class
	var $descr;
	var $default_value;
	var $addparam; // enum entries, min/max, masks etc.
	
	// name and descr must be given in HTML manner
	function __construct($flags, $name, $descr, $default_value, $addparam, $sectionName, $prop, $acl) 
	{
		$this->flags		= intval($flags);
		$this->name			= $name;
		$this->descr		= $descr;
		$this->default_value= $default_value;
		$this->addparam		= $addparam;
		$this->sectionName	= $sectionName;
		$this->prop			= is_array($prop)? $prop : array();
		$this->acl			= $acl;
	}
}

class Table_Def_Class
{
	var $flags;
	var $name;
	var $descr;
	var $rows;
	var $addparam;
	var $color;
	var $trigger_script;
	
	function __construct($flags, $name, $descr, $addparam = 0, $acl = 0) 
	{
		$this->flags			= $flags;
		$this->name				= $name;
		$this->descr			= $descr;
		$this->rows 			= array();
		$this->color			= '#aaaaaa';
		$this->addparam			= is_array($addparam)? $addparam : array();
		$this->acl				= $acl;
		$this->trigger_script	= '';
	}

	// name and descr must not be given in HTML manner
	function add_row($flags, $name, $descr, $default_value = 0, $addparam = 0, $sectionName = '', $prop = 0, $acl = 0)
	{
		$this->rows[] = new Row_Def_Class($flags, $name, $descr, $default_value, $addparam, $sectionName, $prop, $acl);
	}
	
	// Set a trigger script.  The script should set the global value $pluginfunc
	// to the name of the function to call.  The function should have the following
	// prototype: "function my_trigger(&$param)" where $param is an associative array with
	//   $param['action'] set to 'afterinsert', 'afterupdate' or 'afterdelete' and
	//   $param['id']     set to the affected row ID.
	// Return values are ignored at the moment, always return the integer "1".
	function set_trigger($script)
	{
		$this->trigger_script = $script;
	}
	
	// checks if the table is _only_ a secondary table
	function is_only_secondary(&$primary_table_name, &$primary_table_field)
	{
		global $Table_Def;
		
		$primary_table_name  = '';
		$primary_table_field = '';
		
		// a primary table is not only a secondary table
		if( $this->flags & TABLE_PRIMARY ) {
			return 0; // okay, not only a secondary table
		}
		
		// search for a attribute reference
		$is_secondary = 0;
		for( $t = 0; $t < sizeof($Table_Def); $t++ )
		{
			for( $r = 0; $r < sizeof($Table_Def[$t]->rows); $r++ )
			{
				$row_type = $Table_Def[$t]->rows[$r]->flags & TABLE_ROW;
				
				if( ($row_type == TABLE_MATTR || $row_type == TABLE_SATTR)
				 &&  $Table_Def[$t]->rows[$r]->addparam->name == $this->name ) {
				 	return 0; // okay, not only a secondary table
				}

				if( ($row_type == TABLE_SECONDARY)
				 &&  $Table_Def[$t]->rows[$r]->addparam->name == $this->name ) {
				 	$primary_table_name = $Table_Def[$t]->name;
				 	$primary_table_field = $Table_Def[$t]->rows[$r]->name;
				 	$is_secondary = 1; 
				}
			}
		}
		
		return $is_secondary; // if secondary, it is _only_ a secondary table
	}
	
	// function checks if the table uses TABLE_TRACKDEFAULTS for any row
	function uses_track_defaults()
	{
		for( $r = 0; $r < sizeof($this->rows); $r++ ) {
			if( intval($this->rows[$r]->flags) & TABLE_TRACKDEFAULTS ) {
				return 1;
			}
		}
		return 0;
	}
	
	// function removes all record dependencies
	function destroy_record_dependencies($id, $destroySecondary = 1)
	{	
		/*
		$db  = new DB_Admin;
		$dba = new DB_Admin;
		
		for( $r = 0; $r < sizeof($this->rows); $r++ )
		{
			$row_type = $this->rows[$r]->flags & TABLE_ROW;
			if( $row_type == TABLE_MATTR )
			{
				$db->query("DELETE FROM " . $this->name . '_' . $this->rows[$r]->name . " WHERE primary_id=$id");
			}
			else if( $row_type == TABLE_SECONDARY )
			{
				$dba->query("SELECT secondary_id FROM " . $this->name . '_' . $this->rows[$r]->name . " WHERE primary_id=$id");
				while( $dba->next_record() ) 
				{
					$this->rows[$r]->addparam->destroy_record_dependencies($dba->f('secondary_id'), $destroySecondary);
					
					if( $destroySecondary ) {
						$db->query("DELETE FROM " . $this->rows[$r]->addparam->name . " WHERE id=" . $dba->f('secondary_id'));
					}
				}
				
				if( $destroySecondary ) {				
					$db->query("DELETE FROM " . $this->name . '_' . $this->rows[$r]->name . " WHERE primary_id=$id");
				}
			}
		}
		*/
		$db = new DB_Admin;
		$this->destroy_record_n_dependencies($db, $id, array('leave_primary_record'=>true, 'leave_secondary_relations'=>!$destroySecondary, 'leave_secondary_records'=>!$destroySecondary, ));
	}
	function destroy_record_n_dependencies(&$db, $id, $addparam = 0)
	{
		if( !is_array($addparam) ) $addparam = array();
		$id = intval($id);
		
		for( $r = 0; $r < sizeof($this->rows); $r++ )
		{
			$row_type = $this->rows[$r]->flags & TABLE_ROW;
			if( $row_type == TABLE_MATTR )
			{
				$db->query("DELETE FROM " . $this->name . '_' . $this->rows[$r]->name . " WHERE primary_id=$id");
			}
			else if( $row_type == TABLE_SECONDARY )
			{
				$secondary_ids = array();
				$db->query("SELECT secondary_id FROM " . $this->name . '_' . $this->rows[$r]->name . " WHERE primary_id=$id");
				while( $db->next_record() ) {
					$secondary_ids[] = $db->fs('secondary_id');
				}
				
				if( sizeof($secondary_ids) ) {
					for( $i = 0; $i < sizeof($secondary_ids); $i++ ) {
						$this->rows[$r]->addparam->destroy_record_n_dependencies($db, $secondary_ids[$i], $addparam);
					}
					if( !$addparam['leave_secondary_records'] ) {
						$db->query("DELETE FROM " . $this->rows[$r]->addparam->name . " WHERE id IN(" . implode(',', $secondary_ids) . ");");
					}
				}
				
				if( !$addparam['leave_secondary_relations'] ) {
					$db->query("DELETE FROM " . $this->name . '_' . $this->rows[$r]->name . " WHERE primary_id=$id");
				}
			}
		}
		
		if( !$addparam['leave_primary_record'] )
		{
			$db->query("DELETE FROM $this->name WHERE id=$id;");
		}
	}
	
	// function calculates the number of references to the table
	function num_references($id /* -1 for all */, &$references)
	{
		global $Table_Def;
		$total_ref = 0;
		$db = new DB_Admin;
		$references = array();
		
		for( $t = 0; $t < sizeof($Table_Def); $t++ )
		{
			for( $r = 0; $r < sizeof($Table_Def[$t]->rows); $r++ )
			{
				$row_type		= $Table_Def[$t]->rows[$r]->flags&TABLE_ROW;
				$row_addparam	= $Table_Def[$t]->rows[$r]->addparam->name;
				$query			= '';
				$primary_id		= '';
				
				// get the query to search for references
				if( $row_type==TABLE_MATTR && $row_addparam == $this->name )
				{
					$query = "SELECT COUNT(*) FROM {$Table_Def[$t]->name}_{$Table_Def[$t]->rows[$r]->name}";
					if( $id != -1 ) {
						$query		.=	" WHERE attr_id=$id";
						$primary_id	=	'primary_id';
					}
				}
				else if( $row_type==TABLE_SATTR && $row_addparam==$this->name )
				{
					$query = "SELECT COUNT(*) FROM {$Table_Def[$t]->name}";
					if( $id != -1 ) {
						$query		.=	" WHERE {$Table_Def[$t]->rows[$r]->name}=$id";
						$primary_id	=	'id';
					}
				}
				else if( $row_type == TABLE_SECONDARY && $row_addparam==$this->name )
				{
					$query = "SELECT COUNT(*) FROM {$Table_Def[$t]->name}_{$Table_Def[$t]->rows[$r]->name}";
					if( $id != -1 ) {
						$query .= " WHERE secondary_id=$id";
						$primary_id	=	'primary_id';
					}
				}
				
				// do we have a query?
				if( $query != '' )
				{
					// get the number of references
					$row_ref = -1;
					$db->query($query);
					if( $db->next_record() ) {
						$row_ref = $db->f('COUNT(*)');
					}
					
					if( $row_ref >= 0 ) 
					{
						// get the reference if we have exactly one reference
						$row_id = 0;
						if( $primary_id != '' && $row_ref == 1 ) {
							$db->query(str_replace(' COUNT(*) ', " $primary_id ", $query));
							if( $db->next_record() ) {
								$row_id = $db->f($primary_id);
							}
						}
					
						// add to array of references
						$references[] = array(	$Table_Def[$t]->name,			$Table_Def[$t]->descr,
												$Table_Def[$t]->rows[$r]->name,	$Table_Def[$t]->rows[$r]->descr,
												$row_ref, $row_id);
						$total_ref += $row_ref;
					}
				}
			}
		}
		
		return $total_ref;
	}

	function get_text(&$db, $id, $field)
	{
		$db->query("SELECT $field FROM $this->name WHERE id=$id");
		if( $db->next_record() ) {
			$ret = $db->fs($field);
		}
		else {
			$ret = $id;
		}
		
		return $ret;
	}

	function get_bitfield_summary($r, $bits, &$ret_all_bits)
	{
		$values = explode('###', $this->rows[$r]->addparam);
		$text = '';
		$num_bits_set = 0;
		$num_total_bits = 0;

		for( $v = 0; $v < sizeof($values); $v+=2 ) {
			if( intval($values[$v]) & intval($bits) ) {
				if( $text ) {
					$text .= ', ';
				}
				$text .= trim($values[$v+1]);
				$num_bits_set++;
			}
			$num_total_bits++;
		}
		
		$ret_all_bits = ($num_bits_set==$num_total_bits)? 1 : 0;
		
		return $text;
	}

	function get_enum_summary($r, $enumval)
	{
		$values = explode('###', $this->rows[$r]->addparam);

		for( $v = 0; $v < sizeof($values); $v+=2 ) {
			if( $values[$v] == $enumval ) {
				return $values[$v+1];
			}
		}
		
		return '';
	}

	// function gets a summary string for the given table row
	function get_summary($id, $sep = '', $force_TABLE_LIST = 0)
	{
		global $g_db_summary; 
		if( !is_object($g_db_summary)  )
		{
			$g_db_summary = new DB_Admin;
		}
		
		$ret = '';

		$list_or_summary = TABLE_LIST; // if no summary rows are specified, TABLE_LIST is used
		if( !$force_TABLE_LIST ) {
			for( $r = 0; $r < sizeof($this->rows); $r++ )
			{
				if( $this->rows[$r]->flags & TABLE_SUMMARY ) {
					$list_or_summary = TABLE_SUMMARY;
					break;
				}
			}
		}

		for( $r = 0; $r < sizeof($this->rows); $r++ )
		{
			if( $this->rows[$r]->flags & $list_or_summary )
			{
				switch( $this->rows[$r]->flags & TABLE_ROW )
				{
					case TABLE_SECONDARY:
						$temp = array();
						$g_db_summary->query("SELECT secondary_id FROM $this->name" . '_' . $this->rows[$r]->name . " WHERE primary_id=$id ORDER BY structure_pos");
						while( $g_db_summary->next_record() )
						{
							$temp[] = $g_db_summary->f('secondary_id');
						}
						
						for( $t = 0; $t < sizeof($temp); $t++ )
						{
							$curr = $this->rows[$r]->addparam->get_summary($temp[$t], '/'/*value seperator*/);
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
						$g_db_summary->query("SELECT attr_id FROM $this->name" . '_' . $this->rows[$r]->name . " WHERE primary_id=$id");
						while( $g_db_summary->next_record() )
						{
							$temp[] = $g_db_summary->f('attr_id');
						}
						
						for( $t = 0; $t < sizeof($temp); $t++ )
						{
							$curr = $this->rows[$r]->addparam->get_summary($temp[$t], ' / '/*value seperator*/);
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
						$curr = $this->rows[$r]->addparam->get_summary($this->get_text($g_db_summary, $id, $this->rows[$r]->name), ' / '/*value seperator*/);
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
						$curr = $this->get_text($g_db_summary, $id, $this->rows[$r]->name);
						if( trim($curr) )
						{
							$curr = smart_truncate($curr);
							if( $ret ) $ret .= $sep;
							$ret .= $curr;
						}
						break;

					case TABLE_BLOB:
						$curr = explode(';', $this->get_text($g_db_summary, $id, $this->rows[$r]->name));
						if( trim($curr[0]) )
						{
							if( $ret ) $ret .= $sep;
							$ret .= $curr[0];
						}
						break;
						
					default:
						$curr = $this->get_text($g_db_summary, $id, $this->rows[$r]->name);
						if( $curr!='' )
						{
							$curr = $this->formatField($this->rows[$r]->name, $curr);
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
			$ret = $this->get_summary($id, $sep /*value seperator*/, 1 /*force_TABLE_LIST*/);
		}
		
		return $ret;
	}

	// function gets the id from a known field
	function get_id_from_field(&$db, $field, $txt)
	{
		$db->query("SELECT COUNT(*) FROM $this->name WHERE $field='" . addslashes($txt) . "'");
		$db->next_record();
		if( $db->f('COUNT(*)') == 1 )
		{
			$db->query("SELECT id FROM $this->name WHERE $field='" . addslashes($txt) . "'");
			if( $db->next_record() )
				return intval($db->f('id'));
		}
		else
		{
			$db->query("SELECT COUNT(*) FROM $this->name WHERE $field LIKE '" . addslashes($txt) . "%'");
			$db->next_record();
			if( $db->f('COUNT(*)') == 1 )
			{
				$db->query("SELECT id FROM $this->name WHERE $field LIKE '" . addslashes($txt) . "%'");
				if( $db->next_record() )
					return intval($db->f('id'));
			}
		}
		
		return 0; // not found
	}

	// function gets the id from any field value, returns 0 for errors
	function get_id_from_txt($txt, &$ret_error_txt)
	{
		$db = new DB_Admin;
		$ret_error_txt = '';

		if( intval($txt) && strlen(intval($txt))==strlen(trim($txt)) )
		{
			$db->query("SELECT id FROM $this->name WHERE id=$txt");
			if( $db->next_record() ) {
				return intval($txt);
			}
			else {
				$ret_error_txt = htmlconstant('_UNKNOWNVALUE', isohtmlentities($txt));
				return intval(0); // not found
			}
		}
		else
		{
			for( $r = 0; $r < sizeof($this->rows); $r++ )
			{
				if( $this->rows[$r]->flags & TABLE_LIST )
				{
					switch( $this->rows[$r]->flags & TABLE_ROW ) 
					{
						case TABLE_TEXT:
						case TABLE_TEXTAREA:
						case TABLE_INT:
						case TABLE_DATE:
						case TABLE_DATETIME:
							$id = $this->get_id_from_field($db, $this->rows[$r]->name, $txt);
							if( $id ) {
								return intval($id);
							}
							break;
					}
				}
			}
		}

		$ret_error_txt = htmlconstant('_UNKNOWNVALUE', isohtmlentities($txt));
		return intval(0); // not found
	}
	
	function formatField($fieldName, $value) // return ASCII
	{
		if( $fieldName == 'user_access' )
		{
			return access_to_human($value);
		}
		else if( $fieldName == 'user_grp' )
		{
			return grp_ascii_name($value);
		}
		else if( $fieldName == 'user_created' || $fieldName == 'user_modified' )
		{
			return user_ascii_name($value);
		}
		else for( $r = 0; $r < sizeof($this->rows); $r++ )
		{
			if( $this->rows[$r]->name == $fieldName )
			{
				$rowflags = $this->rows[$r]->flags;
				switch( $rowflags&TABLE_ROW )
				{
					case TABLE_DATE:
					case TABLE_DATETIME:
						if( ($rowflags&TABLE_ROW)==TABLE_DATE ) {
							$type = ($rowflags&TABLE_DAYMONTHOPT)? 'dateopt' : 'date';
						}
						else {
							$type = 'datetime';
						}
						return sql_date_to_human($value, $type);
						
					case TABLE_ENUM:
						return $this->get_enum_summary($r, $value);
					
					case TABLE_BITFIELD:
						return $this->get_bitfield_summary($r, $value, $ret_all_bits);
					
					case TABLE_FLAG:
						return htmlconstant($value? '_YES' : '_NO');	// well, we assume htmlconstant's HTML is equal to ASCII here
					
					case TABLE_INT:
						$value = intval($value);
						if( ($rowflags&TABLE_EMPTYONNULL && $value==0) || ($rowflags&TABLE_EMPTYONMINUSONE && $value==-1) ) {
							return '';
						}
						else {
							return $value;
						}
				}
				break;
			}
		}
		return $value;
	}
}



// function finds the table definition from the table name
function Table_Find_Def($name, $accessCheck = 1)
{
	global $Table_Def;

	for( $t = 0; $t < sizeof($Table_Def); $t++ ) 
	{
		if( $Table_Def[$t]->name == $name ) 
		{
			if( !$_SESSION['g_session_userid'] || $accessCheck == 0) {
				// table found by name
				return $Table_Def[$t]; 
			}
			
			// table found, check access
			require_once('acl.inc.php');
			$acl = acl_get_access("$name.COMMON");
			if( !($acl&ACL_READ) ) {
				// no access to this table
				return 0;
			}

			// build new table definition regarding the user's access rights
			$ret = new Table_Def_Class(
				$Table_Def[$t]->flags,
				$Table_Def[$t]->name, 
				$Table_Def[$t]->descr, 
				$Table_Def[$t]->addparam,
				$acl
			);
			
			$ret->set_trigger($Table_Def[$t]->trigger_script);
			
			/*
			$delayedSection = '';
			*/
			$rows = sizeof($Table_Def[$t]->rows);
			for( $r = 0; $r < $rows; $r++ ) 
			{
				$rowflags	= $Table_Def[$t]->rows[$r]->flags;
				$rowsection	= $Table_Def[$t]->rows[$r]->sectionName;
				
				$acl = acl_get_access("{$name}.{$Table_Def[$t]->rows[$r]->name}");
				if( $rowflags&TABLE_READONLY )
				{
					$acl &= ~(ACL_NEW|ACL_EDIT|ACL_DELETE);
				}
				
				/* -- 08:57 31.01.2014: we assume fields can always be read.
				if( $acl&ACL_READ )
				{
				*/
					/*
					if(  $delayedSection
					 && !($rowflags&TABLE_NEWSECTION) )
					{
						$rowflags	   |= TABLE_NEWSECTION;
						$rowsection		= $delayedSection;
						$delayedSection = '';
					}
					*/
					
					$ret->add_row(
						$rowflags, 
						$Table_Def[$t]->rows[$r]->name, 
						$Table_Def[$t]->rows[$r]->descr, 
						$Table_Def[$t]->rows[$r]->default_value, 
						$Table_Def[$t]->rows[$r]->addparam, 
						$rowsection,
						$Table_Def[$t]->rows[$r]->prop,
						$acl
					);
				/*
				}
				else
				{
					if( $rowflags&TABLE_NEWSECTION )
					{
						$delayedSection = $rowsection? $rowsection : $Table_Def[$t]->rows[$r]->descr;
					}
				}
				*/
			}
			
			return $ret;
		}
	}

	// table not found
	return 0; 
}

// Table_Find_Def() plus id check
function Table_Find_Id($name, $id)
{
	$table_def = Table_Find_Def($name);
	if( $table_def ) {
		require_once('acl.inc.php');
		if( acl_check_access("$name.COMMON", $id) ) {
			return $table_def;
		}
	}
	return 0;	
}


function Table_Def_Finish($prop=0)
{
	global $Table_Def;
	
	if( !is_array($prop) )
		$prop = array();
	
	//
	// add table "groups"...
	//
	$groups = new Table_Def_Class(0,					'user_grp',	htmlconstant('_GROUPS'));
	$groups->add_row(TABLE_TEXT|TABLE_LIST|TABLE_MUST|TABLE_UNIQUE|TABLE_SUMMARY,
														'shortname',htmlconstant('_LOGINNAME'), '', '', '', array('ctrl.size'=>'10-20-80'));
	$groups->add_row(TABLE_PASSWORD,					'password',	htmlconstant('_PASSWORD'), '', '', '', array('layout.join'=>1));
	$groups->add_row(TABLE_TEXT|TABLE_LIST|TABLE_UNIQUE_RECOMMENTED,
														'name',		htmlconstant('_NAME'), '', '', '', array('ctrl.size'=>'20-80', 'layout.bg.class'=>'e_bglite', 'layout.descr.class'=>'e_bolder', 'ctrl.class'=>'e_bolder'));
														
	$groups->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION,	'settings',			htmlconstant('_SETTINGS'), 0, 0, 0, array('help.url'=>$prop['user_grp.settings.help.url']));
	$groups->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION,	'notizen',			'Journal', '', '', '', array('layout.section'=>1));

	//
	// add table "roles"...
	//
	if( defined('USE_ROLES') )
	{
		$roles = new Table_Def_Class(0, 					'user_roles',			'Benutzerrollen');
		$roles->add_row(TABLE_TEXT|TABLE_LIST|TABLE_SUMMARY|TABLE_MUST|TABLE_UNIQUE_RECOMMENTED,
															'name',					'Name der Rolle', '', '', '', array('ctrl.size'=>'30-80', 'layout.bg.class'=>'e_bglite', 'layout.descr.class'=>'e_bolder', 'ctrl.class'=>'e_bolder'));
		$roles->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION,	'text_to_confirm',		'Zu bestätigender Text', 0, 0, 0, array('ctrl.rows'=>20));
		$roles->add_row(TABLE_TEXT|TABLE_LIST|TABLE_URL,	'email_notify',			'E-Mail&nbsp;für&nbsp;Bestätigungen', '', '', '', array('ctrl.size'=>'40-80', 'help.tooltip'=>'Wenn ein Benutzer den angezeigten Text bestätigt hat, wird eine Nachricht an diese E-Mail-Addresse gesandt.'));
	}
		

	//
	// add table "user"...
	//
	$user = new Table_Def_Class(0,					'user',				htmlconstant('_USER'));
	$user->add_row(TABLE_TEXT|TABLE_LIST|TABLE_MUST|TABLE_UNIQUE|TABLE_INDEX,	
													'loginname',		htmlconstant('_LOGINNAME'), '', '', '', array('ctrl.size'=>'10-20-80'));
	$user->add_row(TABLE_PASSWORD,					'password',			htmlconstant('_PASSWORD'), '', '', '', array('layout.join'=>1));
	$user->add_row(TABLE_TEXT|TABLE_LIST|TABLE_SUMMARY|TABLE_UNIQUE_RECOMMENTED,			
													'name',				htmlconstant('_NAME'), '', '', '', array('ctrl.size'=>'20-80', 'layout.bg.class'=>'e_bglite', 'layout.descr.class'=>'e_bolder', 'ctrl.class'=>'e_bolder'));
	$user->add_row(TABLE_TEXT|TABLE_TEL,            'phone',			htmlconstant('_PHONE'), '', '', '', array('ctrl.size'=>'10-20-80'));
	$user->add_row(TABLE_TEXT|TABLE_UNIQUE_RECOMMENTED|TABLE_URL,
													'email',			htmlconstant('_EMAIL'), '', '', '', array('layout.join'=>1, 'ctrl.size'=>'10-20-80'));
	$user->add_row(TABLE_TEXTAREA|TABLE_USERDEF|TABLE_NEWSECTION,	
													'access',			htmlconstant('_GRANTS'), 0, '', htmlconstant('_GRANTS').', '.htmlconstant('_GROUPS'), array('ctrl.phpclass'=>'CONTROL_USERACCESS_CLASS',  'deprecated_userdef'=>'deprecated_edit_access.php'));
	$user->add_row(TABLE_MATTR|TABLE_LIST,			'attr_grp',			htmlconstant('_GROUPS'), 0, $groups);
	if( defined('USE_ROLES') )
	{
		$user->add_row(TABLE_SATTR,					'attr_role',		'Rolle', 0, $roles);
	}
	$user->add_row(TABLE_TEXTAREA,					'msg_to_user',		'Nachricht an den Benutzer', 0, 0, '', array('ctrl.rows'=>3, 'help.tooltip'=>'die Nachricht wird dem Benutzer immer angezeigt, wenn er sich im Redaktionssystem einloggt'));
	$user->add_row(TABLE_DATETIME|TABLE_NEWSECTION,	'last_login',		htmlconstant('_LASTLOGIN'), 0, 0, htmlconstant('_STATE'));
	$user->add_row(TABLE_DATETIME,					'last_login_error',	htmlconstant('_LASTLOGINERROR'), '', '', '', array('layout.join'=>1));
	$user->add_row(TABLE_INT,						'num_login_errors',	htmlconstant('_STATE'), '', '', '', array('layout.join'=>1));
	$user->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION,	'settings',			htmlconstant('_SETTINGS'), 0, 0, 0, array('layout.defhide'=>2, 'layout.join'=>1, 'help.url'=>$prop['user.settings.help.url']));
	//$user->add_row(TABLE_TEXTAREA,					'remembered',		htmlconstant('_JOBLISTS')); -- this field may be several MB in size; do not make it editable, eg. diff is very time consuming completely useless
	$user->add_row(TABLE_TEXTAREA|TABLE_NEWSECTION,	'notizen',			'Journal', '', '', '', array('layout.section'=>1));

	// ...add tables						
	$Table_Def[] = $user;
	$Table_Def[] = $groups;
	if( defined('USE_ROLES') ) {
		$Table_Def[] = $roles;	
	}
	
	// convert linked table names to objects
	for( $t = 0; $t < sizeof($Table_Def); $t++ )
	{
		for( $r = 0; $r < sizeof($Table_Def[$t]->rows); $r++ )
		{
			$row_type = $Table_Def[$t]->rows[$r]->flags & TABLE_ROW;
			
			if( ($row_type == TABLE_MATTR || $row_type == TABLE_SATTR || $row_type == TABLE_SECONDARY)
			 &&  !is_object($Table_Def[$t]->rows[$r]->addparam) )
			{
				$Table_Def[$t]->rows[$r]->addparam = Table_Find_Def($Table_Def[$t]->rows[$r]->addparam, 0/*no access check*/);
			}
		}
	}	
}

// needed for the including of 'db.inc.php'
global $Table_Def;


