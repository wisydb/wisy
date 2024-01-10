<?php

/******************************************************************************
WISY Autocomplete
***************************************************************************//**

Autocomplete, Server part. 

Parameters for the JSON callback (this method is used to create the suggestion 
list):

- `acdata` - the main scope as `<table>.<field>`, may be `<table>.seeselect`

- `select` - The selected field as `<field>[.<subfield>[.<subsubfield>]]` if 
			 `seeselect` is used above. `<subfield>` and `<subsubfield>` are 
			 only used if `<field>` refers to an attribute table
			 
- `term` - the user's input


Parameters for forwarding to indexlist/edit (this method is used for the little
arrows beside the suggested values):

- `acdata` - base field and table as "<table>.<field>"
	
- `v0`, `v1`, `v2`, ...	- The values; we need several one as autocomplete may
			handle nested fields - value #0, value #1, value #2 etc. represent 
			each a single field marked by TABLE_ACNEST then


@author Bjoern Petersen, http://b44t.com

******************************************************************************/


require_once("WisyKi/wisykistart.php");
if( isset($_REQUEST['term']) )
{
	// the JSON callback
	require_once('functions.inc.php'); // this will also make the file uncacheable, maybe we should implement a little cache of a few seconds?
	$ob = new AUTOCOMPLETE_JSON_CLASS;
	$ob->handle_request();
}
else if( isset($_REQUEST['v0']) )
{
	// parameters for forwarding to indexlist/edit
	require_once('functions.inc.php');
	$ob = new AUTOCOMPLETE_DETAILS_CLASS;
	$ob->handle_request();
}
else
{
	die('bad input');
}

