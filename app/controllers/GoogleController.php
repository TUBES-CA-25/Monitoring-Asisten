<?php
require_once '../app/core/GoogleClient.php';

class GoogleController extends Controller {
    
    public function connect() {
        if (!isset($_SESSION['user_id'])) { header("Location: " . BASE_URL); exit; }
        
        $google = new GoogleClient();
        header("Location: " . $google->getAuthUrl());
        exit;
    }

    public function callback() {
        if (!isset($_GET['code'])) { header("Location: " . BASE_URL); exit; }
        if (!isset($_SESSION['user_id'])) { header("Location: " . BASE_URL . "/auth/login"); exit; }

        $google = new GoogleClient();
        $token = $google->authenticate($_GET['code']);

        if (isset($token['access_token'])) {
            $db = new Database();
            $conn = $db->getConnection();
            $userId = $_SESSION['user_id'];

            $sql = "INSERT INTO user_google_token (id_user, access_token, refresh_token, expires_in) 
                    VALUES (:uid, :at, :rt, :exp)
                    ON DUPLICATE KEY UPDATE 
                    access_token = :at, refresh_token = :rt, expires_in = :exp, created_at = NOW()";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':uid' => $userId,
                ':at' => $token['access_token'],
                ':rt' => $token['refresh_token'] ?? '', 
                ':exp' => $token['expires_in']
            ]);

            $_SESSION['google_modal'] = [
                'type' => 'success',
                'title' => 'Integrasi Berhasil!',
                'message' => 'Akun Google Calendar berhasil terhubung. Jadwal Anda kini tersinkronisasi otomatis.'
            ];
        } else {
            $_SESSION['google_modal'] = [
                'type' => 'error',
                'title' => 'Gagal Terhubung',
                'message' => 'Terjadi kesalahan saat menautkan akun Google. Silakan coba lagi.'
            ];
        }

        header("Location: " . BASE_URL . "/user/profile");
        exit;
    }
}