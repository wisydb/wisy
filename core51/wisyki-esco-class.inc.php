<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/core51/wisyki-python-class.inc.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/core51/wisyki-json-response.inc.php');

/**
 * Class WISYKI_ESCO_CLASS 
 * 
 * This class provides functionality for working with the ESCO taxonomy.
 * 
 * The "Weiterbildungsscout" was created by the project consortium "WISY@KI" as part of the Innovationswettbewerb INVITE 
 * and was funded by the Bundesinstitut für Berufsbildung and the Federal Ministry of Education and Research.
 * 
 * @copyright   2023 ISy TH Lübeck <dev.ild@th-luebeck.de>
 * @author		Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class WISYKI_ESCO_CLASS {
    /**
     * The framework of the wisy frontend. Provides basic functionalities to navigate the system. 
     *
     * @var WISY_FRAMEWORK_CLASS
     */
    private WISY_FRAMEWORK_CLASS $framework;

    /**
     * Class that handles requests to the PYTHON-AI-API.
     *
     * @var WISYKI_PYTHON_CLASS
     */
    private WISYKI_PYTHON_CLASS $pythonAPI;

    /**
     * The second part of the requested URI path.
     * For the URI <host>/esco/autocomplete $request would be "autocomplete".
     * This parameter is used to determine which service the client is requesting.
     *
     * @var string
     */
    private string $request = '';

    /**
     * Constructor.
     *
     * @param WISY_FRAMEWORK_CLASS $framework
     * @param string|null $request
     */
    function __construct(WISY_FRAMEWORK_CLASS &$framework = null, string|null $request) {
        // constructor
        $this->framework = &$framework;
        $this->pythonAPI = new WISYKI_PYTHON_CLASS();
        if (isset($request)) {
            $this->request = $request;
        }
    }

    /**
     * Calls ESCO API to get suggestions based on the given term for skill concepts and 
     * enhances the search by including the titles of more specific skills into the search.
     *
     * @param string $term
     * @param int|null $limit
     * @return array [{"label":"title1","value":"url1"},{"label":"title2","value":"url2"}]
     */
    function search_skills_hierarchy($term, $limit = 5) {

        // Call search API to get initial results.
        $concepts = $this->search_api($term, 'concept', 'skills-hierarchy', $limit);

        // If the number of results equals the limit or no limit has been set, return the initial results.
        if (isset($limit) && count($concepts) >= $limit) {
            return $concepts;
        }

        // Build search request for specific skills.
        $url = "https://ec.europa.eu/esco/api/search";
        $dataArray = array(
            'text' => $term,
            'language' => 'de',
            'type' => 'skill',
            'isInScheme' => 'http://data.europa.eu/esco/concept-scheme/member-skills',
            'full' => 'false',
            'alt' => 'true'
        );

        $data = http_build_query($dataArray);
        $getUrl = $url . "?" . $data;

        // Make request to ESCO API.
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $getUrl,
            CURLOPT_TIMEOUT => 80,
        ]);

        $response = curl_exec($curl);

        if (curl_error($curl)) {
            JSONResponse::error500('Request Error:' . curl_error($curl));
        }

        curl_close($curl);

        // Filter results for title and uri attributes.
        $response = json_decode($response, true);
        $results = $response['_embedded']['results'];

        // Store the broaderHierarchyConcept of the found skills in the concepts array.
        foreach ($results as $result) {
            $broaderConcept = $result["broaderHierarchyConcept"][0];

            if (!array_key_exists($broaderConcept, $concepts)) {
                $skill = $this->getSkillDetails($broaderConcept);

                $concepts[$broaderConcept] = [
                    "label" => $skill["title"],
                    "count" => 1,
                ];
            } else {
                if (array_key_exists("count", $concepts[$broaderConcept])) {
                    $concepts[$broaderConcept]["count"] += 1;
                } else {
                    $concepts[$broaderConcept]["count"] = 1;
                }
            }


            // If the number of results equals the limit, return the current concepts.
            if (isset($limit) && count($concepts) >= $limit) {
                break;
            }
        }

        // Sort the concepts by the number of times they appear in the results.
        usort($concepts, function ($a, $b) {
            $a_val = (int) $a['count'];
            $b_val = (int) $b['count'];

            if ($a_val > $b_val) {
                return -1;
            }

            if ($a_val < $b_val) {
                return 1;
            }

            return 0;
        });

        return $concepts;
    }

    /**
     * Calls the ESCO API to get the details of a skill.
     *
     * @param string $uri
     * @return array
     */
    function getSkillDetails(string $uri): array {
        $url = 'https://ec.europa.eu/esco/api/resource/skill';
        $dataArray = array(
            'uri' => $uri,
            'language' => 'de',
        );

        $data = http_build_query($dataArray);
        $getUrl = $url . "?" . $data;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_URL, $getUrl);
        curl_setopt($curl, CURLOPT_TIMEOUT, 80);

        $response = curl_exec($curl);

        if (curl_error($curl)) {
            JSONResponse::error500('Request Error:' . curl_error($curl));
        }

        curl_close($curl);

        return json_decode($response, true);
    }

    /**
     * Calls ESCO API to get suggestions for ESCO vocabulary based on the given term.
     *
     * @param string $term
     * @param string $type
     * @param string $scheme
     * @param int $limit
     * @return array [{"url1": {"label": "title1"}, {"url2": {"label": "title2"},]
     */
    function search_api($term, $type = null, $scheme = null, $limit = 5) {
        $escoSuggestions =  array();
        $available_types = ['occupation', 'skill', 'concept'];
        $available_esco_schemes = [
            'skills-hierarchy' => 'http://data.europa.eu/esco/concept-scheme/skills-hierarchy',
            'member-skills' => 'http://data.europa.eu/esco/concept-scheme/member-skills',
            'member-occupations' => 'http://data.europa.eu/esco/concept-scheme/member-occupations',
            'isco' => 'http://data.europa.eu/esco/concept-scheme/isco',
        ];

        // Build request url.
        $url = "https://ec.europa.eu/esco/api/search";
        $dataArray = array(
            'text' => $term,
            'language' => 'de',
            'full' => 'false',
            'alt' => 'true',
            'limit' => $limit,
        );

        if (isset($type) && !empty($type)) {
            if (!in_array($type, $available_types)) {
                JSONResponse::error401($type . ' is not a valid type.');
            }
            $dataArray['type'] = $type;
        }

        if (isset($scheme) && !empty($scheme)) {
            if (!array_key_exists($scheme, $available_esco_schemes)) {
                JSONResponse::error401($scheme . ' is not a valid scheme.');
            }
            $dataArray['isInScheme'] = $available_esco_schemes[$scheme];
        }

        $data = http_build_query($dataArray);
        $getUrl = $url . "?" . $data;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_URL, $getUrl);
        curl_setopt($curl, CURLOPT_TIMEOUT, 80);

        $response = curl_exec($curl);

        if (curl_error($curl)) {
            JSONResponse::error500('Request Error:' . curl_error($curl));
        }

        curl_close($curl);

        // Decode response and filter results for title and uri attributes.
        $response = json_decode($response, true);
        $results = $response['_embedded']['results'];

        foreach ($results as $result) {
            if ($result['className'] == 'Concept') {
                if (!preg_match("/.*esco\/(skill\/S\d\.\d\.\d)|(isced-f\/\d{4})/", $result["uri"])) {
                    continue;
                }
            }

            $escoSuggestions[$result["uri"]] = [
                "label" => $result["title"]
            ];
        }

        return $escoSuggestions;
    }

    /**
     * Calls ESCO API to get the essential skills of an occupation based on the given occupation uri.
     *
     * @param string $uri
     * @param int $limit
     * @return array [{"label":"title1","value":"url1"},{"label":"title2","value":"url2"}]
     */
    function getSkillsOf($uri, $onlyrelevant = false) {
        $escoSuggestions =  array();

        // Build request url.
        if (strpos($uri, "occupation") !== false) {
            $url = "https://ec.europa.eu/esco/api/resource/occupation";
        } else {
            $url = "https://ec.europa.eu/esco/api/resource/concept";
        }
        $dataArray = array(
            'uri' => $uri,
            'language' => 'de'
        );

        $data = http_build_query($dataArray);
        $getUrl = $url . "?" . $data;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_URL, $getUrl);
        curl_setopt($curl, CURLOPT_TIMEOUT, 80);

        $response = curl_exec($curl);

        if (curl_error($curl)) {
            JSONResponse::error500('Request Error:' . curl_error($curl));
        }

        curl_close($curl);

        // Decode response and filter results for title and uri attributes.
        $response = json_decode($response, true);
        if (array_key_exists('hasEssentialSkill', $response['_links'])) {
            $skills = $response['_links']['hasEssentialSkill'];
        } else if (array_key_exists('narrowerSkill', $response['_links'])) {
            $skills = $response['_links']['narrowerSkill'];
        } else {
            return [];
        }

        // Build array in a format that is understood by the function consumer.
        foreach ($skills as $skill) {
            $escoSuggestions[$skill["uri"]] = [
                "label" => $skill["title"],
                "href" => $skill["href"],
            ];
        }

        if ($onlyrelevant) {
            $escoSuggestions = $this->filter_is_relevant($escoSuggestions);
        }

        return array(
            "uri" => $response["uri"],
            "title" => $response["title"],
            "skills" => $escoSuggestions,
        );
    }

    /**
     * Calls ESCO API to get suggestions for ESCO vocabulary based on the given term.
     *
     * @param string $term
     * @param string $type
     * @param string $scheme
     * @param int $limit
     * @return array [{"label":"title1","value":"url1"},{"label":"title2","value":"url2"}]
     */
    function search_wisy($term, $type = null, $scheme = null, $limit = 5) {
        $url = $_SERVER['HTTP_HOST'] . '/autosuggest';

        $dataArray = array(
            'q' => $term,
            'limit' => $limit,
            'timestamp' => time(),
        );

        $data = http_build_query($dataArray);
        $getUrl = $url . "?" . $data;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_URL, $getUrl);
        curl_setopt($curl, CURLOPT_TIMEOUT, 80);

        $response = curl_exec($curl);

        if (curl_error($curl)) {
            JSONResponse::error500('Request Error:' . curl_error($curl));
        }

        curl_close($curl);

        $search_results = array();

        $tags = explode('|', $response);
        for ($i = 0; $i < count($tags); $i++) {
            if ($i % 4 != 0) {
                continue;
            }
            $tag = preg_replace('/\d+\s*/', '', $tags[$i]);
            if (empty($tag) || str_contains($tag, 'volltext:')) {
                continue;
            }
            $search_results[] = [
                "label" =>  utf8_encode($tag),
            ];
        }

        return $search_results;
    }

    /**
     * Filters an array of esco skills to only contain skills for which there is a tag with the same name presenet in the wisy database.
     * It is irrelevant whether the tag in the database is of type ESCO_Kompetenz or any other type.
     *
     * @param array $skills A two-dimensional array of esco skills ordered by a category. [['category1] => ['skill1', 'skill2'], ['category2] => ['skill3']]
     * @return array Array containing skills that have equivalent tags in the wisy databse.
     */
    function filter_is_relevant(array $skills): array {
        $db = new DB_Admin();
        $filtered = array();
        foreach ($skills as $index => $skill) {
            $sql = 'SELECT * FROM x_tags WHERE tag_name = "' . $skill['label'] . '"';
            $db->query($sql);
            if ($db->next_record()) {
                $filtered[$index] = $skill;
            }
        }
        return $filtered;
    }

    /**
     * Uses the ESCO API to generate autocomplete results for known ESCO concepts, skills and occupations based on a given term.
     *
     * @param string $term The term based on which autocomplete reults are searched.
     * @param string|null $type The type of results that are searched. By default every type is searched.
     * @param array|null $scheme The ESCO Scheme that is to be searched. By default every scheme is searched.
     * @param int|null $limit The max number of results.
     * @param boolean|null $onlyrelevant Whether only relevant results should be returned. A relevant result is one that has an equivalent tag with the same name in the WISY database.
     * @return array A two dimensional array of ESCO results ordered by category, which can be either the type or scheme of the result. [['category1] => ['skill1', 'skill2'], ['category2] => ['skill3']]
     */
    function autocomplete($term, $type = null, $scheme = null, $limit = null, $onlyrelevant = true): array {
        $results = [];

        if (isset($scheme) && !empty($scheme)) {
            if (count($scheme) > 1) {
                foreach ($scheme as $s) {
                    $s = trim($s);
                    if ($s == 'extended-skills-hierarchy') {
                        $results['skills-hierarchy'] = $this->search_skills_hierarchy($term, $limit);
                    } else if ($s == 'sachstichwort') {
                        $results['sachstichwort'] = $this->search_wisy($term, $type, $s, $limit);
                    } else {
                        $results[$s] = $this->search_api($term, $type, $s, $limit);
                    }
                }
            } else {
                if ($scheme[0] == 'extended-skills-hierarchy') {
                    $results['skills-hierarchy'] = $this->search_skills_hierarchy($term, $limit);
                } else if ($scheme[0] == 'sachstichwort') {
                    $results['sachstichwort'] = $this->search_wisy($term, $type, $scheme[0], $limit);
                } else {
                    $results[$scheme[0]] = $this->search_api($term, $type, $scheme[0], $limit);
                }
            }
        } else {
            $results[$type] = $this->search_api($term, $type, $scheme, $limit);
        }

        if (is_array($scheme) && !in_array('extended-skills-hierarchy', $scheme) && array_key_exists('skills-hierarchy', $results) && empty($results['skills-hierarchy'])) {
            $results['skills-hierarchy'] = $this->search_skills_hierarchy($term, $limit);
        }



        if ($onlyrelevant) {
            foreach ($results as $categoryname => $category) {
                $results[$categoryname] = $this->filter_is_relevant($category);
            }
        }

        return $results;
    }

    /**
     * Suggests skills based on the given title and description by extracting keywords from the text and searching
     * the ESCO API for skills that match those keywords.
     *
     * @param string $title The title to extract keywords from.
     * @param string $description The description to extract keywords from.
     * @return array An array containing the search terms used and the resulting skill suggestions.
     */
    function suggestSkills(string $title, string $description): array {
        // Extract keywords from the title and description.
        $keywords = $this->pythonAPI->extract_keywords($title . ' \n\n ' . $description);

        // Build search terms string from extracted keywords and title.
        $searchterms = "";
        foreach ($keywords as $keyword) {
            $searchterms .= $keyword[0] . ", ";
        }
        $searchterms .= $title;

        // Search ESCO API for skills that match the search terms.
        $skillSuggestions = $this->search_api($searchterms, 'skill', null, 5);

        // Return the search terms and resulting skill suggestions.
        $result = array(
            'searchterms' => $searchterms,
            'result' => $skillSuggestions,
        );
        return $result;
    }

    /**
     * Determines whether the ESCO skill identified by the given URI is a language skill.
     *
     * @param string $uri The URI to check.
     * @return bool Returns true if the skill is a language skill, false otherwise.
     */
    function is_language_skill(string $uri): bool {
        // Set up the API request.
        $url = "https://ec.europa.eu/esco/api/resource/skill";
        $dataArray = array(
            'uri' => $uri,
            'language' => 'de'
        );

        $data = http_build_query($dataArray);
        $getUrl = $url . "?" . $data;

        // Make the API request.
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_URL, $getUrl);
        curl_setopt($curl, CURLOPT_TIMEOUT, 80);

        $response = curl_exec($curl);

        // Check for errors and return an error response if there is one.
        if (curl_error($curl)) {
            JSONResponse::error500('Request Error:' . curl_error($curl));
        }

        curl_close($curl);

        // Decode the API response and check for language skill.
        $response = json_decode($response, true);
        foreach ($response['_links']['isInScheme'] as $scheme) {
            if ($scheme['uri'] === "http://data.europa.eu/esco/concept-scheme/skill-language-groups") {
                return true;
            }
        }

        return false;
    }

    /**
     * Renders the results of an autocomplete search based on the specified GET parameters as a JSON response.
     * 
     * @var string $_GET['term']   Required. The search term to autocomplete on.
     * @var string $_GET['type']   Optional. The type of concept to search for.
     * @var string $_GET['scheme'] Optional. Comma-separated list of concept schemes to restrict the search to.
     * @var int    $_GET['limit']  Optional. The maximum number of results to return.
     * @var bool   $_GET['onlyrelevant'] Optional. Whether to only include concepts relevant to the wisy database.
     * 
     * @return void
     */
    function render_autocomplete() {
        header('Content-Type: application/json; charset=UTF-8');
        $term = $_GET['term'];
        if (!isset($term) || empty($term)) {
            JSONResponse::error400('The request is missing the term parameter.');
        }

        $type = null;
        if (isset($_GET['type']) && !empty($_GET['type'])) {
            $type = $_GET['type'];
        }

        $scheme = null;
        if (isset($_GET['scheme']) && !empty($_GET['scheme'])) {
            $scheme = explode(',', $_GET['scheme']);
            if (!is_array($scheme)) {
                $scheme = array($scheme);
            }
        }

        $limit = null;
        if (isset($_GET['limit']) && !empty($_GET['limit'])) {
            $limit = $_GET['limit'];
        }

        $onlyrelevant = true;
        if (isset($_GET['onlyrelevant']) && !empty($_GET['onlyrelevant'])) {
            $onlyrelevant = strtolower($_GET['onlyrelevant']) === 'true' ? true : (strtolower($_GET['onlyrelevant']) === 'false' ? false : JSONResponse::error401($_GET['onlyrelevant'] . ' is not a valid value for onlyrelevant.'));
        }

        JSONResponse::send_json_response($this->autocomplete($term, $type, $scheme, $limit, $onlyrelevant));
    }

    /**
     * Renders the concept skills as a JSON response.
     * 
     * @var string $_GET['uri'] The URI of the concept for which the skills are requested.
     * @var bool $_GET['onlyrelevant'] Whether to return only relevant skills. Optional, defaults to true.
     * 
     * @return void
     */
    function render_skills_of_concept() {
        header('Content-Type: application/json; charset=UTF-8');

        // Check if the uri parameter is present and not empty.
        $uri = $_GET['uri'];
        if (!isset($uri) || empty($uri)) {
            JSONResponse::error400('The request is missing the uri parameter.');
        }

        // Validate the URI.
        $parsed = parse_url($uri);
        if (!isset($parsed['scheme'])) {
            JSONResponse::error401($uri . ' is not a valid uri. Please specifiy a valid http protocol.');
        }
        if (!isset($parsed['host'])) {
            JSONResponse::error401($uri . ' is not a valid uri. Please specifiy a host.');
        }
        if ($parsed['host'] !== 'data.europa.eu') {
            JSONResponse::error401('Unkown host. Only the host data.europa.eu is allowed here.');
        }
        if (!isset($parsed['path']) || empty(trim($parsed['path'], '/'))) {
            JSONResponse::error400($uri . ' is not valid. Please specifiy a ressource.');
        }

        // Check if the onlyrelevant parameter is present and set to true if empty.
        $onlyrelevant = true;
        if (isset($_GET['onlyrelevant']) && !empty($_GET['onlyrelevant'])) {
            $onlyrelevant = strtolower($_GET['onlyrelevant']) === 'true' ? true : (strtolower($_GET['onlyrelevant']) === 'false' ? false : JSONResponse::error401($_GET['onlyrelevant'] . ' is not a valid value for onlyrelevant.'));
        }

        // Get the skills for the specified concept and send the JSON response.
        JSONResponse::send_json_response($this->getSkillsOf($uri, $onlyrelevant));
    }

    /**
     * Renders skill suggestions as a JSON response based on a title and a description provided by a POST Request.
     *
     * @return void
     */
    function render_suggest_skills() {
        header('Content-Type: application/json; charset=UTF-8');
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (empty($data)) {
            JSONResponse::error400('No valid json provided.');
        }
        if (!isset($data['title'])) {
            JSONResponse::error400('The provided json is missing a title.');
        }
        if (!isset($data['description'])) {
            JSONResponse::error400('The provided json is missing a description.');
        }
        JSONResponse::send_json_response($this->suggestSkills($data['title'], $data['description']));
    }

    /**
     * Sends a JSON response for the request to identify wether a given skill identified by its uri is a language skill.
     *
     * @return void
     */
    function render_is_language_skill() {
        header('Content-Type: application/json; charset=UTF-8');

        if (!isset($_GET['uri'])) {
            JSONResponse::error400('The request is missing the uri parameter.');
        }
        JSONResponse::send_json_response($this->is_language_skill($_GET['uri']));
    }

    /**
     * Renders the appropriate response based on the value of the request parameter.
     *
     * @return void
     */
    function render() {
        // Evaluate the value of the request parameter using a switch statement.
        switch ($this->request) {
            case 'getConceptSkills':
                $this->render_skills_of_concept();
                break;
            case 'suggestSkills':
                $this->render_suggest_skills();
                break;
            case 'autocomplete':
                $this->render_autocomplete();
                break;
            case 'isLanguageSkill':
                $this->render_is_language_skill();
                break;
            default:
                // If the request parameter doesn't match any of the above cases, send a 404 Not Found HTTP response.
                JSONResponse::error404();
        }
    }
}
