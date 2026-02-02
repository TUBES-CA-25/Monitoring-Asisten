<!DOCTYPE html>
<body class="bg-green-50 flex items-center justify-center h-screen text-center">
    <script src="https://cdn.tailwindcss.com"></script>
    <div class="bg-white p-10 rounded shadow-xl">
        <div class="text-5xl mb-4"><?= ($data['tipe'] == 'success') ? '✅' : '⚠️'; ?></div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2"><?= $data['pesan']; ?></h1>
        <a href="<?= BASEURL; ?>/dashboard" class="text-blue-500 underline">Kembali ke Dashboard</a>
    </div>
</body>