<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

<style>
    .animate-enter { animation: fadeInUp 0.5s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    
    #calendar { 
        font-family: 'Inter', sans-serif; 
        --fc-border-color: #f1f5f9; 
        --fc-button-text-color: #475569; 
        --fc-button-bg-color: white; 
        --fc-button-border-color: #e2e8f0;
        --fc-today-bg-color: #e8f4ffff;
        min-height: 700px;
    }
    
    .fc-header-toolbar { margin-bottom: 1.5rem !important; }
    .fc-button { 
        border-radius: 12px !important; 
        font-weight: 600 !important; 
        text-transform: capitalize; 
        box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important; 
        padding: 0.5rem 1rem !important;
    }
    .fc-button-active { 
        background: #eff6ff !important; 
        color: #2563eb !important; 
        border-color: #bfdbfe !important; 
    }
    .fc-daygrid-day { transition: background 0.2s; cursor: pointer; }
    .fc-daygrid-day:hover { background-color: #f0f9ff; }
    
    .fc-event { display: none; } 

    .day-dots-container {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 4px;
        margin-top: 4px;
        padding: 0 4px;
    }
    
    .dot-category {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    
    .legend-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 8px; }
    
    .assistant-card { transition: all 0.2s ease; border: 1px solid transparent; }
    
    /* State Active (Saat dipilih) */
    .assistant-card.filter-active {
        background-color: #eff6ff; /* blue-50 */
        border-color: #bfdbfe;     /* blue-200 */
    }
    
    .assistant-card.filter-active .check-icon {
        opacity: 1;
    }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-12">

    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0 text-center md:text-left">
                <h1 class="text-3xl font-extrabold tracking-tight">Monitoring Jadwal</h1>
                <p class="text-blue-100 mt-2 text-sm">Pantau aktivitas, shift jaga, dan penggunaan lab.</p>
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

    <div class="flex flex-col lg:flex-row gap-6 h-[850px]">
        
        <div class="w-full lg:w-72 space-y-6 flex flex-col h-full">
            
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200">
                <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wide mb-4">Kategori</h3>
                <div class="space-y-3 text-sm font-medium text-gray-600">
                    <div class="flex items-center"><span class="legend-dot bg-gray-800"></span> Umum (Lab)</div>
                    <div class="flex items-center"><span class="legend-dot bg-blue-500"></span> Asisten Lab</div>
                    <div class="flex items-center"><span class="legend-dot bg-orange-500"></span> Piket</div>
                    <div class="flex items-center"><span class="legend-dot bg-green-500"></span> Kuliah Asisten</div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col overflow-hidden flex-1 h-full">
                <div class="p-5 border-b border-gray-100 bg-white sticky top-0 z-20">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="font-extrabold text-gray-700 text-sm uppercase tracking-wide">Filter Asisten</h3>
                            <p class="text-[10px] text-gray-400">Pilih untuk filter kalender</p>
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
                    
                    <div onclick="applyFilter('all')" id="filter-all" 
                        class="assistant-card filter-item filter-active p-3 rounded-2xl cursor-pointer flex items-center gap-3 border border-transparent hover:bg-gray-50 hover:border-gray-200 transition-all duration-200 group">
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                            <i class="fas fa-layer-group text-sm"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800 text-sm leading-tight">Semua Jadwal</h4>
                            <p class="text-[10px] text-gray-500 font-medium mt-0.5">Gabungan semua data</p>
                        </div>
                        <div class="ml-auto opacity-0 check-icon text-blue-600 transition-opacity"><i class="fas fa-check-circle"></i></div>
                    </div>

                    <?php foreach($assistants as $ast): ?>
                    <div onclick="applyFilter(<?= $ast['id'] ?>)" id="filter-<?= $ast['id'] ?>" 
                        class="assistant-card filter-item p-3 rounded-2xl cursor-pointer flex items-center gap-3 border border-transparent hover:bg-gray-50 hover:border-gray-200 transition-all duration-200 group"
                        data-name="<?= strtolower($ast['name']) ?>">
                        
                        <div class="relative shrink-0">
                            <img src="<?= !empty($ast['photo_profile']) ? BASE_URL.'/uploads/profile/'.$ast['photo_profile'] : 'https://ui-avatars.com/api/?name='.urlencode($ast['name']).'&background=random' ?>" 
                                class="w-10 h-10 rounded-full object-cover border border-gray-100 shadow-sm group-hover:scale-105 transition-transform">
                        </div>
                        
                        <div class="min-w-0 flex-1">
                            <h4 class="font-bold text-gray-800 text-sm leading-tight truncate group-hover:text-blue-600 transition-colors"><?= $ast['name'] ?></h4>
                            <p class="text-[10px] text-gray-500 font-medium mt-0.5 truncate"><?= $ast['position'] ?? 'Anggota Lab' ?></p>
                        </div>

                        <div class="ml-auto opacity-0 check-icon text-blue-600 transition-opacity"><i class="fas fa-check-circle"></i></div>
                    </div>
                    <?php endforeach; ?>

                    <div id="noResultFilter" class="hidden text-center py-8 text-gray-400 text-xs italic">
                        Tidak ditemukan.
                    </div>

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
            <div>
                <p class="text-blue-600 text-[10px] font-extrabold uppercase tracking-widest mb-1">Detail Kegiatan</p>
                <h3 id="modalDateTitle" class="text-xl font-extrabold text-gray-800"></h3>
            </div>
            <button onclick="closeDayModal()" class="w-8 h-8 rounded-full bg-gray-50 hover:bg-red-50 text-gray-400 hover:text-red-500 transition flex items-center justify-center"><i class="fas fa-times"></i></button>
        </div>

        <div class="p-0 overflow-y-auto flex-1 bg-gray-50 custom-scrollbar" id="modalListContainer">
            </div>
        </div>
</div>

<script>
    // 1. DATA PROCESSING
    const rawEvents = <?= json_encode($raw_schedules ?? []) ?>;
    
    // Transform Data untuk FullCalendar
    const events = rawEvents.map(s => {
        let color = '#3b82f6'; // Asisten (Blue)
        if(s.type === 'piket') color = '#f97316'; // Piket (Orange)
        if(s.type === 'umum') color = '#1f2937';  // Umum (Dark)
        if(s.type === 'kuliah') color = '#10b981'; // Kuliah (Green)
        
        return {
            id: s.id,
            title: s.title,
            start: s.tanggal ? s.tanggal + 'T' + s.start_time : null,
            startTime: s.start_time,
            endTime: s.end_time,
            daysOfWeek: s.hari ? [s.hari] : null,
            
            // Hide event asli, render manual via renderCalendarDots()
            display: 'none', 
            
            // Simpan info
            backgroundColor: color, 
            extendedProps: { 
                type: s.type, 
                location: s.location, 
                userId: s.user_id, 
                userName: s.user_name,
                isRepeat: s.hari ? true : false,
                rawDate: s.tanggal,
                dotColor: color
            }
        };
    });

    let calendar;
    let selectedDateStr = new Date().toISOString().split('T')[0];
    let currentFilter = 'all';

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            themeSystem: 'standard',
            headerToolbar: { left: 'title', right: 'prev,today,next' },
            events: events, // Load semua data
            
            // Render Dots
            datesSet: function() {
                renderCalendarDots();
            },

            // Klik Tanggal -> Buka Modal Detail
            dateClick: function(info) {
                selectedDateStr = info.dateStr;
                renderDayDetails(info.date);
                openDayModal();
            }
        });
        calendar.render();

        // Fitur Search Filter
        const filterInput = document.getElementById('searchFilterInput');
        if (filterInput) {
            filterInput.addEventListener('keyup', function() {
                const key = this.value.toLowerCase();
                const items = document.querySelectorAll('#filterListContainer .assistant-card[data-name]');
                let visibleCount = 0;

                items.forEach(item => {
                    const name = item.getAttribute('data-name');
                    if (name.includes(key)) {
                        item.style.display = 'flex';
                        visibleCount++;
                    } else {
                        item.style.display = 'none';
                    }
                });

                const noResult = document.getElementById('noResultFilter');
                if (noResult) {
                    if (visibleCount === 0) noResult.classList.remove('hidden');
                    else noResult.classList.add('hidden');
                }
            });
        }
    });

    // --- RENDER VISUALISASI DOTS DI KALENDER ---
    function renderCalendarDots() {
        document.querySelectorAll('.day-dots-container').forEach(e => e.remove());
        const allEvents = calendar.getEvents();
        
        document.querySelectorAll('.fc-daygrid-day').forEach(cell => {
            const dateStr = cell.getAttribute('data-date');
            if(!dateStr) return;
            
            const cellDate = new Date(dateStr);
            const dayNum = cellDate.getDay() === 0 ? 7 : cellDate.getDay(); 
            
            let uniqueColors = new Set();

            allEvents.forEach(evt => {
                const props = evt.extendedProps;
                
                const isMatchFilter = (currentFilter === 'all') || 
                                      (props.userId == currentFilter) || 
                                      (props.type === 'umum');
                
                if(!isMatchFilter) return;

                let isMatchDate = false;
                if (props.isRepeat) {
                    if (evt._def.recurringDef.typeData.daysOfWeek.includes(dayNum)) {
                        isMatchDate = true;
                    }
                } else {
                    if (evt.startStr.split('T')[0] === dateStr) {
                        isMatchDate = true;
                    }
                }

                if (isMatchDate) {
                    uniqueColors.add(props.dotColor);
                }
            });

            if (uniqueColors.size > 0) {
                const frame = cell.querySelector('.fc-daygrid-day-frame');
                const container = document.createElement('div');
                container.className = 'day-dots-container';
                
                uniqueColors.forEach(color => {
                    const dot = document.createElement('div');
                    dot.className = 'dot-category';
                    dot.style.backgroundColor = color;
                    container.appendChild(dot);
                });
                
                frame.appendChild(container);
            }
        });
    }

    // --- FILTER LOGIC ---
    window.applyFilter = function(uid) {
        currentFilter = uid;
        
        document.querySelectorAll('.filter-item').forEach(el => {
            el.classList.remove('filter-active');
        });
        
        const activeEl = document.getElementById('filter-' + uid);
        if(activeEl) {
            activeEl.classList.add('filter-active');
        }

        renderCalendarDots();
    }

    // --- RENDER DETAIL MODAL (READ ONLY) ---
    function renderDayDetails(dateObj) {
        const container = document.getElementById('modalListContainer');
        const dateTitle = document.getElementById('modalDateTitle');
        const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
        dateTitle.innerText = dateObj.toLocaleDateString('id-ID', options);
        container.innerHTML = '';

        const dateStr = dateObj.toISOString().split('T')[0];
        const dayNum = dateObj.getDay() === 0 ? 7 : dateObj.getDay();

        // Cari event yang cocok
        const visibleEvents = calendar.getEvents().filter(evt => {
            const props = evt.extendedProps;
            const isMatchFilter = (currentFilter === 'all') || (props.userId == currentFilter) || (props.type === 'umum');
            if(!isMatchFilter) return false;

            if (props.isRepeat) {
                return evt._def.recurringDef.typeData.daysOfWeek.includes(dayNum);
            } else {
                return evt.startStr.split('T')[0] === dateStr;
            }
        });

        visibleEvents.sort((a, b) => (a.extendedProps.startTime || '00:00').localeCompare(b.extendedProps.startTime || '00:00'));

        if (visibleEvents.length === 0) {
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3 text-2xl opacity-50">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <p class="text-sm italic">Tidak ada jadwal.</p>
                </div>`;
            return;
        }

        visibleEvents.forEach(evt => {
            const props = evt.extendedProps;
            const timeStr = props.startTime ? props.startTime.substring(0,5) : '-';
            
            // Badge Styles
            let badgeClass = 'bg-blue-50 text-blue-600 border-blue-100';
            let icon = 'fa-user-tie';
            if(props.type === 'piket') { badgeClass = 'bg-orange-50 text-orange-600 border-orange-100'; icon = 'fa-broom'; }
            if(props.type === 'umum') { badgeClass = 'bg-gray-100 text-gray-600 border-gray-200'; icon = 'fa-building'; }
            if(props.type === 'kuliah') { badgeClass = 'bg-green-50 text-green-600 border-green-100'; icon = 'fa-graduation-cap'; }

            // [SUPER ADMIN] Tidak ada tombol Edit/Hapus
            let html = `
                <div class="bg-white p-4 border-b border-gray-100 flex items-center hover:bg-gray-50 transition group">
                    <div class="w-14 text-center mr-4 shrink-0">
                        <span class="block text-sm font-bold text-gray-800">${timeStr}</span>
                        <span class="block text-[10px] text-gray-400 font-medium uppercase tracking-wide">WITA</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-6 h-6 rounded-md flex items-center justify-center border text-[10px] ${badgeClass}"><i class="fas ${icon}"></i></span>
                            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">${props.type}</span>
                        </div>
                        <h4 class="font-bold text-gray-800 text-sm truncate">${evt.title}</h4>
                        <p class="text-xs text-gray-500 truncate mt-0.5">
                            <span class="font-semibold text-gray-700">${props.userName}</span> • ${props.location || 'Lab'}
                        </p>
                    </div>
                </div>`;
            container.innerHTML += html;
        });
    }

    // --- MODAL UTILS ---
    function openDayModal() {
        const m = document.getElementById('dayDetailModal');
        m.classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('detailBackdrop').classList.remove('opacity-0');
            document.getElementById('detailContent').classList.remove('opacity-0', 'scale-95');
            document.getElementById('detailContent').classList.add('scale-100');
        }, 10);
    }
    function closeDayModal() {
        const m = document.getElementById('dayDetailModal');
        document.getElementById('detailBackdrop').classList.add('opacity-0');
        document.getElementById('detailContent').classList.add('opacity-0', 'scale-95');
        document.getElementById('detailContent').classList.remove('scale-100');
        setTimeout(() => { m.classList.add('hidden'); }, 300);
    }

    // --- JAM ---
    function updateClock() {
        const now = new Date();
        document.getElementById('liveDate').innerText = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        document.getElementById('liveTime').innerText = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).replace(/\./g, ':');
    }
    setInterval(updateClock, 1000); updateClock();
</script>