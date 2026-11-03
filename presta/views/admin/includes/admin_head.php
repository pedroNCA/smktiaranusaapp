<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title>Panel Admin - PRESTA</title>

<!-- Favicon -->
<link rel="icon" type="image/png" href="assets/img/favicon.ico">

<!-- Tailwind CSS v3 CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Poppins', 'sans-serif']
                },
                colors: {
                    brand: {
                        50: '#eff6ff',
                        100: '#dbeafe',
                        500: '#3b82f6',
                        600: '#2563eb',
                        700: '#1d4ed8'
                    }
                }
            }
        }
    }
</script>

<!-- Alpine.js Core -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<!-- DataTables & jQuery -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- SweetAlert2 & FontAwesome -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<!-- Custom Styles -->
<link rel="stylesheet" href="assets/css/admin_style.css">
<link rel="stylesheet" href="assets/css/style_dashboard.css">

<style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8fafc;
    }
</style>