<<<<<<< HEAD
<<<<<<< HEAD
<?php
class LogbookModel {
    private $conn;
    public function __construct() { $db = new Database(); $this->conn = $db->getConnection(); }

    public function getAllWithUserInfo() {
        // Tambahkan pr.waktu_presensi dan pr.waktu_pulang
        $sql = "SELECT l.id_logbook as id, l.detail_aktivitas as activity_detail, 
                       pr.tanggal as date, pr.waktu_presensi, pr.waktu_pulang,
                       p.nama as user_name, p.id_user, l.id_presensi
                FROM logbook l 
                JOIN profile p ON l.id_profil = p.id_profil 
                JOIN presensi pr ON l.id_presensi = pr.id_presensi
                ORDER BY pr.tanggal DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countTotal() {
        $stmt = $this->conn->query("SELECT COUNT(*) as total FROM logbook");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserLogbookHistory($userId) {
        // Ambil profil ID dulu
        $stmtP = $this->conn->prepare("SELECT id_profil FROM profile WHERE id_user = :uid");
        $stmtP->execute([':uid' => $userId]);
        $pId = $stmtP->fetchColumn();

        // History 30 hari (Gabung Presensi & Logbook)
        $sql = "SELECT 
                    pr.tanggal as date, 
                    pr.waktu_presensi as check_in_time, 
                    pr.waktu_pulang as check_out_time, 
                    l.detail_aktivitas as activity_detail,
                    pr.waktu_presensi as log_time, -- Gunakan jam masuk sebagai referensi
                    l.id_logbook as log_id
                FROM presensi pr
                LEFT JOIN logbook l ON pr.id_presensi = l.id_presensi
                WHERE pr.id_profil = :pid
                ORDER BY pr.tanggal DESC LIMIT 30";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':pid' => $pId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveLogbook($data) {
        // 1. Cari id_profil
        $stmtP = $this->conn->prepare("SELECT id_profil FROM profile WHERE id_user = :uid");
        $stmtP->execute([':uid' => $data['user_id']]);
        $pId = $stmtP->fetchColumn();

        // 2. Cari id_presensi pada tanggal tersebut
        $stmtPr = $this->conn->prepare("SELECT id_presensi FROM presensi WHERE id_profil = :pid AND tanggal = :date");
        $stmtPr->execute([':pid' => $pId, ':date' => $data['date']]);
        $presensi = $stmtPr->fetch(PDO::FETCH_ASSOC);

        if (!$presensi) return false; // Tidak bisa isi logbook jika belum presensi
        $idPresensi = $presensi['id_presensi'];

        // 3. Cek Logbook Existing
        $checkSql = "SELECT id_logbook FROM logbook WHERE id_presensi = :idp";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->execute([':idp' => $idPresensi]);
        
        if ($checkStmt->rowCount() > 0) {
            $sql = "UPDATE logbook SET detail_aktivitas = :act WHERE id_presensi = :idp";
        } else {
            $sql = "INSERT INTO logbook (id_profil, id_presensi, detail_aktivitas, is_verified) 
                    VALUES (:pid, :idp, :act, 0)";
        }

        $stmt = $this->conn->prepare($sql);
        $params = [':idp' => $idPresensi, ':act' => $data['activity']];
        if ($checkStmt->rowCount() == 0) $params[':pid'] = $pId; // Insert butuh pid

        return $stmt->execute($params);
    }
}
=======
<?php
class LogbookModel {
    private $conn;
    public function __construct() { $db = new Database(); $this->conn = $db->getConnection(); }

    public function getAllWithUserInfo() {
        // Tambahkan pr.waktu_presensi dan pr.waktu_pulang
=======
<?php
class LogbookModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllWithUserInfo() {
        // Digunakan oleh Admin & SuperAdmin
>>>>>>> main
        $sql = "SELECT l.id_logbook as id, l.detail_aktivitas as activity_detail, 
                       pr.tanggal as date, pr.waktu_presensi, pr.waktu_pulang,
                       p.nama as user_name, p.id_user, l.id_presensi
                FROM logbook l 
                JOIN profile p ON l.id_profil = p.id_profil 
                JOIN presensi pr ON l.id_presensi = pr.id_presensi
                ORDER BY pr.tanggal DESC";
<<<<<<< HEAD
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countTotal() {
        $stmt = $this->conn->query("SELECT COUNT(*) as total FROM logbook");
        return $stmt->fetch(PDO::FETCH_ASSOC);
=======
        
        $this->db->query($sql);
        return $this->db->resultSet();
    }

    public function countTotal() {
        $this->db->query("SELECT COUNT(*) as total FROM logbook");
        return $this->db->single();
>>>>>>> main
    }

    public function getUserLogbookHistory($userId) {
        // Ambil profil ID dulu
<<<<<<< HEAD
        $stmtP = $this->conn->prepare("SELECT id_profil FROM profile WHERE id_user = :uid");
        $stmtP->execute([':uid' => $userId]);
        $pId = $stmtP->fetchColumn();

        // History 30 hari (Gabung Presensi & Logbook)
=======
        $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
        $this->db->bind(':uid', $userId);
        $result = $this->db->single();
        $pId = $result['id_profil'] ?? false;

        if (!$pId) return [];

        // History 30 hari (Gabung Presensi & Logbook)
        // LEFT JOIN memastikan hari dimana user absen tapi belum isi logbook tetap muncul
>>>>>>> main
        $sql = "SELECT 
                    pr.tanggal as date, 
                    pr.waktu_presensi as check_in_time, 
                    pr.waktu_pulang as check_out_time, 
                    l.detail_aktivitas as activity_detail,
<<<<<<< HEAD
                    pr.waktu_presensi as log_time, -- Gunakan jam masuk sebagai referensi
=======
>>>>>>> main
                    l.id_logbook as log_id
                FROM presensi pr
                LEFT JOIN logbook l ON pr.id_presensi = l.id_presensi
                WHERE pr.id_profil = :pid
                ORDER BY pr.tanggal DESC LIMIT 30";
        
<<<<<<< HEAD
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':pid' => $pId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
=======
        $this->db->query($sql);
        $this->db->bind(':pid', $pId);
        return $this->db->resultSet();
>>>>>>> main
    }

    public function saveLogbook($data) {
        // 1. Cari id_profil
<<<<<<< HEAD
        $stmtP = $this->conn->prepare("SELECT id_profil FROM profile WHERE id_user = :uid");
        $stmtP->execute([':uid' => $data['user_id']]);
        $pId = $stmtP->fetchColumn();

        // 2. Cari id_presensi pada tanggal tersebut
        $stmtPr = $this->conn->prepare("SELECT id_presensi FROM presensi WHERE id_profil = :pid AND tanggal = :date");
        $stmtPr->execute([':pid' => $pId, ':date' => $data['date']]);
        $presensi = $stmtPr->fetch(PDO::FETCH_ASSOC);
=======
        $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
        $this->db->bind(':uid', $data['user_id']);
        $resP = $this->db->single();
        $pId = $resP['id_profil'] ?? false;

        if (!$pId) return false;

        // 2. Cari id_presensi pada tanggal tersebut
        $this->db->query("SELECT id_presensi FROM presensi WHERE id_profil = :pid AND tanggal = :date");
        $this->db->bind(':pid', $pId);
        $this->db->bind(':date', $data['date']);
        $presensi = $this->db->single();
>>>>>>> main

        if (!$presensi) return false; // Tidak bisa isi logbook jika belum presensi
        $idPresensi = $presensi['id_presensi'];

<<<<<<< HEAD
        // 3. Cek Logbook Existing
        $checkSql = "SELECT id_logbook FROM logbook WHERE id_presensi = :idp";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->execute([':idp' => $idPresensi]);
        
        if ($checkStmt->rowCount() > 0) {
            $sql = "UPDATE logbook SET detail_aktivitas = :act WHERE id_presensi = :idp";
        } else {
            $sql = "INSERT INTO logbook (id_profil, id_presensi, detail_aktivitas, is_verified) 
                    VALUES (:pid, :idp, :act, 0)";
        }

        $stmt = $this->conn->prepare($sql);
        $params = [':idp' => $idPresensi, ':act' => $data['activity']];
        if ($checkStmt->rowCount() == 0) $params[':pid'] = $pId; // Insert butuh pid

        return $stmt->execute($params);
    }
}
>>>>>>> main
=======
        // 3. Cek Logbook Existing (Update jika ada, Insert jika baru)
        $this->db->query("SELECT id_logbook FROM logbook WHERE id_presensi = :idp");
        $this->db->bind(':idp', $idPresensi);
        
        if ($this->db->rowCount() > 0) {
            // Update
            $sql = "UPDATE logbook SET detail_aktivitas = :act WHERE id_presensi = :idp";
            $this->db->query($sql);
            $this->db->bind(':idp', $idPresensi);
            $this->db->bind(':act', $data['activity']);
        } else {
            // Insert
            $sql = "INSERT INTO logbook (id_profil, id_presensi, detail_aktivitas, is_verified) 
                    VALUES (:pid, :idp, :act, 0)";
            $this->db->query($sql);
            $this->db->bind(':pid', $pId);
            $this->db->bind(':idp', $idPresensi);
            $this->db->bind(':act', $data['activity']);
        }

        return $this->db->execute();
    }

    // --- [BARU] Method untuk Admin ---
    public function getAllLogs() {
        // Alias untuk getAllWithUserInfo agar sesuai panggilan Controller
        return $this->getAllWithUserInfo();
    }

    public function getLogsByUserIdForAdmin($userId) {
        // Ambil ID Profil
        $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
        $this->db->bind(':uid', $userId);
        $pid = $this->db->single()['id_profil'] ?? null;
        if(!$pid) return [];

        // Join Presensi & Logbook (LEFT JOIN agar hari tanpa logbook tetap bisa terlihat jika presensi ada)
        $sql = "SELECT 
                    pr.id_presensi,
                    l.id_logbook,
                    pr.tanggal as date, 
                    pr.waktu_presensi as time_in, 
                    pr.waktu_pulang as time_out, 
                    l.detail_aktivitas as activity
                FROM presensi pr
                LEFT JOIN logbook l ON pr.id_presensi = l.id_presensi
                WHERE pr.id_profil = :pid
                ORDER BY pr.tanggal DESC";
        
        $this->db->query($sql);
        $this->db->bind(':pid', $pid);
        return $this->db->resultSet();
    }

    // 2. Admin Save (Create/Update) - Tanpa validasi 'Pulang'
    public function saveLogAdmin($data) {
        try {
            $this->db->getConnection()->beginTransaction();

            // A. Cari ID Profil
            $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
            $this->db->bind(':uid', $data['user_id']);
            $pid = $this->db->single()['id_profil'] ?? null;
            if(!$pid) return false;

            // B. Cek/Buat Presensi Dulu (Karena Logbook butuh FK id_presensi)
            $this->db->query("SELECT id_presensi FROM presensi WHERE id_profil = :pid AND tanggal = :date");
            $this->db->bind(':pid', $pid);
            $this->db->bind(':date', $data['date']);
            $presensi = $this->db->single();

            $idPresensi = null;

            if ($presensi) {
                // Update jam presensi jika diedit admin
                $idPresensi = $presensi['id_presensi'];
                $sqlUpdPres = "UPDATE presensi SET waktu_presensi = :tin, waktu_pulang = :tout WHERE id_presensi = :idp";
                $this->db->query($sqlUpdPres);
                $this->db->bind(':tin', $data['time_in']);
                $this->db->bind(':tout', !empty($data['time_out']) ? $data['time_out'] : null);
                $this->db->bind(':idp', $idPresensi);
                $this->db->execute();
            } else {
                // Buat Presensi Baru (Manual oleh Admin)
                $sqlInsPres = "INSERT INTO presensi (id_profil, tanggal, waktu_presensi, waktu_pulang, status, foto_presensi) 
                               VALUES (:pid, :date, :tin, :tout, 'Hadir', 'admin_manual.jpg')";
                $this->db->query($sqlInsPres);
                $this->db->bind(':pid', $pid);
                $this->db->bind(':date', $data['date']);
                $this->db->bind(':tin', $data['time_in']);
                $this->db->bind(':tout', !empty($data['time_out']) ? $data['time_out'] : null);
                $this->db->execute();
                $idPresensi = $this->db->getConnection()->lastInsertId();
            }

            // C. Simpan/Update Logbook
            $this->db->query("SELECT id_logbook FROM logbook WHERE id_presensi = :idp");
            $this->db->bind(':idp', $idPresensi);
            
            if ($this->db->rowCount() > 0) {
                // Update Logbook
                $sqlLog = "UPDATE logbook SET detail_aktivitas = :act WHERE id_presensi = :idp";
                $this->db->query($sqlLog);
            } else {
                // Insert Logbook
                $sqlLog = "INSERT INTO logbook (id_profil, id_presensi, detail_aktivitas, is_verified) 
                           VALUES (:pid, :idp, :act, 1)"; // Admin input langsung verified
                $this->db->query($sqlLog);
                $this->db->bind(':pid', $pid);
            }
            
            $this->db->bind(':idp', $idPresensi);
            $this->db->bind(':act', $data['activity']);
            $this->db->execute();

            $this->db->getConnection()->commit();
            return true;

        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            return false;
        }
    }

    // 3. Admin Delete Logbook (Hapus Logbook saja, Presensi tetap)
    public function deleteLogAdmin($idLogbook) {
        $this->db->query("DELETE FROM logbook WHERE id_logbook = :id");
        $this->db->bind(':id', $idLogbook);
        return $this->db->execute();
    }
}
>>>>>>> main
?>