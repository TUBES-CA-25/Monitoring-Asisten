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

        $query = "INSERT INTO presensi (id_profil, tanggal, waktu_presensi, foto_presensi, status)
                  VALUES (:pid, CURDATE(), CURTIME(), :img, 'Hadir')";

        $this->db->query($query);
        $this->db->bind(':pid', $pId);
        $this->db->bind(':img', $img);
        return $this->db->execute();
    }

    public function clockOut($userId, $img)
    {
        $pId = $this->getProfilId($userId);
        if (!$pId) return false;

        $query = "UPDATE presensi SET waktu_pulang = CURTIME(), foto_pulang = :img
                  WHERE id_profil = :pid AND tanggal = CURDATE()";

        $this->db->query($query);
        $this->db->bind(':pid', $pId);
        $this->db->bind(':img', $img);
        return $this->db->execute();
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
}
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
    }
