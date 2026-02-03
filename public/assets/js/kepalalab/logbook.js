(() => {
    "use strict";

    const BASE_URL = window.LOGBOOK_CONFIG?.baseUrl || "";
    let currentUserId = null;

    const $ = (id) => document.getElementById(id);
    const $$ = (selector) => document.querySelectorAll(selector);

    const searchInput = $("searchAssistant");
    if (searchInput) {
        searchInput.addEventListener("keyup", function () {
            const key = this.value.toLowerCase();
            $$(".assistant-card").forEach(card => {
                const name = card.dataset.name || "";
                card.style.display = name.includes(key) ? "flex" : "none";
            });
        });
    }

    window.loadLogs = function (userId, name, photo, element) {
        if (currentUserId === userId) {
            resetView();
            return;
        }

        currentUserId = userId;

        $$(".assistant-card").forEach(c => c.classList.remove("active"));
        element.classList.add("active");

        $("emptyState").classList.add("hidden");
        const logContent = $("logContent");
        logContent.classList.remove("hidden");
        setTimeout(() => logContent.classList.remove("opacity-0"), 50);

        $("headerName").innerText = name;
        $("headerAvatar").src = photo
            ? `${BASE_URL}/uploads/profile/${photo}`
            : `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=random`;

        const fd = new FormData();
        fd.append("user_id", userId);

        fetch(`${BASE_URL}/kepalalab/getLogsByUser`, {
            method: "POST",
            body: fd
        })
            .then(res => res.json())
            .then(data => renderTable(data))
            .catch(err => {
                console.error("Error fetching logs:", err);
                renderError();
            });
    };

    function resetView() {
        currentUserId = null;

        $$(".assistant-card").forEach(c => c.classList.remove("active"));

        const emptyState = $("emptyState");
        const logContent = $("logContent");

        logContent.classList.add("opacity-0");
        setTimeout(() => {
            logContent.classList.add("hidden");
            emptyState.classList.remove("hidden");
            setTimeout(() => emptyState.classList.remove("opacity-0"), 50);
        }, 300);
    }

    function renderTable(logs) {
        const tbody = $("logsTableBody");
        tbody.innerHTML = "";

        if (!Array.isArray(logs) || logs.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="p-8 text-center text-gray-400 italic">
                        Belum ada data logbook.
                    </td>
                </tr>`;
            return;
        }

        logs.forEach(log => {
            const dateStr = new Date(log.date).toLocaleDateString("id-ID", {
                day: "numeric",
                month: "short",
                year: "numeric"
            });

            let badgeClass = "bg-gray-100 text-gray-600";
            if (log.color === "green") badgeClass = "bg-green-100 text-green-600";
            else if (log.color === "yellow") badgeClass = "bg-yellow-100 text-yellow-600";
            else if (log.color === "red") badgeClass = "bg-red-100 text-red-600";

            let proofInBtn = `<span class="text-gray-300 text-xs">-</span>`;
            if (log.proof_in) {
                const folder = log.status === "Hadir" ? "attendance" : "leaves";
                proofInBtn = `
                    <button onclick="viewEvidence('${escapeHtml(log.status)} (Datang)', 
                        '${BASE_URL}/uploads/${folder}/${log.proof_in}')"
                        class="text-blue-500 hover:bg-blue-50 p-1.5 rounded-lg">
                        <i class="fas fa-eye"></i>
                    </button>`;
            }

            let proofOutBtn = `<span class="text-gray-300 text-xs">-</span>`;
            if (log.proof_out) {
                proofOutBtn = `
                    <button onclick="viewEvidence('Pulang', 
                        '${BASE_URL}/uploads/attendance/${log.proof_out}')"
                        class="text-orange-500 hover:bg-orange-50 p-1.5 rounded-lg">
                        <i class="fas fa-eye"></i>
                    </button>`;
            }

            const row = `
                <tr class="hover:bg-gray-50 transition border-b border-gray-50">
                    <td class="p-0 relative">
                        <div class="absolute inset-y-0 left-0 w-1 ${badgeClass.replace("text", "bg").split(" ")[0]}"></div>
                    </td>
                    <td class="p-5">
                        <div class="font-bold text-gray-700">${dateStr}</div>
                        <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded ${badgeClass}">
                            ${escapeHtml(log.status)}
                        </span>
                    </td>
                    <td class="p-5 text-center text-xs font-mono">
                        <div class="text-blue-600 font-bold">IN: ${log.time_in || "-"}</div>
                        <div class="text-orange-500 font-bold">OUT: ${log.time_out || "-"}</div>
                    </td>
                    <td class="p-5 text-center">${proofInBtn}</td>
                    <td class="p-5 text-center">${proofOutBtn}</td>
                    <td class="p-5 text-sm text-gray-600 line-clamp-2">
                        ${escapeHtml(log.activity || "-")}
                    </td>
                </tr>`;
            tbody.insertAdjacentHTML("beforeend", row);
        });
    }

    function renderError() {
        const tbody = $("logsTableBody");
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="p-8 text-center text-red-400 italic">
                    Gagal memuat data logbook.
                </td>
            </tr>`;
    }

    window.viewEvidence = function (type, url) {
        $("modalImg").src = url;
        $("downloadLink").href = url;
        $("proofTitle").innerText = "Bukti " + type;
        $("photoModal").classList.remove("hidden");
    };

    window.closePhoto = function () {
        $("photoModal").classList.add("hidden");
    };

    function escapeHtml(text) {
        return String(text || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

})();
