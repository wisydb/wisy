<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/core51/wisy-ki-python-class.inc.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/core51/wisy-ki-json-response.inc.php');

/**
 * Class WISY_KI_ESCO_CLASS 
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
class WISY_KI_ESCO_CLASS {
    /**
     * The framework of the wisy frontend. Provides basic functionalities to navigate the system. 
     *
     * @var WISY_FRAMEWORK_CLASS|null
     */
    private WISY_FRAMEWORK_CLASS|null $framework;

    /**
     * Class that handles requests to the PYTHON-AI-API.
     *
     * @var WISY_KI_PYTHON_CLASS
     */
    private WISY_KI_PYTHON_CLASS $pythonAPI;

    /**
     * The second part of the requested URI path.
     * For the URI <host>/esco/autocomplete $request would be "autocomplete".
     * This parameter is used to determine which service the client is requesting.
     *
     * @var string
     */
    private string $request;

    /**
     * Constructor.
     *
     * @param WISY_FRAMEWORK_CLASS $framework
     * @param string|null $request
     */
    function __construct(WISY_FRAMEWORK_CLASS &$framework = null, string $request = '') {
        // constructor
        $this->framework = &$framework;
        $this->pythonAPI = new WISY_KI_PYTHON_CLASS();
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
            if (!isset($broaderConcept)) {
                continue;
            }

            if (!array_key_exists($broaderConcept, $concepts)) {
                $skill = $this->getSkillDetails($broaderConcept);

                $concepts[$broaderConcept] = [
                    "label" => $skill["title"],
                    "uri" => $broaderConcept,
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
        // usort($concepts, function ($a, $b) {
        //     $a_val = (int) $a['count'];
        //     $b_val = (int) $b['count'];

        //     if ($a_val > $b_val) {
        //         return -1;
        //     }

        //     if ($a_val < $b_val) {
        //         return 1;
        //     }

        //     return 0;
        // });

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
     * @param string|null $type
     * @param string|null $scheme
     * @param int $limit
     * @param array|null $filterconcepts If set, only skills or occupations that are part of the given concepts will be searched.
     * @return array [{"url1": {"label": "title1"}, {"url2": {"label": "title2"},]
     */
    function search_api($term, $type = null, $scheme = null, $limit = 0, $filterconcepts = null) {
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
        // $url = "https://ec.europa.eu/esco/api/suggest2";
        $dataArray = array(
            'text' => $term,
            'language' => 'de',
            'full' => 'false',
            'alt' => 'true',
            'limit' => $limit > 0 ? $limit : null,
        );

        if (isset($type) && !empty($type)) {
            if (!in_array($type, $available_types)) {
                JSONResponse::error401($type . ' is not a valid type.');
            }
            $dataArray['type'] = $type;
        }

        if (isset($scheme) && !empty($scheme)) {
            $schemes = explode(',', $scheme);
            $esco_schemes = array();
            foreach ($schemes as $s) {
                $s = trim($s);
                if (!array_key_exists($s, $available_esco_schemes)) {
                    JSONResponse::error401($scheme . ' is not a valid scheme.');
                }
                $esco_schemes[] = $available_esco_schemes[$s];
            }
            $dataArray['isInScheme'] = join(', ', $esco_schemes);
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

            if (isset($filterconcepts) && !empty($filterconcepts)) {
                if ($result['className'] == 'Concept' || empty($result["broaderHierarchyConcept"])) {
                    continue;
                }

                $ispartofconcept = false;
                foreach ($filterconcepts as $filterconcept) {
                    if (in_array($filterconcept, $result["broaderHierarchyConcept"])) {
                        $ispartofconcept = true;
                        break;
                    }
                }

                if (!$ispartofconcept) {
                    continue;
                }
            }

            $escoSuggestions[$result["uri"]] = [
                "label" => $result["title"],
                "uri" => $result["uri"],
                // "isInScheme"=> $dataArray['isInScheme'],
                // "broaderHierarchyConcept" => $result["broaderHierarchyConcept"]
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
            if (array_key_exists('hasOptionalSkill', $response['_links'])) {
                $skills = array_merge($skills, $response['_links']['hasOptionalSkill']);
            }
        } else if (array_key_exists('narrowerSkill', $response['_links'])) {
            $skills = $response['_links']['narrowerSkill'];
        } else {
            return [];
        }

        // Build array in a format that is understood by the function consumer.
        foreach ($skills as $skill) {
            $escoSuggestions[$skill["uri"]] = [
                "label" => $skill["title"],
                "uri" => $skill["uri"],
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
    function search_wisy($term, $limit = 5, $sheme = "")
    {
        $tags = array();
        if ($sheme == "sachstichwort_red") {
            require_once($_SERVER['DOCUMENT_ROOT'] . '/admin/WisyKi/lib/search/wisy_search.inc.php');
            $tags = suggestTags(utf8_decode($term), array("max" => $limit, 'q_tag_type_not' => array(1, 65536, 4, 8, 32768, 16, 32, 64, 128, 512, 1024, 2048, 4096, 8192, 16384, 65, 256)));
        } else {
            $tagsuggestor = &createWisyObject('WISY_TAGSUGGESTOR_CLASS', $this->framework);
            $tags = $tagsuggestor->suggestTags(utf8_decode($term), array("max" => $limit, 'q_tag_type_not' => array(1, 65536, 4, 8, 32768, 16, 32, 64, 128, 512, 1024, 2048, 4096, 8192, 16384, 65, 256)));
        }
        $search_results = array();

        foreach ($tags as $tag) {
            if ($tag["tag_freq"])
            $search_results[] = [
                "label" =>  utf8_encode($tag["tag"]),
                "uri" => utf8_encode($tag["tag"]),
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
		global $wisyPortalId;
        $db = new DB_Admin();
        $filtered = array();
        foreach ($skills as $index => $skill) {
            $label = utf8_decode($skill['label']);
            $stichwortlabel = preg_replace('/ *\(.*\)/', '', $label);
            $labels = array($label);
            if ($stichwortlabel != $label) {
                $labels[] = $stichwortlabel;
            }
            $labels = join("', '", $labels);
            $sql = "SELECT * 
                    FROM x_tags 
                    LEFT JOIN x_scout_tags_freq freq 
                        ON freq.tag_id = x_tags.tag_id 
                    WHERE x_tags.tag_name IN ('$labels')
                    AND freq.portal_id = $wisyPortalId";
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
     * @param array|null $filterconcepts If set, only skills or occupations that are part of the given concepts will be searched.
     * @return array A two dimensional array of ESCO results ordered by category, which can be either the type or scheme of the result. [['category1] => ['skill1', 'skill2'], ['category2] => ['skill3']]
     */
    function autocomplete($term, $type = null, $schemes = null, $limit = null, $onlyrelevant = true, $filterconcepts = null): array {
        $results = [];

        if (isset($schemes) && !empty($schemes)) {
            $minlimit = round($limit / count($schemes), 0, PHP_ROUND_HALF_UP);
            $counter = 0;
            $res = 0;
            foreach (array_reverse($schemes) as $scheme) {
                $counter++;
                    $schemelimit = $counter * $minlimit - $res;
                if ($scheme == 'extended-skills-hierarchy') {
                    $results = array_merge($results, $this->search_skills_hierarchy($term, $schemelimit));
                } else if ($scheme == 'sachstichwort' || $scheme == 'sachstichwort_red') {
                    //additional scheme to mark search from Redaktionsmaske (Karl Weber)
               $sachstichworte = $this->search_wisy($term, $schemelimit, $scheme);
                   $res = count($sachstichworte);
                } else {
                    $results = array_merge($results, $this->search_api($term, $type, $scheme, $schemelimit, $filterconcepts));
                    $res = count($results);
                }
            }
        } else {
            $results = array_merge($results, $this->search_api($term, $type, $schemes, $limit, $filterconcepts));
        }

        // If there are esco skills and stichworte with the same label, skip the stichwort.
        if (!empty($sachstichworte)) {
            foreach ($sachstichworte as $stichwort) {
                // Remove substrings in parentheses at the end of a stichwort.
                // Example: "Statistik (ESCO)" -> "Statistik"
                $stichwortlabel = preg_replace('/ *\(.*\)/', '', $stichwort['label']);
                $duplicate = false;
                foreach ($results as $key => $skill) {
                    $skilllabel = preg_replace('/ *\(.*\)/', '', $skill['label']);
                    if ($stichwortlabel == $skilllabel) {
                        if (strpos($stichwort['label'], ' (ESCO)') !== false) {
                            $results[$key]['label'] = $stichwort['label'];
                        }
                        $duplicate = true;
                        break;
                    }
                }
                if (!$duplicate) {
                    
                    $stichwort['uri'] = "";
                    $results[] = $stichwort;
                }
            }
        }

        // if (is_array($scheme) && !in_array('extended-skills-hierarchy', $scheme) && array_key_exists('skills-hierarchy', $results) && empty($results['skills-hierarchy'])) {
        //     $results = array_merge($results, $this->search_skills_hierarchy($term, $limit));
        // }

        if ($onlyrelevant) {
            $results = $this->filter_is_relevant($results);
        }
        if ($schemes[0] != 'member-skills')
        usort($results, function ($a, $b) use ($term) {
            return $this->sort_term_first($a, $b, $term);
        });

        return $results;
    }

    /**
     * Sorts the given ESCO results based on whether their labels start with a given search term.
     *
     * @param array $a The first ESCO result.   ['label' => 'skill1']
     * @param array $b The second ESCO result.  ['label' => 'skill2']
     * @param string $searchterm The search term.
     * @return int Returns -1 if $a starts with $searchterm, 1 if $b starts with $searchterm and 0 if both do not.
     */
    function sort_term_first($a, $b, $searchterm) {
        // labels to lower case
        $a_label = strtolower($a["label"]);
        $b_label = strtolower($b["label"]);
        $searchterm = strtolower($searchterm);
    
        $a_starts_with_search_string = (substr($a_label, 0, strlen($searchterm)) === $searchterm);
        $b_starts_with_search_string = (substr($b_label, 0, strlen($searchterm)) === $searchterm);

        if ($a_starts_with_search_string && !$b_starts_with_search_string) {
            return -1;
        } else if (!$a_starts_with_search_string && $b_starts_with_search_string) {
            return 1;
        } else if ($a_starts_with_search_string && $b_starts_with_search_string) {
            return strlen($a_label) > strlen($b_label);
            // return strcmp($a_label, $b_label);
        } else {
            return 0;
        }
    }

    /**
     * Determines whether the ESCO skill identified by the given URI is a language skill and wether the skill is linked to a given occupation.
     *
     * @param string $uri The URI of the skill to check.
     * @param string|null $occupationuri The URI of an esco occupation.
     * @return bool Returns true if the skill is a language skill, false otherwise.
     */
    function get_skill_info(string $uri, string $occupationuri = null): array {
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

        $result = array(
            'isLanguageSkill' => false,
            'isOccupationSkill' => false,
        );

        // Decode the API response and check for language skill.
        $response = json_decode($response, true);
        foreach ($response['_links']['isInScheme'] as $scheme) {
            if ($scheme['uri'] === "http://data.europa.eu/esco/concept-scheme/skill-language-groups") {
                $result['isLanguageSkill'] = true;
            }
        }
        if ($occupationuri) {
            foreach ($response['_links']['isEssentialForOccupation'] as $occupation) {
                if ($occupation['uri'] === $occupationuri) {
                    $result['isOccupationSkill'] = true;
                    break;
                }
            }
            if ($result['isOccupationSkill'] == false) {
                foreach ($response['_links']['isOptionalForOccupation'] as $occupation) {
                    if ($occupation['uri'] === $occupationuri) {
                        $result['isOccupationSkill'] = true;
                        break;
                    }
                }
            }
        }

        $result['similarSkills'] = array('broader' => array(), 'narrower' => array());
        if (isset($response['_links']['broaderSkill']) && !empty($response['_links']['broaderSkill'])) {
            foreach ($response['_links']['broaderSkill'] as $broaderSkill) {
                $result['similarSkills']['broader'][] = $broaderSkill['title'];
            }
        } 
        if (isset($response['_links']['broaderHierarchyConcept']) && !empty($response['_links']['broaderHierarchyConcept'])) {
            foreach ($response['_links']['broaderHierarchyConcept'] as $broaderHierarchyConcept) {
                $result['similarSkills']['broader'][] = $broaderHierarchyConcept['title'];
            }
        }
        if (isset($response['_links']['narrowerSkill']) && !empty($response['_links']['narrowerSkill'])) {
            foreach ($response['_links']['narrowerSkill'] as $narrowerSkill) {
                $result['similarSkills']['narrower'][] = $narrowerSkill['title'];
            }
        }
        if (isset($response['_links']['isOptionalForSkill']) && !empty($response['_links']['isOptionalForSkill'])) {
            foreach ($response['_links']['isOptionalForSkill'] as $isOptionalForSkill) {
                $result['similarSkills']['narrower'][] = $isOptionalForSkill['title'];
            }
        }
        if (isset($response['_links']['isEssentialForSkill']) && !empty($response['_links']['isEssentialForSkill'])) {
            foreach ($response['_links']['isEssentialForSkill'] as $isEssentialForSkill) {
                $result['similarSkills']['narrower'][] = $isEssentialForSkill['title'];
            }
        }
        

        return $result;
    }

    /**
     * Renders the results of an autocomplete search based on the specified GET parameters as a JSON response.
     * 
     * @var string $_GET['term']   Required. The search term to autocomplete on.
     * @var string $_GET['type']   Optional. The type of concept to search for.
     * @var string $_GET['scheme'] Optional. Comma-separated list of concept schemes to restrict the search to.
     * @var int    $_GET['limit']  Optional. The maximum number of results to return.
     * @var bool   $_GET['onlyrelevant'] Optional. Whether to only include concepts relevant to the wisy database.
     * @var string   $_GET['onlyrelevant'] Optional. Comma-separated list of concepts to restrict the search to.
     * 
     * @return void
     */
    function render_autocomplete() {
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
            $scheme = array_map('trim', $scheme);
        }

        $limit = null;
        if (isset($_GET['limit']) && !empty($_GET['limit'])) {
            $limit = $_GET['limit'];
        }

        $onlyrelevant = true;
        if (isset($_GET['onlyrelevant']) && !empty($_GET['onlyrelevant'])) {
            $onlyrelevant = strtolower($_GET['onlyrelevant']) === 'true' ? true : (strtolower($_GET['onlyrelevant']) === 'false' ? false : JSONResponse::error401($_GET['onlyrelevant'] . ' is not a valid value for onlyrelevant.'));
        }

        $filterconcepts = null;
        if (isset($_GET['filterconcepts']) && !empty($_GET['filterconcepts'])) {
            $filterconcepts = explode(',', $_GET['filterconcepts']);
            if (!is_array($filterconcepts)) {
                $filterconcepts = array($filterconcepts);
            }
            $filterconcepts = array_map('trim', $filterconcepts);
        }

        JSONResponse::send_json_response($this->autocomplete($term, $type, $scheme, $limit, $onlyrelevant, $filterconcepts));
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
     * Sends a JSON response for the request with additional info for a esco skill.
     *
     * @return void
     */
    function render_skill_info() {
        if (!isset($_GET['uri'])) {
            JSONResponse::error400('The request is missing the uri parameter.');
        }
        $occupationuri = null;
        if (isset($_GET['occupationuri']) && !empty($_GET['occupationuri'])) {
            $occupationuri = $_GET['occupationuri'];
        }
        JSONResponse::send_json_response($this->get_skill_info($_GET['uri'], $occupationuri));
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
            case 'autocomplete':
                $this->render_autocomplete();
                break;
            case 'getSkillInfo':
                $this->render_skill_info();
                break;
            default:
                // If the request parameter doesn't match any of the above cases, send a 404 Not Found HTTP response.
                JSONResponse::error404();
        }
    }
}
