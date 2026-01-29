<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .animate-enter { animation: fadeInUp 0.5s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }

    /* --- CAROUSEL 3D STYLES --- */
    .carousel-3d-container {
        position: relative;
        width: 100%;
        height: 320px; 
        perspective: 1000px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .carousel-item-3d {
        position: absolute;
        transition: all 0.6s cubic-bezier(0.25, 0.8, 0.25, 1);
        width: 240px;
        height: 260px;
        background: white;
        border-radius: 24px;
        box-shadow: 0 15px 35px -5px rgba(0,0,0,0.1);
        border: 1px solid #f3f4f6;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 20px;
        left: 50%; 
        margin-left: -120px;
        top: 50%;
        margin-top: -130px;
    }

    .carousel-item-3d.active {
        z-index: 20;
        transform: translateX(0) scale(1);
        opacity: 1;
        border: 2px solid #6366f1;
        box-shadow: 0 20px 40px -5px rgba(99, 102, 241, 0.25);
    }

    .carousel-item-3d.next-1 { z-index: 15; transform: translateX(180px) scale(0.85); opacity: 0.6; filter: blur(0.5px); }
    .carousel-item-3d.prev-1 { z-index: 15; transform: translateX(-180px) scale(0.85); opacity: 0.6; filter: blur(0.5px); }
    .carousel-item-3d.hidden-item { z-index: 5; opacity: 0; transform: scale(0); }

    .empty-schedule-box { animation: float 3s ease-in-out infinite; }
    @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-10px); } 100% { transform: translateY(0px); } }

    /* TAB SWITCHER STYLES */
    .tab-btn { transition: all 0.3s ease; }
    .tab-btn.active { background-color: white; color: #4f46e5; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    .tab-btn.inactive { color: #6b7280; }
    .tab-btn.inactive:hover { color: #111827; }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-10">
    
    <div class="flex items-center gap-4 mb-2">
        <?php 
            $rolePath = strtolower(str_replace(' ', '', $_SESSION['role']));
            $backLink = BASE_URL . '/' . $rolePath . '/dashboard';
        ?>
        <a href="<?= $backLink ?>" class="w-10 h-10 flex items-center justify-center bg-white rounded-full shadow-sm text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition">
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
                <div class="w-40 h-40 rounded-full p-2 bg-gray-50 border border-gray-100 shadow-inner mx-auto relative">
                    <?php 
                        $photoName = $user['photo_profile'] ?? '';
                        $physicalPath = 'uploads/profile/' . $photoName;
                        if (!empty($photoName) && file_exists($physicalPath)) {
                            $avatarUrl = BASE_URL . '/uploads/profile/' . $photoName;
                        } else {
                            $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=random&bold=true";
                        }
                    ?>
                    <img src="<?= $avatarUrl ?>" alt="Profile" class="w-full h-full rounded-full object-cover shadow-lg">
                    
                    <?php if($user['role'] == 'User'): ?>
                        <div class="absolute bottom-2 right-2 w-6 h-6 rounded-full border-4 border-white 
                            <?= ($user['is_online'] > 0) ? 'bg-green-500 animate-pulse' : 'bg-gray-400' ?>" 
                            title="<?= ($user['is_online'] > 0) ? 'Online (Sedang di Lab)' : 'Offline' ?>">
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if($user['role'] == 'User'): ?>
                    <div class="mt-3 text-center">
                        <span class="px-3 py-1 <?= ($user['is_online'] > 0) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?> rounded-full text-[10px] font-bold uppercase tracking-wider border <?= ($user['is_online'] > 0) ? 'border-green-200' : 'border-gray-200' ?>">
                            <i class="fas fa-circle text-[8px] mr-1"></i> <?= ($user['is_online'] > 0) ? 'Sedang Bertugas' : 'Offline' ?>
                        </span>
                    </div>
                <?php endif; ?>
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
                    
                    <div class="flex items-center gap-3 py-4 border-b border-gray-50">
                        <div class="w-8 h-8 rounded-lg bg-green-50 text-green-500 flex items-center justify-center text-xs"><i class="fas fa-phone"></i></div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold">No. Telepon</p>
                            <p class="text-xs font-bold text-gray-700 font-mono"><?= $user['no_telp'] ?? '-' ?></p>
                        </div>
                    </div>

                    <?php if($user['role'] == 'User'): ?>
                    <div class="flex items-center gap-3 py-4 border-b border-gray-50">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-500 flex items-center justify-center text-xs"><i class="fas fa-graduation-cap"></i></div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold">Kelas</p>
                            <p class="text-xs font-bold text-gray-700 font-mono"><?= $user['kelas'] ?? '-' ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 py-4 border-b border-gray-50">
                        <div class="w-8 h-8 rounded-lg bg-orange-50 text-orange-500 flex items-center justify-center text-xs"><i class="fas fa-building"></i></div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold">Laboratorium</p>
                            <p class="text-xs font-bold text-gray-700"><?= $user['lab_name'] ?? 'Umum / Semua Lab' ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="flex items-center gap-3 py-4">
                        <div class="w-8 h-8 rounded-lg bg-gray-50 text-gray-500 flex items-center justify-center text-xs"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold">Alamat</p>
                            <p class="text-xs font-medium text-gray-600 leading-tight"><?= $user['alamat'] ?? '-' ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-auto w-full pt-6">
                <?php 
                    // [LOGIKA BARU] Cek User & Super Admin yang sudah completed
                    $isLocked = false;
                    $targetRoles = ['User', 'Super Admin'];
                    
                    if(in_array($user['role'], $targetRoles) && isset($user['is_completed']) && $user['is_completed'] == 1) {
                        $isLocked = true;
                    }
                ?>

                <?php if($isLocked): ?>
                    <button onclick="showCustomAlert('locked', 'Profil Terkunci', 'Anda sudah melengkapi profil. Data tidak dapat diubah lagi. Hubungi Administrator jika terdapat kesalahan.')" 
                            class="block w-full py-3 font-bold rounded-xl transition text-sm uppercase tracking-wide text-center shadow-none bg-gray-200 text-gray-400 cursor-not-allowed">
                        <i class="fas fa-lock mr-2"></i> Profil Terkunci
                    </button>
                <?php else: ?>
                    <a href="<?= BASE_URL . "/" . strtolower(str_replace(' ', '', $user['role'])) . "/editProfile" ?>" 
                       class="block w-full py-3 font-bold rounded-xl transition text-sm uppercase tracking-wide text-center shadow-lg bg-gray-900 hover:bg-gray-800 text-white">
                        <i class="fas fa-edit mr-2"></i> Edit Profil
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="mt-6 bg-white p-6 rounded-3xl border border-gray-200 shadow-sm relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <i class="fab fa-google text-6xl text-blue-500"></i>
                </div>
                
                <div class="flex flex-col md:flex-row justify-between items-center gap-4 relative z-10">
                    <div>
                        <h3 class="font-bold text-gray-800 flex items-center gap-2">
                            Integrasi Google Calendar
                            <!-- <i class="fab fa-google text-blue-500"></i> Integrasi Google Calendar -->
                        </h3>
                        <p class="text-xs text-gray-500 mt-1 max-w-md">
                            Hubungkan akun untuk sinkronisasi otomatis jadwal asisten, kuliah, dan piket ke kalender pribadi Anda.
                        </p>
                    </div>

                    <div class="w-full md:w-auto">
                        <?php if (!empty($is_google_connected) && $is_google_connected): ?>
                            <div class="flex items-center justify-center md:justify-start gap-3 py-2 px-5 bg-green-50 border border-green-200 rounded-xl">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center text-green-600 shadow-sm">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <span class="block text-sm font-bold text-green-700">Terhubung</span>
                                    <span class="text-[10px] text-green-600 font-medium">Sinkronisasi Aktif</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/google/connect" class="flex items-center justify-center gap-3 w-full md:w-auto py-3 px-6 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 hover:border-blue-300 hover:shadow-md transition-all group-btn">
                                <img src="https://nordichost.fi/wp-content/uploads/2025/02/HiView-Solutions-Google-Workspace-Reseller.-Super-G-Icon-min.png" class="w-5 h-5 transition-transform group-hover:scale-110">
                                <span class="text-sm font-bold text-gray-700 group-hover:text-blue-600">Hubungkan Sekarang</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-2/3 flex flex-col gap-6">
            
            <?php if($user['role'] == 'User'): ?>
                <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-8 flex-1 flex flex-col">
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
                            <span class="text-xs font-bold text-red-800 uppercase">Tidak Hadir</span>
                        </div>
                    </div>
                    <script>
                        const stats = <?= json_encode($stats) ?>;
                        const ctx = document.getElementById('userProfileChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: ['Hadir', 'Izin', 'Alpa'],
                                datasets: [{ data: [stats.hadir, stats.izin, stats.alpa], backgroundColor: ['#22c55e', '#eab308', '#ef4444'], borderWidth: 0 }]
                            },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
                        });
                    </script>
                </div>

            <?php else: ?>
                <div class="flex justify-between items-center bg-white p-4 rounded-2xl shadow-sm border border-gray-200">
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">Panel Informasi Lab</h3>
                        <p class="text-xs text-gray-500">Demografi asisten dan jadwal operasional umum.</p>
                    </div>
                    <div class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-lg text-xs font-bold">
                        Total Asisten: <?= $total_managed_users ?>
                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col min-h-[400px]">
                    <div class="flex flex-col sm:flex-row justify-between items-center p-6 border-b border-gray-100 gap-4">
                        <div class="flex items-center gap-2">
                            <i id="widgetIcon" class="fas fa-chart-pie text-indigo-500 text-xl"></i>
                            <h4 id="widgetTitle" class="text-sm font-bold text-gray-700 uppercase tracking-widest">Demografi</h4>
                        </div>
                        
                        <div class="bg-gray-100 p-1 rounded-xl flex items-center shadow-inner">
                            <button onclick="switchAdminTab('demo')" id="tab-demo" class="tab-btn active px-4 py-1.5 rounded-lg text-xs font-bold flex items-center gap-2">
                                <i class="fas fa-chart-pie"></i> Demografi
                            </button>
                            <button onclick="switchAdminTab('schedule')" id="tab-schedule" class="tab-btn inactive px-4 py-1.5 rounded-lg text-xs font-bold flex items-center gap-2">
                                <i class="fas fa-calendar-alt"></i> Jadwal Terdekat
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 relative p-6 overflow-hidden">
                        
                        <div id="view-demo" class="w-full h-full flex flex-col transition-opacity duration-300">
                            <div class="flex justify-between items-center mb-4">
                                <p class="text-xs text-gray-400">Analisis sebaran asisten berdasarkan kategori.</p>
                                <div class="flex gap-2">
                                    <div class="relative">
                                        <select id="demographicFilter" onchange="updateDemographicChart()" class="bg-gray-50 border-none text-gray-600 text-xs font-bold rounded-lg py-1.5 pl-3 pr-8 focus:ring-2 focus:ring-blue-200 cursor-pointer outline-none">
                                            <option value="gender">Gender</option>
                                            <option value="class">Kelas</option>
                                            <option value="interest">Minat</option>
                                        </select>
                                        <i class="fas fa-chevron-down absolute right-3 top-2.5 text-gray-400 text-[10px] pointer-events-none"></i>
                                    </div>
                                    <div class="flex bg-gray-50 p-1 rounded-lg">
                                        <button onclick="setChartType('bar')" class="p-1.5 rounded hover:bg-white shadow-sm transition text-gray-500 hover:text-indigo-600"><i class="fas fa-chart-bar text-xs"></i></button>
                                        <button onclick="setChartType('line')" class="p-1.5 rounded hover:bg-white shadow-sm transition text-gray-500 hover:text-indigo-600"><i class="fas fa-chart-line text-xs"></i></button>
                                        <button onclick="setChartType('pie')" class="p-1.5 rounded hover:bg-white shadow-sm transition text-gray-500 hover:text-indigo-600"><i class="fas fa-chart-pie text-xs"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-1 w-full relative min-h-[300px]">
                                <canvas id="demographicChart"></canvas>
                            </div>
                        </div>

                        <div id="view-schedule" class="w-full h-full hidden opacity-0 transition-opacity duration-300">
                            <?php if (empty($upcoming_schedules)): ?>
                                <div class="w-full h-full flex flex-col items-center justify-center empty-schedule-box text-center p-6 min-h-[300px]">
                                    <div class="w-20 h-20 bg-indigo-50 rounded-2xl shadow-sm flex items-center justify-center text-indigo-300 text-4xl mb-4 border border-indigo-100">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <h5 class="text-indigo-900 font-bold text-base">Tidak Ada Jadwal Umum</h5>
                                    <p class="text-indigo-400 text-sm mt-1">Laboratorium sedang kosong untuk kegiatan umum.</p>
                                </div>
                            <?php else: ?>
                                <div class="carousel-3d-container" id="scheduleCarousel">
                                    <?php foreach($upcoming_schedules as $idx => $sch): 
                                        $judul = $sch['nama_kegiatan'] ?? $sch['title'] ?? 'Kegiatan'; 
                                        $tgl = isset($sch['tanggal']) ? date('d M Y', strtotime($sch['tanggal'])) : ($sch['display_date'] ?? '-');
                                        $jam = isset($sch['jam_mulai']) ? substr($sch['jam_mulai'], 0, 5) : ($sch['start_time'] ?? '00:00');
                                        $lok = $sch['lokasi'] ?? $sch['location'] ?? 'Lab Terpadu';
                                    ?>
                                    <div class="carousel-item-3d <?= $idx == 0 ? 'active' : ($idx == 1 ? 'next-1' : ($idx == 2 ? 'prev-1' : 'hidden-item')) ?>" data-index="<?= $idx ?>">
                                        <div class="flex justify-between items-start w-full">
                                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-indigo-100 text-indigo-600 shadow-sm">
                                                <i class="fas fa-users text-lg"></i>
                                            </div>
                                            <span class="px-3 py-1 bg-gray-50 border border-gray-100 rounded-lg text-[10px] font-bold uppercase text-gray-500">UMUM</span>
                                        </div>
                                        <div class="w-full mt-4">
                                            <h4 class="font-bold text-gray-800 text-lg leading-snug line-clamp-2 mb-2" title="<?= $judul ?>">
                                                <?= $judul ?>
                                            </h4>
                                            <p class="text-xs text-indigo-500 font-bold flex items-center gap-2 bg-indigo-50 px-2 py-1.5 rounded-lg w-max">
                                                <i class="far fa-calendar"></i> <?= $tgl ?>
                                            </p>
                                        </div>
                                        <div class="w-full pt-4 border-t border-dashed border-gray-200 flex justify-between items-center mt-auto">
                                            <div class="text-sm font-mono font-bold text-gray-700 bg-gray-50 px-2 py-1 rounded border border-gray-100">
                                                <?= $jam ?>
                                            </div>
                                            <div class="flex items-center gap-1 text-[10px] text-gray-400 font-medium">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span class="truncate max-w-[80px]"><?= $lok ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-center mt-4 text-xs text-gray-400 italic">
                                    Geser otomatis setiap 3 detik
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col flex-1 overflow-hidden min-h-[400px]">
                    <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                        <h4 class="text-sm font-bold text-gray-700 uppercase tracking-widest">
                            <i class="fas fa-trophy text-yellow-500 mr-2"></i>Peringkat Asisten
                        </h4>
                        <div class="relative">
                            <select id="rankingFilter" onchange="renderRanking()" class="pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-100 shadow-sm cursor-pointer appearance-none">
                                <option value="online">🟢 Sedang Online</option>
                                <option value="rajin">🔥 Paling Rajin (Hadir)</option>
                                <option value="jarang">😴 Jarang Hadir</option>
                                <option value="cepat">⚡ Datang Tercepat</option>
                                <option value="terlambat">🐌 Datang Terlambat</option>
                                <option value="sering_izin">🤒 Sering Izin</option>
                                <option value="logbook_lengkap">📝 Logbook Terlengkap</option>
                                <option value="logbook_singkat">📉 Logbook Singkat</option>
                                <option value="sibuk">📅 Paling Sibuk (Jadwal)</option>
                                <option value="santai">🏖️ Paling Santai</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-3 top-2.5 text-gray-400 text-xs pointer-events-none"></i>
                        </div>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-0 custom-scrollbar" id="rankingList"></div>
                </div>

                <script>
                    // === A. SWITCHER LOGIC ===
                    function switchAdminTab(tab) {
                        const btnDemo = document.getElementById('tab-demo');
                        const btnSch = document.getElementById('tab-schedule');
                        const viewDemo = document.getElementById('view-demo');
                        const viewSch = document.getElementById('view-schedule');
                        const title = document.getElementById('widgetTitle');
                        const icon = document.getElementById('widgetIcon');

                        if(tab === 'demo') {
                            btnDemo.classList.add('active'); btnDemo.classList.remove('inactive');
                            btnSch.classList.add('inactive'); btnSch.classList.remove('active');
                            viewSch.classList.add('opacity-0');
                            setTimeout(() => { viewSch.classList.add('hidden'); viewDemo.classList.remove('hidden'); setTimeout(() => viewDemo.classList.remove('opacity-0'), 50); }, 300);
                            title.innerText = 'Demografi'; icon.className = 'fas fa-chart-pie text-indigo-500 text-xl';
                            setTimeout(() => { if(demoChart) demoChart.resize(); }, 350);
                        } else {
                            btnSch.classList.add('active'); btnSch.classList.remove('inactive');
                            btnDemo.classList.add('inactive'); btnDemo.classList.remove('active');
                            viewDemo.classList.add('opacity-0');
                            setTimeout(() => { viewDemo.classList.add('hidden'); viewSch.classList.remove('hidden'); setTimeout(() => viewSch.classList.remove('opacity-0'), 50); }, 300);
                            title.innerText = 'Jadwal Terdekat'; icon.className = 'fas fa-calendar-alt text-indigo-500 text-xl';
                        }
                    }

                    // === B. DEMOGRAPHIC CHART ===
                    const rawDemographics = <?= json_encode($demographics ?? []) ?>;
                    let demoChart = null; let demoType = 'bar';

                    function updateDemographicChart() {
                        const ctx = document.getElementById('demographicChart').getContext('2d');
                        const key = document.getElementById('demographicFilter').value;
                        const dataGroup = rawDemographics[key] || [];
                        const colName = (key == 'gender' ? 'jenis_kelamin' : (key == 'class' ? 'kelas' : 'peminatan'));
                        const labels = dataGroup.map(item => item[colName] || 'Tidak Diketahui');
                        const counts = dataGroup.map(item => item.count);

                        if(demoChart) demoChart.destroy();
                        const colors = ['#6366f1', '#ec4899', '#10b981', '#f59e0b', '#8b5cf6', '#06b6d4'];

                        demoChart = new Chart(ctx, {
                            type: demoType,
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Jumlah Asisten', data: counts,
                                    backgroundColor: (demoType == 'line') ? 'rgba(99, 102, 241, 0.2)' : colors,
                                    borderColor: (demoType == 'pie') ? '#fff' : '#4f46e5',
                                    borderWidth: 2, fill: (demoType == 'line'), tension: 0.4, borderRadius: 6
                                }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                scales: (demoType == 'pie') ? {} : { y: { beginAtZero: true, ticks: { precision: 0 } } },
                                plugins: { legend: { display: (demoType == 'pie'), position: 'right' } }
                            }
                        });
                    }
                    function setChartType(t) { demoType = t; updateDemographicChart(); }
                    if(Object.keys(rawDemographics).length > 0) updateDemographicChart();

                    // === C. SCHEDULE CAROUSEL ===
                    const items = document.querySelectorAll('.carousel-item-3d');
                    if(items.length > 0) {
                        let currentIndex = 0; const totalItems = items.length;
                        function rotateCarousel() { currentIndex = (currentIndex + 1) % totalItems; updateClasses(); }
                        function updateClasses() {
                            items.forEach((item, i) => {
                                let pos = (i - currentIndex); if (pos < 0) pos += totalItems;
                                item.className = 'carousel-item-3d'; 
                                if (pos === 0) item.classList.add('active'); 
                                else if (pos === 1) item.classList.add('next-1');
                                else if (pos === totalItems - 1) item.classList.add('prev-1');
                                else item.classList.add('hidden-item');
                            });
                        }
                        setInterval(rotateCarousel, 3000);
                    }

                    // === D. RANKING ===
                    const rankings = <?= json_encode($rankings ?? []) ?>;
                    function renderRanking() {
                        const filter = document.getElementById('rankingFilter').value;
                        const container = document.getElementById('rankingList');
                        const data = rankings[filter];
                        container.innerHTML = '';
                        if (!data || data.length === 0) { 
                            container.innerHTML = `<div class="flex flex-col items-center justify-center py-12 text-gray-400"><i class="fas fa-user-slash mb-2"></i><p class="text-xs italic">Tidak ada data untuk kategori ini.</p></div>`; 
                            return; 
                        }
                        data.forEach((item, index) => {
                            let badgeColor = 'bg-gray-100 text-gray-600'; 
                            if (index === 0) badgeColor = 'bg-yellow-100 text-yellow-700 border-yellow-200'; 
                            if (index === 1) badgeColor = 'bg-gray-200 text-gray-700 border-gray-300'; 
                            if (index === 2) badgeColor = 'bg-orange-100 text-orange-700 border-orange-200';
                            
                            let scoreLabel = 'Skor'; 
                            if(filter.includes('rajin') || filter.includes('jarang') || filter.includes('sibuk')) scoreLabel = 'Total'; 
                            if(filter.includes('cepat') || filter.includes('terlambat')) scoreLabel = 'Rata-rata'; 
                            if(filter.includes('logbook_lengkap')) scoreLabel = 'Karakter';
                            if(filter.includes('logbook_singkat')) scoreLabel = 'Karakter';
                            if(filter === 'online') scoreLabel = 'Status';

                            let photoUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(item.nama)}&background=random&size=100`; 
                            if(item.photo_profile) photoUrl = `<?= BASE_URL ?>/uploads/profile/${item.photo_profile}`;
                            
                            const html = `
                                <div class="flex items-center justify-between p-4 border-b border-gray-50 hover:bg-indigo-50/30 transition">
                                    <div class="flex items-center gap-4">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold border ${badgeColor}">#${index + 1}</div>
                                        <img src="${photoUrl}" class="w-10 h-10 rounded-full object-cover border border-gray-200 shadow-sm">
                                        <div>
                                            <h5 class="font-bold text-gray-800 text-sm leading-tight">${item.nama}</h5>
                                            <p class="text-[10px] text-gray-500 font-bold uppercase mt-0.5">${item.jabatan ?? 'Anggota'}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="block font-extrabold text-indigo-600 text-sm">${item.score}</span>
                                        <span class="text-[9px] text-gray-400 uppercase font-bold tracking-wider">${scoreLabel}</span>
                                    </div>
                                </div>
                            `;
                            container.innerHTML += html;
                        });
                    }
                    if(Object.keys(rankings).length > 0) renderRanking();
                </script>
            <?php endif; ?>

        </div>
    </div>
</div>

<div id="customAlertModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="alertBackdrop"></div>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative z-10 overflow-hidden transform scale-90 opacity-0 transition-all duration-300 flex flex-col items-center p-6 text-center" id="alertContent">
        <div id="alertIconBg" class="w-20 h-20 rounded-full flex items-center justify-center mb-4 transition-colors">
            <i id="alertIcon" class="fas text-4xl"></i>
        </div>
        <h3 id="alertTitle" class="text-2xl font-extrabold text-gray-800 mb-2"></h3>
        <p id="alertMessage" class="text-sm text-gray-500 mb-6 px-2 leading-relaxed"></p>
        <button onclick="closeCustomAlert()" class="w-full py-3.5 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02] active:scale-95" id="alertBtn">OK</button>
    </div>
</div>

<script>
    function showCustomAlert(type, title, message) {
        const modal = document.getElementById('customAlertModal');
        const content = document.getElementById('alertContent');
        const backdrop = document.getElementById('alertBackdrop');
        const iconBg = document.getElementById('alertIconBg');
        const icon = document.getElementById('alertIcon');
        const btn = document.getElementById('alertBtn');

        document.getElementById('alertTitle').innerText = title;
        document.getElementById('alertMessage').innerText = message;

        if (type === 'success') {
            iconBg.className = 'w-20 h-20 rounded-full flex items-center justify-center mb-4 bg-green-100 text-green-500';
            icon.className = 'fas fa-check';
            btn.className = 'w-full py-3.5 rounded-xl font-bold text-white shadow-lg bg-green-600 hover:bg-green-700 shadow-green-500/30 transition';
        } else if (type === 'locked') {
            iconBg.className = 'w-20 h-20 rounded-full flex items-center justify-center mb-4 bg-gray-100 text-gray-500';
            icon.className = 'fas fa-lock';
            btn.className = 'w-full py-3.5 rounded-xl font-bold text-white shadow-lg bg-gray-600 hover:bg-gray-700 shadow-gray-500/30 transition';
        } else {
            iconBg.className = 'w-20 h-20 rounded-full flex items-center justify-center mb-4 bg-red-100 text-red-500';
            icon.className = 'fas fa-times';
            btn.className = 'w-full py-3.5 rounded-xl font-bold text-white shadow-lg bg-red-600 hover:bg-red-700 shadow-red-500/30 transition';
        }

        modal.classList.remove('hidden');
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            content.classList.remove('scale-90', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeCustomAlert() {
        const modal = document.getElementById('customAlertModal');
        const content = document.getElementById('alertContent');
        const backdrop = document.getElementById('alertBackdrop');

        backdrop.classList.add('opacity-0');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-90', 'opacity-0');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }
</script>