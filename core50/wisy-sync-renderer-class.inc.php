<?php if( !defined('IN_WISY') ) die('!IN_WISY');


/*****************************************************************************
 * WISY_SYNC_RENDERER_CLASS
 * automatische Synchronisation Datenbank -> Suchindex
 *****************************************************************************
 
 Aufruf dieses Scripts:
 sync?kurseFast		-	Aufruf z.B. alle 30 oder 60 oder 120 Minute
						* geaenderte kurse nach x_kurse / x_kurse_plz / x_kurse_tags kopieren
						
						
 sync?kurseSlow		-	Aufruf z.B. einmal taeglich, 6:00 Uhr:
						* alle kurse nach x_kurse / x_kurse_plz / x_kurse_tags kopieren
 
 Zusaetzliche Parameter:
 &apikey=<apikey>	-	evtl. notwendiges Passwort, kann in den Portaleinstellungen
						der Domain unter apikey= definiert werden.
						Standardpasswort: none

 *****************************************************************************

 Die verschiedenen Sync aktualisieren die Indextabellen, die da sind:
																				
 x_kurse		-	alle Kurse, 1:1 aus der Tabelle "kurse"					
 x_kurse_plz	-	PLZ der Kurse, ein Kurs kann viele PLZ haben			
 x_kurse_tags	-	Tags der Kurse, ein Kurs kann viele Tags haben			
 x_tags			-	die Tags aus x_kurse_tags								
 x_tags_freq	-	wie of kommt ein best. Tag in einem best. Portal vor?	
 x_tags_syn		-	Synonyme der Tags										
 
 *****************************************************************************

 Bits for tag_type:
 
 1		  	- Abschluss				(gleicher Wert wie stichwort.eigenschaften)
 2			- Foerderungsart		(gleicher Wert wie stichwort.eigenschaften)
 4          - Qualitaetszert.		(gleicher Wert wie stichwort.eigenschaften)
 8          - Zielgruppe			(gleicher Wert wie stichwort.eigenschaften)
 16         - Abschlussart			(gleicher Wert wie stichwort.eigenschaften)
 32			- verstecktes Synonym	(gleicher Wert wie stichwort.eigenschaften, in tag_type nur in Kombination mit Synonym/64)
 64         - Synonym 				(gleicher Wert wie stichwort.eigenschaften, alles, was kein Synonym ist, ist ein Lemma)
 128        - Thema             	(so nicht in stichwort.eigenschaften - wird nicht verwendet, da zu viele ueberschneidungen mit normalen Stichworten)
 256        - Anbieter				(so nicht in stichwort.eigenschaften)
 512        - Ort					(so nicht in stichwort.eigenschaften)
 1024       - sonstiges Merkmal		(gleicher Wert wie stichwort.eigenschaften)
 32768		- Unterrichtsart		(gleicher Wert wie stichwort.eigenschaften)
 65536		- Zertifikat			(gleicher Wert wie stichwort.eigenschaften)
 0x0sss0000	- Subtype				(verwendet fuer Anbieter)
 0x10000000 - Indent				(wird nur zur Laufzeit verwendet)
 0x20000000	- Fuzzy					(wird nur zur Laufzeit verwendet)

 *****************************************************************************/
 
 
 
/* WISY_SYNC_STATE_CLASS --
 * hier werden in einer INI-aehnlichen Tabelle zustaende abgelegt, z.B. das Datum des letzten Syncs;
 * modifizierte Tabellen: x_state
 *****************************************************************************/
 
class WISY_SYNC_STATETABLE_CLASS
{
	function WISY_SYNC_STATETABLE_CLASS(&$framework)
	{
		$this->framework 	=& $framework;
		$this->db 			= new DB_Admin;
	}

	// read / write / lock
	function readState($key, $default='')
	{
		$ret = $default;
		$sql = "SELECT svalue FROM x_state WHERE skey='".addslashes($key)."';";
		$this->db->query($sql);
		if( $this->db->next_record() )
			$ret = $this->db->f8('svalue');
		return $ret;
	}
	function writeState($key, $val)
	{
		$this->db->query("SELECT svalue FROM x_state WHERE skey='".addslashes($key)."';");
		if( !$this->db->next_record() )
			$this->db->query("INSERT INTO x_state (skey) VALUES('".addslashes($key)."');");
		$this->db->query("UPDATE x_state SET svalue='".addslashes($val)."' WHERE skey='".addslashes($key)."';");
	}
	function lock($lock)
	{
		if( $lock )
			return $this->db->lock('x_state');
		else
			return $this->db->unlock();
	}
	
	// update stick handling
	function allocateUpdatestick()
	{
		if( !$this->lock(true) ) { return false; }
					$updatestick = $this->readState('updatestick', '0000-00-00 00:00:00');
					if( $updatestick > strftime("%Y-%m-%d %H:%M:00", time() - 3*60) /*wait 3 minutes*/ )
						{ $this->lock(false); return false; }
					$this->updatestick_datetime = strftime("%Y-%m-%d %H:%M:00");
					$this->writeState('updatestick', $this->updatestick_datetime);
		$this->lock(false);
		return true;
	}
	function updateUpdatestick()
	{
		if( $this->updatestick_datetime != strftime('%Y-%m-%d %H:%M:00') )
		{
			$this->updatestick_datetime = strftime('%Y-%m-%d %H:%M:00');
			$this->lock(true);
						$this->writeState('updatestick', $this->updatestick_datetime);
			$this->lock(false);
		}
	}
	function releaseUpdatestick()
	{
		$this->lock(true);
					$this->writeState('updatestick', '0000-00-00 00:00:00');
		$this->lock(false);
	}
}




/* TAGTABLE_CLASS --
 * Einfache, schnelle Zuordnung tag_name -> tag_id, nicht mehr, nicht weniger
 * von dieser Klasse existiert i.d.R. nur genau eine Instanz;
 * Modifizierte Tabellen: x_tags
 *****************************************************************************/

class TAGTABLE_CLASS
{
	function TAGTABLE_CLASS()
	{
		$this->db = new DB_Admin;
		$this->tags = array();	
	}
	
	function lookup($tag_name)
	{
		// lookup a tag and return its ID; if unexistant, 0 is returned
		$tag_name = trim($tag_name);
		
		if( !isset($this->tags[ $tag_name ]) )
		{
			$this->db->query("SELECT tag_id, tag_type, tag_help, tag_descr FROM x_tags WHERE tag_name='".addslashes($tag_name)."';");
			if( $this->db->next_record() )
			{
				$this->tags[ $tag_name ] = array( intval($this->db->f8('tag_id')), intval($this->db->f8('tag_type')), intval($this->db->f8('tag_help')), $this->db->f8('tag_descr') );
			}
			else
			{
				$this->tags[ $tag_name ] = array( 0, 0, 0 ); // even add "not existant" to the cache
			}
		}
		
		return $this->tags[ $tag_name ][ 0 ];
	}
	
	function lookupOrInsert($tag_name, $tag_type, $tag_help = 0, $tag_descr = '')
	{
		// lookup a tag and return its ID; if unexistant, the tag is inserted
		$tag_name = trim($tag_name);
		
		if( $tag_name == '' || strpos($tag_name, ',')!==false || strpos($tag_name, ':')!==false )
		{
			return 0; // error
		}
		
		if( $this->lookup($tag_name) == 0 )
		{
			$tag_soundex = soundex($tag_name);
			$tag_metaphone = metaphone($tag_name);
			$this->db->query("INSERT INTO x_tags (tag_name, tag_descr, tag_type, tag_help, tag_soundex, tag_metaphone) VALUES ('".addslashes($tag_name)."', '".addslashes($tag_descr)."', $tag_type, $tag_help, '$tag_soundex', '$tag_metaphone')");
			$this->tags[ $tag_name ] = array( intval($this->db->insert_id()), $tag_type, $tag_help, $tag_descr );
		}
		else 
		{
			// if needed, correct tag_type/tag_help - this may happen if we add new tag types, in the normal db usage, these values normally do not change.
			if( $this->tags[ $tag_name ][ 1 ] != $tag_type 
			 || $this->tags[ $tag_name ][ 2 ] != $tag_help 
			 || $this->tags[ $tag_name ][ 3 ] != $tag_descr )
			{
				$this->db->query("UPDATE x_tags SET tag_type=$tag_type, tag_help=$tag_help, tag_descr='".addslashes($tag_descr)."' WHERE tag_id=".$this->tags[ $tag_name ][ 0 ]);
				$this->tags[ $tag_name ][ 1 ] = $tag_type;
				$this->tags[ $tag_name ][ 2 ] = $tag_help;
				$this->tags[ $tag_name ][ 3 ] = $tag_descr;
			}
		}
		
		return $this->tags[ $tag_name ][ 0 ];
	}
	
	function getUsedTagIdsAsString()
	{
		// returns a comma-separated string with all tag IDs
		// (the function returns the tag IDs *really* used by a lookup() or by a lookupOrInsert() call, not the complete database,
		// in fact, the returned list ist used to delete old records from the database)
		$ret = '0';
		reset( $this->tags );
		while( list($tag_name, $tag_param) = each($this->tags) )
			$ret .= ', ' . $tag_param[ 0 ];
		
		// add all synonyms and all portal tags; they should be preserved as the function is used to delete tags
		$this->db->query("SELECT tag_id FROM x_tags WHERE tag_type & 64;");
		while( $this->db->next_record() )
			$ret .= ', ' . $this->db->f8('tag_id');
		
		return $ret;
	}
}




/* ATTR2TAG_CLASS --
 * Diese Klasse konvertiert eine Attribut-ID in eine Tag-Id;
 * Modifizierte Tabellen: x_tags
 *****************************************************************************/

class ATTR2TAG_CLASS
{
	function ATTR2TAG_CLASS(&$tagtable, $table, $field)
	{
		$this->db = new DB_Admin;
		$this->tagtable =& $tagtable;
		
		$this->table 	= $table;
		$this->field 	= $field;
		$this->addField	= '';
		$this->addWhere	= '';
		
		$this->cache	= array();
		
		if( $table == 'themen' )
		{
			// ... special preparations for "themen"
			$this->addField = ', kuerzel_sorted';
		}
		else if( $this->table == 'stichwoerter' )
		{
			// ... special preparations for "stichwoerter"
			global $hidden_stichwort_eigenschaften;
			$hidden_stichwort_eigenschaften_plus_synonyme = $hidden_stichwort_eigenschaften | 32 | 64; // hier die Bits nicht addieren: Dies fuehrt schnell dazu, das Werte doppelt addiert werden ...
			$this->addWhere = " AND (eigenschaften & $hidden_stichwort_eigenschaften_plus_synonyme)=0 ";
			$this->addField = ', eigenschaften, glossar, zusatzinfo';
		}
		else if( $this->table == 'anbieter' )
		{
			// ... special preparations for "anbieter"
			$this->addField = ', suchname_sorted, typ';
		}
	}
	
	function lookupNames($attr_id, &$retArray)
	{
		// returns the names belonging to the given attribute ID as an array,
		// (only for hierarchical themen, more than one name is returned)

		if( !isset($this->cache[ $attr_id ]) )
		{
			$sql = "SELECT $this->field $this->addField FROM $this->table WHERE id=$attr_id $this->addWhere;";
			$this->db->query($sql);
			if( !$this->db->next_record() )
				return; // error - id not found
			
			// find out current tag_type
			$curr_tag_type = 0;
			$curr_tag_help = 0;
			$curr_tag_descr = '';
			if( $this->table == 'stichwoerter' )
			{
				$curr_tag_type = intval($this->db->f8('eigenschaften')) & (1+2+4+8+16+1024+32768+65536) /*flags, s.o.*/;
				$curr_tag_help = intval($this->db->f8('glossar'));
				$curr_tag_descr = $this->db->f8('zusatzinfo');
			}
			else if( $this->table == 'anbieter' )
			{
				$curr_tag_type = 256 								// maintype: Anbieter
						+		(intval($this->db->f8('typ'))<<16);	// subtype:  0=Anbieter,  1=Trainer, 2=Betratungsstelle, 64=Namensverweisung/Synonym
				
			}
			
			// do insert
			$this->cache[ $attr_id ][ 'names' ] = array();
			$this->cache[ $attr_id ][ 'names' ][] = array(g_sync_removeSpecialChars($this->db->f8($this->field)), $curr_tag_type, $curr_tag_help, $curr_tag_descr);
			if( $this->table == 'themen' )
			{
				$attr_kuerzel = $this->db->f8('kuerzel_sorted');
				$sql = '';
				for( $slen = strlen($attr_kuerzel) - 10; $slen > 0; $slen -= 10 )
				{
					$parent_kuerzel = substr($attr_kuerzel, 0, $slen);
					$sql .= $sql==''? "SELECT thema FROM themen WHERE kuerzel_sorted IN (" : ',';
					$sql .= "'" . $parent_kuerzel . "'";
				}
				
				if( $sql != '' )
				{
					$sql .= ');';
					$this->db->query($sql);
					while( $this->db->next_record() )
					{
						$this->cache[ $attr_id ][ 'names' ][] = array(g_sync_removeSpecialChars($this->db->f8('thema')), $curr_tag_type, 0);
					}
				}
			}
			else if( $this->table == 'anbieter' )
			{
				$this->cache[ $attr_id ][ 'sorted' ] = $this->db->f8('suchname_sorted');
			}
		}
		
		$retArray = $this->cache[ $attr_id ][ 'names' ];
	}
	
	function lookupTagIds($attr_id, &$retArray)
	{
		$names = array();
		$this->lookupNames($attr_id, $names);
		for( $i = 0; $i < sizeof($names); $i++ )
		{
			$id = $this->tagtable->lookupOrInsert($names[$i][0], $names[$i][1], $names[$i][2], $names[$i][3]);
			if( $id != 0 )
				$retArray[] = $id;
		}
	}
	
	function lookupSorted($attr_id)
	{
		$this->lookupNames($attr_id, $dummy); // lookupNames() loads the attributes implicitly ...
		return $this->cache[ $attr_id ][ 'sorted' ];
	}
};



/* PORTAL2TAG_CLASS
 * Berechnet zu einer Kurs-ID die zugehoerigen Portal-Tags
 * Modifizierte Tabellen: x_tags
 ****************************************************************************/
 
class KURS2PORTALTAG_CLASS
{
	public  $all_portals;
	private $portaltags;
	private $plzfilter;
	
	function KURS2PORTALTAG_CLASS(&$framework, &$tagtable, &$statetable, $alle_kurse_str)
	{
		$db = new DB_ADMIN;
		$db2 = new DB_ADMIN;
		$this->framework = $framework;
				
		// nachsehen, in welchen portalen, die kurse von $alle_kurse_str enthalten sind 
		$this->all_portals = array();
		$this->portaltags = array();
		$this->plzfilter = array();
		$eql2sql = new EQL2SQL_CLASS('kurse');
		$db->query("SELECT id, einstellungen, einstcache, filter FROM portale;");
		while( $db->next_record() )
		{
			$portal_id		= $db->f8('id');
			$portal_tag		= 0;
			$einstellungen	= explodeSettings($db->f8('einstellungen'));
			$einstcache		= explodeSettings($db->f8('einstcache'));
			$filter			= explodeSettings($db->f8('filter'));

			if( $einstellungen['core'] == '20' && $filter['stdkursfilter'] != '' )
			{
				$portal_tag = $tagtable->lookupOrInsert(".portal$portal_id", 0);
				
				$sql = $eql2sql->eql2sql($filter['stdkursfilter'], 'id', '(kurse.id IN ('.$alle_kurse_str.'))', '');
				$db2->query($sql);
				
				echo sprintf("... lade %d Kurse fuer Portal %d ...\n", $db2->num_rows(), $portal_id); flush();
				$statetable->updateUpdatestick();
				
				while( $db2->next_record() ) {
					$this->portaltags[ $db2->f8('id') ][] = $portal_tag;
				}
				
				if( $einstellungen['durchf.plz.hardfilter'] ) {
					$this->plzfilter[ $portal_tag ] =& createWisyObject('WISY_PLZFILTER_CLASS', $this->framework, $einstellungen);
				}
			}
			
			$this->all_portals[ $portal_id ] = array( 'einstellungen'=>$einstellungen, 'einstcache'=>$einstcache, 'filter'=>$filer, 'portal_tag'=>$portal_tag);
		}
	}
	
	function getPortalTagsAndIncCounts($kurs_id, &$tag_ids, $anbieter_id, $anz_durchf, $d_plz, $d_has_unset_plz)
	{
		for( $i = sizeof($this->portaltags[ $kurs_id ])-1; $i >= 0; $i-- )
		{
			// der kurs ist in diesem portal ...
			$portal_tag_id = $this->portaltags[ $kurs_id ][ $i ];
			
			// ist der kurs auch ueber den PLZ-Fiter ausgewaehlt? wenn nicht, soll er nicht in diesem Portal erscheinen
			if( is_object($this->plzfilter[ $portal_tag_id ]) ) {
				if( $d_has_unset_plz || $this->plzfilter[ $portal_tag_id ]->is_valid_plz_in_hash($d_plz) ) {
					//echo "OK: $kurs_id<br />";
				}
				else {
					//echo "SKIPPED: $kurs_id for $portal_tag_id\n";
					continue;
				}
			}
			
			// portal tag zurueckgeben
			$tag_ids[] = $portal_tag_id;
			
			// anzahlen fuer dieses portal erhoehen (nur wenn es auch durchfuehrungen gibt)
			if( $anz_durchf ) 
			{
				$this->portal_tags_anz_anbieter[ $portal_tag_id ][ $anbieter_id ] = 1;
				$this->portal_tags_anz_kurse   [ $portal_tag_id ] ++;
				$this->portal_tags_anz_durchf  [ $portal_tag_id ] += $anz_durchf;
			}
		}

		// anzahlen portal ohne filter erhoehen (nur wenn es auch durchfuehrungen gibt)
		if( $anz_durchf ) 
		{
			$this->portal_tags_anz_anbieter[ 0 ][ $anbieter_id ] = 1;
			$this->portal_tags_anz_kurse   [ 0 ] ++;
			$this->portal_tags_anz_durchf  [ 0 ] += $anz_durchf;
		}
	}
	
	function getPortalTagsCounts($portal_tag_id)
	{
		return array(
			'anz_anbieter'	=>	sizeof($this->portal_tags_anz_anbieter[ $portal_tag_id ]),
			'anz_kurse'		=>	intval($this->portal_tags_anz_kurse   [ $portal_tag_id ]),
			'anz_durchf'	=>	intval($this->portal_tags_anz_durchf  [ $portal_tag_id ]),
		);
	}
}


/* WISY_SYNC_RENDERER_CLASS
 *****************************************************************************/

class WISY_SYNC_RENDERER_CLASS
{
	function __construct(&$framework, $param)
	{
		// include EQL
		require_once('admin/classes.inc.php');
		require_once("admin/lang.inc.php");								
		require_once("admin/eql.inc.php");
		require_once("admin/config/codes.inc.php");
		require_once('admin/config/trigger_kurse.inc.php');
	
		$db = new DB_Admin;
		
		// setup vars
		$this->framework		=& $framework;
		$this->statetable		= new WISY_SYNC_STATETABLE_CLASS($this->framework);
		$this->tagtable			= new TAGTABLE_CLASS();
		$this->themen2tag   	= new ATTR2TAG_CLASS($this->tagtable, 'themen', 		'thema'			);
		$this->stichw2tag   	= new ATTR2TAG_CLASS($this->tagtable, 'stichwoerter',	'stichwort'		);
		$this->anbieter2tag 	= new ATTR2TAG_CLASS($this->tagtable, 'anbieter',		'suchname'		);
		$this->weekdays			= array('Montags', 'Dienstags', 'Mittwochs', 'Donnerstags', 'Freitags', 'Samstags', 'Sonntags');
		$this->tagescodes		= array(''/*0*/, 'Ganztags'/*1*/, 'Vormittags'/*2*/, 'Nachmittags'/*3*/, 'Abends'/*4*/, 'Wochenende'/*5*/);
		$this->today_datetime   = strftime("%Y-%m-%d %H:%M:%S");
		$this->today_datenotime = substr($this->today_datetime, 0, 10);
		
		// create hash stichwort => übergeordneteStichwörter
		$this->verweis2 = array();
		$db->query("SELECT primary_id, attr_id FROM stichwoerter_verweis2 LEFT JOIN stichwoerter ON attr_id=id WHERE eigenschaften&(32|64)=0;");
		while( $db->next_record() ) {
			$this->verweis2[ $db->f8('attr_id') ][] = $db->f8('primary_id');
		}
		
		$this->flatenArray($this->verweis2);
	}
	
	
	protected function flatenArray(&$ids)
	{
		// function expects an array as $id => $super_ids
		// for each $id it then scans all $super_ids and checks if they exists as a $id - if so, these $super_ids are added to the original $id, too.
		// recursive function.
		$this->in_work = array();
		foreach( $ids as $id => $super_ids )
		{
			$this->flatenArray__($ids, $id);
		}
	}
	protected function flatenArray__(&$ids, $id)
	{
		if( $this->in_work[$id] ) { return; } $this->in_work[$id] = 1; // avoid dead lock and speed up things
		foreach( $ids[$id] as $super_id )
		{
			if( is_array($ids[$super_id]) ) 
			{
				$this->flatenArray__($ids, $super_id);
				for( $k = 0; $k < sizeof($ids[$super_id]); $k++ )
				{
					$to_add = $ids[$super_id][$k];
					if( !in_array($to_add, $ids[$id]) )
					{
						$ids[$id][] = $to_add;
					}
				}
			}
		}
	}
	
	
	function log($str)
	{
		echo $str . "\n";
		flush();
		$this->framework->log('sync', $str);
	}

	function cleanupSearchCache()
	{
		$dbCache =& createWisyObject('WISY_CACHE_CLASS', $this->framework, array('table' => 'x_cache_search'));
		$dbCache->cleanup(); 
	}
	
	private function doSyncSynonyms()
	{
		$insertValues = array();
		$tableValues = array();

		// collect Stichwoerter / Synonyme
		$db = new DB_Admin;
		$db2 = new DB_Admin;
		$db->query("SELECT id, stichwort, eigenschaften FROM stichwoerter WHERE eigenschaften=32 OR eigenschaften=64;"); // !!! in "eigenschaften" gilt: 32 = versteckte Synonyme, 64 =normale Synonyme
		while( $db->next_record() )
		{
			$synonym = g_sync_removeSpecialChars($db->f8('stichwort'));
			$synonym_id = intval($db->f8('id'));
			$eigenschaften = intval($db->f8('eigenschaften'));
			$dest_found = false;

			$db2->query("SELECT stichwort FROM stichwoerter LEFT JOIN stichwoerter_verweis v ON id=v.attr_id WHERE v.primary_id=$synonym_id");
			while( $db2->next_record() )
			{	
				$cur = g_sync_removeSpecialChars($db2->f8('stichwort'));
				$cur_id = $this->tagtable->lookup($cur);
				if( $cur_id ) { $dest_found = true; $tableValues[] = array($synonym, $cur_id); }
			}
			
			if( $dest_found )
				$insertValues[] = array($synonym, $eigenschaften==32? 64+32 : 64); // !!! in "tag_type" gilt: das Synonym-Bit ist immer gesetzt, das Versteckte-Synonym-Bit kommt optional dazu
		}
																				$this->statetable->updateUpdatestick();
		// collect Anbieter / Namensverweise
		$db->query("SELECT suchname, id FROM anbieter WHERE typ=64;"); // 64 = Synonym
		while( $db->next_record() )
		{
			$synonym = g_sync_removeSpecialChars($db->f8('suchname'));
			$synonym_id = intval($db->f8('id'));
			$dest_found = false;
			
			$db2->query("SELECT suchname FROM anbieter LEFT JOIN anbieter_verweis v ON id=v.attr_id WHERE v.primary_id=$synonym_id");
			while( $db2->next_record() )
			{
				$cur = g_sync_removeSpecialChars($db2->f8('suchname'));
				$cur_id = $this->tagtable->lookup($cur);
				if( $cur_id ) { $dest_found = true; $tableValues[] = array($synonym, $cur_id); }
			}
			
			if( $dest_found )
				$insertValues[] = array($synonym, 64);
		}
																				$this->statetable->updateUpdatestick();
		// write all synonyms
		$db->query("DELETE FROM x_tags WHERE tag_type & 64;");
		for( $i = 0; $i < sizeof($insertValues); $i++ ) {
			$this->tagtable->lookupOrInsert($insertValues[$i][0], $insertValues[$i][1]);
		}
																				$this->statetable->updateUpdatestick();
		// create table to show where the synonyms link to
		$db->query("DELETE FROM x_tags_syn;");
		$values = '';
		for( $t = 0; $t < sizeof($tableValues); $t++ ) 
		{
			$syn_id = $this->tagtable->lookup($tableValues[$t][0]);
			if( $syn_id )
			{
				$values .= $values===''? '' : ', ';
				$values .= "($syn_id, {$tableValues[$t][1]})";
			}
			else
			{
				$this->log("ERROR: synonym $syn_id not found for {$tableValues[$t][0]} - {$tableValues[$t][1]}");
			}
		}
																				$this->statetable->updateUpdatestick();
		if( $values != '' )
		{
			$sql = "INSERT INTO x_tags_syn (tag_id, lemma_id) VALUES $values";
			$db->query($sql);
		}
		
		$this->log(sprintf("%s synonyms checked.", sizeof($insertValues)));
	}	
	

	/* syncing kurse
	 *************************************************************************/

	function doSyncKurse($deepupdate)
	{
		$db = new DB_Admin;
		$db2 = new DB_Admin;
		$db3 = new DB_Admin;
		
		$updateLatlng = $deepupdate;
		if( $updateLatlng ) {
			$geocoding_total_s = 0.0;
			$geocoded_addresses = 0;
			$geocoder =& createWisyObject('WISY_OPENSTREETMAP_CLASS', $this->framework);
		}

		// find out the date to start with syncing
		if( $deepupdate )
			$lastsync = '0000-00-00 00:00:00';
		else
			$lastsync = $this->statetable->readState('lastsync.kurse.global', '0000-00-00 00:00:00');
		
		// load all kurse modified since $lastsync
		$sql = "SELECT id, freigeschaltet, thema, anbieter, date_modified, bu_nummer, fu_knr, azwv_knr FROM kurse WHERE date_modified>='$lastsync' AND (freigeschaltet=1 OR freigeschaltet=4 OR freigeschaltet=3) $this->dbgCond;";
		$db->query( $sql );

		$alle_kurse_str = '0';
		$kurse_cnt = $db->ResultNumRows;
		for( $i = 0; $i < $kurse_cnt; $i++ )
			$alle_kurse_str .= ', ' . $db->Result[$i]['id'];
		
		$kurs2portaltag = new KURS2PORTALTAG_CLASS($this->framework, $this->tagtable, $this->statetable, $alle_kurse_str);
		
		// go through all kurse modified since $lastsync
		$kurs_cnt = 0;
		while( $db->next_record() )
		{
			$kurs_id 		= intval($db->f8('id'));							if( ($kurs_cnt % 100 ) == 0 ) { echo "... $kurs_cnt ff ...\n"; $this->statetable->updateUpdateStick(); }
			$anbieter_id	= intval($db->f8('anbieter'));						if( $anbieter_id <= 0 ) { $this->log("ERROR: kein Anbieter angegeben fuer Kurs ID $kurs_id."); }
			$freigeschaltet	= intval($db->f8('freigeschaltet'));
			
			// convert thema, stichw, anbieter etc. to tag IDs
			$tag_ids = array();
			$this->themen2tag->  lookupTagIds(intval($db->f8('thema')),    $tag_ids);
			$this->anbieter2tag->lookupTagIds($anbieter_id, $tag_ids);
			$db2->query("SELECT attr_id FROM kurse_stichwort WHERE primary_id=$kurs_id");
			$all_stichwort_ids = array();
			while( $db2->next_record() )
			{
				$stichwort_id = intval($db2->f8('attr_id'));
				$all_stichwort_ids[] = $stichwort_id;
				$this->stichw2tag->lookupTagIds($stichwort_id, $tag_ids);
			}
			
			$db2->query("SELECT attr_id FROM anbieter_stichwort WHERE primary_id=$anbieter_id;");
			while( $db2->next_record() )
			{
				$stichwort_id = intval($db2->f8('attr_id'));
				$all_stichwort_ids[] = $stichwort_id;
				$this->stichw2tag->lookupTagIds($stichwort_id, $tag_ids);
			}		
			
			// spezielle Nummern erzeugen spezielle Tags
			// siehe hierzu die Anmerkungen in wisy-durchf-class.inc.php bei [1]
			if( $db->f8('bu_nummer') != '' ) 
			{
				$tag_id = $this->tagtable->lookupOrInsert('Bildungsurlaub', 2 /*foerderungsart*/);
				if( $tag_id ) $tag_ids[] = $tag_id;
			}

			if( $db->f8('fu_knr') != '' ) 
			{
				$tag_id = $this->tagtable->lookupOrInsert('Fernunterricht', 0 /*sachstichw*/);
				if( $tag_id ) $tag_ids[] = $tag_id;
			}
			
			if( $db->f8('azwv_knr') != '' ) 
			{
				$tag_id = $this->tagtable->lookupOrInsert('Bildungsgutschein', 2 /*foerderungsart*/);
				if( $tag_id ) $tag_ids[] = $tag_id;
			}			
			
			// add AutoStichwort (d.h. zu einem Stichwort die uebergeordneten Stichwoerter automatisch hinzufuegen)
			// siehe hierzu die Anmerkungen in wisy-durchf-class.inc.php bei [1]
			foreach( $all_stichwort_ids as $stichwort_id )
			{
				if( is_array($this->verweis2[$stichwort_id]) )
				{
					foreach( $this->verweis2[$stichwort_id] as $stichwort_id2 )
					{
						$this->stichw2tag->lookupTagIds($stichwort_id2, $tag_ids);
					}
				}
			}
			
			// durchfuehrungen durchgehen ...
			$d_beginn			= array();
			$d_plz				= array(); $d_has_unset_plz = false;
			$d_latlng			= array();
			$k_beginn			= '0000-00-00';
			$k_beginn_last		= '0000-00-00';
			$k_preis			= -1;
			$k_dauer			= 0;
			$k_kurstage			= 0;
			$k_tagescodes		= array();
			$ort_sortonly		= '';
			$db2->query("SELECT durchfuehrung.id AS did, strasse, plz, ort, stadtteil, land, beginn, ende, beginnoptionen, dauer, preis, kurstage, tagescode, zeit_von, zeit_bis FROM durchfuehrung LEFT JOIN kurse_durchfuehrung ON secondary_id=id WHERE primary_id=$kurs_id");
			$anz_durchf = 0;
			//$at_least_one_durchf = false;
			while( $db2->next_record() )
			{
				// ... stadtteil / ort als Tag anlegen
				$strasse   		= $db2->f8('strasse');
				$plz 			= $db2->f8('plz'); if( $plz == '00000' ) $plz = '';
				$ort       		= $db2->f8('ort');
				$stadtteil 		= $db2->f8('stadtteil');
				$land 			= $db2->f8('land');
				$beginn			= $db2->f8('beginn');
				$ende			= $db2->f8('ende');
				$d_kurstage  	= intval($db2->f8('kurstage'));
				if( strpos($stadtteil, ',')===false && strpos($ort, ',')===false
				 && strpos($stadtteil, ':')===false && strpos($ort, ':')===false  // do not add suspicious fields -- there is no reason for a comma in stadtteil/ort
				) 
				{
					$tag_id = $this->tagtable->lookupOrInsert($stadtteil, 512);
					if( $tag_id ) $tag_ids[] = $tag_id;
	
					if( $ort!='Fernunterricht' /*ja, das wird teilweise so eingegeben ...*/)
					{
						$tag_id = $this->tagtable->lookupOrInsert($ort, 512);
						if( $tag_id ) $tag_ids[] = $tag_id;
					}
				}
				
				// stadtteil / ort zum sortieren aufbereiten
				if( $ort_sortonly == '' && $ort != '' )
				{
					$ort_sortonly = "$ort $stadtteil";
					$ort_sortonly = g_eql_normalize_natsort($ort_sortonly);
				}
				
				// plz sammeln
				if( $plz != '' )
				{
					$d_plz[$plz] = 1;
				}
				else
				{
					$d_has_unset_plz = true;
				}
				
				// latlng sammeln
				if( $updateLatlng )
				{
					$geocoding_start_s = microtime(true);
						$temp = $geocoder->geocode2($db2->Record, false);
						//$temp = $geocoder->geocode2perm($db2->Record, true);
						if( !$temp['error'] ) {
							$d_latlng[ intval($temp['lat']*1000000).','.intval($temp['lng']*1000000) ] = 1;
							$geocoded_addresses++;
						}
					$geocoding_total_s += microtime(true)-$geocoding_start_s;
				}
				
				// alle beginndaten sammeln
				$temp = substr($beginn, 0, 10); // yyyy-mm-dd
				if( $temp != '0000-00-00' )
				{
					$d_beginn[] = $temp;
					
					if( $temp >= $this->today_datenotime )
						$anz_durchf++;
				}
				else
				{
					$anz_durchf++;
				}
				//$at_least_one_durchf = true;
				
				$d_beginnoptionen = intval($db2->f8('beginnoptionen'));
				if( $d_beginnoptionen & 512 )
				{
					$tag_id = $this->tagtable->lookupOrInsert('Startgarantie', 0);
					if( $tag_id ) $tag_ids[] = $tag_id;
				}					
				
				// HACK: tagescode und dauer in Quelle berechnen
				// (dies sollte besser ausserhalb des Portals im Redaktionssystem passieren, aber hier ist es so schoen praktisch, da die Trigger sowieso aufgerufen werden)
				$write_back = '';
				$d_tagescode = berechne_tagescode($db2->f8('zeit_von'), $db2->f8('zeit_bis'), $d_kurstage);
				if( $d_tagescode != intval($db2->f8('tagescode')) )
				{
					$write_back = " tagescode=$d_tagescode ";
				}

				$d_dauer = berechne_dauer($beginn, $ende);
				if( $d_dauer != intval($db2->f8('dauer')) )
				{
					$write_back = " dauer=$d_dauer ";
				}
				
				if( $write_back != '' )
				{
					$sql = "UPDATE durchfuehrung SET $write_back WHERE id=".$db2->f8('did');
					$db3->query($sql);
				}
				
				// hoechste dauer setzen ("hoechste" hier willkuerlich, in 99.9% sind eh alle Angaben hierzu bei allen Druchfuehrungen gleich)
				if( $d_dauer > 0 && ($d_dauer > $k_dauer || $k_dauer==0) )
				{
					$k_dauer = $d_dauer;
				}				

				// hoechsten preis setzen ("hoechsten" wg. spam verhinderung, keine Ausnutzung besonderheiten des Systems)
				$d_preis = intval($db2->f8('preis'));
				if( $d_preis != -1 && ($d_preis > $k_preis || $k_preis == -1) )
				{
					$k_preis = $d_preis;
				}
				
				// kurstage (wochentage) / tagescode (vormittags, nachmittags, ...) sammeln
				$k_kurstage |= $d_kurstage;
				$k_tagescodes[ $d_tagescode ] = 1;
				
			} // ende durchfuehrungen

			// portale-tags zu $tag_ids hinzufuegen, anzahlen erhoehen
			//if( $anz_durchf == 0 && $at_least_one_durchf ) // 21:29 01.10.2013 at_least_one_durchf stellt sicher, dass im Zweifelsfalle eher mehr gezaehlt wird als zu wenig, s. Mails mit Juergen
			//	$anz_durchf++;								 // 15:11 08.10.2013 das fuehrt zu zu hohen Zahlen in RLP und anderswo, wir lassen das so also sein...
			
			$kurs2portaltag->getPortalTagsAndIncCounts($kurs_id, $tag_ids /*modified*/, $anbieter_id, $anz_durchf, $d_plz, $d_has_unset_plz);

			// kurstage (wochentage) / tagescode (vormittags, nachmittags, ...) setzen
			for( $i = 0; $i < 7; $i++ ) 
			{
				if( $k_kurstage & (1<<$i) )
				{
					$tag_id = $this->tagtable->lookupOrInsert($this->weekdays[$i], 0);
					if( $tag_id ) $tag_ids[] = $tag_id;
				}
			}
			
			reset($k_tagescodes);
			while( list($code) = each($k_tagescodes) )
			{
				if( $this->tagescodes[$code] != '' )
				{
					$tag_id = $this->tagtable->lookupOrInsert($this->tagescodes[$code], 0);
					if( $tag_id ) $tag_ids[] = $tag_id;
				}
			}
			
			// fruehestmoeglichstes beginndatum setzen
			if( sizeof($d_beginn) )
			{
				sort($d_beginn);
				for( $i = 0; $i < sizeof($d_beginn); $i++ )
				{
					$k_beginn = $d_beginn[$i];
					if( $k_beginn >= $this->today_datenotime )
						break;
				}
				
				// spaetestmoegliches beginndatum setzen
				for( $i = 0; $i < sizeof($d_beginn); $i++ )
				{
					if( $d_beginn[$i] >= $this->today_datenotime && $d_beginn[$i] >= $k_beginn_last)
						$k_beginn_last = $d_beginn[$i];
				}
			}
			
			if( $freigeschaltet == 1 /*freigegeben*/ || $freigeschaltet == 4 /*dauerhaft*/ )
			{
				if( $k_beginn < $this->today_datenotime )
					$k_beginn = '9999-09-09';	// Any date in the future -- this may happen frequently on missing dates with given beginnoptionen
												// -- 11:23 26.04.2013 it also happens with the new Stichwort #315/Einstieg bis Kursende moeglich
			}
			else if( $freigeschaltet == 3 /*abgelaufen*/ ) 
			{
				if( $k_beginn >= $this->today_datenotime )
					$k_beginn = '0000-00-00'; // Any date in the past -- this should normally not happen, only if the kurs is valid normally but set to abgelaufen manually
				
				if( $k_beginn_last >= $this->today_datenotime )
					$k_beginn_last = '0000-00-00'; // Any date in the past -- this should normally not happen, only if the kurs is valid normally but set to abgelaufen manually
			}

			// fruehestmoeglichstes beginndatum korrigieren, falls dieses in der Vergangenheit liegt UND kurse die Eigentschaften "Beginn erfragen" etc. zugewiesen wurde
			/*
			if( $k_beginn < $this->today_datenotime && $k_beginnerfragenetc )
			{
				$k_beginn = '0000-00-00';
			}
			*/

			// CREATE main search entry for this record (if not exist)
			$db2->query("SELECT begmod_hash, begmod_date FROM x_kurse WHERE kurs_id=$kurs_id;");
			if( $db2->next_record() )
			{
				$begmod_hash = $db2->f8('begmod_hash');
				$begmod_date = $db2->f8('begmod_date'); 
			}
			else
			{
				$db2->query("INSERT INTO x_kurse (kurs_id) VALUES ($kurs_id);");
				$begmod_hash = '';
				$begmod_date = $db->f8('date_modified');
			}

			// "Beginnaenderungsdatum" aktualisieren
			$begmod_hash = explode(',', $begmod_hash);
			for( $i = 0; $i < sizeof($d_beginn); $i++ )
			{
				if( !in_array($d_beginn[$i], $begmod_hash) )
				{
					$begmod_date = $db->f8('date_modified');
					break;
				}
			}
			$begmod_hash = implode(',', $d_beginn);
			
			// UPDATE main search entry for this record
			$sql = "UPDATE	x_kurse 
					SET 	beginn='$k_beginn'
					,		beginn_last='$k_beginn_last'
					,		dauer=$k_dauer
					,		preis=$k_preis
					,		anbieter_sortonly='".$this->anbieter2tag->lookupSorted($anbieter_id)."'
					,		ort_sortonly='$ort_sortonly'
					,		begmod_hash='$begmod_hash'
					,		begmod_date='$begmod_date'
					WHERE 	kurs_id=$kurs_id;";
			$db2->query($sql);
			
			// UPDATE tag table for this record
			$sql = '';
			$added = array();
			for( $t = 0; $t < sizeof($tag_ids); $t++ )
			{
				$tag_id = $tag_ids[$t];
				if( !$added[ $tag_id ] )
				{
					$added[ $tag_id ] = true;
					$sql .= $sql==""? "INSERT INTO x_kurse_tags (kurs_id, tag_id) VALUES " : ", ";
					$sql .= "($kurs_id, $tag_id)";
				}
			}
			
			$db2->query("DELETE FROM x_kurse_tags WHERE kurs_id=$kurs_id;");
			if( $sql != '' )
			{
				$db2->query($sql);
			}

			// UPDATE plz table for this record
			$sql = '';
			reset($d_plz);
			while( list($plz) = each($d_plz) )
			{
				$sql .= $sql==""? "INSERT INTO x_kurse_plz (kurs_id, plz) VALUES " : ", ";
				$sql .= "($kurs_id, '".addslashes($plz)."')";
			}
			
			$db2->query("DELETE FROM x_kurse_plz WHERE kurs_id=$kurs_id;");
			if( $sql != '' )
			{
				$db2->query($sql);
			}

			// UPDATE latlng table for this record
			// die Abfrage in x_kurse_latlng sollte ausreihend schnell sein; 
			// lt. http://dev.mysql.com/doc/refman/4.1/en/mysql-indexes.html wird der B-Tree auch fuer groesser/kleiner oder BETWEEN abfragen verwendet.
			if( $updateLatlng )
			{
				$sql = '';
				reset($d_latlng);
				while( list($latlng) = each($d_latlng) )
				{
					$sql .= $sql==""? "INSERT INTO x_kurse_latlng (kurs_id, lat, lng) VALUES " : ", ";
					$sql .= "($kurs_id, ".addslashes($latlng).")";
				}
				
				$db2->query("DELETE FROM x_kurse_latlng WHERE kurs_id=$kurs_id;");
				if( $sql != '' )
				{
					$db2->query($sql);
				}
			}
			
			// next kurs
			$kurs_cnt++;
		}
		
		$this->log(sprintf("%d addressed geocoded in %1.3f seconds.", $geocoded_addresses, $geocoding_total_s));
		$this->log(sprintf("%d records updated.", $kurs_cnt));
		
		// some specials for deepupdates
		if( $lastsync == '0000-00-00 00:00:00' )
		{
			// delete old records
			$db->query("DELETE FROM x_kurse_latlng WHERE kurs_id NOT IN($alle_kurse_str);");
			$db->query("DELETE FROM x_kurse_tags WHERE kurs_id NOT IN($alle_kurse_str);");
			$db->query("DELETE FROM x_kurse WHERE kurs_id NOT IN($alle_kurse_str);");
			$this->log($db->affected_rows() . " records deleted.");

			// delete unused tags
			$all_tag_ids = $this->tagtable->getUsedTagIdsAsString();
			$db->query("DELETE FROM x_kurse_tags WHERE tag_id NOT IN($all_tag_ids);");
			$db->query("DELETE FROM x_tags WHERE tag_id NOT IN($all_tag_ids);");
			$this->log($db->affected_rows() . " unused tags deleted.");
			
			// sync synonyms (should be done before calculating tag frequencies)
			$this->doSyncSynonyms();

			// TAG_FREQ: get the hash portal_tag_id => portal_id
			$portal_tag_ids = array(0=>1);
			reset($kurs2portaltag->all_portals);
			while( list($portalId, $values) = each($kurs2portaltag->all_portals) )
				$portal_tag_ids[ $values['portal_tag'] ] = 1; // $portalTagId may be 0
																				$this->statetable->updateUpdatestick();
			// TAG_FREQ: alle Tagfilter synchronisieren
			$cont			= true;
			$curr_portals	= array(0);
			$curr_tags		= array();
			$last_kurs_id	= 0;
			$result			= array();
			$today 			= strftime("%Y-%m-%d");
			
			$limit_i = 0;
			$limit_cnt = 200000;
			$sql = "SELECT t.tag_id, t.kurs_id 
					FROM x_kurse_tags t 
					LEFT JOIN x_kurse k ON k.kurs_id=t.kurs_id 
					WHERE (k.beginn>='$today') 
					ORDER BY kurs_id";  // -- dies beruecksichtigt nur die akt. kurse ... "SELECT tag_id, kurs_id FROM x_kurse_tags ORDER BY kurs_id"; wuerde auch die abgelaufenen kurse beruecksichtigen
										// Abfrage vor 13:55 30.01.2013: (k.beginn='0000-00-00' OR k.beginn>='$today')
			$db->query($sql . " LIMIT $limit_i, $limit_cnt"); // da die Abfrage sehr speicherintensiv ist, und die SQL-implementierung in DB_Admin ein ergebnis komplett einliest, hier ausnahmsweise Limitanweisungen, um speicher zu sparen.
			while( $cont )
			{
				if( !$db->next_record() )
				{
					$limit_i += $limit_cnt;
					$db->query($sql . " LIMIT $limit_i, $limit_cnt");
					if( !$db->next_record() )
						$cont = false; $db->Record['kurs_id'] = 0;  // force one for flush below!
				} 
					
				if( $last_kurs_id != $db->Record['kurs_id'] )
				{
					// flush tags ...
					for( $p = sizeof($curr_portals)-1; $p >= 0; $p-- )
					{
						for( $t = sizeof($curr_tags)-1; $t >= 0; $t-- )
						{
							$result[ $curr_portals[$p] ][ $curr_tags[$t] ] ++;
						}
					}
					
					// prepare for next
					$last_kurs_id = $db->Record['kurs_id'];
					$curr_portals = array(0);
					$curr_tags = array();
				}
				
				// collect tags
				$tag_id  = $db->Record['tag_id'];
				if( $portal_tag_ids[$tag_id] )
					$curr_portals[] = $tag_id;
				else
					$curr_tags[] = $tag_id;
			}
																				$this->statetable->updateUpdatestick();
			// TAG_FREQ: add the synonyms
			$rev_syn = array();
			$db->query("SELECT s.tag_id, s.lemma_id FROM x_tags_syn s LEFT JOIN x_tags t ON s.tag_id=t.tag_id WHERE NOT(t.tag_type&32);");
			while( $db->next_record() )
			{
				$rev_syn[ $db->f8('lemma_id') ][] = $db->f8('tag_id');
			}
																				$this->statetable->updateUpdatestick();			
			// TAG_FREQ: write the stuff
			$db->query("DELETE FROM x_tags_freq;");
			$portalIdFor0Out = false;
			reset($kurs2portaltag->all_portals);
			while( list($portalId, $values) = each($kurs2portaltag->all_portals) )
			{
				if( $values['einstellungen']['core'] == '20' )
				{
					// calculate the stats for the portal
					$portalTagId = $values['portal_tag'];
					if( $portalTagId && sizeof($result[$portalTagId]) )
					{
						$portalIdFor = $portalId;
					}
					else
					{
						$portalIdFor = 0;
						$portalTagId = 0;
					}
					
					// write the x_tags_freq table
					if( $portalIdFor != 0 || !$portalIdFor0Out )
					{
						$v = '';
						
						if( is_array($result[$portalTagId]) )
						{
							reset( $result[$portalTagId] );
							while( list($currTagId, $currFreq) = each($result[$portalTagId]) )
							{
								$v .= $v===''? '' : ', ';
								$v .= "($currTagId, $portalIdFor, $currFreq)";
								
								if( is_array($rev_syn[$currTagId]) )
								{
									for( $s = sizeof($rev_syn[$currTagId])-1; $s >= 0; $s-- )
									{
										$v .= $v===''? '' : ', ';										// these two lines will add the synonymes
										$v .= "({$rev_syn[$currTagId][$s]}, $portalIdFor, $currFreq)";	// to x_tags_freq - hiding, if needed, may happen in the viewing classes.
									}
								}
							}
						}
						
						if( $v != '' )
							$db->query("INSERT INTO x_tags_freq (tag_id, portal_id, tag_freq) VALUES $v;");
						
						if( $portalIdFor == 0 ) $portalIdFor0Out = true;
					}
																				$this->statetable->updateUpdatestick();
					// update the stats for the portal
					$counts = $kurs2portaltag->getPortalTagsCounts( $values['portal_tag'] );
					$values['einstcache']['stats.anzahl_kurse'] = $counts['anz_kurse'];
					$values['einstcache']['stats.anzahl_anbieter'] = $counts['anz_anbieter'];
					$values['einstcache']['stats.anzahl_durchfuehrungen'] = $counts['anz_durchf'];
					//$values['einstcache']['stats.tag_filter'] = $einstcache_tagfilter;
					$this->framework->cacheFlushInt($values['einstcache'], $portalId);
					//$this->log("stats for portal $portalId updated to anz_kurse=".$counts['anz_kurse'].'/anz_anbieter='.$counts['anz_anbieter'].'/anz_durchf='.$counts['anz_durchf']);

				}
			}
			$this->log("tag frequencies updated.");
			
		}
		
		// done
		$this->statetable->writeState('lastsync.kurse.global', $this->today_datetime);
	}
	
	/* main - see what to do
	 *************************************************************************/
	
	function render()
	{
		$overall_time = microtime(true);
		headerDoCache(0);
		header("Content-type: text/plain");

		$host = $_SERVER['HTTP_HOST'];
		
		// print common information
		$this->log(sprintf("sync script started, PHP version: %s", phpversion()));
		
		// check the apikey
		// the apikey must be set in the portal settings of the domain this script is executed on.
		if( $_GET['apikey'] != $this->framework->iniRead('apikey', 'none') )
		{
			$this->log("********** ERROR: $host: bad apikey. ");
			return;
		}
		
		// make sure, this script does not abort too soon
		set_time_limit(2*60*60 /*2 hours ...*/);
		ignore_user_abort(true);

		// use the following for debugging only ...
		$this->dbgCond = "";
		if( substr($host, -6)=='.localx' )
		{
			$this->dbgCond = " ORDER BY date_modified DESC LIMIT 500";
			$this->log("********** WARNING: $host: incomplete update forced!");
		}
		
		
		// allocate exclusive access
		if( !$this->statetable->allocateUpdatestick() ) { $this->log("********** ERROR: $host: cannot sync now, update stick in use, please try again in about 10 minutes."); return; }
		
					// see what to do ...
					if( isset($_GET['kurseFast']) )
					{
						$this->log("********** $host: starting kurseFast - if you do not read \"done.\" below, we're aborted unexpectedly and things may not work!");
						$this->doSyncKurse(false);
					}
					else if( isset($_GET['kurseSlow']) )
					{
						$this->log("********** $host: calling alle_freischaltungen_ueberpruefen()");
						alle_freischaltungen_ueberpruefen();
						
						$this->log("********** $host: starting kurseSlow - if you do not read \"done.\" below, we're aborted unexpectedly and things may not work!");
						$this->doSyncKurse(true);
					}
					else
					{
						$this->log("********** ERROR: $host: unknown syncing option, use one of \"kurseFast\" or \"kurseSlow\".");
					}

					// cleanup caches (not recommended as this may result in heavy server load after a sync)
					if( SEARCH_CACHE_ITEM_LIFETIME_SECONDS == 0 )
					{
						$this->cleanupSearchCache();
						$this->log("search cache cleaned.");
					}
					else
					{
						$this->log(sprintf("search cache refresh after %d seconds.", SEARCH_CACHE_ITEM_LIFETIME_SECONDS));
					}

					// if this is not reached the script may be limited by some server limits: Script-Time, CPU-Time, Memory ...
					// plesae note that kursportal.domainfactory-kunde.de has 5 minutes CPU-time whoile the other domains have only one.
					$this->log(sprintf('done. max. memory: %1.1f MB, time: %1.0f minutes', memory_get_peak_usage(true)/1048576, (microtime(true)-$overall_time)/60));
					
		// release exclusive access
		$this->statetable->releaseUpdatestick();
	}
};

