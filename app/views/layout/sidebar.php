<script>
    // Cek state segera sebelum render body selesai
    if (localStorage.getItem('sidebarState') === 'minimized' && window.innerWidth >= 768) {
        document.documentElement.classList.add('preload-minimized');
    }
</script>
<style>
    /* Style paksaan saat loading awal agar tidak berkedip */
    .preload-minimized #sidebar { width: 5rem !important; } /* w-20 */
    .preload-minimized #mainContent { margin-left: 5rem !important; }
    .preload-minimized .sidebar-text, 
    .preload-minimized .sidebar-header,
    .preload-minimized #toggleSidebar,
    .preload-minimized #logoContainer span { display: none !important; }
    
    .preload-minimized #logoHeader { cursor: pointer !important; }
    .preload-minimized #logoHeader:hover { background-color: rgba(29, 78, 216, 0.5); }
    
    .preload-minimized #profileContainer,
    .preload-minimized #logoContainer { justify-content: center !important; width: 100% !important; }
    
    /* Matikan transisi saat loading awal */
    .preload-minimized * { transition: none !important; }
</style>

<aside id="sidebar" class="fixed top-0 left-0 z-50 h-screen transition-all duration-300 bg-white border-r border-gray-200 shadow-2xl flex flex-col font-sans w-64 -translate-x-full md:translate-x-0">
    
    <div id="logoHeader" class="h-20 flex items-center justify-between px-4 border-b border-gray-100 bg-gradient-to-r from-blue-700 to-cyan-600 shrink-0 relative transition-all duration-300">
        
        <div class="flex items-center gap-3 text-white overflow-hidden transition-all duration-300" id="logoContainer">
            <img src="<?= BASE_URL ?>/assets/img/Logo_ICLABS_White.webp" alt="ICLABS Logo" 
            class="w-8 h-8 object-contain animate-pulse filter drop-shadow-lg shadow-white-400 shrink-0">
            <span class="text-xl font-extrabold tracking-wider font-mono sidebar-text opacity-100 transition-opacity duration-200">ICLABS</span>
        </div>
        
        <button id="toggleSidebar" class="hidden md:flex text-white/80 hover:text-white transition-colors absolute right-4 focus:outline-none">
            <i class="fas fa-chevron-left text-xs transition-transform duration-300" id="toggleIcon"></i>
        </button>
    </div>
    
    <div class="p-4 border-b border-gray-100 bg-blue-50/30 flex flex-col gap-3 shrink-0 group relative overflow-hidden transition-all duration-300" id="profileSection">
        <div class="absolute top-0 right-0 w-20 h-20 bg-blue-100 rounded-full blur-2xl -mr-10 -mt-10 opacity-50"></div>

        <div class="flex items-center gap-3 relative z-10 transition-all duration-300 justify-start" id="profileContainer">
            <div class="relative shrink-0">
                <?php 
                    $photoName = $user['photo_profile'] ?? '';
                    $physicalPath = 'uploads/profile/' . $photoName;
                    if (!empty($photoName) && file_exists($physicalPath)) {
                        $avatarUrl = BASE_URL . '/uploads/profile/' . $photoName;
                    } else {
                        $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=random&bold=true";
                    }
                ?>
                <img src="<?= $avatarUrl ?>" alt="Profile" class="w-10 h-10 rounded-full border-2 border-white shadow-md object-cover">
                <div class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 border-2 border-white rounded-full"></div>
            </div>
            
            <div class="overflow-hidden sidebar-text opacity-100 transition-opacity duration-200">
                <h4 class="font-bold text-gray-800 text-xs truncate leading-tight w-24" title="<?= $_SESSION['name'] ?>">
                    <?= $_SESSION['name'] ?>
                </h4>
                <span class="inline-block px-1.5 py-0.5 mt-0.5 text-[9px] font-bold text-blue-600 bg-white border border-blue-100 rounded-full uppercase tracking-wide truncate max-w-full shadow-sm">
                    <?= $_SESSION['jabatan'] ?>
                </span>
            </div>
        </div>

        <?php $roleLink = strtolower(str_replace(' ', '', $_SESSION['role'])); ?>
        <a href="<?= BASE_URL ?>/<?= $roleLink ?>/profile" 
           class="sidebar-text opacity-100 transition-opacity duration-200 relative z-10 w-full flex items-center justify-center py-1.5 rounded-lg bg-white border border-gray-200 text-gray-400 hover:text-blue-600 hover:border-blue-300 hover:shadow-sm text-[10px] gap-2 mt-1">
            <i class="fas fa-pen"></i> <span>Edit Profil</span>
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 custom-scrollbar">
        <?php 
            $role = strtolower(str_replace(' ', '', $_SESSION['role'])); 
            $current_uri = $_SERVER['REQUEST_URI'];
            
            function isActive($uri, $keyword) {
                return strpos($uri, $keyword) !== false 
                    ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30 border-transparent' 
                    : 'text-gray-600 hover:bg-gray-50 hover:text-blue-600 border-transparent hover:shadow-sm';
            }
            function iconColor($uri, $keyword) {
                return strpos($uri, $keyword) !== false ? 'text-white' : 'text-gray-400 group-hover:text-blue-600';
            }
        ?>
        
        <?php function renderMenuItem($url, $icon, $label, $activeCheck, $currentUri) { ?>
            <a href="<?= $url ?>" title="<?= $label ?>"
               class="flex items-center px-3 py-3 rounded-xl border transition-all duration-200 group relative overflow-hidden <?= isActive($currentUri, $activeCheck) ?>">
                <i class="fas <?= $icon ?> w-6 text-center text-sm transition-colors duration-200 shrink-0 <?= iconColor($currentUri, $activeCheck) ?>"></i>
                <span class="font-semibold text-sm ml-3 sidebar-text opacity-100 transition-all duration-200 whitespace-nowrap"><?= $label ?></span>
                
                <div class="sidebar-tooltip absolute left-14 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 invisible transition-all duration-200 z-50 whitespace-nowrap shadow-lg">
                    <?= $label ?>
                </div>
            </a>
        <?php } ?>

        <div class="mb-6">
            <p class="px-2 text-[9px] font-extrabold text-gray-400 uppercase tracking-widest mb-2 ml-1 sidebar-header transition-opacity duration-200">Utama</p>
            <?php renderMenuItem(BASE_URL . "/$role/dashboard", 'fa-home', 'Dashboard', 'dashboard', $current_uri); ?>
        </div>

        <?php if($_SESSION['role'] == 'User'): ?>
            <div class="mb-6"> 
                <p class="px-2 text-[9px] font-extrabold text-gray-400 uppercase tracking-widest mb-2 ml-1 sidebar-header transition-opacity duration-200">Aktivitas</p>
                <?php renderMenuItem(BASE_URL . '/user/scan', 'fa-qrcode', 'Presensi QR', 'scan', $current_uri); ?>
                <?php renderMenuItem(BASE_URL . '/user/logbook', 'fa-book-open', 'Logbook', 'logbook', $current_uri); ?>
                <?php renderMenuItem(BASE_URL . '/user/schedule', 'fa-calendar-alt', 'Jadwal Saya', 'schedule', $current_uri); ?>
            </div>
        <?php endif; ?>

        <?php if($_SESSION['role'] == 'Admin' || $_SESSION['role'] == ''): ?>
            <div class="mb-6">
                <p class="px-2 text-[9px] font-extrabold text-gray-400 uppercase tracking-widest mb-2 ml-1 sidebar-header transition-opacity duration-200">Manajemen</p>
                <?php renderMenuItem(BASE_URL . "/$role/manageUsers", 'fa-users-cog', 'Kelola User', 'manageUsers', $current_uri); ?>
                <?php renderMenuItem(BASE_URL . "/$role/schedule", 'fa-calendar-check', 'Jadwal Lab', 'schedule', $current_uri); ?>
            </div>

            <div class="mb-6">
                <p class="px-2 text-[9px] font-extrabold text-gray-400 uppercase tracking-widest mb-2 ml-1 sidebar-header transition-opacity duration-200">Monitoring</p>
                <?php renderMenuItem(BASE_URL . "/$role/logbook", 'fa-clipboard-list', 'Logbook Asisten', 'logbook', $current_uri); ?>
                <?php renderMenuItem(BASE_URL . "/$role/monitorAttendance", 'fa-history', 'Rekap Presensi', 'monitorAttendance', $current_uri); ?>
            </div>
        <?php endif; ?>
    </nav>

    <div class="p-4 border-t border-gray-100 shrink-0 bg-white">
        <a href="<?= BASE_URL ?>/auth/logout" title="Keluar"
           class="flex items-center justify-center w-full py-2.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-500 hover:text-white transition-all duration-200 text-sm font-bold gap-2 shadow-sm group border border-red-100 relative">
            <i class="fas fa-sign-out-alt group-hover:-translate-x-1 transition-transform"></i> 
            <span class="sidebar-text opacity-100 transition-all duration-200 whitespace-nowrap">Keluar</span>
        </a>
    </div>
</aside>

<div id="mainContent" class="md:ml-64 flex flex-col min-h-screen bg-gray-50 transition-all duration-300 relative">
    
    <header class="h-16 bg-white border-b flex items-center justify-between px-6 shadow-sm md:hidden shrink-0 sticky top-0 z-30">
        <span class="font-bold text-lg text-blue-600 font-mono tracking-wider">ICLABS</span>
        <button id="mobileMenuBtn" class="text-gray-600 focus:outline-none p-2 rounded-lg hover:bg-gray-100">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </header>

    <main class="flex-1 p-4 md:p-8 overflow-x-hidden">

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const toggleBtn = document.getElementById('toggleSidebar');
        const toggleIcon = document.getElementById('toggleIcon');
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const logoHeader = document.getElementById('logoHeader'); 
        
        const textElements = document.querySelectorAll('.sidebar-text');
        const headers = document.querySelectorAll('.sidebar-header');
        const profileContainer = document.getElementById('profileContainer');
        const logoContainer = document.getElementById('logoContainer');

        let isMinimized = localStorage.getItem('sidebarState') === 'minimized';

        function updateSidebarState() {
            if (window.innerWidth < 768) return; 

            if (isMinimized) {
                // STATE: MINIMIZED
                sidebar.classList.remove('w-64');
                sidebar.classList.add('w-20');
                mainContent.classList.remove('md:ml-64');
                mainContent.classList.add('md:ml-20');
                
                // Sembunyikan Tombol Toggle, Aktifkan Logo Click
                toggleBtn.classList.add('hidden', 'opacity-0');
                toggleBtn.classList.remove('flex');
                logoHeader.classList.add('cursor-pointer', 'hover:bg-blue-700/50');
                logoHeader.title = "Klik untuk memperlebar";
                
                // Sembunyikan Text
                textElements.forEach(el => {
                    el.classList.add('opacity-0', 'w-0', 'hidden');
                    el.classList.remove('opacity-100', 'w-auto');
                });
                headers.forEach(el => el.classList.add('opacity-0', 'hidden'));
                
                // Center Icons
                profileContainer.classList.remove('justify-start');
                profileContainer.classList.add('justify-center');
                logoContainer.querySelector('span').classList.add('hidden');
                logoContainer.classList.add('justify-center', 'w-full');

            } else {
                // STATE: EXPANDED
                sidebar.classList.add('w-64');
                sidebar.classList.remove('w-20');
                mainContent.classList.add('md:ml-64');
                mainContent.classList.remove('md:ml-20');
                
                // Tampilkan Tombol Toggle, Matikan Logo Click
                toggleBtn.classList.remove('hidden', 'opacity-0');
                toggleBtn.classList.add('flex');
                logoHeader.classList.remove('cursor-pointer', 'hover:bg-blue-700/50');
                logoHeader.removeAttribute('title');
                
                // Tampilkan Text
                textElements.forEach(el => {
                    el.classList.remove('opacity-0', 'w-0', 'hidden');
                    el.classList.add('opacity-100', 'w-auto');
                });
                headers.forEach(el => el.classList.remove('opacity-0', 'hidden'));
                
                // Align Start
                profileContainer.classList.add('justify-start');
                profileContainer.classList.remove('justify-center');
                logoContainer.querySelector('span').classList.remove('hidden');
                logoContainer.classList.remove('justify-center', 'w-full');
            }
        }

        // Jalankan update state JS untuk menerapkan class yang benar ke DOM
        updateSidebarState();

        // 3. HAPUS CLASS PRELOAD SETELAH DOM READY
        // Ini kunci agar transisi kembali berfungsi setelah halaman selesai dimuat
        setTimeout(() => {
            document.documentElement.classList.remove('preload-minimized');
        }, 100);

        // EVENT: KLIK TOMBOL TOGGLE (MENGECILKAN)
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation(); 
            isMinimized = true;
            localStorage.setItem('sidebarState', 'minimized');
            updateSidebarState();
        });

        // EVENT: KLIK LOGO HEADER (MEMBESARKAN)
        logoHeader.addEventListener('click', function() {
            if (isMinimized) {
                isMinimized = false;
                localStorage.setItem('sidebarState', 'expanded');
                updateSidebarState();
            }
        });

        // Mobile Menu
        mobileBtn.addEventListener('click', function() {
            sidebar.classList.toggle('-translate-x-full');
            if(!sidebar.classList.contains('-translate-x-full')) {
                isMinimized = false; 
                updateSidebarState();
            }
        });

        document.addEventListener('click', function(e) {
            if (window.innerWidth < 768) {
                if (!sidebar.contains(e.target) && !mobileBtn.contains(e.target) && !sidebar.classList.contains('-translate-x-full')) {
                    sidebar.classList.add('-translate-x-full');
                }
            }
        });
        
        // CSS Tooltip tambahan
        const style = document.createElement('style');
        style.innerHTML = `
            .w-20 .sidebar-tooltip {
                visibility: visible !important;
                opacity: 1 !important;
                left: 70px !important;
            }
            .w-20 a:hover .sidebar-tooltip {
                opacity: 1;
            }
        `;
        document.head.appendChild(style);
    });
</script>
