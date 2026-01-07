<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .animate-enter { animation: fadeInUp 0.5s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-10">
    
    <div class="flex items-center gap-4 mb-2">
        <a href="<?= BASE_URL ?>/superadmin/dashboard" class="w-10 h-10 flex items-center justify-center bg-white rounded-full shadow-sm text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-extrabold text-gray-800">Profil Asisten</h1>
            <p class="text-gray-500 text-sm">Data lengkap dan statistik kehadiran.</p>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 h-full min-h-[600px]">
        
        <div class="w-full lg:w-1/3 bg-white rounded-3xl shadow-sm border border-gray-200 p-8 flex flex-col items-center relative overflow-hidden">
            
            <div class="mt-2 mb-6 relative">
                <div class="w-40 h-40 rounded-full p-2 bg-gray-50 border border-gray-100 shadow-inner mx-auto">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($assistant['name']) ?>&background=random&size=500" 
                         class="w-full h-full rounded-full object-cover shadow-lg">
                </div>
            </div>

            <div class="text-center mb-6">
                <h2 class="text-xl font-extrabold text-gray-800 leading-tight"><?= $assistant['name'] ?></h2>
                <div class="flex items-center justify-center gap-2 mt-2">
                    <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[10px] font-bold uppercase tracking-wider border border-blue-100">
                        <?= $assistant['position'] ?? 'Asisten Lab' ?>
                    </span>
                    <?php if($assistant['kelas']): ?>
                    <span class="px-3 py-1 bg-purple-50 text-purple-600 rounded-full text-[10px] font-bold uppercase tracking-wider border border-purple-100">
                        Kelas <?= $assistant['kelas'] ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="w-full space-y-3 text-left">
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 space-y-3">
                    <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                        <span class="text-xs text-gray-400 font-bold uppercase">NIM</span>
                        <span class="font-mono text-sm font-bold text-gray-700"><?= $assistant['nim'] ?? '-' ?></span>
                    </div>
                    <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                        <span class="text-xs text-gray-400 font-bold uppercase">Email</span>
                        <span class="text-sm font-medium text-gray-700 truncate max-w-[150px]" title="<?= $assistant['email'] ?>"><?= $assistant['email'] ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-400 font-bold uppercase">Lab</span>
                        <span class="text-sm font-bold text-blue-600"><?= $assistant['lab_name'] ?? 'General' ?></span>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-gray-200 space-y-3 shadow-sm">
                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Informasi Pribadi</h4>
                    
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-orange-50 text-orange-500 flex items-center justify-center text-xs"><i class="fas fa-lightbulb"></i></div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold">Peminatan</p>
                            <p class="text-xs font-bold text-gray-700"><?= $assistant['peminatan'] ?? '-' ?></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-pink-50 text-pink-500 flex items-center justify-center text-xs"><i class="fas fa-venus-mars"></i></div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold">Jenis Kelamin</p>
                            <p class="text-xs font-bold text-gray-700">
                                <?= ($assistant['jenis_kelamin'] == 'L') ? 'Laki-Laki' : (($assistant['jenis_kelamin'] == 'P') ? 'Perempuan' : '-') ?>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-green-50 text-green-500 flex items-center justify-center text-xs"><i class="fas fa-phone"></i></div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold">No. Telepon</p>
                            <p class="text-xs font-bold text-gray-700 font-mono"><?= $assistant['no_telp'] ?? '-' ?></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-50 text-gray-500 flex items-center justify-center text-xs"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold">Alamat</p>
                            <p class="text-xs font-medium text-gray-600 leading-tight"><?= $assistant['alamat'] ?? '-' ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-auto w-full pt-6">
                <a href="<?= BASE_URL ?>/superadmin/assistantSchedule/<?= $assistant['id'] ?>" class="block w-full py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition text-sm uppercase tracking-wide text-center shadow-lg shadow-blue-500/30">
                    Lihat Jadwal Lengkap
                </a>
            </div>
        </div>

        <div class="w-full lg:w-2/3 bg-white rounded-3xl shadow-sm border border-gray-200 p-8 flex flex-col">
            <div class="flex justify-between items-start mb-8">
                <h3 class="text-xl font-bold text-gray-700 uppercase tracking-wide">Statistika Kehadiran</h3>
                <div class="flex bg-gray-100 rounded-lg p-1">
                    <button onclick="setChartType('bar')" class="p-2 rounded hover:bg-white shadow-sm transition"><i class="fas fa-chart-bar text-gray-500 text-xs"></i></button>
                    <button onclick="setChartType('pie')" class="p-2 rounded hover:bg-white shadow-sm transition"><i class="fas fa-chart-pie text-gray-500 text-xs"></i></button>
                </div>
            </div>

            <div class="flex-1 relative w-full h-full min-h-[400px] flex items-center justify-center bg-gray-50/50 rounded-2xl border border-gray-100 border-dashed p-4">
                <canvas id="assistantChart"></canvas>
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
        </div>
    </div>
</div>

<script>
    // --- CHART LOGIC ---
    const stats = <?= json_encode($stats) ?>;
    let chartInstance = null;
    let currentType = 'bar';

    function initChart() {
        const ctx = document.getElementById('assistantChart').getContext('2d');
        if (chartInstance) chartInstance.destroy();

        const labels = ['Hadir', 'Izin', 'Tanpa Keterangan'];
        const dataValues = [stats.hadir, stats.izin, stats.alpa];
        const bgColors = ['#22c55e', '#eab308', '#ef4444'];
        
        chartInstance = new Chart(ctx, {
            type: currentType,
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Kehadiran',
                    data: dataValues,
                    backgroundColor: bgColors,
                    borderRadius: 8,
                    borderWidth: 0,
                    barThickness: 60,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: currentType === 'pie', position: 'bottom' }
                },
                scales: currentType === 'bar' ? {
                    y: { beginAtZero: true, grid: { borderDash: [4, 4], color: '#e2e8f0' } },
                    x: { grid: { display: false } }
                } : {}
            }
        });
    }

    function setChartType(type) {
        currentType = type;
        initChart();
    }

    document.addEventListener('DOMContentLoaded', initChart);
</script>