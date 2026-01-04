<style>
    .animate-enter { animation: fadeInUp 0.5s ease forwards; opacity: 0; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
    .badge-locked { background: #f3f4f6; color: #9ca3af; border: 1px solid #e5e7eb; cursor: not-allowed; }
    .badge-active { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; cursor: pointer; }
    .badge-active:hover { background: #2563eb; color: white; }
    .badge-waiting { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; cursor: not-allowed; }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter p-4">
    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-lg relative overflow-hidden">
        <div class="relative z-10 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-extrabold">Logbook Asisten</h1>
                <p class="text-blue-100 text-sm">Catat aktivitas harianmu di laboratorium.</p>
            </div>
            <div class="text-right">
                <h2 id="liveDate" class="text-xl font-bold font-mono"><?= date('d F Y') ?></h2>
                <p class="text-sm opacity-80"><span id="liveTime"><?= date('H:i:s') ?></span> WITA</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-gray-50 font-bold text-gray-500 uppercase tracking-wider border-b">
                        <th class="p-5">Tanggal</th>
                        <th class="p-5">Masuk</th>
                        <th class="p-5">Kegiatan</th>
                        <th class="p-5">Pulang</th>
                        <th class="p-5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php 
                    $today = date('Y-m-d');
                    if(!empty($logs)): foreach($logs as $log): 
                        $isToday = ($log['date'] == $today);
                        $hasIn = !empty($log['check_in_time']);
                        $hasOut = !empty($log['check_out_time']);
                        
                        $btnClass = 'badge-locked'; $btnText = 'LOCKED'; $onClick = '';

                        if ($isToday) {
                            if (!$hasIn) { $btnClass = 'badge-waiting'; $btnText = 'BELUM HADIR'; }
                            elseif ($hasOut) { $btnText = 'SELESAI'; }
                            else {
                                $btnClass = 'badge-active';
                                $btnText = empty($log['activity_detail']) ? 'ISI LOGBOOK' : 'EDIT';
                                $safeActivity = htmlspecialchars($log['activity_detail'] ?? '', ENT_QUOTES);
                                $safeTime = $log['log_time'] ? date('H:i', strtotime($log['log_time'])) : date('H:i');
                                $onClick = "onclick=\"openLogbookModal('$safeActivity', '$safeTime')\"";
                            }
                        }
                    ?>
                    <tr class="<?= $isToday ? 'bg-blue-50/30' : '' ?>">
                        <td class="p-5 font-bold text-gray-700">
                            <?= date('d M Y', strtotime($log['date'])) ?>
                            <?php if($isToday): ?><span class="ml-2 text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full">HARI INI</span><?php endif; ?>
                        </td>
                        <td class="p-5 font-mono"><?= $log['check_in_time'] ? date('H:i', strtotime($log['check_in_time'])) : '-' ?></td>
                        <td class="p-5">
                            <?= $log['activity_detail'] ?: '<span class="text-gray-300 italic">- Kosong -</span>' ?>
                            <?php if($log['log_time']): ?><div class="text-[10px] text-gray-400 font-mono"><?= date('H:i', strtotime($log['log_time'])) ?></div><?php endif; ?>
                        </td>
                        <td class="p-5 font-mono"><?= $log['check_out_time'] ? date('H:i', strtotime($log['check_out_time'])) : '-' ?></td>
                        <td class="p-5"><button <?= $onClick ?> class="px-3 py-1.5 rounded-lg text-[10px] font-bold w-full <?= $btnClass ?>"><?= $btnText ?></button></td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" class="p-10 text-center text-gray-400">Belum ada riwayat.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="logModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
            <h3 class="font-bold">Logbook Kegiatan</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-red-500 text-xl">&times;</button>
        </div>
        <div class="p-6 space-y-4">
            <input type="time" id="inp_time" class="w-full p-3 bg-gray-50 border rounded-xl font-bold">
            <textarea id="inp_activity" rows="4" placeholder="Deskripsi kegiatan..." class="w-full p-3 bg-gray-50 border rounded-xl text-sm resize-none"></textarea>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t flex justify-end">
            <button id="btnSave" onclick="submitLogbook()" class="bg-blue-600 text-white font-bold px-6 py-2 rounded-xl">SIMPAN</button>
        </div>
    </div>
</div>

<script>
    const m = document.getElementById('logModal');
    function openLogbookModal(act, time) {
        document.getElementById('inp_activity').value = act;
        document.getElementById('inp_time').value = time;
        m.classList.remove('hidden');
    }

    function closeModal() { m.classList.add('hidden'); }

    function submitLogbook() {
        const time = document.getElementById('inp_time').value;
        const activity = document.getElementById('inp_activity').value;
        if(!activity.trim()) return alert("Isi kegiatan!");

        const btn = document.getElementById('btnSave');
        btn.disabled = true; btn.innerText = 'Loading...';

        const fd = new FormData();
        fd.append('time', time); fd.append('activity', activity);

        fetch('<?= BASE_URL ?>/user/submit_logbook', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') { location.reload(); }
            else { alert(data.message); btn.disabled = false; btn.innerText = 'SIMPAN'; }
        }).catch(() => { alert('Error'); btn.disabled = false; });
    }

    function updateClock() {
        const now = new Date();
        document.getElementById('liveDate').innerText = now.toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'});
        document.getElementById('liveTime').innerText = now.toLocaleTimeString('id-ID', {hour12:false}).replace(/\./g, ':');
    }
    setInterval(updateClock, 1000);
</script>