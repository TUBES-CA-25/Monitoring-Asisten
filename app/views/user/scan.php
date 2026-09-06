<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Scan Presensi</title>
    <link rel="icon" type="image/png" href="<?= ASSET_URL ?>/img/Logo_ICLABS.png">
    <link rel="shortcut icon" type="image/png" href="<?= ASSET_URL ?>/img/Logo_ICLABS.png">
    <link rel="apple-touch-icon" href="<?= ASSET_URL ?>/img/Logo_ICLABS.png">
    <?php
        // CSRF token untuk fetch calls di scan.js (halaman ini standalone, tidak muat global.js)
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    ?>
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <link rel="stylesheet" href="<?= ASSET_URL ?>/css/user/scan.css">

</head>
<body class="bg-black h-screen w-full flex flex-col font-sans overflow-hidden text-white">

    <!-- [BARU] Latar animasi galaksi Milky Way - full-screen, di belakang
         semua elemen lain (z-index terendah). Lihat galaxy_background.js -
         hanya menggambar & tidak menangkap event apapun (pointer-events
         tetap default none karena posisinya di lapisan paling bawah). -->
    <canvas id="galaxyBgCanvas" class="fixed inset-0 w-full h-full z-0"></canvas>

    <div class="absolute top-0 left-0 w-full z-40 p-4 flex justify-between items-center bg-gradient-to-b from-black/80 to-transparent">
        <a href="<?= BASE_URL ?>/user/dashboard" class="bg-white/10 backdrop-blur-md px-4 py-2 rounded-full text-sm font-bold flex items-center gap-2 hover:bg-white/20 transition border border-white/10">
            <i class="fas fa-chevron-left"></i> Kembali
        </a>
        
        <div class="flex items-center gap-2 bg-black/40 px-4 py-1.5 rounded-full backdrop-blur-md border border-white/10 max-w-[50%]">
            <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse shrink-0" id="gps-dot"></div>
            <span class="text-xs font-mono truncate" id="gps-text">Mencari Lokasi...</span>
        </div>
    </div>

    <div class="flex-1 relative z-10 w-full h-full flex flex-col justify-center overflow-hidden">
        
        <div id="step-scan" class="w-full h-full flex flex-col justify-center items-center p-6 relative z-10 transition-opacity duration-300">
            <div class="relative w-full max-w-sm aspect-square bg-gray-800 rounded-3xl overflow-hidden border-2 border-white/20 shadow-2xl">
                <div id="qr-reader" class="w-full h-full"></div>
                <div class="scan-laser"></div>
                <div class="absolute top-4 left-4 w-8 h-8 border-t-4 border-l-4 border-blue-500 rounded-tl-xl z-20"></div>
                <div class="absolute top-4 right-4 w-8 h-8 border-t-4 border-r-4 border-blue-500 rounded-tr-xl z-20"></div>
                <div class="absolute bottom-4 left-4 w-8 h-8 border-b-4 border-l-4 border-blue-500 rounded-bl-xl z-20"></div>
                <div class="absolute bottom-4 right-4 w-8 h-8 border-b-4 border-r-4 border-blue-500 rounded-br-xl z-20"></div>

                <!-- [BARU - Modul 2 V3] Overlay ditampilkan jika kamera gagal dibuka
                     (izin ditolak / tidak ada kamera), dengan tombol retry -->
                <div id="camera-error" class="hidden absolute inset-0 z-30 bg-gray-900/95 flex flex-col items-center justify-center text-center p-6">
                    <i class="fas fa-video-slash text-4xl text-red-400 mb-4"></i>
                    <p class="text-white font-bold mb-1">Kamera tidak dapat diakses</p>
                    <p class="text-white/60 text-xs mb-6">Pastikan Anda mengizinkan akses kamera pada browser, lalu coba lagi.</p>
                    <button onclick="retryCamera()" class="bg-blue-600 px-6 py-3 rounded-xl font-bold text-sm hover:bg-blue-500 transition flex items-center gap-2">
                        <i class="fas fa-rotate-right"></i> Coba Lagi
                    </button>
                </div>
            </div>
            <p class="text-white/60 text-sm mt-6 text-center animate-pulse bg-black/20 px-3 py-1 rounded-full backdrop-blur-sm">
                Arahkan kamera ke QR Code
            </p>
        </div>

        <div id="step-selfie" class="hidden absolute inset-0 z-20 bg-gray-900 animate-enter flex flex-col">
            <video id="selfie-video" autoplay playsinline class="w-full h-full object-cover transform scale-x-[-1]"></video>
            <img id="selfie-result" class="hidden w-full h-full object-cover absolute top-0 left-0 z-30">
            <canvas id="selfie-canvas" class="hidden"></canvas>

            <!-- [BARU - Modul 2 V3] Overlay ditampilkan jika kamera untuk foto
                 presensi gagal dibuka (izin ditolak / tidak ada kamera), dengan
                 tombol retry -->
            <div id="selfie-camera-error" class="hidden absolute inset-0 z-40 bg-gray-900/95 flex flex-col items-center justify-center text-center p-6">
                <i class="fas fa-camera-rotate text-4xl text-red-400 mb-4"></i>
                <p class="text-white font-bold mb-1">Kamera tidak dapat diakses</p>
                <p class="text-white/60 text-xs mb-6">Pastikan Anda mengizinkan akses kamera pada browser untuk mengambil foto presensi.</p>
                <button onclick="retrySelfieCamera()" class="bg-blue-600 px-6 py-3 rounded-xl font-bold text-sm hover:bg-blue-500 transition flex items-center gap-2">
                    <i class="fas fa-rotate-right"></i> Coba Lagi
                </button>
            </div>
        </div>

        <div id="loading-overlay" class="hidden absolute inset-0 bg-black/80 z-[60] flex flex-col items-center justify-center backdrop-blur-sm">
            <div class="w-16 h-16 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
            <p class="text-lg font-bold tracking-widest uppercase animate-pulse" id="loading-text">Memproses...</p>
        </div>
    </div>

    <div class="bg-gradient-to-t from-black via-black/90 to-transparent pb-8 pt-20 px-6 fixed bottom-0 w-full z-50">
        <div id="controls-selfie" class="hidden flex-col gap-4 items-center w-full max-w-sm mx-auto">
            <div class="bg-blue-600 px-4 py-1 rounded-full text-xs font-bold uppercase tracking-widest shadow-lg mb-2">
                VERIFIKASI WAJAH
            </div>
            <button id="btn-take" onclick="takeSnapshot()" class="w-20 h-20 rounded-full bg-white border-4 border-gray-300 shadow-lg active:scale-95 transition flex items-center justify-center hover:scale-105">
                <div class="w-16 h-16 rounded-full bg-white border-2 border-black"></div>
            </button>
            <div id="action-group" class="hidden w-full flex gap-3">
                <button onclick="resetCamera()" class="flex-1 bg-gray-700/80 backdrop-blur-md text-white font-bold py-3.5 rounded-xl hover:bg-gray-600 transition border border-white/10">Ulangi</button>
                <button onclick="submitAttendance()" class="flex-1 bg-blue-600 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-500/40 hover:bg-blue-500 transition flex items-center justify-center gap-2">Kirim Bukti <i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <div id="customModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
        <div class="bg-white text-gray-800 w-full max-w-sm rounded-3xl p-6 text-center shadow-2xl modal-enter transform transition-all relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-24 bg-gradient-to-br from-blue-500 to-indigo-600 opacity-10"></div>
            <div id="modalIconContainer" class="w-20 h-20 rounded-full mx-auto mb-4 flex items-center justify-center text-4xl shadow-lg relative z-10 text-white transition-colors duration-300">
                <i id="modalIcon" class="fas fa-check"></i>
            </div>
            <h3 id="modalTitle" class="text-2xl font-extrabold mb-2 relative z-10">Berhasil!</h3>
            <p id="modalMessage" class="text-gray-500 text-sm mb-6 leading-relaxed relative z-10 px-2">Pesan...</p>
            <button id="modalBtn" class="w-full py-3.5 rounded-xl text-white font-bold shadow-lg transition relative z-10">OK</button>
        </div>
    </div>

    <!-- <input type="hidden" id="scanned-token">
    <input type="hidden" id="final-image-base64">
    <input type="hidden" id="geo-lat" value="">
    <input type="hidden" id="geo-lng" value="">
    <input type="hidden" id="geo-address" value=""> -->

    <input type="hidden" id="scanned-token">
    <input type="hidden" id="scanned-type"> 
    <input type="hidden" id="final-image-base64">
    <input type="hidden" id="geo-lat" value="">
    <input type="hidden" id="geo-lng" value="">
    <input type="hidden" id="geo-address" value="">

    <script type="application/json" id="scan-config"><?= json_encode([
        'userName'        => $_SESSION['name'] ?? 'User',
        'submitUrl'       => BASE_URL . '/user/submit_attendance',
        'checkTypeUrl'    => BASE_URL . '/user/check_qr_type',
        'dashboardUrl'    => BASE_URL . '/user/dashboard',
        'logbookUrl'      => BASE_URL . '/user/submit_logbook',
        'prefilledToken'  => $prefilled_token  ?? '',
        'prefilledAction' => $prefilled_action ?? '',
    ]) ?></script>

    <!-- [BARU – Tahap 35] Modal isi logbook setelah scan pulang -->
    <div id="logbookModal" class="hidden fixed inset-0 z-[70] flex items-end sm:items-center justify-center p-0 sm:p-6">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
        <div class="relative bg-white w-full sm:max-w-md rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden z-10 flex flex-col">
            <div class="bg-gradient-to-r from-blue-700 to-cyan-600 px-6 py-5 text-white">
                <h3 class="font-extrabold text-lg leading-tight flex items-center gap-2">
                    <i class="fas fa-book-open"></i> Isi Logbook Hari Ini
                </h3>
                <p class="text-blue-100 text-xs mt-1">Ceritakan aktivitas yang sudah kamu lakukan hari ini.</p>
            </div>
            <div class="p-5">
                <textarea id="logbookActivity" rows="4" placeholder="Contoh: Membantu praktikum Algoritma & Pemrograman kelas B1, mendampingi 3 kelompok tugas akhir..."
                          class="w-full border border-gray-200 rounded-2xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 resize-none"></textarea>
                <p id="logbookCharCount" class="text-[10px] text-gray-400 mt-1 text-right">0 karakter</p>
                <div class="flex gap-3 mt-4">
                    <button id="logbookSkipBtn" class="flex-1 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold text-sm hover:bg-gray-50 transition">
                        Lewati
                    </button>
                    <button id="logbookSubmitBtn" class="flex-2 flex-1 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 text-white font-bold text-sm hover:opacity-90 transition">
                        <i class="fas fa-paper-plane mr-2"></i>Kirim
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= ASSET_URL ?>/js/common/galaxy_background.js"></script>
    <script src="<?= ASSET_URL ?>/js/user/scan.js"></script>
</body>
</html>