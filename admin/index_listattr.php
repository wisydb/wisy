<?php


/*=============================================================================
the search formular class - list attributes in a popup window
===============================================================================

file:	
	index_listattr.php
	
author:	
	Bjoern Petersen

parameters:
	table
	scope		-	"column" or undefined for search fields

=============================================================================*/




/*=============================================================================
render options for fields or columns
=============================================================================*/



function load_options(	$tableDefName, 
						$scope,
						&$fieldNames,
						&$fieldDescr,
						&$fieldIsDefault,
						&$fieldIsSelected,
						&$fieldIndent		)
{
	$ob = new DBSEARCH_FORM_CLASS($tableDefName,  
		array (
				'fields'  => regGet("index.searchfields.{$tableDefName}", ''),
				'columns' => regGet("index.view.{$tableDefName}.columns", '')
			  )
		);
	
	if( $scope == 'columns' ) {
		$ob->loadColumns($tableDefName);
		$fieldNames		= $ob->columnNames;
		$fieldDescr		= $ob->columnDescr;
		$fieldIsDefault	= $ob->columnIsDefault;
		$fieldIsSelected= $ob->columnIsSelected;
		$fieldIndent	= $ob->columnIndent;
	}
	else {
		$ob->loadFields($tableDefName, 1 /*show all*/);
		$fieldNames		= $ob->fieldNames;
		$fieldDescr		= $ob->fieldDescr;
		$fieldIsDefault	= $ob->fieldIsDefault;
		$fieldIsSelected= $ob->fieldIsSelected;
		$fieldIndent	= $ob->fieldIndent;
	}
}

function render_options($tableDefName, $scope /* '' for fields or 'columns' */)
{
	//
	// get all field options
	//
	
	load_options($tableDefName, $scope, $fieldNames, $fieldDescr, $fieldIsDefault, $fieldIsSelected, $fieldIndent);
	
	$tableDef = Table_Find_Def($tableDefName);
	

	//
	// store settings using regSet()
	//
	if( isset($_REQUEST['ok']) )
	{
		// get all settings into an associative array
		$allDefaults = 1;
		$settings = array();
		for( $i = 0; $i < sizeof($fieldNames); $i++ )
		{
			if( $_REQUEST["c$i"] && $fieldNames[$i]!='adummyfield' ) {
				$settings[$fieldNames[$i]] = 1;
				if( !$fieldIsDefault[$i] ) $allDefaults = 0;
			}
			else {
				if( $fieldIsDefault[$i] ) $allDefaults = 0;
			}
		}

		// make sure, parent functions are also selected
		$temp = $settings;
		reset($temp);
		while( list($name, $value) = each($temp) ) {
			while( $p=strrpos($name, '.') ) {
				$name = substr($name, 0, $p);
				$settings[$name] = 1;
			}
		}

		// empty array if the new settings are equal to the default settings
		if( $allDefaults ) {
			$settings = array();
		}
		
		// create string from array
		$settingsString = '';
		reset($settings);
		while( list($name, $value) = each($settings) ) {
			$settingsString .= $settingsString? ', ' : '';
			$settingsString .= $name;
		}
		
		// save settings to user's registry	
		if( $scope == 'columns' ) {
			regSet("index.view.{$tableDef->name}.columns", $settingsString, '');
		}
		else {
			regSet("index.searchfields.{$tableDef->name}", $settingsString, '');
		}
		$anythingChanged = regSave();
		
		// close window
		$reload = "index.php?table={$tableDef->name}";
		echo '<html><head><title></title></head><body onload="';
			if( $anythingChanged )
			{
				if( $_REQUEST['reloadparentsparent'] ) 
				{
					echo	'if( window.opener && !window.opener.closed && window.opener.opener && !window.opener.opener.closed ) {';
					echo 		"window.opener.opener.location.href='$reload';";
					echo	'}';
				}
				else
				{
					echo	'if( window.opener && !window.opener.closed ) {';
					echo 		"window.opener.location.href='$reload';";
					echo	'}';
				}
			}
			echo			'window.close();';
		echo '"></body></html>';
		return;
	}
	
	//
	// render page
	//
	global $site;
	
	$site->title = htmlconstant('_SETTINGS_DIALOGTITLE');
	$site->pageStart(array('popfit'=>1));
	form_tag('fieldopt_form', 'index_listattr.php');
	
	$site->skin->submenuStart();
		echo htmlconstant($scope=='columns'? '_MOD_DBSEARCH_COLUMNOPTIONSFOR' : '_MOD_DBSEARCH_OPTIONSFOR', htmlconstant($tableDef->descr));
	$site->skin->submenuBreak();
		$site->menuHelpEntry($scope=='columns'? 'isettingscolumns' : 'isettingsfields');
	$site->skin->submenuEnd();

	$site->skin->workspaceStart();
	
		form_hidden('table',				$tableDef->name);
		form_hidden('reloadparentsparent',	$_REQUEST['reloadparentsparent']? 1 : 0);
		form_hidden('scope',				$scope);

		echo '<table cellpadding="0" cellspacing="0" border="0" title="' .htmlconstant($scope=='columns'? '_MOD_DBSEARCH_COLUMNOPTIONSHINT' : '_MOD_DBSEARCH_OPTIONSHINT', htmlconstant($tableDef->descr)). '"><tr><td nowrap="nowrap">';

			// prepare data
			$startEnd = array();
			for( $i = 0; $i < sizeof($fieldNames); $i++ )
			{
				if( $fieldIndent[$i] < $fieldIndent[$i+1] ) {
					$startEnd[$i] = -1;
				}
				else if( $fieldIndent[$i] > $fieldIndent[$i+1] ) {
					$startEnd[$i] = $fieldIndent[$i]-$fieldIndent[$i+1];
				}
				else {
					$startEnd[$i] = 0;
				}
			}

			// render
			for( $i = 0; $i < sizeof($fieldNames); $i++ )
			{
				// indent and expand/collapse tree
				$checkbox = '';
				if( $startEnd[$i] == -1 ) 
				{
					if( $fieldIndent[$i] ) {
						$checkbox .= '<img src="skins/default/img/1x1.gif" width="' .(24*$fieldIndent[$i]). '" height="13" />';
					}

					$checkbox .= "<img name=\"pm$i\" style=\"cursor:pointer;\" onclick=\"if(this.expanded){this.expanded=0;this.src='skins/default/img/treee1.gif';}else{this.expanded=1;this.src='skins/default/img/treec1.gif';}sectd('tree$i',2);return false\" src=\"skins/default/img/treee1.gif\" width=\"13\" height=\"13\" border=\"0\" alt=\"[+]\" title=\"\" />";
				}
				else 
				{
					$checkbox .= '<img src="skins/default/img/1x1.gif" width="' .(13+24*$fieldIndent[$i]). '" height="13" />';
				}
				
				// checkbox
				if( $fieldNames[$i]=='adummyfield' ) 
				{
					$checkbox .= '&nbsp;';
				}
				else
				{
					$checkbox .= "<input type=\"checkbox\" name=\"c$i\" id=\"i{$i}\" value=\"1\"";
					if( $fieldIsSelected[$i] /*|| $fieldIsDefault[$i]==2*/ ) {
						$checkbox .= ' checked="checked"';
					}
					if( $fieldIsDefault[$i]==2 )
					{
						$checkbox .= ' disabled="disabled"';
					}
					$checkbox .= ' />';
				}
				
				
				// label / descr
				$checkbox .= $fieldNames[$i]=='adummyfield'? '' : "<label for=\"i$i\">";
				$checkbox .= $fieldDescr[$i];
				$checkbox .= $fieldNames[$i]=='adummyfield'? '<br />' : "</label><br />";
				
				// start / end <div>
				if( $startEnd[$i] == -1 ) {
					$checkbox .= "<div id=\"tree$i\" style=\"display:none;\">";
				}
				else if( $startEnd[$i] >= 1 ) {
					for( $j = 0; $j < $startEnd[$i]; $j++ ) {
						$checkbox .= '</div>';
					}
				}
				
				echo $checkbox;
			}

		echo '</td></tr></table>';

	$site->skin->workspaceEnd();
	
	$site->skin->buttonsStart();
		form_button('ok', htmlconstant('_OK'), '');
		form_button('cancel', htmlconstant('_CANCEL'), 'window.close();return false;');
	$site->skin->buttonsEnd();

	echo '</form>';
	$site->pageEnd();
}


/*=============================================================================
main part
=============================================================================*/



require('functions.inc.php');
require('index_form.inc.php');

require_lang('lang/dbsearch');
require_lang('lang/overview');
require_lang('lang/settings');

render_options($_REQUEST['table'], $_REQUEST['scope']);

