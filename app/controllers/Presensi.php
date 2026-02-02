<?php
class Presensi extends Controller {
    public function scan($token) {
        if(!isset($_SESSION['user_id'])) { header('Location: ' . BASEURL . '/auth'); exit; }
        
        $db = new Database;
        $db->query("SELECT * FROM qr_tokens WHERE token_code = :token");
        $db->bind('token', $token);
        $valid = $db->single();

        if (!$valid) { echo "QR Invalid!"; exit; }

        if ($valid['type'] == 'check_in') {
            if ($this->model('Presensi_model')->hasCheckedInToday($_SESSION['user_id'])) {
                $data = ['pesan' => 'Anda sudah check-in hari ini.', 'tipe' => 'warning'];
            } else {
                $this->model('Presensi_model')->absenMasuk($_SESSION['user_id']);
                $data = ['pesan' => 'Selamat bekerja!', 'tipe' => 'success'];
            }
            $this->view('presensi/success_in', $data);
        } else {
            header('Location: ' . BASEURL . '/presensi/form_out');
        }
    }

    public function form_out() {
        if (!$this->model('Presensi_model')->hasCheckedInToday($_SESSION['user_id'])) {
             echo "<script>alert('Belum Check-In!'); window.location='".BASEURL."/dashboard';</script>"; exit;
        }
        $this->view('presensi/form_logbook');
    }

    public function submit_checkout() {
        $log = $_POST['kegiatan'] . " | Note: " . $_POST['keterangan'];
        $this->model('Presensi_model')->absenPulang($_SESSION['user_id'], $log);
        header('Location: ' . BASEURL . '/dashboard');
    }
}