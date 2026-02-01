<?php
class AdminController extends Controller {

    public function index() { $this->dashboard(); }

    public function dashboard() {
        // if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Super Admin')) {
        //     header("Location: " . BASE_URL . "/auth/login"); exit;
        // }
        $this->checkAccess(['Admin']);

        $data['asisten'] = $this->model('UserModel')->getAssistants();

        $data['judul'] = 'Dashboard Admin';
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
                                        p.id_profil, p.nama as name, p.photo_profile, p.jabatan, 
                                        p.nim, p.no_telp, p.alamat, p.prodi, p.kelas, p.is_completed 
                                 FROM user u 
                                 JOIN profile p ON u.id_user = p.id_user 
                                 WHERE u.role = 'User' 
                                 ORDER BY p.nama ASC");
        $assistants = $stmtAst->fetchAll(PDO::FETCH_ASSOC);

        foreach ($assistants as &$ast) {
            $pid = $ast['id_profil'];

            // 1. Cek Status Visual (Logic Lama Tetap Ada)
            $stmtP = $conn->prepare("SELECT waktu_presensi, waktu_pulang FROM presensi WHERE id_profil = :pid AND tanggal = CURDATE()");
            $stmtP->execute([':pid' => $pid]);
            $presensi = $stmtP->fetch(PDO::FETCH_ASSOC);

            $stmtI = $conn->prepare("SELECT tipe FROM izin WHERE id_profil = :pid AND status_approval = 'Approved' AND CURDATE() BETWEEN start_date AND end_date");
            $stmtI->execute([':pid' => $pid]);
            $izin = $stmtI->fetch(PDO::FETCH_ASSOC);

            if ($presensi) {
                $ast['visual_status'] = ($presensi['waktu_pulang'] != null) ? 'offline_pulang' : 'online';
            } elseif ($izin) {
                $ast['visual_status'] = 'izin';
            } else {
                $ast['visual_status'] = 'alpha';
            }

            // 2. [BARU] HITUNG STATISTIK INDIVIDU (REAL DATA)
            // Hitung Total Hadir
            $stmtH = $conn->prepare("SELECT COUNT(*) FROM presensi WHERE id_profil = :pid AND status = 'Hadir'");
            $stmtH->execute([':pid' => $pid]);
            $ast['total_hadir'] = $stmtH->fetchColumn();

            // Hitung Total Izin/Sakit
            $stmtIz = $conn->prepare("SELECT COUNT(*) FROM izin WHERE id_profil = :pid AND status_approval = 'Approved'");
            $stmtIz->execute([':pid' => $pid]);
            $ast['total_izin'] = $stmtIz->fetchColumn();

            // Hitung Total Alpa (Berdasarkan data yang tersimpan di DB sebagai 'Alpha')
            $ast['total_alpa'] = $userModel->calculateRealAlpha($pid, $ast['created_at'], $ast['is_completed']);
            // $stmtA = $conn->prepare("SELECT COUNT(*) FROM presensi WHERE id_profil = :pid AND status = 'Alpha'");
            // $stmtA->execute([':pid' => $pid]);
            // $ast['total_alpa'] = $stmtA->fetchColumn();
        }

        $data['assistants'] = $assistants;
        $chartData = [];
        
        // A. Harian (7 Hari Terakhir)
        $dLabels = []; $dData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dLabels[] = date('d M', strtotime($date));
            // Hitung total kehadiran semua asisten pada tanggal tersebut
            $stmt = $conn->query("SELECT COUNT(*) FROM presensi WHERE tanggal = '$date' AND status = 'Hadir'");
            $dData[] = $stmt->fetchColumn();
        }
        $chartData['daily'] = ['labels' => $dLabels, 'data' => $dData];

        // B. Mingguan (4 Minggu Terakhir)
        $wLabels = []; $wData = [];
        for ($i = 3; $i >= 0; $i--) {
            // Hitung start (Senin) dan end (Minggu) untuk minggu ke-$i yg lalu
            $wStart = date('Y-m-d', strtotime("-$i weeks Monday this week"));
            $wEnd   = date('Y-m-d', strtotime("-$i weeks Sunday this week"));
            $wLabels[] = "Minggu " . date('W', strtotime($wStart));
            
            $stmt = $conn->query("SELECT COUNT(*) FROM presensi WHERE tanggal BETWEEN '$wStart' AND '$wEnd' AND status = 'Hadir'");
            $wData[] = $stmt->fetchColumn();
        }
        $chartData['weekly'] = ['labels' => $wLabels, 'data' => $wData];

        // C. Bulanan (6 Bulan Terakhir)
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
        // if ($_SESSION['role'] != 'Admin') exit;
        $this->checkAccess(['Admin']);
        $data['judul'] = 'Manajemen Pengguna';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
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
        
        // Dropdown Lab (Tetap Ada)
        $db->query("SELECT * FROM lab ORDER BY nama_lab ASC");
        $data['labs'] = $db->resultSet();

        $data['users_list'] = $allUsers;
        
        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/users', $data); 
        $this->view('layout/footer');
    }

    // public function addUser() {
    //     // if ($_SESSION['role'] != 'Admin') exit;
    //     $this->checkAccess(['Admin']);

    //     if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    //         ob_clean(); 
    //         header('Content-Type: application/json');

    //         $photoName = 'default.jpg'; // Default foto
            
    //         // Logika Upload Foto
    //         if (isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != "") {
    //             $targetDir = "../public/uploads/profile/";
    //             if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                
    //             $fileName = time() . '_' . basename($_FILES["photo"]["name"]);
    //             $targetFilePath = $targetDir . $fileName;
    //             $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
                
    //             if (in_array(strtolower($fileType), ['jpg', 'jpeg', 'png', 'webp'])) {
    //                 if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFilePath)) {
    //                     $photoName = $fileName;
    //                 }
    //             }
    //         }

    //         $role = $_POST['role'];
    //         $isUser = ($role == 'User');
            
    //         // Logic Completed: Hanya jika field opsional diisi
    //         $isCompleted = (!empty($_POST['name']) && !empty($_POST['phone']) && !empty($_POST['address'])) ? 1 : 0;

    //         // [PERBAIKAN PENTING] 
    //         // Gunakan !empty() agar jika input kosong dikirim sebagai NULL ke database
    //         // Ini mencegah error saat input data wajib saja.
    //         $data = [
    //             'email'    => $_POST['email'],
    //             'password' => $_POST['password'], 
    //             'role'     => $role,
    //             'name'     => $_POST['name'],
                
    //             // Data Opsional (Ubah "" menjadi NULL)
    //             'nim'      => ($isUser && !empty($_POST['nim'])) ? $_POST['nim'] : null,
    //             'class'    => ($isUser && !empty($_POST['class'])) ? $_POST['class'] : null,
    //             'prodi'    => ($isUser && !empty($_POST['prodi'])) ? $_POST['prodi'] : null,
    //             'lab_id'   => ($isUser && !empty($_POST['lab_id'])) ? $_POST['lab_id'] : null,
    //             'interest' => ($isUser && !empty($_POST['interest'])) ? $_POST['interest'] : null,
                
    //             'position' => !empty($_POST['position']) ? $_POST['position'] : null,
    //             'no_telp'  => !empty($_POST['phone']) ? $_POST['phone'] : null,
    //             'alamat'   => !empty($_POST['address']) ? $_POST['address'] : null,
    //             'gender'   => !empty($_POST['gender']) ? $_POST['gender'] : null,
    //             'photo'    => $photoName,
    //             'is_completed' => $isCompleted
    //         ];

    //         if ($this->model('UserModel')->createUser($data)) {
    //             echo json_encode(['status' => 'success', 'title' => 'Berhasil', 'message' => 'User baru berhasil ditambahkan.']);
    //         } else {
    //             // Pesan error lebih spesifik biasanya karena duplikat email
    //             echo json_encode(['status' => 'error', 'title' => 'Gagal', 'message' => 'Gagal menambah user (Email mungkin sudah ada).']);
    //         }
    //         exit;
    //     }
    // }


    public function addUser() {
        // 1. Cek Akses
        $this->checkAccess(['Admin']);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Bersihkan output buffer agar JSON bersih
            ob_clean(); 
            header('Content-Type: application/json');

            $photoName = 'default.jpg'; 
            
            // 2. Logika Upload Foto (Tidak Ada Perubahan)
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

            $role = $_POST['role'];
            $isUser = ($role == 'User');
            
            // Cek kelengkapan profil dasar
            $isCompleted = (!empty($_POST['name']) && !empty($_POST['phone']) && !empty($_POST['address'])) ? 1 : 0;

            // 3. [PERBAIKAN UTAMA ADA DI SINI]
            // Mengubah 'null' menjadi '' (string kosong) agar Database tidak menolak data
            $data = [
                'email'    => $_POST['email'],
                'password' => $_POST['password'], 
                'role'     => $role,
                'name'     => $_POST['name'],
                
                // Ganti null dengan '' (String Kosong)
                'nim'      => ($isUser && !empty($_POST['nim'])) ? $_POST['nim'] : '',
                'class'    => ($isUser && !empty($_POST['class'])) ? $_POST['class'] : '',
                'prodi'    => ($isUser && !empty($_POST['prodi'])) ? $_POST['prodi'] : '',
                
                // Untuk ID/Angka, gunakan 0 jika kosong
                'lab_id'   => ($isUser && !empty($_POST['lab_id'])) ? $_POST['lab_id'] : 0,
                
                'interest' => ($isUser && !empty($_POST['interest'])) ? $_POST['interest'] : '',
                'position' => !empty($_POST['position']) ? $_POST['position'] : '',
                'no_telp'  => !empty($_POST['phone']) ? $_POST['phone'] : '',
                'alamat'   => !empty($_POST['address']) ? $_POST['address'] : '',
                'gender'   => !empty($_POST['gender']) ? $_POST['gender'] : '',
                'photo'    => $photoName,
                'is_completed' => $isCompleted
            ];

            // 4. Eksekusi ke Model
            if ($this->model('UserModel')->createUser($data)) {
                echo json_encode([
                    'status' => 'success', 
                    'title' => 'Berhasil', 
                    'message' => 'User baru berhasil ditambahkan.'
                ]);
            } else {
                // Jika masih gagal, pesannya tetap ini, tapi kemungkinan besar sudah berhasil
                echo json_encode([
                    'status' => 'error', 
                    'title' => 'Gagal', 
                    'message' => 'Gagal menambah user. Email mungkin sudah ada atau data tidak valid.'
                ]);
            }
            exit;
        }
    }

    public function editUser() {
        // if ($_SESSION['role'] != 'Admin') exit;
        $this->checkAccess(['Admin']);
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            ob_clean();
            header('Content-Type: application/json');

            $oldUser = $this->model('UserModel')->getUserById($_POST['id_user']);
            $photoName = $oldUser['photo_profile'];

            // Logika Upload Foto
            if (isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != "") {
                $targetDir = "../public/uploads/profile/";
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
                
                'position' => !empty($_POST['position']) ? $_POST['position'] : null,
                'no_telp'  => !empty($_POST['phone']) ? $_POST['phone'] : null,
                'alamat'   => !empty($_POST['address']) ? $_POST['address'] : null,
                'gender'   => !empty($_POST['gender']) ? $_POST['gender'] : null,
                'photo'    => ($photoName != $oldUser['photo_profile']) ? $photoName : null,
                'is_completed' => $isCompleted
            ];

            if (!empty($_POST['password'])) {
                $this->model('UserModel')->changePassword($data['id'], $_POST['password']);
            }

            // Panggil Model Update
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
        // if ($_SESSION['role'] != 'Admin') exit;
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
        // if ($_SESSION['role'] != 'Admin') exit;
        $this->checkAccess(['Admin']);        
        
        $data['judul'] = 'Rekap Presensi';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $attModel = $this->model('AttendanceModel');

        // 1. Ambil Data untuk Dropdown Filter
        $data['assistants_list'] = $attModel->getAllAssistantsList();

        // 2. Tangkap Input Filter
        // Logika: Jika tanggal kosong, default ke Hari Ini
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        $assistantId = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : null;

        // Kirim parameter ke View agar input filter tetap terisi
        $data['start_date'] = $startDate;
        $data['end_date'] = $endDate;
        $data['selected_assistant'] = $assistantId;

        // 3. Ambil Data Rekap (Model Pintar: Generate Alpha Otomatis)
        $data['attendance_list'] = $attModel->getAttendanceRecap($startDate, $endDate, $assistantId);

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/attendance', $data);
        $this->view('layout/footer');
    }

    public function exportCsv() {
        // if ($_SESSION['role'] != 'Admin') exit;
        $this->checkAccess(['Admin']);
        
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        $assistantId = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : null;

        $data = $this->model('AttendanceModel')->getAttendanceRecap($startDate, $endDate, $assistantId);

        $filename = "Rekap_Presensi_" . date('d-m-Y', strtotime($startDate)) . "_sd_" . date('d-m-Y', strtotime($endDate)) . ".csv";
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        // Header CSV
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
        // if ($_SESSION['role'] != 'Admin') exit;
        $this->checkAccess(['Admin']);
        
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        $assistantId = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : null;
        
        $attModel = $this->model('AttendanceModel');
        $data['attendance_list'] = $attModel->getAttendanceRecap($startDate, $endDate, $assistantId);
        
        $data['start_date'] = $startDate;
        $data['end_date'] = $endDate;
        
        // Nama Filter untuk Judul PDF
        $data['assistant_name'] = 'Semua Asisten';
        if($assistantId) {
            $user = $this->model('UserModel')->getUserById($assistantId);
            $data['assistant_name'] = $user['name'] ?? 'Asisten';
        }

        $this->view('admin/pdf_attendance', $data);
    }

    public function schedule() {
        // if ($_SESSION['role'] != 'Admin') exit;
        $this->checkAccess(['Admin']);
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
        // if ($_SESSION['role'] != 'Admin') exit;
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
        // if ($_SESSION['role'] != 'Admin') exit;
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
        // if ($_SESSION['role'] != 'Admin') exit;
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
        // if ($_SESSION['role'] != 'Admin') exit;
        $this->checkAccess(['Admin']);
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
        // if ($_SESSION['role'] != 'Admin') exit;
        $this->checkAccess(['Admin']);
        $userId = $_POST['user_id'] ?? 0;
        
        // Panggil Model Cerdas (Unified) agar Admin melihat status Alpha/Izin/Hadir yang akurat
        $logs = $this->model('LogbookModel')->getUnifiedLogbook($userId);
        
        echo json_encode($logs);
    }
    
    // [BARU] Fitur Super Reset (Admin)
    public function reset_logbook() {
        // if ($_SESSION['role'] != 'Admin') { 
        //     echo json_encode(['status'=>'error', 'message'=>'Unauthorized']); exit; 
        // }
        $this->checkAccess(['Admin']);

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

    public function saveLogbookAdmin() {
        // if ($_SESSION['role'] != 'Admin') exit;
        $this->checkAccess(['Admin']);
        
        // Setup Upload Config
        $fileName = null;
        if (isset($_FILES['proof_file']['name']) && $_FILES['proof_file']['name'] != "") {
            // Tentukan folder berdasarkan status (Attendance / Leaves)
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
        // if ($_SESSION['role'] != 'Admin') exit;
        $this->checkAccess(['Admin']);

        $id = $_POST['id'];
        if ($this->model('LogbookModel')->deleteLogAdmin($id)) echo json_encode(['status'=>'success']); else echo json_encode(['status'=>'error']);
    }
    
    public function profile() {
        // if ($_SESSION['role'] != 'Admin') exit;
        $this->checkAccess(['Admin']);        

        $data['judul'] = 'Profil Admin';
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
        
        // Format Tanggal & Mapping Key untuk View
        foreach ($rawSchedules as &$sch) {
            $sch['display_date'] = date('d M Y', strtotime($sch['tanggal']));
            // Menambahkan key 'type' manual agar sesuai dengan logika view
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
        // if ($_SESSION['role'] != 'Admin') exit;
        $this->checkAccess(['Admin']);

        $data['judul'] = 'Edit Profil Admin';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $this->view('layout/header', $data); $this->view('layout/sidebar', $data); $this->view('common/edit_profile', $data); $this->view('layout/footer');
    }
    
    public function updateProfile() {
        // if ($_SESSION['role'] != 'Admin') exit;
        $this->checkAccess(['Admin']);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $userModel = $this->model('UserModel');
            $currentUser = $userModel->getUserById($_SESSION['user_id']);

            // --- LOGIKA UPLOAD FOTO (Tetap Sama) ---
            $photoName = $currentUser['photo_profile'];
            $targetDir = "../public/uploads/profile/";

            // Cek Base64 Cropper
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
            // Cek Upload Biasa
            elseif (isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != "") {
                if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                $fileName = time() . '_' . basename($_FILES["photo"]["name"]);
                if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetDir . $fileName)) {
                    $photoName = $fileName;
                    $_SESSION['photo'] = $fileName;
                }
            }

            // [LOGIC BARU] Cek Kelengkapan Profil Admin
            // Jika Nama, HP, dan Alamat terisi -> Set Completed = 1
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
                'photo'    => ($photoName != $currentUser['photo_profile']) ? $photoName : null,
                'is_completed' => $isCompleted 
            ];

            if ($userModel->updateSelfProfile($data)) {
                $_SESSION['name'] = $_POST['name'];
                $_SESSION['jabatan'] = $_POST['position'];
                
                // [PERBAIKAN UTAMA: Masalah Undefined & Redirect]
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
        // if ($_SESSION['role'] != 'Admin') exit;
        $this->checkAccess(['Admin']);    

        $type = $_POST['type'] ?? 'check_in'; // 'check_in' atau 'check_out'
        $token = $this->model('QrModel')->getOrGenerateToken($type);
        
        // Format JSON agar bisa dibaca oleh QRCodeJS di frontend
        $qrString = json_encode([
            'type' => ($type == 'check_in') ? 'CHECK_IN' : 'CHECK_OUT', 
            'token' => $token
        ]);
        
        echo json_encode(['status' => 'success', 'qr_data' => $qrString]);
    }

    public function assistantSchedule($id) {
        // if ($_SESSION['role'] != 'Admin') exit;
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

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/assistant_schedule', $data); 
        $this->view('layout/footer');
    }

    // // --- FUNGSI BANTUAN UNTUK CEK AKSES & ERROR 401 ---
    // private function checkAccess($allowedRoles = ['Admin']) {
    //     // 1. Cek Login
    //     if (!isset($_SESSION['role'])) {
    //         header("Location: " . BASE_URL . "/auth/login");
    //         exit;
    //     }

    //     // 2. Cek Role (Jika role user tidak ada dalam daftar yang diizinkan)
    //     if (!in_array($_SESSION['role'], $allowedRoles)) {
    //         require_once '../app/controllers/ErrorController.php';
    //         $error = new ErrorController();
    //         $error->unauthorized();
    //         exit; // Matikan script agar halaman admin tidak bocor
    //     }
    // }
}
?>