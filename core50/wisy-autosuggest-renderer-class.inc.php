<?php if( !defined('IN_WISY') ) die('!IN_WISY');



class WISY_AUTOSUGGEST_RENDERER_CLASS
{
	var $framework;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
	}

	function utf8_to_json($in_str)
	{
		// make sure, the mb_-functions are available - hierzu muss in php.ini extension_dir = /usr/local/lib/php_modules/5-STABLE/ eingetragen sein
		if (!extension_loaded('mbstring')) {
			if (!dl('mbstring.so')) {
				die('cannot load mbstring...');
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
	
	function render()
	{
		$querystring = utf8_decode($_GET["q"]);
		$querystring = strip_tags($querystring);
		
		$tagsuggestor =& createWisyObject('WISY_TAGSUGGESTOR_CLASS', $this->framework);

		switch( $_GET['format'] )
		{
			case 'json':
				// return as JSON, used by out OpenSearch implementation
				$tags = $tagsuggestor->suggestTags($querystring);
			
				if( SEARCH_CACHE_ITEM_LIFETIME_SECONDS > 0 )
					headerDoCache(SEARCH_CACHE_ITEM_LIFETIME_SECONDS);

				header('Content-type: application/json');
				
				echo '["' .$this->utf8_to_json(utf8_encode($querystring)). '",[';
					for( $i = 0; $i < sizeof($tags); $i++ )
					{
						echo $i? ',' : '';
						echo '"' .$this->utf8_to_json(utf8_encode($tags[$i]['tag'])). '"';
					}
				echo ']]';
				break;
			
			default:
				// return as simple text, one tag per line, used by the site's AutoSuggest
				$tags = $tagsuggestor->suggestTags($querystring, array('max'=>10));
				
				if($this->framework->iniRead('search.suggest.v2') == 1)
				{
				
					// add Headline and MoreLink at the beginning
					array_unshift($tags, array(
						'tag' => $querystring,
						'tag_descr' => 'Suchvorschl&auml;ge:',
						'tag_type'	=> 0,
						'tag_help'	=> -1 // indicates "headline"
					),
					array(
						'tag'	=>	$querystring,
						'tag_descr' => sizeof($tags)? 'Alle Vorschl&auml;ge im Hauptfenster anzeigen ...' : 'Keine Treffer',
						'tag_type'	=> 0,
						'tag_help'	=> 1 // indicates "more"
					));	
				}

				// addMoreLink at the end
				$tags[] = array(
					'tag'	=>	$querystring,
					'tag_descr' => sizeof($tags)? 'Alle Vorschl&auml;ge im Hauptfenster anzeigen ...' : 'Keine Treffer',
					'tag_type'	=> 0,
					'tag_help'	=> 1 // indicates "more"
				);			
				
				if( SEARCH_CACHE_ITEM_LIFETIME_SECONDS > 0 )
					headerDoCache(SEARCH_CACHE_ITEM_LIFETIME_SECONDS);
					
				for( $i = 0; $i < sizeof($tags); $i++ )
				{
					echo		$tags[$i]['tag'] . 
						"|"	.	$tags[$i]['tag_descr'] . 
						"|"	.	intval($tags[$i]['tag_type']) . 
						"|" .	intval($tags[$i]['tag_help']) . 
						"|" .	intval($tags[$i]['tag_freq']) .
						"\n";
				}
				break;
		}
	}
}



