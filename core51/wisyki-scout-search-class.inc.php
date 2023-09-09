<?php
require_once('wisy-intellisearch-class.inc.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/core51/wisyki-json-response.inc.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/core51/wisyki-python-class.inc.php');

/**
 * Class WISYKI_SCOUT_SEARCH_CLASS
 *
 * Represents a search for courses based on a specific skill and competency level.
 * 
 * The "Weiterbildungsscout" was created by the project consortium "WISY@KI" as part of the Innovationswettbewerb INVITE 
 * and was funded by the Bundesinstitut für Berufsbildung and the Federal Ministry of Education and Research.
 *
 * @copyright   2023 ISy TH Lübeck <dev.ild@th-luebeck.de>
 * @author		Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class WISYKI_SCOUT_SEARCH_CLASS extends WISY_SEARCH_CLASS {
	/**
	 * Helper functions for rendering info about a 'durchfuhrung'.
	 *
	 * @var WISY_DURCHF_CLASS
	 */
	private WISY_DURCHF_CLASS $durchfClass;


	private $complevel_durs = array();
	private $anbieter_durs = array();
	private $durchf_durs = array();
	private $stichwort_durs = array();
	private $orderBySkillMatches = '';
	private $selectSkillMatches = '';
	private $orderBy = 'rand';
	private $queries = array();

	/**
	 * Constructor for WISYKI_SCOUT_SEARCH_CLASS.
	 *
	 * @param WISY_FRAMEWORK_CLASS $framework The framework class instance.
	 * @param array $param An array of additional parameters.
	 */
	function __construct(&$framework, $param) {
		parent::__construct($framework, $param);
		$this->durchfClass = &createWisyObject('WISY_DURCHF_CLASS', $this->framework);
	}

	/**
	 * Get the querystring based on the skill label and level.
	 *
	 * @param ocject $skills The skills to search for.
	 * @param object $filters Filtersettings to narrow the search results.
	 * @return string The querystring.
	 */
	function get_querystring(object $skills, object $filters): string {
		$querystring = '';

		$tags = array();
		foreach ($skills as $skill) {
			$skilllabel = utf8_decode($skill->label);
			$skilllabelWithoutESCO = preg_replace('/ +\(ESCO\)/', '', $skilllabel);
			$skilllabelWithoutESCOTagID = $this->lookupTag($skilllabelWithoutESCO);

			$querystring .= " ODER " . $skilllabel;
			if ($skilllabel != $skilllabelWithoutESCO && $skilllabelWithoutESCOTagID) {
				$querystring .= " ODER " . $skilllabelWithoutESCO;
			}

			$tags[] = $this->lookupTag($skilllabel);

			// Alternative skills.
			if (isset($skill->similarSkills) and !empty($skill->similarSkills)) {
				if ($skilllabelWithoutESCOTagID) {
					// $tags[] = $this->lookupTag($skilllabelWithoutESCO);
				}
				for ($i = 0; $i < count($skill->similarSkills->narrower); $i++) {
					$narrowerSkill = utf8_decode($skill->similarSkills->narrower[$i]);
					$narrowerSkillTagID = $this->lookupTag($narrowerSkill);
					if ($narrowerSkillTagID) {
						$querystring .= ' ODER ' . $narrowerSkill;
						// $tags[] = $narrowerSkillTagID;
					}
				}
				for ($i = 0; $i < count($skill->similarSkills->broader); $i++) {
					$broaderSkill = utf8_decode($skill->similarSkills->broader[$i]);
					$broaderSkillTagID = $this->lookupTag($broaderSkill);
					if ($broaderSkillTagID) {
						// $querystring .= ' ODER ' . $similarSkill;
						// $tags[] = $broaderSkillTagID;
					}
				}
			}
		}
		$this->selectSkillMatches = ', SUM(CASE WHEN xk.tag_id IN (' . join(', ', $tags) . ') THEN 1 ELSE 0 END) as skillMatches';
		$this->orderBySkillMatches = 'skillMatches DESC';
		$this->orderBy = 'skillMatches';

		// Remove leading " ODER " od $querystring.
		$querystring = substr($querystring, 5);

		// Coursemode filter.
		if (isset($filters->coursemode)) {
			for ($i = 0; $i < count($filters->coursemode); $i++) {
				$coursemode = $filters->coursemode[$i];
				if ($i == 0) {
					$querystring .= ', ' . utf8_decode($coursemode);
				} else {
					$querystring .= ' ODER ' . utf8_decode($coursemode);
				}
			}
		}

		// Location filter.
		if (isset($filters->location)) {
			if (isset($filters->location->name) and !empty($filters->location->name)) {
				$querystring .= ', bei:' . utf8_decode($filters->location->name);

				if (isset($filters->location->perimiter) and !empty($filters->location->perimiter)) {
					$querystring .= ', km:' . utf8_decode($filters->location->perimiter);
				}
			}
		}

		// Price filter.
		if (isset($filters->price) and !empty($filters->price)) {
			$querystring .= ', preis:' . utf8_decode($filters->price);
		}

		// TimeOfDay filter.
		if (isset($filters->timeofday)) {
			for ($i = 0; $i < count($filters->timeofday); $i++) {
				$timeofday = $filters->timeofday[$i];
				if ($i == 0) {
					$querystring .= ', ' . utf8_decode($timeofday);
				} else {
					$querystring .= ' ODER ' . utf8_decode($timeofday);
				}
			}
		}

		// Funding filter.
		if (isset($filters->funding)) {
			for ($i = 0; $i < count($filters->funding); $i++) {
				$funding = $filters->funding[$i];
				if ($i == 0) {
					$querystring .= ', ' . utf8_decode($funding);
				} else {
					$querystring .= ' ODER ' . utf8_decode($funding);
				}
			}
		}

		// Complevel filter.
		$querystring .= ', WISY@KI - Kompetenzniveaumodell ODER Sprachniveau';

		return $querystring;
	}

	function search($skills, $filters, $limit) {
		$matches = array();
		$querystring = $this->get_querystring($skills, $filters);

		// Run sarch query.
		$this->prepare($querystring);
		if (!$this->ok()) {
			if ($this->error["id"] == "tag_not_found") {
				return $matches;
			}
			JSONResponse::error500('Error while preparing searcher class with query: ' . json_encode($this->error));
		}

		// Get search results.
		$kurse = $this->getKurseRecords(0, $limit ?? 0, $this->orderBy);
		if (empty($kurse)) {
			return $matches;
		}

		// Get course details.
		$matches = array_map([$this, 'get_course_details'], $kurse['records']);

		// Remove empty array items.
		$matches = array_values(array_filter($matches, function ($item) {
			return $item !== null;
		}));

		return $matches;
	}

	/**
	 * Renders the search results after preparing the search based on the skill label and level.
	 *
	 * @return void Echoes a JSON response.
	 */
	function render() {
		$start1 = new DateTime();
		$skillsjson = $_GET['skills'];
		$skills = json_decode($skillsjson);
		$occupationjson = $_GET['occupation'];
		$occupation = json_decode($occupationjson);
		$filterjson = $_GET['filters'];
		$filters = json_decode($filterjson);
		$limit = $_GET['limit'];
		$filterDurs = array();

		// Get search results for every skill.
		$results = $this->search($skills, $filters, $limit);

		$start2 = new DateTime();

		// Remove duplicates in $results courses by id and merge skill array.
		$uniqueCourses = [];
		for ($index = 0; $index < count($results); $index++) {
			$course = $results[$index];
			$courseId = $course['id'];
			if (!isset($uniqueCourses[$courseId])) {
				$uniqueCourses[$courseId] = $course;
			}
		}
		$results = array_values($uniqueCourses);


		// Order search results by skill and ai recommendations.
		$sets = array();

		// Sort the results based on semantic similarity.
		$pytonapi = new WISYKI_PYTHON_CLASS();

		// Build string describing the user.
		// TODO Add additional info from account, if user loggedin.
		$base = '';
		if (isset($occupation) && isset($occupation->label)) {
			$base .= $occupation->label;
		}
		foreach ($skills as $skill) {
			$base .= ', ' . $skill->label . ': ' . $skill->levelGoal;
		}

		// Sort all courses for the best semantic fit. 
		$semanticMatches = $pytonapi->sortsemantic($base, $results);

		$ai_suggestions = array();
		// Get top results as the courses with the most skill matches.
		$mostSkillMatches = array_slice($results, 0, 5);
		// Max 5 ai suggestions.
		$max_suggestions = 5;

		for ($i = 0; $i < count($semanticMatches) && count($ai_suggestions) < $max_suggestions; $i++) {
			if ($semanticMatches[$i]['score'] < .75) {
				// Do not recommend any courses where score is loaer than .75
				break;
			}

			// Check if course is one of the courses with the most skill matches.
			$mostSkillsMatched = false;
			foreach ($mostSkillMatches as $key => $course) {
				if ($course['id'] == $semanticMatches[$i]['id']) {
					$mostSkillsMatched = true;
					$semanticMatches[$i]['reason'][] = 'mostSkillsMatched';
					break;
				}
			}
			
			// Add to ai suggestion if score is higer than 85% or mostSkillsMatched is true.
			if ($semanticMatches[$i]['score'] > .85 OR $mostSkillsMatched) {
				$semanticMatches[$i]['reason'][] = 'semanticMatch';
				$ai_suggestions[] = $semanticMatches[$i];
			}
		}

		// Build the result set for the ai suggestions.
		if (count($ai_suggestions)) {
			// Remove keys that are not relevant for the client.
			foreach ($ai_suggestions as $key => $course) {
				unset($ai_suggestions[$key]['embedding']);
				unset($ai_suggestions[$key]['tags']);
				unset($ai_suggestions[$key]['thema']);
			}

			$sets[] = array(
				'label' => 'airecommends',
				'count' => count($ai_suggestions),
				'results' => $ai_suggestions,
			);
		}

		// Remove keys that are not relevant for the client.
		foreach ($semanticMatches as $key => $course) {
			unset($semanticMatches[$key]['embedding']);
			unset($semanticMatches[$key]['thema']);
		}

		// Build results sets for every skill by ordering all results by skill.
		foreach ($skills as $skill) {
			$skillresults = array();
			foreach ($semanticMatches as $key => $course) {
				if (in_array($skill->label, $course['tags']) or in_array(preg_replace('/ +\(ESCO\)/', '', $skill->label), $course['tags'])) {
					unset($course['tags']);
					$skillresults[] = $course;
				}
			}

			$sets[] = array(
				'label' => $skill->label,
				'skill' => $skill,
				'count' => count($skillresults),
				'results' => $skillresults,
			);
		}

		$end2 = new DateTime();
		$dur2 = $start2->diff($end2);
		// Print duration in milliseconds.
		$filterDurs[] = 'Duration: ' . $dur2->format('%s.%f') . ' seconds';
		$end1 = new DateTime();
		$dur1 = $start1->diff($end1);

		// Get average duration of $this->anbieter_durs
		$avg_anbieter = 0.0;
		foreach ($this->anbieter_durs as $dur) {
			$avg_anbieter += $dur->f;
		}
		if (count($this->anbieter_durs) > 0) {
			$avg_anbieter /= count($this->anbieter_durs);
		}

		$avg_durchf = 0.0;
		foreach ($this->durchf_durs as $dur) {
			$avg_durchf += $dur->f;
		}
		if (count($this->durchf_durs) > 0) {
			$avg_durchf /= count($this->durchf_durs);
		}

		$avg_complevel = 0.0;
		foreach ($this->complevel_durs as $dur) {
			$avg_complevel += $dur->f;
		}
		if (count($this->complevel_durs) > 0) {
			$avg_complevel /= count($this->complevel_durs);
		}

		$avg_stichwort = 0.0;
		foreach ($this->stichwort_durs as $dur) {
			$avg_stichwort += $dur->f;
		}
		if (count($this->stichwort_durs) > 0) {
			$avg_stichwort /= count($this->stichwort_durs);
		}

		// Build and send response object as json.
		$response = (object) array(
			'overallDur' => 'Duration: ' . $dur1->format('%s.%f') . ' seconds',
			'durPerFilter' => $filterDurs,
			'avg_anbieter' => $avg_anbieter,
			'avg_durchf' => $avg_durchf,
			'avg_complevel' => $avg_complevel,
			'avg_stichwort' => $avg_stichwort,
			'count' => count($results),
			'sets' => $sets,
			'queries' => $this->queries,
		);

		JSONResponse::send_json_response($response);
	}

	private function get_course_details(array $course): array|null {
		$start = new DateTime();

		$db = new DB_Admin();
		$db->query("SELECT anbieter.suchname 
					FROM anbieter 
					WHERE id=" . $course['anbieter']);
		if (!$db->next_record()) {
			JSONResponse::error500('Provider not found for course with id: ' . $course['id']);
		}
		$provider = $db->Record;

		$end = new DateTime();
		$dur = $start->diff($end);
		$this->anbieter_durs[] = $dur;



		$start = new DateTime();

		$db = new DB_Admin();
		$db->query("SELECT d.beginn, d.beginnoptionen, d.ende, d.dauer, d.zeit_von, d.zeit_bis, d.stunden, d.preis, d.ort
					FROM durchfuehrung d
					INNER JOIN kurse_durchfuehrung kd ON d.id = kd.secondary_id
					WHERE kd.primary_id = {$course['id']}
					ORDER BY  d.beginn = '0000-00-00 00:00:00', d.beginn
					LIMIT 1;
		");
		if (!$db->next_record()) {
			$durchfuehrung = array('preis' => -1);
		}
		$durchfuehrung = $db->Record;

		$end = new DateTime();
		$dur = $start->diff($end);
		$this->durchf_durs[] = $dur;


		$start = new DateTime();

		$db = new DB_Admin();
		$db->query("SELECT GROUP_CONCAT(s.tag_name) as tags, t.thema, ke.embedding
					FROM kurse k
					LEFT JOIN x_kurse_tags ks ON k.id = ks.kurs_id
					LEFT JOIN x_tags s ON ks.tag_id = s.tag_id
					LEFT JOIN themen t ON k.thema = t.id
					LEFT JOIN kurse_embedding ke ON ke.kurs_id = k.id
					WHERE k.id = {$course['id']}
					AND s.tag_eigenschaften IN (-1, 0, 524288, 1048576)
					AND s.tag_type = 0
					GROUP BY k.id, t.thema;");
		if (!$db->next_record()) {
			$tags = array();
			$thema = "";
			$embedding = null;
		}
		$tags = $db->Record['tags'];
		$thema = $db->Record['thema'];
		$embedding = $db->Record['embedding'];

		$end = new DateTime();
		$dur = $start->diff($end);
		$this->stichwort_durs[] = $dur;

		$start = new DateTime();

		$courseLevels = array_merge($this->get_course_comp_level($course['id']), $this->get_course_language_level($course['id']));

		$end = new DateTime();
		$dur = $start->diff($end);
		$this->complevel_durs[] = $dur;

		return array(
			'id' => $course['id'],
			'title' => utf8_encode($course['titel']),
			'provider' => utf8_encode($provider['suchname']),
			'levels' => $courseLevels,
			'mode' => utf8_encode($this->get_course_mode($course['id'])),
			'nextDate' => utf8_encode($this->get_next_date($durchfuehrung)),
			'workload' => utf8_encode($this->get_workload($durchfuehrung)),
			'price' => utf8_encode($this->get_price($durchfuehrung)),
			'location' => utf8_encode($durchfuehrung['ort']),
			'tags' => explode(',', utf8_encode($tags)),
			'thema' => utf8_encode($thema),
			'skillMatches' => $course['skillMatches'],
			'embedding' => $embedding,
		);
	}

	private function get_workload(array $durchfuehrung): string {
		return $this->durchfClass->formatDauer(
			$durchfuehrung['dauer'],
			$durchfuehrung['stunden'],
			'<span class="workload__duration">%1</span> <span class="workload__hours">(%2)</span>'
		);
	}

	private function get_price(array $durchfuehrung): string {
		return $this->durchfClass->formatPreis(
			$durchfuehrung['preis'],
			$durchfuehrung['sonderpreis'],
			$durchfuehrung['sonderpreistage'],
			$durchfuehrung['beginn'],
			$durchfuehrung['preishinweise'],
			true, // format as HTML
			array(
				'showDetails' => 1,
			)
		);
	}

	private function get_next_date(array $durchfuehrung): string {
		$begin = $durchfuehrung['beginn'];
		$end = $durchfuehrung['ende'];
		$time_begin = $durchfuehrung['zeit_von'];
		$time_end = $durchfuehrung['zeit_bis'];
		$date = '';
		$time = '';

		if ($durchfuehrung['beginnoptionen']) {
			$date = $this->durchfClass->formatBeginnoptionen($durchfuehrung['beginnoptionen']);
		} else if ($begin) {
			if ($end && $begin != $end) {
				$date = $this->framework->formatDatum($begin) . " - " . $this->framework->formatDatum($end);
			} else {
				$date = $this->framework->formatDatum($begin);
			}
		}

		$next_date = "<span class='next-date__date'>$date</span>";

		if ($time_begin && $time_end) {
			$time = $time_begin . " - " . $time_end;
			$next_date .= " <span class='next-date__time'>$time Uhr</span>";
		}

		return $next_date;
	}

	/**
	 * Get the competency level associated with a given course. 
	 *
	 * @param integer $courseID
	 * @return array Possible return values are ['A', 'B', 'C', '']. Empty if no level is associated with a course.
	 */
	function get_course_comp_level(int $courseID): array {
		$db = new DB_Admin();

		$sql = "SELECT stichwoerter.stichwort as level 
			FROM kurse_stichwort, stichwoerter
			WHERE primary_id = $courseID
				AND attr_id = stichwoerter.id
				AND (stichwoerter.stichwort = 'Niveau A'
					OR stichwoerter.stichwort = 'Niveau B'
					OR stichwoerter.stichwort = 'Niveau C'
				)
			;";

		$db->query($sql);

		$result = array();
		while ($db->next_record()) {
			$result[] = utf8_encode($db->Record['level']);
		}
		return $result;
	}

	/**
	 * Get the language level associated with a given course. 
	 *
	 * @param integer $courseID
	 * @return array Possible return values are ['A1', 'A2', 'B1', 'B2', 'C1', 'C2', '']. Empty if no level is associated with a course.
	 */
	function get_course_language_level(int $courseID): array {
		$db = new DB_Admin();

		$sql = "SELECT stichwoerter.stichwort as level 
			FROM kurse_stichwort, stichwoerter
			WHERE primary_id = $courseID
				AND attr_id = stichwoerter.id
				AND (stichwoerter.stichwort = 'A1'
					OR stichwoerter.stichwort = 'A2'
					OR stichwoerter.stichwort = 'B1'
					OR stichwoerter.stichwort = 'B2'
					OR stichwoerter.stichwort = 'C1'
					OR stichwoerter.stichwort = 'C2'
				)
			;";

		$db->query($sql);

		$result = array();
		while ($db->next_record()) {
			$result[] = str_replace('Niveau ', '', utf8_encode($db->Record['level']));
		}
		return $result;
	}

	/**
	 * Get the course mode (Unterrichtsart) of a given course. 
	 *
	 * @param integer $courseID
	 * @return string The course mode, empty if no coursemode is associated with the course.
	 */
	function get_course_mode(int $courseID): string {
		$db = new DB_Admin();

		$modes = [];

		$sql = "SELECT stichwoerter.stichwort as mode 
			FROM kurse_stichwort, stichwoerter
			WHERE primary_id = $courseID
				AND attr_id = stichwoerter.id
				AND stichwoerter.eigenschaften = 32768;";

		$db->query($sql);

		while ($db->next_record()) {
			$modes[] = $db->Record['mode'];
		}
		if (empty($modes)) {
			return '';
		}
		return join(', ', $modes);
	}

	function getKurseRecords($offset, $rows, $orderBy) {

		$ret = array('records' => array());

		if ($this->error === false) {
			$start = $this->framework->microtime_float();

			global $wisyPortalId;
			$do_recreate = true;
			$cacheKey = "wisysearch.$wisyPortalId.$this->queryString.$offset.$rows.$orderBy";
			if ($this->rawCanCache && ($temp = $this->dbCache->lookup($cacheKey)) != '') {
				// result in cache :-)
				$ret = unserialize($temp);
				if ($ret === false) {
					if (isset($_COOKIE['debug'])) {
						echo "<p style=\"background-color: yellow;\">getKurseRecords(): bad result for key <i>$cacheKey</i>, recreating  ...</p>";
					}
				} else {
					$do_recreate = false;
					if (isset($_COOKIE['debug'])) {
						echo "<p style=\"background-color: yellow;\">getKurseRecords(): result for key <i>$cacheKey</i> loaded from cache ...</p>";
					}
				}
			}


			if ($do_recreate) {
				switch ($orderBy) {
					case 'a':
						$orderBy = "x_kurse.anbieter_sortonly";
						break;	// sortiere nach anbieter
					case 'ad':
						$orderBy = "x_kurse.anbieter_sortonly DESC";
						break;
					case 't':
						$orderBy = 'kurse.titel_sorted';
						break;	// sortiere nach titel
					case 'td':
						$orderBy = 'kurse.titel_sorted DESC';
						break;
					case 'b':
						$orderBy = "x_kurse.beginn='0000-00-00', x_kurse.beginn";
						break;	// sortiere nach beginn, spezielle Daten ans Ende der Liste verschieben
					case 'bd':
						$orderBy = "x_kurse.beginn='9999-09-09', x_kurse.beginn DESC";
						break;
					case 'd':
						$orderBy = 'x_kurse.dauer=0, x_kurse.dauer';
						break;	// sortiere nach dauer
					case 'dd':
						$orderBy = 'x_kurse.dauer DESC';
						break;
					case 'p':
						$orderBy = 'x_kurse.preis=-1, x_kurse.preis';
						break;	// sortiere nach preis
					case 'pd':
						$orderBy = 'x_kurse.preis DESC';
						break;
					case 'o':
						$orderBy = "x_kurse.ort_sortonly='', x_kurse.ort_sortonly";
						break;	// sortiere nach ort
					case 'od':
						$orderBy = "x_kurse.ort_sortonly DESC";
						break;
					case 'creat':
						$orderBy = 'x_kurse.begmod_date';
						break;	// sortiere nach beginnaenderungsdatum (hauptsaechlich fuer die RSS-Feeds interessant)
					case 'creatd':
						$orderBy = 'x_kurse.begmod_date DESC';
						break;
					case 'rand':
						$ip = str_replace('.', '', $_SERVER['REMOTE_ADDR']);
						try {
							$seed = ((int)$ip + (int)date('d'));
						} catch (Exception $e) {
							$seed = 1;
						}
						$this->randSeed;
						$orderBy = 'RAND(' . $seed . ')';
						break;
					case 'skillMatches':
						$ip = str_replace('.', '', $_SERVER['REMOTE_ADDR']);
						try {
							$seed = ((int)$ip + (int)date('d'));
						} catch (Exception $e) {
							$seed = 1;
						}
						$this->randSeed;
						$orderBy = $this->orderBySkillMatches . ', x_kurse.begmod_date DESC';
						break;
					default:
						$orderBy = 'kurse.id';
						die('invalid order!');
				}



				$sql = $this->getKurseRecordsSql("kurse.id, kurse.user_grp, kurse.anbieter, kurse.thema, kurse.freigeschaltet, kurse.titel, kurse.vollstaendigkeit, kurse.date_modified, kurse.bu_nummer, kurse.fu_knr, kurse.azwv_knr, x_kurse.begmod_date, x_kurse.bezirk, x_kurse.ort_sortonly, x_kurse.ort_sortonly_secondary" . $this->fulltext_select);


				$sql .= " GROUP BY id ORDER BY $orderBy, vollstaendigkeit DESC, x_kurse.kurs_id";


				if ($rows != 0) $sql .= " LIMIT $offset, $rows ";

				$this->queries[] = utf8_encode($sql);

				$this->db->query("SET SQL_BIG_SELECTS=1"); // optional
				$this->db->query($sql);

				while ($this->db->next_record()) {
					$ret['records'][] = $this->db->Record;
				}
				$this->db->free();


				// add result to cache
				$this->dbCache->insert($cacheKey, serialize($ret));

				if (isset($_COOKIE['debug'])) {
					echo '<p style="background-color: yellow;">getKurseRecords(): ' . htmlspecialchars($sql) . '</p>';
				}
			}

			$this->secneeded += $this->framework->microtime_float() - $start;
		}

		return $ret;
	}

	function getKurseRecordsSql($fields) {
		$sql =  "SELECT DISTINCT $fields" . $this->selectSkillMatches . "
				   FROM kurse LEFT JOIN x_kurse ON x_kurse.kurs_id=kurse.id " . $this->rawJoin . ' LEFT JOIN x_kurse_tags xk ON x_kurse.kurs_id = xk.kurs_id' . $this->rawWhere;

		return $sql;
	}
}
