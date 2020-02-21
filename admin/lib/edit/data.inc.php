<?php

/*=============================================================================
Edit or View a Record
===============================================================================

EDIT_DATA_CLASS is used to create a flat structure from nested and seocndary
tables; this flat structure can be easily modified by a formular/by JavaScript
(done in EDIT_RENDERER_CLASS) and can then be returned to EDIT_DATA_CLASS which
recreates the complicated structure in the database.

For this purpose, EDIT_DATA_CLASS can be used as follows:

$ob = new EDIT_DATA_CLASS;
$ob->load_from_db();
echo '<form>';
for( $ob->controls ... ) {
	echo '<input ...>'
}
...

TODO: weitere validierungen

===============================================================================
Handcrafted by Bjoern Petersen, http://b44t.com
=============================================================================*/


class ROW_DUMMY_CLASS
{
	public $flags;
	public $name;
	public $descr;
	public $prop;
	public $fine_title;
	
	public function __construct($descr, $prop)
	{
		if( !is_array($prop) ) die('bad parameter for ROW_DUMMY_CLASS constructor.');
		$this->flags = 0; // this indicates this type of row, Row_Def_Class objects have TABLE_* flags here
		$this->name  = '';
		$this->descr = $descr; // may be empty
		$this->prop  = $prop;
		$this->fine_title = '';
	}
};



class EDIT_DATA_CLASS
{
	// common, valid after construction
	public	$db_name;
	public	$table_name;
	public	$table_def;
	public	$id;				// if the id is -1, it may change after calling save_*()
	public	$do_access_check;
	public	$only_secondary;
	
	// data, valid after a call to load_*()()
	public	$date_created;
	public	$date_modified;
	public	$user_modified;
	public	$controls;			// array of CONTROL_BASE_CLASS or derived objects
	public	$control_user_created;
	public	$control_user_grp;
	public	$control_user_access;
	public	$errors, $warnings;	// array of errors and warnings
	public	$can_contain_blobs;	// true, if the data potentially contains blobs

	// misc.
	private $db1;
	private $db2;
	public  $dba;				// $this->dba should be treated as readonly
	private $can_save;
	private $is_new;
	private $today;
	
	public function __construct($db_name, $table_name, $id)
	{
		$this->db_name 		= $db_name;
		$this->table_name	= $table_name;
		$this->id			= $id;
		$this->is_new		= $id==-1? true : false;

		$this->do_access_check 	= $this->db_name == ''? 1 : 0;
		$this->can_save		   	= $this->db_name == ''? 1 : 0;
		
		$this->today			= strftime("%Y-%m-%d %H:%M:%S"); // saving the time makes sure, creation and modification date are the same for new records 
		
		$this->table_def = Table_Find_Def($this->table_name, $this->do_access_check);
		if( !$this->table_def ) $this->halt('bad table');

		if( $this->id <= 0 && $this->id != -1 ) $this->halt('bad id');
		if( $this->do_access_check && !acl_get_access("$this->table_name.COMMON", $this->id) ) $this->halt('no access');
			
		$this->only_secondary = $this->table_def->is_only_secondary($this->only_secondary_primary_table_name, $this->only_secondary_primary_table_field);
	}
	
	/*
	public function __clone()
	{
		// object cloning is used for mainly for test_save() 
		// Klon fonktioniert so: Alle Eigenschaften, die Referenzen auf andere Variablen sind, werden Referenzen bleiben. 
		// für controls[] ist uns das nicht genug, da z.B. save() die Daten ändert, nach einem test_save() sollten sie aber unverändert sein. 
		for( $i = 0; $i < sizeof($this->controls); $i++ ) {
			$this->controls[$i] = clone $this->controls[$i]; 
		}
	}
	*/
	
	private function ids_array_equal($a1, $a2) 
	{
	    if( sizeof((array) $a1)!=sizeof((array) $a2) ) {
			return false;
		}
		for( $i = 0; $i < sizeof((array) $a1); $i++ ) {
			if( $a1[$i] != $a2[$i] ) {
				return false;
			}
		}
		return true;
	}
	
	private function read_simple_list_(&$db, $sql) // ready all elements named 'ret' into a simple array
	{
		$ret = array();
		$db->query($sql);
		while( $db->next_record() ) {
			$ret[] = $db->fs('ret');
		}
		return $ret;
	}
	
	private function halt($msg)
	{
		$GLOBALS['site']->abort(__FILE__, __LINE__, $msg);
		exit();
	}
	
	private function cmp_prepare_str($str)
	{
		return str_replace("\r", "", $str); // ignore \r on string comparison
	}
	
	private function cmp($o)
	{
		if( $this->control_user_created->dbval	!= $o->control_user_created->dbval
         ||	$this->control_user_grp->dbval		!= $o->control_user_grp->dbval
		 || $this->control_user_access->dbval	!= $o->control_user_access->dbval
		 || sizeof((array) $this->controls) != sizeof((array) $o->controls) ) {
			return 1; // objects are different
		}
		
		for( $i = 0; $i < sizeof((array) $this->controls); $i++ ) {
			if( !$this->controls[$i]->is_readonly() )  {
				if( $this->cmp_prepare_str($this->controls[$i]->dbval) != $this->cmp_prepare_str($o->controls[$i]->dbval) ) {
					return 1; // objects are different
				}
			}
		}
		
		return 0; // objects are equal
	}
	
	function get_title()
	{
		// return $this->table_def->descr . ' - ' . htmlconstant($this->id==-1? '_NEW' : '_EDIT'); // old approach
		if( $this->id==-1 ) {
			return $this->table_def->descr . ' - ' . htmlconstant('_NEW');
		}
		else if( $this->fine_title != '' ) {
			return isohtmlspecialchars($this->fine_title) . ' - ' . htmlconstant('_EDIT');
		}
		else {
			return $this->table_def->descr . ' - ' . htmlconstant('_EDIT');
		}
	}
	
	function connect_to_db_()
	{
		if( !$this->db1 || !$this->db2 || !$this->dba )
		{
			$factory = new G_DBCONFACTORY_CLASS;
			if( !$this->db1 ) { $this->db1 = $factory->create_instance($this->db_name); } // check each object as it may be precreated for verify()
			if( !$this->db2 ) { $this->db2 = $factory->create_instance($this->db_name); }
			if( !$this->dba ) { $this->dba = $factory->create_instance($this->db_name); } // $this->dba should be treated as readonly
			if( !$this->db1 || !$this->db2 || !$this->dba ) 
			{
				$this->halt($factory->error_str);
			}
		}
	}
	
	private function connect_to_trigger_n_logger_()
	{
		$this->trigger_script = '';
		$this->trigger_param = array();
		if( $this->table_def->trigger_script && get_class($this->db1) != 'G_DUMMYSQL_CLASS' && $this->db_name == '' ) {
			$this->trigger_script = $this->table_def->trigger_script;
		}
		
		$this->logwriter = false;
		if( get_class($this->db1) != 'G_DUMMYSQL_CLASS' && $this->db_name == '' ) {
			$this->logwriter = new LOG_WRITER_CLASS;
		}
	}
	
	private function call_trigger_()
	{

	    if( $this->trigger_script && is_array($this->trigger_param) && sizeof((array) $this->trigger_param) )
		{
			call_plugin($this->trigger_script, $this->trigger_param);
			if( $this->trigger_param['returnmsg'] )
			{
				$GLOBALS['site']->msgAdd($this->trigger_param['returnmsg'], 'i');
			}
		}
	}
	
	
	
	/**************************************************************************
	 * load bank data
	 **************************************************************************/
	 
	private function CREATE_CONTROL_($name, $row_def, $table_def = 0)
	{
		if( $row_def->prop['ctrl.phpclass'] ) 
		{
			$class_name = $row_def->prop['ctrl.phpclass'];
		}
		else switch( $row_def->flags&TABLE_ROW )
		{
			case TABLE_TEXT: 						$class_name = 'CONTROL_TEXT_CLASS';		break;
			case TABLE_TEXTAREA:					$class_name = 'CONTROL_TEXTAREA_CLASS';	break;
			case TABLE_BLOB:						$class_name = 'CONTROL_BLOB_CLASS';		break;
			case TABLE_INT:							$class_name = 'CONTROL_INT_CLASS';		break;
			case TABLE_FLAG:						$class_name = 'CONTROL_FLAG_CLASS';		break;
			case TABLE_ENUM:						$class_name = 'CONTROL_ENUM_CLASS';		break;
			case TABLE_BITFIELD:					$class_name = 'CONTROL_BITFIELD_CLASS';	break;
			case TABLE_DATE: case TABLE_DATETIME:	$class_name = 'CONTROL_DATE_CLASS';		break;
			case TABLE_SATTR: case TABLE_MATTR:		$class_name = 'CONTROL_ATTR_CLASS';		break;
			case TABLE_PASSWORD:					$class_name = 'CONTROL_PASSWORD_CLASS';	break;
			default:								$class_name = 'CONTROL_BASE_CLASS';		break; // CONTROL_BASE_CLASS is no real option and will just render an error
		}
		
		return new $class_name($name, $row_def, $table_def);
	}

	private function CREATE_RIGHTS_CONTROLS_($copy_record = false)
	{
		$acl = acl_check_access("{$this->table_def->name}.RIGHTS", $copy_record? -1 : $this->id, ACL_EDIT)? ACL_EDIT : 0;
		$users = Table_Find_Def('user', 0);
		$grps = Table_Find_Def('user_grp', 0);
		$this->control_user_created	= new CONTROL_ATTR_CLASS	('user_created',new Row_Def_Class(TABLE_SATTR,	'user_created',	'_EDIT_GRANTSOWNER',	intval($_SESSION['g_session_userid']),	$users, '', array(), $acl), $this->table_def);
		$this->control_user_grp		= new CONTROL_ATTR_CLASS	('user_grp',	new Row_Def_Class(TABLE_SATTR,	'user_grp',		'_EDIT_GRANTSGROUP',	intval(acl_get_default_grp()),			$grps,	'', array(), $acl), $this->table_def);
		$this->control_user_access	= new CONTROL_RIGHTS_CLASS	('user_access',	new Row_Def_Class(TABLE_INT,	'user_access',	'_RIGHTS',				acl_get_default_access(),				'',		'', array(), $acl), $this->table_def);
	}
	
	private function add_blank_object_to_controls_array_($row, $table_def2, $use_tracked_defaults = true)
	{
		$control = $this->CREATE_CONTROL_("f_{$row->name}_object_start[]", new ROW_DUMMY_CLASS('Neue '.$row->descr, array('__META__'=>'object_start')));
		$control->dbval = -1;
		$this->controls[] = $control;
		
		if(is_array($table_def2->rows)) {
		    for( $r2 = 0; $r2 < sizeof((array) $table_def2->rows); $r2++ )
		    {
		        $row2 = $table_def2->rows[$r2];
		        
		        $control = $this->CREATE_CONTROL_("f_{$row->name}_{$row2->name}[]", $row2, $table_def2);
		        $control->dbval = $control->get_default_dbval($use_tracked_defaults);
		        $this->controls[] = $control;
		        
		        if( ($row2->flags&TABLE_ROW) == TABLE_BLOB )
		            $this->can_contain_blobs = true;
		    }
		}
		
		$this->controls[] = $this->CREATE_CONTROL_("f_{$row->name}_object_end[]", new ROW_DUMMY_CLASS('', array('__META__'=>'object_end')));
	}
	
	private function add_template_to_controls_array_($row, $table_def2, $use_tracked_defaults = true)
	{
		$this->controls[] = $this->CREATE_CONTROL_("f_{$row->name}_template_start", new ROW_DUMMY_CLASS('', array('__META__'=>'template_start')));
		
			$this->add_blank_object_to_controls_array_($row, $table_def2);
			
		$this->controls[] = $this->CREATE_CONTROL_("f_{$row->name}_template_end", new ROW_DUMMY_CLASS('', array('__META__'=>'template_end')));		
	}
	
	public function load_blank($use_tracked_defaults = true)
	{
		$this->date_created		= '0000-00-00 00:00:00';
		$this->date_modified	= '0000-00-00 00:00:00';
		$this->user_modified	= intval($_SESSION['g_session_userid']);
		$this->CREATE_RIGHTS_CONTROLS_();
		$this->control_user_created->dbval	= $this->control_user_created->get_default_dbval($use_tracked_defaults);
		$this->control_user_grp->dbval		= $this->control_user_grp->get_default_dbval($use_tracked_defaults);
		$this->control_user_access->dbval	= $this->control_user_access->get_default_dbval($use_tracked_defaults);

		$this->controls = array();
		if(is_array($this->table_def->rows)) {
		    for( $r = 0; $r < sizeof((array) $this->table_def->rows); $r++ )
		    {
		        $row = $this->table_def->rows[$r];
		        switch( $row->flags&TABLE_ROW )
		        {
		            case TABLE_SECONDARY:
		                // secondary list start
		                $this->controls[] = $this->CREATE_CONTROL_("f_{$row->name}_secondary_start", new ROW_DUMMY_CLASS($row->descr, array('__META__'=>'secondary_start')));
		                $table_def2 = Table_Find_Def($this->table_def->rows[$r]->addparam->name, $this->do_access_check);
		                
		                // add secondary items
		                if( $row->default_value )
		                {
		                    $this->add_blank_object_to_controls_array_($row, $table_def2, $use_tracked_defaults);
		                }
		                
		                // add secondary template (this is a normal object surrounded by template marks)
		                $this->add_template_to_controls_array_($row, $table_def2, $use_tracked_defaults);
		                
		                // secondary list end
		                $this->controls[] = $this->CREATE_CONTROL_("f_{$row->name}_secondary_end", new ROW_DUMMY_CLASS('', array('__META__'=>'secondary_end')));
		                break;
		                
		            default:
		                // primary field
		                $control = $this->CREATE_CONTROL_("f_{$row->name}", $row, $this->table_def);
		                $control->dbval = $control->get_default_dbval($use_tracked_defaults);
		                $this->controls[] = $control;
		                if( ($row->flags&TABLE_ROW) == TABLE_BLOB )
		                    $this->can_contain_blobs = true;
		                    break;
		        }
		    }
		}
		
		return true;
	}

	

	/**************************************************************************
	 * load data from database
	 **************************************************************************/
	 
	private function get_value_from_db_(&$db, &$table_def, $r)
	{
		$row = $table_def->rows[$r];
		
		switch( $row->flags&TABLE_ROW )
		{
			case TABLE_MATTR:
				$v = '';
				$this->dba->query("SELECT attr_id FROM {$table_def->name}_{$row->name} WHERE primary_id=".$db->fs('id')." ORDER BY structure_pos;");
				while( $this->dba->next_record() ) {														// ^^^ for secondary tables, this is NOT equal to $this->id
					$temp = intval($this->dba->fs('attr_id'));
					if( $temp > 0 ) {
						$v .= $v? ',' : '';
						$v .= $temp;
					}
				}
				return $v; // empty string on empty list! not '0'!
			
			case TABLE_SATTR:
				$v = $db->fs($row->name);
				return $v=='0'? '' : $v; // empty string on empty list! not '0'!
			
			default:
				if( $this->fine_title == '' && $row->flags&TABLE_SUMMARY ) {
					$this->fine_title = $db->fs($row->name);
				}
				return $db->fs($row->name);
		}
	}
	
	public function load_from_db($copy_record = false)
	{
		$this->connect_to_db_();

		// only secondary? --> link to primary record
		if( $this->only_secondary ) {
			$this->db1->query("SELECT primary_id FROM $this->only_secondary_primary_table_name"."_$this->only_secondary_primary_table_field WHERE secondary_id=$this->id");
			if( $this->db1->next_record() ) {
				$this->warnings[] = htmlconstant('_EDIT_ONLYSECONDARYDATA', '<a href="edit.php?table=' .$this->only_secondary_primary_table_name. '&id=' .$this->db1->fs('primary_id'). '">', '</a>');
			}
		}
			
		$this->db1->query("SELECT * FROM {$this->table_def->name} WHERE id={$this->id};");
		if( !$this->db1->next_record() ) { $this->halt('record does not exist'); } /* normally, we do not go here, the 'no access' error comes first */

		$this->date_created		= $this->db1->fs('date_created');
		$this->date_modified	= $this->db1->fs('date_modified');
		$this->user_modified	= $this->db1->fs('user_modified');
		$this->CREATE_RIGHTS_CONTROLS_($copy_record);
		$this->control_user_created->dbval	= $this->db1->fs('user_created')==0? '' : $this->db1->fs('user_created');
		$this->control_user_grp->dbval		= $this->db1->fs('user_grp')==0? '' : $this->db1->fs('user_grp');
		$this->control_user_access->dbval	= $this->db1->fs('user_access');
		
		$this->controls = array();
		if(is_array($this->table_def->rows)) {
		    for( $r = 0; $r < sizeof((array) $this->table_def->rows); $r++ )
		    {
		        $row = $this->table_def->rows[$r];
		        switch( $row->flags&TABLE_ROW )
		        {
		            case TABLE_SECONDARY:
		                // secondary list start
		                $this->controls[] = $this->CREATE_CONTROL_("f_{$row->name}_secondary_start", new ROW_DUMMY_CLASS($row->descr, array('__META__'=>'secondary_start')));
		                $table_def2 = Table_Find_Def($this->table_def->rows[$r]->addparam->name, $this->do_access_check);
		                
		                // add secondary items
		                $s_id = array();
		                $this->db2->query("SELECT secondary_id FROM {$this->table_def->name}_{$row->name} WHERE primary_id={$this->id} ORDER BY structure_pos;");
		                while( $this->db2->next_record() ) { $s_id[] = $this->db2->fs('secondary_id'); }
		                
		                if(is_array($s_id)) {
		                    for( $s = 0; $s < sizeof((array) $s_id); $s++ )
		                    {
		                        $this->db2->query("SELECT * FROM {$table_def2->name} WHERE id=".$s_id[$s]);
		                        if( $this->db2->next_record() )
		                        {
		                            $control = $this->CREATE_CONTROL_("f_{$row->name}_object_start[]", new ROW_DUMMY_CLASS($row->descr.' '.($s+1), array('__META__'=>'object_start')));
		                            $control->dbval = $copy_record? -1 : $s_id[$s];
		                            $this->controls[] = $control;
		                            
		                            for( $r2 = 0; $r2 < sizeof((array) $table_def2->rows); $r2++ )
		                            {
		                                $row2 = $table_def2->rows[$r2];
		                                switch( $row2->flags&TABLE_ROW ) {
		                                    case TABLE_SECONDARY: die('nested secondary tables are not allowed!'); break;
		                                    default:
		                                        $control = $this->CREATE_CONTROL_("f_{$row->name}_{$row2->name}[]", $row2, $table_def2);
		                                        $control->dbval = $this->get_value_from_db_($this->db2, $table_def2, $r2);
		                                        $this->controls[] = $control;
		                                        break;
		                                }
		                            }
		                            $this->controls[] = $this->CREATE_CONTROL_("f_{$row->name}_object_end[]", new ROW_DUMMY_CLASS('', array('__META__'=>'object_end')));
		                        }
		                        else
		                        {
		                            $this->errors[] = "{$row->descr} ID {$s_id[$s]} existiert nicht oder nicht mehr.";
		                        }
		                    }
		                }
		                
		                // add secondary template (this is a normal object surrounded by template marks)
		                $this->add_template_to_controls_array_($row, $table_def2);
		                
		                // secondary list end
		                $this->controls[] = $this->CREATE_CONTROL_("f_{$row->name}_secondary_end", new ROW_DUMMY_CLASS('', array('__META__'=>'secondary_end')));
		                break;
		                
		            default:
		                // primary field
		                $control = $this->CREATE_CONTROL_("f_{$row->name}", $row, $this->table_def);
		                $control->dbval = $this->get_value_from_db_($this->db1, $this->table_def, $r);
		                $this->controls[] = $control;
		                if( ($row->flags&TABLE_ROW) == TABLE_BLOB )
		                    $this->can_contain_blobs = true;
		                    break;
		        }
		    }
		}
		
		if( $copy_record ) {
			$this->id  = -1;
			$this->control_user_created->dbval	= $this->control_user_created->get_default_dbval(false);
		}
		return true;
	}

	
	
	/**************************************************************************
	 * load data from post
	 **************************************************************************/
	
	private function add_errors_n_warnings_($field, $field_errors, $field_warnings)
	{
	    for( $i = 0; $i < sizeof((array) $field_errors); $i++ ) {
	        $this->errors[] = htmlconstant(trim($field)) . ': ' . $field_errors[$i];
	    }
	    for( $i = 0; $i < sizeof((array) $field_warnings); $i++ ) {
	        $this->warnings[] = htmlconstant(trim($field)) . ': ' . $field_warnings[$i];
	    }
	}
	
	public function load_from_post()
	{
		$this->errors = array();
		$this->warnings = array();
		$this->connect_to_db_();

		$this->date_created		= $_REQUEST['date_created'];
		$this->date_modified	= $_REQUEST['date_modified'];
		$this->user_modified	= $_REQUEST['user_modified'];
		$field_errors = array(); $field_warnings = array(); $addparam = array('dba'=>$this->dba, 'id_base'=>$this->id, 'id'=>$this->id); 
			$this->CREATE_RIGHTS_CONTROLS_();
			$this->control_user_created->set_dbval_from_user_input	($_REQUEST['user_created'],	$field_errors, $field_warnings, $addparam);
			$this->control_user_grp->set_dbval_from_user_input		($_REQUEST['user_grp'],		$field_errors, $field_warnings, $addparam);
			$this->control_user_access->set_dbval_from_user_input	($_REQUEST['user_access'],	$field_errors, $field_warnings, $addparam);
		$this->add_errors_n_warnings_('_RIGHTS', $field_errors, $field_warnings);

		$this->controls = array();
		for( $r = 0; $r < sizeof((array) $this->table_def->rows); $r++ )
		{
			$row = $this->table_def->rows[$r];
			switch( $row->flags&TABLE_ROW )
			{
				case TABLE_SECONDARY:
					// secondary list start
					$this->controls[] = $this->CREATE_CONTROL_("f_{$row->name}_secondary_start", new ROW_DUMMY_CLASS($row->descr, array('__META__'=>'secondary_start')));
						$table_def2 = Table_Find_Def($this->table_def->rows[$r]->addparam->name, $this->do_access_check);
					
						// add secondary items
						$used_s_ids = array();
						for( $s = 0; $s < sizeof((array) $_REQUEST["f_{$row->name}_object_start"]) - 1 /*last is template, skip*/; $s++ )
						{
							$s_id = $_REQUEST["f_{$row->name}_object_start"][$s]; 
							if( $s_id!=-1 && $used_s_ids[$s_id] ) { die('duplicated secondary id in post. please verify the JavaScript part.'); } $used_s_ids[$s_id] = true;
							
							$control = $this->CREATE_CONTROL_("f_{$row->name}_object_start[]", new ROW_DUMMY_CLASS($row->descr.' '.($s+1), array('__META__'=>'object_start')));
							$control->dbval = $s_id;
							$this->controls[] = $control;
							    for( $r2 = 0; $r2 < sizeof((array) $table_def2->rows); $r2++ ) 
								{
									$row2 = $table_def2->rows[$r2];
									switch( $row2->flags&TABLE_ROW ) {
										case TABLE_SECONDARY: die('nested secondary tables are not allowed!'); break;
										default: 
											$control = $this->CREATE_CONTROL_("f_{$row->name}_{$row2->name}[]", $row2, $table_def2); 
											
											$field_errors = array(); $field_warnings = array();
												$control->x_secondary_index = $s;
													$control->set_dbval_from_user_input($_REQUEST["f_{$row->name}_{$row2->name}"][$s], 
														$field_errors, $field_warnings, 
														array('dba'=>$this->dba, 'id_base'=>$this->id, 'id'=>$s_id));
												unset($control->x_secondary_index);
											$this->add_errors_n_warnings_($row2->descr, $field_errors, $field_warnings);
											
											$this->controls[] = $control;
											break;
									}
								}
							$this->controls[] = $this->CREATE_CONTROL_("f_{$row->name}_object_end[]", new ROW_DUMMY_CLASS('', array('__META__'=>'object_end')));
						}
						
						// add secondary template (this is a normal object surrounded by template marks)
						$this->add_template_to_controls_array_($row, $table_def2);
						
					// secondary list end
					$this->controls[] = $this->CREATE_CONTROL_("f_{$row->name}_secondary_end", new ROW_DUMMY_CLASS('', array('__META__'=>'secondary_end')));
					break;
				
				default:	
					// primary field
					$control = $this->CREATE_CONTROL_("f_{$row->name}", $row, $this->table_def);
					
					$field_errors = array(); $field_warnings = array();
						$control->set_dbval_from_user_input($_REQUEST["f_{$row->name}"], $field_errors, $field_warnings, 
							array('dba'=>$this->dba, 'id_base'=>$this->id, 'id'=>$this->id));
					$this->add_errors_n_warnings_($row->descr, $field_errors, $field_warnings);
					
					if( $this->fine_title == '' && $row->flags&TABLE_SUMMARY && ($row->flags&TABLE_ROW)!=TABLE_SATTR && ($row->flags&TABLE_ROW)!=TABLE_MATTR ) {
						$this->fine_title = $control->dbval;
					}
					
					$this->controls[] = $control;
					if( ($row->flags&TABLE_ROW) == TABLE_BLOB ) {
						$this->can_contain_blobs = true;
					}
					break;
			}
		}
		
		return sizeof($this->errors)? false : true;
	}



	/**************************************************************************
	 * Save data to database
	 **************************************************************************/
	
	private function create_record_($table_def)
	{
		$def_grp	= intval(acl_get_default_grp());	// we create the record using the default values for the user;
		$def_access = acl_get_default_access();			// they may be changed later

		$sql = "INSERT INTO $table_def->name (date_created, date_modified, user_created, user_modified, user_grp, user_access) 
											  VALUES (".$this->db1->quote($this->date_created).", ".$this->db1->quote($this->date_modified).", $this->user_modified, $this->user_modified, $def_grp, $def_access)";
		$this->db1->query($sql);	
		return $this->db1->insert_id();
	}
	
	private function save_record_($table_def, $id, $field_index, $secondary_field_name)
	{
		$sql = '';
		
		$field_prefix  = 'f_'.$secondary_field_name.($secondary_field_name? '_' : '');
		$field_postfix = $secondary_field_name? '[]' : '';
		
		for( $r = 0; $r < sizeof((array) $table_def->rows); $r++ )
		{
			$row = $table_def->rows[$r];
			
			//if( $row->acl&ACL_EDIT ) // checked below using is_readonly()
			{
				// get control object for current value
				$field = $this->controls[$field_index];
				if( ($field_prefix.$row->name.$field_postfix) != $field->name && ($row->flags&TABLE_ROW)!=TABLE_SECONDARY /*secondary will fix this issue below*/ ) 
												{ die("field/rows out of sync: $field_prefix$row->name$field_postfix != $field->name "); }
				
				// validate / prepare for saving
				switch( $row->flags&TABLE_ROW )
				{
					case TABLE_MATTR:
						if( !$field->is_readonly() ) {
							$old_ids = $this->read_simple_list_($this->db1, "SELECT attr_id AS ret FROM {$table_def->name}_{$row->name} WHERE primary_id=$id ORDER BY structure_pos;");
							$new_ids = ($field->dbval==''||$field->dbval=='0')? array() : explode(',', $field->dbval);
							if( !$this->ids_array_equal($old_ids, $new_ids) ) {
							
								$this->db1->query("DELETE FROM {$table_def->name}_{$row->name} WHERE primary_id=$id;");
								if( sizeof((array) $new_ids) ) {
								    $sqlattr = "INSERT INTO {$table_def->name}_{$row->name} (primary_id,attr_id,structure_pos) VALUES ";
								    for( $i = 0; $i < sizeof((array) $new_ids); $i++ ) {
								        $sqlattr .= $i? ', ' : '';
								        $sqlattr .= "($id,".intval($new_ids[$i]).",$i)";
								    }
								    $this->db1->query($sqlattr);
								}
							}
						}
						break;
					
					case TABLE_SECONDARY:
						// forward to secondary_end, regard the increment following at [*]
						if( $field->row_def->prop['__META__'] != 'secondary_start' ) { die('secondary out of sync.'); }
						for( ; $field_index < sizeof((array) $this->controls); $field_index++ ) {
							if( $this->controls[$field_index]->row_def->prop['__META__'] == 'secondary_end' )
								break;
						}
						break;
					
					default:
						// if we go here, most stuff is already validated by set_dbval_*() - if any set_dbval_*() returns errors,
						// the record ist **not** saved.
						if( !$field->is_readonly() ) {
							$sql .= $row->name . "=" . $this->db1->quote($field->dbval) . ", ";
							if( ($row->flags&TABLE_ROW)==TABLE_TEXT && $this->db1->column_exists($table_def->name, $row->name.'_sorted') ) {
								require_once('eql.inc.php');
								$sql .= $row->name . "_sorted='" . g_eql_normalize_natsort($field->dbval) . "', ";
							}
						}
						break;
				}
			}
			
			// next field [*]
			$field_index++;		
		}
		
		if( $secondary_field_name == '' && acl_check_access("{$table_def->name}.RIGHTS", $this->id, ACL_EDIT) )
		{
			$sql .= sprintf("user_created=%d, user_grp=%d, user_access=%d, ", intval($this->control_user_created->dbval), intval($this->control_user_grp->dbval), intval($this->control_user_access->dbval));
		}
		
		$this->date_modified = $this->today;
		$this->user_modified = intval($_SESSION['g_session_userid']);
		
		$sql = "UPDATE $table_def->name SET " . $sql . " date_modified=".$this->db1->quote($this->date_modified).", user_modified=$this->user_modified WHERE id=" . intval($id);
		
		$this->db1->query($sql);
	}
	
	public function save_to_db()
	{
		$this->errors = array();
		$this->warnings = array();
		$this->already_up_to_date = false;
		
		// rough check if saving is allowed in common
		if( !$this->can_save 
		 || ( $this->is_new && !($this->table_def->acl&ACL_NEW) ) 
		 || (!$this->is_new && !acl_check_access("$this->table_name.COMMON", $this->id, ACL_EDIT)) )		
		{
			$this->errors[] = "Fehlende Rechte für $this->table_name.$this->id in Datenbank {$this->db_name}.";
			return false;
		}

		$this->connect_to_db_();
		$this->connect_to_trigger_n_logger_();
		
		// prepare primary record
		if( $this->id == -1 )
		{
			$this->date_created  = $this->today;
			$this->date_modified = $this->today;
			$this->user_modified = intval($_SESSION['g_session_userid']);
			$test = $this->create_record_($this->table_def);
			if( $test == 0 ) {
				return false;
			}
			$this->id = $test;
			
			// triggering/logging
			$this->trigger_param = array('action'=>'afterinsert', 'id'=>$this->id);
			$logaction = 'create';
		}
		else if( get_class($this->db1) != 'G_DUMMYSQL_CLASS' ) // comparison and destroying [!] only on real save, not on validate
		{
			$cmp_ob = new EDIT_DATA_CLASS($this->db_name, $this->table_name, $this->id);
			if( !$cmp_ob->load_from_db() ) {
				return false; // may happen if the record was deleted in another window
			}
			
			if( sizeof((array) $cmp_ob->errors)==0 && $this->cmp($cmp_ob) == 0 ) {
				$this->already_up_to_date = true;
				return true; 
			}

			// logging
			$this->trigger_param = array('action'=>'afterupdate', 'id'=>$this->id);
			$logaction = 'edit';
			if( $this->logwriter ) {
				$this->logwriter->addDataFromTable($this->table_def->name, $this->id, 'preparediff');
			}
		}
		
		// save primary record - no abort here
		$this->save_record_($this->table_def, $this->id, 0, '');
		
		// save secondary records
		for( $r = 0; $r < sizeof((array) $this->table_def->rows); $r++ )
		{
			$row = $this->table_def->rows[$r];
			if( ($row->flags&TABLE_ROW) == TABLE_SECONDARY )
			{
				// load old secondary records
				$old_ids = $this->read_simple_list_($this->db1, "SELECT secondary_id AS ret FROM {$this->table_def->name}_{$row->name} WHERE primary_id=$this->id ORDER BY structure_pos;");
			
				// update/create the secondary records
				$table_def2 = Table_Find_Def($this->table_def->rows[$r]->addparam->name, $this->do_access_check);
				$new_ids = array();
				for( $field_index = 0; $field_index < sizeof((array) $this->controls); $field_index++ )
				{
					$field = $this->controls[$field_index];
					if( $field->name == "f_{$row->name}_object_start[]" )
					{
						if( $field->dbval == -1 ) {
							$field->dbval = $this->create_record_($table_def2);
						}
						
						$new_ids[] = $field->dbval;
						$this->save_record_($table_def2, $field->dbval, $field_index+1, $row->name);
					}
					else if( $field->name == "f_{$row->name}_template_start" )
					{
						break; // last is template, this is not saved!
					}
				}
				
				// update the secondary relations table, if needed
				if( !$this->ids_array_equal($old_ids, $new_ids) )
				{
					$this->db1->query("DELETE FROM {$this->table_def->name}_{$row->name} WHERE primary_id=$this->id");
					if( sizeof((array) $new_ids) ) {
						$sql = "INSERT INTO {$this->table_def->name}_{$row->name} (primary_id,secondary_id,structure_pos) VALUES ";
						for( $i = 0; $i < sizeof((array) $new_ids); $i++ ) {
							$sql .= $i? ', ' : '';
							$sql .= "($this->id,{$new_ids[$i]},$i)";
						}
						$this->db1->query($sql);
					}
					//$this->warnings[] = 'secondary relations UPDATED.';
				}
				else
				{
					//$this->warnings[] = 'secondary relations NOT updated.';
				}
			}

		}
		
		// triggering/logging (go log all modifications, logging should be done after the triggers are called)
		$this->call_trigger_();
		$this->returnreload = $this->trigger_param['returnreload']? true : false;
		
		if( $this->logwriter && $logaction ) {
			if( $logaction == 'edit' ) {
				$this->logwriter->addDataFromTable($this->table_name, $this->id, 'creatediff');
			}
			$this->logwriter->log($this->table_name, $this->id, $_SESSION['g_session_userid'], $logaction);
		}
		
		// done
		return true;
	}
	
	// test_save() checks if the records is valid, but does not save anything to disk
	/*
	public function test_save()
	{
		$test = clone $this;
		$test->db1 = new G_DUMMYSQL_CLASS;
		$test->db2 = new G_DUMMYSQL_CLASS;
		
		$ret = $test->save_to_db();
		
		$this->errors = $test->errors;
		$this->warnings = $test->warnings;
		return sizeof($this->errors)? false : $ret; // save_to_db() returns true if the record was saved - even if it contains errors, eg. non-unique values
	}
	*/
	
	
	
	/**************************************************************************
	 * Delete data from database
	 **************************************************************************/
	 
	public function references2string($references)
	{
		$html = '';
	
		for( $i = 0; $i < sizeof((array) $references); $i++ )
		{
			$cnt = $references[$i][4];
			if( $cnt ) {
				if( $cnt==1 ) {
					$href = "edit.php?table={$references[$i][0]}&amp;id={$references[$i][5]}";
				}
				else {
					require_once('eql.inc.php');
					$field = g_eql_normalize_func_name($references[$i][2], 0);
					$href = "index.php?table={$references[$i][0]}&amp;f0=$field&amp;v0=$this->id&amp;searchreset=2&amp;searchoffset=0&amp;orderby=date_modified+DESC";
				}
				
				$html .= $html? ', ' : '';
				$html .= '<a href="'.$href.'" target="_blank">';
				$html .= htmlentities($references[$i][1]) . '.' . htmlentities($references[$i][3]) . ': ' . $cnt;
				$html .= '</a>';
			}
		}
	
		return $html;
	}
	
	public function delete_from_db()
	{
		if( $this->table_def->num_references($this->id, $references) ) {
			$this->errors[] = htmlconstant('_EDIT_ERRREFERENCED') . "<br />" . 'Referenzen: ' . $this->references2string($references) . '';
			return false;
		}
		
		if( !$this->can_save || ($this->do_access_check && !acl_check_access("$this->table_name.COMMON", $this->id, ACL_DELETE)) ) {
			$this->errors[] = htmlconstant('_ERRACCESS');
			return false;
		}

		$this->connect_to_db_();
		$this->connect_to_trigger_n_logger_();

		// triggering/logging
		$this->trigger_param  = array('action'=>'afterdelete', 'id'=>$this->id);
		$this->call_trigger_();
		if( $this->logwriter ) {
			$this->logwriter->addDataFromTable($this->table_name, $this->id, 'dump');
			$this->logwriter->log($this->table_name, $this->id, $_SESSION['g_session_userid'], 'delete');
		}
		
		// delete
		$this->table_def->destroy_record_n_dependencies($this->db1, $this->id);
		
		return true;
	}
	
};
