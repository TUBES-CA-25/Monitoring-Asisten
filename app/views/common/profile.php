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
        height: 100%;
        min-height: 280px;
        perspective: 1000px;
        overflow: hidden;
        display: flex;
        align-items: center;
        /* Padding kiri agar kartu utama tidak terpotong */
        padding-left: 10px; 
    }
    
    .carousel-item-3d {
        position: absolute;
        transition: all 0.6s cubic-bezier(0.25, 0.8, 0.25, 1);
        width: 220px;
        height: 240px; /* Tinggi kotak */
        background: white;
        border-radius: 24px;
        box-shadow: 0 15px 35px -5px rgba(0,0,0,0.1);
        border: 1px solid #f3f4f6;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 20px;
        left: 20px; /* Posisi default */
        top: 50%;
        margin-top: -120px; /* Center vertikal (setengah tinggi) */
    }

    /* KARTU UTAMA (ACTIVE) */
    .carousel-item-3d.active {
        z-index: 20;
        transform: translateX(0) scale(1);
        opacity: 1;
        border: 2px solid #6366f1; /* Indigo Border */
        box-shadow: 0 20px 40px -5px rgba(99, 102, 241, 0.25);
    }

    /* KARTU KE-2 (SEBELAH KANAN) */
    .carousel-item-3d.next-1 {
        z-index: 15;
        transform: translateX(200px) scale(0.85); /* Geser kanan, kecilkan */
        opacity: 0.6;
        filter: blur(0.5px);
    }

    /* KARTU KE-3 (KANAN JAUH) */
    .carousel-item-3d.next-2 {
        z-index: 10;
        transform: translateX(360px) scale(0.7);
        opacity: 0.3;
        filter: blur(1px);
    }

    /* KARTU SISANYA (SEMBUNYI) */
    .carousel-item-3d.hidden-item {
        z-index: 5;
        transform: translateX(450px) scale(0.5);
        opacity: 0;
    }

    /* ANIMASI FLOAT UNTUK KOSONG */
    .empty-schedule-box {
        animation: float 3s ease-in-out infinite;
    }
    @keyframes float {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
        100% { transform: translateY(0px); }
    }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-10">
    
    <div class="flex items-center gap-4 mb-2">
        <?php 
            // Fix: Generate Link Balik ke Dashboard sesuai Role
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
                        <?php if($user['is_online'] > 0): ?>
                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-bold uppercase tracking-wider border border-green-200">
                                <i class="fas fa-circle text-[8px] mr-1"></i> Sedang Bertugas
                            </span>
                        <?php else: ?>
                            <span class="px-3 py-1 bg-gray-100 text-gray-500 rounded-full text-[10px] font-bold uppercase tracking-wider border border-gray-200">
                                <i class="fas fa-circle text-[8px] mr-1"></i> Offline
                            </span>
                        <?php endif; ?>
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
                    
                    <div class="flex items-center gap-3 py-4">
                        <div class="w-8 h-8 rounded-lg bg-orange-50 text-orange-500 flex items-center justify-center text-xs"><i class="fas fa-building"></i></div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold">Laboratorium</p>
                            <p class="text-xs font-bold text-gray-700"><?= $user['lab_name'] ?? 'Umum / Semua Lab' ?></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 py-4">
                        <div class="w-8 h-8 rounded-lg bg-green-50 text-green-500 flex items-center justify-center text-xs"><i class="fas fa-phone"></i></div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold">No. Telepon</p>
                            <p class="text-xs font-bold text-gray-700 font-mono"><?= $user['no_telp'] ?? '-' ?></p>
                        </div>
                    </div>

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
                    $canEdit = true;
                    $btnText = "Edit Profil";
                    $btnClass = "bg-gray-900 hover:bg-gray-800 text-white shadow-gray-900/20";
                    $link = BASE_URL . "/" . strtolower(str_replace(' ', '', $user['role'])) . "/editProfile";
                    
                    if($user['role'] != 'Admin' && isset($user['is_completed']) && $user['is_completed'] == 1) {
                        $canEdit = false;
                        $btnText = "Profil Terkunci";
                        $btnClass = "bg-gray-200 text-gray-400 cursor-not-allowed";
                        $link = "#";
                    }
                ?>

                <a href="<?= $link ?>" class="block w-full py-3 font-bold rounded-xl transition text-sm uppercase tracking-wide text-center shadow-lg <?= $btnClass ?>" <?= !$canEdit ? 'onclick="showCustomAlert(\'error\', \'Akses Ditolak\', \'Profil Anda sudah dikunci. Hubungi Admin untuk perubahan data.\'); return false;"' : '' ?>>
                    <i class="fas <?= $canEdit ? 'fa-edit' : 'fa-lock' ?> mr-2"></i> <?= $btnText ?>
                </a>
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
                </div>

            <?php else: ?>
                <div class="flex justify-between items-center bg-white p-4 rounded-2xl shadow-sm border border-gray-200">
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">Panel Informasi Lab</h3>
                        <p class="text-xs text-gray-500">Demografi asisten dan jadwal operasional.</p>
                    </div>
                    <div class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-lg text-xs font-bold">
                        Total Asisten: <?= $total_managed_users ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200 flex flex-col h-[350px]">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-sm font-bold text-gray-600 uppercase tracking-widest">Demografi</h4>
                            <div class="relative">
                                <select id="demographicFilter" onchange="updateDemographicChart()" class="bg-gray-50 border-none text-gray-600 text-xs font-bold rounded-lg py-1.5 pl-3 pr-8 focus:ring-2 focus:ring-blue-200 cursor-pointer outline-none">
                                    <option value="gender">Gender</option>
                                    <option value="class">Kelas</option>
                                    <option value="interest">Minat</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-3 top-2.5 text-gray-400 text-[10px] pointer-events-none"></i>
                            </div>
                        </div>
                        
                        <div class="flex bg-gray-50 p-1 rounded-lg w-max mb-4">
                            <button onclick="setChartType('bar')" class="p-1.5 rounded hover:bg-white shadow-sm transition text-gray-500 hover:text-indigo-600"><i class="fas fa-chart-bar text-xs"></i></button>
                            <button onclick="setChartType('line')" class="p-1.5 rounded hover:bg-white shadow-sm transition text-gray-500 hover:text-indigo-600"><i class="fas fa-chart-line text-xs"></i></button>
                            <button onclick="setChartType('pie')" class="p-1.5 rounded hover:bg-white shadow-sm transition text-gray-500 hover:text-indigo-600"><i class="fas fa-chart-pie text-xs"></i></button>
                        </div>

                        <div class="flex-1 w-full relative min-h-0">
                            <canvas id="demographicChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-indigo-50 to-blue-50 p-0 rounded-3xl shadow-sm border border-blue-100 flex flex-col h-[350px] overflow-hidden relative">
                        <div class="absolute top-5 left-6 z-30">
                            <h4 class="text-sm font-bold text-indigo-900 uppercase tracking-widest flex items-center gap-2">
                                <i class="fas fa-clock"></i> Jadwal Terdekat
                            </h4>
                        </div>

                        <div class="flex-1 relative flex items-center">
                            <?php if (empty($upcoming_schedules)): ?>
                                <div class="w-full flex flex-col items-center justify-center empty-schedule-box text-center p-6">
                                    <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center text-indigo-200 text-3xl mb-3 border border-indigo-100">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <h5 class="text-indigo-800 font-bold text-sm">Tidak Ada Jadwal</h5>
                                    <p class="text-indigo-400 text-xs mt-1">Laboratorium sedang kosong.</p>
                                </div>
                            <?php else: ?>
                                <div class="carousel-3d-container" id="scheduleCarousel">
                                    <?php foreach($upcoming_schedules as $idx => $sch): 
                                        $bgIcon = $sch['type'] == 'kuliah' ? 'bg-green-100 text-green-600' : ($sch['type'] == 'piket' ? 'bg-orange-100 text-orange-600' : 'bg-indigo-100 text-indigo-600');
                                        $icon = $sch['type'] == 'kuliah' ? 'fa-graduation-cap' : ($sch['type'] == 'piket' ? 'fa-broom' : 'fa-flask');
                                    ?>
                                    <div class="carousel-item-3d <?= $idx == 0 ? 'active' : ($idx == 1 ? 'next-1' : ($idx == 2 ? 'next-2' : 'hidden-item')) ?>" data-index="<?= $idx ?>">
                                        
                                        <div class="flex justify-between items-start w-full">
                                            <div class="w-10 h-10 rounded-xl flex items-center justify-center <?= $bgIcon ?>">
                                                <i class="fas <?= $icon ?>"></i>
                                            </div>
                                            <span class="px-2 py-1 bg-gray-100 rounded text-[9px] font-bold uppercase text-gray-500"><?= $sch['type'] ?></span>
                                        </div>
                                        
                                        <div class="w-full">
                                            <h4 class="font-bold text-gray-800 text-lg leading-snug line-clamp-2 mb-1" title="<?= $sch['title'] ?>">
                                                <?= $sch['title'] ?>
                                            </h4>
                                            <p class="text-xs text-indigo-500 font-medium flex items-center gap-1">
                                                <i class="far fa-calendar"></i> <?= $sch['display_date'] ?>
                                            </p>
                                        </div>

                                        <div class="w-full pt-4 border-t border-dashed border-gray-200 flex justify-between items-center">
                                            <div class="text-sm font-mono font-bold text-gray-700 bg-gray-50 px-2 py-1 rounded">
                                                <?= substr($sch['start_time'], 0, 5) ?>
                                            </div>
                                            <div class="text-[10px] text-gray-400 font-medium truncate max-w-[100px]">
                                                <?= $sch['location'] ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col flex-1 overflow-hidden min-h-[400px]">
                    <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                        <h4 class="text-sm font-bold text-gray-700 uppercase tracking-widest"><i class="fas fa-trophy text-yellow-500 mr-2"></i>Peringkat Asisten</h4>
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
                    
                    <div class="flex-1 overflow-y-auto p-0 custom-scrollbar" id="rankingList">
                        </div>
                </div>

                <script>
                    // === 1. DEMOGRAPHIC CHART LOGIC ===
                    // Pastikan variabel demographics dikirim dari controller
                    const rawDemographics = <?= json_encode($demographics ?? []) ?>;
                    let demoChart = null;
                    let demoType = 'bar';

                    function updateDemographicChart() {
                        const ctx = document.getElementById('demographicChart').getContext('2d');
                        const key = document.getElementById('demographicFilter').value;
                        const dataGroup = rawDemographics[key] || [];

                        // Mapping Key ke Nama Kolom Database yang sesuai
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
                                    label: 'Jumlah Asisten',
                                    data: counts,
                                    backgroundColor: (demoType == 'line') ? 'rgba(99, 102, 241, 0.2)' : colors,
                                    borderColor: (demoType == 'pie') ? '#fff' : '#4f46e5',
                                    borderWidth: 2,
                                    fill: (demoType == 'line'),
                                    tension: 0.4,
                                    borderRadius: 6
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: (demoType == 'pie') ? {} : { y: { beginAtZero: true, ticks: { precision: 0 } } },
                                plugins: { legend: { display: (demoType == 'pie'), position: 'right' } }
                            }
                        });
                    }

                    function setChartType(t) { demoType = t; updateDemographicChart(); }
                    
                    // Init Chart jika data ada
                    if(Object.keys(rawDemographics).length > 0) updateDemographicChart();


                    // === 2. CAROUSEL ANIMATION ===
                    const items = document.querySelectorAll('.carousel-item-3d');
                    if(items.length > 0) {
                        let currentIndex = 0;
                        const totalItems = items.length;

                        function rotateCarousel() {
                            currentIndex = (currentIndex + 1) % totalItems;
                            updateClasses();
                        }

                        function updateClasses() {
                            items.forEach((item, i) => {
                                // Hitung posisi relatif
                                let pos = (i - currentIndex);
                                if (pos < 0) pos += totalItems;

                                // Reset Class
                                item.className = 'carousel-item-3d'; 
                                
                                if (pos === 0) item.classList.add('active');
                                else if (pos === 1) item.classList.add('next-1');
                                else if (pos === 2) item.classList.add('next-2');
                                else item.classList.add('hidden-item');
                            });
                        }

                        setInterval(rotateCarousel, 3000); // Putar tiap 3 detik
                    }


                    // === 3. RANKING LOGIC (TETAP SAMA) ===
                    const rankings = <?= json_encode($rankings ?? []) ?>;
                    
                    function renderRanking() {
                        const filter = document.getElementById('rankingFilter').value;
                        const container = document.getElementById('rankingList');
                        const data = rankings[filter];
                        
                        container.innerHTML = '';

                        if (!data || data.length === 0) {
                            container.innerHTML = `
                                <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                                    <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mb-2"><i class="fas fa-user-slash"></i></div>
                                    <p class="text-xs italic">Tidak ada data untuk kategori ini.</p>
                                </div>`;
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
                            if(filter.includes('logbook')) scoreLabel = 'Kata/Log';
                            if(filter === 'online') scoreLabel = 'Status';

                            let photoUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(item.nama)}&background=random&size=100`;
                            if(item.photo_profile) photoUrl = `<?= BASE_URL ?>/uploads/profile/${item.photo_profile}`;

                            const html = `
                                <div class="flex items-center justify-between p-4 border-b border-gray-50 hover:bg-indigo-50/30 transition">
                                    <div class="flex items-center gap-4">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold border ${badgeColor}">
                                            #${index + 1}
                                        </div>
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