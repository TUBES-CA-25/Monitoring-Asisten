<style>
    .animate-enter { animation: fadeInUp 0.5s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    
    .input-readonly {
        background-color: #f9fafb; /* gray-50 */
        border-color: #e5e7eb;     /* gray-200 */
        color: #374151;            /* gray-700 */
        cursor: default;
    }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-12">

    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0 text-center md:text-left">
                <h1 class="text-3xl font-extrabold">Daftar Pengguna</h1>
                <p class="text-blue-100 mt-2 text-sm">Monitoring detail akun Asisten dan Admin (Mode Lihat).</p>
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
            <h3 class="font-bold text-gray-700 uppercase tracking-wide text-sm">Database Akun</h3>
            
            <div class="relative w-full sm:w-72">
                <i class="fas fa-search absolute left-4 top-3.5 text-gray-400 text-xs"></i>
                <input type="text" id="searchUser" onkeyup="searchTable()" placeholder="Cari nama, email, atau NIM..." class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition shadow-sm">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left" id="userTable">
                <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-400 uppercase tracking-wider">
                    <tr>
                        <th class="p-6 w-1/3">Profil Pengguna</th>
                        <th class="p-6 w-1/4">Jabatan & Role</th>
                        <th class="p-6 w-1/3">Informasi Kontak</th>
                        <th class="p-6 text-center">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if(empty($users_list)): ?>
                        <tr>
                            <td colspan="4" class="p-8 text-center text-gray-400 italic">
                                Tidak ada data pengguna yang ditemukan.
                            </td>
                        </tr>
                    <?php else: foreach($users_list as $u): 
                        // Logic Status Akun
                        $isVerified = isset($u['is_completed']) && $u['is_completed'] == 1;
                        $statusBadge = $isVerified 
                            ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700 border border-green-200"><i class="fas fa-check-circle"></i> Terverifikasi</span>'
                            : '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-yellow-100 text-yellow-700 border border-yellow-200"><i class="fas fa-clock"></i> Pending</span>';
                        
                        // Badge Role Color
                        $roleColor = match($u['role']) {
                            'Super Admin' => 'bg-red-50 text-red-600 border-red-100',
                            'Admin'       => 'bg-purple-50 text-purple-600 border-purple-100',
                            default       => 'bg-blue-50 text-blue-600 border-blue-100'
                        };
                    ?>
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
                                <div class="relative shrink-0">
                                    <img src="<?= $avatarUrl ?>" class="w-12 h-12 rounded-full border-2 border-white shadow-sm object-cover bg-gray-100">
                                    <?php if($u['role'] == 'User'): ?>
                                        <div class="absolute -bottom-1 -right-1 bg-white rounded-full p-0.5 shadow-sm">
                                            <div class="w-3 h-3 rounded-full <?= ($u['is_online'] ?? 0) ? 'bg-green-500' : 'bg-gray-300' ?>" title="Status Online"></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <div class="font-bold text-gray-800 text-sm user-name leading-tight mb-1">
                                        <?= $u['name'] ?>
                                    </div>
                                    <div class="flex flex-wrap gap-2 items-center">
                                        <?= $statusBadge ?>
                                        <?php if ($u['role'] == 'User'): ?>
                                            <span class="text-[10px] text-gray-400 font-mono bg-gray-50 px-1.5 py-0.5 rounded border border-gray-100 user-nim">
                                                <?= $u['nim'] ?? '-' ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="p-6">
                            <div class="mb-1.5">
                                <span class="inline-block px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border <?= $roleColor ?>">
                                    <?= $u['role'] ?>
                                </span>
                            </div>
                            <div class="text-sm font-medium text-gray-700">
                                <?= $u['position'] ?? 'Anggota' ?>
                            </div>
                            
                            <?php if ($u['role'] == 'User' && !empty($u['kelas'])): ?>
                                <div class="mt-2 flex items-center gap-1.5">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase">Kelas:</span>
                                    <span class="text-[10px] font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded border border-gray-200 font-mono">
                                        <?= $u['kelas'] ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td class="p-6">
                            <div class="space-y-1.5">
                                <div class="flex items-center gap-2 text-sm text-gray-600 user-email">
                                    <div class="w-6 flex justify-center"><i class="fas fa-envelope text-gray-300 text-xs"></i></div>
                                    <span class="truncate max-w-[200px]" title="<?= $u['email'] ?>"><?= $u['email'] ?></span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <div class="w-6 flex justify-center"><i class="fas fa-phone text-gray-300 text-[10px]"></i></div>
                                    <span class="font-mono"><?= $u['no_telp'] ?? '-' ?></span>
                                </div>
                            </div>
                        </td>

                        <td class="p-6 text-center">
                            <button onclick='openDetailModal(<?= json_encode($u) ?>)' class="w-9 h-9 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-blue-600 hover:border-blue-300 hover:shadow-md transition flex items-center justify-center mx-auto" title="Lihat Detail Lengkap">
                                <i class="fas fa-eye text-xs"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalDetail" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm transition-opacity" onclick="closeDetailModal()"></div>
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col max-h-[90vh]" id="modalContent">
        
        <div class="bg-white p-6 border-b border-gray-100 flex justify-between items-center shrink-0">
            <div>
                <h3 class="font-bold text-xl text-gray-800">Detail Pengguna</h3>
                <p class="text-xs text-gray-500">Informasi lengkap akun (Mode Lihat).</p>
            </div>
            <button onclick="closeDetailModal()" class="w-8 h-8 rounded-full bg-gray-50 hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-red-500 transition"><i class="fas fa-times"></i></button>
        </div>

        <div class="p-8 overflow-y-auto custom-scrollbar">
            
            <div class="flex flex-col md:flex-row gap-8 items-start mb-8">
                <div class="w-full md:w-auto flex justify-center">
                    <div class="w-32 h-32 rounded-full p-1 border-2 border-gray-100 shadow-lg">
                        <img id="detailPhoto" src="" class="w-full h-full rounded-full object-cover">
                    </div>
                </div>

                <div class="flex-1 w-full space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Nama Lengkap</label>
                            <div id="detailName" class="text-sm font-bold text-gray-800 bg-gray-50 p-2.5 rounded-lg border border-gray-200"></div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Role & Jabatan</label>
                            <div class="flex gap-2">
                                <div id="detailRole" class="text-xs font-bold text-blue-700 bg-blue-50 p-2.5 rounded-lg border border-blue-100 flex-1 text-center"></div>
                                <div id="detailPosition" class="text-xs font-bold text-gray-700 bg-gray-100 p-2.5 rounded-lg border border-gray-200 flex-1 text-center"></div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Email</label>
                        <div id="detailEmail" class="text-sm font-mono text-gray-600 bg-gray-50 p-2.5 rounded-lg border border-gray-200"></div>
                    </div>
                </div>
            </div>

            <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest border-b border-gray-100 pb-2 mb-4">Informasi Tambahan</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">NIM / ID</label>
                    <div id="detailNim" class="input-readonly w-full px-4 py-2.5 rounded-xl border text-sm font-mono"></div>
                </div>
                
                <div id="modalClassContainer" class="hidden">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Kelas</label>
                    <div id="detailClass" class="input-readonly w-full px-4 py-2.5 rounded-xl border text-sm font-mono"></div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">No. Telepon</label>
                    <div id="detailPhone" class="input-readonly w-full px-4 py-2.5 rounded-xl border text-sm font-mono"></div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Laboratorium</label>
                    <div id="detailLab" class="input-readonly w-full px-4 py-2.5 rounded-xl border text-sm"></div>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Status Verifikasi Akun</label>
                    <div id="detailStatus" class="w-full px-4 py-2.5 rounded-xl border text-xs font-bold text-center uppercase"></div>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Alamat Domisili</label>
                    <div id="detailAddress" class="input-readonly w-full px-4 py-2.5 rounded-xl border text-sm min-h-[60px]"></div>
                </div>
            </div>

        </div>

        <div class="p-4 border-t border-gray-100 bg-gray-50 flex justify-end">
            <button onclick="closeDetailModal()" class="px-6 py-2.5 rounded-xl bg-gray-200 text-gray-700 font-bold hover:bg-gray-300 transition text-sm">Tutup</button>
        </div>
    </div>
</div>

<script>
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

        // [LOGIC PENTING] Handle Visibility Kelas
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
</script>