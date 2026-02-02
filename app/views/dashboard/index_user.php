<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden relative group">
            <div class="h-24 bg-gradient-to-r from-ic_blue to-ic_cyan"></div>
            <div class="px-6 pb-6 relative">
                <div class="relative -mt-12 mb-4">
                    <img src="https://ui-avatars.com/api/?name=<?= $data['profile']['name']; ?>&background=random&size=128" 
                         class="w-24 h-24 rounded-full border-4 border-white shadow-md mx-auto">
                    <button class="absolute bottom-0 right-1/2 translate-x-10 bg-gray-800 text-white p-1 rounded-full text-xs hover:bg-gray-600" title="Ganti Foto">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                
                <div class="text-center">
                    <h3 class="text-xl font-bold text-gray-800"><?= $data['profile']['name']; ?></h3>
                    <p class="text-sm text-gray-500 mb-2"><?= $data['profile']['email']; ?></p>
                    <span class="bg-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full font-bold">Asisten Laboratorium</span>
                </div>

                <div class="mt-6 border-t pt-4">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">Peminatan</h4>
                    <div class="flex flex-wrap gap-2">
                        <?php 
                        $minat = explode(',', $data['profile']['specialization']);
                        foreach($minat as $m): 
                        ?>
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded border"><?= trim($m); ?></span>
                        <?php endforeach; ?>
                        <button class="text-xs border border-dashed border-gray-300 text-gray-400 px-2 py-1 rounded hover:border-ic_blue hover:text-ic_blue">+ Edit</button>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">Bio</h4>
                    <p class="text-sm text-gray-600 italic">"<?= $data['profile']['bio'] ?? '-'; ?>"</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-400">
            <h3 class="font-bold text-gray-800 mb-2 flex items-center">
                <i class="fas fa-file-medical mr-2 text-yellow-500"></i> Pengajuan Izin/Sakit
            </h3>
            <p class="text-xs text-gray-500 mb-4">Upload bukti surat dokter atau keterangan izin.</p>
            
            <form action="<?= BASEURL; ?>/presensi/izin" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <select name="jenis" class="w-full text-sm border rounded p-2 bg-gray-50">
                        <option value="Sakit">Sakit</option>
                        <option value="Izin">Izin</option>
                    </select>
                </div>
                <div class="mb-3">
                    <input type="file" name="bukti" class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required>
                </div>
                <div class="mb-3">
                    <textarea name="keterangan" rows="2" class="w-full text-sm border rounded p-2" placeholder="Keterangan singkat..."></textarea>
                </div>
                <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-bold py-2 rounded transition">Kirim Pengajuan</button>
            </form>
        </div>
    </div>

    <div class="lg:col-span-2 space-y-6">
        
        <div class="bg-gradient-to-r from-ic_blue to-blue-600 rounded-xl shadow-lg p-6 text-white flex flex-col md:flex-row justify-between items-center relative overflow-hidden">
            <div class="absolute right-0 top-0 opacity-10 transform translate-x-10 -translate-y-10">
                <i class="fas fa-qrcode fa-10x"></i>
            </div>
            
            <div class="relative z-10 mb-4 md:mb-0">
                <h2 class="text-2xl font-bold">Halo, Asisten!</h2>
                <p class="text-blue-100">Jangan lupa presensi sebelum masuk lab.</p>
            </div>
            
            <div class="relative z-10 flex space-x-3">
                <button onclick="alert('Silakan arahkan kamera ke layar Monitor Admin/Super Admin')" class="bg-white text-ic_blue hover:bg-gray-100 font-bold py-2 px-6 rounded-lg shadow-md transition flex items-center">
                    <i class="fas fa-camera mr-2"></i> Scan Presensi
                </button>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="font-bold text-gray-800 mb-4 flex items-center">
                <i class="far fa-calendar-alt mr-2 text-red-500"></i> Jadwal Praktikum & Kuliah
            </h3>
            <div class="rounded-lg overflow-hidden border">
                <iframe src="https://calendar.google.com/calendar/embed?height=400&wkst=1&bgcolor=%23ffffff&ctz=Asia%2FJakarta&src=ZW4uaW5kb25lc2lhbiNob2xpZGF5QGdyb3VwLnYuY2FsZW5kYXIuZ29vZ2xlLmNvbQ&color=%230B8043" 
                        style="border:0" width="100%" height="400" frameborder="0" scrolling="no"></iframe>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-gray-800">Riwayat Presensi (5 Terakhir)</h3>
                <a href="#" class="text-xs text-ic_blue hover:underline">Lihat Semua</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase font-bold text-xs">
                        <tr>
                            <th class="p-3">Tanggal</th>
                            <th class="p-3">Jam Masuk</th>
                            <th class="p-3">Jam Pulang</th>
                            <th class="p-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach($data['history'] as $h): ?>
                        <tr>
                            <td class="p-3 font-medium"><?= date('d M Y', strtotime($h['date'])); ?></td>
                            <td class="p-3"><?= $h['check_in'] ? date('H:i', strtotime($h['check_in'])) : '-'; ?></td>
                            <td class="p-3"><?= $h['check_out'] ? date('H:i', strtotime($h['check_out'])) : '-'; ?></td>
                            <td class="p-3">
                                <?php if($h['status'] == 'Hadir'): ?>
                                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">Hadir</span>
                                <?php else: ?>
                                    <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs font-bold"><?= $h['status']; ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($data['history'])): ?>
                            <tr><td colspan="4" class="p-4 text-center text-gray-500">Belum ada data presensi.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>