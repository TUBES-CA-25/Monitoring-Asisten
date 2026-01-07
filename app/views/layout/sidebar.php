<aside class="fixed top-0 left-0 z-50 w-64 h-screen transition-transform -translate-x-full bg-white border-r border-gray-200 md:translate-x-0 shadow-2xl flex flex-col font-sans">
    
    <div class="h-20 flex items-center justify-center border-b border-gray-100 bg-gradient-to-r from-blue-600 to-indigo-600 shrink-0">
        <div class="flex items-center gap-3 text-white">
            <img src="<?= BASE_URL ?>/assets/img/Logo_ICLABS_White.webp" alt="ICLABS Logo" 
            class="w-12 h-12 object-contain animate-pulse filter drop-shadow-lg shadow-white-400">
            <!-- <i class="fas fa-microchip text-2xl animate-pulse"></i> -->
            <span class="text-xl font-extrabold tracking-wider">ICLABS</span>
        </div>
    </div>
    
    <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex items-center gap-3 shrink-0">
        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['name']) ?>&background=random" class="w-10 h-10 rounded-full border-2 border-white shadow-md">
        <div class="overflow-hidden min-w-0">
            <h4 class="font-bold text-gray-800 text-sm truncate"><?= $_SESSION['name'] ?></h4>
            <span class="inline-block px-2 py-0.5 text-[10px] font-bold text-blue-600 bg-blue-100 rounded-full uppercase tracking-wide"><?= $_SESSION['role'] ?></span>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-1 custom-scrollbar">
        <?php $role = strtolower(str_replace(' ', '', $_SESSION['role'])); ?>
        
        <p class="px-3 text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Menu Utama</p>

        <a href="<?= BASE_URL ?>/<?= $role ?>/dashboard" class="flex items-center px-3 py-2.5 text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition-all duration-200 group">
            <div class="w-8 h-8 flex items-center justify-center bg-gray-100 rounded-lg text-gray-500 group-hover:bg-blue-600 group-hover:text-white transition mr-3 shadow-sm">
                <i class="fas fa-home"></i>
            </div>
            <span class="font-medium text-sm">Dashboard</span>
        </a>

        <?php if($_SESSION['role'] == 'User'): ?>
        <div class="mt-4 mb-2 px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Monitoring</div>

        <a href="<?= BASE_URL ?>/user/scan" class="flex items-center px-3 py-2.5 text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition-all duration-200 group">
            <div class="w-8 h-8 flex items-center justify-center bg-gray-100 rounded-lg text-gray-500 group-hover:bg-blue-600 group-hover:text-white transition mr-3 shadow-sm">
                <i class="fas fa-qrcode"></i>
            </div>
            <span class="font-medium text-sm">Presensi</span>
        </a>
        <a href="<?= BASE_URL ?>/user/logbook" class="flex items-center px-3 py-2.5 text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition-all duration-200 group">
            <div class="w-8 h-8 flex items-center justify-center bg-gray-100 rounded-lg text-gray-500 group-hover:bg-blue-600 group-hover:text-white transition mr-3 shadow-sm">
                <i class="fas fa-book"></i>
            </div>
            <span class="font-medium text-sm">Logbook</span>
        </a>
        <a href="<?= BASE_URL ?>/user/schedule" class="flex items-center px-3 py-2.5 text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition-all duration-200 group">
            <div class="w-8 h-8 flex items-center justify-center bg-gray-100 rounded-lg text-gray-500 group-hover:bg-blue-600 group-hover:text-white transition mr-3 shadow-sm">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <span class="font-medium text-sm">Jadwal</span>
        </a>
        <?php endif; ?>

        <?php if($_SESSION['role'] == 'Super Admin'): ?>
        <div class="mt-4 mb-2 px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Monitoring</div>

        <a href="<?= BASE_URL ?>/superadmin/logbook" class="flex items-center px-3 py-2.5 text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition-all duration-200 group">
            <div class="w-8 h-8 flex items-center justify-center bg-gray-100 rounded-lg text-gray-500 group-hover:bg-blue-600 group-hover:text-white transition mr-3 shadow-sm">
                <i class="fas fa-book-reader"></i>
            </div>
            <span class="font-medium text-sm">Logbook Asisten</span>
        </a>
        <a href="<?= BASE_URL ?>/superadmin/schedule" class="flex items-center px-3 py-2.5 text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition-all duration-200 group">
            <div class="w-8 h-8 flex items-center justify-center bg-gray-100 rounded-lg text-gray-500 group-hover:bg-blue-600 group-hover:text-white transition mr-3 shadow-sm">
                <i class="fas fa-calendar-check"></i>
            </div>
            <span class="font-medium text-sm">Jadwal Asisten</span>
        </a>
        <?php endif; ?>

        <?php if($_SESSION['role'] == 'Admin'): ?>
            <div class="mt-4 mb-2 px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Manajemen</div>

            <a href="<?= BASE_URL ?>/admin/manageUsers" class="flex items-center px-3 py-2.5 text-gray-600 rounded-xl hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-200 group">
                <div class="w-8 h-8 flex items-center justify-center bg-gray-100 rounded-lg text-gray-500 group-hover:bg-indigo-600 group-hover:text-white transition mr-3 shadow-sm">
                    <i class="fas fa-users-cog"></i>
                </div>
                <span class="font-medium text-sm">Kelola User</span>
            </a>

            <a href="<?= BASE_URL ?>/admin/manageSchedule" class="flex items-center px-3 py-2.5 text-gray-600 rounded-xl hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-200 group">
                <div class="w-8 h-8 flex items-center justify-center bg-gray-100 rounded-lg text-gray-500 group-hover:bg-indigo-600 group-hover:text-white transition mr-3 shadow-sm">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <span class="font-medium text-sm">Kelola Jadwal</span>
            </a>

            <div class="mt-4 mb-2 px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Monitoring</div>

            <a href="<?= BASE_URL ?>/admin/monitorAttendance" class="flex items-center px-3 py-2.5 text-gray-600 rounded-xl hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-200 group">
                <div class="w-8 h-8 flex items-center justify-center bg-gray-100 rounded-lg text-gray-500 group-hover:bg-indigo-600 group-hover:text-white transition mr-3 shadow-sm">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <span class="font-medium text-sm">Presensi</span>
            </a>

            <a href="<?= BASE_URL ?>/admin/monitorLogbook" class="flex items-center px-3 py-2.5 text-gray-600 rounded-xl hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-200 group">
                <div class="w-8 h-8 flex items-center justify-center bg-gray-100 rounded-lg text-gray-500 group-hover:bg-indigo-600 group-hover:text-white transition mr-3 shadow-sm">
                    <i class="fas fa-book-reader"></i>
                </div>
                <span class="font-medium text-sm">Logbook</span>
            </a>
        <?php endif; ?>
    </nav>

    <div class="p-4 border-t border-gray-100 shrink-0 bg-white">
        <a href="<?= BASE_URL ?>/auth/logout" class="flex items-center justify-center w-full py-2.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-500 hover:text-white transition text-sm font-bold gap-2 shadow-sm">
            <i class="fas fa-sign-out-alt"></i> Keluar
        </a>
    </div>
</aside>

<div class="md:ml-64 flex flex-col min-h-screen bg-gray-50 transition-all duration-300 relative">
    
    <header class="h-16 bg-white border-b flex items-center justify-between px-6 shadow-sm md:hidden shrink-0 sticky top-0 z-30">
        <span class="font-bold text-lg text-blue-600">ICLABS</span>
        <button class="text-gray-600"><i class="fas fa-bars"></i></button>
    </header>

    <main class="flex-1 p-8 overflow-x-hidden">