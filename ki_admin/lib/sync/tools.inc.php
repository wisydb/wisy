<?php

/*=============================================================================
Sync Tools
===============================================================================

Author:
	Bjoern Petersen

===============================================================================
Diese Klasse kuemmert sich um die Vergabe eindeutiger IDs verschiedener 
Instanzen des Programms.
Hierzu werden bei Bedarf Trigger fuer alle Tabellen der Datenbank angelegt;
diese Trigger bestimmen die neuen IDs dann nach einem in $g_sync_data 
festgelegzten Algorithmus.
Bei einer neuen Installation kann es daher notwendig sein, $g_sync_data
auf die neuenn Domains und nDatenbank anzupassen bzw. diese hinzuzufuegen.
=============================================================================*/



class SYNC_TOOLS_CLASS
{
	private $trigger_version;
	private $db;

	
	
	function __construct()
	{
		$this->trigger_version	= 9;
		$this->db 				= new DB_Admin();
	}

	
	
	function get_sync_info()
	{
		global $g_sync_data;
		if( !is_array($g_sync_data) ) {
			return false; // no synchronisation wanted
		}

		// find a record matching the exact database name
		for( $i = 0; $i < sizeof((array) $g_sync_data); $i++ ) {
			$dbs = explode(',', str_replace(' ','',$g_sync_data[$i]['dbs']));
			if( in_array($this->db->Database, $dbs) ) {
				return $g_sync_data[$i];
			}
		}
		
		// use the default record
		for( $i = 0; $i < sizeof((array) $g_sync_data); $i++ ) {
			if( $g_sync_data[$i]['dbs']=='*' ) {
				return $g_sync_data[$i];
			}
		}
		
		// no default record but sync enabled? this is a fatal error!
		die('Fatal sync error: Default record missing.');
	}
	
	
	function get_sync_src()
	{
		$syncinfo = $this->get_sync_info();
		if( $syncinfo === false )
			return 0;
		return $syncinfo['offset'];
	}
	
	
	private function get_auto_increment_value($table_name)
	{
		$this->db->query("SHOW TABLE STATUS LIKE '$table_name';");
		if( !$this->db->next_record() ) die("Fatal sync error: Cannot get table status for $table_name");
		$auto_increment = intval($this->db->f('Auto_increment'));
		if( $auto_increment <= 0 ) die("Fatal sync error: Bad Auto_increment row for $table_name");
		return $auto_increment;
	}



	private function get_default_value($table_name)
	{
		$this->db->query("SHOW COLUMNS FROM $table_name LIKE 'sync_src';");
		if( !$this->db->next_record() || $this->db->f('Field')!='sync_src' ) die("Fatal sync error: Column sync_src missing for $table_name");
		if( $this->db->f('Default') == '' /*NULL*/ ) return -1;
		return intval($this->db->f('Default'));
	}
	
	
	
	private function get_triggers()
	{
		$all_triggers = array();
		$this->db->query("SHOW TRIGGERS;");
		while( $this->db->next_record() )
		{
			if( $this->db->f('Timing')=='BEFORE' && $this->db->f('Event')=='INSERT' )
			{
				$all_triggers[ $this->db->f('Table') ] = $this->db->f('Trigger');
			}
		}
		return $all_triggers;
	}
	
	

	function validate_sync_values(&$msg, &$msgtype)
	{
		$msg = '';
		$msgtype = 'i';

		if( ($sync_info=$this->get_sync_info())===false ) {
			return; // no error, sync is not wanted
		}
	
		if( $sync_info['dbs'] == '*' ) {
			$msg .= "TABLE_SYNCABLE warning: Unique algorithm missing for ID calculation in database '{$this->db->Database}'. <b>You won't be able to synchonize your records with other installations!</b> Please contact administrator and set up the table sync_src properly.\n";
			$msgtype = $msgtype=='e'? 'e' : 'w';
		}
	
		
		// go through all tables and set the triggers and the default and start values
		$all_triggers = $this->get_triggers();
		global $Table_Def;
		for( $t = 0; $t < sizeof((array) $Table_Def); $t++ )
		{
			$table_name		= $Table_Def[$t]->name;
			$trigger_name	= "{$table_name}_bi_v{$this->trigger_version}_".$sync_info['inc'].'_'.$sync_info['offset']; //  naming scheme is <table>_bi_v<version>_<inc>_<offset>
			
			// set / update trigger
			if( $all_triggers[ $table_name ] != $trigger_name )
			{
				if( $all_triggers[ $table_name ] )
					$this->db->query("DROP TRIGGER IF EXISTS " .$all_triggers[ $table_name ]. ";");
				
				$this->db->query("CREATE TRIGGER $trigger_name BEFORE INSERT ON $table_name FOR EACH ROW
								  BEGIN
									SET auto_increment_increment = ".$sync_info['inc'].";
									SET auto_increment_offset = ".$sync_info['offset'].";
								  END;");
				
				// validate trigger
				$all_triggers = $this->get_triggers();
				if( $all_triggers[ $table_name ] != $trigger_name )
					die("Fatal sync error: Trigger for table {$table_name} could not be created or updated to version {$this->trigger_version}");
				
				$msg .= "TABLE_SYNCABLE information: Trigger for table {$table_name} created/updated to version {$this->trigger_version}.\n";
			}
			
			// set default value
			$default_value = $this->get_default_value($table_name);
			if( $default_value != $sync_info['offset'] ) 
			{
				$this->db->query("ALTER TABLE $table_name ALTER sync_src SET DEFAULT ".$sync_info['offset']);
				$default_value = $this->get_default_value($table_name);
				if( $default_value != $sync_info['offset'] )  {
					die("Fatal sync error: Cannot set default value for $table_name to $default_value");
				}
				
				$msg .= "TABLE_SYNCABLE information: table $table_name: default updated to $default_value\n";
			}

			// set start value
			$auto_increment_value = $this->get_auto_increment_value($table_name);
			if( $auto_increment_value < $sync_info['start'] )
			{
				$this->db->query("ALTER TABLE $table_name AUTO_INCREMENT =".$sync_info['start']);
				$auto_increment_value = $this->get_auto_increment_value($table_name);
				if( $auto_increment_value < $sync_info['start'] ){
					die("Fatal sync error: Cannot set auto increment value for $table_name to $auto_increment_value");
				}
				$msg .= "TABLE_SYNCABLE information: table $table_name: auto increment value updated to $auto_increment_value\n";
			}
		}
	}
};

