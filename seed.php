<?php
/**
 * seed.php — Skrip Seeder Database ICLABS
 *
 * Mengisi record awal database untuk kebutuhan deployment atau development.
 *
 * Pilihan Sumber Data:
 *   1. Full Dump (iclabs_db.sql) — Memuat 29 akun asisten/admin asli,
 *      profil, jadwal, logbook, master dosen, dsb.
 *   2. Default/Clean — Hanya master lab dan akun default (Admin, Kepala Lab, Demo).
 *
 * Cara Pakai:
 *   php seed.php             Impor data awal (default: iclabs_db.sql jika ada).
 *   php seed.php --status    Lihat ringkasan jumlah record saat ini.
 *   php seed.php --fresh     Reset seluruh tabel, impor ulang dump, dan jalankan migrasi.
 *   php seed.php --default   Hanya buat akun default (tanpa data riil asisten).
 *   php seed.php --help      Tampilkan panduan bantuan.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die("seed.php hanya bisa dijalankan lewat command line (CLI).\n");
}

require __DIR__ . '/app/config/config.php';

function iclabs_seed_out(string $msg): void {
    fwrite(STDOUT, $msg . PHP_EOL);
}

function iclabs_seed_err(string $msg): void {
    fwrite(STDERR, $msg . PHP_EOL);
}

$argvFlags = array_slice($argv, 1);
$isHelp    = in_array('--help', $argvFlags, true) || in_array('-h', $argvFlags, true);
$isStatus  = in_array('--status', $argvFlags, true);
$isFresh   = in_array('--fresh', $argvFlags, true);
$isDefault = in_array('--default', $argvFlags, true);

if ($isHelp) {
    iclabs_seed_out('==================================================');
    iclabs_seed_out(' ICLABS - Database Seeder');
    iclabs_seed_out('==================================================');
    iclabs_seed_out('Penggunaan:');
    iclabs_seed_out('  php seed.php            Impor data awal (iclabs_db.sql atau default)');
    iclabs_seed_out('  php seed.php --status   Cek jumlah record di database');
    iclabs_seed_out('  php seed.php --fresh    Kosongkan database, impor data awal & jalankan migrasi');
    iclabs_seed_out('  php seed.php --default  Hanya buat master lab & akun default (bersih)');
    iclabs_seed_out('  php seed.php --help     Tampilkan pesan bantuan ini');
    exit(0);
}

iclabs_seed_out('==================================================');
iclabs_seed_out(' ICLABS - Database Seeder');
iclabs_seed_out(' Database: ' . DB_NAME . '@' . DB_HOST);
iclabs_seed_out('==================================================');

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE                => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);
    $pdo->exec("SET time_zone = '+08:00'");
} catch (\Throwable $e) {
    // Jika database belum ada, buat otomatis
    if ($e->getCode() == 1049 || stripos($e->getMessage(), 'Unknown database') !== false) {
        try {
            $rootPdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . str_replace('`', '``', DB_NAME) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            unset($rootPdo);

            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE                => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
            ]);
            $pdo->exec("SET time_zone = '+08:00'");
            iclabs_seed_out('Database `' . DB_NAME . '` belum ada dan telah dibuat otomatis.');
        } catch (\Throwable $e2) {
            iclabs_seed_err('Gagal membuat database: ' . $e2->getMessage());
            exit(1);
        }
    } else {
        iclabs_seed_err('Gagal koneksi ke database: ' . $e->getMessage());
        exit(1);
    }
}

// Fungsi bantu untuk cek ringkasan data
function iclabs_show_status(PDO $pdo): void {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    iclabs_seed_out('');
    iclabs_seed_out('Ringkasan Isi Database:');
    iclabs_seed_out('  - Total Tabel     : ' . count($tables) . ' tabel');

    if (in_array('user', $tables)) {
        $userCount = $pdo->query("SELECT COUNT(*) FROM `user`")->fetchColumn();
        iclabs_seed_out("  - User Accounts   : $userCount record");
    }
    if (in_array('profile', $tables)) {
        $profCount = $pdo->query("SELECT COUNT(*) FROM `profile`")->fetchColumn();
        iclabs_seed_out("  - Profiles        : $profCount record");
    }
    if (in_array('dosen', $tables)) {
        $dosenCount = $pdo->query("SELECT COUNT(*) FROM `dosen`")->fetchColumn();
        iclabs_seed_out("  - Master Dosen    : $dosenCount record");
    }
    if (in_array('lab', $tables)) {
        $labCount = $pdo->query("SELECT COUNT(*) FROM `lab`")->fetchColumn();
        iclabs_seed_out("  - Master Lab      : $labCount record");
    }
    if (in_array('jadwal_kuliah', $tables)) {
        $jkCount = $pdo->query("SELECT COUNT(*) FROM `jadwal_kuliah`")->fetchColumn();
        iclabs_seed_out("  - Jadwal Kuliah   : $jkCount record");
    }
    if (in_array('presensi', $tables)) {
        $presCount = $pdo->query("SELECT COUNT(*) FROM `presensi`")->fetchColumn();
        iclabs_seed_out("  - Presensi        : $presCount record");
    }
    if (in_array('schema_migrations', $tables)) {
        $migCount = $pdo->query("SELECT COUNT(*) FROM `schema_migrations`")->fetchColumn();
        iclabs_seed_out("  - Migrasi Tercatat: $migCount file");
    }
    iclabs_seed_out('');
}

if ($isStatus) {
    iclabs_show_status($pdo);
    exit(0);
}

// Cek apakah database sudah memiliki data user
$hasUserTable = (bool)$pdo->query("SHOW TABLES LIKE 'user'")->fetchColumn();
$existingUsers = $hasUserTable ? (int)$pdo->query("SELECT COUNT(*) FROM `user`")->fetchColumn() : 0;

if ($existingUsers > 0 && !$isFresh) {
    iclabs_seed_out('');
    iclabs_seed_out("[PERINGATAN] Database sudah memiliki {$existingUsers} akun user.");
    iclabs_seed_out("Data tidak ditimpa agar aman dan mencegah duplikasi.");
    iclabs_seed_out("");
    iclabs_seed_out("Pilihan:");
    iclabs_seed_out("  - Jalankan 'php seed.php --status' untuk melihat rincian data saat ini.");
    iclabs_seed_out("  - Jalankan 'php seed.php --fresh' jika Anda INGIN mereset dan mengimpor ulang data awal.");
    iclabs_seed_out("  - Jalankan 'php migrate.php' untuk menerapkan migrasi skema terbaru.");
    exit(0);
}

// Handle --fresh: Bersihkan tabel terlebih dahulu
if ($isFresh) {
    iclabs_seed_out('');
    iclabs_seed_out('[FRESH] Menghapus seluruh tabel di database `' . DB_NAME . '`...');
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $allTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($allTables as $t) {
        $pdo->exec("DROP TABLE IF EXISTS `{$t}`");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    iclabs_seed_out('   OK (Tabel lama berhasil dibersihkan)');
}

$dumpFile = __DIR__ . '/iclabs_db.sql';

if (!$isDefault && file_exists($dumpFile)) {
    iclabs_seed_out('');
    iclabs_seed_out('Ditemukan file database dump: iclabs_db.sql');
    iclabs_seed_out('>> Mengimpor seluruh record data riil (User, Profil, Dosen, Jadwal, Presensi)...');

    $sql = file_get_contents($dumpFile);
    if ($sql === false) {
        iclabs_seed_err('   GAGAL: Tidak dapat membaca file iclabs_db.sql');
        exit(1);
    }

    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec($sql);
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        iclabs_seed_out('   OK (Impor record iclabs_db.sql berhasil)');
    } catch (\Throwable $e) {
        iclabs_seed_err('   GAGAL: ' . $e->getMessage());
        exit(1);
    }
} else {
    iclabs_seed_out('');
    iclabs_seed_out('>> Menjalankan seeder default bersih (database/schema.sql)...');
    $schemaFile = __DIR__ . '/database/schema.sql';
    if (!file_exists($schemaFile)) {
        iclabs_seed_err("   GAGAL: File {$schemaFile} tidak ditemukan.");
        exit(1);
    }

    $schemaSql = file_get_contents($schemaFile);
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec($schemaSql);
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        iclabs_seed_out('   OK (Skema basis & data awal default berhasil dibuat)');
    } catch (\Throwable $e) {
        iclabs_seed_err('   GAGAL: ' . $e->getMessage());
        exit(1);
    }
}

// Menjalankan migrasi otomatis agar database up-to-date
iclabs_seed_out('');
iclabs_seed_out('>> Menyinkronkan struktur database dengan menjalankan migrate.php...');
passthru('php ' . escapeshellarg(__DIR__ . '/migrate.php'));

iclabs_seed_out('');
iclabs_seed_out('=== SEEDING SELESAI ===');
iclabs_show_status($pdo);
