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
                <p class="text-blue-100 mt-2 text-sm">Monitoring kehadiran, izin, dan alpha asisten.</p>
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

    <div class="max-w-7xl mx-auto space-y-6 animate-enter">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200 flex flex-col lg:flex-row items-center justify-between gap-4 relative overflow-hidden">
                <div class="absolute right-0 top-0 h-full w-16 bg-blue-50/50 skew-x-12 transform origin-bottom-left"></div>
                
                <div class="relative z-10 flex items-center gap-2 flex-shrink-0 self-start lg:self-center">
                    <div class="p-2 bg-blue-100 text-blue-600 rounded-lg">
                        <i class="fas fa-filter"></i>
                    </div>
                    <h3 class="font-extrabold text-gray-700">Filter Data</h3>
                </div>

                <form class="relative z-10 flex flex-col gap-3 w-full lg:w-auto">
                    <div class="grid grid-cols-2 gap-2 w-full">
                        <div class="flex items-center gap-2 bg-gray-50 p-2 rounded-xl border border-gray-200">
                            <span class="text-[10px] text-gray-400 font-bold uppercase mr-1">Dari</span>
                            <input type="date" name="start_date" value="<?= $start_date ?>" class="bg-transparent border-none focus:ring-0 text-xs font-bold text-gray-600 outline-none p-0 w-full">
                        </div>
                        <div class="flex items-center gap-2 bg-gray-50 p-2 rounded-xl border border-gray-200">
                            <span class="text-[10px] text-gray-400 font-bold uppercase mr-1">Sampai</span>
                            <input type="date" name="end_date" value="<?= $end_date ?>" class="bg-transparent border-none focus:ring-0 text-xs font-bold text-gray-600 outline-none p-0 w-full">
                        </div>
                    </div>

                    <div class="flex gap-2 w-full">
                        <select name="assistant_id" class="flex-1 bg-gray-50 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl p-2.5 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer">
                            <option value="">-- Semua Asisten --</option>
                            <?php foreach($assistants_list as $ast): ?>
                                <option value="<?= $ast['id_user'] ?>" <?= ($selected_assistant == $ast['id_user']) ? 'selected' : '' ?>>
                                    <?= $ast['nama'] ?> (<?= $ast['nim'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-xl flex items-center justify-center hover:bg-blue-700 transition shadow-md font-bold text-xs" title="Terapkan Filter">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4 relative overflow-hidden">
                <div class="absolute right-0 top-0 h-full w-16 bg-green-50/50 skew-x-12 transform origin-bottom-left"></div>
                
                <div class="relative z-10 flex items-center gap-2 self-start sm:self-center">
                    <div class="p-2 bg-green-100 text-green-600 rounded-lg">
                        <i class="fas fa-file-export"></i>
                    </div>
                    <h3 class="font-extrabold text-gray-700">Ekspor Laporan</h3>
                </div>

                <div class="relative z-10 flex gap-3 w-full sm:w-auto justify-end">
                    <?php 
                        // Deteksi Role untuk Link Export
                        $roleLink = strtolower(str_replace(' ', '', $_SESSION['role'])); 
                        $qs = "start_date=" . $start_date . "&end_date=" . $end_date . "&assistant_id=" . $selected_assistant;
                    ?>
                    <a href="<?= BASE_URL ?>/<?= $roleLink ?>/exportPdf?<?= $qs ?>" class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-5 py-2.5 bg-red-50 text-red-600 border border-red-100 rounded-xl font-bold text-xs hover:bg-red-600 hover:text-white hover:shadow-lg transition group">
                        <i class="fas fa-file-pdf text-lg group-hover:scale-110 transition"></i> <span>PDF</span>
                    </a>
                    <a href="<?= BASE_URL ?>/<?= $roleLink ?>/exportCsv?<?= $qs ?>" class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-5 py-2.5 bg-green-50 text-green-600 border border-green-100 rounded-xl font-bold text-xs hover:bg-green-600 hover:text-white hover:shadow-lg transition group">
                        <i class="fas fa-file-excel text-lg group-hover:scale-110 transition"></i> <span>Excel</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-xs font-bold text-gray-400 uppercase border-b border-gray-100">
                    <tr>
                        <th class="p-5 pl-8 w-[250px]">Asisten</th>
                        <th class="p-5 text-center w-[150px]">Tanggal</th>
                        <th class="p-5 text-center">Masuk</th>
                        <th class="p-5 text-center">Pulang</th>
                        <th class="p-5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php 
                    if(!empty($attendance_list)): 
                        $currentDateGroup = '';
                        foreach($attendance_list as $row): 
                            $rowDate = date('d F Y', strtotime($row['tanggal']));
                            $status = $row['status'];
                            
                            // Visual Grouping: Tampilkan Header Tanggal jika berbeda dari baris sebelumnya
                            // Ini berguna jika kita melihat data banyak user dalam rentang tanggal
                            if ($currentDateGroup != $rowDate && empty($selected_assistant)): 
                                $currentDateGroup = $rowDate;
                    ?>
                        <tr class="bg-blue-50/50 border-b border-blue-100">
                            <td colspan="5" class="px-5 py-2 text-[10px] font-extrabold text-blue-600 uppercase tracking-widest">
                                <i class="far fa-calendar-alt mr-1"></i> <?= $rowDate ?>
                            </td>
                        </tr>
                    <?php endif; 
                        
                        // Avatar Logic
                        $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($row['name']) . "&background=random";
                        if (!empty($row['photo_profile']) && file_exists('uploads/profile/' . $row['photo_profile'])) {
                            $avatarUrl = BASE_URL . '/uploads/profile/' . $row['photo_profile'];
                        }
                    ?>
                    <tr class="hover:bg-gray-50 transition duration-150 group">
                        <td class="p-5 pl-8">
                            <div class="flex items-center gap-4">
                                <img src="<?= $avatarUrl ?>" class="w-10 h-10 rounded-full border border-gray-200 shadow-sm object-cover">
                                <div>
                                    <div class="font-bold text-gray-800 text-sm"><?= $row['name'] ?></div>
                                    <div class="text-[10px] text-gray-400 font-mono mt-0.5"><?= $row['nim'] ?? '-' ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="p-5 text-center">
                            <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                <?= date('d/m/Y', strtotime($row['tanggal'])) ?>
                            </span>
                        </td>
                        <td class="p-5 text-center">
                            <span class="font-mono text-xs font-bold <?= $row['waktu_presensi'] ? 'text-green-600' : 'text-gray-300' ?>">
                                <?= $row['waktu_presensi'] ? date('H:i', strtotime($row['waktu_presensi'])) : '-' ?>
                            </span>
                        </td>
                        <td class="p-5 text-center">
                            <span class="font-mono text-xs font-bold <?= $row['waktu_pulang'] ? 'text-red-600' : 'text-gray-300' ?>">
                                <?= $row['waktu_pulang'] ? date('H:i', strtotime($row['waktu_pulang'])) : '-' ?>
                            </span>
                        </td>
                        <td class="p-5 text-center">
                            <?php 
                                $statusClass = match($status) {
                                    'Hadir' => 'bg-green-100 text-green-700 border-green-200',
                                    'Sakit' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                    'Izin'  => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'Alpha' => 'bg-red-100 text-red-700 border-red-200',
                                    default => 'bg-gray-100 text-gray-500 border-gray-200'
                                };
                            ?>
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold border <?= $statusClass ?> shadow-sm">
                                <?= $status == '-' ? 'Belum Hadir' : $status ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="5" class="p-12 text-center">
                            <div class="flex flex-col items-center justify-center opacity-50">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500 text-sm font-medium mt-2">Tidak ada data untuk filter yang dipilih.</p>
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