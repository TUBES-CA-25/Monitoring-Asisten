<?php
require_once '../app/core/ApiResponse.php';
require_once '../app/api/AuthApi.php';
require_once '../app/models/ScheduleModel.php';

class DashboardApi {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * GET /api/dashboard/summary
     * Get dashboard summary untuk user (hadir/alpa/izin bulan ini, dll)
     */
    public function summary() {
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        try {
            // --- 1. LOGIKA ALPA DINAMIS (TITIK START) ---
            $firstAbsenQuery = "SELECT MIN(tanggal) as start_date FROM presensi WHERE id_profil = :pid";
            $stmtStart = $this->conn->prepare($firstAbsenQuery);
            $stmtStart->execute([':pid' => $profilId]);
            $startDate = $stmtStart->fetch(PDO::FETCH_ASSOC)['start_date'];

            $alpaDinamis = 0;
            $hadirCount = 0;
            $terlambatCount = 0;

            if ($startDate) {
                $start = new DateTime($startDate);
                $today = new DateTime();
                $interval = $start->diff($today);
                $totalDaysPassed = $interval->days + 1; 

                $statsQuery = "SELECT 
                            SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) as hadir,
                            SUM(CASE WHEN status = 'Terlambat' THEN 1 ELSE 0 END) as terlambat
                            FROM presensi WHERE id_profil = :pid";
                $stmtStats = $this->conn->prepare($statsQuery);
                $stmtStats->execute([':pid' => $profilId]);
                $resStats = $stmtStats->fetch(PDO::FETCH_ASSOC);
                
                $hadirCount = (int)($resStats['hadir'] ?? 0);
                $terlambatCount = (int)($resStats['terlambat'] ?? 0);
                // [PERBAIKAN] Sejak Modul 1 V3, hari "hadir" bisa berstatus 'Hadir'
                // ATAU 'Terlambat'. Sebelumnya hanya $hadirCount yang dikurangkan,
                // sehingga hari Terlambat ikut terhitung sebagai Alpa.
                $totalPresent = $hadirCount + $terlambatCount;
                $alpaDinamis = ($totalDaysPassed > $totalPresent) ? ($totalDaysPassed - $totalPresent) : 0;
            }

            // --- 2. LOGIKA IZIN (KEMBALI KE STRUKTUR LAMA AGAR TERBACA) ---
            $leaveQuery = "SELECT 
                        SUM(CASE WHEN tipe = 'Izin' AND status_approval = 'Approved' THEN DATEDIFF(end_date, start_date) + 1 ELSE 0 END) as izin_approved,
                        SUM(CASE WHEN tipe = 'Sakit' AND status_approval = 'Approved' THEN DATEDIFF(end_date, start_date) + 1 ELSE 0 END) as sakit_approved,
                        COUNT(CASE WHEN status_approval = 'Pending' THEN 1 END) as pending_count
                        FROM izin
                        WHERE id_profil = :pid";

            $leaveStmt = $this->conn->prepare($leaveQuery);
            $leaveStmt->execute([':pid' => $profilId]); 
            $leaveStats = $leaveStmt->fetch(PDO::FETCH_ASSOC);

            // --- 3. BUILD RESPONSE (SESUAIKAN KEY DENGAN FLUTTER) ---
            $responseData = [
                'today_status' => $this->getTodayStatus($profilId),
                'attendance' => [
                    'hadir' => $hadirCount,
                    'alpa' => $alpaDinamis,
                    'terlambat' => $terlambatCount
                ],
                'leave' => [
                    // Kembalikan key ini agar Flutter bisa membaca datanya
                    'izin_approved' => (int)($leaveStats['izin_approved'] ?? 0),
                    'sakit_approved' => (int)($leaveStats['sakit_approved'] ?? 0),
                    'pending_requests' => (int)($leaveStats['pending_count'] ?? 0)
                ],
                'logbook' => [
                    'total_entries' => 0 
                ]
            ];

            ApiResponse::success($responseData, 'Dashboard summary retrieved', 200);
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    private function getTodayStatus($profilId) {
        $query = "SELECT status, waktu_pulang FROM presensi 
                  WHERE id_profil = :pid AND tanggal = CURDATE() LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':pid' => $profilId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) return 'Belum Check-in';
        
        return $data['status'] . ($data['waktu_pulang'] ? ' (Sudah Pulang)' : ' (Check-in)');
    }

    /**
     * GET /api/dashboard/upcoming-schedule
     * Get upcoming schedule untuk hari ini + beberapa hari ke depan
     * Query: ?days=3 (default 3 hari ke depan)
     */
    public function upcomingSchedule() {
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        try {
            // Kita beri alias 'jk' untuk jadwal_kuliah agar MySQL tidak bingung
            $query = "SELECT 
                        jk.id_jadwal_kuliah as id_schedule,
                        jk.matkul,
                        jk.dosen,
                        jk.kelas,
                        jk.ruangan,
                        jk.hari,
                        jk.tanggal, 
                        jk.start_time,
                        jk.end_time,
                        'Kuliah' as tipe
                    FROM jadwal_kuliah jk
                    WHERE jk.id_profil = :pid
                    AND jk.tanggal >= CURDATE()
                    ORDER BY jk.tanggal ASC, jk.start_time ASC
                    LIMIT 20";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([':pid' => $profilId]);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $groupedSchedules = [];
            foreach ($schedules as $schedule) {
                $date = $schedule['tanggal'];
                if (!isset($groupedSchedules[$date])) {
                    $groupedSchedules[$date] = [
                        'date' => $date,
                        'day_name' => $this->getDayName(date('N', strtotime($date))),
                        'schedules' => []
                    ];
                }
                $groupedSchedules[$date]['schedules'][] = $schedule;
            }

            ApiResponse::success(array_values($groupedSchedules), 'Upcoming schedule retrieved', 200);
        } catch (PDOException $e) {
            // Ini akan menangkap jika ada error SQL lagi
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Helper function to get day name in Indonesian
     */
    private function getDayName($dayNumber) {
        $days = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu'
        ];
        return $days[$dayNumber] ?? 'Unknown';
    }

    public function upcomingScheduleMobile() {
        try {
            $payload = AuthApi::validateToken();
            $userId = isset($payload['id_user']) ? $payload['id_user'] : ($payload['profil_id'] ?? null);

            $model = new ScheduleModel();
            $allSchedules = $model->getAllUserSchedules($userId); 

            $groupedSchedules = [];
            $today = new DateTime();
            
            // Loop 7 hari ke depan
            for ($i = 0; $i < 7; $i++) {
                $currentDate = clone $today;
                if ($i > 0) $currentDate->modify("+$i day");
                
                $dateStr = $currentDate->format('Y-m-d');
                $dayOfWeekPHP = (int)$currentDate->format('N'); // 1=Senin, ..., 7=Minggu

                foreach ($allSchedules as $s) {
                    $startDate = $s['start_date'];
                    $endDate = !empty($s['end_date']) ? $s['end_date'] : $startDate;
                    $modelRepeat = $s['model_perulangan'] ?? 'sekali';
                    
                    // --- KONVERSI HARI DARI DATABASE ---
                    // Kita pastikan day_of_week dari DB jadi angka 1-7
                    $dbDay = $s['day_of_week'];
                    if (!is_numeric($dbDay)) {
                        // Jika di DB isinya teks "Senin", "Selasa" dst
                        $mapHari = ['Senin'=>1, 'Selasa'=>2, 'Rabu'=>3, 'Kamis'=>4, 'Jumat'=>5, 'Sabtu'=>6, 'Minggu'=>7];
                        $dbDay = $mapHari[$dbDay] ?? 0;
                    } else {
                        // Jika di DB isinya 0-6 (Minggu=0), konversi ke 1-7 (Senin=1)
                        if ($dbDay == 0) $dbDay = 7; 
                    }

                    $isMatch = false;

                    // 1. Logika Mingguan
                    if ($modelRepeat === 'mingguan' || $modelRepeat === 'rentang') {
                        if ($dateStr >= $startDate && ($endDate === '0000-00-00' || $dateStr <= $endDate)) {
                            // Bandingkan hari di kalender ($dayOfWeekPHP) dengan hari di DB ($dbDay)
                            if ((int)$dbDay === $dayOfWeekPHP) {
                                $isMatch = true;
                            }
                        }
                    } 
                    // 2. Logika Sekali Jalan
                    else if ($modelRepeat === 'sekali') {
                        if ($dateStr === $startDate) {
                            $isMatch = true;
                        }
                    }

                    if ($isMatch) {
                        if (!isset($groupedSchedules[$dateStr])) {
                            $groupedSchedules[$dateStr] = [
                                'date' => $dateStr,
                                'day_name' => $this->getDayName($dayOfWeekPHP),
                                'schedules' => []
                            ];
                        }

                        $groupedSchedules[$dateStr]['schedules'][] = [
                            'id_schedule' => $s['id'] ?? null,
                            'matkul'      => $s['title'] ?? '-',
                            'ruangan'     => $s['location'] ?? '-',
                            'start_time'  => $s['start_time'] ?? '00:00',
                            'end_time'    => $s['end_time'] ?? '00:00',
                            'tipe'        => ucfirst($s['type'] ?? 'Kuliah')
                        ];
                    }
                }
            }

            // Penting: Urutkan key array berdasarkan tanggal agar tidak berantakan di UI
            ksort($groupedSchedules);

            foreach ($groupedSchedules as $date => $data) {
                usort($groupedSchedules[$date]['schedules'], function($a, $b) {
                    return strcmp($a['start_time'], $b['start_time']);
                });
            }
            ApiResponse::success(array_values($groupedSchedules), 'Success', 200);

        } catch (Exception $e) {
            ApiResponse::error("Server Error: " . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/logbook/history
     * Mengambil riwayat logbook/presensi user
     */
    public function logbookHistory() {
        try {
            $payload = AuthApi::validateToken();
            $profilId = $payload['profil_id'];

            // Query untuk mengambil data presensi
            // Kita urutkan dari tanggal terbaru ke terlama (DESC)
            $query = "SELECT 
                        id_presensi,
                        tanggal,
                        waktu_masuk,
                        waktu_pulang,
                        status,
                        keterangan as aktivitas
                      FROM presensi 
                      WHERE id_profil = :pid 
                      ORDER BY tanggal DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([':pid' => $profilId]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Kita kirimkan datanya
            ApiResponse::success($history, 'Logbook history retrieved', 200);

        } catch (Exception $e) {
            ApiResponse::error("Server Error: " . $e->getMessage(), 500);
        }
    }
}
?>
