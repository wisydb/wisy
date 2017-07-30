<?php


/*=============================================================================
the search formular class
===============================================================================

file:	
	index_form.inc.php
	
author:	
	Bjoern Petersen

parameters:
	none, only function definitions in this file
	
usage:
		 $ob = new DBSEARCH_FORM_CLASS($table, $settings);
		[$ob->setAction($action, $hiddenParam);]
		 $ob->changeStateFromUrl($stateName);
	echo $ob->render();

to reset the search mask use the url...
		...&searchreset=1

=============================================================================*/




class DBSEARCH_FORM_CLASS
{
	// common
	var $tableDefName;	// the name of the table to search in

	var $action;		// where to go if the form is submitted
	var $hiddenParam;

	var $settings;		// associative array with :
						// keys:				values (first is default):
						// fields				comma separated list of fields 
						//						to use, emty list for default
						//						fields
						// fieldoptions			0, 1
						// hide_<field name>	0, 1
						// rows_min				3, n
						// rows_autooverhead	1, n
						// rows_addrowsoverhead	2, n

	// advanced mode
	var $fieldNames;	
	var $fieldHash;		
	var $fieldDescr;
	var $rows;

	
	//
	// DBSEARCH_FORM_CLASS constructor
	// -------------------------------
	//
	function __construct(			$tableDefName		= 'main',
									$settings			= ''
								)
	{
		// store basic information
		$this->tableDefName		= $tableDefName;
		
		// init settings
		$this->settings = is_array($settings)? $settings : array();
		
		if( !isset($this->settings['rows_min']) )				{ $this->settings['rows_min']				= 3; }
		if( !isset($this->settings['rows_autooverhead']) )		{ $this->settings['rows_autooverhead']		= 1; }
		if( !isset($this->settings['rows_addrowsoverhead']) )	{ $this->settings['rows_addrowsoverhead']	= 2; }

		// create array for the fields
		$this->settingsFields = array();
		$temp = str_replace(' ', '', $this->settings['fields']);
		if( $temp != '' ) {
			$temp = explode(',', $temp);
			for( $i = 0; $i < sizeof($temp); $i++ ) {
				$this->settingsFields[$temp[$i]] = 1;
			}
		}

		// create array for the columns
		$this->settingsColumns = array();
		$temp = str_replace(' ', '', $this->settings['columns']);
		if( $temp != '' ) {
			$temp = explode(',', $temp);
			for( $i = 0; $i < sizeof($temp); $i++ ) {
				$this->settingsColumns[$temp[$i]] = 1;
			}
		}
		
		// get table definition
		global $site;
		require_once("eql.inc.php");
		require_once("table_def.inc.php");
		require_once("config/db.inc.php");
	}

	//
	// DBSEARCH_FORM_CLASS->setAction()
	// --------------------------------
	// used as '<form action="$action"...>$hiddenParam'
	// where $hiddenParam should contain sth. like '<input type="hidden"... />'
	//
	function setAction($action, $hiddenParam)
	{
		$this->action		= $action;
		$this->hiddenParam	= $hiddenParam;
	}
	
	//
	// DBSEARCH_FORM_CLASS loading/saving States
	// -----------------------------------------
	//
	private function _initState($stateName)
	{
		$this->rows			= array();
		
		$this->_saveStateToSession($stateName);
	}
	
	private function _loadStateFromSession($stateName)
	{
		$state = $_SESSION['g_session_dbsearch'][$stateName];
		
		if( !is_array($state) )
		{
			$state = @unserialize(regGet("$stateName.lastquery", ''));
		}
		
		if( is_array($state) ) 
		{
			$this->rows			= $state['rows'];
		}
		else 
		{
			$this->_initState($stateName);
		}
	}
	
	private function _saveStateToSession($stateName)
	{
		$state = array();
		$state['rows']			= $this->rows;
		
		if( !is_array($_SESSION['g_session_dbsearch']) )
			$_SESSION['g_session_dbsearch'] = array();
		
		$_SESSION['g_session_dbsearch'][$stateName] = $state;
		
		regSet("$stateName.lastquery", serialize($state), '');
	}

	//
	// DBSEARCH_FORM_CLASS->changeStateFromUrl()
	// -----------------------------------------
	// returns !=0 if records should be searched.
	// returns 0 otherwise (if "addrow" is selected)
	//
	function changeStateFromUrl($stateName, $init = 0)
	{
		global $site;
		
		// first, handle $_REQUEST['searchalt'] which may be used as an alternative
		// to set values normally set by submit-buttons
		$searchaddrow = $_REQUEST['searchaddrow'];
		switch( $_REQUEST['searchalt'] ) 
		{
			case 'addrow': $searchaddrow = 1; break;
		}

		// load previous state from session
		$this->_loadStateFromSession($stateName);
		
		// init?
		if( $_REQUEST['searchreset'] )
		{
			$this->_initState($stateName);
			if( $_REQUEST['searchreset'] == 2 ) 
			{
				// init by URL
				for( $i = 0; $i < 100; $i++ ) {
					$value="v{$i}";
					if( isset($_REQUEST[$value]) ) {
						$this->rows[$i] = array('', '', '', '');
					}
					else {
						break;
					}
				}
			}
		}
		
		// assume, the caller should select the records
		$ret = 1;
		
		// set needed scripts
		$site->addScript('index_form.js');
		
		// change rows from URL param
		$newRows = $this->rows;
		
		$prevEmptyRows = 0;
		for( $i = 0; $i < sizeof($newRows); $i++ ) 
		{
			$and="a{$i}"; 	$field="f{$i}";   $op="o{$i}";	$value="v{$i}";

			if( isset($_REQUEST[$value]) ) 
			{
				$_REQUEST[$value] = trim($_REQUEST[$value]);
				if( $_REQUEST[$value] != '' && ($_REQUEST[$field]=='' || $_REQUEST[$field]=='OPTIONS' || $_REQUEST[$field]=='DUMMY') ) {
					$_REQUEST[$field] = 'ANY';
				}
				$newRows[$i] = array($_REQUEST[$and], $_REQUEST[$field], $_REQUEST[$op], $_REQUEST[$value]);
			}
			
			if( !$newRows[$i][3] ) {
				$prevEmptyRows++;
			}
		}

		// get the fields
		$this->fieldNames = array();
		$this->fieldDescr = array();
		$this->fieldHash  = array();
		$this->fieldIsDefault = array();
		$this->loadFields($this->tableDefName);
		
		// remove empty rows
		$this->rows = array();
		for( $i = 0; $i < sizeof($newRows); $i++ ) {
			if( $newRows[$i][3] != '' ) {
				$this->rows[] = $newRows[$i];
			}
		}

		// add one empty rows at end
		if( $this->settings['rows_autooverhead'] ) {
			$this->rows[] = array('', '', '', '');
			$newEmptyRows = 1;
		}
		else {
			$newEmptyRows = 0;
		}
	
		// force a minimal number of rows
		while( sizeof($this->rows) < $this->settings['rows_min'] ) {
			$this->rows[] = array('', '', '', '');
			$newEmptyRows++;
		}
		
		// add two additional rows if "add row" was pressed
		if( isset($searchaddrow) ) {
			$ret = 0;
			for( $i = 0; $i < ($prevEmptyRows-$newEmptyRows)+$this->settings['rows_addrowsoverhead']; $i++ ) {
				$this->rows[] = array('', '', '', '');
			}
		}
		
		// store state back to session
		$this->_saveStateToSession($stateName);
		
		return $ret;
	}



	//
	// DBSEARCH_FORM_CLASS->loadFields()
	// ----------------------------------
	//
	private function _addField__($name, $descr, $indentCnt, $indentFuncs, $loadAll, $isDefaultField /*0=no, 1=default, 2=default+alwaysOn*/ )
	{
		global $site;
		
		$fieldName = g_eql_normalize_func_name($name, 0);
		
		if( $loadAll 
		 || $this->settingsFields["$indentFuncs$fieldName"]
		 || (sizeof($this->settingsFields)==0 && $isDefaultField)
		 ||	$isDefaultField==2 )
		{
			$fieldNormName					= g_eql_normalize_func_name($name, 1);
			$this->fieldNames[]				= "$indentFuncs$fieldName";
			$this->fieldHash[$fieldNormName]= $fieldName;
			
			$descr = trim(htmlconstant($descr));
			
			if( $loadAll )
			{
				// store indent for further usage in index_listattr.php
				$this->fieldIndent[]	= $indentCnt;
				$this->fieldIsSelected[]= ($this->settingsFields["$indentFuncs$fieldName"] || (sizeof($this->settingsFields)==0 && $isDefaultField) || $isDefaultField==2)? 1 : 0;
				$this->fieldIsDefault[]	= $isDefaultField;
			}
			else
			{
				// shorten and indent description
				$descr = $site->htmldeentities($descr);
				if( strlen($descr) > 16 ) {
					$descr = substr($descr, 0, 16) . '...';
				}
				$descr = isohtmlentities($descr);
				
				if( $indentCnt ) {
					$descr = "&nbsp; - $descr";
					if( $indentCnt > 1 ) {
						$descr = str_repeat('&nbsp; &nbsp; ', $indentCnt-1) . $descr;
					}
				}
			}
			
			$this->fieldDescr[] = $descr;
		}
	}
	
	function loadFields($tableDefName, $loadAll = 0, $indentCnt = 0, $indentFuncs = '', $addDefaultFields = 1)
	{
		// avoid too deep recursion
		if( $indentCnt > 2 ) {
			return;
		}
		
		// get table definition
		$tableDef = Table_Find_Def($tableDefName);

		// add fields for ID/Active		
		if( $indentCnt == 0 )
		{
			$this->_addField__('id',  '_MOD_DBSEARCH_FIELDID',      0, '', $loadAll, 2);
			if( regGet('toolbar.bin', 1) )
				$this->_addField__('job', '_OVERVIEW_SELECTREMEMBERED',	0, '', $loadAll, 2);
		}
		
		// add all rows
		for( $r = 0; $r < sizeof($tableDef->rows); $r++ ) 
		{
			$rowflags	= $tableDef->rows[$r]->flags;
			$rowtype	= $rowflags&TABLE_ROW;
			
			// check if the field is a default field
			$isDefaultField = 0;
			if( $addDefaultFields ) 
			{
				if( ($rowflags & (TABLE_LIST|TABLE_SUMMARY))
				 || $rowtype == TABLE_SECONDARY || $rowtype == TABLE_MATTR || $rowtype == TABLE_SATTR )
				{
					$isDefaultField = 1;
				}
			}
			
			// add field
			$this->_addField__($tableDef->rows[$r]->name, $tableDef->rows[$r]->descr,
				$indentCnt, $indentFuncs, $loadAll, $isDefaultField);
			
			// add linked tables
			if( $rowtype == TABLE_SECONDARY || $rowtype == TABLE_MATTR || $rowtype == TABLE_SATTR )
			{
				$linkedTable = $tableDef->rows[$r]->addparam->name;
				if( $linkedTable != $tableDefName )
				{
					$this->loadFields($linkedTable, $loadAll,
						$indentCnt+1, $indentFuncs.g_eql_normalize_func_name($tableDef->rows[$r]->name, 0).'.',
						$rowtype == TABLE_SECONDARY? 1 : 0 /* add default fields */);
				}
			}
		}

		// add created/modified etc.
		if( $indentCnt == 0 ) 
		{
			$this->_addField__('created',		'_OVERVIEW_CREATED',			$indentCnt, '', $loadAll, 0);
			$this->_addField__('createdby',		'_OVERVIEW_CREATEDBY',			$indentCnt, '', $loadAll, 0);
			$this->_addField__('modified',		'_OVERVIEW_MODIFIED',			$indentCnt, '', $loadAll, 0);
			$this->_addField__('modifiedby', 	'_OVERVIEW_MODIFIEDBY',			$indentCnt, '', $loadAll, 0);
			$this->_addField__('group',			'_GROUP',						$indentCnt, '', $loadAll, 0);
			$this->_addField__('rights',		'_RIGHTS',						$indentCnt, '', $loadAll, 0);
		}
	}

	//
	// DBSEARCH_FORM_CLASS->loadColumns()
	// -----------------------------------
	//
	private function _addColumn__($name, $descr, $indentCnt, $isDefaultColumn /*0=no, 1=default, 2=default+alwaysOn*/ )
	{
		$this->columnNames[]		= $name;
		$this->columnDescr[]		= htmlconstant($descr);
		$this->columnIndent[]		= $indentCnt;
		$this->columnIsSelected[]	= ($this->settingsColumns[$name] || (sizeof($this->settingsColumns)==0 && $isDefaultColumn) || $isDefaultColumn==2)? 1 : 0;
		$this->columnIsDefault[]	= $isDefaultColumn;
	}
	
	function loadColumns($tableDefName)
	{
		// get table definition, init arrays
		$tableDef				= Table_Find_Def($tableDefName);
		$this->columnNames		= array();
		$this->columnDescr		= array();
		$this->columnIsDefault	= array();
		$this->columnIsSelected	= array();
		$this->columnIndent		= array();

		// add columns for ID/Active
		$this->_addColumn__('id',	'_MOD_DBSEARCH_FIELDID',		0, 2);
			
		// go through all columns
		$hasSecondary = 0;
		$secondaryIsDefault = 0;
		for( $r = 0; $r < sizeof($tableDef->rows); $r++ ) 
		{
			$rowflags	= $tableDef->rows[$r]->flags;
			$rowtype	= $rowflags&TABLE_ROW;
			
			$isDefaultColumn = ($rowflags&TABLE_LIST)? 1 : 0;

			if( $rowtype == TABLE_SECONDARY ) 
			{
				$hasSecondary = 1;
				if( $isDefaultColumn ) {
					$secondaryIsDefault = 1;
				}
				
				$this->_addColumn__('adummyfield', $tableDef->rows[$r]->descr, 0, 0);

				$sTableDef = Table_Find_Def($tableDef->rows[$r]->addparam->name);
				for( $sr = 0; $sr < sizeof($sTableDef->rows); $sr++ ) {
					$rowflags	= $sTableDef->rows[$sr]->flags;
					$rowtype	= $rowflags&TABLE_ROW;
					if( $rowtype != TABLE_SECONDARY ) {
						$this->_addColumn__("{$tableDef->rows[$r]->addparam->name}.{$sTableDef->rows[$sr]->name}", $sTableDef->rows[$sr]->descr, 1, 0);
					}
				}
			}
			else 
			{
				$this->_addColumn__($tableDef->rows[$r]->name, $tableDef->rows[$r]->descr, 0, $isDefaultColumn);
			}
		}
		
		if( $hasSecondary ) {
			$this->_addColumn__('SUMMARY',	'_SUMMARY', 0, $secondaryIsDefault);
		}
				
		$this->_addColumn__('date_created',	'_OVERVIEW_CREATED',			0, 0);
		$this->_addColumn__('user_created',	'_OVERVIEW_CREATEDBY',			0, 0);
		$this->_addColumn__('date_modified','_OVERVIEW_MODIFIED',			0, 0);
		$this->_addColumn__('user_modified','_OVERVIEW_MODIFIEDBY',			0, 0);
		$this->_addColumn__('user_grp',		'_GROUP',						0, 0);
		$this->_addColumn__('user_access',	'_RIGHTS',						0, 0);
		$this->_addColumn__('REFERENCES',	'_REFCOUNT',					0, 0);
	}


	//
	// DBSEARCH_FORM_CLASS->_addOrRemoveRow()
	// --------------------------------------
	//
	private function _addOrRemoveRow(&$rows, $expr, $remove)
	{
		// get field / value
		$field = 'ANY';
		$value = trim($expr);
		if( substr($value, -1) == ')' ) {
			$test = explode('(', $value);
			if( sizeof($test) == 2 ) {
				$testField = $this->fieldHash[g_eql_normalize_func_name($test[0])];
				if( $testField ) {
					$field = $testField;
					$value = trim(substr($test[1], 0, strlen($test[1])-1));
				}
			}
		}
		
		// check if the row exists
		for( $i = 0; $i < sizeof($rows); $i++ ) {
			if( $rows[$i][1]==$field && $rows[$i][3]==$value ) {
				if( $remove ) {
					$rows[$i][3] = '';
				}
				return; // row found / removed
			}
		}
		
		// add row
		if( !$remove ) {
			$rows[] = array('', $field, '', $value);
		}
	}


	//
	// DBSEARCH_FORM_CLASS->render()
	// -----------------------------
	//
	function render()
	{
		global $site;
	
		// get form start/end tag
		$formStart = "<form name=\"dbsearch\" action=\"{$this->action}\" method=\"get\"><input type=\"hidden\" name=\"searchalt\" value=\"\" />{$this->hiddenParam}<input type=\"hidden\" name=\"searchoffset\" value=\"0\" />";
		$formEnd = "</form>";

		// get the 'search' button
		$searchButton = '<input class="button" type="submit" name="searchdo" value="' .htmlconstant('_MOD_DBSEARCH_DOSEARCH'). "\"";
		$searchButton .= ' />';

		// advanced mode...
	
		if( $site ) {
			$optionsHref = "index_listattr.php?table={$this->tableDefName}";
		}

		$ret  = "$formStart<table border=\"0\">";
		
			for( $rowNum = 0; $rowNum < sizeof($this->rows); $rowNum++ ) 
			{
				$ret .= '<tr>';
				
					// and / or
					if( sizeof($this->rows) > 1 ) {
						$ret .= '<td>';
							if( $rowNum ) {
								$ret .= "<select name=\"a{$rowNum}\" size=\"1\">";
									$ret .= $this->_renderOption('', $this->rows[$rowNum][0], htmlconstant('_MOD_DBSEARCH_AND'));
									$ret .= $this->_renderOption('or', $this->rows[$rowNum][0], htmlconstant('_MOD_DBSEARCH_OR'));
								$ret .= "</select>";
							}
						$ret .= '</td>';
					}

					// field/op/value
					$ret .= '<td nowrap="nowrap">';
					
						// field
						$ret .= "<select name=\"f{$rowNum}\" size=\"1\" onchange=\"dbsearch_chf($rowNum, '{$optionsHref}');\" class=\"acselect\">";
							$ret .= $this->_renderOption('', $this->rows[$rowNum][1], '');
							$ret .= $this->_renderOption('ANY', $this->rows[$rowNum][1], htmlconstant('_MOD_DBSEARCH_FIELDANY'));
							for( $i = 0; $i < sizeof($this->fieldNames); $i++ ) {
								$ret .= $this->_renderOption($this->fieldNames[$i], $this->rows[$rowNum][1], $this->fieldDescr[$i]);
							}
							
							if( $site 
							 && $this->settings['fieldoptions'] )
							{
								$ret .= $this->_renderOption('DUMMY', '', '');
								$ret .= $this->_renderOption('OPTIONS', '', htmlconstant('_MOD_DBSEARCH_OPTIONS'));
								$ret .= $this->_renderOption('OPTIONS', '', '');
							}
						$ret .= "</select> ";

						// op
						$ist = $this->rows[$rowNum][2];
						$ret .= "<select name=\"o{$rowNum}\" size=\"1\" onchange=\"dbsearch_cho($rowNum);\">";
							$ret .= $this->_renderOption('',	$ist, '=');
							$ret .= $this->_renderOption('ne',	$ist, '&lt;&gt;');
							$ret .= $this->_renderOption('lt',	$ist, '&lt;');
							$ret .= $this->_renderOption('le',	$ist, '&lt;=');
							$ret .= $this->_renderOption('gt',	$ist, '&gt;');
							$ret .= $this->_renderOption('ge',	$ist, '&gt;=');
							$ret .= $this->_renderOption('p', 	$ist, htmlconstant('_MOD_DBSEARCH_OPPHRASE'));
							$ret .= $this->_renderOption('f', 	$ist, htmlconstant('_MOD_DBSEARCH_OPFUZZY'));
							$ret .= $this->_renderOption('r',	$ist, htmlconstant('_MOD_DBSEARCH_OPWORDSTART'));
							$ret .= $this->_renderOption('l',	$ist, htmlconstant('_MOD_DBSEARCH_OPWORDEND'));
							$ret .= $this->_renderOption('b',	$ist, htmlconstant('_MOD_DBSEARCH_OPCONTAINS'));
							//$ret .= $this->_renderOption('o',	$ist, htmlconstant('_MOD_DBSEARCH_OPONEOF'));
						$ret .= "</select> ";

						// value
						$ret .= "<input type=\"text\" name=\"v{$rowNum}\" value=\"" .isohtmlentities($this->rows[$rowNum][3]). "\" size=\"30\" maxlength=\"1000\" style=\"width:220pt;\" class=\"acclass\" data-acdata=\"{$this->tableDefName}.seeselect\" />";
					
					// field/op/value done
					$ret .= '</td>';

					// buttons
					$ret .= '<td>';
						if( $rowNum == 0 ) {
							$ret .= $searchButton;
						}
					$ret .= '</td>';
					
				$ret .= '</tr>';
			}

		$ret .= "</table>$formEnd";

		return $ret;
	}
	
	private function _renderOption($istValue, $sollValue, $descr)
	{
		// render option
		$ret = "<option value=\"$istValue\"";
		if( $istValue == $sollValue ) {
			$ret .= ' selected="selected"';
		}
		$ret .= ">$descr</option>";
		
		return $ret;
	}
	
	//
	// get the ELSE Query Language String
	//
	function getEql()
	{
		$eql = '';
		
		for( $i = 0; $i < sizeof($this->rows); $i++ ) 
		{
			$value = trim($this->rows[$i][3]);
			if( $value!='' ) 
			{
				$andor	= $this->rows[$i][0];
				$field	= strtolower($this->rows[$i][1]);
				$op		= $this->rows[$i][2];
				$close	= '';
				
				// and / or 
				switch( $andor ) 
				{
					case 'or':
						if( $eql ) { $eql .= ' or '; }
						break;

					case 'not':
						if( $eql ) { $eql .= ' and '; }
						$eql .= 'not(';
						$close .= ')';
						break;

					default:
						if( $eql ) { $eql .= ' and '; }
						break;
				}

				// field
				if( $field && $field!='any' ) {
					$eql .= $field;
				}
				else {
					$field = '';
				}

				// op
				switch( $op )
				{
					case 'p': // phrase
						if( $field ) $eql .= '=';
						$eql .= '"';
						$close .= '"';
						break;
					
					case 'f': // fuzzy
						if( $field ) { $eql .= '('; $close .= ')'; }
						$eql .= 'fuzzy(';
						$close .= ')';
						break;

					case 'r': // truncated right
						if( $field ) { $eql .= '('; $close .= ')'; }
						$value = "{$value}*";
						if( strpos($value, ' ') ) $value = "\"{$value}\"";
						break;

					case 'l': // truncated left
						if( $field ) { $eql .= '('; $close .= ')'; }
						$value = "*{$value}";
						if( strpos($value, ' ') ) $value = "\"{$value}\"";
						break;

					case 'b': // truncated both (contains)
						if( $field ) { $eql .= '('; $close .= ')'; }
						$value = "*{$value}*";
						if( strpos($value, ' ') ) $value = "\"{$value}\"";
						break;

					case 'o': // one of
						if( $field ) { $eql .= '('; $close .= ')'; }
						$eql .= 'oneof(';
						$close .= ')';
						break;
					
					case 'ne': // not equal
						if( $field ) {
							$eql .= '<>';
						}
						else {
							$eql .= 'not(';
							$close .= ')';
						}
						break;

					case 'lt': // less than
						if( $field ) $eql .= '<';
						break;

					case 'le': // less or equal
						if( $field ) $eql .= '<=';
						break;

					case 'gt': // greater than
						if( $field ) $eql .= '>';
						break;
						
					case 'ge': // greater or equal
						if( $field ) $eql .= '>=';
						break;

					default:
						if( $field ) { $eql .= '('; $close .= ')'; }
						break;
				}
				
				if( $close == '' && strpos($value, ' ') ) {
					$eql .= '(';
					$close = ')';
				}
				
				$eql .= $value;
				
				if( $close ) {
					$eql .= $close;
				}
			}
		}
		
		return $eql;
	}
}


