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
                       p.nama as name, p.prodi, p.jabatan as position, p.photo_profile, p.is_completed 
                FROM user u 
                JOIN profile p ON u.id_user = p.id_user 
                WHERE u.email = :email";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserById($id) {
        $sql = "SELECT u.id_user as id, u.created_at, p.id_profil, u.role, u.email, 
                       p.nama as name, p.nim, p.kelas, p.prodi, p.jabatan as position, p.photo_profile,
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

    public function calculateRealAlpha($id_profil, $accountCreatedAt, $isCompleted) {
        if ($isCompleted != 1) return 0;

        $this->db->query("SELECT tanggal FROM presensi WHERE id_profil = :pid AND status IN ('Hadir', 'Terlambat')");
        $this->db->bind(':pid', $id_profil);
        $presensiRaw = $this->db->resultSet();
        $presensiMap = [];
        foreach($presensiRaw as $p) $presensiMap[$p['tanggal']] = true;

        $this->db->query("SELECT start_date, end_date FROM izin WHERE id_profil = :pid AND status_approval = 'Approved'");
        $this->db->bind(':pid', $id_profil);
        $izinRanges = $this->db->resultSet();

        $startDate = new DateTime($accountCreatedAt);
        $startDate->setTime(0, 0, 0);

        $endDate = new DateTime();
        $endDate->setTime(0, 0, 0);
        $endDate->modify('-1 day'); 

        if ($startDate > $endDate) return 0;

        $alphaCount = 0;

        while ($startDate <= $endDate) {
            $currDate = $startDate->format('Y-m-d');
            $dayOfWeek = $startDate->format('N');
            
            if ($dayOfWeek <= 7) { 
                $isPresent = isset($presensiMap[$currDate]);
                $isPermitted = false;

                if (!$isPresent) {
                    foreach($izinRanges as $iz) {
                        if ($currDate >= $iz['start_date'] && $currDate <= $iz['end_date']) {
                            $isPermitted = true;
                            break;
                        }
                    }
                }

                if (!$isPresent && !$isPermitted) {
                    $alphaCount++;
                }
            }
            $startDate->modify('+1 day');
        }
        return $alphaCount;
    }

    public function updateSelfProfile($data) {
        try {
            $this->conn->beginTransaction();

            $query = "UPDATE profile SET 
                      nama = :nama, 
                      nim = :nim, 
                      kelas = :kelas,
                      prodi = :prodi,
                      jabatan = :pos,
                      no_telp = :hp, 
                      alamat = :alamat, 
                      jenis_kelamin = :jk,
                      peminatan = :minat,
                      id_lab = :lab,
                      is_completed = :completed 
                      WHERE id_user = :uid";
            
            $stmt = $this->conn->prepare($query);
            
            $params = [
                ':nama' => $data['name'],
                ':nim' => $data['nim'],
                ':kelas' => $data['class'] ?? null,
                ':prodi' => $data['prodi'] ?? null,
                ':pos' => $data['position'],
                ':hp' => $data['phone'],
                ':alamat' => $data['address'],
                ':jk' => $data['gender'],
                ':minat' => $data['interest'] ?? null,
                ':lab' => $data['lab_id'] ?? null,
                ':completed' => $data['is_completed'] ?? 1, 
                ':uid' => $data['id']
            ];

            if (!empty($data['photo'])) {
                $queryFoto = "UPDATE profile SET photo_profile = :photo WHERE id_user = :uid";
                $stmtFoto = $this->conn->prepare($queryFoto);
                $stmtFoto->execute([':photo' => $data['photo'], ':uid' => $data['id']]);
            }

            $queryStatus = "UPDATE profile SET is_completed = 1 WHERE id_user = :uid";
            $stmtStatus = $this->conn->prepare($queryStatus);
            $stmtStatus->execute([':uid' => $data['id']]);

            $stmt->execute($params);
            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getAllUsers() {
       $sql = "SELECT u.id_user as id, u.created_at, p.id_profil, u.role, u.email, 
                       p.nama as name, p.nim, p.kelas, p.prodi, p.jabatan as position, p.photo_profile,
                       p.no_telp, p.alamat, l.nama_lab as lab_name, p.is_completed
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
            $stmtUser->execute([':email'=>$data['email'], ':pass'=>password_hash($data['password'], PASSWORD_BCRYPT), ':role'=>$data['role']]);
            $newUserId = $this->conn->lastInsertId();

            $sqlProf = "INSERT INTO profile (id_user, nama, nim, kelas, prodi, jabatan, no_telp, alamat, photo_profile, is_completed, id_lab) 
                        VALUES (:uid, :name, :nim, :cls, :prodi, :pos, :hp, :addr, :photo, :completed, :lab)";
            $stmtProf = $this->conn->prepare($sqlProf);
            $stmtProf->execute([
                ':uid'  => $newUserId, 
                ':name' => $data['name'], 
                ':nim'  => $data['nim'] ?? null, 
                ':cls'  => $data['class'] ?? null, 
                ':prodi'=> $data['prodi'] ?? null, 
                ':pos'  => $data['position'] ?? 'Anggota',
                ':hp'   => $data['no_telp'], 
                ':addr' => $data['alamat'], 
                ':photo'=> $data['photo'],
                ':completed' => $data['is_completed'] ?? 0,
                ':lab' => $data['lab_id'] ?? null
            ]);
            $this->conn->commit();
            return true;
        } catch (Exception $e) { $this->conn->rollBack(); return false; }
    }

    public function updateUser($data) {
        try {
            $this->conn->beginTransaction();
            $sqlProf = "UPDATE profile SET nama = :name, nim = :nim, kelas = :cls, prodi = :prodi, no_telp = :telp, alamat = :alamat, jabatan = :pos, id_lab = :lab";
            if (!empty($data['photo'])) { $sqlProf .= ", photo_profile = :photo"; }
            $sqlProf .= " WHERE id_user = :uid";
            
            $stmtProf = $this->conn->prepare($sqlProf);
            $params = [
                ':name' => $data['name'], ':nim' => $data['nim'], ':cls' => $data['class'] ?? null, 
                ':prodi' => $data['prodi'] ?? null, ':telp' => $data['no_telp'], 
                ':alamat' => $data['alamat'], ':pos' => $data['position'], ':lab' => $data['lab_id'] ?? null, 
                ':uid' => $data['id']
            ];
            if (!empty($data['photo'])) $params[':photo'] = $data['photo'];
            
            $stmtProf->execute($params);
            
            $sqlUser = "UPDATE user SET email = :email, role = :role WHERE id_user = :uid";
            $stmtUser = $this->conn->prepare($sqlUser);
            $stmtUser->execute([':email'=>$data['email'], ':role'=>$data['role'], ':uid'=>$data['id']]);
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) { $this->conn->rollBack(); return false; }
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

        $sqlKelas = "SELECT kelas, COUNT(*) as count FROM profile p JOIN user u ON p.id_user = u.id_user WHERE u.role = 'User' AND kelas IS NOT NULL GROUP BY kelas";
        $stmt = $this->conn->prepare($sqlKelas); $stmt->execute();
        $results['class'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlMinat = "SELECT peminatan, COUNT(*) as count FROM profile p JOIN user u ON p.id_user = u.id_user WHERE u.role = 'User' AND peminatan IS NOT NULL GROUP BY peminatan";
        $stmt = $this->conn->prepare($sqlMinat); $stmt->execute();
        $results['interest'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $results;
    }

    public function getAllAssistantsWithStatus() {
        $this->db->query("SELECT p.*, u.role 
                          FROM profile p 
                          JOIN user u ON p.id_user = u.id_user 
                          WHERE u.role = 'User' 
                          ORDER BY p.nama ASC");
        $assistants = $this->db->resultSet();

        $today = date('Y-m-d');
        
        foreach ($assistants as &$ast) {
            $ast['status_today'] = 'red'; 

            $this->db->query("SELECT id_presensi FROM presensi 
                              WHERE id_profil = :pid AND tanggal = :d AND waktu_presensi IS NOT NULL");
            $this->db->bind(':pid', $ast['id_profil']);
            $this->db->bind(':d', $today);
            
            if ($this->db->single()) {
                $ast['status_today'] = 'green'; 
            } else {
                $this->db->query("SELECT id_izin FROM izin 
                                  WHERE id_profil = :pid 
                                  AND :d BETWEEN start_date AND end_date 
                                  AND status_approval = 'Approved'");
                $this->db->bind(':pid', $ast['id_profil']);
                $this->db->bind(':d', $today);
                
                if ($this->db->single()) {
                    $ast['status_today'] = 'yellow'; 
                }
            }
        }

        return $assistants;
    }

    public function saveGoogleToken($userId, $token) {
        try {
            $refreshToken = $token['refresh_token'] ?? '';
            $idToken = $token['id_token'] ?? ''; 

            $sql = "INSERT INTO user_google_token (id_user, access_token, refresh_token, id_token, expires_in, created_at) 
                    VALUES (:uid, :at, :rt, :it, :exp, NOW())
                    ON DUPLICATE KEY UPDATE 
                    access_token = :at, 
                    refresh_token = IF(:rt != '', :rt, refresh_token), 
                    id_token = IF(:it != '', :it, id_token), 
                    expires_in = :exp, 
                    created_at = NOW()";

            $this->db->query($sql);
            $this->db->bind(':uid', $userId);
            $this->db->bind(':at', $token['access_token']);
            $this->db->bind(':rt', $refreshToken);
            $this->db->bind(':it', $idToken);
            $this->db->bind(':exp', $token['expires_in']);
            
            return $this->db->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    public function isGoogleConnected($userId) {
        $stmt = $this->conn->prepare("SELECT id_token FROM user_google_token WHERE id_user = :uid");
        $stmt->execute([':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function getAssistants() {
        $sql = "SELECT u.id_user, u.email, u.role,
                       p.nama as name, p.nim, p.photo_profile, 
                       p.jabatan as position, p.kelas, p.is_completed 
                FROM user u 
                LEFT JOIN profile p ON u.id_user = p.id_user 
                WHERE u.role = 'User' 
                ORDER BY p.nama ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>