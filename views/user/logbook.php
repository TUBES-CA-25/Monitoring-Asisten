<div class="max-w-7xl mx-auto space-y-6 pb-12">
    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden shrink-0">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">Monitoring Logbook</h1>
                <p class="text-blue-100 mt-2 text-sm">Rekam jejak aktivitas harian, perizinan, dan kehadiran Anda.</p>
            </div>
            <div class="text-center md:text-right bg-white/10 p-3 rounded-2xl backdrop-blur-sm border border-white/20">
                <p class="text-[10px] font-bold text-blue-100 uppercase tracking-widest mb-1">Waktu Sistem</p>
                <h2 id="liveDate" class="text-xl font-bold font-mono"><?= date('d F Y') ?></h2>
                <span class="bg-blue-900/30 px-2 py-0.5 rounded text-sm font-mono"><?= date('H:i:s') ?> WITA</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[11px] text-gray-500 border-b border-gray-200 bg-gray-50">
                        <th class="w-1 p-0"></th> 
                        <th class="px-4 py-3 font-bold uppercase tracking-wider w-[100px]">Tanggal</th>
                        <th class="px-3 py-5 font-bold uppercase tracking-wider text-center w-[100px]">Waktu</th>
                        <th class="px-2 py-5 font-bold uppercase tracking-wider text-center w-[90px]">Masuk</th>
                        <th class="px-2 py-5 font-bold uppercase tracking-wider text-center w-[90px]">Pulang</th>
                        <th class="px-4 py-5 font-bold uppercase tracking-wider w-[100px]">Keterangan Aktivitas</th>
                        <th class="px-4 py-5 font-bold uppercase tracking-wider text-center w-[120px]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-700 divide-y divide-gray-100">
                    <?php if(empty($logs)): ?>
                        <tr>
                            <td colspan="7" class="p-8 text-center text-gray-400 italic">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <i class="fas fa-book-open text-2xl opacity-20"></i>
                                    <span class="text-xs">Belum ada riwayat aktivitas bulan ini.</span>
                                </div>
                            </td>
                        </tr>
                    <?php else: foreach($logs as $log): 
                        // Setup Variabel Tampilan
                        $isToday = ($log['date'] == date('Y-m-d'));
                        $dateDisplay = date('d M Y', strtotime($log['date']));
                        $dayName = date('l', strtotime($log['date'])); 
                        
                        // Warna Badge Status
                        $badgeClass = match($log['color']) {
                            'green' => 'bg-green-50 text-green-700 border-green-100',
                            'yellow' => 'bg-yellow-50 text-yellow-700 border-yellow-100',
                            'red' => 'bg-red-50 text-red-700 border-red-100',
                            default => 'bg-gray-50 text-gray-600'
                        };
                        
                        // Bar Warna Kiri
                        $barColor = match($log['color']) {
                            'green' => 'bg-green-500',
                            'yellow' => 'bg-yellow-500',
                            'red' => 'bg-red-500',
                            default => 'bg-gray-300'
                        };
                    ?>
                        <tr class="hover:bg-blue-50/30 transition group">
                            <td class="p-0 relative w-1">
                                <div class="absolute inset-y-0 left-0 w-1 <?= $barColor ?>"></div>
                            </td>

                            <td class="px-4 py-3 align-middle">
                                <div class="flex flex-col gap-1">
                                    <span class="font-bold text-gray-800 text-xs whitespace-nowrap"><?= $dateDisplay ?></span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] text-gray-400"><?= $dayName ?></span>
                                        <?php if($isToday): ?>
                                            <span class="text-[9px] bg-blue-100 text-blue-600 px-1.5 rounded font-bold">TODAY</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold uppercase border w-max mt-0.5 <?= $badgeClass ?>">
                                        <?= $log['status'] ?>
                                    </span>
                                </div>
                            </td>

                            <td class="px-2 py-3 text-center align-middle">
                                <div class="flex flex-col gap-1 text-[11px] font-mono leading-tight">
                                    <div class="text-gray-500">
                                        <span class="text-[9px] font-bold text-gray-400 mr-1">IN</span>
                                        <span class="<?= $log['color'] == 'green' ? 'text-blue-600 font-bold' : '' ?>"><?= $log['time_in'] ?></span>
                                    </div>
                                    <div class="text-gray-500">
                                        <span class="text-[9px] font-bold text-gray-400 mr-1">OUT</span>
                                        <span class="<?= $log['color'] == 'green' ? 'text-orange-600 font-bold' : '' ?>"><?= $log['time_out'] ?></span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-2 py-3 text-center align-middle">
                                <?php if($log['proof_in']): ?>
                                    <button onclick="viewEvidence('<?= $log['status'] ?> (Datang)', '<?= BASE_URL ?>/uploads/<?= ($log['status'] == 'Hadir' ? 'attendance' : 'leaves') ?>/<?= $log['proof_in'] ?>')" 
                                            class="w-8 h-8 rounded-lg bg-gray-50 border border-gray-200 hover:border-blue-400 hover:bg-blue-50 hover:text-blue-600 text-gray-400 transition flex items-center justify-center mx-auto" title="Lihat">
                                        <i class="fas fa-eye text-xs"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="text-gray-300 text-xs">-</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-2 py-3 text-center align-middle">
                                <?php if($log['proof_out']): ?>
                                    <button onclick="viewEvidence('Pulang', '<?= BASE_URL ?>/uploads/attendance/<?= $log['proof_out'] ?>')" 
                                            class="w-8 h-8 rounded-lg bg-gray-50 border border-gray-200 hover:border-orange-400 hover:bg-orange-50 hover:text-orange-600 text-gray-400 transition flex items-center justify-center mx-auto" title="Lihat">
                                        <i class="fas fa-eye text-xs"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="text-gray-300 text-xs">-</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-4 py-3 align-middle">
                                <?php if(!empty($log['activity'])): ?>
                                    <p class="text-[13px] text-gray-700 leading-snug line-clamp-2" title="<?= htmlspecialchars($log['activity']) ?>">
                                        <?= nl2br($log['activity']) ?>
                                    </p>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400 italic opacity-60">Belum ada catatan.</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-4 py-3 text-center align-middle whitespace-nowrap">
                                <?php if($log['is_locked']): ?>
                                    <div class="flex flex-col items-center">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase bg-gray-100 px-2 py-0.5 rounded flex items-center gap-1">
                                            <i class="fas fa-lock text-[8px]"></i> Terkunci
                                        </span>
                                        <?php if($log['status'] == 'Alpha'): ?>
                                            <span class="text-[9px] text-red-400 mt-0.5">Otomatis</span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="flex justify-center items-center gap-2">
                                        <button onclick="openLogModal('<?= $log['date'] ?>', '<?= addslashes($log['activity']) ?>', '<?= $log['time_in'] ?>')" 
                                                class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold shadow-sm transition hover:shadow-md active:scale-95 flex items-center gap-1">
                                            <i class="fas fa-pen"></i> <?= empty($log['activity']) ? 'Isi' : 'Edit' ?>
                                        </button>
                                        
                                        <?php if(!empty($log['activity']) && $log['can_reset']): ?>
                                            <button onclick="confirmReset('<?= $log['log_id'] ?>')" 
                                                    class="w-7 h-7 rounded-lg border border-red-200 text-red-400 hover:bg-red-50 hover:text-red-600 transition flex items-center justify-center" 
                                                    title="Reset">
                                                <i class="fas fa-trash-alt text-[10px]"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="logModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeLogModal()"></div>
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl relative z-10 overflow-hidden transform scale-100 transition-all">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex justify-between items-center text-white">
            <h3 class="font-bold text-base"><i class="fas fa-edit mr-2"></i> Update Aktivitas</h3>
            <button onclick="closeLogModal()" class="text-white/70 hover:text-white transition"><i class="fas fa-times"></i></button>
        </div>
        
        <form id="logForm" onsubmit="submitLogbook(event)" class="p-6 space-y-5">
            <input type="hidden" id="modalDate" name="date"> 
            
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Jam Pencatatan</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="far fa-clock"></i></div>
                    <input type="time" id="modalTime" name="time" required 
                           class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
                </div>
            </div>
            
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Deskripsi Kegiatan</label>
                <textarea id="modalActivity" name="activity" rows="5" required placeholder="Jelaskan detail pekerjaan atau aktivitas yang dilakukan..." 
                          class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition resize-none"></textarea>
            </div>

            <button type="submit" id="btnSubmit" class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold shadow-lg shadow-blue-500/30 transition transform active:scale-[0.98]">
                Simpan Data
            </button>
        </form>
    </div>
</div>

<div id="resetModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeResetModal()"></div>
    <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl relative z-10 p-6 text-center transform scale-100 transition-all">
        <div class="w-14 h-14 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4 text-xl">
            <i class="fas fa-eraser"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800 mb-2">Reset Logbook?</h3>
        <p class="text-xs text-gray-500 mb-6 px-4">Deskripsi kegiatan akan dihapus. Anda perlu mengisi ulang keterangan aktivitas.</p>
        
        <div class="flex gap-3">
            <button onclick="closeResetModal()" class="flex-1 py-2.5 rounded-xl border border-gray-200 text-gray-600 font-bold hover:bg-gray-50 transition text-sm">Batal</button>
            <button id="confirmResetBtn" class="flex-1 py-2.5 rounded-xl bg-red-600 text-white font-bold shadow-lg hover:bg-red-700 transition text-sm">Ya, Reset</button>
        </div>
    </div>
</div>

<div id="photoModal" class="hidden fixed inset-0 z-[70] flex items-center justify-center p-4 bg-black/95 backdrop-blur-md" onclick="closePhoto()">
    <div class="relative max-w-2xl w-full" onclick="event.stopPropagation()">
        <button onclick="closePhoto()" class="absolute -top-10 right-0 text-white hover:text-red-400 transition text-2xl">
            <i class="fas fa-times"></i>
        </button>
        <div class="bg-white rounded-lg overflow-hidden shadow-2xl">
            <div class="p-3 bg-gray-100 border-b flex justify-between items-center">
                <span id="proofTitle" class="font-bold text-gray-700 text-xs uppercase">Bukti</span>
                <a id="downloadLink" href="#" download class="text-blue-600 hover:underline text-[10px] font-bold"><i class="fas fa-download"></i> Unduh</a>
            </div>
            <img id="modalImg" src="" class="w-full h-auto max-h-[70vh] object-contain bg-gray-50">
        </div>
    </div>
</div>

<script>
    let logIdToReset = null;

    // --- Modal Form ---
    function openLogModal(dateStr, activity, timeIn) {
        document.getElementById('modalDate').value = dateStr;
        // Decode HTML entities
        const textArea = document.createElement('textarea');
        textArea.innerHTML = activity;
        document.getElementById('modalActivity').value = textArea.value;
        
        // Auto set time jika kosong
        if(!timeIn || timeIn === '-') {
            const now = new Date();
            document.getElementById('modalTime').value = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');
        } else {
            // Ambil HH:MM saja
            document.getElementById('modalTime').value = timeIn.substring(0,5);
        }
        
        document.getElementById('logModal').classList.remove('hidden');
    }

    function closeLogModal() {
        document.getElementById('logModal').classList.add('hidden');
    }

    // --- Submit Logic ---
    function submitLogbook(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmit');
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Menyimpan...';
        btn.disabled = true;

        const formData = new FormData(e.target);

        fetch('<?= BASE_URL ?>/user/submit_logbook', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                location.reload(); 
            } else {
                alert('Gagal: ' + data.message);
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
        })
        .catch(err => {
            alert('Kesalahan jaringan.');
            btn.innerHTML = originalContent;
            btn.disabled = false;
        });
    }

    // --- Reset Logic ---
    function confirmReset(logId) {
        logIdToReset = logId;
        document.getElementById('resetModal').classList.remove('hidden');
    }

    function closeResetModal() {
        document.getElementById('resetModal').classList.add('hidden');
        logIdToReset = null;
    }

    document.getElementById('confirmResetBtn').addEventListener('click', function() {
        if(!logIdToReset) return;
        
        const btn = this;
        const originalText = btn.innerText;
        btn.innerText = 'Memproses...';
        btn.disabled = true;

        const fd = new FormData();
        fd.append('log_id', logIdToReset);

        fetch('<?= BASE_URL ?>/user/reset_logbook', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                location.reload();
            } else {
                alert(data.message);
                closeResetModal();
                btn.innerText = originalText;
                btn.disabled = false;
            }
        })
        .catch(() => {
            alert('Gagal menghubungi server.');
            closeResetModal();
            btn.innerText = originalText;
            btn.disabled = false;
        });
    });

    // --- Viewer Logic ---
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