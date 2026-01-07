<?php
class AuthController extends Controller {
    
    public function index() { $this->login(); }

    public function login() {
        if (isset($_SESSION['role'])) {
            header("Location: " . BASE_URL . $this->getRoleUrl($_SESSION['role']));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Panggil method login di Model (Pastikan UserModel sudah update ke tabel baru)
            $user = $this->model('UserModel')->login($_POST['email']);

            if ($user && password_verify($_POST['password'], $user['password'])) {
                // SIMPAN DATA PENTING KE SESSION
                $_SESSION['user_id']   = $user['id'];       // id_user
                $_SESSION['profil_id'] = $user['id_profil'];// id_profil (PENTING untuk relasi)
                $_SESSION['role']      = ucwords(strtolower($user['role'])); 
                $_SESSION['name']      = $user['name'];     // nama dari tabel profile
                
                // Ambil Jabatan (Prioritas: Jabatan di Profile -> Role di User)
                $_SESSION['jabatan']   = !empty($user['position']) ? $user['position'] : $_SESSION['role'];
                $_SESSION['photo']     = $user['photo_profile'] ?? 'default.jpg'; // Untuk foto di sidebar

                header("Location: " . BASE_URL . $this->getRoleUrl($_SESSION['role']));
                exit;
            } else {
                echo "<script>alert('Login Gagal! Email atau Password Salah.'); window.location.href='".BASE_URL."';</script>";
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