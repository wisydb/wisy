<?php
require_once('wisy-intellisearch-class.inc.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/core51/wisyki-json-response.inc.php');

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
	 * @param string $label The label of the skill to search for.
	 * @param string $level The level of the skill to search for.
	 * @return string The querystring.
	 */
	function get_querystring(string $label, string $level): string {
		return $label . ', ' . $level;
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
		$label = $_GET['label'];
		$level = $_GET['level'];
		$limit = $_GET['limit'];

		$querystring = $this->get_querystring($label, $level);

		$this->prepare($querystring);

		if (!$this->ok()) {
			JSONResponse::error500();
		}

		$kurse = $this->getKurseRecords(0, $limit ?? 0, 'rand');
		$kursecount = $this->getKurseCount();
		$exactmatch = array();
		if (!empty($kurse)) {
			$exactmatch = array_map([$this, 'get_course_details'], $kurse['records']);
			// Remove null array items.
			$exactmatch = array_values(array_filter($exactmatch, function($item) {
					return $item !== null;
			}));


		// If there arent a lot of exact matches, search for other close matches.
		// TODO: Search for courses with other levels and related esco skills.
		$closematch = array();
		// if (!isset($limit) || $kursecount < $limit) {
		// 	$searcher2 =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
		// 	$searcher2->prepare($label);

		// 	if (!$searcher2->ok()) {
		// 		JSONResponse::error500();
		// 	}

		// 	$morekurse = $searcher2->getKurseRecords(0, $limit ?? 0, 'rand');
		// 	if (!empty($morekurse)) {
		// 		foreach ($morekurse['records'] as $key => $closekurs) {
		// 			foreach ($kurse['records'] as $exactkurs) {
		// 				if ($closekurs == $exactkurs) {
		// 					unset($morekurse['records'][$key]);
		// 				}
		// 			}
		// 		}
		// 		$closematch = array_splice(array_map([$this, 'get_course_details'], $morekurse['records']), 0, $limit-$kursecount);
		// 		// Remove null array items.
		// 		$closematch = array_values(array_filter($closematch, function($item) {
		// 			return $item !== null;
		// 		}));
		// 	}
		}

		JSONResponse::send_json_response(array(
			'query' => $querystring,
			'skill' => array(
				'label' => $label,
				'levelGoal' => $level,
			),
			'exactMatch' => $exactmatch,
			'closeMatch' => $closematch,
			'count' => $this->getKurseCount(),
		));
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

		$courseLevels = array_merge($this->get_course_comp_level($course['id']), $this->get_course_language_level($course['id']));

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
				'showDetails'=>1,
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

		if($time_begin && $time_end) {
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
		if (isset($_GET['prepare']) AND $_GET['prepare'] === 'true') {
			$this->render_prepare();
		} else {
			$this->render_result();
		}
	}
};
