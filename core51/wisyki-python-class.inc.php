<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/admin/sql_curr.inc.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/admin/config/config.inc.php");
require_once($_SERVER['DOCUMENT_ROOT'] . '/core51/wisyki-json-response.inc.php');

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
    private string $api_uri;

    /**
     * Constructor of WISYKI_PYTHON_CLASS.
     */
    function __construct() {
        $this->pythonlib = dirname(__FILE__) . '/python/';
        $this->api_uri = "https://wbhessen.eu.pythonanywhere.com";
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
            throw new Exception($errorlangstr . "\nPython output: " . implode(", ", $output) . "\n", $exitcode);
        }

        return [$result, $exitcode];
    }

    /**
     * Extracts keywords from a given text.
     *
     * @param  string $text The text to extract keywords from.
     * @return mixed        Returns an array of keywords or NULL on failure.
     */
    public function extract_keywords(string $title=null, string $text) {
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

        $url = $this->api_uri . $endpoint;
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
            JSONResponse::error500('Request Error:' . curl_error($curl));
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

        $url = $this->api_uri . $endpoint;
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
            JSONResponse::error500('Request Error:' . curl_error($curl));
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
     * @return mixed
     */
    public function predict_esco_terms(string $title, string $doc, string $thema, array $abschluesse, array $sachstichworte) {
        $endpoint = "/predictESCO";
        $wisytags = $sachstichworte;
        $wisytags = array_merge($wisytags, $abschluesse);
        $keywords = [$title, $thema];
        $keywords = array_merge($keywords, $wisytags);
        // Add Keywords and topic to course description to influence the outcome of the esco suggestions, in case the course description is not descriptive enough on its own. 
        $doc .= ' ' . $title . ' ' . join(', ', $wisytags) . join(', ', $wisytags) . ' ' . $thema;

        $data = [
            "searchterms" => [
                "keywords" => $keywords
            ],
            "doc" => $doc,
            "extract_keywords" => count($sachstichworte) <= 1,
            "exclude_irrelevant" => true
        ];

        $post_data = json_encode($data);

        $url = $this->api_uri . $endpoint;
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
            // JSONResponse::error500('Request Error:' . curl_error($curl));
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

        $url = $this->api_uri . $endpoint;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $training_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_POST, true);

        // Set HTTP Header for POST request 
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        $response = curl_exec($curl);

        if (curl_error($curl)) {
            JSONResponse::error500('Request Error:' . curl_error($curl));
        }

        curl_close($curl);

        // Decode response and filter results for title and uri attributes.
        $response = json_decode($response, true);
        return $response;
    }

    /**
     * Returns a report with the current statistics of the competency-level prediction model.
     *
     * @return array  An array with the response from the API.
     */
    public function get_comp_level_report() {
        $endpoint = "/getCompLevelReport";

        $url = $this->api_uri . $endpoint;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($curl);

        if (curl_error($curl)) {
            JSONResponse::error500('Request Error:' . curl_error($curl));
        }

        curl_close($curl);

        // Decode response and filter results for title and uri attributes.
        $response = json_decode($response, true);
        return $response;
    }
}
