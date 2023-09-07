<?php if (!defined('IN_WISY')) die('!IN_WISY');

date_default_timezone_set('Europe/Berlin');

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

/* WISY_SYNC_RENDERER_CLASS
 *****************************************************************************/

class WISYKI_SYNC_RENDERER_CLASS {
	private $framework;
	private $statetable;
	private $par;
	private $curl_session;
	private $pythonAPI;
	private $tagtable;
	private $today_datetime;

	function __construct(&$framework, $param) {
		// include EQL
		// require_once('admin/classes.inc.php');
		require_once("admin/lang.inc.php");
		require_once("admin/eql.inc.php");
		require_once("admin/config/codes.inc.php");
		require_once("core51/wisy-sync-renderer-class.inc.php");
		require_once("core51/wisyki-python-class.inc.php");
		require_once("admin/config/trigger_kurse.inc.php");

		$this->framework = &$framework;
		$this->statetable = new WISY_SYNC_STATETABLE_CLASS($this->framework);
		$this->pythonAPI = new WISYKI_PYTHON_CLASS;
		$this->tagtable	= new TAGTABLE_CLASS();
		$this->today_datetime   = strftime("%Y-%m-%d %H:%M:%S");
	}

	function log($str) {
		date_default_timezone_set('Europe/Berlin');

		echo "[" . date("d.m.Y") . " - " . date("H:i:s") . "] " . $str . "\n";
		flush();
		$this->framework->log('wisykisync', $str);
	}

	function cleanupSearchCache() {
		$dbCache = &createWisyObject('WISY_CACHE_CLASS', $this->framework, array('table' => 'x_cache_search'));
		$dbCache->cleanup();
	}

	/* syncing kurse
	 *************************************************************************/

	function doSyncKurse($deepupdate) {
		$db = new DB_Admin;

		$updateLatlng = $deepupdate;

		global $geocode_errors_file;

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
		if ($deepupdate) {
			$lastsync = '0000-00-00 00:00:00';
		} else {
			$lastsync = $this->statetable->readState('lastsync.kurse.global', '0000-00-00 00:00:00');
		}


		// Kurse: Freigegeben, Dauerhaft

		// load all kurse modified since $lastsync
		$sql = "SELECT id, titel, freigeschaltet, thema, anbieter, date_modified, bu_nummer, fu_knr, azwv_knr FROM kurse WHERE date_modified>='$lastsync' AND (freigeschaltet=1 OR freigeschaltet=4);";

		$db->query($sql);

		$alle_kurse_str = '0';
		$kurse_cnt = $db->ResultNumRows;
		echo "Kurse-Cnt vorher: " . $kurse_cnt . "\n\n";
		for ($i = 0; $i < $kurse_cnt; $i++)
			$alle_kurse_str .= ', ' . $db->Result[$i]['id'];

		$kurs2portaltag = new KURS2PORTALTAG_CLASS($this->framework, $this->tagtable, $this->statetable, $alle_kurse_str);

		// some specials for deepupdates
		if ($lastsync == '0000-00-00 00:00:00') {
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
			$sql = "SELECT t.tag_id, xt.kurs_id
					FROM x_kurse_tags xt
					INNER JOIN x_kurse k ON k.kurs_id = xt.kurs_id
					INNER JOIN x_tags t ON t.tag_id = xt.tag_id
					WHERE k.beginn >= '$today'
					AND EXISTS (
						SELECT 1
						FROM x_kurse_tags xt2
						LEFT JOIN x_tags t ON t.tag_id = xt2.tag_id
						WHERE xt2.kurs_id = xt.kurs_id
						AND t.tag_name IN ('Berufliche Bildung', 'Sprache')
					)
					ORDER BY xt.kurs_id";  // -- dies beruecksichtigt nur die akt. kurse ... "SELECT tag_id, kurs_id FROM x_kurse_tags ORDER BY kurs_id"; wuerde auch die abgelaufenen kurse beruecksichtigen
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


			$this->statetable->updateUpdatestick();
			// TAG_FREQ: write the stuff
			$db->query("DELETE FROM x_scout_tags_freq;");
			$portalIdFor0Out = false;
			reset($kurs2portaltag->all_portals);
			foreach ($kurs2portaltag->all_portals as $portalId => $values) {
				// calculate the stats for the portal
				$portalTagId = $values['portal_tag'];
				if ($portalTagId && sizeof((array) $result[$portalTagId])) {
					$portalIdFor = $portalId;
				} else {
					$portalIdFor = 0;
					$portalTagId = 0;
				}

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
						}
					}

					if ($v != '')
						$db->query("INSERT INTO x_scout_tags_freq (tag_id, portal_id, tag_freq) VALUES $v;");

					if ($portalIdFor == 0) $portalIdFor0Out = true;
				}
			}
			$this->log("tag frequencies updated.");
		}
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
	function keywordESCO($keyword, $mode = 0) {
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
	function fdr($url, $level) {
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

	//find subordinate keywords by id
	function findSubordinateKeys($tablename, $headerkey, $subkey, $db) {
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
	function findid($uri) {
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
	function findWISYDescriptor($keyword) {
		$db = new DB_Admin;
		$sqlESCO = "SELECT id FROM stichwoerter WHERE stichwort = '$keyword' AND eigenschaften <> 524288";
		$db->query($sqlESCO);
		$db->next_record();
		$ret = $db->fs('id');
		//$db->close();
		return $ret;
	}

	//Find Synonym bei Descriptor
	function findSynonym($keyword) {
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

	//insertESCOKeyword an synonyms
	//Modi von insertESCOKeyword
	//3 = ESCO-Synonym anlegen
	//0 = ESCO-Stichwort testweise anlegen
	//1 = ESCO-Oberbegriff anlegen
	//2 = zu ESCO-Stichwort Synonyme suchen
	//$keyword = ESCO-Struktur des Stichworts, zu dem Synonyme gesucht werden
	function insertESCOKeyword($keyword, $db, $freigeschaltet, &$count, $mode = 0) {
		$ldb = new DB_Admin;
		$newId = 0;
		if ($mode <> 3) {
			$uri = $keyword['uri'];
			$lab = Utf8_decode($keyword['preferredLabel']['de']);
			if (!isset($lab) || empty($lab)) {
				$lab = Utf8_decode($keyword['title']);
			}
		} else {
			$uri = "Synonym";
			$lab = Utf8_decode($keyword);
		}

		if (!isset($lab) || empty($lab)) {
			$this->log("Label of keyword missing.");
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
			$sqlESCO = "INSERT INTO stichwoerter (date_created, date_modified, user_created, user_modified, user_grp, user_access, stichwort, stichwort_sorted, eigenschaften, zusatzinfo, notizen, scope_note, algorithmus, esco_url ) 
		 VALUES ('$creationDate', '$creationDate','$userCreated', '$userCreated', '$userGrp', '$userAccess', '$lab','$sorted', '$eigenschaften', '$zusatzinfo', '$notizen', '$scope_note', '$algorithmus', '$uri')";
			$db->query($sqlESCO);
			$sqlESCO = "SELECT stichwoerter.id FROM stichwoerter WHERE stichwoerter.stichwort = '$lab' LIMIT 1";
			$db->query($sqlESCO);
			if ($db->next_record()) {
				$newId = $db->Record['id'];
				$this->log("Stichwort created with id: " . $db->Record['id']);
				$count++;
			} else {
				$newId = 0;
			}
		} elseif ($existingId <> null) {
			$this->log("Stichwort " . $keyword['title'] . " with id " . $existingId . " already exists.");
			$newId  = intval($existingId);
		}
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

	function doSyncESCO($deepupdate) {
		$this->curl_session = curl_init();

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

	function get_levels() {
		$levels = array(
			'A' => '',
			'B' => '',
			'C' => ''
		);
		$db = new DB_Admin();
		foreach ($levels as $key => $level) {
			$sql = "SELECT id FROM stichwoerter WHERE stichwoerter.stichwort = 'Niveau $key'";
			$db->query($sql);
			if ($db->next_record()) {
				$levels[$key] = $db->Record['id'];
			}
		}

		return $levels;
	}

	function get_course_stichworte($courseid) {
		$db = new DB_Admin();

		// SQL query to get course tags of type Sachstichwort and Abschluss.
		$sql = "SELECT stichwoerter.stichwort, stichwoerter.eigenschaften 
			FROM stichwoerter
			LEFT JOIN kurse_stichwort
				ON kurse_stichwort.attr_id = stichwoerter.id
			WHERE kurse_stichwort.primary_id = $courseid
			AND stichwoerter.eigenschaften IN (0,1);";

		$db->query($sql);

		$sachstichworte = array();
		$abschluesse = array();
		while ($db->next_record()) {
			if ($db->Record['eigenschaften'] == 0) {
				$sachstichworte[] = utf8_encode($db->Record['stichwort']);
			} else {
				$abschluesse[] = utf8_encode($db->Record['stichwort']);
			}
		}

		return array("Sachstichwort" => $sachstichworte, "Abschluss" => $abschluesse);
	}

	function prepareEditEnv($deepupdate) {
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
	}

	function updateEmbeddings($deepupdate) {
		$this->prepareEditEnv($deepupdate);

		// Check for UserId.
		if (!isset($_REQUEST['userid'])) {
			$this->log("userid ist zwingend erforderlich.");
			exit();
		} else {
			$_SESSION['g_session_userid'] = $_REQUEST['userid'];
		}

		// create a new DB_Admin object
		$db = new DB_Admin();

		// The amount of courses to be classified. 30 Courses take about 1 minute to compute.
		$limit = ""; // Set default.
		if (!empty($_REQUEST['batchsize']) && intval($_REQUEST['batchsize']) > 0) {
			$limit = 'ORDER BY RAND() LIMIT ' . intval($_REQUEST['batchsize']);
		}
		$wherecourseidsql = ""; // Set default.
		if (!empty($_REQUEST['courseid']) && intval($_REQUEST['courseid']) > 0) {
			$wherecourseidsql = "AND k.id = " . intval($_REQUEST['courseid']);
		}

		// build the SQL query to retrieve courses without levels
		$sql = "SELECT k.id, k.titel, k.beschreibung, GROUP_CONCAT(s.stichwort) as tags, t.thema
				FROM kurse k
				LEFT JOIN kurse_stichwort ks ON k.id = ks.primary_id
				LEFT JOIN stichwoerter s ON ks.attr_id = s.id
				LEFT JOIN themen t ON k.thema = t.id
				LEFT JOIN kurse_embedding ke ON ke.kurs_id = k.id
				WHERE k.freigeschaltet IN (1, 4)
				$wherecourseidsql
				AND (
					-- Courses without embedding or with old embedding
					ke.kurs_id IS NULL -- No embedding present
					OR ke.date_modified < k.date_modified -- Embedding older than kurs
				)
				AND (
					-- Courses that have skills associated with them and therefore are relevant for the scout
					(s.eigenschaften IN (0, 524288, 1048576)) -- Sachstichwort OR ESCO-Kompetenz OR ESCO-Tätigkeit
					OR s.id IN (6714, 5471, 5472, 5473, 5474, 5475, 5476) -- Sprachniveaus A1-C2 und 'Sprachen'
				)
				GROUP BY k.id, t.thema
				$limit";

		// execute the SQL query and retrieve the courses
		if (!$db->query($sql)) {
			$this->log("Error executing sql: $sql");
		}

		$this->log("For " . $db->ResultNumRows . " courses embeddings will be calculated.");

		$coursecount = 0;
		$updatedcount = 0;

		// loop through the courses and generate and store the embeddings for the course description.
		while ($db->next_record()) {
			$coursecount++;
			if ($this->createEmbedding($db->Record)) {
				$this->log(" - Calculated embedding successfully.");
				$updatedcount++;
			} else {
				$this->log(" - Error: Couldn't store embedding in db.");
			}
		}

		$this->log("Successfully updated embeddings for $updatedcount/$coursecount courses.");
	}

	function createEmbedding($course) {
		$doc = utf8_encode($course['title']) . ' ' . utf8_encode($course['beschreibung']) . ' ' . utf8_encode($course['tags']) . ' ' . utf8_encode($course['thema']);

		$this->log("Calculating embedding for course {$course['id']}, " . $course['title']);
		try {
			$embeddings = $this->pythonAPI->getEmbeddings(array($doc));
		} catch (Exception $e) {
			$this->log($e->getMessage());
			return false;
		}
		if (!$embeddings) {
			$this->log("- Failed calculating embedding.");
			exit();
		}
		$embedding = json_encode($embeddings[0]);

		$db2 = new DB_Admin();
		$sql = "REPLACE INTO kurse_embedding (kurs_id, embedding, date_modified) VALUES ({$course['id']}, '$embedding', NOW())";

		if ($db2->query($sql)) {
			return true;
		} else {
			return false;
		}
	}

	function get_filterconcepts($themaid) {
		$concepts = array();
		$db = new DB_Admin();
		$sql = "SELECT concepturi FROM thema_esco WHERE themaid = $themaid";
		$db->query($sql);
		while ($db->next_record()) {
			$concepts[] = $db->Record['concepturi'];
		}
		return $concepts;
	}

	function classifyLearningOutcome($deepupdate) {
		$this->prepareEditEnv($deepupdate);


		// The amount of courses to be classified. 30 Courses take about 1 minute to compute.
		$limit = "";
		if (!empty($_REQUEST['batchsize']) && intval($_REQUEST['batchsize']) > 0) {
			$limit = "ORDER BY RAND() LIMIT " . intval($_REQUEST['batchsize']);
		}

		$wherecourseidsql = ""; // Set default.
		if (!empty($_REQUEST['courseid']) && intval($_REQUEST['courseid']) > 0) {
			$wherecourseidsql = "AND kurse.id = " . intval($_REQUEST['courseid']);
		}

		$doCompLevel = true; // Set default.
		if (isset($_REQUEST['doCompLevel'])) {
			$doCompLevel = boolval($_REQUEST['doCompLevel']);
		}

		$doESCO = true; // Set default.
		if (isset($_REQUEST['doESCO'])) {
			$doESCO = boolval($_REQUEST['doESCO']);
		}

		$doEmbedding = true; // Set default.
		if (isset($_REQUEST['doEmbedding'])) {
			$doEmbedding = boolval($_REQUEST['doEmbedding']);
		}

		$asSuggestion = false; // Set default.
		if (isset($_REQUEST['asSuggestion'])) {
			$asSuggestion = boolval($_REQUEST['asSuggestion']);
		}

		$onlyBeruflich = true; // Set default.
		if (isset($_REQUEST['onlyBeruflich'])) {
			$onlyBeruflich = boolval($_REQUEST['onlyBeruflich']);
		}

		$minrequiredscore = 0.7;
		if (isset($_REQUEST['minrequiredscore'])) {
			$minrequiredscore = floatval($_REQUEST['minrequiredscore']);
		}

		$strict = 2;
		if (isset($_REQUEST['strict'])) {
			$strict = intval($_REQUEST['strict']);
		}

		// Check for UserId.
		if (!isset($_REQUEST['userid'])) {
			$this->log("userid ist zwingend erforderlich.");
			exit();
		} else {
			$_SESSION['g_session_userid'] = $_REQUEST['userid'];
		}

		// create a new DB_Admin object
		$db = new DB_Admin();

		$levels = $this->get_levels();
		$levelidlist = join(", ", array_values($levels));

		$wherenocomplevel = "1=1";
		$wherenocomplevelnotice = "";
		if ($doCompLevel) {
			$wherenocomplevelnotice = "kurse.notizen NOT LIKE '%wisyki-bot-classification-complevel%'";
			$wherenocomplevel = "ks.attr_id IN ($levelidlist)";
		}

		$wherenoesconotice = "1=1";
		$wherenoescoskills = "";
		if ($doESCO) {
			$wherenoescoskills = "s.eigenschaften = 524288";
			$wherenoesconotice = "kurse.notizen NOT LIKE '%wisyki-bot-classification-esco%'";
		}

		// build the SQL query to retrieve courses without levels
		$sql = "SELECT kurse.id, kurse.titel, kurse.beschreibung, kurse.notizen, kurse.thema as themaid, themen.thema, GROUP_CONCAT(s.stichwort) as tags
				FROM kurse
				LEFT JOIN themen
					ON themen.id = kurse.thema
				LEFT JOIN kurse_stichwort ks ON kurse.id = ks.primary_id
				LEFT JOIN stichwoerter s ON ks.attr_id = s.id
				WHERE kurse.freigeschaltet in (1, 4)
				";

		if ($onlyBeruflich) {
			$sql .= "";
			$sql .= "
				AND NOT EXISTS (
					SELECT 1
					FROM kurse_stichwort ks
					LEFT JOIN stichwoerter s ON ks.attr_id = s.id
					WHERE kurse.id = ks.primary_id
					AND (
						$wherenoescoskills
						OR $wherenocomplevel
					)
				)
				AND EXISTS (
					SELECT 1
					FROM kurse_stichwort ks
					LEFT JOIN stichwoerter s ON ks.attr_id = s.id
					WHERE kurse.id = ks.primary_id
					AND (
						s.stichwort = 'Berufliche Bildung'
					)
				)";
		} else {
			$sql .= "
				AND NOT kurse.thema = 119 -- Do not get kurse where thema is Persönliche Lebensfragen: 119
				AND NOT EXISTS (
					SELECT 1
					FROM kurse_stichwort ks
					LEFT JOIN stichwoerter s ON ks.attr_id = s.id
					WHERE kurse.id = ks.primary_id
					AND (
						ks.attr_id IN (6714, 5471, 5472, 5473, 5474, 5475, 5476) -- Do not get kurse that have the tag 'Sprachen' or Sprachniveaus A1-C2
						OR $wherenoescoskills
						OR $wherenocomplevel
					)
				)";
		}
		$sql .= "
				$wherecourseidsql
				AND (
					$wherenocomplevelnotice
					OR
					$wherenoesconotice
				)
				GROUP BY kurse.id
				$limit";

		// execute the SQL query and retrieve the courses
		$db->query($sql);
		$count = $db->ResultNumRows;
		$this->log("Start classification of $count courses");
		$this->log("");
		$updatedlevelcount = 0;
		$updatedescocount = 0;
		$updatedembeddingcount = 0;
		$coursecount = 0;

		// loop through the courses and generate competency levels for each one
		while ($db->next_record()) {
			$coursecount++;
			$course = $db->Record;
			// Get course tags.
			$course['stichworte'] = $this->get_course_stichworte($course['id']);

			// call the endpoint using cURL
			$title = utf8_encode($course['titel']);
			$this->log("");
			$this->log("Classify LOs for course {$course['id']}, " . utf8_decode($title) . ": ");

			$timestamp = time();
			$creationDate = date("Y-m-d H:i:s", $timestamp);
			$notizen = '';

			if (!$doCompLevel) {
				$this->log(" - skip compLevel classification");
			} elseif (str_contains($course['notizen'], 'wisyki-bot-classification-complevel')) {
				$this->log(" - skip complevel-classification, course has already been classified");
			} else {
				try {
					$level_prediction = $this->pythonAPI->predict_comp_level(utf8_encode($course['titel']), utf8_encode($course['beschreibung']));
				} catch (Exception $e) {
					$this->log(" - " . $e->getMessage());
					continue;
				}
				if (!empty($level_prediction)) {
					$this->log(" - AI suggests " . $level_prediction['level'] . " " . round($level_prediction['target_probability'] * 100, 2) . "%");
					if ($level_prediction['target_probability'] >= $minrequiredscore) {
						$levelid = $levels[$level_prediction['level']];
						$leveldb = new DB_Admin();

						// If there are already levels associated with the course, delete them.
						$sql = "DELETE FROM kurse_stichwort WHERE kurse_stichwort.primary_id = {$course['id']} AND kurse_stichwort.attr_id IN ($levelidlist)";
						$leveldb->query($sql);

						// Insert level as a stichwort.
						$sql = "INSERT INTO kurse_stichwort (primary_id, attr_id) VALUES ({$course['id']}, $levelid)";
						if ($leveldb->query($sql)) {
							$notizen .= $creationDate . " Niveau {$level_prediction['level']} wurde automatisiert durch KI vergeben. (wisyki-bot-classification-complevel)\n";
							$this->log(" - Added 'Niveau {$level_prediction['level']}' tag to course. DB updated sucessfully.");
							$updatedlevelcount++;
						} else {
							$this->log(" - Error: Couldn't add 'Niveau {$level_prediction['level']}' tag to course.)");
						}
					} else {
						$this->log(" - Did not add 'Niveau {$level_prediction['level']}' tag to course, because probability score < ." . $minrequiredscore);
					}
					$this->log("");
				} else {
					$this->log(" - ERROR: " . $level_prediction);
				}
			}

			if (!$doESCO) {
				$this->log(" - skip ESCO classification");
			} elseif (str_contains($course['notizen'], 'wisyki-bot-classification-esco')) {
				$this->log(" - skip esco-classification, course has already been classified");
			} else {
				$filterconcepts = $this->get_filterconcepts($course['themaid']);
				if (empty($filterconcepts)) {
					$this->log(" - no filterconcepts available for Thema: " . $course['thema']);
				}
				try {
					$esco_prediction = $this->pythonAPI->predict_esco_terms(utf8_encode($course['titel']), utf8_encode($course['beschreibung']), utf8_encode($course['thema']), $course['stichworte']['Abschluss'], $course['stichworte']['Sachstichwort'], $filterconcepts, $strict, 40);
				} catch (Exception $e) {
					$this->log(" - " . $e->getMessage());
					continue;
				}
				if (!empty($esco_prediction)) {
					if (empty($esco_prediction["results"])) {
						$this->log(" - No releveant ESCO terms found for this course.");
					} else {
						$escodb = new DB_Admin();
						$skillsmappedcount = 0;
						foreach ($esco_prediction["results"] as $result) {
							if ($result['className'] == 'Occupation') {
								continue;
							}

							// Insert skill as tag in db, if it does not already exist.
							$skillid = $this->insertESCOKeyword($result, $escodb, "0", $dummy, 0);
							if ($skillid <= 0) {
								$this->log(" - Error: ESCO keyword couldnt be inserted, skillid: " . $skillid);
								break;
							}

							// Check if skill is already mapped to course.
							$sql = "SELECT * FROM kurse_stichwort WHERE primary_id = {$course['id']} AND attr_id = $skillid LIMIT 1";
							if ($escodb->query($sql) and $escodb->next_record()) {
								$this->log(" - Already mapped skill '{$result['title']}' tag to course.");
								continue;
							}

							if ($asSuggestion) {
								$sql = "REPLACE INTO kurse_kompetenz (primary_id, attr_id, attr_url, suggestion) VALUES ({$course['id']}, $skillid, '{$result['uri']}', 1)";
								if (!$escodb->query($sql)) {
									$this->log(" - Error: Couldn't map Skill '{$result['title']}' tag to course. kurse_kompetenz)");
									continue;
								}
							} else {
								$sql = "REPLACE INTO kurse_kompetenz (primary_id, attr_id, attr_url, suggestion) VALUES ({$course['id']}, $skillid, '{$result['uri']}', 0)";
								if (!$escodb->query($sql)) {
									$this->log(" - Error: Couldn't map Skill '{$result['title']}' tag to course. kurse_kompetenz)");
									continue;
								}
								// Map skill to course.
								$sql = "REPLACE INTO kurse_stichwort (primary_id, attr_id) VALUES ({$course['id']}, $skillid)";
								if (!$escodb->query($sql)) {
									$this->log(" - Error: Couldn't map Skill '{$result['title']}' tag to course. Table kurse_stichwort.)");
									continue;
								}
							}
							$this->log(" - Mapped skill '{$result['title']}' tag to course. DB updated sucessfully.");
							$skillsmappedcount++;
						}
						// ESCO Competency mapped successfully to course.
						if ($skillsmappedcount > 0) {
							$notizen .= $creationDate . " ESCO-Stichworte wurden automatisiert durch KI ergänzt. (wisyki-bot-classification-esco)\n";
							$updatedescocount++;
						}
					}
				} else {
					$this->log(" - ERROR: " . $esco_prediction);
				}

				if (!empty($notizen)) {
					$notizen .= utf8_encode($course['notizen']);
					$notizen = addslashes(utf8_decode($notizen));
					$kursdb = new DB_Admin();
					$sql = "UPDATE kurse
							SET notizen = '$notizen', date_modified = '$creationDate', user_modified = '" . $_REQUEST['userid'] . "'
							WHERE kurse.id = " . $course['id'];
					$kursdb->query($sql);
				}
				$this->log("");
			}

			if (!$doEmbedding) {
				$this->log(" - skip updateing embedding");
			} else {
				if ($this->createEmbedding($course)) {
					$this->log(" - Calculated embedding successfully.");
					$updatedembeddingcount++;
				} else {
					$this->log(" - Error: Couldn't store embedding in db.");
				}
			}
			$this->log("");
		}
		$this->log("Successfully classified compLevel for $updatedlevelcount/$coursecount courses.");
		$this->log("Successfully classified ESCO LOs for $updatedescocount/$coursecount courses.");
		$this->log("Successfully calculated embeddings for $updatedembeddingcount/$coursecount courses.");
	}

	/* main - see what to do
	 *************************************************************************/

	function render() {
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
		set_time_limit(6 * 60 * 60 /*5 hours ...*/);
		ignore_user_abort(true);

		// allocate exclusive access
		if (!$this->statetable->allocateUpdatestick()) {
			$this->log("********** ERROR: $host: cannot sync now, update stick in use, please try again in about 10 minutes.");
			return;
		}

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
		}else if (isset($_GET['syncESCO'])) {
			$this->log("********** $host: starting Syncronisateion with ESCO - if you do not read \"done.\" below, we're aborted unexpectedly and things may not work!");
			$this->doSyncESCO(false);
		} else if (isset($_GET['classifyLearningOutcome'])) {
			$this->log("********** $host: starting Classification of competency levels - if you do not read \"done.\" below, we're aborted unexpectedly and things may not work!");
			try {
				$this->classifyLearningOutcome(false);
			} catch (Exception $e) {
				$this->log("********** ERROR: " . $e->getMessage());
			}
		} else if (isset($_GET['updateEmbeddings'])) {
			$this->log("********** $host: starting update of course embeddings - if you do not read \"done.\" below, we're aborted unexpectedly and things may not work!");
			try {
				$this->updateEmbeddings(false);
			} catch (Exception $e) {
				$this->log("********** ERROR: " . $e->getMessage());
			}
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
