<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($judul_halaman) ? $judul_halaman . ' - ' : '' ?>E-Point SMKS Tiara Nusa</title>

<!-- Google Fonts: Plus Jakarta Sans untuk Tipografi Standar Produk Modern -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">

<!-- Bootstrap 5 & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="assets/tom-select.bootstrap5.min.css">

<style>
    /* ==========================================================================
       GLOBAL DESIGN SYSTEM (BESPOKE, MODERN SAAS & HUMAN-CRAFTED)
       ========================================================================== */
    :root {
        --font-sans: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        --bg-body: #f8fafc;
        --sidebar-bg: #090d16;
        --sidebar-border: rgba(255, 255, 255, 0.07);
        --card-bg: #ffffff;
        --card-border: rgba(226, 232, 240, 0.85);
        --primary-blue: #2563eb;
        --primary-sky: #0ea5e9;
        --accent-emerald: #10b981;
        --accent-rose: #f43f5e;
        --accent-amber: #f59e0b;
        --text-main: #0f172a;
        --text-muted: #64748b;
    }

    * {
        box-sizing: border-box;
    }

    html, body {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    body { 
        background-color: var(--bg-body); 
        background-image: 
            radial-gradient(at 0% 0%, rgba(14, 165, 233, 0.04) 0px, transparent 50%),
            radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.04) 0px, transparent 50%);
        font-family: var(--font-sans); 
        color: var(--text-main);
        letter-spacing: -0.01em;
        min-height: 100vh;
    }

    /* Custom Modern Scrollbars */
    ::-webkit-scrollbar {
        width: 7px;
        height: 7px;
    }
    ::-webkit-scrollbar-track {
        background: transparent;
    }
    ::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* ==========================================================================
       SIDEBAR ULTRA-SLEEK OBSIDIAN
       ========================================================================== */
    #sidebar {
        width: 260px; 
        height: 100vh; 
        background: linear-gradient(180deg, #090d16 0%, #0d1527 50%, #080d1a 100%); 
        position: fixed; 
        top: 0; 
        left: 0; 
        z-index: 1045;
        border-right: 1px solid var(--sidebar-border);
        box-shadow: 4px 0 25px rgba(0, 0, 0, 0.15);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: left 0.28s cubic-bezier(0.16, 1, 0.3, 1), width 0.28s ease;
    }

    /* Brand Header */
    .sidebar-brand-card {
        padding: 22px 20px;
        border-bottom: 1px solid var(--sidebar-border);
        background: rgba(255, 255, 255, 0.02);
    }
    .brand-logo-ring {
        position: relative;
        width: 42px;
        height: 42px;
        border-radius: 12px;
        padding: 2px;
        background: linear-gradient(135deg, rgba(56, 189, 248, 0.8), rgba(99, 102, 241, 0.8));
        box-shadow: 0 0 16px rgba(56, 189, 248, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .brand-logo-img {
        width: 100%;
        height: 100%;
        border-radius: 10px;
        object-fit: cover;
        background: #fff;
    }
    .brand-name {
        color: #ffffff;
        font-weight: 800;
        font-size: 0.95rem;
        letter-spacing: -0.02em;
        line-height: 1.2;
    }
    .brand-subtag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 3px;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        color: #38bdf8;
    }
    .pulse-indicator {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: #38bdf8;
        box-shadow: 0 0 8px #38bdf8;
        display: inline-block;
        animation: pulseSlow 2s infinite ease-in-out;
    }
    @keyframes pulseSlow {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(0.85); }
    }

    /* Navigation Container */
    .sidebar-nav-container {
        flex: 1;
        overflow-y: auto;
        padding: 14px 10px;
    }
    .sidebar-section-label {
        font-size: 0.67rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #64748b;
        padding: 14px 14px 6px;
    }
    .sidebar-nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        margin-bottom: 4px;
        border-radius: 12px;
        color: #94a3b8;
        font-size: 0.88rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
    }
    .sidebar-nav-item:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.06);
        transform: translateX(3px);
    }
    .sidebar-nav-item.active {
        background: linear-gradient(135deg, rgba(37, 99, 235, 0.2), rgba(14, 165, 233, 0.12));
        border: 1px solid rgba(56, 189, 248, 0.32);
        color: #38bdf8 !important;
        font-weight: 600;
        box-shadow: 0 4px 18px rgba(14, 165, 233, 0.14);
    }
    .sidebar-nav-item.active::after {
        content: '';
        position: absolute;
        right: 12px;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: #38bdf8;
        box-shadow: 0 0 10px #38bdf8;
    }

    .nav-icon-box {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        background: rgba(255, 255, 255, 0.05);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
        transition: all 0.2s ease;
    }
    .sidebar-nav-item:hover .nav-icon-box {
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
    }
    .sidebar-nav-item.active .nav-icon-box {
        background: rgba(56, 189, 248, 0.22);
        color: #38bdf8;
    }
    .nav-icon-box.nav-danger { color: #fb7185; }
    .nav-icon-box.nav-success { color: #34d399; }
    .nav-icon-box.nav-info { color: #38bdf8; }

    .nav-badge-pill {
        margin-left: auto;
        font-size: 0.68rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 20px;
        background: rgba(56, 189, 248, 0.15);
        color: #38bdf8;
        border: 1px solid rgba(56, 189, 248, 0.25);
    }

    /* Sidebar Footer User Info */
    .sidebar-footer-card {
        padding: 16px;
        border-top: 1px solid var(--sidebar-border);
        background: rgba(0, 0, 0, 0.2);
    }
    .sidebar-user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: linear-gradient(135deg, #0d9488, #0284c7);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.95rem;
        flex-shrink: 0;
    }
    .sidebar-user-info {
        overflow: hidden;
        line-height: 1.25;
    }
    .sidebar-user-name {
        color: #f1f5f9;
        font-size: 0.84rem;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sidebar-user-role {
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    /* ==========================================================================
       MAIN CONTENT & TOPBAR ELEVATED FROSTED GLASS
       ========================================================================== */
    .app-layout {
        display: flex;
        width: 100%;
        max-width: 100%;
        min-height: 100vh;
        position: relative;
    }

    #content { 
        margin-left: 260px; 
        width: calc(100% - 260px); 
        max-width: calc(100% - 260px); 
        min-width: 0;
        flex: 1 1 auto;
        padding: 24px 28px; 
        min-height: 100vh; 
        box-sizing: border-box;
        overflow-x: hidden;
        transition: margin-left 0.28s cubic-bezier(0.16, 1, 0.3, 1), width 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .topbar { 
        background: rgba(255, 255, 255, 0.85); 
        backdrop-filter: blur(18px); 
        -webkit-backdrop-filter: blur(18px);
        padding: 12px 20px; 
        border-radius: 18px; 
        border: 1px solid var(--card-border);
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.03); 
        margin-bottom: 24px; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        gap: 12px;
        flex-wrap: wrap;
    }
    .topbar-title-box {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .topbar-title {
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        letter-spacing: -0.02em;
    }
    .topbar-status-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
        border-radius: 20px;
        padding: 3px 10px;
        font-size: 0.72rem;
        font-weight: 700;
    }
    .topbar-status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: #10b981;
        display: inline-block;
        animation: pulseSlow 2s infinite ease-in-out;
    }

    .user-profile-capsule {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 4px 12px 4px 6px;
        background: #f1f5f9;
        border-radius: 30px;
        border: 1px solid #e2e8f0;
    }
    .user-profile-avatar {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2563eb, #0ea5e9);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 800;
    }

    .btn-topbar-action {
        border-radius: 12px;
        font-size: 0.84rem;
        font-weight: 600;
        padding: 7px 14px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
    }
    .btn-topbar-action:hover {
        transform: translateY(-1px);
    }

    /* ==========================================================================
       MODERN CARDS, TABLES, BADGES & BUTTONS (OVERRIDE DEFAULT BOOTSTRAP)
       ========================================================================== */
    .card { 
        border: 1px solid var(--card-border) !important; 
        border-radius: 18px !important; 
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.02), 0 10px 25px -5px rgba(15, 23, 42, 0.04) !important; 
        background: #ffffff;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .card-header {
        background: transparent !important;
        border-bottom: 1px solid #f1f5f9 !important;
        padding: 18px 24px !important;
        font-weight: 700 !important;
        color: #0f172a !important;
    }

    /* ==========================================================================
       MODERN DASHBOARD STAT CARDS WITH RICH GRADIENT BACKGROUNDS
       ========================================================================== */
    .stat-card-modern {
        position: relative !important;
        overflow: hidden !important;
        border-radius: 20px !important;
        border: 1px solid rgba(255, 255, 255, 0.18) !important;
        color: #ffffff !important;
        padding: 18px 20px !important;
        transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.28s ease !important;
        cursor: default;
    }
    .stat-card-modern:hover {
        transform: translateY(-5px);
    }
    
    /* 1. Sapphire Blue Gradient (Total Siswa) */
    .stat-card-blue {
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 52%, #38bdf8 100%) !important;
        box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.4), 0 8px 10px -6px rgba(37, 99, 235, 0.25) !important;
    }
    .stat-card-blue:hover {
        box-shadow: 0 18px 32px -5px rgba(37, 99, 235, 0.5), 0 12px 14px -6px rgba(37, 99, 235, 0.3) !important;
    }

    /* 2. Crimson Rose Gradient (Pelanggaran) */
    .stat-card-rose {
        background: linear-gradient(135deg, #881337 0%, #e11d48 52%, #fb7185 100%) !important;
        box-shadow: 0 10px 25px -5px rgba(225, 29, 72, 0.4), 0 8px 10px -6px rgba(225, 29, 72, 0.25) !important;
    }
    .stat-card-rose:hover {
        box-shadow: 0 18px 32px -5px rgba(225, 29, 72, 0.5), 0 12px 14px -6px rgba(225, 29, 72, 0.3) !important;
    }

    /* 3. Emerald Teal Gradient (Prestasi) */
    .stat-card-emerald {
        background: linear-gradient(135deg, #064e3b 0%, #059669 52%, #34d399 100%) !important;
        box-shadow: 0 10px 25px -5px rgba(5, 150, 105, 0.4), 0 8px 10px -6px rgba(5, 150, 105, 0.25) !important;
    }
    .stat-card-emerald:hover {
        box-shadow: 0 18px 32px -5px rgba(5, 150, 105, 0.5), 0 12px 14px -6px rgba(5, 150, 105, 0.3) !important;
    }

    /* 4. Royal Purple Gradient (Rombel / Kelas) */
    .stat-card-purple {
        background: linear-gradient(135deg, #4c1d95 0%, #7c3aed 52%, #c084fc 100%) !important;
        box-shadow: 0 10px 25px -5px rgba(124, 58, 237, 0.4), 0 8px 10px -6px rgba(124, 58, 237, 0.25) !important;
    }
    .stat-card-purple:hover {
        box-shadow: 0 18px 32px -5px rgba(124, 58, 237, 0.5), 0 12px 14px -6px rgba(124, 58, 237, 0.3) !important;
    }

    /* Ambient Background Watermark Icon */
    .stat-watermark {
        position: absolute;
        right: -8px;
        bottom: -16px;
        font-size: 5.8rem;
        line-height: 1;
        color: #ffffff;
        opacity: 0.12;
        pointer-events: none;
        z-index: 1;
        transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.35s ease;
    }
    .stat-card-modern:hover .stat-watermark {
        transform: scale(1.12) rotate(4deg);
        opacity: 0.18;
    }

    /* Frosted Glass Badge for Icon */
    .stat-icon-glass {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.22);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        flex-shrink: 0;
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .stat-card-modern:hover .stat-icon-glass {
        transform: scale(1.08);
    }

    /* Typography & Micro-tags inside Stat Cards */
    .stat-label {
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: rgba(255, 255, 255, 0.88);
        margin-bottom: 4px;
    }
    .stat-value {
        font-size: clamp(1.75rem, 2vw, 2.25rem);
        font-weight: 800;
        line-height: 1.1;
        color: #ffffff;
        letter-spacing: -0.02em;
    }
    .stat-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 30px;
        background: rgba(255, 255, 255, 0.18);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.25);
        font-size: 0.72rem;
        font-weight: 600;
        color: #ffffff;
        margin-top: 12px;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Modern Table Header & Cells */
    table thead { 
        background: #f8fafc !important; 
        color: #475569 !important; 
    }
    table thead th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        color: #475569 !important;
        border-bottom: 1.5px solid #e2e8f0 !important;
        border-top: none !important;
        padding: 12px 16px !important;
    }
    table tbody td {
        vertical-align: middle !important;
        padding: 13px 16px !important;
        border-bottom: 1px solid #f1f5f9 !important;
        font-size: 0.88rem !important;
        color: #334155 !important;
    }
    table tbody tr:hover {
        background-color: #f8fafc !important;
    }

    /* Soft Pastel Modern Badges */
    .badge {
        font-weight: 600 !important;
        padding: 5px 11px !important;
        border-radius: 30px !important;
        font-size: 0.76rem !important;
        letter-spacing: 0.2px !important;
    }
    .badge.bg-primary {
        background-color: #eff6ff !important;
        color: #1d4ed8 !important;
        border: 1px solid #bfdbfe !important;
    }
    .badge.bg-success {
        background-color: #ecfdf5 !important;
        color: #047857 !important;
        border: 1px solid #a7f3d0 !important;
    }
    .badge.bg-danger {
        background-color: #fef2f2 !important;
        color: #b91c1c !important;
        border: 1px solid #fecaca !important;
    }
    .badge.bg-warning {
        background-color: #fffbeb !important;
        color: #b45309 !important;
        border: 1px solid #fde68a !important;
    }
    .badge.bg-info {
        background-color: #f0f9ff !important;
        color: #0369a1 !important;
        border: 1px solid #bae6fd !important;
    }
    .badge.bg-secondary {
        background-color: #f1f5f9 !important;
        color: #475569 !important;
        border: 1px solid #cbd5e1 !important;
    }
    .badge.bg-dark {
        background-color: #0f172a !important;
        color: #f8fafc !important;
    }

    /* Modern Buttons */
    .btn {
        border-radius: 12px !important;
        font-weight: 600 !important;
        font-size: 0.88rem !important;
        padding: 8px 16px !important;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
    .btn:active {
        transform: scale(0.98) !important;
    }

    /* Responsive Mobile & Tablet Layout */
    .sidebar-backdrop {
        display: none; 
        position: fixed; 
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.6); 
        backdrop-filter: blur(4px);
        z-index: 1040;
    }
    @media (max-width: 1199.98px) {
        #content {
            padding: 20px 18px;
        }
    }
    @media (max-width: 991.98px) {
        #sidebar { 
            left: -260px; 
        }
        #sidebar.show { 
            left: 0; 
        }
        #content { 
            margin-left: 0 !important; 
            width: 100% !important; 
            max-width: 100% !important; 
            padding: 16px 14px !important; 
        }
        .sidebar-backdrop.show { 
            display: block; 
        }
        .topbar {
            padding: 12px 16px;
            margin-bottom: 18px;
        }
    }
    @media (max-width: 575.98px) {
        #content {
            padding: 14px 10px !important;
        }
        .topbar {
            padding: 10px 12px;
            border-radius: 14px;
        }
        .topbar-title {
            font-size: 1rem;
        }
    }

    /* Styling Tom Select Searchable Dropdown */
    .ts-wrapper.form-select {
        padding: 0 !important;
        border: none !important;
        background: transparent !important;
    }
    .ts-control {
        border-radius: 12px !important;
        border: 1.5px solid #cbd5e1 !important;
        padding: 0.55rem 0.95rem !important;
        font-size: 0.92rem !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }
    .ts-control input {
        font-size: 0.92rem !important;
    }
    .ts-control:focus, .ts-wrapper.focus .ts-control {
        border-color: #0284c7 !important;
        box-shadow: 0 0 0 3.5px rgba(2, 132, 199, 0.15) !important;
    }
    .ts-dropdown {
        border-radius: 14px !important;
        box-shadow: 0 16px 36px -10px rgba(15, 23, 42, 0.15) !important;
        border: 1px solid #e2e8f0 !important;
        margin-top: 4px !important;
        z-index: 1060 !important;
    }
    .ts-dropdown .option {
        padding: 10px 15px !important;
        font-size: 0.9rem !important;
        border-bottom: 1px solid #f8fafc;
    }
    .ts-dropdown .active {
        background-color: #f0f9ff !important;
        color: #0369a1 !important;
    }
    .ts-dropdown .selected {
        background-color: #e0f2fe !important;
        color: #0284c7 !important;
        font-weight: 700;
    }
</style>
</head>
<body>
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>
<div class="app-layout">
<?php include __DIR__ . '/sidebar.php'; ?>
<div id="content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button type="button" class="btn btn-sm btn-outline-secondary d-lg-none" onclick="toggleSidebar()"><i class="bi bi-list fs-5"></i></button>
            <div class="topbar-title-box">
                <h1 class="topbar-title"><?= $judul_halaman ?? 'Dashboard' ?></h1>
                <span class="topbar-status-chip d-none d-sm-inline-flex">
                    <span class="topbar-status-dot"></span>
                    <span>Sistem Terhubung</span>
                </span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php
            $namaKelas = !empty($_SESSION['nama_kelas']) ? ' ' . htmlspecialchars($_SESSION['nama_kelas']) : '';
            $roleLabel = [
                'admin'          => ['Administrator', 'dark'],
                'kepala_sekolah' => ['Kepala Sekolah', 'warning text-dark fw-bold'],
                'guru_bk'        => [!empty($_SESSION['nama_kelas']) ? 'Guru BK & Wali' . $namaKelas : 'Guru BK', 'primary'],
                'wali_kelas'     => ['Wali Kelas' . $namaKelas, 'info text-dark'],
                'guru'           => ['Guru Pengajar', 'secondary'],
                'osis'           => ['Petugas OSIS', 'success text-white fw-bold']
            ][$_SESSION['role'] ?? ''] ?? [ucfirst($_SESSION['role'] ?? ''), 'secondary'];
            ?>
            <div class="user-profile-capsule d-none d-md-inline-flex me-2">
                <div class="user-profile-avatar">
                    <?= strtoupper(substr($_SESSION['nama_lengkap'] ?? 'U', 0, 1)) ?>
                </div>
                <span class="fw-bold text-dark small" style="max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? '') ?>
                </span>
                <span class="badge bg-<?= $roleLabel[1] ?> ms-1" style="font-size: 0.7rem !important;"><?= $roleLabel[0] ?></span>
            </div>
            
            <a href="ganti_password.php" class="btn btn-sm btn-outline-secondary btn-topbar-action" title="Ubah Profil & Password">
                <i class="bi bi-key-fill text-warning"></i> 
                <span class="d-none d-xl-inline">Password</span>
            </a>
            <a href="logout.php" class="btn btn-sm btn-outline-danger btn-topbar-action" title="Keluar dari Aplikasi">
                <i class="bi bi-box-arrow-right"></i> 
                <span class="d-none d-md-inline">Keluar</span>
            </a>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_pesan'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_tipe'] ?? 'success' ?> alert-dismissible fade show rounded-4 shadow-sm border-0 d-flex align-items-center justify-content-between p-3 mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <span class="fw-semibold"><?= htmlspecialchars($_SESSION['flash_pesan']) ?></span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_pesan'], $_SESSION['flash_tipe']); ?>
    <?php endif; ?>
