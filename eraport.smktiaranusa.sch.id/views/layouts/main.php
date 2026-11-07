<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'e-Raport SMK Tiara Nusa' ?></title>
    <!-- Favicon -->
    <link rel="icon" href="/assets/img/favicon.ico" type="image/x-icon">
    <!-- Tailwind CSS v3 via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Flowbite v2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-gray-50 font-sans antialiased" x-data="{ sidebarOpen: false }">

    <div class="flex h-screen overflow-hidden">

        <!-- OVERLAY MOBILE -->
        <!-- Bagian ini memberikan efek gelap di latar belakang dan menutup sidebar saat diklik -->
        <div x-show="sidebarOpen"
            x-transition.opacity
            @click="sidebarOpen = false"
            style="display: none;"
            class="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-sm lg:hidden cursor-pointer">
        </div>

        <!-- SIDEBAR -->
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- MAIN CONTAINER -->
        <div class="flex flex-col flex-1 h-full overflow-hidden">

            <!-- TOPBAR -->
            <?php include __DIR__ . '/topbar.php'; ?>

            <!-- CONTENT -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
                <?= $content ?? '' ?>
            </main>

            <!-- FOOTER -->
            <?php include __DIR__ . '/footer.php'; ?>
        </div>
    </div>

    <!-- Flowbite JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</body>

</html>