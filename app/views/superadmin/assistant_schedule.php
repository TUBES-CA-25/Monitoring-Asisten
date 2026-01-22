<style>
    .animate-enter { animation: fadeInUp 0.5s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-10">
    
    <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-4">
            <a href="<?= BASE_URL ?>/superadmin/assistantDetail/<?= $assistant['id'] ?>" class="w-10 h-10 flex items-center justify-center bg-white rounded-full shadow-sm text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-extrabold text-gray-800">Daftar Jadwal</h1>
                <p class="text-gray-500 text-sm">
                    Asisten: <span class="font-bold text-blue-600"><?= $assistant['name'] ?></span>
                </p>
            </div>
        </div>
        
        <div class="w-12 h-12 rounded-full p-1 bg-white shadow-md border border-gray-100">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($assistant['name']) ?>&background=random" class="w-full h-full rounded-full object-cover">
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-8 min-h-[600px]">
        
        <?php if(empty($schedules)): ?>
            <div class="flex flex-col items-center justify-center h-96 text-gray-400">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                    <i class="far fa-calendar-times text-3xl opacity-50"></i>
                </div>
                <p class="text-lg font-bold text-gray-500">Tidak ada jadwal ditemukan.</p>
                <p class="text-sm">Asisten ini belum memiliki jadwal kuliah atau tugas.</p>
            </div>
        <?php else: ?>
            
            <div class="space-y-4">
                <?php 
                // Helper Translate Hari
                $daysIndo = [
                    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
                ];

                foreach($schedules as $sch): 
                    // Format Data
                    $dayName = $daysIndo[date('l', strtotime($sch['start_time']))];
                    $startTime = date('H:i', strtotime($sch['start_time']));
                    $endTime = $sch['end_time'] ? date('H:i', strtotime($sch['end_time'])) : 'Selesai';
                    
                    // Warna Border Kiri berdasarkan Tipe Jadwal
                    $borderClass = 'border-l-4 ';
                    if ($sch['type'] == 'class') $borderClass .= 'border-l-green-500'; // Kuliah
                    elseif ($sch['type'] == 'assistant') $borderClass .= 'border-l-blue-500'; // Asisten
                    else $borderClass .= 'border-l-orange-500'; // Piket
                ?>
                
                <div class="flex flex-col md:flex-row items-stretch bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-md transition group <?= $borderClass ?>">
                    
                    <div class="w-full md:w-32 bg-gray-50 flex items-center justify-center p-4 border-b md:border-b-0 md:border-r border-gray-100">
                        <span class="text-sm font-bold text-gray-700 uppercase tracking-wider"><?= $dayName ?></span>
                    </div>

                    <div class="flex-1 p-5 flex flex-col justify-center border-b md:border-b-0 md:border-r border-gray-100">
                        <h4 class="font-bold text-gray-800 text-base mb-1 group-hover:text-blue-600 transition"><?= $sch['title'] ?></h4>
                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">
                            <?= $sch['description'] ?? 'Kegiatan Akademik' ?> 
                            <?php if($sch['type'] == 'class') echo '<span class="ml-2 text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full">KULIAH</span>'; ?>
                            <?php if($sch['type'] == 'assistant') echo '<span class="ml-2 text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">JAGA LAB</span>'; ?>
                        </p>
                    </div>

                    <div class="w-full md:w-32 flex items-center justify-center p-4 border-b md:border-b-0 md:border-r border-gray-100 bg-white">
                        <div class="text-center">
                            <span class="block text-xs font-bold text-gray-400 uppercase mb-1">JAM</span>
                            <span class="font-mono text-sm font-bold text-gray-700"><?= $startTime ?> - <?= $endTime ?></span>
                        </div>
                    </div>

                    <div class="w-full md:w-40 flex items-center justify-center p-4 bg-white">
                        <div class="text-center w-full">
                            <span class="block text-xs font-bold text-gray-400 uppercase mb-1 md:hidden">RUANGAN</span>
                            <div class="py-2 px-4 bg-gray-100 text-gray-600 rounded-lg text-xs font-bold uppercase truncate border border-gray-200">
                                <?= $sch['location'] ?? 'Lab Komputer' ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
        
    </div>
</div>
