// ===========================
// SIDEBAR
// ===========================
window.toggleSidebar = function (event) {

    if (event) {
        event.stopPropagation();
    }

    const sidebar = document.getElementById("sidebar");

    if (sidebar) {
        sidebar.classList.toggle("active");
    }

};

// ===========================
// USER MENU
// ===========================
window.toggleUserMenu = function (event) {

    if (event) {
        event.stopPropagation();
    }

    const dropdown = document.getElementById("userDropdown");

    if (!dropdown) return;

    dropdown.classList.toggle("show");

};

// ===========================
// TUTUP SIDEBAR & DROPDOWN
// ===========================
document.addEventListener("click", function (event) {

    // Tutup dropdown profil
    const dropdown = document.getElementById("userDropdown");
    const profileTrigger = document.getElementById("profileTrigger");

    if (
        dropdown &&
        !dropdown.contains(event.target) &&
        profileTrigger &&
        !profileTrigger.contains(event.target)
    ) {
        dropdown.classList.remove("show");
    }

    // Tutup sidebar jika klik di luar sidebar
    const sidebar = document.getElementById("sidebar");
    const menuToggle = document.querySelector(".menu-toggle");

    if (
        window.innerWidth <= 768 &&
        sidebar &&
        sidebar.classList.contains("active") &&
        !sidebar.contains(event.target) &&
        menuToggle &&
        !menuToggle.contains(event.target)
    ) {
        sidebar.classList.remove("active");
    }

});

// ===========================
// JAM REALTIME
// ===========================
document.addEventListener("DOMContentLoaded", function () {

    const jam = document.getElementById("jamRealtime");

    if (!jam) return;

    function updateJam() {

        const now = new Date();

        jam.innerHTML =
            now.toLocaleTimeString("id-ID") + " WIB";

    }

    updateJam();

    setInterval(updateJam, 1000);

});