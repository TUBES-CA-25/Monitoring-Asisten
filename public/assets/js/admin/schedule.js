const BASE_URL = window.APP_CONFIG.baseUrl;
    const rawEvents = window.APP_CONFIG.rawEvents || [];
    
    let calendar;
    let selectedDateStr = new Date().toISOString().split('T')[0];
    let currentFilter = 'all';

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');

        // [BARU] #formContent memakai class "overflow-hidden" dan
        // #scheduleForm memakai "overflow-y-auto" - kombinasi ini membuat
        // bubble validasi bawaan browser (mis. saat <select required> belum
        // dipilih) menjadi TIDAK TERLIHAT (clipped), sehingga form gagal
        // submit TANPA ada tampilan error apapun ke pengguna ("modal diam
        // saja"). Tangani validasi secara manual di sini: jika form tidak
        // valid, batalkan submit, scroll+highlight field yang bermasalah,
        // dan tampilkan modal pemberitahuan agar pengguna tahu apa yang
        // perlu dilengkapi.
        const scheduleFormEl = document.getElementById('scheduleForm');
        if (scheduleFormEl) {
            scheduleFormEl.addEventListener('submit', function(e) {
                if (!this.checkValidity()) {
                    e.preventDefault();
                    const invalid = this.querySelector(':invalid');
                    if (invalid) {
                        invalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        invalid.classList.add('ring-2', 'ring-red-400');
                        invalid.focus();
                        setTimeout(() => invalid.classList.remove('ring-2', 'ring-red-400'), 3000);
                    }
                    showCustomAlert('error', 'Form Belum Lengkap', 'Mohon lengkapi semua field yang wajib diisi (ditandai dengan garis merah), termasuk Lokasi/Laboratorium.');
                }
            });
        }

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

            datesSet: function() { renderCustomLayers(); }
        });
        
        calendar.render();

        const filterInput = document.getElementById('searchFilterInput');
        if (filterInput) {
            filterInput.addEventListener('keyup', function() {
                const key = this.value.toLowerCase();
                const items = document.querySelectorAll('#filterListContainer .assistant-card[data-name]');
                items.forEach(item => {
                    const name = item.getAttribute('data-name');
                    item.style.display = name.includes(key) ? 'flex' : 'none';
                });
            });
        }
    });

    // --- 2. LOGIC TANGGAL ---
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

    // --- 3. FILTER & TRANSISI (AJAX) ---
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

        const roleLink = 'admin';
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

    // --- 4. RENDER LAYERS (PERBAIKAN LOGIC WARNA) ---
    function renderCustomLayers() {
        // Hapus layer lama (kecuali jika ada mekanisme update parsial, tapi di FullCalendar redraw lebih aman)
        document.querySelectorAll('.day-click-overlay, .day-dots-container').forEach(e => e.remove());
        
        document.querySelectorAll('.fc-daygrid-day').forEach(cell => {
            const dateStr = cell.getAttribute('data-date'); if(!dateStr) return;
            const frame = cell.querySelector('.fc-daygrid-day-frame'); if(!frame) return;

            // Layer Klik
            const clickLayer = document.createElement('div');
            clickLayer.className = 'day-click-overlay';
            clickLayer.onclick = function(e) { e.stopPropagation(); selectedDateStr = dateStr; renderDayDetails(dateStr); openDayModal(); };
            frame.appendChild(clickLayer);

            // Layer Dots
            let uniqueColors = new Set();
            
            rawEvents.forEach(evt => {
                const uId = String(evt.id_profil || '');
                const type = (evt.type || 'asisten').toLowerCase();
                const filterId = String(currentFilter);

                // [LOGIKA STRICT FILTER]
                // - Jika filter = 'all': Tampilkan semua
                // - Jika filter = ID User: Tampilkan JIKA (milik user itu) ATAU (tipe umum)
                let isValid = false;
                if (type === 'umum') {
                    isValid = true; // Umum selalu muncul
                } else if (filterId === 'all') {
                    isValid = true; // Mode All: semua muncul
                } else if (uId === filterId) {
                    isValid = true; // Mode User: hanya milik user tsb
                }

                if (!isValid) return; // Skip jika tidak valid

                if (isEventOnDate(evt, dateStr)) {
                    let color = '#3b82f6'; // Default (Asisten)
                    if(type === 'piket') color = '#f97316';
                    if(type === 'umum') color = '#1f2937';
                    if(type === 'class' || type === 'kuliah') color = '#10b981';
                    uniqueColors.add(color);
                }
            });

            if (uniqueColors.size > 0) {
                const dotsLayer = document.createElement('div');
                dotsLayer.className = 'day-dots-container dots-hidden'; // Mulai dengan hidden (opacity 0)
                
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

    // --- 5. RENDER MODAL DETAIL (PERBAIKAN LOGIC) ---
    function renderDayDetails(dateStr) {
        const container = document.getElementById('modalListContainer');
        const dateObjForTitle = new Date(dateStr + "T00:00:00");
        document.getElementById('modalDateTitle').innerText = dateObjForTitle.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        container.innerHTML = '';

        // Filter Event untuk Modal (Sama dengan Logic Dots)
        const visibleEvents = rawEvents.filter(evt => {
            const uId = String(evt.id_profil || '');
            const type = (evt.type || 'asisten').toLowerCase();
            const filterId = String(currentFilter);

            let isValid = false;
            if (type === 'umum') isValid = true;
            else if (filterId === 'all') isValid = true;
            else if (uId === filterId) isValid = true;

            if (!isValid) return false;
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

            const props = {
                id: evt.id, type: type, title: evt.title, location: evt.location || 'Lab',
                userId: evt.id_profil || '',
                rawDate: evt.start_date, fmtStartTime: (evt.start_time || '00:00').substring(0,5),
                fmtEndTime: (evt.end_time || '00:00').substring(0,5),
                repeatModel: evt.model_perulangan || 'sekali', endDateRepeat: evt.end_date,
                dosen: evt.dosen || '', kelas: evt.kelas || '', idDosen: evt.id_dosen || '',
                // [BARU - Modul 5 V3] status sinkronisasi Google Calendar
                syncStatus: evt.sync_status || 'skipped'
            };
            const jsonStr = JSON.stringify({ extendedProps: props }).replace(/"/g, '&quot;');

            let actions = '';
            // [DIUBAH] Jadwal Kuliah adalah wewenang penuh Asisten - Admin
            // hanya bisa melihat (tidak ada tombol edit/hapus). Tipe lain
            // (umum/asisten/piket) tetap bisa diedit/dihapus oleh Admin.
            // [DIUBAH] Sebelumnya menampilkan ikon gembok sebagai placeholder;
            // sekarang dibiarkan kosong saja sesuai permintaan.
            if (type === 'kuliah') {
                actions = '';
            } else {
                actions = `<div class="flex gap-1 pl-3 border-l border-gray-100 ml-3 shrink-0">
                    <button onclick="openFormModal('edit', ${jsonStr})" class="w-8 h-8 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition flex items-center justify-center"><i class="fas fa-pen text-xs"></i></button>
                    <button onclick="triggerDelete('${evt.id}', '${type}')" class="w-8 h-8 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition flex items-center justify-center"><i class="fas fa-trash text-xs"></i></button>
                </div>`;
            }

            let extraInfo = '';
            if (props.dosen || props.kelas) {
                extraInfo = `<div class="mt-1 flex gap-2 text-[10px] text-gray-500">
                    ${props.dosen ? `<span class="bg-gray-100 px-1.5 rounded"><i class="fas fa-user-tie mr-1"></i>${props.dosen}</span>` : ''}
                    ${props.kelas ? `<span class="bg-gray-100 px-1.5 rounded"><i class="fas fa-chalkboard mr-1"></i>Kelas ${props.kelas}</span>` : ''}
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
                        <h4 class="font-bold text-gray-800 text-sm truncate">${props.title}</h4>
                        <p class="text-xs text-gray-500 truncate mt-0.5"><span class="font-semibold text-gray-700">${evt.user_name || 'Lab'}</span> • ${props.location}</p>
                        ${extraInfo}
                    </div>
                    ${actions}
                </div>`;
        });
    }

    // --- 6. UTILS LAINNYA ---
    function handleTypeChange() {
        const type = document.getElementById('inputType').value;
        const uContainer = document.getElementById('userSelectContainer');
        const uInput = document.getElementById('inputUser');
        const aFields = document.getElementById('asistenFields'); 
        const locToggleWrap = document.getElementById('locationToggleWrap');
        const locToggle = document.getElementById('inputLocationToggle');
        // [PERBAIKAN] #inputKelas (di dalam #asistenFields) memakai atribut
        // "required" tetap di HTML. Saat #asistenFields disembunyikan
        // (Jadwal Umum/Piket) tapi #inputKelas masih required & kosong,
        // browser memblokir submit dengan error konsol "An invalid form
        // control with name='kelas' is not focusable" - TANPA pesan apapun
        // ke pengguna, dan jadwal gagal dibuat. Solusinya: required harus
        // ikut di-nonaktifkan saat field-nya disembunyikan.
        const kelasInput = document.getElementById('inputKelas');

        if (type === 'umum') { 
            uContainer.classList.add('hidden'); uInput.required = false; uInput.value = ""; 
            if(aFields) aFields.classList.add('hidden');
            if(kelasInput) kelasInput.required = false;
            // [BARU - Modul Lab] Jadwal Umum: tampilkan toggle "Lokasi Luar Lab".
            locToggleWrap.style.display = 'flex';
        } else if (type === 'piket') {
            uContainer.classList.remove('hidden'); uInput.required = true;
            if(aFields) aFields.classList.add('hidden');
            if(kelasInput) kelasInput.required = false;
            // [BARU - Modul Lab] tipe selain Umum: sembunyikan toggle, paksa
            // mode dropdown laboratorium (tidak ada lokasi bebas).
            locToggleWrap.style.display = 'none';
            locToggle.checked = false; handleLocationToggle();
            // [BARU] Jadwal Piket: default lokasi ke "Laboratorium Terpadu
            // Fakultas Ilmu Komputer Universitas Muslim Indonesia" (jika
            // tersedia di daftar Laboratorium). Admin tetap bisa mengganti
            // dropdown ini untuk kondisi tertentu (mis. piket di lab
            // spesifik). Pada mode edit, nilai ini akan ditimpa oleh lokasi
            // tersimpan jika cocok dengan salah satu opsi (lihat
            // openFormModal).
            const locSelect = document.getElementById('inputLocationSelect');
            for (const opt of locSelect.options) {
                if (opt.textContent.trim() === 'Laboratorium Terpadu Fakultas Ilmu Komputer Universitas Muslim Indonesia') {
                    locSelect.value = opt.value;
                    break;
                }
            }
        } else if (type === 'class' || type === 'kuliah') { // Handle Tipe Kuliah
            uContainer.classList.remove('hidden'); uInput.required = true;
            if(aFields) aFields.classList.remove('hidden');
            if(kelasInput) kelasInput.required = true;
            locToggleWrap.style.display = 'none';
            locToggle.checked = false; handleLocationToggle();
        } else { // Asisten
            uContainer.classList.remove('hidden'); uInput.required = true;
            if(aFields) aFields.classList.remove('hidden');
            if(kelasInput) kelasInput.required = true;
            locToggleWrap.style.display = 'none';
            locToggle.checked = false; handleLocationToggle();
        }
    }

    // [BARU - Modul Lab] Toggle antara dropdown Laboratorium (#inputLocationSelect)
    // dan input teks bebas (#inputLocationText) - keduanya bernama "location",
    // tapi hanya field yang AKTIF (tidak disabled) yang ikut ter-submit.
    function handleLocationToggle() {
        const toggle = document.getElementById('inputLocationToggle');
        const select = document.getElementById('inputLocationSelect');
        const text = document.getElementById('inputLocationText');

        if (toggle.checked) {
            select.classList.add('hidden'); select.disabled = true; select.required = false;
            text.classList.remove('hidden'); text.disabled = false; text.required = true;
        } else {
            text.classList.add('hidden'); text.disabled = true; text.required = false; text.value = '';
            select.classList.remove('hidden'); select.disabled = false; select.required = true;
        }
    }

    function handleRepeatChange() { const m = document.getElementById('inputRepeatModel').value; const c = document.getElementById('endDateContainer'); const i = document.getElementById('inputEndDateRepeat'); const h = document.getElementById('repeatHint'); if (m === 'sekali') { c.classList.add('hidden'); i.required = false; h.innerText = "Jadwal hanya pada tanggal terpilih."; } else { c.classList.remove('hidden'); i.required = true; h.innerText = "Jadwal berulang sampai batas tanggal."; } }
    function openDayModal() { const m = document.getElementById('dayDetailModal'); m.classList.remove('hidden'); setTimeout(() => { document.getElementById('detailBackdrop').classList.remove('opacity-0'); document.getElementById('detailContent').classList.remove('opacity-0', 'scale-95'); document.getElementById('detailContent').classList.add('scale-100'); }, 10); }
    function closeDayModal() { const m = document.getElementById('dayDetailModal'); document.getElementById('detailBackdrop').classList.add('opacity-0'); document.getElementById('detailContent').classList.add('opacity-0', 'scale-95'); document.getElementById('detailContent').classList.remove('scale-100'); setTimeout(() => { m.classList.add('hidden'); }, 300); }
    function closeFormModal() { const m = document.getElementById('formModal'); document.getElementById('formBackdrop').classList.add('opacity-0'); document.getElementById('formContent').classList.add('opacity-0', 'scale-95'); document.getElementById('formContent').classList.remove('scale-100'); setTimeout(() => { m.classList.add('hidden'); }, 300); }
    function updateClock() { const now = new Date(); document.getElementById('liveDate').innerText = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }); document.getElementById('liveTime').innerText = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).replace(/\./g, ':'); } setInterval(updateClock, 1000); updateClock();

    function openFormModal(mode, eventData = null) {
        closeDayModal();
        const m = document.getElementById('formModal'); m.classList.remove('hidden'); 
        setTimeout(() => { document.getElementById('formBackdrop').classList.remove('opacity-0'); document.getElementById('formContent').classList.remove('opacity-0', 'scale-95'); document.getElementById('formContent').classList.add('scale-100'); }, 10);
        document.getElementById('scheduleForm').reset();

        // [BARU - Modul 5 V3] Sembunyikan banner sync setiap kali modal dibuka;
        // ditampilkan kembali di bawah jika edit jadwal dengan sync_status='failed'.
        hideSyncBanner();
        
        if (mode === 'add') {
            document.getElementById('formModalTitle').innerText = "Tambah Jadwal";
            document.getElementById('scheduleForm').action = BASE_URL + '/admin/addSchedule';
            document.getElementById('inputDate').value = selectedDateStr;
            // Jika sedang filter user spesifik, otomatis pilih user tersebut
            if(currentFilter !== 'all') document.getElementById('inputUser').value = currentFilter;
            // [BARU - Modul Dosen] pastikan input "Tambah Dosen Baru" tertutup
            // & tidak wajib saat membuka form Tambah (form.reset() di atas
            // tidak mengubah class 'hidden'/required yang di-set JS).
            if (document.getElementById('inputDosenBaru')) {
                document.getElementById('inputDosenBaru').classList.add('hidden');
                document.getElementById('inputDosenBaru').required = false;
            }
            // [BARU - Modul Lab] reset toggle Lokasi ke mode dropdown
            // laboratorium (form.reset() me-reset checkbox tapi tidak
            // memicu handleLocationToggle()).
            document.getElementById('inputLocationToggle').checked = false;
            handleLocationToggle();
            // [BARU] beri nilai default (lab pertama) pada dropdown Lokasi
            // agar tidak kosong saat modal Tambah dibuka - admin tetap bisa
            // menggantinya, tapi ini mengurangi kemungkinan submit terblokir
            // validasi karena field belum disentuh.
            const locSelectDefault = document.getElementById('inputLocationSelect');
            if (locSelectDefault.options.length > 1) locSelectDefault.value = locSelectDefault.options[1].value;
            handleTypeChange(); handleRepeatChange();
        } else {
            document.getElementById('formModalTitle').innerText = "Edit Jadwal";
            document.getElementById('scheduleForm').action = BASE_URL + '/admin/editSchedule';
            const props = eventData.extendedProps;
            document.getElementById('inputId').value = props.id;
            document.getElementById('inputType').value = props.type;
            document.getElementById('inputTitle').value = props.title;
            document.getElementById('inputDate').value = props.rawDate || selectedDateStr;
            document.getElementById('inputStart').value = props.fmtStartTime;
            document.getElementById('inputEnd').value = props.fmtEndTime;
            
            // Handle User Select visibility
            handleTypeChange();
            if (props.type !== 'umum') document.getElementById('inputUser').value = props.userId;

            // [BARU - Modul Lab] Cek apakah lokasi tersimpan cocok dengan
            // salah satu opsi Laboratorium (dari tabel `lab`). Jika cocok,
            // pakai mode dropdown. Jika tidak (mis. jadwal lama dengan
            // lokasi "Lab Terpadu", atau Jadwal Umum dengan lokasi luar lab),
            // dan tipe-nya 'umum', alihkan ke mode teks bebas. Untuk tipe
            // selain umum, dropdown tetap dipakai (handleTypeChange() di atas
            // sudah memaksa mode dropdown) - admin perlu memilih ulang jika
            // lokasi lama tidak ada di daftar.
            const locSelect = document.getElementById('inputLocationSelect');
            const isLabOption = Array.from(locSelect.options).some(o => o.value === props.location);
            if (props.type === 'umum' && !isLabOption) {
                document.getElementById('inputLocationToggle').checked = true;
                handleLocationToggle();
                document.getElementById('inputLocationText').value = props.location || '';
            } else {
                document.getElementById('inputLocationToggle').checked = false;
                handleLocationToggle();
                if (isLabOption) locSelect.value = props.location;
            }
            
            // [DIPERBAIKI - Modul Dosen] dropdown #inputIdDosen menggantikan
            // input teks #inputDosen. Pre-select berdasarkan props.idDosen
            // (hasil migrasi/penyimpanan sebelumnya). Jika id_dosen belum
            // ada (baris lama belum termigrasi), dropdown tetap kosong -
            // admin perlu memilih ulang dosennya. Input "Tambah Dosen Baru"
            // selalu disembunyikan & dikosongkan saat membuka modal edit.
            const dosenSelect = document.getElementById('inputIdDosen');
            if (dosenSelect) dosenSelect.value = props.idDosen || '';
            if (document.getElementById('inputDosenBaru')) {
                document.getElementById('inputDosenBaru').classList.add('hidden');
                document.getElementById('inputDosenBaru').required = false;
                document.getElementById('inputDosenBaru').value = '';
            }
            if (document.getElementById('inputKelas')) document.getElementById('inputKelas').value = props.kelas || '';

            document.getElementById('inputRepeatModel').value = props.repeatModel || 'sekali';
            if (props.repeatModel !== 'sekali') document.getElementById('inputEndDateRepeat').value = props.endDateRepeat;
            handleRepeatChange();

            // [BARU - Modul 5 V3] Tampilkan banner "Retry failed sync" jika
            // sinkronisasi Google Calendar item ini sebelumnya gagal.
            if (props.syncStatus === 'failed') showSyncBanner();
        }
    }

    // [BARU - Modul 5 V3] Helper show/hide banner status sync.
    function showSyncBanner() {
        const banner = document.getElementById('syncStatusBanner');
        banner.classList.remove('hidden');
        banner.classList.add('flex');
    }

    function hideSyncBanner() {
        const banner = document.getElementById('syncStatusBanner');
        banner.classList.add('hidden');
        banner.classList.remove('flex');
        const btn = document.getElementById('btnRetrySync');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-rotate-right"></i> Coba Sync Ulang';
    }

    // [BARU - Modul 5 V3] Coba sinkronkan ulang jadwal yang sedang dibuka di
    // modal edit ("Retry failed sync").
    function retrySync() {
        const id = document.getElementById('inputId').value;
        const type = document.getElementById('inputType').value;
        const btn = document.getElementById('btnRetrySync');

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Menyinkronkan...';

        const fd = new FormData();
        fd.append('id', id);
        fd.append('type', type);

        fetch(BASE_URL + '/admin/retrySync', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    hideSyncBanner();
                    showCustomAlert('success', 'Berhasil', data.message);
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-rotate-right"></i> Coba Sync Ulang';
                    showCustomAlert('error', 'Gagal', data.message || 'Sinkronisasi masih gagal.');
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-rotate-right"></i> Coba Sync Ulang';
                showCustomAlert('error', 'Gagal', 'Gagal menghubungi server.');
            });
    }

    function showCustomAlert(type, title, message) { const modal = document.getElementById('customAlertModal'); const iconBg = document.getElementById('alertIconBg'); const icon = document.getElementById('alertIcon'); const btn = document.getElementById('alertBtn'); document.getElementById('alertTitle').innerText = title; document.getElementById('alertMessage').innerText = message; if (type === 'success') { iconBg.className = 'w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-green-100 text-green-600'; icon.className = 'fas fa-check text-3xl'; btn.className = 'w-full py-3 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02] bg-green-600 hover:bg-green-700 shadow-green-500/30'; } else { iconBg.className = 'w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-red-100 text-red-600'; icon.className = 'fas fa-times text-3xl'; btn.className = 'w-full py-3 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02] bg-red-600 hover:bg-red-700 shadow-red-500/30'; } modal.classList.remove('hidden'); setTimeout(() => { document.getElementById('alertBackdrop').classList.remove('opacity-0'); document.getElementById('alertContent').classList.remove('scale-90', 'opacity-0'); document.getElementById('alertContent').classList.add('scale-100', 'opacity-100'); }, 50); }
    function closeCustomAlert() { const m = document.getElementById('customAlertModal'); document.getElementById('alertBackdrop').classList.add('opacity-0'); document.getElementById('alertContent').classList.remove('scale-100', 'opacity-100'); document.getElementById('alertContent').classList.add('scale-90', 'opacity-0'); setTimeout(() => m.classList.add('hidden'), 300); }
    
    let deleteUrl = '';
    function triggerDelete(id, type) {
        deleteUrl = `${BASE_URL}/admin/deleteSchedule?id=${id}&type=${type}`;
        const modal = document.getElementById('customConfirmModal');
        const content = document.getElementById('confirmContent');
        const backdrop = document.getElementById('confirmBackdrop');
        modal.classList.remove('hidden'); setTimeout(() => { backdrop.classList.remove('opacity-0'); content.classList.remove('scale-90', 'opacity-0'); content.classList.add('scale-100', 'opacity-100'); }, 50);
    }
    document.getElementById('confirmYesBtn').addEventListener('click', function() { if(deleteUrl) window.location.href = deleteUrl; });
    function closeCustomConfirm() { const modal = document.getElementById('customConfirmModal'); const content = document.getElementById('confirmContent'); const backdrop = document.getElementById('confirmBackdrop'); backdrop.classList.add('opacity-0'); content.classList.remove('scale-100', 'opacity-100'); content.classList.add('scale-90', 'opacity-0'); setTimeout(() => modal.classList.add('hidden'), 300); }
