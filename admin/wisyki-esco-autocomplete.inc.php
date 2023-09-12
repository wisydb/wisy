<?php
header('Content-Type: application/json; charset=utf-8');
require_once('lib/ki/wisyki-esco-class.inc.php');

$esco = new WISY_KI_ESCO_CLASS;

$type = null;
if (isset($_GET['type'])) {
    $type = $_GET['type'];
}

$limit = null;
if (isset($_GET['limit'])) {
    $limit = $_GET['limit'];
}

$onlyrelevant = true;
if (isset($_GET['onlyrelevant'])) {
    $onlyrelevant = $_GET['onlyrelevant'];
}

$scheme = null;
if (isset($_GET['scheme'])) {
    $scheme = explode(',', $_GET['scheme']);
    if (!is_array($scheme)) {
        $scheme = array($scheme);
    }
}


$results = [];

if (isset($scheme) && !empty($scheme)) {
    if (count($scheme) > 1) {
        foreach($scheme as $s) {
            if ($s == 'extenden-skills-hierarchy') {
                $results['skills-hierarchy'] = $esco->search_skills_hierarchy($_GET['term'], $limit);
            } else if ($s == 'sachstichwort') {
                $results['sachstichwort'] = $esco->search_wisy($_GET['term'], $type, $s, $limit);
            } else {
                $results[$s] = $esco->search_api($_GET['term'], $type, $s, $limit);
            }
        }
    } else {
        if ($scheme[0] == 'extended-skills-hierarchy') {
            $results['skills-hierarchy'] = $esco->search_skills_hierarchy($_GET['term'], $limit);
        } else if ($scheme[0] == 'sachstichwort') {
            $results['sachstichwort'] = $esco->search_wisy($_GET['term'], $type, $scheme[0], $limit);
        } else {
            $results[$scheme[0]] = $esco->search_api($_GET['term'], $type, $scheme[0], $limit);
        }
    }
} else {
    $results[$type] = $esco->search_api($_GET['term'], $type, $scheme, $limit);
}

if (is_array($scheme) && !in_array('extenden-skills-hierarchy', $scheme) && array_key_exists('skills-hierarchy', $results) && empty($results['skills-hierarchy'])) {
    $results['skills-hierarchy'] = $esco->search_skills_hierarchy($_GET['term'], $limit);
}



if ($onlyrelevant == 1) {
    $results = $esco->filter_is_relevant($results);
}

echo json_encode($results, JSON_THROW_ON_ERROR);
