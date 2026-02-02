<style>
    .marquee-container { overflow: hidden; white-space: nowrap; }
    .marquee-content { display: inline-flex; animation: scroll 20s linear infinite; }
    .marquee-container:hover .marquee-content { animation-play-state: paused; }
    @keyframes scroll { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
    .perspective-1000 { perspective: 1000px; }
    .transform-style-3d { transform-style: preserve-3d; }
    .backface-hidden { backface-visibility: hidden; }
    .rotate-y-180 { transform: rotateY(180deg); }
</style>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-lg text-center">
        <h3 class="font-bold mb-4">QR Code Generator</h3>
        <button onclick="showModal('in')" class="w-full bg-blue-600 text-white py-2 rounded mb-2">Show QR Masuk (Auto)</button>
        <button onclick="showModal('out')" class="w-full bg-orange-500 text-white py-2 rounded">Show QR Pulang</button>
    </div>
    <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-lg">
        <h3 class="font-bold mb-4">Status Laboratorium</h3>
        <div class="grid grid-cols-3 gap-4">
            <?php foreach($data['labs'] as $lab): ?>
            <div class="border p-4 rounded bg-gray-50">
                <h4 class="font-bold"><?= $lab['lab_name']; ?></h4>
                <p class="text-sm text-gray-500">PJ: <?= $lab['pj_name']; ?></p>
                <span class="text-xs px-2 py-1 rounded bg-blue-100 text-blue-800"><?= $lab['status']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="marquee-container mb-8 py-4">
    <div class="marquee-content space-x-6 px-4">
        <?php 
        $all = array_merge($data['assistants'], $data['assistants']);
        foreach($all as $ast): 
            $json = htmlspecialchars(json_encode($ast), ENT_QUOTES, 'UTF-8');
        ?>
        <div onclick='openCard(<?= $json; ?>)' class="w-48 h-64 flex-shrink-0 bg-white rounded-xl shadow border cursor-pointer hover:scale-110 transition relative group">
            <img src="https://ui-avatars.com/api/?name=<?= $ast['name']; ?>&size=200" class="w-full h-full object-cover opacity-90 group-hover:opacity-100">
            <div class="absolute bottom-0 w-full bg-black bg-opacity-50 text-white p-2">
                <p class="font-bold text-sm truncate"><?= $ast['name']; ?></p>
                <p class="text-xs"><?= $ast['specialization']; ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="flex justify-between mb-4">
        <h3 class="font-bold">Analitik Presensi 7 Hari</h3>
        <div>
            <button onclick="renderChart('bar')" class="px-3 py-1 bg-gray-200 rounded text-sm">Bar</button>
            <button onclick="renderChart('line')" class="px-3 py-1 bg-gray-200 rounded text-sm">Line</button>
        </div>
    </div>
    <div class="h-64"><canvas id="grandChart"></canvas></div>
</div>

<div id="qrModal" class="fixed inset-0 hidden bg-black bg-opacity-90 flex items-center justify-center z-50">
    <div class="bg-white p-8 rounded text-center">
        <h2 id="qrTitle" class="text-2xl font-bold mb-4">QR CODE</h2>
        <img id="qrImage" src="" class="mx-auto w-64 h-64 border">
        <button onclick="document.getElementById('qrModal').classList.add('hidden')" class="mt-4 text-red-500 underline">Tutup</button>
    </div>
</div>

<script>
    const BASE_URL = "<?= BASEURL; ?>";
    const chartData = <?= json_encode($data['chart_stats']); ?>;
    let myChart = null;

    function renderChart(type) {
        if(myChart) myChart.destroy();
        const ctx = document.getElementById('grandChart').getContext('2d');
        myChart = new Chart(ctx, {
            type: type,
            data: {
                labels: chartData.labels,
                datasets: [
                    { label: 'Hadir', data: chartData.hadir, backgroundColor: '#10B981', borderColor: '#10B981' },
                    { label: 'Pulang', data: chartData.pulang, backgroundColor: '#3B82F6', borderColor: '#3B82F6' },
                    { label: 'Sakit/Izin', data: chartData.sakit, backgroundColor: '#F59E0B', borderColor: '#F59E0B' },
                    { label: 'Alpha', data: chartData.alpha, backgroundColor: '#EF4444', borderColor: '#EF4444' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }
    renderChart('bar');

    function showModal(type) {
        document.getElementById('qrModal').classList.remove('hidden');
        if(type === 'in') {
            document.getElementById('qrTitle').innerText = "SCAN MASUK";
            updateQr(); setInterval(updateQr, 10000);
        } else {
            document.getElementById('qrTitle').innerText = "SCAN PULANG";
            const tokenOut = "<?= $this->model('Admin_model')->getCheckOutToken(); ?>";
            const url = `${BASE_URL}/presensi/scan/${tokenOut}`;
            document.getElementById('qrImage').src = `https://api.qrserver.com/v1/create-qr-code/?size=300&data=${encodeURIComponent(url)}`;
        }
    }

    function updateQr() {
        fetch(`${BASE_URL}/dashboard/get_live_token`)
            .then(res => res.json())
            .then(d => {
                const url = `${BASE_URL}/presensi/scan/${d.token}`;
                document.getElementById('qrImage').src = `https://api.qrserver.com/v1/create-qr-code/?size=300&data=${encodeURIComponent(url)}`;
            });
    }

    function openCard(data) { alert("Info: " + data.name + "\nBio: " + data.bio); /* Implementasikan Modal 3D disini jika perlu detail */ }
</script>