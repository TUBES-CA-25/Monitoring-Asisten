<?php
require_once '../app/core/ApiResponse.php';
require_once '../app/api/AuthApi.php';
class UserApi {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * GET /api/user/profile
     */
    public function profile() {
        $payload = AuthApi::validateToken();
        $userId = $payload['user_id'];

        $query = "SELECT 
                    u.id_user,
                    u.email,
                    u.role,
                    u.created_at,
                    p.id_profil,
                    p.nama,
                    p.nim,
                    p.kelas,
                    p.prodi,
                    p.jabatan,
                    p.no_telp,
                    p.alamat,
                    p.jenis_kelamin,
                    p.peminatan,
                    p.photo_profile,
                    p.is_completed,
                    l.id_lab,
                    l.nama_lab as lab_name
                  FROM user u
                  JOIN profile p ON u.id_user = p.id_user
                  LEFT JOIN lab l ON p.id_lab = l.id_lab
                  WHERE u.id_user = :uid";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':uid' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            ApiResponse::error('User not found', 404);
        }

        ApiResponse::success($user, 'User profile', 200);
    }

    /**
     * PUT /api/user/profile/update
     * Request body: {
     *   "nama": "...",
     *   "no_telp": "...",
     *   "alamat": "...",
     *   "jenis_kelamin": "...",
     *   "peminatan": "..."
     * }
     */
    /**
     * POST /api/user/update
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::error('Method not allowed', 405);
        }
        
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        // 1. Ambil data dari $_POST (bisa menerima 'name' / 'nama' dari Flutter, serta peminatan & no_telp)
        $nama = $_POST['name'] ?? $_POST['nama'] ?? '';
        $peminatan = $_POST['peminatan'] ?? '';
        $no_telp = $_POST['no_telp'] ?? '';

        if (empty($nama)) {
            ApiResponse::error('Nama tidak boleh kosong', 400);
        }

        $photoName = null;

        // 2. Logika Upload Foto menggunakan UPLOAD_PATH (cross-platform, aman dari perubahan CWD)
        if (isset($_FILES['photo_profile']) && $_FILES['photo_profile']['error'] === 0) {
            $targetDir = UPLOAD_PATH . 'profile/';
            
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $fileExtension = pathinfo($_FILES['photo_profile']['name'], PATHINFO_EXTENSION);
            // Nama file unik berdasarkan profile ID dan timestamp
            $baseName = "profile_" . $profilId . "_" . time();

            $allowTypes = ['jpg', 'png', 'jpeg'];
            if (in_array(strtolower($fileExtension), $allowTypes)) {
                // [BARU] Konversi otomatis ke WebP (poin 1) - fallback ke
                // ekstensi asli kalau GD/WebP tidak tersedia.
                $photoName = ImageHelper::convertUploadToWebp($_FILES['photo_profile']['tmp_name'], $targetDir, $baseName);
                if (!$photoName) {
                    $photoName = $baseName . '.' . $fileExtension;
                    if (!move_uploaded_file($_FILES['photo_profile']['tmp_name'], $targetDir . $photoName)) {
                        ApiResponse::error('Gagal mengupload file ke server', 500);
                    }
                }
            } else {
                ApiResponse::error('Format file tidak didukung', 400);
            }
        }

        // 3. Query Update Dinamis agar update 'peminatan' & 'no_telp' ikut tersimpan,
        // serta tidak merusak status 'id_lab' asisten jika tidak dikirim.
        try {
            $fields = [
                'nama = :nama',
                'no_telp = :no_telp',
                'peminatan = :peminatan',
                'is_completed = 1'
            ];
            $params = [
                ':nama' => $nama,
                ':no_telp' => $no_telp,
                ':peminatan' => $peminatan,
                ':pid' => $profilId
            ];

            if (isset($_POST['id_lab'])) {
                $fields[] = 'id_lab = :lab';
                $params[':lab'] = $_POST['id_lab'];
            }

            if ($photoName) {
                $fields[] = 'photo_profile = :photo';
                $params[':photo'] = $photoName;
            }

            $query = "UPDATE profile SET " . implode(', ', $fields) . " WHERE id_profil = :pid";

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);

            // 4. Ambil data profil lengkap pasca-update agar responnya sesuai skema ProfileEntity di Flutter BLoC
            $selectQuery = "SELECT 
                        u.id_user,
                        u.email,
                        u.role,
                        u.created_at,
                        p.id_profil,
                        p.nama,
                        p.nim,
                        p.kelas,
                        p.prodi,
                        p.jabatan,
                        p.no_telp,
                        p.alamat,
                        p.jenis_kelamin,
                        p.peminatan,
                        p.photo_profile,
                        p.is_completed,
                        l.id_lab,
                        l.nama_lab as lab_name
                      FROM user u
                      JOIN profile p ON u.id_user = p.id_user
                      LEFT JOIN lab l ON p.id_lab = l.id_lab
                      WHERE p.id_profil = :pid";

            $selectStmt = $this->conn->prepare($selectQuery);
            $selectStmt->execute([':pid' => $profilId]);
            $updatedUser = $selectStmt->fetch(PDO::FETCH_ASSOC);

            if (!$updatedUser) {
                ApiResponse::error('Gagal mengambil data profil terbaru', 500);
            }

            ApiResponse::success($updatedUser, 'Profil berhasil diperbarui', 200);
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/user/change-password
     * Request body: {
     * "old_password": "...",
     * "new_password": "...",
     * "new_password_confirmation": "..."
     * }
     */
    public function changePassword() {
        // 1. Validasi Method Wajib POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::error('Method not allowed', 405);
        }

        // 2. Validasi Token JWT (Memastikan asisten sudah login)
        $payload = AuthApi::validateToken();
        $userId = $payload['user_id'];

        // 3. Ambil Input Data JSON dari Flutter ChangePasswordCubit
        $input = json_decode(file_get_contents('php://input'), true);
        $oldPassword = $input['old_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';
        $confirmPassword = $input['new_password_confirmation'] ?? '';

        // 4. Validasi Input Kosong
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            ApiResponse::error('Semua field password wajib diisi, Bro!', 400);
        }

        if ($newPassword !== $confirmPassword) {
            ApiResponse::error('Konfirmasi password baru tidak cocok!', 400);
        }

        try {
            // 5. Ambil Password Lama dari Database untuk Dicocokkan
            $query = "SELECT password FROM user WHERE id_user = :uid";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':uid' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                ApiResponse::error('User tidak ditemukan!', 404);
            }

            if (!password_verify($oldPassword, $user['password'])) {
                ApiResponse::error('Password lama yang Anda masukkan salah!', 400);
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            
            $updateQuery = "UPDATE user SET password = :pwd WHERE id_user = :uid";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->execute([
                ':pwd' => $hashedPassword,
                ':uid' => $userId
            ]);

            ApiResponse::success(null, 'Password akun asisten berhasil diperbarui, Bro!', 200);

        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }
}
?>