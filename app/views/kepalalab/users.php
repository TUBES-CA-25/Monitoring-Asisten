

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-12">

    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden circuit-pattern">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0 text-center md:text-left">
                <h1 class="text-3xl font-extrabold">Daftar Pengguna</h1>
                <p class="text-blue-100 mt-2 text-sm">Monitoring Data Akun Asisten dan Admin.</p>
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
        <div class="bg-gradient-to-r from-blue-600 to-cyan-500 circuit-pattern relative overflow-hidden p-6 flex flex-col sm:flex-row justify-between items-center gap-4">
            <h3 class="font-bold text-white uppercase tracking-wide text-sm">Daftar User</h3>

            <?php
                // [BARU] Sebelumnya halaman ini hanya punya pencarian client-side
                // (hanya menyaring baris yang sudah dimuat di halaman saat ini) —
                // padahal controller sudah menyiapkan filter role/jabatan/angkatan
                // sejak lama. Disamakan dengan admin/users.php: form GET penuh,
                // dan jabatan/angkatan otomatis nonaktif saat role Admin/Kepala Lab
                // dipilih (lihat handleUserRoleFilterChange di kepalalab/users.js).
                $roleLocksJabatanAngkatan = in_array($filter_role ?? '', ['Admin', 'Kepala Lab'], true);
            ?>
            <form action="<?= BASE_URL ?>/kepalalab/manageUsers" method="GET" class="flex flex-wrap gap-2 items-center">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-3 text-blue-100 text-xs"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search_keyword ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Cari nama, email, NIM..." autocomplete="off"
                           class="pl-8 pr-3 py-2 rounded-xl bg-white/10 text-white placeholder-blue-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-white/30 border border-white/20 w-52">
                </div>
                <select name="role" id="filterRoleSelect" onchange="handleUserRoleFilterChange(this)" class="px-3 py-2 rounded-xl bg-white/10 text-white text-xs font-medium border border-white/20 focus:outline-none focus:ring-2 focus:ring-white/30 cursor-pointer">
                    <option value="">Semua Role</option>
                    <option value="Kepala Lab" <?= (($filter_role??'')=='Kepala Lab')?'selected':'' ?>>Kepala Lab</option>
                    <option value="Admin" <?= (($filter_role??'')=='Admin')?'selected':'' ?>>Admin</option>
                    <option value="User" <?= (($filter_role??'')=='User')?'selected':'' ?>>Asisten</option>
                </select>
                <select name="jabatan" id="filterJabatanSelect" onchange="this.form.submit()" <?= $roleLocksJabatanAngkatan ? 'disabled title="Tidak berlaku untuk role Admin/Kepala Lab"' : '' ?> class="px-3 py-2 rounded-xl bg-white/10 text-white text-xs font-medium border border-white/20 focus:outline-none focus:ring-2 focus:ring-white/30 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                    <option value="">Semua Jabatan</option>
                    <?php foreach(($jabatan_list ?? []) as $jab): ?>
                    <option value="<?= htmlspecialchars($jab, ENT_QUOTES, 'UTF-8') ?>" <?= (($filter_jabatan??'')==$jab)?'selected':'' ?>>
                        <?= htmlspecialchars($jab, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select name="angkatan" id="filterAngkatanSelect" onchange="this.form.submit()" <?= $roleLocksJabatanAngkatan ? 'disabled title="Tidak berlaku untuk role Admin/Kepala Lab"' : '' ?> class="px-3 py-2 rounded-xl bg-white/10 text-white text-xs font-medium border border-white/20 focus:outline-none focus:ring-2 focus:ring-white/30 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                    <option value="">Semua Angkatan</option>
                    <?php foreach(($angkatan_list ?? []) as $ang): ?>
                    <option value="<?= htmlspecialchars($ang, ENT_QUOTES, 'UTF-8') ?>" <?= (($filter_angkatan??'')==$ang)?'selected':'' ?>>
                        <?= htmlspecialchars($ang, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="px-4 py-2 rounded-xl bg-white/20 hover:bg-white/30 text-white text-xs font-bold border border-white/20 transition">
                    <i class="fas fa-search mr-1"></i>Cari
                </button>
                <?php if (($search_keyword ?? '') || ($filter_role ?? '') || ($filter_jabatan ?? '') || ($filter_angkatan ?? '')): ?>
                <a href="<?= BASE_URL ?>/kepalalab/manageUsers" class="px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-blue-200 text-xs font-medium border border-white/10 transition">
                    <i class="fas fa-times"></i> Reset
                </a>
                <?php endif; ?>
            </form>
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
                        // Logic Status Presensi (online / izin / belum presensi)
                        if ((($u['status_account'] ?? 'ACTIVE') === 'INACTIVE')) {
                            $statusBadge = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-gray-300 text-gray-600 border border-gray-400"><i class="fas fa-user-slash"></i> Nonaktif</span>';
                        } elseif ($u['role'] !== 'User') {
                            $statusBadge = '';
                        } elseif (!empty($u['is_online'])) {
                            $statusBadge = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700 border border-green-200"><i class="fas fa-circle text-[8px] animate-pulse"></i> Online</span>';
                        } elseif (!empty($u['is_on_leave'])) {
                            $statusBadge = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-yellow-100 text-yellow-700 border border-yellow-200"><i class="fas fa-file-medical-alt"></i> Izin</span>';
                        } else {
                            $statusBadge = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-500 border border-gray-200"><i class="fas fa-clock"></i> Belum Presensi</span>';
                        }
                        
                        // Badge Role Color
                        $roleColor = match($u['role']) {
                            'Kepala Lab' => 'bg-red-50 text-red-600 border-red-100',
                            'Admin'       => 'bg-purple-50 text-purple-600 border-purple-100',
                            default       => 'bg-blue-50 text-blue-600 border-blue-100'
                        };
                    ?>
                    
                    <tr class="group hover:bg-blue-50/30 transition duration-200 user-row <?= (($u['status_account'] ?? 'ACTIVE') === 'INACTIVE') ? 'bg-gray-100/70 opacity-75' : '' ?>">
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
                                </div>
                                
                                <div>
                                    <div class="font-bold text-gray-800 text-sm user-name leading-tight mb-1">
                                        <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <?php if ($u['role'] === 'User' && !empty($u['nim'])): ?>
                                    <div class="text-[10px] font-mono text-gray-400 mb-1 user-nim"><?= htmlspecialchars($u['nim']) ?></div>
                                    <?php endif; ?>
                                    <div class="flex flex-wrap gap-2 items-center">
                                        <?= $statusBadge ?>
                                        <?php if ($u['role'] == 'User'): ?>
                                            <span class="text-[10px] text-gray-400 font-mono bg-gray-50 px-1.5 py-0.5 rounded border border-gray-100 user-nim">
                                                <?= htmlspecialchars($u['nim'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="p-6">
                            <div class="text-sm font-bold text-gray-700 mb-1"><?= htmlspecialchars($u['position'] ?? 'Anggota', ENT_QUOTES, 'UTF-8') ?></div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase <?= $u['role']=='Admin'?'bg-purple-50 text-purple-600 border-purple-100':($u['role']=='Kepala Lab'?'bg-red-50 text-red-600 border-red-100':'bg-blue-50 text-blue-600 border-blue-100') ?>">
                                <?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php if ($u['role'] == 'User' && !empty($u['kelas'])): ?>
                                <div class="mt-2 flex items-center gap-1.5">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase">Kelas:</span>
                                    <span class="text-[10px] font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded border border-gray-200 font-mono">
                                        <?= htmlspecialchars($u['kelas'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td class="p-6">
                            <div class="space-y-1.5">
                                <div class="flex items-center gap-2 text-sm text-gray-600 user-email">
                                    <div class="w-6 flex justify-center"><i class="fas fa-envelope text-gray-300 text-xs"></i></div>
                                    <span class="truncate max-w-[200px]" title="<?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <div class="w-6 flex justify-center"><i class="fas fa-phone text-gray-300 text-[10px]"></i></div>
                                    <span class="font-mono"><?= htmlspecialchars($u['no_telp'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
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

        <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex flex-col sm:flex-row items-center justify-between gap-4">

            <div class="text-xs text-gray-500 font-bold uppercase tracking-wide">
                Halaman <span class="text-blue-600"><?= (int)$pagination['current'] ?></span> dari <?= (int)$pagination['total_pages'] ?>
                <span class="normal-case text-gray-400 font-medium ml-1">(Total <?= (int)$pagination['total_items'] ?> User)</span>
            </div>

            <div class="flex items-center gap-2">
                <?php
                    if (!function_exists('buildKepalaLabUserUrl')) {
                        function buildKepalaLabUserUrl($page) {
                            $params = $_GET;
                            $params['page'] = $page;
                            return BASE_URL . '/kepalalab/manageUsers?' . http_build_query($params);
                        }
                    }
                ?>

                <?php if ($pagination['current'] > 1): ?>
                    <a href="<?= buildKepalaLabUserUrl($pagination['current'] - 1) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition shadow-sm"><i class="fas fa-chevron-left text-xs"></i></a>
                <?php else: ?>
                    <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 border border-gray-200 text-gray-300 cursor-not-allowed"><i class="fas fa-chevron-left text-xs"></i></span>
                <?php endif; ?>

                <?php
                    $startPage = max(1, $pagination['current'] - 2);
                    $endPage = min($pagination['total_pages'], $pagination['current'] + 2);
                    for ($i = $startPage; $i <= $endPage; $i++):
                        $activeClass = ($i == $pagination['current']) ? 'bg-blue-600 text-white border-blue-600 shadow-blue-500/30' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50';
                ?>
                    <a href="<?= buildKepalaLabUserUrl($i) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border text-xs font-bold transition shadow-sm <?= $activeClass ?>"><?= (int)$i ?></a>
                <?php endfor; ?>

                <?php if ($pagination['current'] < $pagination['total_pages']): ?>
                    <a href="<?= buildKepalaLabUserUrl($pagination['current'] + 1) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition shadow-sm"><i class="fas fa-chevron-right text-xs"></i></a>
                <?php else: ?>
                    <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 border border-gray-200 text-gray-300 cursor-not-allowed"><i class="fas fa-chevron-right text-xs"></i></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div id="modalDetail" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm transition-opacity" onclick="closeDetailModal()"></div>
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl relative z-10 overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col max-h-[90vh]" id="modalContent">
        
        <div class="bg-gradient-to-r from-blue-700 to-cyan-600 circuit-pattern relative overflow-hidden p-6 flex justify-between items-center shrink-0">
            <div>
                <h3 class="font-bold text-xl text-white">Detail Pengguna</h3>
                <p class="text-xs text-blue-100">Informasi lengkap akun (Mode Lihat).</p>
            </div>
            <button onclick="closeDetailModal()" class="w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 flex items-center justify-center text-blue-100 hover:text-white transition"><i class="fas fa-times"></i></button>
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
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- NIM — hanya User/Asisten -->
                <div id="modalNimContainer">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">NIM</label>
                    <div id="detailNim" class="input-readonly w-full px-4 py-2.5 rounded-xl border text-sm font-mono">-</div>
                </div>

                <!-- Prodi — hanya User/Asisten -->
                <div id="modalProdiContainer">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Program Studi</label>
                    <div id="detailProdi" class="input-readonly w-full px-4 py-2.5 rounded-xl border text-sm">-</div>
                </div>

                <!-- Kelas — hanya User/Asisten -->
                <div id="modalClassContainer">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Kelas</label>
                    <div id="detailClass" class="input-readonly w-full px-4 py-2.5 rounded-xl border text-sm font-mono">-</div>
                </div>

                <!-- Angkatan — hanya User/Asisten -->
                <div id="modalAngkatanContainer">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Angkatan</label>
                    <div id="detailAngkatan" class="input-readonly w-full px-4 py-2.5 rounded-xl border text-sm font-mono">-</div>
                </div>

                <!-- Peminatan — hanya User/Asisten -->
                <div id="modalPeminatanContainer" class="md:col-span-2">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Peminatan</label>
                    <div id="detailPeminatan" class="input-readonly w-full px-4 py-2.5 rounded-xl border text-sm">-</div>
                </div>

                <!-- No. Telepon -->
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">No. WhatsApp</label>
                    <div id="detailPhone" class="input-readonly w-full px-4 py-2.5 rounded-xl border text-sm font-mono">-</div>
                </div>

                <!-- Jenis Kelamin -->
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Jenis Kelamin</label>
                    <div id="detailGender" class="input-readonly w-full px-4 py-2.5 rounded-xl border text-sm">-</div>
                </div>

                <!-- Laboratorium — sembunyikan untuk Admin -->
                <div id="modalLabContainer" class="md:col-span-2">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Laboratorium</label>
                    <div id="detailLab" class="input-readonly w-full px-4 py-2.5 rounded-xl border text-sm">-</div>
                </div>

                <!-- Status Verifikasi -->
                <!-- Status Verifikasi + Status Akun -->
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Status Profil</label>
                    <div id="detailStatus" class="w-full px-4 py-2.5 rounded-xl border text-xs font-bold text-center uppercase">-</div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Status Akun</label>
                    <div id="detailAccountStatus" class="w-full px-4 py-2.5 rounded-xl border text-xs font-bold text-center uppercase">-</div>
                </div>

                <!-- Tanggal Bergabung -->
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Tanggal Bergabung</label>
                    <div id="detailCreatedAt" class="input-readonly w-full px-4 py-2.5 rounded-xl border text-xs font-semibold text-gray-700">-</div>
                </div>

                <!-- Alamat -->
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Alamat Domisili</label>
                    <div id="detailAddress" class="input-readonly w-full px-4 py-2.5 rounded-xl border text-sm min-h-[60px]">-</div>
                </div>
            </div>

        </div>

        <div class="p-4 border-t border-gray-100 bg-gray-50 flex justify-end">
            <button onclick="closeDetailModal()" class="px-6 py-2.5 rounded-xl bg-gray-200 text-gray-700 font-bold hover:bg-gray-300 transition text-sm">Tutup</button>
        </div>
    </div>
</div>

