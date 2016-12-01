<?php

/*=============================================================================
Rendering the Protocol
===============================================================================

Author:	
	Bjoern Petersen

parameters that may be used for fitlering:
	table
	id
	user

used on subsequent calls:
	date
	showall (hidden, for debugging only)

===============================================================================
Änderungen, die direkt nach Anlegen eines Datensatzes vom Ersteller selbst 
erfolgen, werden nur im Protokoll für den Datensatz selbst angezeigt.
Eine Überlegung war, diese Änderungen gar nicht erst zu erfassen, aber manchmal
können auch diese wichtig sein (wenn z.B. ein Datensatz schon online ist).
Abgesehen davon belegen diese Änderungen nur ca. 10% des Protokolls und
das Einlesen/Parsen von ca. 1 MB dauert nur 0.002/0.020 Sekunden.
===============================================================================
TODO: 
- Zeilen mit "Keine Änderung" werden ebenfalls nicht ausgegeben; diese sollten
  mittelfristig aber auch gar nicht erst erfasst werden - stattdessen
  sollte eine Warnung beim Abspeichern ausgegeben werden.
- evtl. sollte man den "Ältere Einträge"-Link auch schon nach weniger als 
  einen Tag einblenden, mal sehen wieviele Daten da in der Praxis 
  zusammen kommen.
- Gibt es noch Probleme mit dem diff? 
=============================================================================*/



// configuration
define('LOG_ROWS_OUT', 	20);		// if LOG_ROWS_OUT rows are printed, we stop rendering and add a "more..." link
									// the number of rows is compated after each complete logging file is rendererd.

define('LOG_FILE_SCAN_MAX', 30);	// if LOG_FILE_SCAN_MAX files are checked, we stop rendering and add a "more..." link -
									// even if not LOG_ROWS_OUT are found!

									

class LOG_RENDERER_CLASS
{
	private $filterTable;
	private $filterId;
	private $filterUser;
	private $dateStart;
	
	private function dumpRow($currDay, $currLineNum, &$record)
	{
		global $site;
		$site->skin->rowStart();

			$action = $record[4];
		
			// date / stat
			$site->skin->cellStart('nowrap');

				if( $action == 'more___' )
				{
					$cell = $record[6];
				}
				else if( is_array($record[0]) ) 
				{
					$cell = sql_date_to_human($record[0][0], 'date -weekdays') . ' - ' . sql_date_to_human($record[0][1], 'date -weekdays');
				}
				else if( $record[0]!='' ) 
				{
					$cell = sql_date_to_human($record[0], 'datetime');
				}
				else 
				{
					$cell = '&nbsp;';
				}

				echo $cell;

			$site->skin->cellEnd();
			
			// Table / Record Name
			$tableDef = Table_Find_Def($record[1], 0 /*no access check*/);
			$wasSameRecord = false;

			$site->skin->cellStart();
				$cell = '';
				if( !$this->filterTable && $record[1] )
				{
					$name = $record[1];
					if( $tableDef ) $name = $tableDef->descr;
					$cell =	'<a href="'.isohtmlspecialchars($this->getUrl(array('table'=>$record[1], 'id'=>0))).'"><b>'
						.		$name
						.	'</b></a>';
				}
				if( sizeof($record[2])==1 )
				{
					if( $record[2][0] )
					{	
						$name = $record[2][0];
						if( $tableDef ) $name = isohtmlspecialchars($tableDef->get_summary($record[2][0]));
						$cell .= $cell==''?'':': ';
						$cell .=	'<a href="'.isohtmlspecialchars($this->getUrl(array('table'=>$record[1], 'id'=>$record[2][0]))).'">'
							.		$name
							.	'</a>'
							.	'<a href="edit.php?table='.$record[1].'&amp;id='.$record[2][0].'">&nbsp;&#8599;&nbsp;</a>'
							;
					}
					else
					{
						$cell .= '&nbsp;'; // nichts ausgeben, da dies auch für die Zeile "weitere Datensätze verwendet wird"
					}
				}
				else
				{
					$cell .= $cell==''?'':': ';
					$cell .= htmlconstant('_LOG_N_RECORDS', sizeof($record[2]));
				}
				
				if( $cell == $this->lastRecordCell ) {
					echo '&nbsp; &quot;';
					$wasSameRecord = true;
				}
				else {
					echo $cell;
					$this->lastRecordCell = $cell=='&nbsp;'? '' : $cell;
				}
				
			$site->skin->cellEnd();

			
			// action & details
			$fieldFormatter = new LOG_FIELDFORMATTER_CLASS($currDay, $currLineNum);
			
			$site->skin->cellStart();
				switch( $action )
				{
					case 'more___':
						echo '<a href="'.isohtmlspecialchars($record[5]).'" class="log_showOlderEntries">['.htmlconstant('_LOG_SHOW_OLDER_ENTRIES___').']</a>';
						break;

					case 'edit':
						// details out
						if( !$this->showAll )
							$fieldFormatter->combineDoubleFields($record);
						for( $i = 5, $cnt = 0; $i < sizeof($record); $i += 3 )
						{
							$prefix = substr($record[$i], 0, strrpos($record[$i], '.')) . '.';
							if( $record[$i] && !$fieldFormatter->deletedSecondary[$prefix] ) {
								echo $cnt? '<br />' : '';
								echo $fieldFormatter->formatField($i, $action, $tableDef, 
									$record[$i], $record[$i+1], $record[$i+2]);
								$cnt ++;
							}
						}
						if( $cnt == 0 ) {
							echo '<i>'.htmlconstant('_LOG_ACTION_NO_MODIFICATIONS').'</i>';
						}
						break;
						
					default:
						// action out
						$span = '<span style="font-weight:bold;">';
						if( $action == 'login' || $action == 'logout' || $action == 'create' || $action == 'import' )	{ $span = '<span style="color:#0A0; font-weight:bold;">'; }
						else if( $action == 'loginfailed' || $action == 'delete' || $action=='exportfailed' ) 				{ $span = '<span style="color:#A00; font-weight:bold;">'; }
						else if( $action == 'nop' ) 											{ $span = '<span style="font-style:italic;">'; }

						echo $span . htmlconstant('_LOG_ACTION_'.strtoupper($action)) . '</span>';
						
						// details out
						$moreAfter = -1;
						if( $action == 'login' || $action == 'loginfailed' || $action == 'confirmed' || $action == 'delete' || $action == 'export' || $action == 'exportfailed' || $action == 'requestpw' || $action == 'resetpw' ) { $moreAfter = 0;  }
						if( $action == 'import' ) $moreAfter = 1;
						for( $i = 5, $cnt = 0; $i < sizeof($record); $i += 3 )
						{
							if( $record[$i] ) {
								if( $moreAfter == $cnt ) {																			
									echo ' <a href="log.php?l='.$currLineNum.'&amp;date='.$currDay.'" class="log_dt">[...]</a>';
									break;
								}
								echo $cnt? '<br />' : ' ';
								echo $fieldFormatter->formatField($i, $action, $tableDef, 
									$record[$i], $record[$i+1], $record[$i+2]);
								$cnt ++;
							}
						}
						
						break;
				}

			$site->skin->cellEnd();
				
			// user
			if( !$this->filterUser )
			{
				$site->skin->cellStart('nowrap');
					if( $record[3] )
					{
						$cell =	'<a href="'.isohtmlspecialchars($this->getUrl(array('user'=>$record[3]))).'">'
							.		user_html_name($record[3])
							.	'</a>';
					}
					else {
						$cell = '&nbsp;';
					}
					
					if( $this->lastUserCell == $cell && $wasSameRecord ) {
						echo '&nbsp; &quot;';
					}
					else {
						echo $cell;
						$this->lastUserCell = $cell=='&nbsp;'? '' : $cell;
					}
				$site->skin->cellEnd();
			}

			
		$site->skin->rowEnd();
	}
	
	private function dumpFile($currDay, $filename, &$ret_fileRowsOut, &$ret_stats)
	{
		$logfile = new LOG_FILE_CLASS($filename, $this->filterTable, $this->filterId, $this->filterUser, $this->showAll);
		
		while( ($record=$logfile->next_record())!==false )
		{
			$this->dumpRow($currDay, $logfile->get_curr_line_number(), $record);
			$ret_fileRowsOut++;
		}
		
		
		// get stats
		$percentBytes = $logfile->bytesTotal? intval($logfile->bytesSaved*100/$logfile->bytesTotal) : 0;
		$percentLines = $logfile->linesTotal? intval($logfile->linesSaved*100/$logfile->linesTotal) : 0;
		$ret_stats = isohtmlspecialchars(sprintf("%1.3f s Lesen, %1.3f s Parsen; %d Zeilen (%d%% versteckt); %d KB (%d%% versteckt)", 
			$logfile->time1, $logfile->time2,
			$logfile->linesTotal, $percentLines, $logfile->bytesTotal/1024, $percentBytes));
	}
	
	private function dumpProtocol() 
	{
		$time3 = microtime(true);
		
		$logwriter = new LOG_WRITER_CLASS;
		
		$timestamp = sql_date_to_timestamp($this->dateStart);
		$fileDaysScanned  = 0;
		$totalRowsOut = 0;
		$nopPrint = false;
		while( 1 )
		{
			// calculate the day to handle
			$currDay = strftime("%Y-%m-%d", $timestamp - $fileDaysScanned*86400);

			// check this file
			$fileRowsOut = 0;
			$filename = $logwriter->getFilename($currDay);
			if( file_exists($filename) ) {
				$this->dumpFile($currDay, $filename, $fileRowsOut, $stats);
			}

			if( $fileRowsOut ) {
				$totalRowsOut += $fileRowsOut;
				$nopPrint = false;
			}
			else if( !$nopPrint ) {
				$nopPrint = true;
				$nopStart = $currDay;
			}
			
			// check for exit
			$fileDaysScanned ++;
			if( $fileDaysScanned >= LOG_FILE_SCAN_MAX )
				break;
			if( $totalRowsOut >= LOG_ROWS_OUT )
				break;
		}
		
		$time3 = microtime(true) - $time3; 
		
		// render "No Entry" row, if we have scanned files in the past without success
		if( $nopPrint ) {	
			$record = array(array($currDay, $nopStart), $this->filterTable, array($this->filterId), 0, 'nop');
			$this->dumpRow($currDay, 0, $record);
		}

		// render "more ..." link
		$record = array('', '', 0, 0, 'more___', $this->getUrl(array('date'=>strftime("%Y-%m-%d", $timestamp - $fileDaysScanned*86400))), 
			"<small title=\"$stats\" style=\"color: #bbb;\">&nbsp; ".sprintf('%1.3f s', $time3)."</small>");
		$this->dumpRow($currDay, 0, $record);
	}

	private function getUrl($param)
	{
		$ret = 'log.php';
		$cnt = 0;
		if( !isset($param['table']) )	$param['table'] 	= $this->filterTable;
		if( !isset($param['id']) )		$param['id']	 	= $this->filterId;
		if( !isset($param['user']) )	$param['user']  	= $this->filterUser;
		reset($param);
		while( list($n, $v) = each($param) ) {
			if( $v ) {
				$ret .= ($cnt? '&' : '?') . $n . '=' . urlencode($v);
				$cnt++;
			}
		}
		return $ret;
	}
	
	private function getParam()
	{
		// get table to use for filtering, if any
		$this->filterTable = '';
		$this->filterId = 0;
		if( isset($_REQUEST['table']) && Table_Find_Def($_REQUEST['table'], 0 /*no access check*/)) {
			$this->filterTable = $_REQUEST['table'];
			$this->filterId = intval($_REQUEST['id']); // only set the ID filter if the table filter is valid
		}
		$this->filterUser = intval($_REQUEST['user']);

		// get start date to use for filtering (we go to the past from this date)
		$this->dateStart = strftime("%Y-%m-%d");
		if( strlen($_REQUEST['date']) == 10 ) 
		{
			$this->dateStart = $_REQUEST['date'];
		}
		else if( $this->filterTable 
		      && $this->filterId /*without an ID we cannot guess the last modification date eg. using "SELECT MAX(date_modified) AS dm FROM {$this->filterTable};" - we'll forget deleted records this way ...*/
		      && $this->filterTable != 'user' && $this->filterTable != 'anbieter' /*these tables contain login information that do not affect date_modified! */ )
		{
			$db = new DB_Admin;
			$db->query("SELECT date_modified AS dm FROM {$this->filterTable} WHERE id={$this->filterId};");
			if( $db->next_record() && $db->f('dm')!='0000-00-00 00:00:00' ) {
				$this->dateStart = substr($db->f('dm'), 0, 10);
			}
		}
		
		// misc.
		$this->showAll = ($_REQUEST['showall'] || $this->filterId)? 1 : 0;
		$this->ajax    = $_REQUEST['ajax']? 1 : 0;
	}

	function renderAjaxDetails($line)
	{
		$logwriter = new LOG_WRITER_CLASS;
		
		$filename = $logwriter->getFilename($this->dateStart);
		if( !file_exists($filename) ) die('bad date.');
		
		$logfile = new LOG_FILE_CLASS($filename, '', 0, 0, true);
		$record = $logfile->get_record_by_line($line);

		$fieldFormatter = new LOG_FIELDFORMATTER_CLASS($this->dateStart, $line);
		
		$action = $record[4];

		$moreAfter = 0;
		if( $action == 'import' ) $moreAfter = 1;
		
		$tableDef = Table_Find_Def($record[1], 0 /*no access check*/);
		for( $i = 5 + $moreAfter*3, $cnt = 0; $i < sizeof($record); $i += 3 )
		{
			$prefix = substr($record[$i], 0, strrpos($record[$i], '.')) . '.';		
			
			if( $record[$i] ) 
			{
				$field = $fieldFormatter->formatField($i, $action, $tableDef, 
					$record[$i], $record[$i+1], $record[$i+2]);
					

				if(  $action != 'edit'
				 || ($action == 'edit' && $fieldFormatter->deletedSecondary[$prefix]) )
				{
					echo ($cnt? '<br />' : '') . $field;
					$cnt ++;
				}
			}
		}
	}
	
	function handle_request()
	{
		global $site;
		
		$this->getParam(); // must be first, set paramters used by renderAjaxDetails and by normal page renderung
		require_lang('lang/log');
		
		if( isset($_REQUEST['l']) )
		{
			header('Content-Type: text/html; charset=iso-8859-1'); // otherwise the ajax request will be interpreted as UTF-8
			$this->renderAjaxDetails($_REQUEST['l']);
			return;
		}
		
		// render a normal page or an page ajax addition
		// --------------------------------------------------------------------
		
		
		// if possible, compress this page - this costs about 300 ms serverload per MB, however, 
		// the performance seems to be worth it.
		ob_start("ob_gzhandler");
		
		// page start
		if( !$this->ajax )
		{
			$site->title = htmlconstant('_LOG_TITLE');
			$site->addScript('lib/log/renderer.js');
			$site->pageStart();
				
				$site->skin->workspaceStart();
					echo htmlconstant('_LOG_FILTER').': ';
					if( $this->filterTable || $this->filterUser )
					{
						$filtertitle = htmlconstant('_LOG_FILTER_REMOVE_CRITERION');
						$close = '&nbsp;&times;&nbsp;';
						if( $this->filterTable ) {
							$tableDef = Table_Find_Def($this->filterTable, 0 /*no access check*/);
							$name = $this->filterTable;
							if( $tableDef ) $name = $tableDef->descr;
							if( !$this->filterId ) $name = "<b>$name</b>";
							echo htmlconstant('_LOG_TABLE') . '=' . $name 
									.	'<a href="'.isohtmlspecialchars($this->getUrl(array('table'=>'', 'id'=>0))).'" title="'.$filtertitle.'">'.$close.'</a>';
						}
						if( $this->filterId ) {
							$name = $this->filterId;
							if( $tableDef) $name = isohtmlspecialchars($tableDef->get_summary($this->filterId));
							$name = "<b>$name</b>";
							echo $this->filterTable? ', ' : '';		
							echo htmlconstant('_LOG_RECORD') . '=' . $name 
									.	'<a href="'.isohtmlspecialchars($this->getUrl(array('id'=>0))).'" title="'.$filtertitle.'">'.$close.'</a>';
						}
						if( $this->filterUser ) {
							echo $this->filterTable? ', ' : '';
							$name = user_html_name($this->filterUser);
							$name = "<b>$name</b>";
							echo htmlconstant('_LOG_USER') . '=' . $name 
									.	 '<a href="'.isohtmlspecialchars($this->getUrl(array('user'=>0))).'" title="'.$filtertitle.'">'.$close.'</a>';
						}
					}
					else
					{
						echo '<i>'.htmlconstant('_LOG_FILTER_OFF').'</i>';
					}
				$site->skin->workspaceEnd();

				// start table
				$site->skin->tableStart();
					
					// many CSS-definitions (font, wrap, ...)do not work with colgroup/col, however, with seems to work everywhere
					echo	'<colgroup>'
						.									'<col style="width:150px;" />'
						.									'<col style="width:220px;" />'
						.			 						'<col />'
						.		( $this->filterUser? '' :	'<col style="width:150px;" />' )
						.	"</colgroup>";
						
					// table head
					$site->skin->headStart();
					
							$site->skin->cellStart(); // 'width="150"'
								echo htmlconstant('_LOG_DATE') . ' ' . $site->skin->ti_sortdesc;		
							$site->skin->cellEnd();
						
							$site->skin->cellStart(); // 'width="220"'
								echo htmlconstant('_LOG_RECORD');	
							$site->skin->cellEnd();
						
							$site->skin->cellStart(''); 
								echo htmlconstant('_LOG_ACTION');	
							$site->skin->cellEnd();
						
						if( !$this->filterUser ) 
						{
							$site->skin->cellStart(); // 'width="150"'
								echo htmlconstant('_LOG_USER');		
							$site->skin->cellEnd();
						}
						
					$site->skin->headEnd();
					flush();
		}
		else
		{	
			header('Content-Type: text/html; charset=iso-8859-1'); // otherwise the ajax request will be interpreted as UTF-8
		}
		
		// rows out
		$this->dumpProtocol();
		
		// page end
		if( !$this->ajax )
		{
				$site->skin->tableEnd();
				echo '<br /><br /><br /><br /><br /><br />'; // some space abottom, this makes clicking the "more link..." smarter.
			$site->pageEnd();
		}

		
	}
};
