<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

<style>
    /* ANIMASI & STYLE DASAR */
    .animate-enter { animation: fadeInUp 0.5s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    
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

    /* LAYERS OVERLAY & DOTS ANIMATION */
    .day-click-overlay {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        z-index: 50; background: transparent; cursor: pointer;
    }
    .day-dots-container {
        display: flex; justify-content: center; flex-wrap: wrap; gap: 3px; padding: 0 4px;
        position: absolute; top: 32px; left: 0; right: 0;
        z-index: 40; pointer-events: none !important;
        transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out; /* Transisi Halus */
        opacity: 1;
        transform: translateY(0);
    }
    /* State Tersembunyi untuk Animasi */
    .dots-hidden {
        opacity: 0 !important;
        transform: translateY(5px) !important;
    }

    .dot-category {
        width: 8px; height: 8px; border-radius: 50%;
        box-shadow: 0 1px 1px rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.5);
    }

    /* UTILS */
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .legend-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 8px; }
    .filter-active { background-color: #eff6ff; border-color: #bfdbfe; }
    .assistant-card { transition: all 0.2s ease; border: 1px solid transparent; }
    .assistant-card.filter-active .check-icon { opacity: 1; }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-12">

    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0 text-center md:text-left">
                <h1 class="text-3xl font-extrabold tracking-tight">Manajemen Jadwal</h1>
                <p class="text-slate-300 mt-2 text-sm">Kelola dan monitoring jadwal kegiatan laboratorium.</p>
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
        <div class="w-full lg:w-72 space-y-6 flex flex-col h-full">
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
                <i class="fas fa-plus-circle text-lg"></i> Tambah Jadwal
            </button>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col overflow-hidden flex-1 min-h-0">
                <div class="p-5 border-b border-gray-100 bg-white sticky top-0 z-20">
                    <h3 class="font-extrabold text-gray-700 text-sm uppercase tracking-wide mb-3">Filter Asisten</h3>
                    <input type="text" id="searchFilterInput" placeholder="Cari nama..." class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div class="flex-1 overflow-y-auto p-3 space-y-2 custom-scrollbar" id="filterListContainer">
                    <div onclick="applyFilter('all')" id="filter-all" class="assistant-card filter-item filter-active p-3 rounded-2xl cursor-pointer flex items-center gap-3 border border-transparent hover:bg-gray-50 group">
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0"><i class="fas fa-layer-group text-sm"></i></div>
                        <div><h4 class="font-bold text-gray-800 text-sm">Semua Jadwal</h4><p class="text-[10px] text-gray-500">Gabungan data</p></div>
                        <div class="ml-auto opacity-0 check-icon text-blue-600 transition-opacity"><i class="fas fa-check-circle"></i></div>
                    </div>
                    <?php foreach($assistants as $ast): ?>
                    <div onclick="applyFilter('<?= $ast['id_profil'] ?>')" id="filter-<?= $ast['id_profil'] ?>" class="assistant-card filter-item p-3 rounded-2xl cursor-pointer flex items-center gap-3 border border-transparent hover:bg-gray-50 group" data-name="<?= strtolower($ast['name']) ?>">
                        <div class="relative shrink-0"><img src="<?= !empty($ast['photo_profile']) ? BASE_URL.'/uploads/profile/'.$ast['photo_profile'] : 'https://ui-avatars.com/api/?name='.urlencode($ast['name']) ?>" class="w-10 h-10 rounded-full object-cover border border-gray-100 shadow-sm"></div>
                        <div class="min-w-0 flex-1"><h4 class="font-bold text-gray-800 text-sm truncate"><?= $ast['name'] ?></h4><p class="text-[10px] text-gray-500 truncate">Asisten</p></div>
                        <div class="ml-auto opacity-0 check-icon text-blue-600 transition-opacity"><i class="fas fa-check-circle"></i></div>
                    </div>
                    <?php endforeach; ?>
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
                <i class="fas fa-plus"></i> Tambah di Tanggal Ini
            </button>
        </div>
    </div>
</div>

<div id="formModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="formBackdrop" onclick="closeFormModal()"></div>
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col" id="formContent">
        <div class="bg-white px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-lg text-gray-800" id="formModalTitle">Form Jadwal</h3>
            <button onclick="closeFormModal()" class="text-gray-400 hover:text-red-500"><i class="fas fa-times"></i></button>
        </div>
        <form id="scheduleForm" method="POST" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto custom-scrollbar">
            <input type="hidden" name="id_schedule" id="inputId"> 
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Tipe Jadwal</label>
                    <select name="type" id="inputType" class="w-full pl-3 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-100 transition" onchange="handleTypeChange()">
                        <option value="umum">Umum (Lab)</option>
                        <option value="asisten">Asisten Lab</option>
                        <option value="piket">Piket</option>
                        <option value="class">Kuliah Asisten</option>
                        </select>
                </div>
                <div id="userSelectContainer" class="hidden">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Pilih Asisten</label>
                    <select name="user_id" id="inputUser" class="w-full pl-3 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                        <option value="" disabled selected>-- Pilih --</option>
                        <?php foreach($assistants as $ast): ?>
                            <option value="<?= $ast['id_profil'] ?>"><?= $ast['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Nama Kegiatan / Matkul</label>
                <input type="text" name="title" id="inputTitle" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 transition" placeholder="Contoh: Jaga Lab Sesi 1">
            </div>

            <div id="asistenFields" class="hidden grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Dosen</label>
                    <input type="text" name="dosen" id="inputDosen" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100" placeholder="Nama Dosen">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Kelas</label>
                    <input type="text" name="kelas" id="inputKelas" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100" placeholder="A/B/C">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Lokasi</label><input type="text" name="location" id="inputLocation" value="Lab Terpadu" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100"></div>
                <div><label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Tgl Mulai</label><input type="date" name="date" id="inputDate" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100"></div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Jam Mulai</label><input type="time" name="start_time" id="inputStart" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100"></div>
                <div><label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Jam Selesai</label><input type="time" name="end_time" id="inputEnd" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100"></div>
            </div>

            <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 space-y-3">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Mode Perulangan</label>
                        <select name="model_perulangan" id="inputRepeatModel" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm" onchange="handleRepeatChange()">
                            <option value="sekali">Sekali (1 Hari)</option>
                            <option value="mingguan">Mingguan (Hari Sama)</option>
                            <option value="rentang">Berurutan (Rentang)</option>
                        </select>
                    </div>
                    <div id="endDateContainer" class="hidden">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Sampai Tanggal</label>
                        <input type="date" name="end_date_repeat" id="inputEndDateRepeat" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm">
                    </div>
                </div>
                <p class="text-[10px] text-gray-500 italic" id="repeatHint">Jadwal hanya akan dibuat pada tanggal yang dipilih.</p>
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
        <h3 class="text-xl font-extrabold text-gray-800 mb-2">Hapus Jadwal?</h3><p class="text-sm text-gray-500 mb-6 px-2">Jadwal ini akan dihapus permanen.</p>
        <div class="flex gap-3 w-full"><button onclick="closeCustomConfirm()" class="flex-1 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold hover:bg-gray-50 transition">Batal</button><button id="confirmYesBtn" class="flex-1 py-3 rounded-xl bg-red-600 text-white font-bold shadow-lg hover:bg-red-700 transition">Ya, Hapus</button></div>
    </div>
</div>

<script>
    // --- 1. CONFIG & DATA ---
    const rawEvents = <?= json_encode($raw_schedules ?? []) ?>;
    
    <?php if(isset($_SESSION['flash'])): ?>
        document.addEventListener("DOMContentLoaded", function() { setTimeout(() => { showCustomAlert('<?= $_SESSION['flash']['type'] ?>', '<?= $_SESSION['flash']['title'] ?>', '<?= $_SESSION['flash']['message'] ?>'); }, 300); });
    <?php unset($_SESSION['flash']); endif; ?>

    let calendar;
    let selectedDateStr = new Date().toISOString().split('T')[0];
    let currentFilter = 'all';

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

        const filterInput = document.getElementById('searchFilterInput');
        if (filterInput) {
            filterInput.addEventListener('keyup', function() {
                const key = this.value.toLowerCase();
                const items = document.querySelectorAll('#filterListContainer .assistant-card[data-name]');
                items.forEach(item => {
                    const name = item.getAttribute('data-name');
                    item.style.display = name.includes(key) ? 'flex' : 'none';
                });
            });
        }
    });

    // --- 2. LOGIC TANGGAL ---
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

    // --- 3. FILTER & TRANSISI ---
    function applyFilter(uid) { 
        // 1. Update UI Sidebar
        document.querySelectorAll('.filter-item').forEach(el => el.classList.remove('filter-active')); 
        const activeEl = document.getElementById('filter-' + uid); 
        if(activeEl) activeEl.classList.add('filter-active'); 
        
        // 2. Transisi Soft: Fade Out Dots Lama
        const dotsContainers = document.querySelectorAll('.day-dots-container');
        dotsContainers.forEach(el => el.classList.add('dots-hidden'));

        // 3. Render Ulang setelah sedikit jeda (250ms)
        setTimeout(() => {
            currentFilter = uid;
            renderCustomLayers(); 
        }, 250);
    }

    // --- 4. RENDER LAYERS (PERBAIKAN LOGIC WARNA) ---
    function renderCustomLayers() {
        // Hapus layer lama (kecuali jika ada mekanisme update parsial, tapi di FullCalendar redraw lebih aman)
        document.querySelectorAll('.day-click-overlay, .day-dots-container').forEach(e => e.remove());
        
        document.querySelectorAll('.fc-daygrid-day').forEach(cell => {
            const dateStr = cell.getAttribute('data-date'); if(!dateStr) return;
            const frame = cell.querySelector('.fc-daygrid-day-frame'); if(!frame) return;

            // Layer Klik
            const clickLayer = document.createElement('div');
            clickLayer.className = 'day-click-overlay';
            clickLayer.onclick = function(e) { e.stopPropagation(); selectedDateStr = dateStr; renderDayDetails(dateStr); openDayModal(); };
            frame.appendChild(clickLayer);

            // Layer Dots
            let uniqueColors = new Set();
            
            rawEvents.forEach(evt => {
                const uId = String(evt.id_profil || '');
                const type = (evt.type || 'asisten').toLowerCase();
                const filterId = String(currentFilter);

                // [LOGIKA STRICT FILTER]
                // - Jika filter = 'all': Tampilkan semua
                // - Jika filter = ID User: Tampilkan JIKA (milik user itu) ATAU (tipe umum)
                let isValid = false;
                if (type === 'umum') {
                    isValid = true; // Umum selalu muncul
                } else if (filterId === 'all') {
                    isValid = true; // Mode All: semua muncul
                } else if (uId === filterId) {
                    isValid = true; // Mode User: hanya milik user tsb
                }

                if (!isValid) return; // Skip jika tidak valid

                if (isEventOnDate(evt, dateStr)) {
                    let color = '#3b82f6'; // Default (Asisten)
                    if(type === 'piket') color = '#f97316';
                    if(type === 'umum') color = '#1f2937';
                    if(type === 'class' || type === 'kuliah') color = '#10b981';
                    uniqueColors.add(color);
                }
            });

            if (uniqueColors.size > 0) {
                const dotsLayer = document.createElement('div');
                dotsLayer.className = 'day-dots-container dots-hidden'; // Mulai dengan hidden (opacity 0)
                
                uniqueColors.forEach(color => {
                    const dot = document.createElement('div');
                    dot.className = 'dot-category';
                    dot.style.backgroundColor = color;
                    dotsLayer.appendChild(dot);
                });
                frame.appendChild(dotsLayer);

                // Trigger Fade In (Animation)
                requestAnimationFrame(() => {
                    dotsLayer.classList.remove('dots-hidden');
                });
            }
        });
    }

    // --- 5. RENDER MODAL DETAIL (PERBAIKAN LOGIC) ---
    function renderDayDetails(dateStr) {
        const container = document.getElementById('modalListContainer');
        const dateObjForTitle = new Date(dateStr + "T00:00:00");
        document.getElementById('modalDateTitle').innerText = dateObjForTitle.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        container.innerHTML = '';

        // Filter Event untuk Modal (Sama dengan Logic Dots)
        const visibleEvents = rawEvents.filter(evt => {
            const uId = String(evt.id_profil || '');
            const type = (evt.type || 'asisten').toLowerCase();
            const filterId = String(currentFilter);

            let isValid = false;
            if (type === 'umum') isValid = true;
            else if (filterId === 'all') isValid = true;
            else if (uId === filterId) isValid = true;

            if (!isValid) return false;
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
            else if(type === 'class' || type === 'kuliah') { badgeClass = 'bg-green-50 text-green-600 border-green-100'; icon = 'fa-graduation-cap'; }

            const props = {
                id: evt.id, type: type, title: evt.title, location: evt.location || 'Lab',
                userId: evt.id_profil || '',
                rawDate: evt.start_date, fmtStartTime: (evt.start_time || '00:00').substring(0,5),
                fmtEndTime: (evt.end_time || '00:00').substring(0,5),
                repeatModel: evt.model_perulangan || 'sekali', endDateRepeat: evt.end_date,
                dosen: evt.dosen || '', kelas: evt.kelas || ''
            };
            const jsonStr = JSON.stringify({ extendedProps: props }).replace(/"/g, '&quot;');

            let actions = '';
            // Admin bisa edit semua kecuali kuliah (opsional) atau full access
            actions = `<div class="flex gap-1 pl-3 border-l border-gray-100 ml-3 shrink-0">
                <button onclick="openFormModal('edit', ${jsonStr})" class="w-8 h-8 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition flex items-center justify-center"><i class="fas fa-pen text-xs"></i></button>
                <button onclick="triggerDelete('${evt.id}', '${type}')" class="w-8 h-8 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition flex items-center justify-center"><i class="fas fa-trash text-xs"></i></button>
            </div>`;

            let extraInfo = '';
            if (props.dosen || props.kelas) {
                extraInfo = `<div class="mt-1 flex gap-2 text-[10px] text-gray-500">
                    ${props.dosen ? `<span class="bg-gray-100 px-1.5 rounded"><i class="fas fa-user-tie mr-1"></i>${props.dosen}</span>` : ''}
                    ${props.kelas ? `<span class="bg-gray-100 px-1.5 rounded"><i class="fas fa-chalkboard mr-1"></i>Kelas ${props.kelas}</span>` : ''}
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
                            <span class="w-auto px-2 h-5 rounded-md flex items-center justify-center border text-[10px] ${badgeClass} gap-1">
                                <i class="fas ${icon}"></i> <span class="uppercase tracking-wider font-bold">${type}</span>
                            </span>
                        </div>
                        <h4 class="font-bold text-gray-800 text-sm truncate">${props.title}</h4>
                        <p class="text-xs text-gray-500 truncate mt-0.5"><span class="font-semibold text-gray-700">${evt.user_name || 'Lab'}</span> • ${props.location}</p>
                        ${extraInfo}
                    </div>
                    ${actions}
                </div>`;
        });
    }

    // --- 6. UTILS LAINNYA ---
    function handleTypeChange() {
        const type = document.getElementById('inputType').value;
        const uContainer = document.getElementById('userSelectContainer');
        const uInput = document.getElementById('inputUser');
        const aFields = document.getElementById('asistenFields'); 

        if (type === 'umum') { 
            uContainer.classList.add('hidden'); uInput.required = false; uInput.value = ""; 
            if(aFields) aFields.classList.add('hidden');
        } else if (type === 'piket') {
            uContainer.classList.remove('hidden'); uInput.required = true;
            if(aFields) aFields.classList.add('hidden');
        } else if (type === 'class' || type === 'kuliah') { // Handle Tipe Kuliah
            uContainer.classList.remove('hidden'); uInput.required = true;
            if(aFields) aFields.classList.remove('hidden');
        } else { // Asisten
            uContainer.classList.remove('hidden'); uInput.required = true;
            if(aFields) aFields.classList.remove('hidden');
        }
    }

    function handleRepeatChange() { const m = document.getElementById('inputRepeatModel').value; const c = document.getElementById('endDateContainer'); const i = document.getElementById('inputEndDateRepeat'); const h = document.getElementById('repeatHint'); if (m === 'sekali') { c.classList.add('hidden'); i.required = false; h.innerText = "Jadwal hanya pada tanggal terpilih."; } else { c.classList.remove('hidden'); i.required = true; h.innerText = "Jadwal berulang sampai batas tanggal."; } }
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
            document.getElementById('formModalTitle').innerText = "Tambah Jadwal";
            document.getElementById('scheduleForm').action = "<?= BASE_URL ?>/admin/addSchedule";
            document.getElementById('inputDate').value = selectedDateStr;
            // Jika sedang filter user spesifik, otomatis pilih user tersebut
            if(currentFilter !== 'all') document.getElementById('inputUser').value = currentFilter;
            handleTypeChange(); handleRepeatChange();
        } else {
            document.getElementById('formModalTitle').innerText = "Edit Jadwal";
            document.getElementById('scheduleForm').action = "<?= BASE_URL ?>/admin/editSchedule";
            const props = eventData.extendedProps;
            document.getElementById('inputId').value = props.id;
            document.getElementById('inputType').value = props.type;
            document.getElementById('inputTitle').value = props.title;
            document.getElementById('inputDate').value = props.rawDate || selectedDateStr;
            document.getElementById('inputStart').value = props.fmtStartTime;
            document.getElementById('inputEnd').value = props.fmtEndTime;
            document.getElementById('inputLocation').value = props.location;
            
            // Handle User Select visibility
            handleTypeChange();
            if (props.type !== 'umum') document.getElementById('inputUser').value = props.userId;
            
            if (document.getElementById('inputDosen')) document.getElementById('inputDosen').value = props.dosen || '';
            if (document.getElementById('inputKelas')) document.getElementById('inputKelas').value = props.kelas || '';

            document.getElementById('inputRepeatModel').value = props.repeatModel || 'sekali';
            if (props.repeatModel !== 'sekali') document.getElementById('inputEndDateRepeat').value = props.endDateRepeat;
            handleRepeatChange();
        }
    }

    function showCustomAlert(type, title, message) { const modal = document.getElementById('customAlertModal'); const iconBg = document.getElementById('alertIconBg'); const icon = document.getElementById('alertIcon'); const btn = document.getElementById('alertBtn'); document.getElementById('alertTitle').innerText = title; document.getElementById('alertMessage').innerText = message; if (type === 'success') { iconBg.className = 'w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-green-100 text-green-600'; icon.className = 'fas fa-check text-3xl'; btn.className = 'w-full py-3 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02] bg-green-600 hover:bg-green-700 shadow-green-500/30'; } else { iconBg.className = 'w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-red-100 text-red-600'; icon.className = 'fas fa-times text-3xl'; btn.className = 'w-full py-3 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02] bg-red-600 hover:bg-red-700 shadow-red-500/30'; } modal.classList.remove('hidden'); setTimeout(() => { document.getElementById('alertBackdrop').classList.remove('opacity-0'); document.getElementById('alertContent').classList.remove('scale-90', 'opacity-0'); document.getElementById('alertContent').classList.add('scale-100', 'opacity-100'); }, 50); }
    function closeCustomAlert() { const m = document.getElementById('customAlertModal'); document.getElementById('alertBackdrop').classList.add('opacity-0'); document.getElementById('alertContent').classList.remove('scale-100', 'opacity-100'); document.getElementById('alertContent').classList.add('scale-90', 'opacity-0'); setTimeout(() => m.classList.add('hidden'), 300); }
    
    let deleteUrl = '';
    function triggerDelete(id, type) {
        deleteUrl = `<?= BASE_URL ?>/admin/deleteSchedule?id=${id}&type=${type}`;
        const modal = document.getElementById('customConfirmModal');
        const content = document.getElementById('confirmContent');
        const backdrop = document.getElementById('confirmBackdrop');
        modal.classList.remove('hidden'); setTimeout(() => { backdrop.classList.remove('opacity-0'); content.classList.remove('scale-90', 'opacity-0'); content.classList.add('scale-100', 'opacity-100'); }, 50);
    }
    document.getElementById('confirmYesBtn').addEventListener('click', function() { if(deleteUrl) window.location.href = deleteUrl; });
    function closeCustomConfirm() { const modal = document.getElementById('customConfirmModal'); const content = document.getElementById('confirmContent'); const backdrop = document.getElementById('confirmBackdrop'); backdrop.classList.add('opacity-0'); content.classList.remove('scale-100', 'opacity-100'); content.classList.add('scale-90', 'opacity-0'); setTimeout(() => modal.classList.add('hidden'), 300); }
</script>