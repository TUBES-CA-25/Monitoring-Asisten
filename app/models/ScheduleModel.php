<?php
class ScheduleModel {
    private $conn;
    public function __construct() { $db = new Database(); $this->conn = $db->getConnection(); }

    public function getAllUserSchedules($userId) {
        // 1. Dapatkan ID Profil dari User ID
        $stmtP = $this->conn->prepare("SELECT id_profil FROM profile WHERE id_user = :uid");
        $stmtP->execute([':uid' => $userId]);
        $pId = $stmtP->fetchColumn();

        if (!$pId) return []; // Jika profil belum ada

        $schedules = [];

        // A. AMBIL JADWAL KULIAH (Tipe: Kuliah -> Hijau)
        $sqlK = "SELECT id_jadwal_kuliah as id, matkul as title, dosen as description, 
                        ruangan as location, tanggal, start_time, end_time, 
                        'kuliah' as type 
                 FROM jadwal_kuliah WHERE id_profil = :pid";
        $stmtK = $this->conn->prepare($sqlK);
        $stmtK->execute([':pid' => $pId]);
        $kuliah = $stmtK->fetchAll(PDO::FETCH_ASSOC);
        foreach($kuliah as $row) { $schedules[] = $row; }

        // B. AMBIL JADWAL ASISTEN (Tipe: Asisten -> Biru)
        $sqlA = "SELECT id_jadwal_asisten as id, mata_kuliah as title, 
                        CONCAT('Jaga Lab - ', prodi) as description, 
                        ruangan_lab as location, tanggal, start_time, end_time, 
                        'asisten' as type 
                 FROM jadwal_asisten WHERE id_profil = :pid";
        $stmtA = $this->conn->prepare($sqlA);
        $stmtA->execute([':pid' => $pId]);
        $asisten = $stmtA->fetchAll(PDO::FETCH_ASSOC);
        foreach($asisten as $row) { $schedules[] = $row; }

        // C. AMBIL JADWAL PIKET (Tipe: Piket -> Merah)
        // Note: Piket biasanya seharian atau jam tertentu, kita set default jam jika null
        $sqlP = "SELECT id_jadwal_piket as id, subjek as title, 'Tugas Kebersihan/Jaga' as description, 
                        'Lab' as location, tanggal, '08:00:00' as start_time, '17:00:00' as end_time, 
                        'piket' as type 
                 FROM jadwal_piket WHERE id_profil = :pid";
        $stmtP = $this->conn->prepare($sqlP);
        $stmtP->execute([':pid' => $pId]);
        $piket = $stmtP->fetchAll(PDO::FETCH_ASSOC);
        foreach($piket as $row) { $schedules[] = $row; }

        return $schedules;
    }

    // CRUD Jadwal Kuliah (Pribadi)
    public function createPersonalSchedule($data) {
        // 1. Get Profil ID
        $stmtP = $this->conn->prepare("SELECT id_profil FROM profile WHERE id_user = :uid");
        $stmtP->execute([':uid' => $data['user_id']]);
        $pId = $stmtP->fetchColumn();

        // 2. Insert jadwal_kuliah
        $sql = "INSERT INTO jadwal_kuliah (id_profil, matkul, dosen, ruangan, tanggal, start_time, end_time, tipe) 
                VALUES (:pid, :title, :desc, :loc, DATE(:start), TIME(:start), TIME(:end), 'Teori')";
        $stmt = $this->conn->prepare($sql);
        $success = $stmt->execute([
            ':pid' => $pId, ':title' => $data['title'], ':desc' => $data['description'],
            ':loc' => $data['location'], ':start' => $data['start_time'], ':end' => $data['end_time']
        ]);

        // 3. Insert jadwal_full trigger
        if($success) {
            $lastId = $this->conn->lastInsertId();
            $this->conn->query("INSERT INTO jadwal_full (id_jadwal_kuliah) VALUES ($lastId)");
        }
        return $success;
    }

    public function deletePersonalSchedule($id, $userId) {
        // Hapus dari jadwal_full, Cascade akan menghapus di jadwal_kuliah? 
        // Tidak, FK ada di jadwal_full. Jadi hapus jadwal_full dulu yang link ke jadwal_kuliah.
        // Tapi logika kita delete based on ID VIEW.
        
        // Simplifikasi: Hapus jadwal kuliah langsung, jadwal_full ikut terhapus jika setting cascade benar,
        // atau hapus jadwal_full where id_jadwal_kuliah = ...
        
        // Mendapatkan id_jadwal_kuliah dari view id (jadwal_full.id_jadwal)
        $stmtGet = $this->conn->prepare("SELECT id_jadwal_kuliah FROM jadwal_full WHERE id_jadwal = :id");
        $stmtGet->execute([':id' => $id]);
        $jkId = $stmtGet->fetchColumn();

        if($jkId) {
            $stmtDel = $this->conn->prepare("DELETE FROM jadwal_kuliah WHERE id_jadwal_kuliah = :jkid");
            return $stmtDel->execute([':jkid' => $jkId]);
        }
        return false;
    }

    public function getPersonalClassSchedules($userId) {
        $stmtP = $this->conn->prepare("SELECT id_profil FROM profile WHERE id_user = :uid");
        $stmtP->execute([':uid' => $userId]);
        $pId = $stmtP->fetchColumn();
        
        $sql = "SELECT id_jadwal_kuliah as id, matkul as title, dosen as description, 
                       ruangan as location, tanggal, start_time, end_time
                FROM jadwal_kuliah WHERE id_profil = :pid ORDER BY tanggal DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':pid' => $pId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Support untuk method Admin (getAllSchedules)
    public function getAllSchedules() {
        // Reuse logic getAllUserSchedules tapi tanpa filter user specific
        // ... (Implementasi serupa dengan getAllUserSchedules tanpa WHERE clause user)
        return []; 
    } 
}
?>