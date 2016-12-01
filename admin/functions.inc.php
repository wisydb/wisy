<?php

/*=============================================================================
Common Framework and Session Management
===============================================================================

Author:	
	Bjoern Petersen

=============================================================================*/



require_once('classes.inc.php');






/*=============================================================================
registry functions
=============================================================================*/



function regLoadFromDb__($in) // this function should not depend on any globals; it is also used directly before the session is really started (for the role check)
{
	$in = strtr($in, "\r\t", "\n ");
	$in = explode("\n", $in);
	
	$out = array();
	for( $i = 0; $i < sizeof($in); $i++ )
	{
		$equalPos = strpos($in[$i], '=');
		if( $equalPos ) {
			$regKey = trim(substr($in[$i], 0, $equalPos));
			if( $regKey != '' && strpos($regKey, '.') ) {
				$out[$regKey] = trim(substr($in[$i], $equalPos+1));
			}
		}
	}
	
	return $out;
}



function regSaveToDb__($userId, $settings)
{
	global $g_regDb;
	
	$ret = '';
	ksort($settings);
	reset($settings);
	while( list($regKey, $regValue) = each($settings) ) 
	{
		$regKey		= strval($regKey);
		$regValue	= strval($regValue);
		if( $regKey!='' ) {
			$regValue = strtr($regValue, "\n\r\t", "   ");
			$ret .= "$regKey=$regValue\n";
		}
	}
	
	$g_regDb->query("UPDATE user SET settings='" .addslashes($ret). "' WHERE id=$userId");
}



function regInit__() // there is no need to call this function directly
{
	global $g_regDb;
	global $g_regUser;
	global $g_regUserIsTemplate;
	global $g_regTemplate;

	// connect to DB	
	if( !is_object($g_regDb) ) {
		$g_regDb = new DB_Admin;
	}
	
	if( !is_array($g_regUser) ) {
		if( is_array($_SESSION['g_session_reg_temporary']) ) {
			$g_regUser = $_SESSION['g_session_reg_temporary'];
		}
		else {
			$userid = intval($_SESSION['g_session_userid']);
			if( $userid ) {
				$g_regDb->query("SELECT settings, loginname FROM user WHERE id=" . $userid);
				if( $g_regDb->next_record() ) {
					$g_regUser = regLoadFromDb__($g_regDb->fs('settings'));
					$g_regUserIsTemplate = $g_regDb->f('loginname')=='template'? 1 : 0;
				}
			}
		}
	}
	
	if( !is_array($g_regTemplate) ) {
		$g_regDb->query("SELECT settings FROM user WHERE loginname='template'");
		if( $g_regDb->next_record() ) {
			$g_regTemplate = regLoadFromDb__($g_regDb->fs('settings'));
		}
		else {
			require_once('genpassword.inc.php');
			$g_regDb->query("INSERT INTO user (loginname, password) VALUES('template', '" .addslashes(crypt(genpassword())). "')");
		}
	}
}



function regGet($regKey, $defaultValue = '<default value here>', $regOptions = '')
	// the default value is '' and not 0 because inval('') _is_ 0
	// but strval(0) is _not_ ''
{
	global $g_regUser;
	global $g_regUserIsTemplate;
	global $g_regTemplate;

	// load registry if not yet done
	if( !is_array($g_regUser) || !is_array($g_regTemplate) ) {
		regInit__();
	}

	// make sure, we're settings a string
	$defaultValue = strval($defaultValue);
	
	// check for unset default value
	if( $defaultValue == '<default value here>' ) {
		echo '<b>' . isohtmlentities("ERROR: unset default value for: regGet(\"$regKey\", \"$defaultValue\");") . '</b><br />';
	}
	
	// check if the user _is_ the template
	if( $g_regUserIsTemplate ) {
		$regOptions .= ' template';
	}

	// get registry key
	if( isset($g_regUser[$regKey]) && strpos($regOptions, 'template')===false ) {
		return $g_regUser[$regKey];
	}
	else if( isset($g_regTemplate[$regKey]) ) {
		return $g_regTemplate[$regKey];
	}
	else {
		return $defaultValue;
	}
}


// the function returns != 0 if anything was changed to disk, and 0 otherwise
function regSet($regKey, $newValue, $defaultValue = '<default value here>', $regOptions = '')
{
	global $g_regUser;
	global $g_regUserModified;
	global $g_regUserIsTemplate;
	global $g_regTemplate;
	global $g_regTemplateModified;

	$ret = 0;

	// load registry if not yet done
	if( !is_array($g_regUser) || !is_array($g_regTemplate) ) {
		regInit__();
	}
	
	// make sure, we're setting a string
	$newValue = strval($newValue);
	$defaultValue = strval($defaultValue);

	// check for unset default value
	if( $defaultValue == '<default value here>' ) {
		echo '<b>' . isohtmlentities("ERROR: unset default value for: regSet(\"$regKey\", \"$newValue\", \"$defaultValue\");") . '</b><br />';
	}
	
	// check if the user _is_ the template
	if( $g_regUserIsTemplate ) {
		$regOptions .= ' template';
	}
	
	// set value
	if( strpos($regOptions, 'template') === false )
	{
		if( is_array($g_regUser) )
		{
			if( ( isset($g_regTemplate[$regKey]) && $g_regTemplate[$regKey]==$newValue)
			 || (!isset($g_regTemplate[$regKey]) && $defaultValue==$newValue) )
			{
				if( isset($g_regUser[$regKey]) ) {
					unset($g_regUser[$regKey]);
					$g_regUserModified = 1;
					$ret = 1;
				}
			}
			else if( !isset($g_regUser[$regKey]) || $g_regUser[$regKey]!=$newValue )
			{
				$g_regUser[$regKey] = $newValue;
				$g_regUserModified = 1;
				$ret = 1;
			}
		}
	}
	else
	{
		if( $defaultValue==$newValue )
		{
			if( isset($g_regTemplate[$regKey]) ) {
				unset($g_regTemplate[$regKey]);
				$g_regTemplateModified = 1;
				$ret = 1;
			}
		}
		else if( !isset($g_regTemplate[$regKey]) || $g_regTemplate[$regKey]!=$newValue ) 
		{
			$g_regTemplate[$regKey] = $newValue;
			$g_regTemplateModified = 1;
			$ret = 1;
		}
	}
	
	return $ret;
}



// save any modifications made my regSet(), the function returns != 0 if 
// anything was written to disk, and 0 if there are no changes to save.
function regSave()
{
	global $g_regDb;
	global $g_regUser;
	global $g_regUserModified;
	global $g_regTemplate;
	global $g_regTemplateModified;

	$ret = 0;
	
	if( $g_regUserModified )
	{
		if( regGet('settings.editable', 1) )
		{
			if( $_SESSION['g_session_userid'] ) {
				regSaveToDb__($_SESSION['g_session_userid'], $g_regUser);
				$g_regUserModified = 0;
				$ret = 1;
			}
		}
		else
		{
			$_SESSION['g_session_reg_temporary'] = $g_regUser;
			$ret = 1;
		}
	}
	
	if( $g_regTemplateModified )
	{
		$g_regDb->query("SELECT id FROM user WHERE loginname='template'");
		if( $g_regDb->next_record() ) {
			regSaveToDb__($g_regDb->f('id'), $g_regTemplate);
			$g_regTemplateModified = 0;
			$ret = 1;
		}
	}
	
	return $ret;
}



/*=============================================================================
common form ouput functions
=============================================================================*/



//
// a complete form has the following syntax:
//
// $site->skin->dialogStart()
//
//			form_contol_start()
//				form_control_text()
//			form_control_end()
//			.
//			.
// $site->skin->dialogEnd()
//
// 


function form_tag($name, $action, $onsubmit='', $enctype='', $method='post', $target='')
{
	global $form_name;
	$form_name = $name;

	echo '<form name="' .$name. '" action="' .$action. '" method="' .$method. '"';
		if( $onsubmit ) echo ' onsubmit="' . $onsubmit . '"';
		if( $enctype ) 	echo ' enctype="' . $enctype . '"';
		if( $target ) 	echo ' target="' . $target . '"';
	echo '>';
}



function form_control_start($descr = '', $hint = 0, $css_classes = '')
{
	global $site;

	$site->skin->controlStart();

		form_control_continue($descr, $hint, $css_classes, 0);
	
	$site->skin->controlBreak();
}



function form_control_continue($descr = '', $hint = 0, $css_classes = '', $continued = 1)
{
	if( $hint && !$descr ) {
		$descr = htmlconstant('_ERROR');
	}
	
	if( $descr ) {	
		echo $continued? '&nbsp;&nbsp; <span class="dllcontinue">' : '';
		hint_start($hint);
		if( $css_classes ) echo '<span class="'.$css_classes.'">';
			echo "$descr:";
		if( $css_classes ) echo '</span>';
		hint_end();
		echo $continued? '</span>&nbsp;' : '';
	}
	else {
		echo '&nbsp;';
	}
}



function form_control_end()
{
	global $site;
	$site->skin->controlEnd();
}


function form_control_textarea( $name, $value, $width, $height, 
								$addparam = array() )
{	
	if( $addparam['readonly'] )
	{
		echo nl2br(isohtmlentities($value));
		form_hidden($name, $value);
	}
	else
	{
		$width = intval($width);
		if( $width <= 0 ) $width = 40;
		
		$height = intval($height);
		if( $height <= 0 ) $height = 5;
	
		echo '<textarea name="' .$name. '" wrap="virtual" rows="' .$height. '" cols="' .$width. '"';
			if( $addparam['css_classes'] )	{ echo ' class="'.trim($addparam['css_classes']).'"';		}
			if( $addparam['onchange'] )		{ echo ' onchange="'.$addparam['onchange'].'"';				}
		echo '>' . isohtmlentities($value) . '</textarea>';
	}	
}

function form_control_text(	$name, $value, $width = 0, $maxlength = 0,
							$addparam = array() )
{
	if( $addparam['readonly'] ) 
	{
		echo isohtmlentities($value);
		form_hidden($name, $value);
	}
	else
	{
		$width = intval($width);
		if( $width <= 0 ) $width = 40;

		$maxlength = intval($maxlength);
		if( $maxlength <= 0 ) $maxlength = 250;
		
		echo '<input type="text" size="' .$width. '" maxlength="' .$maxlength. '" name="' .$name. '" value="' . isohtmlentities($value) . '"';
		
			if( $addparam['autocomplete'] ) 
			{
				echo ' data-acdata="'.$addparam['autocomplete'].'"';
				if( $addparam['autocomplete.nest'] ) {
					//echo ' data-acnest="1"';
					$addparam['css_classes'] = trim('acnest '.$addparam['css_classes']);
				}
				$addparam['css_classes'] = trim('acclass '.$addparam['css_classes']);
			}
			
			if( $addparam['css_classes'] )	{ echo ' class="'.trim($addparam['css_classes']).'"';		}
			if( $addparam['onchange'] )		{ echo ' onchange="'.$addparam['onchange'].'"';		}
			
		echo ' />';
	}
}



// create a date/datetime input control from a SQL-Date as YYYY-MM-DD HH:MM:SS
// type is one of:
//   date		- create a date input control
//   dateopt	- create a date input control with optional day/month input
//   datetime	- create a date and time input control
function form_control_datetime($name, $sqldate_, $type = 'date', $readonly = 0)
{
	if( $readonly )
	{
		echo sql_date_to_human($sqldate_, $type);
	}
	else
	{
		$value = sql_date_to_human($sqldate_, "$type editable");
		
		$title = htmlconstant($type=='dateopt'? '_DATEFORMATOPTHINT' : '_DATEFORMATHINT');
		if( $type == 'datetime' ) {
			$title .= ', ' . htmlconstant('_TIMEFORMATHINT');
		}
		
		$size = $type=='datetime'? 22 : 12;
		echo "<input type=\"text\" name=\"$name\" value=\"$value\" title=\"$title\" size=\"$size\" maxlength=\"32\" />";
	}
}



function form_control_check($name, $value, $onclick = '', $readonly = 0, $useLabel = 0)
{
	global $site;
	
	if( $readonly ) 
	{
		if( $value ) {
			echo htmlconstant('_YES');
			echo '<input type="hidden" name="'.$name.'" value="1" /> ';
		}
		else {
			echo htmlconstant('_NO');
		}
	}
	else 
	{
		echo '<input type="checkbox" name="' .$name. '" value="1"'; // if checked, 'name' is set to 'value' on submit. otherwise, 'name' is unset.
			
			if( $useLabel ) {
				echo ' id="' .$name. '"';
			}
		
			if( $value ) {
				echo ' checked="checked"';
			}
			if( $onclick ) {
				echo ' onclick="' .$onclick. '"';
			}
			
		echo ' />';
	}
}



function form_control_password($name, $value = '', $width = 40, $addattr = '')
{
	echo '<input type="password" size="' .$width. '" maxlength="250" name="' .$name. '" value="' . isohtmlentities($value) . '"';
	if( $addattr ) {
		echo " $addattr ";
	}
	echo ' />';
}



function form_control_radio($name, $value, $values)
{
	$values = explode('###', $values);

	for( $v = 0; $v < sizeof($values); $v+=2 ) {
		echo "<input type=\"radio\" name=\"$name\" id=\"$name$v\" value=\"{$values[$v]}\"";
		if( $values[$v] == $value ) {
			echo ' checked="checked"';
		}
		echo " /><label for=\"$name$v\">{$values[$v+1]}</label>";

		if( $v != sizeof($values) - 2 ) {
			echo substr($values[$v+1], -1)==' '? ' ' : '<br>';
		}
	}
}



function form_control_enum($name, $value, $values, $readonly = 0, $style = '', $onchange = '', $size = 1)
{
	$values = explode('###', $values);
	
	if( $readonly ) {
		$valFound = false;
		for( $v = 0; $v < sizeof($values); $v+=2 ) {
			if( $values[$v] == $value ) {
				echo $values[$v+1] . ' ';
				$valFound = true;
				break;
			}
		}
		if( !$valFound ) echo isohtmlspecialchars($value);
		echo '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
	}
	else {
		echo "<select name=\"$name\" size=\"$size\"";
		if( $onchange ) {
			echo " onchange=\"$onchange\"";
		}
		if( $style ) {
			echo " style=\"$style\"";
		}
		echo '>';
			for( $v = 0; $v < sizeof($values); $v+=2 ) {
				echo '<option value="' .$values[$v]. '"';
				if( $values[$v] == $value ) {
					echo ' selected="selected"';
				}
				echo '>' .$values[$v+1]. '</option>';
			}
		echo '</select>';
	}
}



function form_button($name, $descr, $onclick = '')
{
	echo "<input class=\"button\" type=\"submit\" name=\"$name\" value=\"$descr\"";

	if( $onclick ) {
		echo " onclick=\"$onclick\"";
	}

	echo ' />';
}


function form_clickbutton($url, $descr)
{
	$js = "window.location='$url'; return false;";
	echo "<input class=\"button\" type=\"submit\" value=\"$descr\" onclick=\"".$js."\">";
																		//	  ^^^	07.01.2014: to be strict, we should use isohtmlspecialchars() here - 
																		// 			however, in reality this doe not work as the entities are not removed before the 
																		//			JavaScript is executed :-( 
}



function form_hidden($name, $value)
{
	echo '<input type="hidden" name="' .$name. '" value="' . isohtmlentities($value) . '" />';
}



function hint_start($hint)
{
	global $hint_started;
	if( $hint ) {
		$hint = strip_tags($hint);
		if( substr($hint, 0, 8) == 'warning:' ) {
			$hint = trim(substr($hint, 8));
			$cls = 'hintw';
		}
		else {
			$cls = 'hinte';
		}
		
		if( $hint != 1 ) {
			echo "<i class=\"$cls\" title=\"$hint\">";
		}
		else {
			echo "<i class=\"$cls\">";
		}
		
		$hint_started = 1;
	}
	else {
		$hint_started = 0;
	}
}



function hint_end()
{
	global $hint_started;
	if( $hint_started ) {
		echo '</i>';
	}
}




/*=============================================================================
Misc
=============================================================================*/



require_once('date.inc.php');
function access_to_human($access)
{
	$access = intval($access);
	$ret = '';
	$chars = "xwrxwrxwr";
	for( $i = 8; $i >= 0; $i-- ) {
		$ret .= ($access & (1<<$i))? substr($chars, $i, 1) : '-';
		if( $i==6 || $i==3 ) {
			$ret .= '';
		}
	}
	$ret .= ' ('.$access.')';
	return $ret;
}




$g_plugins = array();
function call_plugin($phpfile, &$param)
{
	global $g_plugins;
	
	if( !$g_plugins[$phpfile] ) {
		$pluginfunc = ''; // will be overwritten by the following require()-statement
		require_once($phpfile);
		$g_plugins[$phpfile] = $pluginfunc;
	}
	
	$pluginfunc = $g_plugins[$phpfile];
	return $pluginfunc($param);
}



function redirect($url)
{
	header("Location: $url");
	exit();
}



function user_ascii_name($id)
{
	global $g_uhn_cache;
	global $g_uhn_db; if( !isset($g_uhn_db) ) { $g_uhn_cache = array(); $g_uhn_db = new DB_Admin; }
	
	$id = intval($id);

	if( isset($g_uhn_cache[$id]) )
	{
		return $g_uhn_cache[$id];
	}
	else
	{
		$ret = '';
		if( $id==0 ) {
			$ret = htmlconstant('_UNKNOWN'); // Note: in edit*.php we may compare against this string!
		}
		else {
			$g_uhn_db->query("SELECT name,loginname FROM user WHERE id=$id");
			if( $g_uhn_db->next_record() ) {
				$ret = $g_uhn_db->fs('name');
				if( $ret == '' ) {
					$ret = $g_uhn_db->f('loginname');
					if( $ret == '' ) {
						$ret = $id;
					}
				}
			}
			else {
				$ret = $id;
			}
		}
		
		$g_uhn_cache[ $id ] = $ret;
		return $ret;
	}
}
function user_html_name($id)
{
	return isohtmlspecialchars(user_ascii_name($id));
}

function grp_ascii_name($id)
{
	global $g_uhn_db; if( !isset($g_uhn_db) ) { $g_uhn_cache = array(); $g_uhn_db = new DB_Admin; }

	$id = intval($id);
	
	$g_uhn_db->query("SELECT name,shortname FROM user_grp WHERE id=$id");
	if( $g_uhn_db->next_record() ) {
		$ret = $g_uhn_db->fs('name');
		if( $ret == '' ) {
			$ret = $g_uhn_db->fs('shortname');
			if( $ret == '' ) {
				$ret = $id;
			}
		}
	}
	else {
		$ret = $id==0? htmlconstant('_NOGROUP') : $id;
	}
	
	return $ret;
}
function grp_html_name($id)
{
	return isohtmlspecialchars(grp_ascii_name($id));
}



function smart_size($bytes)
{
	if( $bytes >= 10485760 /*10 MB*/ ) {
		return round($bytes/1048576 /*1 MB*/ ) . ' MB';
	}
	else if( $bytes >= 524288 /*1/2 MB*/ ) {
		return round($bytes/1048576 /*1 MB*/, 1) . ' MB';
	}
	else if( $bytes >= 1024 /*1 KB*/ ) {
		return round($bytes/1024) . ' KB';
	}
	else {
		return "$bytes Byte";
	}
}



function make_clickable_url($url, &$ahref)
{
	if( substr($url, 0, 7)!='mailto:' 
	 && !strpos($url, ':/'.'/') )
	{
		if( strpos($url, '@') ) {
			$url = "mailto:$url";
		}
		else {
			$url = "http:/"."/$url";
		}
	}

	$ahref = '<a href="' . isohtmlspecialchars($url) . '" target="_blank" title="'.htmlconstant('_VIEW').'">'; // always _blank - otherwise mailto: may open in the same browser tab (eg. for Google Mail)
	
	return $url;
}



$g_num_bin_rendered = 0;
function bin_render($table, $id)
{
	global $site;

	global $g_num_bin_rendered;
	
	global $g_curr_bin_ids;
	global $g_curr_bin_ids_table;
	global $g_curr_bin_editable;
	
	// hash the IDs because loading may consume some time
	if( !is_array($g_curr_bin_ids) 
	 || $g_curr_bin_ids_table != $table )
	{
		$g_curr_bin_ids			= array_keys($_SESSION['g_session_bin']->getRecords($table));
		$g_curr_bin_ids_table	= $table;
		$g_curr_bin_editable	= $_SESSION['g_session_bin']->binIsEditable()? 1 : 0;
	}
	
	// get state
	$state = in_array($id, $g_curr_bin_ids)? 1 : 0;
	
	// render
	$html = "<script type=\"text/javascript\"><!--\n";
		$html .= "binRender('$table',$id,$state" .($g_num_bin_rendered==0? (",'".$_SESSION['g_session_bin']->getName($_SESSION['g_session_bin']->activeBin)."','{$site->skin->imgFolder}',".sizeof($_SESSION['g_session_bin']->getBins()).','.$g_curr_bin_editable) : ""). ");";
	$html .= "/" . "/--></script>";
	
	// prepare for next
	$g_num_bin_rendered++;
	
	return $html;
}	



/*=============================================================================
Global Part
=============================================================================*/



// make the html file uncachable, start session
header("Cache-Control: no-cache, must-revalidate");	// HTTP/1.1
header("Pragma: no-cache");							// HTTP/1.0

if( !defined('G_SKIP_LOGIN') )	// do NOT start sessions on "skip login" as this would result in non-cookie sessions with URL rewriting.
{								// beside the security problem, this will also make problems with functions as readfile() with binary data.
	define('SESSION_LIFETIME_SECONDS', 2*60*60); 
	ini_set('session.gc_maxlifetime', 36000); 
	session_name('sj');
	session_start();
	if(time()-intval($_SESSION['gc_self'])>SESSION_LIFETIME_SECONDS){$_SESSION=array();}$_SESSION['gc_self']=time(); // why we use our own timout: http://goo.gl/FZhF8

	if( !isset($_SESSION['g_session_list_results']) ) {
		$_SESSION['g_session_list_results'] = array();
	}

	if( !isset($_SESSION['g_session_track_defaults']) )	{
		$_SESSION['g_session_track_defaults'] = array();
	}

	if( $free_object ) {
		unset( $_SESSION[$free_object] );
	}
}


// first includes
require_once('sql_curr.inc.php');
require_once('config/config.inc.php');

// set language
require_once('lang.inc.php');

if( !$_SESSION['g_session_language'] ) {
	$wantedLang = '';
	if( $_COOKIE['g_cookie_login'] ) {
		$wantedLang = explode('&', $_COOKIE['g_cookie_login']);
		$wantedLang = urldecode($wantedLang[0]) . ',';
	}
	$wantedLang .= $_SERVER['HTTP_ACCEPT_LANGUAGE'];
	$_SESSION['g_session_language'] = check_wanted_lang(get_avail_lang_from_folder(regGet('login.lang', '')), $wantedLang);
}

if( isset($_REQUEST['g_do_session_language_change']) ) {
	$_SESSION['g_session_language'] = check_wanted_lang(get_avail_lang_from_folder(regGet('login.lang', '')), $_REQUEST['g_do_session_language_change']);
}

require_lang('lang/basic');
require_lang('config/lang/basic');

// further includes
require_once('table_def.inc.php');
require_once('config/db.inc.php');
require_once('acl.inc.php');

// create site
$site = new ADMIN_SITE_CLASS();
$site->initSkin();


// check if the user is authorized or prompt for password
if( !$_SESSION['g_session_userid'] && !defined('G_SKIP_LOGIN') )
{
	require_once('login.inc.php');
	login_check();
}

// init bins
if( $_SESSION['g_session_userid'] )
{
	$db = new DB_Admin; // not sure: is this object used anywhere else?
	$db->query("SELECT remembered FROM user WHERE id=".$_SESSION['g_session_userid']);
	if( $db->next_record() )
	{
		// load remembered bin
		if( regGet('settings.editable', 1) ) {
			$_SESSION['g_session_bin'] = unserialize($db->fs('remembered'));
		}
		else {
			if( !is_object($_SESSION['g_session_bin']) ) {
				$_SESSION['g_session_bin'] = unserialize($db->fs('remembered'));
			}
		}
		
		if( !is_object($_SESSION['g_session_bin']) ) {
			$_SESSION['g_session_bin'] = new G_BIN_CLASS();
		}
		
		$_SESSION['g_session_bin']->userId = $_SESSION['g_session_userid'];
	}
}





