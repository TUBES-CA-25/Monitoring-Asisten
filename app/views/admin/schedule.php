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
    
    .filter-active {
        background-color: #eff6ff;
        border-color: #bfdbfe;
        color: #1d4ed8;
    }
    .filter-active .icon-box {
        background-color: white;
        color: #2563eb;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .assistant-card { transition: all 0.2s ease; border: 1px solid transparent; }
    
    .assistant-card.filter-active {
        background-color: #eff6ff; 
        border-color: #bfdbfe;
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
                <h1 class="text-3xl font-extrabold tracking-tight">Manajemen Jadwal</h1>
                <p class="text-blue-100 mt-2 text-sm">Kelola shift jaga, piket, dan pantau kuliah asisten.</p>
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

            <button onclick="openFormModal('add')" class="w-full bg-white text-blue-600 px-4 py-3.5 rounded-xl font-bold shadow-sm hover:shadow-md hover:bg-blue-50 transition transform hover:scale-[1.02] flex items-center justify-center gap-2 border border-blue-100">
                <i class="fas fa-plus-circle text-lg"></i> Tambah Jadwal
            </button>

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

        <form id="scheduleForm" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="id_schedule" id="inputId"> 
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Tipe Jadwal</label>
                    <div class="relative">
                        <select name="type" id="inputType" class="w-full pl-3 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-100 transition appearance-none cursor-pointer" onchange="toggleUserSelect()">
                            <option value="umum">Umum (Lab)</option>
                            <option value="asisten">Asisten Lab</option>
                            <option value="piket">Piket</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-3 text-gray-400 text-xs pointer-events-none"></i>
                    </div>
                </div>
                <div id="userSelectContainer" class="hidden">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Pilih Asisten</label>
                    <div class="relative">
                        <select name="user_id" id="inputUser" class="w-full pl-3 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-100 transition appearance-none cursor-pointer">
                            <option value="" disabled selected>-- Pilih --</option>
                            <?php foreach($assistants as $ast): ?>
                                <option value="<?= $ast['id'] ?>"><?= $ast['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-3 text-gray-400 text-xs pointer-events-none"></i>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Nama Kegiatan / Matkul</label>
                <input type="text" name="title" id="inputTitle" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 transition" placeholder="Contoh: Jaga Lab Sesi 1">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Tanggal</label>
                    <input type="date" name="date" id="inputDate" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Lokasi</label>
                    <input type="text" name="location" id="inputLocation" value="Lab Terpadu" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Jam Mulai</label>
                    <input type="time" name="start_time" id="inputStart" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Jam Selesai</label>
                    <input type="time" name="end_time" id="inputEnd" required class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                </div>
            </div>

            <div class="bg-indigo-50 p-4 rounded-xl flex items-center gap-3 border border-indigo-100 cursor-pointer" onclick="document.getElementById('inputRepeat').click()">
                <input type="checkbox" name="is_repeat" id="inputRepeat" class="w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer pointer-events-none">
                <div>
                    <p class="text-sm font-bold text-indigo-800">Ulangi Setiap Minggu</p>
                    <p class="text-[10px] text-indigo-600">Jadwal akan muncul otomatis di hari yang sama.</p>
                </div>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold shadow-lg shadow-blue-500/30 transition transform hover:-translate-y-0.5">Simpan Jadwal</button>
        </form>
    </div>
</div>

<div id="customAlertModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="alertBackdrop"></div>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative z-10 overflow-hidden transform scale-90 opacity-0 transition-all duration-300 flex flex-col items-center p-6 text-center" id="alertContent">
        <div id="alertIconBg" class="w-16 h-16 rounded-full flex items-center justify-center mb-4">
            <i id="alertIcon" class="fas text-3xl"></i>
        </div>
        <h3 id="alertTitle" class="text-xl font-extrabold text-gray-800 mb-2"></h3>
        <p id="alertMessage" class="text-sm text-gray-500 mb-6 px-2"></p>
        <button onclick="closeCustomAlert()" class="w-full py-3 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02]" id="alertBtn">OK</button>
    </div>
</div>

<div id="customConfirmModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="confirmBackdrop"></div>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative z-10 overflow-hidden transform scale-90 opacity-0 transition-all duration-300 flex flex-col items-center p-6 text-center" id="confirmContent">
        <div class="w-16 h-16 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center mb-4">
            <i class="fas fa-exclamation-triangle text-3xl"></i>
        </div>
        <h3 class="text-xl font-extrabold text-gray-800 mb-2">Hapus Jadwal?</h3>
        <p class="text-sm text-gray-500 mb-6 px-2">Jadwal ini akan dihapus permanen dari kalender.</p>
        <div class="flex gap-3 w-full">
            <button onclick="closeCustomConfirm()" class="flex-1 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold hover:bg-gray-50 transition">Batal</button>
            <button id="confirmYesBtn" class="flex-1 py-3 rounded-xl bg-red-600 text-white font-bold shadow-lg hover:bg-red-700 transition">Ya, Hapus</button>
        </div>
    </div>
</div>

<script>
    <?php if(isset($_SESSION['flash'])): ?>
        document.addEventListener("DOMContentLoaded", function() {
            showCustomAlert('<?= $_SESSION['flash']['type'] ?>', '<?= $_SESSION['flash']['title'] ?>', '<?= $_SESSION['flash']['message'] ?>');
        });
    <?php unset($_SESSION['flash']); endif; ?>

    const rawEvents = <?= json_encode($raw_schedules ?? []) ?>;
    
    const events = rawEvents.map(s => {
        let color = '#3b82f6';
        if(s.type === 'piket') color = '#f97316';
        if(s.type === 'umum') color = '#1f2937';
        if(s.type === 'kuliah') color = '#10b981';
        
        return {
            id: s.id,
            title: s.title,
            start: s.tanggal ? s.tanggal + 'T' + s.start_time : null,
            startTime: s.start_time,
            endTime: s.end_time,
            daysOfWeek: s.hari ? [s.hari] : null,
            display: 'none', 
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
            events: events,
            datesSet: function() {
                renderCalendarDots();
            },
            dateClick: function(info) {
                selectedDateStr = info.dateStr;
                renderDayDetails(info.date);
                openDayModal();
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

    function applyFilter(uid) {
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

    function renderDayDetails(dateObj) {
        const container = document.getElementById('modalListContainer');
        const dateTitle = document.getElementById('modalDateTitle');
        const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
        dateTitle.innerText = dateObj.toLocaleDateString('id-ID', options);
        container.innerHTML = '';

        const dateStr = dateObj.toISOString().split('T')[0];
        const dayNum = dateObj.getDay() === 0 ? 7 : dateObj.getDay();

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
            
            let badgeClass = 'bg-blue-50 text-blue-600 border-blue-100';
            let icon = 'fa-user-tie';
            if(props.type === 'piket') { badgeClass = 'bg-orange-50 text-orange-600 border-orange-100'; icon = 'fa-broom'; }
            if(props.type === 'umum') { badgeClass = 'bg-gray-100 text-gray-600 border-gray-200'; icon = 'fa-building'; }
            if(props.type === 'kuliah') { badgeClass = 'bg-green-50 text-green-600 border-green-100'; icon = 'fa-graduation-cap'; }

            let actions = '';
            if (props.type !== 'kuliah') {
                actions = `
                    <div class="flex gap-1 pl-3 border-l border-gray-100 ml-3 shrink-0">
                        <button onclick='openFormModal("edit", ${JSON.stringify(evt)})' class="w-8 h-8 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition flex items-center justify-center"><i class="fas fa-pen text-xs"></i></button>
                        <button onclick="triggerDelete('${evt.id}', '${props.type}')" class="w-8 h-8 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition flex items-center justify-center"><i class="fas fa-trash text-xs"></i></button>
                    </div>`;
            }

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
                    ${actions}
                </div>`;
            container.innerHTML += html;
        });
    }

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

    function toggleUserSelect() {
        const type = document.getElementById('inputType').value;
        const container = document.getElementById('userSelectContainer');
        const inputUser = document.getElementById('inputUser');
        if (type === 'umum') { container.classList.add('hidden'); inputUser.required = false; } 
        else { container.classList.remove('hidden'); inputUser.required = true; }
    }

    function openFormModal(mode, eventData = null) {
        closeDayModal();
        const m = document.getElementById('formModal');
        m.classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('formBackdrop').classList.remove('opacity-0');
            document.getElementById('formContent').classList.remove('opacity-0', 'scale-95');
            document.getElementById('formContent').classList.add('scale-100');
        }, 10);
        
        document.getElementById('scheduleForm').reset();
        
        if (mode === 'add') {
            document.getElementById('formModalTitle').innerText = "Tambah Jadwal";
            document.getElementById('scheduleForm').action = "<?= BASE_URL ?>/admin/addSchedule";
            document.getElementById('inputDate').value = selectedDateStr;
            toggleUserSelect();
        } else {
            document.getElementById('formModalTitle').innerText = "Edit Jadwal";
            document.getElementById('scheduleForm').action = "<?= BASE_URL ?>/admin/editSchedule";
            
            const props = eventData.extendedProps;
            document.getElementById('inputId').value = eventData.id;
            document.getElementById('inputType').value = props.type;
            document.getElementById('inputTitle').value = eventData.title;
            document.getElementById('inputDate').value = props.rawDate || selectedDateStr;
            document.getElementById('inputStart').value = props.startTime || eventData.start.toTimeString().substring(0,5);
            document.getElementById('inputEnd').value = eventData.endTime ? eventData.endTime.toTimeString().substring(0,5) : '17:00';
            document.getElementById('inputLocation').value = props.location;
            
            toggleUserSelect();
            if(props.type !== 'umum') document.getElementById('inputUser').value = props.userId;
            if(props.isRepeat) document.getElementById('inputRepeat').checked = true;
        }
    }

    function closeFormModal() {
        const m = document.getElementById('formModal');
        document.getElementById('formBackdrop').classList.add('opacity-0');
        document.getElementById('formContent').classList.add('opacity-0', 'scale-95');
        document.getElementById('formContent').classList.remove('scale-100');
        setTimeout(() => { m.classList.add('hidden'); }, 300);
    }

    function updateClock() {
        const now = new Date();
        document.getElementById('liveDate').innerText = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        document.getElementById('liveTime').innerText = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).replace(/\./g, ':');
    }
    setInterval(updateClock, 1000); updateClock();

    function showCustomAlert(type, title, message) {
        const modal = document.getElementById('customAlertModal');
        const content = document.getElementById('alertContent');
        const backdrop = document.getElementById('alertBackdrop');
        const iconBg = document.getElementById('alertIconBg');
        const icon = document.getElementById('alertIcon');
        const btn = document.getElementById('alertBtn');

        document.getElementById('alertTitle').innerText = title;
        document.getElementById('alertMessage').innerText = message;

        if (type === 'success') {
            iconBg.className = 'w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-green-100 text-green-600';
            icon.className = 'fas fa-check text-3xl';
            btn.className = 'w-full py-3 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02] bg-green-600 hover:bg-green-700 shadow-green-500/30';
        } else {
            iconBg.className = 'w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-red-100 text-red-600';
            icon.className = 'fas fa-times text-3xl';
            btn.className = 'w-full py-3 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02] bg-red-600 hover:bg-red-700 shadow-red-500/30';
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
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    let deleteUrl = '';
    function triggerDelete(id, type) {
        deleteUrl = `<?= BASE_URL ?>/admin/deleteSchedule?id=${id}&type=${type}`;
        
        const modal = document.getElementById('customConfirmModal');
        const content = document.getElementById('confirmContent');
        const backdrop = document.getElementById('confirmBackdrop');

        modal.classList.remove('hidden');
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            content.classList.remove('scale-90', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    document.getElementById('confirmYesBtn').addEventListener('click', function() {
        if(deleteUrl) window.location.href = deleteUrl;
    });

    function closeCustomConfirm() {
        const modal = document.getElementById('customConfirmModal');
        const content = document.getElementById('confirmContent');
        const backdrop = document.getElementById('confirmBackdrop');
        backdrop.classList.add('opacity-0');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-90', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }
</script>