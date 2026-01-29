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
        // Prefer fixed/clean model file when present to avoid loading corrupted files
        $fixedPath = '../app/models/' . $model . '_fixed.php';
        $origPath = '../app/models/' . $model . '.php';
        if (file_exists($fixedPath)) {
            require_once $fixedPath;
            return new $model;
        }
        if (file_exists($origPath)) {
            require_once $origPath;
            return new $model;
        }
        die("Model <b>$model</b> tidak ditemukan.");
    }
}
?>