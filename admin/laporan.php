<?php
/* =========================================================
   LAPORAN - HALAMAN ADMIN
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
   FILTER
========================================================= */
$filterEkskul  = (int)($_GET['ekskul'] ?? 0);
$filterDari    = trim($_GET['dari'] ?? "");
$filterSampai  = trim($_GET['sampai'] ?? "");

if ($filterDari !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDari)) {
    $filterDari = "";
}
if ($filterSampai !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterSampai)) {
    $filterSampai = "";
}


/* =========================================================
   DATA EKSKUL
========================================================= */
$listEkskulFilter = [];
$qEF = mysqli_query($conn,
    "SELECT id, nama_ekskul FROM ekstrakurikuler ORDER BY nama_ekskul ASC");

if ($qEF) {
    while ($r = mysqli_fetch_assoc($qEF)) {
        $listEkskulFilter[] = $r;
    }
}


/* =========================================================
   QUERY LAPORAN
========================================================= */
$where  = [];
$params = [];
$types  = "";

if ($filterEkskul > 0) {
    $where[]  = "e.id = ?";
    $params[] = $filterEkskul;
    $types   .= "i";
}

$subWhere  = "";
$subParams = [];
$subTypes  = "";

if ($filterDari !== "") {
    $subWhere   .= " AND a.tanggal >= ?";
    $subParams[] = $filterDari;
    $subTypes   .= "s";
}

if ($filterSampai !== "") {
    $subWhere   .= " AND a.tanggal <= ?";
    $subParams[] = $filterSampai;
    $subTypes   .= "s";
}

$whereSQL = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$queryLaporan = "
    SELECT
        e.id,
        e.nama_ekskul,
        (SELECT COUNT(DISTINCT p.siswa_id)
         FROM pendaftaran p
         WHERE p.ekskul_id = e.id
           AND p.status = 'diterima') AS jumlah_anggota,
        (SELECT COUNT(*)
         FROM absensi a
         WHERE a.ekskul_id = e.id
           AND LOWER(a.status_hadir) = 'hadir'
           $subWhere) AS hadir,
        (SELECT COUNT(*)
         FROM absensi a
         WHERE a.ekskul_id = e.id
           AND LOWER(a.status_hadir) = 'izin'
           $subWhere) AS izin,
        (SELECT COUNT(*)
         FROM absensi a
         WHERE a.ekskul_id = e.id
           AND LOWER(a.status_hadir) = 'sakit'
           $subWhere) AS sakit,
        (SELECT COUNT(*)
         FROM absensi a
         WHERE a.ekskul_id = e.id
           AND LOWER(a.status_hadir) = 'alfa'
           $subWhere) AS alfa
    FROM ekstrakurikuler e
    $whereSQL
    ORDER BY e.nama_ekskul ASC
";

$laporan      = [];
$totalEkskul  = 0;
$totalAnggota = 0;
$totalHadir   = 0;
$totalIzin    = 0;
$totalSakit   = 0;
$totalAlfa    = 0;

if (count($params) > 0 || count($subParams) > 0) {

    $allParams = array_merge($params, $subParams, $subParams, $subParams, $subParams);
    $allTypes  = $types;
    for ($i = 0; $i < 4; $i++) {
        $allTypes .= $subTypes;
    }

    $stmtLap = mysqli_prepare($conn, $queryLaporan);

    if ($stmtLap) {
        if (count($allParams) > 0) {
            mysqli_stmt_bind_param($stmtLap, $allTypes, ...$allParams);
        }
        mysqli_stmt_execute($stmtLap);
        $resLap = mysqli_stmt_get_result($stmtLap);

        if ($resLap) {
            while ($r = mysqli_fetch_assoc($resLap)) {
                $laporan[] = $r;
                $totalEkskul++;
                $totalAnggota += (int)$r['jumlah_anggota'];
                $totalHadir   += (int)$r['hadir'];
                $totalIzin    += (int)$r['izin'];
                $totalSakit   += (int)$r['sakit'];
                $totalAlfa    += (int)$r['alfa'];
            }
        }
        mysqli_stmt_close($stmtLap);
    }

} else {

    $resLap = mysqli_query($conn, $queryLaporan);

    if ($resLap) {
        while ($r = mysqli_fetch_assoc($resLap)) {
            $laporan[] = $r;
            $totalEkskul++;
            $totalAnggota += (int)$r['jumlah_anggota'];
            $totalHadir   += (int)$r['hadir'];
            $totalIzin    += (int)$r['izin'];
            $totalSakit   += (int)$r['sakit'];
            $totalAlfa    += (int)$r['alfa'];
        }
    }
}

$totalSemuaAbsensi = $totalHadir + $totalIzin + $totalSakit + $totalAlfa;
$persenHadir = $totalSemuaAbsensi > 0 ? round(($totalHadir / $totalSemuaAbsensi) * 100, 1) : 0;


/* =========================================================
   EXPORT CSV
========================================================= */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_ekskul_' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($out, ['No', 'Ekstrakurikuler', 'Jumlah Anggota', 'Hadir', 'Izin', 'Sakit', 'Alfa', 'Persentase Hadir (%)']);

    $no = 1;
    foreach ($laporan as $row) {
        $totalRow = (int)$row['hadir'] + (int)$row['izin'] + (int)$row['sakit'] + (int)$row['alfa'];
        $persen   = $totalRow > 0 ? round(((int)$row['hadir'] / $totalRow) * 100, 1) : 0;
        fputcsv($out, [$no++, $row['nama_ekskul'], $row['jumlah_anggota'], $row['hadir'], $row['izin'], $row['sakit'], $row['alfa'], $persen]);
    }

    fputcsv($out, ['', 'TOTAL', $totalAnggota, $totalHadir, $totalIzin, $totalSakit, $totalAlfa, $persenHadir]);
    fclose($out);
    exit();
}
?>


<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Laporan - Sistem Informasi Ekstrakurikuler</title>

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
.icon-orange { background:#ffedd5; color:#ea580c; }
.icon-teal   { background:#ccfbf1; color:#0f766e; }
.icon-yellow { background:#fef3c7; color:#b45309; }
.icon-red    { background:#fee2e2; color:#dc2626; }
.icon-indigo { background:#e0e7ff; color:#4338ca; }

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
    margin-bottom: 22px;
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
   FILTER
===================================================== */

.filter-box {
    padding: 20px;
    background: #f8fafc;
}

.form-label { font-size: 12px; font-weight: 600; color: #475569; }

.form-select, .form-control {
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    padding: 10px 12px;
    font-size: 13px;
    min-height: 43px;
}

.form-select:focus, .form-control:focus {
    border-color: #38bdf8;
    box-shadow: 0 0 0 3px rgba(14,165,233,.10);
}


/* =====================================================
   BUTTON
===================================================== */

.btn-primary-custom {
    border: none;
    border-radius: 10px;
    padding: 10px 17px;
    background: linear-gradient(135deg, #0284c7, #0f766e);
    color: white;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 5px 15px rgba(2,132,199,.18);
    transition: .2s;
}

.btn-primary-custom:hover {
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(2,132,199,.25);
}

.btn-reset {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 17px;
    background: white;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: .2s;
}

.btn-reset:hover { background: #f1f5f9; color: #475569; }

.btn-export {
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    padding: 10px 15px;
    background: #dcfce7;
    color: #15803d;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: .2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-export:hover { background: #bbf7d0; color: #166534; }

.btn-print {
    border: 1px solid #bae6fd;
    border-radius: 10px;
    padding: 10px 15px;
    background: #e0f2fe;
    color: #0284c7;
    font-size: 13px;
    font-weight: 600;
    transition: .2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-print:hover { background: #bae6fd; color: #0369a1; }


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

.table tfoot td {
    padding: 14px 20px;
    background: #f8fafc;
    font-weight: 700;
    color: #172033;
    border-top: 2px solid #e2e8f0;
}

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

.ekskul-box { display: flex; align-items: center; gap: 12px; }

.ekskul-icon {
    width: 40px; height: 40px;
    flex-shrink: 0;
    border-radius: 11px;
    background: #e0f2fe;
    color: #0284c7;
    display: flex;
    align-items: center; justify-content: center;
    font-size: 17px;
}

.ekskul-name { font-weight: 600; font-size: 13px; }


/* =====================================================
   BADGE
===================================================== */

.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 38px;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.badge-anggota { background:#e0f2fe; color:#0369a1; }
.status-hadir  { background:#dcfce7; color:#15803d; }
.status-izin   { background:#fef3c7; color:#b45309; }
.status-sakit  { background:#dbeafe; color:#1d4ed8; }
.status-alfa   { background:#fee2e2; color:#dc2626; }

.progress-mini {
    height: 6px;
    border-radius: 10px;
    background: #f1f5f9;
    overflow: hidden;
    margin-top: 6px;
    width: 90px;
}

.progress-mini-bar {
    height: 100%;
    border-radius: 10px;
    background: linear-gradient(90deg, #22c55e, #16a34a);
}

.persen-text {
    font-size: 11px;
    font-weight: 700;
    color: #15803d;
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
   KETERANGAN
===================================================== */

.ket-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: #f8fafc;
    border-radius: 10px;
    height: 100%;
}

.ket-item small { color: #64748b; font-size: 12px; }


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


/* =====================================================
   PRINT
===================================================== */

@media print {

    .sidebar,
    .topbar,
    .welcome,
    .filter-box,
    .btn-print,
    .btn-export,
    .btn-reset,
    .btn-primary-custom {
        display: none !important;
    }

    .content { margin: 0; padding: 0; }
    body { background: white; }

    .section-card {
        box-shadow: none;
        border: 1px solid #ddd;
    }
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
    <a href="ekstrakurikuler.php"><i class="bi bi-stars"></i><span>Ekstrakurikuler</span></a>
    <a href="data_siswa.php"><i class="bi bi-people-fill"></i><span>Data Siswa</span></a>
    <a href="pendaftaran.php"><i class="bi bi-person-check-fill"></i><span>Pendaftaran</span></a>
    <a href="jadwal.php"><i class="bi bi-calendar-event-fill"></i><span>Jadwal</span></a>
    <a href="absensi.php"><i class="bi bi-calendar-check-fill"></i><span>Absensi</span></a>
    <a href="laporan.php" class="active"><i class="bi bi-bar-chart-fill"></i><span>Laporan</span></a>
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
            <h5>Laporan Ekstrakurikuler</h5>
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
                <i class="bi bi-bar-chart-fill"></i>
                Manajemen Laporan
            </div>
            <h2>Laporan Ekstrakurikuler</h2>
            <p>
                Ringkasan jumlah anggota dan rekapitulasi
                kehadiran siswa setiap ekstrakurikuler.
            </p>
        </div>
    </div>


    <!-- STATISTIK -->
    <div class="row g-3 mb-4">

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-blue"><i class="bi bi-stars"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalEkskul; ?></div>
                <div class="stat-label">Total Ekstrakurikuler</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-indigo"><i class="bi bi-people-fill"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalAnggota; ?></div>
                <div class="stat-label">Total Anggota</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-green"><i class="bi bi-person-check-fill"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalHadir; ?></div>
                <div class="stat-label">Total Hadir</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-yellow"><i class="bi bi-info-circle-fill"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalIzin + $totalSakit; ?></div>
                <div class="stat-label">Izin + Sakit</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-red"><i class="bi bi-person-x-fill"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalAlfa; ?></div>
                <div class="stat-label">Total Alfa</div>
            </div>
        </div>

    </div>


    <!-- FILTER -->
    <div class="section-card">

        <div class="section-header">
            <div>
                <h5><i class="bi bi-funnel-fill me-1"></i> Filter Laporan</h5>
                <small>Pilih ekstrakurikuler dan periode untuk melihat laporan tertentu</small>
            </div>

            <div class="d-flex gap-2 flex-wrap">

                <a href="laporan.php?export=csv<?= $filterEkskul > 0 ? '&ekskul=' . $filterEkskul : ''; ?><?= $filterDari !== '' ? '&dari=' . urlencode($filterDari) : ''; ?><?= $filterSampai !== '' ? '&sampai=' . urlencode($filterSampai) : ''; ?>"
                   class="btn-export">
                    <i class="bi bi-file-earmark-excel-fill"></i>
                    Export CSV
                </a>

                <button type="button" class="btn-print" onclick="window.print()">
                    <i class="bi bi-printer-fill"></i>
                    Cetak
                </button>

            </div>
        </div>


        <div class="filter-box">
            <form method="GET" action="laporan.php">
                <div class="row g-3 align-items-end">

                    <div class="col-lg-4">
                        <label class="form-label">Ekstrakurikuler</label>
                        <select name="ekskul" class="form-select">
                            <option value="">Semua Ekstrakurikuler</option>
                            <?php foreach ($listEkskulFilter as $f): ?>
                                <option value="<?= (int)$f['id']; ?>"
                                    <?= $filterEkskul === (int)$f['id'] ? "selected" : ""; ?>>
                                    <?= htmlspecialchars($f['nama_ekskul']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="dari" class="form-control"
                               value="<?= htmlspecialchars($filterDari); ?>">
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="sampai" class="form-control"
                               value="<?= htmlspecialchars($filterSampai); ?>">
                    </div>

                    <div class="col-lg-2 d-flex gap-2">
                        <button type="submit" class="btn-primary-custom flex-grow-1">
                            <i class="bi bi-search me-1"></i> Tampilkan
                        </button>
                    </div>

                </div>

                <?php if ($filterEkskul > 0 || $filterDari !== "" || $filterSampai !== ""): ?>
                    <div class="mt-3">
                        <a href="laporan.php" class="btn-reset">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

    </div>


    <!-- REKAPITULASI -->
    <div class="section-card">

        <div class="section-header">
            <div>
                <h5><i class="bi bi-table me-1"></i> Rekapitulasi Laporan</h5>
                <small>Data anggota dan kehadiran siswa</small>
            </div>

            <div class="d-flex gap-2">
                <span class="badge rounded-pill text-bg-light" style="padding:8px 14px;font-size:12px;">
                    <i class="bi bi-stars me-1"></i> <?= $totalEkskul; ?> Ekstrakurikuler
                </span>
                <span class="badge rounded-pill text-bg-light" style="padding:8px 14px;font-size:12px;">
                    <i class="bi bi-people-fill me-1"></i> <?= $totalAnggota; ?> Anggota
                </span>
            </div>
        </div>


        <div class="table-responsive">

            <table class="table">

                <thead>
                    <tr>
                        <th style="width:60px;">No</th>
                        <th>Nama Ekstrakurikuler</th>
                        <th class="text-center">Jumlah Anggota</th>
                        <th class="text-center">Hadir</th>
                        <th class="text-center">Izin</th>
                        <th class="text-center">Sakit</th>
                        <th class="text-center">Alfa</th>
                        <th class="text-center">% Hadir</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($laporan) > 0): ?>

                    <?php $no = 1; foreach ($laporan as $row): ?>

                        <?php
                        $subTotal = (int)$row['hadir'] + (int)$row['izin'] + (int)$row['sakit'] + (int)$row['alfa'];
                        $persen   = $subTotal > 0 ? round(((int)$row['hadir'] / $subTotal) * 100, 1) : 0;

                        $progressColor = "linear-gradient(90deg, #22c55e, #16a34a)";
                        if ($persen < 50) {
                            $progressColor = "linear-gradient(90deg, #ef4444, #dc2626)";
                        } elseif ($persen < 75) {
                            $progressColor = "linear-gradient(90deg, #f59e0b, #d97706)";
                        }
                        ?>

                        <tr>

                            <td><div class="number-box"><?= $no++; ?></div></td>

                            <td>
                                <div class="ekskul-box">
                                    <div class="ekskul-icon"><i class="bi bi-stars"></i></div>
                                    <div class="ekskul-name"><?= htmlspecialchars($row['nama_ekskul']); ?></div>
                                </div>
                            </td>

                            <td class="text-center">
                                <span class="status-badge badge-anggota"><?= (int)$row['jumlah_anggota']; ?></span>
                            </td>

                            <td class="text-center">
                                <span class="status-badge status-hadir"><?= (int)$row['hadir']; ?></span>
                            </td>

                            <td class="text-center">
                                <span class="status-badge status-izin"><?= (int)$row['izin']; ?></span>
                            </td>

                            <td class="text-center">
                                <span class="status-badge status-sakit"><?= (int)$row['sakit']; ?></span>
                            </td>

                            <td class="text-center">
                                <span class="status-badge status-alfa"><?= (int)$row['alfa']; ?></span>
                            </td>

                            <td class="text-center">
                                <div class="d-flex flex-column align-items-center">
                                    <span class="persen-text"><?= $persen; ?>%</span>
                                    <div class="progress-mini">
                                        <div class="progress-mini-bar"
                                             style="width:<?= $persen; ?>%;background:<?= $progressColor; ?>;">
                                        </div>
                                    </div>
                                </div>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="bi bi-bar-chart"></i>
                                <strong>Belum Ada Data Laporan</strong>
                                <span>Data laporan ekstrakurikuler belum tersedia.</span>
                            </div>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

                <?php if (count($laporan) > 0): ?>

                <tfoot>
                    <tr>
                        <td colspan="2">
                            <i class="bi bi-calculator me-1"></i> TOTAL KESELURUHAN
                        </td>
                        <td class="text-center"><?= $totalAnggota; ?></td>
                        <td class="text-center"><?= $totalHadir; ?></td>
                        <td class="text-center"><?= $totalIzin; ?></td>
                        <td class="text-center"><?= $totalSakit; ?></td>
                        <td class="text-center"><?= $totalAlfa; ?></td>
                        <td class="text-center">
                            <span class="persen-text"><?= $persenHadir; ?>%</span>
                        </td>
                    </tr>
                </tfoot>

                <?php endif; ?>

            </table>

        </div>

    </div>


    <!-- KETERANGAN -->
    <div class="section-card">

        <div class="section-header">
            <div>
                <h5><i class="bi bi-info-circle-fill me-1"></i> Keterangan Status</h5>
                <small>Informasi status kehadiran siswa</small>
            </div>
        </div>

        <div class="p-3">
            <div class="row g-3">

                <div class="col-md-3">
                    <div class="ket-item">
                        <span class="status-badge status-hadir">Hadir</span>
                        <small>Siswa hadir kegiatan</small>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="ket-item">
                        <span class="status-badge status-izin">Izin</span>
                        <small>Siswa izin tidak hadir</small>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="ket-item">
                        <span class="status-badge status-sakit">Sakit</span>
                        <small>Siswa sakit</small>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="ket-item">
                        <span class="status-badge status-alfa">Alfa</span>
                        <small>Tidak hadir tanpa keterangan</small>
                    </div>
                </div>

            </div>
        </div>

    </div>


</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>