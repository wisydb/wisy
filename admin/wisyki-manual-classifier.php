<?php
require_once('lib/ki/wisyki-python-api.inc.php');

$pythonAPI = new WISYKI_PYTHON_API;

function get_course_without_level($levelidlist) {
    $db = new DB_Admin();

    $sql = "SELECT kurse.id, kurse.titel, kurse.beschreibung FROM kurse, kurse_stichwort WHERE kurse_stichwort.primary_id = kurse.id AND kurse_stichwort.attr_id NOT IN ($levelidlist) ORDER BY RAND() LIMIT 1";

    $db->query($sql);

    if ($db->next_record()) { 
        $course = $db->Record;
    }
    
    return $course;
}

function count_courses_with_level($levelidlist) {
    $db = new DB_Admin();

    $sql = "SELECT COUNT(kurse.id) count FROM kurse, kurse_stichwort WHERE kurse_stichwort.primary_id = kurse.id AND kurse_stichwort.attr_id IN ($levelidlist)";

    $db->query($sql);

    if ($db->next_record()) { 
        $count = $db->Record['count'];
    }
    
    return $count;
}

function count_all_courses() {
    $db = new DB_Admin();

    $sql = "SELECT COUNT(kurse.id) count FROM kurse";

    $db->query($sql);

    if ($db->next_record()) { 
        $count = $db->Record['count'];
    }
    
    return $count;
}

function get_course($courseid) {
    $db = new DB_Admin();
    $course = array();

    $sql = "SELECT kurse.id, kurse.titel, kurse.beschreibung FROM kurse WHERE kurse.id = $courseid";

    $db->query($sql);

    if ($db->next_record()) { 
        $course = $db->Record;
    }
    
    return $course;
}

function get_level_ids($levels) {
    $db = new DB_Admin(); 
    foreach ($levels as $key => $value) {
        $sql = 'SELECT id FROM stichwoerter WHERE stichwoerter.stichwort = "Niveau ' . $key . '"'; 
        $db->query($sql); 
        if ($db->next_record()) {
            $levels[$key]['id'] = $db->Record['id']; 
        } 
    } 

    return $levels; 
}

function round_report(array $report): array {
    $rounded = array();
    foreach ($report as $key => $value) {
        if (is_array($value)) {
            $rounded[$key] = round_report($value);
        } elseif (is_float($value)) {
            $rounded[$key] = number_format($value, 4, ',', '');
        } else {
            $rounded[$key] = $value;
        }
    }
    return $rounded;
}

function pagestart($title) {
    echo (" <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta http-equiv='X-UA-Compatible' content='IE=edge'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <link rel='stylesheet' href='wisyki-esco-test.css'>
                <title>$title</title>
            </head>

            <body class='manual-classification'>
            <header>
                <h1>Manuelle Kursklassifikation</h1>
            </header>");
}

function pageend() {
    echo ("</body></html>");
}

$pageuri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$levels = [
    'A' => [
        'name' => 'Grundstufe', 'id' => ''
    ], 
    'B' => [
        'name' => 'Aufbaustufe', 'id' => ''
    ], 
    'C' => [
        'name' => 'Fortgeschrittenenstufe', 'id' => ''
    ], 
    'D' => [
        'name' => 'Expertenstufe', 'id' => ''
    ],
];
$levels = get_level_ids($levels);
$levelids = array();
foreach ($levels as $level) {
    $levelids[] = $level['id'];
}
$levelidlist = "'" . implode("', '", $levelids) . "'";
$course = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['level-selection']) AND isset($_POST['courseid'])) {
        // Update course.
        $courseid = $_POST['courseid'];
        $selectedlevel = $_POST['level-selection'];
        $levelid = $levels[$selectedlevel]['id'];
        $levelname = $levels[$selectedlevel]['name'];

        $db = new DB_Admin(); 

        // If there are already levels associated with the course, delete them.
        $sql = "DELETE FROM kurse_stichwort WHERE kurse_stichwort.primary_id = $courseid AND kurse_stichwort.attr_id IN ($levelidlist)";
        $db->query($sql);

        // Insert level as a stichwort.
        $sql = "INSERT INTO kurse_stichwort (primary_id, attr_id) VALUES ($courseid, $levelid)"; 
        $db->query($sql);

        $labeled_courses = count_courses_with_level($levelidlist);
        $all_courses = count_all_courses();
        $trainings_progress = number_format(($labeled_courses / $all_courses) * 100, 2, ',', '');
        $progress_value = max($trainings_progress, 5);

        $report = $pythonAPI->get_comp_level_report();
        $rounded_report = round_report($report);
        $json_report = json_encode($rounded_report, JSON_PRETTY_PRINT);

        $model_accuracy = number_format($report['accuracy'] * 100, 2, ',', '');
        $trainings_date = date("d.m.Y h:i", $report['time']) . ' Uhr';
        $execution_time = number_format($report['executiontime'], 2, ',', '');
        // HTML response.
        pagestart('Erfolgreiche Kursklassifikation');
        
        ?>
        <main class="manual-classification_review">
            <h2>Vielen Dank für deinen Beitrag!</h2>
            <p>Dank deiner Unterstützung kann das KI-Modell lernen passgenauere Entscheidungen zu treffen.</p>
            <br>
            <label for="trainings-progress">Trainings Fortschritt:</label>
            <progress id="trainings-progress" value="<?php echo $progress_value ?>" max="100"> <?php echo $trainings_progress ?>% </progress>
            <p><?php echo $labeled_courses ?>/<?php echo $all_courses ?> Kurse wurden bereits klassifiziert.</p>
            <br>
            <details>
                <summary>KI-Model Genauigkeit: <?php echo $model_accuracy ?>%</summary>
                <br>
                <ul>
                    <li>Modellname: <?php echo $report['modelname'] ?></li>
                    <li>Zuletzt trainiert am <?php echo $trainings_date ?></li>
                    <li>Anzahl der Trainingsdaten: <?php echo $report['weighted avg']['support'] ?></li>
                    <li>Trainingsdauer: <?php echo $execution_time ?> Sekunden</li>
                </ul>
                <br>
                <details>
                    <summary>Detailierte Statistiken</summary>
                    <pre><?php echo $json_report ?></pre>
                </details>
            </details>
            <section class="actions">
                <a href="<?php echo $pageuri . '?courseid=' . $courseid ?>" class="btn btn-secondary">Auswahl korrigieren</a>
                <a href="<?php echo $pageuri ?>" class="btn btn-primary">Nächster Kurs</a>
            </section>
        </main>
        <script type="application/x-javascript" src=lib/ki/js/p5/p5.min.js></script>
        <script type="application/x-javascript" src=lib/ki/js/sketch.js></script>
        <script type="application/x-javascript" src=lib/ki/js/particle.js></script>
        <script type="application/x-javascript" src=lib/ki/js/tailParticle.js></script>
        <script type="application/x-javascript" src=lib/ki/js/firework.js></script>
        <?php

        pageend();
    }
} else {
    $course = array();
    if (isset($_GET['courseid'])) {
        $course = get_course($_GET['courseid']);
    } else {
        $course = get_course_without_level($levelidlist);
    }
    if (empty($course)) {
        pagestart('Error - Manuelle Kursklassifikation');
        echo('Es konnte kein Kurs gefunden werden, bitte lade die Seite erneut.');
    } else {
        // list($result, $exitcode) = $pythonAPI->exec_command(
        //     "predict_comp_level.py",
        //     array("title" => $course['titel'], "description" => $course['beschreibung']),
        //     "Kompetenzniveau konnte nicht bestimmt werden."
        // );
        $prediction = $pythonAPI->predict_comp_level($course['titel'], $course['beschreibung']);
        $course['level_suggestion'] = $prediction['level'];
        pagestart('Manuelle Kursklassifikation');

        ?>

        <main class="manual-classification_selection">
            <h2>Bestimme ein passendes Kompetenzniveau für den folgenden zufällig ausgewählten Kurs.</h2>
            <section class="course-info">
                <label for="course-title">Titel</label>
                <p id="course-title"><?php echo $course['titel'] ?></p>
                <label for="course-id">ID</label>
                <p id="course-id"><?php echo $course['id'] ?></p>
                <label for="course-description">Beschreibung</label>
                <p id="course-description"><?php echo $course['beschreibung'] ?></p>
            </section>
            <form method="post" name="level-selection-form">
                <section class="form-input">
                    <label for="level-selection">Wähle ein passendes Kompetenzniveau.</label>
                    <input type="hidden" name="courseid" value="<?php echo $course['id'] ?>">
                    <select name="level-selection" id="level-selection">
                    <?php
                    
                    $index = 0;
                    foreach ($levels as $key => $value) {
                        $level = $value['name'];
                        $level_probability = number_format($prediction['class_probability'][$index++] * 100, 2, ',', '');
                        if ($key == $course['level_suggestion']) {
                            echo("<option value='$key' selected='selected'>$level - empfohlen ($level_probability%)</option>");
                        } else {
                            echo("<option value='$key'>$level ($level_probability%)</option>");
                        }
                    }
                    ?>
                    </select>
                </section>
                <section class="form-action">
                    <a href="<?php echo $pageuri ?>" class="btn btn-secondary">Überspringen</a>
                    <button type="submit" class="btn btn-primary">Abschicken</button>
                </section>
                <section class="help">
                    <label for="help-actions">Hilfe</label>
                    <section class="help-actions">
                        <a href="https://www.dqr.de/SiteGlobals/Forms/dqr/de/qualifikationssuche/suche_formular.html" target="_blank" rel="noopener noreferrer" class="btn">DQR-Suche</a>
                        <a href="https://europa.eu/europass/de/find-courses" target="_blank" rel="noopener noreferrer" class="btn">EQR-Suche</a>
                        <a href="https://sh.kursportal.info/k<?php echo $course['id'] ?>" target="_blank" rel="noopener noreferrer" class="btn">Zum Angebot im Kursportal</a>
                    </section>
                </section>
            </form>
        </main>

        <?php
    }
    pageend();
}