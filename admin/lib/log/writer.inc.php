<?php



class LOG_WRITER_CLASS
{
	private $data;
	
	function __construct()
	{
		$this->clear();
	}

	function clear()
	{
		$this->data = array(); // $data is array( key1=>value1, key2=>array(value2a,value2b), ... )
	}
	
	function getFilename($date)
	{
		if( $GLOBALS['g_logs_dir'] )
		{
			$db = new DB_Admin;
			$filename = CMS_PATH . $GLOBALS['g_logs_dir'] . '/' . $date . '-cms-' . $db->Database . '.txt';
			return $filename;
		}
		else
		{
			return '';
		}
	}

	function log($table, $recordIds, $userId, $action) // $recordIds may be a comma separated list
	{
		$tr = array("\\"=>"/", "\n"=>"\\n", "\r"=>"", "\t"=>" ");
		
		$line = strftime("%Y-%m-%d %H:%M:%S") . "\t" . $table . "\t" . $recordIds . "\t" . $userId . "\t" . $action . "\t";

		reset($this->data);
		while( list($key, $v) = each($this->data) )
		{
			if( is_array($v) ) {
				if( $v[0] != $v[1] ) {
					$line .= $key . "\t" . strtr($v[0], $tr) . "\t" . strtr($v[1], $tr) . "\t";
				}
			}
			else {
				$line .= $key . "\t\t" /*no old value*/ . strtr($v, $tr) /*new value*/ . "\t";
			}
		}

		$line .= "\n";

		// get the file name
		$fullfilename = $this->getFilename(strftime("%Y-%m-%d"));
		if( $fullfilename )
		{
			// open the logging file
			$handle = @fopen($fullfilename, 'a');
			if( !$handle )
			{
				$handle = @fopen($fullfilename.'-2ndtry-'.time(), 'a');  // if the file cannot be opened, log to a separate file - if this happens too often, we should retry to open the original file after a little timeout (or use 
				if( !$handle ) { return; }
			}
			
			// append the line to the logging file and close it
			@fwrite($handle, $line);
			@fclose($handle);
		}
		
		// prepare for next
		$this->clear();
	}

	function addData($key, $value)
	{
		$this->data[ $key ] = $value;
	}
	
	function addDataFromTable($table, $id, $what, $rownameprefix = '', $isSecondary = false) // $isSecondary is for internal use only
	{
		switch( $what ) {
			case 'dump':			$index = 1;	break;
			case 'preparediff':		$index = 0;	break;
			case 'creatediff':		$index = 1;	break;
			default: die('bad param for addDataFromTable().');
		}
		
		$table_def = Table_Find_Def($table, 0 /*no access check*/);
		$db = new DB_Admin; 
		$dba = new DB_Admin;
		$db->query("SELECT * FROM $table WHERE id=$id;");
		if( $db->next_record() )
		{
			for( $r = 0; $r < sizeof($table_def->rows); $r++ )
			{
				$rowname  = $table_def->rows[$r]->name;
				$rowflags = $table_def->rows[$r]->flags;
				if( !($rowflags&TABLE_READONLY) )
				{
					switch( $rowflags&TABLE_ROW )
					{
						case TABLE_TEXT:
						case TABLE_FLAG:
						case TABLE_INT:
						case TABLE_BITFIELD:
						case TABLE_DATE:
						case TABLE_DATETIME:
						case TABLE_ENUM:
						case TABLE_SATTR:
							$value = $db->fs( $rowname );
							if( ( ($rowflags&TABLE_ROW)==TABLE_SATTR 	&& $value == '0'  )
							 || ( ($rowflags&TABLE_ROW)==TABLE_ENUM  	&& $value == '0'  )
							 || ( ($rowflags&TABLE_ROW)==TABLE_BITFIELD	&& $value == '0'  )
							 || ( ($rowflags&TABLE_ROW)==TABLE_FLAG  	&& $value == '0'  )
							 || ( ($rowflags&TABLE_EMPTYONNULL)      	&& $value == '0'  )
							 || ( ($rowflags&TABLE_EMPTYONMINUSONE)  	&& $value == '-1' )
							 || ( $value == '0000-00-00' )
							 || ( $value == '0000-00-00 00:00:00' )
							 || ( $value == '00:00' )
							 ) {
								$value = '';
							}
							$this->data[ $rownameprefix . $rowname ][ $index ] = $value;
							break;
						
						case TABLE_TEXTAREA:
							$this->data[ $rownameprefix . $rowname ][ $index ] = $db->fs( $rowname );
							if( $what == 'creatediff' ) {
								$this->diff($this->data[ $rownameprefix . $rowname ][ 0 ], $this->data[ $rownameprefix . $rowname ][ 1 ]);
							}
							break;
						
						case TABLE_PASSWORD: // sensible data, protect from logging the real data, log changes only
							if( $what == 'preparediff' ) {
								$this->data[ $rownameprefix . $rowname ][ 0 ] = $db->fs( $rowname );
							}
							else if( $what == 'creatediff' ) {	
								if( $this->data[ $rownameprefix . $rowname ][ 0 ] == $db->fs( $rowname ) ) {
									$this->data[ $rownameprefix . $rowname ][ 0 ] = '';  		// not changed
								}
								else {
									$this->data[ $rownameprefix . $rowname ][ 0 ] = '<old password>';	// changed - make sure, the values just differ
									$this->data[ $rownameprefix . $rowname ][ 1 ] = '<new password>';  
								}
							}
							break;
						
						case TABLE_BLOB:
							$value = '';
							$ob = new G_BLOB_CLASS($db->fs($rowname));
							if( strlen($ob->blob) )
								$value = sprintf('%s, %d x %d, %d Byte', $ob->name, $ob->w, $ob->h, strlen($ob->blob));
							$this->data[ $rownameprefix . $rowname ][ $index ] = $value;
							break;
						
						case TABLE_MATTR:
							$value = '';
							$dba->query("SELECT attr_id FROM " . $table_def->name . '_' .$rowname . " WHERE primary_id=$id ORDER BY structure_pos, attr_id;");
							while( $dba->next_record() ) {
								$value .= ($value==''? '' : ',') . $dba->f('attr_id');
							}
							$this->data[ $rownameprefix . $rowname ][ $index ] = $value;
							break;
						
						case TABLE_SECONDARY:
							$value = '';
							$this->data[ $rowname ][ $index ] = ''; // just to make sure, this comes first
							$dba->query("SELECT secondary_id FROM " . $table_def->name . '_' .$rowname . " WHERE primary_id=$id ORDER BY structure_pos, secondary_id;");
							while( $dba->next_record() )
							{
								$secondary_id	  = $dba->f('secondary_id');
								$secondary_prefix = $rowname.'.'.$secondary_id.'.';
								$value 			  .= ($value==''? '' : ',') . $secondary_id;
								
								if( $what == 'creatediff' ) {
									$do_recurse = false; 	// if the secondary table (durchfuehrung) does /not/ exist in the old values, a dump is not needed
									reset($this->data);			
									while( list($n) = each($this->data) ) {
										if( substr($n, 0, strlen($secondary_prefix)) == $secondary_prefix ) { $do_recurse = true; break; }
									}
								}
								else {
									$do_recurse = true;
								}
								
								if( $do_recurse ) {
									$this->addDataFromTable($table_def->rows[$r]->addparam->name, $secondary_id, $what, $rownameprefix.$secondary_prefix, true /*isSecondary*/);
								}
							}
							$this->data[ $rownameprefix . $rowname ][ $index ] = $value; // needed to track creation and deletion of secondary tables
							break;
					}
				}
			}
		}
		
		if( !$isSecondary )
		{
			$this->data[$rownameprefix . 'user_created'][ $index] = $db->f('user_created'); // user_modified, date_modified is always logged implicit; date_created cannot be changed
			$this->data[$rownameprefix . 'user_grp'][ $index]	  = $db->f('user_grp');
			$this->data[$rownameprefix . 'user_access'][ $index]  = $db->f('user_access');
		}
	}
	
	private function diff(&$str1, &$str2) // create a difference between two strings
	{
		// simplyfy the strings
		$str1 = trim(strtr($str1, "\n\t\r", '   '));
		while( !(strpos($str1, '  ' )===false) ) $str1 = str_replace('  ', ' ' , $str1);
		
		$str2 = trim(strtr($str2, "\n\t\r", '   '));
		while( !(strpos($str2, '  ' )===false) ) $str2 = str_replace('  ', ' ' , $str2);
		
		// remove equal characters at the beginning
		$p = 0;
		while( (substr($str1, 0, $p+1) == substr($str2, 0, $p+1)) && $p<=strlen($str1) ) {
			$p++;
		}
		
		if( $p ) {
			$p -= 32;
			while($p>0 && $str1{$p}!=' ') $p--;
			if( $p > 10 ) {
				$str1 = '...' . substr($str1, $p);
				$str2 = '...' . substr($str2, $p);
			}
		}

		// remove equal characters at the end
		$p = 0;
		$str1len = strlen($str1);
		$str2len = strlen($str2);
		while( (substr($str1, $str1len-$p-1, $p+1) == substr($str2, $str2len-$p-1, $p+1)) && $p<=strlen($str1) ) {
			$p++;
		}
		
		if( $p ) {
			$p -= 32;
			while($p>0 && $str1{$p}!=' ') $p--;
			if( $p > 10 ) {
				$str1 = substr($str1, 0, $str1len-$p) . '...';
				$str2 = substr($str2, 0, $str2len-$p) . '...';
			}
		}
	}

};
