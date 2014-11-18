<?php



$pluginfunc = 'trigger_apikeys';
function trigger_apikeys(&$param)
{
	if( $param['action'] == 'afterinsert' )
	{
		// config
		$keylen    = 32;
		$keychars  = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		
		// create the key
		$key = '';
		for( $i = 0; $i < $keylen; $i++ )
			$key .= substr($keychars, mt_rand(0, strlen($keychars)-1), 1);
		
		// insert the new key
		$db = new DB_Admin;
		$db->query("UPDATE apikeys SET apikey='".$key."' WHERE id=".$param['id']);
		$param['returnreload'] = 1;
	}
	
	return 1;
}


