<style>
    .animate-enter { animation: fadeInUp 0.5s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

    .assistant-card { transition: all 0.2s ease; border: 1px solid transparent; }
    .assistant-card:hover, .assistant-card.active { 
        background-color: #eff6ff; 
        border-color: #bfdbfe; 
        transform: translateX(4px); 
    }
    .assistant-card.active .icon-arrow { opacity: 1; transform: translateX(0); }
    .assistant-card .icon-arrow { opacity: 0; transform: translateX(-10px); transition: all 0.2s; }
    
    .assistant-card.active .action-icon { color: #2563eb; }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-12 h-[calc(100vh-100px)] flex flex-col">

    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden shrink-0">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">Monitoring Logbook</h1>
                <p class="text-blue-100 mt-2 text-sm">Pantau aktivitas harian asisten laboratorium.</p>
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

    <div class="flex flex-col lg:flex-row gap-6 flex-1 overflow-hidden">
        
        <div class="w-full lg:w-1/3 bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col overflow-hidden">
            <div class="p-5 border-b border-gray-100 bg-white sticky top-0 z-10">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h3 class="font-extrabold text-gray-700 text-sm uppercase tracking-wide">Data Asisten</h3>
                        <p class="text-[10px] text-gray-400">Pilih asisten untuk melihat logbook</p>
                    </div>
                    <div class="px-3 py-1 bg-blue-50 text-blue-600 rounded-lg text-xs font-bold border border-blue-100 shadow-sm">
                        <span class="font-normal text-blue-400">Total: </span> <?= count($assistants) ?> 
                    </div>
                </div>
                <div class="relative group">
                    <i class="fas fa-search absolute left-4 top-3.5 text-gray-400 text-sm"></i>
                    <input type="text" id="searchAssistant" placeholder="Cari nama asisten..." 
                           class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar" id="assistantList">
                <?php foreach($assistants as $ast): ?>
                <div onclick="loadLogs(<?= $ast['id'] ?>, '<?= htmlspecialchars($ast['name'], ENT_QUOTES) ?>', '<?= $ast['photo_profile'] ?? '' ?>', this)" 
                     class="assistant-card p-3 rounded-2xl cursor-pointer flex items-center justify-between group" 
                     data-id="<?= $ast['id'] ?>" 
                     data-name="<?= strtolower($ast['name']) ?>">
                    
                    <div class="flex items-center gap-3">
                        <img src="<?= !empty($ast['photo_profile']) ? BASE_URL.'/uploads/profile/'.$ast['photo_profile'] : 'https://ui-avatars.com/api/?name='.urlencode($ast['name']).'&background=random' ?>" 
                             class="w-10 h-10 rounded-full object-cover border border-gray-200 shadow-sm">
                        <div>
                            <h4 class="font-bold text-gray-800 text-sm leading-tight"><?= $ast['name'] ?></h4>
                            <p class="text-[10px] text-gray-500 font-medium mt-0.5"><?= $ast['position'] ?? 'Anggota' ?></p>
                        </div>
                    </div>
                    
                    <button class="w-8 h-8 rounded-full bg-white border border-gray-100 text-gray-400 flex items-center justify-center shadow-sm group-hover:text-blue-600 transition action-icon">
                        <i class="fas fa-chevron-right text-xs icon-default"></i>
                        <i class="fas fa-times text-xs icon-active hidden"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="w-full lg:w-2/3 bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col overflow-hidden relative">
            
            <div id="emptyState" class="absolute inset-0 flex flex-col items-center justify-center text-center bg-white z-20 transition-opacity duration-300">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4 animate-bounce">
                    <i class="fas fa-book-open text-3xl text-gray-300"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Pilih Asisten</h3>
                <p class="text-sm text-gray-500 mt-1">Klik salah satu asisten di samping untuk melihat logbook.</p>
            </div>

            <div id="logContent" class="flex flex-col h-full hidden opacity-0 transition-opacity duration-300">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <div class="flex items-center gap-4">
                        <img id="headerAvatar" src="" class="w-12 h-12 rounded-full border-2 border-white shadow-md object-cover">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Logbook Asisten</p>
                            <h2 id="headerName" class="text-xl font-extrabold text-gray-800"></h2>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-0 custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-white sticky top-0 z-10 shadow-sm text-xs font-bold text-gray-400 uppercase">
                            <tr>
                                <th class="p-5 border-b border-gray-100">Tanggal</th>
                                <th class="p-5 border-b border-gray-100">Jam Masuk</th>
                                <th class="p-5 border-b border-gray-100">Aktivitas</th>
                                <th class="p-5 border-b border-gray-100">Jam Pulang</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody" class="divide-y divide-gray-50 text-sm text-gray-700">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentUserId = null;
    let currentUserName = '';

    document.getElementById('searchAssistant').addEventListener('keyup', function() {
        const key = this.value.toLowerCase();
        document.querySelectorAll('.assistant-card').forEach(card => {
            const name = card.getAttribute('data-name');
            card.style.display = name.includes(key) ? 'flex' : 'none';
        });
    });

    function loadLogs(userId, name, photo, element) {
        document.querySelectorAll('.assistant-card').forEach(c => {
            c.querySelector('.icon-default').classList.remove('hidden');
            c.querySelector('.icon-active').classList.add('hidden');
        });

        if (currentUserId === userId) {
            resetView();
            return;
        }

        currentUserId = userId;
        currentUserName = name;

        document.querySelectorAll('.assistant-card').forEach(c => c.classList.remove('active'));
        element.classList.add('active');
        
        element.querySelector('.icon-default').classList.add('hidden');
        element.querySelector('.icon-active').classList.remove('hidden');
        
        const emptyState = document.getElementById('emptyState');
        const logContent = document.getElementById('logContent');
        
        emptyState.classList.add('opacity-0');
        setTimeout(() => {
            emptyState.classList.add('hidden');
            logContent.classList.remove('hidden');
            setTimeout(() => logContent.classList.remove('opacity-0'), 50);
        }, 300);
        
        document.getElementById('headerName').innerText = name;
        document.getElementById('headerAvatar').src = photo ? `<?= BASE_URL ?>/uploads/profile/${photo}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=random`;

        const fd = new FormData();
        fd.append('user_id', userId);

        fetch('<?= BASE_URL ?>/admin/getLogsByUser', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => renderTable(data))
        .catch(err => console.error(err));
    }

    function resetView() {
        currentUserId = null;
        currentUserName = '';

        document.querySelectorAll('.assistant-card').forEach(c => {
            c.classList.remove('active');
            c.querySelector('.icon-default').classList.remove('hidden');
            c.querySelector('.icon-active').classList.add('hidden');
        });

        const emptyState = document.getElementById('emptyState');
        const logContent = document.getElementById('logContent');

        logContent.classList.add('opacity-0');
        setTimeout(() => {
            logContent.classList.add('hidden');
            emptyState.classList.remove('hidden');
            setTimeout(() => emptyState.classList.remove('opacity-0'), 50);
        }, 300);
    }

    function renderTable(logs) {
        const tbody = document.getElementById('logsTableBody');
        tbody.innerHTML = '';

        if(logs.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-gray-400 italic">Belum ada data logbook untuk asisten ini.</td></tr>`;
            return;
        }

        logs.forEach(log => {
            const timeIn = log.time_in ? log.time_in.substring(0,5) : '-';
            const timeOut = log.time_out ? log.time_out.substring(0,5) : '-';
            const desc = log.activity || '<span class="text-gray-400 italic">Belum diisi</span>';
            const dateStr = new Date(log.date).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'});

            const row = `
                <tr class="hover:bg-blue-50/50 transition group">
                    <td class="p-5 font-bold text-gray-700 w-32">${dateStr}</td>
                    <td class="p-5 font-mono text-green-600 font-bold">${timeIn}</td>
                    <td class="p-5 max-w-xs">
                        <div class="line-clamp-2 text-gray-600">${desc}</div>
                    </td>
                    <td class="p-5 font-mono text-red-600 font-bold">${timeOut}</td>
                    <td class="p-5 text-center">
                        <div class="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition">
                            <button onclick='editLog(${JSON.stringify(log)})' class="w-8 h-8 rounded-lg bg-white border border-gray-200 text-yellow-500 hover:bg-yellow-50 hover:border-yellow-200 transition shadow-sm"><i class="fas fa-pen text-xs"></i></button>
                            ${log.id_logbook ? 
                                `<button onclick="triggerDeleteLog(${log.id_logbook})" class="w-8 h-8 rounded-lg bg-white border border-gray-200 text-red-500 hover:bg-red-50 hover:border-red-200 transition shadow-sm"><i class="fas fa-trash text-xs"></i></button>` 
                                : ''}
                        </div>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    }
</script>