<?php
class QrModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getOrGenerateToken($type) {
        // Mapping input 'check_in' -> 'Presensi' (Database ENUM)
        $dbType = ($type == 'check_in') ? 'Presensi' : 'Pulang';

        // 1. Cek apakah ada token yang MASIH VALID (valid_until > NOW)
        $sql = "SELECT * FROM qr_code 
                WHERE tipe = :t AND valid_until > NOW() 
                ORDER BY id_qr DESC LIMIT 1";
        
        $this->db->query($sql);
        $this->db->bind(':t', $dbType);
        $token = $this->db->single();

        if ($token) {
            return $token['token_code'];
        }

        // 2. Jika tidak ada / expired, Generate Baru
        $code = md5(uniqid(rand(), true));
        $interval = ($dbType == 'Presensi') ? '5 MINUTE' : '24 HOUR'; 
        
        $sqlInsert = "INSERT INTO qr_code (tipe, token_code, generated_at, valid_until) 
                      VALUES (:t, :c, NOW(), DATE_ADD(NOW(), INTERVAL $interval))";
        
        $this->db->query($sqlInsert);
        $this->db->bind(':t', $dbType);
        $this->db->bind(':c', $code);
        $this->db->execute();
        
        return $code;
    }
    
    // Validasi token saat user scan
    public function validateToken($code, $typeInput) {
        $dbType = ($typeInput == 'check_in') ? 'Presensi' : 'Pulang';
        
        $this->db->query("SELECT * FROM qr_code WHERE token_code = :c AND tipe = :t AND valid_until > NOW()");
        $this->db->bind(':c', $code);
        $this->db->bind(':t', $dbType);
        
        return $this->db->rowCount() > 0;
    }
}
?>