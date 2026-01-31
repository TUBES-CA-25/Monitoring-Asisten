<script>
    // Cek status sidebar di localStorage segera saat loading
    if (localStorage.getItem('sidebarState') === 'minimized' && window.innerWidth >= 768) {
        document.documentElement.classList.add('preload-minimized');
    }
</script>

<style>
    /* Style Paksaan untuk Preload (agar transisi mati sementara) */
    .preload-minimized #sidebar { width: 5rem !important; } /* w-20 */
    .preload-minimized #mainContent { margin-left: 5rem !important; }
    
    /* Sembunyikan elemen teks saat preload */
    .preload-minimized .sidebar-text, 
    .preload-minimized .sidebar-header,
    .preload-minimized #toggleSidebar,
    .preload-minimized #logoContainer span { display: none !important; }
    
    /* Style Logo saat minimized */
    .preload-minimized #logoHeader { cursor: pointer !important; }
    .preload-minimized #profileContainer,
    .preload-minimized #logoContainer { justify-content: center !important; width: 100% !important; }
    
    /* Matikan animasi saat loading */
    .preload-minimized * { transition: none !important; }

    /* Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
    .custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #94a3b8; }

    /* Tooltip Custom (Muncul hanya saat sidebar kecil) */
    .sidebar-tooltip {
        visibility: hidden; opacity: 0; left: 4rem; position: absolute; pointer-events: none;
        background-color: #1f2937; color: white; padding: 4px 8px; border-radius: 4px;
        font-size: 0.75rem; z-index: 50; white-space: nowrap; transition: opacity 0.2s;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    /* Trik CSS: Tooltip muncul jika parent (.group) dihover DAN sidebar sedang w-20 */
    #sidebar.w-20 .group:hover .sidebar-tooltip { visibility: visible; opacity: 1; left: 3.5rem; pointer-events: auto; }
</style>

<aside id="sidebar" class="fixed top-0 left-0 z-50 h-screen transition-all duration-300 bg-white border-r border-gray-200 shadow-2xl flex flex-col font-sans w-64 -translate-x-full md:translate-x-0">
    
    <div id="logoHeader" class="h-20 flex items-center justify-between px-4 border-b border-gray-100 bg-gradient-to-r from-blue-700 to-cyan-600 shrink-0 relative transition-all duration-300">
        <div class="flex items-center gap-3 text-white overflow-hidden transition-all duration-300" id="logoContainer">
            <img src="<?= BASE_URL ?>/assets/img/Logo_ICLABS_White.webp" alt="Logo" class="w-8 h-8 object-contain filter drop-shadow-md shrink-0">
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
                    $avatarUrl = (!empty($photoName) && file_exists($physicalPath)) 
                        ? BASE_URL . '/uploads/profile/' . $photoName 
                        : "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['name'] ?? 'User') . "&background=random&bold=true";
                ?>
                <img src="<?= $avatarUrl ?>" alt="Profile" class="w-10 h-10 rounded-full border-2 border-white shadow-md object-cover">
                <div class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 border-2 border-white rounded-full"></div>
            </div>
            
            <div class="overflow-hidden sidebar-text opacity-100 transition-opacity duration-200">
                <h4 class="font-bold text-gray-800 text-xs truncate leading-tight w-24" title="<?= $_SESSION['name'] ?? '' ?>">
                    <?= $_SESSION['name'] ?? 'Guest' ?>
                </h4>
                <span class="inline-block px-1.5 py-0.5 mt-0.5 text-[9px] font-bold text-blue-600 bg-white border border-blue-100 rounded-full uppercase tracking-wide truncate max-w-full shadow-sm">
                    <?= $_SESSION['jabatan'] ?? 'Member' ?>
                </span>
            </div>
        </div>

        <?php $roleLink = strtolower(str_replace(' ', '', $_SESSION['role'] ?? 'user')); ?>
        <a href="<?= BASE_URL ?>/<?= $roleLink ?>/profile" 
           class="sidebar-text opacity-100 transition-opacity duration-200 relative z-10 w-full flex items-center justify-center py-1.5 rounded-lg bg-white border border-gray-200 text-gray-400 hover:text-blue-600 hover:border-blue-300 hover:shadow-sm text-[10px] gap-2 mt-1">
            <i class="fas fa-pen"></i> <span>Edit Profil</span>
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 custom-scrollbar">
        <?php 
            $role = strtolower(str_replace(' ', '', $_SESSION['role'] ?? '')); 
            $current_uri = $_SERVER['REQUEST_URI'];
            
            function renderMenuItem($url, $icon, $label, $keyword, $currentUri) {
                $isActive = strpos($currentUri, $keyword) !== false;
                $activeClass = $isActive 
                    ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30 border-transparent' 
                    : 'text-gray-600 hover:bg-gray-50 hover:text-blue-600 border-transparent hover:shadow-sm';
                $iconColor = $isActive ? 'text-white' : 'text-gray-400 group-hover:text-blue-600';
                
                echo '
                <a href="'.$url.'" title="'.$label.'" class="flex items-center px-3 py-3 rounded-xl border transition-all duration-200 group relative overflow-visible '.$activeClass.'">
                    <i class="fas '.$icon.' w-6 text-center text-sm transition-colors duration-200 shrink-0 '.$iconColor.'"></i>
                    <span class="font-semibold text-sm ml-3 sidebar-text opacity-100 transition-all duration-200 whitespace-nowrap">'.$label.'</span>
                    <div class="sidebar-tooltip">'.$label.'</div>
                </a>';
            }
        ?>

        <div class="mb-6">
            <p class="px-2 text-[9px] font-extrabold text-gray-400 uppercase tracking-widest mb-2 ml-1 sidebar-header">Utama</p>
            <?php renderMenuItem(BASE_URL . "/$role/dashboard", 'fa-home', 'Dashboard', 'dashboard', $current_uri); ?>
        </div>

        <?php if(($_SESSION['role'] ?? '') == 'User'): ?>
            <div class="mb-6"> 
                <p class="px-2 text-[9px] font-extrabold text-gray-400 uppercase tracking-widest mb-2 ml-1 sidebar-header">Aktivitas</p>
                <?php renderMenuItem(BASE_URL . '/user/scan', 'fa-qrcode', 'Presensi QR', 'scan', $current_uri); ?>
                <?php renderMenuItem(BASE_URL . '/user/logbook', 'fa-book-open', 'Logbook', 'logbook', $current_uri); ?>
                <?php renderMenuItem(BASE_URL . '/user/schedule', 'fa-calendar-alt', 'Jadwal Saya', 'schedule', $current_uri); ?>
            </div>
        <?php endif; ?>

        <?php if(in_array($_SESSION['role'] ?? '', ['Admin', 'Kepala Lab', 'Super Admin'])): ?>
            <div class="mb-6">
                <p class="px-2 text-[9px] font-extrabold text-gray-400 uppercase tracking-widest mb-2 ml-1 sidebar-header">Manajemen</p>
                <?php renderMenuItem(BASE_URL . "/$role/manageUsers", 'fa-users-cog', 'Kelola User', 'manageUsers', $current_uri); ?>
                <?php renderMenuItem(BASE_URL . "/$role/schedule", 'fa-calendar-check', 'Jadwal Lab', 'schedule', $current_uri); ?>
            </div>

            <div class="mb-6">
                <p class="px-2 text-[9px] font-extrabold text-gray-400 uppercase tracking-widest mb-2 ml-1 sidebar-header">Monitoring</p>
                <?php renderMenuItem(BASE_URL . "/$role/logbook", 'fa-clipboard-list', 'Logbook Asisten', 'logbook', $current_uri); ?>
                <?php renderMenuItem(BASE_URL . "/$role/monitorAttendance", 'fa-history', 'Rekap Presensi', 'monitorAttendance', $current_uri); ?>
            </div>
        <?php endif; ?>
    </nav>

    <div class="p-4 border-t border-gray-100 shrink-0 bg-white">
        <a href="<?= BASE_URL ?>/auth/logout" class="flex items-center justify-center w-full py-2.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-500 hover:text-white transition-all duration-200 text-sm font-bold gap-2 shadow-sm group border border-red-100 relative overflow-visible">
            <i class="fas fa-sign-out-alt group-hover:-translate-x-1 transition-transform"></i> 
            <span class="sidebar-text opacity-100 transition-all duration-200 whitespace-nowrap">Keluar</span>
            <div class="sidebar-tooltip bg-red-600">Keluar</div>
        </a>
    </div>
</aside>

<div id="mainContent" class="md:ml-64 flex flex-col min-h-screen bg-gray-50 transition-all duration-300 relative">
    
    <header class="h-16 bg-white border-b flex items-center justify-between px-6 shadow-sm md:hidden shrink-0 sticky top-0 z-30">
        <span class="font-bold text-lg text-blue-600 font-mono tracking-wider">ICLABS</span>
        
        <button id="mobileMenuBtn" class="text-gray-600 focus:outline-none p-2 rounded-lg hover:bg-gray-100 transition">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </header>

    <main class="flex-1 p-4 md:p-8 overflow-x-hidden">
