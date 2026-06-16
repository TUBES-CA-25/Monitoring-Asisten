
    function updateClock() {
        const now = new Date();
        document.getElementById('liveDate').innerText = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        document.getElementById('liveTime').innerText = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).replace(/\./g, ':');
    }
    setInterval(updateClock, 1000); updateClock();

    let currentMode = 'add'; 

    function toggleRoleFields() {
        const role = document.getElementById('inputRole').value;
        const userFields = document.querySelectorAll('.user-field');
        
        if (role === 'User') {
            userFields.forEach(el => el.classList.remove('hidden'));
        } else {
            userFields.forEach(el => el.classList.add('hidden'));
            // Reset nilai agar bersih saat dikirim
            ['inputNim', 'inputClass', 'inputProdi', 'inputLab', 'inputInterest'].forEach(id => {
                const el = document.getElementById(id);
                if(el) el.value = '';
            });
        }
    }

    function openUserModal(mode, data = null) {
        currentMode = mode;
        const modal = document.getElementById('modalUser');
        const content = document.getElementById('modalContent');
        const title = document.getElementById('modalTitle');
        const form = document.getElementById('userForm');
        
        form.reset();
        
        if (mode === 'add') {
            title.innerText = "Tambah Pengguna Baru";
            // document.getElementById('inputRole').value = 'User'; 
            document.getElementById('inputPass').required = true;
            document.getElementById('passReq').classList.remove('hidden');
            document.getElementById('passHint').innerText = "";
        } else {
            title.innerText = "Edit Data Pengguna";
            document.getElementById('inputId').value = data.id;
            document.getElementById('inputName').value = data.name;
            document.getElementById('inputEmail').value = data.email;
            document.getElementById('inputRole').value = data.role;
            
            document.getElementById('inputPosition').value = data.position || '';
            document.getElementById('inputPhone').value = data.no_telp || '';
            document.getElementById('inputAddress').value = data.alamat || '';
            document.getElementById('inputGender').value = data.jenis_kelamin || '';

            if (data.role === 'User') {
                document.getElementById('inputNim').value = data.nim || '';
                document.getElementById('inputClass').value = data.kelas || '';
                document.getElementById('inputProdi').value = data.prodi || '';
                document.getElementById('inputLab').value = data.id_lab || '';
                document.getElementById('inputInterest').value = data.peminatan || '';
            }

            document.getElementById('inputPass').required = false;
            document.getElementById('passReq').classList.add('hidden');
            document.getElementById('passHint').innerText = "(Kosongkan jika tidak ingin mengubah password)";
        }

        toggleRoleFields(); 

        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeUserModal() {
        const modal = document.getElementById('modalUser');
        const content = document.getElementById('modalContent');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    let deleteTargetId = null;

    function triggerDeleteUser(id) {
        deleteTargetId = id;
        const modal = document.getElementById('deleteModal');
        const content = modal.querySelector('div.relative.z-10');
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        const content = modal.querySelector('div.relative.z-10');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            deleteTargetId = null;
        }, 200);
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (deleteTargetId) {
            const btn = this;
            const originalText = btn.innerText;
            btn.innerText = 'Menghapus...';
            btn.disabled = true;

            fetch('<?= BASE_URL ?>/admin/deleteUser?id=' + deleteTargetId)
                .then(res => res.json())
                .then(data => {
                    closeDeleteModal();
                    btn.innerText = originalText;
                    btn.disabled = false;
                    showAlert(data.status, data.title, data.message);
                })
                .catch(err => {
                    closeDeleteModal();
                    btn.innerText = originalText;
                    btn.disabled = false;
                    console.error(err);
                    showAlert('error', 'Error', 'Terjadi kesalahan jaringan.');
                });
        }
    });

    document.getElementById('userForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const url = currentMode === 'add' ? '<?= BASE_URL ?>/admin/addUser' : '<?= BASE_URL ?>/admin/editUser';
        const btn = document.getElementById('btnSave');
        const originalText = btn.innerText;
        
        btn.innerText = 'Menyimpan...';
        btn.disabled = true;

        fetch(url, { method: 'POST', body: formData })
        .then(res => {
            return res.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error("Server Responded with Non-JSON:", text);
                    throw new Error("Respon server tidak valid.");
                }
            });
        })
        .then(data => {
            btn.innerText = originalText;
            btn.disabled = false;
            closeUserModal();
            showAlert(data.status, data.title || 'Info', data.message);
        })
        .catch(err => {
            console.error(err);
            btn.innerText = originalText;
            btn.disabled = false;
            closeUserModal();
            showAlert('error', 'Error Sistem', 'Gagal memproses data.');
        });
    });

    function showAlert(type, title, msg) {
        const modal = document.getElementById('alertModal');
        const icon = document.getElementById('alertIcon');
        const titleEl = document.getElementById('alertTitle');
        const msgEl = document.getElementById('alertMsg');
        const btn = document.getElementById('alertBtn');

        titleEl.innerText = title;
        msgEl.innerText = msg;

        if (type === 'success') {
            icon.className = 'w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 bg-green-100 text-green-600';
            icon.innerHTML = '<i class="fas fa-check text-3xl"></i>';
            btn.className = 'w-full py-3 rounded-xl font-bold text-white shadow-lg transition bg-green-600 hover:bg-green-700';
            // Reload halaman setelah OK jika sukses (agar tabel terupdate)
            btn.onclick = function() { window.location.reload(); };
        } else {
            icon.className = 'w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 bg-red-100 text-red-600';
            icon.innerHTML = '<i class="fas fa-times text-3xl"></i>';
            btn.className = 'w-full py-3 rounded-xl font-bold text-white shadow-lg transition bg-red-600 hover:bg-red-700';
            btn.onclick = function() { modal.classList.add('hidden'); };
        }
        
        modal.classList.remove('hidden');
    }

    function searchTable() {
        const input = document.getElementById('searchUser');
        const filter = input.value.toLowerCase();
        const rows = document.getElementsByClassName('user-row');
        for (let i = 0; i < rows.length; i++) {
            const name = rows[i].querySelector('.user-name').innerText.toLowerCase();
            const email = rows[i].querySelector('.user-email').innerText.toLowerCase();
            rows[i].style.display = (name.includes(filter) || email.includes(filter)) ? "" : "none";
        }
    }
