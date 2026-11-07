<?php
require 'config.php';
require 'includes/auth.php';
if (!isAdmin()) {
    redirect('index.php', 'Akses ditolak: Halaman Panel Guru hanya dapat diakses oleh Administrator.', 'danger');
}
$judul_halaman = 'Panel Guru & Wali Kelas';

$filter_jurusan = clean($koneksi, $_GET['jurusan'] ?? '');
$filter_role = clean($koneksi, $_GET['role'] ?? '');

$sql = "SELECT u.*, k.nama_kelas, k.jurusan 
        FROM users u 
        LEFT JOIN kelas k ON u.kelas_id = k.id 
        WHERE u.role IN ('kepala_sekolah', 'guru_bk', 'wali_kelas', 'guru')";

if ($filter_jurusan !== '') {
    $sql .= " AND k.jurusan = '$filter_jurusan'";
}
if ($filter_role === 'wali_kelas') {
    $sql .= " AND (u.role = 'wali_kelas' OR u.kelas_id IS NOT NULL)";
} elseif ($filter_role !== '') {
    $sql .= " AND u.role = '$filter_role'";
}
$sql .= " ORDER BY FIELD(u.role, 'kepala_sekolah', 'guru_bk', 'wali_kelas', 'guru'), k.jurusan, k.nama_kelas, u.nama_lengkap";

$guru_list = mysqli_query($koneksi, $sql);

// Statistik Ringkas Guru & Tenaga Kependidikan
$stat_guru = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT 
        COUNT(*) AS total_guru,
        SUM(CASE WHEN role='kepala_sekolah' THEN 1 ELSE 0 END) AS total_kepsek,
        SUM(CASE WHEN role='guru_bk' THEN 1 ELSE 0 END) AS total_bk,
        SUM(CASE WHEN u.kelas_id IS NOT NULL THEN 1 ELSE 0 END) AS total_wali,
        SUM(CASE WHEN k.jurusan='MPLB' AND u.kelas_id IS NOT NULL THEN 1 ELSE 0 END) AS wali_mplb,
        SUM(CASE WHEN k.jurusan='TJKT' AND u.kelas_id IS NOT NULL THEN 1 ELSE 0 END) AS wali_tjkt
    FROM users u
    LEFT JOIN kelas k ON u.kelas_id = k.id
    WHERE u.role IN ('kepala_sekolah', 'guru_bk', 'wali_kelas', 'guru')
"));

include 'includes/header.php';
?>

<style>
@media print {
    #sidebar, .topbar, .btn, .no-print, .input-group, form { display: none !important; }
    #content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
    .card { border: 1px solid #ddd !important; box-shadow: none !important; }
    .table thead { background: #f8fafc !important; color: #000 !important; }
    .print-header { display: block !important; }
}
.print-header { display: none; }
</style>

<!-- Print Header -->
<div class="print-header mb-4 text-center border-bottom pb-3">
    <h4 class="fw-bold mb-0">SMKS TIARA NUSA</h4>
    <p class="mb-1 text-muted">Daftar Akun Login Guru &amp; Wali Kelas &bull; Aplikasi E-Point Disiplin &amp; Prestasi</p>
    <small class="text-secondary">Dicetak pada: <?= date('d/m/Y H:i') ?></small>
</div>

<!-- Header Card -->
<div class="card p-3 p-md-4 mb-3 border-0 shadow-sm" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color:#fff; border-radius:14px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <span class="badge bg-primary px-2 py-1 fw-bold"><i class="bi bi-person-badge-fill me-1"></i> Data Pendidik</span>
                <span class="badge bg-white text-dark fw-semibold"><?= (int)$stat_guru['total_guru'] ?> Akun Terdaftar</span>
            </div>
            <h4 class="fw-bold mb-1">Panel Akun Guru &amp; Wali Kelas</h4>
            <p class="mb-0 text-white-50 small">
                Daftar informasi login (Username &amp; Password) seluruh Guru BK dan Wali Kelas SMKS Tiara Nusa.
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap no-print">
            <button type="button" class="btn btn-light btn-sm fw-semibold shadow-sm" onclick="salinSemuaAkun()">
                <i class="bi bi-clipboard-check text-primary me-1"></i> Salin Semua (Format WA)
            </button>
            <button type="button" class="btn btn-outline-light btn-sm fw-semibold" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Cetak / Print
            </button>
            <?php if (isAdmin()): ?>
            <a href="users.php" class="btn btn-primary btn-sm fw-semibold shadow-sm">
                <i class="bi bi-person-gear me-1"></i> Kelola Master User
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Statistik Ringkas Guru -->
<div class="row g-3 mb-3 no-print">
    <div class="col-sm-6 col-md-3">
        <div class="card p-3 border-0 shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small">Total Pendidik</div>
                    <h3 class="fw-bold mb-0 text-dark"><?= (int)$stat_guru['total_guru'] ?></h3>
                </div>
                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:44px; height:44px;">
                    <i class="bi bi-people-fill fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card p-3 border-0 shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small">Guru Bimbingan Konseling</div>
                    <h3 class="fw-bold mb-0 text-primary"><?= (int)$stat_guru['total_bk'] ?></h3>
                </div>
                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:44px; height:44px;">
                    <i class="bi bi-heart-pulse-fill fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card p-3 border-0 shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small">Wali Kelas MPLB</div>
                    <h3 class="fw-bold mb-0" style="color:#0f766e;"><?= (int)$stat_guru['wali_mplb'] ?> <small class="fs-6 fw-normal text-muted">Rombel</small></h3>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:44px; height:44px; background:#f0fdfa; color:#0f766e;">
                    <i class="bi bi-briefcase-fill fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card p-3 border-0 shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small">Wali Kelas TJKT</div>
                    <h3 class="fw-bold mb-0" style="color:#4338ca;"><?= (int)$stat_guru['wali_tjkt'] ?> <small class="fs-6 fw-normal text-muted">Rombel</small></h3>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:44px; height:44px; background:#eef2ff; color:#4338ca;">
                    <i class="bi bi-hdd-network-fill fs-5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Utama Akun Guru -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px; overflow:hidden;">
    <div class="card-header bg-white border-bottom py-3 px-3 px-md-4 d-flex flex-wrap justify-content-between align-items-center gap-3 no-print">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center mb-0">
            <div class="input-group input-group-sm" style="width: 240px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="filterInput" class="form-control bg-light border-start-0" placeholder="Ketik nama, username, kelas..." onkeyup="filterTable()">
            </div>

            <select name="jurusan" class="form-select form-select-sm" style="width: 140px;" onchange="this.form.submit()">
                <option value="">Semua Jurusan</option>
                <option value="MPLB" <?= $filter_jurusan==='MPLB'?'selected':'' ?>>MPLB</option>
                <option value="TJKT" <?= $filter_jurusan==='TJKT'?'selected':'' ?>>TJKT</option>
            </select>

            <select name="role" class="form-select form-select-sm" style="width: 140px;" onchange="this.form.submit()">
                <option value="">Semua Peran</option>
                <option value="kepala_sekolah" <?= $filter_role==='kepala_sekolah'?'selected':'' ?>>Kepala Sekolah</option>
                <option value="guru_bk" <?= $filter_role==='guru_bk'?'selected':'' ?>>Guru BK</option>
                <option value="wali_kelas" <?= $filter_role==='wali_kelas'?'selected':'' ?>>Wali Kelas</option>
            </select>

            <?php if ($filter_jurusan !== '' || $filter_role !== ''): ?>
                <a href="guru.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle me-1"></i> Reset</a>
            <?php endif; ?>
        </form>

        <div class="small text-muted">
            Format bawaan: <code>[username]123</code>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tabelGuru">
            <thead class="table-light text-secondary" style="font-size:0.82rem; letter-spacing:0.3px;">
                <tr>
                    <th class="ps-3 ps-md-4" style="width:50px;">No</th>
                    <th>Nama Pendidik / Pimpinan</th>
                    <th>Peran &amp; Penugasan</th>
                    <th>Jurusan</th>
                    <th>Username</th>
                    <th>Password Default</th>
                    <?php if (isAdmin()): ?>
                    <th class="text-end pe-3 pe-md-4 no-print">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody style="font-size:0.9rem;">
                <?php 
                $no = 1;
                while ($g = mysqli_fetch_assoc($guru_list)): 
                    $is_me = ($g['id'] == $_SESSION['user_id']);
                    $default_pass = !empty($g['password_plain']) ? $g['password_plain'] : ($g['username'] . '123');
                    if (empty($g['password_plain'])) {
                        if ($g['username'] === 'gurubk') $default_pass = 'gurubk123';
                        elseif ($g['username'] === 'walikelas') $default_pass = 'walikelas123';
                        elseif ($g['username'] === 'kepsek') $default_pass = 'kepsek123';
                    }

                    $role_badge = [
                        'kepala_sekolah' => '<span class="badge bg-warning text-dark border border-warning-subtle fw-bold"><i class="bi bi-mortarboard-fill me-1"></i>Kepala Sekolah</span>',
                        'guru_bk'        => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-heart-pulse me-1"></i>Guru BK</span>',
                        'wali_kelas'     => '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><i class="bi bi-person-badge me-1"></i>Wali Kelas</span>',
                        'guru'           => '<span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-person me-1"></i>Guru Mapel</span>'
                    ][$g['role']] ?? '<span class="badge bg-secondary">' . htmlspecialchars($g['role']) . '</span>';
                ?>
                <tr class="row-guru <?= $is_me ? 'table-primary-subtle' : '' ?>">
                    <td class="ps-3 ps-md-4 text-muted small"><?= $no++ ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-circle rounded-circle bg-light border text-primary fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px; height:36px;">
                                <?php if ($g['role'] === 'kepala_sekolah'): ?>
                                    <i class="bi bi-mortarboard-fill text-warning"></i>
                                <?php else: ?>
                                    <i class="bi bi-person-fill"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="fw-bold text-dark nama-guru">
                                    <?= htmlspecialchars($g['nama_lengkap']) ?>
                                    <?php if ($is_me): ?>
                                        <span class="badge bg-primary ms-1" style="font-size:0.7rem;">Akun Anda</span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted">ID: #<?= $g['id'] ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <?= $role_badge ?>
                            <?php if ($g['role'] === 'guru_bk' && !empty($g['nama_kelas'])): ?>
                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><i class="bi bi-person-badge me-1"></i>Wali Kelas</span>
                            <?php endif; ?>
                            <?php if (!empty($g['nama_kelas'])): ?>
                                <span class="badge bg-dark text-white fw-semibold kelas-guru"><?= htmlspecialchars($g['nama_kelas']) ?></span>
                            <?php elseif ($g['role'] === 'wali_kelas'): ?>
                                <span class="badge bg-light text-muted border">Perwalian Demo</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php if ($g['role'] === 'kepala_sekolah'): ?>
                            <span class="badge bg-light text-dark border"><i class="bi bi-building-gear me-1"></i>Pimpinan</span>
                        <?php elseif ($g['role'] === 'guru_bk' && empty($g['jurusan'])): ?>
                            <span class="badge bg-light text-muted border">Semua Jurusan</span>
                        <?php else: ?>
                            <?= badgeJurusan($g['jurusan']) ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-inline-flex align-items-center gap-1 bg-light border rounded px-2 py-1">
                            <code class="text-primary fw-bold username-val"><?= htmlspecialchars($g['username']) ?></code>
                            <button type="button" class="btn btn-link btn-sm p-0 text-muted no-print" onclick="copyText('<?= htmlspecialchars($g['username']) ?>', this)" title="Salin Username">
                                <i class="bi bi-copy" style="font-size:0.78rem;"></i>
                            </button>
                        </div>
                    </td>
                    <td>
                        <?php if (isAdmin() || $is_me): ?>
                            <div class="d-inline-flex align-items-center gap-1 bg-light border rounded px-2 py-1">
                                <code class="text-dark fw-semibold pass-val"><?= htmlspecialchars($default_pass) ?></code>
                                <button type="button" class="btn btn-link btn-sm p-0 text-muted no-print" onclick="copyText('<?= htmlspecialchars($default_pass) ?>', this)" title="Salin Password">
                                    <i class="bi bi-copy" style="font-size:0.78rem;"></i>
                                </button>
                            </div>
                        <?php else: ?>
                            <span class="text-muted small"><em>(Terselubung)</em></span>
                        <?php endif; ?>
                    </td>
                    <?php if (isAdmin()): ?>
                    <td class="text-end pe-3 pe-md-4 no-print">
                        <a href="users.php" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size:0.8rem;" title="Edit di Kelola User">
                            <i class="bi bi-pencil-square me-1"></i> Edit
                        </a>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterTable() {
    const query = document.getElementById('filterInput').value.toLowerCase();
    const rows = document.querySelectorAll('#tabelGuru tbody tr.row-guru');
    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        r.style.display = text.includes(query) ? '' : 'none';
    });
}

function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const icon = btn.querySelector('i');
        const origClass = icon.className;
        icon.className = 'bi bi-check-lg text-success';
        setTimeout(() => { icon.className = origClass; }, 1500);
    });
}

function salinSemuaAkun() {
    let text = "📋 DAFTAR AKUN LOGIN E-POINT SMKS TIARA NUSA\n";
    text += "========================================\n\n";
    const rows = document.querySelectorAll('#tabelGuru tbody tr.row-guru');
    rows.forEach((r, idx) => {
        const nama = r.querySelector('.nama-guru')?.innerText.replace('Akun Anda', '').trim() || '';
        const kelas = r.querySelector('.kelas-guru')?.innerText.trim() || '';
        const username = r.querySelector('.username-val')?.innerText.trim() || '';
        const pass = r.querySelector('.pass-val')?.innerText.trim() || '[username]123';
        const peran = kelas ? `Wali Kelas ${kelas}` : (username === 'gurubk' ? 'Guru BK' : 'Wali Kelas Demo');
        text += `${idx + 1}. ${nama} (${peran})\n   Username: ${username} | Password: ${pass}\n\n`;
    });
    text += "Link Akses: " + window.location.origin + "/epoint/login.php\n";
    text += "Catatan: Harap segera ganti password default setelah berhasil login.";

    navigator.clipboard.writeText(text).then(() => {
        alert("✅ Seluruh data akun guru berhasil disalin ke clipboard! Anda dapat langsung menempelkannya (paste) ke WhatsApp atau dokumen catatan.");
    });
}
</script>

<?php include 'includes/footer.php'; ?>
