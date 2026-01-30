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

    protected function checkAccess($allowedRoles = []) {
        // 1. Pastikan Session Mulai
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // 2. Cek Login (Kalau belum login -> Lempar ke Halaman Login)
        if (!isset($_SESSION['role'])) {
            header("Location: " . BASE_URL . "/auth/login");
            exit;
        }

        // 3. Cek Role (Kalau sudah login tapi SALAH role -> Forbidden 403)
        // Contoh: User biasa maksa masuk halaman Admin
        if (!empty($allowedRoles)) {
            if (!in_array($_SESSION['role'], $allowedRoles)) {
                require_once '../app/controllers/ErrorController.php';
                (new ErrorController)->forbidden(); // Panggil Layar Ungu
                exit; 
            }
        }
    }
}
?>