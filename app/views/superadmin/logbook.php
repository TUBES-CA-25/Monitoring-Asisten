<?php
// PHP Data Processing
// Pastikan Controller mengirim data lengkap (termasuk waktu_presensi jika ada di query model)
$calendarEvents = [];
if (!empty($raw_logs)) {
    foreach ($raw_logs as $log) {
        $names = explode(' ', $log['user_name']);
        $shortName = $names[0];
        
        $calendarEvents[] = [
            'title' => $log['activity_detail'],
            'start' => $log['date'],
            'backgroundColor' => '#ffffff', 
            'borderColor' => 'transparent',
            'textColor' => '#1e293b',
            'extendedProps' => [
                'userId' => $log['user_id'],
                'userName' => $log['user_name'],
                'fullActivity' => $log['activity_detail'],
                // Pastikan model mengirim check_in_time/out, jika tidak set default '-'
                'checkIn' => $log['check_in_time'] ?? '-', 
                'checkOut' => $log['check_out_time'] ?? '-',
                'type' => 'logbook'
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

    /* TABLE ROW STYLE (Untuk Modal List) */
    .log-row { display: grid; grid-template-columns: 1fr 100px 3fr 100px; gap: 1rem; align-items: center; border: 1px solid #e5e7eb; padding: 0.75rem; margin-bottom: 0.5rem; border-radius: 0.5rem; font-size: 0.875rem; background: white; transition: all 0.2s; }
    .log-row:hover { border-color: #3b82f6; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .log-header { font-weight: 700; text-transform: uppercase; color: #64748b; font-size: 0.7rem; letter-spacing: 0.05em; background: #f8fafc; border: none; }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter h-full flex flex-col">
    
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 shrink-0">
        <div class="flex flex-col md:flex-row justify-between items-end">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">Monitoring Logbook</h1>
                <p class="text-blue-100 mt-2 text-sm">Kalender aktivitas laboratorium terintegrasi.</p>
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
                <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wide mb-3">Filter Asisten</h3>
                <div class="relative group">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-xs"></i>
                    <input type="text" id="searchAsisten" onkeyup="filterAsistenList()" placeholder="Cari nama asisten..." class="w-full pl-9 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                </div>
            </div>
            
            <div class="max-h-[500px] overflow-y-auto p-3 space-y-1 custom-scrollbar">
                <div onclick="setFilter('all')" class="asisten-item active p-3 rounded-xl cursor-pointer flex items-center gap-3 transition" id="usr-all">
                    <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100"><i class="fas fa-users"></i></div>
                    <div class="font-bold text-gray-700 text-sm">Semua Asisten</div>
                </div>
                <?php if(!empty($assistants)): foreach($assistants as $ast): ?>
                <div onclick="setFilter(<?= $ast['id'] ?>)" class="asisten-item p-3 rounded-xl cursor-pointer flex items-center gap-3 transition filter-item group" id="usr-<?= $ast['id'] ?>" data-name="<?= strtolower($ast['name']) ?>">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($ast['name']) ?>&background=random" class="w-10 h-10 rounded-full border border-gray-200 group-hover:scale-110 transition">
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

<div id="dailyModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="modalBackdrop" onclick="closeModal()"></div>
    
    <div class="bg-white rounded-2xl shadow-2xl w-full relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col max-h-[85vh]" id="modalContent">
        
        <div class="bg-white border-b border-gray-100 p-6 shrink-0 flex justify-between items-center">
            <div>
                <p id="modalSubtitle" class="text-blue-600 text-xs font-extrabold uppercase tracking-widest mb-1">Logbook</p>
                <h3 id="modalDateTitle" class="text-2xl font-extrabold text-gray-800"></h3>
            </div>
            <button onclick="closeModal()" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-red-100 hover:text-red-600 transition"><i class="fas fa-times"></i></button>
        </div>

        <div class="p-6 overflow-y-auto flex-1 bg-gray-50 custom-scrollbar" id="modalListContainer">
            </div>

        <div class="p-4 border-t border-gray-100 bg-white text-right shrink-0">
            <button onclick="closeModal()" class="px-6 py-2.5 bg-gray-900 hover:bg-gray-800 text-white font-bold rounded-xl transition shadow-lg shadow-gray-900/20">Tutup</button>
        </div>
    </div>
</div>

<script>
    const rawLogs = <?= json_encode($raw_logs ?? []) ?>; 
    const calendarEvents = <?= json_encode($calendarEvents) ?>;
    let currentFilterId = 'all'; // State untuk menyimpan filter aktif

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
            eventClick: function(info) { openDynamicModal(info.event.start); },
            dateClick: function(info) { openDynamicModal(info.date); },

            // Render
            eventContent: function(arg) {
                let props = arg.event.extendedProps;
                let shortName = props.userName.split(' ')[0];
                let isFiltered = currentFilterId !== 'all';
                
                // Jika sedang filter user tertentu, tampilkan task saja
                // Jika all user, tampilkan nama: task
                let titleText = isFiltered ? arg.event.title : `<span class="font-bold text-blue-600 mr-1">${shortName}:</span> ${arg.event.title}`;

                return { 
                    html: `
                    <div class="flex items-center gap-2 w-full overflow-hidden">
                        <div class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0"></div>
                        <div class="flex-1 truncate text-xs text-gray-700">
                            ${titleText}
                        </div>
                    </div>` 
                };
            }
        });
        window.calendar.render();
    });

    // --- LOGIKA UTAMA: MEMBUKA MODAL SESUAI KONDISI ---
    function openDynamicModal(dateObj) {
        const dateStr = dateObj.toISOString().split('T')[0];
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const dateFormatted = dateObj.toLocaleDateString('id-ID', options);
        
        document.getElementById('modalDateTitle').innerText = dateFormatted;
        const container = document.getElementById('modalListContainer');
        const modalContent = document.getElementById('modalContent');
        const modalSubtitle = document.getElementById('modalSubtitle');
        
        container.innerHTML = '';

        // 1. FILTER: ALL USER (TAMPILAN TABEL LIST)
        if (currentFilterId === 'all') {
            modalContent.className = "bg-white rounded-2xl shadow-2xl w-full max-w-4xl relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col max-h-[85vh]"; // Lebar
            modalSubtitle.innerText = "DAFTAR AKTIVITAS HARIAN";

            // Filter log hari itu
            const dailyLogs = rawLogs.filter(log => log.date === dateStr);

            if (dailyLogs.length > 0) {
                // Header Tabel
                let html = `
                    <div class="log-row log-header">
                        <div>Nama Asisten</div>
                        <div class="text-center">Jam Datang</div>
                        <div>Deskripsi Kegiatan</div>
                        <div class="text-center">Jam Pulang</div>
                    </div>
                `;

                // Isi Tabel
                dailyLogs.forEach(log => {
                    const checkIn = log.check_in_time ? log.check_in_time.substring(0,5) : '-'; // Placeholder logic
                    const checkOut = log.check_out_time ? log.check_out_time.substring(0,5) : '-';
                    
                    html += `
                        <div class="log-row">
                            <div class="font-bold text-gray-800 truncate pr-2">${log.user_name}</div>
                            <div class="text-center font-mono text-green-600 font-bold bg-green-50 rounded py-1">${checkIn}</div>
                            <div class="text-gray-600 truncate" title="${log.activity_detail}">${log.activity_detail}</div>
                            <div class="text-center font-mono text-red-600 font-bold bg-red-50 rounded py-1">${checkOut}</div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                renderEmptyState(container, "Tidak ada asisten yang mengisi logbook hari ini.");
            }

        } 
        // 2. FILTER: SPECIFIC USER (TAMPILAN DETAIL FORM)
        else {
            modalContent.className = "bg-white rounded-2xl shadow-2xl w-full max-w-lg relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col max-h-[85vh]"; // Sempit
            
            // Cari data user spesifik di hari itu
            const userLog = rawLogs.find(log => log.date === dateStr && log.user_id == currentFilterId);
            
            if (userLog) {
                modalSubtitle.innerText = "DETAIL LOGBOOK - " + userLog.user_name.toUpperCase();
                const checkIn = userLog.check_in_time ? userLog.check_in_time.substring(0,5) : '-';
                const checkOut = userLog.check_out_time ? userLog.check_out_time.substring(0,5) : '-';

                container.innerHTML = `
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Absen Datang</label>
                                <div class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl font-mono text-green-600 font-bold text-center">
                                    ${checkIn}
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Absen Pulang</label>
                                <div class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl font-mono text-red-600 font-bold text-center">
                                    ${checkOut}
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Deskripsi Kegiatan</label>
                            <div class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-700 leading-relaxed min-h-[120px]">
                                ${userLog.activity_detail}
                            </div>
                        </div>
                    </div>
                `;
            } else {
                modalSubtitle.innerText = "DETAIL LOGBOOK";
                renderEmptyState(container, "Asisten ini belum mengisi logbook / absen pada tanggal ini.");
            }
        }

        // Animasi Buka Modal
        const modal = document.getElementById('dailyModal');
        const backdrop = document.getElementById('modalBackdrop');
        const content = document.getElementById('modalContent');
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            content.classList.remove('opacity-0', 'scale-95');
            content.classList.add('scale-100');
        }, 10);
    }

    function renderEmptyState(container, message) {
        container.innerHTML = `
            <div class="flex flex-col items-center justify-center h-64 text-gray-400 text-center p-6">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-clipboard-list text-2xl opacity-40"></i>
                </div>
                <p class="font-medium text-gray-500">Data Kosong</p>
                <p class="text-xs text-gray-400 mt-1">${message}</p>
            </div>
        `;
    }

    function closeModal() {
        const modal = document.getElementById('dailyModal');
        document.getElementById('modalBackdrop').classList.add('opacity-0');
        document.getElementById('modalContent').classList.add('opacity-0', 'scale-95');
        document.getElementById('modalContent').classList.remove('scale-100');
        setTimeout(() => { modal.classList.add('hidden'); }, 300);
    }

    // --- FILTER LOGIC ---
    function setFilter(userId) {
        currentFilterId = userId; // Update State Global

        // Update UI Sidebar
        document.querySelectorAll('.asisten-item').forEach(el => el.classList.remove('active'));
        document.getElementById('usr-' + userId).classList.add('active');
        
        // Update Tampilan Kalender (Filter Event)
        var allEvents = window.calendar.getEvents();
        allEvents.forEach(evt => {
            let shouldShow = (userId === 'all' || evt.extendedProps.userId == userId);
            evt.setProp('display', shouldShow ? 'auto' : 'none');
        });

        // Re-render calendar untuk trigger eventContent formatter ulang (biar nama asisten hilang/muncul)
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

    // Clock
    function updateClock() {
        const now = new Date();
        document.getElementById('liveDate').innerText = now.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        document.getElementById('liveTime').innerText = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }).replace(/\./g, ':');
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>