<?php
require_once '../app/core/GoogleClient.php';
class ScheduleModel {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // =====================================================================
    // 1. GET ALL (ADMIN)
    // =====================================================================
    public function getAllSchedules() {
        $schedules = [];

        // A. UMUM
        try {
            $sqlL = "SELECT id_jadwal_lab as id, nama_kegiatan as title, lokasi as location, 
                            tanggal as start_date, tanggal_selesai as end_date, hari as day_of_week, 
                            jam_mulai as start_time, jam_selesai as end_time, model_perulangan,
                            '' as dosen, '' as kelas,
                            0 as id_profil, 'umum' as type, 'Laboratorium' as user_name 
                     FROM jadwal_lab";
            $stmtL = $this->conn->prepare($sqlL); $stmtL->execute();
            foreach($stmtL->fetchAll(PDO::FETCH_ASSOC) as $row) { $schedules[] = $row; }
        } catch (PDOException $e) {}

        // B. ASISTEN
        $sqlA = "SELECT ja.id_jadwal_asisten as id, ja.mata_kuliah as title, ja.ruangan_lab as location, 
                        ja.dosen, ja.kelas_lab,
                        ja.tanggal as start_date, ja.tanggal_selesai as end_date, ja.hari as day_of_week, 
                        ja.start_time, ja.end_time, ja.model_perulangan, ja.id_profil, 'asisten' as type,
                        p.nama as user_name
                 FROM jadwal_asisten ja JOIN profile p ON ja.id_profil = p.id_profil";
        $stmtA = $this->conn->prepare($sqlA); $stmtA->execute();
        foreach($stmtA->fetchAll(PDO::FETCH_ASSOC) as $row) { $schedules[] = $row; }

        // C. PIKET
        $sqlP = "SELECT jp.id_jadwal_piket as id, jp.subjek as title, 'Lab' as location, 
                        '' as dosen, '' as kelas,
                        jp.tanggal as start_date, jp.tanggal_selesai as end_date, jp.hari as day_of_week, 
                        jp.jam_mulai as start_time, jp.jam_selesai as end_time, jp.model_perulangan, 
                        jp.id_profil, 'piket' as type, p.nama as user_name 
                 FROM jadwal_piket jp JOIN profile p ON jp.id_profil = p.id_profil";
        $stmtP = $this->conn->prepare($sqlP); $stmtP->execute();
        foreach($stmtP->fetchAll(PDO::FETCH_ASSOC) as $row) { $schedules[] = $row; }

        // D. KULIAH
        $sqlK = "SELECT jk.id_jadwal_kuliah as id, jk.matkul as title, jk.ruangan as location, 
                        jk.dosen, jk.kelas,
                        jk.tanggal as start_date, jk.tanggal_selesai as end_date, jk.hari as day_of_week, 
                        jk.start_time, jk.end_time, jk.model_perulangan, jk.id_profil, 'kuliah' as type,
                        p.nama as user_name
                 FROM jadwal_kuliah jk JOIN profile p ON jk.id_profil = p.id_profil";
        $stmtK = $this->conn->prepare($sqlK); $stmtK->execute();
        foreach($stmtK->fetchAll(PDO::FETCH_ASSOC) as $row) { $schedules[] = $row; }

        return $schedules;
    }

    // =====================================================================
    // 2. GET USER SCHEDULES (Untuk Dashboard & Kalender User)
    // =====================================================================
    public function getAllUserSchedules($userId) {
        $schedules = [];
        
        // UMUM
        try {
            $sqlL = "SELECT id_jadwal_lab as id, nama_kegiatan as title, lokasi as location, 
                            tanggal as start_date, tanggal_selesai as end_date, hari as day_of_week, 
                            jam_mulai as start_time, jam_selesai as end_time, model_perulangan,
                            '' as dosen, '' as kelas,
                            0 as id_profil, 'umum' as type, 'Laboratorium' as user_name 
                     FROM jadwal_lab";
            $stmtL = $this->conn->prepare($sqlL); $stmtL->execute();
            foreach($stmtL->fetchAll(PDO::FETCH_ASSOC) as $row) { $schedules[] = $row; }
        } catch (PDOException $e) {}

        if (empty($userId)) return $schedules;
        
        // Ambil Data Profil User
        $stmtP = $this->conn->prepare("SELECT id_profil, nama FROM profile WHERE id_user = :uid");
        $stmtP->execute([':uid' => $userId]);
        $profil = $stmtP->fetch(PDO::FETCH_ASSOC);
        if (!$profil) return $schedules;
        $pId = $profil['id_profil']; $pName = $profil['nama'];

        // KULIAH (Milik Sendiri)
        $sqlK = "SELECT id_jadwal_kuliah as id, matkul as title, ruangan as location, 
                        dosen, kelas,
                        tanggal as start_date, tanggal_selesai as end_date, hari as day_of_week, 
                        start_time, end_time, model_perulangan, id_profil, 'kuliah' as type 
                 FROM jadwal_kuliah WHERE id_profil = :pid";
        $stmtK = $this->conn->prepare($sqlK); $stmtK->execute([':pid' => $pId]);
        foreach($stmtK->fetchAll(PDO::FETCH_ASSOC) as $row) { $row['user_name'] = $pName; $schedules[] = $row; }

        // ASISTEN (Milik Sendiri)
        $sqlA = "SELECT id_jadwal_asisten as id, mata_kuliah as title, ruangan_lab as location, 
                        dosen, kelas_lab,
                        tanggal as start_date, tanggal_selesai as end_date, hari as day_of_week, 
                        start_time, end_time, model_perulangan, id_profil, 'asisten' as type 
                 FROM jadwal_asisten WHERE id_profil = :pid";
        $stmtA = $this->conn->prepare($sqlA); $stmtA->execute([':pid' => $pId]);
        foreach($stmtA->fetchAll(PDO::FETCH_ASSOC) as $row) { $row['user_name'] = $pName; $schedules[] = $row; }

        // PIKET (Milik Sendiri)
        $sqlP = "SELECT id_jadwal_piket as id, subjek as title, 'Lab' as location, 
                        '' as dosen, '' as kelas,
                        tanggal as start_date, tanggal_selesai as end_date, hari as day_of_week, 
                        jam_mulai as start_time, jam_selesai as end_time, model_perulangan, id_profil, 'piket' as type 
                 FROM jadwal_piket WHERE id_profil = :pid";
        $stmtP = $this->conn->prepare($sqlP); $stmtP->execute([':pid' => $pId]);
        foreach($stmtP->fetchAll(PDO::FETCH_ASSOC) as $row) { $row['user_name'] = $pName; $schedules[] = $row; }

        return $schedules;
    }

    // =====================================================================
    // 3. GET SCHEDULE FOR DASHBOARD (Minggu Ini)
    // =====================================================================
    public function getUserScheduleForWeek($userId) {
        $allSchedules = $this->getAllUserSchedules($userId);
        $thisWeekSchedules = [];
        
        $monday = date('Y-m-d', strtotime('monday this week'));
        $sunday = date('Y-m-d', strtotime('sunday this week'));

        foreach ($allSchedules as $s) {
            $start = $s['start_date']; 
            $end = $s['end_date'] ?? $start; 
            $model = $s['model_perulangan'] ?? 'sekali';

            $isInWeek = false;
            
            if ($model == 'sekali') {
                if ($start >= $monday && $start <= $sunday) $isInWeek = true;
            } elseif ($model == 'rentang' || $model == 'mingguan') {
                if ($start <= $sunday && $end >= $monday) $isInWeek = true;
            }

            if ($isInWeek) {
                // Mapping ulang untuk view dashboard jika perlu
                $s['hari'] = $s['day_of_week']; // Pastikan kompatibilitas
                $s['tanggal'] = $s['start_date'];
                $thisWeekSchedules[] = $s;
            }
        }
        return $thisWeekSchedules;
    }

    private function formatEventData($data) {
        // Konversi tanggal ke format RFC3339 (Contoh: 2023-01-01T09:00:00+08:00)
        $startDateTime = date('Y-m-d\TH:i:s', strtotime($data['date'] . ' ' . $data['start_time']));
        $endDateTime   = date('Y-m-d\TH:i:s', strtotime(($data['end_date_repeat'] ?? $data['date']) . ' ' . $data['end_time']));
        
        $event = [
            'summary' => $data['title'],
            'location' => $data['location'] ?? 'Laboratorium',
            'description' => "Jadwal " . ucfirst($data['type']) . "\nDosen: " . ($data['dosen'] ?? '-') . "\nKelas: " . ($data['kelas'] ?? '-'),
            'start' => [
                'dateTime' => $startDateTime,
                'timeZone' => 'Asia/Makassar', // Sesuaikan Timezone
            ],
            'end' => [
                'dateTime' => $endDateTime,
                'timeZone' => 'Asia/Makassar',
            ],
        ];

        // Jika Berulang Mingguan
        if (isset($data['model_perulangan']) && $data['model_perulangan'] == 'mingguan' && isset($data['end_date_repeat'])) {
            $endRepeat = date('Ymd\THis\Z', strtotime($data['end_date_repeat'] . ' 23:59:59'));
            $event['recurrence'] = ["RRULE:FREQ=WEEKLY;UNTIL=$endRepeat"];
        }

        return $event;
    }

    public function createSchedule($data) {
        try {
            $this->conn->beginTransaction();

            $type = $data['type']; 
            $model = $data['model_perulangan']; 
            $tglMulai = $data['date'];
            $tglSelesai = ($model == 'sekali') ? $tglMulai : ($data['end_date_repeat'] ?? $tglMulai);
            $hari = date('N', strtotime($tglMulai)); // 1 (Senin) - 7 (Minggu)

            $lastId = null;

            // A. INSERT KE TABEL SPESIFIK
            if ($type == 'umum') {
                $sql = "INSERT INTO jadwal_lab (nama_kegiatan, lokasi, tanggal, tanggal_selesai, hari, jam_mulai, jam_selesai, model_perulangan) 
                        VALUES (:title, :loc, :tgl, :tgl_end, :hari, :start, :end, :model)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':title'=>$data['title'], ':loc'=>$data['location'], ':tgl'=>$tglMulai, ':tgl_end'=>$tglSelesai, ':hari'=>$hari, ':start'=>$data['start_time'], ':end'=>$data['end_time'], ':model'=>$model]);
                $lastId = $this->conn->lastInsertId();
            
            } elseif ($type == 'piket') {
                $sql = "INSERT INTO jadwal_piket (id_profil, subjek, tanggal, tanggal_selesai, hari, jam_mulai, jam_selesai, model_perulangan) 
                        VALUES (:pid, :title, :tgl, :tgl_end, :hari, :start, :end, :model)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':pid'=>$data['user_id'], ':title'=>$data['title'], ':tgl'=>$tglMulai, ':tgl_end'=>$tglSelesai, ':hari'=>$hari, ':start'=>$data['start_time'], ':end'=>$data['end_time'], ':model'=>$model]);
                $lastId = $this->conn->lastInsertId();

            } elseif ($type == 'kuliah') {
                $sql = "INSERT INTO jadwal_kuliah (id_profil, matkul, ruangan, dosen, kelas, tanggal, tanggal_selesai, hari, start_time, end_time, model_perulangan, tipe) 
                        VALUES (:pid, :title, :loc, :dosen, :kelas, :tgl, :tgl_end, :hari, :start, :end, :model, 'Teori')";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    ':pid'=>$data['user_id'], ':title'=>$data['title'], ':loc'=>$data['location'], 
                    ':dosen'=>$data['dosen']??'', ':kelas'=>$data['kelas']??'', 
                    ':tgl'=>$tglMulai, ':tgl_end'=>$tglSelesai, ':hari'=>$hari, 
                    ':start'=>$data['start_time'], ':end'=>$data['end_time'], 
                    ':model'=>$model
                ]);
                $lastId = $this->conn->lastInsertId();

            } else { // asisten
                $sql = "INSERT INTO jadwal_asisten (id_profil, mata_kuliah, ruangan_lab, dosen, kelas_lab, tanggal, tanggal_selesai, hari, model_perulangan, start_time, end_time) 
                        VALUES (:pid, :title, :loc, :dosen, :kelas, :tgl, :tgl_end, :hari, :model, :start, :end)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':pid'=>$data['user_id'], ':title'=>$data['title'], ':loc'=>$data['location'], ':dosen'=>$data['dosen']??'', ':kelas'=>$data['kelas']??'', ':tgl'=>$tglMulai, ':tgl_end'=>$tglSelesai, ':hari'=>$hari, ':model'=>$model, ':start'=>$data['start_time'], ':end'=>$data['end_time']]);
                $lastId = $this->conn->lastInsertId();
            }

            // B. INTEGRASI GOOGLE CALENDAR
            $googleEventId = null;
            
            // Kita butuh ID User (dari tabel User) untuk ambil token. 
            // $data['user_id'] di sini adalah ID PROFIL. Kita perlu cari ID USER nya.
            if (!empty($data['user_id'])) {
                // Ambil id_user dari id_profil
                $stmtU = $this->conn->prepare("SELECT id_user FROM profile WHERE id_profil = :pid");
                $stmtU->execute([':pid' => $data['user_id']]);
                $userRow = $stmtU->fetch(PDO::FETCH_ASSOC);

                if ($userRow) {
                    $realUserId = $userRow['id_user'];
                    
                    // Inisialisasi Google Client
                    $google = new GoogleClient();
                    $accessToken = $google->getValidAccessToken($realUserId);

                    if ($accessToken) {
                        $eventPayload = $this->formatEventData($data);
                        $gResponse = $google->createEvent($accessToken, $eventPayload);
                        
                        if (isset($gResponse['id'])) {
                            $googleEventId = $gResponse['id'];
                        }
                    }
                }
            }

            // C. INSERT KE JADWAL_FULL (Mapping ID Lokal & Google ID)
            $colName = 'id_jadwal_' . $type; // e.g., id_jadwal_kuliah
            if ($type == 'umum') $colName = 'id_jadwal_lab'; // Mapping khusus untuk umum

            $sqlFull = "INSERT INTO jadwal_full ($colName, google_calendar_API) VALUES (:ref_id, :g_id)";
            $stmtFull = $this->conn->prepare($sqlFull);
            $stmtFull->execute([':ref_id' => $lastId, ':g_id' => $googleEventId]);

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            // Uncomment line bawah untuk debugging jika error
            // die($e->getMessage());
            return false;
        }
    }

    // =====================================================================
    // 2. UPDATE SCHEDULE (SYNC GOOGLE)
    // =====================================================================
    public function updateSchedule($data) {
        try {
            $type = $data['type']; 
            $model = $data['model_perulangan']; 
            $tglMulai = $data['date'];
            $tglSelesai = ($model == 'sekali') ? $tglMulai : ($data['end_date_repeat'] ?? $tglMulai);
            $hari = date('N', strtotime($tglMulai));

            // A. UPDATE TABEL LOKAL
            if ($type == 'umum') {
                $sql = "UPDATE jadwal_lab SET nama_kegiatan=:title, lokasi=:loc, tanggal=:tgl, tanggal_selesai=:tgl_end, hari=:hari, jam_mulai=:start, jam_selesai=:end, model_perulangan=:model WHERE id_jadwal_lab=:id";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':id'=>$data['id'], ':title'=>$data['title'], ':loc'=>$data['location'], ':tgl'=>$tglMulai, ':tgl_end'=>$tglSelesai, ':hari'=>$hari, ':start'=>$data['start_time'], ':end'=>$data['end_time'], ':model'=>$model]);
            } elseif ($type == 'piket') {
                $sql = "UPDATE jadwal_piket SET id_profil=:pid, subjek=:title, tanggal=:tgl, tanggal_selesai=:tgl_end, hari=:hari, jam_mulai=:start, jam_selesai=:end, model_perulangan=:model WHERE id_jadwal_piket=:id";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':id'=>$data['id'], ':pid'=>$data['user_id'], ':title'=>$data['title'], ':tgl'=>$tglMulai, ':tgl_end'=>$tglSelesai, ':hari'=>$hari, ':start'=>$data['start_time'], ':end'=>$data['end_time'], ':model'=>$model]);
            } elseif ($type == 'kuliah') {
                $sql = "UPDATE jadwal_kuliah SET id_profil=:pid, matkul=:title, ruangan=:loc, dosen=:dosen, kelas=:kelas, tanggal=:tgl, tanggal_selesai=:tgl_end, hari=:hari, start_time=:start, end_time=:end, model_perulangan=:model WHERE id_jadwal_kuliah=:id";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    ':id'=>$data['id'], ':pid'=>$data['user_id'], ':title'=>$data['title'], ':loc'=>$data['location'], 
                    ':dosen'=>$data['dosen']??'', ':kelas'=>$data['kelas']??'', 
                    ':tgl'=>$tglMulai, ':tgl_end'=>$tglSelesai, ':hari'=>$hari, 
                    ':start'=>$data['start_time'], ':end'=>$data['end_time'], ':model'=>$model
                ]);
            } else { // asisten
                $sql = "UPDATE jadwal_asisten SET id_profil=:pid, mata_kuliah=:title, ruangan_lab=:loc, dosen=:dosen, kelas_lab=:kelas, tanggal=:tgl, tanggal_selesai=:tgl_end, hari=:hari, model_perulangan=:model, start_time=:start, end_time=:end WHERE id_jadwal_asisten=:id";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':id'=>$data['id'], ':pid'=>$data['user_id'], ':title'=>$data['title'], ':loc'=>$data['location'], ':dosen'=>$data['dosen']??'', ':kelas'=>$data['kelas']??'', ':tgl'=>$tglMulai, ':tgl_end'=>$tglSelesai, ':hari'=>$hari, ':model'=>$model, ':start'=>$data['start_time'], ':end'=>$data['end_time']]);
            }

            // B. UPDATE GOOGLE CALENDAR
            if (!empty($data['user_id'])) {
                // 1. Cari Google Event ID di tabel jadwal_full
                $colName = ($type == 'umum') ? 'id_jadwal_lab' : 'id_jadwal_' . $type;
                $stmtGet = $this->conn->prepare("SELECT google_calendar_API FROM jadwal_full WHERE $colName = :id");
                $stmtGet->execute([':id' => $data['id']]);
                $row = $stmtGet->fetch(PDO::FETCH_ASSOC);
                $gEventId = $row['google_calendar_API'] ?? null;

                // 2. Ambil User ID Asli
                $stmtU = $this->conn->prepare("SELECT id_user FROM profile WHERE id_profil = :pid");
                $stmtU->execute([':pid' => $data['user_id']]);
                $userRow = $stmtU->fetch(PDO::FETCH_ASSOC);

                if ($gEventId && $userRow) {
                    $google = new GoogleClient();
                    $accessToken = $google->getValidAccessToken($userRow['id_user']);
                    
                    if ($accessToken) {
                        $eventPayload = $this->formatEventData($data);
                        $google->updateEvent($accessToken, $gEventId, $eventPayload);
                    }
                }
            }

            return true;
        } catch (Exception $e) { return false; }
    }

    // =====================================================================
    // 3. DELETE SCHEDULE (SYNC GOOGLE)
    // =====================================================================
    public function deleteSchedule($id, $type) {
        try {
            // A. HAPUS DARI GOOGLE CALENDAR DULU (Sebelum data lokal hilang)
            
            // 1. Tentukan nama kolom FK
            $colName = ($type == 'umum') ? 'id_jadwal_lab' : 'id_jadwal_' . $type;
            
            // 2. Mapping tabel spesifik untuk ambil id_profil (karena jadwal_full tidak punya id_profil)
            $tableMap = [
                'kuliah' => 'jadwal_kuliah', 'asisten' => 'jadwal_asisten', 
                'piket' => 'jadwal_piket', 'umum' => 'jadwal_lab'
            ];
            $tableName = $tableMap[$type] ?? null;
            $pkName = ($type == 'umum') ? 'id_jadwal_lab' : 'id_' . $tableName; 

            if ($tableName && $type != 'umum') {
                // Join jadwal_full dengan tabel spesifik untuk dapat id_profil
                $sqlGet = "SELECT jf.google_calendar_API, t.id_profil 
                           FROM jadwal_full jf 
                           JOIN $tableName t ON jf.$colName = t.$pkName 
                           WHERE jf.$colName = :id";
                
                $stmtGet = $this->conn->prepare($sqlGet);
                $stmtGet->execute([':id' => $id]);
                $row = $stmtGet->fetch(PDO::FETCH_ASSOC);

                if ($row && !empty($row['google_calendar_API']) && !empty($row['id_profil'])) {
                    // Ambil ID User asli
                    $stmtU = $this->conn->prepare("SELECT id_user FROM profile WHERE id_profil = :pid");
                    $stmtU->execute([':pid' => $row['id_profil']]);
                    $uRow = $stmtU->fetch(PDO::FETCH_ASSOC);

                    if ($uRow) {
                        $google = new GoogleClient();
                        $accessToken = $google->getValidAccessToken($uRow['id_user']);
                        if ($accessToken) {
                            $google->deleteEvent($accessToken, $row['google_calendar_API']);
                        }
                    }
                }
            }

            // B. HAPUS DATA LOKAL (CASCADE akan hapus di jadwal_full otomatis)
            $sqlDelete = "DELETE FROM $tableName WHERE $pkName = :id";
            if ($type == 'umum') $sqlDelete = "DELETE FROM jadwal_lab WHERE id_jadwal_lab = :id"; // Override

            $stmt = $this->conn->prepare($sqlDelete); 
            return $stmt->execute([':id' => $id]);

        } catch (Exception $e) { return false; }
    }
    
    public function getUpcomingSchedules() { return []; }
}
?>