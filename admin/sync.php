<?php


/*=============================================================================
Synchronisation
===============================================================================

file:	
	sync.php
	
author:	
	Bjoern Petersen

parameters:
	n/a
	
=============================================================================*/





require('functions.inc.php');



switch( $_REQUEST['page'] )
{	
	case 'deletejob':	$ob = new SYNC_DELETEJOBRENDERER_CLASS	(intval($_REQUEST['jobid']));	break;
	case 'editjob':		$ob = new SYNC_EDITJOBRENDERER_CLASS	(intval($_REQUEST['jobid']));	break;
	default:			$ob = new SYNC_OVERVIEWRENDERER_CLASS	();								break;
}

$ob->handle_request();