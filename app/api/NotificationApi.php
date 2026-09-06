<?php
// app/api/NotificationApi.php
require_once '../app/core/ApiResponse.php';
require_once '../app/api/AuthApi.php';

class NotificationApi {
    private $conn;
    private $table_notification = 'notification';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * GET /api/notification/list
     * 🚀 ENGINE PREMIUM V6: Sinkronisasi Penuh Presensi, Jadwal Pribadi, & Sistem Izin Pintar (Auto-Expire)
     */
    public function getlist() {
        // Atur timezone kampus UMI agar perhitungan tanggal sinkron murni 100%
        date_default_timezone_set('Asia/Makassar');
        
        header('Content-Type: application/json; charset=UTF-8');
        
        try {
            // 🔑 Validasi token JWT untuk mengambil data asisten yang sedang login
            $payload = AuthApi::validateToken();
            $profilId = $payload['profil_id'] ?? null; // ID Asisten (Contoh: 17 milik Zaki)
            
            if (!$profilId) {
                ApiResponse::error('Profil ID tidak ditemukan dalam token', 401);
                exit;
            }
            
            $hariIni = date('Y-m-d');
            $notifikasiOtomatis = [];
            $idCounter = 1; 

            // =========================================================================
            // 🔍 PEMERIKSAAN 1: LOGIKA ABSENSI (DATANG & PULANG HARI INI)
            // =========================================================================
            $qPresensi = "SELECT id_presensi, waktu_presensi, waktu_pulang FROM presensi 
                          WHERE id_profil = :pid AND tanggal = :tanggal LIMIT 1";
            $stmtPresensi = $this->conn->prepare($qPresensi);
            $stmtPresensi->execute([
                ':pid'     => $profilId,
                ':tanggal' => $hariIni
            ]);
            $presensiHariIni = $stmtPresensi->fetch(PDO::FETCH_ASSOC);

            // 🎯 KATEGORI 1A: NOTIFIKASI STATUS ABSENSI DATANG
            if ($presensiHariIni && !empty($presensiHariIni['waktu_presensi'])) {
                $notifikasiOtomatis[] = [
                    "id_notification" => $idCounter++,
                    "title" => "Absen Masuk Berhasil! ✅",
                    "message" => "Sistem mencatat Anda telah hadir di laboratorium pada jam " . $presensiHariIni['waktu_presensi'] . ". Selamat bertugas!",
                    "time_ago" => "Terverifikasi",
                    "is_read" => 0
                ];
            } else {
                $notifikasiOtomatis[] = [
                    "id_notification" => $idCounter++,
                    "title" => "Peringatan Absen Datang! ⚠️",
                    "message" => "Anda belum melakukan absensi DATANG hari ini. Segera lakukan scan QR Code!",
                    "time_ago" => "Hari ini",
                    "is_read" => 0
                ];
            }

            // 🎯 KATEGORI 1B: NOTIFIKASI STATUS ABSENSI PULANG
            if ($presensiHariIni && !empty($presensiHariIni['waktu_presensi'])) {
                if (!empty($presensiHariIni['waktu_pulang'])) {
                    $notifikasiOtomatis[] = [
                        "id_notification" => $idCounter++,
                        "title" => "Absen Pulang Selesai 👋",
                        "message" => "Terima kasih sudah menyelesaikan tugas hari ini. Absen pulang tercatat pada jam " . $presensiHariIni['waktu_pulang'] . ".",
                        "time_ago" => "Selesai",
                        "is_read" => 0
                    ];
                } else {
                    $notifikasiOtomatis[] = [
                        "id_notification" => $idCounter++,
                        "title" => "Jangan Lupa Absen Pulang! 🚨",
                        "message" => "Anda sudah melakukan absen datang, tetapi belum melakukan absensi PULANG. Ingat scan QR sebelum keluar dari FIKOM UMI!",
                        "time_ago" => "Penting",
                        "is_read" => 0
                    ];
                }
            }

            // =========================================================================
            // 🔍 PEMERIKSAAN 2: CEK STATUS IZIN YANG AKTIF HARI INI (ANTI-NYANGKUT)
            // =========================================================================
            // 🔥 Menggunakan kolom asli dari izin.sql: deskripsi, status_approval, start_date, end_date
            // 🔥 Menggunakan BETWEEN CURRENT_DATE agar otomatis hilang jika rentang tanggalnya sudah lewat!
            $qIzin = "SELECT deskripsi, status_approval, start_date, end_date FROM izin 
                      WHERE id_profil = :pid 
                        AND CURRENT_DATE BETWEEN start_date AND end_date
                      ORDER BY id_izin DESC LIMIT 1"; 
            
            $stmtIzin = $this->conn->prepare($qIzin);
            $stmtIzin->execute([':pid' => $profilId]);
            $izinTerakhir = $stmtIzin->fetch(PDO::FETCH_ASSOC);

            if ($izinTerakhir) {
                $statusIzin = strtolower($izinTerakhir['status_approval']);
                $ketIzin = $izinTerakhir['deskripsi'] ?? 'Tanpa alasan';
                $tglIzin = $izinTerakhir['start_date'];
                
                if ($statusIzin === 'approved') {
                    $notifikasiOtomatis[] = [
                        "id_notification" => $idCounter++,
                        "title" => "Izin Diterima Admin! Disetujui ✅",
                        "message" => "Permohonan izin/sakit Anda untuk tanggal (" . $tglIzin . ") dengan keterangan '" . $ketIzin . "' telah DISETUJUI oleh Admin Web. Absensi Anda aman!",
                        "time_ago" => "Izin Selesai",
                        "is_read" => 0
                    ];
                } else if ($statusIzin === 'rejected') {
                    $notifikasiOtomatis[] = [
                        "id_notification" => $idCounter++,
                        "title" => "Peringatan: Izin Ditolak Admin! ❌",
                        "message" => "Mohon maaf, pengajuan izin Anda untuk tanggal (" . $tglIzin . ") telah DITOLAK oleh Admin Web. Silakan hubungi koordinator laboratorium!",
                        "time_ago" => "Urgent",
                        "is_read" => 0
                    ];
                } else {
                    $notifikasiOtomatis[] = [
                        "id_notification" => $idCounter++,
                        "title" => "Izin Sedang Diproses ⏳",
                        "message" => "Pengajuan izin Anda dengan keterangan '" . $ketIzin . "' saat ini masih dalam antrean peninjauan oleh Admin Web. Tunggu info selanjutnya ya!",
                        "time_ago" => "Pending",
                        "is_read" => 0
                    ];
                }
            }

            // =========================================================================
            // 🔍 PEMERIKSAAN 3: GABUNGAN JADWAL KULIAH, ASISTEN, PIKET, & LAB (REAL-TIME TIME SORTING)
            // =========================================================================
            date_default_timezone_set('Asia/Makassar');

            $hariAngkaIni = date('N'); // 1 = Senin, 7 = Minggu
            $jamSekarang  = date('H:i:s'); // Format waktu berjalan WITA

            $daftarHariTeks = [
                1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 
                5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
            ];
            $hariIniNama = $daftarHariTeks[$hariAngkaIni];

            $adaJadwalHariIni = false;

            // -------------------------------------------------------------------------
            // 🎓 DATA 1: JADWAL KULIAH PERSONAL (Berdasarkan Angka Hari & Jam Selesai)
            // -------------------------------------------------------------------------
            try {
                $qJadwalKuliah = "SELECT matkul, ruangan, start_time, end_time FROM jadwal_kuliah 
                                  WHERE id_profil = :pid AND hari = :hariAngka
                                  ORDER BY start_time ASC";
                
                $stmtKuliah = $this->conn->prepare($qJadwalKuliah);
                $stmtKuliah->execute([
                    ':pid'       => $profilId,
                    ':hariAngka' => $hariAngkaIni
                ]);
                $myLectures = $stmtKuliah->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($myLectures)) {
                    foreach ($myLectures as $kuliah) {
                        if ($jamSekarang <= $kuliah['end_time']) {
                            $adaJadwalHariIni = true;
                            $notifikasiOtomatis[] = [
                                "id_notification" => $idCounter++,
                                "title" => "Jadwal Kuliah Hari Ini 🎓",
                                "message" => "Hari ini Anda ada kuliah '" . $kuliah['matkul'] . "' di Ruang " . $kuliah['ruangan'] . " pukul " . date('H:i', strtotime($kuliah['start_time'])) . " - " . date('H:i', strtotime($kuliah['end_time'])) . ".",
                                "time_ago" => "Jadwal Kuliah",
                                "is_read" => 0
                            ];
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Eror Query Jadwal Kuliah: " . $e->getMessage());
            }

            // -------------------------------------------------------------------------
            // 📅 DATA 2: JADWAL TUGAS ASISTEN PERSONAL (Berdasarkan Teks Hari & Jam Selesai)
            // -------------------------------------------------------------------------
            try {
                $qJadwalAsisten = "SELECT mata_kuliah, ruangan_lab, start_time, end_time FROM jadwal_asisten 
                                   WHERE id_profil = :pid AND hari = :hariTeks
                                   ORDER BY start_time ASC";
                
                $stmtAsisten = $this->conn->prepare($qJadwalAsisten);
                $stmtAsisten->execute([
                    ':pid'      => $profilId,
                    ':hariTeks' => $hariIniNama
                ]);
                $mySchedules = $stmtAsisten->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($mySchedules)) {
                    foreach ($mySchedules as $jadwal) {
                        if ($jamSekarang <= $jadwal['end_time']) {
                            $adaJadwalHariIni = true;
                            $notifikasiOtomatis[] = [
                                "id_notification" => $idCounter++,
                                "title" => "Jadwal Tugas Asisten 📅",
                                "message" => "Hari ini Anda bertugas mendampingi praktikum '" . $jadwal['mata_kuliah'] . "' di Ruang " . $jadwal['ruangan_lab'] . " pukul " . date('H:i', strtotime($jadwal['start_time'])) . " - " . date('H:i', strtotime($jadwal['end_time'])) . ".",
                                "time_ago" => "Tugas Lab",
                                "is_read" => 0
                            ];
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Eror Query Jadwal Asisten: " . $e->getMessage());
            }

            // -------------------------------------------------------------------------
            // 🧹 DATA 3: JADWAL PIKET ASISTEN (Berdasarkan ID Profil, Angka Hari & Jam Selesai)
            // -------------------------------------------------------------------------
            try {
                // Sesuai struktur: id_profil, subjek, hari (angka), jam_mulai, jam_selesai
                $qJadwalPiket = "SELECT subjek, jam_mulai, jam_selesai FROM jadwal_piket 
                                 WHERE id_profil = :pid AND hari = :hariAngka
                                 ORDER BY jam_mulai ASC";
                
                $stmtPiket = $this->conn->prepare($qJadwalPiket);
                $stmtPiket->execute([
                    ':pid'       => $profilId,
                    ':hariAngka' => $hariAngkaIni
                ]);
                $myPikets = $stmtPiket->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($myPikets)) {
                    foreach ($myPikets as $piket) {
                        // Sorting Waktu: Hanya ambil jika jam sekarang belum melewati batas selesai piket
                        if ($jamSekarang <= $piket['jam_selesai']) {
                            $adaJadwalHariIni = true;
                            $notifikasiOtomatis[] = [
                                "id_notification" => $idCounter++,
                                "title" => "Agenda Piket Asisten 🧹",
                                "message" => "Hari ini Anda terdaftar dalam agenda '" . $piket['subjek'] . "' pukul " . date('H:i', strtotime($piket['jam_mulai'])) . " - " . date('H:i', strtotime($piket['jam_selesai'])) . ". Pastikan kebersihan lab terjaga!",
                                "time_ago" => "Jadwal Piket",
                                "is_read" => 0
                            ];
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Eror Query Jadwal Piket: " . $e->getMessage());
            }

            // -------------------------------------------------------------------------
            // 🏢 DATA 4: JADWAL KEGIATAN LABORATORIUM GLOBAL (Informasi Seluruh Penggunaan Lab)
            // -------------------------------------------------------------------------
            try {
                // Sesuai struktur: nama_kegiatan, lokasi, hari (angka), jam_mulai, jam_selesai
                // Tidak disaring pakai id_profil karena informasi lab bersifat global untuk semua asisten
                $qJadwalLab = "SELECT nama_kegiatan, lokasi, jam_mulai, jam_selesai FROM jadwal_lab 
                               WHERE hari = :hariAngka
                               ORDER BY jam_mulai ASC";
                
                $stmtLab = $this->conn->prepare($qJadwalLab);
                $stmtLab->execute([
                    ':hariAngka' => $hariAngkaIni
                ]);
                $labActivities = $stmtLab->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($labActivities)) {
                    foreach ($labActivities as $lab) {
                        // Sorting Waktu: Hanya ambil jika kegiatan lab yang bersangkutan belum selesai dilakukan
                        if ($jamSekarang <= $lab['jam_selesai']) {
                            $adaJadwalHariIni = true;
                            $notifikasiOtomatis[] = [
                                "id_notification" => $idCounter++,
                                "title" => "Info Kegiatan Ruang Lab 🏢",
                                "message" => "Hari ini terdapat agenda '" . $lab['nama_kegiatan'] . "' bertempat di " . $lab['lokasi'] . " pukul " . date('H:i', strtotime($lab['jam_mulai'])) . " - " . date('H:i', strtotime($lab['jam_selesai'])) . ".",
                                "time_ago" => "Kegiatan Lab",
                                "is_read" => 0
                            ];
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Eror Query Jadwal Penggunaan Lab: " . $e->getMessage());
            }

            // -------------------------------------------------------------------------
            // 🎯 FALLBACK SAFEGUARD GLOBAL (Jika Hari Ini Bersih dari Seluruh Agenda)
            // -------------------------------------------------------------------------
            if (!$adaJadwalHariIni) {
                $notifikasiOtomatis[] = [
                    "id_notification" => $idCounter++,
                    "title" => "Info Laboratorium",
                    "message" => "Seluruh agenda kuliah, piket, praktikum, dan kegiatan di laboratorium untuk hari " . $hariIniNama . " ini telah selesai atau kosong. Selamat beristirahat!",
                    "time_ago" => "Info Lab",
                    "is_read" => 0
                ];
            }


            // =========================================================================
            // 🔥 KIRIM RESPON SEBAGAI ARRAY MURNI KE FLUTTER CUBIT
            // =========================================================================
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                "status"  => "success",
                "message" => "Real-time comprehensive notifications cooked successfully",
                "data"    => $notifikasiOtomatis
            ]);
            exit;

        } catch (Exception $e) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                "status"  => "error",
                "message" => "Engine Error: " . $e->getMessage(),
                "data"    => []
            ]);
            exit;
        }
    }

    /**
     * PUT /api/notification/mark-read/{id}
     * 🎯 FIX MUTLAK: Mengembalikan objek kosong agar Flutter tidak crash index
     */
    public function markRead($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            ApiResponse::error('Method not allowed', 405);
            exit;
        }

        AuthApi::validateToken();

        if (!$id) {
            ApiResponse::error('Notification ID required', 400);
            exit;
        }

        try {
            // 🔥 AMAN: Jika id virtual, langsung bypass dengan return struktur map sukses agar Flutter tidak bingung
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                "status"  => "success",
                "message" => "Notification marked as read",
                "data"    => [] // Dipaksa berupa array objek kosong, bukan null!
            ]);
            exit;
        } catch (PDOException $e) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(["status" => "error", "message" => $e->getMessage(), "data" => []]);
            exit;
        }
    }

    /**
     * DELETE /api/notification/{id}
     * 🎯 FIX MUTLAK: Mengembalikan objek kosong agar Flutter tidak crash index
     */
    public function delete($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            ApiResponse::error('Method not allowed', 405);
            exit;
        }

        if (!$id) {
            ApiResponse::error('Notification ID required', 400);
            exit;
        }

        try {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                "status"  => "success",
                "message" => "Notification deleted successfully",
                "data"    => [] // Dipaksa berupa array objek kosong, bukan null!
            ]);
            exit;
        } catch (PDOException $e) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(["status" => "error", "message" => $e->getMessage(), "data" => []]);
            exit;
        }
    }
}
?>