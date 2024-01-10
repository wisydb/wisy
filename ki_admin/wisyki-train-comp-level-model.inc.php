<?php
header('Content-Type: application/json');
require_once('lib/ki/wisyki-python-api.inc.php');

$pythonAPI = new WISY_KI_PYTHON_API;

$db = new DB_Admin();
$kurse = get_kurse_with_level();
$labledCoursesFile = './lib/ki/python/data/labeledCourses.json';
if (file_exists($labledCoursesFile)) {
    $labledCourses = json_decode(file_get_contents($labledCoursesFile), true);
} else {
    $labledCourses = [];
}
$result = array();

if ($kurse) {
    foreach ($kurse as $kurs) {
        $exists = false;
        for ($i = 0; $i < count($labledCourses); $i++) {
            if ($labledCourses[$i]["id"] == $kurs["id"]) {
                $exists = true;
                $labledCourses[$i]["text"] = utf8_encode($kurs["titel"] . " \n\n " . $kurs["beschreibung"]);
                $labledCourses[$i]["label"] = utf8_encode($kurs["label"]);
                break;
            }
        }

        if(!$exists) {
            $labledCourses[] = array(
                "id" => $kurs["id"],
                "text" => utf8_encode($kurs["titel"] . "\n\n" . $kurs["beschreibung"]),
                "label" => utf8_encode($kurs["label"]),
            );
        }
    }
}

$jsonLabeledCourses = json_encode($labledCourses, JSON_THROW_ON_ERROR);
file_put_contents($labledCoursesFile, $jsonLabeledCourses);

// Start time.
$start_time = microtime(true);
// list($training_result, $exitcode) = $pythonAPI->exec_command(
//     "train_comp_level_model.py",
//     array(),
//     "Kompetenzniveau-KI-Modell konnte nicht trainiert werden."
// );



$training_result = $pythonAPI->train_comp_level_model($jsonLabeledCourses);

// End time.
$end_time = microtime(true);
// Get execution time of python script.
$execution_time = $end_time - $start_time;
    
$result['Training data'] = array(
    'labeled courses in db' => count($kurse),
    'labeled courses total' => count($labledCourses),
    'Execution time in seconds' => $execution_time,
);

$result['Training result'] = $training_result;
echo(json_encode($result));

function get_level_ids() {
    $levels = ['Niveau A', 'Niveau B', 'Niveau C', 'Niveau D'];

    $db = new DB_Admin();
    foreach ($levels as $level) {
        $sql = 'SELECT id FROM stichwoerter WHERE stichwoerter.stichwort = "' . $level . '"';
        $db->query($sql);
        if ($db->next_record()) {
            $levelids[str_replace("Niveau ", "", $level)] = $db->Record['id'];
        }
    }

    return $levelids;
}

function get_kurse_with_level() {
    $kurse = array();
    $levelids = get_level_ids();

    $db = new DB_Admin();
    foreach ($levelids as $level => $levelid) {
        $sql = 'SELECT id, titel, beschreibung FROM kurse, kurse_stichwort WHERE kurse.id = kurse_stichwort.primary_id AND kurse_stichwort.attr_id = "' . $levelid . '"';
        $db->query($sql);
        while ($db->next_record()) {
            $kurse[] = array("id" => $db->Record['id'], "titel" => $db->Record['titel'], "beschreibung" => $db->Record['beschreibung'], "label" => $level);
        }
    }

    return $kurse;
}