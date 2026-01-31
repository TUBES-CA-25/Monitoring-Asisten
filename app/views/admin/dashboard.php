<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<style>
    /* ANIMASI & STYLE DASAR */
    .animate-enter { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    
    /* CAROUSEL INFINITE LOOP */
    @keyframes scroll { 
        0% { transform: translateX(0); } 
        100% { transform: translateX(-50%); } 
    }
    
    .carousel-container { 
        overflow: hidden; 
        padding: 20px 0; 
        mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent); 
    }
    
    .carousel-track { 
        display: flex; 
        /* GAP DIHAPUS agar perhitungan matematika loop presisi */
        /* gap: 2rem; */
        width: max-content; 
        animation: scroll 80s linear infinite; 
        will-change: transform; /* Optimasi performa */
    }
    
    .carousel-track:hover { 
        animation-play-state: paused; 
    }

    /* KARTU ASISTEN POLAROID */
    .polaroid-card {
        background: white; 
        width: 220px; 
        padding: 10px 10px 40px 10px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); 
        border: 1px solid #e5e7eb;
        transition: transform 0.3s; 
        position: relative;
        
        /* [PERBAIKAN UTAMA] Gunakan margin item, bukan gap container */
        margin-right: 2rem; 
        flex-shrink: 0; 
    }
    
    .polaroid-card:hover { transform: scale(1.05); z-index: 10; box-shadow: 0 15px 25px rgba(0,0,0,0.1); }
    
    .led { width: 12px; height: 12px; border-radius: 50%; position: absolute; top: 20px; right: 20px; z-index: 10; animation: pulse 2s infinite; }
    .led.green { background: #22c55e; box-shadow: 0 0 10px #22c55e; }
    .led.red { background: #ef4444; box-shadow: 0 0 10px #ef4444; }
    .led.yellow { background: #eab308; box-shadow: 0 0 10px #eab308; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    .img-status.red { filter: grayscale(100%); opacity: 0.8; }
    .img-status.yellow { filter: sepia(100%); opacity: 0.9; }
</style>

<div class="max-w-7xl mx-auto space-y-8 animate-enter">
    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0 text-center md:text-left">
                <?php
                    $fullName = $user['name'];
                    $parts = explode(',', $fullName);
                    $frontNameOnly = trim($parts[0]);

                    $words = explode(' ', $frontNameOnly);
                    $displayName = $words[0];
                    foreach ($words as $word) {
                        $word = trim($word);
                        if (!empty($word) && strpos($word, '.') === false) {
                            $displayName = $word;
                            break;
                        }
                    }
                ?>
                <h1 class="text-3xl font-extrabold">Halo, <?= htmlspecialchars($displayName) ?> ! 👋</h1>
                <p class="text-blue-100 mt-2 text-sm">Kelola operasional lab dan pantau presensi real-time.</p>
                
                <button onclick="openQRModal()" class="mt-6 bg-white text-blue-600 px-6 py-3 rounded-xl font-bold shadow-lg hover:bg-indigo-50 transition transform hover:scale-105 flex items-center gap-2 mx-auto md:mx-0">
                    <i class="fas fa-qrcode"></i> Buka QR Presensi
                </button>
            </div>
        <div class="text-center md:text-right bg-white/10 p-3 rounded-2xl backdrop-blur-sm border border-white/20">
            <p class="text-[10px] font-bold text-blue-100 uppercase tracking-widest mb-1">Waktu Sistem</p>
            <h2 id="liveDate" class="text-xl font-bold font-mono"><?= date('d F Y') ?></h2>
            <p class="text-sm opacity-90 font-mono mt-1">
                <span id="liveTime" class="bg-blue-900/30 px-2 py-0.5 rounded"><?= date('H:i:s') ?></span> WITA
            </p>
        </div>
    </div>
</div>

    <div class="bg-white p-6 rounded-3xl shadow-lg shadow-gray-200/50 border border-gray-100 mt-8">
        <h4 class="text-sm font-bold text-gray-500 uppercase tracking-widest mb-6">Ringkasan Aktivitas Hari Ini</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 p-4 rounded-2xl border border-blue-100 text-center hover:shadow-md transition">
                <span class="block text-3xl font-extrabold text-blue-600"><?= $stats['total_asisten'] ?></span>
                <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider">Total Asisten</span>
            </div>

            <div class="bg-green-50 p-4 rounded-2xl border border-green-100 text-center hover:shadow-md transition">
                <span class="block text-3xl font-extrabold text-green-600"><?= $stats['hadir_today'] ?></span>
                <span class="text-[10px] font-bold text-green-700 uppercase tracking-wider">Hadir Hari Ini</span>
            </div>

            <div class="bg-yellow-50 p-4 rounded-2xl border border-yellow-100 text-center hover:shadow-md transition">
                <span class="block text-3xl font-extrabold text-yellow-600"><?= $stats['izin_today'] ?></span>
                <span class="text-[10px] font-bold text-yellow-700 uppercase tracking-wider">Izin Hari Ini</span>
            </div>

            <div class="bg-red-50 p-4 rounded-2xl border border-red-100 text-center hover:shadow-md transition">
                <span class="block text-3xl font-extrabold text-red-600"><?= $stats['alpa_today'] ?></span>
                <span class="text-[10px] font-bold text-red-700 uppercase tracking-wider">Tidak Hadir</span>
            </div>
        </div>
    </div>

    <div class="space-y-2">
        <div class="flex items-center justify-between px-2">
            <h3 class="text-sm font-bold text-gray-600 uppercase tracking-wide">Daftar Asisten</h3>
            <div class="flex gap-2 text-[10px] font-bold text-gray-400">
                <span class="flex items-center gap-1"><i class="fas fa-circle text-green-500 text-[6px]"></i> On</span>
                <span class="flex items-center gap-1"><i class="fas fa-circle text-yellow-500 text-[6px]"></i> Izin</span>
                <span class="flex items-center gap-1"><i class="fas fa-circle text-red-500 text-[6px]"></i> Off</span>
            </div>
        </div>
        
        <?php if(!empty($assistants)): ?>
            <div class="carousel-container">
                <div class="carousel-track">
                    <?php 
                    // [PERBAIKAN LOGIKA PHP UNTUK LOOPING]
                    // 1. Buat temporary list
                    $workingList = $assistants;
                    $minItems = 10; // Jumlah minimal item agar track cukup panjang (mengisi layar)

                    // 2. Jika item kurang dari minimal, duplikasi diri sendiri sampai cukup
                    if (!empty($workingList)) {
                        while (count($workingList) < $minItems) {
                            $workingList = array_merge($workingList, $assistants);
                        }
                    }

                    // 3. Duplikasi Final (2 Set) untuk efek infinite scroll CSS (0% ke -50%)
                    $allCards = array_merge($workingList, $workingList); 
                    
                    foreach($allCards as $asisten): 
                        $vStatus = $asisten['visual_status'] ?? 'alpha';
                        $imgFilter = ''; 
                        $dotColor = '';  
                        $statusLabel = '';

                        switch ($vStatus) {
                            case 'online': 
                                $imgFilter = 'grayscale-0'; 
                                $dotColor = 'bg-green-500 animate-pulse shadow-[0_0_8px_#22c55e]';
                                $statusLabel = 'Online'; break;
                            case 'izin': 
                                $imgFilter = 'sepia brightness-90'; 
                                $dotColor = 'bg-yellow-500 shadow-[0_0_8px_#eab308]';
                                $statusLabel = 'Izin'; break;
                            default:
                                $imgFilter = 'grayscale opacity-70'; 
                                $dotColor = 'bg-red-500 shadow-[0_0_8px_#ef4444]';
                                $statusLabel = 'Offline'; break;
                        }

                        $photoPath = !empty($asisten['photo_profile']) && file_exists('uploads/profile/' . $asisten['photo_profile'])
                            ? BASE_URL . '/uploads/profile/' . $asisten['photo_profile'] 
                            : "https://ui-avatars.com/api/?name=" . urlencode($asisten['name']) . "&background=random&size=500";
                        
                        $jsonUser = htmlspecialchars(json_encode($asisten), ENT_QUOTES, 'UTF-8');
                    ?>
                    
                    <div class="polaroid-card rounded-xl cursor-pointer group relative hover:-translate-y-1 transition-all duration-300" onclick="openDetailModal(<?= $jsonUser ?>)">
                        
                        <div class="absolute top-3 right-3 z-20 w-3 h-3 rounded-full border-2 border-white <?= $dotColor ?>" title="<?= $statusLabel ?>"></div>

                        <div class="aspect-square bg-gray-100 mb-3 border border-gray-100 overflow-hidden rounded-lg relative">
                            <img src="<?= $photoPath ?>" 
                                class="w-full h-full object-cover transition-all duration-500 <?= $imgFilter ?>" 
                                alt="<?= $asisten['name'] ?>">
                        </div>

                        <div class="text-center">
                            <h3 class="font-bold text-gray-800 text-sm truncate px-1 leading-tight"><?= $asisten['name'] ?></h3>
                            <p class="text-[10px] text-gray-400 font-bold uppercase mt-1"><?= $asisten['jabatan'] ?? 'Asisten' ?></p>
                        </div>

                        <div class="absolute inset-0 bg-black/10 opacity-0 group-hover:opacity-100 transition flex items-center justify-center rounded-xl">
                            <span class="bg-white/90 px-3 py-1 rounded-full text-[10px] font-bold shadow-sm text-gray-700 backdrop-blur-sm">Lihat Detail</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-gray-400 bg-white rounded-3xl border border-dashed border-gray-200">
                <i class="fas fa-users-slash text-4xl mb-3 opacity-50"></i>
                <p class="text-xs">Belum ada data asisten.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h3 class="text-lg font-bold text-gray-800">Analisis Kehadiran</h3>
            <div class="flex gap-2">
                <select id="chartFilter" onchange="updateChart()" class="bg-gray-50 border-none text-gray-600 text-xs font-bold rounded-lg p-2 focus:ring-2 focus:ring-indigo-200 cursor-pointer outline-none">
                    <option value="daily">Harian</option>
                    <option value="weekly" selected>Mingguan</option>
                    <option value="monthly">Bulanan</option>
                </select>
                <div class="flex bg-gray-100 p-1 rounded-lg">
                    <button onclick="setChartType('bar')" class="p-1.5 rounded hover:bg-white shadow-sm transition"><i class="fas fa-chart-bar text-xs text-gray-600"></i></button>
                    <button onclick="setChartType('line')" class="p-1.5 rounded hover:bg-white shadow-sm transition"><i class="fas fa-chart-line text-xs text-gray-600"></i></button>
                    <button onclick="setChartType('pie')" class="p-1.5 rounded hover:bg-white shadow-sm transition"><i class="fas fa-chart-pie text-xs text-gray-600"></i></button>
                </div>
            </div>
        </div>
        <div class="h-80 w-full"><canvas id="adminChart"></canvas></div>
    </div>
</div>

<div id="qrModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm" onclick="closeQRModal()"></div>
    <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="qrContent">
        <div class="bg-indigo-600 p-6 text-center">
            <h3 class="text-xl font-bold text-white tracking-wide" id="qrTitle">SCAN UNTUK MASUK</h3>
            <p class="text-indigo-200 text-xs mt-1">Arahkan kamera HP ke kode di bawah ini</p>
            <button onclick="closeQRModal()" class="absolute top-4 right-4 text-white/70 hover:text-white"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-8 flex flex-col items-center">
            <div class="p-4 bg-white border-4 border-dashed border-indigo-100 rounded-xl mb-6 shadow-inner relative group">
                <div id="qrcode" class="opacity-90 transition group-hover:opacity-100"></div>
            </div>
            <div class="flex bg-gray-100 p-1 rounded-full w-full max-w-[200px] relative">
                <div class="w-1/2 text-center text-[10px] font-bold text-gray-500 py-2 uppercase z-10">Masuk</div>
                <div class="w-1/2 text-center text-[10px] font-bold text-gray-500 py-2 uppercase z-10">Pulang</div>
                <div id="qrToggleBtn" class="absolute left-1 top-1 bottom-1 w-[calc(50%-4px)] bg-white rounded-full shadow-md transition-all duration-300 border border-gray-200"></div>
                <button onclick="setQRMode(true)" class="absolute left-0 top-0 w-1/2 h-full z-20"></button>
                <button onclick="setQRMode(false)" class="absolute right-0 top-0 w-1/2 h-full z-20"></button>
            </div>
        </div>
    </div>
</div>

<div id="detailModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm transition-opacity opacity-0" id="detailBackdrop" onclick="closeDetailModal()"></div>
    
    <div class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col md:flex-row max-h-[90vh]" id="detailContent">
        
        <div class="w-full md:w-1/3 bg-gray-50 border-r border-gray-100 flex flex-col">
            <div class="p-6 flex flex-col items-center text-center overflow-y-auto custom-scrollbar h-full">
                
                <div class="w-24 h-24 rounded-full p-1 bg-white shadow-lg mb-3 relative group">
                    <img id="m_photo" src="" class="w-full h-full rounded-full object-cover">
                    <div id="m_dot_overlay" class="absolute bottom-1 right-1 w-5 h-5 rounded-full border-2 border-white shadow-sm"></div>
                </div>
                <h2 id="m_name" class="text-lg font-extrabold text-gray-800 leading-tight px-2"></h2>
                <p id="m_position" class="text-xs text-indigo-600 font-bold uppercase tracking-wider mt-1 mb-6 px-3 py-1 bg-indigo-50 rounded-full inline-block"></p>
                
                <div class="w-full space-y-3 text-left">
                    <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm">
                        <p class="text-[10px] text-gray-400 font-bold uppercase mb-1">Nomor Induk (NIM)</p>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-id-badge text-gray-300"></i>
                            <span id="m_nim" class="font-mono font-bold text-gray-700 text-xs">-</span>
                        </div>
                    </div>

                    <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm">
                        <p class="text-[10px] text-gray-400 font-bold uppercase mb-1">Info Akademik</p>
                        <div class="text-xs text-gray-600 space-y-1">
                            <div class="flex items-center gap-2"><i class="fas fa-graduation-cap text-gray-300 w-4"></i> <span id="m_class" class="font-bold">-</span></div>
                            <div class="flex items-center gap-2"><i class="fas fa-university text-gray-300 w-4"></i> <span id="m_prodi">-</span></div>
                        </div>
                    </div>

                    <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm">
                        <p class="text-[10px] text-gray-400 font-bold uppercase mb-1">Kontak</p>
                        <div class="text-xs text-gray-600 space-y-2">
                            <div class="flex items-center gap-2 overflow-hidden"><i class="fas fa-envelope text-gray-300 w-4"></i> <span id="m_email" class="truncate">-</span></div>
                            <div class="flex items-center gap-2"><i class="fab fa-whatsapp text-gray-300 w-4"></i> <span id="m_phone" class="font-mono">-</span></div>
                        </div>
                    </div>

                    <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm">
                        <p class="text-[10px] text-gray-400 font-bold uppercase mb-1">Alamat</p>
                        <div class="flex items-start gap-2">
                            <i class="fas fa-map-marker-alt text-gray-300 w-4 mt-0.5"></i>
                            <span id="m_address" class="text-xs text-gray-600 leading-snug">-</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 w-full">
                    <a id="btnSchedule" href="/admin/assistant_schedule" class="flex items-center justify-center w-full py-3 rounded-xl bg-indigo-600 text-white font-bold text-xs uppercase tracking-wider hover:bg-indigo-700 shadow-md transition-all transform hover:scale-[1.02]">
                        <i class="fas fa-calendar-alt mr-2"></i> Jadwal Lengkap
                    </a>
                </div>

            </div>
        </div>

        <div class="w-full md:w-2/3 flex flex-col bg-white">
            <div class="flex border-b border-gray-100 px-6 py-4 justify-between items-center shrink-0">
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-chart-pie text-indigo-500"></i> Statistik & Kehadiran
                </h3>
                <button onclick="closeDetailModal()" class="w-8 h-8 rounded-full bg-gray-50 hover:bg-red-50 text-gray-400 hover:text-red-500 transition flex items-center justify-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="flex-1 p-6 overflow-y-auto custom-scrollbar">
                
                <div id="m_status_box" class="mb-6 p-4 rounded-2xl border flex items-center gap-4 transition-all shadow-sm">
                    <div id="m_status_icon_bg" class="w-12 h-12 rounded-full flex items-center justify-center text-xl shrink-0 transition-colors">
                        <i id="m_status_icon" class="fas"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-widest opacity-60 mb-0.5">Status Hari Ini</p>
                        <h4 id="m_status_text" class="text-lg font-extrabold">Loading...</h4>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total Kehadiran</h4>
                        
                        <div class="flex bg-gray-100 rounded-lg p-1 gap-1">
                            <button onclick="setModalChartType('bar')" class="p-1.5 rounded-md hover:bg-white hover:shadow-sm text-gray-500 transition text-xs" title="Bar Chart"><i class="fas fa-chart-bar"></i></button>
                            <button onclick="setModalChartType('doughnut')" class="p-1.5 rounded-md hover:bg-white hover:shadow-sm text-gray-500 transition text-xs" title="Doughnut Chart"><i class="fas fa-chart-pie"></i></button>
                            </div>
                    </div>
                    
                    <div class="relative h-48 w-full flex items-center justify-center">
                        <canvas id="modalChartCanvas"></canvas>
                    </div>

                    <div class="grid grid-cols-3 gap-2 mt-4 text-center">
                        <div class="p-2 rounded-lg bg-green-50 border border-green-100">
                            <span class="block text-xl font-bold text-green-600" id="stat_hadir">0</span>
                            <span class="text-[10px] uppercase font-bold text-green-400">Hadir</span>
                        </div>
                        <div class="p-2 rounded-lg bg-yellow-50 border border-yellow-100">
                            <span class="block text-xl font-bold text-yellow-600" id="stat_izin">0</span>
                            <span class="text-[10px] uppercase font-bold text-yellow-400">Izin</span>
                        </div>
                        <div class="p-2 rounded-lg bg-red-50 border border-red-100">
                            <span class="block text-xl font-bold text-red-600" id="stat_alpa">0</span>
                            <span class="text-[10px] uppercase font-bold text-red-400">Alpa</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    // ==========================================
    // 1. LOGIKA QR CODE (REAL TIME)
    // ==========================================
    const qrDataIn = '<?= $qr_in ?? "" ?>'; 
    const qrDataOut = '<?= $qr_out ?? "" ?>';
    const qrcodeContainer = document.getElementById("qrcode");
    
    // Inisialisasi QR Code jika elemen ada
    let qrCodeObj = null;
    if (qrcodeContainer) {
        qrCodeObj = new QRCode(qrcodeContainer, { width: 200, height: 200 });
    }

    let qrInterval = null;
    let currentMode = 'check_in';

    const roleSegment = window.location.href.includes('superadmin') ? 'superadmin' : 'admin';
    const qrFetchUrl = `<?= BASE_URL ?>/${roleSegment}/getQrAjax`;

    function openQRModal() {
        document.getElementById('qrModal').classList.remove('hidden');
        setTimeout(() => { 
            document.getElementById('qrContent').classList.remove('scale-95', 'opacity-0'); 
            document.getElementById('qrContent').classList.add('scale-100', 'opacity-100'); 
        }, 10);
        
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
            // Refresh QR Masuk setiap 4.5 menit
            qrInterval = setInterval(() => { fetchAndRenderQR('check_in'); }, 270000);
        } else {
            currentMode = 'check_out';
            title.innerText = "SCAN UNTUK PULANG";
            container.className = "bg-red-600 p-6 text-center transition-colors duration-300";
            toggle.style.transform = "translateX(100%)";
            
            fetchAndRenderQR('check_out');
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
            ? '<?= BASE_URL ?>/uploads/profile/' + user.photo_profile 
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
        const currentRole = window.location.href.includes('superadmin') ? 'superadmin' : 'admin';
        if (btnSchedule) {
            btnSchedule.href = `<?= BASE_URL ?>/${currentRole}/assistantSchedule/${user.id_user}`;
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
    const chartData = <?= json_encode($chart_data ?? []) ?>;
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
</script>