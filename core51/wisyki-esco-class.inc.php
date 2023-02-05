<?php

class WISYKI_ESCO_CLASS {
	var $framework;

	/**************************************************************************
	 * Tools / Misc.
	 *************************************************************************/
	
	function __construct(&$framework)
	{
		// constructor
		$this->framework	=& $framework;
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
            if (in_array($type, $available_types)) {
                $dataArray['type'] = $type;
            }
		}

		if (isset($scheme) && !empty($scheme)) {
            if (array_key_exists($scheme, $available_esco_schemes)) {
                $dataArray['isInScheme'] = $available_esco_schemes[$scheme];
            }
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
            return;
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
    function getSkillsOf($uri) {
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


    function filter_is_relevant($tags) {
        $db = new DB_Admin();
        $filtered = array();
        foreach ($tags as $category => $categorytags) {
            foreach ($categorytags as $id => $tag) {
                $sql = 'SELECT * FROM x_tags WHERE tag_name = "'. $tag['label'] .'"';
                $db->query($sql);
                if ($db->next_record()) {
                    $filtered[$category][$id] = $tag;
                }
            }
        }
        return $filtered;
    }

    function autocomplete() {
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
                        $results['skills-hierarchy'] = $this->search_skills_hierarchy($_GET['term'], $limit);
                    } else if ($s == 'sachstichwort') {
                        $results['sachstichwort'] = $this->search_wisy($_GET['term'], $type, $s, $limit);
                    } else {
                        $results[$s] = $this->search_api($_GET['term'], $type, $s, $limit);
                    }
                }
            } else {
                if ($scheme[0] == 'extended-skills-hierarchy') {
                    $results['skills-hierarchy'] = $this->search_skills_hierarchy($_GET['term'], $limit);
                } else if ($scheme[0] == 'sachstichwort') {
                    $results['sachstichwort'] = $this->search_wisy($_GET['term'], $type, $scheme[0], $limit);
                } else {
                    $results[$scheme[0]] = $this->search_api($_GET['term'], $type, $scheme[0], $limit);
                }
            }
        } else {
            $results[$type] = $this->search_api($_GET['term'], $type, $scheme, $limit);
        }

        if (is_array($scheme) && !in_array('extenden-skills-hierarchy', $scheme) && array_key_exists('skills-hierarchy', $results) && empty($results['skills-hierarchy'])) {
            $results['skills-hierarchy'] = $this->search_skills_hierarchy($_GET['term'], $limit);
        }



        if ($onlyrelevant == 1) {
            $results = $this->filter_is_relevant($results);
        }

        echo json_encode($results, JSON_THROW_ON_ERROR);
    }

    function skillSuggest() {
        $result = $this->getSkillsOf($_GET["uri"]);
        if (is_array($result)) {
            echo json_encode($result);
        } else {
            echo $result;
        }
    }

    function render() {
        header('Content-Type: application/json; charset=UTF-8');
		$action = $_GET['action'];
	

		switch( $action ) {
            case 'skill-suggest':
                $this->skillSuggest();
                break;
            case 'autocomplete':
                $this->autocomplete();
                break;
            default:
                echo json_encode(array('error' => 'missing action parameter'));
        }

    }
}