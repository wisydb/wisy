<?php

class SYNC_OVERVIEWRENDERER_CLASS extends SYNC_FUNCTIONS_CLASS
{
	function __construct()
	{
		parent::__construct('overview', 0);
	}
	
	function handle_request()
	{
		global $site;
	
		// page start
		$this->sync_render_page_start();

		// render all jobs
		$site->skin->tableStart();	
			echo	'<colgroup>'
				.		'<col style="width:30em;" />'
				.		'<col style="width:20em;" />'
				.		'<col />'
				.	"</colgroup>";			
			$site->skin->headStart();
				$site->skin->cellStart();		
					echo 'Synchronisierungsaufgabe';		
				$site->skin->cellEnd();
				$site->skin->cellStart();		
					echo 'Anfrage an Quellserver';		
				$site->skin->cellEnd();
				$site->skin->cellStart();		
					echo 'Zuletzt ausgef&uuml;hrt';		
				$site->skin->cellEnd();
			$site->skin->headEnd();
			
			$ids = SYNC_JOB_CLASS::s_get_all_ids();
			
			for( $j = 0; $j < sizeof((array) $ids); $j++ )
			{
				$currJob = new SYNC_JOB_CLASS($ids[$j]);
				
				$hilite_start = '';
				$hilite_end = '';
				$hilite = isset($_REQUEST['hilite']) ? $_REQUEST['hilite'] : null;
				if( intval($hilite)==$currJob->jobid ) {
					$hilite_start = '<b>';
					$hilite_end = '</b>';
				}
				
				$site->skin->rowStart();
					$site->skin->cellStart();
						$htmlname = isohtmlspecialchars($currJob->getfinename());
						$cell = '<a href="sync.php?page=editjob&amp;jobid='.$currJob->jobid.'" title="'.htmlconstant('_SYNC_JOBEDIT').'">'
						 .      $hilite_start
						 .		$htmlname
						 .      $hilite_end
						 .	 '</a>';
						$cell .= "<a href=\"sync.php?page=deletejob&amp;jobid=".$currJob->jobid."\" onclick=\"return confirm('" .htmlconstant('_SYNC_JOBDELETEASK', $htmlname). "');\" title=\"".htmlconstant('_SYNC_JOBDELETE')."\">&nbsp;&times;</a>";
						echo $cell;
					$site->skin->cellEnd();
					$site->skin->cellStart();
						echo isohtmlspecialchars($currJob->getfinequery());
					$site->skin->cellEnd();
					$site->skin->cellStart();
						if( $currJob->lasttime == 0 ) {
							echo 'Nie';
						}
						else {
							echo sql_date_to_human(ftime('%Y-%m-%d %H:%M:%S', $currJob->lasttime), 'datetime');
						}
						
					$site->skin->cellEnd();
				$site->skin->rowEnd();			
				
			}
			
			
			$site->skin->rowStart();
				$site->skin->cellStart('colspan="3"');		
					echo '<a href="sync.php?page=editjob&amp;jobid=0"><i>Synchronisierungsaufgabe hinzuf&uuml;gen...</i></a>';		
				$site->skin->cellEnd();
			$site->skin->rowEnd();			
		
		$site->skin->tableEnd();
		
		// page end
		$site->skin->buttonsStart();
			echo '&nbsp;';
		$site->skin->buttonsBreak();
			echo "<a href=\"log.php\" target=\"_blank\" rel=\"noopener noreferrer\">" . htmlconstant('_LOG') . '</a>';
		$site->skin->buttonsEnd();
		
		$this->sync_render_page_end();
	}
	
};