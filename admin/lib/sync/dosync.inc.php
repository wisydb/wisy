<?php

class SYNC_DOSYNC_CLASS extends SYNC_FUNCTIONS_CLASS
{
	function __construct($jobid)
	{
		parent::__construct('overview', $jobid);
	}
	
	private function _log($msg)
	{
		if( is_object($GLOBALS['g_cron']) ) 
			$GLOBALS['g_cron']->write_to_cron_debug_log($msg);
		else
			echo isohtmlspecialchars($msg) . " <br />\n";
	}

	private function _copy_remote_to_local($remote, $local)
	{
		if( !copy($remote, $local) ) {
			$this->_log('copy error!');
			return false;
		}
		return true;
	}
	
	public function handle_request()
	{
		set_time_limit(0);
		ignore_user_abort(1);
		$starting_time = time();
		
		// Job informationen holen 
		$this->_log("Job $this->jobid ausfï¿½hren ... wenn am Ende nicht 'Fertig' steht, wurde die Aufgabe unterbrochen!");
		$this->job = new SYNC_JOB_CLASS($this->jobid);
		if( $this->job->jobid == 0 ) {
			$this->_log("Kann Job $this->jobid nicht laden.");
			return false;
		}
		
		// Request-URL erzeugen
		$apikey = regGet('export.apikey.'.$this->job->host, '', 'template');
		if( $apikey == '' ) $apikey = regGet('export.apikey', '', 'template');
		
		$query = $this->job->query;
		$query = str_replace('__LAST_DATE__', strftime("%Y-%m-%d", $this->job->lasttime), $query);
												
											//  "admin" anstelle von $GLOBALS['site']->adminDir ist hier in Ordnung, da es sich um den entfernten Rechner handelt
		$requrl_without_key = sprintf('https://%s/admin/exp.php?exp=mix&apikey=<apikey>&table=%s&q=%s', $this->job->host, urlencode($this->job->table), urlencode($query));
		$requrl_incl_key    = str_replace('<apikey>', urlencode($apikey), $requrl_without_key);
		
		// get local destination
		$mix_fullpath =  $GLOBALS['g_temp_dir'] . '/imp-0-' . 'sync' . time() . 'job'.$this->job->jobid.'.mix';;
		
		// MIX-Datei herunterladen
		$this->_log("Kopiere $requrl_without_key nach $mix_fullpath ...");
		if( !$this->_copy_remote_to_local($requrl_incl_key, $mix_fullpath) )
			return false;
		
		// MIX-Datei importieren
		$importer = new IMP_IMPORTER_CLASS;
		$importer->set_user($this->job->defuser, $this->job->defgrp, $this->job->defaccess);
		
		if( is_object($GLOBALS['g_cron']) )
			$importer->set_progress_callback(array($GLOBALS['g_cron'], 'yield_func'));
		
		if( !$importer->import_do($mix_fullpath, $this->job->overwrite, $this->job->delete, $this->job->further_options) )
		{
			$this->_log('Importfehler, s. Protokoll für weitere Details.');
			return false;
		}
		
		// set the last time - we use the local time here: - __LAST_DATE__ specifies only the date, not the time, so we should get all records when using modified>=__LAST_DATE__
		//												   - if we want to be more exact there, we could create a difference between export_start_time and the local time
		$this->job->lasttime = $starting_time;
		$this->job->save();
		
		// done.
		$this->_log('Fertig.');
		return true;
	}
};
