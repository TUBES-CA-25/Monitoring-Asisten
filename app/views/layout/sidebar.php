<aside class="fixed top-0 left-0 z-50 w-64 h-screen transition-transform -translate-x-full bg-white border-r border-gray-200 md:translate-x-0 shadow-2xl flex flex-col font-sans">
    
    <div class="h-20 flex items-center justify-center border-b border-gray-100 bg-gradient-to-r from-blue-700 to-cyan-600 shrink-0">
        <div class="flex items-center gap-3 text-white">
            <img src="<?= BASE_URL ?>/assets/img/Logo_ICLABS_White.webp" alt="ICLABS Logo" 
            class="w-10 h-10 object-contain animate-pulse filter drop-shadow-lg shadow-white-400">
            <span class="text-xl font-extrabold tracking-wider font-mono">ICLABS</span>
        </div>
    </div>
    
    <div class="p-6 border-b border-gray-100 bg-blue-50/30 flex items-center justify-between gap-3 shrink-0 group relative overflow-hidden">
        <div class="absolute top-0 right-0 w-20 h-20 bg-blue-100 rounded-full blur-2xl -mr-10 -mt-10 opacity-50"></div>

        <div class="flex items-center gap-3 overflow-hidden relative z-10">
            <div class="relative">
                <?php 
                    $photoName = $user['photo_profile'] ?? '';
                    $physicalPath = 'uploads/profile/' . $photoName;
                    if (!empty($photoName) && file_exists($physicalPath)) {
                        $avatarUrl = BASE_URL . '/uploads/profile/' . $photoName;
                    } else {
                        $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=random&bold=true";
                    }
                ?>

                <img src="<?= $avatarUrl ?>" alt="Profile"
                     class="w-11 h-11 rounded-full border-2 border-white shadow-md object-cover">
                <div class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 border-2 border-white rounded-full"></div>
            </div>
            <div class="overflow-hidden">
                <h4 class="font-bold text-gray-800 text-sm truncate leading-tight w-24" title="<?= $_SESSION['name'] ?>">
                    <?= $_SESSION['name'] ?>
                </h4>
                <span class="inline-block px-2 py-0.5 mt-1 text-[10px] font-bold text-blue-600 bg-white border border-blue-100 rounded-full uppercase tracking-wide truncate max-w-full shadow-sm">
                    <?= $_SESSION['jabatan'] ?? $_SESSION['role'] ?>
                </span>
            </div>
        </div>

        <?php $roleLink = strtolower(str_replace(' ', '', $_SESSION['role'])); ?>
        <a href="<?= BASE_URL ?>/<?= $roleLink ?>/profile" class="relative z-10 w-8 h-8 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-400 hover:text-blue-600 hover:border-blue-300 hover:shadow-md transition-all duration-200 flex-shrink-0" title="Edit Profil">
            <i class="fas fa-pen text-[10px]"></i>
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2 custom-scrollbar">
        <?php 
            $role = strtolower(str_replace(' ', '', $_SESSION['role'])); 
            $current_uri = $_SERVER['REQUEST_URI'];
            
            function isActive($uri, $keyword) {
                return strpos($uri, $keyword) !== false 
                    ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30 border-transparent' 
                    : 'text-gray-600 hover:bg-gray-50 hover:text-blue-600 border-transparent hover:shadow-sm';
            }
            // Helper icon color (putih jika aktif, abu jika tidak)
            function iconColor($uri, $keyword) {
                return strpos($uri, $keyword) !== false ? 'text-white' : 'text-gray-400 group-hover:text-blue-600';
            }
        ?>
        
        <div>
            <p class="px-2 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3 ml-1">Utama</p>
            <a href="<?= BASE_URL ?>/<?= $role ?>/dashboard" 
               class="flex items-center px-3 py-3 rounded-xl border transition-all duration-200 group <?= isActive($current_uri, 'dashboard') ?>">
                <i class="fas fa-home w-6 text-center text-sm transition-colors duration-200 <?= iconColor($current_uri, 'dashboard') ?>"></i>
                <span class="font-semibold text-sm ml-2">Dashboard</span>
            </a>
        </div>

        <?php if($_SESSION['role'] == 'User'): ?>
            <div class="mt-8"> <p class="px-2 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3 ml-1">Aktivitas</p>
                
                <a href="<?= BASE_URL ?>/user/scan" class="flex items-center px-3 py-3 rounded-xl border transition-all duration-200 group mb-2 <?= isActive($current_uri, 'scan') ?>">
                    <i class="fas fa-qrcode w-6 text-center text-sm transition-colors duration-200 <?= iconColor($current_uri, 'scan') ?>"></i>
                    <span class="font-semibold text-sm ml-2">Presensi QR</span>
                </a>
                
                <a href="<?= BASE_URL ?>/user/logbook" class="flex items-center px-3 py-3 rounded-xl border transition-all duration-200 group mb-2 <?= isActive($current_uri, 'logbook') ?>">
                    <i class="fas fa-book-open w-6 text-center text-sm transition-colors duration-200 <?= iconColor($current_uri, 'logbook') ?>"></i>
                    <span class="font-semibold text-sm ml-2">Logbook</span>
                </a>

                <a href="<?= BASE_URL ?>/user/schedule" class="flex items-center px-3 py-3 rounded-xl border transition-all duration-200 group <?= isActive($current_uri, 'schedule') ?>">
                    <i class="fas fa-calendar-alt w-6 text-center text-sm transition-colors duration-200 <?= iconColor($current_uri, 'schedule') ?>"></i>
                    <span class="font-semibold text-sm ml-2">Jadwal Saya</span>
                </a>
            </div>
        <?php endif; ?>

        <?php if($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'Super Admin'): ?>
            
            <div class="mt-8">
                <p class="px-2 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3 ml-1">Manajemen</p>

                <a href="<?= BASE_URL ?>/<?= $role ?>/manageUsers" class="flex items-center px-3 py-3 rounded-xl border transition-all duration-200 group mb-2 <?= isActive($current_uri, 'manageUsers') ?>">
                    <i class="fas fa-users-cog w-6 text-center text-sm transition-colors duration-200 <?= iconColor($current_uri, 'manageUsers') ?>"></i>
                    <span class="font-semibold text-sm ml-2">Kelola User</span>
                </a>

                <a href="<?= BASE_URL ?>/<?= $role ?>/schedule" class="flex items-center px-3 py-3 rounded-xl border transition-all duration-200 group <?= isActive($current_uri, 'schedule') ?>">
                    <i class="fas fa-calendar-check w-6 text-center text-sm transition-colors duration-200 <?= iconColor($current_uri, 'schedule') ?>"></i>
                    <span class="font-semibold text-sm ml-2">Jadwal Lab</span>
                </a>
            </div>

            <div class="mt-8">
                <p class="px-2 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3 ml-1">Monitoring</p>

                <a href="<?= BASE_URL ?>/<?= $role ?>/logbook" class="flex items-center px-3 py-3 rounded-xl border transition-all duration-200 group mb-2 <?= isActive($current_uri, 'logbook') ?>">
                    <i class="fas fa-clipboard-list w-6 text-center text-sm transition-colors duration-200 <?= iconColor($current_uri, 'logbook') ?>"></i>
                    <span class="font-semibold text-sm ml-2">Logbook Asisten</span>
                </a>
                
                <a href="<?= BASE_URL ?>/<?= $role ?>/monitorAttendance" class="flex items-center px-3 py-3 rounded-xl border transition-all duration-200 group <?= isActive($current_uri, 'monitorAttendance') ?>">
                    <i class="fas fa-history w-6 text-center text-sm transition-colors duration-200 <?= iconColor($current_uri, 'monitorAttendance') ?>"></i>
                    <span class="font-semibold text-sm ml-2">Rekap Presensi</span>
                </a>
            </div>
        <?php endif; ?>
    </nav>

    <div class="p-5 border-t border-gray-100 shrink-0 bg-white">
        <a href="<?= BASE_URL ?>/auth/logout" class="flex items-center justify-center w-full py-3 bg-red-50 text-red-600 rounded-xl hover:bg-red-500 hover:text-white transition-all duration-200 text-sm font-bold gap-2 shadow-sm group border border-red-100">
            <i class="fas fa-sign-out-alt group-hover:-translate-x-1 transition-transform"></i> 
            <span>Keluar</span>
        </a>
    </div>
</aside>

<div class="md:ml-64 flex flex-col min-h-screen bg-gray-50 transition-all duration-300 relative">
    
    <header class="h-16 bg-white border-b flex items-center justify-between px-6 shadow-sm md:hidden shrink-0 sticky top-0 z-30">
        <span class="font-bold text-lg text-blue-600">ICLABS</span>
        <button class="text-gray-600 focus:outline-none"><i class="fas fa-bars text-xl"></i></button>
    </header>

    <main class="flex-1 p-8 overflow-x-hidden">