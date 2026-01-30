<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>502 - Bad Gateway</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-3xl shadow-2xl overflow-hidden text-center p-8 relative border border-gray-200">
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-cyan-400 to-teal-500"></div>
        
        <div class="mb-6 relative inline-block">
            <div class="relative bg-cyan-50 text-cyan-600 w-24 h-24 rounded-full flex items-center justify-center text-4xl shadow-inner border border-cyan-100">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>

        <h1 class="text-3xl font-extrabold text-gray-800 mb-2">Jalur Terputus!</h1>
        <p class="text-gray-500 mb-8 leading-relaxed text-sm">
            Server menerima respons yang tidak valid<br>
        </p>

        <div class="flex flex-col gap-3">
            <button onclick="location.reload()" class="w-full py-3 bg-cyan-600 hover:bg-cyan-700 text-white font-bold rounded-xl shadow-lg shadow-cyan-500/30 transition transform hover:-translate-y-1 flex items-center justify-center gap-2">
                <i class="fas fa-sync-alt"></i> Coba Lagi
            </button>
            <a href="<?= BASE_URL ?>" class="w-full py-3 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold rounded-xl transition border border-gray-200">
                Ke Dashboard
            </a>
        </div>
        
        <div class="mt-8 text-[10px] text-black-400 font-mono font-bold">
            Error Code: 502 Bad Gateway
        </div>
    </div>

</body>
</html>