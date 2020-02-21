<?php if( !defined('IN_WISY') ) die('!IN_WISY');


class WISY_INTELLISEARCH_CLASS
{
	// all private!
	var $framework;
	var $searcher;

	function __construct(&$framework, $param)
	{
		$this->framework	=& $framework;
		$this->searcher		=& createWisyObject('WISY_SEARCH_CLASS', $framework, $param);
		
		
	}
	
	function prepare($queryString)
	{
		$this->suggestions = array();
		$this->changed_query = '';
	
		$this->searcher->prepare($queryString);
		if( $this->searcher->tokens['show'] != 'kurse' )
			return; // die intelligente Suche greift aktuell nur für die Suche nach Kursen
	
		$this->kurseCount = $this->searcher->getKurseCount();
		if( $this->kurseCount > 0 )
			return; // wir haben ein ergebnis, FEIN ...

		// ... ANSONSTEN: dies ist genau die Stelle, an der die "intelligente Suche" versuchen muss, 
		// doch noch eine Ergebnismenge zusammen zu stellen ...

		$this->suggestions = $this->getSuggestions($queryString);

		// ... dies geschieht allerdings nur bei _genau_ _einem_ token - andernfalls ist ein leeres Ergebnis sehr wahrscheinlich und
		// teilweise auch gewollt.
		$sizeOkay = false;
		if(  sizeof($this->searcher->tokens['cond']) == 1 
		 || (sizeof($this->searcher->tokens['cond']) == 2 && $this->searcher->tokens['cond'][1]['field']) )
			$sizeOkay = true;
		
		if( !$sizeOkay
		 || $this->searcher->tokens['cond'][0]['field'] != 'tag' )
		{
			return;
		}

		// try a nr:-search
		if( intval($this->framework->iniRead('intellisearch.nr', 0))==1 )
		{
			$nrSearcher = createWisyObject('WISY_SEARCH_NR_CLASS', $this->framework);
			$ids = $nrSearcher->nr2id($this->searcher->tokens['cond'][0]['value']);
			if( sizeof((array) $ids) )
			{
				$changed_query = 'nr:' . $this->searcher->tokens['cond'][0]['value'];
				$this->searcher->prepare($changed_query);
				$this->kurseCount = $this->searcher->getKurseCount();
				if( $this->kurseCount > 0 )
				{
					$this->changed_query = $changed_query;
					$this->suggestions = array();
					return; // success with nr:-search :-)
				}
			}
		}

		// try to perform a fulltext search
		$fulltextSetting = intval($this->framework->iniRead('intellisearch.fulltext', 0));
		if( ($fulltextSetting == 1 && sizeof($this->suggestions) == 0)
		 || ($fulltextSetting == 2) )
		{
			$changed_query = 'volltext:' . $this->searcher->tokens['cond'][0]['value'];
			$this->searcher->prepare($changed_query);
			$this->kurseCount = $this->searcher->getKurseCount();
			if( $this->kurseCount > 0 )
			{
				$this->changed_query = $changed_query;
				return; // success with fulltext search :-)
			}
		}
	}	

	function ok()
	{	
		if( $this->searcher->error['id'] == 'tag_not_found' )
			return true;
			
		return $this->searcher->ok();
	}
	
	function getInfo()
	{
		$ret = $this->searcher->getInfo();
			
		$ret['suggestions'] = $this->suggestions;
		$ret['changed_query'] = $this->changed_query;
		
		return $ret;
	}
	
	function getKurseCount()
	{
		return $this->kurseCount;
	}
	
	function getKurseRecords($offset, $rows, $orderBy)
	{
		return $this->searcher->getKurseRecords($offset, $rows, $orderBy);
	}
	
	function getAnbieterCount()
	{
		return $this->searcher->getAnbieterCount();
	}
	
	function getAnbieterRecords($offset, $rows, $orderBy)
	{
		return $this->searcher->getAnbieterRecords($offset, $rows, $orderBy);
	}

	function getSuggestions($org_queryString /*without ".portal" etc.*/)
	{
		$ret = array();

		switch( $this->searcher->error['id'] )
		{
			case 'tag_not_found':
				// show a list simelar to the ajax-suggestions
				$qstart = '';
				for( $i = 0; $i < $this->searcher->error['first_bad_tag']; $i++ )
				{
					$qstart .= $this->searcher->tokens['cond'][$i]['value'];
					$qstart .= ', ';
				}
				
				$tagsuggestor =& createWisyObject('WISY_TAGSUGGESTOR_CLASS', $this->framework);
                
				$qsuggestions = $tagsuggestor->suggestTags($this->searcher->tokens['cond'][ $this->searcher->error['first_bad_tag'] ]['value'],array('max'=>10, 'q_tag_type_not'=>array(256,512)));
				for( $i = 0; $i < sizeof((array) $qsuggestions); $i++ )
				{
					//if( ($qsuggestions[$i]['tag_type']&64) == 0 )
					{
						$ret[] = array(	'tag'		=> $qstart . $qsuggestions[$i]['tag']
									  , 'tag_type'	=> $qsuggestions[$i]['tag_type']
									  , 'tag_help'	=> $qsuggestions[$i]['tag_help']
									  );
					}
				}
				break;
			
			default:
				// most common reason for this: an empty result, suggest easier searches
				if( $this->searcher->ok() && sizeof($this->searcher->tokens['cond'])>=2 )
				{
					for( $i = 0; $i < sizeof($this->searcher->tokens['cond']); $i++ )
					{
						if( $this->searcher->tokens['cond'][$i]['field'] == 'tag' )
						{
							$value = $this->searcher->tokens['cond'][$i]['value'];
							if( substr($value, 0, 7) != '.portal' )
							{
								if( $value != rtrim($org_queryString, ', ') )
								{
									$ret[] = array(	'tag'		=> $value
												  ,	'tag_type'	=> 0
												  );
								}
							}
						}
					}
				}
				break;
		}
		
		return $ret;
	}
};
