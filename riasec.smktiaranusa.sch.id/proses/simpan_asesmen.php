<?php
// proses/simpan_asesmen.php

// 1. Panggil session.php TERLEBIH DAHULU agar fungsi CSRF dikenali
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

// 2. Validasi Token Keamanan CSRF
$token_dikirim = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($token_dikirim)) {
    // Keluarkan UI Error yang rapi karena ini akan diload oleh HTMX
    die('
        <div class="bg-red-500 border-8 border-black p-6 rounded-3xl text-center feast-shadow mb-8">
            <h2 class="text-3xl font-bold text-white mb-2">❌ AKSES DITOLAK</h2>
            <p class="text-lg font-bold text-white">Token keamanan tidak valid atau sesi telah habis. Silakan muat ulang (refresh) halaman ini.</p>
        </div>
    ');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $postData = sanitizeInput($_POST);

        // 1. Ambil Data Identitas dari Form
        $siswa_id = isset($postData['siswa_id']) ? (int)$postData['siswa_id'] : 0;
        $nis_input = trim($postData['nis'] ?? '');

        if (!$siswa_id || empty($nis_input)) {
            throw new Exception("Data identitas (Nama atau NIS) tidak lengkap.");
        }

        // 2. Verifikasi NIS ke tabel master_siswa
        $stmtCek = $pdo->prepare("SELECT nama, nis FROM master_siswa WHERE id = ?");
        $stmtCek->execute([$siswa_id]);
        $siswa = $stmtCek->fetch(PDO::FETCH_ASSOC);

        if (!$siswa) {
            throw new Exception("Data siswa tidak ditemukan di sistem sekolah.");
        }

        $nama_siswa = $siswa['nama'];
        $nis_db_decrypted = decryptData($siswa['nis']);

        // Jika NIS yang diketik tidak sama dengan NIS di database
        if ($nis_input !== $nis_db_decrypted) {
            throw new Exception("NIS yang Anda masukkan salah. Verifikasi identitas gagal.");
        }

        // 3. Cek Duplikasi (Cegah siswa mengisi lebih dari 1 kali)
        $stmtDuplikat = $pdo->prepare("SELECT id FROM hasil_asesmen WHERE siswa_id = ?");
        $stmtDuplikat->execute([$siswa_id]);
        if ($stmtDuplikat->fetch()) {
            throw new Exception("Anda sudah menyelesaikan asesmen ini sebelumnya. Data hanya dapat diisi satu kali.");
        }

        // 4. Ambil Data Pertanyaan Terbuka
        $cita = $postData['cita_cita'] ?? '';
        $kegiatan = $postData['kegiatan_favorit'] ?? '';
        $prestasi = $postData['prestasi'] ?? '';
        $keter = $postData['keterampilan'] ?? '';
        $dukung = $postData['dukungan'] ?? '';

        // 5. Fungsi Bantu Hitung Skor
        function hitungSkor($arr, $start, $end)
        {
            $total = 0;
            for ($i = $start; $i <= $end; $i++) {
                $total += (int)($arr[$i] ?? 0);
            }
            return $total;
        }

        // 6. Ambil Array Jawaban Mentah
        $minat = $postData['minat'] ?? [];
        $bakat = $postData['bakat'] ?? [];
        $riasec = $postData['riasec'] ?? [];

        // Gabungkan semua jawaban menjadi JSON untuk kolom jawaban_lengkap
        $semua_jawaban = [
            'minat' => $minat,
            'bakat' => $bakat,
            'riasec' => $riasec
        ];
        $jawaban_json = json_encode($semua_jawaban);

        // Hitung Skor Minat
        $m1 = hitungSkor($minat, 1, 5);
        $m2 = hitungSkor($minat, 6, 10);
        $m3 = hitungSkor($minat, 11, 15);
        $m4 = hitungSkor($minat, 16, 20);
        $m5 = hitungSkor($minat, 21, 25);
        $m6 = hitungSkor($minat, 26, 30);
        $m7 = hitungSkor($minat, 31, 35);
        $m8 = hitungSkor($minat, 36, 40);

        // Hitung Skor Bakat
        $b1 = (int)($bakat[1] ?? 0);
        $b2 = (int)($bakat[2] ?? 0);
        $b3 = (int)($bakat[3] ?? 0);
        $b4 = (int)($bakat[4] ?? 0);
        $b5 = (int)($bakat[5] ?? 0);
        $b6 = (int)($bakat[6] ?? 0);
        $b7 = (int)($bakat[7] ?? 0);
        $b8 = (int)($bakat[8] ?? 0);

        // Hitung Skor RIASEC
        $r = hitungSkor($riasec, 1, 6);
        $i = hitungSkor($riasec, 7, 12);
        $a = hitungSkor($riasec, 13, 18);
        $s = hitungSkor($riasec, 19, 24);
        $e = hitungSkor($riasec, 25, 30);
        $c = hitungSkor($riasec, 31, 36);

        // Menentukan Kode Holland
        $skor_riasec = ['R' => $r, 'I' => $i, 'A' => $a, 'S' => $s, 'E' => $e, 'C' => $c];
        arsort($skor_riasec);
        $top3 = array_slice(array_keys($skor_riasec), 0, 3);
        $kode_holland = implode('', $top3);

        // 7. Insert ke tabel hasil_asesmen (Sesuai dengan relasi master_siswa)
        $stmtHasil = $pdo->prepare("INSERT INTO hasil_asesmen (
            siswa_id, 
            m1_akademik, m2_sains, m3_seni, m4_bahasa, m5_sosial, m6_olahraga, m7_wirausaha, m8_digital,
            b1_linguistik, b2_logika, b3_spasial, b4_kinestetik, b5_musik, b6_interpersonal, b7_intrapersonal, b8_naturalis,
            r_realistic, i_investigative, a_artistic, s_social, e_enterprising, c_conventional, 
            kode_holland, cita_cita, kegiatan_favorit, prestasi, keterampilan, dukungan, jawaban_lengkap
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?, ?
        )");

        $stmtHasil->execute([
            $siswa_id,
            $m1,
            $m2,
            $m3,
            $m4,
            $m5,
            $m6,
            $m7,
            $m8,
            $b1,
            $b2,
            $b3,
            $b4,
            $b5,
            $b6,
            $b7,
            $b8,
            $r,
            $i,
            $a,
            $s,
            $e,
            $c,
            $kode_holland,
            $cita,
            $kegiatan,
            $prestasi,
            $keter,
            $dukung,
            $jawaban_json
        ]);

        $pdo->commit();

        // 8. Tampilkan UI Sukses (HTMX Swap). 
        // Sesuai aturan: Siswa TIDAK BOLEH melihat hasil asesmen, hanya pesan sukses.
?>
        <div class="bg-green-400 border-8 border-black p-8 rounded-3xl text-center feast-shadow-lg mb-8 transform rotate-[1deg]">
            <div class="text-7xl mb-4">🎉</div>
            <h2 class="text-4xl font-extrabold text-white mb-2" style="-webkit-text-stroke: 2px black;">BERHASIL DISIMPAN!</h2>
            <p class="text-xl font-bold mb-6 text-black">Terima kasih <strong><?= htmlspecialchars($nama_siswa) ?></strong> telah menyelesaikan asesmen.</p>
            <br>
            <a href="/" class="inline-block bg-white text-black text-2xl font-bold px-8 py-3 border-4 border-black rounded-full feast-shadow hover-feast-press transition-all">
                🏠 KEMBALI KE BERANDA
            </a>
        </div>
        <script>
            // Sembunyikan form agar tidak bisa disubmit berulang kali
            document.getElementById('asesmenFormElement').style.display = 'none';
            // Scroll ke pesan sukses
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        </script>
    <?php
    } catch (Exception $e) {
        $pdo->rollBack();
        // Tampilkan UI Error (HTMX Swap)
    ?>
        <div class="bg-red-500 border-8 border-black p-6 rounded-3xl text-center feast-shadow mb-8">
            <h2 class="text-3xl font-bold text-white mb-2">❌ TERJADI KESALAHAN</h2>
            <p class="text-lg font-bold text-white"><?= htmlspecialchars($e->getMessage()) ?></p>
        </div>
        <script>
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        </script>
<?php
    }
}
?>