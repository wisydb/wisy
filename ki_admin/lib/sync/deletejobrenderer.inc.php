<?php



class SYNC_DELETEJOBRENDERER_CLASS extends SYNC_FUNCTIONS_CLASS
{
	function __construct($jobid)
	{
		parent::__construct('deletejob', $jobid);
	}

	public function handle_request()
	{
		$currJob = new SYNC_JOB_CLASS($this->jobid);
		$currJob->remove();
		
		redirect('sync.php');
	}
	
};