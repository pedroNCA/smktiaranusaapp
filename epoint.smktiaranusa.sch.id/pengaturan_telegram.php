<?php
require 'config.php';
require 'includes/auth.php';
hanyaAdmin();
require_once 'includes/telegram_helper.php';

$judul_halaman = 'Pengaturan Bot Telegram';

// Ambil konfigurasi saat ini
$cfg = getTelegramConfig($koneksi);
$hasil_uji = null;

// Handle Form Submission
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $aksi = clean($koneksi, $_POST['aksi'] ?? 'simpan');

    if ($aksi === 'simpan') {
        $aktif = isset($_POST['telegram_aktif']) ? '1' : '0';
        $bot_token = clean($koneksi, trim($_POST['telegram_bot_token'] ?? ''));
        $chat_id = clean($koneksi, trim($_POST['telegram_chat_id'] ?? ''));

        // Simpan ke tabel pengaturan
        mysqli_query($koneksi, "INSERT INTO pengaturan (kunci, nilai) VALUES ('telegram_aktif', '$aktif') ON DUPLICATE KEY UPDATE nilai='$aktif'");
        mysqli_query($koneksi, "INSERT INTO pengaturan (kunci, nilai) VALUES ('telegram_bot_token', '$bot_token') ON DUPLICATE KEY UPDATE nilai='$bot_token'");
        mysqli_query($koneksi, "INSERT INTO pengaturan (kunci, nilai) VALUES ('telegram_chat_id', '$chat_id') ON DUPLICATE KEY UPDATE nilai='$chat_id'");

        redirect('pengaturan_telegram.php', 'Konfigurasi Bot Telegram berhasil disimpan.');
    } elseif ($aksi === 'test_kirim') {
        $test_token = clean($koneksi, trim($_POST['telegram_bot_token'] ?? ''));
        $test_chat = clean($koneksi, trim($_POST['telegram_chat_id'] ?? ''));

        if (empty($test_token) || empty($test_chat)) {
            $hasil_uji = [
                'status' => 'danger',
                'pesan' => 'Harap isi Bot Token dan Chat ID terlebih dahulu sebelum melakukan uji coba kirim.'
            ];
        } else {
            $pesan_test = "🔔 <b>TES NOTIFIKASI BOT E-POINT BERHASIL</b>\n";
            $pesan_test .= "━━━━━━━━━━━━━━━━━━━━━\n";
            $pesan_test .= "Halo Guru & Petugas Kedisiplinan! 👋\n\n";
            $pesan_test .= "Bot Telegram ini telah <b>berhasil terhubung</b> dengan aplikasi <b>E-Point SMKS Tiara Nusa</b>.\n\n";
            $pesan_test .= "📅 <i>Waktu Uji Coba: " . date('d/m/Y H:i:s') . " WIB</i>\n";
            $pesan_test .= "👤 <i>Diuji oleh: " . htmlspecialchars($_SESSION['nama_lengkap']) . "</i>\n";
            $pesan_test .= "━━━━━━━━━━━━━━━━━━━━━\n";
            $pesan_test .= "✅ Notifikasi pelanggaran siswa akan otomatis dikirimkan ke grup/chat ini.";

            $res = kirimPesanTelegram($pesan_test, $test_token, $test_chat);
            if ($res['success']) {
                $hasil_uji = [
                    'status' => 'success',
                    'pesan' => 'Pesan uji coba BERHASIL terkirim ke Telegram! Periksa grup atau chat Telegram Anda sekarang.'
                ];
            } else {
                $hasil_uji = [
                    'status' => 'danger',
                    'pesan' => 'Gagal mengirim pesan uji coba: <b>' . htmlspecialchars($res['error']) . '</b>. Pastikan Token Bot dan Chat ID sudah benar serta Bot sudah dimasukkan ke dalam grup.'
                ];
            }
        }
    }
}

// Reload config
$cfg = getTelegramConfig($koneksi);

include 'includes/header.php';
?>

<div class="row g-4">
    <!-- Kolom Kiri: Form Konfigurasi Bot -->
    <div class="col-lg-7">
        <!-- Banner Header Telegram -->
        <div class="card border-0 shadow-sm mb-4 text-white overflow-hidden" 
             style="background: linear-gradient(135deg, #0088cc 0%, #0284c7 60%, #0369a1 100%); border-radius: 18px;">
            <div class="card-body p-4 position-relative">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center justify-content-center bg-white text-primary rounded-circle shadow-sm" style="width: 52px; height: 52px; font-size: 1.8rem; color: #0088cc !important;">
                            <i class="bi bi-telegram"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1 text-white">Integrasi Bot Telegram</h5>
                            <p class="mb-0 text-white-50 small">Kirim notifikasi pelanggaran siswa secara instan ke HP Guru BK &amp; Kesiswaan</p>
                        </div>
                    </div>
                    <div>
                        <?php if ($cfg['aktif'] && !empty($cfg['bot_token']) && !empty($cfg['chat_id'])): ?>
                            <span class="badge bg-success text-white px-3 py-2 fw-bold shadow-sm" style="border-radius: 30px; font-size: 0.8rem;">
                                <i class="bi bi-check-circle-fill me-1"></i> AKTIF &amp; TERHUBUNG
                            </span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark px-3 py-2 fw-semibold shadow-sm" style="border-radius: 30px; font-size: 0.8rem;">
                                <i class="bi bi-dash-circle text-muted me-1"></i> BELUM AKTIF
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($hasil_uji): ?>
            <div class="alert alert-<?= $hasil_uji['status'] ?> alert-dismissible fade show rounded-4 shadow-sm border-0 d-flex align-items-start gap-3 p-3 mb-4" role="alert">
                <i class="bi bi-<?= $hasil_uji['status'] === 'success' ? 'check-circle-fill fs-4 text-success' : 'exclamation-triangle-fill fs-4 text-danger' ?>"></i>
                <div class="flex-grow-1">
                    <?= $hasil_uji['pesan'] ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Form Pengaturan -->
        <div class="card border-0 shadow-sm p-4">
            <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-sliders text-primary"></i> Parameter Koneksi Bot
            </h6>

            <form method="POST" id="formTelegram">
                <input type="hidden" name="aksi" id="inputAksiTelegram" value="simpan">

                <!-- Switch Aktif / Nonaktif -->
                <div class="p-3 rounded-3 bg-light border mb-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fw-bold text-dark small">Status Notifikasi Otomatis</div>
                        <div class="text-muted" style="font-size: 0.78rem;">Aktifkan agar setiap ada siswa melanggar langsung dikirim ke Telegram</div>
                    </div>
                    <div class="form-check form-switch fs-4 mb-0">
                        <input class="form-check-input" type="checkbox" name="telegram_aktif" id="switchAktif" value="1" <?= $cfg['aktif'] ? 'checked' : '' ?>>
                    </div>
                </div>

                <!-- Input Bot Token -->
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary small d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-key-fill text-warning me-1"></i> Bot Token Telegram</span>
                        <a href="#panduanBot" class="text-primary text-decoration-none" style="font-size: 0.75rem;">Cara dapat token?</a>
                    </label>
                    <div class="input-group">
                        <input type="password" name="telegram_bot_token" id="inputBotToken" class="form-control" 
                               value="<?= htmlspecialchars($cfg['bot_token']) ?>" 
                               placeholder="Contoh: 1234567890:ABCdefGhIjkLmNoPqRsTuVwXyZ" 
                               style="font-family: monospace; font-size: 0.88rem; border-radius: 12px 0 0 12px;">
                        <button class="btn btn-outline-secondary" type="button" onclick="toggleShowToken()" title="Tampilkan/Sembunyikan Token" style="border-radius: 0 12px 12px 0;">
                            <i class="bi bi-eye" id="iconEyeToken"></i>
                        </button>
                    </div>
                    <small class="text-muted" style="font-size: 0.75rem;">Didapatkan dari akun resmi <code>@BotFather</code> saat membuat bot baru.</small>
                </div>

                <!-- Input Chat ID -->
                <div class="mb-4">
                    <label class="form-label fw-semibold text-secondary small d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-chat-dots-fill text-info me-1"></i> Target Chat ID (Grup / Channel / Pribadi)</span>
                        <a href="#panduanChatId" class="text-primary text-decoration-none" style="font-size: 0.75rem;">Cara cek Chat ID?</a>
                    </label>
                    <input type="text" name="telegram_chat_id" id="inputChatId" class="form-control" 
                           value="<?= htmlspecialchars($cfg['chat_id']) ?>" 
                           placeholder="Contoh grup: -1001234567890 atau ID pribadi: 12345678" 
                           style="font-family: monospace; font-size: 0.88rem; border-radius: 12px;">
                    <small class="text-muted" style="font-size: 0.75rem;">ID grup kedisiplinan guru. Biasanya diawali tanda minus <code>-</code> (contoh: <code>-100...</code>).</small>
                </div>

                <!-- Tombol Aksi -->
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <button type="button" class="btn btn-primary fw-semibold flex-grow-1 py-2" onclick="submitSimpan()">
                        <i class="bi bi-save me-1"></i> Simpan Konfigurasi
                    </button>
                    <button type="button" class="btn btn-outline-primary fw-semibold py-2" onclick="submitTestKirim()" title="Kirim pesan tes ke Telegram untuk memastikan bot bekerja">
                        <i class="bi bi-send-fill me-1"></i> Uji Coba Kirim Pesan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Kolom Kanan: Panduan Lengkap & Preview -->
    <div class="col-lg-5">
        <!-- Panduan 3 Langkah Cepat -->
        <div class="card border-0 shadow-sm p-4 mb-4" id="panduanBot">
            <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-book-half text-success"></i> Panduan Cepat Menghubungkan Bot
            </h6>

            <div class="timeline-panduan">
                <!-- Langkah 1 -->
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="badge rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 26px; height: 26px; font-size: 0.8rem;">1</div>
                    <div>
                        <div class="fw-bold text-dark small">Buat Bot di @BotFather</div>
                        <p class="text-muted small mb-0" style="font-size: 0.78rem;">
                            Buka Telegram, cari akun <b>@BotFather</b> (centang biru resmi). Ketik perintah <code>/newbot</code>, beri nama bot (misal: <i>E-Point SMKS Tiara Nusa</i>). Anda akan mendapatkan <b>Bot Token</b>.
                        </p>
                    </div>
                </div>

                <!-- Langkah 2 -->
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="badge rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 26px; height: 26px; font-size: 0.8rem;">2</div>
                    <div>
                        <div class="fw-bold text-dark small">Buat Grup &amp; Undang Bot</div>
                        <p class="text-muted small mb-0" style="font-size: 0.78rem;">
                            Buat grup Telegram untuk Guru BK &amp; Kesiswaan (contoh: <i>"Kedisiplinan Tiara Nusa"</i>). Tambahkan bot yang baru Anda buat ke dalam grup tersebut dan jadikan sebagai <b>Admin Grup</b>.
                        </p>
                    </div>
                </div>

                <!-- Langkah 3 -->
                <div class="d-flex align-items-start gap-3 mb-2" id="panduanChatId">
                    <div class="badge rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 26px; height: 26px; font-size: 0.8rem;">3</div>
                    <div>
                        <div class="fw-bold text-dark small">Dapatkan Chat ID Grup</div>
                        <p class="text-muted small mb-0" style="font-size: 0.78rem;">
                            Undang bot pembantu seperti <b>@RawDataBot</b> atau <b>@userinfobot</b> ke grup Anda sebentar. Bot tersebut akan langsung menampilkan Chat ID grup (angka berawalan minus, cth: <code>-1002345678901</code>). Salin angka tersebut ke kolom Chat ID di sebelah kiri.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Tampilan Notifikasi Telegram -->
        <div class="card border-0 shadow-sm p-4">
            <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                <i class="bi bi-chat-square-text-fill text-info"></i> Simulasi Tampilan di Telegram
            </h6>
            <p class="text-muted small mb-3" style="font-size: 0.78rem;">Pesan otomatis terkirim setiap kali pelanggaran dicatat di sistem:</p>

            <!-- Telegram Chat Bubble Mockup -->
            <div class="p-3 rounded-4" style="background-color: #f0f4f8; border: 1px solid #d9e2ec; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 0.84rem; line-height: 1.5; color: #102a43;">
                <div class="fw-bold text-danger mb-1">🚨 LAPORAN PELANGGARAN SISWA</div>
                <div class="text-muted" style="font-size:0.75rem;">━━━━━━━━━━━━━━━━━━━━━</div>
                <div>👤 <b>Nama Siswa</b> : Ahmad Fauzi</div>
                <div>🏫 <b>Kelas</b> : X TJKT 1 (TJKT)</div>
                <div>🆔 <b>NISN</b> : 0071234567</div>
                <div class="mt-2 fw-semibold">⚠️ Pelanggaran yang Dicatat:</div>
                <div class="ps-2 text-danger small">
                    • <b>[KH01]</b> Terlambat hadir ke sekolah (<b>-3 Poin</b>)<br>
                    • <b>[SPK01]</b> Tidak memakai seragam sesuai jadwal (<b>-2 Poin</b>)
                </div>
                <div class="mt-2">📉 <b>Potongan Poin Kali Ini</b> : -5 Poin</div>
                <div>🎯 <b>Sisa Skor Karakter</b> : 🟢 <b>95 / 100</b></div>
                <div>📝 <b>Keterangan</b> : Terjaring razia kedisiplinan gerbang</div>
                <div>📅 <b>Tanggal</b> : <?= date('d/m/Y') ?> (07:15 WIB)</div>
                <div>👮 <b>Petugas Pencatat</b> : Petugas OSIS 1</div>
                <div class="text-muted" style="font-size:0.75rem;">━━━━━━━━━━━━━━━━━━━━━</div>
                <div class="text-muted small fst-italic">ℹ️ E-Point Kedisiplinan &amp; Prestasi SMKS Tiara Nusa</div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleShowToken() {
    const input = document.getElementById('inputBotToken');
    const icon = document.getElementById('iconEyeToken');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function submitSimpan() {
    document.getElementById('inputAksiTelegram').value = 'simpan';
    document.getElementById('formTelegram').submit();
}

function submitTestKirim() {
    const token = document.getElementById('inputBotToken').value.trim();
    const chat = document.getElementById('inputChatId').value.trim();
    if (!token || !chat) {
        alert('⚠️ Harap isi Bot Token dan Chat ID terlebih dahulu untuk melakukan uji coba.');
        return;
    }
    document.getElementById('inputAksiTelegram').value = 'test_kirim';
    document.getElementById('formTelegram').submit();
}
</script>

<?php include 'includes/footer.php'; ?>
