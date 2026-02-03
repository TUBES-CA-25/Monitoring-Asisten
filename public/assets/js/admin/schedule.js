(() => {
  "use strict";

  const rawEvents = window.SCHEDULE_DATA || [];
  const flashMessage = window.FLASH_MESSAGE || null;
  const baseUrl = window.BASE_URL || "";

  let calendar;
  let selectedDateStr = new Date().toISOString().split("T")[0];
  let currentFilter = "all";
  let deleteUrl = "";

  document.addEventListener("DOMContentLoaded", () => {
    initCalendar();
    initSearchFilter();
    initClock();
    bindConfirmDelete();

    if (flashMessage) {
      setTimeout(() => {
        showCustomAlert(
          flashMessage.type,
          flashMessage.title,
          flashMessage.message
        );
      }, 300);
    }
  });

  function initCalendar() {
    const calendarEl = document.getElementById("calendar");
    if (!calendarEl || typeof FullCalendar === "undefined") return;

    calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: "dayGridMonth",
      headerToolbar: { left: "title", right: "prev,today,next" },
      events: [],
      selectable: false,
      datesSet: () => renderCustomLayers(),
    });

    calendar.render();
  }

  function initSearchFilter() {
    const filterInput = document.getElementById("searchFilterInput");
    if (!filterInput) return;

    filterInput.addEventListener("keyup", function () {
      const key = this.value.toLowerCase();
      const items = document.querySelectorAll(
        "#filterListContainer .assistant-card[data-name]"
      );

      items.forEach((item) => {
        const name = item.getAttribute("data-name") || "";
        item.style.display = name.includes(key) ? "flex" : "none";
      });
    });
  }

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

  function bindConfirmDelete() {
    const btn = document.getElementById("confirmYesBtn");
    if (!btn) return;

    btn.addEventListener("click", () => {
      if (deleteUrl) window.location.href = deleteUrl;
    });
  }

  window.applyFilter = function (uid) {
    document
      .querySelectorAll(".filter-item")
      .forEach((el) => el.classList.remove("filter-active"));

    const activeEl = document.getElementById("filter-" + uid);
    if (activeEl) activeEl.classList.add("filter-active");

    document
      .querySelectorAll(".day-dots-container")
      .forEach((el) => el.classList.add("dots-hidden"));

    setTimeout(() => {
      currentFilter = uid;
      renderCustomLayers();
    }, 250);
  };

  function isEventOnDate(evt, checkDateStr) {
    const startDate = evt.start_date;
    const endDate = evt.end_date || startDate;
    const repeatModel = evt.model_perulangan || "sekali";

    if (repeatModel === "sekali") return startDate === checkDateStr;
    if (repeatModel === "rentang")
      return checkDateStr >= startDate && checkDateStr <= endDate;

    if (repeatModel === "mingguan") {
      if (checkDateStr >= startDate && checkDateStr <= endDate) {
        const d = new Date(checkDateStr + "T00:00:00").getDay();
        const dayCheck = d === 0 ? 7 : d;
        return String(dayCheck) === String(evt.day_of_week);
      }
    }
    return false;
  }

  function renderCustomLayers() {
    document
      .querySelectorAll(".day-click-overlay, .day-dots-container")
      .forEach((e) => e.remove());

    document.querySelectorAll(".fc-daygrid-day").forEach((cell) => {
      const dateStr = cell.getAttribute("data-date");
      if (!dateStr) return;

      const frame = cell.querySelector(".fc-daygrid-day-frame");
      if (!frame) return;

      // Click Layer
      const clickLayer = document.createElement("div");
      clickLayer.className = "day-click-overlay";
      clickLayer.onclick = function (e) {
        e.stopPropagation();
        selectedDateStr = dateStr;
        renderDayDetails(dateStr);
        openDayModal();
      };
      frame.appendChild(clickLayer);

      const uniqueColors = new Set();

      rawEvents.forEach((evt) => {
        const uId = String(evt.id_profil || "");
        const type = (evt.type || "asisten").toLowerCase();
        const filterId = String(currentFilter);

        let isValid = false;
        if (type === "umum") isValid = true;
        else if (filterId === "all") isValid = true;
        else if (uId === filterId) isValid = true;

        if (!isValid) return;

        if (isEventOnDate(evt, dateStr)) {
          let color = "#3b82f6";
          if (type === "piket") color = "#f97316";
          if (type === "umum") color = "#1f2937";
          if (type === "class" || type === "kuliah") color = "#10b981";
          uniqueColors.add(color);
        }
      });

      if (uniqueColors.size > 0) {
        const dotsLayer = document.createElement("div");
        dotsLayer.className = "day-dots-container dots-hidden";

        uniqueColors.forEach((color) => {
          const dot = document.createElement("div");
          dot.className = "dot-category";
          dot.style.backgroundColor = color;
          dotsLayer.appendChild(dot);
        });

        frame.appendChild(dotsLayer);
        requestAnimationFrame(() => {
          dotsLayer.classList.remove("dots-hidden");
        });
      }
    });
  }
  function renderDayDetails(dateStr) {
    const container = document.getElementById("modalListContainer");
    if (!container) return;

    const dateObj = new Date(dateStr + "T00:00:00");
    document.getElementById("modalDateTitle").innerText =
      dateObj.toLocaleDateString("id-ID", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
      });

    container.innerHTML = "";

    const visibleEvents = rawEvents.filter((evt) => {
      const uId = String(evt.id_profil || "");
      const type = (evt.type || "asisten").toLowerCase();
      const filterId = String(currentFilter);

      let isValid = false;
      if (type === "umum") isValid = true;
      else if (filterId === "all") isValid = true;
      else if (uId === filterId) isValid = true;

      return isValid && isEventOnDate(evt, dateStr);
    });

    visibleEvents.sort((a, b) =>
      (a.start_time || "00:00").localeCompare(b.start_time || "00:00")
    );

    if (visibleEvents.length === 0) {
      container.innerHTML = `
        <div class="flex flex-col items-center justify-center py-12 text-gray-400">
          <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3 text-2xl opacity-50">
            <i class="fas fa-calendar-times"></i>
          </div>
          <p class="text-sm italic">Tidak ada jadwal.</p>
        </div>`;
      return;
    }

    visibleEvents.forEach((evt) => {
      const type = (evt.type || "asisten").toLowerCase();
      const timeStr = `${(evt.start_time || "00:00").substring(
        0,
        5
      )} - ${(evt.end_time || "00:00").substring(0, 5)}`;

      let badgeClass = "bg-blue-50 text-blue-600 border-blue-100";
      let icon = "fa-user-tie";

      if (type === "piket") {
        badgeClass = "bg-orange-50 text-orange-600 border-orange-100";
        icon = "fa-broom";
      } else if (type === "umum") {
        badgeClass = "bg-gray-800 text-white border-gray-700";
        icon = "fa-building";
      } else if (type === "class" || type === "kuliah") {
        badgeClass = "bg-green-50 text-green-600 border-green-100";
        icon = "fa-graduation-cap";
      }

      const props = {
        id: evt.id,
        type: type,
        title: evt.title,
        location: evt.location || "Lab",
        userId: evt.id_profil || "",
        rawDate: evt.start_date,
        fmtStartTime: (evt.start_time || "00:00").substring(0, 5),
        fmtEndTime: (evt.end_time || "00:00").substring(0, 5),
        repeatModel: evt.model_perulangan || "sekali",
        endDateRepeat: evt.end_date,
        dosen: evt.dosen || "",
        kelas: evt.kelas || "",
      };

      const jsonStr = JSON.stringify({
        extendedProps: props,
      }).replace(/"/g, "&quot;");

      container.innerHTML += `
        <div class="bg-white p-4 border-b border-gray-100 flex items-center hover:bg-gray-50 transition">
          <div class="w-24 text-center mr-3 shrink-0 border-r border-gray-100 pr-3">
            <span class="block text-xs font-bold text-gray-800 font-mono">${timeStr}</span>
            <span class="block text-[10px] text-gray-400 uppercase">WITA</span>
          </div>
          <div class="flex-1 min-w-0">
            <span class="inline-flex items-center px-2 h-5 rounded-md border text-[10px] ${badgeClass} gap-1 mb-1">
              <i class="fas ${icon}"></i>
              <span class="uppercase tracking-wider font-bold">${type}</span>
            </span>
            <h4 class="font-bold text-gray-800 text-sm truncate">${props.title}</h4>
            <p class="text-xs text-gray-500 truncate">
              <span class="font-semibold text-gray-700">${
                evt.user_name || "Lab"
              }</span> • ${props.location}
            </p>
          </div>
          <div class="flex gap-1 pl-3 border-l border-gray-100 ml-3 shrink-0">
            <button onclick="openFormModal('edit', ${jsonStr})"
              class="w-8 h-8 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 flex items-center justify-center">
              <i class="fas fa-pen text-xs"></i>
            </button>
            <button onclick="triggerDelete('${evt.id}', '${type}')"
              class="w-8 h-8 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 flex items-center justify-center">
              <i class="fas fa-trash text-xs"></i>
            </button>
          </div>
        </div>`;
    });
  }
  window.openDayModal = function () {
    const m = document.getElementById("dayDetailModal");
    const backdrop = document.getElementById("detailBackdrop");
    const content = document.getElementById("detailContent");

    m.classList.remove("hidden");
    setTimeout(() => {
      backdrop.classList.remove("opacity-0");
      content.classList.remove("opacity-0", "scale-95");
      content.classList.add("scale-100");
    }, 10);
  };

  window.closeDayModal = function () {
    const m = document.getElementById("dayDetailModal");
    const backdrop = document.getElementById("detailBackdrop");
    const content = document.getElementById("detailContent");

    backdrop.classList.add("opacity-0");
    content.classList.add("opacity-0", "scale-95");
    content.classList.remove("scale-100");

    setTimeout(() => m.classList.add("hidden"), 300);
  };

  window.openFormModal = function (mode, eventData = null) {
    closeDayModal();

    const modal = document.getElementById("formModal");
    const backdrop = document.getElementById("formBackdrop");
    const content = document.getElementById("formContent");
    const form = document.getElementById("scheduleForm");

    modal.classList.remove("hidden");
    setTimeout(() => {
      backdrop.classList.remove("opacity-0");
      content.classList.remove("opacity-0", "scale-95");
      content.classList.add("scale-100");
    }, 10);

    form.reset();

    if (mode === "add") {
      document.getElementById("formModalTitle").innerText = "Tambah Jadwal";
      form.action = `${baseUrl}/admin/addSchedule`;
      document.getElementById("inputDate").value = selectedDateStr;

      if (currentFilter !== "all") {
        document.getElementById("inputUser").value = currentFilter;
      }

      handleTypeChange();
      handleRepeatChange();
      return;
    }
    const props = eventData.extendedProps;

    document.getElementById("formModalTitle").innerText = "Edit Jadwal";
    form.action = `${baseUrl}/admin/editSchedule`;

    document.getElementById("inputId").value = props.id;
    document.getElementById("inputType").value = props.type;
    document.getElementById("inputTitle").value = props.title;
    document.getElementById("inputDate").value =
      props.rawDate || selectedDateStr;
    document.getElementById("inputStart").value = props.fmtStartTime;
    document.getElementById("inputEnd").value = props.fmtEndTime;
    document.getElementById("inputLocation").value = props.location;

    handleTypeChange();
    if (props.type !== "umum") {
      document.getElementById("inputUser").value = props.userId;
    }

    if (document.getElementById("inputDosen")) {
      document.getElementById("inputDosen").value = props.dosen || "";
    }
    if (document.getElementById("inputKelas")) {
      document.getElementById("inputKelas").value = props.kelas || "";
    }

    document.getElementById("inputRepeatModel").value =
      props.repeatModel || "sekali";
    if (props.repeatModel !== "sekali") {
      document.getElementById("inputEndDateRepeat").value =
        props.endDateRepeat;
    }

    handleRepeatChange();
  };

  window.closeFormModal = function () {
    const modal = document.getElementById("formModal");
    const backdrop = document.getElementById("formBackdrop");
    const content = document.getElementById("formContent");

    backdrop.classList.add("opacity-0");
    content.classList.add("opacity-0", "scale-95");
    content.classList.remove("scale-100");

    setTimeout(() => modal.classList.add("hidden"), 300);
  };
  window.handleTypeChange = function () {
    const type = document.getElementById("inputType").value;
    const userContainer = document.getElementById("userSelectContainer");
    const userInput = document.getElementById("inputUser");
    const asistenFields = document.getElementById("asistenFields");

    if (type === "umum") {
      userContainer.classList.add("hidden");
      userInput.required = false;
      userInput.value = "";
      if (asistenFields) asistenFields.classList.add("hidden");
    } else if (type === "piket") {
      userContainer.classList.remove("hidden");
      userInput.required = true;
      if (asistenFields) asistenFields.classList.add("hidden");
    } else {
      userContainer.classList.remove("hidden");
      userInput.required = true;
      if (asistenFields) asistenFields.classList.remove("hidden");
    }
  };

  window.handleRepeatChange = function () {
    const model = document.getElementById("inputRepeatModel").value;
    const container = document.getElementById("endDateContainer");
    const input = document.getElementById("inputEndDateRepeat");
    const hint = document.getElementById("repeatHint");

    if (model === "sekali") {
      container.classList.add("hidden");
      input.required = false;
      hint.innerText = "Jadwal hanya pada tanggal terpilih.";
    } else {
      container.classList.remove("hidden");
      input.required = true;
      hint.innerText = "Jadwal berulang sampai batas tanggal.";
    }
  };
  window.triggerDelete = function (id, type) {
    deleteUrl = `${baseUrl}/admin/deleteSchedule?id=${id}&type=${type}`;

    const modal = document.getElementById("customConfirmModal");
    const content = document.getElementById("confirmContent");
    const backdrop = document.getElementById("confirmBackdrop");

    modal.classList.remove("hidden");
    setTimeout(() => {
      backdrop.classList.remove("opacity-0");
      content.classList.remove("scale-90", "opacity-0");
      content.classList.add("scale-100", "opacity-100");
    }, 50);
  };

  window.closeCustomConfirm = function () {
    const modal = document.getElementById("customConfirmModal");
    const content = document.getElementById("confirmContent");
    const backdrop = document.getElementById("confirmBackdrop");

    backdrop.classList.add("opacity-0");
    content.classList.remove("scale-100", "opacity-100");
    content.classList.add("scale-90", "opacity-0");

    setTimeout(() => modal.classList.add("hidden"), 300);
  };

  window.showCustomAlert = function (type, title, message) {
    const modal = document.getElementById("customAlertModal");
    const iconBg = document.getElementById("alertIconBg");
    const icon = document.getElementById("alertIcon");
    const btn = document.getElementById("alertBtn");

    document.getElementById("alertTitle").innerText = title;
    document.getElementById("alertMessage").innerText = message;

    if (type === "success") {
      iconBg.className =
        "w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-green-100 text-green-600";
      icon.className = "fas fa-check text-3xl";
      btn.className =
        "w-full py-3 rounded-xl font-bold text-white bg-green-600 hover:bg-green-700 shadow-lg shadow-green-500/30";
    } else {
      iconBg.className =
        "w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-red-100 text-red-600";
      icon.className = "fas fa-times text-3xl";
      btn.className =
        "w-full py-3 rounded-xl font-bold text-white bg-red-600 hover:bg-red-700 shadow-lg shadow-red-500/30";
    }

    modal.classList.remove("hidden");
    setTimeout(() => {
      document.getElementById("alertBackdrop").classList.remove("opacity-0");
      document
        .getElementById("alertContent")
        .classList.remove("scale-90", "opacity-0");
      document
        .getElementById("alertContent")
        .classList.add("scale-100", "opacity-100");
    }, 50);
  };

  window.closeCustomAlert = function () {
    const modal = document.getElementById("customAlertModal");
    const backdrop = document.getElementById("alertBackdrop");
    const content = document.getElementById("alertContent");

    backdrop.classList.add("opacity-0");
    content.classList.remove("scale-100", "opacity-100");
    content.classList.add("scale-90", "opacity-0");

    setTimeout(() => modal.classList.add("hidden"), 300);
  };
})();
