<?php








class G_CRON_CLASS
{
	function __construct()
	{
		$this->db = new DB_Admin;
	}

	// logging / progress handling
	// ------------------------------------------------------------------------

	public function write_to_cron_debug_log($msg)
	{
		$fullfilename = CMS_PATH . $GLOBALS['g_logs_dir'] . '/' . strftime("%Y-%m-%d") . '-crondbg-' . $this->db->Database . '.txt';

		// open the logging file
		$handle = @fopen($fullfilename, 'a');
		if( !$handle ) { return; }
				
		// append the line to the logging file and close it
		$line = strftime("%Y-%m-%d %H:%M:%S") . "\t" . $msg;
		
		@fwrite($handle, $line . "\n");
		@fclose($handle);
		
		echo $line . "<br />\n";
		flush();
	}
	
	public function yield_func($msg='', $force_output = false) // "yield" alone is a reserved in PHP
	{
		if( $this->last_yield != time() || $force_output )
		{
			echo ' &nbsp; <i>' . $msg . '</i><br />'; flush();
			$this->_updateUpdatestick();
		}
		$this->last_yield = time();
	}
	
	// update stick handling
	// ------------------------------------------------------------------------
	
	private function _lock($lock)
	{
		if( $lock )
			return $this->db->lock('user');
		else
			return $this->db->unlock();
	}
	private function _allocateUpdatestick()
	{
		if( !$this->_lock(true) ) { return false; }
					$updatestick = regGet('cron.updatestick', '0000-00-00 00:00:00', 'template');
					if( $updatestick > strftime("%Y-%m-%d %H:%M:00", time() - 3*60) /*wait 3 minutes*/ )
						{ $this->_lock(false); return false; }
					$this->updatestick_datetime = strftime("%Y-%m-%d %H:%M:00");
					regSet('cron.updatestick', $this->updatestick_datetime, '0000-00-00 00:00:00', 'template');
					regSave();
		$this->_lock(false);
		return true;
	}
	private function _updateUpdatestick()
	{
		if( $this->updatestick_datetime != strftime('%Y-%m-%d %H:%M:00') )
		{
			$this->updatestick_datetime = strftime('%Y-%m-%d %H:%M:00');
			$this->_lock(true);
						regSet('cron.updatestick', $this->updatestick_datetime, '0000-00-00 00:00:00', 'template');
						regSave();
			$this->_lock(false);
		}
	}
	private function _releaseUpdatestick()
	{
		$this->_lock(true);
					regSet('cron.updatestick', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'template');
					regSave();
		$this->_lock(false);
	}

	
	// main entry point
	// ------------------------------------------------------------------------
	
	public function handle_request()
	{
		set_time_limit(0);
		ignore_user_abort(1);

		// first, make sure, the apikey-parameter is set to export.apikey
		$ab = new SYNC_LOGNABORT_CLASS();
		$ab->abort_on_bad_apikey();

		// make sure, there is no other instance of this file running
		if( !$this->_allocateUpdatestick() ) {
			$this->write_to_cron_debug_log('ERROR: cannot start cron.php as it is already started');
			exit();
		}
		
		// really start the CRON jobs
		$this->write_to_cron_debug_log('cron.php started ...');
			$this->_do_sync_jobs();
		$this->write_to_cron_debug_log('cron.php ended.');
		
		// release the updatestick
		$this->_releaseUpdatestick();
	}

	// check, if a cron should be run	
	// ------------------------------------------------------------------------
	
	private function _get_hour_nr($timestamp)	{ return intval(strftime("%H", $timestamp), 10); }
	private function _get_day_nr($timestamp)	{ return intval(strftime("%d", $timestamp), 10); }
	private function _get_week_nr($timestamp)	{ return intval(strftime("%W", $timestamp), 10); }
	private function _get_month_nr($timestamp)	{ return intval(strftime("%m", $timestamp), 10); }
	private function _do_start_now($last_runtime, $freq)
	{
		$just_now = time();
		if( $freq == 3600 /*stündlich*/ ) {
			if( $this->_get_hour_nr($last_runtime) != $this->_get_hour_nr($just_now)) {
				return true;
			}
		}
		else if( $freq == 86400 /*täglich*/ ) {
			if( $this->_get_day_nr($last_runtime) != $this->_get_day_nr($just_now)) {
				return true;
			}
		}
		else if( $freq == 604800 /*wöchentlich*/ ) {
			if( $this->_get_week_nr($last_runtime) != $this->_get_week_nr($just_now)) {
				return true;
			}
		}
		else if( $freq == 2592000 /*monatlich*/ ) {
			if( $this->_get_month_nr($last_runtime) != $this->_get_month_nr($just_now)) {
				return true;
			}			
		}
		return false;
	}
	
	
	// do the CRON jobs here
	// ------------------------------------------------------------------------

	private function _do_sync_jobs()
	{
		$ids = SYNC_JOB_CLASS::s_get_all_ids();
		for( $j = 0; $j < sizeof((array) $ids); $j++ )
		{
			$test = new SYNC_JOB_CLASS($ids[$j]);
			if( $test->jobid )
			{
				$do_sync = false;
				if( isset($_REQUEST['force']) ) {
					if( $_REQUEST['force']=='sync' && intval($_REQUEST['forceid'])==intval($test->jobid) ) {
						$do_sync = true;
					}
				}
				else if( $this->_do_start_now($test->lasttime, $test->freq) ) {
					$do_sync = true;
				}
				
				if( $do_sync )
				{
					$this->write_to_cron_debug_log("syncing job $test->jobid ...");
					$ob = new SYNC_DOSYNC_CLASS($test->jobid);
					$ob->handle_request();
				}
			}
		}
	}
	
};
