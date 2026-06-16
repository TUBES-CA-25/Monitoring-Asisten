<?php
require_once '../app/core/ApiResponse.php';
require_once '../app/api/AuthApi.php';

class LeaveApi {
    private $conn;
    private $uploadDir;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
        
        // Menggunakan path absolut agar lebih aman di server mana pun
        $this->uploadDir = dirname(__DIR__, 2) . "/public/uploads/leaves/";
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    public function submit() {
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        $tipe = $_POST['tipe'] ?? 'Izin';
        $startDate = $_POST['start_date'] ?? null;
        $endDate = $_POST['end_date'] ?? null;
        $deskripsi = $_POST['deskripsi'] ?? null;

        try {
            $fileName = null;
            if (isset($_FILES['file_bukti']) && $_FILES['file_bukti']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['file_bukti'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                // VALIDASI KEAMANAN: Cek apakah benar gambar
                $allowed = ['jpg', 'jpeg', 'png'];
                if (!in_array($ext, $allowed)) {
                    ApiResponse::error("Format file tidak diizinkan (Hanya JPG/PNG)", 400);
                    return;
                }

                // Ganti nama file menjadi unik (Inject-Proof)
                $fileName = "izin_" . $profilId . "_" . time() . ".jpg"; 
                $targetFile = $this->uploadDir . $fileName;

                if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
                    ApiResponse::error("Gagal menyimpan file di server", 500);
                    return;
                }
            }

            // Simpan ke database
            $query = "INSERT INTO izin (id_profil, tipe, start_date, end_date, deskripsi, file_bukti, status_approval) 
                      VALUES (:pid, :tipe, :start, :end, :desc, :file, 'Pending')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':pid' => $profilId,
                ':tipe' => $tipe,
                ':start' => $startDate,
                ':end' => $endDate,
                ':desc' => $deskripsi,
                ':file' => $fileName 
            ]);

            ApiResponse::success(null, 'Pengajuan izin berhasil dikirim', 201);
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }
}