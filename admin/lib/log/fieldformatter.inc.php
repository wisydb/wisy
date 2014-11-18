<?php

class LOG_FIELDFORMATTER_CLASS
{
	private $currDay;
	private $currLineNumber;
	private $times;
	
	public	$deletedSecondary;
	
	function __construct($currDay, $currLineNumber)
	{
		$this->currDay 			= $currDay;
		$this->currLineNumber	= $currLineNumber;
		$this->times			= array();
		$this->deletedSecondary	= array();
	}

	function combineDoubleFields(&$record)
	{
		$record_size = sizeof($record);

		// remove the secondary ID from the field name (convert <table>.<id>.<field> to <table>.<field>)
		$record_field_names = array();
		for( $i = 5; $i < $record_size; $i += 3 )
		{
			$temp = explode('.',$record[$i]);
			$record_field_names[$i] = $temp[0] . '.' . $temp[2];
		}
		
		// search for double fields
		for( $i = 5; $i < $record_size; $i += 3 )
		{
			for( $j = $i+3; $j < $record_size; $j += 3 )
			{
				if( $record_field_names[$i]   == $record_field_names[$j]
				 && $record[$i+1] == $record[$j+1]
				 && $record[$i+2] == $record[$j+2] )
				{
					if( isset($this->times[$i]) )
						$this->times[$i] ++;
					else
						$this->times[$i] = 2;
					
					$record[$j] = '';
				}
			}
		}
	}	
	
	private function getIdListInfo($oldValues, $newValues, &$ret_deleted, &$ret_created, &$ret_orderModified) // orderModified is only correct if nothing is deleted or created
	{
		$ret_deleted		= array();
		$ret_created		= array();
		$ret_orderModified	= false;
		
		$oldValues = $oldValues==''? array() : explode(',', $oldValues);
		$newValues = $newValues==''? array() : explode(',', $newValues);
		
		for( $i = 0; $i < sizeof($oldValues); $i++ ) {	
			if( !is_numeric($oldValues[$i]) ) return false; // error
			if( !in_array($oldValues[$i], $newValues) ) { $ret_deleted[] = $oldValues[$i]; }
			if( $oldValues[$i] != $newValues[$i] ) { $ret_orderModified = true; }
		}
		for( $i = 0; $i < sizeof($newValues); $i++ ) {
			if( !is_numeric($newValues[$i]) ) return false; // error
			if( !in_array($newValues[$i], $oldValues) ) { $ret_created[] = $newValues[$i]; }
		}
		
		return true; // success
	}


	
	function formatField($index, $action, $tableDef, $fieldName, $oldValue, $newValue)
	{
		// for secondary tables, correct $tableDef and $fieldName
		if( $tableDef && strpos($fieldName, '.') ) {
			$temp = explode('.', $fieldName); // field name may be "seconday.id.field" or "secondary.field"
			for( $r = 0; $r < sizeof($tableDef->rows); $r++ ) {
				if( $tableDef->rows[$r]->name == $temp[0] 
				 && ($tableDef->rows[$r]->flags&TABLE_ROW)==TABLE_SECONDARY ) {
					$tableDef = $tableDef->rows[$r]->addparam;
					$fieldName = $temp[sizeof($temp)-1];
					break;
				}
			}
		}
		
		// find out the correct field descr and halt on the correct row
		$fieldDescr = isohtmlspecialchars($fieldName);
		$fieldFlags = 0;
		if( $tableDef ) {
			for( $r = 0; $r < sizeof($tableDef->rows); $r++ ) {
				if( $tableDef->rows[$r]->name == $fieldName ) {
					$fieldDescr = trim($tableDef->rows[$r]->descr);
					$fieldFlags = $tableDef->rows[$r]->flags;
					break;
				}
			}
		}

		if( $fieldDescr == $fieldName )
		{
			switch( $fieldName ) { // try some defaults for the field names
				case 'user_access':			$fieldDescr = htmlconstant('_RIGHTS'); break;
				case 'user_grp':			$fieldDescr = htmlconstant('_GROUP'); break;
				case 'user_created':		$fieldDescr = htmlconstant('_LOG_OWNER'); break;
				case 'url':					$fieldDescr = 'URL'; break;
				case 'ip':					$fieldDescr = htmlconstant('_LOG_IP_ADDRESS'); break;
				case 'browser':				$fieldDescr = htmlconstant('_LOG_BROWSER'); break;
				case 'loginname':			$fieldDescr = htmlconstant('_LOGINNAME'); break;
				case 'msg':					$fieldDescr = htmlconstant('_LOG_MSG'); break;
				case 'query':				$fieldDescr = htmlconstant('_LOG_QUERY'); break;
				case 'action':				$fieldDescr = htmlconstant('_LOG_ACTION'); break;
				
				case 'email':				$fieldDescr = 'E-Mail'; break;
				case 'format':				$fieldDescr = 'Format'; break;	// the fowllowing are from export/import
				case 'q':					$fieldDescr = 'Anfrage'; break;
				case 'file':				$fieldDescr = 'Datei'; break;
				case 'overwrite':			require_lang('lang/imex');	$fieldDescr = htmlconstant('_IMP_OVERWRITE'); break;
				case 'delete':				require_lang('lang/imex');	$fieldDescr = htmlconstant('_IMP_DELETE'); break;
				case 'further_options':		require_lang('lang/imex');	$fieldDescr = htmlconstant('_IMP_FURTHEROPTIONS'); break;
				case 'deleted':				$fieldDescr = 'Gelöschte Datensätze'; break;
				case 'export_host':			$fieldDescr = 'Export Host'; break;
				case 'export_user':			$fieldDescr = 'Exporteur'; break;
				case 'export_start_time':	$fieldDescr = 'Exportzeit'; break;
			}
		}
		
		// fine formatting ...
		$value = '';
		
		switch( $fieldFlags&TABLE_ROW )
		{
			case TABLE_SECONDARY:
				if( $action == 'edit' && $this->getIdListInfo($oldValue, $newValue, $deleted, $created, $orderModified) )
				{
					if( sizeof($created) ) { 
						$value .= ($value?', ':'') . '<span style="color:#0A0;">' . sizeof($created) . ' Stück hinzugefügt</span>'; 
					}
					/*no else, both may happen */
					if( sizeof($deleted) ) { 
						$value .= ($value?', ':'') . '<span style="color:#A00;">' . sizeof($deleted) . ' Stück gelöscht</span>'; 
						$value .= ' <a href="log.php?l='.$this->currLineNumber.'&amp;date='.$this->currDay.'" class="log_dt">[...]</a>';
						for( $i = 0; $i < sizeof($deleted); $i++ )
							$this->deletedSecondary[ $fieldName . '.' . $deleted[$i] . '.' ] = 1;
					}
					if( $orderModified && $value == '' ) { $value = '<i>Reihenfolge geändert</i>'; }
				} 
				break;
			
			case TABLE_MATTR:
			case TABLE_SATTR:
				$attrTableDef = $tableDef->rows[$r]->addparam;
				if( $this->getIdListInfo($oldValue, $newValue, $deleted, $created, $orderModified) )
				{
					if( sizeof($deleted) ) {
						for( $i = 0; $i < sizeof($deleted); $i++ ) 
							$value .= ($i?', ':'') . isohtmlspecialchars($attrTableDef->get_summary($deleted[$i])) .	
								'<a href="edit.php?table='.$attrTableDef->name.'&amp;id='.$deleted[$i].'">&nbsp;&#8599;</a>';
						$value = '<s>' . $value . '</s> ';
					}
					
					if( sizeof($created) ) {
						for( $i = 0; $i < sizeof($created); $i++ ) 
							$value .= ($i?', ':'') . isohtmlspecialchars($attrTableDef->get_summary($created[$i]))	 .
								'<a href="edit.php?table='.$attrTableDef->name.'&amp;id='.$created[$i].'">&nbsp;&#8599;</a>';
						if( ($fieldFlags&TABLE_ROW)==TABLE_MATTR && $action == 'edit' /*else this is a dump*/ ) $value .= ' hinzugefügt'; 
					}
					
					if( $orderModified && $value == '' ){ $value = '<i>Reihenfolge geändert</i>'; }
				}
				break;
			
			default:																					// also used for user_created etc.
				if( $oldValue && $tableDef ) $oldValue = $tableDef->formatField($fieldName, $oldValue); // only format non-empty values to avoid getting stuff as "unknown" or "n/a"
				if( $newValue && $tableDef )$newValue = $tableDef->formatField($fieldName, $newValue);
				break;
		}

		if( $value == '' && $fieldFlags == '' )
		{
			switch( $fieldName ) { 
				case 'portal': // very special for WISY
					$fieldDescr = 'Portal';
					$portalTableDef = Table_Find_Def('portale', 0 /*no access check*/);
					$value = $portalTableDef->get_summary(intval($newValue))
								. '<a href="edit.php?table=portale&amp;id='.$newValue.'">&nbsp;&#8599;&nbsp;</a>';
					break;
				
				case 'overwrite':
					$dummy = new IMP_MIXFILE_CLASS;
					switch($newValue) {
						case IMP_OVERWRITE_NEVER: $newValue = htmlconstant('_IMP_OVERWRITENEVER'); break;
						case IMP_OVERWRITE_OLDER: $newValue = htmlconstant('_IMP_OVERWRITEOLDER'); break;
						case IMP_OVERWRITE_ALWAYS: $newValue = htmlconstant('_IMP_OVERWRITEALWAYS'); break;
					}
					break;

				case 'delete':
					$dummy = new IMP_MIXFILE_CLASS;
					switch($newValue) {
						case IMP_DELETE_NEVER: $newValue = htmlconstant('_IMP_DELETENEVER'); break;
						case IMP_DELETE_DELETED: $newValue = htmlconstant('_IMP_DELETEDELETED'); break;
					}
					break;
					
				case 'ip':
					$value = $newValue . ' ('. @gethostbyaddr($newValue) . ')';
					break;
			}
		}
		
		if( $value == '' )
		{
			$oldValue = str_replace("\\n", '<br />', isohtmlspecialchars($oldValue));
			$newValue = str_replace("\\n", '<br />', isohtmlspecialchars($newValue));
			$value = '<s>' . $oldValue . '</s> ' . $newValue;
		}

		if( $this->times[$index] )
		{
			$fieldDescr = intval($this->times[$index]) . ' x ' . $fieldDescr . ' geändert';
		}
		
		if( $fieldName == 'msg' && $value != '' && !$this->times[$index] )
			return '<b>' . $value . '</b>';
		else
			return '<b>' . $fieldDescr . '</b>: ' . $value;
	}	
	
};

