(function loadWebcamLib() {
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js';
    script.onload = initCamera;
    document.head.appendChild(script);
})();

function initCamera() {
    if (!window.Webcam) {
        console.error('WebcamJS gagal dimuat');
        return;
    }

    Webcam.set({
        width: 320,
        height: 240,
        image_format: 'jpeg',
        jpeg_quality: 90
    });

    Webcam.attach('#my_camera');
}

document.addEventListener('DOMContentLoaded', () => {
    const captureBtn = document.getElementById('btn-capture');
    const submitBtn = document.getElementById('btn-submit');

    if (captureBtn) {
        captureBtn.addEventListener('click', takeSnapshot);
    }

    if (submitBtn) {
        submitBtn.addEventListener('click', submitAttendance);
    }
});

function takeSnapshot() {
    if (!window.Webcam) return;

    Webcam.snap(dataUri => {
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            canvas.width = img.width;
            canvas.height = img.height;
            ctx.drawImage(img, 0, 0);

            // Watermark waktu
            const dateText = new Date().toLocaleString('id-ID');
            ctx.font = 'bold 16px Arial';
            ctx.fillStyle = 'yellow';
            ctx.shadowColor = 'black';
            ctx.shadowBlur = 4;
            ctx.fillText(dateText, 10, img.height - 20);

            const finalImage = canvas.toDataURL('image/jpeg');

            renderPreview(finalImage);
        };
        img.src = dataUri;
    });
}

function renderPreview(imageData) {
    const camera = document.getElementById('my_camera');
    const result = document.getElementById('result');
    const photoInput = document.getElementById('photo_data');
    const submitBtn = document.getElementById('btn-submit');

    camera.style.display = 'none';

    result.innerHTML = `<img src="${imageData}" alt="Preview">`;
    result.classList.remove('hidden');

    photoInput.value = imageData;
    submitBtn.classList.remove('hidden');
}

function submitAttendance() {
    const photo = document.getElementById('photo_data').value;
    const type = document.getElementById('attendance_type').value;

    if (!photo) {
        alert('Silakan ambil foto terlebih dahulu.');
        return;
    }

    const formData = new FormData();
    formData.append('photo', photo);
    formData.append('type', type);

    fetch(`${BASE_URL}/user/submitAttendance`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Presensi berhasil dikirim!');
            window.location.href = `${BASE_URL}/user/dashboard`;
        } else {
            alert(data.message || 'Gagal mengirim presensi.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Terjadi kesalahan koneksi.');
    });
}
