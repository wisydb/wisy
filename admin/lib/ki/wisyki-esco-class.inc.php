<?php

require_once("./sql_curr.inc.php");
require_once("./config/config.inc.php");

class WISYKI_ESCO_CLASS {
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
        $url = "https://ec.europa.eu/esco/api/suggest2";
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
                ];

                if ($limit && count($concepts) >= $limit) {
                    return $concepts;
                }
            }
        }

        return $concepts;
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
        $db = new DB_Admin();

        $search_results = array();

		$available_wisy_schemes = [
            'sachstichwort' => '0',
        ];

        $sql = 'SELECT id, stichwort FROM stichwoerter WHERE stichwort LIKE "%'. $term .'%"';

		if (isset($scheme) && !empty($scheme)) {
            if (array_key_exists($scheme, $available_wisy_schemes)) {
                $sql .= ' AND eigenschaften = ' .$available_wisy_schemes[$scheme];
            }
		}

        if (isset($limit)) {
            $sql .= ' LIMIT ' . $limit;
        }

        $db->query($sql);
        while ($db->next_record()) {
            $search_results[$db->Record['id']] = [
                "label" =>  utf8_encode($db->Record['stichwort'])
            ];
        }

        return $search_results;
    }
}