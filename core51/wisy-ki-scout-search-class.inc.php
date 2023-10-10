<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/core51/wisy-ki-json-response.inc.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/core51/wisy-ki-python-class.inc.php');

/**
 * Class WISY_KI_SCOUT_SEARCH_CLASS
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
class WISY_KI_SCOUT_SEARCH_CLASS extends WISY_SEARCH_CLASS {
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
	private $embedding_durs = array();
	private $orderBy = 'rand';
	private $queries = array();
	private $complevelids = array();
	private $langlevelids = array();
	private $skilltags = array();

	/**
	 * Constructor for WISY_KI_SCOUT_SEARCH_CLASS.
	 *
	 * @param WISY_FRAMEWORK_CLASS $framework The framework class instance.
	 * @param array $param An array of additional parameters.
	 */
	function __construct(&$framework, $param) {
		parent::__construct($framework, $param);
		$this->durchfClass = &createWisyObject('WISY_DURCHF_CLASS', $this->framework);
		$this->complevelids = $this->getTagIds(array('Niveau A', 'Niveau B', 'Niveau C'));
		$this->langlevelids = $this->getTagIds(array('A1', 'A2', 'B1', 'B2', 'C1', 'C2'));
	}

	function getTagIds (array $tags) {
		
		$levelids = array();
		$sql = "SELECT tag_id FROM x_tags WHERE tag_name IN ('" . join("', '", $tags) . "')";
		$this->db->query($sql);
		while ($this->db->next_record()) {
			$levelids[] = $this->db->Record['tag_id'];
		}
		return $levelids;
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
			$this->skilltags[] = $skilllabel;

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
		$querystring .= ', Berufliche Bildung ODER Sprache';

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
		// Get json post data
		$start1 = new DateTime();
		$json = file_get_contents('php://input');
		$data = json_decode($json);
		$skills = $data->skills;
		$occupation = $data->occupation;
		$filters = $data->filters;
		$limit = $data->limit;

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
		$pythonapi = new WISY_KI_PYTHON_CLASS();

		// Build string describing the user.
		// TODO Add additional info from account, if user loggedin.
		$base = '';
		if (isset($occupation) && isset($occupation->label)) {
			$base .= $occupation->label;
		}
		foreach ($skills as $skill) {
			$base .= ', ' . $skill->label . ': ' . $skill->levelGoal . ', ' . $skill->levelGoalID;
		}

		// Sort all courses for the best semantic fit. 
		$semanticMatches = $pythonapi->score_semantically($base, $results, false);

		// Adjust score based on levelGoal-Matching
		foreach ($semanticMatches as $coursekey => $course) {
			$fitsLevelGoal = false;
			foreach ($skills as $skillkey => $skill) {
				if (!in_array($skill->label, $course['tags']) AND  !in_array(preg_replace('/ +\(ESCO\)/', '', $skill->label), $course['tags'])) {
					continue;
				}
				if ($skill->levelGoalID == "" OR in_array($skill->levelGoalID, $course['levels'])) {
					$fitsLevelGoal = true;
					break;
				}
			}

			$semanticMatches[$coursekey]['fitsLevelGoal'] = $fitsLevelGoal;
			if (!$fitsLevelGoal) {
				$semanticMatches[$coursekey]['score'] += -.05;
			}
		}

        // Sort courses based on score.
        usort($semanticMatches, function ($a, $b) {
            return $a['score'] < $b['score'];
        });

		$ai_suggestions = array();
		// Get top results as the courses with the most skill matches.
		$mostSkillMatches = $results;
		// Sort courses based on score.
        usort($mostSkillMatches, function ($a, $b) {
            return $a['skillMatches'] < $b['skillMatches'];
        });
		$mostSkillMatches = array_slice($mostSkillMatches, 0, 5);
		// Max 5 ai suggestions.
		$max_suggestions = 5;

		for ($i = 0; $i < count($semanticMatches) && count($ai_suggestions) < $max_suggestions; $i++) {
			if ($semanticMatches[$i]['score'] < .75) {
				// Do not recommend any courses where score is lower than .75
				break;
			}

			// Check if course is one of the courses with the most skill matches.
			$mostSkillsMatched = false;
			foreach ($mostSkillMatches as $key => $course) {
				if ($course['skillMatches'] > 1 && $course['id'] == $semanticMatches[$i]['id']) {
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
				if (in_array($skill->label, $course['tags']) or in_array(preg_replace('/ +\(ESCO\)/', '', $skill->label), $course['tags']) or in_array($skill->label . " (ESCO)", $course['tags'])) {
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

		$end1 = new DateTime();
		$dur1 = $start1->diff($end1);
		$end2 = new DateTime();
		$filterDur = $start2->diff($end2);

		$e = new DateTime('00:00');
		$f = clone $e;
		$total_anbieter = $e;
		foreach ($this->anbieter_durs as $dur) {
			$total_anbieter->add($dur);
		}
		$total_anbieter = $total_anbieter->diff($f);

		$e = new DateTime('00:00');
		$f = clone $e;
		$total_durchf = $e;
		foreach ($this->durchf_durs as $dur) {
			$total_durchf->add($dur);
		}
		$total_durchf = $total_durchf->diff($f);

		$e = new DateTime('00:00');
		$f = clone $e;
		$total_complevel = $e;
		foreach ($this->complevel_durs as $dur) {
			$total_complevel->add($dur);
		}
		$total_complevel = $total_complevel->diff($f);
		
		$e = new DateTime('00:00');
		$f = clone $e;
		$total_stichwort = $e;
		foreach ($this->stichwort_durs as $dur) {
			$total_stichwort->add($dur);
		}
		$total_stichwort = $total_stichwort->diff($f);
		
		$e = new DateTime('00:00');
		$f = clone $e;
		$total_embedding = $e;
		foreach ($this->embedding_durs as $dur) {
			$total_embedding->add($dur);
		}
		$total_embedding = $total_embedding->diff($f);

		// Build and send response object as json.
		$response = (object) array(
			'dur_overall' => $dur1->s + $dur1->f,
			'dur_filterandsorting' => $filterDur->s + $filterDur->f,
			'dur_anbieter_sql' => $total_anbieter->s+ $total_anbieter->f,
			'dur_durchf_sql' => $total_durchf->s + $total_durchf->f,
			'dur_complevel_sql' => $total_complevel->s + $total_complevel->f,
			'dur_stichwort_sql' => $total_stichwort->s + $total_stichwort->f,
			'dur_embedding_sql' => $total_embedding->s + $total_embedding->f,
			'count' => count($results),
			'sets' => $sets,
			'queries' => $this->queries,
		);

		JSONResponse::send_json_response($response);
	}

	private function get_course_details(array $course): array|null {
		$start = new DateTime();

		
		$this->db->query("SELECT anbieter.suchname 
					FROM anbieter 
					WHERE id=" . $course['anbieter']);
		if (!$this->db->next_record()) {
			JSONResponse::error500('Provider not found for course with id: ' . $course['id']);
		}
		$provider = $this->db->Record;

		$end = new DateTime();
		$dur = $start->diff($end);
		$this->anbieter_durs[] = $dur;



		$start = new DateTime();
		$today = strftime("%Y-%m-%d %H:%M:%S");
		
		$this->db->query("SELECT d.beginn, d.beginnoptionen, d.ende, d.dauer, d.zeit_von, d.zeit_bis, d.stunden, d.preis, d.ort
					FROM durchfuehrung d
					INNER JOIN kurse_durchfuehrung kd ON d.id = kd.secondary_id
					WHERE kd.primary_id = {$course['id']}
					ORDER BY  d.beginn = '0000-00-00 00:00:00', d.beginn;
		");
		$durchfuehrung = null;
		while ($this->db->next_record()) {
			if ($this->db->Record['beginn'] >= $today || $this->db->Record['beginn'] == '0000-00-00 00:00:00') {
				$durchfuehrung = $this->db->Record;
				break;
			}
		}
		if (!$durchfuehrung) {
			$durchfuehrung = array('preis' => -1);
		}
		$end = new DateTime();
		$dur = $start->diff($end);
		$this->durchf_durs[] = $dur;


		$start = new DateTime();

		
		$this->db->query("SELECT GROUP_CONCAT(s.tag_name ORDER BY s.tag_name) as tags, t.thema
					FROM kurse k
					INNER JOIN x_kurse_tags ks ON k.id = ks.kurs_id
					INNER JOIN x_tags s ON ks.tag_id = s.tag_id
					LEFT JOIN themen t ON k.thema = t.id
					WHERE k.id = {$course['id']}
					AND s.tag_eigenschaften IN (-1, 0, 524288, 1048576)
					AND s.tag_type = 0
					GROUP BY k.id, t.thema;");
		if (!$this->db->next_record()) {
			$tags = array();
			$skillMatches = 0;
			$thema = "";
		} else {
			$tags = explode(',', utf8_encode($this->db->Record['tags']));
			$skillMatches = 0;
			foreach ($this->skilltags as $skilltag) {
				if (in_array($skilltag, $tags)) {
					$skillMatches++;
				}
			}
			$thema = $this->db->Record['thema'];
		}

		$end = new DateTime();
		$dur = $start->diff($end);
		$this->stichwort_durs[] = $dur;


		$start = new DateTime();

		
		$this->db->query("SELECT ke.embedding
					FROM kurse_embedding ke
					WHERE ke.kurs_id = {$course['id']};");
		if (!$this->db->next_record()) {
			$embedding = null;
		} else {
			$embedding = $this->db->Record['embedding'];
		}

		$end = new DateTime();
		$dur = $start->diff($end);
		$this->embedding_durs[] = $dur;

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
			'tags' => $tags,
			'thema' => utf8_encode($thema),
			'skillMatches' => $skillMatches,
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
	 * @return array Possible return values are ['Niveau A', 'Niveau B', 'Niveau C']. Empty if no level is associated with a course.
	 */
	function get_course_comp_level(int $courseID): array {
		

		$sql = "SELECT t.tag_name as level
				FROM x_tags t
				JOIN x_kurse_tags kt ON kt.tag_id = t.tag_id
				WHERE kt.kurs_id = $courseID
				AND t.tag_id IN (" . join(', ', $this->complevelids) . ")";

		$this->db->query($sql);

		$result = array();
		while ($this->db->next_record()) {
			$result[] = utf8_encode($this->db->Record['level']);
		}
		return $result;
	}

	/**
	 * Get the language level associated with a given course. 
	 *
	 * @param integer $courseID
	 * @return array Possible return values are ['A1', 'A2', 'B1', 'B2', 'C1', 'C2']. Empty if no level is associated with a course.
	 */
	function get_course_language_level(int $courseID): array {
		$sql = "SELECT t.tag_name as level
				FROM x_tags t
				JOIN x_kurse_tags kt ON kt.tag_id = t.tag_id
				WHERE kt.kurs_id = $courseID
				AND t.tag_id IN (" . join(', ', $this->langlevelids) . ")";

		$this->db->query($sql);

		$result = array();
		while ($this->db->next_record()) {
			$result[] = utf8_encode($this->db->Record['level']);
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
		$modes = [];

		$sql = "SELECT stichwoerter.stichwort as mode 
			FROM kurse_stichwort, stichwoerter
			WHERE primary_id = $courseID
				AND attr_id = stichwoerter.id
				AND stichwoerter.eigenschaften = 32768;";

		$this->db->query($sql);

		while ($this->db->next_record()) {
			$modes[] = $this->db->Record['mode'];
		}
		if (empty($modes)) {
			return '';
		}
		return join(', ', $modes);
	}
 
	function getKurseRecordsSql($fields) { 
		// Only get courses that are freigeschaltet or dauerhaft
		$this->rawWhere .= $this->rawWhere ? ' AND ' : ' WHERE ';
		$this->rawWhere .= "kurse.freigeschaltet IN (1, 4)";
		$sql =  "SELECT DISTINCT $fields
				FROM kurse LEFT JOIN x_kurse ON x_kurse.kurs_id=kurse.id " . $this->rawJoin . ' LEFT JOIN x_kurse_tags xk ON x_kurse.kurs_id = xk.kurs_id' . $this->rawWhere; 
 
 		$this->queries[] = utf8_encode($sql); 
		return $sql; 
	}
}
