<?php
class Controller {
    public function view($view, $data = []) {
        // Cek apakah file view ada
        if (file_exists('../app/views/' . $view . '.php')) {
            // PENTING: extract() mengubah array ['user' => 'Andi'] menjadi variabel $user = 'Andi'
            // Ini solusi untuk error "Undefined variable" di screenshot Anda.
            extract($data); 
            require_once '../app/views/' . $view . '.php';
        } else {
            die("View <b>$view</b> tidak ditemukan.");
        }
    }

    public function model($model) {
        if (file_exists('../app/models/' . $model . '.php')) {
            require_once '../app/models/' . $model . '.php';
            return new $model;
        } else {
            die("Model <b>$model</b> tidak ditemukan.");
        }
    }

    /**
     * [Item 1] Ambil (atau buat) CSRF token untuk sesi saat ini.
     * Token disimpan di session dan disisipkan ke <meta name="csrf-token">
     * di header.php agar dapat dibaca oleh JavaScript.
     */
    protected function getCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * [Item 1] Validasi CSRF token pada request POST.
     * Membaca token dari:
     *   1. Header X-CSRF-TOKEN  (dikirim oleh fetch() global patch di global.js)
     *   2. POST field _token    (untuk form HTML tradisional — fallback)
     *
     * Melempar respons 403 dan menghentikan eksekusi jika token tidak cocok.
     */
    protected function validateCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        // Baca token dari header (lebih aman) atau POST body
        $headers   = function_exists('getallheaders') ? getallheaders() : [];
        $fromHeader = $headers['X-Csrf-Token'] ?? $headers['X-CSRF-TOKEN'] ?? $headers['x-csrf-token'] ?? '';
        $fromPost   = $_POST['_token'] ?? '';
        $token      = $fromHeader !== '' ? $fromHeader : $fromPost;
        $expected   = $_SESSION['csrf_token'] ?? '';

        if ($expected === '' || !hash_equals($expected, $token)) {
            ob_clean();
            http_response_code(403);
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                   || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
                   || $fromHeader !== '';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Sesi tidak valid. Muat ulang halaman dan coba lagi.']);
            } else {
                echo '<h1>403 Forbidden</h1><p>CSRF token tidak valid. Silakan kembali dan coba lagi.</p>';
            }
            exit;
        }
    }

    protected function checkAccess($allowedRoles = []) {
        // 1. Cek Session
        if (session_status() == PHP_SESSION_NONE) session_start();

        // 2. Cek Login
        if (!isset($_SESSION['user_id'])) {
            // [BARU] Simpan URL yang sedang dikunjungi sebelum redirect ke login.
            // Digunakan oleh AuthController::login() untuk mengembalikan user setelah
            // login berhasil — khususnya untuk deep-link QR dari Google Lens.
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            if ($requestUri && strpos($requestUri, '/auth/login') === false) {
                $_SESSION['redirect_after_login'] = (isset($_SERVER['HTTPS'], $_SERVER['HTTP_HOST'])
                    ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '')
                    . $requestUri;
            }
            header("Location: " . BASE_URL . "/auth/login");
            exit;
        }

        // 3. [Item 1] Validasi CSRF untuk semua request POST dari sesi web
        //    (bukan API — API menggunakan JWT Bearer token, tidak lewat checkAccess)
        $this->validateCsrf();

        // 3. Cek Role
        if (!empty($allowedRoles)) {
            $userRole = trim((string)($_SESSION['role'] ?? ''));
            if (strcasecmp($userRole, 'asisten') === 0) {
                $userRole = 'User';
            }
            if (strcasecmp($userRole, 'laboran') === 0) {
                $userRole = 'Admin';
            }

            $allowedLower = array_map('strtolower', $allowedRoles);
            if (!in_array(strtolower($userRole), $allowedLower)) {
                require_once '../app/controllers/ErrorController.php';
                $error = new ErrorController();
                $error->forbidden();
                exit;
            }
        }
    }

    /**
     * [Item 4] Validasi MIME type upload dari magic bytes file (bukan ekstensi nama).
     * Ekstensi bisa dipalsukan; finfo/getimagesize membaca isi file sesungguhnya.
     *
     * @param  string $tmpPath  Path file sementara ($_FILES['x']['tmp_name'])
     * @param  array  $allowed  Daftar MIME type yang diizinkan
     * @return bool
     */
    protected function validateImageMime(string $tmpPath, array $allowed = ['image/jpeg','image/png','image/webp']): bool
    {
        if (!is_uploaded_file($tmpPath)) return false;
        if (function_exists('finfo_open')) {
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);
        } else {
            // Fallback untuk server tanpa ext-fileinfo
            $info = @getimagesize($tmpPath);
            $mimeType = $info['mime'] ?? '';
        }
        return in_array($mimeType, $allowed, true);
    }
}
?>