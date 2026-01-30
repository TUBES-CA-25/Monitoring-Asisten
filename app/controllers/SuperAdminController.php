<?php
class SuperAdminController extends Controller {

    public function index() { $this->dashboard(); }

    public function dashboard() {
        // if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Super Admin') {
        //     header("Location: " . BASE_URL . "/auth/login"); exit;
        // }
        $this->checkAccess(['Super Admin']);

        $data['judul'] = 'Dashboard Pengawas';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $attModel = $this->model('AttendanceModel');
        $userModel = $this->model('UserModel');
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

        $stmtAst = $conn->query("SELECT u.id_user, u.email, u.created_at,
                                        p.id_profil, p.nama, p.photo_profile, p.jabatan, 
                                        p.nim, p.no_telp, p.alamat, p.prodi, p.kelas, p.is_completed 
                                 FROM user u 
                                 JOIN profile p ON u.id_user = p.id_user 
                                 WHERE u.role = 'User' 
                                 ORDER BY p.nama ASC");
        $assistants = $stmtAst->fetchAll(PDO::FETCH_ASSOC);

        foreach ($assistants as &$ast) {
            $pid = $ast['id_profil'];

            // A. Cek Status Visual Hari Ini
            $stmtP = $conn->prepare("SELECT waktu_presensi, waktu_pulang FROM presensi WHERE id_profil = :pid AND tanggal = CURDATE()");
            $stmtP->execute([':pid' => $pid]);
            $presensi = $stmtP->fetch(PDO::FETCH_ASSOC);

            $stmtI = $conn->prepare("SELECT tipe FROM izin WHERE id_profil = :pid AND status_approval = 'Approved' AND CURDATE() BETWEEN start_date AND end_date");
            $stmtI->execute([':pid' => $pid]);
            $izin = $stmtI->fetch(PDO::FETCH_ASSOC);

            if ($presensi) {
                // Jika sudah pulang -> Merah (Offline), jika belum -> Hijau (Online)
                $ast['visual_status'] = ($presensi['waktu_pulang'] != null) ? 'offline_pulang' : 'online';
            } elseif ($izin) {
                $ast['visual_status'] = 'izin';
            } else {
                $ast['visual_status'] = 'alpha';
            }

            // B. Hitung Statistik Individu (Total Hadir/Izin/Alpa)
            $stmtH = $conn->prepare("SELECT COUNT(*) FROM presensi WHERE id_profil = :pid AND status = 'Hadir'");
            $stmtH->execute([':pid' => $pid]);
            $ast['total_hadir'] = $stmtH->fetchColumn();

            $stmtIz = $conn->prepare("SELECT COUNT(*) FROM izin WHERE id_profil = :pid AND status_approval = 'Approved'");
            $stmtIz->execute([':pid' => $pid]);
            $ast['total_izin'] = $stmtIz->fetchColumn();

            $ast['total_alpa'] = $userModel->calculateRealAlpha($pid, $ast['created_at'], $ast['is_completed']);
            // $stmtA = $conn->prepare("SELECT COUNT(*) FROM presensi WHERE id_profil = :pid AND status = 'Alpha'");
            // $stmtA->execute([':pid' => $pid]);
            // $ast['total_alpa'] = $stmtA->fetchColumn();
        }

        $data['assistants'] = $assistants;
        $chartData = [];
        
        // A. Harian
        $dLabels = []; $dData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dLabels[] = date('d M', strtotime($date));
            $stmt = $conn->query("SELECT COUNT(*) FROM presensi WHERE tanggal = '$date' AND status = 'Hadir'");
            $dData[] = $stmt->fetchColumn();
        }
        $chartData['daily'] = ['labels' => $dLabels, 'data' => $dData];

        // B. Mingguan
        $wLabels = []; $wData = [];
        for ($i = 3; $i >= 0; $i--) {
            $wStart = date('Y-m-d', strtotime("-$i weeks Monday this week"));
            $wEnd   = date('Y-m-d', strtotime("-$i weeks Sunday this week"));
            $wLabels[] = "Minggu " . date('W', strtotime($wStart));
            $stmt = $conn->query("SELECT COUNT(*) FROM presensi WHERE tanggal BETWEEN '$wStart' AND '$wEnd' AND status = 'Hadir'");
            $wData[] = $stmt->fetchColumn();
        }
        $chartData['weekly'] = ['labels' => $wLabels, 'data' => $wData];

        // C. Bulanan
        $mLabels = []; $mData = [];
        for ($i = 5; $i >= 0; $i--) {
            $mStart = date('Y-m-01', strtotime("-$i months"));
            $mEnd   = date('Y-m-t', strtotime("-$i months"));
            $mLabels[] = date('F', strtotime($mStart));
            $stmt = $conn->query("SELECT COUNT(*) FROM presensi WHERE tanggal BETWEEN '$mStart' AND '$mEnd' AND status = 'Hadir'");
            $mData[] = $stmt->fetchColumn();
        }
        $chartData['monthly'] = ['labels' => $mLabels, 'data' => $mData];

        $data['chart_data'] = $chartData;
        // $data['chart_data'] = $attModel->getChartData();

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('superadmin/dashboard', $data);
        $this->view('layout/footer');
    }

    public function manageUsers() {
        // if ($_SESSION['role'] != 'Super Admin') exit;
        $this->checkAccess(['Super Admin']);

        $this->checkAccess(['Super Admin']);
        $data['judul'] = 'Daftar Pengguna';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        // [PERBAIKAN] Query Lengkap (sama seperti Admin)
        $db = new Database();
        $conn = $db->getConnection();
        
        $query = "SELECT u.id_user as id, u.email, u.role,
                         p.nama as name, p.photo_profile, p.jabatan as position, 
                         p.nim, p.kelas, p.prodi, p.no_telp, p.alamat, p.jenis_kelamin, p.is_completed,
                         p.id_lab, l.nama_lab as lab_name
                  FROM user u
                  JOIN profile p ON u.id_user = p.id_user
                  LEFT JOIN lab l ON p.id_lab = l.id_lab
                  ORDER BY p.nama ASC";

        $stmt = $conn->prepare($query);
        $stmt->execute();
        $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Filter: Sembunyikan akun sendiri dari daftar
        $data['users_list'] = array_filter($allUsers, function($u) {
            return $u['id'] != $_SESSION['user_id'];
        });
        
        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('superadmin/users', $data); 
        $this->view('layout/footer');
    }

    public function schedule() {
        // if ($_SESSION['role'] != 'Super Admin') exit;
        $this->checkAccess(['Super Admin']);
        

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
        // if ($_SESSION['role'] != 'Super Admin') exit;
        $this->checkAccess(['Super Admin']);

        $this->checkAccess(['Super Admin']);
        
        $data['judul'] = 'Rekap Presensi';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $attModel = $this->model('AttendanceModel');

        // 1. Data Dropdown
        $data['assistants_list'] = $attModel->getAllAssistantsList();

        // 2. Filter Logic
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        $assistantId = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : null;

        $data['start_date'] = $startDate;
        $data['end_date'] = $endDate;
        $data['selected_assistant'] = $assistantId;

        // 3. Get Data
        $data['attendance_list'] = $attModel->getAttendanceRecap($startDate, $endDate, $assistantId);

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        // Pastikan view mengarah ke folder yang sesuai atau gunakan view admin jika dishare
        $this->view('superadmin/attendance', $data); 
        $this->view('layout/footer');
    }

    public function exportCsv() {
        // if ($_SESSION['role'] != 'Super Admin') exit;
        $this->checkAccess(['Super Admin']);

        $this->checkAccess(['Super Admin']);

        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        $assistantId = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : null;

        $data = $this->model('AttendanceModel')->getAttendanceRecap($startDate, $endDate, $assistantId);
        $filename = "Rekap_Presensi_" . date('d-m-Y', strtotime($startDate)) . ".csv";
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['No', 'Tanggal', 'Nama Asisten', 'NIM', 'Jabatan', 'Jam Masuk', 'Jam Pulang', 'Status']);

        $no = 1;
        foreach ($data as $row) {
            fputcsv($output, [
                $no++, $row['tanggal'], $row['name'], $row['nim'] ?? '-', $row['position'] ?? 'Anggota',
                $row['waktu_presensi'] ? date('H:i', strtotime($row['waktu_presensi'])) : '-',
                $row['waktu_pulang'] ? date('H:i', strtotime($row['waktu_pulang'])) : '-', $row['status']
            ]);
        }
        fclose($output); exit;
    }

    public function exportPdf() {
        // if ($_SESSION['role'] != 'Super Admin') exit;
        $this->checkAccess(['Super Admin']);

        $this->checkAccess(['Super Admin']);
        
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        $assistantId = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : null;
        
        $attModel = $this->model('AttendanceModel');
        $data['attendance_list'] = $attModel->getAttendanceRecap($startDate, $endDate, $assistantId);
        
        $data['start_date'] = $startDate;
        $data['end_date'] = $endDate;
        
        $data['assistant_name'] = 'Semua Asisten';
        if($assistantId) {
            $user = $this->model('UserModel')->getUserById($assistantId);
            $data['assistant_name'] = $user['name'] ?? 'Asisten';
        }

        // Menggunakan view PDF yang sama dengan Admin (Shared View)
        $this->view('admin/pdf_attendance', $data);
    }

    public function logbook() {
        // if ($_SESSION['role'] != 'Super Admin') exit;
        $this->checkAccess(['Super Admin']);

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
        // if ($_SESSION['role'] != 'Super Admin') exit;
        $this->checkAccess(['Super Admin']);

        
        $userId = $_POST['user_id'] ?? 0;
        $logs = $this->model('LogbookModel')->getUnifiedLogbook($userId);
        
        echo json_encode($logs);
    }

    public function profile() {
        // if ($_SESSION['role'] != 'Super Admin') exit;
        $this->checkAccess(['Super Admin']);


        $data['judul'] = 'Profil Super Admin';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $db = new Database(); 
        $conn = $db->getConnection();

        $stmtG = $conn->prepare("SELECT id_token FROM user_google_token WHERE id_user = :uid");
        $stmtG->execute([':uid' => $_SESSION['user_id']]);
        $data['is_google_connected'] = $stmtG->rowCount() > 0;
        
        // 1. Total Asisten
        $stmt = $conn->query("SELECT COUNT(*) as total FROM user WHERE role='User'");
        $data['total_managed_users'] = $stmt->fetch()['total'];

        // 2. Chart Kehadiran
        $attModel = $this->model('AttendanceModel');
        $data['chart_data'] = $attModel->getChartData(); 

        $userModel = $this->model('UserModel');

        // 3. Demografi
        $data['demographics'] = $userModel->getDemographics();

        $stmtSch = $conn->query("SELECT * FROM jadwal_lab 
                                 WHERE tanggal >= CURDATE() 
                                 ORDER BY tanggal ASC, jam_mulai ASC 
                                 LIMIT 5");
        $rawSchedules = $stmtSch->fetchAll(PDO::FETCH_ASSOC);
        
        // Format Tanggal & Mapping
        foreach ($rawSchedules as &$sch) {
            $sch['display_date'] = date('d M Y', strtotime($sch['tanggal']));
            // Inject type agar dibaca 'UMUM' oleh view
            $sch['type'] = 'umum';
        }
        $data['upcoming_schedules'] = $rawSchedules;
        
        // 5. Peringkat Asisten
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
        // if ($_SESSION['role'] != 'Super Admin') exit;
        $this->checkAccess(['Super Admin']);

        
        $data['judul'] = 'Edit Profil';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('common/edit_profile', $data);
        $this->view('layout/footer');
    }

    public function updateProfile() {
        // if ($_SESSION['role'] != 'Super Admin') exit;
        $this->checkAccess(['Super Admin']);


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
                'position' => $_POST['position'] ?? 'Kepala Lab',
                'prodi'    => null,
                'phone'    => $_POST['phone'],
                'address'  => $_POST['address'],
                'gender'   => $_POST['gender'],
                'interest' => null,
                'photo'    => ($photoName != $currentUser['photo_profile']) ? $photoName : null,
                // 'is_completed' => $isCompleted
            ];

            if ($userModel->updateSelfProfile($data)) {
                $_SESSION['name'] = $_POST['name'];
                $_SESSION['jabatan'] = $_POST['position'];
                
                // [PERBAIKAN UTAMA: Masalah Undefined & Redirect]
                echo json_encode([
                    'status'   => 'success', 
                    'title'    => 'Berhasil',
                    'message'  => 'Profil berhasil diperbarui.',
                    'redirect' => BASE_URL . '/superadmin/profile'
                ]);
            } else {
                echo json_encode(['status' => 'error', 'title' => 'Gagal', 'message' => 'Gagal memperbarui profil.']);
            }
            exit;
        }
    }

    public function assistantDetail($id) {
        // if ($_SESSION['role'] != 'Super Admin') exit;
        $this->checkAccess(['Super Admin']);


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
        // if ($_SESSION['role'] != 'Super Admin') exit;
        $this->checkAccess(['Super Admin']);


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
     // --- FUNGSI BANTUAN UNTUK CEK AKSES & ERROR 401 ---
    private function checkAccess($allowedRoles = ['Admin']) {
        // 1. Cek Login
        if (!isset($_SESSION['role'])) {
            header("Location: " . BASE_URL . "/auth/login");
            exit;
        }

        // 2. Cek Role (Jika role user tidak ada dalam daftar yang diizinkan)
        if (!in_array($_SESSION['role'], $allowedRoles)) {
            require_once '../app/controllers/ErrorController.php';
            $error = new ErrorController();
            $error->unauthorized();
            exit; // Matikan script agar halaman admin tidak bocor
        }
    }
}
?>