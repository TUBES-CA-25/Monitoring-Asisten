<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICLABS System</title>

    <!-- [BARU – Tahap 34] Favicon menggunakan logo ICLABS -->
    <link rel="icon" type="image/png" href="<?= ASSET_URL ?>/img/Logo_ICLABS.png">
    <link rel="shortcut icon" type="image/png" href="<?= ASSET_URL ?>/img/Logo_ICLABS.png">
    <link rel="apple-touch-icon" href="<?= ASSET_URL ?>/img/Logo_ICLABS.png">
    
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
