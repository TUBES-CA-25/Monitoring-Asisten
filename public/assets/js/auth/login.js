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

      fetch(`${BASE_URL}/auth/login`, {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.status === "success") {
            showModal("success", data.title, data.message);
            setTimeout(() => {
              window.location.href = data.redirect;
            }, 1500);
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
