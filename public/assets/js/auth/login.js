(() => {
  "use strict";

  // [DIPERBAIKI - Tahap 16] auth/login.php adalah halaman BERDIRI SENDIRI
  // (punya <html>/<head>/<body> sendiri) yang TIDAK memuat layout/footer.php
  // - sehingga "window.APP_CONFIG" TIDAK PERNAH ada di halaman ini. Halaman
  // ini punya data-island SENDIRI: "window.BASE_URL = <?= json_encode(BASE_URL) ?>;"
  // (lihat akhir app/views/auth/login.php). Versi sebelumnya
  // (window.BASE_URL) SUDAH BENAR - perubahan Tahap 14 ke
  // "window.APP_CONFIG?.BASE_URL" adalah REGRESI (APP_CONFIG selalu
  // undefined di sini -> BASE_URL jadi "" -> fetch ke "/auth/login" salah
  // arah -> 404). Dikembalikan ke window.BASE_URL.
  const BASE_URL = window.BASE_URL || "";

  document.addEventListener("DOMContentLoaded", () => {
    initBackgroundSlider();
    bindLoginForm();
  });
  function initBackgroundSlider() {
    const slides = document.querySelectorAll(".bg-slide");
    if (!slides.length) return;

    let currentSlide = 0;

    setInterval(() => {
      slides[currentSlide].classList.remove("active");
      currentSlide = (currentSlide + 1) % slides.length;
      slides[currentSlide].classList.add("active");
    }, 5000);
  }
  window.togglePass = function () {
    const pwd = document.getElementById("passwordInput");
    const icon = document.getElementById("togglePassword");

    if (!pwd || !icon) return;

    if (pwd.type === "password") {
      pwd.type = "text";
      icon.classList.replace("fa-eye", "fa-eye-slash");
      icon.classList.add("text-cyan-400");
    } else {
      pwd.type = "password";
      icon.classList.replace("fa-eye-slash", "fa-eye");
      icon.classList.remove("text-cyan-400");
    }
  };
  function showModal(type, title, message) {
    const modal = document.getElementById("modalAlert");
    const iconBg = document.getElementById("modalIconBg");
    const icon = document.getElementById("modalIcon");
    const btn = document.getElementById("modalBtn");

    document.getElementById("modalTitle").innerText = title;
    document.getElementById("modalMessage").innerText = message;

    if (type === "success") {
      iconBg.className =
        "w-20 h-20 rounded-full flex items-center justify-center mb-5 bg-green-500/20 text-green-400 shadow-[0_0_20px_rgba(74,222,128,0.4)]";
      icon.className = "fas fa-check text-4xl";
      btn.classList.add("hidden");
    } else {
      iconBg.className =
        "w-20 h-20 rounded-full flex items-center justify-center mb-5 bg-red-500/20 text-red-400 shadow-[0_0_20px_rgba(248,113,113,0.4)]";
      icon.className = "fas fa-times text-4xl";
      btn.className =
        "w-full py-3.5 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02] active:scale-95 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 shadow-red-900/30";
      btn.classList.remove("hidden");
    }

    modal.classList.add("show");
  }

  window.closeModal = function () {
    document.getElementById("modalAlert").classList.remove("show");
  };
  function bindLoginForm() {
    const form = document.getElementById("loginForm");
    if (!form) return;

    const emailInput = document.getElementById("emailInput");
    const passwordInput = document.getElementById("passwordInput");
    const submitBtn = document.getElementById("submitBtn");
    const btnText = document.getElementById("btnText");
    const btnIcon = document.getElementById("btnIcon");
    const btnSpinner = document.getElementById("btnSpinner");

    form.addEventListener("submit", (e) => {
      e.preventDefault();

      const email = emailInput.value.trim();
      const password = passwordInput.value.trim();

      if (!email || !password) {
        showModal("error", "Data Tidak Lengkap", "Harap isi Email dan Password Anda.");
        return;
      }

      setLoadingState(true);

      const formData = new FormData();
      formData.append("email", email);
      formData.append("password", password);

      // [Fix] Login page tidak memuat global.js (yang berisi auto-patch CSRF),
      // sehingga token harus disisipkan manual dari <meta name="csrf-token">.
      const csrfMeta  = document.querySelector('meta[name="csrf-token"]');
      const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

      fetch(`${BASE_URL}/auth/login`, {
        method: "POST",
        headers: csrfToken ? { "X-CSRF-TOKEN": csrfToken } : {},
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.status === "success") {
            showModal("success", data.title, data.message);
            setTimeout(() => { window.location.href = data.redirect; }, 1500);
          } else if (data.status === "locked") {
            // [Item 9] Tampilkan modal lockout dengan countdown
            setLoadingState(false);
            showLockoutModal(data.remaining, data.round, data.contact_admin);
          } else {
            showModal("error", data.title, data.message);
            resetInputs();
            setLoadingState(false);
          }
        })
        .catch(() => {
          showModal("error", "Kesalahan Sistem", "Terjadi masalah koneksi atau server.");
          setLoadingState(false);
        });
    });

    function resetInputs() {
      emailInput.value = "";
      passwordInput.value = "";
      emailInput.focus();
    }

    function setLoadingState(state) {
      submitBtn.disabled = state;

      if (state) {
        submitBtn.classList.add("opacity-75", "cursor-not-allowed");
        btnText.innerText = "Memproses...";
        btnIcon.classList.add("hidden");
        btnSpinner.classList.remove("hidden");
      } else {
        submitBtn.classList.remove("opacity-75", "cursor-not-allowed");
        btnText.innerText = "Masuk Sekarang";
        btnIcon.classList.remove("hidden");
        btnSpinner.classList.add("hidden");
      }
    }
  }
})();

// ================================================================
// [Item 9] LOCKOUT MODAL — countdown, quotes rotation, page-refresh,
//          Tunggu button (dismiss temporarily), pre-submit guard
// ================================================================
(function initLockoutSystem() {

    var _countdownTimer  = null;
    var _quoteTimer      = null;
    var _currentQuoteIdx = -1;
    var _quotes          = window.LOCKOUT_QUOTES || [];
    var _lockoutEndTime  = 0;   // Unix timestamp (detik) saat lockout berakhir
    var _lockoutRound    = 0;   // Ronde lockout saat ini
    var _modalDismissed  = false;

    /* ── Elemen DOM ─────────────────────────────────────── */
    var modal       = document.getElementById('lockoutModal');
    var display     = document.getElementById('countdownDisplay');
    var humanText   = document.getElementById('countdownHuman');
    var progressBar = document.getElementById('lockoutProgressBar');
    var subtitle    = document.getElementById('lockoutSubtitle');
    var adminMsg    = document.getElementById('contactAdminMsg');
    var quoteText   = document.getElementById('quoteText');
    var quoteSource = document.getElementById('quoteSource');

    /* ── Format waktu ───────────────────────────────────── */
    function fmtMM_SS(s) {
        var m = Math.floor(s / 60);
        var r = s % 60;
        return (m < 10 ? '0' : '') + m + ':' + (r < 10 ? '0' : '') + r;
    }
    function fmtHuman(s) {
        if (s <= 0) return 'Selesai';
        if (s < 60) return s + ' detik lagi';
        var m = Math.floor(s / 60);
        var r = s % 60;
        return (m > 0 ? m + ' menit ' : '') + (r > 0 ? r + ' detik' : '') + ' lagi';
    }

    /* ── Rotasi quote acak dengan fade ─────────────────── */
    function nextQuote() {
        if (!_quotes.length || !quoteText) return;
        // Pilih index berbeda dari index saat ini
        var next = _currentQuoteIdx;
        if (_quotes.length > 1) {
            while (next === _currentQuoteIdx) {
                next = Math.floor(Math.random() * _quotes.length);
            }
        }
        _currentQuoteIdx = next;
        var q = _quotes[next];
        // Fade out
        if (quoteText)   quoteText.style.opacity   = '0';
        if (quoteSource) quoteSource.style.opacity = '0';
        setTimeout(function () {
            if (quoteText)   { quoteText.textContent   = '\u201c' + q.text + '\u201d'; quoteText.style.opacity   = '1'; }
            if (quoteSource) { quoteSource.textContent = q.source;                       quoteSource.style.opacity = '1'; }
        }, 600);
    }

    /* ── Aktifkan / nonaktifkan form ───────────────────── */
    function setFormLocked(locked) {
        var form = document.getElementById('loginForm');
        if (!form) return;
        form.querySelectorAll('input, button').forEach(function (el) {
            el.disabled = locked;
        });
    }

    /* ── Tutup modal sementara (Tunggu button) ─────────── */
    window.closeLockoutModalTemp = function () {
        if (!modal) return;
        modal.classList.add('hidden');
        _modalDismissed = true;
        // Re-aktifkan input (tapi submit diblokir via pre-submit guard)
        // Biarkan user melihat form, tapi tidak bisa submit
        var form = document.getElementById('loginForm');
        if (form) {
            form.querySelectorAll('input').forEach(function (el) { el.disabled = false; });
            var btn = document.getElementById('submitBtn');
            if (btn) btn.disabled = true; // tetap disabled sampai countdown habis
        }
    };

    /* ── Tampilkan modal lockout ────────────────────────── */
    window.showLockoutModal = function (remainingSeconds, round, contactAdmin) {
        if (!modal) return;

        _lockoutRound    = round || 1;
        _lockoutEndTime  = Math.floor(Date.now() / 1000) + remainingSeconds;
        _modalDismissed  = false;

        var totalDuration = _lockoutRound * 10;
        if (totalDuration <= 0) totalDuration = remainingSeconds;

        // Subtitle
        if (subtitle) {
            var durStr = totalDuration >= 60
                ? Math.floor(totalDuration / 60) + ' menit ' + (totalDuration % 60 > 0 ? totalDuration % 60 + ' detik' : '')
                : totalDuration + ' detik';
            subtitle.textContent = 'Ronde ke-' + _lockoutRound + ' \u2014 durasi ' + durStr.trim();
        }

        // Pesan admin
        if (adminMsg) adminMsg.classList.toggle('hidden', !contactAdmin);

        // Kunci form
        setFormLocked(true);

        // Tampilkan modal
        modal.classList.remove('hidden');

        // ── Countdown ──
        var remaining = remainingSeconds;
        clearInterval(_countdownTimer);

        function tick() {
            remaining = Math.max(0, Math.floor(_lockoutEndTime - Date.now() / 1000));

            if (display)    display.textContent   = fmtMM_SS(remaining);
            if (humanText)  humanText.textContent = fmtHuman(remaining);

            // Progress bar
            if (progressBar) {
                var pct = totalDuration > 0 ? Math.max(0, (remaining / totalDuration) * 100) : 0;
                progressBar.style.width = pct.toFixed(1) + '%';
            }

            if (remaining <= 0) {
                clearInterval(_countdownTimer);
                clearInterval(_quoteTimer);
                // Lockout selesai → sembunyikan modal & buka kembali form
                modal.classList.add('hidden');
                setFormLocked(false);
                _lockoutEndTime = 0;
                _modalDismissed = false;
                if (display)    display.textContent    = '00:00';
                if (progressBar) progressBar.style.width = '0%';
            }
        }

        tick();
        _countdownTimer = setInterval(tick, 500); // 500ms agar lebih akurat

        // Quote: tampil pertama, lalu ganti tiap 5 detik
        nextQuote();
        clearInterval(_quoteTimer);
        _quoteTimer = setInterval(nextQuote, 5000);
    };

    /* ── Cek lockout saat halaman dimuat (termasuk setelah refresh) ── */
    document.addEventListener('DOMContentLoaded', function () {
        var cfg          = window.APP_CONFIG || {};
        var lockoutUntil = parseInt(cfg.lockout_until || '0', 10);
        var lockoutRound = parseInt(cfg.lockout_round || '0', 10);
        var contactAdmin = lockoutRound >= 5;

        if (lockoutUntil > 0) {
            var nowSec    = Math.floor(Date.now() / 1000);
            var remaining = lockoutUntil - nowSec;
            if (remaining > 0) {
                showLockoutModal(remaining, lockoutRound, contactAdmin);
            }
        }

        // ── Pre-submit guard: cek lockout lokal sebelum kirim request ──
        // Jika user menutup modal sementara (Tunggu), submit tetap dicegat
        // tanpa membuang request ke server.
        var form = document.getElementById('loginForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                var nowSec = Math.floor(Date.now() / 1000);
                if (_lockoutEndTime > nowSec) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    var remaining = _lockoutEndTime - nowSec;
                    showLockoutModal(remaining, _lockoutRound, _lockoutRound >= 5);
                }
                // Jika tidak dalam lockout, biarkan handler submit asli berjalan
            }, true); // useCapture=true agar lebih awal dari handler bindLoginForm
        }
    });

})();
