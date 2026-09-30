<?php
/* =========================================================
   DASHBOARD PEMBINA - SISTEM INFORMASI EKSTRAKURIKULER
   Fitur: Dashboard, Pendaftaran Masuk (terima/tolak),
          Absensi, Jadwal
   Routing via ?page=
========================================================= */

session_start();
require_once __DIR__ . "/../config/config.php";

/* CEK KONEKSI */
if (!$conn) {
    die("Koneksi database gagal.");
}

/* CEK LOGIN */
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

/* CEK ROLE PEMBINA */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "pembina") {
    header("Location: ../login.php");
    exit();
}

$userId = (int) $_SESSION['id'];
$namaPembina = $_SESSION['nama'] ?? "Pembina";
$inisial = strtoupper(substr(trim($namaPembina), 0, 1));


/* =========================================================
   ROUTING
========================================================= */
$page = $_GET['page'] ?? 'dashboard';

$allowedPages = [
    'dashboard',
    'pendaftaran_masuk',
];

if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}


/* =========================================================
   AMBIL EKSKUL YANG DIAMPU PEMBINA
========================================================= */
$listEkskulPembina = [];
$stmtCek = mysqli_prepare($conn,
    "SELECT e.id, e.nama_ekskul
     FROM ekstrakurikuler e
     INNER JOIN pembina_ekskul pe ON pe.ekskul_id = e.id
     WHERE pe.user_id = ?
     ORDER BY e.nama_ekskul ASC");

if ($stmtCek) {
    mysqli_stmt_bind_param($stmtCek, "i", $userId);
    mysqli_stmt_execute($stmtCek);
    $resCek = mysqli_stmt_get_result($stmtCek);
    while ($r = mysqli_fetch_assoc($resCek)) {
        $listEkskulPembina[] = $r;
    }
    mysqli_stmt_close($stmtCek);
}

$ekskulIds = array_map('intval', array_column($listEkskulPembina, 'id'));


/* =========================================================
   PROSES TERIMA/TOLAK (halaman pendaftaran_masuk)
========================================================= */
$pesan = "";
$tipePesan = "";

if ($page === 'pendaftaran_masuk' && isset($_POST['aksi']) && isset($_POST['pendaftaran_id'])) {

    $pendaftaran_id = (int) $_POST['pendaftaran_id'];
    $aksi = $_POST['aksi'];
    $status_baru = ($aksi === "terima") ? "diterima" : "ditolak";

    $stmtCekP = mysqli_prepare($conn,
        "SELECT p.id, p.ekskul_id FROM pendaftaran p WHERE p.id = ? LIMIT 1");

    if ($stmtCekP) {
        mysqli_stmt_bind_param($stmtCekP, "i", $pendaftaran_id);
        mysqli_stmt_execute($stmtCekP);
        $resP = mysqli_stmt_get_result($stmtCekP);
        $dataP = mysqli_fetch_assoc($resP);
        mysqli_stmt_close($stmtCekP);

        if (!$dataP) {
            $pesan = "Data pendaftaran tidak ditemukan.";
            $tipePesan = "danger";
        } elseif (!in_array((int)$dataP['ekskul_id'], $ekskulIds, true)) {
            $pesan = "Anda tidak berhak memproses pendaftaran ini.";
            $tipePesan = "danger";
        } else {
            $stmtUpdate = mysqli_prepare($conn,
                "UPDATE pendaftaran SET status = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmtUpdate, "si", $status_baru, $pendaftaran_id);

            if (mysqli_stmt_execute($stmtUpdate)) {
                $pesan = "Pendaftaran berhasil di" . ($aksi === "terima" ? "terima" : "tolak") . ".";
                $tipePesan = "success";
            } else {
                $pesan = "Gagal memperbarui status: " . mysqli_error($conn);
                $tipePesan = "danger";
            }
            mysqli_stmt_close($stmtUpdate);
        }
    }
}


/* =========================================================
   DATA PENDAFTARAN MASUK (untuk halaman pendaftaran_masuk)
========================================================= */
$listPendaftaran = [];
$totalMasuk = 0;
$totalMenunggu = 0;
$totalDiterima = 0;
$totalDitolak = 0;

if ($page === 'pendaftaran_masuk' && count($ekskulIds) > 0) {

    $placeholders = implode(",", array_fill(0, count($ekskulIds), "?"));
    $types = str_repeat("i", count($ekskulIds));

    $stmtList = mysqli_prepare($conn,
        "SELECT p.id, p.siswa_id, p.status, p.ekskul_id,
                s.nama_siswa, s.nis, s.kelas,
                e.nama_ekskul
         FROM pendaftaran p
         INNER JOIN siswa s ON p.siswa_id = s.id
         INNER JOIN ekstrakurikuler e ON p.ekskul_id = e.id
         WHERE p.ekskul_id IN ($placeholders)
         ORDER BY FIELD(p.status, 'menunggu', 'diterima', 'ditolak'), p.id DESC");

    if ($stmtList) {
        mysqli_stmt_bind_param($stmtList, $types, ...$ekskulIds);
        mysqli_stmt_execute($stmtList);
        $resList = mysqli_stmt_get_result($stmtList);
        while ($r = mysqli_fetch_assoc($resList)) {
            $listPendaftaran[] = $r;
        }
        mysqli_stmt_close($stmtList);
    }

    $totalMasuk = count($listPendaftaran);
    foreach ($listPendaftaran as $p) {
        $st = strtolower($p['status']);
        if ($st === 'menunggu') $totalMenunggu++;
        elseif ($st === 'diterima') $totalDiterima++;
        elseif ($st === 'ditolak') $totalDitolak++;
    }
}


/* =========================================================
   STATISTIK DASHBOARD
========================================================= */
$totalEkskulPembina = count($listEkskulPembina);

$totalSiswaDiterima = 0;
$totalJadwal = 0;

if (count($ekskulIds) > 0) {

    /* Total siswa diterima di ekskul pembina */
    $stmtS = mysqli_prepare($conn,
        "SELECT COUNT(*) AS total
         FROM pendaftaran p
         WHERE p.ekskul_id IN (" . implode(",", array_fill(0, count($ekskulIds), "?")) . ")
           AND p.status = 'diterima'");
    if ($stmtS) {
        mysqli_stmt_bind_param($stmtS, str_repeat("i", count($ekskulIds)), ...$ekskulIds);
        mysqli_stmt_execute($stmtS);
        $resS = mysqli_stmt_get_result($stmtS);
        $ds = mysqli_fetch_assoc($resS);
        $totalSiswaDiterima = (int)($ds['total'] ?? 0);
        mysqli_stmt_close($stmtS);
    }

    /* Total jadwal ekskul pembina */
    $stmtJ = mysqli_prepare($conn,
        "SELECT COUNT(*) AS total
         FROM jadwal
         WHERE ekskul_id IN (" . implode(",", array_fill(0, count($ekskulIds), "?")) . ")");
    if ($stmtJ) {
        mysqli_stmt_bind_param($stmtJ, str_repeat("i", count($ekskulIds)), ...$ekskulIds);
        mysqli_stmt_execute($stmtJ);
        $resJ = mysqli_stmt_get_result($stmtJ);
        $dj = mysqli_fetch_assoc($resJ);
        $totalJadwal = (int)($dj['total'] ?? 0);
        mysqli_stmt_close($stmtJ);
    }
}

/* Total pendaftaran menunggu di ekskul pembina */
$totalMenungguDash = 0;
if (count($ekskulIds) > 0) {
    $stmtM = mysqli_prepare($conn,
        "SELECT COUNT(*) AS total
         FROM pendaftaran
         WHERE ekskul_id IN (" . implode(",", array_fill(0, count($ekskulIds), "?")) . ")
           AND status = 'menunggu'");
    if ($stmtM) {
        mysqli_stmt_bind_param($stmtM, str_repeat("i", count($ekskulIds)), ...$ekskulIds);
        mysqli_stmt_execute($stmtM);
        $resM = mysqli_stmt_get_result($stmtM);
        $dm = mysqli_fetch_assoc($resM);
        $totalMenungguDash = (int)($dm['total'] ?? 0);
        mysqli_stmt_close($stmtM);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Pembina - Sistem Informasi Ekstrakurikuler</title>

<!-- PERFORMANCE -->
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="dns-prefetch" href="https://cdn.jsdelivr.net">

<link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></noscript>

<link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"></noscript>

<style>
/* =====================================================
   SCROLLBAR
===================================================== */
::-webkit-scrollbar { width: 8px; height: 8px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: rgba(15, 118, 110, .25); border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: rgba(15, 118, 110, .5); }
html { scrollbar-width: thin; scrollbar-color: rgba(15, 118, 110, .25) transparent; }
.sidebar::-webkit-scrollbar { width: 6px; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, .15); }

/* =====================================================
   ACCESSIBILITY
===================================================== */
:focus-visible { outline: 2px solid #0284c7; outline-offset: 2px; }
button:focus-visible, a:focus-visible, input:focus-visible,
select:focus-visible, textarea:focus-visible {
    outline: 2px solid #0284c7; outline-offset: 2px;
}
.skip-link {
    position: absolute; top: -50px; left: 6px;
    background: #0284c7; color: white; padding: 10px 18px;
    border-radius: 8px; text-decoration: none; z-index: 99999;
    font-weight: 600; transition: top .2s;
}
.skip-link:focus { top: 10px; color: white; }

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
    position: fixed; left: 0; top: 0;
    width: 255px; height: 100vh;
    background: linear-gradient(180deg, #075985 0%, #0f766e 100%);
    color: white; padding: 22px 15px;
    z-index: 1000; overflow-y: auto;
    box-shadow: 8px 0 25px rgba(15,23,42,.08);
}
.brand { text-align: center; padding: 8px 8px 23px; border-bottom: 1px solid rgba(255,255,255,.15); }
.brand-icon {
    width: 60px; height: 60px; margin: auto;
    border-radius: 17px; display: flex; align-items: center;
    justify-content: center; background: rgba(255,255,255,.14);
    font-size: 27px;
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
.sidebar a:hover { color: #fff; background: rgba(255,255,255,.12); transform: translateX(2px); }
.sidebar a.active { color: #fff; background: rgba(255,255,255,.18); box-shadow: inset 3px 0 0 #fff; }
.sidebar .badge-menunggu {
    background: #f59e0b; color: white;
    font-size: 10px; font-weight: 700;
    padding: 3px 7px; border-radius: 10px;
    margin-left: auto;
}
.logout { margin-top: 20px; }
.logout a { background: rgba(220,38,38,.9); color: #fff; }
.logout a:hover { background: #dc2626; }

/* =====================================================
   CONTENT
===================================================== */
.content { margin-left: 255px; padding: 28px; }

/* =====================================================
   TOPBAR
===================================================== */
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

/* =====================================================
   WELCOME
===================================================== */
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
.welcome::after {
    content: ""; position: absolute;
    width: 120px; height: 120px; border-radius: 50%;
    background: rgba(255,255,255,.06);
    right: 120px; bottom: -75px;
}
.welcome-content { position: relative; z-index: 2; }
.welcome-badge {
    display: inline-flex; align-items: center; gap: 7px;
    background: rgba(255,255,255,.13); border: 1px solid rgba(255,255,255,.16);
    padding: 6px 11px; border-radius: 20px;
    font-size: 12px; margin-bottom: 12px;
}
.welcome h2 { margin: 0 0 7px; font-size: 27px; font-weight: 700; }
.welcome p { margin: 0; color: rgba(255,255,255,.95); }

/* =====================================================
   STAT CARD
===================================================== */
.stat-card {
    background: white; border-radius: 17px; padding: 20px;
    min-height: 145px; box-shadow: 0 5px 22px rgba(15,23,42,.055);
    border: 1px solid #edf2f5; transition: .2s;
}
.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 28px rgba(15,23,42,.09);
}
.stat-top { display: flex; align-items: center; justify-content: space-between; }
.stat-icon {
    width: 45px; height: 45px; border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
}
.icon-blue   { background:#e0f2fe; color:#0284c7; }
.icon-green  { background:#dcfce7; color:#166534; }
.icon-orange { background:#ffedd5; color:#c2410c; }
.icon-teal   { background:#ccfbf1; color:#0f766e; }
.icon-red    { background:#fee2e2; color:#991b1b; }
.icon-purple { background:#ede9fe; color:#6d28d9; }
.stat-arrow { color: #94a3b8; font-size: 19px; }
.stat-number { font-size: 27px; font-weight: 750; margin-top: 15px; }
.stat-label { color: #475569; font-size: 13px; }

/* =====================================================
   SECTION CARD
===================================================== */
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

/* =====================================================
   TABLE
===================================================== */
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

/* =====================================================
   STUDENT / EKSKUL BOX
===================================================== */
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

.number-box {
    width: 32px; height: 32px; border-radius: 10px;
    background: #f1f5f9; color: #475569; font-weight: 600;
    font-size: 12px; display: inline-flex;
    align-items: center; justify-content: center;
}

/* =====================================================
   STATUS BADGE
===================================================== */
.status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
}
.status-menunggu { background:#fef3c7; color:#92400e; }
.status-diterima { background:#dcfce7; color:#166534; }
.status-ditolak  { background:#fee2e2; color:#991b1b; }

/* =====================================================
   BUTTON
===================================================== */
.btn-terima, .btn-tolak {
    border: none; border-radius: 8px;
    padding: 7px 12px; font-size: 12px;
    font-weight: 600; cursor: pointer; transition: .2s;
}
.btn-terima { background: #dcfce7; color: #166534; }
.btn-terima:hover { background: #16a34a; color: white; }
.btn-tolak { background: #fee2e2; color: #991b1b; }
.btn-tolak:hover { background: #dc2626; color: white; }

.btn-primary-custom {
    display: inline-flex; align-items: center; gap: 7px;
    border: none; border-radius: 10px; padding: 10px 15px;
    background: linear-gradient(135deg, #0284c7, #0f766e);
    color: white; font-size: 13px; font-weight: 600;
    text-decoration: none; transition: .2s;
}
.btn-primary-custom:hover { color: white; transform: translateY(-1px); }

.btn-view {
    color: #0284c7; text-decoration: none;
    font-size: 12px; font-weight: 600;
}
.btn-view:hover { color: #0369a1; }

/* =====================================================
   EMPTY STATE
===================================================== */
.empty-state {
    padding: 45px 20px; text-align: center; color: #475569;
}
.empty-state i {
    font-size: 35px; color: #94a3b8;
    display: block; margin-bottom: 10px;
}
.empty-state strong { display: block; color: #172033; margin-bottom: 4px; }
.empty-state span { font-size: 12px; color: #64748b; }

/* =====================================================
   RESPONSIVE
===================================================== */
@media (max-width: 992px) {
    .sidebar { width: 220px; }
    .content { margin-left: 220px; padding: 20px; }
}

@media (max-width: 768px) {
    .sidebar { position: relative; width: 100%; height: auto; min-height: auto; }
    .logout { position: static; margin-top: 20px; }
    .content { margin-left: 0; padding: 15px; }
    .topbar { flex-direction: column; align-items: flex-start; gap: 13px; }
    .admin-info { text-align: left; }
    .welcome h2 { font-size: 23px; }
    .section-header { flex-direction: column; align-items: flex-start; }
}
</style>
</head>
<body>

<a href="#konten-utama" class="skip-link">Langsung ke konten</a>

<!-- =========================================================
     SIDEBAR
========================================================= -->
<nav class="sidebar" aria-label="Menu utama">
    <div class="brand">
        <div class="brand-icon" aria-hidden="true"><i class="bi bi-mortarboard-fill"></i></div>
        <h4>Ekstrakurikuler</h4>
        <small>Portal Pembina</small>
    </div>

    <div class="menu-title">Menu Utama</div>

    <a href="dashboard_pembina.php"
       class="<?= $page === 'dashboard' ? 'active' : ''; ?>">
        <i class="bi bi-grid-1x2-fill" aria-hidden="true"></i>
        <span>Dashboard</span>
    </a>

    <a href="dashboard_pembina.php?page=pendaftaran_masuk"
       class="<?= $page === 'pendaftaran_masuk' ? 'active' : ''; ?>">
        <i class="bi bi-inbox-fill" aria-hidden="true"></i>
        <span>Pendaftaran Masuk</span>
        <?php if ($totalMenungguDash > 0): ?>
            <span class="badge-menunggu"><?= $totalMenungguDash; ?></span>
        <?php endif; ?>
    </a>

    <a href="absensi_pembina.php">
        <i class="bi bi-calendar-check-fill" aria-hidden="true"></i>
        <span>Absensi</span>
    </a>

    <a href="jadwal_pembina.php">
        <i class="bi bi-calendar-event-fill" aria-hidden="true"></i>
        <span>Jadwal</span>
    </a>

    <div class="logout">
        <a href="../logout.php">
            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            <span>Logout</span>
        </a>
    </div>
</nav>


<!-- =========================================================
     CONTENT
========================================================= -->
<main class="content" id="konten-utama">

    <!-- TOPBAR -->
    <header class="topbar">
        <div class="topbar-title">
            <h1 class="h5">
                <?= $page === 'pendaftaran_masuk' ? 'Pendaftaran Masuk' : 'Dashboard Pembina'; ?>
            </h1>
            <small>Sistem Informasi Ekstrakurikuler</small>
        </div>
        <div class="admin-area">
            <div class="admin-info">
                <strong><?= htmlspecialchars($namaPembina); ?></strong>
                <small>Pembina</small>
            </div>
            <div class="admin-avatar" aria-hidden="true"><?= htmlspecialchars($inisial); ?></div>
        </div>
    </header>


<?php if ($page === 'pendaftaran_masuk'): ?>

    <!-- =================================================
         HALAMAN: PENDAFTARAN MASUK
    ================================================== -->
    <section class="welcome">
        <div class="welcome-content">
            <div class="welcome-badge">
                <i class="bi bi-inbox-fill" aria-hidden="true"></i>
                Kelola Pendaftaran
            </div>
            <h2>Pendaftaran Ekskul Anda</h2>
            <p>
                Terima atau tolak siswa yang mendaftar ke ekstrakurikuler yang Anda ampu.
            </p>
        </div>
    </section>

    <?php if ($pesan !== ""): ?>
    <div class="alert alert-<?= htmlspecialchars($tipePesan); ?> alert-dismissible fade show" role="alert">
        <i class="bi <?= $tipePesan === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2" aria-hidden="true"></i>
        <?= htmlspecialchars($pesan); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>


    <!-- STATISTIK -->
    <section class="row g-3 mb-4" aria-label="Statistik pendaftaran">
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-blue" aria-hidden="true"><i class="bi bi-inbox-fill"></i></div>
                </div>
                <div class="stat-number"><?= $totalMasuk; ?></div>
                <div class="stat-label">Total Pendaftaran</div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-orange" aria-hidden="true"><i class="bi bi-hourglass-split"></i></div>
                </div>
                <div class="stat-number"><?= $totalMenunggu; ?></div>
                <div class="stat-label">Menunggu Review</div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-green" aria-hidden="true"><i class="bi bi-check-circle-fill"></i></div>
                </div>
                <div class="stat-number"><?= $totalDiterima; ?></div>
                <div class="stat-label">Diterima</div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-red" aria-hidden="true"><i class="bi bi-x-circle-fill"></i></div>
                </div>
                <div class="stat-number"><?= $totalDitolak; ?></div>
                <div class="stat-label">Ditolak</div>
            </div>
        </div>
    </section>


    <!-- DAFTAR PENDAFTAR -->
    <section class="section-card">
        <div class="section-header">
            <div>
                <h2><i class="bi bi-inbox-fill me-1" aria-hidden="true"></i> Daftar Pendaftar</h2>
                <small>
                    <?php if (count($listEkskulPembina) > 0): ?>
                        Ekskul:
                        <?php foreach ($listEkskulPembina as $e): ?>
                            <span class="badge text-bg-light"><?= htmlspecialchars($e['nama_ekskul']); ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        Anda belum ditugaskan ke ekskul mana pun.
                    <?php endif; ?>
                </small>
            </div>
        </div>

        <?php if (count($listPendaftaran) > 0): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th width="60">No</th>
                        <th>Siswa</th>
                        <th>Ekstrakurikuler</th>
                        <th>Status</th>
                        <th width="220">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no = 1; foreach ($listPendaftaran as $row): ?>
                    <?php
                    $st = strtolower($row['status']);
                    if ($st === 'diterima') { $cls = 'status-diterima'; $icon = 'bi-check-circle-fill'; }
                    elseif ($st === 'ditolak') { $cls = 'status-ditolak'; $icon = 'bi-x-circle-fill'; }
                    else { $cls = 'status-menunggu'; $icon = 'bi-hourglass-split'; }
                    ?>
                    <tr>
                        <td><div class="number-box"><?= $no++; ?></div></td>
                        <td>
                            <div class="student-box">
                                <div class="student-avatar" aria-hidden="true"><i class="bi bi-person-fill"></i></div>
                                <div class="student-info">
                                    <strong><?= htmlspecialchars($row['nama_siswa']); ?></strong>
                                    <small>
                                        NIS: <?= htmlspecialchars($row['nis'] ?? '-'); ?>
                                        · Kelas: <?= htmlspecialchars($row['kelas'] ?? '-'); ?>
                                    </small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="ekskul-box">
                                <div class="ekskul-icon" aria-hidden="true"><i class="bi bi-stars"></i></div>
                                <div class="ekskul-name"><?= htmlspecialchars($row['nama_ekskul']); ?></div>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge <?= $cls; ?>">
                                <i class="bi <?= $icon; ?>" aria-hidden="true"></i>
                                <?= ucfirst(htmlspecialchars($row['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($st === 'menunggu'): ?>
                                <form method="POST" action="dashboard_pembina.php?page=pendaftaran_masuk"
                                      style="display:inline;"
                                      onsubmit="return confirm('Terima siswa ini?');">
                                    <input type="hidden" name="pendaftaran_id" value="<?= (int)$row['id']; ?>">
                                    <input type="hidden" name="aksi" value="terima">
                                    <button type="submit" class="btn-terima">
                                        <i class="bi bi-check-lg" aria-hidden="true"></i> Terima
                                    </button>
                                </form>
                                <form method="POST" action="dashboard_pembina.php?page=pendaftaran_masuk"
                                      style="display:inline;"
                                      onsubmit="return confirm('Tolak siswa ini?');">
                                    <input type="hidden" name="pendaftaran_id" value="<?= (int)$row['id']; ?>">
                                    <input type="hidden" name="aksi" value="tolak">
                                    <button type="submit" class="btn-tolak">
                                        <i class="bi bi-x-lg" aria-hidden="true"></i> Tolak
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted small">Sudah diproses</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-inbox" aria-hidden="true"></i>
            <strong>Belum Ada Pendaftaran</strong>
            <span>Belum ada siswa yang mendaftar ke ekskul Anda.</span>
        </div>
        <?php endif; ?>
    </section>


<?php else: ?>

    <!-- =================================================
         HALAMAN: DASHBOARD
    ================================================== -->
    <section class="welcome">
        <div class="welcome-content">
            <div class="welcome-badge">
                <i class="bi bi-person-workspace" aria-hidden="true"></i>
                Portal Pembina
            </div>
            <h2>Selamat Datang, <?= htmlspecialchars($namaPembina); ?>!</h2>
            <p>
                Kelola pendaftaran siswa dan absensi ekskul yang Anda ampu
                dari satu dashboard.
            </p>
        </div>
    </section>


    <!-- STATISTIK DASHBOARD -->
    <section class="row g-3 mb-4" aria-label="Statistik">

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-blue" aria-hidden="true"><i class="bi bi-stars"></i></div>
                    <div class="stat-arrow" aria-hidden="true"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalEkskulPembina; ?></div>
                <div class="stat-label">Ekskul yang Diampu</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-orange" aria-hidden="true"><i class="bi bi-hourglass-split"></i></div>
                    <div class="stat-arrow" aria-hidden="true"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalMenungguDash; ?></div>
                <div class="stat-label">Menunggu Review</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-green" aria-hidden="true"><i class="bi bi-people-fill"></i></div>
                    <div class="stat-arrow" aria-hidden="true"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalSiswaDiterima; ?></div>
                <div class="stat-label">Siswa Diterima</div>
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

    </section>


    <!-- INFO EKSKUL YANG DIAMPU -->
    <section class="section-card">
        <div class="section-header">
            <div>
                <h2><i class="bi bi-stars me-1" aria-hidden="true"></i> Ekskul yang Anda Ampu</h2>
                <small>Daftar ekstrakurikuler yang Anda bina</small>
            </div>
            <a href="dashboard_pembina.php?page=pendaftaran_masuk" class="btn-primary-custom">
                <i class="bi bi-inbox-fill" aria-hidden="true"></i>
                Kelola Pendaftaran
            </a>
        </div>

        <?php if (count($listEkskulPembina) > 0): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th width="60">No</th>
                        <th>Nama Ekstrakurikuler</th>
                        <th width="150" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no = 1; foreach ($listEkskulPembina as $e): ?>
                    <tr>
                        <td><div class="number-box"><?= $no++; ?></div></td>
                        <td>
                            <div class="ekskul-box">
                                <div class="ekskul-icon" aria-hidden="true"><i class="bi bi-stars"></i></div>
                                <div class="ekskul-name"><?= htmlspecialchars($e['nama_ekskul']); ?></div>
                            </div>
                        </td>
                        <td class="text-center">
                            <a href="dashboard_pembina.php?page=pendaftaran_masuk"
                               class="btn-view">
                                <i class="bi bi-inbox-fill me-1" aria-hidden="true"></i>
                                Lihat Pendaftar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            <strong>Belum Ditugaskan ke Ekskul</strong>
            <span>Anda belum ditugaskan sebagai pembina ekskul. Hubungi admin.</span>
        </div>
        <?php endif; ?>
    </section>


    <!-- INFO CARD -->
    <div class="row g-3 mt-4">
        <div class="col-md-6">
            <div class="section-card h-100">
                <div class="p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="stat-icon icon-blue" aria-hidden="true">
                            <i class="bi bi-lightbulb-fill"></i>
                        </div>
                        <div>
                            <h2 class="h6 mb-1 fw-bold">Tugas Anda</h2>
                            <small class="text-muted">Fitur yang bisa Anda akses</small>
                        </div>
                    </div>
                    <ul class="mb-0" style="font-size:13px; color:#475569; padding-left:18px;">
                        <li>Review dan setujui pendaftaran siswa</li>
                        <li>Input absensi kegiatan ekskul</li>
                        <li>Lihat jadwal kegiatan</li>
                        <li>Lihat data siswa yang diterima</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="section-card h-100">
                <div class="p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="stat-icon icon-green" aria-hidden="true">
                            <i class="bi bi-info-circle-fill"></i>
                        </div>
                        <div>
                            <h2 class="h6 mb-1 fw-bold">Tips</h2>
                            <small class="text-muted">Saran penggunaan</small>
                        </div>
                    </div>
                    <ul class="mb-0" style="font-size:13px; color:#475569; padding-left:18px;">
                        <li>Review pendaftar secara rutin</li>
                        <li>Terima siswa sesuai kuota ekskul</li>
                        <li>Catat absensi setiap kegiatan</li>
                        <li>Update jadwal jika ada perubahan</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>