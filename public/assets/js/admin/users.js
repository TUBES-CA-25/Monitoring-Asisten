(() => {
  "use strict";

  const BASE_URL = window.BASE_URL || "";

  let currentMode = "add";
  let deleteTargetId = null;

  document.addEventListener("DOMContentLoaded", () => {
    initClock();
    bindDeleteConfirm();
    bindUserForm();
  });

  function initClock() {
    const elDate = document.getElementById("liveDate");
    const elTime = document.getElementById("liveTime");

    if (!elDate || !elTime) return;

    function updateClock() {
      const now = new Date();
      elDate.innerText = now.toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "long",
        year: "numeric",
      });
      elTime.innerText = now
        .toLocaleTimeString("id-ID", {
          hour: "2-digit",
          minute: "2-digit",
          second: "2-digit",
          hour12: false,
        })
        .replace(/\./g, ":");
    }

    setInterval(updateClock, 1000);
    updateClock();
  }

  window.toggleRoleFields = function () {
    const role = document.getElementById("inputRole")?.value;
    const userFields = document.querySelectorAll(".user-field");

    if (role === "User") {
      userFields.forEach((el) => el.classList.remove("hidden"));
    } else {
      userFields.forEach((el) => el.classList.add("hidden"));

      ["inputNim", "inputClass", "inputProdi", "inputLab", "inputInterest"].forEach(
        (id) => {
          const el = document.getElementById(id);
          if (el) el.value = "";
        }
      );
    }
  };

  window.openUserModal = function (mode, data = null) {
    currentMode = mode;

    const modal = document.getElementById("modalUser");
    const content = document.getElementById("modalContent");
    const title = document.getElementById("modalTitle");
    const form = document.getElementById("userForm");
    const passInput = document.getElementById("inputPass");
    const passReq = document.getElementById("passReq");
    const passHint = document.getElementById("passHint");

    if (!modal || !content || !form) return;

    form.reset();

    if (mode === "add") {
      title.innerText = "Tambah Pengguna Baru";
      passInput.required = true;
      passReq.classList.remove("hidden");
      passHint.innerText = "";
    } else {
      title.innerText = "Edit Data Pengguna";

      document.getElementById("inputId").value = data.id || "";
      document.getElementById("inputName").value = data.name || "";
      document.getElementById("inputEmail").value = data.email || "";
      document.getElementById("inputRole").value = data.role || "";
      document.getElementById("inputPosition").value = data.position || "";
      document.getElementById("inputPhone").value = data.no_telp || "";
      document.getElementById("inputAddress").value = data.alamat || "";
      document.getElementById("inputGender").value = data.jenis_kelamin || "";

      if (data.role === "User") {
        document.getElementById("inputNim").value = data.nim || "";
        document.getElementById("inputClass").value = data.kelas || "";
        document.getElementById("inputProdi").value = data.prodi || "";
        document.getElementById("inputLab").value = data.id_lab || "";
        document.getElementById("inputInterest").value = data.peminatan || "";
      }

      passInput.required = false;
      passReq.classList.add("hidden");
      passHint.innerText = "(Kosongkan jika tidak ingin mengubah password)";
    }

    toggleRoleFields();

    modal.classList.remove("hidden");
    setTimeout(() => {
      content.classList.remove("scale-95", "opacity-0");
      content.classList.add("scale-100", "opacity-100");
    }, 10);
  };

  window.closeUserModal = function () {
    const modal = document.getElementById("modalUser");
    const content = document.getElementById("modalContent");

    if (!modal || !content) return;

    content.classList.remove("scale-100", "opacity-100");
    content.classList.add("scale-95", "opacity-0");

    setTimeout(() => modal.classList.add("hidden"), 300);
  };

  window.triggerDeleteUser = function (id) {
    deleteTargetId = id;

    const modal = document.getElementById("deleteModal");
    const content = modal?.querySelector("div.relative.z-10");

    if (!modal || !content) return;

    modal.classList.remove("hidden");
    setTimeout(() => {
      content.classList.remove("scale-95", "opacity-0");
      content.classList.add("scale-100", "opacity-100");
    }, 10);
  };

  window.closeDeleteModal = function () {
    const modal = document.getElementById("deleteModal");
    const content = modal?.querySelector("div.relative.z-10");

    if (!modal || !content) return;

    content.classList.remove("scale-100", "opacity-100");
    content.classList.add("scale-95", "opacity-0");

    setTimeout(() => {
      modal.classList.add("hidden");
      deleteTargetId = null;
    }, 200);
  };

  function bindDeleteConfirm() {
    const btn = document.getElementById("confirmDeleteBtn");
    if (!btn) return;

    btn.addEventListener("click", () => {
      if (!deleteTargetId) return;

      const originalText = btn.innerText;
      btn.innerText = "Menghapus...";
      btn.disabled = true;

      fetch(`${BASE_URL}/admin/deleteUser?id=${deleteTargetId}`)
        .then((res) => res.json())
        .then((data) => {
          closeDeleteModal();
          btn.innerText = originalText;
          btn.disabled = false;
          showAlert(data.status, data.title, data.message);
        })
        .catch(() => {
          closeDeleteModal();
          btn.innerText = originalText;
          btn.disabled = false;
          showAlert("error", "Error", "Terjadi kesalahan jaringan.");
        });
    });
  }

  function bindUserForm() {
    const form = document.getElementById("userForm");
    const btn = document.getElementById("btnSave");

    if (!form || !btn) return;

    form.addEventListener("submit", (e) => {
      e.preventDefault();

      const formData = new FormData(form);
      const url =
        currentMode === "add"
          ? `${BASE_URL}/admin/addUser`
          : `${BASE_URL}/admin/editUser`;

      const originalText = btn.innerText;
      btn.innerText = "Menyimpan...";
      btn.disabled = true;

      fetch(url, { method: "POST", body: formData })
        .then((res) =>
          res.text().then((text) => {
            try {
              return JSON.parse(text);
            } catch {
              throw new Error("Respon server tidak valid.");
            }
          })
        )
        .then((data) => {
          btn.innerText = originalText;
          btn.disabled = false;
          closeUserModal();
          showAlert(data.status, data.title || "Info", data.message);
        })
        .catch(() => {
          btn.innerText = originalText;
          btn.disabled = false;
          closeUserModal();
          showAlert("error", "Error Sistem", "Gagal memproses data.");
        });
    });
  }

  function showAlert(type, title, msg) {
    const modal = document.getElementById("alertModal");
    const icon = document.getElementById("alertIcon");
    const titleEl = document.getElementById("alertTitle");
    const msgEl = document.getElementById("alertMsg");
    const btn = document.getElementById("alertBtn");

    if (!modal || !icon || !titleEl || !msgEl || !btn) return;

    titleEl.innerText = title;
    msgEl.innerText = msg;

    if (type === "success") {
      icon.className =
        "w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 bg-green-100 text-green-600";
      icon.innerHTML = '<i class="fas fa-check text-3xl"></i>';
      btn.className =
        "w-full py-3 rounded-xl font-bold text-white bg-green-600 hover:bg-green-700 shadow-lg";
      btn.onclick = () => window.location.reload();
    } else {
      icon.className =
        "w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 bg-red-100 text-red-600";
      icon.innerHTML = '<i class="fas fa-times text-3xl"></i>';
      btn.className =
        "w-full py-3 rounded-xl font-bold text-white bg-red-600 hover:bg-red-700 shadow-lg";
      btn.onclick = () => modal.classList.add("hidden");
    }

    modal.classList.remove("hidden");
  }
})();
