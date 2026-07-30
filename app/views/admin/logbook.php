

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-12 h-[calc(100vh-100px)] flex flex-col">

    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden circuit-pattern shrink-0">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">Monitoring Logbook</h1>
                <p class="text-blue-100 mt-2 text-sm">Pantau, edit, dan reset aktivitas asisten laboratorium.</p>
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

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 flex-1 min-h-0">
        
        <!-- Assistant List Sidebar -->
        <div class="lg:col-span-1 bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col overflow-hidden">
            <div class="p-5 border-b border-gray-100 bg-white sticky top-0 z-10">
                <h3 class="font-extrabold text-gray-700 text-sm uppercase tracking-wide mb-3">Daftar Asisten</h3>
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-3.5 text-gray-400 text-sm"></i>
                    <input type="text" id="searchAssistant" placeholder="Cari nama..." 
                           class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-3 space-y-2 custom-scrollbar" id="assistantList">
                <?php 
                $assistants = $assistants ?? [];
                if (empty($assistants)): ?>
                    <div class="p-6 text-center text-gray-400">
                        <i class="fas fa-inbox text-2xl mb-2 opacity-30"></i>
                        <p class="text-xs font-medium">Tidak ada asisten</p>
                    </div>
                <?php else: ?>
                    <?php foreach($assistants as $ast): ?>
                    <div onclick="loadLogs(<?= $ast['id'] ?>, '<?= htmlspecialchars($ast['name'], ENT_QUOTES) ?>', '<?= $ast['photo_profile'] ?? '' ?>', this)" 
                         class="assistant-card p-3 rounded-xl cursor-pointer flex items-center justify-between group hover:bg-blue-50 hover:border-blue-200 transition border border-transparent" 
                         data-name="<?= htmlspecialchars(strtolower($ast['name']), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <img src="<?= !empty($ast['photo_profile']) ? BASE_URL.'/uploads/profile/'.$ast['photo_profile'] : 'https://ui-avatars.com/api/?name='.urlencode($ast['name']).'&background=random' ?>" 
                                 class="w-9 h-9 rounded-full object-cover border border-gray-200 shadow-sm flex-shrink-0">
                            <div class="min-w-0">
                                <h4 class="font-bold text-gray-800 text-sm leading-tight truncate"><?= htmlspecialchars($ast['name'], ENT_QUOTES, 'UTF-8') ?></h4>
                                <p class="text-[10px] text-gray-500 font-medium mt-0.5"><?= htmlspecialchars($ast['position'] ?? 'Asisten', ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-300 icon-arrow flex-shrink-0 ml-2"></i>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Logbook Table Area -->
        <div class="lg:col-span-3 bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col overflow-hidden relative">
            
            <!-- Empty State -->
            <div id="emptyState" class="absolute inset-0 flex flex-col items-center justify-center text-center bg-white rounded-3xl transition-opacity duration-300 pointer-events-none">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-clipboard-list text-4xl text-gray-300"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Pilih Asisten</h3>
                <p class="text-xs text-gray-500 mt-1 max-w-xs">Klik nama asisten di samping untuk melihat dan mengelola logbook mereka.</p>
            </div>

            <!-- Content -->
            <div id="logContent" class="flex flex-col h-full hidden opacity-0 transition-opacity duration-300">
                <!-- Header -->
                <div class="p-5 bg-gradient-to-r from-blue-600 to-cyan-500 circuit-pattern relative overflow-hidden flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-3">
                        <img id="headerAvatar" src="" class="w-10 h-10 rounded-full border-2 border-white shadow-md object-cover">
                        <div>
                            <p class="text-[10px] font-bold text-blue-100 uppercase tracking-wider">Logbook</p>
                            <h2 id="headerName" class="text-base font-extrabold text-white"></h2>
                        </div>
                    </div>
                    <!-- <button onclick="openEditModal(null, 'add')" class="bg-white hover:bg-blue-50 text-blue-600 px-4 py-2 rounded-lg font-bold text-xs shadow-md transition transform hover:scale-105 flex items-center gap-2 whitespace-nowrap">
                        <i class="fas fa-plus text-xs"></i> Tambah
                    </button> -->
                </div>

                <!-- [BARU – Tahap 35] Live stats bar: dihitung ulang setiap renderTable() -->
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

                <!-- Table -->
                <div class="flex-1 overflow-x-auto overflow-y-auto custom-scrollbar min-h-0">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead class="bg-gray-50 sticky top-0 z-10 border-b border-gray-200">
                            <tr class="text-xs font-bold text-gray-600 uppercase tracking-wider">
                                <th class="px-4 py-3 w-[140px] whitespace-nowrap">Tanggal & Status</th>
                                <th class="px-4 py-3 w-[110px] text-center whitespace-nowrap">Waktu</th>
                                <th class="px-4 py-3 w-[100px] text-center whitespace-nowrap">Datang</th>
                                <th class="px-4 py-3 w-[100px] text-center whitespace-nowrap">Pulang</th>
                                <th class="px-4 py-3 w-[90px] text-center whitespace-nowrap">Izin</th>
                                <th class="px-4 py-3 min-w-[200px]">Aktivitas</th>
                                <th class="px-4 py-3 w-[80px] text-center whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody" class="divide-y divide-gray-100"></tbody>
                    </table>
                    <!-- Pagination logbook — hanya muncul saat data lebih dari 1 halaman -->
                    <div id="logPaginationBar" class="hidden border-t border-gray-100 bg-gray-50 px-4"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="logModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeLogModal()"></div>
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl relative z-10 overflow-hidden transform scale-100 transition-all">
        <div class="bg-gradient-to-r from-blue-700 to-cyan-600 circuit-pattern relative overflow-hidden px-6 py-4 flex justify-between items-center text-white">
            <h3 class="font-bold text-lg" id="modalTitle">Edit Logbook</h3>
            <button onclick="closeLogModal()" class="text-blue-100 hover:text-white transition"><i class="fas fa-times"></i></button>
        </div>
        <form id="logForm" class="p-6 space-y-4" enctype="multipart/form-data">
            <input type="hidden" name="user_id" id="inputUserId">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Tanggal</label>
                    <input type="date" name="date" id="inputDate" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Status</label>
                    <select name="status" id="inputStatus" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 outline-none transition" onchange="toggleTimeFields()">
                        <option value="Hadir">Hadir</option>
                        <option value="Izin">Izin</option>
                        <option value="Sakit">Sakit</option>
                        <option value="Alpha">Tidak Hadir</option>
                    </select>
                </div>
            </div>

            <div id="timeFields" class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Jam Masuk</label>
                    <input type="time" name="time_in" id="inputIn" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Jam Pulang</label>
                    <input type="time" name="time_out" id="inputOut" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 outline-none transition">
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4" id="uploadContainer">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5" id="labelProofMain">Upload Bukti (Datang/Izin)</label>
                    <input type="file" name="proof_file" id="inputProof" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-xl text-xs">
                </div>
                
                <div id="proofOutContainer">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Upload Bukti Pulang</label>
                    <input type="file" name="proof_file_out" id="inputProofOut" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-xl text-xs">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Aktivitas / Keterangan</label>
                <textarea name="activity" id="inputActivity" rows="3" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 outline-none transition"></textarea>
            </div>

            <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg transition transform active:scale-95">Simpan Perubahan</button>
        </form>
    </div>
</div>

<div id="photoModal" class="hidden fixed inset-0 z-[70] flex items-center justify-center p-4 bg-black/95 backdrop-blur-md" onclick="closePhoto()">
    <div class="relative max-w-2xl w-full" onclick="event.stopPropagation()">
        <button onclick="closePhoto()" class="absolute -top-12 right-0 text-white hover:text-red-400 transition text-3xl"><i class="fas fa-times"></i></button>
        <div class="bg-white rounded-lg overflow-hidden shadow-2xl">
            <div class="p-3 bg-gray-100 border-b flex justify-between items-center">
                <span id="proofTitle" class="font-bold text-gray-700 text-sm uppercase">Bukti</span>
                <a id="downloadLink" href="#" download class="text-blue-600 hover:underline text-xs font-bold"><i class="fas fa-download"></i> Unduh</a>
            </div>
            <div class="flex justify-center bg-gray-50 p-2">
                <img id="modalImg" src="" class="hidden max-h-[70vh] object-contain">
                <iframe id="modalFrame" src="" class="hidden w-full h-[70vh]"></iframe>
            </div>
        </div>
    </div>
</div>

<div id="resetModal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm transition-opacity" onclick="closeResetModal()"></div>
    
    <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl relative z-10 p-6 text-center transform transition-all scale-100">
        <div class="w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-exclamation-triangle text-2xl"></i>
        </div>
        
        <h3 class="text-xl font-bold text-gray-800 mb-2">Konfirmasi Tindakan</h3>
        <p class="text-xs text-gray-500 mb-6">Pilih jenis penghapusan data:</p>
        
        <div class="space-y-3 mb-6 text-left">
            <label class="flex items-center p-3 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition group">
                <input type="radio" name="resetMode" value="partial" checked class="w-4 h-4 text-blue-600 focus:ring-blue-500">
                <div class="ml-3">
                    <span class="block text-sm font-bold text-gray-700 group-hover:text-blue-700">Reset Logbook Saja</span>
                    <span class="block text-[10px] text-gray-400">Menghapus isi laporan, status tetap "Hadir".</span>
                </div>
            </label>
            
            <label class="flex items-center p-3 border border-red-200 bg-red-50/30 rounded-xl cursor-pointer hover:bg-red-50 transition group">
                <input type="radio" name="resetMode" value="full" class="w-4 h-4 text-red-600 focus:ring-red-500">
                <div class="ml-3">
                    <span class="block text-sm font-bold text-red-700">Hapus Total (Alpha)</span>
                    <span class="block text-[10px] text-red-400">Menghapus data presensi sepenuhnya.</span>
                </div>
            </label>
        </div>
        
        <div class="flex gap-3">
            <button onclick="closeResetModal()" class="flex-1 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold hover:bg-gray-50 transition">Batal</button>
            <button id="confirmResetBtn" class="flex-1 py-3 rounded-xl bg-red-600 text-white font-bold shadow-lg hover:bg-red-700 transition flex items-center justify-center gap-2">
                <span>Proses</span>
            </button>
        </div>
    </div>
</div>

