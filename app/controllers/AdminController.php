<?php
require_once '../app/core/GoogleClient.php';

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
        $data['js'] = 'admin/dashboard.js';

        // Script chart "Performa Asisten" & modal "Cari Asisten" (dipakai bersama
        // oleh dashboard Admin & Kepala Lab)
        $data['page_js'] = [
            ASSET_URL . '/js/common/assistant_performance_chart.js',
            ASSET_URL . '/js/common/assistant_search_modal.js'
        ];

        $data['vendor_js'] = [
            'https://cdn.jsdelivr.net/npm/chart.js',
            'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js'
        ];

        $data['js_config'] = [
            'baseUrl'     => rtrim(BASE_URL, '/'),
            'roleSegment' => strtolower(str_replace(' ', '', $_SESSION['role'])),
            'chartData'   => $data['chart_data'],
            'qrIn'        => $data['qr_in'],
            'qrOut'       => $data['qr_out']
        ];


        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/dashboard', $data);
        $this->view('layout/footer', $data);
    }

    /**
     * AJAX: data chart "Performa Asisten" (semua lab) untuk dashboard Admin.
     * Dipanggil oleh public/assets/js/common/assistant_performance_chart.js
     */
    public function getAssistantChartData() {
        $this->checkAccess(['Admin']);

        ob_clean();
        header('Content-Type: application/json');

        $allowed = ['kehadiran', 'jam_masuk', 'izin', 'logbook', 'jadwal', 'durasi_kerja'];
        $metric = $_GET['metric'] ?? 'kehadiran';
        if (!in_array($metric, $allowed)) {
            $metric = 'kehadiran';
        }

        // Admin melihat seluruh asisten dari semua lab (id_lab = null = tanpa filter)
        $rows = $this->model('UserModel')->getAssistantPerformanceData($metric, null);

        echo json_encode([
            'status' => 'success',
            'metric' => $metric,
            'labels' => array_column($rows, 'name'),
            'data'   => array_column($rows, 'score')
        ]);
        exit;
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
            ASSET_URL . '/css/admin/users.css',
            // [BARU - Fitur Crop Foto] sama seperti common/edit_profile.php
            'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css'
        ];

        $data['page_js'] = [
            // [BARU - Fitur Crop Foto]
            'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js',
            ASSET_URL . '/js/admin/users.js'
        ];

        $data['js_config'] = [
            'BASE_URL' => BASE_URL
        ];

        // [BARU - Tahap 29] Pencarian real-time: jika dipanggil via AJAX
        // (?ajax=1, dari public/assets/js/admin/users.js), render HANYA
        // partial tabel + pagination tanpa header/sidebar/footer, agar bisa
        // langsung disisipkan ke halaman tanpa reload.
        if (!empty($_GET['ajax'])) {
            ob_start();
            extract($data);
            // Suppress notices/warnings agar tidak merusak HTML yang di-parse JS
            $prev = error_reporting(0);
            require '../app/views/admin/partials/users_table.php';
            error_reporting($prev);
            $html = ob_get_clean();
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/users', $data); 
        $this->view('layout/footer', $data);
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

            // [BARU - Validasi Password] sama seperti common/edit_profile.php:
            // password wajib diisi untuk akun baru, minimal 8 karakter.
            if (empty($_POST['password']) || strlen($_POST['password']) < 8) {
                echo json_encode(['status' => 'error', 'title' => 'Password Tidak Valid', 'message' => 'Password wajib diisi, minimal 8 karakter.']);
                exit;
            }

            $photoName = 'default.jpg';
            $targetDir = UPLOAD_PATH . 'profile/';
            // [DIUBAH - Fitur Crop Foto] hanya terima JPG/JPEG/PNG (selaras
            // dengan accept="image/png, image/jpeg, image/jpg" pada input
            // file, dan dengan edit_profile.php yang sudah lebih dulu
            // memvalidasi 3 ekstensi ini). 'webp' yang sebelumnya diizinkan
            // di sini dihapus untuk konsistensi.
            $allowedExtensions = ['jpg', 'jpeg', 'png'];

            // [BARU - Fitur Crop Foto] Sama seperti common/edit_profile.php:
            // jika admin memotong foto lewat Cropper.js, hasilnya dikirim
            // sebagai data URL base64 di $_POST['cropped_image'].
            if (!empty($_POST['cropped_image'])) {
                $dataImg = $_POST['cropped_image'];
                if (preg_match('/^data:image\/(\w+);base64,/', $dataImg, $type)) {
                    $type = strtolower($type[1]);
                    if (in_array($type, $allowedExtensions)) {
                        $dataImg = substr($dataImg, strpos($dataImg, ',') + 1);
                        $decodedData = base64_decode($dataImg);
                        if ($decodedData !== false) {
                            if (!file_exists($targetDir)) {
                                mkdir($targetDir, 0777, true);
                            }
                            $newFileName = time() . '_' . uniqid() . '.' . $type;
                            if (file_put_contents($targetDir . $newFileName, $decodedData)) {
                                $photoName = $newFileName;
                            } else {
                                echo json_encode(['status' => 'error', 'title' => 'Gagal Simpan', 'message' => 'Gagal menyimpan hasil crop foto. Cek izin folder profile.']);
                                exit;
                            }
                        }
                    } else {
                        echo json_encode(['status' => 'error', 'title' => 'Format Tidak Didukung', 'message' => 'Foto harus berformat JPG, JPEG, atau PNG.']);
                        exit;
                    }
                }
            } elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                
                $fileExtension = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
                
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
                } else {
                    echo json_encode(['status' => 'error', 'title' => 'Format Tidak Didukung', 'message' => 'Foto harus berformat JPG, JPEG, atau PNG.']);
                    exit;
                }
            }

            $role = $_POST['role'];
            $isUser = ($role == 'User');
            $labRelevant = in_array($role, ['User', 'Kepala Lab']);
            $isCompleted = (!empty($_POST['name']) && !empty($_POST['phone']) && !empty($_POST['address'])) ? 1 : 0;

            $data = [
                'email'    => $_POST['email'],
                'password' => $_POST['password'], 
                'role'     => $role,
                'name'     => $_POST['name'],
                'nim'      => ($isUser && !empty($_POST['nim'])) ? $_POST['nim'] : '',
                'class'    => ($isUser && !empty($_POST['class'])) ? $_POST['class'] : '',
                'angkatan' => ($isUser && !empty($_POST['angkatan'])) ? $_POST['angkatan'] : '',
                'prodi'    => ($isUser && !empty($_POST['prodi'])) ? $_POST['prodi'] : '',
                'lab_id'   => ($labRelevant && !empty($_POST['lab_id'])) ? $_POST['lab_id'] : 0,
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

            // [BARU - Validasi Password] sama seperti common/edit_profile.php:
            // password baru opsional (kosong = tidak diganti), tapi jika
            // diisi harus minimal 8 karakter.
            if (!empty($_POST['password']) && strlen($_POST['password']) < 8) {
                echo json_encode(['status' => 'error', 'title' => 'Password Tidak Valid', 'message' => 'Password baru minimal 8 karakter.']);
                exit;
            }

            $oldUser = $this->model('UserModel')->getUserById($_POST['id_user']);
            $photoName = $oldUser['photo_profile'];
            $targetDir = UPLOAD_PATH . 'profile/';
            // [DIUBAH - Fitur Crop Foto] hanya terima JPG/JPEG/PNG, selaras
            // dengan accept="image/png, image/jpeg, image/jpg" dan
            // common/edit_profile.php. 'webp' dihapus untuk konsistensi.
            $allowedExtensions = ['jpg', 'jpeg', 'png'];

            // [BARU - Fitur Crop Foto] hasil potong dari Cropper.js (lihat
            // common/edit_profile.php untuk pola yang sama).
            if (!empty($_POST['cropped_image'])) {
                $dataImg = $_POST['cropped_image'];
                if (preg_match('/^data:image\/(\w+);base64,/', $dataImg, $type)) {
                    $type = strtolower($type[1]);
                    if (in_array($type, $allowedExtensions)) {
                        $dataImg = substr($dataImg, strpos($dataImg, ',') + 1);
                        $decodedData = base64_decode($dataImg);
                        if ($decodedData !== false) {
                            if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                            $newFileName = time() . '_' . uniqid() . '.' . $type;
                            if (file_put_contents($targetDir . $newFileName, $decodedData)) {
                                if ($oldUser['photo_profile'] && $oldUser['photo_profile'] != 'default.jpg' && file_exists($targetDir . $oldUser['photo_profile'])) {
                                    unlink($targetDir . $oldUser['photo_profile']);
                                }
                                $photoName = $newFileName;
                            } else {
                                echo json_encode(['status' => 'error', 'title' => 'Gagal Simpan', 'message' => 'Gagal menyimpan hasil crop foto. Cek izin folder profile.']);
                                exit;
                            }
                        }
                    } else {
                        echo json_encode(['status' => 'error', 'title' => 'Format Tidak Didukung', 'message' => 'Foto harus berformat JPG, JPEG, atau PNG.']);
                        exit;
                    }
                }
            } elseif (isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != "") {
                $fileType = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));

                if (in_array($fileType, $allowedExtensions)) {
                    $fileName = time() . '_' . basename($_FILES["photo"]["name"]);
                    $targetFilePath = $targetDir . $fileName;

                    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFilePath)) {
                        if ($oldUser['photo_profile'] && $oldUser['photo_profile'] != 'default.jpg' && file_exists($targetDir . $oldUser['photo_profile'])) {
                            unlink($targetDir . $oldUser['photo_profile']);
                        }
                        $photoName = $fileName;
                    }
                } else {
                    echo json_encode(['status' => 'error', 'title' => 'Format Tidak Didukung', 'message' => 'Foto harus berformat JPG, JPEG, atau PNG.']);
                    exit;
                }
            }

            $role = $_POST['role'];

            // [PERBAIKAN] Jangan timpa data yang sudah ada dengan NULL hanya karena
            // field tidak terkirim/kosong dari form (mis. field disembunyikan untuk
            // role tertentu). Jika input baru kosong, pertahankan nilai lama.
            $nim      = !empty($_POST['nim'])      ? $_POST['nim']      : ($oldUser['nim'] ?? null);
            $kelas    = !empty($_POST['class'])    ? $_POST['class']    : ($oldUser['kelas'] ?? null);
            $angkatan = !empty($_POST['angkatan']) ? $_POST['angkatan'] : ($oldUser['angkatan'] ?? null);
            $prodi    = !empty($_POST['prodi'])    ? $_POST['prodi']    : ($oldUser['prodi'] ?? null);
            $interest = !empty($_POST['interest']) ? $_POST['interest'] : ($oldUser['peminatan'] ?? null);
            $labId    = !empty($_POST['lab_id'])   ? $_POST['lab_id']   : ($oldUser['id_lab'] ?? null);

            $phone    = !empty($_POST['phone'])   ? $_POST['phone']   : ($oldUser['no_telp'] ?? null);
            $address  = !empty($_POST['address']) ? $_POST['address'] : ($oldUser['alamat'] ?? null);
            $gender   = !empty($_POST['gender'])  ? $_POST['gender']  : ($oldUser['jenis_kelamin'] ?? null);

            // Field NIM/Kelas/Prodi/Peminatan & Laboratorium hanya relevan untuk
            // role tertentu, tapi nilainya tetap dipertahankan (tidak dihapus) jika
            // role berubah, supaya data tidak hilang bila role dikembalikan lagi.
            $isCompleted = (!empty($_POST['name']) && !empty($phone) && !empty($address)) ? 1 : 0;

            $data = [
                'id'       => $_POST['id_user'],
                'name'     => $_POST['name'],
                'email'    => $_POST['email'],
                'role'     => $role,
                'password' => !empty($_POST['password']) ? $_POST['password'] : null,
                // [BARU – Tahap 33] status_account hanya boleh diubah untuk
                // role User (Asisten). Admin dan Kepala Lab selalu ACTIVE.
                'status_account' => ($role === 'User' && in_array($_POST['status_account'] ?? 'ACTIVE', ['ACTIVE','INACTIVE']))
                    ? $_POST['status_account'] : 'ACTIVE',
                
                'nim'      => $nim,
                'class'    => $kelas,
                'angkatan' => $angkatan,
                'prodi'    => $prodi,
                'lab_id'   => $labId,
                'interest' => $interest,
                
                'position' => $_POST['position'],
                
                'no_telp'  => $phone,
                'alamat'   => $address,
                'gender'   => $gender,
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

    /**
     * [BARU - Tahap 30] Reset presensi & logbook asisten + auto-download ZIP.
     *
     * GET ?scope=all               → reset semua asisten
     * GET ?scope=single&pid=N      → reset satu asisten (id_profil = N)
     * GET ?scope=single&uid=N      → alternatif: pakai id_user
     *
     * Langsung stream file ZIP ke browser via header Content-Disposition.
     */
    public function resetAttendance() {
        $this->checkAccess(['Admin']);

        ob_clean();

        require_once '../app/models/ResetAttendanceModel.php';
        $model    = new ResetAttendanceModel();
        $adminId  = (int) ($_SESSION['user_id'] ?? 0);
        $scope    = $_GET['scope'] ?? 'all';

        $idProfil = null;
        if ($scope === 'single') {
            if (!empty($_GET['pid'])) {
                $idProfil = (int) $_GET['pid'];
            } elseif (!empty($_GET['uid'])) {
                // resolve user_id -> id_profil
                $stmt = (new Database())->getConnection()->prepare(
                    "SELECT id_profil FROM profile WHERE id_user = :uid"
                );
                $stmt->execute([':uid' => (int) $_GET['uid']]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $idProfil = $row ? (int) $row['id_profil'] : null;
            }
        }

        $result = $model->exportAndDelete($idProfil, $adminId);

        if (!empty($result['error'])) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $result['error']]);
            exit;
        }

        // Stream ZIP ke browser
        $zipPath  = $result['zip_path'];
        $filename = $result['filename'];

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($zipPath));
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($zipPath);
        @unlink($zipPath); // hapus file tmp setelah dikirim
        exit;
    }

    /**
     * [BARU - Tahap 30] Aktifkan / Nonaktifkan akun user.
     * Jika dinonaktifkan, data presensi+logbook di-archive (ZIP) + dihapus.
     *
     * POST body: { user_id: N, status: 'ACTIVE'|'INACTIVE' }
     * Response JSON (untuk fetch AJAX dari JS).
     * Jika INACTIVE dan ZIP dibuat, redirect response menyertakan URL
     * download ZIP (?download=...) yang kemudian dibuka JS di tab baru.
     */
    public function toggleUserStatus() {
        $this->checkAccess(['Admin']);

        ob_clean();
        header('Content-Type: application/json');

        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $userId  = (int) ($body['user_id'] ?? 0);
        $status  = strtoupper($body['status'] ?? '');
        $adminId = (int) ($_SESSION['user_id'] ?? 0);

        if (!$userId || !in_array($status, ['ACTIVE', 'INACTIVE'])) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid.']);
            exit;
        }

        // [BARU – Tahap 33] Hanya role User (Asisten) yang bisa dinonaktifkan
        $checkStmt = (new Database())->getConnection()->prepare(
            "SELECT role FROM `user` WHERE id_user = :uid"
        );
        $checkStmt->execute([':uid' => $userId]);
        $targetUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if (!$targetUser || $targetUser['role'] !== 'User') {
            echo json_encode(['status' => 'error', 'message' => 'Fitur ini hanya berlaku untuk akun User (Asisten).']);
            exit;
        }

        require_once '../app/models/ResetAttendanceModel.php';
        $model  = new ResetAttendanceModel();
        $result = $model->toggleAccountStatus($userId, $status, $adminId);

        if (!$result['ok']) {
            echo json_encode(['status' => 'error', 'message' => $result['error'] ?? 'Gagal.']);
            exit;
        }

        $response = ['status' => 'success', 'new_status' => $status];

        // Jika nonaktifkan → kirim URL unduh ZIP (file tmp di server)
        if (!empty($result['zip_path'])) {
            // Simpan path ZIP di session agar bisa diunduh lewat endpoint download
            if (session_status() == PHP_SESSION_NONE) session_start();
            $_SESSION['pending_zip'] = [
                'path'     => $result['zip_path'],
                'filename' => $result['filename'],
                'expires'  => time() + 300, // 5 menit
            ];
            $response['download_url'] = BASE_URL . '/admin/downloadPendingZip';
        }

        echo json_encode($response);
        exit;
    }

    /**
     * [BARU - Tahap 30] Stream ZIP yang tersimpan di session (toggle nonaktif).
     * Endpoint sementara - file dihapus setelah dikirim / expired.
     */
    public function downloadPendingZip() {
        $this->checkAccess(['Admin']);

        if (session_status() == PHP_SESSION_NONE) session_start();
        $zip = $_SESSION['pending_zip'] ?? null;

        if (!$zip || time() > $zip['expires'] || !file_exists($zip['path'])) {
            http_response_code(404);
            echo 'File tidak tersedia atau sudah expired.';
            exit;
        }

        ob_clean();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip['filename'] . '"');
        header('Content-Length: ' . filesize($zip['path']));
        readfile($zip['path']);
        @unlink($zip['path']);
        unset($_SESSION['pending_zip']);
        exit;
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
        $this->view('layout/footer', $data);
    }

    public function exportCsv() {
        $this->checkAccess(['Admin']);
        
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        $assistantId = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : null;

        $data = $this->model('AttendanceModel')->getAttendanceSummary($startDate, $endDate, $assistantId);

        $filename = "Rekap_Presensi_" . date('d-m-Y', strtotime($startDate)) . "_sd_" . date('d-m-Y', strtotime($endDate)) . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        // Kirim UTF-8 BOM agar Excel dapat mendeteksi encoding dan memisahkan kolom dengan benar
        fwrite($output, "\xEF\xBB\xBF");
        
        fputcsv($output, ['No', 'Nama Asisten', 'NIM', 'Jabatan', 'Masuk', 'Pulang', 'Hadir', 'Izin', 'Alpa', 'Total Kehadiran'], ';', '"', '');

        $no = 1;
        foreach ($data as $row) {
            fputcsv($output, [
                $no++,
                $row['name'],
                $row['nim'] ?? '-',
                $row['position'] ?? 'Asisten',
                $row['total_masuk'],
                $row['total_pulang'],
                $row['total_hadir'] . " Hari",
                $row['total_izin'] . " Hari",
                $row['total_alpa'] . " Hari",
                $row['total_hadir'] . " Hari"
            ], ';', '"', '');
        }
        fclose($output);
        exit;
    }

    public function exportPdf() {
        $this->checkAccess(['Admin']);
        
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        $assistantId = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : null;
        
        $attModel = $this->model('AttendanceModel');
        
        // Menggunakan metode baru untuk mendapatkan data rekapitulasi
        $data['summary_data'] = $attModel->getAttendanceSummary($startDate, $endDate, $assistantId);
        
        $data['start_date'] = $startDate;
        $data['end_date'] = $endDate;
        
        // Menentukan nama untuk header laporan
        $data['report_title_name'] = 'Semua Asisten';
        if($assistantId) {
            $user = $this->model('UserModel')->getUserById($assistantId);
            $data['report_title_name'] = $user['name'] ?? 'Asisten';
        }
        
        $data['css'] = 'admin/pdf_attendance.css';

        $this->view('admin/pdf_attendance', $data);
    }

    public function exportScheduleCsv() {
        $this->checkAccess(['Admin']);
        
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : '';
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : '';
        $assistantId = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : '';
        $scheduleType = !empty($_GET['schedule_type']) ? $_GET['schedule_type'] : '';
        $sortBy = !empty($_GET['sort_by']) ? $_GET['sort_by'] : 'hari_waktu';

        $schModel = $this->model('ScheduleModel');
        $schedules = $schModel->getAllSchedules();
        
        // Filter by Date Range
        if (!empty($startDate) && !empty($endDate)) {
            $schedules = array_filter($schedules, function($row) use ($startDate, $endDate) {
                $evtStart = $row['start_date'];
                $evtEnd = !empty($row['end_date']) ? $row['end_date'] : $evtStart;
                return ($evtStart <= $endDate && $evtEnd >= $startDate);
            });
        }

        // Filter by Assistant
        if (!empty($assistantId) && $assistantId !== 'all') {
            $schedules = array_filter($schedules, function($row) use ($assistantId) {
                return strtolower($row['type'] ?? '') === 'umum' || (isset($row['id_profil']) && strval($row['id_profil']) === strval($assistantId));
            });
        }

        // Filter by Schedule Type
        if (!empty($scheduleType)) {
            $schedules = array_filter($schedules, function($row) use ($scheduleType) {
                return strtolower($row['type'] ?? '') === strtolower($scheduleType);
            });
        }

        // Sort schedules by Day, Time, Assistant Name, and Type
        usort($schedules, function($a, $b) {
            $dayA = intval($a['day_of_week'] ?? 0);
            $dayB = intval($b['day_of_week'] ?? 0);
            if ($dayA !== $dayB) return $dayA - $dayB;
            
            $timeA = $a['start_time'] ?? '00:00:00';
            $timeB = $b['start_time'] ?? '00:00:00';
            $cmpTime = strcasecmp($timeA, $timeB);
            if ($cmpTime !== 0) return $cmpTime;

            $nameA = $a['user_name'] ?? '';
            $nameB = $b['user_name'] ?? '';
            $cmpName = strcasecmp($nameA, $nameB);
            if ($cmpName !== 0) return $cmpName;

            $typeA = strtolower($a['type'] ?? '');
            $typeB = strtolower($b['type'] ?? '');
            return strcasecmp($typeA, $typeB);
        });

        $filename = "Jadwal_Asisten_Lab_" . date('d-m-Y') . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        
        fputcsv($output, ['No', 'Jenis Jadwal', 'Nama Asisten / PIC', 'Kegiatan / Mata Kuliah', 'Kelas', 'Dosen Pengampu', 'Hari', 'Waktu', 'Lokasi'], ';', '"', '');
        
        $dayMap = [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 
            5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
        ];
        
        $no = 1;
        foreach ($schedules as $row) {
            $typeFmt = match(strtolower($row['type'] ?? '')) {
                'umum' => 'Umum (Lab)',
                'asisten' => 'Asisten Lab',
                'piket' => 'Piket',
                'kuliah' => 'Kuliah Asisten',
                default => ucfirst($row['type'] ?? '')
            };
            
            $dayNum = intval($row['day_of_week'] ?? 0);
            $dayName = $dayMap[$dayNum] ?? '-';
            
            $timeStr = '-';
            if (!empty($row['start_time']) && !empty($row['end_time'])) {
                $timeStr = date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time'])) . ' WITA';
            }
            
            fputcsv($output, [
                $no++,
                $typeFmt,
                $row['user_name'] ?? 'Laboratorium',
                $row['title'] ?? '-',
                $row['kelas'] ?? '-',
                $row['dosen'] ?? '-',
                $dayName,
                $timeStr,
                $row['location'] ?? 'Lab'
            ], ';', '"', '');
        }
        fclose($output);
        exit;
    }

    public function exportSchedulePdf() {
        $this->checkAccess(['Admin']);
        
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : '';
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : '';
        $assistantId = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : '';
        $scheduleType = !empty($_GET['schedule_type']) ? $_GET['schedule_type'] : '';
        $sortBy = !empty($_GET['sort_by']) ? $_GET['sort_by'] : 'hari_waktu';

        $schModel = $this->model('ScheduleModel');
        $schedules = $schModel->getAllSchedules();
        
        // Filter by Date Range
        if (!empty($startDate) && !empty($endDate)) {
            $schedules = array_filter($schedules, function($row) use ($startDate, $endDate) {
                $evtStart = $row['start_date'];
                $evtEnd = !empty($row['end_date']) ? $row['end_date'] : $evtStart;
                return ($evtStart <= $endDate && $evtEnd >= $startDate);
            });
        }

        // Filter by Assistant
        if (!empty($assistantId) && $assistantId !== 'all') {
            $schedules = array_filter($schedules, function($row) use ($assistantId) {
                return strtolower($row['type'] ?? '') === 'umum' || (isset($row['id_profil']) && strval($row['id_profil']) === strval($assistantId));
            });
        }

        // Filter by Schedule Type
        if (!empty($scheduleType)) {
            $schedules = array_filter($schedules, function($row) use ($scheduleType) {
                return strtolower($row['type'] ?? '') === strtolower($scheduleType);
            });
        }

        // Sort schedules by Day, Time, Assistant Name, and Type
        usort($schedules, function($a, $b) {
            $dayA = intval($a['day_of_week'] ?? 0);
            $dayB = intval($b['day_of_week'] ?? 0);
            if ($dayA !== $dayB) return $dayA - $dayB;
            
            $timeA = $a['start_time'] ?? '00:00:00';
            $timeB = $b['start_time'] ?? '00:00:00';
            $cmpTime = strcasecmp($timeA, $timeB);
            if ($cmpTime !== 0) return $cmpTime;

            $nameA = $a['user_name'] ?? '';
            $nameB = $b['user_name'] ?? '';
            $cmpName = strcasecmp($nameA, $nameB);
            if ($cmpName !== 0) return $cmpName;

            $typeA = strtolower($a['type'] ?? '');
            $typeB = strtolower($b['type'] ?? '');
            return strcasecmp($typeA, $typeB);
        });

        $data['schedules'] = $schedules;
        $data['start_date'] = $startDate;
        $data['end_date'] = $endDate;
        $data['selected_assistant'] = $assistantId;
        $data['schedule_type'] = $scheduleType;
        $data['sort_by'] = $sortBy;
        $data['report_title_name'] = 'Semua Asisten';
        if (!empty($assistantId) && $assistantId !== 'all') {
            $assistants = $this->model('UserModel')->getAssistantsWithProfile();
            foreach ($assistants as $ast) {
                if (strval($ast['id_profil']) === strval($assistantId)) {
                    $data['report_title_name'] = $ast['name'];
                    break;
                }
            }
        }
        
        $this->view('admin/pdf_schedule', $data);
    }

    public function schedule() {
        $this->checkAccess(['Admin']);
        $data['judul'] = 'Kelola Jadwal';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $allUsers = $this->model('UserModel')->getAllUsers();
        $assistants = array_filter($allUsers, fn($u) => $u['role'] == 'User');
        usort($assistants, fn($a, $b) => strcasecmp($a['nama'] ?? $a['name'] ?? '', $b['nama'] ?? $b['name'] ?? ''));
        $data['assistants'] = $assistants;

        // [BARU - Modul Dosen] daftar dosen master untuk dropdown "Dosen Pengampu"
        $data['dosen_list'] = $this->model('ScheduleModel')->getAllDosen();

        // [BARU - Modul Lab] daftar laboratorium master untuk dropdown "Lokasi/Ruangan"
        $data['lab_list'] = $this->model('ScheduleModel')->getAllLabs();
        
        // Filters
        $data['start_date'] = !empty($_GET['start_date']) ? $_GET['start_date'] : '';
        $data['end_date'] = !empty($_GET['end_date']) ? $_GET['end_date'] : '';
        $data['assistant_id'] = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : '';
        $data['schedule_type'] = !empty($_GET['schedule_type']) ? $_GET['schedule_type'] : '';
        $data['sort_by'] = !empty($_GET['sort_by']) ? $_GET['sort_by'] : 'hari_waktu';

        $schedules = $this->model('ScheduleModel')->getAllSchedules();

        // Filter by Date Range
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $schedules = array_filter($schedules, function($row) use ($data) {
                $evtStart = $row['start_date'];
                $evtEnd = !empty($row['end_date']) ? $row['end_date'] : $evtStart;
                return ($evtStart <= $data['end_date'] && $evtEnd >= $data['start_date']);
            });
        }

        // Filter by Assistant
        if (!empty($data['assistant_id'])) {
            $schedules = array_filter($schedules, function($row) use ($data) {
                return strtolower($row['type'] ?? '') === 'umum' || (isset($row['id_profil']) && strval($row['id_profil']) === strval($data['assistant_id']));
            });
        }

        // Filter by Schedule Type
        if (!empty($data['schedule_type'])) {
            $schedules = array_filter($schedules, function($row) use ($data) {
                return strtolower($row['type'] ?? '') === strtolower($data['schedule_type']);
            });
        }

        // Sort schedules
        usort($schedules, function($a, $b) use ($data) {
            if ($data['sort_by'] === 'nama') {
                $cmp = strcasecmp($a['user_name'] ?? '', $b['user_name'] ?? '');
                if ($cmp !== 0) return $cmp;
            } elseif ($data['sort_by'] === 'jenis') {
                $typeA = strtolower($a['type'] ?? '');
                $typeB = strtolower($b['type'] ?? '');
                $cmp = strcasecmp($typeA, $typeB);
                if ($cmp !== 0) return $cmp;
            }
            $dayA = intval($a['day_of_week'] ?? 0);
            $dayB = intval($b['day_of_week'] ?? 0);
            if ($dayA !== $dayB) return $dayA - $dayB;
            
            $timeA = $a['start_time'] ?? '00:00:00';
            $timeB = $b['start_time'] ?? '00:00:00';
            return strcasecmp($timeA, $timeB);
        });

        $data['raw_schedules'] = $schedules; 

        $data['css'] = 'admin/schedule.css';
        $data['js'] = 'admin/schedule.js';

        $data['js_config'] = [
            'baseUrl'     => BASE_URL,
            'rawEvents'   => $data['raw_schedules'],
            'initialDate' => !empty($data['start_date']) ? $data['start_date'] : date('Y-m-d')
        ];

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/schedule', $data);
        $this->view('layout/footer', $data);
    }

    public function getFilteredSchedulesJson() {
        $this->checkAccess(['Admin']);
        
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : '';
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : '';
        $assistantId = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : '';
        $scheduleType = !empty($_GET['schedule_type']) ? $_GET['schedule_type'] : '';
        $sortBy = !empty($_GET['sort_by']) ? $_GET['sort_by'] : 'hari_waktu';

        $schModel = $this->model('ScheduleModel');
        $schedules = $schModel->getAllSchedules();

        // Filter by Date Range
        if (!empty($startDate) && !empty($endDate)) {
            $schedules = array_filter($schedules, function($row) use ($startDate, $endDate) {
                $evtStart = $row['start_date'];
                $evtEnd = !empty($row['end_date']) ? $row['end_date'] : $evtStart;
                return ($evtStart <= $endDate && $evtEnd >= $startDate);
            });
        }

        // Filter by Assistant
        if (!empty($assistantId) && $assistantId !== 'all') {
            $schedules = array_filter($schedules, function($row) use ($assistantId) {
                return strtolower($row['type'] ?? '') === 'umum' || (isset($row['id_profil']) && strval($row['id_profil']) === strval($assistantId));
            });
        }

        // Filter by Schedule Type
        if (!empty($scheduleType)) {
            $schedules = array_filter($schedules, function($row) use ($scheduleType) {
                return strtolower($row['type'] ?? '') === strtolower($scheduleType);
            });
        }

        // Sort schedules by Day, Time, Assistant Name, and Type
        usort($schedules, function($a, $b) {
            $dayA = intval($a['day_of_week'] ?? 0);
            $dayB = intval($b['day_of_week'] ?? 0);
            if ($dayA !== $dayB) return $dayA - $dayB;
            
            $timeA = $a['start_time'] ?? '00:00:00';
            $timeB = $b['start_time'] ?? '00:00:00';
            $cmpTime = strcasecmp($timeA, $timeB);
            if ($cmpTime !== 0) return $cmpTime;

            $nameA = $a['user_name'] ?? '';
            $nameB = $b['user_name'] ?? '';
            $cmpName = strcasecmp($nameA, $nameB);
            if ($cmpName !== 0) return $cmpName;

            $typeA = strtolower($a['type'] ?? '');
            $typeB = strtolower($b['type'] ?? '');
            return strcasecmp($typeA, $typeB);
        });

        $schedules = array_values($schedules);

        header('Content-Type: application/json');
        echo json_encode($schedules);
        exit;
    }


    public function addSchedule() {
        $this->checkAccess(['Admin']);
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $type = $_POST['type']; 
            $userId = ($type == 'umum') ? NULL : ($_POST['user_id'] ?? null);
            $data = [
                'type' => $type, 'user_id' => $userId,
                // Dipakai ScheduleModel untuk sinkronisasi Google Calendar jadwal
                // umum (event dibuat di akun Google Admin yang membuat jadwal).
                'creator_user_id' => $_SESSION['user_id'],
                'title' => $_POST['title'], 'location' => $_POST['location'] ?? 'Lab',
                'dosen' => $_POST['dosen'] ?? null, 'id_dosen' => $_POST['id_dosen'] ?? null, 'dosen_baru' => $_POST['dosen_baru'] ?? null, 'kelas' => $_POST['kelas'] ?? null, 
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

            // [BARU] Jadwal Kuliah adalah wewenang penuh Asisten - Admin
            // hanya boleh melihat, tidak boleh mengedit (jaga-jaga di luar
            // tombol UI yang sudah disembunyikan).
            if ($type == 'kuliah') {
                $_SESSION['flash'] = ['type' => 'error', 'title' => 'Ditolak', 'message' => 'Jadwal Kuliah hanya dapat diubah oleh Asisten bersangkutan.'];
                header("Location: " . BASE_URL . "/admin/schedule"); exit;
            }

            $userId = ($type == 'umum') ? NULL : ($_POST['user_id'] ?? null);
            $data = [
                'id' => $_POST['id_schedule'], 'type' => $type, 'user_id' => $userId,
                // Dipakai ScheduleModel untuk sinkronisasi Google Calendar jadwal
                // umum (event diupdate di akun Google Admin yang membuat jadwal).
                'creator_user_id' => $_SESSION['user_id'],
                'title' => $_POST['title'], 'location' => $_POST['location'] ?? 'Lab',
                'dosen' => $_POST['dosen'] ?? null, 'id_dosen' => $_POST['id_dosen'] ?? null, 'dosen_baru' => $_POST['dosen_baru'] ?? null, 'kelas' => $_POST['kelas'] ?? null, 
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
            // [BARU] Jadwal Kuliah adalah wewenang penuh Asisten - Admin
            // tidak boleh menghapus (jaga-jaga di luar tombol UI yang sudah
            // disembunyikan).
            if ($_GET['type'] == 'kuliah') {
                $_SESSION['flash'] = ['type' => 'error', 'title' => 'Ditolak', 'message' => 'Jadwal Kuliah hanya dapat dihapus oleh Asisten bersangkutan.'];
                header("Location: " . BASE_URL . "/admin/schedule"); exit;
            }

            if ($this->model('ScheduleModel')->deleteSchedule($_GET['id'], $_GET['type'], $_SESSION['user_id'])) {
                $_SESSION['flash'] = ['type' => 'success', 'title' => 'Terhapus', 'message' => 'Jadwal berhasil dihapus.'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Gagal menghapus.'];
            }
            header("Location: " . BASE_URL . "/admin/schedule");
            exit;
        }
    }

    // [BARU - Modul 5 V3] Retry sinkronisasi Google Calendar untuk item
    // jadwal yang sync_status-nya 'failed'. AJAX endpoint, dipanggil dari
    // modal edit jadwal (admin/schedule.js).
    public function retrySync() {
        $this->checkAccess(['Admin']);

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.']);
            exit;
        }

        $id = $_POST['id'] ?? null;
        $type = $_POST['type'] ?? null;

        if (empty($id) || !ctype_digit((string) $id) || empty($type)) {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak valid.']);
            exit;
        }

        $success = $this->model('ScheduleModel')->retrySchedule($id, $type, $_SESSION['user_id']);

        if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'Sinkronisasi ke Google Calendar berhasil.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Sinkronisasi masih gagal. Pastikan akun Google terhubung, lalu coba lagi.']);
        }
        exit;
    }

    public function logbook() {
        $this->checkAccess(['Admin']);
        $data['judul'] = 'Monitoring Logbook';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $allUsers = $this->model('UserModel')->getAllUsers();
        $data['assistants'] = array_filter($allUsers, fn($u) => $u['role'] == 'User');

        $data['css'] = 'admin/logbook.css';
        $data['js'] = 'admin/logbook.js';

        $data['js_config'] = [
            'baseUrl' => BASE_URL
        ];

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/logbook', $data);
        $this->view('layout/footer', $data);
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
            ob_clean();
            header('Content-Type: application/json');

            $idRef = $_POST['id_ref']; 
            $type = $_POST['type'];    
            $mode = $_POST['mode'];    

            if ($this->model('LogbookModel')->resetLogAdmin($idRef, $type, $mode)) {
                echo json_encode(['status' => 'success', 'message' => 'Data berhasil direset/dihapus.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data. Cek integritas database.']);
            }
            exit;
        }
    }

    public function deleteLogbook() {
        $this->checkAccess(['Admin']);
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            ob_clean();
            header('Content-Type: application/json');
            
            $id = $_POST['id'];
            if ($this->model('LogbookModel')->deleteLogAdmin($id)) {
                echo json_encode(['status'=>'success']); 
            } else {
                echo json_encode(['status'=>'error', 'message' => 'Gagal menghapus item logbook.']);
            }
            exit;
        }
    }

    public function saveLogbookAdmin() {
        $this->checkAccess(['Admin']);
        
        $fileName = null;
        if (isset($_FILES['proof_file']['name']) && $_FILES['proof_file']['name'] != "") {
            $status = $_POST['status'];
            $folder = ($status == 'Hadir') ? 'attendance' : 'leaves';
            $targetDir = UPLOAD_PATH . "$folder/";
            
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
    
    public function profile() {
        $this->checkAccess(['Admin']);        

        $data['judul'] = 'Profil Admin';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);

        $userModel = $this->model('UserModel');
        $attModel  = $this->model('AttendanceModel');
        $schModel  = $this->model('ScheduleModel');

        $data['is_google_connected'] = $userModel->isGoogleConnected($_SESSION['user_id']);
        $data['google_configured'] = (new GoogleClient())->isConfigured();
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
        $this->checkAccess(['Admin']);
        $data['judul'] = 'Edit Profil Admin';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);

        // [PERBAIKAN] common/edit_profile.php membutuhkan $role/$isUser/$isAdmin
        // (dipakai untuk link kembali, banner "edit sekali", dan penyaringan
        // opsi Jabatan/field per-role). Sebelumnya tidak pernah di-set di sini,
        // sehingga semua var tersebut undefined saat Admin membuka /admin/editProfile
        // — banner "SATU KALI" salah tampil ke Admin & opsi Jabatan jatuh ke
        // default Asisten.
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

        $this->view('layout/header', $data); $this->view('layout/sidebar', $data); 
        $this->view('common/edit_profile', $data); 
        $this->view('layout/footer', $data);
    }
    
    public function updateProfile() {
        $this->checkAccess(['Admin']);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            ob_clean(); header('Content-Type: application/json');

            $userModel = $this->model('UserModel');
            $currentUser = $userModel->getUserById($_SESSION['user_id']);

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
            } 
            elseif (isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != "") {
                $fileExt = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
                if (!in_array($fileExt, $allowedExtensions)) {
                    echo json_encode(['status' => 'error', 'title' => 'Format Tidak Didukung', 'message' => 'Foto harus berformat JPG, JPEG, atau PNG.']);
                    exit;
                }
                if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                $fileName = time() . '_' . uniqid() . '.' . $fileExt;
                if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetDir . $fileName)) {
                    $photoName = $fileName;
                    $_SESSION['photo'] = $fileName;
                    if ($currentUser['photo_profile'] && $currentUser['photo_profile'] != 'default.jpg' && file_exists($targetDir . $currentUser['photo_profile'])) {
                        unlink($targetDir . $currentUser['photo_profile']);
                    }
                }
            }

            $isCompleted = 0;
            if (!empty($_POST['name']) && !empty($_POST['phone']) && !empty($_POST['address'])) {
                $isCompleted = 1;
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

            // [PERBAIKAN] Per spesifikasi, Admin "Tanpa data laboratorium" — field
            // Laboratorium dihapus dari common/edit_profile.php untuk role Admin.
            // Pertahankan id_lab lama (biasanya NULL untuk Admin) agar tidak
            // tertimpa NULL hanya karena form tidak lagi mengirim 'lab_id'.
            $labId = $currentUser['id_lab'] ?? null;

            $data = [
                'id'       => $_SESSION['user_id'],
                'role'     => 'Admin',
                'name'     => $_POST['name'],
                'email'    => $newEmail,
                'password' => $newPassword,
                'nim'      => null,
                // [PERBAIKAN] Fallback 'Administrator' bukan nilai ENUM jabatan yang
                // valid (menyebabkan jabatan tersimpan kosong). Jabatan kini wajib
                // diisi via form (opsi tersaring di common/edit_profile.php), tapi
                // fallback diarahkan ke 'Laboran' yang valid jika tetap kosong.
                'position' => !empty($_POST['position']) ? $_POST['position'] : 'Laboran',
                'prodi'    => null,
                'phone'    => $_POST['phone'],
                'address'  => $_POST['address'],
                'gender'   => $_POST['gender'],
                'interest' => null,
                'lab_id'   => $labId,
                'photo'    => ($photoName != $currentUser['photo_profile']) ? $photoName : null,
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
        
        $rawSchedules = $this->model('ScheduleModel')->getAllUserSchedules($id);
        $finalSchedules = [];
        $now = time(); 

        $dayMap = [
            'Monday'=>1, 'Tuesday'=>2, 'Wednesday'=>3, 'Thursday'=>4, 'Friday'=>5, 'Saturday'=>6, 'Sunday'=>7,
            'Senin'=>1, 'Selasa'=>2, 'Rabu'=>3, 'Kamis'=>4, 'Jumat'=>5, 'Sabtu'=>6, 'Minggu'=>7,
            1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6, 7=>7
        ];

        foreach ($rawSchedules as $sch) {
            $isWeekly = (isset($sch['model_perulangan']) && strtolower($sch['model_perulangan']) == 'mingguan');
            $startTime = $sch['start_time'];
            $endTime = $sch['end_time'] ?: '23:59:00';
            $targetDate = '';

            if ($isWeekly) {
                $scheduleDayNum = isset($dayMap[$sch['day_of_week']]) ? $dayMap[$sch['day_of_week']] : 0;
                
                if ($scheduleDayNum > 0) {
                    $currentDayNum = date('N');
                    $diff = $scheduleDayNum - $currentDayNum;
                    
                    $isTodayButPassed = ($diff == 0 && strtotime(date('Y-m-d') . ' ' . $endTime) < $now);

                    if ($diff < 0 || $isTodayButPassed) {
                        $diff += 7; 
                    }
                    
                    $targetDate = date('Y-m-d', strtotime("+$diff days"));
                } else {
                    continue; 
                }
            } else {
                $targetDate = $sch['start_date'];
            }

            $scheduleEndTimestamp = strtotime("$targetDate $endTime");
            $scheduleStartTimestamp = strtotime("$targetDate $startTime");

            if ($scheduleEndTimestamp < $now) {
                continue;
            }

            $sch['sort_timestamp'] = $scheduleStartTimestamp;
            $sch['start_date'] = $targetDate;
            
            $finalSchedules[] = $sch;
        }

        usort($finalSchedules, function($a, $b) {
            return $a['sort_timestamp'] - $b['sort_timestamp'];
        });

        $limit = 6; 
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $totalData = count($finalSchedules);
        $totalPages = ceil($totalData / $limit);

        if ($page < 1) $page = 1;
        if ($page > $totalPages && $totalPages > 0) $page = $totalPages;

        $offset = ($page - 1) * $limit;
        $data['schedules'] = array_slice($finalSchedules, $offset, $limit);

        $data['pagination'] = [
            'current' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalData
        ];

        $data['css'] = 'admin/assistant_schedule.css';

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('admin/assistant_schedule', $data); 
        $this->view('layout/footer', $data);
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

    public function izin() {
        $this->checkAccess(['Admin']);
        
        // Handle approve/reject
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['id_izin'])) {
            $action = $_POST['action'] ?? null;
            $id_izin = $_POST['id_izin'] ?? null;

            if ($action && $id_izin) {
                $izinModel = $this->model('IzinModel');
                
                if ($action === 'approve') {
                    $izinModel->approve($id_izin);
                } elseif ($action === 'reject') {
                    $izinModel->reject($id_izin);
                }

                // Redirect back dengan status
                header('Location: ' . BASE_URL . '/admin/izin?status=success');
                exit();
            }
        }

        // Load data
        $izinModel = $this->model('IzinModel');
        $status = $_POST['status'] ?? $_GET['status'] ?? 'Pending';
        $page = $_POST['page'] ?? $_GET['page'] ?? 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $data['judul'] = 'Manajemen Izin';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $data['izins'] = $izinModel->getAll($status, null, $limit, $offset);
        $data['total'] = $izinModel->getCount($status, null);
        $data['total_pages'] = ceil($data['total'] / $limit);
        $data['current_page'] = $page;
        $data['current_status'] = $status;
        $data['current_tipe'] = null;

        // Check if it's an AJAX request
        $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
                  || (isset($_POST['status']) && !isset($_POST['action']));

        if ($is_ajax) {
            // Return only the table HTML for AJAX
            $data['css'] = 'admin/izin.css';
            
            $this->view('admin/izin', $data);
        } else {
            // Return full page for normal request
            $data['css'] = 'admin/izin.css';
            $data['js'] = 'admin/izin.js';

            $data['js_config'] = [
                'BASE_URL' => BASE_URL
            ];

            $this->view('layout/header', $data);
            $this->view('layout/sidebar', $data);
            $this->view('admin/izin', $data);
            $this->view('layout/footer', $data);
        }
    }
}
?>