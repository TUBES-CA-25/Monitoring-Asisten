<?php
require_once '../app/core/ApiResponse.php';
class AuthApi {
    private $conn;
    private $table_user = 'user';
    private $table_profile = 'profile';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * POST /api/auth/login
     * Request body: { "email": "user@email.com", "password": "password" }
     */
    public function login() {
        // Validate request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::error('Method not allowed', 405);
        }

        // Rate limit berbasis IP (SEC-06)
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $rateLimitFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'api_login_rl_' . md5($clientIp) . '.json';
        $rateLimitData = ['attempts' => 0, 'lockout_until' => 0];

        if (file_exists($rateLimitFile)) {
            $content = @file_get_contents($rateLimitFile);
            if ($content) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $rateLimitData = $decoded;
                }
            }
        }

        $now = time();
        if (($rateLimitData['lockout_until'] ?? 0) > $now) {
            $remaining = $rateLimitData['lockout_until'] - $now;
            ApiResponse::error("Terlalu banyak percobaan login yang gagal. Silakan tunggu {$remaining} detik.", 429);
        }

        // Get raw data
        $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;

        // Validate input
        if (empty($data['email']) || empty($data['password'])) {
            ApiResponse::error('Email and password required', 400, [
                'email' => empty($data['email']) ? 'Email is required' : null,
                'password' => empty($data['password']) ? 'Password is required' : null
            ]);
        }

        // Query user (LOGIC-02: cek status_account)
        $query = "SELECT u.id_user, u.email, u.password, u.role, u.created_at, u.status_account,
                         p.id_profil, p.nama, p.photo_profile, p.is_completed, p.id_lab
                  FROM {$this->table_user} u
                  JOIN {$this->table_profile} p ON u.id_user = p.id_user
                  WHERE u.email = :email AND u.role = 'User'";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':email' => $data['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $this->recordFailedLogin($rateLimitFile, $rateLimitData);
            ApiResponse::error('Email not found or not a User role', 401);
        }

        // Cek status keaktifan akun
        if (($user['status_account'] ?? 'ACTIVE') === 'INACTIVE') {
            ApiResponse::error('Akun Anda dinonaktifkan. Hubungi Administrator.', 403);
        }

        // Verify password
        if (!password_verify($data['password'], $user['password'])) {
            $this->recordFailedLogin($rateLimitFile, $rateLimitData);
            ApiResponse::error('Invalid password', 401);
        }

        // Reset rate limit jika berhasil login
        if (file_exists($rateLimitFile)) {
            @unlink($rateLimitFile);
        }

        // Generate JWT Token
        $token = JwtHandler::generateToken([
            'user_id' => $user['id_user'],
            'profil_id' => $user['id_profil'],
            'email' => $user['email'],
            'role' => $user['role'],
            'name' => $user['nama']
        ]);

        $responseData = [
            'token' => $token,
            'user' => [
                'id_user' => $user['id_user'],
                'id_profil' => $user['id_profil'],
                'email' => $user['email'],
                'nama' => $user['nama'],
                'role' => $user['role'],
                'photo_profile' => $user['photo_profile'],
                'is_completed' => $user['is_completed'],
                'lab_id' => $user['id_lab']
            ]
        ];

        ApiResponse::success($responseData, 'Login successful', 200);
    }

    private function recordFailedLogin($file, $data) {
        $attempts = ($data['attempts'] ?? 0) + 1;
        $lockout = 0;
        if ($attempts >= 5) {
            // Kunci selama 60 detik jika salah 5 kali
            $lockout = time() + 60;
            $attempts = 0;
        }
        @file_put_contents($file, json_encode([
            'attempts' => $attempts,
            'lockout_until' => $lockout
        ]));
    }

    /**
     * POST /api/auth/refresh
     * Request body: { "token": "old_jwt_token" }
     */
    public function refresh() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::error('Method not allowed', 405);
        }

        $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;

        if (empty($data['token'])) {
            ApiResponse::error('Token required', 400);
        }

        $payload = JwtHandler::verifyToken($data['token']);

        if (!$payload) {
            ApiResponse::error('Invalid or expired token', 401);
        }

        // Cek status keaktifan akun saat refresh token
        $userId = $payload['user_id'] ?? null;
        if ($userId) {
            $stmt = $this->conn->prepare("SELECT status_account FROM {$this->table_user} WHERE id_user = :uid LIMIT 1");
            $stmt->execute([':uid' => $userId]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$u || ($u['status_account'] ?? 'ACTIVE') === 'INACTIVE') {
                ApiResponse::error('Akun Anda dinonaktifkan.', 403);
            }
        }

        // Generate new token
        $newToken = JwtHandler::generateToken([
            'user_id' => $payload['user_id'],
            'profil_id' => $payload['profil_id'],
            'email' => $payload['email'],
            'role' => $payload['role'],
            'name' => $payload['name']
        ]);

        ApiResponse::success(['token' => $newToken], 'Token refreshed', 200);
    }

    private static $userStatusCache = [];

    /**
     * Helper: Validate Token & Return Payload
     */
    public static function validateToken() {
        $token = JwtHandler::getBearerToken();

        if (!$token) {
            ApiResponse::error('Authorization token required', 401);
        }

        $payload = JwtHandler::verifyToken($token);

        if (!$payload) {
            ApiResponse::error('Invalid or expired token', 401);
        }

        // Verifikasi keaktifan akun pengguna
        $userId = $payload['user_id'] ?? null;
        if ($userId) {
            if (!isset(self::$userStatusCache[$userId])) {
                try {
                    $db = new Database();
                    $conn = $db->getConnection();
                    $stmt = $conn->prepare("SELECT status_account FROM user WHERE id_user = :uid LIMIT 1");
                    $stmt->execute([':uid' => $userId]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    self::$userStatusCache[$userId] = $row ? ($row['status_account'] ?? 'ACTIVE') : 'INACTIVE';
                } catch (\Throwable $e) {
                    self::$userStatusCache[$userId] = 'ACTIVE';
                }
            }

            if (self::$userStatusCache[$userId] === 'INACTIVE') {
                ApiResponse::error('Akun Anda telah dinonaktifkan. Hubungi Administrator.', 403);
            }
        }

        return $payload;
    }
}
?>