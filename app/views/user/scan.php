<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Scan Presensi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    
    <style>
        .scan-laser { position: absolute; top: 0; left: 0; width: 100%; height: 3px; background: #3b82f6; box-shadow: 0 0 15px #3b82f6; animation: scan 2s infinite ease-in-out; z-index: 50; }
        @keyframes scan { 0% {top: 0} 50% {top: 100%} 100% {top: 0} }
        .animate-enter { animation: fadeInUp 0.4s ease-out forwards; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        #qr-reader video { object-fit: cover; width: 100%; height: 100%; border-radius: 1rem; }
        #qr-reader { border: none !important; }
    </style>
</head>
<body class="bg-black h-screen w-full flex flex-col font-sans overflow-hidden text-white">

    <div class="absolute top-0 left-0 w-full z-40 p-4 flex justify-between items-center bg-gradient-to-b from-black/80 to-transparent">
        <a href="<?= BASE_URL ?>/user/dashboard" class="bg-white/10 backdrop-blur-md px-4 py-2 rounded-full text-sm font-bold flex items-center gap-2 hover:bg-white/20 transition border border-white/10">
            <i class="fas fa-chevron-left"></i> Kembali
        </a>
        
        <div class="flex items-center gap-2 bg-black/40 px-4 py-1.5 rounded-full backdrop-blur-md border border-white/10 max-w-[50%]">
            <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse shrink-0" id="gps-dot"></div>
            <span class="text-xs font-mono truncate" id="gps-text">Mencari Lokasi...</span>
        </div>
    </div>

    <div class="flex-1 relative w-full h-full bg-gray-900 flex flex-col justify-center overflow-hidden">
        
        <div id="step-scan" class="w-full h-full flex flex-col justify-center items-center p-6 relative z-10 transition-opacity duration-300">
            <div class="relative w-full max-w-sm aspect-square bg-gray-800 rounded-3xl overflow-hidden border-2 border-white/20 shadow-2xl">
                <div id="qr-reader" class="w-full h-full"></div>
                <div class="scan-laser"></div>
                <div class="absolute top-4 left-4 w-8 h-8 border-t-4 border-l-4 border-blue-500 rounded-tl-xl z-20"></div>
                <div class="absolute top-4 right-4 w-8 h-8 border-t-4 border-r-4 border-blue-500 rounded-tr-xl z-20"></div>
                <div class="absolute bottom-4 left-4 w-8 h-8 border-b-4 border-l-4 border-blue-500 rounded-bl-xl z-20"></div>
                <div class="absolute bottom-4 right-4 w-8 h-8 border-b-4 border-r-4 border-blue-500 rounded-br-xl z-20"></div>
            </div>
            <p class="text-white/60 text-sm mt-6 text-center animate-pulse bg-black/20 px-3 py-1 rounded-full backdrop-blur-sm">
                Arahkan kamera ke QR Code
            </p>
        </div>

        <div id="step-selfie" class="hidden absolute inset-0 z-20 bg-gray-900 animate-enter flex flex-col">
            <video id="selfie-video" autoplay playsinline class="w-full h-full object-cover transform scale-x-[-1]"></video>
            <img id="selfie-result" class="hidden w-full h-full object-contain bg-gray-900 absolute top-0 left-0 z-30 p-4">
            <canvas id="selfie-canvas" class="hidden"></canvas>
        </div>

        <div id="loading-overlay" class="hidden absolute inset-0 bg-black/80 z-[60] flex flex-col items-center justify-center backdrop-blur-sm">
            <div class="w-16 h-16 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
            <p class="text-lg font-bold tracking-widest uppercase animate-pulse" id="loading-text">Memproses...</p>
        </div>
    </div>

    <div class="bg-gradient-to-t from-black via-black/90 to-transparent pb-8 pt-20 px-6 fixed bottom-0 w-full z-50">
        <div id="controls-selfie" class="hidden flex-col gap-4 items-center w-full max-w-sm mx-auto">
            <div class="bg-blue-600 px-4 py-1 rounded-full text-xs font-bold uppercase tracking-widest shadow-lg mb-2">
                VERIFIKASI WAJAH
            </div>
            <button id="btn-take" onclick="takeSnapshot()" class="w-20 h-20 rounded-full bg-white border-4 border-gray-300 shadow-lg active:scale-95 transition flex items-center justify-center hover:scale-105">
                <div class="w-16 h-16 rounded-full bg-white border-2 border-black"></div>
            </button>
            <div id="action-group" class="hidden w-full flex gap-3">
                <button onclick="resetCamera()" class="flex-1 bg-gray-700/80 backdrop-blur-md text-white font-bold py-3.5 rounded-xl hover:bg-gray-600 transition border border-white/10">Ulangi</button>
                <button onclick="submitAttendance()" class="flex-1 bg-blue-600 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-500/40 hover:bg-blue-500 transition flex items-center justify-center gap-2">Kirim Bukti <i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <div id="customModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
        <div class="bg-white text-gray-800 w-full max-w-sm rounded-3xl p-6 text-center shadow-2xl modal-enter transform transition-all relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-24 bg-gradient-to-br from-blue-500 to-indigo-600 opacity-10"></div>
            <div id="modalIconContainer" class="w-20 h-20 rounded-full mx-auto mb-4 flex items-center justify-center text-4xl shadow-lg relative z-10 text-white transition-colors duration-300">
                <i id="modalIcon" class="fas fa-check"></i>
            </div>
            <h3 id="modalTitle" class="text-2xl font-extrabold mb-2 relative z-10">Berhasil!</h3>
            <p id="modalMessage" class="text-gray-500 text-sm mb-6 leading-relaxed relative z-10 px-2">Pesan...</p>
            <button id="modalBtn" class="w-full py-3.5 rounded-xl text-white font-bold shadow-lg transition relative z-10">OK</button>
        </div>
    </div>

    <input type="hidden" id="scanned-token">
    <input type="hidden" id="final-image-base64">
    <input type="hidden" id="geo-lat" value="">
    <input type="hidden" id="geo-lng" value="">
    <input type="hidden" id="geo-address" value="">

    <script>
        let html5QrcodeScanner = null;
        let selfieStream = null;
        const videoEl = document.getElementById('selfie-video');
        const canvasEl = document.getElementById('selfie-canvas');
        const resultImg = document.getElementById('selfie-result');

        document.addEventListener('DOMContentLoaded', () => {
            initQRWidget();
            initGeolocation();
        });

        // 1. GEOLOCATION DENGAN REVERSE GEOCODING (ALAMAT ASLI)
        function initGeolocation() {
            const gpsText = document.getElementById('gps-text');
            const gpsDot = document.getElementById('gps-dot');

            if (navigator.geolocation) {
                // Konfigurasi Akurasi Tinggi
                const options = {
                    enableHighAccuracy: true,
                    timeout: 20000, 
                    maximumAge: 0 
                };

                navigator.geolocation.getCurrentPosition(
                    async (pos) => {
                        const { latitude: lat, longitude: lng } = pos.coords;
                        
                        // Simpan Koordinat
                        document.getElementById('geo-lat').value = lat;
                        document.getElementById('geo-lng').value = lng;
                        
                        // Update UI Sementara
                        gpsText.innerText = "Mendapatkan Alamat...";
                        gpsDot.className = "w-2 h-2 rounded-full bg-yellow-400 animate-pulse";

                        try {
                            // Panggil OpenStreetMap Nominatim (Gratis & Valid)
                            const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`;
                            
                            // Tambahkan header user-agent agar tidak diblokir
                            const response = await fetch(url, {
                                headers: { 'Accept-Language': 'id' }
                            });
                            const data = await response.json();
                            
                            if (data && data.address) {
                                // Susun Alamat yang Rapi
                                let addr = data.address;
                                let parts = [];
                                
                                // Prioritas komponen alamat
                                if(addr.road) parts.push(addr.road);
                                else if(addr.building) parts.push(addr.building);
                                
                                if(addr.village) parts.push(addr.village);
                                else if(addr.suburb) parts.push(addr.suburb);
                                
                                if(addr.city) parts.push(addr.city);
                                else if(addr.town) parts.push(addr.town);
                                else if(addr.county) parts.push(addr.county);

                                const fullAddress = parts.join(', ');
                                const shortAddress = parts.length > 0 ? parts[0] + (parts[1] ? ', ' + parts[1] : '') : 'Lokasi Terdeteksi';

                                // Update Hidden Field (Untuk Watermark & Database)
                                document.getElementById('geo-address').value = fullAddress;
                                
                                // Update UI Navbar (Tampilkan Kecamatan/Kota)
                                gpsText.innerText = shortAddress.substring(0, 25) + (shortAddress.length > 25 ? '...' : '');
                                gpsDot.className = "w-2 h-2 rounded-full bg-green-500 shadow-[0_0_10px_#22c55e]";
                            } else {
                                throw new Error("Alamat tidak ditemukan");
                            }
                        } catch (e) {
                            console.warn("Geo Error:", e);
                            // Fallback ke koordinat jika internet lambat/gagal fetch
                            const fallback = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
                            document.getElementById('geo-address').value = fallback;
                            gpsText.innerText = "Lokasi: Koordinat";
                            gpsDot.className = "w-2 h-2 rounded-full bg-green-500";
                        }
                    },
                    (err) => {
                        console.error("GPS Error:", err);
                        gpsText.innerText = "GPS Nonaktif/Error";
                        gpsDot.className = "w-2 h-2 rounded-full bg-red-500";
                        // Coba minta user nyalakan GPS via Alert
                        if(err.code === 1) showModal('error', 'Izin Ditolak', 'Mohon izinkan akses lokasi browser Anda.');
                    }, 
                    options
                );
            } else {
                gpsText.innerText = "Browser Tidak Support";
            }
        }

        // 2. QR SCANNER
        function initQRWidget() {
            html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", { 
                fps: 10, 
                qrbox: { width: 250, height: 250 }, 
                aspectRatio: 1.0,
                showTorchButtonIfSupported: true
            }, false);
            html5QrcodeScanner.render(onScanSuccess, (err) => {});
        }

        function onScanSuccess(decodedText) {
            html5QrcodeScanner.clear().then(() => {
                let cleanToken = decodedText;
                try {
                    const parsed = JSON.parse(decodedText);
                    if (parsed.token) cleanToken = parsed.token;
                } catch (e) {}

                document.getElementById('scanned-token').value = cleanToken;
                
                document.getElementById('step-scan').classList.add('hidden');
                document.getElementById('step-selfie').classList.remove('hidden');
                document.getElementById('controls-selfie').classList.remove('hidden');
                document.getElementById('controls-selfie').classList.add('flex');

                setTimeout(startSelfieCamera, 500); 
            }).catch(err => {
                console.error("Scanner Error", err);
                location.reload();
            });
        }

        // 3. KAMERA
        async function startSelfieCamera() {
            try {
                selfieStream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: 'user', width: { ideal: 1080 }, height: { ideal: 1080 } }, 
                    audio: false 
                });
                videoEl.srcObject = selfieStream;
            } catch (err) {
                try {
                    selfieStream = await navigator.mediaDevices.getUserMedia({ video: true });
                    videoEl.srcObject = selfieStream;
                } catch (err2) {
                    showModal('error', 'Kamera Error', 'Gagal membuka kamera.');
                }
            }
        }

        // 4. SNAPSHOT + WATERMARK ALAMAT
        function takeSnapshot() {
            if (!selfieStream) return;
            const size = 1000; const imgHeight = 800;
            canvasEl.width = size; canvasEl.height = size;
            const ctx = canvasEl.getContext('2d');

            // Background
            ctx.fillStyle = "#ffffff"; ctx.fillRect(0, 0, size, size);
            
            // Image
            ctx.save(); ctx.translate(size, 0); ctx.scale(-1, 1);
            const vW = videoEl.videoWidth; const vH = videoEl.videoHeight;
            const minDim = Math.min(vW, vH);
            ctx.drawImage(videoEl, (vW-minDim)/2, (vH-minDim)/2, minDim, minDim, 0, 0, size, imgHeight);
            ctx.restore();

            // Footer Data
            const now = new Date();
            const dateStr = now.toLocaleDateString('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
            const timeStr = now.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' }).replace(/\./g, ':');
            const name = "<?= $_SESSION['name'] ?? 'User' ?>";
            
            // Ambil alamat valid yang sudah di-fetch
            const address = document.getElementById('geo-address').value || 'Lokasi GPS Tidak Terdeteksi';

            ctx.textAlign = "center";
            ctx.fillStyle = "#1f2937"; // Gray-800
            
            // Nama
            ctx.font = "bold 40px sans-serif";
            ctx.fillText(name, size/2, imgHeight + 60);

            // Alamat Valid
            ctx.fillStyle = "#4b5563"; // Gray-600
            ctx.font = "italic 24px sans-serif";
            
            // Simple text wrapping untuk alamat panjang
            let displayAddr = address;
            if(address.length > 50) displayAddr = address.substring(0, 50) + "...";
            ctx.fillText("📍 " + displayAddr, size/2, imgHeight + 100);

            // Waktu
            ctx.fillStyle = "#2563eb"; // Blue-600
            ctx.font = "bold 32px monospace";
            ctx.fillText(`${timeStr} WITA • ${dateStr}`, size/2, imgHeight + 150);

            const dataUrl = canvasEl.toDataURL('image/jpeg', 0.9);
            resultImg.src = dataUrl; resultImg.classList.remove('hidden');
            document.getElementById('final-image-base64').value = dataUrl;

            videoEl.classList.add('hidden');
            document.getElementById('btn-take').classList.add('hidden');
            document.getElementById('action-group').classList.remove('hidden');
            document.getElementById('action-group').classList.add('flex');
        }

        function resetCamera() {
            resultImg.classList.add('hidden'); videoEl.classList.remove('hidden');
            document.getElementById('btn-take').classList.remove('hidden');
            document.getElementById('action-group').classList.add('hidden');
            document.getElementById('action-group').classList.remove('flex');
        }

        // 5. SUBMIT
        function submitAttendance() {
            const token = document.getElementById('scanned-token').value;
            const img = document.getElementById('final-image-base64').value;
            // Kirim alamat string juga untuk disimpan di DB (opsional, kalau ada kolomnya)
            const address = document.getElementById('geo-address').value;

            if (!img) { showModal('error', 'Foto Kosong', 'Silakan ambil foto bukti.'); return; }

            showLoading(true);

            const fd = new FormData();
            fd.append('token', token);
            fd.append('image', img);
            fd.append('address', address); // Kirim alamat

            fetch('<?= BASE_URL ?>/user/submit_attendance', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                showLoading(false);
                if(data.status === 'success') {
                    showModal('success', 'Berhasil', data.message, () => window.location.href = '<?= BASE_URL ?>/user/dashboard');
                } else {
                    showModal('error', 'Gagal', data.message);
                }
            })
            .catch(() => { showLoading(false); showModal('error', 'Koneksi Error', 'Gagal menghubungi server.'); });
        }

        function showLoading(show) {
            const el = document.getElementById('loading-overlay');
            if(show) { el.classList.remove('hidden'); el.classList.add('flex'); }
            else { el.classList.add('hidden'); el.classList.remove('flex'); }
        }

        function showModal(type, title, message, onOk = null) {
            const m = document.getElementById('customModal');
            const iconCont = document.getElementById('modalIconContainer');
            const icon = document.getElementById('modalIcon');
            const btn = document.getElementById('modalBtn');

            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalMessage').innerText = message;

            iconCont.className = "w-20 h-20 rounded-full mx-auto mb-4 flex items-center justify-center text-4xl shadow-lg relative z-10 text-white transition-colors";
            
            if (type === 'success') {
                iconCont.classList.add('bg-green-500'); icon.className = "fas fa-check";
                btn.className = "w-full py-3.5 rounded-xl bg-green-600 text-white font-bold shadow-lg hover:bg-green-700 transition relative z-10";
                btn.innerText = "Selesai";
            } else {
                iconCont.classList.add('bg-red-500'); icon.className = "fas fa-times";
                btn.className = "w-full py-3.5 rounded-xl bg-red-600 text-white font-bold shadow-lg hover:bg-red-700 transition relative z-10";
                btn.innerText = "Tutup";
            }
            m.classList.remove('hidden');
            btn.onclick = () => { m.classList.add('hidden'); if(onOk) onOk(); };
        }
    </script>
</body>
</html>