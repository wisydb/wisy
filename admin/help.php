<?php



/*=============================================================================
Help to the Program
===============================================================================

file:	
	help.php
	
author:	
	Bjoern Petersen

parameters:
	id			the topic to show, eg. "ieditrecord".
				you can give multiple topics using "." as a seperator. in this 
				case the first topic found is shown.
	
				"." is a special id: the login help is shown without the 
				possibility to go to other help topics.
	
				if ommited, the index is shown.
			
subsequent parameters:
	printdlg	the topic to show the print dialog for
	print		the topic to print
	printchapter

=============================================================================*/


/******************************************************************************
 * First of all, a little register-globals test
 ******************************************************************************/
 
if( isset($id) ) {
	define('GLOBALS_ENABLED', true);
}


/******************************************************************************
 * Functions
 ******************************************************************************/

 
 
 function help_define($currId, $currText)
{
	global $help_id2text;
	global $help_id2title;
	global $help_id2chapter;
	global $help_title2id;
	global $help_sort2id;

	if( $currText != '' )
	{
		// get title
		$p = strpos($currText, "\n");
		$currTitle = trim(substr($currText, 1, $p-2));
		
		// get key / chapter
		$currChapter = 1;
		$currId = strtolower(substr($currId, 6));
		if( intval(substr($currId, -1)) || substr($currId, -1)=='0' ) {
			$currChapter = intval(substr($currId, -1));
			$currId = substr($currId, 0, strlen($currId)-1);
			if( $currChapter == 9 ) {
				$currChapter = 8;
			}
		}
		
		// store topic
		$help_id2chapter[$currId]							= $currChapter;
		$help_id2text[$currId]								= $currText;
		$help_id2title[$currId]								= $currTitle;
		$help_title2id[$currTitle]							= $currId;
		$help_sort2id[g_eql_normalize_natsort($currTitle)]	= $currId;
	}
}



function arabic2roman($num_arabic) // converts an arabic number to a roman number
{ 
	$unit_roman	= array( "M","CM", "D", "CD", "C", "XC", "L", "XL", "X", "IX", "V", "IV", "I");
	$unit_arabic= array(1000, 900, 500,  400, 100,   90,  50,  40,   10,    9,   5,    4,   1);
	$num_roman	= "";
	$num_arabic	= intval($num_arabic);

	if($num_arabic <= 0) return '';

	for($i = 0; $i < sizeof($unit_arabic); $i++)
	{
		while ($num_arabic >= $unit_arabic[$i])
		{
			$num_roman .= $unit_roman[$i];
			$num_arabic -= $unit_arabic[$i];
		}
	}

	return $num_roman;
}



function find_id(&$id, &$idprev, &$idnext)
{
	global $help_sort2id;
	
	$idprev = '';
	$idnext = '';
	$idfound = 0;
	reset($help_sort2id);
	foreach($help_sort2id as $dummy => $currId)
	{
		if( $idfound && !$idnext ) {
			$idnext = $currId;
		}
		
		if( $currId == $id ) {
			$idfound = 1;
		}
		
		if( !$idfound ) {
			$idprev = $currId;
		}
	}
	
	if( !$idfound ) {
		$id = '';
	}
}



function render_page($id) // render single page
{
	global $wiki2html;
	global $help_id2text;
	
	return $wiki2html->run($help_id2text[$id] /*. "\n\n[[stat()]]"*/);
}




function render_sysinfo_scan__($startFolder = '.') // render the system information
{
	$newestMtime = 0;
	$subdirs = array();
	$handle = @opendir($startFolder);
	if( $handle ) {
		while( $folderentry = readdir($handle) ) 
		{
			if( $folderentry!='.'
			 && $folderentry!='..'
			 && is_dir("$startFolder/$folderentry") )
			{
				$subdirs[] = "$startFolder/$folderentry";
			}
			else if( preg_match('/.(php|html?|js|css)$/i', $folderentry) )
			{
				$mtime = @filemtime("$startFolder/$folderentry");
				if( $mtime > $newestMtime ) {
					$newestMtime = $mtime;
				}
			}
		}
	}

	for( $i = 0; $i < sizeof($subdirs); $i++ ) {
		$mtime = render_sysinfo_scan__($subdirs[$i]);
		if( $mtime > $newestMtime ) {
			$newestMtime = $mtime;
		}
	}
	
	return $newestMtime;
}
function render_sysinfo_item__(&$ret, $descr, $value)
{
	$ret .= "<tr>";
		$ret .= "<td nowrap=\"nowrap\" align=\"right\" valign=\"top\">$descr:</td>";
		$ret .= "<td>&nbsp;&nbsp;</td>";
		$ret .= "<td valign=\"top\">$value</td>";
	$ret .= "</tr>";
}
function render_b44t_logo()
{
	return '<p align="center">
				&nbsp;<br />
				&nbsp;<br />
				<a href="http:/'.'/b44t.com" target="_blank" rel="noopener noreferrer"><img src="lang/help/files/b44t-81x50.png" width="81" height="50" alt="[b44t.com]" title="" border="0"></a>
			 </p>
			 <p align="center">
				Program by <a href="http:/'.'/b44t.com" target="_blank" rel="noopener noreferrer">Bj&ouml;rn Petersen Software Design and Development</a>.
			 </p><br />';
}
function render_sysinfo()
{
	//$ret = '&nbsp;<br />';
	
	$systemname = htmlconstant('_CONST_SYSTEMNAME');
	$ret .= '<p><b>'.$systemname.' '.CMS_VERSION.'</b></p>';
	
	$ret .= '<table cellpadding="0" cellspacing="0" border="0">';
		
		// program 
		$temp = CMS_VERSION.', ' . sql_date_to_human(strftime('%Y-%m-%d %H:%M:%S', render_sysinfo_scan__()), 'datetime');
		$changelogurl = htmlconstant('_CONST_CHANGELOGURL');
		if( $changelogurl ) $temp .= ' - <a href="'.$changelogurl.'" target="_blank" rel="noopener noreferrer">changelog...</a>';
		render_sysinfo_item__($ret, htmlconstant('_SYSINFO_XVERSION', $systemname), $temp);

		// php
		$temp = phpversion();
		if( defined('GLOBALS_ENABLED') || !isset($_REQUEST['id']) /*if $_REQUEST['id'] is not set, we cannt safely set GLOBALS_ENABLED */ ) {
			$temp .= ", register"."_globals=".ini_get('register'.'_globals')." (for security reasons, it is recommended to switch register"."_globals off)";
		}
		if( acl_get_access('SYSTEM.*') ) $temp .= " - <a href=\"help.php?phpinfo\" target=\"_blank\" rel=\"noopener noreferrer\">phpinfo()...</a>";
		render_sysinfo_item__($ret, htmlconstant('_SYSINFO_XVERSION', 'PHP'), $temp);
			  
		// Password encryption methods _available_ by PHP (just for information, currently we _use_ MD5 with 12 character Salt (default on PHP crypt()))
		// PHP 5.3 supports crypt() with blowfish and SHA.
		// To upgrade passwords, we could use a rehashing on login or rehashing the old MD5-Values. Just some ideas.
		/*
		$pi = '';
		function crypt_avail($method) { return (defined($method) && constant($method)==1)? true : false; }
		if( crypt_avail('CRYPT_STD_DES') ) { $pi .= 'Standard DES, ';	}
		if( crypt_avail('CRYPT_EXT_DES') ) { $pi .= 'Extended DES, ';	} // avail in PHP 5.3
		if( crypt_avail('CRYPT_MD5')	 ) { $pi .= 'MD5, ';			}
		if( crypt_avail('CRYPT_BLOWFISH')) { $pi .= 'Blowfish, ';		} // avail in PHP 5.3
		if( crypt_avail('CRYPT_SHA256')  ) { $pi .= 'SHA-256, ';		} // avail in PHP 5.3
		if( crypt_avail('CRYPT_SHA512')  ) { $pi .= 'SHA-512, ';		} // avail in PHP 5.3
		$pi .= 'Salt Length=' . (defined('CRYPT_SALT_LENGTH')? CRYPT_SALT_LENGTH : '?');
		render_sysinfo_item__($ret, 'Password encryption', $pi);
		*/
		
		// database
		$db = new DB_Admin;
		render_sysinfo_item__($ret, htmlconstant('_SYSINFO_DATABASE'), $db->Database);
		
		// sync info
		$sync_tools = new SYNC_TOOLS_CLASS();
		if( ($syncinfo=$sync_tools->get_sync_info())!==false )
		{
			$syncable = $syncinfo['dbs'] == '*'? 'Daten <i>nicht vollst&auml;ndig synchronisierbar</i>' : 'Daten <i>sind synchronisierbar</i>';
			
			$syncable .= '<br />Datenbankkennung: '.$syncinfo['offset'];
			$syncable .= "<br />ID-Algorithmus: <i>N</i>*".$syncinfo['inc']."+".$syncinfo['offset'] . ', Minimum: '.$syncinfo['start'];
			$syncable .= "<br />Kommentar: ".isohtmlspecialchars($syncinfo['msg']);

			/*
			$sync_tools->validate_sync_values($msgsync, $msgtype);
			if( $msgsync != '' )
			{
				echo $msgsync;
			}
			*/

		}
		else
		{
			$syncable = 'Daten sind <i>nicht synchronisierbar</i>';
		}
		render_sysinfo_item__($ret, 'Synchonisierbarkeit', 
			$syncable);
		
	$ret .= '</table>';

	$ret .= render_b44t_logo();
	
	return $ret;
}



require_once('wiki2html.inc.php');
class HELP_WIKI2HTML_CLASS extends WIKI2HTML_CLASS 
{
	function pageExists($title)
	{
		global $help_title2id;
		global $help_id2title;
		return ($help_title2id[$title] || $help_id2title[$title])? 1 : '???';
	}
	
	function pageUrl($title, $pageExists)
	{
		if( $pageExists ) {
			global $help_title2id;
			return "help.php?back=1&id=" . ($help_title2id[$title]? $help_title2id[$title] : $title);
		}
		else {
			return "help.php?back=1";
		}
	}
	
	function pageFunction($name, $param, &$state)
	{
		switch( $name )
		{
			case 'box':
				$state = 2;
				if( trim($param) == '0' ) {
					$this->boxStyle = '';
				}
				else {
					$this->boxStyle = $_REQUEST['print']? ' style="border:1px solid #000000; padding:6px;"' : ' style="background-color:#DDDDDD; padding:6px;"';
				}
				return '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td valign="top"'.$this->boxStyle.'>';

			case 'boxwrap':
				$state = 2;
				return '</td><td valign="top"'.$this->boxStyle.'>&nbsp;&nbsp;</td><td valign="top"'.$this->boxStyle.'>';

			case 'boxend':
				$state = 2;
				return '</td></tr></table>';

			case 'sysinfo':
				$state = 2;
				return render_sysinfo();
			
			case 'toplinks':
				if( $_REQUEST['print'] ) {
					$state = 1;
					return ''; // no toplinks in print
				}
				break; // default processing

			case 'iframe':				
				if( $_REQUEST['print'] ) {
					$state = 1;
					return '...'; // no iframes in print
				}
				break; // default processing
				
			case 'content':
				if( $_REQUEST['print'] ) {
					$state = 1;
					return ''; // no content in print
				}
				break;
			
			case 'menu':
				if( !$_REQUEST['print'] )
				{
					global $site;
					$ret = '';
					$param = explode(',', $param);
					echo $site->skin->submenuStart();
					for( $i = 0; $i < sizeof($param); $i+=2 ) {
						$temp = trim($param[$i]);
				 		echo $site->skin->submenuItem('mlink', trim($param[$i+1]), $_REQUEST['id']==$temp? '' : "<a href=\"help.php?id=$temp&amp;back=1\">");
					}
					echo $site->skin->submenuBreak();
						echo "&nbsp;";
					echo $site->skin->submenuEnd();
				}
				$state = 1;
				return ''; // done
		}
	}

	function renderA($text, $type, $href, $tooltip, $pageExists)
	{
		if( $_REQUEST['print'] ) {
			if( $type == 'footnote' || $type == 'footref' ) {
				return "[$text]";
			}
			else if( $text == '?' )
			{
				return '';
			}
			else {
				return $text . ($pageExists? '' : '???');
			}
		}
		else {
			return parent::renderA($text, $type, $href, $tooltip, $pageExists);
		}
	}

	function renderH($html, $level)
	{	
		if( $this->h1AltText ) {
			global $site;
			$html = $this->h1AltText;	
			$this->h1AltText = '';
		}
		return parent::renderH($html, $level);
	}
	
	function renderPre($text)
	{
		$style = $_REQUEST['print']? ' style="border:1px solid #000000; padding:6px;"' : ' style="background-color:#DDDDDD; padding:6px;"';
		return "<pre$style>$text</pre>";
	}
}



/******************************************************************************
 * Global Part
 ******************************************************************************/


// get parameters
$id = $_REQUEST['id'];
$idlast = $_REQUEST['idlast'];
$chapter = $_REQUEST['chapter'];
$back = $_REQUEST['back'];
$printdlg = $_REQUEST['printdlg'];
$print  = $_REQUEST['print'];
$printchapter = $_REQUEST['printchapter'];

// render login help screen - should be first to avoid a login screen
if( $id == '.' )
{
	define('G_SKIP_LOGIN', 1);
	require_once('functions.inc.php');
	require_lang('lang/login');
	$site->title = htmlconstant('_HELP');
	$site->pageStart(array('popfit'=>1));
		echo '<br />';
		$site->skin->mainmenuStart();
			$site->skin->mainmenuItem(htmlconstant('_HELP'), "<a href=\"help.php?id=.\">", 1);
		$site->skin->mainmenuBreak();
			echo "&nbsp;";
		$site->skin->mainmenuEnd();
		$site->skin->submenuStart();
			echo "&nbsp;";
		$site->skin->submenuBreak();
			echo "&nbsp;";
		$site->skin->submenuEnd();
		echo '<table cellpadding="8" cellspacing="0" border="0"><tr><td>';
			echo nl2br(htmlconstant('_LOGIN_HELP'));
		echo '</td></tr></table>';
		echo render_b44t_logo();
	$site->pageEnd();
	exit();
}

// includes
require_once('functions.inc.php');
require('eql.inc.php');
require_lang('lang/sysinfo');
require_lang('lang/overview');

// get all help texts
$help_chapters = array(1, 2, 8, 0);
$help_id2chapter= array();
$help_id2text	= array();
$help_id2title	= array();
$help_title2id	= array();
$help_sort2id	= array();

require_lang('lang/help');
require_lang('config/lang/help');

ksort($help_sort2id);

// check given id
$ids = explode('.', $id);
for( $i = 0; $i < sizeof($ids); $i++ ) {
	$id = $ids[$i];
	find_id($id, $idprev, $idnext);
	if( $id ) {
		break;
	}
}

if( $id ) {
	$index = 0;
}
else {
	$index = 1;
	$id = $idlast;
	find_id($id, $idprev, $idnext);
	if( !$id ) {
	    reset($help_sort2id);
	    
	    $dummy = array_keys($help_sort2id);
	    $dummy = $dummy[0]; // array_key_first() only > php7
	    $id = array_values($help_sort2id);
	    $id = $id[0];
	    
	    find_id($id, $idprev, $idnext);
	}
}

// create parser
$wiki2html = new HELP_WIKI2HTML_CLASS;

// render help print dialog
if( $printdlg ) 
{
	require_lang('lang/print');
	$site->title = htmlconstant('_HELP').' - '.htmlconstant('_PRINT');
	$site->pageStart(array('popfit'=>1));
	form_tag('help_print_form', 'help.php', '', '', 'get');
	form_hidden('print', $printdlg);
		$site->skin->submenuStart();
			echo htmlconstant('_PRINT');
		$site->skin->submenuBreak();
			echo '&nbsp;';
		$site->skin->submenuEnd();
		$site->skin->dialogStart();
			form_control_start(htmlconstant('_PRINT_AREA'));
				$options = 'page###' . htmlconstant('_PRINT_AREA_CURRENTPAGE');
				for( $i = 0; $i < sizeof((array) $help_chapters)-1; $i++ ) {
					$options .= "###{$help_chapters[$i]}###" . htmlconstant("_SYSINFO_HELP_CHAPTER{$help_chapters[$i]}");
				}
				form_control_radio('printchapter', 'page', $options);
			form_control_end();
			form_control_start(htmlconstant('_PRINT_FONTSIZE'));
				$options = '';
				for( $i = 6; $i <= 18; $i++ ) {
					$options .= $options? '###' : '';
					$options .= "$i###".htmlconstant('_PRINT_FONTSIZENPT', $i);
				}
				form_control_enum('pagefontsize', regGet('print.fontsize.pt', 10), $options);
			form_control_end();
			form_control_start(htmlconstant('_PRINT_PAGEBREAK'));
				form_control_check('pagebreak', regGet('print.pagebreak.onoff', 0));
			form_control_end();
		$site->skin->dialogEnd();
		$site->skin->buttonsStart();
			form_button('ok', htmlconstant('_PRINT'));
			form_button('cancel', htmlconstant('_CANCEL'), 'window.close();return false;');
		$site->skin->buttonsEnd();
	echo '</form>';
	$site->pageEnd();
	exit();	
}

// do print
if( $print )
{
	regSet('print.fontsize.pt', $pagefontsize, 10);
	regSet('print.pagebreak.onoff', $pagebreak, 0);
	regSave();
	
	require_lang('lang/print');
	$site->addScript('print.js');
	$site->pageStart(array('css'=>'print', 'pt'=>$pagefontsize));
		if( $printchapter == 'page' )
		{
			$wiki2html->h1AltText = isohtmlentities(strtoupper($help_id2title[$print]));
			echo render_page($print);
		}
		else
		{
			echo '<h1>'.isohtmlentities(strtoupper($site->htmldeentities(htmlconstant('_CONST_SYSTEMNAME'))))." V$g_version</h1>";
			echo '<h1>'.isohtmlentities(strtoupper($site->htmldeentities(htmlconstant("_SYSINFO_HELP_CHAPTER".$printchapter)))).'</h1>';
			echo '<table cellpadding="0" cellspacing="0" border="0">';
				$i = 1;
				reset($help_sort2id);
				foreach($help_sort2id as $currSort => $currId)
				{
					if( $help_id2chapter[$currId] == $printchapter )
					{
						echo '<tr>';
							echo '<td nowrap="nowrap" valign="top">'.arabic2roman($i++).'.&nbsp;&nbsp;</td>';
							echo '<td>'.isohtmlentities(strtoupper($help_id2title[$currId])).'</td>';
						echo '</tr>';
					}
				}
			echo '</table><br />';			
			
			$i = 1;			
			reset($help_sort2id);
			foreach($help_sort2id as $currSort => $currId)
			{
				if( $help_id2chapter[$currId] == $printchapter )
				{
					if( $pagebreak ) {
						echo '<div style="page-break-after:always;"></div>';
					}
					else {
						echo '<p style="page-break-after:avoid;">&nbsp;</p>';
					}

					$wiki2html->h1AltText = arabic2roman($i++).'.&nbsp;&nbsp;'.isohtmlentities(strtoupper($help_id2title[$currId]));
					echo render_page($currId);
				}
			}
		}
	$site->pageEnd();
	exit();
}



// render phpinfo()
if( isset($_REQUEST['phpinfo']) ) {
	if( !acl_get_access('SYSTEM.*') ) die('no rights');
	phpinfo();
	exit();
}

// render normal help screen
$site->title = htmlconstant('_HELP') . ($index? '' : (' - '.isohtmlentities($help_id2title[$id])));
$site->pageStart(array('popfit'=>1));
	echo '<br />';

	if( !isset($chapter) ) {
		$chapter = $help_id2chapter[$id? $id : $idlast];
	}

	
	echo '<a name="top"></a>';
	$site->skin->mainmenuStart();
	for( $i = 0; $i < sizeof((array) $help_chapters); $i++ )  {
			$descr = $site->htmldeentities(htmlconstant("_SYSINFO_HELP_CHAPTER{$help_chapters[$i]}"));
			$title = '';
			if( $chapter!=$help_chapters[$i] && strlen($descr)>10 && $i != sizeof($help_chapters)-1 ) {
				$title = ' title="'.isohtmlentities($descr).'"';
				$descr = isohtmlentities(substr($descr, 0, 8)) . '..';
			}
			else {
				$title = '';
				$descr = isohtmlentities($descr);
			}
			$site->skin->mainmenuItem($descr, "<a href=\"help.php?back=1&idlast=$id&chapter={$help_chapters[$i]}\"$title>", $chapter==$help_chapters[$i]? 1 : 0);
		}
	$site->skin->mainmenuBreak();
		echo '&nbsp;';
	$site->skin->mainmenuEnd();

	$site->skin->submenuStart();
		$site->skin->submenuItem('mindex', htmlconstant('_SYSINFO_HELP_INDEX'), $index? '' : "<a href=\"help.php?back=1&idlast=$id\"$title>");
		$site->skin->submenuItem('mback', htmlconstant('_BACK'), $back? "<a href=\"\" onclick=\"history.back(); return false;\">" : "");
	$site->skin->submenuBreak();
		$site->skin->submenuItem('mprint', htmlconstant('_PRINT'), "<a href=\"help.php?printdlg=$id\" target=\"prnthelp\" onclick=\"return popup(this,800,600);\">");
		$site->skin->submenuItem('mhelp', htmlconstant('_HELP'), "<a href=\"help.php?back=1&id=ihelponhelp\">");
	$site->skin->submenuEnd();

		if( $index )
		{
			echo '<table cellpadding="8" cellspacing="0" border="0"><tr><td>';
				echo '<h1>' . htmlconstant('_SYSINFO_HELP_INDEX') . '</h1>';
				echo '<table cellpadding="0" cellspacing="0" border="0"><tr><td valign="top">';
					reset($help_sort2id);
					$num_themes = 0;
					foreach($help_sort2id as $currSort => $currId)
					{
						if( $chapter == 0
						 || $help_id2chapter[$currId] == $chapter )
						{
							$num_themes++;
						}
					}	
						
					reset($help_sort2id);
					$last_char = '';
					$item_count = 0;
					$break_done = 0;
					foreach($help_sort2id as $currSort => $currId)
					{
						if( $chapter == 0
						 || $help_id2chapter[$currId] == $chapter )
						{
							if( $last_char != '' && $last_char != $currSort{0} ) {
								if( !$break_done && $item_count >= $num_themes/2 ) {
									echo '</td><td>&nbsp;&nbsp;&nbsp;</td><td valign="top">';
									$break_done = 1;
								}
								else {
									echo '<br />';
								}
							}
							
							$title = $help_id2title[$currId];
							echo '<a href="help.php?back=1&id=' .$currId. '">';
								if( $last_char != $currSort{0} ) {
									$last_char = $currSort{0};
									echo '<big><b>'.isohtmlentities(substr($title, 0, 1)) .'</b></big>'.isohtmlentities(substr($title, 1));
								}
								else {
									echo isohtmlentities($title);
								}
							echo '</a>';
							echo '<br />';
							$item_count++;
						}
					}
				echo '</td></tr></table>';
			echo '</td></tr></table>';
		}
		else
		{
			$ret = render_page($id);
			echo '<table cellpadding="8" cellspacing="0" border="0"><tr><td>';
				echo $ret;
			echo '</td></tr></table>';

			// save ID to make synchronisation from the system localisation possible
			$_SESSION['g_session_help_last_id'] = '_HELP_'.strtoupper("$id$chapter");
		}


$site->pageEnd();
