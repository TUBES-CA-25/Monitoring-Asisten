<?php
class QrModel {
    private $conn;
    public function __construct() { $db = new Database(); $this->conn = $db->getConnection(); }

    public function getOrGenerateToken($type) {
        // Mapping tipe input 'check_in' -> 'Presensi' (Database ENUM)
        $dbType = ($type == 'check_in') ? 'Presensi' : 'Pulang';

        // Cek token aktif
        $stmt = $this->conn->prepare("SELECT * FROM qr_code WHERE tipe = :t AND valid_until > NOW() ORDER BY id_qr DESC LIMIT 1");
        $stmt->execute([':t' => $dbType]);
        $token = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($token) {
            return $token['token_code'];
        }

        // Generate baru
        $code = md5(uniqid(rand(), true));
        $interval = ($dbType == 'Presensi') ? '5 MINUTE' : '24 HOUR'; // Sesuai aturan baru
        
        $sql = "INSERT INTO qr_code (tipe, token_code, generated_at, valid_until) 
                VALUES (:t, :c, NOW(), DATE_ADD(NOW(), INTERVAL $interval))";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':t' => $dbType, ':c' => $code]);
        
        return $code;
    }
}



?>