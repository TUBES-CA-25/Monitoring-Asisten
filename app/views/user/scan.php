<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Presensi - ICLABS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js"></script>
    <style>
        .scan-laser {
            position: absolute; top: 0; left: 0; width: 100%; height: 3px;
            background: #3b82f6; box-shadow: 0 0 10px #3b82f6;
            animation: scan 2s infinite ease-in-out; z-index: 20;
        }
        @keyframes scan { 0%, 100% {top: 0} 50% {top: 100%} }
        .animate-enter { animation: fadeInUp 0.5s ease-out; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; } }
        video { object-fit: cover; }
    </style>
</head>
<body class="bg-gray-900 h-screen flex flex-col items-center justify-center p-4 font-sans text-white">
    <a href="<?= BASE_URL ?>/user/dashboard" class="absolute top-6 left-6 z-50 bg-white/10 backdrop-blur-md px-4 py-2 rounded-full border border-white/10 text-sm">
        <i class="fas fa-arrow-left"></i> Batal
    </a>

    <div id="step-scan" class="w-full max-w-md text-center">
        <h1 class="text-2xl font-bold mb-1">Langkah 1: Scan QR</h1>
        <p class="text-gray-400 text-sm mb-8">Arahkan kamera ke QR Code di Lab.</p>
        <div class="relative aspect-square bg-black rounded-3xl overflow-hidden border-2 border-blue-500/50 shadow-2xl">
            <div id="qr-reader" class="w-full h-full"></div>
            <div class="scan-laser"></div>
        </div>
    </div>

    <div id="step-selfie" class="hidden w-full max-w-md text-center animate-enter">
        <h1 class="text-2xl font-bold mb-1">Langkah 2: Selfie</h1>
        <p class="text-gray-400 text-sm mb-6">Ambil foto sebagai bukti kehadiran.</p>
        <div class="relative aspect-[4/3] bg-black rounded-2xl overflow-hidden border-2 border-white/20 mb-6">
            <div id="my_camera" class="w-full h-full"></div>
            <div id="results" class="hidden absolute inset-0 bg-black z-20"></div>
        </div>
        <div class="flex gap-3">
            <button id="btn-take" onclick="take_snapshot()" class="flex-1 bg-blue-600 p-4 rounded-xl font-bold"><i class="fas fa-camera"></i> Ambil Foto</button>
            <button id="btn-retake" onclick="reset_camera()" class="hidden flex-1 bg-gray-600 p-4 rounded-xl">Ulangi</button>
            <button id="btn-submit" onclick="upload_presence()" class="hidden flex-1 bg-green-600 p-4 rounded-xl font-bold">Kirim</button>
        </div>
    </div>

    <input type="hidden" id="scanned-token">
    <input type="hidden" id="final-image-data">

    <script>
        let scanner = new Html5QrcodeScanner("qr-reader", { fps: 10, qrbox: 250 });

        // Mulai scanner langsung
        scanner.render(onScanSuccess);

        function onScanSuccess(decodedText) {
            scanner.clear().then(() => {
                document.getElementById('scanned-token').value = decodedText;
                document.getElementById('step-scan').classList.add('hidden');
                document.getElementById('step-selfie').classList.remove('hidden');
                initWebcam();
            });
        }

        function initWebcam() {
            Webcam.set({ width: 640, height: 480, image_format: 'jpeg', jpeg_quality: 90, facingMode: 'user' });
            Webcam.attach('#my_camera');
        }

        function take_snapshot() {
            Webcam.snap(data_uri => {
                let canvas = document.createElement('canvas');
                let ctx = canvas.getContext('2d');
                let img = new Image();
                img.onload = () => {
                    canvas.width = img.width; canvas.height = img.height;
                    ctx.drawImage(img, 0, 0);
                    // Watermark
                    ctx.fillStyle = "rgba(0,0,0,0.5)"; ctx.fillRect(0, canvas.height-60, canvas.width, 60);
                    ctx.fillStyle = "#fff"; ctx.font = "20px Arial";
                    ctx.fillText(new Date().toLocaleString('id-ID') + " | <?= $_SESSION['name'] ?>", 20, canvas.height-25);
                    
                    let finalImg = canvas.toDataURL('image/jpeg');
                    document.getElementById('final-image-data').value = finalImg;
                    document.getElementById('results').innerHTML = `<img src="${finalImg}" class="w-full h-full object-cover"/>`;
                    document.getElementById('results').classList.remove('hidden');
                    ['btn-take', 'my_camera'].forEach(id => document.getElementById(id).classList.add('hidden'));
                    ['btn-retake', 'btn-submit'].forEach(id => document.getElementById(id).classList.remove('hidden'));
                };
                img.src = data_uri;
            });
        }

        function reset_camera() {
            document.getElementById('results').classList.add('hidden');
            document.getElementById('my_camera').classList.remove('hidden');
            document.getElementById('btn-take').classList.remove('hidden');
            document.getElementById('btn-retake').classList.add('hidden');
            document.getElementById('btn-submit').classList.add('hidden');
        }

        function upload_presence() {
            const btn = document.getElementById('btn-submit');
            btn.disabled = true; btn.innerText = 'Mengirim...';

            fetch('<?= BASE_URL ?>/user/submit_attendance', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `token=${encodeURIComponent(document.getElementById('scanned-token').value)}&image=${encodeURIComponent(document.getElementById('final-image-data').value)}`
            })
            .then(res => res.json()).then(data => {
                if(data.status === 'success') { alert('Berhasil!'); window.location.href = '<?= BASE_URL ?>/user/dashboard'; }
                else { alert('Gagal: ' + data.message); btn.disabled = false; btn.innerText = 'Kirim Ulang'; }
            }).catch(() => { alert('Error koneksi'); btn.disabled = false; });
        }
    </script>
</body>
</html>