<!-- FullCalendar loaded via vendor_js in controller -->

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-12">

    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden circuit-pattern">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0 text-center md:text-left">
                <h1 class="text-3xl font-extrabold tracking-tight">Manajemen Jadwal</h1>
                <p class="text-slate-300 mt-2 text-sm">Kelola dan monitoring jadwal Anda.</p>
            </div>
            <div class="text-center md:text-right bg-white/10 p-3 rounded-2xl backdrop-blur-sm border border-white/20">
                <p class="text-[10px] font-bold text-slate-300 uppercase tracking-widest mb-1">Waktu Sistem</p>
                <h2 id="liveDate" class="text-xl font-bold font-mono"><?= date('d F Y') ?></h2>
                <p class="text-sm opacity-90 font-mono mt-1">
                    <span id="liveTime" class="bg-slate-900/50 px-2 py-0.5 rounded"><?= date('H:i:s') ?></span> WITA
                </p>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 lg:h-[850px] h-auto">
        
        <div class="w-full lg:w-72 space-y-5 flex flex-col h-full">

            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200 shrink-0">
                <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wide mb-4 flex items-center gap-2">
                    <i class="fas fa-palette text-blue-500"></i> Kategori
                </h3>
                <div class="grid grid-cols-2 gap-2">
                    <div class="legend-chip bg-gray-800 text-white"><i class="fas fa-building"></i><span>Umum</span></div>
                    <div class="legend-chip bg-blue-50 text-blue-600 border-blue-100"><i class="fas fa-user-tie"></i><span>Asisten</span></div>
                    <div class="legend-chip bg-orange-50 text-orange-600 border-orange-100"><i class="fas fa-broom"></i><span>Piket</span></div>
                    <div class="legend-chip bg-green-50 text-green-600 border-green-100"><i class="fas fa-graduation-cap"></i><span>Kuliah</span></div>
                </div>
            </div>
            
            <button onclick="openFormModal('add')" class="w-full bg-white text-blue-600 px-4 py-3.5 rounded-xl font-bold shadow-sm hover:shadow-md hover:bg-blue-50 transition transform hover:scale-[1.02] flex items-center justify-center gap-2 border border-blue-100 shrink-0">
                <i class="fas fa-plus-circle text-lg"></i> Tambah Jadwal Kuliah
            </button>

            <div class="bg-white p-5 rounded-3xl shadow-sm border border-gray-200 shrink-0 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                    <h3 class="font-extrabold text-gray-700 text-xs uppercase tracking-wide"><i class="fas fa-filter mr-1"></i> Filter Jadwal</h3>
                    <button onclick="resetFilters()" class="text-[10px] text-blue-500 hover:text-blue-700 font-bold">Reset</button>
                </div>
                
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block">Kategori</label>
                    <select id="filterCategory" onchange="renderCustomLayers()" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-100 cursor-pointer">
                        <option value="all">Semua Kategori</option>
                        <option value="umum">Umum (Lab)</option>
                        <option value="asisten">Asisten (Saya)</option>
                        <option value="piket">Piket (Saya)</option>
                        <option value="kuliah">Kuliah (Saya)</option>
                    </select>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block">Hari</label>
                    <select id="filterDay" onchange="renderCustomLayers()" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-100 cursor-pointer">
                        <option value="all">Semua Hari</option>
                        <option value="1">Senin</option>
                        <option value="2">Selasa</option>
                        <option value="3">Rabu</option>
                        <option value="4">Kamis</option>
                        <option value="5">Jumat</option>
                        <option value="6">Sabtu</option>
                        <option value="7">Minggu</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block">Mulai</label>
                        <input type="time" id="filterStart" onchange="renderCustomLayers()" class="w-full px-2 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block">Selesai</label>
                        <input type="time" id="filterEnd" onchange="renderCustomLayers()" class="w-full px-2 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col overflow-hidden flex-1 min-h-0">
                <div class="p-5 border-b border-gray-100 bg-white sticky top-0 z-20">
                    <h3 class="font-extrabold text-gray-700 text-xs uppercase tracking-wide flex items-center gap-2">
                        <i class="fas fa-calendar-alt text-blue-500"></i> Jadwal Akan Datang
                    </h3>
                </div>
                
                <div class="flex-1 overflow-y-auto p-3 space-y-2 custom-scrollbar" id="upcomingListContainer">
                    <div class="text-center py-8 text-gray-400 text-xs italic">Memuat data...</div>
                </div>
            </div>
        </div>

        <div class="flex-1 bg-white rounded-3xl shadow-sm border border-gray-200 p-6 relative overflow-hidden">
            <div id='calendar' class="h-full"></div>
        </div>
    </div>
</div>

<div id="dayDetailModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="detailBackdrop" onclick="closeDayModal()"></div>
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col max-h-[85vh]" id="detailContent">
        <div class="bg-gradient-to-r from-blue-700 to-cyan-600 circuit-pattern relative overflow-hidden px-6 py-5 flex justify-between items-center shrink-0">
            <div><p class="text-blue-100 text-[10px] font-extrabold uppercase tracking-widest mb-1">Detail Kegiatan</p><h3 id="modalDateTitle" class="text-xl font-extrabold text-white"></h3></div>
            <button onclick="closeDayModal()" class="w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 text-blue-100 hover:text-white transition flex items-center justify-center"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-0 overflow-y-auto flex-1 bg-gray-50 custom-scrollbar" id="modalListContainer"></div>
        <div class="p-4 border-t border-gray-100 bg-white shrink-0">
            <button onclick="openFormModal('add')" class="w-full py-3 rounded-xl bg-blue-600 text-white font-bold shadow-lg shadow-blue-500/30 hover:bg-blue-700 transition flex items-center justify-center gap-2">
                <i class="fas fa-plus"></i> Tambah Jadwal Kuliah
            </button>
        </div>
    </div>
</div>

<div id="formModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="formBackdrop" onclick="closeFormModal()"></div>
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col" id="formContent">
        <div class="bg-gradient-to-r from-blue-700 to-cyan-600 circuit-pattern relative overflow-hidden px-6 py-4 flex justify-between items-center">
            <h3 class="font-bold text-lg text-white" id="formModalTitle">Jadwal Kuliah</h3>
            <button onclick="closeFormModal()" class="text-blue-100 hover:text-white transition"><i class="fas fa-times"></i></button>
        </div>
        <form id="scheduleForm" method="POST" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto custom-scrollbar">
            <input type="hidden" name="id_schedule" id="inputId"> 
            <input type="hidden" name="type" value="kuliah">
            
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Mata Kuliah</label>
                <input type="text" name="title" id="inputTitle" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-green-100" placeholder="Contoh: Pemrograman Web">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Kelas</label>
                    <select name="kelas" id="inputKelas" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-green-100 cursor-pointer">
                        <option value="" disabled selected>Pilih Kelas</option>
                        <?php 
                        $listKelas = ['A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'A7', 'A8', 'A9', 'A10',
                                        'B1', 'B2', 'B3', 'B4', 'B5', 'C1', 'C2', 'C3', 'C4', 'C5'];
                        foreach ($listKelas as $k): 
                        ?>
                            <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>">Kelas <?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
        
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Ruangan</label>
                    <select name="location" id="inputLocation" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-green-100 cursor-pointer">
                        <option value="" disabled selected>Pilih Ruangan</option>
                        <optgroup label="Ruangan Kelas">
                            <?php

                            $listRuangan = [
                            '301','302','303','304',
                            '306','307','308','309',
                            '401','402','403','404',
                            '406','407','408','409'
                            ];

                            foreach($listRuangan as $r):?>
                            <option value="<?= htmlspecialchars($r, ENT_QUOTES, 'UTF-8') ?>">Ruang <?= htmlspecialchars($r, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </optgroup>

                        <optgroup label="Laboratorium">
                            <!-- [BARU - Modul Lab] dropdown dari tabel master `lab` -->
                            <?php if (!empty($lab_list) && is_array($lab_list)): ?>
                                <?php foreach ($lab_list as $lab): ?>
                                <option value="<?= htmlspecialchars($lab['nama_lab']) ?>"><?= htmlspecialchars($lab['nama_lab']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </optgroup>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Dosen Pengampu</label>
                <!-- [BARU - Modul Dosen] dropdown dari tabel master `dosen`,
                     + opsi "Tambah Dosen Baru" yang membuka input teks
                     (name="dosen_baru") jika dosen belum ada di daftar. -->
                <select name="id_dosen" id="inputIdDosen" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-green-100" onchange="handleDosenChange(this, 'user')">
                    <option value="" disabled selected>-- Pilih Dosen --</option>
                    <?php if (!empty($dosen_list) && is_array($dosen_list)): ?>
                        <?php foreach ($dosen_list as $d): ?>
                            <option value="<?= $d['id_dosen'] ?>"><?= htmlspecialchars($d['nama_dosen']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <option value="__new__">+ Tambah Dosen Baru...</option>
                </select>
                <input type="text" name="dosen_baru" id="inputDosenBaru" class="hidden w-full mt-2 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-green-100" placeholder="Nama Dosen Baru (lengkap dengan gelar)">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Tgl Mulai</label><input type="date" name="date" id="inputDate" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-green-100"></div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Mode Perulangan</label>
                    <select name="model_perulangan" id="inputRepeatModel" class="w-full px-3 py-2.5 bg-white border border-gray-200 rounded-xl text-sm" onchange="handleRepeatChange()">
                        <option value="sekali">Sekali</option>
                        <option value="mingguan">Mingguan</option>
                        <option value="rentang">Berurutan</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Jam Mulai</label><input type="time" name="start_time" id="inputStart" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-green-100"></div>
                <div><label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Jam Selesai</label><input type="time" name="end_time" id="inputEnd" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-green-100"></div>
            </div>
            
            <div id="endDateContainer" class="hidden">
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Sampai Tanggal</label>
                <input type="date" name="end_date_repeat" id="inputEndDateRepeat" class="w-full px-3 py-2.5 bg-white border border-gray-200 rounded-xl text-sm">
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold shadow-lg shadow-blue-500/30 transition transform hover:-translate-y-0.5">Simpan Jadwal</button>
        </form>
    </div>
</div>

<div id="customAlertModal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity opacity-0" id="alertBackdrop"></div>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative z-10 overflow-hidden transform scale-90 opacity-0 transition-all duration-300 flex flex-col items-center p-6 text-center" id="alertContent">
        <div id="alertIconBg" class="w-16 h-16 rounded-full flex items-center justify-center mb-4"><i id="alertIcon" class="fas text-3xl"></i></div>
        <h3 id="alertTitle" class="text-xl font-extrabold text-gray-800 mb-2"></h3><p id="alertMessage" class="text-sm text-gray-500 mb-6 px-2"></p>
        <button onclick="closeCustomAlert()" class="w-full py-3 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02]" id="alertBtn">OK</button>
    </div>
</div>
<div id="customConfirmModal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity opacity-0" id="confirmBackdrop"></div>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative z-10 overflow-hidden transform scale-90 opacity-0 transition-all duration-300 flex flex-col items-center p-6 text-center" id="confirmContent">
        <div class="w-16 h-16 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center mb-4"><i class="fas fa-exclamation-triangle text-3xl"></i></div>
        <h3 class="text-xl font-extrabold text-gray-800 mb-2">Hapus?</h3><p class="text-sm text-gray-500 mb-6 px-2">Jadwal ini akan dihapus permanen.</p>
        <div class="flex gap-3 w-full"><button onclick="closeCustomConfirm()" class="flex-1 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold hover:bg-gray-50 transition">Batal</button><button id="confirmYesBtn" class="flex-1 py-3 rounded-xl bg-red-600 text-white font-bold shadow-lg hover:bg-red-700 transition">Ya, Hapus</button></div>
    </div>
</div>

<?php if (isset($_SESSION['flash'])): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() { setTimeout(() => { showCustomAlert(<?= json_encode($_SESSION['flash']['type']) ?>, <?= json_encode($_SESSION['flash']['title']) ?>, <?= json_encode($_SESSION['flash']['message']) ?>); }, 300); });
</script>
<?php unset($_SESSION['flash']); endif; ?>

