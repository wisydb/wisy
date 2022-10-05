<?php

/*=============================================================================
Edit or View a Record
===============================================================================

file:	
	edit.php
	
author:	
	Bjoern Petersen

parameters on first call:
	table		- the table to edit
          
	id			- the record to edit - if -1 or unset, a new record will be 
				  created
	nodefaults	- the new record will be created empty - otherwise, the 
				  defaults are used

parameters on subsequent calls:
	object		- the name of the object which holds all data to access
				  the data, use $_SESSION[ $_REQUEST['object'] ]

=============================================================================*/





require_once('deprecated_edit_class.php');		// must be included BEFORE the session is
									// started in functions.inc.php as the edit
									// instances may be stored in the session

require_once('functions.inc.php');

require_lang('lang/edit');

 
//
// get or create object - the table instance
//
$ob_reqname = isset( $_REQUEST['object'] )  ? $_REQUEST['object'] : null;
$table = isset( $_REQUEST['table'] )        ? $_REQUEST['table'] : null;
$id = isset($_REQUEST['id'])                ? $_REQUEST['id'] : -1;
$nodefaults = isset( $_REQUEST['nodefaults'] ) && $_REQUEST['nodefaults'] ? 0 : 1; /* 1 = use defaults */

if( !isset($_REQUEST['object']) || !isset($_SESSION[ $ob_reqname ]) )
{
	// create object
    if( $id > -1 && $table ) {
        $ob_reqname = 'object_'.$table.'_'.$id;
	}
	elseif( $table ) {
	    $ob_reqname = 'object_'.$table.'_new_'.time();
	}
	
	$_SESSION[ $ob_reqname ] = new Table_Inst_Class( $ob_reqname, $table, $id, $nodefaults );
	$_SESSION[ $ob_reqname ]->verify_data(0 /* show alert */);
	
}
else
{
	$_SESSION[ $ob_reqname ]->handle_submit();
}


//
// render whole page
//
if( isset($_SESSION[ $ob_reqname ]) )
    $_SESSION[ $ob_reqname ]->render();