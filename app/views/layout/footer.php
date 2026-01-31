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
        // 1. Definisi Elemen (Sesuai ID di sidebar.php)
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        
        // Tombol Desktop (Panah) & Icon-nya
        const toggleBtn = document.getElementById('toggleSidebar'); 
        const toggleIcon = document.getElementById('toggleIcon');
        
        // Tombol Mobile (Garis Tiga)
        const mobileBtn = document.getElementById('mobileMenuBtn'); 
        
        // Header Logo (Bisa diklik untuk membesarkan)
        const logoHeader = document.getElementById('logoHeader'); 

        // Elemen yang perlu disembunyikan saat minimized
        const textElements = document.querySelectorAll('.sidebar-text');
        const headers = document.querySelectorAll('.sidebar-header');
        const profileContainer = document.getElementById('profileContainer');
        const logoContainer = document.getElementById('logoContainer');

        // 2. Cek Status Simpanan (LocalStorage)
        let isMinimized = localStorage.getItem('sidebarState') === 'minimized';

        // 3. Fungsi Update Tampilan Sidebar (Otak Utamanya)
        function updateSidebarState() {
            // Jangan jalankan logika minimize jika di layar HP (Mobile)
            if (window.innerWidth < 768) return; 

            if (isMinimized) {
                // --- MODE MINIMIZED (KECIL) ---
                sidebar.classList.remove('w-64');
                sidebar.classList.add('w-20');
                
                mainContent.classList.remove('md:ml-64');
                mainContent.classList.add('md:ml-20');
                
                // Ubah Ikon Panah jadi ke Kanan
                if(toggleIcon) {
                    toggleIcon.classList.remove('fa-chevron-left');
                    toggleIcon.classList.add('fa-chevron-right');
                }
                
                // Sembunyikan Teks & Header Menu
                textElements.forEach(el => el.classList.add('hidden', 'opacity-0'));
                headers.forEach(el => el.classList.add('hidden', 'opacity-0'));
                
                // Tengahkan Logo & Profil
                if(profileContainer) {
                    profileContainer.classList.remove('justify-start');
                    profileContainer.classList.add('justify-center');
                }
                if(logoContainer) {
                    logoContainer.classList.add('justify-center');
                    const span = logoContainer.querySelector('span');
                    if(span) span.classList.add('hidden');
                }

                // Ubah kursor header jadi pointer (memberi tahu bisa diklik)
                if(logoHeader) {
                    logoHeader.classList.add('cursor-pointer', 'hover:bg-blue-700/50');
                    logoHeader.title = "Klik untuk memperbesar";
                }

            } else {
                // --- MODE EXPANDED (BESAR) ---
                sidebar.classList.add('w-64');
                sidebar.classList.remove('w-20');
                
                mainContent.classList.add('md:ml-64');
                mainContent.classList.remove('md:ml-20');
                
                // Kembalikan Ikon Panah ke Kiri
                if(toggleIcon) {
                    toggleIcon.classList.add('fa-chevron-left');
                    toggleIcon.classList.remove('fa-chevron-right');
                }
                
                // Tampilkan Teks kembali
                textElements.forEach(el => el.classList.remove('hidden', 'opacity-0'));
                headers.forEach(el => el.classList.remove('hidden', 'opacity-0'));
                
                // Kembalikan Layout ke Kiri
                if(profileContainer) {
                    profileContainer.classList.add('justify-start');
                    profileContainer.classList.remove('justify-center');
                }
                if(logoContainer) {
                    logoContainer.classList.remove('justify-center');
                    const span = logoContainer.querySelector('span');
                    if(span) span.classList.remove('hidden');
                }

                // Hapus kursor pointer di header
                if(logoHeader) {
                    logoHeader.classList.remove('cursor-pointer', 'hover:bg-blue-700/50');
                    logoHeader.removeAttribute('title');
                }
            }
        }

        // 4. Jalankan Update Pertama Kali (Saat halaman dimuat)
        updateSidebarState();
        
        // 5. Hapus Class Preload (Agar transisi animasi aktif kembali)
        // Delay sedikit 100ms agar browser selesai merender layout awal
        setTimeout(() => {
            document.documentElement.classList.remove('preload-minimized');
        }, 100);

        // 6. Event Listener: Klik Tombol Panah (Desktop)
        if(toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation(); // Biar tidak bentrok dengan klik header
                isMinimized = !isMinimized; // Balik status (True jadi False, dst)
                localStorage.setItem('sidebarState', isMinimized ? 'minimized' : 'expanded');
                updateSidebarState();
            });
        }

        // 7. Event Listener: Klik Header Logo (Desktop - Cara Cepat Membesarkan)
        if(logoHeader) {
            logoHeader.addEventListener('click', function() {
                if(isMinimized) {
                    isMinimized = false;
                    localStorage.setItem('sidebarState', 'expanded');
                    updateSidebarState();
                }
            });
        }

        // 8. Event Listener: Tombol Hamburger (Mobile)
        if(mobileBtn) {
            mobileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                sidebar.classList.toggle('-translate-x-full');
            });
        }
        
        // 9. Event Listener: Klik di Luar Sidebar (Khusus Mobile)
        // Jika user klik area konten saat menu terbuka di HP, menu akan tertutup otomatis
        document.addEventListener('click', function(e) {
            if(window.innerWidth < 768) {
                // Jika klik bukan di sidebar DAN bukan di tombol menu
                if(!sidebar.contains(e.target) && mobileBtn && !mobileBtn.contains(e.target)) {
                    sidebar.classList.add('-translate-x-full');
                }
            }
        });
    });
</script>

</body>
</html>