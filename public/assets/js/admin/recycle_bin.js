/**
 * recycle_bin.js — Logika halaman Recycle Bin Presensi [Tahap 35]
 */
(function () {
    'use strict';

    const base = ((window.APP_CONFIG && window.APP_CONFIG.baseUrl) || '').replace(/\/$/, '');

    let _pendingAction = null; // { action: 'restore'|'delete', id_bin: N, name: '...' }

    /* ── Buka modal konfirmasi ─────────────────────────── */
    window.confirmBinAction = function (action, idBin, name) {
        _pendingAction = { action, id_bin: idBin, name };

        const header   = document.getElementById('binModalHeader');
        const title    = document.getElementById('binModalTitle');
        const subtitle = document.getElementById('binModalSubtitle');
        const body     = document.getElementById('binModalBody');
        const btn      = document.getElementById('binActionBtn');
        const conflict = document.getElementById('binConflictWarn');
        if (conflict) conflict.classList.add('hidden');

        if (action === 'restore') {
            header.className    = 'px-6 py-5 text-white bg-gradient-to-r from-green-600 to-emerald-500';
            title.innerText     = 'Pulihkan Data';
            subtitle.innerText  = 'Data akan dikembalikan ke tabel presensi';
            body.innerText      = `Data presensi & logbook milik "${name}" akan dipulihkan. Data yang TIDAK bertumbukan dengan entri baru akan dikembalikan. Konflik akan dilaporkan.`;
            btn.className       = 'flex-1 py-3 rounded-xl bg-green-600 text-white font-bold text-sm hover:bg-green-700 transition';
            btn.innerText       = 'Ya, Pulihkan';
        } else {
            header.className    = 'px-6 py-5 text-white bg-gradient-to-r from-red-600 to-rose-500';
            title.innerText     = 'Hapus Permanen';
            subtitle.innerText  = 'Tindakan ini tidak dapat dibatalkan';
            body.innerText      = `Data presensi & logbook arsipan milik "${name}" akan dihapus SECARA PERMANEN. Tidak ada cara untuk memulihkannya setelah ini.`;
            btn.className       = 'flex-1 py-3 rounded-xl bg-red-600 text-white font-bold text-sm hover:bg-red-700 transition';
            btn.innerText       = 'Ya, Hapus Permanen';
        }

        document.getElementById('binActionModal').classList.remove('hidden');
    };

    window.closeBinModal = function () {
        document.getElementById('binActionModal').classList.add('hidden');
        _pendingAction = null;
    };

    /* ── Eksekusi aksi ─────────────────────────────────── */
    window.executeBinAction = function () {
        if (!_pendingAction) return;

        const btn     = document.getElementById('binActionBtn');
        const origTxt = btn.innerText;
        btn.disabled  = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...';

        const { action, id_bin } = _pendingAction; // id_bin is now a hash string
        const endpoint = action === 'restore'
            ? base + '/admin/recycleBinRestore'
            : base + '/admin/recycleBinDelete';

        const fd = new FormData();
        fd.append('id_bin', id_bin); // send hash string directly

        fetch(endpoint, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(function (data) {
                btn.disabled  = false;
                btn.innerText = origTxt;

                if (data.status === 'success') {
                    if (action === 'restore' && data.has_conflict) {
                        // Tampilkan konflik di modal sebelum tutup
                        const conflictWarn = document.getElementById('binConflictWarn');
                        const conflictList = document.getElementById('binConflictList');
                        if (conflictWarn && conflictList) {
                            conflictList.innerHTML = data.conflicts
                                .map(function (c) { return '<li>' + (c.nama_asisten || '') + ' — ' + c.tanggal + '</li>'; })
                                .join('');
                            conflictWarn.classList.remove('hidden');
                        }
                        // Ubah tombol jadi "OK, mengerti"
                        btn.innerText = 'OK, Mengerti';
                        btn.onclick   = function () { closeBinModal(); location.reload(); };
                    } else {
                        closeBinModal();
                        // Hapus baris dari tabel tanpa reload
                        const row = document.getElementById('bin-row-' + id_bin); // id_bin is hash
                        if (row) {
                            row.style.transition = 'opacity .3s';
                            row.style.opacity    = '0';
                            setTimeout(function () { row.remove(); }, 320);
                        } else {
                            location.reload();
                        }
                        if (typeof showCustomAlert === 'function') {
                            showCustomAlert('success',
                                action === 'restore' ? 'Data Dipulihkan' : 'Data Dihapus',
                                action === 'restore'
                                    ? 'Presensi & logbook berhasil dikembalikan.'
                                    : 'Data telah dihapus secara permanen.');
                        }
                    }
                } else {
                    if (typeof showCustomAlert === 'function') {
                        showCustomAlert('error', 'Gagal', data.message || 'Terjadi kesalahan.');
                    }
                }
            })
            .catch(function () {
                btn.disabled  = false;
                btn.innerText = origTxt;
                if (typeof showCustomAlert === 'function') {
                    showCustomAlert('error', 'Error', 'Koneksi ke server gagal.');
                }
            });
    };

    /* ── Pencarian nama (instan) + filter jabatan & angkatan ──────
       Client-side murni (bukan AJAX) karena jumlah entri recycle bin
       biasanya kecil - cukup hide/show baris <tr> berdasarkan
       data-name/data-jabatan/data-angkatan yang sudah dirender PHP. */
    (function initRecycleBinFilters() {
        var searchInput   = document.getElementById('binSearchInput');
        var jabatanFilter = document.getElementById('binJabatanFilter');
        var angkatanFilter= document.getElementById('binAngkatanFilter');
        var tbody         = document.getElementById('binTableBody');
        var noResultEl    = document.getElementById('binNoFilterResult');
        var countEl       = document.getElementById('binVisibleCount');

        if (!tbody) return; // recycle bin kosong - tidak ada tabel untuk difilter

        function applyFilters() {
            var query   = (searchInput && searchInput.value || '').trim().toLowerCase();
            var jabatan = jabatanFilter ? jabatanFilter.value : '';
            var angkatan= angkatanFilter ? angkatanFilter.value : '';
            var rows    = tbody.querySelectorAll('tr[data-name]');
            var visible = 0;

            rows.forEach(function (row) {
                var matchesName     = !query || row.dataset.name.indexOf(query) !== -1;
                var matchesJabatan  = !jabatan || row.dataset.jabatan === jabatan;
                var matchesAngkatan = !angkatan || row.dataset.angkatan === angkatan;
                var show = matchesName && matchesJabatan && matchesAngkatan;
                row.classList.toggle('hidden', !show);
                if (show) visible++;
            });

            if (noResultEl) noResultEl.classList.toggle('hidden', visible !== 0);
            if (countEl) countEl.innerText = visible;
        }

        if (searchInput)    searchInput.addEventListener('input', applyFilters);
        if (jabatanFilter)  jabatanFilter.addEventListener('change', applyFilters);
        if (angkatanFilter) angkatanFilter.addEventListener('change', applyFilters);
    })();

    /* ── Jam live di header ───────────────────────────────────
       Update #liveDate dan #liveTime setiap detik, sama seperti
       halaman Logbook / Dashboard sehingga tampilan konsisten. */
    (function initRecycleBinClock() {
        function tick() {
            var now   = new Date();
            var dOpts = { day: '2-digit', month: 'long', year: 'numeric' };
            var tOpts = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
            var elDate = document.getElementById('liveDate');
            var elTime = document.getElementById('liveTime');
            if (elDate) elDate.innerText = now.toLocaleDateString('id-ID', dOpts);
            if (elTime) elTime.innerText = now.toLocaleTimeString('id-ID', tOpts).replace(/\./g, ':');
        }
        setInterval(tick, 1000);
        tick();
    })();
})();
