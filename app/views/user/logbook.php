<style>
    /* Animasi masuk yang lebih ringan */
    .animate-enter { animation: fadeInUp 0.4s ease-out forwards; opacity: 0; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    
    /* Status Badge dengan Utility-First approach */
    .badge-base { @apply px-3 py-1.5 rounded-lg text-[10px] font-bold tracking-wide w-full transition-all; }
    .badge-locked { background: #f3f4f6; color: #9ca3af; cursor: not-allowed; }
    .badge-active { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; cursor: pointer; }
    .badge-active:hover { @apply bg-blue-600 text-white shadow-md; }
    .badge-waiting { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; cursor: not-allowed; }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter p-4">
    
    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 origin-bottom-left"></div>
        <div class="relative z-10 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-extrabold">Logbook Asisten</h1>
                <p class="text-blue-100 text-sm">Rekap aktivitas harian laboratorium.</p>
            </div>
            <div class="text-right font-mono">
                <h2 id="liveDate" class="text-xl font-bold"><?= date('d F Y') ?></h2>
                <p class="text-sm opacity-80"><span id="liveTime"><?= date('H:i:s') ?></span> WITA</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-bold border-b">
                    <tr>
                        <th class="p-4">Tanggal</th>
                        <th class="p-4">Datang</th>
                        <th class="p-4">Kegiatan</th>
                        <th class="p-4">Pulang</th>
                        <th class="p-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php 
                    $today = date('Y-m-d');
                    if(!empty($logs)): foreach($logs as $log): 
                        $isToday = ($log['date'] == $today);
                        $hasIn = !empty($log['check_in_time']);
                        $hasOut = !empty($log['check_out_time']);
                        
                        // Logic Status Sederhana
                        $status = ['class' => 'badge-locked', 'text' => 'LOCKED', 'action' => ''];

                        if ($isToday) {
                            if (!$hasIn) {
                                $status = ['class' => 'badge-waiting', 'text' => 'BELUM HADIR', 'action' => ''];
                            } elseif ($hasOut) {
                                $status = ['class' => 'badge-locked', 'text' => 'SELESAI', 'action' => ''];
                            } else {
                                $safeActivity = htmlspecialchars($log['activity_detail'] ?? '', ENT_QUOTES);
                                $safeTime = $log['log_time'] ? date('H:i', strtotime($log['log_time'])) : date('H:i');
                                $status = [
                                    'class' => 'badge-active',
                                    'text' => empty($log['activity_detail']) ? 'ISI LOGBOOK' : 'EDIT',
                                    'action' => "onclick=\"openModal('$safeActivity', '$safeTime')\""
                                ];
                            }
                        }
                    ?>
                    <tr class="hover:bg-gray-50/50 <?= $isToday ? 'bg-blue-50/30' : '' ?>">
                        <td class="p-4 font-bold text-gray-700">
                            <?= date('d M Y', strtotime($log['date'])) ?>
                            <?php if($isToday): ?><span class="ml-2 text-[9px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full">HARI INI</span><?php endif; ?>
                        </td>
                        <td class="p-4 font-mono text-gray-500"><?= $log['check_in_time'] ? date('H:i', strtotime($log['check_in_time'])) : '-' ?></td>
                        <td class="p-4">
                            <?php if($log['activity_detail']): ?>
                                <p class="text-gray-600"><?= $log['activity_detail'] ?></p>
                                <span class="text-[10px] text-gray-400 font-mono"><i class="far fa-clock"></i> <?= date('H:i', strtotime($log['log_time'])) ?></span>
                            <?php else: ?>
                                <span class="text-gray-300 italic">Belum ada catatan</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 font-mono text-gray-500"><?= $log['check_out_time'] ? date('H:i', strtotime($log['check_out_time'])) : '-' ?></td>
                        <td class="p-4">
                            <button <?= $status['action'] ?> class="badge-base <?= $status['class'] ?>">
                                <?= $status['text'] ?>
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

<div id="logModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden transform transition-all" id="modContent">
        <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
            <h3 class="font-bold text-gray-800">Logbook Kegiatan</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-red-500 text-2xl">&times;</button>
        </div>
        
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Waktu</label>
                <input type="time" id="inp_time" class="w-full p-2.5 bg-gray-50 border rounded-xl font-mono focus:ring-2 focus:ring-blue-100 outline-none">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Deskripsi</label>
                <textarea id="inp_activity" rows="4" class="w-full p-2.5 bg-gray-50 border rounded-xl text-sm focus:ring-2 focus:ring-blue-100 outline-none resize-none" placeholder="Tulis kegiatan..."></textarea>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t flex justify-end">
            <button id="btnSave" onclick="submitLogbook()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2 rounded-xl transition shadow-md">
                SIMPAN
            </button>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('logModal');
    
    function openModal(act, time) {
        document.getElementById('inp_activity').value = act;
        document.getElementById('inp_time').value = time;
        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    function submitLogbook() {
        const activity = document.getElementById('inp_activity').value.trim();
        if(!activity) return alert("Deskripsi wajib diisi!");

        const btn = document.getElementById('btnSave');
        const originalText = btn.innerText;
        
        btn.disabled = true;
        btn.innerText = 'MENYIMPAN...';

        const fd = new FormData();
        fd.append('time', document.getElementById('inp_time').value);
        fd.append('activity', activity);

        fetch('<?= BASE_URL ?>/user/submit_logbook', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') location.reload();
                else {
                    alert(data.message);
                    btn.disabled = false;
                    btn.innerText = originalText;
                }
            })
            .catch(err => {
                alert('Terjadi kesalahan sistem.');
                btn.disabled = false;
                btn.innerText = originalText;
            });
    }

    function updateClock() {
        const now = new Date();
        const dateStr = now.toLocaleDateString('id-ID', { day:'numeric', month:'long', year:'numeric' });
        const timeStr = now.toLocaleTimeString('id-ID', { hour12:false }).replace(/\./g, ':');
        document.getElementById('liveDate').innerText = dateStr;
        document.getElementById('liveTime').innerText = timeStr;
    }
    
    setInterval(updateClock, 1000);
    updateClock();
</script>