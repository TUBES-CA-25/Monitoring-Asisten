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
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-12 h-[calc(100vh-100px)] flex flex-col">

    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden shrink-0">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">Monitoring Logbook</h1>
                <p class="text-blue-100 mt-2 text-sm">Pantau aktivitas harian seluruh asisten.</p>
            </div>
            <div class="text-center md:text-right bg-white/10 p-3 rounded-2xl backdrop-blur-sm border border-white/20">
                <p class="text-[10px] font-bold text-blue-100 uppercase tracking-widest mb-1">Waktu Sistem</p>
                <h2 id="liveDate" class="text-xl font-bold font-mono"><?= date('d F Y') ?></h2>
                <span class="bg-blue-900/30 px-2 py-0.5 rounded text-sm font-mono"><?= date('H:i:s') ?> WITA</span>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 flex-1 overflow-hidden">
        
        <div class="w-full lg:w-1/3 bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col overflow-hidden">
            <div class="p-5 border-b border-gray-100 bg-white sticky top-0 z-10">
                <h3 class="font-extrabold text-gray-700 text-sm uppercase tracking-wide mb-4">Data Asisten</h3>
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
                     data-name="<?= strtolower($ast['name']) ?>">
                    
                    <div class="flex items-center gap-3">
                        <img src="<?= !empty($ast['photo_profile']) ? BASE_URL.'/uploads/profile/'.$ast['photo_profile'] : 'https://ui-avatars.com/api/?name='.urlencode($ast['name']).'&background=random' ?>" 
                             class="w-10 h-10 rounded-full object-cover border border-gray-200 shadow-sm">
                        <div>
                            <h4 class="font-bold text-gray-800 text-sm leading-tight"><?= $ast['name'] ?></h4>
                            <p class="text-[10px] text-gray-500 font-medium mt-0.5"><?= $ast['position'] ?? 'Anggota' ?></p>
                        </div>
                    </div>
                    
                    <i class="fas fa-chevron-right text-gray-300 icon-arrow"></i>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="w-full lg:w-2/3 bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col overflow-hidden relative">
            
            <div id="emptyState" class="absolute inset-0 flex flex-col items-center justify-center text-center bg-white z-20 transition-opacity duration-300">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4 animate-bounce">
                    <i class="fas fa-eye text-3xl text-gray-300"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Pilih Asisten</h3>
                <p class="text-sm text-gray-500 mt-1">Klik nama asisten di samping untuk melihat logbook.</p>
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
                                <th class="p-5 border-b border-gray-100 w-4">Sts</th>
                                <th class="p-5 border-b border-gray-100">Tanggal</th>
                                <th class="p-5 border-b border-gray-100 text-center">Waktu</th>
                                <th class="p-5 border-b border-gray-100 text-center">Bukti Datang</th>
                                <th class="p-5 border-b border-gray-100 text-center">Bukti Pulang</th>
                                <th class="p-5 border-b border-gray-100 w-10">Aktivitas</th>
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

<div id="photoModal" class="hidden fixed inset-0 z-[99] flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm" onclick="closePhoto()">
    <div class="relative max-w-2xl w-full" onclick="event.stopPropagation()">
        <button onclick="closePhoto()" class="absolute -top-12 right-0 text-white hover:text-red-400 transition text-3xl"><i class="fas fa-times"></i></button>
        <div class="bg-white rounded-lg overflow-hidden shadow-2xl">
            <div class="p-3 bg-gray-100 border-b flex justify-between items-center">
                <span id="proofTitle" class="font-bold text-gray-700 text-sm uppercase">Bukti</span>
                <a id="downloadLink" href="#" download class="text-blue-600 hover:underline text-xs font-bold"><i class="fas fa-download"></i> Unduh</a>
            </div>
            <img id="modalImg" src="" class="w-full h-auto max-h-[70vh] object-contain bg-gray-50">
        </div>
    </div>
</div>

<script>
    let currentUserId = null;

    document.getElementById('searchAssistant').addEventListener('keyup', function() {
        const key = this.value.toLowerCase();
        document.querySelectorAll('.assistant-card').forEach(card => {
            card.style.display = card.dataset.name.includes(key) ? 'flex' : 'none';
        });
    });

    function loadLogs(userId, name, photo, element) {
        if (currentUserId === userId) { resetView(); return; }

        currentUserId = userId;

        // UI Updates
        document.querySelectorAll('.assistant-card').forEach(c => c.classList.remove('active'));
        element.classList.add('active');
        
        document.getElementById('emptyState').classList.add('hidden');
        document.getElementById('logContent').classList.remove('hidden');
        setTimeout(() => document.getElementById('logContent').classList.remove('opacity-0'), 50);
        
        document.getElementById('headerName').innerText = name;
        document.getElementById('headerAvatar').src = photo ? `<?= BASE_URL ?>/uploads/profile/${photo}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=random`;

        const fd = new FormData();
        fd.append('user_id', userId);

        // Fetch Data Unified dari SuperAdminController
        fetch('<?= BASE_URL ?>/superadmin/getLogsByUser', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => renderTable(data))
        .catch(err => console.error('Error fetching logs:', err));
    }

    function resetView() {
        currentUserId = null;
        document.querySelectorAll('.assistant-card').forEach(c => c.classList.remove('active'));
        
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
            tbody.innerHTML = `<tr><td colspan="6" class="p-8 text-center text-gray-400 italic">Belum ada data logbook.</td></tr>`;
            return;
        }

        logs.forEach(log => {
            const dateStr = new Date(log.date).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'});

            // Badge Warna (Unified Logic)
            let badgeClass = 'bg-gray-100 text-gray-600';
            if(log.color == 'green') badgeClass = 'bg-green-100 text-green-600';
            else if(log.color == 'yellow') badgeClass = 'bg-yellow-100 text-yellow-600';
            else if(log.color == 'red') badgeClass = 'bg-red-100 text-red-600';

            // Tombol Bukti Datang
            let proofInBtn = '<span class="text-gray-300 text-xs">-</span>';
            if(log.proof_in) {
                const folder = log.status == 'Hadir' ? 'attendance' : 'leaves';
                proofInBtn = `<button onclick="viewEvidence('${log.status} (Datang)', '<?= BASE_URL ?>/uploads/${folder}/${log.proof_in}')" class="text-blue-500 hover:bg-blue-50 p-1.5 rounded-lg"><i class="fas fa-eye"></i></button>`;
            }

            // Tombol Bukti Pulang
            let proofOutBtn = '<span class="text-gray-300 text-xs">-</span>';
            if(log.proof_out) {
                proofOutBtn = `<button onclick="viewEvidence('Pulang', '<?= BASE_URL ?>/uploads/attendance/${log.proof_out}')" class="text-orange-500 hover:bg-orange-50 p-1.5 rounded-lg"><i class="fas fa-eye"></i></button>`;
            }

            // Render Baris (Tanpa Kolom Aksi)
            const row = `
                <tr class="hover:bg-gray-50 transition border-b border-gray-50">
                    <td class="p-0 relative"><div class="absolute inset-y-0 left-0 w-1 ${badgeClass.replace('text','bg').split(' ')[0]}"></div></td>
                    <td class="p-5">
                        <div class="font-bold text-gray-700">${dateStr}</div>
                        <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded ${badgeClass}">${log.status}</span>
                    </td>
                    <td class="p-5 text-center text-xs font-mono">
                        <div class="text-blue-600 font-bold">IN: ${log.time_in}</div>
                        <div class="text-orange-500 font-bold">OUT: ${log.time_out}</div>
                    </td>
                    <td class="p-5 text-center">${proofInBtn}</td>
                    <td class="p-5 text-center">${proofOutBtn}</td>
                    <td class="p-5 text-sm text-gray-600 line-clamp-2">${log.activity}</td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    }

    function viewEvidence(type, url) {
        document.getElementById('modalImg').src = url;
        document.getElementById('downloadLink').href = url;
        document.getElementById('proofTitle').innerText = 'Bukti ' + type;
        document.getElementById('photoModal').classList.remove('hidden');
    }
    
    function closePhoto() {
        document.getElementById('photoModal').classList.add('hidden');
    }
</script>