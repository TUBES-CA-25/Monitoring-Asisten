document.addEventListener('DOMContentLoaded', () => {
    initClock();
    initSearch();
});

function initClock() {
    const updateClock = () => {
        const now = new Date();
        const dateEl = document.getElementById('liveDate');
        const timeEl = document.getElementById('liveTime');

        if (!dateEl || !timeEl) return;

        dateEl.innerText = now.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });

        timeEl.innerText = now.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        }).replace(/\./g, ':');
    };

    updateClock();
    setInterval(updateClock, 1000);
}

function initSearch() {
    const input = document.getElementById('searchUser');
    if (!input) return;

    input.addEventListener('keyup', searchTable);
}

function searchTable() {
    const input = document.getElementById('searchUser');
    const filter = input.value.toLowerCase();
    const rows = document.getElementsByClassName('user-row');

    Array.from(rows).forEach(row => {
        const name = row.querySelector('.user-name')?.innerText.toLowerCase() || '';
        const email = row.querySelector('.user-email')?.innerText.toLowerCase() || '';
        const nim = row.querySelector('.user-nim')?.innerText.toLowerCase() || '';

        const visible =
            name.includes(filter) ||
            email.includes(filter) ||
            nim.includes(filter);

        row.style.display = visible ? '' : 'none';
    });
}

function openDetailModal(userData) {
    const modal = document.getElementById('modalDetail');
    const content = document.getElementById('modalContent');

    if (!modal || !content) return;

    setText('detailName', userData.name);
    setText('detailEmail', userData.email);
    setText('detailRole', userData.role);
    setText('detailPosition', userData.position || '-');
    setText('detailNim', userData.nim || '-');
    setText('detailPhone', userData.no_telp || '-');
    setText('detailAddress', userData.alamat || '-');
    setText('detailLab', userData.lab_name || 'Umum');

    const classBox = document.getElementById('modalClassContainer');
    if (userData.role === 'User') {
        classBox.classList.remove('hidden');
        setText('detailClass', userData.kelas || '-');
    } else {
        classBox.classList.add('hidden');
    }

    renderStatus(userData.is_completed);

    renderPhoto(userData);

    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeDetailModal() {
    const modal = document.getElementById('modalDetail');
    const content = document.getElementById('modalContent');

    if (!modal || !content) return;

    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');

    setTimeout(() => modal.classList.add('hidden'), 300);
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.innerText = value;
}

function renderStatus(isCompleted) {
    const el = document.getElementById('detailStatus');
    if (!el) return;

    if (parseInt(isCompleted) === 1) {
        el.className = "w-full px-4 py-2.5 rounded-xl border border-green-200 bg-green-50 text-green-700 text-xs font-bold text-center uppercase";
        el.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Lengkap / Terverifikasi';
    } else {
        el.className = "w-full px-4 py-2.5 rounded-xl border border-yellow-200 bg-yellow-50 text-yellow-700 text-xs font-bold text-center uppercase";
        el.innerHTML = '<i class="fas fa-clock mr-1"></i> Belum Lengkap';
    }
}

function renderPhoto(userData) {
    const img = document.getElementById('detailPhoto');
    if (!img) return;

    let photoUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(userData.name)}&background=random&size=500`;

    if (userData.photo_profile && userData.photo_profile.trim() !== "") {
        photoUrl = `${BASE_URL}/uploads/profile/${userData.photo_profile}`;
    }

    img.src = photoUrl;
}
