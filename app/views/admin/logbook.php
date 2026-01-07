<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

<div class="max-w-7xl mx-auto space-y-6 animate-enter">
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-3xl p-8 text-white shadow-xl">
        <h1 class="text-2xl font-bold">Monitoring Logbook</h1>
        <p class="text-indigo-100 text-sm">Klik tanggal untuk melihat detail aktivitas.</p>
    </div>

    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 h-[700px]">
        <div id='calendar' class="h-full"></div>
    </div>
</div>

<div id="logModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50">
    <div class="bg-white p-6 rounded-2xl max-w-sm w-full">
        <h3 id="mDate" class="font-bold text-lg mb-2"></h3>
        <p id="mActivity" class="text-gray-600 text-sm bg-gray-50 p-3 rounded-lg"></p>
        <button onclick="document.getElementById('logModal').classList.add('hidden')" class="mt-4 w-full py-2 bg-indigo-100 text-indigo-700 font-bold rounded-lg">Tutup</button>
    </div>
</div>

<script>
    const events = <?= json_encode($raw_logs ?? []) ?>.map(l => ({
        title: l.activity_detail, start: l.date,
        extendedProps: { activity: l.activity_detail, user: l.user_name }
    }));

    document.addEventListener('DOMContentLoaded', function() {
        var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
            initialView: 'dayGridMonth', events: events,
            eventClick: function(info) {
                document.getElementById('mDate').innerText = info.event.start.toLocaleDateString() + ' - ' + info.event.extendedProps.user;
                document.getElementById('mActivity').innerText = info.event.extendedProps.activity;
                document.getElementById('logModal').classList.remove('hidden');
            }
        });
        calendar.render();
    });
</script>