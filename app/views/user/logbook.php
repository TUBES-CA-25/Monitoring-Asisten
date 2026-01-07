<style>
    .animate-enter { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    
    /* Status Badge Styles */
    .badge-locked { background: #f3f4f6; color: #9ca3af; border: 1px solid #e5e7eb; cursor: not-allowed; }
    .badge-active { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; cursor: pointer; transition: all 0.2s; }
    .badge-active:hover { background: #2563eb; color: white; transform: scale(1.05); }
    .badge-waiting { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; cursor: not-allowed; }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter min-h-screen">
    
    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-extrabold">Logbook Asisten</h1>
                <p class="text-blue-100 mt-2 text-sm">Catat aktivitas harianmu selama bertugas di laboratorium.</p>
            </div>
            <div class="mt-4 md:mt-0 text-right">
                <p class="text-xs font-bold text-blue-200 uppercase tracking-widest mb-1">Waktu Sistem</p>
                <h2 id="liveDate" class="text-2xl font-bold font-mono"><?= date('d F Y') ?></h2>
                <p class="text-sm opacity-80">
                    <span id="liveTime"><?= date('H:i:s') ?></span> <span>WITA</span>
                </p>
            </div>
            <!-- <a href="<?= BASE_URL ?>/user/scan" class="group flex items-center gap-3 bg-white text-blue-600 px-6 py-3 rounded-2xl font-bold shadow-lg hover:bg-blue-50 transition transform hover:scale-105">
                <div class="p-2 bg-blue-100 rounded-full group-hover:bg-blue-200 transition">
                    <i class="fas fa-qrcode text-xl"></i>
                </div>
                <span>Scan Presensi</span>
            </a> -->
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                        <th class="p-5 w-40">Tanggal</th>
                        <th class="p-5 w-32">Absen Datang</th>
                        <th class="p-5">Deskripsi Kegiatan</th>
                        <th class="p-5 w-32">Absen Pulang</th>
                        <th class="p-5 w-24 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php 
                    $today = date('Y-m-d');
                    if(!empty($logs)): foreach($logs as $log): 
                        // Logic Status Row
                        $isToday = ($log['date'] == $today);
                        $hasCheckIn = !empty($log['check_in_time']);
                        $hasCheckOut = !empty($log['check_out_time']);
                        
                        // Tentukan Status Tombol Edit
                        $btnClass = 'badge-locked'; 
                        $btnText = 'LOCKED';
                        $onClick = '';

                        if ($isToday) {
                            if (!$hasCheckIn) {
                                $btnClass = 'badge-waiting'; 
                                $btnText = 'BELUM HADIR';
                            } elseif ($hasCheckOut) {
                                $btnClass = 'badge-locked'; 
                                $btnText = 'SELESAI'; // Sudah pulang, tidak bisa edit
                            } else {
                                $btnClass = 'badge-active shadow-lg shadow-blue-500/20'; 
                                $btnText = empty($log['activity_detail']) ? 'ISI LOGBOOK' : 'EDIT';
                                // Siapkan data untuk modal
                                $safeActivity = htmlspecialchars($log['activity_detail'] ?? '', ENT_QUOTES);
                                $safeTime = $log['log_time'] ? date('H:i', strtotime($log['log_time'])) : date('H:i');
                                $onClick = "onclick=\"openLogbookModal('$safeActivity', '$safeTime')\"";
                            }
                        }
                    ?>
                    <tr class="hover:bg-gray-50/50 transition <?= $isToday ? 'bg-blue-50/30' : '' ?>">
                        <td class="p-5 font-bold text-gray-700">
                            <?= date('d M Y', strtotime($log['date'])) ?>
                            <?php if($isToday): ?><span class="ml-2 text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full">HARI INI</span><?php endif; ?>
                        </td>
                        <td class="p-5 font-mono text-gray-600">
                            <?= $log['check_in_time'] ? date('H:i', strtotime($log['check_in_time'])) : '-' ?>
                        </td>
                        <td class="p-5 text-gray-600">
                            <?php if($log['activity_detail']): ?>
                                <?= $log['activity_detail'] ?>
                                <div class="text-[10px] text-gray-400 mt-1 font-mono"><i class="far fa-clock"></i> <?= date('H:i', strtotime($log['log_time'])) ?></div>
                            <?php else: ?>
                                <span class="text-gray-300 italic">- Belum ada catatan -</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-5 font-mono text-gray-600">
                            <?= $log['check_out_time'] ? date('H:i', strtotime($log['check_out_time'])) : '-' ?>
                        </td>
                        <td class="p-5 text-center">
                            <button <?= $onClick ?> class="px-3 py-1.5 rounded-lg text-[10px] font-bold tracking-wide w-full <?= $btnClass ?>">
                                <?= $btnText ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" class="p-10 text-center text-gray-400">Belum ada riwayat kehadiran.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="logModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="modBackdrop" onclick="closeModal()"></div>
    
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="modContent">
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Logbook</h3>
                <p class="text-xs text-gray-500">Isi kegiatan untuk hari ini</p>
            </div>
            <button onclick="closeModal()" class="text-gray-400 hover:text-red-500 transition"><i class="fas fa-times text-xl"></i></button>
        </div>
        
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Jam Kegiatan</label>
                <input type="time" id="inp_time" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl font-bold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-100 focus:bg-white transition">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Deskripsi Kegiatan</label>
                <textarea id="inp_activity" rows="5" placeholder="Contoh: Membantu praktikum Jaringan Komputer, Instalasi Software..." class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:bg-white transition resize-none"></textarea>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
            <button id="btnSave" onclick="submitLogbook()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2.5 rounded-xl shadow-lg shadow-blue-500/30 transition transform hover:scale-105 flex items-center gap-2">
                <i class="fas fa-save"></i> <span>SIMPAN</span>
            </button>
        </div>
    </div>
</div>

<script>
    function openLogbookModal(currentActivity, currentTime) {
        // Set Nilai Form
        document.getElementById('inp_activity').value = currentActivity;
        document.getElementById('inp_time').value = currentTime;

        // Tampilkan Modal Animation
        const m = document.getElementById('logModal');
        m.classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('modBackdrop').classList.remove('opacity-0');
            document.getElementById('modContent').classList.remove('opacity-0', 'scale-95');
            document.getElementById('modContent').classList.add('scale-100');
        }, 10);
    }

    function closeModal() {
        document.getElementById('modBackdrop').classList.add('opacity-0');
        document.getElementById('modContent').classList.add('opacity-0', 'scale-95');
        document.getElementById('modContent').classList.remove('scale-100');
        setTimeout(() => { document.getElementById('logModal').classList.add('hidden'); }, 300);
    }

    function submitLogbook() {
        const time = document.getElementById('inp_time').value;
        const activity = document.getElementById('inp_activity').value;

        if(!activity.trim()) { alert("Deskripsi kegiatan tidak boleh kosong!"); return; }

        // Loading State
        const btn = document.getElementById('btnSave');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
        btn.disabled = true;

        // AJAX Request
        const formData = new FormData();
        formData.append('time', time);
        formData.append('activity', activity);

        fetch('<?= BASE_URL ?>/user/submit_logbook', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                alert('✅ Logbook berhasil disimpan!');
                window.location.reload(); // Reload untuk update tabel
            } else {
                alert('❌ Gagal: ' + data.message);
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            alert('Terjadi kesalahan sistem.');
            btn.innerHTML = originalHtml;
            btn.disabled = false;
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