<?php
header('Content-Type: application/json; charset=utf-8');
require_once('lib/ki/wisyki-python-api.inc.php');

$pythonAPI = new WISY_KI_PYTHON_API;

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$params = array();
$filename = '';

// To prevent commandline arguments exceeding the allowed length of 8192 bytes, 
// the description is written to a file and the filename passed to the python script, 
// instead of the long description.
// if (strlen($data['title']) + strlen($data['description']) > 7500) {
//     $filename = dirname(__FILE__) . "/../temp/prediction".time().".txt";
//     $myfile = fopen($filename, "w");
//     fwrite($myfile, $data['description']);
//     fclose($myfile);
//     $params = array("title" => $data['title'], "description" => '', "filename" => $filename);
// } else {
//     $params = array("title" => $data['title'], "description" => $data['description']);
// }

// list($result, $exitcode) = $pythonAPI->exec_command(
//     "predict_comp_level.py",
//     $params,
//     "Kompetenzniveau konnte nicht bestimmt werden."
// );

$result = $pythonAPI->predict_comp_level($data['title'], $data['description']);

// Delete the temporary file containing the course description.
// if ($filename) {
//     unlink($filename);
// }

// if ($exitcode == 0) {
    echo(json_encode($result));
// }

