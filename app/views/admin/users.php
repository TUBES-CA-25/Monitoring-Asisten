<style>
    .animate-enter { animation: fadeInUp 0.5s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    /* Hide scrollbar for modal content */
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-12">

    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0 text-center md:text-left">
                <h1 class="text-3xl font-extrabold">Manajemen Pengguna</h1>
                <p class="text-blue-100 mt-2 text-sm">Kelola akun Asisten, Admin, dan Kepala Lab.</p>
                <button onclick="openUserModal('add')" class="mt-6 bg-white text-blue-600 px-6 py-3 rounded-xl font-bold shadow-lg hover:bg-indigo-50 transition transform hover:scale-105 flex items-center gap-2 mx-auto md:mx-0">
                    <i class="fas fa-plus-circle"></i> Tambah User Baru
                </button>
            </div>
            <div class="text-center md:text-right bg-white/10 p-3 rounded-2xl backdrop-blur-sm border border-white/20">
                <p class="text-[10px] font-bold text-blue-100 uppercase tracking-widest mb-1">Waktu Sistem</p>
                <h2 id="liveDate" class="text-xl font-bold font-mono"><?= date('d F Y') ?></h2>
                <span id="liveTime" class="bg-blue-900/30 px-2 py-0.5 rounded text-sm font-mono"><?= date('H:i:s') ?> WITA</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4">
            <h3 class="font-bold text-gray-700 uppercase tracking-wide text-sm">Daftar User</h3>
            
            <div class="relative w-full sm:w-72">
                <i class="fas fa-search absolute left-4 top-3.5 text-gray-400 text-xs"></i>
                <input type="text" id="searchUser" onkeyup="searchTable()" placeholder="Cari nama, email, atau NIM..." class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition shadow-sm">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-400 uppercase">
                    <tr>
                        <th class="p-6">Pengguna</th>
                        <th class="p-6">Role & Jabatan</th>
                        <th class="p-6">Kontak</th>
                        <th class="p-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach($users_list as $u): 
                        $isVerified = $u['is_completed'] == 1;
                        $statusBadge = $isVerified 
                            ? '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700 border border-green-200"><i class="fas fa-check-circle mr-1"></i>Verifikasi</span>'
                            : '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-yellow-100 text-yellow-700 border border-yellow-200"><i class="fas fa-clock mr-1"></i>Pending</span>';
                    ?>
                    <tr class="group hover:bg-blue-50/30 transition user-row">
                        <td class="p-6">
                            <div class="flex items-center gap-4">
                                <?php 
                                    $photoName = $u['photo_profile'] ?? '';
                                    $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($u['name']) . "&background=random&bold=true";
                                    if (!empty($photoName) && file_exists('uploads/profile/' . $photoName)) {
                                        $avatarUrl = BASE_URL . '/uploads/profile/' . $photoName;
                                    }
                                ?>
                                <img src="<?= $avatarUrl ?>" class="w-10 h-10 rounded-full object-cover border border-gray-200 shadow-sm">
                                <div>
                                    <div class="font-bold text-gray-800 text-sm user-name"><?= $u['name'] ?></div>
                                    <div class="mt-1"><?= $statusBadge ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="p-6">
                            <div class="text-sm font-bold text-gray-700 mb-1"><?= $u['position'] ?? 'Anggota' ?></div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase <?= $u['role']=='Admin'?'bg-purple-50 text-purple-600 border-purple-100':($u['role']=='Kepala Lab'?'bg-red-50 text-red-600 border-red-100':'bg-blue-50 text-blue-600 border-blue-100') ?>">
                                <?= $u['role'] ?>
                            </span>
                            <?php if ($u['role'] == 'User' && !empty($u['class'])): ?>
                                <div class="mt-2 flex items-center gap-1.5">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase">Kelas:</span>
                                    <span class="text-[10px] font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded border border-gray-200 font-mono">
                                        <?= $u['class'] ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="p-6">
                            <div class="text-sm text-gray-600 user-email"><i class="fas fa-envelope text-gray-300 mr-2"></i><?= $u['email'] ?></div>
                            <div class="text-xs text-gray-500 mt-1"><i class="fas fa-phone text-gray-300 mr-2"></i><?= $u['no_telp'] ?? '-' ?></div>
                        </td>
                        <td class="p-6 text-center">
                            <?php if($u['id'] != $_SESSION['user_id']): ?>
                                <div class="flex justify-center gap-2">
                                    <button onclick='openUserModal("edit", <?= json_encode($u) ?>)' class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition flex items-center justify-center"><i class="fas fa-edit"></i></button>
                                    <button onclick="triggerDeleteUser(<?= $u['id'] ?>)" class="w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition flex items-center justify-center"><i class="fas fa-trash"></i></button>
                                </div>
                            <?php else: ?>
                                <span class="text-[10px] text-gray-400 italic">Akun Anda</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalUser" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeUserModal()"></div>
    <div class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl relative z-10 overflow-hidden transform scale-95 transition-all duration-300 flex flex-col max-h-[90vh]" id="modalContent">
        
        <div class="bg-white p-6 border-b border-gray-100 flex justify-between items-center shrink-0">
            <div>
                <h3 class="font-bold text-xl text-gray-800" id="modalTitle">Tambah Pengguna</h3>
                <p class="text-xs text-gray-500">Isi data akun dengan lengkap.</p>
            </div>
            <button onclick="closeUserModal()" class="w-8 h-8 rounded-full bg-gray-50 hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-red-500 transition"><i class="fas fa-times"></i></button>
        </div>

        <div class="p-8 overflow-y-auto custom-scrollbar">
            <form id="userForm" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="id_user" id="inputId">
                
                <div class="bg-blue-50/50 p-5 rounded-2xl border border-blue-100">
                    <h4 class="text-xs font-bold text-blue-600 uppercase mb-4 tracking-widest">Informasi Akun (Wajib)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="inputName" required class="w-full p-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none font-bold">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Email <span class="text-red-500">*</span></label>
                            <input type="email" name="email" id="inputEmail" required class="w-full p-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Password <span class="text-red-500" id="passReq">*</span></label>
                            <input type="password" name="password" id="inputPass" class="w-full p-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none" placeholder="Minimal 6 karakter">
                            <p class="text-[10px] text-gray-400 mt-1" id="passHint"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Role Akun <span class="text-red-500">*</span></label>
                            <select name="role" id="inputRole" onchange="toggleRoleFields()" required class="w-full p-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none cursor-pointer">
                                <option value="User">User (Asisten)</option>
                                <option value="Admin">Admin</option>
                                <option value="Kepala Lab">Kepala Lab</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Jenis Kelamin</label>
                            <select name="gender" id="inputGender" class="w-full p-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none cursor-pointer">
                                <option value="" disabled selected>-- Pilih --</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase mb-4 tracking-widest border-b pb-2">Detail Profil</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Jabatan</label>
                            <select name="position" id="inputPosition" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none cursor-pointer">
                                <option value="" disabled selected>-- Pilih Jabatan --</option>
                                <option value="Kepala Lab">Kepala Lab</option>
                                <option value="Laboran">Laboran</option>
                                <option value="Administrator">Koordinator Asisten</option>
                                <option value="Asisten 1">Asisten 1</option>
                                <option value="Asisten 2">Asisten 2</option>
                                <option value="Asisten Pendamping">Asisten Pendamping</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">No. WhatsApp</label>
                            <input type="tel" name="phone" id="inputPhone" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none font-mono">
                        </div>

                        <div class="user-field md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">NIM</label>
                                <input type="text" name="nim" id="inputNim" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">Kelas</label>
                                <input type="text" name="class" id="inputClass" placeholder="Contoh: TI-3A" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none font-mono uppercase">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">Program Studi</label>
                                <select name="prodi" id="inputProdi" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none cursor-pointer">
                                <option value="" prodi>-- Pilih Prodi --</option>
                                <option value="Sistem Informasi">Sistem Informasi</option>
                                <option value="Teknik Informatika">Teknik Informatika</option>
                                </select>
                            
                            </div>

                            <div>

                                <label class="block text-xs font-bold text-gray-500 mb-1">Laboratorium</label>
                                <select name="lab_id" id="inputLab" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none cursor-pointer">
                                    <option value="">-- Pilih Lab --</option>
                                    <?php foreach($labs as $lab): ?>
                                        <option value="<?= $lab['id_lab'] ?>"><?= $lab['nama_lab'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 mb-1">Peminatan</label>
                                <select name="interest" id="inputInterest" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none cursor-pointer">
                                    <option value="">-- Pilih Peminatan --</option>
                                    <option value="RPL">Rekayasa Perangkat Lunak (RPL)</option>
                                    <option value="Jaringan">Jaringan Komputer</option>
                                    <option value="IoT">Internet of Things (IoT)</option>
                                    <option value="Multimedia">Multimedia</option>
                                    <option value="AI">Artificial Intelligence (AI)</option>
                                </select>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 mb-1">Alamat Domisili</label>
                            <textarea name="address" id="inputAddress" rows="2" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none"></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 mb-1">Foto Profil (Opsional)</label>
                            <input type="file" name="photo" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-6 border-t border-gray-100">
                    <button type="button" onclick="closeUserModal()" class="px-6 py-2.5 rounded-xl border border-gray-300 text-gray-600 font-bold text-sm hover:bg-gray-50 transition">Batal</button>
                    <button type="submit" id="btnSave" class="px-6 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-sm hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="deleteModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeDeleteModal()"></div>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm relative z-10 p-6 text-center transform scale-100 transition-all">
        <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 bg-red-100 text-red-600">
            <i class="fas fa-exclamation-triangle text-3xl"></i>
        </div>
        <h3 class="text-xl font-extrabold text-gray-800 mb-2">Hapus Pengguna?</h3>
        <p class="text-sm text-gray-500 mb-6">Tindakan ini tidak dapat dibatalkan. Semua data terkait pengguna ini akan hilang.</p>
        <div class="flex gap-3">
            <button onclick="closeDeleteModal()" class="flex-1 py-2.5 rounded-xl border border-gray-300 text-gray-600 font-bold hover:bg-gray-50 transition">Batal</button>
            <button id="confirmDeleteBtn" class="flex-1 py-2.5 rounded-xl bg-red-600 text-white font-bold hover:bg-red-700 shadow-lg transition">Ya, Hapus</button>
        </div>
    </div>
</div>

<div id="alertModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm relative z-10 p-6 text-center transform scale-100 transition-all">
        <div id="alertIcon" class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4"></div>
        <h3 id="alertTitle" class="text-xl font-extrabold text-gray-800 mb-2"></h3>
        <p id="alertMsg" class="text-sm text-gray-500 mb-6"></p>
        <button onclick="window.location.reload()" class="w-full py-3 rounded-xl font-bold text-white shadow-lg transition" id="alertBtn">OK</button>
    </div>
</div>

<script>
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
            document.getElementById('inputRole').value = 'User'; 
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
</script>
