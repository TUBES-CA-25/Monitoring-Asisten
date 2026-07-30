<?php
/**
 * =====================================================================
 *  KONFIGURASI APLIKASI (ICLABS)
 * =====================================================================
 *  File ini memuat seluruh konfigurasi inti aplikasi: path project,
 *  koneksi database, JWT (untuk REST API / aplikasi mobile), Base URL,
 *  serta integrasi Google Calendar.
 *
 *  PENTING - PORTABILITAS WINDOWS & macOS:
 *  Jangan mengedit nilai konfigurasi langsung di file ini. Salin file
 *  ".env.example" (di root project) menjadi ".env", lalu sesuaikan
 *  nilainya. File ".env" sudah masuk daftar .gitignore sehingga aman
 *  untuk menyimpan kredensial lokal/produksi tanpa ter-commit ke Git.
 *
 *  Jika ".env" tidak ditemukan, aplikasi tetap berjalan menggunakan
 *  nilai default di bawah (cocok untuk instalasi XAMPP/Laragon lokal).
 * =====================================================================
 */

// ---------------------------------------------------------------
// 1. PATH DASAR PROJECT (cross-platform: Windows, macOS, Linux)
// ---------------------------------------------------------------
// File ini berada di app/config/, sehingga 2 folder di atasnya
// adalah root project (tempat folder "app" dan "public" berada).
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', rtrim(str_replace('\\', '/', dirname(__DIR__, 2)), '/'));
}

// ---------------------------------------------------------------
// 2. SIMPLE .ENV LOADER (tanpa dependency Composer)
// ---------------------------------------------------------------
if (!function_exists('iclabs_load_env')) {
    function iclabs_load_env($path) {
        if (!is_readable($path)) return;

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) return;

        foreach ($lines as $line) {
            $line = trim($line);

            // Lewati baris kosong & komentar
            if ($line === '' || $line[0] === '#') continue;
            if (strpos($line, '=') === false) continue;

            list($name, $value) = explode('=', $line, 2);
            $name  = trim($name);
            $value = trim($value);

            // Hilangkan tanda kutip pembungkus nilai (jika ada)
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last  = substr($value, -1);
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            // Jangan timpa env var yang sudah di-set oleh sistem/server
            if (getenv($name) === false) {
                putenv("$name=$value");
                $_ENV[$name]    = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

if (!function_exists('env')) {
    /**
     * Ambil nilai dari environment variable / .env, dengan fallback default.
     */
    function env($key, $default = null) {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }
        return $value;
    }
}

// Muat file .env di root project (jika ada)
iclabs_load_env(ROOT_PATH . '/.env');

// ---------------------------------------------------------------
// 3. APP ENVIRONMENT
// ---------------------------------------------------------------
// APP_ENV   : 'local' | 'production'
// APP_DEBUG : true  -> tampilkan error PHP secara langsung (dev)
//             false -> sembunyikan error dari user (production)
define('APP_ENV', env('APP_ENV', 'local'));
define('APP_DEBUG', filter_var(env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN));

// ---------------------------------------------------------------
// 4. BASE URL (auto-detect, bisa di-override lewat .env)
// ---------------------------------------------------------------
// Auto-detect membaca skema (http/https), host, dan folder tempat
// "public/index.php" berada (mis. "/ICLABS/public"), sehingga
// project bisa langsung dibuka di Windows (XAMPP/Laragon) maupun
// macOS (MAMP/XAMPP) tanpa edit kode, selama struktur foldernya sama.
if (env('BASE_URL')) {
    define('BASE_URL', rtrim(env('BASE_URL'), '/'));
} else {
    $iclabs_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $iclabs_host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $iclabs_dir    = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])) : '';
    $iclabs_dir    = rtrim($iclabs_dir, '/');

    define('BASE_URL', $iclabs_scheme . '://' . $iclabs_host . $iclabs_dir);

    unset($iclabs_scheme, $iclabs_host, $iclabs_dir);
}

// URL ke folder public/assets (CSS, JS, gambar statis)
define('ASSET_URL', BASE_URL . '/assets');

// ---------------------------------------------------------------
// 5. PATH & URL UNTUK FILE UPLOAD (cross-platform)
// ---------------------------------------------------------------
// Sebelumnya beberapa endpoint API menulis ke path absolut yang
// di-hardcode khusus macOS/XAMPP. Sekarang semua endpoint upload
// (lihat QrApi, AttendanceApi, IzinApi, UserApi, dll) WAJIB memakai
// konstanta UPLOAD_PATH/UPLOAD_URL di bawah ini agar konsisten &
// otomatis bekerja di Windows maupun macOS/Linux.
define('UPLOAD_PATH', ROOT_PATH . '/public/uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');

// Pastikan folder upload yang dibutuhkan selalu tersedia
foreach (['attendance', 'leaves', 'profile'] as $iclabs_upload_dir) {
    $iclabs_full_path = UPLOAD_PATH . $iclabs_upload_dir;
    if (!is_dir($iclabs_full_path)) {
        @mkdir($iclabs_full_path, 0775, true);
    }
}
unset($iclabs_upload_dir, $iclabs_full_path);

// ---------------------------------------------------------------
// 6. KONFIGURASI DATABASE
// ---------------------------------------------------------------
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'iclabs_db'));

// ---------------------------------------------------------------
// 7. KONFIGURASI JWT (untuk REST API / aplikasi mobile)
// ---------------------------------------------------------------
// PENTING: ganti JWT_SECRET di file .env sebelum deploy ke produksi.
// Contoh generate secret acak:
//   php -r "echo bin2hex(random_bytes(32));"
define('JWT_SECRET', env('JWT_SECRET', 'kunci_rahasia_kamu_123'));

// [Item 7] Gagal keras jika produksi masih pakai secret default.
// Ini mencegah JWT yang mudah dipalsukan di environment production.
if (defined('APP_ENV') && APP_ENV === 'production'
    && JWT_SECRET === 'kunci_rahasia_kamu_123') {
    error_log('[ICLABS SECURITY] FATAL: JWT_SECRET belum dikonfigurasi di .env!');
    http_response_code(500);
    die('Server configuration error. Please contact administrator.');
}

// Default 86400 detik (24 jam) - selaras dengan dokumentasi API.
define('JWT_EXPIRATION', (int) env('JWT_EXPIRATION', 86400));

// ---------------------------------------------------------------
// 8. KONFIGURASI GOOGLE CALENDAR (OAuth 2.0)
// ---------------------------------------------------------------
// Daftarkan Redirect URI berikut di Google Cloud Console:
//   {BASE_URL}/google/callback
define('GOOGLE_CLIENT_ID', env('GOOGLE_CLIENT_ID', ''));
define('GOOGLE_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET', ''));
define('GOOGLE_REDIRECT_URI', env('GOOGLE_REDIRECT_URI', BASE_URL . '/google/callback'));

// ---------------------------------------------------------------
// 8b. HASH ID — Obfuscation salt untuk URL (Hashids-like)
// ---------------------------------------------------------------
// Digunakan oleh HashHelper::encode/decode agar ID integer tidak
// tampil secara eksplisit di URL (mis. ?id=3 → ?id=mZ4kQ9).
// Ganti nilai ini di .env sebelum deploy ke produksi:
//   HASH_SALT=your-unique-random-string-here
define('HASH_SALT', env('HASH_SALT', 'iclabs-fikom-umi-2026-xK9pQ'));

// ---------------------------------------------------------------
// 9. ERROR REPORTING (mengikuti APP_ENV / APP_DEBUG)
// ---------------------------------------------------------------
// [PERBAIKAN PENTING - PHP 8.4] SEBELUMNYA: `display_errors=1` saat
// APP_DEBUG=true (default) menyebabkan SEMUA warning/notice/deprecated PHP
// dicetak LANGSUNG ke body response. PHP 8.4 memperkenalkan BANYAK
// deprecation baru yang sangat umum di codebase bergaya PHP 7 (contoh
// nyata: `fputcsv()` tanpa parameter $escape eksplisit) - setiap kali salah
// satu terpicu, teks "<br />\n<b>Deprecated</b>: ..." ikut tercetak DI
// TENGAH response, merusak:
//   - HTML data-island `<script>window.APP_CONFIG = {...}</script>` di
//     layout/footer.php -> JSON jadi tidak valid -> SyntaxError -> seluruh
//     script halaman (chart, calendar, search modal, dll) gagal jalan.
//   - Response JSON endpoint AJAX (CRUD, edit profil, edit/hapus user,
//     select user di logbook/jadwal, dll) -> res.json() gagal parse.
//   - Output CSV export (lihat hasil export Rekap Presensi).
//   - Output gambar/QR code biner.
// Inilah penyebab UTAMA gejala "banyak fitur jadi tidak berfungsi" yang
// HANYA muncul di lingkungan PHP 8.4 (mis. XAMPP versi baru) - bukan bug
// logika di masing-masing fitur.
//
// SEKARANG: `display_errors` SELALU OFF (apa pun APP_DEBUG), sehingga
// response yang dibaca aplikasi (JSON/CSV/gambar/HTML) TIDAK PERNAH
// tercemar oleh warning/notice/deprecated PHP versi berapa pun. Error tetap
// DICATAT ke file log (`log_errors=1`) agar developer bisa memeriksanya.
// APP_DEBUG hanya mengatur SEBERAPA DETAIL yang di-log (error_reporting).
if (APP_DEBUG) {
    error_reporting(E_ALL);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
}

ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Lokasi file log PHP (dibuat otomatis jika belum ada, sama seperti folder
// upload di atas). Jika folder tidak bisa dibuat/ditulis (mis. permission
// hosting tertentu), PHP akan jatuh ke konfigurasi error_log default server
// - aplikasi tetap berjalan normal karena display_errors tetap '0'.
$iclabs_log_dir = ROOT_PATH . '/storage/logs';
if (!is_dir($iclabs_log_dir)) {
    @mkdir($iclabs_log_dir, 0775, true);
}
if (is_dir($iclabs_log_dir) && is_writable($iclabs_log_dir)) {
    ini_set('error_log', $iclabs_log_dir . '/php_errors.log');
}
unset($iclabs_log_dir);

// ---------------------------------------------------------------
// 10. PARSE JSON BODY -> $_POST (untuk REST API)
// ---------------------------------------------------------------
$inputJSON  = file_get_contents('php://input');
$inputArray = json_decode($inputJSON, true);

if (!empty($inputArray) && is_array($inputArray)) {
    foreach ($inputArray as $key => $value) {
        $_POST[$key] = $value;
    }
}
