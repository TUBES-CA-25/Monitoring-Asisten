<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<style>
    /* ANIMASI & STYLE DASAR */
    .animate-enter { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    
    /* CAROUSEL */
    @keyframes scroll { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
    .carousel-container { overflow: hidden; padding: 20px 0; mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent); }
    .carousel-track { display: flex; gap: 2rem; width: max-content; animation: scroll 40s linear infinite; }
    .carousel-track:hover { animation-play-state: paused; }

    /* KARTU ASISTEN */
    .polaroid-card {
        background: white; width: 220px; padding: 10px 10px 40px 10px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;
        transition: transform 0.3s; transform: rotate(-1deg); position: relative;
    }
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
            <p class="text-blue-100 mt-2 text-sm">Kelola operasional lab dan pantau presensi real-time.</p>
            
            <button onclick="openQRModal()" class="mt-6 bg-white text-blue-600 px-6 py-3 rounded-xl font-bold shadow-lg hover:bg-indigo-50 transition transform hover:scale-105 flex items-center gap-2 mx-auto md:mx-0">
                <i class="fas fa-qrcode"></i> Buka QR Presensi
            </button>
            
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
            <h3 class="text-sm font-bold text-gray-600 uppercase tracking-wide">Daftar Asisten</h3>
            <span class="text-xs text-gray-400"><i class="fas fa-circle text-green-500 text-[8px] animate-pulse mr-1"></i> Klik kartu untuk detail</span>
        </div>
        
        <?php if(!empty($assistants)): ?>
            <div class="carousel-container">
                <div class="carousel-track">
                    <?php 
                    $allCards = array_merge($assistants, $assistants); 
                    foreach($allCards as $asisten): 
                        $statusColor = $asisten['status_today'];
                        // Encode data user untuk dikirim ke JS Modal
                        $jsonUser = htmlspecialchars(json_encode($asisten), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="polaroid-card rounded-xl cursor-pointer" onclick="openDetailModal(<?= $jsonUser ?>)">
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
                        <div class="absolute inset-0 bg-black/5 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                            <span class="bg-white/90 px-3 py-1 rounded-full text-xs font-bold shadow-sm">Lihat Detail</span>
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

<div id="qrModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm" onclick="closeQRModal()"></div>
    <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="qrContent">
        <div class="bg-indigo-600 p-6 text-center">
            <h3 class="text-xl font-bold text-white tracking-wide" id="qrTitle">SCAN UNTUK MASUK</h3>
            <p class="text-indigo-200 text-xs mt-1">Arahkan kamera HP ke kode di bawah ini</p>
            <button onclick="closeQRModal()" class="absolute top-4 right-4 text-white/70 hover:text-white"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-8 flex flex-col items-center">
            <div class="p-4 bg-white border-4 border-dashed border-indigo-100 rounded-xl mb-6 shadow-inner relative group">
                <div id="qrcode" class="opacity-90 transition group-hover:opacity-100"></div>
            </div>
            <div class="flex bg-gray-100 p-1 rounded-full w-full max-w-[200px] relative">
                <div class="w-1/2 text-center text-[10px] font-bold text-gray-500 py-2 uppercase z-10">Masuk</div>
                <div class="w-1/2 text-center text-[10px] font-bold text-gray-500 py-2 uppercase z-10">Pulang</div>
                <div id="qrToggleBtn" class="absolute left-1 top-1 bottom-1 w-[calc(50%-4px)] bg-white rounded-full shadow-md transition-all duration-300 border border-gray-200"></div>
                <button onclick="setQRMode(true)" class="absolute left-0 top-0 w-1/2 h-full z-20"></button>
                <button onclick="setQRMode(false)" class="absolute right-0 top-0 w-1/2 h-full z-20"></button>
            </div>
        </div>
    </div>
</div>

<div id="detailModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm transition-opacity opacity-0" id="detailBackdrop" onclick="closeDetailModal()"></div>
    
    <div class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col md:flex-row max-h-[90vh]" id="detailContent">
        
        <div class="w-full md:w-1/3 bg-gray-50 border-r border-gray-100 p-6 flex flex-col items-center text-center overflow-y-auto">
            <div class="w-24 h-24 rounded-full p-1 bg-white shadow-lg mb-3">
                <img id="m_photo" src="" class="w-full h-full rounded-full object-cover">
            </div>
            <h2 id="m_name" class="text-xl font-bold text-gray-800 leading-tight"></h2>
            <p id="m_position" class="text-sm text-indigo-600 font-bold mb-6"></p>
            
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
            <div class="flex border-b border-gray-100 px-6 pt-6 gap-6 justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800">Ringkasan Data</h3>
                <button onclick="closeDetailModal()" class="text-gray-400 hover:text-red-500 transition"><i class="fas fa-times text-xl"></i></button>
            </div>
            
            <div class="flex-1 p-6 overflow-y-auto bg-white">
                <div id="m_stats_grid" class="grid grid-cols-2 gap-4">
                    </div>
                
                <div class="mt-6 p-4 bg-indigo-50 rounded-xl border border-indigo-100">
                    <h5 class="font-bold text-indigo-900 mb-2 text-sm">Status Hari Ini</h5>
                    <div class="flex items-center gap-2">
                        <span id="m_status_dot" class="w-3 h-3 rounded-full bg-gray-300"></span>
                        <span id="m_status_text" class="text-sm font-medium text-indigo-700">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // === 1. QR LOGIC ===
    const qrDataIn = '<?= $qr_in ?>'; const qrDataOut = '<?= $qr_out ?>';
    const qrcodeContainer = document.getElementById("qrcode");
    let qrCodeObj = new QRCode(qrcodeContainer, { text: qrDataIn, width: 200, height: 200 });

    function openQRModal() {
        document.getElementById('qrModal').classList.remove('hidden');
        setTimeout(() => { document.getElementById('qrContent').classList.remove('scale-95', 'opacity-0'); document.getElementById('qrContent').classList.add('scale-100', 'opacity-100'); }, 10);
        setQRMode(true);
    }
    function closeQRModal() {
        document.getElementById('qrContent').classList.remove('scale-100', 'opacity-100'); document.getElementById('qrContent').classList.add('scale-95', 'opacity-0');
        setTimeout(() => { document.getElementById('qrModal').classList.add('hidden'); }, 300);
    }
    function setQRMode(isEntry) {
        const title = document.getElementById('qrTitle'); const toggle = document.getElementById('qrToggleBtn');
        qrCodeObj.clear();
        if (isEntry) { qrCodeObj.makeCode(qrDataIn); title.innerText = "SCAN UNTUK MASUK"; title.parentElement.className = "bg-indigo-600 p-6 text-center"; toggle.style.transform = "translateX(0)"; }
        else { qrCodeObj.makeCode(qrDataOut); title.innerText = "SCAN UNTUK PULANG"; title.parentElement.className = "bg-red-600 p-6 text-center"; toggle.style.transform = "translateX(100%)"; }
    }

    // === 2. MODAL DETAIL ASISTEN LOGIC ===
    let modalChartInstance = null;
    let currentModalChartType = 'doughnut';
    let currentStats = null;

    function openDetailModal(user) {
        // Tampilkan Modal
        const m = document.getElementById('detailModal');
        m.classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('detailBackdrop').classList.remove('opacity-0');
            document.getElementById('detailContent').classList.remove('scale-95', 'opacity-0');
            document.getElementById('detailContent').classList.add('scale-100', 'opacity-100');
        }, 10);

        // Isi Data Profil
        document.getElementById('m_name').innerText = user.name;
        document.getElementById('m_position').innerText = user.position || 'Asisten Laboratorium';
        let photoUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=random&size=500`;
        
        if (user.photo_profile && user.photo_profile.trim() !== "") {
            photoUrl = `<?= BASE_URL ?>/uploads/profile/${user.photo_profile}`;
        }
        
        document.getElementById('m_photo').src = photoUrl;

        // Simpan Stats untuk Chart
        currentStats = user.stats;
        initModalChart(user.stats);

        // Isi Grid Statistik
        const total = (user.stats.hadir + user.stats.izin + user.stats.alpa) || 1;
        const percent = Math.round((user.stats.hadir / total) * 100);
        
        document.getElementById('m_stats_grid').innerHTML = `
            <div class="p-4 bg-green-50 rounded-xl border border-green-100">
                <div class="text-xs text-green-600 font-bold uppercase">Hadir</div>
                <div class="text-2xl font-bold text-gray-800">${user.stats.hadir}</div>
            </div>
            <div class="p-4 bg-yellow-50 rounded-xl border border-yellow-100">
                <div class="text-xs text-yellow-600 font-bold uppercase">Izin</div>
                <div class="text-2xl font-bold text-gray-800">${user.stats.izin}</div>
            </div>
            <div class="p-4 bg-red-50 rounded-xl border border-red-100">
                <div class="text-xs text-red-600 font-bold uppercase">Tidak Hadir</div>
                <div class="text-2xl font-bold text-gray-800">${user.stats.alpa}</div>
            </div>
            <div class="p-4 bg-indigo-50 rounded-xl border border-indigo-100">
                <div class="text-xs text-indigo-600 font-bold uppercase">Persentase</div>
                <div class="text-2xl font-bold text-gray-800">${percent}%</div>
            </div>
        `;

        // Status Hari Ini
        const dot = document.getElementById('m_status_dot');
        const txt = document.getElementById('m_status_text');
        if (user.status_today === 'green') {
            dot.className = "w-3 h-3 rounded-full bg-green-500 animate-pulse";
            txt.innerText = "Sudah Hadir Hari Ini";
            txt.className = "text-sm font-bold text-green-600";
        } else if (user.status_today === 'yellow') {
            dot.className = "w-3 h-3 rounded-full bg-yellow-500";
            txt.innerText = "Sedang Izin / Sakit";
            txt.className = "text-sm font-bold text-yellow-600";
        } else {
            dot.className = "w-3 h-3 rounded-full bg-red-500";
            txt.innerText = "Belum Melakukan Presensi";
            txt.className = "text-sm font-bold text-red-600";
        }
    }

    function initModalChart(stats) {
        const ctx = document.getElementById('modalChartCanvas').getContext('2d');
        if (modalChartInstance) modalChartInstance.destroy();

        modalChartInstance = new Chart(ctx, {
            type: currentModalChartType,
            data: {
                labels: ['Hadir', 'Izin', 'Tidak Hadir'],
                datasets: [{
                    data: [stats.hadir, stats.izin, stats.alpa],
                    backgroundColor: ['#22c55e', '#eab308', '#ef4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } },
                scales: currentModalChartType === 'bar' ? { y: { beginAtZero: true } } : {}
            }
        });
    }

    function setModalChartType(type) {
        currentModalChartType = type;
        if(currentStats) initModalChart(currentStats);
    }

    function closeDetailModal() {
        document.getElementById('detailContent').classList.remove('scale-100', 'opacity-100');
        document.getElementById('detailContent').classList.add('scale-95', 'opacity-0');
        document.getElementById('detailBackdrop').classList.add('opacity-0');
        setTimeout(() => { document.getElementById('detailModal').classList.add('hidden'); }, 300);
    }

    // === 3. CHART UTAMA DASHBOARD ===
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
    initChart();

    // === 4. JAM ===
    function updateClock() {
        const now = new Date();
        
        const dateOptions = { day: '2-digit', month: 'long', year: 'numeric' };
        const dateString = now.toLocaleDateString('id-ID', dateOptions);
        
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
        const timeString = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':');
        
        // Update DOM dengan Safety Check
        const elDate = document.getElementById('liveDate');
        const elTime = document.getElementById('liveTime');
        
        if (elDate) elDate.innerText = dateString;
        if (elTime) elTime.innerText = timeString;
    }
    setInterval(updateClock, 1000); updateClock();
</script>