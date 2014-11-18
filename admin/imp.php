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


switch( $_REQUEST['page'] )
{	
	case 'import':	$ob = new IMP_IMPORTRENDERER_CLASS('import',	$_REQUEST['mix']);	break;
	case 'options':	$ob = new IMP_OPTIONSRENDERER_CLASS('options',	$_REQUEST['mix']);	break;
	default:		$ob = new IMP_FILESRENDERER_CLASS('files',		$_REQUEST['mix']);	break;
}

$ob->handle_request();



