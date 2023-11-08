<?php

function suggestTags($q_tag_name, $param = 0 /*can be an hash, for future use*/)
{
	$db			= new DB_Admin;
	$db2			= new DB_Admin;
	$db3			= new DB_Admin;
	$db4			= new DB_Admin;
	// check some parameters
	if ($param == 0)
		$param = array();

	$max = isset($param['max']) ? $param['max'] : 512; // plus the synonyms

	$min = intval($max / 2);
	if ($min > 6) $min = 6;

	$max_suggestions = 5;

	$use_soundex      = True;
	$suggest_fulltext = True;
	// return an array with suggestions ...
	$ret = array();
	if (strlen($q_tag_name) >= 1) {
		$QUERY				= addslashes($q_tag_name);
		$LEN				= strlen($q_tag_name);
		$WILDCARDATSTART	= $LEN > 1 ? '%' : '';
		$COND				= "tag_name LIKE '$WILDCARDATSTART$QUERY%'";
		$COND_TAGTYPE		= "";

		if (isset($param['q_tag_type_not']) && is_array($param['q_tag_type_not'])) {
			foreach ($param['q_tag_type_not'] as $qttn) {
				$COND_TAGTYPE .= " AND NOT(tag_type & " . intval($qttn) . ")";
			}
		}

		$portalIdCond = '';
		if ($GLOBALS['wisyPortalFilter']['stdkursfilter'] != '') {
			$portalIdCond = ' AND f.portal_id=' . $GLOBALS['wisyPortalId'] . ' ';
		} else {
			$portalIdCond = ' AND f.portal_id=0 ';
		}

		$ret = array();
		$tags_done  = array();
		$links_done = array();

		// Usually only one try - two tries for fuzzy post-search-searches
		for ($tries = 0; $tries <= 1; $tries++) {
			// First try: search for tags containing q-string
			$COND .= $COND_TAGTYPE;
			$sql = "SELECT t.tag_id, tag_name, tag_descr, tag_type, tag_help, SUM(tag_freq) AS tag_freq 
							FROM x_tags t 
							LEFT JOIN x_tags_freq f ON f.tag_id=t.tag_id $portalIdCond
							WHERE ( $COND )
							$portalIdCond
							GROUP BY tag_name 
							ORDER BY LEFT(tag_name,$LEN)<>'$QUERY', tag_name LIMIT 0, $max"; // sort alphabetically - matching word beginning esp. important! Group By because multiple same tag_name entries

			$db->query($sql);
			while ($db->next_record()) {
				// tag matching q-string found...

				// add the tag
				$tag_id   = intval($db->fcs8('tag_id'));
				$tag_name = $db->fcs8('tag_name');

				$tag_descr = $db->fcs8('tag_descr');
				$tag_type = intval($db->fcs8('tag_type'));
				$tag_help = intval($db->fcs8('tag_help'));
				$tag_freq = intval($db->fcs8('tag_freq'));
				$tag_anbieter_id = '';
				$tag_groups = array();

				if (
					!isset($tags_done[$tag_name])   // kein Tag zweimal ausgeben (koennte passieren, wenn es sowohl durch die buchstabenadditive und duch die fehlertolerante Suche gefunden wuerde)
					&& !isset($links_done[$tag_name])
				) // wenn zuvor auf ein lemma via Synonym verwiesen wurde, dieses Lemma nicht noch einmal einzeln hinzufügen
				{
					$fuzzy = $tries == 1 ? 0x20000000 : 0;
					$tags_done[$tag_name] = 1;
					$names = array();

					// check if found tag (matching q-string) is a synonym ...
					if ($tag_type & 64 || $tag_type & 262144) //  // 64: Public synonym // 262144: Public Anbieternamensverweisung // 131072 = 65: Invisible Anbieternamensverweisung

					{
						// While it's not useful to add a simple synonym (one destination) an open synonym or Anbieter-Namensverweisung may point to several tags / Anbieter => useful to add to suggestions / search by synonym/Anbieter-Namensverweisung
						if ($tag_type & 64 || $tag_type & 262144) {
							$names[] = array(
								'tag_name' => $tag_name,
								'tag_descr' => $tag_descr,
								'tag_type' => $tag_type,
								'tag_help' => $tag_help,
								'tag_freq' => $tag_freq
							);
						}

						$db2->query("SELECT tag_name, tag_descr, tag_type, tag_help, tag_freq
													FROM x_tags t 
													LEFT JOIN x_tags_syn s ON s.lemma_id=t.tag_id 
													LEFT JOIN x_tags_freq f ON f.tag_id=t.tag_id $portalIdCond
													WHERE s.tag_id=$tag_id $COND_TAGTYPE $portalIdCond");
						while ($db2->next_record()) {
							$names[] = array(
								'tag_name' => $db2->fcs8('tag_name'),
								'tag_descr' => $db2->fcs8('tag_descr'),
								'tag_type' => $db2->fcs8('tag_type'),
								'tag_help' => $db2->fcs8('tag_help'),
								'tag_freq' => $db2->fcs8('tag_freq')
							);
						}
					} {
						// Find Ancestor(s)
						{
							    // 32 = verstecktes Synonym, 256 = Volltext Titel, 512 = Volltext Beschreibung, 2048 = Verwaltungsstichwort, 8192 = Schlagwort nicht verwenden
							    $dontdisplay = array(32, 256, 512, 2048, 8192);
							    
							    // 1. Anhand $tag_name in stichwoerter die stichwort-ID ermitteln
							    $sql = "SELECT id, eigenschaften FROM stichwoerter WHERE stichwort=". $db4->quote($tag_name);
							    $db4->query($sql);
							    
							    if( $db4->next_record() )
							    {
							        $stichwort_id = $db4->fcs8('id');
							        
							        // 2. in stichwoerter_verweis2 Oberbegriffe finden
							        $db4->query("SELECT id, stichwort, primary_id, eigenschaften
														FROM stichwoerter_verweis2
														LEFT JOIN stichwoerter ON id=primary_id
														WHERE attr_id = " . intval($stichwort_id) );
							        
							        while( $db4->next_record() )
							        {
							            $tag_type = $db4->fcs8('eigenschaften');
							            
							            if(!in_array($tag_type, $dontdisplay))
							                $tag_groups[] = $db4->fcs8('stichwort');
							        }
							    }
						}
						$has_man_sug = false;
						{
							// ... simple lemma
							$tag_array = array(	'tag' 		=> $tag_name, 
											'tag_descr' => $tag_descr, 
											'tag_type'	=> $tag_type | $fuzzy, 
											'tag_help'	=> intval($tag_help),
											'tag_freq'	=> $tag_freq	);

							
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
			}
			require_once($_SERVER['DOCUMENT_ROOT'] . '/admin/lib/soundex/x3m_soundex_ger.php');

			// if there are only very few results, try an additional soundex search = has equal word value
			if( sizeof((array) $ret) < $min && $use_soundex )
				$COND = "tag_soundex='".soundex_ger($q_tag_name)."'";
			else
				break; // stop searching with one try if no tag found containing q

		}
	}
	return $ret;
}
