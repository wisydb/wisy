<?php

/*=============================================================================
Rembemer a given Record in any list
===============================================================================

file:	
	bin.php
	
author:	
	Bjoern Petersen

parameters:
	table - the table the record belongs to, may be unset or empty
	id    - the id of the record, may be unset or < 1

=============================================================================*/


require_once('functions.inc.php');

require_lang('lang/settings');


function render_bin_overview()
{
	global $site;
	
	$table = isset( $_REQUEST['table'] )   ? $_REQUEST['table'] : null;
	$id = isset( $_REQUEST['id'] )         ? intval($_REQUEST['id']) : null;
	$what = isset( $_REQUEST['what'] )     ? $_REQUEST['what'] : null; 
	if( ( !isset($what) || $what!='inview' )  &&  ( !isset($what) || $what!='clicked' )  ) 
	    $what = 'clicked';

	$table_def = Table_Find_Def($table);
	$color = $table_def->color;

	// form out
	$site->skin->submenuStart();
		echo '<form action="bin.php" method="get">';
			form_hidden('table', $table);
			form_hidden('id', $id);
			$select = '<select name="what" size="1" onchange="this.form.submit(); return true;">';
				$select .= '<option value="clicked"'.($what=='clicked'?' selected="selected"':'').'>'.htmlconstant('_SETTINGS_BINCLICKEDRECORD').'</option>';
				$select .= '<option value="inview"' .($what=='inview' ?' selected="selected"':'').'>'.htmlconstant('_SETTINGS_BINRECORDSINVIEW').'</option>';
			$select .= '</select>';
			echo htmlconstant('_SETTINGS_BINREMEMBERIN___', $select);
		echo '</form>';
	$site->skin->submenuBreak();
		echo '&nbsp;';
	$site->skin->submenuEnd();

	$site->skin->workspaceStart();
	
		$imgsize = GetImageSize("{$site->skin->imgFolder}/bin0.gif");
		$i = 0;	
		$bins = isset($_SESSION['g_session_bin']) ? $_SESSION['g_session_bin']->getBins() : null;
		
		echo '<table cellpadding="3" cellspacing="0" border="0">';
		
			for( $i = 0; $i < sizeof((array) $bins); $i++ )
			{
				echo '<tr><td valign="top">';
				
				    $state = isset($_SESSION['g_session_bin']) ? $_SESSION['g_session_bin']->recordExists($table, $id, $bins[$i]) : null;
				    $editable = isset($_SESSION['g_session_bin']) ? $_SESSION['g_session_bin']->binIsEditable($bins[$i]) : null;
					
					echo $editable? "<a href=\"bin.php?list=" . urlencode($bins[$i]) . "&table=$table&id=$id&what=$what&toggle=1\">" : '';
		
					echo '<img name="bin_' . ($bins[$i] == (isset($_SESSION['g_session_bin']) ? $_SESSION['g_session_bin']->activeBin : null) ? 'scope' : $i) . '" src="' .$site->skin->imgFolder. '/bin' . ($state?'1':'0') . '.gif" width="' .$imgsize[0]. '" height="' .$imgsize[1]. '" border="0" alt="" />';
						echo '<img src="skins/default/img/1x1.gif" width="6" height="13" border="0" alt="" />';
						
					echo $editable? '</a>' : '';
				
				echo '</td><td>';
	
				    echo ( isset($_SESSION['g_session_bin']) ? html_entity_decode($_SESSION['g_session_bin']->getName($bins[$i])) : '' );
					
					if( !$editable ) {
						echo ' ' . htmlconstant('_SETTINGS_BINREADONLY');
					}
					
					if( sizeof((array) $bins) > 1 && $bins[$i] == ( isset($_SESSION['g_session_bin']) ? $_SESSION['g_session_bin']->activeBin : null ) ) {
						echo ' ' . htmlconstant('_SETTINGS_BINISACTIVEBIN');
					}

				echo '</td></tr>';
			}
		
		echo '</table>';
	
	$site->skin->workspaceEnd();

	$site->skin->buttonsStart();
		form_button('ok', htmlconstant('_OK'), 'window.close();return false;');
	$site->skin->buttonsEnd();

	$site->skin->tableEnd();
}



$site->title = htmlconstant('_SETTINGS');
$toggle = isset( $_REQUEST['toggle'] ) ? $_REQUEST['toggle'] : null;
$site->pageStart( array( 'popfit' => $toggle ? 0 : 1) );

$table = isset( $_REQUEST['table'] ) ? $_REQUEST['table'] : null;
if( !Table_Find_Def($table) ) die('bin.php: Bad table given.');
	
if( isset( $_REQUEST['toggle'] ) && $_REQUEST['toggle'] ) 
{
    $reqList = isset($_REQUEST['list']) ? $_REQUEST['list'] : null;
    
	// collect the IDs to add/remove
	$ids = array();
	if( isset($what) && $what == 'inview' ) {
		$printSelectedSql = '';
		if( isset($_SESSION['g_session_index_sql'][$table]) ) {
			$printSelectedSql = $_SESSION['g_session_index_sql'][$table];
		}		
		$printViewOffset	= intval(regGet("index.view.{$table}.offset", 0));
		$printViewRows		= intval(regGet("index.view.{$table}.rows", 10));
		$printViewSql		= "$printSelectedSql LIMIT $printViewOffset,$printViewRows";
		$db = new DB_Admin;
		$db->query($printViewSql);
		$action = 'remove';
		while( $db->next_record() ) { 
			$id = intval($db->f('id'));
			$ids[$id] = 1;
			if( !isset($_SESSION['g_session_bin']) || !$_SESSION['g_session_bin']->recordExists($table, $id, $reqList) ) {
				$action = 'add';
			}
		}		
		//echo $action;
		//print_r($ids);
	}
	else {
		$ids    = array($_REQUEST['id'] => 1);
		$reqID  = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
		$action = isset($_SESSION['g_session_bin']) && $_SESSION['g_session_bin']->recordExists($table, $reqID, $reqList) ? 'remove' : 'add';
	}
	
	// add/remove all given IDs
	reset($ids);
	foreach($ids as $id => $dummy) {
	    if( isset($action) && $action == 'add' && isset($_SESSION['g_session_bin']) )  {
		    $_SESSION['g_session_bin']->recordAdd($table, $id, $reqList);
		}
		else if( isset($action) && $action == 'remove' && isset($_SESSION['g_session_bin']) ) {
		    $_SESSION['g_session_bin']->recordDelete($table, $id, $reqList);
		}
	}
	
	render_bin_overview();
	
	if( isset($_SESSION['g_session_bin']) && $reqList == $_SESSION['g_session_bin']->activeBin ) {
		$_SESSION['g_session_bin']->updateOpener();
	}
}
else 
{
	render_bin_overview();
}

$site->pageEnd();