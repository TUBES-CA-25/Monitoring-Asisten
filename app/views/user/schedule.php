<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

<style>
    /* ANIMASI HALAMAN */
    .animate-enter { animation: fadeInUp 0.5s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    
    /* ANIMASI DOTS (TRANSISI FILTER) */
    @keyframes popIn {
        0% { transform: scale(0); opacity: 0; }
        80% { transform: scale(1.2); opacity: 1; }
        100% { transform: scale(1); opacity: 1; }
    }

    /* KALENDER */
    #calendar { 
        font-family: 'Inter', sans-serif; 
        --fc-border-color: #f1f5f9; 
        --fc-button-text-color: #475569; 
        --fc-button-bg-color: white; 
        --fc-button-border-color: #e2e8f0;
        --fc-today-bg-color: #f8fafc;
        min-height: 750px;
    }
    .fc-header-toolbar { margin-bottom: 1.5rem !important; }
    .fc-button { border-radius: 10px !important; font-weight: 600; padding: 0.6rem 1.2rem; }
    .fc-button-active { background: #eff6ff !important; color: #2563eb !important; border-color: #bfdbfe !important; }
    .fc-daygrid-day { cursor: pointer !important; transition: background 0.2s; } 
    .fc-daygrid-day:hover { background-color: #f0f9ff; }
    .fc-daygrid-day-frame { position: relative !important; min-height: 100%; z-index: 1; }
    .fc-event { display: none !important; } 

    /* LAYERS (INTERAKSI & VISUAL) */
    .day-click-overlay {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        z-index: 50; background: transparent; cursor: pointer;
    }
    
    /* CONTAINER DOTS */
    .day-dots-container {
        display: flex; justify-content: center; flex-wrap: wrap; gap: 3px; padding: 0 4px;
        position: absolute; top: 32px; left: 0; right: 0;
        z-index: 40; pointer-events: none !important;
        transition: all 0.3s ease; /* Transisi Container */
    }
    
    /* ITEM DOT */
    .dot-category {
        width: 8px; height: 8px; border-radius: 50%;
        box-shadow: 0 1px 1px rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.5);
        animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; /* Efek Pop-up */
    }

    /* UTILS & SCROLLBAR */
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .legend-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 8px; }
    
    /* UPCOMING CARD STYLE */
    .upcoming-card { transition: all 0.2s; border: 1px solid transparent; }
    .upcoming-card:hover { transform: translateX(2px); background-color: #f8fafc; border-color: #e2e8f0; }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-12">

    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0 text-center md:text-left">
                <h1 class="text-3xl font-extrabold tracking-tight">Manajemen Jadwal</h1>
                <p class="text-slate-300 mt-2 text-sm">Kelola dan monitoring jadwal Anda.</p>
            </div>
            <div class="text-center md:text-right bg-white/10 p-3 rounded-2xl backdrop-blur-sm border border-white/20">
                <p class="text-[10px] font-bold text-slate-300 uppercase tracking-widest mb-1">Waktu Sistem</p>
                <h2 id="liveDate" class="text-xl font-bold font-mono"><?= date('d F Y') ?></h2>
                <p class="text-sm opacity-90 font-mono mt-1">
                    <span id="liveTime" class="bg-slate-900/50 px-2 py-0.5 rounded"><?= date('H:i:s') ?></span> WITA
                </p>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 h-[850px]">
        
        <div class="w-full lg:w-72 space-y-5 flex flex-col h-full">

            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200 shrink-0">
                <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wide mb-4">Kategori</h3>
                <div class="space-y-3 text-sm font-medium text-gray-600">
                    <div class="flex items-center"><span class="legend-dot bg-gray-800"></span> Umum (Lab)</div>
                    <div class="flex items-center"><span class="legend-dot bg-blue-500"></span> Asisten Lab</div>
                    <div class="flex items-center"><span class="legend-dot bg-orange-500"></span> Piket</div>
                    <div class="flex items-center"><span class="legend-dot bg-green-500"></span> Kuliah Asisten</div>
                </div>
            </div>
            
            <button onclick="openFormModal('add')" class="w-full bg-white text-blue-600 px-4 py-3.5 rounded-xl font-bold shadow-sm hover:shadow-md hover:bg-blue-50 transition transform hover:scale-[1.02] flex items-center justify-center gap-2 border border-blue-100 shrink-0">
                <i class="fas fa-plus-circle text-lg"></i> Tambah Jadwal Kuliah
            </button>

            <div class="bg-white p-5 rounded-3xl shadow-sm border border-gray-200 shrink-0 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                    <h3 class="font-extrabold text-gray-700 text-xs uppercase tracking-wide"><i class="fas fa-filter mr-1"></i> Filter Jadwal</h3>
                    <button onclick="resetFilters()" class="text-[10px] text-blue-500 hover:text-blue-700 font-bold">Reset</button>
                </div>
                
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block">Kategori</label>
                    <select id="filterCategory" onchange="renderCustomLayers()" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-100 cursor-pointer">
                        <option value="all">Semua Kategori</option>
                        <option value="umum">Umum (Lab)</option>
                        <option value="asisten">Asisten (Saya)</option>
                        <option value="piket">Piket (Saya)</option>
                        <option value="kuliah">Kuliah (Saya)</option>
                    </select>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block">Hari</label>
                    <select id="filterDay" onchange="renderCustomLayers()" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-100 cursor-pointer">
                        <option value="all">Semua Hari</option>
                        <option value="1">Senin</option>
                        <option value="2">Selasa</option>
                        <option value="3">Rabu</option>
                        <option value="4">Kamis</option>
                        <option value="5">Jumat</option>
                        <option value="6">Sabtu</option>
                        <option value="7">Minggu</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block">Mulai</label>
                        <input type="time" id="filterStart" onchange="renderCustomLayers()" class="w-full px-2 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block">Selesai</label>
                        <input type="time" id="filterEnd" onchange="renderCustomLayers()" class="w-full px-2 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col overflow-hidden flex-1 min-h-0">
                <div class="p-5 border-b border-gray-100 bg-white sticky top-0 z-20">
                    <h3 class="font-extrabold text-gray-700 text-xs uppercase tracking-wide flex items-center gap-2">
                        <i class="fas fa-calendar-alt text-blue-500"></i> Jadwal Akan Datang
                    </h3>
                </div>
                
                <div class="flex-1 overflow-y-auto p-3 space-y-2 custom-scrollbar" id="upcomingListContainer">
                    <div class="text-center py-8 text-gray-400 text-xs italic">Memuat data...</div>
                </div>
            </div>
        </div>

        <div class="flex-1 bg-white rounded-3xl shadow-sm border border-gray-200 p-6 relative overflow-hidden">
            <div id='calendar' class="h-full"></div>
        </div>
    </div>
</div>

<div id="dayDetailModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="detailBackdrop" onclick="closeDayModal()"></div>
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col max-h-[85vh]" id="detailContent">
        <div class="bg-white px-6 py-5 border-b border-gray-100 flex justify-between items-center shrink-0">
            <div><p class="text-blue-600 text-[10px] font-extrabold uppercase tracking-widest mb-1">Detail Kegiatan</p><h3 id="modalDateTitle" class="text-xl font-extrabold text-gray-800"></h3></div>
            <button onclick="closeDayModal()" class="w-8 h-8 rounded-full bg-gray-50 hover:bg-red-50 text-gray-400 hover:text-red-500 transition flex items-center justify-center"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-0 overflow-y-auto flex-1 bg-gray-50 custom-scrollbar" id="modalListContainer"></div>
        <div class="p-4 border-t border-gray-100 bg-white shrink-0">
            <button onclick="openFormModal('add')" class="w-full py-3 rounded-xl bg-blue-600 text-white font-bold shadow-lg shadow-blue-500/30 hover:bg-blue-700 transition flex items-center justify-center gap-2">
                <i class="fas fa-plus"></i> Tambah Jadwal Kuliah
            </button>
        </div>
    </div>
</div>

<div id="formModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="formBackdrop" onclick="closeFormModal()"></div>
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col" id="formContent">
        <div class="bg-white px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-lg text-gray-800" id="formModalTitle">Jadwal Kuliah</h3>
            <button onclick="closeFormModal()" class="text-gray-400 hover:text-red-500"><i class="fas fa-times"></i></button>
        </div>
        <form id="scheduleForm" method="POST" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto custom-scrollbar">
            <input type="hidden" name="id_schedule" id="inputId"> 
            <input type="hidden" name="type" value="kuliah">
            
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Mata Kuliah</label>
                <input type="text" name="title" id="inputTitle" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-green-100" placeholder="Contoh: Pemrograman Web">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Kelas</label>
                    <input type="text" name="kelas" id="inputKelas" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-green-100" placeholder="A">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Ruangan</label>
                    <input type="text" name="location" id="inputLocation" value="Lab Terpadu" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-green-100">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Dosen Pengampu</label>
                <input type="text" name="dosen" id="inputDosen" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-green-100" placeholder="Nama Dosen">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Tgl Mulai</label><input type="date" name="date" id="inputDate" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-green-100"></div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Mode Perulangan</label>
                    <select name="model_perulangan" id="inputRepeatModel" class="w-full px-3 py-2.5 bg-white border border-gray-200 rounded-xl text-sm" onchange="handleRepeatChange()">
                        <option value="sekali">Sekali</option>
                        <option value="mingguan">Mingguan</option>
                        <option value="rentang">Berurutan</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Jam Mulai</label><input type="time" name="start_time" id="inputStart" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-green-100"></div>
                <div><label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Jam Selesai</label><input type="time" name="end_time" id="inputEnd" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-green-100"></div>
            </div>
            
            <div id="endDateContainer" class="hidden">
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Sampai Tanggal</label>
                <input type="date" name="end_date_repeat" id="inputEndDateRepeat" class="w-full px-3 py-2.5 bg-white border border-gray-200 rounded-xl text-sm">
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold shadow-lg shadow-blue-500/30 transition transform hover:-translate-y-0.5">Simpan Jadwal</button>
        </form>
    </div>
</div>

<div id="customAlertModal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity opacity-0" id="alertBackdrop"></div>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative z-10 overflow-hidden transform scale-90 opacity-0 transition-all duration-300 flex flex-col items-center p-6 text-center" id="alertContent">
        <div id="alertIconBg" class="w-16 h-16 rounded-full flex items-center justify-center mb-4"><i id="alertIcon" class="fas text-3xl"></i></div>
        <h3 id="alertTitle" class="text-xl font-extrabold text-gray-800 mb-2"></h3><p id="alertMessage" class="text-sm text-gray-500 mb-6 px-2"></p>
        <button onclick="closeCustomAlert()" class="w-full py-3 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02]" id="alertBtn">OK</button>
    </div>
</div>
<div id="customConfirmModal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity opacity-0" id="confirmBackdrop"></div>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative z-10 overflow-hidden transform scale-90 opacity-0 transition-all duration-300 flex flex-col items-center p-6 text-center" id="confirmContent">
        <div class="w-16 h-16 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center mb-4"><i class="fas fa-exclamation-triangle text-3xl"></i></div>
        <h3 class="text-xl font-extrabold text-gray-800 mb-2">Hapus?</h3><p class="text-sm text-gray-500 mb-6 px-2">Jadwal ini akan dihapus permanen.</p>
        <div class="flex gap-3 w-full"><button onclick="closeCustomConfirm()" class="flex-1 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold hover:bg-gray-50 transition">Batal</button><button id="confirmYesBtn" class="flex-1 py-3 rounded-xl bg-red-600 text-white font-bold shadow-lg hover:bg-red-700 transition">Ya, Hapus</button></div>
    </div>
</div>

<script>
    // --- 1. CONFIG & DATA ---
    const rawEvents = <?= json_encode($raw_schedules ?? []) ?>;
    const currentUserId = "<?= $_SESSION['profil_id'] ?>"; 
    
    <?php if(isset($_SESSION['flash'])): ?>
        document.addEventListener("DOMContentLoaded", function() { setTimeout(() => { showCustomAlert('<?= $_SESSION['flash']['type'] ?>', '<?= $_SESSION['flash']['title'] ?>', '<?= $_SESSION['flash']['message'] ?>'); }, 300); });
    <?php unset($_SESSION['flash']); endif; ?>

    let calendar;
    let selectedDateStr = new Date().toISOString().split('T')[0];

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            themeSystem: 'standard',
            headerToolbar: { left: 'title', right: 'prev,today,next' },
            events: [], selectable: false, selectMirror: true,
            datesSet: function() { renderCustomLayers(); }
        });
        calendar.render();
        renderUpcomingList(); 
    });

    // --- 2. LOGIC FILTERING ---
    function shouldShowEvent(evt) {
        const cat = document.getElementById('filterCategory').value;
        const type = evt.type.toLowerCase();
        if (cat !== 'all' && type !== cat) return false;

        const day = document.getElementById('filterDay').value;
        if (day !== 'all' && String(evt.day_of_week) !== String(day)) return false;

        const tStart = document.getElementById('filterStart').value;
        const tEnd = document.getElementById('filterEnd').value;
        const evtStart = (evt.start_time || '00:00').substring(0,5);
        const evtEnd = (evt.end_time || '00:00').substring(0,5);

        if (tStart && evtEnd <= tStart) return false;
        if (tEnd && evtStart >= tEnd) return false;

        return true;
    }

    function resetFilters() {
        document.getElementById('filterCategory').value = 'all';
        document.getElementById('filterDay').value = 'all';
        document.getElementById('filterStart').value = '';
        document.getElementById('filterEnd').value = '';
        renderCustomLayers();
    }

    // --- 3. LOGIC TANGGAL ---
    function isEventOnDate(evt, checkDateStr) {
        const startDate = evt.start_date;
        const endDate = evt.end_date || startDate;
        const repeatModel = evt.model_perulangan || 'sekali';
        if (repeatModel === 'sekali') return startDate === checkDateStr;
        if (repeatModel === 'rentang') return checkDateStr >= startDate && checkDateStr <= endDate;
        if (repeatModel === 'mingguan') {
            if (checkDateStr >= startDate && checkDateStr <= endDate) {
                const d = new Date(checkDateStr + "T00:00:00").getDay(); 
                const dayCheck = d === 0 ? 7 : d; 
                return String(dayCheck) === String(evt.day_of_week);
            }
        }
        return false;
    }

    // --- 4. RENDER LAYERS (DENGAN FIX NAMA CLASS) ---
    function renderCustomLayers() {
        // [FIX UTAMA: Hapus menggunakan class yang benar]
        document.querySelectorAll('.day-click-overlay').forEach(e => e.remove());
        document.querySelectorAll('.day-dots-container').forEach(e => e.remove());
        
        document.querySelectorAll('.fc-daygrid-day').forEach(cell => {
            const dateStr = cell.getAttribute('data-date'); if(!dateStr) return;
            const frame = cell.querySelector('.fc-daygrid-day-frame'); if(!frame) return;

            const clickLayer = document.createElement('div');
            clickLayer.className = 'day-click-overlay';
            clickLayer.onclick = function(e) {
                e.stopPropagation(); selectedDateStr = dateStr;
                renderDayDetails(dateStr); openDayModal();
            };
            frame.appendChild(clickLayer);

            let uniqueColors = new Set();
            rawEvents.forEach(evt => {
                if (!shouldShowEvent(evt)) return; 

                if (isEventOnDate(evt, dateStr)) {
                    let color = '#3b82f6'; 
                    const type = evt.type.toLowerCase();
                    if(type === 'piket') color = '#f97316';
                    if(type === 'umum') color = '#1f2937';
                    if(type === 'kuliah') color = '#10b981';
                    uniqueColors.add(color);
                }
            });

            if (uniqueColors.size > 0) {
                const dotsLayer = document.createElement('div');
                dotsLayer.className = 'day-dots-container';
                uniqueColors.forEach(color => {
                    const dot = document.createElement('div');
                    dot.className = 'dot-category';
                    dot.style.backgroundColor = color;
                    dotsLayer.appendChild(dot);
                });
                frame.appendChild(dotsLayer);
            }
        });
    }

    function renderUpcomingList() {
        const container = document.getElementById('upcomingListContainer');
        const now = new Date();
        now.setHours(0,0,0,0); // Reset jam ke 00:00 hari ini agar perbandingan akurat

        let upcoming = [];

        rawEvents.forEach(evt => {
            // Konversi tanggal dari DB
            const startDate = new Date(evt.start_date + "T00:00:00");
            const endDate = evt.end_date ? new Date(evt.end_date + "T23:59:59") : null;
            
            let instanceDate = null; // Tanggal kemunculan berikutnya

            // 1. TIPE SEKALI
            if (evt.model_perulangan === 'sekali') {
                if (startDate >= now) {
                    instanceDate = startDate;
                }
            } 
            // 2. TIPE RENTANG (Berurutan)
            else if (evt.model_perulangan === 'rentang') {
                // Jika hari ini masih dalam rentang (start <= now <= end)
                if (startDate <= now && (!endDate || endDate >= now)) {
                    instanceDate = now; // Tampilkan sebagai jadwal hari ini
                } 
                // Atau jika rentang baru mulai di masa depan
                else if (startDate > now) {
                    instanceDate = startDate;
                }
            } 
            // 3. TIPE MINGGUAN
            else if (evt.model_perulangan === 'mingguan') {
                // Konversi Hari DB (1=Senin...7=Minggu) ke JS (0=Minggu...6=Sabtu)
                const targetDayDB = parseInt(evt.day_of_week); 
                const targetDayJS = targetDayDB === 7 ? 0 : targetDayDB; 

                // Cek 7 hari ke depan mulai dari hari ini untuk mencari kecocokan hari
                let checkDate = new Date(now);
                for(let i=0; i<7; i++) {
                    // Jika harinya cocok
                    if (checkDate.getDay() === targetDayJS) {
                        // Pastikan tanggal ini valid dalam durasi jadwal (>= start_date jadwal & <= end_date jadwal)
                        if (checkDate >= startDate && (!endDate || checkDate <= endDate)) {
                            instanceDate = new Date(checkDate);
                        }
                        break; // Ketemu hari terdekat, stop loop
                    }
                    checkDate.setDate(checkDate.getDate() + 1); // Cek besok
                }
            }

            // Jika ditemukan tanggal valid yang akan datang/hari ini
            if (instanceDate) {
                // Clone event dan simpan tanggal tampilan yang sudah dihitung
                let nextEvt = {...evt};
                nextEvt.displayDate = instanceDate; 
                upcoming.push(nextEvt);
            }
        });

        // SORTING: Urutkan berdasarkan Tanggal Tampilan, lalu Jam
        upcoming.sort((a, b) => {
            const dateA = a.displayDate;
            const dateB = b.displayDate;
            if (dateA.getTime() !== dateB.getTime()) return dateA - dateB;
            return (a.start_time || '00:00').localeCompare(b.start_time || '00:00');
        });

        // Limit tampilan (misal 10 jadwal terdekat)
        upcoming = upcoming.slice(0, 10);

        // RENDER KE HTML
        if (upcoming.length === 0) {
            container.innerHTML = `<div class="text-center py-8 text-gray-400 text-xs italic">Tidak ada jadwal mendatang.</div>`;
            return;
        }

        container.innerHTML = '';
        upcoming.forEach(evt => {
            const d = evt.displayDate;
            const dayName = d.toLocaleDateString('id-ID', { weekday: 'short' }); // Senin, Sel, dll
            const dateNum = d.getDate();
            const timeFmt = (evt.start_time || '00:00').substring(0,5);
            const type = (evt.type || 'asisten').toLowerCase();
            
            // Warna Badge
            let colorClass = 'bg-blue-50 text-blue-600 border-blue-200';
            if(type === 'piket') colorClass = 'bg-orange-50 text-orange-600 border-orange-200';
            if(type === 'umum') colorClass = 'bg-gray-50 text-gray-600 border-gray-200';
            if(type === 'kuliah') colorClass = 'bg-green-50 text-green-600 border-green-200';

            container.innerHTML += `
                <div class="upcoming-card bg-white p-3 rounded-2xl border border-gray-100 flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl ${colorClass} border flex flex-col items-center justify-center shrink-0">
                        <span class="text-[9px] font-bold uppercase leading-none opacity-80">${dayName}</span>
                        <span class="text-xs font-bold leading-none mt-0.5">${dateNum}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="font-bold text-gray-800 text-xs truncate" title="${evt.title}">${evt.title}</h4>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-[10px] text-gray-500 font-mono flex items-center">
                                <i class="far fa-clock mr-1 text-[9px]"></i>${timeFmt}
                            </span>
                            <span class="text-[9px] px-1.5 py-0.5 rounded-md bg-gray-100 text-gray-500 truncate capitalize">
                                ${type}
                            </span>
                        </div>
                    </div>
                </div>
            `;
        });
    }

    // --- 6. RENDER MODAL ---
    function renderDayDetails(dateStr) {
        const container = document.getElementById('modalListContainer');
        const dateObjForTitle = new Date(dateStr + "T00:00:00");
        document.getElementById('modalDateTitle').innerText = dateObjForTitle.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        container.innerHTML = '';

        const visibleEvents = rawEvents.filter(evt => {
            if (!shouldShowEvent(evt)) return false; 
            return isEventOnDate(evt, dateStr);
        });

        visibleEvents.sort((a, b) => (a.start_time || '00:00').localeCompare(b.start_time || '00:00'));

        if (visibleEvents.length === 0) {
            container.innerHTML = `<div class="flex flex-col items-center justify-center py-12 text-gray-400"><div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3 text-2xl opacity-50"><i class="fas fa-calendar-times"></i></div><p class="text-sm italic">Tidak ada jadwal.</p></div>`;
            return;
        }

        visibleEvents.forEach(evt => {
            const type = (evt.type || 'asisten').toLowerCase();
            const timeStr = `${(evt.start_time || '00:00').substring(0,5)} - ${(evt.end_time || '00:00').substring(0,5)}`;
            let badgeClass = 'bg-blue-50 text-blue-600 border-blue-100';
            let icon = 'fa-user-tie';
            if(type === 'piket') { badgeClass = 'bg-orange-50 text-orange-600 border-orange-100'; icon = 'fa-broom'; }
            else if(type === 'umum') { badgeClass = 'bg-gray-800 text-white border-gray-700'; icon = 'fa-building'; }
            else if(type === 'kuliah') { badgeClass = 'bg-green-50 text-green-600 border-green-100'; icon = 'fa-graduation-cap'; }

            let actions = '';
            if (type === 'kuliah' && String(evt.id_profil) === String(currentUserId)) {
                const props = {
                    id: evt.id, title: evt.title, location: evt.location || 'Lab',
                    dosen: evt.dosen || '', kelas: evt.kelas || '', 
                    rawDate: evt.start_date, fmtStartTime: (evt.start_time || '00:00').substring(0,5),
                    fmtEndTime: (evt.end_time || '00:00').substring(0,5),
                    repeatModel: evt.model_perulangan || 'sekali', endDateRepeat: evt.end_date
                };
                const jsonStr = JSON.stringify({ extendedProps: props }).replace(/"/g, '&quot;');
                
                actions = `<div class="flex gap-1 pl-3 border-l border-gray-100 ml-3 shrink-0">
                    <button onclick="openFormModal('edit', ${jsonStr})" class="w-8 h-8 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition flex items-center justify-center"><i class="fas fa-pen text-xs"></i></button>
                    <button onclick="triggerDelete('${evt.id}', 'kuliah')" class="w-8 h-8 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition flex items-center justify-center"><i class="fas fa-trash text-xs"></i></button>
                </div>`;
            }

            let extraInfo = '';
            if (evt.dosen || evt.kelas) {
                extraInfo = `<div class="mt-1 flex gap-2 text-[10px] text-gray-500">
                    ${evt.dosen ? `<span class="bg-gray-100 px-1.5 rounded"><i class="fas fa-user-tie mr-1"></i>${evt.dosen}</span>` : ''}
                    ${evt.kelas ? `<span class="bg-gray-100 px-1.5 rounded"><i class="fas fa-chalkboard mr-1"></i>${evt.kelas}</span>` : ''}
                </div>`;
            }

            container.innerHTML += `
                <div class="bg-white p-4 border-b border-gray-100 flex items-center hover:bg-gray-50 transition group">
                    <div class="w-24 text-center mr-3 shrink-0 border-r border-gray-100 pr-3">
                        <span class="block text-xs font-bold text-gray-800 font-mono">${timeStr}</span>
                        <span class="block text-[10px] text-gray-400 font-medium uppercase tracking-wide">WITA</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-auto px-2 h-5 rounded-md flex items-center justify-center border text-[10px] ${badgeClass} gap-1"><i class="fas ${icon}"></i> <span class="uppercase tracking-wider font-bold">${type}</span></span>
                        </div>
                        <h4 class="font-bold text-gray-800 text-sm truncate">${evt.title}</h4>
                        <p class="text-xs text-gray-500 truncate mt-0.5"><span class="font-semibold text-gray-700">${evt.user_name || 'Lab'}</span> • ${evt.location || 'Lab'}</p>
                        ${extraInfo}
                    </div>
                    ${actions}
                </div>`;
        });
    }

    // --- UTILS ---
    function handleRepeatChange() { const m = document.getElementById('inputRepeatModel').value; const c = document.getElementById('endDateContainer'); const i = document.getElementById('inputEndDateRepeat'); if (m === 'sekali') { c.classList.add('hidden'); i.required = false; } else { c.classList.remove('hidden'); i.required = true; } }
    function openDayModal() { const m = document.getElementById('dayDetailModal'); m.classList.remove('hidden'); setTimeout(() => { document.getElementById('detailBackdrop').classList.remove('opacity-0'); document.getElementById('detailContent').classList.remove('opacity-0', 'scale-95'); document.getElementById('detailContent').classList.add('scale-100'); }, 10); }
    function closeDayModal() { const m = document.getElementById('dayDetailModal'); document.getElementById('detailBackdrop').classList.add('opacity-0'); document.getElementById('detailContent').classList.add('opacity-0', 'scale-95'); document.getElementById('detailContent').classList.remove('scale-100'); setTimeout(() => { m.classList.add('hidden'); }, 300); }
    function closeFormModal() { const m = document.getElementById('formModal'); document.getElementById('formBackdrop').classList.add('opacity-0'); document.getElementById('formContent').classList.add('opacity-0', 'scale-95'); document.getElementById('formContent').classList.remove('scale-100'); setTimeout(() => { m.classList.add('hidden'); }, 300); }
    function updateClock() { const now = new Date(); document.getElementById('liveDate').innerText = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }); document.getElementById('liveTime').innerText = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).replace(/\./g, ':'); } setInterval(updateClock, 1000); updateClock();

    function openFormModal(mode, eventData = null) {
        closeDayModal();
        const m = document.getElementById('formModal'); m.classList.remove('hidden'); 
        setTimeout(() => { document.getElementById('formBackdrop').classList.remove('opacity-0'); document.getElementById('formContent').classList.remove('opacity-0', 'scale-95'); document.getElementById('formContent').classList.add('scale-100'); }, 10);
        document.getElementById('scheduleForm').reset();
        
        if (mode === 'add') {
            document.getElementById('formModalTitle').innerText = "Tambah Jadwal Kuliah";
            document.getElementById('scheduleForm').action = "<?= BASE_URL ?>/user/addSchedule";
            document.getElementById('inputDate').value = selectedDateStr;
            handleRepeatChange();
        } else {
            document.getElementById('formModalTitle').innerText = "Edit Jadwal Kuliah";
            document.getElementById('scheduleForm').action = "<?= BASE_URL ?>/user/editSchedule";
            const props = eventData.extendedProps;
            document.getElementById('inputId').value = props.id;
            document.getElementById('inputTitle').value = props.title;
            document.getElementById('inputKelas').value = props.kelas || '';
            document.getElementById('inputLocation').value = props.location;
            document.getElementById('inputDosen').value = props.dosen || '';
            document.getElementById('inputDate').value = props.rawDate || selectedDateStr;
            document.getElementById('inputStart').value = props.fmtStartTime;
            document.getElementById('inputEnd').value = props.fmtEndTime;
            
            document.getElementById('inputRepeatModel').value = props.repeatModel || 'sekali';
            if (props.repeatModel !== 'sekali') document.getElementById('inputEndDateRepeat').value = props.endDateRepeat;
            handleRepeatChange();
        }
    }

    function showCustomAlert(type, title, message) { const modal = document.getElementById('customAlertModal'); const iconBg = document.getElementById('alertIconBg'); const icon = document.getElementById('alertIcon'); const btn = document.getElementById('alertBtn'); document.getElementById('alertTitle').innerText = title; document.getElementById('alertMessage').innerText = message; if (type === 'success') { iconBg.className = 'w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-green-100 text-green-600'; icon.className = 'fas fa-check text-3xl'; btn.className = 'w-full py-3 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02] bg-green-600 hover:bg-green-700 shadow-green-500/30'; } else { iconBg.className = 'w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-red-100 text-red-600'; icon.className = 'fas fa-times text-3xl'; btn.className = 'w-full py-3 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02] bg-red-600 hover:bg-red-700 shadow-red-500/30'; } modal.classList.remove('hidden'); setTimeout(() => { document.getElementById('alertBackdrop').classList.remove('opacity-0'); document.getElementById('alertContent').classList.remove('scale-90', 'opacity-0'); document.getElementById('alertContent').classList.add('scale-100', 'opacity-100'); }, 50); }
    function closeCustomAlert() { const m = document.getElementById('customAlertModal'); document.getElementById('alertBackdrop').classList.add('opacity-0'); document.getElementById('alertContent').classList.remove('scale-100', 'opacity-100'); document.getElementById('alertContent').classList.add('scale-90', 'opacity-0'); setTimeout(() => m.classList.add('hidden'), 300); }
    
    let deleteUrl = '';
    function triggerDelete(id, type) {
        deleteUrl = `<?= BASE_URL ?>/user/deleteSchedule?id=${id}&type=${type}`;
        const modal = document.getElementById('customConfirmModal');
        const content = document.getElementById('confirmContent');
        const backdrop = document.getElementById('confirmBackdrop');
        modal.classList.remove('hidden'); setTimeout(() => { backdrop.classList.remove('opacity-0'); content.classList.remove('scale-90', 'opacity-0'); content.classList.add('scale-100', 'opacity-100'); }, 50);
    }
    document.getElementById('confirmYesBtn').addEventListener('click', function() { if(deleteUrl) window.location.href = deleteUrl; });
    function closeCustomConfirm() { const modal = document.getElementById('customConfirmModal'); const content = document.getElementById('confirmContent'); const backdrop = document.getElementById('confirmBackdrop'); backdrop.classList.add('opacity-0'); content.classList.remove('scale-100', 'opacity-100'); content.classList.add('scale-90', 'opacity-0'); setTimeout(() => modal.classList.add('hidden'), 300); }
</script>