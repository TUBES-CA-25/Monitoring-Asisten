<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICLABS System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { ic_blue: '#3B82F6', ic_cyan: '#06B6D4', ic_dark: '#1E293B' } } } }
    </script>
</head>
<body class="bg-gray-50 font-sans text-gray-800 antialiased overflow-hidden">
    <div class="flex h-screen">
        <aside class="w-64 bg-white shadow-xl hidden md:flex flex-col justify-between z-20">
            <div>
                <div class="h-16 flex items-center justify-center bg-ic_blue text-white text-2xl font-bold tracking-widest">
                    <i class="fas fa-microchip mr-2"></i> ICLABS
                </div>
                <nav class="mt-8 px-4 space-y-2">
                    <a href="<?= BASEURL; ?>/dashboard" class="flex items-center p-3 text-gray-700 hover:bg-blue-50 hover:text-ic_blue rounded-lg transition"><i class="fas fa-home w-6"></i> Dashboard</a>
                    <?php if($_SESSION['role_id'] == 1): ?>
                        <a href="#" class="flex items-center p-3 text-gray-700 hover:bg-blue-50 hover:text-ic_blue rounded-lg transition"><i class="fas fa-flask w-6"></i> Labs</a>
                    <?php endif; ?>
                </nav>
            </div>
            <div class="p-4 border-t">
                <a href="<?= BASEURL; ?>/auth/logout" class="flex items-center p-3 text-red-500 hover:bg-red-50 rounded-lg transition"><i class="fas fa-sign-out-alt w-6"></i> Logout</a>
            </div>
        </aside>

        <div class="flex-1 flex flex-col h-screen overflow-hidden">
            <header class="flex justify-between items-center py-4 px-6 bg-white shadow-sm h-16">
                <h2 class="text-xl font-semibold text-gray-700"><?= $data['judul']; ?></h2>
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-bold"><?= $_SESSION['user_name']; ?></span>
                    <img src="https://ui-avatars.com/api/?name=<?= $_SESSION['user_name']; ?>" class="w-8 h-8 rounded-full">
                </div>
            </header>
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
                <?php require_once '../app/views/' . $view_content . '.php'; ?>
            </main>
        </div>
    </div>
</body>
</html>