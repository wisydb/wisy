<?php

class G_DBCONFACTORY_CLASS
{
	public $error_str;

	public function create_instance($db_name = '')
	{
		$this->db_name = $_REQUEST['db'];
		if( $this->db_name == '' ) 
		{
			// create instance of default MySQL class
			$instance = new DB_Admin;
		}
		else 
		{
			// create instance of a SQLITE file
			
			// ... the following characters are not allowed in SQLITE file names: /:*?\
			if( preg_match("/[\\/\\:\\*\\?\\\\]/", $this->db_name) ) 
			{ 
				$this->error_str = 'G_DBCONFACTORY_CLASS: bad database file name'; 
				return false; 
			}
			
			// ... get full path of the SQLITE file name (SQLITE files are allowed in the temporary directory only)
			$test = $GLOBALS['g_temp_dir'] . '/' . $this->db_name;
			if( !@file_exists($test) ) 
			{
				$this->error_str = "G_DBCONFACTORY_CLASS: $test does not exist.";
				return false;
			}
			
			// ... create SQLITE object
			$instance = new G_SQLITE_CLASS;
			if( !$instance->open($test) )
			{ 
				$this->error_str = "G_DBCONFACTORY_CLASS: cannot open $test [".$instance->get_last_error()."]";
				return false;
			}
			else
			{
				;
			}
		}

		return $instance;
	}
};

