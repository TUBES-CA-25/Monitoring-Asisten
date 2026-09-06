

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-12">

    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden circuit-pattern">
        <div class="absolute right-0 top-0 h-full w-1/2 bg-white/10 skew-x-12 transform origin-bottom-left"></div>
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0 text-center md:text-left">
                <h1 class="text-3xl font-extrabold">Manajemen Pengguna</h1>
                <p class="text-blue-100 mt-2 text-sm">Kelola Akun Asisten, Admin, dan Kepala Lab.</p>
                <button onclick="openUserModal('add')" class="mt-6 bg-white text-blue-600 px-6 py-3 rounded-xl font-bold shadow-lg hover:bg-indigo-50 transition transform hover:scale-105 flex items-center gap-2 mx-auto md:mx-0">
                    <i class="fas fa-plus-circle"></i> Tambah User Baru
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
        <!-- [BARU] Sebelumnya form filter memakai "flex flex-wrap" tanpa
             justify-end - begitu layar menyempit dan kontrol filter pindah
             ke baris kedua, baris itu ikut menempel ke tepi KIRI form
             (bukan rata kanan mengikuti baris pertama), terlihat berantakan.
             Ditambahkan "justify-end" (rata kanan, konsisten di semua
             baris) + setiap kontrol diberi "min-w" sendiri (bukan lebar
             tetap) supaya membungkus dengan rapi tanpa terpotong. -->
        <div class="bg-gradient-to-r from-blue-600 to-cyan-500 circuit-pattern relative overflow-hidden p-6 flex flex-col sm:flex-row justify-between items-center gap-4">
            <h3 class="font-bold text-white uppercase tracking-wide text-sm shrink-0 whitespace-nowrap">Daftar User</h3>

            <form action="<?= BASE_URL ?>/admin/manageUsers" method="GET" class="w-full sm:w-auto flex flex-wrap items-center gap-2 justify-center sm:justify-end" id="userSearchForm">
                <!-- Search -->
                <div class="relative flex-1 min-w-[160px] sm:flex-none sm:w-52">
                    <!-- [BARU] Ikon dipusatkan lewat flex (inset-y-0 + items-center),
                         bukan "top" statis - selalu presisi center berapa pun
                         tinggi/ukuran font inputnya, tidak lagi terlihat naik/miring. -->
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="fas fa-search text-blue-100 text-xs leading-none"></i>
                    </div>
                    <input type="text" name="search" id="userSearchInput" value="<?= htmlspecialchars($search_keyword ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Cari nama, email, NIM..." autocomplete="off"
                           class="w-full pl-9 pr-3 py-2.5 rounded-xl bg-white/10 text-white placeholder-blue-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-white/30 border border-white/20">
                </div>
                <?php
                    // [BARU] Admin & Kepala Lab tidak punya jabatan/angkatan asisten
                    // (kolom itu hanya relevan untuk role User) — jika role tsb dipilih,
                    // nonaktifkan kedua filter tersebut baik saat render awal (PHP)
                    // maupun saat diubah kembali via JS (lihat handleUserRoleFilterChange).
                    $roleLocksJabatanAngkatan = in_array($filter_role ?? '', ['Admin', 'Kepala Lab'], true);
                ?>
                <!-- Filter Role -->
                <select name="role" id="filterRoleSelect" onchange="handleUserRoleFilterChange(this)" class="flex-1 min-w-[120px] sm:flex-none sm:w-auto px-3 py-2.5 rounded-xl bg-white/10 text-white text-xs font-medium border border-white/20 focus:outline-none focus:ring-2 focus:ring-white/30 cursor-pointer">
                    <option value="">Semua Role</option>
                    <option value="Kepala Lab" <?= (($filter_role??'')=='Kepala Lab')?'selected':'' ?>>Kepala Lab</option>
                    <option value="Admin" <?= (($filter_role??'')=='Admin')?'selected':'' ?>>Admin</option>
                    <option value="User" <?= (($filter_role??'')=='User')?'selected':'' ?>>Asisten</option>
                </select>
                <!-- Filter Jabatan -->
                <select name="jabatan" id="filterJabatanSelect" onchange="this.form.submit()" <?= $roleLocksJabatanAngkatan ? 'disabled title="Tidak berlaku untuk role Admin/Kepala Lab"' : '' ?> class="flex-1 min-w-[130px] sm:flex-none sm:w-auto px-3 py-2.5 rounded-xl bg-white/10 text-white text-xs font-medium border border-white/20 focus:outline-none focus:ring-2 focus:ring-white/30 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                    <option value="">Semua Jabatan</option>
                    <?php foreach(($jabatan_list ?? []) as $jab): ?>
                    <option value="<?= htmlspecialchars($jab, ENT_QUOTES, 'UTF-8') ?>" <?= (($filter_jabatan??'')==$jab)?'selected':'' ?>>
                        <?= htmlspecialchars($jab, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <!-- Filter Angkatan -->
                <select name="angkatan" id="filterAngkatanSelect" onchange="this.form.submit()" <?= $roleLocksJabatanAngkatan ? 'disabled title="Tidak berlaku untuk role Admin/Kepala Lab"' : '' ?> class="flex-1 min-w-[130px] sm:flex-none sm:w-auto px-3 py-2.5 rounded-xl bg-white/10 text-white text-xs font-medium border border-white/20 focus:outline-none focus:ring-2 focus:ring-white/30 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                    <option value="">Semua Angkatan</option>
                    <?php foreach(($angkatan_list ?? []) as $ang): ?>
                    <option value="<?= htmlspecialchars($ang, ENT_QUOTES, 'UTF-8') ?>" <?= (($filter_angkatan??'')==$ang)?'selected':'' ?>>
                        <?= htmlspecialchars($ang, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <!-- Submit + Reset -->
                <div class="flex gap-2 shrink-0">
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-white/20 hover:bg-white/30 text-white text-xs font-bold border border-white/20 transition whitespace-nowrap">
                        <i class="fas fa-search mr-1"></i>Cari
                    </button>
                    <?php if ($search_keyword || $filter_role || $filter_jabatan || $filter_angkatan): ?>
                    <a href="<?= BASE_URL ?>/admin/manageUsers" class="px-3 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-blue-200 text-xs font-medium border border-white/10 transition whitespace-nowrap">
                        <i class="fas fa-times"></i> Reset
                    </a>
                    <?php endif; ?>
                </div>
            </form>

        </div>

        <?php require __DIR__ . '/partials/users_table.php'; ?>
        </div> </div>

<div id="modalUser" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeUserModal()"></div>
    <div class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl relative z-10 overflow-hidden transform scale-95 transition-all duration-300 flex flex-col max-h-[90vh]" id="modalContent">
        
        <div class="bg-gradient-to-r from-blue-700 to-cyan-600 circuit-pattern relative overflow-hidden p-6 flex justify-between items-center shrink-0">
            <div>
                <h3 class="font-bold text-xl text-white" id="modalTitle">Tambah Pengguna</h3>
                <p class="text-xs text-blue-100">Isi data akun dengan lengkap.</p>
            </div>
            <button onclick="closeUserModal()" class="w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 flex items-center justify-center text-blue-100 hover:text-white transition"><i class="fas fa-times"></i></button>
        </div>

        <div class="p-8 overflow-y-auto custom-scrollbar">
            <!-- [BARU] novalidate - form ini terbagi 2 tab (Data Akun/Data
                 Pribadi) yang disembunyikan bergantian via class "hidden".
                 TANPA novalidate, validasi HTML5 bawaan browser tetap
                 mencoba memvalidasi field required di tab yang SEDANG
                 TERSEMBUNYI - karena field itu tidak bisa di-fokus/di-scroll
                 ke (invisible), browser DIAM-DIAM memblokir submit tanpa
                 pesan/bubble apa pun ("form seperti macet"), dan handler
                 submit custom di bawah (validateUserForm - yang sudah benar
                 menampilkan modal peringatan & pindah ke tab yang benar)
                 tidak pernah sempat berjalan sama sekali. novalidate
                 menonaktifkan validasi bawaan sepenuhnya, disiplin validasi
                 sekarang 100% ditangani manual lewat JS di bawah. -->
            <form id="userForm" enctype="multipart/form-data" class="space-y-5" novalidate>
                <input type="hidden" name="id_user" id="inputId">
                <input type="hidden" name="status_account" id="inputStatusAccount" value="ACTIVE">

                <div class="flex gap-1 bg-gray-100 p-1 rounded-xl">
                    <button type="button" onclick="switchUserTab('akun')" id="tabBtnAkun" class="tab-btn-user active flex-1 py-2 rounded-lg text-xs font-bold flex items-center justify-center gap-2">
                        <i class="fas fa-user-shield"></i> Data Akun
                    </button>
                    <button type="button" onclick="switchUserTab('pribadi')" id="tabBtnPribadi" class="tab-btn-user inactive flex-1 py-2 rounded-lg text-xs font-bold flex items-center justify-center gap-2">
                        <i class="fas fa-id-card"></i> Data Pribadi
                    </button>
                </div>

                <!-- TAB: DATA AKUN -->
                <div id="tabAkun">
                    <div class="bg-blue-50/50 p-5 rounded-2xl border border-blue-100 space-y-5">
                        <h4 class="text-xs font-bold text-blue-600 uppercase tracking-widest">Informasi Akun &amp; Akses</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 mb-1">Email <span class="text-red-500">*</span></label>
                                <input type="email" name="email" id="inputEmail" required class="w-full p-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">Role Akun <span class="text-red-500">*</span></label>
                                <select name="role" id="inputRole" onchange="toggleRoleFields()" required class="w-full p-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none cursor-pointer">
                                    <option value="" disabled selected>Role</option>
                                    <option value="User">User (Asisten)</option>
                                    <option value="Admin">Admin</option>
                                    <option value="Kepala Lab">Kepala Lab</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">Jabatan <span class="text-red-500">*</span></label>
                                <select name="position" id="inputPosition" required class="w-full p-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none cursor-pointer">
                                    <option value="" disabled selected>Pilih Jabatan</option>
                                    <option value="Kepala Lab">Kepala Lab</option>
                                    <option value="Laboran">Laboran</option>
                                    <option value="Koordinator Asisten">Koordinator Asisten</option>
                                    <option value="Asisten 1">Asisten 1</option>
                                    <option value="Asisten 2">Asisten 2</option>
                                    <option value="Asisten Pendamping">Asisten Pendamping</option>
                                </select>
                            </div>
                        </div>

                        <div class="border-t border-blue-100 pt-4">
                            <!-- [BARU – Tahap 30] Toggle Aktif/Nonaktif Akun (hanya tampil di mode Edit) -->
                            <div id="accountStatusToggleWrap" style="display:none" class="items-center justify-between bg-orange-50 border border-orange-200 p-3 rounded-xl mb-3">
                                <div>
                                    <p class="text-xs font-bold text-orange-700 flex items-center gap-1.5">
                                        <i class="fas fa-user-shield text-orange-500"></i> Status Akun
                                    </p>
                                    <p class="text-[10px] text-orange-500 mt-0.5" id="accountStatusHint">Akun aktif — user dapat login dan mengakses semua fitur.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer ml-3 shrink-0">
                                    <input type="checkbox" id="inputAccountActive" onchange="handleAccountStatusChange()" class="sr-only peer" checked>
                                    <div class="w-10 h-5 bg-red-300 peer-checked:bg-green-500 rounded-full transition-colors relative after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-5"></div>
                                </label>
                            </div>

                            <div id="passwordToggleWrap" class="hidden flex items-center justify-between bg-white p-3 rounded-xl border border-gray-200 mb-3">
                                <div>
                                    <p class="text-xs font-bold text-gray-700">Ganti Password</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">Aktifkan jika ingin mengubah password akun ini.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="inputChangePassword" onchange="toggleChangePassword()" class="sr-only peer">
                                    <div class="w-10 h-5 bg-gray-300 peer-checked:bg-blue-600 rounded-full transition-colors relative after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-5"></div>
                                </label>
                            </div>
                            <div id="passwordFieldWrap">
                                <label class="block text-xs font-bold text-gray-500 mb-1"><span id="passLabelText">Password</span> <span class="text-red-500" id="passReq">*</span></label>
                                <input type="password" name="password" id="inputPass" minlength="8" class="w-full p-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none" placeholder="Minimal 8 karakter">
                                <p class="text-[10px] text-gray-400 mt-1" id="passHint"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: DATA PRIBADI -->
                <div id="tabPribadi" class="hidden space-y-5">
                    <div>
                        <h4 class="text-xs font-bold text-gray-400 uppercase mb-4 tracking-widest border-b pb-2">Data Pribadi</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                                <input type="text" name="name" id="inputName" required class="w-full p-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none font-bold">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">Jenis Kelamin</label>
                                <select name="gender" id="inputGender" class="w-full p-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none cursor-pointer">
                                    <option value="" disabled selected>Pilih</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">No. WhatsApp</label>
                                <!-- [BARU] Kode negara (default Indonesia) + input angka saja
                                     (filter otomatis via .js-phone-digits, lihat global.js). -->
                                <div class="flex gap-2">
                                    <select id="inputPhoneCountry" class="shrink-0 w-[92px] p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold focus:border-blue-500 outline-none cursor-pointer">
                                        <option value="62" selected>🇮🇩 +62</option>
                                        <option value="60">🇲🇾 +60</option>
                                        <option value="65">🇸🇬 +65</option>
                                        <option value="1">🇺🇸 +1</option>
                                        <option value="44">🇬🇧 +44</option>
                                        <option value="61">🇦🇺 +61</option>
                                        <option value="81">🇯🇵 +81</option>
                                    </select>
                                    <input type="tel" name="phone" id="inputPhone" inputmode="numeric" pattern="[0-9]*" maxlength="15" placeholder="81234567890" class="js-phone-digits flex-1 min-w-0 p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none font-mono">
                                </div>
                            </div>

                            <div class="user-field md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">NIM</label>
                                    <input type="text" name="nim" id="inputNim" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none font-mono">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Kelas <span class="text-red-500">*</span></label>
                                    <select name="class" id="inputClass" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition cursor-pointer">
                                        <option value="" disabled selected>Pilih Kelas</option>
                                        <?php
                                            $daftarKelas = [
                                                'A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'A7', 'A8', 'A9', 'A10',
                                                'B1', 'B2', 'B3', 'B4', 'B5', 'C1', 'C2', 'C3', 'C4', 'C5'
                                            ];
                                            foreach($daftarKelas as $k):
                                        ?>
                                            <option value="<?= $k ?>"><?= $k ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Angkatan</label>
                                    <input type="text" name="angkatan" id="inputAngkatan" maxlength="4" inputmode="numeric" pattern="\d{4}" placeholder="Contoh: 2023" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none font-mono">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Program Studi</label>
                                    <select name="prodi" id="inputProdi" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none cursor-pointer">
                                        <option value="" disabled selected>Pilih Prodi</option>
                                        <option value="Sistem Informasi">Sistem Informasi</option>
                                        <option value="Teknik Informatika">Teknik Informatika</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Peminatan</label>
                                    <select name="interest" id="inputInterest" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none cursor-pointer">
                                        <option value="" disabled selected>Pilih Peminatan</option>
                                        <option value="RPL">Rekayasa Perangkat Lunak (RPL)</option>
                                        <option value="Jaringan">Jaringan Komputer</option>
                                        <option value="IoT">Internet of Things (IoT)</option>
                                        <option value="Multimedia">Multimedia</option>
                                        <option value="AI">Artificial Intelligence (AI)</option>
                                    </select>
                                </div>
                                <div class="lab-field">
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Laboratorium</label>
                                    <select name="lab_id" id="inputLab" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none cursor-pointer">
                                        <option value="">Pilih Lab</option>
                                        <?php foreach(($labs ?? []) as $lab): ?>
                                            <option value="<?= $lab['id_lab'] ?>"><?= $lab['nama_lab'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 mb-1">Alamat Domisili</label>
                                <textarea name="address" id="inputAddress" rows="2" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-blue-500 outline-none"></textarea>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 mb-1">Foto Profil</label>
                                <!-- [BARU - Fitur Crop Foto] sama seperti common/edit_profile.php:
                                     preview + tombol kamera membuka modal Cropper.js. Hasil potong
                                     dikirim via #croppedImage (base64); jika admin tidak mengganti
                                     foto, #photoInput tetap kosong dan foto lama dipertahankan. -->
                                <div class="flex items-center gap-4">
                                    <div class="relative shrink-0">
                                        <img id="previewImg" src="https://ui-avatars.com/api/?name=?&background=random&bold=true" class="w-16 h-16 rounded-full object-cover border-2 border-gray-100 shadow-sm">
                                        <label for="photoInput" class="absolute -bottom-1 -right-1 bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center cursor-pointer hover:bg-blue-700 transition border-2 border-white shadow-sm" title="Pilih &amp; Potong Foto">
                                            <i class="fas fa-camera text-[10px]"></i>
                                        </label>
                                        <input type="file" name="photo" id="photoInput" class="hidden" accept="image/png, image/jpeg, image/jpg, image/webp">
                                        <input type="hidden" name="cropped_image" id="croppedImage">
                                    </div>
                                    <div class="text-xs">
                                        <p id="currentPhotoLabel" class="hidden text-gray-600 font-bold mb-0.5">
                                            <i class="fas fa-check-circle text-green-500 mr-1"></i>Foto sudah ada: <span id="currentPhotoName" class="font-mono font-normal text-gray-500"></span>
                                        </p>
                                        <p class="text-gray-400">Klik ikon kamera untuk pilih &amp; potong foto baru.<br>Format: JPG, PNG, WEBP (Maks. 2MB).</p>
                                    </div>
                                </div>
                            </div>
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

<!-- [BARU] Konfirmasi Aktifkan/Nonaktifkan Akun dari modal Edit Pengguna -->
<div id="statusToggleModal" class="hidden fixed inset-0 z-[70] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="cancelStatusToggle()"></div>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm relative z-10 p-6 text-center transform scale-100 transition-all">
        <div id="statusToggleIcon" class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4"></div>
        <h3 id="statusToggleTitle" class="text-xl font-extrabold text-gray-800 mb-2"></h3>
        <p id="statusToggleMsg" class="text-sm text-gray-500 mb-6"></p>
        <div class="flex gap-3">
            <button onclick="cancelStatusToggle()" class="flex-1 py-2.5 rounded-xl border border-gray-300 text-gray-600 font-bold hover:bg-gray-50 transition">Batal</button>
            <button id="confirmStatusToggleBtn" onclick="confirmStatusToggle()" class="flex-1 py-2.5 rounded-xl bg-gray-700 text-white font-bold hover:bg-gray-800 shadow-lg transition">Ya</button>
        </div>
    </div>
</div>

<div id="alertModal" style="display:none" class="fixed inset-0 z-[60] items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm relative z-10 p-6 text-center transform scale-100 transition-all">
        <div id="alertIcon" class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4"></div>
        <h3 id="alertTitle" class="text-xl font-extrabold text-gray-800 mb-2"></h3>
        <p id="alertMsg" class="text-sm text-gray-500 mb-6"></p>
        <button onclick="window.location.reload()" class="w-full py-3 rounded-xl font-bold text-white shadow-lg transition" id="alertBtn">OK</button>
    </div>
</div>

<!-- [BARU - Fitur Crop Foto] Sama seperti common/edit_profile.php -->
<div id="cropperModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm" id="cropperBackdrop"></div>
    <div class="relative bg-white rounded-3xl overflow-hidden shadow-2xl w-full max-w-lg flex flex-col max-h-[90vh]">
        <div class="bg-gradient-to-r from-blue-700 to-cyan-600 circuit-pattern relative overflow-hidden px-6 py-4 flex justify-between items-center">
            <h3 class="font-bold text-white">Potong Foto</h3>
            <button id="closeModalBtn" class="text-blue-100 hover:text-white transition"><i class="fas fa-times"></i></button>
        </div>
        <div class="flex-1 bg-gray-900 relative flex items-center justify-center overflow-hidden h-[400px]">
            <img id="imageToCrop" src="" class="max-w-full max-h-full">
        </div>
        <div class="px-6 py-4 flex justify-end gap-3 bg-gray-50">
            <button id="cancelCropBtn" class="px-4 py-2 rounded-lg border text-gray-600 hover:bg-gray-100 font-bold text-sm">Batal</button>
            <button id="cropImageBtn" class="px-6 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 font-bold text-sm">Potong &amp; Simpan</button>
        </div>
    </div>
</div>
