<style>
    .animate-enter { animation: fadeInUp 0.5s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

    .assistant-card { transition: all 0.2s ease; border: 1px solid transparent; }
    .assistant-card:hover, .assistant-card.active { 
        background-color: #eff6ff; border-color: #bfdbfe; transform: translateX(4px); 
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
                <p class="text-blue-100 mt-2 text-sm">Pantau, edit, dan reset aktivitas asisten laboratorium.</p>
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
                    <i class="fas fa-user-clock text-3xl text-gray-300"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Pilih Asisten</h3>
                <p class="text-sm text-gray-500 mt-1">Klik nama asisten di samping untuk melihat detail logbook.</p>
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
                    <button onclick="openEditModal(null, 'add')" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-blue-500/30 transition transform hover:scale-105 flex items-center gap-2">
                        <i class="fas fa-plus"></i> Manual Input
                    </button>
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
                                <th class="p-5 border-b border-gray-100 w-1/3">Aktivitas / Keterangan</th>
                                <th class="p-5 border-b border-gray-100 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody" class="divide-y divide-gray-50 text-sm text-gray-700"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="logModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeLogModal()"></div>
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl relative z-10 overflow-hidden transform scale-100 transition-all">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex justify-between items-center text-white">
            <h3 class="font-bold text-lg" id="modalTitle">Edit Logbook</h3>
            <button onclick="closeLogModal()" class="text-white/70 hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form id="logForm" class="p-6 space-y-4" enctype="multipart/form-data">
            <input type="hidden" name="user_id" id="inputUserId">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Tanggal</label>
                    <input type="date" name="date" id="inputDate" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Status</label>
                    <select name="status" id="inputStatus" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 outline-none transition" onchange="toggleTimeFields()">
                        <option value="Hadir">Hadir</option>
                        <option value="Izin">Izin</option>
                        <option value="Sakit">Sakit</option>
                        <option value="Alpha">Alpha (Hapus)</option>
                    </select>
                </div>
            </div>

            <div id="timeFields" class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Jam Masuk</label>
                    <input type="time" name="time_in" id="inputIn" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Jam Pulang</label>
                    <input type="time" name="time_out" id="inputOut" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 outline-none transition">
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4" id="uploadContainer">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5" id="labelProofMain">Upload Bukti (Datang/Izin)</label>
                    <input type="file" name="proof_file" id="inputProof" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-xl text-xs">
                </div>
                
                <div id="proofOutContainer">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Upload Bukti Pulang</label>
                    <input type="file" name="proof_file_out" id="inputProofOut" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-xl text-xs">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Aktivitas / Keterangan</label>
                <textarea name="activity" id="inputActivity" rows="3" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 outline-none transition"></textarea>
            </div>

            <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg transition transform active:scale-95">Simpan Perubahan</button>
        </form>
    </div>
</div>

<div id="photoModal" class="hidden fixed inset-0 z-[70] flex items-center justify-center p-4 bg-black/95 backdrop-blur-md" onclick="closePhoto()">
    <div class="relative max-w-2xl w-full" onclick="event.stopPropagation()">
        <button onclick="closePhoto()" class="absolute -top-12 right-0 text-white hover:text-red-400 transition text-3xl"><i class="fas fa-times"></i></button>
        <div class="bg-white rounded-lg overflow-hidden shadow-2xl">
            <div class="p-3 bg-gray-100 border-b flex justify-between items-center">
                <span id="proofTitle" class="font-bold text-gray-700 text-sm uppercase">Bukti</span>
                <a id="downloadLink" href="#" download class="text-blue-600 hover:underline text-xs font-bold"><i class="fas fa-download"></i> Unduh</a>
            </div>
            <div class="flex justify-center bg-gray-50 p-2">
                <img id="modalImg" src="" class="hidden max-h-[70vh] object-contain">
                <iframe id="modalFrame" src="" class="hidden w-full h-[70vh]"></iframe>
            </div>
        </div>
    </div>
</div>

<div id="resetModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeResetModal()"></div>
    <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl relative z-10 p-6 text-center">
        <div class="w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fas fa-trash-alt text-2xl"></i></div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">Hapus Data?</h3>
        <p class="text-xs text-gray-500 mb-6">Pilih metode penghapusan:</p>
        <div class="space-y-3 mb-6">
            <label class="flex items-center p-3 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition">
                <input type="radio" name="resetMode" value="partial" checked class="w-4 h-4 text-blue-600">
                <div class="ml-3 text-left"><span class="block text-sm font-bold text-gray-700">Hapus Keterangan Saja</span><span class="block text-xs text-gray-400">Presensi tetap ada, logbook dikosongkan.</span></div>
            </label>
            <label class="flex items-center p-3 border border-red-200 bg-red-50/50 rounded-xl cursor-pointer hover:bg-red-50 transition">
                <input type="radio" name="resetMode" value="full" class="w-4 h-4 text-red-600">
                <div class="ml-3 text-left"><span class="block text-sm font-bold text-red-700">Hapus Total (Jadi Alpha)</span><span class="block text-xs text-red-400">Data presensi/izin dihapus permanen.</span></div>
            </label>
        </div>
        <div class="flex gap-3"><button onclick="closeResetModal()" class="flex-1 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold hover:bg-gray-50">Batal</button><button id="confirmResetBtn" class="flex-1 py-3 rounded-xl bg-red-600 text-white font-bold shadow-lg hover:bg-red-700">Proses</button></div>
    </div>
</div>

<script>
    let currentUserId = null;
    let currentUserName = '';
    let currentResetId = null;
    let currentResetType = null;

    document.getElementById('searchAssistant').addEventListener('keyup', function() {
        const key = this.value.toLowerCase();
        document.querySelectorAll('.assistant-card').forEach(card => {
            card.style.display = card.dataset.name.includes(key) ? 'flex' : 'none';
        });
    });

    function toggleTimeFields() {
        const status = document.getElementById('inputStatus').value;
        const timeFields = document.getElementById('timeFields');
        const proofOutContainer = document.getElementById('proofOutContainer');
        const labelProofMain = document.getElementById('labelProofMain');

        if (status === 'Hadir') {
            timeFields.classList.remove('hidden'); timeFields.classList.add('grid');
            proofOutContainer.classList.remove('hidden');
            labelProofMain.innerText = "Upload Bukti Datang";
        } else {
            timeFields.classList.add('hidden'); timeFields.classList.remove('grid');
            proofOutContainer.classList.add('hidden');
            labelProofMain.innerText = "Upload Bukti Izin/Sakit";
        }
    }

    function loadLogs(userId, name, photo, el) {
        currentUserId = userId; currentUserName = name;
        document.querySelectorAll('.assistant-card').forEach(c => c.classList.remove('active'));
        el.classList.add('active');
        document.getElementById('headerName').innerText = name;
        document.getElementById('headerAvatar').src = photo ? `<?= BASE_URL ?>/uploads/profile/${photo}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=random`;
        document.getElementById('inputUserId').value = userId;
        document.getElementById('emptyState').classList.add('hidden');
        document.getElementById('logContent').classList.remove('hidden');
        setTimeout(() => document.getElementById('logContent').classList.remove('opacity-0'), 50);

        const fd = new FormData(); fd.append('user_id', userId);
        fetch('<?= BASE_URL ?>/admin/getLogsByUser', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => renderTable(data));
    }

    function renderTable(logs) {
        const tbody = document.getElementById('logsTableBody');
        tbody.innerHTML = '';

        if(logs.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="p-8 text-center text-gray-400 italic">Belum ada data.</td></tr>`;
            return;
        }

        logs.forEach(log => {
            const dateStr = new Date(log.date).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'});
            
            let badgeClass = 'bg-gray-100 text-gray-600';
            if(log.color == 'green') badgeClass = 'bg-green-100 text-green-600';
            else if(log.color == 'yellow') badgeClass = 'bg-yellow-100 text-yellow-600';
            else if(log.color == 'red') badgeClass = 'bg-red-100 text-red-600';

            // 1. Bukti Datang (atau Bukti Izin)
            let proofBtn = '<span class="text-gray-300 text-xs">-</span>';
            if(log.proof_in) {
                const folder = (log.status == 'Hadir') ? 'attendance' : 'leaves';
                proofBtn = `<button onclick="viewEvidence('${log.status}', '<?= BASE_URL ?>/uploads/${folder}/${log.proof_in}')" class="text-blue-500 hover:bg-blue-50 p-1.5 rounded-lg border border-blue-100 bg-blue-50 text-xs font-bold"><i class="fas fa-image"></i> Lihat</button>`;
            }

            // 2. Bukti Pulang (Khusus Hadir)
            let proofOutBtn = '<span class="text-gray-300 text-xs">-</span>';
            if(log.status == 'Hadir') {
                if (log.proof_out) {
                    proofOutBtn = `<button onclick="viewEvidence('Pulang', '<?= BASE_URL ?>/uploads/attendance/${log.proof_out}')" class="text-purple-500 hover:bg-purple-50 p-1.5 rounded-lg border border-purple-100 bg-purple-50 text-xs font-bold"><i class="fas fa-image"></i> Lihat</button>`;
                } else {
                    proofOutBtn = '<span class="text-red-300 text-[10px] italic">Belum Pulang</span>';
                }
            }

            const actionBtns = `
                <div class="flex justify-center gap-1">
                    <button onclick='openEditModal(${JSON.stringify(log)}, "edit")' class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg"><i class="fas fa-pen"></i></button>
                    ${log.status != 'Alpha' ? `<button onclick="confirmReset('${log.id_ref}', '${log.status}')" class="p-2 text-red-500 hover:bg-red-50 rounded-lg"><i class="fas fa-trash-alt"></i></button>` : ''}
                </div>
            `;

            const timeDisplay = (log.status == 'Hadir') 
                ? `<div class="text-blue-600 font-bold">${log.time_in}</div><div class="text-orange-500 font-bold text-[10px]">${log.time_out}</div>`
                : `<div class="text-gray-400">-</div>`;

            const row = `
                <tr class="hover:bg-gray-50 transition border-b border-gray-50">
                    <td class="p-0 relative"><div class="absolute inset-y-0 left-0 w-1 ${badgeClass.replace('text','bg').split(' ')[0]}"></div></td>
                    <td class="p-5">
                        <div class="font-bold text-gray-700">${dateStr}</div>
                        <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded ${badgeClass}">${log.status}</span>
                    </td>
                    <td class="p-5 text-center text-xs font-mono">${timeDisplay}</td>
                    <td class="p-5 text-center">${proofBtn}</td>
                    <td class="p-5 text-center">${proofOutBtn}</td>
                    <td class="p-5 text-sm text-gray-600 line-clamp-2">${log.activity}</td>
                    <td class="p-5 text-center">${actionBtns}</td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    }

    function openEditModal(log, mode) {
        document.getElementById('logModal').classList.remove('hidden');
        document.getElementById('inputUserId').value = currentUserId;
        
        if(mode == 'add') {
            document.getElementById('modalTitle').innerText = 'Tambah Log Manual';
            document.getElementById('logForm').reset();
            document.getElementById('inputStatus').value = 'Hadir';
        } else {
            document.getElementById('modalTitle').innerText = 'Edit ' + log.date;
            document.getElementById('inputDate').value = log.date;
            
            let status = log.status;
            if(['Izin','Sakit'].includes(status)) document.getElementById('inputStatus').value = status;
            else if(status == 'Hadir') document.getElementById('inputStatus').value = 'Hadir';
            else document.getElementById('inputStatus').value = 'Alpha';

            document.getElementById('inputIn').value = (log.time_in !== '-' && status=='Hadir') ? log.time_in : '';
            document.getElementById('inputOut').value = (log.time_out !== '-' && status=='Hadir') ? log.time_out : '';
            
            let cleanActivity = log.activity.replace('Tidak Hadir (Alpha)', '').replace(' (Pengajuan Izin)', '');
            document.getElementById('inputActivity').value = cleanActivity;
        }
        toggleTimeFields();
    }
    
    function closeLogModal() { document.getElementById('logModal').classList.add('hidden'); }

    document.getElementById('logForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fetch('<?= BASE_URL ?>/admin/saveLogbookAdmin', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                closeLogModal();
                const activeCard = document.querySelector('.assistant-card.active');
                loadLogs(currentUserId, currentUserName, null, activeCard);
            } else alert(data.message);
        });
    });

    function confirmReset(idRef, type) {
        currentResetId = idRef; currentResetType = type;
        document.getElementById('resetModal').classList.remove('hidden');
    }
    function closeResetModal() { document.getElementById('resetModal').classList.add('hidden'); }

    document.getElementById('confirmResetBtn').addEventListener('click', function() {
        const mode = document.querySelector('input[name="resetMode"]:checked').value;
        const btn = this; btn.innerHTML = 'Memproses...'; btn.disabled = true;
        const fd = new FormData();
        fd.append('id_ref', currentResetId); fd.append('type', currentResetType); fd.append('mode', mode);

        fetch('<?= BASE_URL ?>/admin/reset_logbook', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            closeResetModal();
            btn.innerHTML = 'Proses'; btn.disabled = false;
            const activeCard = document.querySelector('.assistant-card.active');
            loadLogs(currentUserId, currentUserName, null, activeCard);
        });
    });

    function viewEvidence(type, url) {
        const ext = url.split('.').pop().toLowerCase();
        const img = document.getElementById('modalImg');
        const frame = document.getElementById('modalFrame');
        document.getElementById('downloadLink').href = url;
        document.getElementById('proofTitle').innerText = 'Bukti ' + type;
        document.getElementById('photoModal').classList.remove('hidden');
        if(ext === 'pdf') { img.classList.add('hidden'); frame.classList.remove('hidden'); frame.src = url; }
        else { frame.classList.add('hidden'); img.classList.remove('hidden'); img.src = url; }
    }
    function closePhoto() { document.getElementById('photoModal').classList.add('hidden'); }
</script>