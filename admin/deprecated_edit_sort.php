<?php

/*=============================================================================
Change the order of multiple Attributes in an edit object
===============================================================================

file:	
	deprecated_edit_sort.php
	
author:	
	Bjoern Petersen

parameters:
	object			- the object currently in edit, will be modified and 
	                  forwarded back to edit.php
	edit_control	- the control to edit, also used as position anchor when 
					  going back to 'edit.php'
subsequent parameters:
	sort_index
	sort_dir

=============================================================================*/




//
// includes
//


require_once('deprecated_edit_class.php');		// must be included BEFORE the session is
									// started in functions.inc.php as the edit
									// instances may be stored in the session
require_once('functions.inc.php');
require_lang('lang/edit');


//
// authorized?
//



if(    !isset( $_REQUEST['object'] ) 
    || !isset( $_SESSION[$_REQUEST['object']] ) 
    || !is_object($_SESSION[$_REQUEST['object']]) ) {
	$site->abort(__FILE__, __LINE__);
	exit(); // no access to this table
}

// $_REQUEST['object'] existance guaranteed

$table_def = isset($_REQUEST['object']) && isset($_SESSION[$_REQUEST['object']]) ? Table_Find_Def($_SESSION[$_REQUEST['object']]->table_def_name) : '';

if( !$table_def ) {
	$site->abort(__FILE__, __LINE__);
	exit(); // no access to this table
}

if( isset( $_REQUEST['edit_control'] ) && $_SESSION[$_REQUEST['object']]->getset_attrtable($_REQUEST['edit_control'], $attr_values, $attr_table_def_name, $dummy, $attr_name) != -1 ) {
	$site->abort(__FILE__, __LINE__);
	exit(); // no access to this table
}

$attr_table_def = Table_Find_Def($attr_table_def_name);
if( !$attr_table_def ) {
	$site->abort(__FILE__, __LINE__);
	exit(); // no access to this table
}


//
// handle submit
//
$sort_index = isset( $_REQUEST['sort_index'] ) ? $_REQUEST['sort_index'] : null;
$sort_dir = isset( $_REQUEST['sort_dir'] ) ? $_REQUEST['sort_dir'] : null;
if( isset($sort_index) && isset($sort_dir) ) 
{
	if( $sort_dir=='up' || $sort_dir=='down' ) 
	{
		// handle up / down
		$swap_index_a = $sort_index;
		$swap_index_b = $sort_index + ($sort_dir=='up'?-1:1);
		
		if( $swap_index_a >= 0 && $swap_index_a < sizeof((array) $attr_values)
		 && $swap_index_b >= 0 && $swap_index_b < sizeof((array) $attr_values) )
		{
			$temp						= $attr_values[$swap_index_a];
			$attr_values[$swap_index_a]	= $attr_values[$swap_index_b];
			$attr_values[$swap_index_b] = $temp;
			
			if( isset( $_REQUEST['edit_control'] ) && isset( $_SESSION[$_REQUEST['object']] ) )
			 $_SESSION[$_REQUEST['object']]->getset_attrtable($_REQUEST['edit_control'], $attr_values, $attr_table_def_name, $dummy, $attr_name, 1 /*set*/);
		}
	}
	else if( ($sort_dir=='top' || $sort_dir=='bottom') 
		  && $sort_index >= 0
		  && $sort_index <  sizeof((array) $attr_values) )
	{
		// handle top / bottom
		$temp = $attr_values[$sort_index];
		array_splice($attr_values, $sort_index, 1);
		if( $sort_dir=='bottom' ) {
			$attr_values[] = $temp;
		}
		else {
			array_unshift($attr_values, $temp);
		}
		
		if( isset( $_REQUEST['edit_control'] ) && isset( $_SESSION[$_REQUEST['object']] ) )
		  $_SESSION[$_REQUEST['object']]->getset_attrtable($_REQUEST['edit_control'], $attr_values, $attr_table_def_name, $dummy, $attr_name, 1 /*set*/);
	}
}




//
// render page
//



// start page
$site->title = $table_def->descr . ' - ' . htmlconstant('_EDIT_EDITORDERTITLE', $attr_table_def->descr);
$site->pageStart();

$site->menuItem('mmainmenu', $table_def->descr, "<a href=\"index.php?table={$table_def->name}\">");
$site->menuHelpScope	= 'ieditattrselection';
$site->menuSettingsUrl	= 'settings.php?reload=' . urlencode("deprecated_edit_sort.php?object=".(isset($_REQUEST['object']) ? $_REQUEST['object'] : '')."&edit_control=".(isset( $_REQUEST['edit_control'] ) ? $_REQUEST['edit_control'] : ''));
$site->menuLogoutUrl	= 'edit.php?table='.$table_def->name . '&id=' . ( isset($_REQUEST['object']) && isset($_SESSION[$_REQUEST['object']]) ? $_SESSION[$_REQUEST['object']]->id : '' );
$site->menuFreeObject	= isset($_REQUEST['object']) ? $_REQUEST['object'] : null;
$site->menuOut();


// render sort images
$attrimgsize = GetImageSize("{$site->skin->imgFolder}/areaup.gif");
function render_sort_icon($img, $alt, $index, $disabled)
{
	global $site;
	global $attrimgsize;
	
	echo '<td nowrap="nowrap">';
		if( $disabled ) {
			echo '&nbsp;';
		}
		else {
		    if( isset( $_REQUEST['edit_control'] ) )
		        echo '<a href="deprecated_edit_sort.php?object=' . ( isset($_REQUEST['object']) ? $_REQUEST['object'] : '' )
		        . '&edit_control=' . ( isset($_REQUEST['edit_control']) ? $_REQUEST['edit_control'] : '' )
		        . '&sort_index=' .$index. '&sort_dir=' .$img. '" title="'.$alt.'">&nbsp;';
			
			switch( $img )
			{
				case 'top':		echo '&lt;&lt;';	break;
				case 'up':		echo '&lt;';		break;
				case 'down':	echo '&gt;';		break;
				case 'bottom':	echo '&gt;&gt;';	break;
			}
			
			echo '&nbsp;</a>';
		}
	echo '</td>';
	
	return;
	
	if( $disabled ) {
		echo "<img src=\"skins/default/img/1x1.gif\" width=\"{$attrimgsize[0]}\" height=\"{$attrimgsize[1]}\" border=\"0\" alt=\"\" />";
	}
	else {
	    if( isset( $_REQUEST['edit_control'] ) )
	        echo '<a href="deprecated_edit_sort.php?object=' . ( isset($_REQUEST['object']) ? $_REQUEST['object'] : '' )
	           . '&edit_control=' . ( isset($_REQUEST['edit_control']) ? $_REQUEST['edit_control'] : '' )
	           . '&sort_index=' .$index. '&sort_dir=' .$img. '">';
		
			$repeat = 1;
			switch( $img )
			{
				case 'top':
					$repeat = 2;
				case 'up':
					$img = 'areaup';
					break;
			}
			
			for( $i = 0; $i < $repeat; $i++ )
			echo "<img src=\"{$site->skin->imgFolder}/{$img}.gif\" onmouseover=\"rollI(this);\" width=\"{$attrimgsize[0]}\" height=\"{$attrimgsize[1]}\" border=\"0\" alt=\"$alt\" />";
			
		echo '</a>';
	}
}


// order form
$site->skin->dialogStart();

	form_control_start($attr_name, 0);

		echo '<table cellpadding="0" cellspacing="0" border="0">';
			for( $a = 0; $a < sizeof((array) $attr_values); $a++ )
			{
				echo '<tr>';
					// sort icons
					if( sizeof((array) $attr_values) > 2 ) {
					  render_sort_icon('top',	htmlconstant('_EDIT_SORTTOP'),		$a, $a==0);
					}
					render_sort_icon('up',		htmlconstant('_EDIT_SORTUP'),		$a, $a==0);
					render_sort_icon('down',	htmlconstant('_EDIT_SORTDOWN'),		$a, $a==(sizeof((array) $attr_values)-1));
					if( sizeof((array) $attr_values) > 2 ) {
					  render_sort_icon('bottom',htmlconstant('_EDIT_SORTBOTTOM'),	$a, $a==(sizeof((array) $attr_values)-1));
					}
					
					// attribute name
					echo '<td valign="top">';
					   echo '<a href="edit.php?table=' . ( isset( $attr_table_def->name ) ? $attr_table_def->name : '' ) . '&id=' . $attr_values[$a] . '" target="_blank" rel="noopener noreferrer">';
						  echo isohtmlentities( strval( $attr_table_def->get_summary($attr_values[$a], ' / '/*value seperator*/) ) );
						echo '</a>';
					echo '</td>';
				echo '</tr>';
			}
		echo '</table>';

	form_control_end();

$site->skin->dialogEnd();
		
$site->skin->buttonsStart();
    if( isset($_REQUEST['object']) && isset( $_REQUEST['edit_control'] ) )
	   form_clickbutton("edit.php?object=" . $_REQUEST['object'] . "#c".$_REQUEST['edit_control'], htmlconstant('_OK'));
$site->skin->buttonsEnd();



// end page
$site->pageEnd();

?>