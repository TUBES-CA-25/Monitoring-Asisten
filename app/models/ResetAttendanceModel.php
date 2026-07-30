<?php
/**
 * ResetAttendanceModel
 *
 * Tangani dua operasi:
 *  1. exportAndDelete($idProfil = null) – kumpulkan data presensi, logbook,
 *     izin, rekap, dan jadwal (asisten/kuliah/piket) ke dalam file ZIP,
 *     kemudian hapus baris presensi & logbook dari DB.
 *     $idProfil = null  → scope ALL asisten.
 *     $idProfil = N     → scope satu asisten (id_profil = N).
 *
 *  2. toggleAccountStatus($userId, $status) – set status_account user
 *     ke 'ACTIVE' atau 'INACTIVE'.  Jika INACTIVE, juga export+delete data
 *     user tersebut (sama seperti reset single).
 */
class ResetAttendanceModel
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /* ─────────────────────────────────────────────────────────────
       PRIVATE HELPERS
       ───────────────────────────────────────────────────────────── */

    /** Ambil daftar asisten (id_profil, nama) - bisa satu atau semua */
    private function getTargetProfiles(?int $idProfil): array
    {
        if ($idProfil !== null) {
            $stmt = $this->conn->prepare(
                "SELECT p.id_profil, p.nama FROM profile p
                 JOIN user u ON p.id_user = u.id_user
                 WHERE p.id_profil = :pid AND u.role = 'User'"
            );
            $stmt->execute([':pid' => $idProfil]);
        } else {
            $stmt = $this->conn->prepare(
                "SELECT p.id_profil, p.nama FROM profile p
                 JOIN user u ON p.id_user = u.id_user
                 WHERE u.role = 'User'
                 ORDER BY p.nama ASC"
            );
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Ambil semua presensi milik $idProfil */
    private function getPresensi(int $idProfil): array
    {
        $stmt = $this->conn->prepare(
            "SELECT pr.id_presensi, pr.tanggal, pr.waktu_presensi,
                    pr.waktu_pulang, pr.status, pr.late_minutes, pr.foto_presensi
             FROM presensi pr
             WHERE pr.id_profil = :pid
             ORDER BY pr.tanggal ASC"
        );
        $stmt->execute([':pid' => $idProfil]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Ambil semua logbook milik $idProfil */
    private function getLogbook(int $idProfil): array
    {
        $stmt = $this->conn->prepare(
            "SELECT lb.id_logbook, pr.tanggal, lb.detail_aktivitas, lb.is_verified
             FROM logbook lb
             JOIN presensi pr ON lb.id_presensi = pr.id_presensi
             WHERE lb.id_profil = :pid
             ORDER BY pr.tanggal ASC"
        );
        $stmt->execute([':pid' => $idProfil]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Ambil semua izin milik $idProfil */
    private function getIzin(int $idProfil): array
    {
        $stmt = $this->conn->prepare(
            "SELECT id_izin, tipe, start_date, end_date, deskripsi, status_approval
             FROM izin
             WHERE id_profil = :pid
             ORDER BY start_date ASC"
        );
        $stmt->execute([':pid' => $idProfil]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Ambil jadwal asisten, kuliah, piket milik $idProfil */
    private function getJadwal(int $idProfil): array
    {
        $rows = [];

        // jadwal_asisten
        $stmt = $this->conn->prepare(
            "SELECT 'Asisten Lab' as tipe, mata_kuliah as kegiatan, ruangan_lab as lokasi,
                    dosen, kelas_lab as kelas, tanggal, tanggal_selesai,
                    start_time, end_time, model_perulangan
             FROM jadwal_asisten WHERE id_profil = :pid ORDER BY tanggal ASC"
        );
        $stmt->execute([':pid' => $idProfil]);
        $rows = array_merge($rows, $stmt->fetchAll(PDO::FETCH_ASSOC));

        // jadwal_kuliah
        $stmt = $this->conn->prepare(
            "SELECT 'Kuliah' as tipe, matkul as kegiatan, ruangan as lokasi,
                    dosen, kelas, tanggal, tanggal_selesai,
                    start_time, end_time, model_perulangan
             FROM jadwal_kuliah WHERE id_profil = :pid ORDER BY tanggal ASC"
        );
        $stmt->execute([':pid' => $idProfil]);
        $rows = array_merge($rows, $stmt->fetchAll(PDO::FETCH_ASSOC));

        // jadwal_piket
        $stmt = $this->conn->prepare(
            "SELECT 'Piket' as tipe, subjek as kegiatan, 'Lab' as lokasi,
                    '' as dosen, '' as kelas, tanggal, tanggal_selesai,
                    jam_mulai as start_time, jam_selesai as end_time, model_perulangan
             FROM jadwal_piket WHERE id_profil = :pid ORDER BY tanggal ASC"
        );
        $stmt->execute([':pid' => $idProfil]);
        $rows = array_merge($rows, $stmt->fetchAll(PDO::FETCH_ASSOC));

        return $rows;
    }

    /** Tulis array of rows ke string CSV */
    private function toCsv(array $rows): string
    {
        if (empty($rows)) return "Tidak ada data.\n";
        $out = fopen('php://memory', 'r+');
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $row) fputcsv($out, $row);
        rewind($out);
        $content = stream_get_contents($out);
        fclose($out);
        return $content;
    }

    /** Hapus presensi (cascade logbook via FK atau manual) untuk satu profil */
    private function deletePresensiAndLogbook(int $idProfil): array
    {
        // Hitung dulu
        $cntP = $this->conn->prepare("SELECT COUNT(*) FROM presensi WHERE id_profil = :pid");
        $cntP->execute([':pid' => $idProfil]);
        $jumlahPresensi = (int)$cntP->fetchColumn();

        $cntL = $this->conn->prepare("SELECT COUNT(*) FROM logbook WHERE id_profil = :pid");
        $cntL->execute([':pid' => $idProfil]);
        $jumlahLogbook = (int)$cntL->fetchColumn();

        // Hapus logbook dulu (FK ke presensi)
        $this->conn->prepare("DELETE FROM logbook WHERE id_profil = :pid")
                   ->execute([':pid' => $idProfil]);

        // Hapus presensi
        $this->conn->prepare("DELETE FROM presensi WHERE id_profil = :pid")
                   ->execute([':pid' => $idProfil]);

        return ['presensi' => $jumlahPresensi, 'logbook' => $jumlahLogbook];
    }

    /* ─────────────────────────────────────────────────────────────
       PUBLIC API
       ───────────────────────────────────────────────────────────── */

    /**
     * Export semua data ke ZIP, kemudian hapus presensi & logbook.
     *
     * @param int|null $idProfil  null = semua asisten, N = satu asisten
     * @param int      $adminId   id_user admin (untuk reset_log)
     * @return array  ['zip_path' => '/path/ke/tmp.zip', 'filename' => 'nama.zip']
     *                atau ['error' => 'pesan'] jika gagal
     */
    public function exportAndDelete(?int $idProfil, int $adminId): array
    {
        $profiles = $this->getTargetProfiles($idProfil);
        if (empty($profiles)) {
            return ['error' => 'Tidak ada data asisten ditemukan.'];
        }

        $scope = ($idProfil === null) ? 'all' : 'single';
        $timestamp = date('Ymd_His');
        $label     = ($scope === 'all') ? 'SEMUA_ASISTEN' : preg_replace('/\s+/', '_', strtoupper($profiles[0]['nama']));
        $zipName   = "ICLABS_RESET_{$label}_{$timestamp}.zip";
        $zipPath   = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return ['error' => 'Gagal membuat file ZIP. Periksa permission folder tmp.'];
        }

        $totalPresensi = 0;
        $totalLogbook  = 0;

        foreach ($profiles as $p) {
            $pid    = (int) $p['id_profil'];
            $folder = preg_replace('/[^a-zA-Z0-9_]/', '_', $p['nama']);

            // Kumpulkan data
            $presensiRows = $this->getPresensi($pid);
            $logbookRows  = $this->getLogbook($pid);
            $izinRows     = $this->getIzin($pid);
            $jadwalRows   = $this->getJadwal($pid);

            // Rekap ringkas
            $rekap = [
                ['Metrik', 'Jumlah'],
                ['Total Presensi (Hadir/Terlambat)', count(array_filter($presensiRows, fn($r) => in_array($r['status'], ['Hadir', 'Terlambat'])))],
                ['Total Terlambat',   count(array_filter($presensiRows, fn($r) => $r['status'] === 'Terlambat'))],
                ['Total Alpa',        0], // dihitung di luar (butuh hari kerja logic)
                ['Total Izin Approve', count(array_filter($izinRows, fn($r) => $r['status_approval'] === 'Approved'))],
                ['Total Logbook',     count($logbookRows)],
                ['Logbook Terverifikasi', count(array_filter($logbookRows, fn($r) => !empty($r['is_verified'])))],
                ['Total Jadwal',      count($jadwalRows)],
            ];
            $rekapCsv = '';
            $tmp = fopen('php://memory', 'r+');
            foreach ($rekap as $row) fputcsv($tmp, $row);
            rewind($tmp); $rekapCsv = stream_get_contents($tmp); fclose($tmp);

            $zip->addFromString("{$folder}/01_presensi.csv",  $this->toCsv($presensiRows));
            $zip->addFromString("{$folder}/02_logbook.csv",   $this->toCsv($logbookRows));
            $zip->addFromString("{$folder}/03_izin.csv",      $this->toCsv($izinRows));
            $zip->addFromString("{$folder}/04_jadwal.csv",    $this->toCsv($jadwalRows));
            $zip->addFromString("{$folder}/05_rekap.csv",     $rekapCsv);

            // Hapus data
            $deleted       = $this->deletePresensiAndLogbook($pid);
            $totalPresensi += $deleted['presensi'];
            $totalLogbook  += $deleted['logbook'];

            // Catat di reset_log
            $this->conn->prepare(
                "INSERT INTO reset_log
                    (id_admin, scope, id_profil, nama_asisten, jumlah_presensi, jumlah_logbook, zip_filename)
                 VALUES (:adm, :scope, :pid, :nama, :jp, :jl, :zip)"
            )->execute([
                ':adm'   => $adminId,
                ':scope' => $scope,
                ':pid'   => $pid,
                ':nama'  => $p['nama'],
                ':jp'    => $deleted['presensi'],
                ':jl'    => $deleted['logbook'],
                ':zip'   => $zipName,
            ]);
        }

        // File README di root ZIP
        $info  = "ICLABS – Reset Data Kehadiran\n";
        $info .= "================================\n";
        $info .= "Tanggal Reset : " . date('d F Y H:i:s') . "\n";
        $info .= "Scope         : " . ($scope === 'all' ? 'Semua Asisten' : $profiles[0]['nama']) . "\n";
        $info .= "Total Presensi Dihapus : {$totalPresensi}\n";
        $info .= "Total Logbook  Dihapus : {$totalLogbook}\n";
        $info .= "\nData jadwal (asisten/kuliah/piket) TIDAK dihapus.\n";
        $info .= "Rekap per-asisten tersedia di masing-masing folder.\n";
        $zip->addFromString('README.txt', $info);

        $zip->close();

        return ['zip_path' => $zipPath, 'filename' => $zipName];
    }

    /**
     * Set status_account user.
     * Jika status baru = INACTIVE, sekaligus export+delete data attendance.
     *
     * @return array ['ok' => bool, 'zip_path'? => ..., 'filename'? => ..., 'error'? => ...]
     */
    public function toggleAccountStatus(int $userId, string $status, int $adminId): array
    {
        $status = strtoupper($status);
        if (!in_array($status, ['ACTIVE', 'INACTIVE'])) {
            return ['ok' => false, 'error' => 'Status tidak valid.'];
        }

        // Ambil id_profil user
        $stmt = $this->conn->prepare("SELECT id_profil FROM profile WHERE id_user = :uid");
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return ['ok' => false, 'error' => 'Profil user tidak ditemukan.'];
        $idProfil = (int) $row['id_profil'];

        // [PERBAIKAN v7] Nonaktifkan akun → data masuk ke Recycle Bin (bukan
        // lagi ekspor ZIP sementara). Konsisten dengan fitur Reset Presensi.
        if ($status === 'INACTIVE') {
            require_once '../app/models/RecycleBinModel.php';
            $binModel = new RecycleBinModel();
            $result   = $binModel->archiveAndDelete($idProfil, $adminId);
            if (!$result['ok']) {
                return ['ok' => false, 'error' => $result['error'] ?? 'Gagal mengarsipkan data ke Recycle Bin.'];
            }
        }

        // Update status di DB
        $this->conn->prepare("UPDATE `user` SET status_account = :st WHERE id_user = :uid")
                   ->execute([':st' => $status, ':uid' => $userId]);

        return ['ok' => true];
    }

    /** Cek apakah akun user aktif */
    public function isAccountActive(int $userId): bool
    {
        $stmt = $this->conn->prepare("SELECT status_account FROM `user` WHERE id_user = :uid");
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (!$row || ($row['status_account'] ?? 'ACTIVE') === 'ACTIVE');
    }
}
