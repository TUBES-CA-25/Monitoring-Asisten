<?php
/**
 * RecycleBinModel – Mengelola data presensi & logbook yang di-reset
 * ke "recycle bin" sebelum dihapus permanen. [Tahap 35]
 */
class RecycleBinModel
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->getConnection();
        $this->ensureTablesExist();
    }

    /**
     * Buat tabel recycle bin jika belum ada (idempoten).
     * Dipanggil dari constructor sehingga fitur reset bekerja
     * tanpa perlu menjalankan migration secara manual.
     */
    private function ensureTablesExist(): void
    {
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS `attendance_recycle_bin` (
                `id_bin`          INT AUTO_INCREMENT PRIMARY KEY,
                `reset_scope`     ENUM('all','single') NOT NULL DEFAULT 'single',
                `reset_label`     VARCHAR(255) NOT NULL DEFAULT '',
                `id_profil`       INT NULL,
                `nama_asisten`    VARCHAR(150) NULL,
                `jabatan_asisten` VARCHAR(100) NULL,
                `date_data_start` DATE NULL,
                `date_data_end`   DATE NULL,
                `date_reset`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `jumlah_presensi` INT DEFAULT 0,
                `jumlah_logbook`  INT DEFAULT 0,
                `jumlah_izin`     INT DEFAULT 0,
                `data_presensi`   LONGTEXT NULL,
                `data_logbook`    LONGTEXT NULL,
                `data_izin`       LONGTEXT NULL,
                `id_admin`        INT NOT NULL DEFAULT 0,
                `status`          ENUM('archived','restored','deleted') NOT NULL DEFAULT 'archived',
                INDEX idx_bin_profil (`id_profil`),
                INDEX idx_bin_scope  (`reset_scope`),
                INDEX idx_bin_status (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS `recycle_bin_conflicts` (
                `id_conflict`    INT AUTO_INCREMENT PRIMARY KEY,
                `id_bin`         INT NOT NULL,
                `id_profil`      INT NOT NULL DEFAULT 0,
                `nama_asisten`   VARCHAR(150),
                `tanggal`        DATE NOT NULL,
                `conflict_type`  VARCHAR(50) DEFAULT 'presensi_overlap',
                `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_conflict_bin (`id_bin`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // [BARU v7] Tambah kolom baru jika belum ada (idempoten)
        try {
            $this->conn->exec("ALTER TABLE `profile` ADD COLUMN IF NOT EXISTS `attendance_reset_at` DATETIME NULL DEFAULT NULL");
            $this->conn->exec("ALTER TABLE `attendance_recycle_bin` ADD COLUMN IF NOT EXISTS `jumlah_izin` INT DEFAULT 0");
            $this->conn->exec("ALTER TABLE `attendance_recycle_bin` ADD COLUMN IF NOT EXISTS `data_izin` LONGTEXT NULL DEFAULT NULL");
        } catch (\Throwable $e) {
            // Kolom mungkin sudah ada — abaikan
        }
    }

    /* ─────────────────────────────────────────────────────────
       ARCHIVE (saat reset dilakukan)
       ───────────────────────────────────────────────────────── */

    /**
     * Arsipkan presensi & logbook ke recycle bin, kemudian hapus dari tabel asli.
     *
     * @param int|null $idProfil  null = semua asisten (scope=all), N = satu asisten
     * @param int      $adminId
     * @return array  ['ok'=>bool, 'id_bin'=>int|null, 'error'=>string|null]
     */
    public function archiveAndDelete(?int $idProfil, int $adminId): array
    {
        try {
            // 1. Kumpulkan profil yang terdampak
            if ($idProfil !== null) {
                $stmt = $this->conn->prepare(
                    "SELECT p.id_profil, p.nama, p.jabatan
                     FROM profile p JOIN user u ON p.id_user=u.id_user
                     WHERE p.id_profil=:pid AND u.role='User'"
                );
                $stmt->execute([':pid' => $idProfil]);
            } else {
                $stmt = $this->conn->prepare(
                    "SELECT p.id_profil, p.nama, p.jabatan
                     FROM profile p JOIN user u ON p.id_user=u.id_user
                     WHERE u.role='User' ORDER BY p.nama"
                );
                $stmt->execute();
            }
            $profiles = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            if (empty($profiles)) return ['ok' => false, 'error' => 'Tidak ada data asisten.'];

            $scope      = ($idProfil === null) ? 'all' : 'single';
            $resetLabel = ($scope === 'all') ? 'Semua Asisten' : $profiles[0]['nama'];

            $this->conn->beginTransaction();

            $insertedIds = [];

            foreach ($profiles as $p) {
                $pid = (int)$p['id_profil'];

                // Presensi (dengan logbook terikut via LEFT JOIN)
                $stmtP = $this->conn->prepare(
                    "SELECT pr.*, l.id_logbook, l.detail_aktivitas, l.is_verified
                     FROM presensi pr
                     LEFT JOIN logbook l ON l.id_presensi = pr.id_presensi
                     WHERE pr.id_profil = :pid ORDER BY pr.tanggal ASC"
                );
                $stmtP->execute([':pid' => $pid]);
                $presensiRows = $stmtP->fetchAll(\PDO::FETCH_ASSOC);

                // Logbook orphan (tanpa presensi)
                $stmtL = $this->conn->prepare(
                    "SELECT l.* FROM logbook l
                     LEFT JOIN presensi pr ON pr.id_presensi = l.id_presensi
                     WHERE l.id_profil = :pid AND pr.id_presensi IS NULL"
                );
                $stmtL->execute([':pid' => $pid]);
                $orphanLogs = $stmtL->fetchAll(\PDO::FETCH_ASSOC);

                $allLogbookRows = array_merge(
                    array_filter(array_column($presensiRows, null), fn($r) => !empty($r['id_logbook'])),
                    $orphanLogs
                );

                // [BARU v7] Izin — arsipkan sebelum dihapus agar bisa di-restore
                $stmtIz = $this->conn->prepare(
                    "SELECT * FROM izin WHERE id_profil = :pid ORDER BY start_date ASC"
                );
                $stmtIz->execute([':pid' => $pid]);
                $izinRows = $stmtIz->fetchAll(\PDO::FETCH_ASSOC);

                // [BARU] Lewati profil yang memang tidak punya data apapun
                // untuk diarsipkan (umum terjadi saat "Reset Keseluruhan"
                // menyentuh asisten yang belum pernah presensi/logbook/izin
                // sama sekali) - baris recycle bin kosong seperti itu hanya
                // membingungkan admin karena tidak ada apa pun yang bisa
                // di-restore, sementara tidak ada juga yang perlu dihapus
                // dari tabel asli untuk profil ini.
                if (count($presensiRows) === 0 && count($allLogbookRows) === 0 && count($izinRows) === 0) {
                    continue;
                }

                // Rentang tanggal dari presensi
                $dates     = array_column($presensiRows, 'tanggal');
                $dateStart = !empty($dates) ? min($dates) : null;
                $dateEnd   = !empty($dates) ? max($dates) : null;

                // Insert ke recycle bin (dengan data_izin)
                $stmtIns = $this->conn->prepare(
                    "INSERT INTO attendance_recycle_bin
                        (reset_scope, reset_label, id_profil, nama_asisten, jabatan_asisten,
                         date_data_start, date_data_end, date_reset,
                         jumlah_presensi, jumlah_logbook, jumlah_izin,
                         data_presensi, data_logbook, data_izin, id_admin, status)
                     VALUES
                        (:scope, :label, :pid, :nama, :jabatan,
                         :ds, :de, NOW(),
                         :jp, :jl, :ji,
                         :dp, :dl, :diz, :adm, 'archived')"
                );
                $stmtIns->execute([
                    ':scope'   => $scope,
                    ':label'   => $resetLabel,
                    ':pid'     => $pid,
                    ':nama'    => $p['nama'],
                    ':jabatan' => $p['jabatan'] ?? null,
                    ':ds'      => $dateStart,
                    ':de'      => $dateEnd,
                    ':jp'      => count($presensiRows),
                    ':jl'      => count($allLogbookRows),
                    ':ji'      => count($izinRows),
                    ':dp'      => json_encode($presensiRows, JSON_UNESCAPED_UNICODE),
                    ':dl'      => json_encode($allLogbookRows, JSON_UNESCAPED_UNICODE),
                    ':diz'     => json_encode($izinRows, JSON_UNESCAPED_UNICODE),
                    ':adm'     => $adminId,
                ]);
                $insertedIds[] = (int)$this->conn->lastInsertId();

                // Hapus dari tabel asli (urutan: logbook → presensi → izin)
                $this->conn->prepare("DELETE FROM logbook  WHERE id_profil = :pid")->execute([':pid' => $pid]);
                $this->conn->prepare("DELETE FROM presensi WHERE id_profil = :pid")->execute([':pid' => $pid]);
                // [BARU v7] Hapus izin agar total izin = 0 setelah reset
                $this->conn->prepare("DELETE FROM izin WHERE id_profil = :pid")->execute([':pid' => $pid]);

                // [BARU v7] Tandai waktu reset di profil agar calculateRealAlpha
                // menghitung alpha mulai dari tanggal reset (bukan created_at)
                // sehingga alpha = 0 segera setelah reset.
                $this->conn->prepare(
                    "UPDATE profile SET attendance_reset_at = NOW() WHERE id_profil = :pid"
                )->execute([':pid' => $pid]);
            }

            $this->conn->commit();
            return ['ok' => true, 'id_bin_list' => $insertedIds];

        } catch (\Throwable $e) {
            $this->conn->rollBack();
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /* ─────────────────────────────────────────────────────────
       LIST / FILTER
       ───────────────────────────────────────────────────────── */

    public function getAll(array $filter = []): array
    {
        $where  = ["b.status = 'archived'"];
        $params = [];

        if (!empty($filter['scope'])) {
            $where[]          = 'b.reset_scope = :scope';
            $params[':scope'] = $filter['scope'];
        }
        if (!empty($filter['id_profil'])) {
            $where[]             = 'b.id_profil = :pid';
            $params[':pid']      = $filter['id_profil'];
        }

        // [BARU] JOIN profile untuk angkatan - tidak disimpan di kolom bin
        // sendiri (beda dengan nama_asisten/jabatan_asisten yang memang
        // snapshot saat arsip dibuat), tapi profil tidak ikut dihapus saat
        // reset jadi aman diambil langsung dari sumbernya.
        $sql = "SELECT b.id_bin, b.reset_scope, b.reset_label, b.id_profil,
                       b.nama_asisten, b.jabatan_asisten, p.angkatan AS angkatan_asisten,
                       b.date_data_start, b.date_data_end, b.date_reset,
                       b.jumlah_presensi, b.jumlah_logbook, b.status
                FROM attendance_recycle_bin b
                LEFT JOIN profile p ON p.id_profil = b.id_profil
                WHERE " . implode(' AND ', $where) . "
                ORDER BY b.date_reset DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getById(int $idBin): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM attendance_recycle_bin WHERE id_bin=:id");
        $stmt->execute([':id' => $idBin]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Daftar asisten unik yang ada di bin (untuk filter dropdown) */
    public function getAssistantList(): array
    {
        $stmt = $this->conn->prepare(
            "SELECT DISTINCT id_profil, nama_asisten, jabatan_asisten
             FROM attendance_recycle_bin
             WHERE reset_scope='single' AND status='archived'
             ORDER BY jabatan_asisten, nama_asisten"
        );
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /* ─────────────────────────────────────────────────────────
       RESTORE
       ───────────────────────────────────────────────────────── */

    /**
     * Kembalikan data dari bin ke tabel presensi & logbook.
     * Data yang sudah ada di tabel (tidak bertumbukan) langsung dimasukkan.
     * Data yang bertumbukan (tanggal sudah ada presensi baru) dicatat ke
     * tabel conflict log untuk dilaporkan ke admin.
     *
     * @return array ['ok'=>bool, 'restored'=>int, 'conflicts'=>array, 'error'=>?string]
     */
    public function restore(int $idBin): array
    {
        $entry = $this->getById($idBin);
        if (!$entry) return ['ok' => false, 'error' => 'Entri tidak ditemukan.'];
        if ($entry['status'] !== 'archived') return ['ok' => false, 'error' => 'Data sudah di-restore atau dihapus.'];

        $presensiData = json_decode($entry['data_presensi'], true) ?? [];
        $logbookData  = json_decode($entry['data_logbook'],  true) ?? [];
        // [BARU v7] Restore izin yang turut diarsipkan saat reset
        $izinData     = json_decode($entry['data_izin'] ?? '[]', true) ?? [];
        $pid          = (int)$entry['id_profil'];

        $conflicts = [];
        $restored  = 0;

        try {
            $this->conn->beginTransaction();

            foreach ($presensiData as $row) {
                // Cek apakah sudah ada presensi baru di tanggal itu
                $chk = $this->conn->prepare(
                    "SELECT id_presensi FROM presensi WHERE id_profil=:pid AND tanggal=:tgl LIMIT 1"
                );
                $chk->execute([':pid' => $pid, ':tgl' => $row['tanggal']]);
                if ($chk->fetch()) {
                    $conflicts[] = [
                        'id_profil'    => $pid,
                        'nama_asisten' => $entry['nama_asisten'],
                        'tanggal'      => $row['tanggal'],
                        'conflict_type'=> 'presensi_overlap',
                    ];
                    continue; // lewati, jangan timpa data baru
                }

                // Insert presensi lama kembali
                $ins = $this->conn->prepare(
                    "INSERT INTO presensi
                        (id_profil, tanggal, waktu_presensi, waktu_pulang,
                         status, foto_presensi, foto_pulang, late_minutes, work_duration)
                     VALUES
                        (:pid, :tgl, :tin, :tout, :sts, :fp, :fpul, :late, :wd)"
                );
                $ins->execute([
                    ':pid'  => $pid,
                    ':tgl'  => $row['tanggal'],
                    ':tin'  => $row['waktu_presensi'],
                    ':tout' => $row['waktu_pulang'],
                    ':sts'  => $row['status'],
                    ':fp'   => $row['foto_presensi'],
                    ':fpul' => $row['foto_pulang'] ?? null,
                    ':late' => $row['late_minutes'] ?? 0,
                    ':wd'   => $row['work_duration'] ?? null,
                ]);
                $newPresensiId = (int)$this->conn->lastInsertId();
                $restored++;

                // Insert logbook untuk presensi ini
                if (!empty($row['id_logbook'])) {
                    $insL = $this->conn->prepare(
                        "INSERT INTO logbook (id_profil, id_presensi, detail_aktivitas, is_verified)
                         VALUES (:pid, :prid, :act, :ver)"
                    );
                    $insL->execute([
                        ':pid'  => $pid,
                        ':prid' => $newPresensiId,
                        ':act'  => $row['detail_aktivitas'],
                        ':ver'  => $row['is_verified'] ?? 0,
                    ]);
                }
            }

            // [BARU v7] Restore izin yang turut diarsipkan
            foreach ($izinData as $iz) {
                // Cek apakah sudah ada izin yang overlap di tanggal tersebut
                $chkIz = $this->conn->prepare(
                    "SELECT id_izin FROM izin WHERE id_profil=:pid
                     AND (:ds BETWEEN start_date AND end_date
                          OR :de BETWEEN start_date AND end_date
                          OR start_date BETWEEN :ds AND :de) LIMIT 1"
                );
                $chkIz->execute([':pid' => $pid, ':ds' => $iz['start_date'], ':de' => $iz['end_date']]);
                if ($chkIz->fetch()) {
                    $conflicts[] = [
                        'id_profil'     => $pid,
                        'nama_asisten'  => $entry['nama_asisten'],
                        'tanggal'       => $iz['start_date'] . ' s/d ' . $iz['end_date'],
                        'conflict_type' => 'izin_overlap',
                    ];
                    continue;
                }
                $insIz = $this->conn->prepare(
                    "INSERT INTO izin (id_profil, tipe, start_date, end_date, deskripsi,
                                      file_bukti, status_approval)
                     VALUES (:pid, :tipe, :ds, :de, :desc, :file, :status)"
                );
                $insIz->execute([
                    ':pid'    => $pid,
                    ':tipe'   => $iz['tipe'],
                    ':ds'     => $iz['start_date'],
                    ':de'     => $iz['end_date'],
                    ':desc'   => $iz['deskripsi'] ?? '',
                    ':file'   => $iz['file_bukti'] ?? null,
                    ':status' => $iz['status_approval'] ?? 'Approved',
                ]);
            }

            // [BARU v7] Reset attendance_reset_at agar alpha dihitung kembali
            // dari presensi yang baru saja di-restore (bukan dari reset_at).
            $this->conn->prepare(
                "UPDATE profile SET attendance_reset_at = NULL WHERE id_profil = :pid"
            )->execute([':pid' => $pid]);

            // Catat konflik ke tabel log
            if (!empty($conflicts)) {
                foreach ($conflicts as $c) {
                    $this->conn->prepare(
                        "INSERT INTO recycle_bin_conflicts
                            (id_bin, id_profil, nama_asisten, tanggal, conflict_type)
                         VALUES (:bin, :pid, :nama, :tgl, :type)"
                    )->execute([
                        ':bin'  => $idBin,
                        ':pid'  => $c['id_profil'],
                        ':nama' => $c['nama_asisten'],
                        ':tgl'  => $c['tanggal'],
                        ':type' => $c['conflict_type'],
                    ]);
                }
            }

            // Tandai bin entry sebagai restored
            $this->conn->prepare(
                "UPDATE attendance_recycle_bin SET status='restored' WHERE id_bin=:id"
            )->execute([':id' => $idBin]);

            $this->conn->commit();
            return ['ok' => true, 'restored' => $restored, 'conflicts' => $conflicts];

        } catch (\Throwable $e) {
            $this->conn->rollBack();
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /* ─────────────────────────────────────────────────────────
       DELETE PERMANENT
       ───────────────────────────────────────────────────────── */

    public function deletePermanent(int $idBin): bool
    {
        try {
            // Konflik log dihapus otomatis via ON DELETE CASCADE
            $stmt = $this->conn->prepare(
                "UPDATE attendance_recycle_bin SET status='deleted', data_presensi=NULL, data_logbook=NULL
                 WHERE id_bin=:id"
            );
            $stmt->execute([':id' => $idBin]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
