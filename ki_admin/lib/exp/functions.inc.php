<?php

require_lang('lang/imex');
require_lang('lang/overview');

function exp_format_compare($a, $b)
{
	if( $a == 'mix' ) $a = 'aaamix';
	if( $b == 'mix' ) $b = 'aaamix';
	$cmp = strcmp($a, $b);
	return $cmp;
}


class EXP_FUNCTIONS_CLASS
{
	public $scanner;

	function __construct()
	{
		$this->scanner = new G_TEMPDIR_CLASS;
	}
	
	function exp_render_page_start($expFormat)
	{
		global $site;

		// page start
		$site->title = htmlconstant('_EXPORT');
		$site->pageStart();

		$site->menuItem('mmainmenu', htmlconstant('_EXPORT'), '<a href="exp.php">');
		$site->menuSettingsUrl		= "settings.php?reload=".urlencode("exp.php?page=$expFormat");
		$site->menuHelpScope		= "$expFormat.iexport";
		$site->menuLogoutUrl		= "exp.php";
		$site->menuOut();
		
		$site->skin->workspaceStart();
			echo '&nbsp;';
		$site->skin->workspaceEnd();
		
		$site->skin->mainmenuStart();
			$all_exp_plugins = array();

			for( $i = 0; $i <= 1; $i++ ) 
			{		
				$handle = @opendir($i==0? 'lib/exp/' : 'config/exp/');
				if( $handle ) {
					while( $folderentry = readdir($handle) ) {
						if( preg_match('/^format([a-z0-9]+)\.inc\.php$/', $folderentry, $matches) ) {
							$all_exp_plugins[] = $matches[1];
						}
					}
					closedir($handle);
				}
			}

			usort($all_exp_plugins, 'exp_format_compare');
		
			$options = '';
			$debugParam = isset($_REQUEST['debug']) && $_REQUEST['debug'] ? "&debug=".$_REQUEST['debug'] : '';
			for( $i = 0; $i < sizeof($all_exp_plugins); $i++ )
			{
				$site->skin->mainmenuItem(htmlconstant('_EXP_'.strtoupper($all_exp_plugins[$i])),
					"<a href=\"exp.php?page={$all_exp_plugins[$i]}{$debugParam}\">", 
					$expFormat==$all_exp_plugins[$i]? 1 : 0);
			}
			
			$site->skin->mainmenuItem(htmlconstant('_EXP_FILES'), "<a href=\"exp.php?page=files$debugParam\">", $expFormat=='files'? 1 : 0);
		$site->skin->mainmenuEnd();
	}



	function exp_render_page_end()
	{
		global $site;
		
		$site->pageEnd();
	}

};