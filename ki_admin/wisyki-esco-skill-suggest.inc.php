<?php
header('Content-Type: application/json; charset=utf-8');
require_once('lib/ki/wisyki-esco-class.inc.php');

$esco = new WISY_KI_ESCO_CLASS;

$result = $esco->getSkillsOf($_GET["uri"]);
if (is_array($result)) {
    echo json_encode($result);
} else {
    echo $result;
}
