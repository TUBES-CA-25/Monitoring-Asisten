<?php
class App {
    protected $controller = 'AuthController';
    protected $method = 'index';
    protected $params = [];

    public function __construct() {
        // Parse URL
        $url = $this->parseURL();

        // 1. Tentukan Controller
        // Cek apakah segmen pertama URL ada file controllernya
        if (isset($url[0])) {
            $u_ctrl = ucfirst($url[0]) . 'Controller';
            if (file_exists('../app/controllers/' . $u_ctrl . '.php')) {
                $this->controller = $u_ctrl;
                unset($url[0]);
            }
        }

        require_once '../app/controllers/' . $this->controller . '.php';
        $this->controller = new $this->controller;

        // 2. Tentukan Method
        if (isset($url[1])) {
            if (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            }
        }

        // 3. Ambil Parameter sisanya
        if (!empty($url)) {
            $this->params = array_values($url);
        }

        // 4. Jalankan
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    public function parseURL() {
        if (isset($_GET['url'])) {
            // Hapus slash terakhir, sanitasi, lalu pecah jadi array
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
    }
}