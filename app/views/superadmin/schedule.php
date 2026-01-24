<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

<style>
    /* COPY DARI FILE ADMIN ANDA (AGAR TAMPILAN SAMA PERSIS) */
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
    .fc-button { border-radius: 10px !important; font-weight: 600; text-transform: capitalize; padding: 0.6rem 1.2rem; }
    .fc-button-active { background: #eff6ff !important; color: #2563eb !important; border-color: #bfdbfe !important; }

    /* Wajib relative agar layer absolute di dalamnya posisinya benar */
    .fc-daygrid-day-frame {
        position: relative !important;
        min-height: 100%;
        z-index: 1;
    }

    /* LAYER 1: INTERAKSI (PALING ATAS) */
    .day-click-overlay {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        z-index: 50; 
        background: transparent;
        cursor: pointer;
    }

    /* LAYER 2: VISUAL DOTS (DI BAWAH INTERAKSI) */
    .day-dots-container {
        display: flex; justify-content: center; flex-wrap: wrap; gap: 3px; padding: 0 4px;
        position: absolute;
        top: 32px; 
        left: 0; right: 0;
        z-index: 40; 
        pointer-events: none !important; /* Klik tembus */
    }
    
    .dot-category {
        width: 8px; height: 8px; border-radius: 50%;
        box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        border: 1px solid rgba(255,255,255,0.5);
    }

    .fc-event { display: none !important; } 

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
                <h1 class="text-3xl font-extrabold tracking-tight">Monitoring Jadwal</h1>
                <p class="text-slate-300 mt-2 text-sm">Pantau aktivitas laboratorium.</p>
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

            <div class="bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col overflow-hidden flex-1 min-h-0">
                <div class="p-5 border-b border-gray-100 bg-white sticky top-0 z-20">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="font-extrabold text-gray-700 text-sm uppercase tracking-wide">Filter Asisten</h3>
                            <p class="text-[10px] text-gray-400">Klik untuk filter kalender</p>
                        </div>
                        <div class="px-3 py-1 bg-blue-50 text-blue-600 rounded-lg text-xs font-bold border border-blue-100 shadow-sm">
                            <span class="font-normal text-blue-400">Total: </span> <?= count($assistants) ?> 
                        </div>
                    </div>

                    <div class="relative group">
                        <i class="fas fa-search absolute left-4 top-3.5 text-gray-400 group-focus-within:text-blue-500 transition-colors text-sm"></i>
                        <input type="text" id="searchFilterInput" placeholder="Cari nama..." 
                            class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition-all">
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-3 space-y-2 custom-scrollbar" id="filterListContainer">
                    <div onclick="applyFilter('all')" id="filter-all" class="assistant-card filter-item filter-active p-3 rounded-2xl cursor-pointer flex items-center gap-3 border border-transparent hover:bg-gray-50 hover:border-gray-200 transition-all duration-200 group">
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0 shadow-sm group-hover:scale-105 transition-transform"><i class="fas fa-layer-group text-sm"></i></div>
                        <div><h4 class="font-bold text-gray-800 text-sm leading-tight">Semua Jadwal</h4><p class="text-[10px] text-gray-500 font-medium mt-0.5">Gabungan semua data</p></div>
                        <div class="ml-auto opacity-0 check-icon text-blue-600 transition-opacity"><i class="fas fa-check-circle"></i></div>
                    </div>

                    <?php foreach($assistants as $ast): ?>
                    <div onclick="applyFilter('<?= $ast['id_profil'] ?>')" id="filter-<?= $ast['id_profil'] ?>" class="assistant-card filter-item p-3 rounded-2xl cursor-pointer flex items-center gap-3 border border-transparent hover:bg-gray-50 hover:border-gray-200 transition-all duration-200 group" data-name="<?= strtolower($ast['name']) ?>">
                        <div class="relative shrink-0"><img src="<?= !empty($ast['photo_profile']) ? BASE_URL.'/uploads/profile/'.$ast['photo_profile'] : 'https://ui-avatars.com/api/?name='.urlencode($ast['name']).'&background=random' ?>" class="w-10 h-10 rounded-full object-cover border border-gray-100 shadow-sm group-hover:scale-105 transition-transform"></div>
                        <div class="min-w-0 flex-1"><h4 class="font-bold text-gray-800 text-sm leading-tight truncate group-hover:text-blue-600 transition-colors"><?= $ast['name'] ?></h4><p class="text-[10px] text-gray-500 font-medium mt-0.5 truncate"><?= $ast['position'] ?? 'Anggota Lab' ?></p></div>
                        <div class="ml-auto opacity-0 check-icon text-blue-600 transition-opacity"><i class="fas fa-check-circle"></i></div>
                    </div>
                    <?php endforeach; ?>

                    <div id="noResultFilter" class="hidden text-center py-8 text-gray-400 text-xs italic">Tidak ditemukan.</div>
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
        <div class="p-0 overflow-y-auto flex-1 bg-gray-50 custom-scrollbar" id="modalListContainer">
            </div>
        </div>
</div>

<script>
    // --- 1. DATA SETUP ---
    const rawEvents = <?= json_encode($raw_schedules ?? []) ?>;
    
    let calendar;
    let selectedDateStr = new Date().toISOString().split('T')[0];
    let currentFilter = 'all';

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            themeSystem: 'standard',
            headerToolbar: { left: 'title', right: 'prev,today,next' },
            events: [], // Kosongkan, kita render manual pakai layer
            selectable: false, // Matikan seleksi area
            
            // Panggil render custom layer saat load/ganti bulan
            datesSet: function() { 
                renderCustomLayers(); 
            }
        });
        calendar.render();

        const filterInput = document.getElementById('searchFilterInput');
        if (filterInput) {
            filterInput.addEventListener('keyup', function() {
                const key = this.value.toLowerCase();
                const items = document.querySelectorAll('#filterListContainer .assistant-card[data-name]');
                let visibleCount = 0;
                items.forEach(item => {
                    const name = item.getAttribute('data-name');
                    if (name.includes(key)) { item.style.display = 'flex'; visibleCount++; } else { item.style.display = 'none'; }
                });
                document.getElementById('noResultFilter').classList.toggle('hidden', visibleCount > 0);
            });
        }
    });

    // --- 2. LOGIC TANGGAL (SAMA PERSIS DENGAN ADMIN) ---
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

    // --- 3. RENDER LAYER (DOTS & KLIK) ---
    function renderCustomLayers() {
        document.querySelectorAll('.interaction-layer').forEach(e => e.remove());
        document.querySelectorAll('.dots-layer').forEach(e => e.remove());
        
        document.querySelectorAll('.fc-daygrid-day').forEach(cell => {
            const dateStr = cell.getAttribute('data-date'); 
            if(!dateStr) return;
            
            const frame = cell.querySelector('.fc-daygrid-day-frame');
            if(!frame) return;

            // A. LAYER KLIK (Buka Modal Read Only)
            const clickLayer = document.createElement('div');
            clickLayer.className = 'day-click-overlay'; // Menggunakan class overlay di CSS
            clickLayer.title = "Lihat Detail";
            clickLayer.onclick = function(e) {
                e.stopPropagation(); 
                selectedDateStr = dateStr;
                renderDayDetails(dateStr);
                openDayModal();
            };
            frame.appendChild(clickLayer);

            // B. LAYER DOTS
            let uniqueColors = new Set();
            rawEvents.forEach(evt => {
                const uId = evt.id_profil || 0; 
                const type = (evt.type || 'asisten').toLowerCase();
                
                let isVisible = false;
                if (type === 'umum') isVisible = true; 
                else if (currentFilter === 'all') isVisible = true;
                else if (String(uId) === String(currentFilter)) isVisible = true;

                if (!isVisible) return;

                if (isEventOnDate(evt, dateStr)) {
                    let color = '#3b82f6'; 
                    if(type === 'piket') color = '#f97316';
                    if(type === 'umum') color = '#1f2937';
                    if(type === 'kuliah') color = '#10b981';
                    uniqueColors.add(color);
                }
            });

            if (uniqueColors.size > 0) {
                const dotsLayer = document.createElement('div');
                dotsLayer.className = 'day-dots-container'; // Menggunakan class dots di CSS
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

    // --- 4. RENDER MODAL (READ ONLY - TANPA TOMBOL EDIT/HAPUS) ---
    function renderDayDetails(dateStr) {
        const container = document.getElementById('modalListContainer');
        const dateObjForTitle = new Date(dateStr + "T00:00:00");
        document.getElementById('modalDateTitle').innerText = dateObjForTitle.toLocaleDateString('id-ID', { 
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' 
        });
        
        container.innerHTML = '';

        const visibleEvents = rawEvents.filter(evt => {
            const uId = evt.id_profil || 0; 
            const type = (evt.type || 'asisten').toLowerCase();
            
            let isVisible = false;
            if (type === 'umum') isVisible = true; 
            else if (currentFilter === 'all') isVisible = true;
            else if (String(uId) === String(currentFilter)) isVisible = true;
            
            if (!isVisible) return false;
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

            // [SUPER ADMIN: TOMBOL ACTIONS (EDIT/HAPUS) DIHILANGKAN]
            
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
                        <h4 class="font-bold text-gray-800 text-sm truncate">${evt.title}</h4>
                        <p class="text-xs text-gray-500 truncate mt-0.5"><span class="font-semibold text-gray-700">${evt.user_name || 'Lab'}</span> • ${evt.location || 'Lab'}</p>
                    </div>
                </div>`;
        });
    }

    // --- 5. UTILS ---
    function applyFilter(uid) { 
        currentFilter = uid; 
        document.querySelectorAll('.filter-item').forEach(el => el.classList.remove('filter-active')); 
        const activeEl = document.getElementById('filter-' + uid); 
        if(activeEl) activeEl.classList.add('filter-active'); 
        renderCustomLayers(); 
    }

    function openDayModal() { const m = document.getElementById('dayDetailModal'); m.classList.remove('hidden'); setTimeout(() => { document.getElementById('detailBackdrop').classList.remove('opacity-0'); document.getElementById('detailContent').classList.remove('opacity-0', 'scale-95'); document.getElementById('detailContent').classList.add('scale-100'); }, 10); }
    function closeDayModal() { const m = document.getElementById('dayDetailModal'); document.getElementById('detailBackdrop').classList.add('opacity-0'); document.getElementById('detailContent').classList.add('opacity-0', 'scale-95'); document.getElementById('detailContent').classList.remove('scale-100'); setTimeout(() => { m.classList.add('hidden'); }, 300); }
    function updateClock() { const now = new Date(); document.getElementById('liveDate').innerText = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }); document.getElementById('liveTime').innerText = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).replace(/\./g, ':'); } setInterval(updateClock, 1000); updateClock();
</script>