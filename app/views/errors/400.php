<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>400 - Permintaan Tidak Valid</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-3xl shadow-2xl overflow-hidden text-center p-8 relative border border-gray-200">
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-amber-400 to-orange-500"></div>
        
        <div class="mb-6 relative inline-block">
            <div class="absolute inset-0 bg-amber-50 rounded-full opacity-50"></div>
            <div class="relative bg-amber-50 text-amber-500 w-24 h-24 rounded-full flex items-center justify-center text-4xl shadow-inner border border-amber-100">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>

        <h1 class="text-3xl font-extrabold text-gray-800 mb-2">Permintaan Tidak Valid</h1>
        <p class="text-gray-500 mb-8 leading-relaxed text-sm">
            Maaf, sistem tidak dapat memproses permintaan Anda (400).<br>
        </p>

        <div class="flex flex-col gap-3">
            <a href="javascript:history.back()" class="w-full py-3 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl shadow-lg shadow-amber-500/30 transition transform hover:-translate-y-1 flex items-center justify-center gap-2">
                <i class="fas fa-undo"></i> Kembali & Perbaiki
            </a>
            <a href="<?= BASE_URL ?>" class="w-full py-3 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold rounded-xl transition border border-gray-200">
                Ke Halaman Utama
            </a>
        </div>
        
        <div class="mt-8 text-[10px] text-gray-400