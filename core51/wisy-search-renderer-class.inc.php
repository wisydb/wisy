<?php if( !defined('IN_WISY') ) die('!IN_WISY');

require_once('admin/config/codes.inc.php');

class WISY_SEARCH_RENDERER_CLASS
{
	var $framework;
	var $unsecureOnly = false;
	var $tokens;
	var $title_relevance = -1;
	var $title_relevance_substr = -1;
	var $beschreibung_relevance = -1;
	var $changedquery = '';
	var $classes_changedquery = '';
	
	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
		$this->rows = 20;
	}

	function pageSelLink($baseUrl, $currRowsPerPage, $currPageNumber, $hilite)
	{
		$ret = $hilite? '<strong class="wisy_paginate_pagelink">' : '<a class="wisy_paginate_pagelink" href="' . htmlentities($baseUrl) . intval($currPageNumber*$currRowsPerPage) . '">';
			$ret .= intval($currPageNumber+1);
		$ret .= $hilite? '</strong> ' : '</a> ';
		return $ret;
	}
	
	function pageSelCurrentpage($currOffset, $currRowsPerPage)
	{
		// find out the current page number (the current page number is zero-based)
		return $currPageNumber = intval($currOffset / $currRowsPerPage);
	}
	
	function pageSelMaxpages($totalRows, $currRowsPerPage)
	{
		// find out the max. page page number (also zero-based)
		$maxPageNumber = intval($totalRows / $currRowsPerPage);
		if( intval($totalRows / $currRowsPerPage) == $totalRows / $currRowsPerPage ) {
			$maxPageNumber--;
		}
		return $maxPageNumber;
	}
	
	
	function pageSel($baseUrl, $currRowsPerPage, $currOffset, $totalRows)
	{
		$page_sel_surround = 3;
		$currPageNumber = $this->pageSelCurrentpage($currOffset, $currRowsPerPage);
		$maxPageNumber = $this->pageSelMaxpages($totalRows, $currRowsPerPage);
	
		// find out the first/last page number surrounding the current page (zero-based)
		$firstPageNumber = $currPageNumber-$page_sel_surround;
		if( $firstPageNumber < $page_sel_surround ) {
			$firstPageNumber = 0;
		}
		
		$lastPageNumber = $currPageNumber+$page_sel_surround;
		if( $lastPageNumber > ($maxPageNumber-$page_sel_surround) ) {
			$lastPageNumber = $maxPageNumber;
		}
	
		// get the options string
		$options = '';
		if( $firstPageNumber != 0 ) {
		    $options .= $this->pageSelLink($baseUrl, $currRowsPerPage, 0, 0) . '<span class="ppp">... </span>';
		}
		
		for( $i = $firstPageNumber; $i<=$lastPageNumber; $i++ ) {
			$options .= $this->pageSelLink($baseUrl, $currRowsPerPage, $i, $i==$currPageNumber? 1 : 0);
		}
		
		if( $lastPageNumber != $maxPageNumber ) {
		    $options .= '<span class="ppp">... </span>' . $this->pageSelLink($baseUrl, $currRowsPerPage, $maxPageNumber, 0);
		}
	
		return trim($options);
	}
	
	function renderColumnTitle($title, $sollOrder, $istOrder, $info=0)
	{
	    // #richtext
	    $richtext = (intval(trim($this->framework->iniRead('meta.richtext'))) === 1);
	    $headattribs = ($richtext) ? ' content="'.$title.'"' : '';
	    
	    // Add column title class for use in responsive CSS
	    echo '    <th class="wisyr_'. $this->framework->cleanClassname($title, true) .'" '.$headattribs.'>'; // #richtext
	    
	    if($this->framework->simplified)
	    {
				echo $title;
			}
			else
			{
				if( $sollOrder )
				{
					if( $istOrder{0} == $sollOrder ) 
					{
						$dir = $istOrder{1}=='d'? 'd' /*desc*/ : 'a' /*asc*/;
						$newOrder = $sollOrder . ($dir=='d'? '' : 'd');
						$icon = $dir=='d'? ' &#9660;' /*v*/ : ' &#9650;' /*^*/;
					}
					else
					{
						$newOrder = $sollOrder;
						$icon = '';
					}
				
					echo '<a href="' . htmlspecialchars($this->framework->getUrl('search', array('q'=>$this->framework->getParam('q', ''), 'order'=>$newOrder))) . '" title="Liste nach diesem Kriterium sortieren" class="wisy_orderby">';
				}
			
				echo $title;
			
				if( $sollOrder )
				{			
					echo $icon . '</a>';
				}
			
				if( $info > 0 )
				{
					echo ' <a href="' . htmlspecialchars($this->framework->getHelpUrl($info)) . '" title="Hilfe" class="wisy_help">i</a>';
				}
			}
		echo '</th>' . "\n";
	}
	
	function renderPagination($prevurl, $nexturl, $pagesel, $currRowsPerPage, $currOffset, $totalRows, $extraclass)
	{
		$currentPage = $this->pageSelCurrentpage($currOffset, $currRowsPerPage) + 1;
		$maxPages = $this->pageSelMaxpages($totalRows, $currRowsPerPage) + 1;
		echo ' <span class="wisy_paginate ' . $extraclass . '">';
			echo '<span class="wisy_paginate_seitevon">Seite ' . $currentPage . ' von ' . $maxPages . '</span>';
			echo '<span class="wisy_paginate_text">Gehe zu Seite</span>';
		
			if( $prevurl ) {
				echo " <a class=\"wisy_paginate_prev\" href=\"" . htmlspecialchars($prevurl) . "\">&laquo;</a> ";
			}
	
			echo $pagesel;
	
			if( $nexturl ) {
				echo " <a class=\"wisy_paginate_next\" href=\"" . htmlspecialchars($nexturl) . "\">&raquo;</a>";
			}
		echo '</span>' . "\n";
	}
	
	protected function renderAnbieterCell2(&$db2, $record, $param)
	{
		$currAnbieterId = $record['id'];
		$anbieterName = $record['suchname'];
		$pruefsiegel_seit = $record['pruefsiegel_seit'];
		$anspr_tel = $record['anspr_tel'];

		echo '    <td class="wisy_anbieter wisyr_anbieter" data-title="Anbieter">';
			echo $this->framework->getSeals($db2, array('anbieterId'=>$currAnbieterId, 'seit'=>$pruefsiegel_seit, 'size'=>'small'));
			
			if( $anbieterName )
			{
				$aparam = array('id'=>$currAnbieterId, 'q'=>$param['q']);
				if( $param['promoted'] )
				{
					$aparam['promoted'] = intval($param['kurs_id']);
					echo '<span class="wisy_promoted_prefix">Anzeige von:</span> ';
				}
			
				if( $param['clickableName'] ) echo '<a href="'.$this->framework->getUrl('a', $aparam).'">';
					
				    $kursAnalyzer1 =& createWisyObject('WISY_KURS_ANALYZER_CLASS', $this->framework);
				
					if( $param['addIcon'] ) {
						if( $record['typ'] == 2 ) echo '<span class="wisy_icon_beratungsstelle">Beratungsstelle<span class="dp">:</span></span> ';
					}
					
					echo htmlspecialchars($this->framework->encode_windows_chars( cs8($anbieterName) ));
					
				if( $param['clickableName'] ) echo '</a>';

				if( $param['addPhone'] && $anspr_tel )
				{
				    // $anspr_tel = str_replace(' ', '', $anspr_tel); // macht Aerger, da in den Telefonnummern teilw. Erklaerungen/Preise mitstehen. Auskommentiert am 5.9.2008 (bp)
				    $anspr_tel = str_replace('/', ' / ', $anspr_tel);
				    echo '<span class="wisyr_comma">,</span><span class="wisyr_anbieter_telefon"> ' . htmlspecialchars( cs8($anspr_tel) ) . '</span>';
				}
				
				if( !$param['clickableName'] )  echo '<span class="wisyr_anbieter_profil"> - <a href="'.$this->framework->getUrl('a', $aparam).'">Anbieterprofil...</a></span>';
			}
			else
			{
				echo 'k. A.';
			}
		echo '</td>' . "\n";
		
		return $anbieterName; // #richtext
	}
	
	// display fulltext statistics
	// display subheading: 1:1 in both title & description or one of them oder match of one of the search terms in either text
	function fulltextHead($records, $record, $rows, $query_string) {
	    $volltext_heading = false;
	    
	    if( $this->title_relevance == intval($record['title_relevance']) )
	        ; // dont display subheading if match category doesn't change
	        else {
	            $this->title_relevance = intval($record['title_relevance']);
	            $volltext_heading = true; // match in title found
	        }
	        
	        if( $this->beschreibung_relevance == intval($record['beschreibung_relevance']) )
	            ; // dont display subheading if match category doesn't change
	            else {
	                $this->beschreibung_relevance = intval($record['beschreibung_relevance']);
	                $volltext_heading = true;  // match in description found
	            }
	            
	            $total_rows = $records['meta']['cnt_bothRelevance'] + $records['meta']['cnt_titleRelevance'] + $records['meta']['cnt_beschreibungRelevance'] + $records['meta']['cnt_oneRelevance'];
	            
	            // display statistics and match category jump links, if first row of table of current page & has matches at all
	            if($rows == 1 && $total_rows > 0) {
	                echo '<tr>';
	                echo '<td colspan="7" class="statistics">';
	                $cnt_relevantRows = 1;
	                echo '<b>Vorkommen des Suchbegriffs:</b><br>';
	                
	                
	                
	                $rows_total = 1;
	                foreach($records['meta'] AS $key => $rows) {
	                    
	                    $this->ignored_words = array();
	                    
	                    if( strlen(trim($query_string)) < $record['min_chars'] )  // query_string as such too short
	                        array_push($this->ignored_words, $query_string);
	                        
	                        if(stripos($key, 'cnt_') !== FALSE && stripos($key, 'relevance') !== FALSE && $rows) {
	                            $fullPagi = $this->get_fulltextPagination($rows_total);
	                            
	                            if($key == 'cnt_bothRelevance') {
	                                echo '<span class="stat_row">In <b>Titel und Beschreibung</b> gefunden: <b>'.$rows.'x</b></span>'; $anchor = "#titel_beschreibung";
	                            }
	                            elseif($key == 'cnt_titleRelevance') {
	                                echo '<span class="stat_row"><b>Nur im Titel</b> gefunden: <b>'.$rows.'x</b></span>'; $anchor = "#nur_titel";
	                            }
	                            elseif($key == 'cnt_beschreibungRelevance') {
	                                echo '<span class="stat_row"><b>Nur in der Beschreibung</b> gefunden: <b>'.$rows.'x</b></span>'; $anchor = "#nur_beschreibung";
	                            }
	                            elseif($key == 'cnt_oneRelevance') {
	                                echo '<span class="stat_row">Nur <b>einzelne W&ouml;rter</b> in <b>Titel</b> ODER <b>Beschreibung</b>: <b>'.$rows.'x</b></span>'; $anchor = "#einzelne_woerter";
	                                
	                                foreach( array_map('trim', explode(' ', $query_string)) AS $word) {
	                                    if(strlen($word) < $record['min_chars'])
	                                        array_push($this->ignored_words, rtrim($word, ','));
	                                }
	                                
	                            } // end: cnt_oneRelevance
	                            
	                            
	                            
	                            
	                            if( $total_rows > $this->rows)
	                                echo ' <span class="page_searchresults">('.( ( $this->framework->getParam('offset') != $fullPagi['offset_target']) ? '<a href="search?'.$fullPagi['query_result'].$anchor.'">' . 'ab Seite '.$fullPagi['page'].'</a>' : 'ab Seite '.$fullPagi['page']).')' . '</span>';
	                                
	                                echo '.<br>';
	                                
	                                if(count($this->ignored_words)) {
	                                    echo '<small>Folgende Worte wurden einzeln nicht gesucht (da weniger als '.$record['min_chars'].' Buchstaben): <b>"'.implode(',', $this->ignored_words).'"</b></small> ';
	                                    $ignored_informed = true;
	                                }
	                                
	                        }
	                        $rows_total += $rows;
	                }
	                
	                global $ignoreWords_DE;
	                
	                
	                $ignored_words = "";
	                $count_replacements1 = array();
	                $count_replacements2 = array();
	                $count_replacements3 = array();
	                
	                foreach($ignoreWords_DE AS $ignoreWord) { // ignore hard coded list of (admin/config/codes.inc.php) filler words
	                    $replacement = ' ';
	                    $subject = $record['fulltext_query'];
	                    $limit = -1; // no limit
	                    
	                    $pattern1 = "/ ".$ignoreWord." /i"; // in middle of search string
	                    $fulltext_matchall = preg_replace($pattern1, $replacement, $subject, $limit, $count_replacements1);
	                    
	                    if($count_replacements1) $ignored_words .= '"'.trim($ignoreWord).'", ';
	                    
	                    $pattern2 = "/^".$ignoreWord." /i"; // at beginning of search string
	                    $fulltext_matchall = preg_replace($pattern2, $replacement, $subject, $limit, $count_replacements2);
	                    
	                    if($count_replacements2) $ignored_words .= '"'.trim($ignoreWord).'", ';
	                    
	                    $pattern3 = "/ ".$ignoreWord."\.{0,1}$/i"; // at end of search string
	                    $fulltext_matchall = preg_replace($pattern3, $replacement, $subject, $limit, $count_replacements3);
	                    
	                    if($count_replacements3) $ignored_words .= '"'.trim($ignoreWord).'", ';
	                }
	                
	                
	                if($ignored_words != "" && !$ignored_informed)
	                    echo "<small>(NICHT ber&uuml;cksichtigt: ".trim($ignored_words, ', ').")</small>";
	                    
	                    echo '</td>';
	                    echo '<tr>';
	            }
	            
	            // if match category has changed with this row, display: match category as additional row above
	            if($volltext_heading && ($record['title_relevance'] && $record['beschreibung_relevance']) ) {
	                echo '<tr>';
	                echo '<td colspan="7" class="subheading" id="titel_beschreibung">';
	                echo '<span class="fulltext_query">"'.$record['fulltext_query'].'"</span> 1:1 in <b>Titel</b> UND <b>Beschreibung</b>:';
	                echo '</td>';
	                echo '<tr>';
	            }
	            elseif($volltext_heading && ($record['title_relevance'] && !$record['beschreibung_relevance']) ) {
	                echo '<tr>';
	                echo '<td colspan="7" class="subheading" id="nur_titel">';
	                echo '<span class="fulltext_query">"'.$record['fulltext_query'].'"</span> 1:1 nur im <b>Titel</b>:';
	                echo '</td>';
	                echo '<tr>';
	            }
	            elseif($volltext_heading && (!$record['title_relevance'] && $record['beschreibung_relevance']) ) {
	                echo '<tr>';
	                echo '<td colspan="7" class="subheading" id="nur_beschreibung">';
	                echo '<span class="fulltext_query">"'.$record['fulltext_query'].'"</span> 1:1 nur in der <b>Beschreibung</b>:';
	                echo '</td>';
	                echo '<tr>';
	            }
	            elseif($volltext_heading && ( $record['title_relevance'] == 0 && $record['beschreibung_relevance'] == 0 ) ) {
	                echo '<tr>';
	                echo '<td colspan="7" class="subheading" id="einzelne_woerter">';
	                $words = array_map(
	                    function($word) {
	                        return (!in_array($word, $this->ignored_words) ? '<span class="fulltext_query">'.'"'.$word.'"</span> ODER <span class="fulltext_query">' : '');
	                    }, explode(' ', $record['fulltext_matchall'] )
	                    );
	                $words = implode(' ', $words);
	                echo 'Einzelne Begriffe in <b>Titel</b> ODER <b>Beschreibung</b> gefunden'.(count($this->ignored_words) ? ' <small>- Folgende Worte wurden einzeln nicht gesucht (da weniger als '.$record['min_chars'].' Buchstaben: <b>"'.implode(',', $this->ignored_words).'"</b>)</small>' : '').':<br>';
	                echo substr($words, 0, strrpos($words, ' ODER'));
	                
	                echo '</td>';
	                echo '<tr>';
	            }
	}
	
	// fulltexxt: enclose match in title with span
	function get_fulltextTitleMarker($title, $fulltext_query = array(), $exact = false, $parts = false) {
	    if( $exact && !in_array($fulltext_query['fulltext_query'], $this->ignored_words) ) {
	        return str_ireplace($fulltext_query['fulltext_query'], '<span class="mark">'.ucfirst(strtolower($fulltext_query['fulltext_query'])).'</span>', $title); // " " necessary: searcher only finds actual words not parts of words
	    }
	    elseif( $parts ) {
	        $single_matches = explode(' ', $fulltext_query['fulltext_matchall']);
	        foreach($single_matches AS $match) {
	            if( stripos($title, $match) !== FALSE && !in_array($match, $this->ignored_words) ) {
	                $title = str_ireplace($match, '<span class="mark">'.ucfirst(strtolower($match)).'</span>', $title);
	            }
	        }
	    }
	    
	    return $title;
	}
	
	// fulltext: enclose match in descriptioin excerpt(s) with span.
	// display: additional 5 words pre and post match for context
	function get_fulltextDetails($beschreibung, $fulltext_query = array(), $exact, $parts) {
	    
	    if( $exact && !in_array($fulltext_query['fulltext_query'], $this->ignored_words)) {
	        $sourcestring = str_ireplace($fulltext_query['fulltext_query'], '<span class=\'mark\'>"'.$fulltext_query['fulltext_query'].'"</span>', $beschreibung); // ' ' necessary: searcher only finds actual words not parts of words
	        // preg_match_all('/(?:\S+\s*){0,5}\S*'.$fulltext_query['fulltext_query'].'\S*(?:\s*\S+){0,5}/si',$sourcestring,$matches);
	        preg_match_all('/(?:\S+\s*){0,5}\S*'.preg_quote($fulltext_query['fulltext_query'], '/').'\S*(?:\s*\S+){0,5}/si',$sourcestring,$matches);
	        
	        if($matches[0][0])
	            return "<br><br><small class='cite'>"."[...]&nbsp;".str_replace('"', '', $matches[0][0])."&nbsp;[...]"."</small>";
	    }
	    elseif( $parts ) { // no 1:1 match but at least one of the words must have a match
	        $single_matches = explode(' ', $fulltext_query['fulltext_matchall']);
	        foreach($single_matches AS $match) {
	            $sourcestring = $beschreibung;
	            // preg_match_all('/(?:\S+\s*){0,5}\S*'.' '.$match.' '.'\S*(?:\s*\S+){0,5}/si',$sourcestring,$matches);
	            preg_match_all('/(?:\S+\s*){0,5}\S*'.preg_quote($match, '/').'\S*(?:\s*\S+){0,5}/si',$sourcestring,$matches); // .' ' .' '
	            if($matches[0][0]  && !in_array($matches[0][0], $this->ignored_words) ) {
	                // $marked_match = str_ireplace($match, '<span class=\'mark\'>"'.$match.'"</span>', $matches[0][0]);
	                $marked_match = preg_replace("/".preg_quote($match, '/')."/i", "<span class='mark'>\$0</span>", $matches[0][0]);
	                return "<br><br><small class='cite'>"."[...]&nbsp;".str_replace('"', '', $marked_match)."&nbsp;[...]"."</small>";
	            }
	        } // end: foreach
	    }
	    
	    return "";
	}
	
	// fulltext: calculate page on which new match category starts & return offset-link
	function get_fulltextPagination($total_rows) {
	    $ret = array();
	    $ret['page'] = ceil($total_rows / $this->rows);
	    $server_query = $_GET;
	    $ret['offset_target'] = floor($total_rows / $this->rows )*$this->rows;
	    $server_query['offset'] = $ret['offset_target'];
	    $ret['query_result'] = http_build_query($server_query);
	    return $ret;
	}
	
	function renderKursRecords(&$db, &$records, &$recordsToSkip, $param)
	{
		global $wisyPortalSpalten;

		$loggedInAnbieterId = $this->framework->getEditAnbieterId();

		// build skip hash
		$recordsToSkipHash = array();
		if( is_array($recordsToSkip['records']) )
		{
			reset($recordsToSkip['records']);
			foreach($recordsToSkip['records'] as $i => $record)
			{
			    $recordsToSkipHash[ $record['id'] ] = true;
			}
		}

		// load all latlng values
		$distances = array();
		if( $this->hasDistanceColumn )
		{
			$ids = '';
			reset($records['records']);
			foreach($records['records'] as $i => $record)
			{
			    $ids .= ($ids==''? '' : ', ') . $record['id'];
			}
			
			if ($ids != '' )
			{
				$x1 = $this->baseLng *  71460.0;
				$y1 = $this->baseLat * 111320.0;
				$sql = "SELECT kurs_id, lat, lng FROM x_kurse_latlng WHERE kurs_id IN ($ids)";
				$db->query($sql);
				while( $db->next_record() )
				{
					$kurs_id = intval($db->fcs8('kurs_id'));
					$x2 = (floatval($db->fcs8('lng')) / 1000000) *  71460.0;
					$y2 = (floatval($db->fcs8('lat')) / 1000000) * 111320.0;

					// calculate the distance between the points ($x1/$y1) and ($x2/$y2)
					// d = sqrt( (x1-x2)^2 + (y1-y2)^2 )
					$dx = $x1 - $x2; if( $dx < 0 ) $dx *= -1;
					$dy = $y1 - $y2; if( $dy < 0 ) $dy *= -1;
					$d = sqrt( $dx*$dx + $dy*$dy ); // $d ist nun die Entfernung in Metern ;-)
					
					// remember the smallest distance
					if( !isset($distances[ $kurs_id ]) || $distances[ $kurs_id ] > $d )
					{
						$distances[ $kurs_id ] = $d;
					}
				}
			}
		}

		// go through result
		$durchfClass =& createWisyObject('WISY_DURCHF_CLASS', $this->framework);
		
		$kursAnalyzer =& createWisyObject('WISY_KURS_ANALYZER_CLASS', $this->framework);
		
		$fav_use = $this->framework->iniRead('fav.use', 0);
		
		$rows = 0;
		
		$tag_cloud = array();
		
		reset($records['records']);
		foreach($records['records'] as $i => $record)
		{	
			// get kurs basics
			$currKursId = $record['id'];
			$currAnbieterId = $record['anbieter'];
			$currKursFreigeschaltet = $record['freigeschaltet'];
			$durchfuehrungenIds = $durchfClass->getDurchfuehrungIds($db, $currKursId);

			// record already promoted? if so, skip the normal row
			if( $recordsToSkipHash[ $currKursId ] )
				continue;

			// dump kurs
			$rows ++;
				
			if( $param['promoted'] )
			    $class = ' class="wisy_promoted"';
			else
			    $class = ($rows%2)==0? ' class="wisy_even"' : '';
				        
				        
			// fulltext: ' ' ist explode criteria
			$record['fulltext_matchall'] = trim($record['fulltext_matchall']);
				        
			// fulltext: get statistics, jump-links & sub-heading-row
			if( isset($record['title_relevance']) && isset($record['beschreibung_relevance']) ) // check needed in case both 1:! match relevance == '0'
			    echo $this->fulltextHead($records, $record, $rows, $param['q']);
				            
			// fulltext:
			$fulltext = array();
			$fulltext['match_both'] = intval($this->title_relevance === 1) && intval($this->beschreibung_relevance === 1); // 1:1 match in title and description
			$fulltext['match_title'] = intval($this->title_relevance === 1) && intval($this->beschreibung_relevance === 0); // 1:1 match in title only
			$fulltext['match_descr'] = intval($this->title_relevance === 0) && intval($this->beschreibung_relevance === 1); // 1:1 match in description only
			$fulltext['match_one'] = (isset($record['title_relevance']) && isset($record['beschreibung_relevance']) && $this->title_relevance === 0 && $this->beschreibung_relevance === 0); // no 1:1 match, but one of the search terms found in title or description
				            
			echo "  <tr$class data-title_relevance_substr='".$record['title_relevance_substr']."' data-beschreibung_relevance='".$record['beschreibung_relevance']."' >\n";
				            
			// SPALTE: kurstitel
			$db->query("SELECT id, suchname, pruefsiegel_seit, anspr_tel, typ, freigeschaltet FROM anbieter WHERE id=$currAnbieterId");
			$db->next_record();
			$anbieter_record = $db->Record;
				            
			// continue if Anbieter disabled!
			if($anbieter_record['freigeschaltet'] == 2)
			 continue;
				                
		    echo '    <td class="wisy_kurstitel wisyr_angebot" data-title="Angebot">';
		    
			$aparam = array('id'=>$currKursId, 'q'=>$param['q']);
			if( $param['promoted'] ) {$aparam['promoted'] = $currKursId;}
					
			$aclass = '';
			if( $fav_use ) {
				$aclass = ' class="fav_add" data-favid="'.$currKursId.'"';
			}
					
			if( count($kursAnalyzer->hasKeyword($db, 'kurse', $currKursId, TAG_EINRICHTUNGSORT)) )
			 echo '<a href="/a'.$currAnbieterId.'" class="'.$this->framework->getUrl('k', $aparam).'">';
			else
			 echo '<a href="' .$this->framework->getUrl('k', $aparam). "\"{$aclass}>";
						
			if( $currKursFreigeschaltet == 0 ) { echo '<em>Kurs in Vorbereitung:</em><br />'; }
			if( $currKursFreigeschaltet == 2 ) { echo '<em>Gesperrt:</em><br />'; }
			if( $currKursFreigeschaltet == 3 ) { echo '<em>Abgelaufen:</em><br />'; }
					    
			// $anbieter_record['typ'] == 2 = Beratungsstelle + tag Einrichtungsort => show label Beratung
			if( $anbieter_record['typ'] == 2 && count($kursAnalyzer->hasKeyword($db, 'kurse', $currKursId, TAG_EINRICHTUNGSORT)) ) echo '<span class="wisy_icon_beratungsstelle">Beratung<span class="dp">:</span></span> ';
					    
			$elearning = 806311;
		    if( $this->framework->iniRead('label.elearning', 0) && count($kursAnalyzer->hasKeyword($db, 'kurse', $currKursId, $elearning)) )
			 echo '<span class="wisy_icon_elearning">E-Learning<span class="dp">:</span></span> ';
					        
		    echo $this->getAbschlussLabel($db, $kursAnalyzer, $currKursId);
		    echo $this->getZertifikatLabel($db, $kursAnalyzer, $currKursId);
		    
		    $title = htmlspecialchars($this->framework->encode_windows_chars( cs8($record['titel']) ));
		    
		    // fulltext: mark matches in title
		    $title = $this->get_fulltextTitleMarker($title, array('fulltext_query' => $record['fulltext_query'], 'fulltext_matchall' => $record['fulltext_matchall']) , ($fulltext['match_both'] || $fulltext['match_title']), $fulltext['match_one']);
		    
		    echo $title;
		    
		    // fulltext: show under title: mark matches in description excerpt
		    echo $this->get_fulltextDetails($record['beschreibung'], array('fulltext_query' => $record['fulltext_query'], 'fulltext_matchall' => $record['fulltext_matchall']) , ($fulltext['match_both'] ||$fulltext['match_descr']), $fulltext['match_one']);
		    
		    echo '</a>';
		    if( $loggedInAnbieterId == $currAnbieterId )
			{
				$vollst = $record['vollstaendigkeit'];
				if( $vollst>=1 ) {
					echo " <span class=\"wisy_editvollstcol\" title=\"Vollst&auml;ndigkeit der Kursdaten, bearbeiten Sie den Kurs, um die Vollst&auml;ndigkeit zu erh&ouml;hen\">($vollst% vollst&auml;ndig)</span>";
				}
				echo '<br><span class="wisy_edittoolbar"><a href="'.$this->framework->getUrl('edit', array('action'=>'ek', 'id'=>$currKursId)).'">Bearbeiten</a></span>';
			}
			echo '</td>' . "\n";

			if (($wisyPortalSpalten & 1) > 0)
			{
			     // SPALTE: anbieter
				 // #richtext
				 $anbieterName = $this->renderAnbieterCell2($db, $anbieter_record, array('q'=>$param['q'], 'addPhone'=>true, 'promoted'=>$param['promoted'], 'kurs_id'=>$currKursId));
			}
				
			// SPALTEN: durchfuehrung
			$addText = '';
			if( count((array) $durchfuehrungenIds) > 1 )
			{
				$addText = ' <span class="wisyr_termin_weitere"><a href="' .$this->framework->getUrl('k', $aparam). '">';
				    $temp = count((array) $durchfuehrungenIds) - 1;
				    $addText .= $temp==1? "$temp<span> weiterer...</span>" : "$temp<span> weitere...</span>";
				$addText .= '</a></span>';
			}
				
			$tags = $this->framework->loadStichwoerter($db, 'kurse', $currKursId);
			array_push($tag_cloud, $tags);
			$durchfClass->formatDurchfuehrung($db, $currKursId, $durchfuehrungenIds, 0, 0, 1, $addText, array('record'=>$record, 'stichwoerter'=>$tags));
				
			// SPALTE: Entfernung
			if( $this->hasDistanceColumn )
			{
				$cell = '<td class="wisyr_entfernung" data-title="Entfernung">';
				if( isset($distances[$currKursId]) )
				{
					$meters = $distances[$currKursId];
					if( $meters > 1500 )
					{
						// 1 km, 2 km etc.
						$km = intval(($meters+500)/1000); if( $km < 1 ) $km = 1;
						$cell .= '~' . $km . ' km';
					}
					else if( $meters > 550 )
					{
						// 100 m, 200 m etc.
						$hundreds = intval(($meters+50)/100); if( $hundreds < 1 ) $hundreds = 1;
						$cell .= '~' . $hundreds . '00 m';
					}
					else
					{
						$cell .= '&lt;500 m';
					}
				}
				else
				{
					$cell .= 'k. A.';
				}
				$cell .= '</td>';
				echo $cell;
			
			}
				
			echo '  </tr>' . "\n";
		}
		
		return $tag_cloud;
	}
	
	function getAbschlussLabel($db, $kursAnalyzer, $currKursId) {
	    $abschlussName = "";
	    $isAbschlussDescendant = false;
	    $abschlussTags = $kursAnalyzer->loadKeywordsAbschluss($db, 'kurse', $currKursId);
	    $isAbschluss = count($abschlussTags);
	    
	    if(!$isAbschluss) {
	        $abschlussAncestorTags = $kursAnalyzer->loadSearchableKeywordsAbschluss($db, 'kurse', $currKursId);
	        
	        if(count($abschlussAncestorTags)) {
	            $isAbschluss = true;
	            $abschlussName = "Abschlussseigenschaft stammt ab von: ";
	            foreach($abschlussAncestorTags AS $abschlussAncestor) {
	                $abschlussName .= "'".$abschlussAncestor['tag_name']."',";
	            }
	        }
	    } else {
	        $abschlussName = "Abschlusseigenschaft durch: ";
	        foreach($abschlussTags AS $abschlussTag) {
	            $abschlussName .= "'".$abschlussTag['stichwort']."',";
	        }
	    }
	    
	    if($this->framework->iniRead('label.abschluss', 0) && $isAbschluss)
	        return '<span class="wisy_icon_abschluss" title="'.trim($abschlussName, ',').'">Abschluss<span class="dp">:</span></span> ';
	        else
	            return '';
	}
	
	function getZertifikatLabel($db, $kursAnalyzer, $currKursId) {
	    $zertifikatName = "";
	    $isZertifikatDescendant = false;
	    $zertifikatTags = $kursAnalyzer->loadKeywordsZertifikat($db, 'kurse', $currKursId);
	    $isZertifikat = count($zertifikatTags);
	    
	    if(!$isZertifikat) {
	        $zertifikatAncestorTags = $kursAnalyzer->loadSearchableKeywordsZertifikat($db, 'kurse', $currKursId);
	        
	        if(count($zertifikatAncestorTags)) {
	            $isZertifikat = true;
	            $zertifikatName = "Zertifikatseigenschaft stammt ab von: ";
	            foreach($zertifikatAncestorTags AS $zertifikatAncestor) {
	                $zertifikatName .= "'".$zertifikatAncestor['tag_name']."',";
	            }
	        }
	    } else {
	        $zertifikatName = "Zertifikatseigenschaft durch: ";
	        foreach($zertifikatTags AS $zertifikatTag) {
	            $zertifikatName .= "'".$zertifikatTag['stichwort']."',";
	        }
	    }
	    
	    if($this->framework->iniRead('label.zertifikat', 0) && $isZertifikat)
	        return '<span class="wisy_icon_zertifikat" title="'.trim($zertifikatName, ',').'">Zertifikat<span class="dp">:</span></span> ';
	        else
	            return '';
	}
	
	function formatItem($tag_name, $tag_descr, $tag_type, $tag_help, $tag_freq, $addparam=0)
	{
		if( !is_array($addparam) ) $addparam = array();
		
		/* see also (***) in the JavaScript part*/
		$row_class   = 'ac_normal';
		$row_prefix  = '';
		$row_preposition = '';
		$row_postfix = '';
		
		/* base type */
		     if( $tag_type &   1 )	{ $row_class = "ac_abschluss";		      $row_preposition = ' zum '; $row_postfix = '<b>Abschluss</b>'; }
		else if( $tag_type &   2 )	{ $row_class = "ac_foerderung";		      $row_preposition = ' zur '; $row_postfix = 'F&ouml;rderung'; }
		else if( $tag_type &   4 )	{ $row_class = "ac_qualitaetszertifikat"; $row_preposition = ' zum '; $row_postfix = 'Qualit&auml;tszertifikat'; }
		else if( $tag_type &   8 )	{ $row_class = "ac_zielgruppe";		      $row_preposition = ' zur '; $row_postfix = 'Zielgruppe'; }
		else if( $tag_type &  16 )	{ $row_class = "ac_abschlussart";		  $row_preposition = ' zur '; $row_postfix = 'Abschlussart'; }
		else if( $tag_type & 128 )	{ $row_class = "ac_thema";		 		  $row_preposition = ' zum '; $row_postfix = 'Thema'; }
		else if( $tag_type & 256 )	{ $row_class = "ac_anbieter";		     
											  if( $tag_type &  0x10000 )    { $row_preposition = ' zum '; $row_postfix = 'Trainer'; }
										 else if( $tag_type &  0x20000 )    { $row_preposition = ' zur '; $row_postfix = 'Beratungsstelle'; }
										 else if( $tag_type & 0x400000 )    { $row_preposition = ' zum '; $row_postfix = 'Anbieterverweis'; }
										 else							    { $row_preposition = ' zum '; $row_postfix = 'Anbieter'; }
								    }
		else if( $tag_type & 512 )	{ $row_class = "ac_ort";                  $row_preposition = ' zum '; $row_postfix = 'Ort'; }
		else if( $tag_type & 1024 )	{ $row_class = "ac_sonstigesmerkmal";     $row_preposition = ' zum '; $row_postfix = 'sonstigen Merkmal'; }
		else if( $tag_type & 32768 ){ $row_class = "ac_unterrichtsart";       $row_preposition = ' zur '; $row_postfix = 'Unterrichtsart'; }
		else if( $tag_type & 65536 ){ $row_class = "ac_zertifikat";           $row_preposition = ' zum '; $row_postfix = 'Zertifikat'; }
	
		if( $addparam['hidetagtypestr'] ) {
			$row_preposition = '';
			$row_postfix = '';
		}

		/* frequency, end base type */ 
		if( $tag_freq > 0 )
		{
			$row_postfix = ($tag_freq==1? '1 Angebot' : "$tag_freq Angebote") . $row_preposition . $row_postfix;
		}
		
		if( $tag_descr ) 
		{
			$row_postfix = $tag_descr . ', ' . $row_postfix;
		}
		
		if( $row_postfix != '' )
		{
		    $row_postfix = ' <span class="ac_tag_type">(' . htmlentities($row_postfix) . ')</span> ';
		}
	
		/* additional flags */
		if( $tag_type & 0x10000000 )
		{
			$row_prefix = '&nbsp; &nbsp; &nbsp; &nbsp; &#8594; ';
			$row_class .= " ac_indent";
		}	
		else if( $tag_type & 0x20000000 )
		{
			$row_prefix = ''; //05.06.2017 war: 'Meinten Sie: '
		}
		else
		{
			$row_prefix = ''; //13.05.2014 was: 'Suche nach '
		}
		
		
		/* help link */
		if( $tag_help > 0 )
		{
			$row_postfix .=
			 " <a class=\"wisy_help\" href=\"" . $this->framework->getUrl('g', array('id'=>$tag_help, 'q'=>$tag_name)) . "\" title=\"Ratgeber\">&nbsp;i&nbsp;</a>";
		}
		
		return '<span class="' .$row_class. '">' .
		  		$row_prefix . ' <a href="' . $this->framework->getUrl('search', array('q'=>$addparam['qprefix'].$tag_name)) . ( $this->framework->qtrigger ? '&qtrigger='.$this->framework->qtrigger : '').($this->framework->force ? '&force='.$this->framework->force : '') . '">' . htmlspecialchars($tag_name) . '</a> ' . $row_postfix .
		  		'</span>';
	}
	
	function formatItem_v2($tag_name, $tag_descr, $tag_type, $tag_help, $tag_freq, $tag_anbieter_id=false, $tag_groups='', $tr_class='', $queryString='')
	{
		/* see also (***) in the JavaScript part*/
		$row_class   = 'ac_normal';
		$row_type  = 'Lernziel';
		$row_count = '';
		$row_count_prefix = ($tag_freq == 1) ? ' Angebote zum' : ' Angebote zum';
		$row_info = '';
		$row_prefix = '';
		$row_postfix = '';
		$row_groups = '';
	
		/* base type */
		     if( $tag_type &   1 ) { $row_class = "ac_abschluss";		     $row_type = 'Abschluss'; }
		else if( $tag_type &   2 ) { $row_class = "ac_foerderung";		     $row_type = 'F&ouml;rderung'; $row_count_prefix = ($tag_freq == 1) ? ' Angebot zur' : ' Angebote zur'; }
		else if( $tag_type &   4 ) { $row_class = "ac_qualitaetszertifikat"; $row_type = 'Qualit&auml;tsmerkmal'; }
		else if( $tag_type &   8 ) { $row_class = "ac_zielgruppe";		     $row_type = 'Zielgruppe'; $row_count_prefix = ($tag_freq == 1) ? ' Angebot zur' : ' Angebote zur'; }
		else if( $tag_type &  16 ) { $row_class = "ac_abschlussart";		 $row_type = 'Abschlussart'; $row_count_prefix = ($tag_freq == 1) ? ' Angebot zur' : ' Angebote zur'; }
		else if( $tag_type &  64 ) { $row_class = "ac_synonym";				 $row_type = 'Verweis'; }
		else if( $tag_type & 128 ) { $row_class = "ac_thema";		 		 $row_type = 'Thema'; }
		else if( $tag_type & 256 ) { $row_class = "ac_anbieter";		     
									      if( $tag_type &  0x20000 ) { $row_type = 'Beratungsstelle'; $row_count_prefix = ($tag_freq == 1) ? ' Angebot von der' : ' Angebote von der'; }
									 else if( $tag_type & 0x400000 ) { $row_type = 'Tr&auml;gerverweis'; }
									 else							 { $row_type = 'Tr&auml;ger'; $row_count_prefix = ($tag_freq == 1) ? ' Angebot vom' : ' Angebote vom'; }
								   }
		else if( $tag_type & 512 ) { $row_class = "ac_ort";                  $row_type = 'Kursort'; $row_count_prefix = ($tag_freq == 1) ? ' Angebot am' : ' Angebote am'; }
		else if( $tag_type & 1024) { $row_class = "ac_merkmal";			 	 $row_type = 'Kursmerkmal'; }
		else if( $tag_type & 32768){ $row_class = "ac_unterrichtsart";		 $row_type = 'Unterrichtsart'; $row_count_prefix = ($tag_freq == 1) ? ' Angebot zur' : ' Angebote zur'; }
		else if( $tag_type & 65536){ $row_class = "ac_zertifikat";           $row_type = 'Zertifikat'; }

		if( $tag_descr ) $row_postfix .= ' <span class="ac_tag_type">('. htmlentities($tag_descr) .')</span>';
		
	
		if( $tag_freq > 0 ) {
			$row_count = $tag_freq;
			if($row_count_prefix == '') {
				$row_count .= ($tag_freq == 1) ? ' Angebot' : ' Angebote';
			} else {
				$row_count .= $row_count_prefix;
			}
		}

		/* additional flags */
		if( $tag_type & 0x10000000 )
		{
			$row_prefix = '<span class="wisyr_indent">&#8594;</span> ';
			$row_class .= " ac_indent";
		}	
		else if( $tag_type & 0x20000000 )
		{
			$row_prefix = 'Meinten Sie: ';
		}
		else
		{
			$row_prefix = ''; //13.05.2014 was: 'Suche nach '
		}
	
		if( $tag_groups ) $row_groups = implode('<br />', $tag_groups);
	
		if( $tag_help )
		{
			$row_info = '<a href="' . $this->framework->getUrl('g', array('id'=>$tag_help, 'q'=>$tag_name)) . '">Zeige Erkl&auml;rung</a>';
		} else if( $tag_type & 256 && $tag_anbieter_id ) {
			$row_info = '<a href="' . $this->framework->getUrl('a', array('id'=>$tag_anbieter_id)) . '">Zeige Tr&auml;gerprofil</a>';
		}
	
		$row_class = $row_class . ' ' . $tr_class;
		
		// highlight search string
		$tag_name = htmlspecialchars($tag_name);
		if($queryString != '') {
			//$tag_name_highlighted = str_ireplace($queryString, "<strong>$queryString</strong>", $tag_name);
			$tag_name_highlighted = preg_replace("/".preg_quote($queryString, "/")."/i", "<em>$0</em>", $tag_name);
		} else {
			$tag_name_highlighted = $tag_name;
		}
	
		return '<tr class="' .$row_class. '">' .
					'<td class="wisyr_tag_name" data-title="Rechercheziele">'. $row_prefix .'<a href="' . $this->framework->getUrl('search', array('q'=>$tag_name)) . '">' . $tag_name_highlighted . '</a>'. $row_postfix .'<span class="tag_count">'. $row_count .'</span></td>' . 
					'<td class="wisyr_tag_type" data-title="Kategorie">'. $row_type .'</td>' .
					'<td class="wisyr_tag_groups" data-title="Oberbegriffe">'. $row_groups .'</td>' . 
					'<td class="wisyr_tag_info" data-title="Zusatzinfo">'. $row_info . '</td>' .
			   '</tr>';
	}
	
	function renderTagliste($queryString)
	{
		$tagsuggestor =& createWisyObject('WISY_TAGSUGGESTOR_CLASS', $this->framework);
		$suggestions = $tagsuggestor->suggestTags($queryString);

		if( count((array) $suggestions) ) 
		{
		    if($this->framework->iniRead('search.suggest.v2') == 1)
		    {
		        echo '<div class="wisyr_list_header"><h1 class="wisyr_rechercheziele">Suchvorschl&auml;ge zum Stichwort &quot;' . htmlspecialchars(trim($this->framework->QS)) . '&quot;</h1></div>';
		        echo '<table class="wisy_list wisy_tagtable">';
		        echo '	<thead>';
		        echo '		<tr>'.
		  		        '<th class="wisyr_titel"><span class="title">Rechercheziele</span> <span class="tag_count">Angebote dazu</span></th>'.
		  		        '<th class="wisyr_art">Kategorie</th>'.
		  		        '<th class="wisyr_gruppe">Oberbegriffe</th>'.
		  		        '<th class="wisyr_info">Zusatzinfo</th>'.
		  		        '</tr>';
		        echo '	</thead>';
		        echo '	<tbody>';
		        for( $i = 0; $i < count((array) $suggestions); $i++ )
		        {
		            $tr_class = ($i%2) ? 'ac_even' : 'ac_odd';
		            echo $this->formatItem_v2($suggestions[$i]['tag'], $suggestions[$i]['tag_descr'], $suggestions[$i]['tag_type'], intval($suggestions[$i]['tag_help']), intval($suggestions[$i]['tag_freq']), $suggestions[$i]['tag_anbieter_id'], $suggestions[$i]['tag_groups'], $tr_class, $queryString);
		        }
		        echo '	</tbody>';
		        echo '</table>';
		    }
		    else
		    {
		        echo '<h1 class="wisyr_rechercheziele">Ihre Suche nach &quot;' . htmlspecialchars(trim($this->framework->QS)) . '&quot; ergab keine Treffer</h1>';
		        echo '<ul>';
		        for( $i = 0; $i < count((array) $suggestions); $i++ )
		        {
		            echo '<li>' . $this->formatItem($suggestions[$i]['tag'], $suggestions[$i]['tag_descr'], $suggestions[$i]['tag_type'], intval($suggestions[$i]['tag_help']), intval($suggestions[$i]['tag_freq'])) . '</li>';
		        }
		        echo '</ul>';
		    }
		}
		else
		{
			echo 'Keine Treffer.';
		}
	}
	
	function getSortOrderByTag($orderBy, $queryString, $validOrders) {
	    
	    $searcher_tmp =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
	    $tokens = $searcher_tmp->tokenize($queryString)['cond'];
	    $tokens_arr = array();
	    foreach($tokens AS $token) {
	        array_push($tokens_arr, $token['value']);
	    }
	    $tokens_lc = array_map("strtolower", $tokens_arr);
	    $array_specialsort_str = array();
	    
	    foreach($validOrders AS $validOder) {
	        
	        if( ($specialSort = trim($this->framework->iniRead('kurse.sortierung.'.$validOder, ''))) != "") {
	            $array_specialsort[$validOder] = explode(',', $specialSort);
	            $array_specialsort_str[$validOder] = array();
	            
	            if(is_array($array_specialsort[$validOder]) && count($array_specialsort[$validOder]) ) {
	                $db_tmp = new DB_Admin();
	                $query = "SELECT stichwort FROM stichwoerter WHERE id IN (".implode(",", $array_specialsort[$validOder]).") ";
	                $db_tmp->query($query);
	                while( $db_tmp->next_record() ) {
	                    array_push($array_specialsort_str[$validOder], $db_tmp->fcs8('stichwort'));
	                }
	                $array_specialsort_str[$validOder] = array_map("strtolower", $array_specialsort_str[$validOder]);
	                $common_elements = array_intersect($array_specialsort_str[$validOder], $tokens_lc);
	                
	                if(count($common_elements) > 0) {
	                    $orderBy = $validOder;
	                    $this->framework->specialSortOrder = $orderBy;
	                }
	            }
	        }
	    } /* End: foreach valid order */
	    
	    return $orderBy;
	}
	
	function renderKursliste(&$searcher, $queryString, $offset, $showFilters=true, $baseurl='search', $hlevel=1)
	{
	    global $wisyPortalSpalten;
	    $this->emptymsg_output = false;
	    
	    $richtext = (intval(trim($this->framework->iniRead('meta.richtext'))) === 1); // #richtext
	    
	    $validOrders = array('a', 'ad', 't', 'td', 'b', 'bd', 'd', 'dd', 'p', 'pd', 'o', 'od', 'creat', 'creatd', 'rand');
	    $portal_order = $this->framework->iniRead('kurse.sortierung', false);
	    $orderBy = $this->framework->order;
	    
	    $orderBy = $this->getSortOrderByTag($orderBy, $queryString, $validOrders);
	    
	    if( in_array($orderBy, $validOrders) )
	        ;
	    elseif(in_array($portal_order, $validOrders))
	    $orderBy = $portal_order;
	    else
	        $orderBy = 'b';
	        
	        $info = $searcher->getInfo();
	        $info['volltext_select'] = $searcher->getFulltextSelect();
	        
	        // echo "<!-- changed: ".$info['changed_query'].", sugg:".print_r($info['suggestions'], true)." -->";
	        // there should not be a fulltext - suggestion if already fulltext-searched
	        // however there is. Without $this->emptymsg_output empty searchresult would be outbput twice
	        // example: /search?qs=Fu%DFballspiegel&q=Fu%DFball [...] trigger
	        if( !$this->emptymsg_output && ( $info['changed_query'] || count((array) $info['suggestions']) ) )
	        {
	            $this->render_emptysearchresult_message($info, $false, $hlevel);
	            $this->emptymsg_output = true;
	        }
	        
	        $sqlCount = $searcher->getKurseCount();
	        
	        if( $sqlCount )
	        {
	            $db = new DB_Admin();
	            
	            // create get prev / next URLs
	            $prevurl = $offset==0? '' : $this->framework->getUrl($baseurl, array('q'=>$queryString, 'offset'=>$offset-$this->rows, 'order'=>(($orderBy != 'b') ? $orderBy : 'b')));
	            $nexturl = ($offset+$this->rows<$sqlCount)? $this->framework->getUrl($baseurl, array('q'=>$queryString, 'offset'=>$offset+$this->rows, 'order'=>(($orderBy != 'b') ? $orderBy : 'b'))) : '';
	            
	            if( $prevurl || $nexturl )
	            {
	                $param = array('q'=>$queryString);
	                $param['order'] = ($orderBy != 'b') ? $orderBy : 'b';
	                
	                $param['offset'] = '';
	                $pagesel = $this->pageSel($this->framework->getUrl($baseurl, $param), $this->rows, $offset, $sqlCount);
	            }
	            else
	            {
	                $pagesel = '';
	            }
	            
	            // render head
	            echo '<div class="wisyr_list_header">';
	            echo '<div class="wisyr_listnav">';
	            echo '<span class="active tab_kurse">Angebote</span>';
	            echo '<a href="' . $baseurl . '?q=' . ($this->changedquery ? urlencode($this->changedquery) : urlencode($queryString)) . '%2C+Zeige:Anbieter' . ( $this->framework->qtrigger ? '&qtrigger='.$this->framework->qtrigger : '') . ( $this->framework->force ? '&force='.$this->framework->force : '') . '" class="tab_anbieter">Anbieter</a>';
	            echo '</div>';
	            echo '<div class="wisyr_filternav';
	            if($this->framework->simplified && $this->framework->filterer->getActiveFiltersCount() > 0) echo ' wisyr_filters_active';
	            echo '">';
	            
	            if( $queryString == '' ) {
	                echo '<span class="wisyr_aktuelle_angebote">Aktuelle Angebote</span>';
	            }
	            else {
	                echo '<span class="wisyr_angebote_zum_suchauftrag">';
	                /* ! actually makes sense: but how? Because of richtext additional output above?
	                 if($richtext)
	                 echo "&nbsp;"; // notwendig
	                 else */
	                 echo $sqlCount==1? '<span class="wisyr_anzahl_angebote">1 Angebot</span> zum Suchauftrag ' : '<span class="wisyr_anzahl_angebote">' . $sqlCount . ' Angebote</span> zum Suchauftrag'.($info['changed_query'] && stripos($info['changed_query'], 'volltext:') !== FALSE ? ' - Volltextsuche:' : '');
	                 
	                 if($info['changed_query'] && stripos($info['changed_query'], 'volltext:') !== FALSE)
	                     $this->framework->QS = 'volltext:'.trim($this->framework->QS);
	                 
	                 if($this->framework->simplified)
	                 {
	                         if(trim($this->framework->QS) != '')
	                     {
	                         echo ' <span class="wisyr_angebote_suchauftrag">"' . htmlentities(html_entity_decode($this->framework->QS)) . '"</span>'; // htmlspecialchars
	                     }
	                 }
	                 else
	                 {
	                     echo ' <span class="wisyr_angebote_suchauftrag">"' . htmlentities((trim(html_entity_decode($queryString), ', '))) . '"</span>';	// htmlspecialchars
	                 }
	                 
	                 $double_tags = $searcher->getDoubleTags();
	                 $tags_heap = (array) $searcher->getTagHeap();
	                 if(is_array($double_tags) && count($double_tags) > 0) {
	                     echo '<br><br><span class="redundant_tags">Hinweis: Die Suche wurde abge&auml;ndert, weil redundante Begriffe vorkamen:<br>';
	                     foreach($double_tags AS $double_tag) {
	                         echo $double_tag.'<br>';
	                     }
	                     echo "</span>";
	                     
	                     global $wisyPortalId;
	                     if (($key = array_search(".portal".$wisyPortalId, $tags_heap)) !== false) { unset($tags_heap[$key]); } // remove portal identifying tag
	                     $tags_heap = array_map(function($tag){ $tag = strtolower($tag); $tag = ucfirst($tag); return $tag; }, $tags_heap); // format string
	                     $tag_heap_str = implode(" &bull; ", $tags_heap);
	                     $tag_heap_str = preg_replace('/&bull$/', '', $tag_heap_str);
	                     
	                     echo '<span class="redundant_tags" id="actual_searchtags">Ber&uuml;cksichtigte Suchbegriffe: '.$tag_heap_str.'</span>';
	                 }
	                 
	                 if( $assumedLocation = $searcher->getAssumedLocation() ) {
	                     echo '<br><br><span class="changed_ort">Ort verstanden als: '.$assumedLocation.'<br>';
	                     echo "</span>";
	                 }
	                 
	                 echo '</span>';
	            }
	            
	            // prepare "number of results" string
	            $number_of_results_string  = '<h' . ($hlevel+2) . ' class="wisyr_list_anzahl_angebote">';
	            $number_of_results_string .= $sqlCount==1? '<span class="wisyr_anzahl_angebote">1 Angebot</span> zum Suchauftrag ' : '<span class="wisyr_anzahl_angebote">' . $sqlCount . ' Angebote</span> zum Suchauftrag ';
	            $number_of_results_string .= '</h' . ($hlevel+2) . '>';
	            
	            // Show filter / advanced search
	            // pass "number of results" string to filterRenderer so that it is output ahead of the "filter_order" select
	            if( $showFilters )
	            {
	                $no_f = (is_object($this->framework->filterer) ? $this->framework->filterer->getActiveFiltersCount() : 0);
	                $DEFAULT_FILTERLINK_HTML= '<a href="filter?q=__Q_URLENCODE__'. ( $this->framework->qtrigger ? '&qtrigger='.$this->framework->qtrigger : '') . ( $this->framework->force ? '&force='.$this->framework->force : '')  .'" id="wisy_filterlink">'
	                    . ( $no_f > 0 ? $no_f . ' aktive'.($no_f === 1 ? 'n' : '') . ' Suchfilter anpassen...' : 'Suchfilter anpassen...') . '</a>';
	                echo $this->framework->replacePlaceholders($this->framework->iniRead('searcharea.filterlink', $DEFAULT_FILTERLINK_HTML));
	                    
	                    
	                    
	                $filterRenderer =& createWisyObject('WISY_FILTER_RENDERER_CLASS', $this->framework);
	                $filterRenderer->renderForm($queryString, $searcher->getKurseRecords(0, 0, $orderBy), $hlevel, $number_of_results_string);
	            } else {
	                // Show number of results
	                echo $number_of_results_string;
	            }
	            
	            
	            
	            if( $pagesel )
	            {
	                $this->renderPagination($prevurl, $nexturl, $pagesel, $this->rows, $offset, $sqlCount, 'wisyr_paginate_top');
	            }
	            
	            echo '</div>';
	            echo '</div>';
	            
	            flush();
	            
	            /* $aggregateOffer = ($richtext) ? 'itemprop="offers" itemscope itemtype="http://schema.org/AggregateOffer"': '';
	             echo '<div '.$aggregateOffer.'>'; */
	            
	            // render table start
	            echo "\n".'<table class="wisy_list wisyr_kursliste">' . "\n";
	            
	            // render column titles
	            echo '  <thead><tr>' . "\n";
	            $colspan = 0;
	            $this->hasDistanceColumn = false;
	            {	$this->renderColumnTitle('Angebot',			't', 	$orderBy,	1333);			$colspan++; }
	            if (($wisyPortalSpalten & 1)  > 0) {	$this->renderColumnTitle('Anbieter',		'a', 	$orderBy,	311); 			$colspan++; }
	            if (($wisyPortalSpalten & 2)  > 0) {	$this->renderColumnTitle('Termin',			'b', 	$orderBy,	308);			$colspan++; }
	            if (($wisyPortalSpalten & 4)  > 0) {	$this->renderColumnTitle('Dauer',			'd',	$orderBy,	0);				$colspan++; }
	            if (($wisyPortalSpalten & 8)  > 0) {	$this->renderColumnTitle('Art',				'',		$orderBy,	1967);			$colspan++; }
	            if (($wisyPortalSpalten & 16) > 0) {	$this->renderColumnTitle('Preis',			'p',	$orderBy,	309);			$colspan++; }
	            if (($wisyPortalSpalten & 32) > 0) {	$this->renderColumnTitle('Ort',				'o', 	$orderBy,	1936);			$colspan++; }
	            if (($wisyPortalSpalten & 64) > 0) {	$this->renderColumnTitle('Ang.-Nr.',		'', 	$orderBy,	0);				$colspan++; }
	            /* if (($wisyPortalSpalten & 128)> 0) { 	$this->renderColumnTitle('Bemerkungen',				'', 	$orderBy,	0);				$colspan++; } */ /* ohne $details=true sinnlos */
	            /* if (($wisyPortalSpalten & 256)> 0) { 	$this->renderColumnTitle('BU',				'', 	$orderBy,	0);				$colspan++; } */
	            if (($wisyPortalSpalten & 512)> 0
	                && $info['lat'] && $info['lng']) {  $this->renderColumnTitle('Entfernung',		'', 	$orderBy,	0);				$colspan++; $this->hasDistanceColumn = true; $this->baseLat = $info['lat']; $this->baseLng = $info['lng'];  }
	                echo '  </tr></thead>' . "\n";
	                
	                // render promoted records
	                $records2 = array();
	                if( !$info['changed_query']
	                    && $offset == 0
	                    && $this->framework->iniRead('useredit.promote', 0)!=0 )
	                {
	                    global $wisyPortalId;
	                    $searcher2 =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
	                    
	                    $queryString_db = $this->framework->mysql_escape_mimic($queryString);
	                    
	                    if($queryString_db != "")
	                        $searcher2->prepare($queryString_db . ', schaufenster:' . $wisyPortalId);
	                        
	                        if( $searcher2->ok() )
	                        {
	                            $promoteCnt = $searcher2->getKurseCount();
	                            if( $promoteCnt > 0 )
	                            {
	                                $promoteRows = 3;
	                                if( $promoteRows > $promoteCnt ) $promoteRows = $promoteCnt;
	                                
	                                $promoteOffset = mt_rand(0, $promoteCnt-$promoteRows);
	                                
	                                $records2 = $searcher2->getKurseRecords($promoteOffset, $promoteRows, 'rand');
	                                $notingToSkip = array();
	                                
	                                // render promoted head
	                                echo '<tr class="wisy_promoted_head"><td colspan="'.$colspan.'">';
	                                echo 'Schaufenster Weiterbildung ';
	                                echo '<a href="' .$this->framework->getHelpUrl(3368). '" class="wisy_help" title="Hilfe">i</a>';
	                                echo '</td></tr>';
	                                
	                                // render promoted records
	                                $this->renderKursRecords($db, $records2, $nothingToSkip, array('q'=>$queryString, 'promoted'=>true));
	                                
	                                // log the rendered records
	                                if( $queryString != '' ) // do not decreease / log on the homepage, see E-Mails/04.11.2010
	                                {
	                                    $promoter =& createWisyObject('WISY_PROMOTE_CLASS', $this->framework);
	                                    $promoter->logPromotedRecordViews($records2, $queryString);
	                                }
	                            }
	                        }
	                        
	                }
	                
	                // render other records
	                $records = $searcher->getKurseRecords($offset, $this->rows, $orderBy);
	                $tags_heap = $this->renderKursRecords($db, $records, $records2 /*recordsToSkip*/, array('q'=>$queryString));
	                
	                // main table end
	                echo '</table>' . "\n\n";
	                
	                /* // #richtext
    				if($richtext) {
    					// sort($durchfClass->preise);
    					// echo '<meta itemprop="lowprice" content="'.$this->framework->preisUS($durchfClass->preise[0]).'">';
    				// 	echo '<meta itemprop="highprice" content="'.$this->framework->preisUS($durchfClass->preise[count((array) $durchfClass->preise)-1]).'">';
    					
    					echo '<meta itemprop="priceCurrency" content="EUR">';
    				// 	echo '<meta itemprop="offerCount" content="'.count((array) $durchfClass->preise).'">';
    					echo '<meta itemprop="url" content="http://'.$_SERVER['SERVER_NAME'].str_replace('&amp;', '&', htmlspecialchars( $_SERVER['REQUEST_URI'] )).'">';
    					echo '<meta itemprop="eligibleRegion" content="DE-RP">';
    					echo '<span itemprop="eligibleCustomerType" itemscope itemtype="https://schema.org/BusinessEntityType">';
    					echo '<meta itemprop="additionalType" content="http://purl.org/goodrelations/v1#Enduser">';
    					echo '</span>';
    					echo '</div>'; // Ende AggregateOffer
    				} */
    			
    			    flush();
	                
	                if( $pagesel )
	                {
	                    echo '<div class="wisyr_list_footer clearfix">';
	                    // if( $this->framework->iniRead('rsslink', 0) )
	                     // echo '<div class="wisyr_rss_link_wrapper">' . $this->framework->getRSSLink() . '</div>';
	                    $this->renderPagination($prevurl, $nexturl, $pagesel, $this->rows, $offset, $sqlCount, 'wisyr_paginate_bottom');
	                    echo '</div>';
	                }
	        }
	        else
	        {
	            
	            if( !$this->emptymsg_output && ( count((array) $info['suggestions']) == 0 ) )
	            {
	                $this->render_emptysearchresult_message($info, false, $hlevel);
	                $this->emptymsg_output = true;
	            }
	            
	            echo '<div class="wisyr_list_footer clearfix">';
	            // if( $this->framework->iniRead('rsslink', 0) )
	             // echo '<div class="wisyr_rss_link_wrapper">' . $this->framework->getRSSLink() . '</div>';
	            echo '</div>';
	        }
	        
	        // dont display word clouds while online editing
	        if( $this->framework->iniRead('sw_cloud.suche_anzeige', 0) && !$this->framework->editSessionStarted ) {
		    global $wisyPortalId;
		    
		    $cacheKey = "sw_cloud_p".$wisyPortalId."_s".$queryString;
		    $this->dbCache		=& createWisyObject('WISY_CACHE_CLASS', $this->framework, array('table'=>'x_cache_tagcloud', 'itemLifetimeSeconds'=>60*60*24));
		    
		    $tag_cloud = "";
		    
		    if( ($temp=$this->dbCache->lookup($cacheKey))!='' )
		    {
		        $tag_cloud = $temp." <!-- tag cloud from cache -->";
		    }
		    elseif($tags_heap)
		    {
		        $filtersw = array_map("trim", explode(",", $this->framework->iniRead('sw_cloud.filtertyp', "32, 2048, 8192")));
		        $distinct_tags = array();
		        $tag_cloud = '<div id="sw_cloud"><h3>'.$this->framework->iniRead('sw_cloud.bezeichnung_suche', 'Suchbegriffe').'</h3> ';
		        //$tag_cloud .= '<h4>Suchbegriffe</h4>';
		        $tag_done = array();
		        
		        foreach($tags_heap AS $tags) {
		            for($i = 0; $i < count($tags); $i++)
		            {
		                $tag = $tags[$i];
		                
		                if(in_array($tag['id'], $tag_done))
		                    continue;
		                    
		                    $weight = 0;
		                    
		                    if($this->framework->iniRead('sw_cloud.suche_gewichten', 1)) {
		                        $tag_freq = $this->framework->getTagFreq($db, $tag['stichwort']);
		                        $weight = (floor($tag_freq/50) > 15) ? 15 : floor($tag_freq/50);
		                    }
		                    
		                    if($tag['eigenschaften'] != $filtersw && $tag_freq > 0); {
		                        if($this->framework->iniRead('sw_cloud.suche_stichwoerter', 1))
		                            $tag_cloud .= '<span class="sw_raw typ_'.$tag['eigenschaften'].'" data-weight="'.$weight.'"><a href="/search?q='.urlencode( cs8($tag['stichwort']) ).( $this->framework->qtrigger ? '&qtrigger='.$this->framework->qtrigger : '').( $this->framework->force ? '&force='.$this->framework->force : '').'">'.cs8($tag['stichwort']).'</a></span>, ';
		                            
		                        if($this->framework->iniRead('sw_cloud.suche_synonyme', 0))
		                            $tag_cloud .= $this->framework->writeDerivedTags($this->framework->loadDerivedTags($db, $tag['id'], $distinct_tags, "Synonyme"), $filtersw, "Synonym", cs8($tag['stichwort']));
		                                
		                        if($this->framework->iniRead('sw_cloud.suche_oberbegriffe', 0))
		                            $tag_cloud .= $this->framework->writeDerivedTags($this->framework->loadDerivedTags($db, $tag['id'], $distinct_tags, "Oberbegriffe"), $filtersw, "Oberbegriff", cs8($tag['stichwort']));
		                                    
		                        if($this->framework->iniRead('sw_cloud.suche_unterbegriffe', 0))
		                            $tag_cloud .= $this->framework->writeDerivedTags($this->framework->loadDerivedTags($db, $tag['id'], $distinct_tags, "Unterbegriffe"), $filtersw, "Unterbegriff", cs8($tag['stichwort']));
		                    }
		                    
		                    array_push($tag_done, $tag['id']);
		                    
		            } // end: for
		        }
		        
		        $tag_cloud = trim($tag_cloud, ", ");
		        $tag_cloud .= '</div>';
		        
		        $this->dbCache->insert($cacheKey, $tag_cloud);
		    }
		    echo $tag_cloud;
		} // end: tag cloud
			
		if( !$nexturl && $_SERVER['HTTPS']!='on' && !$this->framework->editSessionStarted ) {

			echo '	<div id="iwwb"><!-- BANNER IWWB START -->
				<script>
			        var defaultZIP="PLZ";
			        var defaultCity="Ort";
			        var defaultKeywords = "Suchw&ouml;rter eingeben";

			        function IWWBonFocusTextField(field,defaultValue){
			                if (field.value==defaultValue) field.value="";
			        }
			        function IWWBonBlurTextField(field,defaultValue){
			                if (field.value=="") field.value=defaultValue;
			        }
			        function IWWBsearch(button) {
			            if (button.form.feldinhalt1.value == defaultKeywords) {
			                        alert("Bitte geben Sie Ihre Suchw366rter ein!");
			                } else {
			                        if ((typeof button.form.feldinhalt2=="object") && button.form.feldinhalt2.value == defaultZIP) button.form.feldinhalt2.value="";
			                        if ((typeof button.form.feldinhalt3=="object") && button.form.feldinhalt3.value == defaultCity) button.form.feldinhalt3.value="";

			                        button.form.submit();
			                        if ((typeof button.form.feldinhalt2=="object") && button.form.feldinhalt2.value == "") button.form.feldinhalt2.value=defaultZIP;
			                        if ((typeof button.form.feldinhalt3=="object") && button.form.feldinhalt3.value == "") button.form.feldinhalt3.value=defaultCity;
			                }
			        }
			</script>

			<form method="post" action="https://www.iwwb.de/suchergebnis.php" target="IWWB">
			<input type="hidden" name="external" value="true">
  <input type="hidden" name="method" value="iso">
  <input type="hidden" name="feldname1" id="feldname1" value="Freitext" />
  <input type="hidden" name="feldname2" id="feldname2" value="PLZOrt" />
  <input type="hidden" name="feldname3" id="feldname3" value="PLZOrt" />
  <input type="hidden" name="feldname7" id="feldname7" value="datum1" />
  <input type="hidden" name="feldinhalt7" id="feldinhalt7" value="morgen" />

			<table id="iwwb_table">
				<tr>
				<td><label for="iwwb_suchfeld">Bundesweite Suche im InfoWeb Weiterbildung</label></td>
				<td>
					<input name="feldinhalt1" class="feldinhalt1" id="iwwb_suchfeld" type="text" value="' .  $queryString . '" onfocus="IWWBonFocusTextField(this,defaultKeywords)" onblur="IWWBonBlurTextField(this,defaultKeywords)">
					<input name="search" type="button"  value="Suche starten" onClick="IWWBsearch(this)">
				</td>
				<td>
					<a href="https://www.iwwb.de" target="_blank" rel="noopener noreferrer">
						<img src="https://www.iwwb.de/web/images/iwwb.gif" alt="Logo des InfoWeb Weiterbildung">
					</a>&nbsp;
				</td>
				</tr>
				</table>
			</form>
			</div>
			';
		
		}
	}
	
	function renderAnbieterliste(&$searcher, $queryString, $offset)
	{
	    $anbieterRenderer =& createWisyObject('WISY_ANBIETER_RENDERER_CLASS', $this->framework);
	    
	    $validOrders = array('a', 'ad', 's', 'sd', 'p', 'pd', 'o', 'od', 'h', 'hd', 'e', 'ed', 't', 'td', 'creat', 'creatd');
	    $orderBy = $this->framework->order; if( !in_array($orderBy, $validOrders) ) $orderBy = 'a';
	    
	    $db2 = new DB_Admin();
	    
	    $sqlCount = $searcher->getAnbieterCount();
	    
	    if( $sqlCount )
	    {
	        // create get prev / next URLs
	        $prevurl = $offset==0? '' : $this->framework->getUrl('search', array('q'=>$queryString, 'offset'=>$offset-$this->rows));
	        $nexturl = ($offset+$this->rows<$sqlCount)? $this->framework->getUrl('search', array('q'=>$queryString, 'offset'=>$offset+$this->rows)) : '';
	        if( $prevurl || $nexturl )
	        {
	            $param = array('q'=>$queryString);
	            $param['order'] = ($orderBy != 'b') ? $orderBy : 'b';
	            $param['offset'] = '';
	            $pagesel = $this->pageSel($this->framework->getUrl('search', $param), $this->rows, $offset, $sqlCount);
	        }
	        else
	        {
	            $pagesel = '';
	        }
	        
	        // render head
	        echo '<div class="wisyr_list_header">';
	        echo '<div class="wisyr_listnav">';
	        $link_angebote = trim(urlencode(str_replace(array(',,', ', ,'), array(',', ','), str_replace('Zeige:Anbieter', '', $queryString))));
	        if($link_angebote)
	            echo '<a href="search?q=' . $link_angebote . ( $this->framework->qtrigger ? '&qtrigger='.$this->framework->qtrigger : '') . ( $this->framework->force ? '&force='.$this->framework->force : '') . '" class="tab_kurse">Angebote</a>';
	            
	            echo '<span class="active tab_anbieter">Anbieter</span>';
	            echo '</div>';
	            echo '<span class="wisyr_anbieter_zum_suchauftrag">';
	            echo '<span class="zurAngeboteSuche">&larr; Hier geht\'s <a href="/">zur Angebote-Suche</a></span>';
	            echo '<span class="wisyr_anzahl_anbieter">' . $sqlCount . ' Anbieter</span> zum Suchauftrag';
	            echo '</span>';
	            
	            // Show filter / advanced search
	            // pass "number of results" string to filterRenderer so that it is output ahead of the "filter_order" select
	            if( $this->framework->iniRead('search.anbieter.filter', false) )
	            {
	                $no_f = $this->framework->filterer->getActiveFiltersCount();
	                $DEFAULT_FILTERLINK_HTML= '<a href="filter?q=__Q_URLENCODE__'. ( $this->framework->qtrigger ? '&qtrigger='.$this->framework->qtrigger : '') . ( $this->framework->force ? '&force='.$this->framework->force : '')  .'" id="wisy_filterlink">'
	                    . ( $no_f > 0 ? $no_f . ' aktive'.($no_f === 1 ? 'n' : '') . ' Suchfilter anpassen...' : 'Suchfilter anpassen...') . '</a>';
	                    
	                    echo $this->framework->replacePlaceholders($this->framework->iniRead('searcharea.filterlink', $DEFAULT_FILTERLINK_HTML));
	                    
	                    $filterRenderer =& createWisyObject('WISY_FILTER_RENDERER_CLASS', $this->framework);
	                    $filterRenderer->renderForm($queryString, $searcher->getKurseRecords(0, 0, $orderBy), $hlevel, $number_of_results_string);
	            } else {
	                // Show number of results
	                echo $number_of_results_string;
	            }
	            
	            if( $pagesel )
	            {
	                $this->renderPagination($prevurl, $nexturl, $pagesel, $this->rows, $offset, $sqlCount, 'wisyr_paginate_top');
	            }
			echo '</div>' . "\n";
			flush();
			
			// render column titles
			echo "\n".'<table class="wisy_list wisyr_anbieterliste">' . "\n";
			echo '  <thead><tr>' . "\n";
			    $this->renderColumnTitle('Anbieter',	'a', 	$orderBy,	311);
			
			    if($this->framework->iniRead('spalten.anbieter.portrait', false) || $this->framework->showcol == 'x')
			        $this->renderColumnTitle('Portr&auml;t und Angebote',			'x',	$orderBy,	0);
			    
			    $this->renderColumnTitle('Stra&szlig;e',		's', 	$orderBy,	0);
				$this->renderColumnTitle('PLZ',			'p',	$orderBy,	0);
				$this->renderColumnTitle('Ort',			'o',	$orderBy,	0);
				$this->renderColumnTitle('Web',			'h',	$orderBy,	0);
				$this->renderColumnTitle('E-Mail',		'e',	$orderBy,	0);
				$this->renderColumnTitle('Telefon',		't',	$orderBy,	0);
			echo '  </tr></thead>' . "\n";

			// render records
			$records = $searcher->getAnbieterRecords($offset, $this->rows, $orderBy);
			$rows = 0;
			
			$tag_cloud = array();
			$wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this->framework);
			
			foreach($records['records'] as $i => $record)
			{
			    if($sqlCount == 1 && $this->framework->iniRead('anbietersuche.redirect', false)  && strpos( $this->framework->getParam('q', '') , 'volltext') === FALSE  && strpos( $this->framework->getParam('qs', ''), 'volltext') === FALSE) {
			        ?>
						<script>
							/* redirect to Anbieter if only 1 in search result */
							window.location.href = '/a<?php echo $record['id']."?qs=".urlencode( $this->framework->getParam('qs', '') )."&q=".urlencode( $this->framework->getParam('q', '') )."&qf=".urlencode( $this->framework->getParam('qf', '') )."&anbieterRedirect=1". ( $this->framework->qtrigger ? '&qtrigger='.$this->framework->check_validTrigger( $this->framework->qtrigger )  : '') . ( $this->framework->force ? '&force='.intval($this->framework->force) : ''); ?>';
						</script>
						<?php
						exit;
				}
				
				$rows++;
				$class = ($rows%2)==0? ' class="wisy_even"' : '';
				
				echo "  <tr$class>\n";
				$this->renderAnbieterCell2($db2, $record, array('q'=>$queryString, 'addPhone'=>false, 'clickableName'=>true, 'addIcon'=>true));
				if($this->framework->iniRead('spalten.anbieter.portrait', false) || $this->framework->showcol == 'x') {
				    echo '<td class="wisyr_portrait" data-title="Portrait">';
				    $portrait = $record['firmenportraet'];
				    echo str_replace(array('<b>', '</b>', '<p>', '</p>'), '', trim($wiki2html->run($this->framework->encode_windows_chars(mb_substr($portrait, 0, 100))))).(strlen($portrait) > 100 ? '...' : '' )."<br><a href='/a".$record['id']."?qs=".urlencode( $this->framework->getParam('qs') )."&q=".urlencode( $this->framework->getParam('q') )."&qf=".urlencode( $this->framework->getParam('qf') ) . ( $this->framework->qtrigger ? '&qtrigger='.$this->framework->qtrigger : '') . ( $this->framework->force ? '&force='.$this->framework->force : '')."'>Mehr Infos...</a>";
				    echo ' </td>';
				}
				echo '<td class="wisyr_strasse" data-title="Stra&szlig;e">';
				echo htmlspecialchars( cs8($record['strasse']) );
				echo ' </td>';
				echo '<td class="wisyr_plz" data-title="PLZ">';
				echo htmlspecialchars( cs8($record['plz']) );
				echo ' </td>';
				echo '<td class="wisyr_ort" data-title="Ort">';
				echo str_replace(' ', '&nbsp;', htmlspecialchars( cs8($record['ort'])) );
					echo ' </td>';
					echo '<td class="wisyr_homepage" data-title="Homepage">';
						$link = $record['homepage'];
						if( $link != '' )
						{
							if( substr($link, 0, 4) != 'http' )
								$link = 'http:/' . '/' . $link;
							echo '<a href="'.$link.'" target="_blank" rel="noopener noreferrer">Web</a>';
						}
					echo ' </td>';
					echo '<td class="wisyr_email" data-title="E-Mail">';
						$link = $record['anspr_email'];
						if( $link != '' )
						    echo '<a href="' . $anbieterRenderer->createMailtoLink($link) . '" target="_blank" rel="noopener noreferrer">E-Mail</a>';
						    echo ' </td>';
					echo '<td class="wisyr_telefon" data-title="Telefon">';
					echo '<a href="tel:' . urlencode( cs8($record['anspr_tel']) ) . '">' . htmlspecialchars( cs8($record['anspr_tel']) ) . '</a>';
					echo ' </td>';
				echo '  </tr>' . "\n";
			}

			// main table end
			echo '</table>' . "\n\n";
			flush();

			// render tail
			if( $pagesel )
			{
				echo '<div class="wisyr_list_footer clearfix">';
				    // if( $this->framework->iniRead('rsslink', 0) )
					 // echo '<div class="wisyr_rss_link_wrapper">' . $this->framework->getRSSLink() . '</div>';
					$this->renderPagination($prevurl, $nexturl, $pagesel, $this->rows, $offset, $sqlCount, 'wisyr_paginate_bottom');
				echo '</div>';
			}
		}
		else /* if( sqlCount ) */
		{
			$this->render_emptysearchresult_message();
		}
	}
	
	function render_emptysearchresult_message($info = false, $error = false, $hlevel=1) {
	    
	    $this->changedquery = $info['changed_query'];
	    $this->classes_changedquery = (($info['changed_query']) ? "changed_query" : "").' '.($info['changed_cnt'] ? 'has_changedresults' : 'no_changedresults');
	    echo '<div class="wisy_suggestions noresults '.$this->classes_changedquery.'">';
	    
	    echo '<span class="wisyr_angebote_zum_suchauftrag"><span class="wisyr_anzahl_angebote">0 Angebote</span> zum eingegebenen Suchauftrag';
	    if(trim($this->framework->QS) != '')
	    {
	        echo ' &quot;' . htmlspecialchars($this->framework->QS) . '&quot;</span></span>';
	    }
	    else
	    {
	        echo '</span></span>';
	    }
	    
	    echo '<div class="" id="noresults_msg">'.$this->framework->iniRead('search.noresults.msg', "").'</div>';
	    
	    if(isset($error['help']))
	        echo "<span class='searcherror_help'>".$error['help']."</span>";
	        
	        echo '<div class="wisy_suggestions_inner '.(($info['changed_query']) ? "changed_query" : "").'">';
	        
	        if($info['changed_query'])
	            echo '<b>Hinweis:</b><br>Der Suchauftrag wurde abge&auml;ndert in: <i><a href="'.$this->framework->getUrl('search', array('q'=>$info['changed_query'])).'">'.htmlspecialchars(cs8($info['changed_query'])).'</a></i>';
	            
	            echo '<div class="suggestions">';
	            
	            // Change notice depending on wether filter selected
	            if(count($this->framework->tokensQF) == 0)
	            {
	                // Empty search wihtout active filters
	                if( count((array) $info['suggestions']) && !(count((array) $info['suggestions']) === 1 && strpos($info['suggestions'][0]['tag'], 'volltext:') !== FALSE))
	                {
	                    echo '<h3>Suchvorschl&auml;ge</h3>';
	                    echo '<ul>';
	                    for( $i = 0; $i < count((array) $info['suggestions']); $i++ )
	                    {
	                        $link = $this->formatItem($info['suggestions'][$i]['tag'], $info['suggestions'][$i]['tag_descr'], $info['suggestions'][$i]['tag_type'], intval($info['suggestions'][$i]['tag_help']), intval($suggestions[$i]['tag_freq']));
	                        
	                        if( strpos($link, 'volltext:') === false ) // allmost impossible to
	                            echo '<li>' . $link . '</li>';
	                        else
	                            echo '<li>' . str_replace('?q=', '?force=1&q=', $link) . '</li>';
	                    }
	                    echo '</ul>';
	                }
	                
	                if(stripos($this->framework->QS, 'zeige:anbieter') === FALSE) { // Don't show Anbieter-Search option if already Anbieter-Search
	                    echo '<h3>Anbietersuche</h3>';
	                    echo '<p>Falls Sie auf der Suche nach einem bestimmten Kursanbieter sind, nutzen Sie bitte unser Anbieterverzeichnis.</p>';
	                    echo '<a href="/search?qs=' . htmlspecialchars($this->framework->QS) . '%2C+Zeige%3AAnbieter">Eine Anbietersuche nach &quot;' . htmlspecialchars(trim($this->framework->QS)) . '&quot; ausf&uuml;hren</a><br><br>';
	                } else {
	                    
	                    echo '<h3>Angebotesuche</h3>';
	                    echo '<p>Falls es sich bei Ihrem Suchbegriff nicht um den Namen eines Kursanbieters handelte k&ouml;nnen Sie es auch mit einer Angebote-Suche versuchen:</p>';
	                    echo '<a href="/search?qs=' . htmlspecialchars(trim(str_ireplace('zeige:anbieter', '', $this->framework->QS), ',')) . '">Eine Angebotesuche nach &quot;' . htmlspecialchars(trim(trim(str_ireplace('zeige:anbieter', '', $this->framework->QS)), ',')) . '&quot; ausf&uuml;hren</a><br><br>';
	                }
	                
	                global $ignoreWords_DE;
	                $qs_fulltextReady = $this->framework->replaceWords($ignoreWords_DE, $this->framework->QS);
	                
	                if($info['changed_query'] && intval($this->framework->iniRead('intellisearch.fulltext', 0)) === 2 || $qs_fulltextReady == "")
	                    ; // don't display fultext link if automatic fulltext search via intellisearch == 2 has already let to 0 results
	                    else {
	                        echo '<h3>Volltextsuche</h3>';
	                        echo '<p>Das Ergebnis der Volltextsuche enth&auml;lt alle Angebote, die den Suchbegriff oder den Wortteil in der Kursbeschreibung enthalten.</p>';
	                        echo '<a href="/search?q=volltext:' . htmlspecialchars($qs_fulltextReady) . '&force=1">Eine Volltextsuche nach &quot;' . htmlspecialchars($qs_fulltextReady) . '&quot; ausf&uuml;hren</a>';
	                    }
	                    
	                    echo '<h3>M&ouml;glicherweise helfen auch Ver&auml;nderungen an Ihrem Suchbegriff:</h3>';
	                    echo '<ul>';
	                    echo '<li>Pr&uuml;fen Sie Ihren Suchbegriff auf Rechtschreibfehler</li>';
	                    echo '<li>Nutzen Sie die Suchvorschl&auml;ge, die w&auml;hrend der Eingabe unter dem Eingabefeld angezeigt werden</li>';
	                    echo '<li>Suchen Sie nach &auml;hnlichen Stichw&ouml;rtern oder Kursthemen</li>';
	                    echo '</ul>';
	            }
	            else
	            {
	                
	                if( $this->framework->getParam('filter_zeige', '') == "Anbieter" ) {
	                    // Empty search with active filters
	                    echo '<h3>Leider keinen Anbieter mit diesem Namen gefunden!</h3><br>';
	                    echo '<ul>';
	                    echo '<li>Passen Sie Ihre Suche bitte an und achten Sie ggf. auf die Vorschl&auml;ge bzw. w&auml;hlen einen aus.</li>';
	                    echo '<li>Oder suchen Sie nach Angeboten? Dann bitte hier <a href="/">zur Angebote-Suche...</a></li>';
	                    echo '</ul>';
	                } else {
	                    // Empty search with active filters
	                    echo '<h3>M&ouml;glicherweise helfen Ver&auml;nderungen an Ihren Filtereinstellungen:</h3>';
	                    echo '<ul>';
	                    echo '<li>&Auml;ndern oder entfernen Sie einzelne Filter, um mehr Angebote f&uuml;r Ihren Suchauftrag zu erhalten</li>';
	                    echo '<li>Suchen Sie nach &auml;hnlichen Stichw&ouml;rtern oder Kursthemen</li>';
	                    echo '</ul>';
	                    
	                    // Output filter navigation even with empty search result
	                    echo '<div class="wisyr_list_header">';
	                    echo '<div class="wisyr_filternav';
	                    if($this->framework->simplified && $this->framework->filterer->getActiveFiltersCount() > 0) echo ' wisyr_filters_active';
	                    echo '">';
	                    
	                    // Show filter / advanced search
	                    $no_f = $this->framework->filterer->getActiveFiltersCount();
	                    $DEFAULT_FILTERLINK_HTML= '<a href="filter?q=__Q_URLENCODE__'. ( $this->framework->qtrigger ? '&qtrigger='.$this->framework->qtrigger : '') . ( $this->framework->force ? '&force='.$this->framework->force : '')  .'" id="wisy_filterlink">'
	                                    . ( $no_f > 0 ? $no_f . ' aktive'.($no_f === 1 ? 'n' : '') . ' Suchfilter anpassen...' : 'Suchfilter anpassen...') . '</a>';
	                                    
	                                    echo $this->framework->replacePlaceholders($this->framework->iniRead('searcharea.filterlink', $DEFAULT_FILTERLINK_HTML));
	                                    
	                                    $filterRenderer =& createWisyObject('WISY_FILTER_RENDERER_CLASS', $this->framework);
	                                    $filterRenderer->renderForm($this->framework->getParam('q', ''), array());
	                                    echo '</div>';
	                                    echo '</div>';
	                }
	                
	                
	            }
	            
	            echo "</div>"; // end: suggesstions
	            
	            // Error
	            if(is_array($error) && count($error))
	            {
	                switch($error['id'])
	                {
	                    case 'bad_location':
	                    case 'inaccurate_location':
	                    case 'bad_km':
	                    case 'km_without_bei':
	                        echo '<h2>Fehler bei Ortssuche</h2>';
	                        echo '<ul>';
	                        echo '<li>&Uuml;berpr&uuml;fen Sie Ihre Ortsangabe und nutzen Sie die Suchvorschl&auml;ge bei der Ortseingabe</li>';
	                        echo '<li>&Uuml;berpr&uuml;fen Sie die gew&auml;hlte Umkreis-Einstellung</li>';
	                        echo '</ul>';
	                        
	                        break;
	                }
	            }
	            
	            
	            echo '<p><br /></p>';
	            echo '</div>';
	            echo '</div>';
	}
	
	function render()
	{
	    if(trim($this->framework->iniRead('disable.suche', false)))
	        $this->framework->error404();
	    
		// get parameters
		// --------------------------------------------------------------------
		
		if($this->framework->simplified) {
			$queryString = $this->framework->Q;	
		} else {
		    $queryString = $this->framework->getParam('q', '');
		}
		
		// We need original chars for searches. Check Filter-class constructTokens function for details. Also: https://www.php.net/manual/de/function.htmlspecialchars.php
		$queryString = str_replace(array("&amp;", "&quot;", "&#039;", "&apos;", "&lt;", "&gt;"), array("&", '"', "'", "'", "<", ">"), $queryString);
		
		$redirect = false;
	
		if( $this->framework->iniRead('searcharea.radiussearch', 0) )
		{
			// add "bei" and "km" to the parameter "q" - this is needed as we forward only one paramter, eg. to subsequent pages
			$bei = trim($this->framework->getParam('bei', ''));
			if( $bei != '' ) {
				$bei = strtr($bei, array(', '=>'/', ','=>'/')); // convert the comma to slashes as commas are used to separate fields
				$queryString = trim($queryString, ', ');
				$queryString .= ($queryString!=''? ', ' : '') . 'bei:' . $bei;
			
				$km = intval($this->framework->getParam('km', ''));
				if( $km > 0 ) {
					$queryString .= ($queryString!=''? ', ' : '') . 'km:' . $km;
				}
			
				$redirect = true;
			}
		}
		
		// We need original chars for searches. Check Filter-class constructTokens function for details. Also: https://www.php.net/manual/de/function.htmlspecialchars.php
		$queryString = str_replace(array("&amp;", "&quot;", "&#039;", "&apos;", "&lt;", "&gt;"), array("&", '"', "'", "'", "<", ">"), $queryString); 
  	
		// Redirect if $queryString has changed
		if($redirect && $this->framework->getParam('r', 0) == 1) {
			// TODO
		}
		if($redirect && $this->framework->getParam('r', 0) != 1)
		{
		    header('Location: search?q=' . urlencode($queryString) . '&qs=' . urlencode(htmlspecialchars($this->framework->QS)) . '&qf=' . urlencode(htmlspecialchars($this->framework->QF)) . '&r=1'. (isset($_GET['qtrigger']) ? '&qtrigger='.$_GET['qtrigger'] : '') . (isset($_GET['force']) ? '&force='.$_GET['force'] : ''));
		    exit();
		}
		
		$offset = intval( $this->framework->getParam('offset') ); if( $offset < 0 ) $offset = 0;
		$title = trim($queryString, ', ');
		
		// page / ajax start
		// --------------------------------------------------------------------
		
		if( intval( $this->framework->getParam('ajax') ) )
		{
		    header('Content-type: text/html; charset=utf-8');
		}
		else
		{
			echo $this->framework->getPrologue(array(
												'title'		=>	$title,
												'bodyClass'	=>	'wisyp_search',
											));
			echo $this->framework->getSearchField();
			flush();
		}

		// result out
		// --------------------------------------------------------------------
		
		
		// Print
		// different titles according to sort order:
		$orderby = strtolower( strval( $this->framework->getParam('filter_order') ) );
		
		if($orderby != "") {
		    
		    $validOrders = array();
		    
		    if(strpos(strtolower(strval($this->framework->getParam('q'))), 'zeige:anbieter') !== FALSE) {
		        $validOrders = array('a' => 'Anbieter',
		            'ad' => 'Anbieter umgekehrt alphabetisch',
		            's' => 'Strasse',
		            'sd' => 'Strasse umgekehrt alphabetisch',
		            'p' => 'Postleitzahlen',
		            'pd' => 'Postleitzahlen absteigend',
		            'o' => 'Ort',
		            'od' => 'Ort umgekehrt alphabetisch',
		            'h' => 'Homepage',
		            'hd' => 'Homepage umgekehrt alphabetisch',
		            'e' => 'Kontakt - E-Mail',
		            'ed' => 'Kontakt - E-Mail umgekehrt alphabetisch',
		            't' => 'Telefonnummern',
		            'td' => 'Telefonnummern absteigend',
		            'creat' => 'Erstellungsdatum von alt nach neu',
		            'creatd' => 'Erstellungsdatum von neu nach alt');
		    } else {
		        $validOrders = array('a' => 'Anbieter',
		            'ad' => 'Anbieter umgekehrt alphabetisch',
		            't' => 'Angebot',
		            'td' => 'Angebot umgekehrt alphabetisch',
		            'b' => 'Termin',
		            'bd' => 'Termin absteigend',
		            'd' => 'Dauer',
		            'dd' => 'Dauer von lang nach kurz',
		            'p' => 'Preis',
		            'pd' => 'Preis absteigend',
		            'o' => 'Ort',
		            'od' => 'Ort umgekehrt alphabetisch',
		            'creat' => 'Erstellungsdatum von alt nach neu',
		            'creatd' => 'Erstellungsdatum von neu nach alt',
		            'rand' => 'Zufallsprinzip');
		    }
		    
		    if(array_key_exists($orderby, $validOrders)) {
		        $sort = " - sortiert nach ".$validOrders[$orderby];
		    } else {
		        $sort = " - sortiert nach '".$orderby."'"; // ok: htmlentities applied before output
		    }
		    
		}
		$q_qs = $this->framework->getParam('q').",".$this->framework->getParam('qs');
		$q_str = str_replace("Seite", "<br>Seite", $this->framework->getTitleString($q_qs));
		$print_title = '<h1>' .strtoupper($q_str). ($sort ? $sort : "") .'</h1>'.$_SERVER["SERVER_NAME"];
		// htmlentities not necessary for print or anti-xss but good for further rendering of page if search contains problematic chars
		echo "\n".'<div class="printonly search_title" style="display: none;">'.htmlentities(strip_tags($print_title)).'</div>'."\n"; 
		
		// Suche und Ergebnisliste auf Startseite optional abschalten
		if(!(intval(trim($this->framework->iniRead('search.startseite.disable'))) && $this->framework->getPageType() == "startseite")) {
		    $searcher =& createWisyObject('WISY_INTELLISEARCH_CLASS', $this->framework);
		    
		    $queryString_db = $this->framework->mysql_escape_mimic($queryString);
		        
		   if($queryString_db != "")
		      $searcher->prepare($queryString_db);
		            
		            
		            
		  echo $this->framework->replacePlaceholders( $this->framework->iniRead('spalten.above', '') );
		            
		  echo '<div id="wisy_resultarea" class="' .$this->framework->getAllowFeedbackClass(). '">';
		
		        if( $this->framework->getParam('show') == 'tags' )
				{
					$this->renderTagliste($queryString);
				}
				else if( $searcher->ok() )
				{
					$info = $searcher->getInfo();
					if( $info['show'] == 'kurse' )
					{
						$this->renderKursliste($searcher, $queryString, $offset);
					}
					else
					{
						$this->renderAnbieterliste($searcher, $queryString, $offset);
					}
				}
				else
				{
					$info  = $searcher->getInfo();
					$error = $info['error'];

					$this->render_emptysearchresult_message($info, $info['error']);
				}

				if( $this->framework->editSessionStarted )
				{
					$loggedInAnbieterId = $this->framework->getEditAnbieterId();
				
					$editor =& createWisyObject('WISY_EDIT_RENDERER_CLASS', $this->framework);
					$adminAnbieterUserIds = $editor->getAdminAnbieterUserIds();
				
					// get a list of all offers that are not "gesperrt"
					$temp = '0';
					$titles = array();
					$db = new DB_Admin;
					$db->query("SELECT id, titel FROM kurse WHERE anbieter=$loggedInAnbieterId AND user_created IN (".implode(',',$adminAnbieterUserIds).") AND freigeschaltet!=2;");
					while( $db->next_record() )
					{ 
						$currId = intval($db->fcs8('id')); $titles[ $currId ] = $db->fcs8('titel'); $temp .= ', ' . $currId;
					}
				
					// compare the 'offers that are not "gesperrt"' against the ones that are in the search index
					$liveIds = array();
					$db->query("SELECT kurs_id FROM x_kurse WHERE kurs_id IN($temp)");
					while( $db->next_record() )
					{
						$liveIds[ $db->fcs8('kurs_id') ] = 1;
					}
				
					echo '<br><div id="kurse_invorbereitung">';
					
					// show 'offers that are not "gesperrt"' which are _not_ in the search index (eg. just created offers) below the normal search result
					echo '<span class="wisy_edittoolbar" title="Um einen neuen Kurs hinzuzuf&uuml;gen, klicken Sie oben auf &quot;Neuer Kurs&quot;">Kurse in Vorbereitung:</span><br><br>';
					$out = 0;
					reset( $titles );
					$cnt_statuszero = 0;
					
					if(count($titles))
					    echo "<ul>";
					
					foreach($titles as $currId => $currTitel)
					{
					    $cnt_statuszero++;
					    if( !$liveIds[ $currId ] )
					    {
					        echo $out? '' : ''; // &ndash;
					        echo '<li><a href="k'.$currId.'" class="in_vorbereitung '.($cnt_statuszero % 2 == 0 ? 'wisy_even' : 'wisy_odd').'">' . htmlspecialchars($currTitel) . '</a></li>';
					        
					        $out++;
					    }
					}
					
					if(count($titles))
					    echo "</ul>";
					
					if( $out == 0 ) echo '<i title="Um einen neuen Kurs hinzuzuf&uuml;gen, klicken Sie oben auf &quot;Neuer Kurs&quot;">keine</i>';
					//echo ' &ndash; <a href="edit?action=ek&amp;id=0">Neuer Kurs...</a>';
					echo '</div> <!-- Ende: Kurse in Vorbereitung -->';
				}
		
			echo '</div>';
		}
		
		echo $this->framework->replacePlaceholders( $this->framework->iniRead('spalten.below', '') );
		
		// page / ajax end
		// --------------------------------------------------------------------
		
		if( intval( $this->framework->getParam('ajax') ) )
		{
			;
		}
		else
		{
			echo $this->framework->getEpilogue();
		}
	}
};
