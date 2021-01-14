<?php
/******************************************************************************
Simple SQLite wrapper
*******************************************************************************

Usage:
	$db = new G_SQLITE_CLASS;
	if( $db->open(file) )
	{
		$result = $db->query("SELECT a, b FROM c WHERE d;");
		while( $db->next_record() ) 
		{
			$field = $db->fs('a');
			$field = $db->fs('b');
		}
		$db->close();
	}

As we're using PDO, there is no need to call stripslashes() for the fields -
however, this is a problem as a f() function would not be compatible to MySQL,
where we use stripslashes(f()) ...

Therefore, we implement the new function fs(): This function always returns 
the strings correctly without slashes.

*******************************************************************************

NB: The different slash functions:
Normal String:		' \			<-- if you call stripslashes() on this, the backslashes would be away ...
SQLite::quote()		'' \		
addslashes()		\' \\

*******************************************************************************
Handcrafted by Bjoern Petersen, http://b44t.com
******************************************************************************/



class G_SQLITE_CLASS 
{
	private $filename;
	private $dbHandle;
	private $error_str; // only used if dbHandle ist unset

	
	function __construct()
	{
		$this->dbHandle = false;
	}
	function __destruct()
	{
		$this->close();
	}

	
	/**************************************************************************
	open/close database
	**************************************************************************/

	function open($filename)
	{
		if( $this->dbHandle ) die('there is already a file assigned to this sqlite object.');
		
		// file already opened? (we use the same handle in this case)
		if( $GLOBALS['g_sqlite_handles'][$filename]['usage'] >= 1 )
		{
			$GLOBALS['g_sqlite_handles'][$filename]['usage']++;
			$this->dbHandle = $GLOBALS['g_sqlite_handles'][$filename]['handle'];
			$this->filename = $filename;
			return true; 
		}
		
		// check, if pdo_sqlite is available
		$drivers = PDO::getAvailableDrivers();
		if( !in_array('sqlite', $drivers) ) {
			if( function_exists('dl') ) // die funktion ist auf multithreaded-servern nicht verfuegbar!
			{
				@dl('pdo_sqlite.so');
			}
			$drivers = PDO::getAvailableDrivers();
			if( !in_array('sqlite', $drivers) ) {
			    echo "<script>alert('pdo_sqlite.so not available');</script>";
				$this->error_str = 'pdo_sqlite.so not available';
				return false;
			}
		}
		
		// try to open the file
		try
		{
			$this->dbHandle = new PDO('sqlite:'.$filename);
		}
		catch(Exception $e)
		{
			$this->dbHandle = false;
			$this->error_str = $e->getMessage();
		}
		
		if( !$this->dbHandle )
		{
			return false;
		}

		// file successfully opened
		$GLOBALS['g_sqlite_handles'][$filename]['handle'] = $this->dbHandle;
		$GLOBALS['g_sqlite_handles'][$filename]['usage' ] = 1;
		$this->filename = $filename;
		return true;
	}
		
	function close()
	{
		if( $this->dbHandle )
		{
			if( $this->dbHandle != $GLOBALS['g_sqlite_handles'][$this->filename]['handle'] ) die('bad sqlite handle.');
			
			$GLOBALS['g_sqlite_handles'][$this->filename]['usage']--;
			if( $GLOBALS['g_sqlite_handles'][$this->filename]['usage'] == 0 )
			{
				//$this->dbHandle->close(); -- does not exist
				unset($GLOBALS['g_sqlite_handles'][$this->filename]);
			}
			$this->dbHandle = false;
		}
	}


	/**************************************************************************
	query
	**************************************************************************/
	
	// exec() is simelar to query, but does not return an result set but the number of affected rows
	function exec($query)
	{
		return $this->dbHandle->exec($query);
	}
	
	// query database
	private $resultHandle;
	private $currRecord;
	function query($query)
	{
		$this->resultHandle = $this->dbHandle->query($query);
		return $this->resultHandle !== false;
	}
	
	function next_record()
	{
		if( !$this->resultHandle )
			return false;

		$this->currRecord = $this->resultHandle->fetch(PDO::FETCH_ASSOC);		
		return $this->currRecord; // may be false!
	}
	
	function fs($fieldname) // return the given field, no additional stripslashes() or sth. like that needed!
	{
		return $this->currRecord[$fieldname];
	}
	
	// handle transactions - this is WAY faster than using autocommit!
	function begin_transaction()
	{
		$this->dbHandle->beginTransaction();
	}
	
	function commit()
	{
		$this->dbHandle->commit();
	}
	
	function roll_back()
	{
		$this->dbHandle->rollBack();
	}
	
	function beginTransaction()  { $this->begin_transaction(); } /*beginTransaction() is deprecated*/

	function insert_id()
	{
		return $this->dbHandle->lastInsertId();
	}	
	
	
	/**************************************************************************
	Tools
	**************************************************************************/

	function quote($str)
	{
		// quote() should be used for fields in query() (instead of addslashes())
		// note, that quote() also adds the quotes to the string!
		return $this->dbHandle->quote($str);
	}
	
	function get_last_error()
	{
		$ret = '';
		if( $this->dbHandle ) {
			$code = $this->dbHandle->errorInfo ();
			if( $code[1] ) {
				$ret = sprintf('%s (%d)', $code[2], $code[1]);
			}
		}
		else {
			$ret = $this->error_str; // error from open()
		}
		return 'SQLite error: ' . ($ret==''? 'unknown error' : $ret);
	}

	function table_exists($table)
	{
		$this->query("PRAGMA table_info($table)");
		return $this->next_record()? 1 : 0;
	} 	
};

