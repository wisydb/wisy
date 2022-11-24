<?php

require_once("./sql_curr.inc.php");
require_once("./config/config.inc.php");

class WISYKI_PYTHON_API {

    function __construct()
	{
        $this->pythonlib = dirname(__FILE__) . '/python/';
        $this->api_uri = "https://wisyki.eu.pythonanywhere.com";
    }

    /**
     * Executes the specified python module.
     *
     * @param  string $modulename
     * @param  array  $params
     * @param  string $errorlangstr
     * @return array [0] is the result body and [1] the exit code.
     */
    public function exec_command(string $modulename, array $params, string $errorlangstr) {

        $cmd = PYTHON_HOME . ' ' . $this->pythonlib . $modulename . ' ';
        if(count($params) >= 1) {
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

        if (curl_error($curl)){
            echo 'Request Error:' . curl_error($curl);
            return;
        }

        curl_close($curl);

        // Decode response and filter results for title and uri attributes.
        $response = json_decode($response, true);
        return $response;
    }
    
    
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

        if (curl_error($curl)){
            echo 'Request Error:' . curl_error($curl);
            return;
        }

        curl_close($curl);

        // Decode response and filter results for title and uri attributes.
        $response = json_decode($response, true);
        return $response;
    }
    
    
    public function get_comp_level_report() {
        $endpoint = "/getCompLevelReport";

        $url = $this->api_uri . $endpoint;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
          
        $response = curl_exec($curl);

        if (curl_error($curl)){
            echo 'Request Error:' . curl_error($curl);
            return;
        }

        curl_close($curl);

        // Decode response and filter results for title and uri attributes.
        $response = json_decode($response, true);
        return $response;
    }
}