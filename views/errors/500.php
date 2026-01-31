<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-3xl shadow-2xl overflow-hidden text-center p-8 relative border border-gray-200">
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-red-600 to-red-800"></div>
        
        <div class="mb-6 relative inline-block">
            <div class="absolute inset-0 bg-red-50 rounded-full opacity-50"></div>
            <div class="relative bg-red-100 text-red-600 w-24 h-24 rounded-full flex items-center justify-center text-4xl shadow-inner border border-red-200">
                <i class="fas fa-server"></i>
            </div>
        </div>

        <h1 class="text-3xl font-extrabold text-gray-800 mb-2">Terjadi Kesalahan!</h1>
        <p class="text-gray-500 mb-8 leading-relaxed text-sm">
           Maaf, server sedang mengalami gangguan teknis
        </p>

        <div class="flex flex-col gap-3">
            <button onclick="location.reload()" class="w-full py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-lg shadow-red-500/30 transition flex items-center justify-center gap-2 cursor-pointer">
                <i class="fas fa-sync-alt"></i> Refresh Halaman
            </button>
            <a href="<?= BASE_URL ?>" class="w-full py-3 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold rounded-xl transition border border-gray-200">
                Kembali ke Beranda
            </a>
        </div>
        
        <div class="mt-8 text-[10px] text-black-400 font-mono font-bold">
            Error Code: 500 Internal Server Error
        </div>
    </div>

</body>
</html>