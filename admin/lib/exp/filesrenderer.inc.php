<?php

/*
damit beim Download die Datei gesichert, und nicht im Browser  geoeffnet wird, 
sollte im temp-verzeichnis die folgende .htaccess Datei stehen:

AddType application/octet-stream .csv
AddType application/octet-stream .txt
AddType application/octet-stream .zip
AddType application/octet-stream .mix

*/


class EXP_FILESRENDERER_CLASS extends EXP_FUNCTIONS_CLASS
{
	

	function exp_render_files_overview($files)
	{
		global $site;

		$addParam = $this->debug? '&debug='.$this->debug : '';
		$addParam = $this->hilite? '&hilite='.$this->hilite : '';

		
		$this->exp_render_page_start('files');

			// list out

			
			$site->skin->tableStart();
				echo	'<colgroup>'
					.		'<col style="width:30em;" />'
					.		'<col style="width:8em;" />'
					.		'<col />'
					.	"</colgroup>";
				$site->skin->headStart();
					$site->skin->cellStart();
						echo htmlconstant('_FILE');
					$site->skin->cellEnd();
					$site->skin->cellStart();
						echo htmlconstant('_EXP_SIZE');
					$site->skin->cellEnd();
					$site->skin->cellStart();
						echo htmlconstant('_OVERVIEW_CREATED') . ' ' . $site->skin->ti_sortdesc;
					$site->skin->cellEnd();
				$site->skin->headEnd();
				
				reset($files);
				$filesCnt = 0;
				foreach($files as $key => $currFile)
				{
					$site->skin->rowStart();
						$site->skin->cellStart('nowrap');
							$cell = "<a href=\"{$currFile->full_path}\" title=\"".htmlconstant('_EXP_FILESDOWNLOAD')."\" >";
								
							if( isset( $this->hilite ) && $this->hilite && substr($currFile->name_wo_scope, 0, strlen($this->hilite))==$this->hilite ) { $cell .= '<b>'; $bold = true; }
								
							$cell .= isohtmlentities( strval( $currFile->file_name ) );
								
							if( isset($bold) && $bold ) { $cell .= '</b>'; $bold = false; }
							$cell .= '</a>';
							$url = 'exp.php?page=files&delete=' .urlencode($currFile->file_name). $addParam;
							$cell .= "<a href=\"".isohtmlspecialchars($url)."\" onclick=\"return confirm('" .htmlconstant('_EXP_FILESDELETEASK', isohtmlentities( strval( $currFile->file_name ) ) ). "');\" title=\"".htmlconstant('_EXP_FILESDELETE')."\">&nbsp;&times;</a>";
							echo $cell;
						$site->skin->cellEnd();
						$site->skin->cellStart('nowrap');
							echo isohtmlspecialchars(smart_size($currFile->size));
						$site->skin->cellEnd();
						$site->skin->cellStart('nowrap');
							echo sql_date_to_human(ftime("%Y-%m-%d %H:%M:%S", $currFile->mtime), 'datetime');
						$site->skin->cellEnd();
					$site->skin->rowEnd();
					$filesCnt++;
				}
				
				if( $filesCnt == 0 )
				{
					$site->skin->rowStart();
						$site->skin->cellStart('colspan="3"');
							echo '<i>' . htmlconstant('_EXP_FILESNOFILES') . '</i>';
						$site->skin->cellEnd();
					$site->skin->rowEnd();
				}
			$site->skin->tableEnd();

			$site->skin->buttonsStart();
				// form_clickbutton('etc.php', htmlconstant('_CANCEL')); -- we do not have "cancel" buttons to go back to the "etc." screen. "Cancel" should be only possible within the export functionality.
				echo '&nbsp;';
			$site->skin->buttonsBreak();
				if( $filesCnt ) {
					echo htmlconstant('_EXP_FILESAREDELETEDAFTERNDAYS', $this->scanner->get_expire_days(),
						'<a href="'.isohtmlspecialchars('exp.php?page=files&delete=all'.$addParam).'" title="'.htmlconstant('_EXP_FILESDELETEALL').'" onclick="return confirm(\''.htmlconstant('_EXP_FILESDELETEALLASK', $filesCnt).'\');">',
						'</a> | ');
				}
				echo "<a href=\"log.php\" target=\"_blank\" rel=\"noopener noreferrer\">" . htmlconstant('_LOG') . '</a>';
			$site->skin->buttonsEnd();

		$this->exp_render_page_end();
	}

	function handle_request()
	{
		// check access
		if( !acl_check_access("SYSTEM.EXPORT", -1, ACL_READ) ) {
			$GLOBALS['site']->abort(__FILE__, __LINE__, "SYSTEM.EXPORT");
		}

		// get parameters
		$this->hilite	= isset($_REQUEST['hilite'])    ? $_REQUEST['hilite']   : null;
		$this->expTime  = isset($_REQUEST['exptime'])   ? $_REQUEST['exptime']  : null;
		$this->debug    = isset($_REQUEST['debug'])     ? $_REQUEST['debug']    : null;
		$delete         = isset($_REQUEST['delete'])    ? $_REQUEST['delete']   : null;

		// read all files, delete marked
		$files = $this->scanner->scan('exp');
		if( $delete ) {
		    for( $i = 0; $i < sizeof((array) $files); $i++ ) {
				if( $delete == $files[$i]->file_name || $delete == 'all' ) {
					unlink( $files[$i]->full_path );
				}
			}
			$files = $this->scanner->scan('exp');
		}
		
		$this->exp_render_files_overview($files);
	}
}