<?php

error_reporting(E_ALL);
// In production / after merges we should not print PHP errors to the page
// (they break layout). Log errors instead and disable display.
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);

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
