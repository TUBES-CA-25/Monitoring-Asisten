<?php
/**
 * migrate.php — Penerap migrasi database ICLABS secara otomatis.
 *
 * Menjalankan setiap file *.sql di folder migrations/ (urut berdasarkan
 * nama file, format "YYYY_MM_vN_..." sehingga otomatis kronologis) yang
 * BELUM diterapkan ke database tujuan, dilacak lewat tabel
 * `schema_migrations`. Sekali dijalankan, database lama (mis. sistem yang
 * sudah live/production) akan disesuaikan strukturnya (tabel & kolom baru)
 * mengikuti seluruh perbaikan yang sudah dilakukan di kode ini — tanpa
 * devops perlu tahu/menyusun sendiri daftar perubahan satu per satu.
 *
 * AMAN DIJALANKAN BERULANG:
 *   - Tabel `schema_migrations` mencatat file mana yang sudah diterapkan
 *     -> dijalankan ulang, file yang sama otomatis dilewati.
 *   - Setiap file migrasi (migrations/*.sql) SENDIRI juga ditulis
 *     idempotent (cek information_schema / IF NOT EXISTS) sebagai lapisan
 *     keamanan kedua, seandainya tabel pelacakan belum sempat tercatat
 *     (mis. proses terputus di tengah jalan).
 *   - Setiap file dijalankan dalam SATU transaksi: gagal di tengah file
 *     -> di-rollback, migrasi berikutnya TIDAK dijalankan, dan file itu
 *     TIDAK ikut tercatat sebagai selesai (aman untuk diperbaiki lalu
 *     dijalankan ulang).
 *
 * Konfigurasi koneksi database memakai app/config/config.php +
 * app/core/Database.php YANG SAMA dengan aplikasi (baca dari .env) -
 * tidak perlu memasukkan kredensial terpisah.
 *
 * Kompatibilitas mobile: seluruh migrasi di project ini bersifat ADDITIVE
 * (tabel/kolom/nilai enum baru) - tidak pernah menghapus atau mengubah
 * tipe kolom yang sudah dipakai endpoint app/api/*.php, sehingga versi
 * aplikasi mobile yang lebih lama tetap bisa berjalan setelah migrasi ini
 * dijalankan (lihat catatan "Kompatibilitas API mobile" di tiap file
 * migrations/*.sql).
 *
 * PENTING: skrip ini HANYA menyesuaikan STRUKTUR DATABASE. Jika ada
 * perubahan pada KONTRAK/response API (bukan struktur tabel) yang
 * membuat versi mobile app lama tidak kompatibel, itu di luar jangkauan
 * skrip ini dan perlu penanganan terpisah (endpoint versioning / update
 * aplikasi mobile).
 *
 * Cara pakai:
 *   php migrate.php              Terapkan semua migrasi yang tertunda.
 *   php migrate.php --status     Tampilkan status saja, tidak mengubah apa pun.
 *   php migrate.php --dry-run    Tampilkan migrasi yang AKAN dijalankan, tanpa mengeksekusinya.
 *
 * SELALU backup database sebelum menjalankan pada sistem yang belum
 * pernah menerapkan migrasi ini (lihat pesan peringatan sebelum eksekusi).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die("migrate.php hanya bisa dijalankan lewat command line (php migrate.php).\n");
}

require __DIR__ . '/app/config/config.php';
require __DIR__ . '/app/core/Database.php';

function iclabs_migrate_out(string $msg): void {
    fwrite(STDOUT, $msg . PHP_EOL);
}

function iclabs_migrate_err(string $msg): void {
    fwrite(STDERR, $msg . PHP_EOL);
}

/**
 * Pecah isi file .sql menjadi statement individual (dipisah ';' di akhir
 * baris). Cukup untuk gaya migrasi di project ini (SET/ALTER/CREATE/
 * INSERT/PREPARE/EXECUTE/DEALLOCATE biasa - tanpa stored procedure/
 * DELIMITER) dan lebih portabel daripada mengandalkan dukungan
 * multi-statement bawaan driver PDO (tidak selalu aktif di semua hosting).
 */
function iclabs_split_sql(string $sql): array {
    $statements = [];
    $buffer = '';
    foreach (explode("\n", $sql) as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || strpos($trimmed, '--') === 0) {
            continue;
        }
        $buffer .= $line . "\n";
        if (substr(rtrim($line), -1) === ';') {
            $statements[] = $buffer;
            $buffer = '';
        }
    }
    if (trim($buffer) !== '') {
        $statements[] = $buffer;
    }
    return $statements;
}

$argvFlags = array_slice($argv, 1);
$dryRun     = in_array('--dry-run', $argvFlags, true);
$statusOnly = in_array('--status', $argvFlags, true);

iclabs_migrate_out('==================================================');
iclabs_migrate_out(' ICLABS - Migration Runner');
iclabs_migrate_out(' Database: ' . DB_NAME . '@' . DB_HOST);
iclabs_migrate_out('==================================================');

try {
    $pdo = (new Database())->getConnection();
} catch (\Throwable $e) {
    iclabs_migrate_err('Gagal konek ke database: ' . $e->getMessage());
    exit(1);
}

// Tabel pelacak migrasi yang sudah diterapkan - dibuat otomatis kalau
// belum ada (aman dijalankan berulang).
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `schema_migrations` (
        `id`          INT AUTO_INCREMENT PRIMARY KEY,
        `filename`    VARCHAR(255) NOT NULL UNIQUE,
        `applied_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$applied = array_flip(
    $pdo->query('SELECT filename FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN, 0)
);

$migrationDir = __DIR__ . '/migrations';
$files = glob($migrationDir . '/*.sql');
sort($files, SORT_STRING); // nama file berformat YYYY_MM_vN_... -> otomatis urut kronologis

if (empty($files)) {
    iclabs_migrate_out('Tidak ada file migrasi ditemukan di ' . $migrationDir);
    exit(0);
}

$pending = array_values(array_filter($files, fn($f) => !isset($applied[basename($f)])));

iclabs_migrate_out('');
iclabs_migrate_out('Total file migrasi   : ' . count($files));
iclabs_migrate_out('Sudah diterapkan     : ' . (count($files) - count($pending)));
iclabs_migrate_out('Menunggu diterapkan  : ' . count($pending));
iclabs_migrate_out('');

if (empty($pending)) {
    iclabs_migrate_out('Database sudah sinkron - tidak ada migrasi yang perlu dijalankan.');
    exit(0);
}

foreach ($pending as $f) {
    iclabs_migrate_out('  - ' . basename($f));
}

if ($statusOnly) {
    exit(0);
}

if ($dryRun) {
    iclabs_migrate_out('');
    iclabs_migrate_out('[DRY RUN] Tidak ada perubahan yang dieksekusi.');
    iclabs_migrate_out('Jalankan "php migrate.php" (tanpa --dry-run) untuk benar-benar menerapkannya.');
    exit(0);
}

iclabs_migrate_out('');
iclabs_migrate_out('PENTING: pastikan database sudah di-backup sebelum melanjutkan');
iclabs_migrate_out('jika ini pertama kalinya skrip ini dijalankan pada sistem ini.');
iclabs_migrate_out('');
iclabs_migrate_out('Menerapkan ' . count($pending) . ' migrasi...');
iclabs_migrate_out('');

$failed = false;
foreach ($pending as $f) {
    $name = basename($f);
    iclabs_migrate_out(">> $name");
    $sql = file_get_contents($f);

    if ($sql === false) {
        iclabs_migrate_err("   GAGAL: tidak bisa membaca file.");
        $failed = true;
        break;
    }

    try {
        $pdo->beginTransaction();

        foreach (iclabs_split_sql($sql) as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }
            $pdo->exec($statement);
        }

        $ins = $pdo->prepare('INSERT INTO schema_migrations (filename) VALUES (:f)');
        $ins->execute([':f' => $name]);

        $pdo->commit();
        iclabs_migrate_out('   OK');
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        iclabs_migrate_err('   GAGAL: ' . $e->getMessage());
        iclabs_migrate_err("   Migrasi dihentikan di $name (belum tercatat selesai, aman dijalankan ulang setelah diperbaiki).");
        $failed = true;
        break;
    }
}

iclabs_migrate_out('');

if ($failed) {
    iclabs_migrate_err('Migrasi berhenti karena error. Perbaiki masalah di atas lalu jalankan ulang "php migrate.php"');
    iclabs_migrate_err('(migrasi yang sudah berhasil sebelumnya otomatis dilewati).');
    exit(1);
}

iclabs_migrate_out('Semua migrasi berhasil diterapkan. Database sudah sinkron dengan kode saat ini.');
exit(0);
