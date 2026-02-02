<?php
class Auth extends Controller {
    public function index() {
        if(isset($_SESSION['user_id'])) { header('Location: ' . BASEURL . '/dashboard'); exit; }
        $this->view('auth/login');
    }
    public function login() {
        $email = $_POST['email']; $pass = $_POST['password'];
        $user = $this->model('User_model')->getUserByEmail($email);
        if($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role_id'] = $user['role_id'];
            header('Location: ' . BASEURL . '/dashboard');
        } else { header('Location: ' . BASEURL . '/auth'); }
    }
    public function logout() { session_destroy(); header('Location: ' . BASEURL . '/auth'); }
}