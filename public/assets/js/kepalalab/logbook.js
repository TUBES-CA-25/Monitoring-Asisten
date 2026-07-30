(() => {
    "use strict";

    const BASE_URL = window.APP_CONFIG?.BASE_URL || "";
    let currentUserId = null;
    let currentUserName = "";
    let currentUserPhoto = "";
    let currentLogPage = 1;
    let currentPerPage = 30;

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
        currentUserName = name;
        currentUserPhoto = photo;
        currentLogPage = 1;

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

        fetchLogs();
    };

    window.loadUserLogsPage = function (page) {
        currentLogPage = page;
        fetchLogs();
    };

    function fetchLogs() {
        const fd = new FormData();
        fd.append("user_id", currentUserId);
        fd.append("page", currentLogPage);
        fd.append("per_page", currentPerPage);

        fetch(`${BASE_URL}/kepalalab/getLogsByUser`, {
            method: "POST",
            body: fd
        })
            .then(res => res.json())
            .then(resp => {
                // resp = { logs, total, hadir, izin, alpha, page, per_page, total_pages }
                updateLiveStats(resp.hadir || 0, resp.izin || 0, resp.alpha || 0);
                renderTable(resp.logs || []);
                renderLogbookPagination(resp.total_pages || 1, resp.page || 1, resp.total || 0);
            })
            .catch(err => {
                console.error("Error fetching logs:", err);
                renderError();
            });
    }

    function updateLiveStats(hadir, izin, alpha) {
        const bar = $("liveStatsBar");
        if (!bar) return;
        bar.classList.remove("hidden");
        const ch = $("countHadir"), ci = $("countIzin"), ca = $("countAlpha");
        if (ch) ch.innerText = hadir;
        if (ci) ci.innerText = izin;
        if (ca) ca.innerText = alpha;
    }

    function renderLogbookPagination(totalPages, currentPage, totalItems) {
        const container = $("logPaginationBar");
        if (!container) return;
        if (totalPages <= 1) { container.innerHTML = ""; container.classList.add("hidden"); return; }

        container.classList.remove("hidden");
        let html = `<div class="flex items-center gap-2 flex-wrap justify-center py-2 text-xs">`;
        html += `<span class="text-gray-400">${totalItems} entri</span>`;
        html += `<div class="flex gap-1">`;

        if (currentPage > 1)
            html += `<button onclick="loadUserLogsPage(${currentPage-1})" class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 font-bold text-gray-600"><i class="fas fa-chevron-left text-[10px]"></i></button>`;

        const maxButtons = 7;
        let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
        let endPage = Math.min(totalPages, startPage + maxButtons - 1);
        if (endPage - startPage < maxButtons - 1) startPage = Math.max(1, endPage - maxButtons + 1);

        if (startPage > 1) html += `<button onclick="loadUserLogsPage(1)" class="px-3 py-1.5 rounded-lg bg-gray-100 font-bold text-gray-600">1</button><span class="px-1 text-gray-400">…</span>`;
        for (let p = startPage; p <= endPage; p++) {
            const active = p === currentPage ? "bg-blue-600 text-white shadow" : "bg-gray-100 hover:bg-gray-200 text-gray-600";
            html += `<button onclick="loadUserLogsPage(${p})" class="px-3 py-1.5 rounded-lg ${active} font-bold">${p}</button>`;
        }
        if (endPage < totalPages) html += `<span class="px-1 text-gray-400">…</span><button onclick="loadUserLogsPage(${totalPages})" class="px-3 py-1.5 rounded-lg bg-gray-100 font-bold text-gray-600">${totalPages}</button>`;

        if (currentPage < totalPages)
            html += `<button onclick="loadUserLogsPage(${currentPage+1})" class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 font-bold text-gray-600"><i class="fas fa-chevron-right text-[10px]"></i></button>`;

        html += `</div></div>`;
        container.innerHTML = html;
    }

    function resetView() {
        currentUserId = null;
        currentLogPage = 1;

        $$(".assistant-card").forEach(c => c.classList.remove("active"));

        const emptyState = $("emptyState");
        const logContent = $("logContent");
        const statsBar = $("liveStatsBar");
        const pagination = $("logPaginationBar");
        if (statsBar) statsBar.classList.add("hidden");
        if (pagination) { pagination.innerHTML = ""; pagination.classList.add("hidden"); }

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
                        <div class="text-blue-600 font-bold">IN: ${log.time_in || "-"}${
                            (log.time_in && log.time_in !== "-")
                                ? (log.late_minutes > 0
                                    ? `<i class="fas fa-clock text-red-500 text-[9px] ml-1" title="Terlambat ${log.late_minutes} menit"></i>`
                                    : `<i class="fas fa-check-circle text-green-500 text-[9px] ml-1" title="Tepat Waktu"></i>`)
                                : ""
                        }</div>
                        <div class="text-orange-500 font-bold">OUT: ${log.time_out || "-"}${
                            (log.time_out && log.time_out !== "-" && log.is_early_checkout)
                                ? `<i class="fas fa-door-open text-yellow-500 text-[9px] ml-1" title="Pulang Lebih Cepat"></i>`
                                : ""
                        }</div>
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

    function updateClock() {
        const now = new Date();
        const dateOptions = { day: "2-digit", month: "long", year: "numeric" };
        const timeOptions = { hour: "2-digit", minute: "2-digit", second: "2-digit", hour12: false };

        const elDate = $("liveDate");
        const elTime = $("liveTime");

        if (elDate) elDate.innerText = now.toLocaleDateString("id-ID", dateOptions);
        if (elTime) elTime.innerText = now.toLocaleTimeString("id-ID", timeOptions).replace(/\./g, ":");
    }

    updateClock();
    setInterval(updateClock, 1000);

})();
