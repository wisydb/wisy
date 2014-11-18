<?php


/*=============================================================================
Synchronisation
===============================================================================

This file should be called regularly (eg. every hour) to perform CRON jobs.

file:	
	cron.php
	
author:	
	Bjoern Petersen

parameters:
	apikey		the apikey as defined at export.apikey
	
=============================================================================*/


define('G_SKIP_LOGIN', 1); // skip the normal login, instead perform a check of export.apikey in handle_request()
require('functions.inc.php');

$GLOBALS['g_cron'] = new G_CRON_CLASS;
$GLOBALS['g_cron']->handle_request();

