/**
 * qr_page.js — QR Presensi Toggle Design [Tahap 35 rev]
 *
 * Satu QR ditampilkan sekaligus (Masuk atau Pulang).
 * Toggle switch berganti tampilan dan QR secara animatif.
 * Tidak ada countdown — QR auto-refresh di background setiap ~175 detik
 * (< 3 menit server) TANPA menampilkan hitungan mundur ke pengguna.
 */
(function () {
    'use strict';

    var cfg    = window.APP_CONFIG || {};
    var base   = (cfg.baseUrl || '').replace(/\/$/, '');
    var TICK   = cfg.interval || 175; // detik

    var currentMode = 'in';       // 'in' | 'out'
    var qrObj       = null;
    var timers      = { in: null, out: null };
    var seconds     = { in: TICK, out: TICK };

    /* ── Warna & teks per mode ──────────────────────────── */
    var modeConfig = {
        in: {
            headerCls : 'bg-gradient-to-r from-blue-600 to-cyan-500',
            borderCls : 'border-blue-200',
            pulseCls  : 'qr-pulse-in',
            icon      : 'fa-sign-in-alt',
            title     : 'QR Masuk (Check-In)',
            subtitle  : 'Scan untuk absen masuk',
            subtitleCls: 'text-blue-100',
        },
        out: {
            headerCls : 'bg-gradient-to-r from-orange-500 to-amber-500',
            borderCls : 'border-orange-200',
            pulseCls  : 'qr-pulse-out',
            icon      : 'fa-sign-out-alt',
            title     : 'QR Pulang (Check-Out)',
            subtitle  : 'Scan untuk absen pulang',
            subtitleCls: 'text-orange-100',
        }
    };

    /* ── Init ───────────────────────────────────────────── */
    // Inisialisasi QR: dijalankan segera (script dimuat di akhir body, DOM sudah siap)
    // + fallback DOMContentLoaded untuk ketepatan sinkronisasi.
    function initQRPage() {
        var qrIn  = safeParse(cfg.qrIn);
        var qrOut = safeParse(cfg.qrOut);

        // Cache data awal kedua mode
        window._qrData = { in: qrIn ? JSON.stringify(qrIn) : null, out: qrOut ? JSON.stringify(qrOut) : null };

        // Render QR mode default (masuk).
        // Gunakan requestAnimationFrame agar browser selesai layout sebelum QRCode.js
        // membuat canvas — mencegah QR tidak muncul saat pertama kali halaman dibuka.
        if (typeof QRCode !== 'undefined') {
            setMode('in');
        } else {
            // QRCode.js belum siap (jarang terjadi) — coba setelah render berikutnya
            requestAnimationFrame(function () {
                if (typeof QRCode !== 'undefined') { setMode('in'); }
            });
        }

        // Background timer untuk refresh otomatis kedua mode
        startBgTimer('in');
        startBgTimer('out');

        // Jam live (update setiap detik)
        function updateQrClock() {
            var now = new Date();
            var opts = { day: '2-digit', month: 'long', year: 'numeric' };
            var tOpts = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
            var elDate = document.getElementById('liveDate');
            var elTime = document.getElementById('liveTime');
            if (elDate) elDate.innerText = now.toLocaleDateString('id-ID', opts);
            if (elTime) elTime.innerText = now.toLocaleTimeString('id-ID', tOpts).replace(/\./g, ':');
        }
        setInterval(updateQrClock, 1000);
        updateQrClock();
    }

    // Call immediately (DOM is ready when scripts load at end of body)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initQRPage);
    } else {
        initQRPage(); // DOM sudah siap — langsung render QR
    }

    /* ── Parse JSON aman ────────────────────────────────── */
    function safeParse(v) {
        try { return typeof v === 'string' ? JSON.parse(v) : v; } catch (e) { return null; }
    }

    /* ── Ganti mode (toggle) ────────────────────────────── */
    // [PENTING] Deklarasi function biasa (bukan window.setMode = function...)
    // agar di-hoisting ke atas scope IIFE ini. initQRPage() di atas memanggil
    // setMode('in') SAAT DIPANGGIL — pada navigasi AJAX (readyState sudah
    // 'complete') initQRPage() dieksekusi SEGERA saat script disisipkan,
    // yaitu SEBELUM baris ini tereksekusi secara berurutan. Tanpa hoisting,
    // setMode belum terdefinisi saat dipanggil → "setMode is not defined"
    // dan QR gagal tampil setiap kali halaman ini dibuka lewat navigasi AJAX.
    function setMode(mode) {
        currentMode = mode;
        var mc = modeConfig[mode];

        // Update header card
        // [PENTING] String class di bawah ini HARUS tetap sinkron dengan
        // markup statis di admin/qr_page.php (termasuk varian lg: untuk
        // layout desktop) - setMode() dipanggil SEGERA saat halaman dimuat
        // (initQRPage -> setMode('in')), jadi apapun yang di-set di sini
        // menimpa class hasil render PHP sebelum pengguna sempat melihatnya.
        var header = document.getElementById('qrCardHeader');
        if (header) header.className = mc.headerCls + ' px-5 py-3.5 lg:px-7 lg:py-5 flex items-center gap-3';

        var icon = document.getElementById('qrCardIcon');
        if (icon) icon.innerHTML = '<i class="fas ' + mc.icon + ' text-white lg:text-lg"></i>';

        var titleEl = document.getElementById('qrCardTitle');
        if (titleEl) titleEl.innerHTML =
            '<h2 class="font-extrabold leading-tight text-base lg:text-xl">' + mc.title + '</h2>' +
            '<p class="' + mc.subtitleCls + ' text-[11px] lg:text-sm">' + mc.subtitle + '</p>';

        // Update QR border/pulse (ukuran w-full/max-w tetap dipertahankan -
        // itulah yang membuat QR fleksibel mengikuti frame container-nya)
        var wrap = document.getElementById('qrWrap');
        if (wrap) {
            wrap.className = 'rounded-2xl border-4 p-3 lg:p-4 bg-white qr-pulse-' + (mode === 'in' ? 'in' : 'out') + ' ' + mc.borderCls + ' w-full max-w-[288px] lg:max-w-[360px] mx-auto';
        }

        // Toggle pill & button styles
        var pill = document.getElementById('togglePill');
        var btnIn  = document.getElementById('btnModeIn');
        var btnOut = document.getElementById('btnModeOut');
        if (pill) {
            if (mode === 'in') {
                pill.style.transform = 'translateX(0)';
                pill.className = 'toggle-pill absolute top-1 left-1 w-[calc(50%-4px)] h-[calc(100%-8px)] rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 shadow-md shadow-blue-300/40 z-0';
            } else {
                pill.style.transform = 'translateX(100%)';
                pill.className = 'toggle-pill absolute top-1 left-1 w-[calc(50%-4px)] h-[calc(100%-8px)] rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 shadow-md shadow-orange-300/40 z-0';
            }
        }
        if (btnIn)  btnIn.className  = 'relative z-10 flex-1 flex items-center justify-center gap-2 py-2.5 lg:py-3.5 rounded-xl text-sm lg:text-base font-bold transition ' + (mode === 'in'  ? 'text-white'  : 'text-gray-500');
        if (btnOut) btnOut.className = 'relative z-10 flex-1 flex items-center justify-center gap-2 py-2.5 lg:py-3.5 rounded-xl text-sm lg:text-base font-bold transition ' + (mode === 'out' ? 'text-white' : 'text-gray-500');

        // Render QR untuk mode ini
        renderQR(window._qrData[mode]);
    }
    window.setMode = setMode;

    /* ── Render QR ──────────────────────────────────────── */
    // [BARU] Ekstrak URL deep-link dari data QR jika tersedia.
    // QR baru mengandung JSON: { type, token, scan_url }.
    // scan_url dipakai sebagai isi QR sehingga Google Lens / scanner
    // eksternal langsung membuka halaman presensi di browser.
    // scan.js (in-app) tetap bekerja: ia mengekstrak token dari ?t= & ?a=.
    function qrContentFrom(raw) {
        if (!raw) return null;
        try {
            var obj = JSON.parse(raw);
            if (obj && obj.scan_url) return obj.scan_url; // preferensi: URL
        } catch (e) {}
        return raw; // fallback: raw string (format lama)
    }

    // [DIUBAH] Ukuran QR sekarang FLEKSIBEL - diukur langsung dari lebar
    // #qrBox yang sebenarnya (ditentukan CSS: w-full dibatasi max-width
    // per breakpoint, lihat qr_page.php), bukan dua nilai breakpoint
    // tetap. QR selalu di-generate ULANG persis di resolusi itu supaya
    // pas & tajam di dalam bingkainya di ukuran layar manapun.
    function measuredQrSize() {
        var box = document.getElementById('qrBox');
        if (!box) return 256;
        var w = Math.round(box.getBoundingClientRect().width);
        return w > 0 ? w : 256;
    }

    function renderQR(text) {
        var box = document.getElementById('qrBox');
        if (!box) return;
        var size = measuredQrSize(); // ukur SEBELUM box dikosongkan
        box.innerHTML = '';
        if (qrObj) { try { qrObj.clear(); } catch(e){} qrObj = null; }
        if (!text) return;

        var content = qrContentFrom(text); // URL atau raw JSON

        qrObj = new QRCode(box, {
            text         : content,
            width        : size,
            height       : size,
            colorDark    : '#1e293b',
            colorLight   : '#ffffff',
            correctLevel : QRCode.CorrectLevel.H
        });

        // Animasi fade-in
        var wrap = document.getElementById('qrWrap');
        if (wrap) {
            wrap.classList.remove('qr-fadein');
            void wrap.offsetWidth;
            wrap.classList.add('qr-fadein');
        }
    }

    /* ── Background timer (refresh tanpa countdown display) ─ */
    function startBgTimer(mode) {
        timers[mode] = setInterval(function () {
            seconds[mode]--;
            if (seconds[mode] <= 0) {
                seconds[mode] = TICK;
                fetchAndUpdate(mode);
            }
        }, 1000);
    }

    /* ── Fetch token baru dari server ───────────────────── */
    function fetchAndUpdate(mode) {
        var type = (mode === 'in') ? 'check_in' : 'check_out';
        var fd = new FormData();
        fd.append('type', type);

        fetch(base + '/admin/getQrAjax', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'success' && data.qr_data) {
                    window._qrData[mode] = data.qr_data;
                    // Jika mode ini yang sedang ditampilkan, update QR di layar
                    if (currentMode === mode) renderQR(data.qr_data);
                }
            })
            .catch(function () {});
    }

    /* ── Manual refresh tombol "Generate Ulang" ─────────── */
    window.forceRefreshQR = function () {
        clearInterval(timers[currentMode]);
        seconds[currentMode] = TICK;
        var type = (currentMode === 'in') ? 'check_in' : 'check_out';
        var fd = new FormData();
        fd.append('type', type);
        fd.append('force', '1');
        fetch(base + '/admin/getQrAjax', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'success' && data.qr_data) {
                    window._qrData[currentMode] = data.qr_data;
                    renderQR(data.qr_data);
                }
            })
            .catch(function () {});
        startBgTimer(currentMode);
    };

    /* ── Regenerasi QR saat ukuran frame-nya berubah cukup signifikan ───
       Supaya QR tetap tajam (bukan di-stretch CSS dari bitmap lama) saat
       lebar #qrBox berubah, mis. jendela browser di-resize melewati
       breakpoint lg. Threshold 10px mencegah regenerasi berlebihan untuk
       perubahan sub-pixel kecil. */
    var lastQrSize = measuredQrSize();
    var resizeTimer = null;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            var size = measuredQrSize();
            if (Math.abs(size - lastQrSize) >= 10) {
                lastQrSize = size;
                if (window._qrData && window._qrData[currentMode]) {
                    renderQR(window._qrData[currentMode]);
                }
            }
        }, 200);
    });

})();
