<?php
class AttendanceModel {
    private $conn;
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    private function getProfilId($userId) {
        $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
        $this->db->bind(':uid', $userId);
        $result = $this->db->single();
        return $result['id_profil'] ?? false;
    }

    public function clockIn($userId, $img) {
        $pId = $this->getProfilId($userId);
        if (!$pId) return false;

        // Insert ke tabel presensi
        $query = "INSERT INTO presensi (id_profil, tanggal, waktu_presensi, foto_presensi, status) 
                  VALUES (:pid, CURDATE(), CURTIME(), :img, 'Hadir')";
        
        $this->db->query($query);
        $this->db->bind(':pid', $pId);
        $this->db->bind(':img', $img);
        
        return $this->db->execute();
    }

    public function clockOut($userId, $img) {
        $pId = $this->getProfilId($userId);
        if (!$pId) return false;

        $query = "UPDATE presensi SET waktu_pulang = CURTIME(), foto_pulang = :img 
                  WHERE id_profil = :pid AND tanggal = CURDATE()";
        
        $this->db->query($query);
        $this->db->bind(':pid', $pId);
        $this->db->bind(':img', $img);
        
        return $this->db->execute();
    }

    // Dipanggil di Controller Dashboard (Admin/User/Super)
    public function getStatusColor($userId) {
        $pId = $this->getProfilId($userId);
        if (!$pId) return 'red'; // Belum ada profil

        $today = date('Y-m-d');

        // 1. Cek Presensi (Hadir)
        $this->db->query("SELECT id_presensi FROM presensi WHERE id_profil = :pid AND tanggal = :date");
        $this->db->bind(':pid', $pId);
        $this->db->bind(':date', $today);
        if ($this->db->rowCount() > 0) return 'green';

        // 2. Cek Izin (Approved)
        $this->db->query("SELECT id_izin FROM izin 
                          WHERE id_profil = :pid 
                          AND :date BETWEEN start_date AND end_date 
                          AND status_approval = 'Approved'");
        $this->db->bind(':pid', $pId);
        $this->db->bind(':date', $today);
        if ($this->db->rowCount() > 0) return 'yellow';

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

    public function getAllAttendanceByDate($date) {
        $query = "SELECT p.*, 
                         prof.nama as name, 
                         prof.nim, 
                         prof.jabatan as position, 
                         prof.photo_profile 
                  FROM presensi p
                  JOIN profile prof ON p.id_profil = prof.id_profil
                  WHERE p.tanggal = :date
                  ORDER BY p.waktu_presensi ASC";
        
        $this->db->query($query);
        $this->db->bind(':date', $date);
        return $this->db->resultSet();
    }
}
?>