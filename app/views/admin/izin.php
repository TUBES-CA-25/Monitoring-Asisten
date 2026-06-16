<?php
$izins = $izins ?? [];
$total = $total ?? 0;
$total_pages = $total_pages ?? 1;
$current_page = $current_page ?? 1;
$current_status = $current_status ?? 'Pending';
$current_tipe = $current_tipe ?? null;
?>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-12">

    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden circuit-pattern">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-extrabold">Manajemen Izin Asisten</h1>
                <p class="text-blue-100 mt-2">Konfirmasi permintaan izin/sakit dari asisten.</p>
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

    <!-- Success Message -->
    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i> Izin berhasil diupdate!
    </div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-2">
                <i class="fas fa-filter text-blue-600"></i>
                <h3 class="font-bold text-gray-700">Filter</h3>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                <select id="statusFilter" name="status" class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm">
                    <option value="Pending" <?= $current_status === 'Pending' ? 'selected' : '' ?>>Pending Saja</option>
                    <option value="Approved" <?= $current_status === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Rejected" <?= $current_status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="all" <?= $current_status === 'all' ? 'selected' : '' ?>>Semua Status</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h3 class="font-bold text-gray-700 uppercase tracking-wide text-sm">
                <i class="fas fa-clipboard-list mr-2 text-blue-600"></i>Daftar Izin (<span id="countIzin"><?= count($izins) ?></span> dari <span id="totalIzin"><?= $total ?></span>)
            </h3>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-400 uppercase">
                    <tr>
                        <th class="p-4">Asisten</th>
                        <th class="p-4">Tipe</th>
                        <th class="p-4">Tanggal</th>
                        <th class="p-4">Durasi</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="izinTableBody" class="divide-y divide-gray-50">
                    <?php if (count($izins) === 0): ?>
                    <tr>
                        <td colspan="6" class="p-6 text-center text-gray-400">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>Tidak ada data izin</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($izins as $izin): ?>
                    <tr class="hover:bg-blue-50/50 transition">
                        <td class="p-4">
                            <div class="flex items-center gap-2">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($izin['nama_asisten'] ?? 'User') ?>&background=random" 
                                     class="w-8 h-8 rounded-full border border-gray-200">
                                <div>
                                    <div class="font-bold text-gray-800"><?= htmlspecialchars($izin['nama_asisten'] ?? 'N/A') ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($izin['nim_asisten'] ?? '-') ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="p-4">
                            <span class="badge-<?= strtolower($izin['tipe']) ?>">
                                <?= htmlspecialchars($izin['tipe']) ?>
                            </span>
                        </td>
                        <td class="p-4 text-sm">
                            <div class="font-bold"><?= htmlspecialchars($izin['start_date_format']) ?></div>
                            <div class="text-xs text-gray-500">s/d <?= htmlspecialchars($izin['end_date_format']) ?></div>
                        </td>
                        <td class="p-4">
                            <span class="font-bold text-blue-600"><?= $izin['durasi_hari'] ?> hari</span>
                        </td>
                        <td class="p-4">
                            <span class="badge-<?= strtolower(str_replace(' ', '_', $izin['status_approval'])) ?>">
                                <?= htmlspecialchars($izin['status_approval']) ?>
                            </span>
                        </td>
                        <td class="p-4 text-center">
                            <button type="button" onclick="showModal(<?= $izin['id_izin'] ?>, '<?= htmlspecialchars($izin['nama_asisten']) ?>', '<?= htmlspecialchars($izin['tipe']) ?>', '<?= htmlspecialchars($izin['deskripsi']) ?>', '<?= htmlspecialchars($izin['status_approval']) ?>', '<?= htmlspecialchars($izin['file_bukti'] ?? '') ?>')"
                                    class="px-3 py-2 bg-blue-600 text-white rounded-lg text-xs font-bold hover:bg-blue-700">
                                <i class="fas fa-eye mr-1"></i> Detail
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="paginationContainer" class="p-4 border-t border-gray-100 flex justify-between items-center">
            <?php if ($current_page > 1): ?>
            <a href="javascript:loadData(<?= $current_page - 1 ?>)" 
               class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-bold">
                <i class="fas fa-chevron-left mr-1"></i> Sebelumnya
            </a>
            <?php else: ?>
            <div></div>
            <?php endif; ?>
            
            <span class="text-sm text-gray-600">Halaman <span id="currentPage"><?= $current_page ?></span> dari <span id="totalPages"><?= $total_pages ?></span></span>
            
            <?php if ($current_page < $total_pages): ?>
            <a href="javascript:loadData(<?= $current_page + 1 ?>)" 
               class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-bold">
                Selanjutnya <i class="fas fa-chevron-right ml-1"></i>
            </a>
            <?php else: ?>
            <div></div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Detail Modal -->
<div id="detailModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-700 to-cyan-600 circuit-pattern relative overflow-hidden px-6 py-4 flex justify-between items-center">
            <h2 class="text-xl font-extrabold text-white">Detail Izin</h2>
            <button onclick="closeModal()" class="text-blue-100 hover:text-white transition text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="p-6">
        <div id="modalContent" class="space-y-4 mb-6">
            <!-- Content will be loaded by JS -->
        </div>

        <form id="actionForm" method="POST" class="flex gap-3 pt-6 border-t border-gray-200">
            <input type="hidden" name="id_izin" id="modal_id_izin">
            <input type="hidden" name="action" id="modal_action">
            
            <button type="button" onclick="closeModal()" class="flex-1 px-4 py-3 bg-gray-200 text-gray-800 rounded-lg font-bold hover:bg-gray-300">
                Batal
            </button>
            <button type="button" id="approveBtn" onclick="submitForm('approve')" style="display:none;" class="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700">
                <i class="fas fa-check-circle mr-2"></i> Approve
            </button>
            <button type="button" id="rejectBtn" onclick="submitForm('reject')" style="display:none;" class="flex-1 px-4 py-3 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700">
                <i class="fas fa-times-circle mr-2"></i> Reject
            </button>
        </form>
        </div>
    </div>
</div>

