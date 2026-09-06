<?php
$start_date = !empty($start_date) ? $start_date : date('Y-m-01');
$end_date = !empty($end_date) ? $end_date : date('Y-m-t');
$assistant_id = $assistant_id ?? '';
$schedule_type = $schedule_type ?? '';
$sort_by = $sort_by ?? 'hari_waktu';
$assistants = $assistants ?? [];
?>
<!-- FullCalendar loaded via vendor_js in controller -->

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-12">

    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden circuit-pattern">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0 text-center md:text-left">
                <h1 class="text-3xl font-extrabold tracking-tight">Monitoring Jadwal</h1>
                <p class="text-slate-300 mt-2 text-sm">Pantau aktivitas laboratorium.</p>
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
                        <input type="date" name="start_date" value="<?= htmlspecialchars($start_date, ENT_QUOTES, 'UTF-8') ?>" max="<?= htmlspecialchars($end_date, ENT_QUOTES, 'UTF-8') ?>" onchange="handleStartDateChange(this.value)" class="bg-transparent border-none focus:ring-0 text-xs font-bold text-gray-600 outline-none p-0 w-full cursor-pointer mt-0.5">
                    </div>
                    <div class="flex flex-col bg-gray-50 px-3 py-1.5 rounded-xl border border-gray-200">
                        <span class="text-[9px] text-gray-400 font-bold uppercase leading-none">Sampai</span>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($end_date, ENT_QUOTES, 'UTF-8') ?>" min="<?= htmlspecialchars($start_date, ENT_QUOTES, 'UTF-8') ?>" onchange="handleEndDateChange(this.value)" class="bg-transparent border-none focus:ring-0 text-xs font-bold text-gray-600 outline-none p-0 w-full cursor-pointer mt-0.5">
                    </div>
                </div>

                <input type="hidden" name="sort_by" value="<?= htmlspecialchars($sort_by, ENT_QUOTES, 'UTF-8') ?>">

                <div class="flex flex-wrap gap-2 w-full">
                    <select name="assistant_id_select" onchange="applyFilterFromDropdown(this.value)" class="flex-1 min-w-[120px] bg-gray-50 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl p-2.5 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer">
                        <option value="all">Semua Asisten</option>
                        <?php if (!empty($assistants) && is_array($assistants)): ?>
                            <?php foreach($assistants as $ast): ?>
                                <option value="<?= $ast['id_profil'] ?>" <?= ($assistant_id == $ast['id_profil']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ast['nama'] ?? $ast['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>

                    <select name="schedule_type" onchange="fetchFilteredSchedules()" class="flex-1 min-w-[110px] bg-gray-50 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl p-2.5 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer">
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
        <!-- [BARU] Sebelumnya "flex-col sm:flex-row" - breakpoint sm (viewport
             640px) tidak memperhitungkan bahwa card ini hanya 5/12 lebar grid
             begitu breakpoint lg (1024px) aktif, sehingga di layar ~1024-1279px
             tombol Excel kepotong keluar card (overflow-hidden menyembunyikannya).
             flex-wrap bereaksi terhadap lebar CONTAINER sebenarnya, bukan viewport,
             jadi selalu pas berapa pun lebar kolomnya. -->
        <div class="lg:col-span-5 bg-white p-6 rounded-3xl shadow-sm border border-gray-200 flex flex-wrap items-center justify-between gap-4 relative overflow-hidden">
            <div class="absolute right-0 top-0 h-full w-16 bg-green-50/50 skew-x-12 transform origin-bottom-left"></div>

            <div class="relative z-10 flex items-center gap-2 shrink-0">
                <div class="p-2 bg-green-100 text-green-600 rounded-lg">
                    <i class="fas fa-file-export"></i>
                </div>
                <h3 class="font-extrabold text-gray-700 whitespace-nowrap">Ekspor Laporan</h3>
            </div>

            <div class="relative z-10 flex flex-wrap gap-3 flex-1 justify-end min-w-[210px]">
                <?php
                    $roleLink = strtolower(str_replace(' ', '', $_SESSION['role']));
                    $qs = "start_date=" . $start_date . "&end_date=" . $end_date . "&assistant_id=" . $assistant_id . "&schedule_type=" . $schedule_type . "&sort_by=" . $sort_by;
                ?>
                <a id="exportPdfBtn" href="<?= BASE_URL ?>/<?= $roleLink ?>/exportSchedulePdf?<?= $qs ?>" class="flex-1 min-w-[100px] flex items-center justify-center gap-2 px-5 py-2.5 bg-red-50 text-red-600 border border-red-100 rounded-xl font-bold text-xs hover:bg-red-600 hover:text-white hover:shadow-lg transition group">
                    <i class="fas fa-file-pdf text-lg group-hover:scale-110 transition"></i> <span>PDF</span>
                </a>
                <a id="exportExcelBtn" href="<?= BASE_URL ?>/<?= $roleLink ?>/exportScheduleCsv?<?= $qs ?>" class="flex-1 min-w-[100px] flex items-center justify-center gap-2 px-5 py-2.5 bg-green-50 text-green-600 border border-green-100 rounded-xl font-bold text-xs hover:bg-green-600 hover:text-white hover:shadow-lg transition group">
                    <i class="fas fa-file-excel text-lg group-hover:scale-110 transition"></i> <span>Excel</span>
                </a>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 lg:h-[850px] h-auto">
        <div class="w-full lg:w-72 space-y-6 flex flex-col h-full shrink-0">
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

            <!-- Filter Asisten Sidebar -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col overflow-hidden flex-1 min-h-0">
                <div class="p-5 border-b border-gray-100 bg-white sticky top-0 z-20">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="font-extrabold text-gray-700 text-sm uppercase tracking-wide">Filter Asisten</h3>
                            <p class="text-[10px] text-gray-400">Klik untuk filter kalender</p>
                        </div>
                        <div class="px-3 py-1 bg-blue-50 text-blue-600 rounded-lg text-xs font-bold border border-blue-100 shadow-sm">
                            <span class="font-normal text-blue-400">Total: </span> <?= count($assistants) ?> 
                        </div>
                    </div>

                    <div class="relative group">
                        <i class="fas fa-search absolute left-4 top-3.5 text-gray-400 group-focus-within:text-blue-500 transition-colors text-sm"></i>
                        <input type="text" id="searchFilterInput" placeholder="Cari nama..." 
                            class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition-all">
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-3 space-y-2 custom-scrollbar" id="filterListContainer">
                    <div onclick="applyFilter('all')" id="filter-all" class="assistant-card filter-item filter-active p-3 rounded-2xl cursor-pointer flex items-center gap-3 border border-transparent hover:bg-gray-50 hover:border-gray-200 transition-all duration-200 group">
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0 shadow-sm group-hover:scale-105 transition-transform"><i class="fas fa-layer-group text-sm"></i></div>
                        <div><h4 class="font-bold text-gray-800 text-sm leading-tight">Semua Jadwal</h4><p class="text-[10px] text-gray-500 font-medium mt-0.5">Gabungan semua data</p></div>
                        <div class="ml-auto opacity-0 check-icon text-blue-600 transition-opacity"><i class="fas fa-check-circle"></i></div>
                    </div>

                    <?php foreach($assistants as $ast): ?>
                    <div onclick="applyFilter('<?= $ast['id_profil'] ?>')" id="filter-<?= $ast['id_profil'] ?>" class="assistant-card filter-item p-3 rounded-2xl cursor-pointer flex items-center gap-3 border border-transparent hover:bg-gray-50 hover:border-gray-200 transition-all duration-200 group" data-name="<?= htmlspecialchars(strtolower($ast['nama'] ?? $ast['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="relative shrink-0"><img src="<?= !empty($ast['photo_profile']) ? BASE_URL.'/uploads/profile/'.$ast['photo_profile'] : 'https://ui-avatars.com/api/?name='.urlencode($ast['nama'] ?? $ast['name'] ?? '').'&background=random' ?>" class="w-10 h-10 rounded-full object-cover border border-gray-100 shadow-sm group-hover:scale-105 transition-transform"></div>
                        <div class="min-w-0 flex-1"><h4 class="font-bold text-gray-800 text-sm leading-tight truncate group-hover:text-blue-600 transition-colors"><?= htmlspecialchars($ast['nama'] ?? $ast['name'] ?? '') ?></h4><p class="text-[10px] text-gray-500 font-medium mt-0.5 truncate"><?= htmlspecialchars($ast['position'] ?? 'Anggota Lab', ENT_QUOTES, 'UTF-8') ?></p></div>
                        <div class="ml-auto opacity-0 check-icon text-blue-600 transition-opacity"><i class="fas fa-check-circle"></i></div>
                    </div>
                    <?php endforeach; ?>

                    <div id="noResultFilter" class="hidden text-center py-8 text-gray-400 text-xs italic">Tidak ditemukan.</div>
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
        <div class="p-0 overflow-y-auto flex-1 bg-gray-50 custom-scrollbar" id="modalListContainer">
            </div>
    </div>
</div>

