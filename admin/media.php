<?php



/*=============================================================================
simply dump a blob / text as binary data from the database
===============================================================================

file:	
	media.php
	
author:	
	Bjoern Petersen

parameters:
	id	-	the media ID to dump
	t	-	the table to get the media from, defaults to 'media'
	f	-	the field to get the media from, defaults to 'file'
	hex -	if true, a hexdump is processed instead of the binary data,
			if set to 'all', really all data are dumped

alternativly the file can be called as:

	                       media.php/<id>/filename.ext
	               media.php/<table>/<id>/filename.ext
	       media.php/<field>/<table>/<id>/filename.ext

this is usefull as the file is a "valid" file with path and extension.

=============================================================================*/



require_once('sql_curr.inc.php');
require_once('config/config.inc.php');
require_once('classes.inc.php');



// get all request parameter
$hex = $_REQUEST['hex'];
$id = intval($_REQUEST['id']);
if( !$id )
{
	$param = explode('/', $_SERVER['REQUEST_URI']);
	for( $i = 0, $p = sizeof($param)-2; $p >= 0; $p-- ) 
	{
		if( $param[$p] == 'media.php' ) {
			break;
		}
		else switch( $i++ ) {
			case 0: $id	= intval($param[$p]); break;
			case 1: $t	= $param[$p]; break;
			case 2: $f	= $param[$p]; break;
		}
	}
}
else
{
	$t = $_REQUEST['t'];
	$f = $_REQUEST['f'];
}



// correct table and field, use urlencode to make sure, there are no "bad" characters in the string
$t = urlencode($t);
if( $t == '' ) {
	$t = 'media';
}

$f = urlencode($f);
if( $f == '' ) 
{	$f = 'file';
}


//
// connect to database, get record
//
$db = new DB_Admin;
$db->query("SELECT $f FROM $t WHERE id=$id");
if( $db->next_record() )
{
	$ob = new G_BLOB_CLASS($db->fs($f));
	if( $hex )
	{
		//
		// dump BLOB as hex
		//
		require_once('functions.inc.php');
		echo '<html><head><title>Hexdump</title></head><body><pre>'; $s = '';
			echo isohtmlentities($ob->name);
			if( $ob->w || $ob->h ) {
				printf(", %d x %d", $ob->w, $ob->h);
			}
			printf(", %s", isohtmlentities($ob->mime));
			printf(", %d Bytes", strlen($ob->blob));
			
			echo "\n\n";
			
			$str = $ob->blob;
			$strl = strlen($str);
			if( $hex != 'all' ) {
				$strl = 256;
			}
			
			for( $i = 0; $i < $strl; $i++ ) 
			{
				if( ($i % 16) == 0 ) { 
					printf("%08x: ", $i); 
				}
				
				$o = ord(substr($str, $i, 1));
				$s .= $o > 32? isohtmlentities(chr($o)) : '.';
				
				printf("%02x%s", $o, (($i%16)==7?"|":" "));
				
				if( ($i % 16) == 15 ) { 
					printf("| %s\n", $s); $s = ''; 
				}
			}
			
			if( $strl%16 ) {
				for( $i = 0; $i < (16-($strl%16)); $i++ ) {
					echo '   ';
				}
				printf("| %s\n", $s); $s = ''; 
			}
			
			if( $hex != 'all' && $strl != strlen($str) ) {
				echo "\n<a href=\"media.php?t=$t&f=$f&id=$id&hex=all\">all ".strlen($str)." Bytes...</a>";
			}
			
		echo '</pre></body></html>';
	}
	else if( strlen($ob->blob) )
	{
		//
		// dump BLOB
		// 
		header('Content-type: ' . $ob->mime);
		header('Content-disposition: filename=' . $ob->name . ';');
		header("Content-length: " . strlen($ob->blob));
		header("Cache-Control: public");
		header('Expires: ' . gmdate("D, d M Y H:i:s", intval(time()+43200)) . ' GMT'); // in 12 hours
		echo $ob->blob;
	}

}
