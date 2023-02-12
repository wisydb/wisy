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

		$label = $_GET['label'];
		$level = $_GET['level'];

		$querystring = $this->get_querystring($label, $level);

		$this->prepare($querystring);

		if ($this->ok()) {
			$kurse = $this->getKurseRecords(0, 3, 'rand');
			$result = array();
	
			foreach($kurse['records'] as $kurs) {
				$result[$kurs['id']] = array(
					'title' => utf8_encode($kurs['titel']),
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
