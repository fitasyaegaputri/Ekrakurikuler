<?php
/* =========================================================
   PENDAFTARAN - ADMIN (MONITOR ONLY)
   Admin hanya memantau. Persetujuan oleh PEMBINA.
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


/* =========================================================
   PENCARIAN & FILTER
========================================================= */
$keyword      = trim($_GET['keyword'] ?? "");
$statusFilter = $_GET['status'] ?? "";

$where = [];

if ($keyword !== "") {
    $keywordSafe = mysqli_real_escape_string($conn, $keyword);
    $where[] = "(s.nama_siswa LIKE '%$keywordSafe%' OR e.nama_ekskul LIKE '%$keywordSafe%')";
}

if ($statusFilter !== "") {
    $statusSafe = mysqli_real_escape_string($conn, $statusFilter);
    $where[] = "p.status = '$statusSafe'";
}

$whereSQL = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";


/* =========================================================
   DATA PENDAFTARAN
========================================================= */
$listPendaftaran = [];

$queryPendaftaran = mysqli_query($conn,
    "SELECT
        p.id, p.siswa_id, p.ekskul_id, p.status,
        s.nama_siswa,
        e.nama_ekskul
     FROM pendaftaran p
     INNER JOIN siswa s ON p.siswa_id = s.id
     INNER JOIN ekstrakurikuler e ON p.ekskul_id = e.id
     $whereSQL
     ORDER BY p.id DESC");

if (!$queryPendaftaran) {
    die("Error query: " . mysqli_error($conn));
}

while ($r = mysqli_fetch_assoc($queryPendaftaran)) {
    $listPendaftaran[] = $r;
}


/* =========================================================
   STATISTIK
========================================================= */
$totalPendaftaran = 0;
$totalMenunggu    = 0;
$totalDiterima    = 0;
$totalDitolak     = 0;

$queryStatistik = mysqli_query($conn,
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) AS menunggu,
        SUM(CASE WHEN status = 'diterima' THEN 1 ELSE 0 END) AS diterima,
        SUM(CASE WHEN status = 'ditolak'  THEN 1 ELSE 0 END) AS ditolak
     FROM pendaftaran");

if ($queryStatistik) {
    $stat = mysqli_fetch_assoc($queryStatistik);
    $totalPendaftaran = (int)($stat['total']    ?? 0);
    $totalMenunggu    = (int)($stat['menunggu'] ?? 0);
    $totalDiterima    = (int)($stat['diterima'] ?? 0);
    $totalDitolak     = (int)($stat['ditolak']  ?? 0);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Monitor Pendaftaran - Sistem Informasi Ekstrakurikuler">

<title>Monitor Pendaftaran - Admin</title>

<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="dns-prefetch" href="https://cdn.jsdelivr.net">

<link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></noscript>

<link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"></noscript>

<style>
/* SCROLLBAR HIDDEN */
::-webkit-scrollbar { width: 0; height: 0; display: none; }
html { scrollbar-width: none; -ms-overflow-style: none; }
body::-webkit-scrollbar { display: none; }
.sidebar::-webkit-scrollbar { width: 0; display: none; }

/* GLOBAL */
* { box-sizing: border-box; }
body {
    margin: 0;
    background: #f3f7fa;
    color: #172033;
    font-family: "Segoe UI", Arial, sans-serif;
}

/* SIDEBAR */
.sidebar {
    position: fixed; left: 0; top: 0;
    width: 255px; height: 100vh;
    background: linear-gradient(180deg, #075985 0%, #0f766e 100%);
    color: #fff; padding: 22px 15px;
    z-index: 1000; overflow-y: auto;
    box-shadow: 8px 0 25px rgba(15,23,42,.08);
    display: flex; flex-direction: column;
}
.brand {
    text-align: center; padding: 8px 8px 23px;
    border-bottom: 1px solid rgba(255,255,255,.15);
}
.brand-icon {
    width: 60px; height: 60px; margin: auto;
    border-radius: 17px; display: flex;
    align-items: center; justify-content: center;
    background: rgba(255,255,255,.14); font-size: 27px;
}
.brand h4 { margin: 11px 0 3px; font-weight: 700; }
.brand small { color: rgba(255,255,255,.85); }
.menu-title {
    margin: 25px 11px 9px; color: rgba(255,255,255,.75);
    font-size: 10px; text-transform: uppercase; letter-spacing: 1px;
}
.sidebar a {
    display: flex; align-items: center; gap: 13px;
    padding: 12px 14px; margin-bottom: 5px; border-radius: 11px;
    color: rgba(255,255,255,.92); text-decoration: none; transition: .2s;
}
.sidebar a i { width: 21px; text-align: center; font-size: 17px; }
.sidebar a:hover {
    color: #fff; background: rgba(255,255,255,.12);
    transform: translateX(2px);
}
.sidebar a.active {
    color: #fff; background: rgba(255,255,255,.18);
    box-shadow: inset 3px 0 0 #fff;
}
.logout {
    margin-top: auto; padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,.15);
}
.logout a {
    background: rgba(220,38,38,.9); color: #fff;
    display: flex; align-items: center; gap: 13px;
    padding: 12px 14px; border-radius: 11px;
    text-decoration: none; font-weight: 600; transition: .2s;
}
.logout a:hover { background: #dc2626; transform: translateX(2px); }

/* CONTENT */
.content { margin-left: 255px; padding: 28px; }

/* TOPBAR */
.topbar {
    background: white; min-height: 72px;
    padding: 14px 20px; border-radius: 17px;
    display: flex; align-items: center; justify-content: space-between;
    box-shadow: 0 5px 22px rgba(15,23,42,.06); margin-bottom: 22px;
}
.topbar-title h1 { margin: 0; font-size: 18px; font-weight: 700; }
.topbar-title small { color: #475569; }
.admin-area { display: flex; align-items: center; gap: 11px; }
.admin-info { text-align: right; }
.admin-info strong { display: block; font-size: 14px; }
.admin-info small { color: #475569; }
.admin-avatar {
    width: 44px; height: 44px; border-radius: 13px;
    background: #e0f2fe; color: #0369a1;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 17px;
}

/* WELCOME */
.welcome {
    position: relative; overflow: hidden;
    background: linear-gradient(135deg, #0284c7, #0f766e);
    color: white; border-radius: 21px;
    padding: 29px 31px; margin-bottom: 22px;
    box-shadow: 0 12px 30px rgba(14,116,144,.18);
}
.welcome::before {
    content: ""; position: absolute;
    width: 220px; height: 220px; border-radius: 50%;
    background: rgba(255,255,255,.07);
    right: -65px; top: -100px;
}
.welcome-content { position: relative; z-index: 2; }
.welcome-badge {
    display: inline-flex; align-items: center; gap: 7px;
    background: rgba(255,255,255,.13);
    border: 1px solid rgba(255,255,255,.16);
    padding: 6px 11px; border-radius: 20px;
    font-size: 12px; margin-bottom: 12px;
}
.welcome h2 { margin: 0 0 7px; font-size: 27px; font-weight: 700; }
.welcome p { margin: 0; color: rgba(255,255,255,.95); }

/* STAT CARD */
.stat-card {
    background: white; border-radius: 17px; padding: 20px;
    min-height: 145px; box-shadow: 0 5px 22px rgba(15,23,42,.055);
    border: 1px solid #edf2f5;
}
.stat-top { display: flex; align-items: center; justify-content: space-between; }
.stat-icon {
    width: 45px; height: 45px; border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
}
.icon-blue   { background: #e0f2fe; color: #0284c7; }
.icon-orange { background: #ffedd5; color: #c2410c; }
.icon-green  { background: #dcfce7; color: #166534; }
.icon-red    { background: #fee2e2; color: #991b1b; }
.stat-arrow { color: #94a3b8; font-size: 19px; }
.stat-number { font-size: 27px; font-weight: 750; margin-top: 15px; }
.stat-label { color: #475569; font-size: 13px; }

/* SECTION CARD */
.section-card {
    background: white; border-radius: 18px;
    box-shadow: 0 5px 22px rgba(15,23,42,.055);
    border: 1px solid #edf2f5; overflow: hidden;
}
.section-header {
    padding: 18px 20px; border-bottom: 1px solid #eef2f5;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 15px;
}
.section-header h2 { margin: 0; font-size: 16px; font-weight: 700; }
.section-header small { color: #475569; }

/* SEARCH */
.search-area {
    padding: 18px 20px; background: #f8fafc;
    border-bottom: 1px solid #eef2f5;
}
.search-input { position: relative; }
.search-input i {
    position: absolute; left: 13px; top: 50%;
    transform: translateY(-50%); color: #64748b;
}
.search-input input {
    padding-left: 39px; min-height: 43px;
    border: 1px solid #e2e8f0; border-radius: 11px;
}
.search-area .form-select {
    min-height: 43px; border-radius: 11px;
    border: 1px solid #e2e8f0;
}

/* TABLE */
.table { margin: 0; }
.table thead th {
    background: #f8fafc; border: none; color: #475569;
    font-size: 11px; text-transform: uppercase;
    padding: 13px 20px; white-space: nowrap;
}
.table tbody td {
    padding: 14px 20px; vertical-align: middle;
    border-color: #f1f5f9; font-size: 13px;
}
.table tbody tr:hover { background: #f8fafc; }

.number-box {
    width: 32px; height: 32px; border-radius: 10px;
    background: #f1f5f9; color: #475569; font-weight: 600;
    font-size: 12px; display: inline-flex;
    align-items: center; justify-content: center;
}
.student-box { display: flex; align-items: center; gap: 12px; }
.student-avatar {
    width: 42px; height: 42px; flex-shrink: 0;
    border-radius: 12px; background: #e0f2fe; color: #0284c7;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
}
.student-info strong { display: block; font-size: 13px; }
.student-info small { color: #475569; font-size: 11px; }

.ekskul-box { display: flex; align-items: center; gap: 12px; }
.ekskul-icon {
    width: 40px; height: 40px; flex-shrink: 0;
    border-radius: 11px; background: #e0f2fe; color: #0284c7;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px;
}
.ekskul-name { font-weight: 600; font-size: 13px; }

.status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
}
.status-menunggu { background: #fef3c7; color: #92400e; }
.status-diterima { background: #dcfce7; color: #166534; }
.status-ditolak  { background: #fee2e2; color: #991b1b; }

.btn-detail {
    display: inline-flex; align-items: center; gap: 5px;
    border: none; border-radius: 10px;
    padding: 7px 12px; font-size: 12px; font-weight: 600;
    background: #e0f2fe; color: #0284c7;
    text-decoration: none; cursor: pointer; transition: .2s;
}
.btn-detail:hover { background: #0284c7; color: white; }

/* MODAL */
.modal-content { border: none; border-radius: 18px; overflow: hidden; }
.modal-header {
    background: linear-gradient(135deg, #0284c7, #0f766e);
    color: white; border: none; padding: 17px 20px;
}
.modal-header .btn-close { filter: brightness(0) invert(1); }
.modal-title { font-size: 16px; font-weight: 700; }
.modal-body { padding: 22px; }
.modal-footer { border-top: 1px solid #f1f5f9; padding: 15px 22px; }
.form-label { font-size: 12px; font-weight: 600; color: #475569; }
.form-control {
    border-radius: 10px; min-height: 43px;
    font-size: 13px; background: #f8fafc;
}
.form-control[readonly] { background: #f1f5f9; color: #475569; }
.btn-cancel {
    border: 1px solid #e2e8f0; border-radius: 10px;
    padding: 9px 17px; background: white;
    color: #475569; font-size: 13px;
}

/* EMPTY STATE */
.empty-state {
    padding: 45px 20px; text-align: center; color: #475569;
}
.empty-state i { font-size: 35px; color: #94a3b8; display: block; margin-bottom: 10px; }
.empty-state strong { display: block; color: #172033; margin-bottom: 4px; }
.empty-state span { font-size: 12px; color: #64748b; }

/* ALERT INFO */
.alert-info-soft {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    color: #0369a1;
    border-radius: 12px;
    padding: 13px 18px;
    margin-bottom: 22px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* RESPONSIVE */
@media(max-width: 992px) {
    .sidebar { width: 220px; }
    .content { margin-left: 220px; padding: 20px; }
}
@media(max-width: 768px) {
    .sidebar {
        position: relative; width: 100%;
        height: auto; min-height: auto;
    }
    .logout { margin-top: 20px; }
    .content { margin-left: 0; padding: 15px; }
    .topbar { flex-direction: column; align-items: flex-start; gap: 13px; }
    .admin-info { text-align: left; }
    .welcome h2 { font-size: 23px; }
    .section-header { flex-direction: column; align-items: flex-start; }
}
</style>

</head>

<body>

<!-- SIDEBAR -->
<nav class="sidebar" aria-label="Menu utama">

    <div class="brand">
        <div class="brand-icon" aria-hidden="true">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <h4>Ekstrakurikuler</h4>
        <small>Administrator</small>
    </div>

    <div class="menu-title">Menu Utama</div>

    <a href="dashboard_admin.php">
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

    <a href="pendaftaran.php" class="active">
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
<main class="content">

    <!-- TOPBAR -->
    <header class="topbar">
        <div class="topbar-title">
            <h1 class="h5">Monitor Pendaftaran</h1>
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
                <i class="bi bi-eye-fill" aria-hidden="true"></i>
                Mode Monitor
            </div>
            <h2>Pantau Pendaftaran</h2>
            <p>
                Anda hanya <strong>memantau</strong> semua pendaftaran siswa.
                Persetujuan dilakukan oleh <strong>Pembina Ekstrakurikuler</strong>.
            </p>
        </div>
    </section>


    <!-- INFO -->
    <div class="alert-info-soft" role="alert">
        <i class="bi bi-info-circle-fill fs-5" aria-hidden="true"></i>
        <div>
            <strong>Admin hanya memantau.</strong>
            Untuk menerima atau menolak pendaftaran, silakan login sebagai
            <strong>Pembina</strong>.
        </div>
    </div>


    <!-- STATISTIK -->
    <section class="row g-3 mb-4" aria-label="Statistik pendaftaran">

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-blue" aria-hidden="true">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                    <div class="stat-arrow" aria-hidden="true">
                        <i class="bi bi-three-dots"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $totalPendaftaran; ?></div>
                <div class="stat-label">Total Pendaftaran</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-orange" aria-hidden="true">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="stat-arrow" aria-hidden="true">
                        <i class="bi bi-three-dots"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $totalMenunggu; ?></div>
                <div class="stat-label">Menunggu Pembina</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-green" aria-hidden="true">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="stat-arrow" aria-hidden="true">
                        <i class="bi bi-three-dots"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $totalDiterima; ?></div>
                <div class="stat-label">Diterima</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-red" aria-hidden="true">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                    <div class="stat-arrow" aria-hidden="true">
                        <i class="bi bi-three-dots"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $totalDitolak; ?></div>
                <div class="stat-label">Ditolak</div>
            </div>
        </div>

    </section>


    <!-- DATA PENDAFTARAN -->
    <section class="section-card">

        <div class="section-header">
            <div>
                <h2><i class="bi bi-eye-fill me-1" aria-hidden="true"></i> Monitor Pendaftaran</h2>
                <small>Menampilkan <?= count($listPendaftaran); ?> data pendaftaran</small>
            </div>
        </div>


        <!-- SEARCH -->
        <div class="search-area">
            <form method="GET" action="pendaftaran.php">
                <div class="row g-2">

                    <div class="col-lg-6">
                        <div class="search-input">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input type="text" name="keyword" class="form-control"
                                   placeholder="Cari nama siswa atau ekstrakurikuler..."
                                   value="<?= htmlspecialchars($keyword); ?>">
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="menunggu" <?= $statusFilter === "menunggu" ? "selected" : ""; ?>>Menunggu</option>
                            <option value="diterima" <?= $statusFilter === "diterima" ? "selected" : ""; ?>>Diterima</option>
                            <option value="ditolak"  <?= $statusFilter === "ditolak"  ? "selected" : ""; ?>>Ditolak</option>
                        </select>
                    </div>

                    <div class="col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1"
                                style="height:43px;border-radius:11px;">
                            <i class="bi bi-search me-1" aria-hidden="true"></i> Cari
                        </button>

                        <?php if ($keyword !== "" || $statusFilter !== ""): ?>
                            <a href="pendaftaran.php" class="btn btn-light border d-flex align-items-center"
                               style="height:43px;border-radius:11px;" title="Reset">
                                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                            </a>
                        <?php endif; ?>
                    </div>

                </div>
            </form>
        </div>


        <!-- TABLE -->
        <?php if (count($listPendaftaran) > 0): ?>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th width="60">No</th>
                        <th>Siswa</th>
                        <th>Ekstrakurikuler</th>
                        <th>Status</th>
                        <th width="140" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no = 1; foreach ($listPendaftaran as $row): ?>
                    <?php
                    $st = strtolower(trim($row['status']));
                    if ($st === 'diterima') {
                        $cls = 'status-diterima'; $icon = 'bi-check-circle-fill';
                    } elseif ($st === 'ditolak') {
                        $cls = 'status-ditolak';  $icon = 'bi-x-circle-fill';
                    } else {
                        $cls = 'status-menunggu'; $icon = 'bi-hourglass-split';
                    }
                    ?>
                    <tr>
                        <td>
                            <div class="number-box"><?= $no++; ?></div>
                        </td>
                        <td>
                            <div class="student-box">
                                <div class="student-avatar" aria-hidden="true">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div class="student-info">
                                    <strong><?= htmlspecialchars($row['nama_siswa']); ?></strong>
                                    <small>ID Siswa: <?= htmlspecialchars($row['siswa_id']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="ekskul-box">
                                <div class="ekskul-icon" aria-hidden="true">
                                    <i class="bi bi-stars"></i>
                                </div>
                                <div class="ekskul-name"><?= htmlspecialchars($row['nama_ekskul']); ?></div>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge <?= $cls; ?>">
                                <i class="bi <?= $icon; ?>" aria-hidden="true"></i>
                                <?= htmlspecialchars(ucfirst($row['status'])); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn-detail"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalDetail<?= (int)$row['id']; ?>">
                                <i class="bi bi-eye-fill" aria-hidden="true"></i>
                                Lihat
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php else: ?>

        <div class="empty-state">
            <i class="bi bi-inbox" aria-hidden="true"></i>
            <strong>Belum Ada Data Pendaftaran</strong>
            <span>
                <?= ($keyword !== "" || $statusFilter !== "")
                    ? "Tidak ada data yang cocok dengan filter."
                    : "Belum ada siswa yang mendaftar."; ?>
            </span>
        </div>

        <?php endif; ?>


        <!-- MODAL DETAIL (READ-ONLY) -->
        <?php foreach ($listPendaftaran as $modal): ?>
        <div class="modal fade" id="modalDetail<?= (int)$modal['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                            Detail Pendaftaran
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">

                        <div class="mb-3">
                            <label class="form-label">Nama Siswa</label>
                            <input type="text" class="form-control"
                                   value="<?= htmlspecialchars($modal['nama_siswa']); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ekstrakurikuler</label>
                            <input type="text" class="form-control"
                                   value="<?= htmlspecialchars($modal['nama_ekskul']); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <input type="text" class="form-control"
                                   value="<?= htmlspecialchars(ucfirst($modal['status'])); ?>" readonly>
                        </div>

                        <div class="alert alert-info mb-0" style="font-size:12px;border-radius:10px;">
                            <i class="bi bi-info-circle-fill me-1" aria-hidden="true"></i>
                            Status pendaftaran ini diproses oleh
                            <strong>Pembina Ekstrakurikuler</strong>.
                            Admin hanya memantau.
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-cancel" data-bs-dismiss="modal">
                            Tutup
                        </button>
                    </div>

                </div>
            </div>
        </div>
        <?php endforeach; ?>

    </section>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

</body>
</html>