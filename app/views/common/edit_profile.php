<div class="max-w-4xl mx-auto space-y-6 animate-enter pb-12">
    
    <div class="flex items-center gap-4 mb-2">
        <?php $roleLink = strtolower(str_replace(' ', '', $role)); ?>
        <a href="<?= BASE_URL ?>/<?= $roleLink ?>/profile" class="w-10 h-10 flex items-center justify-center bg-white rounded-full shadow-sm text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-extrabold text-gray-800">Edit Profil</h1>
            <p class="text-gray-500 text-sm">Lengkapi dan perbarui data diri Anda.</p>
        </div>
    </div>

    <?php if(!$isAdmin): ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg shadow-sm flex items-start gap-3">
        <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5"></i>
        <div>
            <h4 class="font-bold text-yellow-700 text-sm">Perhatian Penting!</h4>
            <p class="text-xs text-yellow-600 mt-1">
                Pastikan data benar. Anda hanya dapat melengkapi profil <strong>SATU KALI</strong>. Setelah disimpan, data akan dikunci.
            </p>
        </div>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-8">
        <form id="profileForm" enctype="multipart/form-data" class="space-y-6">
            
            <div class="flex flex-col items-center justify-center mb-6">
                <div class="relative group">
                    <?php 
                        $photoPath = !empty($user['photo_profile']) && file_exists("uploads/profile/" . $user['photo_profile']) 
                            ? BASE_URL . "/uploads/profile/" . $user['photo_profile'] 
                            : "https://ui-avatars.com/api/?name=" . urlencode($user['name']);
                    ?>
                    <img id="previewImg" src="<?= $photoPath ?>" class="w-32 h-32 rounded-full object-cover border-4 border-gray-100 shadow-md">
                    
                    <label for="photoInput" class="absolute bottom-0 right-0 bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center cursor-pointer hover:bg-blue-700 transition border-2 border-white shadow-sm z-10" title="Ganti Foto">
                        <i class="fas fa-camera text-xs"></i>
                    </label>
                    
                    <input type="file" name="photo" id="photoInput" class="hidden" accept="image/png, image/jpeg, image/jpg, image/webp">
                    <input type="hidden" name="cropped_image" id="croppedImage">
                </div>
                <p class="text-xs text-gray-400 mt-2">Format: JPG, PNG, WEBP (Max 2MB)</p>
            </div>

            <!-- [BARU - Edit Email/Password] Sama seperti form Tambah/Edit
                 Pengguna di Manajemen User Admin: email selalu bisa diubah,
                 password memakai toggle "Ganti Password" + minimal 8 karakter. -->
            <div class="bg-blue-50/50 p-5 rounded-2xl border border-blue-100 space-y-5">
                <h4 class="text-xs font-bold text-blue-600 uppercase tracking-widest">Akun &amp; Keamanan</h4>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="inputEmail" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required class="w-full p-3 bg-white border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition">
                </div>

                <div class="border-t border-blue-100 pt-4">
                    <div id="passwordToggleWrap" class="flex items-center justify-between bg-white p-3 rounded-xl border border-gray-200 mb-3">
                        <div>
                            <p class="text-xs font-bold text-gray-700">Ganti Password</p>
                            <p class="text-[10px] text-gray-400 mt-0.5">Aktifkan jika ingin mengubah password akun ini.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="inputChangePassword" class="sr-only peer">
                            <div class="w-10 h-5 bg-gray-300 peer-checked:bg-blue-600 rounded-full transition-colors relative after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-5"></div>
                        </label>
                    </div>
                    <div id="passwordFieldWrap" class="hidden">
                        <label class="block text-xs font-bold text-gray-500 mb-1">Password Baru <span class="text-red-500">*</span></label>
                        <input type="password" name="password" id="inputPass" minlength="8" class="w-full p-3 bg-white border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition" placeholder="Minimal 8 karakter">
                        <p class="text-[10px] text-gray-400 mt-1">Minimal 8 karakter.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition font-bold text-gray-700">
                </div>
                
                <?php if($isUser): ?>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">NIM <span class="text-red-500">*</span></label>
                    <input type="text" name="nim" value="<?= htmlspecialchars($user['nim'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition font-mono">
                </div>
                <?php endif; ?>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">NIDN/NIP <span class="text-red-500">*</span></label>
                    <input type="text" name="nidn_nip" value="<?= $user['nidn_nip'] ?? '' ?>" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Jabatan <span class="text-red-500">*</span></label>
                    <select name="position" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition cursor-pointer">
                        <option value="" disabled <?= htmlspecialchars(empty($user['position']) ? 'selected' : '', ENT_QUOTES, 'UTF-8') ?>>Pilih Jabatan</option>
                        <?php 
                            // [PERBAIKAN] Opsi Jabatan disaring sesuai role dan disamakan dengan
                            // ENUM kolom profile.jabatan ('Kepala Lab','Laboran','Koordinator Asisten',
                            // 'Asisten 1','Asisten 2','Asisten Pendamping') agar tidak ada nilai
                            // tidak valid yang membuat data jabatan hilang saat disimpan.
                            $positions = match($role) {
                                'Kepala Lab' => ['Kepala Lab'],
                                'Admin'      => ['Laboran', 'Koordinator Asisten'],
                                default      => ['Asisten 1', 'Asisten 2', 'Asisten Pendamping'],
                            };
                            foreach($positions as $pos): 
                        ?>
                            <option value="<?= $pos ?>" <?= htmlspecialchars(($user['position'] ?? '') == $pos ? 'selected' : '', ENT_QUOTES, 'UTF-8') ?>><?= $pos ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">No. WhatsApp <span class="text-red-500">*</span></label>
                    <?php
                        // [BARU] Kode negara (default Indonesia) + input angka saja.
                        // Baca kembali no_telp lama (format lokal "0..." atau yang
                        // sudah "+<kode>...") supaya select & digit terisi benar.
                        $rawPhone = $user['no_telp'] ?? '';
                        $phoneCountry = '62';
                        $phoneDigits = preg_replace('/\D/', '', $rawPhone);
                        if (str_starts_with($rawPhone, '+')) {
                            if (preg_match('/^\+(62|60|65|1|44|61|81)(\d+)$/', $rawPhone, $m)) {
                                $phoneCountry = $m[1];
                                $phoneDigits = $m[2];
                            }
                        } elseif (str_starts_with($phoneDigits, '0')) {
                            $phoneDigits = substr($phoneDigits, 1);
                        }
                    ?>
                    <div class="flex gap-2">
                        <select id="inputPhoneCountry" class="shrink-0 w-[92px] p-3 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold focus:outline-none focus:border-blue-500 transition cursor-pointer">
                            <option value="62" <?= $phoneCountry === '62' ? 'selected' : '' ?>>🇮🇩 +62</option>
                            <option value="60" <?= $phoneCountry === '60' ? 'selected' : '' ?>>🇲🇾 +60</option>
                            <option value="65" <?= $phoneCountry === '65' ? 'selected' : '' ?>>🇸🇬 +65</option>
                            <option value="1" <?= $phoneCountry === '1' ? 'selected' : '' ?>>🇺🇸 +1</option>
                            <option value="44" <?= $phoneCountry === '44' ? 'selected' : '' ?>>🇬🇧 +44</option>
                            <option value="61" <?= $phoneCountry === '61' ? 'selected' : '' ?>>🇦🇺 +61</option>
                            <option value="81" <?= $phoneCountry === '81' ? 'selected' : '' ?>>🇯🇵 +81</option>
                        </select>
                        <input type="tel" name="phone" id="inputPhone" value="<?= htmlspecialchars($phoneDigits, ENT_QUOTES, 'UTF-8') ?>" required inputmode="numeric" pattern="[0-9]*" maxlength="15" placeholder="81234567890" class="js-phone-digits flex-1 min-w-0 p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition font-mono">
                    </div>
                </div>

             <?php if($isUser): ?>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Program Studi</label>
                    
                    <select name="prodi" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition cursor-pointer">
                        <option value="" disabled <?= htmlspecialchars(empty($user['prodi']) ? 'selected' : '', ENT_QUOTES, 'UTF-8') ?>>Pilih Prodi</option>
                        
                        <?php 
                            // Daftar Prodi (Samakan dengan yang ada di Admin)
                            $daftarProdi = [
                                'Sistem Informasi', 
                                'Teknik Informatika'
                            ];

                            foreach($daftarProdi as $p): 
                        ?>
                            <option value="<?= $p ?>" <?= htmlspecialchars(($user['prodi'] ?? '') == $p ? 'selected' : '', ENT_QUOTES, 'UTF-8') ?>>
                                <?= $p ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

                <?php if($isUser): ?>
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
                                    $cek = ($user['kelas'] ?? '') == $k ? 'selected' : '';
                            ?>
                                <option value="<?= $k ?>" <?= $cek ?>><?= $k ?></option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Angkatan <span class="text-red-500">*</span></label>
                        <input type="text" name="angkatan" value="<?= htmlspecialchars($user['angkatan'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required maxlength="4" inputmode="numeric" pattern="\d{4}" placeholder="Contoh: 2023" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition font-mono">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Laboratorium <span class="text-red-500">*</span></label>
                        <select name="lab_id" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition cursor-pointer">
                            <option value="" disabled <?= empty($user['id_lab']) ? 'selected' : '' ?>>Pilih Laboratorium</option>
                            
                            <?php 
                            if(!empty($labs)): 
                                foreach($labs as $lab): 
                            ?>
                                <option value="<?= $lab['id_lab'] ?>" <?= ($user['id_lab'] ?? '') == $lab['id_lab'] ? 'selected' : '' ?>>
                                    <?= $lab['nama_lab'] ?>
                                </option>
                            <?php 
                                endforeach;
                            endif; 
                            ?>
                            
                        </select>
                    </div>
                <?php endif; ?>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Jenis Kelamin <span class="text-red-500">*</span></label>
                    <select name="gender" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition cursor-pointer">
                        <option value="" disabled <?= empty($user['jenis_kelamin']) ? 'selected' : '' ?>>Pilih</option>
                        <option value="L" <?= $user['jenis_kelamin'] == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= $user['jenis_kelamin'] == 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>

                <?php if($isUser): ?>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Peminatan <span class="text-red-500">*</span></label>
                    <select name="interest" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition cursor-pointer">
                        <option value="" disabled <?= htmlspecialchars(empty($user['peminatan']) ? 'selected' : '', ENT_QUOTES, 'UTF-8') ?>>Pilih Peminatan</option>
                        <option value="RPL" <?= htmlspecialchars($user['peminatan'] == 'RPL' ? 'selected' : '', ENT_QUOTES, 'UTF-8') ?>>Rekayasa Perangkat Lunak (RPL)</option>
                        <option value="Jaringan" <?= htmlspecialchars($user['peminatan'] == 'Jaringan' ? 'selected' : '', ENT_QUOTES, 'UTF-8') ?>>Jaringan Komputer</option>
                        <option value="IoT" <?= htmlspecialchars($user['peminatan'] == 'IoT' ? 'selected' : '', ENT_QUOTES, 'UTF-8') ?>>Internet of Things (IoT)</option>
                        <option value="Multimedia" <?= htmlspecialchars($user['peminatan'] == 'Multimedia' ? 'selected' : '', ENT_QUOTES, 'UTF-8') ?>>Multimedia</option>
                        <option value="AI" <?= htmlspecialchars($user['peminatan'] == 'AI' ? 'selected' : '', ENT_QUOTES, 'UTF-8') ?>>Artificial Intelligence (AI)</option>
                    </select>
                </div>
                <?php endif; ?>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Alamat Domisili <span class="text-red-500">*</span></label>
                    <textarea name="address" rows="2" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition"><?= htmlspecialchars($user['alamat'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <div class="flex justify-end pt-6 border-t border-gray-100">
                <button type="submit" id="saveBtn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-8 py-3 rounded-xl shadow-lg transition transform hover:scale-105 flex items-center gap-2">
                    <i class="fas fa-save"></i> 
                    <?= $isAdmin ? 'Simpan Perubahan' : 'Simpan Permanen' ?>
                </button>
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

<div id="confirmModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeConfirmModal()"></div>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm relative z-10 p-6 text-center">
        <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">
            <i class="fas fa-question"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Simpan Data?</h3>
        <p class="text-xs text-gray-500 mb-5">
            <?= $isAdmin ? 'Pastikan data yang Anda masukkan sudah benar.' : 'Pastikan data sudah benar. Data akan dikunci setelah disimpan.' ?>
        </p>
        <div class="flex gap-3">
            <button onclick="closeConfirmModal()" class="flex-1 py-2.5 rounded-xl border border-gray-300 text-gray-600 font-bold hover:bg-gray-50">Batal</button>
            <button id="confirmYesBtn" class="flex-1 py-2.5 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg">Ya, Simpan</button>
        </div>
    </div>
</div>

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
            <button id="cropImageBtn" class="px-6 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 font-bold text-sm">Potong & Simpan</button>
        </div>
    </div>
</div>

