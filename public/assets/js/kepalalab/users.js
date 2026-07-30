document.addEventListener('DOMContentLoaded', () => {
    initClock();
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

// [BARU] Pencarian sebelumnya hanya menyaring baris yang SUDAH dimuat di
// halaman saat ini (client-side, per-halaman pagination) — sekarang memakai
// form GET penuh (search/role/jabatan/angkatan) yang diproses server, sama
// seperti admin/users.php, sehingga hasilnya konsisten dengan pagination.

// [BARU] Admin & Kepala Lab tidak memiliki jabatan/angkatan asisten — kedua
// filter tsb dinonaktifkan otomatis saat salah satu role tsb dipilih.
// <select disabled> otomatis tidak ikut terkirim saat form di-submit.
window.handleUserRoleFilterChange = function (sel) {
    const jabatanSel  = document.getElementById('filterJabatanSelect');
    const angkatanSel = document.getElementById('filterAngkatanSelect');
    const lock = (sel.value === 'Admin' || sel.value === 'Kepala Lab');

    [jabatanSel, angkatanSel].forEach((el) => {
        if (!el) return;
        el.disabled = lock;
        if (lock) el.value = '';
    });

    sel.form.submit();
};

function openDetailModal(userData) {
    const modal   = document.getElementById('modalDetail');
    const content = document.getElementById('modalContent');
    if (!modal || !content) return;

    const val = (v) => (v && String(v).trim() !== '') ? v : '-';
    const genderMap = { 'L': 'Laki-laki', 'P': 'Perempuan' };

    setText('detailName',     val(userData.name));
    setText('detailEmail',    val(userData.email));
    setText('detailRole',     val(userData.role));
    setText('detailPosition', val(userData.position || userData.jabatan));
    setText('detailPhone',    val(userData.no_telp));
    setText('detailAddress',  val(userData.alamat));
    setText('detailGender',   userData.jenis_kelamin ? (genderMap[userData.jenis_kelamin] || userData.jenis_kelamin) : '-');
    setText('detailLab',      val(userData.lab_name));

    // Status profil (is_completed)
    renderStatus(userData.is_completed);

    // Status akun (ACTIVE / INACTIVE)
    const accEl = document.getElementById('detailAccountStatus');
    if (accEl) {
        const isActive = !userData.status_account || userData.status_account === 'ACTIVE';
        accEl.className = 'w-full px-4 py-2.5 rounded-xl border text-xs font-bold text-center uppercase '
            + (isActive ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200');
        accEl.innerHTML = isActive
            ? '<i class="fas fa-check-circle mr-1"></i>Aktif'
            : '<i class="fas fa-ban mr-1"></i>Nonaktif';
    }

    // Tanggal bergabung
    if (userData.created_at) {
        const d = new Date(userData.created_at);
        setText('detailCreatedAt', d.toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'}));
    } else {
        setText('detailCreatedAt', '-');
    }

    const isUser = (userData.role === 'User');
    ['modalNimContainer','modalProdiContainer','modalClassContainer',
     'modalAngkatanContainer','modalPeminatanContainer'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = isUser ? '' : 'none';
    });
    const labC = document.getElementById('modalLabContainer');
    if (labC) labC.style.display = userData.role === 'Admin' ? 'none' : '';

    if (isUser) {
        setText('detailNim',       val(userData.nim));
        setText('detailProdi',     val(userData.prodi));
        setText('detailClass',     val(userData.kelas));
        setText('detailAngkatan',  val(userData.angkatan));
        setText('detailPeminatan', val(userData.peminatan));
    }

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
        photoUrl = `${window.APP_CONFIG.BASE_URL}/uploads/profile/${userData.photo_profile}`;
    }

    img.src = photoUrl;
}
