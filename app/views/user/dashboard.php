<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php
if (!isset($user) || !is_array($user)) {
    $user = [
        'name' => $_SESSION['name'] ?? '',
        'photo_profile' => $_SESSION['photo_profile'] ?? '',
        'nim' => $_SESSION['nim'] ?? '',
        'position' => $_SESSION['jabatan'] ?? $_SESSION['role'] ?? 'Asisten Lab',
        'no_telp' => $_SESSION['no_telp'] ?? ''
    ];
}
if (!isset($weekly_schedule) || !is_array($weekly_schedule)) $weekly_schedule = [];
if (!isset($stats) || !is_array($stats)) $stats = ['hadir'=>0,'izin'=>0,'alpa'=>0];
if (!isset($status_today)) $status_today = 'red';
?>

<style>
    .animate-enter { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    
    /* Custom Scrollbar */
    .schedule-scroll::-webkit-scrollbar { height: 6px; }
    .schedule-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-10">
    
    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0 text-center md:text-left">
                <h1 class="text-3xl font-extrabold">Halo, <?= explode(' ', $user['name'])[0] ?>! 👋</h1>
                <p class="text-blue-100 mt-2 text-sm">Siap untuk berkontribusi di laboratorium hari ini?</p>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 relative overflow-hidden group hover:shadow-md transition">
                <div class="h-24 bg-gradient-to-r from-blue-600 to-cyan-500"></div>
                
                <div class="px-6 pb-6 relative">
                    <div class="-mt-12 mb-4 flex justify-center">
                        <div class="w-24 h-24 rounded-full p-1.5 bg-white shadow-lg relative">
                            <?php 
                                $photoPath = !empty($user['photo_profile']) ? BASE_URL . '/uploads/profile/' . $user['photo_profile'] : null;
                                $avatarUrl = $photoPath ?? "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=random&size=500";
                            ?>
                            <img src="<?= $avatarUrl ?>" 
                                class="w-full h-full rounded-full object-cover shadow-lg">
                            <div class="absolute bottom-1 right-1 w-5 h-5 rounded-full border-2 border-white <?= ($status_today == 'green') ? 'bg-green-500' : (($status_today == 'yellow') ? 'bg-yellow-500' : 'bg-red-500') ?>" title="Status Kehadiran"></div>
                        </div>
                    </div>
                    
                    <div class="text-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800 leading-tight"><?= $user['name'] ?></h2>
                        <p class="text-sm text-blue-600 font-medium bg-blue-50 inline-block px-3 py-1 rounded-full mt-2"><?= $user['position'] ?? 'Asisten Laboratorium' ?></p>
                        
                        <?php 
                            $st = $status_today; 
                            $label = $st=='green'?'SUDAH HADIR':($st=='yellow'?'IZIN / SAKIT':'BELUM HADIR');
                            $badgeColor = $st=='green'?'text-green-600 bg-green-50':($st=='yellow'?'text-yellow-600 bg-yellow-50':'text-red-600 bg-red-50');
                        ?>
                        <div class="mt-4 text-[10px] font-extrabold tracking-widest uppercase <?= $badgeColor ?> py-2 rounded-xl border border-opacity-50">
                            <?= $label ?>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 space-y-3">
                        <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                            <span class="text-[10px] font-bold text-gray-400 uppercase">NIM / ID</span>
                            <span class="text-sm font-bold text-gray-700"><?= $user['nim'] ?? '-' ?></span>
                        </div>
                        <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                            <span class="text-[10px] font-bold text-gray-400 uppercase">Jurusan</span>
                            <span class="text-xs font-bold text-gray-700 truncate w-32 text-right">Informatika</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold text-gray-400 uppercase">No. HP</span>
                            <span class="text-xs font-bold text-gray-700"><?= $user['no_telp'] ?? '-' ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <a href="<?= BASE_URL ?>/user/scan" class="group flex items-center justify-between bg-white border border-blue-100 rounded-3xl p-4 shadow-sm hover:shadow-md transition cursor-pointer">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl group-hover:bg-blue-600 group-hover:text-white transition">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-sm">Scan Presensi</h3>
                        <p class="text-[10px] text-gray-500">Klik untuk masuk/pulang</p>
                    </div>
                </div>
                <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 group-hover:text-blue-600 transition">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            <div class="grid grid-cols-3 gap-4 p-2">
                <div class="bg-green-50 p-4 rounded-2xl border border-green-100 text-center">
                    <span class="block text-2xl font-extrabold text-green-600"><?= $stats['hadir'] ?></span>
                    <span class="text-[10px] font-bold text-green-700 uppercase tracking-wider">Hadir</span>
                </div>
                <div class="bg-yellow-50 p-4 rounded-2xl border border-yellow-100 text-center">
                    <span class="block text-2xl font-extrabold text-yellow-600"><?= $stats['izin'] ?></span>
                    <span class="text-[10px] font-bold text-yellow-700 uppercase tracking-wider">Izin</span>
                </div>
                <div class="bg-red-50 p-4 rounded-2xl border border-red-100 text-center">
                    <span class="block text-2xl font-extrabold text-red-600"><?= $stats['alpa'] ?></span>
                    <span class="text-[10px] font-bold text-red-700 uppercase tracking-wider">Tidak Hadir</span>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                    <h3 class="font-bold text-gray-700 uppercase tracking-wide text-xs">Analisis Kehadiran</h3>
                    <div class="flex gap-2">
                        <select id="timeFilter" class="bg-gray-50 border-none text-gray-600 text-xs font-bold rounded-lg p-2 focus:ring-2 focus:ring-blue-200 cursor-pointer outline-none">
                            <option value="daily">Harian (7 Hari)</option>
                            <option value="weekly">Mingguan</option>
                            <option value="monthly">Bulanan</option>
                        </select>
                        <div class="flex bg-gray-100 p-1 rounded-lg">
                            <button onclick="setChartType('bar')" class="p-1.5 rounded hover:bg-white shadow-sm transition" title="Bar"><i class="fas fa-chart-bar text-xs text-gray-600"></i></button>
                            <button onclick="setChartType('line')" class="p-1.5 rounded hover:bg-white shadow-sm transition" title="Line"><i class="fas fa-chart-line text-xs text-gray-600"></i></button>
                            <button onclick="setChartType('pie')" class="p-1.5 rounded hover:bg-white shadow-sm transition" title="Pie"><i class="fas fa-chart-pie text-xs text-gray-600"></i></button>
                        </div>
                    </div>
                </div>
                <div class="h-64 w-full relative">
                    <canvas id="userChart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-blue-100 p-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-blue-500"></div>
                <h3 class="font-bold text-gray-800 mb-5 flex items-center gap-2 text-sm">
                    <i class="fas fa-envelope-open-text text-blue-500"></i> Pengajuan Sakit / Izin
                </h3>
                
                <form action="<?= BASE_URL ?>/user/submit_leave" method="POST" enctype="multipart/form-data" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Upload Bukti</label>
                            <div class="relative">
                                <input type="file" name="attachment" id="fileInput" class="hidden" onchange="document.getElementById('fileName').innerText = this.files[0].name">
                                <label for="fileInput" class="flex items-center w-full cursor-pointer bg-white border border-gray-200 rounded-xl overflow-hidden hover:border-blue-300 transition group">
                                    <div class="bg-blue-50 text-blue-600 px-4 py-2.5 text-xs font-bold border-r border-gray-100 group-hover:bg-blue-100 transition">
                                        Choose File
                                    </div>
                                    <div id="fileName" class="px-3 text-xs text-gray-500 truncate">No file chosen</div>
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Jenis Izin</label>
                            <select name="type" class="w-full p-2.5 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition cursor-pointer">
                                <option value="Sakit">Sakit</option>
                                <option value="Izin">Izin</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Keterangan</label>
                        <div class="flex gap-3">
                            <input type="text" name="reason" placeholder="Tuliskan alasan izin..." class="w-full p-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
                            
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2.5 rounded-xl shadow-lg shadow-blue-500/30 transition transform hover:scale-105 flex items-center gap-2 text-sm shrink-0">
                                <i class="fas fa-paper-plane"></i> Kirim
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="flex items-center gap-3 transition cursor-pointer group select-none">
                            <div class="relative flex items-center">
                                <input type="checkbox" required class="peer w-5 h-5 cursor-pointer appearance-none rounded border border-gray-300 shadow-sm checked:bg-blue-600 checked:border-blue-600 transition-all focus:ring-2 focus:ring-blue-200">
                                <i class="fas fa-check text-white absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-xs opacity-0 peer-checked:opacity-100 pointer-events-none transition-opacity"></i>
                            </div>
                            <span class="text-sm font-bold text-gray-600 group-hover:text-gray-800">
                                Apakah data yang telah diinputkan sudah benar ?
                            </span>
                        </label>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mt-6">
        <h3 class="font-bold text-gray-700 uppercase tracking-wide text-xs mb-4">Jadwal Mingguan Anda</h3>
        <div class="flex gap-3 overflow-x-auto pb-2 schedule-scroll">
            <?php 
            // Parsing Jadwal untuk Tampilan Horizontal
            $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
            // Grouping data
            $grouped = [];
            foreach($days as $d) $grouped[$d] = [];
            
            if(!empty($weekly_schedule)) {
                foreach($weekly_schedule as $sch) {
                    $dName = '';
                    // Deteksi nama hari dari 'hari' (recurring) atau 'tanggal'
                    if(!empty($sch['hari'])) {
                        $map = [1=>'Senin', 2=>'Selasa', 3=>'Rabu', 4=>'Kamis', 5=>'Jumat', 6=>'Sabtu', 7=>'Minggu'];
                        $dName = $map[$sch['hari']] ?? '';
                    } elseif (!empty($sch['tanggal'])) {
                        $ts = strtotime($sch['tanggal']);
                        $dayEn = date('l', $ts);
                        $mapEn = ['Monday'=>'Senin', 'Tuesday'=>'Selasa', 'Wednesday'=>'Rabu', 'Thursday'=>'Kamis', 'Friday'=>'Jumat', 'Saturday'=>'Sabtu', 'Sunday'=>'Minggu'];
                        $dName = $mapEn[$dayEn] ?? '';
                    }
                    if($dName) $grouped[$dName][] = $sch;
                }
            }

            foreach($days as $day): 
                $schedules = $grouped[$day];
            ?>
            <div class="min-w-[140px] flex-1 bg-gray-50 border border-gray-200 rounded-2xl p-3 flex flex-col gap-2 transition hover:border-blue-300">
                <span class="text-[10px] font-extrabold text-gray-400 uppercase text-center block mb-1"><?= $day ?></span>
                
                <?php if(empty($schedules)): ?>
                    <div class="flex-1 flex items-center justify-center text-gray-300 text-[10px] italic py-2">
                        - Kosong -
                    </div>
                <?php else: foreach($schedules as $s): 
                    $cClass = ($s['type'] == 'kuliah') ? 'text-green-600 bg-green-100' : (($s['type'] == 'asisten') ? 'text-blue-600 bg-blue-100' : 'text-orange-600 bg-orange-100');
                ?>
                    <div class="bg-white p-2 rounded-xl border border-gray-100 shadow-sm">
                        <div class="text-[9px] font-bold <?= $cClass ?> inline-block px-1.5 rounded mb-1">
                            <?= date('H:i', strtotime($s['start_time'])) ?>
                        </div>
                        <div class="text-xs font-bold text-gray-700 truncate" title="<?= $s['title'] ?>"><?= $s['title'] ?></div>
                        <div class="text-[9px] text-gray-400 truncate"><?= $s['location'] ?? '-' ?></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    // Data Chart dari PHP (Controller)
    const chartData = <?= json_encode($chart_data ?? []) ?>;
    let chartInstance = null;
    let currentType = 'bar';

    // 1. FUNGSI JAM DIGITAL
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

    // 2. FUNGSI GRAFIK
    function initChart() {
        const ctxEl = document.getElementById('userChart');
        if (!ctxEl) return; // Hentikan jika canvas tidak ada
        
        const ctx = ctxEl.getContext('2d');
        const filterEl = document.getElementById('timeFilter');
        
        // Logika Fallback: Jika data Mingguan/Bulanan belum ada, default ke 'daily'
        let filter = filterEl ? filterEl.value : 'daily';
        if (!chartData[filter]) {
            // Jika data untuk filter yang dipilih kosong, cari data yang tersedia (misal 'daily')
            if(chartData['daily']) {
                filter = 'daily';
                if(filterEl) filterEl.value = 'daily'; // Update dropdown UI
            }
        }

        const dataSet = chartData[filter] || { labels: [], data: [] };

        if(chartInstance) chartInstance.destroy();

        // Warna untuk Pie Chart vs Bar/Line
        const bgColors = currentType === 'pie' 
            ? ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6366f1', '#14b8a6'] 
            : '#94a3b8';
        
        const labelText = filter === 'daily' ? 'Status Hadir (1=Ya)' : (filter === 'weekly' ? 'Jumlah Hari Hadir' : 'Total Kehadiran');

        chartInstance = new Chart(ctx, {
            type: currentType,
            data: {
                labels: dataSet.labels,
                datasets: [{
                    label: labelText,
                    data: dataSet.data,
                    backgroundColor: currentType === 'bar' ? '#cbd5e1' : bgColors,
                    hoverBackgroundColor: '#3b82f6',
                    borderColor: '#64748b',
                    borderWidth: 1,
                    borderRadius: 4,
                    tension: 0.4,
                    fill: currentType === 'line'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: currentType === 'pie' ? {} : { 
                    y: { beginAtZero: true, grid: { display: false }, ticks: { precision: 0 } }, 
                    x: { grid: { display: false } } 
                },
                plugins: { legend: { display: currentType === 'pie' } }
            }
        });
    }

    // Fungsi Helper Global
    function updateChart() { initChart(); }
    function setChartType(type) { currentType = type; initChart(); }

    // 3. INISIALISASI SETELAH DOM READY
    document.addEventListener("DOMContentLoaded", function() {
        // Jalankan Jam
        setInterval(updateClock, 1000);
        updateClock();

        // Jalankan Grafik
        initChart();
        
        // Listener Filter Grafik
        const filterEl = document.getElementById('timeFilter');
        if(filterEl) filterEl.addEventListener('change', () => initChart());
    });
</script>