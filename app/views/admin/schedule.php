<?php
$start_date = !empty($start_date) ? $start_date : date('Y-m-01');
$end_date = !empty($end_date) ? $end_date : date('Y-m-t');
$assistant_id = $assistant_id ?? '';
$schedule_type = $schedule_type ?? '';
$sort_by = $sort_by ?? 'hari_waktu';
$assistants = $assistants ?? [];
?>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-12">

    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden circuit-pattern">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0 text-center md:text-left">
                <h1 class="text-3xl font-extrabold tracking-tight">Manajemen Jadwal</h1>
                <p class="text-slate-300 mt-2 text-sm">Kelola dan monitoring jadwal kegiatan laboratorium.</p>
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

    <!-- Filter & Export Cards Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-enter">
        <!-- Card 1: Filter Data -->
        <div class="lg:col-span-7 bg-white p-6 rounded-3xl shadow-sm border border-gray-200 flex flex-col lg:flex-row items-center justify-between gap-4 relative overflow-hidden">
            <div class="absolute right-0 top-0 h-full w-16 bg-blue-50/50 skew-x-12 transform origin-bottom-left"></div>
            
            <div class="relative z-10 flex items-center gap-2 flex-shrink-0 self-start lg:self-center">
                <div class="p-2 bg-blue-100 text-blue-600 rounded-lg">
                    <i class="fas fa-filter"></i>
                </div>
                <h3 class="font-extrabold text-gray-700">Filter Data</h3>
            </div>

            <div class="relative z-10 flex flex-col gap-3 w-full lg:w-auto">
                <div class="grid grid-cols-2 gap-2 w-full">
                    <div class="flex flex-col bg-gray-50 px-3 py-1.5 rounded-xl border border-gray-200">
                        <span class="text-[9px] text-gray-400 font-bold uppercase leading-none">Dari</span>
                        <input type="date" name="start_date" value="<?= $start_date ?>" max="<?= $end_date ?>" onchange="handleStartDateChange(this.value)" class="bg-transparent border-none focus:ring-0 text-xs font-bold text-gray-600 outline-none p-0 w-full cursor-pointer mt-0.5">
                    </div>
                    <div class="flex flex-col bg-gray-50 px-3 py-1.5 rounded-xl border border-gray-200">
                        <span class="text-[9px] text-gray-400 font-bold uppercase leading-none">Sampai</span>
                        <input type="date" name="end_date" value="<?= $end_date ?>" min="<?= $start_date ?>" onchange="handleEndDateChange(this.value)" class="bg-transparent border-none focus:ring-0 text-xs font-bold text-gray-600 outline-none p-0 w-full cursor-pointer mt-0.5">
                    </div>
                </div>

                <input type="hidden" name="sort_by" value="<?= $sort_by ?>">

                <div class="flex gap-2 w-full">
                    <select name="assistant_id_select" onchange="applyFilterFromDropdown(this.value)" class="flex-1 bg-gray-50 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl p-2.5 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer">
                        <option value="all">Semua Asisten</option>
                        <?php if (!empty($assistants) && is_array($assistants)): ?>
                            <?php foreach($assistants as $ast): ?>
                                <option value="<?= $ast['id_profil'] ?>" <?= ($assistant_id == $ast['id_profil']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ast['nama'] ?? $ast['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>

                    <select name="schedule_type" onchange="fetchFilteredSchedules()" class="flex-1 bg-gray-50 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl p-2.5 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer">
                        <option value="">Semua Jenis</option>
                        <option value="umum" <?= ($schedule_type == 'umum') ? 'selected' : '' ?>>Umum (Lab)</option>
                        <option value="asisten" <?= ($schedule_type == 'asisten') ? 'selected' : '' ?>>Asisten Lab</option>
                        <option value="piket" <?= ($schedule_type == 'piket') ? 'selected' : '' ?>>Piket</option>
                        <option value="kuliah" <?= ($schedule_type == 'kuliah') ? 'selected' : '' ?>>Kuliah Asisten</option>
                    </select>

                    <button onclick="fetchFilteredSchedules()" class="bg-blue-600 text-white px-4 py-2.5 rounded-xl flex items-center justify-center hover:bg-blue-700 transition shadow-md font-bold text-xs" title="Terapkan Filter">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Card 2: Ekspor Laporan -->
        <div class="lg:col-span-5 bg-white p-6 rounded-3xl shadow-sm border border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4 relative overflow-hidden">
            <div class="absolute right-0 top-0 h-full w-16 bg-green-50/50 skew-x-12 transform origin-bottom-left"></div>
            
            <div class="relative z-10 flex items-center gap-2 self-start sm:self-center">
                <div class="p-2 bg-green-100 text-green-600 rounded-lg">
                    <i class="fas fa-file-export"></i>
                </div>
                <h3 class="font-extrabold text-gray-700">Ekspor Laporan</h3>
            </div>
            <div class="relative z-10 flex gap-3 w-full sm:w-auto justify-end">
                <?php 
                    $roleLink = strtolower(str_replace(' ', '', $_SESSION['role'])); 
                    $qs = "start_date=" . $start_date . "&end_date=" . $end_date . "&assistant_id=" . $assistant_id . "&schedule_type=" . $schedule_type . "&sort_by=" . $sort_by;
                ?>
                <a id="exportPdfBtn" href="<?= BASE_URL ?>/<?= $roleLink ?>/exportSchedulePdf?<?= $qs ?>" class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-5 py-2.5 bg-red-50 text-red-600 border border-red-100 rounded-xl font-bold text-xs hover:bg-red-600 hover:text-white hover:shadow-lg transition group">
                    <i class="fas fa-file-pdf text-lg group-hover:scale-110 transition"></i> <span>PDF</span>
                </a>
                <a id="exportExcelBtn" href="<?= BASE_URL ?>/<?= $roleLink ?>/exportScheduleCsv?<?= $qs ?>" class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-5 py-2.5 bg-green-50 text-green-600 border border-green-100 rounded-xl font-bold text-xs hover:bg-green-600 hover:text-white hover:shadow-lg transition group">
                    <i class="fas fa-file-excel text-lg group-hover:scale-110 transition"></i> <span>Excel</span>
                </a>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 lg:h-[850px] h-auto">
        <div class="w-full lg:w-72 space-y-6 flex flex-col h-full shrink-0">
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200 shrink-0">
                <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wide mb-4">Kategori</h3>
                <div class="space-y-3 text-sm font-medium text-gray-600">
                    <div class="flex items-center"><span class="legend-dot bg-gray-800"></span> Umum (Lab)</div>
                    <div class="flex items-center"><span class="legend-dot bg-blue-500"></span> Asisten Lab</div>
                    <div class="flex items-center"><span class="legend-dot bg-orange-500"></span> Piket</div>
                    <div class="flex items-center"><span class="legend-dot bg-green-500"></span> Kuliah Asisten</div>
                </div>
            </div>

            <button onclick="openFormModal('add')" class="w-full bg-white text-blue-600 px-4 py-3.5 rounded-xl font-bold shadow-sm hover:shadow-md hover:bg-blue-50 transition transform hover:scale-[1.02] flex items-center justify-center gap-2 border border-blue-100 shrink-0">
                <i class="fas fa-plus-circle text-lg"></i> Tambah Jadwal
            </button>

            <!-- Filter Asisten Sidebar -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col overflow-hidden flex-1 min-h-0">
                <div class="p-5 border-b border-gray-100 bg-white sticky top-0 z-20">
                    <h3 class="font-extrabold text-gray-700 text-sm uppercase tracking-wide mb-3">Filter Asisten</h3>
                    <input type="text" id="searchFilterInput" placeholder="Cari nama..." class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div class="flex-1 overflow-y-auto p-3 space-y-2 custom-scrollbar" id="filterListContainer">
                    <div onclick="applyFilter('all')" id="filter-all" class="assistant-card filter-item filter-active p-3 rounded-2xl cursor-pointer flex items-center gap-3 border border-transparent hover:bg-gray-50 group">
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0"><i class="fas fa-layer-group text-sm"></i></div>
                        <div><h4 class="font-bold text-gray-800 text-sm">Semua Jadwal</h4><p class="text-[10px] text-gray-500">Gabungan data</p></div>
                        <div class="ml-auto opacity-0 check-icon text-blue-600 transition-opacity"><i class="fas fa-check-circle"></i></div>
                    </div>
                    <?php if (!empty($assistants) && is_array($assistants)): ?>
                        <?php foreach($assistants as $ast): ?>
                        <div onclick="applyFilter('<?= $ast['id_profil'] ?>')" id="filter-<?= $ast['id_profil'] ?>" class="assistant-card filter-item p-3 rounded-2xl cursor-pointer flex items-center gap-3 border border-transparent hover:bg-gray-50 group" data-name="<?= strtolower($ast['nama'] ?? $ast['name'] ?? '') ?>">
                            <div class="relative shrink-0"><img src="<?= !empty($ast['photo_profile']) ? BASE_URL.'/uploads/profile/'.$ast['photo_profile'] : 'https://ui-avatars.com/api/?name='.urlencode($ast['nama'] ?? $ast['name'] ?? '').'&background=random' ?>" class="w-10 h-10 rounded-full object-cover border border-gray-100 shadow-sm"></div>
                            <div class="min-w-0 flex-1"><h4 class="font-bold text-gray-800 text-sm truncate"><?= htmlspecialchars($ast['nama'] ?? $ast['name'] ?? '') ?></h4><p class="text-[10px] text-gray-500 truncate">Asisten</p></div>
                            <div class="ml-auto opacity-0 check-icon text-blue-600 transition-opacity"><i class="fas fa-check-circle"></i></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
                <i class="fas fa-plus"></i> Tambah di Tanggal Ini
            </button>
        </div>
    </div>
</div>

<div id="formModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="formBackdrop" onclick="closeFormModal()"></div>
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col" id="formContent">
        <div class="bg-gradient-to-r from-blue-700 to-cyan-600 circuit-pattern relative overflow-hidden px-6 py-4 flex justify-between items-center">
            <h3 class="font-bold text-lg text-white" id="formModalTitle">Form Jadwal</h3>
            <button onclick="closeFormModal()" class="text-blue-100 hover:text-white transition"><i class="fas fa-times"></i></button>
        </div>

        <!-- [BARU - Modul 5 V3] Banner status sinkronisasi Google Calendar,
             tampil hanya saat edit jadwal yang sync_status='failed' -->
        <div id="syncStatusBanner" class="hidden mx-6 mt-4 p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-xs flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <i class="fas fa-triangle-exclamation"></i>
                <span>Sinkronisasi ke Google Calendar gagal untuk jadwal ini.</span>
            </div>
            <button type="button" onclick="retrySync()" id="btnRetrySync" class="shrink-0 bg-amber-600 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg hover:bg-amber-700 transition whitespace-nowrap">
                <i class="fas fa-rotate-right"></i> Coba Sync Ulang
            </button>
        </div>

        <form id="scheduleForm" method="POST" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto custom-scrollbar">
            <input type="hidden" name="id_schedule" id="inputId"> 
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Tipe Jadwal</label>
                    <select name="type" id="inputType" class="w-full pl-3 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-100 transition" onchange="handleTypeChange()">
                        <option value="umum">Umum (Lab)</option>
                        <option value="asisten">Asisten Lab</option>
                        <option value="piket">Piket</option>
                        <!-- <option value="class">Kuliah Asisten</option> -->
                        </select>
                </div>
                <div id="userSelectContainer" class="hidden">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Asisten</label>
                    <select name="user_id" id="inputUser" class="w-full pl-3 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                        <option value="" disabled selected>Pilih Asisten</option>
                        <?php if (!empty($assistants) && is_array($assistants)): ?>
                            <?php foreach($assistants as $ast): ?>
                                <option value="<?= $ast['id_profil'] ?>"><?= htmlspecialchars($ast['name']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Nama Kegiatan / Mata Kuliah</label>
                <input type="text" name="title" id="inputTitle" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 transition" placeholder="Contoh: Jaga Lab Sesi 1">
            </div>

            <div id="asistenFields" class="hidden grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Dosen</label>
                    <!-- [BARU - Modul Dosen] dropdown dari tabel master `dosen`,
                         + opsi "Tambah Dosen Baru" yang membuka input teks
                         (name="dosen_baru") jika dosen belum ada di daftar. -->
                    <select name="id_dosen" id="inputIdDosen" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100" onchange="handleDosenChange(this, 'admin')">
                        <option value="">Pilih Dosen</option>
                        <?php if (!empty($dosen_list) && is_array($dosen_list)): ?>
                            <?php foreach ($dosen_list as $d): ?>
                                <option value="<?= $d['id_dosen'] ?>"><?= htmlspecialchars($d['nama_dosen']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <option value="__new__">+ Tambah Dosen Baru...</option>
                    </select>
                    <input type="text" name="dosen_baru" id="inputDosenBaru" class="hidden w-full mt-2 px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100" placeholder="Nama Dosen Baru (lengkap dengan gelar)">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Kelas</label>
                    <select name="kelas" id="inputKelas" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-green-100 cursor-pointer">
                        <option value="" disabled selected>Pilih Kelas</option>
                        <?php 
                        $listKelas = ['A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'A7', 'A8', 'A9', 'A10',
                                        'B1', 'B2', 'B3', 'B4', 'B5', 'C1', 'C2', 'C3', 'C4', 'C5'];
                        foreach ($listKelas as $k): 
                        ?>
                            <option value="<?= $k ?>">Kelas <?= $k ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase">Lokasi</label>
                        <!-- [BARU - Modul Lab] toggle khusus Jadwal Umum: defaultnya
                             dropdown laboratorium (dari tabel `lab`), bisa diganti ke
                             input teks bebas untuk kegiatan di luar laboratorium.
                             Disembunyikan (style.display) untuk tipe Asisten/Piket -
                             tipe tersebut SELALU memilih dari daftar laboratorium. -->
                        <label id="locationToggleWrap" class="items-center gap-1.5 text-[10px] font-bold text-gray-400 uppercase cursor-pointer" style="display:none;">
                            <input type="checkbox" id="inputLocationToggle" onchange="handleLocationToggle()" class="rounded text-blue-600 focus:ring-2 focus:ring-blue-100">
                            Lokasi Luar Lab
                        </label>
                    </div>
                    <!-- [BARU - Modul Lab] dropdown dari tabel master `lab` -->
                    <select name="location" id="inputLocationSelect" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-green-100 cursor-pointer">
                        <option value="" disabled selected>Pilih Laboratorium</option>
                        <?php if (!empty($lab_list) && is_array($lab_list)): ?>
                            <?php foreach ($lab_list as $lab): ?>
                                <option value="<?= htmlspecialchars($lab['nama_lab']) ?>"><?= htmlspecialchars($lab['nama_lab']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <!-- [BARU - Modul Lab] input teks bebas, hanya untuk Jadwal Umum
                         dengan toggle "Lokasi Luar Lab" aktif -->
                    <input type="text" name="location" id="inputLocationText" disabled class="hidden w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100" placeholder="Contoh: Aula Kampus, Ruang Sidang FT">
                </div>
                <div><label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Tanggal Mulai</label><input type="date" name="date" id="inputDate" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100"></div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Jam Mulai</label><input type="time" name="start_time" id="inputStart" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100"></div>
                <div><label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Jam Selesai</label><input type="time" name="end_time" id="inputEnd" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100"></div>
            </div>

            <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 space-y-3">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Mode Perulangan</label>
                        <select name="model_perulangan" id="inputRepeatModel" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm" onchange="handleRepeatChange()">
                            <option value="sekali">Sekali (1 Hari)</option>
                            <option value="mingguan">Mingguan (Hari Sama)</option>
                            <option value="rentang">Berurutan (Rentang)</option>
                        </select>
                    </div>
                    <div id="endDateContainer" class="hidden">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Sampai Tanggal</label>
                        <input type="date" name="end_date_repeat" id="inputEndDateRepeat" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm">
                    </div>
                </div>
                <p class="text-[10px] text-gray-500 italic" id="repeatHint">Jadwal hanya akan dibuat pada tanggal yang dipilih.</p>
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
        <h3 class="text-xl font-extrabold text-gray-800 mb-2">Hapus Jadwal?</h3><p class="text-sm text-gray-500 mb-6 px-2">Jadwal ini akan dihapus permanen.</p>
        <div class="flex gap-3 w-full"><button onclick="closeCustomConfirm()" class="flex-1 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold hover:bg-gray-50 transition">Batal</button><button id="confirmYesBtn" class="flex-1 py-3 rounded-xl bg-red-600 text-white font-bold shadow-lg hover:bg-red-700 transition">Ya, Hapus</button></div>
    </div>
</div>

<?php if (isset($_SESSION['flash'])): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() { setTimeout(() => { showCustomAlert(<?= json_encode($_SESSION['flash']['type']) ?>, <?= json_encode($_SESSION['flash']['title']) ?>, <?= json_encode($_SESSION['flash']['message']) ?>); }, 300); });
</script>
<?php unset($_SESSION['flash']); endif; ?>

