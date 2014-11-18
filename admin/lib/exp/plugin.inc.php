<?php

/*=============================================================================
Export Base class
===============================================================================

Author:
	Bjoern Petersen

===============================================================================
How to create your own export modules

- to create an export module, create a file named
  format<mymodule>.inc.php in /lib/exp/ or in /config/exp/
  (<mymodule> should be a name build out of characters a-z - no underscore or
  minus characters, please)

- this file should contain at least a class with the name "EXP_FORMAT<MYMODULE>_CLASS";
  the class must be derived from EXP_PLUGIN_CLASS.  For more details about this
  base class see below.

- finally, the program looks for a system localization entry named
  "_EXP_<MYMODULE>"
=============================================================================*/







class EXP_PLUGIN_CLASS
{
	var $options;			// the options to use, format:
							//    	$this->options[<name>] = array(
							//			<typ>, <descr>, <default> [, <more>]
							//		);
							// allowed types:
							// radio,enum	let the user select a value from a list of values
							// text 		let the user enter some text
							// check		a simple checkbox
							// remark 		a remark
	
	var $param;				// some parameters from the options
	
	private $expGrp;		// an identifier that is unique for every allocated file; moreover, this includes the Hex'd user id after the first 8 characters
	
	function __construct()
	{
		$this->options	= array();
		$this->expGrp	= sprintf("%d", time()-0x4fef6f4b);
	}
	
	
	public function getExpGrp()
	{
		return $this->expGrp;
	}
	
	// export is called to start the export progress.
	// $param is initialized with the correct values when this function is called.
	public function export($param)
	{
		$fn = $this->allocateFileName('foobar.txt');
		$handle = fopen($fn, 'w+');
		if( $handle ) {
			fwrite($handle, 'foobar file contents');
			fclose($handle);
		}
	}


	
	// functions to control the export flow, used by derived classes
	protected function progress_info($msg)
	{
		$GLOBALS['g_export_renderer']->progress_info($msg);
	}
	protected function progress_abort($msg)
	{
		$GLOBALS['g_export_renderer']->progress_abort($msg);
	}
	protected function allocateFileName($name)
	{
		// get a filename to use for the export. it's up to the derived classes to do something useful with this
		return $GLOBALS['g_export_renderer']->_allocate_file_name($this->expGrp, $name);
	}

};


