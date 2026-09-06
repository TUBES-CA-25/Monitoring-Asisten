<?php
/**
 * SystemHealthModel — "Sistem cek direktori" (poin 2).
 *
 * Mencocokkan setiap nama file gambar yang tercatat di database (presensi,
 * izin, profile) dengan keberadaan file FISIK-nya di disk. Dibuat khusus
 * untuk kasus: setelah deploy ulang, folder penyimpanan (public/uploads/)
 * dipindahkan/di-mount di lokasi lain tanpa devops menyesuaikan kode -
 * akibatnya baris DB tetap ada tapi file fisiknya "hilang" dari sudut
 * pandang aplikasi (foto presensi/logbook tidak tampil, tombol "Lihat
 * Bukti" ada tapi gambarnya rusak).
 *
 * Dipakai oleh AdminController::uploadHealthCheck() - laporan ini TIDAK
 * memperbaiki data (tidak ada cara aman menebak lokasi file yang hilang),
 * tapi memberi sinyal jelas & cepat kalau UPLOAD_PATH sedang salah arah,
 * jauh sebelum ada laporan manual dari pengguna.
 */
class SystemHealthModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    /**
     * @return array{
     *   upload_path: string, upload_path_exists: bool, upload_path_writable: bool,
     *   folders: array<string, array{path:string, exists:bool, writable:bool, file_count:int}>,
     *   categories: array<string, array{label:string, folder:string, total:int, missing:int, missing_samples:string[]}>
     * }
     */
    public function checkUploadHealth(): array
    {
        $result = [
            'upload_path'          => UPLOAD_PATH,
            'upload_path_exists'   => is_dir(UPLOAD_PATH),
            'upload_path_writable' => is_dir(UPLOAD_PATH) && is_writable(UPLOAD_PATH),
            'folders'              => [],
            'categories'           => [],
        ];

        foreach (['attendance', 'leaves', 'profile'] as $folder) {
            $path = UPLOAD_PATH . $folder . '/';
            $exists = is_dir($path);
            $result['folders'][$folder] = [
                'path'       => $path,
                'exists'     => $exists,
                'writable'   => $exists && is_writable($path),
                'file_count' => $exists ? count(glob($path . '*') ?: []) : 0,
            ];
        }

        $result['categories']['presensi_masuk'] = $this->checkColumn(
            'Foto Presensi Datang', 'presensi', 'foto_presensi', 'attendance'
        );
        $result['categories']['presensi_pulang'] = $this->checkColumn(
            'Foto Presensi Pulang', 'presensi', 'foto_pulang', 'attendance'
        );
        $result['categories']['izin_bukti'] = $this->checkColumn(
            'Bukti Izin/Sakit', 'izin', 'file_bukti', 'leaves'
        );
        $result['categories']['profile_photo'] = $this->checkColumn(
            'Foto Profil', 'profile', 'photo_profile', 'profile', "AND photo_profile != '" . DEFAULT_PROFILE_PHOTO . "'"
        );

        return $result;
    }

    private function checkColumn(string $label, string $table, string $column, string $folder, string $extraWhere = ''): array
    {
        $sql = "SELECT `$column` AS fname FROM `$table` WHERE `$column` IS NOT NULL AND `$column` != '' $extraWhere";
        $stmt = $this->conn->query($sql);
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];

        $total   = count($rows);
        $missing = [];
        $dir     = UPLOAD_PATH . $folder . '/';

        foreach ($rows as $fname) {
            if (!file_exists($dir . $fname)) {
                $missing[] = $fname;
            }
        }

        return [
            'label'           => $label,
            'folder'          => $folder,
            'total'           => $total,
            'missing'         => count($missing),
            'missing_samples' => array_slice($missing, 0, 10),
        ];
    }
}
