<?php
class IzinModel {
    private $table_izin = 'izin';
    private $table_profile = 'profile';
    private $conn;
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    // Get all izin dengan filter
    public function getAll($status = null, $tipe = null, $limit = 10, $offset = 0) {
        $where = "WHERE 1=1";
        $params = [];

        if ($status && $status !== 'all') {
            $where .= " AND i.status_approval = ?";
            $params[] = $status;
        }

        if ($tipe) {
            $where .= " AND i.tipe = ?";
            $params[] = $tipe;
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
                  LEFT JOIN {$this->table_profile} p ON i.id_profil = p.id_profil
                  {$where}
                  ORDER BY i.start_date DESC, i.id_izin DESC
                  LIMIT " . intval($limit) . " OFFSET " . intval($offset);

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("IzinModel::getAll Error: " . $e->getMessage());
            return [];
        }
    }

    // Get total count
    public function getCount($status = null, $tipe = null) {
        $where = "WHERE 1=1";
        $params = [];

        if ($status && $status !== 'all') {
            $where .= " AND status_approval = ?";
            $params[] = $status;
        }

        if ($tipe) {
            $where .= " AND tipe = ?";
            $params[] = $tipe;
        }

        $query = "SELECT COUNT(*) as total FROM {$this->table_izin} {$where}";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Get izin by ID
    public function getById($id) {
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
                  LEFT JOIN {$this->table_profile} p ON i.id_profil = p.id_profil
                  WHERE i.id_izin = ?";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    // Approve izin
    public function approve($id) {
        try {
            // Get izin details
            $izin = $this->getById($id);
            if (!$izin) return false;

            // Get date range
            $start_date = $izin['start_date'];
            $end_date = $izin['end_date'];
            $id_profil = $izin['id_profil'];

            $this->conn->beginTransaction();

            // Update izin status
            $query = "UPDATE {$this->table_izin} SET status_approval = 'Approved' WHERE id_izin = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);

            // Update presensi YANG SUDAH ADA untuk seluruh tanggal dalam rentang izin
            $presensi_query = "UPDATE presensi 
                              SET status = 'Izin' 
                              WHERE id_profil = ? 
                              AND tanggal BETWEEN ? AND ?
                              AND status != 'Izin'";
            $presensi_stmt = $this->conn->prepare($presensi_query);
            $presensi_stmt->execute([$id_profil, $start_date, $end_date]);

            // [BARU] Generate (INSERT) presensi status='Izin' untuk tanggal dalam
            // rentang izin yang BELUM punya record presensi sama sekali, sesuai
            // RANCANGAN_PERUBAHAN_ICLABS_WEB_V3 poin 4 (Izin Integration -
            // "Approve Admin -> Generate presensi otomatis").
            $this->generateLeavePresensi($id_profil, $start_date, $end_date);

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("IzinModel::approve Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate (INSERT) baris presensi status='Izin' untuk setiap tanggal
     * dalam rentang [$start_date, $end_date] milik $id_profil yang belum
     * memiliki record presensi sama sekali. Tanggal yang SUDAH punya record
     * dilewati (sudah ditangani oleh UPDATE pada approve()).
     *
     * Dipanggil di dalam transaksi approve() — tidak melakukan
     * begin/commit/rollBack sendiri.
     */
    private function generateLeavePresensi($id_profil, $start_date, $end_date) {
        $period = new DatePeriod(
            new DateTime($start_date),
            new DateInterval('P1D'),
            (new DateTime($end_date))->modify('+1 day')
        );

        $checkStmt = $this->conn->prepare(
            "SELECT id_presensi FROM presensi WHERE id_profil = ? AND tanggal = ?"
        );
        $insertStmt = $this->conn->prepare(
            "INSERT INTO presensi (id_profil, tanggal, status) VALUES (?, ?, 'Izin')"
        );

        foreach ($period as $date) {
            $tanggal = $date->format('Y-m-d');

            $checkStmt->execute([$id_profil, $tanggal]);
            if ($checkStmt->fetch()) {
                continue;
            }

            $insertStmt->execute([$id_profil, $tanggal]);
        }
    }

    // Reject izin
    public function reject($id) {
        try {
            // Get izin details
            $izin = $this->getById($id);
            if (!$izin) return false;

            // Get date range
            $start_date = $izin['start_date'];
            $end_date = $izin['end_date'];
            $id_profil = $izin['id_profil'];

            // Update izin status
            $query = "UPDATE {$this->table_izin} SET status_approval = 'Rejected' WHERE id_izin = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);

            // Revert presensi status for all dates in izin range back to Alpa
            $presensi_query = "UPDATE presensi 
                              SET status = 'Alpa' 
                              WHERE id_profil = ? 
                              AND tanggal BETWEEN ? AND ?
                              AND status = 'Izin'";
            $presensi_stmt = $this->conn->prepare($presensi_query);
            $presensi_stmt->execute([$id_profil, $start_date, $end_date]);

            return true;
        } catch (PDOException $e) {
            error_log("IzinModel::reject Error: " . $e->getMessage());
            return false;
        }
    }
}
?>