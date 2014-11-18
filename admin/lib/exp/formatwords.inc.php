<?php



//
// words export
// ----------------------------------------------------------------------------
//


require_once('eql.inc.php');

class EXP_FORMATWORDS_CLASS extends EXP_PLUGIN_CLASS
{
	function __construct()
	{
		parent::__construct();
		
		$this->remark			= '_EXP_WORDSREMARK';
		$this->options['table']	=	array('enum', '_EXP_TABLETOEXPORT', '', 'tables');
	}

	function exportWords($table, $select)
	{
		$db		= new DB_Admin('use_phys_connection');
		$db2	= new DB_Admin;
		
		// collect text fields
		$tableDef = Table_Find_Def($table, false /*no access check*/);
		if( !$tableDef ) {
			return; // nothing to do
		}

		$fields = array();
		$natsort = array();
		for( $r = 0; $r < sizeof($tableDef->rows); $r++ )
		{
			$rowtype = intval($tableDef->rows[$r]->flags & TABLE_ROW);
			
			if( $rowtype == TABLE_TEXT || $rowtype == TABLE_TEXTAREA ) {
				$fields[] = $tableDef->rows[$r]->name;
			}
			
			if( $rowtype == TABLE_TEXT && $db->column_exists($table, $tableDef->rows[$r]->name.'_sorted') ) {
				$natsort[] = $tableDef->rows[$r]->name;
			}
		}
		
		$fieldsCount = sizeof($fields);
		$natsortCount = sizeof($natsort);
		
		if( $fieldsCount == 0 && $natsortCount == 0 ) {
			return; // nothing to do
		}

		// go through all records
		$recCount = 0;
		$db->query($select);
		while( $db->next_record() ) 
		{
			// go through all records of the current field and add the words
			for( $f = 0; $f < $fieldsCount; $f++ )
			{
				$words = g_eql_normalize_words($db->f($fields[$f]));
				$wordsCount = sizeof($words);
				
				// go through all words
				for( $w = 0; $w < $wordsCount; $w++ ) {
					$this->words[$words[$w]] = 1;
				}
			}
			
			// go through all records of the current field and add the 'natsort' string
			for( $f = 0; $f < $natsortCount; $f++ )
			{
				$db2->query("UPDATE $table SET {$natsort[$f]}_sorted='" . g_eql_normalize_natsort($db->f($natsort[$f])) . "' WHERE id=" . $db->f('id'));
			}
			
			// progress info
			$recCount++;
			if( ($recCount % 500) == 0 ) {
				$this->progress_info(htmlconstant('_EXP_NRECORDSDONE___', $table, $recCount));
			}
		}
	}

	function export($param)
	{
		
		
		// init word list
		$this->words = array();

		// go through all tables and collect the words
		
		$table = $param['table'];
		$this->exportWords($table, "SELECT * FROM $table;");

		// sort words
		ksort($this->words);
		
		// create table 'user_fuzzy' if not exists
		$db = new DB_Admin;
		if( !$db->table_exists('user_fuzzy') )
		{
			$db->query(
					"CREATE TABLE user_fuzzy ( "
				.	"word varchar(128) NOT NULL default '', "
				.	"soundex varchar(128) NOT NULL default '', "
				.	"metaphone varchar(128) NOT NULL default '', "
				.	"KEY word (word), "
				.	"KEY metaphone (metaphone), "
				.	"KEY soundex (soundex) );"
			);
		}
		
		// update table 'user_fuzzy', create file list
		$handle = fopen($this->allocateFileName('words.txt'), 'w+');if( !$handle ) $this->progress_abort("Cannot open dest file.");
		$recCount = 0;
		$totalCount = sizeof($this->words);
		reset($this->words);
		while( list($word) = each($this->words) )
		{
			// update table 'user_fuzzy' (addslashes() not needed as $word does not contain slashes by definition)
			$db->query("SELECT word FROM user_fuzzy WHERE word='$word'");
			if( !$db->next_record() ) 
			{
				// get soundex key
				$soundex = soundex($word);
				if( $soundex{0} == '0' ) {
					$soundex = '';
				}
				
				// get metaphone key
				$metaphone = metaphone($word);
				
				// insert new word
				$db->query("INSERT INTO user_fuzzy (word, soundex, metaphone) VALUES ('$word', '$soundex', '$metaphone')");
			}
		
			// add to file list
			fwrite($handle, "$word\n");

			// progress info
			$recCount++;
			if( ($recCount % 1000) == 0 ) {
				$this->progress_info(htmlconstant('_EXP_NRECORDSDONE___', 'words', "$recCount/$totalCount"));
			}
		}
		
		fclose($handle);
	}
}




