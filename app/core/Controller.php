<?php
class Controller {
    public function view($view, $data = []) {
        // Cek apakah file view ada
        if (file_exists('../app/views/' . $view . '.php')) {
            // PENTING: extract() mengubah array ['user' => 'Andi'] menjadi variabel $user = 'Andi'
            // Ini solusi untuk error "Undefined variable" di screenshot Anda.
            extract($data); 
            require_once '../app/views/' . $view . '.php';
        } else {
            die("View <b>$view</b> tidak ditemukan.");
        }
    }

    public function model($model) {
        if (file_exists('../app/models/' . $model . '.php')) {
            require_once '../app/models/' . $model . '.php';
            return new $model;
        } else {
            die("Model <b>$model</b> tidak ditemukan.");
        }
    }
}
?>