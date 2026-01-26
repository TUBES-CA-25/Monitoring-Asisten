<?php
class SuperAdminController extends Controller {

    public function index() { $this->dashboard(); }

    public function dashboard() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Super Admin') {
            header("Location: " . BASE_URL . "/auth/login"); exit;
        }
        
        $data['judul'] = 'Dashboard Pengawas';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $attModel = $this->model('AttendanceModel');
        $todayStats = $attModel->getTodayStats(); 
        
        $db = new Database(); 
        $conn = $db->getConnection();

        // Statistik Global
        $stmt = $conn->query("SELECT COUNT(*) as total FROM user WHERE role = 'User'");
        $totalAsisten = $stmt->fetch()['total'];

        $stmtLate = $conn->query("SELECT COUNT(*) as total FROM presensi WHERE tanggal = CURDATE() AND waktu_presensi > '08:00:00'");
        $totalLate = $stmtLate->fetch()['total'];

        $data['stats'] = [
            'hadir_today'   => $todayStats['hadir'],
            'izin_today'    => $todayStats['izin'],
            'alpa_today'    => $todayStats['alpa'],
            'total_asisten' => $totalAsisten,
            'total_late'    => $totalLate
        ];

        // List Asisten
        $allUsers = $this->model('UserModel')->getAllUsers();
        $assistants = array_filter($allUsers, fn($u) => $u['role'] == 'User');
        $today = date('Y-m-d'); // Tanggal Hari Ini

        foreach ($assistants as &$asisten) {
            $pId = $asisten['id_profil'];
            
            // --- [FIX LOGIKA STATUS REAL-TIME] ---
            // Default: Merah (Belum Hadir)
            $asisten['status_today'] = 'red';

            // 1. Cek Presensi Hari Ini
            $stmtP = $conn->prepare("SELECT id_presensi FROM presensi WHERE id_profil = :pid AND tanggal = :d");
            $stmtP->execute([':pid' => $pId, ':d' => $today]);
            if ($stmtP->fetch()) {
                $asisten['status_today'] = 'green'; // Hadir
            } else {
                // 2. Jika belum hadir, Cek Izin Hari Ini
                $stmtIz = $conn->prepare("SELECT id_izin FROM izin WHERE id_profil = :pid AND :d BETWEEN start_date AND end_date AND status_approval = 'Approved'");
                $stmtIz->execute([':pid' => $pId, ':d' => $today]);
                if ($stmtIz->fetch()) {
                    $asisten['status_today'] = 'yellow'; // Izin/Sakit
                }
            }
            // -------------------------------------
            
            // Hitung statistik ringkas untuk card dashboard
            $stmtH = $conn->prepare("SELECT COUNT(*) as total FROM presensi WHERE id_profil = :pid AND status = 'Hadir'");
            $stmtH->execute([':pid' => $pId]);
            $hadir = $stmtH->fetch()['total'];

            $stmtI = $conn->prepare("SELECT COUNT(*) as total FROM izin WHERE id_profil = :pid AND status_approval = 'Approved'");
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

    public function manageUsers() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        $data['judul'] = 'Daftar Pengguna';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $data['users_list'] = $this->model('UserModel')->getAllUsers();
        
        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('superadmin/users', $data); 
        $this->view('layout/footer');
    }

    public function schedule() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        
        $data['judul'] = 'Monitoring Jadwal Lab';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $allUsers = $this->model('UserModel')->getAllUsers();
        $data['assistants'] = array_filter($allUsers, fn($u) => $u['role'] == 'User');
        $data['raw_schedules'] = $this->model('ScheduleModel')->getAllSchedules(); 
        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('superadmin/schedule', $data); 
        $this->view('layout/footer');
    }

    public function monitorAttendance() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        $data['judul'] = 'Rekap Presensi';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $date = $_GET['date'] ?? date('Y-m-d');
        $data['filter_date'] = $date;
        $data['attendance_list'] = $this->model('AttendanceModel')->getAllAttendanceByDate($date);

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('superadmin/attendance', $data); 
        $this->view('layout/footer');
    }

    public function exportCsv() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        $data = $this->model('AttendanceModel')->getAllAttendanceByDate($date);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="Laporan_Presensi_' . $date . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['No', 'Nama Asisten', 'NIM', 'Jabatan', 'Jam Masuk', 'Jam Pulang', 'Status']);

        $no = 1;
        foreach ($data as $row) {
            fputcsv($output, [
                $no++,
                $row['name'],
                $row['nim'] ?? '-',
                $row['position'] ?? 'Anggota',
                $row['check_in_time'] ? date('H:i:s', strtotime($row['check_in_time'])) : '-',
                $row['check_out_time'] ? date('H:i:s', strtotime($row['check_out_time'])) : '-',
                $row['status']
            ]);
        }
        fclose($output);
        exit;
    }

    public function exportPdf() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        $data['attendance_list'] = $this->model('AttendanceModel')->getAllAttendanceByDate($date);
        $data['date'] = $date;

        $this->view('admin/pdf_attendance', $data); 
    }

    public function logbook() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        $data['judul'] = 'Monitoring Logbook';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $allUsers = $this->model('UserModel')->getAllUsers();
        $data['assistants'] = array_filter($allUsers, fn($u) => $u['role'] == 'User');

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('superadmin/logbook', $data); 
        $this->view('layout/footer');
    }

    public function getLogsByUser() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        
        $userId = $_POST['user_id'] ?? 0;
        $logs = $this->model('LogbookModel')->getUnifiedLogbook($userId);
        
        echo json_encode($logs);
    }

    public function profile() {
        if ($_SESSION['role'] != 'Super Admin') exit;

        $data['judul'] = 'Profil Super Admin';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $db = new Database(); 
        $conn = $db->getConnection();
        
        $stmt = $conn->query("SELECT COUNT(*) as total FROM user WHERE role='User'");
        $data['total_managed_users'] = $stmt->fetch()['total'];

        $attModel = $this->model('AttendanceModel');
        $data['chart_data'] = $attModel->getChartData(); 

        $userModel = $this->model('UserModel');
        $scheduleModel = $this->model('ScheduleModel');

        $data['demographics'] = $userModel->getDemographics();
        $data['upcoming_schedules'] = $scheduleModel->getUpcomingSchedules();
        
        $data['rankings'] = [
            'online' => $userModel->getAssistantRankings('online'),
            'rajin' => $userModel->getAssistantRankings('rajin'),
            'jarang' => $userModel->getAssistantRankings('jarang'),
            'cepat' => $userModel->getAssistantRankings('cepat'),
            'terlambat' => $userModel->getAssistantRankings('terlambat'),
            'sering_izin' => $userModel->getAssistantRankings('sering_izin'),
            'logbook_lengkap' => $userModel->getAssistantRankings('logbook_lengkap'),
            'logbook_singkat' => $userModel->getAssistantRankings('logbook_singkat'),
            'sibuk' => $userModel->getAssistantRankings('sibuk'),
            'santai' => $userModel->getAssistantRankings('santai'),
        ];

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('common/profile', $data); 
        $this->view('layout/footer');
    }

    public function editProfile() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        
        $data['judul'] = 'Edit Profil';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('common/edit_profile', $data);
        $this->view('layout/footer');
    }

    public function updateProfile() {
        if ($_SESSION['role'] != 'Super Admin') exit;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $userModel = $this->model('UserModel');
            $currentUser = $userModel->getUserById($_SESSION['user_id']);

            if (empty($_POST['name']) || empty($_POST['phone']) || empty($_POST['address'])) {
                echo json_encode(['status' => 'error', 'message' => 'Data wajib diisi.']);
                exit;
            }

            $photoName = $currentUser['photo_profile'];
            $targetDir = "../public/uploads/profile/";

            if (!empty($_POST['cropped_image'])) {
                $dataImg = $_POST['cropped_image'];
                if (preg_match('/^data:image\/(\w+);base64,/', $dataImg, $type)) {
                    $dataImg = substr($dataImg, strpos($dataImg, ',') + 1);
                    $type = strtolower($type[1]); 
                    $decodedData = base64_decode($dataImg);

                    if ($decodedData !== false) {
                        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                        $fileName = time() . '_' . uniqid() . '.' . $type;
                        $filePath = $targetDir . $fileName;
                        
                        if (file_put_contents($filePath, $decodedData)) {
                            $photoName = $fileName;
                            $_SESSION['photo'] = $fileName;
                            if ($currentUser['photo_profile'] && file_exists($targetDir . $currentUser['photo_profile'])) {
                                unlink($targetDir . $currentUser['photo_profile']); 
                            }
                        }
                    }
                }
            } elseif (isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != "") {
                if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                $fileName = time() . '_' . basename($_FILES["photo"]["name"]);
                $targetFilePath = $targetDir . $fileName;
                $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($fileType), ['jpg', 'jpeg', 'png', 'webp'])) {
                    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFilePath)) {
                        $photoName = $fileName;
                        $_SESSION['photo'] = $fileName;
                    }
                }
            }

            $data = [
                'id'       => $_SESSION['user_id'],
                'role'     => 'Super Admin',
                'name'     => $_POST['name'],
                'nim'      => $_POST['nim'] ?? null,
                'position' => $_POST['position'] ?? 'Pengawas Lab',
                'phone'    => $_POST['phone'],
                'address'  => $_POST['address'],
                'gender'   => $_POST['gender'],
                'interest' => null,
                'photo'    => ($photoName != $currentUser['photo_profile']) ? $photoName : null
            ];

            if ($userModel->updateSelfProfile($data)) {
                $_SESSION['name'] = $_POST['name'];
                $_SESSION['jabatan'] = $_POST['position'];
                echo json_encode(['status' => 'success', 'message' => 'Profil berhasil diperbarui.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui profil.']);
            }
            exit;
        }
    }

    public function assistantDetail($id) {
        if ($_SESSION['role'] != 'Super Admin') exit;

        $userModel = $this->model('UserModel');
        $assistant = $userModel->getUserById($id);

        if (!$assistant || $assistant['role'] != 'User') {
            header("Location: " . BASE_URL . "/superadmin/dashboard");
            exit;
        }

        $data['judul'] = 'Detail Asisten';
        $data['user'] = $userModel->getUserById($_SESSION['user_id']); 
        $data['assistant'] = $assistant; 

        // 1. Ambil Statistik Presensi
        $db = new Database(); $conn = $db->getConnection();
        $pId = $assistant['id_profil'];
        
        $stmtH = $conn->prepare("SELECT COUNT(*) as total FROM presensi WHERE id_profil = :pid AND status = 'Hadir'");
        $stmtH->execute([':pid' => $pId]);
        $hadir = $stmtH->fetch()['total'];

        $stmtI = $conn->prepare("SELECT COUNT(*) as total FROM izin WHERE id_profil = :pid AND status_approval = 'Approved'");
        $stmtI->execute([':pid' => $pId]);
        $izin = $stmtI->fetch()['total'];

        $alpa = 0; 

        $data['stats'] = ['hadir' => $hadir, 'izin' => $izin, 'alpa' => $alpa];

        // 2. Ambil Riwayat Logbook
        $data['logs'] = $this->model('LogbookModel')->getUserLogbookHistory($id);

        // 3. Ambil Jadwal
        $data['schedules'] = $this->model('ScheduleModel')->getAllUserSchedules($id);

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('superadmin/detail_assistant', $data);
        $this->view('layout/footer');
    }

    public function assistantSchedule($id) {
        if ($_SESSION['role'] != 'Super Admin') exit;

        $assistant = $this->model('UserModel')->getUserById($id);
        if (!$assistant || $assistant['role'] != 'User') {
            header("Location: " . BASE_URL . "/superadmin/dashboard");
            exit;
        }

        $data['judul'] = 'Jadwal Asisten';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']); 
        $data['assistant'] = $assistant; 
        
        $data['schedules'] = $this->model('ScheduleModel')->getAllUserSchedules($id);

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('superadmin/assistant_schedule', $data); 
        $this->view('layout/footer');
    }
}
?>