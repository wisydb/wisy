<?php

/*=============================================================================
Load a Module from the 'config' directory;
===============================================================================

file:	
	module.php
	
author:	
	Bjoern Petersen

parameters:
	module	-	the module to load, without path and extension
	
	all other parameters can be used by the module itself;
	this file is needed to make the relative paths work

=============================================================================*/



$module = isset($_REQUEST['module']) ? $_REQUEST['module'] : '';
if( !preg_match('/^[a-z0-9_\.\-]+$/i', $module) )
	die('bad module.');


require('config/' . $module . '.inc.php');