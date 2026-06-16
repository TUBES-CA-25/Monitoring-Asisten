(() => {
  "use strict";

  const BASE_URL = (window.APP_CONFIG && window.APP_CONFIG.BASE_URL) || "";
  const USER_ROLE = (window.APP_CONFIG && window.APP_CONFIG.USER_ROLE) || "User";

  let redirectUrl = null;
  let cropper = null;

  document.addEventListener("DOMContentLoaded", () => {
    bindForm();
    bindCropper();
    bindConfirmModal();
    bindPasswordToggle();
  });

  // [BARU - Edit Email/Password] toggle "Ganti Password" - sama seperti
  // admin/users.js: saat dinonaktifkan, #inputPass dikosongkan & tidak
  // wajib (password lama dipertahankan); saat diaktifkan, field tampil
  // dengan minlength="8" yang sudah diatur di markup.
  function bindPasswordToggle() {
    const checkbox = document.getElementById("inputChangePassword");
    const wrap = document.getElementById("passwordFieldWrap");
    const input = document.getElementById("inputPass");

    if (!checkbox || !wrap || !input) return;

    checkbox.addEventListener("change", () => {
      if (checkbox.checked) {
        wrap.classList.remove("hidden");
        input.required = true;
        input.value = "";
        input.focus();
      } else {
        wrap.classList.add("hidden");
        input.required = false;
        input.value = "";
      }
    });
  }

  function bindForm() {
    const form = document.getElementById("profileForm");
    if (!form) return;

    form.addEventListener("submit", (e) => {
      e.preventDefault();
      openConfirmModal();
    });
  }

  function submitData() {
    const form = document.getElementById("profileForm");
    const btn = document.getElementById("saveBtn");
    if (!form || !btn) return;

    const formData = new FormData(form);
    const originalHTML = btn.innerHTML;

    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Menyimpan...';
    btn.disabled = true;

    let url = "";
    if (USER_ROLE === "User") url = `${BASE_URL}/user/updateProfile`;
    else if (USER_ROLE === "Admin") url = `${BASE_URL}/admin/updateProfile`;
    else url = `${BASE_URL}/kepalalab/updateProfile`;

    fetch(url, { method: "POST", body: formData })
      .then((res) => res.json())
      .then((data) => {
        btn.innerHTML = originalHTML;
        btn.disabled = false;

        if (data.status === "success") {
          redirectUrl = data.redirect || null;
          showAlert("success", data.title || "Berhasil", data.message);
        } else {
          showAlert("error", data.title || "Gagal", data.message);
        }
      })
      .catch((err) => {
        console.error(err);
        btn.innerHTML = originalHTML;
        btn.disabled = false;
        showAlert("error", "Error Sistem", "Gagal menghubungi server.");
      });
  }

  function bindConfirmModal() {
    const yesBtn = document.getElementById("confirmYesBtn");
    if (!yesBtn) return;

    yesBtn.addEventListener("click", () => {
      closeConfirmModal();
      submitData();
    });
  }

  function openConfirmModal() {
    const modal = document.getElementById("confirmModal");
    if (modal) modal.classList.remove("hidden");
  }

  window.closeConfirmModal = function () {
    const modal = document.getElementById("confirmModal");
    if (modal) modal.classList.add("hidden");
  };

  function showAlert(type, title, message) {
    const modal = document.getElementById("customAlertModal");
    const iconBg = document.getElementById("alertIconBg");
    const icon = document.getElementById("alertIcon");
    const btn = document.getElementById("alertBtn");
    const backdrop = document.getElementById("alertBackdrop");
    const content = document.getElementById("alertContent");

    document.getElementById("alertTitle").innerText = title;
    document.getElementById("alertMessage").innerText = message;

    if (type === "success") {
      iconBg.className = "w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-green-100 text-green-600";
      icon.className = "fas fa-check text-3xl";
      btn.className =
        "w-full py-3 rounded-xl font-bold text-white shadow-lg bg-green-600 hover:bg-green-700 transition";
    } else {
      iconBg.className = "w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-red-100 text-red-600";
      icon.className = "fas fa-times text-3xl";
      btn.className =
        "w-full py-3 rounded-xl font-bold text-white shadow-lg bg-red-600 hover:bg-red-700 transition";
    }

    modal.classList.remove("hidden");
    setTimeout(() => {
      backdrop.classList.remove("opacity-0");
      content.classList.remove("scale-90", "opacity-0");
      content.classList.add("scale-100", "opacity-100");
    }, 10);
  }

  window.closeCustomAlert = function () {
    const modal = document.getElementById("customAlertModal");
    const backdrop = document.getElementById("alertBackdrop");
    const content = document.getElementById("alertContent");

    backdrop.classList.add("opacity-0");
    content.classList.remove("scale-100", "opacity-100");
    content.classList.add("scale-90", "opacity-0");

    setTimeout(() => {
      modal.classList.add("hidden");
      if (redirectUrl) {
        window.location.href = redirectUrl;
      }
    }, 300);
  };

  function bindCropper() {
    const photoInput = document.getElementById("photoInput");
    const previewImg = document.getElementById("previewImg");
    const croppedInput = document.getElementById("croppedImage");
    const cropperModal = document.getElementById("cropperModal");
    const imageToCrop = document.getElementById("imageToCrop");
    const cropBtn = document.getElementById("cropImageBtn");
    const closeBtn = document.getElementById("closeModalBtn");
    const cancelBtn = document.getElementById("cancelCropBtn");

    if (!photoInput || !previewImg || !cropperModal || !imageToCrop) return;

    photoInput.addEventListener("change", (e) => {
      const file = e.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = () => {
        imageToCrop.src = reader.result;
        cropperModal.classList.remove("hidden");
        cropperModal.classList.add("flex");

        if (cropper) cropper.destroy();
        cropper = new Cropper(imageToCrop, {
          aspectRatio: 1,
          viewMode: 1,
        });
      };
      reader.readAsDataURL(file);
    });

    cropBtn?.addEventListener("click", () => {
      if (!cropper) return;

      const canvas = cropper.getCroppedCanvas({ width: 500, height: 500 });
      const base64Image = canvas.toDataURL("image/jpeg");

      previewImg.src = base64Image;
      croppedInput.value = base64Image;

      closeCropper(cropperModal, photoInput);
    });

    closeBtn?.addEventListener("click", () => {
      closeCropper(cropperModal, photoInput);
    });

    cancelBtn?.addEventListener("click", () => {
      closeCropper(cropperModal, photoInput);
    });
  }

  function closeCropper(modal, input) {
    modal.classList.add("hidden");
    modal.classList.remove("flex");
    input.value = "";
    if (cropper) {
      cropper.destroy();
      cropper = null;
    }
  }
})();
