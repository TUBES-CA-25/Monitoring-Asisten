<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* ANIMASI & STYLE DASAR */
    .animate-enter { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    
    /* CAROUSEL */
    @keyframes scroll { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
    .carousel-container { overflow: hidden; padding: 20px 0; mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent); }
    .carousel-track { display: flex; gap: 2rem; width: max-content; animation: scroll 40s linear infinite; }
    .carousel-track:hover { animation-play-state: paused; }

    /* KARTU ASISTEN POLAROID */
    .polaroid-card {
        background: white; width: 220px; padding: 10px 10px 40px 10px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;
        transition: transform 0.3s; transform: rotate(-1deg); position: relative;
        cursor: pointer;
    }
    /* Efek Hover: Scale sedikit dan Z-Index naik */
    .polaroid-card:hover { transform: scale(1.05) rotate(2deg); z-index: 10; box-shadow: 0 15px 25px rgba(0,0,0,0.1); }
    
    .led { width: 12px; height: 12px; border-radius: 50%; position: absolute; top: 20px; right: 20px; z-index: 10; animation: pulse 2s infinite; }
    .led.green { background: #22c55e; box-shadow: 0 0 10px #22c55e; }
    .led.red { background: #ef4444; box-shadow: 0 0 10px #ef4444; }
    .led.yellow { background: #eab308; box-shadow: 0 0 10px #eab308; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    .img-status.red { filter: grayscale(100%); opacity: 0.8; }
    .img-status.yellow { filter: sepia(100%); opacity: 0.9; }
</style>

<div class="max-w-7xl mx-auto space-y-8 animate-enter">
    
    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0 text-center md:text-left">
                <h1 class="text-3xl font-extrabold">Halo, <?= explode(' ', $user['name'])[0] ?> ! 👋</h1>
                <p class="text-blue-100 mt-2 text-sm">Monitoring aktivitas laboratorium secara menyeluruh.</p>
            </div>
            <div class="text-center md:text-right bg-white/10 p-3 rounded-2xl backdrop-blur-sm border border-white/20">
                <p class="text-[10px] font-bold text-blue-100 uppercase tracking-widest mb-1">Waktu Sistem</p>
                <h2 id="liveDate" class="text-xl font-bold font-mono"><?= date('d F Y') ?></h2>
                <p class="text-sm opacity-90 font-mono mt-1">
                    <span id="liveTime" class="bg-blue-900/30 px-2 py-0.5 rounded"><?= date('H:i:s') ?></span> WITA
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-3xl shadow-lg shadow-gray-200/50 border border-gray-100 mt-8">
        <h4 class="text-sm font-bold text-gray-500 uppercase tracking-widest mb-6">Ringkasan Aktivitas Hari Ini</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 p-4 rounded-2xl border border-blue-100 text-center hover:shadow-md transition">
                <span class="block text-3xl font-extrabold text-blue-600"><?= $stats['total_asisten'] ?></span>
                <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider">Total Asisten</span>
            </div>

            <div class="bg-green-50 p-4 rounded-2xl border border-green-100 text-center hover:shadow-md transition">
                <span class="block text-3xl font-extrabold text-green-600"><?= $stats['hadir_today'] ?></span>
                <span class="text-[10px] font-bold text-green-700 uppercase tracking-wider">Hadir Hari Ini</span>
            </div>

            <div class="bg-yellow-50 p-4 rounded-2xl border border-yellow-100 text-center hover:shadow-md transition">
                <span class="block text-3xl font-extrabold text-yellow-600"><?= $stats['izin_today'] ?></span>
                <span class="text-[10px] font-bold text-yellow-700 uppercase tracking-wider">Izin Hari Ini</span>
            </div>

            <div class="bg-red-50 p-4 rounded-2xl border border-red-100 text-center hover:shadow-md transition">
                <span class="block text-3xl font-extrabold text-red-600"><?= $stats['alpa_today'] ?></span>
                <span class="text-[10px] font-bold text-red-700 uppercase tracking-wider">Tidak Hadir</span>
            </div>
        </div>
    </div>

    <div class="space-y-2">
        <div class="flex items-center justify-between px-2">
            <h3 class="text-sm font-bold text-gray-600 uppercase tracking-wide">Status Asisten Realtime</h3>
            <span class="text-xs text-gray-400"><i class="fas fa-info-circle text-blue-500 mr-1"></i> Klik kartu untuk detail</span>
        </div>
        
        <?php if(!empty($assistants)): ?>
            <div class="carousel-container">
                <div class="carousel-track">
                    <?php 
                    $allCards = array_merge($assistants, $assistants); 
                    foreach($allCards as $asisten): 
                        $statusColor = $asisten['status_today'];
                    ?>
                    <div onclick="window.location.href='<?= BASE_URL ?>/superadmin/assistantDetail/<?= $asisten['id'] ?>'" class="polaroid-card rounded-xl cursor-pointer group">
                        <div class="led <?= $statusColor ?>"></div>
                        <div class="aspect-square bg-gray-100 mb-3 border border-gray-100 overflow-hidden rounded">
                            <?php 
                                $photoPath = !empty($asisten['photo_profile']) ? BASE_URL . '/uploads/profile/' . $asisten['photo_profile'] : null;
                                $avatarUrl = $photoPath ?? "https://ui-avatars.com/api/?name=" . urlencode($asisten['name']) . "&background=random&size=500";
                            ?>
                            <img src="<?= $avatarUrl ?>" 
                                class="w-full h-full object-cover img-status <?= $statusColor ?>" 
                                alt="<?= $asisten['name'] ?>">
                        </div>
                        <div class="text-center">
                            <h3 class="font-bold text-gray-800 text-sm truncate"><?= $asisten['name'] ?></h3>
                            <p class="text-[10px] text-gray-400 font-bold uppercase mt-1"><?= $asisten['position'] ?? 'Asisten Lab' ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-20 text-gray-400 bg-white rounded-3xl border border-dashed border-gray-200">
                <i class="fas fa-users-slash text-4xl mb-4"></i>
                <p>Belum ada data asisten untuk ditampilkan.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h3 class="text-lg font-bold text-gray-800">Analisis Kehadiran</h3>
            <div class="flex gap-2">
                <select id="chartFilter" onchange="updateChart()" class="bg-gray-50 border-none text-gray-600 text-xs font-bold rounded-lg p-2 focus:ring-2 focus:ring-indigo-200 cursor-pointer outline-none">
                    <option value="daily">Harian</option>
                    <option value="weekly" selected>Mingguan</option>
                    <option value="monthly">Bulanan</option>
                </select>
                <div class="flex bg-gray-100 p-1 rounded-lg">
                    <button onclick="setChartType('bar')" class="p-1.5 rounded hover:bg-white shadow-sm transition"><i class="fas fa-chart-bar text-xs text-gray-600"></i></button>
                    <button onclick="setChartType('line')" class="p-1.5 rounded hover:bg-white shadow-sm transition"><i class="fas fa-chart-line text-xs text-gray-600"></i></button>
                    <button onclick="setChartType('pie')" class="p-1.5 rounded hover:bg-white shadow-sm transition"><i class="fas fa-chart-pie text-xs text-gray-600"></i></button>
                </div>
            </div>
        </div>
        <div class="h-80 w-full"><canvas id="adminChart"></canvas></div>
    </div>
</div>

<script>
    // === 1. CHART UTAMA DASHBOARD ===
    const chartData = <?= json_encode($chart_data) ?>;
    let chartInstance = null;
    let currentType = 'bar';

    function initChart() {
        const ctx = document.getElementById('adminChart').getContext('2d');
        const filter = document.getElementById('chartFilter').value;
        const dataSet = chartData[filter];

        if(chartInstance) chartInstance.destroy();

        const bgColors = currentType === 'pie' ? ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'] : '#6366f1';
        const borderColor = currentType === 'pie' ? '#ffffff' : '#4f46e5';

        chartInstance = new Chart(ctx, {
            type: currentType,
            data: {
                labels: dataSet.labels,
                datasets: [{
                    label: 'Kehadiran',
                    data: dataSet.data,
                    backgroundColor: bgColors,
                    borderColor: borderColor,
                    borderWidth: 2,
                    borderRadius: currentType === 'pie' ? 0 : 6,
                    tension: 0.4,
                    fill: currentType === 'line'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: currentType === 'pie' ? {} : { y: { beginAtZero: true } },
                plugins: { legend: { display: currentType === 'pie' } }
            }
        });
    }

    function updateChart() { initChart(); }
    function setChartType(type) { currentType = type; initChart(); }
    
    document.addEventListener("DOMContentLoaded", function() {
        initChart();
    });

    // === 2. JAM ===
    function updateClock() {
        const now = new Date();
        const dateOptions = { day: '2-digit', month: 'long', year: 'numeric' };
        const dateString = now.toLocaleDateString('id-ID', dateOptions);
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
        const timeString = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':');
        
        const elDate = document.getElementById('liveDate');
        const elTime = document.getElementById('liveTime');
        
        if (elDate) elDate.innerText = dateString;
        if (elTime) elTime.innerText = timeString;
    }
    setInterval(updateClock, 1000); updateClock();
</script>