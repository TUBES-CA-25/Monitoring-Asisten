<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

<div class="max-w-7xl mx-auto space-y-6 animate-enter">
    <div class="flex flex-col md:flex-row justify-between items-center bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-800">Manajemen Jadwal</h1>
            <p class="text-gray-500 text-sm mt-1">Atur shift jaga dan kegiatan.</p>
        </div>
        <button onclick="alert('Fitur tambah jadwal akan muncul disini')" class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:bg-indigo-700 transition flex items-center gap-2">
            <i class="fas fa-plus"></i> Tambah
        </button>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 h-[700px]">
        <div class="w-full lg:w-80 bg-white rounded-3xl shadow-sm border border-gray-100 flex flex-col overflow-hidden shrink-0">
            <div class="p-5 border-b border-gray-100 bg-gray-50 font-bold text-gray-700 text-sm uppercase tracking-wide">Filter Asisten</div>
            <div class="flex-1 overflow-y-auto p-3 space-y-2 custom-scrollbar">
                 <div onclick="filterCal('all')" class="p-3 rounded-xl bg-indigo-50 text-indigo-700 font-bold cursor-pointer hover:bg-indigo-100 transition">Semua Jadwal</div>
                 <?php foreach($assistants as $ast): ?>
                 <div onclick="filterCal(<?= $ast['id'] ?>)" class="p-3 rounded-xl hover:bg-gray-50 cursor-pointer flex items-center gap-3 transition" id="usr-<?= $ast['id'] ?>">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($ast['name']) ?>&background=random" class="w-8 h-8 rounded-full border border-gray-200">
                    <span class="text-sm font-bold text-gray-700"><?= $ast['name'] ?></span>
                 </div>
                 <?php endforeach; ?>
            </div>
        </div>
        <div class="flex-1 bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
            <div id='calendar' class="h-full"></div>
        </div>
    </div>
</div>

<script>
    const events = <?= json_encode($raw_schedules ?? []) ?>.map(s => ({
        title: s.title, start: s.start_time, end: s.end_time,
        extendedProps: { 
            userId: s.user_id, 
            userName: s.user_name || 'Umum',
            type: s.user_id ? 'personal' : 'general'
        }
    }));
    
    document.addEventListener('DOMContentLoaded', function() {
        var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
            initialView: 'dayGridMonth', 
            headerToolbar: { left: 'title', right: 'prev,next today' },
            events: events,
            eventContent: function(arg) {
                // CUSTOM EVENT RENDER (AVATAR + TEXT)
                let props = arg.event.extendedProps;
                let bgClass = props.type === 'general' ? 'bg-gray-100 text-gray-600' : 'bg-indigo-50 text-indigo-700 border-l-4 border-indigo-500';
                
                let html = `
                    <div class="flex items-center gap-1 p-1 rounded-md ${bgClass} w-full overflow-hidden shadow-sm text-xs mt-1">
                        ${props.type === 'personal' ? `<div class="w-4 h-4 rounded-full bg-indigo-200 flex items-center justify-center text-[8px] font-bold">${props.userName.substring(0,2)}</div>` : ''}
                        <span class="truncate font-bold">${arg.event.title}</span>
                    </div>
                `;
                return { html: html };
            }
        });
        calendar.render();
        
        window.filterCal = function(uid) {
            calendar.getEvents().forEach(e => e.setProp('display', (uid === 'all' || e.extendedProps.userId == uid) ? 'auto' : 'none'));
        }
    });
</script>