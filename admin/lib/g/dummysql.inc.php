<?php
/*=============================================================================
a dummy SQL class
===============================================================================

This class can be used as an replacement for SQLite or MySQL classes, eg.
for testing purposes without real writing data.

=============================================================================*/


class G_DUMMYSQL_CLASS
{
	public function query($sql)
	{
	}
	
	public function next_record()
	{
		return false; // returning true would result in an endless loop
	}
	
	public function fs($name)
	{
		return '';
	}
	
	public function insert_id()
	{
		return 666; // assume an insert was successful, perfect for dummy record creation in EDIT_DATA_CLASS::save_record_()
	}
	
	public function quote($str)
	{
		return "'".addslashes($str)."'";
	}
	
	public function column_exists($name)
	{
		return false; // columns doen not exist by default, perfect eg. in EDIT_DATA_CLASS::save_record_() as this saves is to create EQL stuff
	}
	
	// add other functions as needed, however, for things as table_exists(), it 
	// will be difficult to define a default behaviour.
};