<?php


/*=============================================================================
Print Handler
===============================================================================

file:	
	print.php
	
author:	
	Bjoern Petersen

parameters:
	table			-	the table to print
	id				-	the record to print (optional)

subsequent parameters:
	printArea
	view
	prevview [sic!]	-	previous view
	ok				-	start print now

=============================================================================*/





/*=============================================================================
Functions
=============================================================================*/


// print class templage
class print_plugin_class
{
	var $options;			// the options to use
	var $param;				// parameters from the options
	
	function __construct()
	{
	}
	
	function printdo()
	{
	}
}



// the print dialog
function render_print_dialog($id, $printArea, $view, $printViewCnt, $printSelectedCnt, $printAllCnt)
{
	global $site;
	global $tableDef;
	global $all_print_plugins;
	global $module;
	
	$site->title = "$tableDef->descr - " . htmlconstant('_PRINT');
	$site->pageStart(array('popfit'=>1));
	form_tag('print_form', 'print.php', '', '', 'get');
	form_hidden('table', $tableDef->name);
	form_hidden('id', $id);
	form_hidden('prevview', /*[sic!]*/ $view);
	
		$site->skin->submenuStart();
			echo htmlconstant('_PRINT');
		$site->skin->submenuBreak();
			$site->menuHelpEntry('iprint');
		$site->skin->submenuEnd();
		
		$site->skin->dialogStart();
			// area
			form_control_start(htmlconstant('_PRINT_AREA'));
				$viewopt = 'view###'.htmlconstant($cntView==1? '_PRINT_AREA_ONLYRECORDINVIEW' : '_PRINT_AREA_ONLYRECORDSINVIEW', $printViewCnt);
				
				if( $printSelectedCnt ) {	
					$viewopt .= '###selected###'.htmlconstant('_PRINT_AREA_ALLSELECTEDRECORDS', $printSelectedCnt);
				}
			
				$viewopt .= '###all###'.htmlconstant('_PRINT_AREA_ALLRECORDS', $printAllCnt);
				
				if( !$printArea ) { $printArea = 'view'; }
				form_control_radio('printArea', $printArea, $viewopt);
			form_control_end();
			
			// view
			form_control_start(htmlconstant('_PRINT_VIEW'));
			
				$viewopt = "";
				reset($all_print_plugins);
				foreach($all_print_plugins as $name => $descr) {
					$viewopt .= $viewopt? "###" : "";
					$viewopt .= "$name###$descr";
				}
			
				form_control_enum('view', $view, $viewopt, 0, '', 'submit();return true;');

			form_control_end();
			
			// view options
			if( is_array($module->options) )
			{
				reset($module->options);
				foreach($module->options as $optionName => $optionParam)
				{
					if( $optionName == 'fontsize' )
					{
						form_control_start(htmlconstant('_PRINT_FONTSIZE'));
							$options = '';
							for( $i = 6; $i <= 18; $i++ ) {
								$options .= $options? '###' : '';
								$options .= "$i###".htmlconstant('_PRINT_FONTSIZENPT', $i);
							}
							form_control_enum('fontsize', $module->param['fontsize'], $options);
						form_control_end();
					}
					else if( $optionName == 'pagebreak' )
					{
						form_control_start(htmlconstant('_PRINT_PAGEBREAK'));
							$pagebreakopt =  '0###'.htmlconstant('_PRINT_PAGEBREAK_AUTO');
							$pagebreakopt .= '###1###'.htmlconstant('_PRINT_PAGEBREAK_1');
							for( $i =  2; $i <    8; $i+= 1 ) { $pagebreakopt .= '###'.$i.'###'.htmlconstant('_PRINT_PAGEBREAK_N', $i); }
							for( $i =  8; $i <   20; $i+= 2 ) { $pagebreakopt .= '###'.$i.'###'.htmlconstant('_PRINT_PAGEBREAK_N', $i); }
							for( $i = 20; $i <=  50; $i+= 5 ) { $pagebreakopt .= '###'.$i.'###'.htmlconstant('_PRINT_PAGEBREAK_N', $i); }
							for( $i = 50; $i <= 100; $i+=10 ) { $pagebreakopt .= '###'.$i.'###'.htmlconstant('_PRINT_PAGEBREAK_N', $i); }
							form_control_enum('pagebreak', $module->param['pagebreak'], $pagebreakopt);
						form_control_end();
					}
					else switch( $optionParam[0] )
					{
						case 'check':
							form_control_start($optionParam[3]? htmlconstant($optionParam[3]) : 0);
								form_control_check($optionName, $module->param[$optionName], '', 0, 1);
								echo "<label for=\"$optionName\">" . htmlconstant($optionParam[1]) . '</label>';
							form_control_end();
							break;
						
						case 'remark':
							form_control_start($optionParam[3]? htmlconstant($optionParam[3]) : 0);
								echo htmlconstant($optionParam[1]);
							form_control_end();
							break;
					}
				}
			}
		$site->skin->dialogEnd();
		$site->skin->buttonsStart();
			form_button('ok', htmlconstant('_PRINT'));
			form_button('cancel', htmlconstant('_CANCEL'), 'window.close();return false;');
		$site->skin->buttonsEnd();
	
	echo '</form>';
	$site->pageEnd();
}



/*=============================================================================
Global Part
=============================================================================*/




// common includes
set_time_limit(120);

require('functions.inc.php');
require('eql.inc.php');
require_lang('lang/print');


// get all print plugins
$all_print_plugins = array();
for( $i = 0; $i <= 1; $i++ ) 
{		
	$handle = @opendir($i==0? '.' : 'config');
	if( $handle ) {
		while( $folderentry = readdir($handle) ) {
			if( preg_match('/^print_format_([a-z0-9_]+)\.inc\.php$/', $folderentry, $matches) )
			{
				$p = strrpos($matches[1], '_');
				if( $p===false
				 || substr($matches[1], 0, $p)==$table )
				{
					$all_print_plugins[$matches[1]] = htmlconstant("_PRINT_".strtoupper($matches[1]));
				}
			}
		}
		closedir($handle);
	}
}

asort($all_print_plugins);

// get parameters
$printArea	= $_REQUEST['printArea']; 
$view		= $_REQUEST['view']; 
$table		= $_REQUEST['table'];
$id 		= intval($_REQUEST['id']);
$prevview	= $_REQUEST['prevview'];

// check basic parameters: get table def
$tableDef = $id? Table_Find_Id($table, $id) : Table_Find_Def($table);
if( !$tableDef ) {
	$site->abort(__FILE__, __LINE__);
	exit();
}

// load and init module
$temp = $id? 'edit' : 'index';
if( $view ) {
	regSet("print.$temp.$table", $view, $id? 'details' : 'list');
}
else {
	$view = regGet("print.$temp.$table", $id? 'details' : 'list');
}

if( !$all_print_plugins[$view] ) {
	$view = $id? 'details' : 'list';
}

if( @file_exists("print_format_{$view}.inc.php") ) {
	require_once("print_format_{$view}.inc.php");
}
else {
	require_once("config/print_format_{$view}.inc.php");
}

$temp = "print_{$view}_class";
$module = new $temp;
$module->param['table'] = $table;



// check pagebreak
if( isset($_REQUEST['pagebreak']) ) {
	$module->param['pagebreak'] = intval($_REQUEST['pagebreak']);
	regSet('print.pagebreak.pages', $module->param['pagebreak'], 0);
}
else {
	$module->param['pagebreak'] = regGet('print.pagebreak.pages', 0);
}

if( $module->param['pagebreak'] <= 0 ) {
	$module->param['pagebreak'] = 0;
}



// check font size
if( isset($_REQUEST['fontsize']) ) {
	$module->param['fontsize'] = intval($_REQUEST['fontsize']);
	regSet('print.fontsize.pt', $module->param['fontsize'], 10);
}
else {
	$module->param['fontsize'] = regGet('print.fontsize.pt', 10);
}

if( $module->param['fontsize']<=4 || $module->param['fontsize']>=40 ) {
	$module->param['fontsize'] = 10;
}



// check additional settings
if( is_array($module->options) && (!$prevview || $prevview==$view) )
{
	reset($module->options);
	foreach($module->options as $optionName => $optionParam)
	{
		if( $optionName!='pagebreak' && $optionName!='fontsize' && $optionParam[0]!='remark') 
		{
			if( $prevview ) {
				$module->param[$optionName] = $options[0]=='check'? ($_REQUEST[$optionName]? 1 : 0) : $_REQUEST[$optionName];
				regSet("print.$view.$optionName", $module->param[$optionName], $optionParam[2]);
			}
			else {
				$module->param[$optionName] = regGet("print.$view.$optionName", $optionParam[2]);
			}
		}
	}
}


// save settings
regSave();



// get the EQL/SQL statments and the record counts...

// ...all
$eql2sql = new EQL2SQL_CLASS($tableDef->name);
$printAllEql = '*';
$printAllSql = $eql2sql->eql2sql($printAllEql, 'id', acl_get_sql(ACL_READ, 0, 1, $tableDef->name), regGet("index.view.{$tableDef->name}.orderby", 'date_modified DESC'));
$printAllCnt = $eql2sql->sqlCount($printAllSql);


// ...selected
$printSelectedEql = '';
$printSelectedSql = '';
$printSelectedCnt = 0;
if( isset($_SESSION['g_session_index_sql'][$tableDef->name]) ) {
	$printSelectedEql = $_SESSION['g_session_index_eql'][$tableDef->name]==''? '*' : $_SESSION['g_session_index_eql'][$tableDef->name];
	$printSelectedSql = $_SESSION['g_session_index_sql'][$tableDef->name];
	$printSelectedCnt = $eql2sql->sqlCount($printSelectedSql);
}


// ...view
$printViewEql = '';
$printViewSql = '';
$printViewCnt = 0;
if( $id ) {
	$printViewEql		= "id($id)";
	$printViewOffset	= 0;
	$printViewRows		= 1;
	$printViewSql		= "SELECT id FROM $tableDef->name WHERE id=$id";
	$printViewCnt		= 1;
}
else if( $printSelectedSql ) {
	$printViewEql		= $printSelectedEql;
	$printViewOffset	= intval(regGet("index.view.{$tableDef->name}.offset", 0));
	$printViewRows		= intval(regGet("index.view.{$tableDef->name}.rows", 10));
	$printViewSql		= "$printSelectedSql LIMIT $printViewOffset,$printViewRows";
	$db = new DB_Admin;
	$db->query($printViewSql);
	while( $db->next_record() ) { $printViewCnt++; }
}



// invoke dialog or print?

if( !$_REQUEST['ok'] )
{
	render_print_dialog($id, $printArea, $view, $printViewCnt, $printSelectedCnt, $printAllCnt);
}
else
{
	if( $printArea == 'all' ) {
		$module->param['eql']		= $printAllEql;
		$module->param['offset']	= 0;
		$module->param['rows']		= $printAllCnt;
		$module->param['sql']		= $printAllSql;
		$module->param['cnt']		= $printAllCnt;
	}
	else if( $printArea == 'selected' ) {
		$module->param['eql'] 		= $printSelectedEql;
		$module->param['offset']	= 0;
		$module->param['rows']		= $printSelectedCnt;
		$module->param['sql'] 		= $printSelectedSql;
		$module->param['cnt'] 		= $printSelectedCnt;
	}
	else {
		$module->param['eql'] 		= $printViewEql;
		$module->param['offset']	= $printViewOffset;
		$module->param['rows']		= $printViewRows;
		$module->param['sql'] 		= $printViewSql;
		$module->param['cnt'] 		= $printSelectedCnt;
	}
	
	$module->printdo();
}

