
    const DashboardConfig = {
        baseUrl: "<?= rtrim(BASE_URL, '/') ?>",
        roleSegment: "<?= strtolower(str_replace(' ', '', $_SESSION['role'])) ?>", 
        
        chartData: <?= json_encode($chart_data ?? []) ?>,
        qrIn: '<?= $qr_in ?? "" ?>',
        qrOut: '<?= $qr_out ?? "" ?>'
    };
    let modalChartInstance = null;
    let currentModalChartType = 'doughnut';
    let currentStatsData = { hadir: 0, izin: 0, alpa: 0 };

    function openDetailModal(user) {
        const modal = document.getElementById('detailModal');
        const backdrop = document.getElementById('detailBackdrop');
        const content = document.getElementById('detailContent');
        
        // Mapping Text
        document.getElementById('m_name').innerText = user.name;
        document.getElementById('m_position').innerText = user.jabatan || 'Asisten Lab';
        document.getElementById('m_nim').innerText = user.nim || '-';
        document.getElementById('m_class').innerText = user.kelas || '-';
        document.getElementById('m_prodi').innerText = user.prodi || '-';
        document.getElementById('m_email').innerText = user.email || '-';
        document.getElementById('m_phone').innerText = user.no_telp || '-';
        document.getElementById('m_address').innerText = user.alamat || '-';

        // Mapping Foto
        const photoUrl = user.photo_profile 
            ? '<?= BASE_URL ?>/uploads/profile/' + user.photo_profile 
            : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.nama) + '&background=random&size=200';
        document.getElementById('m_photo').src = photoUrl;

        // Logic Visual Status
        const vStatus = user.visual_status || 'alpha';
        const els = {
            box: document.getElementById('m_status_box'),
            iconBg: document.getElementById('m_status_icon_bg'),
            icon: document.getElementById('m_status_icon'),
            text: document.getElementById('m_status_text'),
            dot: document.getElementById('m_dot_overlay'),
            img: document.getElementById('m_photo')
        };

        // Reset
        els.img.className = "w-full h-full rounded-full object-cover transition-all duration-500";
        els.dot.className = "absolute bottom-1 right-1 w-5 h-5 rounded-full border-2 border-white shadow-sm";

        if (vStatus === 'online') {
            els.box.className = "mb-6 p-4 rounded-2xl border flex items-center gap-4 bg-green-50 border-green-100 text-green-800";
            els.iconBg.className = "w-12 h-12 rounded-full flex items-center justify-center text-xl shrink-0 bg-green-200 text-green-700";
            els.icon.className = "fas fa-check-circle";
            els.text.innerText = "Sedang Bertugas";
            els.dot.classList.add('bg-green-500', 'animate-pulse');
            els.img.classList.add('grayscale-0');
        } else if (vStatus === 'izin') {
            els.box.className = "mb-6 p-4 rounded-2xl border flex items-center gap-4 bg-yellow-50 border-yellow-100 text-yellow-800";
            els.iconBg.className = "w-12 h-12 rounded-full flex items-center justify-center text-xl shrink-0 bg-yellow-200 text-yellow-700";
            els.icon.className = "fas fa-info-circle";
            els.text.innerText = "Izin / Sakit";
            els.dot.classList.add('bg-yellow-500');
            els.img.classList.add('sepia');
        } else if (vStatus === 'offline_pulang') {
            els.box.className = "mb-6 p-4 rounded-2xl border flex items-center gap-4 bg-red-50 border-red-100 text-red-800";
            els.iconBg.className = "w-12 h-12 rounded-full flex items-center justify-center text-xl shrink-0 bg-red-200 text-red-700";
            els.icon.className = "fas fa-flag-checkered";
            els.text.innerText = "Sudah Pulang (Offline)";
            els.dot.classList.add('bg-red-500');
            els.img.classList.add('grayscale');
        } else {
            els.box.className = "mb-6 p-4 rounded-2xl border flex items-center gap-4 bg-gray-50 border-gray-200 text-gray-600";
            els.iconBg.className = "w-12 h-12 rounded-full flex items-center justify-center text-xl shrink-0 bg-gray-200 text-gray-500";
            els.icon.className = "fas fa-moon";
            els.text.innerText = "Belum Hadir";
            els.dot.classList.add('bg-red-500');
            els.img.classList.add('grayscale');
        }

        // Logic Chart & Statistik
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
        const currentRole = window.location.href.includes('kepalalab') ? 'kepalalab' : 'admin';
        if (btnSchedule) {
            const userId = user.id_user || user.id;
            const targetUrl = `${DashboardConfig.baseUrl}/${DashboardConfig.roleSegment}/assistantSchedule/${userId}`;
            btnSchedule.href = targetUrl;
        }

        // Show Modal
        modal.classList.remove('hidden');
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function initModalChart(type) {
        const ctx = document.getElementById('modalChartCanvas').getContext('2d');
        if (modalChartInstance) modalChartInstance.destroy();

        const dataValues = [currentStatsData.hadir, currentStatsData.izin, currentStatsData.alpa];
        const total = dataValues.reduce((a, b) => a + b, 0);
        const chartData = total === 0 ? [1] : dataValues;
        const bgColors = total === 0 ? ['#f3f4f6'] : ['#22c55e', '#eab308', '#ef4444'];
        const labels = total === 0 ? ['Belum ada data'] : ['Hadir', 'Izin', 'Alpa'];

        modalChartInstance = new Chart(ctx, {
            type: type,
            data: {
                labels: labels,
                datasets: [{ data: chartData, backgroundColor: bgColors, borderWidth: 0, hoverOffset: 4 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: type !== 'bar', position: 'right' }, tooltip: { enabled: total > 0 } },
                scales: type === 'bar' ? { y: { beginAtZero: true, grid: {display:false} }, x: { grid: {display:false} } } : { y: { display: false }, x: { display: false } },
                cutout: type === 'doughnut' ? '70%' : 0
            }
        });
    }

    function setModalChartType(type) {
        currentModalChartType = type;
        initModalChart(type);
    }

    function closeDetailModal() {
        const modal = document.getElementById('detailModal');
        const content = document.getElementById('detailContent');
        const backdrop = document.getElementById('detailBackdrop');
        backdrop.classList.add('opacity-0');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => { modal.classList.add('hidden'); }, 300);
    }

    // === 1. CHART UTAMA DASHBOARD ===
    const chartData = <?= json_encode($chart_data) ?>;
    let chartInstance = null;
    let currentType = 'bar';

    function initChart() {
        const ctx = document.getElementById('adminChart').getContext('2d');
        const filter = document.getElementById('chartFilter').value;
        const dataSet = chartData[filter];

        if(chartInstance) chartInstance.destroy();

        const bgColors = currentType === 'pie' ? ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'] : '#6366f1';
        const borderColor = currentType === 'pie' ? '#ffffff' : '#4f46e5';

        chartInstance = new Chart(ctx, {
            type: currentType,
            data: {
                labels: dataSet.labels,
                datasets: [{
                    label: 'Kehadiran',
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
                scales: currentType === 'pie' ? {} : { y: { beginAtZero: true } },
                plugins: { legend: { display: currentType === 'pie' } }
            }
        });
    }

    function updateChart() { initChart(); }
    function setChartType(type) { currentType = type; initChart(); }
    
    document.addEventListener("DOMContentLoaded", function() {
        initChart();
    });

    // === 2. JAM ===
    function updateClock() {
        const now = new Date();
        const dateOptions = { day: '2-digit', month: 'long', year: 'numeric' };
        const dateString = now.toLocaleDateString('id-ID', dateOptions);
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
        const timeString = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':');
        
        const elDate = document.getElementById('liveDate');
        const elTime = document.getElementById('liveTime');
        
        if (elDate) elDate.innerText = dateString;
        if (elTime) elTime.innerText = timeString;
    }
    setInterval(updateClock, 1000); updateClock();
