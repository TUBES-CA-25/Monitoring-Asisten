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
        // 1. Cek Session
        if (session_status() == PHP_SESSION_NONE) session_start();

        // 2. Cek Login
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "/auth/login");
            exit;
        }

        // 3. Cek Role
        if (!empty($allowedRoles)) {
            $userRole = $_SESSION['role'] ?? '';

            if (!in_array($userRole, $allowedRoles)) {
                
                // --- DISINI KITA PANGGIL TAMPILAN 403 KAMU ---
                // Kita langsung panggil ErrorController, tidak perlu bikin echo manual lagi
                require_once '../app/controllers/ErrorController.php';
                $error = new ErrorController();
                $error->forbidden(); // <--- Ini akan membuka views/errors/403.php kamu
                
                exit; // Stop agar user tidak bisa lanjut
            }
        }
    }
}
?>