<?php



/*=============================================================================
The main Edit Class
===============================================================================

file:	
	deprecated_edit_class.php
	
author:	
	Bjoern Petersen

parameters:
	none, only function definitions in this file

=============================================================================*/




function is_referencable(&$db, $table, $id)
{
	// bits in user access: rwx   rwx rwx
	//                      other grp user
	
	$db->query("SELECT user_access FROM $table WHERE id=".intval($id));
	if( $db->next_record() ) 
	{
		$user_access = intval($db->f('user_access'));
		if( $user_access & 0111 ) 
			return true;
	}
	return false;	
}



/*=============================================================================
Image button stuff
=============================================================================*/


if( $_REQUEST['imgbutton'] ) 
{
	$imgbutton = explode('|', $_REQUEST['imgbutton']);
	$imgbuttonUseNext = 0;
	for( $imgbuttonCount = 0; $imgbuttonCount < sizeof($imgbutton); $imgbuttonCount += 2 ) 
	{
		$getvar = 'imgbutton' . ($imgbuttonCount/2) . '_x';
		if( isset($_REQUEST[$getvar]) || $imgbuttonUseNext ) 
		{
			/*$basevar = explode('[', $imgbutton[$imgbuttonCount]);
			$setvar = "global \${$basevar[0]}; \${$imgbutton[$imgbuttonCount]}=urldecode(\"{$imgbutton[$imgbuttonCount+1]}\");";
			eval($setvar);*/
			$tempname = str_replace(']', '', $imgbutton[$imgbuttonCount]);
			$tempname = explode('[', $tempname);
			if( sizeof($tempname)==2 ) { // used for $control[index] = $val
				$_REQUEST[ $tempname[0] ][ $tempname[1] ] = urldecode($imgbutton[$imgbuttonCount+1]);
			}
			else if( sizeof($tempname)==1 ) { // used for $goto = $val
				$_REQUEST[ $tempname[0] ] = urldecode($imgbutton[$imgbuttonCount+1]);
			}
			else {
				die('bad imgbutton!');
			}
			break;
		}
		else if( $imgbutton[$imgbuttonCount]=='' && $imgbutton[$imgbuttonCount+1]=='' ) 
		{
			$imgbuttonUseNext = 1;
		}
	}
	unset($_REQUEST['imgbutton']);
}



function imgbuttonRender(	$buttonname,
							$buttonvalue,
							$img, $imgAlt = '',
							$righttext = '', $alt = '', $tooltip = '',
							$onclick = ''	)
{
	//
	// get image size
	//
	if( $img ) {
		$imgSize = GetImageSize($img);	
	}
	
	$ret = '';
	
	//
	// get a free number, init
	//
	global $imgbuttonShouldFinish;
	if( !is_array($imgbuttonShouldFinish) ) {
		$imgbuttonShouldFinish = array();
	}
	
	$imgbuttonNum = sizeof($imgbuttonShouldFinish)/2;

	//
	// create the button code...
	//
	if( $onclick ) {
		$onclick = "if($onclick){";
		$onclickend = '}';
	}
	else {
		$onclickend = "";
	}
	
	$ret .= "<a href=\"\" title=\"$tooltip\" onclick=\"{$onclick}document.forms[0].imgbutton.value='||$buttonname|$buttonvalue';document.forms[0].submit();{$onclickend}return false;\"";
		if( $img && $imgAlt ) {
			$ret .= " onmouseover=\"document.imgbuttonimg{$imgbuttonNum}.src='$imgAlt';\" onmouseout=\"document.imgbuttonimg{$imgbuttonNum}.src='$img';\"";
		}
	$ret .= ">";
	
		if( $img ) {
			$ret .= "<img name=\"imgbuttonimg{$imgbuttonNum}\" src=\"$img\" width=\"{$imgSize[0]}\" height=\"{$imgSize[1]}\" border=\"0\" alt=\"$alt\" title=\"$title\" />";
		}
		$ret .= $righttext;

	$ret .= '</a>';

	//
	// store the real name and the real value
	//
	$imgbuttonShouldFinish[] = $buttonname;
	$imgbuttonShouldFinish[] = $buttonvalue;

	//
	// done
	//
	return $ret;
}



function imgbuttonFinish()
{
	global $imgbuttonShouldFinish;
	
	$ret = '<input type="hidden" name="imgbutton" value="';
		for( $i = 0; $i < sizeof($imgbuttonShouldFinish); $i += 2 ) {
			if( $i ) { $ret .= '|'; }
			$ret .= $imgbuttonShouldFinish[$i] . '|' . urlencode($imgbuttonShouldFinish[$i+1]);
		}
	$ret .= '" />';
	
	$imgbuttonShouldFinish = 0;
	return $ret;
}




/*=============================================================================
The edit class
=============================================================================*/




class Table_Inst_Class
{
	var $table_def_name;	// the name of the table definition
	var $id;				// -1 if unknown

	var $is_new;

	var $object_name;		// name of object

	var $date_created;		// version info
	var $date_modified;
	var $user_created;
	var $user_modified;
	var $user_access;

	var $values;			// the values
	var $value_errors;
	var $value_errors_addparam;

	var $parent_field;		// the field in the primary parent table if the table is a secondary table
							// for primary tables, this field is empty

	//
	// create a new table instance
	//
	function Table_Inst_Class(	$object_name, 
								$table_def_name, 
								$id, 
								$table_usedefaults = 1,
								$parent_field = ''	)
	{
		global $site;
	
		$this->object_name		= $object_name;
		$this->table_def_name	= $table_def_name;
		$this->id				= $id;
		$this->parent_field		= $parent_field;
		$this->section			= 0;

		// set section	
		if( isset($_REQUEST['section']) ) {
			$this->section = intval($_REQUEST['section']);
		}
		
		$table_def = Table_Find_Def($this->table_def_name);
		if( !$table_def ) {
			$site->abort(__FILE__, __LINE__, "$path_to_check");
			exit(); // no access to this table
		}

		if( $id != -1 )
		{
			//
			// load values from db
			//
			$db  = new DB_Admin;
			$dba = new DB_Admin;
			$db->query("SELECT * FROM $table_def->name WHERE id=$this->id");
			if( $db->next_record() )
			{
				$this->date_created	= $db->fs('date_created');
				$this->date_modified= $db->fs('date_modified');
				$this->user_created	= intval($db->fs('user_created'));
				$this->user_modified= intval($db->fs('user_modified'));
				$this->user_grp		= intval($db->fs('user_grp'));
				$this->user_access	= $db->fs('user_access');
				$this->sync_src		= intval($db->fs('sync_src'));

				for( $r = 0; $r < sizeof($table_def->rows); $r++ )
				{
					if( $table_def->rows[$r]->flags & TABLE_USERDEF )
					{
						$param['cmd']		=	'load';
						$param['table']		=	$table_def->name;
						$param['field']		=	$table_def->rows[$r]->name;
						$param['id']		= 	$this->id;
						$param['db']		=&	$db;
						$this->values[$r]	= call_plugin($table_def->rows[$r]->prop['deprecated_userdef'], $param);
					}
					else switch( $table_def->rows[$r]->flags & TABLE_ROW )
					{
						case TABLE_FLAG:
						case TABLE_BITFIELD:
						case TABLE_INT:
						case TABLE_TEXT:
						case TABLE_TEXTAREA:
						case TABLE_PASSWORD:
						case TABLE_DATE:
						case TABLE_DATETIME:
						case TABLE_ENUM:
							$this->values[$r] = $db->fs($table_def->rows[$r]->name);
							break;
						
						case TABLE_BLOB:
							$blob = new G_BLOB_CLASS($db->f($table_def->rows[$r]->name));
							$this->values[$r][0] = 0; // blob not modified
							$this->values[$r][1] = ''; // the blob itself
							$this->values[$r][2] = $blob->name;
							$this->values[$r][3] = $blob->mime;
							$this->values[$r][4] = strlen($blob->blob);
							$this->values[$r][5] = $blob->w;
							$this->values[$r][6] = $blob->h;
							global $record_has_blob;
							$record_has_blob = 1;
							break;
						
						case TABLE_SATTR:
							$this->values[$r] = intval($db->f($table_def->rows[$r]->name));
							break;
						
						case TABLE_MATTR:
							$this->values[$r] = array();
							$dba->query("SELECT attr_id FROM " . $table_def->name . '_' . $table_def->rows[$r]->name . " WHERE primary_id=$this->id ORDER BY structure_pos");
							while( $dba->next_record() )
							{
								$temp = intval($dba->f('attr_id'));
								if( !in_array($temp, $this->values[$r]) ) {
									$this->values[$r][] = $temp;
								}
							}
							break;

						case TABLE_SECONDARY:
							$this->values[$r] = array();
							$dba->query("SELECT secondary_id,structure_pos FROM " . $table_def->name . '_' . $table_def->rows[$r]->name . " WHERE primary_id=$this->id ORDER BY structure_pos");
							while( $dba->next_record() )
							{
								$this->values[$r][] = new Table_Inst_Class(
									$this->object_name, 								// object_name
									$table_def->rows[$r]->addparam->name, 				// table_def_name
									$dba->f('secondary_id'), 							// id
									0,													// use_defaults
									$table_def->name.'.'.$table_def->rows[$r]->name);	// parent_field
							}
							break;
					}
				}
			}
			else
			{
				$this->error(htmlconstant('_EDIT_ERRCANNOTREADRECORD') . " ({$table_def->name}.{$this->id})");
				$id = -1; // forward to new
			}
		}

		if( $id == -1 )
		{
			// init values
			$this->is_new		= 1;
			$this->date_created	= strftime("%Y-%m-%d %H:%M:%S");
			$this->date_modified= strftime("%Y-%m-%d %H:%M:%S");
			$this->user_created	= intval($_SESSION['g_session_userid']);
			$this->user_modified= intval($_SESSION['g_session_userid']);
			$this->user_grp		= intval(acl_get_default_grp());
			$this->user_access	= acl_get_default_access();

			for( $r = 0; $r < sizeof($table_def->rows); $r++ )
			{
				if( $table_def->rows[$r]->flags & TABLE_USERDEF )
				{
					$param['cmd']		= 'init';
					$param['table']		= $table_def->name;
					$param['field']		= $table_def->rows[$r]->name;
					$this->values[$r]	= call_plugin($table_def->rows[$r]->prop['deprecated_userdef'], $param);
				}
				else switch( $table_def->rows[$r]->flags & TABLE_ROW )
				{
					case TABLE_FLAG:
					case TABLE_ENUM:
					case TABLE_BITFIELD:
						$this->values[$r] = intval($table_def->rows[$r]->default_value);
						break;

					case TABLE_INT:
						$this->values[$r] = strval($table_def->rows[$r]->default_value); // may be empty
						break;

					case TABLE_TEXT:
					case TABLE_TEXTAREA:
						if( $table_def->rows[$r]->default_value == '0' ) {
							$this->values[$r] = '';
						}
						else {
							$this->values[$r] = $table_def->rows[$r]->default_value;
						}
						break;
					
					case TABLE_BLOB:
						$this->values[$r][0] = 0;	// blob not modified
						$this->values[$r][1] = '';	// the blob itself
						$this->values[$r][2] = '';	// name
						$this->values[$r][3] = '';	// mime
						$this->values[$r][4] = 0;	// bytes
						$this->values[$r][5] = 0;	// w
						$this->values[$r][6] = 0;	// h
						global $record_has_blob;
						$record_has_blob = 1;
						break;

					case TABLE_PASSWORD:
						$this->values[$r] = crypt('');
						break;

					case TABLE_DATE:
					case TABLE_DATETIME:
						$this->values[$r] = (($table_def->rows[$r]->default_value=='today')? $this->date_created : '0000-00-00 00:00:00');
						if( strval($table_def->rows[$r]->default_value) == 'today' ) {
							$this->values[$r] = $this->date_created;
						}
						else {
							$this->values[$r] = '0000-00-00 00:00:00';
						}
						break;

					case TABLE_MATTR:
						$this->values[$r] = array();
						if( $table_usedefaults && ($table_def->rows[$r]->flags & TABLE_TRACKDEFAULTS) )
						{
							$defaults = $_SESSION['g_session_track_defaults'][$table_def->rows[$r]->addparam->name];
							if( $defaults && is_array($defaults) )
							{
								for( $a = 0; $a < sizeof($defaults); $a++ ) {
									$this->values[$r][] = intval($defaults[$a]);
								}
							}
						}
						break;

					case TABLE_SATTR:
						$this->values[$r] = intval($table_def->rows[$r]->default_value);
						if( $table_usedefaults && ($table_def->rows[$r]->flags & TABLE_TRACKDEFAULTS) )
						{
							$this->values[$r] = intval($_SESSION['g_session_track_defaults'][$table_def->rows[$r]->addparam->name]);
						}
						break;

					case TABLE_SECONDARY:
						$this->values[$r] = array();
						if( $table_def->rows[$r]->default_value
						 || ($table_usedefaults && ($table_def->rows[$r]->flags & TABLE_TRACKDEFAULTS) && $_SESSION['g_session_track_defaults'][$table_def->rows[$r]->addparam->name]) )
						{
							$this->values[$r][] = new Table_Inst_Class(
								$this->object_name, 								// object_name
								$table_def->rows[$r]->addparam->name, 				// table_def_name
								-1, 												// id
								$table_usedefaults,									// use_defaults
								$table_def->name.'.'.$table_def->rows[$r]->name);	// parent_field
						}
						break;
				}
			}
		}
	}

	// function adds an error or a warning to the global error string
	function error($descr, $field = '', $type = 'e')
	{
		global $site;

		if( $field ) {
			$site->msgAdd($field . ': ' . $descr, $type);
		}
		else {
			$site->msgAdd($descr, $type);
		}
	}
	
	// function makes a copy by simply settings all ids to -1
	function copy_record()
	{
		$table_def = Table_Find_Def($this->table_def_name);

		$this->id			= -1;
		$this->is_new		= 1;
		$this->date_created	= strftime("%Y-%m-%d %H:%M:%S");
		$this->date_modified= strftime("%Y-%m-%d %H:%M:%S");
		$this->user_created	= intval($_SESSION['g_session_userid']);
		$this->user_modified= intval($_SESSION['g_session_userid']);
		$this->user_grp		= intval(acl_get_default_grp());
		$this->user_access	= acl_get_default_access();

		for( $r = 0; $r < sizeof($table_def->rows); $r++ )
		{
			switch( $table_def->rows[$r]->flags & TABLE_ROW )
			{
				case TABLE_SECONDARY:
					for( $s = 0; $s < sizeof($this->values[$r]); $s++ ) {
						$this->values[$r][$s]->copy_record();
					}
					break;
			}
		}
	}

	// create the record physically in the db. return the id (only needed for recursive calls as same as $this->id).
	function create_record()
	{
		$db = new DB_Admin;
		$db->query("INSERT INTO " . $this->table_def_name . " (date_created,user_created,user_access,user_grp) VALUES ('$this->date_created',$this->user_created,$this->user_access,$this->user_grp)");
		$this->id = $db->insert_id();
		return $this->id;
	}

	// destroy the record physically from the db
	function destroy_record()
	{
		$db = new DB_Admin;
		$db->query("DELETE FROM " . $this->table_def_name . " WHERE id=$this->id");
		$this->id = -1;
	}

	// write the record physically to the db. the id is already known at this time
	function write_record($on_root_level)
	{
		$table_def = Table_Find_Def($this->table_def_name);
		$this->date_modified= strftime("%Y-%m-%d %H:%M:%S");
		$db = new DB_Admin;

		$query = '';
		for( $r = 0; $r < sizeof($table_def->rows); $r++ )
		{
			if( $table_def->rows[$r]->acl&ACL_EDIT )
			{
				switch( $table_def->rows[$r]->flags & TABLE_ROW )
				{
					case TABLE_TEXT:
						$query .= $table_def->rows[$r]->name . "='" . addslashes($this->values[$r]) . "', ";
						if( $db->column_exists($table_def->name, $table_def->rows[$r]->name.'_sorted') ) {
							require_once('eql.inc.php');
							$query .= $table_def->rows[$r]->name . "_sorted='" . g_eql_normalize_natsort($this->values[$r]) . "', ";
						}
						break;
						
					case TABLE_FLAG:
					case TABLE_INT:
					case TABLE_BITFIELD:
					case TABLE_TEXTAREA:
					case TABLE_PASSWORD:
					case TABLE_DATE:
					case TABLE_DATETIME:
					case TABLE_ENUM:
						$query .= $table_def->rows[$r]->name . "='" . addslashes($this->values[$r]) . "', ";
						break;
					
					case TABLE_BLOB:
						if( $this->values[$r][0] /*blob modified?*/ ) {
							$blob = new G_BLOB_CLASS();
							$blob->name = $this->values[$r][2];
							$blob->mime = $this->values[$r][3];
							$blob->w = intval($this->values[$r][5]);
							$blob->h = intval($this->values[$r][6]);
							$blob->blob = $this->values[$r][1];
							$query .= $table_def->rows[$r]->name . "=" . $db->quote($blob->encode_as_str()) . ", ";
						}
						break;
	
					case TABLE_MATTR:
						$defaults = array();
	
						for( $a = 0; $a < sizeof($this->values[$r]); $a++ ) {
							$db->query("INSERT INTO " . $table_def->name . '_' . $table_def->rows[$r]->name . " (primary_id,attr_id,structure_pos) VALUES ($this->id," .$this->values[$r][$a]. ",$a)");
							$defaults[] = $this->values[$r][$a];
						}
	
						if( $table_def->rows[$r]->flags & TABLE_TRACKDEFAULTS ) {
							$_SESSION['g_session_track_defaults'][$table_def->rows[$r]->addparam->name] = $defaults;
						}
						break;
	
					case TABLE_SATTR:
						$query .= $table_def->rows[$r]->name . "=" . intval($this->values[$r]) . ", ";
						if( $table_def->rows[$r]->flags & TABLE_TRACKDEFAULTS ) {
							$_SESSION['g_session_track_defaults'][$table_def->rows[$r]->addparam->name] = intval($this->values[$r]);
						}
						break;
	
					case TABLE_SECONDARY:
						// find out the previously used secondary IDs
						$prev_secondary_ids = array();
						$db->query("SELECT secondary_id FROM {$table_def->name}_{$table_def->rows[$r]->name} WHERE primary_id=$this->id");
						while( $db->next_record() ) 
						{
							$prev_secondary_ids[] = intval($db->f('secondary_id'));
						}
						
						if( sizeof($prev_secondary_ids) )
						{
							// find out the currently used secondary IDs
							$curr_secondary_ids = array();
							for( $i = 0; $i < sizeof($this->values[$r]); $i++ ) 
							{
								$curr_secondary_ids[] = intval($this->values[$r][$i]->id);
							}
							
							// delete previous secondary IDs if they are no longer used in current
							for( $i = 0; $i < sizeof($prev_secondary_ids); $i++ ) 
							{
								if( !in_array($prev_secondary_ids[$i], $curr_secondary_ids) ) 
								{
									$temp = Table_Find_Def($table_def->rows[$r]->addparam->name);
									$temp->destroy_record_dependencies($prev_secondary_ids[$i]);
									$db->query("DELETE FROM $temp->name WHERE id={$prev_secondary_ids[$i]}");
									$db->query("DELETE FROM {$table_def->name}_{$table_def->rows[$r]->name} WHERE secondary_id={$prev_secondary_ids[$i]}");
								}
							}
						}
						
						// create and/or update the currrent secondary IDs
						for( $i = 0; $i < sizeof($this->values[$r]); $i++ ) 
						{
							if( $this->values[$r][$i]->id == -1 ) 
							{
								$temp = $this->values[$r][$i]->create_record();
								$db->query("INSERT INTO {$table_def->name}_{$table_def->rows[$r]->name} (primary_id,secondary_id) VALUES ($this->id,$temp)");
							}
							
							$this->values[$r][$i]->write_record(0 /*not on root level*/);
							$db->query("UPDATE {$table_def->name}_{$table_def->rows[$r]->name} SET structure_pos=$i WHERE primary_id=$this->id AND secondary_id={$this->values[$r][$i]->id}");
						}
	
						// store defaults
						if( $table_def->rows[$r]->flags & TABLE_TRACKDEFAULTS ) 
						{
							$_SESSION['g_session_track_defaults'][$table_def->rows[$r]->addparam->name] = sizeof($this->values[$r])? 1 : 0;
						}
						break;
				}
			}
		}

		// root stuff
		if( $on_root_level )
		{
			if( acl_check_access("{$table_def->name}.RIGHTS", $this->id, ACL_EDIT) )
			{
				$query .= "user_created=$this->user_created, user_grp=$this->user_grp, user_access=$this->user_access, ";
			}
		}

		// user stuff
		if( $query != '' )
		{
			$this->user_modified = intval($_SESSION['g_session_userid']);
			$query = "UPDATE {$table_def->name} SET {$query}date_modified='$this->date_modified', user_modified=$this->user_modified WHERE id=$this->id";
			
			$db->query($query);
		}
	}

	// Function returns the reference to an attribite table and other
	// information belonging to this field. Used eg. by deprecated_edit_sort.php.
	// Returnes -1 on success
	function getset_attrtable(	$edit_control_index,
								&$attr_values,
								&$attr_table_def_name,
								&$attr_flags,
								&$attr_name,
								$set=0,
								$curr_control_index=0 )
	{
		$table_def = Table_Find_Def($this->table_def_name);

		// skip structure control
		$curr_control_index++;

		// search for attribute control
		for( $r = 0; $r < sizeof($table_def->rows); $r++ )
		{
			$rowflags	= $table_def->rows[$r]->flags;
			$rowtype	= $rowflags&TABLE_ROW;
			
			if( $rowtype == TABLE_MATTR )
			{
					if( $curr_control_index == $edit_control_index ) {
						$attr_table_def_name= $table_def->rows[$r]->addparam->name;
						$attr_flags			= $rowflags;
						$attr_name			= $table_def->rows[$r]->descr;
						if( $set ) {
							$this->values[$r] = $attr_values;
						}
						else {
							$attr_values = $this->values[$r];
						}
						return -1; // done
					}
			}
			
			if( $rowtype == TABLE_SATTR ) 
			{
					if( $curr_control_index == $edit_control_index ) {
						$attr_table_def_name= $table_def->rows[$r]->addparam->name;
						$attr_flags			= $rowflags;
						$attr_name			= $table_def->rows[$r]->descr;
						if( $set ) {
							if( sizeof($attr_values) ) {
								$this->values[$r] = intval($attr_values[sizeof($attr_values)-1]);
							}
							else {
								$this->values[$r] = 0;
							}
						}
						else {
							$attr_values = array();
							if( $this->values[$r] ) {
								$attr_values[0] = $this->values[$r];
							}
						}
						return -1; // done
					}
			}
			
			if( $rowtype == TABLE_SECONDARY )
			{
					for( $s = 0; $s < sizeof($this->values[$r]); $s++ ) 
					{
						if( $curr_control_index == $edit_control_index ) { // may be used for insertfrom
							$attr_table_def_name= $this->table_def_name;
							$attr_flags			= TABLE_SECONDARY; // hack: this does not correspondent to $attr_table_def_name, however, index.php is lucky...
							$attr_name			= $table_def->rows[$r]->descr;
							if( $set ) {
								$this->insertfrom = $attr_values;
							}
							else {
								$attr_values = array();
								if( is_array($this->insertfrom) ) {
									$attr_values = $this->insertfrom;
								}
							}
							return -1; // done
						}

						$curr_control_index = $this->values[$r][$s]->getset_attrtable(
												$edit_control_index,
												$attr_values,
												$attr_table_def_name,
												$attr_flags,
												$attr_name,
												$set,
												$curr_control_index);
						if( $curr_control_index==-1 ) {
							return -1; // done
						}
					}
			}

			$curr_control_index++;
		}

		return $curr_control_index;
	}

	function get_ctrl_name($edit_control_index, &$ret, $curr_control_index = 0)
	{
		$table_def = Table_Find_Def($this->table_def_name);

		// skip structure control
		$curr_control_index++;

		// search for attribute control
		for( $r = 0; $r < sizeof($table_def->rows); $r++ )
		{
			if( $edit_control_index == $curr_control_index ) {
				$ret = "{$table_def->descr}.{$table_def->rows[$r]->descr}";
				return -1;
			}
			
			if( ($table_def->rows[$r]->flags&TABLE_ROW) == TABLE_SECONDARY )
			{
				for( $s = 0; $s < sizeof($this->values[$r]); $s++ )
				{
					if( $edit_control_index == $curr_control_index ) {
						$ret = "{$table_def->descr}.{$table_def->rows[$r]->descr}";
						return -1;
					}
					
					$curr_control_index = $this->values[$r][$s]->get_ctrl_name(
											$edit_control_index, $ret, $curr_control_index);
					if( $curr_control_index==-1 ) {
						return -1; // done
					}
				}
			}

			$curr_control_index++;
		}

		return $curr_control_index;
	}

	//
	// function toggles (set=0) or sets (set=1) the given attribute.
	// return values are:
	// 0 - error
	// 1 - attribute unset
	// 2 - attribute set
	//
	function toggle_attr($tg_control, $tg_id, $set = 0)
	{
		// get all attributes
		if( $this->getset_attrtable($tg_control, $attr_values, $tg_table, $dummy, $dummy) != -1 ) {
			return 0; // error
		}

		// change attributes
		$ret = 0;
		$tg_id = intval($tg_id);
		for( $a = 0; $a < sizeof($attr_values); $a++ ) 
		{
			if( $attr_values[$a] == $tg_id ) {
				if( $set ) {
					return 2;
				}
				else {
					array_splice($attr_values, $a, 1);
					$ret = 1;
					break;
				}
			}
		}

		if( !$ret && acl_check_access("$tg_table.COMMON", $tg_id, ACL_REF, 0 /*no user filter*/) ) {
			$attr_values[] = $tg_id;
			$ret = 2;
		}

		if( !$ret ) {
			global $site;
			$temp = "<a href=\"edit.php?table=$tg_table&id=$tg_id\" target=\"_blank\">$tg_id</a>";
			$site->msgAdd(htmlconstant('_EDIT_ERRNOTREFERENCABLE', $temp), 'e');
		}

		// set attributes back
		$this->getset_attrtable($tg_control, $attr_values, $dummy, $dummy, $dummy, 1 /*set*/);

		return $ret;
	}

	// function modifies the secondary tables.
	// returns the new control index, -1 when done or -2 to delete current.
	// after calling this function, the control[] field is out of synchronisation (cause: area expand/shrink)
	function modify_secondarytable($curr_control_index=0)
	{
		$table_def = Table_Find_Def($this->table_def_name);

		// check structure control
		switch( $_REQUEST['control'][$curr_control_index] )
		{
			case 'deletearea':
				return -2; // let the parent delete this secondary table

			case 'copyarea':
				return -5; // let the parent copy this secondary table

			case 'areaup':
				return -3; // let the parent modify the position

			case 'areadown':
				return -4; // let the parent modify the position

			case 'noaction':
				break;

			default: // add secondary table
				for( $r = 0; $r < sizeof($table_def->rows); $r++ )
				{
					if( ($table_def->rows[$r]->flags & TABLE_ROW) == TABLE_SECONDARY
					 && $r == $_REQUEST['control'][$curr_control_index] )
					{
						$this->values[$r][] = new Table_Inst_Class(
							$this->object_name, 								// object_name
							$table_def->rows[$r]->addparam->name, 				// table_def_name
							-1,													// id
							1,													// use_defaults
							$table_def->name.'.'.$table_def->rows[$r]->name);	// parent_field

						$this->section = 10000; // get section to select by "justAdded"
						$this->values[$r][sizeof($this->values[$r])-1]->justAdded = 1;
						return -1; // done
					}
				}
				break;
		}
		$curr_control_index++;

		// delete child controls if needed, skip all other controls
		for( $r = 0; $r < sizeof($table_def->rows); $r++ )
		{
			if( ($table_def->rows[$r]->flags & TABLE_ROW) == TABLE_SECONDARY )
			{
				for( $s = 0; $s < sizeof($this->values[$r]); $s++ )
				{
					$curr_control_index = $this->values[$r][$s]->modify_secondarytable($curr_control_index);

					if( $curr_control_index==-1 ) // done?
					{
						return -1; // done!
					}

					if( $curr_control_index==-2 ) // remove this area?
					{
						$new_secondary = array();
						for( $s2 = 0; $s2 < sizeof($this->values[$r]); $s2++ )
						{
							if( $s2 != $s ) {
								$new_secondary[] = $this->values[$r][$s2];
							}
						}
						$this->values[$r] = $new_secondary;
						$this->section = 0;
						return -1; // done
					}

					if( $curr_control_index==-3 ) // area up?
					{
						$new_secondary = array();
						for( $s2 = 0; $s2 < sizeof($this->values[$r]); $s2++ )
						{
							if( $s2 == $s-1 )
								$new_secondary[] = $this->values[$r][$s];
							if( $s2 != $s )
								$new_secondary[] = $this->values[$r][$s2];
						}
						$this->values[$r] = $new_secondary;
						$this->section--;
						return -1; // done
					}

					if( $curr_control_index==-4 ) // area down?
					{
						$new_secondary = array();
						for( $s2 = 0; $s2 < sizeof($this->values[$r]); $s2++ )
						{
							if( $s2 != $s )
								$new_secondary[] = $this->values[$r][$s2];
							if( $s2 == $s+1 )
								$new_secondary[] = $this->values[$r][$s];
						}
						$this->values[$r] = $new_secondary;
						$this->section++;
						return -1; // done
					}
					
					if( $curr_control_index==-5 ) // copy area?
					{
						$this->verify_data(0 /*show alert*/, 1 /*load all blob*/);
						$this->values[$r][] = clone( $this->values[$r][$s] ); // EDIT 18.02.2010: "clone" is needed for php 5.x
						$this->section = 10000; // get section to select by "justAdded"
						$this->values[$r][sizeof($this->values[$r])-1]->id = -1;
						$this->values[$r][sizeof($this->values[$r])-1]->justAdded = 1;
						return -1; // done
					}
				}
			}

			$curr_control_index++;
		}

		return $curr_control_index;
	}
	
	function modify_secondarytable_insertfrom($edit_control_index, $idsToAdd, $curr_control_index=0)
	{
		global $site;
		
		$table_def = Table_Find_Def($this->table_def_name);

		$curr_control_index++;
		
		for( $r = 0; $r < sizeof($table_def->rows); $r++ )
		{
			if( ($table_def->rows[$r]->flags & TABLE_ROW) == TABLE_SECONDARY )
			{
				for( $s = 0; $s < sizeof($this->values[$r]); $s++ )
				{
					if( $curr_control_index == $edit_control_index )
					{
						// got the secondary table to insert the stuff after...
						
						// ...collect all secondary-IDs
						$db = new DB_Admin;
						$durchfIds = array();
						for( $i = 0; $i < sizeof($idsToAdd); $i++ )
						{
							$db->query("SELECT secondary_id FROM {$table_def->name}_{$table_def->rows[$r]->name} WHERE primary_id={$idsToAdd[$i]} ORDER BY structure_pos;");
							while( $db->next_record() )
							{
								$durchfIds[] = $db->f('secondary_id');
							}
						}
						
						// ...create objects for the secondary records to add 
						for( $i = 0; $i < sizeof($durchfIds); $i++ )
						{
							$this->values[$r][] = new Table_Inst_Class(
										$this->object_name, 								// object_name
										$table_def->rows[$r]->addparam->name, 				// table_def_name
										$durchfIds[$i],										// id
										1,													// use_defaults
										$table_def->name.'.'.$table_def->rows[$r]->name);	// parent_field
							
							$this->values[$r][sizeof($this->values[$r])-1]->id = -1;
							
							if( $i == 0 )
							{
								$this->section = 10000; // get section to select by "justAdded"
								$this->values[$r][sizeof($this->values[$r])-1]->justAdded = 1;
							}
						}

						$count = sizeof($durchfIds);
						$title = $count==1? 'Datensatz' : 'Datens&auml;tze';
						$site->msgAdd("{$count} $title von {$table_def->descr}.{$table_def->rows[$r]->descr} eingef&uuml;gt.", 'i');
						
						// done
						return;
					}
					
					$curr_control_index = $this->values[$r][$s]->modify_secondarytable_insertfrom($edit_control_index, $idsToAdd, $curr_control_index);

				}
			}
			
			$curr_control_index++;
		}
		
		return $curr_control_index;
	}
	

	// function copies data from control[] to the object
	function modify_controls(&$do_modify_secondarytable, $base_table_name, $curr_control_index=0)
	{
		$table_def = Table_Find_Def($this->table_def_name);

		// rights etc.
		if( $curr_control_index == 0 )
		{
			if( acl_check_access("{$this->table_def_name}.RIGHTS", $this->id, ACL_EDIT) )
			{
				// set access
				$this->user_access = 0;
				for( $i = 0; $i < 9; $i++ ) {
					$value = "grant$i";
					if( $_REQUEST[$value] ) {
						$this->user_access |= 1<<$i;
					}
				}

				if( $this->user_access & 0300 ) { $this->user_access |= 0400; }
				if( $this->user_access & 0030 ) { $this->user_access |= 0040; }
				if( $this->user_access & 0003 ) { $this->user_access |= 0004; }
				
				// set owner
				$db = new DB_Admin;
				$user_created = $_REQUEST['user_created'];
				$temp = strval(htmlconstant('_UNKNOWN')); // does not work in if-close - i don't know why, wrong type?
				if( $user_created == ''
				 || $user_created == '0'
				 || isohtmlentities($user_created) == $temp )
				{
					$this->user_created = 0;
				}
				else 
				{
					$db->query("SELECT id FROM user WHERE loginname='" .addslashes($user_created). "'");
					if( $db->next_record() ) {
						$this->user_created = intval($db->f('id'));
					}
					else {
						$db->query("SELECT id FROM user WHERE name='" .addslashes($user_created). "'");
						if( $db->next_record() ) {
							$this->user_created = intval($db->f('id'));
						}
						else {
							$db->query("SELECT id FROM user WHERE id=" .intval($user_created));
							if( $db->next_record() ) {
								$this->user_created = intval($db->f('id'));
							}
							else {
								if( preg_match('/.*\s\((\d+)\)$/', $user_created, $matches) ) {
									$this->user_created = intval($matches[1]);
								}
								else {
									$this->user_created = strval($user_created);
								}
							}
						}
					}
				}

				// set group
				$this->user_grp = intval($_REQUEST['user_grp']);
			}
		}

		// check structure control, then skip
		if( $_REQUEST['control'][$curr_control_index] != 'noaction' )
			$do_modify_secondarytable = 1;
		$curr_control_index++;

		// go through all other controls
		for( $r = 0; $r < sizeof($table_def->rows); $r++ )
		{
			$rowflags = $table_def->rows[$r]->flags;
			
			if( $rowflags&TABLE_USERDEF )
			{
				$param['cmd']		= 'derender';
				$param['table']		= $table_def->name;
				$param['field']		= $table_def->rows[$r]->name;
				$param['id']		= $this->id; // may be -1
				$param['control']	= $curr_control_index;
				$this->values[$r]	= call_plugin($table_def->rows[$r]->prop['deprecated_userdef'], $param);
			}
			else switch( $rowflags&TABLE_ROW )
			{
				case TABLE_FLAG:
				case TABLE_TEXT:
				case TABLE_TEXTAREA:
				case TABLE_ENUM:
					$this->values[$r] = trim($_REQUEST['control'][$curr_control_index]);
					break;

				case TABLE_INT:
					$temp = trim($_REQUEST['control'][$curr_control_index]);
					if( $rowflags&TABLE_EMPTYONNULL && $temp == '' ) {
						$this->values[$r] = 0;
					}
					else if( $rowflags&TABLE_EMPTYONMINUSONE && $temp == '' ) {
						$this->values[$r] = -1;
					}
					else {
						$this->values[$r] = $temp;
					}
					break;
				
				case TABLE_PASSWORD:
					if( $_REQUEST['control'][$curr_control_index][0] ) {
						if( strpos(' '.$_REQUEST['control'][$curr_control_index][1], '"')
						 || strpos(' '.$_REQUEST['control'][$curr_control_index][1], "'") ) {
							$this->values[$r] = '"'/*error mark*/ . htmlconstant('_EDIT_ERRPASSWORDQUOTED');
						}
						else {
							$this->values[$r] = crypt(trim($_REQUEST['control'][$curr_control_index][1]));
						}
					}
					break;

				case TABLE_BITFIELD:
					$this->values[$r] = 0;
					$bits = explode('###', $table_def->rows[$r]->addparam);
					for( $b = 0; $b < sizeof($bits); $b += 2 )
					{
						if( $_REQUEST['control'][$curr_control_index][$b] ) {
							$this->values[$r] |= intval($bits[$b]);
						}
					}
					break;

				case TABLE_DATE:
					$this->values[$r] = sql_date_from_human($_REQUEST['control'][$curr_control_index], 
						$rowflags&TABLE_DAYMONTHOPT? 'dateopt' : 'date');
					break;

				case TABLE_DATETIME:
					$this->values[$r] = sql_date_from_human($_REQUEST['control'][$curr_control_index], 
						'datetime');
					break;

				case TABLE_BLOB:
					$userfile_name		= $_FILES["userfile_$curr_control_index"]['name'];
					$userfile_type 		= $_FILES["userfile_$curr_control_index"]['type'];
					$userfile_size 		= $_FILES["userfile_$curr_control_index"]['size'];
					$userfile_tmp_name 	= $_FILES["userfile_$curr_control_index"]['tmp_name'];
					if( $userfile_tmp_name && $userfile_tmp_name != 'none' && is_uploaded_file($userfile_tmp_name) && $userfile_size > 0 )
					{
						$userfile_handle = fopen($userfile_tmp_name, 'rb');
						if( $userfile_handle )
						{
							// set blob itself
							$this->values[$r][0] = 1; // blob modified
							$this->values[$r][1] = fread($userfile_handle, $userfile_size);
							$this->values[$r][4] = $userfile_size;
							fclose($userfile_handle);

							// set name
							$this->values[$r][2] = $userfile_name? $userfile_name : 'noname';

							// set size 
							$old_level = error_reporting(0);
							$userfile_dim = GetImageSize($userfile_tmp_name);
							error_reporting($old_level);
							$this->values[$r][5] = $userfile_dim[0];
							$this->values[$r][6] = $userfile_dim[1];

							// set mime type
							$mime = $userfile_type;
							$mime = str_replace(',', ' ', $mime);
							$mime = str_replace(';', ' ', $mime);
							$mime = explode(' ', trim($mime));
							$mime = $mime[0];
							if( !$mime ) {
								switch($userfile_dim[2]) {
									case 1: $mime = 'image/gif'; break;
									case 2: $mime = 'image/jpeg'; break;
									case 3: $mime = 'image/png'; break;
								}
							}
							$this->values[$r][3] = $mime;
						}
					}
					else if( trim($_REQUEST['control'][$curr_control_index][2])=='' )
					{
						// remove blob
						$this->values[$r][0] = 1;	// blob modified
						$this->values[$r][1] = '';	// the blob itself
						$this->values[$r][2] = '';  // name
						$this->values[$r][3] = '';  // mime
						$this->values[$r][4] = 0;	// bytes
						$this->values[$r][5] = 0;	// w
						$this->values[$r][6] = 0;	// h
					}
					break;

				case TABLE_SATTR:
					$curr_attr = trim($_REQUEST['control'][$curr_control_index]);
					if( $curr_attr )
					{
						$curr_id = $table_def->rows[$r]->addparam->get_id_from_txt($curr_attr, $attr_error);
						if( $curr_id ) {
							$this->values[$r] = intval($curr_id); // change attribute
						}
						else {
							$this->values[$r] = strval($curr_attr); // error!
						}
					}
					break;

				case TABLE_MATTR:
					$temp = regGet("edit.seperator.$base_table_name", ';');
					$attr = explode($temp==''? ';' : $temp, $_REQUEST['control'][$curr_control_index]);
					for( $a = 0; $a < sizeof($attr); $a++ )
					{
						$curr_attr = trim($attr[$a]);
						if( $curr_attr )
						{
							$curr_id = $table_def->rows[$r]->addparam->get_id_from_txt($curr_attr, $attr_error);
							if( $curr_id )
							{
								$do_insert = 1; // avoid double values
								for( $a2 = 0; $a2 < sizeof($this->values[$r]); $a2++ ) {
									if( $this->values[$r][$a2] == $curr_id )
										$do_insert = 0;
								}

								if( $do_insert )
									$this->values[$r][] = intval($curr_id);
							}
							else
							{
								$this->values[$r][] = strval($curr_attr); // error!
							}
						}
					}
					break;

				case TABLE_SECONDARY:
					for( $s = 0; $s < sizeof($this->values[$r]); $s++ ) {
						$curr_control_index = $this->values[$r][$s]->modify_controls(
							$do_modify_secondarytable, 
							$base_table_name,
							$curr_control_index);
					}
					break;
			}

			$curr_control_index++;
		}

		return $curr_control_index;
	}

	// function verifies the data.
	//
	// if load_all_blob >= 1, blobs are loaded. this is needed for secondary tables
	function verify_data($show_alert, $load_all_blob = -1000, $root_table_def_name = '')
	{
		$db = new DB_Admin;

		// get root table name
		$table_def = Table_Find_Def($this->table_def_name);
		if( !$root_table_def_name ) {
			$root_table_def_name = $table_def->name;
			$on_root_level = 1;
			$this->title1 = '';
			$this->title2 = '';
		}
		else {
			$on_root_level = 0;
		}

		// go through all rows
		for( $r = 0; $r < sizeof($table_def->rows); $r++ )
		{
			$this->value_errors_addparam[$r] = '';
			$error = '';
			$warning = '';
			$rowflags = $table_def->rows[$r]->flags;
			
			if( $on_root_level && (($rowflags&TABLE_ROW)==TABLE_TEXT) ) {
				if( $this->title1=='' && $rowflags&TABLE_SUMMARY ) {
					$this->title1 = isohtmlentities($this->values[$r]);
				}
				else if( $this->title2=='' && $rowflags&TABLE_LIST ) {
					$this->title2 = isohtmlentities($this->values[$r]);
				}
			}
			
			if( $rowflags&TABLE_USERDEF )
			{
				$param['cmd']		=	'verify';
				$param['table']		=	$table_def->name;
				$param['field']		=	$table_def->rows[$r]->name;
				$param['id']		= 	$this->id; // may be -1
				$param['values']	=&	$this->values[$r];
				$temperr = call_plugin($table_def->rows[$r]->prop['deprecated_userdef'], $param);
				if( $temperr ) {
					$error = $temperr;
				}
			}
			else switch( $rowflags&TABLE_ROW )
			{
				case TABLE_PASSWORD:
					// error: already set on modify_controls()
					if( substr($this->values[$r], 0, 1) == '"' /*error mark*/ ) {
						$error = substr($this->values[$r], 1);
					}
					break;

				case TABLE_INT:
					// errors
					if( strlen(intval($this->values[$r])) != strlen(trim($this->values[$r])) ) {
						$error = htmlconstant('_EDIT_ERRENTERANUMBER');
					}
					else if( $table_def->rows[$r]->addparam ) {
						$minmax = explode('###', $table_def->rows[$r]->addparam);
						if( $this->values[$r] < $minmax[0] || $this->values[$r] > $minmax[1] ) {
							$error = htmlconstant('_EDIT_ERRVALUENOTINRANGE', $minmax[0], $minmax[1]);
						}
					}
					break;

				case TABLE_TEXT:
				case TABLE_TEXTAREA:
					// error / warning: empty field?
					if( $rowflags & TABLE_MUST && $this->values[$r] == '' ) {
						$error = htmlconstant('_EDIT_ERREMPTYTEXTFIELD');
					}

					if( $rowflags&TABLE_RECOMMENTED && $this->values[$r] == '' ) {
						$warning = htmlconstant('_EDIT_WARNEMPTYTEXTFIELD');
					}

					// error / warning: mask okay?
					$rules = explode('###', $table_def->rows[$r]->addparam);
					if( $this->values[$r] != '' && sizeof($rules)>=2 ) 
					{
						$orgValue = $this->values[$r];
						for( $i = 3; $i < sizeof($rules); $i += 2 ) {
							$this->values[$r] = preg_replace($rules[$i], $rules[$i+1], $this->values[$r]);
						}
						
						if( $rules[1] ) {
							if( !preg_match($rules[1], $this->values[$r]) ) {
								$error = htmlconstant('_EDIT_ERRVALUENOTINMASK', trim($rules[0]));
								$this->values[$r] = $orgValue;
							}
						}

						if( $rules[2] ) {
							if( !preg_match($rules[2], $this->values[$r]) ) {
								$warning = htmlconstant('_EDIT_WARNVALUENOTINMASK', trim($rules[0]));
							}
						}
					}

					// error / warning: unique?
					if( ($rowflags&TABLE_UNIQUE || $rowflags&TABLE_UNIQUE_RECOMMENTED) && $this->values[$r] != '' ) {
						$db->query("SELECT id FROM " . $table_def->name . " WHERE " . $table_def->rows[$r]->name . "='" . addslashes($this->values[$r]) . "' AND id!=".intval($this->id));
						if( $db->next_record() ) {
							$href = "<a href=\"edit.php?table=$table_def->name&id=" .$db->f('id'). '" target="_blank">' . $db->f('id') . '</a>';
							if( $rowflags & TABLE_UNIQUE ) {
								$error = htmlconstant('_EDIT_ERRFIELDNOTUNIQUE', $href);
							}
							else {
								$warning = htmlconstant('_EDIT_WARNFIELDNOTUNIQUE', $href);
							}
						}
					}
					break;

				case TABLE_BLOB:
					if( $load_all_blob >= 1 && $this->values[$r][0] == 0 /*blob not loaded*/ ) {
						$db->query("SELECT " . $table_def->rows[$r]->name . " FROM $table_def->name WHERE id=$this->id");
						if( $db->next_record() ) {
							$blob = new G_BLOB_CLASS($db->fs($table_def->rows[$r]->name));
							$this->values[$r][0] = 1; // blob modified
							$this->values[$r][1] = $blob->blob; // the blob itself
						}
					}

					if( $rowflags & TABLE_MUST && $this->values[$r][4] /*bytes*/ == 0 ) {
						$error = htmlconstant('_EDIT_ERREMPTYFILEFIELD');
					}

					global $record_has_blob;
					$record_has_blob = 1;
					break;

				case TABLE_SATTR:
					if( ($rowflags & TABLE_MUST) 
					 && $this->values[$r] === 0 )
					{
						$error = htmlconstant('_EDIT_ERREMPTYATTRFIELD');
					}
					else if( is_string($this->values[$r]) )
					{
						$error .= htmlconstant('_EDIT_ERRUNKNOWNVALUE', $this->values[$r]);
						$this->value_errors_addparam[$r] .= $this->values[$r] . "\n";
						$this->values[$r] = 0;
					}
					else if( !($this->values[$r] === 0) 
					      &&  acl_get_access("{$table_def->rows[$r]->addparam->name}.COMMON")
					      && !acl_check_access("{$table_def->rows[$r]->addparam->name}.COMMON", $this->values[$r], ACL_REF, 0 ) //no user filter
						  ) 
					{
						if( is_referencable($db, $table_def->rows[$r]->addparam->name, $this->values[$r]) )
						{
							$warning .= htmlconstant('_EDIT_ERRNOTREFERENCABLE', $this->values[$r]);
						}
						else
						{
							$error .= htmlconstant('_EDIT_ERRNOTREFERENCABLE', $this->values[$r]);
							$this->values[$r] = 0;
						}
						
					}
					break;

				case TABLE_MATTR:
					for( $a = 0; $a < sizeof($this->values[$r]); $a++ )
					{
						if( is_string($this->values[$r][$a]) )
						{
							$error .= htmlconstant('_EDIT_ERRUNKNOWNVALUE', $this->values[$r][$a]);
							$this->value_errors_addparam[$r] .= $this->values[$r][$a] . "\n";
							array_splice($this->values[$r], $a, 1);
							$a--; // continue with the same index
						}
						else if( acl_get_access("{$table_def->rows[$r]->addparam->name}.COMMON")
							  && !acl_check_access("{$table_def->rows[$r]->addparam->name}.COMMON", $this->values[$r][$a], ACL_REF, 0 ) ) // no user filter
						{
							if( is_referencable($db, $table_def->rows[$r]->addparam->name, $this->values[$r][$a]) )
							{
								$warning .= htmlconstant('_EDIT_ERRNOTREFERENCABLE', $this->values[$r][$a]) . ' ';
							}
							else
							{
								$error .= htmlconstant('_EDIT_ERRNOTREFERENCABLE', $this->values[$r][$a]) . ' ';
								array_splice($this->values[$r], $a, 1); $a--; // continue with the same index
							}
						}
					}

					if( ($rowflags & TABLE_MUST) && sizeof($this->values[$r]) == 0 ) {
						$error .= htmlconstant('_EDIT_ERREMPTYATTRFIELD');
					}
					break;

				case TABLE_DATE:
				case TABLE_DATETIME:
					if( ($rowflags & TABLE_MUST) || $this->values[$r] != '0000-00-00 00:00:00' ) {
						$temperr = check_sql_date($this->values[$r]);
						if( $temperr ) {
							$error = $temperr;
						}
					}
					break;

				case TABLE_SECONDARY:
					for( $s = 0; $s < sizeof($this->values[$r]); $s++ ) {
						$this->values[$r][$s]->verify_data($show_alert, $load_all_blob+1, $root_table_def_name);
					}
					break;

				default:
					break;
			}

			if( $error || $warning ) 
			{
				if( $show_alert ) {
					$this->error($error? $error : $warning, $table_def->rows[$r]->descr, $error? 'e' : 'w');
				}
				
				if( $error ) {
					$this->value_errors[$r] = $error;
				}
				else if( $warning ) {
					$this->value_errors[$r] = "warning: $warning";
				}
			}
			else 
			{
				$this->value_errors[$r] = '';
			}
		}

		// rights etc.
		if( $on_root_level ) {
			if( is_string($this->user_created) ) {
				$this->error(htmlconstant('_EDIT_ERRUNKNOWNVALUE', isohtmlentities($this->user_created)), htmlconstant('_EDIT_GRANTSOWNER'));
			}

			if( is_string($this->user_grp) ) {
				$this->error(htmlconstant('_EDIT_ERRUNKNOWNVALUE', isohtmlentities($this->user_grp)), htmlconstant('_EDIT_GRANTSGROUP'));
			}
			
		}
	}

	// function handles all possible submit/click events
	function handle_submit()
	{
		global $site;
		
		$data_verified = 0;
		
		$trigger_script = '';

		$logwriter = new LOG_WRITER_CLASS;
		$logid     = $this->id;
		$logaction = '';
		
		// set an attribute control
		if( isset($_REQUEST['tg_control']) && isset($_REQUEST['tg_id']) ) {
			$this->toggle_attr($_REQUEST['tg_control'], $_REQUEST['tg_id'], 1 /*set*/);
		}

		// set section	
		if( isset($_REQUEST['section']) ) {
			$this->section = $_REQUEST['section'];
		}

		// "insertfrom"?
		if( is_array($this->insertfrom) && sizeof($this->insertfrom) && isset($_REQUEST['tg_control']) )
		{
			$this->modify_secondarytable_insertfrom($_REQUEST['tg_control'], $this->insertfrom);
			$this->insertfrom = 0;
		}

		// any data submitted?
		if( isset($_REQUEST['submit_anything']) )
		{
			// copy information from <form> to the object
			$do_modify_secondarytable = 0;
			$this->modify_controls($do_modify_secondarytable, $this->table_def_name);

			// remove/add/expand/shrink secondary tables?
			if( $do_modify_secondarytable ) {
				$this->modify_secondarytable(0);
			}

			// handle sumbit buttons
			if( isset($_REQUEST['submit_ok']) || isset($_REQUEST['submit_apply']) )
			{
				$this->verify_data(1 /*show alert*/, 0 /*load all blob of secondary tables*/);
				$data_verified = 1;
				if( !$site->msgCount() )
				{
					$table_def = Table_Find_Def($this->table_def_name);
					if( ( $this->is_new && $table_def->acl&ACL_NEW)
					 || (!$this->is_new && acl_check_access("$this->table_def_name.COMMON", $this->id, ACL_EDIT)) )
					{
						$site->msgReset();

						if( $table_def->trigger_script )
						{
							$trigger_script = $table_def->trigger_script;
						}

						if( $this->id == -1 )  
						{
							$this->create_record();
							$this->write_record(1 /*on root level*/);
							$trigger_param = array('action'=>'afterinsert', 'id'=>$this->id);

							$logid     = $this->id;
							$logaction = 'create';
						}
						else 
						{
							$logwriter->addDataFromTable($this->table_def_name, $logid, 'preparediff');
							$logaction = 'edit';
						
							$table_def->destroy_record_dependencies($this->id, 0 /*don't destroy secondary records*/);
							$this->write_record(1 /*on root level*/);
							$trigger_param = array('action'=>'afterupdate', 'id'=>$this->id);
						}
						
						$site->msgAdd(htmlconstant('_EDIT_RECORDSAVED'), 'i');
					}
					else 
					{
						$this->error(str_replace("\n", "<br />", htmlconstant('_ERRACCESS')));
					}
				}

				if( $site->msgCount() )
				{
					$this->error("\n" . htmlconstant('_EDIT_ERRRECORDNOTSAVED'));
					$trigger_script = '';
				}
			}
		}
		else if( $_REQUEST['copy_record'] )
		{
			if( !$site->msgCount() )
			{
				$this->verify_data(0 /*show alert*/, 1 /*load all blob of secondary tables*/);
				$data_verified = 1;
				$this->copy_record();
				if( $site->msgCount() ) {
					$this->error('###' . htmlconstant('_EDIT_ERRRECORDNOTCOPIED'));
				}
			}
		}
		else if( $_REQUEST['delete_record'] )
		{
			if( !$site->msgCount() )
			{
				if( $this->id != -1 )
				{
					if( $this->is_new
					 || acl_check_access("$this->table_def_name.COMMON", $this->id, ACL_DELETE) )
					{
						$table_def = Table_Find_Def($this->table_def_name);
						if( $table_def->num_references($this->id, $dummy) )
						{
							$this->error(htmlconstant('_EDIT_ERRREFERENCED'));
						}
						else
						{
							if( $table_def->trigger_script )
							{
								$trigger_script = $table_def->trigger_script;
								$trigger_param  = array('action'=>'afterdelete', 'id'=>$this->id);
							}

							$logwriter->addDataFromTable($this->table_def_name, $logid, 'dump');
							$logaction = 'delete';
							
							$table_def->destroy_record_dependencies($this->id);
							$this->destroy_record();
							$site->msgAdd(htmlconstant('_EDIT_RECORDDELETED'), 'i');
						}
					}
					else
					{
						$this->error(str_replace("\n", "###", htmlconstant('_ERRACCESS')));
					}
				}
			}

			if( $site->msgCount() ) {
				$this->error('###' . htmlconstant('_EDIT_ERRRECORDNOTDELETED'));
			}
		}

		// are there triggers to call?
		if( $trigger_script )
		{
			call_plugin($trigger_script, $trigger_param);
			if( $trigger_param['returnmsg'] )
			{
				$site->msgAdd($trigger_param['returnmsg'], 'i');
			}
		}
		
		// finalize the logging, this should be done after the triggers are called
		if( $logaction != '' )
		{
			if( $logaction == 'edit' ) {
				$logwriter->addDataFromTable($this->table_def_name, $logid, 'creatediff');
			}
			$logwriter->log($this->table_def_name, $logid, $_SESSION['g_session_userid'], $logaction);
		}

		// close dialog?
		if( !$site->msgCount('e') && !$site->msgCount('w') )
		{
			if( isset($_REQUEST['submit_ok']) || isset($_REQUEST['submit_cancel']) || $_REQUEST['delete_record'] )
			{
				$tempurl = "index.php?table={$this->table_def_name}&justedited={$this->id}#id{$this->id}";
				unset($_SESSION[$this->object_name]);
				redirect($tempurl);
			}
			else if( $trigger_param['returnreload'] )
			{
				$tempurl = "edit.php?table={$this->table_def_name}&id={$this->id}&section={$this->section}";
				unset($_SESSION[$this->object_name]);
				redirect($tempurl);
			}
		}

		// goto other page?
		if( $_REQUEST['goto'] ) {
			redirect($_REQUEST['goto']);
		}
		
		// verify data
		if( !$data_verified ) {
			$this->verify_data(0 /*show alert*/);
		}
	}

	function renderStructureMenu($structuremenu_name='', $structuremenu_values='')
	{
		$ret = '';
		
		if( $structuremenu_values!='' )
		{
			$structuremenu_values = explode('###', $structuremenu_values);
			$ret .= '<select class="structuremenu" name="' .$structuremenu_name. '" size="1" onchange="submit();">';
			
				$ret .= '<option value="noaction" selected="selected">';
					$ret .= htmlconstant('_EDIT_STRUCTCREATE___');
				$ret .= '</option>';
				for( $v = 0; $v < sizeof($structuremenu_values); $v+=2 ) 
				{
					if( $structuremenu_values[$v]!='' && $structuremenu_values[$v+1]!='' )
					{
						$ret .= '<option value="' .$structuremenu_values[$v]. '">';
							$ret .= $structuremenu_values[$v+1];
						$ret .= '</option>';
					}
				}
				
			$ret .= '</select>';
			$ret .=  '<noscript>';
				$ret .= '<input class="button" type="submit" name="submit_noscript" value="' .htmlconstant('_OK'). '" />';
			$ret .= '</noscript>';
		}
		
		return $ret;
	}

	function renderGrants($grantBit, $readonly)
	{
		if( $readonly ) 
		{
			$anythingWritten = 0;
			
			if( $this->user_access & (1<<$grantBit) ) {
				form_hidden("grant$grantBit", $this->user_access & (1<<$grantBit));
				echo htmlconstant('_READ');
				$anythingWritten = 1;
			}
			$grantBit--;

			if( $this->user_access & (1<<$grantBit) ) {
				if( $anythingWritten ) echo ', ';
				form_hidden("grant$grantBit", $this->user_access & (1<<$grantBit));
				echo htmlconstant('_EDIT') . '/' . htmlconstant('_DELETE');
				$anythingWritten = 1;
			}
			$grantBit--;

			if( $this->user_access & (1<<$grantBit) ) {
				if( $anythingWritten ) echo ', ';
				form_hidden("grant$grantBit", $this->user_access & (1<<$grantBit));
				echo htmlconstant('_EDIT_GRANTREF');
			}
		}
		else 
		{
			form_control_check("grant$grantBit", $this->user_access & (1<<$grantBit), 'if(!checked){document.forms[0].grant'.($grantBit-1).'.checked=0;document.forms[0].grant'.($grantBit-2).'.checked=0;}return true;', 0, 1);
			echo "<label for=\"grant$grantBit\">" . htmlconstant('_READ') . "</label>&nbsp; ";
			$grantBit--;
		
			form_control_check("grant$grantBit", $this->user_access & (1<<$grantBit), 'if(checked){document.forms[0].grant'.($grantBit+1).'.checked=1;}return true;', 0, 1);
			echo "<label for=\"grant$grantBit\">" . htmlconstant('_EDIT') . '/' . htmlconstant('_DELETE') . "</label>&nbsp; ";
			$grantBit--;
		
			form_control_check("grant$grantBit", $this->user_access & (1<<$grantBit), 'if(checked){document.forms[0].grant'.($grantBit+2).'.checked=1;}return true;', 0, 1);
			echo "<label for=\"grant$grantBit\">" . htmlconstant('_EDIT_GRANTREF') . "</label>&nbsp; ";
		}
	}

	function renderRights($num_references, $references)
	{
		global $site;
	
		$table_def = Table_Find_Def($this->table_def_name);
	
		// get all users
		$db  = new DB_Admin;
		$users_enum = '0###' . htmlconstant('_UNKNOWN');
		$db->query("SELECT id,name,loginname FROM user ORDER BY name");
		while( $db->next_record() )
		{
			$id = intval($db->f('id'));
			
			$name = $db->fs('name');
			if( !$name ) $name = $db->fs('loginname');
			if( !$name ) $name = $id;
			if( $id == $_SESSION['g_session_userid'] ) $name .= ' (' .htmlconstant('_ME'). ')';
		
			$users_enum .= "###$id###$name";
		}
		
		// section start	
		$site->skin->sectionStart();

			// render rights
			$user_rights_readonly = !acl_check_access("{$this->table_def_name}.RIGHTS", $this->id, ACL_EDIT);
			$site->skin->submenuStart();
				echo htmlconstant('_RIGHTS');
			$site->skin->submenuBreak();
				echo '&nbsp;';
			$site->skin->submenuEnd();
			$site->skin->dialogStart();
				form_control_start(htmlconstant('_EDIT_GRANTSOWNER'), 
				 is_string($this->user_created)? htmlconstant('_EDIT_ERRUNKNOWNVALUE', isohtmlentities($this->user_created)) : 0);
					$temp = is_string($this->user_created)? isohtmlentities($this->user_created) : user_html_name($this->user_created);
					
					if( $user_rights_readonly ) {
						echo $temp;
					}
					else {
						echo "<input type=\"text\" name=\"user_created\" value=\"$temp\" size=\"" .(strlen($temp)+4). "\" maxlength=\"250\" />";
					}

				form_control_end();

				form_control_start('');
					$this->renderGrants(8, $user_rights_readonly);
				form_control_end();

				form_control_start(htmlconstant('_EDIT_GRANTSGROUP'),
				 is_string($this->user_grp)? htmlconstant('_EDIT_ERRUNKNOWNVALUE', isohtmlentities($this->user_grp)) : 0);
				
					if( $user_rights_readonly )
					{
						echo grp_html_name($this->user_grp);
					}
					else
					{
						$groups_enum = '';
						$group_found = 0;
						$db->query("SELECT id,shortname,name FROM user_grp ORDER BY name");
						while( $db->next_record() )
						{
							$id = intval($db->f('id'));
							if( $id == $this->user_grp ) {
								$group_found = 1;
							}
				
							$name = isohtmlentities($db->fs('name'));
							if( !$name ) $name = isohtmlentities($db->fs('shortname'));
							if( !$name ) $name = $id;
						
							$groups_enum .= "$id###$name###";
						}

						if( !$group_found && $this->user_grp != 0 ) {
							$groups_enum .= "$this->user_grp###" . htmlconstant('_UNKNOWN') . " ($this->user_grp)###";
						}

						$groups_enum .= "0###" . htmlconstant('_NOGROUP');
						
						form_control_enum('user_grp', $this->user_grp, $groups_enum);
					}

				form_control_end();
				
				form_control_start('');
					$this->renderGrants(5, $user_rights_readonly);
				form_control_end();

				form_control_start(htmlconstant('_EDIT_GRANTSOTHER'));
					$this->renderGrants(2, $user_rights_readonly);
				form_control_end();
			$site->skin->dialogEnd();

			// references
			if( $num_references ) // sizeof($references) contains the number of possible reference fields in the table, $num_references contains the number of references used
			{
				$site->skin->submenuStart();
					echo htmlconstant('_REF');
				$site->skin->submenuBreak();
					echo '&nbsp;';
				$site->skin->submenuEnd();
				$site->skin->dialogStart();
					for( $i = 0; $i < sizeof($references); $i++ )
					{
						$href = '';
						$cnt  = $references[$i][4];
						if( $cnt==1 ) {
							$href = "edit.php?table={$references[$i][0]}&amp;id={$references[$i][5]}";
						}
						else if( $cnt > 1 ) {
							require_once('eql.inc.php');
							$field = g_eql_normalize_func_name($references[$i][2], 0);
							$href = "index.php?table={$references[$i][0]}&amp;f0=$field&amp;v0=$this->id&amp;searchreset=2&amp;searchoffset=0&amp;orderby=date_modified+DESC";
						}
						
						form_control_start($references[$i][1] . '.' . $references[$i][3]);
							if( $cnt >= 1 ) {
								echo "<a href=\"$href\" title=\"".htmlconstant('_EDIT_REF_OPENINTHIS')."\">".htmlconstant($cnt==1?'_EDIT_REF_1REFERENCE':'_EDIT_REF_NREFERENCES', $cnt)."</a>"
								 .   "<a href=\"$href\" title=\"".htmlconstant('_EDIT_REF_OPENINNEW') ."\" target=\"_blank\">&nbsp;&#8599;&nbsp;</a>";
							}
							else {
								echo '0';
							}
						form_control_end();
					}			
					form_control_start('&sum;');
						echo $num_references;
					form_control_end();
				$site->skin->dialogEnd();
			}
			
		// section end
		$site->skin->sectionEnd();
	}


	// render the edit dialog,
	// function returns the control index
	function render	(	$descr = '<toplevel>', 
						$showUp = 0, 
						$showDown = 0, 
						$curr_control_index = 0, 
						$parent_id = -1, /* may also be -1 for _new_ secondary table */
						$base_table_name = ''
					)
	{
		global $site;
		global $record_has_blob;

		// connect to database
		$db = new DB_Admin;

		// get the table definition
		$table_def = Table_Find_Def($this->table_def_name);

		// get some image sizes
		$checkimgsize = GetImageSize("{$site->skin->imgFolder}/check0.gif");
		$areaimgsize = GetImageSize("{$site->skin->imgFolder}/areaup.gif");
		$site->skin->useTabsForSections(regGet('edit.rowrider', 1)!=0);
		
		$inDialog = 0;
		$inSection = 0;
		if( $descr == '<toplevel>' )
		{
			// start page: set base table name
			$base_table_name = $this->table_def_name;

			// start page: get the number of references			
			$num_references = ($this->id == -1) ? 0 : $table_def->num_references($this->id, $references);
			
			// start page: get structure menu, declare sections
			$structuremenu = '';
			$sectionCount = 0;
			$nextIsNewSection = 0;
			for( $r = 0; $r < sizeof($table_def->rows); $r++ ) 
			{
				$rowflags	= $table_def->rows[$r]->flags;
				$rowtype	= $rowflags&TABLE_ROW;
				
				if( $rowtype == TABLE_SECONDARY ) 
				{
					if( $table_def->rows[$r]->acl&ACL_EDIT
					 && acl_check_access("{$table_def->rows[$r]->addparam->name}.COMMON", -1, ACL_NEW) ) 
					{
						$structuremenu .= $r . '###' . $table_def->rows[$r]->descr . '###';
					}
					
					for( $s = 0; $s < sizeof($this->values[$r]); $s++ ) 
					{
						$sectionTitle = trim($table_def->rows[$r]->descr);
						if( sizeof($this->values[$r]) > 1 ) {
							$sectionTitle = $s==0? ("$sectionTitle ".($s+1)) : ($s+1);
						}
						
						if( $this->values[$r][$s]->justAdded ) {
							$this->section = $sectionCount;
							$this->values[$r][$s]->justAdded = 0;
						}
						
						$site->skin->sectionDeclare($sectionTitle, 
							"edit.php?object={$this->object_name}&section=$sectionCount",
							$sectionCount==$this->section);
						$sectionCount++;
					}
					
					$nextIsNewSection = 1;	
				}
				else if( $rowflags&TABLE_NEWSECTION || $nextIsNewSection ) 
				{
					$site->skin->sectionDeclare($table_def->rows[$r]->sectionName? $table_def->rows[$r]->sectionName : trim($table_def->rows[$r]->descr), 
						"edit.php?object={$this->object_name}&section=$sectionCount", 
						$sectionCount==$this->section);
					$sectionCount++;
					$nextIsNewSection = 0;
				}
			}
	
			if( $sectionCount == 0 ) {
				$site->skin->sectionDeclare($table_def->descr,
					"edit.php?object={$this->object_name}&section=$sectionCount", 
					$sectionCount==$this->section);
				$sectionCount++;
				$fakeFirstSection = 1;
			}

			$temp = htmlconstant('_RIGHTS');
			if( $num_references ) $temp .= ', ' . $num_references . ' ' . htmlconstant('_REFABBR');
			$site->skin->sectionDeclare($temp,
				"edit.php?object={$this->object_name}&section=$sectionCount", 
				$sectionCount==$this->section);
			$sectionCount++;

	
			// start page: menu link to index
			$only_secondary = 0;
			$site->menuItem('mmainmenu', $table_def->descr, 
				"<a href=\"index.php?table=$table_def->name\">");

			// start page: menu link to prev/next
			for( $i = 0;  $i < sizeof($_SESSION['g_session_list_results'][$table_def->name]); $i++ ) {
				if( $_SESSION['g_session_list_results'][$table_def->name][$i] == $this->id ) {
					if( $_SESSION['g_session_list_results'][$table_def->name][$i-1] ) {
						$prev_url = "edit.php?table={$this->table_def_name}&amp;id=".$_SESSION['g_session_list_results'][$table_def->name][$i-1]."&amp;free_object={$this->object_name}";
					}
					if( $_SESSION['g_session_list_results'][$table_def->name][$i+1] ) {
						$next_url = "edit.php?table={$this->table_def_name}&amp;id=".$_SESSION['g_session_list_results'][$table_def->name][$i+1]."&amp;free_object={$this->object_name}";
					}
					break;
				}
			}
			
			$site->menuItem('mprev', htmlconstant('_PREVIOUS'), 
				$prev_url? "<a href=\"$prev_url\">" : '');
				
			$site->menuItem('mnext', htmlconstant('_NEXT'),
				$next_url? "<a href=\"$next_url\">" : '');

			// start page: menu link to search (same as index)
			$site->menuItem('msearch', htmlconstant('_SEARCH'), 
				"<a href=\"index.php?table=$table_def->name\">");

			// start page: menu link to new/empty
			if( $table_def->acl&ACL_NEW ) {
				$only_secondary = $table_def->is_only_secondary($only_secondary_primary_table_name, $only_secondary_primary_table_field);
				
				$site->menuItem('mnew', htmlconstant('_NEW'), 
					$only_secondary? '' : "<a href=\"edit.php?table={$table_def->name}&amp;free_object={$this->object_name}\">");
				
				if( $table_def->uses_track_defaults() ) {
					$site->menuItem('mempty', htmlconstant('_EMPTY'), 
						$only_secondary? '' : "<a href=\"edit.php?table={$table_def->name}&amp;nodefaults=1&amp;free_object={$this->object_name}\">");
				}
			}

			// start page: menu link to copy
			if( $table_def->acl&ACL_NEW ) 
			{
				$site->menuItem('mcopy', htmlconstant('_EDIT_COPY'), 
					(!$only_secondary && $this->id!=-1)? "<a href=\"edit.php?copy_record=1&amp;object={$this->object_name}\">" : '');
			}

			// start page: menu link to delete
			if( $this->is_new || $table_def->acl&ACL_DELETE )
			{
				if( $this->id!=-1 
				 && $num_references==0 
				 && acl_check_access("$this->table_def_name.COMMON", $this->id, ACL_DELETE) )
				{
					$site->menuItem('mdel', htmlconstant('_DELETE'),
							"<a href=\"edit.php?object=$this->object_name&amp;delete_record=1\" onclick=\"return confirm('" .htmlconstant('_EDIT_REALLYDELETERECORD'). "')\">");
				}
				else 
				{
					$site->menuItem('mdel', htmlconstant('_DELETE'), '');
				}
			}

			$any_access = acl_get_access("$this->table_def_name.COMMON", $this->id);

			// start page: menu link to view
			$viewurl = '';
			if( $table_def->name == 'user' || $table_def->name == 'user_grp' ) {
				$viewurl = 'user_access_view.php?showfields=1' . ($table_def->name=='user'? "&amp;user=$this->id" : "");
			}
			else if( @file_exists("config/view_{$table_def->name}.inc.php") ) {
				$viewurl = 'module.php?module=view_' . $table_def->name . '&amp;id=' .$this->id;
			}
			
			if( $viewurl )
			{
				$site->menuItem('mview', htmlconstant('_VIEW'), 
					($this->id!=-1 && $any_access)? "<a href=\"$viewurl\" target=\"_blank\">" : ''); // was before 2011-03-27: target=\"index_view\" onclick=\"return popup(this,750,550);\"
			}

			// start page: menu link to edit plugin(s)
			for( $i = 0; $i <= 3; $i++ ) {
				if( @file_exists("config/edit_plugin_{$table_def->name}_{$i}.inc.php") ) {
		 			$site->menuItem("mplugin$i", htmlconstant(strtoupper("_edit_plugin_{$table_def->name}_{$i}")), 
		 				($this->id!=-1 && $any_access)? "<a href=\"module.php?module=edit_plugin_{$table_def->name}_{$i}&amp;id={$this->id}\" target=\"edit_plugin_{$table_def->name}_{$i}\" onclick=\"return popup(this,750,550);\">" : '');
				}
				else {
					break;
				}
			}

			// start page: set title and check access
			$site->title = $table_def->descr 
				. ($this->id>0? '' : (' - '.htmlconstant('_NEW')))
				. ($this->title1? " - $this->title1" : ($this->title2? " - $this->title2" : ""));
			
			if( !$any_access ) {
				$site->msgAdd(htmlconstant('_ERRACCESS') . "\n\n<a href=\"index.php?table=$table_def->name\">" .htmlconstant('_CANCEL'). "</a>");
				$site->pageStart();
				$site->menuSettingsUrl = "settings.php?table=$table_def->name&scope=edit&reload=".urlencode("edit.php?table=$table_def->name&id=$this->id");
				$site->menuOut();
				$site->pageEnd();
				return;
			}

			// only secondary? --> link to primary record
			if( $only_secondary ) {
				$db->query("SELECT primary_id FROM $only_secondary_primary_table_name"."_$only_secondary_primary_table_field WHERE secondary_id=$this->id");
				if( $db->next_record() ) {
					$site->msgAdd(htmlconstant('_EDIT_ONLYSECONDARYDATA', '<a href="edit.php?table=' .$only_secondary_primary_table_name. '&id=' .$db->f('primary_id'). '&free_object=' .$this->object_name. '">', '</a>'), 'w');
				}
			}

			// start page: now, really start the page!
			$site->pageStart();
			
			$site->menuBinParam		= "table=$table_def->name&id=$this->id";
			$site->menuSettingsUrl	= "settings.php?table=$table_def->name&scope=edit&reload=" .urlencode("edit.php?free_object=$this->object_name&table=$table_def->name" . ($this->id!=-1? "&id=$this->id" : ""));
			$site->menuPrintUrl		= ($this->id!=-1 && $any_access)? "print.php?table=$table_def->name&id=$this->id" : '';
			$site->menuHelpScope	= $table_def->name . '.ieditrecords';
			$site->menuLogoutUrl	= 'edit.php?table='.$table_def->name.'&id='.$this->id;
			$site->menuFreeObject	= $this->object_name;
			$site->menuOut();

			// start form
			echo '<a name="c"></a>';
			form_tag($this->object_name, 'edit.php', '', $record_has_blob? 'multipart/form-data' : '');
			if( $record_has_blob ) {
				form_hidden('MAX_FILE_SIZE', 10000000 /*10 MB*/);
			}
			form_hidden('object', $this->object_name);
			form_hidden('submit_anything', 1); // generic flag to indicate that the formular was posted - needed as we have _many_ submit possibilities due the structure menu
			form_hidden('section', $this->section);
			
			$curr_control_index++;
			
			// start dialog
			$site->skin->dialogStart();
			$inDialog = 1;

				// id/activate out
				form_control_start(htmlconstant('_ID'));

					// ID
					if( isset($this->sync_src) ) echo '<span title="Datenbankkennung: '.$this->sync_src.'">';
					echo ($this->id == -1)? htmlconstant('_NA') : $this->id;
					if( isset($this->sync_src) ) echo '</span>';
					
					// bin	
					if( $this->id > 0 && regGet('toolbar.bin', 1) ) {
						echo ' ';
						echo bin_render($this->table_def_name, $this->id);
					}
	
					if( $structuremenu ) {
						$site->skin->sectionDeclareRight($this->renderStructureMenu("control[" . ($curr_control_index-1) . "]", $structuremenu));
					}
				form_control_end();
			
			if( $fakeFirstSection )
			{
				$site->skin->dialogEnd();
				$site->skin->sectionStart();
				$site->skin->submenuStart();
					echo $table_def->descr;
				$site->skin->submenuBreak();
					echo '&nbsp;';
				$site->skin->submenuEnd();
				$site->skin->dialogStart();
				
				$inSection = 1;
			}
		}
		else
		{
			// start dialog
			$site->skin->submenuStart();
			
				echo $descr . '&nbsp; &nbsp; ';
				
				if( $table_def->acl&ACL_NEW )
				{
					echo imgbuttonRender(
						"control[$curr_control_index]", 'copyarea', 
						'', "{$site->skin->imgFolder}/dummy.gif", /*image is only used if JavaScript is not available - and we do not allow logins with JavaScript disabled */
						htmlconstant('_EDIT_COPY') . '&nbsp;&nbsp; ');
				}
				
				if( $table_def->acl&ACL_DELETE ) 
				{
					echo imgbuttonRender(
						"control[$curr_control_index]", 'deletearea', 
						"", "{$site->skin->imgFolder}/dummy.gif", /*image is only used if JavaScript is not available - and we do not allow logins with JavaScript disabled*/
						htmlconstant('_DELETE') . '&nbsp;&nbsp; ', '', '',
						"confirm('" .htmlconstant('_EDIT_STRUCTDELETEASK'). "')");
				}

				if( $table_def->acl&ACL_NEW )
				{
					$temp = "deprecated_index.php?table={$base_table_name}&object={$this->object_name}&edit_control={$curr_control_index}";
					echo imgbuttonRender(
						'goto', $temp, 
						'', "{$site->skin->imgFolder}/dummy.gif", /*image is only used if JavaScript is not available - and we do not allow logins with JavaScript disabled*/
						htmlconstant('_EDIT_INSERTFROM') . '&nbsp;&nbsp; ');
				}
				
			$site->skin->submenuBreak();
			
				if( ($showUp || $showDown) && $table_def->acl&ACL_EDIT)
				{
					if( $showUp ) {
						echo imgbuttonRender("control[$curr_control_index]", 'areaup', "{$site->skin->imgFolder}/areaup.gif", "{$site->skin->imgFolder}/areauproll.gif", '', '^', htmlconstant('_EDIT_STRUCTUP'));
					}
					else {
						echo "<img src=\"{$site->skin->imgFolder}/areaupdis.gif\" width=\"{$areaimgsize[0]}\" height=\"{$areaimgsize[1]}\" border=\"0\" alt=\"\" />";
					}
					
					if( $showDown ) {
						echo imgbuttonRender("control[$curr_control_index]", 'areadown', "{$site->skin->imgFolder}/areadown.gif", "{$site->skin->imgFolder}/areadownroll.gif", '', '^', htmlconstant('_EDIT_STRUCTDOWN'));
					}
					else {
						echo "<img src=\"{$site->skin->imgFolder}/areadowndis.gif\" width=\"{$areaimgsize[0]}\" height=\"{$areaimgsize[1]}\" border=\"0\" alt=\"\" />";
					}
					
				}
				
				echo '&nbsp;';
				
			$site->skin->submenuEnd();
			
			$site->skin->dialogStart();
			$inDialog = 1;
			$curr_control_index++;
		}

		// controls out
		$control_started = 0;
		$nextIsNewSection = 0;
		for( $r = 0; $r < sizeof($table_def->rows); $r++ )
		{
			$rowflags	= $table_def->rows[$r]->flags;
			$rowtype	= $rowflags & TABLE_ROW;
			$rowdescr	= trim($table_def->rows[$r]->descr);
			
			if( $rowtype == TABLE_SECONDARY )
			{
				if( $descr != '<toplevel>' ) {
					echo '<h1>ERROR: secondary tables are only allowed at top-level!</h1>';
					exit();
				}
				
				if( sizeof($this->values[$r]) )
				{
					if( $inDialog ) {
						$site->skin->dialogEnd();
						$inDialog = 0;
					}
					
					if( $inSection ) {
						$site->skin->sectionEnd();
						$inSection = 0;
					}
					
					for( $s = 0; $s < sizeof($this->values[$r]); $s++ )
					{
						$site->skin->sectionStart();
						
							$curr_control_index = $this->values[$r][$s]->render(
								sizeof($this->values[$r])==1? $rowdescr : ("$rowdescr ".($s+1)),
								($s!=0)? 1 : 0, /* showUp */
								($s!=sizeof($this->values[$r])-1)? 1 : 0, /* showDown */
								$curr_control_index,
								$this->id,
								$base_table_name);
							
						$site->skin->sectionEnd();
					}
				}
				
				$nextIsNewSection = 1;
			}
			else
			{
				// start dialog if not yet done
				if( $rowflags&TABLE_NEWSECTION || $nextIsNewSection ) 
				{
					if( $inDialog )	{ 
						$site->skin->dialogEnd();
					}
					
					if( $descr == '<toplevel>' ) {
						if( $inSection ) { 
							$site->skin->sectionEnd();
						}
	
						$site->skin->sectionStart();
						$inSection = 1;			
						$nextIsNewSection = 0;
					}
					
					$site->skin->submenuStart();
						echo $table_def->rows[$r]->sectionName? $table_def->rows[$r]->sectionName : $rowdescr;
					$site->skin->submenuBreak();
					$site->skin->submenuEnd();

					$site->skin->dialogStart();
					$inDialog = 1;
				}
				
				// start control line if not yet done
				$label = $rowdescr;
				$tooltip = $table_def->rows[$r]->prop['help.tooltip'];
				if( $tooltip )
				{
					$label = '<span title="'.$tooltip.'">' . $label . '</span>';
				}
				
				$css_class = '';
				if( $table_def->rows[$r]->prop['css.class'] )
					$css_class = $table_def->rows[$r]->prop['css.class']; 
							
				if( $control_started ) {
					form_control_continue($label,
						$this->value_errors[$r],
						$css_class);
				}
				else {
					form_control_start($label,
						$this->value_errors[$r],
						$css_class);
					$control_started = 1;
				}

				if( $rowflags & TABLE_USERDEF )
				{
					$param['cmd']		=	'render';
					$param['table']		=	$table_def->name;
					$param['field']		=	$table_def->rows[$r]->name;
					$param['id']		= 	$this->id; // may be -1
					$param['control']	=	$curr_control_index;
					$param['values']	=&	$this->values[$r];
					$param['readonly']	=	!($table_def->rows[$r]->acl&ACL_EDIT);
					echo call_plugin($table_def->rows[$r]->prop['deprecated_userdef'], $param);
				}
				else switch( $rowtype )
				{
					case TABLE_FLAG:
						form_control_check("control[$curr_control_index]",
							$this->values[$r],
							'',
							!($table_def->rows[$r]->acl&ACL_EDIT));
						break;

					case TABLE_BITFIELD:
						$value = $this->values[$r];
						$bits = explode('###', $table_def->rows[$r]->addparam);
						$anythingWritten = 0;
						for( $b = 0; $b < sizeof($bits); $b += 2 )
						{
							if( $table_def->rows[$r]->acl&ACL_EDIT )
							{
								form_control_check('control[' .$curr_control_index. '][' .$b. ']', intval($value) & intval($bits[$b]), '', 0, 1 /*add label*/);
								echo '<label for="control[' .$curr_control_index. '][' .$b. ']">';
									echo trim($bits[$b+1]);
								echo '</label>';
								
								if( $b != sizeof($bits)-2 ) {
									echo substr($bits[$b+1], -1) == ' '? ' ' : '<br />';
								}
							}
							else
							{
								form_hidden('control[' .$curr_control_index. '][' .$b. ']', intval($value) & intval($bits[$b]));
								if( intval($value) & intval($bits[$b]) ) {
									if( $anythingWritten ) echo ', ';
									echo trim($bits[$b+1]);
									$anythingWritten = 1;
								}
							}
						}
						break;

					case TABLE_ENUM:
						form_control_enum("control[$curr_control_index]",
							$this->values[$r],
							$table_def->rows[$r]->addparam,
							!($table_def->rows[$r]->acl&ACL_EDIT));
						break;

					case TABLE_TEXT:
						$width = str_replace(' ', '', regGet("edit.field.{$table_def->name}.{$table_def->rows[$r]->name}.size", '40 x 1'));
						list($width) = explode('x', $width);
						$width = intval($width);
						if( $width < 3 || $width > 200 ) $width = 40;
						
						if( $table_def->rows[$r]->addparam ) {
							$rules = explode('###', $table_def->rows[$r]->addparam);
							if( substr($rules[0], -1)!=' ' ) {
								if( strlen(intval($rules[0])) == strlen($rules[0]) ) {
									$width = intval($rules[0]);
								}
								else {
									$width = strlen($rules[0]); // the first field contains the mask, a trailing space indicates no max. length
								}
							}
						}
						
						$param = array('readonly' => !($table_def->rows[$r]->acl&ACL_EDIT), 'css_classes' => $css_class /*, 
								'autocomplete'=>"{$table_def->name}.{$table_def->rows[$r]->name}"*/);
						/*
						if( $rowflags & (TABLE_ACNEST|TABLE_ACNESTSTART) ) {
							$param['autocomplete.nest'] = 1;
						}
						*/
						form_control_text("control[$curr_control_index]",
						 $this->values[$r],
						 $width,
						 0 /*maxlen*/, 
						 $param );

						 
						if( ($rowflags & TABLE_URL) && $this->values[$r] ) 
						{
							make_clickable_url($this->values[$r], $ahref);
							echo $ahref.'&nbsp;&#8599;</a>';
						}
						else if( ($rowflags & TABLE_TEL) && $this->values[$r] ) 
						{
							$nr = $this->values[$r];
							$nr = strtr($nr, array('('=>'', ')'=>'', '-'=>'', '/'=>'', ' '=>''));	// Trennzeichen aus der Telefonnummer entfernen
							preg_match_all('/\d{6,}/', $nr, $matches);
							for( $i = 0; $i < sizeof($matches[0]); $i++ )
							{
								$lnr = $matches[0][$i];
								echo ' <a href="tel:'.urlencode($lnr).'" title="'.htmlconstant('_EDIT_CALL_PHONE', isohtmlspecialchars($lnr)).'">&#9742;</a>';
							}
							
						}
						break;

					case TABLE_TEXTAREA:
						$readonly = !($table_def->rows[$r]->acl&ACL_EDIT);
						
						$html_editor = '';
						if( !$readonly
						 && $rowflags&TABLE_HTML ) {
							$html_editor = regGet("edit.textarea.{$table_def->name}.html", '');
						}

						$width = str_replace(' ', '', regGet("edit.field.{$table_def->name}.{$table_def->rows[$r]->name}.size", '40 x 5'));
						list($width, $height) = explode('x', $width);
						$width = intval($width);
						if( $width < 3 || $width > 200 ) $width = 40;
						$height = intval($height);
						if( $height < 1 || $height > 99 ) $height = 5;

						if( $html_editor == 'ewebeditpro' )
						{
							global $Admin_Ewebeditpro2_Include;
							include_once($Admin_Ewebeditpro2_Include);
							echo eWebEditProEditor("control[$curr_control_index]",
								$height*20, "720",
								$this->values[$r]);
						}
						else if( $html_editor == 'pcwebedit' )
						{
							global $Admin_Pcwebedit_Include;
							include_once($Admin_Pcwebedit_Include);
							form_hidden("control[$curr_control_index]", $this->values[$r]);
							$temp = new pcWebEdit("control[$curr_control_index]", $Admin_Pcwebedit_Include, $_SESSION['g_session_language']);
							echo $temp->create(720, $height*20);
						}
						else
						{
							form_control_textarea("control[$curr_control_index]",
							 $this->values[$r],
							 $width, $height,
							 array('readonly'=>$readonly, 'css_classes'=>trim(regGet("edit.textarea.$base_table_name.css", ''). ' '.$css_class)));
							 
							 // help out
							 $helpattr = '';
							 if( $table_def->rows[$r]->prop['help.url'] )
							 {
								$helpattr = 'href="'.$table_def->rows[$r]->prop['help.url'].'" target="_blank"';
							 }
							 else if( $rowflags&TABLE_WIKI )
							 {
								$helpattr = 'href="help.php?id=iwiki" target="help" onclick="return popup(this,500,380);"';
							 }
							 
							 if( $helpattr )
								echo '<a '.$helpattr.' title="'.htmlconstant('_HELP').'">&nbsp;?&nbsp;</a>';
						}
						break;
					
					case TABLE_INT:
						$maxlen = 12;
						if( $table_def->rows[$r]->addparam ) {
							$minmax = explode('###', $table_def->rows[$r]->addparam);
							$maxlen = strlen($minmax[0]);
							if( strlen($minmax[1]) > $maxlen ) {
								$maxlen = strlen($minmax[1]);
							}
						}
						
						$temp = strval($this->values[$r]);
						if( $rowflags&TABLE_EMPTYONNULL && $temp=='0' ) {
							$temp = '';
						}
						else if( $rowflags&TABLE_EMPTYONMINUSONE && $temp=='-1' ) {
							$temp = '';
						}
						
						form_control_text("control[$curr_control_index]",
						 $temp, $maxlen /*width*/, $maxlen /*maxlen*/, 
						 array('readonly'=>!($table_def->rows[$r]->acl&ACL_EDIT)));
						break;

					case TABLE_PASSWORD:
						if( $table_def->rows[$r]->acl&ACL_EDIT ) {
							echo "<input type=\"radio\" name=\"control[$curr_control_index][0]\" id=\"controllabel1[$curr_control_index]\" value=\"0\" checked=\"checked\" />";
							echo "<label for=\"controllabel1[$curr_control_index]\">" .htmlconstant('_EDIT_DONOTCHANGE'). "</label>&nbsp; ";
							
							echo "<input type=\"radio\" name=\"control[$curr_control_index][0]\" id=\"controllabel2[$curr_control_index]\" value=\"1\" onclick=\"setFocus('control[$curr_control_index][1]'); return true;\" />";
							echo "<label for=\"controllabel2[$curr_control_index]\">" .htmlconstant('_EDIT_CHANGETO'). "</label> ";
							
							require_once("genpassword.inc.php");
							form_control_text("control[$curr_control_index][1]", genpassword(), 12 /*width*/ );
							echo ' <a href="edit.php?table='.$table_def->name.'&id=' .$this->id. '&free_object=' .$this->object_name. "\">" . htmlconstant('_OTHERSUGGESTION___') . '</a>';
						}
						else {
							form_hidden("control[$curr_control_index][0]", 0);
							form_control_text("control[$curr_control_index][1]",
							 '*****', 0 /*width*/, 0 /*maxlen*/, array('readonly'=>true));
						}

						break;

					case TABLE_BLOB:
						$readonly = !( $table_def->rows[$r]->acl&ACL_EDIT );
					
						if( $this->values[$r][4] /*bytes */ )
						{
							if( !$readonly ) form_control_check("control[$curr_control_index][2]", '1');
							
							echo  ' <a href="media.php/' . $table_def->rows[$r]->name . '/' . $table_def->name . '/' . $this->id . '/' . $this->values[$r][2] . '" target="_blank" title="' .htmlconstant('_VIEW'). '">'
								.   isohtmlspecialchars($this->values[$r][2]) . '&nbsp;&#8599;'
							    . '</a>';
							
							echo ' (<a href="media.php?t=' . $table_def->name . '&id='.$this->id . '&f='.$table_def->rows[$r]->name . '&hex=1" target="_blank" title="Hexdump">' . smart_size($this->values[$r][4]) . '</a>';
								if( $this->values[$r][5] && $this->values[$r][6] ) {
									echo ', ' . $this->values[$r][5] . 'x' . $this->values[$r][6];
								}
							echo ')';
						}

						if( !$readonly ) echo ' <input type="file" size="8" name="userfile_' .$curr_control_index. '" />';
						break;

					case TABLE_DATE:
						form_control_datetime("control[$curr_control_index]",
							$this->values[$r],
							($rowflags & TABLE_DAYMONTHOPT)? 'dateopt' : 'date',
							!($table_def->rows[$r]->acl&ACL_EDIT));
						break;

					case TABLE_DATETIME:
						form_control_datetime("control[$curr_control_index]",
							$this->values[$r],
							'datetime',
							!($table_def->rows[$r]->acl&ACL_EDIT));
						break;

					case TABLE_SATTR:
						$attr_readable = acl_get_access($table_def->rows[$r]->addparam->name.'.COMMON')? 1 : 0;
						$attr_editable = $table_def->rows[$r]->acl&ACL_EDIT? $attr_readable : 0;

						echo '<a name="c' .$curr_control_index. '"></a>';
						if( $this->values[$r] )
						{
							$curr_name = $table_def->rows[$r]->addparam->get_summary($this->values[$r], ' / ' /*value seperator*/);

							if( $attr_editable ) {
								echo "<a href=\"deprecated_edit_tgattr.php?object=$this->object_name&tg_control=$curr_control_index&tg_id=" .$this->values[$r]. "\" target=\"tg$curr_control_index"."_".$this->values[$r]."\" onclick=\"return editTgAttr(this,0);\">";
									echo "<img name=\"tg{$curr_control_index}_{$this->values[$r]}\" src=\"{$site->skin->imgFolder}/check2.gif\" width=\"{$checkimgsize[0]}\" height=\"{$checkimgsize[1]}\" border=\"0\" alt=\"[" .htmlconstant('_SELECT'). ']" />';
								echo "</a> ";
							}

							if( $attr_readable ) {
								echo '<a href="edit.php?table=' . $table_def->rows[$r]->addparam->name . '&id=' . $this->values[$r] . '" target="_blank">';
							}

							echo isohtmlentities($curr_name);

							if( $attr_readable ) {
								echo '</a>';
							}

							echo '&nbsp;&nbsp; ';
						}

						if( $attr_editable )
						{
							$addValues = regGet("edit.field.{$table_def->name}.{$table_def->rows[$r]->name}.addvalues", 0);
							
							if( $this->values[$r] && $addValues ) {
								echo '<br />';
							}
							
							$temp = "deprecated_index.php?table={$table_def->rows[$r]->addparam->name}&object={$this->object_name}&edit_control={$curr_control_index}";
							echo imgbuttonRender("goto", $temp, "{$site->skin->imgFolder}/check3.gif", '', '', '[+]', htmlconstant('_SELECT')) . ' ';
							
							if( $addValues ) {
								$temp = isohtmlentities($this->value_errors_addparam[$r]);
								echo "<input class=\"addvalues\" type=\"text\" name=\"control[$curr_control_index]\" value=\"$temp\" size=\"40\" maxlength=\"250\" title=\"" .htmlconstant('_EDIT_ADDVALUESHINTSINGLE'). '" /><br />';
							}
						}


						break;

					case TABLE_MATTR:
						$attr_table			= $table_def->rows[$r]->addparam->name;
						$attr_readable		= acl_get_access($attr_table.'.COMMON')? 1 : 0;
						$attr_editable		= $table_def->rows[$r]->acl&ACL_EDIT? $attr_readable : 0;
						$attr_break			= ' &nbsp;&nbsp;';
						$attr_addvaluessize	= sizeof($this->values[$r])? 10 : 40;

						// get plugin (if any)
						$attr_plugin = "config/attr_plugin_{$table_def->name}_{$table_def->rows[$r]->name}.inc.php";
						if( !@file_exists($attr_plugin) ) {
							$attr_plugin = '';
						}
						// get all summaries
						$attr_summaries = array();
						for( $a = 0; $a < sizeof($this->values[$r]); $a++ ) {
							$attr_summaries[$a] = $table_def->rows[$r]->addparam->get_summary($this->values[$r][$a],  ' / '/*value seperator*/);
							if( $attr_summaries[$a]=='' ) {
								$attr_summaries[$a] = $this->values[$r][$a];
							}
							if( strlen($attr_summaries[$a]) > 8 ) {
								$attr_break = '<br />';
								$attr_addvaluessize = 40;
							}
						}

						// get all references
						$attr_references = array();
						if( $table_def->name == $attr_table 
						 && $this->id > 0
						 && $rowflags&TABLE_SHOWREF )
						{
							$db->query("SELECT primary_id FROM {$table_def->name}_{$table_def->rows[$r]->name} WHERE attr_id=$this->id ORDER BY structure_pos");
							while( $db->next_record() ) {
								$temp = $table_def->get_summary($db->f('primary_id'),  ' / '/*value seperator*/);
								$attr_references[] = array($db->f('primary_id'), $temp);
								if( strlen($temp) > 8 ) {
									$attr_break = '<br />';
									$attr_addvaluessize = 40;
								}
							}
						}

						// create attribute list
						echo '<a name="c' .$curr_control_index. '"></a>';
						for( $a = 0; $a < sizeof($this->values[$r]); $a++ )
						{
							if( $attr_editable ) {
								echo "<a href=\"deprecated_edit_tgattr.php?object=$this->object_name&tg_control=$curr_control_index&tg_id=" .$this->values[$r][$a]. "\" target=\"tg$curr_control_index"."_".$this->values[$r][$a]."\" onclick=\"return editTgAttr(this,0);\">";
									echo "<img name=\"tg{$curr_control_index}_{$this->values[$r][$a]}\" src=\"{$site->skin->imgFolder}/check2.gif\" width=\"{$checkimgsize[0]}\" height=\"{$checkimgsize[1]}\" border=\"0\" alt=\"[X]\" title=\"" .htmlconstant('_SELECT'). '" />';
								echo "</a>&nbsp;";
							}

							if( $attr_readable ) {
								echo '<a href="edit.php?table=' . $attr_table . '&id=' . $this->values[$r][$a] . '" target="_blank">';
							}

							echo isohtmlentities($attr_summaries[$a]);

							if( $rowflags & TABLE_SHOWID) {
								echo '&nbsp;<b>&lt;'.$this->values[$r][$a] .'&gt;</b>';
							}

							if( $attr_readable ) {
								echo '</a>';
							}
							
							if( $attr_plugin ) {
								echo ' ';
								$param = array('cmd'=>'renderAfterName', 'id'=>$this->values[$r][$a]);
								call_plugin($attr_plugin, $param);
							}
							
							if( $a != sizeof($this->values[$r])-1 ) {
								echo $attr_break;
							}
						}

						// edit / new

						if( sizeof($this->values[$r])
						 && ($attr_editable) ) {
							echo $attr_break;
						}

						if( $attr_editable )
						{
							$temp = "deprecated_index.php?table={$attr_table}&object={$this->object_name}&edit_control={$curr_control_index}";
							echo imgbuttonRender("goto", $temp, "{$site->skin->imgFolder}/check3.gif", '', '', '[+]', htmlconstant('_SELECT')) . ' ';

							if( regGet("edit.field.{$table_def->name}.{$table_def->rows[$r]->name}.addvalues", 0) )
							{
								$sep = regGet("edit.seperator.$base_table_name", ';');
								$temp = isohtmlentities($this->value_errors_addparam[$r]);
								if( !$temp ) $temp = $sep;
								
								echo "<input class=\"addvalues\" type=\"text\" name=\"control[$curr_control_index]\" value=\"$temp\" size=\"$attr_addvaluessize\" maxlength=\"250\" title=\"" .htmlconstant('_EDIT_ADDVALUESHINT', $sep). '" /><br />';
							}

							if( !($rowflags & TABLE_HIDESORT)
							 && sizeof($this->values[$r])>1 ) {
							 	$temp = "deprecated_edit_sort.php?object={$this->object_name}&edit_control={$curr_control_index}";
								echo imgbuttonRender("goto", $temp, "{$site->skin->imgFolder}/sort.gif", "{$site->skin->imgFolder}/sortroll.gif", '', '[^v]', htmlconstant('_EDIT_EDITORDER')) . ' ';
							}
						}

						
						// create references list
						if( sizeof($attr_references) )
						{
							$title = $table_def->rows[$r]->prop['ref.name']; if( $title == '' ) $title = '_REF';
							form_control_end();
							form_control_start(htmlconstant($title));
							$control_started = 1;
							for( $a = 0; $a < sizeof($attr_references); $a++ ) {
								echo "<img src=\"{$site->skin->imgFolder}/check1.gif\" width=\"{$checkimgsize[0]}\" height=\"{$checkimgsize[1]}\" border=\"0\" alt=\"[X]\" title=\"\" />&nbsp;";
								echo '<a href="edit.php?table=' . $attr_table . '&id=' . $attr_references[$a][0] . '" target="_blank">';
									echo isohtmlentities($attr_references[$a][1]);
								echo '</a>';
								if( $a != sizeof($attr_references)-1 ) {
									echo $attr_break;
								}
							}
						}						

						break;
				}

				if( $rowflags&TABLE_PERCENT )
					echo " %";

				// end control line if its description does not end with a space
				if( substr($table_def->rows[$r]->descr, -1) != ' ' ) {
					form_control_end();
					$control_started = 0;
				}
			}

			$curr_control_index++;
		}

		// end dialog
		if( $inDialog ) $site->skin->dialogEnd();
		if( $inSection )$site->skin->sectionEnd();

		if( $descr == '<toplevel>' )
		{
			// rights & references
			$this->renderRights($num_references, $references);
			
			// buttons out, date, protocol
			$site->skin->fixedFooterStart();
			$site->skin->buttonsStart();
				if( $table_def->acl&ACL_EDIT || $this->is_new )
				{
					form_button('submit_ok', htmlconstant('_OK'));
					form_button('submit_cancel', htmlconstant('_CANCEL'));
					form_button('submit_apply', htmlconstant('_APPLY'));
				}
				else
				{
					form_clickbutton("index.php?table={$this->table_def_name}&amp;free_object={$this->object_name}", htmlconstant('_CANCEL'));
				}
				
				if( $this->id != -1 )
				{
					$site->skin->buttonsBreak();
						echo         htmlconstant('_EDIT_CREATED')  . ': ' . isohtmlentities(sql_date_to_human($this->date_created, 'datetime')) 
						 .	 " | " . htmlconstant('_EDIT_MODIFIED') . ': ' . isohtmlentities(sql_date_to_human($this->date_modified, 'datetime')) . ' ' .  htmlconstant('_EDIT_BY') . ' ' . user_html_name($this->user_modified)
						 .   " | <a href=\"log.php?table={$this->table_def_name}&amp;id={$this->id}\" target=\"_blank\">" . htmlconstant('_LOG') . '</a>'
						 ;

				}
			$site->skin->buttonsEnd();
			$site->skin->fixedFooterEnd();

			// finish image buttons
			echo imgbuttonFinish();

			// end form
			echo '</form>';
			

 			// end page
 			$site->pageEnd();
		}

		// return new control index
		return $curr_control_index;
	}
}



