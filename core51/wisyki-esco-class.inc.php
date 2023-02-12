<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/core51/wisyki-python-class.inc.php');

class WISYKI_ESCO_CLASS {
	var $framework;
	var $pythonAPI;
	var $request;

	/**************************************************************************
	 * Tools / Misc.
	 *************************************************************************/
	
	function __construct(&$framework = null, $request = '')
	{
		// constructor
		$this->framework	=& $framework;
        $this->pythonAPI = new WISYKI_PYTHON_CLASS();
        $this->request = $request;
	}
    /**
     * Calls ESCO API to get autocomplete suggestions based on the given term
     * for skill concepts by including the titles of specific skills into the search.
     *
     * @param string $term
     * @param int $limit
     * @return array [{"label":"title1","value":"url1"},{"label":"title2","value":"url2"}]
     */
    function search_skills_hierarchy($term, $limit = 5) {

        $concepts = $this->search_api($term, 'concept', 'skills-hierarchy', $limit);

        if ($limit && count($concepts) >= $limit) {
            return $concepts;
        }

        // Build request url.
        $url = "https://ec.europa.eu/esco/api/search";
        $dataArray = array(
            'text' => $term,
            'language' => 'de',
            'type' => 'skill',
            'isInScheme' => 'http://data.europa.eu/esco/concept-scheme/member-skills',
            'full' => 'false', // Set True for full object and False for faster requests. For 'search' endpoint.
            'alt' => 'true' // For 'suggest2' endpoint.
        );

        $data = http_build_query($dataArray);
        $getUrl = $url."?".$data;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_URL, $getUrl);
        curl_setopt($curl, CURLOPT_TIMEOUT, 80);

        $response = curl_exec($curl);

        if (curl_error($curl)){
            echo 'Request Error:' . curl_error($curl);
            return;
        }

        curl_close($curl);

        // Decode response and filter results for title and uri attributes.
        $response = json_decode($response, true);
        $results = $response['_embedded']['results'];

        foreach ($results as $result) {
            if (!array_key_exists($result["broaderHierarchyConcept"][0], $concepts)) {
                $url = 'https://ec.europa.eu/esco/api/resource/skill';
                $dataArray = array(
                    'uri' => $result["broaderHierarchyConcept"][0],
                    'language' => 'de',
                );

                $data = http_build_query($dataArray);
                $getUrl = $url."?".$data;

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($curl, CURLOPT_URL, $getUrl);
                curl_setopt($curl, CURLOPT_TIMEOUT, 80);

                $response = curl_exec($curl);

                if (curl_error($curl)){
                    echo 'Request Error:' . curl_error($curl);
                    return;
                }

                curl_close($curl);

                $skill = json_decode($response, true);

                $concepts[$result["broaderHierarchyConcept"][0]] = [
                    "label" => $skill["title"],
                    "count" => 1,
                ];

                if ($limit && count($concepts) >= $limit) {
                    return $concepts;
                }
            } else {
                if (array_key_exists("count", $concepts[$result["broaderHierarchyConcept"][0]])) {
                    $concepts[$result["broaderHierarchyConcept"][0]]["count"] += 1;
                } else {
                    $concepts[$result["broaderHierarchyConcept"][0]]["count"] = 1;
                }
            }
        }

        usort($concepts, function ($a, $b) {
            $a_val = (int) $a['count'];
            $b_val = (int) $b['count'];
          
            if($a_val > $b_val) return -1;
            if($a_val < $b_val) return 1;
            return 0;
          });

        return $concepts;
    }

    function sort_by_count($concepts) {
        $sorted = array();
        foreach($concepts as $key => $value) {

        }
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
		$available_types = ['occupation','skill','concept'];
		$available_esco_schemes = [
            'skills-hierarchy' => 'http://data.europa.eu/esco/concept-scheme/skills-hierarchy',
            'member-skills' => 'http://data.europa.eu/esco/concept-scheme/member-skills',
            'member-occupations' => 'http://data.europa.eu/esco/concept-scheme/member-occupations',
            'isco' => 'http://data.europa.eu/esco/concept-scheme/isco',
        ];

        // Build request url.
        // $url = "https://ec.europa.eu/esco/api/suggest2";
        $url = "https://ec.europa.eu/esco/api/search";
        $dataArray = array(
            'text' => $term,
            'language' => 'de',
            // 'type' => ['occupation','skill','concept'],
            // 'isInScheme': ['http://data.europa.eu/esco/concept-scheme/skills-hierarchy', 'http://data.europa.eu/esco/concept-scheme/member-skills']
            'full' => 'false', // Set True for full object and False for faster requests. For 'search' endpoint.
            'alt' => 'true', // For 'suggest2' endpoint.
            'limit' => $limit,
        );

		if (isset($type) && !empty($type)) {
            if (!in_array($type, $available_types)) {
                $this->error_401($type . ' is not a valid type.');
            }
            $dataArray['type'] = $type;
		}

		if (isset($scheme) && !empty($scheme)) {
            if (!array_key_exists($scheme, $available_esco_schemes)) {
                $this->error_401($scheme . ' is not a valid scheme.');
            }
            $dataArray['isInScheme'] = $available_esco_schemes[$scheme];
		}

        $data = http_build_query($dataArray);
        $getUrl = $url."?".$data;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_URL, $getUrl);
        curl_setopt($curl, CURLOPT_TIMEOUT, 80);

        $response = curl_exec($curl);

        if (curl_error($curl)){
            echo 'Request Error:' . curl_error($curl);
            $this->error_500();
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
        $getUrl = $url."?".$data;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_URL, $getUrl);
        curl_setopt($curl, CURLOPT_TIMEOUT, 80);

        $response = curl_exec($curl);

        if (curl_error($curl)){
            echo 'Request Error:' . curl_error($curl);
            return;
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
        $getUrl = $url."?".$data;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_URL, $getUrl);
        curl_setopt($curl, CURLOPT_TIMEOUT, 80);

        $response = curl_exec($curl);

        if (curl_error($curl)){
            echo 'Request Error:' . curl_error($curl);
            return;
        }

        curl_close($curl);

        $search_results = array();

        $tags = explode('|', $response);
        for ($i = 0; $i < count($tags); $i++) {
            if ($i % 4 != 0) {
                continue;
            }
            $tag = preg_replace('/\d+\s*/', '', $tags[$i]);
            if(empty($tag) || str_contains($tag, 'volltext:')) {
                continue;
            }
            $search_results[] = [
                "label" =>  utf8_encode($tag),
            ];
        }

        // $db = new DB_Admin();

        // $search_results = array();

		// $available_wisy_schemes = [
        //     'sachstichwort' => '0',
        // ];

        // $sql = 'SELECT id, stichwort FROM stichwoerter WHERE stichwort LIKE "%'. $term .'%"';

		// if (isset($scheme) && !empty($scheme)) {
        //     if (array_key_exists($scheme, $available_wisy_schemes)) {
        //         $sql .= ' AND eigenschaften = ' .$available_wisy_schemes[$scheme];
        //     }
		// }

        // if (isset($limit)) {
        //     $sql .= ' LIMIT ' . $limit;
        // }

        // $db->query($sql);
        // while ($db->next_record()) {
        //     $search_results[$db->Record['id']] = [
        //         "label" =>  utf8_encode($db->Record['stichwort'])
        //     ];
        // }

        return $search_results;
    }

    /**
     * Filters an array of esco skills to only contain skills for which there is a tag with the same name presenet in the wisy database.
     * It is irrelevant whether the tag in the database is of type ESCO_Kompetenz or any other type.
     *
     * @param array $skills A two-dimensional array of esco skills ordered by a category. [['category1] => ['skill1', 'skill2'], ['category2] => ['skill3']]
     * @return array Array containing skills that have equivalent tags in the wisy databse.
     */
    function filter_is_relevant(array $skills):array {
        $db = new DB_Admin();
        $filtered = array();
        foreach ($skills as $index => $skill) {
            $sql = 'SELECT * FROM x_tags WHERE tag_name = "'. $skill['label'] .'"';
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
     * @param string $type The type of results that are searched. By default every type is searched.
     * @param array $scheme The ESCO Scheme that is to be searched. By default every scheme is searched.
     * @param int $limit The max number of results.
     * @param boolean $onlyrelevant Whether only relevant results should be returned. A relevant result is one that has an equivalent tag with the same name in the WISY database.
     * @return array A two dimensional array of ESCO results ordered by category, which can be either the type or scheme of the result. [['category1] => ['skill1', 'skill2'], ['category2] => ['skill3']]
     */
    function autocomplete($term, $type = null, $scheme = null, $limit = null, $onlyrelevant = true): array {
        $results = [];

        if (isset($scheme) && !empty($scheme)) {
            if (count($scheme) > 1) {
                foreach($scheme as $s) {
                    $s = trim($s);
                    if ($s == 'extenden-skills-hierarchy') {
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

        if (is_array($scheme) && !in_array('extenden-skills-hierarchy', $scheme) && array_key_exists('skills-hierarchy', $results) && empty($results['skills-hierarchy'])) {
            $results['skills-hierarchy'] = $this->search_skills_hierarchy($term, $limit);
        }



        if ($onlyrelevant) {
            foreach ($results as $categoryname => $category) {
                $results[$categoryname] = $this->filter_is_relevant($category);
            }
        }

        return $results;
    }

    function suggestSkills(string $title, string $description): array {
        $keywords = $this->pythonAPI->extract_keywords($title . ' \n\n ' . $description);
        $searchterms = ""; 
        foreach ($keywords as $keyword) { 
            $searchterms .= $keyword[0] . ", "; 
        } 
        $searchterms .= $title;
        $skillSuggestions = array();
        // $skillSuggestions = array_merge($skillSuggestions, $escoAPI->search_api("speicherprogrammierbare Steuerung", 'skill', null, 5));
        // $skillSuggestions = array_merge($skillSuggestions, $escoAPI->search_api("Individualarbeitsrecht", 'skill', null, 5));
        // $skillSuggestions = array_merge($skillSuggestions, $this->search_api("englisch sprechen ", 'skill', null, 5));
        // $skillSuggestions = array_merge($skillSuggestions, $escoAPI->search_api("excel tabellenkalulation ", 'skill', null, 5));
        $skillSuggestions = array_merge($skillSuggestions, $this->search_api($searchterms, 'skill', null, 5));

       

        $result = array(
            'searchterms' => $searchterms,
            'result' => $skillSuggestions,
        );

        return $result;
    }

    function render_autocomplete() {
        header('Content-Type: application/json; charset=UTF-8');
        $term = $_GET['term'];
        if (!isset($term) || empty($term)) {
            $this->error_400('The request is missing the term parameter.');
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
            $onlyrelevant = strtolower($_GET['onlyrelevant']) === 'true' ? true : (strtolower($_GET['onlyrelevant']) === 'false' ? false : $this->error_401($_GET['onlyrelevant'] . ' is not a valid value for onlyrelevant.'));
        }
        echo json_encode($this->autocomplete($term, $type, $scheme, $limit, $onlyrelevant));
    }

    function render_concept_skills() {
        header('Content-Type: application/json; charset=UTF-8');
        $uri = $_GET['uri'];
        if (!isset($uri) || empty($uri)) {
            $this->error_400('The request is missing the uri parameter.');
        }
        $parsed = parse_url($uri);
        if (!isset($parsed['scheme'])) {
            $this->error_401($uri . ' is not a valid uri. Please specifiy a valid http protocol.');
        }
        if (!isset($parsed['host'])) {
            $this->error_401($uri . ' is not a valid uri. Please specifiy a host.');
        }
        if ($parsed['host'] !== 'data.europa.eu') {
            $this->error_401('Unkown host. Only the host data.europa.eu is allowed here.');
        }
        if (!isset($parsed['path']) || empty(trim($parsed['path'], '/'))) {
            $this->error_401($uri . ' is not valid. Please specifiy a ressource.');
        }

        $onlyrelevant = true;
        if (isset($_GET['onlyrelevant']) && !empty($_GET['onlyrelevant'])) {
            $onlyrelevant = strtolower($_GET['onlyrelevant']) === 'true' ? true : (strtolower($_GET['onlyrelevant']) === 'false' ? false : $this->error_401($_GET['onlyrelevant'] . ' is not a valid value for onlyrelevant.'));
        }

        echo json_encode($this->getSkillsOf($uri, $onlyrelevant));
    }

    function render_suggest_skills() {
        header('Content-Type: application/json; charset=UTF-8');
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (empty($data)) {
            $this->error_400('No valid json provided.');
        }
        if (!isset($data['title'])) {
            $this->error_400('The provided json is missing a title.');
        }
        if (!isset($data['description'])) {
            $this->error_400('The provided json is missing a description.');
        }
        echo json_encode($this->suggestSkills($data['title'], $data['description']));
    }

    function error_400($message) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(400);
        echo json_encode(array(
            'error_code' => '400',
            'error_message' => $message,
        ));
        die();
    }

    function error_401($message) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(401);
        echo json_encode(array(
            'error_code' => '401',
            'error_message' => $message,
        ));
        die();
    }

    function error_404() {
        http_response_code(404);
        die();
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
		switch( $this->request ) {
            case 'get-concept-skills':
                $this->render_concept_skills();
                break;
            case 'suggest-skills':
                $this->render_suggest_skills();
                break;
            case 'autocomplete':
                $this->render_autocomplete();
                break;
            default:
                $this->error_404();
        }

    }
}