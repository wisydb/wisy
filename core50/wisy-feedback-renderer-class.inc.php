<?php



class WISY_FEEDBACK_RENDERER_CLASS
{
	var $framework;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
	}
	
	function anonymize_ip1($ip)
	{
		// function replaces the first octet of an IP-Adress by null
		// (this is more unique than replacing the last one, see _TODO-2013.txt)
		$p = strpos($ip, '.');
		if( $p !== false )
			$ip = '0' . substr($ip, $p);
		return $ip;
	}
		
	function render()
	{
		// die Ausgabe dieser Funktion dient nur debugging-zwecken;
		// wichtig sind allein die Parameter, die in der Datenbank in der Tabelle "Feedback" abgelegt werden.

		headerDoCache(0);
		
		$url    = $_GET['url'];
		$ip		= $this->anonymize_ip1($_SERVER['REMOTE_ADDR']);
		$rating = intval($_GET['rating']); if( $rating != 0 && $rating != 1 ) { echo 'BAD RATING'; return; }
		$descr  = trim(utf8_decode($_GET['descr']));
		
		// connect to db
		$today = strftime("%Y-%m-%d %H:%M:%S");
		$threeHours = strftime("%Y-%m-%d %H:%M:%S", time()+3*60*60);
		
		$db = new DB_Admin;
		$db->query("SELECT id FROM feedback WHERE url='".addslashes($url)."' AND ip='".addslashes($ip)."' AND date_created<'$threeHours';");
		if( $db->next_record() )
		{
			// modify an existing record (only adding a description is allowed)
			$id = intval($db->f('id'));
			if( $descr != '' )
			{
				$db->query("UPDATE feedback SET descr='".addslashes($descr)."' WHERE id=$id;");
				echo 'DESCR ADDED';
				return;
			}
			else
			{
				echo 'FEEDBACK EXISTS';
				return;
			}
		}

		$db->query("SELECT user_grp FROM portale WHERE id=".$GLOBALS['wisyPortalId']);
		$db->next_record();
		$user_grp = intval($db->f('user_grp'));

		$user_access = 511; // =0777, was: 508=0774, however, this would require supervisor settings to modify the feedback settings
		$db->query("INSERT INTO feedback (url, ip, rating, descr, date_created, date_modified, user_grp, user_access) 
					VALUES ('".addslashes($url)."', '".addslashes($ip)."', $rating, '".addslashes($descr)."', '$today', '$today', $user_grp, $user_access)");

		echo 'FEEDBACK ADDED';
	}
};


