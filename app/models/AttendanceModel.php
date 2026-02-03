<?php
class AttendanceModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    private function getProfilId($userId)
    {
        $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
        $this->db->bind(':uid', $userId);
        $result = $this->db->single();
        return $result['id_profil'] ?? false;
    }

    public function clockIn($userId, $img)
    {
        $pId = $this->getProfilId($userId);
        if (!$pId) return false;

        $date = date('Y-m-d');
        $time = date('H:i:s');

        $this->db->query("SELECT id_presensi FROM presensi WHERE id_profil = :pid AND tanggal = :date");
        $this->db->bind(':pid', $pId);
        $this->db->bind(':date', $date);
        
        if($this->db->single()) return false;

        $query = "INSERT INTO presensi (id_profil, tanggal, waktu_presensi, foto_presensi, status)
                  VALUES (:pid, :date, :time, :img, 'Hadir')";

        $this->db->query($query);
        $this->db->bind(':pid', $pId);
        $this->db->bind(':date', $date);
        $this->db->bind(':time', $time);
        $this->db->bind(':img', $img);
        return $this->db->execute();
    }

    public function clockOut($userId, $img)
    {
        $pId = $this->getProfilId($userId);
        if (!$pId) return false;

        $date = date('Y-m-d');
        $time = date('H:i:s');

        $query = "UPDATE presensi SET waktu_pulang = :time, foto_pulang = :img
                  WHERE id_profil = :pid AND tanggal = :date";
        $this->db->bind(':pid', $pId);
        $this->db->bind(':date', $date);
        $data = $this->db->single();

        if (!$data) return false;

        if ($data['waktu_pulang'] != null) {
            return false; 
        }

        $query = "UPDATE presensi SET waktu_pulang = :time, foto_pulang = :img
                  WHERE id_profil = :pid AND tanggal = :date";
        $this->db->query($query);
        $this->db->bind(':pid', $pId);
        $this->db->bind(':img', $img);
        $this->db->bind(':time', $time);
        $this->db->bind(':date', $date);
        if($this->db->execute()) {
            return $this->db->rowCount() > 0;
        }
        return false;
    }

    public function getStatusColor($userId)
    {
        $pId = $this->getProfilId($userId);
        if (!$pId) return 'red';

        $today = date('Y-m-d');
        $this->db->query("SELECT id_presensi FROM presensi WHERE id_profil = :pid AND tanggal = :date");
        $this->db->bind(':pid', $pId);
        $this->db->bind(':date', $today);
        if ($this->db->rowCount() > 0) return 'green';

        $this->db->query("SELECT id_izin FROM izin
                          WHERE id_profil = :pid
                          AND :date BETWEEN start_date AND end_date
                          AND status_approval = 'Approved'");
        $this->db->bind(':pid', $pId);
        $this->db->bind(':date', $today);
        if ($this->db->rowCount() > 0) return 'yellow';

        return 'red';
    }

    public function getMonitoringData($date)
    {
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

    public function getTodayStats()
    {
        $today = date('Y-m-d');
        $stats = ['hadir' => 0, 'izin' => 0, 'alpa' => 0];

        $this->db->query("SELECT COUNT(*) as total FROM presensi WHERE tanggal = :date");
        $this->db->bind(':date', $today);
        $stats['hadir'] = $this->db->single()['total'] ?? 0;

        $this->db->query("SELECT COUNT(*) as total FROM izin WHERE :date BETWEEN start_date AND end_date AND status_approval = 'Approved'");
        $this->db->bind(':date', $today);
        $stats['izin'] = $this->db->single()['total'] ?? 0;

        $this->db->query("SELECT COUNT(*) as total FROM user WHERE role = 'User'");
        $totalAsisten = $this->db->single()['total'] ?? 0;

        $stats['alpa'] = max(0, $totalAsisten - ($stats['hadir'] + $stats['izin']));
        return $stats;
    }

    public function getChartData()
    {
        $weeklyData = [];
        $weeklyLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $this->db->query("SELECT COUNT(*) as total FROM presensi WHERE tanggal = :date");
            $this->db->bind(':date', $date);
            $res = $this->db->single();
            $weeklyData[] = $res ? $res['total'] : 0;
            $weeklyLabels[] = date('D', strtotime($date));
        }

        $this->db->query("SELECT MONTH(tanggal) as bulan, COUNT(*) as total FROM presensi WHERE YEAR(tanggal) = YEAR(CURDATE()) GROUP BY MONTH(tanggal)");
        $results = $this->db->resultSet();
        $monthlyData = array_fill(0, 12, 0);
        foreach ($results as $res) {
            $monthlyData[$res['bulan'] - 1] = $res['total'];
        }

        return [
            'weekly'  => ['labels' => $weeklyLabels, 'data' => $weeklyData],
            'monthly' => ['labels' => ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'], 'data' => $monthlyData],
            'daily'   => ['labels' => ['08:00', '10:00', '12:00', '14:00', '16:00'], 'data' => [0,0,0,0,0]]
        ];
    }

    // Tambahkan ini di dalam class AttendanceModel
    public function getUserStats($pId) {
        $stats = [];
        $this->db->query("SELECT COUNT(*) as total FROM presensi WHERE id_profil = :pid AND status = 'Hadir'");
        $this->db->bind(':pid', $pId);
        $stats['hadir'] = $this->db->single()['total'] ?? 0;

        $this->db->query("SELECT COUNT(*) as total FROM izin WHERE id_profil = :pid AND status_approval = 'Approved'");
        $this->db->bind(':pid', $pId);
        $stats['izin'] = $this->db->single()['total'] ?? 0;

        return $stats;
    }

    public function getTodayAttendanceDetail($pId) {
        $today = date('Y-m-d');

        $this->db->query("SELECT * FROM presensi WHERE id_profil = :pid AND tanggal = :d");
        $this->db->bind(':pid', $pId);
        $this->db->bind(':d', $today);
        $presensi = $this->db->single();

        $this->db->query("SELECT * FROM izin WHERE id_profil = :pid AND :d BETWEEN start_date AND end_date AND status_approval = 'Approved'");
        $this->db->bind(':pid', $pId);
        $this->db->bind(':d', $today);
        $izin = $this->db->single();

        return ['presensi' => $presensi, 'izin' => $izin];
    }

    public function getUserDailyChart($pId) {
        $labels = []; $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('d M', strtotime($date));
            
            $this->db->query("SELECT COUNT(*) as total FROM presensi WHERE id_profil = :pid AND tanggal = :d AND status = 'Hadir'");
            $this->db->bind(':pid', $pId);
            $this->db->bind(':d', $date);
            $data[] = $this->db->single()['total'] ?? 0;
        }
        return ['labels' => $labels, 'data' => $data];
    }

    public function getAllAssistantsList() {
        $this->db->query("SELECT u.id_user, p.nama, p.nim 
                          FROM user u 
                          JOIN profile p ON u.id_user = p.id_user 
                          WHERE u.role = 'User' 
                          ORDER BY p.nama ASC");
        return $this->db->resultSet();
    }

    public function getAttendanceRecap($startDate, $endDate, $userId = null) {
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

        $finalData = [];
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $end->modify('+1 day');
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($start, $interval, $end);

        foreach ($period as $dt) {
            $currentDate = $dt->format("Y-m-d");
            
            foreach ($targetUsers as $user) {
                $row = [
                    'tanggal' => $currentDate,
                    'name' => $user['nama'],
                    'nim' => $user['nim'],
                    'position' => $user['jabatan'],
                    'photo_profile' => $user['photo_profile'],
                    'waktu_presensi' => null,
                    'waktu_pulang' => null,
                    'status' => 'Alpha'
                ];

                foreach ($rawPresensi as $p) {
                    if ($p['id_user'] == $user['id_user'] && $p['tanggal'] == $currentDate) {
                        $row['waktu_presensi'] = $p['waktu_presensi'];
                        $row['waktu_pulang'] = $p['waktu_pulang'];
                        $row['status'] = 'Hadir';
                        break;
                    }
                }

                if ($row['status'] == 'Alpha') {
                    foreach ($rawIzin as $iz) {
                        if ($user['id_user'] == $iz['id_user'] && $currentDate >= $iz['start_date'] && $currentDate <= $iz['end_date']) {
                            $row['status'] = $iz['tipe'];
                            break;
                        }
                    }
                }

                if ($row['status'] == 'Alpha' && $currentDate == date('Y-m-d') && date('H:i') < '18:00') {
                    $row['status'] = '-'; 
                }

                $finalData[] = $row;
            }
        }

        usort($finalData, function($a, $b) use ($userId) {
            if ($a['tanggal'] == $b['tanggal']) {
                return strcmp($a['name'], $b['name']);
            }
            return strcmp($a['tanggal'], $b['tanggal']);
        });

        return $finalData;
    }

    public function getAllAttendanceByDate($startDate, $endDate = null) {
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
    
    public function validateLogbookEntry($profileId, $date) {
        $this->db->query("SELECT waktu_presensi, waktu_pulang FROM presensi WHERE id_profil = :pid AND tanggal = :d");
        $this->db->bind(':pid', $profileId);
        $this->db->bind(':d', $date);
        return $this->db->single();
    }

    public function countLateToday() {
        $today = date('Y-m-d');
        $this->db->query("SELECT COUNT(*) as total FROM presensi WHERE tanggal = :d AND waktu_presensi > '08:00:00'");
        $this->db->bind(':d', $today);
        $result = $this->db->single();
        return $result['total'] ?? 0;
    }
}