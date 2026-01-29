<?php
class UserController extends Controller {
    
    public function index() { $this->dashboard(); }

    public function dashboard() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'User') {
            header("Location: " . BASE_URL . "/auth/login"); exit;
        }

        $data['judul'] = 'Dashboard Asisten';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        $attModel = $this->model('AttendanceModel');
        $schModel = $this->model('ScheduleModel');
        
        $uid = $_SESSION['user_id'];
        $pId = $_SESSION['profil_id']; // Gunakan ID Profil
        
        $db = new Database(); 
        $conn = $db->getConnection();

        // 1. STATISTIK (Query ke tabel baru: presensi & izin)
        // Hitung Hadir
        $stmtH = $conn->prepare("SELECT COUNT(*) as total FROM presensi WHERE id_profil = :pid AND status = 'Hadir'");
        $stmtH->execute([':pid' => $pId]);
        $hadir = $stmtH->fetch()['total'];

        // Hitung Izin (Hanya yang Approved)
        $stmtI = $conn->prepare("SELECT COUNT(*) as total FROM izin WHERE id_profil = :pid AND status_approval = 'Approved'");
        $stmtI->execute([':pid' => $pId]);
        $izin = $stmtI->fetch()['total'];

        // Hitung Alpa (Logika sederhana)
        $alpa = 0; 
        
        $data['stats'] = ['hadir' => $hadir, 'izin' => $izin, 'alpa' => $alpa];
        
        // Status Hari Ini (Warna indikator)
        $data['status_today'] = $attModel->getStatusColor($uid); 

        // 2. JADWAL MINGGUAN
        $data['weekly_schedule'] = $schModel->getUserScheduleForWeek($uid); 

        // 3. CHART DATA (Harian - 7 Hari Terakhir)
        $dailyLabels = []; 
        $dailyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dailyLabels[] = date('D', strtotime($date));
            
            // Cek presensi berdasarkan id_profil dan tanggal
            $stmt = $conn->prepare("SELECT count(*) as c FROM presensi WHERE id_profil=:pid AND tanggal=:d AND status='Hadir'");
            $stmt->execute([':pid'=>$pId, ':d'=>$date]);
            $dailyData[] = $stmt->fetch()['c'] > 0 ? 1 : 0;
        }

        $data['chart_data'] = [
            'daily'   => ['labels' => $dailyLabels, 'data' => $dailyData]
        ];

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data); 
        $this->view('user/dashboard', $data);
        $this->view('layout/footer');
    }

    public function profile() {
        if ($_SESSION['role'] != 'User') exit;

        $data['judul'] = 'Profil Saya';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        // Ambil statistik untuk ditampilkan di profil
        $pId = $_SESSION['profil_id'];
        $db = new Database(); $conn = $db->getConnection();
        
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
        if ($role != 'User' && $role != 'Super Admin') exit; // Safety check

        $user = $this->model('UserModel')->getUserById($_SESSION['user_id']);

        // CEK CONSTRAINT: Jika sudah completed, tolak akses.
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
        $this->view('common/edit_profile', $data); // View kita buat di langkah 4
        $this->view('layout/footer');
    }

    public function updateProfile() {
        // Hanya proses jika request POST
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $role = $_SESSION['role'];
            $userModel = $this->model('UserModel');
            
            // Ambil data user saat ini untuk cek status & foto lama
            $currentUser = $userModel->getUserById($_SESSION['user_id']);

            // 1. CEK KUNCI PROFIL (Kecuali Admin)
            // Jika bukan Admin DAN profil sudah completed (1), tolak akses.
            if ($role != 'Admin' && isset($currentUser['is_completed']) && $currentUser['is_completed'] == 1) {
                echo "<script>
                    alert('Profil Anda sudah terkunci. Hubungi Admin untuk perubahan data.'); 
                    window.location.href='" . BASE_URL . "/user/profile';
                </script>";
                exit;
            }

            // 2. VALIDASI DATA WAJIB
            if (empty($_POST['name']) || empty($_POST['nim']) || empty($_POST['position']) || empty($_POST['phone']) || empty($_POST['address'])) {
                echo "<script>alert('Semua data bertanda (*) wajib diisi!'); window.history.back();</script>";
                exit;
            }

            // 3. LOGIKA UPLOAD FOTO (Prioritas: Hasil Crop Base64)
            $photoName = $currentUser['photo_profile']; // Default gunakan foto lama
            $targetDir = "../public/uploads/profile/";
            
            // A. Cek apakah ada data gambar hasil crop (Base64)
            if (!empty($_POST['cropped_image'])) {
                $data = $_POST['cropped_image'];
                
                // Parsing data URI scheme: "data:image/jpeg;base64,..."
                if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
                    $data = substr($data, strpos($data, ',') + 1);
                    $type = strtolower($type[1]); // jpg, png, dll
                    $decodedData = base64_decode($data);

                    if ($decodedData !== false) {
                        // Pastikan folder ada
                        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                        
                        // Generate nama file unik
                        $fileName = time() . '_' . uniqid() . '.' . $type;
                        $filePath = $targetDir . $fileName;
                        
                        // Simpan file ke server
                        if (file_put_contents($filePath, $decodedData)) {
                            $photoName = $fileName;
                            $_SESSION['photo'] = $fileName; // Update session foto
                            
                            // Hapus foto lama jika ada (dan bukan default avatar)
                            if ($currentUser['photo_profile'] && file_exists($targetDir . $currentUser['photo_profile'])) {
                                unlink($targetDir . $currentUser['photo_profile']); 
                            }
                        }
                    }
                }
            } 
            // B. Fallback: Cek Upload File Biasa (Jika JS Cropper gagal)
            elseif (isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != "") {
                if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                
                $fileName = time() . '_' . basename($_FILES["photo"]["name"]);
                $targetFilePath = $targetDir . $fileName;
                $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
                
                // Validasi tipe file
                if (in_array(strtolower($fileType), ['jpg', 'jpeg', 'png'])) {
                    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFilePath)) {
                        $photoName = $fileName;
                        $_SESSION['photo'] = $fileName;
                    }
                }
            }

            // 4. PERSIAPAN DATA UNTUK MODEL
            $data = [
                'id'       => $_SESSION['user_id'],
                'role'     => $role,
                'name'     => $_POST['name'],
                'nim'      => $_POST['nim'],           // Data baru (NIM)
                'position' => $_POST['position'],      // Data baru (Jabatan)
                'phone'    => $_POST['phone'],
                'address'  => $_POST['address'],
                'gender'   => $_POST['gender'],
                'interest' => $_POST['interest'] ?? null,
                'photo'    => ($photoName != $currentUser['photo_profile']) ? $photoName : null
            ];

            // 5. EKSEKUSI UPDATE KE DATABASE
            if ($userModel->updateSelfProfile($data)) {
                // Update Session agar perubahan langsung tampil di Sidebar/Header
                $_SESSION['name'] = $_POST['name'];
                $_SESSION['jabatan'] = $_POST['position']; 
                
                $msg = 'Profil berhasil dilengkapi dan kini DATA DIKUNCI.';
                echo "<script>alert('$msg'); window.location.href='" . BASE_URL . "/user/profile';</script>";
            } else {
                echo "<script>alert('Gagal memperbarui profil. Silakan coba lagi.'); window.history.back();</script>";
            }
        }
    }

    public function logbook() {
        if ($_SESSION['role'] != 'User') exit;
        $data['judul'] = 'Logbook Kegiatan';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        // Model Logbook harus menggunakan id_user/id_profil yang sesuai
        $data['logs'] = $this->model('LogbookModel')->getUserLogbookHistory($_SESSION['user_id']); 

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
        
        // Cek apakah sudah presensi (waktu_presensi) dan belum pulang (waktu_pulang)
        $stmt = $conn->prepare("SELECT waktu_presensi, waktu_pulang FROM presensi WHERE id_profil = :pid AND tanggal = :d");
        $stmt->execute([':pid'=>$pId, ':d'=>$today]);
        $att = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$att || !$att['waktu_presensi']) {
            echo json_encode(['status'=>'error', 'message'=>'Anda belum melakukan scan masuk!']); exit;
        }
        if ($att['waktu_pulang']) {
            echo json_encode(['status'=>'error', 'message'=>'Logbook terkunci karena Anda sudah scan pulang.']); exit;
        }

        // Simpan Logbook
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

    public function schedule() {
        if ($_SESSION['role'] != 'User') exit;
        $data['judul'] = 'Jadwal Saya';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        
        // Ambil semua jadwal (Kuliah + Asisten + Piket)
        $data['all_schedules'] = $this->model('ScheduleModel')->getAllUserSchedules($_SESSION['user_id']);
        
        // Ambil jadwal personal (Kuliah) untuk fitur Edit
        $data['my_classes'] = $this->model('ScheduleModel')->getPersonalClassSchedules($_SESSION['user_id']);

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('user/schedule', $data);
        $this->view('layout/footer');
    }

    public function scan() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'User') { header("Location: " . BASE_URL . "/auth/login"); exit; }
        $data['judul'] = 'Scan Presensi';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $this->view('user/scan', $data); 
    }

    public function submit_attendance() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $userId = $_SESSION['user_id'];
            $imageData = $_POST['image']; // Base64 Image

            // Simpan gambar (Logic sederhana)
            $imageParts = explode(";base64,", $imageData);
            $imageBase64 = base64_decode($imageParts[1]);
            $fileName = 'att_' . $userId . '_' . time() . '.jpg';
            $filePath = '../public/uploads/attendance/' . $fileName;
            
            // Pastikan folder ada
            if (!file_exists('../public/uploads/attendance/')) {
                mkdir('../public/uploads/attendance/', 0777, true);
            }
            file_put_contents($filePath, $imageBase64);

            // Simpan ke DB via Model
            if($this->model('AttendanceModel')->clockIn($userId, $fileName)) {
                echo json_encode(['status' => 'success', 'message' => 'Presensi Berhasil!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ke database.']);
            }
        }
    }

    // 1. Tambah Jadwal Kuliah
    public function addSchedule() {
        if ($_SESSION['role'] != 'User') exit;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'id_profil'   => $_SESSION['profil_id'],
                'title'       => $_POST['course_name'],
                'description' => $_POST['lecturer'] ?? '-',
                'location'    => $_POST['room'] ?? '-',
                'type_repeat' => $_POST['type_repeat'], // 'once' atau 'repeat'
                'date'        => $_POST['date'] ?? null, // Untuk 'once'
                'day'         => $_POST['day'] ?? null,  // Untuk 'repeat' (1-7)
                'start'       => $_POST['start_clock'] . ':00',
                'end'         => $_POST['end_clock'] . ':00'
            ];

            if ($this->model('ScheduleModel')->addKuliah($data)) {
                echo json_encode(['status' => 'success', 'message' => 'Jadwal berhasil ditambahkan']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan jadwal']);
            }
        }
    }

    // 2. Edit Jadwal Kuliah
    public function editSchedule() {
        if ($_SESSION['role'] != 'User') exit;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'id'          => $_POST['id_schedule'],
                'id_profil'   => $_SESSION['profil_id'],
                'title'       => $_POST['course_name'],
                'description' => $_POST['lecturer'],
                'location'    => $_POST['room'],
                'date'        => $_POST['date'],
                'start'       => $_POST['start_clock'],
                'end'         => $_POST['end_clock']
            ];

            if ($this->model('ScheduleModel')->updateKuliah($data)) {
                echo json_encode(['status' => 'success', 'message' => 'Jadwal berhasil diperbarui']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui jadwal']);
            }
        }
    }

    // 3. Hapus Jadwal Kuliah
    public function deleteSchedule() {
        if ($_SESSION['role'] != 'User') exit;

        if (isset($_POST['id'])) {
            $id = $_POST['id'];
            $pId = $_SESSION['profil_id'];

            if ($this->model('ScheduleModel')->deleteKuliah($id, $pId)) {
                echo json_encode(['status' => 'success', 'message' => 'Jadwal dihapus']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus jadwal']);
            }
        }
    }
}
?>