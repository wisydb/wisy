<?php

/*=============================================================================
Lightweight MySQL queries
===============================================================================

Author:
	Bjoern Petersen

===============================================================================

die Klasse DB_Sql etabliert eine "leichtgewichtige" MySQL-Verbindung:
es wird für alle Instanzen von DB_Sql (bzw. DB_Admin) nur *eine* echte 
mysql_connect() Verbindung aufgebaut.

Achtung: die Klasse liest ein Ergebnis komplett ein, noch bevor query() 
zurückkehrt.  Für die meisten Aufgaben, die in einer PHP-Seite notwendig werden, 
ist dies in Ordnung; bei sehr speicherintensiven Aufgaben, kann dies aber zum 
Problem werden.
Lösungen hierfür:
- die mysql-funktionen direkt verwenden
- zusätzliche LIMIT-Anweisungen verwenden
- use_phys_connection verwenden, bei großen Abfragen (100.000 Datensätze) ist 
  dies ca. doppelt so schnell wie das Aufteilen in 1000er-LIMIT-Stückchen

Achtung: wenn man verschiedene Ableitungen von DB_Sql verwenden möchte, muss man zwischen
verschiedenen globalen Datenbankparametern und Handles unterscheiden

=============================================================================*/



// wrapper for Mysqli
if( !@function_exists('mysql_connect') )
{
	function mysql_connect($host, $user, $pw, $new_link)
	{
		return mysqli_connect($host, $user, $pw);
	}

	function mysql_select_db($database, $link_obj)
	{
		$success = mysqli_select_db($link_obj, $database);
		mysqli_query($link_obj, "SET SESSION sql_mode='';"); /* otherwise, MySQL complains about many things as missing default values and so on. sql_mode was introduced in MySQL 5.1.8 */
		return $success;
	}

	function mysql_query($query, $link_obj)
	{
		return mysqli_query($link_obj, $query); // returns result_obj of class mysqli_result
	}

	function mysql_affected_rows($link_obj)
	{
		return mysqli_affected_rows($link_obj);
	}

	function mysql_insert_id($link_obj)
	{
		return mysqli_insert_id($link_obj);
	}

	function mysql_num_rows($result_obj)
	{
		return mysqli_num_rows($result_obj);
	}

	function mysql_fetch_assoc($result_obj)
	{
		return mysqli_fetch_assoc($result_obj);
	}

	function mysql_free_result($result_obj)
	{
		mysqli_free_result($result_obj);
	}

	function mysql_error($link_obj=null)
	{
		return $link_obj? mysqli_error($link_obj) : mysqli_connect_error();
	}

	function mysql_errno($link_obj=null)
	{
		return $link_obj? mysqli_errno($link_obj) : mysqli_connect_errno();
	}
}


// PHP 7 changes the default characters set to UTF-8; we still prefer ISO-8859-1
@ini_set('default_charset', 'ISO-8859-1');


class DB_Sql
{
	// settings, changeable by the user
	public $Host     			= "";
	public $Database			= "";
	public $User     			= "";
	public $Password			= "";
	public $Halt_On_Error		= "yes"; // "yes" (halt with message), "no" (ignore errors quietly), "report" (ignore error, but spit a warning)
	
	// settings, readable by the user - however, we recommend to use the appropriate functions instead
	public $Result				= array();
	public $ResultNumRows		= 0;
	public $Record				= array();
	public $Errno    			= 0;
	public $Error    			= '';
	
	// private stuff
	private $ResultAffectedRows	= 0;
	private $ResultInsertId		= 0;
	private $Link_ID 			= 0;
	private $ResultI			= 0;
	private $phys_query_id		= 0;
	
	// the constructor
	function __construct($flags='')
	{
		$this->use_phys_connection = $flags=='use_phys_connection'? true : false;
	}

	private function log($file, $msg)
	{
		// open the file
		$fullfilename = '../files/logs/' . strftime("%Y-%m-%d") . '-' . $file . '.txt';
		$fd = @fopen($fullfilename, 'a');
		if( $fd )
		{
			$GLOBALS['sql_log_cnt']++;
			$line = strftime("%Y-%m-%d %H:%M:%S") . ': #' . $GLOBALS['sql_log_cnt'] . ': ' . $msg . "\n";	
			@fwrite($fd, $line);
			@fclose($fd);
		}
	}
	
	/**************************************************************************
	 * Connect to Database
	 **************************************************************************/
	
	private function phys_connect()
	{
		$GLOBALS['g_total_db_count'] += 1;
		if( $GLOBALS['g_total_db_count'] > 3 )
		{
			$this->halt("max_user_connections error precaution: This page will produce too many database connections.");
			return 0;
		}

		$link_id = @mysql_connect($this->Host, $this->User, $this->Password, true /*force new*/); // using connect() instead of pconnect() - this is a little bit slower, but safer when using locks()
		if( !$link_id ) {
			$this->halt("pconnect($this->Host, $this->User, <i>password</i>) failed.");
			return 0;
		}

		if( !@mysql_select_db($this->Database, $link_id) ) {
			$this->halt("cannot use database ".$this->Database);
			return 0;
		}
		
		@mysql_query('SET MAX_JOIN_SIZE=128000000;', $link_id);	// sollte ausreichen, sehr komplexe Abfragen im Red.System werden einen Fehler erzeugen, aber das ist dann auch i.O.
		@mysql_query('SET SQL_BIG_SELECTS=0;', $link_id);		// joins>MAX_JOIN_SIZE erlauben?
		return $link_id;
	}
	
	private function lazy_connect()
	{
		if( !isset($GLOBALS['g_link_id']) ) {
			
			$GLOBALS['g_link_id'] = $this->phys_connect();
		}
		return $GLOBALS['g_link_id'];
	}

	/**************************************************************************
	 * Main functionality
	 **************************************************************************/
	
	function query($query_string, $query_limit_offset = -1, $query_limit_rows = -1)
	{
		// a rough look at the query string
		if( $query_string == '' ) {
			return 0;
		}
		if( $query_limit_offset >= 0 && $query_limit_rows > 0 ) 
		{
			$query_string .= " LIMIT $query_limit_offset, $query_limit_rows";
			echo '<h1>DB_Sql::query(): using parameter 2 and 3 for limits is deprecated!</h1>';
		}

		//$this->log('sql', $query_string);
		
		if( $this->use_phys_connection )
		{
			if( !$this->Link_ID ) {
				$this->Link_ID = $this->phys_connect();									// connect
				if( !$this->Link_ID ) return 0; 
			}

			$this->phys_query_id = @mysql_query($query_string, $this->Link_ID);			// do the query
			if( !$this->phys_query_id )  { $this->halt("Invalid SQL: " . $query_string); return 0; }
			
			$this->ResultAffectedRows	= @mysql_affected_rows($this->Link_ID); 		// fetch some result information
			$this->ResultInsertId		= @mysql_insert_id($this->Link_ID);
			$this->ResultNumRows		= @mysql_num_rows($this->phys_query_id);
			return 1; // done
		}
		else
		{
			$this->Link_ID = $this->lazy_connect();										// connect
			if( !$this->Link_ID ) return 0; 
			
			$lazy_query_id = @mysql_query($query_string, $this->Link_ID);				// do the query
			if( !$lazy_query_id )  { $this->halt("Invalid SQL: " . $query_string); return 0; }
			
			$this->ResultAffectedRows	= @mysql_affected_rows($this->Link_ID); 		// fetch some result information
			$this->ResultInsertId		= @mysql_insert_id($this->Link_ID);
			$this->ResultNumRows		= 0;
			$this->ResultI 				= 0;

			$this->Result = array(); // fetch the complete result
			while( 1 )
			{
				$this->Result[ $this->ResultNumRows ] = @mysql_fetch_assoc($lazy_query_id);
				if(  !is_array($this->Result[ $this->ResultNumRows ]) )
					break;
				$this->ResultNumRows++;
			}
			
			@mysql_free_result($lazy_query_id); // done
			return 1;
		}
	}
	
	function next_record()
	{
		if( $this->use_phys_connection )
		{
			$this->Record = @mysql_fetch_assoc($this->phys_query_id);
			if( is_array($this->Record) ) {
				return true;
			}
			else {
				@mysql_free_result($this->phys_query_id);
				$this->phys_query_id = 0;
				return false;
			}
		}
		else
		{
			if( $this->ResultI >= $this->ResultNumRows ) {
				return 0;  // no more records - this is no error
			}
			$this->Record =& $this->Result[ $this->ResultI ];
			$this->ResultI++;
			return 1; // next record
		}
	}
	
	function quote($str)
	{
		return "'" . addslashes($str) . "'";		// you should prefer quote() instead of calling addslashes() directly - this makes stuff compatible to SQLite
	}
	
	function f($Name)
	{
		return $this->Record[$Name];
	}
	function fs($Name)
	{
		return $this->Record[$Name];	// if possible, always use fs() instead of f() - this makes stuff compatible to SQLite
										// stripslashes() is no longer needed, mysql_fetch_assoc() does _not_ add slashes (and has never done before)
	}
	
	function f8($Name)
	{
		return utf8_encode($this->Record[$Name]);	// UTF-8 encode because the DB is still ISO-encoded. Used in core50
	}

	function affected_rows() 
	{
		return $this->ResultAffectedRows;
	}

	function num_rows() 
	{
		return $this->ResultNumRows;
	}

	function insert_id()
	{
		return $this->ResultInsertId;
	}
	
	function free()
	{
		$this->Result = array();
		$this->ResultNumRows = 0;
		$this->ResultAffectedRows = 0;
		$this->ResultInsertId = 0;
		if( $this->phys_query_id ) {
			@mysql_free_result($this->phys_query_id);
			$this->phys_query_id = 0;
		}
	}

	private function halt($msg) 
	{
		if( $this->Link_ID ) {
			$this->Error = @mysql_error($this->Link_ID);
			$this->Errno = @mysql_errno($this->Link_ID);
		}
		else {
			$this->Error = @mysql_error();
			$this->Errno = @mysql_errno();
		}
		if( $this->Halt_On_Error == 'no' )
			return;

		printf('<p style="border: 2px solid black;"><b>DB_Sql error:</b> %s<br>MySQL says: Errno %s - %s</p>', $msg, $this->Errno, $this->Error);
		
		if ($this->Halt_On_Error != "report")
			die("Session halted.");
	}

	/**************************************************************************
	 * Additional tools (just using the main functionlity)
	 **************************************************************************/
 	
	function lock($table, $mode="write")
	{
		$query="lock tables ";
		if (is_array($table))
		{
			while (list($key,$value)=each($table)) 
			{
				if ($key=="read" && $key!=0) {
					$query.="$value read, ";
				} else {
					$query.="$value $mode, ";
				}
			}
			$query=substr($query,0,-2);
		} else {
			$query.="$table $mode";
		}
		$res = $this->query($query);
		if (!$res) {
			return 0;
		}
		return $res;
	}
  
	function unlock()
	{
		$res = $this->query("unlock tables");
		if (!$res) {
			return 0;
		}
		return $res;
	}

	function table_exists($table)
	{
		$this->query("SHOW TABLES LIKE '$table'");
		return $this->next_record()? 1 : 0;
	}
  
	function column_exists($table, $column)
	{
		$this->query("SHOW COLUMNS FROM $table");
		while( $this->next_record() )
		{
			if( $this->f('Field') == $column )
				return 1;
		}
		return 0;
	}

	function index_info($table)
	{
		$ret = array();
		$this->query("SHOW INDEX FROM $table");
		while( $this->next_record() )
		{
			$info =& $this->Record;
			$fulltext = 0;
			if( !(strpos(strtoupper($info['Comment']), 'FULLTEXT')===false)
			 || !(strpos(strtoupper($info['Index_type']), 'FULLTEXT')===false) ) 
			{
				$fulltext = 1;
			}
  	
			$ret[$info['Key_name']]['fulltext']							= $fulltext;
			$ret[$info['Key_name']]['primary']							= $info['Key_name']=='PRIMARY'? 1 : 0;
			$ret[$info['Key_name']]['unique']							= $info['Non_unique']? 0 : 1;
			$ret[$info['Key_name']]['cardinality']						= intval($info['Cardinality']);
			$ret[$info['Key_name']]['fields'][$info['Seq_in_index']-1]	= $info['Column_name'];
		}
		return $ret;
	}

}

