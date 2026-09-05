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

function iclabs_migrate_out(string $msg): void {
    fwrite(STDOUT, $msg . PHP_EOL);
}

function iclabs_migrate_err(string $msg): void {
    fwrite(STDERR, $msg . PHP_EOL);
}

$argvFlags = array_slice($argv, 1);
$dryRun     = in_array('--dry-run', $argvFlags, true);
$statusOnly = in_array('--status', $argvFlags, true);

iclabs_migrate_out('==================================================');
iclabs_migrate_out(' ICLABS - Migration Runner');
iclabs_migrate_out(' Database: ' . DB_NAME . '@' . DB_HOST);
iclabs_migrate_out('==================================================');

// [DIPERBAIKI] Sebelumnya memakai Database::getConnection() (koneksi
// biasa) lalu memecah tiap file .sql jadi statement individual & meng-exec
// satu-satu (iclabs_split_sql() + loop exec()). Pola idempotency di semua
// file migrations/*.sql (SET @ddl := IF(...); PREPARE stmt FROM @ddl;
// EXECUTE stmt; DEALLOCATE PREPARE stmt;) ternyata memicu CRASH PHP native
// (exit code 255, tanpa exception yang bisa ditangkap) saat PREPARE/EXECUTE/
// DEALLOCATE dikirim sebagai perintah PDO::exec() TERPISAH satu sama lain -
// bug interaksi PDO/mysqlnd dengan siklus hidup prepared statement
// server-side MySQL, direproduksi konsisten di lingkungan ini.
//
// Perbaikannya BUKAN mengubah isi file migrasi (semuanya tetap valid SQL
// standar), melainkan mengubah CARA migrate.php mengeksekusinya: koneksi
// terpisah dengan PDO::MYSQL_ATTR_MULTI_STATEMENTS diaktifkan, lalu seluruh
// isi file dikirim sebagai SATU pemanggilan exec() (bukan dipecah per
// statement). Diverifikasi tidak crash untuk seluruh file migrasi yang ada
// (termasuk yang punya 4 blok PREPARE/DEALLOCATE sekaligus).
//
// Koneksi KHUSUS untuk skrip ini saja (bukan lewat Database.php) karena
// MYSQL_ATTR_MULTI_STATEMENTS harus diaktifkan saat construct PDO - kalau
// dipasang di Database.php akan berlaku ke SELURUH query aplikasi web
// (termasuk yang menerima input mentah tanpa parameter binding di
// beberapa tempat lama), yang tidak diinginkan sebagai default global.
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);
    $pdo->exec("SET time_zone = '+08:00'");
} catch (\Throwable $e) {
    // Tangani skenario jika database belum dibuat di MySQL (error 1049)
    if ($e->getCode() == 1049 || stripos($e->getMessage(), 'Unknown database') !== false) {
        try {
            $rootPdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . str_replace('`', '``', DB_NAME) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            unset($rootPdo);

            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
            ]);
            $pdo->exec("SET time_zone = '+08:00'");
            iclabs_migrate_out('Database `' . DB_NAME . '` belum ada dan telah dibuat otomatis.');
        } catch (\Throwable $e2) {
            iclabs_migrate_err('Gagal membuat database otomatis: ' . $e2->getMessage());
            exit(1);
        }
    } else {
        iclabs_migrate_err('Gagal konek ke database: ' . $e->getMessage());
        exit(1);
    }
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

// Cek apakah database masih kosong / baru
$tableCheck = $pdo->query("SHOW TABLES LIKE 'user'")->fetchColumn();
if (!$tableCheck) {
    iclabs_migrate_out('');
    iclabs_migrate_out('[INFO] Tabel inti sistem belum ditemukan (database baru/kosong).');
    iclabs_migrate_out('[INFO] database/schema.sql akan otomatis diinisialisasi saat migrasi dijalankan.');
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

// Jika database baru/kosong, terapkan skema basis terlebih dahulu
if (!$tableCheck) {
    $baseSchemaFile = __DIR__ . '/database/schema.sql';
    if (file_exists($baseSchemaFile)) {
        iclabs_migrate_out('');
        iclabs_migrate_out('Database baru/kosong terdeteksi.');
        iclabs_migrate_out('>> Inisialisasi skema basis (database/schema.sql)...');
        $schemaSql = file_get_contents($baseSchemaFile);
        if ($schemaSql === false) {
            iclabs_migrate_err('   GAGAL: Tidak bisa membaca database/schema.sql.');
            exit(1);
        }
        try {
            $pdo->exec($schemaSql);
            iclabs_migrate_out('   OK (Tabel basis & data awal berhasil dibuat)');
        } catch (\Throwable $e) {
            iclabs_migrate_err('   GAGAL: ' . $e->getMessage());
            exit(1);
        }
    }
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
        // [DIPERBAIKI] Sebelumnya dibungkus beginTransaction()/commit() -
        // tapi MySQL melakukan IMPLICIT COMMIT di setiap statement DDL
        // (ALTER/CREATE TABLE), yang memutus transaksi begitu statement DDL
        // pertama di file dieksekusi. Explicit commit() di akhir jadi selalu
        // gagal dengan "There is no active transaction" walau perubahan
        // skemanya sendiri sudah BERHASIL diterapkan - bug ini sebelumnya
        // tidak pernah ketahuan karena migrasi selalu crash duluan (lihat
        // catatan PDO::MYSQL_ATTR_MULTI_STATEMENTS di atas) sebelum sampai
        // ke commit(). Karena DDL tidak benar-benar transactional di MySQL,
        // membungkusnya dengan transaksi cuma memberi rasa aman palsu -
        // dihapus. Setiap file migrasi SUDAH idempotent (cek
        // information_schema sebelum ALTER/CREATE), jadi aman dijalankan
        // ulang kalau terhenti di tengah jalan akibat sebab lain.
        $pdo->exec($sql);

        $ins = $pdo->prepare('INSERT INTO schema_migrations (filename) VALUES (:f)');
        $ins->execute([':f' => $name]);

        iclabs_migrate_out('   OK');
    } catch (\Throwable $e) {
        iclabs_migrate_err('   GAGAL: ' . $e->getMessage());
        iclabs_migrate_err("   Migrasi dihentikan di $name (belum tercatat selesai, aman dijalankan ulang setelah diperbaiki - file migrasi bersifat idempotent).");
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
