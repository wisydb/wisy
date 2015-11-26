<?php if( !defined('IN_WISY') ) die('!IN_WISY');


/*******************************************************************************

Search for a (Durchfuehrungs)-Nr, return offer ID(s)

*******************************************************************************/


class WISY_SEARCH_NR_CLASS
{
	private $framework;
	private $db;
	
	function __construct(&$framework, $param)
	{
		$this->framework	=& $framework;
		$this->db			= new DB_Admin;
	}
	
	public function nr2id($nr)
	{	
		$nr = trim($nr);
	
		$sql = "SELECT DISTINCT k.id" // DISTINCT is needed as there may be offers with double nr in different durchfuehrungen  
			  . " FROM kurse k 
			 LEFT JOIN kurse_durchfuehrung s ON k.id=s.primary_id 
			 LEFT JOIN durchfuehrung d ON s.secondary_id=d.id 
			     WHERE d.nr=".$this->db->quote($nr);
	
		$editAnbieterId = $this->framework->getEditAnbieterId();
		if( $editAnbieterId > 0 ) {
			$sql .= ' AND k.anbieter='.intval($editAnbieterId);
		}
		
		$ret = array();
		$this->db->query($sql);
		while( $this->db->next_record() ) 
		{
			$ret[] = $this->db->f('id');
		}

		return $ret;
	}
};
