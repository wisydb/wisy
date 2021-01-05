<?php

class IMP_IMPORTRENDERER_CLASS extends IMP_FUNCTIONS_CLASS
{
	function __construct($page, $mix)
	{
		parent::__construct($page, $mix);
	}
	
	
	function handle_request()
	{
		ignore_user_abort(1);
		set_time_limit(0);

		// UI start
		$this->progress_ob = new EXP_PROGRESS_CLASS;
		$this->imp_render_page_start();
		$GLOBALS['site']->skin->submenuStart();
			echo '&nbsp;';
		$GLOBALS['site']->skin->submenuEnd();
		$this->progress_ob->render_placeholder(htmlconstant('_IMP_IMPORTINPROGRESS', '<i>'.isohtmlspecialchars($this->mix).'</i>'));


		// the import
		$importer = new IMP_IMPORTER_CLASS;
		$importer->set_user( $_SESSION['g_session_userid'], acl_get_default_grp(), acl_get_default_access());
		$importer->set_progress_callback(array($this->progress_ob, 'progress_info'));
		if( $importer->import_do($GLOBALS['g_temp_dir'].'/imp-'.$_SESSION['g_session_userid'].'-'.$this->mix, intval($_REQUEST['overwrite']), intval($_REQUEST['delete']), $_REQUEST['further_options']) )
		{
			$GLOBALS['site']->msgAdd(htmlconstant('_IMP_IMPORTDONEMSG', $this->progress_ob->progress_time(), '<a href="log.php" target="_blank" rel="noopener noreferrer">', '</a>'), 'i');
		}
		else
		{
			$GLOBALS['site']->msgAdd('Importfehler, s. <a href="log.php" target="_blank" rel="noopener noreferrer">Protokoll</a> f&uuml;r weitere Details', 'e');
		}

		// UI end
		$this->imp_render_page_end();
		$url = 'imp.php?page=options&mix='.urlencode($this->mix);
		
		unset($_SESSION['mixfile_bl_cache']); // force an update or the browsing cache - this is not really needed as we use the Update_time in importrenderer, however, it should be faster if there are no timestamps to check
		$this->progress_ob->js_redirect($url, 'Import abgeschlossen.');
		
		
	}
}
