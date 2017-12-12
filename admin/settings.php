<?php


/*=============================================================================
Edit the user-defined settings
===============================================================================

file:	
	settings.php
	
author:	
	Bjoern Petersen

parameters:	
	table		-	table to change settings for
	scope		-	which settings to change:
					"" (for global), "index", "edit", "syslocedit"
	reload		-	the URL to reload in the opener window on "OK" or "Apply"
	section

subsequent parameters:
	
	edt*
	av*
	fgrp*
	row*
	tb*
	text*
	other*
	pw*
	job*
	
settings are (for values, the first value is the default in most cases):

	addrules.system				<comma seperated list of add rules>
	daily.lastrun				<datetime, read-only>
	daily.log					<text, read-only>
	date.global					<dateSetting>
								"dateSetting" is a comma-separated list with:
								relative, weekdays, century, time, seconds
	edit.field.<table>.<field>.addvalues
								{0|1}							old editor only
	edit.field.<table>.<field>.size
								{40 x 1|<width> x 1}
									or
								{40 x 5|<width> x <height>}		new editor: height only, old editor: both
	edit.oldeditor              {0|1}
								s.a. cookie "oldeditor"
	edit.seperator.<table>		{;|<other seperator>}			old editor only
	edit.textarea.<table>.css	{|monospc|prop}					old editor only
	edit.textarea.<table>.editor{1|0}							old editor only
	edit.textarea.<table>.html	{|pcwebedit|ewebeditpro}		old editor only
	filter.grp					{|<grpID>[, <grpID> [, ...]]}
								"grpID" specify the group IDs that shoud _not_
								be shown, special group ids are
									- 0 for unknown 
									- 999999 for all other groups
	export.<format>.<setting>	<value>
	export.compr				<value>
	export.downloaddays			{14|<other value>}
	index.rowcursor				{1|0} -- no longer supported as completely handled by the CSS
	index.onnoaccess			{|showhint}
	index.select.<table>.offset   \
	index.select.<table>.rows      \ see index.view.<table>.*
	index.select.<table>.orderby   /
	index.view.<table>.columns	{|<colName>[, <colName> [, ...]]}
								special column names are: 
									- REFERENCES
									- SUMMARY
									- BIN
									- VIEW
	index.view.<table>.offset	{0|<offset>}
	index.view.<table>.rows		{10|<rowsPerPage>}
	index.view.<table>.orderby	{date_modified DESC|<colName> [DESC]}
	index.searchfields.<table>	{|<comma separated list of search fields>}
	index.searchplusminusword	{0|1}
	index.searchshowpcode		{1|0}
	index.searchfuzzyinfo2		{1|0}
	login.as					{0|1}
	login.lastpage				{|<last page before logout>}
	login.password.minlen		{4|<otherlen>}
	login.remember				{0|1}
	login.tipsntricks.use       {1|0}  (global settings)
	login.tipsntricks			{1|0}  (user settings)
	login.tipsntricks.position	<lang1>, <pos1>[, <lang2>, <pos2>[, ...]]
	login.userlist				{0|1}
	login.lang					{|<lang list>}
								<lang list> is a comma separated list of 
								language IDs as "de", "en" etc. "auto" is the
								browser-set language. the first language is 
								used as default.
	logo.favicon.url			{|<URL of favicon to use>}
	logo.image.url				{|<URL of logo to use>}
	logo.image.dest.url			{|<URL of destination on logo click>}
	msg.beforelogin				{|<message>}
	msg.afterlogin				{|<message>}
	msg.afterlogout				{|<message>}
	print.fontsize.pt			{10|<other size>}
	print.pagebreak.pages		{0|<after n pages>}
	print.pagebreak.onoff		{0|1}
	print.edit.<table>			{details|list|<other format>}
	print.index.<table>			{list|details|<other format>}
	print.<format>.<setting>	<value>
	settings.editable			{1|0}
								if set, settings are not permanent
	settings.<table>.<scope>.section	
								{0|<other section number>}
	sysloc.edit.size			{40 x 5|<width> x <height>}
	skin.editable				{1|0}
	skin.folder					{skins/default|<other folder>}
	toolbar.bin					{1|0}
	
=============================================================================*/






/******************************************************************************
 * functions
 *****************************************************************************/
 
 
 
function reloadParent($reload, $reloadParentsParent = 0)
{
	echo	"<script type=\"text/javascript\"><!--\n";
	
	if( $reloadParentsParent )
	{
		echo 	"if(window.opener && !window.opener.closed && window.opener.opener && !window.opener.opener.closed){";
		echo		"window.opener.opener.location.href=\"$reload\";";
		echo	"}";
	}
	else
	{
		echo 	"if(window.opener && !window.opener.closed){";
		echo		"window.opener.location.href=\"$reload\";";
		echo	"}";
	}
	
	echo	"/"."/--></script>";
}

function closeAndReloadParent($reload, $reloadParentsParent = 0)
{
	echo	"<html><head><title></title></head><body>";
	if( $reload ) {
		reloadParent($reload, $reloadParentsParent);
	}
	echo	"<script type=\"text/javascript\"><!--\nwindow.close();\n/"."/--></script></body></html>";
	exit();
}

function regGetSize($regKey, &$retWidth, &$retHeight)
{
	$regValue = str_replace(' ', '', regGet($regKey, "$retWidth x $retHeight"));
	list($regWidth, $regHeight) = explode('x', $regValue);

	$regWidth  = intval($regWidth);
	if( $regWidth < 3 ) $regWidth = 40;
	if( $regWidth > 200 ) $regWidth = 200;

	if( $retHeight == 1 ) {
		$regHeight = 1;
	}
	else {
		$regHeight = intval($regHeight);
		if( $regHeight < 3 ) $regHeight = 5;
		if( $regHeight > 99 ) $regHeight = 99;
	}
	
	$retWidth	= $regWidth;
	$retHeight	= $regHeight;
}

function regSetSize($regKey, $newValue, $def = '40 x 5')
{
	if( ($def == '40 x 5' && preg_match('/[^\d]*(\d{1,3})[^\d]+(\d{1,3}).*/', $newValue, $matches))
	 ||	preg_match('/[^\d]*(\d{1,3}).*/', $newValue, $matches) )
	{
		$regWidth = intval($matches[1]);
		if( $regWidth < 3 ) 	$regWidth = 3;
		if( $regWidth > 200 ) 	$regWidth = 200;
		
		$regHeight = intval($matches[2]);
		if( $regHeight < 1 ) 	$regHeight = 1;
		if( $regHeight > 99 )	$regHeight = 99;
		
		regSet($regKey, "$regWidth x $regHeight", $def);
	}
}

function edit_fields_in($currTable)
{
	global $numAddValues;
	global $numText;
	
	$currTableDef = Table_Find_Def($currTable);
	for( $r = 0; $r < sizeof($currTableDef->rows); $r++ )
	{
		$rowflags= intval($currTableDef->rows[$r]->flags);
		$rowtype = $rowflags & TABLE_ROW;
		switch( $rowtype ) 
		{
			case TABLE_TEXT:
				if( $currTableDef->rows[$r]->acl&ACL_EDIT )
				{
					$valueName = "text$numText";
					regSetSize("edit.field.{$currTableDef->name}.{$currTableDef->rows[$r]->name}.size",
						$_REQUEST[$valueName], "40 x 1");
					
					$numText++;
				}
				break;

			case TABLE_TEXTAREA:
				if( $currTableDef->rows[$r]->acl&ACL_EDIT )
				{
					$valueName = "text$numText";
					regSetSize("edit.field.{$currTableDef->name}.{$currTableDef->rows[$r]->name}.size", 
						$_REQUEST[$valueName], "40 x 5");
					
					if( $rowflags & TABLE_HTML ) {
						global $hasHtmlEditor;
						$hasHtmlEditor = 1;
					}
					else {
						global $hasTextarea;
						$hasTextarea = 1;
					}
					
					$numText++;
				}
				break;
				
			case TABLE_MATTR:
			case TABLE_SATTR:
				if( $currTableDef->rows[$r]->acl&ACL_EDIT )
				{
					$valueName = "av$numAddValues";
					regSet("edit.field.{$currTableDef->name}.{$currTableDef->rows[$r]->name}.addvalues", $_REQUEST[$valueName]? 1 : 0, 0); 
					
					$numAddValues++;
				}
				break;
				
			case TABLE_SECONDARY:
				edit_fields_in($currTableDef->rows[$r]->addparam->name);
				break;
		}
	}		
}

function edit_fields_out($currTable)
{
	global $site;
	global $ob;
	global $numText;
	global $namesUsed;
	global $hasAttr;
	
	$currTableDef = Table_Find_Def($currTable);
	for( $r = 0; $r < sizeof($currTableDef->rows); $r++ )
	{
		$rowflags= intval($currTableDef->rows[$r]->flags);
		$rowtype = $rowflags & TABLE_ROW;
		switch( $rowtype )
		{
			case TABLE_TEXT:
				if( $currTableDef->rows[$r]->acl&ACL_EDIT )
				{
					if( $currTableDef->rows[$r]->addparam ) {
						$rules = explode('###', $currTableDef->rows[$r]->addparam);
						if( substr($rules[0], -1)!=' ' ) {
							$numText++;
							break; // the first field contains the mask, a trailing space indicates no max. length
						}
					}
				}
				// fall through
				
			case TABLE_TEXTAREA:
				if( $currTableDef->rows[$r]->acl&ACL_EDIT )
				{
					if( !$ob ) 
					{
						$site->skin->submenuStart();
							echo htmlconstant('_SETTINGS_INPUTFIELDSSIZEHINT') . ' (nur alter Editor)';
						$site->skin->submenuBreak();
							$site->menuHelpEntry('isettingsinput');
						$site->skin->submenuEnd();
						$site->skin->workspaceStart();
						$ob = new COLTABLE_CLASS;
						echo $ob->tableStart(2);
					}
					
					echo $ob->cellStart('nowrap="nowrap" align="right"');
					
					// name
					$descr = trim($currTableDef->rows[$r]->descr);
					if( $namesUsed[$descr] )  {
						$descr = "$descr ($currTableDef->descr)";
					}
					else {
						$namesUsed[$descr] = 1;
					}
					echo "<span class=\"dllcontinue\">$descr:</span>&nbsp;";
					
					// control
					if( $rowtype == TABLE_TEXT ) {
						$width = 40;
						$height = 1;
					}
					else {
						$width = 40;
						$height = 5;

						if( $rowflags&TABLE_HTML ) {
							global $hasHtmlEditor;
							$hasHtmlEditor = 1;
						}
						else {
							global $hasTextarea;
							$hasTextarea = 1;
						}
					}

					regGetSize("edit.field.{$currTableDef->name}.{$currTableDef->rows[$r]->name}.size", $width, $height);
					
					form_control_text("text$numText", "$width x $height", 8 /*width*/, 8 /*maxlen*/);

					$numText++;
				}
				break;
			
			case TABLE_SECONDARY:
				edit_fields_out($currTableDef->rows[$r]->addparam->name);
				break;
			
			case TABLE_MATTR:
			case TABLE_SATTR:
				if( $currTableDef->rows[$r]->acl&ACL_EDIT )
				{
					$hasAttr = 1;
				}
				break;
		}
	}
}

function addvalues_fields_out($currTable)
{
	global $site;
	global $ob;
	global $numAddValues;
	global $namesUsed;
	
	$currTableDef = Table_Find_Def($currTable);
	for( $r = 0; $r < sizeof($currTableDef->rows); $r++ )
	{
		$rowtype = intval($currTableDef->rows[$r]->flags) & TABLE_ROW;
		switch( $rowtype )
		{
			case TABLE_SECONDARY:
				addvalues_fields_out($currTableDef->rows[$r]->addparam->name);
				break;
			
			case TABLE_MATTR:
			case TABLE_SATTR:
				if( $currTableDef->rows[$r]->acl&ACL_EDIT )
				{
					echo $ob->cellStart('nowrap="nowrap"');
						// control
						form_control_check("av$numAddValues", 
							regGet("edit.field.{$currTableDef->name}.{$currTableDef->rows[$r]->name}.addvalues", 0)? 1 : 0, 
							'', 0, 1 /*label*/);
						
						// name
						$descr = $currTableDef->rows[$r]->descr;
						if( $namesUsed[$descr] )  {
							$descr = "$descr ($currTableDef->descr)";
						}
						else {
							$namesUsed[$descr] = 1;
						}
						echo "<label for=\"av$numAddValues\">$descr</label>";
					
					$numAddValues++;
				}
				break;
		}
	}
}

function table_start()
{
	echo '<table cellpadding="3" cellspacing="0" border="0">';
}

function table_end()
{
	echo '</table>';
}

function table_item($level = 3, $width = 61)
{
	echo '<tr><td valign="top"><img src="skins/default/img/treei'.$level.'.gif" width="'.$width.'" height="13" border="0" /></td><td>';
}

function table_item_end()
{
	echo '</td></tr>';
}
 

 
 
/******************************************************************************
 * global part
 *****************************************************************************/

// includes 
require_once('functions.inc.php');
require_once('coltable.inc.php');
require_lang('lang/settings');


// get parameters
$table = $_REQUEST['table'];
$scope = $_REQUEST['scope'];
$section = $_REQUEST['section'];
$settings_ok = $_REQUEST['settings_ok'];
$settings_apply = $_REQUEST['settings_apply'];
$settings_cancel = $_REQUEST['settings_cancel'];
$any_setting_changed = $_REQUEST['any_setting_changed'];
$jobname = $_REQUEST['jobname'];
$jobtable = $_REQUEST['jobtable'];
$pwoldpw = $_REQUEST['pwoldpw'];
$pwnewpw1 = $_REQUEST['pwnewpw1'];
$pwnewpw2 = $_REQUEST['pwnewpw2'];




// get table definition
$table_def = $table? Table_Find_Def($table) : 0;

if( $table_def ) {
	$section_setting = "settings.$table.$scope.section";
}
else if( $scope ) {
	$section_setting = "settings.global.$scope.section";
}
else {
	$section_setting = "settings.global.global.section";
}



// store settings

if( isset($settings_ok) || isset($settings_apply) )
{
	if( $_REQUEST['jobnew'] != '' || $_REQUEST['jobadd'] != '' || $_REQUEST['jobaccess'] != '' )
	{
		// store settings: job-stuff
		
		unset($settings_ok); // avoid close of settings window, store is done later
	}
	else if( ($pwoldpw || $pwnewpw1 || $pwnewpw2) && regGet('settings.editable', 1) )
	{
		// store settings: password
		
		$db = new DB_Admin;
		$db->query("SELECT password FROM user WHERE id=" . $_SESSION['g_session_userid']);
		$db->next_record();
		if( crypt($pwoldpw, $db->fs('password')) == $db->fs('password') )
		{
			if( $pwnewpw1 == $pwnewpw2 ) {
				if( strlen($pwnewpw1) >= regGet('login.password.minlen', 4) ) {
					if( !strpos(' '.$pwnewpw1, '"') && !strpos(' '.$pwnewpw1, "'") ) {
						if( trim($pwnewpw1) == $pwnewpw1 )
						{
							$db->query("UPDATE user SET password='" .addslashes(crypt($pwnewpw1)). "' WHERE id=" . $_SESSION['g_session_userid']);
							closeAndReloadParent('logout.php?logout=pwchanged');
						}
						else
						{
							$pwerr = htmlconstant('_SETTINGS_PWERRSPACES');
						}
					}
					else {
						$pwerr = htmlconstant('_SETTINGS_PWERRQUOTES');
					}
				}
				else {
					$pwerr = htmlconstant('_SETTINGS_PWERRLENGTH', regGet('login.password.minlen', 4));
				}
			}
			else {
				$pwerr = htmlconstant('_SETTINGS_PWERRUNIQUE');
			}
		}
		else {
			$pwerr = htmlconstant('_SETTINGS_PWERRINVALID');
		}
		
		if( $pwerr ) {
			unset($settings_ok); // avoid close of settings window
		}
	}
	else
	{
		// store settings: non-password
		
		if( $table_def && $scope == 'index' )
		{
			// store settings: table: search
			
			regSet('index.searchplusminusword', $_REQUEST['otherallowplusminusword']? 1 : 0, 0);
			regSet('index.searchshowpcode', $_REQUEST['othershowpcode']? 1 : 0, 1);
			regSet('index.searchfuzzyinfo2', $_REQUEST['otherfuzzyinfo']? 1 : 0, 1);
		}
		else if( $table_def && $scope == 'edit' )
		{
			// store settings: table: edit
			
			$numText = 0;
			$numAddValues = 0;
			$hasHtmlEditor = 0;
			$hasTextarea = 0;
			
			edit_fields_in($table);
			
			if( $hasTextarea ) {
				regSet("edit.textarea.$table.css", $_REQUEST['edtcss'], '');
				//regSet("edit.textarea.$table.editor", $_REQUEST['edteditor']? 1 : 0, 1);
			}

			if( $hasHtmlEditor ) {
				regSet("edit.textarea.$table.html", $_REQUEST['edthtml'], '');
			}
			
			$othersep = $_REQUEST['othersep'];
			if( $othersep == 'hash' ) $othersep = '#';
			regSet("edit.seperator.$table", $othersep, ';');
			
			// ...store tab usage
			regSet('edit.rowrider', $_REQUEST['usetabs']? 1 : 0, 1);
			
		}
		else if( $scope == 'syslocedit' )
		{
			// store settings: sysloc: edit

			regSetSize('sysloc.edit.size', $_REQUEST['textsysloc'], '40 x 5');
		}
	
		
		// store settings: filter
	
	
		$allgroups = acl_get_all_groups();
		$allgroups[] = array(0, '');
		$allgroups[] = array(999999, '');
		$filteredgroups = '';
		for( $g = 0; $g < sizeof($allgroups); $g++ ) {
			$groupname = "fgrp{$allgroups[$g][0]}";
			if( !($_REQUEST[$groupname]) ) {
				$filteredgroups .= ($filteredgroups!=''? ', ' : '') . $allgroups[$g][0];
			}
		}
		regSet('filter.grp', $filteredgroups, '');
		
		regSet('index.onnoaccess', $_REQUEST['onnoaccess'], '');
	
		
		// store settings: view
	
	
		// ...store row cursor
		/*
		regSet('index.rowcursor', $_REQUEST['rowcursor']? 1 : 0, 1);
		*/

			// DEPRECATED 13:23 26.09.2013
			$temp = $_POST['neweditor']? 0 : 1; // if we use $_REQUEST[], this would get the setting from the cookie ...
			setcookie('oldeditor', $temp, time()+60*60*24*100);
			$_COOKIE['oldeditor'] = $temp;
			regSet('edit.oldeditor', intval($temp), 0); // just to make the changes visible at once
			// /DEPRECATED 13:23 26.09.2013
		
		
		// ... store bin settings
		$tbbin = $_REQUEST['tbbin']? 1 : 0;
		$tbbinold = regGet('toolbar.bin', 1);
		if( $tbbin != $tbbinold ) {
			regSet('toolbar.bin', $tbbin, 1);
			if( $tbbin ) {
				$section++;
			}
			else {
				$section--;
			}
		}
	
		// ... store tips'n'tricks
		if( regGet('login.tipsntricks.use', 1) ) {
			regSet('login.tipsntricks', $_REQUEST['tbtnt']?1:0, 1);
		}
	
		// ... store skin
		$skn_changed = 0;
		if( regGet('skin.editable', 1) ) {
			$skn_changed = regSet('skin.folder', $_REQUEST['edtskin'], 'skins/default');
		}


		// ...store date settings
		$dateSetting = '';
		$dateSetting .= $_REQUEST['otherrelative']?	'relative '	: 'absolute ';
		$dateSetting .= $_REQUEST['otherweekdays']?	'weekdays '	: '';
		$dateSetting .= $_REQUEST['othercentury']?	'century '	: '';
		
		if ( $_REQUEST['othertime'] ) {
			$dateSetting .= 'time ';
			if( $_REQUEST['otherseconds'] ) {
				$dateSetting .= 'seconds ';
			}
		}
		
		$dateSetting = str_replace(' ', ', ', trim($dateSetting));
		regSet('date.global',	$dateSetting, 'relative, weekdays');
		
		// ...store settings from $g_addsettings_names
		global $g_addsettings_names;
		for( $i = 0; $i < sizeof($g_addsettings_names); $i += 2 )
			regSet($g_addsettings_names[$i+1],  $_REQUEST["addsettings$i"], '');
	
		// store settings: save settings to db

		$any_setting_changed = regSave();
		if( $any_setting_changed ) {
			$_SESSION['g_session_filter_active_hint'] = 0;
		}
		
		if( isset($section) ) {
			regSet($section_setting, $section, 0);
			regSave();
		}
	}
}



// reload opening window and/or close settings window?



if( isset($settings_ok) || isset($settings_cancel) ) {
	closeAndReloadParent($any_setting_changed? $_REQUEST['reload'] : '');
}

$sectionBaseUrl	= "settings.php?table=$table&scope=$scope&reload=" .urlencode($_REQUEST['reload']). "&section=";

if( $skn_changed ) {
	redirect("$sectionBaseUrl$section&any_setting_changed=1");
}



//
// start formular, declare the sections
// ============================================================================
//


$site->title = htmlconstant('_SETTINGS_DIALOGTITLE');
$site->pageStart(array('popfit'=>1));
echo '<br />';

if( $any_setting_changed ) {
	reloadParent($_REQUEST['reload']);
}

$section		= isset($section)? $section : regGet($section_setting, 0);
$sectionCounter	= 0;

form_tag('settings_form', 'settings.php');
form_hidden('table', $table);
form_hidden('scope', $scope);
form_hidden('reload', $_REQUEST['reload']);
form_hidden('section', $section);

if( $scope == 'index' && $table_def )
{
	$site->skin->sectionDeclare(htmlconstant('_SETTINGS_SEARCH'),
		 "$sectionBaseUrl$sectionCounter", $sectionCounter==$section);
	$sectionCounter++;
}
else if( ($scope == 'edit' && $table_def) || $scope == 'syslocedit' ) 
{
	$site->skin->sectionDeclare(htmlconstant('_SETTINGS_INPUTFIELDS'),
		 "$sectionBaseUrl$sectionCounter", $sectionCounter==$section);
	$sectionCounter++;
}

if( regGet('toolbar.bin', 1) )
{
	$jobBaseUrl = "$sectionBaseUrl$sectionCounter=";
	$site->skin->sectionDeclare(htmlconstant('_JOBLISTS'), $jobBaseUrl, $sectionCounter==$section);
	$sectionCounter++;
}

$site->skin->sectionDeclare(htmlconstant('_SETTINGS_FILTER'), "$sectionBaseUrl$sectionCounter", $sectionCounter==$section);
$sectionCounter++;

$site->skin->sectionDeclare(htmlconstant('_SETTINGS_VIEW'), "$sectionBaseUrl$sectionCounter", $sectionCounter==$section);
$sectionCounter++;

if( regGet('settings.editable', 1) ) {
	$sectionPasswordUrl = "$sectionBaseUrl$sectionCounter";
	$site->skin->sectionDeclare(htmlconstant('_PASSWORD'), $sectionPasswordUrl, $sectionCounter==$section);
	$sectionCounter++;
}




//
// section: table
// ============================================================================
//


if( $scope == 'index' && $table_def )
{
	$site->skin->sectionStart();

		//
		// section: table: search
		// ================================================================
		//

		$site->skin->submenuStart();
			echo htmlconstant('_SETTINGS_SEARCHSETTINGSFOR', $table_def->descr);
		$site->skin->submenuBreak();
			$site->menuHelpEntry('isettingssearch');
		$site->skin->submenuEnd();
		
		$site->skin->dialogStart();

			form_control_start(htmlconstant('_SETTINGS_SEARCHINPUT'));

				form_control_check('otherallowplusminusword', regGet('index.searchplusminusword', 0), '', 0, 1);
				echo '<label for="otherallowplusminusword">' . htmlconstant('_SETTINGS_ALLOWMINUSPLUSWORD') . '</label><br />';
			
				form_control_check('othershowpcode', regGet('index.searchshowpcode', 1), '', 0, 1);
				echo '<label for="othershowpcode">' . htmlconstant('_SETTINGS_SEARCHSHOWPCODE') . '</label><br />';

				form_control_check('otherfuzzyinfo', regGet('index.searchfuzzyinfo2', 1), '', 0, 1);
				echo '<label for="otherfuzzyinfo">' . htmlconstant('_SETTINGS_SEARCHSHOWFUZZYINFO') . '</label>';
			
			form_control_end();

			form_control_start(htmlconstant('_SETTINGS_SEARCHFIELDS'));


				$href = "index_listattr.php?table={$table_def->name}&amp;reloadparentsparent=1";
				echo "<a href=\"$href\" target=\"dbsearch_opt\" onclick=\"return popup(this,260,500);\">" . htmlconstant('_SETTINGS_SEARCHEDITFIELDS___') . '</a>';

			form_control_end();
			
			form_control_start(htmlconstant('_SETTINGS_COLUMNS'));

				$href = "index_listattr.php?table={$table_def->name}&amp;reloadparentsparent=1&amp;scope=columns";
				echo "<a href=\"$href\" target=\"dbsearch_opt\" onclick=\"return popup(this,260,500);\">" . htmlconstant('_SETTINGS_SEARCHEDITCOLUMNS___') . '</a>';
			
			form_control_end();
			
		$site->skin->dialogEnd();
		
	
	$site->skin->sectionEnd();
}
else if( $scope == 'edit' && $table_def ) 
{
	$site->skin->sectionStart();
	
		//
		// section: table: editfields
		// ================================================================
		//
		
		
		
		
		// editfields...
		$ob = 0;
		$numText = 0;
		$namesUsed = array();
		$hasAttr = 0;
		$hasHtmlEditor = 0;
		$hasTextarea = 0;
		
		edit_fields_out($table);
		
		if( $ob ) 
		{
			echo $ob->tableEnd();
			$site->skin->workspaceEnd();
			
			if( $hasTextarea || $hasHtmlEditor )
			{
				$site->skin->submenuStart();
					echo htmlconstant('_SETTINGS_MULTIPLELINEINPUT') . ' (nur alter Editor)';
				$site->skin->submenuBreak();
					echo '&nbsp;';
				$site->skin->submenuEnd();
				$site->skin->dialogStart();
					
					if( $hasTextarea )
					{
						form_control_start(htmlconstant('_SETTINGS_FONT'));
							$avail_editors = '###' . htmlconstant('_AUTO') . '###prop###' .htmlconstant('_SETTINGS_PROPORTIONAL'). '###monospc###' . htmlconstant('_SETTINGS_MONOSPACED') . ' ';
							form_control_enum('edtcss', regGet("edit.textarea.$table.css", ''), $avail_editors);
						form_control_end();
						/*
						form_control_start(htmlconstant('_SETTINGS_FUNCTIONEDITOR'));
							form_control_check('edteditor', regGet("edit.textarea.$table.editor", 1), '', 0, 1);
						form_control_end();
						*/
					}
					
					if( $hasHtmlEditor )
					{				
						form_control_start(htmlconstant('_SETTINGS_HTMLEDITOR'));
							$avail_editors = '###' .  htmlconstant('_SETTINGS_HTMLEDITORNONE');
							if( $Admin_Ewebeditpro2_Include ) {
								$avail_editors .= '###ewebeditpro###eWebEditPro';
							}
							if( $Admin_Pcwebedit_Include ) {
								$avail_editors .= '###pcwebedit###pcWebEdit';
							}
							
							form_control_enum('edthtml', regGet("edit.textarea.$table.html", ''), $avail_editors);
						form_control_end();
					}
					
				$site->skin->dialogEnd();
			}
			
			
		}
		
		// function "add values"			
		if( $hasAttr )
		{
			$site->skin->submenuStart();
				echo htmlconstant('_SETTINGS_FUNCTIONADDVALUES') . ' (nur alter Editor)';
			$site->skin->submenuBreak();
				if( $ob ) {
					echo '&nbsp;';
				}
				else {
					$site->menuHelpEntry('isettingsinput');
				}
			$site->skin->submenuEnd();
			$site->skin->workspaceStart();
				$ob = new COLTABLE_CLASS;
				echo $ob->tableStart(3);
					
					$numAddValues = 0;
					$namesUsed = array();
					
					addvalues_fields_out($table);

					if( $numAddValues >= 3 ) {
						echo $ob->cellStart('nowrap="nowrap"');
							echo "&nbsp;<a href=\"\" onclick=\"return allNone('settings_form','av');\">" . htmlconstant('_ALLNONE') . '</a>';
					}
				
				echo $ob->tableEnd();
			$site->skin->workspaceEnd();
		
			$site->skin->dialogStart();
				form_control_start(htmlconstant('_SETTINGS_SEPERATOR'));
					$othersep = regGet("edit.seperator.$table", ';');
					if( $othersep == '#' ) { $othersep = 'hash'; }
					form_control_enum('othersep', $othersep, 
						',###&nbsp;,&nbsp;###;###&nbsp;;&nbsp;###/###&nbsp;/&nbsp;###+###&nbsp;+&nbsp;###hash###&nbsp;#&nbsp;');

				form_control_end();
				
				
			$site->skin->dialogEnd();
		}
		
		$site->skin->submenuStart();
			echo 'Verschiedenes (nur alter Editor)';
		$site->skin->submenuEnd();		
		$site->skin->workspaceStart();
			form_control_check('usetabs', regGet('edit.rowrider', 1), '', 0, 1);
			echo '<label for="usetabs">' . htmlconstant('_SETTINGS_USETABS') . '</label><br />';						
		$site->skin->workspaceEnd();


		

	
	$site->skin->sectionEnd();
}
else if( $scope == 'syslocedit' )
{
	//
	// section: sysloc: edit
	// ================================================================
	//
	
	$site->skin->sectionStart();
	
		$site->skin->submenuStart();
			echo htmlconstant('_SETTINGS_INPUTFIELDSSIZEHINT');
		$site->skin->submenuBreak();
			$site->menuHelpEntry('isettingsinput');
		$site->skin->submenuEnd();
		$site->skin->workspaceStart();
			$width = 40;
			$height = 5;
			regGetSize("sysloc.edit.size", $width, $height);
			form_control_text('textsysloc', "$width x $height", 8 /*width*/, 8 /*maxlen*/);
		$site->skin->workspaceEnd();
		
	$site->skin->sectionEnd();
}



//
// section: jobs
// ============================================================================
//



if( regGet('toolbar.bin', 1) )
{
	//
	// perform action
	//
	
	$updateOpener = 0;
	$reloadOpener = 0;
	
	$oldNumberOfBins = sizeof($_SESSION['g_session_bin']->getBins());
	
	$jobmsg = '';
	$joberr = '';
	
	if( $_REQUEST['jobactive'] )
	{
		// define default job-list
		$_SESSION['g_session_bin']->binSetActive($_REQUEST['jobactive']);
		$updateOpener = 1;
		if( !isset($jobname) ) {
			$jobname = $_REQUEST['jobactive'];
			$jobtable = 'OPTIONS';
		}
	}
	else if( $_REQUEST['jobempty'] != '' )
	{
		// empty bin list
		if( $_SESSION['g_session_bin']->binExists($_REQUEST['jobempty']) ) 
		{
			$_SESSION['g_session_bin']->binEmpty($_REQUEST['jobempty']);
			$updateOpener = 1;

			$jobname = $_REQUEST['jobempty'];
			$jobtable = 'OPTIONS';
			
			$jobmsg = htmlconstant('_SETTINGS_BINMSGEMPTYED', $_SESSION['g_session_bin']->getName($_REQUEST['jobempty']));
		}
	}
	else if( $_REQUEST['jobdelete'] != '' )
	{
		// delete bin list
		if( $_SESSION['g_session_bin']->binExists($_REQUEST['jobdelete']) ) 
		{
			$_SESSION['g_session_bin']->binDelete($_REQUEST['jobdelete']);			
			$updateOpener = 1;
			
			$jobmsg = htmlconstant('_SETTINGS_BINMSGDELETED', $_SESSION['g_session_bin']->getName($_REQUEST['jobdelete']));
		}
	}
	else if( $_REQUEST['jobremovenonexistant'] != '' ) 
	{
		// remove non existant records from a bin 
		if( $_SESSION['g_session_bin']->binExists($jobname) ) 
		{
			$jobtable	= $_REQUEST['jobremovenonexistant'];
			$currIds	= $_SESSION['g_session_bin']->getRecords($jobtable, $jobname);
			
			reset($currIds);
			while( list($currId) = each($currIds) ) {
				$db->query("SELECT id FROM $jobtable WHERE id=$currId");
				if( !$db->next_record() ) {
					$_SESSION['g_session_bin']->recordDelete($jobtable, $currId, $jobname);
				}
			}
		}
	}
	else if( $_REQUEST['jobadd'] != '' )
	{
		// add records from another job list
		if( $_SESSION['g_session_bin']->binExists($_SESSION['g_session_jobname']) 
		 && $_SESSION['g_session_bin']->binExists($_REQUEST['jobadd']) )
		{
			$jobname		= $_SESSION['g_session_jobname'];
			$jobtable		= 'OPTIONS';
			$recordsAdded	= 0;

			$source = $_SESSION['g_session_bin']->getRecords('', $_REQUEST['jobadd']);
			while( list($currTable, $currIds) = each($source) ) {
				reset($currIds);
				while( list($currId) = each($currIds) ) {
					if( !$_SESSION['g_session_bin']->recordExists($currTable, $currId, $jobname) ) {
						$_SESSION['g_session_bin']->recordAdd($currTable, $currId, $jobname);
						$recordsAdded ++;
					}
				}
			}
			
			$jobmsg = htmlconstant('_SETTINGS_BINMSGRECORDSADDED', $recordsAdded, $_SESSION['g_session_bin']->getName($_REQUEST['jobadd']), $_SESSION['g_session_bin']->getName($jobname));
		}
	}
	else if( $_REQUEST['jobnew'] != '' )
	{
		// new job list / create link to a job list of another user
		// correct bin name
		$jobnew = $_REQUEST['jobnew'];
		switch( $_SESSION['g_session_bin']->binAdd($jobnew) )
		{
			case 0:
				$jobmsg = htmlconstant('_SETTINGS_BINMSGNEWLISTCREATED', $_SESSION['g_session_bin']->getName($jobnew));
				$jobname = $jobnew;
				break;
				
			case 1:
				$joberr = htmlconstant('_SETTINGS_BINERRNAMEEXISTS', isohtmlentities($jobnew));
				$jobname = '@NEW';
				break;
				
			case 2:
				$joberr = htmlconstant('_SETTINGS_BINERRLISTNOTFOUND', isohtmlentities($jobnew));
				$jobname = '@NEW';
				break;
		}
	}
	else if( $_REQUEST['jobaccess'] != '' )
	{
		// set access for a job list
		if( $_SESSION['g_session_bin']->binExists($_SESSION['g_session_jobname']) )
		{
			$_SESSION['g_session_bin']->binSetAccess($_REQUEST['jobaccess'], $_SESSION['g_session_jobname']);
			
			$jobname = $_SESSION['g_session_jobname'];
			$jobtable = 'OPTIONS';

			$jobmsg = htmlconstant('_SETTINGS_BINMSGACCESSCHANGED', $_SESSION['g_session_bin']->getName($jobname), htmlconstant('_SETTINGS_BINALLOW'.strtoupper($_REQUEST['jobaccess'])));
		}
	}
	
	// check the number of bin and if we have to reload the whole page 
	// to reflect the options "..." right of the paper-clip
	$bins = $_SESSION['g_session_bin']->getBins();
	if( $oldNumberOfBins != sizeof($bins) 
	 && ($oldNumberOfBins == 1 || sizeof($bins)==1) ) {
	 	$reloadOpener = 1;
	}
	
	//
	// update or reload opener?
	//
	
	if( $reloadOpener ) {
		reloadParent($_REQUEST['reload']);
	}
	else if( $updateOpener ) {
		$_SESSION['g_session_bin']->updateOpener();
	}
	
	//
	// render page
	//
	
	
	$site->skin->sectionStart();
	
		$site->skin->submenuStart();
			echo htmlconstant('_JOBLISTS'); 
		$site->skin->submenuBreak();
			$site->menuHelpEntry('isettingsjobs');
		$site->skin->submenuEnd();
		
		if( $jobmsg || $joberr ) {
			$site->skin->msgStart($joberr? 'e' : 'i');
				echo $joberr? $joberr : $jobmsg;
			$site->skin->msgEnd();
		}

		$site->skin->workspaceStart();

		// dump all bins
		table_start();
		for( $i = 0; $i < sizeof($bins); $i++ )
		{
			$currName = $bins[$i];
			$currEntries = $_SESSION['g_session_bin']->getRecords('', $currName);
			
			// render list name
			echo '<tr><td>';
			
				$currDescr = $_SESSION['g_session_bin']->getName($currName);
			
				echo '<a href="' . isohtmlentities("$jobBaseUrl&jobname=".urlencode($currName==$jobname? '@0' : $currName)) . '">';
					echo "<img src=\"skins/default/img/tree" .($jobname==$currName? 'c' : 'e'). "1.gif\" width=\"13\" height=\"13\" border=\"0\" alt=\"[+]\" title=\"\" />";
				echo '</a>';
			
			echo '</td><td>';
				
				echo $jobname==$currName? "<b>$currDescr</b>" : $currDescr;
				
				if( $_SESSION['g_session_bin']->access[$currName] == 'r' 
				 || $_SESSION['g_session_bin']->access[$currName] == 'e' ) {
					echo ' ' . htmlconstant('_SETTINGS_BINALLOWSHORT', htmlconstant('_SETTINGS_BINALLOW'.strtoupper($_SESSION['g_session_bin']->access[$currName])));
				}
				
				if( !$_SESSION['g_session_bin']->binIsEditable($currName) ) {
					echo ' ' . htmlconstant('_SETTINGS_BINREADONLY');
				}
				
				if( sizeof($bins) > 1 ) {
					if( $currName==$_SESSION['g_session_bin']->activeBin ) {
						echo " <img src=\"{$site->skin->imgFolder}/bin1.gif\" width=\"15\" height=\"13\" border=\"0\" alt=\"[X]\" title=\"\" />";
						echo ' ' . htmlconstant('_SETTINGS_BINISACTIVEBIN');
					}
					else {
						echo " <a href=\"" .isohtmlentities("$jobBaseUrl&jobname=".urlencode($jobname)."&jobtable=".urlencode($jobtable)."&jobactive=".urlencode($currName)). "\">";
							echo "<img src=\"{$site->skin->imgFolder}/bin0.gif\" width=\"15\" height=\"13\" border=\"0\" alt=\"[ ]\" title=\"\" />";
						echo '</a>';
					}
				}
			
			echo '</td></tr>';
			
			if( $jobname == $currName )
			{
				table_end();
				table_start();
				
					// render list content overview
					reset($currEntries);
					$currTotalCount = 0;
					while( list($currTable, $currIds) = each($currEntries) ) 
					{
						$currIdsCount = sizeof($currIds);
						if( $currIdsCount )
						{
							$tempTableDef = Table_Find_Def($currTable);
							if( $tempTableDef ) 
							{
								echo '<tr><td>';
									echo '<a href="' . isohtmlentities("$jobBaseUrl&jobname=".urlencode($currName)."&jobtable=".urlencode($currTable==$jobtable? '0' : $currTable)) . '">';
										echo "<img src=\"skins/default/img/tree" .($jobtable==$currTable? 'c' : 'e'). "2.gif\" width=\"37\" height=\"13\" border=\"0\" alt=\"[+]\" title=\"\" />";
									echo "</a>";
								echo '</td><td>';
									$hiliteStart = $jobtable == $currTable? '<b>' : '';
									$hiliteEnd = $jobtable == $currTable? '</b>' : '';
									echo $hiliteStart;
									echo htmlconstant
										(
											$currIdsCount==1? '_SETTINGS_BINNRECORDIN' : '_SETTINGS_BINNRECORDSIN', 
											$currIdsCount, 
											"$hiliteEnd<a href=\"index.php?table=$currTable&f0=job&v0=" .urlencode($currName). "&searchreset=2&searchoffset=0&orderby=date_modified+DESC\" target=\"_blank\" onclick=\"return popdown(this);\">$hiliteStart{$tempTableDef->descr}$hiliteEnd</a>$hiliteStart"
										);
									echo $hiliteEnd;
								echo '</td></tr>';
								
								if( $jobtable == $currTable ) 
								{
									table_end();
									table_start();
									
										$hasNonExistantRecords = 0;
										reset($currIds);
										while( list($currId, $active) = each($currIds) ) {
											table_item();
												$db->query("SELECT id FROM $tempTableDef->name WHERE id=$currId");
												if( $db->next_record() ) {
													echo "<a href=\"edit.php?table=$currTable&id=$currId\" target=\"_blank\" onclick=\"return popdown(this);\">";
														echo isohtmlentities($tempTableDef->get_summary($currId, '; '));
													echo '</a>';
												}
												else {
													echo htmlconstant('_SETTINGS_BINRECORDDOESNOTEXIST', $currId);
													$hasNonExistantRecords = 1;
												}
											table_item_end();
										}
										
										if( $hasNonExistantRecords && !$_SESSION['g_session_bin']->binIsExt($currName) ) {
											table_item();
												echo '<a href="' . isohtmlentities("$jobBaseUrl&jobname=".urlencode($currName)."&jobremovenonexistant=".urlencode($jobtable)) . "\" onclick=\"return confirm('" .htmlconstant('_SETTINGS_BINREMOVENONEXISTANTASK', $currDescr, $tempTableDef->descr). "');\">";
													echo htmlconstant('_SETTINGS_BINREMOVENONEXISTANT');
												echo '</a>';
											table_item_end();
										}
										
									table_end();
									table_start();
								}
								
								$currTotalCount += $currIdsCount;
							}
						}
						
					} // next table
	
					if( $currTotalCount == 0 ) {
						echo '<tr><td>';
							echo '<img src="skins/default/img/treei2.gif" width="37" height="13" border="0" />&nbsp;';
						echo '</td><td>';
							echo htmlconstant('_SETTINGS_BINNORECORDS');
						echo '</td></tr>';
					}
					
					// options...
					echo '<tr><td>';
						echo '<a href="' . isohtmlentities("$jobBaseUrl&jobname=".urlencode($currName)."&jobtable=".($jobtable=='OPTIONS'? '0' : 'OPTIONS')) . '">';
							echo "<img src=\"skins/default/img/tree" .($jobtable=='OPTIONS'? 'c' : 'e'). "2.gif\" width=\"37\" height=\"13\" border=\"0\" alt=\"[+]\" title=\"\" />";
						echo '</a>';
					echo '</td><td>';
						echo $jobtable == 'OPTIONS'? '<b>' : '';
						echo htmlconstant('_SETTINGS_BINOPTIONS___');
						echo $jobtable == 'OPTIONS'? '</b>' : '';
					echo '</td></tr>';
					
					if( $jobtable == 'OPTIONS' )
					{
						table_end();
						table_start();

						// use as active bin
						if( $currName!=$_SESSION['g_session_bin']->activeBin )
						{
							table_item();
								echo "<a href=\"" .isohtmlentities("$jobBaseUrl&jobactive=".urlencode($currName)). "\" title=\"" .htmlconstant('_SETTINGS_BINACTIVEBINHINT'). "\">" . htmlconstant('_SETTINGS_BINUSEASDEFAULTLIST') . '</a>';
							table_item_end();
						}

						if( $_SESSION['g_session_bin']->binIsExt($currName) )
						{
							// external list options
							table_item();
								echo htmlconstant('_SETTINGS_BINLISTBYOTHER', $_SESSION['g_session_bin']->getExtUserName($currName));
							table_item_end();

							table_item();
								echo "<a href=\"" .isohtmlentities("{$jobBaseUrl}0&jobdelete=".urlencode($currName)). "\" onclick=\"return confirm('" .htmlconstant('_SETTINGS_BINUSENOLONGERASK', $currDescr). "')\">" . htmlconstant('_SETTINGS_BINUSENOLONGER') . '</a>';
							table_item_end();
						}
						else
						{
							// permissions
							table_item();
							
								$permoptions = '<select name="jobaccess" size="1">';
									$temp = array('n', 'r', 'e');
									for( $j = 0; $j < sizeof($temp); $j++ ) {
										$tempChecked = ($_SESSION['g_session_bin']->access[$currName]==$temp[$j] || ($temp[$j]=='n' && $_SESSION['g_session_bin']->access[$currName]==''))? 1 : 0;
										$permoptions .= '<option value="' .($tempChecked? '' : $temp[$j]). '"';
										$permoptions .= $tempChecked? ' selected="selected"' : '';
										$permoptions .= '>';
											 $permoptions .= htmlconstant('_SETTINGS_BINALLOW'.strtoupper($temp[$j]));
										$permoptions .= '</option>';
									}
								$permoptions .= '</select>';
								
								echo htmlconstant('_SETTINGS_BINALLOW', $permoptions, "$currName@" . $_SESSION['g_session_userloginname']);
								
							table_item_end();
	
							// add records from
							$nonEmptyLists = '###';
							for( $j = 0; $j < sizeof($bins); $j++ )
							{
								$tempName = $bins[$j];
								$tempEntries = $_SESSION['g_session_bin']->getRecords('', $tempName);
								if( $tempName != $currName ) {
									$tempTotal = 0;
									while( list($tempTable, $tempIds) = each($tempEntries) ) {
										$tempTotal += sizeof($tempIds);
									}
									
									if( $tempTotal ) {
										$nonEmptyLists .= "###$tempName###" . $_SESSION['g_session_bin']->getName($tempName);
									}
								}
							}		
							if( $nonEmptyLists !='###' ) {						
								table_item();
									echo htmlconstant('_SETTINGS_BINADDRECORDSFROM') . ' ';
									form_control_enum('jobadd', '', $nonEmptyLists);
								table_item_end();
							}
							
							// empty
							if( $currTotalCount ) {
								table_item();
									echo "<a href=\"" .isohtmlentities("$jobBaseUrl&jobempty=".urlencode($currName)). "\" onclick=\"return confirm('" .htmlconstant('_SETTINGS_BINEMPTYASK', $currDescr, $currTotalCount). "')\">" . htmlconstant('_SETTINGS_BINEMPTY') . '</a>';
								table_item_end();
							}
							
							// delete
							if( sizeof($bins) > 1 || $_SESSION['g_session_bin']->activeBin != 'default' ) {
								table_item();
									echo "<a href=\"" .isohtmlentities("{$jobBaseUrl}0&jobdelete=".urlencode($currName)). "\" onclick=\"return confirm('" .htmlconstant('_SETTINGS_BINDELETEASK', $currDescr, $currTotalCount). "')\">" . htmlconstant('_SETTINGS_BINDELETE') . '</a>';
								table_item_end();
							}
						}
					}
				
				table_end();
				table_start();

				// save in session
				$_SESSION['g_session_jobname'] = $jobname;
				
			} // end bin details
			
		} // next bin

		// new
		
		echo '<tr><td>';
			echo '<a href="' . isohtmlentities("$jobBaseUrl&jobname=".urlencode($jobname=='@NEW'? '@0' : '@NEW')) . '">';
				echo "<img src=\"skins/default/img/tree" .($jobname=='@NEW'? 'c' : 'e'). "1.gif\" width=\"13\" height=\"13\" border=\"0\" alt=\"[+]\" title=\"\" />";
			echo '</a>';
		echo '</td><td>';
			echo $jobname=='@NEW'? '<b>' : '';
			echo htmlconstant('_SETTINGS_BINNEW___');
			echo $jobname=='@NEW'? '</b>' : '';
		echo '</td></tr>';
		
		if( $jobname=='@NEW' )
		{
			table_end();
			table_start();
			table_item(2, 37);
				echo htmlconstant('_SETTINGS_BINNAMEOFNEWLIST', 
					'<input type="text" name="jobnew" value="" size="14" maxlength="100" />');
			table_item_end();
		}
		
		echo '</table>';
		
		$site->skin->workspaceEnd();
	
	$site->skin->sectionEnd();
}



//
// section: filter
// ============================================================================
//



$site->skin->sectionStart();

	$site->skin->submenuStart();
		echo htmlconstant('_SETTINGS_FILTERGRPHINT');
	$site->skin->submenuBreak();
		$site->menuHelpEntry('isettingsfilter');
	$site->skin->submenuEnd();
	$site->skin->workspaceStart();

		$allgroups = acl_get_all_groups();
		$allgroups[] = array(0, htmlconstant('_UNKNOWN'));
		$allgroups[] = array(999999, htmlconstant('_SETTINGS_GRANTSOTHER'));
		
		$filteredgroups = str_replace(' ', '', regGet('filter.grp', ''));
		if( $filteredgroups != '' ) {
			$filteredgroups = explode(',', $filteredgroups);
		}
		else {
			$filteredgroups = array();
		}
		
		$ob = new COLTABLE_CLASS;
		echo $ob->tableStart(1 /*the number of rows, one is just fine as names are somtimes little longer ...*/);
		
			for( $g = 0; $g < sizeof($allgroups); $g++ ) 
			{
				echo $ob->cellStart(/*'nowrap="nowrap"'*/);
					form_control_check("fgrp{$allgroups[$g][0]}", 
						in_array($allgroups[$g][0], $filteredgroups)? 0 : 1, 
						'', 0, 1);
					echo "<label for=\"fgrp{$allgroups[$g][0]}\">" . isohtmlentities($allgroups[$g][1]) . '</label>';
			}
	
			if( sizeof($allgroups) >= 3  ) 
			{
				echo $ob->cellStart('nowrap="nowrap"');
					echo "&nbsp;<a href=\"\" onclick=\"return allNone('settings_form','fgrp');\">" . htmlconstant('_ALLNONE') . '</a>';
			}
		
		echo $ob->tableEnd();

		if( $debug ) {
			acl_get_grp_filter($filteredgroups, $filterpositive);
			echo $filterpositive? '<hr /><b>groups to <i>show:</i></b> ' : '<b>groups to <i>hide:</i></b> '; 
			for( $i = 0; $i < sizeof($filteredgroups); $i++ ) echo ($i? ', ' : '') . $filteredgroups[$i];
			echo '<hr />';
		}

		echo '&nbsp;<br />';

		$ob = new COLTABLE_CLASS;
		echo $ob->tableStart(2);
			echo $ob->cellStart('nowrap="nowrap"');
				echo '<span class="dllcontinue">' . htmlconstant('_SETTINGS_ONNOACCESS') . ':</span>';
			echo $ob->cellStart('nowrap="nowrap"');
				form_control_enum('onnoaccess', regGet('index.onnoaccess', ''), 
					'###'.htmlconstant('_SETTINGS_ONNOACCESSHIDE').'###showhint###'.htmlconstant('_SETTINGS_ONNOACCESSSHOWHINT'));
		echo $ob->tableEnd();
		

	$site->skin->workspaceEnd();
	
$site->skin->sectionEnd();



//
// section: view
// ============================================================================
//



$site->skin->sectionStart();
	
	// skin
	$site->skin->submenuStart();
		echo htmlconstant('_SETTINGS_VIEW');
	$site->skin->submenuBreak();
		$site->menuHelpEntry('isettingsview');
	$site->skin->submenuEnd();
	$site->skin->dialogStart();
		form_control_start(htmlconstant('_SETTINGS_SKIN'));
		
			if( regGet('skin.editable', 1) )
			{
				$skins = 'skins/default###Orange';
					$handle = @opendir('skins');
					if( $handle ) {
						while( $folderentry=@readdir($handle) ) {
							if( $folderentry[0]!='.' && $folderentry!='default' && @file_exists("skins/$folderentry/class.php") )
								$skins .= "###skins/$folderentry###" . ucwords(strtr($folderentry, '_-', '  '));
						}
						closedir($handle);
					}
					$handle = @opendir('config/skins');
					if( $handle ) {
						while( $folderentry=@readdir($handle) ) {
							if( $folderentry[0]!='.' && @file_exists("config/skins/$folderentry/class.php") )
								$skins .= "###config/skins/$folderentry###" . ucwords(strtr($folderentry, '_-', '  '));
						}
						closedir($handle);
					}
				form_control_enum('edtskin', regGet('skin.folder', 'skins/default'), $skins);
				echo '<br />';
			}

			/*
			form_control_check('rowcursor', regGet('index.rowcursor', 1), '', 0, 1);
			echo '<label for="rowcursor">' . htmlconstant('_SETTINGS_ROWCURSOR') . '</label><br />';
			*/

			form_control_check('neweditor', regGet('edit.oldeditor', 0)? 0 : 1, '', 0, 1);
			echo '<label for="neweditor">' . 'Neuer Editor' . '</label> <a href="https://b2b.kursportal.info/index.php?title=Neuer_Editor" target="_blank">weitere Informationen...</a><br />';

			

			
		form_control_end();

		form_control_start(htmlconstant('_SETTINGS_TOOLBAR'));
			
			form_control_check('tbbin', regGet('toolbar.bin', 1), '', 0, 1);
			echo '<label for="tbbin">' . htmlconstant('_SETTINGS_USEJOBLISTS') . '</label><br />';

			if( regGet('login.tipsntricks.use', 1) ) {
				form_control_check('tbtnt', regGet('login.tipsntricks', 1), '', 0, 1);
				echo '<label for="tbtnt">' . htmlconstant('_SETTINGS_TIPSNTRICKS') . '</label><br />';
			}


		form_control_end();

		// date
		form_control_start(htmlconstant('_SETTINGS_DATE'));

			$dateSetting = str_replace(',', ' ', '  '.regGet('date.global', 'relative, weekdays').' ');
			
			form_control_check("otherrelative", strpos($dateSetting, ' relative '), '', 0, 1);
			echo "<label for=\"otherrelative\">" . htmlconstant('_SETTINGS_DATESHOWRELDATE') . '</label><br />';
		
			form_control_check("otherweekdays", strpos($dateSetting, ' weekdays '), '', 0, 1);
			echo "<label for=\"otherweekdays\">" . htmlconstant('_SETTINGS_DATESHOWWEEKDAYS') . '</label><br />';
		
			form_control_check("othercentury", strpos($dateSetting, ' century '), '', 0, 1);
			echo "<label for=\"othercentury\">" .  htmlconstant('_SETTINGS_DATESHOWCENTURY') . '</label><br />';
		
			$time = strpos($dateSetting, ' time ')? 1 : 0;
			form_control_check("othertime", $time, 
				"if(this.checked)sectd('seconds',1);else sectd('seconds',0);return true;", 
				0, 1);
			echo "<label for=\"othertime\">" . htmlconstant('_SETTINGS_DATESHOWTIME') . '</label><br />';
			
			echo '<div id="seconds" style="display:' .($time? 'block' : 'none'). ';">';
			
				form_control_check("otherseconds", strpos($dateSetting, ' seconds '), '', 0, 1);
				echo "<label for=\"otherseconds\">" . htmlconstant('_SETTINGS_DATESHOWSECONDS') . '</label>';
				
			echo '</div>';

		form_control_end();
		
		// show settings from $g_addsettings_names
		global $g_addsettings_names;
		for( $i = 0; $i < sizeof($g_addsettings_names); $i += 2 )
		{
			form_control_start(htmlconstant($g_addsettings_names[$i]));
				form_control_text("addsettings$i", regGet($g_addsettings_names[$i+1], ''));
			form_control_end();
		}
		
		
	$site->skin->dialogEnd();	
	
$site->skin->sectionEnd();



//
// section: password
// ============================================================================
//

	
if( regGet('settings.editable', 1) ) 
{
	$site->skin->sectionStart();
	
		$site->skin->submenuStart();
			echo htmlconstant('_SETTINGS_PWTITLE');
		$site->skin->submenuBreak();
			$site->menuHelpEntry('isettingspassword');
		$site->skin->submenuEnd();
	
		if( $pwerr ) 
		{
			$site->skin->msgStart('e');
				echo $pwerr;
			$site->skin->msgEnd();
		}
	
		$site->skin->dialogStart();
	
			form_control_start(htmlconstant('_LOGINNAME'));
				echo isohtmlentities($_SESSION['g_session_userloginname']);
			form_control_end();
			
			form_control_start(htmlconstant('_SETTINGS_PWCURRPASSWORD'));
				form_control_password('pwoldpw', '', 20, 'autocomplete="off"'); // if autocomplete is on (this is the default in most browsers, 
			form_control_end();													// this field is always submitted with a value; so our check if a password 
																				// is entered and should be stored, will fail ...
			form_control_start(htmlconstant('_SETTINGS_PWNEWPASSWORD'));
				form_control_password('pwnewpw1', '', 20, 'autocomplete="off"');
			form_control_end();
			
			form_control_start(htmlconstant('_SETTINGS_PWREPEATPASSWORD'));
				form_control_password('pwnewpw2', '', 20, 'autocomplete="off"');
			form_control_end();

			if( isset($_REQUEST['pwsug']) ) {
				$pwsug = $_REQUEST['pwsug'];
			} else {
				require_once("genpassword.inc.php");
				$pwsug = genpassword();
			}

			form_control_start(htmlconstant('_SETTINGS_PWSUGGESTION'));
				form_hidden('pwsug', $pwsug);
				echo isohtmlentities($pwsug) . ' <a href="' . isohtmlentities($sectionPasswordUrl) . '">' . htmlconstant('_OTHERSUGGESTION___') . '</a>';
			form_control_end();
			
		$site->skin->dialogEnd();
		
	$site->skin->sectionEnd();
}




//
// end formular, end page
// ============================================================================
//


if( !regGet('settings.editable', 1) )
{
	$site->skin->msgStart('w');
		echo '<b>' . htmlconstant('_SETTINGS_REMARK1') . '</b><br /><br />' . htmlconstant('_SETTINGS_REMARK2');
	$site->skin->msgEnd();
}

$site->skin->buttonsStart();
	form_button('settings_ok', htmlconstant('_OK'), '');
	form_button('settings_cancel', htmlconstant('_CANCEL'), 'window.close();return false;');
	form_button('settings_apply', htmlconstant('_APPLY'));
$site->skin->buttonsEnd();


echo '</form>';

$site->pageEnd();



