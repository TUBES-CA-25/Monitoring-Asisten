let config = {};
let calendar;
let selectedDateStr = new Date().toISOString().split('T')[0];

document.addEventListener('DOMContentLoaded', () => {
    readConfig();
    initCalendar();
    renderUpcomingList();
    initFlash();
    startClock();
});

function readConfig() {
    const el = document.getElementById('schedule-config');
    if (!el) return;
    try {
        config = JSON.parse(el.textContent);
    } catch (e) {
        console.error('Schedule config invalid', e);
    }
}

function initFlash() {
    if (!config.flash) return;
    setTimeout(() => {
        showCustomAlert(
            config.flash.type,
            config.flash.title,
            config.flash.message
        );
    }, 300);
}

function initCalendar() {
    const calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'title', right: 'prev,today,next' },
        events: [],
        datesSet() {
            renderCustomLayers();
        }
    });
    calendar.render();
}

function shouldShowEvent(evt) {
    const cat = document.getElementById('filterCategory').value;
    const type = evt.type.toLowerCase();
    if (cat !== 'all' && type !== cat) return false;

    const day = document.getElementById('filterDay').value;
    if (day !== 'all' && String(evt.day_of_week) !== String(day)) return false;

    const tStart = document.getElementById('filterStart').value;
    const tEnd = document.getElementById('filterEnd').value;
    const evtStart = (evt.start_time || '00:00').substring(0, 5);
    const evtEnd = (evt.end_time || '00:00').substring(0, 5);

    if (tStart && evtEnd <= tStart) return false;
    if (tEnd && evtStart >= tEnd) return false;

    return true;
}

function resetFilters() {
    document.getElementById('filterCategory').value = 'all';
    document.getElementById('filterDay').value = 'all';
    document.getElementById('filterStart').value = '';
    document.getElementById('filterEnd').value = '';
    renderCustomLayers();
}

function isEventOnDate(evt, dateStr) {
    const startDate = evt.start_date;
    const endDate = evt.end_date || startDate;
    const repeatModel = evt.model_perulangan || 'sekali';

    if (repeatModel === 'sekali') return startDate === dateStr;
    if (repeatModel === 'rentang') return dateStr >= startDate && dateStr <= endDate;

    if (repeatModel === 'mingguan') {
        if (dateStr >= startDate && dateStr <= endDate) {
            const d = new Date(dateStr + "T00:00:00").getDay();
            const dayCheck = d === 0 ? 7 : d;
            return String(dayCheck) === String(evt.day_of_week);
        }
    }
    return false;
}

function renderCustomLayers() {
    document.querySelectorAll('.day-click-overlay').forEach(e => e.remove());
    document.querySelectorAll('.day-dots-container').forEach(e => e.remove());

    document.querySelectorAll('.fc-daygrid-day').forEach(cell => {
        const dateStr = cell.getAttribute('data-date');
        if (!dateStr) return;

        const frame = cell.querySelector('.fc-daygrid-day-frame');
        if (!frame) return;

        const clickLayer = document.createElement('div');
        clickLayer.className = 'day-click-overlay';
        clickLayer.onclick = () => {
            selectedDateStr = dateStr;
            renderDayDetails(dateStr);
            openDayModal();
        };
        frame.appendChild(clickLayer);

        const colors = new Set();

        config.rawEvents.forEach(evt => {
            if (!shouldShowEvent(evt)) return;
            if (isEventOnDate(evt, dateStr)) {
                let color = '#3b82f6';
                const type = evt.type.toLowerCase();
                if (type === 'piket') color = '#f97316';
                if (type === 'umum') color = '#1f2937';
                if (type === 'kuliah') color = '#10b981';
                colors.add(color);
            }
        });

        if (colors.size) {
            const dots = document.createElement('div');
            dots.className = 'day-dots-container';
            colors.forEach(c => {
                const dot = document.createElement('div');
                dot.className = 'dot-category';
                dot.style.backgroundColor = c;
                dots.appendChild(dot);
            });
            frame.appendChild(dots);
        }
    });
}

function renderUpcomingList() {
    const container = document.getElementById('upcomingListContainer');
    const now = new Date();
    now.setHours(0, 0, 0, 0);

    let upcoming = [];

    config.rawEvents.forEach(evt => {
        const startDate = new Date(evt.start_date + "T00:00:00");
        const endDate = evt.end_date ? new Date(evt.end_date + "T23:59:59") : null;

        let instanceDate = null;

        if (evt.model_perulangan === 'sekali') {
            if (startDate >= now) instanceDate = startDate;
        } else if (evt.model_perulangan === 'rentang') {
            if (startDate <= now && (!endDate || endDate >= now)) instanceDate = now;
            else if (startDate > now) instanceDate = startDate;
        } else if (evt.model_perulangan === 'mingguan') {
            const targetDay = parseInt(evt.day_of_week);
            const jsDay = targetDay === 7 ? 0 : targetDay;

            let check = new Date(now);
            for (let i = 0; i < 7; i++) {
                if (check.getDay() === jsDay) {
                    if (check >= startDate && (!endDate || check <= endDate)) {
                        instanceDate = new Date(check);
                    }
                    break;
                }
                check.setDate(check.getDate() + 1);
            }
        }

        if (instanceDate) {
            upcoming.push({ ...evt, displayDate: instanceDate });
        }
    });

    upcoming.sort((a, b) => {
        if (a.displayDate - b.displayDate !== 0)
            return a.displayDate - b.displayDate;
        return (a.start_time || '00:00')
            .localeCompare(b.start_time || '00:00');
    });

    upcoming = upcoming.slice(0, 10);

    if (!upcoming.length) {
        container.innerHTML =
            `<div class="text-center py-8 text-gray-400 text-xs italic">
                Tidak ada jadwal mendatang.
             </div>`;
        return;
    }

    container.innerHTML = '';
    upcoming.forEach(evt => {
        const d = evt.displayDate;
        const dayName = d.toLocaleDateString('id-ID', { weekday: 'short' });
        const dateNum = d.getDate();
        const timeFmt = (evt.start_time || '00:00').substring(0, 5);
        const type = evt.type.toLowerCase();

        let colorClass = 'bg-blue-50 text-blue-600 border-blue-200';
        if (type === 'piket') colorClass = 'bg-orange-50 text-orange-600 border-orange-200';
        if (type === 'umum') colorClass = 'bg-gray-50 text-gray-600 border-gray-200';
        if (type === 'kuliah') colorClass = 'bg-green-50 text-green-600 border-green-200';

        container.innerHTML += `
            <div class="upcoming-card bg-white p-3 rounded-2xl border flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl ${colorClass} flex flex-col items-center justify-center shrink-0">
                    <span class="text-[9px] font-bold uppercase">${dayName}</span>
                    <span class="text-xs font-bold">${dateNum}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <h4 class="font-bold text-gray-800 text-xs truncate">${evt.title}</h4>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-[10px] text-gray-500 font-mono">
                            <i class="far fa-clock mr-1"></i>${timeFmt}
                        </span>
                        <span class="text-[9px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 capitalize">
                            ${type}
                        </span>
                    </div>
                </div>
            </div>
        `;
    });
}

function openFormModal(mode, eventData = null) {
    closeDayModal();
    const modal = document.getElementById('formModal');
    modal.classList.remove('hidden');

    setTimeout(() => {
        document.getElementById('formBackdrop').classList.remove('opacity-0');
        const c = document.getElementById('formContent');
        c.classList.remove('opacity-0', 'scale-95');
        c.classList.add('scale-100');
    }, 10);

    const form = document.getElementById('scheduleForm');
    form.reset();

    if (mode === 'add') {
        document.getElementById('formModalTitle').innerText = "Tambah Jadwal Kuliah";
        form.action = config.addUrl;
        document.getElementById('inputDate').value = selectedDateStr;
        handleRepeatChange();
    } else {
        const p = eventData.extendedProps;
        document.getElementById('formModalTitle').innerText = "Edit Jadwal Kuliah";
        form.action = config.editUrl;

        document.getElementById('inputId').value = p.id;
        document.getElementById('inputTitle').value = p.title;
        document.getElementById('inputKelas').value = p.kelas || '';
        document.getElementById('inputLocation').value = p.location;
        document.getElementById('inputDosen').value = p.dosen || '';
        document.getElementById('inputDate').value = p.rawDate;
        document.getElementById('inputStart').value = p.fmtStartTime;
        document.getElementById('inputEnd').value = p.fmtEndTime;

        document.getElementById('inputRepeatModel').value = p.repeatModel || 'sekali';
        if (p.repeatModel !== 'sekali')
            document.getElementById('inputEndDateRepeat').value = p.endDateRepeat;

        handleRepeatChange();
    }
}

function triggerDelete(id, type) {
    config._deleteUrl = `${config.deleteBase}?id=${id}&type=${type}`;
    const modal = document.getElementById('customConfirmModal');
    modal.classList.remove('hidden');

    setTimeout(() => {
        document.getElementById('confirmBackdrop').classList.remove('opacity-0');
        const c = document.getElementById('confirmContent');
        c.classList.remove('scale-90', 'opacity-0');
        c.classList.add('scale-100');
    }, 10);
}

document.getElementById('confirmYesBtn').addEventListener('click', () => {
    if (config._deleteUrl) window.location.href = config._deleteUrl;
});

function startClock() {
    function updateClock() {
        const now = new Date();
        document.getElementById('liveDate').innerText =
            now.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            });

        document.getElementById('liveTime').innerText =
            now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            }).replace(/\./g, ':');
    }
    setInterval(updateClock, 1000);
    updateClock();
}

window.resetFilters = resetFilters;
window.openFormModal = openFormModal;
window.triggerDelete = triggerDelete;
window.handleRepeatChange = handleRepeatChange;
