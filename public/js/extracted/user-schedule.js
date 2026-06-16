
    const rawEvents = <?= json_encode($raw_schedules ?? []) ?>;
    const currentUserId = "<?= $_SESSION['profil_id'] ?>"; 
    
    <?php if(isset($_SESSION['flash'])): ?>
        document.addEventListener("DOMContentLoaded", function() { setTimeout(() => { showCustomAlert('<?= $_SESSION['flash']['type'] ?>', '<?= $_SESSION['flash']['title'] ?>', '<?= $_SESSION['flash']['message'] ?>'); }, 300); });
    <?php unset($_SESSION['flash']); endif; ?>

    let calendar;
    let selectedDateStr = new Date().toISOString().split('T')[0];

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        
        var isMobile = window.innerWidth < 768;

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
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
        renderUpcomingList(); 
    });

    function shouldShowEvent(evt) {
        const cat = document.getElementById('filterCategory').value;
        const type = evt.type.toLowerCase();
        if (cat !== 'all' && type !== cat) return false;

        const day = document.getElementById('filterDay').value;
        if (day !== 'all' && String(evt.day_of_week) !== String(day)) return false;

        const tStart = document.getElementById('filterStart').value;
        const tEnd = document.getElementById('filterEnd').value;
        const evtStart = (evt.start_time || '00:00').substring(0,5);
        const evtEnd = (evt.end_time || '00:00').substring(0,5);

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

    function renderCustomLayers() {
        document.querySelectorAll('.day-click-overlay').forEach(e => e.remove());
        document.querySelectorAll('.day-dots-container').forEach(e => e.remove());
        
        document.querySelectorAll('.fc-daygrid-day').forEach(cell => {
            const dateStr = cell.getAttribute('data-date'); if(!dateStr) return;
            const frame = cell.querySelector('.fc-daygrid-day-frame'); if(!frame) return;

            const clickLayer = document.createElement('div');
            clickLayer.className = 'day-click-overlay';
            clickLayer.onclick = function(e) {
                e.stopPropagation(); selectedDateStr = dateStr;
                renderDayDetails(dateStr); openDayModal();
            };
            frame.appendChild(clickLayer);

            let uniqueColors = new Set();
            rawEvents.forEach(evt => {
                if (!shouldShowEvent(evt)) return; 

                if (isEventOnDate(evt, dateStr)) {
                    let color = '#3b82f6'; 
                    const type = evt.type.toLowerCase();
                    if(type === 'piket') color = '#f97316';
                    if(type === 'umum') color = '#1f2937';
                    if(type === 'kuliah') color = '#10b981';
                    uniqueColors.add(color);
                }
            });

            if (uniqueColors.size > 0) {
                const dotsLayer = document.createElement('div');
                dotsLayer.className = 'day-dots-container';
                uniqueColors.forEach(color => {
                    const dot = document.createElement('div');
                    dot.className = 'dot-category';
                    dot.style.backgroundColor = color;
                    dotsLayer.appendChild(dot);
                });
                frame.appendChild(dotsLayer);
            }
        });
    }

    function renderUpcomingList() {
        const container = document.getElementById('upcomingListContainer');
        const now = new Date();
        now.setHours(0,0,0,0);

        let upcoming = [];

        rawEvents.forEach(evt => {
            const startDate = new Date(evt.start_date + "T00:00:00");
            const endDate = evt.end_date ? new Date(evt.end_date + "T23:59:59") : null;
            
            let instanceDate = null; 

            if (evt.model_perulangan === 'sekali') {
                if (startDate >= now) {
                    instanceDate = startDate;
                }
            } 
            else if (evt.model_perulangan === 'rentang') {
                if (startDate <= now && (!endDate || endDate >= now)) {
                    instanceDate = now; 
                } 
                else if (startDate > now) {
                    instanceDate = startDate;
                }
            } 
            else if (evt.model_perulangan === 'mingguan') {
                const targetDayDB = parseInt(evt.day_of_week); 
                const targetDayJS = targetDayDB === 7 ? 0 : targetDayDB; 

                let checkDate = new Date(now);
                for(let i=0; i<7; i++) {
                    if (checkDate.getDay() === targetDayJS) {
                        if (checkDate >= startDate && (!endDate || checkDate <= endDate)) {
                            instanceDate = new Date(checkDate);
                        }
                        break; 
                    }
                    checkDate.setDate(checkDate.getDate() + 1);
                }
            }

            if (instanceDate) {
                let nextEvt = {...evt};
                nextEvt.displayDate = instanceDate; 
                upcoming.push(nextEvt);
            }
        });

        upcoming.sort((a, b) => {
            const dateA = a.displayDate;
            const dateB = b.displayDate;
            if (dateA.getTime() !== dateB.getTime()) return dateA - dateB;
            return (a.start_time || '00:00').localeCompare(b.start_time || '00:00');
        });

        upcoming = upcoming.slice(0, 10);

        if (upcoming.length === 0) {
            container.innerHTML = `<div class="text-center py-8 text-gray-400 text-xs italic">Tidak ada jadwal mendatang.</div>`;
            return;
        }

        container.innerHTML = '';
        upcoming.forEach(evt => {
            const d = evt.displayDate;
            const dayName = d.toLocaleDateString('id-ID', { weekday: 'short' }); // Senin, Sel, dll
            const dateNum = d.getDate();
            const timeFmt = (evt.start_time || '00:00').substring(0,5);
            const type = (evt.type || 'asisten').toLowerCase();
            
            let colorClass = 'bg-blue-50 text-blue-600 border-blue-200';
            if(type === 'piket') colorClass = 'bg-orange-50 text-orange-600 border-orange-200';
            if(type === 'umum') colorClass = 'bg-gray-50 text-gray-600 border-gray-200';
            if(type === 'kuliah') colorClass = 'bg-green-50 text-green-600 border-green-200';

            container.innerHTML += `
                <div class="upcoming-card bg-white p-3 rounded-2xl border border-gray-100 flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl ${colorClass} border flex flex-col items-center justify-center shrink-0">
                        <span class="text-[9px] font-bold uppercase leading-none opacity-80">${dayName}</span>
                        <span class="text-xs font-bold leading-none mt-0.5">${dateNum}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="font-bold text-gray-800 text-xs truncate" title="${evt.title}">${evt.title}</h4>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-[10px] text-gray-500 font-mono flex items-center">
                                <i class="far fa-clock mr-1 text-[9px]"></i>${timeFmt}
                            </span>
                            <span class="text-[9px] px-1.5 py-0.5 rounded-md bg-gray-100 text-gray-500 truncate capitalize">
                                ${type}
                            </span>
                        </div>
                    </div>
                </div>
            `;
        });
    }

    function renderDayDetails(dateStr) {
        const container = document.getElementById('modalListContainer');
        const dateObjForTitle = new Date(dateStr + "T00:00:00");
        document.getElementById('modalDateTitle').innerText = dateObjForTitle.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        container.innerHTML = '';

        const visibleEvents = rawEvents.filter(evt => {
            if (!shouldShowEvent(evt)) return false; 
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
            else if(type === 'kuliah') { badgeClass = 'bg-green-50 text-green-600 border-green-100'; icon = 'fa-graduation-cap'; }

            let actions = '';
            if (type === 'kuliah' && String(evt.id_profil) === String(currentUserId)) {
                const props = {
                    id: evt.id, title: evt.title, location: evt.location || 'Lab',
                    dosen: evt.dosen || '', kelas: evt.kelas || '', 
                    rawDate: evt.start_date, fmtStartTime: (evt.start_time || '00:00').substring(0,5),
                    fmtEndTime: (evt.end_time || '00:00').substring(0,5),
                    repeatModel: evt.model_perulangan || 'sekali', endDateRepeat: evt.end_date
                };
                const jsonStr = JSON.stringify({ extendedProps: props }).replace(/"/g, '&quot;');
                
                actions = `<div class="flex gap-1 pl-3 border-l border-gray-100 ml-3 shrink-0">
                    <button onclick="openFormModal('edit', ${jsonStr})" class="w-8 h-8 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition flex items-center justify-center"><i class="fas fa-pen text-xs"></i></button>
                    <button onclick="triggerDelete('${evt.id}', 'kuliah')" class="w-8 h-8 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition flex items-center justify-center"><i class="fas fa-trash text-xs"></i></button>
                </div>`;
            }

            let extraInfo = '';
            if (evt.dosen || evt.kelas) {
                extraInfo = `<div class="mt-1 flex gap-2 text-[10px] text-gray-500">
                    ${evt.dosen ? `<span class="bg-gray-100 px-1.5 rounded"><i class="fas fa-user-tie mr-1"></i>${evt.dosen}</span>` : ''}
                    ${evt.kelas ? `<span class="bg-gray-100 px-1.5 rounded"><i class="fas fa-chalkboard mr-1"></i>${evt.kelas}</span>` : ''}
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
                            <span class="w-auto px-2 h-5 rounded-md flex items-center justify-center border text-[10px] ${badgeClass} gap-1"><i class="fas ${icon}"></i> <span class="uppercase tracking-wider font-bold">${type}</span></span>
                        </div>
                        <h4 class="font-bold text-gray-800 text-sm truncate">${evt.title}</h4>
                        <p class="text-xs text-gray-500 truncate mt-0.5"><span class="font-semibold text-gray-700">${evt.user_name || 'Lab'}</span> • ${evt.location || 'Lab'}</p>
                        ${extraInfo}
                    </div>
                    ${actions}
                </div>`;
        });
    }

    function handleRepeatChange() { const m = document.getElementById('inputRepeatModel').value; const c = document.getElementById('endDateContainer'); const i = document.getElementById('inputEndDateRepeat'); if (m === 'sekali') { c.classList.add('hidden'); i.required = false; } else { c.classList.remove('hidden'); i.required = true; } }
    function openDayModal() { const m = document.getElementById('dayDetailModal'); m.classList.remove('hidden'); setTimeout(() => { document.getElementById('detailBackdrop').classList.remove('opacity-0'); document.getElementById('detailContent').classList.remove('opacity-0', 'scale-95'); document.getElementById('detailContent').classList.add('scale-100'); }, 10); }
    function closeDayModal() { const m = document.getElementById('dayDetailModal'); document.getElementById('detailBackdrop').classList.add('opacity-0'); document.getElementById('detailContent').classList.add('opacity-0', 'scale-95'); document.getElementById('detailContent').classList.remove('scale-100'); setTimeout(() => { m.classList.add('hidden'); }, 300); }
    function closeFormModal() { const m = document.getElementById('formModal'); document.getElementById('formBackdrop').classList.add('opacity-0'); document.getElementById('formContent').classList.add('opacity-0', 'scale-95'); document.getElementById('formContent').classList.remove('scale-100'); setTimeout(() => { m.classList.add('hidden'); }, 300); }
    function updateClock() { const now = new Date(); document.getElementById('liveDate').innerText = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }); document.getElementById('liveTime').innerText = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).replace(/\./g, ':'); } setInterval(updateClock, 1000); updateClock();

    function openFormModal(mode, eventData = null) {
        closeDayModal();
        const m = document.getElementById('formModal'); m.classList.remove('hidden'); 
        setTimeout(() => { document.getElementById('formBackdrop').classList.remove('opacity-0'); document.getElementById('formContent').classList.remove('opacity-0', 'scale-95'); document.getElementById('formContent').classList.add('scale-100'); }, 10);
        document.getElementById('scheduleForm').reset();
        
        if (mode === 'add') {
            document.getElementById('formModalTitle').innerText = "Tambah Jadwal Kuliah";
            document.getElementById('scheduleForm').action = "<?= BASE_URL ?>/user/addSchedule";
            document.getElementById('inputDate').value = selectedDateStr;
            handleRepeatChange();
        } else {
            document.getElementById('formModalTitle').innerText = "Edit Jadwal Kuliah";
            document.getElementById('scheduleForm').action = "<?= BASE_URL ?>/user/editSchedule";
            const props = eventData.extendedProps;
            document.getElementById('inputId').value = props.id;
            document.getElementById('inputTitle').value = props.title;
            document.getElementById('inputKelas').value = props.kelas || '';
            document.getElementById('inputLocation').value = props.location;
            document.getElementById('inputDosen').value = props.dosen || '';
            document.getElementById('inputDate').value = props.rawDate || selectedDateStr;
            document.getElementById('inputStart').value = props.fmtStartTime;
            document.getElementById('inputEnd').value = props.fmtEndTime;
            
            document.getElementById('inputRepeatModel').value = props.repeatModel || 'sekali';
            if (props.repeatModel !== 'sekali') document.getElementById('inputEndDateRepeat').value = props.endDateRepeat;
            handleRepeatChange();
        }
    }

    function showCustomAlert(type, title, message) { const modal = document.getElementById('customAlertModal'); const iconBg = document.getElementById('alertIconBg'); const icon = document.getElementById('alertIcon'); const btn = document.getElementById('alertBtn'); document.getElementById('alertTitle').innerText = title; document.getElementById('alertMessage').innerText = message; if (type === 'success') { iconBg.className = 'w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-green-100 text-green-600'; icon.className = 'fas fa-check text-3xl'; btn.className = 'w-full py-3 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02] bg-green-600 hover:bg-green-700 shadow-green-500/30'; } else { iconBg.className = 'w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-red-100 text-red-600'; icon.className = 'fas fa-times text-3xl'; btn.className = 'w-full py-3 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02] bg-red-600 hover:bg-red-700 shadow-red-500/30'; } modal.classList.remove('hidden'); setTimeout(() => { document.getElementById('alertBackdrop').classList.remove('opacity-0'); document.getElementById('alertContent').classList.remove('scale-90', 'opacity-0'); document.getElementById('alertContent').classList.add('scale-100', 'opacity-100'); }, 50); }
    function closeCustomAlert() { const m = document.getElementById('customAlertModal'); document.getElementById('alertBackdrop').classList.add('opacity-0'); document.getElementById('alertContent').classList.remove('scale-100', 'opacity-100'); document.getElementById('alertContent').classList.add('scale-90', 'opacity-0'); setTimeout(() => m.classList.add('hidden'), 300); }
    
    let deleteUrl = '';
    function triggerDelete(id, type) {
        deleteUrl = `<?= BASE_URL ?>/user/deleteSchedule?id=${id}&type=${type}`;
        const modal = document.getElementById('customConfirmModal');
        const content = document.getElementById('confirmContent');
        const backdrop = document.getElementById('confirmBackdrop');
        modal.classList.remove('hidden'); setTimeout(() => { backdrop.classList.remove('opacity-0'); content.classList.remove('scale-90', 'opacity-0'); content.classList.add('scale-100', 'opacity-100'); }, 50);
    }
    document.getElementById('confirmYesBtn').addEventListener('click', function() { if(deleteUrl) window.location.href = deleteUrl; });
    function closeCustomConfirm() { const modal = document.getElementById('customConfirmModal'); const content = document.getElementById('confirmContent'); const backdrop = document.getElementById('confirmBackdrop'); backdrop.classList.add('opacity-0'); content.classList.remove('scale-100', 'opacity-100'); content.classList.add('scale-90', 'opacity-0'); setTimeout(() => modal.classList.add('hidden'), 300); }
