<?php
// app/api/ScheduleApi.php

class ScheduleApi {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * POST /api/schedule/add
     */
    public function add() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::error('Method not allowed', 405);
            exit;
        }

        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['matkul']) || empty($data['hari']) || empty($data['start_time'])) {
            ApiResponse::error('Missing required fields: matkul, hari, start_time', 400);
            exit;
        }

        $query = "INSERT INTO jadwal_kuliah 
                  (id_profil, matkul, dosen, id_dosen, kelas, ruangan, hari, tanggal, tanggal_selesai, model_perulangan, start_time, end_time) 
                  VALUES 
                  (:pid, :matkul, :dosen, :id_dosen, :kelas, :ruangan, :hari, :tgl, :tgl_s, :model, :start, :end)";

        // [BARU - Modul Dosen] aplikasi mobile masih mengirim nama dosen
        // sebagai teks bebas - cari/buat di tabel master `dosen` agar tetap
        // konsisten dengan data yang diisi lewat dropdown web. Kontrak
        // response API tidak berubah (tetap tidak mengembalikan dosen).
        require_once '../app/models/ScheduleModel.php';
        $scheduleModel = new ScheduleModel();
        $dosenInfo = $scheduleModel->findOrCreateDosen($data['dosen'] ?? '');

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':pid'    => $profilId,
                ':matkul' => $data['matkul'],
                ':dosen'  => $dosenInfo ? $dosenInfo['nama'] : ($data['dosen'] ?? ''),
                ':id_dosen' => $dosenInfo ? $dosenInfo['id'] : null,
                ':kelas'  => $data['kelas'] ?? '',
                ':ruangan'=> $data['ruangan'] ?? '',
                ':hari'   => $data['hari'],
                ':tgl'    => $data['tanggal'] ?? date('Y-m-d'),
                ':tgl_s'  => $data['tanggal_selesai'] ?? date('Y-m-d', strtotime('+6 months')),
                ':model'  => $data['model_perulangan'] ?? 'mingguan',
                ':start'  => $data['start_time'],
                ':end'    => $data['end_time'] ?? ''
            ]);

            ApiResponse::success(null, 'Jadwal kuliah berhasil ditambahkan', 201);
            exit;
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
            exit;
        }
    }

    /**
     * GET /api/schedule/kuliah
     * Hanya mengambil jadwal kuliah aktif yang belum lewat tanggal selesainya
     */
    public function getKuliah() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $payload = AuthApi::validateToken();
            $profilId = $payload['profil_id'];

            // 🚀 PERBAIKAN: Ditambahkan kondisi mengecek tanggal_selesai agar jadwal expired otomatis hilang
            $query = "SELECT 
                        id_jadwal_kuliah as id,
                        matkul,
                        dosen,
                        kelas,
                        ruangan,
                        hari,
                        start_time,
                        end_time,
                        tanggal,
                        tanggal_selesai,
                        'Kuliah' as type
                      FROM jadwal_kuliah 
                      WHERE id_profil = :pid 
                        AND tanggal_selesai >= CURRENT_DATE
                      ORDER BY hari ASC, start_time ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([':pid' => $profilId]);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                "status" => "success",
                "message" => "Jadwal kuliah retrieved",
                "data" => $schedules
            ]);
            exit;
            
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            exit;
        }
    }

    /**
     * GET /api/schedule/formOptions
     * Mengambil daftar master jadwal kegiatan laboratorium murni dari sisi Web Admin
     */
    public function getFormOptions() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=UTF-8');

        try {
            // 1. Validasi Token asisten
            AuthApi::validateToken();

            // 2. Ambil data mentah asli apa adanya dari tabel jadwal_lab milik web admin
            $queryLab = "SELECT 
                            id_jadwal_lab, 
                            nama_kegiatan, 
                            lokasi, 
                            hari,
                            jam_mulai,
                            jam_selesai
                         FROM jadwal_lab 
                         ORDER BY id_jadwal_lab DESC";
            
            $stmt = $this->conn->prepare($queryLab);
            $stmt->execute();
            $availableSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Kirim data asli tanpa rekayasa alias ke Flutter
            ApiResponse::success([
                "available_schedules" => $availableSchedules
            ], 'Available admin schedules synchronized successfully', 200);
            exit;

        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
            exit;
        }
    }

    /**
     * GET /api/schedule/week
     */
    public function week() {
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id']; 

        $query = "SELECT id_jadwal_kuliah, matkul, dosen, kelas, ruangan, hari, start_time, end_time, tanggal, tanggal_selesai FROM jadwal_kuliah WHERE id_profil = :pid ORDER BY hari ASC, start_time ASC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':pid' => $profilId]);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            ApiResponse::success($schedules, 'Weekly schedule retrieved', 200);
            exit;
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
            exit;
        }
    }

    /**
     * GET /api/schedule/detail/{id}
     */
    public function detail($id = null) {
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        if (!$id) {
            ApiResponse::error('Schedule ID required', 400);
            exit;
        }

        $query = "SELECT * FROM jadwal_kuliah WHERE id_jadwal_kuliah = :id AND id_profil = :pid";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id, ':pid' => $profilId]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$schedule) {
            ApiResponse::error('Schedule not found', 404);
            exit;
        }

        ApiResponse::success($schedule, 'Schedule detail', 200);
        exit;
    }

    /**
     * GET /api/schedule/asisten
     */
    public function getAsisten() {
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        $query = "SELECT id_jadwal_asisten as id, mata_kuliah as matkul, dosen, kelas_lab as kelas, ruangan_lab as ruangan, hari, start_time, end_time, tanggal, tanggal_selesai, 'Asisten' as type FROM jadwal_asisten WHERE id_profil = :pid ORDER BY hari ASC, start_time ASC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':pid' => $profilId]);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            ApiResponse::success($schedules, 'Jadwal asisten retrieved', 200);
            exit;
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
            exit;
        }
    }

    /**
     * GET /api/schedule/lab
     */
    public function getLab() {
        $query = "SELECT id_jadwal_lab as id, nama_kegiatan as activity, lokasi, tanggal, tanggal_selesai, hari, jam_mulai as start_time, jam_selesai as end_time, model_perulangan, 'Lab' as type FROM jadwal_lab ORDER BY tanggal ASC, jam_mulai ASC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            ApiResponse::success($schedules, 'Jadwal lab retrieved', 200);
            exit;
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
            exit;
        }
    }

    /**
     * GET /api/schedule/piket
     */
    public function getPiket() {
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        $query = "SELECT id_jadwal_piket as id, subjek, hari, tanggal, tanggal_selesai, model_perulangan, jam_mulai as start_time, jam_selesai as end_time, 'Piket' as type FROM jadwal_piket WHERE id_profil = :pid ORDER BY tanggal ASC, jam_mulai ASC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':pid' => $profilId]);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            ApiResponse::success($schedules, 'Jadwal piket retrieved', 200);
            exit;
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
            exit;
        }
    }


    /**
     * POST /api/schedule/delete
     */
    public function delete() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=UTF-8');

        try {
            AuthApi::validateToken();
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;

            if (!$id) {
                ApiResponse::error('ID jadwal tidak ditemukan', 400);
                exit;
            }

            $query = "DELETE FROM jadwal_kuliah WHERE id_jadwal_kuliah = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id' => $id]);

            ApiResponse::success(null, 'Jadwal kuliah berhasil dihapus!', 200);
            exit;
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
            exit;
        }
    }
}