/* public/assets/js/sidebar.js */

document.addEventListener("DOMContentLoaded", function() {
    // 1. Definisikan Elemen
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const toggleBtn = document.getElementById('toggleSidebar');
    const logoHeader = document.getElementById('logoHeader');
    const logoSpan = document.querySelector('#logoContainer span');
    const profileContainer = document.getElementById('profileContainer');
    const logoContainer = document.getElementById('logoContainer');
    const mobileBtn = document.getElementById('mobileMenuBtn'); // Pastikan ID ini ada di header

    // Group Elemen Teks
    const texts = document.querySelectorAll('.sidebar-text');
    const headers = document.querySelectorAll('.sidebar-header');

    // 2. Ambil Status Terakhir
    let isMinimized = localStorage.getItem('sidebarState') === 'minimized';

    // 3. Fungsi Utama Update Tampilan
    function updateSidebarState() {
        // --- MODE MOBILE (< 768px) ---
        if (window.innerWidth < 768) {
            // Reset ke lebar penuh (behavior mobile)
            sidebar.classList.remove('w-20');
            sidebar.classList.add('w-64');
            
            // Hapus margin main content
            if(mainContent) mainContent.classList.remove('ml-20', 'ml-64'); 
            
            // Tampilkan semua teks
            texts.forEach(el => el.classList.remove('hidden'));
            headers.forEach(el => el.classList.remove('hidden'));
            if(logoSpan) logoSpan.classList.remove('hidden');
            
            // Reset padding container
            if(profileContainer) { profileContainer.classList.remove('justify-center'); profileContainer.classList.add('px-6'); }
            if(logoContainer) { logoContainer.classList.remove('justify-center'); logoContainer.classList.add('px-6'); }
            
            // Hapus kelas preload agar animasi jalan
            document.documentElement.classList.remove('preload-minimized');
            return;
        }

        // --- MODE DESKTOP (>= 768px) ---
        if (isMinimized) {
            // -- KECILKAN (MINIMIZED) --
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-20');
            if(mainContent) { mainContent.classList.remove('ml-64'); mainContent.classList.add('ml-20'); }

            // Sembunyikan Text & Header
            texts.forEach(el => el.classList.add('hidden'));
            headers.forEach(el => el.classList.add('hidden'));
            if(logoSpan) logoSpan.classList.add('hidden');
            if(toggleBtn) toggleBtn.classList.add('hidden');

            // Pusatkan Icon
            if(profileContainer) { profileContainer.classList.remove('px-6'); profileContainer.classList.add('justify-center'); }
            if(logoContainer) { logoContainer.classList.remove('px-6'); logoContainer.classList.add('justify-center'); }

            // Ubah cursor logo
            logoHeader.classList.add('cursor-pointer', 'hover:bg-blue-700/50');

        } else {
            // -- BESARKAN (EXPANDED) --
            sidebar.classList.remove('w-20');
            sidebar.classList.add('w-64');
            if(mainContent) { mainContent.classList.remove('ml-20'); mainContent.classList.add('ml-64'); }

            // Tampilkan Text & Header
            texts.forEach(el => el.classList.remove('hidden'));
            headers.forEach(el => el.classList.remove('hidden'));
            if(logoSpan) logoSpan.classList.remove('hidden');
            if(toggleBtn) toggleBtn.classList.remove('hidden');

            // Reset Padding
            if(profileContainer) { profileContainer.classList.add('px-6'); profileContainer.classList.remove('justify-center'); }
            if(logoContainer) { logoContainer.classList.add('px-6'); logoContainer.classList.remove('justify-center'); }

            logoHeader.classList.remove('cursor-pointer', 'hover:bg-blue-700/50');
        }
        
        // Hapus preload class
        setTimeout(() => {
            document.documentElement.classList.remove('preload-minimized');
        }, 50);
    }

    // 4. Event Listeners
    if(toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            isMinimized = !isMinimized;
            localStorage.setItem('sidebarState', isMinimized ? 'minimized' : 'expanded');
            updateSidebarState();
        });
    }

    if(logoHeader) {
        logoHeader.addEventListener('click', function() {
            if (isMinimized) {
                isMinimized = false;
                localStorage.setItem('sidebarState', 'expanded');
                updateSidebarState();
            }
        });
    }

    if(mobileBtn) {
        mobileBtn.addEventListener('click', function() {
            sidebar.classList.toggle('-translate-x-full');
            if(!sidebar.classList.contains('-translate-x-full')) {
                isMinimized = false; 
                updateSidebarState();
            }
        });
    }

    // Klik di luar sidebar (Mobile Close)
    document.addEventListener('click', function(e) {
        if (window.innerWidth < 768) {
            if (sidebar && !sidebar.contains(e.target) && mobileBtn && !mobileBtn.contains(e.target) && !sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.add('-translate-x-full');
            }
        }
    });

    // 5. Jalankan Awal
    updateSidebarState();
    window.addEventListener('resize', updateSidebarState);
});