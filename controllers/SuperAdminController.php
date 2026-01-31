<?php
class SuperAdminController extends Controller {

    public function index() { $this->dashboard(); }

    public function dashboard() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Super Admin') {
            header("Location: " . BASE_URL . "/auth/login"); exit;
        }

        $data['judul'] = 'Dashboard Pengawas';
        
        $userModel = $this->model('UserModel');
        $attModel = $this->model('AttendanceModel');
        $qrModel = $this->model('QrModel');

        $data['user'] = $userModel->getUserById($_SESSION['user_id']);
        
        $todayStats = $attModel->getTodayStats(); 
        $totalAsisten = $userModel->countUsersByRole('User');
        $totalLate = $attModel->countLateToday();
        
        $data['stats'] = [
            'hadir_today'   => $todayStats['hadir'],
            'izin_today'    => $todayStats['izin'],
            'alpa_today'    => $todayStats['alpa'],
            'total_asisten' => $totalAsisten,
            'total_late'    => $totalLate 
        ];

        $allUsers = $userModel->getAllUsers();
        $assistants = array_filter($allUsers, fn($u) => $u['role'] == 'User');
        
        foreach ($assistants as &$ast) {
            $pid = $ast['id_profil'];

            $statusDetail = $attModel->getTodayAttendanceDetail($pid);
            $presensi = $statusDetail['presensi'];
            $izin = $statusDetail['izin'];

            if ($presensi && !empty($presensi['waktu_presensi'])) {
                $ast['visual_status'] = ($presensi['waktu_pulang'] != null) ? 'offline_pulang' : 'online';
            } elseif ($izin) {
                $ast['visual_status'] = 'izin';
            } else {
                $ast['visual_status'] = 'alpha';
            }

            $userStats = $attModel->getUserStats($pid);
            $ast['total_hadir'] = $userStats['hadir'];
            $ast['total_izin'] = $userStats['izin'];
            
            $createdAt = $ast['created_at'] ?? date('Y-m-d');
            $isCompleted = $ast['is_completed'] ?? 0;
            $ast['total_alpa'] = $userModel->calculateRealAlpha($pid, $createdAt, $isCompleted);
        }

        $data['assistants'] = $assistants;
        $data['chart_data'] = $attModel->getChartData();

        $data['qr_in'] = json_encode(['type'=>'CHECK_IN', 'token'=>$qrModel->getOrGenerateToken('check_in')]);
        $data['qr_out'] = json_encode(['type'=>'CHECK_OUT', 'token'=>$qrModel->getOrGenerateToken('check_out')]);

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('superadmin/dashboard', $data);
        $this->view('layout/footer');
    }

    public function manageUsers() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        
        $data['judul'] = 'Daftar Pengguna';
        $userModel = $this->model('UserModel');
        $data['user'] = $userModel->getUserById($_SESSION['user_id']);
        
        $allUsers = $userModel->getAllUsers();
        $data['users_list'] = array_filter($allUsers, function($u) {
            return $u['id'] != $_SESSION['user_id'];
        });
        
        $data['labs'] = $this->model('LabModel')->getAllLabs();
        
        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('superadmin/users', $data); 
        $this->view('layout/footer');
    }

    public function addUser() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        echo json_encode(['status' => 'error', 'title' => 'Akses Ditolak', 'message' => 'Super Admin hanya memiliki akses lihat (Read-Only).']);
    }

    public function editUser() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        echo json_encode(['status' => 'error', 'title' => 'Akses Ditolak', 'message' => 'Super Admin hanya memiliki akses lihat (Read-Only).']);
    }

    public function deleteUser() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        echo json_encode(['status' => 'error', 'title' => 'Akses Ditolak', 'message' => 'Super Admin hanya memiliki akses lihat (Read-Only).']);
    }

    public function schedule() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        
        $data['judul'] = 'Monitoring Jadwal Lab';
        $userModel = $this->model('UserModel');
        $data['user'] = $userModel->getUserById($_SESSION['user_id']);
        
        $allUsers = $userModel->getAllUsers();
        $data['assistants'] = array_filter($allUsers, fn($u) => $u['role'] == 'User');
        
        $data['raw_schedules'] = $this->model('ScheduleModel')->getAllSchedules(); 
        
        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('superadmin/schedule', $data); 
        $this->view('layout/footer');
    }

    public function addSchedule() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        $_SESSION['flash'] = ['type' => 'error', 'title' => 'Akses Ditolak', 'message' => 'Super Admin hanya memiliki akses lihat (Read-Only).'];
        header("Location: " . BASE_URL . "/superadmin/schedule");
    }

    public function editSchedule() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        $_SESSION['flash'] = ['type' => 'error', 'title' => 'Akses Ditolak', 'message' => 'Super Admin hanya memiliki akses lihat (Read-Only).'];
        header("Location: " . BASE_URL . "/superadmin/schedule");
    }

    public function deleteSchedule() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        $_SESSION['flash'] = ['type' => 'error', 'title' => 'Akses Ditolak', 'message' => 'Super Admin hanya memiliki akses lihat (Read-Only).'];
        header("Location: " . BASE_URL . "/superadmin/schedule");
    }

    public function monitorAttendance() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        
        $data['judul'] = 'Rekap Presensi';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $attModel = $this->model('AttendanceModel');

        $data['assistants_list'] = $attModel->getAllAssistantsList();

        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        $assistantId = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : null;

        $data['start_date'] = $startDate;
        $data['end_date'] = $endDate;
        $data['selected_assistant'] = $assistantId;

        $data['attendance_list'] = $attModel->getAttendanceRecap($startDate, $endDate, $assistantId);

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('superadmin/attendance', $data); 
        $this->view('layout/footer');
    }

    public function exportCsv() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        
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
        if ($_SESSION['role'] != 'Super Admin') exit;
        
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

        $this->view('superadmin/pdf_attendance', $data);
    }

    public function logbook() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        $data['judul'] = 'Monitoring Logbook';
        $userModel = $this->model('UserModel');
        $data['user'] = $userModel->getUserById($_SESSION['user_id']);
        
        $allUsers = $userModel->getAllUsers();
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

    public function reset_logbook() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        echo json_encode(['status' => 'error', 'message' => 'Akses Ditolak. Super Admin hanya memiliki akses lihat (Read-Only).']);
    }

    public function saveLogbookAdmin() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        echo json_encode(['status' => 'error', 'message' => 'Akses Ditolak. Super Admin hanya memiliki akses lihat (Read-Only).']);
    }

    public function deleteLogbook() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        echo json_encode(['status' => 'error', 'message' => 'Akses Ditolak. Super Admin hanya memiliki akses lihat (Read-Only).']);
    }

    public function profile() {
        if ($_SESSION['role'] != 'Super Admin') exit;

        $data['judul'] = 'Profil Super Admin';
        $userModel = $this->model('UserModel');
        $data['user'] = $userModel->getUserById($_SESSION['user_id']);
        
        $data['is_google_connected'] = $userModel->isGoogleConnected($_SESSION['user_id']);
        $data['total_managed_users'] = $userModel->countUsersByRole('User');

        $attModel = $this->model('AttendanceModel');
        $data['chart_data'] = $attModel->getChartData(); 

        $data['demographics'] = $userModel->getDemographics();
        
        $allSchedules = $this->model('ScheduleModel')->getAllSchedules();
        $upcoming = array_filter($allSchedules, function($s) {
            return ($s['type'] == 'umum') && ($s['start_date'] >= date('Y-m-d'));
        });
        usort($upcoming, function($a, $b) { return strtotime($a['start_date']) - strtotime($b['start_date']); });
        $data['upcoming_schedules'] = array_slice($upcoming, 0, 5);
        foreach($data['upcoming_schedules'] as &$sch) {
            $sch['display_date'] = date('d M Y', strtotime($sch['start_date']));
        }
        
        $data['rankings'] = [
            'online' => $userModel->getAssistantRankings('online'),
            'rajin' => $userModel->getAssistantRankings('rajin'),
            'terlambat' => $userModel->getAssistantRankings('terlambat'),
            'logbook_lengkap' => $userModel->getAssistantRankings('logbook_lengkap'),
            'sibuk' => $userModel->getAssistantRankings('sibuk')
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
        $data['labs'] = $this->model('LabModel')->getAllLabs();

        $data['role'] = $_SESSION['role'];
        $data['isUser'] = ($_SESSION['role'] == 'User');
        $data['isAdmin'] = ($_SESSION['role'] == 'Admin'); 

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('common/edit_profile', $data);
        $this->view('layout/footer');
    }

    public function updateProfile() {
        if ($_SESSION['role'] != 'Super Admin') exit;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            ob_clean(); header('Content-Type: application/json');
            $userModel = $this->model('UserModel');
            $currentUser = $userModel->getUserById($_SESSION['user_id']);

            if (empty($_POST['name']) || empty($_POST['phone']) || empty($_POST['address'])) {
                echo json_encode(['status' => 'error', 'message' => 'Data wajib diisi.']); exit;
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
                        if (file_put_contents($targetDir . $fileName, $decodedData)) {
                            $photoName = $fileName;
                            $_SESSION['photo'] = $fileName;
                            if ($currentUser['photo_profile'] && $currentUser['photo_profile'] != 'default.jpg' && file_exists($targetDir . $currentUser['photo_profile'])) {
                                unlink($targetDir . $currentUser['photo_profile']);
                            }
                        }
                    }
                }
            } elseif (isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != "") {
                if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                $fileName = time() . '_' . basename($_FILES["photo"]["name"]);
                if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetDir . $fileName)) {
                    $photoName = $fileName;
                    $_SESSION['photo'] = $fileName;
                }
            }

            $isCompleted = (!empty($_POST['name']) && !empty($_POST['phone']) && !empty($_POST['address'])) ? 1 : 0;
            $labId = !empty($_POST['lab_id']) ? $_POST['lab_id'] : null;

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
                'is_completed' => $isCompleted,
                'lab_id'   => $labId
            ];

            if ($userModel->updateSelfProfile($data)) {
                $_SESSION['name'] = $_POST['name'];
                $_SESSION['jabatan'] = $_POST['position'];
                
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

    public function getQrAjax() {
        if ($_SESSION['role'] != 'Super Admin') exit;
        
        $type = $_POST['type'] ?? 'check_in'; 
        $token = $this->model('QrModel')->getOrGenerateToken($type);
        
        $qrString = json_encode([
            'type' => ($type == 'check_in') ? 'CHECK_IN' : 'CHECK_OUT', 
            'token' => $token
        ]);
        
        echo json_encode(['status' => 'success', 'qr_data' => $qrString]);
    }

    public function assistantDetail($id) {
        if ($_SESSION['role'] != 'Super Admin') exit;

        $userModel = $this->model('UserModel');
        $attModel = $this->model('AttendanceModel');
        $assistant = $userModel->getUserById($id);

        if (!$assistant || $assistant['role'] != 'User') {
            header("Location: " . BASE_URL . "/superadmin/dashboard");
            exit;
        }

        $data['judul'] = 'Detail Asisten';
        $data['user'] = $userModel->getUserById($_SESSION['user_id']); 
        $data['assistant'] = $assistant; 

        $pId = $assistant['id_profil'];
        $userStats = $attModel->getUserStats($pId);
        $alpa = $userModel->calculateRealAlpha($pId, $assistant['created_at'], $assistant['is_completed']);

        $data['stats'] = [
            'hadir' => $userStats['hadir'], 
            'izin' => $userStats['izin'], 
            'alpa' => $alpa
        ];

        $data['logs'] = $this->model('LogbookModel')->getUserLogbookHistory($id);
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