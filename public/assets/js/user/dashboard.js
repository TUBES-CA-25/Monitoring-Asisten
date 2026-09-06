// [BARU] Sebelumnya chart.js dimuat manual di sini via document.head.appendChild
// karena tidak pernah ada mekanisme vendor_js untuk halaman ini (dashboard
// user hanya memuat <script> inline di dalam #mainContent, yang tidak
// dieksekusi browser saat halaman dicapai lewat navigasi AJAX). Sekarang
// chart.js dimuat dengan benar via $data['vendor_js'] di UserController::
// dashboard() (dirender di footer, dikelola ulang oleh global.js di setiap
// navigasi AJAX) - workaround manual ini sudah tidak diperlukan lagi.
//
// [PENTING] var (bukan let/const) di top-level script ini secara sengaja:
// script ini dimuat ulang (script tag baru) setiap kali halaman dashboard
// dikunjungi via AJAX navigation (lihat global.js _iclabsLoadPageScripts).
// let/const top-level akan melempar "Identifier ... has already been
// declared" pada kunjungan kedua karena binding lexical tetap ada di scope
// global walau elemen <script> lama sudah dihapus dari DOM.
if (window.Chart && window.ChartDataLabels) { Chart.register(window.ChartDataLabels); }

var chartInstance = null;
var currentType = 'bar';
var shouldReload = false;
var chartData = {};

document.addEventListener('DOMContentLoaded', () => {
    const jsonEl = document.getElementById('chart-data');
    if (jsonEl) {
        try {
            chartData = JSON.parse(jsonEl.textContent);
        } catch (e) {
            console.error('Chart data JSON invalid', e);
        }
    }

    bindEvents();
    initClock();
    initChart();
});

function initClock() {
    updateClock();
    setInterval(updateClock, 1000);
}

function updateClock() {
    const now = new Date();

    const dateOptions = { day: '2-digit', month: 'long', year: 'numeric' };
    const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };

    const elDate = document.getElementById('liveDate');
    const elTime = document.getElementById('liveTime');

    if (elDate) elDate.innerText = now.toLocaleDateString('id-ID', dateOptions);
    if (elTime) elTime.innerText = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':');
}

function initChart() {
    const canvas = document.getElementById('userChart');
    if (!canvas || !window.Chart) return;

    const ctx = canvas.getContext('2d');
    const filterEl = document.getElementById('timeFilter');

    let filter = filterEl ? filterEl.value : 'daily';

    if (!chartData[filter]) {
        filter = chartData['daily'] ? 'daily' : Object.keys(chartData)[0];
        if (filterEl) filterEl.value = filter;
    }

    const dataSet = chartData[filter] || { labels: [], data: [] };
    const total = (dataSet.data || []).reduce((a, b) => a + b, 0);

    if (chartInstance) chartInstance.destroy();

    const pieColors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6366f1', '#14b8a6'];
    const bgColors = currentType === 'pie' ? pieColors : '#3b82f6';

    const labelText =
        filter === 'daily' ? 'Status Hadir' :
        filter === 'weekly' ? 'Jumlah Hari Hadir' :
        'Total Kehadiran';

    chartInstance = new Chart(ctx, {
        type: currentType,
        data: {
            labels: dataSet.labels,
            datasets: [{
                label: labelText,
                data: dataSet.data,
                backgroundColor: bgColors,
                hoverBackgroundColor: currentType === 'pie' ? pieColors : '#2563eb',
                borderColor: currentType === 'pie' ? '#fff' : '#2563eb',
                borderWidth: currentType === 'pie' ? 3 : 2,
                borderRadius: currentType === 'pie' ? 0 : 8,
                maxBarThickness: 46,
                tension: 0.4,
                fill: currentType === 'line',
                pointBackgroundColor: '#3b82f6',
                pointRadius: currentType === 'line' ? 4 : 0,
                hoverOffset: currentType === 'pie' ? 10 : 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 500, easing: 'easeOutQuart' },
            scales: currentType === 'pie' ? {} : {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { precision: 0 } },
                x: { grid: { display: false } }
            },
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

function bindEvents() {
    const filterEl = document.getElementById('timeFilter');
    if (filterEl) {
        filterEl.addEventListener('change', () => initChart());
    }

    const leaveForm = document.getElementById('leaveForm');
    if (leaveForm) {
        leaveForm.addEventListener('submit', handleLeaveSubmit);
    }

    document.querySelectorAll('[data-chart-type]').forEach(btn => {
        btn.addEventListener('click', () => {
            currentType = btn.dataset.chartType;
            initChart();
        });
    });
}

function handleLeaveSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalHTML = submitBtn.innerHTML;

    submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Mengirim...';
    submitBtn.disabled = true;

    fetch(form.action, {
        method: 'POST',
        body: new FormData(form)
    })
    .then(res => res.json())
    .then(data => {
        submitBtn.innerHTML = originalHTML;
        submitBtn.disabled = false;

        if (data.status === 'success') {
            showCustomAlert('success', data.title, data.message, true);
            form.reset();
        } else {
            showCustomAlert('error', 'Gagal', data.message || 'Permintaan gagal.');
        }
    })
    .catch(() => {
        submitBtn.innerHTML = originalHTML;
        submitBtn.disabled = false;
        showCustomAlert('error', 'Error', 'Koneksi server bermasalah.');
    });
}

function showCustomAlert(type, title, message, reloadOnClose = false) {
    shouldReload = reloadOnClose;

    const modal = document.getElementById('customAlertModal');
    const content = document.getElementById('alertContent');
    const backdrop = document.getElementById('alertBackdrop');
    const iconBg = document.getElementById('alertIconBg');
    const icon = document.getElementById('alertIcon');
    const btn = document.getElementById('alertBtn');

    document.getElementById('alertTitle').innerText = title;
    document.getElementById('alertMessage').innerText = message;

    if (type === 'success') {
        iconBg.className = 'w-20 h-20 rounded-full flex items-center justify-center mb-4 bg-green-100 text-green-500';
        icon.className = 'fas fa-check';
        btn.className = 'w-full py-3.5 rounded-xl font-bold text-white bg-green-600 hover:bg-green-700 shadow-lg transition';
    } else {
        iconBg.className = 'w-20 h-20 rounded-full flex items-center justify-center mb-4 bg-red-100 text-red-500';
        icon.className = 'fas fa-times';
        btn.className = 'w-full py-3.5 rounded-xl font-bold text-white bg-red-600 hover:bg-red-700 shadow-lg transition';
    }

    modal.classList.remove('hidden');
    setTimeout(() => {
        backdrop.classList.remove('opacity-0');
        content.classList.remove('scale-90', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeCustomAlert() {
    const modal = document.getElementById('customAlertModal');
    const content = document.getElementById('alertContent');
    const backdrop = document.getElementById('alertBackdrop');

    backdrop.classList.add('opacity-0');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-90', 'opacity-0');

    setTimeout(() => {
        modal.classList.add('hidden');
        if (shouldReload) window.location.reload();
    }, 300);
}

window.setChartType = type => {
    currentType = type;
    initChart();
};
