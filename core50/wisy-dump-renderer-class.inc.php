<?php if( !defined('IN_WISY') ) die('!IN_WISY');



class WISY_DUMP_RENDERER_CLASS
{
	var $framework;
	var $param;

	function __construct(&$framework, $param)
	{
		// constructor
		$this->framework =& $framework;
		$this->param = $param;
	}
	
	function render()
	{
		global $wisyPortalId;
	
		$db = new DB_Admin;
	
		if( $this->param['src'] == 'portal.css' )
		{
			$sql = "SELECT css FROM portale WHERE id=$wisyPortalId;";
			$db->query($sql);
			if( $db->next_record() )
			{
				$css = $db->fcs8('css');
				header("Content-type: text/css");
				header("Content-length: " . strlen($css));
				headerDoCache();
				echo $css;			
			}
			$db->free($sql);
		}
	}
}



