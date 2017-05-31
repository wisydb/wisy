<?php



/*=============================================================================
table overview, also used as an attribute selector
===============================================================================

file:	
	index.php
	
author:	
	Bjoern Petersen

parameters to call from extern as a simple list:
	table			- the table to list
	justedited		- optional, the ID of the record just saved or canceled, 
					  used in addition with the hash #id1234

additional parameters when calling as attribute selector:
	selectobject	- we should select an object for the editor

internal parameters for subsequent calls:
	orderby			- set the order
	searchoffset	- the offset for result output
	rows			- the number of rows to show on result output
	...

=============================================================================*/



/*=============================================================================
Functions
=============================================================================*/



function createColumnsHash($table, $prefix = '')
{
	global $columnsToShow;
	global $columnsHash;
	global $show_summary;
	global $show_secondary;
	global $sqlFields;

	$columnsCount		= 0;
	$hasSecondary		= 0;
	$secondaryIsDefault	= 0;
	
	if( $prefix == '' )
	{
		// ID/bin/view column
		$columnsCount++;
		$columnsHash[] = 1;
	}
	
	// "direct" columns
	$tableDef = Table_Find_Def($table);
	for( $r = 0; $r < sizeof($tableDef->rows); $r++ ) 
	{
		$rowflags		= intval($tableDef->rows[$r]->flags);
		$rowtype		= $rowflags&TABLE_ROW;
		$defaultColumn	= $rowflags&TABLE_LIST? 1 : 0;
		
		if( $rowtype == TABLE_SECONDARY )
		{
			$hasSecondary		= 1;
			$secondaryIsDefault	= $secondaryIsDefault? 1 : $defaultColumn;
			
			$secondaryColumnsCount = createColumnsHash(	$tableDef->rows[$r]->addparam->name,
														$tableDef->rows[$r]->addparam->name . '.');
			if( $secondaryColumnsCount ) {
				$columnsCount += $secondaryColumnsCount;
				$show_secondary[$tableDef->rows[$r]->addparam->name] = 1;
			}
		}
		else if( (!$columnsToShow && $defaultColumn && $prefix=='')
			  || ( $columnsToShow && in_array($prefix.$tableDef->rows[$r]->name, $columnsToShow)) )
		{
			$columnsCount++;
			$columnsHash[] = 1;
			
			if( $prefix == '' ) {
				if( $rowtype != TABLE_MATTR ) {
					$sqlFields .= ', ' . $tableDef->rows[$r]->name;
				}
			}
		}
		else
		{
			$columnsHash[] = 0;
		}
	}
	
	if( $prefix == '' ) 
	{
		// secondary summary column
		$columnsHash[] = 0;
		if( $hasSecondary ) {
			if( (!$columnsToShow && $secondaryIsDefault)
			 || ( $columnsToShow && in_array('SUMMARY', $columnsToShow)) )
			{
				$columnsCount++;
				$columnsHash[sizeof($columnsHash)-1] = 1;
				$show_summary = 1;
			}
		}

		// "rights" columns		
		$test = array
		(	
			'date_created', 'date_created', 
			'user_created', 'user_created', 
			'date_modified','date_modified', 
			'user_modified','user_modified', 
			'user_grp',		'user_grp', 
			'user_access', 	'user_access', 
			'REFERENCES',	''
		);
					
		for( $r = 0; $r < sizeof($test); $r += 2 ) {
			if( $columnsToShow && in_array($test[$r], $columnsToShow) ) 
			{
				$columnsCount++;
				$columnsHash[] = 1;
				if( $test[$r+1] ) {
					$sqlFields .= ', ' . $test[$r+1];
				}
			}
			else
			{
				$columnsHash[] = 0;
			}
		}
	}
	
	// done
	return $columnsCount;
}

function renderTableHeadCell($curr_field, $descr, $def_desc = 0, $sum_field = 0)
{
	global $site;
	global $columnsCount;
	global $tableHeadCellsRendered;
	global $tableDef;
	global $orderby;
	global $baseurl;
	global $Table_Shortnames;
	
	if( !isset($tableHeadCellsRendered) )
		$tableHeadCellsRendered = 0;
	
	// start head cell
	$site->skin->cellStart();
	
		// prepare for columns settings
		$settingsOut = 0;
		if( $tableHeadCellsRendered == $columnsCount-1 && !isset($_REQUEST['object']) ) 
		{
			echo '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="left" nowrap="nowrap" class="hdnested">';
			$settingsOut = 1;
		}

		// get order by information
		$orderby_sort = ''; // == ASC
		if( strpos($orderby, 'DESC') ) {
			$orderby_sort = 'DESC';
		}

		$orderby_field = $orderby;
		$orderby_field = str_replace('ASC', '', $orderby_field);
		$orderby_field = str_replace('DESC', '', $orderby_field);
		$orderby_field = trim($orderby_field);

		// get arrowImage'n'action
		$img = '';
		$action = '';
		if( $curr_field ) {
			$action = $baseurl;
			if( $curr_field == $orderby_field ) {
				if( $orderby_sort == 'DESC' ) {
					$img = 'sortdesc';
					$action .= '&orderby=' . $curr_field;
				}
				else {
					$img = 'sortasc';
					$action .= '&orderby=' . $curr_field . '+DESC';
				}
				$title = htmlconstant('_OVERVIEW_SORTTOGGLE');
			}
			else {
				if( $def_desc ) {
					$action .= '&orderby=' . $curr_field . '+DESC';
				}
				else {
					$action .= '&orderby=' . $curr_field;
				}
				$title = htmlconstant('_OVERVIEW_SORT');
			}
		}
	
		// render action'n'description
		if( $action ) {
			echo '<a href="'.isohtmlentities($action)."\" title=\"$title\">";
		}

		$descr = trim($descr);
		if( $Table_Shortnames[$descr] ) {
			echo $Table_Shortnames[$descr];
		}
		else {
			echo $descr;
		}

		if( $action ) {
			if( $img ) {
				echo $img=='sortdesc'? " {$site->skin->ti_sortdesc}" : " {$site->skin->ti_sortasc}";
			}
			echo '</a>';
		}

		if( $sum_field )
		{
			$dbsum = new DB_Admin;
			$dbsum->query("SELECT SUM($curr_field) AS alltogether FROM {$tableDef->name};");
			$dbsum->next_record();
			echo ' &sum;'.intval($dbsum->f('alltogether'));
		}

		// columns settings out
		if( $settingsOut )
		{
			echo '&nbsp;</td><td align="right" nowrap="nowrap">';
				$href = "index_listattr.php?table={$tableDef->name}&amp;scope=columns";
				echo "<a href=\"$href\" target=\"dbsearch_opt\" onclick=\"return popup(this,260,500);\" title=\"".htmlconstant('_OVERVIEW_EDITCOLUMNS___')."\">|||</a>";
			echo '</td></tr></table>';
		}
	
	// end head cell
	$site->skin->cellEnd();
	$tableHeadCellsRendered++;
}

function getSortField($tableDef)
{
	for( $r = 0; $r < sizeof($tableDef->rows); $r++ )
	{
		$rowflags	= intval($tableDef->rows[$r]->flags);
		$rowtype	= $rowflags&TABLE_ROW;
		if( ($rowflags&TABLE_LIST || $rowflags&TABLE_SUMMARY)
		 && $rowtype!=TABLE_MATTR
		 && $rowtype!=TABLE_SATTR
		 && $rowtype!=TABLE_SECONDARY )
		{
			return $tableDef->rows[$r]->name;
		}
	}
	return 'id'; // nothing else found
}

function renderTableHead(&$hi, $table, $prefix = '')
{
	global $columnsHash;
	global $site;
	
	if( $prefix == '' ) 
	{
		// select column (this column is not counted)
		if( isset($_REQUEST['object']) ) {
			$site->skin->cellStart();
				echo htmlconstant('_SELECT');
			$site->skin->cellEnd();
		}
	
		// ID / BIN / VIEW column	
		if( $columnsHash[$hi++] ) {
			renderTableHeadCell('id', htmlconstant('_ID'), 1);
		}
	}
	
	// "direct" columns
	$tableDef = Table_Find_Def($table);
	for( $r = 0; $r < sizeof($tableDef->rows); $r++ ) 
	{
		$rowflags	= intval($tableDef->rows[$r]->flags);
		$rowtype	= $rowflags&TABLE_ROW;
		
		if( $rowtype == TABLE_SECONDARY )
		{
			renderTableHead($hi, $tableDef->rows[$r]->addparam->name, 
				$tableDef->rows[$r]->name . '.');
		}
		else 
		{
			if( $columnsHash[$hi++] ) 
			{
				// find sort criteria
				if( $prefix == '' ) {
					switch( $rowtype )
					{
						case TABLE_MATTR:
						case TABLE_SATTR:
							$curr_field = $tableDef->rows[$r]->name.'.'.getSortField($tableDef->rows[$r]->addparam);
							break;
							
						default:
							$curr_field = $tableDef->rows[$r]->name;
							break;
					}
				}
				else {
					switch( $rowtype ) 
					{
						case TABLE_MATTR:	
						case TABLE_SATTR:
							$curr_field = '';
							break;
							
						default:
							$curr_field = $prefix.$tableDef->rows[$r]->name;
							break;
					}
				}
				
				renderTableHeadCell( 
					$curr_field, 
					$tableDef->rows[$r]->descr,
					($rowtype==TABLE_DATE||$rowtype==TABLE_DATETIME)? 1 : 0,
					($rowtype==TABLE_INT&&($rowflags&TABLE_SUM))? 1 : 0);
			}
		}
	}
	
	if( $prefix == '' ) 
	{
		// secondary summary column
		if( $columnsHash[$hi++] ) {
			renderTableHeadCell('', htmlconstant('_SUMMARY'));
		}
	
		// "rights" columns
		$test = array
		(	
			'date_created',		'_OVERVIEW_CREATED',	'_OVERVIEW_CREATED',	1,
			'user_created', 	'_OVERVIEW_CREATEDBY',	'_OVERVIEW_BY',			0,
			'date_modified', 	'_OVERVIEW_MODIFIED',	'_OVERVIEW_MODIFIED',	1,
			'user_modified',	'_OVERVIEW_MODIFIEDBY',	'_OVERVIEW_BY',			0,
			'user_grp',			'_GROUP',				'_GROUP',				0,
			'user_access', 		'_RIGHTS',				'_RIGHTS',				0,
			'',					'_REFABBR',				'_REFABBR',				0
		);
					
		for( $r = 0; $r < sizeof($test); $r+=4 ) {
			if( $columnsHash[$hi++] ) {
				renderTableHeadCell($test[$r], htmlconstant($test[$r+($lastSet? 2 : 1)]), $test[$r+3]);
				$lastSet = 1;
			}
			else {
				$lastSet = 0;
			}
		}
	}
}

function canRead()
{
	global $curr_access;
	
	if( $curr_access & ACL_READ ) 
	{
		return 1;
	}
	else
	{
		global $curr_access_hint_printed;
		global $noaccessimgsize;
		global $site;
		
		if( !$curr_access_hint_printed ) {
			$title = htmlconstant('_NOACCESS');
			echo "<img src=\"{$site->skin->imgFolder}/noaccess.gif\" width=\"{$noaccessimgsize[0]}\" height=\"{$noaccessimgsize[1]}\" border=\"0\" alt=\"$title\" title=\"$title\" />";
			$curr_access_hint_printed = 1;
		}
		else {
			echo '&nbsp;';
		}
		
		return 0;
	}
}

function getHtmlContent(&$tableDef, $r, &$db)
{
	global $dba;

	$rowflags	= intval($tableDef->rows[$r]->flags);
	$rowtype	= $rowflags&TABLE_ROW;
	
	switch( $rowtype )
	{
		case TABLE_BLOB:
			$value = explode(';', $db->fs($tableDef->rows[$r]->name));
			if( $value[0] != '' ) {
				return isohtmlentities($value[0]);
			}
			else {
				return '&nbsp;';
			}
			
		case TABLE_TEXT:
		case TABLE_TEXTAREA:
			$value = smart_truncate($db->fs($tableDef->rows[$r]->name));
			if( $value != '' ) {
				return isohtmlentities($value);
			}
			else {
				return '&nbsp;';
			}

		case TABLE_MATTR:
			$value = '';
			
			$id = $db->f('id');
			$dba->query("SELECT attr_id FROM $tableDef->name" . '_' . $tableDef->rows[$r]->name . " WHERE primary_id=$id ORDER BY structure_pos");
			while( $dba->next_record() ) {
				$attrid = $dba->f('attr_id');
				if( $value ) {
					$value .= ', ';
				}
				$value .= $tableDef->rows[$r]->addparam->get_summary($attrid, '; ' /*value seperator*/);
			}
			
			if( $value ) {
				return isohtmlentities($value);
			}
			else {
				return '&nbsp;';
			}

		case TABLE_SATTR:
			$value = isohtmlentities($tableDef->rows[$r]->addparam->get_summary($db->f($tableDef->rows[$r]->name), '; ' /*value seperator*/));
			return $value==''? '&nbsp;' : $value;

		case TABLE_INT:
			$value = $tableDef->formatField($tableDef->rows[$r]->name, $db->f($tableDef->rows[$r]->name));
			if( $value ) {
				return isohtmlspecialchars($value);
			}
			else {
				if($tableDef->rows[$r]->default_value !== 0 && $value === 0) {
					return intval($value); // "0" if price or similar where "0" is not the same as "nothing"
				} elseif ($rowtype == TABLE_INT) {
					return "&nbsp;"; // "&nbsp;" if no price (etc.) at all
				}
			}
			
		default:
			$value = $tableDef->formatField($tableDef->rows[$r]->name, $db->f($tableDef->rows[$r]->name));
			if( $value ) {
				return isohtmlspecialchars($value);
			}
			else {
				return '&nbsp;';
			}
	}
}



/*=============================================================================
Global Part begins here
=============================================================================*/

// includes
if( isset($_REQUEST['object']) ) require_once('deprecated_edit_class.php'); // must be included _before_ the session is started in functions.inc.php as the edit instances may be stored in the session
require_once('functions.inc.php');
require_once('index_tools.inc.php');
require_once('index_form.inc.php');
require_lang('lang/dbsearch');
require_lang('lang/overview');

// get table and other paramters
$table = $_REQUEST['table'];
if( !$table ) // may be unset after a call to index.php after a login with the session id already set
{
	require_once('login.inc.php');
	$table = get_first_accecssible_table();
}

$debug = $_REQUEST['debug'];

// get base URL
$baseurl = "index.php?table=$table";
if( isset($_REQUEST['selectobject']) )
{
	$baseurl .= "&selectobject";
	$settingsPrefix = 'index.select';
}
else
{
	$settingsPrefix = 'index.view';
}

if( $debug ) {
	$baseurl .= "&debug=$debug";
}

$allurl = "$baseurl&searchreset=1&searchoffset=0&orderby=date_modified+DESC";

// connect to database
$db	= new DB_Admin;
$dba= new DB_Admin;
$dbs= new DB_Admin;

// increase time available to execute this script
set_time_limit(90);

// find out table definition
$tableDef = Table_Find_Def($table);
if( !$tableDef ) 
{
	$site->abort(__FILE__, __LINE__);
	exit();
}


// load settings - either from stored settings or from URL parameters

// offset
if( !isset($_REQUEST['searchoffset']) ) {
	$searchoffset = intval( regGet("$settingsPrefix.$table.offset", 0) );
}
else {
	$searchoffset = intval( $_REQUEST['searchoffset'] );
	regSet("$settingsPrefix.$table.offset", $searchoffset, 0);
}

if( $searchoffset < 0 ) {
	$searchoffset = 0;
}

// rows
if( !isset($_REQUEST['rows']) ) {
	$rows = intval( regGet("$settingsPrefix.$table.rows", 10) );
}
else {
	$rows = intval( $_REQUEST['rows'] );
	regSet("$settingsPrefix.$table.rows", $rows, 10);
}

if( $rows < 1 ) { $rows = 1; }
if( $rows > 200 ) { $rows = 200; }

// order
if( !isset($_REQUEST['orderby']) ) {
	$orderby = regGet("$settingsPrefix.$table.orderby", 'date_modified DESC');
	if( !$orderby ) {
		$orderby = 'date_modified DESC';
		regSet("$settingsPrefix.$table.orderby", $orderby, 'date_modified DESC');
	}
}
else {
	$orderby = $_REQUEST['orderby'];
	regSet("$settingsPrefix.$table.orderby", $orderby, 'date_modified DESC');
}

// save settings is done after changeStateFromUrl()



// calculate the number of columns and the fields needed for the query

// get the columns to show, 
// if this list is empty, we'll show the default columns
$columnsToShow = regGet("index.view.$table.columns", '');
$columnsToShow = str_replace(' ', '', $columnsToShow);
if( $columnsToShow ) {
	$columnsToShow = explode(',', $columnsToShow);
}


// create columns hash
$columnsHash = array();
$sqlFields = '';
$columnsCount = createColumnsHash($tableDef->name);
$sqlFields = 'id' . $sqlFields;



// select records

if( !isset($_REQUEST['object']) ) {
	$_SESSION['g_session_list_results'][$table] = array(); // needed to move in result from the editor
}

// create the SQL condition matching the user's ACL rights
$aclCond = '';
if( regGet('index.onnoaccess', '')!='showhint' ) {
	$aclCond = acl_get_sql(isset($_REQUEST['object'])? ACL_REF : ACL_READ, 0, 1, $table);
}

// create the search formular
$searchForm = new DBSEARCH_FORM_CLASS($table, array(
	'fields'				=>regGet("index.searchfields.{$table}", ''),
	'fieldoptions'			=>1,
	'columns'				=>regGet("index.view.{$table}.columns", ''),
	'rows_min'				=>1,
	'rows_autooverhead'		=>0,
	'rows_addrowsoverhead'	=>3,
));

// set search formular action
$addparam = "<input type=\"hidden\" name=\"table\" value=\"$table\" />";
if( isset($_REQUEST['selectobject']) ) {
	$addparam .= "<input type=\"hidden\" name=\"selectobject\" value=\"1\" />";
}
if( $debug ) {
	$addparam .= '<input type="hidden" name="debug" value="' .$debug. '" />';
}
$searchForm->setAction('index.php', $addparam);

// get EQL (ELSE Query Language)...
if( !$searchForm->changeStateFromUrl("$settingsPrefix.$table") )
{
	$select_numrows = 0;
}
else
{
	$hasError = 0;
	$eql2sql = new EQL2SQL_CLASS($table, regGet('index.searchplusminusword', 0));
	$eql = $searchForm->getEql();

	// get SQL from EQL...
	$sql = $eql2sql->eql2sql($eql, $sqlFields, $aclCond, "$orderby, id");
	if( $sql == '0' ) 
	{
		// ...error - can't get SQL
		$site->msgAdd($eql2sql->lastError, 'e');
		$hasError = 1;
		$sql = "SELECT $sqlFields FROM $table WHERE id=-1";
	}

	// get the number of rows - we have an SQL statement anyway this time
	$select_numrows = $eql2sql->sqlCount($sql);
	
	if( $select_numrows == 0 && !$hasError )
	{
		$site->msgAdd("\n\n".htmlconstant('_OVERVIEW_NORECORDSFOUND', '<a href="' . isohtmlspecialchars($allurl) . '">', '</a>')."\n\n", 'i');
		$searchedButNothingFound = 1;
		
		if( regGet('index.searchshowpcode', 1) ) {
			$site->msgAdd("\n\n".htmlconstant('_OVERVIEW_YOURQUERYINTERPRETEDAS', '<i>'.isohtmlspecialchars($eql).'</i>', '<i>'.isohtmlspecialchars($eql2sql->getPCode($eql)).'</i>')."\n\n", 'i');
		}

		if( acl_grp_filter_active() ) {
			$site->msgAdd("\n\n".htmlconstant('_OVERVIEW_FILTERACTIVEWARNING', '', '')."\n\n", 'w');
			$_SESSION['g_session_filter_active_hint'] = 1;
		}
	}
	
	$info = $eql2sql->getInfo(regGet('index.searchfuzzyinfo2', 1));
	if( $info ) {
		$site->msgAdd("\n\n{$info}\n\n", 'i');
	}
}

if( !isset($_REQUEST['object']) ) {
	if( !is_array($_SESSION['g_session_index_sql']) ) $_SESSION['g_session_index_sql'] = array();
	if( !is_array($_SESSION['g_session_index_eql']) ) $_SESSION['g_session_index_eql'] = array();
	$_SESSION['g_session_index_sql'][$table] = $sql;
	$_SESSION['g_session_index_eql'][$table] = $eql;
}

regSave();



/*=============================================================================
start page and create the menu
=============================================================================*/



// link to overview
$site->menuItem('mmainmenu', $tableDef->descr, "<a href=\"index.php?table=$table\">");

// prev / next
$prevurl = $searchoffset==0? '' : ("$baseurl&searchoffset=" . intval($searchoffset-$rows));
$site->menuItem('mprev', htmlconstant('_PREVIOUS'), $prevurl? ('<a href="'.isohtmlentities($prevurl).'">') : '');

$nexturl = ($searchoffset+$rows<$select_numrows)? ("$baseurl&searchoffset=" . intval($searchoffset+$rows)) : '';
$site->menuItem('mnext', htmlconstant('_NEXT'), $nexturl? ('<a href="'.isohtmlentities($nexturl).'">') : '');

// add rows
$site->menuItem('msearchexp', htmlconstant('_OVERVIEW_SEARCHEXPAND'), 
	'<a href="' . isohtmlentities("$baseurl&searchaddrow=1") . '" onclick="return dbsearch_addrow();">');

// all
$site->menuItem('mall', htmlconstant('_OVERVIEW_ALL'), '<a href="'.isohtmlentities($allurl).'">');

// new / empty
$only_secondary = 0;
if( !isset($_REQUEST['object']) ) {
	if( $tableDef->acl&ACL_NEW ) {
		$only_secondary = $tableDef->is_only_secondary($only_secondary_primary_table_name, $dummy);
		$site->menuItem('mnew', htmlconstant('_NEW'), 
			$only_secondary? '' : "<a href=\"edit.php?table={$tableDef->name}\">");
		if( $tableDef->uses_track_defaults() ) {
			$site->menuItem('mempty', htmlconstant('_EMPTY'), 
				$only_secondary? '' : "<a href=\"edit.php?table={$tableDef->name}&amp;nodefaults=1\">");
		}
	}
}

// settings / print
$site->menuSettingsUrl	= "settings.php?table=$tableDef->name&scope=index&reload=" . urlencode($baseurl);
$site->menuPrintUrl		= $_REQUEST['object']? '' : "print.php?table=$tableDef->name";

// menu link to index plugin(s)
for( $i = 0; $i <= 3; $i++ ) 
{
	if( @file_exists("config/index_plugin_{$tableDef->name}_{$i}.inc.php") ) 
	{
		$test = explode(',', regGet("index_plugin_{$tableDef->name}_{$i}.access", ""));
		if( trim($test[0]) == '' || acl_check_access(trim($test[0]), -1, intval($test[1])? intval($test[1]) : ACL_EDIT) )
		{
			$site->menuItem("mplugin$i", htmlconstant(strtoupper("_index_plugin_{$tableDef->name}_{$i}")), 
				"<a href=\"module.php?module=index_plugin_{$tableDef->name}_{$i}\" target=\"index_plugin_{$tableDef->name}_{$i}\" onclick=\"return popup(this,750,550);\">");
		}
	}
	else 
	{
		break;
	}
}


// messages
if( $only_secondary )
{
	$site->msgAdd(htmlconstant('_OVERVIEW_ONLYSECONDARYDATA', "<a href=\"index.php?table=$only_secondary_primary_table_name\">", '</a>'), 'w');
}

if( !$_SESSION['g_session_filter_active_hint'] && acl_grp_filter_active() )
{
	$site->msgAdd(htmlconstant('_OVERVIEW_FILTERACTIVEWARNING', '', ''), 'w');
	$_SESSION['g_session_filter_active_hint'] = 1;
}



// start page'n'menu

$site->title = "$tableDef->descr - " . htmlconstant('_OVERVIEW');

$site->pageStart();


if( isset($_REQUEST['selectobject']) ) {
	$site->skin->submenuStart();
		$site->skin->submenuItem('mprev', htmlconstant('_PREVIOUS'), $prevurl? ('<a href="'.isohtmlentities($prevurl).'">') : '');$site->menuItem('mnext', htmlconstant('_NEXT'), $nexturl? ('<a href="'.isohtmlentities($nexturl).'">') : '');
		$site->skin->submenuItem('mnext', htmlconstant('_NEXT'), $nexturl? ('<a href="'.isohtmlentities($nexturl).'">') : '');
		$site->skin->submenuItem('msearchexp', htmlconstant('_OVERVIEW_SEARCHEXPAND'),  '<a href="' . isohtmlentities("$baseurl&searchaddrow=1") . '" onclick="return dbsearch_addrow();">');
		$site->skin->submenuItem('mall', htmlconstant('_OVERVIEW_ALL'), '<a href="'.isohtmlentities($allurl).'">');
	$site->skin->submenuBreak();
		echo 'Bitte einen Datensatz auswählen';
	$site->skin->submenuEnd();
}
else {
	$site->menuBinParam		= "table=$tableDef->name";
	$site->menuHelpScope	= (isset($_REQUEST['object'])? 'iattrselection' : ($tableDef->name.'.ioverviewrecords'));
	$site->menuLogoutUrl	= 'index.php?table='.$table;
	$site->menuOut();
}




/*=============================================================================
search formular, pagee selector and attribute selection hint out
=============================================================================*/



// search formular
$site->skin->workspaceStart();
	echo $searchForm->render();
$site->skin->workspaceEnd();

if( $select_numrows )
{
	// page selector
	$site->skin->mainmenuStart();
		echo page_sel("$baseurl&searchoffset=", $rows, $searchoffset, $select_numrows, 1);
	$site->skin->mainmenuEnd();

	if( $_REQUEST['object']) {
		// attribute selection hint
		$site->skin->submenuStart();
			echo $subtitle;
		$site->skin->submenuBreak();
			echo '&nbsp;';
		$site->skin->submenuEnd();
	}
}



/*=============================================================================
table out
=============================================================================*/



if( $select_numrows ) 
{
	// table start, table head
	$site->skin->tableStart();
	$site->skin->headStart();
		$hi = 0;
		renderTableHead($hi, $tableDef->name);
	$site->skin->headEnd();

	// prepage view and action URLs
	$action_view_base = '';
	if( file_exists("config/view_{$tableDef->name}.inc.php") ) {
		$action_view_base = "module.php?module=view_{$tableDef->name}&id=";
	}

	// get selected objects
	if( isset($_SESSION[$_REQUEST['object']]) ) {
		$_SESSION[$_REQUEST['object']]->getset_attrtable($edit_control, $attr_values, $dummy, $attr_flags, $dummy);
	}
	$checkimgsize = GetImageSize("{$site->skin->imgFolder}/check0.gif");
	$noaccessimgsize = GetImageSize("{$site->skin->imgFolder}/noaccess.gif");
	$show_bin = regGet('toolbar.bin', 1)? 1 : 0;

	if( isset($_REQUEST['selectobject']) ) {
		$tr_a_attr = ' class="clicktr" title="Auswählen" ';
	}
	else {
		$tr_a_attr = ' class="clicktr" title="' .htmlconstant('_OVERVIEW_EDIT'). '" '; // clicktr makes this url open on a click anywhere in the row
	}
	
	// go through all records
	$db->query("$sql LIMIT $searchoffset,$rows");
	while( $db->next_record() ) 
	{
		// init hash index, get ID
		$hi = 0;
		$id = $db->f('id');

		// get current access
		$curr_access = acl_get_access("$table.COMMON", $id);
		$curr_access_hint_printed = 0;
	
		// set stuff needed to move in result from the editor
		if( !isset($_REQUEST['object']) ) {
			$_SESSION['g_session_list_results'][$table][] = intval($id);
		}
	
		// get action			
		if( isset($_REQUEST['selectobject']) ) {
			$actionUri = "javascript:selUpdtOpnr($id);";
		} 
		else {
			$actionUri = "edit.php?table=$table&id=$id";
		}
		
		// start row
		$justedited = $id==$_REQUEST['justedited']? true : false;
		
		$site->skin->rowStart($justedited? 'class="justedited"' : '');
	
			// select column (this column is not counted)
			if( isset($_REQUEST['object']) ) {
				$site->skin->cellStart();
					if( $curr_access & ACL_REF ) 
					{
						$attr_checked = 'check0';
						for( $a = 0; $a < sizeof($attr_values); $a++ ) {
							if( $attr_values[$a] == $id ) {
								$attr_checked = 'check2';
								break;
							}
						}

						$uncheckOther = false;
						if( (($attr_flags&TABLE_ROW)==TABLE_SATTR) )
						{
							$uncheckOther = true;
						}
						
						echo "&nbsp;<a href=\"deprecated_edit_tgattr.php?object=".$_REQUEST['object']."&amp;tg_control=$edit_control&amp;tg_id=$id\" target=\"tg$id\" onclick=\"return editTgAttr(this," .($uncheckOther?"'{$site->skin->imgFolder}'":0). ");\">";
							echo "<img name=\"tg{$id}\" src=\"{$site->skin->imgFolder}/{$attr_checked}.gif\" width=\"{$checkimgsize[0]}\" height=\"{$checkimgsize[1]}\" border=\"0\" alt=\"[" .htmlconstant('_SELECT'). ']" />';
						echo '</a>&nbsp;';
					}
					else
					{
						echo "<img src=\"{$site->skin->imgFolder}/noaccess.gif\" width=\"{$noaccessimgsize[0]}\" height=\"{$noaccessimgsize[1]}\" border=\"0\" alt=\"" .htmlconstant('_NOTREFERENCEABLE'). '" title="' .htmlconstant('_OVERVIEW_NOTREFERENCEABLE'). '" />';
					}
				$site->skin->cellEnd();
			}
			
			// id / bin / view column
			if( $columnsHash[$hi++] )
			{
				$site->skin->cellStart('nowrap');
					
					if( $id == $_REQUEST['justedited'] )
					{
						echo "<a name=\"id$id\"></a>";
					}

					if( $action_view_base ) {
						if( canRead() ) {
							echo	'<a href="' . isohtmlentities($action_view_base) . $id . '" target="_blank" onclick="setClickConsumed();return true;" title="' .htmlconstant('_VIEW'). '">'  
								.		'&#8599;&nbsp;'
								.	'</a>';
						}
					}

					echo 	'<a href="' . isohtmlentities($actionUri) . '"'.$tr_a_attr.'>'
						.		$id											
						.	'</a>';

					if( $show_bin ) {
						echo ' ';
						echo bin_render($tableDef->name, $id);
					}
					
				$site->skin->cellEnd();
			}
			
			// "direct" columns
			$summary = '';
			for( $r = 0; $r < sizeof($tableDef->rows); $r++ ) 
			{
				$rowflags		= intval($tableDef->rows[$r]->flags);
				$rowtype		= $rowflags&TABLE_ROW;
				
				if( $rowtype == TABLE_SECONDARY )
				{
					// get secondary summary
					if( $show_summary
					 || $show_secondary[$tableDef->rows[$r]->addparam->name] )
					{
						$secondaryIds = array();
						$dba->query("SELECT secondary_id FROM {$table}_{$tableDef->rows[$r]->name} WHERE primary_id=$id ORDER BY structure_pos");
						while( $dba->next_record() ) {
							$secondaryIds[] = $dba->f('secondary_id');
							if( $show_summary ) {
								$curr = $tableDef->rows[$r]->addparam->get_summary($dba->f('secondary_id'), '; '/*value seperator*/, 1 /*force TABLE_LIST instead of TABLE_SUMMARY*/);
								if( $curr ) {
									$summary .= $summary? '; ' : '';
									$summary .= $curr;
								}
							}
						}
					}

					// get secondary table definition				
					$sTableDef = Table_Find_Def($tableDef->rows[$r]->addparam->name);
					if( $show_secondary[$tableDef->rows[$r]->addparam->name] )
					{
						// go through all secondary rows and collect the HTML contents
						$hiBak = $hi;
						$secondaryHash = array();
						for( $s = 0; $s < sizeof($secondaryIds); $s++ ) {
							$dbs->query("SELECT * FROM $sTableDef->name WHERE id=" . $secondaryIds[$s]);
							if( $dbs->next_record() ) {
								$hi = $hiBak;
								for( $sr = 0; $sr < sizeof($sTableDef->rows); $sr++ )
								{
									if( $columnsHash[$hi++] )
									{
										$value = getHtmlContent($sTableDef, $sr, $dbs);
										$secondaryHash[$sTableDef->rows[$sr]->name][$value] = 1;
									}
								}
							}
						}
						
						// go through all secondary rows and render 
						$hi = $hiBak;
						for( $sr = 0; $sr < sizeof($sTableDef->rows); $sr++ )
						{
							$rowflags	= $sTableDef->rows[$sr]->flags;
							$rowtype	= $rowflags&TABLE_ROW;
							
							if( $columnsHash[$hi++] )
							{
								$site->skin->cellStart();
									if( canRead() )
									{
										$valuesOut = 0;
										
										if( is_array($secondaryHash[$sTableDef->rows[$sr]->name]) )
										{
											reset($secondaryHash[$sTableDef->rows[$sr]->name]);
											while( list($value) = each($secondaryHash[$sTableDef->rows[$sr]->name]) ) {
												$value = strval($value);
												if( $value != '' && $value != '&nbsp;' ) {
													echo $valuesOut? ', ' : '';
													echo $value;
													$valuesOut++;
												}
											}
										}
										
										if( $valuesOut == 0 ) {
											echo '&nbsp;';
										}
									}
								$site->skin->cellEnd();
							}
						}
					}
					else
					{
						$hi += sizeof($sTableDef->rows);
					}
				}
				else
				{
					// direct column
					if( $columnsHash[$hi++] )
					{
						$site->skin->cellStart();
							if( canRead() )
							{
								echo getHtmlContent($tableDef, $r, $db);
							}
						$site->skin->cellEnd();
					}
				}
			}
			
			// summary column
			if( $columnsHash[$hi++] ) {
				$site->skin->cellStart();
					if( canRead() )
					{
						if( strlen($summary) > 77 ) { // truncate string
							$summary = substr($summary, 0, 77) . '...';
						}
						
						if( $summary ) {
							echo isohtmlentities($summary);
						}
						else {
							echo '&nbsp;';
						}
					}
				$site->skin->cellEnd();
			}
			
			// date created
			if( $columnsHash[$hi++] ) {
				$site->skin->cellStart();
					if( canRead() ) 
					{
						echo isohtmlentities(sql_date_to_human($db->f('date_created'), 'datetime'));
					}
				$site->skin->cellEnd();
			}

			// user created
			if( $columnsHash[$hi++] ) {
				$site->skin->cellStart();
					if( canRead() )
					{
						echo user_html_name($db->f('user_created'));
					}
				$site->skin->cellEnd();
			}

			// date modified
			if( $columnsHash[$hi++] ) {
				$site->skin->cellStart();
					if( canRead() )
					{
						echo isohtmlentities(sql_date_to_human($db->f('date_modified'), 'datetime'));
					}
				$site->skin->cellEnd();
			}

			// user modified
			if( $columnsHash[$hi++] ) {
				$site->skin->cellStart();
					if( canRead() )
					{
						echo user_html_name($db->f('user_modified'));
					}
				$site->skin->cellEnd();
			}

			// user group
			if( $columnsHash[$hi++] ) {
				$site->skin->cellStart();
					if( canRead() )
					{
						echo grp_html_name($db->f('user_grp'));
					}
				$site->skin->cellEnd();
			}

			// user access
			if( $columnsHash[$hi++] ) {
				$site->skin->cellStart('nowrap');
					if( canRead() )
					{
						echo access_to_human($db->f('user_access'));
					}
				$site->skin->cellEnd();
			}

			// references
			if( $columnsHash[$hi++] ) {
				$site->skin->cellStart();
					if( canRead() )
					{
						$num_references = $tableDef->num_references($id, $dummy);
						echo $num_references;
					}
				$site->skin->cellEnd();
			}
			
		// end row
		$site->skin->rowEnd();
	}

	// table End
	$site->skin->tableEnd();

	// any notes?
	if( $tableDef->addparam['notes'] ) {
		echo '<p>' . $tableDef->addparam['notes'] . '</p>';
	}
	
	// selection information
	if( !isset($_REQUEST['selectobject']) ) $site->skin->fixedFooterStart();
		$site->skin->submenuStart();
			
			$eqlShort = $eql;
			if( strlen($eqlShort) > 40 && !$_REQUEST['eqlfull'] ) {
				$eqlShort = isohtmlspecialchars(substr($eqlShort, 0, 40)) . '<a href="' . isohtmlspecialchars("$baseurl&eqlfull=1") . '">...</a>';
			}
			else {
				$eqlShort = isohtmlspecialchars($eqlShort);
			}
			
			echo htmlconstant($select_numrows==1? '_OVERVIEW_QUERYSUMMARYS' : '_OVERVIEW_QUERYSUMMARYP', "<i>$eqlShort</i>", $select_numrows);
			
			if( acl_grp_filter_active() ) {
				echo ' ' . htmlconstant('_OVERVIEW_QUERYSUMMARYFILTERACTIVE');
			}
			
			if( $aclCond ) {
				echo ' ' . htmlconstant('_OVERVIEW_QUERYSUMMARYMAYBEHIDDEN');
			}

		$site->skin->submenuBreak();

			echo htmlconstant('_OVERVIEW_ROWS') . ' ' . rows_per_page_sel("$baseurl&searchoffset=0&rows=", $rows);
			if( !isset($_REQUEST['selectobject']) ) {
				echo " | <a href=\"log.php?table=$table\" target=\"_blank\">" . htmlconstant('_LOG') . '</a>';
			}

		$site->skin->submenuEnd();
		
	if( !isset($_REQUEST['selectobject']) ) $site->skin->fixedFooterEnd();
}
else
{
	// cancel button to show all
	$site->skin->buttonsStart();
		form_clickbutton(isohtmlentities(($hasError || $searchedButNothingFound)? $allurl : $baseurl), htmlconstant('_CANCEL'));
	$site->skin->buttonsBreak();
		echo "<a href=\"log.php?table=$table\" target=\"_blank\">" . htmlconstant('_LOG') . '</a>';
	$site->skin->buttonsEnd();
}







/*=============================================================================
done: end page
=============================================================================*/



$site->pageEnd();

