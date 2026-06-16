<?php
// public/index.php

// ====================================================================
// 1. Load Konfigurasi Aplikasi (PALING AWAL)
//    - Memuat .env (jika ada), mendefinisikan BASE_URL, UPLOAD_PATH,
//      DB & JWT config, serta mengatur error_reporting/display_errors
//      sesuai APP_ENV/APP_DEBUG (lihat app/config/config.php).
// ====================================================================
require_once '../app/config/config.php';

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
