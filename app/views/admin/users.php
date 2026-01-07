<div class="max-w-7xl mx-auto space-y-6 animate-enter">
    
    <div class="flex flex-col md:flex-row justify-between items-center bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-800">Manajemen Pengguna</h1>
            <p class="text-gray-500 text-sm mt-1">Kelola data asisten, admin, dan super admin.</p>
        </div>
        <button onclick="document.getElementById('modalUser').classList.remove('hidden')" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-blue-500/30 hover:bg-blue-700 transition flex items-center gap-2 transform hover:-translate-y-1">
            <i class="fas fa-user-plus"></i> Tambah User Baru
        </button>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-400 uppercase tracking-wider">
                        <th class="p-6">Profil</th>
                        <th class="p-6">Role & Jabatan</th>
                        <th class="p-6">Kontak</th>
                        <th class="p-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach($users_list as $u): ?>
                    <tr class="group hover:bg-blue-50/50 transition duration-200">
                        <td class="p-6">
                            <div class="flex items-center gap-4">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($u['name']) ?>&background=random" class="w-10 h-10 rounded-full border border-gray-200 shadow-sm">
                                <div>
                                    <div class="font-bold text-gray-800 text-sm"><?= $u['name'] ?></div>
                                    
                                    <?php if ($u['role'] == 'User'): ?>
                                        <div class="text-[10px] text-gray-400 font-mono mt-0.5">NIM: <?= $u['nim'] ?? '-' ?></div>
                                        <!-- <div class="text-[10px] text-gray-400 font-mono mt-0.5">ID: <?= $u['id'] ?></div> -->
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="p-6">
                            <span class="inline-block px-3 py-1 rounded-full text-[10px] font-bold uppercase mb-1
                                <?= $u['role']=='Admin'?'bg-purple-100 text-purple-600':($u['role']=='Super Admin'?'bg-red-100 text-red-600':'bg-blue-100 text-blue-600') ?>">
                                <?= $u['role'] ?>
                            </span>
                            <div class="text-xs text-gray-500 font-medium"><?= $u['position'] ?? 'Anggota' ?></div>
                        </td>
                        <td class="p-6">
                            <div class="text-sm text-gray-600"><?= $u['email'] ?></div>
                            <div class="text-xs text-gray-400 mt-0.5"><?= $u['nim'] ?? '-' ?></div>
                        </td>
                        <td class="p-6">
                            <div class="flex items-center justify-center gap-2 opacity-60 group-hover:opacity-100 transition">
                                <button class="w-8 h-8 rounded-lg bg-yellow-100 text-yellow-600 hover:bg-yellow-200 flex items-center justify-center transition" title="Edit">
                                    <i class="fas fa-edit text-xs"></i>
                                </button>
                                <a href="<?= BASE_URL ?>/admin/deleteUser?id=<?= $u['id'] ?>" onclick="return confirm('Hapus user ini?')" class="w-8 h-8 rounded-lg bg-red-100 text-red-600 hover:bg-red-200 flex items-center justify-center transition" title="Hapus">
                                    <i class="fas fa-trash text-xs"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalUser" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="document.getElementById('modalUser').classList.add('hidden')"></div>
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl relative z-10 overflow-hidden animate-enter">
        <div class="bg-gray-50 p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Tambah Pengguna Baru</h3>
            <button onclick="document.getElementById('modalUser').classList.add('hidden')" class="text-gray-400 hover:text-red-500"><i class="fas fa-times"></i></button>
        </div>
        <form action="<?= BASE_URL ?>/admin/addUser" method="POST" class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Lengkap</label>
                <input type="text" name="name" required class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Email</label>
                    <input type="email" name="email" required class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Role</label>
                    <select name="role" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition text-sm">
                        <option value="User">Asisten (User)</option>
                        <option value="Admin">Admin</option>
                        <option value="Super Admin">Super Admin</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition text-sm">
            </div>
            <div class="pt-4 flex gap-3">
                <button type="button" onclick="document.getElementById('modalUser').classList.add('hidden')" class="flex-1 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold hover:bg-gray-50 transition">Batal</button>
                <button type="submit" class="flex-1 py-3 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition">Simpan User</button>
            </div>
        </form>
    </div>
</div>