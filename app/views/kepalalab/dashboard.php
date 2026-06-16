<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="max-w-7xl mx-auto space-y-8 animate-enter">
    
    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden circuit-pattern">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0 text-center md:text-left">
                <?php
                    $fullName = $user['name'];
                    $parts = explode(',', $fullName);
                    $frontNameOnly = trim($parts[0]);
                    $words = explode(' ', $frontNameOnly);
                    $displayName = $words[0];
                    foreach ($words as $word) {
                        $word = trim($word);
                        if (!empty($word) && strpos($word, '.') === false) {
                            $displayName = $word;
                            break;
                        }
                    }
                ?>
                <h1 class="text-3xl font-extrabold">Halo, <?= htmlspecialchars($displayName) ?> ! 👋</h1>
                <p class="text-blue-100 mt-2 text-sm">Monitoring aktivitas laboratorium secara menyeluruh.</p>
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

    <div class="bg-white p-6 rounded-3xl shadow-lg shadow-gray-200/50 border border-gray-100 mt-8">
        <h4 class="text-sm font-bold text-gray-500 uppercase tracking-widest mb-6">Ringkasan Aktivitas Hari Ini</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 p-4 rounded-2xl border border-blue-100 text-center hover:shadow-md transition">
                <span class="block text-3xl font-extrabold text-blue-600"><?= $stats['total_asisten'] ?></span>
                <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider">Total Asisten</span>
            </div>
            <div class="bg-green-50 p-4 rounded-2xl border border-green-100 text-center hover:shadow-md transition">
                <span class="block text-3xl font-extrabold text-green-600"><?= $stats['hadir_today'] ?></span>
                <span class="text-[10px] font-bold text-green-700 uppercase tracking-wider">Hadir Hari Ini</span>
            </div>
            <div class="bg-yellow-50 p-4 rounded-2xl border border-yellow-100 text-center hover:shadow-md transition">
                <span class="block text-3xl font-extrabold text-yellow-600"><?= $stats['izin_today'] ?></span>
                <span class="text-[10px] font-bold text-yellow-700 uppercase tracking-wider">Izin Hari Ini</span>
            </div>
            <div class="bg-red-50 p-4 rounded-2xl border border-red-100 text-center hover:shadow-md transition">
                <span class="block text-3xl font-extrabold text-red-600"><?= $stats['alpa_today'] ?></span>
                <span class="text-[10px] font-bold text-red-700 uppercase tracking-wider">Tidak Hadir</span>
            </div>
        </div>
    </div>

    <div class="space-y-2 mb-8">
        <div class="flex items-center justify-between px-2">
            <h3 class="text-sm font-bold text-gray-600 uppercase tracking-wide">Daftar Asisten</h3>
            <div class="flex items-center gap-2 text-[10px] font-bold text-gray-400">
                <span class="flex items-center gap-1"><i class="fas fa-circle text-green-500 text-[6px]"></i> On</span>
                <span class="flex items-center gap-1"><i class="fas fa-circle text-yellow-500 text-[6px]"></i> Izin</span>
                <span class="flex items-center gap-1"><i class="fas fa-circle text-red-500 text-[6px]"></i> Off</span>
                <button type="button" onclick="openAssistantSearchModal()" title="Cari Asisten" class="flex items-center justify-center w-5 h-5 rounded-full bg-gray-100 hover:bg-blue-100 hover:text-blue-600 text-gray-500 transition">
                    <i class="fas fa-magnifying-glass text-[9px]"></i>
                </button>
            </div>
        </div>
        
        <?php
            // [BARU] Data untuk modal "Cari Asisten" (lihat akhir file). Dihitung
            // terpisah dari loop carousel di bawah agar carousel yang sudah ada
            // TIDAK disentuh/diubah sama sekali.
            $searchCards = [];
            $jabatanList = [];
            foreach ($assistants as $a) {
                $sVStatus   = $a['visual_status'] ?? 'alpha';
                $sInactive  = (($a['status_account'] ?? 'ACTIVE') === 'INACTIVE');

                if ($sInactive) {
                    $sImgFilter   = 'grayscale opacity-50';
                    $sDotColor    = 'bg-white border border-gray-400';
                    $sStatusLabel = 'Nonaktif';
                } else {
                    switch ($sVStatus) {
                        case 'online':
                            $sImgFilter   = 'grayscale-0';
                            $sDotColor    = 'bg-green-500 animate-pulse shadow-[0_0_8px_#22c55e]';
                            $sStatusLabel = 'Online'; break;
                        case 'izin':
                            $sImgFilter   = 'sepia brightness-90';
                            $sDotColor    = 'bg-yellow-500 shadow-[0_0_8px_#eab308]';
                            $sStatusLabel = 'Izin'; break;
                        default:
                            $sImgFilter   = 'grayscale opacity-70';
                            $sDotColor    = 'bg-red-500 shadow-[0_0_8px_#ef4444]';
                            $sStatusLabel = 'Offline'; break;
                    }
                }

                $sPhotoPath = !empty($a['photo_profile']) && file_exists('uploads/profile/' . $a['photo_profile'])
                    ? BASE_URL . '/uploads/profile/' . $a['photo_profile']
                    : "https://ui-avatars.com/api/?name=" . urlencode($a['name'] ?? 'Asisten') . "&background=random&size=500";

                // Kepala Lab: getAllUsers() mengembalikan alias 'position' (bukan 'jabatan')
                $sJabatan = $a['position'] ?? 'Asisten';
                if (!in_array($sJabatan, $jabatanList)) $jabatanList[] = $sJabatan;

                $searchCards[] = [
                    'json'        => htmlspecialchars(json_encode($a), ENT_QUOTES, 'UTF-8'),
                    'name'        => $a['name'] ?? 'Asisten',
                    'jabatan'     => $sJabatan,
                    'imgFilter'   => $sImgFilter,
                    'dotColor'    => $sDotColor,
                    'statusLabel' => $sStatusLabel,
                    'photoPath'   => $sPhotoPath,
                    'isInactive'  => $sInactive,
                ];
            }
            sort($jabatanList);
        ?>

        <?php if(!empty($assistants)): ?>
            <div class="carousel-container">
                <div class="carousel-track">
                    <?php 
                    $workingList = $assistants;
                    $minItems = 10;
                    if (!empty($workingList)) {
                        while (count($workingList) < $minItems) {
                            $workingList = array_merge($workingList, $assistants);
                        }
                    }
                    $allCards = array_merge($workingList, $workingList); 
                    
                    foreach($allCards as $asisten): 
                        $vStatus    = $asisten['visual_status'] ?? 'alpha';
                        $isInactive = (($asisten['status_account'] ?? 'ACTIVE') === 'INACTIVE');
                        $imgFilter  = '';
                        $dotColor   = '';
                        $statusLabel = '';

                        if ($isInactive) {
                            $imgFilter   = 'grayscale opacity-50';
                            $dotColor    = 'bg-white border border-gray-400';
                            $statusLabel = 'Nonaktif';
                        } else {
                            switch ($vStatus) {
                                case 'online':
                                    $imgFilter   = 'grayscale-0';
                                    $dotColor    = 'bg-green-500 animate-pulse shadow-[0_0_8px_#22c55e]';
                                    $statusLabel = 'Online'; break;
                                case 'izin':
                                    $imgFilter   = 'sepia brightness-90';
                                    $dotColor    = 'bg-yellow-500 shadow-[0_0_8px_#eab308]';
                                    $statusLabel = 'Izin'; break;
                                default:
                                    $imgFilter   = 'grayscale opacity-70';
                                    $dotColor    = 'bg-red-500 shadow-[0_0_8px_#ef4444]';
                                    $statusLabel = 'Offline'; break;
                            }
                        }

                        $photoPath = !empty($asisten['photo_profile']) && file_exists('uploads/profile/' . $asisten['photo_profile'])
                            ? BASE_URL . '/uploads/profile/' . $asisten['photo_profile']
                            : "https://ui-avatars.com/api/?name=" . urlencode($asisten['name']) . "&background=random&size=500";

                        $jsonUser  = htmlspecialchars(json_encode($asisten), ENT_QUOTES, 'UTF-8');
                        $cardExtra = $isInactive ? 'border-2 border-gray-300 bg-gray-100/60' : '';
                    ?>
                    
                    <div class="polaroid-card <?= $cardExtra ?> rounded-xl cursor-pointer group relative hover:-translate-y-1 transition-all duration-300" onclick="openDetailModal(<?= $jsonUser ?>)">
                        <div class="absolute top-3 right-3 z-20 w-3 h-3 rounded-full border-2 border-white <?= $dotColor ?>" title="<?= $statusLabel ?>"></div>
                        <?php if ($isInactive): ?>
                        <div class="absolute top-2 left-2 z-20">
                            <span class="text-[9px] font-extrabold bg-gray-500 text-white px-1.5 py-0.5 rounded-md uppercase tracking-wide">Nonaktif</span>
                        </div>
                        <?php endif; ?>
                        <div class="aspect-square bg-gray-100 mb-3 border border-gray-100 overflow-hidden rounded-lg relative">
                            <img src="<?= $photoPath ?>" class="w-full h-full object-cover transition-all duration-500 <?= $imgFilter ?>" alt="<?= $asisten['name'] ?>">
                        </div>
                        <div class="text-center">
                            <h3 class="font-bold text-sm truncate px-1 leading-tight <?= $isInactive ? 'text-gray-400' : 'text-gray-800' ?>"><?= $asisten['name'] ?></h3>
                            <p class="text-[10px] text-gray-400 font-bold uppercase mt-1"><?= $asisten['position'] ?? 'Asisten' ?></p>
                        </div>
                        <div class="absolute inset-0 bg-black/10 opacity-0 group-hover:opacity-100 transition flex items-center justify-center rounded-xl">
                            <span class="bg-white/90 px-3 py-1 rounded-full text-[10px] font-bold shadow-sm text-gray-700 backdrop-blur-sm">Lihat Detail</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-gray-400 bg-white rounded-3xl border border-dashed border-gray-200">
                <i class="fas fa-users-slash text-4xl mb-3 opacity-50"></i>
                <p class="text-xs">Belum ada data asisten.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h3 class="text-lg font-bold text-gray-800">Analisis Kehadiran</h3>
            <div class="flex gap-2">
                <select id="chartFilter" onchange="updateChart()" class="bg-gray-50 border-none text-gray-600 text-xs font-bold rounded-lg p-2 focus:ring-2 focus:ring-indigo-200 cursor-pointer outline-none">
                    <option value="daily">Harian</option>
                    <option value="weekly" selected>Mingguan</option>
                    <option value="monthly">Bulanan</option>
                </select>
                <div class="flex bg-gray-100 p-1 rounded-lg">
                    <button onclick="setChartType('bar')" class="p-1.5 rounded hover:bg-white shadow-sm transition"><i class="fas fa-chart-bar text-xs text-gray-600"></i></button>
                    <button onclick="setChartType('line')" class="p-1.5 rounded hover:bg-white shadow-sm transition"><i class="fas fa-chart-line text-xs text-gray-600"></i></button>
                    <button onclick="setChartType('pie')" class="p-1.5 rounded hover:bg-white shadow-sm transition"><i class="fas fa-chart-pie text-xs text-gray-600"></i></button>
                </div>
            </div>
        </div>
        <div class="h-80 w-full"><canvas id="adminChart"></canvas></div>
    </div>

    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
        <div class="flex flex-col md:flex-row justify-between items-center mb-2 gap-4">
            <h3 class="text-lg font-bold text-gray-800">Performa Asisten</h3>
            <div class="flex flex-wrap gap-2 items-center">
                <select id="perfMetric" onchange="updatePerfChart(true)" class="bg-gray-50 border-none text-gray-600 text-xs font-bold rounded-lg p-2 focus:ring-2 focus:ring-indigo-200 cursor-pointer outline-none">
                    <option value="kehadiran">Jumlah Kehadiran</option>
                    <option value="jam_masuk">Rata-rata Jam Masuk</option>
                    <option value="izin">Jumlah Izin</option>
                    <option value="logbook">Detail Logbook</option>
                    <option value="jadwal">Jumlah Jadwal/Tugas</option>
                    <option value="durasi_kerja">Durasi Kerja</option>
                </select>
                <select id="perfLimit" onchange="updatePerfChart(false)" class="bg-gray-50 border-none text-gray-600 text-xs font-bold rounded-lg p-2 focus:ring-2 focus:ring-indigo-200 cursor-pointer outline-none">
                    <option value="10">Top 10</option>
                    <option value="20">Top 20</option>
                    <option value="all" selected>Semua</option>
                </select>
                <div class="flex bg-gray-100 p-1 rounded-lg" id="perfChartTypeGroup">
                    <button onclick="setPerfChartType('bar')" data-type="bar" class="perf-type-btn p-1.5 rounded hover:bg-white shadow-sm transition"><i class="fas fa-chart-bar text-xs text-gray-600"></i></button>
                    <button onclick="setPerfChartType('line')" data-type="line" class="perf-type-btn p-1.5 rounded hover:bg-white shadow-sm transition"><i class="fas fa-chart-line text-xs text-gray-600"></i></button>
                    <button onclick="setPerfChartType('pie')" data-type="pie" class="perf-type-btn p-1.5 rounded hover:bg-white shadow-sm transition"><i class="fas fa-chart-pie text-xs text-gray-600"></i></button>
                </div>
            </div>
        </div>
        <p class="text-xs text-gray-400 mb-3">Bandingkan performa antar asisten di laboratorium Anda berdasarkan metrik yang dipilih.</p>
        <p id="perfChartNote" class="hidden text-[10px] text-amber-600 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 mb-3"></p>
        <div id="perfChartOuter" class="w-full h-80 overflow-y-auto custom-scrollbar relative">
            <div id="perfChartInner" class="w-full h-full relative">
                <canvas id="assistantPerfChart"></canvas>
            </div>
            <div id="perfChartEmpty" class="hidden absolute inset-0 flex flex-col items-center justify-center text-gray-400">
                <i class="fas fa-inbox text-3xl mb-2 opacity-40"></i>
                <p class="text-xs">Belum ada data untuk metrik ini.</p>
            </div>
            <div id="perfChartLoading" class="hidden absolute inset-0 flex items-center justify-center text-gray-400 text-xs bg-white/60">
                <i class="fas fa-circle-notch fa-spin mr-2"></i> Memuat data...
            </div>
        </div>
    </div>

</div>

<div id="detailModal" class="hidden fixed inset-0 z-60 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm transition-opacity opacity-0" id="detailBackdrop" onclick="closeDetailModal()"></div>
    
    <div class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col md:flex-row max-h-[90vh]" id="detailContent">
        
        <div class="w-full md:w-1/3 bg-gray-50 border-r border-gray-100 flex flex-col">
            <div class="p-6 flex flex-col items-center text-center overflow-y-auto custom-scrollbar h-full">
                <div class="w-24 h-24 rounded-full p-1 bg-white shadow-lg mb-3 relative group">
                    <img id="m_photo" src="" class="w-full h-full rounded-full object-cover">
                    <div id="m_dot_overlay" class="absolute bottom-1 right-1 w-5 h-5 rounded-full border-2 border-white shadow-sm"></div>
                </div>
                <h2 id="m_name" class="text-lg font-extrabold text-gray-800 leading-tight px-2"></h2>
                <p id="m_position" class="text-xs text-indigo-600 font-bold uppercase tracking-wider mt-1 mb-6 px-3 py-1 bg-indigo-50 rounded-full inline-block"></p>
                
                <div class="w-full space-y-3 text-left">
                    <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm">
                        <p class="text-[10px] text-gray-400 font-bold uppercase mb-1">Nomor Induk Mahasiswa (NIM)</p>
                        <div class="flex items-center gap-2"><i class="fas fa-id-badge text-gray-300"></i><span id="m_nim" class="font-mono font-bold text-gray-700 text-xs">-</span></div>
                    </div>
                    <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm">
                        <p class="text-[10px] text-gray-400 font-bold uppercase mb-1">Info Akademik</p>
                        <div class="text-xs text-gray-600 space-y-1">
                            <div class="flex items-center gap-2"><i class="fas fa-graduation-cap text-gray-300 w-4"></i> <span id="m_class" class="font-bold">-</span></div>
                            <div class="flex items-center gap-2"><i class="fas fa-university text-gray-300 w-4"></i> <span id="m_prodi">-</span></div>
                        </div>
                    </div>
                    <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm">
                        <p class="text-[10px] text-gray-400 font-bold uppercase mb-1">Kontak</p>
                        <div class="text-xs text-gray-600 space-y-2">
                            <div class="flex items-center gap-2 overflow-hidden"><i class="fas fa-envelope text-gray-300 w-4"></i> <span id="m_email" class="truncate">-</span></div>
                            <div class="flex items-center gap-2"><i class="fab fa-whatsapp text-gray-300 w-4"></i> <span id="m_phone" class="font-mono">-</span></div>
                        </div>
                    </div>
                    <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm">
                        <p class="text-[10px] text-gray-400 font-bold uppercase mb-1">Alamat</p>
                        <div class="flex items-start gap-2"><i class="fas fa-map-marker-alt text-gray-300 w-4 mt-0.5"></i><span id="m_address" class="text-xs text-gray-600 leading-snug">-</span></div>
                    </div>
                    <div class="mt-6 w-full space-y-2">
                        <a id="btnSchedule" href="#" class="flex items-center justify-center w-full py-3 rounded-xl bg-indigo-600 text-white font-bold text-xs uppercase tracking-wider hover:bg-indigo-700 shadow-md transition-all transform hover:scale-[1.02]">
                            <i class="fas fa-calendar-alt mr-2"></i> Jadwal Lengkap
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full md:w-2/3 flex flex-col bg-white relative">
            <div class="flex border-b border-gray-100 px-6 py-4 justify-between items-center shrink-0">
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-chart-pie text-indigo-500"></i> <span id="m_stats_title">Statistik & Kehadiran</span></h3>
                <button onclick="closeDetailModal()" class="w-8 h-8 rounded-full bg-gray-50 hover:bg-red-50 text-gray-400 hover:text-red-500 transition flex items-center justify-center"><i class="fas fa-times"></i></button>
            </div>

            <!-- [BARU – Tahap 30] Overlay akun nonaktif -->
            <div id="m_inactive_overlay" class="hidden absolute inset-0 z-20 backdrop-blur-sm bg-white/70 flex flex-col items-center justify-center text-center p-8">
                <div class="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center mb-4">
                    <i class="fas fa-user-slash text-gray-400 text-2xl"></i>
                </div>
                <h4 class="text-lg font-extrabold text-gray-500 mb-2">Akun Telah Dinonaktifkan</h4>
                <p class="text-sm text-gray-400 max-w-xs">Data statistik dan kehadiran asisten ini disembunyikan karena akunnya sedang dinonaktifkan oleh Admin.</p>
            </div>

            <div class="flex-1 p-6 overflow-y-auto custom-scrollbar relative">
                <div id="m_status_box" class="mb-6 p-4 rounded-2xl border flex items-center gap-4 transition-all shadow-sm">
                    <div id="m_status_icon_bg" class="w-12 h-12 rounded-full flex items-center justify-center text-xl shrink-0 transition-colors"><i id="m_status_icon" class="fas"></i></div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-widest opacity-60 mb-0.5">Status Hari Ini</p>
                        <h4 id="m_status_text" class="text-lg font-extrabold">Loading...</h4>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total Kehadiran</h4>
                        <div class="flex bg-gray-100 rounded-lg p-1 gap-1">
                            <button onclick="setModalChartType('bar')" class="p-1.5 rounded-md hover:bg-white hover:shadow-sm text-gray-500 transition text-xs"><i class="fas fa-chart-bar"></i></button>
                            <button onclick="setModalChartType('doughnut')" class="p-1.5 rounded-md hover:bg-white hover:shadow-sm text-gray-500 transition text-xs"><i class="fas fa-chart-pie"></i></button>
                        </div>
                    </div>
                    <div class="relative h-48 w-full flex items-center justify-center"><canvas id="modalChartCanvas"></canvas></div>
                    <div class="grid grid-cols-3 gap-2 mt-4 text-center">
                        <div class="p-2 rounded-lg bg-green-50 border border-green-100"><span class="block text-xl font-bold text-green-600" id="stat_hadir">0</span><span class="text-[10px] uppercase font-bold text-green-400">Hadir</span></div>
                        <div class="p-2 rounded-lg bg-yellow-50 border border-yellow-100"><span class="block text-xl font-bold text-yellow-600" id="stat_izin">0</span><span class="text-[10px] uppercase font-bold text-yellow-400">Izin</span></div>
                        <div class="p-2 rounded-lg bg-red-50 border border-red-100"><span class="block text-xl font-bold text-red-600" id="stat_alpa">0</span><span class="text-[10px] uppercase font-bold text-red-400">Alpa</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- [BARU – Tahap 30] Modal Konfirmasi Reset Individu (Kepala Lab lihat-only — admin yg execute via URL) -->
<div id="singleResetModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm" onclick="closeSingleResetModal()"></div>
    <div class="relative bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden z-10">
        <div class="bg-gradient-to-r from-orange-500 to-amber-500 px-6 py-5 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center shrink-0"><i class="fas fa-rotate-right text-white"></i></div>
            <div class="text-white"><h3 class="font-extrabold text-lg leading-tight">Reset Presensi Asisten</h3><p class="text-orange-100 text-[11px]">Hanya data asisten yang dipilih</p></div>
        </div>
        <div class="p-6 space-y-4">
            <p class="text-sm text-gray-600">Anda akan mereset seluruh data presensi dan logbook milik <span id="singleResetName" class="font-bold text-gray-800"></span>.</p>
            <div class="bg-orange-50 border border-orange-200 rounded-2xl p-4 text-xs text-orange-700"><p class="font-bold mb-1"><i class="fas fa-info-circle mr-1"></i>Jadwal tidak akan terpengaruh.</p><p>File ZIP rekap akan diunduh otomatis.</p></div>
            <div class="flex gap-3">
                <button onclick="closeSingleResetModal()" class="flex-1 py-3 rounded-xl bg-gray-100 text-gray-600 font-bold text-sm hover:bg-gray-200 transition">Batal</button>
                <button onclick="executeSingleReset()" class="flex-1 py-3 rounded-xl bg-orange-500 text-white font-bold text-sm hover:bg-orange-600 transition"><i class="fas fa-rotate-right mr-2"></i>Ya, Reset Data Ini</button>
            </div>
        </div>
    </div>
</div>

<!-- [BARU – Tahap 30] Modal nonaktifkan/aktifkan (untuk overlay JS) -->
<div id="deactivateModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm" onclick="closeDeactivateModal()"></div>
    <div class="relative bg-white w-full max-w-sm rounded-3xl shadow-2xl overflow-hidden z-10">
        <div class="bg-gradient-to-r from-gray-700 to-gray-600 px-6 py-5 text-white"><h3 class="font-extrabold">Nonaktifkan Akun?</h3><p class="text-gray-300 text-xs mt-1">Data presensi akan diarsipkan + dihapus</p></div>
        <div class="p-6"><p class="text-sm text-gray-600 mb-4">Asisten: <span id="deactivateName" class="font-bold text-gray-800"></span></p><div class="flex gap-3"><button onclick="closeDeactivateModal()" class="flex-1 py-3 rounded-xl bg-gray-100 text-gray-600 font-bold text-sm">Batal</button><button onclick="executeToggleStatus('INACTIVE')" class="flex-1 py-3 rounded-xl bg-gray-700 text-white font-bold text-sm hover:bg-gray-800">Nonaktifkan</button></div></div>
    </div>
</div>
<div id="reactivateModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm" onclick="closeReactivateModal()"></div>
    <div class="relative bg-white w-full max-w-sm rounded-3xl shadow-2xl overflow-hidden z-10">
        <div class="bg-gradient-to-r from-blue-700 to-cyan-600 circuit-pattern relative overflow-hidden px-6 py-5 text-white"><h3 class="font-extrabold">Aktifkan Kembali?</h3><p class="text-blue-100 text-xs mt-1">Data presensi lama akan diarsipkan + direset</p></div>
        <div class="p-6"><p class="text-sm text-gray-600 mb-4">Asisten: <span id="reactivateName" class="font-bold text-gray-800"></span></p><p class="text-xs text-blue-600 bg-blue-50 border border-blue-100 rounded-xl p-3 mb-4"><i class="fas fa-download mr-1"></i>ZIP akan diunduh otomatis.</p><div class="flex gap-3"><button onclick="closeReactivateModal()" class="flex-1 py-3 rounded-xl bg-gray-100 text-gray-600 font-bold text-sm">Batal</button><button onclick="executeToggleStatus('ACTIVE')" class="flex-1 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 text-white font-bold text-sm">Aktifkan</button></div></div>
    </div>
</div>

<!-- [BARU] Modal "Cari Asisten" — tampilan grid 4xn, sidebar jabatan & pencarian.
     Diakses lewat ikon kaca pembesar di samping legenda On/Izin/Off di atas
     carousel. Klik kartu memakai fungsi yang sama dengan carousel
     (openDetailModal) sehingga membuka detailModal seperti biasa. -->
<div id="assistantSearchModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm transition-opacity opacity-0" id="assistantSearchBackdrop" onclick="closeAssistantSearchModal()"></div>

    <div class="bg-white w-full max-w-5xl h-[85vh] rounded-3xl shadow-2xl relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col" id="assistantSearchContent">

        <!-- [DIUBAH] Header gradien biru-cyan + motif sirkuit, selaras dengan
             header sidebar utama & banner halaman. Logo chip ICLABS sebagai
             aksen "branding" pada modal. -->
        <div class="bg-gradient-to-r from-blue-700 to-cyan-600 circuit-pattern relative overflow-hidden flex items-center justify-between gap-3 p-4 md:p-5 shrink-0">
            <div class="flex items-center gap-3 text-white">
                <div class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
                    <i class="fas fa-microchip text-sm"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold leading-tight">Cari Asisten</h3>
                    <p class="text-[10px] text-blue-100 tracking-widest uppercase">ICLABS</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="relative">
                    <i class="fas fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-blue-100 text-xs"></i>
                    <input type="text" id="assistantSearchInput" oninput="filterAssistantSearch()" placeholder="Cari nama asisten..." class="pl-9 pr-4 py-2 w-40 sm:w-64 rounded-xl bg-white/15 border border-white/25 text-white placeholder-blue-100 text-sm focus:outline-none focus:bg-white/25 transition">
                </div>
                <button type="button" onclick="closeAssistantSearchModal()" class="text-blue-100 hover:text-white transition"><i class="fas fa-times text-xl"></i></button>
            </div>
        </div>

        <div class="flex-1 flex flex-col md:flex-row overflow-hidden">

            <!-- [DIUBAH] Sidebar jabatan: daftar di tengah, item aktif berupa
                 pill gradien biru-cyan + shadow (konsep sama dengan menu
                 aktif pada sidebar utama). -->
            <div class="w-full md:w-52 flex-shrink-0 bg-gray-50 border-b md:border-b-0 md:border-r border-gray-100 flex flex-row md:flex-col items-center overflow-x-auto md:overflow-y-auto custom-scrollbar px-3 py-3 md:px-4 md:pt-5 gap-2 md:gap-1.5">
                <div class="hidden md:flex w-9 h-9 rounded-xl bg-teal-50 items-center justify-center mb-2">
                    <i class="fas fa-microchip text-teal-600 text-sm"></i>
                </div>
                <h4 class="hidden md:block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Jabatan</h4>
                <button type="button" onclick="filterAssistantJabatan('all')" data-jabatan="all" class="assistant-jabatan-btn active whitespace-nowrap text-left text-xs font-bold px-3 py-2 rounded-xl transition md:w-44">
                    <i class="fas fa-users mr-1.5 opacity-70"></i>Semua Asisten
                </button>
                <?php foreach ($jabatanList as $j): ?>
                    <button type="button" onclick="filterAssistantJabatan('<?= htmlspecialchars($j, ENT_QUOTES) ?>')" data-jabatan="<?= htmlspecialchars($j, ENT_QUOTES) ?>" class="assistant-jabatan-btn whitespace-nowrap text-left text-xs font-bold px-3 py-2 rounded-xl transition md:w-44">
                        <i class="fas fa-user-tag mr-1.5 opacity-70"></i><?= htmlspecialchars($j) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar p-4 md:p-6">
                <?php if (!empty($searchCards)): ?>
                <div id="assistantSearchGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    <?php foreach ($searchCards as $card): ?>
                        <?php $cInactive = !empty($card['isInactive']); ?>
                        <div class="polaroid-card assistant-search-card rounded-xl cursor-pointer group relative hover:-translate-y-1 transition-all duration-300 <?= $cInactive ? 'border-2 border-gray-300 bg-gray-100/60' : '' ?>"
                             data-name="<?= strtolower(htmlspecialchars($card['name'], ENT_QUOTES)) ?>"
                             data-jabatan="<?= htmlspecialchars($card['jabatan'], ENT_QUOTES) ?>"
                             onclick="openDetailModal(<?= $card['json'] ?>)">
                            <div class="absolute top-3 right-3 z-20 w-3 h-3 rounded-full border-2 border-white <?= $card['dotColor'] ?>" title="<?= $card['statusLabel'] ?>"></div>
                            <?php if ($cInactive): ?>
                            <div class="absolute top-2 left-2 z-20">
                                <span class="text-[9px] font-extrabold bg-gray-500 text-white px-1.5 py-0.5 rounded-md uppercase tracking-wide">Nonaktif</span>
                            </div>
                            <?php endif; ?>
                            <div class="aspect-square bg-gray-100 mb-3 border border-gray-100 overflow-hidden rounded-lg relative">
                                <img src="<?= $card['photoPath'] ?>" class="w-full h-full object-cover transition-all duration-500 <?= $card['imgFilter'] ?>" alt="<?= htmlspecialchars($card['name'], ENT_QUOTES) ?>">
                            </div>
                            <div class="text-center">
                                <h3 class="font-bold text-sm truncate px-1 leading-tight <?= $cInactive ? 'text-gray-400' : 'text-gray-800' ?>"><?= htmlspecialchars($card['name']) ?></h3>
                                <p class="text-[10px] text-gray-400 font-bold uppercase mt-1"><?= htmlspecialchars($card['jabatan']) ?></p>
                            </div>
                            <div class="absolute inset-0 bg-black/10 opacity-0 group-hover:opacity-100 transition flex items-center justify-center rounded-xl">
                                <span class="bg-white/90 px-3 py-1 rounded-full text-[10px] font-bold shadow-sm text-gray-700 backdrop-blur-sm">Lihat Detail</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="assistantSearchEmpty" class="hidden text-center text-gray-400 py-16">
                    <i class="fas fa-user-slash text-3xl mb-3 opacity-40"></i>
                    <p class="text-xs">Tidak ada asisten yang cocok.</p>
                </div>
                <?php else: ?>
                <div class="text-center text-gray-400 py-16">
                    <i class="fas fa-users-slash text-3xl mb-3 opacity-40"></i>
                    <p class="text-xs">Belum ada data asisten.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
