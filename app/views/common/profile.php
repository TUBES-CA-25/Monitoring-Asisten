<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-10">
    
    <div class="flex items-center gap-4 mb-2">
        <?php 
            $rolePath = strtolower(str_replace(' ', '', $_SESSION['role']));
            $backLink = BASE_URL . '/' . $rolePath . '/dashboard';
        ?>
        <a href="<?= $backLink ?>" class="w-10 h-10 flex items-center justify-center bg-white rounded-full shadow-sm text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-extrabold text-gray-800">Profil Saya</h1>
            <p class="text-gray-500 text-sm">Kelola informasi akun pribadi.</p>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 h-full min-h-[600px]">
        
        <div class="w-full lg:w-1/3 bg-white rounded-3xl shadow-sm border border-gray-200 p-8 flex flex-col items-center relative overflow-hidden">
            
            <div class="mt-2 mb-6 relative">
                <div class="w-40 h-40 rounded-full p-2 bg-gray-50 border border-gray-100 shadow-inner mx-auto relative">
                    <?php 
                        $photoName = $user['photo_profile'] ?? '';
                        $physicalPath = 'uploads/profile/' . $photoName;
                        if (!empty($photoName) && file_exists($physicalPath)) {
                            $avatarUrl = BASE_URL . '/uploads/profile/' . $photoName;
                        } else {
                            $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=random&bold=true";
                        }
                    ?>
                    <img src="<?= $avatarUrl ?>" alt="Profile" class="w-full h-full rounded-full object-cover shadow-lg">
                    
                    <?php if($user['role'] == 'User'): ?>
                        <div class="absolute bottom-2 right-2 w-6 h-6 rounded-full border-4 border-white 
                            <?= ($user['is_online'] > 0) ? 'bg-green-500 animate-pulse' : 'bg-gray-400' ?>" 
                            title="<?= ($user['is_online'] > 0) ? 'Online (Sedang di Lab)' : 'Offline' ?>">
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if($user['role'] == 'User'): ?>
                    <div class="mt-3 text-center">
                        <span class="px-3 py-1 <?= ($user['is_online'] > 0) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?> rounded-full text-[10px] font-bold uppercase tracking-wider border <?= ($user['is_online'] > 0) ? 'border-green-200' : 'border-gray-200' ?>">
                            <i class="fas fa-circle text-[8px] mr-1"></i> <?= ($user['is_online'] > 0) ? 'Sedang Bertugas' : 'Offline' ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="text-center mb-6">
                <h2 class="text-xl font-extrabold text-gray-800 leading-tight"><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="flex items-center justify-center gap-2 mt-2">
                    <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[10px] font-bold uppercase tracking-wider border border-blue-100">
                        <?= htmlspecialchars($user['position'] ?? $user['role'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
            </div>

            <div class="w-full space-y-3 text-left">
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 space-y-3">
                    <?php if($isUser): ?>
                    <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                        <span class="text-xs text-gray-400 font-bold uppercase">NIM</span>
                        <span class="font-mono text-sm font-bold text-gray-700"><?= htmlspecialchars($user['nim'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if(!$isUser): ?>
                    <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                        <span class="text-xs text-gray-400 font-bold uppercase">NIDN/NIP</span>
                        <span class="font-mono text-sm font-bold text-gray-700"><?= htmlspecialchars($user['nidn_nip'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-400 font-bold uppercase">Email</span>
                        <span class="text-sm font-medium text-gray-700 truncate max-w-[150px]" title="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-gray-200 space-y-3 shadow-sm">
                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Detail Informasi</h4>
                    
                    <div class="flex items-center gap-3 py-4 border-b border-gray-50">
                        <div class="w-8 h-8 rounded-lg bg-green-50 text-green-500 flex items-center justify-center text-xs"><i class="fas fa-phone"></i></div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold">No. Telepon</p>
                            <p class="text-xs font-bold text-gray-700 font-mono"><?= htmlspecialchars($user['no_telp'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>

                    <?php if($user['role'] == 'User'): ?>
                    <div class="flex items-center gap-3 py-4 border-b border-gray-50">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-500 flex items-center justify-center text-xs"><i class="fas fa-graduation-cap"></i></div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold">Kelas</p>
                            <p class="text-xs font-bold text-gray-700 font-mono"><?= htmlspecialchars($user['kelas'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 py-4 border-b border-gray-50">
                        <div class="w-8 h-8 rounded-lg bg-orange-50 text-orange-500 flex items-center justify-center text-xs"><i class="fas fa-building"></i></div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold">Laboratorium</p>
                            <p class="text-xs font-bold text-gray-700"><?= $user['lab_name'] ?? 'Umum / Semua Lab' ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="flex items-center gap-3 py-4">
                        <div class="w-8 h-8 rounded-lg bg-gray-50 text-gray-500 flex items-center justify-center text-xs"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold">Alamat</p>
                            <p class="text-xs font-medium text-gray-600 leading-tight"><?= htmlspecialchars($user['alamat'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-auto w-full pt-6">
                <?php 
                    // [LOGIKA BARU] Cek User & Kepala Lab yang sudah completed
                    $isLocked = false;
                    $targetRoles = ['User', 'Kepala Lab'];
                    
                    if(in_array($user['role'], $targetRoles) && isset($user['is_completed']) && $user['is_completed'] == 1) {
                        $isLocked = true;
                    }
                ?>

                <?php if($isLocked): ?>
                    <button onclick="showCustomAlert('locked', 'Profil Terkunci', 'Anda sudah melengkapi profil. Data tidak dapat diubah lagi. Hubungi Administrator jika terdapat kesalahan.')" 
                            class="block w-full py-3 font-bold rounded-xl transition text-sm uppercase tracking-wide text-center shadow-none bg-gray-200 text-gray-400 cursor-not-allowed">
                        <i class="fas fa-lock mr-2"></i> Profil Terkunci
                    </button>
                <?php else: ?>
                    <a href="<?= BASE_URL . "/" . strtolower(str_replace(' ', '', $user['role'])) . "/editProfile" ?>" 
                       class="block w-full py-3 font-bold rounded-xl transition text-sm uppercase tracking-wide text-center shadow-lg bg-gray-900 hover:bg-gray-800 text-white">
                        <i class="fas fa-edit mr-2"></i> Edit Profil
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="mt-6 bg-white p-6 rounded-3xl border border-gray-200 shadow-sm relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <i class="fab fa-google text-6xl text-blue-500"></i>
                </div>

                <!-- [BARU] Sebelumnya "flex-col md:flex-row" - breakpoint md
                     (viewport 768px) tidak memperhitungkan bahwa card ini ada
                     di kolom kiri yang hanya 1/3 lebar begitu breakpoint lg
                     (1024px) aktif. Akibatnya di layar ~1024-1300px, judul
                     "Integrasi Google Calendar" dan badge status dipaksa
                     sejajar dalam kolom sempit ~300-400px sehingga teks
                     terpotong/tumpang tindih. Dibuat selalu bertumpuk
                     vertikal (flex-col) - konsisten & aman di semua lebar
                     kolom, tidak bergantung pada breakpoint viewport. -->
                <div class="flex flex-col gap-4 relative z-10">
                    <div>
                        <h3 class="font-bold text-gray-800 flex items-center gap-2">
                            <i class="fab fa-google text-blue-500"></i> Integrasi Google Calendar
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            Hubungkan akun untuk sinkronisasi otomatis jadwal asisten, kuliah, dan piket ke kalender pribadi Anda.
                        </p>
                    </div>

                    <div class="w-full">
                        <?php if (!empty($is_google_connected) && $is_google_connected): ?>
                            <div class="flex items-center gap-3 py-2 px-5 bg-green-50 border border-green-200 rounded-xl">
                                <div class="w-8 h-8 shrink-0 bg-green-100 rounded-full flex items-center justify-center text-green-600 shadow-sm">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="min-w-0">
                                    <span class="block text-sm font-bold text-green-700">Terhubung</span>
                                    <span class="text-[10px] text-green-600 font-medium">Sinkronisasi Aktif</span>
                                </div>
                            </div>
                        <?php elseif (!empty($google_configured) && $google_configured): ?>
                            <a href="<?= BASE_URL ?>/google/connect" class="flex items-center justify-center gap-3 w-full py-3 px-6 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 hover:border-blue-300 hover:shadow-md transition-all group-btn">
                                <i class="fab fa-google text-lg text-blue-500 transition-transform group-hover:scale-110"></i>
                                <span class="text-sm font-bold text-gray-700 group-hover:text-blue-600">Hubungkan Sekarang</span>
                            </a>
                        <?php else: ?>
                            <div class="flex items-center gap-3 py-2 px-5 bg-gray-50 border border-gray-200 rounded-xl" title="Hubungi Administrator untuk mengaktifkan integrasi Google Calendar">
                                <div class="w-8 h-8 shrink-0 bg-gray-100 rounded-full flex items-center justify-center text-gray-400 shadow-sm">
                                    <i class="fas fa-plug"></i>
                                </div>
                                <div class="min-w-0">
                                    <span class="block text-sm font-bold text-gray-500">Belum Dikonfigurasi</span>
                                    <span class="text-[10px] text-gray-400 font-medium">Hubungi Administrator</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-2/3 flex flex-col gap-6">
            
            <?php if($user['role'] == 'User'): ?>
                <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-8 flex-1 flex flex-col">
                    <div class="flex justify-between items-start mb-8">
                        <h3 class="text-xl font-bold text-gray-700 uppercase tracking-wide">Statistik Kehadiran Anda</h3>
                    </div>
                    <div class="relative w-full bg-gray-50/50 rounded-2xl border border-gray-100 border-dashed p-4">
                        <div class="chart-box chart-box-lg">
                            <canvas id="userProfileChart"></canvas>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4 mt-8">
                        <div class="text-center p-4 bg-green-50 rounded-2xl border border-green-100">
                            <i class="fas fa-check-circle text-green-500 text-lg mb-1"></i>
                            <span class="block text-3xl font-extrabold text-green-600"><?= $stats['hadir'] ?></span>
                            <span class="text-xs font-bold text-green-800 uppercase">Hadir</span>
                        </div>
                        <div class="text-center p-4 bg-yellow-50 rounded-2xl border border-yellow-100">
                            <i class="fas fa-file-signature text-yellow-500 text-lg mb-1"></i>
                            <span class="block text-3xl font-extrabold text-yellow-600"><?= $stats['izin'] ?></span>
                            <span class="text-xs font-bold text-yellow-800 uppercase">Izin</span>
                        </div>
                        <div class="text-center p-4 bg-red-50 rounded-2xl border border-red-100">
                            <i class="fas fa-times-circle text-red-500 text-lg mb-1"></i>
                            <span class="block text-3xl font-extrabold text-red-600"><?= $stats['alpa'] ?></span>
                            <span class="text-xs font-bold text-red-800 uppercase">Tidak Hadir</span>
                        </div>
                    </div>

                    <?php
                        // [BARU] Peringatan presensi dinamis berdasarkan jumlah
                        // Alpha asisten (memakai $stats yang sudah dihitung
                        // controller lewat calculateRealAlpha - sumber yang
                        // sama dengan dashboard/admin, jadi selalu konsisten).
                        $alpaCount = (int) ($stats['alpa'] ?? 0);
                        if ($alpaCount >= 5) {
                            $warnCls  = 'bg-red-50 border-red-200 text-red-700';
                            $warnIcon = 'fa-triangle-exclamation text-red-500';
                            $warnTitle = 'Peringatan: Ketidakhadiran Cukup Tinggi';
                            $warnMsg  = "Anda tercatat <strong>$alpaCount kali</strong> tidak hadir tanpa keterangan (Alpha). Kondisi ini dapat memengaruhi penilaian kinerja Anda sebagai asisten — segera hubungi Kepala Lab jika ada kendala yang menyebabkan ketidakhadiran.";
                        } elseif ($alpaCount >= 1) {
                            $warnCls  = 'bg-amber-50 border-amber-200 text-amber-700';
                            $warnIcon = 'fa-circle-exclamation text-amber-500';
                            $warnTitle = 'Jaga Konsistensi Kehadiran';
                            $warnMsg  = "Anda tercatat <strong>$alpaCount kali</strong> tidak hadir tanpa keterangan (Alpha). Pastikan untuk selalu presensi atau mengajukan izin resmi agar tidak memengaruhi penilaian kinerja.";
                        } else {
                            $warnCls  = 'bg-green-50 border-green-200 text-green-700';
                            $warnIcon = 'fa-circle-check text-green-500';
                            $warnTitle = 'Kehadiran Anda Sangat Baik';
                            $warnMsg  = 'Belum ada catatan ketidakhadiran tanpa keterangan (Alpha). Pertahankan konsistensi ini!';
                        }
                    ?>
                    <div class="mt-6 border rounded-2xl p-4 flex items-start gap-3 <?= $warnCls ?>">
                        <i class="fas <?= $warnIcon ?> text-lg mt-0.5 shrink-0"></i>
                        <div>
                            <p class="font-bold text-sm"><?= $warnTitle ?></p>
                            <p class="text-xs mt-1 leading-relaxed"><?= $warnMsg ?></p>
                        </div>
                    </div>

                    <!-- [BARU] Aturan presensi - mengikuti aturan bisnis nyata
                         di AttendanceRuleService (jam masuk/pulang) & aturan
                         umum sistem (QR + verifikasi wajah, logbook, hari
                         kerja) supaya isinya akurat, bukan sekadar teks umum. -->
                    <div class="mt-4 bg-gray-50 border border-gray-100 rounded-2xl p-4">
                        <p class="text-xs font-extrabold text-gray-500 uppercase tracking-widest mb-3 flex items-center gap-2">
                            <i class="fas fa-book text-gray-400"></i> Aturan Presensi
                        </p>
                        <ul class="space-y-2 text-xs text-gray-600">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-clock text-blue-400 mt-0.5 shrink-0"></i>
                                <span>Jam masuk maksimal <strong>08:00 WITA</strong> (mengikuti jam jadwal jika lebih pagi) — lewat dari itu tercatat <strong>Terlambat</strong>.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-door-open text-amber-400 mt-0.5 shrink-0"></i>
                                <span>Jam pulang minimal <strong>16:00 WITA</strong> — pulang sebelum itu tercatat <strong>Pulang Cepat</strong>.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-qrcode text-indigo-400 mt-0.5 shrink-0"></i>
                                <span>Presensi wajib memakai QR Code + verifikasi wajah &amp; lokasi setiap sesi masuk/pulang.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-book-open text-emerald-400 mt-0.5 shrink-0"></i>
                                <span>Logbook aktivitas wajib diisi setiap hari kerja setelah presensi pulang.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-calendar-week text-purple-400 mt-0.5 shrink-0"></i>
                                <span>Hari kerja: <strong>Senin–Sabtu</strong>. Hari Minggu tidak dihitung sebagai ketidakhadiran.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-triangle-exclamation text-red-400 mt-0.5 shrink-0"></i>
                                <span>Tidak hadir tanpa izin yang disetujui akan tercatat <strong>Alpha</strong> dan memengaruhi penilaian kinerja.</span>
                            </li>
                        </ul>
                    </div>

                    <?php
                        // [BARU] Quote motivasi berganti tiap hari & berbeda
                        // per asisten - deterministik lewat hash(tanggal + id
                        // user), bukan random murni, supaya quote yang sama
                        // tetap tampil sepanjang hari itu (tidak berubah tiap
                        // reload) tapi berbeda dari asisten lain & berubah
                        // lagi besok.
                        $motivationalQuotes = [
                            'Konsistensi kecil yang dilakukan setiap hari mengalahkan usaha besar yang hanya sesekali.',
                            'Kehadiranmu hari ini adalah investasi untuk kepercayaan yang kamu bangun esok hari.',
                            'Disiplin adalah jembatan antara tujuan dan pencapaian.',
                            'Tidak perlu sempurna, cukup hadir dan berikan yang terbaik hari ini.',
                            'Setiap kali kamu datang tepat waktu, kamu sedang melatih dirimu menjadi orang yang bisa diandalkan.',
                            'Semangat kecil yang konsisten jauh lebih kuat daripada semangat besar yang cepat padam.',
                            'Kerja keras tanpa disiplin hanya menghasilkan kelelahan; disiplin tanpa kerja keras hanya menghasilkan angan-angan.',
                            'Hal-hal hebat tidak dilakukan secara impulsif, melainkan sebagai rangkaian hal kecil yang dilakukan bersama.',
                            'Jadilah asisten yang bukan hanya hadir secara fisik, tapi juga hadir dengan kontribusi nyata.',
                            'Reputasi dibangun dari kebiasaan kecil yang dilakukan berulang kali, termasuk kebiasaan tepat waktu.',
                            'Hari ini adalah kesempatan baru untuk memperbaiki catatan kehadiranmu kemarin.',
                            'Tanggung jawab yang kamu jaga hari ini adalah fondasi kepercayaan yang kamu tuai nanti.',
                            'Bukan seberapa besar langkahmu, tapi seberapa konsisten kamu melangkah.',
                            'Waktu yang kamu hargai hari ini akan menghargaimu di masa depan.',
                            'Menjadi andal dimulai dari hal sederhana: hadir, tepat waktu, dan bertanggung jawab.',
                            'Setiap presensi tepat waktu adalah bukti kecil dari karakter besar yang sedang kamu bangun.',
                            'Kesuksesan adalah jumlah dari usaha-usaha kecil yang diulang hari demi hari.',
                            'Lab ini menjadi lebih baik karena kontribusi dan kehadiranmu yang konsisten.',
                            'Jangan menunggu motivasi untuk hadir — hadirlah, dan motivasi akan mengikuti.',
                            'Orang yang bisa diandalkan bukan yang paling pintar, tapi yang paling konsisten.',
                        ];
                        $quoteSeed  = crc32(date('Y-m-d') . '-' . ($user['id'] ?? 0));
                        $todayQuote = $motivationalQuotes[$quoteSeed % count($motivationalQuotes)];
                    ?>
                    <div class="mt-4 bg-gradient-to-r from-blue-600 to-cyan-500 circuit-pattern relative overflow-hidden rounded-2xl p-5 text-white">
                        <i class="fas fa-quote-left text-2xl text-white/30 mb-1"></i>
                        <p class="text-sm font-semibold leading-relaxed relative z-10"><?= htmlspecialchars($todayQuote, ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-[10px] text-blue-100 mt-3 uppercase tracking-widest font-bold relative z-10">Motivasi Hari Ini · <?= date('d F Y') ?></p>
                    </div>
                </div>

            <?php else: ?>
                <div class="flex justify-between items-center bg-white p-4 rounded-2xl shadow-sm border border-gray-200">
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">Panel Informasi Lab</h3>
                        <p class="text-xs text-gray-500">Demografi asisten dan jadwal operasional umum.</p>
                    </div>
                    <div class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-lg text-xs font-bold">
                        Total Asisten: <?= $total_managed_users ?>
                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col min-h-[400px]">
                    <div class="flex flex-col sm:flex-row justify-between items-center p-6 border-b border-gray-100 gap-4">
                        <div class="flex items-center gap-2">
                            <i id="widgetIcon" class="fas fa-chart-pie text-indigo-500 text-xl"></i>
                            <h4 id="widgetTitle" class="text-sm font-bold text-gray-700 uppercase tracking-widest">Demografi</h4>
                        </div>
                        
                        <div class="bg-gray-100 p-1 rounded-xl flex items-center shadow-inner">
                            <button onclick="switchAdminTab('demo')" id="tab-demo" class="tab-btn active px-4 py-1.5 rounded-lg text-xs font-bold flex items-center gap-2">
                                <i class="fas fa-chart-pie"></i> Demografi
                            </button>
                            <button onclick="switchAdminTab('schedule')" id="tab-schedule" class="tab-btn inactive px-4 py-1.5 rounded-lg text-xs font-bold flex items-center gap-2">
                                <i class="fas fa-calendar-alt"></i> Jadwal Terdekat
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 relative p-6 overflow-hidden">
                        
                        <div id="view-demo" class="w-full h-full flex flex-col transition-opacity duration-300">
                            <div class="flex justify-between items-center mb-4">
                                <p class="text-xs text-gray-400">Analisis sebaran asisten berdasarkan kategori.</p>
                                <div class="flex gap-2">
                                    <div class="relative">
                                        <select id="demographicFilter" onchange="updateDemographicChart()" class="bg-gray-50 border-none text-gray-600 text-xs font-bold rounded-lg py-3.5 pl-3 pr-8 focus:ring-2 focus:ring-blue-200 cursor-pointer outline-none">
                                            <option value="gender">Gender</option>
                                            <option value="class">Kelas</option>
                                            <option value="interest">Minat</option>
                                        </select>
                                        <i class="absolute right-3 top-2.5 text-gray-400 text-[10px] pointer-events-none"></i>
                                    </div>
                                    <div class="flex bg-gray-50 p-1 rounded-lg">
                                        <button onclick="setChartType('bar')" class="p-1.5 rounded hover:bg-white shadow-sm transition text-gray-500 hover:text-indigo-600"><i class="fas fa-chart-bar text-xs"></i></button>
                                        <button onclick="setChartType('line')" class="p-1.5 rounded hover:bg-white shadow-sm transition text-gray-500 hover:text-indigo-600"><i class="fas fa-chart-line text-xs"></i></button>
                                        <button onclick="setChartType('pie')" class="p-1.5 rounded hover:bg-white shadow-sm transition text-gray-500 hover:text-indigo-600"><i class="fas fa-chart-pie text-xs"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="chart-box chart-box-lg">
                                <canvas id="demographicChart"></canvas>
                            </div>
                            <div id="demographicLegend" class="flex flex-wrap gap-2 mt-4"></div>
                        </div>

                        <div id="view-schedule" class="w-full h-full hidden opacity-0 transition-opacity duration-300">
                            <?php if (empty($upcoming_schedules)): ?>
                                <div class="w-full h-full flex flex-col items-center justify-center empty-schedule-box text-center p-6 min-h-[300px]">
                                    <div class="w-20 h-20 bg-indigo-50 rounded-2xl shadow-sm flex items-center justify-center text-indigo-300 text-4xl mb-4 border border-indigo-100">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <h5 class="text-indigo-900 font-bold text-base">Tidak Ada Jadwal Umum</h5>
                                    <p class="text-indigo-400 text-sm mt-1">Laboratorium sedang kosong untuk kegiatan umum.</p>
                                </div>
                            <?php else: ?>
                                <div class="carousel-3d-container" id="scheduleCarousel">
                                    <?php foreach($upcoming_schedules as $idx => $sch): 
                                        $judul = $sch['nama_kegiatan'] ?? $sch['title'] ?? 'Kegiatan'; 
                                        $tgl = isset($sch['tanggal']) ? date('d M Y', strtotime($sch['tanggal'])) : ($sch['display_date'] ?? '-');
                                        $jam = isset($sch['jam_mulai']) ? substr($sch['jam_mulai'], 0, 5) : ($sch['start_time'] ?? '00:00');
                                        $lok = $sch['lokasi'] ?? $sch['location'] ?? 'Lab Terpadu';
                                    ?>
                                    <div class="carousel-item-3d <?= $idx == 0 ? 'active' : ($idx == 1 ? 'next-1' : ($idx == 2 ? 'prev-1' : 'hidden-item')) ?>" data-index="<?= $idx ?>">
                                        <div class="flex justify-between items-start w-full">
                                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-indigo-100 text-indigo-600 shadow-sm">
                                                <i class="fas fa-users text-lg"></i>
                                            </div>
                                            <span class="px-3 py-1 bg-gray-50 border border-gray-100 rounded-lg text-[10px] font-bold uppercase text-gray-500">UMUM</span>
                                        </div>
                                        <div class="w-full mt-4">
                                            <h4 class="font-bold text-gray-800 text-lg leading-snug line-clamp-2 mb-2" title="<?= $judul ?>">
                                                <?= $judul ?>
                                            </h4>
                                            <p class="text-xs text-indigo-500 font-bold flex items-center gap-2 bg-indigo-50 px-2 py-1.5 rounded-lg w-max">
                                                <i class="far fa-calendar"></i> <?= $tgl ?>
                                            </p>
                                        </div>
                                        <div class="w-full pt-4 border-t border-dashed border-gray-200 flex justify-between items-center mt-auto">
                                            <div class="text-sm font-mono font-bold text-gray-700 bg-gray-50 px-2 py-1 rounded border border-gray-100">
                                                <?= $jam ?>
                                            </div>
                                            <div class="flex items-center gap-1 text-[10px] text-gray-400 font-medium">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span class="truncate max-w-[80px]"><?= $lok ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-center mt-4 text-xs text-gray-400 italic">
                                    Geser otomatis setiap 3 detik
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col flex-1 overflow-hidden min-h-[400px]">
                    <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                        <h4 class="text-sm font-bold text-gray-700 uppercase tracking-widest">
                            <i class="fas fa-trophy text-yellow-500 mr-2"></i>Peringkat Asisten
                        </h4>
                        <div class="relative">
                            <select id="rankingFilter" onchange="renderRanking()" class="pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-100 shadow-sm cursor-pointer appearance-none">
                                <option value="online">🟢 Sedang Online</option>
                                <option value="rajin">🔥 Paling Rajin (Hadir)</option>
                                <option value="jarang">😴 Jarang Hadir</option>
                                <option value="cepat">⚡ Datang Tercepat</option>
                                <option value="terlambat">🐌 Datang Terlambat</option>
                                <option value="sering_izin">🤒 Sering Izin</option>
                                <option value="logbook_lengkap">📝 Logbook Terlengkap</option>
                                <option value="logbook_singkat">📉 Logbook Singkat</option>
                                <option value="sibuk">📅 Paling Sibuk (Jadwal)</option>
                                <option value="santai">🏖️ Paling Santai</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-3 top-2.5 text-gray-400 text-xs pointer-events-none"></i>
                        </div>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-0 custom-scrollbar" id="rankingList"></div>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<div id="customAlertModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="alertBackdrop"></div>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative z-10 overflow-hidden transform scale-90 opacity-0 transition-all duration-300 flex flex-col items-center p-6 text-center" id="alertContent">
        <div id="alertIconBg" class="w-20 h-20 rounded-full flex items-center justify-center mb-4 transition-colors">
            <i id="alertIcon" class="fas text-4xl"></i>
        </div>
        <h3 id="alertTitle" class="text-2xl font-extrabold text-gray-800 mb-2"></h3>
        <p id="alertMessage" class="text-sm text-gray-500 mb-6 px-2 leading-relaxed"></p>
        <button onclick="closeCustomAlert()" class="w-full py-3.5 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02] active:scale-95" id="alertBtn">OK</button>
    </div>
</div>

<?php if (!empty($_SESSION['google_modal'])):
    $googleModal = $_SESSION['google_modal'];
    unset($_SESSION['google_modal']);
?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        showCustomAlert(
            <?= json_encode($googleModal['type'] ?? 'success') ?>,
            <?= json_encode($googleModal['title'] ?? '') ?>,
            <?= json_encode($googleModal['message'] ?? '') ?>
        );
    });
</script>
<?php endif; ?>

