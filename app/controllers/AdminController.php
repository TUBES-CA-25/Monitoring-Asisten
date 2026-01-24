<?php
class AdminController extends Controller {

    public function index() { $this->dashboard(); }

    public function dashboard() {
        if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Super Admin')) {
            header("Location: " . BASE_URL . "/auth/login"); exit;
        }

        $data['judul'] = 'Dashboard Admin';
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

        // Ambil Data Asisten & Cek Status Real-time
        $allUsers = $this->model('UserModel')->getAllUsers();
        $assistants = array_filter($allUsers, fn($u) => $u['role'] == 'User');
        $today = date('Y-m-d');

        foreach ($assistants as &$asisten) {
            $pId = $asisten['id_profil'];
            
            // --- [FIX] LOGIKA CEK STATUS REAL-TIME ---
            // Default: Merah (Belum Hadir)
            $asisten['status_today'] = 'red';

            // 1. Cek Presensi Hari Ini
            $stmtP = $conn->prepare("SELECT id_presensi FROM presensi WHERE id_profil = :pid AND tanggal = :d");
            $stmtP->execute([':pid' => $pId, ':d' => $today]);
            if ($stmtP->fetch()) {
                $asisten['status_today'] = 'green'; // Hadir
            } else {
                // 2. Jika tidak hadir, Cek Izin Hari Ini
                $stmtIz = $conn->prepare("SELECT id_izin FROM izin WHERE id_profil = :pid AND :d BETWEEN start_date AND end_date AND status_approval = 'Approved'");
                $stmtIz->execute([':pid' => $pId, ':d' => $today]);
                if ($stmtIz->fetch()) {
                    $asisten['status_today'] = 'yellow'; // Izin
                }
            }
            // ------------------------------------------
            
            // Statistik Individu
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

        // QR Code
        $qrModel = $this->model('QrModel');
        $data['qr_in'] = json_encode(['type'=>'CHECK_IN', 'token'=>$qrModel->getOrGenerateToken('check_in')]);
        $data['qr_out'] = json_encode(['type'=>'CHECK_OUT', 'token'=>$qrModel->getOrGenerateToken('check_out')]);

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/dashboard', $data);
        $this->view('layout/footer');
    }

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

    public function addUser() {
        if ($_SESSION['role'] != 'Admin') exit;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $photoName = null;
            if (isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != "") {
                $targetDir = "../public/uploads/profile/";
                if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                
                $fileName = time() . '_' . basename($_FILES["photo"]["name"]);
                $targetFilePath = $targetDir . $fileName;
                $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($fileType), ['jpg', 'jpeg', 'png', 'webp'])) {
                    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFilePath)) {
                        $photoName = $fileName;
                    }
                }
            }

            $nim = ($_POST['role'] == 'User') ? $_POST['nim'] : null;

            $data = [
                'email'    => $_POST['email'],
                'password' => $_POST['password'],
                'role'     => $_POST['role'],
                'name'     => $_POST['name'],
                'nim'      => $nim,
                'position' => $_POST['position'],
                'no_telp'  => $_POST['phone'],
                'alamat'   => $_POST['address'],
                'photo'    => $photoName
            ];

            if ($this->model('UserModel')->createUser($data)) {
                $_SESSION['flash'] = ['type' => 'success', 'title' => 'Berhasil', 'message' => 'User baru berhasil ditambahkan.'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Email mungkin sudah terdaftar.'];
            }
            header("Location: " . BASE_URL . "/admin/manageUsers");
            exit;
        }
    }

    public function editUser() {
        if ($_SESSION['role'] != 'Admin') exit;
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $oldUser = $this->model('UserModel')->getUserById($_POST['id_user']);
            $photoName = $oldUser['photo_profile'];

            if (isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != "") {
                $targetDir = "../public/uploads/profile/";
                if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                
                $fileName = time() . '_' . basename($_FILES["photo"]["name"]);
                $targetFilePath = $targetDir . $fileName;
                $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($fileType), ['jpg', 'jpeg', 'png', 'webp'])) {
                    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFilePath)) {
                        $photoName = $fileName;
                        
                        if ($oldUser['photo_profile'] && file_exists($targetDir . $oldUser['photo_profile'])) {
                            unlink($targetDir . $oldUser['photo_profile']);
                        }
                    }
                }
            }

            $nim = ($_POST['role'] == 'User') ? $_POST['nim'] : null;

            $data = [
                'id'       => $_POST['id_user'],
                'name'     => $_POST['name'],
                'email'    => $_POST['email'],
                'role'     => $_POST['role'],
                'nim'      => $nim,
                'position' => $_POST['position'],
                'no_telp'  => $_POST['phone'],
                'alamat'   => $_POST['address'],
                'photo'    => ($photoName != $oldUser['photo_profile']) ? $photoName : null
            ];

            if (!empty($_POST['password'])) {
                $this->model('UserModel')->changePassword($data['id'], $_POST['password']);
            }

            if ($this->model('UserModel')->updateUser($data)) {
                $_SESSION['flash'] = ['type' => 'success', 'title' => 'Berhasil', 'message' => 'Data user diperbarui.'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Gagal memperbarui data.'];
            }
            header("Location: " . BASE_URL . "/admin/manageUsers");
            exit;
        }
    }

    public function deleteUser() {
        if ($_SESSION['role'] != 'Admin') exit;
        if (isset($_GET['id'])) {
            if ($this->model('UserModel')->deleteUser($_GET['id'])) {
                $_SESSION['flash'] = ['type' => 'success', 'title' => 'Terhapus', 'message' => 'User berhasil dihapus.'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Gagal menghapus user.'];
            }
            header("Location: " . BASE_URL . "/admin/manageUsers");
            exit;
        }
    }

    public function monitorAttendance() {
        if ($_SESSION['role'] != 'Admin') exit;
        $data['judul'] = 'Rekap Presensi';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $date = $_GET['date'] ?? date('Y-m-d');
        $data['filter_date'] = $date;
        $data['attendance_list'] = $this->model('AttendanceModel')->getAllAttendanceByDate($date);

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/attendance', $data);
        $this->view('layout/footer');
    }

    public function exportCsv() {
        if ($_SESSION['role'] != 'Admin') exit;
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
        if ($_SESSION['role'] != 'Admin') exit;
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        $data['attendance_list'] = $this->model('AttendanceModel')->getAllAttendanceByDate($date);
        $data['date'] = $date;

        $this->view('admin/pdf_attendance', $data);
    }

    public function schedule() {
        if ($_SESSION['role'] != 'Admin') exit;
        $data['judul'] = 'Kelola Jadwal';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $allUsers = $this->model('UserModel')->getAllUsers();
        $data['assistants'] = array_filter($allUsers, fn($u) => $u['role'] == 'User');
        
        $data['raw_schedules'] = $this->model('ScheduleModel')->getAllSchedules(); 

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/schedule', $data);
        $this->view('layout/footer');
    }

    public function addSchedule() {
        if ($_SESSION['role'] != 'Admin') exit;
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $type = $_POST['type']; 
            $userId = ($type == 'umum') ? NULL : ($_POST['user_id'] ?? null);
            $data = [
                'type' => $type, 'user_id' => $userId,
                'title' => $_POST['title'], 'location' => $_POST['location'] ?? 'Lab',
                'dosen' => $_POST['dosen'] ?? null, 'kelas' => $_POST['kelas'] ?? null, 
                'date' => $_POST['date'], 'start_time' => $_POST['start_time'], 'end_time' => $_POST['end_time'],
                'model_perulangan' => $_POST['model_perulangan'] ?? 'sekali', 'end_date_repeat' => $_POST['end_date_repeat'] ?? null
            ];
            if ($this->model('ScheduleModel')->createSchedule($data)) { $_SESSION['flash'] = ['type' => 'success', 'title' => 'Sukses', 'message' => 'Jadwal dibuat.']; } 
            else { $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Gagal.']; }
            header("Location: " . BASE_URL . "/admin/schedule"); exit;
        }
    }

    public function editSchedule() {
        if ($_SESSION['role'] != 'Admin') exit;
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $type = $_POST['type'];
            $userId = ($type == 'umum') ? NULL : ($_POST['user_id'] ?? null);
            $data = [
                'id' => $_POST['id_schedule'], 'type' => $type, 'user_id' => $userId,
                'title' => $_POST['title'], 'location' => $_POST['location'] ?? 'Lab',
                'dosen' => $_POST['dosen'] ?? null, 'kelas' => $_POST['kelas'] ?? null, 
                'date' => $_POST['date'], 'start_time' => $_POST['start_time'], 'end_time' => $_POST['end_time'],
                'model_perulangan' => $_POST['model_perulangan'] ?? 'sekali', 'end_date_repeat' => $_POST['end_date_repeat'] ?? null
            ];
            if ($this->model('ScheduleModel')->updateSchedule($data)) { $_SESSION['flash'] = ['type' => 'success', 'title' => 'Sukses', 'message' => 'Jadwal diupdate.']; } 
            else { $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Gagal update.']; }
            header("Location: " . BASE_URL . "/admin/schedule"); exit;
        }
    }

    public function deleteSchedule() {
        if ($_SESSION['role'] != 'Admin') exit;

        if (isset($_GET['id']) && isset($_GET['type'])) {
            if ($this->model('ScheduleModel')->deleteSchedule($_GET['id'], $_GET['type'])) {
                $_SESSION['flash'] = ['type' => 'success', 'title' => 'Terhapus', 'message' => 'Jadwal berhasil dihapus.'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Gagal menghapus.'];
            }
            header("Location: " . BASE_URL . "/admin/schedule");
            exit;
        }
    }

    public function logbook() {
        if ($_SESSION['role'] != 'Admin') exit;
        $data['judul'] = 'Monitoring Logbook';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        // Ambil daftar user aktif untuk sidebar
        $allUsers = $this->model('UserModel')->getAllUsers();
        $data['assistants'] = array_filter($allUsers, fn($u) => $u['role'] == 'User');

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/logbook', $data);
        $this->view('layout/footer');
    }
    
    // [UPDATE] Mengambil Data Logbook (Unified Data)
    public function getLogsByUser() {
        if ($_SESSION['role'] != 'Admin') exit;
        $userId = $_POST['user_id'] ?? 0;
        
        // Panggil Model Cerdas (Unified) agar Admin melihat status Alpha/Izin/Hadir yang akurat
        $logs = $this->model('LogbookModel')->getUnifiedLogbook($userId);
        
        echo json_encode($logs);
    }
    
    // [BARU] Fitur Super Reset (Admin)
    public function reset_logbook() {
        if ($_SESSION['role'] != 'Admin') { 
            echo json_encode(['status'=>'error', 'message'=>'Unauthorized']); exit; 
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $idRef = $_POST['id_ref']; // ID Presensi atau ID Izin
            $type = $_POST['type'];    // 'Hadir' atau 'Izin'
            $mode = $_POST['mode'];    // 'partial' (Hapus Ket) atau 'full' (Hapus Data)

            if ($this->model('LogbookModel')->resetLogAdmin($idRef, $type, $mode)) {
                echo json_encode(['status' => 'success', 'message' => 'Logbook berhasil direset.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal mereset data.']);
            }
        }
    }

    // [OPTIONAL] Simpan Edit Manual Admin (Tetap Pertahankan)
    public function saveLogbookAdmin() {
        if ($_SESSION['role'] != 'Admin') exit;
        $data = [
            'user_id' => $_POST['user_id'], 
            'date' => $_POST['date'], 
            'time_in' => $_POST['time_in'], 
            'time_out' => $_POST['time_out'], 
            'activity' => $_POST['activity']
        ];
        
        if ($this->model('LogbookModel')->saveLogAdmin($data)) {
            echo json_encode(['status'=>'success', 'message'=>'Perubahan tersimpan.']); 
        } else {
            echo json_encode(['status'=>'error', 'message'=>'Gagal menyimpan data.']);
        }
    }
    
    public function deleteLogbook() {
        if ($_SESSION['role'] != 'Admin') exit;
        $id = $_POST['id'];
        if ($this->model('LogbookModel')->deleteLogAdmin($id)) echo json_encode(['status'=>'success']); else echo json_encode(['status'=>'error']);
    }
    
    public function profile() {
        if ($_SESSION['role'] != 'Admin') exit;
        $data['judul'] = 'Profil Admin';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $db = new Database(); $conn = $db->getConnection();
        $stmt = $conn->query("SELECT COUNT(*) as total FROM user WHERE role='User'");
        $data['total_managed_users'] = $stmt->fetch()['total'];
        $attModel = $this->model('AttendanceModel');
        $data['chart_data'] = $attModel->getChartData();
        $userModel = $this->model('UserModel');
        $scheduleModel = $this->model('ScheduleModel');
        $data['demographics'] = $userModel->getDemographics();
        $data['upcoming_schedules'] = $scheduleModel->getUpcomingSchedules();
        
        $this->view('layout/header', $data); $this->view('layout/sidebar', $data); $this->view('common/profile', $data); $this->view('layout/footer');
    }
    
    public function editProfile() {
        if ($_SESSION['role'] != 'Admin') exit;
        $data['judul'] = 'Edit Profil Admin';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $this->view('layout/header', $data); $this->view('layout/sidebar', $data); $this->view('common/edit_profile', $data); $this->view('layout/footer');
    }
    
    public function updateProfile() {
        if ($_SESSION['role'] != 'Admin') exit;
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $userModel = $this->model('UserModel');
            $currentUser = $userModel->getUserById($_SESSION['user_id']);
            if (empty($_POST['name']) || empty($_POST['phone']) || empty($_POST['address'])) { echo json_encode(['status' => 'error', 'message' => 'Data wajib diisi.']); exit; }
            $photoName = $currentUser['photo_profile']; $targetDir = "../public/uploads/profile/";
            if (!empty($_POST['cropped_image'])) {
                $dataImg = $_POST['cropped_image'];
                if (preg_match('/^data:image\/(\w+);base64,/', $dataImg, $type)) {
                    $dataImg = substr($dataImg, strpos($dataImg, ',') + 1);
                    $type = strtolower($type[1]); $decodedData = base64_decode($dataImg);
                    if ($decodedData !== false) {
                        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                        $fileName = time() . '_' . uniqid() . '.' . $type;
                        if (file_put_contents($targetDir . $fileName, $decodedData)) {
                            $photoName = $fileName; $_SESSION['photo'] = $fileName;
                            if ($currentUser['photo_profile'] && file_exists($targetDir . $currentUser['photo_profile'])) unlink($targetDir . $currentUser['photo_profile']);
                        }
                    }
                }
            } elseif (isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != "") {
                if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                $fileName = time() . '_' . basename($_FILES["photo"]["name"]);
                if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetDir . $fileName)) { $photoName = $fileName; $_SESSION['photo'] = $fileName; }
            }
            $data = [
                'id' => $_SESSION['user_id'], 'role' => 'Admin', 'name' => $_POST['name'], 'nim' => $_POST['nim'] ?? null,
                'position' => $_POST['position'] ?? 'Administrator', 'phone' => $_POST['phone'], 'address' => $_POST['address'],
                'gender' => $_POST['gender'], 'interest' => null, 'photo' => ($photoName != $currentUser['photo_profile']) ? $photoName : null
            ];
            if ($userModel->updateSelfProfile($data)) { $_SESSION['name'] = $_POST['name']; $_SESSION['jabatan'] = $_POST['position']; echo json_encode(['status' => 'success', 'message' => 'Profil berhasil diperbarui.']); } else { echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui profil.']); }
            exit;
        }
    }

    public function getQrAjax() {
        if ($_SESSION['role'] != 'Admin') exit;
        
        $type = $_POST['type'] ?? 'check_in'; // 'check_in' atau 'check_out'
        $token = $this->model('QrModel')->getOrGenerateToken($type);
        
        // Format JSON agar bisa dibaca oleh QRCodeJS di frontend
        $qrString = json_encode([
            'type' => ($type == 'check_in') ? 'CHECK_IN' : 'CHECK_OUT', 
            'token' => $token
        ]);
        
        echo json_encode(['status' => 'success', 'qr_data' => $qrString]);
    }
}
?>