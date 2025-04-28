<?php
/**
 * Common API utility functions
 */

if (!function_exists('sendError')) {
    /**
     * Send a JSON error response and exit
     * 
     * @param int $code HTTP status code
     * @param string $message Error message
     * @return void
     */
    function sendError($code, $message) {
        http_response_code($code);
        echo json_encode(['error' => $message]);
        exit;
    }
}

if (!function_exists('sendResponse')) {
    /**
     * Send a JSON success response
     * 
     * @param mixed $data Response data
     * @param int $code HTTP status code (default 200)
     * @return void
     */
    function sendResponse($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data);
        exit;
    }
}
?>