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
class WISYKI_SCOUT_SEARCH_CLASS extends WISY_INTELLISEARCH_CLASS {
	/**
	 * Helper functions for rendering info about a 'durchfuhrung'.
	 *
	 * @var WISY_DURCHF_CLASS
	 */
	private WISY_DURCHF_CLASS $durchfClass;

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
	 * @param object $skill The skill to search for.
	 * @param object $filters Filtersettings to narrow the search results.
	 * @return string The querystring.
	 */
	function get_querystring(object $skill, object $filters): string {
		$querystring = utf8_decode($skill->label);
		if ($filters->coursemode) {
			for ($i = 0; $i < count($filters->coursemode); $i++) {
				$coursemode = $filters->coursemode[$i];
				if ($i == 0) {
					$querystring .= ', ' . utf8_decode($coursemode);
				} else {
					$querystring .= ' ODER ' . utf8_decode($coursemode);
				}
			}
		}

		return $querystring;
	}

	/**
	 * Renders the search results after preparing the search based on the skill label and level.
	 * 
	 * @return void Echoes the an overview of the Search in JSON format.
	 */
	function render_prepare() {
		$label = $_GET['label'];
		$level = $_GET['level'];

		$querystring = $this->get_querystring($label, $level);

		$this->prepare($querystring);
		// TODO: Cache the search results for later retrieval.

		if (!$this->ok()) {
			JSONResponse::error500();
		}

		JSONResponse::send_json_response(array(
			'query' => $querystring,
			'skill' => array(
				'label' => $label,
				'levelGoal' => $level,
			),
			'count' => $this->getKurseCount(),
		));
	}

	/**
	 * Renders the search results after preparing the search based on the skill label and level.
	 *
	 * @return void Echoes a JSON response.
	 */
	function render_result() {
		$skillsjson = $_GET['skills'];
		$skills = json_decode($skillsjson);
		$filterjson = $_GET['filters'];
		$filters = json_decode($filterjson);
		$limit = $_GET['limit'];


		$results = array();

		foreach ($skills as $skill) {
			$querystring = $this->get_querystring($skill, $filters);

			$this->prepare($querystring);
			if (!$this->ok()) {
				JSONResponse::error500();
			}

			$kurse = $this->getKurseRecords(0, $limit ?? 0, 'rand');
			$kursecount = $this->getKurseCount();
			$match = array();
			if (!empty($kurse)) {
				$match = array_map([$this, 'get_course_details'], $kurse['records']);
				// Remove null array items.
				$match = array_values(array_filter($match, function ($item) {
					return $item !== null;
				}));
			}

			// Set skill foreach match
			foreach ($match as $key => $course) {
				$match[$key]['skill'] = $skill->label;
			}

			$results = array_merge($results, $match);
		}

		// Sort the results based on semantic similarity.
		$pytonapi = new WISYKI_PYTHON_CLASS();
		$base = '';
		foreach ($skills as $skill) {
			$base .= $skill->label . ': ' . $skill->levelGoal . ', ';
		}
		$sorted = $pytonapi->sortsemantic($base, $results);

		foreach ($sorted as $key => $course) {
			// Remove keys that are not relevant for the client.
			unset($sorted[$key]['description']);
			unset($sorted[$key]['thema']);
			unset($sorted[$key]['tags']);
		}


		$sets = array();

		// Get first 5 values from $sorted.
		$ai_suggestions = array_slice($sorted, 0, 5);
		$sets[] = array(
			'label' => 'airecommends',
			'count' => count($ai_suggestions),
			'results' => $ai_suggestions,
		);

		foreach ($skills as $skill) {
			$skillresults = array();
			foreach ($sorted as $key => $course) {
				if ($course['skill'] == $skill->label) {
					unset($course['skill']);
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

		$response = (object) array(
			'count' => count($results),
			'sets' => $sets,
		);

		JSONResponse::send_json_response($response);
	}

	private function get_course_details(array $course): array|null {
		$db = new DB_Admin();
		$db->query("SELECT anbieter.suchname 
					FROM anbieter 
					WHERE id=" . $course['anbieter']);
		if (!$db->next_record()) {
			JSONResponse::error500();
		}
		$provider = $db->Record;


		$db = new DB_Admin();
		$today = strftime("%Y-%m-%d");
		$db->query("SELECT d.beginn, d.beginnoptionen, d.ende, d.dauer, d.zeit_von, d.zeit_bis, d.stunden, d.preis, d.ort 
					FROM durchfuehrung d, kurse_durchfuehrung kd, x_kurse 
					WHERE d.id = kd.secondary_id 
					AND kd.primary_id={$course['id']} 
					AND (d.beginn>='$today' OR d.beginn=0) 
					ORDER BY d.beginn ASC LIMIT 1;");
		if (!$db->next_record()) {
			return null;
		}
		$durchfuehrung = $db->Record;


		$db = new DB_Admin();
		$db->query("SELECT k.id, GROUP_CONCAT(s.stichwort SEPARATOR ', ') AS stichwort, t.thema
					FROM kurse k
					JOIN kurse_stichwort ks ON k.id = ks.primary_id
					JOIN stichwoerter s ON ks.attr_id = s.id
					JOIN themen t ON t.id = k.thema
					WHERE k.id = {$course['id']}
					AND s.eigenschaften IN (0, 524288, 1048576)
					GROUP BY k.id, t.thema;");
		if (!$db->next_record()) {
			return null;
		}
		$tags = $db->Record['stichwort'];
		$thema = $db->Record['thema'];

		$courseLevels = array_merge($this->get_course_comp_level($course['id']), $this->get_course_language_level($course['id']));

		return array(
			'id' => $course['id'],
			'title' => utf8_encode($course['titel']),
			'description' => utf8_encode($course['beschreibung']),
			'provider' => utf8_encode($provider['suchname']),
			'levels' => $courseLevels,
			'mode' => utf8_encode($this->get_course_mode($course['id'])),
			'nextDate' => utf8_encode($this->get_next_date($durchfuehrung)),
			'workload' => utf8_encode($this->get_workload($durchfuehrung)),
			'price' => utf8_encode($this->get_price($durchfuehrung)),
			'location' => utf8_encode($durchfuehrung['ort']),
			'tags' => utf8_encode($tags),
			'thema' => utf8_encode($thema),
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
	 * @return array Possible return values are ['A', 'B', 'C', 'D', '']. Empty if no level is associated with a course.
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
					OR stichwoerter.stichwort = 'Niveau D'
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

	/**
	 * Renders the search reults.
	 * 
	 * @var $_GET['prepare] determines wether the search is only supposed to be prepared
	 *  and an overview of the search result rendered or the complete reults should be rendered.
	 *
	 * @return void
	 */
	function render() {
		if (isset($_GET['prepare']) and $_GET['prepare'] === 'true') {
			$this->render_prepare();
		} else {
			$this->render_result();
		}
	}
};
