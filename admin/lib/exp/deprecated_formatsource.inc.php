<?php

class EXP_FORMATSOURCE_CLASS extends EXP_PLUGIN_CLASS
{
	function __construct()
	{	
		$this->remark	= '_EXP_SOURCEREMARK';
		
		$this->rootdirsToExport	= array('admin', 'api', 'core20', 'core50', 'core51');
		$this->filesToSkip		= array(
			'@eaDir',			// written by some daemon eg. on synology servers
			'config.inc.php'	// this file contains the passwords, do not export
		);
		
		parent::__construct();
	}
	
	function recurseDirectory($startFolder, $zipPrefix)
	{
		$handle = @opendir($startFolder);
		if( $handle === false )
			$this->progress_abort("cannot read $startFolder");

		$this->progress_info($zipPrefix);
			
		$subdirs = array();
		while( $folderentry = @readdir($handle) ) 
		{
			if( $folderentry!='.' && $folderentry!='..'
			 && !in_array($folderentry, $this->filesToSkip)	)
			{
				$this->progress_info("$zipPrefix$folderentry");
			
				if( is_dir("$startFolder/$folderentry") )
				{
					if( $zipPrefix != ''
					 || in_array($folderentry, $this->rootdirsToExport) )
						$subdirs[] = $folderentry;
				}
				else
				{
					$content = @file_get_contents("$startFolder/$folderentry");
					if( $content === false ) 
						$this->progress_abort("cannot read $startFolder/$folderentry");
					if( !$this->zipfile->add_data($content, "$zipPrefix$folderentry", @filemtime("$startFolder/$folderentry")) )
						$this->progress_abort("cannot write $zipPrefix$folderentry");
				}
			}
		} 
		@closedir($handle);
		
		for( $i = 0; $i < sizeof($subdirs); $i++ )
		{
			$this->recurseDirectory($startFolder.'/'.$subdirs[$i], $zipPrefix.$subdirs[$i].'/');
		}
	}
	
	function export($param)
	{
		$this->zipfile = new EXP_ZIPWRITER_CLASS($this->allocateFileName('source-v'.CMS_VERSION.'.zip'));
		
		$startdir = dirname(CMS_PATH); // start one directory above /admin
		$this->recurseDirectory($startdir, ''); 
		
		if( !$this->zipfile->close() )
			$this->progress_abort("cannot close zipfile");
	}
};
