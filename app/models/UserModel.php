<?php
class UserModel {
    private $conn;
    public function __construct() { $db = new Database(); $this->conn = $db->getConnection(); }

    public function login($email) {
        $sql = "SELECT u.id_user as id, p.id_profil, u.email, u.password, u.role, p.nama as name 
                FROM user u 
                JOIN profile p ON u.id_user = p.id_user 
                WHERE u.email = :email";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserById($id) {
        // PERBAIKAN: Menambahkan kolom lengkap dari tabel PROFILE
        $sql = "SELECT u.id_user as id, p.id_profil, u.role, u.email, 
                       p.nama as name, p.nim, p.kelas, p.jabatan as position, p.photo_profile,
                       p.alamat, p.no_telp, p.jenis_kelamin, p.peminatan,
                       l.nama_lab as lab_name 
                FROM user u 
                JOIN profile p ON u.id_user = p.id_user 
                LEFT JOIN lab l ON p.id_lab = l.id_lab 
                WHERE u.id_user = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllUsers() {
        $sql = "SELECT u.id_user as id, p.id_profil, u.role, u.email, 
                       p.nama as name, p.nim, p.jabatan as position,
                       l.nama_lab as lab_name
                FROM user u 
                JOIN profile p ON u.id_user = p.id_user 
                LEFT JOIN lab l ON p.id_lab = l.id_lab 
                ORDER BY u.role, p.nama";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createUser($data) {
        try {
            $this->conn->beginTransaction();
            $sqlUser = "INSERT INTO user (email, password, role) VALUES (:email, :pass, :role)";
            $stmtUser = $this->conn->prepare($sqlUser);
            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmtUser->execute([':email' => $data['email'], ':pass' => $hash, ':role' => $data['role']]);
            $newUserId = $this->conn->lastInsertId();

            $sqlProf = "INSERT INTO profile (id_user, nama, nim, kelas, jabatan, id_lab) VALUES (:uid, :name, :nim, :cls, :pos, :lab)";
            $stmtProf = $this->conn->prepare($sqlProf);
            $stmtProf->execute([
                ':uid' => $newUserId, ':name' => $data['name'], 
                ':nim' => $data['nim'] ?? null, ':cls' => $data['class_name'] ?? null, 
                ':pos' => $data['position'], ':lab' => !empty($data['assigned_lab_id']) ? $data['assigned_lab_id'] : null
            ]);
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function deleteUser($id) {
        $stmt = $this->conn->prepare("DELETE FROM user WHERE id_user=:id");
        return $stmt->execute([':id' => $id]);
    }

    public function getAttendanceStatusColor($userId) {
        $today = date('Y-m-d');
        $stmtProf = $this->conn->prepare("SELECT id_profil FROM profile WHERE id_user = ?");
        $stmtProf->execute([$userId]);
        $profil = $stmtProf->fetch();
        if(!$profil) return 'red';
        $pId = $profil['id_profil'];

        $stmt = $this->conn->prepare("SELECT id_presensi FROM presensi WHERE id_profil = ? AND tanggal = ?");
        $stmt->execute([$pId, $today]);
        if($stmt->rowCount() > 0) return 'green';

        $stmt = $this->conn->prepare("SELECT id_izin FROM izin WHERE id_profil = ? AND ? BETWEEN start_date AND end_date AND status_approval = 'Approved'");
        $stmt->execute([$pId, $today]);
        if($stmt->rowCount() > 0) return 'yellow';

        return 'red';
    }
}

?>