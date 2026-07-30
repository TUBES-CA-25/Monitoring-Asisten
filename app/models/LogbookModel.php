<?php
require_once __DIR__ . '/../services/AttendanceAutoService.php';
// [BARU] Dibutuhkan untuk UserModel::WORK_DAYS_MAX_DOW (lihat
// getUnifiedLogbook()) - di-require eksplisit di sini karena Controller::
// model() memakai require_once PER MODEL YANG DIMINTA; endpoint yang
// memanggil getUnifiedLogbook() tidak semuanya turut memuat UserModel
// lebih dulu (mis. AdminController::getLogsByUser()).
require_once __DIR__ . '/UserModel.php';

class LogbookModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

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
        $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
        $this->db->bind(':uid', $data['user_id']);
        $resP = $this->db->single();
        $pId = $resP['id_profil'] ?? false;

        if (!$pId) return false;

        if (!empty($data['log_id'])) {
        // [BARU - Modul 3 V3] Cek dulu apakah log_id ini benar milik profil
        // ini (ownership validation) SEBELUM UPDATE. Tidak memakai rowCount()
        // dari UPDATE karena MySQL mengembalikan rowCount()=0 juga jika data
        // TIDAK BERUBAH (bukan hanya jika tidak match) - bisa salah dianggap
        // "not_found" untuk edit yang isinya sama dengan sebelumnya.
        $this->db->query("SELECT id_logbook FROM logbook WHERE id_logbook = :lid AND id_profil = :pid");
        $this->db->bind(':lid', $data['log_id']);
        $this->db->bind(':pid', $pId);
        if (!$this->db->single()) return 'not_found';

        $sql = "UPDATE logbook SET detail_aktivitas = :act WHERE id_logbook = :lid AND id_profil = :pid";
        $this->db->query($sql);
        $this->db->bind(':lid', $data['log_id']);
        $this->db->bind(':pid', $pId);
        $this->db->bind(':act', $data['activity']);
        return $this->db->execute();
    }

        $this->db->query("SELECT id_presensi FROM presensi WHERE id_profil = :pid AND tanggal = :date");
        $this->db->bind(':pid', $pId);
        $this->db->bind(':date', $data['date']);
        $presensi = $this->db->single();

        if (!$presensi) return false; 
        $idPresensi = $presensi['id_presensi'];

        $this->db->query("SELECT id_logbook FROM logbook WHERE id_presensi = :idp");
        $this->db->bind(':idp', $idPresensi);
        $existingLog = $this->db->single(); 
        
        if ($existingLog) {
            $sql = "UPDATE logbook SET detail_aktivitas = :act WHERE id_presensi = :idp";
            $this->db->query($sql);
            $this->db->bind(':idp', $idPresensi);
            $this->db->bind(':act', $data['activity']);
        } else {
            $sql = "INSERT INTO logbook (id_profil, id_presensi, detail_aktivitas, is_verified) 
                    VALUES (:pid, :idp, :act, 0)";
            $this->db->query($sql);
            $this->db->bind(':pid', $pId);
            $this->db->bind(':idp', $idPresensi);
            $this->db->bind(':act', $data['activity']);
        }

        return $this->db->execute();
    }

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

            $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
            $this->db->bind(':uid', $data['user_id']);
            $pid = $this->db->single()['id_profil'] ?? null;
            if(!$pid) return false;

            $date = $data['date'];
            $status = $data['status'];

            $this->db->query("SELECT id_presensi FROM presensi WHERE id_profil = :pid AND tanggal = :date");
            $this->db->bind(':pid', $pid);
            $this->db->bind(':date', $date);
            $existPresensi = $this->db->single();

            $this->db->query("SELECT id_izin FROM izin WHERE id_profil = :pid AND :date BETWEEN start_date AND end_date");
            $this->db->bind(':pid', $pid);
            $this->db->bind(':date', $date);
            $existIzin = $this->db->single();

            if ($status == 'Hadir') {
                if ($existIzin) {
                    $this->db->query("DELETE FROM izin WHERE id_izin = :id");
                    $this->db->bind(':id', $existIzin['id_izin']);
                    $this->db->execute();
                }

                $file = $data['file'] ?? ($existPresensi ? null : 'admin_manual.jpg'); 
                
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

            else if ($status == 'Izin' || $status == 'Sakit') {
                if ($existPresensi) {
                    $this->db->query("DELETE FROM presensi WHERE id_presensi = :id");
                    $this->db->bind(':id', $existPresensi['id_presensi']);
                    $this->db->execute();
                }

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
                    $this->db->bind(':file', $data['file'] ?? null);
                }
                
                $this->db->bind(':type', $status);
                $this->db->bind(':desc', $data['activity']); 
                if ($existIzin && $data['file']) $this->db->bind(':file', $data['file']);
                
                $this->db->execute();
            }

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

    public function resetLogAdmin($idRef, $type, $mode) {
        $this->db->getConnection()->beginTransaction();

        try {
            if ($type == 'Hadir') {
                if ($mode == 'partial') {
                    $this->db->query("UPDATE logbook SET detail_aktivitas = NULL WHERE id_presensi = :id");
                    $this->db->bind(':id', $idRef);
                    $this->db->execute();
                } 
                elseif ($mode == 'full') {
                    $this->db->query("DELETE FROM logbook WHERE id_presensi = :id");
                    $this->db->bind(':id', $idRef);
                    $this->db->execute();
                    $this->db->query("DELETE FROM presensi WHERE id_presensi = :id");
                    $this->db->bind(':id', $idRef);
                    $this->db->execute();
                }
            } 
            // [PERBAIKAN – Tahap 35] Sebelumnya hanya handle 'Izin', sehingga
            // tipe 'Sakit' melewati kedua kondisi tanpa menghapus data.
            // Akibatnya alpha count tidak berubah saat entri Sakit dihapus.
            elseif (in_array($type, ['Izin', 'Sakit'])) {
                $this->db->query("DELETE FROM izin WHERE id_izin = :id");
                $this->db->bind(':id', $idRef);
                $this->db->execute();
            }

            $this->db->getConnection()->commit();
            return true;

        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            return false;
        }
    }
    
    public function deleteLogAdmin($idLogbook) {
        try {
            $this->db->query("DELETE FROM logbook WHERE id_logbook = :id");
            $this->db->bind(':id', $idLogbook);
            return $this->db->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * getUnifiedLogbook — Riwayat lengkap logbook seorang asisten.
     *
     * Menampilkan semua hari sejak profil asisten ter-verifikasi (is_completed=1),
     * dengan status hadir/izin/alpha dihitung dari data presensi dan izin.
     *
     * @param  int $userId   ID user asisten
     * @param  int $page     Halaman (default 1)
     * @param  int $perPage  Baris per halaman (default 30)
     * @return array {data, total, hadir, izin, alpha, page, per_page, total_pages}
     */
    public function getUnifiedLogbook($userId, $page = 1, $perPage = 30) {
        // 1. Ambil profil: id_profil + tanggal_bergabung (dari profile.created_at)
        $this->db->query("SELECT id_profil, created_at FROM profile
                          WHERE id_user = :uid AND is_completed = 1
                          ORDER BY id_profil DESC LIMIT 1");
        $this->db->bind(':uid', $userId);
        $prof = $this->db->single();

        // Fallback: jika profil belum lengkap, ambil tanpa filter is_completed
        if (!$prof) {
            $this->db->query("SELECT id_profil, created_at FROM profile WHERE id_user = :uid LIMIT 1");
            $this->db->bind(':uid', $userId);
            $prof = $this->db->single();
        }
        if (!$prof) return ['data'=>[],'total'=>0,'hadir'=>0,'izin'=>0,'alpha'=>0,'page'=>1,'per_page'=>$perPage,'total_pages'=>0];

        $pId        = $prof['id_profil'];

        // [BARU - SINKRONISASI DATA] Sebelumnya titik mulai HANYA memakai
        // profile.created_at, sementara UserModel::calculateRealAlpha()
        // (dipakai di dashboard admin/kepala lab/user & modal detail
        // asisten) memakai attendance_reset_at jika lebih baru dari
        // created_at (di-set saat admin "Reset Presensi"). Akibatnya jumlah
        // Alpha di halaman Logbook jauh lebih besar daripada di
        // dashboard/modal - keduanya menghitung rentang hari yang beda.
        // Disamakan di sini supaya kedua tempat menghitung rentang hari
        // yang PERSIS SAMA.
        $startDate = date('Y-m-d', strtotime($prof['created_at']));
        try {
            $this->db->query("SELECT attendance_reset_at FROM profile WHERE id_profil = :pid");
            $this->db->bind(':pid', $pId);
            $resetRow = $this->db->single();
            $resetAt  = $resetRow['attendance_reset_at'] ?? null;
            if ($resetAt && $resetAt > $startDate) {
                $startDate = date('Y-m-d', strtotime($resetAt));
            }
        } catch (\Throwable $e) {
            // Kolom belum ada (migration v7 belum dijalankan) - abaikan
        }

        $today      = new DateTime();
        $yesterday  = (clone $today)->modify('-1 day');

        // Jangan tampilkan hari ini kecuali sudah melewati jam 18:00
        $endDate = (date('H:i') >= '18:00')
            ? $today->format('Y-m-d')
            : $yesterday->format('Y-m-d');

        if ($startDate > $endDate) {
            return ['data'=>[],'total'=>0,'hadir'=>0,'izin'=>0,'alpha'=>0,'page'=>1,'per_page'=>$perPage,'total_pages'=>0];
        }

        // 2. Ambil semua presensi + logbook dari rentang tersebut
        $this->db->query("SELECT pr.id_presensi, pr.tanggal, pr.waktu_presensi,
                                 pr.waktu_pulang, pr.foto_presensi, pr.foto_pulang,
                                 pr.status AS status_db, pr.late_minutes,
                                 l.id_logbook, l.detail_aktivitas, l.is_verified
                          FROM presensi pr
                          LEFT JOIN logbook l ON pr.id_presensi = l.id_presensi
                          WHERE pr.id_profil = :pid
                            AND pr.tanggal BETWEEN :s AND :e");
        $this->db->bind(':pid', $pId);
        $this->db->bind(':s',   $startDate);
        $this->db->bind(':e',   $endDate);
        $rawPresensi = $this->db->resultSet();

        // 3. Ambil semua izin yang disetujui
        $this->db->query("SELECT * FROM izin
                          WHERE id_profil = :pid AND status_approval = 'Approved'
                            AND end_date >= :s AND start_date <= :e");
        $this->db->bind(':pid', $pId);
        $this->db->bind(':s',   $startDate);
        $this->db->bind(':e',   $endDate);
        $rawIzin = $this->db->resultSet();

        // 4. Susun index presensi dan izin berdasarkan tanggal untuk O(1) lookup
        $presensiByDate = [];
        foreach ($rawPresensi as $p) { $presensiByDate[$p['tanggal']] = $p; }
        $izinByDate = [];
        foreach ($rawIzin as $iz) {
            $d0 = new DateTime($iz['start_date']);
            $d1 = new DateTime($iz['end_date']);
            $d1->modify('+1 day');
            for ($d = clone $d0; $d < $d1; $d->modify('+1 day')) {
                $dk = $d->format('Y-m-d');
                if ($dk >= $startDate && $dk <= $endDate) $izinByDate[$dk] = $iz;
            }
        }

        // 5. Bangun daftar hari dari endDate mundur ke startDate
        $autoService = new AttendanceAutoService();
        $allEntries = [];
        $cHadir = 0; $cIzin = 0; $cAlpha = 0;

        $cur = new DateTime($endDate);
        $beg = new DateTime($startDate);
        while ($cur >= $beg) {
            $dateStr = $cur->format('Y-m-d');
            $entry = [
                'date'       => $dateStr,
                'status'     => 'Alpha',
                'color'      => 'red',
                'time_in'    => '-',
                'time_out'   => '-',
                'proof_in'   => null,
                'proof_out'  => null,
                'proof_izin' => null,
                'activity'   => 'Tidak Hadir (Alpha)',
                'is_locked'  => true,
                'log_id'     => null,
                'id_ref'     => null,
                'can_reset'  => false,
                'late_minutes' => 0,
                // [BARU] Penanda "pulang lebih cepat" - dihitung ulang dari
                // waktu_pulang (bukan disimpan sebagai kolom), sama seperti
                // AttendanceModel::clockOut() menghitungnya saat checkout.
                'is_early_checkout' => false,
            ];

            if (isset($presensiByDate[$dateStr])) {
                $p  = $presensiByDate[$dateStr];
                $st = $p['status_db'] ?? 'Hadir';  // 'Hadir', 'Terlambat', dll.
                $entry['status']    = ($st === 'Terlambat') ? 'Terlambat' : 'Hadir';
                $entry['color']     = 'green';
                $entry['time_in']   = $p['waktu_presensi'] ? date('H:i', strtotime($p['waktu_presensi'])) : '-';
                $entry['time_out']  = $p['waktu_pulang']   ? date('H:i', strtotime($p['waktu_pulang']))   : '-';
                $entry['proof_in']  = $p['foto_presensi'];
                $entry['proof_out'] = $p['foto_pulang'];
                $entry['activity']  = $p['detail_aktivitas'] ?? '';
                $entry['log_id']    = $p['id_logbook'];
                $entry['id_ref']    = $p['id_presensi'];
                $entry['is_locked'] = false;
                $entry['can_reset'] = true;
                $entry['late_minutes'] = (int)($p['late_minutes'] ?? 0);
                $entry['is_early_checkout'] = !empty($p['waktu_pulang']) && $autoService->isEarlyCheckout($p['waktu_pulang']);
                $cHadir++;
            } elseif (isset($izinByDate[$dateStr])) {
                $iz = $izinByDate[$dateStr];
                $entry['status']     = $iz['tipe'];  // 'Izin' atau 'Sakit'
                $entry['color']      = 'yellow';
                $entry['proof_izin'] = $iz['file_bukti'];
                $entry['activity']   = ($iz['deskripsi'] ?? '') . ' (Pengajuan Izin)';
                $entry['id_ref']     = $iz['id_izin'];
                $cIzin++;
            } elseif ((int) $cur->format('N') > \UserModel::WORK_DAYS_MAX_DOW) {
                // [BARU - SINKRONISASI DATA] UserModel::calculateRealAlpha()
                // (dipakai di dashboard admin/kepala lab/user & modal detail
                // asisten) HANYA menghitung Alpha untuk hari kerja (Senin-
                // Sabtu, N<=6) - Minggu tidak pernah dihitung sebagai Alpha
                // sama sekali. Sebelumnya di sini SEMUA hari tanpa presensi/
                // izin dihitung Alpha termasuk Minggu, membuat jumlah Alpha
                // di Logbook jauh lebih besar & tidak sinkron dengan
                // dashboard/modal - mempengaruhi penilaian kinerja asisten
                // secara tidak adil. Ditandai "Libur" (bukan Alpha, tidak
                // menambah $cAlpha) supaya kedua tempat menghitung angka
                // yang sama persis.
                $entry['status']   = 'Libur';
                $entry['color']    = 'gray';
                $entry['activity'] = 'Hari Libur (Minggu)';
            } else {
                $cAlpha++;
            }

            $allEntries[] = $entry;
            $cur->modify('-1 day');
        }

        // 6. Pagination
        $total      = count($allEntries);
        $totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 1;
        $page       = max(1, min((int)$page, max(1, $totalPages)));
        $offset     = ($page - 1) * $perPage;
        $paginatedEntries = array_slice($allEntries, $offset, $perPage);

        return [
            'data'        => $paginatedEntries,
            'total'       => $total,
            'hadir'       => $cHadir,
            'izin'        => $cIzin,
            'alpha'       => $cAlpha,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ];
    }


    public function resetLogUser($logId, $userId) {
        // [BARU - Modul 3 V3] Ownership validation: cek dulu apakah log_id
        // ini benar milik user ini, sebelum UPDATE - agar caller bisa
        // membedakan "berhasil" vs "log_id bukan milik Anda / tidak ada"
        // (sebelumnya execute() selalu true meski 0 baris ter-match).
        $this->db->query("SELECT l.id_logbook FROM logbook l
                JOIN profile p ON l.id_profil = p.id_profil
                WHERE l.id_logbook = :lid AND p.id_user = :uid");
        $this->db->bind(':lid', $logId);
        $this->db->bind(':uid', $userId);
        if (!$this->db->single()) return 'not_found';

        $sql = "UPDATE logbook l
                JOIN profile p ON l.id_profil = p.id_profil
                SET l.detail_aktivitas = NULL
                WHERE l.id_logbook = :lid AND p.id_user = :uid";
        
        $this->db->query($sql);
        $this->db->bind(':lid', $logId);
        $this->db->bind(':uid', $userId);
        return $this->db->execute();
    }

    // [DIHAPUS - Modul 3 V3] Tiga method private di bawah ini
    // (getProfileIdByUser, updateByLogId, updateByDate) sebelumnya ada di
    // sini tapi TIDAK PERNAH DIPANGGIL dari mana pun (dead code), dan
    // updateByLogId/updateByDate mereferensikan kolom
    // `presensi.keterangan_aktivitas` yang TIDAK ADA di skema tabel
    // `presensi` saat ini (akan error jika dipanggil). Dihapus sebagai
    // bagian dari pembersihan keamanan/maintainability.

}
?>
