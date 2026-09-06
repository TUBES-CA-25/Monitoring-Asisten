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

        $sql = "SELECT * FROM qr_code 
                WHERE tipe = :t 
                  AND valid_until > DATE_ADD(NOW(), INTERVAL 30 SECOND) 
                  AND used_by_user_id IS NULL
                ORDER BY id_qr DESC LIMIT 1";
        
        $this->db->query($sql);
        $this->db->bind(':t', $dbType);
        $token = $this->db->single();

        if ($token) {
            return $token['token_code'];
        }
        
        // [Item 8] Gunakan random_bytes — kriptografis aman, tidak bisa diprediksi
        $code = bin2hex(random_bytes(24)); // 48 karakter hex
        $interval = '3 MINUTE';
        
        $sqlClean = "DELETE FROM qr_code WHERE tipe = :t AND valid_until < NOW()";
        $this->db->query($sqlClean);
        $this->db->bind(':t', $dbType);
        $this->db->execute();

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

        // [Item 8] Token kriptografis — tidak bergantung pada timestamp
            $staticCode = bin2hex(random_bytes(24));
        
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
        // [BARU – Tahap 35] Token valid jika: belum expired DAN belum dipakai user lain
        $this->db->query("SELECT * FROM qr_code WHERE token_code = :c AND valid_until >= NOW() AND used_by_user_id IS NULL");
        $this->db->bind(':c', $cleanCode);
        return $this->db->single(); 
    }
    
    public function validateToken($code, $typeInput) {
        $res = $this->getTokenData($code);
        if (!$res) return false;
        
        $dbType = ($typeInput == 'check_in') ? 'Presensi' : 'Pulang';
        return $res['tipe'] === $dbType;
    }

    /**
     * [BARU – Tahap 35] Selalu buat token baru, abaikan token lama
     * yang belum expired. Dipakai saat admin menekan "Generate Ulang"
     * atau saat timer halaman QR habis.
     */
    public function generateFreshToken($type) {
        $dbType   = ($type == 'check_in') ? 'Presensi' : 'Pulang';
        // [Item 8] Token kriptografis
        $code     = bin2hex(random_bytes(24));
        $interval = '3 MINUTE';

        // Bersihkan token lama (expired atau sudah dipakai)
        $this->db->query("DELETE FROM qr_code WHERE tipe = :t AND (valid_until < NOW() OR used_by_user_id IS NOT NULL)");
        $this->db->bind(':t', $dbType);
        $this->db->execute();

        $this->db->query("INSERT INTO qr_code (tipe, token_code, generated_at, valid_until) VALUES (:t, :c, NOW(), DATE_ADD(NOW(), INTERVAL $interval))");
        $this->db->bind(':t', $dbType);
        $this->db->bind(':c', $code);
        $this->db->execute();

        return $code;
    }

    public function markTokenUsed($code, $userId) {
        $this->db->query(
            "UPDATE qr_code SET used_by_user_id = :uid, used_at = NOW()
             WHERE token_code = :c AND used_by_user_id IS NULL"
        );
        $this->db->bind(':uid', $userId);
        $this->db->bind(':c', $code);
        return $this->db->execute();
    }
}
?>