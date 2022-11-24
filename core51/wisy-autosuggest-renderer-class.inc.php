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
		// to use this with strings encoded with ISO-8859-15, call utf8_encode() before
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
	    $querystring = utf8_decode( strval( $this->framework->getParam('q') ) );
	    $querystring = strip_tags($querystring);
		
		$tagsuggestor =& createWisyObject('WISY_TAGSUGGESTOR_CLASS', $this->framework);

		switch( $this->framework->getParam('format') )
		{
			/* deprecated:
			   case 'json':
				// return as JSON, used by out OpenSearch implementation
				$tags = $tagsuggestor->suggestTags($querystring);
			
				if( SEARCH_CACHE_ITEM_LIFETIME_SECONDS > 0 )
					headerDoCache(SEARCH_CACHE_ITEM_LIFETIME_SECONDS);

				header('Content-type: application/json');
				
				echo '["' .$this->utf8_to_json(cs8($querystring)). '",[';
				    for( $i = 0; $i < sizeof((array) $tags); $i++ )
					{
						echo $i? ',' : '';
						echo '"' .$this->utf8_to_json(cs8($tags[$i]['tag'])). '"';
					}
				echo ']]';
				break;
			*/
				
			default:
			    // return as simple text, one tag per line, used by the site's AutoSuggest
			    if( $this->framework->getParam('type') == 'ort' )
			    {
			        $tags = $tagsuggestor->suggestTags($querystring, array('max'=>10, 'q_tag_type'=>array(512)));
			    }
			    else if( $this->framework->getParam('type') == 'anbieter')
			    {
				    $tag_type_anbieter = $this->framework->iniRead('autosuggest_sw_typ_anbieter', array(2, 131328, 256, 262144));
				    $tag_type_anbieter = (is_array($tag_type_anbieter)) ? $tag_type_anbieter : array_map("trim", explode(",", $tag_type_anbieter));
				    $tags = $tagsuggestor->suggestTags($querystring, array('max'=>10, 'q_tag_type'=>$tag_type_anbieter, 'q_tag_type_not'=>array(0,1,65536,4,8,32768,16,32,64,128,512,1024,2048,4096,8192,16384,65)));
				}
				else
				{

				    $tags = $tagsuggestor->suggestTags($querystring, array('max'=>10, 'q_tag_type_not'=>array(0,32,128,256,2048,4096,131072,262144)));
				    
				    if($this->framework->iniRead('search.ajax.combine_angebote_anbieter', false)) {
				        $tag_type_anbieter = $this->framework->iniRead('autosuggest_sw_typ_anbieter', array(2, 131328, 256, 262144));
				        $tag_type_anbieter = (is_array($tag_type_anbieter)) ? $tag_type_anbieter : array_map("trim", explode(",", $tag_type_anbieter));
				        $tags_anbieter = $tagsuggestor->suggestTags($querystring, array('max'=>10, 'q_tag_type'=>$tag_type_anbieter, 'q_tag_type_not'=>array(0,1,65536,4,8,32768,16,32,64,128,512,1024,2048,4096,8192,16384,65))); // 131072 = 65
				        
				        $tags = array_merge($tags, $tags_anbieter);
				    }
				    
				}
                
                // Filter out suggestions with tag_freq == 0
                $filtered_tags = array();
                for( $i = 0; $i < sizeof((array) $tags); $i++ )
                {
                    $skip = false;
                    
                    foreach($filtered_tags AS $filtered_tag) {
                        // eliminate double tags and tags without courses (except for synonyms)
                        if( ($filtered_tag['tag'] == $tags[$i]['tag']) || (intval($tags[$i]['tag_help']) == 0 && (!isset($tags[$i]['tag_freq']) || (intval($tags[$i]['tag_freq']) == 0) && intval($tags[$i]['tag_type']) != 64)) )
                            $skip = true;
                    }
                    
                    if(!$skip) {
                        $filtered_tags[] = $tags[$i];
                    }
                }
                

                // No results
                if( $this->framework->getParam('type') != 'ort')
                {
                    // If hidden synonym don't display: "Keine Suchvorschlaege"
                    if($tagsuggestor->getTagId($querystring) && count($filtered_tags) == 0) {
                        
                        $filtered_tags[] = array(
                            'tag'	=>	$querystring,
                            'tag_descr' => '&nbsp;',
                            'tag_type'	=> 0,
                            'tag_help'	=> -2 // indicates "no results"
                        );
                        
                    } elseif(count($filtered_tags) == 0) {
                        
                        $filtered_tags[] = array(
                            'tag'	=>	"Suchbegriff: ".$querystring,
                            'tag_descr' => 'Keine Suchvorschl'.(PHP7 ? utf8_decode("ä") : 'ä').'ge m'.(PHP7 ? utf8_decode("ö") : 'ö').'glich', // HTML entities not possible b/c 1:1 output by js
                            'tag_type'	=> 0,
                            'tag_help'	=> -2 // indicates "no results"
                        );
                        // addMoreLink at the end when more than 10 entries have been found
                    } else if(count($filtered_tags) > 9) {
                        
                        $filtered_tags[] = array(
                            'tag'	=>	$querystring,
                            'tag_descr' => 'Alle Suchvorschl'.(PHP7 ? utf8_decode("ä") : 'ä').'ge anzeigen',
                            'tag_type'	=> 0,
                            'tag_help'	=> 1 // indicates "more"
                        );
                    }
                }
				
				if( SEARCH_CACHE_ITEM_LIFETIME_SECONDS > 0 )
					headerDoCache(SEARCH_CACHE_ITEM_LIFETIME_SECONDS);
					
				for( $i = 0; $i < sizeof((array) $filtered_tags); $i++ )
				{
					$tag_freq = 0;
					if (isset($filtered_tags[$i]['tag_freq']) && !empty($filtered_tags[$i]['tag_freq'])) {
						$tag_freq = $filtered_tags[$i]['tag_freq'];
					}
					
					echo		$filtered_tags[$i]['tag'] . 
						"|"	.	$filtered_tags[$i]['tag_descr'] . 
						"|"	.	intval($filtered_tags[$i]['tag_type']) . 
						"|" .	intval($filtered_tags[$i]['tag_help']) . 
						"|" .	$tag_freq .
						"\n";
				}
				break;
		}
	}
}



