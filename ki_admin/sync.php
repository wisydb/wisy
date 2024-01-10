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

$page = isset( $_REQUEST['page'] )  ? $_REQUEST['page'] : null;

switch( $page )
{	
	case 'deletejob':
	   $jobid = isset( $_REQUEST['jobid'] ) ? $_REQUEST['jobid'] : null;
	   $ob = new SYNC_DELETEJOBRENDERER_CLASS	(intval($jobid));	
	   break;
	case 'editjob':		
	    $jobid = isset( $_REQUEST['jobid'] ) ? $_REQUEST['jobid'] : null;
	    $ob = new SYNC_EDITJOBRENDERER_CLASS	(intval($jobid));	
	    break;
	default:
	    $ob = new SYNC_OVERVIEWRENDERER_CLASS	();								
	    break;
}

$ob->handle_request();