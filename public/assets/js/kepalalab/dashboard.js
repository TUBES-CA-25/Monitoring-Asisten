(() => {
    'use strict';

    if (!window.APP_CONFIG) {
        console.error('Dashboard data not found');
        return;
    }

    const BASE_URL = window.APP_CONFIG.baseUrl;
    const chartData = window.APP_CONFIG.chartData;

    // Plugin ornamen (angka langsung di atas/di dalam chart) - dimuat via
    // vendor_js sebagai window.ChartDataLabels, didaftarkan sekali di sini.
    if (window.Chart && window.ChartDataLabels) { Chart.register(window.ChartDataLabels); }

    let chartInstance = null;
    let modalChartInstance = null;
    let currentType = 'bar';
    let currentModalChartType = 'doughnut';
    let currentStatsData = { hadir: 0, izin: 0, alpa: 0 };

    function updateClock() {
        const now = new Date();

        const dateString = now.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });

        const timeString = now.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        }).replace(/\./g, ':');

        const elDate = document.getElementById('liveDate');
        const elTime = document.getElementById('liveTime');

        if (elDate) elDate.innerText = dateString;
        if (elTime) elTime.innerText = timeString;
    }

    function initChart() {
        const canvas = document.getElementById('adminChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const filter = document.getElementById('chartFilter').value;
        const dataSet = chartData[filter];
        const total = (dataSet.data || []).reduce((a, b) => a + b, 0);

        if (chartInstance) chartInstance.destroy();

        const pieColors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
        const bgColors = currentType === 'pie' ? pieColors : '#6366f1';
        const borderColor = currentType === 'pie' ? '#ffffff' : '#4f46e5';

        chartInstance = new Chart(ctx, {
            type: currentType,
            data: {
                labels: dataSet.labels,
                datasets: [{
                    label: 'Kehadiran',
                    data: dataSet.data,
                    backgroundColor: bgColors,
                    hoverBackgroundColor: currentType === 'pie' ? pieColors : '#4338ca',
                    borderColor: borderColor,
                    borderWidth: 2,
                    borderRadius: currentType === 'pie' ? 0 : 8,
                    maxBarThickness: 46,
                    tension: 0.4,
                    fill: currentType === 'line',
                    pointBackgroundColor: '#6366f1',
                    pointRadius: currentType === 'line' ? 4 : 0,
                    hoverOffset: currentType === 'pie' ? 10 : 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 500, easing: 'easeOutQuart' },
                scales: currentType === 'pie'
                    ? {}
                    : { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } },
                plugins: {
                    legend: {
                        display: currentType === 'pie',
                        position: 'right',
                        labels: { usePointStyle: true, pointStyle: 'circle', padding: 14, font: { size: 11, weight: '600' } }
                    },
                    tooltip: { backgroundColor: '#1e293b', padding: 10, cornerRadius: 8, titleFont: { weight: '700' } },
                    datalabels: total > 0 ? {
                        color: currentType === 'pie' ? '#fff' : '#475569',
                        anchor: currentType === 'bar' ? 'end' : 'center',
                        align: currentType === 'bar' ? 'top' : 'center',
                        offset: 4,
                        font: { weight: '700', size: 11 },
                        formatter: (v) => (v > 0 ? v : '')
                    } : false
                }
            }
        });
    }

    window.updateChart = () => initChart();
    window.setChartType = (type) => {
        currentType = type;
        initChart();
    };

    function initModalChart(type) {
        const canvas = document.getElementById('modalChartCanvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        if (modalChartInstance) modalChartInstance.destroy();

        const values = [
            currentStatsData.hadir,
            currentStatsData.izin,
            currentStatsData.alpa
        ];

        const total = values.reduce((a, b) => a + b, 0);

        const chartValues = total === 0 ? [1] : values;
        const bgColors = total === 0
            ? ['#f3f4f6']
            : ['#22c55e', '#eab308', '#ef4444'];

        const labels = total === 0
            ? ['Belum ada data']
            : ['Hadir', 'Izin', 'Alpa'];

        modalChartInstance = new Chart(ctx, {
            type: type,
            data: {
                labels,
                datasets: [{
                    data: chartValues,
                    backgroundColor: bgColors,
                    borderWidth: type === 'doughnut' ? 3 : 0,
                    borderColor: '#fff',
                    borderRadius: type === 'bar' ? 6 : 0,
                    maxBarThickness: 40,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 450, easing: 'easeOutQuart' },
                plugins: {
                    legend: {
                        display: type !== 'bar',
                        position: 'right',
                        labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, font: { size: 10, weight: '600' } }
                    },
                    tooltip: {
                        enabled: total > 0,
                        backgroundColor: '#1e293b', padding: 8, cornerRadius: 8
                    },
                    datalabels: total > 0 ? {
                        color: type === 'bar' ? '#475569' : '#fff',
                        anchor: type === 'bar' ? 'end' : 'center',
                        align: type === 'bar' ? 'top' : 'center',
                        offset: 4,
                        font: { weight: '700', size: 10 },
                        formatter: (v) => (v > 0 ? v : '')
                    } : false
                },
                scales: type === 'bar'
                    ? {
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                        x: { grid: { display: false } }
                    }
                    : {
                        y: { display: false },
                        x: { display: false }
                    },
                cutout: type === 'doughnut' ? '70%' : 0
            }
        });
    }

    window.setModalChartType = (type) => {
        currentModalChartType = type;
        initModalChart(type);
    };

    window.openDetailModal = (user) => {
        const modal = document.getElementById('detailModal');
        const backdrop = document.getElementById('detailBackdrop');
        const content = document.getElementById('detailContent');

        document.getElementById('m_name').innerText = user.name || '-';
        document.getElementById('m_position').innerText = user.jabatan || 'Asisten Lab';
        document.getElementById('m_nim').innerText = user.nim || '-';
        document.getElementById('m_class').innerText = user.kelas || '-';
        document.getElementById('m_prodi').innerText = user.prodi || '-';
        document.getElementById('m_email').innerText = user.email || '-';
        document.getElementById('m_phone').innerText = user.no_telp || '-';
        document.getElementById('m_address').innerText = user.alamat || '-';

        const photo = document.getElementById('m_photo');
        photo.src = user.photo_profile
            ? `${BASE_URL}/uploads/profile/${user.photo_profile}`
            : `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=random&size=200`;

        const els = {
            box: document.getElementById('m_status_box'),
            iconBg: document.getElementById('m_status_icon_bg'),
            icon: document.getElementById('m_status_icon'),
            text: document.getElementById('m_status_text'),
            dot: document.getElementById('m_dot_overlay'),
            img: photo
        };

        els.img.className = "w-full h-full rounded-full object-cover transition-all duration-500";
        els.dot.className = "absolute bottom-1 right-1 w-5 h-5 rounded-full border-2 border-white shadow-sm";

        const vStatus = user.visual_status || 'alpha';

        if (vStatus === 'online') {
            els.box.className = "mb-6 p-4 rounded-2xl border flex items-center gap-4 bg-green-50 border-green-100 text-green-800";
            els.iconBg.className = "w-12 h-12 rounded-full flex items-center justify-center text-xl bg-green-200 text-green-700";
            els.icon.className = "fas fa-check-circle";
            els.text.innerText = "Sedang Bertugas";
            els.dot.classList.add('bg-green-500', 'animate-pulse');
            els.img.classList.add('grayscale-0');
        } else if (vStatus === 'izin') {
            els.box.className = "mb-6 p-4 rounded-2xl border flex items-center gap-4 bg-yellow-50 border-yellow-100 text-yellow-800";
            els.iconBg.className = "w-12 h-12 rounded-full flex items-center justify-center text-xl bg-yellow-200 text-yellow-700";
            els.icon.className = "fas fa-info-circle";
            els.text.innerText = "Izin / Sakit";
            els.dot.classList.add('bg-yellow-500');
            els.img.classList.add('sepia');
        } else if (vStatus === 'offline_pulang') {
            els.box.className = "mb-6 p-4 rounded-2xl border flex items-center gap-4 bg-red-50 border-red-100 text-red-800";
            els.iconBg.className = "w-12 h-12 rounded-full flex items-center justify-center text-xl bg-red-200 text-red-700";
            els.icon.className = "fas fa-flag-checkered";
            els.text.innerText = "Sudah Pulang (Offline)";
            els.dot.classList.add('bg-red-500');
            els.img.classList.add('grayscale');
        } else {
            els.box.className = "mb-6 p-4 rounded-2xl border flex items-center gap-4 bg-gray-50 border-gray-200 text-gray-600";
            els.iconBg.className = "w-12 h-12 rounded-full flex items-center justify-center text-xl bg-gray-200 text-gray-500";
            els.icon.className = "fas fa-moon";
            els.text.innerText = "Belum Hadir";
            els.dot.classList.add('bg-red-500');
            els.img.classList.add('grayscale');
        }

        currentStatsData = {
            hadir: parseInt(user.total_hadir || 0),
            izin: parseInt(user.total_izin || 0),
            alpa: parseInt(user.total_alpa || 0)
        };

        document.getElementById('stat_hadir').innerText = currentStatsData.hadir;
        document.getElementById('stat_izin').innerText = currentStatsData.izin;
        document.getElementById('stat_alpa').innerText = currentStatsData.alpa;

        initModalChart(currentModalChartType);

        const btnSchedule = document.getElementById('btnSchedule');
        if (btnSchedule) {
            btnSchedule.href = `${BASE_URL}/kepalalab/assistantSchedule/${user.id_hash || user.id_user || user.id}`;
        }

        // [BARU – Tahap 30] Inactive account overlay
        _currentDetailUser = user;
        const isInactive = (user.status_account ?? 'ACTIVE') === 'INACTIVE';
        const overlay = document.getElementById('m_inactive_overlay');
        if (overlay) overlay.classList.toggle('hidden', !isInactive);
        if (btnSchedule) {
            if (isInactive) {
                btnSchedule.removeAttribute('href');
                btnSchedule.classList.add('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
            } else {
                btnSchedule.href = `${BASE_URL}/kepalalab/assistantSchedule/${user.id_hash || user.id_user || user.id}`;
                btnSchedule.classList.remove('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
            }
        }

        modal.classList.remove('hidden');
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    };

    window.closeDetailModal = () => {
        const modal = document.getElementById('detailModal');
        const content = document.getElementById('detailContent');
        const backdrop = document.getElementById('detailBackdrop');

        backdrop.classList.add('opacity-0');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');

        setTimeout(() => modal.classList.add('hidden'), 300);
    };

    document.addEventListener('DOMContentLoaded', () => {
        initChart();
        updateClock();
        setInterval(updateClock, 1000);
    });

})();

// ================================================================
// [BARU – Tahap 30] Reset Individu & Toggle Status (Kepala Lab)
// Kepala Lab tidak bisa mereset/nonaktifkan sendiri (hanya Admin),
// tapi overlay & tombol toggle ditampilkan karena dashboard.js
// kepalalab memakai fungsi yang sama. Jika dieksekusi, request
// dikirim ke endpoint Admin (butuh session Admin). Umumnya Kepala Lab
// hanya akan melihat overlay "Akun Dinonaktifkan" tanpa tombol aksi.
// ================================================================
// [PENTING] var (bukan let): script ini dimuat ulang setiap kali halaman
// dashboard dikunjungi via AJAX navigation — let top-level akan melempar
// "Identifier ... has already been declared" pada kunjungan kedua.
var _currentDetailUser = null;

function openSingleResetModal() {
    if (!_currentDetailUser) return;
    const n = document.getElementById('singleResetName');
    if (n) n.innerText = _currentDetailUser.name || _currentDetailUser.nama || '';
    document.getElementById('singleResetModal')?.classList.remove('hidden');
}
function closeSingleResetModal() { document.getElementById('singleResetModal')?.classList.add('hidden'); }
function executeSingleReset() {
    if (!_currentDetailUser) return;
    closeSingleResetModal();
    const base = (window.APP_CONFIG && window.APP_CONFIG.BASE_URL) || window.BASE_URL || '';
    const uid  = _currentDetailUser.id_user || _currentDetailUser.id || '';
    const f = document.createElement('iframe');
    f.style.display = 'none';
    f.src = base + '/admin/resetAttendance?scope=single&uid=' + uid;
    document.body.appendChild(f);
    setTimeout(() => document.body.removeChild(f), 30000);
}
function openDeactivateModal() {
    if (!_currentDetailUser) return;
    const n = document.getElementById('deactivateName');
    if (n) n.innerText = _currentDetailUser.name || _currentDetailUser.nama || '';
    document.getElementById('deactivateModal')?.classList.remove('hidden');
}
function closeDeactivateModal() { document.getElementById('deactivateModal')?.classList.add('hidden'); }
function openReactivateModal() {
    if (!_currentDetailUser) return;
    const n = document.getElementById('reactivateName');
    if (n) n.innerText = _currentDetailUser.name || _currentDetailUser.nama || '';
    document.getElementById('reactivateModal')?.classList.remove('hidden');
}
function closeReactivateModal() { document.getElementById('reactivateModal')?.classList.add('hidden'); }
function executeToggleStatus(newStatus) {
    closeDeactivateModal(); closeReactivateModal();
    if (!_currentDetailUser) return;
    const base   = (window.APP_CONFIG && window.APP_CONFIG.BASE_URL) || window.BASE_URL || '';
    const userId = _currentDetailUser.id_user || _currentDetailUser.id;
    fetch(base + '/admin/toggleUserStatus', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId, status: newStatus })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            if (data.download_url) {
                const f = document.createElement('iframe');
                f.style.display = 'none';
                f.src = data.download_url;
                document.body.appendChild(f);
                setTimeout(() => document.body.removeChild(f), 30000);
            }
            setTimeout(() => location.reload(), 2000);
        }
    })
    .catch(() => {});
}
