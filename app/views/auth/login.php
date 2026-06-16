<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login System - ICLABS</title>
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

    <!-- "Data island": kirim BASE_URL ke JS eksternal (public/assets/js/auth/login.js)
         tanpa perlu menyisipkan PHP di file .js terpisah. -->
    <script>
        window.BASE_URL = <?= json_encode(BASE_URL) ?>;
    </script>
    <script src="<?= BASE_URL ?>/assets/js/auth/login.js"></script>
</body>
</html>