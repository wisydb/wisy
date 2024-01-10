<?php

/*=============================================================================
Tools to handle the temporary directory (used for import and export, currently)
===============================================================================

Author:	
	Bjoern Petersen

===============================================================================

File format for the temp. directory files:
<scope>-<userId>-filename.ext	

=============================================================================*/



class G_TEMPFILE_CLASS
{
	public $file_name;		// contains eg. "exp-123-4567-kurse.csv"
	public $full_path;		// contains eg. "/volume/dir/exp-123-4567-kurse.csv"
	public $scope;			// contains eg. "exp"
	public $user_id;		// contains eg. "123"
	public $name_wo_scope;	// contains eg. "4567-kurse.csv" (file name without scope)
	public $size;
	public $mtime;
};



function G_TEMPFILE_CLASS_compare($a, $b)
{
	$cmp = strcmp($a->mtime, $b->mtime) * -1;
	if( $cmp == 0 )
		$cmp = strcmp($a->file_name, $b->file_name);
	return $cmp;
}



class G_TEMPDIR_CLASS
{
	private $expire_days;
	
	function __construct()
	{
		$this->expire_days = regGet('export.downloaddays', 14);
	}

	public function get_expire_days()
	{
		return $this->expire_days;
	}
	
	public function scan($wanted_scope, $delete_old_files = true)
	{
		$ret = array();
		
		$wanted_user_id = isset($_SESSION['g_session_userid']) ? $_SESSION['g_session_userid'] : null;

		// files older than $expireDate will be deleted
		$expireTime = time() - intval($this->expire_days*86400);
		$filesToDelete = array();
		
		$gTmpDir = isset($GLOBALS['g_temp_dir']) ? $GLOBALS['g_temp_dir'] : '';
		$handle = @opendir($gTmpDir);
		if( $handle ) 
		{
			while( $file_name = readdir($handle) ) 
			{
				$ob = new G_TEMPFILE_CLASS;
				$ob->file_name = $file_name;
				$ob->full_path = $gTmpDir.'/'.$ob->file_name;
				if( $ob->file_name[0] != '.' && !is_dir($ob->full_path) )
				{
					if( preg_match('/([0-9a-z]{1,8})-([0-9]+)-(.+)/', $ob->file_name, $matches) )
					{
						$ob->scope			= $matches[1];
						$ob->user_id		= $matches[2];
						$ob->name_wo_scope	= $matches[3];
						
						if( $wanted_scope==$ob->scope )
						{
							$stat = @stat($ob->full_path);
							$ob->mtime = $stat['mtime']; if( $ob->mtime <= 0 ) $ob->mtime = time();
							$ob->size  = $stat['size'];
							
							if( $delete_old_files && $ob->mtime < $expireTime )
							{
								$filesToDelete[] = $ob->full_path;
							}
							else if( $wanted_user_id==0 || $wanted_user_id==$ob->user_id )
							{
								$ret[] = $ob;
							}
						}
					}
				}
			}
			closedir($handle);
		}

		usort($ret, 'G_TEMPFILE_CLASS_compare');
		
		for( $i = 0; $i < sizeof((array) $filesToDelete); $i++ ) {
			unlink($filesToDelete[$i]);
		}
		
		return $ret;
	}
	
};