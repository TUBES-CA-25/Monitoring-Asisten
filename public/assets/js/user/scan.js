// [Fix CSRF] scan.php adalah halaman standalone (tidak muat global.js).
// Patch window.fetch di sini agar semua POST dari scan.js otomatis
// menyertakan header X-CSRF-TOKEN dari <meta name="csrf-token">.
(function patchFetchCsrfScan() {
    var _orig = window.fetch.bind(window);
    window.fetch = function(resource, init) {
        init = init || {};
        if (((init.method || 'GET')).toUpperCase() !== 'GET') {
            var token = (document.querySelector('meta[name="csrf-token"]') || {}).getAttribute('content') || '';
            if (token) {
                if (!init.headers) init.headers = {};
                if (!init.headers['X-CSRF-TOKEN']) init.headers['X-CSRF-TOKEN'] = token;
            }
        }
        return _orig(resource, init);
    };
})();

let html5QrCode = null;
        let scanLocked = false; // [BARU - Modul 2 V3] guard pencegahan double scan
        let selfieStream = null;
        let config = {};
        const videoEl = document.getElementById('selfie-video');
        const canvasEl = document.getElementById('selfie-canvas');
        const resultImg = document.getElementById('selfie-result');

        function readConfig() {
            const el = document.getElementById('scan-config');
            if (!el) return;
            try {
                config = JSON.parse(el.textContent);
            } catch (e) {
                console.error('Scan config invalid JSON', e);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            readConfig();
            initGeolocation();

            // [BARU] Deep-link dari Google Lens / QR scanner eksternal:
            // URL mengandung ?t={token}&a={in|out}. Jika ada, skip tampilan
            // kamera scan dan langsung validasi token ke server, lalu ke selfie.
            if (config.prefilledToken) {
                // Sembunyikan widget kamera QR, tampilkan indikator loading
                const stepScan = document.getElementById('step-scan');
                if (stepScan) {
                    stepScan.innerHTML = [
                        '<div class="flex flex-col items-center justify-center gap-4 py-12 text-center">',
                        '  <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center animate-pulse">',
                        '    <i class="fas fa-qrcode text-2xl text-blue-600"></i>',
                        '  </div>',
                        '  <p class="text-white font-bold text-sm">QR terdeteksi via scanner</p>',
                        '  <p class="text-blue-200 text-xs">Memvalidasi token presensi...</p>',
                        '</div>'
                    ].join('');
                }

                // Tentukan tipe: 'in' → check_in, 'out' → check_out
                var prefilledType = (config.prefilledAction === 'out') ? 'check_out' : 'check_in';

                // Langsung validasi token
                validateScannedToken(config.prefilledToken, prefilledType);
            } else {
                // Alur normal: tampilkan kamera untuk scan QR
                initQRWidget();
            }
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
        // [BARU - Modul 2 V3] Memakai Html5Qrcode (low-level) alih-alih
        // Html5QrcodeScanner (high-level widget). Keuntungan:
        //  - Bisa langsung minta kamera BELAKANG (facingMode: environment)
        //    tanpa enumerasi device dulu -> permintaan izin & start kamera
        //    lebih cepat ("Percepatan request izin kamera" & "Perbaikan
        //    kamera loading lama").
        //  - Tidak merender UI tambahan (dropdown kamera/file upload) milik
        //    widget bawaan, sehingga tampilan custom (corner brackets, laser)
        //    jadi satu-satunya indikator.
        //  - stop()/clear() lebih reliable untuk benar-benar mematikan stream
        //    kamera setelah scan sukses.
        function initQRWidget() {
            hideCameraError();

            html5QrCode = new Html5Qrcode("qr-reader");

            const qrConfig = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            };

            // [BARU] Kamera belakang sebagai default (exact), dengan retry ke
            // constraint yang lebih longgar bila perangkat tidak punya kamera
            // belakang (mis. laptop) - "Kamera belakang sebagai default" &
            // "Retry scanner ketika gagal".
            html5QrCode.start(
                { facingMode: { exact: "environment" } },
                qrConfig,
                onScanSuccess,
                () => {} // error per-frame (QR belum terbaca) - diamkan, normal
            ).catch((err) => {
                console.warn("Gagal start kamera (environment exact), retry dengan constraint longgar:", err);
                html5QrCode.start(
                    { facingMode: "environment" },
                    qrConfig,
                    onScanSuccess,
                    () => {}
                ).catch((err2) => {
                    console.error("Gagal mengakses kamera:", err2);
                    showCameraError();
                });
            });
        }

        // [BARU] Hentikan & lepas stream kamera QR secara aman. Dipakai saat
        // scan sukses ("Stop kamera setelah berhasil scan") maupun saat retry.
        function stopScanner() {
            if (!html5QrCode) return Promise.resolve();
            return html5QrCode.stop()
                .then(() => html5QrCode.clear())
                .catch((err) => {
                    console.warn("Gagal stop kamera QR (lanjut tetap):", err);
                });
        }

        function showCameraError() {
            document.getElementById('camera-error').classList.remove('hidden');
            document.getElementById('camera-error').classList.add('flex');
        }

        function hideCameraError() {
            document.getElementById('camera-error').classList.add('hidden');
            document.getElementById('camera-error').classList.remove('flex');
        }

        // [BARU] Tombol "Coba Lagi" pada overlay error kamera.
        function retryCamera() {
            initQRWidget();
        }

        // [BARU] Kembali ke tahap scan (mis. setelah QR ternyata expired),
        // reset guard scan & nyalakan ulang kamera QR.
        function restartScanner() {
            scanLocked = false;

            document.getElementById('step-selfie').classList.add('hidden');
            document.getElementById('controls-selfie').classList.add('hidden');
            document.getElementById('controls-selfie').classList.remove('flex');
            document.getElementById('step-scan').classList.remove('hidden');

            initQRWidget();
        }

        function onScanSuccess(decodedText) {
            // [BARU - Modul 2 V3] guard pencegahan double scan
            if (scanLocked) return;
            scanLocked = true;

            let cleanToken = decodedText;
            let type = 'check_in'; // Default

            try {
                // [BARU] Coba parse sebagai URL terlebih dahulu (format QR baru dengan scan_url)
                // Contoh: https://domain.com/user/scan?t=TOKEN&a=in
                var urlObj = new URL(decodedText);
                var tParam = urlObj.searchParams.get('t');
                var aParam = urlObj.searchParams.get('a');
                if (tParam) {
                    cleanToken = tParam;
                    if (aParam === 'out') type = 'check_out';
                    else type = 'check_in';
                } else {
                    // Bukan URL dengan ?t= — coba parse sebagai JSON (format lama)
                    throw new Error('no_t_param');
                }
            } catch (urlErr) {
                // Bukan URL valid — coba parse sebagai JSON (format lama)
                try {
                    const parsed = JSON.parse(decodedText);
                    if (parsed.token) cleanToken = parsed.token;
                    if (parsed.type === 'CHECK_IN')  type = 'check_in';
                    if (parsed.type === 'CHECK_OUT') type = 'check_out';
                } catch (jsonErr) {
                    console.warn("Format QR bukan URL maupun JSON, menggunakan token mentah.");
                }
            }

            stopScanner().then(() => {
                validateScannedToken(cleanToken, type);
            });
        }

        // [BARU] Validasi awal token QR (valid/expired/tipe) lewat endpoint
        // /user/check_qr_type sebelum lanjut ke selfie. Jika tidak valid,
        // tampilkan pesan & izinkan scan ulang tanpa reload halaman.
        function validateScannedToken(token, type) {
            showLoading(true, 'Memvalidasi QR...');

            const fd = new FormData();
            fd.append('token', token);

            fetch(config.checkTypeUrl, { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    showLoading(false);

                    if (data.status !== 'success') {
                        showModal('error', 'QR Code Tidak Valid', data.message || 'QR Code tidak valid atau sudah kadaluwarsa.', restartScanner);
                        return;
                    }

                    // Sinkronkan tipe dari server (sumber kebenaran: Presensi/Pulang)
                    const serverType = data.type === 'Presensi' ? 'check_in'
                        : (data.type === 'Pulang' ? 'check_out' : type);

                    document.getElementById('scanned-token').value = token;
                    document.getElementById('scanned-type').value = serverType;

                    proceedToSelfie();
                })
                .catch(() => {
                    showLoading(false);
                    showModal('error', 'Koneksi Error', 'Gagal memvalidasi QR Code. Periksa koneksi Anda.', restartScanner);
                });
        }

        // [BARU] Pindah dari tahap scan ke tahap selfie (diekstrak dari
        // onScanSuccess lama agar bisa dipanggil setelah validasi token).
        function proceedToSelfie() {
            document.getElementById('step-scan').classList.add('hidden');
            document.getElementById('step-selfie').classList.remove('hidden');
            document.getElementById('controls-selfie').classList.remove('hidden');
            document.getElementById('controls-selfie').classList.add('flex');

            // [BARU - Modul 2 V3] Langsung minta izin kamera presensi (tanpa
            // delay buatan) - browser akan menampilkan prompt izin segera.
            startSelfieCamera();
        }

        // [BARU - Modul 2 V3] Kamera untuk foto presensi memang BERBEDA dari
        // kamera scan QR (yang default belakang/environment): di sini default
        // KAMERA DEPAN (facingMode: 'user') karena tujuannya foto wajah
        // sebagai bukti presensi. Permintaan izin langsung dipanggil
        // (getUserMedia) tanpa menunggu - browser akan menampilkan prompt izin
        // segera. Retry chain: depan+resolusi ideal -> depan saja (tanpa
        // batasan resolusi) -> kamera APA PUN yang tersedia (mis. perangkat
        // tanpa kamera depan) -> jika semua gagal, tampilkan overlay error
        // dengan tombol "Coba Lagi".
        const SELFIE_CONSTRAINTS = [
            { video: { facingMode: 'user', width: { ideal: 1080 }, height: { ideal: 1080 } }, audio: false },
            { video: { facingMode: 'user' }, audio: false },
            { video: true, audio: false }
        ];

        async function startSelfieCamera() {
            hideSelfieCameraError();

            for (const constraint of SELFIE_CONSTRAINTS) {
                try {
                    selfieStream = await navigator.mediaDevices.getUserMedia(constraint);
                    videoEl.srcObject = selfieStream;
                    console.log("Kamera presensi berhasil dimulai dengan constraint:", constraint);
                    document.getElementById('btn-take').classList.remove('hidden');
                    return;
                } catch (err) {
                    console.warn("Gagal getUserMedia dengan constraint:", constraint, err);
                }
            }

            // Semua percobaan gagal
            console.error("Tidak dapat mengakses kamera untuk foto presensi.");
            showSelfieCameraError();
        }

        function showSelfieCameraError() {
            document.getElementById('selfie-camera-error').classList.remove('hidden');
            document.getElementById('selfie-camera-error').classList.add('flex');
            // Sembunyikan tombol jepret - tidak ada stream untuk diambil.
            document.getElementById('btn-take').classList.add('hidden');
        }

        function hideSelfieCameraError() {
            document.getElementById('selfie-camera-error').classList.add('hidden');
            document.getElementById('selfie-camera-error').classList.remove('flex');
        }

        // [BARU] Tombol "Coba Lagi" pada overlay error kamera presensi.
        function retrySelfieCamera() {
            startSelfieCamera();
        }

        // 4. SNAPSHOT + WATERMARK OVERLAY
        function takeSnapshot() {
            if (!selfieStream) return;

            // [PERBAIKAN] Canvas selalu persegi 1000×1000 agar foto tidak gepeng.
            // Sebelumnya drawImage menulis sumber persegi ke area 1000×800,
            // menyebabkan distorsi vertikal. Sekarang sumber (crop persegi dari
            // video) dan tujuan (1000×1000) sama-sama persegi → tidak ada
            // stretching. Footer ditulis sebagai overlay semi-transparan di
            // bagian bawah foto (bukan area terpisah).
            const SIZE = 1000;
            canvasEl.width  = SIZE;
            canvasEl.height = SIZE;
            const ctx = canvasEl.getContext('2d');

            // -- Gambar video (crop persegi, mirror horizontal) --
            ctx.save();
            ctx.translate(SIZE, 0);
            ctx.scale(-1, 1);
            const vW = videoEl.videoWidth  || SIZE;
            const vH = videoEl.videoHeight || SIZE;
            const crop = Math.min(vW, vH);
            const sx   = (vW - crop) / 2;
            const sy   = (vH - crop) / 2;
            ctx.drawImage(videoEl, sx, sy, crop, crop, 0, 0, SIZE, SIZE);
            ctx.restore();

            // -- Footer overlay (gradient gelap di bagian bawah) --
            const FOOTER_H = 180;
            const grad = ctx.createLinearGradient(0, SIZE - FOOTER_H, 0, SIZE);
            grad.addColorStop(0, 'rgba(0,0,0,0)');
            grad.addColorStop(0.4, 'rgba(0,0,0,0.65)');
            grad.addColorStop(1,   'rgba(0,0,0,0.82)');
            ctx.fillStyle = grad;
            ctx.fillRect(0, SIZE - FOOTER_H, SIZE, FOOTER_H);

            // -- Teks nama --
            const name = config.userName || 'User';
            ctx.textAlign = 'center';
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 38px sans-serif';
            ctx.fillText(name, SIZE / 2, SIZE - FOOTER_H + 52);

            // -- Alamat --
            const address = document.getElementById('geo-address').value || 'Lokasi GPS Tidak Terdeteksi';
            let displayAddr = address.length > 52 ? address.substring(0, 52) + '…' : address;
            ctx.fillStyle = 'rgba(255,255,255,0.80)';
            ctx.font = 'italic 22px sans-serif';
            ctx.fillText('📍 ' + displayAddr, SIZE / 2, SIZE - FOOTER_H + 94);

            // -- Waktu --
            const now     = new Date();
            const dateStr = now.toLocaleDateString('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
            const timeStr = now.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' }).replace(/\./g, ':');
            ctx.fillStyle = '#93c5fd'; // blue-300
            ctx.font = 'bold 26px monospace';
            ctx.fillText(timeStr + ' WITA  •  ' + dateStr, SIZE / 2, SIZE - FOOTER_H + 138);

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

            showLoading(true, 'Mengirim bukti presensi...');

            const fd = new FormData();
            fd.append('token', token);
            fd.append('type', type);
            fd.append('image', img);
            fd.append('address', address); // Kirim alamat

            fetch(config.submitUrl, { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                showLoading(false);
                if(data.status === 'success') {
                    // [BARU – Tahap 35] Jika check-out, tampilkan modal logbook
                    // sebelum kembali ke dashboard. Jika check-in atau modal
                    // tidak tersedia, langsung redirect.
                    if (type === 'check_out' && document.getElementById('logbookModal')) {
                        showLogbookModal();
                    } else {
                        showModal('success', 'Berhasil', data.message, () => window.location.href = config.dashboardUrl);
                    }
                } else {
                    showModal('error', 'Gagal', data.message);
                }
            })
            .catch(() => { 
                showLoading(false); 
                showModal('error', 'Koneksi Error', 'Gagal menghubungi server.'); });
        }

        function showLoading(show, text = 'Memproses...') {
            const el = document.getElementById('loading-overlay');
            if(show) {
                document.getElementById('loading-text').innerText = text;
                el.classList.remove('hidden'); el.classList.add('flex');
            }
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

        // [BARU – Tahap 35] Modal isi logbook setelah scan pulang
        function showLogbookModal() {
            const modal   = document.getElementById('logbookModal');
            const textarea = document.getElementById('logbookActivity');
            const charCnt = document.getElementById('logbookCharCount');
            const skipBtn = document.getElementById('logbookSkipBtn');
            const submitBtn = document.getElementById('logbookSubmitBtn');
            if (!modal) { window.location.href = config.dashboardUrl; return; }

            // Reset
            textarea.value = '';
            if (charCnt) charCnt.innerText = '0 karakter';
            modal.classList.remove('hidden');

            textarea.addEventListener('input', function () {
                if (charCnt) charCnt.innerText = this.value.length + ' karakter';
            });

            // Lewati → langsung ke dashboard
            skipBtn.onclick = function() {
                modal.classList.add('hidden');
                window.location.href = config.dashboardUrl;
            };

            // Submit logbook
            submitBtn.onclick = function() {
                const activity = textarea.value.trim();
                if (!activity) {
                    textarea.classList.add('ring-2','ring-red-300');
                    textarea.placeholder = 'Aktivitas tidak boleh kosong!';
                    return;
                }
                textarea.classList.remove('ring-2','ring-red-300');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';

                // [BARU] Sebelumnya mengirim tanggal via new Date().toISOString()
                // (zona waktu UTC browser) - antara pukul 00:00-08:00 WITA,
                // ini menghasilkan tanggal HARI SEBELUMNYA (WITA = UTC+8),
                // beda dengan tanggal presensi yang baru saja dicatat server
                // (Asia/Makassar) - submit_logbook() lalu tidak menemukan
                // baris presensi utk tanggal "salah" tsb & menolak dengan
                // pesan "Anda belum melakukan scan masuk!". Tidak perlu
                // dikirim sama sekali - server sudah default ke tanggal
                // HARI INI di zona waktu yang benar (lihat UserController::
                // submit_logbook(), $targetDate = $_POST['date'] ?? date('Y-m-d')).
                const fd = new FormData();
                fd.append('activity', activity);
                fd.append('time',     new Date().toTimeString().slice(0,5));
                fd.append('log_id',   '');

                fetch(config.logbookUrl, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(function(data) {
                    modal.classList.add('hidden');
                    if (data.status === 'success') {
                        showModal('success', 'Logbook Tersimpan!',
                            'Aktivitas kamu hari ini sudah dicatat. Sampai jumpa!',
                            () => window.location.href = config.dashboardUrl);
                    } else {
                        // [BARU] Sebelumnya kegagalan diam-diam di-redirect ke
                        // dashboard tanpa pemberitahuan apa pun - pengguna
                        // mengira logbook tersimpan padahal tidak. Tampilkan
                        // pesan error sungguhan (dari server jika ada), baru
                        // redirect setelah pengguna menutup modal.
                        showModal('error', 'Gagal Menyimpan Logbook',
                            data.message || 'Terjadi kesalahan saat menyimpan logbook. Silakan isi ulang lewat menu Logbook.',
                            () => window.location.href = config.dashboardUrl);
                    }
                })
                .catch(function() {
                    modal.classList.add('hidden');
                    showModal('error', 'Gagal Menyimpan Logbook',
                        'Koneksi ke server terputus. Silakan isi ulang lewat menu Logbook.',
                        () => window.location.href = config.dashboardUrl);
                });
            };
        }
