<?php

/*=============================================================================
Read a single log file from bottom to top (future to past)
===============================================================================

Author:	
	Bjoern Petersen

===============================================================================

Log file format (fields are separated by Tabs):
	<date> <table> <recordId> <userId> <action> [<key1> <value1a> <value1b> [<key2> <value2a> <value2b> ...]]

Examples:

	2012-05-15 17:23:10 user	 3   3  login  ip <ip> browser <userAgent>
																	// Benutzer #3 loggt sich im Redaktionssystem ein
	2012-05-15 17:24:12 kurse	 456 3  edit   <changes>			// Benutzer kurs 456 wurde vom soeben eingeloggten Anbieter geändert
	2012-05-15 17:24:12 user     3   3  logout						// Benutzer 3 meldet sich wieder ab
	
	2012-05-15 17:23:10 anbieter 123 20 login ip <ip> portal <portalId> browser <userAgent>
																	// anbieter 123 loggt sich vom Portal aus ein, aus sicht des Redaktionssystem ist dies immer User #20
	2012-05-15 17:24:12 kurse    456 20 edit  <changes>				// kurs 456 wurde vom eingeloggten Anbieter geändert: CAVE: sind mehrere Anbieter geleichzeigtig angemeldet, tauchen diese alle als User #20 auf
	2012-05-15 17:24:12 anbieter 123 20 logout						// anbieter 123 meldet sich wieder ab
	
	2012-05-15 17:23:10 user     0   0  loginfailed ip <ip>			// bad login attempt from unknown user
	2012-05-15 17:23:10 user     4   4  loginfailed ip <ip>			// bad login attempt from user #4
	2012-05-15 17:23:10 anbieter 4   20 loginfailed ip <ip>			// bad login attempt from user #4

	2012-05-15 17:23:23 stichwoerter 3456 5 create					// stichwort 3456 created by user 5
	2012-05-15 17:23:23 stichwoerter 2345 5 delete <backup>			// stichwort 2345 deleted by user 5

with <changes>:

	<fieldName> <oldValue> <newValue> [<fieldName> <oldValue> <newValue> ...]
	
	with <fieldName>:
		<primaryFieldName>
	or	<secondaryTable>.<secondaryId>.<secondaryFieldName>

with <backup>:

	<fielName> <empty> <value> [<fieldName> <empty> <value> ...]

=============================================================================*/

 

class LOG_FILE_CLASS
{
	private $lines;
	private $l;
	private $filterTable;
	private $filterId;
	private $filterUser;
	private $currLineNum;
	
	
	public	$linesSaved;
	public	$linesTotal;
	public	$bytesSaved;
	public	$bytesTotal;
	public  $time1;
	public  $time2;
	
	function __construct($filename, $filterTable, $filterId, $filterUser, $showAll)
	{
		// read the file read
		$this->time1 = microtime(true);
	
			$this->lines = @file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		
		$this->time1 = microtime(true) - $this->time1;
		
		// settings
		$this->filterTable	= $filterTable;
		$this->filterId    	= $filterId;
		$this->filterUser	= $filterUser;
		
		$this->linesSaved = 0;
		$this->linesTotal = 0;
		$this->bytesSaved = 0;
		$this->bytesTotal = 0;

		// create records, mark minor edits
		$this->time2 = microtime(true);
		
			$skip = array();
			$l = 0;
			while( $l < sizeof((array) $this->lines) )
			{
				// create record from $lines
				$record    	= explode("\t", $this->lines[ $l ]);
				$recordBytes= strlen($this->lines[ $l ]);
				$record[2]	= explode(",", str_replace(' ', '', $record[2])); // record ids may be comma separated (used eg. for multi edit)
				
				// is this line a "minor" edit?
				$minorEdit = false;
				
				if( sizeof($record[2]) == 1 )
				{
					if( $record[4] == 'create' )
					{
						$skip[ $record[1] ][ $record[2][0] ] = $record[3];
					}
					else if( $record[4] == 'edit' )
					{
						if( isset($skip[ $record[1] ][ $record[2][0] ]) ) {
							if( $skip[ $record[1] ][ $record[2][0] ] == $record[3] ) {
								$minorEdit = true; // edit after creation from the same user, skip this minor edit
							}
							else {
								$skip[ $record[1] ][ $record[2][0] ] = -1; // edit by a different user, no longer skip minor edits
							}
						}
						if( $record[5]=='' ) {
							$minorEdit = true; // hitting "save" whithout any changed, skip this edit
						}
					}
				}
				
				
				// store record back to $lines
				if( $minorEdit && !$showAll )
					$record[3] = -1;
				$this->lines[ $l ] = $record;
				
				// stats
				$this->linesTotal++;
				$this->bytesTotal += $recordBytes;
				if( $minorEdit ) {
					$this->linesSaved++;
					$this->bytesSaved += $recordBytes;
				}
				
				$l++;
			}
			
		$this->time2 = microtime(true) - $this->time2;
		
		// prepare for next_record()
		$this->currLineNum = sizeof((array) $this->lines);
	}
	
	function next_record()
	{
		while( $this->currLineNum > 0 ) 
		{
			// read the record
			$this->currLineNum--;
			$record = $this->lines[ $this->currLineNum ];
			
			// does the record match the filter?
			if( ($this->filterTable== '' || $this->filterTable== $record[1])
			 && ($this->filterId==0      || in_array($this->filterId, $record[2]))
			 && ($this->filterUser == 0  || $this->filterUser == $record[3]) && $record[3]!=-1 /*minorEdit*/ )
			{
				return $record; // fine, record found
			}
		}
		
		return false; // no (more) records found
	}
	
	function get_curr_line_number()
	{
		// returns the line number of the last successfull call to next_record(); the line number may be 0.
		// if the last call to next_record has failed, the return is undefined.
		return $this->currLineNum;
	}
	
	function get_record_by_line($lineNum)
	{
		return $this->lines[ $lineNum ];
	}
};