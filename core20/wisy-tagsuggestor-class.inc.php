<?php if( !defined('IN_WISY') ) die('!IN_WISY');

class WISY_TAGSUGGESTOR_CLASS
{
	// all private!
	var $framework;

	function __construct(&$framework, $param)
	{
		$this->framework	=& $framework;
		$this->db			= new DB_Admin;
		$this->db2			= new DB_Admin;
	}

	
	//
	// manually addeed suggestions, they must be defined in the settings using tag.<tag> = <other>;<another>;<com>,<bined>;<etc>
	//
	private function get_manual_suggestions($tag_name)
	{
		$ret = array('sug' => array());
		
		$all = $this->framework->iniRead('tag.'.$tag_name, '');
		if( $all != '' ) {
			$all = explode(';', $all);
			for( $m = 0; $m < sizeof($all); $m++ ) {
				$one = trim($all[$m]);
				if( $one != '' ) {
					$ret['sug'][] = array('tag_name'=>$one);
				}
			}
		}
		
		return $ret;
	}
	

	//
	// suggest some tags
	//
	function suggestTags($q_tag_name, $param = 0 /*can be an hash, for future use*/)
	{
		global $wisyPortalId;
		
		// check some parameters
		if( $param == 0 )
			$param = array();
			
		$max = isset($param['max'])? $param['max'] : 512; // plus the synonyms
		
		$min = intval($max / 2);
		if( $min > 6 ) $min = 6;
			
		$use_soundex      = $this->framework->iniRead('search.suggest.fuzzy',    1)!=0;
		$suggest_fulltext = $this->framework->iniRead('search.suggest.fulltext', 1)!=0;
	
		// return an array with suggestions ...
		$ret = array();
		if( strlen($q_tag_name) >= 1 )
		{
			$QUERY				= addslashes($q_tag_name);
			$LEN				= strlen($q_tag_name);
			$WILDCARDATSTART	= $LEN>1? '%' : '';
			$COND				= "tag_name LIKE '$WILDCARDATSTART$QUERY%'";
			
			//$PORTALFILTER 		= $this->framework->cacheRead('stats.tag_filter', '(1)');
			
			//$portalIdFor		= $PORTALFILTER=='(1)'? 0 : $wisyPortalId;
			$portalIdFor		= $GLOBALS['wisyPortalFilter']['stdkursfilter']!=''? $GLOBALS['wisyPortalId'] : 0;
			
			$ret = array();
			$tags_done  = array();
			$links_done = array();
			for( $tries = 0; $tries <= 1; $tries ++ )
			{
				$sql = "SELECT t.tag_id, tag_name, tag_descr, tag_type, tag_help, tag_freq 
							FROM x_tags t 
							LEFT JOIN x_tags_freq f ON f.tag_id=t.tag_id AND f.portal_id=$portalIdFor
							WHERE ( $COND )
							AND f.portal_id=$portalIdFor
							ORDER BY LEFT(tag_name,$LEN)<>'$QUERY', tag_name LIMIT 0, $max"; // sortierung alphabetisch, richtiger Wortanfang aber immer zuerst!

				$this->db->query($sql); 
				while( $this->db->next_record() )
				{
					// add the tag
					$tag_id   = intval($this->db->f('tag_id'));
					$tag_name = $this->db->fs('tag_name');
					$tag_descr = $this->db->fs('tag_descr');
					$tag_type = intval($this->db->f('tag_type'));
					$tag_help = intval($this->db->f('tag_help'));
					$tag_freq = intval($this->db->f('tag_freq'));
					
					if( !$tags_done [ $tag_name ]   // kein Tag zweimal ausgeben (koennte passieren, wenn es sowohl durch die buchstabenadditive und duch die fehlertolerante Suche gefunden wuerde)
					 && !$links_done[ $tag_name ] ) // wenn zuvor auf ein lemma via Synonym verwiesen wurde, dieses Lemma nicht noch einmal einzeln hinzufügen
					{
						$fuzzy = $tries==1? 0x20000000 : 0;
						$tags_done[ $tag_name ] = 1;
						$names = array();
						
						// get synonyms ...
						if( $tag_type&64 )
						{
							$this->db2->query("SELECT tag_name, tag_descr, tag_type, tag_help, tag_freq
													FROM x_tags t 
													LEFT JOIN x_tags_syn s ON s.lemma_id=t.tag_id 
													LEFT JOIN x_tags_freq f ON f.tag_id=t.tag_id AND f.portal_id=$portalIdFor
													WHERE s.tag_id=$tag_id AND f.portal_id=$portalIdFor");
							while( $this->db2->next_record() )
							{
								$names[] = array(	'tag_name'=>$this->db2->fs('tag_name'), 
													'tag_descr'=> $this->db2->fs('tag_descr'),
													'tag_type'=>$this->db2->f('tag_type'), 
													'tag_help'=>$this->db2->f('tag_help'), 
													'tag_freq'=>$this->db2->f('tag_freq'));
							}
						}
							
							
						// get manually added suggestions
						$has_man_sug = false;
						{
							$temp = $this->get_manual_suggestions($tag_name);
							if( sizeof($temp['sug']) )
							{
								$has_man_sug = true;
								for( $n = 0; $n < sizeof($temp['sug']); $n++ )
								{
									$names[] = array(	'tag_name'=>$temp['sug'][$n]['tag_name'],
														'tag_descr'=>'',
														'tag_type'=>0,
														'tag_help'=>0,
														'tag_freq'=>0			);
								}
							}
						}
						
							
						if( sizeof($names) == 1 && !$has_man_sug /* manual suggestions should always be shown*/ )
						{
							// ... only one destination as a simple synonym: directly follow 1-dest-only-synonyms
							$ret[] = array(	'tag' => $tag_name, 
											'tag_descr'=>$names[0]['tag_descr'],
											'tag_type' => ($names[0]['tag_type'] & ~64) | $fuzzy,
											'tag_help'=>intval($names[0]['tag_help']),
											'tag_freq'=>intval($names[0]['tag_freq']) /*the link itself has no freq*/	);
						}
						else if( sizeof($names) >= 1 ) 
						{
							// ... more than one destinations
							$ret[] = array(	'tag' => $tag_name, 'tag_type' => 64 | $fuzzy, 'tag_help' => intval($tag_help) );
							for( $n = 0; $n < sizeof($names); $n++ )
							{
								$dest = $names[$n]['tag_name'];
								$ret[] = array(	'tag' => $dest, 
												'tag_descr'=>$names[$n]['tag_descr'],
												'tag_type' => ($names[$n]['tag_type'] & ~64) | 0x10000000, 
												'tag_help'=>intval($names[$n]['tag_help']),
												'tag_freq'=>intval($names[$n]['tag_freq']) /*the link itself has no freq*/	);
								$links_done[ $dest ] = 1;
							}
						}
						else
						{
							// ... simple lemma
							$ret[] = array(	'tag' 		=> $tag_name, 
											'tag_descr' => $tag_descr, 
											'tag_type'	=> $tag_type | $fuzzy, 
											'tag_help'	=> intval($tag_help),
											'tag_freq'	=> $tag_freq	);
						}
					}
				}

				// if there are only very few results, try an additional soundex search
				if( sizeof($ret) < $min && $use_soundex )
					$COND = "tag_soundex='".soundex($q_tag_name)."'";
				else
					break;
			}

			// 15.11.2012: Der Vorschlag zur Volltextsuche kann nun ausgeschaltet werden
			if( $suggest_fulltext )
			{
				// 13.02.2010: die folgende Erweiterung bewirkt, das neben den normalen Vorschlägen auch immer die Volltextsuche vorgeschlagen wird -
				// und zwar in der Ajax-Vorschlagliste und auch unter "Bitte verfeinern Sie Ihren Suchauftrag"
				// wenn man hier differenzierter Vorgehen möchte, muss man ein paar Ebenen höher ansetzen (bp)
				$ret[] = array(
					'tag'	=>	'volltext:' . $q_tag_name,
					'tag_descr' => '',
					'tag_type'	=> 0,
					'tag_help'	=> 0
				);
				// /13.02.2010: 
			}
			// /15.11.2012

		}
		
		return $ret;
	}	
};
