

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-12 h-[calc(100vh-100px)] flex flex-col">

    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden circuit-pattern shrink-0">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">Monitoring Logbook</h1>
                <p class="text-blue-100 mt-2 text-sm">Pantau aktivitas harian seluruh asisten.</p>
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

    <div class="flex flex-col lg:flex-row gap-6 flex-1 overflow-hidden">
        
        <div class="w-full lg:w-1/3 bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col overflow-hidden">
            <div class="p-5 border-b border-gray-100 bg-white sticky top-0 z-10">
                <h3 class="font-extrabold text-gray-700 text-sm uppercase tracking-wide mb-4">Data Asisten</h3>
                <div class="relative group">
                    <i class="fas fa-search absolute left-4 top-3.5 text-gray-400 text-sm"></i>
                    <input type="text" id="searchAssistant" placeholder="Cari nama asisten..." 
                           class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar" id="assistantList">
                <?php foreach($assistants as $ast): ?>
                <?php
                    // [PERBAIKAN] Sebelumnya hanya cek !empty(photo_profile) -
                    // kalau nilainya ADA tapi file fisiknya sudah tidak ada
                    // (hilang/dipindah), <img> menampilkan ikon rusak alih-alih
                    // fallback. Disamakan dengan pola carousel asisten dashboard
                    // admin (admin/dashboard.php) yang sudah benar: cek
                    // file_exists() juga, baru fallback ke avatar inisial nama.
                    $astPhotoPath = 'uploads/profile/' . ($ast['photo_profile'] ?? '');
                    $astPhotoUrl = (!empty($ast['photo_profile']) && file_exists($astPhotoPath))
                        ? BASE_URL . '/uploads/profile/' . $ast['photo_profile']
                        : 'https://ui-avatars.com/api/?name=' . urlencode($ast['name']) . '&background=random';
                ?>
                <div onclick="loadLogs(<?= $ast['id'] ?>, '<?= htmlspecialchars($ast['name'], ENT_QUOTES) ?>', '<?= $ast['photo_profile'] ?? '' ?>', this)"
                     class="assistant-card p-3 rounded-2xl cursor-pointer flex items-center justify-between group"
                     data-name="<?= htmlspecialchars(strtolower($ast['name']), ENT_QUOTES, 'UTF-8') ?>">

                    <div class="flex items-center gap-3">
                        <img src="<?= $astPhotoUrl ?>"
                             class="w-10 h-10 rounded-full object-cover border border-gray-200 shadow-sm">
                        <div>
                            <h4 class="font-bold text-gray-800 text-sm leading-tight"><?= htmlspecialchars($ast['name'], ENT_QUOTES, 'UTF-8') ?></h4>
                            <p class="text-[10px] text-gray-500 font-medium mt-0.5"><?= htmlspecialchars($ast['position'] ?? 'Anggota', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                    
                    <i class="fas fa-chevron-right text-gray-300 icon-arrow"></i>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="w-full lg:w-2/3 bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col overflow-hidden relative">
            
            <div id="emptyState" class="absolute inset-0 flex flex-col items-center justify-center text-center bg-white z-20 transition-opacity duration-300">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4 animate-bounce">
                    <i class="fas fa-eye text-3xl text-gray-300"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Pilih Asisten</h3>
                <p class="text-sm text-gray-500 mt-1">Klik nama asisten di samping untuk melihat logbook.</p>
            </div>

            <div id="logContent" class="flex flex-col h-full hidden opacity-0 transition-opacity duration-300">
                <div class="p-6 bg-gradient-to-r from-blue-600 to-cyan-500 circuit-pattern relative overflow-hidden flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <img id="headerAvatar" src="" class="w-12 h-12 rounded-full border-2 border-white shadow-md object-cover">
                        <div>
                            <p class="text-[10px] font-bold text-blue-100 uppercase tracking-widest">Logbook Asisten</p>
                            <h2 id="headerName" class="text-xl font-extrabold text-white"></h2>
                        </div>
                    </div>
                    </div>

                <div id="liveStatsBar" class="hidden px-4 py-2.5 bg-gray-50 border-b border-gray-100 flex items-center gap-3 text-xs shrink-0">
                    <span class="text-gray-400 font-bold uppercase tracking-wide text-[10px]">Ringkasan:</span>
                    <span class="flex items-center gap-1 font-bold text-green-600">
                        <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
                        Hadir: <span id="countHadir" class="tabular-nums">0</span>
                    </span>
                    <span class="flex items-center gap-1 font-bold text-yellow-600">
                        <span class="w-2 h-2 rounded-full bg-yellow-400 inline-block"></span>
                        Izin/Sakit: <span id="countIzin" class="tabular-nums">0</span>
                    </span>
                    <span class="flex items-center gap-1 font-bold text-red-600">
                        <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
                        Alpha: <span id="countAlpha" class="tabular-nums">0</span>
                    </span>
                </div>

                <div class="flex-1 overflow-y-auto p-0 custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-white sticky top-0 z-10 shadow-sm text-xs font-bold text-gray-400 uppercase">
                            <tr>
                                <th class="p-5 border-b border-gray-100 w-4">Sts</th>
                                <th class="p-5 border-b border-gray-100">Tanggal</th>
                                <th class="p-5 border-b border-gray-100 text-center">Waktu</th>
                                <th class="p-5 border-b border-gray-100 text-center">Bukti Datang</th>
                                <th class="p-5 border-b border-gray-100 text-center">Bukti Pulang</th>
                                <th class="p-5 border-b border-gray-100 w-10">Aktivitas</th>
                                </tr>
                        </thead>
                        <tbody id="logsTableBody" class="divide-y divide-gray-50 text-sm text-gray-700">
                        </tbody>
                    </table>
                    <div id="logPaginationBar" class="hidden border-t border-gray-100 bg-gray-50 px-4"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="photoModal" class="hidden fixed inset-0 z-[99] flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm" onclick="closePhoto()">
    <div class="relative max-w-2xl w-full" onclick="event.stopPropagation()">
        <button onclick="closePhoto()" class="absolute -top-12 right-0 text-white hover:text-red-400 transition text-3xl"><i class="fas fa-times"></i></button>
        <div class="bg-white rounded-lg overflow-hidden shadow-2xl">
            <div class="p-3 bg-gray-100 border-b flex justify-between items-center">
                <span id="proofTitle" class="font-bold text-gray-700 text-sm uppercase">Bukti</span>
                <a id="downloadLink" href="#" download class="text-blue-600 hover:underline text-xs font-bold"><i class="fas fa-download"></i> Unduh</a>
            </div>
            <img id="modalImg" src="" class="w-full h-auto max-h-[70vh] object-contain bg-gray-50">
        </div>
    </div>
</div>

