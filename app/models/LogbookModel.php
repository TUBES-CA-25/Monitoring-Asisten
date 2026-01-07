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
>>>>>>> main
?>