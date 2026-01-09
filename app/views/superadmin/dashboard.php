<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* Styling Layout & Animasi */
    .animate-enter { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    
    /* Carousel */
    @keyframes scroll { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
    .carousel-container {
        overflow: hidden;
        white-space: nowrap;
        position: relative;
        padding: 20px 0;
        mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
    }

    .carousel-track {
        display: flex;
        gap: 2rem;
        width: max-content;
        animation: scroll 40s linear infinite;
    }

    .carousel-track:hover { animation-play-state: paused; }

    /* Polaroid Card Style */
    .polaroid-card {
        background: white;
        width: 260px;
        padding: 15px 15px 50px 15px; /* Padding bawah besar untuk efek polaroid */
        box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        /* transform: rotate(-2deg); Miring sedikit random */
        border: 1px solid #f0f0f0;
        cursor: pointer;
        text-decoration: none; /* Hilangkan garis bawah link */
    }

    /* Variasi rotasi agar terlihat natural (gunakan nth-child) */
    /* .polaroid-card:nth-child(even) { transform: rotate(1deg); }
    .polaroid-card:nth-child(3n) { transform: rotate(-1.5deg); } */

    .polaroid-card:hover {
        transform: scale(1.05) rotate(0deg) !important; /* Lurus & Zoom saat hover */
        z-index: 20;
        box-shadow: 0 20px 30px -10px rgba(0,0,0,0.2);
    }

    /* Font Tulisan Tangan (Opsional, agar mirip tulisan spidol di polaroid) */
    @import url('https://fonts.googleapis.com/css2?family=Gochi+Hand&display=swap');
    .font-handwriting {
        font-family: 'Gochi Hand', cursive, sans-serif;
        letter-spacing: 1px;
    }

    /* Visual Effects */
    .img-status.red { filter: grayscale(100%) contrast(1.2); opacity: 0.9; }
    .img-status.yellow { filter: sepia(100%); opacity: 0.9; }
    .img-status.green { filter: none; }

    .led {
        width: 12px; height: 12px; border-radius: 50%;
        position: absolute; top: 20px; right: 20px;
        animation: pulse 2s infinite; z-index: 5;
    }
    .led.green { background: #22c55e; box-shadow: 0 0 10px #22c55e; }
    .led.red { background: #ef4444; box-shadow: 0 0 10px #ef4444; }
    .led.yellow { background: #eab308; box-shadow: 0 0 10px #eab308; }
    
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter h-full flex flex-col">
    
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white opacity-10 rounded-full blur-3xl -mr-16 -mt-16"></div>
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-end">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">Dashboard Pengawas</h1>
                <p class="text-blue-100 mt-2">Selamat Datang, <b><?= explode(' ', $user['name'])[0] ?></b>. Berikut adalah ringkasan hari ini.</p>
            </div>
            <div class="mt-4 md:mt-0 text-right">
                <p class="text-xs font-bold text-blue-200 uppercase tracking-widest mb-1">Waktu Sistem</p>
                <h2 id="liveDate" class="text-2xl font-bold font-mono"><?= date('d F Y') ?></h2>
                <p class="text-sm opacity-80">
                    <span id="liveTime"><?= date('H:i:s') ?></span> <span>WITA</span>
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-3xl shadow-lg shadow-gray-200/50 border border-gray-100 mt-8">
        <h4 class="text-sm font-bold text-gray-500 uppercase tracking-widest mb-6">Ringkasan Aktivitas Hari Ini</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-4 bg-green-50 rounded-2xl border border-green-100 text-center">
                <span class="text-3xl font-extrabold text-green-600"><?= $stats['hadir_today'] ?></span>
                <p class="text-xs font-bold text-gray-500 uppercase mt-1">Sudah Hadir</p>
            </div>
            <div class="p-4 bg-yellow-50 rounded-2xl border border-yellow-100 text-center">
                <span class="text-3xl font-extrabold text-yellow-600"><?= $stats['izin_today'] ?></span>
                <p class="text-xs font-bold text-gray-500 uppercase mt-1">Sakit / Izin</p>
            </div>
            <div class="p-4 bg-red-50 rounded-2xl border border-red-100 text-center">
                <span class="text-3xl font-extrabold text-red-600"><?= $stats['alpa_today'] ?></span>
                <p class="text-xs font-bold text-gray-500 uppercase mt-1">Belum Hadir</p>
            </div>
        </div>
    </div>

    <!-- <div class="grid grid-cols-1 md:grid-cols-4 gap-4 animate-enter" style="animation-delay: 0.1s;">
        <div class="bg-white p-4 rounded-xl border border-l-4 border-l-green-500 shadow-sm flex justify-between items-center">
            <div><p class="text-xs text-gray-400 font-bold uppercase">Hadir</p><h3 class="text-2xl font-bold text-gray-800"><?= $stats['hadir_today'] ?></h3></div>
            <i class="fas fa-check-circle text-green-100 text-3xl"></i>
        </div>
        <div class="bg-white p-4 rounded-xl border border-l-4 border-l-yellow-500 shadow-sm flex justify-between items-center">
            <div><p class="text-xs text-gray-400 font-bold uppercase">Izin</p><h3 class="text-2xl font-bold text-gray-800"><?= $stats['izin_today'] ?></h3></div>
            <i class="fas fa-envelope-open text-yellow-100 text-3xl"></i>
        </div>
        <div class="bg-white p-4 rounded-xl border border-l-4 border-l-red-500 shadow-sm flex justify-between items-center">
            <div><p class="text-xs text-gray-400 font-bold uppercase">Absen</p><h3 class="text-2xl font-bold text-gray-800"><?= $stats['alpa_today'] ?></h3></div>
            <i class="fas fa-times-circle text-red-100 text-3xl"></i>
        </div>
        <div class="bg-white p-4 rounded-xl border border-l-4 border-l-blue-500 shadow-sm flex justify-between items-center">
            <div><p class="text-xs text-gray-400 font-bold uppercase">Logbook</p><h3 class="text-2xl font-bold text-gray-800"><?= $stats['total_logbook'] ?></h3></div>
            <i class="fas fa-book text-blue-100 text-3xl"></i>
        </div>
    </div> -->

    <div class="space-y-4">
        <div class="flex items-center justify-between px-2">
            <h3 class="text-sm font-bold text-gray-600 uppercase tracking-wide">Daftar Asisten</h3>
            <span class="text-xs text-gray-400"><i class="fas fa-info-circle mr-1"></i> Klik foto untuk melihat profil lengkap</span>
        </div>
        
        <?php if(!empty($assistants)): ?>
            <div class="carousel-container pb-10"> <div class="carousel-track">
                    <?php 
                    // Trik Infinite Scroll: Duplikasi array
                    $cards = array_merge($assistants, $assistants); 
                    foreach($cards as $asisten): 
                        $statusColor = $asisten['status_today'];
                        // Link ke halaman detail baru
                        $detailUrl = BASE_URL . '/superadmin/assistantDetail/' . $asisten['id']; 
                    ?>
                    <a href="<?= $detailUrl ?>" class="polaroid-card group block">
                        <div class="led <?= $statusColor ?>"></div>
                        
                        <div class="aspect-square bg-gray-100 mb-4 border border-gray-100 overflow-hidden rounded-sm relative">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($asisten['name']) ?>&background=random&size=500" 
                                class="w-full h-full object-cover img-status <?= $statusColor ?> transition duration-500 group-hover:scale-110" 
                                alt="<?= $asisten['name'] ?>">
                            
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition duration-300"></div>
                        </div>

                        <div class="text-center px-2">
                            <h3 class="font-bold text-gray-800 text-lg truncate font-handwriting"><?= $asisten['name'] ?></h3>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mt-1"><?= $asisten['position'] ?? 'Asisten Lab' ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-gray-400 bg-white rounded-3xl border border-dashed border-gray-200">
                <i class="fas fa-users-slash text-4xl mb-3 opacity-30"></i>
                <p>Belum ada data asisten.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 animate-enter" style="animation-delay: 0.3s;">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h3 class="text-lg font-bold text-gray-800">Analisis Kehadiran Global</h3>
            <div class="flex gap-2">
                <select id="timeFilter" onchange="updateChart()" class="bg-gray-50 border border-gray-200 text-gray-700 text-xs rounded-lg p-2 focus:border-blue-500 outline-none">
                    <option value="daily">Harian (Jam)</option>
                    <option value="weekly" selected>Mingguan</option>
                    <option value="monthly">Bulanan</option>
                </select>
                <div class="flex bg-gray-100 p-1 rounded-lg">
                    <button onclick="setChartType('line')" class="p-1.5 rounded hover:bg-white hover:shadow-sm transition" title="Line"><i class="fas fa-chart-line text-xs"></i></button>
                    <button onclick="setChartType('bar')" class="p-1.5 rounded hover:bg-white hover:shadow-sm transition" title="Bar"><i class="fas fa-chart-bar text-xs"></i></button>
                    <button onclick="setChartType('pie')" class="p-1.5 rounded hover:bg-white hover:shadow-sm transition" title="Pie"><i class="fas fa-chart-pie text-xs"></i></button>
                </div>
            </div>
        </div>
        <div class="h-80 w-full">
            <canvas id="mainChart"></canvas>
        </div>
    </div>
</div>

<div id="modalDetail" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm transition-opacity opacity-0" id="modalBackdrop" onclick="closeModal()"></div>
    
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300 overflow-hidden flex flex-col md:flex-row max-h-[90vh]" id="modalPanel">
            
            <div class="w-full md:w-1/3 bg-gray-50 border-r border-gray-100 p-6 flex flex-col items-center text-center overflow-y-auto">
                <div class="w-24 h-24 rounded-full p-1 bg-white shadow-lg mb-3">
                    <img id="m_photo" src="" class="w-full h-full rounded-full object-cover">
                </div>
                <h2 id="m_name" class="text-xl font-bold text-gray-800 leading-tight"></h2>
                <p id="m_position" class="text-sm text-blue-600 font-bold mb-6"></p>
                
                <div class="w-full mt-auto bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Statistik Individu</h4>
                        <div class="flex bg-gray-100 rounded p-0.5">
                            <button onclick="setModalChartType('doughnut')" class="px-1.5 py-0.5 text-[10px] hover:bg-white rounded"><i class="fas fa-chart-pie"></i></button>
                            <button onclick="setModalChartType('bar')" class="px-1.5 py-0.5 text-[10px] hover:bg-white rounded"><i class="fas fa-chart-bar"></i></button>
                        </div>
                    </div>
                    <div class="relative h-48 w-full">
                        <canvas id="modalChartCanvas"></canvas>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-2/3 flex flex-col bg-white">
                <div class="flex border-b border-gray-100 px-6 pt-6 gap-6">
                    <button onclick="switchTab('presensi')" id="tab_presensi" class="tab-btn pb-3 text-sm font-bold text-blue-600 border-b-2 border-blue-600">Ringkasan Data</button>
                    <button onclick="switchTab('jadwal')" id="tab_jadwal" class="tab-btn pb-3 text-sm font-bold text-gray-400 border-b-2 border-transparent">Info Tambahan</button>
                </div>
                
                <div class="flex-1 p-6 overflow-y-auto bg-white relative">
                    <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 transition"><i class="fas fa-times text-xl"></i></button>
                    
                    <div id="content_presensi" class="tab-content space-y-4">
                        </div>

                    <div id="content_jadwal" class="tab-content hidden">
                        <div class="flex flex-col items-center justify-center h-40 text-gray-400">
                            <i class="fas fa-calendar-alt text-4xl mb-2 opacity-50"></i>
                            <p class="text-sm">Silakan cek Menu Jadwal untuk detail lengkap.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // --- 1. CONFIG CHART DASHBOARD UTAMA ---
    const chartData = <?= json_encode($chart_data) ?>;
    let currentType = 'line';
    let mainChartInstance = null; // Ganti nama variabel agar tidak tabrakan

    function initMainChart() {
        const ctx = document.getElementById('mainChart').getContext('2d');
        const timeFrame = document.getElementById('timeFilter').value;
        const dataSet = chartData[timeFrame];

        const bgColors = currentType === 'pie' 
            ? ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899']
            : 'rgba(59, 130, 246, 0.5)';
        const borderColors = currentType === 'pie' ? '#fff' : '#3b82f6';

        if (mainChartInstance) mainChartInstance.destroy();

        mainChartInstance = new Chart(ctx, {
            type: currentType,
            data: {
                labels: dataSet.labels,
                datasets: [{
                    label: 'Aktivitas Lab',
                    data: dataSet.data,
                    backgroundColor: bgColors,
                    borderColor: borderColors,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: currentType === 'pie' } },
                scales: currentType === 'pie' ? {} : { y: { beginAtZero: true, grid: { display: false } } }
            }
        });
    }

    function updateChart() { initMainChart(); }
    function setChartType(type) { currentType = type; initMainChart(); }
    
    // Jalankan Chart Dashboard
    initMainChart();


    // --- 2. CONFIG CHART MODAL (INDIVIDU) ---
    let modalChartInstance = null;
    let currentModalChartType = 'doughnut';
    let currentUserStats = null;

    function openModal(user) {
        // Tampilkan Modal UI
        const modal = document.getElementById('modalDetail');
        modal.classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('modalBackdrop').classList.remove('opacity-0');
            document.getElementById('modalPanel').classList.remove('opacity-0', 'scale-95');
            document.getElementById('modalPanel').classList.add('scale-100');
        }, 10);

        // Isi Data Profil
        document.getElementById('m_name').innerText = user.name;
        document.getElementById('m_position').innerText = user.position || 'Asisten Laboratorium';
        document.getElementById('m_photo').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=random&size=500`;

        // Simpan stats user saat ini untuk keperluan ganti tipe chart
        currentUserStats = user.stats; 

        // Inisialisasi Chart Modal
        initModalChart(currentUserStats);

        // Isi Tab Data Ringkasan (Realtime dari stats)
        const contentDiv = document.getElementById('content_presensi');
        contentDiv.innerHTML = `
            <div class="grid grid-cols-2 gap-3">
                <div class="p-3 bg-green-50 rounded-xl border border-green-100">
                    <div class="text-xs text-green-600 font-bold uppercase">Total Hadir</div>
                    <div class="text-2xl font-bold text-gray-800">${user.stats.hadir}</div>
                </div>
                <div class="p-3 bg-yellow-50 rounded-xl border border-yellow-100">
                    <div class="text-xs text-yellow-600 font-bold uppercase">Izin / Sakit</div>
                    <div class="text-2xl font-bold text-gray-800">${user.stats.izin}</div>
                </div>
                <div class="p-3 bg-red-50 rounded-xl border border-red-100">
                    <div class="text-xs text-red-600 font-bold uppercase">Tanpa Keterangan</div>
                    <div class="text-2xl font-bold text-gray-800">${user.stats.alpa}</div>
                </div>
                <div class="p-3 bg-blue-50 rounded-xl border border-blue-100">
                    <div class="text-xs text-blue-600 font-bold uppercase">Kehadiran (%)</div>
                    <div class="text-2xl font-bold text-gray-800">
                        ${Math.round((user.stats.hadir / (user.stats.hadir + user.stats.izin + user.stats.alpa || 1)) * 100)}%
                    </div>
                </div>
            </div>
            <div class="mt-4 p-4 bg-gray-50 rounded-xl border border-gray-100">
                <h5 class="font-bold text-gray-700 mb-2 text-sm">Status Hari Ini</h5>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full ${user.status_today === 'green' ? 'bg-green-500' : (user.status_today === 'yellow' ? 'bg-yellow-500' : 'bg-red-500')}"></span>
                    <span class="text-sm font-medium text-gray-600">
                        ${user.status_today === 'green' ? 'Sudah Hadir' : (user.status_today === 'yellow' ? 'Izin / Sakit' : 'Belum Hadir')}
                    </span>
                </div>
            </div>
        `;

        switchTab('presensi');
    }

    function initModalChart(stats) {
        const ctx = document.getElementById('modalChartCanvas').getContext('2d');
        
        // Hapus instance lama agar tidak tabrakan (Canvas reuse)
        if (modalChartInstance) modalChartInstance.destroy();

        // Data dinamis dari user
        const dataValues = [stats.hadir, stats.izin, stats.alpa];
        
        modalChartInstance = new Chart(ctx, {
            type: currentModalChartType,
            data: {
                labels: ['Hadir', 'Izin', 'Alpa'],
                datasets: [{
                    label: 'Statistik',
                    data: dataValues,
                    backgroundColor: ['#22c55e', '#eab308', '#ef4444'],
                    borderWidth: 1,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } }
                },
                scales: currentModalChartType === 'bar' ? { y: { beginAtZero: true } } : {}
            }
        });
    }

    function setModalChartType(type) {
        currentModalChartType = type;
        if(currentUserStats) initModalChart(currentUserStats);
    }

    function closeModal() {
        const modal = document.getElementById('modalDetail');
        document.getElementById('modalBackdrop').classList.add('opacity-0');
        document.getElementById('modalPanel').classList.add('opacity-0', 'scale-95');
        document.getElementById('modalPanel').classList.remove('scale-100');
        setTimeout(() => { modal.classList.add('hidden'); }, 300);
    }

    function switchTab(tab) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.getElementById('content_' + tab).classList.remove('hidden');
        document.querySelectorAll('.tab-btn').forEach(btn => { 
            btn.classList.remove('text-blue-600', 'border-blue-600'); 
            btn.classList.add('text-gray-400', 'border-transparent'); 
        });
        document.getElementById('tab_' + tab).classList.remove('text-gray-400', 'border-transparent');
        document.getElementById('tab_' + tab).classList.add('text-blue-600', 'border-blue-600');
    }

    // --- 3. CLOCK REALTIME ---
    function updateClock() {
        const now = new Date();
        const dateOptions = { day: 'numeric', month: 'long', year: 'numeric' };
        const dateString = now.toLocaleDateString('id-ID', dateOptions);
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
        const timeString = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':');
        document.getElementById('liveDate').innerText = dateString;
        document.getElementById('liveTime').innerText = timeString;
    }
    setInterval(updateClock, 1000);
    updateClock();
    
</script>