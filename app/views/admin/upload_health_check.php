<?php
/* app/views/admin/upload_health_check.php — "Cek Sistem" (poin 2)
   Menampilkan hasil SystemHealthModel::checkUploadHealth(): apakah folder
   uploads/ ada & bisa ditulis, dan berapa banyak file yang tercatat di
   database tapi hilang secara fisik dari disk (indikasi kuat direktori
   penyimpanan dipindahkan/salah konfigurasi setelah deploy). */

$anyMissing = false;
foreach ($health['categories'] as $cat) {
    if ($cat['missing'] > 0) { $anyMissing = true; break; }
}
$pathOk = $health['upload_path_exists'] && $health['upload_path_writable'];
?>
<div class="max-w-5xl mx-auto space-y-6 animate-enter pb-12">

    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 circuit-pattern relative overflow-hidden rounded-3xl p-6 md:p-8 text-white shadow-xl shadow-blue-500/20">
        <h1 class="text-2xl font-extrabold flex items-center gap-2"><i class="fas fa-stethoscope"></i> Cek Sistem — Direktori Upload</h1>
        <p class="text-blue-100 text-sm mt-1">Memastikan foto presensi, bukti izin, dan foto profil masih tersimpan sesuai catatan database — berguna setelah deploy ulang untuk memastikan lokasi folder <code class="bg-white/15 px-1.5 py-0.5 rounded">public/uploads/</code> tidak berubah/berpindah.</p>
    </div>

    <?php if (!$pathOk): ?>
    <div class="bg-red-50 border border-red-200 rounded-2xl p-5 flex items-start gap-3">
        <i class="fas fa-triangle-exclamation text-red-500 text-xl mt-0.5"></i>
        <div>
            <h3 class="font-extrabold text-red-700">Folder upload bermasalah!</h3>
            <p class="text-sm text-red-600 mt-1">Sistem tidak bisa membaca atau menulis ke folder upload utama. Ini kemungkinan besar sebabnya foto presensi/logbook tidak tampil. Cek permission folder atau apakah lokasinya benar setelah deploy.</p>
        </div>
    </div>
    <?php elseif ($anyMissing): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 flex items-start gap-3">
        <i class="fas fa-triangle-exclamation text-amber-500 text-xl mt-0.5"></i>
        <div>
            <h3 class="font-extrabold text-amber-700">Ditemukan file yang hilang</h3>
            <p class="text-sm text-amber-600 mt-1">Beberapa foto yang tercatat di database tidak ditemukan secara fisik di server. Kemungkinan penyebab: folder <code class="bg-white/60 px-1.5 py-0.5 rounded">uploads/</code> dipindahkan/di-mount ulang saat deploy tanpa data lama ikut disalin. Lihat rincian per kategori di bawah.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-green-50 border border-green-200 rounded-2xl p-5 flex items-start gap-3">
        <i class="fas fa-circle-check text-green-500 text-xl mt-0.5"></i>
        <div>
            <h3 class="font-extrabold text-green-700">Semua sehat</h3>
            <p class="text-sm text-green-600 mt-1">Folder upload dapat diakses, dan seluruh file yang tercatat di database ditemukan di disk.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Status folder -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-5 border-b border-gray-100 bg-gray-50/50">
            <h4 class="text-sm font-bold text-gray-700 uppercase tracking-widest"><i class="fas fa-folder-open text-blue-500 mr-2"></i>Status Folder</h4>
            <p class="text-[11px] text-gray-400 mt-1 font-mono break-all">UPLOAD_PATH = <?= htmlspecialchars($health['upload_path'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-100">
            <?php foreach ($health['folders'] as $name => $f): ?>
            <div class="p-5">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-bold text-gray-700 text-sm">uploads/<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>/</span>
                    <?php if ($f['exists'] && $f['writable']): ?>
                        <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-green-100 text-green-700">OK</span>
                    <?php else: ?>
                        <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-red-100 text-red-700"><?= !$f['exists'] ? 'Tidak Ada' : 'Tidak Bisa Ditulis' ?></span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-gray-400"><?= number_format($f['file_count']) ?> file tersimpan</p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Kecocokan data vs file fisik -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-5 border-b border-gray-100 bg-gray-50/50">
            <h4 class="text-sm font-bold text-gray-700 uppercase tracking-widest"><i class="fas fa-link text-blue-500 mr-2"></i>Kecocokan Data ↔ File Fisik</h4>
        </div>
        <div class="divide-y divide-gray-100">
            <?php foreach ($health['categories'] as $cat): ?>
            <div class="p-5">
                <div class="flex items-center justify-between">
                    <h5 class="font-bold text-gray-700 text-sm"><?= htmlspecialchars($cat['label'], ENT_QUOTES, 'UTF-8') ?></h5>
                    <?php if ($cat['total'] === 0): ?>
                        <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-gray-100 text-gray-500">Belum ada data</span>
                    <?php elseif ($cat['missing'] === 0): ?>
                        <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-green-100 text-green-700"><?= $cat['total'] ?>/<?= $cat['total'] ?> ditemukan</span>
                    <?php else: ?>
                        <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-red-100 text-red-700"><?= $cat['missing'] ?> dari <?= $cat['total'] ?> hilang</span>
                    <?php endif; ?>
                </div>
                <?php if ($cat['missing'] > 0): ?>
                <div class="mt-2 bg-red-50 border border-red-100 rounded-xl p-3">
                    <p class="text-[10px] text-red-500 font-bold uppercase tracking-wide mb-1">Contoh file hilang (maks. 10):</p>
                    <ul class="text-xs text-red-600 font-mono space-y-0.5">
                        <?php foreach ($cat['missing_samples'] as $fname): ?>
                            <li>uploads/<?= htmlspecialchars($cat['folder'], ENT_QUOTES, 'UTF-8') ?>/<?= htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>
