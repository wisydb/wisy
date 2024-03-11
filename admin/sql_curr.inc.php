<?php

/*=============================================================================
Lightweight MySQL queries
===============================================================================

Author:
	Bjoern Petersen

===============================================================================

die Klasse DB_Sql etabliert eine "leichtgewichtige" MySQL-Verbindung:
es wird fuer alle Instanzen von DB_Sql (bzw. DB_Admin) nur *eine* echte 
mysql_connect() Verbindung aufgebaut.

Achtung: die Klasse liest ein Ergebnis komplett ein, noch bevor query() 
zurueckkehrt.  Fuer die meisten Aufgaben, die in einer PHP-Seite notwendig werden, 
ist dies in Ordnung; bei sehr speicherintensiven Aufgaben, kann dies aber zum 
Problem werden.
Loesungen hierfuer:
- die mysql-funktionen direkt verwenden
- zusaetzliche LIMIT-Anweisungen verwenden
- use_phys_connection verwenden, bei grossen Abfragen (100.000 Datensaetze) ist 
  dies ca. doppelt so schnell wie das Aufteilen in 1000er-LIMIT-Stoeckchen

Achtung: wenn man verschiedene Ableitungen von DB_Sql verwenden moechte, muss man zwischen
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
	    try {
	        if( !$result = mysqli_query($link_obj, $query) ) {
	            throw new Exception( mysqli_error($link_obj) );
	        }
	    }
	    catch (Exception $e) {
	        $e_msg = is_object($e) ? $e->getMessage(). "<br><br>" : '';
	        
	        die( "SQL Fehler" );
	        // Only if debugging(!): 
	        // die( "SQL Fehler:<br>" . $e_msg . "<b>" . $query . "</b>" );
	    }
	    
	    
		return $result; // returns result_obj of class mysqli_result
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
	    if( !is_bool($result_obj) )
		  return mysqli_fetch_assoc($result_obj);
	}

	function mysql_free_result($result_obj)
	{
	    if( !is_bool($result_obj) )
		  mysqli_free_result($result_obj);
	}

	function mysql_error($link_obj=null)
	{
		return $link_obj? mysqli_error($link_obj) : mysqli_connect_error();
	}

	function mysql_errno($link_obj=null)
	{
		if(function_exists("mysqli_connect_errno"))
			return $link_obj? mysqli_errno($link_obj) : mysqli_connect_errno();
		else
			return $link_obj? $link_obj->connect_error : '<unknown error no>';
	}
}


// PHP 7 changes the default characters set to UTF-8; we still prefer ISO-8859-1
if(substr(PHP_VERSION, 0, 1) > 6)
	@ini_set('default_charset', 'ISO-8859-1');


class DB_Sql
{
	// settings, changeable by the user
	public $Host     			= "";
	public $Database			= "";
	public $User     			= "";
	public $Password			= "";
	public $PasswordPreset	    = "";
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
		$fullfilename = '../files/logs/' . ftime("%Y-%m-%d") . '-' . $file . '.txt';
		
		$fd = @fopen($fullfilename, 'a');
		if( $fd )
		{
		    $GLOBALS['sql_log_cnt'] = isset($GLOBALS['sql_log_cnt']) ? $GLOBALS['sql_log_cnt'] + 1 : 1;
		    $line = ftime("%Y-%m-%d %H:%M:%S") . ': #' . $GLOBALS['sql_log_cnt'] . ': ' . $msg . "\n";	
			@fwrite($fd, $line);
			@fclose($fd);
		}
	}
	
	/**************************************************************************
	 * Connect to Database
	 **************************************************************************/
	
	private function phys_connect()
	{
	    if( isset( $GLOBALS['g_total_db_count'] ) )
		 $GLOBALS['g_total_db_count'] += 1;
	    
		 if( isset( $GLOBALS['g_total_db_count'] ) && $GLOBALS['g_total_db_count'] > 3 )
		{
			$this->halt("max_user_connections error precaution: This page will produce too many database connections.");
			return 0;
		}
		
		if( strlen ($this->PasswordPreset) > 3 )
		    $this->Password = $this->PasswordPreset;
		
			$link_id = @mysql_connect($this->Host, $this->User, $this->Password, true /*force new*/); // using connect() instead of pconnect() - this is a little bit slower, but safer when using locks()
		if( !$link_id ) {
			$this->halt("pconnect($this->Host, $this->User, <i>password</i>) failed.");
			return 0;
		}

		if( !@mysql_select_db($this->Database, $link_id) ) {
			$this->halt("cannot use database ".$this->Database);
			return 0;
		}
		
		// Do use the following settings if database/server only allows for UTF-8 connections! (instead of iso8859-* etc)
		// mysql_query('SET character_set_client=latin1;', $link_id);
		// mysql_query('SET character_set_connection=latin1;', $link_id);
		// mysql_query('SET character_set_results=latin1;', $link_id);
		
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
		
		if( isset( $this->use_phys_connection ) && $this->use_phys_connection )
		{
		    if( !isset( $this->Link_ID ) || !$this->Link_ID ) {
				$this->Link_ID = $this->phys_connect();									// connect
				if( !$this->Link_ID ) return 0; 
			}

			$this->phys_query_id = @mysql_query($query_string, $this->Link_ID);			// do the query
			if( !$this->phys_query_id )  { $this->halt("Invalid SQL OR connection closed before Query: " . $query_string); return 0; }
			
			$affectedRows = @mysql_affected_rows($this->Link_ID);                       // fetch some result information
			$this->ResultAffectedRows	= $affectedRows ? $affectedRows : 0; 		
			
			$insertID = @mysql_insert_id($this->Link_ID);
			$this->ResultInsertId		= $insertID ? $insertID : 0;
			
			$physQueryID = @mysql_num_rows($this->phys_query_id);
			$this->ResultNumRows		= $physQueryID ? $physQueryID : 0;
			
			return 1; // done
		}
		else
		{
			$this->Link_ID = $this->lazy_connect();										// connect
			$mysqli = is_object($this->Link_ID);
			
			if( !$this->Link_ID )
				return 0; 

			if( !$mysqli ) {
			    $noMySqli = mysql_query($query_string, $this->Link_ID);
			    $lazy_query_id = $noMySqli ? $noMySqli : 0;				
		    }
		    else {
		        $yesMySqli = mysql_query($query_string, $this->Link_ID);
		        $lazy_query_id = $yesMySqli ? $yesMySqli : 0;
		    }
			
			
			if( !$lazy_query_id )  { $this->halt("Invalid SQL OR connection closed before Query: " . $query_string); return 0; }
			
			$affectedRows = @mysql_affected_rows($this->Link_ID);
			$this->ResultAffectedRows	= $affectedRows ? $affectedRows : 0;
			
			$insertID = @mysql_insert_id($this->Link_ID);
			$this->ResultInsertId		= $insertID ? $insertID : 0;
			
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
	    if( isset( $this->use_phys_connection ) && $this->use_phys_connection )
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
		    if( isset( $this->ResultI ) && $this->ResultI >= $this->ResultNumRows ) {
				return 0;  // no more records - this is no error
			}
			$this->Record =& $this->Result[ $this->ResultI ];
			$this->ResultI++;
			return 1; // next record
		}
	}
	
	function prev_record()
	{
	    // correct?
	    if( isset( $this->use_phys_connection ) && $this->use_phys_connection )
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
	        if( isset( $this->ResultI ) && $this->ResultI < 1 ) {
	            return 0;  // no more records - this is no error
	        }
	        $this->Record =& $this->Result[ $this->ResultI ];
	        $this->ResultI--;
	        return 1; // prev record
	    }
	}

	/**
	 * Executes a SQL query with prepared statements and returns the result.
	 *
	 * @param string $query The SQL query to execute.
	 * @param array|null $params An array of parameters to bind to the query.
	 * @param string|null $types A string of types for the parameters. The string must contain one letter for each parameter. The possible types are "i" for integer, "d" for double, "s" for string, and "b" for blob.
	 * @param string|null $from_encoding The character encoding of the parameters.
	 * @param string|null $to_encoding The character encoding to convert the parameters to.
	 * @return array|true The fetched results as an associative array.
	 * @throws Exception Throws an exception if there is a database connection error or a query error.
	 */
	public function execute(string $query, array $params = null, string $types=null, string $from_encoding = null, string $to_encoding = null): array|bool {
		// Establish a connection if one doesn't exist.
		if (!isset($this->Link_ID) || !$this->Link_ID ) {
			$this->Link_ID = $this->lazy_connect();
			
			if (!$this->Link_ID) {
				error_log('Unable to establish a MySQL connection');
				throw new Exception('Database connection error');
			}
		}

		// Prepare the statement.
		$stmt = mysqli_prepare($this->Link_ID, $query);
		if (!$stmt) {
			error_log('Failed to prepare the statement: ' . mysqli_error($this->Link_ID));
			throw new Exception('Database query error');
		}

		if (!empty($params)) {
			// Infer the types of the parameters if not provided.
			$types = $types ?? $this->inferTypes($params);

			// Convert the parameters to the specified encoding.
			if ($from_encoding && $to_encoding) {
				$params = array_map(function($param) use ($from_encoding, $to_encoding){
					return is_string($param) ? mb_convert_encoding($param, $to_encoding, $from_encoding) : $param;
				}, $params);
			}

			// Bind the parameters to the statement.
			if (!$stmt->bind_param($types, ...$params)) {
				error_log('Failed to bind parameters: ' . $stmt->error);
				throw new Exception('Database query error');
			}
		}

		// Execute the statement.
		if (!$stmt->execute()) {
			error_log('Failed to execute the statement: ' . $stmt->error);
			throw new Exception('Database query error');
		}

		// Fetch the results.
		return $this->fetchResults($stmt, $to_encoding, $from_encoding);
	}

	/**
	 * Infers the types of the elements in the given array and returns a string representation of the types.
	 *
	 * @param array $params The array containing the elements to infer the types from.
	 * @return string The string representation of the inferred types.
	 */
	protected function inferTypes(array $params): string {
		$types = "";
		foreach ($params as $param) {
			if (is_int($param)) {
				$types .= "i";
			} elseif (is_float($param)) {
				$types .= "d";
			} else {
				$types .= "s";
			}
		}
		return $types;
	}

	/**
	 * Fetches the results from a MySQLi prepared statement.
	 *
	 * @param mysqli_stmt $stmt The prepared statement object.
	 * @param string|null $from_encoding The encoding of the results to convert from.
	 * @param string|null $to_encoding The encoding of the results to convert to.
	 * @return array|true The fetched rows as an associative array, or true for INSERT, DELETE, and UPDATE queries.
	 * @throws Exception If there is an error in the database query or failed to fetch the rows.
	 */
	protected function fetchResults(mysqli_stmt $stmt, string $from_encoding = null, string $to_encoding = null): array|bool {
		$result = $stmt->get_result();
		if ($result === false) {
			if ($stmt->errno) {
				error_log('Error in MySQL query: ' . $stmt->error);
				throw new Exception('Database query error');
			} else {
				// For INSERT, DELETE, UPDATE, return true on success
				return true;
			}
		}

		// For SELECT, SHOW, DESCRIBE or EXPLAIN fetch the rows.
		$rows = $result->fetch_all(MYSQLI_ASSOC);
		if ($stmt->error) {
			error_log('Failed to fetch the rows: ' . $stmt->error);
			throw new Exception('Failed to fetch the rows: ' . $stmt->error);
		}
		$this->ResultAffectedRows = $stmt->affected_rows;
		$this->ResultInsertId = $stmt->insert_id;
		$this->ResultNumRows = $result->num_rows;
		$this->ResultI 				= 0;

		// Convert the results to the requested encoding.
		if ($from_encoding && $to_encoding) {
			$rows = array_map(function($row) use ($from_encoding, $to_encoding) {
				return array_map(function($value) use ($from_encoding, $to_encoding) {
					return is_string($value) ? mb_convert_encoding($value, $to_encoding, $from_encoding) : $value;
				}, $row);
			}, $rows);
		}

		$this->Result = $rows;
		return $rows;
	}

	/**
	 * Creates an IN clause for SQL queries.
	 *
	 * @param array $values The values to be used in the IN clause.
	 * @return array A tupel containing the IN clause and the values.
	 */
	public function createInClause(array $values): array {
		$placeholders = implode(',', array_fill(0, count($values), '?'));
		return array("IN ($placeholders)", $values);
	}

	/**
	 * Escapes a string for use in a MySQL query.
	 *
	 * @param string $str The string to be escaped.
	 * @return string The escaped string.
	 * @throws Exception If there is no valid MySQL connection or if an error occurs during escaping.
	 */
	public function escape($str): string
	{
		if (!isset($this->Link_ID) || !$this->Link_ID ) {
			$this->Link_ID = $this->lazy_connect(); // Establish a connection if one doesn't exist
			if (!$this->Link_ID) {
				throw new Exception('Unable to establish a MySQL connection');
			}
		}
		try {
			return mysqli_real_escape_string($this->Link_ID, $str);
		} catch (Exception $e) {
			throw new Exception('Error in MySQL escape: ' . $e->getMessage());
		}
	}
	
	function quote($str)
	{
		return "'" . addslashes(strval($str)) . "'";		// you should prefer quote() instead of calling addslashes() directly - this makes stuff compatible to SQLite
	}
	
	function f($Name)
	{
	    return ( isset( $this->Record[$Name] ) ? $this->Record[$Name] : null );
	}
	function fs($Name)
	{
	    return ( isset( $this->Record[$Name] ) ? $this->Record[$Name] : null );	// if possible, always use fs() instead of f() - this makes stuff compatible to SQLite
										                                    // stripslashes() is no longer needed, mysql_fetch_assoc() does _not_ add slashes (and has never done before)
	}
	
	/**
	 * Returns the value of a specific field from the current record converted to UTF-8 encoding.
	 *
	 * @param string $Name The name of the field.
	 * @return string|null The value of the field, converted to UTF-8 encoding, or null if the field is not set.
	 */
	public function f8($Name): ?string
	{
		return (isset($this->Record[$Name]) ? mb_convert_encoding($this->Record[$Name], 'UTF-8', 'ISO-8859-1') : null); // utf8_encode is depracted since PHP 8.2
	}
	
	function fcs8($Name)
	{
	    return $this->fs($Name);
	    
		// ! return (substr(PHP_VERSION_ID, 0, 1) > 6) ? $this->Record[$Name] : utf8_encode($this->Record[$Name]);	// UTF-8 only if not > PHP7
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
	
	function close() {
		$this->free();
		
		if( isset( $this->Link_ID ) && $this->Link_ID && function_exists("mysql_close") )
			return mysql_close($this->Link_ID); // @mysql...
	    elseif( isset( $this->Link_ID ) && $this->Link_ID && function_exists("mysqli_close") )
			return mysqli_close($this->Link_ID); // @mysql...
		else
			return false;
	}
	
	/* function close() {
		$this->free();
		
		if( isset( $this->Link_ID ) &&  $this->Link_ID )
			return true; // mysql_close($this->Link_ID); // @mysql... #PHP7
		else
			return false;
	} */
	
	function free()
	{
		$this->Result = array();
		$this->ResultNumRows = 0;
		$this->ResultAffectedRows = 0;
		$this->ResultInsertId = 0;
		if( isset( $this->phys_query_id ) && $this->phys_query_id ) {
			@mysql_free_result($this->phys_query_id);
			$this->phys_query_id = 0;
		}
	}

	private function halt($msg) 
	{
	    if( isset( $this->Link_ID ) && $this->Link_ID ) {
			$this->Error = @mysql_error($this->Link_ID);
			$this->Errno = @mysql_errno($this->Link_ID);
		}
		else {
			$this->Error = @mysql_error();
			$this->Errno = @mysql_errno();
		}
		if( isset( $this->Halt_On_Error ) && $this->Halt_On_Error == 'no' )
			return;

		echo '<p style="border: 2px solid black;"><b>DB-Anfrage error:</b>'.$this->Errno.'</b></p>';
		
		// ! printf('<p style="border: 2px solid black;"><b>DB_Sql error:</b> %s<br>MySQL says: Errno %s - %s</p>', $msg, $this->Errno, $this->Error);
		
		// file_put_contents("error.sql", $msg);
		
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
			$infoComment = isset( $info['Comment'] ) ? $info['Comment'] : null;
			$infoIndexType = isset( $info['Index_type'] ) ? $info['Index_type'] : null;
			
			if( !(strpos(strtoupper($infoComment), 'FULLTEXT')===false)
			 || !(strpos(strtoupper($infoIndexType), 'FULLTEXT')===false) ) 
			{
				$fulltext = 1;
			}
  	
			$ret[$info['Key_name']]['fulltext']							= $fulltext;
			$ret[$info['Key_name']]['primary']							= isset($info['Key_name']) && $info['Key_name']=='PRIMARY' ? 1 : 0;
			$ret[$info['Key_name']]['unique']							= isset($info['Non_unique']) && $info['Non_unique'] ? 0 : 1;
			$ret[$info['Key_name']]['cardinality']						= isset($info['Cardinality']) ? intval($info['Cardinality']) : null;
			$ret[$info['Key_name']]['fields'][$info['Seq_in_index']-1]	= isset($info['Column_name']) ? $info['Column_name'] : null;
		}
		return $ret;
	}
	
	function get_stat() {
	    if( isset( $this->Link_ID ) && $this->Link_ID )
			return mysqli_stat ( $this->Link_ID );
	
		return false;
	}

}

