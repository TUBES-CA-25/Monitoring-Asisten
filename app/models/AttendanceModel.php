<?php
class AttendanceModel {
    private $conn;
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function getCurrentStatus($userId) {
        // Ambil Profil ID
        $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
        $this->db->bind(':uid', $userId);
        $res = $this->db->single();
        if(!$res) return 'unknown';
        $pId = $res['id_profil'];

        // Cek data presensi terakhir hari ini
        $this->db->query("SELECT * FROM presensi WHERE id_profil = :pid AND tanggal = CURDATE() ORDER BY id_presensi DESC LIMIT 1");
        $this->db->bind(':pid', $pId);
        $lastLog = $this->db->single();

        if (!$lastLog) {
            return 'not_present'; // Belum absen sama sekali hari ini
        }

        if ($lastLog['waktu_presensi'] && $lastLog['waktu_pulang'] == NULL) {
            return 'checked_in'; // Sudah masuk, BELUM pulang (Sedang Kerja)
        }

        return 'checked_out'; // Sudah masuk DAN sudah pulang (Selesai Sesi)
    }

    private function getProfilId($userId) {
        $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
        $this->db->bind(':uid', $userId);
        $result = $this->db->single();
        return $result['id_profil'] ?? false;
    }

    public function clockIn($userId, $photo) {
        try {
            $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
            $this->db->bind(':uid', $userId);
            $pId = $this->db->single()['id_profil'];

            $query = "INSERT INTO presensi (id_profil, tanggal, waktu_presensi, status, foto_presensi) 
                      VALUES (:pid, CURDATE(), CURTIME(), 'Hadir', :foto)";
            
            $this->db->query($query);
            $this->db->bind(':pid', $pId);
            $this->db->bind(':foto', $photo);
            return $this->db->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    public function clockOut($userId, $photo) {
        try {
            $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
            $this->db->bind(':uid', $userId);
            $pId = $this->db->single()['id_profil'];

            // Update baris terakhir + simpan foto pulang
            $query = "UPDATE presensi 
                      SET waktu_pulang = CURTIME(), foto_pulang = :foto 
                      WHERE id_profil = :pid 
                      AND tanggal = CURDATE() 
                      AND waktu_pulang IS NULL 
                      ORDER BY id_presensi DESC LIMIT 1";
            
            $this->db->query($query);
            $this->db->bind(':pid', $pId);
            $this->db->bind(':foto', $photo);
            
            if ($this->db->execute()) {
                return $this->db->rowCount() > 0;
            }
            return false;
        } catch (Exception $e) { return false; }
    }

    public function reClockIn($userId, $photo) {
        try {
            $this->db->getConnection()->beginTransaction();

            $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
            $this->db->bind(':uid', $userId);
            $pId = $this->db->single()['id_profil'];

            $this->db->query("SELECT id_presensi FROM presensi WHERE id_profil = :pid AND tanggal = CURDATE()");
            $this->db->bind(':pid', $pId);
            $presensi = $this->db->single();

            if ($presensi) {
                $idPresensi = $presensi['id_presensi'];

                $sqlUpd = "UPDATE presensi 
                           SET waktu_presensi = CURTIME(), 
                               waktu_pulang = NULL, 
                               foto_presensi = :foto, 
                               foto_pulang = NULL 
                           WHERE id_presensi = :idp";
                $this->db->query($sqlUpd);
                $this->db->bind(':foto', $photo);
                $this->db->bind(':idp', $idPresensi);
                $this->db->execute();

                $sqlLog = "UPDATE logbook SET detail_aktivitas = NULL WHERE id_presensi = :idp";
                $this->db->query($sqlLog);
                $this->db->bind(':idp', $idPresensi);
                $this->db->execute();
            }

            $this->db->getConnection()->commit();
            return true;
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            return false;
        }
    }

    public function getStatusColor($userId) {
        $status = $this->getCurrentStatus($userId);
        if ($status == 'checked_in') return 'green'; // Sedang kerja
        if ($status == 'checked_out') return 'yellow'; // Sudah pulang (Sesi selesai)
        
        // Cek Izin jika not_present
        $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
        $this->db->bind(':uid', $userId);
        $pId = $this->db->single()['id_profil'] ?? 0;
        
        $this->db->query("SELECT * FROM izin WHERE id_profil = :pid AND CURDATE() BETWEEN start_date AND end_date AND status_approval = 'Approved'");
        $this->db->bind(':pid', $pId);
        if($this->db->single()) return 'yellow';

        return 'red';
    }

    public function getMonitoringData($date) {
        $query = "SELECT p.nama as name, u.role, pr.waktu_presensi as check_in_time, pr.waktu_pulang as check_out_time, pr.status 
                  FROM profile p
                  JOIN user u ON p.id_user = u.id_user
                  LEFT JOIN presensi pr ON p.id_profil = pr.id_profil AND pr.tanggal = :d 
                  WHERE u.role = 'User' 
                  ORDER BY pr.waktu_presensi DESC";
        
        $this->db->query($query);
        $this->db->bind(':d', $date);
        return $this->db->resultSet();
    }

    public function getTodayStats() {
        $today = date('Y-m-d');
        $stats = ['hadir' => 0, 'izin' => 0, 'alpa' => 0];

        // Hitung Hadir
        $this->db->query("SELECT COUNT(*) as total FROM presensi WHERE tanggal = :date");
        $this->db->bind(':date', $today);
        $stats['hadir'] = $this->db->single()['total'];

        // Hitung Izin
        $this->db->query("SELECT COUNT(*) as total FROM izin WHERE :date BETWEEN start_date AND end_date AND status_approval = 'Approved'");
        $this->db->bind(':date', $today);
        $stats['izin'] = $this->db->single()['total'];
        
        // Hitung Total Asisten
        $this->db->query("SELECT COUNT(*) as total FROM user WHERE role = 'User'");
        $totalAsisten = $this->db->single()['total'];
        
        $stats['alpa'] = max(0, $totalAsisten - ($stats['hadir'] + $stats['izin']));
        return $stats;
    }

    public function getChartData() {
        // Mingguan (7 Hari Terakhir)
        $weeklyData = []; $weeklyLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            
            $this->db->query("SELECT COUNT(*) as total FROM presensi WHERE tanggal = :date");
            $this->db->bind(':date', $date);
            $res = $this->db->single();
            
            $weeklyData[] = $res ? $res['total'] : 0;
            $weeklyLabels[] = date('D', strtotime($date));
        }
        
        // Bulanan
        $this->db->query("SELECT MONTH(tanggal) as bulan, COUNT(*) as total FROM presensi WHERE YEAR(tanggal) = YEAR(CURDATE()) GROUP BY MONTH(tanggal)");
        $results = $this->db->resultSet();
        
        $monthlyData = array_fill(0, 12, 0);
        foreach ($results as $res) { 
            $monthlyData[$res['bulan'] - 1] = $res['total']; 
        }

        // Harian (Distribusi Jam Masuk - Opsional/Sederhana)
        // Menghitung berapa orang masuk di jam 07, 08, 09, dst.
        $this->db->query("SELECT HOUR(waktu_presensi) as jam, COUNT(*) as total FROM presensi WHERE tanggal = CURDATE() GROUP BY HOUR(waktu_presensi)");
        $dailyRes = $this->db->resultSet();
        $dailyData = array_fill(0, 24, 0); // 00:00 - 23:00
        foreach($dailyRes as $d) {
            $dailyData[$d['jam']] = $d['total'];
        }
        // Kita ambil jam kerja saja 07:00 - 17:00 untuk chart
        $dailyLabelsChart = ['07:00', '09:00', '11:00', '13:00', '15:00'];
        $dailyDataChart = [
            $dailyData[7]+$dailyData[8], 
            $dailyData[9]+$dailyData[10], 
            $dailyData[11]+$dailyData[12], 
            $dailyData[13]+$dailyData[14], 
            $dailyData[15]+$dailyData[16]
        ];

        return [
            'weekly'  => ['labels' => $weeklyLabels, 'data' => $weeklyData],
            'monthly' => ['labels' => ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'], 'data' => $monthlyData],
            'daily'   => ['labels' => $dailyLabelsChart, 'data' => $dailyDataChart]
        ];
    }

    public function getAllAssistantsList() {
        $this->db->query("SELECT u.id_user, p.nama, p.nim FROM user u JOIN profile p ON u.id_user = p.id_user WHERE u.role = 'User' ORDER BY p.nama ASC");
        return $this->db->resultSet();
    }

    public function getAttendanceRecap($startDate, $endDate, $userId = null) {
        // 1. Ambil Data Presensi Raw dalam rentang
        $sqlP = "SELECT p.*, prof.nama, prof.nim, prof.jabatan, prof.id_user 
                 FROM presensi p 
                 JOIN profile prof ON p.id_profil = prof.id_profil 
                 WHERE p.tanggal BETWEEN :start AND :end";
        if ($userId) $sqlP .= " AND prof.id_user = :uid";
        
        $this->db->query($sqlP);
        $this->db->bind(':start', $startDate);
        $this->db->bind(':end', $endDate);
        if ($userId) $this->db->bind(':uid', $userId);
        $rawPresensi = $this->db->resultSet();

        // 2. Ambil Data Izin Raw (Approved) dalam rentang
        $sqlIz = "SELECT i.*, prof.id_user 
                  FROM izin i 
                  JOIN profile prof ON i.id_profil = prof.id_profil 
                  WHERE i.status_approval = 'Approved' 
                  AND (
                      (i.start_date BETWEEN :start AND :end) OR 
                      (i.end_date BETWEEN :start AND :end) OR
                      (:start BETWEEN i.start_date AND i.end_date)
                  )";
        if ($userId) $sqlIz .= " AND prof.id_user = :uid";

        $this->db->query($sqlIz);
        $this->db->bind(':start', $startDate);
        $this->db->bind(':end', $endDate);
        if ($userId) $this->db->bind(':uid', $userId);
        $rawIzin = $this->db->resultSet();

        // 3. Ambil Target User (Semua atau Satu)
        $sqlUser = "SELECT u.id_user, p.id_profil, p.nama, p.nim, p.jabatan, p.photo_profile 
                    FROM user u JOIN profile p ON u.id_user = p.id_user 
                    WHERE u.role = 'User'";
        if ($userId) {
            $sqlUser .= " AND u.id_user = :uid";
            $this->db->query($sqlUser);
            $this->db->bind(':uid', $userId);
        } else {
            $sqlUser .= " ORDER BY p.nama ASC";
            $this->db->query($sqlUser);
        }
        $targetUsers = $this->db->resultSet();

        // 4. Generate Data Harian (Mapping)
        $finalData = [];
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $end->modify('+1 day'); // Supaya inclusive
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($start, $interval, $end);

        foreach ($period as $dt) {
            $currentDate = $dt->format("Y-m-d");
            
            // Loop setiap user untuk setiap tanggal
            foreach ($targetUsers as $user) {
                $row = [
                    'tanggal' => $currentDate,
                    'name' => $user['nama'],
                    'nim' => $user['nim'],
                    'position' => $user['jabatan'],
                    'photo_profile' => $user['photo_profile'],
                    'waktu_presensi' => null,
                    'waktu_pulang' => null,
                    'status' => 'Alpha' // Default Alpha
                ];

                // Cek Presensi
                foreach ($rawPresensi as $p) {
                    if ($p['id_user'] == $user['id_user'] && $p['tanggal'] == $currentDate) {
                        $row['waktu_presensi'] = $p['waktu_presensi'];
                        $row['waktu_pulang'] = $p['waktu_pulang'];
                        $row['status'] = 'Hadir';
                        break;
                    }
                }

                // Cek Izin (Jika belum hadir)
                if ($row['status'] == 'Alpha') {
                    foreach ($rawIzin as $iz) {
                        if ($user['id_user'] == $iz['id_user'] && $currentDate >= $iz['start_date'] && $currentDate <= $iz['end_date']) {
                            $row['status'] = $iz['tipe']; // Izin / Sakit
                            break;
                        }
                    }
                }

                // Logic Alpha Hari Ini: Jika hari ini & belum jam 18:00, jangan set Alpha, tapi kosong (-)
                if ($row['status'] == 'Alpha' && $currentDate == date('Y-m-d') && date('H:i') < '18:00') {
                    $row['status'] = '-'; // Belum waktunya dianggap Alpha
                }

                $finalData[] = $row;
            }
        }

        // 5. Sorting Data
        // Jika filter User ID aktif: Sort by Tanggal ASC
        // Jika filter range tanggal (semua user): Sort by Tanggal ASC, lalu Nama ASC
        usort($finalData, function($a, $b) use ($userId) {
            if ($a['tanggal'] == $b['tanggal']) {
                return strcmp($a['name'], $b['name']);
            }
            return strcmp($a['tanggal'], $b['tanggal']);
        });

        return $finalData;
    }

    public function getAllAttendanceByDate($startDate, $endDate = null) {
        // Jika endDate kosong, gunakan startDate (mode satu hari)
        if ($endDate === null) {
            $endDate = $startDate;
        }

        $query = "SELECT p.*, 
                         prof.nama as name, 
                         prof.nim, 
                         prof.jabatan as position, 
                         prof.photo_profile 
                  FROM presensi p
                  JOIN profile prof ON p.id_profil = prof.id_profil
                  WHERE p.tanggal BETWEEN :start AND :end
                  ORDER BY p.tanggal DESC, p.waktu_presensi ASC";
        
        $this->db->query($query);
        $this->db->bind(':start', $startDate);
        $this->db->bind(':end', $endDate);
        return $this->db->resultSet();
    }

    public function createLeaveRequest($data) {
        try {
            // Perhatikan bagian 'Approved' di akhir VALUES
            $query = "INSERT INTO izin (id_profil, tipe, start_date, end_date, deskripsi, file_bukti, status_approval) 
                      VALUES (:pid, :tipe, :sdate, :edate, :desc, :file, 'Approved')";
            
            $this->db->query($query);
            $this->db->bind(':pid', $data['id_profil']);
            $this->db->bind(':tipe', $data['type']);
            $this->db->bind(':sdate', $data['start_date']);
            $this->db->bind(':edate', $data['end_date']);
            $this->db->bind(':desc', $data['reason']); 
            $this->db->bind(':file', $data['file_bukti']);
            
            return $this->db->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    
}
?>