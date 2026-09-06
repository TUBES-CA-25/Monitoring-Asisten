<?php
date_default_timezone_set('Asia/Makassar');
require_once '../app/core/ApiResponse.php';
require_once '../app/api/AuthApi.php';
require_once __DIR__ . '/../services/AttendanceAutoService.php';

class QrApi {
    private $conn;
    private $table_qr = 'qr_code';
    private $table_presensi = 'presensi';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * GET /api/qr/generate
     */
    public function generate() {
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        $type = isset($_GET['type']) ? $_GET['type'] : 'Presensi';
        $validity = isset($_GET['validity']) ? (int)$_GET['validity'] : 3600; 

        if (!in_array($type, ['Presensi', 'Pulang'])) {
            ApiResponse::error('Type hanya Presensi atau Pulang', 400);
        }

        try {
            $token = bin2hex(random_bytes(32));
            $generatedAt = date('Y-m-d H:i:s');
            $validUntil = date('Y-m-d H:i:s', time() + $validity);

            $query = "INSERT INTO {$this->table_qr} (tipe, token_code, generated_at, valid_until)
                     VALUES (:tipe, :token, :gen_at, :valid_until)";

            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':tipe' => $type,
                ':token' => $token,
                ':gen_at' => $generatedAt,
                ':valid_until' => $validUntil
            ]);

            if ($result) {
                $responseData = [
                    'id_qr' => $this->conn->lastInsertId(),
                    'type' => $type,
                    'token' => $token,
                    'generated_at' => $generatedAt,
                    'valid_until' => $validUntil,
                    'validity_seconds' => $validity,
                    'qr_string' => $token
                ];
                ApiResponse::success($responseData, 'QR code generated successfully', 201);
            } else {
                ApiResponse::error('Failed to generate QR code', 500);
            }
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/qr/scan
     */
    public function scan() {
        ob_start(); 

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            ApiResponse::error('Method not allowed', 405);
            return;
        }

        try {
            $payload = AuthApi::validateToken();
            $profilId = $payload['profil_id'];

            $rawToken = $_POST['token'] ?? '';
            $latitude = trim($_POST['latitude'] ?? '0.0');
            $longitude = trim($_POST['longitude'] ?? '0.0');

            $input = json_decode(file_get_contents("php://input"), true) ?? $_POST;
            $rawToken = $input['token'] ?? '';

            if (strpos($rawToken, '{') !== false) {
                $decoded = json_decode($rawToken, true);
                $tokenFinal = $decoded['token'] ?? $rawToken;
            } else {
                $tokenFinal = $rawToken;
            }
            $tokenFinal = trim($tokenFinal);

            if (empty($tokenFinal)) {
                ob_end_clean();
                ApiResponse::error("Token QR tidak ditemukan", 400);
                return;
            }

            $now = date('Y-m-d H:i:s');
            
            $query = "SELECT id_qr, tipe, valid_until FROM {$this->table_qr} 
                    WHERE token_code = :token AND valid_until >= :sekarang 
                    LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':token' => $tokenFinal, ':sekarang' => $now]);
            $qrCode = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$qrCode) {
                ob_end_clean(); 
                ApiResponse::error("QR Code tidak valid atau sudah expired", 401);
                return;
            }

            $today = date('Y-m-d');
            $currentTime = date('H:i:s');

            $photoName = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $photoName = $this->processAndWatermarkImage($_FILES['image'], $latitude, $longitude);
            } else {
                ob_end_clean();
                ApiResponse::error("Foto bukti presensi wajib diunggah", 400);
                return;
            }

            ob_end_clean(); 

            if ($qrCode['tipe'] === 'Presensi') {
                $this->handleCheckIn($profilId, $today, $currentTime, $photoName);
            } else {
                $this->handleCheckOut($profilId, $today, $currentTime, $photoName);
            }

        } catch (Exception $e) {
            if (ob_get_length()) ob_end_clean();
            ApiResponse::error('System Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * HELPER: Pengolah Gambar & Watermark Geotagging Native PHP
     * Menggunakan konstanta UPLOAD_PATH (app/config/config.php) yang
     * dihitung otomatis dari root project, sehingga bekerja di
     * Windows maupun macOS/Linux tanpa perlu mengubah path manual.
     */
    private function processAndWatermarkImage($fileInfo, $lat, $lon) {

        $targetDir = UPLOAD_PATH . 'attendance/';

        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true); 
        }

        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        $fileName = $profilId . '_' . time() . '.png';
        $targetFile = $targetDir . $fileName;

        $image = @imagecreatefromjpeg($fileInfo['tmp_name']);
        if (!$image) $image = @imagecreatefrompng($fileInfo['tmp_name']);

        if ($image) {
            $imgWidth  = imagesx($image);
            $imgHeight = imagesy($image);

            $waktu = date('D, d M Y | H:i:s') . " WITA";
            $gps = "GPS: " . $lat . ", " . $lon;
            $text = $waktu . " | " . $gps;

            $white = imagecolorallocate($image, 255, 255, 255);
            $darkBlue = imagecolorallocate($image, 20, 50, 120);
            $black = imagecolorallocate($image, 0, 0, 0);

            imagestring($image, 5, 15, imagesy($image) - 30, $text, $black);
            imagestring($image, 5, 14, imagesy($image) - 31, $text, $white);

            $boxHeight = 50;
            $x1 = 0;
            $y1 = $imgHeight - $boxHeight;
            $x2 = $imgWidth;
            $y2 = $imgHeight;

            imagefilledrectangle($image, $x1, $y1, $x2, $y2, $white);

            $textX = 20; 
            $textY = $imgHeight - 33;

            imagestring($image, 5, $textX, $textY, $text, $darkBlue);
            imagestring($image, 5, $textX + 1, $textY, $text, $darkBlue);

            if (!imagepng($image, $targetFile)) {
                imagedestroy($image);
                throw new Exception("Gagal menulis file PNG ke folder fisik htdocs. Cek kembali permission folder.");
            }
            
            imagedestroy($image);
            return $fileName;
        }

        if (!move_uploaded_file($fileInfo['tmp_name'], $targetFile)) {
            throw new Exception("Gagal memindahkan file upload melalui fungsi move_uploaded_file.");
        }
        
        return $fileName;
    }

    /**
     * POST /api/qr/validate
     */
    public function validate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::error('Method not allowed', 405);
            return;
        }

        try {
            $payload = AuthApi::validateToken();
            $profilId = $payload['profil_id'];

            $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
            $rawToken = $data['token'] ?? '';
            $now = date('Y-m-d H:i:s');

            if (strpos($rawToken, '{') !== false) {
                $decoded = json_decode($rawToken, true);
                $tokenFinal = $decoded['token'] ?? $rawToken;
            } else {
                $tokenFinal = $rawToken;
            }
            $tokenFinal = trim($tokenFinal);

            if (empty($tokenFinal)) {
                ApiResponse::error('Token required', 400);
                return;
            }

            $query = "SELECT id_qr, tipe, valid_until FROM {$this->table_qr}
                     WHERE token_code = :token AND valid_until >= :sekarang
                     LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([':token' => $tokenFinal, ':sekarang' => $now]);
            $qrCode = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$qrCode) {
                ApiResponse::error('QR Code tidak valid atau sudah expired', 404);
                return;
            }

            $today = date('Y-m-d');
            $checkQuery = "SELECT id_presensi, waktu_presensi, waktu_pulang FROM {$this->table_presensi} 
                          WHERE id_profil = :pid AND tanggal = :date LIMIT 1";
            $stmtCheck = $this->conn->prepare($checkQuery);
            $stmtCheck->execute([':pid' => $profilId, ':date' => $today]);
            $userAttendance = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($qrCode['tipe'] === 'Presensi') {
                if ($userAttendance) {
                    ApiResponse::error('Anda sudah melakukan check-in hari ini', 409);
                    return;
                }
            } else if ($qrCode['tipe'] === 'Pulang') {
                // Kasus Scan Pulang
                if (!$userAttendance) {
                    ApiResponse::error('Anda belum melakukan check-in masuk hari ini', 404);
                    return;
                }
                if ($userAttendance['waktu_pulang'] !== null) {
                    ApiResponse::error('Anda sudah melakukan check-out pulang hari ini', 409);
                    return;
                }
            }

            ApiResponse::success([
                'id_qr' => $qrCode['id_qr'],
                'type' => $qrCode['tipe'],
                'valid_until' => $qrCode['valid_until'],
                'is_valid' => true
            ], 'Token valid dan siap melakukan pemotretan absensi', 200);

        } catch (Exception $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    private function handleCheckIn($profilId, $today, $currentTime, $photoName = null) {
        $checkQuery = "SELECT id_presensi FROM {$this->table_presensi} 
                    WHERE id_profil = :pid AND tanggal = :date";
        $stmt = $this->conn->prepare($checkQuery);
        $stmt->execute([':pid' => $profilId, ':date' => $today]);

        if ($stmt->fetch()) {
            ApiResponse::error('Sudah check-in hari ini', 409);
            return;
        }

        try {
            // [BARU] Hitung status (Hadir/Terlambat) & menit terlambat secara
            // otomatis sesuai aturan jam masuk (default 08:00, atau jadwal
            // asisten hari ini jika lebih pagi) — Modul 1 V3. Konsisten dengan
            // AttendanceModel::clockIn() & AttendanceApi::clockIn().
            $autoService = new AttendanceAutoService();
            $eval = $autoService->evaluateCheckIn($profilId, $today, $currentTime);

            $insertQuery = "INSERT INTO {$this->table_presensi}
                        (id_profil, tanggal, waktu_presensi, foto_presensi, status, late_minutes)
                        VALUES (:pid, :date, :time, :foto, :status, :late)";

            $stmt = $this->conn->prepare($insertQuery);
            $stmt->bindValue(':pid', $profilId, PDO::PARAM_INT);
            $stmt->bindValue(':date', $today);
            $stmt->bindValue(':time', $currentTime);
            $stmt->bindValue(':foto', $photoName);
            $stmt->bindValue(':status', $eval['attendance_status']);
            $stmt->bindValue(':late', $eval['late_minutes'], PDO::PARAM_INT);

            if ($stmt->execute()) {
                ApiResponse::success([
                    'id_presensi' => (string)$this->conn->lastInsertId(),
                    'tanggal' => $today,
                    'waktu_presensi' => $currentTime,
                    'status' => $eval['attendance_status'],
                    // [BARU] Field tambahan bersifat ADDITIVE.
                    'late_minutes' => $eval['late_minutes']
                ], 'Check-in berhasil via QR code', 201);
            }
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    private function handleCheckOut($profilId, $today, $currentTime, $photoName = null) {
    $detailAktivitas = isset($_POST['notes']) ? trim($_POST['notes']) : '-';

    $checkQuery = "SELECT id_presensi, waktu_presensi, waktu_pulang FROM {$this->table_presensi}
                  WHERE id_profil = :pid AND tanggal = :date";
    $stmt = $this->conn->prepare($checkQuery);
    $stmt->execute([':pid' => $profilId, ':date' => $today]);
    $presensi = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$presensi) {
        ApiResponse::error('Belum check-in hari ini', 404);
        return;
    }

    if ($presensi['waktu_pulang'] !== null) {
        ApiResponse::error('Sudah check-out hari ini', 409);
        return;
    }

        try {
            $this->conn->beginTransaction();

            // [BARU] Hitung durasi kerja (menit) & flag "pulang lebih awal"
            // (informasi saja, tidak memblokir check-out) — Modul 1 V3.
            // Konsisten dengan AttendanceModel::clockOut() & AttendanceApi::clockOut().
            $autoService = new AttendanceAutoService();
            $workDuration = $autoService->calculateWorkDuration($today, $presensi['waktu_presensi'], $currentTime);
            $isEarly = $autoService->isEarlyCheckout($currentTime);

            // 1. UPDATE data kepulangan di tabel presensi (Waktu Pulang & Nama Foto Pulang)
            $updatePresensi = "UPDATE {$this->table_presensi}
                            SET waktu_pulang = :time, foto_pulang = :foto, work_duration = :duration
                            WHERE id_presensi = :id";
            $stmtP = $this->conn->prepare($updatePresensi);
            $stmtP->execute([
                ':id'    => $presensi['id_presensi'],
                ':time'  => $currentTime,
                ':foto'  => $photoName,
                ':duration' => $workDuration
            ]);

            // 2. PERBAIKAN: Ganti UPDATE menjadi INSERT INTO di tabel logbook
            // Karena baris logbook asisten untuk hari ini memang belum ada, jadi harus kita ciptakan baru!
            $insertLogbook = "INSERT INTO logbook (id_profil, id_presensi, detail_aktivitas, is_verified) 
                            VALUES (:id_profil, :id_presensi, :detail, 0)";
            $stmtL = $this->conn->prepare($insertLogbook);
            $stmtL->execute([
                ':id_profil'  => $profilId,
                ':id_presensi'=> $presensi['id_presensi'],
                ':detail'     => $detailAktivitas
            ]);

            $this->conn->commit();

            ApiResponse::success([
                'id_presensi'      => (string)$presensi['id_presensi'],
                'waktu_pulang'     => $currentTime,
                'foto_pulang'      => $photoName,
                'detail_aktivitas' => $detailAktivitas,
                'status'           => 'Pulang',
                // [BARU] Field tambahan bersifat ADDITIVE.
                'work_duration'    => $workDuration,
                'is_early_checkout'=> $isEarly
            ], 'Absen pulang & Logbook berhasil disimpan ke database!', 200);

        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            ApiResponse::error('Gagal simpan data ke database: ' . $e->getMessage(), 500);
        }
    }
}

