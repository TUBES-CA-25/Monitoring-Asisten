<?php
class QrModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->db->query("SET time_zone = '+08:00'");
        $this->db->execute();
    }

    public function getOrGenerateToken($type) {
        $dbType = ($type == 'check_in') ? 'Presensi' : 'Pulang';

        // =================================================================================
        // OPSI 1: QR CODE DINAMIS (DENGAN AUTO-CLEANUP)
        // =================================================================================
        $sql = "SELECT * FROM qr_code 
                WHERE tipe = :t AND valid_until > DATE_ADD(NOW(), INTERVAL 30 SECOND) 
                ORDER BY id_qr DESC LIMIT 1";
        
        $this->db->query($sql);
        $this->db->bind(':t', $dbType);
        $token = $this->db->single();

        if ($token) {
            return $token['token_code'];
        }

        $code = md5(uniqid(rand(), true));
        $interval = ($dbType == 'Presensi') ? '5 MINUTE' : '24 HOUR'; 
        
        $sqlClean = "DELETE FROM qr_code WHERE tipe = :t AND valid_until < NOW()";
        $this->db->query($sqlClean);
        $this->db->bind(':t', $dbType);
        $this->db->execute();

        // 3. Simpan Token Baru
        $sqlInsert = "INSERT INTO qr_code (tipe, token_code, generated_at, valid_until) 
                      VALUES (:t, :c, NOW(), DATE_ADD(NOW(), INTERVAL $interval))";
        
        $this->db->query($sqlInsert);
        $this->db->bind(':t', $dbType);
        $this->db->bind(':c', $code);
        $this->db->execute();
        
        return $code;
        

        // =================================================================================
        // OPSI 2: QR CODE STATIS / PERMANEN (OPSIONAL)
        // =================================================================================
        /*
        $sql = "SELECT token_code FROM qr_code 
                WHERE tipe = :t AND valid_until > '3000-01-01' 
                ORDER BY id_qr DESC LIMIT 1";
        
        $this->db->query($sql);
        $this->db->bind(':t', $dbType);
        $token = $this->db->single();

        if ($token) {
            return $token['token_code'];
        }

        $staticCode = md5('STATIC_' . $dbType . '_' . time()); 
        
        $sqlInsert = "INSERT INTO qr_code (tipe, token_code, generated_at, valid_until) 
                      VALUES (:t, :c, NOW(), '9999-12-31 23:59:59')";
        
        $this->db->query($sqlInsert);
        $this->db->bind(':t', $dbType);
        $this->db->bind(':c', $staticCode);
        $this->db->execute();
        
        return $staticCode;
        */
    }
    
    public function getTokenData($code) {
        $cleanCode = trim($code);
        $this->db->query("SELECT * FROM qr_code WHERE token_code = :c AND valid_until >= NOW()");
        $this->db->bind(':c', $cleanCode);
        return $this->db->single(); 
    }
    
    public function validateToken($code, $typeInput) {
        $res = $this->getTokenData($code);
        if (!$res) return false;
        
        $dbType = ($typeInput == 'check_in') ? 'Presensi' : 'Pulang';
        return $res['tipe'] === $dbType;
    }
}
?>