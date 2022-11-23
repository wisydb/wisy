<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="wisyki-esco-test.css">
    <script src="wisyki-esco-test.js"></script>
    <title>ESCO Test</title>
</head>
<body>
    <main>
        <h1>ESCO Test</h1>
        <div class="tabs">
            <!-- Tab links -->
            <div class="tabmenu">
                <button class="tablink active" value="Testcase1">Tätigkeit</button>
                <button class="tablink" value="Testcase2">Kompetenzkonzept</button>
                <button class="tablink" value="Testcase4">Tätigkeit & Kompetenzkonzept</button>
                <button class="tablink" value="Testcase5">Kompetenzen</button>
                <button class="tablink" value="Testcase6">Relevante Kompetenzen</button>
                <button class="tablink" value="Testcase7">WISY Sachstichworte</button>
                <button class="tablink" value="Testcase8">Relevante Stichworte</button>
                <button class="tablink" value="Testcase9">ESCO Kompetenzen & WISY Sachstichworte</button>
            </div>

            <!-- Tab content -->
            <div id="Testcase1" class="tabcontent active">
                <h2>Für welchen Beruf oder welche Tätigkeit suchst Du eine Weiterbildung?</h2>
                <input type="text" placeholder="koch" name="esco-occupation-select" class="esco-autocomplete" esco-type="occupation" onlyrelevant=False>
                <output name="esco-occupation-autocomplete" for="esco-occupation-select"></output>
            </div>

            <div id="Testcase2" class="tabcontent">
                <h2>Für welches Thema suchst Du eine Weiterbildung?</h2>
                <input type="text" placeholder="koch" name="esco-concept-select-select" class="esco-autocomplete" esco-type="concept" esco-scheme="skills-hierarchy" onlyrelevant=False>
                <output name="esco-concept-autocomplete" for="esco-concept-select"></output>
            </div>

            <div id="Testcase4" class="tabcontent">
                <h2>Für welches Thema suchst Du eine Weiterbildung?</h2>
                <input type="text" placeholder="koch" name="esco-occupationAndConcept-select-select" class="esco-autocomplete" esco-scheme="skills-hierarchy member-occupations" onlyrelevant=False>
                <output name="esco-occupationAndConcept-autocomplete" for="esco-occupationAndConcept-select">
                </output>
            </div>

            <div id="Testcase5" class="tabcontent">
                <h2>Über welche Kompetenzen verfügst Du, was kannst Du gut?</h2>
                <input type="text" placeholder="koch" name="esco-skill-select" class="esco-autocomplete" esco-type="skill" onlyrelevant=False>
                <output name="esco-skill-autocomplete" for="esco-skill-select"></output>
            </div>

            <div id="Testcase6" class="tabcontent">
                <h2>Über welche Kompetenzen verfügst Du, was kannst Du gut?</h2>
                <input type="text" placeholder="koch" name="esco-skill-select" class="esco-autocomplete" esco-type="skill">
                <output name="esco-skill-autocomplete" for="esco-skill-select"></output>
            </div>

            <div id="Testcase7" class="tabcontent">
                <h2>Über welche Kompetenzen verfügst Du, was kannst Du gut?</h2>
                <input type="text" placeholder="koch" name="esco-skill-select" class="esco-autocomplete" esco-type="skill" esco-scheme="sachstichwort" onlyrelevant=False>
                <output name="esco-skill-autocomplete" for="esco-skill-select"></output>
            </div>

            <div id="Testcase8" class="tabcontent">
                <h2>Über welche Kompetenzen verfügst Du, was kannst Du gut?</h2>
                <input type="text" placeholder="koch" name="esco-skill-select" class="esco-autocomplete" esco-type="skill" esco-scheme="sachstichwort">
                <output name="esco-skill-autocomplete" for="esco-skill-select"></output>
            </div>

            <div id="Testcase9" class="tabcontent">
                <h2>Über welche Kompetenzen verfügst Du, was kannst Du gut?</h2>
                <input type="text" placeholder="koch" name="esco-skill-select" class="esco-autocomplete" esco-type="skill" esco-scheme="sachstichwort member-skills skills-hierarchy" max="5" onlyrelevant=False>
                <output name="esco-skill-autocomplete" for="esco-skill-select"></output>
            </div>
        </div>


        <div class="tabs">
            <!-- Tab links -->
            <div class="tabmenu">
                <button class="tablink active" value="tab-skill-suggestions">Kompetenzvorschläge</button>
            </div>

            <!-- Tab content -->
            <div id="tab-skill-suggestions" class="tabcontent active">
                <input type="text" placeholder="Kompetenz-URI" size="80" name="esco-uri-input" class="esco-skill-suggest">
                <label for="esco-uri-input">Bezeichnung</label>
                <output name="skill-suggestions-output" for="esco-skill-select"></output>
            </div>

        </div>
    </main>
</body>
</html>