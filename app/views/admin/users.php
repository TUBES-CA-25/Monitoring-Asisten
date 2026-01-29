<style>
    .animate-enter { animation: fadeInUp 0.5s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-12">

    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0 text-center md:text-left">
                <h1 class="text-3xl font-extrabold">Manajemen Pengguna</h1>
                <p class="text-blue-100 mt-2 text-sm">Kelola akun Asisten, Admin, dan Super Admin.</p>
                <button onclick="openUserModal('add')" class="mt-6 bg-white text-blue-600 px-6 py-3 rounded-xl font-bold shadow-lg hover:bg-indigo-50 transition transform hover:scale-105 flex items-center gap-2 mx-auto md:mx-0">
                    <i class="fas fa-user-plus"></i> <span>Tambah User</span>
                </button>
            </div>
            <div class="text-center md:text-right bg-white/10 p-3 rounded-2xl backdrop-blur-sm border border-white/20">
                <p class="text-[10px] font-bold text-blue-100 uppercase tracking-widest mb-1">Waktu Sistem</p>
                <h2 id="liveDate" class="text-xl font-bold font-mono"><?= date('d F Y') ?></h2>
                <p class="text-sm opacity-90 font-mono mt-1">
                    <span id="liveTime" class="bg-blue-900/30 px-2 py-0.5 rounded"><?= date('H:i:s') ?></span> WITA
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4">
            <h3 class="font-bold text-gray-700 uppercase tracking-wide text-sm">Daftar Akun Aktif</h3>
            <div class="relative w-full sm:w-72">
                <i class="fas fa-search absolute left-4 top-3.5 text-gray-400 text-xs"></i>
                <input type="text" id="searchUser" onkeyup="searchTable()" placeholder="Cari nama atau email..." class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left" id="userTable">
                <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-400 uppercase tracking-wider">
                    <tr>
                        <th class="p-6">Profil</th>
                        <th class="p-6">Jabatan & Role</th>
                        <th class="p-6">Kontak</th>
                        <th class="p-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach($users_list as $u): ?>
                    <tr class="group hover:bg-blue-50/30 transition duration-200 user-row">
                        <td class="p-6">
                            <div class="flex items-center gap-4">
                                <?php 
                                    $photoName = $u['photo_profile'] ?? '';
                                    $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($u['name']) . "&background=random&bold=true";
                                    
                                    if (!empty($photoName) && file_exists('uploads/profile/' . $photoName)) {
                                        $avatarUrl = BASE_URL . '/uploads/profile/' . $photoName;
                                    }
                                ?>
                                <img src="<?= $avatarUrl ?>" class="w-12 h-12 rounded-full border-2 border-white shadow-sm object-cover bg-gray-100">
                                <div>
                                    <div class="font-bold text-gray-800 text-sm user-name"><?= $u['name'] ?></div>
                                    <?php if ($u['role'] == 'User'): ?>
                                        <div class="text-[10px] text-gray-400 font-mono mt-0.5 bg-gray-100 px-2 py-0.5 rounded inline-block">NIM: <?= $u['nim'] ?? '-' ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="p-6">
                            <div class="text-sm font-bold text-gray-700 mb-1"><?= $u['position'] ?? 'Anggota' ?></div>
                            <span class="inline-block px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border
                                <?= $u['role']=='Admin'?'bg-purple-50 text-purple-600 border-purple-100':($u['role']=='Super Admin'?'bg-red-50 text-red-600 border-red-100':'bg-blue-50 text-blue-600 border-blue-100') ?>">
                                <?= $u['role'] ?>
                            </span>
                        </td>
                        <td class="p-6">
                            <div class="flex items-center gap-2 text-sm text-gray-600 mb-1 user-email">
                                <i class="fas fa-envelope text-gray-300 text-xs"></i> <?= $u['email'] ?>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                <i class="fas fa-phone text-gray-300 text-[10px]"></i> <?= $u['no_telp'] ?? '-' ?>
                            </div>
                        </td>
                        <td class="p-6">
                            <div class="flex items-center justify-center gap-2 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                <button onclick='openUserModal("edit", <?= json_encode($u) ?>)' class="w-9 h-9 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-blue-600 hover:border-blue-300 hover:shadow-md transition flex items-center justify-center" title="Edit">
                                    <i class="fas fa-pen text-xs"></i>
                                </button>
                                <button onclick="triggerDeleteUser(<?= $u['id'] ?>)" class="w-9 h-9 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-red-600 hover:border-red-300 hover:shadow-md transition flex items-center justify-center" title="Hapus">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalUser" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm transition-opacity" onclick="closeUserModal()"></div>
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col max-h-[90vh]" id="modalContent">
        
        <div class="bg-white p-6 border-b border-gray-100 flex justify-between items-center shrink-0">
            <div>
                <h3 class="font-bold text-xl text-gray-800" id="modalTitle">User Baru</h3>
                <p class="text-xs text-gray-500" id="modalSubtitle">Tambahkan akun untuk akses sistem.</p>
            </div>
            <button onclick="closeUserModal()" class="w-8 h-8 rounded-full bg-gray-50 hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-red-500 transition"><i class="fas fa-times"></i></button>
        </div>

        <form id="userForm" action="<?= BASE_URL ?>/admin/addUser" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 overflow-y-auto custom-scrollbar">
            <input type="hidden" name="id_user" id="inputId">

            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-full bg-gray-100 border border-gray-200 overflow-hidden shrink-0">
                    <img id="previewPhoto" src="" class="w-full h-full object-cover hidden">
                    <div id="defaultPhotoIcon" class="w-full h-full flex items-center justify-center text-gray-400">
                        <i class="fas fa-camera text-xl"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Foto Profil</label>
                    <input type="file" name="photo" id="inputPhoto" accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition" onchange="previewImage(this)">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Nama Lengkap</label>
                <input type="text" name="name" id="inputName" required class="w-full px-4 py-2.5 rounded-xl bg-gray-50 border border-gray-200 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition text-sm">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Role</label>
                    <select name="role" id="inputRole" class="w-full px-4 py-2.5 rounded-xl bg-gray-50 border border-gray-200 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition text-sm cursor-pointer" onchange="toggleNimField()">
                        <option value="User">Asisten (User)</option>
                        <option value="Admin">Admin</option>
                        <option value="Super Admin">Super Admin</option>
                    </select>
                </div>
                <div id="nimContainer">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">NIM / ID</label>
                    <input type="text" name="nim" id="inputNim" class="w-full px-4 py-2.5 rounded-xl bg-gray-50 border border-gray-200 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition text-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">No. Telepon</label>
                    <input type="text" name="phone" id="inputPhone" class="w-full px-4 py-2.5 rounded-xl bg-gray-50 border border-gray-200 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Jabatan</label>
                    <input type="text" name="position" id="inputPos" class="w-full px-4 py-2.5 rounded-xl bg-gray-50 border border-gray-200 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition text-sm" placeholder="Anggota">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Alamat</label>
                <textarea name="address" id="inputAddress" rows="2" class="w-full px-4 py-2.5 rounded-xl bg-gray-50 border border-gray-200 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition text-sm resize-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Email</label>
                <input type="email" name="email" id="inputEmail" required class="w-full px-4 py-2.5 rounded-xl bg-gray-50 border border-gray-200 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Password <span id="passHint" class="font-normal normal-case text-gray-400 ml-1"></span></label>
                <input type="password" name="password" id="inputPass" class="w-full px-4 py-2.5 rounded-xl bg-gray-50 border border-gray-200 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition text-sm" placeholder="********">
            </div>

            <div class="pt-2 flex gap-3">
                <button type="button" onclick="closeUserModal()" class="flex-1 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold hover:bg-gray-50 transition text-sm">Batal</button>
                <button type="submit" class="flex-1 py-3 rounded-xl bg-blue-600 text-white font-bold shadow-lg hover:bg-blue-700 transition text-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="customAlertModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="alertBackdrop"></div>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative z-10 overflow-hidden transform scale-90 opacity-0 transition-all duration-300 flex flex-col items-center p-6 text-center" id="alertContent">
        <div id="alertIconBg" class="w-16 h-16 rounded-full flex items-center justify-center mb-4">
            <i id="alertIcon" class="fas text-3xl"></i>
        </div>
        <h3 id="alertTitle" class="text-xl font-extrabold text-gray-800 mb-2"></h3>
        <p id="alertMessage" class="text-sm text-gray-500 mb-6 px-2"></p>
        <button onclick="closeCustomAlert()" class="w-full py-3 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02]" id="alertBtn">OK</button>
    </div>
</div>

<div id="customConfirmModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="confirmBackdrop"></div>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative z-10 overflow-hidden transform scale-90 opacity-0 transition-all duration-300 flex flex-col items-center p-6 text-center" id="confirmContent">
        <div class="w-16 h-16 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center mb-4">
            <i class="fas fa-exclamation-triangle text-3xl"></i>
        </div>
        <h3 class="text-xl font-extrabold text-gray-800 mb-2">Hapus User?</h3>
        <p class="text-sm text-gray-500 mb-6 px-2">Data pengguna ini akan dihapus secara permanen.</p>
        <div class="flex gap-3 w-full">
            <button onclick="closeCustomConfirm()" class="flex-1 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold hover:bg-gray-50 transition">Batal</button>
            <button id="confirmYesBtn" class="flex-1 py-3 rounded-xl bg-red-600 text-white font-bold shadow-lg hover:bg-red-700 transition">Ya, Hapus</button>
        </div>
    </div>
</div>

<script>
    <?php if(isset($_SESSION['flash'])): ?>
        document.addEventListener("DOMContentLoaded", function() {
            showCustomAlert('<?= $_SESSION['flash']['type'] ?>', '<?= $_SESSION['flash']['title'] ?>', '<?= $_SESSION['flash']['message'] ?>');
        });
    <?php unset($_SESSION['flash']); endif; ?>

    function showCustomAlert(type, title, message) {
        const modal = document.getElementById('customAlertModal');
        const content = document.getElementById('alertContent');
        const backdrop = document.getElementById('alertBackdrop');
        const iconBg = document.getElementById('alertIconBg');
        const icon = document.getElementById('alertIcon');
        const btn = document.getElementById('alertBtn');

        document.getElementById('alertTitle').innerText = title;
        document.getElementById('alertMessage').innerText = message;

        if (type === 'success') {
            iconBg.className = 'w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-green-100 text-green-600';
            icon.className = 'fas fa-check text-3xl';
            btn.className = 'w-full py-3 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02] bg-green-600 hover:bg-green-700 shadow-green-500/30';
        } else {
            iconBg.className = 'w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-red-100 text-red-600';
            icon.className = 'fas fa-times text-3xl';
            btn.className = 'w-full py-3 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02] bg-red-600 hover:bg-red-700 shadow-red-500/30';
        }

        modal.classList.remove('hidden');
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            content.classList.remove('scale-90', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeCustomAlert() {
        const modal = document.getElementById('customAlertModal');
        const content = document.getElementById('alertContent');
        const backdrop = document.getElementById('alertBackdrop');
        backdrop.classList.add('opacity-0');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-90', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    let deleteUrl = '';
    function triggerDeleteUser(id) {
        deleteUrl = '<?= BASE_URL ?>/admin/deleteUser?id=' + id;
        const modal = document.getElementById('customConfirmModal');
        const content = document.getElementById('confirmContent');
        const backdrop = document.getElementById('confirmBackdrop');
        modal.classList.remove('hidden');
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            content.classList.remove('scale-90', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    document.getElementById('confirmYesBtn').addEventListener('click', function() {
        if(deleteUrl) window.location.href = deleteUrl;
    });

    function closeCustomConfirm() {
        const modal = document.getElementById('customConfirmModal');
        const content = document.getElementById('confirmContent');
        const backdrop = document.getElementById('confirmBackdrop');
        backdrop.classList.add('opacity-0');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-90', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    function toggleNimField() {
        const role = document.getElementById('inputRole').value;
        const nimContainer = document.getElementById('nimContainer');
        const nimInput = document.getElementById('inputNim');

        if (role === 'User') {
            nimContainer.style.display = 'block';
            nimInput.required = true;
        } else {
            nimContainer.style.display = 'none';
            nimInput.value = '';
            nimInput.required = false;
        }
    }

    function previewImage(input) {
        const preview = document.getElementById('previewPhoto');
        const icon = document.getElementById('defaultPhotoIcon');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                icon.classList.add('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function openUserModal(mode, userData = null) {
        const modal = document.getElementById('modalUser');
        const content = document.getElementById('modalContent');
        const form = document.getElementById('userForm');
        
        form.reset();
        
        document.getElementById('previewPhoto').classList.add('hidden');
        document.getElementById('defaultPhotoIcon').classList.remove('hidden');
        
        if (mode === 'add') {
            document.getElementById('modalTitle').innerText = "User Baru";
            document.getElementById('modalSubtitle').innerText = "Tambahkan akun untuk akses sistem.";
            form.action = "<?= BASE_URL ?>/admin/addUser";
            
            document.getElementById('inputPass').required = true;
            document.getElementById('passHint').innerText = "(Wajib diisi)";
            document.getElementById('inputRole').value = 'User';
            toggleNimField();

        } else {
            document.getElementById('modalTitle').innerText = "Edit User";
            document.getElementById('modalSubtitle').innerText = "Perbarui informasi akun pengguna.";
            form.action = "<?= BASE_URL ?>/admin/editUser";
            
            document.getElementById('inputId').value = userData.id;
            document.getElementById('inputName').value = userData.name;
            document.getElementById('inputEmail').value = userData.email;
            document.getElementById('inputRole').value = userData.role;
            document.getElementById('inputNim').value = userData.nim || '';
            document.getElementById('inputPos').value = userData.position || '';
            document.getElementById('inputPhone').value = userData.no_telp || '';
            document.getElementById('inputAddress').value = userData.alamat || '';
            
            if (userData.photo_profile) {
                let photoUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(userData.name)}&background=random&size=500`;
                if (userData.photo_profile && userData.photo_profile.trim() !== "") {
                    photoUrl = `<?= BASE_URL ?>/uploads/profile/${userData.photo_profile}`;
                }
                document.getElementById('previewPhoto').src = photoUrl;
                document.getElementById('previewPhoto').classList.remove('hidden');
                document.getElementById('defaultPhotoIcon').classList.add('hidden');
            }

            toggleNimField();
            document.getElementById('inputPass').required = false;
            document.getElementById('passHint').innerText = "(Kosongkan jika tidak ubah)";
        }

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
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function searchTable() {
        const input = document.getElementById('searchUser');
        const filter = input.value.toLowerCase();
        const rows = document.getElementsByClassName('user-row');

        for (let i = 0; i < rows.length; i++) {
            const name = rows[i].querySelector('.user-name').innerText.toLowerCase();
            const email = rows[i].querySelector('.user-email').innerText.toLowerCase();
            if (name.includes(filter) || email.includes(filter)) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    }
</script>