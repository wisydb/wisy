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
	        $gzip = (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE);
	        
	        $sql = "SELECT css, css_gz, date_modified FROM portale WHERE id=$wisyPortalId;";
	        
	        $db->query($sql);
	        if( $db->next_record() )
	        {
	            $css = $db->f8('css');
	            $css_gz = $db->f('css_gz');
	            $date_modified = $db->f('date_modified');
	            
	            header("Content-type: text/css");
	            header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60 * 24 * 30))); // 1 month // headerDoCache();
	            header('Last-Modified: '.gmdate('D, d M Y H:i:s \G\M\T', strtotime($date_modified)));
	            
	            // ! reactivate when population of css_gz is explained
	            if(false && $gzip && trim($css_gz) != "") {
	                header ("Content-Encoding: gzip");
	                header("Content-length: " . strlen($css_gz));
	                echo $css_gz;
	            } else {
	                header("Content-length: " . strlen($css));
	                echo $css;
	            }
	            
	        }
	        $db->free($sql);
	        $db->close();
	    } elseif( $this->param['src'] == 'core.css' )
	    {
	        $gzip = (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE);
	        
	        
	        $db->query($sql);
	        if( $db->next_record() )
	        {
	            $css = $db->f8('css');
	            $css_gz = $db->f('css_gz');
	            $date_modified = $db->f('date_modified');
	            
	            header("Content-type: text/css");
	            header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60 * 24 * 30))); // 1 month // headerDoCache();
	            header('Last-Modified: '.gmdate('D, d M Y H:i:s \G\M\T', strtotime($date_modified)));
	            
	            if($gzip && trim($css_gz) != "") {
	                header ("Content-Encoding: gzip");
	                header("Content-length: " . strlen($css_gz));
	                echo $css_gz;
	            } else {
	                header("Content-length: " . strlen($css));
	                echo $css;
	            }
	            
	        }
	        $db->free($sql);
	        $db->close();
	    }
	}
}



