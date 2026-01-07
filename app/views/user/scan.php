<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Presensi - ICLABS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js"></script>
    
    <style>
        /* Efek Laser Scan */
        .scan-laser {
            position: absolute; top: 0; left: 0; width: 100%; height: 3px;
            background: #3b82f6; box-shadow: 0 0 10px #3b82f6;
            animation: scan 2s infinite ease-in-out;
            z-index: 20;
        }
        @keyframes scan { 0% {top: 0} 50% {top: 100%} 100% {top: 0} }
        
        /* Hilangkan border default WebcamJS jika ada */
        video { object-fit: cover; }
    </style>
</head>
<body class="bg-gray-900 h-screen w-screen overflow-hidden flex flex-col items-center justify-center relative font-sans">

    <a href="<?= BASE_URL ?>/user/dashboard" class="absolute top-6 left-6 z-50 bg-white/10 backdrop-blur-md text-white px-4 py-2 rounded-full hover:bg-white/20 transition flex items-center gap-2 border border-white/10">
        <i class="fas fa-arrow-left"></i> <span class="text-sm font-bold">Batal</span>
    </a>

    <div id="step-scan" class="w-full max-w-md p-4 relative z-10 flex flex-col items-center">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-white tracking-tight">Langkah 1: Scan QR Code</h1>
            <p class="text-gray-400 text-sm mt-1">Arahkan kamera ke QR Code Presensi di Lab.</p>
        </div>
        
        <div class="relative w-full aspect-square bg-black rounded-3xl overflow-hidden border-2 border-blue-500/50 shadow-2xl shadow-blue-500/20">
            <div id="qr-reader" class="w-full h-full"></div>
            <div class="scan-laser pointer-events-none"></div>
        </div>
        
        <p class="text-xs text-gray-500 mt-6 text-center">Pastikan QR Code berada di dalam kotak area scan.</p>
    </div>

    <div id="step-selfie" class="hidden w-full max-w-md p-4 relative z-10 flex flex-col items-center animate-enter">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-white tracking-tight">Langkah 2: Verifikasi Wajah</h1>
            <p class="text-gray-400 text-sm mt-1">Ambil foto selfie sebagai bukti kehadiran.</p>
        </div>

        <div class="relative w-full aspect-[4/3] bg-black rounded-2xl overflow-hidden border-2 border-white/20 shadow-xl mb-6">
            <div id="my_camera" class="w-full h-full object-cover"></div>
            <div id="results" class="hidden w-full h-full absolute top-0 left-0 bg-black z-20"></div>
        </div>

        <div class="flex gap-3 w-full">
            <button id="btn-take" onclick="take_snapshot()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl transition shadow-lg shadow-blue-500/30 flex items-center justify-center gap-2">
                <i class="fas fa-camera"></i> Ambil Foto
            </button>
            <button id="btn-retake" onclick="reset_camera()" class="hidden flex-1 bg-gray-600 hover:bg-gray-700 text-white font-bold py-3.5 rounded-xl transition border border-gray-500">
                Ulangi
            </button>
            <button id="btn-submit" onclick="upload_presence()" class="hidden flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-3.5 rounded-xl transition shadow-lg shadow-green-500/30 flex items-center justify-center gap-2">
                <i class="fas fa-paper-plane"></i> Kirim Absen
            </button>
        </div>
    </div>

    <input type="hidden" id="scanned-token">
    <input type="hidden" id="final-image-data">

    <style>
        .animate-enter { animation: fadeInUp 0.5s ease-out forwards; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>

    <script>
        // --- CEK DUKUNGAN BROWSER ---
        document.addEventListener('DOMContentLoaded', function() {
            // Cek apakah browser support media devices
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert("❌ Browser Anda tidak mendukung akses kamera!\nPastikan menggunakan HTTPS atau Localhost.");
                return;
            }

            // Cek Permission Awal
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(function(stream) {
                    // Izin diberikan, matikan stream dan mulai scanner
                    stream.getTracks().forEach(track => track.stop());
                    initQRScanner();
                })
                .catch(function(err) {
                    alert("⚠️ Izin Kamera Ditolak!\nSilakan izinkan akses kamera di pengaturan browser.\nError: " + err.name);
                });
        });

        // --- 1. FUNGSI SCANNER QR ---
        let html5QrcodeScanner = null;

        function initQRScanner() {
            // Gunakan Config Khusus untuk HP
            const config = { 
                fps: 10, 
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0,
                // Pakai kamera belakang (environment) secara default
                videoConstraints: { facingMode: { exact: "environment" } } 
            };

            // Fallback jika 'exact environment' gagal (misal di laptop)
            const configFallback = { 
                fps: 10, 
                qrbox: 250
            };

            html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", config, false);
            
            // Coba render, jika gagal coba konfigurasi fallback (kamera depan/webcam laptop)
            html5QrcodeScanner.render(onScanSuccess, (errorMessage) => {
                // Error scanning frame biasa, abaikan
            }).catch(err => {
                console.log("Gagal start kamera belakang, mencoba kamera default...", err);
                html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", configFallback, false);
                html5QrcodeScanner.render(onScanSuccess);
            });
        }

        function onScanSuccess(decodedText, decodedResult) {
            // Matikan QR Scanner
            html5QrcodeScanner.clear().then(_ => {
                document.getElementById('scanned-token').value = decodedText;
                document.getElementById('step-scan').classList.add('hidden');
                document.getElementById('step-selfie').classList.remove('hidden');
                
                // Jeda 1 detik agar kamera hardware terlepas
                setTimeout(initWebcam, 1000); 
            }).catch(error => {
                alert("Gagal mematikan scanner. Refresh halaman.");
            });
        }

        // --- 2. FUNGSI WEBCAM (SELFIE) ---
        function initWebcam() {
            Webcam.set({
                width: 640,
                height: 480,
                image_format: 'jpeg',
                jpeg_quality: 90,
                facingMode: 'user' // Kamera depan untuk selfie
            });
            
            // Tambahkan Error Listener untuk WebcamJS
            Webcam.on('error', function(err) {
                alert("Gagal mengakses kamera selfie: " + err);
            });

            Webcam.attach('#my_camera');
        }

        function take_snapshot() {
            Webcam.snap(function(data_uri) {
                var canvas = document.createElement('canvas');
                var ctx = canvas.getContext('2d');
                var img = new Image();

                img.onload = function() {
                    canvas.width = img.width;
                    canvas.height = img.height;
                    ctx.drawImage(img, 0, 0);

                    // WATERMARK
                    var now = new Date();
                    var dateStr = now.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                    var timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace(/\./g, ':');
                    
                    ctx.fillStyle = "rgba(0, 0, 0, 0.6)";
                    ctx.fillRect(0, canvas.height - 80, canvas.width, 80);

                    ctx.font = "bold 24px sans-serif";
                    ctx.fillStyle = "#ffffff";
                    ctx.fillText(timeStr + " WITA | " + dateStr, 20, canvas.height - 45);
                    
                    ctx.font = "16px sans-serif";
                    ctx.fillStyle = "#cccccc";
                    ctx.fillText("<?= $_SESSION['name'] ?>", 20, canvas.height - 20);

                    var finalImage = canvas.toDataURL('image/jpeg', 0.9);
                    
                    document.getElementById('my_camera').style.display = 'none';
                    document.getElementById('results').innerHTML = '<img src="'+finalImage+'" class="w-full h-full object-cover"/>';
                    document.getElementById('results').classList.remove('hidden');
                    document.getElementById('final-image-data').value = finalImage;

                    document.getElementById('btn-take').classList.add('hidden');
                    document.getElementById('btn-retake').classList.remove('hidden');
                    document.getElementById('btn-submit').classList.remove('hidden');
                };
                img.src = data_uri;
            });
        }

        function reset_camera() {
            document.getElementById('results').classList.add('hidden');
            document.getElementById('my_camera').style.display = 'block';
            document.getElementById('btn-take').classList.remove('hidden');
            document.getElementById('btn-retake').classList.add('hidden');
            document.getElementById('btn-submit').classList.add('hidden');
        }

        function upload_presence() {
            const token = document.getElementById('scanned-token').value;
            const image = document.getElementById('final-image-data').value;

            if(!token || !image) { alert("Data belum lengkap!"); return; }

            const btn = document.getElementById('btn-submit');
            btn.innerHTML = 'Mengirim...';
            btn.disabled = true;

            fetch('<?= BASE_URL ?>/user/submit_attendance', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'token=' + encodeURIComponent(token) + '&image=' + encodeURIComponent(image)
            })
            .then(res => res.json())
            .then(data => {
                if(data.status == 'success') {
                    alert('Berhasil!');
                    window.location.href = '<?= BASE_URL ?>/user/dashboard';
                } else {
                    alert('Gagal: ' + data.message);
                    btn.innerHTML = 'Kirim Ulang';
                    btn.disabled = false;
                }
            })
            .catch(err => {
                alert('Error koneksi');
                btn.innerHTML = 'Kirim Ulang';
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>