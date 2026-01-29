<?php
class AuthController extends Controller {
    
    public function index() { $this->login(); }

    public function login() {
        // Cek jika sudah login, redirect sesuai role
        if (isset($_SESSION['role'])) {
            header("Location: " . BASE_URL . $this->getRoleUrl($_SESSION['role']));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // 1. Ambil data User (Login credential)
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $this->model('UserModel')->login($email);

            // Debug logging (temporary) untuk diagnosis login
            error_log("[AUTH] login attempt email=" . $email);
            if ($user === false || $user === null) {
                error_log("[AUTH] user record not found for: " . $email);
            } else {
                // mask sensitive fields when logging
                $safe = $user;
                if (isset($safe['password'])) $safe['password'] = '[HIDDEN]';
                error_log("[AUTH] user fetched: " . json_encode($safe));
            }

            $ok = ($user && isset($user['password']) && password_verify($password, $user['password']));
            error_log("[AUTH] password_verify result: " . ($ok ? 'true' : 'false'));

            if ($ok) {
                // 2. Ambil data Profile (Info detail user) untuk Session
                $profile = $this->model('UserModel')->getUserById($user['id']);

                // 3. SET SESSION LENGKAP
                $_SESSION['user_id']   = $user['id'];       // id_user (FK)
                $_SESSION['profil_id'] = $user['id_profil'] ?? null; // id_profil (PK tabel profile)
                $_SESSION['role']      = ucwords(strtolower($user['role'])); 
                $_SESSION['name']      = $user['name'] ?? ($profile['name'] ?? '');
                // Ambil Jabatan (Prioritas: Jabatan di Profile -> Role di User)
                $_SESSION['jabatan']   = !empty($profile['position']) ? $profile['position'] : $_SESSION['role'];
                $_SESSION['photo']     = $profile['photo_profile'] ?? 'default.jpg'; 

                // 4. Redirect
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
?>
