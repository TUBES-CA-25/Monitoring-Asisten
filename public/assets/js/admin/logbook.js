(() => {
    "use strict";

    // [PENTING] Semua state & fungsi dibungkus dalam IIFE ini (bukan top-level
    // let/const) karena script ini dimuat ulang setiap kali halaman logbook
    // dikunjungi via AJAX navigation (lihat global.js _iclabsLoadPageScripts).
    // Sebelumnya `currentUserId` dideklarasikan dua kali sebagai top-level let
    // (baris paling atas & di dekat loadUserLogsPage) — ini SyntaxError
    // ("Identifier has already been declared") yang membuat SELURUH script
    // gagal dimuat, sehingga klik asisten tidak menampilkan apa pun.
    let currentUserId = null;
    let currentUserName = '';
    let currentResetId = null;
    let currentResetType = null;
    let currentLogPage = 1;
    let currentPerPage = 30;

    // [BARU – Tahap 35] Update stats bar atas tabel setelah setiap renderTable()
    function updateLiveStats(hadir, izin, alpha) {
        const el = document.getElementById('liveStatsBar');
        if (!el) return;
        const ch = document.getElementById('countHadir');
        const ci = document.getElementById('countIzin');
        const ca = document.getElementById('countAlpha');
        if (ch) ch.innerText = hadir;
        if (ci) ci.innerText = izin;
        if (ca) ca.innerText = alpha;
    }

    const searchInput = document.getElementById('searchAssistant');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const key = this.value.toLowerCase();
            document.querySelectorAll('.assistant-card').forEach(card => {
                card.style.display = card.dataset.name.includes(key) ? 'flex' : 'none';
            });
        });
    }

    function toggleTimeFields() {
        const status = document.getElementById('inputStatus').value;
        const timeFields = document.getElementById('timeFields');
        const proofOutContainer = document.getElementById('proofOutContainer');
        const labelProofMain = document.getElementById('labelProofMain');

        if (status === 'Hadir') {
            timeFields.classList.remove('hidden'); timeFields.classList.add('grid');
            proofOutContainer.classList.remove('hidden');
            labelProofMain.innerText = "Upload Bukti Datang";
        } else {
            timeFields.classList.add('hidden'); timeFields.classList.remove('grid');
            proofOutContainer.classList.add('hidden');
            labelProofMain.innerText = "Upload Bukti Izin/Sakit";
        }
    }

    // Ganti asisten yang dipilih (klik kartu di daftar) — reset ke halaman 1
    // lalu ambil data logbook lengkap sejak akun asisten dibuat (real-time,
    // bukan dibatasi 30 hari — batasan 30 hanya jumlah baris per halaman).
    function loadLogs(userId, name, photo, el) {
        currentUserId = userId; currentUserName = name;
        currentLogPage = 1;
        document.querySelectorAll('.assistant-card').forEach(c => c.classList.remove('active'));
        if (el) el.classList.add('active');
        document.getElementById('headerName').innerText = name;
        document.getElementById('headerAvatar').src = photo ? `${window.APP_CONFIG.baseUrl}/uploads/profile/${photo}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=random`;
        document.getElementById('inputUserId').value = userId;
        document.getElementById('emptyState').classList.add('hidden');
        document.getElementById('logContent').classList.remove('hidden');
        // [BARU – Tahap 35] Tampilkan stats bar
        const statsBar = document.getElementById('liveStatsBar');
        if (statsBar) statsBar.classList.remove('hidden');
        setTimeout(() => document.getElementById('logContent').classList.remove('opacity-0'), 50);

        fetchLogs();
    }

    // Ambil ulang data logbook untuk asisten yang SEDANG aktif, pada halaman
    // yang sedang dipilih. Dipakai oleh loadLogs() (asisten baru) maupun
    // loadUserLogsPage() (pindah halaman pagination untuk asisten yang sama).
    function fetchLogs() {
        const fd = new FormData();
        fd.append('user_id', currentUserId);
        fd.append('page',     currentLogPage);
        fd.append('per_page', currentPerPage);
        fetch(`${window.APP_CONFIG.baseUrl}/admin/getLogsByUser`, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(resp => {
            // resp = { logs, total, hadir, izin, alpha, page, per_page, total_pages }
            updateLiveStats(resp.hadir || 0, resp.izin || 0, resp.alpha || 0);
            renderTable(resp.logs || []);
            renderLogbookPagination(resp.total_pages || 1, resp.page || 1, resp.total || 0);
        });
    }

    // [FIX] Sebelumnya memanggil `loadUserLogs(currentUserId)` — fungsi yang
    // tidak pernah ada di file ini — sehingga klik tombol pagination gagal
    // total (ReferenceError). Diganti memakai fetchLogs() di atas.
    function loadUserLogsPage(page) {
        currentLogPage = page;
        fetchLogs();
    }

    function renderLogbookPagination(totalPages, currentPage, totalItems) {
        const container = document.getElementById('logPaginationBar');
        if (!container) return;
        if (totalPages <= 1) { container.innerHTML = ''; container.classList.add('hidden'); return; }

        container.classList.remove('hidden');
        let html = `<div class="flex items-center gap-2 flex-wrap justify-center py-2 text-xs">`;
        html += `<span class="text-gray-400">${totalItems} entri</span>`;
        html += `<div class="flex gap-1">`;

        if (currentPage > 1)
            html += `<button onclick="loadUserLogsPage(${currentPage-1})" class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 font-bold text-gray-600"><i class="fas fa-chevron-left text-[10px]"></i></button>`;

        const maxButtons = 7;
        let startPage = Math.max(1, currentPage - Math.floor(maxButtons/2));
        let endPage   = Math.min(totalPages, startPage + maxButtons - 1);
        if (endPage - startPage < maxButtons - 1) startPage = Math.max(1, endPage - maxButtons + 1);

        if (startPage > 1) html += `<button onclick="loadUserLogsPage(1)" class="px-3 py-1.5 rounded-lg bg-gray-100 font-bold text-gray-600">1</button><span class="px-1 text-gray-400">…</span>`;
        for (let p = startPage; p <= endPage; p++) {
            const active = p === currentPage ? 'bg-blue-600 text-white shadow' : 'bg-gray-100 hover:bg-gray-200 text-gray-600';
            html += `<button onclick="loadUserLogsPage(${p})" class="px-3 py-1.5 rounded-lg ${active} font-bold">${p}</button>`;
        }
        if (endPage < totalPages) html += `<span class="px-1 text-gray-400">…</span><button onclick="loadUserLogsPage(${totalPages})" class="px-3 py-1.5 rounded-lg bg-gray-100 font-bold text-gray-600">${totalPages}</button>`;

        if (currentPage < totalPages)
            html += `<button onclick="loadUserLogsPage(${currentPage+1})" class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 font-bold text-gray-600"><i class="fas fa-chevron-right text-[10px]"></i></button>`;

        html += `</div></div>`;
        container.innerHTML = html;
    }

    function renderTable(logs) {
        const tbody = document.getElementById('logsTableBody');
        tbody.innerHTML = '';
        if(logs.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-8 text-center text-gray-400 italic text-sm">Belum ada data logbook untuk rentang ini.</td></tr>`;
            return;
        }

        logs.forEach(log => {
            const dateStr = new Date(log.date).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'});

            let badgeClass = 'bg-gray-100 text-gray-600';
            let borderColor = 'border-gray-300';
            if(log.color == 'green') { badgeClass = 'bg-green-100 text-green-600'; borderColor = 'border-green-300'; }
            else if(log.color == 'yellow') { badgeClass = 'bg-yellow-100 text-yellow-600'; borderColor = 'border-yellow-300'; }
            else if(log.color == 'red') { badgeClass = 'bg-red-100 text-red-600'; borderColor = 'border-red-300'; }

            // Bukti Datang
            let proofBtn = '<span class="text-gray-300 text-[10px]">-</span>';
            if(log.proof_in) {
                const folder = (log.status == 'Hadir') ? 'attendance' : 'leaves';
                proofBtn = `<button onclick="viewEvidence('Bukti', '${window.APP_CONFIG.baseUrl}/uploads/${folder}/${log.proof_in}')" class="px-2 py-1 bg-blue-50 text-blue-600 border border-blue-200 rounded text-[10px] font-bold hover:bg-blue-100 transition"><i class="fas fa-image text-[10px] mr-1"></i>Lihat</button>`;
            }

            // Bukti Pulang (hanya untuk Hadir)
            let proofOutBtn = '<span class="text-gray-300 text-[10px]">-</span>';
            if(log.status == 'Hadir') {
                if(log.proof_out) {
                    proofOutBtn = `<button onclick="viewEvidence('Bukti Pulang', '${window.APP_CONFIG.baseUrl}/uploads/attendance/${log.proof_out}')" class="px-2 py-1 bg-orange-50 text-orange-600 border border-orange-200 rounded text-[10px] font-bold hover:bg-orange-100 transition"><i class="fas fa-image text-[10px] mr-1"></i>Lihat</button>`;
                } else {
                    proofOutBtn = '<span class="text-red-400 text-[10px] italic">Belum</span>';
                }
            }

            // Bukti Izin (untuk Izin/Sakit)
            let proofIzinBtn = '<span class="text-gray-300 text-[10px]">-</span>';
            if(['Izin', 'Sakit'].includes(log.status) && log.proof_izin) {
                proofIzinBtn = `<button onclick="viewEvidence('Bukti ${log.status}', '${window.APP_CONFIG.baseUrl}/uploads/leaves/${log.proof_izin}')" class="px-2 py-1 bg-orange-50 text-orange-600 border border-orange-200 rounded text-[10px] font-bold hover:bg-orange-100 transition"><i class="fas fa-file-pdf text-[10px] mr-1"></i>Lihat</button>`;
            }

            // [BARU] Tanda terlambat/tepat waktu di samping jam masuk, dan
            // pulang lebih cepat (kuning) di samping jam pulang - late_minutes
            // & is_early_checkout disiapkan LogbookModel::getUnifiedLogbook().
            const inMark = (log.late_minutes > 0)
                ? `<i class="fas fa-clock text-red-500 text-[9px] ml-1" title="Terlambat ${log.late_minutes} menit"></i>`
                : `<i class="fas fa-check-circle text-green-500 text-[9px] ml-1" title="Tepat Waktu"></i>`;
            const outMark = log.is_early_checkout
                ? `<i class="fas fa-door-open text-yellow-500 text-[9px] ml-1" title="Pulang Lebih Cepat"></i>`
                : '';

            // [DIPERBAIKI] Sebelumnya syarat "log.status == 'Hadir'" membuat
            // jam masuk/pulang TIDAK PERNAH tampil untuk status "Terlambat"
            // (padahal keduanya sama-sama presensi asli dengan jam sungguhan
            // - hanya "Hadir"/"Terlambat" yang membedakan tepat waktu atau
            // tidak). Cukup cek time_in tersedia (cocok dengan cara
            // LogbookModel/PHP view lain menentukan baris ini py data jam).
            const timeDisplay = (log.time_in && log.time_in !== '-')
                ? `<div class="text-center">
                     <div class="text-blue-600 font-bold text-xs">${log.time_in}${inMark}</div>
                     <div class="text-orange-600 font-bold text-[10px]">${log.time_out && log.time_out !== '-' ? log.time_out : '—'}${(log.time_out && log.time_out !== '-') ? outMark : ''}</div>
                   </div>`
                : '<span class="text-gray-400 text-xs">—</span>';

            const actionBtns = `
                <div class="flex justify-center items-center gap-1">
                    <button onclick='openEditModal(${JSON.stringify(log)}, "edit")' class="p-1.5 text-blue-500 hover:bg-blue-50 rounded-lg transition" title="Edit"><i class="fas fa-pen text-xs"></i></button>
                    ${log.status != 'Alpha' ? `<button onclick="confirmReset('${log.id_ref}', '${log.status}')" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition" title="Hapus"><i class="fas fa-trash-alt text-xs"></i></button>` : ''}
                </div>
            `;

            const row = `
                <tr class="border-l-4 ${borderColor.replace('border','border-l').split(' ')[0]} hover:bg-gray-50 transition">
                    <td class="px-4 py-3">
                        <div class="font-bold text-gray-800 text-xs">${dateStr}</div>
                        <span class="inline-block text-[9px] uppercase font-bold px-2 py-0.5 rounded mt-1 ${badgeClass}">${log.status}</span>
                    </td>
                    <td class="px-4 py-3 text-center">${timeDisplay}</td>
                    <td class="px-4 py-3 text-center">${proofBtn}</td>
                    <td class="px-4 py-3 text-center">${proofOutBtn}</td>
                    <td class="px-4 py-3 text-center">${proofIzinBtn}</td>
                    <td class="px-4 py-3">
                        <p class="text-xs text-gray-700 line-clamp-2" title="${log.activity || 'Tidak ada catatan'}">${log.activity || '<span class="text-gray-400 italic">Tidak ada catatan</span>'}</p>
                    </td>
                    <td class="px-4 py-3 text-center">${actionBtns}</td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    }

    function openEditModal(log, mode) {
        document.getElementById('logModal').classList.remove('hidden');
        document.getElementById('inputUserId').value = currentUserId;

        if(mode == 'add') {
            document.getElementById('modalTitle').innerText = 'Tambah Log Manual';
            document.getElementById('logForm').reset();
            document.getElementById('inputStatus').value = 'Hadir';
        } else {
            document.getElementById('modalTitle').innerText = 'Edit ' + log.date;
            document.getElementById('inputDate').value = log.date;

            let status = log.status;
            if(['Izin','Sakit'].includes(status)) document.getElementById('inputStatus').value = status;
            else if(status == 'Hadir') document.getElementById('inputStatus').value = 'Hadir';
            else document.getElementById('inputStatus').value = 'Alpha';

            document.getElementById('inputIn').value = (log.time_in !== '-' && status=='Hadir') ? log.time_in : '';
            document.getElementById('inputOut').value = (log.time_out !== '-' && status=='Hadir') ? log.time_out : '';

            let cleanActivity = log.activity.replace('Tidak Hadir (Alpha)', '').replace(' (Pengajuan Izin)', '');
            document.getElementById('inputActivity').value = cleanActivity;
        }
        toggleTimeFields();
    }

    function closeLogModal() { document.getElementById('logModal').classList.add('hidden'); }

    const logForm = document.getElementById('logForm');
    if (logForm) {
        logForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            fetch(`${window.APP_CONFIG.baseUrl}/admin/saveLogbookAdmin`, { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    closeLogModal();
                    fetchLogs();
                    // [BARU – Tahap 35] Refresh stat di detail modal dashboard jika sedang terbuka
                    refreshDashboardStats(currentUserId);
                } else showCustomAlert('error', 'Gagal', data.message);
            });
        });
    }

    // Fungsi Membuka Modal
    function confirmReset(idRef, type) {
        currentResetId = idRef;
        currentResetType = type;

        const modal = document.getElementById('resetModal');
        modal.classList.remove('hidden');

        // Reset state tombol & input
        document.getElementById('confirmResetBtn').disabled = false;
        document.getElementById('confirmResetBtn').innerHTML = 'Proses';

        // Jika Tipe Izin, paksa ke mode 'full' (karena izin tidak punya logbook parsial)
        const radios = document.getElementsByName('resetMode');
        if (type === 'Izin') {
            radios[0].disabled = true; // Disable Partial
            radios[1].checked = true;  // Auto select Full
            radios[0].parentElement.classList.add('opacity-50', 'pointer-events-none');
        } else {
            radios[0].disabled = false;
            radios[0].checked = true;
            radios[0].parentElement.classList.remove('opacity-50', 'pointer-events-none');
        }
    }

    // Fungsi Menutup Modal
    function closeResetModal() {
        document.getElementById('resetModal').classList.add('hidden');
    }

    // Event Listener Tombol Proses
    // [FIX] Sebelumnya listener ini didaftarkan DUA KALI (duplikat persis),
    // menyebabkan setiap klik memicu dua request reset sekaligus.
    const confirmResetBtn = document.getElementById('confirmResetBtn');
    if (confirmResetBtn) {
        confirmResetBtn.addEventListener('click', function() {
            const mode = document.querySelector('input[name="resetMode"]:checked').value;
            const btn = this;

            // UI Loading
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            btn.disabled = true;

            const fd = new FormData();
            fd.append('id_ref', currentResetId);
            fd.append('type', currentResetType);
            fd.append('mode', mode);

            fetch(`${window.APP_CONFIG.baseUrl}/admin/reset_logbook`, {
                method: 'POST',
                body: fd
            })
            .then(res => {
                if (!res.ok) throw new Error("Server Error");
                return res.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    closeResetModal();
                    fetchLogs();
                    // [BARU – Tahap 35] Refresh stat di detail modal dashboard jika sedang terbuka
                    refreshDashboardStats(currentUserId);
                } else {
                    showCustomAlert('error', 'Gagal', data.message || 'Gagal menghapus data.');
                    btn.innerHTML = 'Coba Lagi';
                    btn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                showCustomAlert('error', 'Kesalahan Koneksi', 'Terjadi kesalahan koneksi.');
                btn.innerHTML = 'Proses';
                btn.disabled = false;
            });
        });
    }

    function viewEvidence(type, url) {
        const ext = url.split('.').pop().toLowerCase();
        const img = document.getElementById('modalImg');
        const frame = document.getElementById('modalFrame');
        document.getElementById('downloadLink').href = url;
        document.getElementById('proofTitle').innerText = 'Bukti ' + type;
        document.getElementById('photoModal').classList.remove('hidden');
        if(ext === 'pdf') { img.classList.add('hidden'); frame.classList.remove('hidden'); frame.src = url; }
        else { frame.classList.add('hidden'); img.classList.remove('hidden'); img.src = url; }
    }
    function closePhoto() { document.getElementById('photoModal').classList.add('hidden'); }

    function updateClock() {
        const now = new Date();
        const dateOptions = { day: '2-digit', month: 'long', year: 'numeric' };
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };

        const elDate = document.getElementById('liveDate');
        const elTime = document.getElementById('liveTime');

        if (elDate) elDate.innerText = now.toLocaleDateString('id-ID', dateOptions);
        if (elTime) elTime.innerText = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':');
    }
    setInterval(updateClock, 1000); updateClock();

    // [BARU – Tahap 35] Refresh angka stat di detail modal dashboard jika sedang
    // terbuka. Dipanggil setelah setiap perubahan data logbook.
    function refreshDashboardStats(userId) {
        if (!userId) return;
        // Hanya jalankan jika elemen stat ada di DOM (halaman dashboard)
        const statHadir = document.getElementById('stat_hadir');
        const statIzin  = document.getElementById('stat_izin');
        const statAlpa  = document.getElementById('stat_alpa');
        if (!statHadir || !statIzin || !statAlpa) return;

        const fd = new FormData();
        fd.append('user_id', userId);
        fetch(`${window.APP_CONFIG.baseUrl}/admin/getAssistantStats`, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                statHadir.innerText = data.total_hadir;
                statIzin.innerText  = data.total_izin;
                statAlpa.innerText  = data.total_alpa;
                // Perbarui grafik modal jika fungsi tersedia
                if (typeof initModalChart === 'function' && typeof currentModalChartType !== 'undefined') {
                    if (typeof currentStatsData !== 'undefined') {
                        currentStatsData = {
                            hadir: data.total_hadir,
                            izin:  data.total_izin,
                            alpa:  data.total_alpa
                        };
                    }
                    initModalChart(currentModalChartType);
                }
            }
        })
        .catch(() => {}); // Fail silently — stats bar di logbook page sudah update
    }

    // Ekspos ke window: dipanggil dari onclick="" statis di view maupun dari
    // HTML yang di-generate secara dinamis (renderTable/renderLogbookPagination)
    // — atribut onclick selalu di-resolve di scope global, bukan scope IIFE ini.
    window.loadLogs           = loadLogs;
    window.loadUserLogsPage   = loadUserLogsPage;
    window.openEditModal      = openEditModal;
    window.closeLogModal      = closeLogModal;
    window.confirmReset       = confirmReset;
    window.closeResetModal    = closeResetModal;
    window.viewEvidence       = viewEvidence;
    window.closePhoto         = closePhoto;
    window.toggleTimeFields   = toggleTimeFields;
})();
