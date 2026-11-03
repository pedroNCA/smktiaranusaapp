</main> <!-- Penutup Main Content Wrapper dari header.php -->

<!-- Script Global UI -->
<script>
    // Logika Toggle Sidebar Mobile
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) sidebar.classList.toggle('active');
    }

    // Otomatis menutup sidebar jika klik di luar area saat terbuka di mobile
    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.querySelector('button[onclick="toggleSidebar()"]');

        if (
            sidebar && toggleBtn &&
            sidebar.classList.contains('active') &&
            !sidebar.contains(e.target) &&
            !toggleBtn.contains(e.target)
        ) {
            sidebar.classList.remove('active');
        }
    });
</script>
</body>

</html>