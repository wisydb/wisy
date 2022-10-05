<?php

/******************************************************************************
Implementing single and multiple attribute selections
***************************************************************************//**

Some words to the "renderAfterName" in the old attr_plugin_*:
Currently, we do not support this stuff.  For any detailed information about
an attribute, you can double click the attribute and get directly to the
edit/view page of the attribute while leaving the original record open. This 
should be just fine nowadays.

@author Bjoern Petersen, http://b44t.com

******************************************************************************/



class CONTROL_ATTR_CLASS extends CONTROL_BASE_CLASS
{
	private $mattr;

	public function __construct($name, $row_def, $table_def)
	{
		// init ...
		parent::__construct($name, $row_def, $table_def);
		$this->mattr = ($this->row_def->flags&TABLE_ROW)==TABLE_MATTR? 1 : 0;
		
		$this->attr_table = $this->row_def->addparam->name;
		$this->attr_ftitle = 'id';
		if( isset($this->row_def->prop['ctrl.attr.ftitle']) ) {
			$this->attr_ftitle = $this->row_def->prop['ctrl.attr.ftitle'];
		}
		else for( $r = 0; $r < sizeof((array) $this->row_def->addparam->rows); $r++ ) {
		    $flags = isset( $this->row_def->addparam->rows[$r]->flags ) ? $this->row_def->addparam->rows[$r]->flags : null;
			if( $flags & TABLE_SUMMARY ) {
				$this->attr_ftitle = $this->row_def->addparam->rows[$r]->name;
				break;
			}
		}
	}

	public function get_default_dbval($use_tracked_defaults)
	{
		$arr = array(intval($this->row_def->default_value));
		if( $use_tracked_defaults && ($this->row_def->flags&TABLE_TRACKDEFAULTS) && isset($_SESSION['g_session_track_defaults'][$this->attr_table]) ) {
		    if( isset( $this->mattr ) && $this->mattr &&  is_array($_SESSION['g_session_track_defaults'][$this->attr_table]) ) {
				$arr = $_SESSION['g_session_track_defaults'][$this->attr_table];
			}
			else {
			    $arr = isset($_SESSION['g_session_track_defaults'][$this->attr_table]) ? array(intval($_SESSION['g_session_track_defaults'][$this->attr_table])) : array();
			}
		}
		
		$v = '';
		for( $a = 0; $a < sizeof($arr); $a++ ) { // check all values being int - do not trust incoming data
			$attr = intval($arr[$a]);
			if( $attr > 0 ) {
				$v .= $v==''? '' : ',';
				$v .= $attr;
			}
		}
		return $v; // empty string on empty list! not '0'!
	}
	
	// Cave: $user_input ist not always the same as $_REQUEST[$this->name] - esp. for secondary tables, the caller regards the index!
	public function set_dbval_from_user_input($user_input, &$errors, &$warnings, $addparam)
	{
		// create an array from $user_input; make sure, the array contains only positive integers and no duplicates (this early and check may make a later slow db check unnecessary)
		$in_arr = array();
		$testa = explode(',', $user_input);
		for( $i = 0; $i < sizeof($testa); $i++ ) {
			$testv = intval(trim($testa[$i]));
			if( $testv ) {
				if( !in_array($testv, $in_arr) ) {
					$in_arr[] = $testv;
				}
				else {
					$warnings[] = "Attribut ID {$testv} mehrfach vorhanden; wird nur einfach gespeichert.";
				}
			}
		}
		
		// validate input elements against real existant records
		$out_arr = array();
		if( sizeof($in_arr) ) {
			$dba = $addparam['dba'];
			$testa = array();
			$dba->query("SELECT id, user_access, {$this->attr_ftitle} FROM {$this->attr_table} WHERE id IN (".implode(',', $in_arr).");");
			while( $dba->next_record() ) {
				$user_access = $dba->fs('user_access');
				if( isset( $this->attr_table ) && $this->attr_table == 'user' || isset( $this->attr_table ) && $this->attr_table == 'user_grp' ) {
					$user_access |= 0111; // assume users/user groups to be always referencable
				}
				$testa[ intval($dba->fs('id')) ] = array('user_access'=>$user_access, 'ftitle'=>$dba->fs($this->attr_ftitle));
			}
			
			for( $i = 0; $i < sizeof($in_arr); $i++ ) {
				$testv = $in_arr[$i];
				if( isset($testa[$testv]) ) {
					if( $testa[$testv]['user_access'] & 0111 /*referenzierbar?*/ ) {
						$out_arr[] = $testv;
					}
					else {
						// diese Attribute werden aktuell (12:11 05.02.2014) in den Vorschlaglisten angeboten; ich bin mir
						// nicht sicher, ob dies ein Bug oder ein Feature ist.  Es kann durchaus sinnvoll sein, diese zu "sehen" um eine Liste von Verweisen (nach Doppelklick) zu erhalten.
						$errors[] = '<a href="edit.php?table='.$this->attr_table.'&amp;id=' .$testv. '" target="_blank" rel="noopener noreferrer"><i>'.isohtmlspecialchars($testa[$testv]['ftitle'])."</i> (ID {$testv})</a> ist nicht referenzierbar. Bitte vergeben Sie ein referenzierbares Attribut.";
						$out_arr[] = $testv;
					}
				} 
				else {
					$errors[] = "Attribut ID {$testv} existiert nicht oder nicht mehr.";
					$out_arr[] = $testv;
				}
			}
		}
		
		// check for must-have values
		if( sizeof($out_arr) == 0 ) {
		    $flags = isset( $this->row_def->flags ) ? $this->row_def->flags : null;
			if( $flags&TABLE_MUST ) { $errors[] = htmlconstant('_EDIT_ERREMPTYTEXTFIELD'); }
			if( $flags&TABLE_RECOMMENTED ) { $warnings[] = htmlconstant('_EDIT_WARNEMPTYTEXTFIELD'); }
		}
		
		// track defaults
		if( sizeof($errors) == 0 && sizeof($warnings) == 0 ) {
			$_SESSION['g_session_track_defaults'][$this->row_def->addparam->name] = $out_arr; 
		}
		
		// done.
		$this->dbval = implode(',', $out_arr);
	}	
	


	/**************************************************************************
	render ...
	**************************************************************************/
	
	// create the user input controls - the data from here is normally forwarded to set_dbval_from_user_input() after submit
	public function render_html($addparam)
	{
		$dba = $addparam['dba'];

		$titles = array();
		$actypes = array();
		if( isset( $this->dbval ) && $this->dbval != '' )
		{
			$actypefield  = '';
			if( isset( $this->attr_table ) && $this->attr_table == 'stichwoerter' ) { // very special handling for this table, we make this more generic some time
				$actypefield = ', eigenschaften AS actype';
			}
			
			$sql = "SELECT id, $this->attr_ftitle $actypefield FROM $this->attr_table WHERE id IN($this->dbval);";
			$dba->query($sql);
			while( $dba->next_record() ) {
				$id = $dba->fs('id');
				$titles [$id] = $dba->fs($this->attr_ftitle);
				if( $dba->fs('actype') ) {
					$actypes[$id] = $dba->fs('actype');
				}
			}
			
		}
		
		$html = '';
		
		$class = 'e_attr';
		if( isset( $this->row_def->prop['ctrl.class'] ) && $this->row_def->prop['ctrl.class'] ) $class .= ' ' . $this->row_def->prop['ctrl.class'];
		$html .= '<span class="' . $class . '" data-table="'.$this->row_def->addparam->name.'" data-mattr="'.$this->mattr.'">';
		
		    $name = isset($this->name) ? $this->name : '';
		    $newEntryForm = false;
		    
		    if( $this->dbval == 1 && !isset($id) ) {// new entry form
		        $this->dbval = 0;
		        $newEntryForm = true;
		    }
		        
		    $html .= '<input name="' . $name . '" type="hidden" value="' . ( $this->dbval > 0 ? isohtmlspecialchars($this->dbval) : '' ) . '" />';
			
			$ids = $this->dbval==''? array() : explode(',', $this->dbval);
			for( $i = 0; $i < sizeof($ids); $i++ ) 
			{
				$actype_class = '';
				if( isset( $actypes[$ids[$i]] ) && $actypes[$ids[$i]] ) {
					$actype_class = ' e_attractype'.$actypes[$ids[$i]];
				}
				
				if( $newEntryForm ) // new entry form
				    ;
				else {
				$html .=	'<span class="e_attritem" data-attrid="'.$ids[$i].'"><span class="e_attrinner'.$actype_class.'">';
			     
				
				    $html .=	( isset( $titles[$ids[$i]]) && $titles[$ids[$i]] ? isohtmlspecialchars($titles[$ids[$i]]) : $ids[$i] ); 
				    $html .=	'<span class="e_attrdel">&nbsp;&times;&nbsp;</span>';
				
				
			    $html .=	'</span></span>'; // should be same as [*] in edit.js
				}
			}
			
			
			$html .= '<input name="x_add_' . $this->name . '" type="text" value="" size="10" data-acdata="'. $this->table_def->name . '.' . $this->row_def->name . '" />';
		
		$html .= '</span>';
		
		// add references
		///////////////////////////////////////////////////////////////////////

		$this->load_attrref($addparam);
		if( sizeof($this->attr_references) )
		{
			//$html .= $this->row_def->prop['layout.join']? ' ' : '<br />';
			$html .= ' <span class="e_attrref">';
			
			$title = isset($this->row_def->prop['ref.name']) ? $this->row_def->prop['ref.name'] : ''; 
			if( $title == '' ) $title = '_REF';
			$html .= htmlconstant($title) . ': ';
						for( $a = 0; $a < sizeof($this->attr_references); $a++ ) {
							$html .=  $a? ', ' : '';
							$html .=  '<a href="edit.php?table=' . $this->attr_table . '&id=' . $this->attr_references[$a][0] . '" target="_blank" rel="noopener noreferrer">';
							$html .=  isohtmlentities( strval( $this->attr_references[$a][1] ) );
							$html .=  '</a>';
						}
			$html .= '</span>';
		}
		
		
		// done
		///////////////////////////////////////////////////////////////////////
		return $html;
	}
	
	
	
	
	private function load_attrref($addparam)
	{
	    if( !isset( $this->attr_references )  || !is_array($this->attr_references) )
		{
			$this->attr_references = array();
			$tableDefName = isset( $this->table_def->name ) ? $this->table_def->name : null;
			$attrTable = isset( $this->attr_table ) ? $this->attr_table : null;
			$flags = isset( $this->row_def->flags ) ? $this->row_def->flags : null;
			
			if( $tableDefName == $attrTable 
			 && isset( $addparam['id'] ) && $addparam['id']
			 && $flags&TABLE_SHOWREF )
			{
			    $dba = new DB_Admin;
			    $dba->query("SELECT primary_id FROM {$this->table_def->name}_{$this->row_def->name} WHERE attr_id=".intval($addparam['id'])." ORDER BY structure_pos");
			    while( $dba->next_record() ) {
					$temp = $this->table_def->get_summary($dba->f('primary_id'),  '/');
					$this->attr_references[] = array($dba->f('primary_id'), $temp);
				}
			}
		}
	}
	
	// overwriting this function, we want the control to be expanded if there are references
	// this is close to is_default(), but not always exactly the same.
	public function is_default_display($addparam)
	{
		$is_default = $this->is_default();
		if( $is_default ) {
			$this->load_attrref($addparam);
			if( sizeof($this->attr_references) ) {
				$is_default = false;
			}
		}
		return $is_default;
	}
};