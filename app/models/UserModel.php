<?php
class UserModel {
    private $conn;
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function login($email) {
        $sql = "SELECT u.id_user as id, p.id_profil, u.email, u.password, u.role, 
                       p.nama as name, p.jabatan as position, p.photo_profile, p.is_completed 
                FROM user u 
                JOIN profile p ON u.id_user = p.id_user 
                WHERE u.email = :email";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserById($id) {
        $sql = "SELECT u.id_user as id, p.id_profil, u.role, u.email, 
                       p.nama as name, p.nim, p.kelas, p.jabatan as position, p.photo_profile,
                       p.alamat, p.no_telp, p.jenis_kelamin, p.peminatan, p.is_completed,
                       l.nama_lab as lab_name,
                       (SELECT COUNT(*) FROM presensi pr WHERE pr.id_profil = p.id_profil AND pr.tanggal = CURDATE() AND pr.waktu_pulang IS NULL) as is_online
                FROM user u 
                JOIN profile p ON u.id_user = p.id_user 
                LEFT JOIN lab l ON p.id_lab = l.id_lab 
                WHERE u.id_user = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateSelfProfile($data) {
        try {
            $this->conn->beginTransaction();

            $query = "UPDATE profile SET 
                      nama = :nama, 
                      nim = :nim, 
                      jabatan = :pos,
                      no_telp = :hp, 
                      alamat = :alamat, 
                      jenis_kelamin = :jk,
                      peminatan = :minat
                      WHERE id_user = :uid";
            
            $params = [
                ':nama' => $data['name'],
                ':nim' => $data['nim'],
                ':pos' => $data['position'],
                ':hp' => $data['phone'],
                ':alamat' => $data['address'],
                ':jk' => $data['gender'],
                ':minat' => $data['interest'] ?? null,
                ':uid' => $data['id']
            ];

            if (!empty($data['photo'])) {
                $query = str_replace("WHERE", ", photo_profile = :foto WHERE", $query);
                $params[':foto'] = $data['photo'];
            }

            if ($data['role'] != 'Admin') {
                $query = str_replace("WHERE", ", is_completed = 1 WHERE", $query);
            }

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getAllUsers() {
        $sql = "SELECT u.id_user as id, p.id_profil, u.role, u.email, 
                       p.nama as name, p.nim, p.jabatan as position, p.photo_profile,
                       p.no_telp, p.alamat, 
                       l.nama_lab as lab_name, p.is_completed
                FROM user u 
                JOIN profile p ON u.id_user = p.id_user 
                LEFT JOIN lab l ON p.id_lab = l.id_lab 
                ORDER BY u.role, p.nama";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countUsersByRole($role = null) {
        if ($role) {
            $sql = "SELECT COUNT(*) as total FROM user WHERE role = :role";
            $this->db->query($sql);
            $this->db->bind(':role', $role);
        } else {
            $sql = "SELECT COUNT(*) as total FROM user";
            $this->db->query($sql);
        }
        return $this->db->single()['total'];
    }

    public function createUser($data) {
        try {
            $this->conn->beginTransaction();
            $sqlUser = "INSERT INTO user (email, password, role) VALUES (:email, :pass, :role)";
            $stmtUser = $this->conn->prepare($sqlUser);
            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmtUser->execute([':email' => $data['email'], ':pass' => $hash, ':role' => $data['role']]);
            $newUserId = $this->conn->lastInsertId();
            $sqlProf = "INSERT INTO profile (id_user, nama, nim, kelas, jabatan, id_lab, jenis_kelamin) 
                        VALUES (:uid, :name, :nim, :cls, :pos, :lab, :jk)";
            $stmtProf = $this->conn->prepare($sqlProf);
            $stmtProf->execute([
                ':uid'  => $newUserId, ':name' => $data['name'], ':nim'  => $data['nim'] ?? null,
                ':cls'  => $data['class_name'] ?? null, ':pos'  => $data['position'] ?? 'Anggota',
                ':lab'  => !empty($data['assigned_lab_id']) ? $data['assigned_lab_id'] : null, ':jk'   => $data['gender'] ?? null
            ]);
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function updateUser($data) {
        try {
            $this->conn->beginTransaction();
            $sqlProf = "UPDATE profile SET nama = :name, nim = :nim, no_telp = :telp, alamat = :alamat, jabatan = :pos";
            if (!empty($data['photo'])) { $sqlProf .= ", photo_profile = :photo"; }
            $sqlProf .= " WHERE id_user = :uid";
            $stmtProf = $this->conn->prepare($sqlProf);
            $params = [
                ':name' => $data['name'], ':nim' => $data['nim'], ':telp' => $data['no_telp'],
                ':alamat' => $data['alamat'], ':pos' => $data['position'], ':uid' => $data['id']
            ];
            if (!empty($data['photo'])) { $params[':photo'] = $data['photo']; }
            $stmtProf->execute($params);
            if (!empty($data['email'])) {
                $sqlUser = "UPDATE user SET email = :email, role = :role WHERE id_user = :uid";
                $stmtUser = $this->conn->prepare($sqlUser);
                $stmtUser->execute([':email' => $data['email'], ':role' => $data['role'], ':uid' => $data['id']]);
            }
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function deleteUser($id) {
        $this->db->query("DELETE FROM user WHERE id_user = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function changePassword($id, $newPassword) {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->query("UPDATE user SET password = :pass WHERE id_user = :id");
        $this->db->bind(':pass', $hash);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function getAssistantRankings($type) {
        $sql = "";
        switch($type) {
            case 'online':
                $sql = "SELECT p.nama, p.jabatan, p.photo_profile, 'Online' as score 
                        FROM presensi pr 
                        JOIN profile p ON pr.id_profil = p.id_profil 
                        WHERE pr.tanggal = CURDATE() AND pr.waktu_pulang IS NULL";
                break;
            case 'rajin':
                $sql = "SELECT p.nama, p.jabatan, p.photo_profile, COUNT(pr.id_presensi) as score 
                        FROM profile p 
                        JOIN presensi pr ON p.id_profil = pr.id_profil 
                        WHERE pr.status = 'Hadir' 
                        GROUP BY p.id_profil ORDER BY score DESC LIMIT 5";
                break;
            case 'jarang':
                $sql = "SELECT p.nama, p.jabatan, p.photo_profile, COUNT(pr.id_presensi) as score 
                        FROM profile p 
                        JOIN user u ON p.id_user = u.id_user 
                        LEFT JOIN presensi pr ON p.id_profil = pr.id_profil AND pr.status = 'Hadir'
                        WHERE u.role = 'User'
                        GROUP BY p.id_profil ORDER BY score ASC LIMIT 5";
                break;
            case 'cepat':
                $sql = "SELECT p.nama, p.jabatan, p.photo_profile, SEC_TO_TIME(AVG(TIME_TO_SEC(pr.waktu_presensi))) as score 
                        FROM profile p 
                        JOIN presensi pr ON p.id_profil = pr.id_profil 
                        WHERE pr.status = 'Hadir'
                        GROUP BY p.id_profil ORDER BY score ASC LIMIT 5";
                break;
            case 'terlambat':
                $sql = "SELECT p.nama, p.jabatan, p.photo_profile, SEC_TO_TIME(AVG(TIME_TO_SEC(pr.waktu_presensi))) as score 
                        FROM profile p 
                        JOIN presensi pr ON p.id_profil = pr.id_profil 
                        WHERE pr.status = 'Hadir'
                        GROUP BY p.id_profil ORDER BY score DESC LIMIT 5";
                break;
            case 'sering_izin':
                $sql = "SELECT p.nama, p.jabatan, p.photo_profile, COUNT(i.id_izin) as score 
                        FROM profile p 
                        JOIN izin i ON p.id_profil = i.id_profil 
                        WHERE i.status_approval = 'Approved'
                        GROUP BY p.id_profil ORDER BY score DESC LIMIT 5";
                break;
            case 'jarang_izin':
                $sql = "SELECT p.nama, p.jabatan, p.photo_profile, COUNT(i.id_izin) as score 
                        FROM profile p 
                        JOIN user u ON p.id_user = u.id_user
                        LEFT JOIN izin i ON p.id_profil = i.id_profil AND i.status_approval = 'Approved'
                        WHERE u.role = 'User'
                        GROUP BY p.id_profil ORDER BY score ASC LIMIT 5";
                break;
            case 'logbook_lengkap':
                $sql = "SELECT p.nama, p.jabatan, p.photo_profile, FLOOR(AVG(LENGTH(l.detail_aktivitas) - LENGTH(REPLACE(l.detail_aktivitas, ' ', '')) + 1)) as score 
                        FROM profile p 
                        JOIN logbook l ON p.id_profil = l.id_profil
                        GROUP BY p.id_profil ORDER BY score DESC LIMIT 5";
                break;
            case 'logbook_singkat':
                $sql = "SELECT p.nama, p.jabatan, p.photo_profile, FLOOR(AVG(LENGTH(l.detail_aktivitas) - LENGTH(REPLACE(l.detail_aktivitas, ' ', '')) + 1)) as score 
                        FROM profile p 
                        JOIN logbook l ON p.id_profil = l.id_profil
                        GROUP BY p.id_profil ORDER BY score ASC LIMIT 5";
                break;
            case 'sibuk':
                $sql = "SELECT p.nama, p.jabatan, p.photo_profile, COUNT(ja.id_jadwal_asisten) as score 
                        FROM profile p 
                        JOIN jadwal_asisten ja ON p.id_profil = ja.id_profil
                        GROUP BY p.id_profil ORDER BY score DESC LIMIT 5";
                break;
            case 'santai':
                $sql = "SELECT p.nama, p.jabatan, p.photo_profile, COUNT(ja.id_jadwal_asisten) as score 
                        FROM profile p 
                        JOIN user u ON p.id_user = u.id_user
                        LEFT JOIN jadwal_asisten ja ON p.id_profil = ja.id_profil
                        WHERE u.role = 'User'
                        GROUP BY p.id_profil ORDER BY score ASC LIMIT 5";
                break;
        }

        if(empty($sql)) return [];
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDemographics() {
        $results = [];

        $sqlJK = "SELECT jenis_kelamin, COUNT(*) as count FROM profile p JOIN user u ON p.id_user = u.id_user WHERE u.role = 'User' GROUP BY jenis_kelamin";
        $stmt = $this->conn->prepare($sqlJK); $stmt->execute();
        $results['gender'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlAngkatan = "SELECT SUBSTRING(nim, 5, 2) as angkatan, COUNT(*) as count FROM profile p JOIN user u ON p.id_user = u.id_user WHERE u.role = 'User' AND nim IS NOT NULL GROUP BY angkatan";
        $stmt = $this->conn->prepare($sqlAngkatan); $stmt->execute();
        $results['year'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlKelas = "SELECT kelas, COUNT(*) as count FROM profile p JOIN user u ON p.id_user = u.id_user WHERE u.role = 'User' AND kelas IS NOT NULL GROUP BY kelas";
        $stmt = $this->conn->prepare($sqlKelas); $stmt->execute();
        $results['class'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlMinat = "SELECT peminatan, COUNT(*) as count FROM profile p JOIN user u ON p.id_user = u.id_user WHERE u.role = 'User' AND peminatan IS NOT NULL GROUP BY peminatan";
        $stmt = $this->conn->prepare($sqlMinat); $stmt->execute();
        $results['interest'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $results;
    }
}
?>