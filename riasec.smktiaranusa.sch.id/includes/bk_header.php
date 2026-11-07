<?php
// includes/bk_header.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Portal BK | SMK Tiara Nusa' ?></title>
    
    <link rel="icon" href="../../assets/img/favicon.ico" type="image/x-icon">
    
    <!-- Font Inter untuk kesan profesional dan formal -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS v4 via CDN -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    
    <!-- Alpine.js untuk interaktivitas Sidebar Mobile -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style type="text/tailwindcss">
        @theme {
            --font-inter: 'Inter', sans-serif;
            --color-primary: #1e3a8a; /* Blue 900 - Biru Korporat Gelap */
            --color-primary-light: #1e40af; /* Blue 800 */
        }
        body { 
            font-family: var(--font-inter); 
            background-color: #f1f5f9; /* Slate 50 - Latar belakang abu-abu sangat muda */
            color: #334155; 
        }
    </style>
</head>
<!-- Alpine.js state untuk mengatur buka/tutup sidebar di mobile -->
<body class="min-h-screen flex antialiased" x-data="{ sidebarOpen: false }">