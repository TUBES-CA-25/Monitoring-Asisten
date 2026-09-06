(() => {
  "use strict";

  const BASE_URL = (window.APP_CONFIG && window.APP_CONFIG.BASE_URL) || "";
  const rawDemographics = (window.APP_CONFIG && window.APP_CONFIG.RAW_DEMOGRAPHICS) || {};
  const rankings = (window.APP_CONFIG && window.APP_CONFIG.RANKINGS) || {};
  const isUserRole = (window.APP_CONFIG && window.APP_CONFIG.IS_USER_ROLE) || false;

  // [BARU] Plugin ornamen (menampilkan nilai langsung di atas/di dalam
  // chart) - dimuat via vendor_js/page_js sebagai window.ChartDataLabels.
  // Didaftarkan sekali di sini, dipakai oleh semua chart di halaman ini.
  if (window.Chart && window.ChartDataLabels) {
    Chart.register(window.ChartDataLabels);
  }

  let demoChart = null;
  let demoType = "bar";

  const CATEGORY_ICONS = {
    gender: { L: "fa-mars", P: "fa-venus" },
    class: {},
    interest: {},
  };
  function iconForLabel(key, label) {
    return (CATEGORY_ICONS[key] && CATEGORY_ICONS[key][label]) || "fa-tag";
  }

  document.addEventListener("DOMContentLoaded", () => {
    if (!isUserRole) {
      initAdminWidgets();
    } else {
      initUserChart();
    }

    initCarousel();
    renderRanking();
  });

  function initUserChart() {
    const stats = window.APP_CONFIG && window.APP_CONFIG.USER_STATS;
    if (!stats || !window.Chart) return;

    const canvas = document.getElementById("userProfileChart");
    if (!canvas) return;

    const ctx = canvas.getContext("2d");
    const total = (stats.hadir || 0) + (stats.izin || 0) + (stats.alpa || 0);

    new Chart(ctx, {
      type: "doughnut",
      data: {
        labels: ["Hadir", "Izin", "Alpa"],
        datasets: [
          {
            data: [stats.hadir, stats.izin, stats.alpa],
            backgroundColor: ["#22c55e", "#eab308", "#ef4444"],
            hoverBackgroundColor: ["#16a34a", "#ca8a04", "#dc2626"],
            borderWidth: 3,
            borderColor: "#fff",
            hoverOffset: 8,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "68%",
        animation: { animateScale: true, animateRotate: true },
        plugins: {
          legend: {
            position: "right",
            labels: { usePointStyle: true, pointStyle: "circle", padding: 16, font: { size: 12, weight: "600" } },
          },
          tooltip: {
            backgroundColor: "#1e293b",
            padding: 10,
            cornerRadius: 8,
            titleFont: { weight: "700" },
          },
          datalabels: total > 0 ? {
            color: "#fff",
            font: { weight: "700", size: 11 },
            formatter: (value) => (value > 0 ? Math.round((value / total) * 100) + "%" : ""),
          } : false,
        },
      },
    });
  }

  function initAdminWidgets() {
    const filter = document.getElementById("demographicFilter");
    if (filter) {
      filter.addEventListener("change", updateDemographicChart);
    }

    const chartBtns = document.querySelectorAll("[data-chart-type]");
    chartBtns.forEach((btn) => {
      btn.addEventListener("click", () => {
        setChartType(btn.dataset.chartType);
      });
    });

    if (Object.keys(rawDemographics).length > 0) {
      updateDemographicChart();
    }
  }

  window.switchAdminTab = function (tab) {
    const btnDemo = document.getElementById("tab-demo");
    const btnSch = document.getElementById("tab-schedule");
    const viewDemo = document.getElementById("view-demo");
    const viewSch = document.getElementById("view-schedule");
    const title = document.getElementById("widgetTitle");
    const icon = document.getElementById("widgetIcon");

    if (!btnDemo || !btnSch || !viewDemo || !viewSch) return;

    if (tab === "demo") {
      btnDemo.classList.add("active");
      btnDemo.classList.remove("inactive");
      btnSch.classList.add("inactive");
      btnSch.classList.remove("active");

      viewSch.classList.add("opacity-0");
      setTimeout(() => {
        viewSch.classList.add("hidden");
        viewDemo.classList.remove("hidden");
        setTimeout(() => viewDemo.classList.remove("opacity-0"), 50);
      }, 300);

      title.innerText = "Demografi";
      icon.className = "fas fa-chart-pie text-indigo-500 text-xl";
      setTimeout(() => {
        if (demoChart) demoChart.resize();
      }, 350);
    } else {
      btnSch.classList.add("active");
      btnSch.classList.remove("inactive");
      btnDemo.classList.add("inactive");
      btnDemo.classList.remove("active");

      viewDemo.classList.add("opacity-0");
      setTimeout(() => {
        viewDemo.classList.add("hidden");
        viewSch.classList.remove("hidden");
        setTimeout(() => viewSch.classList.remove("opacity-0"), 50);
      }, 300);

      title.innerText = "Jadwal Terdekat";
      icon.className = "fas fa-calendar-alt text-indigo-500 text-xl";
    }
  };

  function updateDemographicChart() {
    const canvas = document.getElementById("demographicChart");
    if (!canvas || !window.Chart) return;

    const ctx = canvas.getContext("2d");
    const key = document.getElementById("demographicFilter").value;
    const dataGroup = rawDemographics[key] || [];

    const colName =
      key === "gender"
        ? "jenis_kelamin"
        : key === "class"
        ? "kelas"
        : "peminatan";

    const labels = dataGroup.map((item) => item[colName] || "Tidak Diketahui");
    const counts = dataGroup.map((item) => item.count);
    const total = counts.reduce((a, b) => a + b, 0);

    if (demoChart) demoChart.destroy();

    const colors = ["#6366f1", "#ec4899", "#10b981", "#f59e0b", "#8b5cf6", "#06b6d4"];
    const barColors = labels.map((_, i) => colors[i % colors.length]);

    demoChart = new Chart(ctx, {
      type: demoType,
      data: {
        labels,
        datasets: [
          {
            label: "Jumlah Asisten",
            data: counts,
            backgroundColor: demoType === "line" ? "rgba(99,102,241,0.15)" : barColors,
            hoverBackgroundColor: demoType === "pie" ? barColors : "#4f46e5",
            borderColor: demoType === "line" ? "#6366f1" : (demoType === "pie" ? "#fff" : barColors),
            borderWidth: demoType === "pie" ? 3 : 2,
            pointBackgroundColor: "#6366f1",
            pointRadius: demoType === "line" ? 4 : 0,
            fill: demoType === "line",
            tension: 0.4,
            borderRadius: demoType === "bar" ? 8 : 0,
            maxBarThickness: 56,
            hoverOffset: demoType === "pie" ? 10 : 0,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 500, easing: "easeOutQuart" },
        scales: demoType === "pie" ? {} : {
          y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: "#f1f5f9" } },
          x: { grid: { display: false } },
        },
        plugins: {
          legend: {
            display: demoType === "pie",
            position: "right",
            labels: { usePointStyle: true, pointStyle: "circle", padding: 14, font: { size: 11, weight: "600" } },
          },
          tooltip: {
            backgroundColor: "#1e293b",
            padding: 10,
            cornerRadius: 8,
            titleFont: { weight: "700" },
          },
          datalabels: total > 0 ? {
            color: demoType === "pie" ? "#fff" : "#475569",
            anchor: demoType === "bar" ? "end" : "center",
            align: demoType === "bar" ? "top" : "center",
            offset: 4,
            font: { weight: "700", size: 11 },
            formatter: (value) => (value > 0 ? value : ""),
          } : false,
        },
      },
    });

    renderDemographicLegend(key, labels, counts, barColors, total);
  }

  // [BARU] Ornamen legend di bawah chart - dot warna + ikon + jumlah,
  // menggantikan ketergantungan pada legend bawaan Chart.js (yang hanya
  // muncul untuk tipe pie) sehingga jenis kategori tetap jelas di semua
  // tipe grafik (bar/line/pie).
  function renderDemographicLegend(key, labels, counts, colors, total) {
    const container = document.getElementById("demographicLegend");
    if (!container) return;

    if (!labels.length) {
      container.innerHTML = `<span class="text-xs text-gray-400 italic">Tidak ada data untuk kategori ini.</span>`;
      return;
    }

    container.innerHTML = labels.map((label, i) => {
      const pct = total > 0 ? Math.round((counts[i] / total) * 100) : 0;
      return `
        <span class="chart-legend-badge" style="color:${colors[i]};border-color:${colors[i]}33;background:${colors[i]}14">
          <i class="fas ${iconForLabel(key, label)}"></i>
          <span class="chart-legend-dot" style="background:${colors[i]}"></span>
          ${label}: ${counts[i]} <span style="opacity:.6">(${pct}%)</span>
        </span>`;
    }).join("");
  }

  window.setChartType = function (type) {
    demoType = type;
    updateDemographicChart();
  };

  window.updateDemographicChart = updateDemographicChart;

  function initCarousel() {
    const items = document.querySelectorAll(".carousel-item-3d");
    if (!items.length) return;

    let currentIndex = 0;
    const total = items.length;

    function updateClasses() {
      items.forEach((item, i) => {
        let pos = i - currentIndex;
        if (pos < 0) pos += total;

        item.className = "carousel-item-3d";
        if (pos === 0) item.classList.add("active");
        else if (pos === 1) item.classList.add("next-1");
        else if (pos === total - 1) item.classList.add("prev-1");
        else item.classList.add("hidden-item");
      });
    }

    setInterval(() => {
      currentIndex = (currentIndex + 1) % total;
      updateClasses();
    }, 3000);
  }

  // [BARU] Skor waktu (mis. "08:15:30") kadang datang dengan sisa desimal
  // detik dari SQL (mis. "08:15:30.500000") - dipotong di sini sebagai
  // lapisan aman kedua (perbaikan utama ada di UserModel::getAssistantRankings,
  // yang membulatkan AVG() sebelum SEC_TO_TIME) supaya tidak pernah tampil
  // seperti hasil perhitungan matematika mentah ke pengguna.
  function formatRankScore(score) {
    if (typeof score === "string" && /^\d{2}:\d{2}:\d{2}(\.\d+)?$/.test(score)) {
      return score.split(".")[0];
    }
    return score;
  }

  function renderRanking() {
    const filter = document.getElementById("rankingFilter");
    const container = document.getElementById("rankingList");

    if (!filter || !container) return;

    const data = rankings[filter.value];
    container.innerHTML = "";

    if (!data || !data.length) {
      container.innerHTML = `
        <div class="flex flex-col items-center justify-center py-12 text-gray-400">
          <i class="fas fa-user-slash mb-2"></i>
          <p class="text-xs italic">Tidak ada data untuk kategori ini.</p>
        </div>`;
      return;
    }

    data.forEach((item, index) => {
      let badgeColor = "bg-gray-100 text-gray-600";
      if (index === 0) badgeColor = "bg-yellow-100 text-yellow-700 border-yellow-200";
      if (index === 1) badgeColor = "bg-gray-200 text-gray-700 border-gray-300";
      if (index === 2) badgeColor = "bg-orange-100 text-orange-700 border-orange-200";

      let scoreLabel = "Skor";
      if (filter.value.includes("rajin") || filter.value.includes("jarang") || filter.value.includes("sibuk"))
        scoreLabel = "Total";
      if (filter.value.includes("cepat") || filter.value.includes("terlambat"))
        scoreLabel = "Rata-rata";
      if (filter.value.includes("logbook")) scoreLabel = "Karakter";
      if (filter.value === "online") scoreLabel = "Status";

      const photoUrl =
        item.photo_profile && item.photo_profile !== ""
          ? `${BASE_URL}/uploads/profile/${item.photo_profile}`
          : `https://ui-avatars.com/api/?name=${encodeURIComponent(item.nama)}&background=random&size=100`;

      container.innerHTML += `
        <div class="flex items-center justify-between p-4 border-b border-gray-50 hover:bg-indigo-50/30 transition">
          <div class="flex items-center gap-4">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold border ${badgeColor}">#${
        index + 1
      }</div>
            <img src="${photoUrl}" class="w-10 h-10 rounded-full object-cover border border-gray-200 shadow-sm">
            <div>
              <h5 class="font-bold text-gray-800 text-sm leading-tight">${item.nama}</h5>
              <p class="text-[10px] text-gray-500 font-bold uppercase mt-0.5">${
                item.jabatan || "Anggota"
              }</p>
            </div>
          </div>
          <div class="text-right">
            <span class="block font-extrabold text-indigo-600 text-sm">${formatRankScore(item.score)}</span>
            <span class="text-[9px] text-gray-400 uppercase font-bold tracking-wider">${scoreLabel}</span>
          </div>
        </div>`;
    });
  }

  window.renderRanking = renderRanking;

  window.showCustomAlert = function (type, title, message) {
    const modal = document.getElementById("customAlertModal");
    const content = document.getElementById("alertContent");
    const backdrop = document.getElementById("alertBackdrop");
    const iconBg = document.getElementById("alertIconBg");
    const icon = document.getElementById("alertIcon");
    const btn = document.getElementById("alertBtn");

    document.getElementById("alertTitle").innerText = title;
    document.getElementById("alertMessage").innerText = message;

    if (type === "success") {
      iconBg.className = "w-20 h-20 rounded-full flex items-center justify-center mb-4 bg-green-100 text-green-500";
      icon.className = "fas fa-check";
      btn.className =
        "w-full py-3.5 rounded-xl font-bold text-white shadow-lg bg-green-600 hover:bg-green-700 shadow-green-500/30 transition";
    } else if (type === "locked") {
      iconBg.className = "w-20 h-20 rounded-full flex items-center justify-center mb-4 bg-gray-100 text-gray-500";
      icon.className = "fas fa-lock";
      btn.className =
        "w-full py-3.5 rounded-xl font-bold text-white shadow-lg bg-gray-600 hover:bg-gray-700 shadow-gray-500/30 transition";
    } else {
      iconBg.className = "w-20 h-20 rounded-full flex items-center justify-center mb-4 bg-red-100 text-red-500";
      icon.className = "fas fa-times";
      btn.className =
        "w-full py-3.5 rounded-xl font-bold text-white shadow-lg bg-red-600 hover:bg-red-700 shadow-red-500/30 transition";
    }

    modal.classList.remove("hidden");
    setTimeout(() => {
      backdrop.classList.remove("opacity-0");
      content.classList.remove("scale-90", "opacity-0");
      content.classList.add("scale-100", "opacity-100");
    }, 10);
  };

  window.closeCustomAlert = function () {
    const modal = document.getElementById("customAlertModal");
    const content = document.getElementById("alertContent");
    const backdrop = document.getElementById("alertBackdrop");

    backdrop.classList.add("opacity-0");
    content.classList.remove("scale-100", "opacity-100");
    content.classList.add("scale-90", "opacity-0");

    setTimeout(() => modal.classList.add("hidden"), 300);
  };
})();
