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

        // Get raw data
        $data = json_decode(file_get_contents("php://input"), true);

        // Validate input
        if (empty($data['email']) || empty($data['password'])) {
            ApiResponse::error('Email and password required', 400, [
                'email' => empty($data['email']) ? 'Email is required' : null,
                'password' => empty($data['password']) ? 'Password is required' : null
            ]);
        }

        // Query user
        $query = "SELECT u.id_user, u.email, u.password, u.role, u.created_at,
                         p.id_profil, p.nama, p.photo_profile, p.is_completed, p.id_lab
                  FROM {$this->table_user} u
                  JOIN {$this->table_profile} p ON u.id_user = p.id_user
                  WHERE u.email = :email AND u.role = 'User'";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':email' => $data['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            ApiResponse::error('Email not found or not a User role', 401);
        }

        // Verify password
        if (!password_verify($data['password'], $user['password'])) {
            ApiResponse::error('Invalid password', 401);
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

    /**
     * POST /api/auth/refresh
     * Request body: { "token": "old_jwt_token" }
     */
    public function refresh() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::error('Method not allowed', 405);
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['token'])) {
            ApiResponse::error('Token required', 400);
        }

        $payload = JwtHandler::verifyToken($data['token']);

        if (!$payload) {
            ApiResponse::error('Invalid or expired token', 401);
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

        return $payload;
    }
}
?>