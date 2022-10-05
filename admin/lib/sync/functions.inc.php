<?php

require_lang('lang/imex');
require_lang('lang/overview');

class SYNC_FUNCTIONS_CLASS
{

	function __construct($page, $jobid)
	{
		$this->page = $page;
		$this->jobid  = intval($jobid);
	}
	

	function sync_render_page_start()
	{
		global $site;

		// page start
		$site->title = htmlconstant('_SYNC');
		$site->pageStart();

		$site->menuItem('mmainmenu', htmlconstant('_SYNC'), '<a href="sync.php">');
		$site->menuLogoutUrl		= "sync.php?page=$this->page&job=".$this->jobid;
		$site->menuSettingsUrl		= "settings.php?reload=".urlencode($site->menuLogoutUrl);
		$site->menuHelpScope		= "isync";
		$site->menuOut();
		
		$site->skin->workspaceStart();
				echo '&nbsp;';
		$site->skin->workspaceEnd();
		
		
		$site->skin->mainmenuStart();
			$site->skin->mainmenuItem('&Uuml;bersicht', "<a href=\"sync.php\">",  $this->page=='overview'? 1 : 0);
			if( isset( $this->page ) && $this->page == 'editjob'  ) {
				$title = $this->jobid? 'Synchronisierungsaufgabe bearbeiten' : 'Neue Synchronisierungsaufgabe';
				$site->skin->mainmenuItem($title, "<a href=\"imp.php?page=editjob&amp;job=".$this->jobid."\">",  1);
			}
			$site->skin->mainmenuEnd();
	}



	function sync_render_page_end()
	{
		global $site;
		
		$site->pageEnd();
	}

};

