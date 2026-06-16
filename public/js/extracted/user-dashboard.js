
    // Data Chart dari PHP (Controller)
    const chartData = <?= json_encode($chart_data ?? []) ?>;
    let chartInstance = null;
    let currentType = 'bar';

    // 1. FUNGSI JAM DIGITAL
    function updateClock() {
        const now = new Date();
        
        const dateOptions = { day: '2-digit', month: 'long', year: 'numeric' };
        const dateString = now.toLocaleDateString('id-ID', dateOptions);

        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
        const timeString = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':');
        
        // Update DOM dengan Safety Check
        const elDate = document.getElementById('liveDate');
        const elTime = document.getElementById('liveTime');
        
        if (elDate) elDate.innerText = dateString;
        if (elTime) elTime.innerText = timeString;
    }

    // 2. FUNGSI GRAFIK
    function initChart() {
        const ctxEl = document.getElementById('userChart');
        if (!ctxEl) return; // Hentikan jika canvas tidak ada
        
        const ctx = ctxEl.getContext('2d');
        const filterEl = document.getElementById('timeFilter');
        
        // Logika Fallback: Jika data Mingguan/Bulanan belum ada, default ke 'daily'
        let filter = filterEl ? filterEl.value : 'daily';
        if (!chartData[filter]) {
            // Jika data untuk filter yang dipilih kosong, cari data yang tersedia (misal 'daily')
            if(chartData['daily']) {
                filter = 'daily';
                if(filterEl) filterEl.value = 'daily'; // Update dropdown UI
            }
        }

        const dataSet = chartData[filter] || { labels: [], data: [] };

        if(chartInstance) chartInstance.destroy();

        // Warna untuk Pie Chart vs Bar/Line
        const bgColors = currentType === 'pie' 
            ? ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6366f1', '#14b8a6'] 
            : '#94a3b8';
        
        const labelText = filter === 'daily' ? 'Status Hadir' : (filter === 'weekly' ? 'Jumlah Hari Hadir' : 'Total Kehadiran');

        chartInstance = new Chart(ctx, {
            type: currentType,
            data: {
                labels: dataSet.labels,
                datasets: [{
                    label: labelText,
                    data: dataSet.data,
                    backgroundColor: currentType === 'bar' ? '#cbd5e1' : bgColors,
                    hoverBackgroundColor: '#3b82f6',
                    borderColor: '#64748b',
                    borderWidth: 1,
                    borderRadius: 4,
                    tension: 0.4,
                    fill: currentType === 'line'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: currentType === 'pie' ? {} : { 
                    y: { beginAtZero: true, grid: { display: false }, ticks: { precision: 0 } }, 
                    x: { grid: { display: false } } 
                },
                plugins: { legend: { display: currentType === 'pie' } }
            }
        });
    }

    let shouldReload = false;

    // 1. EVENT LISTENER FORM
    document.getElementById('leaveForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Mencegah reload halaman bawaan
        
        const form = this;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Ubah tombol jadi loading
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Mengirim...';
        submitBtn.disabled = true;

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Kembalikan tombol
            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;

            if (data.status === 'success') {
                showCustomAlert('success', data.title, data.message, true); // True = Reload halaman saat tutup
                form.reset();
            } else {
                showCustomAlert('error', 'Gagal', data.message, false);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;
            showCustomAlert('error', 'Error', 'Terjadi kesalahan koneksi server.', false);
        });
    });

    // 2. FUNGSI MENAMPILKAN MODAL
    function showCustomAlert(type, title, message, reloadOnClose = false) {
        shouldReload = reloadOnClose;
        
        const modal = document.getElementById('customAlertModal');
        const content = document.getElementById('alertContent');
        const backdrop = document.getElementById('alertBackdrop');
        const iconBg = document.getElementById('alertIconBg');
        const icon = document.getElementById('alertIcon');
        const btn = document.getElementById('alertBtn');

        // Set Content
        document.getElementById('alertTitle').innerText = title;
        document.getElementById('alertMessage').innerText = message;

        // Styling berdasarkan Tipe
        if (type === 'success') {
            iconBg.className = 'w-20 h-20 rounded-full flex items-center justify-center mb-4 bg-green-100 text-green-500';
            icon.className = 'fas fa-check';
            btn.className = 'w-full py-3.5 rounded-xl font-bold text-white shadow-lg bg-green-600 hover:bg-green-700 shadow-green-500/30 transition';
        } else {
            iconBg.className = 'w-20 h-20 rounded-full flex items-center justify-center mb-4 bg-red-100 text-red-500';
            icon.className = 'fas fa-times';
            btn.className = 'w-full py-3.5 rounded-xl font-bold text-white shadow-lg bg-red-600 hover:bg-red-700 shadow-red-500/30 transition';
        }

        // Tampilkan dengan Animasi
        modal.classList.remove('hidden');
        // Sedikit delay agar transisi CSS berjalan
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            content.classList.remove('scale-90', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    // 3. FUNGSI MENUTUP MODAL
    function closeCustomAlert() {
        const modal = document.getElementById('customAlertModal');
        const content = document.getElementById('alertContent');
        const backdrop = document.getElementById('alertBackdrop');

        // Animasi Keluar
        backdrop.classList.add('opacity-0');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-90', 'opacity-0');

        setTimeout(() => {
            modal.classList.add('hidden');
            if (shouldReload) {
                window.location.reload(); // Reload halaman hanya jika sukses
            }
        }, 300);
    }

    // Fungsi Helper Global
    function updateChart() { initChart(); }
    function setChartType(type) { currentType = type; initChart(); }

    // 3. INISIALISASI SETELAH DOM READY
    document.addEventListener("DOMContentLoaded", function() {
        // Jalankan Jam
        setInterval(updateClock, 1000);
        updateClock();

        // Jalankan Grafik
        initChart();
        
        // Listener Filter Grafik
        const filterEl = document.getElementById('timeFilter');
        if(filterEl) filterEl.addEventListener('change', () => initChart());
    });
