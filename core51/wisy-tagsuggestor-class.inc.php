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
		$this->db3			= new DB_Admin;
		$this->db4			= new DB_Admin;
	}

	
	//
	// manually addeed suggestions, they must be defined in the settings using tag.<tag> = <other>;<another>;<com>,<bined>;<etc>
	//
	protected function get_manual_suggestions($tag_name)
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
	
	public function keyword2tagName($keyword)
	{
		// function takes keywords or an offerer name and converts it into a tag by just removing the characters ":" and "," and double spaces
		$tag = strtr($keyword, ':,', '  ');
		while( strpos($tag, '  ')!==false ) {
			$tag = str_replace('  ', ' ', $tag);
		}
		return $tag;
	}
	
	public function getTagId($keyword_or_tag_name)
	{
		// returns the tag id for a given keyword/offerer name/tag name
		$tag = $this->keyword2tagName($keyword_or_tag_name);
		$this->db2->query("SELECT tag_id FROM x_tags WHERE tag_name=".$this->db2->quote($tag));
		if( $this->db2->next_record() ) {
		    return $this->db2->fcs8('tag_id');
		}
		return 0;
	}

	public function getWisyPortalTagId()
	{
		if( !isset($this->getWisyPortalTagId_cache) ) {	
			if( $GLOBALS['wisyPortalFilter']['stdkursfilter']!='' ) {
				$this->getWisyPortalTagId_cache = $this->getTagId('.portal'.$GLOBALS['wisyPortalId']);
			}
			else {
				$this->getWisyPortalTagId_cache = 0;
			}
		}
		return $this->getWisyPortalTagId_cache;
	}

	public function getTagFreq($tag_ids_arr)
	{
	    
	    if( sizeof((array) $tag_ids_arr) == 1 )
	    {
	            
	            $portalIdCond = '';
	            if( $GLOBALS['wisyPortalFilter']['stdkursfilter']!='' ) {
	                $portalIdCond = ' AND portal_id=' . $GLOBALS['wisyPortalId'] . ' ';
	            }
	            else {
	                $portalIdCond = ' AND portal_id=0 ';
	            }
	            $this->db2->query("SELECT tag_freq FROM x_tags_freq WHERE tag_id=".intval($tag_ids_arr[0]) . $portalIdCond); // x_tags_freq only contains recent offers, date checking is not required
	            if( $this->db2->next_record() ) {
	                return $this->db2->fcs8('tag_freq');
	            }
	    }
	    // more than one tag id
	    else if( sizeof((array) $tag_ids_arr) > 1 )
	    {
	            $portalTagId = $this->getWisyPortalTagId();
	            if( $portalTagId ) {
	                $tag_ids_arr[] = $portalTagId;
	            }
	            
	            $sql = "SELECT DISTINCT t.kurs_id AS cnt
			          FROM x_kurse_tags t
			          LEFT JOIN x_kurse k ON t.kurs_id=k.kurs_id
			         WHERE t.tag_id=" . intval($tag_ids_arr[0]);
	            for( $i = 1; $i < sizeof((array) $tag_ids_arr); $i++ ) {
	                $sql .= " AND t.kurs_id IN(SELECT kurs_id FROM x_kurse_tags WHERE tag_id=".intval($tag_ids_arr[$i]) . ") ";
	            }
	            $sql .= " AND k.beginn>=".$this->db2->quote(ftime("%Y-%m-%d"));
	                
	                $freq = 0;
	                $this->db2->query($sql);
	                while( $this->db2->next_record() ) {
	                    $freq++;
	                }
	                return $freq;
	    }
	    
	    return 0;
	}


	//
	// suggest some tags
	//
	function suggestTags($q_tag_name, $param = 0 /*can be an hash, for future use*/)
	{
		// check some parameters
		if( $param == 0 )
			$param = array();
	
		$max = isset($param['max'])? $param['max'] : 512; // plus the synonyms
		
		$min = intval($max / 2);
		if( $min > 6 ) $min = 6;
		
		$max_suggestions = 5;
			
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
			$COND_TAGTYPE		= "";
			
			// Allow filtering by tag_type
			if(isset($param['q_tag_type']) && is_array($param['q_tag_type'])) {
				if(count($param['q_tag_type']) == 1)
				{
					$COND_TAGTYPE .= " AND tag_type & " . intval($param['q_tag_type'][0]);
				} 
				else if(count($param['q_tag_type']) > 1)
				{
					$COND_TAGTYPE .= " AND (";
					for($i=0;$i<count($param['q_tag_type']);$i++)
					{
						$COND_TAGTYPE .= "tag_type & " . intval($param['q_tag_type'][$i]);
						if($i < count($param['q_tag_type']) - 1) $COND_TAGTYPE .= " OR ";
					}
					$COND_TAGTYPE .= ")";
				}
				
				if(in_array(512, $param['q_tag_type']))
				{
					// No fulltext or soundex for "ort" -> TODO db: besser konfigurierbar machen
					$use_soundex = 0;
					$suggest_fulltext = 0;
				}
			}

			if(isset($param['q_tag_type_not']) && is_array($param['q_tag_type_not'])) {          
				foreach($param['q_tag_type_not'] as $qttn)
				{
					$COND_TAGTYPE .= " AND NOT(tag_type & " . intval($qttn) . ")";
				}
			}
			
			$portalIdCond = '';
			if( $GLOBALS['wisyPortalFilter']['stdkursfilter']!='' ) {
				$portalIdCond = ' AND f.portal_id=' . $GLOBALS['wisyPortalId'] . ' ';
			}
			else {
				$portalIdCond = ' AND f.portal_id=0 ';
			}
			
			$ret = array();
			$tags_done  = array();
			$links_done = array();
			
			// Usually only one try - two tries for fuzzy post-search-searches
			for( $tries = 0; $tries <= 1; $tries ++ )
			{
			    // First try: search for tags containing q-string
				$COND .= $COND_TAGTYPE;
                $sql = "SELECT t.tag_id, tag_name, tag_descr, tag_type, tag_help, SUM(tag_freq) AS tag_freq 
							FROM x_tags t 
							LEFT JOIN x_tags_freq f ON f.tag_id=t.tag_id $portalIdCond
							WHERE ( $COND )
							$portalIdCond
							GROUP BY tag_name 
							ORDER BY LEFT(tag_name,$LEN)<>'$QUERY', tag_name LIMIT 0, $max"; // sort alphabetically - matching word beginning esp. important! Group By because multiple same tag_name entries
                            
				$this->db->query($sql); 
				while( $this->db->next_record() )
				{
				    // tag matching q-string found...
				    
				    // add the tag
				    $tag_id   = intval($this->db->fcs8('tag_id'));
				    $tag_name = $this->db->fcs8('tag_name');
				        
				    $tag_descr = $this->db->fcs8('tag_descr');
				    $tag_type = intval($this->db->fcs8('tag_type'));
				    $tag_help = intval($this->db->fcs8('tag_help'));
				    $tag_freq = intval($this->db->fcs8('tag_freq'));
				    $tag_anbieter_id = '';
				    $tag_groups = array();
					
					if( !isset($tags_done [ $tag_name ])   // kein Tag zweimal ausgeben (koennte passieren, wenn es sowohl durch die buchstabenadditive und duch die fehlertolerante Suche gefunden wuerde)
					 && !isset($links_done[ $tag_name ]) ) // wenn zuvor auf ein lemma via Synonym verwiesen wurde, dieses Lemma nicht noch einmal einzeln hinzufügen
					{
						$fuzzy = $tries==1? 0x20000000 : 0;
						$tags_done[ $tag_name ] = 1;
						$names = array();
						
						// check if found tag (matching q-string) is a synonym ...
						if($tag_type&64 || $tag_type&262144) //  // 64: Public synonym // 262144: Public Anbieternamensverweisung // 131072 = 65: Invisible Anbieternamensverweisung
						{
						    // While it's not useful to add a simple synonym (one destination) an open synonym or Anbieter-Namensverweisung may point to several tags / Anbieter => useful to add to suggestions / search by synonym/Anbieter-Namensverweisung
						    if($tag_type&64 || $tag_type&262144) {
						        $names[] = array(	'tag_name'=>$tag_name,
						            'tag_descr'=>$tag_descr,
						            'tag_type'=>$tag_type,
						            'tag_help'=>$tag_help,
						            'tag_freq'=>$tag_freq);
						    }
						    
							$this->db2->query("SELECT tag_name, tag_descr, tag_type, tag_help, tag_freq
													FROM x_tags t 
													LEFT JOIN x_tags_syn s ON s.lemma_id=t.tag_id 
													LEFT JOIN x_tags_freq f ON f.tag_id=t.tag_id $portalIdCond
													WHERE s.tag_id=$tag_id $COND_TAGTYPE $portalIdCond");
							while( $this->db2->next_record() )
							{
							        $names[] = array(	'tag_name'=>$this->db2->fcs8('tag_name'),
							            'tag_descr'=>$this->db2->fcs8('tag_descr'),
							            'tag_type'=>$this->db2->fcs8('tag_type'),
							            'tag_help'=>$this->db2->fcs8('tag_help'),
							            'tag_freq'=>$this->db2->fcs8('tag_freq'));
							}
						}
						
						if($this->framework->iniRead('search.suggest.v2') == 1)
						{
							// Anbieter-ID abfragen
							if( $tag_type&256 )
							{
							    $sql = "SELECT id FROM anbieter WHERE REPLACE(suchname, ',', '')=". $this->db3->quote($tag_name);	// Commas are always removed from tag names => ignore in comparison
							    $this->db3->query($sql);
								$this->db3->next_record();
								$tag_anbieter_id = $this->db3->fcs8('id');
							}
					
							// Find Ancestor(s)
							{
							    // 32 = verstecktes Synonym, 256 = Volltext Titel, 512 = Volltext Beschreibung, 2048 = Verwaltungsstichwort, 8192 = Schlagwort nicht verwenden
							    $dontdisplay = array(32, 256, 512, 2048, 8192);
							    
							    // 1. Anhand $tag_name in stichwoerter die stichwort-ID ermitteln
							    $sql = "SELECT id, eigenschaften FROM stichwoerter WHERE stichwort=". $this->db4->quote($tag_name);
							    $this->db4->query($sql);
							    
							    if( $this->db4->next_record() )
							    {
							        $stichwort_id = $this->db4->fcs8('id');
							        
							        // 2. in stichwoerter_verweis2 Oberbegriffe finden
							        $this->db4->query("SELECT id, stichwort, primary_id, eigenschaften
														FROM stichwoerter_verweis2
														LEFT JOIN stichwoerter ON id=primary_id
														WHERE attr_id = " . intval($stichwort_id) );
							        
							        while( $this->db4->next_record() )
							        {
							            $tag_type = $this->db4->fcs8('eigenschaften');
							            
							            if(!in_array($tag_type, $dontdisplay))
							                $tag_groups[] = $this->db4->fcs8('stichwort');
							        }
							    }
							}
						}
							
							
						// get manually added suggestions
						$has_man_sug = false;
						{
						    $temp = $this->get_manual_suggestions($tag_name);
						    if( sizeof((array) $temp['sug']) )
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
						
							
						if( sizeof((array) $names) == 1 && !$has_man_sug /* manual suggestions should always be shown*/ )
						{
							// ... only one destination as a simple synonym: directly follow 1-dest-only-synonyms
							$tag_array = array(	'tag' => $tag_name, 
											'tag_descr'=>$names[0]['tag_descr'],
											'tag_type' => ($names[0]['tag_type'] & ~64) | $fuzzy,
											'tag_help'=>intval($names[0]['tag_help']),
											'tag_freq'=>intval($names[0]['tag_freq']) /*the link itself has no freq*/	);
											
							if($this->framework->iniRead('search.suggest.v2') == 1)
							{
								$tag_array['tag_anbieter_id'] = $tag_anbieter_id;
								$tag_array['tag_groups'] = $tag_groups;
							}
							if($fuzzy == 0 || $max_suggestions-- > 0)
							{
								$ret[] = $tag_array;
							}
						}
						else if( sizeof((array) $names) >= 1 ) 
						{
							// ... more than one destinations
							if($fuzzy == 0 || $max_suggestions-- > 0)
							{
								$ret[] = array(	'tag' => $tag_name, 'tag_type' => 64 | $fuzzy, 'tag_help' => intval($tag_help) );
							}
							for( $n = 0; $n < sizeof((array) $names); $n++ )
							{
								$dest = $names[$n]['tag_name'];
								$tag_array = array(	'tag' => $dest, 
												'tag_descr'=>$names[$n]['tag_descr'],
												'tag_type' => ($names[$n]['tag_type'] & ~64) | 0x10000000, 
												'tag_help'=>intval($names[$n]['tag_help']),
												'tag_freq'=>intval($names[$n]['tag_freq']) /*the link itself has no freq*/	);
												
								if($this->framework->iniRead('search.suggest.v2') == 1)
								{
									$tag_array['tag_anbieter_id'] = $tag_anbieter_id;
									$tag_array['tag_groups'] = $tag_groups;
								}
								$ret[] = $tag_array;
												
								$links_done[ $dest ] = 1;
							}
						}
						else
						{
							// ... simple lemma
							$tag_array = array(	'tag' 		=> $tag_name, 
											'tag_descr' => $tag_descr, 
											'tag_type'	=> $tag_type | $fuzzy, 
											'tag_help'	=> intval($tag_help),
											'tag_freq'	=> $tag_freq	);

							if($this->framework->iniRead('search.suggest.v2') == 1)
							{
								$tag_array['tag_anbieter_id'] = $tag_anbieter_id;
								$tag_array['tag_groups'] = $tag_groups;
							}
							if($fuzzy == 0 || $max_suggestions-- > 0)
							{
								$ret[] = $tag_array;
							}
						}
					}
				}
				
				require_once("ki_admin/lib/soundex/x3m_soundex_ger.php");

				// if there are only very few results, try an additional soundex search = has equal word value
				if( sizeof((array) $ret) < $min && $use_soundex )
					$COND = "tag_soundex='".soundex_ger($q_tag_name)."'";
				else
				    break; // stop searching with one try if no tag found containing q
			}

			// 15.11.2012: Der Vorschlag zur Volltextsuche kann nun ausgeschaltet werden
			if( $suggest_fulltext )
			{
			        $searcher_tmp =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
			        
			        // 13.02.2010: die folgende Erweiterung bewirkt, das neben den normalen Vorschl√§gen auch immer die Volltextsuche vorgeschlagen wird -
			        // und zwar in der Ajax-Vorschlagliste und auch unter "Bitte verfeinern Sie Ihren Suchauftrag"
			        // wenn man hier differenzierter Vorgehen m√∂chte, muss man ein paar Ebenen h√∂her ansetzen (bp)
			        if( strlen($q_tag_name) >= $searcher_tmp->getMinChars() ) { // only if long enough for MYSQL actually search in fulltext mode
			            $ret[] = array(
			                'tag'	=>	'volltext:' . $q_tag_name,
			                'tag_descr' => '',
			                'tag_type'	=> 0,
			                'tag_help'	=> -3 // to signify "volltext"
			            );
			        }
			        // /13.02.2010:
			}
			// /15.11.2012
			

		}
		
		return $ret;
	}	
};
