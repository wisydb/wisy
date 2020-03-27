<?php if( !defined('IN_WISY') ) die('!IN_WISY');


class WISY_KURS_ANALYZER_CLASS
{
	var $framework;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
		require_once('admin/config/codes.inc.php'); // fuer hidden_stichwort_eigenschaften
	}
	
	public function hasKeyword(&$db, $table, $kursId, $tagId)
	{
	    return $this->loadKeyword($db, $table, $kursId, $tagId);
	}
	
	public function loadKeywordsAbschluss(&$db, $table, $kursId)
	{
		return $this->loadKeywordsByType($db, $table, $kursId, 1);
	}
	
	public function loadKeywordsZertifikat(&$db, $table, $kursId)
	{
		return $this->loadKeywordsByType($db, $table, $kursId, 65536);
	}
	
	public function loadKeywordUnterrichtsart(&$db, $table, $kursId)
	{
	    return $this->loadKeywordsByType($db, $table, $kursId, 32768);
	}
	
	public function loadKeywordsVerwaltungsstichwort(&$db, $table, $kursId)
	{
		return $this->loadKeywordsByType($db, $table, $kursId, 2048);
	}
	
	public function loadKeywordsSonstigesMerkmal(&$db, $table, $kursId)
	{
		return $this->loadKeywordsByType($db, $table, $kursId, 1024);
	}
	
	protected function loadKeyword(&$db, $table, $kursId, $tagId)
	{
	    $ret = array();
	    
	    $sql = "SELECT primary_id FROM {$table}_stichwort WHERE primary_id = $kursId AND attr_id = $tagId";
	    
	    $db->query($sql);
	    while( $db->next_record() )
	    {
	        $ret[] = $db->Record;
	    }
	    $db->free();
	    
	    return $ret;
	}
	
	/* Sachstichwort: 0, Abschluss: 1, Förderungsart: 2, Qualitätszertifikat: 4, Zielgruppe: 8,
	 * Unterrichtsart: 32768, Abschlussart: 16, verstecktes Synonym: 32, Synonym: 64, Veranstaltungsort: 128,
	 * Volltext Titel: 256, Volltext Beschreibung: 512, Sonstiges Merkmal: 1024, Verwaltungsstichwort: 2048,
	 * Thema: 4096, Schlagwort nicht verwenden: 8192, Anbieterstichwort: 16384, Zertifikat: 65536
	 * */
	protected function loadKeywordsByType(&$db, $table, $kursId, $typeId)
	{
		$ret = array();
	
	 global $hidden_stichwort_eigenschaften;
				
		$sql = "SELECT id, stichwort, eigenschaften FROM stichwoerter LEFT JOIN {$table}_stichwort ON id=attr_id WHERE primary_id=$kursId "
					."AND eigenschaften=$typeId ORDER BY structure_pos;";
								
		$db->query($sql);
		while( $db->next_record() )
		{
				$ret[] = $db->Record;
		}
		$db->free();
	
		return $ret;
	}		
		
	/* Example Usage of m_search in search renderer context:
	 * $specialKeyword = 123456;
	 * $isKursWithSpecialKeyword = $kursAnalyzer->hasKeywordOfType($kursAnalyzer->loadKeywordsSonstigesMerkmal($db, 'kurse', $currKursId), "id", $keyword_special);
	 **/	
	public function hasKeywordOfType($array, $key, $value)
	{
	    $results = array();
	
	    if (is_array($array))
	    {
						if (isset($array[$key]) && $array[$key] == $value)
										$results[] = $array;
					
						foreach ($array as $subarray)
										$results = array_merge($results, $this->m_search($subarray, $key, $value));
	    }
	
	    return $results;
	}
	
}