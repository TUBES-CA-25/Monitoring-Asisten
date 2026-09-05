<?php
class AuthController extends Controller {
    
    public function index() { $this->login(); }

    public function login() {
        if (isset($_SESSION['role'])) {
            // Jika ada URL yang disimpan sebelum redirect ke login (mis. dari scan QR eksternal),
            // kembalikan user ke sana setelah login berhasil.
            $afterLogin = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);
            header("Location: " . ($afterLogin ?: BASE_URL . $this->getRoleUrl($_SESSION['role'])));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

            // [Item 1] Validasi CSRF untuk form login
            $this->validateCsrf();

            // ── [Item 9] Rate Limiting — Sistem Lockout Bertahap ─────────────
            // Setiap 3 percobaan salah = 1 ronde. Durasi lockout bertambah
            // 10 detik per ronde. Ronde ke-5 (15 total) → pesan hubungi admin.
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ipLockoutKey = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'web_login_rl_' . md5($clientIp) . '.json';
            $ipSec = ['failed_count' => 0, 'lockout_until' => 0, 'current_round' => 0];
            if (file_exists($ipLockoutKey)) {
                $raw = @file_get_contents($ipLockoutKey);
                if ($raw) {
                    $parsed = json_decode($raw, true);
                    if (is_array($parsed)) $ipSec = $parsed;
                }
            }

            if (!isset($_SESSION['login_security'])) {
                $_SESSION['login_security'] = [
                    'failed_count' => 0,
                    'lockout_until' => 0,
                    'current_round' => 0,
                ];
            }
            $sec  = &$_SESSION['login_security'];
            $now  = time();

            // Sinkronisasi session dengan data IP rate limit (mencegah bypass hapus cookie)
            if (($ipSec['lockout_until'] ?? 0) > ($sec['lockout_until'] ?? 0)) {
                $sec = $ipSec;
            }

            // Cek apakah sedang dalam lockout
            if ($sec['lockout_until'] > $now) {
                $remaining     = $sec['lockout_until'] - $now;
                $contactAdmin  = $sec['current_round'] >= 5;
                echo json_encode([
                    'status'         => 'locked',
                    'remaining'      => $remaining,
                    'round'          => $sec['current_round'],
                    'contact_admin'  => $contactAdmin,
                ]);
                exit;
            }

            // Jika lockout sudah lewat, reset (tapi failed_count tetap untuk riwayat)
            if ($sec['lockout_until'] > 0 && $sec['lockout_until'] <= $now) {
                $sec['lockout_until'] = 0;
                $ipSec['lockout_until'] = 0;
                @file_put_contents($ipLockoutKey, json_encode($ipSec));
            }

            try {
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';

                if (!$email || !$password) {
                    echo json_encode([
                        'status' => 'error',
                        'title' => 'Form Kosong',
                        'message' => 'Email dan password wajib diisi.'
                    ]);
                    exit;
                }

                $userModel = $this->model('UserModel');
                $user = $userModel->login($email);

                if ($user && password_verify($password, $user['password'])) {
                    // [Item 3] Regenerasi session ID setelah login berhasil
                    session_regenerate_id(true);
                    // [Item 9] Reset rate limit setelah login berhasil
                    unset($_SESSION['login_security']);
                    if (file_exists($ipLockoutKey)) {
                        @unlink($ipLockoutKey);
                    }

                    $profile = $userModel->getUserById($user['id']);

                    $_SESSION['user_id']       = $user['id'];
                    $_SESSION['profil_id']     = $profile['id_profil'] ?? null;
                    $_SESSION['role']          = ucwords(strtolower($user['role']));
                    $_SESSION['name']          = $user['name'];
                    $_SESSION['jabatan']       = $profile['position'] ?? $_SESSION['role'];
                    $_SESSION['photo']         = $profile['photo_profile'] ?? null;
                    $_SESSION['status_account'] = $user['status_account'] ?? 'ACTIVE';

                    // [BARU] Jika ada redirect tersimpan (mis. user datang dari scan QR
                    // eksternal sebelum login), kembalikan ke sana setelah login.
                    $afterLogin = $_SESSION['redirect_after_login'] ?? null;
                    unset($_SESSION['redirect_after_login']);

                    $redirect = ($_SESSION['status_account'] === 'INACTIVE')
                        ? BASE_URL . '/auth/suspended'
                        : ($afterLogin ?: BASE_URL . $this->getRoleUrl($_SESSION['role']));

                    // session_write_close() memastikan session tertulis ke disk
                    // sebelum browser redirect ke halaman berikutnya.
                    // Tanpa ini ada race condition: browser bisa sampai di halaman
                    // dashboard sebelum PHP menulis session → checkAccess gagal.
                    session_write_close();

                    echo json_encode([
                        'status'   => 'success',
                        'title'    => 'Login Berhasil',
                        'message'  => 'Mengalihkan...',
                        'redirect' => $redirect
                    ]);
                    exit;
                }

                echo json_encode([
                    'status' => 'error',
                    'title' => 'Login Gagal',
                    'message' => 'Email atau password salah.'
                ]);
                // [Item 9] Tambah counter percobaan gagal & trigger lockout per 3 gagal
                $sec['failed_count']++;
                if ($sec['failed_count'] % 3 === 0) {
                    $sec['current_round']  = (int)($sec['failed_count'] / 3);
                    $lockDuration          = $sec['current_round'] * 10; // 10, 20, 30, 40, 50 detik
                    $sec['lockout_until']  = $now + $lockDuration;
                    $contactAdmin          = $sec['current_round'] >= 5;
                    @file_put_contents($ipLockoutKey, json_encode($sec));
                    // Override dengan respons lockout
                    ob_clean();
                    echo json_encode([
                        'status'        => 'locked',
                        'remaining'     => $lockDuration,
                        'round'         => $sec['current_round'],
                        'contact_admin' => $contactAdmin,
                    ]);
                } else {
                    @file_put_contents($ipLockoutKey, json_encode($sec));
                }
                exit;
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Kesalahan Sistem',
                    'message' => 'Terjadi kesalahan pada server. Silakan hubungi administrator.'
                ]);
                exit;
            }
        }

        // [Item 9] Kirim status lockout ke view agar modal tampil ulang saat refresh
        $sec = $_SESSION['login_security'] ?? ['lockout_until' => 0, 'current_round' => 0, 'failed_count' => 0];
        $data['js_config'] = [
            'BASE_URL'        => BASE_URL,
            'lockout_until'   => (int)$sec['lockout_until'],
            'lockout_round'   => (int)$sec['current_round'],
            'failed_count'    => (int)$sec['failed_count'],
        ];

        $this->view('auth/login', $data);
    }


    public function logout() {
        session_destroy();
        header("Location: " . BASE_URL);
        exit;
    }

    /**
     * [BARU - Tahap 30] Halaman akun dinonaktifkan.
     * Akun bisa login tapi langsung diarahkan ke sini.
     * Memperlihatkan pesan + tombol logout.
     */
    public function suspended() {
        // Pastikan sudah login (bukan akses langsung URL)
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "/auth/login");
            exit;
        }
        // Jika ternyata akun sudah aktif kembali, redirect normal
        if (($_SESSION['status_account'] ?? 'ACTIVE') === 'ACTIVE') {
            header("Location: " . BASE_URL . $this->getRoleUrl($_SESSION['role'] ?? ''));
            exit;
        }
        $data['judul'] = 'Akun Dinonaktifkan';
        $data['name']  = $_SESSION['name'] ?? 'Pengguna';
        $this->view('auth/suspended', $data);
    }

    private function getRoleUrl($role) {
        if ($role == 'User') return '/user/dashboard';
        if ($role == 'Admin') return '/admin/dashboard';
        if ($role == 'Kepala Lab') return '/kepalalab/dashboard';
        return ''; 
    }
}
?>
