<?php
class UserModel {
    private $conn;
    private $db;

    /**
     * [BARU - Modul 1 V3] Definisi "hari kerja" untuk status ALPHA otomatis
     * ("Semua asisten wajib hadir setiap hari kerja"). Nilai mengikuti
     * DateTime::format('N') -> 1=Senin .. 7=Minggu. Default Senin-Sabtu
     * (1-6), Minggu (7) dianggap hari libur & tidak dihitung Alpa. Ubah
     * konstanta ini saja jika definisi "hari kerja" berbeda (mis. 5 untuk
     * Senin-Jumat).
     */
    const WORK_DAYS_MAX_DOW = 6;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function login($email) {
        $sql = "SELECT u.id_user as id, p.id_profil, u.email, u.password, u.role, u.status_account,
                       p.nama as name, p.prodi, p.jabatan as position, p.photo_profile, p.is_completed 
                FROM user u 
                JOIN profile p ON u.id_user = p.id_user 
                WHERE u.email = :email";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // [BARU - Edit Email/Password] Cek apakah email sudah dipakai user LAIN
    // (dipakai sebelum UPDATE user.email agar tidak menabrak UNIQUE KEY
    // `email` dengan error SQL mentah yang membingungkan).
    public function isEmailTaken($email, $excludeUserId = null) {
        $sql = "SELECT id_user FROM user WHERE email = :email";
        $params = [':email' => $email];
        if (!empty($excludeUserId)) {
            $sql .= " AND id_user != :uid";
            $params[':uid'] = $excludeUserId;
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserById($id) {
        $sql = "SELECT u.id_user as id, u.created_at, u.status_account, p.id_profil, u.role, u.email, 
                       p.nama as name, p.nim, p.kelas, p.angkatan, p.prodi, p.jabatan as position, p.photo_profile,
                       p.alamat, p.no_telp, p.jenis_kelamin, p.peminatan, p.is_completed, p.id_lab,
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

    public function createUser($data) {
        try {
            $this->conn->beginTransaction();

            $sqlUser = "INSERT INTO user (email, password, role, created_at) VALUES (:email, :pass, :role, NOW())";
            $stmtUser = $this->conn->prepare($sqlUser);
            $stmtUser->execute([
                ':email' => $data['email'], 
                ':pass'  => password_hash($data['password'], PASSWORD_BCRYPT), 
                ':role'  => $data['role']
            ]);
    
            $newUserId = $this->conn->lastInsertId();

            $sqlProf = "INSERT INTO profile (id_user, nama, nim, kelas, angkatan, prodi, jabatan, no_telp, alamat, jenis_kelamin, peminatan, photo_profile, is_completed, id_lab) 
                        VALUES (:uid, :name, :nim, :cls, :angkatan, :prodi, :pos, :hp, :addr, :jk, :minat, :photo, :completed, :lab)";
            
            $stmtProf = $this->conn->prepare($sqlProf);
            
            $stmtProf->execute([
                ':uid'  => $newUserId, 
                ':name' => $data['name'], 
                ':nim'  => !empty($data['nim']) ? $data['nim'] : NULL, 
                ':cls'  => !empty($data['class']) ? $data['class'] : NULL, 
                ':angkatan' => !empty($data['angkatan']) ? $data['angkatan'] : NULL,
                ':prodi'=> !empty($data['prodi']) ? $data['prodi'] : NULL, 
                ':pos'  => $data['position'],
                ':hp'   => !empty($data['no_telp']) ? $data['no_telp'] : NULL, 
                ':addr' => !empty($data['alamat']) ? $data['alamat'] : NULL, 
                ':jk'   => !empty($data['gender']) ? $data['gender'] : NULL,
                ':minat'=> !empty($data['interest']) ? $data['interest'] : NULL,
                ':photo'=> $data['photo'], 
                ':completed' => $data['is_completed'] ?? 0,
                ':lab'  => ($data['lab_id'] > 0) ? $data['lab_id'] : NULL
            ]);

            $this->conn->commit();
            return true;

        } catch (Exception $e) { 
            $this->conn->rollBack(); 
    
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'title' => 'DATABASE ERROR',
                'message' => 'Penyebab Gagal: ' . $e->getMessage()
            ]);
            exit; 
        }
    }

    public function updateUser($data) {
        try {
            $this->conn->beginTransaction();
            
            $sqlProf = "UPDATE profile SET nama = :name, nim = :nim, kelas = :cls, angkatan = :angkatan, prodi = :prodi, no_telp = :telp, alamat = :alamat, jenis_kelamin = :jk, peminatan = :minat, jabatan = :pos, id_lab = :lab";
            if (!empty($data['photo'])) { $sqlProf .= ", photo_profile = :photo"; }
            $sqlProf .= " WHERE id_user = :uid";
            
            $stmtProf = $this->conn->prepare($sqlProf);
            $params = [
                ':name' => $data['name'], 
                ':nim' => !empty($data['nim']) ? $data['nim'] : NULL, 
                ':cls' => !empty($data['class']) ? $data['class'] : NULL, 
                ':angkatan' => !empty($data['angkatan']) ? $data['angkatan'] : NULL,
                ':prodi' => !empty($data['prodi']) ? $data['prodi'] : NULL, 
                ':telp' => !empty($data['no_telp']) ? $data['no_telp'] : NULL, 
                ':alamat' => !empty($data['alamat']) ? $data['alamat'] : NULL, 
                ':jk' => !empty($data['gender']) ? $data['gender'] : NULL,
                ':minat' => !empty($data['interest']) ? $data['interest'] : NULL,
                ':pos' => !empty($data['position']) ? $data['position'] : 'Anggota', 
                ':lab' => !empty($data['lab_id']) ? $data['lab_id'] : NULL, 
                ':uid' => $data['id']
            ];
            if (!empty($data['photo'])) $params[':photo'] = $data['photo'];
            
            $stmtProf->execute($params);
            
            $sqlUser = "UPDATE user SET email = :email, role = :role, status_account = :status_account";
            $paramsUser = [':email'=>$data['email'], ':role'=>$data['role'], ':uid'=>$data['id'], ':status_account'=>$data['status_account'] ?? 'ACTIVE'];

            if (!empty($data['password'])) {
                $sqlUser .= ", password = :pass";
                $paramsUser[':pass'] = password_hash($data['password'], PASSWORD_BCRYPT);
            }

            $sqlUser .= " WHERE id_user = :uid";
            $stmtUser = $this->conn->prepare($sqlUser);
            $stmtUser->execute($paramsUser);
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) { 
            $this->conn->rollBack(); 
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function updateSelfProfile($data) {
        try {
            $this->conn->beginTransaction();

            $query = "UPDATE profile SET
                      nama = :nama,
                      nim = :nim,
                      nidn_nip = :nidn_nip,
                      kelas = :kelas,
                      angkatan = :angkatan,
                      prodi = :prodi,
                      jabatan = :pos,
                      no_telp = :hp, 
                      alamat = :alamat, 
                      jenis_kelamin = :jk,
                      peminatan = :minat,
                      id_lab = :lab,
                      is_completed = 1 
                      WHERE id_user = :uid";
            
            $stmt = $this->conn->prepare($query);
            
            $params = [
                ':nama' => $data['name'],
                ':nim' => !empty($data['nim']) ? $data['nim'] : NULL,
                // [BARU] Sebelumnya kolom ini tidak ada di query sama sekali -
                // NIDN/NIP yang diisi Admin/Kepala Lab di form Edit Profil
                // selalu hilang tanpa jejak walau server merespons "berhasil".
                ':nidn_nip' => !empty($data['nidn_nip']) ? $data['nidn_nip'] : NULL,
                ':kelas' => !empty($data['class']) ? $data['class'] : NULL,
                ':angkatan' => !empty($data['angkatan']) ? $data['angkatan'] : NULL,
                ':prodi' => !empty($data['prodi']) ? $data['prodi'] : NULL,
                ':pos' => $data['position'],
                ':hp' => $data['phone'],
                ':alamat' => $data['address'],
                ':jk' => $data['gender'],
                ':minat' => !empty($data['interest']) ? $data['interest'] : NULL,
                ':lab' => !empty($data['lab_id']) ? $data['lab_id'] : NULL,
                ':uid' => $data['id']
            ];

            $stmt->execute($params);

            // [BARU - Edit Email/Password] update tabel `user` jika
            // email/password ikut dikirim dari common/edit_profile.php.
            // - email: selalu diperbarui jika dikirim (sudah divalidasi
            //   format & keunikan di controller).
            // - password: hanya diperbarui jika toggle "Ganti Password"
            //   aktif & non-kosong (sudah divalidasi minimal 8 karakter di
            //   controller); hash dilakukan di sini.
            if (!empty($data['email']) || !empty($data['password'])) {
                $setParts = [];
                $userParams = [':uid' => $data['id']];

                if (!empty($data['email'])) {
                    $setParts[] = 'email = :email';
                    $userParams[':email'] = $data['email'];
                }
                if (!empty($data['password'])) {
                    $setParts[] = 'password = :pass';
                    $userParams[':pass'] = password_hash($data['password'], PASSWORD_BCRYPT);
                }

                $queryUser = "UPDATE user SET " . implode(', ', $setParts) . " WHERE id_user = :uid";
                $stmtUser = $this->conn->prepare($queryUser);
                $stmtUser->execute($userParams);
            }

            if (!empty($data['photo'])) {
                $queryFoto = "UPDATE profile SET photo_profile = :photo WHERE id_user = :uid";
                $stmtFoto = $this->conn->prepare($queryFoto);
                $stmtFoto->execute([':photo' => $data['photo'], ':uid' => $data['id']]);
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }


    public function calculateRealAlpha($id_profil, $accountCreatedAt, $isCompleted) {
        // [PERBAIKAN v7] Gunakan attendance_reset_at sebagai titik mulai
        // penghitungan alpha jika tersedia (di-set saat reset dilakukan).
        // Query dibungkus try-catch: kolom ini baru ada setelah migration v7
        // dijalankan; jika belum ada, gracefully fall back ke accountCreatedAt.
        $resetAt = null;
        try {
            $this->db->query("SELECT attendance_reset_at FROM profile WHERE id_profil = :pid");
            $this->db->bind(':pid', $id_profil);
            $profileRow = $this->db->single();
            $resetAt    = $profileRow['attendance_reset_at'] ?? null;
        } catch (\Throwable $e) {
            // Kolom belum ada (migration v7 belum dijalankan) — abaikan
            $resetAt = null;
        }

        $this->db->query("SELECT tanggal FROM presensi WHERE id_profil = :pid AND status IN ('Hadir', 'Terlambat')");
        $this->db->bind(':pid', $id_profil);
        $presensiRaw = $this->db->resultSet();
        $presensiMap = [];
        foreach($presensiRaw as $p) $presensiMap[$p['tanggal']] = true;

        $this->db->query("SELECT start_date, end_date FROM izin WHERE id_profil = :pid AND status_approval = 'Approved'");
        $this->db->bind(':pid', $id_profil);
        $izinRanges = $this->db->resultSet();

        // Titik mulai: gunakan yang paling akhir antara created_at dan reset_at
        $startDate = new DateTime($accountCreatedAt);
        if ($resetAt) {
            $resetDate = new DateTime($resetAt);
            if ($resetDate > $startDate) {
                $startDate = clone $resetDate;
            }
        }
        $startDate->setTime(0, 0, 0);

        $endDate = new DateTime();
        $endDate->setTime(0, 0, 0);
        $endDate->modify('-1 day'); 

        if ($startDate > $endDate) return 0;

        $alphaCount = 0;

        while ($startDate <= $endDate) {
            $currDate  = $startDate->format('Y-m-d');
            $dayOfWeek = (int) $startDate->format('N');

            if ($dayOfWeek <= self::WORK_DAYS_MAX_DOW) {
                $isPresent   = isset($presensiMap[$currDate]);
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

    public function getAllUsers() {
       $sql = "SELECT u.id_user as id, u.created_at, u.status_account, p.id_profil, u.role, u.email, 
                       p.nama as name, p.nim, p.kelas, p.angkatan, p.prodi, p.jabatan as position, p.photo_profile,
                       p.no_telp, p.alamat, p.jenis_kelamin, p.peminatan, p.id_lab, l.nama_lab as lab_name, p.is_completed,
                       (SELECT COUNT(*) FROM presensi pr WHERE pr.id_profil = p.id_profil AND pr.tanggal = CURDATE() AND pr.waktu_pulang IS NULL) as is_online,
                       (SELECT COUNT(*) FROM izin iz WHERE iz.id_profil = p.id_profil AND iz.status_approval = 'Approved' AND CURDATE() BETWEEN iz.start_date AND iz.end_date) as is_on_leave
                FROM user u 
                JOIN profile p ON u.id_user = p.id_user 
                LEFT JOIN lab l ON p.id_lab = l.id_lab 
                ORDER BY
                   CASE
                     WHEN u.role = 'Kepala Lab' THEN 1
                     WHEN u.role = 'Admin' AND p.jabatan LIKE '%Laboran%'      THEN 2
                     WHEN u.role = 'Admin' AND p.jabatan LIKE '%Koordinator%'  THEN 3
                     WHEN u.role = 'Admin'                                     THEN 4
                     WHEN u.role = 'User' AND p.jabatan LIKE '%Asisten 1%'     THEN 5
                     WHEN u.role = 'User' AND p.jabatan LIKE '%Asisten 2%'     THEN 6
                     WHEN u.role = 'User' AND p.jabatan LIKE '%Pendamping%'    THEN 7
                     WHEN u.role = 'User'                                      THEN 8
                     ELSE 9
                   END,
                   p.nama ASC";
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
                // [PERBAIKAN] Hadir+Terlambat = hadir (Modul 1 V3) — lihat juga
                // catatan di AttendanceModel::getUserStats().
                $sql = "SELECT p.nama, p.jabatan, p.photo_profile, COUNT(pr.id_presensi) as score
                        FROM profile p
                        JOIN presensi pr ON p.id_profil = pr.id_profil
                        WHERE pr.status IN ('Hadir', 'Terlambat')
                        GROUP BY p.id_profil ORDER BY score DESC LIMIT 10";
                break;
            case 'jarang':
                $sql = "SELECT p.nama, p.jabatan, p.photo_profile, COUNT(pr.id_presensi) as score
                        FROM profile p
                        JOIN user u ON p.id_user = u.id_user
                        LEFT JOIN presensi pr ON p.id_profil = pr.id_profil AND pr.status IN ('Hadir', 'Terlambat')
                        WHERE u.role = 'User'
                        GROUP BY p.id_profil ORDER BY score ASC LIMIT 10";
                break;
            case 'cepat':
                // [PERBAIKAN] "Rata-rata jam masuk" harus mencakup check-in
                // 'Terlambat' juga, bukan hanya yang tepat waktu — kalau tidak,
                // rata-rata akan bias ke jam lebih pagi (Modul 1 V3).
                // [PERBAIKAN] AVG() menghasilkan DECIMAL (bisa pecahan detik),
                // sehingga SEC_TO_TIME() sebelumnya menampilkan sisa desimal
                // (mis. "08:15:30.500000") - dibungkus ROUND() dulu supaya
                // hasilnya selalu HH:MM:SS bulat tanpa digit di belakang koma.
                $sql = "SELECT p.nama, p.jabatan, p.photo_profile, SEC_TO_TIME(ROUND(AVG(TIME_TO_SEC(pr.waktu_presensi)))) as score
                        FROM profile p
                        JOIN presensi pr ON p.id_profil = pr.id_profil
                        WHERE pr.status IN ('Hadir', 'Terlambat')
                        GROUP BY p.id_profil ORDER BY score ASC LIMIT 10";
                break;
            case 'terlambat':
                $sql = "SELECT p.nama, p.jabatan, p.photo_profile, SEC_TO_TIME(ROUND(AVG(TIME_TO_SEC(pr.waktu_presensi)))) as score
                        FROM profile p
                        JOIN presensi pr ON p.id_profil = pr.id_profil
                        WHERE pr.status IN ('Hadir', 'Terlambat')
                        GROUP BY p.id_profil ORDER BY score DESC LIMIT 10";
                break;
            case 'sering_izin':
                $sql = "SELECT p.nama, p.jabatan, p.photo_profile, COUNT(i.id_izin) as score
                        FROM profile p
                        JOIN izin i ON p.id_profil = i.id_profil
                        WHERE i.status_approval = 'Approved'
                        GROUP BY p.id_profil ORDER BY score DESC LIMIT 10";
                break;
            case 'logbook_lengkap':
                $sql = "SELECT p.nama, p.jabatan, p.photo_profile, FLOOR(AVG(LENGTH(l.detail_aktivitas) - LENGTH(REPLACE(l.detail_aktivitas, ' ', '')) + 1)) as score
                        FROM profile p
                        JOIN logbook l ON p.id_profil = l.id_profil
                        GROUP BY p.id_profil ORDER BY score DESC LIMIT 10";
                break;
            case 'logbook_singkat':
                $sql = "SELECT p.nama, p.jabatan, p.photo_profile, FLOOR(AVG(LENGTH(l.detail_aktivitas) - LENGTH(REPLACE(l.detail_aktivitas, ' ', '')) + 1)) as score
                        FROM profile p
                        JOIN logbook l ON p.id_profil = l.id_profil
                        GROUP BY p.id_profil ORDER BY score ASC LIMIT 10";
                break;
            case 'sibuk':
                $sql = "SELECT p.nama, p.jabatan, p.photo_profile, COUNT(ja.id_jadwal_asisten) as score
                        FROM profile p
                        JOIN jadwal_asisten ja ON p.id_profil = ja.id_profil
                        GROUP BY p.id_profil ORDER BY score DESC LIMIT 10";
                break;
            case 'santai':
                $sql = "SELECT p.nama, p.jabatan, p.photo_profile, COUNT(ja.id_jadwal_asisten) as score
                        FROM profile p
                        JOIN user u ON p.id_user = u.id_user
                        LEFT JOIN jadwal_asisten ja ON p.id_profil = ja.id_profil
                        WHERE u.role = 'User'
                        GROUP BY p.id_profil ORDER BY score ASC LIMIT 10";
                break;
        }

        if(empty($sql)) return [];
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Data performa SELURUH asisten (tanpa LIMIT) untuk chart "Performa Asisten"
     * di dashboard Admin & Kepala Lab. Beda dengan getAssistantRankings() yang
     * hanya mengambil Top 5 untuk satu arah (rajin/jarang dsb), method ini
     * mengembalikan satu baris per asisten sehingga urutan lengkap (termasuk
     * yang berada di posisi bawah) bisa digambarkan dalam satu chart.
     *
     * @param string   $metric 'kehadiran' | 'jam_masuk' | 'izin' | 'logbook' | 'jadwal'
     * @param int|null $idLab  Jika diisi, hanya asisten pada lab tersebut (untuk Kepala Lab).
     * @return array[] ['name' => string, 'score' => float]
     */
    public function getAssistantPerformanceData($metric, $idLab = null) {
        $labFilter = $idLab ? "AND p.id_lab = :id_lab" : "";

        switch ($metric) {
            case 'jam_masuk':
                // Rata-rata jam masuk (dalam menit sejak 00:00). Hanya asisten yang
                // pernah check-in minimal sekali yang muncul (rata-rata dari 0 data
                // tidak bermakna & bisa menyesatkan jika dipaksa jadi 0 = 00:00).
                // [PERBAIKAN] Sertakan status 'Terlambat' juga — kalau tidak,
                // rata-rata akan bias ke jam lebih pagi (Modul 1 V3).
                $sql = "SELECT p.nama as name, 
                               ROUND(AVG(TIME_TO_SEC(pr.waktu_presensi)) / 60) as score
                        FROM profile p
                        JOIN user u ON p.id_user = u.id_user
                        JOIN presensi pr ON p.id_profil = pr.id_profil AND pr.status IN ('Hadir', 'Terlambat')
                        WHERE u.role = 'User' $labFilter
                        GROUP BY p.id_profil, p.nama
                        ORDER BY score ASC, p.nama ASC";
                break;
            case 'izin':
                $sql = "SELECT p.nama as name, COUNT(i.id_izin) as score
                        FROM profile p
                        JOIN user u ON p.id_user = u.id_user
                        LEFT JOIN izin i ON p.id_profil = i.id_profil AND i.status_approval = 'Approved'
                        WHERE u.role = 'User' $labFilter
                        GROUP BY p.id_profil, p.nama
                        ORDER BY score DESC, p.nama ASC";
                break;
            case 'logbook':
                $sql = "SELECT p.nama as name, 
                               FLOOR(AVG(LENGTH(l.detail_aktivitas) - LENGTH(REPLACE(l.detail_aktivitas, ' ', '')) + 1)) as score
                        FROM profile p
                        JOIN user u ON p.id_user = u.id_user
                        LEFT JOIN logbook l ON p.id_profil = l.id_profil
                        WHERE u.role = 'User' $labFilter
                        GROUP BY p.id_profil, p.nama
                        ORDER BY score DESC, p.nama ASC";
                break;
            case 'jadwal':
                $sql = "SELECT p.nama as name, COUNT(ja.id_jadwal_asisten) as score
                        FROM profile p
                        JOIN user u ON p.id_user = u.id_user
                        LEFT JOIN jadwal_asisten ja ON p.id_profil = ja.id_profil
                        WHERE u.role = 'User' $labFilter
                        GROUP BY p.id_profil, p.nama
                        ORDER BY score DESC, p.nama ASC";
                break;
            case 'durasi_kerja':
                // [BARU - Modul 1 V3] Rata-rata durasi kerja (menit), dihitung
                // dari hari-hari yang sudah check-out (work_duration terisi).
                // Hanya asisten dengan minimal 1 data work_duration yang muncul
                // (sama seperti pola 'jam_masuk').
                $sql = "SELECT p.nama as name, 
                               ROUND(AVG(pr.work_duration)) as score
                        FROM profile p
                        JOIN user u ON p.id_user = u.id_user
                        JOIN presensi pr ON p.id_profil = pr.id_profil AND pr.work_duration IS NOT NULL
                        WHERE u.role = 'User' $labFilter
                        GROUP BY p.id_profil, p.nama
                        ORDER BY score DESC, p.nama ASC";
                break;
            case 'kehadiran':
            default:
                // [PERBAIKAN] Hadir+Terlambat = hadir (Modul 1 V3).
                $sql = "SELECT p.nama as name, COUNT(pr.id_presensi) as score
                        FROM profile p
                        JOIN user u ON p.id_user = u.id_user
                        LEFT JOIN presensi pr ON p.id_profil = pr.id_profil AND pr.status IN ('Hadir', 'Terlambat')
                        WHERE u.role = 'User' $labFilter
                        GROUP BY p.id_profil, p.nama
                        ORDER BY score DESC, p.nama ASC";
                break;
        }

        $stmt = $this->conn->prepare($sql);
        if ($idLab) {
            $stmt->bindValue(':id_lab', $idLab, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['score'] = $row['score'] !== null ? (float)$row['score'] : 0;
        }

        return $rows;
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

    
    public function isGoogleConnected($userId)
    {
        $this->db->query("
            SELECT id_token 
            FROM user_google_token 
            WHERE id_user = :uid
        ");
        $this->db->bind(':uid', $userId);
        return $this->db->rowCount() > 0;
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

    public function getTotalAssistants()
    {
        $this->db->query("SELECT COUNT(*) as total FROM user WHERE role = 'User'");
        return $this->db->single()['total'];
    }

    public function getAssistantsWithProfile()
    {
        $this->db->query("
            SELECT u.id_user, u.email, u.created_at, u.status_account,
                p.id_profil, p.nama as name, p.photo_profile, p.jabatan, 
                p.nim, p.no_telp, p.alamat, p.prodi, p.kelas, p.is_completed 
            FROM user u 
            JOIN profile p ON u.id_user = p.id_user 
            WHERE u.role = 'User' 
            ORDER BY p.nama ASC
        ");
        return $this->db->resultSet();
    }

    public function getUsersWithProfileAndLab($keyword = null, $limit = 10, $offset = 0, $roleFilter = null, $jabatanFilter = null, $angkatanFilter = null)
    {
        $sql = "
            SELECT u.id_user as id, u.email, u.role, u.status_account,
                p.id_profil, p.nama as name, p.photo_profile, p.jabatan as position, 
                p.nim, p.kelas, p.angkatan, p.prodi, p.no_telp, p.alamat, 
                p.jenis_kelamin, p.peminatan, p.is_completed,
                p.id_lab, l.nama_lab as lab_name,
                (SELECT COUNT(*) FROM presensi pr WHERE pr.id_profil = p.id_profil AND pr.tanggal = CURDATE() AND pr.waktu_pulang IS NULL) as is_online,
                (SELECT COUNT(*) FROM izin iz WHERE iz.id_profil = p.id_profil AND iz.status_approval = 'Approved' AND CURDATE() BETWEEN iz.start_date AND iz.end_date) as is_on_leave
            FROM user u
            JOIN profile p ON u.id_user = p.id_user
            LEFT JOIN lab l ON p.id_lab = l.id_lab
        ";

        // [FIX] Sebelumnya hanya $keyword yang dipakai di WHERE — $roleFilter,
        // $jabatanFilter, $angkatanFilter diterima sebagai parameter tapi TIDAK
        // PERNAH dipakai di query ini, sehingga daftar yang ditampilkan selalu
        // berisi SEMUA user (tidak tersaring), padahal countUsersWithProfileAndLab()
        // di bawah sudah benar menghitung total sesuai filter — akibatnya jumlah
        // halaman/total sesuai filter, tapi baris yang tampil tidak sesuai filter.
        $conds = [];
        if ($keyword)        { $conds[] = "(p.nama LIKE :key OR p.nim LIKE :key2 OR u.email LIKE :key3)"; }
        if ($roleFilter)     { $conds[] = "u.role = :role"; }
        if ($jabatanFilter)  { $conds[] = "p.jabatan = :jabatan"; }
        if ($angkatanFilter) { $conds[] = "p.angkatan = :angkatan"; }
        if ($conds) $sql .= " WHERE " . implode(" AND ", $conds);

        $sql .= " ORDER BY
                   CASE
                     WHEN u.role = 'Kepala Lab' THEN 1
                     WHEN u.role = 'Admin' AND p.jabatan LIKE '%Laboran%'     THEN 2
                     WHEN u.role = 'Admin' AND p.jabatan LIKE '%Koordinator%' THEN 3
                     WHEN u.role = 'Admin'                                    THEN 4
                     WHEN u.role = 'User' AND p.jabatan LIKE '%Asisten 1%'    THEN 5
                     WHEN u.role = 'User' AND p.jabatan LIKE '%Asisten 2%'    THEN 6
                     WHEN u.role = 'User' AND p.jabatan LIKE '%Pendamping%'   THEN 7
                     WHEN u.role = 'User'                                     THEN 8
                     ELSE 9
                   END, p.nama ASC
                LIMIT :limit OFFSET :offset";

        $this->db->query($sql);

        if ($keyword) {
            $this->db->bind(':key', "%$keyword%");
            $this->db->bind(':key2', "%$keyword%");
            $this->db->bind(':key3', "%$keyword%");
        }
        if ($roleFilter)     $this->db->bind(':role', $roleFilter);
        if ($jabatanFilter)  $this->db->bind(':jabatan', $jabatanFilter);
        if ($angkatanFilter) $this->db->bind(':angkatan', $angkatanFilter);

        $this->db->bind(':limit', (int)$limit);
        $this->db->bind(':offset', (int)$offset);

        return $this->db->resultSet();
    }

    public function countUsersWithProfileAndLab($keyword = null, $roleFilter = null, $jabatanFilter = null, $angkatanFilter = null)
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM user u
            JOIN profile p ON u.id_user = p.id_user
            LEFT JOIN lab l ON p.id_lab = l.id_lab
        ";

        $conds = [];
        if ($keyword) { $conds[] = "(p.nama LIKE :key OR p.nim LIKE :key2 OR u.email LIKE :key3)"; }
        if ($roleFilter) { $conds[] = "u.role = :role"; }
        if ($jabatanFilter) { $conds[] = "p.jabatan = :jabatan"; }
        if ($angkatanFilter) { try { $conds[] = "p.angkatan = :angkatan"; } catch(\Throwable $e){} }
        if ($conds) $sql .= " WHERE " . implode(" AND ", $conds);

        $this->db->query($sql);
        if ($keyword) { $this->db->bind(':key', "%$keyword%"); $this->db->bind(':key2', "%$keyword%"); $this->db->bind(':key3', "%$keyword%"); }
        if ($roleFilter) $this->db->bind(':role', $roleFilter);
        if ($jabatanFilter) $this->db->bind(':jabatan', $jabatanFilter);
        if ($angkatanFilter) { try { $this->db->bind(':angkatan', $angkatanFilter); } catch(\Throwable $e){} }

        return $this->db->single()['total'];
    }

    public function getTotalManagedUsers()
    {
        $this->db->query("
            SELECT COUNT(*) as total 
            FROM user 
            WHERE role = 'User'
        ");
        return $this->db->single()['total'];
    }

    
}
?>