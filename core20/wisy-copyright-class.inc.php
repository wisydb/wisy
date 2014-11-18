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
	
		// search by stichwort
		if( $copyright == '' && ($table=='kurse' || $table=='anbieter') )
		{
			$sql = "SELECT notizen x FROM stichwoerter s, {$table}_stichwort a
					 WHERE a.attr_id=s.id AND a.primary_id=$recordId AND s.eigenschaften=2048 AND s.notizen LIKE '%copyright.$table%' ORDER BY a.structure_pos;";
			$db->query($sql);
			while( $db->next_record() )
			{
				$test = explodeSettings($db->fs('x'));
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
				$test = explodeSettings($db->fs('x'));
				if( $test["copyright.$table"] != '' )
				{
					$copyright = $test["copyright.$table"];
				}
			}
		}
	
		// search by group id
		if( $copyright == '' )
		{
			$sql = "SELECT s.settings x FROM user_grp s, $table a
					 WHERE a.user_grp=s.id AND a.id=$recordId;";
			$db->query($sql);
			if( $db->next_record() )
			{
				$test = explodeSettings($db->fs('x'));
				if( $test["copyright.$table"] != '' )
				{
					$copyright = $test["copyright.$table"];
				}
			}
		}
	
		// render
		if( $copyright != '' )
		{
			echo '<p id="wisy_copyright_footer">' . $copyright . '</p>';
		}
	}
}
