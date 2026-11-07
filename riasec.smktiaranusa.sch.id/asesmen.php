<?php
// asesmen.php

// Panggil sesi terlebih dahulu untuk fitur CSRF Token
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

// 0. Ambil Data Master Siswa untuk Pencarian (Ambil ID, Nama, Kelas, JK)
try {
    $stmtSiswa = $pdo->query("SELECT id, nama, kelas, jenis_kelamin FROM master_siswa ORDER BY nama ASC");
    $list_master_siswa = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $list_master_siswa = [];
}

// 1. Data Pertanyaan Aspek Minat (40 Soal)
$soal_minat = [
    'Akademik' => [
        1 => 'Saya senang membaca buku.',
        2 => 'Saya suka mempelajari hal baru.',
        3 => 'Saya senang mengerjakan soal yang menantang.',
        4 => 'Saya tertarik mengikuti lomba akademik.',
        5 => 'Saya ingin melanjutkan pendidikan setinggi mungkin.'
    ],
    'Sains dan Teknologi' => [
        6 => 'Saya suka melakukan eksperimen.',
        7 => 'Saya tertarik pada teknologi.',
        8 => 'Saya senang belajar matematika dan IPA.',
        9 => 'Saya tertarik robotika/coding.',
        10 => 'Saya ingin menciptakan inovasi baru.'
    ],
    'Seni' => [
        11 => 'Saya senang menggambar.',
        12 => 'Saya suka bernyanyi.',
        13 => 'Saya tertarik pada desain.',
        14 => 'Saya menikmati fotografi.',
        15 => 'Saya suka tampil di depan umum.'
    ],
    'Bahasa' => [
        16 => 'Saya senang menulis.',
        17 => 'Saya suka membaca cerita.',
        18 => 'Saya tertarik belajar bahasa asing.',
        19 => 'Saya senang berbicara di depan umum.',
        20 => 'Saya suka membuat cerita.'
    ],
    'Sosial' => [
        21 => 'Saya senang membantu orang lain.',
        22 => 'Saya mudah bergaul.',
        23 => 'Saya suka bekerja dalam tim.',
        24 => 'Saya tertarik menjadi guru/konselor.',
        25 => 'Saya senang mengikuti kegiatan sosial.'
    ],
    'Olahraga' => [
        26 => 'Saya senang berolahraga.',
        27 => 'Saya suka aktivitas luar ruangan.',
        28 => 'Saya menikmati permainan tim.',
        29 => 'Saya menjaga kebugaran tubuh.',
        30 => 'Saya ingin berprestasi di bidang olahraga.'
    ],
    'Kewirausahaan' => [
        31 => 'Saya suka berjualan.',
        32 => 'Saya ingin memiliki usaha sendiri.',
        33 => 'Saya tertarik bisnis online.',
        34 => 'Saya suka mencari peluang.',
        35 => 'Saya senang membuat produk yang kreatif.'
    ],
    'Teknologi Digital' => [
        36 => 'Saya suka menggunakan komputer.',
        37 => 'Saya tertarik media sosial.',
        38 => 'Saya suka membuat konten digital.',
        39 => 'Saya tertarik desain grafis/editing.',
        40 => 'Saya ingin menguasai teknologi masa depan.'
    ]
];

// 2. Data Pertanyaan Aspek Bakat (8 Soal)
$soal_bakat = [
    1 => ['Linguistik (Bahasa)', 'Saya mudah mengungkapkan ide melalui tulisan atau lisan.'],
    2 => ['Logika Matematika', 'Saya suka berhitung dan memecahkan masalah logika.'],
    3 => ['Visual Spasial', 'Saya mudah memahami gambar dan desain.'],
    4 => ['Kinestetik', 'Saya aktif bergerak dan belajar melalui praktik.'],
    5 => ['Musik', 'Saya peka terhadap nada dan irama.'],
    6 => ['Interpersonal', 'Saya mudah memahami perasaan orang lain.'],
    7 => ['Intrapersonal', 'Saya mengenal diri saya dengan baik.'],
    8 => ['Naturalis', 'Saya menyukai tumbuhan, hewan, dan alam.']
];

// 3. Data Pertanyaan RIASEC (36 Soal)
$soal_riasec = [
    'R - Realistic' => [
        1 => 'Saya senang membuat atau memperbaiki sesuatu dengan tangan.',
        2 => 'Saya tertarik menggunakan alat atau mesin.',
        3 => 'Saya senang melakukan kegiatan praktik daripada hanya membaca teori.',
        4 => 'Saya tertarik dengan pekerjaan yang dilakukan di luar ruangan.',
        5 => 'Saya senang membuat benda atau produk yang dapat digunakan.',
        6 => 'Saya tertarik mempelajari cara kerja kendaraan, mesin, atau peralatan.'
    ],
    'I - Investigative' => [
        7 => 'Saya senang mencari tahu mengapa sesuatu bisa terjadi.',
        8 => 'Saya suka memecahkan soal atau masalah yang menantang.',
        9 => 'Saya tertarik melakukan eksperimen atau penelitian sederhana.',
        10 => 'Saya senang mengamati, membandingkan, dan menemukan pola.',
        11 => 'Saya tertarik mempelajari sains, teknologi, atau ilmu pengetahuan.',
        12 => 'Saya senang mencari informasi sebelum mengambil kesimpulan.'
    ],
    'A - Artistic' => [
        13 => 'Saya senang menggambar, melukis, atau membuat karya visual.',
        14 => 'Saya tertarik membuat desain atau karya kreatif.',
        15 => 'Saya senang menulis cerita, puisi, atau membuat tulisan kreatif.',
        16 => 'Saya menikmati kegiatan musik, bernyanyi, atau memainkan alat musik.',
        17 => 'Saya senang menemukan cara yang berbeda dan unik dalam mengerjakan sesuatu.',
        18 => 'Saya tertarik pada fotografi, video, animasi, atau desain digital.'
    ],
    'S - Social' => [
        19 => 'Saya senang membantu teman yang sedang mengalami kesulitan.',
        20 => 'Saya senang berdiskusi dan berbicara dengan orang lain.',
        21 => 'Saya tertarik mengajar atau menjelaskan sesuatu kepada orang lain.',
        22 => 'Saya senang bekerja dalam kelompok.',
        23 => 'Saya mudah memahami perasaan atau keadaan orang lain.',
        24 => 'Saya tertarik melakukan kegiatan yang bermanfaat bagi masyarakat.'
    ],
    'E - Enterprising' => [
        25 => 'Saya senang menjadi pemimpin dalam kegiatan kelompok.',
        26 => 'Saya berani menyampaikan ide atau pendapat di depan orang lain.',
        27 => 'Saya tertarik membuat atau menjalankan usaha.',
        28 => 'Saya senang meyakinkan orang lain terhadap ide yang saya miliki.',
        29 => 'Saya senang mengambil inisiatif ketika melihat suatu masalah.',
        30 => 'Saya tertarik mempelajari pemasaran, bisnis, atau cara menjual produk.'
    ],
    'C - Conventional' => [
        31 => 'Saya senang menyusun data atau informasi agar lebih rapi.',
        32 => 'Saya senang membuat daftar dan membuat jadwal kegiatan.',
        33 => 'Saya teliti ketika mengerjakan tugas yang membutuhkan ketepatan.',
        34 => 'Saya senang bekerja menggunakan komputer untuk mengolah data atau dokumen.',
        35 => 'Saya nyaman mengikuti aturan dan langkah kerja yang jelas.',
        36 => 'Saya senang mengarsipkan, mencatat atau mengelompokkan informasi.'
    ]
];

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Isi Asesmen | SMK Tiara Nusa</title>

    <!-- Favicon -->
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS v4 -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>

    <!-- Alpine.js & htmx -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>

    <style type="text/tailwindcss">
        @theme {
            --font-fredoka: 'Fredoka', sans-serif;
            --color-feast-blue: #0099ff;
            --color-feast-pink: #ff007f;
            --color-feast-yellow: #ffde00;
        }
        body { font-family: var(--font-fredoka); background-color: var(--color-feast-yellow); color: #000; }
        .feast-shadow { box-shadow: 6px 6px 0px 0px rgba(0,0,0,1); }
        .hover-feast-press:active { box-shadow: 0px 0px 0px 0px rgba(0,0,0,1); transform: translate(6px, 6px); }
        
        /* Custom Radio Button Style */
        input[type="radio"] { accent-color: var(--color-feast-pink); width: 1.5rem; height: 1.5rem; }
    </style>
</head>

<body class="min-h-screen p-4 lg:p-8">

    <!-- Header dengan Logo -->
    <header class="max-w-4xl mx-auto flex items-center justify-between bg-white border-4 border-black p-4 rounded-2xl feast-shadow mb-8 rotate-[-1deg]">
        <div class="flex items-center gap-4">
            <img src="assets/img/logo.png" alt="Logo SMK" class="w-12 h-12 object-contain" onerror="this.style.display='none'">
            <h1 class="text-2xl font-bold text-feast-blue">Asesmen Minat Bakat</h1>
        </div>
        <a href="/" class="font-bold border-2 border-black px-4 py-2 rounded-xl hover:bg-gray-100 transition">Kembali</a>
    </header>

    <!-- Container Form dengan Alpine.js Data Component (asesmenForm) -->
    <main class="max-w-4xl mx-auto bg-white border-8 border-black rounded-3xl p-6 lg:p-10 feast-shadow" x-data="asesmenForm">

        <!-- Area Hasil htmx -->
        <div id="form-result"></div>

        <!-- Form Pengisian -->
        <form hx-post="proses/simpan_asesmen.php" hx-swap="innerHTML" hx-target="#form-result" class="space-y-8" id="asesmenFormElement">

            <!-- INPUT CSRF TOKEN -->
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">

            <!-- Progress Bar Sederhana -->
            <div class="flex justify-between items-center mb-8 border-b-4 border-black pb-4 text-sm md:text-base">
                <span :class="step === 1 ? 'text-feast-pink font-bold md:text-xl' : 'text-gray-400 font-semibold'">1. Identitas</span>
                <span :class="step === 2 ? 'text-feast-pink font-bold md:text-xl' : 'text-gray-400 font-semibold'">2. Minat</span>
                <span :class="step === 3 ? 'text-feast-pink font-bold md:text-xl' : 'text-gray-400 font-semibold'">3. Bakat</span>
                <span :class="step === 4 ? 'text-feast-pink font-bold md:text-xl' : 'text-gray-400 font-semibold'">4. RIASEC</span>
                <span :class="step === 5 ? 'text-feast-pink font-bold md:text-xl' : 'text-gray-400 font-semibold'">5. Selesai</span>
            </div>

            <!-- STEP 1: Identitas Peserta Didik -->
            <div id="step-1" x-show="step === 1" x-transition>
                <h2 class="text-3xl font-bold mb-2">📝 Identitas Peserta Didik</h2>
                <p class="mb-6 font-semibold bg-blue-50 text-blue-800 p-4 rounded-xl border-2 border-black">
                    Silakan ketik <strong>Nama</strong> Anda dan klik dari daftar yang muncul. Gunakan <strong>NIS</strong> Anda sebagai kata sandi verifikasi.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- Pencarian Nama (Custom Autocomplete) -->
                    <div class="md:col-span-2 relative">
                        <label class="block font-bold mb-2">Cari Nama Lengkap <span class="text-red-500">*</span></label>

                        <!-- Input Pencarian -->
                        <input type="text"
                            x-model="searchQuery"
                            @input="filterSiswa"
                            @focus="showDropdown = true"
                            @click.away="showDropdown = false"
                            autocomplete="off"
                            placeholder="Ketik nama Anda di sini..."
                            class="w-full border-4 border-black p-3 rounded-xl focus:outline-none focus:ring-4 focus:ring-feast-pink">

                        <!-- Hidden input untuk ID Master Siswa (Diperbaiki) -->
                        <input type="hidden" name="siswa_id" :value="selectedSiswaId">

                        <!-- Dropdown Hasil Pencarian -->
                        <ul x-show=" showDropdown && filteredSiswa.length> 0"
                            class="absolute z-20 w-full bg-white border-4 border-black rounded-xl mt-2 max-h-60 overflow-y-auto feast-shadow" style="display: none;">
                            <template x-for="siswa in filteredSiswa" :key="siswa.id">
                                <li @click="selectSiswa(siswa)"
                                    class="p-3 hover:bg-feast-pink hover:text-white cursor-pointer font-semibold border-b-2 border-gray-100 transition-colors">
                                    <span x-text="siswa.nama"></span>
                                </li>
                            </template>
                        </ul>

                        <p x-show="searchQuery.length > 0 && filteredSiswa.length === 0 && !selectedSiswaId" class="text-red-500 font-semibold mt-2 text-sm">
                            Nama tidak ditemukan di database sekolah.
                        </p>
                    </div>

                    <!-- NIS Verifikasi Manual -->
                    <div>
                        <label class="block font-bold mb-2">NIS (2627.xx.xxx.xxxx) <span class="text-red-500">*</span></label>
                        <input type="text" name="nis" required placeholder="Ketik NIS Anda..." class="w-full border-4 border-black p-3 rounded-xl focus:outline-none focus:ring-4 focus:ring-feast-pink">
                    </div>

                    <!-- Auto-filled Fields (Readonly) -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold mb-2">Kelas</label>
                            <input type="text" x-model="autoKelas" readonly tabindex="-1" placeholder="-" class="w-full border-4 border-black p-3 rounded-xl bg-gray-200 text-gray-600 font-bold outline-none cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block font-bold mb-2">Jenis Kelamin</label>
                            <input type="text" x-model="autoJk" readonly tabindex="-1" placeholder="-" class="w-full border-4 border-black p-3 rounded-xl bg-gray-200 text-gray-600 font-bold outline-none cursor-not-allowed">
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: Aspek Minat -->
            <div id="step-2" x-show="step === 2" x-transition style="display: none;">
                <h2 class="text-3xl font-bold mb-2">A. Aspek Minat</h2>
                <p class="mb-6 font-semibold bg-gray-100 p-4 rounded-xl border-2 border-black">
                    Pilih skala: <strong class="text-red-500">1 (Tidak Sesuai)</strong>, <strong class="text-orange-500">2 (Kurang Sesuai)</strong>, <strong class="text-blue-500">3 (Sesuai)</strong>, <strong class="text-green-600">4 (Sangat Sesuai)</strong>
                </p>

                <?php foreach ($soal_minat as $kategori => $soal) : ?>
                    <div class="mb-8 p-6 bg-feast-blue/10 border-4 border-black rounded-2xl">
                        <h3 class="text-xl font-bold mb-4 bg-white inline-block px-4 py-1 border-2 border-black rounded-lg transform rotate-[-1deg]"><?= $kategori ?></h3>
                        <div class="space-y-4">
                            <?php foreach ($soal as $no => $pernyataan) : ?>
                                <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-3 border-2 border-black rounded-xl">
                                    <span class="font-semibold text-lg flex-1 mb-2 md:mb-0"><?= $no ?>. <?= $pernyataan ?></span>
                                    <div class="flex gap-4">
                                        <?php for ($i = 1; $i <= 4; $i++): ?>
                                            <label class="flex flex-col items-center cursor-pointer">
                                                <input type="radio" name="minat[<?= $no ?>]" value="<?= $i ?>" class="cursor-pointer" required>
                                                <span class="font-bold text-sm mt-1"><?= $i ?></span>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- STEP 3: Aspek Bakat -->
            <div id="step-3" x-show="step === 3" x-transition style="display: none;">
                <h2 class="text-3xl font-bold mb-6">B. Aspek Bakat (Multiple Intelligences)</h2>
                <p class="mb-6 font-semibold bg-gray-100 p-4 rounded-xl border-2 border-black">
                    Pilih skala: <strong class="text-red-500">1 (Tidak Sesuai)</strong>, <strong class="text-orange-500">2 (Kurang Sesuai)</strong>, <strong class="text-blue-500">3 (Sesuai)</strong>, <strong class="text-green-600">4 (Sangat Sesuai)</strong>
                </p>
                <div class="space-y-4">
                    <?php foreach ($soal_bakat as $no => $data) : ?>
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-4 border-4 border-black rounded-xl hover:bg-gray-50 transition">
                            <div class="flex-1 pr-4">
                                <h4 class="font-bold text-feast-blue text-lg"><?= $data[0] ?></h4>
                                <p class="font-semibold text-gray-700"><?= $data[1] ?></p>
                            </div>
                            <div class="flex gap-4 mt-3 md:mt-0 bg-gray-200 p-2 border-2 border-black rounded-lg">
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <label class="flex flex-col items-center cursor-pointer px-2">
                                        <input type="radio" name="bakat[<?= $no ?>]" value="<?= $i ?>" required>
                                        <span class="font-bold"><?= $i ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- STEP 4: RIASEC -->
            <div id="step-4" x-show="step === 4" x-transition style="display: none;">
                <h2 class="text-3xl font-bold mb-6">C. RIASEC (Holland)</h2>
                <p class="mb-6 font-semibold bg-gray-100 p-4 rounded-xl border-2 border-black">
                    Pilih skala: <strong class="text-red-500">1 (Tidak Suka)</strong>, <strong class="text-orange-500">2 (Kurang Suka)</strong>, <strong class="text-blue-500">3 (Suka)</strong>, <strong class="text-green-600">4 (Sangat Suka)</strong>
                </p>
                <?php foreach ($soal_riasec as $kategori => $soal) : ?>
                    <div class="mb-6 p-4 border-4 border-black rounded-xl">
                        <h3 class="font-bold text-xl bg-feast-yellow inline-block px-3 py-1 border-2 border-black rounded-md mb-3"><?= $kategori ?></h3>
                        <div class="space-y-3">
                            <?php foreach ($soal as $no => $pernyataan) : ?>
                                <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-gray-50 p-3 border-b-2 border-dashed border-gray-400">
                                    <span class="font-medium flex-1 pr-4 mb-2 md:mb-0"><?= $no ?>. <?= $pernyataan ?></span>
                                    <div class="flex gap-4">
                                        <?php for ($i = 1; $i <= 4; $i++): ?>
                                            <label class="flex flex-col items-center cursor-pointer">
                                                <input type="radio" name="riasec[<?= $no ?>]" value="<?= $i ?>" class="cursor-pointer" required>
                                                <span class="font-bold text-sm mt-1"><?= $i ?></span>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- STEP 5: Pertanyaan Terbuka & Submit -->
            <div id="step-5" x-show="step === 5" x-transition style="display: none;">
                <h2 class="text-3xl font-bold mb-6">💬 Pertanyaan Terbuka</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block font-bold mb-2">1. Cita-cita saya adalah:</label>
                        <input type="text" name="cita_cita" required class="w-full border-4 border-black p-3 rounded-xl">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">2. Kegiatan favorit saya:</label>
                        <input type="text" name="kegiatan_favorit" required class="w-full border-4 border-black p-3 rounded-xl">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">3. Prestasi yang pernah saya raih:</label>
                        <input type="text" name="prestasi" required class="w-full border-4 border-black p-3 rounded-xl">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">4. Keterampilan yang ingin saya kembangkan:</label>
                        <input type="text" name="keterampilan" required class="w-full border-4 border-black p-3 rounded-xl">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">5. Dukungan yang saya butuhkan:</label>
                        <textarea name="dukungan" rows="3" required class="w-full border-4 border-black p-3 rounded-xl"></textarea>
                    </div>
                </div>

                <div class="mt-8 bg-feast-pink text-white p-6 border-4 border-black rounded-2xl text-center rotate-[1deg]">
                    <h3 class="text-2xl font-bold mb-2">Sudah Yakin dengan Jawabanmu?</h3>
                    <p class="mb-4">Pastikan tidak ada pernyataan yang terlewat ya!</p>
                    <button type="submit" class="bg-feast-yellow text-black text-2xl font-bold px-10 py-4 border-4 border-black rounded-full feast-shadow hover-feast-press transition-all">
                        🚀 KIRIM JAWABAN
                    </button>
                </div>
            </div>

            <!-- Navigasi Stepper Alpine.js -->
            <div class="flex justify-between mt-10 border-t-4 border-black pt-6">
                <button type="button" x-show="step > 1" @click="step--" class="bg-white border-4 border-black font-bold px-6 py-2 rounded-full feast-shadow hover-feast-press">
                    ⬅️ Sebelumnya
                </button>
                <div class="flex-1"></div> <!-- Spacer -->
                <button type="button" x-show="step < 5" @click="nextStep()" class="bg-feast-blue text-white border-4 border-black font-bold px-8 py-2 rounded-full feast-shadow hover-feast-press">
                    Selanjutnya ➡️
                </button>
            </div>

        </form>
    </main>

    <!-- Script Logika Validasi & Typeahead Autocomplete -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('asesmenForm', () => ({
                step: 1,

                // Data Master Siswa disuntikkan dari PHP (Mengandung id, nama, kelas, jenis_kelamin)
                dataSiswa: <?= json_encode($list_master_siswa) ?>,

                searchQuery: '',
                filteredSiswa: [],
                showDropdown: false,

                // Menyimpan status pilihan siswa
                selectedSiswaId: '',
                selectedNamaAwal: '',
                autoKelas: '',
                autoJk: '',

                // Fungsi Filter Pencarian Live (Typeahead)
                filterSiswa() {
                    // Reset ID jika pengguna mengetik ulang setelah memilih
                    if (this.searchQuery !== this.selectedNamaAwal) {
                        this.selectedSiswaId = '';
                        this.autoKelas = '';
                        this.autoJk = '';
                    }

                    if (this.searchQuery.trim() === '') {
                        this.filteredSiswa = [];
                        this.showDropdown = false;
                        return;
                    }

                    // Mencari nama yang mengandung kata kunci (Case Insensitive)
                    this.filteredSiswa = this.dataSiswa.filter(s =>
                        s.nama.toLowerCase().includes(this.searchQuery.toLowerCase())
                    );

                    this.showDropdown = true;
                },

                // Fungsi saat nama pada dropdown diklik
                selectSiswa(siswa) {
                    this.searchQuery = siswa.nama;
                    this.selectedNamaAwal = siswa.nama; // Patokan jika diketik ulang
                    this.selectedSiswaId = siswa.id;
                    this.autoKelas = siswa.kelas;
                    this.autoJk = siswa.jenis_kelamin;

                    this.showDropdown = false;
                },

                // PERBAIKAN: Jadikan async agar bisa menunggu respon fetch dari server
                async nextStep() {
                    let currentDiv = document.getElementById('step-' + this.step);
                    let isValid = true;

                    // ----------------------------------------------------
                    // Validasi Khusus Step 1: Cek NIS ke Server API
                    // ----------------------------------------------------
                    if (this.step === 1) {
                        if (!this.selectedSiswaId) {
                            alert('⚠️ Silakan pilih Nama Anda dari daftar pencarian yang muncul di bawah kolom input!');
                            return;
                        }

                        let nisInput = document.querySelector('input[name="nis"]').value.trim();
                        if (!nisInput) {
                            alert('⚠️ Silakan masukkan NIS Anda untuk verifikasi identitas!');
                            return;
                        }

                        // Kirim request pencocokan NIS ke server
                        try {
                            let response = await fetch(`proses/cek_nis.php?id=${this.selectedSiswaId}&nis=${nisInput}`);
                            let result = await response.json();

                            if (!result.valid) {
                                alert('⚠️ ' + result.message); // Menampilkan pesan salah NIS atau sudah pernah mengisi
                                return; // Berhenti di sini, jangan lanjut ke step 2
                            }
                        } catch (error) {
                            alert('Terjadi kesalahan koneksi. Pastikan internet Anda terhubung.');
                            return;
                        }
                    }

                    // 1. Validasi input biasa (Teks, Select, Textarea)
                    let normalInputs = currentDiv.querySelectorAll('input:not([type="radio"]):not([type="hidden"])[required], select[required], textarea[required]');
                    normalInputs.forEach(input => {
                        if (!input.value.trim()) {
                            isValid = false;
                        }
                    });

                    // 2. Validasi input Radio (Pilihan Skala)
                    let radios = currentDiv.querySelectorAll('input[type="radio"][required]');
                    let radioNames = new Set(Array.from(radios).map(r => r.name));

                    radioNames.forEach(name => {
                        if (!currentDiv.querySelector(`input[name="${name}"]:checked`)) {
                            isValid = false;
                        }
                    });

                    // 3. Eksekusi Hasil Validasi
                    if (isValid) {
                        this.step++;
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    } else {
                        alert('⚠️ STOP! Ada pertanyaan atau isian yang terlewat. Mohon lengkapi terlebih dahulu sebelum melanjutkan!');
                    }
                }
            }));
        });
    </script>

</body>

</html>