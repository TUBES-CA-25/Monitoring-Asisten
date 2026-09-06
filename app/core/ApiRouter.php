<?php
class ApiRouter {
    private $url = [];
    private $method = '';
    private $controller = '';
    private $action = '';
    private $params = [];

    public function __construct() {
        // Handle CORS
        ApiResponse::handleCors();

        // Parse URL
        if (isset($_GET['url'])) {
            $this->url = explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }

        $this->method = strtolower($_SERVER['REQUEST_METHOD']);

        // Route format: /api/{controller}/{action}/{param1}/{param2}
        // Example: /api/attendance/clock-in
        
        if (empty($this->url[0]) || $this->url[0] !== 'api') {
            ApiResponse::error('API endpoint not found', 404);
        }

        // Get controller name (url[1])
        if (isset($this->url[1])) {
            $this->controller = ucfirst(strtolower($this->url[1])) . 'Api';
            unset($this->url[1]);
        } else {
            ApiResponse::error('Controller required', 400);
        }

        // Get action name (url[2])
        if (isset($this->url[2])) {
            $this->action = strtolower($this->url[2]);
            unset($this->url[2]);
        } else {
            ApiResponse::error('Action required', 400);
        }

        // Get params (url[3] and onwards)
        if (!empty($this->url)) {
            $this->params = array_values($this->url);
        }

        // Load & Execute Controller
        $this->executeController();
    }

    private function executeController() {
        $controllerPath = '../app/api/' . $this->controller . '.php';

        if (!file_exists($controllerPath)) {
            ApiResponse::error('Controller not found: ' . $this->controller, 404);
        }

        require_once $controllerPath;

        if (!class_exists($this->controller)) {
            ApiResponse::error('Class not found: ' . $this->controller, 404);
        }

        $controller = new $this->controller();

        if (!method_exists($controller, $this->action)) {
            ApiResponse::error('Action not found: ' . $this->action, 404);
        }

        // Execute method with params
        call_user_func_array([$controller, $this->action], $this->params);
    }
}
?>