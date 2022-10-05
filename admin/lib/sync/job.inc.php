<?php


class SYNC_JOB_CLASS
{
	public $jobid;		// 0 for errors/unset
	public $descr;		// any description, if unset, we'll use host/table/q
	public $host;
	public $table;
	public $query;
	public $freq;
	public $overwrite;
	public $delete;
	public $defuser;
	public $defgrp;
	public $defaccess;
	public $lasttime;
	public $further_options;
	
	function __construct($jobid)
	{
		$dummy = new IMP_MIXFILE_CLASS; // needed to define IMP_OVERWRITE_* etc. 
		$this->read($jobid);
	}
	
	public function getfinename()
	{
		$ret = '';

		if( isset( $this->host ) && $this->host != '' && $this->table != '' )
		{
			$tableDef = Table_Find_Def($this->table, false /*no access check*/);
			
			$ret .= $this->query==''? 'Alle ' : 'Auswahl ';
			$ret .= (is_object($tableDef)? $tableDef->descr : $table) . ' von ' . $this->host . ' importieren';
		}

		if( isset( $this->descr ) && $this->descr != '' )
		{
			$shortdescr = $this->descr;
			$maxln = 32;
			if( strlen($shortdescr) > $maxln ) {
				$shortdescr = substr($shortdescr, 0, $maxln-4) . '..';
			}
			
			$ret .= ($ret? ', ' : '') . $shortdescr;
		}
		
		if( $ret == '' )
		{
			$ret = 'Unbenannt';
		}
		
		return $ret;
	}
	
	public function getfinequery()
	{
	    if( isset( $this->query ) && $this->query != '' )
		{
			$ret = $this->query;
			$maxln = 32;
			if( strlen($ret) > $maxln ) {
				$ret = substr($ret, 0, $maxln-4) . '..';
			}
			return $ret;
		}
		else
		{
			$tableDef = Table_Find_Def($this->table, false /*no access check*/);
			return 'Alle ' . (is_object($tableDef)? $tableDef->descr : $this->table);
		}
	}
	
	public function read($jobid)
	{
		// set defaults
		$this->jobid			= 0;
		$this->descr			= '';
		$this->host  			= '';
		$this->table 			= '';
		$this->query 			= '';
		$this->freq	 			= 0;
		$this->overwrite		= IMP_OVERWRITE_NEVER;
		$this->delete			= IMP_DELETE_NEVER;
		$this->defuser			= isset( $_SESSION['g_session_userid'] ) ? intval($_SESSION['g_session_userid']) : null;
		$this->defgrp			= acl_get_default_grp();
		$this->defaccess		= acl_get_default_access();
		$this->lasttime			= 0;
		$this->further_options	= '';
					
		// try to read the data
		$jobid  = intval($jobid);
		if( $jobid > 0 )
		{
		
			$data = regGet('sync.'.$jobid, '', 'template');
			if( $data != '' ) 
			{
				$this->jobid = $jobid;
				$data = explode('###', $data);

				$this->descr  			= $this->_decode_str($data[0]);
				$this->host  			= $this->_decode_str($data[1]);
				$this->table 			= $this->_decode_str($data[2]);
				$this->query 			= $this->_decode_str($data[3]);
				$this->freq	 			= intval($data[4]);
				$this->overwrite		= intval($data[5]);
				$this->delete			= intval($data[6]);
				$this->defuser			= intval($data[7])? intval($data[7]) : $this->defuser;
				$this->defgrp			= intval($data[8])? intval($data[8]) : $this->defgrp;
				$this->defaccess		= intval($data[9])? intval($data[9]) : $this->defaccess;
				$this->lasttime			= intval($data[10]);
				$this->further_options	= $this->_decode_str($data[11]);
			}
		}
	}
	
	public function save()
	{
		// create a jobid, if not yet done
	    if( !isset( $this->jobid ) || $this->jobid == 0 )
		{
			$ids = SYNC_JOB_CLASS::_s_get_ids_info();
			$this->jobid = $ids['first_free'];
		}
		
		// save the data
		$data = array();
		$data[0]	= $this->_encode_str($this->descr);
		$data[1]	= $this->_encode_str($this->host);
		$data[2]	= $this->_encode_str($this->table);
		$data[3]	= $this->_encode_str($this->query);
		$data[4]	= intval($this->freq);
		$data[5]	= intval($this->overwrite);
		$data[6]	= intval($this->delete);
		$data[7]	= intval($this->defuser);
		$data[8]	= intval($this->defgrp);
		$data[9]	= intval($this->defaccess);
		$data[10]	= intval($this->lasttime);
		$data[11]	= $this->_encode_str($this->further_options);
		regSet('sync.'.$this->jobid, implode('###', $data), '', 'template');
		regSave();
	}

	public function remove()
	{
		regSet('sync.'.$this->jobid, '', '', 'template');
		regSave();
	}

	// encode / decode strings
	// ------------------------------------------------------------------------
	
	private function _encode_str($str)
	{
		return trim(strtr($str, array('###'=>'#', "\n"=>"<br />", "\r"=>"", "\t"=>" ", "\\"=>'/')));
	}
	private function _decode_str($str)
	{
		return trim(strtr($str, array("<br />"=>"\n")));
	}
	
	// handle ID stuff
	// ------------------------------------------------------------------------
	
	private static function _s_get_ids_info()
	{
		$ret = array();
		$ret['all'] = array();
		$ret['first_free'] = 0;
		
		$testid = 1;
		$max_unused_ids = 256;
		$unused_ids_allowed = $max_unused_ids;
		while( $unused_ids_allowed > 0 ) 
		{
			if( regGet('sync.'.$testid, '', 'template') != '' )
			{
				$ret['all'][] = $testid;
				$unused_ids_allowed = $max_unused_ids;
			}
			else
			{
				if( $ret['first_free'] == 0 )
					$ret['first_free'] = $testid;
				$unused_ids_allowed--;
			}
			$testid++;
		}
		
		if( $ret['first_free'] == 0 )
			$ret['first_free'] = 1;
		
		return $ret;
	}
	
	public static function s_get_all_ids()
	{
		$all = SYNC_JOB_CLASS::_s_get_ids_info();
		return $all['all'];
	}
	
};


