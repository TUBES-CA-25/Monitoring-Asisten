<?php
require_once '../app/core/ApiResponse.php';
require_once '../app/api/AuthApi.php';
require_once '../app/models/IzinModel.php';

class IzinApi {
    private $conn;
    private $table_izin = 'izin';
    private $table_profile = 'profile';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * GET /api/izin/list?limit=10&offset=0
     * Mengambil semua riwayat pengajuan izin/sakit milik asisten yang sedang login
     */
    public function getlist() {
        // Ambil token JWT yang dikirim dari Flutter, validasi, lalu ekstrak ID Profilnya
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        // Mengatur pagination jika dioper dari Flutter
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        // Query mengambil riwayat izin murni milik asisten ini saja
        $query = "SELECT 
                    id_izin,
                    tipe,
                    start_date,
                    end_date,
                    deskripsi,
                    file_bukti,
                    status_approval,
                    DATEDIFF(end_date, start_date) + 1 as durasi_hari,
                    DATE_FORMAT(start_date, '%d/%m/%Y') as start_date_format,
                    DATE_FORMAT(end_date, '%d/%m/%Y') as end_date_format
                  FROM {$this->table_izin}
                  WHERE id_profil = :pid
                  ORDER BY start_date DESC, id_izin DESC
                  LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':pid', $profilId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $izins = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Hitung total data izin asisten ini untuk pagination di Flutter
            $countQuery = "SELECT COUNT(*) as total FROM {$this->table_izin} WHERE id_profil = :pid";
            $countStmt = $this->conn->prepare($countQuery);
            $countStmt->execute([':pid' => $profilId]);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // 🎯 Bungkus data ke dalam key 'data' agar sesuai dengan mapping IzinCubit di Flutter
            ApiResponse::success([
                'data' => $izins,
                'pagination' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset
                ]
            ], 'Leave list retrieved successfully', 200);
            
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/izin/create
     * Create new leave/izin request
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::error('Method not allowed', 405);
        }

        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        // Check if form data or JSON
        $data = [];
        if (isset($_POST['tipe'])) {
            // Form data with file upload
            $data = $_POST;
        } else {
            // JSON data
            $data = json_decode(file_get_contents("php://input"), true);
        }

        // Validation
        $errors = [];
        if (empty($data['tipe'])) $errors['tipe'] = 'Tipe izin harus dipilih (Izin/Sakit)';
        if (empty($data['start_date'])) $errors['start_date'] = 'Tanggal mulai diperlukan';
        if (empty($data['end_date'])) $errors['end_date'] = 'Tanggal akhir diperlukan';
        if (empty($data['deskripsi'])) $errors['deskripsi'] = 'Deskripsi diperlukan';

        // Validate tipe
        if (!in_array($data['tipe'] ?? '', ['Izin', 'Sakit'])) {
            $errors['tipe'] = 'Tipe izin hanya Izin atau Sakit';
        }

        // Validate dates
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $startDate = strtotime($data['start_date']);
            $endDate = strtotime($data['end_date']);
            if ($startDate > $endDate) {
                $errors['date_range'] = 'Tanggal mulai tidak boleh lebih besar dari tanggal akhir';
            }
        }

        if (!empty($errors)) {
            ApiResponse::error('Validation failed', 400, $errors);
        }

        // Handle file upload
        $filePath = null;
        if (isset($_FILES['file_bukti']) && $_FILES['file_bukti']['error'] === 0) {
            $uploadDir = UPLOAD_PATH . 'leaves/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileExtension = pathinfo($_FILES['file_bukti']['name'], PATHINFO_EXTENSION);
            $fileName = strtolower($data['tipe']) . '_' . $profilId . '_' . time() . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;

            $allowTypes = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
            if (in_array(strtolower($fileExtension), $allowTypes)) {
                if (!move_uploaded_file($_FILES['file_bukti']['tmp_name'], $targetPath)) {
                    ApiResponse::error('Gagal upload file bukti', 500);
                }
                $filePath = $fileName;
            } else {
                ApiResponse::error('Format file tidak didukung', 400, ['file_bukti' => 'Hanya PDF, JPG, PNG, DOC, DOCX']);
            }
        }

        try {
            $query = "INSERT INTO {$this->table_izin}
                     (id_profil, tipe, start_date, end_date, deskripsi, file_bukti, status_approval)
                     VALUES (:pid, :tipe, :start_date, :end_date, :deskripsi, :file_bukti, 'Pending')";

            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':pid' => $profilId,
                ':tipe' => $data['tipe'],
                ':start_date' => $data['start_date'],
                ':end_date' => $data['end_date'],
                ':deskripsi' => $data['deskripsi'],
                ':file_bukti' => $filePath
            ]);

            if ($result) {
                $responseData = [
                    'id_izin' => $this->conn->lastInsertId(),
                    'tipe' => $data['tipe'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'deskripsi' => $data['deskripsi'],
                    'file_bukti' => $filePath,
                    'status_approval' => 'Pending'
                ];
                ApiResponse::success($responseData, 'Izin berhasil diajukan', 201);
            } else {
                ApiResponse::error('Gagal membuat izin', 500);
            }
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/izin/detail/{id}
     * Get leave/izin detail
     */
    public function detail($id = null) {
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        if (!$id) {
            ApiResponse::error('Izin ID required', 400);
        }

        $query = "SELECT 
                    id_izin,
                    tipe,
                    start_date,
                    end_date,
                    deskripsi,
                    file_bukti,
                    status_approval,
                    DATEDIFF(end_date, start_date) + 1 as durasi_hari
                  FROM {$this->table_izin}
                  WHERE id_izin = :id AND id_profil = :pid";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id' => $id, ':pid' => $profilId]);
            $izin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$izin) {
                ApiResponse::error('Izin not found', 404);
            }

            ApiResponse::success($izin, 'Izin detail', 200);
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/izin/update/{id}
     * Update leave/izin request (only if status is Pending)
     */
    public function update($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            ApiResponse::error('Method not allowed', 405);
        }

        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        if (!$id) {
            ApiResponse::error('Izin ID required', 400);
        }

        $data = json_decode(file_get_contents("php://input"), true);

        try {
            // Check if izin exists and status is Pending
            $checkQuery = "SELECT status_approval FROM {$this->table_izin} 
                          WHERE id_izin = :id AND id_profil = :pid";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([':id' => $id, ':pid' => $profilId]);
            $izin = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$izin) {
                ApiResponse::error('Izin not found', 404);
            }

            if ($izin['status_approval'] !== 'Pending') {
                ApiResponse::error('Izin sudah di-approve/reject, tidak bisa diubah', 400);
            }

            $updates = [];
            $params = [':id' => $id, ':pid' => $profilId];

            if (isset($data['tipe'])) {
                if (!in_array($data['tipe'], ['Izin', 'Sakit'])) {
                    ApiResponse::error('Tipe izin hanya Izin atau Sakit', 400);
                }
                $updates[] = "tipe = :tipe";
                $params[':tipe'] = $data['tipe'];
            }

            if (isset($data['start_date'])) {
                $updates[] = "start_date = :start_date";
                $params[':start_date'] = $data['start_date'];
            }

            if (isset($data['end_date'])) {
                $updates[] = "end_date = :end_date";
                $params[':end_date'] = $data['end_date'];
            }

            if (isset($data['deskripsi'])) {
                $updates[] = "deskripsi = :deskripsi";
                $params[':deskripsi'] = $data['deskripsi'];
            }

            if (empty($updates)) {
                ApiResponse::error('No fields to update', 400);
            }

            $query = "UPDATE {$this->table_izin} SET " . implode(", ", $updates) . 
                    " WHERE id_izin = :id AND id_profil = :pid";

            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($params);

            if ($result && $stmt->rowCount() > 0) {
                ApiResponse::success(null, 'Izin updated successfully', 200);
            } else {
                ApiResponse::error('Izin not found or no changes made', 404);
            }
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/izin/cancel/{id}
     * Cancel leave/izin request (only if status is Pending)
     */
    public function cancel($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::error('Method not allowed', 405);
        }

        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        if (!$id) {
            ApiResponse::error('Izin ID required', 400);
        }

        try {
            // Check if izin exists and status is Pending
            $checkQuery = "SELECT status_approval FROM {$this->table_izin} 
                          WHERE id_izin = :id AND id_profil = :pid";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([':id' => $id, ':pid' => $profilId]);
            $izin = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$izin) {
                ApiResponse::error('Izin not found', 404);
            }

            if ($izin['status_approval'] !== 'Pending') {
                ApiResponse::error('Hanya izin dengan status Pending yang bisa dibatalkan', 400);
            }

            // Delete izin
            $deleteQuery = "DELETE FROM {$this->table_izin} WHERE id_izin = :id AND id_profil = :pid";
            $deleteStmt = $this->conn->prepare($deleteQuery);
            $result = $deleteStmt->execute([':id' => $id, ':pid' => $profilId]);

            if ($result && $deleteStmt->rowCount() > 0) {
                ApiResponse::success(null, 'Izin cancelled successfully', 200);
            } else {
                ApiResponse::error('Failed to cancel izin', 500);
            }
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/izin/approve/{id}
     * Approve leave/izin request (Admin only)
     */
    public function approve($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            ApiResponse::error('Method not allowed', 405);
        }

        $payload = AuthApi::validateToken();
        // Verify admin role
        if ($payload['role'] !== 'Admin') {
            ApiResponse::error('Unauthorized. Admin only.', 403);
        }

        if (!$id) {
            ApiResponse::error('Izin ID required', 400);
        }

        try {
            // Check if izin exists and status is Pending
            $checkQuery = "SELECT status_approval FROM {$this->table_izin} WHERE id_izin = :id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([':id' => $id]);
            $izin = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$izin) {
                ApiResponse::error('Izin not found', 404);
            }

            if ($izin['status_approval'] !== 'Pending') {
                ApiResponse::error('Hanya izin dengan status Pending yang bisa di-approve', 400);
            }

            // [BARU] Pakai IzinModel::approve() agar presensi ikut digenerate
            // otomatis untuk rentang tanggal izin (Modul 4 V3 - Izin
            // Integration), konsisten dengan AdminController::izin() di web.
            $izinModel = new IzinModel();

            if ($izinModel->approve($id)) {
                ApiResponse::success(null, 'Izin approved successfully', 200);
            } else {
                ApiResponse::error('Failed to approve izin', 500);
            }
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/izin/reject/{id}
     * Reject leave/izin request (Admin only)
     */
    public function reject($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            ApiResponse::error('Method not allowed', 405);
        }

        $payload = AuthApi::validateToken();
        // Verify admin role
        if ($payload['role'] !== 'Admin') {
            ApiResponse::error('Unauthorized. Admin only.', 403);
        }

        if (!$id) {
            ApiResponse::error('Izin ID required', 400);
        }

        try {
            // Check if izin exists and status is Pending
            $checkQuery = "SELECT status_approval FROM {$this->table_izin} WHERE id_izin = :id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([':id' => $id]);
            $izin = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$izin) {
                ApiResponse::error('Izin not found', 404);
            }

            if ($izin['status_approval'] !== 'Pending') {
                ApiResponse::error('Hanya izin dengan status Pending yang bisa di-reject', 400);
            }

            // [BARU] Pakai IzinModel::reject() agar konsisten dengan
            // AdminController::izin() di web (termasuk revert presensi
            // status='Izin' -> 'Alpa' jika sebelumnya pernah di-generate).
            $izinModel = new IzinModel();

            if ($izinModel->reject($id)) {
                ApiResponse::success(null, 'Izin rejected successfully', 200);
            } else {
                ApiResponse::error('Failed to reject izin', 500);
            }
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/izin/admin/pending
     * Get all pending leave requests (Admin view)
     */
    public function getPendingForAdmin() {
        $payload = AuthApi::validateToken();
        if ($payload['role'] !== 'Admin') {
            ApiResponse::error('Unauthorized. Admin only.', 403);
        }

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $status = isset($_GET['status']) ? $_GET['status'] : 'Pending'; // Default filter by Pending

        // Build query with optional status filter
        $whereClause = "WHERE 1=1";
        if (!empty($status) && $status !== 'all') {
            $whereClause .= " AND i.status_approval = :status";
        }

        $query = "SELECT 
                    i.id_izin,
                    i.id_profil,
                    i.tipe,
                    i.start_date,
                    i.end_date,
                    i.deskripsi,
                    i.file_bukti,
                    i.status_approval,
                    DATEDIFF(i.end_date, i.start_date) + 1 as durasi_hari,
                    DATE_FORMAT(i.start_date, '%d/%m/%Y') as start_date_format,
                    DATE_FORMAT(i.end_date, '%d/%m/%Y') as end_date_format,
                    p.nama as nama_asisten,
                    p.nim as nim_asisten,
                    p.photo_profile as foto_asisten
                  FROM {$this->table_izin} i
                  LEFT JOIN profile p ON i.id_profil = p.id_profil
                  LEFT JOIN user u ON p.id_user = u.id_user
                  {$whereClause}
                  ORDER BY i.start_date DESC, i.id_izin DESC
                  LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            if (!empty($status) && $status !== 'all') {
                $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            }
            $stmt->execute();

            $izins = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM {$this->table_izin} i WHERE 1=1";
            if (!empty($status) && $status !== 'all') {
                $countQuery .= " AND i.status_approval = :status";
            }
            $countStmt = $this->conn->prepare($countQuery);
            if (!empty($status) && $status !== 'all') {
                $countStmt->bindValue(':status', $status, PDO::PARAM_STR);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            ApiResponse::success([
                'data' => $izins,
                'pagination' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset
                ]
            ], 'Pending leave list retrieved', 200);
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }
}
?>
