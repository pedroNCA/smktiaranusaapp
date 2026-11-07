<?php
/**
 * Modul Integrasi Notifikasi Bot Telegram E-Point
 * SMKS Tiara Nusa
 */

if (!function_exists('getTelegramConfig')) {
    function getTelegramConfig($koneksi) {
        $config = [
            'aktif' => false,
            'bot_token' => '',
            'chat_id' => '',
        ];

        // Cek dari tabel pengaturan di database
        if ($koneksi) {
            $check_table = mysqli_query($koneksi, "SHOW TABLES LIKE 'pengaturan'");
            if ($check_table && mysqli_num_rows($check_table) > 0) {
                $q = mysqli_query($koneksi, "SELECT kunci, nilai FROM pengaturan WHERE kunci LIKE 'telegram_%'");
                while ($r = mysqli_fetch_assoc($q)) {
                    if ($r['kunci'] === 'telegram_aktif') {
                        $config['aktif'] = ($r['nilai'] === '1' || strtolower($r['nilai']) === 'true');
                    }
                    if ($r['kunci'] === 'telegram_bot_token') {
                        $config['bot_token'] = trim($r['nilai']);
                    }
                    if ($r['kunci'] === 'telegram_chat_id') {
                        $config['chat_id'] = trim($r['nilai']);
                    }
                }
            }
        }

        // Fallback jika dikonfigurasi lewat konstanta config.php
        if (defined('TELEGRAM_AKTIF')) {
            $config['aktif'] = TELEGRAM_AKTIF;
        }
        if (empty($config['bot_token']) && defined('TELEGRAM_BOT_TOKEN')) {
            $config['bot_token'] = TELEGRAM_BOT_TOKEN;
        }
        if (empty($config['chat_id']) && defined('TELEGRAM_CHAT_ID')) {
            $config['chat_id'] = TELEGRAM_CHAT_ID;
        }

        return $config;
    }
}

if (!function_exists('kirimPesanTelegram')) {
    function kirimPesanTelegram($pesan, $bot_token, $chat_id) {
        $bot_token = trim($bot_token);
        $chat_id = trim($chat_id);

        if (empty($bot_token) || empty($chat_id) || empty($pesan)) {
            return ['success' => false, 'error' => 'Bot Token atau Chat ID belum diisi.'];
        }

        $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
        $post_data = [
            'chat_id' => $chat_id,
            'text' => $pesan,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ];

        // Gunakan cURL dengan timeout pendek (3 detik) agar jika server internet lambat tidak memblokir user
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curl_error) {
            return ['success' => false, 'error' => 'Gagal koneksi ke Telegram: ' . $curl_error];
        }

        $json = json_decode($response, true);
        if ($json && !empty($json['ok'])) {
            return ['success' => true, 'response' => $json];
        }

        $err_msg = $json['description'] ?? ("HTTP {$http_code}: Respon tidak valid");
        return ['success' => false, 'error' => $err_msg];
    }
}

if (!function_exists('kirimNotifikasiPelanggaran')) {
    function kirimNotifikasiPelanggaran($koneksi, $siswa_id, $kategori_ids, $tanggal, $keterangan, $petugas_nama) {
        $cfg = getTelegramConfig($koneksi);
        if (!$cfg['aktif'] || empty($cfg['bot_token']) || empty($cfg['chat_id'])) {
            return ['sent' => false, 'reason' => 'Notifikasi dinonaktifkan atau kredensial kosong.'];
        }

        // Ambil data siswa
        $q_siswa = mysqli_query($koneksi, "SELECT s.*, k.nama_kelas, k.jurusan FROM siswa s JOIN kelas k ON s.kelas_id=k.id WHERE s.id = " . (int)$siswa_id);
        $siswa = mysqli_fetch_assoc($q_siswa);
        if (!$siswa) {
            return ['sent' => false, 'reason' => 'Siswa tidak ditemukan.'];
        }

        // Hitung total sisa skor saat ini
        $q_tot_pel = mysqli_query($koneksi, "SELECT COALESCE(SUM(kp.poin), 0) AS t FROM riwayat_pelanggaran rp JOIN kategori_pelanggaran kp ON rp.kategori_id=kp.id WHERE rp.siswa_id = " . (int)$siswa_id);
        $total_pel = (int)mysqli_fetch_assoc($q_tot_pel)['t'];

        $q_tot_pres = mysqli_query($koneksi, "SELECT COALESCE(SUM(kp.poin), 0) AS t FROM riwayat_prestasi rp JOIN kategori_prestasi kp ON rp.kategori_id=kp.id WHERE rp.siswa_id = " . (int)$siswa_id);
        $total_pres = (int)mysqli_fetch_assoc($q_tot_pres)['t'];

        $skor_akhir = SKOR_AWAL - $total_pel + $total_pres;

        // Ambil rincian butir pelanggaran yang dicatat
        $list_kasus = [];
        $total_poin_baru = 0;
        foreach ($kategori_ids as $kid) {
            $q_kat = mysqli_query($koneksi, "SELECT kode, nama_pelanggaran, poin, tingkat FROM kategori_pelanggaran WHERE id = " . (int)$kid);
            if ($k = mysqli_fetch_assoc($q_kat)) {
                $list_kasus[] = "• <b>[{$k['kode']}]</b> " . htmlspecialchars($k['nama_pelanggaran']) . " (<b>-{$k['poin']} Poin</b>)";
                $total_poin_baru += (int)$k['poin'];
            }
        }

        $str_kasus = !empty($list_kasus) ? implode("\n", $list_kasus) : '-';
        $ket_text = !empty($keterangan) ? htmlspecialchars($keterangan) : '-';
        $tgl_format = date('d/m/Y', strtotime($tanggal));
        $jam_format = date('H:i') . ' WIB';

        // Indikator predikat skor
        $icon_status = '🟢';
        if ($skor_akhir < 50) $icon_status = '🔴';
        elseif ($skor_akhir < 75) $icon_status = '🟠';
        elseif ($skor_akhir < 90) $icon_status = '🟡';

        $text = "🚨 <b>LAPORAN PELANGGARAN SISWA</b>\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "👤 <b>Nama Siswa</b> : " . htmlspecialchars($siswa['nama']) . "\n";
        $text .= "🏫 <b>Kelas</b> : " . htmlspecialchars($siswa['nama_kelas']) . " (" . htmlspecialchars($siswa['jurusan']) . ")\n";
        $text .= "🆔 <b>NISN</b> : " . htmlspecialchars($siswa['nisn'] ?: $siswa['nis']) . "\n\n";
        $text .= "⚠️ <b>Pelanggaran yang Dicatat:</b>\n";
        $text .= $str_kasus . "\n\n";
        $text .= "📉 <b>Potongan Poin Kali Ini</b> : -{$total_poin_baru} Poin\n";
        $text .= "🎯 <b>Sisa Skor Karakter</b> : {$icon_status} <b>{$skor_akhir} / 100</b>\n";
        $text .= "📝 <b>Keterangan</b> : {$ket_text}\n";
        $text .= "📅 <b>Tanggal</b> : {$tgl_format} ({$jam_format})\n";
        $text .= "👮 <b>Petugas Pencatat</b> : " . htmlspecialchars($petugas_nama) . "\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "ℹ️ <i>E-Point Kedisiplinan &amp; Prestasi SMKS Tiara Nusa</i>";

        return kirimPesanTelegram($text, $cfg['bot_token'], $cfg['chat_id']);
    }
}
