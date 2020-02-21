<?php



/*=============================================================================
System Localisation
===============================================================================

file:	
	sysloc.php
	
author:	
	Bjoern Petersen

parameters (for subsequent calls only):
	f		the folder to edit
	id		the id to edit in the form of "<key>.<language>"
			if unset, an overview is shown
	scope	
	op

=============================================================================*/




/*****************************************************************************
 *  class to handle the system localisation
 *****************************************************************************/




class SYS_LOC_CLASS
{
	var $folder;
	var $type; // currently either 'php' or 'js', if unknown, the directory should not be localized
	var $subtype; // currently either '' or 'help_define'
	var $languages;
	var $strings;
	var $string_remarks;
	var $file_remark;

	function SYS_LOC_CLASS($folder)
	{
		// store folder, init type and languages
		$this->folder		= $folder;
		$this->type			= '';
		$this->subtype		= '';
		$this->languages	= array();

		// scan directory to determinate the type and the available languages
		$old_level = error_reporting(0);
		$handle = opendir($folder);
		error_reporting($old_level);
		if( $handle )
		{
			while( $folderentry = readdir($handle) )
			{
				if( $folderentry!='.'
				 && $folderentry!='..'
			 	 && !is_dir("$folder/$folderentry") )
			 	{
			 		$dot = strrpos($folderentry, '.');
			 		if( $dot ) {
				 		// set type if not yet done
				 		$ext = strtolower(substr($folderentry, $dot+1));
					 	if( !$this->type ) {
					 		if( $ext == 'php' || $ext == 'js' ) {
					 			$this->type = $ext;
					 		}
					 	}

					 	// add languages if type is known
					 	if( $this->type && $ext == $this->type ) {
					 		$this->languages[] = strtolower(substr($folderentry, 0, $dot));
					 	}
					 }
				}
			}
			
			closedir($handle);
		}
		
		sort($this->languages);
	}

	// function loads all strings from the given file and returns an associative array
	// containing the strings
	function load_lang_strings(&$filename, &$file_remark)
	{
		$ret = array();
		$file_remark = '';
		$entry_remark = '';
		$num_empty_remarks = 0;

		// open file
		$filehandle = fopen($filename, 'r');
		if( !$filehandle ) {
			return $ret;
		}

		// go through all lines
		while( !feof($filehandle) )
		{
			// get line
			$line = trim(fgets($filehandle, 32000));

			// get rid of PHP start / end mark
			if( substr($line, 0, 5) == ('<'.'?'.'php') ) {
				$line = trim(substr($line, 5));
			}

			if( substr($line, -2) == '?'.'>') {
				$line = trim(substr($line, 0, strlen($line)-2));
			}

			if( substr($line, 0, 2) == '/'.'/' )
			{
				// line completely marked out, check for "file remark" or "entry remark"
				$line = trim(substr($line,2));
				if( $line ) {
					if( substr($line, 0, 1) != '!' ) {
						if( sizeof($ret) == 0 ) {
							if( $num_empty_remarks >= 2 ) {
								// file remark
								if( $file_remark ) { $file_remark .= ' '; }
								$file_remark .= $line;
							}
						}
						else {
							// entry remark
							if( $entry_remark ) { $entry_remark .= ' '; }
							$entry_remark .= $line;
						}
					}
				}
				else {
					$num_empty_remarks++;
				}
			}
			else
			{
				// write entry remark, if any
				if( $entry_remark ) {
					if( strlen($entry_remark) > strlen($this->string_remarks[$lastKey]) ) {
						$this->string_remarks[$lastKey] = $entry_remark;
					}
					$entry_remark = '';
				}
				
				// get rid of starting 'var' / 'define' / 'help_define'
				if( substr($line, 0, 11) == 'help_define' ) {
					$line = trim(substr($line, 11));
					$this->subtype = 'help_define';
				}

				if( substr($line, 0, 6) == 'define' ) {
					$line = trim(substr($line, 6));
				}

				if( substr($line, 0, 3) == 'var' ) {
					$line = trim(substr($line, 3));
				}

				// get rid of trailing ';' / ')'
				if( substr($line, -1) == ';') {
					$line = trim(substr($line, 0, strlen($line)-1));
				}

				if( substr($line, -1) == ')') {
					$line = trim(substr($line, 0, strlen($line)-1));
				}

				// get key'n'value
				$p = strpos($line, $this->type == 'php'? ',' : '=');
				if( $p ) {
					$key = trim(substr($line, 0, $p));
					$key = strtr($key, " ('\"", '    ');
					$key = str_replace(' ', '', $key);

					$value = trim(substr($line, $p+1)); // get value
					$value = trim(substr($value, 1, strlen($value)-2)); // get rid of startign and endling quotes
					$value = str_replace("\\n", "\n", $value); // add "real" linewraps
					$ret[$key] = stripslashes($value);

					$lastKey = $key;
				}
			}
		}

		// close file
		fclose($filehandle);

		return $ret;
	}

	function load_all_strings()
	{
		$this->strings			= array();
		$this->string_remarks	= array();
		$this->file_remark		= '';
		$this->max_key_len		= 0;

		// get all strings
		for( $lc = 0; $lc < sizeof((array) $this->languages); $lc++ ) {
			$filename = $this->folder . '/' . $this->languages[$lc] . '.' . $this->type;
			if( file_exists($filename) ) {
				$curr_strings = $this->load_lang_strings($filename, $curr_file_remark);
				if( strlen($curr_file_remark) > strlen($this->file_remark) ) {
					$this->file_remark = $curr_file_remark;
				}

				reset($curr_strings);
				foreach($curr_strings as $key => $value) {
					if( strlen($key) > $this->max_key_len ) {
						$this->max_key_len = strlen($key);
					}
					if( $this->strings[$key] ) {
						$this->strings[$key][$this->languages[$lc]] = $value;
					}
					else {
						$this->strings[$key] = array($this->languages[$lc]=>$value);
					}
				}
			}
		}

		// sort strings
		ksort($this->strings);
	}

	function save_lang_strings($lang)
	{
		// open file
		$filehandle = fopen("$this->folder/$lang.$this->type", 'w+');
		if( !$filehandle ) {
			return 0; // error
		}
		
		// init file
		if( $this->type == 'php' ) {
			fwrite($filehandle, "<"."?"."php");  // do not add the trailing "? >" - this only creates errors with too much spaces
		}

		fwrite($filehandle, "\n/"."/");
		fwrite($filehandle, "\n/"."/ This file is created and parsed by sysloc.php!");
		fwrite($filehandle, "\n/"."/ - Place exactly one statement per line, no additional linewraps.");
		fwrite($filehandle, "\n/"."/ - Do not use any operators nor any function other than define().");
		fwrite($filehandle, "\n/"."/ - Do not use other remarks than /"."/ at the beginning of a line.");
		fwrite($filehandle, "\n/"."/");

		if( $this->file_remark ) {
			$currValue = str_replace('/'.'/', '/', $this->file_remark);
			fwrite($filehandle, "\n/".'/ ' . $currValue);
			fwrite($filehandle, "\n/"."/");
		}

		// write all strings
		if( $this->type == 'php' ) {
			$keyMask = $this->subtype == 'help_define'? "\nhelp_define('%s',%s %s);" : "\ndefine('%s',%s %s);";
		}
		else {
			$keyMask = "\nvar %s%s = %s;";
		}
		
		$stringTrans = array("\$"=>"\\\$", "\""=>"\\\"", "\\"=>"\\\\", "\t"=>" ", "\r"=>"", "\n"=>"\\n");
		reset($this->strings);
		foreach($this->strings as $currKey => $currValue)
		{
			// get spaces
			$spaces = '';
			for( $i = strlen($currKey); $i < $this->max_key_len; $i++ ) {
				$spaces .= ' ';
			}
			
			// get value
			$currValue = strtr(trim($currValue[$lang]), $stringTrans);
			$currValue = "\"" . $currValue . "\"";
			
			// write key'n'value
			fwrite($filehandle, sprintf($keyMask, $currKey, $spaces, $currValue));
			
			// write remark
			if( $this->string_remarks[$currKey] ) {
				fwrite($filehandle, "\n/"."/ " . $this->string_remarks[$currKey]);
			}
		}

		// success
		fclose($filehandle);
		return 1;
	}

	function get_next_prev_key($wantedKey, &$prevKey, &$nextKey)
	{
		$prevKey = '';
		$nextKey = '';
		$returnNext = 0;
		reset($this->strings);
		foreach($this->strings as $currKey => $currValue) {
			if( $returnNext ) {
				$nextKey = $currKey;
				break;
			}
			else if( $currKey == $wantedKey ) {
				$returnNext = 1;
			}
			else {
				$prevKey = $currKey;
			}
		}
	}

	function check_write_access($lang, &$errorString)
	{
		$errorString = '';
		if( is_writable("$this->folder/$lang.$this->type") ) {
			return 1; // write access
		}
		else {
			$errorString = htmlconstant('_SYSLOC_ERRW', $lang, "$this->folder/$lang.$this->type");
			return 0; // no write access
		}
	}
	
	function add_new_lang($newLang, $initByLang)
	{
		// copy strings
		$strings = $this->strings;
		
		// add new language
		reset($strings);
		foreach($strings as $currKey => $currValue) {
			$newString = $initByLang? $currValue[$initByLang] : '';
			$this->strings[$currKey][$newLang] = $newString;
		}
	}
	
	// deletes an entry for all languages
	function delete_entry($id)
	{
		$strings = array();
		
		reset($this->strings);
		foreach($this->strings as $currKey => $currValue) {
			if( $currKey != $id ) {
				$strings[$currKey] = $currValue;
			}
		}
		
		$this->strings = $strings;
	}

	function lang_exists($lang)
	{
	    for( $i = 0; $i < sizeof((array) $this->languages); $i++ ) {
			if( $this->languages[$i]==$lang ) {
				return 1;
			}
		}
		return 0;
	}
	
	function check_langcol($in)
	{
		$out = array();
		
		for( $i = 0; $i < sizeof((array) $in); $i++ ) {
			if( $in[$i] && $this->lang_exists($in[$i]) ) {
				$lang_exists = 0;
				for( $j = 0; $j < sizeof($out); $j++ ) {
					if( $out[$j] == $in[$i] ) {
						$lang_exists = 1;
						break;
					}
				}
				
				if( !$lang_exists ) {
					$out[] = $in[$i];
					if( sizeof($out) >= SYSLOC_MAX_LANG_COLS ) {
						break;
					}
				}
			}
		}
		
		if( sizeof($out) == 0 ) {
		    for( $i = 0; $i < min(SYSLOC_MAX_LANG_COLS,sizeof((array) $this->languages)); $i++ ) {
				$out[] = $this->languages[$i];
			}
		}
		
		return $out;
	}
}



/*****************************************************************************
 * render the overview
 *****************************************************************************/



function sysloc_overview_search_folders($startFolder, &$ret, $addAll = 0, $rootLevel = 1)
{
	if( $rootLevel ) {
		$ret = array();
	}
	
	$old_level = error_reporting(0);
	$handle = opendir($startFolder);
	error_reporting($old_level);
	if( $handle ) {
		while( $folderentry = readdir($handle) ) {
			if( $folderentry!='.'
			 && $folderentry!='..'
			 && $folderentry!='mysql'
			 &&  is_dir("$startFolder/$folderentry")
			 && !is_link("$startFolder/$folderentry") )
			{
				if( $addAll
				 || strtolower($folderentry) == 'lang' )
				{
					$info = new SYS_LOC_CLASS("$startFolder/$folderentry");
					if( $info->type ) {
						$ret[] = "$startFolder/$folderentry";
					}
					sysloc_overview_search_folders("$startFolder/$folderentry", $ret, 1 /*add all*/, 0 /*no root level*/);
				}
				else
				{
					sysloc_overview_search_folders("$startFolder/$folderentry", $ret, 0 /*search for 'lang'*/, 0 /*no root level*/);
				}
			}
		}
		closedir($handle);
	}

	if( $rootLevel ) {
		sort($ret);
	}
}



function sysloc_overview($f)
{
	global $site;

	$rows = $_REQUEST['rows'];
	$langcol = $_REQUEST['langcol'];
	
	// store "offset" and "rows"
	if( isset($_REQUEST['offset']) ) { $_SESSION['g_session_sysloc_offset'] = $_REQUEST['offset']; }
	$_SESSION['g_session_sysloc_offset'] = intval($_SESSION['g_session_sysloc_offset']);
	if( $_SESSION['g_session_sysloc_offset'] < 0 ) { $_SESSION['g_session_sysloc_offset'] = 0; }

	if( isset($rows) ) { $_SESSION['g_session_sysloc_rows'] = $rows; }
	$_SESSION['g_session_sysloc_rows'] = intval($_SESSION['g_session_sysloc_rows']);
	if( $_SESSION['g_session_sysloc_rows'] < 1 || $_SESSION['g_session_sysloc_rows'] > 1000) { $_SESSION['g_session_sysloc_rows'] = 20; }

	// get all folders
	sysloc_overview_search_folders('.', $folders);

	// check given folder
	$folder_found = 0;
	for( $fc = 0; $fc < sizeof((array) $folders); $fc++ ) {
		if( $folders[$fc] == $f ) {
			$folder_found = 1;
			break;
		}
	}
	if( !$folder_found ) {
		$f = $folders[0];
	}

	// get information about the furrent folder
	$info = new SYS_LOC_CLASS($f);
	$info->load_all_strings();

	// store language columns
	if( isset($langcol) ) {
		$_SESSION['g_session_sysloc_langcol'] = $info->check_langcol($langcol);
	}
	else {
		$_SESSION['g_session_sysloc_langcol'] = $info->check_langcol($_SESSION['g_session_sysloc_langcol']);
	}

	// start page
	$site->menuItem('mmainmenu', htmlconstant('_SYSLOC'), '<a href="sysloc.php?f=' . urlencode($f) . '">');

	$prevurl = $_SESSION['g_session_sysloc_offset']==0? '' : ('<a href="sysloc.php?f='.urlencode($f).'&amp;offset='.intval($_SESSION['g_session_sysloc_offset']-$_SESSION['g_session_sysloc_rows']).'">');
	$site->menuItem('mprev', htmlconstant('_PREVIOUS'), $prevurl);

	$nexturl = ($_SESSION['g_session_sysloc_offset']+$_SESSION['g_session_sysloc_rows'] < sizeof($info->strings))? ('<a href="sysloc.php?f='.urlencode($f).'&amp;offset='.intval($_SESSION['g_session_sysloc_offset']+$_SESSION['g_session_sysloc_rows']).'">') : '';
	$site->menuItem('mnext', htmlconstant('_NEXT'), $nexturl);

	if( acl_check_access('SYSTEM.LOCALIZELANG', -1, ACL_NEW) ) {
		$site->menuItem('mnew', htmlconstant('_SYSLOC_NEWLANG'), 
			'<a href="sysloc.php?f='.urlencode($f).'&amp;scope=lang&amp;op=new">');
	}

	if( acl_check_access('SYSTEM.LOCALIZEENTRIES', -1, ACL_NEW) ) {
		$site->menuItem('mnewentry', htmlconstant('_SYSLOC_NEWENTRY'), 
			'<a href="sysloc.php?f='.urlencode($f).'&amp;scope=entry&amp;op=new">');
	}

	if( substr($f,-5)=='/help' ) {
		$site->menuItem('mview', htmlconstant('_VIEW'), "<a href=\"help.php\" target=\"help\" onclick=\"return popup(this,500,380);\">");
		$site->menuItem('meditfromview', htmlconstant('_SYSLOC_EDITFROMVIEW'), '<a href="sysloc.php?editfromview">');
	}
	else if( substr($f,-12)=='/tipsntricks' ) {
		$site->menuItem('mview', htmlconstant('_VIEW'), "<a href=\"tipsntricks.php?showtip=0\" target=\"tntall\" onclick=\"return popup(this,500,300);\">");
	}

	$site->title = htmlconstant('_SYSLOC');
	$site->pageStart();
	
	$site->menuHelpScope	= 'isysloc';
	$site->menuLogoutUrl	= 'sysloc.php';
	$site->menuSettingsUrl	= 'settings.php?reload=' . urlencode('sysloc.php?f='.urlencode($f));
	$site->menuOut();

	
	// navigation elements
	$site->skin->workspaceStart();
		echo '<table cellpadding="4" cellspacing="0"  border="0">';
		form_tag('form_sysloc', 'sysloc.php', '', '', 'get');
		
			echo '<tr>';
				echo '<td nowrap="nowrap" valign="top">';

					echo htmlconstant('_SYSLOC_MODULE') . ' = ';
					echo '<select name="f" size="1" onchange="this.form.offset.value=0; submit();">';
					   for( $fc = 0; $fc < sizeof((array) $folders); $fc++ ) {
							echo '<option value="' . $folders[$fc] . '"';
							if( $folders[$fc] == $f ) {
								echo ' selected="selected"';
							}
							echo '>' .str_replace('../', '', $folders[$fc]). '</option>';
						}
					echo '</select>&nbsp;&nbsp;';

					echo htmlconstant('_LANGUAGES') . ' = ';
					for( $i = 0; $i < min(sizeof((array) $info->languages),SYSLOC_MAX_LANG_COLS); $i++ )
					{
						echo "<select name=\"langcol[$i]\" size=\"1\">";
							echo '<option value=""';
							if( !$_SESSION['g_session_sysloc_langcol'][$i] ) {
								echo ' selected="selected"';
							}
							echo '></option>';
							for( $lc = 0; $lc < sizeof((array) $info->languages); $lc++ ) 
							{
								echo '<option value="' . $info->languages[$lc] . '"';
								if( $_SESSION['g_session_sysloc_langcol'][$i] == $info->languages[$lc] ) {
									echo ' selected="selected"';
								}
								echo '>' .$info->languages[$lc]. '</option>';
							}
						echo '</select>&nbsp;';
					}

					form_hidden('offset', $_SESSION['g_session_sysloc_offset']); // needed to reset on module selection

					echo '&nbsp;&nbsp;<input class="button" type="submit" name="ok" value="' . htmlconstant('_OK') . '" />';

				echo '</td>';
			echo '</tr>';
		echo '</form>';
		echo '</table>';
			if( $info->file_remark ) {
				echo '<p>'.isohtmlspecialchars($info->file_remark) . '</p>';
			}
		
	$site->skin->workspaceEnd();

	$site->skin->mainmenuStart();
		require_once('index_tools.inc.php');
		require_lang('lang/overview');
		echo page_sel("sysloc.php?f=".urlencode($f)."&offset=", $_SESSION['g_session_sysloc_rows'], $_SESSION['g_session_sysloc_offset'], sizeof($info->strings), 1);
	$site->skin->mainmenuEnd();


	$site->skin->tableStart();

		$site->skin->headStart();
			$site->skin->cellStart();
				echo htmlconstant('_ID');
			$site->skin->cellEnd();
			for( $lc = 0; $lc < sizeof((array) $_SESSION['g_session_sysloc_langcol']); $lc++ ) {
				$site->skin->cellStart();
					echo htmlconstant('_LANGUAGE') . ': ' . $_SESSION['g_session_sysloc_langcol'][$lc];
				$site->skin->cellEnd();
			}
		$site->skin->headEnd();

		reset($info->strings);
		$offset = 0;
		$rows = 0;
		$access_edit = acl_check_access('SYSTEM.LOCALIZEENTRIES', -1, ACL_EDIT);
		foreach($info->strings as $key => $value)
		{
			if( $offset >= $_SESSION['g_session_sysloc_offset'] )
			{
				$site->skin->rowStart();

					$site->skin->cellStart();
						$any_empty_lang = 0;
						for( $lc = 0; $lc < sizeof((array) $_SESSION['g_session_sysloc_langcol']); $lc++ ) {
							if( !$value[$_SESSION['g_session_sysloc_langcol'][$lc]] ) {
								$any_empty_lang = htmlconstant('_SYSLOC_TRANSLMISSING');
								break;
							}
						}
						hint_start($any_empty_lang);
							echo isohtmlentities($key);
						hint_end();
						if( $any_empty_lang ) {
							echo '<br />&nbsp;'; // make sure, th hint is visible
						}
					$site->skin->cellEnd();

					for( $lc = 0; $lc < sizeof((array) $_SESSION['g_session_sysloc_langcol']); $lc++ ) {
						$site->skin->cellStart();
							$str = $value[$_SESSION['g_session_sysloc_langcol'][$lc]];
							if( $str ) { 
								$str=smart_truncate($str); 
							} 
							else { 
								$str = htmlconstant('_NA');
							}
							
							if( $access_edit ) {
								echo '<a href="sysloc.php?f=' .urlencode($f). '&id=' .urlencode($key . '.' . $_SESSION['g_session_sysloc_langcol'][$lc]). '">';
									echo isohtmlentities($str);
								echo '</a>';
							}
							else {
								echo isohtmlentities($str);
							}
						$site->skin->cellEnd();
					}

				$site->skin->rowEnd();

				$rows++;
				if( $rows >= $_SESSION['g_session_sysloc_rows']) {
					break;
				}
			}

			$offset++;
		}

	$site->skin->tableEnd();

	$site->skin->submenuStart();
	echo htmlconstant('_SYSLOC_NUMREC', sizeof((array) $info->strings), sizeof((array) $info->languages));
	$site->skin->submenuBreak();
		echo htmlconstant('_OVERVIEW_ROWS') . ' ' . rows_per_page_sel("sysloc.php?f=".urlencode($f)."&offset=0&rows=", $_SESSION['g_session_sysloc_rows']);
	$site->skin->submenuEnd();
		

	// end page
	$site->pageEnd();
}



/*****************************************************************************
 * edit a given string
 *****************************************************************************/

 

function sysloc_editentry($f, $id)
{
	global $site;

	$str 						= $_REQUEST['str'];
	$sysloc_canceleditentry		= $_REQUEST['sysloc_canceleditentry'];
	$sysloc_okeditentry			= $_REQUEST['sysloc_okeditentry'];
	$sysloc_subsequenteditentry	= $_REQUEST['sysloc_subsequenteditentry'];

	// back to overview?
	if( isset($sysloc_canceleditentry) ) {
		sysloc_overview($f);
		return;
	}

	// extract language from id
	$p = strrpos($id, '.');
	if( $p === false ) {
		sysloc_overview($f);
		return; // error
	}
	$lang = substr($id, $p+1);
	$id = substr($id, 0, $p);

	// get information, on errors invoke folder overview
	$info = new SYS_LOC_CLASS($f);
	if( !$info->type ) {
		sysloc_overview($f);
		return; // error
	}

	$info->load_all_strings();
	if( !$info->strings[$id] ) {
		sysloc_overview($f);
		return;
	}
	$write_access = $info->check_write_access($lang, $write_access_err);

	$_SESSION['g_session_sysloc_langcol'] = $info->check_langcol($_SESSION['g_session_sysloc_langcol']);

	// save settings
	if( isset($sysloc_subsequenteditentry) && $write_access ) {
		$info->strings[$id][$lang] = stripslashes($str);
		$info->save_lang_strings($lang);
	}

	// back to overview?
	if( isset($sysloc_okeditentry) ) {
		sysloc_overview($f);
		return;
	}

	// start page
	$site->menuItem('mmainmenu', htmlconstant('_SYSLOC'), '<a href="sysloc.php?f='.urlencode($f).'">');

	$info->get_next_prev_key($id, $prevurl, $nexturl);
	$prevurl = $prevurl? ('<a href="sysloc.php?f='.urlencode($f)."&amp;id=$prevurl.$lang\">") : '';
	$nexturl = $nexturl? ('<a href="sysloc.php?f='.urlencode($f)."&amp;id=$nexturl.$lang\">") : '';
	$site->menuItem('mprev', htmlconstant('_PREVIOUS'), $prevurl);
	$site->menuItem('mnext', htmlconstant('_NEXT'), $nexturl);

	$site->menuItem('msearch', htmlconstant('_OVERVIEW'), '<a href="sysloc.php?f='.urlencode($f).'">');

	if( acl_check_access('SYSTEM.LOCALIZEENTRIES', -1, ACL_NEW) ) {
		$site->menuItem('mnew', htmlconstant('_SYSLOC_NEWENTRY'), 
			'<a href="sysloc.php?f='.urlencode($f).'&amp;scope=entry&amp;op=new">');
	}
	
	if( acl_check_access('SYSTEM.LOCALIZELANG', -1, ACL_DELETE) ) {
		$site->menuItem('mdel', htmlconstant('_SYSLOC_DELLANG'), 
			"<a href=\"sysloc.php?f=".urlencode($f)."&amp;scope=lang&amp;op=delete&amp;id=$lang\" onclick=\"return confirm('".htmlconstant('_SYSLOC_DELLANGHINT', isohtmlentities($lang))."');\">");
	}

	if( acl_check_access('SYSTEM.LOCALIZEENTRIES', -1, ACL_DELETE) ) {
		$site->menuItem('mdelentry', htmlconstant('_SYSLOC_DELENTRY'), 
			"<a href=\"sysloc.php?f=".urlencode($f)."&amp;scope=entry&amp;op=delete&amp;id=$id\" onclick=\"return confirm('".htmlconstant('_SYSLOC_DELENTRYHINT', isohtmlentities($id))."');\">");
	}
	
	if( substr($f,-5)=='/help' ) {
		$temp = explode('.', $id);
		$temp = strtolower(substr($temp[0], 6));
		if( intval(substr($temp,-1)) || substr($temp,-1)=='0' ) $temp = substr($temp, 0, strlen($temp)-1);
		$site->menuItem('mview', htmlconstant('_VIEW'), "<a href=\"help.php?id={$temp}\" target=\"help\" onclick=\"return popup(this,500,380);\">");
		$site->menuItem('meditfromview', htmlconstant('_SYSLOC_EDITFROMVIEW'), '<a href="sysloc.php?editfromview">');
	}
	else if( substr($f,-12)=='/tipsntricks' ) {
		$temp = explode('.', $id);
		$temp = intval(substr($temp[0], 5))+100000;
		$site->menuItem('mview', htmlconstant('_VIEW'), "<a href=\"tipsntricks.php?showtip={$temp}\" target=\"tntall\" onclick=\"return popup(this,500,300);\">");
	}

	$site->title = htmlconstant('_SYSLOC');
	$site->pageStart();
	
	$site->menuHelpScope	= 'isysloc';
	$site->menuLogoutUrl	= 'sysloc.php';
	$site->menuSettingsUrl	= 'settings.php?scope=syslocedit&reload=' . urlencode('sysloc.php?f='.urlencode($f).'&id='.urlencode("$id.$lang"));
	$site->menuOut();

	// form out
	$site->skin->dialogStart();
	form_tag('form_sysloc', 'sysloc.php');
	form_hidden('sysloc_subsequenteditentry', 1);
	form_hidden('f', $f);
	form_hidden('id', "$id.$lang");
	
		form_control_start(htmlconstant('_SYSLOC_MODULE'));
			echo str_replace('../', '', $info->folder);
		form_control_end();
		if( $info->file_remark ) {
			form_control_start('');
				echo isohtmlspecialchars($info->file_remark);
			form_control_end();
		}
		form_control_end();

		form_control_start(htmlconstant('_ID'));
			echo $id;
		form_control_end();

		// add language to edit
		$str = $info->strings[$id][$lang];
		form_control_start(htmlconstant('_LANGUAGE') . ': ' . $lang, ($write_access_err?$write_access_err:($str?'':htmlconstant('_SYSLOC_TRANSLMISSING'))));
			form_control_textarea('str', $str, SYSLOC_EDIT_WIDTH, SYSLOC_EDIT_HEIGHT);
			if( $info->string_remarks[$id] ) {
				echo '<br />'.isohtmlentities($info->string_remarks[$id]);
			}
		form_control_end();

		// add languages explicitly selected in the overview
		for( $lc = 0; $lc < sizeof((array) $_SESSION['g_session_sysloc_langcol']); $lc++ ) {
			if( $_SESSION['g_session_sysloc_langcol'][$lc] != $lang ) {
				$str = $info->strings[$id][$_SESSION['g_session_sysloc_langcol'][$lc]];
				form_control_start(htmlconstant('_LANGUAGE') . ': ' . $_SESSION['g_session_sysloc_langcol'][$lc], $str?'':htmlconstant('_SYSLOC_TRANSLMISSING'));
					if( !$str ) { $str = htmlconstant('_NA'); }
					echo nl2br(isohtmlentities($str));
					echo '<br /><a href="sysloc.php?f='.urlencode($f).'&id=' .$id. '.' .$_SESSION['g_session_sysloc_langcol'][$lc]. '">' . htmlconstant('_EDIT___') . '</a>';
				form_control_end();
			}
		}
		
		// add all missing languages
		$further_lang = 0;
		
		for( $lc = 0; $lc < sizeof((array) $info->languages); $lc++ ) 
		{
			if( $info->languages[$lc] != $lang && !in_array($info->languages[$lc], $_SESSION['g_session_sysloc_langcol']) ) 
			{
				if( $further_lang == 0 ) {
					form_control_start(htmlconstant('_SYSLOC_FURTHERLANGUAGES'));
				}
				else {
					echo ', ';
				}
				echo '<a href="sysloc.php?f='.urlencode($f).'&id=' .$id. '.' .$info->languages[$lc]. '">' . $info->languages[$lc] . '</a>';
				$further_lang++;
			}
		}
		
		if( $further_lang ) {
			form_control_end();
		}
		
	$site->skin->dialogEnd();
	
	$site->skin->buttonsStart();
	
		if( $write_access ) {
			form_button('sysloc_okeditentry', htmlconstant('_OK'), '');
		}
		form_button('sysloc_canceleditentry', htmlconstant('_CANCEL'));
		if( $write_access ) {
			form_button('sysloc_apply', htmlconstant('_APPLY'));
		}
	$site->skin->buttonsEnd();

	echo '</form>';
	

	// end page
	$site->pageEnd();
}



/*****************************************************************************
 * new / delete language or entry
 *****************************************************************************/

 
 
function sysloc_new($f, $scope, $op)
{
	global $site;

	$id 					= $_REQUEST['id'];
	$init					= $_REQUEST['init'];
	$sysloc_cancelnew		= $_REQUEST['sysloc_cancelnew'];
	$sysloc_oknew			= $_REQUEST['sysloc_oknew'];
	$sysloc_subsequentnew	= $_REQUEST['sysloc_subsequentnew'];

	$error_id = '';
	$error_module = '';

	// back to overview?
	if( isset($sysloc_cancelnew) ) {
		sysloc_overview($f);
		return;
	}

	// get information, on errors invoke folder overview
	$info = new SYS_LOC_CLASS($f);
	if( !$info->type ) {
		sysloc_overview($f);
		return; // error
	}

	$info->load_all_strings();


	// create new
	if( isset($sysloc_subsequentnew) ) 
	{
		// ID set?
		$id = trim($scope == 'lang'? strtolower($id) : strtoupper($id));
		if( !$id ) {
			$error_id = htmlconstant('_SYSLOC_ERRIDMISSING');
		}

		// id valid?
		if( strpos(' '.$id, '.') || strpos(' '.$id, '/') || strpos(' '.$id, ':') || strpos(' '.$id, '|') ) {
			$error_id = htmlconstant('_SYSLOC_ERRIDINVALID');
		}

		// ID free?
		if( $scope == 'lang' ) {
			if( $info->lang_exists($id) ) {
				$error_id = htmlconstant('_SYSLOC_ERRIDINUSE');
			}
		}
		else {
		    foreach($info->strings as $currKey => $currValue) {
				if( $currKey == $id ) {
					$error_id = htmlconstant('_SYSLOC_ERRIDINUSE');
					break;
				}
			}
		}
		
		// add language or entry
		if( !$error_id && !$error_module ) {
			if( $scope == 'lang' ) {
				$old_level = error_reporting(0);
				$filehandle = fopen("$info->folder/$id.$info->type", 'w+');
				error_reporting($old_level);
				if( $filehandle ) {
					fclose($filehandle);
					$info->add_new_lang($id, $init);
					$info->save_lang_strings($id);
				}
				else {
					$error_module = htmlconstant('_SYSLOC_ERRWDIR', $info->folder);
				}
			}
			else {
			    for( $lc = 0; $lc < sizeof((array) $info->languages); $lc++ ) {
					if( !$info->check_write_access($info->languages[$lc], $error_module) ) {
						break;
					}
				}

				if( !$error_module ) {
					$info->strings[$id] = array();
					$info->string_remarks[$id] = strtr(stripslashes($init), "\n\r\t", '   ');
					ksort($info->strings);
					for( $lc = 0; $lc < sizeof((array) $info->languages); $lc++ ) {
						$info->save_lang_strings($info->languages[$lc]);
					}
				}
			}
		}

		// add error to global error string
		if( $error_id ) {
			$site->msgAdd($error_id);
		}
		if( $error_module ) {
			$site->msgAdd($error_module);
		}

		// back to overview or editor? 
		if( !$error_id && !$error_module ) {
			if( $scope == 'lang' ) {
				sysloc_overview($f);
			}
			else {
				sysloc_editentry($f, $id.'.'.$info->languages[0]);
			}
			return;
		}
	}
	else {
		$id = '';
		$init = '';
	}
	

	// start page
	$site->title = htmlconstant('_SYSLOC');
	$site->pageStart();

	$site->menuItem('mmainmenu', htmlconstant('_SYSLOC'), '<a href="sysloc.php?f='.urlencode($f).'">');
	$site->menuHelpScope	= 'isysloc';
	$site->menuSettingsUrl	= 'settings.php?scope=syslocedit&reload=' . urlencode('sysloc.php?f='.urlencode($f)."&scope=$scope&op=$op");
	$site->menuLogoutUrl	= 'sysloc.php';
	$site->menuOut();

	// form out
	$site->skin->dialogStart();
	form_tag('form_sysloc', 'sysloc.php');
	form_hidden('sysloc_subsequentnew', 1);
	form_hidden('scope', $scope);
	form_hidden('op', $op);
	form_hidden('f', $f);
	
		form_control_start(htmlconstant('_SYSLOC_MODULE'), $error_module);
			echo str_replace('../', '', $info->folder);
			if( $info->file_remark ) {
				echo '<br />' . isohtmlentities($info->file_remark);
			}
		form_control_end();

		form_control_start(htmlconstant('_ID'), $error_id);
			form_control_text('id', $id, 20 /*width*/);
			if( $scope == 'lang' ) {
				$hint = htmlconstant('_SYSLOC_NEWLANGHINT', '<a href="http:/'.'/www.ietf.org/rfc/rfc1766.txt" target="_blank">RFC 1766</a>', '<a href="http:/'.'/www.oasis-open.org/cover/iso639a.html" target="_blank">ISO 639</a>');
			}
			else {
				$hint = htmlconstant('_SYSLOC_NEWENTRYHINT');
			}
			echo '<br />' . $hint;
		form_control_end();

		if( $scope == 'lang' ) {
			form_control_start(htmlconstant('_SYSLOC_INITNEWLANG'));
				$values = '###';
				for( $lc = 0; $lc < sizeof((array) $info->languages); $lc++ ) {
					$values .= '###' . $info->languages[$lc] . '###' . $info->languages[$lc];
				}
				form_control_enum('init', $init, $values);
			form_control_end();
		}
		else 
		{
			form_control_start(htmlconstant('_SYSLOC_REMARK'));
				form_control_textarea('init', $init, SYSLOC_EDIT_WIDTH, SYSLOC_EDIT_HEIGHT);
			form_control_end();
		}
		
	$site->skin->dialogEnd();

	$site->skin->buttonsStart();
		form_button('sysloc_oknew', htmlconstant('_OK'), '');
		form_button('sysloc_cancelnew', htmlconstant('_CANCEL'));
	$site->skin->buttonsEnd();
	
	echo '</form>';

	// end page
	$site->pageEnd();
}




function sysloc_delete($f, $scope, $op, $id)
{
	global $site;

	// get information, on errors invoke folder overview
	$info = new SYS_LOC_CLASS($f);
	if( !$info->type ) {
		sysloc_overview($f);
		return; // error
	}

	$info->load_all_strings();

	// delete
	if( $scope == 'lang' ) {
		$old_level = error_reporting(0);
		if( !unlink("$info->folder/$id.$info->type") ) {
			$site->msgAdd(htmlconstant('_SYSLOC_ERRW', $id, "$info->folder/$id.$info->type"));
		}
		error_reporting($old_level);
	}
	else {
		$error_module = '';
		for( $lc = 0; $lc < sizeof((array) $info->languages); $lc++ ) {
			if( !$info->check_write_access($info->languages[$lc], $error_module) ) {
				$site->msgAdd($error_module);
				break;
			}
		}

		if( !$error_module ) {
			$info->delete_entry($id);
			for( $lc = 0; $lc < sizeof((array) $info->languages); $lc++ ) {
				$info->save_lang_strings($info->languages[$lc]);
			}
		}
	}

	// back to overview
	sysloc_overview($f);
}



/*****************************************************************************
 *  Global Part
 *****************************************************************************/

 

require_once('functions.inc.php');
require_lang('lang/sysloc');


// size definitions
$width = str_replace(' ', '', regGet("sysloc.edit.size", '40 x 5'));
list($width, $height) = explode('x', $width);
$width = intval($width);
if( $width < 3 || $width > 200 ) $width = 40;
$height = intval($height);
if( $height < 1 || $height > 99 ) $height = 5;

define('SYSLOC_EDIT_WIDTH', $width);
define('SYSLOC_EDIT_HEIGHT', $height);

define('SYSLOC_MAX_LANG_COLS', 3);

// get parameters
$req_f 		= $_REQUEST['f'];
$req_id		= $_REQUEST['id'];
$req_scope	= $_REQUEST['scope'];
$req_op		= $_REQUEST['op'];



if( isset($_REQUEST['editfromview']) ) // edit topic currently in help view?
{	
	global $site;
	$req_id = '';
	for(  $i = 0; $i <= 1; $i++ )
	{
		$req_f = $i? '../'.$site->adminDir.'/lang/help' : '../'.$site->adminDir.'/config/lang/help';
		$test = new SYS_LOC_CLASS($req_f);
		if( $test->type ) {
			$test->load_all_strings();
			if( isset($test->strings[$_SESSION['g_session_help_last_id']]) ) {
				$req_id = $_SESSION['g_session_help_last_id'];
				break;
			}
			else if( isset($test->strings[substr($_SESSION['g_session_help_last_id'], 0, strlen($_SESSION['g_session_help_last_id'])-1)]) ) {
				$req_id = substr($_SESSION['g_session_help_last_id'], 0, strlen($_SESSION['g_session_help_last_id'])-1);
				break;
			}
			else if( isset($test->strings[substr($_SESSION['g_session_help_last_id'], 0, strlen($_SESSION['g_session_help_last_id'])-1).'9']) ) {
				$req_id = substr($_SESSION['g_session_help_last_id'], 0, strlen($_SESSION['g_session_help_last_id'])-1).'9';
				break;
			}
		}
	}
	
	if( $req_id ) {
		$req_id .= "." . $_SESSION['g_session_language'];
	}
}


if( ($req_scope=='lang'||$req_scope=='entry') && $req_op=='new' ) 
{
	if( acl_check_access('SYSTEM.LOCALIZE'.($req_scope=='lang'? 'LANG' : 'ENTRIES'), -1, ACL_NEW) ) {
		sysloc_new($req_f, $req_scope, $req_op);
	}
	else {
		sysloc_overview($req_f);
	}
}
else if( ($req_scope=='lang'||$req_scope=='entry') && $req_op=='delete' ) 
{
	if( acl_check_access('SYSTEM.LOCALIZE'.($req_scope=='lang'? 'LANG' : 'ENTRIES'), -1, ACL_DELETE) ) {
		sysloc_delete($req_f, $req_scope, $req_op, $req_id);
	}
	else {
		sysloc_overview($req_f);
	}
}
else if( $req_id && acl_check_access('SYSTEM.LOCALIZEENTRIES', -1, ACL_EDIT) )
{
	sysloc_editentry($req_f, $req_id);
}
else
{
	sysloc_overview($req_f);
}



