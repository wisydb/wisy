<?php

// to make sure, job lists etc. are not loaded/saved mangled 
// after transfer of WISY system to other server environments
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', 1);


class G_BIN_CLASS
{
	var $activeBin;	// the current bin
	var $bins; 		// bins[<bin>][<table>][<id>] = 1, NEVER use this field directly,
					// use getBins() and getRecords() instead
	var $access;	// access[bin] = [n|r|e] (for nothing, read, edit)
	var $db;
	
	function __construct()
	{
		$this->init_();
	}

	function db_connect()
	{
		if( !is_object($this->db) )
			$this->db = new DB_Admin;
	}
	
	//
	// get the bins in an array
	// ------------------------------------------------------------------------
	//
	function getBins()
	{
		$ret = array();
		
		reset($this->bins);
		foreach(array_keys($this->bins) as $name) {
			$ret[] = $name;
		}
		
		return $ret;
	}
	
	// does a bin exist?
	function binExists($bin)
	{
	    if( isset( $this->bins[$bin] ) && is_array($this->bins[$bin]) ) {
			return 1;
		}
		else {
			return 0;
		}
	}
	
	// get the correct internal name of a bin list
	function binFind($bin)
	{
		return $bin;
	}
	
	// empty a bin - we will not empty external bin lists
	function binEmpty($bin)
	{
		if( $this->binExists($bin) ) {
			$this->bins[$bin] = array();
			$this->save_();
		}
	}
	
	// add a new bin, the new bin may be external
	// return values:	0 - ok
	//					1 - the bin list exists
	//					2 - external bin list not found or not shared
	function binAdd($bin)
	{
		$bin = strtr($bin, "\\\"'<>|", "      ");
		while( !(strpos($bin, '  ')===false) ) { $bin = str_replace('  ', ' ', $bin); }
	
		if( $this->binExists($bin) ) {
			return 1; // error - the bin list exists
		}
		else {
			if( $this->binIsExt($bin) )
			{
				if( $this->getExtBin_($bin, $localName, $editable) ) {
					$this->bins[$bin] = array();
					ksort($this->bins);
					$this->save_();
					return 0; // success
				}
				else {
					return 2; // error - external bin list not found or not shared
				}
			}
			else
			{
				$this->bins[$bin] = array();
				ksort($this->bins);
				$this->save_();
				return 0; // success
			}
		}
	}
	
	// delete a bin - we will not delete external bin lists, only links to them
	function binDelete($bin)
	{
		if( $this->binExists($bin) )
		{
			// delete bin
			$temp = $this->bins;
			$this->bins = array();
			$firstBinLeft = '';
			reset($temp);
			foreach($temp as $k => $v) {
				if( $k != $bin ) {
					$this->bins[$k] = $v;
					if( !$firstBinLeft ) {
						$firstBinLeft = $k;
					}
				}
			}
			
			// any bin left?
			if( sizeof((array) $this->bins) == 0 ) {
				$this->init_();
			} 
			
			// active bin still valid?
			if( !isset( $this->bins[$this->activeBin] ) || !is_array($this->bins[$this->activeBin]) ) {
				$this->activeBin = is_array($this->bins['default'])? 'default' : $firstBinLeft;
			}
			
			$this->save_();
		}
	}
	
	// change access - we will not change the access of external lists
	function binSetAccess($access /* n, r, e */, $bin)
	{
		if( $this->binExists($bin) && !$this->binIsExt($bin) ) 
		{
			$this->access[$bin] = $access;
			$this->save_();
		}
	}
	
	// change active bin
	function binSetActive($bin)
	{
		if( $this->binExists($bin) && $this->activeBin!=$bin )
		{
			$this->activeBin = $bin;
			$this->save_();
		}
	}

	function binIsExt($bin)
	{
		if( strpos($bin, '@') === false ) {
			return 0;
		}
		else {
			return 1;
		}
	}
	
	function binIsEditable($bin = '')
	{
		// get bin
	    if( !isset( $bin ) || !$bin ) {
			$bin = $this->activeBin;
		}

		// check for editable
		if( !$this->binExists($bin) ) {
			return 0;
		}
		
		if( $this->binIsExt($bin) ) {
			$temp = &$this->getExtBin_($bin, $localName, $editable);
			if( is_object($temp) ) {
				return $editable? 1 : 0;
			}
			else {
				return 0;
			}
		}
		else {
			return 1;
		}
	}
	
	//
	// get the records in form of a two-dimensional array:
	// records[<table>][<id>]
	// ------------------------------------------------------------------------
	//
	function getRecords($table = '', $bin = '')
	{
		// get bin
	    if( !isset( $bin ) || !$bin ) {
			$bin = $this->activeBin;
		}
		
		// get list
		if( $this->binIsExt($bin) )
		{
			$temp = &$this->getExtBin_($bin, $localName, $editable);
			if( is_object($temp) ) {
			    $temp = isset($temp->bins[$localName]) ? $temp->bins[$localName] : null;
			}
			else {
				$temp = array();
			}
		}
		else
		{
			$temp = $this->bins[$bin];
		}

		// return
		if( isset( $table ) && $table ) {
		    return isset( $temp[$table] ) && is_array($temp[$table])? $temp[$table] : array();
		}
		else {
		    return ( isset( $temp ) && is_array($temp) ) ? $temp : array();
		}
	}

	// check id a record exists in a given bin; if no bin is given, the active bin is used
	function recordExists($table, $id, $bin = '')
	{	
		// get bin
	    if( !isset( $bin ) || !$bin ) {
			$bin = $this->activeBin;
		}
		
		// check external bin
		if( $this->binIsExt($bin) ) {
			$temp = &$this->getExtBin_($bin, $localName, $editable);
			if( is_object($temp) ) {
				return $temp->recordExists($table, $id, $localName);
			}
			else {
				return 0;
			}
		}
		
		// check internal bin
		return isset($this->bins[$bin][$table][$id]) && $this->bins[$bin][$table][$id] ? 1 : 0;
	}

	// add a record to a given bin; if no bin is given, the active bin is used
	// return values:
	// 1: record added or already in list
	// 0: record not added
	function recordAdd($table, $id, $bin = '')
	{
		// get bin
	    if( !isset( $bin ) || !$bin ) {
			$bin = $this->activeBin;
		}

		// add to external bin?
		if( $this->binIsExt($bin) ) {
			$temp = &$this->getExtBin_($bin, $localName, $editable);
			if( is_object($temp) && $editable ) {
				return $temp->recordAdd($table, $id, $localName);
			}
			else {
				return 0; // error
			}
		}
		
		// does the bin exist?
		if( !isset( $this->bins[$bin] ) || !is_array($this->bins[$bin]) ) {
			return 0; // error
		}
		
		// add hash for the given table if not yet done
		if( !isset( $this->bins[$bin][$table] ) || !is_array($this->bins[$bin][$table]) ) {
			$this->bins[$bin][$table] = array();
		}
		
		// add record to table hash
		$this->bins[$bin][$table][$id] = 1;
		
		// save all data
		$this->save_();
		
		return 1;
	}
	
	// remove a record from a given bin; if no bin is given, the active bin is used
	// return values:
	// 1: record removed or the record was never in the list
	// 0: record not removed
	function recordDelete($table, $id, $bin = '')
	{
		// get bin
	    if( !isset( $bin ) || !$bin ) {
			$bin = $this->activeBin;
		}

		// remove from external bin?
		if( $this->binIsExt($bin) ) {
			$temp = &$this->getExtBin_($bin, $localName, $editable);
			if( is_object($temp) && $editable ) {
				return $temp->recordDelete($table, $id, $localName);
			}
			else {
				return 0; // error
			}
		}
	
		// does the record table exist?
		if( isset( $this->bins[$bin] ) && is_array($this->bins[$bin]) 
		 && isset( $this->bins[$bin][$table] ) && is_array($this->bins[$bin][$table]) ) 
		{
			// remove record from table hash
			$temp = $this->bins[$bin][$table];
			$this->bins[$bin][$table] = array();
			reset($temp);
			foreach($temp as $k => $v) {
				if( $k != $id ) {
					$this->bins[$bin][$table][$k] = $v;
				}
			}
			
			// remove table hash?
			if( sizeof((array) $this->bins[$bin][$table]) == 0 ) {
				$temp = $this->bins[$bin];
				$this->bins[$bin] = array();
				reset($temp);
				foreach($temp as $k => $v) {
					if( $k != $table ) {
						$this->bins[$bin][$k] = $v;
					}
				}
			}
			
			// save all data
			$this->save_();
			
			return 1; // success
		}
		else
		{
			return 0; // error
		}
	}

	//
	// misc
	// ------------------------------------------------------------------------
	//
	
	// create a script that will update the 'bin images' in the opening window
	// reflecting the state of the active bin
	function updateOpener()
	{
		global $site;
		
		echo "<script type=\"text/javascript\"><!--\n";
		
			echo "binUpdateOpener('"; // rts = records to select
		
			$i = 0;
			reset($this->bins[$this->activeBin]);
			$bin = $this->getRecords('', $this->activeBin);
			foreach($bin as $k1 => $v1)
			{
				if( $i++ ) { echo ':'; }
				echo "$k1:";
				
				$j = 0;
				reset($v1);
				foreach($v1 as $k2 => $v2) {
					if( $j++ ) { echo ' '; }
					echo $k2;
				}
			}
			
			echo "','" .$this->getName($this->activeBin). "','{$site->skin->imgFolder}');";
		
		echo "/" . "/--></script>";
	}

	function getExtUserName($intName)
	{
		list($dummy, $intName) = explode('@', $intName);

		$this->db_connect();
		$this->db->query("SELECT name FROM user WHERE loginname='" .addslashes($intName). "'");
		if( $this->db->next_record() && $this->db->f('name')!='' ) {
			$intName = $this->db->fs('name');
		}
		
		return isohtmlentities($intName);
	}

	// get the name of a list (by internal name)
	function getName($intName)
	{
		if( $this->binIsExt($intName) )
		{
			// correct part before '@'
			$name = explode('@', $intName);
			$name[0] = $name[0]=='default'? htmlconstant('_REMEMBERDEFAULTNAME') : isohtmlentities($name[0]);
			
			// correct part after '@'
			$name[1] = $this->getExtUserName($intName);
			
			// done
			return isohtmlentities($name[0]) . ' @ ' . $name[1];
		}
		else
		{
			// correct name
			return $intName=='default'? htmlconstant('_REMEMBERDEFAULTNAME') : isohtmlentities($intName);
		}
	}
	
	// init 
	function init_()
	{
		$this->activeBin		= 'default';
		$this->bins				= array();
		$this->bins['default']	= array();
	}

	// save all bins
	function save_()
	{
		if( regGet('settings.editable', 1) ) {
			$this->db_connect();
			
			if( is_object($this->db) )   // make sure database connection object is not serialized => causes type problems unserializing in functions.php in PHP >= 8
			    unset($this->db);
			
			$tmpdb = new DB_Admin();
			$tmpdb->query("UPDATE user SET remembered='" .addslashes(serialize($this)). "' WHERE id=$this->userId");
			
			$this->db = new DB_Admin;
		}
	}
	
	function &getExtBin_($bin, &$localName, &$editable)
	{
		$bin = explode('@', $bin);
		if( !isset($_SESSION['g_session_userloginname']) || $bin[1] != $_SESSION['g_session_userloginname'] )
		{
			// $this->db_connect(); // unserialized $this->db not guaranteed > PHP 8
		    $this->db = new DB_Admin();
			if( isset($bin) && (is_array($bin) || is_object($bin)) && isset($bin[1]) )
			 $this->db->query("SELECT remembered, id FROM user WHERE loginname='" .addslashes(strval($bin[1])). "'");
			
			if( $this->db->next_record() ) {
				$ext = unserialize($this->db->fs('remembered'));
				if( is_object($ext) ) {
				    if( isset( $ext->access[$bin[0]] ) && $ext->access[$bin[0]] == 'r'
				     || isset( $ext->access[$bin[0]] ) && $ext->access[$bin[0]] == 'e' ) {
					 	$ext->userId	= $this->db->f('id');
					 	$localName		= $bin[0];
					 	$editable		= $ext->access[$bin[0]]=='e'? 1 : 0;
						return $ext; // external bin found
					}
				}
			}
		}
		
		$nill = null; // b/c this function returns by reference! Users of this function must check against null/object
		
		return $nill; // not found
	}
}