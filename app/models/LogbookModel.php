<?php
class LogbookModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // 1. Digunakan oleh Admin & SuperAdmin (List Semua Logbook)
    public function getAllWithUserInfo() {
        $sql = "SELECT l.id_logbook as id, l.detail_aktivitas as activity_detail, 
                       pr.tanggal as date, pr.waktu_presensi, pr.waktu_pulang,
                       pr.foto_presensi as foto_bukti,
                       p.nama as user_name, p.id_user, l.id_presensi
                FROM logbook l 
                JOIN profile p ON l.id_profil = p.id_profil 
                JOIN presensi pr ON l.id_presensi = pr.id_presensi
                ORDER BY pr.tanggal DESC";
        
        $this->db->query($sql);
        return $this->db->resultSet();
    }

    public function countTotal() {
        $this->db->query("SELECT COUNT(*) as total FROM logbook");
        return $this->db->single();
    }

    public function getUserLogbookHistory($userId) {
        $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
        $this->db->bind(':uid', $userId);
        $result = $this->db->single();
        $pId = $result['id_profil'] ?? false;

        if (!$pId) return [];

        $sql = "SELECT 
                    pr.tanggal as date, 
                    pr.waktu_presensi as check_in_time, 
                    pr.waktu_pulang as check_out_time, 
                    pr.foto_presensi as foto_bukti,
                    pr.foto_pulang as foto_pulang,
                    l.detail_aktivitas as activity_detail,
                    l.id_logbook as log_id
                FROM presensi pr
                LEFT JOIN logbook l ON pr.id_presensi = l.id_presensi
                WHERE pr.id_profil = :pid
                ORDER BY pr.tanggal DESC LIMIT 30";
        
        $this->db->query($sql);
        $this->db->bind(':pid', $pId);
        return $this->db->resultSet();
    }

    public function saveLogbook($data) {
        // A. Cari id_profil
        $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
        $this->db->bind(':uid', $data['user_id']);
        $resP = $this->db->single();
        $pId = $resP['id_profil'] ?? false;

        if (!$pId) return false;

        // B. Cari id_presensi pada tanggal tersebut
        $this->db->query("SELECT id_presensi FROM presensi WHERE id_profil = :pid AND tanggal = :date");
        $this->db->bind(':pid', $pId);
        $this->db->bind(':date', $data['date']);
        $presensi = $this->db->single();

        if (!$presensi) return false; 
        $idPresensi = $presensi['id_presensi'];

        // C. Cek Logbook Existing (PERBAIKAN LOGIKA)
        // Gunakan single() agar query benar-benar dieksekusi sebelum dicek
        $this->db->query("SELECT id_logbook FROM logbook WHERE id_presensi = :idp");
        $this->db->bind(':idp', $idPresensi);
        $existingLog = $this->db->single(); // Eksekusi Query
        
        if ($existingLog) {
            // Update jika data sudah ada
            $sql = "UPDATE logbook SET detail_aktivitas = :act WHERE id_presensi = :idp";
            $this->db->query($sql);
            $this->db->bind(':idp', $idPresensi);
            $this->db->bind(':act', $data['activity']);
        } else {
            // Insert jika data belum ada
            $sql = "INSERT INTO logbook (id_profil, id_presensi, detail_aktivitas, is_verified) 
                    VALUES (:pid, :idp, :act, 0)";
            $this->db->query($sql);
            $this->db->bind(':pid', $pId);
            $this->db->bind(':idp', $idPresensi);
            $this->db->bind(':act', $data['activity']);
        }

        return $this->db->execute();
    }

    // --- Method Tambahan untuk Admin ---
    public function getAllLogs() {
        return $this->getAllWithUserInfo();
    }

    public function getLogsByUserIdForAdmin($userId) {
        $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
        $this->db->bind(':uid', $userId);
        $pid = $this->db->single()['id_profil'] ?? null;
        if(!$pid) return [];

        $sql = "SELECT 
                    pr.id_presensi,
                    l.id_logbook,
                    pr.tanggal as date, 
                    pr.waktu_presensi as time_in, 
                    pr.waktu_pulang as time_out, 
                    pr.foto_presensi as foto_bukti,
                    pr.foto_pulang as foto_pulang,
                    l.detail_aktivitas as activity
                FROM presensi pr
                LEFT JOIN logbook l ON pr.id_presensi = l.id_presensi
                WHERE pr.id_profil = :pid
                ORDER BY pr.tanggal DESC";
        
        $this->db->query($sql);
        $this->db->bind(':pid', $pid);
        return $this->db->resultSet();
    }

    public function saveLogAdmin($data) {
        try {
            $this->db->getConnection()->beginTransaction();

            // 1. Ambil ID Profil
            $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
            $this->db->bind(':uid', $data['user_id']);
            $pid = $this->db->single()['id_profil'] ?? null;
            if(!$pid) return false;

            $date = $data['date'];
            $status = $data['status']; // Hadir, Izin, Sakit, Alpha

            // 2. Cek Data Eksisting
            // Cek Presensi
            $this->db->query("SELECT id_presensi FROM presensi WHERE id_profil = :pid AND tanggal = :date");
            $this->db->bind(':pid', $pid);
            $this->db->bind(':date', $date);
            $existPresensi = $this->db->single();

            // Cek Izin
            $this->db->query("SELECT id_izin FROM izin WHERE id_profil = :pid AND :date BETWEEN start_date AND end_date");
            $this->db->bind(':pid', $pid);
            $this->db->bind(':date', $date);
            $existIzin = $this->db->single();

            // --- SKENARIO 1: STATUS BARU = HADIR ---
            if ($status == 'Hadir') {
                // A. Bersihkan data Izin jika ada (Konflik)
                if ($existIzin) {
                    $this->db->query("DELETE FROM izin WHERE id_izin = :id");
                    $this->db->bind(':id', $existIzin['id_izin']);
                    $this->db->execute();
                }

                // B. Insert/Update Presensi
                $file = $data['file'] ?? ($existPresensi ? null : 'admin_manual.jpg'); // Default jika tidak upload
                
                if ($existPresensi) {
                    $idPresensi = $existPresensi['id_presensi'];
                    $sql = "UPDATE presensi SET waktu_presensi = :tin, waktu_pulang = :tout";
                    if ($data['file']) $sql .= ", foto_presensi = :foto";
                    $sql .= " WHERE id_presensi = :id";
                    
                    $this->db->query($sql);
                    $this->db->bind(':tin', $data['time_in']);
                    $this->db->bind(':tout', $data['time_out']);
                    $this->db->bind(':id', $idPresensi);
                    if ($data['file']) $this->db->bind(':foto', $data['file']);
                    $this->db->execute();
                } else {
                    $sql = "INSERT INTO presensi (id_profil, tanggal, waktu_presensi, waktu_pulang, status, foto_presensi) 
                            VALUES (:pid, :date, :tin, :tout, 'Hadir', :foto)";
                    $this->db->query($sql);
                    $this->db->bind(':pid', $pid);
                    $this->db->bind(':date', $date);
                    $this->db->bind(':tin', $data['time_in']);
                    $this->db->bind(':tout', $data['time_out']);
                    $this->db->bind(':foto', $file);
                    $this->db->execute();
                    $idPresensi = $this->db->getConnection()->lastInsertId();
                }

                // C. Insert/Update Logbook
                // Cek Logbook
                $this->db->query("SELECT id_logbook FROM logbook WHERE id_presensi = :idp");
                $this->db->bind(':idp', $idPresensi);
                $existLog = $this->db->single();

                if ($existLog) {
                    $this->db->query("UPDATE logbook SET detail_aktivitas = :act WHERE id_presensi = :idp");
                } else {
                    $this->db->query("INSERT INTO logbook (id_profil, id_presensi, detail_aktivitas, is_verified) VALUES (:pid, :idp, :act, 1)");
                    $this->db->bind(':pid', $pid);
                }
                $this->db->bind(':idp', $idPresensi);
                $this->db->bind(':act', $data['activity']);
                $this->db->execute();
            }

            // --- SKENARIO 2: STATUS BARU = IZIN / SAKIT ---
            else if ($status == 'Izin' || $status == 'Sakit') {
                // A. Bersihkan Presensi (dan Logbook via Cascade) jika ada
                if ($existPresensi) {
                    $this->db->query("DELETE FROM presensi WHERE id_presensi = :id");
                    $this->db->bind(':id', $existPresensi['id_presensi']);
                    $this->db->execute();
                }

                // B. Insert/Update Izin
                if ($existIzin) {
                    $sql = "UPDATE izin SET tipe = :type, deskripsi = :desc, status_approval = 'Approved'";
                    if ($data['file']) $sql .= ", file_bukti = :file";
                    $sql .= " WHERE id_izin = :id";

                    $this->db->query($sql);
                    $this->db->bind(':id', $existIzin['id_izin']);
                } else {
                    $sql = "INSERT INTO izin (id_profil, tipe, start_date, end_date, deskripsi, file_bukti, status_approval) 
                            VALUES (:pid, :type, :date, :date, :desc, :file, 'Approved')";
                    $this->db->query($sql);
                    $this->db->bind(':pid', $pid);
                    $this->db->bind(':date', $date);
                    // Jika file kosong saat insert baru, gunakan placeholder/null
                    $this->db->bind(':file', $data['file'] ?? null);
                }
                
                $this->db->bind(':type', $status);
                $this->db->bind(':desc', $data['activity']); // Gunakan input aktivitas sebagai deskripsi izin
                if ($existIzin && $data['file']) $this->db->bind(':file', $data['file']);
                
                $this->db->execute();
            }

            // --- SKENARIO 3: STATUS BARU = ALPHA (HAPUS DATA) ---
            else if ($status == 'Alpha') {
                if ($existPresensi) {
                    $this->db->query("DELETE FROM presensi WHERE id_presensi = :id");
                    $this->db->bind(':id', $existPresensi['id_presensi']);
                    $this->db->execute();
                }
                if ($existIzin) {
                    $this->db->query("DELETE FROM izin WHERE id_izin = :id");
                    $this->db->bind(':id', $existIzin['id_izin']);
                    $this->db->execute();
                }
            }

            $this->db->getConnection()->commit();
            return true;

        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            return false;
        }
    }

    public function deleteLogAdmin($idLogbook) {
        $this->db->query("DELETE FROM logbook WHERE id_logbook = :id");
        $this->db->bind(':id', $idLogbook);
        return $this->db->execute();
    }

    public function getUnifiedLogbook($userId) {
        $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
        $this->db->bind(':uid', $userId);
        $res = $this->db->single();
        if(!$res) return [];
        $pId = $res['id_profil'];

        // Ambil Data Presensi & Logbook
        $sqlPresensi = "SELECT 
                            pr.id_presensi, pr.tanggal, pr.waktu_presensi, pr.waktu_pulang, 
                            pr.foto_presensi, pr.foto_pulang, pr.status as status_db,
                            l.id_logbook, l.detail_aktivitas, l.is_verified
                        FROM presensi pr
                        LEFT JOIN logbook l ON pr.id_presensi = l.id_presensi
                        WHERE pr.id_profil = :pid";
        $this->db->query($sqlPresensi);
        $this->db->bind(':pid', $pId);
        $rawPresensi = $this->db->resultSet();

        // Ambil Data Izin (Approved Only)
        $sqlIzin = "SELECT * FROM izin 
                    WHERE id_profil = :pid AND status_approval = 'Approved'";
        $this->db->query($sqlIzin);
        $this->db->bind(':pid', $pId);
        $rawIzin = $this->db->resultSet();

        // RESTRUKTURISASI DATA
        $unifiedData = [];
        $today = new DateTime();
        
        for ($i = 0; $i < 30; $i++) {
            $checkDate = (clone $today)->modify("-$i days")->format('Y-m-d');
            $entry = [
                'date' => $checkDate,
                'status' => 'Alpha', 
                'color' => 'red',    
                'time_in' => '-',
                'time_out' => '-',
                'proof_in' => null,  
                'proof_out' => null, 
                'activity' => 'Tidak Hadir',
                'is_locked' => true, 
                'log_id' => null,    
                'id_ref' => null,    
                'can_reset' => false 
            ];

            // 1. CEK PRESENSI
            $foundP = array_filter($rawPresensi, fn($row) => $row['tanggal'] == $checkDate);
            if (!empty($foundP)) {
                $p = reset($foundP);
                $entry['status'] = 'Hadir';
                $entry['color'] = 'green';
                $entry['time_in'] = $p['waktu_presensi'] ? date('H:i', strtotime($p['waktu_presensi'])) : '-';
                $entry['time_out'] = $p['waktu_pulang'] ? date('H:i', strtotime($p['waktu_pulang'])) : '-';
                $entry['proof_in'] = $p['foto_presensi'];
                $entry['proof_out'] = $p['foto_pulang'];
                $entry['activity'] = $p['detail_aktivitas'] ?? '';
                $entry['log_id'] = $p['id_logbook'];
                $entry['id_ref'] = $p['id_presensi'];
                
                $entry['is_locked'] = false; 
                $entry['can_reset'] = true; 
            } 
            // 2. JIKA TIDAK HADIR, CEK IZIN (Rentang Tanggal)
            else {
                foreach ($rawIzin as $iz) {
                    if ($checkDate >= $iz['start_date'] && $checkDate <= $iz['end_date']) {
                        // [FIXED] Menggunakan nama kolom DB yang benar
                        $entry['status'] = $iz['tipe']; // DB: tipe
                        $entry['color'] = 'yellow';
                        
                        // [FIXED] Tabel izin tidak punya created_at, gunakan '-'
                        $entry['time_in'] = '-'; 
                        
                        $entry['proof_in'] = $iz['file_bukti']; 
                        
                        // [FIXED] DB: deskripsi
                        $entry['activity'] = ($iz['deskripsi'] ?? '') . " (Pengajuan Izin)";
                        
                        $entry['id_ref'] = $iz['id_izin'];
                        $entry['is_locked'] = true; 
                        $entry['can_reset'] = false;
                        break;
                    }
                }
            }

            // 3. JIKA MASIH ALPHA
            if ($entry['status'] == 'Alpha') {
                if ($checkDate == date('Y-m-d') && date('H:i') < '18:00') {
                    continue; 
                }
                $entry['time_out'] = '18:00'; 
                $entry['activity'] = 'Tidak Hadir (Alpha)';
            }

            $unifiedData[] = $entry;
        }

        return $unifiedData;
    }

    public function resetLogUser($logId, $userId) {
        $sql = "UPDATE logbook l
                JOIN profile p ON l.id_profil = p.id_profil
                SET l.detail_aktivitas = NULL
                WHERE l.id_logbook = :lid AND p.id_user = :uid";
        
        $this->db->query($sql);
        $this->db->bind(':lid', $logId);
        $this->db->bind(':uid', $userId);
        return $this->db->execute();
    }

    public function resetLogAdmin($idRef, $type, $mode) {
        try {
            $this->db->getConnection()->beginTransaction();

            if ($type == 'Hadir') {
                if ($mode == 'partial') {
                    $this->db->query("UPDATE logbook SET detail_aktivitas = NULL WHERE id_presensi = :id");
                    $this->db->bind(':id', $idRef);
                    $this->db->execute();
                } elseif ($mode == 'full') {
                    $this->db->query("DELETE FROM logbook WHERE id_presensi = :id");
                    $this->db->bind(':id', $idRef);
                    $this->db->execute();

                    $this->db->query("DELETE FROM presensi WHERE id_presensi = :id");
                    $this->db->bind(':id', $idRef);
                    $this->db->execute();
                }
            } 
            
            $this->db->getConnection()->commit();
            return true;
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            return false;
        }
    }
}
?>