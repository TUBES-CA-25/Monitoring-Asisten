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

        $allUsers = $this->model('UserModel')->getAllUsers();
        $assistants = array_filter($allUsers, fn($u) => $u['role'] == 'User');

        foreach ($assistants as &$asisten) {
            $asisten['status_today'] = $attModel->getStatusColor($asisten['id']); 
            
            $pId = $asisten['id_profil'];
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
        $data['assistants'] = array_filter($this->model('UserModel')->getAllUsers(), fn($u) => $u['role'] == 'User');
        $data['raw_schedules'] = $this->model('ScheduleModel')->getAllSchedules(); 

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/schedule', $data);
        $this->view('layout/footer');
    }

    public function addSchedule() {
        if ($_SESSION['role'] != 'Admin') exit;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'user_id'  => $_POST['user_id'] ?? 0, 
                'title'    => $_POST['title'],
                'date'     => $_POST['date'],
                'start'    => $_POST['start_time'] ?? '08:00:00',
                'end'      => $_POST['end_time'] ?? '17:00:00',
                'location' => $_POST['location'] ?? 'Lab',
                'type'     => $_POST['type'],
                'is_repeat'=> isset($_POST['is_repeat']) ? 1 : 0
            ];

            if ($this->model('ScheduleModel')->addAdminSchedule($data)) {
                $_SESSION['flash'] = ['type' => 'success', 'title' => 'Sukses', 'message' => 'Jadwal berhasil ditambahkan.'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Gagal menambahkan jadwal.'];
            }
            header("Location: " . BASE_URL . "/admin/schedule");
            exit;
        }
    }

    public function editSchedule() {
        if ($_SESSION['role'] != 'Admin') exit;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'id'       => $_POST['id_schedule'],
                'type'     => $_POST['type'], 
                'title'    => $_POST['title'],
                'date'     => $_POST['date'],
                'start'    => $_POST['start_time'] ?? '08:00:00',
                'end'      => $_POST['end_time'] ?? '17:00:00',
                'location' => $_POST['location'] ?? 'Lab',
                'is_repeat'=> isset($_POST['is_repeat']) ? 1 : 0
            ];

            if ($this->model('ScheduleModel')->updateAdminSchedule($data)) {
                $_SESSION['flash'] = ['type' => 'success', 'title' => 'Sukses', 'message' => 'Jadwal berhasil diperbarui.'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Gagal memperbarui jadwal.'];
            }
            header("Location: " . BASE_URL . "/admin/schedule");
            exit;
        }
    }

    public function deleteSchedule() {
        if ($_SESSION['role'] != 'Admin') exit;

        if (isset($_GET['id']) && isset($_GET['type'])) {
            $id = $_GET['id'];
            $type = $_GET['type'];
            
            if ($this->model('ScheduleModel')->deleteAdminSchedule($id, $type)) {
                $_SESSION['flash'] = ['type' => 'success', 'title' => 'Terhapus', 'message' => 'Jadwal berhasil dihapus.'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Gagal menghapus jadwal.'];
            }
            header("Location: " . BASE_URL . "/admin/schedule");
            exit;
        }
    }

    public function logbook() {
        if ($_SESSION['role'] != 'Admin') exit;
        $data['judul'] = 'Monitoring Logbook';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $allUsers = $this->model('UserModel')->getAllUsers();
        $data['assistants'] = array_filter($allUsers, fn($u) => $u['role'] == 'User');

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/logbook', $data);
        $this->view('layout/footer');
    }

    public function getLogsByUser() {
        if ($_SESSION['role'] != 'Admin') exit;
        $userId = $_POST['user_id'] ?? 0;
        $logs = $this->model('LogbookModel')->getLogsByUserIdForAdmin($userId);
        echo json_encode($logs);
    }

    public function saveLogbookAdmin() {
        if ($_SESSION['role'] != 'Admin') exit;
        
        $data = [
            'user_id'   => $_POST['user_id'],
            'date'      => $_POST['date'],
            'time_in'   => $_POST['time_in'],
            'time_out'  => $_POST['time_out'],
            'activity'  => $_POST['activity']
        ];

        if ($this->model('LogbookModel')->saveLogAdmin($data)) {
            echo json_encode(['status' => 'success', 'message' => 'Data tersimpan.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data.']);
        }
    }

    public function deleteLogbook() {
        if ($_SESSION['role'] != 'Admin') exit;
        $id = $_POST['id'];
        if ($this->model('LogbookModel')->deleteLogAdmin($id)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error']);
        }
    }

    public function profile() {
        if ($_SESSION['role'] != 'Admin') exit;

        $data['judul'] = 'Profil Admin';
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

        $userModel = $this->model('UserModel');
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
        if ($_SESSION['role'] != 'Admin') exit;
        
        $data['judul'] = 'Edit Profil Admin';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('common/edit_profile', $data);
        $this->view('layout/footer');
    }

    public function updateProfile() {
        if ($_SESSION['role'] != 'Admin') exit;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $userModel = $this->model('UserModel');
            $currentUser = $userModel->getUserById($_SESSION['user_id']);

            if (empty($_POST['name']) || empty($_POST['phone']) || empty($_POST['address'])) {
                echo json_encode(['status' => 'error', 'message' => 'Data wajib diisi.']);
                exit;
            }

            $photoName = $currentUser['photo_profile'];
            $targetDir = "../public/uploads/profile/";

            // Logika Upload Foto (Base64 Cropped)
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
            } 
            // Logika Upload Foto (Normal File)
            elseif (isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != "") {
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
                'role'     => 'Admin',
                'name'     => $_POST['name'],
                'nim'      => $_POST['nim'] ?? null,
                'position' => $_POST['position'] ?? 'Administrator',
                'phone'    => $_POST['phone'],
                'address'  => $_POST['address'],
                'gender'   => $_POST['gender'],
                'interest' => null,
                'photo'    => ($photoName != $currentUser['photo_profile']) ? $photoName : null
            ];

            if ($userModel->updateSelfProfile($data)) {
                $_SESSION['name'] = $_POST['name'];
                $_SESSION['jabatan'] = $_POST['position'];
                
                // [UBAH] Return JSON sukses
                echo json_encode(['status' => 'success', 'message' => 'Profil berhasil diperbarui.']);
            } else {
                // [UBAH] Return JSON error
                echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui profil.']);
            }
            exit;
        }
    }
}
?>