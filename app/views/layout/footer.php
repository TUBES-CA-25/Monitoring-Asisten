</main> 

    <footer class="bg-white border-t border-gray-200 py-6 px-8 mt-auto shrink-0 z-10 relative">
        <div class="flex flex-col md:flex-row justify-between text-center items-center gap-4">
            <p class="text-xs text-gray-400 font-medium">
                &copy; <?= date('Y') ?> Integrated Computer Laboratory System FIKOM UMI. All rights reserved.
            </p>
        </div>
    </footer>

</div>

<!-- [BARU - Patch 5 V3] Global Modal System.
     Tersedia di SEMUA halaman yang memuat layout/footer ini, lewat fungsi
     global window.showCustomAlert(type, title, message) (didefinisikan di
     public/assets/js/global.js). Tujuannya menggantikan alert() bawaan
     browser yang masih dipakai di beberapa halaman (logbook, dll).
     ID elemen diberi prefix "global" agar tidak bertabrakan dengan halaman
     yang SUDAH memiliki modal #customAlertModal sendiri (mis. admin/
     schedule.php, user/schedule.php) - pada halaman tersebut, fungsi
     showCustomAlert milik halaman akan menimpa (override) versi global ini
     dan tetap memakai modal mereka sendiri. -->
<div id="globalAlertModal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity opacity-0" id="globalAlertBackdrop"></div>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative z-10 overflow-hidden transform scale-90 opacity-0 transition-all duration-300 flex flex-col items-center p-6 text-center" id="globalAlertContent">
        <div id="globalAlertIconBg" class="w-16 h-16 rounded-full flex items-center justify-center mb-4"><i id="globalAlertIcon" class="fas text-3xl"></i></div>
        <h3 id="globalAlertTitle" class="text-xl font-extrabold text-gray-800 mb-2"></h3>
        <p id="globalAlertMessage" class="text-sm text-gray-500 mb-6 px-2"></p>
        <button onclick="closeCustomAlert()" class="w-full py-3 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02]" id="globalAlertBtn">OK</button>
    </div>
</div>

<?php if (!empty($js_config)): ?>
<script>
    // "Data island": konfigurasi/data dari PHP untuk dipakai oleh script halaman
    // (public/assets/js/...) tanpa perlu menyisipkan PHP di file JS terpisah.
    window.APP_CONFIG = <?= json_encode($js_config) ?>;
</script>
<?php endif; ?>

<?php if (!empty($vendor_js)): ?>
    <?php /* vendor_js: library pihak ketiga (Chart.js, QRCode.js, dll.)
             Dimuat SEBELUM page_js agar tersedia saat page script dijalankan */ ?>
    <?php foreach ($vendor_js as $vendorFile): ?>
        <script src="<?= $vendorFile ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($page_js)): ?>
    <?php foreach ($page_js as $jsFile): ?>
        <script src="<?= $jsFile ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($js)): ?>
    <script src="<?= ASSET_URL ?>/js/<?= $js ?>"></script>
<?php endif; ?>

<?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
<!-- [BARU – Tahap 32] Modal Reset Global tersedia di semua halaman Admin -->
<div id="globalResetModal" class="hidden fixed inset-0 z-[9998] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm" onclick="closeGlobalResetModal()"></div>
    <div class="relative bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden z-10">
        <div class="bg-gradient-to-r from-red-600 to-rose-500 px-6 py-5 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center shrink-0"><i class="fas fa-exclamation-triangle text-white"></i></div>
            <div class="text-white">
                <h3 class="font-extrabold text-lg leading-tight">Reset Semua Presensi</h3>
                <p class="text-red-100 text-[11px]">Tindakan ini tidak dapat dibatalkan</p>
            </div>
        </div>
        <div class="p-6 space-y-4">
            <div class="bg-red-50 border border-red-200 rounded-2xl p-4 text-sm text-red-700">
                <p class="font-bold flex items-center gap-2 mb-2"><i class="fas fa-database"></i> Yang akan terhapus:</p>
                <ul class="list-disc ml-5 text-xs text-red-600 space-y-1"><li>Seluruh <strong>presensi</strong> semua asisten</li><li>Seluruh <strong>logbook</strong> semua asisten</li></ul>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-2xl p-3 text-xs text-green-700">
                <p class="font-bold"><i class="fas fa-shield-alt mr-1"></i> Tidak terpengaruh: Jadwal, data user, izin. File ZIP rekap terunduh otomatis.</p>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1.5 uppercase">Ketik <span class="text-red-600 font-mono">RESET</span> untuk konfirmasi:</label>
                <input type="text" id="globalResetConfirmInput" placeholder='Ketik "RESET"' oninput="(function(v){var b=document.getElementById('btnConfirmGlobalReset');if(!b)return;var ok=v.trim()==='RESET';b.disabled=!ok;b.className=ok?'flex-1 py-3 rounded-xl bg-red-600 text-white font-bold text-sm hover:bg-red-700 transition cursor-pointer':'flex-1 py-3 rounded-xl bg-red-200 text-red-400 font-bold text-sm transition cursor-not-allowed';})(this.value)"
                       class="w-full px-4 py-2.5 border-2 border-gray-200 focus:border-red-400 rounded-xl text-sm outline-none font-mono transition">
            </div>
            <div class="flex gap-3">
                <button onclick="closeGlobalResetModal()" class="flex-1 py-3 rounded-xl bg-gray-100 text-gray-600 font-bold text-sm hover:bg-gray-200 transition">Batal</button>
                <button id="btnConfirmGlobalReset" onclick="executeGlobalReset()" disabled class="flex-1 py-3 rounded-xl bg-red-200 text-red-400 font-bold text-sm transition cursor-not-allowed"><i class="fas fa-database mr-2"></i>Ya, Reset</button>
            </div>
        </div>
    </div>
</div>
<script>
window.openGlobalResetModal = window.openGlobalResetModal || function() {
    var inp = document.getElementById('globalResetConfirmInput');
    var btn = document.getElementById('btnConfirmGlobalReset');
    if(inp) inp.value='';
    if(btn){btn.disabled=true;btn.className='flex-1 py-3 rounded-xl bg-red-200 text-red-400 font-bold text-sm transition cursor-not-allowed';}
    document.getElementById('globalResetModal')?.classList.remove('hidden');
};
window.closeGlobalResetModal = window.closeGlobalResetModal || function() {
    document.getElementById('globalResetModal')?.classList.add('hidden');
    var b=document.getElementById('resetGlobalBtn');
    if(b){delete b.dataset.unlocked;b.classList.remove('bg-red-50','text-red-600','border-red-200');b.classList.add('bg-gray-100','text-gray-400','border-gray-200');b.querySelector('.reset-lock')?.classList.remove('hidden');b.querySelector('.reset-label')?.classList.add('hidden');}
};
window.executeGlobalReset = window.executeGlobalReset || function() {
    var base=(window.APP_CONFIG&&(window.APP_CONFIG.baseUrl||window.APP_CONFIG.BASE_URL))||'';
    base = base.replace(/\/$/, '');
    closeGlobalResetModal();

    // [DIUBAH – Tahap 35] Gunakan resetToBin: data masuk recycle bin,
    // bukan langsung dihapus + ZIP. Lebih aman, bisa di-restore.
    fetch(base + '/admin/resetToBin?scope=all', { method: 'GET' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                if (typeof showCustomAlert === 'function')
                    showCustomAlert('success', 'Reset Berhasil',
                        'Data presensi semua asisten telah diarsipkan ke Recycle Bin. Anda bisa lihat, unduh, pulihkan, atau hapus permanen di menu Recycle Bin.');
            } else {
                if (typeof showCustomAlert === 'function')
                    showCustomAlert('error', 'Gagal', data.message || 'Terjadi kesalahan.');
            }
        })
        .catch(function() {
            if (typeof showCustomAlert === 'function')
                showCustomAlert('error', 'Gagal', 'Koneksi ke server terputus.');
        });
};
</script>
<?php endif; ?>

</body>
</html>