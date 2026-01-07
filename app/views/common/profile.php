<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .animate-enter { animation: fadeInUp 0.5s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-10">
    
    <div class="flex items-center gap-4 mb-2">
        <a href="javascript:history.back()" class="w-10 h-10 flex items-center justify-center bg-white rounded-full shadow-sm text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-extrabold text-gray-800">Profil Saya</h1>
            <p class="text-gray-500 text-sm">Kelola informasi akun pribadi.</p>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 h-full min-h-[600px]">
        
        <div class="w-full lg:w-1/3 bg-white rounded-3xl shadow-sm border border-gray-200 p-8 flex flex-col items-center relative overflow-hidden">
            
            <div class="mt-2 mb-6 relative">
                <div class="w-40 h-40 rounded-full p-2 bg-gray-50 border border-gray-100 shadow-inner mx-auto">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&background=random&size=500" 
                         class="w-full h-full rounded-full object-cover shadow-lg">
                </div>
                <div class="absolute bottom-2 right-2 bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center border-2 border-white shadow-sm" title="Role Anda">
                    <i class="fas <?= $user['role'] == 'User' ? 'fa-user' : ($user['role'] == 'Admin' ? 'fa-user-cog' : 'fa-user-shield') ?> text-xs"></i>
                </div>
            </div>

            <div class="text-center mb-6">
                <h2 class="text-xl font-extrabold text-gray-800 leading-tight"><?= $user['name'] ?></h2>
                <div class="flex items-center justify-center gap-2 mt-2">
                    <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[10px] font-bold uppercase tracking-wider border border-blue-100">
                        <?= $user['position'] ?? $user['role'] ?>
                    </span>
                </div>
            </div>

            <div class="w-full space-y-3 text-left">
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 space-y-3">
                    <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                        <span class="text-xs text-gray-400 font-bold uppercase">ID / NIM</span>
                        <span class="font-mono text-sm font-bold text-gray-700"><?= $user['nim'] ?? '-' ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-400 font-bold uppercase">Email</span>
                        <span class="text-sm font-medium text-gray-700 truncate max-w-[150px]" title="<?= $user['email'] ?>"><?= $user['email'] ?></span>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-gray-200 space-y-3 shadow-sm">
                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Detail Informasi</h4>
                    
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-orange-50 text-orange-500 flex items-center justify-center text-xs"><i class="fas fa-building"></i></div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold">Laboratorium</p>
                            <p class="text-xs font-bold text-gray-700"><?= $user['lab_name'] ?? 'Umum / Semua Lab' ?></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-green-50 text-green-500 flex items-center justify-center text-xs"><i class="fas fa-phone"></i></div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold">No. Telepon</p>
                            <p class="text-xs font-bold text-gray-700 font-mono"><?= $user['no_telp'] ?? '-' ?></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-50 text-gray-500 flex items-center justify-center text-xs"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold">Alamat</p>
                            <p class="text-xs font-medium text-gray-600 leading-tight"><?= $user['alamat'] ?? '-' ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-auto w-full pt-6">
                <button onclick="alert('Fitur Edit Profil belum tersedia dalam demo ini.')" class="block w-full py-3 bg-gray-900 text-white font-bold rounded-xl hover:bg-gray-800 transition text-sm uppercase tracking-wide text-center shadow-lg shadow-gray-900/20">
                    <i class="fas fa-edit mr-2"></i> Edit Profil
                </button>
            </div>
        </div>

        <div class="w-full lg:w-2/3 bg-white rounded-3xl shadow-sm border border-gray-200 p-8 flex flex-col">
            
            <?php if($user['role'] == 'User'): ?>
                <div class="flex justify-between items-start mb-8">
                    <h3 class="text-xl font-bold text-gray-700 uppercase tracking-wide">Statistik Kehadiran Anda</h3>
                </div>

                <div class="flex-1 relative w-full h-full min-h-[300px] flex items-center justify-center bg-gray-50/50 rounded-2xl border border-gray-100 border-dashed p-4">
                    <canvas id="userProfileChart"></canvas>
                </div>

                <div class="grid grid-cols-3 gap-4 mt-8">
                    <div class="text-center p-4 bg-green-50 rounded-2xl border border-green-100">
                        <span class="block text-3xl font-extrabold text-green-600"><?= $stats['hadir'] ?></span>
                        <span class="text-xs font-bold text-green-800 uppercase">Hadir</span>
                    </div>
                    <div class="text-center p-4 bg-yellow-50 rounded-2xl border border-yellow-100">
                        <span class="block text-3xl font-extrabold text-yellow-600"><?= $stats['izin'] ?></span>
                        <span class="text-xs font-bold text-yellow-800 uppercase">Izin</span>
                    </div>
                    <div class="text-center p-4 bg-red-50 rounded-2xl border border-red-100">
                        <span class="block text-3xl font-extrabold text-red-600"><?= $stats['alpa'] ?></span>
                        <span class="text-xs font-bold text-red-800 uppercase">Absen</span>
                    </div>
                </div>

                <script>
                    const stats = <?= json_encode($stats) ?>;
                    const ctx = document.getElementById('userProfileChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Hadir', 'Izin', 'Absen'],
                            datasets: [{
                                data: [stats.hadir, stats.izin, stats.alpa],
                                backgroundColor: ['#22c55e', '#eab308', '#ef4444'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'right' } }
                        }
                    });
                </script>

            <?php else: ?>
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-700 uppercase tracking-wide">Panel Kinerja</h3>
                        <p class="text-sm text-gray-500">Overview sistem dan akses cepat.</p>
                    </div>
                    <div class="bg-green-100 text-green-700 px-3 py-1 rounded-lg text-xs font-bold flex items-center gap-2">
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span> System Online
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-gradient-to-br from-indigo-500 to-blue-600 rounded-2xl p-6 text-white shadow-lg shadow-blue-500/30 relative overflow-hidden group">
                        <div class="absolute right-0 top-0 w-24 h-24 bg-white opacity-10 rounded-full -mr-8 -mt-8 transition group-hover:scale-150"></div>
                        <h4 class="text-blue-100 text-xs font-bold uppercase tracking-widest mb-1">Total Users Managed</h4>
                        <span class="text-4xl font-extrabold"><?= $total_managed_users ?? $total_system_users ?? 0 ?></span>
                        <p class="text-xs text-blue-200 mt-2">Pengguna aktif dalam sistem</p>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm flex flex-col justify-center relative overflow-hidden group hover:border-blue-300 transition">
                        <div class="absolute right-4 top-4 text-gray-100 text-6xl group-hover:text-blue-50 transition"><i class="fas fa-database"></i></div>
                        <h4 class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1">Database Status</h4>
                        <span class="text-2xl font-bold text-gray-800 text-green-600">Connected <i class="fas fa-check-circle text-sm ml-1"></i></span>
                        <p class="text-xs text-gray-400 mt-1">Latency: 24ms</p>
                    </div>
                </div>

                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Akses Cepat Admin</h4>
                <div class="grid grid-cols-2 gap-4 flex-1">
                    <a href="<?= BASE_URL ?>/admin/manageUsers" class="flex items-center p-4 bg-gray-50 border border-gray-200 rounded-xl hover:bg-white hover:shadow-md hover:border-blue-200 transition group cursor-pointer">
                        <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center mr-4 group-hover:bg-purple-600 group-hover:text-white transition">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <div>
                            <h5 class="font-bold text-gray-700 text-sm">Kelola User</h5>
                            <p class="text-[10px] text-gray-400">Tambah/Edit akun</p>
                        </div>
                    </a>

                    <a href="<?= BASE_URL ?>/admin/manageSchedule" class="flex items-center p-4 bg-gray-50 border border-gray-200 rounded-xl hover:bg-white hover:shadow-md hover:border-blue-200 transition group cursor-pointer">
                        <div class="w-10 h-10 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center mr-4 group-hover:bg-orange-600 group-hover:text-white transition">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div>
                            <h5 class="font-bold text-gray-700 text-sm">Atur Jadwal</h5>
                            <p class="text-[10px] text-gray-400">Plotting shift</p>
                        </div>
                    </a>

                    <a href="<?= BASE_URL ?>/admin/monitorAttendance" class="flex items-center p-4 bg-gray-50 border border-gray-200 rounded-xl hover:bg-white hover:shadow-md hover:border-blue-200 transition group cursor-pointer">
                        <div class="w-10 h-10 rounded-lg bg-green-100 text-green-600 flex items-center justify-center mr-4 group-hover:bg-green-600 group-hover:text-white transition">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div>
                            <h5 class="font-bold text-gray-700 text-sm">Cek Presensi</h5>
                            <p class="text-[10px] text-gray-400">Laporan harian</p>
                        </div>
                    </a>

                    <div class="flex items-center p-4 bg-gray-50 border border-gray-200 rounded-xl opacity-50 cursor-not-allowed" title="Fitur Segera Hadir">
                        <div class="w-10 h-10 rounded-lg bg-gray-200 text-gray-500 flex items-center justify-center mr-4">
                            <i class="fas fa-print"></i>
                        </div>
                        <div>
                            <h5 class="font-bold text-gray-500 text-sm">Cetak Laporan</h5>
                            <p class="text-[10px] text-gray-400">Export PDF/Excel</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>