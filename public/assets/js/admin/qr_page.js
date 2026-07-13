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
    document.addEventListener('DOMContentLoaded', function () {
        var qrIn  = safeParse(cfg.qrIn);
        var qrOut = safeParse(cfg.qrOut);

        // Cache data awal kedua mode
        window._qrData = { in: qrIn ? JSON.stringify(qrIn) : null, out: qrOut ? JSON.stringify(qrOut) : null };

        // Tampilkan mode default (masuk)
        setMode('in');

        // Background timer untuk refresh otomatis kedua mode
        startBgTimer('in');
        startBgTimer('out');
    });

    /* ── Parse JSON aman ────────────────────────────────── */
    function safeParse(v) {
        try { return typeof v === 'string' ? JSON.parse(v) : v; } catch (e) { return null; }
    }

    /* ── Ganti mode (toggle) ────────────────────────────── */
    window.setMode = function (mode) {
        currentMode = mode;
        var mc = modeConfig[mode];

        // Update header card
        var header = document.getElementById('qrCardHeader');
        if (header) header.className = mc.headerCls + ' px-5 py-3.5 flex items-center gap-3';

        var icon = document.getElementById('qrCardIcon');
        if (icon) icon.innerHTML = '<i class="fas ' + mc.icon + ' text-white"></i>';

        var titleEl = document.getElementById('qrCardTitle');
        if (titleEl) titleEl.innerHTML =
            '<h2 class="font-extrabold leading-tight text-base">' + mc.title + '</h2>' +
            '<p class="' + mc.subtitleCls + ' text-[11px]">' + mc.subtitle + '</p>';

        // Update QR border/pulse
        var wrap = document.getElementById('qrWrap');
        if (wrap) {
            wrap.className = 'rounded-2xl border-4 p-3 bg-white qr-pulse-' + (mode === 'in' ? 'in' : 'out') + ' ' + mc.borderCls;
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
        if (btnIn)  btnIn.className  = 'relative z-10 flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-bold transition ' + (mode === 'in'  ? 'text-white'  : 'text-gray-500');
        if (btnOut) btnOut.className = 'relative z-10 flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-bold transition ' + (mode === 'out' ? 'text-white' : 'text-gray-500');

        // Render QR untuk mode ini
        renderQR(window._qrData[mode]);
    };

    /* ── Render QR ──────────────────────────────────────── */
    function renderQR(text) {
        var box = document.getElementById('qrBox');
        if (!box) return;
        box.innerHTML = '';
        if (qrObj) { try { qrObj.clear(); } catch(e){} qrObj = null; }
        if (!text) return;

        qrObj = new QRCode(box, {
            text         : text,
            width        : 256,
            height       : 256,
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

    document.addEventListener('DOMContentLoaded', () => {
        initClock();
    });

    function initClock() {
        updateClock();
        setInterval(updateClock, 1000);
    }

    function updateClock() {
        const now = new Date();

        const dateOptions = { day: '2-digit', month: 'long', year: 'numeric' };
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };

        const elDate = document.getElementById('liveDate');
        const elTime = document.getElementById('liveTime');

        if (elDate) elDate.innerText = now.toLocaleDateString('id-ID', dateOptions);
        if (elTime) elTime.innerText = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':');
    }

})();
