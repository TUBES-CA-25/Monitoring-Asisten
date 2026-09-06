<?php
require_once '../app/core/GoogleClient.php';

class GoogleController extends Controller {
    
    public function connect() {
        if (!isset($_SESSION['user_id'])) { header("Location: " . BASE_URL); exit; }
        
        $google = new GoogleClient();

        // Jika kredensial Google OAuth belum diisi di .env, jangan lempar user
        // ke Google dengan client_id kosong (akan menghasilkan error di pihak Google).
        // Tampilkan pesan informatif lewat modal di halaman profile.
        if (!$google->isConfigured()) {
            $_SESSION['google_modal'] = [
                'type' => 'error',
                'title' => 'Integrasi Belum Dikonfigurasi',
                'message' => 'Integrasi Google Calendar belum dikonfigurasi oleh Administrator. Silakan hubungi Administrator.'
            ];
            $roleLink = strtolower(str_replace(' ', '', $_SESSION['role'] ?? 'user'));
            header("Location: " . BASE_URL . "/" . $roleLink . "/profile");
            exit;
        }

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

        // FIX: redirect ke halaman profile sesuai role pengguna (Admin/Kepala Lab/User),
        // bukan selalu ke /user/profile yang hanya bisa diakses role 'User'.
        $roleLink = strtolower(str_replace(' ', '', $_SESSION['role'] ?? 'user'));
        header("Location: " . BASE_URL . "/" . $roleLink . "/profile");
        exit;
    }
}