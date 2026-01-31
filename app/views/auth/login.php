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
        
        #modalAlert {
            transition: visibility 0s linear 0.3s, opacity 0.3s ease-out;
            visibility: hidden;
            opacity: 0;
        }
        #modalAlert.show {
            visibility: visible;
            opacity: 1;
            transition-delay: 0s;
        }
        
        #modalContent {
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease-out;
            transform: scale(0.9);
            opacity: 0;
        }
        #modalAlert.show #modalContent {
            transform: scale(1);
            opacity: 1;
        }

        #modalBackdrop {
            transition: opacity 0.3s ease-out;
            opacity: 0;
        }
        #modalAlert.show #modalBackdrop {
            opacity: 1;
        }
    </style>
</head>
<body class="h-screen w-screen flex items-center justify-center overflow-hidden font-sans selection:bg-cyan-500 selection:text-white">

    <div class="bg-container">
        <div class="bg-slide active" style="background-image: url('/ICLABS/public/assets/img/startup.webp');"></div>
        <div class="bg-slide" style="background-image: url('/ICLABS/public/assets/img/IoT.webp');"></div>
        <div class="bg-slide" style="background-image: url('/ICLABS/public/assets/img/CV.webp');"></div>
        <div class="bg-slide" style="background-image: url('/ICLABS/public/assets/img/DS.webp');"></div>
        <div class="bg-slide" style="background-image: url('/ICLABS/public/assets/img/Comnet.webp');"></div>
        <div class="bg-slide" style="background-image: url('/ICLABS/public/assets/img/Micro.webp');"></div>
        <div class="bg-slide" style="background-image: url('/ICLABS/public/assets/img/Mulmed.webp');"></div>
        
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

            <div class="flex justify-end">
                <a href="#" class="text-xs text-slate-400 hover:text-cyan-400 transition-colors">Lupa Password?</a>
            </div>

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

    <script>
        const slides = document.querySelectorAll('.bg-slide');
        let currentSlide = 0;
        setInterval(() => {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }, 5000);

        function togglePass() {
            const pwd = document.getElementById('passwordInput');
            const icon = document.getElementById('togglePassword');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
                icon.classList.add('text-cyan-400');
            } else {
                pwd.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
                icon.classList.remove('text-cyan-400');
            }
        }

        function showModal(type, title, message) {
            const modal = document.getElementById('modalAlert');
            const iconBg = document.getElementById('modalIconBg');
            const icon = document.getElementById('modalIcon');
            const btn = document.getElementById('modalBtn');

            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalMessage').innerText = message;

            if (type === 'success') {
                iconBg.className = 'w-20 h-20 rounded-full flex items-center justify-center mb-5 bg-green-500/20 text-green-400 shadow-[0_0_20px_rgba(74,222,128,0.4)]';
                icon.className = 'fas fa-check text-4xl';
                btn.classList.add('hidden');
            } else {
                iconBg.className = 'w-20 h-20 rounded-full flex items-center justify-center mb-5 bg-red-500/20 text-red-400 shadow-[0_0_20px_rgba(248,113,113,0.4)]';
                icon.className = 'fas fa-times text-4xl';
                btn.className = 'w-full py-3.5 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02] active:scale-95 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 shadow-red-900/30';
                btn.classList.remove('hidden');
            }
            modal.classList.add('show');
        }

        function closeModal() {
            document.getElementById('modalAlert').classList.remove('show');
        }

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault(); 

            const emailInput = document.getElementById('emailInput');
            const passwordInput = document.getElementById('passwordInput');
            
            const email = emailInput.value.trim();
            const password = passwordInput.value.trim();
            
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnIcon = document.getElementById('btnIcon');
            const btnSpinner = document.getElementById('btnSpinner');

            if (!email || !password) {
                showModal('error', 'Data Tidak Lengkap', 'Harap isi Email dan Password Anda.');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
            btnText.innerText = 'Memproses...';
            btnIcon.classList.add('hidden');
            btnSpinner.classList.remove('hidden');

            const formData = new FormData();
            formData.append('email', email);
            formData.append('password', password);

            fetch('<?= BASE_URL ?>/auth/login', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showModal('success', data.title, data.message);
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    showModal('error', data.title, data.message);
                    resetButton();
                    
                    emailInput.value = '';
                    passwordInput.value = '';

                    emailInput.focus();
                }
            })
            .catch(error => {
                console.error(error);
                showModal('error', 'Kesalahan Sistem', 'Terjadi masalah koneksi atau server.');
                resetButton();
            });

            function resetButton() {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
                btnText.innerText = 'Masuk Sekarang';
                btnIcon.classList.remove('hidden');
                btnSpinner.classList.add('hidden');
            }
        });
    </script>
</body>
</html>