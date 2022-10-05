<?php

/*=============================================================================
Overview for all Tables
===============================================================================

file:	
	etc.php
	
author:	
	Bjoern Petersen

parameters:
	table		-	expand the given primary table
	secondary	-	expand the given secondary table

=============================================================================*/



//
// needed includes, connect to database
//
require('functions.inc.php');
require_lang('lang/overview');
$db = new DB_Admin;





//
// start page
//

$site->title = htmlconstant('_ETC');
$site->pageStart();

$site->menuSettingsUrl	= 'settings.php?reload=' . urlencode('etc.php?dummy=dummy');
$site->menuHelpScope	= 'ioverviewtables';
$site->menuLogoutUrl	= 'etc.php';
$site->menuOut();


//
// table overview
//


function table_start()
{
	echo '<table>';
}



function table_end()
{
	echo '</table>';
}



function table_item($level = 3)
{
	$width = array(2, 13, 37, 61); 
	echo '<tr><td style="vertical-align: top;"><img src="skins/default/img/treei'.$level.'.gif" width="'.$width[$level].'" height="13" alt="" /></td><td style="vertical-align: top;">';
}



function table_item_break()
{
	echo '</td><td style="vertical-align: top;">';
}



function table_item_end()
{
	echo '</td></tr>';
}



function table_type($t)
{
	global $Table_Def;
	
	$type = '';
	$is_primary = 0;
	$is_attr = 0;
	
	if( $Table_Def[$t]->flags & TABLE_PRIMARY )
	{
		$type .= htmlconstant('_OVERVIEW_PRIMARYDATA');
		$is_primary = 1;
	}
	
	$curr_type = '';
	for( $t2 = 0; $t2 < sizeof($Table_Def); $t2++ )
	{
		for( $r2 = 0; $r2 < sizeof($Table_Def[$t2]->rows); $r2++ )
		{
			if( (($Table_Def[$t2]->rows[$r2]->flags & TABLE_ROW) == TABLE_SECONDARY)
			 && $Table_Def[$t2]->rows[$r2]->addparam->name == $Table_Def[$t]->name )
			{
				$curr_type .= $curr_type? ', ' : (htmlconstant('_OVERVIEW_SECONDARYDATAFOR').' ');
				$curr_type .= '<a href="index.php?table=' .$Table_Def[$t2]->name. '">' . $Table_Def[$t2]->descr . '</a>';
			}
		}
	}
	
	if( $curr_type ) 
	{
		if( $type )
			$type .= '; ';
		$type .= $curr_type;
	}

	$curr_type = '';
	for( $t2 = 0; $t2 < sizeof($Table_Def); $t2++ )
	{
		for( $r2 = 0; $r2 < sizeof($Table_Def[$t2]->rows); $r2++ )
		{
			if( ((($Table_Def[$t2]->rows[$r2]->flags & TABLE_ROW) == TABLE_MATTR) || (($Table_Def[$t2]->rows[$r2]->flags & TABLE_ROW) == TABLE_SATTR))
			 && $Table_Def[$t2]->rows[$r2]->addparam->name == $Table_Def[$t]->name )
			{
				$curr_type .= $curr_type? ', ' : (htmlconstant('_OVERVIEW_ATTRFOR').' ');
				$curr_type .= '<a href="index.php?table=' .$Table_Def[$t2]->name. '">' . $Table_Def[$t2]->descr . '.' . $Table_Def[$t2]->rows[$r2]->descr . '</a>';
				$is_attr = 1;
			}
		}
	}
	
	if( $curr_type ) {
		if( $type )
			$type .= '; ';
		$type .= $curr_type;
	}
	
	if( !$type ) {
		$type = htmlconstant('_OVERVIEW_PRIMARYDATA');
	}
	

	return $type;
}



function table_details($level, $t, $table, $curr_access, $curr_last_modification)
{
	global $db;
	global $Table_Def;
	
	table_item($level);
		echo htmlconstant('_OVERVIEW_TYPE') . ':';
	table_item_break();
		echo table_type($t);
	table_item_end();

	
	table_item($level);
		echo htmlconstant('_ENTRIES') . ':';
	table_item_break();
		$db->query('SELECT COUNT(*) FROM ' . $table);
		if( $db->next_record() )  {
			$entries = $db->f("COUNT(*)");
		}
		else {
			$entries = 0;
		}
		echo $entries;
	table_item_end();

	table_item($level);
		echo htmlconstant('_REF') . ':';
	table_item_break();
		echo $Table_Def[$t]->num_references(-1, $dummy);
	table_item_end();

	table_item($level);
		echo htmlconstant('_OVERVIEW_RIGHTS') . ':';
	table_item_break();
		echo "<a href=\"user_access_view.php?table=$table\" target=\"_blank\" rel=\"noopener noreferrer\">";
			echo acl_get_readable_str($curr_access);
		echo '</a>';
	table_item_end();
	
	table_item($level);
		echo htmlconstant('_OVERVIEW_LASTMODIFICATION') . ':';
	table_item_break();
	echo isohtmlentities( strval( sql_date_to_human($curr_last_modification, 'datetime') ) );
	table_item_end();
	
	for( $r = 0; $r < sizeof($Table_Def[$t]->rows); $r++ )
	{
		$rowflags	= $Table_Def[$t]->rows[$r]->flags;
		$rowtype	= $rowflags & TABLE_ROW;
		if( $rowtype == TABLE_SECONDARY )
		{
			$curr_sec_table	= $Table_Def[$t]->rows[$r]->addparam->name;
			$curr_access	= acl_get_access($curr_sec_table.'.COMMON');
			$is_selected	= (isset($_REQUEST['secondary']) ? $_REQUEST['secondary'] : null) == $curr_sec_table ? 1 : 0;
			
			if( $curr_access )
			{
				echo '<tr><td>';
				
					echo '<a href="'. ($is_selected? "etc.php?table=$table" : "etc.php?table=$table&amp;secondary=$curr_sec_table") . '">';
						echo "<img src=\"skins/default/img/tree" .($is_selected? 'c' : 'e'). "2.gif\" width=\"37\" height=\"13\" alt=\"[+]\" title=\"\" />";
					echo '</a>';
				
				echo '</td><td colspan="2">';
				
					echo "<a href=\"index.php?table=$curr_sec_table\">";
						echo $is_selected? '<b>' : '';
							echo $Table_Def[$t]->rows[$r]->addparam->descr;
						echo $is_selected? '</b>' : '';
					echo '</a>';
					
				echo '</td></tr>';	
				
				if( $is_selected )
				{
					for( $t2 = 0; $t2 < sizeof($Table_Def); $t2++ ) {
						if( $Table_Def[$t2]->name == $curr_sec_table ) {
							break;
						}
					}

					$curr_last_modification = '0000-00-00 00:00:00';
					$db->query('SELECT MAX(date_modified) FROM ' . $curr_sec_table);
					if( $db->next_record() ) {
						$curr_last_modification = $db->f('MAX(date_modified)');
					}

					table_end();
					table_start();
					table_details(3, $t2, $curr_sec_table, $curr_access, $curr_last_modification);
					table_end();
					table_start();
				}
			}
		}
	}
}



$site->skin->workspaceStart();
	table_start();
	
		// init last monification
		$last_modification = '0000-00-00 00:00:00';
		$last_modification_table = '';
		$last_modification_user = 0;
		
		for( $t = 0; $t < sizeof((array) $Table_Def); $t++ )
		{
			// last monification
			$curr_last_modification = '0000-00-00 00:00:00';
			$db->query('SELECT MAX(date_modified) FROM ' . $Table_Def[$t]->name);
			if( $db->next_record() ) {
				$curr_last_modification = $db->f('MAX(date_modified)');
				if( $curr_last_modification && $curr_last_modification!='0000-00-00 00:00:00' ) {
					if( $curr_last_modification > $last_modification ) {
						$last_modification = $curr_last_modification;
						$last_modification_table = $Table_Def[$t]->descr;
						$db->query("SELECT user_modified FROM {$Table_Def[$t]->name} WHERE date_modified='$curr_last_modification'");
						if( $db->next_record() ) {
							$last_modification_user = intval($db->f('user_modified'));
						}
					}
				}
			}
		
			// render table 
			$curr_access = acl_get_access($Table_Def[$t]->name.'.COMMON');
			$is_only_secondary = $Table_Def[$t]->is_only_secondary($dummy, $dummy);
			$is_selected = $Table_Def[$t]->name == isset( $_REQUEST['table'] ) && $_REQUEST['table'] ? 1 : 0;
			
			if( $curr_access && !$is_only_secondary ) 
			{
				echo '<tr><td>';
				
					echo '<a href="'. ($is_selected? "etc.php" : "etc.php?table={$Table_Def[$t]->name}") . '">';
						echo "<img src=\"skins/default/img/tree" .($is_selected? 'c' : 'e'). "1.gif\" width=\"13\" height=\"13\" alt=\"[+]\" title=\"\" />";
					echo '</a>';
				
				echo '</td><td>';
				
					echo "<a href=\"index.php?table={$Table_Def[$t]->name}\">";
						echo $is_selected? '<b>' : '';
							echo $Table_Def[$t]->descr;
						echo $is_selected? '</b>' : '';
					echo '</a>';
					
				echo '</td></tr>';	
				
				if( $is_selected )
				{
					table_end();
					table_start();
					table_details(2, $t, $Table_Def[$t]->name, $curr_access, $curr_last_modification);
					table_end();
					table_start();
				}
			}
		}

	table_end();

	// import / export / synchronize
	$can_import = acl_check_access('SYSTEM.IMPORT', -1, ACL_EDIT);
	$can_export = acl_check_access('SYSTEM.EXPORT', -1, ACL_READ);
	if( $can_import || $can_export )
	{
		echo '<br />';
		table_start();
			if( $can_export ) {
				table_item(1);
					echo '<a href="exp.php">'.htmlconstant('_EXPORT').'</a>';
				table_item_end();
			}

			if( $can_import ) {
				table_item(1);
					echo '<a href="imp.php">'.htmlconstant('_IMPORT').'</a>';
				table_item_end();
			}

			if( $can_import && $can_export ) {
				table_item(1);
					echo '<a href="sync.php">'.htmlconstant('_SYNC').'</a>';
				table_item_end();
			}
		table_end();
	}

	
	// localize
	if( acl_get_access('SYSTEM.LOCALIZELANG') 
	 || acl_get_access('SYSTEM.LOCALIZEENTRIES') )
	{
		echo '<br />';
		table_start();
			table_item(1);
				echo '<a href="sysloc.php">'.htmlconstant('_SYSLOC').'</a>';
			table_item_end();
		table_end();
	}

		
	
$site->skin->workspaceEnd();





//
// end page
//

// last modification
$site->skin->fixedFooterStart();
	$site->skin->submenuStart();
		echo htmlconstant('_OVERVIEW_SYSTEMTIME',
		    isohtmlentities( strval( sql_date_to_human(ftime("%Y-%m-%d %H:%M:%S"), 'absolute datetime')) ) );
	$site->skin->submenuBreak();
		echo htmlconstant('_OVERVIEW_LASTMODDETAILS',
		    isohtmlentities( strval( sql_date_to_human($last_modification, 'datetime') ) ),
			$last_modification_table,
			user_html_name($last_modification_user));
		echo " | <a href=\"log.php\" target=\"_blank\" rel=\"noopener noreferrer\">" . htmlconstant('_LOG') . '</a>';
	$site->skin->submenuEnd();
$site->skin->fixedFooterEnd();

$site->pageEnd();



?>