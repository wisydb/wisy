<?php



/*=============================================================================
Check the user access rights
===============================================================================

file:	
	user_access_view.php
	
author:	
	Bjoern Petersen

parameters:
	user 			-	the user ID to check, if not set, the logged-in user is used
	table			-	the table to check
	id				-	the record to check, if unset, common table access
						is checked

=============================================================================*/








/******************************************************************************
 * Functions
 *****************************************************************************/

// check a single field
function check_access($path, $id, $user, $descr)
{
	global $site;
	
	$site->skin->rowStart();
		
		$site->skin->cellStart();
			echo $descr;
		$site->skin->cellEnd();
		
		$site->skin->cellStart();
			echo $path;
		$site->skin->cellEnd();
		
		$site->skin->cellStart();
			$access = acl_get_access($path, $id, $user);
			echo '<span style="color:' .($access? '#00A000' : '#A00000'). ';">' .acl_get_readable_str($access). '</span>';
		$site->skin->cellEnd();
		
		if( $user != $_SESSION['g_session_userid'] ) {
			$site->skin->cellStart();
				$access = acl_get_access($path, $id, $_SESSION['g_session_userid']);
				echo '<span style="color:' .($access? '#00A000' : '#A00000'). ';">' .acl_get_readable_str($access). '</span>';
			$site->skin->cellEnd();
		}
		
	$site->skin->rowEnd();
}


// write the table headlines
function write_head($name, $user)
{
	global $site;

	$site->skin->headStart();
		$site->skin->cellStart('width="25%"');
			echo htmlconstant('_FIELD');
		$site->skin->cellEnd();

		$site->skin->cellStart('width="25%"');
			echo htmlconstant('_EDIT_INTERNALNAME');
		$site->skin->cellEnd();
		
		// foreign user
		if( $user != $_SESSION['g_session_userid'] ) {
			$site->skin->cellStart('width="25%"');
				echo user_html_name($user);
			$site->skin->cellEnd();
		}
		
		// self user
		$site->skin->cellStart('width="25%"');
			echo htmlconstant('_EDIT_YOURRIGHTS');
		$site->skin->cellEnd();
		
	$site->skin->headEnd();
}


// check a whole table
function check_table($table, $id /*may be -1*/, $user)
{
	global $site;
	
	// get table def
	$table_def = Table_Find_Def($table, 0 /*no access check*/);
	if( !$table_def ) {
		return;
	}
	
	$site->skin->tableStart();
	
		write_head($table_def->descr, $user);
		
		// common access
		check_access($table_def->name.'.COMMON', $id, $user, $table_def->descr.'.COMMON');
		
		// field access
		for( $r = 0; $r < sizeof((array) $table_def->rows); $r++ ) {
			check_access($table_def->name.'.'.$table_def->rows[$r]->name, $id, $user, $table_def->descr.'.'.$table_def->rows[$r]->descr);
		}
		
		// RIGHTS
		check_access($table_def->name.'.RIGHTS', $id, $user, $table_def->descr.'.RIGHTS');
		
	$site->skin->tableEnd();
}



// check supervisor
function check_supervisor($user)
{
	global $Table_Def;
	global $db;
	global $site;

	$site->skin->tableStart();

		write_head('SUPERVISOR', $user);
	
		$db->query("SELECT shortname,name,id FROM user_grp ORDER BY shortname, name");
		while( $db->next_record() ) {
			$name = $db->fs('shortname');
			if( !$name ) {
				$name = $db->fs('name');
				if( !$name ) {
					$name = $db->f('id');
				}
			}
			
			check_access('SUPERVISOR.'.$db->f('id'), -1, $user, 'SUPERVISOR.'.isohtmlentities($name));
		}
	
	$site->skin->tableEnd();
}

// check system
function check_system($user)
{
	global $Table_Def;
	global $db;
	global $site;

	$site->skin->tableStart();

		write_head('SYSTEM', $user);
	
		check_access('SYSTEM.IMPORT',			-1, $user, 'SYSTEM.IMPORT');
		check_access('SYSTEM.EXPORT',			-1, $user, 'SYSTEM.EXPORT');
		check_access('SYSTEM.LOCALIZELANG',		-1, $user, 'SYSTEM.LOCALIZELANG');
		check_access('SYSTEM.LOCALIZEENTRIES',	-1, $user, 'SYSTEM.LOCALIZEENTRIES');
		
		$temp = explode(',', strtoupper(regGet('addrules.system', '')));
		for( $t = 0; $t < sizeof($temp); $t++ ) {
			if( trim($temp[$t]) ) {
				check_access('SYSTEM.'.trim($temp[$t]),	-1, $user, 'SYSTEM.'.trim($temp[$t]));
			}
		}
		
	$site->skin->tableEnd();
}



/******************************************************************************
 * Global Part
 *****************************************************************************/

 
require_once('functions.inc.php');
require_lang('lang/overview');
require_lang('lang/edit');

// get parameters
$user = intval($_REQUEST['user']);
$table = $_REQUEST['table'];
$id = intval($_REQUEST['id']);


 
// get user enum, correct user
$user_found = 0;
$users_enum = '';
$db->query("SELECT id,name,loginname FROM user ORDER BY name, loginname");
while( $db->next_record() )
{
	$name = $db->fs('name');
	if( $name=='' ) {
		$name = $db->fs('loginname');
		if( $name=='' ) { 
			$name = $db->f('id');
		}
	}
	
	if( strlen($name) > 32 ) {
		$name = substr($name, 0, 28) . '..';
	}

	$users_enum .= ($users_enum?'###':'') . $db->f('id') . '###' . isohtmlspecialchars($name);
	
	if( $db->f('id') == $user ) {
		$user_found = 1;
	}
}

if( !$user_found ) {
	$user = $_SESSION['g_session_userid'];
}



// get table enum, correct table
$table_found = 0;
$tables_enum = '';
for( $t = 0; $t < sizeof((array) $Table_Def); $t++ ) {
	$tables_enum .= $Table_Def[$t]->name . '###' . $Table_Def[$t]->descr . '###';
	if( $Table_Def[$t]->name == $table ) {
		$table_found = 1;
	}
}
$tables_enum .= 'SUPERVISOR###SUPERVISOR###SYSTEM###SYSTEM';

if( !$table_found 
 &&  $table != 'SUPERVISOR' 
 &&  $table != 'SYSTEM' )
{
	$table = $Table_Def[0]->name;
	$id = -1;
}
else if( $table == 'SUPERVISOR'
	  || $table == 'SYSTEM' )
{
	$id = -1;
}



// correct id
$id = intval($id);
if( $id <= 0 ) {
	$id = -1;
}






// page out

$site->title = htmlconstant('_RIGHTS');
$site->pageStart();

	$site->skin->workspaceStart();
	
		form_tag('check_form', 'user_access_view.php', '', '', 'get');
	
			echo htmlconstant('_USER') . '=';
			if( acl_get_access('user.COMMON') ) {
				form_control_enum('user', $user, $users_enum, 0, '', 'this.form.submit(); return true;');
			}
			else {
				echo '<b>' . user_html_name($user) . '</b>,';
			}

			echo ' ' . htmlconstant('_TABLE') . '=';
			form_control_enum('table', $table, $tables_enum, 0, '', "this.form.id.value=''; this.form.submit(); return true;");

			echo ' ' . htmlconstant('_ID'). '=';
			form_control_text('id', $id==-1?'':$id, 6 /*width*/, 6 /*maxlen*/);

			echo ' <input class="button" type="submit" name="search_do" value="OK" />';

			$site->menuHelpEntry('iyourrights');

		echo '</form>';
	$site->skin->workspaceEnd();
	
	if( $table == 'SUPERVISOR' ) {
		check_supervisor($user);
	}
	else if( $table == 'SYSTEM' ) {
		check_system($user);
	}
	else {
		check_table($table, $id /*may be -1*/, $user);
	}

$site->pageEnd();



