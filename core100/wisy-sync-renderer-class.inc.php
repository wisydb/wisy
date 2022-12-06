<?php if (!defined('IN_WISY')) die('!IN_WISY');


date_default_timezone_set('Europe/Berlin');

define('DEBUG', false);

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
	function __construct(&$framework)
	{
		$this->framework 	= &$framework;
		$this->db 			= new DB_Admin;
	}

	// read / write / lock
	function readState($key, $default = '')
	{
		$ret = $default;
		$sql = "SELECT svalue FROM x_state WHERE skey='" . addslashes($key) . "';";
		$this->db->query($sql);
		if ($this->db->next_record())
			$ret = $this->db->fs('svalue');
		return $ret;
	}
	function writeState($key, $val)
	{
		$this->db->query("SELECT svalue FROM x_state WHERE skey='" . addslashes($key) . "';");
		if (!$this->db->next_record())
			$this->db->query("INSERT INTO x_state (skey) VALUES('" . addslashes($key) . "');");
		$this->db->query("UPDATE x_state SET svalue='" . addslashes($val) . "' WHERE skey='" . addslashes($key) . "';");
	}
	function lock($lock)
	{
		if ($lock)
			return $this->db->lock('x_state');
		else
			return $this->db->unlock();
	}

	// update stick handling
	function allocateUpdatestick()
	{
		if (!$this->lock(true)) {
			return false;
		}
		$updatestick = $this->readState('updatestick', '0000-00-00 00:00:00');
		if ($updatestick > strftime("%Y-%m-%d %H:%M:00", time() - 3 * 60) /*wait 3 minutes*/) {
			$this->lock(false);
			return false;
		}
		$this->updatestick_datetime = strftime("%Y-%m-%d %H:%M:00");
		$this->writeState('updatestick', $this->updatestick_datetime);

		$what = '';
		if (isset($_GET['kurseSlow']))
			$what = "kurseSlow";
		if (isset($_GET['kurseFast']))
			$what = "kurseFast";

		$this->writeState('what', $what);

		$this->lock(false);
		return true;
	}
	function updateUpdatestick()
	{
		if ($this->updatestick_datetime != strftime('%Y-%m-%d %H:%M:00')) {
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
		$this->writeState('what', '');
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
	function __construct()
	{
		$this->db = new DB_Admin;
		$this->tags = array();
	}

	function lookup($tag_name)
	{
		// lookup a tag and return its ID; if unexistant, 0 is returned
		$tag_name = trim($tag_name);

		if (!isset($this->tags[$tag_name])) {
			$this->db->query("SELECT tag_id, tag_type, tag_help, tag_descr FROM x_tags WHERE tag_name='" . addslashes($tag_name) . "';");
			if ($this->db->next_record()) {
				$this->tags[$tag_name] = array(intval($this->db->f('tag_id')), intval($this->db->f('tag_type')), intval($this->db->f('tag_help')), $this->db->fs('tag_descr'));
			} else {
				$this->tags[$tag_name] = array(0, 0, 0); // even add "not existant" to the cache
			}
		}

		return $this->tags[$tag_name][0];
	}

	function lookupOrInsert($tag_name, $tag_type, $tag_help = 0, $tag_descr = '', $tag_eigenschaften = '-1')
	{
		require_once("admin/lib/soundex/x3m_soundex_ger.php");

		// lookup a tag and return its ID; if unexistant, the tag is inserted
		$tag_name = trim($tag_name);

		if ($tag_name == '' || strpos($tag_name, ',') !== false || strpos($tag_name, ':') !== false) {
			return 0; // error
		}

		if ($this->lookup($tag_name) == 0) {
			$tag_soundex = soundex_ger($tag_name);
			$tag_metaphone = metaphone($tag_name);

			// Wird ueber Name nachgeschaut, weil ID nicht vorhanden (zentraler Ansatz aber sinnvoll) und Name soll eh eindeutig sein bei SW!
			$this->db->query("SELECT eigenschaften FROM stichwoerter WHERE stichwort='" . addslashes($tag_name) . "'");
			$this->db->next_record();
			$tag_eigenschaften = $this->db->f('eigenschaften');
			$tag_eigenschaften = ($tag_eigenschaften) ? $tag_eigenschaften : $tag_eigenschaften = -1; // if($tag_eigenschaften == -1) ... // Orte etc. ?

			$sql = "INSERT INTO x_tags (tag_name, tag_eigenschaften, tag_descr, tag_type, tag_help, tag_soundex, tag_metaphone) VALUES ('" . addslashes($tag_name) . "', " . addslashes($tag_eigenschaften) . ", '" . addslashes($tag_descr) . "', $tag_type, $tag_help, '$tag_soundex', '$tag_metaphone')";
			$this->db->query($sql);

			$insert_id = $this->db->insert_id();
			$this->tags[$tag_name] = array(intval($insert_id), $tag_type, $tag_help, $tag_descr);

			$sql = "SELECT * FROM x_tags WHERE tag_id = " . $insert_id . ";";
			$this->db->query($sql);
			$ergebnis = "";
			while ($this->db->next_record())
				$ergebnis .= "\n " . $this->db->f('tag_id') . ": " . $this->db->f('tag_name');
		} else {
			// if needed, correct tag_type/tag_help - this may happen if we add new tag types, in the normal db usage, these values normally do not change.
			if (
				$this->tags[$tag_name][1] != $tag_type
				|| $this->tags[$tag_name][2] != $tag_help
				|| $this->tags[$tag_name][3] != $tag_descr
			) {
				$this->db->query("UPDATE x_tags SET tag_eigenschaften=$tag_eigenschaften, tag_type=$tag_type, tag_help=$tag_help, tag_descr='" . addslashes($tag_descr) . "' WHERE tag_id=" . $this->tags[$tag_name][0]);
				$this->tags[$tag_name][1] = $tag_type;
				$this->tags[$tag_name][2] = $tag_help;
				$this->tags[$tag_name][3] = $tag_descr;
			}
		}

		return $this->tags[$tag_name][0];
	}

	function getUsedTagIdsAsString()
	{
		// returns a comma-separated string with all tag IDs
		// (the function returns the tag IDs *really* used by a lookup() or by a lookupOrInsert() call, not the complete database,
		// in fact, the returned list ist used to delete old records from the database)
		$ret = '0';
		reset($this->tags);
		foreach ($this->tags as $tag_name => $tag_param)
			$ret .= ', ' . $tag_param[0];

		// add all synonyms and all portal tags; they should be preserved as the function is used to delete tags
		$this->db->query("SELECT tag_id FROM x_tags WHERE tag_type & 64;");
		while ($this->db->next_record())
			$ret .= ', ' . $this->db->f('tag_id');

		return $ret;
	}
}




/* ATTR2TAG_CLASS --
 * Diese Klasse konvertiert eine Attribut-ID in eine Tag-Id;
 * Modifizierte Tabellen: x_tags
 *****************************************************************************/

class ATTR2TAG_CLASS
{
	function __construct(&$tagtable, $table, $field)
	{
		$this->db = new DB_Admin;
		$this->tagtable = &$tagtable;

		$this->table 	= $table;
		$this->field 	= $field;
		$this->addField	= '';
		$this->addWhere	= '';

		$this->cache	= array();

		if ($table == 'themen') {
			// ... special preparations for "themen"
			$this->addField = ', kuerzel_sorted';
		} else if ($this->table == 'stichwoerter') {
			// ... special preparations for "stichwoerter"
			global $hidden_stichwort_eigenschaften;
			$hidden_stichwort_eigenschaften_plus_synonyme = $hidden_stichwort_eigenschaften | 32 | 64; // hier die Bits nicht addieren: Dies fuehrt schnell dazu, das Werte doppelt addiert werden ...
			$this->addWhere = " AND (eigenschaften & $hidden_stichwort_eigenschaften_plus_synonyme)=0 ";
			$this->addField = ', eigenschaften, glossar, zusatzinfo';
		} else if ($this->table == 'anbieter') {
			// ... special preparations for "anbieter"
			$this->addField = ', suchname_sorted, typ';
		}
	}

	function lookupNames($attr_id, &$retArray)
	{
		// returns the names belonging to the given attribute ID as an array,
		// (only for hierarchical themen, more than one name is returned)

		if (!isset($this->cache[$attr_id])) {
			$sql = "SELECT $this->field $this->addField FROM $this->table WHERE id=$attr_id $this->addWhere;";
			$this->db->query($sql);
			if (!$this->db->next_record())
				return; // error - id not found

			// find out current tag_type
			$curr_tag_type = 0;
			$curr_tag_help = 0;
			$curr_tag_descr = '';
			if ($this->table == 'stichwoerter') {

				$curr_tag_type = intval($this->db->f('eigenschaften')) & (1 + 2 + 4 + 8 + 16 + 1024 + 32768 + 65536) /*flags, s.o.*/;
				$curr_tag_help = intval($this->db->f('glossar'));
				$curr_tag_descr = $this->db->fs('zusatzinfo');
			} else if ($this->table == 'anbieter') {

				$curr_tag_type = 256 					    // maintype: Anbieter
					+		(intval($this->db->f('typ')) << 16);	// subtype:  0=Anbieter,  1=Trainer, 2=Betratungsstelle, 64=Namensverweisung/Synonym

			}

			// do insert
			$this->cache[$attr_id]['names'] = array();
			$this->cache[$attr_id]['names'][] = array(g_sync_removeSpecialChars($this->db->fs($this->field)), $curr_tag_type, $curr_tag_help, $curr_tag_descr);
			if ($this->table == 'themen') {
				$attr_kuerzel = $this->db->fs('kuerzel_sorted');
				$sql = '';
				for ($slen = strlen($attr_kuerzel) - 10; $slen > 0; $slen -= 10) {
					$parent_kuerzel = substr($attr_kuerzel, 0, $slen);
					$sql .= $sql == '' ? "SELECT thema FROM themen WHERE kuerzel_sorted IN (" : ',';
					$sql .= "'" . $parent_kuerzel . "'";
				}

				if ($sql != '') {
					$sql .= ');';
					$this->db->query($sql);
					while ($this->db->next_record()) {
						$this->cache[$attr_id]['names'][] = array(g_sync_removeSpecialChars($this->db->fs('thema')), $curr_tag_type, 0);
					}
				}
			} else if ($this->table == 'anbieter') {
				$this->cache[$attr_id]['sorted'] = $this->db->fs('suchname_sorted');
			}
		}

		$retArray = $this->cache[$attr_id]['names'];
	}

	function lookupTagIds($attr_id, &$retArray)
	{
		$names = array();
		$this->lookupNames($attr_id, $names);
		for ($i = 0; $i < sizeof((array) $names); $i++) {
			$id = $this->tagtable->lookupOrInsert($names[$i][0], $names[$i][1], $names[$i][2], $names[$i][3]);
			if ($id != 0)
				$retArray[] = $id;
		}
	}

	function lookupSorted($attr_id)
	{
		$this->lookupNames($attr_id, $dummy); // lookupNames() loads the attributes implicitly ...
		return $this->cache[$attr_id]['sorted'];
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

	function __construct(&$framework, &$tagtable, &$statetable, $alle_kurse_str)
	{
		$db = new DB_ADMIN;
		$db2 = new DB_ADMIN;
		$this->framework = $framework;

		// nachsehen, in welchen portalen, die kurse von $alle_kurse_str enthalten sind 
		$this->all_portals = array();
		$this->portaltags = array();
		$this->plzfilter = array();
		$eql2sql = new EQL2SQL_CLASS('kurse');

		$db->query("SELECT id, einstellungen, einstcache, filter FROM portale WHERE status=1;");
		while ($db->next_record()) {
			$portal_id		= $db->f('id');
			$portal_tag		= 0;
			$einstellungen	= explodeSettings($db->fs('einstellungen'));
			$einstcache		= explodeSettings($db->fs('einstcache'));
			$filter			= explodeSettings($db->fs('filter'));

			if ($filter['stdkursfilter'] != '') // -- ignore core (2.0) setting, this was to distinguish between WISY 1.0 and WISY 2.0; nowadays we have other core version numbers
			{
				$portal_tag = $tagtable->lookupOrInsert(".portal$portal_id", 0);

				$sql = $eql2sql->eql2sql($filter['stdkursfilter'], 'id', '(kurse.id IN (' . $alle_kurse_str . '))', '');

				$db2->query($sql);

				echo sprintf("\n... lade %d Kurse fuer Portal %d ...\n", $db2->num_rows(), $portal_id);
				flush();
				$statetable->updateUpdatestick();

				while ($db2->next_record()) {
					$this->portaltags[$db2->f('id')][] = $portal_tag;
				}

				if ($einstellungen['durchf.plz.hardfilter']) {
					$this->plzfilter[$portal_tag] = &createWisyObject('WISY_PLZFILTER_CLASS', $this->framework, $einstellungen);
				}
			}

			$this->all_portals[$portal_id] = array('einstellungen' => $einstellungen, 'einstcache' => $einstcache, 'filter' => $filer, 'portal_tag' => $portal_tag);
		}

		if (DEBUG) {
			echo "Kurse fuer Portale geladen. \n";
			flush();
		}

		// Delete zipped .css
		$db->query("UPDATE portale SET css_gz =''; ");
	}

	function getPortalTagsAndIncCounts($kurs_id, &$tag_ids, $anbieter_id, $anz_durchf, $d_plz, $d_has_unset_plz)
	{
		for ($i = sizeof((array) $this->portaltags[$kurs_id]) - 1; $i >= 0; $i--) {
			// der kurs ist in diesem portal ...
			$portal_tag_id = $this->portaltags[$kurs_id][$i];

			// ist der kurs auch ueber den PLZ-Fiter ausgewaehlt? wenn nicht, soll er nicht in diesem Portal erscheinen
			if (is_object($this->plzfilter[$portal_tag_id])) {
				if ($d_has_unset_plz || $this->plzfilter[$portal_tag_id]->is_valid_plz_in_hash($d_plz)) {
					//echo "OK: $kurs_id<br />";
				} else {
					//echo "SKIPPED: $kurs_id for $portal_tag_id\n";
					continue;
				}
			}

			// portal tag zurueckgeben
			$tag_ids[] = $portal_tag_id;

			// anzahlen fuer dieses portal erhoehen (nur wenn es auch durchfuehrungen gibt)
			if ($anz_durchf) {
				$this->portal_tags_anz_anbieter[$portal_tag_id][$anbieter_id] = 1;
				$this->portal_tags_anz_kurse[$portal_tag_id]++;
				$this->portal_tags_anz_durchf[$portal_tag_id] += $anz_durchf;
			}
		}

		// anzahlen portal ohne filter erhoehen (nur wenn es auch durchfuehrungen gibt)
		if ($anz_durchf) {
			$this->portal_tags_anz_anbieter[0][$anbieter_id] = 1;
			$this->portal_tags_anz_kurse[0]++;
			$this->portal_tags_anz_durchf[0] += $anz_durchf;
		}
	}

	function getPortalTagsCounts($portal_tag_id)
	{
		return array(
			'anz_anbieter'	=>	sizeof((array) $this->portal_tags_anz_anbieter[$portal_tag_id]),
			'anz_kurse'		=>	intval($this->portal_tags_anz_kurse[$portal_tag_id]),
			'anz_durchf'	=>	intval($this->portal_tags_anz_durchf[$portal_tag_id]),
		);
	}
}


/* WISY_SYNC_RENDERER_CLASS
 *****************************************************************************/

class WISY_SYNC_RENDERER_CLASS
{
	public $par;
	public $curl_session;

	function __construct(&$framework, $param)
	{

		// include EQL
		require_once('admin/classes.inc.php');
		require_once("admin/lang.inc.php");
		require_once("admin/eql.inc.php");
		require_once("admin/config/codes.inc.php");
		require_once('admin/config/trigger_kurse.inc.php');

		require_lang('lang/edit');
		$db = new DB_Admin;

		// setup vars
		$this->framework		= &$framework;
		$this->statetable		= new WISY_SYNC_STATETABLE_CLASS($this->framework);
		$this->tagtable			= new TAGTABLE_CLASS();
		$this->themen2tag   	= new ATTR2TAG_CLASS($this->tagtable, 'themen', 		'thema');
		$this->stichw2tag   	= new ATTR2TAG_CLASS($this->tagtable, 'stichwoerter',	'stichwort');
		$this->anbieter2tag 	= new ATTR2TAG_CLASS($this->tagtable, 'anbieter',		'suchname');
		$this->weekdays			= array('Montags', 'Dienstags', 'Mittwochs', 'Donnerstags', 'Freitags', 'Samstags', 'Sonntags');
		$this->tagescodes		= array(''/*0*/, 'Ganztags'/*1*/, 'Vormittags'/*2*/, 'Nachmittags'/*3*/, 'Abends'/*4*/, 'Wochenende'/*5*/);
		$this->today_datetime   = strftime("%Y-%m-%d %H:%M:%S");
		$this->today_datenotime = substr($this->today_datetime, 0, 10);

		// create hash stichwort => übergeordneteStichwörter
		$this->verweis2 = array();
		$db->query("SELECT primary_id, attr_id FROM stichwoerter_verweis2 LEFT JOIN stichwoerter ON attr_id=id WHERE eigenschaften&(32|64)=0;");
		while ($db->next_record()) {
			$this->verweis2[$db->f('attr_id')][] = $db->f('primary_id');
		}

		$this->flatenArray($this->verweis2);
	}


	protected function flatenArray(&$ids)
	{
		// function expects an array as $id => $super_ids
		// for each $id it then scans all $super_ids and checks if they exists as a $id - if so, these $super_ids are added to the original $id, too.
		// recursive function.
		$this->in_work = array();
		foreach ($ids as $id => $super_ids) {
			$this->flatenArray__($ids, $id);
		}
	}
	protected function flatenArray__(&$ids, $id)
	{
		if ($this->in_work[$id]) {
			return;
		}
		$this->in_work[$id] = 1; // avoid dead lock and speed up things
		foreach ($ids[$id] as $super_id) {
			if (is_array($ids[$super_id])) {
				$this->flatenArray__($ids, $super_id);
				for ($k = 0; $k < sizeof((array) $ids[$super_id]); $k++) {
					$to_add = $ids[$super_id][$k];
					if (!in_array($to_add, $ids[$id])) {
						$ids[$id][] = $to_add;
					}
				}
			}
		}
	}


	function log($str)
	{
		date_default_timezone_set('Europe/Berlin');

		echo "[" . date("d.m.Y") . " - " . date("H:i:s") . "] " . $str . "\n";
		flush();
		$this->framework->log('sync', $str);
	}

	function cleanupSearchCache()
	{
		$dbCache = &createWisyObject('WISY_CACHE_CLASS', $this->framework, array('table' => 'x_cache_search'));
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
		while ($db->next_record()) {
			$synonym = g_sync_removeSpecialChars($db->fs('stichwort'));
			$synonym_id = intval($db->f('id'));
			$eigenschaften = intval($db->f('eigenschaften'));
			$dest_found = false;

			$db2->query("SELECT stichwort FROM stichwoerter LEFT JOIN stichwoerter_verweis v ON id=v.attr_id WHERE v.primary_id=$synonym_id");
			while ($db2->next_record()) {
				$cur = g_sync_removeSpecialChars($db2->fs('stichwort'));
				$cur_id = $this->tagtable->lookup($cur);
				if ($cur_id) {
					$dest_found = true;
					$tableValues[] = array($synonym, $cur_id);
				}
			}

			if ($dest_found)
				$insertValues[] = array($synonym, $eigenschaften == 32 ? 64 + 32 : 64); // !!! in "tag_type" gilt: das Synonym-Bit ist immer gesetzt, das Versteckte-Synonym-Bit kommt optional dazu
		}

		$this->statetable->updateUpdatestick();

		// collect Anbieter / Namensverweise
		$db->query("SELECT suchname, id FROM anbieter WHERE typ=262144 AND freigeschaltet=1"); // neue Namensverweisung (ehemals 64 = Synonym)
		while ($db->next_record()) {
			$synonym = g_sync_removeSpecialChars($db->fs('suchname'));

			$synonym_id = intval($db->f('id'));
			$dest_found = false;

			$db2->query("SELECT suchname FROM anbieter LEFT JOIN anbieter_verweis v ON id=v.attr_id WHERE v.primary_id=$synonym_id");
			while ($db2->next_record()) {
				$cur = g_sync_removeSpecialChars($db2->fs('suchname'));
				$cur_id = $this->tagtable->lookup($cur);
				if ($cur_id) {
					$dest_found = true;
					$tableValues[] = array($synonym, $cur_id);
				}
			}

			if ($dest_found)
				$insertValues[] = array($synonym, 262144);
		}

		// Versteckte Namensverweise
		$db->query("SELECT suchname, id FROM anbieter WHERE typ=65 AND freigeschaltet=1;"); // 131072
		while ($db->next_record()) {
			$synonym = g_sync_removeSpecialChars($db->fs('suchname'));

			$synonym_id = intval($db->f('id'));
			$dest_found = false;

			$db2->query("SELECT suchname FROM anbieter LEFT JOIN anbieter_verweis v ON id=v.attr_id WHERE v.primary_id=$synonym_id");
			while ($db2->next_record()) {
				$cur = g_sync_removeSpecialChars($db2->fs('suchname'));
				$cur_id = $this->tagtable->lookup($cur);
				if ($cur_id) {
					$dest_found = true;
					$tableValues[] = array($synonym, $cur_id);
				}
			}

			if ($dest_found)
				$insertValues[] = array($synonym, 65); // 131072
		}

		$this->statetable->updateUpdatestick();

		// write all synonyms
		$db->query("DELETE FROM x_tags WHERE tag_type & 64;"); // Synonym
		$db->query("DELETE FROM x_tags WHERE tag_type & 262144;"); // Anbieter-Namensverweisung
		$db->query("DELETE FROM x_tags WHERE tag_type = 65;"); // Versteckte Anbieter-Namensverweisung // "=" weil & 131072 (65) sonst auch Beratungsstellen (131328) l√∂scht
		for ($i = 0; $i < sizeof((array) $insertValues); $i++) {
			$this->tagtable->lookupOrInsert($insertValues[$i][0], $insertValues[$i][1]);
		}

		$this->statetable->updateUpdatestick();

		// create table to show where the synonyms link to
		$db->query("DELETE FROM x_tags_syn;");
		$values = '';
		for ($t = 0; $t < sizeof((array) $tableValues); $t++) {
			$syn_id = $this->tagtable->lookup($tableValues[$t][0]);
			if ($syn_id) {
				$values .= $values === '' ? '' : ', ';
				$values .= "($syn_id, {$tableValues[$t][1]})";
			} else {
				$this->log("ERROR: synonym $syn_id not found for {$tableValues[$t][0]} - {$tableValues[$t][1]}");
			}
		}
		$this->statetable->updateUpdatestick();
		if ($values != '') {
			$sql = "INSERT INTO x_tags_syn (tag_id, lemma_id) VALUES $values";
			$db->query($sql);
		}

		$this->log(sprintf("%s synonyms checked.", sizeof((array) $insertValues)));
	}


	/* syncing kurse
	 *************************************************************************/

	function doSyncKurse($deepupdate)
	{
		$db = new DB_Admin;
		$db2 = new DB_Admin;
		$db3 = new DB_Admin;
		$db_anbieter = new DB_Admin;

		global $geocode_errors_file;

		$updateLatlng = $deepupdate;
		if ($updateLatlng) {
			$geo_protocol_file = $geocode_errors_file;
			$live_geocode_max = 5500; // mapquest: free account = 15.000 / month
			$is_geocode_day = (intval(date('d')) === 5 || intval(date('d')) === 15 || intval(date('d')) === 25); // update on 5th, 15th and 25th day of the month
			$GLOBALS['geocode_called'] = 0;
			$geocoding_total_s = 0.0;
			$geocoded_addresses = 0;
			$geocoded_addresses_ext = 0;
			$non_geocoded_addresses_limit = 0;
			$geocoded_addresses_live = array();
			$nongeocoded_addresses = array();
			$geocoder = &createWisyObject('WISY_OPENSTREETMAP_CLASS', $this->framework);
		}

		// find out the date to start with syncing
		if ($deepupdate)
			$lastsync = '0000-00-00 00:00:00';
		else
			$lastsync = $this->statetable->readState('lastsync.kurse.global', '0000-00-00 00:00:00');

		// Kurse: (nur) in Vorbereitung
		$this->log("Bearbeite Kurse (nur) in Vorbereitung");
		$db4 = new DB_ADMIN;
		$sql = "SELECT id, titel FROM kurse WHERE freigeschaltet=0 $this->dbgCond;";
		if (DEBUG) {
			echo "Kurs-Ermittlung- in Vorbereitung -SQL:\n";
			echo $sql . "\n";
		}

		$db4->query($sql);

		$kurse4_cnt = $db4->ResultNumRows;
		if (DEBUG) {
			echo "Kurse in Vorbereitung: " . $kurse_cnt . "\n\n";
		}

		// go through all kurse in Vorbereitung modified since $lastsync
		$kurs4_cnt = 0;
		while ($db4->next_record()) {
			$kurs4_cnt++;
			$kurs_id 		= intval($db4->fs('id'));
			if (($kurs4_cnt % 100) == 0) {
				echo "\n" . date("Y-m-d H:i:s") . "... $kurs4_cnt ff ...\n";
				$this->statetable->updateUpdateStick();
			}

			$kurs_titel = $db4->fs('titel');
			update_titel_sorted($kurs_id, $kurs_titel); // in admin/config/trigger_kurse.inc.php
		}

		$this->log("Done: " . $kurs4_cnt . " Kurse in Vorbereitung.");


		// Kurse: Freigegeben, Abgelaufen, Dauerhaft

		// load all kurse modified since $lastsync
		$sql = "SELECT id, titel, freigeschaltet, thema, anbieter, date_modified, bu_nummer, fu_knr, azwv_knr FROM kurse WHERE date_modified>='$lastsync' AND (freigeschaltet=1 OR freigeschaltet=4 OR freigeschaltet=3) $this->dbgCond;";
		if (DEBUG) {
			echo "Kurs-Ermittlung-SQL:\n";
			echo $sql . "\n";
		}
		$db->query($sql);

		$alle_kurse_str = '0';
		$kurse_cnt = $db->ResultNumRows;
		echo "Kurse-Cnt vorher: " . $kurse_cnt . "\n\n";
		for ($i = 0; $i < $kurse_cnt; $i++)
			$alle_kurse_str .= ', ' . $db->Result[$i]['id'];

		$kurs2portaltag = new KURS2PORTALTAG_CLASS($this->framework, $this->tagtable, $this->statetable, $alle_kurse_str);

		echo "\nLade Kurse.\n";

		// go through all kurse modified since $lastsync
		$kurs_cnt = 0;
		while ($db->next_record()) {
			$kurs_id 		= intval($db->fs('id'));
			if (($kurs_cnt % 100) == 0) {
				echo "\n" . date("Y-m-d H:i:s") . "... $kurs_cnt ff ...\n";
				$this->statetable->updateUpdateStick();
			}

			$kurs_titel = $db->fs('titel');
			update_titel_sorted($kurs_id, $kurs_titel); // in admin/config/trigger_kurse.inc.php

			$anbieter_id	= intval($db->fs('anbieter'));
			if ($anbieter_id <= 0) {
				$this->log("ERROR: kein Anbieter angegeben fuer Kurs ID $kurs_id.");
			}
			$freigeschaltet	= intval($db->fs('freigeschaltet'));

			// convert thema, stichw, anbieter etc. to tag IDs
			$tag_ids = array();
			$this->themen2tag->lookupTagIds(intval($db->f('thema')),    $tag_ids);
			$this->anbieter2tag->lookupTagIds($anbieter_id, $tag_ids);
			$db2->query("SELECT attr_id FROM kurse_stichwort WHERE primary_id=$kurs_id");
			$all_stichwort_ids = array();
			while ($db2->next_record()) {
				$stichwort_id = intval($db2->f('attr_id'));
				$all_stichwort_ids[] = $stichwort_id;
				$this->stichw2tag->lookupTagIds($stichwort_id, $tag_ids);
			}

			if (DEBUG) echo "Ende: convert-Thema\n";

			$db2->query("SELECT attr_id FROM anbieter_stichwort WHERE primary_id=$anbieter_id;");
			while ($db2->next_record()) {
				$stichwort_id = intval($db2->f('attr_id'));
				$all_stichwort_ids[] = $stichwort_id;
				$this->stichw2tag->lookupTagIds($stichwort_id, $tag_ids);
			}

			if (DEBUG) echo "Ende: Anbieter-SW\n";

			// spezielle Nummern erzeugen spezielle Tags
			// siehe hierzu die Anmerkungen in wisy-durchf-class.inc.php bei [1]
			if ($db->f('bu_nummer') != '') {
				$tag_id = $this->tagtable->lookupOrInsert('Bildungsurlaub', 2); // foerderungsart
				if ($tag_id) $tag_ids[] = $tag_id;
			}

			if ($db->f('fu_knr') != '') {
				$tag_id = $this->tagtable->lookupOrInsert('Fernunterricht', 0); // sachstichw
				if ($tag_id) $tag_ids[] = $tag_id;
			}

			if ($db->f('azwv_knr') != '') {
				$tag_id = $this->tagtable->lookupOrInsert('Bildungsgutschein', 2); // foerderungsart
				if ($tag_id) $tag_ids[] = $tag_id;
			}

			if (DEBUG) echo "Add Auto-SW\n";

			// add AutoStichwort (d.h. zu einem Stichwort die uebergeordneten Stichwoerter automatisch hinzufuegen)
			// siehe hierzu die Anmerkungen in wisy-durchf-class.inc.php bei [1]
			foreach ($all_stichwort_ids as $stichwort_id) {
				if (is_array($this->verweis2[$stichwort_id])) {
					foreach ($this->verweis2[$stichwort_id] as $stichwort_id2) {
						$this->stichw2tag->lookupTagIds($stichwort_id2, $tag_ids);
					}
				}
			}

			if (DEBUG) echo "Ende: Add Auto-SW\n";

			// durchfuehrungen durchgehen ...
			$d_beginn			= array();
			$d_plz				= array();
			$d_has_unset_plz = false;
			$d_latlng			= array();
			$k_beginn			= '0000-00-00';
			$k_beginn_last		= '0000-00-00';
			$k_preis			= -1;
			$k_dauer			= 0;
			$k_kurstage			= 0;
			$k_tagescodes		= array();
			$bezirk     		= '';
			$ort_sortonly		= '';
			$ort_sortonly_secondary		= '';
			$db2->query("SELECT durchfuehrung.id AS did, durchfuehrung.user_grp, durchfuehrung.date_modified, strasse, plz, bezirk, ort, stadtteil, land, beginn, ende, beginnoptionen, dauer, dauer_fix, preis, kurstage, tagescode, zeit_von, zeit_bis, user_grp.shortname FROM durchfuehrung LEFT JOIN user_grp ON user_grp.id=durchfuehrung.user_grp LEFT JOIN kurse_durchfuehrung ON secondary_id=durchfuehrung.id WHERE primary_id=$kurs_id");
			$anz_durchf = 0;

			//$at_least_one_durchf = false;
			while ($db2->next_record()) {
				$db_anbieter->query("SELECT id FROM anbieter WHERE id=" . $anbieter_id . " AND freigeschaltet = 1");

				if ($db_anbieter->next_record()) {
					// Anbieter freigeschaltet
				} else {
					// ! Nicht freigeschaltet, trotz freigegebenem Kurs (!)
					$db_anbieter->query("DELETE FROM x_kurse_tags WHERE kurs_id=$kurs_id;");
					continue 2; // Anbieter nicht freigeschaltet => nicht inidizieren und zwar in Kurs-Schleife weiter (nicht nur DF)
				}

				// ... stadtteil / ort als Tag anlegen
				$strasse   		= trim($db2->fs('strasse'));
				$plz 			= trim($db2->fs('plz'));
				if ($plz == '00000') $plz = '';
				$bezirk       	= trim($db2->fs('bezirk'));
				$ort       		= trim($db2->fs('ort'));
				$stadtteil 		= trim($db2->fs('stadtteil'));
				$land 			= trim($db2->fs('land'));
				$beginn			= trim($db2->fs('beginn'));
				$ende			= trim($db2->fs('ende'));
				$df_id			= $db2->f('did');
				$dauer_fix 	= intval($db2->f('dauer_fix'));


				if (DEBUG) echo "DF-ID " . $df_id . ", ";

				$user_grp			= $db2->f('user_grp');
				$user_grp_shortname			= $db2->fs('shortname');
				$date_modified			= $db2->fs('date_modified');
				$d_kurstage  	= intval($db2->f('kurstage'));
				$bezirk         = $bezirk . " (Bezirk)";
				if (
					strpos($stadtteil, ',') === false && strpos($ort, ',') === false
					&& strpos($stadtteil, ':') === false && strpos($ort, ':') === false
					&& strpos($bezirk, ':') === false && strpos($bezirk, ':') === false    // do not add suspicious fields -- there is no reason for a comma in stadtteil/bezirk/ort
				) {
					// lookupOrInsert($tag_name, $tag_type, $tag_help = 0, $tag_descr = '', $tag_eigenschaften = '-1')
					$tag_id = $this->tagtable->lookupOrInsert($stadtteil, 512, 0, 'Stadtteil');
					if ($tag_id) $tag_ids[] = $tag_id;

					// lookupOrInsert($tag_name, $tag_type, $tag_help = 0, $tag_descr = '', $tag_eigenschaften = '-1')
					$tag_id = $this->tagtable->lookupOrInsert($bezirk, 512, 0, 'Bezirk');
					if ($tag_id) $tag_ids[] = $tag_id;

					if ($ort != 'Fernunterricht' && $ort != 'Fernstudium') // from manual entries
					{
						$tag_id = $this->tagtable->lookupOrInsert($ort, 512);
						if ($tag_id) $tag_ids[] = $tag_id;
					}
				}

				// stadtteil / ort zum sortieren aufbereiten
				if ($ort_sortonly == '' && $ort != '') {
					$ort_sortonly = "$ort $stadtteil";
					$ort_sortonly = g_eql_normalize_natsort($ort_sortonly);
					$db_orte = new DB_Admin;
					if (
						stripos($ort, "Fernunterricht") !== FALSE || stripos($stadtteil, "Fernunterricht") !== FALSE || stripos($strasse, "Fernunterricht") !== FALSE
						|| stripos($ort, "Fernstudium") !== FALSE || stripos($stadtteil, "Fernstudium") !== FALSE || stripos($strasse, "Fernstudium") !== FALSE
					) {
						$sql = "INSERT INTO	x_kurse_orte SET kurs_id=$kurs_id, ort='" . $ort . "', ort_sortonly='zzz_fernstudium (" . $df_id . ")'"; // ort=... obsolete?
					} else {
						$sql = "INSERT INTO	x_kurse_orte SET kurs_id=$kurs_id, ort='" . $ort . "', ort_sortonly='" . $ort_sortonly . " (" . $df_id . ")'"; // ort=... obsolete?
					}
					$db_orte->query($sql);
				} elseif ($ort_sortonly != '' && $ort != '') {
					$ort_sortonly_secondary_tmp = g_eql_normalize_natsort("$ort $stadtteil");
					if ($ort_sortonly != $ort_sortonly_secondary_tmp && @strpos($ort_sortonly_secondary, $ort_sortonly_secondary_tmp) === FALSE) {
						$ort_sortonly_secondary .= "," . $ort_sortonly_secondary_tmp; // obsolete
						if (
							stripos($ort, "Fernunterricht") !== FALSE || stripos($stadtteil, "Fernunterricht") !== FALSE || stripos($strasse, "Fernunterricht") !== FALSE
							|| stripos($ort, "Fernstudium") !== FALSE || stripos($stadtteil, "Fernstudium") !== FALSE || stripos($strasse, "Fernstudium") !== FALSE
						) {
							$sql = "INSERT INTO	x_kurse_orte SET kurs_id=$kurs_id, ort='" . $ort . "', ort_sortonly='zzz_fernstudium (" . $df_id . ")#'"; // ort=... obsolete
						} else {
							$sql = "INSERT INTO	x_kurse_orte SET kurs_id=$kurs_id, ort='" . $ort . "', ort_sortonly='" . $ort_sortonly_secondary_tmp . " (" . $df_id . ")#'"; // ort=... obsolete
						}
						$db_orte->query($sql);
					}
				}
				// $db_orte->free();

				if (
					stripos($ort, "Fernunterricht") !== FALSE || stripos($stadtteil, "Fernunterricht") !== FALSE || stripos($strasse, "Fernunterricht") !== FALSE
					|| stripos($ort, "Fernstudium") !== FALSE || stripos($stadtteil, "Fernstudium") !== FALSE || stripos($strasse, "Fernstudium") !== FALSE
				) {
					$ort_sortonly == "zzz"; // put Fernunterricht / Fernstudium at the end when sorting by city // obsolete
				}

				// plz sammeln
				if ($plz != '') {
					$d_plz[$plz] = 1;
				} else {
					$d_has_unset_plz = true;
				}

				// latlng sammeln
				if ($updateLatlng) {
					$geocoding_start_s = microtime(true);

					if (DEBUG) $this->log("geocode from Cache: " . $db2->Record['strasse'] . ", " . $db2->Record['ort'] . "\n");

					$temp = $geocoder->geocode2($db2->Record, false); // checks perm and search (temp) - caches, Umlaute being converted! Straße -> Strasse

					if (DEBUG) $this->log("Resultat =>" . print_r($temp, true) . "\n");

					if (!$temp['error']) {
						$d_latlng[intval($temp['lat'] * 1000000) . ',' . intval($temp['lng'] * 1000000)] = 1;
						$geocoded_addresses++;
					} else { // not in cache yet

						$done_key = md5(trim($db2->Record['strasse']) . trim($db2->Record['ort']));

						// try to live geocode
						if (
							!isset($geocoded_addresses_live[$done_key]) // only once per address
							&& ($this->framework->iniRead('nominatim.alternate.geocoder', '') == 1 && strlen($this->framework->iniRead('nominatim.url', '')) > 3) // and other than openstreetmap.org
						) {

							if ($geocoded_addresses_ext < $live_geocode_max && ($is_geocode_day)) {

								if (
									stripos($db2->Record['ort'], "Fernunterricht") === FALSE
									&& stripos($db2->Record['ort'], ".") === FALSE
									&& stripos($db2->Record['ort'], ",") === FALSE
									&& strlen($db2->Record['ort']) > 1
								) {

									$adress_str = $db2->Record['strasse'] . ", " . $db2->Record['ort'];
									$adress_check = $this->clean_address($adress_str);
									if ($adress_check["skip"]) {
										if (DEBUG) $this->log($adress_str);
									} else {

										if ($adress_check["changed"]) {
											if (DEBUG) $this->log("=> Geocoding changed");
											$addrArr = explode(",", $adress_check['address']);
											if (count($addrArr) == 2) {
												$db2->Record['strasse'] = $addrArr[0];
												$db2->Record['ort'] = $addrArr[1];
											} elseif (count($addrArr) == 3) {
												$db2->Record['strasse'] = $addrArr[1];
												$db2->Record['ort'] = $addrArr[2];
											}
										}

										$this->log("geocode from LIVE: " . $adress_str . "\n");
										usleep(100000); // 0,1s delay between ext. calls

										$temp = $geocoder->geocode2($db2->Record, true);

										$geocoded_addresses_ext++;

										if (!$temp['error']) {
											$d_latlng[intval($temp['lat'] * 1000000) . ',' . intval($temp['lng'] * 1000000)] = 1;
											if (DEBUG) $this->log("** OK ** von Live: " . $db2->Record['strasse'] . ", " . $db2->Record['ort'] . ", lat: " . $temp['lat'] . ", lng: " . $temp['lng'] . "\n");
											$geocoded_addresses_live[$done_key] = true;
											$geocoded_addresses++;
										} else {

											echo "** Fehler ** von Live: " . $temp['error'] . ", URL:" . $temp['url'] . "\n";

											$this->log("** Fehler ** von Live: " . $temp['error'] . ", URL:" . $temp['url'] . "\n");
											array_push($nongeocoded_addresses, array("error" => $temp['error'], "url" => $temp['url'], "adresse" => $db2->Record, "df_id" => $df_id, "kurs_id" => $kurs_id, "user_grp" => $user_grp, "user_grp_shortname" => $user_grp_shortname, "date_modified" => $date_modified));
											$geocoded_addresses_live[$done_key] = true;
										}
									}
								}
							} else {
								$geocoded_addresses_live[$done_key] = true;

								if ($is_geocode_day)
									$non_geocoded_addresses_limit++;
							}
						}
					}
					$geocoding_total_s += microtime(true) - $geocoding_start_s;
				}

				// alle beginndaten sammeln
				$temp = substr($beginn, 0, 10); // yyyy-mm-dd
				if ($temp != '0000-00-00') {
					$d_beginn[] = $temp;

					if ($temp >= $this->today_datenotime)
						$anz_durchf++;
				} else {
					$anz_durchf++;
				}
				//$at_least_one_durchf = true;

				$d_beginnoptionen = intval($db2->f('beginnoptionen'));
				if ($d_beginnoptionen & 512) {
					$tag_id = $this->tagtable->lookupOrInsert('Startgarantie', 0);
					if ($tag_id) $tag_ids[] = $tag_id;
				}

				// HACK: tagescode und dauer in Quelle berechnen
				// (dies sollte besser ausserhalb des Portals im Redaktionssystem passieren, aber hier ist es so schoen praktisch, da die Trigger sowieso aufgerufen werden)
				$write_back = '';
				$d_tagescode = berechne_tagescode($db2->f('zeit_von'), $db2->f('zeit_bis'), $d_kurstage);
				if ($d_tagescode != intval($db2->f('tagescode'))) {
					$write_back = " tagescode=$d_tagescode ";
				}

				$d_dauer = berechne_dauer($beginn, $ende);
				if ($d_dauer != intval($db2->f('dauer')) && !$dauer_fix) {
					$write_back = " dauer=$d_dauer ";
				}

				if ($write_back != '') {
					$sql = "UPDATE durchfuehrung SET $write_back WHERE id=" . $db2->f('did');
					$db3->query($sql);
				}

				// hoechste dauer setzen ("hoechste" hier willkuerlich, in 99.9% sind eh alle Angaben hierzu bei allen Druchfuehrungen gleich)
				if ($d_dauer > 0 && ($d_dauer > $k_dauer || $k_dauer == 0)) {
					$k_dauer = $d_dauer;
				}

				// hoechsten preis setzen ("hoechsten" wg. spam verhinderung, keine Ausnutzung besonderheiten des Systems)
				$d_preis = intval($db2->f('preis'));
				if ($d_preis != -1 && ($d_preis > $k_preis || $k_preis == -1)) {
					$k_preis = $d_preis;
				}

				// kurstage (wochentage) / tagescode (vormittags, nachmittags, ...) sammeln
				$k_kurstage |= $d_kurstage;
				$k_tagescodes[$d_tagescode] = 1;
			} // ende durchfuehrungen


			if (DEBUG) echo "Ende: Durchfuehrungen.\n";

			// portale-tags zu $tag_ids hinzufuegen, anzahlen erhoehen
			//if( $anz_durchf == 0 && $at_least_one_durchf ) // 21:29 01.10.2013 at_least_one_durchf stellt sicher, dass im Zweifelsfalle eher mehr gezaehlt wird als zu wenig, s. Mails mit Juergen
			//	$anz_durchf++;								 // 15:11 08.10.2013 das fuehrt zu zu hohen Zahlen in RLP und anderswo, wir lassen das so also sein...
			$kurs2portaltag->getPortalTagsAndIncCounts($kurs_id, $tag_ids, $anbieter_id, $anz_durchf, $d_plz, $d_has_unset_plz); // $tag_ids -> modified

			// kurstage (wochentage) / tagescode (vormittags, nachmittags, ...) setzen
			for ($i = 0; $i < 7; $i++) {
				if ($k_kurstage & (1 << $i)) {
					$tag_id = $this->tagtable->lookupOrInsert($this->weekdays[$i], 0);
					if ($tag_id) $tag_ids[] = $tag_id;
				}
			}
			reset($k_tagescodes);
			foreach (array_keys($k_tagescodes) as $code) {
				if ($this->tagescodes[$code] != '') {
					$tag_id = $this->tagtable->lookupOrInsert($this->tagescodes[$code], 0);
					if ($tag_id) $tag_ids[] = $tag_id;
				}
			}

			// fruehestmoegliches beginndatum setzen
			if (sizeof((array) $d_beginn)) {
				sort($d_beginn);
				for ($i = 0; $i < sizeof((array) $d_beginn); $i++) {
					$k_beginn = $d_beginn[$i];
					if ($k_beginn >= $this->today_datenotime)
						break;
				}

				// spaetestmoegliches beginndatum setzen
				for ($i = 0; $i < sizeof((array) $d_beginn); $i++) {
					if ($d_beginn[$i] >= $this->today_datenotime && $d_beginn[$i] >= $k_beginn_last)
						$k_beginn_last = $d_beginn[$i];
				}
			}

			if ($freigeschaltet == 1 || $freigeschaltet == 4) // 1 = freigegeben, 4 = dauerhaft
			{
				if ($k_beginn < $this->today_datenotime)
					$k_beginn = '9999-09-09';	// Any date in the future -- this may happen frequently on missing dates with given beginnoptionen
				// -- 11:23 26.04.2013 it also happens with the new Stichwort #315/Einstieg bis Kursende moeglich
			} else if ($freigeschaltet == 3)  // abgelaufen
			{
				if ($k_beginn >= $this->today_datenotime)
					$k_beginn = '0000-00-00'; // Any date in the past -- this should normally not happen, only if the kurs is valid normally but set to abgelaufen manually

				if ($k_beginn_last >= $this->today_datenotime)
					$k_beginn_last = '0000-00-00'; // Any date in the past -- this should normally not happen, only if the kurs is valid normally but set to abgelaufen manually
			}

			// fruehestmoeglichstes beginndatum korrigieren, falls dieses in der Vergangenheit liegt UND kurse die Eigentschaften "Beginn erfragen" etc. zugewiesen wurde
			//
			// if( $k_beginn < $this->today_datenotime && $k_beginnerfragenetc )
			// {
			// 	$k_beginn = '0000-00-00';
			// }

			// CREATE main search entry for this record (if not exist)
			$db2->query("SELECT begmod_hash, begmod_date FROM x_kurse WHERE kurs_id=$kurs_id;");
			if ($db2->next_record()) {
				$begmod_hash = $db2->f('begmod_hash');
				$begmod_date = $db2->f('begmod_date');
			} else {
				$db2->query("INSERT INTO x_kurse (kurs_id) VALUES ($kurs_id);");
				$begmod_hash = '';
				$begmod_date = $db->f('date_modified');
			}

			// "Beginnaenderungsdatum" aktualisieren
			$begmod_hash = explode(',', $begmod_hash);
			for ($i = 0; $i < sizeof((array) $d_beginn); $i++) {
				if (!in_array($d_beginn[$i], $begmod_hash)) {
					$begmod_date = $db->f('date_modified');
					break;
				}
			}
			$begmod_hash = implode(',', $d_beginn);


			if (DEBUG) echo "Update x_kurse Kurs: " . $kurs_id . ".\n";

			// UPDATE main search entry for this record
			$sql = "UPDATE	x_kurse
					SET 	beginn='$k_beginn'
					,		beginn_last='$k_beginn_last'
					,		dauer=$k_dauer
					,		preis=$k_preis
					,		anbieter_sortonly='" . $this->anbieter2tag->lookupSorted($anbieter_id) . "'
                    ,       bezirk='$bezirk'
					,		ort_sortonly='$ort_sortonly'
					,       ort_sortonly_secondary='" . trim($ort_sortonly_secondary, ",") . "'
					,		begmod_hash='$begmod_hash'
					,		begmod_date='$begmod_date'
					WHERE 	kurs_id=$kurs_id;";
			$db2->query($sql);

			// UPDATE tag table for this record
			$sql = '';
			$added = array();
			for ($t = 0; $t < sizeof($tag_ids); $t++) {
				$tag_id = $tag_ids[$t];
				if (!$added[$tag_id]) {
					$added[$tag_id] = true;
					$sql .= $sql == "" ? "INSERT INTO x_kurse_tags (kurs_id, tag_id) VALUES " : ", ";
					$sql .= "($kurs_id, $tag_id)";
				}
			}

			$db2->query("DELETE FROM x_kurse_tags WHERE kurs_id=$kurs_id;");
			if ($sql != '') {
				$db2->query($sql);
			}

			// UPDATE plz table for this record
			$sql = '';
			reset($d_plz);
			foreach (array_keys($d_plz) as $plz) {
				$sql .= $sql == "" ? "INSERT INTO x_kurse_plz (kurs_id, plz) VALUES " : ", ";
				$sql .= "($kurs_id, '" . addslashes($plz) . "')";
			}

			$db2->query("DELETE FROM x_kurse_plz WHERE kurs_id=$kurs_id;");
			if ($sql != '') {
				$db2->query($sql);
			}


			$sql = '';
			reset($d_latlng);
			foreach (array_keys($d_latlng) as $latlng) {
				$sql .= $sql == "" ? "INSERT INTO x_kurse_latlng (kurs_id, lat, lng) VALUES " : ", ";
				$sql .= "($kurs_id, " . addslashes($latlng) . ")";
			}

			$db2->query("DELETE FROM x_kurse_latlng WHERE kurs_id=$kurs_id;");
			if ($sql != '') {
				$db2->query($sql);
			}
			/* } */

			// next kurs
			$kurs_cnt++;
		}

		if (DEBUG) echo "Ende Kurse.\n";


		// put all distant learning courses at the end of search results sorted by city
		/* ! redundant? $db->query("UPDATE x_kurse SET ort_sortonly = REPLACE(ort_sortonly, 'fernunterricht','zzz')");
		 $db->query("UPDATE x_kurse SET ort_sortonly = REPLACE(ort_sortonly, 'fernstudium','zzz')"); */


		$this->log(sprintf("%d addressed geocoded in %1.3f seconds.", $geocoded_addresses, $geocoding_total_s));
		$this->log(sprintf("%d of which were geocoded through external service (only 1st and 15th day of the week)!", $geocoded_addresses_ext));
		$this->log(sprintf("%d addresses were *not* geocoded through external service because of daily limit!", $non_geocoded_addresses_limit));
		$this->log(sprintf("%d records updated.", $kurs_cnt));

		global $geocode_errors_file;

		if ($is_geocode_day && is_array($nongeocoded_addresses) && count($nongeocoded_addresses) > 1)
			file_put_contents($geocode_errors_file, serialize($nongeocoded_addresses));

		// some specials for deepupdates
		if ($lastsync == '0000-00-00 00:00:00') {
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
			$portal_tag_ids = array(0 => 1);
			reset($kurs2portaltag->all_portals);
			foreach ($kurs2portaltag->all_portals as $portalId => $values)
				$portal_tag_ids[$values['portal_tag']] = 1; // $portalTagId may be 0
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
			while ($cont) {
				if (!$db->next_record()) {
					$limit_i += $limit_cnt;
					$db->query($sql . " LIMIT $limit_i, $limit_cnt");
					if (!$db->next_record())
						$cont = false;
					$db->Record['kurs_id'] = 0;  // force one for flush below!
				}

				if ($last_kurs_id != $db->Record['kurs_id']) {
					// flush tags ...
					for ($p = sizeof((array) $curr_portals) - 1; $p >= 0; $p--) {
						for ($t = sizeof((array) $curr_tags) - 1; $t >= 0; $t--) {
							$result[$curr_portals[$p]][$curr_tags[$t]]++;
						}
					}

					// prepare for next
					$last_kurs_id = $db->Record['kurs_id'];
					$curr_portals = array(0);
					$curr_tags = array();
				}

				// collect tags
				$tag_id  = $db->Record['tag_id'];
				if ($portal_tag_ids[$tag_id])
					$curr_portals[] = $tag_id;
				else
					$curr_tags[] = $tag_id;
			}
			$this->statetable->updateUpdatestick();
			// TAG_FREQ: add the synonyms
			$rev_syn = array();
			$db->query("SELECT s.tag_id, s.lemma_id FROM x_tags_syn s LEFT JOIN x_tags t ON s.tag_id=t.tag_id WHERE NOT(t.tag_type&32);");
			while ($db->next_record()) {
				$rev_syn[$db->fs('lemma_id')][] = $db->fs('tag_id');
			}

			// TAG_FREQ: get all tag IDs for all abschluesse (needed to calculate the number of certificates and the number of offers with certificates)
			$this->statetable->updateUpdatestick();

			$elearning = array();
			$db->query("SELECT tag_id FROM x_tags WHERE tag_name = 'E-Learning'"); //  AND tag_type&32768;
			while ($db->next_record()) {
				$elearning[$db->f('tag_id')] = 1;
				echo "E-Learning - Tag: " . $db->f('tag_id') . "\n";
			}

			$abschluesse = array();
			$db->query("SELECT tag_id FROM x_tags WHERE tag_type&1;");
			while ($db->next_record()) {
				$abschluesse[$db->f('tag_id')] = 1;
			}

			$zertifikate = array();
			$db->query("SELECT tag_id FROM x_tags WHERE tag_type&65536;");
			while ($db->next_record()) {
				$zertifikate[$db->f('tag_id')] = 1;
			}

			$this->statetable->updateUpdatestick();
			// TAG_FREQ: write the stuff
			$db->query("DELETE FROM x_tags_freq;");
			$portalIdFor0Out = false;
			reset($kurs2portaltag->all_portals);
			foreach ($kurs2portaltag->all_portals as $portalId => $values) {
				//if( $values['einstellungen']['core'] == '20' ) -- ignore core setting, this was to distinguish between WISY 1.0 and WISY 2.0; nowadays we have other core version numbers
				{
					// calculate the stats for the portal
					$portalTagId = $values['portal_tag'];
					if ($portalTagId && sizeof((array) $result[$portalTagId])) {
						$portalIdFor = $portalId;
					} else {
						$portalIdFor = 0;
						$portalTagId = 0;
					}

					$anz_kurse_mit_elearning  = 0;
					$anz_kurse_mit_abschluss  = 0;
					$anz_kurse_mit_zertifikat = 0;

					// write the x_tags_freq table
					if ($portalIdFor != 0 || !$portalIdFor0Out) {
						$v = '';

						if (is_array($result[$portalTagId])) {
							reset($result[$portalTagId]);
							foreach ($result[$portalTagId] as $currTagId => $currFreq) {
								$v .= $v === '' ? '' : ', ';
								$v .= "($currTagId, $portalIdFor, $currFreq)";

								if (is_array($rev_syn[$currTagId])) {
									for ($s = sizeof((array) $rev_syn[$currTagId]) - 1; $s >= 0; $s--) {
										$v .= $v === '' ? '' : ', ';										// these two lines will add the synonymes
										$v .= "({$rev_syn[$currTagId][$s]}, $portalIdFor, $currFreq)";	// to x_tags_freq - hiding, if needed, may happen in the viewing classes.
									}
								}

								if ($elearning[$currTagId]) {
									$anz_kurse_mit_elearning += $currFreq;
								}

								if ($abschluesse[$currTagId]) {
									$anz_kurse_mit_abschluss += $currFreq;
								}

								if ($zertifikate[$currTagId]) {
									$anz_kurse_mit_zertifikat += $currFreq;
								}
							}
						}

						if ($v != '')
							$db->query("INSERT INTO x_tags_freq (tag_id, portal_id, tag_freq) VALUES $v;");

						if ($portalIdFor == 0) $portalIdFor0Out = true;
					}

					$this->statetable->updateUpdatestick();

					// update the stats for the portal
					$counts = $kurs2portaltag->getPortalTagsCounts($values['portal_tag']);
					$values['einstcache']['stats.anzahl_kurse'] = $counts['anz_kurse'];
					$values['einstcache']['stats.anzahl_anbieter'] = $counts['anz_anbieter'];
					$values['einstcache']['stats.anzahl_durchfuehrungen'] = $counts['anz_durchf'];
					$values['einstcache']['stats.anzahl_elearning'] = $anz_kurse_mit_elearning;
					$values['einstcache']['stats.anzahl_abschluesse'] = $anz_kurse_mit_abschluss;
					$values['einstcache']['stats.anzahl_zertifikate'] = $anz_kurse_mit_zertifikat;
					$values['einstcache']['stats.statistik_stand'] = date("d.m.Y H:i");

					// $yesterday = date('d.m.Y',strtotime('-1 day' , strtotime(date("d.m.Y H:i"))));
					// Fuer Vergleich am morgigen Tag
					$values['einstcache'][date("d.m.Y")] = 'stats.anzahl_anbieter:' . $counts['anz_anbieter']
						. '###stats.anzahl_durchfuehrungen:' . $counts['anz_durchf']
						. '###stats.anzahl_kurse:' . $counts['anz_kurse']
						. '###stats.anzahl_elearning:' . $anz_kurse_mit_elearning
						. '###stats.anzahl_abschluesse:' . $anz_kurse_mit_abschluss
						. '###stats.anzahl_zertifikate:' . $anz_kurse_mit_zertifikat
						. '###stats.statistik_stand:' . date("d.m.Y H:i");

					//$values['einstcache']['stats.tag_filter'] = $einstcache_tagfilter;

					$ist_domain = strtolower($_SERVER['HTTP_HOST']);

					$stats_file = 'admin/stats/' . (substr($ist_domain, 0, 7) != 'sandbox' ? '' : substr($ist_domain, 0, strpos($ist_domain, '.')) . "_") . 'statistiken_' . $portalId . '.csv';
					if (file_exists($stats_file))
						$fp = fopen($stats_file, 'a'); // append
					else {
						$fp = fopen($stats_file, 'w'); // Open for writing only, place the file pointer at the beginning of the file and truncate the file to zero length
						fputcsv($fp, array("Legende", "Datum", "Anzahl"));
					}
					fputcsv($fp, array("Kurse", date("Y-m-d"), $values['einstcache']['stats.anzahl_kurse']));
					fputcsv($fp, array("Anbieter", date("Y-m-d"), $values['einstcache']['stats.anzahl_anbieter']));
					fputcsv($fp, array("Durchfuehrungen", date("Y-m-d"), $values['einstcache']['stats.anzahl_durchfuehrungen']));
					fputcsv($fp, array("ELearning", date("Y-m-d"), $values['einstcache']['stats.anzahl_elearning']));
					fputcsv($fp, array("Abschluesse", date("Y-m-d"), $values['einstcache']['stats.anzahl_abschluesse']));
					fputcsv($fp, array("Zertifikate", date("Y-m-d"), $values['einstcache']['stats.anzahl_zertifikate']));
					fclose($fp);

					$csvData = $this->readCSV($stats_file);

					// Obtain a list of columns
					foreach ($csvData as $key => $row) {
						$legende[$key]  = $row['Legende'];
						$datum[$key] = $row['Datum'];
					}

					// Sort the data with age first, then favorite
					// Add $csvData as the last parameter, to sort by the common key
					array_multisort($legende, SORT_ASC, $datum, SORT_ASC, $csvData);

					$fp = fopen($stats_file, 'w');
					fputcsv($fp, array("Legende", "Datum", "Anzahl"));
					foreach ($csvData as $row) {
						fputcsv($fp, explode(",", $row[0]));
					}
					fclose($fp);


					// write to portal cache
					$this->cacheFlushInt($values['einstcache'], $portalId);
				}
			}
			$this->log("tag frequencies updated.");
		}

		// done
		$this->statetable->writeState('lastsync.kurse.global', $this->today_datetime);
	}

	//find subordinate keywords by id
	function findSubordinateKeys($tablename, $headerkey, $subkey, $db)
	{
		$found = false;
		$pos = -1;

		$sqlESCO = "SELECT structure_pos, attr_id FROM $tablename WHERE primary_id='$headerkey'";
		$db->query($sqlESCO);
		while ($db->next_record()) {
			if ($subkey == $db->fs('attr_id')) {
				$found = true;
				return array('position' => $pos, 'found' => $found);
			}
			$poslocal = intval($db->fs('structure_pos'));
			if ($poslocal > $pos)
				$pos = $poslocal;
		}
		$pos++;
		return array('position' => $pos, 'found' => $found);
	}

	//Find a special keyword bei title
	function findid($uri)
	{
		$db = new DB_Admin;
		$likestring = "%" . $uri;
		$sqlESCO = "SELECT id FROM stichwoerter WHERE zusatzinfo LIKE '$likestring'";
		$db->query($sqlESCO);
		$db->next_record();
		$ret = $db->fs('id');
		//$db->close();
		return $ret;
	}

	//Find WISY-Competence bei Descriptor
	function findWISYDescriptor($keyword)
	{
		$db = new DB_Admin;
		$sqlESCO = "SELECT id FROM stichwoerter WHERE stichwort = '$keyword' AND eigenschaften <> 524288";
		$db->query($sqlESCO);
		$db->next_record();
		$ret = $db->fs('id');
		//$db->close();
		return $ret;
	}

	//Find Synonym bei Descriptor
	function findSynonym($keyword)
	{
		$db = new DB_Admin;
		$sqlESCO = "SELECT id, zusatzinfo FROM stichwoerter WHERE stichwort = '$keyword' AND (eigenschaften = 64 OR eigenschaften = 32)";
		$db->query($sqlESCO);
		if ($db->next_record()) {
			$ret  = array('id' => $db->fs('id'), 'zusatzinfo' => $db->fs('zusatzinfo'));
		} else
			$ret = null;
		//$db->close();
		return $ret;
	}
	//Read out details from ESCO for keyword $keyword
	//if not found: return ""
	//else mode-depend return
	//mode = 1: If exact $keyword in title of ESCO-structure
	//return 1. this ESCO-Structure and 2. the hierarchy of Meta-Dates in ESCO
	//
	//mode = 0: find all ESCO-Structures wich could be found with Search-Parameter keyword
	//
	//mode = 2: test wether ending " (ESCO)" gives ESCO-Results
	function keywordESCO($keyword, $mode = 0)
	{
		//Zusatz " (ESCO)" entfernen, da er von dieser Api angelegt wurde
		if (!(strpos($keyword, " (ESCO)") === false) && $mode <> 2) {
			if (!($this->keywordESCO($keyword, 2) === true)) {
				//remove add " (ESCO)"
				$keyword = substr($keyword, 0, strlen($keyword) - 7);
			}
		}
		/*  Get ESCO-Data (Author: Karl Weber) */
		$endpoint = "https://ec.europa.eu/esco/api";
		$search_params = array(
			'type' => 'skill',
			'text' => $keyword,
			'language' => 'de',
			'page' => '0',
			'limit' => '100',
			'full' => 'true'
		);
		// $this->curl_session = curl_init();

		$search_url = $endpoint . "/search?"  . http_build_query($search_params);

		curl_setopt($this->curl_session, CURLOPT_URL, $search_url);
		curl_setopt($this->curl_session, CURLOPT_RETURNTRANSFER, TRUE);
		$result = curl_exec($this->curl_session);

		$result = json_decode($result, true);
		$keyresult = $result['_embedded']['results'];
		if (!is_array($keyresult))
			return "";

		$found = null;
		if ($mode == 1) {
			foreach ($keyresult as $resu) {
				if ($resu['title']  == Utf8_encode($keyword)) {
					$found = $resu;
					break;
				}
			}
			if ($found == null)
				return "";

			if ($mode == 2)
				return true;

			$broader = $found['_links']['broaderHierarchyConcept'][0]['href'];
			if ($broader == null)
				$broader = $found['_links']['broaderSkill'][0]['href'];
			if (!isset($broader) || $broader == null)
				return "";
			$result_up = $this->fdr($broader, 0);
			//curl_close($this->curl_session);
			return array('keyword' => $found, 'hierarchy' => $result_up);
		} elseif ($mode == 0) {
			$c1 = 1;


			while (1) {
				//	$search_params['page'] = $c1;
				$search_params['offset'] = $c1;
				$search_url = $endpoint . "/search?"  . http_build_query($search_params);
				curl_setopt($this->curl_session, CURLOPT_URL, $search_url);
				$res = curl_exec($this->curl_session);

				$res = json_decode($res, true);
				if (!isset($res['_embedded']['results']))
					break;
				if (Count($res['_embedded']['results']) == 0) {
					break;
				}
				$keyresult = array_merge($keyresult, $res['_embedded']['results']);

				$c1++;
			}
			return $keyresult;
		}
	}


	//Find generic terms
	function fdr($url, $level)
	{
		curl_setopt($this->curl_session, CURLOPT_URL, $url);
		$level++;
		$result_up  = curl_exec($this->curl_session);
		$result_up  = json_decode($result_up, true);
		if ($result_up['title'] == null)
			return null;
		$res = $result_up['_links']['broaderConcept'][0]['href'];
		if ($res == null)
			$res = $result_up['_links']['broaderHierarchyConcept'][0]['href'];
		if ($res == null || $res == $url)
			return array('level' . $level => $result_up);
		if ($result_up['title'] != 'Fähigkeiten' && $result_up['title'] != 'Kenntnisse') {
			$nextfdr =  $this->fdr($res, $level);
			if ($nextfdr != null)
				return array('level' . $level => $result_up) + $nextfdr;
			else
				return array('level' . $level => $result_up);
		} else
			return array('level' . $level => $result_up);
	}

	//insertESCOKeyword an synonyms
	//Modi von insertESCOKeyword
	//3 = ESCO-Synonym anlegen
	//0 = ESCO-Stichwort testweise anlegen
	//1 = ESCO-Oberbegriff anlegen
	//2 = zu ESCO-Stichwort Synonyme suchen
	//$keyword = ESCO-Struktur des Stichworts, zu dem Synonyme gesucht werden
	function insertESCOKeyword($keyword, $db, $freigeschaltet, &$count, $mode = 0)
	{
		$ldb = new DB_Admin;
		$newId = 0;
		if ($mode <> 3) {
			$uri = $keyword['uri'];
			$lab = Utf8_decode($keyword['preferredLabel']['de']);
			if (!isset($lab)) {
				$lab = Utf8_decode($keyword['title']);
			}
		} else {
			$uri = "Synonym";
			$lab = Utf8_decode($keyword);
		}

		if (!isset($lab)) {
			return $newId;
		}

		//Insert core keyword
		if ($uri <> "Synonym")
			$existingId = $this->findid($uri);
		else
			$existingId = null;

		if (($mode == 3 or $mode == 0 or $mode == 1) and $existingId == null) {
			if ($this->findWISYDescriptor($lab) <> null)  //WISY-keyword with same Descriptor found
			{
				$this->log("Das Stichwort \"" . $lab . "\" ist in WISY vorhanden und wird zu \"" . $lab . " (ESCO)\"");
				$lab = $lab . " (ESCO)";
			}
				
			$zusatzinfo = "WISY@KI: freigeschaltet=" . $freigeschaltet . " uri: " . $uri;
			$timestamp = time();
			$creationDate = date("Y-m-d H:i:s", $timestamp);
			$userCreated = $this->par->control_user_created->dbval;
			$userGrp = $this->par->control_user_grp->dbval;
			$userAccess = $this->par->control_user_access->dbval;
			if ($mode == 0)
				$notizen = $creationDate . " ESCO-Stichwort testweise angelegt.";
			elseif ($mode == 1)
				$notizen = $creationDate . " ESCO-Oberbegriff automatisch angelegt.";
			else
				$notizen = $creationDate . " ESCO-Synonym automatisch angelegt.";
			$scope_note = "";
			$algorithmus = "";

			$sorted = g_eql_normalize_natsort($lab);
			if ($mode == 3)
				$eigenschaften = 64;
			else
				$eigenschaften = 524288;
			$sqlESCO = "INSERT INTO stichwoerter (date_created, date_modified, user_created, user_modified, user_grp, user_access, stichwort, stichwort_sorted, eigenschaften, zusatzinfo, notizen, scope_note, algorithmus ) 
		 VALUES ('$creationDate', '$creationDate','$userCreated', '$userCreated', '$userGrp', '$userAccess', '$lab','$sorted', '$eigenschaften', '$zusatzinfo', '$notizen', '$scope_note', '$algorithmus')";
			$db->query($sqlESCO);
			$sqlESCO = "SELECT LAST_INSERT_ID() AS last_id";
			$db->query($sqlESCO);
			if ($db->next_record()) {
				$lastId = $db->fs('last_id');
				$newId = intval($lastId);
				$count++;
			} else
				$newId = 0;
		} elseif ($existingId <> null)
			$newId  = intval($existingId);
		//Insert Synonym
		if ($mode == 1 or $mode == 2) {
			$actualid = $newId;

			//Insert synonyms if possible
			if (isset($keyword['alternativeLabel']['de'])) {
				$actualid = $this->findid($keyword['uri']);
				if (!isset($actualid))
					$actualid = 0;
				$synonymCountBase = 0;
				$synonymCountMeta = 0;
				$synonymAlreadyWisy = 0;
				$synonymAlreadyESCO = 0;
				$synonymRef = 0;
				foreach ($keyword['alternativeLabel']['de'] as $syn) {
					//	if ($this->findWISYDescriptor($syn) <> null)  //WISY-keyword with same Descriptor found
					//		continue;
					$synRet = $this->findSynonym($syn);
					if ($synRet == null) //Same Synonym not already there
					{
						if ($mode == 1)
							$ok = $this->insertESCOKeyword($syn, $ldb, 1, $synonymCountMeta, 3);
						elseif ($mode == 2)
							$ok = $this->insertESCOKeyword($syn, $ldb, 1, $synonymCountBase, 3);
					} else {
						$ok = intval($synRet['id']);
						if (strpos($synRet['zusatzinfo'], "WISY@KI: freigeschaltet") === false)
							$synonymAlreadyWisy++;
						else
							$synonymAlreadyESCO++;
					}

					$ret = $this->findSubordinateKeys("stichwoerter_verweis", $ok, intval($actualid), $db);
					if ($ret['found'] == false and intval($actualid) > 0 and $ok > 0) {
						$pos = $ret['position'];
						$sqlESCO = "INSERT INTO stichwoerter_verweis (primary_id, attr_id, structure_pos) VALUES ('$ok','$actualid','$pos')";
						$db->query($sqlESCO);
						$synonymRef++;
					}
				}
			}
			if ($synonymRef > 0)
				$this->log("Zum Stichwort \"" . $lab . "\" wurden " . $synonymCountBase . " Synonym-Verweise neu angelegt.");
			if ($synonymCountBase > 0)
				$this->log("Zum ESCO-Basis-Stichwort \"" . $lab . "\" wurden " . $synonymCountBase . " ESCO-Synonyme angelegt.");
			if ($synonymCountMeta > 0)
				$this->log("Zum Oberbegriff \"" . $lab . "\" wurden " . $synonymCountMeta . " ESCO-Synonyme angelegt.");
			if ($synonymAlreadyESCO > 0)
				$this->log("Zum ESCO-Stichwort \"" . $lab . "\" wurden " . $synonymAlreadyESCO . " bereits zuvor angelegte ESCO-Synonyme gefunden.");
			if ($synonymAlreadyWisy > 0)
				$this->log("Zum ESCO-Stichwort \"" . $lab . "\" wurden " . $synonymAlreadyWisy . " bereits zuvor angelegte Wisy-Synonyme gefunden.");
		}

		return $newId;
	}
	/* syncing with ESCO (Author: Karl Weber)
	 *************************************************************************/

	function doSyncESCO($deepupdate)
	{
		$this->curl_session = curl_init();

		require_lang('lang/edit');

		//Check for UserId
		if (!isset($_REQUEST['userid'])) {
			$this->log("userid ist zwingend erforderlich.");
			exit();
		} else {
			$_SESSION['g_session_userid'] = $_REQUEST['userid'];
		}

		$this->par = new EDIT_DATA_CLASS(
			null,
			"stichwoerter",
			-1
		);
		$this->par->load_blank();
		$db = new DB_Admin;
		$db2 = new DB_Admin;
		$db3 = new DB_Admin;
		$db6 = new DB_ADMIN;
		$db5 = new DB_ADMIN;
		$db_anbieter = new DB_Admin;

		global $geocode_errors_file;

		$updateLatlng = $deepupdate;
		if ($updateLatlng) {
			$geo_protocol_file = $geocode_errors_file;
			$live_geocode_max = 5500; // mapquest: free account = 15.000 / month
			$is_geocode_day = (intval(date('d')) === 5 || intval(date('d')) === 15 || intval(date('d')) === 25); // update on 5th, 15th and 25th day of the month
			$GLOBALS['geocode_called'] = 0;
			$geocoding_total_s = 0.0;
			$geocoded_addresses = 0;
			$geocoded_addresses_ext = 0;
			$non_geocoded_addresses_limit = 0;
			$geocoded_addresses_live = array();
			$nongeocoded_addresses = array();
			$geocoder = &createWisyObject('WISY_OPENSTREETMAP_CLASS', $this->framework);
		}

		// find out the date to start with syncing
		if ($deepupdate)
			$lastsync = '0000-00-00 00:00:00';
		else
			$lastsync = $this->statetable->readState('lastsync.kurse.global', '0000-00-00 00:00:00');

		//Install Keywords bei first Letters
		if (isset($_REQUEST['keyinst'])) {
			$this->log("ESCO-Stichworte zum Begriff \"" . $_REQUEST['keyinst'] . "\" werden testweise angelegt.");
			$ret = $this->keywordESCO($_REQUEST['keyinst'], 0);
			if ($ret  == "") {
				$this->log("Zu dem Suchbegriff \"" . $_REQUEST['keyinst'] . "\" wurde nichts gefunden.");
				return;
			}

			$insertcount = 0;
			if (isset($ret)) {
				for ($c1 = 0; $c1 < Count($ret); $c1++) {
					$this->insertESCOKeyword($ret[$c1], $db, "0", $insertcount);
				}
				$this->log($insertcount . " ESCO-Stichworte wurden testweise angelegt.");
			}
		} else {
			// Read ESCO-Keywords

			$this->log("Neue ESCO Kompetenzen werden gelesen");
			//Einlesen aller Stichworte, die als ESCO-Stichworte angelegt sind und noch nicht bearbeitet wurden
			$sqlESCO = "SELECT id, stichwort, zusatzinfo FROM stichwoerter WHERE eigenschaften=524288 AND left(zusatzinfo,25) LIKE 'WISY@KI: freigeschaltet=0'";
			$db5->query($sqlESCO);

			$stichwort_count = $db5->ResultNumRows;
			$stichwort_count = 0;

			while ($db5->next_record()) {

				$stichwort_id = intval($db5->fs('id'));
				$stichwort = $db5->fs('stichwort');
				$result = $this->keywordESCO($stichwort, 1);
				if ($result == "") {
					$this->log("Die Esco-Kompetenz \"" . $stichwort . "\" kann in ESCO nicht (mehr) korrekt ausgelesen werden.");
					continue;
				}

				//synonyme zu der zu bearbeitenden ESCO-Kompetenz in WISY einfügen

				$this->insertESCOKeyword($result['keyword'], $db, 0, $dummy, 2);
				$zusatzinfo = $db5->fs('zusatzinfo');

				$predecessor = $db5->fs('id'); //id from stichwort for first concept level
				if ($result['hierarchy'] <> null) {
					$refCount = 0;
					foreach ($result['hierarchy'] as $genKey) {
						$title = Utf8_decode($genKey['preferredLabel']['de']);

						$actualid = $this->insertESCOKeyword($genKey, $db6, "1", $stichwort_count,  1);
						if ($actualid > 0) {
							$ret = $this->findSubordinateKeys("stichwoerter_verweis2", $actualid, $predecessor, $db6);
							if ($ret['found'] == false) {
								$pos = $ret['position'];
								$sqlESCO = "INSERT INTO stichwoerter_verweis2 (primary_id, attr_id, structure_pos) VALUES ('$actualid','$predecessor','$pos')";
								$db6->query($sqlESCO);
								$refCount++;
							}
						} else {
							$this->log("Der Oberbegriff \"" . $title . "\" zum Stichwort \"" . $stichwort . "\" konnte nicht angelegt werden.");
							break;
						}
						$predecessor = $actualid;
					}
					if ($refCount > 0)
						$this->log("Zum Stichwort \"" . $stichwort . "\" wurden " . $refCount . " neue Verweise zu Oberbegriffen angelegt.");
				}
				$zusatzinfo = str_replace("freigeschaltet=0", "freigeschaltet=1", $zusatzinfo);
				$sqlESCO = "UPDATE stichwoerter SET zusatzinfo='$zusatzinfo' WHERE stichwort='$stichwort' AND (eigenschaften <> 524288 OR eigenschaften <> 524289)"; //ESCO-Skill als bearbeitet markieren Achtung nur Test: spaeter freigeschaltet = 1

				$db6->query($sqlESCO);
			}
			$this->log("Done - " . $stichwort_count . " Stichworte wurden als Oberbegriffe angelegt.");
		}
		//	$db5->close();
		//	$db6->close();
		curl_close($this->curl_session);
	}


	// write to portal cache
	// function cacheFlushInt(&$values, $portalId)
	// {
	// 	echo "CacheFlushInt für Portal: " . $portalId . "<br><br>";

	// 	$ret = '';
	// 	ksort($values);
	// 	reset($values);
	// 	foreach ($values as $regKey => $regValue) {
	// 		$regKey		= strval($regKey);
	// 		$regValue	= strval($regValue);
	// 		if ($regKey != '') {
	// 			if (strpos($regKey, "stats") !== FALSE)
	// 				echo $regKey . "=>" . $regValue . "\n";

	// 			$regValue = strtr($regValue, "\n\r\t", "   ");
	// 			$ret .= "$regKey=$regValue\n";
	// 		}
	// 	}

	// 	$db = new DB_Admin;
	// 	$db->query("UPDATE portale SET einstcache='" . addslashes($ret) . "' WHERE id=$portalId;");
	// }

	// function readCSV($file)
	// {
	// 	$row      = 0;
	// 	$csvArray = array();
	// 	if (($handle = fopen($file, "r")) !== FALSE) {
	// 		while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
	// 			$num = count($data);
	// 			for ($c = 0; $c < $num; $c++) {
	// 				$csvArray[$row][] = $data[$c];
	// 			}
	// 			$row++;
	// 		}
	// 	}
	// 	if (!empty($csvArray)) {
	// 		return array_splice($csvArray, 1); //cut off the first row (names of the fields)
	// 	} else {
	// 		return false;
	// 	}
	// }

	// function doGeoMapping()
	// {
	// 	// Bezirke: Berlin, Hamburg, ...?
	// 	// to be moved
	// 	$db = new DB_ADMIN;
	// 	$file_geodef = "config/geo.php";
	// 	require_once($file_geodef);

	// 	if (!$bezirke_hamburg_ort || !is_array($stadtteile_bezirke_hamburg)) {
	// 		echo "Bezirke-Defintion nicht gefunden. (" . $file_geodef . ")" . "\n";
	// 		return false;
	// 	}

	// 	foreach ($stadtteile_bezirke_hamburg as $stadtteil => $bezirk) {
	// 		// DF
	// 		$query = "UPDATE durchfuehrung SET bezirk = '" . $bezirk . "' WHERE stadtteil = '" . $stadtteil . "' AND ort = '" . $bezirke_hamburg_ort . "'";
	// 		$db->query($query);
	// 		// echo $query."\n";

	// 		// anbieter
	// 		$query = "UPDATE anbieter SET bezirk = '" . $bezirk . "' WHERE stadtteil = '" . $stadtteil . "' AND ort = '" . $bezirke_hamburg_ort . "'";
	// 		$db->query($query);
	// 		// echo $query."\n";
	// 	}

	// 	// $db->close();
	// 	echo "Bezirke anhand von Stadt und Stadtteilen zugeordnet" . "\n";
	// 	return true;
	// }

	// // global clean up queries, e.g for legal reasons
	// function doDBCleanup()
	// {
	// 	$db = new DB_ADMIN;
	// 	$file_db_sql_skripte = "db_cleanup_queries.inc.php";
	// 	require_once($file_db_sql_skripte);

	// 	if (!$db_sql || !is_array($db_sql)) {
	// 		echo "Keine zusätzlichen DB-Skripte gefunden. (" . $file_db_sql_skripte . ")" . "\n";
	// 		return false;
	// 	}

	// 	foreach ($db_sql as $query) {

	// 		echo "Ausführen von:\n" . $query . "\n";
	// 		$this->log("Clean up: Ausführen von:\n" . $query . "\n");
	// 		$db->query($query);
	// 		echo "\n";
	// 	}

	// 	// $db->close();
	// 	echo "Zusätzliche DB-Skripte ausgeführt." . "\n\n";
	// 	return true;
	// }

	// public static for call in adress filter test skript
	// Todo: get rid of redundancies
	// Todo: move values to admin/config/...
	public static function clean_address($address, $manualtest = false)
	{


		/* *********************** */
		/* find / replace patterns */

		// Wenn Strasse = ... und sonst nur noch Ort entspr. dann: Strasse leer
		// Standort - Eingang - VHS - Zentrum: Theoretisch crop, aber man weiß nicht, was nach der Strasse kommt.
		$patterns2_skip = array(".*e\.V\.$", '""$', ".*G\.m\.b\.H\.$", ".*GmbH$", ".*ohne .*", ".*unbekannt.*", ".*E-Learning.*", ".*virtuell.*", ".*Web-Seminar.*", ".*Webinar.*", ".*Online.*", ".*Online-Seminar.*", ".*WWW.*", ".*Fernstudium.*", ".*Inhouse.*", ".*In House.*", ".*Live", ".*Internet.*", ".*Cloud.*", ".*Zoom.*", "N\.N\.", "--", "Campus .*", "Klinikum .*", "gegenüber .*", "Eingang .*", "Campus .*", "Klinikum .*", "Standort.*", ".* neben .*", ".* neben,", ".* neben$", ".* hinter .*", ".* hinter,", ".* hinter$", ".* unten .*", ".* unten,", ".* unten$", ".* oben .*", ".* oben,", ".* oben$", ".* bekannt .*", ".* bekannt$", ".* Anfrage .*", ".* Anfrage$", ".*Adresse .*", ".*Treffpunkt.*");

		// Wenn von mehreren Teil der Ort - NUR LETZER TEIL!
		$patterns_2_3_last_skip = array(".*e\.V\.", '""', ".*G\.m\.b\.H\.", ".*GmbH", ".*ohne .*", ".*unbekannt.*", ".*E-Learning.*", ".*virtuell.*", ".*Web-Seminar.*", ".*Webinar.*", ".*Online.*", ".*Online-Seminar.*", ".*WWW.*", ".*Fernstudium.*", ".*Inhouse.*", ".*In House.*", ".*Live", ".*Internet.*", ".*Cloud.*", ".*Zoom.*", ".* Campus .*", ".* Klinikum .*", "gegenüber .*", "Eingang .*", "Campus .*", "Klinikum .*", "Standort .*", ".* Standort$", ".* neben.*", ".* neben,", ".* neben$", ".* hinter.*", ".* hinter,", ".* hinter$", ".* unten .*", ".* unten,", ".* unten$", ".* oben .*", ".* oben,", ".* oben$", ".* bekannt .*", ".* bekannt$", ".* Anfrage .*", ".* Anfrage$", ".*Adresse .*", ".*Treffpunkt.*");

		// Etwas was bei 3 Teilen bereits durch den ersten Teil die gesamte Adresse disqualifiziert
		$patterns_3_first_skip = array(".*E-Learning.*", ".*virtuell.*", ".*Web-Seminar.*", ".*Webinar.*", ".*Online.*", ".*Online-Seminar.*", ".*WWW.*", ".*Fernstudium.*", ".*Internet.*", ".*Cloud.*", ".*Zoom.*");

		/*$patterns_3_first_crop = array(".*e\.V\.$", '.*""$', ".*G.m.b.H.$", ".*GmbH$", ".*Club$", ".*Hotel$", ".*Kirchengemeinde$", ".*Kirche$", ".*Anstalt$", ".*Gebäude$", ".*Kolleg$", ".*Kita$", ".*Kindertagesstätte$", ".*Schule$", ".*VHS$", ".*Zentrum$",  ".*Eingang$", ".*Campus$", ".*Klinikum$"); */

		// 2ter od. 3ter Teil durch "" ersetzen => wenn einziger String = Skip - Reihenfolge wichtig
		$patterns_2_3_crop = array(".*e\.V\.", '""', ".*G.m.b.H.", ".*GmbH", ".*Club", ".*Hotel", ".*Kirchengemeinde", ".*Kirche", ".*Anstalt", ".*Gebäude", ".*Kolleg", ".*Kita", ".*Kindertagesstätte", ".*Schule", ".*VHS", ".*Zentrum", ".*Raum.{0,2}[0-9]{0,4}", ".*Raum", ".*Haus.{0,2}[0-9]{0,4}");

		// In Straße am Anfang beschneiden - Reihenfolge wichtig:
		$patterns_strasse_crop = array("^.*N.N.", "^.*--", "^.* \/", "^.*.{0,2}\/.{0,2}Ecke", ".*Kirchengemeinde ", ".*Kirche ", ".*Hotel ", ".*Club ", ".*Anstalt ", ".*Gebäude ", ".*Kolleg ", ".*Kita", ".*Kindertagesstätte", ".*Schule ", ".*VHS ", ".*Zentrum ", " Raum.*");

		// In Ort am Ende beschneiden - Reihenfolge wichtig:
		$patterns_ort_post_crop = array("^\/.{0,2}Ecke.*", "\/.*", "N\.N\..*", "--.*"); // Ort

		/* *** */


		if (strstr($address, "\n")) {
			echo "<b style='color: darkblue; padding-top: 5px; padding-bottom: 5px;'>Zeilenumbrüche erkannt. Ersetze durch Komma...</b><br>";
			$address = str_replace("\n", ",", $address);
		}

		$address = trim($address);
		$testArr = explode(",", $address);
		$testArr = array_map("trim", $testArr);
		$adresse = "";
		$ok = false;
		$skip = false;
		$changed = false;
		$uneindeutig = false;
		$leer = false;


		while (count($testArr) > 3) {
			echo "<b style='color: darkblue; padding-top: 5px; padding-bottom: 5px;'>Adresse besteht aus " . count($testArr) . " Teilen, verwerfe 1. Teil (" . $testArr[0] . ").</b><br>";
			unset($testArr[0]);
			$testArr = array_values($testArr);
			$address = implode($testArr, ',');
		}


		// zu wenig Strassen- oder Ortsinfo
		if (trim($address) == "" || count($testArr) == 2 && strlen($testArr[0]) < 3 || count($testArr) == 2 && strlen($testArr[1]) < 3 || count($testArr) == 3 && strlen($testArr[1]) < 3 || count($testArr) == 3 && strlen($testArr[2]) < 3) {
			$leer = true;
			$skip = true;
		}

		// Strasse und Ort sind gleich
		if ((count($testArr) == 2 && strlen($testArr[0]) > 2 && $testArr[0] == $testArr[1])
			|| (count($testArr) == 3 && strlen($testArr[1]) > 2 && $testArr[1] == $testArr[2])
		) {
			$skip = true;
		}



		if (count($testArr) == 3) {


			// Ersten Teil ververfen

			// Ort
			foreach ($patterns_2_3_last_skip as $pattern) {
				if (preg_match('/' . $pattern . '$/i', $testArr[2])) {
					if (DEBUG) {
						echo "<small>K) </small>";
					}
					$adresse = "<b>2)</b> " . $address;
					$skip = true;
				}
			}


			foreach ($patterns_2_3_crop as $pattern) {
				if ($skip) {;
				} // already handled
				elseif (preg_match('/' . $pattern . '/i', $testArr[0], $matches)) { // ERSTER Teil (nicht zweiter)
					$result = $testArr[1] . ", " . $testArr[2];
					$adresse = " <b>3)</b> <strike>" . $matches[0] . ", </strike>" . $testArr[1] . ", " . $testArr[2]
						. " => <b>" . $result . "</b>";
					$changed = true;

					if (strlen($result) < 3 || strlen($testArr[1]) < 3 || strlen($testArr[2]) < 3) {
						$uneindeutig = true;
						$skip = true;
					}
				} elseif (preg_match('/' . $pattern . '/i', $testArr[1], $matches)) { // ZWEITER Teil (nicht dritter)
					$result = $testArr[0] . ", " . $testArr[2];
					$adresse = " <b>3)</b> <strike>" . $matches[1] . ", </strike>" . $testArr[0] . ", " . $testArr[2]
						. " => <b>" . $result . "</b>";
					$changed = true;

					if (strlen($result) < 3 || strlen($testArr[0]) < 3 || strlen($testArr[2]) < 3) {
						$uneindeutig = true;
						$skip = true;
					}
				} elseif ($testArr[2] == "") {
					$adresse = " <b>3)</b> " . $address;
					$uneindeutig = true;
					$skip = true;
				}
			}

			foreach ($patterns_3_first_skip as $pattern) {
				if ($skip) {;
				} // already handled
				elseif (preg_match('/' . $pattern . '$/i', $testArr[0])) {
					if (DEBUG) {
						echo "<small>M) </small>";
					}
					$adresse = "<b>2)</b> " . $address;
					$skip = true;
				}
			}

			foreach ($patterns2_skip as $pattern) {
				if (preg_match('/' . $pattern . '$/i', $testArr[1])) {
					if (DEBUG) {
						echo "<small>L) </small>";
					}
					$adresse = "<b>2)</b> " . $address;
					$skip = true;
				}
			}

			foreach ($patterns_strasse_crop as $pattern) {
				if ($skip) {;
				} // already handled
				elseif (preg_match('/' . $pattern . '/i', $testArr[1], $matches)) { // 1. Teil
					if (DEBUG) {
						echo "<small>G) </small>";
					}
					$changed_str = preg_replace('/' . $pattern . '/i', "", $testArr[1]);
					$result = $changed_str . ", " . $testArr[2];
					$adresse = "<b>2)</b> <strike>" . $matches[0] . " </strike>" . $result . " => <b>" . $result . "</b>";
					$changed = true;

					if (strlen($changed_str) < 3) {
						$uneindeutig = true;
						$skip = true;
					}
				}
			}

			foreach ($patterns_ort_post_crop as $pattern) { // post_crop: nachfolgendes wird gestrichen
				if ($skip) {;
				} // already handled
				elseif (preg_match('/' . $pattern . '/i', $testArr[2], $matches)) { // 2. Teil
					if (DEBUG) {
						echo "<small>H) </small>";
					}
					$changed_str = preg_replace('/' . $pattern . '/i', "", $testArr[2]);
					$result = $testArr[0] . ", " . $changed_str;
					$adresse = "<b>2)</b> " . $testArr[0] . ", " . $testArr[1] . ", " . $result . "<strike>" . $matches[0] . " </strike>" . " => <b>" . $result . "</b>";
					$changed = true;

					if (strlen($changed_str) < 3) {
						$uneindeutig = true;
						$skip = true;
					}
				}
			}

			// no change
			if ($adresse == "")
				$adresse = "<b>2)</b> " . $address;
		} elseif (count($testArr) == 2) {

			foreach ($patterns2_skip as $pattern) {
				if ($skip) {;
				} // already handled
				elseif (preg_match('/' . $pattern . '$/i', $testArr[0])) {
					$adresse = "<b>2)</b> " . $address;
					$skip = true;
				}
			}

			// Ort
			foreach ($patterns_2_3_last_skip as $pattern) {
				if ($skip) {;
				} // already handled
				elseif (preg_match('/' . $pattern . '$/i', $testArr[1])) {
					$adresse = "<b>2)</b> " . $address;
					$skip = true;
				}
			}

			foreach ($patterns_2_3_crop as $pattern) {
				if ($skip) {;
				} // already handled
				elseif (preg_match('/' . $pattern . '/i', $testArr[0], $matches)) { // 1. Teil
					$changed_str = preg_replace('/' . $pattern . '/i', "", $testArr[0]);
					$result = $changed_str . ", " . $testArr[1];
					$adresse = "<b>2)</b> <strike>" . $matches[0] . " </strike>" . $result . " => <b>" . $result . "</b>";
					$changed = true;

					if (strlen($changed_str) < 3) {
						$uneindeutig = true;
						$skip = true;
					}
				} elseif (preg_match('/' . $pattern . '/i', $testArr[1], $matches)) { // 2. Teil
					$changed_str = preg_replace('/' . $pattern . '/i', "", $testArr[1]);
					$result = $testArr[0] . ", " . $changed_str;
					$adresse = "<b>2)</b> " . $testArr[0] . " , <strike>" . $matches[0] . " </strike>" . $changed_str . " => <b>" . $result . "</b>";
					$changed = true;

					if (strlen($changed_str) < 3) {
						$uneindeutig = true;
						$skip = true;
					}
				} elseif ($testArr[0] == "") {
					$uneindeutig = true;
					$skip = true;
				} elseif ($testArr[1] == "") {
					$uneindeutig = true;
					$skip = true;
				}
			}

			foreach ($patterns_strasse_crop as $pattern) {
				if ($skip) {;
				} // already handled
				elseif (preg_match('/' . $pattern . '/i', $testArr[0], $matches)) { // 1. Teil
					$changed_str = preg_replace('/' . $pattern . '/i', "", $testArr[0]);
					$result = $changed_str . ", " . $testArr[1];
					$adresse = "<b>2)</b> <strike>" . $matches[0] . " </strike>" . $result . " => <b>" . $result . "</b>";
					$changed = true;

					if (strlen($changed_str) < 3) {
						$uneindeutig = true;
						$skip = true;
					}
				}
			}

			foreach ($patterns_ort_post_crop as $pattern) { // post_crop: nachfolgendes wird gestrichen
				if ($skip) {;
				} // already handled
				elseif (preg_match('/' . $pattern . '/i', $testArr[1], $matches)) { // 2. Teil
					$changed_str = preg_replace('/' . $pattern . '/i', "", $testArr[1]);
					$result = $testArr[0] . ", " . $changed_str;
					$adresse = "<b>2)</b> " . $testArr[0] . ", " . $result . "<strike>" . $matches[0] . " </strike>" . " => <b>" . $result . "</b>";
					$changed = true;

					if (strlen($changed_str) < 3) {
						$uneindeutig = true;
						$skip = true;
					}
				}
			}

			if ($adresse == "")
				$adresse = "<b>2)</b> " . $address;
		} elseif (count($testArr) == 1) {
			$adresse = "<b>1)</b> " . $address;
			$uneindeutig = true;
			$skip = true;

			if ($adresse == "")
				$adresse = "<b>2)</b> " . $address;
		}




		if ($skip) {
			if ($manualtest)
				echo "<div style='color: darkred; padding-top: 5px; padding-bottom: 5px;'>";

			echo "Ueberspringen" . ($uneindeutig ? ' (uneindeutig)' : '') . ($leer ? ' (leer)' : '') . ": " . $adresse;

			if ($manualtest)
				echo "</div>";
		} elseif ($changed) {
			if ($manualtest)
				echo "<div style='color: darkblue; padding-top: 5px; padding-bottom: 5px;'>";

			echo "Geaendert: " . $adresse;

			if ($manualtest)
				echo "</div>";
		} else {
			$ok = true;
			$adresse = $address;
			if (DEBUG || $manualtest) echo "<div style='color: darkgreen; padding-top: 5px; padding-bottom: 5px;'>OK: " . $adresse . "</div>";
		}

		return array("adress" => trim($address, ","), "skip" => $skip, "changed" => $changed, "ok" => $ok);
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
		if ($_GET['wisykiapikey'] != $this->framework->iniRead('wisykiapikey', 'none')) {
			$this->log("********** ERROR: $host: bad apikey. ");
			return;
		}

		// make sure, this script does not abort too soon
		set_time_limit(5 * 60 * 60 /*5 hours ...*/);
		ignore_user_abort(true);





		// use the following for debugging only ...
		$this->dbgCond = "";
		if (substr($host, -6) == '.localx') {
			$this->dbgCond = " ORDER BY date_modified DESC LIMIT 500";
			$this->log("********** WARNING: $host: incomplete update forced!");
		}


		// allocate exclusive access
		if (!$this->statetable->allocateUpdatestick()) {
			$this->log("********** ERROR: $host: cannot sync now, update stick in use, please try again in about 10 minutes.");
			return;
		}

		// see what to do ...
		if (isset($_GET['syncESCO'])) {
			$this->log("********** $host: starting Syncronisateion with ESCO - if you do not read \"done.\" below, we're aborted unexpectedly and things may not work!");
			$this->doSyncESCO(false);
		} else {
			$this->log("********** ERROR: $host: unknown syncing option, use one of \"kurseFast\" or \"kurseSlow\".");
		}

		// cleanup caches (not recommended as this may result in heavy server load after a sync)
		if (SEARCH_CACHE_ITEM_LIFETIME_SECONDS == 0) {
			$this->cleanupSearchCache();
			$this->log("search cache cleaned.");
		} else {
			$this->log(sprintf("search cache refresh after %d seconds.", SEARCH_CACHE_ITEM_LIFETIME_SECONDS));
		}

		// if this is not reached the script may be limited by some server limits: Script-Time, CPU-Time, Memory ...
		// plesae note that kursportal.domainfactory-kunde.de has 5 minutes CPU-time whoile the other domains have only one.
		$this->log(sprintf('done. max. memory: %1.1f MB, time: %1.0f minutes', memory_get_peak_usage(true) / 1048576, (microtime(true) - $overall_time) / 60));

		// release exclusive access
		$this->statetable->releaseUpdatestick();
	}
};
