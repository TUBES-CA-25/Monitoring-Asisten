
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

        // function onScanSuccess(decodedText) {
        //     html5QrcodeScanner.clear().then(() => {
        //         let cleanToken = decodedText;
        //         try {
        //             const parsed = JSON.parse(decodedText);
        //             if (parsed.token) cleanToken = parsed.token;
        //         } catch (e) {}

        //         document.getElementById('scanned-token').value = cleanToken;
                
        //         document.getElementById('step-scan').classList.add('hidden');
        //         document.getElementById('step-selfie').classList.remove('hidden');
        //         document.getElementById('controls-selfie').classList.remove('hidden');
        //         document.getElementById('controls-selfie').classList.add('flex');

        //         setTimeout(startSelfieCamera, 500); 
        //     }).catch(err => {
        //         console.error("Scanner Error", err);
        //         location.reload();
        //     });
        // }

        // // 3. KAMERA
        // async function startSelfieCamera() {
        //     try {
        //         selfieStream = await navigator.mediaDevices.getUserMedia({ 
        //             video: { facingMode: 'user', width: { ideal: 1080 }, height: { ideal: 1080 } }, 
        //             audio: false 
        //         });
        //         videoEl.srcObject = selfieStream;
        //     } catch (err) {
        //         try {
        //             selfieStream = await navigator.mediaDevices.getUserMedia({ video: true });
        //             videoEl.srcObject = selfieStream;
        //         } catch (err2) {
        //             showModal('error', 'Kamera Error', 'Gagal membuka kamera.');
        //         }
        //     }
        // }

        function onScanSuccess(decodedText) {
    html5QrcodeScanner.clear().then(() => {
        let cleanToken = decodedText;
        let type = 'check_in'; // Default

        try {
            // Mengurai JSON dari QR Admin
            const parsed = JSON.parse(decodedText);
            if (parsed.token) cleanToken = parsed.token;
            
            // Konversi tipe: CHECK_IN -> check_in, CHECK_OUT -> check_out
            if (parsed.type === 'CHECK_IN') type = 'check_in';
            if (parsed.type === 'CHECK_OUT') type = 'check_out';
        } catch (e) {
            console.warn("Format QR bukan JSON, menggunakan token mentah.");
        }

        // Mengisi input hidden yang baru Anda buat
        document.getElementById('scanned-token').value = cleanToken;
        document.getElementById('scanned-type').value = type; 
        
        // Pindah ke tahap selfie
        document.getElementById('step-scan').classList.add('hidden');
        document.getElementById('step-selfie').classList.remove('hidden');
        document.getElementById('controls-selfie').classList.remove('hidden');
        document.getElementById('controls-selfie').classList.add('flex');

        setTimeout(startSelfieCamera, 500); 
    });
}
        async function startSelfieCamera() {
            try {
                selfieStream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: 'user', width: { ideal: 1080 }, height: { ideal: 1080 } }, 
                    audio: false 
                });
                videoEl.srcObject = selfieStream;
                console.log("Kamera selfie berhasil dimulai");
            } catch (err) {
                console.error("Gagal membuka kamera:", err);
                try {
                    // Fallback jika resolusi ideal tidak didukung perangkat
                    selfieStream = await navigator.mediaDevices.getUserMedia({ video: true });
                    videoEl.srcObject = selfieStream;
                } catch (err2) {
                    showModal('error', 'Kamera Error', 'Tidak dapat mengakses kamera: ' + err2.message);
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
            const type = document.getElementById('scanned-type').value;
            const img = document.getElementById('final-image-base64').value;
            // Kirim alamat string juga untuk disimpan di DB (opsional, kalau ada kolomnya)
            const address = document.getElementById('geo-address').value;

            if (!img) { showModal('error', 'Foto Kosong', 'Silakan ambil foto bukti.'); return; }

            // showLoading(true);

            const fd = new FormData();
            fd.append('token', token);
            fd.append('type', type);
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
            .catch(() => { 
                showLoading(false); 
                showModal('error', 'Koneksi Error', 'Gagal menghubungi server.'); });
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
    