let currentUserId = null;
    let currentUserName = '';
    let currentResetId = null;
    let currentResetType = null;

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

    document.getElementById('searchAssistant').addEventListener('keyup', function() {
        const key = this.value.toLowerCase();
        document.querySelectorAll('.assistant-card').forEach(card => {
            card.style.display = card.dataset.name.includes(key) ? 'flex' : 'none';
        });
    });

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

    function loadLogs(userId, name, photo, el) {
        currentUserId = userId; currentUserName = name;
        document.querySelectorAll('.assistant-card').forEach(c => c.classList.remove('active'));
        el.classList.add('active');
        document.getElementById('headerName').innerText = name;
        document.getElementById('headerAvatar').src = photo ? `${window.APP_CONFIG.baseUrl}/uploads/profile/${photo}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=random`;
        document.getElementById('inputUserId').value = userId;
        document.getElementById('emptyState').classList.add('hidden');
        document.getElementById('logContent').classList.remove('hidden');
        // [BARU – Tahap 35] Tampilkan stats bar
        const statsBar = document.getElementById('liveStatsBar');
        if (statsBar) statsBar.classList.remove('hidden');
        setTimeout(() => document.getElementById('logContent').classList.remove('opacity-0'), 50);

        const fd = new FormData(); fd.append('user_id', userId);
        fetch(`${window.APP_CONFIG.baseUrl}/admin/getLogsByUser`, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => renderTable(data));
    }

    function renderTable(logs) {
        const tbody = document.getElementById('logsTableBody');
        tbody.innerHTML = '';

        if(logs.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-8 text-center text-gray-400 italic text-sm">Belum ada data.</td></tr>`;
            updateLiveStats(0, 0, 0);
            return;
        }

        // Hitung stats sebelum render
        let cHadir = 0, cIzin = 0, cAlpha = 0;
        logs.forEach(log => {
            if (log.status === 'Hadir' || log.status === 'Terlambat') cHadir++;
            else if (log.status === 'Izin' || log.status === 'Sakit')  cIzin++;
            else cAlpha++;
        });
        updateLiveStats(cHadir, cIzin, cAlpha);

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

            const timeDisplay = (log.status == 'Hadir' && log.time_in && log.time_in !== '-') 
                ? `<div class="text-center"><div class="text-blue-600 font-bold text-xs">${log.time_in}</div><div class="text-orange-600 font-bold text-[10px]">${log.time_out || '—'}</div></div>`
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

    document.getElementById('logForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fetch(`${window.APP_CONFIG.baseUrl}/admin/saveLogbookAdmin`, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                closeLogModal();
                const activeCard = document.querySelector('.assistant-card.active');
                loadLogs(currentUserId, currentUserName, null, activeCard);
                // [BARU – Tahap 35] Refresh stat di detail modal dashboard jika sedang terbuka
                refreshDashboardStats(currentUserId);
            } else showCustomAlert('error', 'Gagal', data.message);
        });
    });

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
    document.getElementById('confirmResetBtn').addEventListener('click', function() {
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
                // Refresh data logbook (Panggil fungsi loadLogs yang sudah ada di dashboard Anda)
                // Pastikan variabel currentUserId dan currentUserName tersedia/global
                if (typeof loadLogs === "function" && typeof currentUserId !== 'undefined') {
                    const activeCard = document.querySelector('.assistant-card.active');
                    loadLogs(currentUserId, currentUserName, null, activeCard);
                    // [BARU – Tahap 35] Refresh stat di detail modal dashboard jika sedang terbuka
                    refreshDashboardStats(currentUserId);
                } else {
                    location.reload(); // Fallback jika fungsi loadLogs tidak ada
                }
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

    document.getElementById('confirmResetBtn').addEventListener('click', function() {
        const mode = document.querySelector('input[name="resetMode"]:checked').value;
        const btn = this; btn.innerHTML = 'Memproses...'; btn.disabled = true;
        const fd = new FormData();
        fd.append('id_ref', currentResetId); fd.append('type', currentResetType); fd.append('mode', mode);

        fetch(`${window.APP_CONFIG.baseUrl}/admin/reset_logbook`, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            closeResetModal();
            btn.innerHTML = 'Proses'; btn.disabled = false;
            const activeCard = document.querySelector('.assistant-card.active');
            loadLogs(currentUserId, currentUserName, null, activeCard);
        });
    });

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
