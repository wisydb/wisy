<?php if( !defined('IN_WISY') ) die('!IN_WISY');
/*******************************************************************************
WISY PLZ Filter
********************************************************************************

Test a given PLZ against the portal settings durchf.plz.allow, durchf.plz.deny
and durchf.plz.order

durchf.plz.order values:

- allow,deny (standard): First, if there is an allow-list and if the given PLZ 
  is not in the allow-list, it is rejected.
  Second, if the given PLZ is in the deny-list, it is rejected.
  Other PLZ are permitted.

- deny,allow: If the given PLZ is is the deny-list, it is rejected _unless_
  if is present in the allow-list.
  Other PLZ are permitted.
  
@author Bjoern Petersen

*******************************************************************************/


class WISY_PLZFILTER_CLASS 
{	
	private $framework;
	
	private $plz_allow;
	private $plz_deny;
	private $plz_order;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
	}
		
	private function get_plz_array_($ini_key)
	{
		$ret = array();
			$temp = explode(',', $this->framework->iniRead($ini_key, ''));
			for( $i = 0; $i < sizeof($temp); $i++ ) {
				$plz = trim($temp[$i]);
				if( $plz != '' ) {
					$ret[ $plz ] = 1;
				}
			}
		return $ret;
	}
	
	private function is_plz_in_array_($plz, $arr)
	{
		for( $i = strlen($plz); $i >= 1; $i-- ) {
			if( $arr[ substr($plz, 0, $i) ] )
				return true;
		}
		
		return false;
	}
	
	function is_valid_plz($plz)
	{
		// create PLZ allow/deny lists
		if( !is_array($this->plz_allow) )
		{
			$this->plz_allow = $this->get_plz_array_('durchf.plz.allow');
			$this->plz_deny  = $this->get_plz_array_('durchf.plz.deny');
			$this->plz_order = str_replace(' ', '', $this->framework->iniRead('durchf.plz.order', 'allow,deny')); // durchf.plz.order ist akt. (21:57 16.01.2013) nicht dokumentiert und inoffiziell!
		}
		
		// correct the PLZ given
		$plz = trim($plz);
		if( $plz == '' ) {
			$plz = 'empty';
		}
		
		// check, if a PLU is denied or allowed by default; the latter is the standard setting
		if( $this->plz_order == 'deny,allow' )
		{
			// deny,allow
			if( sizeof($this->plz_deny ) ) { 
				if( $this->is_plz_in_array_($plz, $this->plz_deny ) ) { 
					if( sizeof($this->plz_allow) == 0 || !$this->is_plz_in_array_($plz, $this->plz_allow) ) { 
						return false; 
					}
				} 
			}
		}
		else
		{
			// allow,deny - standard behaviour
			if( sizeof($this->plz_allow) ) { 
				if( !$this->is_plz_in_array_($plz, $this->plz_allow) ) { 
					return false; 
				}
			}
			
			if( sizeof($this->plz_deny ) ) { 
				if(  $this->is_plz_in_array_($plz, $this->plz_deny ) ) {
					return false;
				}
			}
		}
		
		return true;
	}
};


