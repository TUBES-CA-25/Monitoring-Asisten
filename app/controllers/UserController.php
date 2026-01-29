<?php
class UserController extends Controller {
    
    public function index() { $this->dashboard(); }

    public function dashboard() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'User') {
            header("Location: " . BASE_URL . "/auth/login"); exit;
        }

        $data['judul'] = 'Dashboard Asisten';
        $userModel = $this->model('UserModel');
        $user = $userModel->getUserById($_SESSION['user_id']);
        $data['user'] = $user;
        
        $schModel = $this->model('ScheduleModel');
        $uid = $_SESSION['user_id'];
        $pId = $_SESSION['profil_id']; 
        
        $db = new Database(); 
        $conn = $db->getConnection();

        $stmtH = $conn->prepare("SELECT COUNT(*) as total FROM presensi WHERE id_profil = :pid AND status = 'Hadir'");
        $stmtH->execute([':pid' => $pId]);
        $hadir = $stmtH->fetch()['total'];

        $stmtI = $conn->prepare("SELECT COUNT(*) as total FROM izin WHERE id_profil = :pid AND status_approval = 'Approved'");
        $stmtI->execute([':pid' => $pId]);
        $izin = $stmtI->fetch()['total'];

        $alpa = $userModel->calculateRealAlpha($pId, $user['created_at'], $user['is_completed']);
        
        $data['stats'] = ['hadir' => $hadir, 'izin' => $izin, 'alpa' => $alpa];
        
        $today = date('Y-m-d');
        
        $stmtTod = $conn->prepare("SELECT waktu_presensi, waktu_pulang FROM presensi WHERE id_profil = :pid AND tanggal = :d");
        $stmtTod->execute([':pid' => $pId, ':d' => $today]);
        $presensiToday = $stmtTod->fetch(PDO::FETCH_ASSOC);

        $stmtIz = $conn->prepare("SELECT id_izin FROM izin WHERE id_profil = :pid AND :d BETWEEN start_date AND end_date AND status_approval = 'Approved'");
        $stmtIz->execute([':pid' => $pId, ':d' => $today]);
        $izinToday = $stmtIz->fetch(PDO::FETCH_ASSOC);

        $data['status_today'] = 'red';
        $data['is_working'] = false;

        if ($presensiToday && !empty($presensiToday['waktu_presensi'])) {
            $data['status_today'] = 'green';
            if (empty($presensiToday['waktu_pulang'])) {
                $data['is_working'] = true;
            }
        } elseif ($izinToday) {
            $data['status_today'] = 'yellow';
        } else {
            $data['status_today'] = 'red';
        }

        $data['weekly_schedule'] = $schModel->getUserScheduleForWeek($uid); 

        $chartData = [];

        $dLabels = []; $dData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dLabels[] = date('D', strtotime($date));
            $stmt = $conn->prepare("SELECT count(*) as c FROM presensi WHERE id_profil=:pid AND tanggal=:d AND status='Hadir'");
            $stmt->execute([':pid'=>$pId, ':d'=>$date]);
            $dData[] = $stmt->fetch()['c'] > 0 ? 1 : 0;
        }
        $chartData['daily'] = ['labels' => $dLabels, 'data' => $dData];

        $wLabels = []; $wData = [];
        for ($i = 3; $i >= 0; $i--) {
            $wStart = date('Y-m-d', strtotime("-$i weeks Monday this week"));
            $wEnd   = date('Y-m-d', strtotime("-$i weeks Sunday this week"));
            $wLabels[] = "Minggu " . date('W', strtotime($wStart));
            
            $stmt = $conn->prepare("SELECT count(*) FROM presensi WHERE id_profil=:pid AND tanggal BETWEEN :s AND :e AND status='Hadir'");
            $stmt->execute([':pid'=>$pId, ':s'=>$wStart, ':e'=>$wEnd]);
            $wData[] = $stmt->fetchColumn();
        }
        $chartData['weekly'] = ['labels' => $wLabels, 'data' => $wData];

        $mLabels = []; $mData = [];
        for ($i = 5; $i >= 0; $i--) {
            $mStart = date('Y-m-01', strtotime("-$i months"));
            $mEnd   = date('Y-m-t', strtotime("-$i months"));
            $mLabels[] = date('M', strtotime($mStart));
            
            $stmt = $conn->prepare("SELECT count(*) FROM presensi WHERE id_profil=:pid AND tanggal BETWEEN :s AND :e AND status='Hadir'");
            $stmt->execute([':pid'=>$pId, ':s'=>$mStart, ':e'=>$mEnd]);
            $mData[] = $stmt->fetchColumn();
        }
        $chartData['monthly'] = ['labels' => $mLabels, 'data' => $mData];

        $data['chart_data'] = $chartData;

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data); 
        $this->view('user/dashboard', $data);
        $this->view('layout/footer');
    }

    public function profile() {
        if ($_SESSION['role'] != 'User') exit;

        $data['judul'] = 'Profil Saya';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $pId = $_SESSION['profil_id'];
        $db = new Database(); $conn = $db->getConnection();

        $stmtG = $conn->prepare("SELECT id_token FROM user_google_token WHERE id_user = :uid");
        $stmtG->execute([':uid' => $_SESSION['user_id']]);
        $data['is_google_connected'] = $stmtG->rowCount() > 0;
        
        $stmtH = $conn->prepare("SELECT COUNT(*) as total FROM presensi WHERE id_profil = :pid AND status = 'Hadir'");
        $stmtH->execute([':pid'=>$pId]);
        $hadir = $stmtH->fetch()['total'];

        $stmtI = $conn->prepare("SELECT COUNT(*) as total FROM izin WHERE id_profil = :pid AND status_approval = 'Approved'");
        $stmtI->execute([':pid'=>$pId]);
        $izin = $stmtI->fetch()['total'];
        
        $data['stats'] = ['hadir' => $hadir, 'izin' => $izin, 'alpa' => 0];

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('common/profile', $data);
        $this->view('layout/footer');
    }

    public function editProfile() {
        $role = $_SESSION['role'];
        if ($role != 'User' && $role != 'Super Admin') exit;

        $user = $this->model('UserModel')->getUserById($_SESSION['user_id']);

        if ($user['is_completed'] == 1) {
            echo "<script>
                alert('Profil Anda sudah dikunci. Hubungi Admin untuk perubahan data.');
                window.location.href='" . BASE_URL . "/" . strtolower(str_replace(' ', '', $role)) . "/profile';
            </script>";
            exit;
        }

        $data['judul'] = 'Edit Profil';
        $data['user'] = $user;

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('common/edit_profile', $data); 
        $this->view('layout/footer');
    }

    public function updateProfile() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $role = $_SESSION['role'];
            $userModel = $this->model('UserModel');
            
            $currentUser = $userModel->getUserById($_SESSION['user_id']);

            if ($role != 'Admin' && isset($currentUser['is_completed']) && $currentUser['is_completed'] == 1) {
                echo "<script>alert('Profil terkunci.'); window.location.href='" . BASE_URL . "/user/profile';</script>";
                exit;
            }

            if (empty($_POST['name']) || empty($_POST['nim']) || empty($_POST['position']) || empty($_POST['phone']) || empty($_POST['address'])) {
                echo "<script>alert('Semua data bertanda (*) wajib diisi!'); window.history.back();</script>";
                exit;
            }

            if ($role == 'User' && empty($_POST['class'])) {
                echo "<script>alert('Data Kelas wajib diisi untuk Asisten!'); window.history.back();</script>";
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
                        if (file_put_contents($targetDir . $fileName, $decodedData)) {
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
                if (in_array(strtolower($fileType), ['jpg', 'jpeg', 'png'])) {
                    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFilePath)) {
                        $photoName = $fileName;
                        $_SESSION['photo'] = $fileName;
                    }
                }
            }

            $data = [
                'id'       => $_SESSION['user_id'],
                'role'     => 'User',
                'name'     => $_POST['name'],
                'nim'      => $_POST['nim'],
                'position' => $_POST['position'], 
                'class'    => $_POST['class'] ?? null, 
                'prodi'    => $_POST['prodi'] ?? null,
                'lab_id'   => $_POST['lab_id'] ?? null, 
                'phone'    => $_POST['phone'],
                'address'  => $_POST['address'],
                'gender'   => $_POST['gender'],
                'interest' => $_POST['interest'] ?? null,
                'photo'    => ($photoName != $currentUser['photo_profile']) ? $photoName : null
            ];

            if ($userModel->updateSelfProfile($data)) {
                $_SESSION['name'] = $_POST['name'];
                $_SESSION['jabatan'] = $_POST['position'];

                echo json_encode([
                    'status' => 'success',
                    'title'  => 'Profil Terkunci',
                    'message'=> 'Profil berhasil dilengkapi. Data Anda kini DIKUNCI dan tidak dapat diubah kembali.',
                    'redirect' => BASE_URL . '/user/profile'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'title'  => 'Gagal Update',
                    'message'=> 'Terjadi kesalahan saat menyimpan data ke database.'
                ]);
            }
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
        
        $db = new Database(); $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT waktu_presensi, waktu_pulang FROM presensi WHERE id_profil = :pid AND tanggal = :d");
        $stmt->execute([':pid'=>$pId, ':d'=>$today]);
        $att = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$att || !$att['waktu_presensi']) {
            echo json_encode(['status'=>'error', 'message'=>'Anda belum melakukan scan masuk!']); exit;
        }
        if ($att['waktu_pulang']) {
            echo json_encode(['status'=>'error', 'message'=>'Logbook terkunci karena Anda sudah scan pulang.']); exit;
        }

        $payload = [
            'user_id'  => $_SESSION['user_id'],
            'date'     => $today,
            'time'     => $_POST['time'],
            'activity' => $_POST['activity']
        ];

        if ($this->model('LogbookModel')->saveLogbook($payload)) {
            echo json_encode(['status'=>'success']);
        } else {
            echo json_encode(['status'=>'error', 'message'=>'Gagal menyimpan data database.']);
        }
    }

    public function reset_logbook() {
    if ($_SESSION['role'] != 'User') { 
        echo json_encode(['status'=>'error', 'message'=>'Unauthorized']); exit; 
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $logId = $_POST['log_id'];
        $userId = $_SESSION['user_id'];

        if ($this->model('LogbookModel')->resetLogUser($logId, $userId)) {
            echo json_encode(['status' => 'success', 'message' => 'Isi logbook berhasil dikosongkan.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mereset logbook.']);
        }
    }
}

    public function schedule() {
        if ($_SESSION['role'] != 'User') exit;
        
        $data['judul'] = 'Jadwal Saya & Lab';
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
                'type' => 'kuliah', 'user_id' => $_SESSION['profil_id'],
                'title' => $_POST['title'], 'location' => $_POST['location'],
                'dosen' => $_POST['dosen'], 'kelas' => $_POST['kelas'], 
                'date' => $_POST['date'], 'start_time' => $_POST['start_time'], 'end_time' => $_POST['end_time'],
                'model_perulangan' => $_POST['model_perulangan'] ?? 'sekali', 'end_date_repeat' => $_POST['end_date_repeat'] ?? null
            ];
            
            if ($this->model('ScheduleModel')->createSchedule($data)) {
                $_SESSION['flash'] = ['type' => 'success', 'title' => 'Sukses', 'message' => 'Jadwal kuliah dibuat.'];
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
                'id' => $_POST['id_schedule'], 'type' => 'kuliah', 'user_id' => $_SESSION['profil_id'],
                'title' => $_POST['title'], 'location' => $_POST['location'],
                'dosen' => $_POST['dosen'], 'kelas' => $_POST['kelas'], 
                'date' => $_POST['date'], 'start_time' => $_POST['start_time'], 'end_time' => $_POST['end_time'],
                'model_perulangan' => $_POST['model_perulangan'] ?? 'sekali', 'end_date_repeat' => $_POST['end_date_repeat'] ?? null
            ];
            
            if ($this->model('ScheduleModel')->updateSchedule($data)) {
                $_SESSION['flash'] = ['type' => 'success', 'title' => 'Sukses', 'message' => 'Jadwal diperbarui.'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Gagal update.'];
            }
            header("Location: " . BASE_URL . "/user/schedule"); exit;
        }
    }

    public function deleteSchedule() {
        if ($_SESSION['role'] != 'User') exit;
        if (isset($_GET['id']) && isset($_GET['type'])) {
            if ($_GET['type'] !== 'kuliah') {
                $_SESSION['flash'] = ['type' => 'error', 'title' => 'Ditolak', 'message' => 'Hanya jadwal kuliah yang bisa dihapus.'];
                header("Location: " . BASE_URL . "/user/schedule"); exit;
            }

            if ($this->model('ScheduleModel')->deleteSchedule($_GET['id'], 'kuliah')) {
                $_SESSION['flash'] = ['type' => 'success', 'title' => 'Terhapus', 'message' => 'Jadwal dihapus.'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Gagal menghapus.'];
            }
            header("Location: " . BASE_URL . "/user/schedule"); exit;
        }
    }

    public function scan() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'User') { header("Location: " . BASE_URL . "/auth/login"); exit; }
        $data['judul'] = 'Scan Presensi';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $this->view('user/scan', $data); 
    }

    public function check_qr_type() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
            $userId = $_SESSION['user_id'];
            $rawToken = $_POST['token'];
            $imageData = $_POST['image']; 

            $token = trim($rawToken);
            $decoded = json_decode($rawToken, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['token'])) {
                $token = $decoded['token'];
            }

            $tokenInfo = $this->model('QrModel')->getTokenData($token);
            if (!$tokenInfo) {
                echo json_encode(['status' => 'error', 'message' => 'QR Code tidak valid/expired!']); exit;
            }
            $qrType = $tokenInfo['tipe']; 
            $attModel = $this->model('AttendanceModel');
            $userStatus = $attModel->getCurrentStatus($userId);
            $processType = ''; 

            if ($qrType == 'Presensi') {
                if ($userStatus == 'checked_in') {
                    echo json_encode(['status' => 'error', 'message' => 'Anda sedang jam kerja. Harap scan QR Pulang dulu sebelum presensi baru.']);
                    exit;
                }
                elseif ($userStatus == 'checked_out') {
                    $processType = 're_in';
                }
                else {
                    $processType = 'in';
                }
            } 
            elseif ($qrType == 'Pulang') {
                if ($userStatus == 'not_present') {
                    echo json_encode(['status' => 'error', 'message' => 'Anda belum presensi MASUK hari ini!']); exit;
                }
                if ($userStatus == 'checked_out') {
                    echo json_encode(['status' => 'error', 'message' => 'Anda sudah presensi PULANG sebelumnya.']); exit;
                }
                $processType = 'out';
            }

            $imageParts = explode(";base64,", $imageData);
            $imageBase64 = base64_decode($imageParts[1]);
            
            $prefix = ($processType == 'out') ? 'out_' : 'in_';
            $fileName = $prefix . $userId . '_' . time() . '.jpg';
            $filePath = '../public/uploads/attendance/' . $fileName;
            
            if (!file_exists('../public/uploads/attendance/')) mkdir('../public/uploads/attendance/', 0777, true);
            file_put_contents($filePath, $imageBase64);

            $success = false;
            $msg = '';

            if ($processType == 're_in') {
                $success = $attModel->reClockIn($userId, $fileName);
                $msg = 'Presensi Diperbarui! Sesi kerja baru dimulai.';
            } 
            elseif ($processType == 'in') {
                $success = $attModel->clockIn($userId, $fileName);
                $msg = 'Selamat Bekerja! Presensi Masuk Berhasil.';
            } 
            else {
                $success = $attModel->clockOut($userId, $fileName);
                $msg = 'Terima Kasih! Presensi Pulang Berhasil.';
            }

            if ($success) {
                echo json_encode(['status' => 'success', 'message' => $msg]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data database.']);
            }
        }
    }

    public function submit_leave() {
        if ($_SESSION['role'] != 'User') {
            echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $userModel = $this->model('UserModel');
            $currentUser = $userModel->getUserById($_SESSION['user_id']);
            $pId = $currentUser['id_profil'];

            $type = $_POST['type'];
            $reason = $_POST['reason'];
            $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];

            if ($endDate < $startDate) {
                echo json_encode(['status' => 'error', 'message' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.']);
                exit;
            }

            $fileName = null;
            if (isset($_FILES['attachment']['name']) && $_FILES['attachment']['name'] != "") {
                $targetDir = "../public/uploads/leaves/"; 
                if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                
                $fileExt = strtolower(pathinfo($_FILES["attachment"]["name"], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
                
                if (in_array($fileExt, $allowed)) {
                    $fileName = strtolower($type) . '_' . $_SESSION['user_id'] . '_' . time() . '.' . $fileExt;
                    if (!move_uploaded_file($_FILES["attachment"]["tmp_name"], $targetDir . $fileName)) {
                        echo json_encode(['status' => 'error', 'message' => 'Gagal mengunggah file bukti.']);
                        exit;
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Format file tidak didukung (Gunakan JPG, PNG, PDF, DOC).']);
                    exit;
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Wajib menyertakan file bukti.']);
                exit;
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
                echo json_encode([
                    'status' => 'success', 
                    'title' => 'Berhasil', 
                    'message' => 'Data Izin/Sakit berhasil dicatat. Status kehadiran otomatis diperbarui.'
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan database.']);
            }
            exit; 
        }
    }
}
?>