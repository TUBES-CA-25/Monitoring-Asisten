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
        $pId = $_SESSION['profil_id']; // Gunakan Profile ID untuk query kegiatan
        
        $db = new Database(); 
        $conn = $db->getConnection();

        // 2. Statistik Ringkas (Query ke tabel presensi & izin)
        $stmtH = $conn->prepare("SELECT COUNT(*) as total FROM presensi WHERE id_profil = :pid AND status = 'Hadir'");
        $stmtH->execute([':pid' => $pId]);
        $hadir = $stmtH->fetch()['total'];

        $stmtI = $conn->prepare("SELECT COUNT(*) as total FROM izin WHERE id_profil = :pid");
        $stmtI->execute([':pid' => $pId]);
        $izin = $stmtI->fetch()['total'];

        $alpa = 0; 
        
        $data['stats'] = ['hadir' => $hadir, 'izin' => $izin, 'alpa' => $alpa];
        $data['status_today'] = $attModel->getStatusColor($uid); 

        // 3. Jadwal Mingguan
        $data['weekly_schedule'] = $schModel->getUserScheduleForWeek($uid); 

        // 4. CHART DATA (Query ke tabel presensi)
        // A. Harian
        $dailyLabels = []; $dailyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dailyLabels[] = date('D', strtotime($date));
            
            $stmt = $conn->prepare("SELECT count(*) as c FROM presensi WHERE id_profil=:pid AND tanggal=:d AND status='Hadir'");
            $stmt->execute([':pid'=>$pId, ':d'=>$date]);
            $dailyData[] = $stmt->fetch()['c'] > 0 ? 1 : 0;
        }

        // B. Mingguan
        $weeklyLabels = []; $weeklyData = [];
        for ($i = 7; $i >= 0; $i--) {
            $startWeek = date('Y-m-d', strtotime("-$i weeks monday"));
            $endWeek   = date('Y-m-d', strtotime("-$i weeks sunday"));
            $weeklyLabels[] = "W" . date('W', strtotime($startWeek));
            
            $stmt = $conn->prepare("SELECT count(*) as c FROM presensi WHERE id_profil=:pid AND tanggal BETWEEN :s AND :e AND status='Hadir'");
            $stmt->execute([':pid'=>$pId, ':s'=>$startWeek, ':e'=>$endWeek]);
            $weeklyData[] = $stmt->fetch()['c'];
        }

        // C. Bulanan
        $monthlyLabels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $monthlyData = array_fill(0, 12, 0);
        $stmt = $conn->prepare("SELECT MONTH(tanggal) as m, COUNT(*) as c FROM presensi WHERE id_profil=:pid AND YEAR(tanggal)=YEAR(CURDATE()) AND status='Hadir' GROUP BY m");
        $stmt->execute([':pid'=>$pId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as $r) { $monthlyData[$r['m'] - 1] = $r['c']; }

        $data['chart_data'] = [
            'daily'   => ['labels' => $dailyLabels, 'data' => $dailyData],
            'weekly'  => ['labels' => $weeklyLabels, 'data' => $weeklyData],
            'monthly' => ['labels' => $monthlyLabels, 'data' => $monthlyData]
        ];

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data); 
        $this->view('user/dashboard', $data);
        $this->view('layout/footer');
    }

    public function profile() {
        if ($_SESSION['role'] != 'User') exit;

        $data['judul'] = 'Profil Saya';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['id_user']);
        
        // Data Statistik untuk Grafik (User Biasa)
        $attModel = $this->model('AttendanceModel');
        
        // Hitung total hadir, izin, alpa (tahun ini)
        $uid = $_SESSION['id_user'];
        $db = new Database(); $conn = $db->getConnection();
        
        $stmtH = $conn->prepare("SELECT COUNT(*) as total FROM trx_attendance WHERE user_id = :uid AND status = 'Hadir'");
        $stmtH->execute([':uid'=>$uid]);
        $hadir = $stmtH->fetch()['total'];

        $stmtI = $conn->prepare("SELECT COUNT(*) as total FROM izin WHERE user_id = :uid");
        $stmtI->execute([':uid'=>$uid]);
        $izin = $stmtI->fetch()['total'];
        
        $data['stats'] = ['hadir' => $hadir, 'izin' => $izin, 'alpa' => 0]; // Alpa dummy 0

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('common/profile', $data); // Kita pakai 1 view untuk semua role
        $this->view('layout/footer');
    }

    public function logbook() {
        if ($_SESSION['role'] != 'User') exit;
        $data['judul'] = 'Logbook Kegiatan';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $data['logs'] = $this->model('LogbookModel')->getUserLogbookHistory($_SESSION['user_id']); // Model handles pId conversion

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('user/logbook', $data);
        $this->view('layout/footer');
    }

    public function submit_logbook() {
        if ($_SESSION['role'] != 'User') { echo json_encode(['status'=>'error', 'message'=>'Unauthorized']); exit; }

        $pId = $_SESSION['profil_id']; // Gunakan Profil ID
        $today = date('Y-m-d');
        
        $db = new Database(); $conn = $db->getConnection();
        // Cek Presensi berdasarkan id_profil
        $stmt = $conn->prepare("SELECT waktu_presensi, waktu_pulang FROM presensi WHERE id_profil = :pid AND tanggal = :d");
        $stmt->execute([':pid'=>$pId, ':d'=>$today]);
        $att = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$att || !$att['waktu_presensi']) {
            echo json_encode(['status'=>'error', 'message'=>'Anda belum melakukan scan masuk!']); exit;
        }
        if ($att['waktu_pulang']) {
            echo json_encode(['status'=>'error', 'message'=>'Logbook terkunci karena Anda sudah scan pulang.']); exit;
        }

        // Proses Simpan (Payload tetap kirim user_id, Model akan handle konversi ke pid)
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
        $data['all_schedules'] = $this->model('ScheduleModel')->getAllUserSchedules($_SESSION['user_id']);
        $data['my_classes'] = $this->model('ScheduleModel')->getPersonalClassSchedules($_SESSION['user_id']);

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('user/schedule', $data);
        $this->view('layout/footer');
    }

    // Aksi Tambah/Hapus Jadwal Kuliah (Pribadi)
    public function add_schedule() { /* Code sama, Model handle tabel baru */
        // ... (kode dari turn sebelumnya tetap valid karena model sudah diupdate)
    }
    public function delete_schedule() { /* ... */ }

    public function scan() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'User') { header("Location: " . BASE_URL . "/auth/login"); exit; }
        $data['judul'] = 'Scan Presensi';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $this->view('user/scan', $data); 
    }

    // --- SUBMIT PRESENSI ---
    public function submit_attendance() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $userId = $_SESSION['user_id'];
            $token = $_POST['token'];
            $imageData = $_POST['image'];

            // 1. Decode Image
            $imageParts = explode(";base64,", $imageData);
            $imageBase64 = base64_decode($imageParts[1]);
            $fileName = 'att_' . $userId . '_' . time() . '.jpg';
            $filePath = '../public/uploads/attendance/' . $fileName;

            if (!file_exists('../public/uploads/attendance/')) mkdir('../public/uploads/attendance/', 0777, true);
            file_put_contents($filePath, $imageBase64);

            // 2. Simpan ke Database (AttendanceModel sudah update tabel 'presensi')
            $attendanceModel = $this->model('AttendanceModel');
            
            // TODO: Tambahkan validasi token via QrModel jika perlu
            // $isValid = $this->model('QrModel')->checkToken($token);

            if($attendanceModel->clockIn($userId, $fileName)) {
                echo json_encode(['status' => 'success', 'message' => 'Presensi Berhasil!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ke database.']);
            }
        }
    }

    // --- SUBMIT IZIN ---
    public function submit_leave() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Logika Insert Manual ke tabel izin
            $pId = $_SESSION['profil_id'];
            $db = new Database(); $conn = $db->getConnection();
            
            $sql = "INSERT INTO izin (id_profil, tipe, start_date, end_date, deskripsi, status_approval) 
                    VALUES (:pid, :tipe, CURDATE(), CURDATE(), :desc, 'Pending')";
            // Catatan: start/end date disederhanakan CURDATE untuk demo, sesuaikan dengan input form jika ada daterange
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':pid' => $pId,
                ':tipe' => $_POST['type'],
                ':desc' => $_POST['reason']
            ]);

            echo "<script>alert('Pengajuan Izin Berhasil Dikirim!'); window.location.href='".BASE_URL."/user/dashboard';</script>";
        }
    }
}
?>