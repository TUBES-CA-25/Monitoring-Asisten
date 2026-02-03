document.addEventListener("DOMContentLoaded", function() {
    
    // 1. JAM DIGITAL
    function updateClock() {
        const now = new Date();
        const dateOptions = { day: '2-digit', month: 'long', year: 'numeric' };
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
        
        const elDate = document.getElementById('liveDate');
        const elTime = document.getElementById('liveTime');
        
        if (elDate) elDate.innerText = now.toLocaleDateString('id-ID', dateOptions);
        if (elTime) elTime.innerText = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':');
    }
    setInterval(updateClock, 1000); 
    updateClock();

    // 2. CHART JS INIT
    initChart();
    
    // 3. QR CODE INIT (Jika modal terbuka)
    // Setup interval refresh otomatis untuk QR
    if (typeof DashboardConfig !== 'undefined') {
        // Logic QR Code bisa diakses dari fungsi global di bawah
    }
});

// ==========================================
// CHART LOGIC
// ==========================================
let chartInstance = null;
let currentType = 'bar';

function initChart() {
    const ctx = document.getElementById('adminChart');
    const filterEl = document.getElementById('chartFilter');
    
    // Cek ketersediaan data dari variabel global PHP->JS
    if(!ctx || !filterEl || typeof DashboardConfig === 'undefined') return;

    const filter = filterEl.value;
    const dataSet = DashboardConfig.chartData[filter] || { labels: [], data: [] };

    if(chartInstance) chartInstance.destroy();

    const bgColors = currentType === 'pie' ? ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'] : '#6366f1';
    const borderColor = currentType === 'pie' ? '#ffffff' : '#4f46e5';

    chartInstance = new Chart(ctx.getContext('2d'), {
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
// QR CODE LOGIC
// ==========================================
let qrCodeObj = null;
let qrInterval = null;
let currentMode = 'check_in';

function openQRModal() {
    const modal = document.getElementById('qrModal');
    const content = document.getElementById('qrContent');
    const container = document.getElementById("qrcode");

    modal.classList.remove('hidden');
    
    // Init QR Lib jika belum ada
    if (!qrCodeObj && container) {
        qrCodeObj = new QRCode(container, { width: 200, height: 200 });
    }

    // Animasi Masuk
    setTimeout(() => { 
        content.classList.remove('scale-95', 'opacity-0'); 
        content.classList.add('scale-100', 'opacity-100'); 
    }, 10);
    
    // Set awal ke check in
    setQRMode(true); 
}

function closeQRModal() {
    const modal = document.getElementById('qrModal');
    const content = document.getElementById('qrContent');

    content.classList.remove('scale-100', 'opacity-100'); 
    content.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => { modal.classList.add('hidden'); }, 300);
    
    if (qrInterval) clearInterval(qrInterval);
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
        
        // Render Awal dari Data PHP
        if(qrCodeObj && DashboardConfig.qrIn) {
            renderQRRaw(DashboardConfig.qrIn);
        }
        
        // Refresh QR via Ajax
        fetchAndRenderQR('check_in');
        qrInterval = setInterval(() => { fetchAndRenderQR('check_in'); }, 270000); // 4.5 menit
    } else {
        currentMode = 'check_out';
        title.innerText = "SCAN UNTUK PULANG";
        container.className = "bg-red-600 p-6 text-center transition-colors duration-300";
        toggle.style.transform = "translateX(100%)";
        
        if(qrCodeObj && DashboardConfig.qrOut) {
            renderQRRaw(DashboardConfig.qrOut);
        }
        fetchAndRenderQR('check_out');
    }
}

function renderQRRaw(tokenString) {
    if(qrCodeObj) {
        qrCodeObj.clear();
        qrCodeObj.makeCode(tokenString);
    }
}

function fetchAndRenderQR(type) {
    if(typeof DashboardConfig === 'undefined') return;

    const fd = new FormData();
    fd.append('type', type);

    fetch(`${DashboardConfig.baseUrl}/${DashboardConfig.roleSegment}/getQrAjax`, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && qrCodeObj) {
                renderQRRaw(data.qr_data);
            }
        })
        .catch(err => console.error("Gagal refresh QR:", err));
}

// ==========================================
// MODAL DETAIL LOGIC
// ==========================================
let modalChartInstance = null;

function openDetailModal(user) {
    // Populate Data Text
    document.getElementById('m_name').innerText = user.name;
    document.getElementById('m_position').innerText = user.jabatan || 'Asisten Lab';
    document.getElementById('m_nim').innerText = user.nim || '-';
    document.getElementById('m_class').innerText = user.kelas || '-';
    document.getElementById('m_prodi').innerText = user.prodi || '-';
    document.getElementById('m_email').innerText = user.email || '-';
    document.getElementById('m_phone').innerText = user.no_telp || '-';
    document.getElementById('m_address').innerText = user.alamat || '-';

    // Foto Profil
    const photoUrl = user.photo_profile 
        ? `${DashboardConfig.baseUrl}/uploads/profile/${user.photo_profile}` 
        : `https://ui-avatars.com/api/?name=${encodeURIComponent(user.nama)}&background=random&size=200`;
    document.getElementById('m_photo').src = photoUrl;

    // Visual Status Logic (Warna & Icon)
    const vStatus = user.visual_status || 'alpha';
    const ui = {
        online: { box: 'green', icon: 'check-circle', text: 'Sedang Bertugas' },
        izin:   { box: 'yellow', icon: 'info-circle', text: 'Izin / Sakit' },
        offline_pulang: { box: 'red', icon: 'flag-checkered', text: 'Sudah Pulang' },
        alpha:  { box: 'gray', icon: 'moon', text: 'Belum Hadir' }
    };
    
    const theme = ui[vStatus] || ui.alpha;
    
    // Apply Classes secara dinamis
    const statusBox = document.getElementById('m_status_box');
    const iconBg = document.getElementById('m_status_icon_bg');
    
    // Hapus class lama (cara kasar tapi efektif utk vanilla JS)
    statusBox.className = `mb-6 p-4 rounded-2xl border flex items-center gap-4 transition-all shadow-sm bg-${theme.box}-50 border-${theme.box}-100 text-${theme.box}-800`;
    iconBg.className = `w-12 h-12 rounded-full flex items-center justify-center text-xl shrink-0 transition-colors bg-${theme.box}-200 text-${theme.box}-700`;
    
    document.getElementById('m_status_icon').className = `fas fa-${theme.icon}`;
    document.getElementById('m_status_text').innerText = theme.text;

    // Link Jadwal
    const btnSchedule = document.getElementById('btnSchedule');
    if (btnSchedule) {
        btnSchedule.href = `${DashboardConfig.baseUrl}/${DashboardConfig.roleSegment}/assistantSchedule/${user.id_user}`;
    }

    // Chart Statistik Modal
    const stats = {
        hadir: parseInt(user.total_hadir || 0),
        izin: parseInt(user.total_izin || 0),
        alpa: parseInt(user.total_alpa || 0)
    };
    
    document.getElementById('stat_hadir').innerText = stats.hadir;
    document.getElementById('stat_izin').innerText = stats.izin;
    document.getElementById('stat_alpa').innerText = stats.alpa;

    renderModalChart(stats, 'doughnut');

    // Show Modal
    const modal = document.getElementById('detailModal');
    modal.classList.remove('hidden');
    setTimeout(() => {
        document.getElementById('detailBackdrop').classList.remove('opacity-0');
        const content = document.getElementById('detailContent');
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeDetailModal() {
    const modal = document.getElementById('detailModal');
    const content = document.getElementById('detailContent');
    
    document.getElementById('detailBackdrop').classList.add('opacity-0');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');

    setTimeout(() => { modal.classList.add('hidden'); }, 300);
}

// Chart Modal Helper
function renderModalChart(stats, type) {
    const ctx = document.getElementById('modalChartCanvas').getContext('2d');
    if (modalChartInstance) modalChartInstance.destroy();

    const total = stats.hadir + stats.izin + stats.alpa;
    const data = total === 0 ? [1] : [stats.hadir, stats.izin, stats.alpa];
    const colors = total === 0 ? ['#f3f4f6'] : ['#22c55e', '#eab308', '#ef4444'];
    const labels = total === 0 ? ['No Data'] : ['Hadir', 'Izin', 'Alpa'];

    modalChartInstance = new Chart(ctx, {
        type: type,
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: type !== 'bar', position: 'right' } },
            cutout: type === 'doughnut' ? '70%' : 0
        }
    });
}

function setModalChartType(type) {
    // Kita butuh data user terakhir yang dibuka untuk render ulang
    // Untuk simplifikasi, anggap data sudah ada di DOM (stat_hadir, dll)
    const stats = {
        hadir: parseInt(document.getElementById('stat_hadir').innerText),
        izin: parseInt(document.getElementById('stat_izin').innerText),
        alpa: parseInt(document.getElementById('stat_alpa').innerText)
    };
    renderModalChart(stats, type);
}