<?php


/*=============================================================================
Import functionality
===============================================================================

file:	
	imp.php
	
author:	
	Bjoern Petersen

parameters:
	page	UI page
	mix
	
=============================================================================*/



require('functions.inc.php');

$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
switch( $page )
{	
    case 'import':	$ob = new IMP_IMPORTRENDERER_CLASS('import',	(isset($_REQUEST['mix']) ? $_REQUEST['mix'] : null) );	break;
    case 'options':	$ob = new IMP_OPTIONSRENDERER_CLASS('options',	(isset($_REQUEST['mix']) ? $_REQUEST['mix'] : null) );	break;
    default:		$ob = new IMP_FILESRENDERER_CLASS('files',		(isset($_REQUEST['mix']) ? $_REQUEST['mix'] : null) );	break;
}

$ob->handle_request();