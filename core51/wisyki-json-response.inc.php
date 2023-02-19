<?php

/**
 * Class JSONResponse
 *
 * A helper class for handling JSON responses.
 * 
 * The "Weiterbildungsscout" was created by the project consortium "WISY@KI" as part of the Innovationswettbewerb INVITE 
 * and was funded by the Bundesinstitut für Berufsbildung and the Federal Ministry of Education and Research.
 * 
 * @copyright   2023 ISy TH Lübeck <dev.ild@th-luebeck.de>
 * @author		Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class JSONResponse {
    /**
     * Sends a 400 Bad Request response to the client.
     *
     * @param string $message The error message to include in the response.
     */
    public static function error400($message) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(400);
        echo json_encode(array(
            'error_code' => '400',
            'error_message' => $message,
        ));
        die();
    }

    /**
     * Sends a 401 Unauthorized response to the client.
     *
     * @param string $message The error message to include in the response.
     */
    public static function error401($message) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(401);
        echo json_encode(array(
            'error_code' => '401',
            'error_message' => $message,
        ));
        die();
    }

    /**
     * Sends a 404 Not Found response to the client.
     */
    public static function error404() {
        http_response_code(404);
        die();
    }

    /**
     * Sends a 500 Internal Server Error response to the client.
     * 
     * @param string|null $message The error message to include in the response.
     */
    public static function error500(string|null $message = '') {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(500);
        echo json_encode(array(
            'error_code' => '500',
            'error_message' => isset($message) ? $message : 'Server error - please retry again at a later time.',
        ));
        die();
    }

    /**
     * Sends a successful JSON response to the client.
     *
     * @param mixed $data The data to include in the response.
     */
    public static function send_json_response($data) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
        die();
    }
}
