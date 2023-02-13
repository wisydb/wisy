<?php
if (!defined('IN_WISY')) die('!IN_WISY');
require_once('wisy-intellisearch-class.inc.php');

class WISYKI_SCOUT_SEARCH_CLASS extends WISY_INTELLISEARCH_CLASS {
	function __construct(&$framework, $param) {
		parent::__construct($framework, $param);
	}

	function search(): array {
		// return array(
		// 	'id' => 'scout-search#1',
		// 	'skill' => array(
		// 		'uri' => 'asd',
		// 		'label' => 'mit Kunden kommunizieren',
		// 		'levelGoal' => 'C',
		// 	),
		// 	'count' => 4,
		// 	'results' => array(
		// 		array(
		// 			'title' => 'Sales Professional',
		// 			'level' => 'C',
		// 			'provider' => 'Wirtschaftsakademie Schleswig-Holstein GmbH'
		// 		),
		// 	));
	}

	function get_querystring($label, $level) : string {
		return $label . ', Niveau ' . $level;
		// return $label;
	}

	function render_prepare() {
		header('Content-Type: application/json; charset=UTF-8');

		$label = $_GET['label'];
		$level = $_GET['level'];
		
		$querystring = $this->get_querystring($label, $level);

		$this->prepare($querystring);

		if ($this->ok()) {
			echo json_encode(array(
				'id' => $querystring,
				'skill' => array(
					'label' => $label,
					'levelGoal' => $level,
				),
				'count' => $this->getKurseCount(),
			));
		} else {
			$this->error_500();
		}
	}

	function render_result() {
		header('Content-Type: application/json; charset=UTF-8');
		$db = new DB_Admin();

		$label = $_GET['label'];
		$level = $_GET['level'];

		$querystring = $this->get_querystring($label, $level);

		$this->prepare($querystring);

		if ($this->ok()) {
			$kurse = $this->getKurseRecords(0, 3, 'rand');
			$result = array();
	
			foreach($kurse['records'] as $kurs) {
				// Get provider title.
				$db->query("SELECT anbieter.suchname FROM anbieter WHERE id=" . $kurs['anbieter']);
				$db->next_record();
				$provider = $db->Record;

				$result[$kurs['id']] = array(
					'title' => utf8_encode($kurs['titel']),
					'provider' => utf8_encode($provider['suchname']),
					'level' => utf8_encode($this->get_course_comp_level($kurs['id'])),
					'mode' => utf8_encode($this->get_course_mode($kurs['id'])),
					'nextDate' => '$nextDate',
					'workload' => '$workload',
					'price' => '$price',
					'location' => '$location',
				);
			}

			echo json_encode(array(
				'id' => $querystring,
				'skill' => array(
					'label' => $label,
					'levelGoal' => $level,
				),
				'result' => $result,
			));
		} else {
			$this->error_500();
		}
	}

    /**
     * Get the Competency Level associated with a given course. 
     *
     * @param integer $courseID
     * @return string Possible return values are ['A', 'B', 'C', 'D', '']. Empty if no level is associated with a course.
     */
	function get_course_comp_level(int $courseID):string {
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

		if ($db->next_record() ) {
			return str_replace('Niveau ', '', $db->Record['level']);
		}
		return '';
	}

    /**
     * Get the course mode (Unterrichtsart) of a given course. 
     *
     * @param integer $courseID
     * @return string The course mode, empty if no coursemode is associated with the course.
     */
	function get_course_mode(int $courseID):string {
		$db = new DB_Admin();

		$sql = "SELECT stichwoerter.stichwort as level 
			FROM kurse_stichwort, stichwoerter
			WHERE primary_id = $courseID
				AND attr_id = stichwoerter.id
				AND stichwoerter.eigenschaften = 32768;";
		
		$db->query($sql);

		if ($db->next_record() ) {
			return str_replace('Niveau ', '', $db->Record['level']);
		}
		return '';
	}

    function error_500() {
        http_response_code(500);
        echo json_encode(array(
            'error_code' => '500',
            'error_message' => 'Server error - please retry again at a later time.',
        ));
        die();
    }

	function render() {
		if (isset($_GET['prepare'])) {
			$this->render_prepare();
		} else {
			$this->render_result();
		}
	}
};
