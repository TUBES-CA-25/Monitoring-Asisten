<?php
require_once '../app/core/GoogleClient.php';

class GoogleController extends Controller {
    
    // Redirect User ke Google
    public function connect() {
        if (!isset($_SESSION['user_id'])) { header("Location: " . BASE_URL); exit; }
        
        $google = new GoogleClient();
        header("Location: " . $google->getAuthUrl());
        exit;
    }

    // Menangani Callback setelah Login
    public function callback() {
        if (!isset($_GET['code'])) { header("Location: " . BASE_URL); exit; }
        if (!isset($_SESSION['user_id'])) { header("Location: " . BASE_URL . "/auth/login"); exit; }

        $google = new GoogleClient();
        $token = $google->authenticate($_GET['code']);

        if (isset($token['access_token'])) {
            $db = new Database();
            $conn = $db->getConnection();
            $userId = $_SESSION['user_id'];

            // Simpan / Update Token di DB
            $sql = "INSERT INTO user_google_token (id_user, access_token, refresh_token, expires_in) 
                    VALUES (:uid, :at, :rt, :exp)
                    ON DUPLICATE KEY UPDATE 
                    access_token = :at, refresh_token = :rt, expires_in = :exp, created_at = NOW()";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':uid' => $userId,
                ':at' => $token['access_token'],
                ':rt' => $token['refresh_token'] ?? '', // Refresh token hanya dikirim saat pertama kali consent
                ':exp' => $token['expires_in']
            ]);

            // Flash Message & Redirect
            echo "<script>alert('Google Calendar berhasil terhubung!'); window.location.href='" . BASE_URL . "/user/profile';</script>";
        } else {
            echo "<script>alert('Gagal menghubungkan Google Calendar.'); window.location.href='" . BASE_URL . "/user/profile';</script>";
        }
    }
}