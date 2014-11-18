<?php


class G_JSON_CLASS
{
	function utf8_to_json($in_str)
	{
		// make sure, the mb_-functions are available - hierzu muss in php.ini extension_dir = /usr/local/lib/php_modules/5-STABLE/ eingetragen sein
		if (!extension_loaded('mbstring')) {
			if( function_exists('dl') ) {
				if (!@dl('mbstring.so')) {
					die('cannot load mbstring...');
				}
			}
		}
	
		// convert a UTF-8-encoded string to a JSON-encoded string.
		// to use this with strings encoded with ISO-8859-1, call utf8_encode() before
		$oldEnc = mb_internal_encoding();
		mb_internal_encoding("UTF-8");
			static $convmap = array(0x80, 0xFFFF, 0, 0xFFFF);
			static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
			$str = "";
			for($i=mb_strlen($in_str)-1; $i>=0; $i--)
			{
				$mb_char = mb_substr($in_str, $i, 1);
				if( mb_ereg("&#(\\d+);", mb_encode_numericentity($mb_char, $convmap, "UTF-8"), $match) )
				{
					$str = sprintf("\\u%04x", $match[1]) . $str;
				}
				else
				{
					$mb_char = str_replace($jsonReplaces[0], $jsonReplaces[1], $mb_char);
					$str = $mb_char . $str;
				}
			}
		mb_internal_encoding($oldEnc);
		return $str;
	}

}
