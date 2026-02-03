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

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['profil_id'] = $profile['id_profil'] ?? null;
                $_SESSION['role']      = ucwords(strtolower($user['role']));
                $_SESSION['name']      = $user['name'];
                $_SESSION['jabatan']  = $profile['position'] ?? $_SESSION['role'];
                $_SESSION['photo']    = $profile['photo_profile'] ?? null;

                echo json_encode([
                    'status' => 'success',
                    'title' => 'Login Berhasil',
                    'message' => 'Mengalihkan ke dashboard...',
                    'redirect' => BASE_URL . $this->getRoleUrl($_SESSION['role'])
                ]);
                exit;
            }

            echo json_encode([
                'status' => 'error',
                'title' => 'Login Gagal',
                'message' => 'Email atau password salah.'
            ]);
            exit;
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

    private function getRoleUrl($role) {
        if ($role == 'User') return '/user/dashboard';
        if ($role == 'Admin') return '/admin/dashboard';
        if ($role == 'Kepala Lab') return '/kepalalab/dashboard';
        return ''; 
    }
}
?>
