<?php
require_once __DIR__ . '/../services/AttendanceAutoService.php';

class AttendanceApi {
    private $conn;
    private $table_presensi = 'presensi';
    private $table_profile = 'profile';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * POST /api/attendance/clock-in
     * Request: multipart/form-data
     *   - token (Authorization header)
     *   - foto_presensi (image file)
     */
    public function clockIn() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::error('Method not allowed', 405);
            return;
        }

        // Validate token
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];
        $userId   = $payload['user_id'] ?? null;

        // Wajibkan input token QR dan validasi (SEC-02)
        $qrToken = trim($_POST['qr_token'] ?? $_POST['token'] ?? '');
        if (empty($qrToken)) {
            ApiResponse::error('Token QR wajib disertakan.', 400);
            return;
        }

        require_once __DIR__ . '/../models/QrModel.php';
        $qrModel = new QrModel();
        if (!$qrModel->validateToken($qrToken, 'check_in')) {
            ApiResponse::error('Token QR tidak valid, sudah kadaluarsa, atau telah digunakan.', 400);
            return;
        }

        // Validate image upload
        if (!isset($_FILES['foto_presensi']) || $_FILES['foto_presensi']['error'] !== UPLOAD_ERR_OK) {
            ApiResponse::error('Photo is required', 400, ['foto_presensi' => 'Upload a valid image']);
            return;
        }

        $ext = strtolower(pathinfo($_FILES['foto_presensi']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowedExts, true)) {
            ApiResponse::error('Format file tidak diizinkan. Hanya JPG dan PNG yang diperbolehkan.', 400);
            return;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['foto_presensi']['tmp_name']);
        finfo_close($finfo);
        $allowedMimes = ['image/jpeg', 'image/png'];
        if (!in_array($mime, $allowedMimes, true)) {
            ApiResponse::error('Konten file gambar tidak sesuai dengan ekstensinya.', 400);
            return;
        }

        if ($_FILES['foto_presensi']['size'] > 5 * 1024 * 1024) {
            ApiResponse::error('Ukuran foto maksimal 5MB.', 400);
            return;
        }

        // Check if already checked in today
        $today = date('Y-m-d');
        $checkQuery = "SELECT id_presensi FROM {$this->table_presensi} 
                      WHERE id_profil = :pid AND tanggal = :date";
        $stmt = $this->conn->prepare($checkQuery);
        $stmt->execute([':pid' => $profilId, ':date' => $today]);

        if ($stmt->fetch()) {
            ApiResponse::error('Already checked in today', 409, ['message' => 'You already checked in today']);
            return;
        }

        // Handle file upload
        $uploadDir = UPLOAD_PATH . 'attendance/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = 'checkin_' . $profilId . '_' . time() . '.' . ($ext === 'png' ? 'png' : 'jpg');
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($_FILES['foto_presensi']['tmp_name'], $filePath)) {
            ApiResponse::error('Failed to upload photo', 500);
            return;
        }

        // Insert presensi record
        try {
            // [BARU] Hitung status (Hadir/Terlambat) & menit terlambat secara
            // otomatis sesuai aturan jam masuk (default 08:00, atau jadwal
            // asisten hari ini jika lebih pagi) — Modul 1 V3. Konsisten dengan
            // AttendanceModel::clockIn() yang dipakai versi web.
            $checkInTime = date('H:i:s');
            $autoService = new AttendanceAutoService();
            $eval = $autoService->evaluateCheckIn($profilId, $today, $checkInTime);

            $insertQuery = "INSERT INTO {$this->table_presensi} 
                           (id_profil, tanggal, waktu_presensi, foto_presensi, status, late_minutes)
                           VALUES (:pid, :date, :time, :foto, :status, :late)";

            $stmt = $this->conn->prepare($insertQuery);
            $result = $stmt->execute([
                ':pid' => $profilId,
                ':date' => $today,
                ':time' => $checkInTime,
                ':foto' => $fileName,
                ':status' => $eval['attendance_status'],
                ':late' => $eval['late_minutes']
            ]);

            if ($result) {
                // Tandai QR token sebagai telah digunakan (Single-Use)
                $qrModel->markTokenUsed($qrToken, $userId);

                $responseData = [
                    'id_presensi' => $this->conn->lastInsertId(),
                    'tanggal' => $today,
                    'waktu_presensi' => $checkInTime,
                    'foto_presensi' => $fileName,
                    'status' => $eval['attendance_status'],
                    // [BARU] Field tambahan bersifat ADDITIVE.
                    'late_minutes' => $eval['late_minutes']
                ];
                ApiResponse::success($responseData, 'Check-in successful', 201);
            } else {
                ApiResponse::error('Failed to save check-in', 500);
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                ApiResponse::error('Already checked in today', 409);
                return;
            }
            error_log("ClockIn PDO Error: " . $e->getMessage());
            ApiResponse::error('Gagal menyimpan data presensi', 500);
        } catch (Exception $e) {
            error_log("ClockIn System Error: " . $e->getMessage());
            ApiResponse::error('Terjadi kesalahan sistem', 500);
        }
    }

    /**
     * POST /api/attendance/clock-out
     * Request: multipart/form-data
     *   - token (Authorization header)
     *   - foto_pulang (image file)
     *   - qr_token (optional QR token)
     */
    public function clockOut() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::error('Method not allowed', 405);
            return;
        }

        // Validate token
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];
        $userId   = $payload['user_id'] ?? null;

        // Validate image
        if (!isset($_FILES['foto_pulang']) || $_FILES['foto_pulang']['error'] !== UPLOAD_ERR_OK) {
            ApiResponse::error('Photo is required', 400);
            return;
        }

        $ext = strtolower(pathinfo($_FILES['foto_pulang']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowedExts, true)) {
            ApiResponse::error('Format file tidak diizinkan. Hanya JPG dan PNG yang diperbolehkan.', 400);
            return;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['foto_pulang']['tmp_name']);
        finfo_close($finfo);
        $allowedMimes = ['image/jpeg', 'image/png'];
        if (!in_array($mime, $allowedMimes, true)) {
            ApiResponse::error('Konten file gambar tidak sesuai dengan ekstensinya.', 400);
            return;
        }

        if ($_FILES['foto_pulang']['size'] > 5 * 1024 * 1024) {
            ApiResponse::error('Ukuran foto maksimal 5MB.', 400);
            return;
        }

        // Check if user has checked in today
        $today = date('Y-m-d');
        $checkQuery = "SELECT id_presensi, waktu_pulang, waktu_presensi FROM {$this->table_presensi} 
                      WHERE id_profil = :pid AND tanggal = :date";
        $stmt = $this->conn->prepare($checkQuery);
        $stmt->execute([':pid' => $profilId, ':date' => $today]);
        $presensi = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$presensi) {
            ApiResponse::error('No check-in record found today', 404);
            return;
        }

        if ($presensi['waktu_pulang'] !== null) {
            ApiResponse::error('Already checked out today', 409);
            return;
        }

        // Handle file upload
        $uploadDir = UPLOAD_PATH . 'attendance/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = 'checkout_' . $profilId . '_' . time() . '.' . ($ext === 'png' ? 'png' : 'jpg');
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($_FILES['foto_pulang']['tmp_name'], $filePath)) {
            ApiResponse::error('Failed to upload photo', 500);
            return;
        }

        // Update presensi record
        try {
            // [BARU] Hitung durasi kerja (menit) & flag "pulang lebih awal"
            // (informasi saja, tidak memblokir check-out) — Modul 1 V3.
            // Konsisten dengan AttendanceModel::clockOut() versi web.
            $checkOutTime = date('H:i:s');
            $autoService = new AttendanceAutoService();
            $workDuration = $autoService->calculateWorkDuration($today, $presensi['waktu_presensi'], $checkOutTime);
            $isEarly = $autoService->isEarlyCheckout($checkOutTime);

            $updateQuery = "UPDATE {$this->table_presensi} 
                           SET waktu_pulang = :time, foto_pulang = :foto, work_duration = :duration
                           WHERE id_presensi = :id";

            $stmt = $this->conn->prepare($updateQuery);
            $result = $stmt->execute([
                ':id' => $presensi['id_presensi'],
                ':time' => $checkOutTime,
                ':foto' => $fileName,
                ':duration' => $workDuration
            ]);

            if ($result) {
                // Jika ada QR token pulang, tandai sebagai terpakai
                $qrToken = trim($_POST['qr_token'] ?? $_POST['token'] ?? '');
                if (!empty($qrToken)) {
                    require_once __DIR__ . '/../models/QrModel.php';
                    $qrModel = new QrModel();
                    if ($qrModel->validateToken($qrToken, 'check_out')) {
                        $qrModel->markTokenUsed($qrToken, $userId);
                    }
                }

                $responseData = [
                    'id_presensi' => $presensi['id_presensi'],
                    'waktu_pulang' => $checkOutTime,
                    'foto_pulang' => $fileName,
                    // [BARU] Field tambahan bersifat ADDITIVE.
                    'work_duration' => $workDuration,
                    'is_early_checkout' => $isEarly
                ];
                ApiResponse::success($responseData, 'Check-out successful', 200);
            } else {
                ApiResponse::error('Failed to save check-out', 500);
            }
        } catch (PDOException $e) {
            error_log("ClockOut PDO Error: " . $e->getMessage());
            ApiResponse::error('Gagal menyimpan data kepulangan', 500);
        } catch (Exception $e) {
            error_log("ClockOut System Error: " . $e->getMessage());
            ApiResponse::error('Terjadi kesalahan sistem', 500);
        }
    }

    /**
     * GET /api/attendance/today
     */
    public function today() {
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        $query = "SELECT * FROM {$this->table_presensi} 
                 WHERE id_profil = :pid AND tanggal = CURDATE()
                 ORDER BY waktu_presensi DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':pid' => $profilId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            ApiResponse::success(null, 'No attendance record today', 200);
        }

        ApiResponse::success($result, 'Today attendance', 200);
    }

    /**
     * GET /api/attendance/history?limit=10&offset=0
     */
    public function history() {
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $query = "SELECT * FROM {$this->table_presensi} 
                 WHERE id_profil = :pid
                 ORDER BY tanggal DESC, waktu_presensi DESC
                 LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':pid', $profilId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        ApiResponse::success($records, 'Attendance history', 200);
    }

    /**
     * GET /api/attendance/stats
     */
    public function stats() {
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        // Get total hadir
        $queryHadir = "SELECT COUNT(*) as total FROM {$this->table_presensi} 
                      WHERE id_profil = :pid";
        $stmt = $this->conn->prepare($queryHadir);
        $stmt->execute([':pid' => $profilId]);
        $hadir = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get total izin
        $queryIzin = "SELECT COUNT(*) as total FROM izin 
                     WHERE id_profil = :pid AND status_approval = 'Approved'";
        $stmt = $this->conn->prepare($queryIzin);
        $stmt->execute([':pid' => $profilId]);
        $izin = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get user creation date to calculate alpa
        $queryUser = "SELECT u.created_at FROM user u 
                    JOIN {$this->table_profile} p ON u.id_user = p.id_user
                    WHERE p.id_profil = :pid";
        $stmt = $this->conn->prepare($queryUser);
        $stmt->execute([':pid' => $profilId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Simple alpa calculation (you can improve this later)
        $createdAt = new DateTime($user['created_at']);
        $today = new DateTime();
        $daysDiff = $today->diff($createdAt)->days;
        $alpa = max(0, $daysDiff - $hadir - $izin);

        $stats = [
            'hadir' => (int)$hadir,
            'izin' => (int)$izin,
            'alpa' => (int)$alpa,
            'total_hari_kerja' => $daysDiff
        ];

        ApiResponse::success($stats, 'Attendance statistics', 200);
    }

    /**
     * GET /api/attendance/photo/{id}
     * Get attendance photo by ID
     */
    public function getPhoto($attendanceId = null) {
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        if (!$attendanceId) {
            ApiResponse::error('Attendance ID required', 400);
        }

        $query = "SELECT foto_presensi, foto_pulang, tanggal FROM {$this->table_presensi}
                 WHERE id_presensi = :id AND id_profil = :pid";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id' => $attendanceId, ':pid' => $profilId]);
            $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$attendance) {
                ApiResponse::error('Attendance record not found', 404);
            }

            $baseUrl = UPLOAD_URL . 'attendance/';
            $responseData = [
                'id_presensi' => $attendanceId,
                'tanggal' => $attendance['tanggal'],
                'foto_presensi' => $attendance['foto_presensi'] ? $baseUrl . $attendance['foto_presensi'] : null,
                'foto_pulang' => $attendance['foto_pulang'] ? $baseUrl . $attendance['foto_pulang'] : null
            ];

            ApiResponse::success($responseData, 'Photos retrieved', 200);
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/attendance/search?date_from=&date_to=&status=
     * Search attendance dengan filter
     */
    public function search() {
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $query = "SELECT * FROM {$this->table_presensi} WHERE id_profil = :pid";
        $params = [':pid' => $profilId];

        if ($dateFrom) {
            $query .= " AND tanggal >= :date_from";
            $params[':date_from'] = $dateFrom;
        }

        if ($dateTo) {
            $query .= " AND tanggal <= :date_to";
            $params[':date_to'] = $dateTo;
        }

        if ($status) {
            $query .= " AND status = :status";
            $params[':status'] = $status;
        }

        $query .= " ORDER BY tanggal DESC, waktu_presensi DESC LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            foreach ($params as $key => $value) {
                if ($key !== ':limit' && $key !== ':offset') {
                    $stmt->bindValue($key, $value);
                }
            }

            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            ApiResponse::success($records, 'Attendance search results', 200);
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/attendance/export?format=pdf&month=05&year=2026
     * Export attendance report
     */
    public function exportReport() {
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        $format = isset($_GET['format']) ? $_GET['format'] : 'json';
        $month = isset($_GET['month']) ? $_GET['month'] : date('m');
        $year = isset($_GET['year']) ? $_GET['year'] : date('Y');

        $query = "SELECT 
                    tanggal,
                    waktu_presensi,
                    waktu_pulang,
                    status
                  FROM {$this->table_presensi}
                  WHERE id_profil = :pid
                  AND MONTH(tanggal) = :month
                  AND YEAR(tanggal) = :year
                  ORDER BY tanggal ASC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':pid' => $profilId,
                ':month' => $month,
                ':year' => $year
            ]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($format === 'csv') {
                $this->exportToCSV($records, $month, $year);
            } elseif ($format === 'pdf') {
                ApiResponse::error('PDF export requires additional library', 501, ['message' => 'Use format=csv or format=json']);
            } else {
                // Return JSON for client-side processing or further use
                ApiResponse::success($records, 'Attendance report data', 200);
            }
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Helper function untuk export ke CSV
     */
    private function exportToCSV($records, $month, $year) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="attendance_' . $month . '_' . $year . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Tanggal', 'Waktu Presensi', 'Waktu Pulang', 'Status'], ';', '"', '');

        foreach ($records as $record) {
            fputcsv($output, [
                $record['tanggal'],
                $record['waktu_presensi'] ?? '-',
                $record['waktu_pulang'] ?? '-',
                $record['status']
            ], ';', '"', '');
        }

        fclose($output);
        exit;
    }
}
?>