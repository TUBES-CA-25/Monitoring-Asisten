<?php
require_once '../app/core/GoogleClient.php';
class ScheduleModel {
    private $conn;
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    // [DIUBAH - Modul 5 lanjutan] Sebelumnya SEMUA asisten (role='User')
    // diundang sebagai attendees pada event Lab/Umum di kalender Admin
    // (perlu accept undangan). Sesuai permintaan, asisten sekarang dapat
    // SALINAN event langsung di kalender mereka sendiri (lihat
    // syncLabEventForAssistants()) - attendee pada event master kini HANYA
    // role='Kepala Lab' (tetap via mekanisme undangan, perlu approve).
    private function getKepalaLabAttendees() {
        $stmt = $this->conn->prepare("SELECT email FROM user WHERE role = 'Kepala Lab'");
        $stmt->execute();
        $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $attendees = [];
        foreach($emails as $email) {
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $attendees[] = ['email' => $email];
            }
        }
        return $attendees;
    }

    // [BARU - Modul 5 lanjutan] Daftar id_user seluruh asisten (role='User'),
    // untuk dipakai syncLabEventForAssistants() membuat/mengupdate/menghapus
    // salinan event Jadwal Lab di kalender masing-masing.
    private function getAllAssistantUserIds() {
        $stmt = $this->conn->prepare("SELECT id_user FROM user WHERE role = 'User'");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * [BARU - Modul 5 lanjutan] Sinkronkan SALINAN event Jadwal Lab/Umum ke
     * kalender Google PRIBADI setiap asisten (pakai token OAuth masing-
     * masing - auto muncul tanpa undangan). Status per-asisten dilacak di
     * tabel `jadwal_lab_sync` (UNIQUE id_jadwal_lab+id_user) agar
     * update/delete/retry berikutnya tahu google_event_id mana yang harus
     * diupdate/dihapus.
     *
     * - $isDelete=false (create/update): loop SEMUA asisten saat ini (agar
     *   asisten yang baru ditambahkan setelah jadwal dibuat juga kebagian),
     *   upsert baris jadwal_lab_sync sesuai hasil createEvent/updateEvent.
     * - $isDelete=true: hapus salinan event di kalender setiap asisten yang
     *   sebelumnya berhasil disinkronkan, lalu hapus semua baris
     *   jadwal_lab_sync untuk jadwal ini.
     */
    private function syncLabEventForAssistants($idJadwalLab, $eventPayload, $isDelete = false) {
        $google = new GoogleClient();

        if ($isDelete) {
            $stmt = $this->conn->prepare("SELECT id_user, google_event_id FROM jadwal_lab_sync WHERE id_jadwal_lab = :id AND google_event_id IS NOT NULL");
            $stmt->execute([':id' => $idJadwalLab]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $accessToken = $google->getValidAccessToken($row['id_user']);
                if ($accessToken) {
                    // Hasil tidak diperiksa - lihat catatan deleteSchedule()
                    // mengenai keterbatasan retry untuk delete.
                    $google->deleteEvent($accessToken, $row['google_event_id']);
                }
            }

            $stmtDel = $this->conn->prepare("DELETE FROM jadwal_lab_sync WHERE id_jadwal_lab = :id");
            $stmtDel->execute([':id' => $idJadwalLab]);
            return;
        }

        // Ambil status sync existing per asisten (jika ada)
        $stmtExisting = $this->conn->prepare("SELECT id_user, google_event_id FROM jadwal_lab_sync WHERE id_jadwal_lab = :id");
        $stmtExisting->execute([':id' => $idJadwalLab]);
        $existing = [];
        foreach ($stmtExisting->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $existing[$row['id_user']] = $row['google_event_id'];
        }

        $stmtUpsert = $this->conn->prepare(
            "INSERT INTO jadwal_lab_sync (id_jadwal_lab, id_user, google_event_id, sync_status)
             VALUES (:jid, :uid, :gid, :status)
             ON DUPLICATE KEY UPDATE google_event_id = :gid2, sync_status = :status2"
        );

        foreach ($this->getAllAssistantUserIds() as $userId) {
            $gEventId = $existing[$userId] ?? null;
            $status = 'skipped';

            $accessToken = $google->getValidAccessToken($userId);
            if ($accessToken) {
                if (!empty($gEventId)) {
                    $gResponse = $google->updateEvent($accessToken, $gEventId, $eventPayload);
                    $status = (is_array($gResponse) && isset($gResponse['id'])) ? 'synced' : 'failed';
                } else {
                    $gResponse = $google->createEvent($accessToken, $eventPayload);
                    if (isset($gResponse['id'])) {
                        $gEventId = $gResponse['id'];
                        $status = 'synced';
                    } else {
                        $status = 'failed';
                    }
                }
            } else {
                // Belum connect Google. Jika sebelumnya pernah synced
                // (jarang, tapi mis. token dicabut user), tandai failed agar
                // bisa di-retry; jika belum pernah, tetap skipped.
                $status = empty($gEventId) ? 'skipped' : 'failed';
            }

            $stmtUpsert->execute([
                ':jid' => $idJadwalLab, ':uid' => $userId, ':gid' => $gEventId, ':status' => $status,
                ':gid2' => $gEventId, ':status2' => $status
            ]);
        }
    }

    public function getAllSchedules() {
        $schedules = [];

        try {
            $sqlL = "SELECT jl.id_jadwal_lab as id, jl.nama_kegiatan as title, jl.lokasi as location, 
                            jl.tanggal as start_date, jl.tanggal_selesai as end_date, jl.hari as day_of_week, 
                            jl.jam_mulai as start_time, jl.jam_selesai as end_time, jl.model_perulangan,
                            '' as dosen, NULL as id_dosen, '' as kelas,
                            0 as id_profil, 'umum' as type, 'Laboratorium' as user_name,
                            jf.google_event_id,
                            -- [BARU - Modul 5 lanjutan] sync_status agregat: 'failed' jika
                            -- event master ATAU salah satu salinan per-asisten gagal, agar
                            -- banner retry tetap tampil walau master 'synced'.
                            CASE
                                WHEN jf.sync_status = 'failed' OR EXISTS (
                                    SELECT 1 FROM jadwal_lab_sync jls
                                    WHERE jls.id_jadwal_lab = jl.id_jadwal_lab AND jls.sync_status = 'failed'
                                ) THEN 'failed'
                                ELSE jf.sync_status
                            END as sync_status
                     FROM jadwal_lab jl
                     LEFT JOIN jadwal_full jf ON jf.id_jadwal_lab = jl.id_jadwal_lab";
            $stmtL = $this->conn->prepare($sqlL); $stmtL->execute();
            foreach($stmtL->fetchAll(PDO::FETCH_ASSOC) as $row) { $schedules[] = $row; }
        } catch (PDOException $e) {}

        $sqlA = "SELECT ja.id_jadwal_asisten as id, ja.mata_kuliah as title, ja.ruangan_lab as location, 
                        ja.dosen, ja.id_dosen, ja.kelas_lab as kelas,
                        ja.tanggal as start_date, ja.tanggal_selesai as end_date, ja.hari as day_of_week, 
                        ja.start_time, ja.end_time, ja.model_perulangan, ja.id_profil, 'asisten' as type,
                        p.nama as user_name,
                        jf.google_event_id, jf.sync_status
                 FROM jadwal_asisten ja JOIN profile p ON ja.id_profil = p.id_profil
                 LEFT JOIN jadwal_full jf ON jf.id_jadwal_asisten = ja.id_jadwal_asisten";
        $stmtA = $this->conn->prepare($sqlA); $stmtA->execute();
        foreach($stmtA->fetchAll(PDO::FETCH_ASSOC) as $row) { $schedules[] = $row; }

        $sqlP = "SELECT jp.id_jadwal_piket as id, jp.subjek as title, 'Lab' as location, 
                        '' as dosen, NULL as id_dosen, '' as kelas,
                        jp.tanggal as start_date, jp.tanggal_selesai as end_date, jp.hari as day_of_week, 
                        jp.jam_mulai as start_time, jp.jam_selesai as end_time, jp.model_perulangan, 
                        jp.id_profil, 'piket' as type, p.nama as user_name,
                        jf.google_event_id, jf.sync_status
                 FROM jadwal_piket jp JOIN profile p ON jp.id_profil = p.id_profil
                 LEFT JOIN jadwal_full jf ON jf.id_jadwal_piket = jp.id_jadwal_piket";
        $stmtP = $this->conn->prepare($sqlP); $stmtP->execute();
        foreach($stmtP->fetchAll(PDO::FETCH_ASSOC) as $row) { $schedules[] = $row; }

        $sqlK = "SELECT jk.id_jadwal_kuliah as id, jk.matkul as title, jk.ruangan as location, 
                        jk.dosen, jk.id_dosen, jk.kelas,
                        jk.tanggal as start_date, jk.tanggal_selesai as end_date, jk.hari as day_of_week, 
                        jk.start_time, jk.end_time, jk.model_perulangan, jk.id_profil, 'kuliah' as type,
                        p.nama as user_name,
                        jf.google_event_id, jf.sync_status
                 FROM jadwal_kuliah jk JOIN profile p ON jk.id_profil = p.id_profil
                 LEFT JOIN jadwal_full jf ON jf.id_jadwal_kuliah = jk.id_jadwal_kuliah";
        $stmtK = $this->conn->prepare($sqlK); $stmtK->execute();
        foreach($stmtK->fetchAll(PDO::FETCH_ASSOC) as $row) { $schedules[] = $row; }

        return $schedules;
    }

    public function getAllUserSchedules($userId) {
    $schedules = [];
        try {
            $sqlL = "SELECT jl.id_jadwal_lab as id, jl.nama_kegiatan as title, jl.lokasi as location, 
                            jl.tanggal as start_date, jl.tanggal_selesai as end_date, jl.hari as day_of_week, 
                            jl.jam_mulai as start_time, jl.jam_selesai as end_time, jl.model_perulangan,
                            '' as dosen, NULL as id_dosen, '' as kelas,
                            0 as id_profil, 'umum' as type, 'Laboratorium' as user_name,
                            jf.google_event_id,
                            CASE
                                WHEN jf.sync_status = 'failed' OR EXISTS (
                                    SELECT 1 FROM jadwal_lab_sync jls
                                    WHERE jls.id_jadwal_lab = jl.id_jadwal_lab AND jls.sync_status = 'failed'
                                ) THEN 'failed'
                                ELSE jf.sync_status
                            END as sync_status
                    FROM jadwal_lab jl
                    LEFT JOIN jadwal_full jf ON jf.id_jadwal_lab = jl.id_jadwal_lab";
            $stmtL = $this->conn->prepare($sqlL);
            $stmtL->execute();
            foreach($stmtL->fetchAll(PDO::FETCH_ASSOC) as $row) { $schedules[] = $row; }
        } catch (PDOException $e) {}

        if (empty($userId)) return $schedules;
        
        $stmtP = $this->conn->prepare("SELECT id_profil, nama FROM profile WHERE id_user = :uid");
        $stmtP->execute([':uid' => $userId]);
        $profil = $stmtP->fetch(PDO::FETCH_ASSOC);

        if (!$profil) {
            $pId = $userId; 
            $pName = "User";
        } else {
            $pId = $profil['id_profil'];
            $pName = $profil['nama'];
        }

        $queries = [
            'kuliah' => "SELECT jk.id_jadwal_kuliah as id, jk.matkul as title, jk.ruangan as location, jk.dosen, jk.id_dosen, jk.kelas, jk.tanggal as start_date, jk.tanggal_selesai as end_date, jk.hari as day_of_week, jk.start_time, jk.end_time, jk.model_perulangan, jk.id_profil, 'kuliah' as type, jf.google_event_id, jf.sync_status FROM jadwal_kuliah jk LEFT JOIN jadwal_full jf ON jf.id_jadwal_kuliah = jk.id_jadwal_kuliah WHERE jk.id_profil = :pid",
            'asisten' => "SELECT ja.id_jadwal_asisten as id, ja.mata_kuliah as title, ja.ruangan_lab as location, ja.dosen, ja.id_dosen, ja.kelas_lab as kelas, ja.tanggal as start_date, ja.tanggal_selesai as end_date, ja.hari as day_of_week, ja.start_time, ja.end_time, ja.model_perulangan, ja.id_profil, 'asisten' as type, jf.google_event_id, jf.sync_status FROM jadwal_asisten ja LEFT JOIN jadwal_full jf ON jf.id_jadwal_asisten = ja.id_jadwal_asisten WHERE ja.id_profil = :pid",
            'piket' => "SELECT jp.id_jadwal_piket as id, jp.subjek as title, 'Lab' as location, '' as dosen, NULL as id_dosen, '' as kelas, jp.tanggal as start_date, jp.tanggal_selesai as end_date, jp.hari as day_of_week, jp.jam_mulai as start_time, jp.jam_selesai as end_time, jp.model_perulangan, jp.id_profil, 'piket' as type, jf.google_event_id, jf.sync_status FROM jadwal_piket jp LEFT JOIN jadwal_full jf ON jf.id_jadwal_piket = jp.id_jadwal_piket WHERE jp.id_profil = :pid"
        ];

        foreach ($queries as $type => $sql) {
            try {
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':pid' => $pId]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($results as $row) {
                    $row['user_name'] = $pName;
                    $schedules[] = $row;
                }
            } catch (PDOException $e) {
                // Log error jika diperlukan agar tidak silent failure
            }
        }
        return $schedules;
    }

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
                $s['hari'] = $s['day_of_week']; 
                $s['tanggal'] = $s['start_date'];
                $thisWeekSchedules[] = $s;
            }
        }
        return $thisWeekSchedules;
    }

    private function formatEventData($data, $attendees = []) {
        $startDateTime = date('Y-m-d\TH:i:s', strtotime($data['date'] . ' ' . $data['start_time']));
        $endDateBase = ($data['model_perulangan'] == 'sekali') ? $data['date'] : ($data['date']);
        $endDateTime = date('Y-m-d\TH:i:s', strtotime($endDateBase . ' ' . $data['end_time']));
        
        $desc = "Jadwal " . ucfirst($data['type']);
        if(!empty($data['dosen'])) $desc .= "\nDosen: " . $data['dosen'];
        if(!empty($data['kelas'])) $desc .= "\nKelas: " . $data['kelas'];

        $event = [
            'summary' => $data['title'],
            'location' => $data['location'] ?? 'Laboratorium',
            'description' => $desc,
            'start' => ['dateTime' => $startDateTime, 'timeZone' => 'Asia/Makassar'],
            'end' => ['dateTime' => $endDateTime, 'timeZone' => 'Asia/Makassar'],
        ];

        if (!empty($attendees)) {
            $event['attendees'] = $attendees;
        }

        if (isset($data['model_perulangan']) && $data['model_perulangan'] == 'mingguan' && !empty($data['end_date_repeat'])) {
            $untilDate = date('Ymd\THis\Z', strtotime($data['end_date_repeat'] . ' 23:59:59'));
            $event['recurrence'] = ["RRULE:FREQ=WEEKLY;UNTIL=$untilDate"];
        }
        return $event;
    }

    private function getRealUserId($profileId) {
        $stmt = $this->conn->prepare("SELECT id_user FROM profile WHERE id_profil = :pid");
        $stmt->execute([':pid' => $profileId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ? $res['id_user'] : null;
    }

    /**
     * [BARU - Modul Dosen] Daftar seluruh dosen master, untuk mengisi
     * dropdown "Dosen Pengampu" di form Jadwal Kuliah & Jadwal Asisten.
     */
    public function getAllDosen() {
        $stmt = $this->conn->prepare("SELECT id_dosen, nama_dosen FROM dosen ORDER BY nama_dosen ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * [BARU - Modul Lab] Daftar seluruh laboratorium master, untuk mengisi
     * dropdown "Lokasi/Ruangan" di form Jadwal (Umum, Asisten, Kuliah).
     */
    public function getAllLabs() {
        $stmt = $this->conn->prepare("SELECT id_lab, nama_lab FROM lab ORDER BY nama_lab ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * [BARU - Modul Dosen] Cari dosen berdasarkan nama (case-insensitive,
     * trimmed) di tabel master. Jika ketemu, kembalikan id_dosen yang sudah
     * ada (mencegah duplikat akibat beda kapitalisasi/spasi). Jika tidak,
     * buat baris baru ("Tambah Dosen Baru" inline dari form jadwal).
     * Mengembalikan null jika $nama kosong.
     */
    public function findOrCreateDosen($nama) {
        $nama = trim((string) $nama);
        if ($nama === '') return null;

        $stmtFind = $this->conn->prepare("SELECT id_dosen, nama_dosen FROM dosen WHERE LOWER(TRIM(nama_dosen)) = LOWER(:nama) LIMIT 1");
        $stmtFind->execute([':nama' => $nama]);
        $existing = $stmtFind->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            return ['id' => (int) $existing['id_dosen'], 'nama' => $existing['nama_dosen']];
        }

        $stmtIns = $this->conn->prepare("INSERT INTO dosen (nama_dosen) VALUES (:nama)");
        $stmtIns->execute([':nama' => $nama]);
        return ['id' => (int) $this->conn->lastInsertId(), 'nama' => $nama];
    }

    /**
     * [BARU - Modul Dosen] Resolve dosen pengampu dari input form/API ke
     * (id_dosen, nama_dosen) untuk disimpan di jadwal_kuliah/jadwal_asisten.
     * Mendukung 2 sumber input:
     *   - $data['dosen_baru']: teks "Tambah Dosen Baru" dari dropdown form
     *     web (diprioritaskan jika diisi).
     *   - $data['id_dosen']: id dari dropdown form web (dosen yang sudah ada).
     *   - $data['dosen']: fallback teks bebas (dipakai oleh API mobile yang
     *     mengirim nama dosen sebagai string, BUKAN id_dosen).
     * Mengembalikan ['id_dosen' => int|null, 'nama' => string].
     */
    private function resolveDosen($data) {
        // 1. "Tambah Dosen Baru" dari form web - prioritas utama.
        if (!empty($data['dosen_baru'])) {
            $res = $this->findOrCreateDosen($data['dosen_baru']);
            if ($res) return ['id_dosen' => $res['id'], 'nama' => $res['nama']];
        }

        // 2. Pilihan dropdown (id_dosen) dari form web.
        if (!empty($data['id_dosen']) && ctype_digit((string) $data['id_dosen'])) {
            $stmt = $this->conn->prepare("SELECT id_dosen, nama_dosen FROM dosen WHERE id_dosen = :id");
            $stmt->execute([':id' => (int) $data['id_dosen']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) return ['id_dosen' => (int) $row['id_dosen'], 'nama' => $row['nama_dosen']];
        }

        // 3. Fallback: teks bebas $data['dosen'] (mis. dari API mobile yang
        //    belum memakai dropdown) - cari/buat di master agar tetap
        //    konsisten ke depannya.
        if (!empty($data['dosen'])) {
            $res = $this->findOrCreateDosen($data['dosen']);
            if ($res) return ['id_dosen' => $res['id'], 'nama' => $res['nama']];
        }

        // 4. Tidak ada input dosen sama sekali.
        return ['id_dosen' => null, 'nama' => ''];
    }

    public function createSchedule($data) {
        $type = $data['type'];
        $model = $data['model_perulangan'];
        $tglMulai = $data['date'];
        $tglSelesai = ($model == 'sekali') ? $tglMulai : ($data['end_date_repeat'] ?? $tglMulai);
        $hari = date('N', strtotime($tglMulai));
        $lastId = null;

        // [PERBAIKAN] Transaksi inti HANYA mencakup penyimpanan data jadwal
        // itu sendiri (jadwal_lab/jadwal_piket/jadwal_kuliah/jadwal_asisten).
        // Sebelumnya, sinkronisasi Google Calendar (di bawah) juga berada
        // di DALAM transaksi & blok catch(Exception) yang sama - jika kode
        // sinkronisasi melempar Error/TypeError (bukan Exception, sehingga
        // TIDAK tertangkap), seluruh transaksi gagal TANPA rollback/return,
        // mengakibatkan: (a) jadwal GAGAL TERSIMPAN, dan (b) controller
        // tidak pernah mencapai header("Location: ...") -> browser
        // "nyangkut" di halaman /admin/addSchedule yang kosong. Sekarang
        // sinkronisasi Google dipisah & dibungkus catch(Throwable) sendiri
        // di bawah, sehingga error di sana TIDAK MEMBATALKAN jadwal yang
        // sudah tersimpan.
        try {
            $this->conn->beginTransaction();

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
                // [BARU - Modul Dosen] resolve dosen pengampu ke id_dosen +
                // nama kanonik (disimpan juga ke kolom dosen sebagai cache).
                $dosenInfo = $this->resolveDosen($data);
                $data['dosen'] = $dosenInfo['nama']; // dipakai formatEventData() di bawah

                $sql = "INSERT INTO jadwal_kuliah (id_profil, matkul, ruangan, dosen, id_dosen, kelas, tanggal, tanggal_selesai, hari, start_time, end_time, model_perulangan, tipe) 
                        VALUES (:pid, :title, :loc, :dosen, :id_dosen, :kelas, :tgl, :tgl_end, :hari, :start, :end, :model, 'Teori')";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':pid'=>$data['user_id'], ':title'=>$data['title'], ':loc'=>$data['location'], ':dosen'=>$dosenInfo['nama'], ':id_dosen'=>$dosenInfo['id_dosen'], ':kelas'=>$data['kelas']??'', ':tgl'=>$tglMulai, ':tgl_end'=>$tglSelesai, ':hari'=>$hari, ':start'=>$data['start_time'], ':end'=>$data['end_time'], ':model'=>$model]);
                $lastId = $this->conn->lastInsertId();
            } else {
                // [BARU - Modul Dosen] sama seperti type='kuliah' di atas.
                $dosenInfo = $this->resolveDosen($data);
                $data['dosen'] = $dosenInfo['nama'];

                $sql = "INSERT INTO jadwal_asisten (id_profil, mata_kuliah, ruangan_lab, dosen, id_dosen, kelas_lab, tanggal, tanggal_selesai, hari, model_perulangan, start_time, end_time) 
                        VALUES (:pid, :title, :loc, :dosen, :id_dosen, :kelas, :tgl, :tgl_end, :hari, :model, :start, :end)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':pid'=>$data['user_id'], ':title'=>$data['title'], ':loc'=>$data['location'], ':dosen'=>$dosenInfo['nama'], ':id_dosen'=>$dosenInfo['id_dosen'], ':kelas'=>$data['kelas']??'', ':tgl'=>$tglMulai, ':tgl_end'=>$tglSelesai, ':hari'=>$hari, ':model'=>$model, ':start'=>$data['start_time'], ':end'=>$data['end_time']]);
                $lastId = $this->conn->lastInsertId();
            }

            $this->conn->commit();
        } catch (\Throwable $e) {
            $this->conn->rollBack();
            return false;
        }

        // --- Sinkronisasi Google Calendar (di luar transaksi inti) ---
        // Jadwal di atas SUDAH TERSIMPAN. Apapun yang terjadi di bawah ini
        // hanya memengaruhi status sinkronisasi (jadwal_full.sync_status),
        // BUKAN keberadaan jadwalnya.
        $googleEventId = null;
        $syncStatus = 'skipped';

        try {
            $attendees = [];
            if ($type == 'umum') {
                $attendees = $this->getKepalaLabAttendees();
            }

            // Tentukan akun Google mana yang dipakai untuk membuat event:
            // - Jadwal pribadi (kuliah/asisten/piket): pakai akun Google milik
            //   asisten yang bersangkutan (user_id di sini = id_profil asisten).
            // - Jadwal umum (Lab): event "master" dibuat di akun Google Admin
            //   pembuat jadwal, dengan role='Kepala Lab' diundang sebagai
            //   attendees (perlu approve). Salinan terpisah untuk setiap
            //   asisten dibuat langsung di kalender masing-masing - lihat
            //   syncLabEventForAssistants() di bawah.
            $realUserId = null;
            if ($type == 'umum') {
                $realUserId = $data['creator_user_id'] ?? null;
            } elseif (!empty($data['user_id'])) {
                $realUserId = $this->getRealUserId($data['user_id']);
            }

            if ($realUserId) {
                $google = new GoogleClient();
                $accessToken = $google->getValidAccessToken($realUserId);

                if ($accessToken) {
                    $eventPayload = $this->formatEventData($data, $attendees); 
                    $gResponse = $google->createEvent($accessToken, $eventPayload);
                    if (isset($gResponse['id'])) {
                        $googleEventId = $gResponse['id'];
                        $syncStatus = 'synced';
                    } else {
                        // [BARU] Akun Google terhubung tapi createEvent gagal
                        // (network/API error) - kandidat "Retry failed sync".
                        $syncStatus = 'failed';
                    }
                }
                // Jika $accessToken null (belum connect Google), tetap 'skipped'.
            }
        } catch (\Throwable $e) {
            $syncStatus = 'failed';
        }

        $colName = 'id_jadwal_' . $type;
        if ($type == 'umum') $colName = 'id_jadwal_lab';

        try {
            $sqlFull = "INSERT INTO jadwal_full ($colName, google_event_id, sync_status) VALUES (:ref_id, :g_id, :sync)";
            $stmtFull = $this->conn->prepare($sqlFull);
            $stmtFull->execute([':ref_id' => $lastId, ':g_id' => $googleEventId, ':sync' => $syncStatus]);

            // [BARU - Modul 5 lanjutan] Jadwal Lab/Umum: buat salinan event
            // langsung di kalender Google setiap asisten yang sudah connect
            // (tanpa attendees - ini kalender milik mereka sendiri).
            if ($type == 'umum') {
                $assistantPayload = $this->formatEventData($data, []);
                $this->syncLabEventForAssistants($lastId, $assistantPayload);
            }
        } catch (\Throwable $e) {
            // jadwal_full / sinkronisasi salinan asisten gagal - jadwal
            // utama (di atas) tetap tersimpan.
        }

        return true;
    }

    public function updateSchedule($data) {
        try {
            $type = $data['type']; $model = $data['model_perulangan']; $tglMulai = $data['date'];
            $tglSelesai = ($model == 'sekali') ? $tglMulai : ($data['end_date_repeat'] ?? $tglMulai);
            $hari = date('N', strtotime($tglMulai));

            if ($type == 'umum') {
                $sql = "UPDATE jadwal_lab SET nama_kegiatan=:title, lokasi=:loc, tanggal=:tgl, tanggal_selesai=:tgl_end, hari=:hari, jam_mulai=:start, jam_selesai=:end, model_perulangan=:model WHERE id_jadwal_lab=:id";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':id'=>$data['id'], ':title'=>$data['title'], ':loc'=>$data['location'], ':tgl'=>$tglMulai, ':tgl_end'=>$tglSelesai, ':hari'=>$hari, ':start'=>$data['start_time'], ':end'=>$data['end_time'], ':model'=>$model]);
            } elseif ($type == 'piket') {
                $sql = "UPDATE jadwal_piket SET id_profil=:pid, subjek=:title, tanggal=:tgl, tanggal_selesai=:tgl_end, hari=:hari, jam_mulai=:start, jam_selesai=:end, model_perulangan=:model WHERE id_jadwal_piket=:id";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':id'=>$data['id'], ':pid'=>$data['user_id'], ':title'=>$data['title'], ':tgl'=>$tglMulai, ':tgl_end'=>$tglSelesai, ':hari'=>$hari, ':start'=>$data['start_time'], ':end'=>$data['end_time'], ':model'=>$model]);
            } elseif ($type == 'kuliah') {
                // [BARU - Modul Dosen] resolve dosen pengampu ke id_dosen +
                // nama kanonik (dipakai juga oleh formatEventData() di bawah).
                $dosenInfo = $this->resolveDosen($data);
                $data['dosen'] = $dosenInfo['nama'];

                $sql = "UPDATE jadwal_kuliah SET id_profil=:pid, matkul=:title, ruangan=:loc, dosen=:dosen, id_dosen=:id_dosen, kelas=:kelas, tanggal=:tgl, tanggal_selesai=:tgl_end, hari=:hari, start_time=:start, end_time=:end, model_perulangan=:model WHERE id_jadwal_kuliah=:id";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':id'=>$data['id'], ':pid'=>$data['user_id'], ':title'=>$data['title'], ':loc'=>$data['location'], ':dosen'=>$dosenInfo['nama'], ':id_dosen'=>$dosenInfo['id_dosen'], ':kelas'=>$data['kelas']??'', ':tgl'=>$tglMulai, ':tgl_end'=>$tglSelesai, ':hari'=>$hari, ':start'=>$data['start_time'], ':end'=>$data['end_time'], ':model'=>$model]);
            } else {
                // [BARU - Modul Dosen] sama seperti type='kuliah' di atas.
                $dosenInfo = $this->resolveDosen($data);
                $data['dosen'] = $dosenInfo['nama'];

                $sql = "UPDATE jadwal_asisten SET id_profil=:pid, mata_kuliah=:title, ruangan_lab=:loc, dosen=:dosen, id_dosen=:id_dosen, kelas_lab=:kelas, tanggal=:tgl, tanggal_selesai=:tgl_end, hari=:hari, model_perulangan=:model, start_time=:start, end_time=:end WHERE id_jadwal_asisten=:id";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':id'=>$data['id'], ':pid'=>$data['user_id'], ':title'=>$data['title'], ':loc'=>$data['location'], ':dosen'=>$dosenInfo['nama'], ':id_dosen'=>$dosenInfo['id_dosen'], ':kelas'=>$data['kelas']??'', ':tgl'=>$tglMulai, ':tgl_end'=>$tglSelesai, ':hari'=>$hari, ':model'=>$model, ':start'=>$data['start_time'], ':end'=>$data['end_time']]);
            }

            // Lihat catatan pada createSchedule(): untuk jadwal umum, akun Google
            // yang dipakai adalah akun Admin pembuat jadwal (creator_user_id),
            // bukan id_profil (yang memang tidak ada untuk jadwal umum).
            $colName = ($type == 'umum') ? 'id_jadwal_lab' : 'id_jadwal_' . $type;
            $stmtGet = $this->conn->prepare("SELECT id_jadwal, google_event_id, sync_status FROM jadwal_full WHERE $colName = :id");
            $stmtGet->execute([':id' => $data['id']]);
            $row = $stmtGet->fetch(PDO::FETCH_ASSOC);

            $fullId = $row['id_jadwal'] ?? null;
            $gEventId = $row['google_event_id'] ?? null;
            $syncStatus = $row['sync_status'] ?? 'skipped';

            $realUserId = null;
            if ($type == 'umum') {
                $realUserId = $data['creator_user_id'] ?? null;
            } elseif (!empty($data['user_id'])) {
                $realUserId = $this->getRealUserId($data['user_id']);
            }

            if ($realUserId) {
                $google = new GoogleClient();
                $accessToken = $google->getValidAccessToken($realUserId);

                if ($accessToken) {
                    $attendees = [];
                    if ($type == 'umum') {
                        $attendees = $this->getKepalaLabAttendees();
                    }
                    $eventPayload = $this->formatEventData($data, $attendees);

                    if (!empty($gEventId)) {
                        // Event sudah ada di Google -> UPDATE.
                        $gResponse = $google->updateEvent($accessToken, $gEventId, $eventPayload);
                        $syncStatus = (is_array($gResponse) && isset($gResponse['id'])) ? 'synced' : 'failed';
                    } else {
                        // [BARU - Modul 5 V3] Belum pernah sync (status
                        // sebelumnya 'skipped'/'failed' tanpa event_id) -
                        // coba CREATE sekarang. Ini "retry-on-edit", mis.
                        // setelah pemilik jadwal menghubungkan akun Google.
                        $gResponse = $google->createEvent($accessToken, $eventPayload);
                        if (isset($gResponse['id'])) {
                            $gEventId = $gResponse['id'];
                            $syncStatus = 'synced';
                        } else {
                            $syncStatus = 'failed';
                        }
                    }
                } else {
                    // [BARU] Token tidak tersedia (belum connect / refresh
                    // gagal). Jika sebelumnya sudah pernah synced, data lokal
                    // berubah tapi tidak ter-propagasi -> tandai 'failed' agar
                    // bisa di-retry. Jika belum pernah sync sama sekali,
                    // biarkan 'skipped'.
                    $syncStatus = empty($gEventId) ? 'skipped' : 'failed';
                }
            }
            // Jika $realUserId null (mis. jadwal umum tanpa creator_user_id),
            // biarkan sync_status apa adanya - tidak ada akun Google yang
            // relevan untuk dicoba.

            if ($fullId) {
                $stmtUpd = $this->conn->prepare("UPDATE jadwal_full SET google_event_id = :g_id, sync_status = :sync WHERE id_jadwal = :fid");
                $stmtUpd->execute([':g_id' => $gEventId, ':sync' => $syncStatus, ':fid' => $fullId]);
            } else {
                // [BARU] jadwal_full row belum ada (mis. data lama sebelum
                // fitur tracking ini) - buat baru agar status sync tercatat.
                $stmtIns = $this->conn->prepare("INSERT INTO jadwal_full ($colName, google_event_id, sync_status) VALUES (:ref_id, :g_id, :sync)");
                $stmtIns->execute([':ref_id' => $data['id'], ':g_id' => $gEventId, ':sync' => $syncStatus]);
            }

            // [BARU - Modul 5 lanjutan] Jadwal Lab/Umum: update salinan event
            // di kalender setiap asisten (juga meng-cover asisten BARU yang
            // belum punya salinan, dan retry asisten yang sebelumnya gagal).
            if ($type == 'umum') {
                $assistantPayload = $this->formatEventData($data, []);
                $this->syncLabEventForAssistants($data['id'], $assistantPayload);
            }

            return true;
        // [PERBAIKAN] Sama seperti createSchedule(): tangkap \Throwable
        // (bukan hanya \Exception) agar editSchedule() di controller selalu
        // mencapai header("Location: ...") - mencegah browser "nyangkut" di
        // /admin/editSchedule jika ada Error/TypeError pada kode sinkronisasi
        // Google di atas. Update data jadwal inti sendiri sudah dieksekusi
        // sebelum bagian sinkronisasi, sehingga tetap tersimpan.
        } catch (\Throwable $e) { return false; }
    }

    public function deleteSchedule($id, $type, $creatorUserId = null) {
        try {
            $colName = ($type == 'umum') ? 'id_jadwal_lab' : 'id_jadwal_' . $type;
            $tableMap = ['kuliah'=>'jadwal_kuliah', 'asisten'=>'jadwal_asisten', 'piket'=>'jadwal_piket', 'umum'=>'jadwal_lab'];
            $tableName = $tableMap[$type];
            $pkName = ($type == 'umum') ? 'id_jadwal_lab' : 'id_' . $tableName;

            $sqlGet = "SELECT jf.google_event_id, t.* FROM jadwal_full jf 
                       JOIN $tableName t ON jf.$colName = t.$pkName 
                       WHERE jf.$colName = :id";
            
            $stmtGet = $this->conn->prepare($sqlGet);
            $stmtGet->execute([':id' => $id]);
            $row = $stmtGet->fetch(PDO::FETCH_ASSOC);

            if ($row && !empty($row['google_event_id'])) {
                // Lihat catatan pada createSchedule(): untuk jadwal umum, event
                // dibuat di akun Google Admin pembuat jadwal, sehingga $creatorUserId
                // di sini HARUS berupa id_user (bukan id_profil) milik Admin tersebut.
                // Untuk jadwal pribadi (kuliah/asisten/piket), pakai id_profil dari baris.
                $realUserId = null;
                if ($type == 'umum') {
                    $realUserId = $creatorUserId;
                } elseif (!empty($row['id_profil'])) {
                    $realUserId = $this->getRealUserId($row['id_profil']);
                }

                if ($realUserId) {
                    $google = new GoogleClient();
                    $accessToken = $google->getValidAccessToken($realUserId);
                    if ($accessToken) {
                        // [CATATAN - Modul 5 V3] Hasil deleteEvent() tidak
                        // diperiksa: data lokal tetap dihapus (DB lokal adalah
                        // sumber kebenaran) walau penghapusan di Google gagal.
                        // "Retry failed sync" untuk DELETE tidak diterapkan -
                        // begitu baris jadwal_* & jadwal_full di bawah ini
                        // dihapus, tidak ada lagi data lokal untuk dasar retry.
                        // Jika deleteEvent gagal, event Google akan tersisa
                        // (orphan) - keterbatasan yang diketahui.
                        $google->deleteEvent($accessToken, $row['google_event_id']);
                    }
                }
            }

            $sqlDelete = "DELETE FROM $tableName WHERE $pkName = :id";
            $stmt = $this->conn->prepare($sqlDelete);
            $ok = $stmt->execute([':id' => $id]);

            // [BARU] Bersihkan baris jadwal_full terkait agar tidak menumpuk
            // referensi dangling (sebelumnya jadwal_full TIDAK PERNAH
            // dibersihkan saat jadwal dihapus).
            if ($ok) {
                $stmtFull = $this->conn->prepare("DELETE FROM jadwal_full WHERE $colName = :id");
                $stmtFull->execute([':id' => $id]);

                // [BARU - Modul 5 lanjutan] Hapus juga salinan event di
                // kalender setiap asisten + baris jadwal_lab_sync terkait.
                if ($type == 'umum') {
                    $this->syncLabEventForAssistants($id, null, true);
                }
            }

            return $ok;

        // [PERBAIKAN] Konsisten dengan createSchedule()/updateSchedule().
        } catch (\Throwable $e) { return false; }
    }
    
    public function getUpcomingSchedules($limit = 5)
    {
        $this->db->query("
            SELECT * 
            FROM jadwal_lab
            WHERE tanggal >= CURDATE()
            ORDER BY tanggal ASC, jam_mulai ASC
            LIMIT :limit
        ");
        $this->db->bind(':limit', (int)$limit);

        $results = $this->db->resultSet();

        foreach ($results as &$sch) {
            $sch['display_date'] = date('d M Y', strtotime($sch['tanggal']));
            $sch['type'] = 'umum';
        }

        return $results;
    }

    /**
     * Fungsi Baru Khusus Dashboard Mobile (Tanpa merusak Web)
     * Mengambil jadwal kuliah, asisten, dan piket dalam satu list
     */
    public function getMobileDashboardSchedules($profilId) {
        try {
            $sql = "SELECT matkul as title, ruangan as location, tanggal, start_time, end_time, 'kuliah' as type 
                    FROM jadwal_kuliah WHERE id_profil = :pid AND tanggal >= CURDATE()
                    UNION
                    SELECT mata_kuliah as title, ruangan_lab as location, tanggal, start_time, end_time, 'asisten' as type 
                    FROM jadwal_asisten WHERE id_profil = :pid AND tanggal >= CURDATE()
                    UNION
                    SELECT subjek as title, 'Lab' as location, tanggal, jam_mulai as start_time, jam_selesai as end_time, 'piket' as type 
                    FROM jadwal_piket WHERE id_profil = :pid AND tanggal >= CURDATE()
                    ORDER BY tanggal ASC, start_time ASC
                    LIMIT 10";
                    
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':pid' => $profilId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * [BARU - Modul 5 V3] Coba sinkronkan ulang satu item jadwal yang
     * sync_status-nya 'failed'. Jika google_event_id sudah ada (update yang
     * gagal sebelumnya), coba updateEvent(); jika belum ada (create yang
     * gagal sebelumnya), coba createEvent(). Mengembalikan true jika item
     * tidak perlu di-retry (sudah 'synced'/'skipped') ATAU retry berhasil;
     * false jika retry tetap gagal atau prasyarat (akun Google) tidak ada.
     */
    public function retrySchedule($id, $type, $creatorUserId = null) {
        try {
            $colName = ($type == 'umum') ? 'id_jadwal_lab' : 'id_jadwal_' . $type;
            $tableMap = ['kuliah'=>'jadwal_kuliah', 'asisten'=>'jadwal_asisten', 'piket'=>'jadwal_piket', 'umum'=>'jadwal_lab'];
            if (!isset($tableMap[$type])) return false;
            $tableName = $tableMap[$type];
            $pkName = ($type == 'umum') ? 'id_jadwal_lab' : 'id_' . $tableName;

            // Alias kolom per tipe jadwal agar sesuai input yang diharapkan
            // formatEventData() (title, location, dosen, kelas, start_time,
            // end_time, date, end_date_repeat, model_perulangan, id_profil) -
            // sama seperti pemetaan di getAllSchedules().
            $selectMap = [
                'umum'    => "t.nama_kegiatan as title, t.lokasi as location, NULL as dosen, NULL as kelas, t.jam_mulai as start_time, t.jam_selesai as end_time, t.tanggal as `date`, t.tanggal_selesai as end_date_repeat, t.model_perulangan, NULL as id_profil",
                'asisten' => "t.mata_kuliah as title, t.ruangan_lab as location, t.dosen, t.kelas_lab as kelas, t.start_time, t.end_time, t.tanggal as `date`, t.tanggal_selesai as end_date_repeat, t.model_perulangan, t.id_profil",
                'piket'   => "t.subjek as title, 'Lab' as location, NULL as dosen, NULL as kelas, t.jam_mulai as start_time, t.jam_selesai as end_time, t.tanggal as `date`, t.tanggal_selesai as end_date_repeat, t.model_perulangan, t.id_profil",
                'kuliah'  => "t.matkul as title, t.ruangan as location, t.dosen, t.kelas, t.start_time, t.end_time, t.tanggal as `date`, t.tanggal_selesai as end_date_repeat, t.model_perulangan, t.id_profil",
            ];

            $sqlGet = "SELECT jf.id_jadwal, jf.google_event_id, jf.sync_status, {$selectMap[$type]}
                       FROM jadwal_full jf JOIN $tableName t ON jf.$colName = t.$pkName
                       WHERE jf.$colName = :id";
            $stmtGet = $this->conn->prepare($sqlGet);
            $stmtGet->execute([':id' => $id]);
            $row = $stmtGet->fetch(PDO::FETCH_ASSOC);

            if (!$row) return false;

            // [BARU - Modul 5 lanjutan] Untuk jadwal Lab/Umum, "failed" bisa
            // berarti event master ATAU salah satu salinan per-asisten
            // (jadwal_lab_sync) gagal. Cek keduanya sebelum memutuskan tidak
            // ada yang perlu di-retry.
            $assistantHasFailed = false;
            if ($type == 'umum') {
                $stmtChk = $this->conn->prepare("SELECT COUNT(*) FROM jadwal_lab_sync WHERE id_jadwal_lab = :id AND sync_status = 'failed'");
                $stmtChk->execute([':id' => $id]);
                $assistantHasFailed = (int) $stmtChk->fetchColumn() > 0;
            }

            $masterFailed = ($row['sync_status'] === 'failed');

            if (!$masterFailed && !$assistantHasFailed) return true; // tidak ada yang perlu di-retry

            $realUserId = null;
            if ($type == 'umum') {
                $realUserId = $creatorUserId;
            } elseif (!empty($row['id_profil'])) {
                $realUserId = $this->getRealUserId($row['id_profil']);
            }

            $data = $row;
            $data['type'] = $type;

            $success = true;

            // 1. Retry event master (jadwal_full) jika gagal.
            if ($masterFailed) {
                if (!$realUserId) {
                    $success = false;
                } else {
                    $google = new GoogleClient();
                    $accessToken = $google->getValidAccessToken($realUserId);
                    if (!$accessToken) {
                        $success = false;
                    } else {
                        $attendees = ($type == 'umum') ? $this->getKepalaLabAttendees() : [];
                        $eventPayload = $this->formatEventData($data, $attendees);

                        if (!empty($row['google_event_id'])) {
                            $gResponse = $google->updateEvent($accessToken, $row['google_event_id'], $eventPayload);
                            $masterOk = is_array($gResponse) && isset($gResponse['id']);
                            $newGId = $row['google_event_id'];
                        } else {
                            $gResponse = $google->createEvent($accessToken, $eventPayload);
                            $masterOk = isset($gResponse['id']);
                            $newGId = $masterOk ? $gResponse['id'] : null;
                        }

                        $newStatus = $masterOk ? 'synced' : 'failed';
                        $stmtUpd = $this->conn->prepare("UPDATE jadwal_full SET google_event_id = :gid, sync_status = :st WHERE id_jadwal = :fid");
                        $stmtUpd->execute([':gid' => $newGId, ':st' => $newStatus, ':fid' => $row['id_jadwal']]);

                        if (!$masterOk) $success = false;
                    }
                }
            }

            // 2. Retry salinan per-asisten (jadwal_lab_sync) jika ada yang gagal.
            //    syncLabEventForAssistants() meng-upsert ulang SEMUA asisten
            //    (idempotent - yang sudah 'synced' tetap update biasa, yang
            //    'failed'/'skipped' dicoba lagi sesuai ketersediaan token).
            if ($type == 'umum' && $assistantHasFailed) {
                $assistantPayload = $this->formatEventData($data, []);
                $this->syncLabEventForAssistants($id, $assistantPayload);

                $stmtChk2 = $this->conn->prepare("SELECT COUNT(*) FROM jadwal_lab_sync WHERE id_jadwal_lab = :id AND sync_status = 'failed'");
                $stmtChk2->execute([':id' => $id]);
                if ((int) $stmtChk2->fetchColumn() > 0) $success = false;
            }

            return $success;
        } catch (Exception $e) {
            return false;
        }
    }

}
?>
