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
            $saveStatus = $this->model('UserModel')->saveGoogleToken($_SESSION['user_id'], $token);

            if ($saveStatus) {
                $_SESSION['google_modal'] = [
                    'type' => 'success',
                    'title' => 'Integrasi Berhasil!',
                    'message' => 'Akun Google Calendar berhasil terhubung. Jadwal Anda kini tersinkronisasi otomatis.'
                ];
            } else {
                $_SESSION['google_modal'] = [
                    'type' => 'error',
                    'title' => 'Gagal Menyimpan',
                    'message' => 'Terjadi kesalahan saat menyimpan token database.'
                ];
            }
        } else {
            $_SESSION['google_modal'] = [
                'type' => 'error',
                'title' => 'Gagal Terhubung',
                'message' => 'Gagal mendapatkan token dari Google. Silakan coba lagi.'
            ];
        }

        header("Location: " . BASE_URL . "/user/profile");
        exit;
    }
}