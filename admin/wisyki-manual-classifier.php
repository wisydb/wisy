<?php
require_once('lib/ki/wisyki-python-api.inc.php');
require_once('../core51/wisy-ki-esco-class.inc.php');

$pythonAPI = new WISY_KI_PYTHON_API;
$escoAPI = new WISY_KI_ESCO_CLASS; 

function get_course_without_level($levelidlist) {
    $db = new DB_Admin();

    $sql = "SELECT kurse.id, kurse.titel, kurse.beschreibung, themen.thema
    FROM kurse
    LEFT JOIN themen
        ON themen.id = kurse.thema
    WHERE kurse.id NOT IN (
        SELECT kurse_stichwort.primary_id 
        FROM kurse_stichwort 
        WHERE kurse_stichwort.attr_id IN ($levelidlist)
    )
    ORDER BY RAND()
    LIMIT 1";

    $db->query($sql);

    if ($db->next_record()) { 
        $course = $db->Record;
        $course['tags'] = get_course_tags($course['id']);
    }
    
    return $course;
}

function get_course_tags($courseid) {
    $db = new DB_Admin();

    // SQL query to get course tags of type Sachstichwort and Abschluss.
    $sql = "SELECT stichwoerter.stichwort, stichwoerter.eigenschaften 
        FROM stichwoerter
        LEFT JOIN kurse_stichwort
            ON kurse_stichwort.attr_id = stichwoerter.id
        WHERE kurse_stichwort.primary_id = $courseid
        AND stichwoerter.eigenschaften IN (0,1);";

    $db->query($sql);

    $sachstichworte = array();
    $abschluesse = array();
    while ($db->next_record()) { 
        if ($db->Record['eigenschaften'] == 0) {
            $sachstichworte[] = utf8_encode($db->Record['stichwort']);
        } else {
            $abschluesse[] = utf8_encode($db->Record['stichwort']);
        }
    }
    
    return array("Sachstichwort" => $sachstichworte, "Abschluss" => $abschluesse);
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

    $sql = "SELECT kurse.id, kurse.titel, kurse.beschreibung, themen.thema 
        FROM kurse
        LEFT JOIN themen
            ON themen.id = kurse.thema 
        WHERE kurse.id = $courseid";

    $db->query($sql);

    if ($db->next_record()) { 
        $course = $db->Record;
        $course['tags'] = get_course_tags($course['id']);
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
                <script src='./wisyki-manual-classifier.js'></script>
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

header('Content-Type: text/html; charset=UTF-8');

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

        $skills = $_POST['skills'];

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
        $prediction = $pythonAPI->predict_comp_level(utf8_encode($course['titel']), utf8_encode($course['beschreibung']));
        $course['level_suggestion'] = $prediction['level'];

        pagestart('Manuelle Kursklassifikation');

        ?>

        <main class="manual-classification_selection">
            <h2>Bestimme ein passendes Kompetenzniveau für den folgenden zufällig ausgewählten Kurs.</h2>
            <section class="course-info">
                <label for="course-title">Titel</label>
                <p id="course-title"><?php echo utf8_encode($course['titel']) ?></p>
                <label for="course-thema">Thema</label>
                <p id="course-thema"><?php echo utf8_encode($course['thema']) ?></p>
                <label for="course-id">ID</label>
                <p id="course-id"><?php echo $course['id'] ?></p>
                <label for="course-description">Beschreibung</label>
                <div id="course-description"><?php echo utf8_encode($course['beschreibung']) ?></div>
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
                    <input type="hidden" name="skills" value="">
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
             
            <br> 
            <br> 
            <section class="skill-suggestions"> 
                <h2>ESCO-Skill Empfehlungen:</h4>
                <ul class="skill-cloud">
                    <?php
                    // if (!empty($skillSuggestions['result'])) { 
                    //     foreach ($skillSuggestions['result'] as $url => $suggestion) { 
                    //         if(!empty($suggestion)) {
                    //             echo("<li><a href='$url' target='_blank' rel='noopener noreferrer' class='btn'>" . $suggestion['label'] . "</a></li>"); 
                    //         }
                    //     } 
                    // }
                    if (!empty($course['tags'])) {
                        foreach ($course['tags']['Sachstichwort'] as $sachstichwort) { 
                            echo('<li><button class="btn tag_sachstichwort">' . $sachstichwort . '</button></li>');
                        }  
                        foreach ($course['tags']['Abschluss'] as $abschluss) { 
                            echo('<li><button class="btn tag_abschluss">' . $abschluss . '</button></li>');
                        } 
                    } 
                    ?> 
                </ul> 
                <details>
                    <summary>Suchworte</summary>
                    <pre class="skill-cloud-searchterm"></pre>
                </details>
                <div class="autocomplete-box">
                    <div class="autocomplete-box__input">
					    <i class="icon search-icon"></i>
                        <input type="text" placeholder="Kompetenzen finden" name="esco-skill-select" id="esco-skill-select" class="esco-autocomplete" esco-scheme="member-skills,skills-hierarchy" onlyrelevant=False>
                        <button class="clear-input" title="Clear input"><i class="icon close-icon"></i></button>
                    </div>
                    <output name="esco-skill-select" for="esco-skill-select"></output>
                </div>
                <ul class="selectable-skills"></ul>
            </section>
        </main>

        <?php
    }
    pageend();
}