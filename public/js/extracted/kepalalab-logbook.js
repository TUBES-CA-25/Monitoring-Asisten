
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

        // Fetch Data Unified dari KepalaController
        fetch('<?= BASE_URL ?>/kepalalab/getLogsByUser', { method: 'POST', body: fd })
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

    function updateClock() {
        const now = new Date();
        const dateOptions = { day: '2-digit', month: 'long', year: 'numeric' };
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
            
        const elDate = document.getElementById('liveDate');
        const elTime = document.getElementById('liveTime');
            
        if (elDate) elDate.innerText = now.toLocaleDateString('id-ID', dateOptions);
        if (elTime) elTime.innerText = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':');
    }
        setInterval(updateClock, 1000); updateClock();
