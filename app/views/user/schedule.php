<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

<style>
    .animate-enter { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    
    /* Kalender Custom */
    #calendar { min-height: 700px; font-family: 'Inter', sans-serif; }
    .fc-header-toolbar { margin-bottom: 1.5rem !important; }
    .fc-button-primary { background: white !important; color: #374151 !important; border: 1px solid #e5e7eb !important; font-weight: bold; }
    .fc-button-active { background: #eff6ff !important; color: #2563eb !important; border-color: #bfdbfe !important; }
    
    /* Legend Dots */
    .dot-legend { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter h-full flex flex-col">
    
    <div class="flex flex-col md:flex-row justify-between items-end bg-gradient-to-r from-blue-600 to-indigo-600 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight">Jadwal & Kegiatan</h1>
            <p class="text-blue-100 mt-2 text-sm">Kelola jadwal kuliah dan pantau jadwal piket.</p>
        </div>
        <div class="mt-4 md:mt-0 text-right">
            <p class="text-xs font-bold text-blue-200 uppercase tracking-widest mb-1">Waktu Sistem</p>
            <h2 id="liveDate" class="text-2xl font-bold font-mono"><?= date('d F Y') ?></h2>
            <p class="text-sm opacity-80">
                <span id="liveTime"><?= date('H:i:s') ?></span> <span>WITA</span>
            </p>
        </div>
        
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        
        <div class="w-full lg:w-72 space-y-6">
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200">
                <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wide mb-4">Kategori Jadwal</h3>
                <div class="space-y-3 text-sm font-medium text-gray-600">
                    <div class="flex items-center"><span class="dot-legend bg-green-500"></span> Jadwal Kuliah</div>
                    <div class="flex items-center"><span class="dot-legend bg-blue-500"></span> Jadwal Asisten</div>
                    <div class="flex items-center"><span class="dot-legend bg-orange-400"></span> Piket / Umum</div>
                </div>
                <div class="mt-6 pt-4 border-t border-gray-100 text-xs text-gray-400">
                    *Hanya "Jadwal Kuliah" yang dapat Anda ubah.
                </div>
            </div>
            
            <button onclick="openManageModal()" class="mt-4 md:mt-0 bg-white text-blue-600 px-7 py-3 rounded-xl font-bold shadow-lg hover:bg-blue-50 transition transform hover:scale-105 flex items-center gap-2">
                <i class="fas fa-edit"></i> Manajemen Jadwal Kuliah
            </button>

            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200">
                <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wide mb-3">Cari Jadwal</h3>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-xs"></i>
                    <input type="text" id="searchInput" placeholder="Mata kuliah, ruangan..." onkeyup="searchCalendar()" class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                </div>
            </div>
        </div>

        <div class="flex-1 bg-white rounded-3xl shadow-sm border border-gray-200 p-6 relative">
            <div id='calendar'></div>
        </div>
    </div>
</div>

<div id="manageModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="manageBackdrop" onclick="closeManageModal()"></div>
    
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col h-[85vh]" id="manageContent">
        
        <div class="bg-white border-b border-gray-100 p-6 flex justify-between items-center shrink-0">
            <div>
                <h3 class="text-xl font-extrabold text-gray-800">Manajemen Jadwal Kuliah</h3>
                <p class="text-gray-500 text-xs">Tambah atau hapus jadwal kuliah pribadi Anda.</p>
            </div>
            <button onclick="closeManageModal()" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-red-100 hover:text-red-600 transition flex items-center justify-center"><i class="fas fa-times"></i></button>
        </div>

        <div class="flex flex-col md:flex-row flex-1 overflow-hidden">
            
            <div class="w-full md:w-2/3 bg-gray-50 p-6 overflow-y-auto border-r border-gray-200 custom-scrollbar">
                
                <div class="flex justify-between items-center mb-4">
                    <h4 class="font-bold text-gray-700 text-sm">Daftar Mata Kuliah</h4>
                    <input type="text" id="searchList" onkeyup="filterListTable()" placeholder="Cari..." class="px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-gray-100 text-[10px] font-bold text-gray-500 uppercase">
                            <tr>
                                <th class="p-3">Hari & Jam</th>
                                <th class="p-3">Mata Kuliah / Kegiatan</th>
                                <th class="p-3">Ruangan</th>
                                <th class="p-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100" id="scheduleTableBody">
                            <?php if(!empty($my_classes)): foreach($my_classes as $cls): ?>
                            <tr class="hover:bg-blue-50 transition filter-row">
                                <td class="p-3">
                                    <div class="font-bold text-gray-700"><?= date('l', strtotime($cls['start_time'])) ?></div> <div class="text-xs text-gray-400 font-mono"><?= date('H:i', strtotime($cls['start_time'])) ?></div>
                                </td>
                                <td class="p-3">
                                    <div class="font-bold text-blue-600 list-title"><?= $cls['title'] ?></div>
                                    <div class="text-xs text-gray-500"><?= $cls['description'] ?></div>
                                </td>
                                <td class="p-3">
                                    <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-bold"><?= $cls['location'] ?></span>
                                </td>
                                <td class="p-3 text-center">
                                    <button onclick="deleteSchedule(<?= $cls['id'] ?>)" class="text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded transition" title="Hapus"><i class="fas fa-trash-alt"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="4" class="p-6 text-center text-gray-400 italic">Belum ada jadwal kuliah.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex justify-center gap-2">
                    </div>
            </div>

            <div class="w-full md:w-1/3 p-6 bg-white overflow-y-auto">
                <h4 class="font-bold text-gray-800 text-sm mb-4 border-b pb-2">Tambah Jadwal Baru</h4>
                <form id="addScheduleForm" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Mata Kuliah</label>
                        <input type="text" name="course_name" required class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Dosen / Info</label>
                        <input type="text" name="lecturer" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Ruangan</label>
                        <input type="text" name="room" required class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal</label>
                        <input type="date" name="date" required class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Jam Mulai</label>
                            <input type="time" name="start_clock" required class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Jam Selesai</label>
                            <input type="time" name="end_clock" required class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white transition">
                        </div>
                    </div>

                    <button type="submit" id="btnSave" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-green-500/30 transition transform hover:scale-105 mt-4">
                        <i class="fas fa-plus-circle mr-2"></i> TAMBAH JADWAL
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // --- 1. DATA PROCESSING ---
    const rawSchedules = <?= json_encode($all_schedules ?? []) ?>;
    const events = rawSchedules.map(s => {
        // Tentukan Warna & Tipe
        let bgColor, borderColor, typeName;
        
        if (s.type === 'class') {
            // Jadwal Kuliah (Personal)
            bgColor = '#10b981'; // Emerald-500
            borderColor = '#059669';
            typeName = 'KULIAH';
        } else if (s.user_id) {
            // Jadwal Asisten (User ID match but not class)
            bgColor = '#3b82f6'; // Blue-500
            borderColor = '#2563eb';
            typeName = 'JAGA';
        } else {
            // Jadwal Umum / Piket
            bgColor = '#f97316'; // Orange-500
            borderColor = '#ea580c';
            typeName = 'PIKET';
        }

        return {
            id: s.id,
            title: s.title,
            start: s.start_time,
            end: s.end_time,
            backgroundColor: bgColor,
            borderColor: borderColor,
            extendedProps: {
                location: s.location,
                desc: s.description,
                type: typeName
            }
        };
    });

    // --- 2. FULLCALENDAR INIT ---
    let calendar;
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
            events: events,
            height: 'auto',
            
            // Render Konten Event
            eventContent: function(arg) {
                let timeText = arg.timeText;
                let props = arg.event.extendedProps;
                let loc = props.location ? `(${props.location})` : '';
                return { 
                    html: `
                    <div class="overflow-hidden">
                        <div class="text-[9px] font-bold opacity-90">${timeText} - ${props.type}</div>
                        <div class="font-bold truncate">${arg.event.title}</div>
                        <div class="text-[9px] truncate">${loc}</div>
                    </div>` 
                };
            },

            // Event Click (View Detail)
            eventClick: function(info) {
                const props = info.event.extendedProps;
                alert(`Detail Jadwal:\n\nKegiatan: ${info.event.title}\nTipe: ${props.type}\nLokasi: ${props.location}\nInfo: ${props.desc}\n\n*Gunakan menu 'Manajemen Jadwal' untuk mengedit jadwal kuliah.`);
            }
        });
        calendar.render();
    });

    // --- 3. FILTER / SEARCH KALENDER ---
    function searchCalendar() {
        const keyword = document.getElementById('searchInput').value.toLowerCase();
        const allEvents = calendar.getEvents();
        
        allEvents.forEach(evt => {
            const title = evt.title.toLowerCase();
            const loc = evt.extendedProps.location ? evt.extendedProps.location.toLowerCase() : '';
            const shouldShow = title.includes(keyword) || loc.includes(keyword);
            evt.setProp('display', shouldShow ? 'auto' : 'none');
        });
    }

    // --- 4. MANAGE MODAL FUNCTIONS ---
    function openManageModal() {
        const m = document.getElementById('manageModal');
        m.classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('manageBackdrop').classList.remove('opacity-0');
            document.getElementById('manageContent').classList.remove('opacity-0', 'scale-95');
            document.getElementById('manageContent').classList.add('scale-100');
        }, 10);
    }

    function closeManageModal() {
        document.getElementById('manageBackdrop').classList.add('opacity-0');
        document.getElementById('manageContent').classList.add('opacity-0', 'scale-95');
        document.getElementById('manageContent').classList.remove('scale-100');
        setTimeout(() => { document.getElementById('manageModal').classList.add('hidden'); }, 300);
    }

    function filterListTable() {
        const input = document.getElementById('searchList').value.toLowerCase();
        const rows = document.getElementsByClassName('filter-row');
        
        for (let row of rows) {
            const title = row.querySelector('.list-title').innerText.toLowerCase();
            row.style.display = title.includes(input) ? '' : 'none';
        }
    }

    // --- 5. CRUD AJAX ---
    
    // Add Schedule
    document.getElementById('addScheduleForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const btn = document.getElementById('btnSave');
        const originalText = btn.innerHTML;
        
        btn.innerHTML = 'Menyimpan...';
        btn.disabled = true;

        fetch('<?= BASE_URL ?>/user/add_schedule', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                alert('Jadwal berhasil ditambahkan!');
                window.location.reload();
            } else {
                alert('Gagal: ' + data.message);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    });

    // Delete Schedule
    function deleteSchedule(id) {
        if(!confirm("Yakin ingin menghapus jadwal kuliah ini?")) return;

        const formData = new FormData();
        formData.append('id', id);

        fetch('<?= BASE_URL ?>/user/delete_schedule', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                alert('Jadwal dihapus.');
                window.location.reload();
            } else {
                alert('Gagal menghapus.');
            }
        });
    }

    function updateClock() {
        const now = new Date();
        const dateOptions = { day: 'numeric', month: 'long', year: 'numeric' };
        const dateString = now.toLocaleDateString('id-ID', dateOptions);
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
        const timeString = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':');
        document.getElementById('liveDate').innerText = dateString;
        document.getElementById('liveTime').innerText = timeString;
    }
    
    setInterval(updateClock, 1000);
    updateClock();
</script>