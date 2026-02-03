let currentUserId = null;
let currentUserName = "";
let currentResetId = null;
let currentResetType = null;

document.addEventListener("DOMContentLoaded", () => {
  initSearch();
  initFormSubmit();
  initResetConfirm();
});

function initSearch() {
  const searchInput = document.getElementById("searchAssistant");
  if (!searchInput) return;

  searchInput.addEventListener("keyup", function () {
    const key = this.value.toLowerCase();
    document.querySelectorAll(".assistant-card").forEach((card) => {
      card.style.display = card.dataset.name.includes(key)
        ? "flex"
        : "none";
    });
  });
}

function toggleTimeFields() {
  const status = document.getElementById("inputStatus").value;
  const timeFields = document.getElementById("timeFields");
  const proofOutContainer = document.getElementById(
    "proofOutContainer"
  );
  const labelProofMain = document.getElementById(
    "labelProofMain"
  );

  if (status === "Hadir") {
    timeFields.classList.remove("hidden");
    timeFields.classList.add("grid");

    proofOutContainer.classList.remove("hidden");
    labelProofMain.innerText = "Upload Bukti Datang";
  } else {
    timeFields.classList.add("hidden");
    timeFields.classList.remove("grid");

    proofOutContainer.classList.add("hidden");
    labelProofMain.innerText = "Upload Bukti Izin/Sakit";
  }
}

function loadLogs(userId, name, photo, el) {
  currentUserId = userId;
  currentUserName = name;

  document
    .querySelectorAll(".assistant-card")
    .forEach((c) => c.classList.remove("active"));
  el.classList.add("active");

  setText("headerName", name);
  document.getElementById("headerAvatar").src = photo
    ? `${window.BASE_URL}/uploads/profile/${photo}`
    : `https://ui-avatars.com/api/?name=${encodeURIComponent(
        name
      )}&background=random`;

  document.getElementById("inputUserId").value = userId;

  document.getElementById("emptyState").classList.add("hidden");

  const content = document.getElementById("logContent");
  content.classList.remove("hidden");
  setTimeout(() => content.classList.remove("opacity-0"), 50);

  const fd = new FormData();
  fd.append("user_id", userId);

  fetch(window.LOGBOOK_FETCH_URL, {
    method: "POST",
    body: fd,
  })
    .then((res) => res.json())
    .then((data) => renderTable(data));
}
function renderTable(logs) {
  const tbody = document.getElementById("logsTableBody");
  tbody.innerHTML = "";

  if (!logs || logs.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="p-8 text-center text-gray-400 italic">
          Belum ada data.
        </td>
      </tr>`;
    return;
  }

  logs.forEach((log) => {
    const dateStr = new Date(log.date).toLocaleDateString(
      "id-ID",
      { day: "numeric", month: "short", year: "numeric" }
    );

    let badgeClass = "bg-gray-100 text-gray-600";
    if (log.color === "green")
      badgeClass = "bg-green-100 text-green-600";
    else if (log.color === "yellow")
      badgeClass = "bg-yellow-100 text-yellow-600";
    else if (log.color === "red")
      badgeClass = "bg-red-100 text-red-600";

    const proofBtn = buildProofButton(log);
    const proofOutBtn = buildProofOutButton(log);
    const timeDisplay = buildTimeDisplay(log);
    const actionBtns = buildActionButtons(log);

    const row = `
      <tr class="hover:bg-gray-50 transition border-b border-gray-50">
        <td class="p-0 relative">
          <div class="absolute inset-y-0 left-0 w-1 ${
            badgeClass.split(" ")[0]
          }"></div>
        </td>
        <td class="p-5">
          <div class="font-bold text-gray-700">${dateStr}</div>
          <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded ${badgeClass}">
            ${log.status}
          </span>
        </td>
        <td class="p-5 text-center text-xs font-mono">${timeDisplay}</td>
        <td class="p-5 text-center">${proofBtn}</td>
        <td class="p-5 text-center">${proofOutBtn}</td>
        <td class="p-5 text-sm text-gray-600 line-clamp-2">
          ${log.activity || "-"}
        </td>
        <td class="p-5 text-center">${actionBtns}</td>
      </tr>
    `;

    tbody.insertAdjacentHTML("beforeend", row);
  });
}
function buildProofButton(log) {
  if (!log.proof_in)
    return `<span class="text-gray-300 text-xs">-</span>`;

  const folder =
    log.status === "Hadir" ? "attendance" : "leaves";

  return `
    <button onclick="viewEvidence('${log.status}', '${window.BASE_URL}/uploads/${folder}/${log.proof_in}')"
      class="text-blue-500 hover:bg-blue-50 p-1.5 rounded-lg border border-blue-100 bg-blue-50 text-xs font-bold">
      <i class="fas fa-image"></i> Lihat
    </button>
  `;
}

function buildProofOutButton(log) {
  if (log.status !== "Hadir")
    return `<span class="text-gray-300 text-xs">-</span>`;

  if (!log.proof_out)
    return `<span class="text-red-300 text-[10px] italic">Belum Pulang</span>`;

  return `
    <button onclick="viewEvidence('Pulang', '${window.BASE_URL}/uploads/attendance/${log.proof_out}')"
      class="text-purple-500 hover:bg-purple-50 p-1.5 rounded-lg border border-purple-100 bg-purple-50 text-xs font-bold">
      <i class="fas fa-image"></i> Lihat
    </button>
  `;
}

function buildTimeDisplay(log) {
  if (log.status !== "Hadir")
    return `<div class="text-gray-400">-</div>`;

  return `
    <div class="text-blue-600 font-bold">${log.time_in}</div>
    <div class="text-orange-500 font-bold text-[10px]">${log.time_out}</div>
  `;
}

function buildActionButtons(log) {
  return `
    <div class="flex justify-center gap-1">
      <button onclick='openEditModal(${JSON.stringify(
        log
      )}, "edit")'
        class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg">
        <i class="fas fa-pen"></i>
      </button>
      ${
        log.status !== "Alpha"
          ? `<button onclick="confirmReset('${log.id_ref}', '${log.status}')"
              class="p-2 text-red-500 hover:bg-red-50 rounded-lg">
              <i class="fas fa-trash-alt"></i>
            </button>`
          : ""
      }
    </div>
  `;
}
function openEditModal(log, mode) {
  const modal = document.getElementById("logModal");
  modal.classList.remove("hidden");

  document.getElementById("inputUserId").value =
    currentUserId;

  if (mode === "add") {
    setText("modalTitle", "Tambah Log Manual");
    document.getElementById("logForm").reset();
    document.getElementById("inputStatus").value = "Hadir";
  } else {
    setText("modalTitle", `Edit ${log.date}`);
    document.getElementById("inputDate").value = log.date;

    const status =
      ["Izin", "Sakit", "Hadir"].includes(log.status)
        ? log.status
        : "Alpha";

    document.getElementById("inputStatus").value = status;

    document.getElementById("inputIn").value =
      log.time_in !== "-" && status === "Hadir"
        ? log.time_in
        : "";

    document.getElementById("inputOut").value =
      log.time_out !== "-" && status === "Hadir"
        ? log.time_out
        : "";

    const cleanActivity = (log.activity || "")
      .replace("Tidak Hadir (Alpha)", "")
      .replace(" (Pengajuan Izin)", "");

    document.getElementById("inputActivity").value =
      cleanActivity;
  }

  toggleTimeFields();
}

function closeLogModal() {
  document.getElementById("logModal").classList.add("hidden");
}

function confirmReset(idRef, type) {
  currentResetId = idRef;
  currentResetType = type;
  document
    .getElementById("resetModal")
    .classList.remove("hidden");
}

function closeResetModal() {
  document
    .getElementById("resetModal")
    .classList.add("hidden");
}

function initFormSubmit() {
  const form = document.getElementById("logForm");
  if (!form) return;

  form.addEventListener("submit", (e) => {
    e.preventDefault();

    const fd = new FormData(form);

    fetch(window.LOGBOOK_SAVE_URL, {
      method: "POST",
      body: fd,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.status === "success") {
          closeLogModal();
          const active = document.querySelector(
            ".assistant-card.active"
          );
          if (active)
            loadLogs(
              currentUserId,
              currentUserName,
              null,
              active
            );
        } else {
          alert(data.message || "Gagal menyimpan data.");
        }
      });
  });
}

function initResetConfirm() {
  const btn = document.getElementById("confirmResetBtn");
  if (!btn) return;

  btn.addEventListener("click", () => {
    const mode = document.querySelector(
      'input[name="resetMode"]:checked'
    ).value;

    btn.innerText = "Memproses...";
    btn.disabled = true;

    const fd = new FormData();
    fd.append("id_ref", currentResetId);
    fd.append("type", currentResetType);
    fd.append("mode", mode);

    fetch(window.LOGBOOK_RESET_URL, {
      method: "POST",
      body: fd,
    })
      .then((res) => res.json())
      .then(() => {
        closeResetModal();
        btn.innerText = "Proses";
        btn.disabled = false;

        const active = document.querySelector(
          ".assistant-card.active"
        );
        if (active)
          loadLogs(
            currentUserId,
            currentUserName,
            null,
            active
          );
      });
  });
}

function viewEvidence(type, url) {
  const ext = url.split(".").pop().toLowerCase();
  const img = document.getElementById("modalImg");
  const frame = document.getElementById("modalFrame");

  document.getElementById("downloadLink").href = url;
  setText("proofTitle", `Bukti ${type}`);

  document
    .getElementById("photoModal")
    .classList.remove("hidden");

  if (ext === "pdf") {
    img.classList.add("hidden");
    frame.classList.remove("hidden");
    frame.src = url;
  } else {
    frame.classList.add("hidden");
    img.classList.remove("hidden");
    img.src = url;
  }
}

function closePhoto() {
  document
    .getElementById("photoModal")
    .classList.add("hidden");
}
function setText(id, value) {
  const el = document.getElementById(id);
  if (el) el.innerText = value;
}