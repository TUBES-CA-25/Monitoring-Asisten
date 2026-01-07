<?php
// PHP Data Processing
$calendarEvents = [];

// Pastikan variabel data tersedia (support $raw_schedules dari admin atau $all_schedules dari user)
$schedulesData = $raw_schedules ?? $all_schedules ?? [];

if (!empty($schedulesData)) {
    foreach ($schedulesData as $sch) {
        // 1. Tentukan Warna Berdasarkan Tipe Jadwal
        // Default (Umum/Lainnya) = Abu-abu
        $bgColor = '#64748b'; 
        $bdColor = '#475569';
        $typeLabel = 'Umum';

        // Normalisasi input tipe (lowercase)
        $type = strtolower($sch['type'] ?? '');

        if ($type == 'kuliah' || $type == 'class') {
            $bgColor = '#10b981'; // Hijau (Emerald-500)
            $bdColor = '#059669';
            $typeLabel = 'Kuliah';
        } elseif ($type == 'asisten' || $type == 'assistant') {
            $bgColor = '#3b82f6'; // Biru (Blue-500)
            $bdColor = '#2563eb';
            $typeLabel = 'Asisten';
        } elseif ($type == 'piket') {
            $bgColor = '#ef4444'; // Merah (Red-500)
            $bdColor = '#dc2626';
            $typeLabel = 'Piket';
        }

        // 2. Format Tanggal ISO8601
        // Cek apakah start_time sudah datetime lengkap atau terpisah
        $start = $sch['start_time'];
        $end = $sch['end_time'] ?? null;
        
        // Jika data dari model baru (terpisah tanggal & jam), gabungkan:
        if (isset($sch['tanggal']) && strpos($start, ' ') === false) {
            $start = $sch['tanggal'] . 'T' . $sch['start_time'];
            $end   = $sch['tanggal'] . 'T' . ($sch['end_time'] ?? '00:00:00');
        }

        $calendarEvents[] = [
            'title' => $sch['title'] ?? 'Kegiatan',
            'start' => $start, 
            'end' => $end,
            // Warna Event
            'backgroundColor' => '#ffffff', // Background kartu tetap putih bersih
            'borderColor' => 'transparent',
            'textColor' => '#1e293b',
            'extendedProps' => [
                'type' => $typeLabel, // Label untuk ditampilkan
                'colorCode' => $bgColor, // Warna untuk Dot Indikator
                'userId' => $sch['user_id'] ?? 'general',
                'userName' => $sch['user_name'] ?? 'Laboratorium',
                'location' => $sch['location'] ?? '-',
                'desc' => $sch['description'] ?? '-'
            ]
        ];
    }
}
?>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

<style>
    /* --- ANIMASI & TRANSISI --- */
    .animate-enter { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    /* --- GOOGLE CALENDAR STYLE --- */
    #calendar { 
        font-family: 'Inter', sans-serif; 
        --fc-border-color: #f1f5f9;
        --fc-button-text-color: #475569;
        --fc-button-bg-color: #ffffff;
        --fc-button-border-color: #e2e8f0;
        --fc-button-hover-bg-color: #f8fafc;
        --fc-button-active-bg-color: #eff6ff;
        --fc-today-bg-color: transparent;
        --fc-page-bg-color: #ffffff;
    }

    .fc-header-toolbar { margin-bottom: 2rem !important; padding: 0 1rem; }
    .fc-toolbar-title { font-size: 1.5rem !important; font-weight: 800; color: #1e293b; letter-spacing: -0.025em; }
    
    .fc-button {
        border-radius: 9999px !important;
        padding: 0.5rem 1.25rem !important;
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        transition: all 0.2s;
        text-transform: capitalize;
    }
    .fc-button:focus { box-shadow: 0 0 0 2px #bfdbfe !important; }
    
    .fc-theme-standard td, .fc-theme-standard th { border-color: var(--fc-border-color); }
    .fc-col-header-cell-cushion { 
        padding: 1rem 0 !important; color: #94a3b8; font-size: 0.75rem; 
        text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;
    }
    
    .fc-daygrid-day-top { flex-direction: row; padding: 8px 12px !important; }
    .fc-daygrid-day-number { 
        font-size: 0.9rem; font-weight: 600; color: #475569; z-index: 2; 
        width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 50%;
    }
    .fc-day-today .fc-daygrid-day-number { background-color: #3b82f6; color: white; }

    /* Event Pill Style */
    .fc-h-event {
        border: 1px solid #e2e8f0; background-color: white; border-radius: 6px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05); margin-top: 4px;
        transition: transform 0.1s, box-shadow 0.1s; cursor: pointer;
    }
    .fc-h-event:hover { transform: translateY(-1px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border-color: #bfdbfe; z-index: 5; }
    .fc-event-main { padding: 4px 8px; }

    /* Sidebar & Scrollbar */
    .asisten-item.active { background: #eff6ff; border-left: 4px solid #3b82f6; }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

    /* SCHEDULE ROW STYLE */
    .sch-row { display: grid; grid-template-columns: 80px 1fr 120px; gap: 0; border: 1px solid #e2e8f0; margin-bottom: 8px; border-radius: 8px; overflow: hidden; transition: all 0.2s; background: white; }
    .sch-row:hover { border-color: #3b82f6; box-shadow: 0 4px 10px -2px rgba(0,0,0,0.05); transform: translateY(-1px); }
    
    .sch-col { padding: 12px 16px; display: flex; flex-direction: column; justify-content: center; }
    .sch-time { background: #f8fafc; border-right: 1px solid #e2e8f0; text-align: center; font-family: monospace; font-weight: 700; color: #475569; font-size: 0.8rem; }
    .sch-content { border-right: 1px solid #e2e8f0; }
    .sch-room { text-align: center; background: #fff; }

    .sch-header { font-weight: 800; text-transform: uppercase; color: #64748b; font-size: 0.7rem; letter-spacing: 0.05em; background: #f1f5f9; border: none; padding: 10px 0; margin-bottom: 10px; border-radius: 6px; }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter h-full flex flex-col">
    
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 shrink-0">
        <div class="flex flex-col md:flex-row justify-between items-end">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">Jadwal Laboratorium</h1>
                <p class="text-blue-100 mt-2 text-sm">Kalender kegiatan dan jadwal asisten.</p>
            </div>
           <div class="mt-4 md:mt-0 text-right">
                <p class="text-xs font-bold text-blue-200 uppercase tracking-widest mb-1">Waktu Server</p>
                <h2 id="liveDate" class="text-2xl font-bold font-mono"><?= date('d F Y') ?></h2>
                <p class="text-sm opacity-80">
                    <span id="liveTime"><?= date('H:i:s') ?></span> <span>WITA</span>
                </p>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-8 flex-1">
        
        <div class="w-full lg:w-80 bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col overflow-hidden shrink-0 h-auto self-start sticky top-6">
            <div class="p-6 border-b border-gray-100 bg-white">
                <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wide mb-3">Filter Jadwal</h3>
                <div class="relative group">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-xs"></i>
                    <input type="text" id="searchAsisten" onkeyup="filterAsistenList()" placeholder="Cari nama..." class="w-full pl-9 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                </div>
            </div>
            
            <div class="px-6 py-3 bg-gray-50 border-b border-gray-100 flex flex-wrap gap-3 text-[10px] font-bold uppercase tracking-wider">
                <div class="flex items-center gap-1.5 text-gray-600"><span class="w-2 h-2 rounded-full bg-green-500"></span> Kuliah</div>
                <div class="flex items-center gap-1.5 text-blue-600"><span class="w-2 h-2 rounded-full bg-blue-500"></span> Asisten</div>
                <div class="flex items-center gap-1.5 text-red-600"><span class="w-2 h-2 rounded-full bg-red-500"></span> Piket</div>
            </div>

            <div class="max-h-[500px] overflow-y-auto p-3 space-y-1 custom-scrollbar">
                <div onclick="setFilter('all')" class="asisten-item active p-3 rounded-xl cursor-pointer flex items-center gap-3 transition" id="usr-all">
                    <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100"><i class="fas fa-calendar-alt"></i></div>
                    <div class="font-bold text-gray-700 text-sm">Semua Jadwal</div>
                </div>
                <?php if(!empty($assistants)): foreach($assistants as $ast): ?>
                <div onclick="setFilter(<?= $ast['id'] ?>)" class="asisten-item p-3 rounded-xl cursor-pointer flex items-center gap-3 transition filter-item group" id="usr-<?= $ast['id'] ?>" data-name="<?= strtolower($ast['name']) ?>">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($ast['name']) ?>&background=random" class="w-10 h-10 rounded-full border border-gray-200 group-hover:scale-110 transition shrink-0">
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-gray-700 text-sm truncate"><?= $ast['name'] ?></div>
                        <div class="text-xs text-gray-400">Asisten Lab</div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="flex-1 bg-white rounded-3xl shadow-sm border border-gray-200 p-2 relative min-h-[600px]">
            <div id='calendar'></div>
        </div>
    </div>
</div>

<div id="scheduleModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="modalBackdrop" onclick="closeModal()"></div>
    
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col max-h-[85vh]" id="modalContent">
        
        <div class="bg-white border-b border-gray-100 p-6 shrink-0 flex justify-between items-center">
            <div>
                <p id="modalSubtitle" class="text-blue-600 text-xs font-extrabold uppercase tracking-widest mb-1">Detail Jadwal</p>
                <h3 id="modalDateTitle" class="text-2xl font-extrabold text-gray-800"></h3>
            </div>
            <button onclick="closeModal()" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-red-100 hover:text-red-600 transition"><i class="fas fa-times"></i></button>
        </div>

        <div class="p-6 overflow-y-auto flex-1 bg-gray-50 custom-scrollbar">
            <div class="sch-row sch-header text-center">
                <div>JAM</div>
                <div class="text-left pl-4">MATA KULIAH / KEGIATAN</div>
                <div>RUANGAN</div>
            </div>
            <div id="modalListContainer" class="space-y-2"></div>
        </div>

        <div class="p-4 border-t border-gray-100 bg-white text-right shrink-0">
            <button onclick="closeModal()" class="px-6 py-2.5 bg-gray-900 hover:bg-gray-800 text-white font-bold rounded-xl transition shadow-lg shadow-gray-900/20">Tutup</button>
        </div>
    </div>
</div>

<script>
    const rawSchedules = <?= json_encode($schedulesData ?? []) ?>; 
    const calendarEvents = <?= json_encode($calendarEvents) ?>;
    let currentFilterId = 'all';

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');

        window.calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            themeSystem: 'standard',
            height: 'auto',
            locale: 'id', 
            dayHeaderFormat: { weekday: 'long' },
            headerToolbar: { left: 'title', right: 'prev,today,next' },
            selectable: true,
            events: calendarEvents,
            
            // Interaction
            eventClick: function(info) { openScheduleModal(info.event.start); },
            dateClick: function(info) { openScheduleModal(info.date); },

            // CUSTOM EVENT RENDER (Dengan Dot Warna Sesuai DB)
            eventContent: function(arg) {
                let props = arg.event.extendedProps;
                let shortName = props.userName.split(' ')[0];
                let isFiltered = currentFilterId !== 'all';
                
                // Ambil warna dari PHP yang sudah ditentukan
                let dotColorStyle = `background-color: ${props.colorCode};`;
                
                let displayText = isFiltered ? arg.event.title : `<span class="font-bold text-gray-800 mr-1">${shortName}:</span> ${arg.event.title}`;

                return { 
                    html: `
                    <div class="flex items-center gap-2 w-full overflow-hidden">
                        <div class="w-1.5 h-1.5 rounded-full shrink-0" style="${dotColorStyle}"></div>
                        <div class="flex-1 truncate text-xs text-gray-600">
                            ${displayText}
                        </div>
                    </div>` 
                };
            }
        });
        window.calendar.render();
    });

    // --- MODAL LOGIC ---
    function openScheduleModal(dateObj) {
        const dateStr = dateObj.toISOString().split('T')[0];
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('modalDateTitle').innerText = dateObj.toLocaleDateString('id-ID', options);
        
        const container = document.getElementById('modalListContainer');
        container.innerHTML = '';

        // Filter jadwal
        const dailySch = rawSchedules.filter(sch => {
            let startT = sch.start_time || sch.tanggal + 'T' + sch.start_time; // Handle kedua format
            let dateMatch = startT.startsWith(dateStr);
            let userMatch = (currentFilterId === 'all') || (sch.user_id == currentFilterId);
            return dateMatch && userMatch;
        });

        dailySch.sort((a, b) => (a.start_time || '').localeCompare(b.start_time || ''));

        if (dailySch.length > 0) {
            dailySch.forEach(sch => {
                // Parsing Data Jam
                let rawStart = sch.start_time; 
                if(rawStart.includes('T')) rawStart = rawStart.split('T')[1]; // Ambil jam saja
                let timeStart = rawStart.substring(0,5);

                let rawEnd = sch.end_time || '';
                if(rawEnd.includes('T')) rawEnd = rawEnd.split('T')[1];
                let timeEnd = rawEnd ? rawEnd.substring(0,5) : 'Selesai';
                
                let room = sch.location || sch.ruangan || 'Lab';
                
                // Label User / Tipe
                let type = (sch.type || '').toLowerCase();
                let badgeClass = 'bg-gray-100 text-gray-600'; // Default
                if(type.includes('kuliah') || type == 'class') badgeClass = 'bg-green-100 text-green-700';
                else if(type.includes('asisten') || type == 'assistant') badgeClass = 'bg-blue-100 text-blue-700';
                else if(type == 'piket') badgeClass = 'bg-red-100 text-red-700';

                let badge = `<span class="text-[10px] ${badgeClass} px-2 py-0.5 rounded font-bold ml-2 uppercase">${type || 'Umum'}</span>`;

                container.innerHTML += `
                    <div class="sch-row">
                        <div class="sch-col sch-time">
                            <div>${timeStart}</div>
                            <div class="text-[10px] font-normal opacity-70">s/d</div>
                            <div>${timeEnd}</div>
                        </div>
                        <div class="sch-col sch-content">
                            <div class="flex items-center mb-1">
                                <h4 class="font-bold text-gray-800 text-sm truncate">${sch.title}</h4>
                                ${badge}
                            </div>
                            <p class="text-xs text-gray-500 truncate"><span class="font-bold">${sch.user_name}</span> - ${sch.description || '-'}</p>
                        </div>
                        <div class="sch-col sch-room">
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 text-[10px] font-bold rounded border border-gray-200 uppercase tracking-wide truncate max-w-full">
                                ${room}
                            </span>
                        </div>
                    </div>
                `;
            });
        } else {
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center h-64 text-gray-400 text-center p-6">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-calendar-times text-2xl opacity-40"></i>
                    </div>
                    <p class="font-medium text-gray-500">Jadwal Kosong</p>
                    <p class="text-xs text-gray-400 mt-1">Tidak ada kegiatan terjadwal pada tanggal ini.</p>
                </div>
            `;
        }

        const modal = document.getElementById('scheduleModal');
        modal.classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('modalBackdrop').classList.remove('opacity-0');
            document.getElementById('modalContent').classList.remove('opacity-0', 'scale-95');
            document.getElementById('modalContent').classList.add('scale-100');
        }, 10);
    }

    function closeModal() {
        const modal = document.getElementById('scheduleModal');
        document.getElementById('modalBackdrop').classList.add('opacity-0');
        document.getElementById('modalContent').classList.add('opacity-0', 'scale-95');
        document.getElementById('modalContent').classList.remove('scale-100');
        setTimeout(() => { modal.classList.add('hidden'); }, 300);
    }

    function setFilter(userId) {
        currentFilterId = userId;
        document.querySelectorAll('.asisten-item').forEach(el => el.classList.remove('active'));
        document.getElementById('usr-' + userId).classList.add('active');
        
        var allEvents = window.calendar.getEvents();
        allEvents.forEach(evt => {
            let props = evt.extendedProps;
            let shouldShow = (userId === 'all') || (props.userId == userId);
            evt.setProp('display', shouldShow ? 'auto' : 'none');
        });
        window.calendar.render();
    }

    function filterAsistenList() {
        var input = document.getElementById('searchAsisten').value.toLowerCase();
        var items = document.getElementsByClassName('filter-item');
        for (var i = 0; i < items.length; i++) {
            var name = items[i].getAttribute('data-name');
            items[i].style.display = name.includes(input) ? "flex" : "none";
        }
    }

    function updateClock() {
        const now = new Date();
        document.getElementById('liveDate').innerText = now.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        document.getElementById('liveTime').innerText = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }).replace(/\./g, ':');
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>