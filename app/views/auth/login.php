<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login System - ICLABS</title>
    <link rel="icon" type="image/png" href="<?= ASSET_URL ?>/img/Logo_ICLABS.png">
    <link rel="shortcut icon" type="image/png" href="<?= ASSET_URL ?>/img/Logo_ICLABS.png">
    <link rel="apple-touch-icon" href="<?= ASSET_URL ?>/img/Logo_ICLABS.png">
    <?php
        // [Item 1] Generate CSRF token untuk form login (halaman publik)
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    ?>
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/auth/login.css" rel="stylesheet">
    
</head>
<body class="h-screen w-screen flex items-center justify-center overflow-hidden font-sans selection:bg-cyan-500 selection:text-white">

    <div class="bg-container">
        <div class="bg-slide active" style="background-image: url('<?= BASE_URL ?>/assets/img/startup.webp');"></div>
        <div class="bg-slide" style="background-image: url('<?= BASE_URL ?>/assets/img/IoT.webp');"></div>
        <div class="bg-slide" style="background-image: url('<?= BASE_URL ?>/assets/img/CV.webp');"></div>
        <div class="bg-slide" style="background-image: url('<?= BASE_URL ?>/assets/img/DS.webp');"></div>
        <div class="bg-slide" style="background-image: url('<?= BASE_URL ?>/assets/img/Comnet.webp');"></div>
        <div class="bg-slide" style="background-image: url('<?= BASE_URL ?>/assets/img/Micro.webp');"></div>
        <div class="bg-slide" style="background-image: url('<?= BASE_URL ?>/assets/img/Mulmed.webp');"></div>
        
        <div class="bg-overlay"></div>
    </div>

    <div class="glass-dark relative z-10 p-10 rounded-3xl w-full max-w-md border-t border-white/20 animate__animated animate__zoomIn">
        
        <div class="text-center mb-8">
            <div class="relative inline-block group">
                <div class="absolute -inset-1 bg-gradient-to-r from-cyan-400 to-blue-600 rounded-2xl blur opacity-25 group-hover:opacity-75 transition duration-1000 group-hover:duration-200"></div>
                <div class="relative w-20 h-20 bg-slate-900 rounded-2xl flex items-center justify-center shadow-2xl border border-white/10">
                    <img src="<?= BASE_URL ?>/assets/img/Logo_ICLABS.webp" alt="ICLABS Logo" 
                    class="w-12 h-12 object-contain animate-pulse filter drop-shadow-lg shadow-cyan-400">
                </div>
            </div>
            <h1 class="text-4xl font-extrabold text-white mt-4 tracking-wider">ICLABS</h1>
            <p class="text-slate-400 text-sm mt-1 font-light tracking-wide">Monitoring Asisten Laboratorium</p>
        </div>

        <form id="loginForm" class="space-y-6">
            
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fas fa-envelope text-slate-500 group-focus-within:text-cyan-400 transition-colors"></i>
                </div>
                <input type="email" name="email" id="emailInput" 
                    class="w-full py-3.5 pl-12 pr-4 bg-slate-800/50 border border-slate-600 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all duration-300"
                    placeholder="Email Address">
            </div>

            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fas fa-lock text-slate-500 group-focus-within:text-cyan-400 transition-colors"></i>
                </div>
                <input type="password" name="password" id="passwordInput" 
                    class="w-full py-3.5 pl-12 pr-12 bg-slate-800/50 border border-slate-600 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all duration-300"
                    placeholder="Password">
                <div class="absolute inset-y-0 right-0 pr-4 flex items-center cursor-pointer" onclick="togglePass()">
                    <i id="togglePassword" class="fas fa-eye text-slate-500 hover:text-cyan-400 transition-colors"></i>
                </div>
            </div>

            <!-- <div class="flex justify-end">
                <a href="#" class="text-xs text-slate-400 hover:text-cyan-400 transition-colors">Lupa Password?</a>
            </div> -->

            <button type="submit" id="submitBtn" class="w-full py-4 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold rounded-xl shadow-lg shadow-cyan-900/20 transform hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center gap-2 group">
                <span id="btnText">Masuk Sekarang</span>
                <i id="btnIcon" class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                <i id="btnSpinner" class="fas fa-circle-notch fa-spin hidden"></i>
            </button>
        </form>

        <div class="mt-8 text-center border-t border-slate-700/50 pt-6">
            <p class="text-slate-500 text-xs">&copy; <?= date('Y') ?> Integrated Computer Laboratory System</p>
        </div>
    </div>

    <div id="modalAlert" class="fixed inset-0 z-[100] flex items-center justify-center px-4">
        <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm" id="modalBackdrop"></div>
        <div class="bg-slate-800 border border-slate-700 rounded-3xl shadow-2xl w-full max-w-sm relative z-10 flex flex-col items-center p-8 text-center" id="modalContent">
            <div id="modalIconBg" class="w-20 h-20 rounded-full flex items-center justify-center mb-5 animate-bounce shadow-lg transition-colors duration-300">
                <i id="modalIcon" class="fas text-4xl transition-colors duration-300"></i>
            </div>
            <h3 id="modalTitle" class="text-2xl font-extrabold text-white mb-2 tracking-tight"></h3>
            <p id="modalMessage" class="text-sm text-slate-400 mb-8 px-2 leading-relaxed"></p>
            <button onclick="closeModal()" id="modalBtn" class="w-full py-3.5 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02] active:scale-95">
                Mengerti
            </button>
        </div>
    </div>

    <!-- [Item 9] Modal Lockout — Countdown + Quotes + Tunggu Button -->
    <div id="lockoutModal"
         class="hidden fixed inset-0 z-[999] flex items-end sm:items-center justify-center p-0 sm:p-4"
         style="background:rgba(8,12,28,0.94);backdrop-filter:blur(10px)">

        <div class="relative w-full max-w-md sm:rounded-3xl rounded-t-3xl overflow-hidden shadow-2xl border border-white/5"
             style="max-height:92dvh;overflow-y:auto">

            <!-- Header gradient -->
            <div class="bg-gradient-to-br from-red-800 via-rose-700 to-red-900 px-6 py-5 text-center relative overflow-hidden">
                <div class="absolute inset-0 opacity-5"
                     style="background-image:repeating-linear-gradient(45deg,#fff 0,#fff 1px,transparent 0,transparent 50%);background-size:10px 10px"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center mx-auto mb-3 border border-white/20 shadow-inner">
                        <i class="fas fa-shield-alt text-red-200 text-2xl"></i>
                    </div>
                    <h2 class="text-white font-extrabold text-lg tracking-tight">Akses Sementara Dikunci</h2>
                    <p id="lockoutSubtitle" class="text-rose-200 text-[11px] mt-1 font-medium tracking-wide">Terlalu banyak percobaan login yang gagal</p>
                </div>
            </div>

            <!-- Body -->
            <div class="bg-slate-900 px-5 pt-5 pb-3">

                <!-- Countdown block -->
                <div class="flex flex-col items-center mb-4">
                    <div class="bg-slate-950 border border-slate-700 rounded-2xl px-10 py-4 text-center shadow-inner">
                        <span id="countdownDisplay"
                              class="text-5xl font-extrabold font-mono tabular-nums text-red-400 leading-none tracking-tighter block">00:00</span>
                        <span id="countdownHuman" class="text-[11px] text-slate-400 mt-2 block font-medium"></span>
                    </div>
                </div>

                <!-- Progress bar -->
                <div class="w-full bg-slate-800 rounded-full h-1.5 mb-4 overflow-hidden">
                    <div id="lockoutProgressBar"
                         class="h-full rounded-full"
                         style="width:100%;transition:width 1s linear;background:linear-gradient(90deg,#ef4444,#f97316)"></div>
                </div>

                <!-- Pesan kontak admin (round >= 5) -->
                <div id="contactAdminMsg"
                     class="hidden bg-amber-950/60 border border-amber-500/30 rounded-xl px-4 py-3 mb-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center shrink-0 mt-0.5">
                            <i class="fas fa-exclamation-triangle text-amber-400 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-amber-300 text-[11px] font-bold uppercase tracking-wider mb-1">Batas Percobaan Tercapai</p>
                            <p class="text-amber-200/80 text-xs leading-relaxed">
                                Anda telah melebihi batas maksimum percobaan login.
                                Segera hubungi <span class="text-amber-300 font-bold">Administrator</span>
                                untuk mendapatkan bantuan perubahan password.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Quote rotator -->
                <div class="bg-slate-800/50 border border-slate-700/60 rounded-xl px-4 py-4 mb-4"
                     style="min-height:96px;display:flex;align-items:center">
                    <div class="text-center w-full">
                        <i class="fas fa-quote-left text-slate-600 text-sm mb-2 block"></i>
                        <p id="quoteText"
                           class="text-slate-300 text-[13px] italic leading-relaxed"
                           style="transition:opacity .6s"></p>
                        <p id="quoteSource"
                           class="text-slate-500 text-[11px] mt-2.5 font-medium not-italic"
                           style="transition:opacity .6s"></p>
                    </div>
                </div>

                <p class="text-center text-slate-600 text-[10px] mb-1 tracking-wide">
                    Halaman kembali normal secara otomatis setelah waktu habis.
                </p>
            </div>

            <!-- Footer: Tunggu button -->
            <div class="bg-slate-900 border-t border-slate-800 px-5 pb-5 pt-3 flex justify-center">
                <button onclick="closeLockoutModalTemp()"
                        class="flex items-center gap-2.5 px-8 py-2.5 rounded-xl font-bold text-sm text-slate-300
                               bg-slate-800 hover:bg-slate-700 border border-slate-600 hover:border-slate-500
                               transition-all active:scale-95 shadow">
                    <i class="fas fa-clock text-slate-400 text-xs"></i>
                    <span>Tunggu</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        window.BASE_URL   = <?= json_encode(BASE_URL) ?>;
        window.APP_CONFIG = <?= json_encode($js_config ?? []) ?>;
        window.LOCKOUT_QUOTES = [
            { text: "Janganlah kamu seperti orang-orang yang lupa kepada Allah, lalu Allah menjadikan mereka lupa kepada diri mereka sendiri. Mereka itulah orang-orang yang fasik.", source: "\u2014 Al-Qur'an, Surat Al-Hasyr (59): 19" },
            { text: "Celakalah orang-orang yang lalai dari salat mereka \u2014 orang-orang yang berbuat riya, dan enggan memberikan bantuan.", source: "\u2014 Al-Qur'an, Surat Al-Ma'un (107): 4\u20137" },
            { text: "Setiap anak Adam pasti melakukan kesalahan, dan sebaik-baik orang yang bersalah adalah yang bertaubat.", source: "\u2014 Hadits Riwayat At-Tirmidzi, no. 2499" },
            { text: "Bencana paling besar adalah kelalaian dan ketidakseriusan dalam urusan penting, karena ia merusak amal seperti cuka merusak madu.", source: "\u2014 Imam Al-Ghazali, Ihya Ulum al-Din" },
            { text: "Lalai adalah dosa yang sering tak disadari, namun akibatnya terasa nyata dan merugikan diri sendiri serta orang lain.", source: "\u2014 Imam Ibnu Al-Qayyim Al-Jauziyyah" },
            { text: "Waktu bagaikan pedang. Jika kamu tidak memakainya dengan baik, ia yang akan memotongmu.", source: "\u2014 Pepatah Arab Klasik" },
            { text: "Orang yang bijak belajar dari kesalahan orang lain; orang yang cerdas belajar dari kesalahannya sendiri; orang yang lalai tidak belajar sama sekali.", source: "\u2014 Pepatah Jawa" },
            { text: "Alon-alon asal kelakon \u2014 perlahan asal tercapai. Tetapi kelalaian bukanlah ketenangan; kelalaian adalah berhenti sebelum sampai.", source: "\u2014 Filosofi Jawa (Diinterpretasi)" },
            { text: "Those who cannot remember the past are condemned to repeat it. Keamanan tanpa kewaspadaan adalah undangan terbuka bagi bencana.", source: "\u2014 George Santayana, The Life of Reason (1905)" },
            { text: "Security is not a product, but a process \u2014 and carelessness is its greatest enemy. Satu celah kecil yang dilupakan bisa meruntuhkan sistem terbesar.", source: "\u2014 Bruce Schneier, Pakar Keamanan Siber" },
            { text: "A small leak will sink a great ship. Kebocoran kecil yang diabaikan akan menenggelamkan kapal besar.", source: "\u2014 Benjamin Franklin, Poor Richard's Almanack (1757)" },
            { text: "Negligence is the rust of the soul that corrodes through all her best resolves. Lalai adalah karat jiwa yang mengikis setiap niat baik.", source: "\u2014 Owen Feltham, Resolves (1623)" },
            { text: "Ingatlah: kepercayaan dibangun bertahun-tahun namun bisa runtuh dalam satu momen ketidakwaspadaan.", source: "\u2014 Prinsip Keamanan Informasi, ISO 27001" },
            { text: "Man is not the creature of circumstances. Circumstances are the creatures of men. Jadilah penguasa keadaanmu, bukan korban kelalaianmu.", source: "\u2014 Benjamin Disraeli, Vivian Grey (1826)" },
        ];
    </script>
    <script src="<?= BASE_URL ?>/assets/js/auth/login.js"></script>
</body>
</html>