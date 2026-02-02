<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow border-b-4 border-green-500 flex items-center justify-between">
        <div>
            <p class="text-gray-500 text-sm font-semibold">Hadir Hari Ini</p>
            <h2 class="text-3xl font-bold text-gray-800"><?= $data['count_hadir']; ?></h2>
        </div>
        <div class="bg-green-100 p-3 rounded-full text-green-600">
            <i class="fas fa-check-circle fa-2x"></i>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow border-b-4 border-yellow-500 flex items-center justify-between">
        <div>
            <p class="text-gray-500 text-sm font-semibold">Izin / Sakit</p>
            <h2 class="text-3xl font-bold text-gray-800"><?= $data['count_izin']; ?></h2>
        </div>
        <div class="bg-yellow-100 p-3 rounded-full text-yellow-600">
            <i class="fas fa-notes-medical fa-2x"></i>
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow border-b-4 border-red-500 flex items-center justify-between">
        <div>
            <p class="text-gray-500 text-sm font-semibold">Belum Hadir (Alpha)</p>
            <h2 class="text-3xl font-bold text-gray-800"><?= $data['count_alpha']; ?></h2>
        </div>
        <div class="bg-red-100 p-3 rounded-full text-red-600">
            <i class="fas fa-user-times fa-2x"></i>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="p-6 border-b flex flex-col md:flex-row justify-between items-center bg-gray-50">
        <div>
            <h3 class="text-lg font-bold text-gray-800">Log Monitoring Asisten</h3>
            <p class="text-sm text-gray-500">Data presensi real-time dari seluruh asisten.</p>
        </div>
        
        <div class="mt-4 md:mt-0 flex space-x-3">
            <div class="relative">
                <input type="text" placeholder="Cari nama asisten..." class="pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ic_blue text-sm">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            <button onclick="window.print()" class="bg-ic_dark text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700 transition">
                <i class="fas fa-print mr-2"></i> Export / Print
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-bold tracking-wider">
                <tr>
                    <th class="p-4 border-b">Asisten</th>
                    <th class="p-4 border-b">Tanggal</th>
                    <th class="p-4 border-b">Masuk</th>
                    <th class="p-4 border-b">Pulang</th>
                    <th class="p-4 border-b text-center">Status</th>
                    <th class="p-4 border-b">Logbook</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach($data['rekap'] as $row): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <img class="h-10 w-10 rounded-full object-cover border" 
                                     src="https://ui-avatars.com/api/?name=<?= $row['name']; ?>&background=random" 
                                     alt="">
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900"><?= $row['name']; ?></div>
                                <div class="text-xs text-gray-500">Asisten</div>
                            </div>
                        </div>
                    </td>
                    <td class="p-4 text-sm text-gray-600 whitespace-nowrap">
                        <?= date('d M Y', strtotime($row['date'])); ?>
                    </td>
                    <td class="p-4 text-sm text-green-600 font-bold">
                        <?= $row['check_in'] ? date('H:i', strtotime($row['check_in'])) : '-'; ?>
                    </td>
                    <td class="p-4 text-sm text-blue-600 font-bold">
                        <?= $row['check_out'] ? date('H:i', strtotime($row['check_out'])) : '-'; ?>
                    </td>
                    <td class="p-4 text-center">
                        <?php 
                            $statusClass = 'bg-gray-100 text-gray-800';
                            if($row['status'] == 'Hadir') $statusClass = 'bg-green-100 text-green-800';
                            if($row['status'] == 'Sakit' || $row['status'] == 'Izin') $statusClass = 'bg-yellow-100 text-yellow-800';
                            if($row['status'] == 'Alpha') $statusClass = 'bg-red-100 text-red-800';
                        ?>
                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass; ?>">
                            <?= $row['status']; ?>
                        </span>
                    </td>
                    <td class="p-4 text-sm text-gray-500 max-w-xs truncate" title="<?= $row['logbook']; ?>">
                        <?= $row['logbook'] ?: '<span class="italic text-gray-300">Belum diisi</span>'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($data['rekap'])): ?>
                <tr>
                    <td colspan="6" class="p-8 text-center text-gray-400">
                        <i class="fas fa-inbox fa-3x mb-3"></i><br>
                        Belum ada data presensi hari ini.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="p-4 border-t bg-gray-50 flex justify-between items-center text-xs text-gray-500">
        <span>Menampilkan semua data terbaru</span>
        <div class="flex space-x-1">
            <button class="px-3 py-1 border rounded bg-white hover:bg-gray-100">Prev</button>
            <button class="px-3 py-1 border rounded bg-white hover:bg-gray-100">Next</button>
        </div>
    </div>
</div>