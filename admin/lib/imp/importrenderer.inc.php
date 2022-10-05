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
		$gSessionUserID = isset($_SESSION['g_session_userid']) ? $_SESSION['g_session_userid'] : null;
		$importer->set_user( $gSessionUserID, acl_get_default_grp(), acl_get_default_access());
		$importer->set_progress_callback(array($this->progress_ob, 'progress_info'));
		if( $importer->import_do(
		      (isset($GLOBALS['g_temp_dir']) ? $GLOBALS['g_temp_dir'] : '') . '/imp-'.$gSessionUserID.'-'.$this->mix, 
		      (isset($_REQUEST['overwrite']) ? intval($_REQUEST['overwrite']) : null), 
		      (isset($_REQUEST['delete']) ? intval($_REQUEST['delete']) : null), 
		      (isset($_REQUEST['further_options']) ? $_REQUEST['further_options'] : null)
		    )
		   )
		{
		    if( isset($GLOBALS['site']) )
			 $GLOBALS['site']->msgAdd(htmlconstant('_IMP_IMPORTDONEMSG', $this->progress_ob->progress_time(), '<a href="log.php" target="_blank" rel="noopener noreferrer">', '</a>'), 'i');
		}
		else
		{
		    if( isset($GLOBALS['site']) )
			 $GLOBALS['site']->msgAdd('Importfehler, s. <a href="log.php" target="_blank" rel="noopener noreferrer">Protokoll</a> f&uuml;r weitere Details', 'e');
		}

		// UI end
		$this->imp_render_page_end();
		$url = 'imp.php?page=options&mix='.urlencode($this->mix);
		
		if( isset($_SESSION['mixfile_bl_cache']) )
		  unset($_SESSION['mixfile_bl_cache']); // force an update or the browsing cache - this is not really needed as we use the Update_time in importrenderer, however, it should be faster if there are no timestamps to check
		
		$this->progress_ob->js_redirect($url, 'Import abgeschlossen.');
		
		
	}
}