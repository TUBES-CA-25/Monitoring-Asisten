<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Dinonaktifkan – ICLABS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #0f172a; }
        .circuit-bg {
            background-image: url("<?= BASE_URL ?>/assets/img/Logo_ICLABS.webp");
            background-repeat: repeat;
            background-size: 80px 80px;
            background-blend-mode:soft-light;;
        }
        @keyframes pulse-slow { 0%,100%{opacity:.6} 50%{opacity:1} }
        .pulse-slow { animation: pulse-slow 3s ease-in-out infinite; }
    </style>
</head>
<body class="circuit-bg min-h-screen flex items-center justify-center p-6">

    <div class="max-w-md w-full">
        <!-- Logo -->
        <div class="flex justify-center mb-8">
            <!-- <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-600 to-cyan-500 flex items-center justify-center shadow-lg shadow-blue-500/30"> -->
            <div class="relative w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-600 to-cyan-500 p-[5px] flex items-center justify-center shadow-lg shadow-blue-500/30">
                <div class="w-full h-full bg-white rounded-2xl flex items-center justify-center">
                    <img src="<?= BASE_URL ?>/assets/img/Logo_ICLABS.webp" alt="ICLABS Logo" class="w-12 h-12 object-contain animate-pulse filter drop-shadow-lg shadow-cyan-400">
                </div>
            </div>
        </div>

        <!-- Card -->
        <div class="bg-slate-800 border border-slate-700 rounded-3xl overflow-hidden shadow-2xl">

            <!-- Header gradient -->
            <div class="bg-gradient-to-r from-slate-700 to-slate-600 px-8 py-6 text-center border-b border-slate-600">
                <div class="w-14 h-14 rounded-full bg-red-500/20 border-2 border-red-400/40 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-slash text-red-400 text-xl"></i>
                </div>
                <h1 class="text-xl font-extrabold text-white">Akun Dinonaktifkan</h1>
                <p class="text-slate-400 text-sm mt-1">ICLABS – Lab Assistant System</p>
            </div>

            <!-- Body -->
            <div class="px-8 py-7 text-center">
                <p class="text-slate-300 text-sm leading-relaxed mb-2">
                    Halo, <span class="font-bold text-white"><?= htmlspecialchars($name) ?></span>.
                </p>
                <p class="text-slate-400 text-sm leading-relaxed mb-6">
                    Akun Anda saat ini <span class="text-red-400 font-semibold">dinonaktifkan</span> oleh Administrator.
                    Anda tidak dapat mengakses fitur apapun hingga akun diaktifkan kembali.
                </p>

                <div class="bg-slate-700/50 border border-slate-600 rounded-2xl p-4 mb-6 text-left space-y-2">
                    <div class="flex items-center gap-3 text-xs text-slate-300">
                        <i class="fas fa-info-circle text-blue-400 w-4 shrink-0"></i>
                        <span>Data kehadiran Anda sudah diarsipkan dengan aman.</span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-slate-300">
                        <i class="fas fa-calendar-check text-cyan-400 w-4 shrink-0"></i>
                        <span>Jadwal Anda tidak terpengaruh.</span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-slate-300">
                        <i class="fas fa-redo text-green-400 w-4 shrink-0"></i>
                        <span>Setelah diaktifkan kembali, semua fitur langsung tersedia.</span>
                    </div>
                </div>

                <p class="text-slate-500 text-xs mb-6">
                    Hubungi <span class="text-blue-400">Administrator Lab</span> jika ada pertanyaan
                    atau jika Anda merasa ini adalah kekeliruan.
                </p>

                <a href="<?= BASE_URL ?>/auth/logout"
                   class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 text-white font-bold text-sm hover:opacity-90 transition shadow-lg shadow-blue-500/20">
                    <i class="fas fa-sign-out-alt"></i> Keluar dari Sistem
                </a>
            </div>
        </div>

        <p class="text-center text-slate-600 text-xs mt-6">
            &copy; <?= date('Y') ?> ICLABS &mdash; ICo Labs-UMI
        </p>
    </div>

</body>
</html>
