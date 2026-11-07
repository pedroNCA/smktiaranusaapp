<?php
require 'config.php';
require 'includes/auth.php';
if (isOsis() || isKepalaSekolah()) {
    redirect('index.php', 'Akses ditolak: Pencatatan prestasi siswa hanya dapat dilakukan oleh Dewan Guru.', 'danger');
}
$judul_halaman = 'Input Prestasi';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $siswa_id = (int)$_POST['siswa_id'];
    $kategori_id = (int)$_POST['kategori_id'];
    $tanggal = clean($koneksi, $_POST['tanggal']);
    $ket = clean($koneksi, $_POST['keterangan']);
    $petugas = $_SESSION['user_id'];

    if (!$siswa_id || !$kategori_id || !$tanggal) {
        redirect('input_prestasi.php', 'Semua field wajib diisi.', 'danger');
    }
    mysqli_query($koneksi, "INSERT INTO riwayat_prestasi (siswa_id, kategori_id, tanggal, keterangan, petugas_id)
        VALUES ($siswa_id, $kategori_id, '$tanggal', '$ket', $petugas)");
    redirect('input_prestasi.php', 'Data prestasi berhasil dicatat.');
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

$kategori_list = mysqli_query($koneksi, "SELECT * FROM kategori_prestasi ORDER BY poin");

$riwayat = mysqli_query($koneksi, "SELECT rp.*, s.nama, s.nis, s.nisn, kp.nama_prestasi, kp.poin, kp.tingkat
    FROM riwayat_prestasi rp
    JOIN siswa s ON rp.siswa_id=s.id
    JOIN kategori_prestasi kp ON rp.kategori_id=kp.id
    ORDER BY rp.created_at DESC LIMIT 15");

include 'includes/header.php';
?>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card p-3 border-0 shadow-sm">
            <h6 class="fw-bold mb-3 text-success"><i class="bi bi-trophy-fill me-1"></i> Form Input Prestasi</h6>
            <form method="POST" id="formPrestasi" onsubmit="return validateFormPrestasi()">
                <!-- Pilih Siswa (Sesuai Foto Kedua: Dropdown Kelas Biru + Input Cari Murid dengan Counter) -->
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary d-flex justify-content-between align-items-center mb-1">
                        <span><i class="bi bi-person-check text-success me-1"></i> Pilih Siswa</span>
                        <span class="badge bg-light text-muted fw-normal" style="font-size:0.75rem;">Pilih kelas &amp; cari nama murid</span>
                    </label>

                    <div class="position-relative">
                        <div class="d-flex align-items-center gap-2">
                            <!-- Dropdown Kelas (Rounded Blue Outline persis Foto Kedua) -->
                            <div style="min-width: 140px; flex-shrink: 0;">
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

                            <!-- Input Cari Nama Murid dengan counter (contoh: 425/425 persis Foto Kedua) -->
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
                            </div>
                        </div>

                        <!-- Hidden Input untuk form submission -->
                        <input type="hidden" name="siswa_id" id="hiddenSiswaId" required value="<?= $preselected_siswa ? $preselected_siswa['id'] : '' ?>">

                        <!-- Dropdown Popover List Hasil Pencarian Siswa -->
                        <div id="dropdownListSiswa" class="dropdown-siswa-box position-absolute start-0 end-0 bg-white border rounded-3 shadow-lg d-none" 
                             style="top: 48px; z-index: 1050; max-height: 250px; overflow-y: auto;">
                            <div id="listSiswaContainer" class="p-1">
                                <!-- Diisi secara dinamis oleh JavaScript -->
                            </div>
                        </div>
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
                <div class="mb-2">
                    <label class="form-label">Jenis Prestasi</label>
                    <select name="kategori_id" class="form-select" required>
                        <option value="">-- Pilih Prestasi --</option>
                        <?php while ($k = mysqli_fetch_assoc($kategori_list)): ?>
                            <option value="<?= $k['id'] ?>"><?= htmlspecialchars("{$k['nama_prestasi']} ({$k['tingkat']}, +{$k['poin']} poin)") ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan (opsional)</label>
                    <textarea name="keterangan" class="form-control" rows="3" placeholder="Detail prestasi..."></textarea>
                </div>
                <button type="submit" class="btn btn-success w-100"><i class="bi bi-save"></i> Simpan Prestasi</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card p-3">
            <h6 class="fw-bold mb-3">Riwayat Terbaru</h6>
            <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th>Tanggal</th><th>Siswa</th><th>Prestasi</th><th>Poin</th></tr></thead>
                <tbody>
                <?php if (mysqli_num_rows($riwayat)===0): ?>
                    <tr><td colspan="4" class="text-center text-muted">Belum ada data</td></tr>
                <?php endif; ?>
                <?php while ($r = mysqli_fetch_assoc($riwayat)): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($r['nama']) ?></td>
                        <td><?= htmlspecialchars($r['nama_prestasi']) ?></td>
                        <td class="text-success fw-bold">+<?= $r['poin'] ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>

<style>
.item-pilih-siswa:hover {
    background-color: #f1f5f9;
}
.dropdown-siswa-box::-webkit-scrollbar {
    width: 6px;
}
.dropdown-siswa-box::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
</style>

<script>
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
                    <div class="avatar-circle rounded-circle bg-light border text-success fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px; height:32px; font-size:0.82rem;">
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

function validateFormPrestasi() {
    const sId = document.getElementById('hiddenSiswaId').value;
    if (!sId) {
        alert('⚠️ Harap pilih murid terlebih dahulu dari daftar pencarian.');
        const inputCari = document.getElementById('inputCariSiswa');
        if (inputCari) inputCari.focus();
        bukaDropdownSiswa();
        return false;
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
</script>
