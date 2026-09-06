<?php

class LogbookApi {
    private $conn;
    private $table_logbook = 'logbook';
    private $table_profile = 'profile';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * GET /api/logbook/getlist
     * Sudah Sinkron dengan Tabel Izin secara Real-Time & Antipeluru!
     */
    public function getlist() {
        header('Content-Type: application/json');
        
        try {
            $payload = AuthApi::validateToken();
            $profilId = $payload['profil_id'];

            // 1. Ambil data dari Database (Presensi + Logbook)
            $query = "SELECT 
                        p.id_presensi,
                        p.tanggal,
                        p.status as status_presensi,
                        p.waktu_presensi as waktu_masuk, 
                        p.waktu_pulang,
                        l.id_logbook,
                        l.detail_aktivitas as aktivitas, 
                        l.keterangan
                    FROM presensi p
                    LEFT JOIN logbook l ON p.id_presensi = l.id_presensi 
                    WHERE p.id_profil = :pid
                    ORDER BY p.tanggal DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':pid', (int)$profilId, PDO::PARAM_INT);
            $stmt->execute();
            $dbResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Map hasil database presensi berdasarkan tanggal agar mudah dicari
            $presenceMap = [];
            foreach ($dbResults as $row) {
                $presenceMap[$row['tanggal']] = $row;
            }

            // 🚀 AMBIL DATA DARI TABEL IZIN UNTUK PERIODE YANG SAMA (31 Hari)
            // Ini ditarik terpisah agar bisa dicocokkan ke dalam kalender generator secara real-time
            $queryIzin = "SELECT id_izin, tipe, start_date, end_date, deskripsi, status_approval, file_bukti 
                          FROM izin 
                          WHERE id_profil = :pid";
            $stmtIzin = $this->conn->prepare($queryIzin);
            $stmtIzin->bindValue(':pid', (int)$profilId, PDO::PARAM_INT);
            $stmtIzin->execute();
            $izinResults = $stmtIzin->fetchAll(PDO::FETCH_ASSOC);

            // 2. LOGIC GENERATOR: Buat list tanggal (31 hari ke belakang)
            $finalResults = [];
            $totalDaysToShow = 31; 

            for ($i = 0; $i < $totalDaysToShow; $i++) {
                $dateIteration = date('Y-m-d', strtotime("-$i days"));
                
                // 🔍 A. Cek terlebih dahulu apakah asisten punya izin/sakit yang aktif di tanggal iterasi ini?
                $dataIzinAktif = null;
                foreach ($izinResults as $iz) {
                    if ($dateIteration >= $iz['start_date'] && $dateIteration <= $iz['end_date']) {
                        $dataIzinAktif = $iz;
                        break; // Stop loop jika sudah ketemu yang cocok
                    }
                }

                // B. Ambil atau inisialisasi data dasar baris hari
                if (isset($presenceMap[$dateIteration])) {
                    $item = $presenceMap[$dateIteration];
                } else {
                    // Jika data presensi kosong, default status diatur ke 'Alpa'
                    $item = [
                        'id_presensi' => null,
                        'tanggal' => $dateIteration,
                        'status_presensi' => 'Alpa',
                        'waktu_masuk' => '-',
                        'waktu_pulang' => '-',
                        'id_logbook' => null,
                        'aktivitas' => null,
                        'keterangan' => null
                    ];
                }

                // 🎯 C. GERBANG SINKRONISASI UTAMA: Jika terdeteksi ada Izin/Sakit, timpakan datanya murni!
                if ($dataIzinAktif !== null) {
                    $item['status_presensi'] = $dataIzinAktif['tipe']; // Mengubah status menjadi 'Izin' atau 'Sakit'
                    $item['file_bukti'] = $dataIzinAktif['file_bukti']; // Inject file bukti gambar untuk Flutter UI
                    $item['deskripsi_izin'] = $dataIzinAktif['deskripsi'];
                    $item['status_approval_izin'] = $dataIzinAktif['status_approval'];
                } else {
                    // Jika tidak sedang izin, beri nilai null agar Flutter tahu baris ini adalah hari normal
                    $item['file_bukti'] = null;
                    $item['deskripsi_izin'] = null;
                    $item['status_approval_izin'] = null;
                }

                // 3. Formatting untuk kebutuhan Flutter
                $timestamp = strtotime($item['tanggal']);
                $item['day_name'] = $this->getDayName(date('N', $timestamp));
                $item['formatted_date'] = date('d M Y', $timestamp);
                $item['is_locked'] = ($item['tanggal'] < date('Y-m-d'));
                $item['status'] = $item['status_presensi']; 

                // Logic teks tampilan aktivitas di baris tabel
                if (empty($item['aktivitas'])) {
                    if ($dataIzinAktif !== null) {
                        // Jika izin, tampilkan deskripsi izinnya langsung di kolom aktivitas, Bro
                        $item['aktivitas'] = "[" . strtoupper($dataIzinAktif['tipe']) . "] " . $dataIzinAktif['deskripsi'];
                    } elseif (strtolower($item['status_presensi'] ?? '') === 'alpa') {
                        $item['aktivitas'] = "Tidak Hadir (Alpha)";
                    } else {
                        $item['aktivitas'] = "Belum mengisi aktivitas";
                    }
                }

                $finalResults[] = $item;
            }

            // Return hasil gabungan bersih berformat JSON murni ke Flutter
            ApiResponse::success($finalResults, 'Logbook history successfully synchronized with Leave API', 200);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Database Error: " . $e->getMessage()
            ]);
        }
    }

    /**
     * Helper function agar tidak error 'getDayName'
     */
    private function getDayName($dayNumber) {
        $days = [
            1 => 'Senin', 
            2 => 'Selasa', 
            3 => 'Rabu',
            4 => 'Kamis', 
            5 => 'Jumat', 
            6 => 'Sabtu', 
            7 => 'Minggu'
        ];
        return $days[$dayNumber] ?? 'Tidak Diketahui';
    }

    // [DIHAPUS - Tahap 13 V3] Empat method di sini (detail, create,
    // search, exportReport + helper exportToCSV) TIDAK PERNAH dipanggil
    // oleh aplikasi mobile (LogbookRepository hanya punya getlist/
    // update/destroy) DAN mereferensikan kolom yang TIDAK ADA di skema
    // tabel `logbook` saat ini (tanggal, aktivitas, deskripsi, status,
    // created_at, updated_at - skema asli hanya id_logbook, id_profil,
    // id_presensi, detail_aktivitas, keterangan, is_verified). Akan
    // SQL error "Unknown column" jika dipanggil, dan membocorkan
    // $e->getMessage() (detail skema) ke pemanggil. Dihapus sebagai
    // bagian dari pembersihan keamanan/maintainability; rute terkait
    // juga dihapus dari app/routes/api.php.

    /// 🎯 FITUR EDIT LOGBOOK (VERSION 100% STERIL & VALID)
    public function update() {
        // Hapus semua output buffer tak terlihat yang berpotensi merusak JSON
        if (ob_get_length()) ob_clean();
        
        // Paksa header keluar sebagai JSON murni
        header('Content-Type: application/json; charset=UTF-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(["status" => "error", "message" => "Method tidak diizinkan!"]);
            exit;
        }

        try {
            $payload = AuthApi::validateToken();
            $profilId = $payload['profil_id']; 

            $input = json_decode(file_get_contents('php://input'), true);
            
            $id_logbook = $input['id_logbook'] ?? null;
            $detail_aktivitas = $input['detail_aktivitas'] ?? '';

            if (!$id_logbook || empty($detail_aktivitas)) {
                echo json_encode(["status" => "error", "message" => "Data input tidak lengkap!"]);
                exit;
            }

            // [BARU - Modul 3 V3] Ownership validation: pastikan id_logbook
            // ini benar milik profil yang login, SEKALIGUS ambil data
            // presensi terkait untuk validasi lock di bawah. Sebelumnya
            // UPDATE ... WHERE id_logbook=:id AND id_profil=:pid yang
            // mempengaruhi 0 baris tetap dibalas "success".
            $checkQuery = "SELECT pr.waktu_presensi, pr.waktu_pulang
                           FROM logbook l
                           JOIN presensi pr ON l.id_presensi = pr.id_presensi
                           WHERE l.id_logbook = :id_logbook AND l.id_profil = :pid
                           LIMIT 1";
            $stmtCheck = $this->conn->prepare($checkQuery);
            $stmtCheck->execute([
                ':id_logbook' => (int)$id_logbook,
                ':pid'        => (int)$profilId
            ]);
            $presensi = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (!$presensi) {
                echo json_encode(["status" => "error", "message" => "Data logbook tidak ditemukan atau bukan milik Anda."]);
                exit;
            }

            // [BARU - Modul 3 V3] "Tombol input/edit tidak tampil sebelum
            // presensi" - sama seperti AttendanceModel::validateLogbookEntry()
            // yang dipakai web (UserController::submit_logbook). Sebelumnya
            // endpoint mobile ini tidak memvalidasi sama sekali.
            if (empty($presensi['waktu_presensi'])) {
                echo json_encode(["status" => "error", "message" => "Anda belum melakukan scan masuk!"]);
                exit;
            }
            if (!empty($presensi['waktu_pulang'])) {
                echo json_encode(["status" => "error", "message" => "Logbook terkunci karena Anda sudah scan pulang."]);
                exit;
            }

            // Query UPDATE dikunci berdasarkan id_logbook dan id_profil
            $query = "UPDATE logbook 
                      SET detail_aktivitas = :detail 
                      WHERE id_logbook = :id_logbook AND id_profil = :pid";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':detail'     => trim($detail_aktivitas),
                ':id_logbook' => (int)$id_logbook,
                ':pid'        => (int)$profilId
            ]);

            // Balasan sukses berformat JSON murni tanpa pengotor
            echo json_encode([
                "status" => "success",
                "message" => "Logbook berhasil diperbarui, Bro!"
            ]);
            exit;

        } catch (\Exception $e) {
            echo json_encode([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ]);
            exit;
        }
    }

    /// 🎯 FITUR HAPUS/RESET LOGBOOK (BARU - Tahap 13: disamakan dengan web)
    public function delete() {
        // 🚀 1. Sapu bersih spasi gaib atau output buffer pengotor agar tidak dikira form login tradisional
        if (ob_get_length()) ob_clean();
        
        // Paksa keluar sebagai JSON murni
        header('Content-Type: application/json; charset=UTF-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(["status" => "error", "message" => "Method tidak diizinkan!"]);
            exit;
        }

        try {
            // 2. Ambil payload JWT untuk memastikan token dari Flutter sah
            $payload = AuthApi::validateToken();
            $profilId = $payload['profil_id']; 

            // 3. Tangkap JSON body dari Flutter (Sesuai kiriman Dio)
            $input = json_decode(file_get_contents('php://input'), true);
            $id_logbook = $input['id_logbook'] ?? null;

            if (!$id_logbook || !ctype_digit((string) $id_logbook)) {
                echo json_encode(["status" => "error", "message" => "ID Logbook kosong/tidak valid!"]);
                exit;
            }

            // [DIUBAH - Tahap 13] Ownership validation: ambil sekaligus data
            // presensi terkait untuk validasi lock di bawah (sama seperti
            // update()).
            $checkQuery = "SELECT pr.waktu_presensi, pr.waktu_pulang
                           FROM logbook l
                           JOIN presensi pr ON l.id_presensi = pr.id_presensi
                           WHERE l.id_logbook = :id_logbook AND l.id_profil = :pid
                           LIMIT 1";
            $stmtCheck = $this->conn->prepare($checkQuery);
            $stmtCheck->execute([
                ':id_logbook' => (int)$id_logbook,
                ':pid'        => (int)$profilId
            ]);
            $presensi = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (!$presensi) {
                echo json_encode(["status" => "error", "message" => "Data logbook tidak ditemukan atau bukan milik Anda."]);
                exit;
            }

            // [BARU - Tahap 13] Aturan lock yang sama seperti update():
            // "tombol input/edit tidak tampil sebelum presensi" juga berlaku
            // untuk hapus/reset.
            if (empty($presensi['waktu_presensi'])) {
                echo json_encode(["status" => "error", "message" => "Anda belum melakukan scan masuk!"]);
                exit;
            }
            if (!empty($presensi['waktu_pulang'])) {
                echo json_encode(["status" => "error", "message" => "Logbook terkunci karena Anda sudah scan pulang."]);
                exit;
            }

            // [DIUBAH - Tahap 13 - PERBAIKAN KRITIS]
            // SEBELUMNYA: "DELETE FROM presensi WHERE id_presensi = :id" -
            // karena ON DELETE CASCADE, ini menghapus SELURUH record
            // presensi hari itu (waktu masuk/pulang, foto, status,
            // late_minutes, work_duration - SEMUA HILANG), bukan cuma
            // logbook-nya. Disamakan dengan web
            // (LogbookModel::resetLogUser()): hanya KOSONGKAN
            // detail_aktivitas, data presensi TETAP UTUH.
            $queryReset = "UPDATE logbook SET detail_aktivitas = NULL WHERE id_logbook = :id AND id_profil = :pid";
            $stmtReset = $this->conn->prepare($queryReset);
            $stmtReset->execute([':id' => (int)$id_logbook, ':pid' => (int)$profilId]);

            echo json_encode([
                "status" => "success",
                "message" => "Isi logbook berhasil dikosongkan."
            ]);
            exit;

        } catch (\Exception $e) { // Gunakan backslash \Exception agar menangkap error global PHP
            echo json_encode([
                "status" => "error",
                "message" => "Gagal mereset data di database: " . $e->getMessage()
            ]);
            exit;
        }
    }
}
?>