<?php /* [BARU - Tahap 29] Partial tabel + pagination "Daftar User".
     Dipisah dari admin/users.php agar bisa di-render ULANG via AJAX saat
     pencarian real-time (lihat AdminController::manageUsers() - parameter
     ?ajax=1 - dan public/assets/js/admin/users.js). Variabel $users_list,
     $pagination, $_SESSION dipakai sama seperti sebelumnya (sudah tersedia
     lewat extract($data) di Controller::view()). */ ?>
<div id="usersTableContainer">
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
                    <?php if(!empty($users_list)): foreach($users_list as $u):
                        $isInactiveUser = (($u['status_account'] ?? 'ACTIVE') === 'INACTIVE');
                        if ($isInactiveUser) {
                            $statusBadge = '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-300 text-gray-600 border border-gray-400"><i class="fas fa-user-slash mr-1"></i>Nonaktif</span>';
                        } elseif ($u['role'] !== 'User') {
                            $statusBadge = '';
                        } elseif (!empty($u['is_online'])) {
                            $statusBadge = '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700 border border-green-200"><i class="fas fa-circle text-[8px] mr-1 animate-pulse"></i>Online</span>';
                        } elseif (!empty($u['is_on_leave'])) {
                            $statusBadge = '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-yellow-100 text-yellow-700 border border-yellow-200"><i class="fas fa-file-medical-alt mr-1"></i>Izin</span>';
                        } else {
                            $statusBadge = '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-500 border border-gray-200"><i class="fas fa-clock mr-1"></i>Belum Presensi</span>';
                        }
                    ?>
                    <tr class="group hover:bg-blue-50/30 transition user-row <?= (($u['status_account'] ?? 'ACTIVE') === 'INACTIVE') ? 'bg-gray-100/70 opacity-75' : '' ?>">
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
                                    <?php if ($u['role'] === 'User' && !empty($u['nim'])): ?>
                                    <div class="mt-0.5 text-[10px] font-mono text-gray-400 user-nim"><?= htmlspecialchars($u['nim']) ?></div>
                                    <?php endif; ?>
                                    <div class="mt-1"><?= $statusBadge ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="p-6">
                            <div class="text-sm font-bold text-gray-700 mb-1"><?= $u['position'] ?? 'Anggota' ?></div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase <?= $u['role']=='Admin'?'bg-purple-50 text-purple-600 border-purple-100':($u['role']=='Kepala Lab'?'bg-red-50 text-red-600 border-red-100':'bg-blue-50 text-blue-600 border-blue-100') ?>">
                                <?= $u['role'] ?>
                            </span>
                            <?php if ($u['role'] == 'User' && !empty($u['kelas'] ?? $u['class'] ?? '')): ?>
                                <div class="mt-2 flex items-center gap-1.5">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase">Kelas:</span>
                                    <span class="text-[10px] font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded border border-gray-200 font-mono">
                                        <?= $u['kelas'] ?? $u['class'] ?? '' ?>
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
                   <?php endforeach; else: ?>
                        <tr>
                            <td colspan="4" class="p-8 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center opacity-50">
                                    <i class="fas fa-search text-3xl mb-2"></i>
                                    <p>Data pengguna tidak ditemukan.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div> <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex flex-col sm:flex-row items-center justify-between gap-4">
            
            <div class="text-xs text-gray-500 font-bold uppercase tracking-wide">
                Halaman <span class="text-blue-600"><?= $pagination['current'] ?></span> dari <?= $pagination['total_pages'] ?> 
                <span class="normal-case text-gray-400 font-medium ml-1">(Total <?= $pagination['total_items'] ?> User)</span>
            </div>

            <div class="flex items-center gap-2">
                <?php 
                    if (!function_exists('buildUserUrl')) {
                        function buildUserUrl($page) {
                            $params = $_GET; 
                            $params['page'] = $page; 
                            unset($params['ajax']); // strip ?ajax=1 dari bookmark URL
                            return BASE_URL . '/admin/manageUsers?' . http_build_query($params);
                        }
                    }
                ?>

                <?php if ($pagination['current'] > 1): ?>
                    <a href="<?= buildUserUrl($pagination['current'] - 1) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition shadow-sm"><i class="fas fa-chevron-left text-xs"></i></a>
                <?php else: ?>
                    <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 border border-gray-200 text-gray-300 cursor-not-allowed"><i class="fas fa-chevron-left text-xs"></i></span>
                <?php endif; ?>

                <?php 
                    $startPage = max(1, $pagination['current'] - 2);
                    $endPage = min($pagination['total_pages'], $pagination['current'] + 2);
                    for ($i = $startPage; $i <= $endPage; $i++): 
                        $activeClass = ($i == $pagination['current']) ? 'bg-blue-600 text-white border-blue-600 shadow-blue-500/30' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50';
                ?>
                    <a href="<?= buildUserUrl($i) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border text-xs font-bold transition shadow-sm <?= $activeClass ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($pagination['current'] < $pagination['total_pages']): ?>
                    <a href="<?= buildUserUrl($pagination['current'] + 1) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition shadow-sm"><i class="fas fa-chevron-right text-xs"></i></a>
                <?php else: ?>
                    <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 border border-gray-200 text-gray-300 cursor-not-allowed"><i class="fas fa-chevron-right text-xs"></i></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
</div>
