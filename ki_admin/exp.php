<?php


/*=============================================================================
Export overview and starting several export procedures
===============================================================================

file:	
	exp.php
	
author:	
	Bjoern Petersen

parameters:
	page=<format>		-	show the settings page for the given format
	exp=<format>		-	export to the given format, may not be used together with page=
	table				-	the table to export
	q					-	the query to select records
	ui					-	if set to 1:	progress information are shown, after export the file is saved to the files page
							else:			no progress information, file is dumped after export and deleted then
	apikey=<password>   -   only needed, if ui=0; must be the password defined in export.apikey in this case
	.. more defined in object->options
	
example URL for a direct dump:
	http://www.kursportal.local/ki_admin/exp.php?exp=mix&table=kurse&q=modified%3E1.10.2012
	http://www.kursportal.local/ki_admin/exp.php?exp=mix&table=anbieter&q=modified%3Dtoday
	
=============================================================================*/





if( isset($_REQUEST['exp']) && (!isset($_REQUEST['ui']) || intval($_REQUEST['ui'])==0) )
{
	// a dump is requested; force a lazy access check (the apikey password is checked later on)
	define('G_SKIP_LOGIN', 1);
	require('functions.inc.php');
}
else
{
	// normal access check
	require('functions.inc.php');
}


// render the pages
$GLOBALS['g_export_renderer'] = new EXP_EXPORTRENDERER_CLASS; // g_export_renderer is also used form other scopes
$GLOBALS['g_export_renderer']->handle_request();