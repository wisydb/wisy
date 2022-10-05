<?php

class EDIT_PAGING_CLASS
{
	public function search_id($table_name, $id, $dir)
	{
	    if( isset($_SESSION['g_session_list_results'][$table_name]) && is_array($_SESSION['g_session_list_results'][$table_name]) )
		{
			if( ($i=array_search($id, $_SESSION['g_session_list_results'][$table_name])) !== false )
			{
				return intval($_SESSION['g_session_list_results'][$table_name][ $dir=='next'? $i+1 : $i-1 ]); // 0 on array start/end, !=0 on success
			}
		}
		
		return 0; // not found
	}

	public function paginate($table_name, $id, $dir /*'prev' or 'next'*/)
	{
		// first, see if the ID was already loaded in index.php or by a previous call to this function
		$fwd_id = $this->search_id($table_name, $id, $dir);
		
		// secondly, if there is no ID yet, recreate the array
		if( $fwd_id == 0 )
		{
			$_SESSION['g_session_list_results'][$table_name] = array();
			if( isset($_SESSION['g_session_index_sql'][$table_name]) )
			{
				$to_add = PHP_INT_MAX;
				$db = new DB_Admin;
				$db->query($_SESSION['g_session_index_sql'][$table_name]);
				while( $db->next_record() )
				{
					$curr_id = $db->fs('id');
					$_SESSION['g_session_list_results'][$table_name][] = $curr_id;
					if( $curr_id == $id ) {
						$to_add = 50; // add 50 more IDs after the found one
					}
					
					$to_add--;
					if( $to_add <= 0 )
						break; // enough added 
				}
				
				$fwd_id = $this->search_id($table_name, $id, $dir);
			}
			
			return $fwd_id;
		}
		
		// done
		return $fwd_id;
	}
};