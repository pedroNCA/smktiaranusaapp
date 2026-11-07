<?php
require 'config.php';
require 'includes/auth.php';
if (isKepalaSekolah()) {
    redirect('index.php', 'Akses ditolak: Kepala Sekolah hanya memiliki hak akses pemantauan (monitoring).', 'danger');
}
$judul_halaman = 'Input Pelanggaran';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $siswa_id = (int)$_POST['siswa_id'];
    $tanggal = clean($koneksi, $_POST['tanggal']);
    $ket = clean($koneksi, $_POST['keterangan']);
    $petugas = (int)$_SESSION['user_id'];
    $mode_input = clean($koneksi, $_POST['mode_input'] ?? 'single');

    $kategori_ids = [];
    if ($mode_input === 'multi' && !empty($_POST['kategori_ids']) && is_array($_POST['kategori_ids'])) {
        foreach ($_POST['kategori_ids'] as $kid) {
            $kid = (int)$kid;
            if ($kid > 0) {
                $kategori_ids[] = $kid;
            }
        }
        $kategori_ids = array_unique($kategori_ids);
    } elseif (!empty($_POST['kategori_id'])) {
        $kid = (int)$_POST['kategori_id'];
        if ($kid > 0) {
            $kategori_ids[] = $kid;
        }
    }

    if (!$siswa_id || empty($kategori_ids) || !$tanggal) {
        redirect('input_pelanggaran.php', 'Semua field wajib diisi dan minimal 1 jenis pelanggaran harus dipilih.', 'danger');
    }

    // Ambil info siswa
    $siswa_q = mysqli_query($koneksi, "SELECT s.id, s.nama, k.nama_kelas FROM siswa s JOIN kelas k ON s.kelas_id=k.id WHERE s.id = $siswa_id");
    if (mysqli_num_rows($siswa_q) === 0) {
        redirect('input_pelanggaran.php', 'Data siswa tidak valid.', 'danger');
    }
    $siswa_row = mysqli_fetch_assoc($siswa_q);

    // Proses pencatatan satu per satu
    $inserted_count = 0;
    $total_poin = 0;
    foreach ($kategori_ids as $kid) {
        $kat_q = mysqli_query($koneksi, "SELECT id, kode, nama_pelanggaran, poin FROM kategori_pelanggaran WHERE id = $kid");
        if ($krow = mysqli_fetch_assoc($kat_q)) {
            $ins = mysqli_query($koneksi, "INSERT INTO riwayat_pelanggaran (siswa_id, kategori_id, tanggal, keterangan, petugas_id)
                VALUES ($siswa_id, $kid, '$tanggal', '$ket', $petugas)");
            if ($ins) {
                $inserted_count++;
                $total_poin += (int)$krow['poin'];
            }
        }
    }

    // Kirim notifikasi Telegram secara otomatis jika bot aktif
    if ($inserted_count > 0) {
        require_once 'includes/telegram_helper.php';
        $petugas_nama = $_SESSION['nama_lengkap'] ?? 'Petugas Kedisiplinan';
        kirimNotifikasiPelanggaran($koneksi, $siswa_id, $kategori_ids, $tanggal, $ket, $petugas_nama);
    }

    if ($inserted_count > 1) {
        redirect('input_pelanggaran.php', "Berhasil mencatat {$inserted_count} pelanggaran sekaligus untuk {$siswa_row['nama']} ({$siswa_row['nama_kelas']}) - Total akumulasi: -{$total_poin} Poin.", 'success');
    } elseif ($inserted_count === 1) {
        redirect('input_pelanggaran.php', "Data pelanggaran untuk {$siswa_row['nama']} ({$siswa_row['nama_kelas']}) berhasil dicatat (-{$total_poin} Poin).", 'success');
    } else {
        redirect('input_pelanggaran.php', 'Gagal mencatat data pelanggaran.', 'danger');
    }
}

$kelas_query = mysqli_query($koneksi, "SELECT id, nama_kelas, jurusan FROM kelas ORDER BY jurusan, nama_kelas");
$mplb_kelas = [];
$tjkt_kelas = [];
while ($k = mysqli_fetch_assoc($kelas_query)) {
    if ($k['jurusan'] === 'MPLB') {
        $mplb_kelas[] = $k;
    } else {
        $tjkt_kelas[] = $k;
    }
}

$siswa_list_query = mysqli_query($koneksi, "SELECT s.id, s.nama, s.nis, s.nisn, s.kelas_id, k.nama_kelas, k.jurusan FROM siswa s JOIN kelas k ON s.kelas_id=k.id WHERE s.status='aktif' ORDER BY k.jurusan, k.nama_kelas, s.nama");
$daftar_siswa = [];
while ($s = mysqli_fetch_assoc($siswa_list_query)) {
    $daftar_siswa[] = $s;
}
$total_siswa = count($daftar_siswa);

$preselected_id = (int)($_GET['siswa_id'] ?? 0);
$preselected_siswa = null;
if ($preselected_id > 0) {
    foreach ($daftar_siswa as $ds) {
        if ((int)$ds['id'] === $preselected_id) {
            $preselected_siswa = $ds;
            break;
        }
    }
}

// Ambil kategori pelanggaran terkelompok
$kategori_query = mysqli_query($koneksi, "SELECT * FROM kategori_pelanggaran ORDER BY id ASC");
$kategori_grouped = [];
$kategori_meta = [];
while ($k = mysqli_fetch_assoc($kategori_query)) {
    $kategori_grouped[$k['kategori']][] = $k;
    $kategori_meta[$k['id']] = [
        'kode' => $k['kode'],
        'kategori' => $k['kategori'],
        'nama' => $k['nama_pelanggaran'],
        'tingkat' => $k['tingkat'],
        'poin' => (int)$k['poin']
    ];
}

// riwayat terbaru untuk ditampilkan di samping form
$riwayat = mysqli_query($koneksi, "SELECT rp.*, s.nama, s.nis, s.nisn, kp.nama_pelanggaran, kp.poin, kp.tingkat, kp.kode, u.nama_lengkap AS nama_petugas, u.role AS role_petugas
    FROM riwayat_pelanggaran rp
    JOIN siswa s ON rp.siswa_id=s.id
    JOIN kategori_pelanggaran kp ON rp.kategori_id=kp.id
    LEFT JOIN users u ON rp.petugas_id=u.id
    ORDER BY rp.created_at DESC LIMIT 15");

include 'includes/header.php';
?>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card p-3 border-0 shadow-sm">
            <h6 class="fw-bold mb-3 text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Form Input Pelanggaran</h6>
            <form method="POST" id="formPelanggaran" onsubmit="return validateFormPelanggaran()">
                <!-- Hidden input mode input -->
                <input type="hidden" name="mode_input" id="hiddenModeInput" value="single">

                <!-- Pilih Siswa (Sesuai Foto Kedua: Dropdown Kelas Biru + Input Cari Murid dengan Counter) -->
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary d-flex justify-content-between align-items-center mb-1">
                        <span><i class="bi bi-person-check text-danger me-1"></i> Pilih Siswa</span>
                        <span class="badge bg-light text-muted fw-normal" style="font-size:0.75rem;">Pilih kelas &amp; cari nama murid</span>
                    </label>

                    <div class="position-relative">
                        <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
                            <!-- Dropdown Kelas (Rounded Blue Outline persis Foto Kedua) -->
                            <div style="min-width: 150px; flex-shrink: 0;">
                                <select id="filterKelasSiswa" class="form-select fw-semibold" 
                                        style="border-radius: 14px; height: 42px; font-size: 0.88rem; border: 1.5px solid #3b82f6; color: #1e293b; background-color: #ffffff;"
                                        onchange="onFilterKelasChange(this.value)">
                                    <option value="semua">Semua Kelas</option>
                                    <optgroup label="Jurusan MPLB">
                                        <?php foreach ($mplb_kelas as $k): ?>
                                            <option value="<?= $k['id'] ?>" <?= ($preselected_siswa && (int)$preselected_siswa['kelas_id'] === (int)$k['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($k['nama_kelas']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="Jurusan TJKT">
                                        <?php foreach ($tjkt_kelas as $k): ?>
                                            <option value="<?= $k['id'] ?>" <?= ($preselected_siswa && (int)$preselected_siswa['kelas_id'] === (int)$k['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($k['nama_kelas']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </div>

                            <!-- Input Cari Nama Murid dengan counter (contoh: 32/425 persis Foto Kedua) -->
                            <div class="position-relative flex-grow-1">
                                <i class="bi bi-search position-absolute text-muted" style="top: 11px; left: 14px; font-size: 0.9rem;"></i>
                                <input type="text" id="inputCariSiswa" class="form-control" 
                                       placeholder="Cari nama murid..." 
                                       autocomplete="off"
                                       style="border-radius: 14px; height: 42px; padding-left: 38px; padding-right: 75px; font-size: 0.9rem; border: 1.5px solid #cbd5e1;"
                                       oninput="onCariSiswaInput(this.value)"
                                       onfocus="bukaDropdownSiswa()"
                                       onclick="bukaDropdownSiswa()">
                                <span class="position-absolute text-muted small fw-semibold" id="counterMuridText" 
                                      style="top: 11px; right: 14px; font-size: 0.8rem; pointer-events: none;">
                                    <?= $total_siswa ?>/<?= $total_siswa ?>
                                </span>

                                <!-- Dropdown Popover List Hasil Pencarian Siswa (Tepat di bawah input) -->
                                <div id="dropdownListSiswa" class="dropdown-siswa-box position-absolute start-0 end-0 bg-white border rounded-3 shadow-lg d-none" 
                                     style="top: 46px; z-index: 1050; max-height: 250px; overflow-y: auto;">
                                    <div id="listSiswaContainer" class="p-1">
                                        <!-- Diisi secara dinamis oleh JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden Input untuk form submission -->
                        <input type="hidden" name="siswa_id" id="hiddenSiswaId" required value="<?= $preselected_siswa ? $preselected_siswa['id'] : '' ?>">
                    </div>

                    <!-- Box Siswa Terpilih (Tampil jika sudah dipilih) -->
                    <div id="boxSiswaTerpilih" class="mt-2 p-2 px-3 rounded-3 bg-light border border-success-subtle d-flex align-items-center justify-content-between <?= $preselected_siswa ? '' : 'd-none' ?>">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-circle rounded-circle bg-success-subtle text-success fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px; height:34px; font-size:0.85rem;" id="terpilihAvatar">
                                <?= $preselected_siswa ? strtoupper(substr($preselected_siswa['nama'], 0, 1)) : '' ?>
                            </div>
                            <div>
                                <div class="fw-bold text-dark small" id="terpilihNama"><?= $preselected_siswa ? htmlspecialchars($preselected_siswa['nama']) : '' ?></div>
                                <div class="text-muted" style="font-size:0.75rem;" id="terpilihSub">
                                    <?= $preselected_siswa ? 'Kelas: <strong>' . htmlspecialchars($preselected_siswa['nama_kelas']) . '</strong> &bull; NISN: ' . htmlspecialchars($preselected_siswa['nisn'] ?: $preselected_siswa['nis']) . ' &bull; <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.7rem;">' . htmlspecialchars($preselected_siswa['jurusan']) . '</span>' : '' ?>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 rounded-pill" onclick="resetPilihSiswa()" title="Ganti Siswa" style="font-size:0.78rem;">
                            <i class="bi bi-x-lg me-1"></i>Ganti
                        </button>
                    </div>
                </div>
                
                <!-- Pemilihan Jenis Pelanggaran (Mode 1 Pelanggaran vs Multi Checklist) -->
                <div class="mb-3">
                    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-2">
                        <label class="form-label fw-semibold text-secondary mb-0">
                            <i class="bi bi-shield-exclamation text-danger me-1"></i> Jenis Pelanggaran
                        </label>
                        <!-- Mode Switch Buttons -->
                        <div class="btn-group btn-group-sm p-1 rounded-pill bg-light border" role="group">
                            <button type="button" class="btn btn-sm rounded-pill px-3 py-1 fw-semibold btn-danger text-white" id="btnModeSingle" onclick="setModeInputPelanggaran('single')">
                                <i class="bi bi-list-check me-1"></i> 1 Pelanggaran
                            </button>
                            <button type="button" class="btn btn-sm rounded-pill px-3 py-1 fw-semibold text-secondary" id="btnModeMulti" onclick="setModeInputPelanggaran('multi')">
                                <i class="bi bi-ui-checks-grid me-1"></i> Multi Checklist <span class="badge bg-danger ms-1" style="font-size:0.65rem;">Baru</span>
                            </button>
                        </div>
                    </div>

                    <!-- Single Mode Container (Dropdown Cepat Standar) -->
                    <div id="containerSingleMode">
                        <select name="kategori_id" id="selectKategori" class="form-select" onchange="updatePreview()">
                            <option value="">-- Pilih Jenis Pelanggaran --</option>
                            <?php foreach ($kategori_grouped as $nama_kat => $items): ?>
                                <optgroup label="<?= htmlspecialchars($nama_kat) ?>">
                                    <?php foreach ($items as $item): ?>
                                        <option value="<?= $item['id'] ?>">
                                            <?= htmlspecialchars("[{$item['kode']}] {$item['nama_pelanggaran']} (-{$item['poin']} Poin)") ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>

                        <!-- Preview Info Box -->
                        <div id="previewBox" class="p-3 mt-2 rounded bg-light border border-danger-subtle d-none">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span id="prevKode" class="badge bg-dark font-monospace"></span>
                                <span id="prevTingkat" class="badge"></span>
                            </div>
                            <div id="prevNama" class="fw-bold text-dark mb-1"></div>
                            <div class="d-flex justify-content-between text-muted small">
                                <span id="prevKategori"></span>
                                <span id="prevPoin" class="fw-bold text-danger fs-6"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Multi Checklist Mode Container (Centang Banyak Sekaligus) -->
                    <div id="containerMultiMode" class="d-none">
                        <!-- Floating / Top Live Summary Box -->
                        <div id="multiSummaryBox" class="p-2 px-3 rounded-3 bg-danger-subtle border border-danger-subtle mb-2">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-danger text-white rounded-pill px-2 py-1" id="multiCountBadge">0</span>
                                    <span class="fw-bold text-dark small">Pelanggaran Terpilih</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-danger fw-bold fs-6" id="multiPoinBadge">-0 Poin</span>
                                    <button type="button" class="btn btn-link text-danger text-decoration-none p-0 small" onclick="resetMultiChecklist()" title="Reset pilihan" style="font-size:0.75rem;">
                                        Reset
                                    </button>
                                </div>
                            </div>
                            <!-- Chips / Pills Pelanggaran yang Dipilih -->
                            <div id="multiSelectedPills" class="d-flex flex-wrap gap-1 mt-2 pt-2 border-top border-danger-subtle d-none custom-scroll" style="max-height: 90px; overflow-y: auto;">
                                <!-- Diisi secara dinamis oleh JavaScript -->
                            </div>
                        </div>

                        <!-- Filter & Search Input for Multi Checklist -->
                        <div class="mb-2">
                            <div class="position-relative mb-1">
                                <i class="bi bi-search position-absolute text-muted" style="top: 8px; left: 10px; font-size: 0.85rem;"></i>
                                <input type="text" id="filterPelanggaranChecklist" class="form-control form-control-sm" 
                                       placeholder="Cari tata tertib (cth: seragam, spk, terlambat, dasi)..." 
                                       style="padding-left: 32px; border-radius: 8px; font-size: 0.82rem;"
                                       oninput="onSearchChecklistPelanggaran(this.value)">
                            </div>
                            <!-- Category Filter Chips -->
                            <div class="d-flex gap-1 overflow-x-auto py-1 custom-scroll" style="font-size: 0.75rem; white-space: nowrap; -webkit-overflow-scrolling: touch;">
                                <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill active cat-pill px-2 py-1 flex-shrink-0" data-cat="all" onclick="filterKatChecklist('all', this)">Semua (59)</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill cat-pill px-2 py-1 flex-shrink-0" data-cat="KH" onclick="filterKatChecklist('KH', this)">Kehadiran</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill cat-pill px-2 py-1 flex-shrink-0" data-cat="SPK" onclick="filterKatChecklist('SPK', this)">Seragam</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill cat-pill px-2 py-1 flex-shrink-0" data-cat="KP" onclick="filterKatChecklist('KP', this)">Kedisiplinan</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill cat-pill px-2 py-1 flex-shrink-0" data-cat="KTJ" onclick="filterKatChecklist('KTJ', this)">Keteraturan</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill cat-pill px-2 py-1 flex-shrink-0" data-cat="EP" onclick="filterKatChecklist('EP', this)">Etika</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill cat-pill px-2 py-1 flex-shrink-0" data-cat="PB" onclick="filterKatChecklist('PB', this)">Berat</button>
                            </div>
                        </div>

                        <!-- Scrollable Checklist Items Container -->
                        <div id="checklistPelanggaranContainer" class="border rounded-3 p-2 bg-white custom-scroll" style="max-height: 330px; overflow-y: auto;">
                            <?php foreach ($kategori_grouped as $nama_kat => $items): 
                                $kat_prefix = 'OTHER';
                                if (preg_match('/\(([A-Z]+)\)/', $nama_kat, $m)) {
                                    $kat_prefix = $m[1];
                                }
                            ?>
                                <div class="kat-group-section mb-2" data-cat-prefix="<?= $kat_prefix ?>">
                                    <div class="fw-bold text-secondary small bg-light px-2 py-1 rounded mb-1 d-flex justify-content-between align-items-center" style="font-size:0.75rem;">
                                        <span><i class="bi bi-bookmark-fill text-danger me-1"></i> <?= htmlspecialchars($nama_kat) ?></span>
                                        <span class="badge bg-secondary-subtle text-secondary" style="font-size:0.68rem;"><?= count($items) ?> butir</span>
                                    </div>
                                    <div class="kat-items-list">
                                        <?php foreach ($items as $item): 
                                            $badgeTingkat = [
                                                'Ringan' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                                'Sedang' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                                'Berat'  => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle'
                                            ][$item['tingkat']] ?? 'bg-secondary-subtle text-secondary';
                                        ?>
                                            <label class="pel-item-card d-flex align-items-start p-2 rounded-2 border mb-1 cursor-pointer" 
                                                   id="label_pel_<?= $item['id'] ?>"
                                                   data-id="<?= $item['id'] ?>"
                                                   data-kode="<?= htmlspecialchars($item['kode']) ?>"
                                                   data-nama="<?= htmlspecialchars($item['nama_pelanggaran']) ?>"
                                                   data-poin="<?= (int)$item['poin'] ?>"
                                                   data-tingkat="<?= htmlspecialchars($item['tingkat']) ?>"
                                                   data-kategori="<?= htmlspecialchars($nama_kat) ?>"
                                                   data-prefix="<?= $kat_prefix ?>"
                                                   data-search="<?= strtolower(htmlspecialchars($item['kode'] . ' ' . $item['nama_pelanggaran'] . ' ' . $item['tingkat'] . ' ' . $nama_kat)) ?>"
                                                   style="cursor: pointer; transition: all 0.15s ease; background-color: #ffffff;">
                                                <input type="checkbox" name="kategori_ids[]" value="<?= $item['id'] ?>" 
                                                       class="form-check-input mt-1 me-2 pel-checkbox flex-shrink-0" 
                                                       id="check_pel_<?= $item['id'] ?>"
                                                       onchange="onPelanggaranCheckboxChange(this)">
                                                <div class="flex-grow-1" style="min-width: 0;">
                                                    <div class="d-flex align-items-center justify-content-between gap-1">
                                                        <span class="badge bg-dark-subtle text-dark font-monospace py-0 px-1" style="font-size: 0.72rem;"><?= $item['kode'] ?></span>
                                                        <span class="text-danger fw-bold small flex-shrink-0">-<?= $item['poin'] ?> Poin</span>
                                                    </div>
                                                    <div class="fw-semibold text-dark small mt-1 leading-tight text-break"><?= htmlspecialchars($item['nama_pelanggaran']) ?></div>
                                                    <div class="mt-1">
                                                        <span class="badge <?= $badgeTingkat ?> py-0 px-1" style="font-size: 0.68rem;"><?= $item['tingkat'] ?></span>
                                                    </div>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div id="noResultsChecklist" class="text-center text-muted p-4 d-none small">
                                <i class="bi bi-search me-1"></i> Tidak ada tata tertib yang cocok dengan pencarian
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Tanggal Kejadian</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Keterangan Tambahan (opsional)</label>
                    <textarea name="keterangan" class="form-control" rows="2" placeholder="Contoh: Terjaring razia kedisiplinan gerbang sekolah..."></textarea>
                </div>
                <button type="submit" class="btn btn-danger w-100 fw-semibold py-2 shadow-sm" id="btnSubmitPelanggaran">
                    <i class="bi bi-save me-1"></i> Simpan Catatan Pelanggaran
                </button>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-3 border-0 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Riwayat Pelanggaran Terbaru</h6>
                <a href="laporan.php" class="btn btn-sm btn-outline-secondary">Lihat Semua Laporan</a>
            </div>
            <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Siswa</th>
                        <th>Pelanggaran</th>
                        <th class="text-end">Poin</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($riwayat)===0): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada catatan pelanggaran</td></tr>
                <?php endif; ?>
                <?php while ($r = mysqli_fetch_assoc($riwayat)): 
                    $badgeTingkat = [
                        'Ringan' => 'bg-info text-dark',
                        'Sedang' => 'bg-warning text-dark',
                        'Berat'  => 'bg-danger text-white'
                    ][$r['tingkat']] ?? 'bg-secondary';
                ?>
                    <tr>
                        <td class="text-muted small"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                        <td>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars($r['nama']) ?></div>
                            <small class="text-muted font-monospace"><?= htmlspecialchars($r['nisn'] ?: $r['nis']) ?></small>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($r['nama_pelanggaran']) ?></div>
                            <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                <span class="badge <?= $badgeTingkat ?> py-0 px-1" style="font-size: 0.72rem;"><?= $r['tingkat'] ?></span>
                                <?php if (!empty($r['nama_petugas'])): ?>
                                    <span class="text-muted" style="font-size: 0.7rem;"><i class="bi bi-person me-1"></i><?= htmlspecialchars($r['nama_petugas']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-end text-danger fw-bold">-<?= $r['poin'] ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<style>
.item-pilih-siswa:hover {
    background-color: #f1f5f9;
}
.dropdown-siswa-box::-webkit-scrollbar,
.custom-scroll::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.dropdown-siswa-box::-webkit-scrollbar-thumb,
.custom-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.cursor-pointer {
    cursor: pointer;
}
.pel-item-card {
    transition: all 0.15s ease;
    border: 1px solid #e2e8f0;
}
.pel-item-card:hover {
    background-color: #f8fafc;
    border-color: #cbd5e1;
}
.pel-item-card.is-checked {
    background-color: #fff1f2 !important;
    border-color: #f43f5e !important;
    box-shadow: 0 1px 3px rgba(244, 63, 94, 0.1);
}
.cat-pill.active {
    background-color: #e11d48 !important;
    border-color: #e11d48 !important;
    color: #ffffff !important;
}
</style>

<script>
const metaPelanggaran = <?= json_encode($kategori_meta) ?>;
const daftarSiswa = <?= json_encode($daftar_siswa) ?>;
const totalSiswaSemua = <?= $total_siswa ?>;

function getFilteredSiswa() {
    const kId = document.getElementById('filterKelasSiswa').value;
    const q = (document.getElementById('inputCariSiswa').value || '').toLowerCase().trim();
    
    let muridDiKelas = daftarSiswa;
    if (kId !== 'semua') {
        muridDiKelas = daftarSiswa.filter(s => String(s.kelas_id) === String(kId));
    }
    
    let hasil = muridDiKelas;
    if (q) {
        hasil = muridDiKelas.filter(s => {
            const nama = (s.nama || '').toLowerCase();
            const nisn = (s.nisn || '').toLowerCase();
            const nis = (s.nis || '').toLowerCase();
            const kelas = (s.nama_kelas || '').toLowerCase();
            return nama.includes(q) || nisn.includes(q) || nis.includes(q) || kelas.includes(q);
        });
    }
    
    return {
        hasil: hasil,
        totalKelas: muridDiKelas.length,
        totalSemua: totalSiswaSemua
    };
}

function updateCounterDisplay(filtered) {
    const counterEl = document.getElementById('counterMuridText');
    if (!counterEl) return;
    const kId = document.getElementById('filterKelasSiswa').value;
    const q = (document.getElementById('inputCariSiswa').value || '').trim();
    
    if (q) {
        counterEl.innerText = `${filtered.hasil.length}/${kId === 'semua' ? filtered.totalSemua : filtered.totalKelas}`;
    } else if (kId !== 'semua') {
        // Persis screenshot foto kedua: 32/425 (jumlah siswa di kelas ini / total semua murid sekolah)
        counterEl.innerText = `${filtered.totalKelas}/${filtered.totalSemua}`;
    } else {
        counterEl.innerText = `${filtered.totalSemua}/${filtered.totalSemua}`;
    }
}

function renderListSiswa() {
    const filtered = getFilteredSiswa();
    updateCounterDisplay(filtered);
    
    const container = document.getElementById('listSiswaContainer');
    if (!container) return;
    
    if (filtered.hasil.length === 0) {
        container.innerHTML = '<div class="p-3 text-center text-muted small"><i class="bi bi-search me-1"></i> Murid tidak ditemukan</div>';
        return;
    }
    
    let html = '';
    filtered.hasil.slice(0, 40).forEach(s => {
        const initial = (s.nama || '?').charAt(0).toUpperCase();
        html += `
            <div class="item-pilih-siswa p-2 rounded-2 d-flex align-items-center justify-content-between cursor-pointer mb-1" 
                 onclick="pilihSiswa(${s.id})"
                 style="cursor: pointer; transition: background-color 0.15s ease;">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-circle rounded-circle bg-light border text-primary fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px; height:32px; font-size:0.82rem;">
                        ${initial}
                    </div>
                    <div>
                        <div class="fw-semibold text-dark small leading-tight">${s.nama}</div>
                        <small class="text-muted" style="font-size:0.72rem;">${s.nama_kelas} &bull; NISN: ${s.nisn || s.nis || '-'}</small>
                    </div>
                </div>
                <span class="badge bg-light text-secondary border small" style="font-size:0.72rem;">${s.nama_kelas}</span>
            </div>
        `;
    });
    
    if (filtered.hasil.length > 40) {
        html += `<div class="text-center text-muted p-1 small" style="font-size:0.75rem;">+ ${filtered.hasil.length - 40} murid lainnya (ketik nama untuk menyaring)...</div>`;
    }
    
    container.innerHTML = html;
}

function bukaDropdownSiswa() {
    renderListSiswa();
    const box = document.getElementById('dropdownListSiswa');
    if (box) box.classList.remove('d-none');
}

function tutupDropdownSiswa() {
    const box = document.getElementById('dropdownListSiswa');
    if (box) box.classList.add('d-none');
}

function onFilterKelasChange(val) {
    renderListSiswa();
    bukaDropdownSiswa();
}

function onCariSiswaInput(val) {
    bukaDropdownSiswa();
}

function pilihSiswa(id) {
    const s = daftarSiswa.find(item => item.id == id);
    if (!s) return;
    
    document.getElementById('hiddenSiswaId').value = s.id;
    document.getElementById('terpilihAvatar').innerText = (s.nama || '?').charAt(0).toUpperCase();
    document.getElementById('terpilihNama').innerText = s.nama;
    document.getElementById('terpilihSub').innerHTML = `Kelas: <strong>${s.nama_kelas}</strong> &bull; NISN: ${s.nisn || s.nis || '-'} &bull; <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.7rem;">${s.jurusan || ''}</span>`;
    
    // Set filter kelas sesuai siswa terpilih
    const kSelect = document.getElementById('filterKelasSiswa');
    if (kSelect) kSelect.value = s.kelas_id;
    
    const inputCari = document.getElementById('inputCariSiswa');
    if (inputCari) inputCari.value = '';
    
    document.getElementById('boxSiswaTerpilih').classList.remove('d-none');
    tutupDropdownSiswa();
    
    const filtered = getFilteredSiswa();
    updateCounterDisplay(filtered);
}

function resetPilihSiswa() {
    document.getElementById('hiddenSiswaId').value = '';
    document.getElementById('boxSiswaTerpilih').classList.add('d-none');
    const inputCari = document.getElementById('inputCariSiswa');
    if (inputCari) {
        inputCari.value = '';
        inputCari.focus();
    }
    bukaDropdownSiswa();
}

let currentInputMode = 'single';
let activeChecklistCat = 'all';

function setModeInputPelanggaran(mode) {
    currentInputMode = mode;
    document.getElementById('hiddenModeInput').value = mode;

    const btnSingle = document.getElementById('btnModeSingle');
    const btnMulti = document.getElementById('btnModeMulti');
    const boxSingle = document.getElementById('containerSingleMode');
    const boxMulti = document.getElementById('containerMultiMode');
    const submitBtn = document.getElementById('btnSubmitPelanggaran');

    if (mode === 'single') {
        btnSingle.className = 'btn btn-sm rounded-pill px-3 py-1 fw-semibold btn-danger text-white';
        btnMulti.className = 'btn btn-sm rounded-pill px-3 py-1 fw-semibold text-secondary';
        boxSingle.classList.remove('d-none');
        boxMulti.classList.add('d-none');
        submitBtn.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Catatan Pelanggaran';
    } else {
        btnMulti.className = 'btn btn-sm rounded-pill px-3 py-1 fw-semibold btn-danger text-white';
        btnSingle.className = 'btn btn-sm rounded-pill px-3 py-1 fw-semibold text-secondary';
        boxSingle.classList.add('d-none');
        boxMulti.classList.remove('d-none');
        updateMultiSummaryUI();
    }
}

function onPelanggaranCheckboxChange(cb) {
    const card = document.getElementById('label_pel_' + cb.value);
    if (card) {
        if (cb.checked) {
            card.classList.add('is-checked');
        } else {
            card.classList.remove('is-checked');
        }
    }
    updateMultiSummaryUI();
}

function updateMultiSummaryUI() {
    const checkedBoxes = Array.from(document.querySelectorAll('input[name="kategori_ids[]"]:checked'));
    const count = checkedBoxes.length;
    let totalPoin = 0;

    const pillsContainer = document.getElementById('multiSelectedPills');
    let pillsHtml = '';

    checkedBoxes.forEach(cb => {
        const card = document.getElementById('label_pel_' + cb.value);
        if (card) {
            const poin = parseInt(card.dataset.poin) || 0;
            const kode = card.dataset.kode || '';
            const nama = card.dataset.nama || '';
            totalPoin += poin;
            pillsHtml += `
                <span class="badge bg-white text-dark border border-danger-subtle d-inline-flex align-items-center gap-1 py-1 px-2" style="font-size:0.75rem;">
                    <strong class="text-danger font-monospace">[${kode}]</strong> ${nama.length > 28 ? nama.substr(0, 28) + '...' : nama} (-${poin} Poin)
                    <button type="button" class="btn-close ms-1" style="font-size:0.55rem;" onclick="uncheckPelanggaran(${cb.value})" title="Batalkan pilihan ini"></button>
                </span>
            `;
        }
    });

    document.getElementById('multiCountBadge').innerText = count;
    document.getElementById('multiPoinBadge').innerText = '-' + totalPoin + ' Poin';

    if (pillsContainer) {
        pillsContainer.innerHTML = pillsHtml;
        if (count > 0) {
            pillsContainer.classList.remove('d-none');
        } else {
            pillsContainer.classList.add('d-none');
        }
    }

    if (currentInputMode === 'multi') {
        const submitBtn = document.getElementById('btnSubmitPelanggaran');
        if (count > 0) {
            submitBtn.innerHTML = `<i class="bi bi-save me-1"></i> Simpan (${count} Pelanggaran Sekaligus &bull; -${totalPoin} Poin)`;
        } else {
            submitBtn.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Catatan Pelanggaran';
        }
    }
}

function uncheckPelanggaran(id) {
    const cb = document.getElementById('check_pel_' + id);
    if (cb) {
        cb.checked = false;
        onPelanggaranCheckboxChange(cb);
    }
}

function resetMultiChecklist() {
    document.querySelectorAll('input[name="kategori_ids[]"]:checked').forEach(cb => {
        cb.checked = false;
        const card = document.getElementById('label_pel_' + cb.value);
        if (card) {
            card.classList.remove('is-checked');
        }
    });
    updateMultiSummaryUI();
}

function filterKatChecklist(cat, btn) {
    activeChecklistCat = cat;
    document.querySelectorAll('.cat-pill').forEach(b => {
        b.classList.remove('active');
        b.classList.add('btn-outline-secondary');
    });
    if (btn) {
        btn.classList.add('active');
        btn.classList.remove('btn-outline-secondary');
    }
    applyChecklistFilter();
}

function onSearchChecklistPelanggaran(q) {
    applyChecklistFilter();
}

function applyChecklistFilter() {
    const q = (document.getElementById('filterPelanggaranChecklist').value || '').toLowerCase().trim();
    const items = document.querySelectorAll('.pel-item-card');
    let visibleCount = 0;

    items.forEach(card => {
        const itemCat = card.dataset.prefix || '';
        const searchData = card.dataset.search || '';

        let matchCat = (activeChecklistCat === 'all' || itemCat === activeChecklistCat);
        let matchText = (!q || searchData.includes(q));

        if (matchCat && matchText) {
            card.classList.remove('d-none');
            visibleCount++;
        } else {
            card.classList.add('d-none');
        }
    });

    document.querySelectorAll('.kat-group-section').forEach(group => {
        const visibleItems = group.querySelectorAll('.pel-item-card:not(.d-none)');
        if (visibleItems.length === 0) {
            group.classList.add('d-none');
        } else {
            group.classList.remove('d-none');
        }
    });

    const noRes = document.getElementById('noResultsChecklist');
    if (noRes) {
        if (visibleCount === 0) {
            noRes.classList.remove('d-none');
        } else {
            noRes.classList.add('d-none');
        }
    }
}

function validateFormPelanggaran() {
    const sId = document.getElementById('hiddenSiswaId').value;
    if (!sId) {
        alert('⚠️ Harap pilih murid terlebih dahulu dari daftar pencarian.');
        const inputCari = document.getElementById('inputCariSiswa');
        if (inputCari) inputCari.focus();
        bukaDropdownSiswa();
        return false;
    }

    if (currentInputMode === 'single') {
        const val = document.getElementById('selectKategori').value;
        if (!val) {
            alert('⚠️ Harap pilih salah satu jenis pelanggaran.');
            document.getElementById('selectKategori').focus();
            return false;
        }
    } else {
        const checked = document.querySelectorAll('input[name="kategori_ids[]"]:checked');
        if (checked.length === 0) {
            alert('⚠️ Harap centang minimal 1 jenis pelanggaran pada Multi Checklist.');
            const filterInput = document.getElementById('filterPelanggaranChecklist');
            if (filterInput) filterInput.focus();
            return false;
        }
    }
    return true;
}

// Tutup dropdown saat klik di luar
document.addEventListener('click', function(e) {
    const box = document.getElementById('dropdownListSiswa');
    const input = document.getElementById('inputCariSiswa');
    const select = document.getElementById('filterKelasSiswa');
    if (!box || !input) return;
    
    if (!box.contains(e.target) && e.target !== input && e.target !== select) {
        tutupDropdownSiswa();
    }
});

// Inisialisasi awal
document.addEventListener('DOMContentLoaded', function() {
    const preId = <?= $preselected_id ?>;
    if (preId > 0) {
        pilihSiswa(preId);
    } else {
        const filtered = getFilteredSiswa();
        updateCounterDisplay(filtered);
    }
});

function updatePreview() {
    const sel = document.getElementById('selectKategori');
    const box = document.getElementById('previewBox');
    const val = sel.value;

    if (!val || !metaPelanggaran[val]) {
        box.classList.add('d-none');
        return;
    }

    const item = metaPelanggaran[val];
    document.getElementById('prevKode').innerText = item.kode;
    document.getElementById('prevNama').innerText = item.nama;
    document.getElementById('prevKategori').innerText = item.kategori;
    document.getElementById('prevPoin').innerText = '-' + item.poin + ' Poin';

    const prevTingkat = document.getElementById('prevTingkat');
    prevTingkat.innerText = item.tingkat;
    prevTingkat.className = 'badge ' + (
        item.tingkat === 'Ringan' ? 'bg-info text-dark' :
        item.tingkat === 'Sedang' ? 'bg-warning text-dark' : 'bg-danger text-white'
    );

    box.classList.remove('d-none');
}
</script>

<?php include 'includes/footer.php'; ?>
