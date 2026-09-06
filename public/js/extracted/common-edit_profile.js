
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
        else url = '<?= BASE_URL ?>/kepalalab/updateProfile';

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
