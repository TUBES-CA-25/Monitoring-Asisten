<div class="max-w-7xl mx-auto space-y-6 animate-enter">
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-end gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-800">Rekap Presensi</h1>
            <p class="text-gray-500 text-sm mt-1">Monitoring kehadiran asisten per hari.</p>
        </div>
        <form class="flex items-center gap-2 bg-gray-50 p-2 rounded-xl border border-gray-200 shadow-inner">
            <input type="date" name="date" value="<?= $filter_date ?>" class="bg-transparent border-none focus:ring-0 text-sm font-bold text-gray-600 outline-none">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-lg hover:bg-indigo-700 transition">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50 text-xs font-bold text-gray-400 uppercase border-b">
                <tr>
                    <th class="p-5">Asisten</th>
                    <th class="p-5">Jam Masuk</th>
                    <th class="p-5">Jam Pulang</th>
                    <th class="p-5">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if(!empty($attendance_list)): foreach($attendance_list as $row): ?>
                <tr class="hover:bg-indigo-50/30 transition duration-150">
                    <td class="p-5">
                        <div class="flex items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['name']) ?>&background=random" class="w-8 h-8 rounded-full border border-gray-200">
                            <span class="font-bold text-gray-700 text-sm"><?= $row['name'] ?></span>
                        </div>
                    </td>
                    <td class="p-5 text-green-600 font-mono text-sm font-bold">
                        <?= $row['check_in_time'] ? '<i class="fas fa-arrow-down text-xs mr-1"></i>'.date('H:i', strtotime($row['check_in_time'])) : '-' ?>
                    </td>
                    <td class="p-5 text-red-600 font-mono text-sm font-bold">
                        <?= $row['check_out_time'] ? '<i class="fas fa-arrow-up text-xs mr-1"></i>'.date('H:i', strtotime($row['check_out_time'])) : '-' ?>
                    </td>
                    <td class="p-5">
                        <span class="px-3 py-1 rounded-full text-[10px] font-bold bg-green-100 text-green-700 border border-green-200"><?= $row['status'] ?></span>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="4" class="p-10 text-center text-gray-400 italic">Tidak ada data presensi pada tanggal ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>