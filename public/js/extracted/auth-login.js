
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

            fetch(`${window.BASE_URL}/auth/login`, {
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
    