<?php



/*=============================================================================
(X)ACL Functions
===============================================================================

file:	
	acl.inc.php
	
author:	
	Bjoern Petersen

parameters:
	none, only function definitions in this file

=============================================================================*/




//
// defines for the access bits, can be combined using the logical OR-Operator,
// these are NOT the same bits as used in the database in the field 
// 'user_access' as 'rwx rwx rwx'
//
define('ACL_NEW',		0x01);
define('ACL_READ',		0x02);
define('ACL_EDIT',		0x04);
define('ACL_DELETE',	0x08);
define('ACL_REF',		0x10);




// 
// connect to database if not yet done
//
function acl_init_db()
{
	global $g_acl_db;
	if( !is_object($g_acl_db) ) {
		$g_acl_db = new DB_Admin;
		$g_acl_db->Halt_On_Error = 'no';
	}
}



//
// function returns the rights for a given object. the object path is sth. like
//	 main				- any access to the table 'main'?
//   main.regioncode	- access to the field 'regioncode' in table 'main'
//	 keywords.RIGHTS	- access to the access fields in table 'keywords'
//   main.COMMON		- access any field in table 'main'
//   SYSTEM.LOCALIZELANG- access to localize languages
//
// the instance is the ID of the record.
//
// returns the access bits as defined above.
//
// BTW: an access grant is defined as followed:
//   <path> [<path>[...]]: <grants>
// with <grants> as:
//   [r][c][m][d]
// eg.
//   crm
//
function acl_get_access(	$object_path, 
							$instance_id = -1, 	// no instance
							$user = 0,			// current user
							$useUserFilter = 1
					   )
{
	//
	// check hash
	//
	global $g_acl_get_hash;
	global $g_acl_get_rights;

	if( $g_acl_get_hash == "$object_path:$instance_id:$user" ) {
		return $g_acl_get_rights;
	}

	$g_acl_get_hash = "$object_path:$instance_id:$user";

	//
	// explode object path
	//
	$object_path = strtr($object_path, "\n\r\t,;:", '      ');
	$object_path = str_replace(' ', '', $object_path);
	$object_path = explode('.', $object_path);
	if( sizeof((array) $object_path) != 2 ) {
		$g_acl_get_rights = 0;
		return 0; // no access
	}

	//
	// get database object
	//
	global $g_acl_db;
	acl_init_db();

	//
	// get correct user
	//
	if( $user <= 0 ) {
		$user = $_SESSION['g_session_userid'];
	}
	$user = intval($user);

	//
	// get user access information...
	//
	global $g_acl_user_access;
	global $g_acl_user_groups;
	global $g_acl_user_id;
	if( $g_acl_user_id != $user || !is_array($g_acl_user_access) ) 
	{
		//
		// ...common access
		//
		$g_acl_user_access = array();
		
		$g_acl_db->query("SELECT access FROM user WHERE id=$user");
		if( !$g_acl_db->next_record() ) {
			$g_acl_get_rights = 0;
			return 0; // no access
		}
		
		$tempi = $g_acl_db->f('access');
		$tempi = strtr($tempi, array("\n"=>";", "\r"=>"", "\t"=>"", " "=>""));
		
		$tempi = explode(";", $tempi);
		for( $i = 0; $i < sizeof((array) $tempi); $i++ ) {
			if( $tempi[$i] ) {
				list($tempp, $tempr) = explode(':', $tempi[$i]);
				
				$tempa = 0;
				$tempr = ' '.strtolower($tempr);
				if( strpos($tempr, 'n') ) { $tempa |= ACL_READ|ACL_NEW; }
				if( strpos($tempr, 'r') ) { $tempa |= ACL_READ; }
				if( strpos($tempr, 'w') ) { $tempa |= ACL_READ|ACL_EDIT; }
				if( strpos($tempr, 'd') ) { $tempa |= ACL_READ|ACL_DELETE; }
				
				$g_acl_user_access[$tempp] = $tempa;
			}
		}
		
		//
		// ...groups
		//
		
		$g_acl_user_groups = array();
		$g_acl_db->query("SELECT attr_id FROM user_attr_grp WHERE primary_id=$user ORDER BY structure_pos");
		while( $g_acl_db->next_record() ) {
			$g_acl_user_groups[] = $g_acl_db->f('attr_id');
		}
		
		//
		// ...hash user
		//
		$g_acl_user_id = $user;
	}
	
	//
	// check user access information...
	// has the user access to the required fields at all?
	//
	$object_rights = 0;
	if( $object_path[0] == 'COMMON' )
	{
		echo '<h1>acl_get_access: COMMON.* is not allowed!</h1>';
		exit();
	}
	else if( $object_path[1] == 'COMMON' ) 
	{
		//
		// ...check for common access to a "table.COMMON" by boolean OR all rights.
		// we also check "*.*" if nothing was found.
		//
		if( isset($g_acl_user_access[$object_path[0].'.*']) ) 
		{
			$object_rights = $g_acl_user_access[$object_path[0].'.*'];
		}
		else if( isset($g_acl_user_access['*.*']) ) 
		{
			$object_rights = $g_acl_user_access['*.*'];
		}

		reset($g_acl_user_access);
		foreach($g_acl_user_access as $k => $v)
		{
			$k = explode('.', $k);
			if( $object_path[0] == $k[0] ) {
				$object_rights |= ($v&(ACL_READ|ACL_EDIT));
			}
		}
	}
	else 
	{
		//
		// check for specific access to "table.field".
		// we also check "table.*", "*.field" and "*.*" (in this order).
		//
		if( isset($g_acl_user_access[$object_path[0].'.'.$object_path[1]]) ) 
		{
			$object_rights = $g_acl_user_access[$object_path[0].'.'.$object_path[1]];
		}
		else if( isset($g_acl_user_access[$object_path[0].'.*']) )
		{
			if( isset($g_acl_user_access['*.'.$object_path[1]]) ) {
				$object_rights = $g_acl_user_access[$object_path[0].'.*'] & $g_acl_user_access['*.'.$object_path[1]];
			}
			else {
				$object_rights = $g_acl_user_access[$object_path[0].'.*'];
			}
		}
		else if( isset($g_acl_user_access['*.'.$object_path[1]]) )
		{
			$object_rights = $g_acl_user_access['*.'.$object_path[1]];
		}
		else if( isset($g_acl_user_access['*.*']) ) 
		{
			$object_rights = $g_acl_user_access['*.*'];
		}
	}
	
	//
	// adapt special rights
	//
	if( $object_path[0] == 'SYSTEM' )
	{
		switch( $object_path[1] ) {
			case 'EXPORT';
				$object_rights = $object_rights & ACL_READ; 
				break;
			
			case 'IMPORT':
				$object_rights = $object_rights & ACL_EDIT;
				break;
		}
	}
	else if( $object_path[1] != 'COMMON' 
		  || $object_path[0] == 'SUPERVISOR' )
	{
		$object_rights = $object_rights & (ACL_READ|ACL_EDIT);
	}
	else if( $object_rights & ACL_READ ) 
	{
		$object_rights |= ACL_REF;
	}
	
	
	//
	// if the user has no access to the required object at all, it won't
	// get better by the instance check. so we can stop here now.
	//
	if( $object_rights == 0 ) {
		$g_acl_get_rights = 0;
		return 0;
	}

	//
	// if no instance is given, we can use the object rights
	// 
	$instance_id = intval($instance_id);
	if( $instance_id <= 0 ) {
		$g_acl_get_rights = $object_rights;
		return $object_rights;
	}

	//
	// check object rights against the instance rights...
	//
	global $g_acl_instance_hash;
	global $g_acl_instance_user;
	global $g_acl_instance_grp;
	global $g_acl_instance_rights;
	global $g_acl_instance_supervisor;
	if( $g_acl_instance_hash != "$object_path[0]:$instance_id:$user" ) 
	{
		//
		// get filter - should be before the query as the function uses $g_acl_db
		//
		if( $useUserFilter ) {
			acl_get_grp_filter($filteredgroups, $filterpositive, $user);
		}
		else {
			$filteredgroups = array();
			$filterpositive = 0;
		}

		//
		// load data
		//
		$g_acl_db->query("SELECT user_created,user_grp,user_access FROM $object_path[0] WHERE id=$instance_id");
		if( !$g_acl_db->next_record() ) {
			$g_acl_get_rights = 0;
			return 0; // record not found - no access
		}

		//
		// get group, check filter
		//
		$g_acl_instance_grp = intval($g_acl_db->f('user_grp'));
		
		if( ($filterpositive? !in_array($g_acl_instance_grp, $filteredgroups) : in_array($g_acl_instance_grp, $filteredgroups)) ) {
			$g_acl_get_rights = 0;
			return 0; // record filtered - no access
		}

		//
		// check if the user is a supervisor of this group
		//
		$g_acl_instance_supervisor = 0;
		if( isset($g_acl_user_access["SUPERVISOR.$g_acl_instance_grp"]) ) {
			$g_acl_instance_supervisor = $g_acl_user_access["SUPERVISOR.$g_acl_instance_grp"];
		}
		else if( isset($g_acl_user_access['SUPERVISOR.*']) ) {
			$g_acl_instance_supervisor = $g_acl_user_access['SUPERVISOR.*'];
		}
		else if( isset($g_acl_user_access['*.*']) ) {
			$g_acl_instance_supervisor = $g_acl_user_access['*.*'];
		}
		$g_acl_instance_supervisor = $g_acl_instance_supervisor & ACL_EDIT;

		//
		// get rights by permissions
		//
		$g_acl_instance_rights = 0;
		$g_acl_instance_user = intval($g_acl_db->f('user_created'));
		$tempa = intval($g_acl_db->f('user_access'));

		if( $g_acl_instance_supervisor ) 
		{
			$tempa = $tempa&0111? 01 : 00;
			$tempa |= 06; // supervisors may read and edit but not always reference the record
		}
		else if( $user == $g_acl_instance_user ) 
		{
			$tempa = ($tempa>>6)&07;
		}
		else if( in_array($g_acl_instance_grp, $g_acl_user_groups) ) 
		{
			$tempa = ($tempa>>3)&07;
		}
		else 
		{
			$tempa &= 07;
		}

		if( $tempa & 4 ) { $g_acl_instance_rights |= ACL_READ; }
		if( $tempa & 2 ) { $g_acl_instance_rights |= (ACL_READ|ACL_EDIT|ACL_DELETE); }
		if( $tempa & 1 ) { $g_acl_instance_rights |= (ACL_READ|ACL_REF); }

		//
		// avoid deletion of special records: table=user, loginname=template
		//
		if( $object_path[0] == 'user' ) {
			$g_acl_db->query("SELECT loginname FROM $object_path[0] WHERE id=$instance_id");
			$g_acl_db->next_record();
			if( $g_acl_db->f('loginname') == 'template' ) {
				$g_acl_instance_rights &= ~ACL_DELETE;
			}
		}

		//
		// ...hash instance information
		//
		$g_acl_instance_hash = "$object_path[0]:$instance_id:$user";
	}

	if( $object_path[1] == 'RIGHTS' )
	{
		$object_rights = ACL_READ;
		if( $user == $g_acl_instance_user || $g_acl_instance_supervisor ) {
			$object_rights |= ACL_EDIT;
		}
	}

	$g_acl_get_rights = $object_rights & $g_acl_instance_rights;
	return $g_acl_get_rights;
}



//
// clear the ACL cache, call this function any time the rights
// have been changed for a user
//
function acl_clear_cache()
{
	global $g_acl_get_hash;
	global $g_acl_user_id;
	global $g_acl_instance_hash;
	
	$g_acl_get_hash = "";
	$g_acl_user_id = 0;
	$g_acl_instance_hash = "";
}



//
// check for specific bits
//
function acl_check_access(	$object_path,
							$instance_id = -1,	// no instance
							$rights = ACL_READ,	// check read access
							$user = 0,			// current user
							$useUserFilter = 1
						 )
{
	return (acl_get_access($object_path, $instance_id, $user, $useUserFilter) & $rights)? 1 : 0;
}



//
// get a readable access string
//
function acl_get_readable_str($rights)
{
	$ret = '';
	
	if( $rights & ACL_READ ) {
		$ret .= ($ret?', ':'') . htmlconstant('_READ');
	}
	
	if( $rights & ACL_EDIT ) {
		$ret .= ($ret?', ':'') . htmlconstant('_EDIT');
	}
	
	if( $rights & ACL_NEW ) {
		$ret .= ($ret?', ':'') . htmlconstant('_NEW');
	}
	
	if( $rights & ACL_DELETE ) {
		$ret .= ($ret?', ':'') . htmlconstant('_DELETE');
	}
	
	if( $rights & ACL_REF ) {
		$ret .= ($ret?', ':'') . htmlconstant('_REFABBR');
	}
	
	return $ret? $ret : htmlconstant('_NOACCESS');
}



//
// get default rights (rwx rwx r--) / group 
//
function acl_get_default_access($table = '', $user = 0)
{
	return 508; // rwx rwx r--
}

function acl_get_default_grp($table = '', $user = 0)
{
	global $g_acl_user_groups;
	acl_get_access("SYSTEM.COMMON", -1, $user); // just check anything, so $g_acl_user_groups is set
	return (is_array($g_acl_user_groups) && sizeof((array) $g_acl_user_groups)) ? $g_acl_user_groups[0] : 0;
}



//
// get an array with all group IDs the given user has access to
//
function acl_get_all_groups($user = 0)
{
	// connect to database
	global $g_acl_db;
	acl_init_db();

	// get correct user
	if( $user <= 0 ) {
		$user = $_SESSION['g_session_userid'];
	}
	$user = intval($user);

	// get all groups
	$ids = array();
	$g_acl_db->query("SELECT id, shortname FROM user_grp ORDER BY shortname");
	while( $g_acl_db->next_record() ) {
		$ids[] = array($g_acl_db->f('id'), $g_acl_db->fs('shortname'));
	}

	// get all groups the user has access to
	$groups = array();
	for( $i = 0; $i < sizeof((array) $ids); $i++ )
	{
		$g_acl_db->query("SELECT attr_id FROM user_attr_grp WHERE primary_id=$user AND attr_id={$ids[$i][0]}");
		if( $g_acl_db->next_record() 
		 || acl_get_access("SUPERVISOR.{$ids[$i][0]}") )
		{
			$groups[] = $ids[$i];
		}
	}
	
	return $groups;
}



//
// function returns the groups that should be hidden (&$filterpositive==0),
// or the groups that should be shown (&$filterpositive==1)
//
function acl_get_grp_filter(&$filteredgroups, &$filterpositive, $user = 0)
{
	global $g_acl_hash_grp_filteruser;
	global $g_acl_hash_grp_filteredgroups;
	global $g_acl_hash_grp_filterpositive;
	
	// get correct user
	if( $user <= 0 ) {
		$user = $_SESSION['g_session_userid'];
	}
	$user = intval($user);
	
	// use hash?
	if( $user == $g_acl_hash_grp_filteruser ) {
		$filteredgroups	= $g_acl_hash_grp_filteredgroups;
		$filterpositive	= $g_acl_hash_grp_filterpositive;
		return;
	}
	
	// get the settings for the current user
	if( $user == $_SESSION['g_session_userid'] ) {
		$filteredgroups = regGet('filter.grp', '');
	}
	else {
		$filteredgroups = '';
	}
	
	// init filter
	$filteredgroups = str_replace(' ', '', $filteredgroups);
	if( $filteredgroups != '' ) {
		$filteredgroups = explode(',', $filteredgroups);
	}
	else {
		$filteredgroups = array();
	}
	
	// add "other" to filter
	if( in_array(999999, $filteredgroups) ) 
	{
		$positivegroups = array();
		$allgroups = acl_get_all_groups($user);
		$allgroups[] = array(0, '');
		for( $i = 0; $i < sizeof((array) $allgroups); $i++ ) {
			if( !in_array($allgroups[$i][0], $filteredgroups) ) {
				$positivegroups[] = $allgroups[$i][0];
			}
		}

		$filteredgroups = $positivegroups;
		$filterpositive = 1;
	}
	else 
	{
		$filterpositive = 0;
	}
	
	// hash filter
	$g_acl_hash_grp_filteruser		= $user;
	$g_acl_hash_grp_filteredgroups	= $filteredgroups;
	$g_acl_hash_grp_filterpositive	= $filterpositive;
}

function acl_grp_filter_active()
{
	if( regGet('filter.grp', '')!='' )
		return 1;
	else 
		return 0;
}


//
// get an SQL string matching the users rights
//
function acl_get_sql(	$test = ACL_READ, 
						$user = 0, // current user
						$useUserFilter = 1,
						$table = ''	)
{
	global $g_acl_db;

	//
	// currently, we can only check ACL_READ and ACL_REF
	//
	if( $test!=ACL_READ && $test!=ACL_REF ) {
		echo '<h1>ERROR: acl_get_sql(): only ACL_READ and ACL_REF tests are allowed!</h1>';
		exit();
	}

	//
	// add table to fields?
	//
	if( $table ) {
		$table .= '.';
	}

	//
	// get correct user
	//
	if( $user <= 0 ) {
		$user = $_SESSION['g_session_userid'];
	}
	$user = intval($user);
	
	//
	// get the filter
	//
	$filter = '';
	
	if( $useUserFilter ) {
		acl_get_grp_filter($filteredgroups, $filterpositive, $user);
	}
	else {
		$filteredgroups = array();
		$filterpositive = 0;
	}
	
	for( $i = 0; $i < sizeof((array) $filteredgroups); $i++ ) {
		$filter .= $filter? ', ' : '';
		$filter .= $filteredgroups[$i];
	}
	
	if( $filterpositive ) {
		if( $filter != '' ) {	
			$filter = strpos($filter, ',')? "{$table}user_grp IN ($filter)" : "{$table}user_grp=$filter";
		}
		else {
			return '(0)'; // everything filtered, return '(0)' to avoid a false return value
		}
	}
	else {
		if( $filter != '' ) {	
			$filter = strpos($filter, ',')? "NOT({$table}user_grp IN ($filter))" : "{$table}user_grp!=$filter";
		}
	}
	
	//
	// get the access
	//
	if( acl_get_access('SUPERVISOR.*') )
	{
		//
		// user has supervisor access
		//
		$access = $test==ACL_READ? '' : "{$table}user_access&73"; // referencable at all? (73=0111)
	}
	else
	{
		//
		// add world access to SQL
		//
		$access = "{$table}user_access&" . ($test==ACL_READ? 4 : 1);
		
		//
		// is the user a group supervisor?
		//
		$ids = array();
		$g_acl_db->query("SELECT id FROM user_grp");
		while( $g_acl_db->next_record() ) {
			$ids[] = $g_acl_db->f('id');
		}
	
		for( $i = 0; $i < sizeof((array) $ids); $i++ ) 
		{
			if( ($filterpositive? in_array($ids[$i], $filteredgroups) : !in_array($ids[$i], $filteredgroups))
			 && acl_get_access("SUPERVISOR.{$ids[$i]}") )
			{
				if( $test == ACL_READ ) {
					if( !$filterpositive ) {
						$access .= " OR {$table}user_grp={$ids[$i]}";
					}
				}
				else {
					$access .= " OR ({$table}user_grp={$ids[$i]} AND {$table}user_access&73)";
				}
			}
		}
		
		//
		// add group access to SQL
		//
		$g_acl_db->query("SELECT attr_id FROM user_attr_grp WHERE primary_id=$user");
		while( $g_acl_db->next_record() ) 
		{
			$user_grp = $g_acl_db->f('attr_id');
			if( ($filterpositive? in_array($user_grp, $filteredgroups) : !in_array($user_grp, $filteredgroups)) ) {
				$access .= " OR ({$table}user_grp=$user_grp AND {$table}user_access&" .($test==ACL_READ? 32 : 8). ')';
			}
		}
		
		//
		// add user access to SQL
		//
		if( $test == ACL_READ ) {
			$access .= " OR {$table}user_created=$user";
		}
		else {
			$access .= " OR ({$table}user_created=$user AND {$table}user_access&64)";
		}
	}
	
	//
	// combine filter and access
	// 
	if( $filter && $access ) 
	{
		return "$filter AND ($access)";
	}
	else if( $filter ) 
	{
		return $filter;
	}
	else 
	{
		return $access;
	}
}


