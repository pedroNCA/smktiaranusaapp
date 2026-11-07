    <div class="text-center text-muted small mt-4 mb-2">
        &copy; <?= date('Y') ?> SMKS Tiara Nusa &bull; Aplikasi E-Point Pelanggaran &amp; Prestasi Siswa
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="assets/tom-select.complete.min.js"></script>
<script>
function toggleSidebar() {
    var sb = document.getElementById('sidebar');
    var bd = document.getElementById('sidebarBackdrop');
    if (sb) sb.classList.toggle('show');
    if (bd) bd.classList.toggle('show');
}

// Inisialisasi Searchable Dropdown (Tom Select)
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.select-search, .select-siswa').forEach(function(el) {
        if (el.tomselect) return;
        var placeholderText = el.getAttribute('data-placeholder') || '-- Ketik nama siswa, NIS, atau kelas... --';
        var ts = new TomSelect(el, {
            create: false,
            maxOptions: 600,
            placeholder: placeholderText,
            allowEmptyOption: false,
            plugins: ['clear_button'],
            render: {
                no_results: function(data, escape) {
                    return '<div class="no-results p-3 text-muted small text-center"><i class="bi bi-search me-1"></i> Data "<strong>' + escape(data.input) + '</strong>" tidak ditemukan</div>';
                }
            }
        });

        // Jika select memiliki data-auto-submit="true"
        if (el.getAttribute('data-auto-submit') === 'true') {
            ts.on('change', function(value) {
                if (value) {
                    el.form.submit();
                }
            });
        }
    });
});
</script>

<!-- Modal Peringatan Inactivity Timeout -->
<div class="modal fade" id="modalSessionTimeout" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalSessionTimeoutLabel" aria-hidden="true" style="z-index: 99999;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header border-0 bg-warning bg-opacity-10 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-warning bg-opacity-25 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-clock-history text-warning fs-5"></i>
                    </div>
                    <h5 class="modal-title fw-bold text-dark fs-6" id="modalSessionTimeoutLabel">Peringatan Sesi Berakhir</h5>
                </div>
            </div>
            <div class="modal-body pt-3 pb-3">
                <p class="text-secondary small mb-3">
                    Anda tidak melakukan aktivitas selama beberapa waktu. Demi keamanan data sistem, sesi Anda akan segera berakhir dalam:
                </p>
                <div class="text-center py-2 px-3 bg-light rounded-3 border mb-3">
                    <span id="sessionCountdownText" class="display-6 fw-bold text-danger">60</span>
                    <span class="text-muted small ms-1">detik</span>
                </div>
                <p class="text-muted small mb-0">
                    Klik <strong>Tetap Login</strong> untuk melanjutkan sesi Anda atau <strong>Logout</strong> untuk keluar sekarang.
                </p>
            </div>
            <div class="modal-footer border-0 bg-light pt-2 pb-3 px-3 d-flex justify-content-end gap-2">
                <a href="logout.php" class="btn btn-sm btn-outline-secondary px-3 py-1.5" style="border-radius: 8px;">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
                <button type="button" class="btn btn-sm btn-primary fw-semibold px-3 py-1.5" id="btnTetapLogin" style="border-radius: 8px;">
                    <i class="bi bi-check-circle me-1"></i> Tetap Login
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Inactivity Timeout Script
(function() {
    var timeoutSeconds = <?= (int)(defined('SESSION_TIMEOUT_SECONDS') ? SESSION_TIMEOUT_SECONDS : 900) ?>;
    var warningSeconds = 60; // Peringatan muncul 60 detik sebelum sesi berakhir
    var warningThreshold = Math.max(10, timeoutSeconds - warningSeconds);
    
    var lastActivityTime = Date.now();
    var modalElement = document.getElementById('modalSessionTimeout');
    var countdownText = document.getElementById('sessionCountdownText');
    var btnTetapLogin = document.getElementById('btnTetapLogin');
    var modalInstance = null;
    var isWarningActive = false;
    var isRedirecting = false;
    var lastPingTime = Date.now();

    function getModal() {
        if (!modalInstance && modalElement && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            modalInstance = new bootstrap.Modal(modalElement, {
                backdrop: 'static',
                keyboard: false
            });
        }
        return modalInstance;
    }

    function recordActivity() {
        if (isWarningActive || isRedirecting) return;
        lastActivityTime = Date.now();

        // Throttle ping ke server: setiap 2 menit jika ada aktivitas kontinu
        var now = Date.now();
        if (now - lastPingTime > 120000) {
            lastPingTime = now;
            pingServer();
        }
    }

    function pingServer(onSuccess) {
        fetch('ping_session.php', { method: 'GET', credentials: 'same-origin' })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.status === 'expired') {
                    redirectTimeout();
                } else if (typeof onSuccess === 'function') {
                    onSuccess();
                }
            })
            .catch(function() {});
    }

    function redirectTimeout() {
        if (isRedirecting) return;
        isRedirecting = true;
        window.location.href = 'logout.php?pesan=timeout';
    }

    if (btnTetapLogin) {
        btnTetapLogin.addEventListener('click', function() {
            btnTetapLogin.disabled = true;
            btnTetapLogin.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyegarkan...';
            
            pingServer(function() {
                lastActivityTime = Date.now();
                lastPingTime = Date.now();
                isWarningActive = false;
                btnTetapLogin.disabled = false;
                btnTetapLogin.innerHTML = '<i class="bi bi-check-circle me-1"></i> Tetap Login';
                var m = getModal();
                if (m) m.hide();
            });
        });
    }

    ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart'].forEach(function(evt) {
        window.addEventListener(evt, recordActivity, { passive: true });
    });

    setInterval(function() {
        if (isRedirecting) return;
        var now = Date.now();
        var idleSeconds = Math.floor((now - lastActivityTime) / 1000);
        var remainingSeconds = Math.max(0, timeoutSeconds - idleSeconds);

        if (idleSeconds >= timeoutSeconds) {
            redirectTimeout();
        } else if (idleSeconds >= warningThreshold) {
            if (!isWarningActive) {
                isWarningActive = true;
                var m = getModal();
                if (m) m.show();
            }
            if (countdownText) {
                countdownText.textContent = remainingSeconds;
            }
        } else if (isWarningActive && idleSeconds < warningThreshold) {
            isWarningActive = false;
            var m = getModal();
            if (m) m.hide();
        }
    }, 1000);
})();
</script>
</body>
</html>
