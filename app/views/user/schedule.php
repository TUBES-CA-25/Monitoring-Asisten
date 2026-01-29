<?php
// --- PHP DATA PROCESSING ---
$schedulesData = [];
if (isset($raw_schedules)) $schedulesData = $raw_schedules;
elseif (isset($all_schedules)) $schedulesData = $all_schedules;

$calendarEvents = [];

if (!empty($schedulesData)) {
    foreach ($schedulesData as $sch) {
        // 1. Tentukan Warna
        $colorCode = '#f97316'; $typeLabel = 'Piket';
        $t = strtolower($sch['type'] ?? '');
        
        if ($t == 'class' || $t == 'kuliah') { 
            $colorCode = '#10b981'; $typeLabel = 'Kuliah'; 
        } elseif ($t == 'asisten' || $t == 'assistant') { 
            $colorCode = '#3b82f6'; $typeLabel = 'Asisten'; 
        }

        // 2. Event Object
        $event = [
            'title' => $sch['title'] ?? 'Kegiatan',
            'backgroundColor' => '#ffffff', 
            'borderColor' => 'transparent',
            'textColor' => '#1e293b',
            'extendedProps' => [
                'type' => $typeLabel,
                'colorCode' => $colorCode,
                'userId' => $sch['user_id'] ?? 'general',
                'userName' => $sch['user_name'] ?? 'Saya',
                'location' => $sch['location'] ?? '-',
                'desc' => $sch['description'] ?? '-'
            ]
        ];

        // 3. Logic Recurring
        if (!empty($sch['hari'])) {
            $dayFC = ($sch['hari'] == 7) ? 0 : $sch['hari']; 
            $event['daysOfWeek'] = [$dayFC];
            $event['startTime'] = $sch['start_time'];
            $event['endTime'] = $sch['end_time'] ?? '00:00:00';
        } else {
            $startDt = $sch['start_time'];
            if (isset($sch['tanggal']) && strpos($startDt, 'T') === false) {
                $startDt = $sch['tanggal'] . 'T' . $sch['start_time'];
            }
            $event['start'] = $startDt;
            if (!empty($sch['end_time'])) {
                $event['end'] = $sch['tanggal'] . 'T' . $sch['end_time'];
            }
        }
        $calendarEvents[] = $event;
    }
}
?>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

<style>
    .animate-enter { animation: fadeInUp 0.5s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    
    #calendar { min-height: 700px; font-family: 'Inter', sans-serif; 
        --fc-border-color: #f1f5f9; --fc-button-text-color: #475569; 
        --fc-button-bg-color: white; --fc-button-border-color: #e2e8f0;
        --fc-today-bg-color: transparent;
    }
    .fc-header-toolbar { margin-bottom: 1.5rem !important; }
    .fc-button { border-radius: 99px !important; font-weight: 600 !important; box-shadow: 0 1px 2px rgba(0,0,0,0.05); text-transform: capitalize; }
    .fc-button-active { background: #eff6ff !important; color: #2563eb !important; border-color: #bfdbfe !important; }
    
    .fc-h-event { border: 1px solid #e2e8f0; background: white; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); margin-top: 2px; }
    .fc-h-event:hover { transform: translateY(-1px); border-color: #bfdbfe; z-index: 10; }

    .toggle-radio:checked + label { background-color: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
    .dot-legend { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter h-full flex flex-col pb-12">
    
    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0 text-center md:text-left">
                <h1 class="text-3xl font-extrabold tracking-tight">Jadwal & Kegiatan</h1>
                <p class="text-blue-100 mt-2 text-sm">Kelola jadwal kuliah dan pantau kegiatan laboratorium.</p>
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

    <div class="flex flex-col lg:flex-row gap-6">
        
        <div class="w-full lg:w-72 space-y-6">
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200">
                <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wide mb-4">Kategori</h3>
                <div class="space-y-3 text-sm font-medium text-gray-600">
                    <div class="flex items-center"><span class="dot-legend bg-green-500"></span> Kuliah</div>
                    <div class="flex items-center"><span class="dot-legend bg-blue-500"></span> Asisten</div>
                    <div class="flex items-center"><span class="dot-legend bg-red-500"></span> Piket</div>
                </div>
            </div>
            
            <?php if($_SESSION['role'] == 'User'): ?>
            <button onclick="openManageModal()" class="w-full bg-white text-blue-600 px-4 py-3 rounded-xl font-bold shadow-sm hover:shadow-md hover:bg-blue-50 transition transform hover:scale-105 flex items-center justify-center gap-2 border border-blue-100">
                <i class="fas fa-edit"></i> Atur Jadwal Kuliah
            </button>
            <?php endif; ?>

            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200">
                <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wide mb-3">Filter</h3>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-xs"></i>
                    <input type="text" id="searchInput" placeholder="Cari kegiatan..." onkeyup="searchCalendar()" class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                </div>
            </div>
        </div>

        <div class="flex-1 bg-white rounded-3xl shadow-sm border border-gray-200 p-6 relative">
            <div id='calendar'></div>
        </div>
    </div>
</div>

<div id="scheduleModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="modalBackdrop" onclick="closeModal()"></div>
    
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col max-h-[80vh]" id="modalContent">
        <div class="bg-white border-b border-gray-100 p-5 shrink-0 flex justify-between items-center">
            <div>
                <p class="text-blue-600 text-[10px] font-extrabold uppercase tracking-widest mb-1">Detail Harian</p>
                <h3 id="modalDateTitle" class="text-xl font-extrabold text-gray-800"></h3>
            </div>
            <button onclick="closeModal()" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 hover:text-red-600 hover:bg-red-50 transition"><i class="fas fa-times"></i></button>
        </div>

        <div class="p-0 overflow-y-auto flex-1 bg-gray-50 custom-scrollbar" id="modalListContainer"></div>

        <div class="p-4 border-t border-gray-100 bg-white text-right shrink-0">
            <button onclick="closeModal()" class="px-6 py-2 bg-gray-900 hover:bg-gray-800 text-white font-bold rounded-xl transition text-sm shadow-lg">Tutup</button>
        </div>
    </div>
</div>

<?php if($_SESSION['role'] == 'User'): ?>
<div id="manageModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="manageBackdrop" onclick="closeManageModal()"></div>
    
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col h-[85vh]" id="manageContent">
        <div class="bg-white border-b border-gray-100 p-6 flex justify-between items-center shrink-0">
            <h3 class="text-xl font-extrabold text-gray-800">Manajemen Jadwal Kuliah</h3>
            <button onclick="closeManageModal()" class="w-8 h-8 rounded-full bg-gray-100 hover:text-red-600 flex items-center justify-center"><i class="fas fa-times"></i></button>
        </div>

        <div class="flex flex-col md:flex-row flex-1 overflow-hidden">
            <div class="w-full md:w-2/3 bg-gray-50 p-6 overflow-y-auto border-r border-gray-200 custom-scrollbar">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-gray-100 text-[10px] font-bold text-gray-500 uppercase">
                            <tr>
                                <th class="p-3">Waktu</th><th class="p-3">Kegiatan</th><th class="p-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100">
                            <?php if(!empty($my_classes)): foreach($my_classes as $cls): ?>
                            <tr class="hover:bg-blue-50 transition">
                                <td class="p-3">
                                    <div class="font-bold text-gray-700"><?= $cls['waktu_display'] ?></div> 
                                    <div class="text-xs text-gray-400 font-mono"><?= date('H:i', strtotime($cls['start_time'])) ?></div>
                                </td>
                                <td class="p-3">
                                    <div class="font-bold text-blue-600"><?= $cls['title'] ?></div>
                                    <div class="text-xs text-gray-500"><?= $cls['location'] ?></div>
                                </td>
                                <td class="p-3 text-center">
                                    <button onclick="deleteSchedule(<?= $cls['id'] ?>)" class="text-red-500 hover:bg-red-50 p-2 rounded"><i class="fas fa-trash-alt"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="3" class="p-6 text-center text-gray-400 italic text-xs">Belum ada jadwal.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="w-full md:w-1/3 p-6 bg-white overflow-y-auto">
                <h4 class="font-bold text-gray-800 text-sm mb-4 border-b pb-2">Tambah Jadwal</h4>
                <form id="addScheduleForm" class="space-y-3">
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Frekuensi</label>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <input type="radio" id="type_repeat" name="type_repeat" value="repeat" class="hidden toggle-radio" checked onchange="toggleDateInput()">
                                <label for="type_repeat" class="block text-center border border-gray-200 p-2 rounded-lg cursor-pointer text-xs font-bold text-gray-600 transition hover:bg-gray-50">Mingguan</label>
                            </div>
                            <div class="flex-1">
                                <input type="radio" id="type_once" name="type_repeat" value="once" class="hidden toggle-radio" onchange="toggleDateInput()">
                                <label for="type_once" class="block text-center border border-gray-200 p-2 rounded-lg cursor-pointer text-xs font-bold text-gray-600 transition hover:bg-gray-50">Tanggal</label>
                            </div>
                        </div>
                    </div>

                    <div id="inputDay">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Hari</label>
                        <select name="day" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="1">Senin</option><option value="2">Selasa</option><option value="3">Rabu</option>
                            <option value="4">Kamis</option><option value="5">Jumat</option><option value="6">Sabtu</option><option value="7">Minggu</option>
                        </select>
                    </div>
                    <div id="inputDate" class="hidden">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal</label>
                        <input type="date" name="date" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Mata Kuliah</label><input type="text" name="course_name" required class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-100"></div>
                    <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Dosen / Info</label><input type="text" name="lecturer" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-100"></div>
                    <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Ruangan</label><input type="text" name="room" required class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-100"></div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Mulai</label><input type="time" name="start_clock" required class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm"></div>
                        <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Selesai</label><input type="time" name="end_clock" required class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm"></div>
                    </div>

                    <button type="submit" id="btnSave" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl shadow-lg mt-4 transition transform hover:scale-105">SIMPAN</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    const rawSchedules = <?= json_encode($schedulesData ?? []) ?>; 
    const calendarEvents = <?= json_encode($calendarEvents) ?>;
    let calendar;

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: { left: 'title', right: 'prev,today,next' },
            selectable: true,
            events: calendarEvents,
            
            dateClick: function(info) { openListModal(info.date); },
            eventClick: function(info) { openListModal(info.event.start || info.event.startTime); },

            eventContent: function(arg) {
                let props = arg.event.extendedProps;
                let shortName = props.userName.split(' ')[0];
                let dotColorStyle = `background-color: ${props.colorCode};`;
                return { 
                    html: `
                    <div class="flex items-center gap-2 w-full overflow-hidden px-1">
                        <div class="w-1.5 h-1.5 rounded-full shrink-0" style="${dotColorStyle}"></div>
                        <div class="flex-1 truncate text-[10px] text-gray-600 leading-tight">
                            <span class="font-bold mr-1 text-gray-800">${shortName}:</span> ${arg.event.title}
                        </div>
                    </div>` 
                };
            }
        });
        calendar.render();
        window.calendar = calendar;
    });

    function openListModal(dateObj) {
        // Handle input jika dateObj berupa objek waktu FullCalendar yang kompleks
        if(!dateObj) dateObj = new Date(); 
        const dateStr = dateObj.toISOString().split('T')[0];
        
        let jsDay = dateObj.getDay();
        let dbDay = (jsDay === 0) ? 7 : jsDay; // 1=Senin...7=Minggu

        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('modalDateTitle').innerText = dateObj.toLocaleDateString('id-ID', options);
        
        const container = document.getElementById('modalListContainer');
        container.innerHTML = '';

        const dailySch = rawSchedules.filter(sch => {
            if (sch.tanggal && sch.tanggal === dateStr) return true;
            if (sch.hari && parseInt(sch.hari) === dbDay) return true;
            return false;
        });

        dailySch.sort((a, b) => (a.start_time || '').localeCompare(b.start_time || ''));

        if (dailySch.length > 0) {
            dailySch.forEach(sch => {
                let timeStart = sch.start_time.substring(0,5);
                let timeEnd = sch.end_time ? sch.end_time.substring(0,5) : 'Selesai';
                let room = sch.location || '-';
                let type = (sch.type || '').toLowerCase();
                let badgeClass = 'text-gray-500 bg-gray-100';
                
                if(type.includes('kuliah')) badgeClass = 'text-green-600 bg-green-50';
                else if(type.includes('asisten')) badgeClass = 'text-blue-600 bg-blue-50';
                else if(type.includes('piket')) badgeClass = 'text-red-600 bg-red-50';

                container.innerHTML += `
                    <div class="bg-white p-4 border-b border-gray-100 flex gap-4 hover:bg-gray-50 transition">
                        <div class="w-16 flex flex-col justify-center items-center bg-gray-50 rounded-lg shrink-0 border border-gray-200">
                            <span class="text-sm font-bold text-gray-700">${timeStart}</span>
                            <span class="text-[10px] text-gray-400">s/d</span>
                            <span class="text-xs font-semibold text-gray-500">${timeEnd}</span>
                        </div>
                        <div class="flex-1 min-w-0 flex flex-col justify-center">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="font-bold text-gray-800 text-sm truncate">${sch.title}</h4>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase ${badgeClass}">${type}</span>
                            </div>
                            <p class="text-xs text-gray-500 truncate">
                                <span class="font-semibold text-blue-600">${sch.user_name ?? 'Saya'}</span> • 
                                ${sch.description || '-'}
                            </p>
                        </div>
                        <div class="flex items-center">
                            <span class="px-3 py-1 bg-white border border-gray-200 rounded text-xs font-bold text-gray-600 shadow-sm">${room}</span>
                        </div>
                    </div>
                `;
            });
        } else {
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center h-48 text-gray-400 text-center">
                    <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                        <i class="fas fa-calendar-times text-xl opacity-40"></i>
                    </div>
                    <p class="font-medium text-sm text-gray-500">Jadwal Kosong</p>
                    <p class="text-xs text-gray-400 mt-1">Tidak ada kegiatan terjadwal pada hari ini.</p>
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
        const m = document.getElementById('scheduleModal');
        document.getElementById('modalBackdrop').classList.add('opacity-0');
        document.getElementById('modalContent').classList.add('opacity-0', 'scale-95');
        document.getElementById('modalContent').classList.remove('scale-100');
        setTimeout(() => { m.classList.add('hidden'); }, 300);
    }

    // --- MANAGE MODAL (FIXED) ---
    function openManageModal() {
        const m = document.getElementById('manageModal');
        if(!m) return;
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

    function searchCalendar() {
        const key = document.getElementById('searchInput').value.toLowerCase();
        if(!window.calendar) return;
        window.calendar.getEvents().forEach(evt => {
            const show = evt.title.toLowerCase().includes(key) || (evt.extendedProps.location||'').toLowerCase().includes(key);
            evt.setProp('display', show ? 'auto' : 'none');
        });
    }

    function toggleDateInput() {
        if(document.getElementById('type_repeat').checked) {
            document.getElementById('inputDay').classList.remove('hidden');
            document.getElementById('inputDate').classList.add('hidden');
        } else {
            document.getElementById('inputDay').classList.add('hidden');
            document.getElementById('inputDate').classList.remove('hidden');
        }
    }

    const addForm = document.getElementById('addScheduleForm');
    if(addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const btn = document.getElementById('btnSave');
            const txt = btn.innerHTML;
            btn.innerHTML = 'Menyimpan...'; btn.disabled = true;

            fetch('<?= BASE_URL ?>/user/add_schedule', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') { alert('Berhasil!'); window.location.reload(); }
                else { alert('Gagal: ' + data.message); btn.innerHTML = txt; btn.disabled = false; }
            });
        });
    }

    function deleteSchedule(id) {
        if(confirm("Hapus jadwal ini?")) {
            const fd = new FormData(); fd.append('id', id);
            fetch('<?= BASE_URL ?>/user/delete_schedule', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') window.location.reload();
                else alert('Gagal menghapus.');
            });
        }
    }

    function updateClock() {
        const now = new Date();
        const dateOptions = { day: '2-digit', month: 'long', year: 'numeric' };
        const dateString = now.toLocaleDateString('id-ID', dateOptions);
        
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
        const timeString = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':');
        document.getElementById('liveDate').innerText = dateString;
        document.getElementById('liveTime').innerText = timeString;
    }
    setInterval(updateClock, 1000); updateClock();
</script>