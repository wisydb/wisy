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
				if(isset($_GET['type']) &&  $_GET['type'] == 'ort')
				{
					$tags = $tagsuggestor->suggestTags($querystring, array('max'=>10, 'q_tag_type'=>array(512)));
				} 
				else if(isset($_GET['type']) &&  $_GET['type'] == 'anbieter')
				{
					$tag_type_anbieter = $this->framework->iniRead('autosuggest_sw_typ_anbieter', array(64,256));
					$tag_type_anbieter = (is_array($tag_type_anbieter)) ? $tag_type_anbieter : array_map("trim", explode(",", $tag_type_anbieter));
					$tags = $tagsuggestor->suggestTags($querystring, array('max'=>10, 'q_tag_type'=>$tag_type_anbieter));
				}
				else
				{
					$tags = $tagsuggestor->suggestTags($querystring, array('max'=>10, 'q_tag_type_not'=>array(64,256,512)));
				}
                
                // Filter out suggestions with tag_freq == 0
                $filtered_tags = array();
				for( $i = 0; $i < sizeof($tags); $i++ )
				{
					if(intval($tags[$i]['tag_help']) == 0 && intval($tags[$i]['tag_freq']) == 0) continue;
                    $filtered_tags[] = $tags[$i];
				}
                

				// No results
				if(!isset($_GET['type']) ||  $_GET['type'] != 'ort')
				{
					if(count($filtered_tags) == 0) {
						$filtered_tags[] = array(
							'tag'	=>	$querystring,
							'tag_descr' => 'Keine Suchvorschläge möglich',
							'tag_type'	=> 0,
							'tag_help'	=> -2 // indicates "no results"
						);
                        // addMoreLink at the end when more than 10 entries have been found
					} else if(count($filtered_tags) > 9) {
                            
						$filtered_tags[] = array(
							'tag'	=>	$querystring,
							'tag_descr' => 'Alle Suchvorschläge anzeigen',
							'tag_type'	=> 0,
							'tag_help'	=> 1 // indicates "more"
						);
					}
				}	
				
				if( SEARCH_CACHE_ITEM_LIFETIME_SECONDS > 0 )
					headerDoCache(SEARCH_CACHE_ITEM_LIFETIME_SECONDS);
					
				for( $i = 0; $i < sizeof($filtered_tags); $i++ )
				{
					
					echo		$filtered_tags[$i]['tag'] . 
						"|"	.	$filtered_tags[$i]['tag_descr'] . 
						"|"	.	intval($filtered_tags[$i]['tag_type']) . 
						"|" .	intval($filtered_tags[$i]['tag_help']) . 
						"|" .	intval($filtered_tags[$i]['tag_freq']) .
						"\n";
				}
				break;
		}
	}
}



