document.addEventListener("DOMContentLoaded", function () {

    const sidebar = document.querySelector(".sidebar");
    const overlay = document.querySelector(".sidebar-overlay");
    const toggleBtn = document.querySelector(".menu-toggle");

    const dropdown = document.querySelector(".user-dropdown");
    const dropdownMenu = document.getElementById("userDropdown");

    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    */

    window.toggleSidebar = function () {

        if (!sidebar) return;

        sidebar.classList.toggle("show");

        if (overlay) {

            overlay.classList.toggle("show");

        }

    };

    window.closeSidebar = function () {

        if (!sidebar) return;

        sidebar.classList.remove("show");

        if (overlay) {

            overlay.classList.remove("show");

        }

    };

    /*
    |--------------------------------------------------------------------------
    | Dropdown User
    |--------------------------------------------------------------------------
    */

    window.toggleDropdown = function () {

        if (!dropdownMenu) return;

        dropdownMenu.classList.toggle("show");

    };

    /*
    |--------------------------------------------------------------------------
    | Klik di luar Sidebar & Dropdown
    |--------------------------------------------------------------------------
    */

    document.addEventListener("click", function (e) {

        if (
            sidebar &&
            toggleBtn &&
            sidebar.classList.contains("show") &&
            !sidebar.contains(e.target) &&
            !toggleBtn.contains(e.target)
        ) {

            closeSidebar();

        }

        if (
            dropdown &&
            dropdownMenu &&
            !dropdown.contains(e.target)
        ) {

            dropdownMenu.classList.remove("show");

        }

    });

    /*
    |--------------------------------------------------------------------------
    | Overlay
    |--------------------------------------------------------------------------
    */

    if (overlay) {

        overlay.addEventListener("click", closeSidebar);

    }

    /*
    |--------------------------------------------------------------------------
    | ESC
    |--------------------------------------------------------------------------
    */

    document.addEventListener("keydown", function (e) {

        if (e.key === "Escape") {

            closeSidebar();

            if (dropdownMenu) {

                dropdownMenu.classList.remove("show");

            }

        }

    });

    /*
    |--------------------------------------------------------------------------
    | Resize Desktop
    |--------------------------------------------------------------------------
    */

    window.addEventListener("resize", function () {

        if (window.innerWidth > 992) {

            closeSidebar();

        }

    });

    /*
    |--------------------------------------------------------------------------
    | Jam Realtime
    |--------------------------------------------------------------------------
    */

    const jam = document.getElementById("jamRealtime");

    if (jam) {

        function updateJam() {

            jam.innerHTML =
                new Date().toLocaleTimeString("id-ID") + " WIB";

        }

        updateJam();

        setInterval(updateJam, 1000);

    }

});