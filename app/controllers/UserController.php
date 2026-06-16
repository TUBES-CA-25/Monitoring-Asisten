<?php
require_once '../app/core/GoogleClient.php';

class UserController extends Controller {
    public function index() { 
        $this->dashboard(); 
    }

    /**
     * [BARU – Tahap 30] Cek apakah akun user masih aktif.
     * Dipanggil di awal setiap method yang membutuhkan akses fitur.
     * Jika nonaktif, redirect ke halaman suspended (tanpa destroy session).
     */
    private function checkAccountActive() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        $status = $_SESSION['status_account'] ?? 'ACTIVE';

        // Jika session belum punya status_account, ambil dari DB (bisa
        // terjadi jika admin baru mengubah status setelah user login).
        if ($status === 'ACTIVE' && !empty($_SESSION['user_id'])) {
            require_once '../app/models/ResetAttendanceModel.php';
            $model = new ResetAttendanceModel();
            if (!$model->isAccountActive((int)$_SESSION['user_id'])) {
                $_SESSION['status_account'] = 'INACTIVE';
                $status = 'INACTIVE';
            }
        }

        if ($status === 'INACTIVE') {
            header("Location: " . BASE_URL . "/auth/suspended");
            exit;
        }
    }

    public function dashboard() {
        $this->checkAccess(['User']);
        $this->checkAccountActive();

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

        $data['page_css'][] = ASSET_URL . '/css/user/dashboard.css';
        $data['page_js'][]  = ASSET_URL . '/js/user/dashboard.js';

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data); 
        $this->view('user/dashboard', $data);
        $this->view('layout/footer', $data);
    }

    public function profile() {
        $this->checkAccess(['User']);
        $this->checkAccountActive();

        $data['judul'] = 'Profil Saya';
        $userModel = $this->model('UserModel');
        $attModel = $this->model('AttendanceModel');

        $data['user'] = $userModel->getUserById($_SESSION['user_id']);
        $pId = $_SESSION['profil_id'];

        $data['is_google_connected'] = $userModel->isGoogleConnected($_SESSION['user_id']);
        $data['google_configured'] = (new GoogleClient())->isConfigured();
        
        $userStats = $attModel->getUserStats($pId);
        $data['stats'] = ['hadir' => $userStats['hadir'], 'izin' => $userStats['izin'], 'alpa' => 0];

        $data['page_css'] = [
            ASSET_URL . '/css/common/profile.css'
        ];

        $data['page_js'] = [
            'https://cdn.jsdelivr.net/npm/chart.js',
            ASSET_URL . '/js/common/profile.js'
        ];

        $data['js_config'] = [
            'BASE_URL' => BASE_URL,
            'RAW_DEMOGRAPHICS' => $data['demographics'] ?? [],
            'RANKINGS' => $data['rankings'] ?? [],
            'USER_STATS' => $data['stats'] ?? null,
            'IS_USER_ROLE' => ($data['user']['role'] === 'User')
        ];

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('common/profile', $data);
        $this->view('layout/footer', $data);
    }

    public function editProfile() {
        $this->checkAccess(['User']);
        $this->checkAccountActive();
        
        $data['judul'] = 'Edit Profil';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);

        // [PERBAIKAN] Asisten hanya dapat mengakses halaman edit profil SATU KALI
        // (selama belum melengkapi profil). Setelah is_completed = 1, akses
        // langsung ke /user/editProfile dialihkan kembali ke halaman profil
        // dengan pesan "Profil Terkunci" (sebelumnya hanya tombol "Edit Profil"
        // yang disembunyikan di common/profile.php, tapi URL tetap bisa diakses).
        if (!empty($data['user']['is_completed'])) {
            $_SESSION['google_modal'] = [
                'type' => 'locked',
                'title' => 'Profil Terkunci',
                'message' => 'Anda sudah melengkapi profil. Data tidak dapat diubah lagi. Hubungi Administrator jika terdapat kesalahan.'
            ];
            header('Location: ' . BASE_URL . '/user/profile');
            exit;
        }

        $data['labs'] = $this->model('LabModel')->getAllLabs();
        
        $data['role'] = $_SESSION['role'];
        $data['isUser'] = ($_SESSION['role'] == 'User');
        $data['isAdmin'] = ($_SESSION['role'] == 'Admin');

        $data['page_css'] = [
            'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css',
            ASSET_URL . '/css/common/edit_profile.css'
        ];

        $data['page_js'] = [
            'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js',
            ASSET_URL . '/js/common/edit_profile.js'
        ];

        $data['js_config'] = [
            'BASE_URL' => BASE_URL,
            'USER_ROLE' => $data['user']['role']
        ];
        
        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('common/edit_profile', $data);
        $this->view('layout/footer', $data);
    }

    public function updateProfile() {
        $this->checkAccess(['User']);
        $this->checkAccountActive();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            ob_clean(); header('Content-Type: application/json');

            $userModel = $this->model('UserModel');
            $currentUser = $userModel->getUserById($_SESSION['user_id']);

            // [PERBAIKAN] Asisten hanya dapat melengkapi/mengubah profil SATU KALI.
            // Setelah is_completed = 1, permintaan update berikutnya ditolak di sisi
            // server (sebelumnya hanya disembunyikan di UI common/profile.php).
            if (!empty($currentUser['is_completed'])) {
                echo json_encode(['status' => 'error', 'title' => 'Profil Terkunci', 'message' => 'Profil Anda sudah dikunci dan tidak dapat diubah lagi. Hubungi Administrator jika terdapat kesalahan.']);
                exit;
            }

            if (empty($_POST['name']) || empty($_POST['phone']) || empty($_POST['address'])) {
                echo json_encode(['status' => 'error', 'title' => 'Gagal', 'message' => 'Data wajib diisi.']); exit;
            }

            // [BARU - Edit Email/Password] validasi email (wajib, format,
            // & keunikan) dan password baru (opsional, minimal 8 karakter)
            // - sama seperti pada form Tambah/Edit Pengguna Admin.
            $newEmail = trim($_POST['email'] ?? '');
            if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['status' => 'error', 'title' => 'Gagal', 'message' => 'Email tidak valid.']); exit;
            }
            if ($newEmail !== $currentUser['email'] && $userModel->isEmailTaken($newEmail, $_SESSION['user_id'])) {
                echo json_encode(['status' => 'error', 'title' => 'Gagal', 'message' => 'Email sudah digunakan oleh akun lain.']); exit;
            }

            $newPassword = $_POST['password'] ?? '';
            if (!empty($newPassword) && strlen($newPassword) < 8) {
                echo json_encode(['status' => 'error', 'title' => 'Gagal', 'message' => 'Password baru minimal 8 karakter.']); exit;
            }

            // [PERBAIKAN] Sebelumnya memakai $_POST['old_photo'] yang tidak pernah
            // dikirim oleh form (menyebabkan PHP warning & response bukan JSON
            // valid). Kini memakai foto lama dari database, dan turut menangani
            // hasil crop (cropped_image) seperti pada Admin/Kepala Lab.
            $photoName = $currentUser['photo_profile'];
            $targetDir = UPLOAD_PATH . 'profile/';
            // [BARU - Audit Validasi Foto] hanya terima JPG/JPEG/PNG, selaras
            // dengan accept="image/png, image/jpeg, image/jpg" pada input file.
            $allowedExtensions = ['jpg', 'jpeg', 'png'];

            if (!empty($_POST['cropped_image'])) {
                $dataImg = $_POST['cropped_image'];
                if (preg_match('/^data:image\/(\w+);base64,/', $dataImg, $type)) {
                    $type = strtolower($type[1]);
                    if (!in_array($type, $allowedExtensions)) {
                        echo json_encode(['status' => 'error', 'title' => 'Format Tidak Didukung', 'message' => 'Foto harus berformat JPG, JPEG, atau PNG.']);
                        exit;
                    }
                    $dataImg = substr($dataImg, strpos($dataImg, ',') + 1);
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
                $fileExt = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
                if (!in_array($fileExt, $allowedExtensions)) {
                    echo json_encode(['status' => 'error', 'title' => 'Format Tidak Didukung', 'message' => 'Foto harus berformat JPG, JPEG, atau PNG.']);
                    exit;
                }
                if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                $newFileName = time() . '_' . uniqid() . '.' . $fileExt;

                if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetDir . $newFileName)) {
                    $photoName = $newFileName;
                    $_SESSION['photo'] = $newFileName;
                    if ($currentUser['photo_profile'] && $currentUser['photo_profile'] != 'default.jpg' && file_exists($targetDir . $currentUser['photo_profile'])) {
                        unlink($targetDir . $currentUser['photo_profile']);
                    }
                }
            }

            $labId = !empty($_POST['lab_id']) ? $_POST['lab_id'] : null;

            $data = [
                'id' => $_SESSION['user_id'],
                'name' => $_POST['name'],
                'email' => $newEmail,
                'password' => $newPassword,
                'nim' => $_POST['nim'],
                'class' => $_POST['class'],
                'angkatan' => $_POST['angkatan'] ?? '',
                'prodi' => $_POST['prodi'],
                'phone' => $_POST['phone'],
                'address' => $_POST['address'],
                'gender' => $_POST['gender'],
                'position' => $_POST['position'],
                'lab_id' => $labId,
                'interest' => $_POST['interest'] ?? '',
                'photo' => ($photoName != $currentUser['photo_profile']) ? $photoName : null,
                'is_completed' => 1
            ];

            if ($userModel->updateSelfProfile($data)) {
                $_SESSION['name'] = $_POST['name'];

                echo json_encode([
                    'status'   => 'success',
                    'title'    => 'Berhasil',
                    'message'  => 'Profil berhasil disimpan dan dikunci secara permanen.',
                    'redirect' => BASE_URL . '/user/profile'
                ]);
            } else {
                echo json_encode(['status' => 'error', 'title' => 'Gagal', 'message' => 'Gagal memperbarui profil.']);
            }
            exit;
        }
    }

    public function logbook() {
        $this->checkAccess(['User']);
        $this->checkAccountActive();
        
        $data['judul'] = 'Logbook Kegiatan';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $allLogs = $this->model('LogbookModel')->getUnifiedLogbook($_SESSION['user_id']); 
        
        $itemsPerPage = 10; 
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($currentPage < 1) $currentPage = 1;

        $totalData = count($allLogs);
        $totalPages = ceil($totalData / $itemsPerPage);
        
        if ($currentPage > $totalPages && $totalPages > 0) $currentPage = $totalPages;

        $offset = ($currentPage - 1) * $itemsPerPage;
        $slicedLogs = array_slice($allLogs, $offset, $itemsPerPage);

        $data['logs'] = $slicedLogs;
        $data['pagination'] = [
            'current' => $currentPage,
            'total_pages' => $totalPages,
            'total_items' => $totalData,
            'per_page' => $itemsPerPage
        ];

        $data['page_css'][] = ASSET_URL . '/css/user/logbook.css';

        $data['page_js'][]  = ASSET_URL . '/js/user/logbook.js';

        $data['js_config'] = [
            'BASE_URL' => BASE_URL
        ];

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('user/logbook', $data);
        $this->view('layout/footer', $data);
    }

    public function submit_logbook() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'User') {
            http_response_code(401);
            echo json_encode(['status'=>'error', 'message'=>'Unauthorized']);
            exit;
        }

        header('Content-Type: application/json');

        $pId = $_SESSION['profil_id']; 
        $targetDate = $_POST['date'] ?? date('Y-m-d');
        $logId = $_POST['log_id'] ?? null;
        // [BARU - Modul 3 V3] Form mengirim <input type="hidden" name="log_id"
        // value=""> untuk entri BARU (belum ada log_id) - perlakukan string
        // kosong sama seperti tidak dikirim sama sekali, agar tidak salah
        // ditolak oleh validasi ctype_digit di bawah.
        if ($logId === '') $logId = null;

        // [BARU - Modul 3 V3] Validasi input: aktivitas wajib diisi & log_id
        // (jika dikirim) harus berupa angka. Sebelumnya $_POST['activity']
        // langsung dipakai tanpa cek (bisa "Undefined array key" / kosong).
        $activity = isset($_POST['activity']) ? trim($_POST['activity']) : '';
        if ($activity === '') {
            echo json_encode(['status'=>'error', 'message'=>'Aktivitas tidak boleh kosong.']); exit;
        }
        if ($logId !== null && !ctype_digit((string) $logId)) {
            echo json_encode(['status'=>'error', 'message'=>'Log ID tidak valid.']); exit;
        }

        $att = $this->model('AttendanceModel')->validateLogbookEntry($pId, $targetDate);

        if (!$att || !$att['waktu_presensi']) {
            echo json_encode(['status'=>'error', 'message'=>'Anda belum melakukan scan masuk!']); exit;
        }
        if ($att['waktu_pulang']) {
            echo json_encode(['status'=>'error', 'message'=>'Logbook terkunci karena Anda sudah scan pulang.']); exit;
        }

        $payload = [
            'log_id'   => $logId,
            'user_id'  => $_SESSION['user_id'],
            'date'     => $targetDate,
            'time'     => $_POST['time'] ?? date('H:i'),
            'activity' => $activity
        ];

        $result = $this->model('LogbookModel')->saveLogbook($payload);

        if ($result === true) {
            echo json_encode(['status'=>'success']);
        } elseif ($result === 'not_found') {
            // [BARU - Modul 3 V3] log_id tidak ditemukan / bukan milik Anda
            // (ownership validation) - sebelumnya UPDATE 0-baris tetap
            // dianggap "success".
            echo json_encode(['status'=>'error', 'message'=>'Data logbook tidak ditemukan atau bukan milik Anda.']);
        } else {
            echo json_encode(['status'=>'error', 'message'=>'Gagal menyimpan data database.']);
        }
    }

    public function reset_logbook() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'User') { http_response_code(401); echo json_encode(['status'=>'error', 'message'=>'Unauthorized']); exit; }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // [BARU - Modul 3 V3] Validasi input: log_id wajib ada & berupa
            // angka (sebelumnya $_POST['log_id'] langsung dipakai tanpa cek).
            $logId = $_POST['log_id'] ?? null;
            if (empty($logId) || !ctype_digit((string) $logId)) {
                echo json_encode(['status'=>'error', 'message'=>'Log ID tidak valid.']); exit;
            }

            $result = $this->model('LogbookModel')->resetLogUser($logId, $_SESSION['user_id']);

            if ($result === true) {
                echo json_encode(['status' => 'success', 'message' => 'Isi logbook berhasil dikosongkan.']);
            } elseif ($result === 'not_found') {
                // [BARU - Modul 3 V3] Ownership validation.
                echo json_encode(['status' => 'error', 'message' => 'Data logbook tidak ditemukan atau bukan milik Anda.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal mereset logbook.']);
            }
        }
    }

    public function saveLogbook()
    {
        $this->checkAccess(['User']);
        $this->checkAccountActive();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/user/dashboard");
            exit;
        }

        $logbookModel = $this->model('LogbookModel');

        $data = [
            'user_id' => $_SESSION['user_id'] ?? null,
            'log_id'  => $_POST['log_id'] ?? null,
            'activity'=> isset($_POST['activity']) ? trim($_POST['activity']) : null,
            'time'    => $_POST['time'] ?? null,
            'date'    => $_POST['date'] ?? null
        ];

        if (
            empty($data['user_id']) ||
            empty($data['activity']) ||
            empty($data['time']) ||
            (empty($data['log_id']) && empty($data['date']))
        ) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Data logbook tidak lengkap.'
            ];
            header("Location: " . BASE_URL . "/user/logbook");
            exit;
        }

        // [BARU - Modul 3 V3] Endpoint ini sebelumnya TIDAK memvalidasi
        // "tombol input/edit tidak tampil sebelum presensi" sama sekali
        // (berbeda dari submit_logbook()), sehingga bisa diakses langsung
        // (POST ke /user/saveLogbook) untuk mengisi logbook tanpa scan
        // masuk, atau setelah scan pulang. Sekarang memakai validasi yang
        // sama (Controller validation).
        $pId = $_SESSION['profil_id'];
        $targetDate = $data['date'] ?: date('Y-m-d');
        $att = $this->model('AttendanceModel')->validateLogbookEntry($pId, $targetDate);

        if (!$att || !$att['waktu_presensi']) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Anda belum melakukan scan masuk!'
            ];
            header("Location: " . BASE_URL . "/user/logbook");
            exit;
        }
        if ($att['waktu_pulang']) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Logbook terkunci karena Anda sudah scan pulang.'
            ];
            header("Location: " . BASE_URL . "/user/logbook");
            exit;
        }

        $result = $logbookModel->saveLogbook($data);

        if ($result === true) {
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'Logbook berhasil disimpan.'
            ];
        } elseif ($result === 'not_found') {
            // [BARU - Modul 3 V3] Ownership validation: log_id tidak ditemukan
            // / bukan milik Anda.
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Data logbook tidak ditemukan atau bukan milik Anda.'
            ];
        } else {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Gagal menyimpan logbook.'
            ];
        }

        header("Location: " . BASE_URL . "/user/logbook");
        exit;
    }



    public function schedule() {
        $this->checkAccess(['User']);
        $this->checkAccountActive();
        
        $data['judul'] = 'Jadwal Saya';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $data['raw_schedules'] = $this->model('ScheduleModel')->getAllUserSchedules($_SESSION['user_id']); 

        // [BARU - Modul Dosen] daftar dosen master untuk dropdown "Dosen Pengampu"
        $data['dosen_list'] = $this->model('ScheduleModel')->getAllDosen();

        // [BARU - Modul Lab] daftar laboratorium master untuk dropdown "Lokasi/Ruangan"
        $data['lab_list'] = $this->model('ScheduleModel')->getAllLabs();

        $data['page_css'][] = ASSET_URL . '/css/user/schedule.css';
        $data['page_js'][]  = ASSET_URL . '/js/user/schedule.js';

        $data['js_config'] = [
            'baseUrl'       => BASE_URL,
            'rawEvents'     => $data['raw_schedules'],
            'currentUserId' => (string) ($_SESSION['profil_id'] ?? '')
        ];

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('user/schedule', $data);
        $this->view('layout/footer', $data);
    }

    public function addSchedule() {
        $this->checkAccess(['User']);
        $this->checkAccountActive();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $data = [
                'type' => 'kuliah', 
                'user_id' => $_SESSION['profil_id'],
                'title' => $_POST['title'],
                'location' => $_POST['location'],
                'dosen' => $_POST['dosen'] ?? null, 'id_dosen' => $_POST['id_dosen'] ?? null, 'dosen_baru' => $_POST['dosen_baru'] ?? null,
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
        $this->checkAccess(['User']);
        $this->checkAccountActive();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $data = [
                'id' => $_POST['id_schedule'], 
                'type' => 'kuliah', 
                'user_id' => $_SESSION['profil_id'],
                'title' => $_POST['title'],
                'location' => $_POST['location'],
                'dosen' => $_POST['dosen'] ?? null, 'id_dosen' => $_POST['id_dosen'] ?? null, 'dosen_baru' => $_POST['dosen_baru'] ?? null,
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
        $this->checkAccess(['User']);
        $this->checkAccountActive();
        
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
        $this->checkAccess(['User']);
        $this->checkAccountActive();
        $data['judul'] = 'Scan Presensi';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);

        $data['page_css'][] = ASSET_URL . '/css/user/scan.css';
        $data['page_js'][]  = ASSET_URL . '/js/user/scan.js';

        $this->view('user/scan', $data); 
    }

    public function check_qr_type() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            ob_clean(); header('Content-Type: application/json');

            // PENTING: endpoint ini sebelumnya tidak memiliki pengecekan sesi/role
            // sama sekali, sehingga bisa diakses tanpa login.
            if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'User') {
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
                exit;
            }

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

            // PENTING: endpoint ini sebelumnya tidak memiliki pengecekan sesi/role
            // sama sekali, sehingga bisa diakses tanpa login.
            if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'User') {
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
                exit;
            }

            $token = $_POST['token'];
            $image = $_POST['image'];
            $typeInput = $_POST['type']; 

            if (!$this->model('QrModel')->validateToken($token, $typeInput)) {
                echo json_encode(['status' => 'error', 'message' => 'Token QR Code tidak valid/sesuai.']);
                exit;
            }

            $folderPath = UPLOAD_PATH . 'attendance/';
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
            $result = false;

            if ($typeInput == 'check_in') {
                $result = $attModel->clockIn($userId, $fileName);
                $msg = $result ? 'Berhasil Check-In!' : 'Gagal Check-In / Anda sudah absen hari ini.';
            } else {
                $result = $attModel->clockOut($userId, $fileName);
                $msg = $result ? 'Berhasil Check-Out!' : 'Gagal Check-Out / Belum saatnya pulang.';
            }

            if ($result) {
                // [BARU] Field tambahan bersifat ADDITIVE (attendance_status,
                // late_minutes saat check-in; work_duration, is_early_checkout
                // saat check-out) — tidak mengubah field {status, message} yang
                // sudah ada agar tetap kompatibel dengan aplikasi mobile.
                $response = array_merge(['status' => 'success', 'message' => $msg], $result);
                echo json_encode($response);
            } else {
                echo json_encode(['status' => 'error', 'message' => $msg]);
            }
        }

        $data['page_css'][] = ASSET_URL . '/css/user/capture.css';
        $data['page_js'][]  = ASSET_URL . '/js/user/capture.js';
    }

    public function submit_leave() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            ob_clean(); header('Content-Type: application/json');

            // PENTING: endpoint ini sebelumnya tidak memiliki pengecekan sesi/role
            // sama sekali, sehingga bisa diakses tanpa login.
            if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'User') {
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
                exit;
            }

            $pId = $_SESSION['profil_id'];
            $type = $_POST['type'];
            $reason = $_POST['reason'];
            $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];

            $fileName = '';
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $targetDir = UPLOAD_PATH . 'leaves/';
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