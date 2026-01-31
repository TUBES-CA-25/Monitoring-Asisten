<?php
class UserController extends Controller {
    public function index() { 
        $this->dashboard(); 
    }

    public function dashboard() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'User') {
            header("Location: " . BASE_URL . "/auth/login"); exit;
        }

        $data['judul'] = 'Dashboard Asisten';
        
        $userModel = $this->model('UserModel');
        $attModel = $this->model('AttendanceModel');
        $schModel = $this->model('ScheduleModel');

        $user = $userModel->getUserById($_SESSION['user_id']);
        $data['user'] = $user;
        
        $uid = $_SESSION['user_id'];
        $pId = $_SESSION['profil_id']; 

        $userStats = $attModel->getUserStats($pId);
        $alpa = $userModel->calculateRealAlpha($pId, $user['created_at'], $user['is_completed']);
        
        $data['stats'] = [
            'hadir' => $userStats['hadir'], 
            'izin' => $userStats['izin'], 
            'alpa' => $alpa
        ];
        
        $todayStatus = $attModel->getTodayAttendanceDetail($pId);
        $presensiToday = $todayStatus['presensi'];
        $izinToday = $todayStatus['izin'];

        $data['status_today'] = 'red';
        $data['is_working'] = false;

        if ($presensiToday && !empty($presensiToday['waktu_presensi'])) {
            $data['status_today'] = 'green';
            if (empty($presensiToday['waktu_pulang'])) {
                $data['is_working'] = true;
            }
        } elseif ($izinToday) {
            $data['status_today'] = 'yellow';
        }

        $data['weekly_schedule'] = $schModel->getUserScheduleForWeek($uid); 

        $dailyChart = $attModel->getUserDailyChart($pId);
        $data['chart_data'] = [
            'daily' => $dailyChart,
            'weekly' => ['labels' => [], 'data' => []],
            'monthly' => ['labels' => [], 'data' => []]
        ];

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data); 
        $this->view('user/dashboard', $data);
        $this->view('layout/footer');
    }

    public function profile() {
        if ($_SESSION['role'] != 'User') exit;

        $data['judul'] = 'Profil Saya';
        $userModel = $this->model('UserModel');
        $attModel = $this->model('AttendanceModel');

        $data['user'] = $userModel->getUserById($_SESSION['user_id']);
        $pId = $_SESSION['profil_id'];

        $data['is_google_connected'] = $userModel->isGoogleConnected($_SESSION['user_id']);
        
        $userStats = $attModel->getUserStats($pId);
        $data['stats'] = ['hadir' => $userStats['hadir'], 'izin' => $userStats['izin'], 'alpa' => 0];

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('common/profile', $data);
        $this->view('layout/footer');
    }

    public function editProfile() {
        if ($_SESSION['role'] != 'User') exit;
        
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
        if ($_SESSION['role'] != 'User') exit;
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            ob_clean(); header('Content-Type: application/json');
            
            $photoName = $_POST['old_photo'];
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $targetDir = "../public/uploads/profile/";
                if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                
                $fileExt = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
                $newFileName = uniqid() . '.' . $fileExt;
                
                if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetDir . $newFileName)) {
                    $photoName = $newFileName;
                }
            }

            $labId = !empty($_POST['lab_id']) ? $_POST['lab_id'] : null;

            $data = [
                'id' => $_SESSION['user_id'],
                'name' => $_POST['name'],
                'nim' => $_POST['nim'],
                'class' => $_POST['class'],
                'prodi' => $_POST['prodi'],
                'phone' => $_POST['phone'],
                'address' => $_POST['address'],
                'gender' => $_POST['gender'],
                'position' => $_POST['position'],
                'lab_id' => $labId,
                'interest' => $_POST['interest'] ?? '',
                'photo' => $photoName,
                'is_completed' => 1
            ];

            if ($this->model('UserModel')->updateSelfProfile($data)) {
                $_SESSION['name'] = $_POST['name']; 
                $_SESSION['flash'] = ['type' => 'success', 'title' => 'Berhasil', 'message' => 'Profil berhasil diperbarui.'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Gagal memperbarui profil.'];
            }
            header("Location: " . BASE_URL . "/user/profile");
            exit;
        }
    }

    public function logbook() {
        if ($_SESSION['role'] != 'User') exit;
        
        $data['judul'] = 'Logbook Kegiatan';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $data['logs'] = $this->model('LogbookModel')->getUnifiedLogbook($_SESSION['user_id']); 
        
        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('user/logbook', $data);
        $this->view('layout/footer');
    }

    public function submit_logbook() {
        if ($_SESSION['role'] != 'User') { echo json_encode(['status'=>'error', 'message'=>'Unauthorized']); exit; }

        $pId = $_SESSION['profil_id']; 
        $today = date('Y-m-d');
        
        $att = $this->model('AttendanceModel')->validateLogbookEntry($pId, $today);

        if (!$att || !$att['waktu_presensi']) {
            echo json_encode(['status'=>'error', 'message'=>'Anda belum melakukan scan masuk!']); exit;
        }
        if ($att['waktu_pulang']) {
            echo json_encode(['status'=>'error', 'message'=>'Logbook terkunci karena Anda sudah scan pulang.']); exit;
        }

        $payload = [
            'user_id'  => $_SESSION['user_id'],
            'date'     => $today,
            'time'     => $_POST['time'] ?? date('H:i'),
            'activity' => $_POST['activity']
        ];

        if ($this->model('LogbookModel')->saveLogbook($payload)) {
            echo json_encode(['status'=>'success']);
        } else {
            echo json_encode(['status'=>'error', 'message'=>'Gagal menyimpan data database.']);
        }
    }

    public function reset_logbook() {
        if ($_SESSION['role'] != 'User') { echo json_encode(['status'=>'error', 'message'=>'Unauthorized']); exit; }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if ($this->model('LogbookModel')->resetLogUser($_POST['log_id'], $_SESSION['user_id'])) {
                echo json_encode(['status' => 'success', 'message' => 'Isi logbook berhasil dikosongkan.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal mereset logbook.']);
            }
        }
    }

    public function schedule() {
        if ($_SESSION['role'] != 'User') exit;
        
        $data['judul'] = 'Jadwal Saya';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $data['raw_schedules'] = $this->model('ScheduleModel')->getAllUserSchedules($_SESSION['user_id']); 

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('user/schedule', $data);
        $this->view('layout/footer');
    }

    public function addSchedule() {
        if ($_SESSION['role'] != 'User') exit;
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $data = [
                'type' => 'kuliah', 
                'user_id' => $_SESSION['profil_id'],
                'title' => $_POST['title'],
                'location' => $_POST['location'],
                'dosen' => $_POST['dosen'],
                'kelas' => $_POST['kelas'], 
                'date' => $_POST['date'],
                'start_time' => $_POST['start_time'],
                'end_time' => $_POST['end_time'],
                'model_perulangan' => $_POST['model_perulangan'] ?? 'sekali',
                'end_date_repeat' => $_POST['end_date_repeat'] ?? null
            ];
            
            if ($this->model('ScheduleModel')->createSchedule($data)) {
                $_SESSION['flash'] = ['type' => 'success', 'title' => 'Sukses', 'message' => 'Jadwal kuliah berhasil dibuat & disinkronkan.'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Gagal membuat jadwal.'];
            }
            header("Location: " . BASE_URL . "/user/schedule"); exit;
        }
    }

    public function editSchedule() {
        if ($_SESSION['role'] != 'User') exit;
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $data = [
                'id' => $_POST['id_schedule'], 
                'type' => 'kuliah', 
                'user_id' => $_SESSION['profil_id'],
                'title' => $_POST['title'],
                'location' => $_POST['location'],
                'dosen' => $_POST['dosen'],
                'kelas' => $_POST['kelas'], 
                'date' => $_POST['date'],
                'start_time' => $_POST['start_time'],
                'end_time' => $_POST['end_time'],
                'model_perulangan' => $_POST['model_perulangan'] ?? 'sekali',
                'end_date_repeat' => $_POST['end_date_repeat'] ?? null
            ];
            
            if ($this->model('ScheduleModel')->updateSchedule($data)) {
                $_SESSION['flash'] = ['type' => 'success', 'title' => 'Sukses', 'message' => 'Jadwal diperbarui.'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Gagal update jadwal.'];
            }
            header("Location: " . BASE_URL . "/user/schedule"); exit;
        }
    }

    public function deleteSchedule() {
        if ($_SESSION['role'] != 'User') exit;
        
        $id = $_GET['id'];
        $type = $_GET['type'];

        if ($type !== 'kuliah') {
            $_SESSION['flash'] = ['type' => 'error', 'title' => 'Ditolak', 'message' => 'Hanya jadwal kuliah yang bisa dihapus.'];
            header("Location: " . BASE_URL . "/user/schedule"); exit;
        }

        if ($this->model('ScheduleModel')->deleteSchedule($id, 'kuliah', $_SESSION['profil_id'])) {
            $_SESSION['flash'] = ['type' => 'success', 'title' => 'Terhapus', 'message' => 'Jadwal berhasil dihapus.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Gagal menghapus jadwal.'];
        }
        header("Location: " . BASE_URL . "/user/schedule"); exit;
    }

    public function scan() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'User') { 
            header("Location: " . BASE_URL . "/auth/login"); exit; 
        }
        $data['judul'] = 'Scan Presensi';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $this->view('user/scan', $data); 
    }

    public function check_qr_type() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            ob_clean(); header('Content-Type: application/json');
            $rawToken = $_POST['token'] ?? ''; 
            $token = trim($rawToken); 
            
            $decoded = json_decode($rawToken, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['token'])) { 
                $token = $decoded['token']; 
            }
            
            $tokenInfo = $this->model('QrModel')->getTokenData($token);
            
            if (!$tokenInfo) { 
                echo json_encode(['status' => 'error', 'message' => 'QR Code tidak valid atau sudah kadaluwarsa.']); 
            } else { 
                echo json_encode(['status' => 'success', 'type' => $tokenInfo['tipe']]); 
            }
        }
    }

    public function submit_attendance() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            ob_clean(); header('Content-Type: application/json');
            $token = $_POST['token'];
            $image = $_POST['image'];
            $typeInput = $_POST['type']; 

            if (!$this->model('QrModel')->validateToken($token, $typeInput)) {
                echo json_encode(['status' => 'error', 'message' => 'Token QR Code tidak valid/sesuai.']);
                exit;
            }

            $folderPath = "../public/uploads/attendance/";
            if (!file_exists($folderPath)) mkdir($folderPath, 0777, true);

            $image_parts = explode(";base64,", $image);
            if (count($image_parts) < 2) {
                 echo json_encode(['status' => 'error', 'message' => 'Format gambar tidak valid.']); exit;
            }
            $image_base64 = base64_decode($image_parts[1]);
            $fileName = $_SESSION['user_id'] . '_' . time() . '.png';
            file_put_contents($folderPath . $fileName, $image_base64);

            $attModel = $this->model('AttendanceModel');
            $userId = $_SESSION['user_id'];
            $success = false;

            if ($typeInput == 'check_in') {
                $success = $attModel->clockIn($userId, $fileName);
                $msg = $success ? 'Berhasil Check-In!' : 'Gagal Check-In / Anda sudah absen hari ini.';
            } else {
                $success = $attModel->clockOut($userId, $fileName);
                $msg = $success ? 'Berhasil Check-Out!' : 'Gagal Check-Out / Belum saatnya pulang.';
            }

            if ($success) {
                echo json_encode(['status' => 'success', 'message' => $msg]);
            } else {
                echo json_encode(['status' => 'error', 'message' => $msg]);
            }
        }
    }

    public function submit_leave() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $pId = $_SESSION['profil_id'];
            $type = $_POST['type'];
            $reason = $_POST['reason'];
            $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];

            $fileName = '';
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $targetDir = "../public/uploads/leaves/";
                if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

                $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
                $fileExt = strtolower(pathinfo($_FILES["attachment"]["name"], PATHINFO_EXTENSION));

                if (in_array($fileExt, $allowedTypes)) {
                    $fileName = "leave_" . $_SESSION['user_id'] . '_' . time() . '.' . $fileExt;
                    if (!move_uploaded_file($_FILES["attachment"]["tmp_name"], $targetDir . $fileName)) {
                        echo json_encode(['status' => 'error', 'message' => 'Gagal mengunggah file bukti.']); exit;
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Format file tidak didukung.']); exit;
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Wajib menyertakan file bukti.']); exit;
            }

            $data = [
                'id_profil'  => $pId,
                'type'       => $type,
                'reason'     => $reason,
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'file_bukti' => $fileName
            ];

            if ($this->model('AttendanceModel')->createLeaveRequest($data)) {
                echo json_encode(['status' => 'success', 'title' => 'Berhasil', 'message' => 'Izin berhasil diajukan.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan database.']);
            }
            exit; 
        }
    }
}
?>