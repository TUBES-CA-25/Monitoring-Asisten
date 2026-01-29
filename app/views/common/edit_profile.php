<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css"/>

<style>
    .animate-enter { animation: fadeInUp 0.5s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>

<?php
    $role = $_SESSION['role'];
    $isUser = ($role == 'User');
    $isAdmin = ($role == 'Admin');

    // Ambil Data Laboratorium untuk Dropdown (Hanya jika User)
    $labs = [];
    if ($isUser) {
        $db = new Database();
        // [FIX] Menggunakan tabel 'lab' sesuai database
        $db->query("SELECT * FROM lab ORDER BY nama_lab ASC");
        $labs = $db->resultSet();
    }
?>

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
                    
                    <input type="file" name="photo" id="photoInput" class="hidden" accept="image/png, image/jpeg, image/jpg">
                    <input type="hidden" name="cropped_image" id="croppedImage">
                </div>
                <p class="text-xs text-gray-400 mt-2">Format: JPG, PNG (Max 2MB)</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="<?= $user['name'] ?>" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition font-bold text-gray-700">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">
                        <?= $isUser ? 'NIM' : 'ID Pengguna' ?> <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nim" value="<?= $user['nim'] ?? '' ?>" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition font-mono">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Jabatan <span class="text-red-500">*</span></label>
                    <select name="position" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition cursor-pointer">
                        <option value="" disabled <?= empty($user['position']) ? 'selected' : '' ?>>-- Pilih Jabatan --</option>
                        <?php 
                            $positions = ['Kepala Lab', 'Laboran', 'Asisten 1', 'Asisten 2', 'Asisten Pendamping', 'Administrator', 'Pengawas Lab'];
                            foreach($positions as $pos): 
                        ?>
                            <option value="<?= $pos ?>" <?= ($user['position'] ?? '') == $pos ? 'selected' : '' ?>><?= $pos ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">No. WhatsApp <span class="text-red-500">*</span></label>
                    <input type="tel" name="phone" value="<?= $user['no_telp'] ?>" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition font-mono">
                </div>

                <?php if($isUser): ?>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Program Studi</label>
                    <input type="text" name="prodi" value="<?= $user['prodi'] ?? '' ?>" placeholder="Contoh: Teknik Informatika" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition">
                </div>
                <?php endif; ?>

                <?php if($isUser): ?>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Kelas <span class="text-red-500">*</span></label>
                        <input type="text" name="class" value="<?= $user['kelas'] ?? '' ?>" required placeholder="Contoh: TI-3A" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition font-mono uppercase">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Laboratorium <span class="text-red-500">*</span></label>
                        <select name="lab_id" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition cursor-pointer">
                            <option value="" disabled <?= empty($user['id_lab']) ? 'selected' : '' ?>>-- Pilih Laboratorium --</option>
                            <?php foreach($labs as $lab): ?>
                                <option value="<?= $lab['id_lab'] ?>" <?= ($user['id_lab'] ?? '') == $lab['id_lab'] ? 'selected' : '' ?>>
                                    <?= $lab['nama_lab'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Alamat Domisili <span class="text-red-500">*</span></label>
                    <textarea name="address" rows="2" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition"><?= $user['alamat'] ?></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Jenis Kelamin <span class="text-red-500">*</span></label>
                    <select name="gender" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition cursor-pointer">
                        <option value="" disabled <?= empty($user['jenis_kelamin']) ? 'selected' : '' ?>>-- Pilih --</option>
                        <option value="L" <?= $user['jenis_kelamin'] == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= $user['jenis_kelamin'] == 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>

                <?php if($isUser): ?>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Peminatan <span class="text-red-500">*</span></label>
                    <select name="interest" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition cursor-pointer">
                        <option value="" disabled <?= empty($user['peminatan']) ? 'selected' : '' ?>>-- Pilih Peminatan --</option>
                        <option value="RPL" <?= $user['peminatan'] == 'RPL' ? 'selected' : '' ?>>RPL</option>
                        <option value="Jaringan" <?= $user['peminatan'] == 'Jaringan' ? 'selected' : '' ?>>Jaringan</option>
                        <option value="IoT" <?= $user['peminatan'] == 'IoT' ? 'selected' : '' ?>>IoT</option>
                        <option value="Multimedia" <?= $user['peminatan'] == 'Multimedia' ? 'selected' : '' ?>>Multimedia</option>
                        <option value="AI" <?= $user['peminatan'] == 'AI' ? 'selected' : '' ?>>AI</option>
                    </select>
                </div>
                <?php endif; ?>
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
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 class="font-bold">Potong Foto</h3>
            <button id="closeModalBtn" class="text-gray-400 hover:text-red-500"><i class="fas fa-times"></i></button>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<script>
    let redirectUrl = null;

    // --- 1. HANDLING FORM SUBMIT (AJAX) ---
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        e.preventDefault(); 
        document.getElementById('confirmModal').classList.remove('hidden');
    });

    function closeConfirmModal() {
        document.getElementById('confirmModal').classList.add('hidden');
    }

    document.getElementById('confirmYesBtn').addEventListener('click', function() {
        closeConfirmModal();
        submitData();
    });

    function submitData() {
        const form = document.getElementById('profileForm');
        const formData = new FormData(form);
        const btn = document.getElementById('saveBtn');
        const originalText = btn.innerHTML;

        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Menyimpan...';
        btn.disabled = true;

        // URL Dinamis berdasarkan Role
        const role = "<?= $role ?>";
        let url = '';
        if (role === 'User') url = '<?= BASE_URL ?>/user/updateProfile';
        else if (role === 'Admin') url = '<?= BASE_URL ?>/admin/updateProfile';
        else url = '<?= BASE_URL ?>/superadmin/updateProfile';

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btn.innerHTML = originalText;
            btn.disabled = false;

            if (data.status === 'success') {
                redirectUrl = data.redirect;
                showCustomAlert('success', data.title, data.message);
            } else {
                showCustomAlert('error', data.title || 'Gagal', data.message);
            }
        })
        .catch(error => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            showCustomAlert('error', 'Error Sistem', 'Gagal menghubungi server.');
            console.error(error);
        });
    }

    // --- 2. ALERT MODAL LOGIC ---
    function showCustomAlert(type, title, message) {
        const modal = document.getElementById('customAlertModal');
        const iconBg = document.getElementById('alertIconBg');
        const icon = document.getElementById('alertIcon');
        const btn = document.getElementById('alertBtn');

        document.getElementById('alertTitle').innerText = title;
        document.getElementById('alertMessage').innerText = message;

        if (type === 'success') {
            iconBg.className = 'w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-green-100 text-green-600';
            icon.className = 'fas fa-check text-3xl';
            btn.className = 'w-full py-3 rounded-xl font-bold text-white shadow-lg bg-green-600 hover:bg-green-700 transition';
        } else {
            iconBg.className = 'w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-red-100 text-red-600';
            icon.className = 'fas fa-times text-3xl';
            btn.className = 'w-full py-3 rounded-xl font-bold text-white shadow-lg bg-red-600 hover:bg-red-700 transition';
        }

        modal.classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('alertBackdrop').classList.remove('opacity-0');
            document.getElementById('alertContent').classList.remove('scale-90', 'opacity-0');
            document.getElementById('alertContent').classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeCustomAlert() {
        const modal = document.getElementById('customAlertModal');
        document.getElementById('alertBackdrop').classList.add('opacity-0');
        document.getElementById('alertContent').classList.remove('scale-100', 'opacity-100');
        document.getElementById('alertContent').classList.add('scale-90', 'opacity-0');

        setTimeout(() => {
            modal.classList.add('hidden');
            if (redirectUrl) {
                window.location.href = redirectUrl;
            }
        }, 300);
    }

    // --- 3. CROPPER JS LOGIC ---
    document.addEventListener('DOMContentLoaded', function () {
        const photoInput = document.getElementById('photoInput');
        const previewImg = document.getElementById('previewImg');
        const croppedImageInput = document.getElementById('croppedImage');
        const cropperModal = document.getElementById('cropperModal');
        const imageToCrop = document.getElementById('imageToCrop');
        let cropper;

        photoInput.addEventListener('change', function (e) {
            const files = e.target.files;
            if (files && files.length > 0) {
                const file = files[0];
                const reader = new FileReader();
                reader.onload = function (event) {
                    imageToCrop.src = reader.result;
                    cropperModal.classList.remove('hidden');
                    cropperModal.classList.add('flex');
                    if(cropper) cropper.destroy();
                    cropper = new Cropper(imageToCrop, { aspectRatio: 1, viewMode: 1 });
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('cropImageBtn').addEventListener('click', function () {
            if (!cropper) return;
            const canvas = cropper.getCroppedCanvas({ width: 500, height: 500 });
            const base64Image = canvas.toDataURL('image/jpeg');
            previewImg.src = base64Image;
            croppedImageInput.value = base64Image;
            cropperModal.classList.add('hidden');
            cropperModal.classList.remove('flex');
        });

        document.getElementById('closeModalBtn').addEventListener('click', () => {
            cropperModal.classList.add('hidden');
            cropperModal.classList.remove('flex');
            photoInput.value = '';
        });
        document.getElementById('cancelCropBtn').addEventListener('click', () => {
            cropperModal.classList.add('hidden');
            cropperModal.classList.remove('flex');
            photoInput.value = '';
        });
    });
</script>