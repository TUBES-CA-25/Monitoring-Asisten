<<<<<<< HEAD
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .animate-enter { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .schedule-scroll::-webkit-scrollbar { height: 6px; }
    .schedule-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-10">
    
    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-extrabold">Halo, <?= explode(' ', $user['name'])[0] ?>! 👋</h1>
                <p class="text-blue-100 mt-2 text-sm">Siap untuk berkontribusi di laboratorium hari ini?</p>
            </div>
            <div class="mt-4 md:mt-0 text-right">
                <p class="text-xs font-bold text-blue-200 uppercase tracking-widest mb-1">Waktu Sistem</p>
                <h2 id="liveDate" class="text-2xl font-bold font-mono"><?= date('d F Y') ?></h2>
                <p class="text-sm opacity-80">
                    <span id="liveTime"><?= date('H:i:s') ?></span> <span>WITA</span>
                </p>
            </div>
            <!-- <a href="<?= BASE_URL ?>/user/scan" class="group flex items-center gap-3 bg-white text-blue-600 px-6 py-3 rounded-2xl font-bold shadow-lg hover:bg-blue-50 transition transform hover:scale-105">
                <div class="p-2 bg-blue-100 rounded-full group-hover:bg-blue-200 transition">
                    <i class="fas fa-qrcode text-xl"></i>
                </div>
                <span>Scan Presensi</span>
            </a> -->
        </div>
    </div>


    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-1 space-y-6">
            
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 relative overflow-hidden group hover:shadow-md transition">
                <div class="h-28 bg-gradient-to-r from-blue-600 to-cyan-500"></div>
                
                <div class="px-6 pb-6 relative">
                    <div class="-mt-12 mb-4 flex justify-center">
                        <div class="w-24 h-24 rounded-full p-1 bg-white shadow-lg">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&background=random&size=256" class="w-full h-full rounded-full object-cover">
                        </div>
                    </div>
                    
                    <div class="text-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800 leading-tight"><?= $user['name'] ?></h2>
                        <p class="text-sm text-blue-600 font-medium"><?= $user['position'] ?? 'Asisten Laboratorium' ?></p>
                        
                        <?php 
                            $st = $status_today; 
                            $bg = $st=='green'?'bg-green-50 text-green-700 border-green-200':($st=='yellow'?'bg-yellow-50 text-yellow-700 border-yellow-200':'bg-red-50 text-red-700 border-red-200');
                            $label = $st=='green'?'SUDAH HADIR':($st=='yellow'?'IZIN / SAKIT':'BELUM HADIR');
                        ?>
                        <div class="mt-3 inline-block px-4 py-1.5 rounded-full border <?= $bg ?> text-[10px] font-extrabold tracking-widest uppercase">
                            <?= $label ?>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
                        <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3 border-b border-gray-200 pb-1">Informasi Detail</h4>
                        
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xs"><i class="fas fa-id-card"></i></div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase font-bold">NIM / ID</p>
                                    <p class="text-sm font-bold text-gray-700"><?= $user['nim'] ?? '-' ?></p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center text-xs"><i class="fas fa-envelope"></i></div>
                                <div class="overflow-hidden">
                                    <p class="text-[10px] text-gray-400 uppercase font-bold">Email</p>
                                    <p class="text-sm font-bold text-gray-700 truncate"><?= $user['email'] ?? '-' ?></p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-green-100 text-green-600 flex items-center justify-center text-xs"><i class="fas fa-phone-alt"></i></div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase font-bold">No. HP</p>
                                    <p class="text-sm font-bold text-gray-700"><?= $user['phone'] ?? '+62 8XX-XXXX-XXXX' ?></p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center text-xs"><i class="fas fa-graduation-cap"></i></div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase font-bold">Jurusan</p>
                                    <p class="text-sm font-bold text-gray-700 truncate"><?= $user['major'] ?? 'Teknik Informatika' ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <a href="<?= BASE_URL ?>/user/scan" class="group block bg-gradient-to-r from-indigo-600 to-blue-600 rounded-3xl shadow-lg shadow-blue-500/30 p-1 cursor-pointer transform transition hover:-translate-y-1">
                <div class="bg-white rounded-[20px] p-4 flex items-center justify-between group-hover:bg-opacity-95 transition">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-base">Scan Presensi</h3>
                            <p class="text-xs text-gray-500">Klik untuk masuk/pulang</p>
                        </div>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 group-hover:bg-blue-600 group-hover:text-white transition">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </div>
            </a>

        </div>

        <div class="lg:col-span-2 space-y-6">
            
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                    <h3 class="font-bold text-gray-700 uppercase tracking-wide text-sm">Analisis Kehadiran</h3>
                    <div class="flex gap-2">
                        <select id="timeFilter" onchange="updateChart()" class="bg-gray-50 border-none text-gray-600 text-xs font-bold rounded-lg p-2 focus:ring-2 focus:ring-blue-200 cursor-pointer outline-none">
                            <option value="daily">Harian (7 Hari)</option>
                            <option value="weekly" selected>Mingguan (8 Minggu)</option>
                            <option value="monthly">Bulanan (Tahun Ini)</option>
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

            <div class="bg-white rounded-3xl shadow-sm border border-blue-200 p-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-blue-500"></div>
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-envelope-open-text text-blue-500"></i> Pengajuan Sakit / Izin
                </h3>
                
                <form action="<?= BASE_URL ?>/user/submit_leave" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Upload Bukti</label>
                            <input type="file" name="attachment" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition border border-gray-200 rounded-xl cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Jenis Izin</label>
                            <select name="type" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition">
                                <option value="Sakit">Sakit</option>
                                <option value="Izin">Izin Keperluan</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Keterangan</label>
                        <div class="flex gap-2">
                            <textarea name="reason" rows="2" placeholder="Tuliskan alasan izin..." class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition"></textarea>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2 rounded-xl shadow-lg shadow-blue-500/30 transition transform hover:scale-105 flex flex-col items-center justify-center gap-1 min-w-[100px]">
                                <i class="fas fa-paper-plane"></i> <span>Kirim</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mt-6">
        <h3 class="font-bold text-gray-700 uppercase tracking-wide text-sm mb-4">Jadwal Mingguan Anda</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
            <?php 
            $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
            foreach($days as $day): 
                $hasSchedule = false; 
                $schData = [];
                if(!empty($weekly_schedule)) {
                    foreach($weekly_schedule as $ws) {
                        if($ws['day_name'] == $day) { $hasSchedule = true; $schData[] = $ws; }
                    }
                }
            ?>
            <div class="min-h-[120px] border border-gray-200 rounded-2xl p-3 flex flex-col relative group hover:border-blue-400 transition bg-gray-50/50">
                <span class="text-xs font-bold text-gray-400 uppercase mb-2"><?= $day ?></span>
                <?php if($hasSchedule): foreach($schData as $s): ?>
                    <div class="bg-white p-2 rounded-lg border border-blue-100 shadow-sm mb-2 hover:shadow-md transition cursor-pointer">
                        <div class="text-[10px] font-bold text-blue-600"><?= date('H:i', strtotime($s['start_time'])) ?></div>
                        <div class="text-xs font-bold text-gray-700 truncate"><?= $s['title'] ?></div>
                    </div>
                <?php endforeach; else: ?>
                    <div class="flex-1 flex items-center justify-center">
                        <span class="text-gray-300 text-xs">- Kosong -</span>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    // --- LOGIKA CHART (DATA REAL DARI DATABASE) ---
    const chartData = <?= json_encode($chart_data) ?>;
    let chartInstance = null;
    let currentType = 'bar';

    function initChart() {
        const ctx = document.getElementById('userChart').getContext('2d');
        const filter = document.getElementById('timeFilter').value;
        const dataSet = chartData[filter];

        if(chartInstance) chartInstance.destroy();

        // Warna untuk Pie Chart
        const bgColors = currentType === 'pie' 
            ? ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6366f1', '#14b8a6'] 
            : '#94a3b8'; // Abu-abu Slate untuk Bar biasa
        
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

    function updateChart() { initChart(); }
    function setChartType(type) { currentType = type; initChart(); }

    initChart();

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
=======
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .animate-enter { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .schedule-scroll::-webkit-scrollbar { height: 6px; }
    .schedule-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-10">
    
    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-extrabold">Halo, <?= explode(' ', $user['name'])[0] ?>! 👋</h1>
                <p class="text-blue-100 mt-2 text-sm">Siap untuk berkontribusi di laboratorium hari ini?</p>
            </div>
            <div class="mt-4 md:mt-0 text-right">
                <p class="text-xs font-bold text-blue-200 uppercase tracking-widest mb-1">Waktu Sistem</p>
                <h2 id="liveDate" class="text-2xl font-bold font-mono"><?= date('d F Y') ?></h2>
                <p class="text-sm opacity-80">
                    <span id="liveTime"><?= date('H:i:s') ?></span> <span>WITA</span>
                </p>
            </div>
            <!-- <a href="<?= BASE_URL ?>/user/scan" class="group flex items-center gap-3 bg-white text-blue-600 px-6 py-3 rounded-2xl font-bold shadow-lg hover:bg-blue-50 transition transform hover:scale-105">
                <div class="p-2 bg-blue-100 rounded-full group-hover:bg-blue-200 transition">
                    <i class="fas fa-qrcode text-xl"></i>
                </div>
                <span>Scan Presensi</span>
            </a> -->
        </div>
    </div>


    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-1 space-y-6">
            
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 relative overflow-hidden group hover:shadow-md transition">
                <div class="h-28 bg-gradient-to-r from-blue-600 to-cyan-500"></div>
                
                <div class="px-6 pb-6 relative">
                    <div class="-mt-12 mb-4 flex justify-center">
                        <div class="w-24 h-24 rounded-full p-1 bg-white shadow-lg">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&background=random&size=256" class="w-full h-full rounded-full object-cover">
                        </div>
                    </div>
                    
                    <div class="text-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800 leading-tight"><?= $user['name'] ?></h2>
                        <p class="text-sm text-blue-600 font-medium"><?= $user['position'] ?? 'Asisten Laboratorium' ?></p>
                        
                        <?php 
                            $st = $status_today; 
                            $bg = $st=='green'?'bg-green-50 text-green-700 border-green-200':($st=='yellow'?'bg-yellow-50 text-yellow-700 border-yellow-200':'bg-red-50 text-red-700 border-red-200');
                            $label = $st=='green'?'SUDAH HADIR':($st=='yellow'?'IZIN / SAKIT':'BELUM HADIR');
                        ?>
                        <div class="mt-3 inline-block px-4 py-1.5 rounded-full border <?= $bg ?> text-[10px] font-extrabold tracking-widest uppercase">
                            <?= $label ?>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
                        <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3 border-b border-gray-200 pb-1">Informasi Detail</h4>
                        
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xs"><i class="fas fa-id-card"></i></div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase font-bold">NIM / ID</p>
                                    <p class="text-sm font-bold text-gray-700"><?= $user['nim'] ?? '-' ?></p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center text-xs"><i class="fas fa-envelope"></i></div>
                                <div class="overflow-hidden">
                                    <p class="text-[10px] text-gray-400 uppercase font-bold">Email</p>
                                    <p class="text-sm font-bold text-gray-700 truncate"><?= $user['email'] ?? '-' ?></p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-green-100 text-green-600 flex items-center justify-center text-xs"><i class="fas fa-phone-alt"></i></div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase font-bold">No. HP</p>
                                    <p class="text-sm font-bold text-gray-700"><?= $user['phone'] ?? '+62 8XX-XXXX-XXXX' ?></p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center text-xs"><i class="fas fa-graduation-cap"></i></div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase font-bold">Jurusan</p>
                                    <p class="text-sm font-bold text-gray-700 truncate"><?= $user['major'] ?? 'Teknik Informatika' ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <a href="<?= BASE_URL ?>/user/scan" class="group block bg-gradient-to-r from-indigo-600 to-blue-600 rounded-3xl shadow-lg shadow-blue-500/30 p-1 cursor-pointer transform transition hover:-translate-y-1">
                <div class="bg-white rounded-[20px] p-4 flex items-center justify-between group-hover:bg-opacity-95 transition">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-base">Scan Presensi</h3>
                            <p class="text-xs text-gray-500">Klik untuk masuk/pulang</p>
                        </div>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 group-hover:bg-blue-600 group-hover:text-white transition">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </div>
            </a>

        </div>

        <div class="lg:col-span-2 space-y-6">
            
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                    <h3 class="font-bold text-gray-700 uppercase tracking-wide text-sm">Analisis Kehadiran</h3>
                    <div class="flex gap-2">
                        <select id="timeFilter" onchange="updateChart()" class="bg-gray-50 border-none text-gray-600 text-xs font-bold rounded-lg p-2 focus:ring-2 focus:ring-blue-200 cursor-pointer outline-none">
                            <option value="daily">Harian (7 Hari)</option>
                            <option value="weekly" selected>Mingguan (8 Minggu)</option>
                            <option value="monthly">Bulanan (Tahun Ini)</option>
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

            <div class="bg-white rounded-3xl shadow-sm border border-blue-200 p-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-blue-500"></div>
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-envelope-open-text text-blue-500"></i> Pengajuan Sakit / Izin
                </h3>
                
                <form action="<?= BASE_URL ?>/user/submit_leave" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Upload Bukti</label>
                            <input type="file" name="attachment" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition border border-gray-200 rounded-xl cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Jenis Izin</label>
                            <select name="type" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition">
                                <option value="Sakit">Sakit</option>
                                <option value="Izin">Izin Keperluan</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Keterangan</label>
                        <div class="flex gap-2">
                            <textarea name="reason" rows="2" placeholder="Tuliskan alasan izin..." class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition"></textarea>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2 rounded-xl shadow-lg shadow-blue-500/30 transition transform hover:scale-105 flex flex-col items-center justify-center gap-1 min-w-[100px]">
                                <i class="fas fa-paper-plane"></i> <span>Kirim</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mt-6">
        <h3 class="font-bold text-gray-700 uppercase tracking-wide text-sm mb-4">Jadwal Mingguan Anda</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
            <?php 
            $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
            foreach($days as $day): 
                $hasSchedule = false; 
                $schData = [];
                if(!empty($weekly_schedule)) {
                    foreach($weekly_schedule as $ws) {
                        if($ws['day_name'] == $day) { $hasSchedule = true; $schData[] = $ws; }
                    }
                }
            ?>
            <div class="min-h-[120px] border border-gray-200 rounded-2xl p-3 flex flex-col relative group hover:border-blue-400 transition bg-gray-50/50">
                <span class="text-xs font-bold text-gray-400 uppercase mb-2"><?= $day ?></span>
                <?php if($hasSchedule): foreach($schData as $s): ?>
                    <div class="bg-white p-2 rounded-lg border border-blue-100 shadow-sm mb-2 hover:shadow-md transition cursor-pointer">
                        <div class="text-[10px] font-bold text-blue-600"><?= date('H:i', strtotime($s['start_time'])) ?></div>
                        <div class="text-xs font-bold text-gray-700 truncate"><?= $s['title'] ?></div>
                    </div>
                <?php endforeach; else: ?>
                    <div class="flex-1 flex items-center justify-center">
                        <span class="text-gray-300 text-xs">- Kosong -</span>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    // --- LOGIKA CHART (DATA REAL DARI DATABASE) ---
    const chartData = <?= json_encode($chart_data) ?>;
    let chartInstance = null;
    let currentType = 'bar';

    function initChart() {
        const ctx = document.getElementById('userChart').getContext('2d');
        const filter = document.getElementById('timeFilter').value;
        const dataSet = chartData[filter];

        if(chartInstance) chartInstance.destroy();

        // Warna untuk Pie Chart
        const bgColors = currentType === 'pie' 
            ? ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6366f1', '#14b8a6'] 
            : '#94a3b8'; // Abu-abu Slate untuk Bar biasa
        
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

    function updateChart() { initChart(); }
    function setChartType(type) { currentType = type; initChart(); }

    initChart();

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
>>>>>>> main
</script>