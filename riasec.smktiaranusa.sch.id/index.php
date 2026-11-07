<?php
// index.php - Landing Page Asesmen Minat Bakat
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asesmen Minat & Bakat | SMK Tiara Nusa</title>

    <!-- Favicon -->
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">

    <!-- Google Fonts untuk efek tebal dan fun ala Feastables -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS v4 via CDN -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>

    <!-- Custom CSS untuk menambahkan variabel Tailwind v4 (jika diperlukan) dan base font -->
    <style type="text/tailwindcss">
        @theme {
            --font-fredoka: 'Fredoka', sans-serif;
            --color-feast-blue: #0099ff;
            --color-feast-pink: #ff007f;
            --color-feast-yellow: #ffde00;
        }
        
        body {
            font-family: var(--font-fredoka);
            background-color: var(--color-feast-blue); /* Background biru cerah */
            color: #000;
        }

        /* Utility class untuk gaya kancing/kartu ala Feastables (Neo-brutalism) */
        .feast-shadow {
            box-shadow: 6px 6px 0px 0px rgba(0,0,0,1);
        }
        .feast-shadow-lg {
            box-shadow: 10px 10px 0px 0px rgba(0,0,0,1);
        }
        .hover-feast-press:active {
            box-shadow: 0px 0px 0px 0px rgba(0,0,0,1);
            transform: translate(6px, 6px);
        }
    </style>
</head>

<body class="min-h-screen overflow-x-hidden selection:bg-feast-pink selection:text-white">

    <!-- Navbar -->
    <nav class="flex justify-between items-center p-6 lg:px-12 max-w-7xl mx-auto">
        <div class="bg-white border-4 border-black rounded-full px-5 py-2 flex items-center gap-3 feast-shadow rotate-[-2deg]">
            <!-- Logo ditambahkan di sini -->
            <img src="assets/img/logo.png" alt="Logo SMK Tiara Nusa" class="w-10 h-10 object-contain" onerror="this.style.display='none'">
            <h1 class="text-2xl font-bold text-feast-pink tracking-wide">SMK Tiara Nusa</h1>
        </div>
        <div>
            <a href="gerbang-admin-z12" class="bg-white border-4 border-black rounded-full px-6 py-2 font-bold text-lg feast-shadow hover-feast-press transition-all duration-75 inline-block rotate-[2deg] animate-bounce">
                🔒 Login BK
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <main class="max-w-7xl mx-auto px-6 lg:px-12 py-12 lg:py-20">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

            <!-- Teks Hero -->
            <div class="space-y-8 z-10">
                <div class="inline-block bg-feast-yellow border-4 border-black rounded-full px-4 py-2 font-bold text-xl feast-shadow rotate-[-3deg]">
                    ✨ Kenali Potensimu!
                </div>
                <h2 class="text-5xl lg:text-7xl font-extrabold text-white leading-tight drop-shadow-[4px_4px_0_rgba(0,0,0,1)]" style="-webkit-text-stroke: 2px black;">
                    TEMUKAN MINAT & BAKATMU HARI INI!
                </h2>
                <p class="text-xl lg:text-2xl font-semibold bg-white p-4 border-4 border-black rounded-2xl feast-shadow inline-block">
                    Isi data dirimu dan jawab pertanyaannya dengan jujur
                </p>
                <br>
                <a href="mulai-bakat-x7f9" class="inline-block mt-4 bg-feast-pink text-white text-2xl font-bold px-12 py-6 border-4 border-black rounded-full feast-shadow-lg hover-feast-press transition-all duration-75 animate-bounce">
                    🚀 MULAI ASESMEN SEKARANG!
                </a>
            </div>

            <!-- Ilustrasi / Visual -->
            <div class="relative flex justify-center mt-10 lg:mt-0">
                <!-- Background Blob/Shape -->
                <div class="absolute bg-feast-yellow w-72 h-72 lg:w-96 lg:h-96 rounded-full border-8 border-black feast-shadow-lg -z-10"></div>

                <!-- Card Contoh (Bisa diganti gambar ilustrasi 3D) -->
                <div class="bg-white border-8 border-black rounded-3xl p-8 feast-shadow-lg rotate-[5deg] w-4/5 max-w-sm text-center">
                    <h3 class="text-4xl font-bold text-feast-blue mb-4">RIASEC</h3>
                    <div class="text-6xl mb-4">🧠</div>
                    <p class="text-xl font-bold">Rancang masa depan kariermu berdasarkan kepribadianmu!</p>
                </div>

                <!-- Ornamen kecil -->
                <div class="absolute top-0 right-10 text-6xl rotate-12 drop-shadow-[2px_2px_0_rgba(0,0,0,1)]">⭐</div>
                <div class="absolute bottom-10 left-0 text-6xl -rotate-12 drop-shadow-[2px_2px_0_rgba(0,0,0,1)]">🎯</div>
            </div>
        </div>
    </main>

    <!-- Info Section (3 Kolom) -->
    <section class="bg-feast-yellow border-y-8 border-black py-16">
        <div class="max-w-7xl mx-auto px-6 lg:px-12 text-center">
            <h2 class="text-4xl lg:text-5xl font-extrabold mb-12 drop-shadow-[3px_3px_0_rgba(0,0,0,1)] text-white" style="-webkit-text-stroke: 1.5px black;">APA SAJA YANG DIUKUR?</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Card 1 -->
                <div class="bg-white border-4 border-black p-8 rounded-3xl feast-shadow transform transition hover:-translate-y-2">
                    <div class="text-5xl mb-4">📚</div>
                    <h3 class="text-3xl font-bold text-feast-pink mb-2">MINAT</h3>
                    <p class="font-semibold text-lg">Dari Akademik hingga Teknologi Digital, ketahui hal apa yang paling membuatmu antusias!</p>
                </div>
                <!-- Card 2 -->
                <div class="bg-feast-blue border-4 border-black p-8 rounded-3xl feast-shadow transform transition hover:-translate-y-2">
                    <div class="text-5xl mb-4">💡</div>
                    <h3 class="text-3xl font-bold text-white mb-2" style="-webkit-text-stroke: 1px black;">BAKAT</h3>
                    <p class="font-semibold text-white text-lg">Eksplorasi kecerdasan majemukmu, apakah kamu kuat di visual, logika, atau bahasa?</p>
                </div>
                <!-- Card 3 -->
                <div class="bg-white border-4 border-black p-8 rounded-3xl feast-shadow transform transition hover:-translate-y-2">
                    <div class="text-5xl mb-4">💼</div>
                    <h3 class="text-3xl font-bold text-feast-pink mb-2">RIASEC</h3>
                    <p class="font-semibold text-lg">Temukan kecenderungan kariermu melalui tes Holland (Realistic, Investigative, dll)</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white border-t-8 border-black text-center py-8">
        <p class="font-bold text-xl">© <?php echo date('Y'); ?> SMK Tiara Nusa. Dibuat dengan 💖</p>
    </footer>

</body>

</html>