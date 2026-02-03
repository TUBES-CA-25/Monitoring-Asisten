let calendar;
let selectedDateStr = new Date().toISOString().split('T')[0];
let currentFilter = 'all';

document.addEventListener('DOMContentLoaded', () => {
    initCalendar();
    initSearchFilter();
    initClock();
});

function initCalendar() {
    const el = document.getElementById('calendar');
    if (!el || typeof FullCalendar === 'undefined') return;

    calendar = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'title', right: 'prev,today,next' },
        events: [],
        selectable: false,
        datesSet: () => renderCustomLayers()
    });

    calendar.render();
}

function initSearchFilter() {
    const input = document.getElementById('searchFilterInput');
    if (!input) return;

    input.addEventListener('keyup', function () {
        const key = this.value.toLowerCase();
        const items = document.querySelectorAll('#filterListContainer .assistant-card[data-name]');
        let visible = 0;

        items.forEach(item => {
            const name = item.dataset.name;
            const show = name.includes(key);
            item.style.display = show ? 'flex' : 'none';
            if (show) visible++;
        });

        document.getElementById('noResultFilter')
            .classList.toggle('hidden', visible > 0);
    });
}

function applyFilter(uid) {
    document.querySelectorAll('.filter-item')
        .forEach(el => el.classList.remove('filter-active'));

    const active = document.getElementById('filter-' + uid);
    if (active) active.classList.add('filter-active');

    document.querySelectorAll('.day-dots-container')
        .forEach(el => el.classList.add('dots-hidden'));

    setTimeout(() => {
        currentFilter = uid;
        renderCustomLayers();
    }, 250);
}

function isEventOnDate(evt, checkDateStr) {
    const startDate = evt.start_date;
    const endDate = evt.end_date || startDate;
    const model = evt.model_perulangan || 'sekali';

    if (model === 'sekali') return startDate === checkDateStr;
    if (model === 'rentang') return checkDateStr >= startDate && checkDateStr <= endDate;

    if (model === 'mingguan') {
        if (checkDateStr >= startDate && checkDateStr <= endDate) {
            const d = new Date(checkDateStr + "T00:00:00").getDay();
            const dayCheck = d === 0 ? 7 : d;
            return String(dayCheck) === String(evt.day_of_week);
        }
    }
    return false;
}

function renderCustomLayers() {
    document.querySelectorAll('.day-click-overlay, .day-dots-container')
        .forEach(e => e.remove());

    document.querySelectorAll('.fc-daygrid-day').forEach(cell => {
        const dateStr = cell.dataset.date;
        if (!dateStr) return;

        const frame = cell.querySelector('.fc-daygrid-day-frame');
        if (!frame) return;

        renderClickLayer(frame, dateStr);
        renderDotsLayer(frame, dateStr);
    });
}

function renderClickLayer(frame, dateStr) {
    const layer = document.createElement('div');
    layer.className = 'day-click-overlay';
    layer.title = 'Lihat Detail';

    layer.onclick = (e) => {
        e.stopPropagation();
        selectedDateStr = dateStr;
        renderDayDetails(dateStr);
        openDayModal();
    };

    frame.appendChild(layer);
}

function renderDotsLayer(frame, dateStr) {
    const colors = new Set();

    rawEvents.forEach(evt => {
        const uid = String(evt.id_profil || '');
        const type = (evt.type || 'asisten').toLowerCase();
        const filterId = String(currentFilter);

        let visible =
            type === 'umum' ||
            filterId === 'all' ||
            uid === filterId;

        if (!visible) return;
        if (!isEventOnDate(evt, dateStr)) return;

        let color = '#3b82f6';
        if (type === 'piket') color = '#f97316';
        if (type === 'umum') color = '#1f2937';
        if (type === 'class' || type === 'kuliah') color = '#10b981';

        colors.add(color);
    });

    if (!colors.size) return;

    const layer = document.createElement('div');
    layer.className = 'day-dots-container dots-hidden';

    colors.forEach(color => {
        const dot = document.createElement('div');
        dot.className = 'dot-category';
        dot.style.backgroundColor = color;
        layer.appendChild(dot);
    });

    frame.appendChild(layer);
    requestAnimationFrame(() => layer.classList.remove('dots-hidden'));
}

function renderDayDetails(dateStr) {
    const container = document.getElementById('modalListContainer');
    const title = document.getElementById('modalDateTitle');

    const dateObj = new Date(dateStr + "T00:00:00");
    title.innerText = dateObj.toLocaleDateString('id-ID', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    });

    container.innerHTML = '';

    const visibleEvents = rawEvents
        .filter(evt => {
            const uid = String(evt.id_profil || '');
            const type = (evt.type || 'asisten').toLowerCase();
            const filterId = String(currentFilter);

            const visible =
                type === 'umum' ||
                filterId === 'all' ||
                uid === filterId;

            return visible && isEventOnDate(evt, dateStr);
        })
        .sort((a, b) => (a.start_time || '00:00').localeCompare(b.start_time || '00:00'));

    if (!visibleEvents.length) {
        container.innerHTML = `
            <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                <i class="fas fa-calendar-times text-3xl mb-3 opacity-50"></i>
                <p class="text-sm italic">Tidak ada jadwal.</p>
            </div>`;
        return;
    }

    visibleEvents.forEach(evt => {
        container.innerHTML += buildEventCard(evt);
    });
}

function buildEventCard(evt) {
    const type = (evt.type || 'asisten').toLowerCase();
    const timeStr = `${evt.start_time?.substring(0,5)} - ${evt.end_time?.substring(0,5)}`;

    let badge = 'bg-blue-50 text-blue-600 border-blue-100';
    let icon = 'fa-user-tie';

    if (type === 'piket') { badge = 'bg-orange-50 text-orange-600 border-orange-100'; icon = 'fa-broom'; }
    else if (type === 'umum') { badge = 'bg-gray-800 text-white border-gray-700'; icon = 'fa-building'; }
    else if (type === 'class' || type === 'kuliah') { badge = 'bg-green-50 text-green-600 border-green-100'; icon = 'fa-graduation-cap'; }

    return `
        <div class="bg-white p-4 border-b border-gray-100 flex items-center hover:bg-gray-50 transition">
            <div class="w-24 text-center mr-3 shrink-0 border-r border-gray-100 pr-3">
                <span class="block text-xs font-bold font-mono">${timeStr}</span>
                <span class="block text-[10px] text-gray-400 uppercase">WITA</span>
            </div>
            <div class="flex-1 min-w-0">
                <span class="inline-flex items-center gap-1 px-2 h-5 rounded-md border text-[10px] ${badge}">
                    <i class="fas ${icon}"></i>${type}
                </span>
                <h4 class="font-bold text-sm truncate mt-1">${evt.title}</h4>
                <p class="text-xs text-gray-500 truncate">${evt.user_name || 'Lab'} • ${evt.location || 'Lab'}</p>
            </div>
        </div>`;
}

function openDayModal() {
    const m = document.getElementById('dayDetailModal');
    m.classList.remove('hidden');
    setTimeout(() => {
        document.getElementById('detailBackdrop').classList.remove('opacity-0');
        document.getElementById('detailContent').classList.remove('opacity-0', 'scale-95');
        document.getElementById('detailContent').classList.add('scale-100');
    }, 10);
}

function closeDayModal() {
    const m = document.getElementById('dayDetailModal');
    document.getElementById('detailBackdrop').classList.add('opacity-0');
    document.getElementById('detailContent').classList.add('opacity-0', 'scale-95');
    document.getElementById('detailContent').classList.remove('scale-100');
    setTimeout(() => m.classList.add('hidden'), 300);
}

function initClock() {
    const update = () => {
        const now = new Date();
        document.getElementById('liveDate').innerText =
            now.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });

        document.getElementById('liveTime').innerText =
            now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false })
                .replace(/\./g, ':');
    };
    update();
    setInterval(update, 1000);
}
