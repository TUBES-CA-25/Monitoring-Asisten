<?php
class Admin_model {
    private $db;
    public function __construct() { $this->db = new Database; }

    public function getLabs() {
        $this->db->query("SELECT * FROM labs");
        return $this->db->resultSet();
    }

    public function getAllAssistants() {
        $this->db->query("SELECT * FROM users WHERE role_id = 3");
        return $this->db->resultSet();
    }

    // Logic Token 3 Jam Sekali
    public function getOrUpdateCheckInToken() {
        $this->db->query("SELECT * FROM qr_tokens WHERE type = 'check_in'");
        $currentToken = $this->db->single();
        
        // Buat signature waktu berdasarkan blok 3 jam
        $currentBlock = floor(date('H') / 3); 
        $dateSignature = date('Y-m-d') . '-' . $currentBlock;
        
        $parts = explode('_', $currentToken['token_code']);
        $dbSignature = isset($parts[0]) ? $parts[0] : '';

        if ($dbSignature !== $dateSignature) {
            $newToken = $dateSignature . '_' . bin2hex(random_bytes(6));
            $this->db->query("UPDATE qr_tokens SET token_code = :token WHERE type = 'check_in'");
            $this->db->bind('token', $newToken);
            $this->db->execute();
            return $newToken;
        }
        return $currentToken['token_code'];
    }

    public function getCheckOutToken() {
        $this->db->query("SELECT token_code FROM qr_tokens WHERE type = 'check_out'");
        return $this->db->single()['token_code'];
    }

    // Logic Statistik Grafik (7 Hari)
    public function getChartStats() {
        $dates = []; $labels = [];
        for ($i = 6; $i >= 0; $i--) {
            $dates[] = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('d M', strtotime("-$i days"));
        }
        $stats = ['labels' => $labels, 'hadir' => [], 'pulang' => [], 'sakit' => [], 'alpha' => []];
        
        $this->db->query("SELECT COUNT(*) as total FROM users WHERE role_id = 3");
        $totalAsisten = $this->db->single()['total'];

        foreach ($dates as $date) {
            $this->db->query("SELECT COUNT(*) as t FROM attendance WHERE date = :d AND status = 'Hadir'");
            $this->db->bind('d', $date); $hadir = $this->db->single()['t']; $stats['hadir'][] = $hadir;

            $this->db->query("SELECT COUNT(*) as t FROM attendance WHERE date = :d AND check_out IS NOT NULL");
            $this->db->bind('d', $date); $stats['pulang'][] = $this->db->single()['t'];

            $this->db->query("SELECT COUNT(*) as t FROM attendance WHERE date = :d AND (status='Sakit' OR status='Izin')");
            $this->db->bind('d', $date); $sakit = $this->db->single()['t']; $stats['sakit'][] = $sakit;

            $alpha = $totalAsisten - ($hadir + $sakit);
            $stats['alpha'][] = ($alpha < 0) ? 0 : $alpha;
        }
        return $stats;
    }
}