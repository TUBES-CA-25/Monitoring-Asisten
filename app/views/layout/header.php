<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($this->getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
    <title>ICLABS System</title>

    <!-- Inline loader styles: in <head> to prevent FOUC on overlay -->
    <style>
    #iclabsLoaderWrap{position:fixed;inset:0;z-index:99998;display:flex;flex-direction:column;align-items:center;justify-content:center;background:rgba(8,16,40,.78);backdrop-filter:blur(14px);opacity:0;pointer-events:none;transition:opacity .2s ease}
    #iclabsLoaderWrap.active{opacity:1;pointer-events:all}
    </style>

    <!-- [BARU – Tahap 34] Favicon menggunakan logo ICLABS -->
    <link rel="icon" type="image/png" href="<?= ASSET_URL ?>/img/Logo_ICLABS.png">
    <link rel="shortcut icon" type="image/png" href="<?= ASSET_URL ?>/img/Logo_ICLABS.png">
    <link rel="apple-touch-icon" href="<?= ASSET_URL ?>/img/Logo_ICLABS.png">
    
    <!-- [Item 1] CSRF token — dibaca oleh global.js untuk disisipkan ke semua
         fetch() POST secara otomatis melalui header X-CSRF-TOKEN -->
    <?php
        // Pastikan session aktif dan token tersedia
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    ?>
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Style global (font, scrollbar, sidebar preload, tooltip, dll) -->
    <link href="<?= ASSET_URL ?>/css/global.css" rel="stylesheet">

    <!-- Script global (deteksi status sidebar sebelum render, toggle sidebar).
         Dimuat di <head> (bukan footer) agar class 'preload-minimized' bisa
         diterapkan ke <html> SEBELUM sidebar dirender, mencegah flash/kedipan. -->
    <script src="<?= ASSET_URL ?>/js/global.js"></script>

    <?php if (!empty($page_css)): ?>
        <?php foreach ($page_css as $cssFile): ?>
            <link href="<?= $cssFile ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($css)): ?>
        <link href="<?= ASSET_URL ?>/css/<?= $css ?>" rel="stylesheet">
    <?php endif; ?>
</head>
<body class="bg-gray-50 text-gray-800 antialiased">

<!-- ── ICLABS Loading Overlay ──────────────────────────────────────────
     Tampil saat AJAX navigation berlangsung.
     Dikelola oleh global.js: iclabsLoaderShow() / iclabsLoaderHide()    -->
<div id="iclabsLoaderWrap" aria-hidden="true" role="status">
  <div class="iclabs-loader-card">
    <!-- Orbit rings -->
    <div class="iclabs-orbit"></div>
    <div class="iclabs-orbit iclabs-orbit-2"></div>
    <div class="iclabs-orbit iclabs-orbit-3"></div>
    <!-- Orbiting dots -->
    <div class="iclabs-dot"></div>
    <div class="iclabs-dot iclabs-dot-2"></div>
    <!-- Logo center -->
    <div class="iclabs-logo-center">
      <img src="<?= ASSET_URL ?>/img/Logo_ICLABS_White.webp" alt="ICLABS">
    </div>
  </div>
  <div class="iclabs-loader-text">
    <span>Memuat halaman...</span>
  </div>
</div>
