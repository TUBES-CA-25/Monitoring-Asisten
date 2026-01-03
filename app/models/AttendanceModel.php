<?php
class AttendanceModel {
    private $conn;
    public function __construct() { $db = new Database(); $this->conn = $db->getConnection(); }

    private function getProfilId($userId) {
        $stmt = $this->conn->prepare("SELECT id_profil FROM profile WHERE id_user = :uid");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchColumn();
    }

    public function clockIn($uid, $img) {
        $pId = $this->getProfilId($uid);
        // Simpan ke tabel presensi
        $sql = "INSERT INTO presensi (id_profil, tanggal, waktu_presensi, foto_presensi, status) 
                VALUES (:pid, CURDATE(), CURTIME(), :img, 'Hadir')";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':pid' => $pId, ':img' => $img]);
    }
 
    public function clockOut($uid, $img) {
        $pId = $this->getProfilId($uid);
        $sql = "UPDATE presensi SET waktu_pulang = CURTIME(), foto_pulang = :img 
                WHERE id_profil = :pid AND tanggal = CURDATE()";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':pid' => $pId, ':img' => $img]);
    }

    public function getMonitoringData($date) {
        // Query disesuaikan dengan tabel baru
        $sql = "SELECT p.nama as name, u.role, pr.waktu_presensi as check_in_time, pr.waktu_pulang as check_out_time, pr.status 
                FROM profile p
                JOIN user u ON p.id_user = u.id_user
                LEFT JOIN presensi pr ON p.id_profil = pr.id_profil AND pr.tanggal = :d 
                WHERE u.role = 'User' 
                ORDER BY pr.waktu_presensi DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':d' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Dashboard Stats
    public function getTodayStats() {
        $today = date('Y-m-d');
        $stats = ['hadir' => 0, 'izin' => 0, 'alpa' => 0];

        // Hitung Hadir
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM presensi WHERE tanggal = :date");
        $stmt->execute([':date' => $today]);
        $stats['hadir'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Hitung Izin (Approved)
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM izin WHERE :date BETWEEN start_date AND end_date AND status_approval = 'Approved'");
        $stmt->execute([':date' => $today]);
        $stats['izin'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Hitung Total Asisten
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM user WHERE role = 'User'");
        $stmt->execute();
        $totalAsisten = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stats['alpa'] = max(0, $totalAsisten - ($stats['hadir'] + $stats['izin']));
        return $stats;
    }

    public function getStatusColor($userId) {
        // Re-use logic from UserModel but specifically for dashboard
        return (new UserModel())->getAttendanceStatusColor($userId);
    }

    public function getChartData() {
        $weeklyData = []; $weeklyLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM presensi WHERE tanggal = :date");
            $stmt->execute([':date' => $date]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            $weeklyData[] = $res ? $res['total'] : 0;
            $weeklyLabels[] = date('D', strtotime($date));
        }
        
        // Bulanan
        $stmt = $this->conn->prepare("SELECT MONTH(tanggal) as bulan, COUNT(*) as total FROM presensi WHERE YEAR(tanggal) = YEAR(CURDATE()) GROUP BY MONTH(tanggal)");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $monthlyData = array_fill(0, 12, 0);
        foreach ($results as $res) { $monthlyData[$res['bulan'] - 1] = $res['total']; }

        return [
            'weekly' => ['labels' => $weeklyLabels, 'data' => $weeklyData],
            'monthly' => ['labels' => ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'], 'data' => $monthlyData],
            'daily' => ['labels' => ['08:00', '10:00', '12:00', '14:00', '16:00'], 'data' => [0, 0, 0, 0, 0]]
        ];
    }
}
?>