<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// -------------------------------

if( !session_id() ) session_start();

date_default_timezone_set('Asia/Makassar');
// 1. Mulai Session
if( !session_id() ) session_start();

// 2. Load Konfigurasi & Core
require_once '../app/config/config.php';
require_once '../app/core/App.php';
require_once '../app/core/Controller.php';
require_once '../app/core/Database.php';

// 3. Jalankan Aplikasi (Router Otomatis)
$app = new App();
?>
