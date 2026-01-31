</main> 

    <footer class="bg-white border-t border-gray-200 py-6 px-8 mt-auto shrink-0 z-10 relative">
        <div class="flex flex-col md:flex-row justify-between text-center items-center gap-4">
            <p class="text-xs text-gray-400 font-medium">
                &copy; <?= date('Y') ?> Integrated Computer Laboratory System FIKOM UMI. All rights reserved.
            </p>
        </div>
    </footer>

</div> <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. DEFINISI ELEMEN ---
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const toggleBtn = document.getElementById('toggleSidebar'); // Tombol Panah (Desktop)
        const toggleIcon = document.getElementById('toggleIcon');
        const mobileBtn = document.getElementById('mobileMenuBtn'); // Tombol Garis Tiga (Mobile)
        const logoHeader = document.getElementById('logoHeader'); 
        
        // Elemen yang perlu disembunyikan/ditampilkan
        const textElements = document.querySelectorAll('.sidebar-text');
        const headers = document.querySelectorAll('.sidebar-header');
        const profileContainer = document.getElementById('profileContainer');
        const logoContainer = document.getElementById('logoContainer');

        // Cek status terakhir dari LocalStorage
        let isMinimized = localStorage.getItem('sidebarState') === 'minimized';

        // --- 2. FUNGSI UPDATE TAMPILAN ---
        function updateSidebarState() {
            // [PERBAIKAN PENTING] 
            // Jika layar di bawah 768px (Mobile), PAKSA sidebar jadi normal (w-64)
            // Biarpun statusnya 'minimized', di HP harus tetap lebar agar menu terlihat saat dibuka
            if (window.innerWidth < 768) {
                sidebar.classList.remove('w-20');
                sidebar.classList.add('w-64');
                // Sembunyikan tombol panah desktop di HP
                if(toggleBtn) toggleBtn.classList.add('hidden');
                
                // Pastikan elemen teks terlihat (untuk persiapan jika menu dibuka)
                textElements.forEach(el => el.classList.remove('hidden', 'opacity-0'));
                headers.forEach(el => el.classList.remove('hidden', 'opacity-0'));
                
                // Kembalikan layout logo & profil
                if(profileContainer) profileContainer.classList.add('justify-start');
                if(logoContainer) {
                    const span = logoContainer.querySelector('span');
                    if(span) span.classList.remove('hidden');
                    logoContainer.classList.remove('justify-center', 'w-full');
                }
                return; // Keluar dari fungsi, jangan jalankan logika desktop
            }

            // --- LOGIKA DESKTOP (> 768px) ---
            if (isMinimized) {
                // MODE: KECIL (MINIMIZED)
                sidebar.classList.remove('w-64');
                sidebar.classList.add('w-20');
                
                mainContent.classList.remove('md:ml-64');
                mainContent.classList.add('md:ml-20');
                
                // Ubah Icon Panah
                if(toggleIcon) {
                    toggleIcon.classList.remove('fa-chevron-left');
                    toggleIcon.classList.add('fa-chevron-right');
                }
                
                // Sembunyikan Teks & Header
                textElements.forEach(el => el.classList.add('hidden', 'opacity-0'));
                headers.forEach(el => el.classList.add('hidden', 'opacity-0'));
                
                // Tengahkan Icon
                if(profileContainer) {
                    profileContainer.classList.remove('justify-start');
                    profileContainer.classList.add('justify-center');
                }
                if(logoContainer) {
                    const span = logoContainer.querySelector('span');
                    if(span) span.classList.add('hidden');
                    logoContainer.classList.add('justify-center', 'w-full');
                }
                
                // Cursor Pointer di Header
                if(logoHeader) {
                    logoHeader.classList.add('cursor-pointer', 'hover:bg-blue-700/50');
                    logoHeader.title = "Klik untuk memperbesar";
                }

            } else {
                // MODE: BESAR (EXPANDED)
                sidebar.classList.add('w-64');
                sidebar.classList.remove('w-20');
                
                mainContent.classList.add('md:ml-64');
                mainContent.classList.remove('md:ml-20');
                
                // Ubah Icon Panah
                if(toggleIcon) {
                    toggleIcon.classList.add('fa-chevron-left');
                    toggleIcon.classList.remove('fa-chevron-right');
                }
                
                // Tampilkan Teks & Header
                textElements.forEach(el => el.classList.remove('hidden', 'opacity-0'));
                headers.forEach(el => el.classList.remove('hidden', 'opacity-0'));
                
                // Reset Layout Icon
                if(profileContainer) {
                    profileContainer.classList.add('justify-start');
                    profileContainer.classList.remove('justify-center');
                }
                if(logoContainer) {
                    const span = logoContainer.querySelector('span');
                    if(span) span.classList.remove('hidden');
                    logoContainer.classList.remove('justify-center', 'w-full');
                }
                
                // Hapus Cursor Pointer
                if(logoHeader) {
                    logoHeader.classList.remove('cursor-pointer', 'hover:bg-blue-700/50');
                    logoHeader.removeAttribute('title');
                }
            }
        }

        // --- 3. EKSEKUSI ---
        
        // Jalankan saat pertama kali load
        updateSidebarState();

        // Hapus Class Preload (Delay sedikit agar render tuntas)
        setTimeout(() => {
            document.documentElement.classList.remove('preload-minimized');
        }, 100);

        // Event Listener: Toggle Desktop
        if(toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                isMinimized = !isMinimized;
                localStorage.setItem('sidebarState', isMinimized ? 'minimized' : 'expanded');
                updateSidebarState();
            });
        }

        // Event Listener: Klik Header Logo (Expand Cepat)
        if(logoHeader) {
            logoHeader.addEventListener('click', function() {
                if (isMinimized && window.innerWidth >= 768) {
                    isMinimized = false;
                    localStorage.setItem('sidebarState', 'expanded');
                    updateSidebarState();
                }
            });
        }

        // Event Listener: Toggle Mobile (Hamburger)
        if (mobileBtn) {
            mobileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                sidebar.classList.toggle('-translate-x-full');
            });
        }
        
        // [PERBAIKAN] Event Listener: Resize Window
        // Agar layout menyesuaikan jika user memutar layar HP/Tablet
        window.addEventListener('resize', updateSidebarState);

        // Event Listener: Klik di Luar Sidebar (Tutup Mobile Menu)
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 768) {
                const isClickInside = sidebar.contains(e.target) || (mobileBtn && mobileBtn.contains(e.target));
                if (!isClickInside && !sidebar.classList.contains('-translate-x-full')) {
                    sidebar.classList.add('-translate-x-full');
                }
            }
        });
    });
</script>

</body>
</html>