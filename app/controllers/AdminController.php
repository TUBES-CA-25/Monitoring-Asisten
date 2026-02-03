<?php
class AdminController extends Controller {

    public function index() { $this->dashboard(); }

    public function dashboard() {
        $this->checkAccess(['Admin']);

        $data['asisten'] = $this->model('UserModel')->getAssistants();

        $data['judul'] = 'Dashboard Admin';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $attModel = $this->model('AttendanceModel');
        $userModel = $this->model('UserModel'); 
        $todayStats = $attModel->getTodayStats();

        $data['stats'] = [
            'hadir_today'   => $todayStats['hadir'],
            'izin_today'    => $todayStats['izin'],
            'alpa_today'    => $todayStats['alpa'],
            'total_asisten'=> $userModel->getTotalAssistants(),
            'total_late'   => $attModel->getTotalLateToday()
        ];

        $assistants = $userModel->getAssistantsWithProfile();

        foreach ($assistants as &$ast) {
            $pid = $ast['id_profil'];

            $presensi = $attModel->getTodayPresenceByProfile($pid);
            $izin     = $attModel->getActiveLeaveByProfile($pid);

            if ($presensi) {
                $ast['visual_status'] = ($presensi['waktu_pulang'] != null)
                    ? 'offline_pulang'
                    : 'online';
            } elseif ($izin) {
                $ast['visual_status'] = 'izin';
            } else {
                $ast['visual_status'] = 'alpha';
            }

            $ast['total_hadir'] = $attModel->getTotalHadir($pid);
            $ast['total_izin'] = $attModel->getTotalIzin($pid);
            $ast['total_alpa'] = $userModel->calculateRealAlpha(
                $pid,
                $ast['created_at'],
                $ast['is_completed']
            );
        }

        $data['assistants'] = $assistants;
        $data['chart_data'] = [
            'daily'   => $attModel->getDailyStats(),
            'weekly'  => $attModel->getWeeklyStats(),
            'monthly' => $attModel->getMonthlyStats()
        ];

        $qrModel = $this->model('QrModel');
        $data['qr_in'] = json_encode([
            'type' => 'CHECK_IN',
            'token' => $qrModel->getOrGenerateToken('check_in')
        ]);

        $data['qr_out'] = json_encode([
            'type' => 'CHECK_OUT',
            'token' => $qrModel->getOrGenerateToken('check_out')
        ]);

        $data['css'] = 'admin/dashboard.css';

        $data['vendor_js'] = [
            'https://cdn.jsdelivr.net/npm/chart.js',
            'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js'
        ];

        $data['js'] = 'admin/dashboard.js';


        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/dashboard', $data);
        $this->view('layout/footer');
    }

    public function manageUsers() {
        $this->checkAccess(['Admin']);
        
        $data['judul'] = 'Manajemen Pengguna';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);

        $userModel = $this->model('UserModel');
        $labModel  = $this->model('LabModel');

        $keyword = isset($_GET['search']) ? $_GET['search'] : null;

        $itemsPerPage = 10; 
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($currentPage < 1) $currentPage = 1;

        $totalData  = $userModel->countUsersWithProfileAndLab($keyword);
        $totalPages = ceil($totalData / $itemsPerPage);
        
        if ($currentPage > $totalPages && $totalPages > 0) $currentPage = $totalPages;

        $offset = ($currentPage - 1) * $itemsPerPage;

        $data['users_list'] = $userModel->getUsersWithProfileAndLab(
            $keyword,
            $itemsPerPage,
            $offset
        ); 
        $data['search_keyword'] = $keyword;

        $data['pagination'] = [
            'current'      => $currentPage,
            'total_pages' => $totalPages,
            'total_items' => $totalData,
            'per_page'    => $itemsPerPage
        ];
        
        $data['labs'] = $labModel->getAllLabs();

        $data['page_css'] = [
            BASE_URL . '/public/css/admin/users.css'
        ];

        $data['page_js'] = [
            BASE_URL . '/public/js/admin/users.js'
        ];

        $data['js_config'] = [
            'BASE_URL' => BASE_URL
        ];

        
        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/users', $data); 
        $this->view('layout/footer');
    }

    public function addUser() {
        $this->checkAccess(['Admin']);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            ob_clean(); 
            header('Content-Type: application/json');

            if (empty($_POST['position'])) {
                echo json_encode(['status' => 'error', 'title' => 'Data Belum Lengkap', 'message' => 'Jabatan wajib dipilih agar data valid.']);
                exit;
            }

            $photoName = 'default.jpg'; 

            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $targetDir = $_SERVER['DOCUMENT_ROOT'] . "/ICLABS/public/uploads/profile/";
                
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                
                $fileExtension = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (in_array($fileExtension, $allowedExtensions)) {
                    if ($_FILES["photo"]["size"] <= 2048000) {
                        $newFileName = time() . '_' . uniqid() . '.' . $fileExtension;
                        $targetFilePath = $targetDir . $newFileName;

                        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFilePath)) {
                            $photoName = $newFileName;
                        } else {
                            echo json_encode(['status' => 'error', 'title' => 'Gagal Simpan', 'message' => 'Gagal memindahkan file. Cek izin folder profile di Windows/Mac Anda.']);
                            exit;
                        }
                    } else {
                        echo json_encode(['status' => 'error', 'title' => 'File Terlalu Besar', 'message' => 'Ukuran foto maksimal adalah 2MB.']);
                        exit;
                    }
                }
            }

            $role = $_POST['role'];
            $isUser = ($role == 'User');
            $isCompleted = (!empty($_POST['name']) && !empty($_POST['phone']) && !empty($_POST['address'])) ? 1 : 0;

            $data = [
                'email'    => $_POST['email'],
                'password' => $_POST['password'], 
                'role'     => $role,
                'name'     => $_POST['name'],
                'nim'      => ($isUser && !empty($_POST['nim'])) ? $_POST['nim'] : '',
                'class'    => ($isUser && !empty($_POST['class'])) ? $_POST['class'] : '',
                'prodi'    => ($isUser && !empty($_POST['prodi'])) ? $_POST['prodi'] : '',
                'lab_id'   => ($isUser && !empty($_POST['lab_id'])) ? $_POST['lab_id'] : 0,
                'interest' => ($isUser && !empty($_POST['interest'])) ? $_POST['interest'] : '',
                'position' => $_POST['position'], 
                'no_telp'  => !empty($_POST['phone']) ? $_POST['phone'] : '',
                'alamat'   => !empty($_POST['address']) ? $_POST['address'] : '',
                'gender'   => !empty($_POST['gender']) ? $_POST['gender'] : '',
                'photo'    => $photoName,
                'is_completed' => $isCompleted
            ];

            if ($this->model('UserModel')->createUser($data)) {
                echo json_encode(['status' => 'success', 'title' => 'Berhasil', 'message' => 'User baru berhasil ditambahkan.']);
            } else {
                echo json_encode(['status' => 'error', 'title' => 'Gagal', 'message' => 'Gagal menambah user. Email mungkin sudah ada.']);
            }
            exit;
        }
    }

    public function editUser() {
        $this->checkAccess(['Admin']);
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            ob_clean();
            header('Content-Type: application/json');

            if (empty($_POST['position'])) {
                echo json_encode(['status' => 'error', 'title' => 'Gagal', 'message' => 'Jabatan tidak boleh dikosongkan.']);
                exit;
            }

            $oldUser = $this->model('UserModel')->getUserById($_POST['id_user']);
            $photoName = $oldUser['photo_profile'];

            if (isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != "") {
                $targetDir = $_SERVER['DOCUMENT_ROOT'] . "/ICLABS/public/uploads/profile/";

                $fileName = time() . '_' . basename($_FILES["photo"]["name"]);
                $targetFilePath = $targetDir . $fileName;
                $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($fileType), ['jpg', 'jpeg', 'png', 'webp'])) {
                    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFilePath)) {
                        $photoName = $fileName;
                        if ($oldUser['photo_profile'] && $oldUser['photo_profile'] != 'default.jpg' && file_exists($targetDir . $oldUser['photo_profile'])) {
                            unlink($targetDir . $oldUser['photo_profile']);
                        }
                    }
                }
            }

            $role = $_POST['role'];
            $isUser = ($role == 'User');
            $isCompleted = (!empty($_POST['name']) && !empty($_POST['phone']) && !empty($_POST['address'])) ? 1 : 0;

            $data = [
                'id'       => $_POST['id_user'],
                'name'     => $_POST['name'],
                'email'    => $_POST['email'],
                'role'     => $role,
                'password' => !empty($_POST['password']) ? $_POST['password'] : null,
                
                'nim'      => ($isUser && !empty($_POST['nim'])) ? $_POST['nim'] : null,
                'class'    => ($isUser && !empty($_POST['class'])) ? $_POST['class'] : null,
                'prodi'    => ($isUser && !empty($_POST['prodi'])) ? $_POST['prodi'] : null,
                'lab_id'   => ($isUser && !empty($_POST['lab_id'])) ? $_POST['lab_id'] : null,
                'interest' => ($isUser && !empty($_POST['interest'])) ? $_POST['interest'] : null,
                
                'position' => $_POST['position'],
                
                'no_telp'  => !empty($_POST['phone']) ? $_POST['phone'] : null,
                'alamat'   => !empty($_POST['address']) ? $_POST['address'] : null,
                'gender'   => !empty($_POST['gender']) ? $_POST['gender'] : null,
                'photo' => $photoName,
                'is_completed' => $isCompleted
            ];

            $updateResult = $this->model('UserModel')->updateUser($data);

            if ($updateResult) {
                echo json_encode(['status' => 'success', 'title' => 'Berhasil', 'message' => 'Data pengguna berhasil disimpan.']);
            } else {
                echo json_encode(['status' => 'error', 'title' => 'Gagal', 'message' => 'Terjadi kesalahan sistem saat menyimpan.']);
            }
            exit;
        }
    }

    public function deleteUser() {
        $this->checkAccess(['Admin']);
        
        if (isset($_GET['id'])) {
            ob_clean();
            header('Content-Type: application/json');
            
            if ($this->model('UserModel')->deleteUser($_GET['id'])) {
                echo json_encode(['status' => 'success', 'title' => 'Terhapus', 'message' => 'Pengguna berhasil dihapus.']);
            } else {
                echo json_encode(['status' => 'error', 'title' => 'Gagal', 'message' => 'Gagal menghapus pengguna.']);
            }
            exit;
        }
    }

    public function monitorAttendance() {
        $this->checkAccess(['Admin']);        
        
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

        $fullData = $attModel->getAttendanceRecap($startDate, $endDate, $assistantId);

        $itemsPerPage = 10;
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($currentPage < 1) $currentPage = 1;

        $totalData = count($fullData);
        $totalPages = ceil($totalData / $itemsPerPage);

        if ($currentPage > $totalPages && $totalPages > 0) $currentPage = $totalPages;

        $offset = ($currentPage - 1) * $itemsPerPage;
        $slicedData = array_slice($fullData, $offset, $itemsPerPage);

        $data['attendance_list'] = $slicedData;

        $data['pagination'] = [
            'current' => $currentPage,
            'total_pages' => $totalPages,
            'total_items' => $totalData,
            'per_page' => $itemsPerPage
        ];

        $data['css'] = 'admin/attendance.css';
        $data['js']  = 'admin/attendance.js';

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/attendance', $data);
        $this->view('layout/footer');
    }

    public function exportCsv() {
        $this->checkAccess(['Admin']);
        
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        $assistantId = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : null;

        $data = $this->model('AttendanceModel')->getAttendanceRecap($startDate, $endDate, $assistantId);

        $filename = "Rekap_Presensi_" . date('d-m-Y', strtotime($startDate)) . "_sd_" . date('d-m-Y', strtotime($endDate)) . ".csv";
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['No', 'Tanggal', 'Nama Asisten', 'NIM', 'Jabatan', 'Jam Masuk', 'Jam Pulang', 'Status']);

        $no = 1;
        foreach ($data as $row) {
            fputcsv($output, [
                $no++,
                $row['tanggal'],
                $row['name'],
                $row['nim'] ?? '-',
                $row['position'] ?? 'Anggota',
                $row['waktu_presensi'] ? date('H:i', strtotime($row['waktu_presensi'])) : '-',
                $row['waktu_pulang'] ? date('H:i', strtotime($row['waktu_pulang'])) : '-',
                $row['status']
            ]);
        }
        fclose($output);
        exit;
    }

    public function exportPdf() {
        $this->checkAccess(['Admin']);
        
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
        $data['css'] = 'admin/pdf_attendance.css';

        $this->view('admin/pdf_attendance', $data);
    }

    public function schedule() {
        $this->checkAccess(['Admin']);
        $data['judul'] = 'Kelola Jadwal';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $allUsers = $this->model('UserModel')->getAllUsers();
        $data['assistants'] = array_filter($allUsers, fn($u) => $u['role'] == 'User');
        
        $data['raw_schedules'] = $this->model('ScheduleModel')->getAllSchedules(); 

        $data['css'] = 'admin/schedule.css';
        $data['js']  = 'admin/schedule.js';

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/schedule', $data);
        $this->view('layout/footer');
    }

    public function addSchedule() {
        $this->checkAccess(['Admin']);
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
        $this->checkAccess(['Admin']);
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
        $this->checkAccess(['Admin']);
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
        $this->checkAccess(['Admin']);
        $data['judul'] = 'Monitoring Logbook';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $allUsers = $this->model('UserModel')->getAllUsers();
        $data['assistants'] = array_filter($allUsers, fn($u) => $u['role'] == 'User');

        $data['css'] = 'admin/logbook.css';
        $data['js']  = 'admin/logbook.js';

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/logbook', $data);
        $this->view('layout/footer');
    }
    
    public function getLogsByUser() {
        $this->checkAccess(['Admin']);
        $userId = $_POST['user_id'] ?? 0;
        $logs = $this->model('LogbookModel')->getUnifiedLogbook($userId);
        echo json_encode($logs);
    }
    
    public function reset_logbook() {
        $this->checkAccess(['Admin']);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $idRef = $_POST['id_ref']; 
            $type = $_POST['type'];    
            $mode = $_POST['mode'];    

            if ($this->model('LogbookModel')->resetLogAdmin($idRef, $type, $mode)) {
                echo json_encode(['status' => 'success', 'message' => 'Logbook berhasil direset.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal mereset data.']);
            }
        }
    }

    public function saveLogbookAdmin() {
        $this->checkAccess(['Admin']);
        
        $fileName = null;
        if (isset($_FILES['proof_file']['name']) && $_FILES['proof_file']['name'] != "") {
            $status = $_POST['status'];
            $folder = ($status == 'Hadir') ? 'attendance' : 'leaves';
            $targetDir = "../public/uploads/$folder/";
            
            if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
            
            $ext = pathinfo($_FILES["proof_file"]["name"], PATHINFO_EXTENSION);
            $fileName = "admin_edit_" . time() . "." . $ext;
            
            move_uploaded_file($_FILES["proof_file"]["tmp_name"], $targetDir . $fileName);
        }

        $data = [
            'user_id'  => $_POST['user_id'], 
            'date'     => $_POST['date'], 
            'status'   => $_POST['status'],
            'time_in'  => $_POST['time_in'] ?? null, 
            'time_out' => $_POST['time_out'] ?? null, 
            'activity' => $_POST['activity'],
            'file'     => $fileName 
        ];
        
        if ($this->model('LogbookModel')->saveLogAdmin($data)) {
            echo json_encode(['status'=>'success', 'message'=>'Data berhasil disimpan.']); 
        } else {
            echo json_encode(['status'=>'error', 'message'=>'Gagal menyimpan data.']);
        }
    }
    
    public function deleteLogbook() {
        $this->checkAccess(['Admin']);
        $id = $_POST['id'];
        if ($this->model('LogbookModel')->deleteLogAdmin($id)) echo json_encode(['status'=>'success']); else echo json_encode(['status'=>'error']);
    }
    
    public function profile() {
        $this->checkAccess(['Admin']);        

        $data['judul'] = 'Profil Admin';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);

        $userModel = $this->model('UserModel');
        $attModel  = $this->model('AttendanceModel');
        $schModel  = $this->model('ScheduleModel');

        $data['is_google_connected'] = $userModel->isGoogleConnected($_SESSION['user_id']);
        $data['total_managed_users'] = $userModel->getTotalManagedUsers();

        $data['chart_data'] = $attModel->getChartData();
        $data['demographics'] = $userModel->getDemographics();

        $data['upcoming_schedules'] = $schModel->getUpcomingSchedules(5);
        
        $data['rankings'] = [
            'online'           => $userModel->getAssistantRankings('online'),
            'rajin'            => $userModel->getAssistantRankings('rajin'),
            'jarang'           => $userModel->getAssistantRankings('jarang'),
            'cepat'            => $userModel->getAssistantRankings('cepat'),
            'terlambat'        => $userModel->getAssistantRankings('terlambat'),
            'sering_izin'     => $userModel->getAssistantRankings('sering_izin'),
            'logbook_lengkap' => $userModel->getAssistantRankings('logbook_lengkap'),
            'logbook_singkat' => $userModel->getAssistantRankings('logbook_singkat'),
            'sibuk'            => $userModel->getAssistantRankings('sibuk'),
            'santai'          => $userModel->getAssistantRankings('santai'),
        ];

        $data['page_css'] = [
            BASE_URL . '/public/css/common/profile.css'
        ];

        $data['page_js'] = [
            'https://cdn.jsdelivr.net/npm/chart.js',
            BASE_URL . '/public/js/common/profile.js'
        ];

        $data['js_config'] = [
            'BASE_URL' => BASE_URL,
            'RAW_DEMOGRAPHICS' => $data['demographics'] ?? [],
            'RANKINGS' => $data['rankings'] ?? [],
            'USER_STATS' => $data['chart_data'] ?? null,
            'IS_USER_ROLE' => ($data['user']['role'] === 'User')
        ];

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('common/profile', $data); 
        $this->view('layout/footer');
    }
    
    public function editProfile() {
        $this->checkAccess(['Admin']);
        $data['judul'] = 'Edit Profil Admin';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);

        $data['page_css'] = [
            'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css',
            BASE_URL . '/public/css/common/edit-profile.css'
        ];

        $data['page_js'] = [
            'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js',
            BASE_URL . '/public/js/common/edit-profile.js'
        ];

        $data['js_config'] = [
            'BASE_URL' => BASE_URL,
            'USER_ROLE' => $data['user']['role']
        ];

        $this->view('layout/header', $data); $this->view('layout/sidebar', $data); 
        $this->view('common/edit_profile', $data); 
        $this->view('layout/footer');
    }
    
    public function updateProfile() {
        $this->checkAccess(['Admin']);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $userModel = $this->model('UserModel');
            $currentUser = $userModel->getUserById($_SESSION['user_id']);

            $photoName = $currentUser['photo_profile'];
                $targetDir = $_SERVER['DOCUMENT_ROOT'] . "/ICLABS/public/uploads/profile/";

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
            } 
            elseif (isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != "") {
                if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                $fileName = time() . '_' . basename($_FILES["photo"]["name"]);
                if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetDir . $fileName)) {
                    $photoName = $fileName;
                    $_SESSION['photo'] = $fileName;
                }
            }

            $isCompleted = 0;
            if (!empty($_POST['name']) && !empty($_POST['phone']) && !empty($_POST['address'])) {
                $isCompleted = 1;
            }

            $data = [
                'id'       => $_SESSION['user_id'],
                'role'     => 'Admin',
                'name'     => $_POST['name'],
                'nim'      => $_POST['nim'] ?? null,
                'position' => $_POST['position'] ?? 'Administrator',
                'prodi'    => null,
                'phone'    => $_POST['phone'],
                'address'  => $_POST['address'],
                'gender'   => $_POST['gender'],
                'interest' => null,
                'photo' => $photoName,
                'is_completed' => $isCompleted 
            ];

            if ($userModel->updateSelfProfile($data)) {
                $_SESSION['name'] = $_POST['name'];
                $_SESSION['jabatan'] = $_POST['position'];
                
                echo json_encode([
                    'status'   => 'success', 
                    'title'    => 'Berhasil', 
                    'message'  => 'Profil Admin berhasil diperbarui.' . ($isCompleted ? ' Status Akun: Terverifikasi.' : ''),
                    'redirect' => BASE_URL . '/admin/profile'
                ]);
            } else {
                echo json_encode(['status' => 'error', 'title' => 'Gagal', 'message' => 'Gagal memperbarui profil.']);
            }
            exit;
        }
    }

    public function getQrAjax() {
        $this->checkAccess(['Admin']);    

        $type = $_POST['type'] ?? 'check_in'; 
        $token = $this->model('QrModel')->getOrGenerateToken($type);
        
        $qrString = json_encode([
            'type' => ($type == 'check_in') ? 'CHECK_IN' : 'CHECK_OUT', 
            'token' => $token
        ]);
        
        echo json_encode(['status' => 'success', 'qr_data' => $qrString]);
    }

    public function assistantSchedule($id) {
        $this->checkAccess(['Admin']);

        $assistant = $this->model('UserModel')->getUserById($id);
        if (!$assistant || $assistant['role'] != 'User') {
            header("Location: " . BASE_URL . "/admin/dashboard");
            exit;
        }

        $data['judul'] = 'Jadwal Asisten';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']); 
        $data['assistant'] = $assistant; 
        
        $data['schedules'] = $this->model('ScheduleModel')->getAllUserSchedules($id);

        $data['css'] = 'admin/assistant_schedule.css';

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/assistant_schedule', $data); 
        $this->view('layout/footer');
    }

    protected function checkAccess($allowedRoles = ['Admin']) {
        if (!isset($_SESSION['role'])) {
            header("Location: " . BASE_URL . "/auth/login");
            exit;
        }

        if (!in_array($_SESSION['role'], $allowedRoles)) {
            if (file_exists('../app/controllers/ErrorController.php')) {
                require_once '../app/controllers/ErrorController.php';
                $error = new ErrorController();
                $error->unauthorized();
            } else {
                header("Location: " . BASE_URL . "/auth/login");
            }
            exit;
        }
    }
}
?>