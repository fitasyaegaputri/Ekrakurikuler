<?php
/* =========================================================
   DASHBOARD ADMIN - SISTEM INFORMASI EKSTRAKURIKULER
   Optimized: Performance + Accessibility
========================================================= */

session_start();

require_once __DIR__ . "/../config/config.php";

if (!$conn) {
    die("Koneksi database gagal.");
}

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../login.php");
    exit();
}

$namaAdmin = $_SESSION['nama'] ?? "Administrator";
$inisial   = strtoupper(substr(trim($namaAdmin), 0, 1));

$page          = $_GET['page'] ?? 'dashboard';
$isPendaftaran = ($page === 'pendaftaran');
$isJadwal      = ($page === 'jadwal');
$isPembina     = ($page === 'pembina');


/* =========================================================
   STATISTIK
========================================================= */
$totalEkskul      = 0;
$totalSiswa       = 0;
$totalPendaftaran = 0;
$totalJadwal      = 0;
$totalAbsensi     = 0;

$q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM ekstrakurikuler");
if ($q) { $d = mysqli_fetch_assoc($q); $totalEkskul = $d['total']; }

$q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM siswa");
if ($q) { $d = mysqli_fetch_assoc($q); $totalSiswa = $d['total']; }

$q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pendaftaran");
if ($q) { $d = mysqli_fetch_assoc($q); $totalPendaftaran = $d['total']; }

$q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM jadwal");
if ($q) { $d = mysqli_fetch_assoc($q); $totalJadwal = $d['total']; }

$q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM absensi");
if ($q) { $d = mysqli_fetch_assoc($q); $totalAbsensi = $d['total']; }


/* =========================================================
   DATA UNTUK DASHBOARD
========================================================= */
$dataAbsensi = false;
$dataEkskul  = false;

if (!$isPendaftaran && !$isJadwal && !$isPembina) {

    $dataAbsensi = mysqli_query($conn,
        "SELECT
            a.id,
            s.nama_siswa AS nama_siswa,
            e.nama_ekskul,
            a.tanggal,
            a.status_hadir
         FROM absensi a
         LEFT JOIN siswa s ON a.siswa_id = s.id
         LEFT JOIN ekstrakurikuler e ON a.ekskul_id = e.id
         ORDER BY a.id DESC
         LIMIT 5");

    if (!$dataAbsensi) {
        die("Error query absensi: " . mysqli_error($conn));
    }

    $dataEkskul = mysqli_query($conn,
        "SELECT * FROM ekstrakurikuler ORDER BY id DESC LIMIT 5");

    if (!$dataEkskul) {
        die("Error query ekstrakurikuler: " . mysqli_error($conn));
    }
}
?>


<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Dashboard Admin Sistem Informasi Ekstrakurikuler">

<title>Dashboard Admin - Sistem Informasi Ekstrakurikuler</title>

<!-- =========================================================
     PERFORMANCE: Preconnect ke CDN
========================================================= -->
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="dns-prefetch" href="https://cdn.jsdelivr.net">

<!-- =========================================================
     BOOTSTRAP CSS (Async Load — tidak blokir render)
========================================================= -->
<link rel="preload"
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      as="style"
      onload="this.onload=null;this.rel='stylesheet'">
<noscript>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</noscript>

<!-- =========================================================
     BOOTSTRAP ICONS (Minified)
========================================================= -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">


<style>
/* =====================================================
   SCROLLBAR TIPIS
===================================================== */

::-webkit-scrollbar { width: 8px; height: 8px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb {
    background: rgba(15, 118, 110, .25);
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover { background: rgba(15, 118, 110, .5); }

html {
    scrollbar-width: thin;
    scrollbar-color: rgba(15, 118, 110, .25) transparent;
}

.sidebar::-webkit-scrollbar { width: 6px; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, .15); }
.sidebar::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, .3); }


/* =====================================================
   ACCESSIBILITY
===================================================== */

:focus-visible {
    outline: 2px solid #0284c7;
    outline-offset: 2px;
}

button:focus-visible,
a:focus-visible,
input:focus-visible,
select:focus-visible,
textarea:focus-visible {
    outline: 2px solid #0284c7;
    outline-offset: 2px;
}

.visually-hidden {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    white-space: nowrap !important;
    border: 0 !important;
}

.skip-link {
    position: absolute;
    top: -50px;
    left: 6px;
    background: #0284c7;
    color: white;
    padding: 10px 18px;
    border-radius: 8px;
    text-decoration: none;
    z-index: 99999;
    font-weight: 600;
    transition: top .2s;
}

.skip-link:focus {
    top: 10px;
    color: white;
}


/* =====================================================
   GLOBAL
===================================================== */

* { box-sizing: border-box; }

body {
    margin: 0;
    background: #f3f7fa;
    color: #172033;
    font-family: "Segoe UI", Arial, sans-serif;
}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {
    position: fixed;
    left: 0; top: 0;
    width: 255px;
    height: 100vh;
    background: linear-gradient(180deg, #075985 0%, #0f766e 100%);
    color: white;
    padding: 22px 15px;
    z-index: 1000;
    overflow-y: auto;
    box-shadow: 8px 0 25px rgba(15,23,42,.08);
}

.brand {
    padding: 8px 8px 23px;
    border-bottom: 1px solid rgba(255,255,255,.15);
    text-align: center;
}

.brand-icon {
    width: 60px; height: 60px;
    margin: auto;
    border-radius: 17px;
    display: flex;
    align-items: center; justify-content: center;
    background: rgba(255,255,255,.14);
    font-size: 27px;
}

.brand h4 { margin: 11px 0 3px; font-weight: 700; }
.brand small { color: rgba(255,255,255,.85); }

.menu-title {
    margin: 25px 11px 9px;
    color: rgba(255,255,255,.75);
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.sidebar a {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 12px 14px;
    margin-bottom: 5px;
    border-radius: 11px;
    color: rgba(255,255,255,.92);
    text-decoration: none;
    transition: .2s;
}

.sidebar a i { width: 21px; text-align: center; font-size: 17px; }

.sidebar a:hover {
    color: white;
    background: rgba(255,255,255,.12);
    transform: translateX(2px);
}

.sidebar a.active {
    color: white;
    background: rgba(255,255,255,.18);
    box-shadow: inset 3px 0 0 white;
}

.logout { margin-top: 5px; }

.logout a {
    background: rgba(220,38,38,.9);
    color: white;
}

.logout a:hover { background: #dc2626; }


/* =====================================================
   CONTENT
===================================================== */

.content {
    margin-left: 255px;
    padding: 28px;
}


/* =====================================================
   TOPBAR
===================================================== */

.topbar {
    background: white;
    min-height: 72px;
    padding: 14px 20px;
    border-radius: 17px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 5px 22px rgba(15,23,42,.06);
    margin-bottom: 22px;
}

.topbar-title h1 { margin: 0; font-size: 18px; font-weight: 700; }
.topbar-title small { color: #475569; }

.admin-area { display: flex; align-items: center; gap: 11px; }
.admin-info { text-align: right; }
.admin-info strong { display: block; font-size: 14px; color: #172033; }
.admin-info small { color: #475569; }

.admin-avatar {
    width: 44px; height: 44px;
    border-radius: 13px;
    background: #e0f2fe;
    color: #0369a1;
    display: flex;
    align-items: center; justify-content: center;
    font-weight: 700;
    font-size: 17px;
}


/* =====================================================
   WELCOME
===================================================== */

.welcome {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #0284c7, #0f766e);
    color: white;
    border-radius: 21px;
    padding: 29px 31px;
    margin-bottom: 22px;
    box-shadow: 0 12px 30px rgba(14,116,144,.18);
}

.welcome::before {
    content: "";
    position: absolute;
    width: 220px; height: 220px;
    border-radius: 50%;
    background: rgba(255,255,255,.07);
    right: -65px; top: -100px;
}

.welcome::after {
    content: "";
    position: absolute;
    width: 120px; height: 120px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
    right: 120px; bottom: -75px;
}

.welcome-content { position: relative; z-index: 2; }

.welcome-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.25);
    padding: 6px 11px;
    border-radius: 20px;
    font-size: 12px;
    margin-bottom: 12px;
}

.welcome h2 { margin: 0 0 7px; font-size: 27px; font-weight: 700; }
.welcome p { margin: 0; color: rgba(255,255,255,.95); }


/* =====================================================
   STAT CARD
===================================================== */

.stat-card {
    position: relative;
    overflow: hidden;
    background: white;
    border-radius: 17px;
    padding: 20px;
    min-height: 145px;
    box-shadow: 0 5px 22px rgba(15,23,42,.055);
    border: 1px solid #edf2f5;
    transition: .2s;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 28px rgba(15,23,42,.09);
}

.stat-top { display: flex; align-items: center; justify-content: space-between; }

.stat-icon {
    width: 45px; height: 45px;
    border-radius: 13px;
    display: flex;
    align-items: center; justify-content: center;
    font-size: 20px;
}

.icon-blue   { background:#e0f2fe; color:#0284c7; }
.icon-green  { background:#dcfce7; color:#15803d; }
.icon-orange { background:#ffedd5; color:#ea580c; }
.icon-teal   { background:#ccfbf1; color:#0f766e; }
.icon-purple { background:#ede9fe; color:#7c3aed; }
.icon-yellow { background:#fef3c7; color:#b45309; }
.icon-red    { background:#fee2e2; color:#dc2626; }

.stat-arrow { color: #94a3b8; font-size: 19px; }
.stat-number { font-size: 27px; font-weight: 750; margin-top: 15px; color: #172033; }
.stat-label { color: #475569; font-size: 13px; }


/* =====================================================
   SECTION CARD
===================================================== */

.section-card {
    background: white;
    border-radius: 18px;
    box-shadow: 0 5px 22px rgba(15,23,42,.055);
    border: 1px solid #edf2f5;
    overflow: hidden;
}

.section-header {
    padding: 18px 20px;
    border-bottom: 1px solid #eef2f5;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    flex-wrap: wrap;
}

.section-header h2 { margin: 0; font-size: 16px; font-weight: 700; color: #172033; }
.section-header small { color: #475569; }


/* =====================================================
   TABLE
===================================================== */

.table { margin: 0; }

.table thead th {
    background: #f8fafc;
    border: none;
    color: #475569;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .4px;
    padding: 13px 20px;
}

.table tbody td {
    padding: 14px 20px;
    vertical-align: middle;
    border-color: #f1f5f9;
    font-size: 13px;
    color: #172033;
}

.table tbody tr:hover { background: #f8fafc; }


/* =====================================================
   SHARED COMPONENTS
===================================================== */

.number-box {
    width: 32px; height: 32px;
    border-radius: 10px;
    background: #f1f5f9;
    color: #475569;
    font-weight: 600;
    font-size: 12px;
    display: inline-flex;
    align-items: center; justify-content: center;
}

.student-box { display: flex; align-items: center; gap: 12px; }

.student-avatar {
    width: 42px; height: 42px;
    flex-shrink: 0;
    border-radius: 12px;
    background: #e0f2fe;
    color: #0284c7;
    display: flex;
    align-items: center; justify-content: center;
}

.student-info strong { display: block; font-size: 13px; color: #172033; }
.student-info small { color: #475569; font-size: 11px; }

.ekskul-box { display: flex; align-items: center; gap: 12px; }

.ekskul-icon {
    width: 40px; height: 40px;
    flex-shrink: 0;
    border-radius: 11px;
    background: #e0f2fe;
    color: #0284c7;
    display: flex;
    align-items: center; justify-content: center;
}

.ekskul-name { font-weight: 600; font-size: 13px; color: #172033; }

.btn-add {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border: none;
    border-radius: 10px;
    padding: 10px 15px;
    background: linear-gradient(135deg, #0284c7, #0f766e);
    color: white;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 5px 15px rgba(2,132,199,.18);
    transition: .2s;
}

.btn-add:hover {
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(2,132,199,.25);
}

.btn-delete {
    padding: 8px 12px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border: none;
    border-radius: 10px;
    background: #fee2e2;
    color: #b91c1c;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    transition: .2s;
}

.btn-delete:hover { background: #dc2626; color: white; }

.btn-edit {
    width: 36px; height: 36px;
    display: inline-flex;
    align-items: center; justify-content: center;
    border: none;
    border-radius: 10px;
    background: #e0f2fe;
    color: #0284c7;
    text-decoration: none;
    transition: .2s;
}

.btn-edit:hover { background: #0284c7; color: white; }

.action-buttons { display: flex; align-items: center; gap: 6px; }
.action-box { display: flex; align-items: center; gap: 6px; }

.btn-action {
    width: 36px; height: 36px;
    display: flex;
    align-items: center; justify-content: center;
    border-radius: 10px;
    text-decoration: none;
    border: none;
    transition: .2s;
    font-size: 14px;
}


/* =====================================================
   STATUS BADGES
===================================================== */

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.status-menunggu { background:#fef3c7; color:#92400e; }
.status-diterima { background:#dcfce7; color:#166534; }
.status-ditolak  { background:#fee2e2; color:#991b1b; }

.status-hadir { background:#dcfce7; color:#166534; }
.status-izin  { background:#fef3c7; color:#92400e; }
.status-sakit { background:#dbeafe; color:#1e40af; }
.status-alfa  { background:#fee2e2; color:#991b1b; }

.hari-badge {
    display: inline-flex;
    align-items: center;
    background: #e0f2fe;
    color: #0369a1;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.jam-box {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #0f766e;
    font-weight: 600;
    font-size: 12px;
}

.lokasi-box {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #475569;
    font-size: 12px;
}


/* =====================================================
   EMPTY STATE
===================================================== */

.empty-state {
    padding: 45px 20px;
    text-align: center;
    color: #475569;
}

.empty-state i { display: block; font-size: 35px; margin-bottom: 10px; color: #94a3b8; }
.empty-state strong { display: block; color: #172033; margin-bottom: 4px; }
.empty-state span { font-size: 12px; color: #64748b; }


/* =====================================================
   MODAL
===================================================== */

.modal-content {
    border: none;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(15,23,42,.18);
}

.modal-header {
    background: linear-gradient(135deg, #0284c7, #0f766e);
    color: white;
    border: none;
    padding: 17px 20px;
}

.modal-header .btn-close { filter: brightness(0) invert(1); }
.modal-title { font-size: 16px; font-weight: 700; }
.modal-body { padding: 22px; }
.modal-footer { border-top: 1px solid #f1f5f9; padding: 15px 22px; }

.form-label { font-size: 12px; font-weight: 600; color: #475569; }

.form-control, .form-select {
    border-radius: 10px;
    min-height: 43px;
    font-size: 13px;
    color: #172033;
}

.form-control:focus, .form-select:focus {
    border-color: #38bdf8;
    box-shadow: 0 0 0 3px rgba(56,189,248,.12);
}

.btn-save {
    border: none;
    border-radius: 10px;
    padding: 9px 17px;
    background: linear-gradient(135deg, #0284c7, #0f766e);
    color: white;
    font-size: 13px;
    font-weight: 600;
}

.btn-save:hover { color: white; opacity: .92; }

.btn-cancel {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 9px 17px;
    background: white;
    color: #475569;
    font-size: 13px;
}

.btn-cancel:hover { background: #f8fafc; }


/* =====================================================
   SEARCH
===================================================== */

.search-area {
    padding: 18px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #eef2f5;
}

.search-input { position: relative; }

.search-input i {
    position: absolute;
    left: 13px; top: 50%;
    transform: translateY(-50%);
    color: #475569;
}

.search-input input {
    padding-left: 39px;
    min-height: 43px;
    border: 1px solid #e2e8f0;
    border-radius: 11px;
}

.search-area .form-select {
    min-height: 43px;
    border-radius: 11px;
    border: 1px solid #e2e8f0;
}

.search-box { position: relative; max-width: 280px; }

.search-box i {
    position: absolute;
    left: 13px; top: 50%;
    transform: translateY(-50%);
    color: #475569;
}

.search-box input {
    padding-left: 38px;
    min-height: 40px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
}


/* =====================================================
   ABSENSI ITEM
===================================================== */

.absensi-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    border-bottom: 1px solid #f1f5f9;
}

.absensi-item:last-child { border-bottom: none; }

.absensi-avatar {
    width: 42px; height: 42px;
    flex-shrink: 0;
    border-radius: 12px;
    background: #e0f2fe;
    color: #0284c7;
    display: flex;
    align-items: center; justify-content: center;
}

.absensi-info { flex: 1; }
.absensi-info strong { display: block; font-size: 13px; color: #172033; }
.absensi-info small { color: #475569; font-size: 11px; }
.absensi-date { color: #475569; font-size: 10px; margin-top: 2px; }

.absensi-status {
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
}


/* =====================================================
   QUICK MENU
===================================================== */

.quick-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    background: #fff;
    border: 1px solid #edf2f5;
    border-radius: 14px;
    text-decoration: none;
    color: #172033;
    transition: .2s;
    height: 100%;
}

.quick-card:hover {
    color: #172033;
    border-color: #bae6fd;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(15,23,42,.07);
}

.quick-icon {
    width: 43px; height: 43px;
    flex-shrink: 0;
    border-radius: 12px;
    background: #e0f2fe;
    color: #0284c7;
    display: flex;
    align-items: center; justify-content: center;
    font-size: 18px;
}

.quick-card strong { display: block; font-size: 13px; color: #172033; }
.quick-card small { color: #475569; font-size: 11px; }


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 992px) {
    .sidebar { width: 220px; }
    .content { margin-left: 220px; padding: 20px; }
}

@media (max-width: 768px) {

    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        min-height: auto;
    }

    .logout { position: static; margin-top: 20px; }

    .content { margin-left: 0; padding: 15px; }

    .topbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 13px;
    }

    .admin-info { text-align: left; }

    .welcome h2 { font-size: 23px; }

    .section-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

</head>


<body>

<!-- SKIP LINK -->
<a href="#konten-utama" class="skip-link">Langsung ke konten</a>


<!-- SIDEBAR -->
<nav class="sidebar" aria-label="Menu utama">

    <div class="brand">
        <div class="brand-icon" aria-hidden="true"><i class="bi bi-mortarboard-fill"></i></div>
        <h4>Ekstrakurikuler</h4>
        <small>Administrator</small>
    </div>

    <div class="menu-title">Menu Utama</div>

    <a href="dashboard_admin.php"
       class="<?= (!$isPendaftaran && !$isJadwal && !$isPembina) ? 'active' : ''; ?>">
        <i class="bi bi-grid-1x2-fill" aria-hidden="true"></i>
        <span>Dashboard</span>
    </a>

    <a href="ekstrakurikuler.php">
        <i class="bi bi-stars" aria-hidden="true"></i>
        <span>Ekstrakurikuler</span>
    </a>

    <a href="data_siswa.php">
        <i class="bi bi-people-fill" aria-hidden="true"></i>
        <span>Data Siswa</span>
    </a>

    <a href="pendaftaran.php">
        <i class="bi bi-person-check-fill" aria-hidden="true"></i>
        <span>Pendaftaran</span>
    </a>

    <a href="jadwal.php">
        <i class="bi bi-calendar-event-fill" aria-hidden="true"></i>
        <span>Jadwal</span>
    </a>

    <a href="absensi.php">
        <i class="bi bi-calendar-check-fill" aria-hidden="true"></i>
        <span>Absensi</span>
    </a>

    <a href="laporan.php">
        <i class="bi bi-bar-chart-fill" aria-hidden="true"></i>
        <span>Laporan</span>
    </a>

    <a href="pembina.php">
        <i class="bi bi-person-workspace" aria-hidden="true"></i>
        <span>Pembina</span>
    </a>

    <div class="logout">
        <a href="../logout.php">
            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            <span>Logout</span>
        </a>
    </div>

</nav>


<!-- CONTENT -->
<main class="content" id="konten-utama">

    <!-- TOPBAR -->
    <header class="topbar">
        <div class="topbar-title">
            <h1 class="h5">
                <?php
                if ($isPendaftaran)     echo "Pendaftaran";
                elseif ($isJadwal)      echo "Jadwal Kegiatan";
                elseif ($isPembina)     echo "Kelola Pembina";
                else                    echo "Dashboard Admin";
                ?>
            </h1>
            <small>Sistem Informasi Ekstrakurikuler</small>
        </div>

        <div class="admin-area">
            <div class="admin-info">
                <strong><?= htmlspecialchars($namaAdmin); ?></strong>
                <small>Administrator</small>
            </div>
            <div class="admin-avatar" aria-hidden="true"><?= htmlspecialchars($inisial); ?></div>
        </div>
    </header>


    <!-- WELCOME -->
    <section class="welcome">
        <div class="welcome-content">
            <div class="welcome-badge">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                Panel Administrator
            </div>
            <h2>Selamat Datang, <?= htmlspecialchars($namaAdmin); ?>!</h2>
            <p>
                Kelola data ekstrakurikuler, siswa, pendaftaran,
                jadwal, absensi, dan laporan sekolah melalui satu sistem.
            </p>
        </div>
    </section>


    <!-- STATISTIK -->
    <section class="row g-3 mb-4" aria-label="Statistik sistem">

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-blue" aria-hidden="true"><i class="bi bi-stars"></i></div>
                    <div class="stat-arrow" aria-hidden="true"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalEkskul; ?></div>
                <div class="stat-label">Total Ekstrakurikuler</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-green" aria-hidden="true"><i class="bi bi-people-fill"></i></div>
                    <div class="stat-arrow" aria-hidden="true"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalSiswa; ?></div>
                <div class="stat-label">Total Siswa</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-orange" aria-hidden="true"><i class="bi bi-person-plus-fill"></i></div>
                    <div class="stat-arrow" aria-hidden="true"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalPendaftaran; ?></div>
                <div class="stat-label">Total Pendaftaran</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-teal" aria-hidden="true"><i class="bi bi-calendar-event-fill"></i></div>
                    <div class="stat-arrow" aria-hidden="true"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalJadwal; ?></div>
                <div class="stat-label">Total Jadwal</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-purple" aria-hidden="true"><i class="bi bi-calendar-check-fill"></i></div>
                    <div class="stat-arrow" aria-hidden="true"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalAbsensi; ?></div>
                <div class="stat-label">Total Absensi</div>
            </div>
        </div>

    </section>


    <!-- DATA EKSKUL + ABSENSI -->
    <div class="row g-4 mb-4">

        <div class="col-lg-7">
            <section class="section-card">

                <div class="section-header">
                    <div>
                        <h2>Data Ekstrakurikuler</h2>
                        <small>Data ekstrakurikuler terbaru</small>
                    </div>
                    <a href="ekstrakurikuler.php" class="btn-view" style="color:#0284c7;text-decoration:none;font-size:12px;font-weight:600;">
                        Lihat Semua <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Ekstrakurikuler</th>
                                <th>Pembina</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no = 1; if ($dataEkskul && mysqli_num_rows($dataEkskul) > 0): ?>
                            <?php while ($e = mysqli_fetch_assoc($dataEkskul)): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="ekskul-icon" aria-hidden="true"><i class="bi bi-stars"></i></div>
                                            <strong><?= htmlspecialchars($e['nama_ekskul'] ?? '-'); ?></strong>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($e['pembina'] ?? '-'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox" aria-hidden="true"></i>
                                        Belum ada data ekstrakurikuler.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </section>
        </div>

        <div class="col-lg-5">
            <section class="section-card">

                <div class="section-header">
                    <div>
                        <h2>Absensi Terbaru</h2>
                        <small>Aktivitas kehadiran siswa</small>
                    </div>
                    <a href="absensi.php" style="color:#0284c7;text-decoration:none;font-size:12px;font-weight:600;">
                        Lihat Semua <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>

                <?php if ($dataAbsensi && mysqli_num_rows($dataAbsensi) > 0): ?>
                    <?php while ($a = mysqli_fetch_assoc($dataAbsensi)): ?>
                        <?php
                        $statusClass = "status-hadir";
                        if (strtolower($a['status_hadir']) == "izin")  $statusClass = "status-izin";
                        elseif (strtolower($a['status_hadir']) == "sakit") $statusClass = "status-sakit";
                        elseif (strtolower($a['status_hadir']) == "alfa")  $statusClass = "status-alfa";
                        ?>
                        <div class="absensi-item">
                            <div class="absensi-avatar" aria-hidden="true"><i class="bi bi-person-fill"></i></div>
                            <div class="absensi-info">
                                <strong><?= htmlspecialchars($a['nama_siswa']); ?></strong>
                                <small><?= htmlspecialchars($a['nama_ekskul']); ?></small>
                                <div class="absensi-date"><?= htmlspecialchars($a['tanggal']); ?></div>
                            </div>
                            <span class="absensi-status <?= $statusClass; ?>">
                                <?= htmlspecialchars($a['status_hadir']); ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-calendar-x" aria-hidden="true"></i>
                        Belum ada data absensi.
                    </div>
                <?php endif; ?>

            </section>
        </div>

    </div>


    <!-- MENU CEPAT -->
    <section class="section-card mb-4">

        <div class="section-header">
            <div>
                <h2>Menu Cepat</h2>
                <small>Akses fitur administrator dengan mudah</small>
            </div>
        </div>

        <div class="p-3">
            <div class="row g-3">

                <div class="col-lg-3 col-md-6">
                    <a href="ekstrakurikuler.php" class="quick-card">
                        <div class="quick-icon" aria-hidden="true"><i class="bi bi-stars"></i></div>
                        <div>
                            <strong>Ekstrakurikuler</strong>
                            <small>Kelola data ekstrakurikuler</small>
                        </div>
                    </a>
                </div>

                <div class="col-lg-3 col-md-6">
                    <a href="data_siswa.php" class="quick-card">
                        <div class="quick-icon" aria-hidden="true"><i class="bi bi-people-fill"></i></div>
                        <div>
                            <strong>Data Siswa</strong>
                            <small>Kelola data siswa</small>
                        </div>
                    </a>
                </div>

                <div class="col-lg-3 col-md-6">
                    <a href="pendaftaran.php" class="quick-card">
                        <div class="quick-icon" aria-hidden="true"><i class="bi bi-person-check-fill"></i></div>
                        <div>
                            <strong>Pendaftaran</strong>
                            <small>Kelola pendaftaran siswa</small>
                        </div>
                    </a>
                </div>

                <div class="col-lg-3 col-md-6">
                    <a href="jadwal.php" class="quick-card">
                        <div class="quick-icon" aria-hidden="true"><i class="bi bi-calendar-event-fill"></i></div>
                        <div>
                            <strong>Jadwal</strong>
                            <small>Kelola jadwal kegiatan</small>
                        </div>
                    </a>
                </div>

                <div class="col-lg-3 col-md-6">
                    <a href="absensi.php" class="quick-card">
                        <div class="quick-icon" aria-hidden="true"><i class="bi bi-calendar-check-fill"></i></div>
                        <div>
                            <strong>Absensi</strong>
                            <small>Kelola kehadiran siswa</small>
                        </div>
                    </a>
                </div>

                <div class="col-lg-3 col-md-6">
                    <a href="laporan.php" class="quick-card">
                        <div class="quick-icon" aria-hidden="true"><i class="bi bi-bar-chart-fill"></i></div>
                        <div>
                            <strong>Laporan</strong>
                            <small>Lihat laporan kegiatan</small>
                        </div>
                    </a>
                </div>

                <div class="col-lg-3 col-md-6">
                    <a href="pembina.php" class="quick-card">
                        <div class="quick-icon" aria-hidden="true"><i class="bi bi-person-workspace"></i></div>
                        <div>
                            <strong>Pembina</strong>
                            <small>Kelola akun pembina</small>
                        </div>
                    </a>
                </div>

                <div class="col-lg-3 col-md-6">
                    <a href="absensi.php" class="quick-card">
                        <div class="quick-icon" aria-hidden="true"><i class="bi bi-clipboard-check-fill"></i></div>
                        <div>
                            <strong>Kelola Absensi</strong>
                            <small>Catat kehadiran siswa</small>
                        </div>
                    </a>
                </div>

            </div>
        </div>

    </section>

</main>


<!-- =========================================================
     BOOTSTRAP JS (defer — tidak blokir render)
========================================================= -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

</body>
</html>