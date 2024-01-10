<?php

$pluginfunc = 'trigger_durchfuehrung';

function trigger_durchfuehrung(&$param)
{
    $kurseID = isset($param['primary_id']) ? $param['primary_id'] : 0;    
    
    if( $kurseID <= 0 )
        return false;
    
    if( $param['action'] == 'afterinsert' )
    {
        $db = new DB_Admin;
        
        $timestamp = date( 'Y-m-d H:i:s' );
        
        $sql  = 'UPDATE kurse SET x_df_lastinserted = "' . $timestamp . '" WHERE kurse.id = ' . $kurseID . ";";
        // $sql .= 'UPDATE kurse SET x_df_lastmodified = "' . $timestamp . '" WHERE kurse.id = ' . $kurseID;
        $db->query( $sql );
        
        $sql = 'UPDATE kurse SET x_df_lastinserted_origin = "' . $param['origin'] . '" WHERE kurse.id = ' . $kurseID . ";";
        
        $db->query( $sql );
        
    } else if( $param['action'] == 'afterupdate' )
    {
        $db = new DB_Admin;
        
        $timestamp = date( 'Y-m-d H:i:s' );
        
        $sql = 'UPDATE kurse SET x_df_lastmodified = "' . $timestamp . '" WHERE kurse.id = ' . $kurseID;
        $db->query( $sql );
        
        $sql = 'UPDATE kurse SET x_df_lastmodified_origin = "' . $param['origin'] . '" WHERE kurse.id = ' . $kurseID . ";";
        
        $db->query( $sql );
    }
    else if( $param['action'] == 'afterdelete' )
	{	    
	    $db = new DB_Admin;
	    
	    $timestamp = date( 'Y-m-d H:i:s' );
	    
	    $sql = 'UPDATE kurse SET x_df_lastdeleted = "' . $timestamp . '" WHERE kurse.id = ' . $kurseID;
	    $db->query( $sql );
	    
	    $sql = 'UPDATE kurse SET x_df_lastdeleted_origin = "' . $param['origin'] . '" WHERE kurse.id = ' . $kurseID . ";";
	    $db->query( $sql );
	}
	
	return 1;
}

?>