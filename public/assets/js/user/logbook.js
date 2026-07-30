// [PENTING] var (bukan let): script ini dimuat ulang setiap kali halaman
// logbook dikunjungi via AJAX navigation — let top-level akan melempar
// "Identifier ... has already been declared" pada kunjungan kedua.
var logIdToReset = null;

// --- Modal Form ---
function openLogModal(dateStr, activity, timeIn, logId) {
    document.getElementById('modalDate').value = dateStr;
    const logIdInput = document.getElementById('modalLogId');
    if (logIdInput) logIdInput.value = logId;

    // Decode HTML entities
    const textArea = document.createElement('textarea');
    textArea.innerHTML = activity;
    document.getElementById('modalActivity').value = textArea.value;

    // Auto set time jika kosong
    if (!timeIn || timeIn === '-') {
        const now = new Date();
        document.getElementById('modalTime').value = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    } else {
        // Ambil HH:MM saja
        document.getElementById('modalTime').value = timeIn.substring(0, 5);
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

    fetch(`${window.APP_CONFIG.BASE_URL}/user/submit_logbook`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            location.reload();
        } else {
            showCustomAlert('error', 'Gagal Menyimpan', data.message);
            btn.innerHTML = originalContent;
            btn.disabled = false;
        }
    })
    .catch(err => {
        showCustomAlert('error', 'Kesalahan Jaringan', 'Tidak dapat menghubungi server. Periksa koneksi Anda.');
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

document.addEventListener('DOMContentLoaded', () => {
    const confirmResetBtn = document.getElementById('confirmResetBtn');
    if (confirmResetBtn) {
        confirmResetBtn.addEventListener('click', function() {
            if (!logIdToReset) return;

            const btn = this;
            const originalText = btn.innerText;
            btn.innerText = 'Memproses...';
            btn.disabled = true;

            const fd = new FormData();
            fd.append('log_id', logIdToReset);

            fetch(`${window.APP_CONFIG.BASE_URL}/user/reset_logbook`, { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    location.reload();
                } else {
                    showCustomAlert('error', 'Gagal', data.message);
                    closeResetModal();
                    btn.innerText = originalText;
                    btn.disabled = false;
                }
            })
            .catch(() => {
                showCustomAlert('error', 'Kesalahan Koneksi', 'Gagal menghubungi server.');
                closeResetModal();
                btn.innerText = originalText;
                btn.disabled = false;
            });
        });
    }

    updateClock();
    setInterval(updateClock, 1000);
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

function updateClock() {
    const now = new Date();
    const dateOptions = { day: '2-digit', month: 'long', year: 'numeric' };
    const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };

    const elDate = document.getElementById('liveDate');
    const elTime = document.getElementById('liveTime');

    if (elDate) elDate.innerText = now.toLocaleDateString('id-ID', dateOptions);
    if (elTime) elTime.innerText = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':');
}
