<?php
class SuperAdminController extends Controller {

    public function index() { $this->dashboard(); }

    public function dashboard() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Super Admin') {
            header("Location: " . BASE_URL . "/auth/login"); exit;
        }

        $data['judul'] = 'Dashboard Pengawas';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $userModel = $this->model('UserModel');
        $attModel = $this->model('AttendanceModel');
        $logModel = $this->model('LogbookModel');

        $todayStats = $attModel->getTodayStats(); 
        
        $data['stats'] = [
            'hadir_today' => $todayStats['hadir'],
            'izin_today'  => $todayStats['izin'],
            'alpa_today'  => $todayStats['alpa'],
            'total_logbook' => $logModel->countTotal()['total'] // countTotal returns array assoc
        ];
        // Data Asisten
        $allUsers = $userModel->getAllUsers();
        $assistants = array_filter($allUsers, fn($u) => $u['role'] == 'User');
        $db = new Database(); $conn = $db->getConnection();

        foreach ($assistants as &$asisten) {
            $asisten['status_today'] = $attModel->getStatusColor($asisten['id']);
            $pId = $asisten['id_profil'];

            // Hitung Statistik Individu (Tabel: presensi, izin)
            $stmtH = $conn->prepare("SELECT COUNT(*) as total FROM presensi WHERE id_profil = :pid AND status = 'Hadir'");
            $stmtH->execute([':pid' => $pId]);
            $hadir = $stmtH->fetch()['total'];

            $stmtI = $conn->prepare("SELECT COUNT(*) as total FROM izin WHERE id_profil = :pid");
            $stmtI->execute([':pid' => $pId]);
            $izin = $stmtI->fetch()['total'];

            $asisten['stats'] = ['hadir' => $hadir, 'izin' => $izin, 'alpa' => 0];
        }
        $data['assistants'] = $assistants;
        $data['chart_data'] = $attModel->getChartData();

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('superadmin/dashboard', $data);
        $this->view('layout/footer');
    }

    public function profile() {
        if ($_SESSION['role'] != 'Super Admin') exit;

        $data['judul'] = 'Profil Super Admin';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['id_user']);
        
        // Data Tambahan Super Admin
        $db = new Database(); $conn = $db->getConnection();
        $stmt = $conn->query("SELECT COUNT(*) as total FROM user");
        $data['total_system_users'] = $stmt->fetch()['total'];

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('common/profile', $data);
        $this->view('layout/footer');
    }

    // --- HALAMAN DETAIL ASISTEN (NEW) ---
    public function assistantDetail($id) {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Super Admin') {
            header("Location: " . BASE_URL . "/auth/login"); exit;
        }

        $userModel = $this->model('UserModel');
        $attModel = $this->model('AttendanceModel');
        $schModel = $this->model('ScheduleModel');

        // 1. Ambil Data User by ID
        $assistant = $userModel->getUserById($id);
        
        if (!$assistant) {
            header("Location: " . BASE_URL . "/superadmin/dashboard"); exit;
        }

        $data['judul'] = 'Detail Asisten - ' . $assistant['name'];
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']); // User yang sedang login (Super Admin)
        $data['assistant'] = $assistant; // Data asisten yang dilihat

        // 2. Statistik Individu
        $db = new Database(); $conn = $db->getConnection();
        $pId = $assistant['id_profil'];

        // Hadir
        $stmtH = $conn->prepare("SELECT COUNT(*) as total FROM presensi WHERE id_profil = :pid AND status = 'Hadir'");
        $stmtH->execute([':pid' => $pId]);
        $hadir = $stmtH->fetch()['total'];

        // Izin
        $stmtI = $conn->prepare("SELECT COUNT(*) as total FROM izin WHERE id_profil = :pid AND status_approval = 'Approved'");
        $stmtI->execute([':pid' => $pId]);
        $izin = $stmtI->fetch()['total'];

        // Alpa (Sederhana)
        $alpa = 0; // Anda bisa mengembangkan logika hitung alpa yang lebih kompleks

        $data['stats'] = ['hadir' => $hadir, 'izin' => $izin, 'alpa' => $alpa];

        // 3. Jadwal Asisten Ini
        // Menggunakan method getPersonalClassSchedules (atau buat method baru khusus asisten lab schedule)
        // Kita asumsikan jadwal 'assistant' ada di tabel jadwal_asisten yang terhubung ke jadwal_full
        $stmtSch = $conn->prepare("
            SELECT ja.*, 'Asisten Lab' as type 
            FROM jadwal_asisten ja 
            WHERE ja.id_profil = :pid 
            ORDER BY ja.tanggal DESC, ja.start_time ASC
        ");
        $stmtSch->execute([':pid' => $pId]);
        $data['schedules'] = $stmtSch->fetchAll(PDO::FETCH_ASSOC);

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('superadmin/detail_assistant', $data); // Kita akan buat view ini
        $this->view('layout/footer');
    }

    public function logbook() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        $data['judul'] = 'Monitoring Logbook';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $data['assistants'] = array_filter($this->model('UserModel')->getAllUsers(), fn($u) => $u['role'] == 'User');
        $data['raw_logs'] = $this->model('LogbookModel')->getAllWithUserInfo();

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('superadmin/logbook', $data); 
        $this->view('layout/footer');
    }

    public function schedule() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        $data['judul'] = 'Jadwal Laboratorium';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $data['assistants'] = array_filter($this->model('UserModel')->getAllUsers(), fn($u) => $u['role'] == 'User');
        $data['raw_schedules'] = $this->model('ScheduleModel')->getAllSchedules();

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('superadmin/schedule', $data);
        $this->view('layout/footer');
    }

    // --- HALAMAN JADWAL SPESIFIK ASISTEN (NEW) ---
    public function assistantSchedule($id) {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Super Admin') {
            header("Location: " . BASE_URL . "/auth/login"); exit;
        }

        // 1. Ambil Data Asisten
        $assistant = $this->model('UserModel')->getUserById($id);
        if (!$assistant) {
            header("Location: " . BASE_URL . "/superadmin/dashboard"); exit;
        }

        $data['judul'] = 'Jadwal - ' . $assistant['name'];
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']); // Admin yang login
        $data['assistant'] = $assistant; // Asisten yang dilihat

        // 2. Ambil Jadwal Asisten Tersebut
        // Kita gunakan method getAllUserSchedules yang sudah ada di ScheduleModel
        // Method ini sudah mengembalikan gabungan jadwal kuliah, asisten, dan piket
        $data['schedules'] = $this->model('ScheduleModel')->getAllUserSchedules($id);

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('superadmin/assistant_schedule', $data); // View baru
        $this->view('layout/footer');
    }
}
?>