// ==========================================
    // 1. LOGIKA QR CODE (REAL TIME)
    // ==========================================
    const qrDataIn = window.APP_CONFIG.qrIn || '';
    const qrDataOut = window.APP_CONFIG.qrOut || '';
    const qrcodeContainer = document.getElementById("qrcode");
    
    // Inisialisasi QR Code jika elemen ada
    let qrCodeObj = null;
    if (qrcodeContainer) {
        qrCodeObj = new QRCode(qrcodeContainer, { width: 200, height: 200 });
    }

    let qrInterval = null;
    let currentMode = 'check_in';

    const roleSegment = window.location.href.includes('kepalalab') ? 'kepalalab' : 'admin';
    const qrFetchUrl = `${window.APP_CONFIG.baseUrl}/${roleSegment}/getQrAjax`;

    function openQRModal() {
        document.getElementById('qrModal').classList.remove('hidden');
        setTimeout(() => { 
            document.getElementById('qrContent').classList.remove('scale-95', 'opacity-0'); 
            document.getElementById('qrContent').classList.add('scale-100', 'opacity-100'); 
        }, 10);
        
        if(currentMode === 'check_in' && qrDataIn) {
        qrCodeObj.makeCode(qrDataIn);
    }

        setQRMode(true); 
    }

    function closeQRModal() {
        document.getElementById('qrContent').classList.remove('scale-100', 'opacity-100'); 
        document.getElementById('qrContent').classList.add('scale-95', 'opacity-0');
        setTimeout(() => { document.getElementById('qrModal').classList.add('hidden'); }, 300);
        
        if (qrInterval) clearInterval(qrInterval);
    }

    function fetchAndRenderQR(type) {
        const fd = new FormData();
        fd.append('type', type);

        fetch(qrFetchUrl, { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && qrCodeObj) {
                    qrCodeObj.clear();
                    qrCodeObj.makeCode(data.qr_data);
                }
            })
            .catch(err => console.error("Gagal refresh QR:", err));
    }

    function setQRMode(isEntry) {
        const title = document.getElementById('qrTitle'); 
        const toggle = document.getElementById('qrToggleBtn');
        const container = title.parentElement;

        if (qrInterval) clearInterval(qrInterval);

        if (isEntry) {
            currentMode = 'check_in';
            title.innerText = "SCAN UNTUK MASUK";
            container.className = "bg-indigo-600 p-6 text-center transition-colors duration-300";
            toggle.style.transform = "translateX(0)";
            
            fetchAndRenderQR('check_in');
            // Refresh QR Masuk setiap 3 menit
            qrInterval = setInterval(() => { fetchAndRenderQR('check_in'); }, 180000);
        } else {
            currentMode = 'check_out';
            title.innerText = "SCAN UNTUK PULANG";
            container.className = "bg-red-600 p-6 text-center transition-colors duration-300";
            toggle.style.transform = "translateX(100%)";
            
            fetchAndRenderQR('check_out');
            // Refresh QR Pulang setiap 3 menit
            qrInterval = setInterval(() => { fetchAndRenderQR('check_out'); }, 180000);
        }
    }

    let modalChartInstance = null;
    let currentModalChartType = 'doughnut'; // Default
    let currentStatsData = { hadir: 0, izin: 0, alpa: 0 }; // Simpan data sementara

    function openDetailModal(user) {
        const modal = document.getElementById('detailModal');
        const backdrop = document.getElementById('detailBackdrop');
        const content = document.getElementById('detailContent');
        
        // 1. MAPPING DATA TEKS (Ke Kolom Kiri)
        document.getElementById('m_name').innerText = user.name;
        document.getElementById('m_position').innerText = user.jabatan || 'Asisten Lab';
        document.getElementById('m_nim').innerText = user.nim || '-';
        document.getElementById('m_class').innerText = user.kelas || '-';
        document.getElementById('m_prodi').innerText = user.prodi || '-';
        document.getElementById('m_email').innerText = user.email || '-';
        document.getElementById('m_phone').innerText = user.no_telp || '-';
        document.getElementById('m_address').innerText = user.alamat || '-';

        // 2. FOTO PROFIL
        const photoUrl = user.photo_profile 
            ? window.APP_CONFIG.baseUrl + '/uploads/profile/' + user.photo_profile 
            : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.nama) + '&background=random&size=200';
        document.getElementById('m_photo').src = photoUrl;

        // 3. LOGIKA STATUS VISUAL (Ke Kolom Kanan Atas)
        const vStatus = user.visual_status || 'alpha';
        const statusBox = document.getElementById('m_status_box');
        const statusIconBg = document.getElementById('m_status_icon_bg');
        const statusIcon = document.getElementById('m_status_icon');
        const statusText = document.getElementById('m_status_text');
        const dotOverlay = document.getElementById('m_dot_overlay');
        const photoImg = document.getElementById('m_photo');

        // Reset Styles
        photoImg.className = "w-full h-full rounded-full object-cover transition-all duration-500";
        dotOverlay.className = "absolute bottom-1 right-1 w-5 h-5 rounded-full border-2 border-white shadow-sm";

        if (vStatus === 'online') {
            statusBox.className = "mb-6 p-4 rounded-2xl border flex items-center gap-4 bg-green-50 border-green-100 text-green-800";
            statusIconBg.className = "w-12 h-12 rounded-full flex items-center justify-center text-xl shrink-0 bg-green-200 text-green-700";
            statusIcon.className = "fas fa-check-circle";
            statusText.innerText = "Sedang Bertugas";
            dotOverlay.classList.add('bg-green-500', 'animate-pulse');
            photoImg.classList.add('grayscale-0');
        } else if (vStatus === 'izin') {
            statusBox.className = "mb-6 p-4 rounded-2xl border flex items-center gap-4 bg-yellow-50 border-yellow-100 text-yellow-800";
            statusIconBg.className = "w-12 h-12 rounded-full flex items-center justify-center text-xl shrink-0 bg-yellow-200 text-yellow-700";
            statusIcon.className = "fas fa-info-circle";
            statusText.innerText = "Izin / Sakit";
            dotOverlay.classList.add('bg-yellow-500');
            photoImg.classList.add('sepia');
        } else if (vStatus === 'offline_pulang') {
            statusBox.className = "mb-6 p-4 rounded-2xl border flex items-center gap-4 bg-red-50 border-red-100 text-red-800";
            statusIconBg.className = "w-12 h-12 rounded-full flex items-center justify-center text-xl shrink-0 bg-red-200 text-red-700";
            statusIcon.className = "fas fa-flag-checkered";
            statusText.innerText = "Sudah Pulang (Offline)";
            dotOverlay.classList.add('bg-red-500');
            photoImg.classList.add('grayscale');
        } else {
            statusBox.className = "mb-6 p-4 rounded-2xl border flex items-center gap-4 bg-gray-50 border-gray-200 text-gray-600";
            statusIconBg.className = "w-12 h-12 rounded-full flex items-center justify-center text-xl shrink-0 bg-gray-200 text-gray-500";
            statusIcon.className = "fas fa-moon";
            statusText.innerText = "Belum Hadir";
            dotOverlay.classList.add('bg-red-500');
            photoImg.classList.add('grayscale');
        }

        // 4. CHART & STATISTIK ANGKA
        currentStatsData = {
            hadir: parseInt(user.total_hadir || 0),
            izin: parseInt(user.total_izin || 0),
            alpa: parseInt(user.total_alpa || 0)
        };

        // Update Angka Ringkasan
        document.getElementById('stat_hadir').innerText = currentStatsData.hadir;
        document.getElementById('stat_izin').innerText = currentStatsData.izin;
        document.getElementById('stat_alpa').innerText = currentStatsData.alpa;

        // Render Grafik
        initModalChart(currentModalChartType);

        // 5. UPDATE BUTTON JADWAL LENGKAP
        const btnSchedule = document.getElementById('btnSchedule');
        const currentRole = window.location.href.includes('kepalalab') ? 'kepalalab' : 'admin';
        if (btnSchedule) {
            btnSchedule.href = `${window.APP_CONFIG.baseUrl}/${currentRole}/assistantSchedule/${user.id_user}`;
        }

        // 6. ANIMASI BUKA MODAL
        modal.classList.remove('hidden');
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    // Fungsi Render Chart (Support 3 Tipe)
    function initModalChart(type) {
        const ctx = document.getElementById('modalChartCanvas').getContext('2d');
        if (modalChartInstance) modalChartInstance.destroy();

        // Config Data
        const dataValues = [currentStatsData.hadir, currentStatsData.izin, currentStatsData.alpa];
        const total = dataValues.reduce((a, b) => a + b, 0);
        
        // Jika data 0, tampilkan placeholder
        const chartData = total === 0 ? [1] : dataValues;
        const bgColors = total === 0 ? ['#f3f4f6'] : ['#22c55e', '#eab308', '#ef4444'];
        const labels = total === 0 ? ['Belum ada data'] : ['Hadir', 'Izin', 'Alpa'];

        modalChartInstance = new Chart(ctx, {
            type: type,
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total',
                    data: chartData,
                    backgroundColor: bgColors,
                    borderWidth: 0,
                    borderRadius: type === 'bar' ? 4 : 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: type !== 'bar', position: 'right', labels: { boxWidth: 10, font: { size: 10 } } },
                    tooltip: { enabled: total > 0 }
                },
                scales: type === 'bar' ? { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } : { y: { display: false }, x: { display: false } },
                cutout: type === 'doughnut' ? '70%' : 0
            }
        });
    }

    // Fungsi Switcher Tombol Grafik
    function setModalChartType(type) {
        currentModalChartType = type;
        initModalChart(type);
    }

    function closeDetailModal() {
        const modal = document.getElementById('detailModal');
        const backdrop = document.getElementById('detailBackdrop');
        const content = document.getElementById('detailContent');

        backdrop.classList.add('opacity-0');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // ==========================================
    // 3. CHART UTAMA DASHBOARD (GLOBAL)
    // ==========================================
    const chartData = window.APP_CONFIG.chartData || {};
    let chartInstance = null;
    let currentType = 'bar';

    function initChart() {
        const ctx = document.getElementById('adminChart').getContext('2d');
        const filterEl = document.getElementById('chartFilter');
        
        // Fallback jika elemen tidak ditemukan/data kosong
        if(!filterEl || !chartData) return;

        const filter = filterEl.value;
        const dataSet = chartData[filter] || { labels: [], data: [] };

        if(chartInstance) chartInstance.destroy();

        const bgColors = currentType === 'pie' ? ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'] : '#6366f1';
        const borderColor = currentType === 'pie' ? '#ffffff' : '#4f46e5';

        chartInstance = new Chart(ctx, {
            type: currentType,
            data: {
                labels: dataSet.labels,
                datasets: [{
                    label: 'Total Kehadiran',
                    data: dataSet.data,
                    backgroundColor: bgColors,
                    borderColor: borderColor,
                    borderWidth: 2,
                    borderRadius: currentType === 'pie' ? 0 : 6,
                    tension: 0.4,
                    fill: currentType === 'line'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: currentType === 'pie' ? {} : { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } },
                plugins: { legend: { display: currentType === 'pie' } }
            }
        });
    }

    function updateChart() { initChart(); }
    function setChartType(type) { currentType = type; initChart(); }
    
    // ==========================================
    // 4. INISIALISASI AKHIR (DOM READY)
    // ==========================================
    document.addEventListener("DOMContentLoaded", function() {
        initChart();
        
        // JAM DIGITAL
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
    });

// ================================================================
// [BARU – Tahap 30] Reset Presensi & Toggle Status Akun
// ================================================================

// State sementara — dipakai BERSAMA antara dashboard.js dan inline script
// di admin/dashboard.php. Pakai window.* agar tidak ada shadow variable.
if (typeof window._currentDetailUser === 'undefined') window._currentDetailUser = null;

// Patch openDetailModal agar menyimpan user ke window._currentDetailUser
// dan menangani tampilan overlay & tombol untuk akun nonaktif.
const _origOpenDetail = window.openDetailModal
    ? window.openDetailModal.bind(window)
    : null;

window.openDetailModal = function(user) {
    window._currentDetailUser = user;
    if (_origOpenDetail) _origOpenDetail(user);

    const isInactive = (user.status_account ?? 'ACTIVE') === 'INACTIVE';
    const overlay    = document.getElementById('m_inactive_overlay');
    const btnSched   = document.getElementById('btnSchedule');
    const btnReset   = document.getElementById('btnResetSingle');

    if (overlay) overlay.classList.toggle('hidden', !isInactive);

    if (btnSched) {
        if (isInactive) {
            btnSched.removeAttribute('href');
            btnSched.classList.add('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
        } else {
            btnSched.classList.remove('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
        }
    }

    const existingToggle = document.getElementById('btnToggleStatus');
    if (existingToggle) existingToggle.remove();

    if (btnReset && btnReset.parentElement) {
        const toggleBtn = document.createElement('button');
        toggleBtn.id = 'btnToggleStatus';
        toggleBtn.className = isInactive
            ? 'flex items-center justify-center w-full py-2.5 rounded-xl bg-green-500 text-white font-bold text-xs uppercase tracking-wider hover:bg-green-600 transition-all mt-1'
            : 'flex items-center justify-center w-full py-2.5 rounded-xl border border-gray-300 bg-gray-100 text-gray-600 hover:bg-gray-200 font-bold text-xs uppercase tracking-wider transition-all mt-1';
        toggleBtn.innerHTML = isInactive
            ? '<i class="fas fa-user-check mr-2"></i> Aktifkan Kembali Akun'
            : '<i class="fas fa-user-slash mr-2"></i> Nonaktifkan Akun';
        toggleBtn.onclick = isInactive ? window.openReactivateModal : window.openDeactivateModal;
        btnReset.parentElement.appendChild(toggleBtn);
    }
};

// ── Global Reset — fungsi didefinisikan di layout/footer.php agar
// tersedia di semua halaman Admin. Di sini hanya placeholder supaya
// dashboard.js tidak error jika dipanggil sebelum footer dimuat.


// ── Single Reset ─────────────────────────────────────────────────
window.openSingleResetModal = function() {
    if (!window._currentDetailUser) return;
    const nameEl = document.getElementById('singleResetName');
    if (nameEl) nameEl.innerText = window._currentDetailUser.name || window._currentDetailUser.nama || '';
    document.getElementById('singleResetModal')?.classList.remove('hidden');
};
window.closeSingleResetModal = function() {
    document.getElementById('singleResetModal')?.classList.add('hidden');
};
window.executeSingleReset = function() {
    if (!window._currentDetailUser) { console.warn('[ICLABS] window._currentDetailUser null'); return; }
    window.closeSingleResetModal();
    const base = ((window.APP_CONFIG && (window.APP_CONFIG.baseUrl || window.APP_CONFIG.BASE_URL)) || '').replace(/\/$/, '');
    const uid  = window._currentDetailUser.id_user || window._currentDetailUser.id || '';
    if (!uid) { console.warn('[ICLABS] uid kosong, obj:', window._currentDetailUser); return; }
    // [DIUBAH – Tahap 35] Data masuk recycle bin, bukan langsung dihapus + ZIP
    fetch(base + '/admin/resetToBin?scope=single&uid=' + uid, { method: 'GET' })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                if (typeof showCustomAlert === 'function')
                    showCustomAlert('success', 'Reset Berhasil', 'Data asisten diarsipkan ke Recycle Bin.');
            } else {
                if (typeof showCustomAlert === 'function')
                    showCustomAlert('error', 'Gagal', data.message || 'Terjadi kesalahan.');
            }
        })
        .catch(() => {
            if (typeof showCustomAlert === 'function')
                showCustomAlert('error', 'Gagal', 'Koneksi ke server terputus.');
        });
};

// ── Toggle Status Akun ───────────────────────────────────────────
window.openDeactivateModal = function() {
    if (!window._currentDetailUser) return;
    const n = document.getElementById('deactivateName');
    if (n) n.innerText = window._currentDetailUser.name || window._currentDetailUser.nama || '';
    document.getElementById('deactivateModal')?.classList.remove('hidden');
};
window.closeDeactivateModal = function() {
    document.getElementById('deactivateModal')?.classList.add('hidden');
};
window.openReactivateModal = function() {
    if (!window._currentDetailUser) return;
    const n = document.getElementById('reactivateName');
    if (n) n.innerText = window._currentDetailUser.name || window._currentDetailUser.nama || '';
    document.getElementById('reactivateModal')?.classList.remove('hidden');
};
window.closeReactivateModal = function() {
    document.getElementById('reactivateModal')?.classList.add('hidden');
};
window.executeToggleStatus = function(newStatus) {
    window.closeDeactivateModal();
    window.closeReactivateModal();
    if (!window._currentDetailUser) return;
    const base   = ((window.APP_CONFIG && (window.APP_CONFIG.baseUrl || window.APP_CONFIG.BASE_URL)) || '').replace(/\/$/, '');
    const userId = window._currentDetailUser.id_user || window._currentDetailUser.id;
    fetch(base + '/admin/toggleUserStatus', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId, status: newStatus })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            if (typeof showCustomAlert === 'function') {
                showCustomAlert('success',
                    newStatus === 'ACTIVE' ? 'Akun Diaktifkan' : 'Akun Dinonaktifkan',
                    newStatus === 'ACTIVE' ? 'Akun berhasil diaktifkan.' : 'Akun dinonaktifkan & data diarsipkan.');
            }
            if (data.download_url) {
                const f = document.createElement('iframe');
                f.style.display = 'none';
                f.src = data.download_url;
                document.body.appendChild(f);
                setTimeout(() => { try { document.body.removeChild(f); } catch(e) {} }, 30000);
            }
            setTimeout(() => location.reload(), 2500);
        } else {
            if (typeof showCustomAlert === 'function') showCustomAlert('error', 'Gagal', data.message || 'Terjadi kesalahan.');
        }
    })
    .catch(() => {
        if (typeof showCustomAlert === 'function') showCustomAlert('error', 'Gagal', 'Koneksi terputus.');
    });
};

// ── Helper Toast ─────────────────────────────────────────────────
window.showToast = function(type, title, msg) {
    if (typeof showCustomAlert === 'function') {
        showCustomAlert(type === 'success' ? 'success' : (type === 'error' ? 'error' : 'info'), title, msg);
    }
};
