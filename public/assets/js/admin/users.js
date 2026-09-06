(() => {
  "use strict";

  let currentMode = "add";
  let deleteTargetId = null;
  // [BARU] id_user mentah dari akun yang sedang diedit + status yang
  // sedang menunggu konfirmasi toggle aktif/nonaktif (lihat
  // handleAccountStatusChange / confirmStatusToggle di bawah).
  let editingUserId = null;
  let pendingStatusToggle = null;
  // [BARU - Fitur Crop Foto] instance Cropper.js aktif (sama seperti
  // common/edit_profile.js)
  let cropper = null;

  document.addEventListener("DOMContentLoaded", () => {
    initClock();
    bindDeleteConfirm();
    bindUserForm();
    bindCropper();
    bindUserSearch();
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

  window.switchUserTab = function (tab) {
    const tabAkun = document.getElementById("tabAkun");
    const tabPribadi = document.getElementById("tabPribadi");
    const btnAkun = document.getElementById("tabBtnAkun");
    const btnPribadi = document.getElementById("tabBtnPribadi");

    if (!tabAkun || !tabPribadi || !btnAkun || !btnPribadi) return;

    if (tab === "pribadi") {
      tabAkun.classList.add("hidden");
      tabPribadi.classList.remove("hidden");
      btnPribadi.classList.add("active");
      btnPribadi.classList.remove("inactive");
      btnAkun.classList.add("inactive");
      btnAkun.classList.remove("active");
    } else {
      tabPribadi.classList.add("hidden");
      tabAkun.classList.remove("hidden");
      btnAkun.classList.add("active");
      btnAkun.classList.remove("inactive");
      btnPribadi.classList.add("inactive");
      btnPribadi.classList.remove("active");
    }
  };

  window.toggleChangePassword = function () {
    const checkbox = document.getElementById("inputChangePassword");
    const wrap = document.getElementById("passwordFieldWrap");
    const input = document.getElementById("inputPass");
    const passReq = document.getElementById("passReq");

    if (!checkbox || !wrap || !input) return;

    if (checkbox.checked) {
      wrap.classList.remove("hidden");
      passReq?.classList.remove("hidden");
      input.required = true;
      input.value = "";
      input.focus();
    } else {
      wrap.classList.add("hidden");
      passReq?.classList.add("hidden");
      input.required = false;
      input.value = "";
    }
  };

  window.toggleRoleFields = function () {
    const role = document.getElementById("inputRole")?.value;
    const userFields = document.querySelectorAll(".user-field");
    const labFields = document.querySelectorAll(".lab-field");

    if (role === "User") {
      userFields.forEach((el) => el.classList.remove("hidden"));
    } else {
      userFields.forEach((el) => el.classList.add("hidden"));
    }

    if (role === "User" || role === "Kepala Lab") {
      labFields.forEach((el) => el.classList.remove("hidden"));
    } else {
      labFields.forEach((el) => el.classList.add("hidden"));
    }
  };

  // [BARU] Nomor telepon + kode negara (default Indonesia). Nilai tersimpan
  // memakai format internasional "+<kode><digit>" (mis. "+6281234567890").
  // parsePhoneForEdit membaca kembali nomor lama (format lokal "0..." atau
  // yang sudah "+...") supaya kode negara & digit terisi benar saat edit.
  const PHONE_COUNTRY_CODES = ["62", "60", "65", "1", "44", "61", "81"];

  function parsePhoneForEdit(raw) {
    raw = (raw || "").trim();
    if (raw.startsWith("+")) {
      const match = raw.match(/^\+(\d{1,3})(\d+)$/);
      if (match && PHONE_COUNTRY_CODES.includes(match[1])) {
        return { country: match[1], number: match[2] };
      }
    }
    // Format lama (lokal, mis. "08534186497") - anggap Indonesia, buang '0' di depan.
    let digits = raw.replace(/\D/g, "");
    if (digits.startsWith("0")) digits = digits.slice(1);
    return { country: "62", number: digits };
  }

  function applyPhoneToForm(rawPhone) {
    const parsed = parsePhoneForEdit(rawPhone);
    const countrySel = document.getElementById("inputPhoneCountry");
    const numberInput = document.getElementById("inputPhone");
    if (countrySel) countrySel.value = parsed.country;
    if (numberInput) numberInput.value = parsed.number;
  }

  // Gabungkan kode negara + digit menjadi satu nilai "+<kode><digit>" pada
  // input#inputPhone tepat sebelum submit, supaya server tetap menerima
  // SATU field "phone" seperti sebelumnya (tidak perlu ubah backend).
  // Jika percobaan submit sebelumnya gagal (nilai sudah pernah digabung),
  // ambil ulang bagian digit setelah kode negara supaya tidak dobel saat
  // submit diulang.
  function combinePhoneBeforeSubmit() {
    const countrySel = document.getElementById("inputPhoneCountry");
    const numberInput = document.getElementById("inputPhone");
    if (!countrySel || !numberInput) return;
    const value = numberInput.value.trim();
    let digits;
    if (value.startsWith("+")) {
      const m = value.match(/^\+\d{1,3}(\d+)$/);
      digits = m ? m[1] : value.replace(/\D/g, "");
    } else {
      digits = value.replace(/\D/g, "");
    }
    numberInput.value = digits ? `+${countrySel.value}${digits}` : "";
  }

  window.openUserModal = function (mode, data = null) {
    currentMode = mode;

    const modal = document.getElementById("modalUser");
    const content = document.getElementById("modalContent");
    const title = document.getElementById("modalTitle");
    const form = document.getElementById("userForm");
    const passInput = document.getElementById("inputPass");
    const passReq = document.getElementById("passReq");
    const passLabelText = document.getElementById("passLabelText");
    const passHint = document.getElementById("passHint");
    const passToggleWrap = document.getElementById("passwordToggleWrap");
    const passFieldWrap = document.getElementById("passwordFieldWrap");
    const changePassCheckbox = document.getElementById("inputChangePassword");
    // [BARU - Fitur Crop Foto]
    const previewImg = document.getElementById("previewImg");
    const croppedInput = document.getElementById("croppedImage");
    const currentPhotoLabel = document.getElementById("currentPhotoLabel");
    const currentPhotoName = document.getElementById("currentPhotoName");

    if (!modal || !content || !form) return;

    form.reset();
    switchUserTab("akun");

    if (mode === "add") {
      title.innerText = "Tambah Pengguna Baru";
      editingUserId = null;
      pendingStatusToggle = null;

      // [BARU - Fitur Crop Foto] mode tambah: belum ada foto, tampilkan
      // placeholder default & sembunyikan indikator "Foto sudah ada".
      if (croppedInput) croppedInput.value = "";
      if (currentPhotoLabel) currentPhotoLabel.classList.add("hidden");
      if (previewImg) previewImg.src = "https://ui-avatars.com/api/?name=?&background=random&bold=true";

      // [BARU – Tahap 30] Mode tambah: sembunyikan toggle status akun
      const statusWrapAdd = document.getElementById("accountStatusToggleWrap");
      if (statusWrapAdd) statusWrapAdd.style.display = "none";

      // Mode tambah: tidak ada password lama, jadi langsung tampilkan
      // kolom password tanpa toggle "Ganti Password".
      passToggleWrap?.classList.add("hidden");
      passFieldWrap?.classList.remove("hidden");
      if (passLabelText) passLabelText.innerText = "Password";
      passReq?.classList.remove("hidden");
      passInput.required = true;
      passInput.value = "";
      passHint.innerText = "";
    } else {
      title.innerText = "Edit Data Pengguna";
      // [BARU] id mentah disimpan di closure (tidak di form) — dipakai
      // khusus untuk memanggil /admin/toggleUserStatus (lihat
      // confirmStatusToggle) yang butuh id_user asli, bukan hash.
      editingUserId = data.id || data.id_user || null;
      pendingStatusToggle = null;

      // [SECURITY] Kirim hash bukan integer mentah ke form hidden field
      document.getElementById("inputId").value = data.id_hash || data.id || "";
      document.getElementById("inputName").value = data.name || "";
      document.getElementById("inputEmail").value = data.email || "";
      document.getElementById("inputRole").value = data.role || "";
      document.getElementById("inputPosition").value = data.position || "";
      applyPhoneToForm(data.no_telp || "");
      document.getElementById("inputAddress").value = data.alamat || "";
      document.getElementById("inputGender").value = data.jenis_kelamin || "";

      // [BARU – Tahap 30] Status akun: tampilkan toggle, set state
      const statusWrap   = document.getElementById("accountStatusToggleWrap");
      const statusToggle = document.getElementById("inputAccountActive");
      const statusHint   = document.getElementById("accountStatusHint");
      const statusInput  = document.getElementById("inputStatusAccount");

      // [BARU – Tahap 33] Toggle hanya untuk role User (Asisten).
      // Admin dan Kepala Lab dikecualikan.
      const editedRole = data.role || "";
      if (statusWrap) statusWrap.style.display = (editedRole === "User") ? "flex" : "none";

      const isActive = (data.status_account ?? "ACTIVE") === "ACTIVE";
      if (statusToggle) statusToggle.checked = isActive;
      if (statusInput)  statusInput.value    = isActive ? "ACTIVE" : "INACTIVE";
      if (statusHint)   statusHint.innerText = isActive
          ? "Akun aktif — user dapat login dan mengakses semua fitur."
          : "Akun nonaktif — user dapat login namun tidak bisa mengakses fitur.";

      // [PERBAIKAN] Isi semua data profil yang sudah ada terlepas dari role,
      // agar tidak ada data yang "hilang" saat disimpan kembali.
      document.getElementById("inputNim").value = data.nim || "";
      document.getElementById("inputClass").value = data.kelas || "";
      document.getElementById("inputAngkatan").value = data.angkatan || "";
      document.getElementById("inputProdi").value = data.prodi || "";
      document.getElementById("inputLab").value = data.id_lab || "";
      document.getElementById("inputInterest").value = data.peminatan || "";

      // [BARU - Fitur Crop Foto] tampilkan foto yang sudah ada (jika ada)
      // beserta nama filenya, supaya admin tidak mengira kolom foto kosong
      // dan tidak sengaja "menghapus" foto yang sudah ter-upload.
      if (croppedInput) croppedInput.value = "";
      // [PERBAIKAN] File placeholder fisiknya sekarang "default.webp"
      // (sebelumnya "default.jpg", sudah tidak ada lagi filenya).
      const hasPhoto = data.photo_profile && data.photo_profile !== "default.jpg" && data.photo_profile !== "default.webp";
      if (hasPhoto) {
        if (previewImg) previewImg.src = window.APP_CONFIG.BASE_URL + "/uploads/profile/" + data.photo_profile;
        if (currentPhotoName) currentPhotoName.innerText = data.photo_profile;
        if (currentPhotoLabel) currentPhotoLabel.classList.remove("hidden");
      } else {
        if (previewImg) previewImg.src = "https://ui-avatars.com/api/?name=" + encodeURIComponent(data.name || "") + "&background=random&bold=true";
        if (currentPhotoLabel) currentPhotoLabel.classList.add("hidden");
      }

      // Mode edit: password lama dipertahankan secara default. Tampilkan
      // toggle "Ganti Password" (nonaktif/default), sembunyikan kolom
      // password baru sampai toggle diaktifkan.
      passToggleWrap?.classList.remove("hidden");
      if (changePassCheckbox) changePassCheckbox.checked = false;
      passFieldWrap?.classList.add("hidden");
      if (passLabelText) passLabelText.innerText = "Password Baru";
      passReq?.classList.add("hidden");
      passInput.required = false;
      passInput.value = "";
      passHint.innerText = "";
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

      fetch(`${window.APP_CONFIG.BASE_URL}/admin/deleteUser?id=${deleteTargetId}`)
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
  // [BARU] Form "Tambah/Edit Pengguna" terbagi 2 tab ("Data Akun" & "Data
  // Pribadi") yang disembunyikan bergantian via class "hidden". Field
  // required di tab yang TIDAK aktif dibebaskan dari validasi HTML5 bawaan
  // (form.checkValidity() tidak akan mendeteksinya) - sehingga admin bisa
  // menekan "Simpan" setelah hanya mengisi tab "Data Akun" tanpa peringatan
  // apapun. Fungsi ini memeriksa SEMUA field wajib di KEDUA tab secara
  // manual (terlepas dari tab mana yang sedang aktif), dan mengembalikan
  // field pertama yang belum valid (atau null jika semua sudah lengkap).
  function validateUserForm() {
    const checks = [
      { id: "inputEmail",    tab: "akun",    label: "Email" },
      { id: "inputRole",     tab: "akun",    label: "Role Akun" },
      { id: "inputPosition", tab: "akun",    label: "Jabatan" },
      { id: "inputPass",     tab: "akun",    label: "Password",     checkRequired: true },
      { id: "inputName",     tab: "pribadi", label: "Nama Lengkap", checkRequired: true },
      { id: "inputClass",    tab: "pribadi", label: "Kelas",        group: ".user-field" },
    ];

    for (const c of checks) {
      const el = document.getElementById(c.id);
      if (!el) continue;

      // Lewati field yang tidak required
      // (checkRequired: true = selalu cek required attr, false = hanya jika required)
      if (!el.required && !c.checkRequired) continue;
      if (c.checkRequired && !el.required) continue;

      // Field role-conditional: lewati jika pembungkusnya disembunyikan
      if (c.group) {
        const wrap = el.closest(c.group);
        if (wrap && (wrap.classList.contains("hidden") || wrap.style.display === "none")) continue;
      }

      // Cek empty value (lebih andal daripada checkValidity saja)
      const isEmpty = el.value.trim() === '';
      const failsValidity = !el.checkValidity();

      if (isEmpty || failsValidity) {
        return { el, tab: c.tab, label: c.label };
      }
    }
    return null;
  }

  // [BARU - Fitur Crop Foto] Sama seperti common/edit_profile.js: saat
  // admin memilih file di #photoInput, tampilkan modal Cropper.js (rasio
  // 1:1, sama seperti foto profil). Hasil potong (#cropImageBtn) disimpan
  // sebagai data URL base64 ke #croppedImage (dikirim ke server) dan
  // dipakai sebagai preview (#previewImg).
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

      // Foto baru dipilih -> sembunyikan indikator "Foto sudah ada: ..."
      // karena foto akan diganti dengan hasil crop ini.
      document.getElementById("currentPhotoLabel")?.classList.add("hidden");

      closeCropperModal(cropperModal, photoInput);
    });

    closeBtn?.addEventListener("click", () => closeCropperModal(cropperModal, photoInput));
    cancelBtn?.addEventListener("click", () => closeCropperModal(cropperModal, photoInput));
  }

  function closeCropperModal(modal, input) {
    modal.classList.add("hidden");
    modal.classList.remove("flex");
    input.value = "";
    if (cropper) {
      cropper.destroy();
      cropper = null;
    }
  }

  function bindUserForm() {
    const form = document.getElementById("userForm");
    const btn = document.getElementById("btnSave");

    if (!form || !btn) return;

    form.addEventListener("submit", (e) => {
      e.preventDefault();

      // [BARU] Validasi lintas-tab - lihat catatan pada validateUserForm().
      const invalidField = validateUserForm();
      if (invalidField) {
        switchUserTab(invalidField.tab);
        setTimeout(() => {
          invalidField.el.scrollIntoView({ behavior: "smooth", block: "center" });
          invalidField.el.classList.add("ring-2", "ring-red-400");
          invalidField.el.focus();
          setTimeout(() => invalidField.el.classList.remove("ring-2", "ring-red-400"), 3000);
        }, 50);
        const tabName = invalidField.tab === "akun" ? "Data Akun" : "Data Pribadi";
        showAlert("error", "Form Belum Lengkap", `Mohon lengkapi field "${invalidField.label}" pada tab ${tabName}.`);
        return;
      }

      combinePhoneBeforeSubmit();

      const formData = new FormData(form);
      const url =
        currentMode === "add"
          ? `${window.APP_CONFIG.BASE_URL}/admin/addUser`
          : `${window.APP_CONFIG.BASE_URL}/admin/editUser`;

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
    const icon  = document.getElementById("alertIcon");
    const titleEl = document.getElementById("alertTitle");
    const msgEl   = document.getElementById("alertMsg");
    const btn     = document.getElementById("alertBtn");

    // Fallback: pakai globalAlertModal (tersedia di semua halaman via footer)
    if (!modal || !icon || !titleEl || !msgEl || !btn) {
        if (typeof showCustomAlert === 'function') showCustomAlert(type, title, msg);
        return;
    }

    titleEl.innerText = title;
    msgEl.innerText   = msg;

    if (type === "success") {
      icon.className  = "w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 bg-green-100 text-green-600";
      icon.innerHTML  = '<i class="fas fa-check text-3xl"></i>';
      btn.className   = "w-full py-3 rounded-xl font-bold text-white bg-green-600 hover:bg-green-700 shadow-lg";
      btn.onclick     = () => window.location.reload();
    } else {
      icon.className  = "w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 bg-red-100 text-red-600";
      icon.innerHTML  = '<i class="fas fa-times text-3xl"></i>';
      btn.className   = "w-full py-3 rounded-xl font-bold text-white bg-red-600 hover:bg-red-700 shadow-lg";
      btn.onclick     = () => { modal.style.display = "none"; };
    }

    // Gunakan style.display untuk menghindari konflik Tailwind hidden+flex
    modal.style.display = "flex";
  }

  // [BARU - Tahap 29] Pencarian real-time pada "Daftar User" - setiap
  // huruf yang diketik (dengan debounce) memicu fetch AJAX ke
  // AdminController::manageUsers(?ajax=1&search=...) yang me-return
  // partial tabel+pagination (app/views/admin/partials/users_table.php),
  // lalu menggantikan #usersTableContainer tanpa reload halaman.
  function bindUserSearch() {
    const input = document.getElementById("userSearchInput");
    const form = document.getElementById("userSearchForm");
    if (!input) return;

    // Fallback aman: cegah submit (Enter) melakukan reload penuh karena
    // pencarian sudah real-time lewat event "input" di bawah.
    if (form) {
      form.addEventListener("submit", (e) => e.preventDefault());
    }

    let debounceTimer = null;
    input.addEventListener("input", () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => fetchUsersTable(input.value), 350);
    });
  }

  function fetchUsersTable(keyword) {
    const icon = document.getElementById("userSearchIcon");
    const base = (window.APP_CONFIG && window.APP_CONFIG.BASE_URL) || "";

    // [FIX] Sebelumnya hanya "search" yang dikirim ke endpoint AJAX ini,
    // sehingga filter Role/Jabatan/Angkatan yang sedang aktif (dipilih lewat
    // select, yang me-reload halaman penuh) langsung HILANG begitu user
    // mengetik di kolom pencarian — tabel AJAX menampilkan hasil pencarian
    // dari SEMUA data, bukan dari data yang sudah tersaring. Sekarang nilai
    // select saat ini ikut disertakan.
    const params = new URLSearchParams();
    params.set('ajax', '1');
    params.set('search', keyword);
    const roleSel     = document.getElementById('filterRoleSelect');
    const jabatanSel  = document.getElementById('filterJabatanSelect');
    const angkatanSel = document.getElementById('filterAngkatanSelect');
    if (roleSel && roleSel.value)                        params.set('role', roleSel.value);
    if (jabatanSel && !jabatanSel.disabled && jabatanSel.value)   params.set('jabatan', jabatanSel.value);
    if (angkatanSel && !angkatanSel.disabled && angkatanSel.value) params.set('angkatan', angkatanSel.value);

    const url = base + "/admin/manageUsers?" + params.toString();

    if (icon) icon.className = "fas fa-spinner fa-spin absolute left-4 top-3.5 text-blue-100 text-xs";

    fetch(url)
      .then((res) => res.text())
      .then((html) => {
        const container = document.getElementById("usersTableContainer");
        if (container) container.outerHTML = html;

        // Sinkronkan URL browser (bookmarkable, tanpa reload) — tetap bawa
        // filter role/jabatan/angkatan yang sedang aktif.
        params.delete('ajax');
        if (!keyword) params.delete('search');
        const qs = params.toString();
        const newUrl = base + "/admin/manageUsers" + (qs ? "?" + qs : "");
        history.replaceState(null, "", newUrl);
      })
      .catch(() => {})
      .finally(() => {
        if (icon) icon.className = "fas fa-search absolute left-4 top-3.5 text-blue-100 text-xs";
      });
  }
  // [BARU] Filter "Role" pada Daftar User: Admin & Kepala Lab tidak memiliki
  // jabatan/angkatan asisten, jadi kedua filter tersebut dinonaktifkan
  // (bukan sekadar disembunyikan) begitu salah satu role tsb dipilih —
  // <select disabled> otomatis tidak ikut terkirim saat form di-submit.
  window.handleUserRoleFilterChange = function (sel) {
    const jabatanSel  = document.getElementById("filterJabatanSelect");
    const angkatanSel = document.getElementById("filterAngkatanSelect");
    const lock = (sel.value === "Admin" || sel.value === "Kepala Lab");

    [jabatanSel, angkatanSel].forEach((el) => {
      if (!el) return;
      el.disabled = lock;
      if (lock) el.value = "";
    });

    sel.form.submit();
  };

  // [DIUBAH] Toggle aktif/nonaktif akun di edit modal — sebelumnya hanya
  // mengubah hidden field `status_account` yang baru benar-benar diterapkan
  // saat form "Simpan Data" disubmit: tidak ada konfirmasi, tidak ada
  // notifikasi khusus, dan (bug utama) TIDAK memanggil endpoint
  // /admin/toggleUserStatus sama sekali — jadi arsip ZIP presensi+logbook
  // (yang harusnya terjadi saat nonaktifkan) tidak pernah dibuat lewat
  // jalur ini. Sekarang toggle memicu konfirmasi + memanggil endpoint yang
  // sama persis dengan yang dipakai tombol "Nonaktifkan Akun" di dashboard,
  // supaya perilaku & notifikasinya konsisten di kedua tempat.
  window.handleAccountStatusChange = function() {
      const toggle = document.getElementById("inputAccountActive");
      if (!toggle) return;
      const wantActive = toggle.checked;

      // Kembalikan tampilan toggle ke posisi semula dulu — baru diterapkan
      // permanen setelah admin benar-benar konfirmasi (lihat confirmStatusToggle).
      toggle.checked = !wantActive;

      if (!editingUserId) return;
      pendingStatusToggle = wantActive ? "ACTIVE" : "INACTIVE";
      openStatusToggleModal(pendingStatusToggle);
  };

  function openStatusToggleModal(status) {
      const modal = document.getElementById("statusToggleModal");
      const icon  = document.getElementById("statusToggleIcon");
      const title = document.getElementById("statusToggleTitle");
      const msg   = document.getElementById("statusToggleMsg");
      const btn   = document.getElementById("confirmStatusToggleBtn");
      if (!modal || !icon || !title || !msg || !btn) return;

      if (status === "INACTIVE") {
          icon.className = "w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 bg-gray-100 text-gray-500";
          icon.innerHTML = '<i class="fas fa-user-slash text-2xl"></i>';
          title.innerText = "Nonaktifkan Akun?";
          msg.innerText = "Data presensi & logbook asisten ini akan diarsipkan (ZIP) lalu dihapus dari sistem. Akun tidak bisa mengakses fitur apapun hingga diaktifkan kembali.";
          btn.className = "flex-1 py-2.5 rounded-xl bg-gray-700 text-white font-bold hover:bg-gray-800 shadow-lg transition";
          btn.innerText = "Ya, Nonaktifkan";
      } else {
          icon.className = "w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 bg-blue-100 text-blue-600";
          icon.innerHTML = '<i class="fas fa-user-check text-2xl"></i>';
          title.innerText = "Aktifkan Kembali Akun?";
          msg.innerText = "Akun ini akan bisa login dan mengakses semua fitur seperti biasa kembali.";
          btn.className = "flex-1 py-2.5 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg transition";
          btn.innerText = "Ya, Aktifkan";
      }
      modal.classList.remove("hidden");
  }

  window.cancelStatusToggle = function() {
      document.getElementById("statusToggleModal")?.classList.add("hidden");
      pendingStatusToggle = null;
  };

  window.confirmStatusToggle = function() {
      const modal    = document.getElementById("statusToggleModal");
      const toggle   = document.getElementById("inputAccountActive");
      const hint     = document.getElementById("accountStatusHint");
      const statusIn = document.getElementById("inputStatusAccount");
      const status   = pendingStatusToggle;

      modal?.classList.add("hidden");
      if (!status || !editingUserId) return;

      const base = ((window.APP_CONFIG && (window.APP_CONFIG.BASE_URL || window.APP_CONFIG.baseUrl)) || "").replace(/\/$/, "");

      fetch(base + "/admin/toggleUserStatus", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ user_id: editingUserId, status: status })
      })
      .then((r) => r.json())
      .then((data) => {
          if (data.status === "success") {
              if (toggle) toggle.checked = (status === "ACTIVE");
              if (statusIn) statusIn.value = status;
              if (hint) hint.innerText = (status === "ACTIVE")
                  ? "Akun aktif — user dapat login dan mengakses semua fitur."
                  : "Akun nonaktif — user dapat login namun tidak bisa mengakses fitur.";

              if (data.download_url) {
                  const f = document.createElement("iframe");
                  f.style.display = "none";
                  f.src = data.download_url;
                  document.body.appendChild(f);
                  setTimeout(() => { try { document.body.removeChild(f); } catch (e) {} }, 30000);
              }

              showAlert("success",
                  status === "ACTIVE" ? "Akun Diaktifkan" : "Akun Dinonaktifkan",
                  status === "ACTIVE"
                      ? "Akun berhasil diaktifkan kembali."
                      : "Akun dinonaktifkan & data presensi/logbook telah diarsipkan.");
          } else {
              showAlert("error", "Gagal", data.message || "Terjadi kesalahan.");
          }
      })
      .catch(() => {
          showAlert("error", "Gagal", "Koneksi ke server terputus.");
      });

      pendingStatusToggle = null;
  };
})();
