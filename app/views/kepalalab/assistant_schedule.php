<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-10">
    
    <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-4">
            <a href="<?= BASE_URL ?>/kepalalab/dashboard/" class="w-10 h-10 flex items-center justify-center bg-white rounded-full shadow-sm text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-extrabold text-gray-800">Daftar Jadwal</h1>
                <p class="text-gray-500 text-sm">
                    Asisten: <span class="font-bold text-blue-600"><?= htmlspecialchars($assistant['name'], ENT_QUOTES, 'UTF-8') ?></span>
                </p>
            </div>
        </div>
        
        <div class="w-12 h-12 rounded-full p-1 bg-white shadow-md border border-gray-100">
            <?php 
                $photoName = $assistant['photo_profile'] ?? '';
                $photoPath = 'uploads/profile/' . $photoName;
                if (!empty($photoName) && file_exists($photoPath)) {
                    $avatarUrl = BASE_URL . '/' . $photoPath;
                } else {
                    $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($assistant['name']) . "&background=random&size=500";
                }
            ?>
            <img src="<?= $avatarUrl ?>" class="w-full h-full rounded-full object-cover shadow-lg">
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
                    // Helper Arrays
                    $daysIndo = [
                        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 
                        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
                        // Support juga jika format hari berupa angka (1=Senin, 7=Minggu)
                        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
                    ];

                    foreach($schedules as $sch): 
                        $type = strtolower($sch['type'] ?? '');
                        
                        // --- STYLE CONFIG (Biarkan kode style Anda yang panjang di sini) ---
                        // (Saya singkat agar fokus ke perbaikan logic)
                        $style = [
                            'border_l' => 'border-l-gray-500', 'bg_date' => 'bg-gray-50', 'text_date' => 'text-gray-700',
                            'bg_hover' => 'group-hover:bg-gray-100', 'text_hover' => 'group-hover:text-gray-600',
                            'badge_bg' => 'bg-gray-100', 'badge_text' => 'text-gray-700', 'badge_border' => 'border-gray-200',
                            'icon_bg_hover' => 'group-hover:bg-gray-200', 'icon_text_hover' => 'group-hover:text-gray-600',
                            'icon' => 'fa-calendar-alt', 'label' => 'KEGIATAN'
                        ];

                        // Mapping Style berdasarkan Tipe
                        if (in_array($type, ['umum', 'general', 'lab'])) {
                             $style = ['border_l'=>'border-l-gray-800', 'bg_date'=>'bg-gray-100', 'text_date'=>'text-gray-800', 'bg_hover'=>'group-hover:bg-gray-200', 'text_hover'=>'group-hover:text-gray-900', 'badge_bg'=>'bg-gray-800', 'badge_text'=>'text-white', 'badge_border'=>'border-gray-700', 'icon_bg_hover'=>'group-hover:bg-gray-300', 'icon_text_hover'=>'group-hover:text-gray-800', 'icon'=>'fa-building', 'label'=>'UMUM (LAB)'];
                        } elseif (in_array($type, ['assistant', 'asisten', 'jaga'])) {
                             $style = ['border_l'=>'border-l-blue-500', 'bg_date'=>'bg-blue-50', 'text_date'=>'text-blue-700', 'bg_hover'=>'group-hover:bg-blue-100', 'text_hover'=>'group-hover:text-blue-600', 'badge_bg'=>'bg-blue-100', 'badge_text'=>'text-blue-700', 'badge_border'=>'border-blue-200', 'icon_bg_hover'=>'group-hover:bg-blue-200', 'icon_text_hover'=>'group-hover:text-blue-600', 'icon'=>'fa-id-card-alt', 'label'=>'JAGA LAB'];
                        } elseif (in_array($type, ['piket', 'kebersihan'])) {
                             $style = ['border_l'=>'border-l-orange-500', 'bg_date'=>'bg-orange-50', 'text_date'=>'text-orange-700', 'bg_hover'=>'group-hover:bg-orange-100', 'text_hover'=>'group-hover:text-orange-600', 'badge_bg'=>'bg-orange-100', 'badge_text'=>'text-orange-700', 'badge_border'=>'border-orange-200', 'icon_bg_hover'=>'group-hover:bg-orange-200', 'icon_text_hover'=>'group-hover:text-orange-600', 'icon'=>'fa-broom', 'label'=>'PIKET'];
                        } elseif (in_array($type, ['class', 'kuliah', 'matkul', 'academic'])) {
                             $style = ['border_l'=>'border-l-green-500', 'bg_date'=>'bg-green-50', 'text_date'=>'text-green-700', 'bg_hover'=>'group-hover:bg-green-100', 'text_hover'=>'group-hover:text-green-600', 'badge_bg'=>'bg-green-100', 'badge_text'=>'text-green-700', 'badge_border'=>'border-green-200', 'icon_bg_hover'=>'group-hover:bg-green-200', 'icon_text_hover'=>'group-hover:text-green-600', 'icon'=>'fa-graduation-cap', 'label'=>'KULIAH'];
                        }

                        // [FIX LOGIC TANGGAL]
                        // Gunakan 'start_date' dari database. Jika kosong baru fallback ke hari ini.
                        $baseDate = !empty($sch['start_date']) ? $sch['start_date'] : date('Y-m-d');
                        
                        // Ambil nama hari. Cek dulu apakah 'day_of_week' ada isinya (angka 1-7 atau string)
                        if (!empty($sch['day_of_week']) && isset($daysIndo[$sch['day_of_week']])) {
                            $dayName = $daysIndo[$sch['day_of_week']];
                        } else {
                            // Jika kosong, ambil dari tanggal asli
                            $dayName = $daysIndo[date('l', strtotime($baseDate))] ?? 'Hari';
                        }

                        $startTime = date('H:i', strtotime($sch['start_time']));
                        $endTime = $sch['end_time'] ? date('H:i', strtotime($sch['end_time'])) : 'Selesai';
                        
                        $isRecurring = isset($sch['model_perulangan']) && strtolower($sch['model_perulangan']) == 'mingguan';
                        
                        $dateTitle = $isRecurring ? "Setiap " . $dayName : $dayName;
                        // Tampilkan tanggal lengkap jika bukan mingguan
                        $dateSubtitle = $isRecurring ? "Rutin Mingguan" : date('d M Y', strtotime($baseDate));
                    ?>
                
                <div class="flex flex-col md:flex-row items-stretch bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg transition-all duration-300 group border-l-4 <?= $style['border_l'] ?>">
                    
                    <div class="w-full md:w-40 <?= $style['bg_date'] ?> flex flex-col items-center justify-center p-4 border-b md:border-b-0 md:border-r border-gray-100 <?= $style['bg_hover'] ?> transition-colors">
                        <span class="text-sm font-bold <?= $style['text_date'] ?> uppercase tracking-wider text-center leading-tight">
                            <?= $dateTitle ?>
                        </span>
                        <span class="text-[10px] font-bold mt-1 bg-white px-2 py-0.5 rounded-full shadow-sm border border-gray-100 text-gray-500">
                            <?= $dateSubtitle ?>
                        </span>
                    </div>

                    <div class="flex-1 p-5 flex flex-col justify-center border-b md:border-b-0 md:border-r border-gray-100 relative">
                        <div class="absolute top-4 right-4 md:hidden">
                            <span class="text-[10px] <?= $style['badge_bg'] ?> <?= $style['badge_text'] ?> px-2 py-1 rounded-full font-bold border <?= $style['badge_border'] ?>">
                                <?= $style['label'] ?>
                            </span>
                        </div>

                        <div class="flex items-center gap-2 mb-1">
                            <h4 class="font-extrabold text-gray-800 text-lg <?= $style['text_hover'] ?> transition">
                                <?= htmlspecialchars($sch['title'], ENT_QUOTES, 'UTF-8') ?>
                            </h4>
                            <span class="hidden md:inline-block text-[10px] <?= $style['badge_bg'] ?> <?= $style['badge_text'] ?> px-2 py-0.5 rounded-full font-bold border <?= $style['badge_border'] ?> align-middle">
                                <i class="fas <?= $style['icon'] ?> mr-1"></i> <?= $style['label'] ?>
                            </span>
                        </div>

                        <?php if(!empty($sch['dosen'])): ?>
                            <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                                <i class="fas fa-chalkboard-teacher w-4 text-center"></i>
                                <span class="font-medium"><?= htmlspecialchars($sch['dosen'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if(!empty($sch['description'])): ?>
                            <div class="flex items-center gap-2 text-xs text-gray-400">
                                <i class="fas fa-info-circle w-4 text-center"></i>
                                <span class="italic"><?= htmlspecialchars($sch['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="w-full md:w-32 flex items-center justify-center p-4 border-b md:border-b-0 md:border-r border-gray-100 bg-white">
                        <div class="text-center">
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-2 text-gray-400 <?= $style['icon_bg_hover'] ?> <?= $style['icon_text_hover'] ?> transition">
                                <i class="far fa-clock"></i>
                            </div>
                            <span class="block font-mono text-sm font-bold text-gray-700">
                                <?= $startTime ?> - <?= $endTime ?>
                            </span>
                        </div>
                    </div>

                    <div class="w-full md:w-48 flex items-center justify-center p-4 bg-white">
                        <div class="text-center w-full">
                            <div class="py-2 px-4 bg-gray-50 text-gray-600 rounded-lg text-xs font-bold uppercase truncate border border-gray-200 group-hover:border-gray-300 transition">
                                <i class="fas fa-map-marker-alt mr-1 text-red-400"></i>
                                <?= htmlspecialchars($sch['location'] ?? 'Lab Komputer', ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <?php if(!empty($sch['kelas'])): ?>
                                <p class="text-[10px] text-gray-400 mt-2 font-mono bg-gray-50 inline-block px-2 py-0.5 rounded border border-gray-100">
                                    Kelas: <?= htmlspecialchars($sch['kelas'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
                <?php endforeach; ?>
                
            </div>
        <?php endif; ?>
    </div>
    <?php if(isset($pagination) && $pagination['total_pages'] > 1): ?>
        <div class="mt-8 pt-6 border-t border-dashed border-gray-100 flex justify-center">
            <nav class="inline-flex bg-white rounded-xl shadow-sm border border-gray-200 p-1 gap-1">
                <a href="?page=<?= max(1, $pagination['current'] - 1) ?>" 
                   class="w-10 h-10 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-50 hover:text-blue-600 transition <?= $pagination['current'] <= 1 ? 'pointer-events-none opacity-50' : '' ?>">
                    <i class="fas fa-chevron-left text-xs"></i>
                </a>

                <?php for($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <a href="?page=<?= $i ?>" 
                       class="w-10 h-10 flex items-center justify-center rounded-lg text-sm font-bold transition <?= $i == $pagination['current'] ? 'bg-blue-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-50 hover:text-blue-600' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <a href="?page=<?= min($pagination['total_pages'], $pagination['current'] + 1) ?>" 
                   class="w-10 h-10 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-50 hover:text-blue-600 transition <?= $pagination['current'] >= $pagination['total_pages'] ? 'pointer-events-none opacity-50' : '' ?>">
                    <i class="fas fa-chevron-right text-xs"></i>
                </a>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>