// [BARU] Halaman ini dimuat ulang lewat navigasi AJAX (SPA-like) di global.js,
// yang mempertahankan JS realm yang sama antar halaman. Deklarasi top-level
// dengan const/let TIDAK BISA dieksekusi dua kali dalam realm yang sama
// (SyntaxError: Identifier '...' has already been declared) - begitu pengguna
// meninggalkan halaman Jadwal lalu kembali lagi, seluruh script ini gagal
// jalan dari baris pertama, sehingga kalender & titik warna penanda jenis
// jadwal "hilang" tanpa pesan apapun. var aman didekralasikan ulang.
var BASE_URL = window.APP_CONFIG.baseUrl;
    // --- 1. DATA SETUP ---
    var rawEvents = window.APP_CONFIG.rawEvents || [];

    var calendar;
    var selectedDateStr = new Date().toISOString().split('T')[0];
    var currentFilter = 'all';

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        
        var isMobile = window.innerWidth < 768;

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            initialDate: window.APP_CONFIG.initialDate,
            themeSystem: 'standard',
            
            headerToolbar: isMobile 
                ? { left: 'prev,next', center: 'title', right: 'today' }
                : { left: 'title', right: 'prev,today,next' },

            height: isMobile ? 'auto' : '100%', 
            
            events: [], 
            selectable: false, 
            selectMirror: true,
            
            windowResize: function(view) {
                if (window.innerWidth < 768) {
                    calendar.setOption('headerToolbar', { left: 'prev,next', center: 'title', right: 'today' });
                    calendar.setOption('height', 'auto');
                } else {
                    calendar.setOption('headerToolbar', { left: 'title', right: 'prev,today,next' });
                    calendar.setOption('height', '100%');
                }
            },

            datesSet: function() { _iclabsFadeCalendar(calendarEl); renderCustomLayers(); }
        });

        calendar.render();

        const filterInput = document.getElementById('searchFilterInput');
        if (filterInput) {
            filterInput.addEventListener('keyup', function() {
                const key = this.value.toLowerCase();
                const items = document.querySelectorAll('#filterListContainer .assistant-card[data-name]');
                let visibleCount = 0;
                items.forEach(item => {
                    const name = item.getAttribute('data-name');
                    if (name.includes(key)) { item.style.display = 'flex'; visibleCount++; } else { item.style.display = 'none'; }
                });
                document.getElementById('noResultFilter').classList.toggle('hidden', visibleCount > 0);
            });
        }
    });

    // [BARU] Transisi fade halus setiap kali tampilan kalender berganti
    // (bulan baru / filter baru) - murni kosmetik, tidak menyentuh render FullCalendar.
    function _iclabsFadeCalendar(calendarEl) {
        var harness = calendarEl.querySelector('.fc-view-harness');
        if (!harness) return;
        harness.classList.add('fc-fade-init');
        requestAnimationFrame(function () {
            requestAnimationFrame(function () { harness.classList.remove('fc-fade-init'); });
        });
    }

    // --- 2. LOGIC TANGGAL (SAMA PERSIS DENGAN ADMIN) ---
    function isEventOnDate(evt, checkDateStr) {
        const startDate = evt.start_date;
        const endDate = evt.end_date || startDate;
        const repeatModel = evt.model_perulangan || 'sekali';

        if (repeatModel === 'sekali') return startDate === checkDateStr;
        if (repeatModel === 'rentang') return checkDateStr >= startDate && checkDateStr <= endDate;
        if (repeatModel === 'mingguan') {
            if (checkDateStr >= startDate && checkDateStr <= endDate) {
                const d = new Date(checkDateStr + "T00:00:00").getDay(); 
                const dayCheck = d === 0 ? 7 : d; 
                return String(dayCheck) === String(evt.day_of_week);
            }
        }
        return false;
    }

    // --- 3. FILTER & TRANSISI SOFT (AJAX) ---
    function applyFilter(uid) { 
        // 1. Update UI Sidebar
        document.querySelectorAll('.filter-item').forEach(el => el.classList.remove('filter-active')); 
        const activeEl = document.getElementById('filter-' + uid); 
        if(activeEl) activeEl.classList.add('filter-active'); 
        
        // Sync Dropdown
        const selectEl = document.querySelector('select[name="assistant_id_select"]');
        if (selectEl) selectEl.value = uid;
        
        currentFilter = uid;
        fetchFilteredSchedules();
    }

    function applyFilterFromDropdown(uid) {
        // Sync Sidebar UI
        document.querySelectorAll('.filter-item').forEach(el => el.classList.remove('filter-active')); 
        const activeEl = document.getElementById('filter-' + uid); 
        if(activeEl) activeEl.classList.add('filter-active'); 
        
        currentFilter = uid;
        fetchFilteredSchedules();
    }

    function handleStartDateChange(val) {
        const endInput = document.querySelector('input[name="end_date"]');
        if (endInput) {
            endInput.min = val;
            if (endInput.value && endInput.value < val) {
                endInput.value = val;
            }
        }
        fetchFilteredSchedules();
    }

    function handleEndDateChange(val) {
        const startInput = document.querySelector('input[name="start_date"]');
        if (startInput) {
            startInput.max = val;
            if (startInput.value && startInput.value > val) {
                startInput.value = val;
            }
        }
        fetchFilteredSchedules();
    }

    function fetchFilteredSchedules() {
        const startDate = document.querySelector('input[name="start_date"]').value;
        const endDate = document.querySelector('input[name="end_date"]').value;
        const scheduleType = document.querySelector('[name="schedule_type"]').value;
        const sortBy = document.querySelector('[name="sort_by"]').value;
        const assistantId = currentFilter;

        const roleLink = 'kepalalab';
        const url = `${BASE_URL}/${roleLink}/getFilteredSchedulesJson?start_date=${startDate}&end_date=${endDate}&assistant_id=${assistantId}&schedule_type=${scheduleType}&sort_by=${sortBy}`;

        // Update download buttons href dynamically
        const exportPdfBtn = document.getElementById('exportPdfBtn');
        const exportExcelBtn = document.getElementById('exportExcelBtn');
        const qs = `start_date=${startDate}&end_date=${endDate}&assistant_id=${assistantId}&schedule_type=${scheduleType}&sort_by=${sortBy}`;
        if (exportPdfBtn) exportPdfBtn.href = `${BASE_URL}/${roleLink}/exportSchedulePdf?${qs}`;
        if (exportExcelBtn) exportExcelBtn.href = `${BASE_URL}/${roleLink}/exportScheduleCsv?${qs}`;

        // Soft transition dots container
        const dotsContainers = document.querySelectorAll('.day-dots-container');
        dotsContainers.forEach(el => el.classList.add('dots-hidden'));

        fetch(url)
            .then(res => res.json())
            .then(data => {
                rawEvents.length = 0;
                data.forEach(evt => rawEvents.push(evt));
                renderCustomLayers();
            })
            .catch(err => console.error("Error fetching schedules: ", err));
    }

    // --- 4. RENDER LAYER (DOTS & KLIK) ---
    function renderCustomLayers() {
        // Bersihkan layer lama
        document.querySelectorAll('.day-click-overlay, .day-dots-container').forEach(e => e.remove());
        
        document.querySelectorAll('.fc-daygrid-day').forEach(cell => {
            const dateStr = cell.getAttribute('data-date'); 
            if(!dateStr) return;
            
            const frame = cell.querySelector('.fc-daygrid-day-frame');
            if(!frame) return;

            // A. LAYER KLIK (Buka Modal Read Only)
            const clickLayer = document.createElement('div');
            clickLayer.className = 'day-click-overlay'; 
            clickLayer.title = "Lihat Detail";
            clickLayer.onclick = function(e) {
                e.stopPropagation(); 
                selectedDateStr = dateStr;
                renderDayDetails(dateStr);
                openDayModal();
            };
            frame.appendChild(clickLayer);

            // B. LAYER DOTS
            let uniqueColors = new Set();
            rawEvents.forEach(evt => {
                const uId = String(evt.id_profil || ''); 
                const type = (evt.type || 'asisten').toLowerCase();
                const filterId = String(currentFilter);
                
                // [LOGIKA STRICT FILTER]
                let isVisible = false;
                if (type === 'umum') {
                    isVisible = true; // Umum selalu muncul
                } else if (filterId === 'all') {
                    isVisible = true; // Mode All: semua muncul
                } else if (uId === filterId) {
                    isVisible = true; // Mode User: hanya milik user tsb
                }

                if (!isVisible) return;

                if (isEventOnDate(evt, dateStr)) {
                    let color = '#3b82f6'; 
                    if(type === 'piket') color = '#f97316';
                    if(type === 'umum') color = '#1f2937';
                    if(type === 'class' || type === 'kuliah') color = '#10b981';
                    uniqueColors.add(color);
                }
            });

            if (uniqueColors.size > 0) {
                const dotsLayer = document.createElement('div');
                dotsLayer.className = 'day-dots-container dots-hidden'; // Mulai dengan hidden (untuk animasi)
                
                uniqueColors.forEach(color => {
                    const dot = document.createElement('div');
                    dot.className = 'dot-category';
                    dot.style.backgroundColor = color;
                    dotsLayer.appendChild(dot);
                });
                frame.appendChild(dotsLayer);

                // Trigger Fade In (Animation)
                requestAnimationFrame(() => {
                    dotsLayer.classList.remove('dots-hidden');
                });
            }
        });
    }

    // --- 5. RENDER MODAL (READ ONLY) ---
    function renderDayDetails(dateStr) {
        const container = document.getElementById('modalListContainer');
        const dateObjForTitle = new Date(dateStr + "T00:00:00");
        document.getElementById('modalDateTitle').innerText = dateObjForTitle.toLocaleDateString('id-ID', { 
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' 
        });
        
        container.innerHTML = '';

        const visibleEvents = rawEvents.filter(evt => {
            const uId = String(evt.id_profil || ''); 
            const type = (evt.type || 'asisten').toLowerCase();
            const filterId = String(currentFilter);
            
            // Logika Filter Modal sama dengan Dots
            let isVisible = false;
            if (type === 'umum') isVisible = true; 
            else if (filterId === 'all') isVisible = true;
            else if (uId === filterId) isVisible = true;
            
            if (!isVisible) return false;
            return isEventOnDate(evt, dateStr);
        });

        visibleEvents.sort((a, b) => (a.start_time || '00:00').localeCompare(b.start_time || '00:00'));

        if (visibleEvents.length === 0) {
            container.innerHTML = `<div class="flex flex-col items-center justify-center py-12 text-gray-400"><div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3 text-2xl opacity-50"><i class="fas fa-calendar-times"></i></div><p class="text-sm italic">Tidak ada jadwal.</p></div>`;
            return;
        }

        visibleEvents.forEach(evt => {
            const type = (evt.type || 'asisten').toLowerCase();
            const timeStr = `${(evt.start_time || '00:00').substring(0,5)} - ${(evt.end_time || '00:00').substring(0,5)}`;
            
            let badgeClass = 'bg-blue-50 text-blue-600 border-blue-100';
            let icon = 'fa-user-tie';
            if(type === 'piket') { badgeClass = 'bg-orange-50 text-orange-600 border-orange-100'; icon = 'fa-broom'; }
            else if(type === 'umum') { badgeClass = 'bg-gray-800 text-white border-gray-700'; icon = 'fa-building'; }
            else if(type === 'class' || type === 'kuliah') { badgeClass = 'bg-green-50 text-green-600 border-green-100'; icon = 'fa-graduation-cap'; }

            // [Kepala Lab: TOMBOL ACTIONS (EDIT/HAPUS) DIHILANGKAN]
            
            let extraInfo = '';
            if (evt.dosen || evt.kelas) {
                extraInfo = `<div class="mt-1 flex gap-2 text-[10px] text-gray-500">
                    ${evt.dosen ? `<span class="bg-gray-100 px-1.5 rounded"><i class="fas fa-user-tie mr-1"></i>${evt.dosen}</span>` : ''}
                    ${evt.kelas ? `<span class="bg-gray-100 px-1.5 rounded"><i class="fas fa-chalkboard mr-1"></i>Kelas ${evt.kelas}</span>` : ''}
                </div>`;
            }

            container.innerHTML += `
                <div class="bg-white p-4 border-b border-gray-100 flex items-center hover:bg-gray-50 transition group">
                    <div class="w-24 text-center mr-3 shrink-0 border-r border-gray-100 pr-3">
                        <span class="block text-xs font-bold text-gray-800 font-mono">${timeStr}</span>
                        <span class="block text-[10px] text-gray-400 font-medium uppercase tracking-wide">WITA</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-auto px-2 h-5 rounded-md flex items-center justify-center border text-[10px] ${badgeClass} gap-1">
                                <i class="fas ${icon}"></i> <span class="uppercase tracking-wider font-bold">${type}</span>
                            </span>
                        </div>
                        <h4 class="font-bold text-gray-800 text-sm truncate">${evt.title}</h4>
                        <p class="text-xs text-gray-500 truncate mt-0.5"><span class="font-semibold text-gray-700">${evt.user_name || 'Lab'}</span> • ${evt.location || 'Lab'}</p>
                        ${extraInfo}
                    </div>
                </div>`;
        });
    }

    // --- 6. UTILS ---
    function openDayModal() { const m = document.getElementById('dayDetailModal'); m.classList.remove('hidden'); setTimeout(() => { document.getElementById('detailBackdrop').classList.remove('opacity-0'); document.getElementById('detailContent').classList.remove('opacity-0', 'scale-95'); document.getElementById('detailContent').classList.add('scale-100'); }, 10); }
    function closeDayModal() { const m = document.getElementById('dayDetailModal'); document.getElementById('detailBackdrop').classList.add('opacity-0'); document.getElementById('detailContent').classList.add('opacity-0', 'scale-95'); document.getElementById('detailContent').classList.remove('scale-100'); setTimeout(() => { m.classList.add('hidden'); }, 300); }
    function updateClock() { const now = new Date(); document.getElementById('liveDate').innerText = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }); document.getElementById('liveTime').innerText = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).replace(/\./g, ':'); } setInterval(updateClock, 1000); updateClock();
