<?php
// Set default values to prevent undefined variable errors
$pagination = $pagination ?? ['current' => 1, 'total_pages' => 1, 'total_items' => 0, 'per_page' => 10];
$start_date = $start_date ?? date('Y-m-d');
$end_date = $end_date ?? date('Y-m-d');
$selected_assistant = $selected_assistant ?? '';
$assistants_list = $assistants_list ?? [];
$attendance_list = $attendance_list ?? [];
?>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-12">

    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden circuit-pattern">
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
        <div class="space-y-6">

            <!-- [FIX] Filter Data sebelumnya berbagi grid 2-kolom dengan kartu
                 "Ekspor Laporan" (lg:grid-cols-2), sementara grid filter di
                 dalamnya JUGA berubah ke lg:grid-cols-3 pada breakpoint yang
                 SAMA (1024px) — akibatnya di lebar ~1024-1279px filter hanya
                 mendapat separuh lebar tapi harus memuat 3 kolom, sehingga
                 tanggal & select terpotong dan tidak terbaca. Filter Data kini
                 dibuat full-width sendiri (baris terpisah dari Ekspor Laporan)
                 dan memakai flex-wrap + min-width per field agar field selalu
                 tampil utuh — melebar mengikuti ruang yang tersedia, dan
                 berpindah baris hanya jika benar-benar tidak muat, bukan
                 dipaksa masuk ke kolom yang terlalu sempit. -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200 relative overflow-hidden">
                <div class="absolute right-0 top-0 h-full w-16 bg-blue-50/50 skew-x-12 transform origin-bottom-left"></div>

                <div class="relative z-10 flex flex-col xl:flex-row xl:items-center gap-4">
                    <div class="flex items-center gap-2 shrink-0">
                        <div class="p-2 bg-blue-100 text-blue-600 rounded-lg">
                            <i class="fas fa-filter"></i>
                        </div>
                        <h3 class="font-extrabold text-gray-700 whitespace-nowrap">Filter Data</h3>
                    </div>

                    <form class="w-full">
                        <div class="flex flex-wrap gap-2">
                            <!-- Tanggal dari–sampai -->
                            <div class="flex items-center gap-2 bg-gray-50 p-2.5 rounded-xl border border-gray-200 flex-1 min-w-[160px]">
                                <span class="text-[10px] text-gray-400 font-bold uppercase shrink-0">Dari</span>
                                <input type="date" name="start_date" value="<?= htmlspecialchars($start_date, ENT_QUOTES, 'UTF-8') ?>" class="bg-transparent border-none focus:ring-0 text-xs font-bold text-gray-600 outline-none p-0 w-full min-w-0">
                            </div>
                            <div class="flex items-center gap-2 bg-gray-50 p-2.5 rounded-xl border border-gray-200 flex-1 min-w-[160px]">
                                <span class="text-[10px] text-gray-400 font-bold uppercase shrink-0">Sampai</span>
                                <input type="date" name="end_date" value="<?= htmlspecialchars($end_date, ENT_QUOTES, 'UTF-8') ?>" class="bg-transparent border-none focus:ring-0 text-xs font-bold text-gray-600 outline-none p-0 w-full min-w-0">
                            </div>

                            <!-- Filter Jabatan -->
                            <div class="bg-gray-50 border border-gray-200 rounded-xl flex-1 min-w-[150px]">
                                <select name="jabatan" class="w-full bg-transparent text-gray-700 text-xs font-bold p-2.5 focus:ring-blue-500 outline-none cursor-pointer">
                                    <option value="">Semua Jabatan</option>
                                    <?php foreach(($jabatan_list ?? []) as $jab): ?>
                                    <option value="<?= htmlspecialchars($jab, ENT_QUOTES, 'UTF-8') ?>" <?= (($selected_jabatan ?? '') == $jab) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($jab, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Filter Angkatan -->
                            <div class="bg-gray-50 border border-gray-200 rounded-xl flex-1 min-w-[150px]">
                                <select name="angkatan" class="w-full bg-transparent text-gray-700 text-xs font-bold p-2.5 focus:ring-blue-500 outline-none cursor-pointer">
                                    <option value="">Semua Angkatan</option>
                                    <?php foreach(($angkatan_list ?? []) as $ang): ?>
                                    <option value="<?= htmlspecialchars($ang, ENT_QUOTES, 'UTF-8') ?>" <?= (($selected_angkatan ?? '') == $ang) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ang, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Filter per Asisten -->
                            <div class="bg-gray-50 border border-gray-200 rounded-xl flex-[2] min-w-[220px]">
                                <select name="assistant_id" class="w-full bg-transparent text-gray-700 text-xs font-bold p-2.5 focus:ring-blue-500 outline-none cursor-pointer">
                                    <option value="">Semua Asisten</option>
                                    <?php foreach($assistants_list as $ast): ?>
                                        <option value="<?= $ast['id_user'] ?>" <?= ($selected_assistant == $ast['id_user']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($ast['nama'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($ast['nim'], ENT_QUOTES, 'UTF-8') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Submit -->
                            <button type="submit" class="bg-blue-600 text-white px-5 py-2.5 rounded-xl flex items-center justify-center gap-2 hover:bg-blue-700 transition shadow-md font-bold text-xs shrink-0">
                                <i class="fas fa-search"></i>
                                <span>Filter</span>
                            </button>
                        </div>
                    </form>
                </div>
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
                        // [FIX] jabatan/angkatan sebelumnya tidak ikut dikirim ke exportPdf/exportCsv,
                        // sehingga hasil ekspor tidak sesuai dengan filter yang sedang aktif di layar.
                        $qs = http_build_query([
                            'start_date'   => $start_date,
                            'end_date'     => $end_date,
                            'assistant_id' => $selected_assistant,
                            'jabatan'      => $selected_jabatan ?? '',
                            'angkatan'     => $selected_angkatan ?? '',
                        ]);
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
                        <th class="p-5 pl-8">Asisten</th>
                        <th class="p-5 text-center">Hadir</th>
                        <th class="p-5 text-center">Izin / Sakit</th>
                        <th class="p-5 text-center">Tidak Hadir</th>
                        <th class="p-5 text-center">Tepat Waktu</th>
                        <th class="p-5 text-center">Terlambat</th>
                        <th class="p-5 text-center">Durasi Kerja</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php 
                    if(!empty($attendance_list)): 
                        $currentDateGroup = '';
                    ?>
<?php 
// attendance_list berisi summary per asisten dari getAttendanceSummary
foreach($attendance_list as $row):
    // Format durasi kerja
    $durasiBrut = (int)($row['total_durasi_menit'] ?? 0);
    $durasiTampil = ($durasiBrut > 0)
        ? floor($durasiBrut / 60) . 'j ' . ($durasiBrut % 60) . 'm'
        : '-';
?>
<tr class="border-b border-gray-100 hover:bg-blue-50/30 transition">
    <!-- Asisten -->
    <td class="p-5 pl-8">
        <div class="flex items-center gap-3">
            <?php $photo = !empty($row['photo_profile']) ? BASE_URL . '/uploads/profile/' . $row['photo_profile'] : null; ?>
            <?php if ($photo): ?>
                <img src="<?= $photo ?>" class="w-8 h-8 rounded-full object-cover border border-gray-200 shrink-0">
            <?php else: ?>
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs shrink-0">
                    <?= strtoupper(substr($row['name'] ?? 'A', 0, 1)) ?>
                </div>
            <?php endif; ?>
            <div>
                <p class="font-bold text-gray-800 text-xs"><?= htmlspecialchars($row['name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-gray-400 text-[10px]"><?= htmlspecialchars($row['nim'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
    </td>
    <!-- Hadir -->
    <td class="p-5 text-center">
        <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs font-bold px-2.5 py-1 rounded-full">
            <i class="fas fa-check text-[9px]"></i> <?= $row['total_hadir'] ?>
        </span>
    </td>
    <!-- Izin / Sakit -->
    <td class="p-5 text-center">
        <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-xs font-bold px-2.5 py-1 rounded-full">
            <i class="fas fa-file-medical text-[9px]"></i> <?= $row['total_izin'] ?>
        </span>
    </td>
    <!-- Tidak Hadir -->
    <td class="p-5 text-center">
        <span class="inline-flex items-center gap-1 bg-red-100 text-red-700 text-xs font-bold px-2.5 py-1 rounded-full">
            <i class="fas fa-times text-[9px]"></i> <?= $row['total_alpa'] ?>
        </span>
    </td>
    <!-- Tepat Waktu -->
    <td class="p-5 text-center">
        <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 text-xs font-bold px-2.5 py-1 rounded-full">
            <i class="fas fa-clock text-[9px]"></i> <?= $row['total_tepat_waktu'] ?? 0 ?>
        </span>
    </td>
    <!-- Terlambat -->
    <td class="p-5 text-center">
        <span class="inline-flex items-center gap-1 bg-orange-100 text-orange-700 text-xs font-bold px-2.5 py-1 rounded-full">
            <i class="fas fa-hourglass-half text-[9px]"></i> <?= $row['total_terlambat'] ?? 0 ?>
        </span>
    </td>
    <!-- Durasi Kerja -->
    <td class="p-5 text-center">
        <span class="text-gray-700 text-xs font-bold"><?= $durasiTampil ?></span>
    </td>
</tr>
<?php endforeach; else: ?>
                    <tr>
                        <td colspan="7" class="p-12 text-center">
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
        
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex flex-col sm:flex-row items-center justify-between gap-4">
            
            <div class="text-xs text-gray-500 font-bold uppercase tracking-wide">
                Menampilkan Halaman <span class="text-blue-600"><?= $pagination['current'] ?></span> dari <?= $pagination['total_pages'] ?> 
                <span class="normal-case text-gray-400 font-medium ml-1">(Total <?= $pagination['total_items'] ?> Data)</span>
            </div>

            <div class="flex items-center gap-2">
                <?php 
                    // Fungsi bantu untuk membuat Link tetap membawa semua filter aktif
                    // (sebelumnya jabatan/angkatan tidak ikut dibawa saat pindah halaman,
                    // sehingga filter tersebut hilang begitu pagination diklik)
                    function buildUrl($page, $sDate, $eDate, $astId, $jab = '', $ang = '') {
                        $params = [
                            'page' => $page,
                            'start_date' => $sDate,
                            'end_date' => $eDate,
                            'assistant_id' => $astId,
                            'jabatan' => $jab,
                            'angkatan' => $ang,
                        ];
                        // Gunakan http_build_query agar URL rapi
                        return BASE_URL . '/admin/monitorAttendance?' . http_build_query($params);
                    }
                ?>

                <?php if ($pagination['current'] > 1): ?>
                    <a href="<?= buildUrl($pagination['current'] - 1, $start_date, $end_date, $selected_assistant, $selected_jabatan ?? '', $selected_angkatan ?? '') ?>"
                       class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition shadow-sm">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </a>
                <?php else: ?>
                    <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 border border-gray-200 text-gray-300 cursor-not-allowed">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </span>
                <?php endif; ?>

                <?php 
                    $startPage = max(1, $pagination['current'] - 2);
                    $endPage = min($pagination['total_pages'], $pagination['current'] + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++): 
                        $activeClass = ($i == $pagination['current']) 
                            ? 'bg-blue-600 text-white border-blue-600 shadow-blue-500/30' 
                            : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50';
                ?>
                    <a href="<?= buildUrl($i, $start_date, $end_date, $selected_assistant, $selected_jabatan ?? '', $selected_angkatan ?? '') ?>"
                       class="w-8 h-8 flex items-center justify-center rounded-lg border text-xs font-bold transition shadow-sm <?= $activeClass ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($pagination['current'] < $pagination['total_pages']): ?>
                    <a href="<?= buildUrl($pagination['current'] + 1, $start_date, $end_date, $selected_assistant, $selected_jabatan ?? '', $selected_angkatan ?? '') ?>"
                       class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition shadow-sm">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </a>
                <?php else: ?>
                    <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 border border-gray-200 text-gray-300 cursor-not-allowed">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        </div>
    </div>
</div>

