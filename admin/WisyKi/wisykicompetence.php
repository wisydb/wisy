<?php
require_once('index_form.inc.php');

class WISYKI_COMPETENCE_CLASS
{
	public $db;
	public $par;
	public $curl_session;
	public $minRel = 0.90;
	public $firstnum = 10;
	private $api_endpoint;

	function __construct($db = null)
	{
		if ($db != null)
			$this->db = $db;
		else
			$this->db = new DB_Admin;
		// $sql = "CREATE TABLE EscoCategories (
		// 	id int(11) NOT NULL,
		// 	user_created int(11) NOT NULL DEFAULT 0,
		// 	user_grp int(11) NOT NULL DEFAULT 0,
		// 	user_access int(11) NOT NULL DEFAULT 0,
		// 	date_modified datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		// 	titel varchar(500) NOT NULL
		// )";

		// $this->db->query($sql);
		if (isset($GLOBALS['MinRel'])) {
			$this->minRel = $GLOBALS['MinRel'];
		}
		if (isset($GLOBALS['MaxPop'])) {
			$this->firstnum = $GLOBALS['MaxPop'];
		}
		
        if (!defined('WISYKI_API')) {
            throw new Exception('WISYKI_API not set in admin/config/config.inc.php');
        }
		$this->api_endpoint = WISYKI_API;
	}
	function correctsql($sql, $level, $selectedid = NULL)
	{
		//$vgl ="SELECT escocategories.id, escocategories.kategorie FROM escocategories  ORDER BY escocategories.date_modified DESC, escocategories.id";
		if ($selectedid == NULL) {
			$newsql = "SELECT DISTINCT " . substr($sql, strpos($sql, " "));
			$newsql = substr($newsql, 0, strpos($newsql, " ORDER BY")) . "INNER JOIN escohierarchy ON escocategories.id=escohierarchy.primary_id WHERE level=$level" . substr($newsql, strpos($newsql, " ORDER BY"));
		} else {
			$level--;
			$newsql = substr($sql, 0, strpos($sql, " ORDER BY")) . "INNER JOIN escohierarchy ON escocategories.id=escohierarchy.attr_id WHERE level=$level AND escohierarchy.primary_id=$selectedid"
				. substr($sql, strpos($sql, " ORDER BY"));
		}
		return $newsql;
	}

	function searchForScills($id)
	{
		$sql = "DELETE FROM `escoskills` WHERE 1";
		$this->db->query($sql);
		$sql =  "SELECT url FROM escocategories WHERE id=$id";
		$this->db->query($sql);
		if ($this->db->next_record()) {
			$url = $this->db->fs('url');
			if (isset($url)) {
				$escostruct = $this->getESCOStruct($url, "skill");
			}
			if (isset($escostruct['_embedded'][$url]['_links']['narrowerSkill'])) {
				$skills = $escostruct['_embedded'][$url]['_links']['narrowerSkill'];
			} else
				return "";

			$idcnt = 1;
			foreach ($skills as $skill) {
				$sql =  "INSERT INTO escoskills(id, user_created, user_grp, user_access, date_created, date_modified, kategorie, url) VALUES($idcnt, 0, 0, 0, '" . ftime("%Y-%m-%d %H:%M:%S") .  "', '"  . ftime("%Y-%m-%d %H:%M:%S") .  "', '"  . utf8_decode($skill['title']) . "', '" . utf8_decode($skill['uri']) . "')";
				$idcnt++;
				$this->db->query($sql);
			}
			return "SELECT id, kategorie FROM escoskills WHERE 1 ORDER BY  kategorie";
		}
		return "";
	}

	//Specialhandling for competence search in ESCO in Wisy@Ki
	function competencesearch($sqle)
	{
		$rtypes = explode(',', $_REQUEST['rtypes']);
		if ($rtypes[0] != "ESCO") {
			$sql = "";
			$sqlstart = "SELECT stichwoerter.id, stichwoerter.stichwort, stichwoerter.eigenschaften FROM stichwoerter  WHERE ";
			for ($f = 0; $f < sizeof((array) $rtypes); $f++) {
				if ($f == 0) {
					if (str_starts_with($rtypes[$f], 'Id')) {
						$sqlstart .= "(stichwoerter.id=";
						$sqlstart .= substr($rtypes[$f], 2);
					} else {
						$sqlstart .= "(stichwoerter.eigenschaften=";
						$sqlstart .= $rtypes[$f];
					}
				} else {
					if (str_starts_with($rtypes[$f], 'Id')) {
						$sqlstart .= " OR stichwoerter.id=";
						$sqlstart .= substr($rtypes[$f], 2);
					} else {
						$sqlstart .= " OR stichwoerter.eigenschaften=";
						$sqlstart .= $rtypes[$f];
					}
				}
			}
			$sqlend =  ") ORDER BY stichwoerter.stichwort ASC";
			$sql = $sqlstart . $sqlend;
			return $sql;
		} else {
			$this->db->query('SELECT COUNT(*) FROM escocategories');
			if ($this->db->next_record()) {
				$entries = $this->db->f("COUNT(*)");
			}
			if ($entries == 0) {
				$this->fillESCO_Cat("http://data.europa.eu/esco/skill/335228d2-297d-4e0e-a6ee-bc6a8dc110d9");
				$this->fillESCO_Cat("http://data.europa.eu/esco/skill/c46fcb45-5c14-4ffa-abed-5a43f104bb22");
				$this->fillESCO_Cat("http://data.europa.eu/esco/skill/04a13491-b58c-4d33-8b59-8fad0d55fe9e");
				$this->fillESCO_Cat("http://data.europa.eu/esco/skill/e35a5936-091d-4e87-bafe-f264e55bd656");
			}
			return $sqle;
		}
	}

	public function ids_array_equal($a1, $a2)
	{
		if (sizeof((array) $a1) != sizeof((array) $a2)) {
			return false;
		}
		for ($i = 0; $i < sizeof((array) $a1); $i++) {
			$a1i = isset($a1[$i]) ? $a1[$i] : null;
			$a2i = isset($a2[$i]) ? $a2[$i] : null;
			if ($a1i != $a2i) {
				return false;
			}
		}
		return true;
	}

	public function read_simple_list_(&$db, $sql) // ready all elements named 'ret' into a simple array
	{
		$ret = array();
		$db->query($sql);
		while ($db->next_record()) {
			$ret[] = $db->fs('ret');
		}
		return $ret;
	}

	//Find ESCO-proposuals
	function WisyKi_find_competence_proposuals($controls)
	{
		$ret = array();
		foreach ($controls as $param) {
			if ($param->name == "f_vorschlaege") {
				if (!$param->dbval == "")
					$ret = explode(',', $param->dbval);
				break;
			}
		}
		return $ret;
	}
	//Find competences in KI
	function WisyKi_competence_search($controls, $id)
	{
		$hier_url = array();
		foreach ($controls as $param) {
			if ($param->name == "f_beschreibung")
				$descr = $param->dbval;

			if ($param->name == "f_stichwort")
				$keywordids = $param->dbval;

			if ($param->name == "f_titel")
				$titel = $param->dbval;
			if ($param->name == "f_lernziele")
				$lernziele = $param->dbval;
			// if ($param->name == "f_vorraussetzungen")
			// $vorraussetzungen = $param->dbval;
			if ($param->name == "f_zielgruppe")
				$zielgruppe = $param->dbval;
			if ($param->name == "f_num_prop" && $param->dbval != "")
				$this->firstnum = intval($param->dbval);
			if ($param->name == "f_rel_prop"  && $param->dbval != "")
				$this->minRel = doubleval($param->dbval);
			if ($param->name == "f_thema") {
				$theme_id = $param->dbval;
				$sql = 'SELECT concepturi FROM thema_esco WHERE themaid = "' . $theme_id . '"';
				$this->db->query($sql);
				while ($this->db->next_record()) {
					$hier_url[] = $this->db->Record['concepturi'];
				}
			}
		}
		if (isset($keywordids)) {
			$keyword_ids = explode(",", $keywordids);
			$keywords = array();
			foreach ($keyword_ids as $keyid) {
				$sql = 'SELECT stichwort FROM stichwoerter WHERE stichwoerter.id = "' . $keyid . '" AND stichwoerter.eigenschaften = "0"';
				$this->db->query($sql);
				if ($this->db->next_record()) {
					$keywords[] = utf8_encode($this->db->Record['stichwort']);
				}
			}
			$sql = 'SELECT attr_url, stichwort FROM kurse_kompetenz, stichwoerter WHERE kurse_kompetenz.primary_id = "' . $id  . '" AND kurse_kompetenz.suggestion = "0" AND stichwoerter.id = kurse_kompetenz.attr_id';
			$skills = array();
			$this->db->query($sql);
			while ($this->db->next_record()) {
				$skills[] = array(
					"title" => utf8_encode($this->db->Record['stichwort']),
					"uri" => $this->db->Record['attr_url'],
				);
			}
		}

		$url = $this->api_endpoint . "/chatsearch";
		$curl_session = curl_init($url);
		$search_params = array(
			// 'searchterms' => $kw,
			'doc' => utf8_encode($titel) . " " . utf8_encode($lernziele) . " " . utf8_encode(implode(" ", $keywords))  .  " " . utf8_encode($zielgruppe) . " " . utf8_encode($descr),
			'top_k' => 20,
			'strict' => 2,
			'skills' => $skills,

			//'doc' => utf8_encode($descr),
			// 'exclude_irrelevant' => true,
			// 'extract_keywords' => true,
			// 'schemes' => "http://data.europa.eu/esco/concept-scheme/member-skills",
			'filterconcepts' => $hier_url,
			'openai_api_key' => OPENAI_API_KEY,
			// 'min_relevancy' => $this->minRel
		);
		$json = json_encode($search_params);

		curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curl_session, CURLOPT_POSTFIELDS, $json);
		curl_setopt($curl_session, CURLOPT_TIMEOUT, 60);
		curl_setopt($curl_session, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($json)
		));
		if (curl_error($curl_session)) {
			echo 'Request Error:' . curl_error($curl_session);
			return;
		}

		curl_close($curl_session);
		$result = curl_exec($curl_session);

		$result = json_decode($result, true);
		$res = array();
		if (isset($result['results'])) {
			$resu = $result['results'];
			if (is_array($resu))
				for ($c1 = 0; $c1 < sizeof((array) $resu); $c1++) {
					if ($c1 < $this->firstnum)
						$res[] = $resu[$c1];
					else
						break;
				}
		} else
			return false;
		return $res;
	}

	// function getESCObyText($search)
	// {
	// 	$curl_session = curl_init();
	// 		/*  Get ESCO-Data (Author: Karl Weber) */
	// 		$endpoint = "https://ec.europa.eu/esco/api";
	// 		$search_params = array(
	// 			'type' => 'concept',
	// 			'text' => $search,
	// 			'language' => 'de',
	// 			'page' => '0',
	// 			'limit' => '100',
	// 			'full' => 'true',
	// 			'selectedVersion' => 'v1.1.1'
	// 		);
	// 		// $this->curl_session = curl_init();

	// 		$search_url = $endpoint . "/search?"  . http_build_query($search_params);

	// 		curl_setopt($curl_session, CURLOPT_URL, $search_url);
	// 		curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, TRUE);
	// 		$result = curl_exec($curl_session);

	// 		$result = json_decode($result, true);
	// 		$keyresult = $result['_embedded']['results'];
	// 		return $keyresult;

	// }
	function getESCOStruct($search, $type)
	{
		$result = "";
		$endpoint = "https://ec.europa.eu/esco/api/resource/$type";
		//$endpoint = "https://ec.europa.eu/esco/api/classification/skills";


		$this->curl_session = curl_init();
		$search_url = $endpoint . "?uris="  . $search . "&language=de&selectedVersion=v1.1.1";
		curl_setopt($this->curl_session, CURLOPT_URL, $search_url);



		curl_setopt($this->curl_session, CURLOPT_RETURNTRANSFER, TRUE);


		curl_setopt($this->curl_session, CURLOPT_TIMEOUT, 180);
		$result = curl_exec($this->curl_session);

		$result = json_decode($result, true);
		return $result;
	}

	function fillESCO_Cat($search, $rec = false, $parent_id = 0, $level = 0)
	{

		/*  Get ESCO-Data (Author: Karl Weber) */

		$result = $this->getESCOStruct($search, "concept");
		if (is_array($result) && isset($result['_embedded'][$search]['_links']['narrowerConcept']))
			$keyresult = $result['_embedded'][$search]['_links']['narrowerConcept'];
		else
			return null;


		if (is_array($keyresult)) {
			for ($c1 = 0; $c1 < sizeof((array) $keyresult); $c1++) {
				$myId = 0;
				if (!isset($keyresult[$c1]['title']))
					continue;
				$sql =  "INSERT INTO escocategories(sync_src, date_created, date_modified, kategorie, url) VALUES(0, '" . ftime("%Y-%m-%d %H:%M:%S") . "', '" . ftime("%Y-%m-%d %H:%M:%S") . "', '"   . utf8_decode($keyresult[$c1]['title']) . "', '" . utf8_decode($keyresult[$c1]['uri']) . "')";
				$this->db->query($sql);
				$sqlESCO = "SELECT LAST_INSERT_ID() AS last_id";
				$this->db->query($sqlESCO);
				if ($this->db->next_record()) {
					$lastId = $this->db->fs('last_id');
					$myId = intval($lastId);
				} else $myId = 0;
				if ($level > 0) {
					$sql = "INSERT INTO escohierarchy(primary_id, attr_id, level) VALUES(" . $parent_id . ", " . $myId . ", " . $level . ")";
					$this->db->query($sql);
				}



				$this->fillESCO_Cat($keyresult[$c1]['uri'], true, $myId, $level + 1);
			}

			return null;
		}
		return null;
	}
}
