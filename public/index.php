<?php
// public/index.php

// ====================================================================
// 1. Load Konfigurasi Aplikasi (PALING AWAL)
//    - Memuat .env (jika ada), mendefinisikan BASE_URL, UPLOAD_PATH,
//      DB & JWT config, serta mengatur error_reporting/display_errors
//      sesuai APP_ENV/APP_DEBUG (lihat app/config/config.php).
// ====================================================================
require_once '../app/config/config.php';

// ====================================================================
// 2. HTTP SECURITY HEADERS  [Item 5]
//    Dikirim sebelum output apapun. Tidak mengubah fungsi atau tampilan.
// ====================================================================
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Content-Security-Policy
// Hati-hati: CSP harus mencantumkan SEMUA sumber eksternal yang dipakai,
// termasuk Tailwind CDN (dimuat sebagai <script>), Font Awesome (cdnjs),
// html5-qrcode (unpkg), dan QRCode.js (cdnjs).
// 'unsafe-inline' wajib ada karena Tailwind CDN menyuntikkan <style> secara
// dinamis via JS, dan banyak event handler inline (onclick="...") di view.
// Tanpa 'unsafe-inline', seluruh halaman menjadi tidak berfungsi.
// Nilai perlindungan CSP di sini difokuskan pada: pembatasan frame (anti-
// clickjacking), pembatasan form-action, dan pembatasan plugin (object-src).
// Perlindungan XSS berasal dari htmlspecialchars() di view, bukan dari CSP.
$_csp_directives = [
    // Hanya izinkan konten dari domain sendiri secara default
    "default-src 'self'",

    // Script: self + inline (Tailwind + onclick handlers) + CDN yang dipakai
    //   cdn.tailwindcss.com  → Tailwind CSS browser build (dimuat sbg <script>)
    //   cdnjs.cloudflare.com → Font Awesome, QRCode.js, Animate.css
    //   unpkg.com            → html5-qrcode (QR scanner kamera asisten)
    //   cdn.jsdelivr.net     → FullCalendar (jadwal)
    "script-src 'self' 'unsafe-inline' "
        . "https://cdn.tailwindcss.com "
        . "https://cdnjs.cloudflare.com "
        . "https://unpkg.com "
        . "https://cdn.jsdelivr.net",

    // Style: self + inline (Tailwind inject <style>) + CDN gaya
    "style-src 'self' 'unsafe-inline' "
        . "https://cdn.tailwindcss.com "
        . "https://cdnjs.cloudflare.com "
        . "https://fonts.googleapis.com "
        . "https://cdn.jsdelivr.net",

    // Font: self + data URI (icon font inline) + Google + cdnjs
    "font-src 'self' data: "
        . "https://cdnjs.cloudflare.com "
        . "https://fonts.gstatic.com",

    // Gambar: self + data (foto base64) + blob (kamera) + CDN avatar
    "img-src 'self' data: blob: "
        . "https://ui-avatars.com "
        . "https://lh3.googleusercontent.com",

    // Fetch/XHR: self (AJAX ke index.php) + Google APIs (kalender)
    "connect-src 'self' https://www.googleapis.com",

    // Keamanan kuat yang tidak butuh unsafe-inline:
    "frame-ancestors 'none'",   // anti-clickjacking (lebih kuat dari X-Frame-Options)
    "object-src 'none'",        // blokir plugin Flash/Java
    "base-uri 'self'",          // cegah base-tag injection
    "form-action 'self'",       // form hanya boleh submit ke domain sendiri
];
header('Content-Security-Policy: ' . implode('; ', $_csp_directives));
unset($_csp_directives);
header('Permissions-Policy: geolocation=(), payment=()');
// Aktifkan HSTS setelah pindah ke HTTPS di produksi:
// header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// ====================================================================
// 3. SESSION — hardening cookie [Item 2] + start [existing]
//    Semua flag ini WAJIB di-set SEBELUM session_start().
// ====================================================================
ini_set('session.cookie_httponly', '1');    // Cegah JS baca cookie session
ini_set('session.cookie_samesite', 'Lax'); // Mitigasi CSRF cross-site
ini_set('session.use_strict_mode', '1');   // Tolak session ID arbitrary
// Aktifkan setelah HTTPS:
// ini_set('session.cookie_secure', '1');

if (!session_id()) session_start();
date_default_timezone_set('Asia/Makassar');


// ====================================================================
// 2. Shortcut khusus untuk logbook/delete
//    (dipertahankan dari implementasi awal agar request DELETE/legacy
//    client tetap bisa memanggil endpoint ini langsung)
// ====================================================================
$request_uri = $_SERVER['REQUEST_URI'];
if (strpos($request_uri, 'logbook/delete') !== false) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    require_once '../app/core/Database.php';
    require_once '../app/core/JwtHandler.php';
    require_once '../app/core/ApiResponse.php';
    require_once '../app/api/AuthApi.php';
    require_once '../app/api/LogbookApi.php';

    $api = new LogbookApi();
    $api->delete();
    exit;
}

// ====================================================================
// 3. Load Core & Controller dasar
// ====================================================================
require_once '../app/core/Database.php';
require_once '../app/core/App.php';
require_once '../app/core/Controller.php';
require_once '../app/core/JwtHandler.php';
require_once '../app/core/ApiResponse.php';
require_once '../app/core/HashHelper.php'; // [BARU] Obfuskasi ID di URL

// ====================================================================
// 4. Load Kelas-kelas REST API (dikonsumsi aplikasi mobile)
// ====================================================================
require_once '../app/api/AuthApi.php';
require_once '../app/api/UserApi.php';
require_once '../app/api/ScheduleApi.php';
require_once '../app/api/AttendanceApi.php';
require_once '../app/api/LogbookApi.php';
require_once '../app/api/IzinApi.php';
require_once '../app/api/QrApi.php';
require_once '../app/api/DashboardApi.php';
require_once '../app/api/NotificationApi.php';
require_once '../app/api/StatsApi.php';
require_once '../app/api/DeviceApi.php';

// ==================== 5. Routing Logic ====================
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';

// 🚀 HANYA LOLOSKAN JIKA BENAR-BENAR DIAWALI DENGAN api/
if (strpos($url, 'api/') === 0) {
    $apiRoutes = require_once '../app/routes/api.php';
    $apiRoutes($url);
    exit;
} else {
    // 6. Jalankan Router Web Tampilan HTML (Admin/Kepala Lab/User)
    $app = new App();
}
