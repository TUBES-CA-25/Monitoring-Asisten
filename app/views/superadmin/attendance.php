<style>
    .animate-enter { animation: fadeInUp 0.5s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-12">

    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0 text-center md:text-left">
                <h1 class="text-3xl font-extrabold">Rekap Presensi</h1>
                <p class="text-blue-100 mt-2 text-sm">Monitoring kehadiran asisten per hari.</p>
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

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4 relative overflow-hidden">
            <div class="absolute right-0 top-0 h-full w-16 bg-blue-50/50 skew-x-12 transform origin-bottom-left"></div>
            <div class="relative z-10 flex items-center gap-2">
                <div class="p-2 bg-blue-100 text-blue-600 rounded-lg"><i class="fas fa-calendar-alt"></i></div>
                <h3 class="font-extrabold text-gray-700">Filter Tanggal</h3>
            </div>
            <form class="relative z-10 flex items-center gap-2 bg-gray-50 p-1.5 pl-4 rounded-xl border border-gray-200 focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-100 transition w-full sm:w-auto">
                <input type="date" name="date" value="<?= $filter_date ?>" class="bg-transparent border-none focus:ring-0 text-sm font-bold text-gray-600 outline-none p-0 w-full">
                <button type="submit" class="bg-blue-600 text-white w-8 h-8 rounded-lg flex items-center justify-center hover:bg-blue-700 transition shadow-md flex-shrink-0">
                    <i class="fas fa-search text-xs"></i>
                </button>
            </form>
        </div>

        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4 relative overflow-hidden">
            <div class="absolute right-0 top-0 h-full w-16 bg-green-50/50 skew-x-12 transform origin-bottom-left"></div>
            <div class="relative z-10 flex items-center gap-2">
                <div class="p-2 bg-green-100 text-green-600 rounded-lg"><i class="fas fa-file-export"></i></div>
                <h3 class="font-extrabold text-gray-700">Ekspor Laporan</h3>
            </div>
            <div class="relative z-10 flex gap-3 w-full sm:w-auto justify-end">
                <a href="<?= BASE_URL ?>/superadmin/exportPdf?date=<?= $filter_date ?>" class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2.5 bg-red-50 text-red-600 border border-red-100 rounded-xl font-bold text-xs hover:bg-red-600 hover:text-white hover:shadow-lg hover:shadow-red-500/30 transition group">
                    <i class="fas fa-file-pdf text-lg group-hover:scale-110 transition"></i><span>PDF</span>
                </a>
                <a href="<?= BASE_URL ?>/superadmin/exportCsv?date=<?= $filter_date ?>" class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2.5 bg-green-50 text-green-600 border border-green-100 rounded-xl font-bold text-xs hover:bg-green-600 hover:text-white hover:shadow-lg hover:shadow-green-500/30 transition group">
                    <i class="fas fa-file-excel text-lg group-hover:scale-110 transition"></i><span>Excel</span>
                </a>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-xs font-bold text-gray-400 uppercase border-b border-gray-100">
                    <tr>
                        <th class="p-5 pl-8">Asisten</th>
                        <th class="p-5">Jam Masuk</th>
                        <th class="p-5">Jam Pulang</th>
                        <th class="p-5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if(!empty($attendance_list)): foreach($attendance_list as $row): ?>
                    <tr class="hover:bg-indigo-50/30 transition duration-150 group">
                        <td class="p-5 pl-8">
                            <div class="flex items-center gap-4">
                                <?php 
                                    // Logika Foto
                                    $photoName = $row['photo_profile'] ?? ''; 
                                    // Catatan: attendance_list mungkin perlu join photo_profile di model jika belum ada
                                    // Fallback ke UI Avatars aman
                                    $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($row['name']) . "&background=random";
                                ?>
                                <img src="<?= $avatarUrl ?>" class="w-10 h-10 rounded-full border border-gray-200 shadow-sm group-hover:scale-105 transition">
                                <div>
                                    <div class="font-bold text-gray-800 text-sm"><?= $row['name'] ?></div>
                                    <div class="text-[10px] text-gray-400 font-mono mt-0.5"><?= $row['nim'] ?? 'ID: -' ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="p-5">
                            <div class="inline-flex items-center gap-2 bg-green-50 px-3 py-1.5 rounded-lg border border-green-100">
                                <i class="fas fa-arrow-down text-green-500 text-xs"></i>
                                <span class="font-mono text-sm font-bold text-green-700"><?= $row['check_in_time'] ? date('H:i', strtotime($row['check_in_time'])) : '--:--' ?></span>
                            </div>
                        </td>
                        <td class="p-5">
                            <div class="inline-flex items-center gap-2 bg-red-50 px-3 py-1.5 rounded-lg border border-red-100">
                                <i class="fas fa-arrow-up text-red-500 text-xs"></i>
                                <span class="font-mono text-sm font-bold text-red-700"><?= $row['check_out_time'] ? date('H:i', strtotime($row['check_out_time'])) : '--:--' ?></span>
                            </div>
                        </td>
                        <td class="p-5 text-center">
                            <?php $statusClass = $row['status'] == 'Hadir' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-yellow-100 text-yellow-700 border-yellow-200'; ?>
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold border <?= $statusClass ?> shadow-sm"><?= $row['status'] ?></span>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="4" class="p-12 text-center">
                            <div class="flex flex-col items-center justify-center opacity-50">
                                <i class="fas fa-calendar-times text-4xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500 text-sm font-medium">Tidak ada data presensi pada tanggal ini.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function updateClock() {
        const now = new Date();
        document.getElementById('liveDate').innerText = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        document.getElementById('liveTime').innerText = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).replace(/\./g, ':');
    }
    setInterval(updateClock, 1000); updateClock();
</script>