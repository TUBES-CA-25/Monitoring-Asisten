// --- CHART LOGIC ---
// [PENTING] var (bukan let/const) di top-level script ini secara sengaja:
// script ini dimuat ulang (script tag baru) setiap kali halaman ini
// dikunjungi via AJAX navigation (lihat global.js _iclabsLoadPageScripts).
// let/const top-level akan melempar "Identifier ... has already been
// declared" pada kunjungan kedua karena binding lexical tetap ada di scope
// global walau elemen <script> lama sudah dihapus dari DOM.
if (window.Chart && window.ChartDataLabels) { Chart.register(window.ChartDataLabels); }

var stats = window.APP_CONFIG.STATS;
var chartInstance = null;
var currentType = 'bar';

function initChart() {
    var canvas = document.getElementById('assistantChart');
    if (!canvas || !window.Chart) return;
    var ctx = canvas.getContext('2d');
    if (chartInstance) chartInstance.destroy();

    var labels = ['Hadir', 'Izin', 'Tanpa Keterangan'];
    var dataValues = [stats.hadir, stats.izin, stats.alpa];
    var total = dataValues.reduce(function (a, b) { return a + b; }, 0);
    var bgColors = ['#22c55e', '#eab308', '#ef4444'];

    chartInstance = new Chart(ctx, {
        type: currentType,
        data: {
            labels: labels,
            datasets: [{
                label: 'Jumlah Kehadiran',
                data: dataValues,
                backgroundColor: bgColors,
                hoverBackgroundColor: ['#16a34a', '#ca8a04', '#dc2626'],
                borderRadius: currentType === 'pie' ? 0 : 8,
                borderWidth: currentType === 'pie' ? 3 : 0,
                borderColor: '#fff',
                barThickness: 60,
                hoverOffset: currentType === 'pie' ? 10 : 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 450, easing: 'easeOutQuart' },
            plugins: {
                legend: {
                    display: currentType === 'pie',
                    position: 'bottom',
                    labels: { usePointStyle: true, pointStyle: 'circle', padding: 14, font: { size: 11, weight: '600' } }
                },
                tooltip: { backgroundColor: '#1e293b', padding: 10, cornerRadius: 8, titleFont: { weight: '700' } },
                datalabels: total > 0 ? {
                    color: currentType === 'pie' ? '#fff' : '#475569',
                    anchor: currentType === 'bar' ? 'end' : 'center',
                    align: currentType === 'bar' ? 'top' : 'center',
                    offset: 4,
                    font: { weight: '700', size: 12 },
                    formatter: function (v) { return v > 0 ? v : ''; }
                } : false
            },
            scales: currentType === 'bar' ? {
                y: { beginAtZero: true, grid: { borderDash: [4, 4], color: '#e2e8f0' } },
                x: { grid: { display: false } }
            } : {}
        }
    });
}

function setChartType(type) {
    currentType = type;
    initChart();
}

document.addEventListener('DOMContentLoaded', initChart);
