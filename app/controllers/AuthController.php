<?php
class AuthController extends Controller {
    
    public function index() { $this->login(); }

    public function login() {
        if (isset($_SESSION['role'])) {
            header("Location: " . BASE_URL . $this->getRoleUrl($_SESSION['role']));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');

            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $this->model('UserModel')->login($email);

            if ($user && password_verify($password, $user['password'])) {
                $profile = $this->model('UserModel')->getUserById($user['id']);

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['profil_id'] = $user['id_profil'];
                $_SESSION['role']      = ucwords(strtolower($user['role'])); 
                $_SESSION['name']      = $user['name'];
                $_SESSION['jabatan']   = !empty($profile['position']) ? $profile['position'] : $_SESSION['role'];
                $_SESSION['photo']     = $profile['photo_profile'] ?? null; 

                echo json_encode([
                    'status' => 'success',
                    'title' => 'Login Berhasil!',
                    'message' => 'Mengalihkan ke dashboard...',
                    'redirect' => BASE_URL . $this->getRoleUrl($_SESSION['role'])
                ]);
                exit;
            } else {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Login Gagal',
                    'message' => 'Email atau Password yang Anda masukkan salah.'
                ]);
                exit;
            }
        }
        
        $this->view('auth/login'); 
    }

    public function logout() {
        session_destroy();
        header("Location: " . BASE_URL);
        exit;
    }

    private function getRoleUrl($role) {
        if ($role == 'User') return '/user/dashboard';
        if ($role == 'Admin') return '/admin/dashboard';
        if ($role == 'Super Admin') return '/superadmin/dashboard';
        return ''; 
    }
}
?>