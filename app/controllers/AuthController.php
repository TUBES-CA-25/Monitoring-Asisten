<?php
class AuthController extends Controller {
    
    public function index() { $this->login(); }

    public function login() {
        if (isset($_SESSION['role'])) {
            header("Location: " . BASE_URL . $this->getRoleUrl($_SESSION['role']));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

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
                    $profile = $userModel->getUserById($user['id']);

                    $_SESSION['user_id']       = $user['id'];
                    $_SESSION['profil_id']     = $profile['id_profil'] ?? null;
                    $_SESSION['role']          = ucwords(strtolower($user['role']));
                    $_SESSION['name']          = $user['name'];
                    $_SESSION['jabatan']       = $profile['position'] ?? $_SESSION['role'];
                    $_SESSION['photo']         = $profile['photo_profile'] ?? null;
                    $_SESSION['status_account'] = $user['status_account'] ?? 'ACTIVE';

                    // [BARU - Tahap 30] Akun nonaktif: bisa login, tapi
                    // langsung diarahkan ke halaman "suspended" - bukan
                    // dashboard normal.
                    $redirect = ($_SESSION['status_account'] === 'INACTIVE')
                        ? BASE_URL . '/auth/suspended'
                        : BASE_URL . $this->getRoleUrl($_SESSION['role']);

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

        $data['js_config'] = [
            'BASE_URL' => BASE_URL
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
