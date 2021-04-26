<?php



class SYNC_EDITJOBRENDERER_CLASS extends SYNC_FUNCTIONS_CLASS
{
	function __construct($jobid)
	{
		parent::__construct('editjob', $jobid);
	}

	private function _get_tables_list()
	{	
		global $Table_Def;
		
		$temp2 = '';
		for( $t = 0; $t < sizeof((array) $Table_Def); $t++ ) {
			if( !$Table_Def[$t]->is_only_secondary($temp, $temp) ) {
				if( $temp2 ) $temp2 .= '###';
				$temp2 .= $Table_Def[$t]->name . '###' . $Table_Def[$t]->descr;
			}
		}
		
		return $temp2;
	}
	
	public function handle_request()
	{
		global $site;

		// get the new job object
		$currJob = new SYNC_JOB_CLASS($this->jobid);
		
		// apply changes made by the user
		if( isset($_REQUEST['subsequent']) )
		{
			$currJob->descr				= $_REQUEST['descr'];
			$currJob->host				= $_REQUEST['host'];
			$currJob->table				= $_REQUEST['table'];
			$currJob->query				= $_REQUEST['query'];
			$currJob->freq				= intval($_REQUEST['freq']);
			$currJob->overwrite			= intval($_REQUEST['overwrite']);
			$currJob->delete			= intval($_REQUEST['delete']);
			$currJob->further_options	= $_REQUEST['further_options'];
			
			$currJob->save();
			
			if( isset($_REQUEST['ok']) )
				redirect('sync.php?hilite='.$currJob->jobid);
		}

		// page start
		$this->sync_render_page_start();
		$site->skin->submenuStart();
			echo '&nbsp;';
		$site->skin->submenuEnd();

		// dialog
		form_tag('form_editsyncjob', 'sync.php', '', '', 'POST');
		form_hidden('subsequent', 1);
		form_hidden('page', 'editjob');
		form_hidden('jobid', $currJob->jobid);
		
			$site->skin->dialogStart();
			
				form_control_start('Quellserver');
					form_control_text('host', $currJob->host, 32 /*width*/);
					echo '&nbsp; z.B. <i>www.server.info</i>';
				form_control_end();
				form_control_start('Tabelle auf Quellserver');
					form_control_enum('table', $currJob->table, $this->_get_tables_list());
				form_control_end();
				form_control_start('Anfrage an Quellserver');
					form_control_text('query', $currJob->query, 60 /*width*/);
					echo '<br />z.B. <i>modified(today)</i> oder <i>modified>=__LAST_DATE__</i> - wenn Sie die Anfrage leer lassen, werden alle Datens&auml;tze der Tabelle synchronisiert.';
					echo '<br />&nbsp;';
					echo '<br />&nbsp;';
				form_control_end();

				form_control_start(htmlconstant('_IMP_OVERWRITE'));
					$options = IMP_OVERWRITE_OLDER.'###'.htmlconstant('_IMP_OVERWRITEOLDER').'###'.IMP_OVERWRITE_ALWAYS.'###'.htmlconstant('_IMP_OVERWRITEALWAYS').'###'.IMP_OVERWRITE_NEVER.'###'.htmlconstant('_IMP_OVERWRITENEVER');
					form_control_enum('overwrite', $currJob->overwrite, $options);
				form_control_end();
				form_control_start(htmlconstant('_IMP_DELETE'));
					$options = IMP_DELETE_DELETED.'###'.htmlconstant('_IMP_DELETEDELETED').'###'.IMP_DELETE_NEVER.'###'.htmlconstant('_IMP_DELETENEVER');
					form_control_enum('delete', $currJob->delete, $options);
				form_control_end();
				form_control_start(htmlconstant('_IMP_FURTHEROPTIONS'));
					form_control_text('further_options', $currJob->further_options, 60 /*width*/);
					echo '<br />z.B. <i>kurse.stichwort=protect; anbieter.stichwort=protect;</i> um das &Uuml;berschreiben eigener Stichw&ouml;rter zu verhindern';
					echo '<br />&nbsp;';
					echo '<br />&nbsp;';
				form_control_end();

				form_control_start('Beschreibung der Aufgabe');
					form_control_textarea('descr', $currJob->descr, 60 /*width*/, 3 /*height*/);
					echo '<br /><i>Auftragsziel, Verantwortlicher, Stand etc.</i><br />&nbsp;';
				form_control_end();

			$site->skin->dialogEnd();
			
			
			$site->skin->dialogStart();
				
				form_control_start('Aufgabe automatisch starten');
					form_control_enum('freq', $currJob->freq, 	'0###nie###'
															.	'3600###St&uuml;ndlich###'
															.	'86400###T&auml;glich###'
															.	'604800###W&ouml;chentlich###'
															.	'2592000###Monatlich'
															,
						0, '' );
					$apikey = regGet('export.apikey', '', 'template');
					echo " <a href=\"cron.php?apikey=".urlencode($apikey)."&amp;force=sync&amp;forceid={$currJob->jobid}\" target=\"_blank\" rel=\"noopener noreferrer\" onclick=\"return confirm('Das starten einer Aufgabe fuehrt u.U. zum Komplettverlust aller bestehenden Daten.\\n\\nDie Aufgabe jetzt starten?');\">[Aufgabe jetzt starten ...]</a>"; 
				form_control_end();

			$site->skin->dialogEnd();
			
			
			$site->skin->buttonsStart();
				form_button('ok', htmlconstant('_OK'));
				form_clickbutton('sync.php?hilite='.$currJob->jobid, htmlconstant('_CANCEL'));
				form_button('apply', htmlconstant('_APPLY'));
			$site->skin->buttonsBreak();
				echo "<a href=\"log.php\" target=\"_blank\" rel=\"noopener noreferrer\">" . htmlconstant('_LOG') . '</a>';
			$site->skin->buttonsEnd();
			
		echo '</form>';

		// page end
		$this->sync_render_page_end();
	}
	
};
