<?php

class IMP_FILESRENDERER_CLASS extends IMP_FUNCTIONS_CLASS
{
	function __construct($page, $mix)
	{
		parent::__construct($page, $mix);
	}

	private function render($files)
	{
		global $site;
		
		$hilite = regGet('import.lastfile', '');
		
		$this->imp_render_page_start();

		$site->skin->tableStart();
			echo	'<colgroup>'
				.		'<col style="width:25em;" />'
				.		'<col style="width:25em;" />' 
				.		'<col style="width:8em;" />' // size
				.		'<col />'
				.	"</colgroup>";
			$site->skin->headStart();
				$site->skin->cellStart();
					echo htmlconstant('_FILE');
				$site->skin->cellEnd();
				$site->skin->cellStart();
					echo htmlconstant('_IMP_CONTENT');
				$site->skin->cellEnd();
				$site->skin->cellStart();
					echo htmlconstant('_EXP_SIZE');
				$site->skin->cellEnd();
				$site->skin->cellStart();
					echo htmlconstant('_IMP_DATEUPLOADED') . ' ' . $site->skin->ti_sortdesc;
				$site->skin->cellEnd();
			$site->skin->headEnd();		

			reset($files);
			$filesCnt = 0;
			foreach($files as $key => $currFile)
			{
				$ob = new IMP_MIXFILE_CLASS;
				$ok = $ob->open($GLOBALS['g_temp_dir'].'/imp-'.$_SESSION['g_session_userid'].'-'.$currFile->name_wo_scope);
			
					$site->skin->rowStart();
						$site->skin->cellStart('nowrap');
							$tooltip = htmlconstant('_IMP_CLICKTOPREPARE');
							$ahref = '<a href="imp.php?page=options&amp;mix='.urlencode($currFile->name_wo_scope).'" title="'.$tooltip.'">';
							$cell =  $ok? $ahref : '';
							$cell .= 			$hilite==$currFile->name_wo_scope? '<b>' : '';
							$cell .=					isohtmlspecialchars($currFile->name_wo_scope);
							$cell .= 			$hilite==$currFile->name_wo_scope? '</b>' : '';
							$cell .= $ok? '</a>' : '';
							
							$url = 'imp.php?page=files&delete=' .urlencode($currFile->name_wo_scope). $addParam;
							$cell .= "<a href=\"".isohtmlspecialchars($url)."\" onclick=\"return confirm('" .htmlconstant('_EXP_FILESDELETEASK', isohtmlentities($currFile->name_wo_scope)). "');\" title=\"".htmlconstant('_EXP_FILESDELETE')."\">&nbsp;&times;</a>";
							echo $cell;
						$site->skin->cellEnd();
						$site->skin->cellStart();
							
							$cell = '';
							if( $ok )
							{
								$table_descr = $ob->ini_read('base_table', '');
								$table_def = Table_Find_Def($table_descr, 0);
								if( $table_def ) 
									$table_descr = $table_def->descr;
								$cell .= $ob->ini_read('record_cnt_base', 0) . ' ' . $table_descr . ', ';
								$cell .= $ob->ini_read('record_cnt_others', 0) . ' andere Datensätze';
							}
							else
							{
								$cell .= '<i>'.isohtmlspecialchars($ob->error_str).'</i>';
							}
							
							echo $cell;
							
							
						$site->skin->cellEnd();
						$site->skin->cellStart('nowrap');
							echo isohtmlspecialchars(smart_size($currFile->size));
						$site->skin->cellEnd();
						$site->skin->cellStart('nowrap');
							echo sql_date_to_human(strftime("%Y-%m-%d %H:%M:%S", $currFile->mtime), 'datetime');
						$site->skin->cellEnd();
					$site->skin->rowEnd();
					$filesCnt++;
				
				$ob->close();
			}

			if( $filesCnt == 0 )
			{
				$site->skin->rowStart();
					$site->skin->cellStart('colspan="4"');
						echo '<i>' . htmlconstant('_EXP_FILESNOFILES') . '</i>';
					$site->skin->cellEnd();
				$site->skin->rowEnd();
			}
			

			
		$site->skin->tableEnd();

		$site->skin->buttonsStart();
			$this->uploader->render_autosubmit_form();
		$site->skin->buttonsBreak();
			if( $filesCnt ) {
				echo htmlconstant('_EXP_FILESAREDELETEDAFTERNDAYS', $this->scanner->get_expire_days(),
					'<a href="'.isohtmlspecialchars('imp.php?page=files&delete=all'.$addParam).'" title="'.htmlconstant('_EXP_FILESDELETEALL').'" onclick="return confirm(\''.htmlconstant('_EXP_FILESDELETEALLASK', $filesCnt).'\');">',
					'</a>') . ' | ';
			}
			echo "<a href=\"log.php\" target=\"_blank\">" . htmlconstant('_LOG') . '</a>';
		$site->skin->buttonsEnd();
		
		$this->imp_render_page_end();
	}

	function handle_request()
	{
		global $site;
		$this->scanner = new G_TEMPDIR_CLASS;
		$this->uploader = new IMP_UPLOADER_CLASS;
		
		if( $this->uploader->form_submitted() )
		{
			$file = $this->uploader->get_uploaded_file();
			if( $file['error_msg'] ) {
				$site->msgAdd($file['error_msg'], 'e');
			}
			else {
				$src  = $file['tmp_name'];
				$dest = $GLOBALS['g_temp_dir'] . '/imp-' . $_SESSION['g_session_userid'] .'-' . $file['name'];
				if( @file_exists($dest) ) {
					$site->msgAdd(htmlconstant('_IMP_UPLOADFILEEXISTS', '<i>'.isohtmlspecialchars($file['name']).'</i>'), 'e');
				}
				else if( !move_uploaded_file($src, $dest) ) {
					$site->msgAdd(htmlconstant('_IMP_UPLOADCANNOTCOPY', isohtmlspecialchars($src), isohtmlspecialchars($dest)), 'e');
				}
				else {
					$site->msgAdd(htmlconstant('_IMP_UPLOADOK', '<i>'.isohtmlspecialchars($file['name']).'</i>', $this->scanner->get_expire_days()), 'i');
					regSet('import.lastfile', $file['name'], '');
					regSave();
				}
			}
		}
		else if( isset($_REQUEST['delete']) )
		{
			// delete one/all files
			$delete = $_REQUEST['delete'];
			$files = $this->scanner->scan('imp');
			for( $i = 0; $i < sizeof((array) $files); $i++ ) {
				if( $delete == $files[$i]->name_wo_scope || $delete == 'all' ) {
					unlink( $files[$i]->full_path );
				}
			}
			
		}

		// render the page
		$files = $this->scanner->scan('imp');
		$this->render($files);
	}
};
