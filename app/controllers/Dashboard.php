<?php
class Dashboard extends Controller {
    public function index() {
    if(!isset($_SESSION['user_id'])) { header('Location: ' . BASEURL . '/auth'); exit; }
    
    $data['judul'] = 'Dashboard';
    $role = $_SESSION['role_id'];
    $db = new Database; // Inisialisasi DB manual untuk query custom cepat

    // --- SUPER ADMIN ---
    if ($role == 1) { 
        $adm = $this->model('Admin_model');
        $data['labs'] = $adm->getLabs();
        $data['assistants'] = $adm->getAllAssistants();
        $data['chart_stats'] = $adm->getChartStats();
        $view = 'dashboard/index_super';

    // --- ADMIN (KOORDINATOR) ---
    } elseif ($role == 2) { 
        // 1. Data Rekap Utama
        $data['rekap'] = $this->model('Presensi_model')->getAllAttendance();
        
        // 2. Statistik Harian (Quick Count)
        $today = date('Y-m-d');
        
        $db->query("SELECT COUNT(*) as t FROM attendance WHERE date=:d AND status='Hadir'");
        $db->bind('d', $today); $data['count_hadir'] = $db->single()['t'];

        $db->query("SELECT COUNT(*) as t FROM attendance WHERE date=:d AND (status='Sakit' OR status='Izin')");
        $db->bind('d', $today); $data['count_izin'] = $db->single()['t'];
        
        // Total user asisten
        $db->query("SELECT COUNT(*) as t FROM users WHERE role_id=3");
        $total_asisten = $db->single()['t'];
        $data['count_alpha'] = $total_asisten - ($data['count_hadir'] + $data['count_izin']);
        if($data['count_alpha'] < 0) $data['count_alpha'] = 0;

        $view = 'dashboard/index_admin';

    // --- USER (ASISTEN) ---
    } else { 
        // 1. History Presensi
        $data['history'] = $this->model('Presensi_model')->getMyHistory($_SESSION['user_id']);
        
        // 2. Ambil Data Profil Lengkap (Peminatan, Bio, Foto)
        $db->query("SELECT * FROM users WHERE id=:id");
        $db->bind('id', $_SESSION['user_id']);
        $data['profile'] = $db->single();

        $view = 'dashboard/index_user';
    }

    $data['view_file'] = $view; 
    global $view_content; $view_content = $view;
    require_once '../app/views/templates/layout.php';
}

    public function get_live_token() {
        if ($_SESSION['role_id'] != 1) exit;
        header('Content-Type: application/json');
        echo json_encode(['token' => $this->model('Admin_model')->getOrUpdateCheckInToken()]);
        exit;
    }
}