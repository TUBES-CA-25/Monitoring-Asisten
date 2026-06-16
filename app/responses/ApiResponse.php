<?php
class ApiResponse {
    
    /**
     * Return Success Response
     * @param mixed $data - Data yang akan dikirim
     * @param string $message - Pesan sukses
     * @param int $statusCode - HTTP Status Code
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        echo json_encode([
            'status' => 'success',
            'statusCode' => $statusCode,
            'message' => $message,
            'data' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Return Error Response
     * @param string $message - Pesan error
     * @param int $statusCode - HTTP Status Code
     * @param mixed $errors - Detail errors (optional)
     */
    public static function error($message = 'Error', $statusCode = 400, $errors = null) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        $response = [
            'status' => 'error',
            'statusCode' => $statusCode,
            'message' => $message
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Handle OPTIONS Request (CORS Preflight)
     */
    public static function handleCors() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            exit;
        }
    }
}
?>