<?php
/* =========================================================
   EKSTRAKURIKULER - HALAMAN ADMIN
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

$pesan     = "";
$tipePesan = "";


/* =========================================================
   TAMBAH DATA
========================================================= */
if (isset($_POST['tambah'])) {

    $nama_ekskul = trim($_POST['nama_ekskul'] ?? "");
    $pembina     = trim($_POST['pembina'] ?? "");
    $lokasi      = trim($_POST['lokasi'] ?? "");

    if ($nama_ekskul === "" || $pembina === "" || $lokasi === "") {
        $pesan     = "Nama ekstrakurikuler, pembina, dan lokasi wajib diisi.";
        $tipePesan = "danger";
    } else {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO ekstrakurikuler (nama_ekskul, pembina, lokasi)
             VALUES (?, ?, ?)");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $nama_ekskul, $pembina, $lokasi);

            if (mysqli_stmt_execute($stmt)) {
                $pesan     = "Data ekstrakurikuler berhasil ditambahkan. Silakan atur jadwal di menu <strong>Jadwal</strong>.";
                $tipePesan = "success";
            } else {
                $pesan     = "Data gagal ditambahkan: " . mysqli_error($conn);
                $tipePesan = "danger";
            }
            mysqli_stmt_close($stmt);
        } else {
            $pesan     = "Query tambah data gagal.";
            $tipePesan = "danger";
        }
    }
}


/* =========================================================
   UPDATE DATA
========================================================= */
if (isset($_POST['edit'])) {

    $id          = (int)($_POST['id'] ?? 0);
    $nama_ekskul = trim($_POST['nama_ekskul'] ?? "");
    $pembina     = trim($_POST['pembina'] ?? "");
    $lokasi      = trim($_POST['lokasi'] ?? "");

    if ($id <= 0) {
        $pesan     = "ID data tidak valid.";
        $tipePesan = "danger";
    } elseif ($nama_ekskul === "" || $pembina === "" || $lokasi === "") {
        $pesan     = "Nama ekstrakurikuler, pembina, dan lokasi wajib diisi.";
        $tipePesan = "danger";
    } else {
        $stmt = mysqli_prepare($conn,
            "UPDATE ekstrakurikuler
             SET nama_ekskul = ?, pembina = ?, lokasi = ?
             WHERE id = ?");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssi", $nama_ekskul, $pembina, $lokasi, $id);

            if (mysqli_stmt_execute($stmt)) {
                $pesan     = "Data ekstrakurikuler berhasil diperbarui.";
                $tipePesan = "success";
            } else {
                $pesan     = "Data gagal diperbarui: " . mysqli_error($conn);
                $tipePesan = "danger";
            }
            mysqli_stmt_close($stmt);
        } else {
            $pesan     = "Query edit data gagal.";
            $tipePesan = "danger";
        }
    }
}


/* =========================================================
   HAPUS DATA
========================================================= */
if (isset($_GET['hapus'])) {

    $id = (int)($_GET['hapus'] ?? 0);

    if ($id > 0) {
        $stmt = mysqli_prepare($conn, "DELETE FROM ekstrakurikuler WHERE id = ?");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id);

            if (mysqli_stmt_execute($stmt)) {
                $pesan     = "Data ekstrakurikuler berhasil dihapus.";
                $tipePesan = "success";
            } else {
                $pesan     = "Data tidak dapat dihapus. Kemungkinan data masih digunakan pada pendaftaran, jadwal, atau absensi.";
                $tipePesan = "danger";
            }
            mysqli_stmt_close($stmt);
        } else {
            $pesan     = "Query hapus data gagal.";
            $tipePesan = "danger";
        }
    }
}


/* =========================================================
   PENCARIAN
========================================================= */
$cari = trim($_GET['cari'] ?? "");

$dataEkskul = false;

if ($cari !== "") {
    $keyword = "%" . $cari . "%";

    $stmt = mysqli_prepare($conn,
        "SELECT * FROM ekstrakurikuler
         WHERE nama_ekskul LIKE ?
            OR pembina LIKE ?
            OR lokasi LIKE ?
         ORDER BY id DESC");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sss", $keyword, $keyword, $keyword);
        mysqli_stmt_execute($stmt);
        $dataEkskul = mysqli_stmt_get_result($stmt);
    }
} else {
    $dataEkskul = mysqli_query($conn,
        "SELECT * FROM ekstrakurikuler ORDER BY id DESC");
}

$rowsEkskul = [];
if ($dataEkskul) {
    while ($r = mysqli_fetch_assoc($dataEkskul)) {
        $rowsEkskul[] = $r;
    }
}
$totalData = count($rowsEkskul);

$menu = "ekstrakurikuler";
?>


<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Ekstrakurikuler - Sistem Informasi Ekstrakurikuler</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* =====================================================
   SCROLLBAR TIPIS & CANTIK
===================================================== */

::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: rgba(15, 118, 110, .25);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(15, 118, 110, .5);
}

html {
    scrollbar-width: thin;
    scrollbar-color: rgba(15, 118, 110, .25) transparent;
}

.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, .15);
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, .3);
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
.brand small { color: rgba(255,255,255,.65); }

.menu-title {
    margin: 25px 11px 9px;
    color: rgba(255,255,255,.55);
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
    color: rgba(255,255,255,.84);
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
    background: rgba(220,38,38,.85);
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

.topbar-title h5 { margin: 0; font-size: 18px; font-weight: 700; }
.topbar-title small { color: #64748b; }

.admin-area { display: flex; align-items: center; gap: 11px; }
.admin-info { text-align: right; }
.admin-info strong { display: block; font-size: 14px; }
.admin-info small { color: #64748b; }

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
    background: rgba(255,255,255,.13);
    border: 1px solid rgba(255,255,255,.16);
    padding: 6px 11px;
    border-radius: 20px;
    font-size: 12px;
    margin-bottom: 12px;
}

.welcome h2 { margin: 0 0 7px; font-size: 27px; font-weight: 700; }
.welcome p { margin: 0; color: rgba(255,255,255,.82); }


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

.stat-arrow { color: #cbd5e1; font-size: 19px; }
.stat-number { font-size: 27px; font-weight: 750; margin-top: 15px; }
.stat-label { color: #64748b; font-size: 13px; }


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

.section-header h5 { margin: 0; font-size: 16px; font-weight: 700; }
.section-header small { color: #64748b; }


/* =====================================================
   SEARCH AREA
===================================================== */

.search-area { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.search-form { display: flex; gap: 7px; }

.search-form .form-control {
    width: 230px;
    min-height: 42px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    padding-left: 38px;
    font-size: 13px;
}

.search-wrap { position: relative; }

.search-wrap i {
    position: absolute;
    left: 13px; top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 14px;
}

.btn-search {
    width: 42px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: white;
    color: #475569;
    transition: .2s;
}

.btn-search:hover {
    background: #0284c7;
    color: white;
    border-color: #0284c7;
}

.btn-reset {
    width: 42px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: white;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: .2s;
}

.btn-reset:hover { background: #f1f5f9; color: #475569; }


/* =====================================================
   BUTTON TAMBAH
===================================================== */

.btn-add {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 10px 15px;
    border: none;
    border-radius: 10px;
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


/* =====================================================
   TABLE
===================================================== */

.table { margin: 0; }

.table thead th {
    background: #f8fafc;
    border: none;
    color: #64748b;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .4px;
    padding: 13px 20px;
    white-space: nowrap;
}

.table tbody td {
    padding: 14px 20px;
    vertical-align: middle;
    border-color: #f1f5f9;
    font-size: 13px;
}

.table tbody tr:hover { background: #f8fafc; }


/* =====================================================
   EKSKUL CELL
===================================================== */

.nama-ekskul { display: flex; align-items: center; gap: 11px; }

.ekskul-icon {
    width: 42px; height: 42px;
    border-radius: 12px;
    background: #e0f2fe;
    color: #0284c7;
    display: flex;
    align-items: center; justify-content: center;
    flex-shrink: 0;
    font-size: 18px;
}

.nama-ekskul strong { display: block; font-size: 13px; }
.nama-ekskul small { color: #94a3b8; font-size: 11px; }

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


/* =====================================================
   ACTION BUTTON
===================================================== */

.action-buttons { display: flex; align-items: center; gap: 7px; }

.btn-action {
    width: 36px; height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    text-decoration: none;
    border: none;
    transition: .2s;
    font-size: 14px;
}

.btn-edit { background: #e0f2fe; color: #0284c7; }
.btn-edit:hover { background: #0284c7; color: white; }

.btn-delete { background: #fee2e2; color: #dc2626; }
.btn-delete:hover { background: #dc2626; color: white; }

.btn-jadwal { background: #fef3c7; color: #b45309; }
.btn-jadwal:hover { background: #f59e0b; color: white; }


/* =====================================================
   ALERT
===================================================== */

.alert-custom {
    border: none;
    border-radius: 14px;
    box-shadow: 0 5px 22px rgba(15,23,42,.05);
}


/* =====================================================
   EMPTY STATE
===================================================== */

.empty-state {
    padding: 45px 20px;
    text-align: center;
    color: #94a3b8;
}

.empty-state i { display: block; font-size: 35px; margin-bottom: 10px; }
.empty-state strong { display: block; color: #475569; margin-bottom: 4px; }
.empty-state span { font-size: 12px; }


/* =====================================================
   MODAL
===================================================== */

.modal-content {
    border: none;
    border-radius: 18px;
    overflow: hidden;
}

.modal-header {
    border: none;
    background: linear-gradient(135deg, #0284c7, #0f766e);
    color: white;
    padding: 18px 20px;
}

.modal-header .btn-close { filter: brightness(0) invert(1); }

.modal-title { font-size: 16px; font-weight: 700; }
.modal-body { padding: 22px; }
.modal-footer { border-top: 1px solid #f1f5f9; padding: 15px 22px; }

.form-label { font-size: 12px; font-weight: 600; color: #475569; }

.form-control {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 12px;
    font-size: 13px;
    box-shadow: none;
    min-height: 43px;
}

.form-control:focus {
    border-color: #38bdf8;
    box-shadow: 0 0 0 3px rgba(14,165,233,.10);
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
    color: #64748b;
    font-size: 13px;
}

.btn-cancel:hover { background: #f8fafc; }


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 992px) {
    .sidebar { width: 220px; }
    .content { margin-left: 220px; padding: 20px; }
    .search-form .form-control { width: 180px; }
}

@media (max-width: 768px) {

    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        min-height: auto;
    }

    .content { margin-left: 0; padding: 15px; }

    .topbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 13px;
    }

    .admin-info { text-align: left; }

    .section-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .search-area {
        width: 100%;
        flex-direction: column;
        align-items: stretch;
    }

    .search-form { width: 100%; }
    .search-form .form-control { width: 100%; }
    .btn-add { justify-content: center; }
}
</style>

</head>


<body>


<!-- SIDEBAR -->
<div class="sidebar">

    <div class="brand">
        <div class="brand-icon"><i class="bi bi-mortarboard-fill"></i></div>
        <h4>Ekstrakurikuler</h4>
        <small>Administrator</small>
    </div>

    <div class="menu-title">Menu Utama</div>

    <a href="dashboard_admin.php"><i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span></a>
    <a href="ekstrakurikuler.php" class="active"><i class="bi bi-stars"></i><span>Ekstrakurikuler</span></a>
    <a href="data_siswa.php"><i class="bi bi-people-fill"></i><span>Data Siswa</span></a>
    <a href="pendaftaran.php"><i class="bi bi-person-check-fill"></i><span>Pendaftaran</span></a>
    <a href="jadwal.php"><i class="bi bi-calendar-event-fill"></i><span>Jadwal</span></a>
    <a href="absensi.php"><i class="bi bi-calendar-check-fill"></i><span>Absensi</span></a>
    <a href="laporan.php"><i class="bi bi-bar-chart-fill"></i><span>Laporan</span></a>
    <a href="pembina.php"><i class="bi bi-person-workspace"></i><span>Pembina</span></a>

    <div class="logout">
        <a href="../logout.php"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
    </div>

</div>


<!-- CONTENT -->
<div class="content">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-title">
            <h5>Ekstrakurikuler</h5>
            <small>Sistem Informasi Ekstrakurikuler</small>
        </div>
        <div class="admin-area">
            <div class="admin-info">
                <strong><?= htmlspecialchars($namaAdmin); ?></strong>
                <small>Administrator</small>
            </div>
            <div class="admin-avatar"><?= htmlspecialchars($inisial); ?></div>
        </div>
    </div>


    <!-- WELCOME -->
    <div class="welcome">
        <div class="welcome-content">
            <div class="welcome-badge">
                <i class="bi bi-stars"></i>
                Manajemen Ekstrakurikuler
            </div>
            <h2>Data Ekstrakurikuler</h2>
            <p>
                Kelola data ekstrakurikuler, pembina, dan lokasi kegiatan.
                Atur jadwal di menu <strong style="color:#fff;">Jadwal</strong>.
            </p>
        </div>
    </div>


    <!-- INFO ALERT -->
    <div class="alert alert-info alert-dismissible fade show alert-custom" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i>
        Untuk mengatur <strong>jadwal kegiatan</strong>, silakan ke menu
        <a href="jadwal.php" class="alert-link">Jadwal</a>.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>


    <!-- STATISTIK -->
    <div class="row g-3 mb-4">

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-blue"><i class="bi bi-stars"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalData; ?></div>
                <div class="stat-label">Total Ekstrakurikuler</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-green"><i class="bi bi-person-badge-fill"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number">
                    <?php
                    $totalPembinaUnik = 0;
                    $sudahHitung = [];
                    foreach ($rowsEkskul as $r) {
                        $p = trim($r['pembina'] ?? '');
                        if ($p !== '' && !in_array($p, $sudahHitung, true)) {
                            $sudahHitung[] = $p;
                            $totalPembinaUnik++;
                        }
                    }
                    echo $totalPembinaUnik;
                    ?>
                </div>
                <div class="stat-label">Total Pembina</div>
            </div>
        </div>

    </div>


    <!-- ALERT -->
    <?php if ($pesan !== ""): ?>
    <div class="alert alert-<?= $tipePesan; ?> alert-dismissible fade show alert-custom" role="alert">
        <i class="bi <?= $tipePesan === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
        <?= $pesan; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>


    <!-- DATA EKSTRAKURIKULER -->
    <div class="section-card">

        <div class="section-header">

            <div>
                <h5>Daftar Ekstrakurikuler</h5>
                <small>Menampilkan <?= $totalData; ?> data ekstrakurikuler</small>
            </div>

            <div class="search-area">

                <form method="GET" class="search-form">

                    <div class="search-wrap">
                        <i class="bi bi-search"></i>
                        <input type="text" name="cari" class="form-control"
                               placeholder="Cari ekstrakurikuler..."
                               value="<?= htmlspecialchars($cari); ?>">
                    </div>

                    <button type="submit" class="btn-search" title="Cari">
                        <i class="bi bi-search"></i>
                    </button>

                    <?php if ($cari !== ""): ?>
                        <a href="ekstrakurikuler.php" class="btn-reset" title="Reset">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    <?php endif; ?>

                </form>

                <button type="button" class="btn-add"
                        data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="bi bi-plus-lg"></i>
                    Tambah Data
                </button>

            </div>

        </div>


        <!-- TABLE -->
        <div class="table-responsive">

            <table class="table">

                <thead>
                    <tr>
                        <th style="width:60px;">No</th>
                        <th>Ekstrakurikuler</th>
                        <th>Pembina</th>
                        <th>Lokasi</th>
                        <th style="width:150px;" class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($totalData > 0): ?>

                    <?php $no = 1; foreach ($rowsEkskul as $row): ?>

                    <tr>

                        <td>
                            <div class="number-box"><?= $no++; ?></div>
                        </td>

                        <td>
                            <div class="nama-ekskul">
                                <div class="ekskul-icon">
                                    <i class="bi bi-stars"></i>
                                </div>
                                <div>
                                    <strong><?= htmlspecialchars($row['nama_ekskul']); ?></strong>
                                    <small>Ekstrakurikuler sekolah</small>
                                </div>
                            </div>
                        </td>

                        <td>
                            <i class="bi bi-person-badge-fill me-2" style="color:#0284c7;"></i>
                            <?= htmlspecialchars($row['pembina']); ?>
                        </td>

                        <td>
                            <i class="bi bi-geo-alt-fill me-2" style="color:#0f766e;"></i>
                            <?= htmlspecialchars($row['lokasi']); ?>
                        </td>

                        <td class="text-center">
                            <div class="action-buttons justify-content-center">

                                <!-- LIHAT JADWAL -->
                                <a href="jadwal.php?ekskul=<?= (int)$row['id']; ?>"
                                   class="btn-action btn-jadwal" title="Lihat Jadwal">
                                    <i class="bi bi-calendar-event"></i>
                                </a>

                                <!-- EDIT -->
                                <button type="button" class="btn-action btn-edit" title="Edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEdit<?= $row['id']; ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </button>

                                <!-- HAPUS -->
                                <a href="ekstrakurikuler.php?hapus=<?= $row['id']; ?>"
                                   class="btn-action btn-delete" title="Hapus"
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus data ekstrakurikuler ini?');">
                                    <i class="bi bi-trash3-fill"></i>
                                </a>

                            </div>
                        </td>

                    </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <strong>Belum Ada Data</strong>
                                <span>
                                    <?= $cari !== ""
                                        ? "Tidak ada ekstrakurikuler yang cocok dengan pencarian."
                                        : "Silakan tambahkan data ekstrakurikuler terlebih dahulu."; ?>
                                </span>
                            </div>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- MODAL EDIT -->
    <?php foreach ($rowsEkskul as $row): ?>

    <div class="modal fade" id="modalEdit<?= $row['id']; ?>" tabindex="-1" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2"></i>
                        Edit Ekstrakurikuler
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form method="POST">

                    <div class="modal-body">

                        <input type="hidden" name="id" value="<?= $row['id']; ?>">

                        <div class="mb-3">
                            <label class="form-label">Nama Ekstrakurikuler</label>
                            <input type="text" name="nama_ekskul" class="form-control"
                                   value="<?= htmlspecialchars($row['nama_ekskul']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Pembina</label>
                            <input type="text" name="pembina" class="form-control"
                                   value="<?= htmlspecialchars($row['pembina']); ?>" required>
                        </div>

                        <div>
                            <label class="form-label">Lokasi</label>
                            <input type="text" name="lokasi" class="form-control"
                                   value="<?= htmlspecialchars($row['lokasi']); ?>" required>
                        </div>

                        <div class="alert alert-info mt-3 mb-0" style="font-size:12px;">
                            <i class="bi bi-info-circle me-1"></i>
                            Untuk mengubah jadwal, buka menu
                            <a href="jadwal.php?ekskul=<?= (int)$row['id']; ?>" class="alert-link">Jadwal</a>.
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-cancel" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit" class="btn-save">
                            <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                        </button>
                    </div>

                </form>

            </div>

        </div>

    </div>

    <?php endforeach; ?>


</div>


<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>
                    Tambah Ekstrakurikuler
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST">

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Nama Ekstrakurikuler</label>
                        <input type="text" name="nama_ekskul" class="form-control"
                               placeholder="Contoh: Futsal" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pembina</label>
                        <input type="text" name="pembina" class="form-control"
                               placeholder="Contoh: Bapak Andi" required>
                    </div>

                    <div>
                        <label class="form-label">Lokasi</label>
                        <input type="text" name="lokasi" class="form-control"
                               placeholder="Contoh: Lapangan Sekolah" required>
                    </div>

                    <div class="alert alert-info mt-3 mb-0" style="font-size:12px;">
                        <i class="bi bi-info-circle me-1"></i>
                        Setelah data disimpan, atur <strong>jadwal</strong> di menu
                        <strong>Jadwal</strong>.
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn-save">
                        <i class="bi bi-save me-1"></i> Simpan Data
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>