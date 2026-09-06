
    // --- 1. CLOCK ---
    function updateClock() {
        const now = new Date();
        document.getElementById('liveDate').innerText = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        document.getElementById('liveTime').innerText = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).replace(/\./g, ':');
    }
    setInterval(updateClock, 1000); updateClock();

    // --- 2. SEARCH ---
    function searchTable() {
        const input = document.getElementById('searchUser');
        const filter = input.value.toLowerCase();
        const rows = document.getElementsByClassName('user-row');

        for (let i = 0; i < rows.length; i++) {
            const nameEl = rows[i].querySelector('.user-name');
            const emailEl = rows[i].querySelector('.user-email');
            const nimEl = rows[i].querySelector('.user-nim'); 

            const name = nameEl ? nameEl.innerText.toLowerCase() : '';
            const email = emailEl ? emailEl.innerText.toLowerCase() : '';
            const nim = nimEl ? nimEl.innerText.toLowerCase() : '';

            if (name.includes(filter) || email.includes(filter) || nim.includes(filter)) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    }

    // --- 3. MODAL LOGIC (READ ONLY) ---
    function openDetailModal(userData) {
        const modal = document.getElementById('modalDetail');
        const content = document.getElementById('modalContent');
        
        // Populate Data Dasar
        document.getElementById('detailName').innerText = userData.name;
        document.getElementById('detailEmail').innerText = userData.email;
        document.getElementById('detailRole').innerText = userData.role;
        document.getElementById('detailPosition').innerText = userData.position || '-';
        document.getElementById('detailNim').innerText = userData.nim || '-';
        document.getElementById('detailPhone').innerText = userData.no_telp || '-';
        document.getElementById('detailAddress').innerText = userData.alamat || '-';
        document.getElementById('detailLab').innerText = userData.lab_name || 'Umum';

        // [LOGIC] Handle Visibility Kelas (Hanya untuk User)
        const classContainer = document.getElementById('modalClassContainer');
        if (userData.role === 'User') {
            classContainer.classList.remove('hidden');
            document.getElementById('detailClass').innerText = userData.kelas || '-';
        } else {
            classContainer.classList.add('hidden');
        }

        // Status Profil
        const statusDiv = document.getElementById('detailStatus');
        if (userData.is_completed == 1) {
            statusDiv.className = "w-full px-4 py-2.5 rounded-xl border border-green-200 bg-green-50 text-green-700 text-xs font-bold text-center uppercase";
            statusDiv.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Lengkap / Terverifikasi';
        } else {
            statusDiv.className = "w-full px-4 py-2.5 rounded-xl border border-yellow-200 bg-yellow-50 text-yellow-700 text-xs font-bold text-center uppercase";
            statusDiv.innerHTML = '<i class="fas fa-clock mr-1"></i> Belum Lengkap';
        }

        // Handle Photo
        let photoUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(userData.name)}&background=random&size=500`;
        if (userData.photo_profile && userData.photo_profile.trim() !== "") {
            photoUrl = `<?= BASE_URL ?>/uploads/profile/${userData.photo_profile}`;
        }
        document.getElementById('detailPhoto').src = photoUrl;

        // Show Animation
        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeDetailModal() {
        const modal = document.getElementById('modalDetail');
        const content = document.getElementById('modalContent');
        
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }
