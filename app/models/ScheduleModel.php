<?php
class ScheduleModel {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function getAllUserSchedules($userId) {
        $params = [];
        $filterK = ""; $filterA = ""; $filterP = "";

        if ($userId != 0) {
            $stmtP = $this->conn->prepare("SELECT id_profil FROM profile WHERE id_user = :uid");
            $stmtP->execute([':uid' => $userId]);
            $pId = $stmtP->fetchColumn();
            
            if (!$pId) return [];
            $params = [':pid' => $pId];

            $filterK = "WHERE id_profil = :pid";
            $filterA = "WHERE ja.id_profil = :pid";
            $filterP = "WHERE jp.id_profil = :pid";
        }

        $schedules = [];

        // 1. Jadwal Kuliah
        $sqlK = "SELECT id_jadwal_kuliah as id, matkul as title, dosen as description, 
                        ruangan as location, tanggal, hari, start_time, end_time, 'kuliah' as type 
                 FROM jadwal_kuliah $filterK";
        $stmtK = $this->conn->prepare($sqlK);
        $stmtK->execute($params);
        foreach($stmtK->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $row['user_name'] = 'Saya';
            $schedules[] = $row;
        }

        // 2. Jadwal Asisten
        $sqlA = "SELECT ja.id_jadwal_asisten as id, ja.mata_kuliah as title, 
                        CONCAT('Jaga Lab - ', ja.prodi) as description, 
                        ja.ruangan_lab as location, ja.tanggal, ja.hari, ja.start_time, ja.end_time, 'asisten' as type,
                        p.nama as user_name
                 FROM jadwal_asisten ja
                 JOIN profile p ON ja.id_profil = p.id_profil
                 $filterA";
        $stmtA = $this->conn->prepare($sqlA);
        $stmtA->execute($params);
        foreach($stmtA->fetchAll(PDO::FETCH_ASSOC) as $row) $schedules[] = $row;

        // 3. Jadwal Piket
        $sqlP = "SELECT jp.id_jadwal_piket as id, jp.subjek as title, 'Piket Kebersihan' as description, 
                        'Lab' as location, jp.tanggal, jp.hari, '08:00:00' as start_time, '17:00:00' as end_time, 'piket' as type,
                        p.nama as user_name
                 FROM jadwal_piket jp
                 JOIN profile p ON jp.id_profil = p.id_profil
                 $filterP";
        $stmtP = $this->conn->prepare($sqlP);
        $stmtP->execute($params);
        foreach($stmtP->fetchAll(PDO::FETCH_ASSOC) as $row) $schedules[] = $row;

        return $schedules;
    }
    
    
    // ==========================================
    // CRUD JADWAL KULIAH (USER)
    // ==========================================

    public function addKuliah($data) {
        try {
            $this->conn->beginTransaction();
            
            // Logika Recurring:
            // Jika 'once' -> Isi Tanggal, Hari NULL
            // Jika 'repeat' -> Isi Hari, Tanggal NULL
            $tanggal = ($data['type_repeat'] == 'once') ? $data['date'] : NULL;
            $hari = ($data['type_repeat'] == 'repeat') ? $data['day'] : NULL;

            $sql = "INSERT INTO jadwal_kuliah (id_profil, matkul, dosen, ruangan, tanggal, hari, start_time, end_time, tipe) 
                    VALUES (:pid, :matkul, :dosen, :ruangan, :tgl, :hari, :start, :end, 'Teori')";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':pid' => $data['id_profil'], ':matkul' => $data['title'], 
                ':dosen' => $data['description'], ':ruangan' => $data['location'],
                ':tgl' => $tanggal, ':hari' => $hari, 
                ':start' => $data['start'], ':end' => $data['end']
            ]);
            $lastId = $this->conn->lastInsertId();

            $this->conn->exec("INSERT INTO jadwal_full (id_jadwal_kuliah) VALUES ($lastId)");
            $this->conn->commit();
            return true;
        } catch (Exception $e) { $this->conn->rollBack(); return false; }
    }

    public function updateKuliah($data) {
        try {
            // Logika Recurring untuk Update
            $tanggal = ($data['type_repeat'] == 'once') ? $data['date'] : NULL;
            $hari = ($data['type_repeat'] == 'repeat') ? $data['day'] : NULL;

            $sql = "UPDATE jadwal_kuliah SET 
                    matkul = :matkul, 
                    dosen = :dosen, 
                    ruangan = :ruangan, 
                    tanggal = :tgl, 
                    hari = :hari,
                    start_time = :start, 
                    end_time = :end 
                    WHERE id_jadwal_kuliah = :id AND id_profil = :pid";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id' => $data['id'], 
                ':pid' => $data['id_profil'],
                ':matkul' => $data['title'], 
                ':dosen' => $data['description'], 
                ':ruangan' => $data['location'], 
                ':tgl' => $tanggal,
                ':hari' => $hari,
                ':start' => $data['start'], 
                ':end' => $data['end']
            ]);
        } catch (Exception $e) { return false; }
    }

    public function deleteKuliah($id, $pId) {
        $stmt = $this->conn->prepare("DELETE FROM jadwal_kuliah WHERE id_jadwal_kuliah=:id AND id_profil=:pid");
        return $stmt->execute([':id' => $id, ':pid' => $pId]);
    }

    // ==========================================
    // CRUD JADWAL ASISTEN & PIKET (ADMIN)
    // ==========================================

    public function addAsistenSchedule($data) {
        try {
            $this->conn->beginTransaction();
            
            $isRepeat = isset($data['is_repeat']) && $data['is_repeat'] == 1;
            $tanggal = $isRepeat ? NULL : $data['date'];
            $hari = $isRepeat ? date('N', strtotime($data['date'])) : NULL;

            // PERBAIKAN: Hapus id_user dari INSERT, cukup id_profil
            $sql = "INSERT INTO jadwal_asisten (id_profil, mata_kuliah, prodi, ruangan_lab, tanggal, hari, start_time, end_time) 
                    VALUES (:pid, :matkul, 'Informatika', :loc, :tgl, :hari, :start, :end)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':pid' => $data['id_profil'],
                ':matkul' => $data['title'], 
                ':loc' => $data['location'],
                ':tgl' => $tanggal, 
                ':hari' => $hari,
                ':start' => $data['start'], 
                ':end' => $data['end']
            ]);
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function updateAsistenSchedule($data) {
        try {
            $isRepeat = isset($data['is_repeat']) && $data['is_repeat'] == 1;
            $tanggal = $isRepeat ? NULL : $data['date'];
            $hari = $isRepeat ? date('N', strtotime($data['date'])) : NULL;

            $sql = "UPDATE jadwal_asisten SET 
                    mata_kuliah = :matkul, 
                    ruangan_lab = :loc, 
                    tanggal = :tgl, 
                    hari = :hari, 
                    start_time = :start, 
                    end_time = :end 
                    WHERE id_jadwal_asisten = :id";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id' => $data['id'], 
                ':matkul' => $data['title'], 
                ':loc' => $data['location'],
                ':tgl' => $tanggal, 
                ':hari' => $hari, 
                ':start' => $data['start'], 
                ':end' => $data['end']
            ]);
        } catch (Exception $e) { return false; }
    }

    public function addPiketSchedule($data) {
        try {
            $this->conn->beginTransaction();
            
            $isRepeat = isset($data['is_repeat']) && $data['is_repeat'] == 1;
            $tanggal = $isRepeat ? NULL : $data['date'];
            $hari = $isRepeat ? date('N', strtotime($data['date'])) : NULL;

            // PERBAIKAN: Hapus id_user dari INSERT
            $sql = "INSERT INTO jadwal_piket (id_profil, subjek, tanggal, hari) 
                    VALUES (:pid, :subjek, :tgl, :hari)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':pid' => $data['id_profil'],
                ':subjek' => $data['title'], 
                ':tgl' => $tanggal, 
                ':hari' => $hari
            ]);
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function updatePiketSchedule($data) {
        try {
            $isRepeat = isset($data['is_repeat']) && $data['is_repeat'] == 1;
            $tanggal = $isRepeat ? NULL : $data['date'];
            $hari = $isRepeat ? date('N', strtotime($data['date'])) : NULL;

            $sql = "UPDATE jadwal_piket SET 
                    subjek = :title, 
                    tanggal = :tgl, 
                    hari = :hari 
                    WHERE id_jadwal_piket = :id";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id' => $data['id'], 
                ':title' => $data['title'], 
                ':tgl' => $tanggal, 
                ':hari' => $hari
            ]);
        } catch (Exception $e) { return false; }
    }

    
    public function addAdminSchedule($data) {
        $targetPid = 0; 
        
        if ($data['type'] != 'umum') {
            $stmt = $this->conn->prepare("SELECT id_profil FROM profile WHERE id_user = :uid");
            $stmt->execute([':uid' => $data['user_id']]);
            $targetPid = $stmt->fetchColumn();
            if(!$targetPid) return false;
        }

        $data['id_profil'] = $targetPid;

        if ($data['type'] == 'piket') {
            return $this->addPiketSchedule($data);
        } else {
            return $this->addAsistenSchedule($data);
        }
    }

    public function updateAdminSchedule($data) {
        if ($data['type'] == 'piket') {
            return $this->updatePiketSchedule($data);
        } else {
            return $this->updateAsistenSchedule($data);
        }
    }

    public function deleteAdminSchedule($id, $type) {
        if ($type == 'kuliah') return false; 
        
        $table = ($type == 'piket') ? 'jadwal_piket' : 'jadwal_asisten';
        $pk = ($type == 'piket') ? 'id_jadwal_piket' : 'id_jadwal_asisten';
        
        $stmt = $this->conn->prepare("DELETE FROM $table WHERE $pk = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    // Helper untuk List Table (User)
    public function getPersonalClassSchedules($userId) {
        $stmtP = $this->conn->prepare("SELECT id_profil FROM profile WHERE id_user = :uid");
        $stmtP->execute([':uid' => $userId]);
        $pId = $stmtP->fetchColumn();
        
        $sql = "SELECT *, 
                CASE 
                    WHEN hari = 1 THEN 'Senin' WHEN hari = 2 THEN 'Selasa' 
                    WHEN hari = 3 THEN 'Rabu'  WHEN hari = 4 THEN 'Kamis' 
                    WHEN hari = 5 THEN 'Jumat' WHEN hari = 6 THEN 'Sabtu' 
                    WHEN hari = 7 THEN 'Minggu'
                    ELSE tanggal 
                END as waktu_display
                FROM jadwal_kuliah WHERE id_profil = :pid ORDER BY hari DESC, tanggal DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':pid' => $pId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Helper untuk Dashboard User (Jadwal Minggu Ini)
    public function getUserScheduleForWeek($userId) {
        // ... (Kode sama dengan sebelumnya, hanya perlu memastikan kolom 'hari' juga diambil jika perlu) ...
        // Untuk Dashboard, kita ambil yang spesifik tanggal minggu ini
        $stmtP = $this->conn->prepare("SELECT id_profil FROM profile WHERE id_user = :uid");
        $stmtP->execute([':uid' => $userId]);
        $pId = $stmtP->fetchColumn();
        if (!$pId) return [];

        $monday = date('Y-m-d', strtotime('monday this week'));
        $sunday = date('Y-m-d', strtotime('sunday this week'));
        $schedules = [];

        // Ambil yang punya tanggal spesifik di minggu ini
        $sqlK = "SELECT id_jadwal_kuliah as id, matkul as title, dosen as description, ruangan as location, tanggal, start_time, end_time, 'kuliah' as type 
                 FROM jadwal_kuliah WHERE id_profil = :pid AND (tanggal BETWEEN :s AND :e)";
        $stmtK = $this->conn->prepare($sqlK);
        $stmtK->execute([':pid' => $pId, ':s' => $monday, ':e' => $sunday]);
        foreach($stmtK->fetchAll(PDO::FETCH_ASSOC) as $row) $schedules[] = $row;

        // Ambil yang RECURRING (Hari 1-7)
        // Kita perlu mapping hari ini hari apa, tapi untuk display "Weekly Schedule", kita ambil semua recurring
        $sqlK_Rec = "SELECT id_jadwal_kuliah as id, matkul as title, dosen as description, ruangan as location, hari, start_time, end_time, 'kuliah' as type 
                     FROM jadwal_kuliah WHERE id_profil = :pid AND hari IS NOT NULL";
        $stmtK_Rec = $this->conn->prepare($sqlK_Rec);
        $stmtK_Rec->execute([':pid' => $pId]);
        foreach($stmtK_Rec->fetchAll(PDO::FETCH_ASSOC) as $row) {
            // Kita perlu konversi 'hari' (int) ke tanggal minggu ini untuk display yang benar di dashboard jika perlu
            // Tapi untuk list sederhana, cukup data mentah
            $schedules[] = $row;
        }

        return $schedules;
    }

    public function getAllSchedules() {
        $schedules = [];

        // A. Jadwal Asisten & Umum
        // PERBAIKAN: Mengambil p.id_user, bukan ja.id_user
        $sqlA = "SELECT ja.id_jadwal_asisten as id, ja.mata_kuliah as title, 
                        ja.ruangan_lab as location, ja.tanggal, ja.hari, 
                        ja.start_time, ja.end_time, 
                        CASE WHEN ja.id_profil = 0 THEN 'umum' ELSE 'asisten' END as type,
                        p.nama as user_name, p.id_user 
                 FROM jadwal_asisten ja
                 LEFT JOIN profile p ON ja.id_profil = p.id_profil";
        
        $stmtA = $this->conn->prepare($sqlA);
        $stmtA->execute();
        foreach($stmtA->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($row['type'] == 'umum') {
                $row['user_name'] = 'Laboratorium (Umum)';
                $row['id_user'] = 0; // Set ID dummy untuk umum
            }
            $schedules[] = $row;
        }

        // B. Jadwal Piket
        // PERBAIKAN: Mengambil p.id_user, bukan jp.id_user
        $sqlP = "SELECT jp.id_jadwal_piket as id, jp.subjek as title, 
                        'Lab' as location, jp.tanggal, jp.hari, 
                        '08:00:00' as start_time, '17:00:00' as end_time, 'piket' as type,
                        p.nama as user_name, p.id_user
                 FROM jadwal_piket jp
                 JOIN profile p ON jp.id_profil = p.id_profil";
        
        $stmtP = $this->conn->prepare($sqlP);
        $stmtP->execute();
        foreach($stmtP->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $schedules[] = $row;
        }

        // C. Jadwal Kuliah (Opsional, agar Admin tahu jadwal kuliah asisten)
        $sqlK = "SELECT jk.id_jadwal_kuliah as id, jk.matkul as title, 
                        jk.ruangan as location, jk.tanggal, jk.hari, 
                        jk.start_time, jk.end_time, 'kuliah' as type,
                        p.nama as user_name, u.id_user
                 FROM jadwal_kuliah jk
                 JOIN profile p ON jk.id_profil = p.id_profil
                 JOIN user u ON p.id_user = u.id_user";
        
        $stmtK = $this->conn->prepare($sqlK);
        $stmtK->execute();
        foreach($stmtK->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $schedules[] = $row;
        }

        return $schedules;
    }

    public function deleteSchedule($id) {
        // Note: Idealnya controller mengirim tipe jadwal. 
        // Untuk fallback keamanan, kita coba hapus dari jadwal_asisten dulu.
        try {
            $sql = "DELETE FROM jadwal_asisten WHERE id_jadwal_asisten = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            if ($stmt->rowCount() > 0) return true;

            // Jika tidak ada di asisten, coba di piket
            $sql2 = "DELETE FROM jadwal_piket WHERE id_jadwal_piket = :id";
            $stmt2 = $this->conn->prepare($sql2);
            return $stmt2->execute([':id' => $id]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function getUpcomingSchedules() {
        $schedules = [];
        
        $sql = "SELECT 'umum' as type, ja.mata_kuliah as title, ja.ruangan_lab as location, 
                       ja.tanggal, ja.hari, ja.start_time, ja.end_time
                FROM jadwal_asisten ja 
                WHERE ja.id_profil = 0
                UNION ALL
                SELECT 'kuliah' as type, jk.matkul as title, jk.ruangan as location,
                       jk.tanggal, jk.hari, jk.start_time, jk.end_time
                FROM jadwal_kuliah jk";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $upcoming = [];
        $today = date('Y-m-d');
        $nowTime = date('H:i:s');
        $dayNum = date('N'); 

        foreach($all as $row) {
            $isUpcoming = false;
            $sortDate = '';

            if ($row['hari']) {
                $diff = $row['hari'] - $dayNum;
                if ($diff < 0) $diff += 7;
                if ($diff == 0 && $row['start_time'] < $nowTime) $diff += 7;
                $sortDate = date('Y-m-d', strtotime("+$diff days")) . ' ' . $row['start_time'];
                $isUpcoming = true;
            } elseif ($row['tanggal']) {
                if ($row['tanggal'] > $today || ($row['tanggal'] == $today && $row['start_time'] > $nowTime)) {
                    $sortDate = $row['tanggal'] . ' ' . $row['start_time'];
                    $isUpcoming = true;
                }
            }

            if ($isUpcoming) {
                $row['sort_time'] = $sortDate;
                $row['display_date'] = date('d M Y', strtotime($sortDate));
                $upcoming[] = $row;
            }
        }

        usort($upcoming, function($a, $b) {
            return strtotime($a['sort_time']) - strtotime($b['sort_time']);
        });

        return array_slice($upcoming, 0, 10);
    }
}