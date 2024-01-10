<?php

require_lang('lang/imex');
require_lang('lang/overview');


class IMP_FUNCTIONS_CLASS
{

	function __construct($page, $mix)
	{
		$this->page = $page;
		$this->mix  = $mix;
	}
	

	function imp_render_page_start()
	{
		global $site;

		// save the last mix file
		if( isset( $this->mix ) && $this->mix != '' && $this->mix != regGet('import.lastfile', '') ) {
			regSet('import.lastfile', $this->mix, '');
			regSave();
		}
		
		// page start
		$site->title = htmlconstant('_IMPORT');
		$site->addScript('lib/imp/imp.js');
		$site->pageStart();

		$mix = isset($this->mix) ? $this->mix : '';
		$page = isset($this->page) ? $this->page : 0;
		$site->menuItem('mmainmenu', htmlconstant('_IMPORT'), '<a href="imp.php">');
		$site->menuLogoutUrl		= "imp.php?page=$page&mix=".urlencode($mix);
		$site->menuSettingsUrl		= "settings.php?reload=".urlencode($site->menuLogoutUrl);
		$site->menuHelpScope		= "iimport";
		$site->menuOut();
		
		$site->skin->workspaceStart();
				echo '&nbsp;';
		$site->skin->workspaceEnd();
		
		
		$site->skin->mainmenuStart();
			$site->skin->mainmenuItem( 'Mix-Datei ausw&auml;hlen' , "<a href=\"imp.php?page=files\">",  $this->page=='files'? 1 : 0);
			if( $this->page == 'options' || $this->page == 'import' ) {
				$site->skin->mainmenuItem( 'Mix-Datei importieren' , "<a href=\"imp.php?page=options&amp;mix=".urlencode($this->mix)."\">",  1);
			}
		$site->skin->mainmenuEnd();
		
		

	}



	function imp_render_page_end()
	{
		global $site;
		
		
		$site->pageEnd();
	}

};