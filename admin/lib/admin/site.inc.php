<?php

class ADMIN_SITE_CLASS
{
	var $title;

	var $sitePath;

	var $menuBinParam;
	var $menuSettingsUrl;
	var $menuHelpScope;
	var $menuLogoutUrl;
	var $menuFreeObject;
	var $menuItems;
	
	private $msg; // $msg also contains messages already rendered (needed for counting messages); $_SESSION['g_session_msg'] contains unrendered messages 
	private $msgRendered;
	
	private $scripts;
	private $pageStarted;
	
	function __construct()
	{
		$this->title				= '';
		
		$this->sitePath				= '../';
		
		$temp = explode('/', $_SERVER['PHP_SELF']);
		$this->adminDir				= $temp[sizeof($temp)-2]; // contains eg. 'admin' now
		
		$this->menuBinParam			= 'dummy=dummy';
		$this->menuSettingsUrl		= '';
		$this->menuHelpScope		= '';
		$this->menuLogoutUrl		= '';
		$this->menuFreeObject		= '';
		$this->menuItems			= array();
		$this->scripts				= array();
		
		// init messages
		$this->msg					= array();
		$this->msgRendered			= 0;

		if( is_array($_SESSION['g_session_msg']) ) {
			$this->msg = $_SESSION['g_session_msg'];
		}
	}
	
	function initSkin()
	{
		$folder = regGet('skin.folder', 'skins/default');
		$writeBack = false;
		
		// be compatible with older settings (added on 19.05.2012 - may be deleted sometime)
		if( $folder == 'lb' ) { $folder = 'skins/lime';  $writeBack = true; }
		if( $folder == 'st' ) { $folder = 'skins/steel'; $writeBack = true; }
		
		// check for skin existance
		if( !$folder || !@file_exists("{$folder}/class.php") )	{ $folder = 'skins/default'; $writeBack = true; }
		if( $writeBack ) { regSet('skin.folder', $folder, 'skins/default'); }
		
		// create the skin object
		require_once("{$folder}/class.php");
		$skinClassName = strtr($folder, array('config/skins/'=>'', 'skins/'=>'', '-'=>'_'));
		$skinClassName = 'SKIN_' . strtoupper($skinClassName) . '_CLASS';
		$this->skin = new $skinClassName($folder);
	}
	
	function pageStart($param = 0)
	{
		if( !is_array($param) ) $param = array();
		
		echo "<!DOCTYPE html>\n"; // without a doctype, the Internet Explorer does not support newer features, eg. CSS child selectors; the simple doctype "html" is used eg. by heise, mozilla and others. should be enought.
		echo "<html>\n\n";
			echo "<head>\n";
				echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=iso-8859-1\" />\n";
				echo '<title>' . ($this->title? $this->title : htmlconstant('_CONST_SYSTEMNAME'))  . "</title>\n";
				if( ($favicon=regGet('logo.favicon.url', '')) ) {
					echo '<link rel="shortcut icon" type="image/ico" href="' .$favicon. '" />' . "\n";
				}

				// load CSS
				echo $this->skin->getCssTags($param);
				
				// load jQuery
				echo '<link rel="stylesheet" type="text/css" href="lib/jquery/css/ui-lightness/jquery-ui-1.10.4.custom.min.css" />' . "\n";
				echo '<script type="text/javascript" src="lib/jquery/js/jquery-1.10.2.min.js"></script>' . "\n";
				echo '<script type="text/javascript" src="lib/jquery/js/jquery-ui-1.10.4.custom.min.js"></script>' . "\n";

				// load internal JavaScript code (The language attribute on the script element is obsolete)
				$jsfile = require_lang_file('lang/js');
				if( $jsfile ) {
					echo '<script type="text/javascript" src="' .$jsfile. '?iv='.CMS_VERSION.'"></script>' . "\n";
				}
				echo '<script type="text/javascript" src="functions.js?iv='.CMS_VERSION.'"></script>' . "\n";
				
				for( $i = 0; $i < sizeof($this->scripts); $i++ ) {
					echo $this->scripts[$i] . "\n";
				}
				
			echo "</head>\n\n";
			
			echo '<body';
				if( $param['popfit'] )  {
					echo ' onload="popfit();"';
				}
			echo '>';
			
			$this->pageStarted = true;
	}
	
	function pageEnd()
	{
				// $this->msgRender(); -- es ist besser, wenn die Nachricht auf der nächsten Seite oben angezeigt wird - vor allem, wenn z.B. beim Export die Seite automatisch neu geladen wird
				
				$this->_poorMansCron();
				
			echo "</body>\n\n";
		echo '</html>';
	}

	private function _poorMansCron()
	{
		// calling this function makes sure, the cron job is called at least from time to time ...
		if( $_SESSION['g_session_userid'] )
		{
			$lastcall = regGet('cron.poormanscron', '0000-00-00 00:00:00', 'template');
			if( $lastcall < strftime("%Y-%m-%d %H:%M:00", time() - 30*60) /*call it about every 30 minutes*/ )
			{
				regSet('cron.poormanscron', strftime("%Y-%m-%d %H:%M:00"),  '0000-00-00 00:00:00', 'template');
				regSave();
				
				$apikey = regGet('export.apikey', '', 'template');
				echo '<img src="cron.php?apikey='.urlencode($apikey).'" width="1" height ="1" border="0" alt="" />';
			}
		}
	}
	
	function menuItem($type, $descr, $ahref)
	{
		$this->menuItems[] = array($type, $descr, $ahref);
	}

	
	function menuOut()
	{
		global $Admin_Unicode_Selector;
		global $Admin_Pcwebedit_Include;
		global $Table_Def;
	
		$this->skin->fixedHeaderStart();
		$this->skin->mainmenuStart();
		
			if( $_SESSION['g_session_userid'] ) 
			{
				$anything_hilited = 0;
				for( $t = 0; $t < sizeof($Table_Def); $t++ )
				{
					if( ($Table_Def[$t]->flags & TABLE_PRIMARY)
					 && ($Table_Def[$t]->name != 'user')
					 && acl_get_access($Table_Def[$t]->name.'.COMMON') ) // show only primary tables with user's access in menu
					{
						$hilite = 0;
						if( strpos($this->menuItems[0][2], "\"index.php?table={$Table_Def[$t]->name}\"") ) {
							$anything_hilited = 1;
							$hilite = 1;
						}
						
						$url = 'index.php?table=' . $Table_Def[$t]->name;
						if( $this->menuFreeObject ) {
							$url .= '&free_object='.$this->menuFreeObject;
						}
						
						$this->skin->mainmenuItem($Table_Def[$t]->descr, '<a href="' . isohtmlentities($url) . '">', $hilite);
					}
				}
				
				$url = 'etc.php';
				if( $this->menuFreeObject ) { $url .= '?free_object=' . $this->menuFreeObject; }
				
				if( !$anything_hilited && $this->menuItems[0][0]=='mmainmenu' )
				{
					$this->skin->mainmenuItem(htmlconstant('_ETC').' &gt;', "<a href=\"$url\">", 2 /*select as breadcrumb*/);
					
					$this->skin->mainmenuItem($this->menuItems[0][1], $this->menuItems[0][2], 1);
					$anything_hilited = 1;
				}
				else
				{
					$this->skin->mainmenuItem(htmlconstant('_ETC'), '<a href="' . isohtmlentities($url) . '">', $anything_hilited? 0 : 1);
				}
			}
			else 
			{
				$loginfile = 'index.php?enter_subsequent=1';
				if( defined('SECURE_HOST') ) {
					$this->skin->mainmenuItem(htmlconstant('_LOGIN_SECURE'), '<a href="https://' . SECURE_HOST . "/{$this->adminDir}/" .  $loginfile .  '">', $_SERVER['HTTPS']=='on'? 1 : 0);
					$this->skin->mainmenuItem(htmlconstant('_LOGIN_INSECURE'), '<a href="http://' . INSECURE_HOST . "/{$this->adminDir}/" . $loginfile .  '">', $_SERVER['HTTPS']!='on'? 1 : 0);
				}
				else {
					$this->skin->mainmenuItem(htmlconstant('_LOGIN_TITLE'), '<a href="' . $loginfile . '">', 1);
				}
			}
			
			$this->skin->mainmenuBreak();
			
			if( defined('LOGO_HTML') )
			{
				echo '&nbsp;' . LOGO_HTML . '&nbsp;';
			}			

			if( regGet('logo.image.url', '') ) 
			{
				$logo = '';
				$Admin_Logo_Size = GetImageSize(regGet('logo.image.url', ''));
				$Admin_Logo_Target = regGet('logo.image.dest.url', '');
				if( $Admin_Logo_Target ) {
					$logo .= '<a href="' .isohtmlentities($Admin_Logo_Target). '" ';
					if( $_SESSION['g_session_userid'] ) {
						$logo .= ' target="_blank"';
					}
					$logo .= '>';
				}
				$logo .= '<img src="' . regGet('logo.image.url', '') . '" '.  $Admin_Logo_Size[3] . ' alt="" />';
				if( $Admin_Logo_Target ) {
					$logo .= '</a>';
				}
				echo $logo;
			}
			
			echo '&nbsp;<a href="help.php?id='.($_SESSION['g_session_userid']?'isysinfo':'.').'" target="help" title="'.htmlconstant('_SYSINFO').'" onclick="return popup(this,500,380);"><small>V'.CMS_VERSION.'</small></a>&nbsp;';
		
		$this->skin->mainmenuEnd();

		$this->skin->submenuStart();
			
			$submenuItems = 0;
			
			if( $_SESSION['g_session_userid'] ) 
			{
				for( $i = ($anything_hilited? 1 : 0); $i < sizeof($this->menuItems); $i++ )
				{
					$this->skin->submenuItem($this->menuItems[$i][0], $this->menuItems[$i][1], $this->menuItems[$i][2]);
					$submenuItems++;
				}
			}

			if( !$submenuItems ) {
				echo '&nbsp;';
			}

		$this->skin->submenuBreak();								

			if( $_SESSION['g_session_userid'] ) 
			{
				// settings
				$a = '';
				if( $this->menuSettingsUrl ) {
					$a = '<a href="' . isohtmlspecialchars($this->menuSettingsUrl) . "\" target=\"settings\" onclick=\"return popup(this,600,500);\">";
				}
				$this->skin->submenuItem('msettings', htmlconstant('_SETTINGS'), $a);

				// Add. menu entries
				$i = 1;
				while( 1 )
				{
					$addTitle = regGet("menu.right.$i", '');
					if( $addTitle == '' ) break;
					$addTitle = explode('###', $addTitle);
					if( strpos($addTitle[1], '<a')===false ) $addTitle[1] = '<a href="' . $addTitle[1] . '">';
					$this->skin->submenuItem("madd$i", $addTitle[0], $addTitle[1]);
					$i++;
				}
			}
			
			// print
			if( $_SESSION['g_session_userid'] ) 
			{
				$a = '';
				if( $this->menuPrintUrl!='' ) {
					$a = '<a href="' . isohtmlentities($this->menuPrintUrl) . "\" target=\"prnt\" onclick=\"return popup(this,800,600);\">";
				}
				$this->skin->submenuItem('mprint', htmlconstant('_PRINT'), $a);
			}

			// help
			$this->menuHelpEntry($this->menuHelpScope);
			
			// logout
			if( $_SESSION['g_session_userid'] ) {
				$this->skin->submenuItem('mlogout', 
					htmlconstant('_LOGOUTNAME', isohtmlentities($_SESSION['g_session_userloginname']), '', ''), 
					  "<a href=\"logout.php?page=".urlencode($this->menuLogoutUrl).'">');
			}

		$this->skin->submenuEnd();
		
		$this->skin->fixedHeaderEnd();
		
		$this->msgRender();
	}
	
	function menuHelpEntry($scope)
	{
		$this->skin->submenuItem('mhelp', htmlconstant('_HELP'), "<a href=\"help.php?id={$scope}\" target=\"help\" onclick=\"return popup(this,500,380);\">");
	}

	function abort($errfile = '', $errline = -1, $errobj = '')
	{
		$msg = htmlconstant('_ERRACCESS');
		
		if( $errfile || $errline != -1 || $errobj ) {
			$msg .= "\n\n";
			if( $errfile ) { $msg .= " " . htmlconstant('_FILE'). ": $errfile; ";  }
			if( $errline ) { $msg .= " " . htmlconstant('_LINE'). ": $errline; ";  }
			if( $errobj  ) { $msg .= " " . htmlconstant('_OBJECT'). ": $errobj; "; }
		}
		
		$cancel_url = 'etc.php';
		$this->pageStart();
		if( $_REQUEST['table'] ) {
			$table_def = Table_Find_Def($_REQUEST['table']);
			if( $table_def ) {
				$this->menuItem('mmainmenu', $table_def->descr, "<a href=\"index.php?table=".$_REQUEST['table']."\">");
				$cancel_url = "index.php?table=".$_REQUEST['table'];
			}
		}
		$this->menuOut();
		$this->skin->msgStart('e', 'no close');
			echo str_replace("\n", '<br />', $msg);
		$this->skin->msgEnd();
		$this->skin->buttonsStart();
			form_clickbutton($cancel_url, htmlconstant('_CANCEL'));
		$this->skin->buttonsEnd();
		$this->pageEnd();
	
		exit();
	}
	
	function addScript($script)
	{
		if( substr($script,-4)=='.css' ) {
			$script = '<link rel="stylesheet" type="text/css" href="' . $script . '?iv=' . CMS_VERSION . '" />';
		}
		else {
			$script = '<script type="text/javascript" src="' . $script . '?iv=' . CMS_VERSION . '"></script>';
		}
		
		if( $this->pageStarted ) {
			echo $script;
		}
		else {
			$this->scripts[] = $script;
		}
	}
	
	function htmldeentities($str)
	{
		global $transentities;
		
		if( !is_array($transentities) ) {
			$transentities = array_flip(get_html_translation_table(HTML_ENTITIES, ENT_COMPAT|ENT_HTML401, 'ISO-8859-1'));
		}
		
		return strtr($str, $transentities);
	}


	//
	// message functions
	//


	function msgAdd($msg, $type = 'e' /* [e]rror, [w]arning, [i]information or [s]aved */)
	{
		if( $msg ) {
			$this->msg[] = "$type:$msg";
			
			$_SESSION['g_session_msg'][] = "$type:$msg";
		}
	}
	
	function msgReset($type = 'e')
	{
		if( $type == '' /*all types*/ ) {
			$this->msg = array();
		}
		else {
			$newmsg = array();
			for( $i = 0; $i < sizeof($this->msg); $i++ ) {
				if( substr($this->msg[$i], 0, 1) != $type ) {
					$newmsg[] = $this->msg[$i];
				}
			}
			$this->msg = $newmsg;
		}
		
		$_SESSION['g_session_msg'] = $this->msg;
	}
	
	function msgCount($type = 'e')
	{
		if( $type == '' /*all types*/ ) {
			return sizeof($this->msg);
		}
		else {
			$cnt = 0;
			for( $i = 0; $i < sizeof($this->msg); $i++ ) {
				if( substr($this->msg[$i], 0, 1) == $type ) {
					$cnt++;
				}
			}
			return $cnt;
		}
	}
	
	function msgRender() // private
	{
		$msg = array();
		for( $m = $this->msgRendered; $m < sizeof($this->msg); $m++ ) 
		{
			$currType = substr($this->msg[$m], 0, 1);
			$currMsg  = substr($this->msg[$m], 2);
			
			if( $msg[$currType] ) {
				$msg[$currType] .= "\n$currMsg";
			}
			else {
				$msg[$currType] = $currMsg;
			}
		}
		
		if( sizeof($msg) ) {
			reset($msg);
			while( list($name, $value) = each($msg) ) {
				$value = trim($value);
				while(strpos($value, "\n\n\n")) { $value = str_replace("\n\n\n", "\n\n", $value); }
				$value = str_replace("\n", "<br />", $value);
				$this->skin->msgStart($name);
					echo $value;
				$this->skin->msgEnd();
			}
		}
	
		$this->msgRendered = sizeof($this->msg); // prepare for future message rendering
		
		$_SESSION['g_session_msg'] = array();
	}
};
