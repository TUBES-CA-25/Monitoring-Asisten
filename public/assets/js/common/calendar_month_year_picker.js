// [BARU] Popover pemilih bulan & tahun untuk kalender FullCalendar di
// halaman Jadwal (Admin/Kepala Lab/User). Diinisialisasi lewat
// window.initCalendarMonthYearPicker(calendar, calendarEl), dipanggil oleh
// masing-masing schedule.js SETELAH calendar.render() supaya toolbar
// (.fc-toolbar-title) sudah ada di DOM.
//
// [PENTING] Fungsi didefinisikan di TOP LEVEL (bukan dibungkus
// DOMContentLoaded) supaya tersedia begitu file ini SELESAI dimuat browser,
// terlepas dari urutan page_js vs js di footer.php (page_js dimuat SEBELUM
// js) - lihat komentar di titik pemanggilannya pada masing-masing
// schedule.js. Sama seperti script halaman lain di situs ini, file ini
// dimuat ulang tiap navigasi AJAX (lihat global.js _iclabsLoadPageScripts),
// jadi state di sini selalu fresh per kunjungan halaman.
(function () {
    "use strict";

    var MONTH_LABELS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    window.initCalendarMonthYearPicker = function (calendar, calendarEl) {
        if (!calendar || !calendarEl) return;

        var titleEl = calendarEl.querySelector('.fc-toolbar-title');
        // dataset guard: kalau suatu saat dipanggil dua kali pada elemen yang
        // sama, jangan pasang listener/panel dobel.
        if (!titleEl || titleEl.dataset.iclabsPickerBound) return;
        titleEl.dataset.iclabsPickerBound = '1';

        var todayDate = new Date();
        var pickerYear = calendar.getDate().getFullYear();

        // Chevron kecil di sebelah judul bulan sebagai penanda "bisa diklik".
        var chevron = document.createElement('i');
        chevron.className = 'fas fa-chevron-down iclabs-cal-title-chevron';
        titleEl.appendChild(chevron);

        // Panel popover ditaruh di document.body (bukan di dalam #calendar),
        // position:fixed, posisi dihitung ulang tiap dibuka lewat
        // getBoundingClientRect() - supaya TIDAK ikut terpotong oleh ancestor
        // manapun yang overflow:hidden/auto (pola sama seperti perbaikan
        // sidebar-tooltip sebelumnya, lihat catatan di global.css).
        var panel = document.createElement('div');
        panel.className = 'iclabs-mypicker-panel';
        panel.innerHTML =
            '<div class="iclabs-mypicker-year">' +
                '<button type="button" class="iclabs-mypicker-year-btn" data-dir="-1" aria-label="Tahun sebelumnya"><i class="fas fa-chevron-left"></i></button>' +
                '<span class="iclabs-mypicker-year-label"></span>' +
                '<button type="button" class="iclabs-mypicker-year-btn" data-dir="1" aria-label="Tahun berikutnya"><i class="fas fa-chevron-right"></i></button>' +
            '</div>' +
            '<div class="iclabs-mypicker-months"></div>';
        document.body.appendChild(panel);

        var yearLabel = panel.querySelector('.iclabs-mypicker-year-label');
        var monthsGrid = panel.querySelector('.iclabs-mypicker-months');

        MONTH_LABELS.forEach(function (label, idx) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'iclabs-mypicker-month';
            btn.textContent = label;
            btn.dataset.month = idx;
            monthsGrid.appendChild(btn);
        });

        function renderPanel() {
            yearLabel.textContent = pickerYear;
            var viewDate = calendar.getDate();
            var viewMonth = viewDate.getMonth();
            var viewYear = viewDate.getFullYear();

            monthsGrid.querySelectorAll('.iclabs-mypicker-month').forEach(function (btn) {
                var m = parseInt(btn.dataset.month, 10);
                var isCurrentView = (pickerYear === viewYear && m === viewMonth);
                var isTodayMonth = (pickerYear === todayDate.getFullYear() && m === todayDate.getMonth());
                btn.classList.toggle('is-current-month', isCurrentView);
                btn.classList.toggle('is-today-month', isTodayMonth);
            });
        }

        function positionPanel() {
            var rect = titleEl.getBoundingClientRect();
            var panelWidth = panel.offsetWidth || 280;
            var left = rect.left;
            if (left + panelWidth > window.innerWidth - 12) {
                left = window.innerWidth - panelWidth - 12;
            }
            if (left < 12) left = 12;
            panel.style.top = (rect.bottom + 10) + 'px';
            panel.style.left = left + 'px';
        }

        function openPanel() {
            pickerYear = calendar.getDate().getFullYear();
            renderPanel();
            positionPanel();
            panel.classList.add('is-open');
            chevron.classList.add('is-open');
            document.addEventListener('click', onDocClick, true);
            document.addEventListener('keydown', onKeydown);
            window.addEventListener('resize', closePanel);
            window.addEventListener('scroll', onScroll, true);
        }

        function closePanel() {
            panel.classList.remove('is-open');
            chevron.classList.remove('is-open');
            document.removeEventListener('click', onDocClick, true);
            document.removeEventListener('keydown', onKeydown);
            window.removeEventListener('resize', closePanel);
            window.removeEventListener('scroll', onScroll, true);
        }

        function onScroll(e) {
            if (panel.contains(e.target)) return;
            closePanel();
        }

        function onDocClick(e) {
            if (panel.contains(e.target) || e.target === titleEl || titleEl.contains(e.target)) return;
            closePanel();
        }

        function onKeydown(e) {
            if (e.key === 'Escape') closePanel();
        }

        titleEl.addEventListener('click', function (e) {
            e.stopPropagation();
            if (panel.classList.contains('is-open')) {
                closePanel();
            } else {
                openPanel();
            }
        });

        panel.querySelectorAll('.iclabs-mypicker-year-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                pickerYear += parseInt(btn.dataset.dir, 10);
                renderPanel();
            });
        });

        monthsGrid.addEventListener('click', function (e) {
            var btn = e.target.closest('.iclabs-mypicker-month');
            if (!btn) return;
            var month = parseInt(btn.dataset.month, 10);
            calendar.gotoDate(new Date(pickerYear, month, 1));
            closePanel();
        });

        // Kalau popover kebetulan terbuka saat pengguna klik prev/next/today
        // bawaan FullCalendar, tutup saja supaya tidak menampilkan state yang
        // sudah tidak sinkron dengan tampilan kalender.
        calendarEl.addEventListener('click', function (e) {
            if (e.target.closest('.fc-prev-button, .fc-next-button, .fc-today-button')) {
                closePanel();
            }
        });
    };
})();
