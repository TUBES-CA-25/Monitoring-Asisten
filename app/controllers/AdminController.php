<?php
class AdminController extends Controller {

    public function index() { $this->dashboard(); }

    public function dashboard() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
            header("Location: " . BASE_URL . "/auth/login"); exit;
        }

        $data['judul'] = 'Dashboard Administrator';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $userModel = $this->model('UserModel');
        $attModel  = $this->model('AttendanceModel');
        $qrModel   = $this->model('QrModel');

        // 1. Statistik Ringkasan Global
        $todayStats = $attModel->getTodayStats();
        
        $db = new Database();
        $conn = $db->getConnection();
        
        // Hitung Total Asisten (Tabel: user)
        $stmt = $conn->query("SELECT COUNT(*) as total FROM user WHERE role = 'User'");
        $totalAsisten = $stmt->fetch()['total'];

        // Hitung Terlambat (Tabel: presensi)
        $stmtLate = $conn->query("SELECT COUNT(*) as total FROM presensi WHERE tanggal = CURDATE() AND waktu_presensi > '08:00:00'");
        $totalLate = $stmtLate->fetch()['total'];

        $data['stats'] = [
            'hadir_today'   => $todayStats['hadir'],
            'izin_today'    => $todayStats['izin'],
            'alpa_today'    => $todayStats['alpa'],
            'total_asisten' => $totalAsisten,
            'total_late'    => $totalLate
        ];

        // 2. Data Asisten Live (Carousel & Modal)
        $allUsers = $userModel->getAllUsers();
        // Filter array hasil query getAllUsers yang sekarang join Profile
        $assistants = array_filter($allUsers, fn($u) => $u['role'] == 'User');

        foreach ($assistants as &$asisten) {
            // A. Status Warna
            $asisten['status_today'] = $attModel->getStatusColor($asisten['id']); // id = id_user

            // B. Statistik Individu (Query Manual ke tabel presensi/izin pakai id_profil)
            // Note: getAllUsers mengembalikan 'id_profil'
            $pId = $asisten['id_profil'];

            // Hadir
            $stmtH = $conn->prepare("SELECT COUNT(*) as total FROM presensi WHERE id_profil = :pid AND status = 'Hadir'");
            $stmtH->execute([':pid' => $pId]);
            $hadir = $stmtH->fetch()['total'];

            // Izin (Approved)
            $stmtI = $conn->prepare("SELECT COUNT(*) as total FROM izin WHERE id_profil = :pid AND status_approval = 'Approved'");
            $stmtI->execute([':pid' => $pId]);
            $izin = $stmtI->fetch()['total'];

            // Alpa (Sederhana)
            $alpa = 0; 

            $asisten['stats'] = ['hadir' => $hadir, 'izin' => $izin, 'alpa' => $alpa];
        }
        $data['assistants'] = $assistants;

        // 3. Grafik
        $data['chart_data'] = $attModel->getChartData();

        // 4. QR Code Tokens (Menggunakan Model agar Validitas Waktu Terjaga)
        // check_in = Presensi (5 menit), check_out = Pulang (24 jam)
        $tokenIn  = $qrModel->getOrGenerateToken('check_in');
        $tokenOut = $qrModel->getOrGenerateToken('check_out');

        $data['qr_in'] = json_encode(['type'=>'CHECK_IN', 'token'=>$tokenIn]);
        $data['qr_out'] = json_encode(['type'=>'CHECK_OUT', 'token'=>$tokenOut]);

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/dashboard', $data);
        $this->view('layout/footer');
    }

    public function profile() {
        if ($_SESSION['role'] != 'Admin') exit;

        $data['judul'] = 'Profil Admin';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['id_user']);
        
        // Data Tambahan untuk Admin (Pengganti Statistik)
        $db = new Database(); $conn = $db->getConnection();
        
        // Hitung total user yang dimanage
        $stmt = $conn->query("SELECT COUNT(*) as total FROM user WHERE role='User'");
        $data['total_managed_users'] = $stmt->fetch()['total'];
        
        // // Hitung total jadwal
        // $stmt = $conn->query("SELECT COUNT(*) as total FROM trx_schedules");
        // $data['total_schedules'] = $stmt->fetch()['total'];

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('common/profile', $data);
        $this->view('layout/footer');
    }

    // --- MANAJEMEN USER ---
    public function manageUsers() {
        if ($_SESSION['role'] != 'Admin') exit;
        $data['judul'] = 'Manajemen Pengguna';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $data['users_list'] = $this->model('UserModel')->getAllUsers();
        
        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/users', $data);
        $this->view('layout/footer');
    }

    // --- MANAJEMEN JADWAL ---
    public function manageSchedule() {
        if ($_SESSION['role'] != 'Admin') exit;
        $data['judul'] = 'Kelola Jadwal';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $data['assistants'] = array_filter($this->model('UserModel')->getAllUsers(), fn($u) => $u['role'] == 'User');
        // Panggil getAllSchedules (Umum)
        $data['raw_schedules'] = $this->model('ScheduleModel')->getAllSchedules(); 

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/schedule', $data);
        $this->view('layout/footer');
    }

    // --- MONITORING ---
    public function monitorAttendance() {
        if ($_SESSION['role'] != 'Admin') exit;
        $data['judul'] = 'Rekap Presensi';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $date = $_GET['date'] ?? date('Y-m-d');
        $data['filter_date'] = $date;
        $data['attendance_list'] = $this->model('AttendanceModel')->getMonitoringData($date);

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/attendance', $data);
        $this->view('layout/footer');
    }

    public function monitorLogbook() {
        if ($_SESSION['role'] != 'Admin') exit;
        $data['judul'] = 'Monitoring Logbook';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $data['assistants'] = array_filter($this->model('UserModel')->getAllUsers(), fn($u) => $u['role'] == 'User');
        $data['raw_logs'] = $this->model('LogbookModel')->getAllWithUserInfo();

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/logbook', $data);
        $this->view('layout/footer');
    }
}
?>