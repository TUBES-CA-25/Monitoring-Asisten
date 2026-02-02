<!DOCTYPE html>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <script src="https://cdn.tailwindcss.com"></script>
    <div class="bg-white p-8 rounded shadow-lg w-full max-w-lg">
        <h2 class="text-xl font-bold mb-4">Isi Logbook Pulang</h2>
        <form action="<?= BASEURL; ?>/presensi/submit_checkout" method="POST">
            <label class="block mb-2">Kegiatan Hari Ini</label>
            <textarea name="kegiatan" class="w-full border p-2 rounded mb-4" rows="4" required></textarea>
            <label class="block mb-2">Keterangan (Opsional)</label>
            <input type="text" name="keterangan" class="w-full border p-2 rounded mb-6">
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 rounded">Kirim & Pulang</button>
        </form>
    </div>
</body>