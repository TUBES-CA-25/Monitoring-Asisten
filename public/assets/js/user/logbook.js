let logIdToReset = null;
let config = {};

document.addEventListener('DOMContentLoaded', () => {
    readConfig();
    bindResetButton();
});

function readConfig() {
    const el = document.getElementById('logbook-config');
    if (!el) return;

    try {
        config = JSON.parse(el.textContent);
    } catch (e) {
        console.error('Logbook config JSON invalid', e);
    }
}

function openLogModal(dateStr, activity, timeIn, logId) {
    document.getElementById('modalDate').value = dateStr;
    document.getElementById('modalLogId').value = logId || '';

    const textarea = document.createElement('textarea');
    textarea.innerHTML = activity || '';
    document.getElementById('modalActivity').value = textarea.value;

    const timeInput = document.getElementById('modalTime');
    if (!timeIn || timeIn === '-') {
        const now = new Date();
        timeInput.value =
            now.getHours().toString().padStart(2, '0') + ':' +
            now.getMinutes().toString().padStart(2, '0');
    } else {
        timeInput.value = timeIn.substring(0, 5);
    }

    document.getElementById('logModal').classList.remove('hidden');
}

function closeLogModal() {
    document.getElementById('logModal').classList.add('hidden');
}

function submitLogbook(e) {
    e.preventDefault();

    const form = e.target;
    const btn = document.getElementById('btnSubmit');
    const originalHTML = btn.innerHTML;

    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Menyimpan...';
    btn.classList.add('btn-loading');

    fetch(config.submitUrl, {
        method: 'POST',
        body: new FormData(form)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            window.location.reload();
        } else {
            alert(data.message || 'Gagal menyimpan data.');
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-loading');
        }
    })
    .catch(() => {
        alert('Kesalahan jaringan.');
        btn.innerHTML = originalHTML;
        btn.classList.remove('btn-loading');
    });
}

function confirmReset(logId) {
    logIdToReset = logId;
    document.getElementById('resetModal').classList.remove('hidden');
}

function closeResetModal() {
    logIdToReset = null;
    document.getElementById('resetModal').classList.add('hidden');
}

function bindResetButton() {
    const btn = document.getElementById('confirmResetBtn');
    if (!btn) return;

    btn.addEventListener('click', () => {
        if (!logIdToReset) return;

        const originalText = btn.innerText;
        btn.innerText = 'Memproses...';
        btn.classList.add('btn-loading');

        const fd = new FormData();
        fd.append('log_id', logIdToReset);

        fetch(config.resetUrl, {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.reload();
            } else {
                alert(data.message || 'Reset gagal.');
                restoreResetButton(originalText);
            }
        })
        .catch(() => {
            alert('Gagal menghubungi server.');
            restoreResetButton(originalText);
        });
    });
}

function restoreResetButton(text) {
    const btn = document.getElementById('confirmResetBtn');
    btn.innerText = text;
    btn.classList.remove('btn-loading');
    closeResetModal();
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

window.openLogModal = openLogModal;
window.closeLogModal = closeLogModal;
window.submitLogbook = submitLogbook;
window.confirmReset = confirmReset;
window.closeResetModal = closeResetModal;
window.viewEvidence = viewEvidence;
window.closePhoto = closePhoto;
