// --- CHART LOGIC ---
const stats = window.APP_CONFIG.STATS;
let chartInstance = null;
let currentType = 'bar';

function initChart() {
    const ctx = document.getElementById('assistantChart').getContext('2d');
    if (chartInstance) chartInstance.destroy();

    const labels = ['Hadir', 'Izin', 'Tanpa Keterangan'];
    const dataValues = [stats.hadir, stats.izin, stats.alpa];
    const bgColors = ['#22c55e', '#eab308', '#ef4444'];

    chartInstance = new Chart(ctx, {
        type: currentType,
        data: {
            labels: labels,
            datasets: [{
                label: 'Jumlah Kehadiran',
                data: dataValues,
                backgroundColor: bgColors,
                borderRadius: 8,
                borderWidth: 0,
                barThickness: 60,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: currentType === 'pie', position: 'bottom' }
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
