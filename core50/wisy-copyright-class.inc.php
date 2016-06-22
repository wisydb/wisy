<?php


class WISY_COPYRIGHT_CLASS
{
	var $framework;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
	}	
	
	function renderCopyright(&$db, $table, $recordId)
	{
		$copyright = '';
	
		// search group id for a special modifier
		$group_settings = array();
		if( $copyright == '' )
		{
			$sql = "SELECT s.settings x, a.user_modified m FROM user_grp s, $table a
					 WHERE a.user_grp=s.id AND a.id=$recordId;";
			$db->query($sql);
			if( $db->next_record() )
			{
				$group_settings = explodeSettings($db->f8('x'));
				$user_modified = intval($db->f8('m'));
				if( $group_settings["copyright.{$table}.modifiedby.{$user_modified}"] != '' )
				{
					$copyright = $group_settings["copyright.{$table}.modifiedby.{$user_modified}"];
				}
			}
		}
	
		// search by stichwort
		if( $copyright == '' && ($table=='kurse' || $table=='anbieter') )
		{
			$sql = "SELECT notizen x FROM stichwoerter s, {$table}_stichwort a
					 WHERE a.attr_id=s.id AND a.primary_id=$recordId AND s.eigenschaften=2048 AND s.notizen LIKE '%copyright.$table%' ORDER BY a.structure_pos;";
			$db->query($sql);
			while( $db->next_record() )
			{
				$test = explodeSettings($db->f8('x'));
				if( $test["copyright.$table"] != '' )
				{
					$copyright = $test["copyright.$table"];
					break;
				}
			}
		}
	
		// search by user id
		if( $copyright == '' )
		{
			$sql = "SELECT s.settings x FROM user s, $table a
					 WHERE a.user_created=s.id AND a.id=$recordId;";
			$db->query($sql);
			if( $db->next_record() )
			{
				$test = explodeSettings($db->f8('x'));
				if( $test["copyright.$table"] != '' )
				{
					$copyright = $test["copyright.$table"];
				}
			}
		}
	
		// search by group id
		if( $copyright == '' )
		{
			if( $group_settings["copyright.$table"] != '' )
			{
				$copyright = $group_settings["copyright.$table"];
			}
		}
	
		// render
		echo '<p id="wisy_copyright_footer">' . ($copyright? $copyright : '&nbsp;') . '</p><!-- /#wisy_copyright_footer -->';
	}
	
	private function unifyDomain($domain)
	{
		return strtr(
			$domain,
			array(
				'www.' => '',
				'.local' => '.info',
			)
		);
	}
	
	function getEditUrl(&$db, $table, $recordId)
	{
		// returns the edit url, including protocol and including trailing slash
		// however, the paramter useredit.url may not contain a slash or a protocol
		$editurl = '';

		// search by user id
		if( $editurl == '' )
		{
			$sql = "SELECT s.settings x FROM user s, $table a
					 WHERE a.user_created=s.id AND a.id=$recordId;";
			$db->query($sql);
			if( $db->next_record() )
			{
				$test = explodeSettings($db->f8('x'));
				if( $test["useredit.url"] != '' )
				{
					$editurl = $test["useredit.url"];
				}
			}
		}
				
		// search by group id
		if( $editurl == '' )
		{
			$sql = "SELECT s.settings x FROM user_grp s, $table a
					 WHERE a.user_grp=s.id AND a.id=$recordId;";
			$db->query($sql);
			if( $db->next_record() )
			{
				$test = explodeSettings($db->f8('x'));
				if( $test["useredit.url"] != '' )
				{
					$editurl = $test["useredit.url"];
				}
			}
		}
		
		if( strpos($editurl, '/') !== false ) {
			return ''; // bad syntax
		}

		if( $this->unifyDomain($editurl) == $this->unifyDomain($_SERVER['HTTP_HOST']) ) {
			return ''; // no external URL
		}

		// done
		if( $editurl == '' ) {
			return '';
		}
		else {
			return 'http://' . $editurl . '/'; // the edit pages will forward to https, if appropriate
		}
		
		
	}
}
