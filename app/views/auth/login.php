<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login System - ICLABS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Container Background */
        .bg-container {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;
            background-color: #0f172a;
        }
        
        /* Slide Item - LOGIKA BARU (Transition instead of Keyframes) */
        .bg-slide {
            position: absolute; inset: 0;
            background-size: cover; background-position: center;
            opacity: 0; 
            transform: scale(1);
            /* Transisi Opacity 2s, Transisi Zoom 15s linear agar smooth */
            transition: opacity 2s ease-in-out, transform 15s linear;
        }
        
        /* State Aktif */
        .bg-slide.active { 
            opacity: 1; 
            transform: scale(1.15); /* Zoom in pelan-pelan saat aktif */
        }

        /* Overlay Gelap untuk Background agar teks selalu kontras */
        .bg-overlay {
            position: absolute; inset: 0; background: rgba(0,0,0,0.4); z-index: 0;
        }

        /* Dark Glassmorphism Modal */
        .glass-dark {
            background: rgba(15, 23, 42, 0.65); /* Slate-900 dengan opacity */
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        /* Input Autofill Fix (Agar warna tidak berubah jadi putih/kuning default browser) */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active{
            -webkit-box-shadow: 0 0 0 30px #1e293b inset !important;
            -webkit-text-fill-color: white !important;
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>
</head>
<body class="h-screen w-screen flex items-center justify-center overflow-hidden font-sans selection:bg-cyan-500 selection:text-white">

    <div class="bg-container">
        <div class="bg-slide active" style="background-image: url('/iclabs_v2/public/assets/img/startup.webp');"></div>
        <div class="bg-slide" style="background-image: url('/iclabs_v2/public/assets/img/IoT.webp');"></div>
        <div class="bg-slide" style="background-image: url('/iclabs_v2/public/assets/img/CV.webp');"></div>
        <div class="bg-slide" style="background-image: url('/iclabs_v2/public/assets/img/DS.webp');"></div>
        <div class="bg-slide" style="background-image: url('/iclabs_v2/public/assets/img/Comnet.webp');"></div>
        <div class="bg-slide" style="background-image: url('/iclabs_v2/public/assets/img/Micro.webp');"></div>
        <div class="bg-slide" style="background-image: url('/iclabs_v2/public/assets/img/Mulmed.webp');"></div>
        
        <div class="bg-overlay"></div>
    </div>

    <div class="glass-dark relative z-10 p-10 rounded-3xl w-full max-w-md border-t border-white/20 animate__animated animate__zoomIn">
        
        <div class="text-center mb-8">
            <div class="relative inline-block group">
                <div class="absolute -inset-1 bg-gradient-to-r from-cyan-400 to-blue-600 rounded-2xl blur opacity-25 group-hover:opacity-75 transition duration-1000 group-hover:duration-200"></div>
                <div class="relative w-20 h-20 bg-slate-900 rounded-2xl flex items-center justify-center shadow-2xl border border-white/10">
                    <img src="<?= BASE_URL ?>/assets/img/Logo_ICLABS.webp" alt="ICLABS Logo" 
                    class="w-12 h-12 object-contain animate-pulse filter drop-shadow-lg shadow-cyan-400">
                    <!-- <i class="fas fa-microchip text-cyan-400 text-4xl animate-pulse"></i> -->
                </div>
            </div>
            <h1 class="text-4xl font-extrabold text-white mt-4 tracking-wider">ICLABS</h1>
            <p class="text-slate-400 text-sm mt-1 font-light tracking-wide">Monitoring Asisten Laboratorium</p>
        </div>

        <form action="<?= BASE_URL ?>/auth/login" method="POST" class="space-y-5" onsubmit="return validateLogin()">
            
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-envelope text-slate-400 group-focus-within:text-cyan-400 transition-colors"></i>
                </div>
                <input type="email" name="email" required 
                    class="w-full pl-10 pr-4 py-3.5 bg-slate-800/50 border border-slate-600 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400 transition-all"
                    placeholder="Email Address">
            </div>

            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-lock text-slate-400 group-focus-within:text-cyan-400 transition-colors"></i>
                </div>
                <input type="password" name="password" id="passwordInput" required minlength="8"
                    class="w-full pl-10 pr-10 py-3.5 bg-slate-800/50 border border-slate-600 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400 transition-all"
                    placeholder="Password">
                
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <i class="fas fa-eye-slash text-slate-400 hover:text-white cursor-pointer transition" id="togglePassword" onclick="togglePass()"></i>
                </div>
            </div>

            <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-blue-600 to-cyan-500 text-white font-bold rounded-xl shadow-lg shadow-cyan-500/20 hover:shadow-cyan-500/40 transform hover:-translate-y-0.5 transition-all duration-300 uppercase tracking-wider text-sm flex items-center justify-center gap-2">
                <span>Masuk Sekarang</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-white/5 text-center">
            <p class="text-xs text-slate-500 font-light">
                &copy; 2025 Laboratorium Komputer FIK UMI
            </p>
        </div>
    </div>

    <script>
        // --- LOGIK BACKGROUND (Fix Poin 1) ---
        const slides = document.querySelectorAll('.bg-slide');
        let currentSlide = 0;
        const SLIDE_DELAY = 7000; // 7 Detik

        function nextSlide() {
            // Hilangkan class active dari slide sekarang (dia akan fade out pelan-pelan)
            slides[currentSlide].classList.remove('active');
            
            // Pindah index
            currentSlide = (currentSlide + 1) % slides.length;
            
            // Tambah class active ke slide baru (dia akan fade in + zoom in)
            slides[currentSlide].classList.add('active');
        }
        setInterval(nextSlide, SLIDE_DELAY);

        // --- PASSWORD TOGGLE (Fix Ikon Terbalik) ---
        function togglePass() {
            const passwordInput = document.getElementById('passwordInput');
            const toggleIcon = document.getElementById('togglePassword');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text'; // Tampilkan
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye'); // Mata Terbuka
                toggleIcon.classList.add('text-cyan-400');
            } else {
                passwordInput.type = 'password'; // Sembunyikan
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash'); // Mata Dicoret
                toggleIcon.classList.remove('text-cyan-400');
            }
        }

        // --- VALIDASI TAMBAHAN (Poin 3) ---
        function validateLogin() {
            const pass = document.getElementById('passwordInput').value;
            if (pass.length < 8) {
                alert("Password harus minimal 8 karakter!");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>