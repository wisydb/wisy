<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/admin/sql_curr.inc.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/admin/config/config.inc.php");

/**
 * Class WISYKI_PYTHON_CLASS
 *
 * Represents a library of functions to handle request to the WISYKI-API.
 * 
 * The "Weiterbildungsscout" was created by the project consortium "WISY@KI" as part of the Innovationswettbewerb INVITE 
 * and was funded by the Bundesinstitut für Berufsbildung and the Federal Ministry of Education and Research.
 *
 * @copyright   2023 ISy TH Lübeck <dev.ild@th-luebeck.de>
 * @author		Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class WISYKI_PYTHON_CLASS {
    /**
     * Filelocation where all python scripts reside.
     *
     * @var string
     */
    private string $pythonlib;

    /**
     * URI of the WISYKI-API.
     *
     * @var string
     */
    private string $api_endpoint;

    /**
     * Constructor of WISYKI_PYTHON_CLASS.
     */
    function __construct() {
        $this->pythonlib = dirname(__FILE__) . '/wisyki/python/';
        if (!defined('WISYKI_API')) {
            throw new Exception('WISYKI_API not set in admin/config/config.inc.php');
        }
        $this->api_endpoint = WISYKI_API;
    }

    /**
     * Executes the specified python module.
     *
     * @param  string $modulename   The name of the Python module to execute.
     * @param  array  $params       The parameters to pass to the Python module.
     * @param  string $errorlangstr The error message to display if the command fails.
     * @return array                Returns an array containing the result body and exit code.
     * @throws Exception            Throws an Exception if the command fails.
     */
    public function exec_command(string $modulename, array $params, string $errorlangstr) {
        // Executes the specified Python module and returns the result.
        $cmd = PYTHON_HOME . ' ' . $this->pythonlib . $modulename . ' ';
        if (count($params) >= 1) {
            foreach ($params as $param) {
                $cmd .= escapeshellarg($param) . ' ';
            }
        }

        $output = null;
        $exitcode = null;
        $result = exec($cmd, $output, $exitcode);

        if (!$result) {
            throw new Exception($errorlangstr . "\nPython output start: " . implode(", ", $output) . " - python output end\n", $exitcode);
        }

        return [$result, $exitcode];
    }

    /**
     * Extracts keywords from a given text.
     *
     * @param  string $text The text to extract keywords from.
     * @return mixed        Returns an array of keywords or NULL on failure.
     */
    public function extract_keywords(string $title = null, string $text) {
        // Remove wisy headings like '''Inhalte:'''.
        $text = preg_replace("/'{3}.+?'{3}/", "", $text);
        // Extracts keywords from a given text using a remote API.
        $endpoint = "/extractKeywords";
        $data = [
            'text' => $text
        ];

        if (isset($title)) {
            $data['title'] = $title;
        }

        $post_data = json_encode($data);

        $url = $this->api_endpoint . $endpoint;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl, CURLOPT_TIMEOUT, 80);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_POST, true);

        // Set HTTP Header for POST request  
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        $response = curl_exec($curl);

        if (curl_error($curl)) {
            throw new Exception('Request Error:' . curl_error($curl));
        }

        curl_close($curl);

        // Decode response and filter results for title and uri attributes. 
        $response = json_decode($response, true);
        return $response;
    }


    /**
     * Predicts the comprehension level of a given course.
     *
     * @param  string $title       The title of the course.
     * @param  string $description The description of the course.
     * @return mixed
     */
    public function predict_comp_level(string $title = '', string $description = '') {
        $endpoint = "/predictCompLevel";
        $data = [
            'title' => utf8_encode($title),
            'description' => utf8_encode($description)
        ];

        $post_data = json_encode($data);

        $url = $this->api_endpoint . $endpoint;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl, CURLOPT_TIMEOUT, 80);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_POST, true);

        // Set HTTP Header for POST request 
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        $response = curl_exec($curl);

        if (curl_error($curl)) {
            throw new Exception('Request Error:' . curl_error($curl));
        }

        curl_close($curl);

        // Decode response and filter results for title and uri attributes.
        $response = json_decode($response, true);
        return $response;
    }


    /**
     * Predicts esco terms relevant to a course.
     *
     * @param  string $title            The title of the course.
     * @param  string $description      The description of the course.
     * @param  string $thema            The thema of the course.
     * @param  array $abschluesse       The abschluesse of the course.
     * @param  array $sachstichworte    The sachstichworte of the course.
     * @param  array $filterconcepts    An optional array of esco concepts for filtering the predictions. Default is an empty array.
     * @param  int $strict              Level of strictness of filtering the results.
     * @return mixed
     */
    public function predict_esco_terms(string $title, string $description, string $thema, array $abschluesse, array $sachstichworte, array $filterconcepts = array(), int $strict = 2) {
        $endpoint = "/chatsearch";
        $wisytags = $sachstichworte;
        $wisytags = array_merge($wisytags, $abschluesse);

        // Add Keywords and topic to course description to influence the outcome of the esco suggestions, in case the course description is not descriptive enough on its own. 
        $doc = $title . ' ' . $description . ' ' . join(', ', $wisytags) . ' ' . $thema;

        $data = [
            "doc" => $doc,
            "top_k" => 20,
            // Check if constant 'OPENAI_API_KEY' is set
            "openai_api_key" => defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null,
            "strict" => $strict,
            "filterconcepts" => $filterconcepts,
        ];

        $post_data = json_encode($data);

        $url = $this->api_endpoint . $endpoint;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl, CURLOPT_TIMEOUT, 40);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_POST, true);

        // Set HTTP Header for POST request 
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        $response = curl_exec($curl);

        if (curl_error($curl)) {
            // throw new Exception(('Request Error:' . curl_error($curl));
            throw new Exception('Request Error:' . curl_error($curl), 1);
        }

        curl_close($curl);

        // Decode response and filter results for title and uri attributes.
        $response = json_decode($response, true);
        return $response;
    }


    /**
     * Triggers a re-training of the competency-level prediction model with the specified training data.
     *
     * @param  string $training_data  The training data as a JSON string.
     * @return array                  An array with the response from the API.
     */
    public function train_comp_level_model(string $training_data) {
        $endpoint = "/trainCompLevel";

        $url = $this->api_endpoint . $endpoint;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $training_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_POST, true);

        // Set HTTP Header for POST request 
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        $response = curl_exec($curl);

        if (curl_error($curl)) {
            throw new Exception('Request Error:' . curl_error($curl));
        }

        curl_close($curl);

        // Decode response and filter results for title and uri attributes.
        $response = json_decode($response, true);
        return $response;
    }

    /**
     * Sort courses in referance to a base string based on semantic similarity.
     *
     * @param  string $base
     * @param  array $courses
     * @return array Sorted documents.
     */
    public function sortsemantic(string $base, array $courses) {
        $endpoint = "/getEmbeddings";

        $data = array(
            "docs" => array($base),
        );

        $url = $this->api_endpoint . $endpoint;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_POST, true);

        // Set HTTP Header for POST request 
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        $response = curl_exec($curl);

        if (curl_error($curl)) {
            throw new Exception('Request Error:' . curl_error($curl));
        }

        curl_close($curl);

        // Decode response and filter results for title and uri attributes.
        $base_embeddings = json_decode($response, true);
        $base_embedding = $base_embeddings[0];

        $doc_embeddings = array_map(function ($course) {
            return json_decode($course['embedding']);
        }, $courses);

        // Calculate cosine similarity for each pair of vector arrays
        $similarityMatrix = [];
        $row = [];
        foreach ($doc_embeddings as $doc_embedding) {
            $similarity = $this->cosineSimilarity($base_embedding, $doc_embedding);
            $row[] = $similarity;
        }
        $similarityMatrix = $row;

        // Add score from response to every document
        foreach ($courses as $index => $course) {
            $courses[$index]['score'] = $similarityMatrix[$index];
        }

        // sort documents based on score
        usort($courses, function ($a, $b) {
            return $a['score'] < $b['score'];
        });

        return $courses;
    }

    /**
     * Calculates the cosine similarity between two vectors.
     * 
     * @param array $vectorA - The first vector.
     * @param array $vectorB - The second vector.
     * @return float - The cosine similarity between the two vectors.
     */
    function cosineSimilarity($vectorA, $vectorB) {
        if (!$vectorB) {
            return 0;
        }
        // Calculate the dot product of the two vectors
        $dotProduct = 0;
        foreach ($vectorA as $index => $value) {
            if (isset($vectorB[$index])) {
                $dotProduct += $value * $vectorB[$index];
            }
        }

        // Calculate the magnitudes of the vectors
        $magnitudeA = sqrt(array_sum(array_map(function ($val) {
            return $val * $val;
        }, $vectorA)));

        $magnitudeB = sqrt(array_sum(array_map(function ($val) {
            return $val * $val;
        }, $vectorB)));

        // Calculate the cosine similarity
        if ($magnitudeA > 0 && $magnitudeB > 0) {
            return $dotProduct / ($magnitudeA * $magnitudeB);
        } else {
            return 0; // Handle division by zero case
        }
    }

    /**
     * Sort courses in referance to a base string based on semantic similarity.
     *
     * @param  array $courses
     * @return array Sorted documents.
     */
    public function getEmbeddings(array $documents) {
        $endpoint = "/getEmbeddings";

        $data = array(
            "docs" => $documents,
        );

        $url = $this->api_endpoint . $endpoint;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_POST, true);

        // Set HTTP Header for POST request 
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        $response = curl_exec($curl);

        if (curl_error($curl)) {
            throw new Exception('Request Error:' . curl_error($curl));
        }

        curl_close($curl);

        return json_decode($response, true);
    }

    /**
     * Returns a report with the current statistics of the competency-level prediction model.
     *
     * @return array  An array with the response from the API.
     */
    public function get_comp_level_report() {
        $endpoint = "/getCompLevelReport";

        $url = $this->api_endpoint . $endpoint;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($curl);

        if (curl_error($curl)) {
            throw new Exception('Request Error:' . curl_error($curl));
        }

        curl_close($curl);

        // Decode response and filter results for title and uri attributes.
        $response = json_decode($response, true);
        return $response;
    }
}
