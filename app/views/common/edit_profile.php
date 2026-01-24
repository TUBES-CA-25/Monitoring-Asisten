<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css"/>

<style>
    .animate-enter { animation: fadeInUp 0.5s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>

<div class="max-w-4xl mx-auto space-y-6 animate-enter pb-12">
    
    <div class="flex items-center gap-4 mb-2">
        <?php $roleLink = strtolower(str_replace(' ', '', $_SESSION['role'])); ?>
        <a href="<?= BASE_URL ?>/<?= $roleLink ?>/profile" class="w-10 h-10 flex items-center justify-center bg-white rounded-full shadow-sm text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-extrabold text-gray-800">Edit Profil</h1>
            <p class="text-gray-500 text-sm">Lengkapi dan perbarui data diri Anda.</p>
        </div>
    </div>

    <?php if($_SESSION['role'] != 'Admin' && (empty($user['is_completed']) || $user['is_completed'] == 0)): ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg shadow-sm flex items-start gap-3">
        <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5"></i>
        <div>
            <h4 class="font-bold text-yellow-700 text-sm">Perhatian Penting!</h4>
            <p class="text-xs text-yellow-600 mt-1">
                Pastikan semua data diisi dengan benar. Anda hanya diberikan kesempatan <strong>SATU KALI</strong> untuk melengkapi profil ini. Setelah disimpan, data akan dikunci.
            </p>
        </div>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-8">
        <form id="profileForm" enctype="multipart/form-data" class="space-y-6">
            
            <div class="flex flex-col items-center justify-center mb-6">
                <div class="relative group">
                    <?php 
                        $photoPath = !empty($user['photo_profile']) && file_exists("../public/uploads/profile/" . $user['photo_profile']) 
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
                <p class="text-xs text-gray-400 mt-2">Klik ikon kamera untuk mengganti foto profil</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="<?= $user['name'] ?>" required minlength="3" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition font-bold text-gray-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">NIM / ID Pengguna <span class="text-red-500">*</span></label>
                    <input type="text" name="nim" value="<?= $user['nim'] ?? '' ?>" required minlength="5" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Jabatan <span class="text-red-500">*</span></label>
                    <select name="position" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition cursor-pointer">
                        <option value="" disabled <?= empty($user['position']) ? 'selected' : '' ?>>-- Pilih Jabatan --</option>
                        <option value="Kepala Lab" <?= ($user['position'] ?? '') == 'Kepala Lab' ? 'selected' : '' ?>>Kepala Lab</option>
                        <option value="Laboran" <?= ($user['position'] ?? '') == 'Laboran' ? 'selected' : '' ?>>Laboran</option>
                        <option value="Asisten Lab" <?= ($user['position'] ?? '') == 'Asisten Lab' ? 'selected' : '' ?>>Asisten Lab</option>
                        <option value="Anggota" <?= ($user['position'] ?? '') == 'Anggota' ? 'selected' : '' ?>>Anggota</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">No. WhatsApp / HP <span class="text-red-500">*</span></label>
                    <input type="tel" name="phone" value="<?= $user['no_telp'] ?>" required pattern="[0-9]+" minlength="10" maxlength="15" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition font-mono">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Alamat Domisili <span class="text-red-500">*</span></label>
                    <textarea name="address" rows="2" required minlength="10" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition"><?= $user['alamat'] ?></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Jenis Kelamin <span class="text-red-500">*</span></label>
                    <select name="gender" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition cursor-pointer">
                        <option value="" disabled <?= empty($user['jenis_kelamin']) ? 'selected' : '' ?>>-- Pilih --</option>
                        <option value="L" <?= $user['jenis_kelamin'] == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= $user['jenis_kelamin'] == 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
                <?php if($_SESSION['role'] == 'User'): ?>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Peminatan <span class="text-red-500">*</span></label>
                    <select name="interest" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition cursor-pointer">
                        <option value="" disabled <?= empty($user['peminatan']) ? 'selected' : '' ?>>-- Pilih Minat --</option>
                        <option value="RPL" <?= $user['peminatan'] == 'RPL' ? 'selected' : '' ?>>RPL (Software)</option>
                        <option value="Jaringan" <?= $user['peminatan'] == 'Jaringan' ? 'selected' : '' ?>>Jaringan (Network)</option>
                        <option value="Multimedia" <?= $user['peminatan'] == 'Multimedia' ? 'selected' : '' ?>>Multimedia</option>
                        <option value="AI" <?= $user['peminatan'] == 'AI' ? 'selected' : '' ?>>Artificial Intelligence</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <div class="flex justify-end pt-6 border-t border-gray-100">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-8 py-3 rounded-xl shadow-lg transition transform hover:scale-105 flex items-center gap-2">
                    <i class="fas fa-save"></i> Simpan Lengkap
                </button>
            </div>
        </form>
    </div>
</div>

<div id="cropperModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm transition-opacity opacity-0" id="cropperBackdrop"></div>
    <div class="relative bg-white rounded-3xl overflow-hidden shadow-2xl w-full max-w-lg transform scale-95 opacity-0 transition-all duration-300 flex flex-col max-h-[90vh]" id="cropperContent">
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-white z-10">
            <div>
                <h3 class="text-xl font-extrabold text-gray-800 tracking-tight">Sesuaikan Foto</h3>
                <p class="text-xs text-gray-500 mt-0.5">Geser dan atur ukuran kotak untuk memotong.</p>
            </div>
            <button type="button" id="closeModalBtn" class="w-8 h-8 rounded-full bg-gray-50 hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition"><i class="fas fa-times"></i></button>
        </div>
        <div class="flex-1 bg-gray-900 relative flex items-center justify-center overflow-hidden h-[400px] md:h-[500px]">
            <img id="imageToCrop" src="" alt="Crop Preview" class="block max-w-full max-h-full mx-auto">
        </div>
        <div class="px-6 py-5 border-t border-gray-100 bg-gray-50/50 flex flex-col-reverse sm:flex-row justify-end gap-3 z-10">
            <button type="button" id="cancelCropBtn" class="w-full sm:w-auto px-6 py-2.5 rounded-xl border border-gray-300 text-gray-600 font-bold text-sm hover:bg-gray-100 hover:text-gray-800 transition active:scale-95">Batal</button>
            <button type="button" id="cropImageBtn" class="w-full sm:w-auto px-8 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-bold text-sm shadow-lg shadow-blue-500/30 transition transform hover:-translate-y-0.5 active:scale-95 flex items-center justify-center gap-2"><i class="fas fa-crop-simple"></i><span>Potong & Simpan</span></button>
        </div>
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
        <button onclick="closeCustomAlertAndRedirect()" class="w-full py-3 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02]" id="alertBtn">OK</button>
    </div>
</div>

<div id="customConfirmModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="confirmBackdrop"></div>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative z-10 overflow-hidden transform scale-90 opacity-0 transition-all duration-300 flex flex-col items-center p-6 text-center" id="confirmContent">
        <div class="w-16 h-16 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center mb-4">
            <i class="fas fa-question-circle text-3xl"></i>
        </div>
        <h3 class="text-xl font-extrabold text-gray-800 mb-2">Simpan Perubahan?</h3>
        <p class="text-sm text-gray-500 mb-6 px-2" id="confirmMessage">Pastikan data yang Anda masukkan sudah benar.</p>
        <div class="flex gap-3 w-full">
            <button onclick="closeCustomConfirm()" class="flex-1 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold hover:bg-gray-50 transition">Batal</button>
            <button id="confirmYesBtn" class="flex-1 py-3 rounded-xl bg-blue-600 text-white font-bold shadow-lg hover:bg-blue-700 transition">Ya, Simpan</button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<script>
    const userRole = '<?= $_SESSION['role'] ?>';
    let shouldRedirect = false; // Flag untuk redirect setelah sukses

    // --- ALERT LOGIC ---
    function showCustomAlert(type, title, message, redirect = false) {
        shouldRedirect = redirect;
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

    function closeCustomAlertAndRedirect() {
        const modal = document.getElementById('customAlertModal');
        const content = document.getElementById('alertContent');
        const backdrop = document.getElementById('alertBackdrop');
        backdrop.classList.add('opacity-0');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-90', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            // JIKA SUKSES, BARU REDIRECT
            if(shouldRedirect) {
                window.location.href = '<?= BASE_URL ?>/<?= $roleLink ?>/profile';
            }
        }, 300);
    }

    // --- CONFIRM & SUBMIT LOGIC ---
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        e.preventDefault(); 
        let msg = 'Simpan perubahan profil?';
        if (userRole !== 'Admin') {
            msg = 'Pastikan semua data sudah benar. Data akan dikunci dan tidak dapat diubah lagi setelah disimpan.';
        }
        showCustomConfirm(msg);
    });

    function showCustomConfirm(message) {
        const modal = document.getElementById('customConfirmModal');
        const content = document.getElementById('confirmContent');
        const backdrop = document.getElementById('confirmBackdrop');
        document.getElementById('confirmMessage').innerText = message;

        modal.classList.remove('hidden');
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            content.classList.remove('scale-90', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeCustomConfirm() {
        const modal = document.getElementById('customConfirmModal');
        const content = document.getElementById('confirmContent');
        const backdrop = document.getElementById('confirmBackdrop');
        backdrop.classList.add('opacity-0');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-90', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    document.getElementById('confirmYesBtn').addEventListener('click', function() {
        closeCustomConfirm();
        submitProfileData();
    });

    // --- AJAX SUBMIT FUNCTION ---
    function submitProfileData() {
        const form = document.getElementById('profileForm');
        const formData = new FormData(form);

        fetch('<?= BASE_URL ?>/<?= $roleLink ?>/updateProfile', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Tampilkan Modal Sukses DULU, Redirect nanti setelah klik OK
                showCustomAlert('success', 'Tersimpan!', data.message, true);
            } else {
                showCustomAlert('error', 'Gagal!', data.message, false);
            }
        })
        .catch(error => {
            showCustomAlert('error', 'Error!', 'Terjadi kesalahan sistem.', false);
            console.error(error);
        });
    }

    // --- CROPPER LOGIC (TETAP SAMA) ---
    document.addEventListener('DOMContentLoaded', function () {
        const photoInput = document.getElementById('photoInput');
        const previewImg = document.getElementById('previewImg');
        const croppedImageInput = document.getElementById('croppedImage');
        const cropperModal = document.getElementById('cropperModal');
        const cropperBackdrop = document.getElementById('cropperBackdrop');
        const cropperContent = document.getElementById('cropperContent');
        const imageToCrop = document.getElementById('imageToCrop');
        const closeBtn = document.getElementById('closeModalBtn');
        const cancelBtn = document.getElementById('cancelCropBtn');
        const cropBtn = document.getElementById('cropImageBtn');
        let cropper;

        function openModal() {
            cropperModal.classList.remove('hidden');
            cropperModal.classList.add('flex');
            setTimeout(() => {
                cropperBackdrop.classList.remove('opacity-0');
                cropperContent.classList.remove('scale-95', 'opacity-0');
                cropperContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeModal() {
            cropperBackdrop.classList.add('opacity-0');
            cropperContent.classList.remove('scale-100', 'opacity-100');
            cropperContent.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                cropperModal.classList.add('hidden');
                cropperModal.classList.remove('flex');
                if (cropper) { cropper.destroy(); cropper = null; }
                if (!croppedImageInput.value) photoInput.value = ''; 
            }, 300);
        }

        photoInput.addEventListener('change', function (e) {
            const files = e.target.files;
            if (files && files.length > 0) {
                const file = files[0];
                if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
                    showCustomAlert('error', 'Format Salah', 'Mohon pilih file gambar (JPG, JPEG, PNG).', false);
                    photoInput.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (event) {
                    if (cropper) cropper.destroy();
                    imageToCrop.src = reader.result;
                    openModal();
                    setTimeout(() => {
                        cropper = new Cropper(imageToCrop, {
                            aspectRatio: 1, viewMode: 1, dragMode: 'move', autoCropArea: 0.8, restore: false, guides: true, center: true, highlight: false, cropBoxMovable: true, cropBoxResizable: true, toggleDragModeOnDblclick: false, background: false
                        });
                    }, 150);
                };
                reader.readAsDataURL(file);
            }
        });

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        cropBtn.addEventListener('click', function () {
            if (!cropper) return;
            const canvas = cropper.getCroppedCanvas({
                width: 500, height: 500, fillColor: '#fff', imageSmoothingEnabled: true, imageSmoothingQuality: 'high',
            });
            const base64Image = canvas.toDataURL('image/jpeg', 0.9);
            previewImg.src = base64Image;
            croppedImageInput.value = base64Image;
            
            cropperBackdrop.classList.add('opacity-0');
            cropperContent.classList.remove('scale-100', 'opacity-100');
            cropperContent.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                cropperModal.classList.add('hidden');
                cropperModal.classList.remove('flex');
                if (cropper) { cropper.destroy(); cropper = null; }
            }, 300);
        });
    });
</script>