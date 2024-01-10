<?php

/*=============================================================================
File Upload Tools
===============================================================================

Author:	
	Bjoern Petersen

===============================================================================

- die max. Uploadgroessee wird von upload_max_filesize, post_max_size UND 
  memory_limit gesteuert. Grob gesprochen, ist der kleinste dieser drei Werte
  die max. Uploadgroesse.
  post_max_size sollte dabei aber groesser als upload_max_filesize sein - ansonsten
  kann kein vernuenftiger Upload-Fehler bei zu grossen Dateien ausgegeben werden.

- <input type="file" /> l..t sich nicht vernuenftig style, ein Hack ist unter
  http://www.quirksmode.org/dom/inputfile.html beschrieben (verwendet opaque um 
  das eigentliche Element unsichtbar, aber noch klickbar zu machen ...)

- Ansonsten gibt es mittlerweile (02.07.2012) einige weitere Methoden, um 
  Dateien auf den Server zu spielen, s. z.B. http://www.w3.org/TR/file-upload/
  ... und haufenweise JavaScript/jQuery Frameworks, die teils aber viel zu 
  komplex sind und in meinen Versuchen auch nicht auf Anhieb funkionierten.	

- for MAX_FILE_SIZE, see http://www.php.net/manual/en/features.file-upload.post-method.php
  
=============================================================================*/	

class IMP_UPLOADER_CLASS
{
	function shorthand_to_int($str)
	{
		$str = trim($str);
		if( substr($str, -1)=='K' ) {
			return intval(str_replace('K', '', $str)) * 1024;
		} elseif( substr($str, -1)=='M' ) {
			return intval(str_replace('M', '', $str)) * 1024 * 1024;
		} elseif( substr($str, -1)=='G' ) {
			return intval(str_replace('G', '', $str)) * 1024 * 1024 * 1024;
		}
		else {
			return intval($str);
		}
	}
	
	function get_max_upload_bytes()
	{
		$upload_max_filesize = $this->shorthand_to_int( @ini_get('upload_max_filesize') ); 
		$post_max_size = intval($this->shorthand_to_int( @ini_get('post_max_size') ) * 0.9 /*leave 10% reserve*/ ); 
		$memory_limit = intval($this->shorthand_to_int( @ini_get('memory_limit') ) * 0.8 /*leave 20% reserve*/); 
		
		// post_max_size and memory_limit may not be set correctly, so if they are 0, ignore them
		$ret = $upload_max_filesize;
		if( $post_max_size > 0 && $post_max_size < $ret )	{ $ret = $post_max_size; }
		if( $memory_limit > 0 && $memory_limit < $ret )		{ $ret = $memory_limit;  }
		
		return $ret;
	}
	
	function render_autosubmit_form()
	{
		$max_upload_bytes = $this->get_max_upload_bytes();
		form_tag('uploadform', 'imp.php', '', 'multipart/form-data', 'POST');
			echo htmlconstant('_IMP_UPLOADFILE', smart_size($max_upload_bytes)) .' ';
			echo '<input type="hidden" name="MAX_FILE_SIZE" value="'.$max_upload_bytes.'" />';
			echo '<input type="file" name="uploadfile" size="4" />';
			echo '<input type="hidden" name="uploadsubsequent" value="1" />';
			echo '<noscript><input type="submit" /></noscript>';
		echo '</form>';
	}
	
	function form_submitted()
	{
	    return (isset($_REQUEST['uploadsubsequent']) && $_REQUEST['uploadsubsequent'] ? true : false);
	}
	
	function get_uploaded_file()
	{
	    $ret = isset($_FILES['uploadfile']) ? $_FILES['uploadfile'] : '';
		
		if( $ret['error'] == UPLOAD_ERR_INI_SIZE )
		{
			$ret['error_msg'] = htmlconstant('_IMP_UPLOADFILETOOBIG', smart_size($this->get_max_upload_bytes()));
		}
		else if( $ret['tmp_name'] == '' || $ret['tmp_name'] == 'none' || !is_uploaded_file($ret['tmp_name']) /*this may be false if somebody wants too fool us ...*/ )
		{
			$ret['error_msg'] = htmlconstant('_IMP_UPLOADNOFILE');
		}
		else if( $ret['size'] <= 0 )
		{
			$ret['error_msg'] = htmlconstant('_IMP_UPLOADEMPTY');
		}
		else if( substr($ret['name'], -4)!='.mix' ) 
		{
			$ret['error_msg'] = htmlconstant('_IMP_UPLOADNOMIXFILE');
		}
		
		if( $ret['error'] )
		{
			$ret['error_msg'] .= ' (' . htmlconstant('_IMP_UPLOADERRORCODE', $ret['error']) . ')';
		}
		
		return $ret;
	}
	
};