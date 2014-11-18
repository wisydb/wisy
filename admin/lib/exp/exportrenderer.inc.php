<?php



class EXP_EXPORTRENDERER_CLASS extends EXP_FUNCTIONS_CLASS
{
	private $expFormat;	// the export's name
	private $expDescr;	// the export's description
	private $ob;		// the export's object
	
	private $_allocated_files;

	function render_export_dialog()
	{
		global $site;
		global $Table_Def;

		$this->exp_render_page_start($this->expFormat);
			$site->skin->submenuStart();
				echo htmlconstant('_EXP_TITLESETTINGS', $this->expDescr);
			$site->skin->submenuBreak();
				echo '&nbsp;';
			$site->skin->submenuEnd();

			form_tag('form_export', 'exp.php', '', '', ($this->expFormat=='mix'||$this->expFormat=='csv')? 'GET' : 'POST');
			form_hidden('exp', $this->expFormat);
			form_hidden('debug', $_REQUEST['debug']);
			form_hidden('ui', 1); // exp.php called from User Interface (if not set, progress information are not shown, the file is dumped after completion and deleted afterwards)
			

			if( isset($this->ob->remark) )
			{
				$site->skin->workspaceStart();
					echo htmlconstant($this->ob->remark);
				$site->skin->workspaceEnd();
			}

			$site->skin->dialogStart();
			
				if( sizeof($this->ob->options) ) 
				{
					$checkDelayed = 0;
					reset($this->ob->options);
					while( list($name, $options) = each($this->ob->options) )
					{
						switch( $options[0] ) 
						{
							case 'radio':
							case 'enum':
								form_control_start(htmlconstant($options[1]));
									$temp2 = '';
									if( $options[3] == 'tables' ) {
										for( $t = 0; $t < sizeof($Table_Def); $t++ ) {
											if( !$Table_Def[$t]->is_only_secondary($temp, $temp) ) {
												if( $temp2 ) $temp2 .= '###';
												$temp2 .= $Table_Def[$t]->name . '###' . $Table_Def[$t]->descr;
											}
										}
									}
									else {
										$temp = explode('###', $options[3]);
										for( $i = 0; $i < sizeof($temp); $i += 2 ) {
											if( $temp2 ) $temp2 .= '###';
											$temp2 .= $temp[$i] . '###' . htmlconstant($temp[$i+1]);
										}
									}
									
									if( $options[0] == 'radio' ) {
										form_control_radio($name, regGet("export.$this->expFormat.$name", $options[2]), $temp2);
									}
									else {
										form_control_enum($name, regGet("export.$this->expFormat.$name", $options[2]), $temp2);
									}
								form_control_end();
								break;
							
							case 'text':
								form_control_start(htmlconstant($options[1]));
									form_control_text($name, regGet("export.$this->expFormat.$name", $options[2]), $options[3]? $options[3] : 10 /*width*/, -1 /*height*/, 5000 /*maxlength*/ );
								form_control_end();
								break;
							
							case 'textarea':
								form_control_start(htmlconstant($options[1]));
									$text = regGet("export.$this->expFormat.$name", $options[2]);
									$text = strtr($text, array("<br>"=>"\n"));
									if( intval(CMS_VERSION) >= 5 ) {
										form_control_textarea($name, $text, 30, 3);
									}
									else {	
										form_control_text($name, $text, 60, 6); // deprecated
									}
								form_control_end();
								break;
							
							case 'check':
								if( !$checkDelayed ) {
								 form_control_start((isset($options[3])&&$options[3])? $options[3] : '');
								}
									form_control_check($name, regGet("export.$this->expFormat.$name", $options[2]), '', 0, 1);
									echo "<label for=\"$name\">" . htmlconstant($options[1]) . '</label>';
								if( substr($options[1], -1) != ' ') {
								 form_control_end();
								 $checkDelayed = 0;
								}
								else {
								 $checkDelayed = 1;
								}
								break;
							
							case 'remark':
								form_control_start();
									echo htmlconstant($options[1]);
								form_control_end();
						}
					}
				}
		
			$site->skin->dialogEnd();

			$site->skin->buttonsStart();
			
				if( sizeof($this->ob->options) ) {
					form_button('apply_only', htmlconstant('_APPLY'));
				}
				
				form_button('ok', htmlconstant('_EXP_STARTEXPORT', $this->expDescr));
				//form_clickbutton('etc.php', htmlconstant('_CANCEL'));-- we do not have "cancel" buttons to go back to the "etc." screen. "Cancel" should be only possible within the export functionality.
			$site->skin->buttonsBreak();
				echo "<a href=\"log.php\" target=\"_blank\">" . htmlconstant('_LOG') . '</a>';
			$site->skin->buttonsEnd();

			echo '</form>';
		$this->exp_render_page_end();
	}

	function progress_info($info)
	{
		if( $this->ui )
		{
			$this->progress_ob->progress_info($info);
		}
	}

	function progress_abort($msg)
	{
		if( $this->ui )
		{
			$url =  "exp.php?page=$this->expFormat&experr=".urlencode($msg);
			$this->progress_ob->js_redirect($url, $msg);
		}
		else
		{
			$logwriter = new LOG_WRITER_CLASS; // only log the error if there is no user-interface involved - otherwise the error is printed and everything is fine.
			$logwriter->addData('msg', $msg);
			$logwriter->addData('ip', $_SERVER['REMOTE_ADDR']);
			$logwriter->addData('browser', $_SERVER['HTTP_USER_AGENT']);
			$logwriter->log($_REQUEST['table'], '', 0, 'exportfailed');
			die($msg);
		}
	}

	function _allocate_file_name($expGrp, $name)
	{
		if( !file_exists($GLOBALS['g_temp_dir']) ) {
			if( !@mkdir($GLOBALS['g_temp_dir']) ) {
				$this->progress_abort(htmlconstant('_EXP_ERREXPORT', 'Cannot create directory export directory.'));
			}
		}

		$fullname = $GLOBALS['g_temp_dir'] . '/exp-' . intval($_SESSION['g_session_userid']) . '-' . $expGrp . '-' . $name;
		
		if( file_exists($fullname) ) {
			$this->progress_abort(htmlconstant('_EXP_ERREXPORT', "File $fullname already exists."));
		}
		
		$this->_allocated_files[] = $fullname;
		
		return $fullname;
	}		
	
	private function initializeExpFormat($wantedExpFormat)
	{
		// set $this->expFormat from $wantedExpFormat
		$oldExpFormat = '';
		if( $this->ui ) {
			$oldExpFormat = regGet('export.format', 'csv');
		}
		
		$this->expFormat = $wantedExpFormat;
		if( $this->expFormat == '' ) {
			$this->expFormat = $oldExpFormat;
		}
		
		if( !file_exists("lib/exp/format{$this->expFormat}.inc.php") && !file_exists("config/exp/format{$this->expFormat}.inc.php") && $this->expFormat != 'files' ) {
			$this->expFormat = 'csv';
		}
		
		if( $this->ui && $this->expFormat != $oldExpFormat ) {
			regSet('export.format', $this->expFormat, 'csv');
			regSave();
		}
		
		// create export format and function objects
		if( $this->expFormat != 'files' )
		{
			$temp = 'EXP_FORMAT'.strtoupper($this->expFormat).'_CLASS';
			$this->ob = new $temp;
			$this->expDescr = htmlconstant('_EXP_'.strtoupper($this->expFormat));
		}
	}
	 
	private function render_ui_export_start()
	{
		$this->exp_render_page_start($this->expFormat);
		$GLOBALS['site']->skin->submenuStart();
			echo '&nbsp;';
		$GLOBALS['site']->skin->submenuEnd();
		$this->progress_ob = new EXP_PROGRESS_CLASS;
		$this->progress_ob->render_placeholder(htmlconstant('_EXP_EXPORTINPROGRESS', $this->expDescr));
	}
	private function render_ui_export_end()
	{
		// redirect to download object or show export dialog
		$GLOBALS['site']->msgAdd(htmlconstant('_EXP_EXPORTDONEMSG', $this->progress_ob->progress_time(), $this->scanner->get_expire_days()), 'i');
		$this->progress_ob->js_redirect('exp.php?page=files&hilite='.$this->ob->getExpGrp(), 'Export done.');
	}

	
	/******************************************************************************
	 * Do the export!
	 ******************************************************************************/

	private function get_options($also_save = true)
	{
		$param = array();

		reset($this->ob->options);
		while( list($name, $options) = each($this->ob->options) )  {
			switch( $options[0] ) {
				case 'check':
					$param[$name] = $_REQUEST[$name]? 1 : 0;
					break;
				
				default:
					$param[$name] = $_REQUEST[$name];
					break;
			}
		}

		if( $also_save )
		{
			reset($this->ob->options);
			while( list($name, $options) = each($this->ob->options) ) {
				$temp = strtr($param[$name], array("\r"=>"", "\n"=>"<br>"));
				regSet("export.$this->expFormat.$name", $temp, $options[2]);
			}
			regSave();
		}
		
		return $param;
	}

	private function do_export()
	{
		set_time_limit(0);

		$param = $this->get_options( $this->ui /*also save?*/ );

		// invoke plugin
		$this->progress_info(htmlconstant('_EXP_PLEASEWAIT___'));
		
		$this->ob->export($param);
		
		// if we're here, the export succeeded  (otherwise the plugin has called progress_abort() and the script is already terminated)
		// so, log this success
		$logwriter = new LOG_WRITER_CLASS;
		$logwriter->addData('format', $this->expFormat);
		reset($param);
		while( list($name, $value) = each($param) ) {
			if( $value != '' && $name!='table' )
				$logwriter->addData($name, $value);
		}
		//$idsStr = is_array($this->ob->idsForTheProtocol)? implode(',',$this->ob->idsForTheProtocol) : $this->ob->idsForTheProtocol;
		if( !$this->ui )
		{
			$logwriter->addData('ip', $_SERVER['REMOTE_ADDR']);
			$logwriter->addData('browser', $_SERVER['HTTP_USER_AGENT']);
		}
		$logwriter->log($param['table'], $idsStr, $_SESSION['g_session_userid'], 'export');
		
	}
	
	
	
	/******************************************************************************
	 * Handle Request
	 ******************************************************************************/

	
	private function _abort_if_no_access()
	{
		if( !acl_check_access("SYSTEM.EXPORT", -1, ACL_READ) )
		{
			$GLOBALS['site']->abort(__FILE__, __LINE__, "SYSTEM.EXPORT");
		}
	} 
	
	function handle_request()
	{
		// see what to do
		if( isset($_REQUEST['exp']) && intval($_REQUEST['ui'])==0 )
		{
			// start direct export, dump file, delete file 
			
			$ab = new SYNC_LOGNABORT_CLASS();
			$ab->abort_on_bad_apikey();
	
			$this->ui = false;
			$this->initializeExpFormat($_REQUEST['exp']);
			set_time_limit(0);
			
			$this->do_export();
			
			$path = $this->_allocated_files[0];
			$filename = explode('/', $path); $filename = $filename[sizeof($filename)-1];
			header('Content-type: application/exportdata');
			header('Content-disposition: filename='.$filename.';');
			header("Content-length: " . @filesize($path));
			
			// TAKE CARE: if there is a session without cookies started, readfile() expands stuff like
			// href="url" to href="url?si=123456789" ... therefore, please avoid starting such sessions!
			readfile($path);	
			exit();
		}
		else if( isset($_REQUEST['exp']) && intval($_REQUEST['ui'])==1 && !isset($_REQUEST['apply_only']) )
		{
			// start ui export, save file, go to files page
			
			$this->_abort_if_no_access();
			
			$this->ui = true;
			$this->initializeExpFormat($_REQUEST['exp']);
			set_time_limit(0);
			
			$this->render_ui_export_start();
				$this->do_export();
			$this->render_ui_export_end();
		}
		else
		{
			// show settings dialog
			
			$this->_abort_if_no_access();
			
			$this->ui = true;
			$this->initializeExpFormat($_REQUEST['page']);

			if( isset($_REQUEST['apply_only']) ) {
				$this->get_options( true /*also save.*/ );
			}
			
			
			if( $this->expFormat == 'files' )
			{
				$ob = new EXP_FILESRENDERER_CLASS;
				$ob->handle_request();
			}
			else
			{
				$GLOBALS['site']->msgAdd($_REQUEST['experr'], 'e');
				$this->render_export_dialog();
			}
		}
	}
};
