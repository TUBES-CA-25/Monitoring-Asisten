<div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-lg">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2">Konfigurasi Sistem</h2>

    <div class="mb-8">
        <h3 class="text-lg font-bold text-gray-700 mb-4">Status Token QR Code</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-bold text-blue-800">QR Check-In (Masuk)</span>
                    <span class="bg-blue-200 text-blue-800 text-xs px-2 py-1 rounded">Reset tiap 3 Jam</span>
                </div>
                <p class="text-sm text-gray-600">Token Aktif saat ini:</p>
                <code class="block bg-white border p-2 rounded mt-1 text-xs break-all"><?= $tokenIn ?? 'Belum digenerate' ?></code>
                <p class="text-xs text-gray-500 mt-2 italic">*Token ini akan berubah otomatis jika admin membuka dashboard monitoring.</p>
            </div>

            <div class="bg-orange-50 border border-orange-200 p-4 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-bold text-orange-800">QR Check-Out (Pulang)</span>
                    <span class="bg-orange-200 text-orange-800 text-xs px-2 py-1 rounded">Reset tiap 24 Jam</span>
                </div>
                <p class="text-sm text-gray-600">Token Aktif saat ini:</p>
                <code class="block bg-white border p-2 rounded mt-1 text-xs break-all"><?= $tokenOut ?? 'Belum digenerate' ?></code>
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-lg font-bold text-gray-700 mb-4">Pengaturan Umum</h3>
        <form onsubmit="alert('Fitur simpan konfigurasi belum tersedia di database (Tabel Config belum dibuat).'); return false;">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Aplikasi</label>
                    <input type="text" value="ICLABS System" class="mt-1 block w-full border border-gray-300 rounded-md p-2" disabled>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Versi Sistem</label>
                    <input type="text" value="v2.0.1" class="mt-1 block w-full border border-gray-300 rounded-md p-2" disabled>
                </div>
            </div>
            <div class="mt-4 text-right">
                <button type="submit" class="bg-gray-400 text-white px-4 py-2 rounded cursor-not-allowed">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>