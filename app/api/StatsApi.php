<?php
require_once '../app/core/ApiResponse.php';
require_once '../app/api/AuthApi.php';

class StatsApi {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * GET /api/stats/attendance-monthly
     * Get attendance stats breakdown per bulan tahun ini
     */
    public function attendanceMonthly() {
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

        try {
            $query = "SELECT 
                        MONTH(tanggal) as month,
                        MONTHNAME(tanggal) as month_name,
                        COUNT(*) as total_hari,
                        SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) as hadir,
                        SUM(CASE WHEN status = 'Alpa' THEN 1 ELSE 0 END) as alpa,
                        SUM(CASE WHEN status = 'Terlambat' THEN 1 ELSE 0 END) as terlambat,
                        ROUND(SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as attendance_percentage
                      FROM presensi
                      WHERE id_profil = :pid
                      AND YEAR(tanggal) = :year
                      GROUP BY MONTH(tanggal)
                      ORDER BY MONTH(tanggal) ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([':pid' => $profilId, ':year' => $year]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format data for charts
            $months = [];
            $hadir = [];
            $alpa = [];
            $terlambat = [];

            foreach ($data as $row) {
                $months[] = $row['month_name'];
                $hadir[] = (int)$row['hadir'];
                $alpa[] = (int)$row['alpa'];
                $terlambat[] = (int)$row['terlambat'];
            }

            $responseData = [
                'year' => $year,
                'detailed' => $data,
                'chart_data' => [
                    'months' => $months,
                    'hadir' => $hadir,
                    'alpa' => $alpa,
                    'terlambat' => $terlambat
                ]
            ];

            ApiResponse::success($responseData, 'Monthly attendance statistics', 200);
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/stats/attendance-yearly
     * Get attendance stats per tahun (last 3 years)
     */
    public function attendanceYearly() {
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        try {
            $query = "SELECT 
                        YEAR(tanggal) as year,
                        COUNT(*) as total_hari,
                        SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) as hadir,
                        SUM(CASE WHEN status = 'Alpa' THEN 1 ELSE 0 END) as alpa,
                        SUM(CASE WHEN status = 'Terlambat' THEN 1 ELSE 0 END) as terlambat,
                        ROUND(SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as attendance_percentage
                      FROM presensi
                      WHERE id_profil = :pid
                      GROUP BY YEAR(tanggal)
                      ORDER BY YEAR(tanggal) DESC
                      LIMIT 5";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([':pid' => $profilId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $responseData = [
                'yearly_stats' => $data,
                'summary' => [
                    'total_records' => array_sum(array_column($data, 'total_hari')),
                    'total_hadir' => array_sum(array_column($data, 'hadir')),
                    'total_alpa' => array_sum(array_column($data, 'alpa')),
                    'total_terlambat' => array_sum(array_column($data, 'terlambat'))
                ]
            ];

            ApiResponse::success($responseData, 'Yearly attendance statistics', 200);
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/stats/punctuality
     * Get punctuality analysis - how often user is late
     * Query: ?month=05&year=2026 (optional)
     */
    public function punctuality() {
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
        $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

        try {
            $query = "SELECT 
                        DATE(tanggal) as tanggal,
                        waktu_presensi,
                        status,
                        CASE 
                            WHEN TIME(waktu_presensi) <= '08:00:00' THEN 'Tepat Waktu'
                            WHEN TIME(waktu_presensi) <= '09:00:00' THEN 'Kurang dari 1 jam'
                            ELSE 'Lebih dari 1 jam'
                        END as punctuality_status
                      FROM presensi
                      WHERE id_profil = :pid
                      AND MONTH(tanggal) = :month
                      AND YEAR(tanggal) = :year
                      AND status != 'Alpa'
                      ORDER BY tanggal ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':pid' => $profilId,
                ':month' => $month,
                ':year' => $year
            ]);
            $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate summary
            $onTime = 0;
            $lessThanOneHour = 0;
            $moreThanOneHour = 0;

            foreach ($details as $record) {
                if ($record['punctuality_status'] === 'Tepat Waktu') $onTime++;
                elseif ($record['punctuality_status'] === 'Kurang dari 1 jam') $lessThanOneHour++;
                else $moreThanOneHour++;
            }

            $total = $onTime + $lessThanOneHour + $moreThanOneHour;
            $onTimePercentage = $total > 0 ? round(($onTime / $total) * 100, 2) : 0;

            $responseData = [
                'month' => $month,
                'year' => $year,
                'summary' => [
                    'tepat_waktu' => $onTime,
                    'kurang_dari_1_jam' => $lessThanOneHour,
                    'lebih_dari_1_jam' => $moreThanOneHour,
                    'total_hadir' => $total,
                    'punctuality_percentage' => $onTimePercentage,
                    'rating' => $this->getPunctualityRating($onTimePercentage)
                ],
                'details' => $details
            ];

            ApiResponse::success($responseData, 'Punctuality analysis', 200);
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Helper function untuk rating punctuality
     */
    private function getPunctualityRating($percentage) {
        if ($percentage >= 90) return 'Excellent (Sangat Baik)';
        elseif ($percentage >= 75) return 'Good (Baik)';
        elseif ($percentage >= 60) return 'Fair (Cukup)';
        else return 'Poor (Kurang)';
    }
}
?>
