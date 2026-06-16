(() => {
  "use strict";

  // Halaman tanpa card "Performa Asisten" (mis. dashboard belum dimuat) —
  // jangan lakukan apa-apa.
  const canvasEl = document.getElementById("assistantPerfChart");
  if (!canvasEl) return;

  const BASE_URL = (window.APP_CONFIG && window.APP_CONFIG.baseUrl) || "";
  const ROLE_SEGMENT = (window.APP_CONFIG && window.APP_CONFIG.roleSegment) || "";

  // Label & format per metrik
  const METRIC_META = {
    kehadiran: { label: "Jumlah Hadir", unit: "" },
    jam_masuk: { label: "Rata-rata Jam Masuk", unit: "time" },
    izin: { label: "Jumlah Izin Disetujui", unit: "" },
    logbook: { label: "Rata-rata Kata Logbook", unit: "" },
    jadwal: { label: "Jumlah Jadwal/Tugas", unit: "" },
    durasi_kerja: { label: "Rata-rata Durasi Kerja", unit: "duration" },
  };

  // Maksimum kategori yang masih nyaman ditampilkan per tipe chart
  const MAX_LINE = 20;
  const MAX_PIE = 8;

  const PIE_COLORS = [
    "#6366f1", "#10b981", "#f59e0b", "#ef4444",
    "#8b5cf6", "#ec4899", "#06b6d4", "#84cc16", "#94a3b8",
  ];

  let chartInstance = null;
  let currentType = "bar";
  let lastResult = { labels: [], data: [], metric: "kehadiran" };

  function formatMinutes(mins) {
    if (mins === null || mins === undefined || isNaN(mins)) return "-";
    const total = Math.round(mins);
    const h = Math.floor(total / 60) % 24;
    const m = total % 60;
    return String(h).padStart(2, "0") + ":" + String(m).padStart(2, "0");
  }

  // Format menit sebagai durasi "Xj Ym" (mis. 205 -> "3j 25m"), untuk metrik
  // "Durasi Kerja" — beda dari formatMinutes() yang memformat sebagai jam
  // (HH:MM) untuk "Jam Masuk".
  function formatDuration(mins) {
    if (mins === null || mins === undefined || isNaN(mins)) return "-";
    const total = Math.round(mins);
    const h = Math.floor(total / 60);
    const m = total % 60;
    if (h <= 0) return `${m}m`;
    return `${h}j ${m}m`;
  }

  function formatValue(metric, value) {
    const unit = METRIC_META[metric]?.unit;
    if (unit === "time") return formatMinutes(value) + " WITA";
    if (unit === "duration") return formatDuration(value);
    return String(value);
  }

  function setNote(text) {
    const el = document.getElementById("perfChartNote");
    if (!el) return;
    if (text) {
      el.textContent = text;
      el.classList.remove("hidden");
    } else {
      el.classList.add("hidden");
    }
  }

  function showLoading(show) {
    document.getElementById("perfChartLoading")?.classList.toggle("hidden", !show);
  }

  function showEmpty(show) {
    document.getElementById("perfChartEmpty")?.classList.toggle("hidden", !show);
    canvasEl.classList.toggle("hidden", show);
  }

  function setActiveTypeButton(type) {
    document.querySelectorAll(".perf-type-btn").forEach((btn) => {
      btn.classList.toggle("bg-white", btn.dataset.type === type);
    });

    // Pie chart selalu dibatasi MAX_PIE + "Lainnya", jadi selektor Top N
    // tidak relevan untuk tampilan ini.
    const limitEl = document.getElementById("perfLimit");
    if (limitEl) {
      limitEl.disabled = type === "pie";
      limitEl.classList.toggle("opacity-50", type === "pie");
      limitEl.classList.toggle("cursor-not-allowed", type === "pie");
    }
  }

  // Susun data sesuai batas tampilan per tipe chart (Top N / grup "Lainnya")
  function prepareSeries(labels, data, limitSel) {
    const total = labels.length;
    let lbls = labels.slice();
    let vals = data.slice();
    let note = "";

    if (currentType === "pie") {
      if (total > MAX_PIE) {
        const topLabels = lbls.slice(0, MAX_PIE);
        const topVals = vals.slice(0, MAX_PIE);
        const restSum = vals.slice(MAX_PIE).reduce((a, b) => a + b, 0);
        lbls = [...topLabels, `Lainnya (${total - MAX_PIE} asisten)`];
        vals = [...topVals, restSum];
        note = `Menampilkan ${MAX_PIE} teratas dari ${total} asisten — sisanya digabung sebagai "Lainnya".`;
      }
    } else if (currentType === "line") {
      let n = limitSel === "all" ? total : Math.min(parseInt(limitSel, 10) || total, total);
      if (n > MAX_LINE) n = MAX_LINE;
      lbls = lbls.slice(0, n);
      vals = vals.slice(0, n);
      if (n < total) {
        note = `Menampilkan ${n} dari ${total} asisten pada grafik garis. Pilih tampilan Bar untuk melihat semua.`;
      }
    } else {
      // bar
      const n = limitSel === "all" ? total : Math.min(parseInt(limitSel, 10) || total, total);
      lbls = lbls.slice(0, n);
      vals = vals.slice(0, n);
      if (n < total) {
        note = `Menampilkan Top ${n} dari ${total} asisten.`;
      }
    }

    return { lbls, vals, note };
  }

  function render() {
    const { labels, data, metric } = lastResult;

    if (!labels.length) {
      showEmpty(true);
      setNote("");
      if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
      }
      return;
    }
    showEmpty(false);

    const limitSel = document.getElementById("perfLimit")?.value || "all";
    const { lbls, vals, note } = prepareSeries(labels, data, limitSel);
    setNote(note);

    // Tinggi canvas dinamis khusus bar horizontal agar nyaman dibaca walau
    // jumlah asisten banyak (di-scroll dalam container tetap 320px).
    const innerEl = document.getElementById("perfChartInner");
    if (currentType === "bar") {
      innerEl.style.height = Math.max(320, lbls.length * 28) + "px";
    } else {
      innerEl.style.height = "100%";
    }

    if (chartInstance) chartInstance.destroy();

    const meta = METRIC_META[metric] || { label: "Nilai", unit: "" };
    const isTime = meta.unit === "time";
    const isDuration = meta.unit === "duration";
    const bg = currentType === "pie" ? PIE_COLORS : "#6366f1";
    const border = currentType === "pie" ? "#ffffff" : "#4f46e5";

    const valueAxisTicks = isTime
      ? { callback: (v) => formatMinutes(v) }
      : isDuration
      ? { callback: (v) => formatDuration(v) }
      : {};

    chartInstance = new Chart(canvasEl.getContext("2d"), {
      type: currentType,
      data: {
        labels: lbls,
        datasets: [
          {
            label: meta.label,
            data: vals,
            backgroundColor: bg,
            borderColor: border,
            borderWidth: currentType === "pie" ? 2 : 1,
            borderRadius: currentType === "bar" ? 6 : 0,
            fill: currentType === "line",
            tension: 0.35,
          },
        ],
      },
      options: {
        indexAxis: currentType === "bar" ? "y" : "x",
        responsive: true,
        maintainAspectRatio: false,
        scales:
          currentType === "pie"
            ? {}
            : currentType === "bar"
            ? {
                x: { beginAtZero: true, grid: { display: false }, ticks: valueAxisTicks },
                y: { grid: { display: false } },
              }
            : {
                y: { beginAtZero: true, grid: { display: false }, ticks: valueAxisTicks },
                x: { grid: { display: false }, ticks: { maxRotation: 60, minRotation: 30 } },
              },
        plugins: {
          legend: {
            display: currentType === "pie",
            position: "right",
            labels: { boxWidth: 10, font: { size: 10 } },
          },
          tooltip: {
            callbacks: {
              label: (ctx) => {
                const raw = ctx.parsed.x ?? ctx.parsed.y ?? ctx.parsed;
                return `${ctx.label}: ${formatValue(metric, raw)}`;
              },
            },
          },
        },
      },
    });
  }

  function fetchData() {
    const metric = document.getElementById("perfMetric")?.value || "kehadiran";

    showLoading(true);
    setNote("");

    fetch(`${BASE_URL}/${ROLE_SEGMENT}/getAssistantChartData?metric=${encodeURIComponent(metric)}`)
      .then((res) => res.json())
      .then((json) => {
        showLoading(false);
        if (json.status !== "success") {
          lastResult = { labels: [], data: [], metric };
          render();
          return;
        }
        lastResult = { labels: json.labels || [], data: json.data || [], metric };
        render();
      })
      .catch(() => {
        showLoading(false);
        lastResult = { labels: [], data: [], metric };
        render();
      });
  }

  window.updatePerfChart = function (refetch) {
    if (refetch) {
      fetchData();
    } else {
      render();
    }
  };

  window.setPerfChartType = function (type) {
    currentType = type;
    setActiveTypeButton(type);
    render();
  };

  document.addEventListener("DOMContentLoaded", () => {
    setActiveTypeButton(currentType);
    fetchData();
  });
})();
