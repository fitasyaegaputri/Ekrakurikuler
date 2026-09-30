<?php
/* =========================================================
   JADWAL - HALAMAN ADMIN
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
   TAMBAH JADWAL
========================================================= */
if (isset($_POST['tambah_jadwal'])) {

    $ekskul_id   = (int)($_POST['ekskul_id'] ?? 0);
    $hari        = trim($_POST['hari'] ?? "");
    $jam_mulai   = trim($_POST['jam_mulai'] ?? "");
    $jam_selesai = trim($_POST['jam_selesai'] ?? "");
    $lokasi      = trim($_POST['lokasi'] ?? "");

    if ($ekskul_id <= 0 || $hari === "" || $jam_mulai === "" || $jam_selesai === "" || $lokasi === "") {
        $pesan     = "Semua data jadwal wajib diisi.";
        $tipePesan = "danger";

    } elseif ($jam_selesai <= $jam_mulai) {
        $pesan     = "Jam selesai harus lebih besar dari jam mulai.";
        $tipePesan = "danger";

    } else {

        $stmt = mysqli_prepare($conn,
            "INSERT INTO jadwal (ekskul_id, hari, jam_mulai, jam_selesai, lokasi)
             VALUES (?, ?, ?, ?, ?)");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "issss",
                $ekskul_id, $hari, $jam_mulai, $jam_selesai, $lokasi);

            if (mysqli_stmt_execute($stmt)) {
                $pesan     = "Jadwal berhasil ditambahkan.";
                $tipePesan = "success";
            } else {
                $pesan     = "Gagal menambahkan jadwal: " . mysqli_stmt_error($stmt);
                $tipePesan = "danger";
            }
            mysqli_stmt_close($stmt);
        } else {
            $pesan     = "Query tambah jadwal gagal: " . mysqli_error($conn);
            $tipePesan = "danger";
        }
    }
}


/* =========================================================
   EDIT JADWAL
========================================================= */
if (isset($_POST['edit_jadwal'])) {

    $id          = (int)($_POST['id'] ?? 0);
    $ekskul_id   = (int)($_POST['ekskul_id'] ?? 0);
    $hari        = trim($_POST['hari'] ?? "");
    $jam_mulai   = trim($_POST['jam_mulai'] ?? "");
    $jam_selesai = trim($_POST['jam_selesai'] ?? "");
    $lokasi      = trim($_POST['lokasi'] ?? "");

    if ($id <= 0 || $ekskul_id <= 0 || $hari === "" || $jam_mulai === "" || $jam_selesai === "" || $lokasi === "") {
        $pesan     = "Data jadwal belum lengkap.";
        $tipePesan = "danger";

    } elseif ($jam_selesai <= $jam_mulai) {
        $pesan     = "Jam selesai harus lebih besar dari jam mulai.";
        $tipePesan = "danger";

    } else {

        $stmt = mysqli_prepare($conn,
            "UPDATE jadwal
             SET ekskul_id = ?, hari = ?, jam_mulai = ?, jam_selesai = ?, lokasi = ?
             WHERE id = ?");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "issssi",
                $ekskul_id, $hari, $jam_mulai, $jam_selesai, $lokasi, $id);

            if (mysqli_stmt_execute($stmt)) {
                $pesan     = "Jadwal berhasil diperbarui.";
                $tipePesan = "success";
            } else {
                $pesan     = "Gagal memperbarui jadwal: " . mysqli_stmt_error($stmt);
                $tipePesan = "danger";
            }
            mysqli_stmt_close($stmt);
        } else {
            $pesan     = "Query edit jadwal gagal: " . mysqli_error($conn);
            $tipePesan = "danger";
        }
    }
}


/* =========================================================
   HAPUS JADWAL
========================================================= */
if (isset($_GET['hapus'])) {

    $id = (int)($_GET['hapus'] ?? 0);

    if ($id > 0) {

        $stmt = mysqli_prepare($conn, "DELETE FROM jadwal WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            $pesan     = "Jadwal berhasil dihapus.";
            $tipePesan = "success";
        } else {
            $pesan     = "Jadwal gagal dihapus: " . mysqli_stmt_error($stmt);
            $tipePesan = "danger";
        }
        mysqli_stmt_close($stmt);
    }
}


/* =========================================================
   PENCARIAN & FILTER
========================================================= */
$cari       = trim($_GET['cari'] ?? "");
$filterHari = trim($_GET['hari'] ?? "");

$where  = [];
$params = [];
$types  = "";

if ($cari !== "") {
    $where[] = "(e.nama_ekskul LIKE ? OR j.hari LIKE ? OR j.lokasi LIKE ? OR j.jam_mulai LIKE ? OR j.jam_selesai LIKE ?)";
    $kw = "%" . $cari . "%";
    for ($i = 0; $i < 5; $i++) {
        $params[] = $kw;
        $types .= "s";
    }
}

if ($filterHari !== "") {
    $where[]  = "j.hari = ?";
    $params[] = $filterHari;
    $types   .= "s";
}

$whereSQL = "";
if (count($where) > 0) {
    $whereSQL = "WHERE " . implode(" AND ", $where);
}


/* =========================================================
   DATA EKSKUL
========================================================= */
$listEkskul = [];
$qEkskul = mysqli_query($conn,
    "SELECT id, nama_ekskul FROM ekstrakurikuler ORDER BY nama_ekskul ASC");

if ($qEkskul) {
    while ($r = mysqli_fetch_assoc($qEkskul)) {
        $listEkskul[] = $r;
    }
}


/* =========================================================
   DATA JADWAL
========================================================= */
$listJadwal = [];

$orderSQL = "ORDER BY
    FIELD(j.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'),
    j.jam_mulai ASC";

if (count($params) > 0) {

    $stmtJ = mysqli_prepare($conn,
        "SELECT
            j.id, j.ekskul_id, j.hari, j.jam_mulai, j.jam_selesai, j.lokasi,
            e.nama_ekskul
         FROM jadwal j
         LEFT JOIN ekstrakurikuler e ON j.ekskul_id = e.id
         $whereSQL
         $orderSQL");

    if ($stmtJ) {
        mysqli_stmt_bind_param($stmtJ, $types, ...$params);
        mysqli_stmt_execute($stmtJ);
        $resultJ = mysqli_stmt_get_result($stmtJ);

        if ($resultJ) {
            while ($r = mysqli_fetch_assoc($resultJ)) {
                $listJadwal[] = $r;
            }
        }
        mysqli_stmt_close($stmtJ);
    }

} else {

    $resultJ = mysqli_query($conn,
        "SELECT
            j.id, j.ekskul_id, j.hari, j.jam_mulai, j.jam_selesai, j.lokasi,
            e.nama_ekskul
         FROM jadwal j
         LEFT JOIN ekstrakurikuler e ON j.ekskul_id = e.id
         $orderSQL");

    if ($resultJ) {
        while ($r = mysqli_fetch_assoc($resultJ)) {
            $listJadwal[] = $r;
        }
    }
}


/* =========================================================
   DATA EDIT
========================================================= */
$dataEdit = null;

if (isset($_GET['edit'])) {

    $idEdit = (int)$_GET['edit'];

    if ($idEdit > 0) {
        $stmtE = mysqli_prepare($conn,
            "SELECT id, ekskul_id, hari, jam_mulai, jam_selesai, lokasi
             FROM jadwal WHERE id = ?");
        mysqli_stmt_bind_param($stmtE, "i", $idEdit);
        mysqli_stmt_execute($stmtE);
        $resE = mysqli_stmt_get_result($stmtE);

        if ($resE) {
            $dataEdit = mysqli_fetch_assoc($resE);
        }
        mysqli_stmt_close($stmtE);
    }
}


/* =========================================================
   TOTAL
========================================================= */
$totalJadwal = 0;
$qTotal = mysqli_query($conn, "SELECT COUNT(*) AS total FROM jadwal");
if ($qTotal) {
    $dT = mysqli_fetch_assoc($qTotal);
    $totalJadwal = (int)($dT['total'] ?? 0);
}

$totalEkskul = count($listEkskul);
$daftarHari  = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
?>


<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Jadwal - Sistem Informasi Ekstrakurikuler</title>

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
.icon-teal   { background:#ccfbf1; color:#0f766e; }
.icon-orange { background:#ffedd5; color:#ea580c; }

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
    color: #94a3b8;
}

.search-input input {
    padding-left: 39px;
    min-height: 43px;
    border: 1px solid #e2e8f0;
    border-radius: 11px;
}

.search-input input:focus {
    border-color: #38bdf8;
    box-shadow: 0 0 0 3px rgba(56,189,248,.12);
}

.search-area .form-select {
    min-height: 43px;
    border-radius: 11px;
    border: 1px solid #e2e8f0;
}

.btn-reset {
    height: 43px;
    border-radius: 11px;
    border: 1px solid #e2e8f0;
    background: white;
    color: #64748b;
    padding: 0 15px;
    font-size: 13px;
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

.hari-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    border-radius: 20px;
    background: #f0fdfa;
    color: #0f766e;
    font-size: 11px;
    font-weight: 600;
}

.jam-box {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #475569;
    font-weight: 600;
    font-size: 12px;
}

.jam-box i { color: #0284c7; }

.lokasi-box {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #475569;
    font-size: 12px;
}

.lokasi-box i { color: #dc2626; }


/* =====================================================
   ACTION BUTTON
===================================================== */

.action-buttons { display: flex; align-items: center; gap: 6px; }

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

.btn-edit { background: #e0f2fe; color: #0284c7; }
.btn-edit:hover { background: #0284c7; color: white; }

.btn-delete { background: #fee2e2; color: #dc2626; }
.btn-delete:hover { background: #dc2626; color: white; }


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
    transition: .2s;
}

.btn-save:hover { color: white; opacity: .92; }

.btn-cancel {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 9px 17px;
    background: white;
    color: #64748b;
    font-size: 13px;
    transition: .2s;
}

.btn-cancel:hover { background: #f8fafc; }


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
    <a href="jadwal.php" class="active"><i class="bi bi-calendar-event-fill"></i><span>Jadwal</span></a>
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
            <h5>Jadwal</h5>
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
                <i class="bi bi-calendar-event-fill"></i>
                Manajemen Jadwal
            </div>
            <h2>Jadwal Ekstrakurikuler</h2>
            <p>
                Kelola jadwal kegiatan ekstrakurikuler
                dengan mudah dan terorganisir.
            </p>
        </div>
    </div>


    <!-- PESAN -->
    <?php if ($pesan !== ""): ?>
    <div class="alert alert-<?= htmlspecialchars($tipePesan); ?> alert-dismissible fade show" role="alert">
        <i class="bi <?= $tipePesan === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
        <?= htmlspecialchars($pesan); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>


    <!-- STATISTIK -->
    <div class="row g-3 mb-4">

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-blue"><i class="bi bi-calendar-event-fill"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalJadwal; ?></div>
                <div class="stat-label">Total Jadwal</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-orange"><i class="bi bi-stars"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalEkskul; ?></div>
                <div class="stat-label">Total Ekstrakurikuler</div>
            </div>
        </div>

    </div>


    <!-- DATA JADWAL -->
    <div class="section-card">

        <div class="section-header">
            <div>
                <h5><i class="bi bi-calendar-week me-1"></i> Data Jadwal</h5>
                <small>Menampilkan <?= count($listJadwal); ?> data jadwal</small>
            </div>

            <button type="button" class="btn-add"
                    data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-plus-lg me-1"></i>
                Tambah Jadwal
            </button>
        </div>


        <!-- SEARCH & FILTER -->
        <div class="search-area">
            <form method="GET" action="jadwal.php">
                <div class="row g-2">

                    <div class="col-lg-7">
                        <div class="search-input">
                            <i class="bi bi-search"></i>
                            <input type="text" name="cari" class="form-control"
                                   placeholder="Cari ekstrakurikuler, hari, lokasi..."
                                   value="<?= htmlspecialchars($cari); ?>">
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <select name="hari" class="form-select">
                            <option value="">Semua Hari</option>
                            <?php foreach ($daftarHari as $h): ?>
                                <option value="<?= $h; ?>"
                                    <?= $filterHari === $h ? "selected" : ""; ?>>
                                    <?= $h; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-lg-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1"
                                style="height:43px;border-radius:11px;">
                            <i class="bi bi-search me-1"></i> Cari
                        </button>

                        <?php if ($cari !== "" || $filterHari !== ""): ?>
                            <a href="jadwal.php" class="btn-reset d-flex align-items-center" title="Reset">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                        <?php endif; ?>
                    </div>

                </div>
            </form>
        </div>


        <!-- TABLE -->
        <div class="table-responsive">

            <table class="table">

                <thead>
                    <tr>
                        <th style="width:60px;">No</th>
                        <th>Ekstrakurikuler</th>
                        <th>Hari</th>
                        <th>Waktu</th>
                        <th>Lokasi</th>
                        <th style="width:120px;" class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($listJadwal) > 0): ?>

                    <?php $no = 1; foreach ($listJadwal as $j): ?>

                    <tr>

                        <td>
                            <div class="number-box"><?= $no++; ?></div>
                        </td>

                        <td>
                            <div class="ekskul-box">
                                <div class="ekskul-icon"><i class="bi bi-stars"></i></div>
                                <div>
                                    <div class="ekskul-name">
                                        <?= htmlspecialchars($j['nama_ekskul'] ?? '-'); ?>
                                    </div>
                                    <small class="text-muted">Kegiatan ekstrakurikuler</small>
                                </div>
                            </div>
                        </td>

                        <td>
                            <span class="hari-badge">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?= htmlspecialchars($j['hari'] ?? '-'); ?>
                            </span>
                        </td>

                        <td>
                            <div class="jam-box">
                                <i class="bi bi-clock"></i>
                                <span>
                                    <?= htmlspecialchars(substr($j['jam_mulai'] ?? '', 0, 5)); ?>
                                    -
                                    <?= htmlspecialchars(substr($j['jam_selesai'] ?? '', 0, 5)); ?>
                                </span>
                            </div>
                        </td>

                        <td>
                            <div class="lokasi-box">
                                <i class="bi bi-geo-alt-fill"></i>
                                <?= htmlspecialchars($j['lokasi'] ?? '-'); ?>
                            </div>
                        </td>

                        <td class="text-center">
                            <div class="action-buttons justify-content-center">

                                <a href="jadwal.php?edit=<?= (int)$j['id']; ?>"
                                   class="btn-action btn-edit" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <a href="jadwal.php?hapus=<?= (int)$j['id']; ?>"
                                   class="btn-action btn-delete" title="Hapus"
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?');">
                                    <i class="bi bi-trash3-fill"></i>
                                </a>

                            </div>
                        </td>

                    </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="bi bi-calendar-x"></i>
                                <strong>
                                    <?= ($cari !== "" || $filterHari !== "")
                                        ? "Data jadwal tidak ditemukan"
                                        : "Belum ada data jadwal"; ?>
                                </strong>
                                <span>
                                    <?= ($cari !== "" || $filterHari !== "")
                                        ? "Coba gunakan kata kunci atau filter lain."
                                        : "Silakan tambahkan jadwal terlebih dahulu."; ?>
                                </span>
                            </div>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


</div>


<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>
                    Tambah Jadwal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST">

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Ekstrakurikuler</label>
                            <select name="ekskul_id" class="form-select" required>
                                <option value="">-- Pilih Ekstrakurikuler --</option>
                                <?php foreach ($listEkskul as $e): ?>
                                    <option value="<?= (int)$e['id']; ?>">
                                        <?= htmlspecialchars($e['nama_ekskul']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Hari</label>
                            <select name="hari" class="form-select" required>
                                <option value="">Pilih Hari</option>
                                <?php foreach ($daftarHari as $h): ?>
                                    <option value="<?= $h; ?>"><?= $h; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Jam Mulai</label>
                            <input type="time" name="jam_mulai" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Jam Selesai</label>
                            <input type="time" name="jam_selesai" class="form-control" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Lokasi</label>
                            <input type="text" name="lokasi" class="form-control"
                                   placeholder="Contoh: Lapangan Sekolah" required>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_jadwal" class="btn-save">
                        <i class="bi bi-save me-1"></i> Simpan
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>


<!-- MODAL EDIT -->
<?php if ($dataEdit): ?>

<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square me-2"></i>
                    Edit Jadwal
                </h5>
                <a href="jadwal.php" class="btn-close"></a>
            </div>

            <form method="POST">

                <div class="modal-body">

                    <input type="hidden" name="id" value="<?= (int)$dataEdit['id']; ?>">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Ekstrakurikuler</label>
                            <select name="ekskul_id" class="form-select" required>
                                <option value="">-- Pilih Ekstrakurikuler --</option>
                                <?php foreach ($listEkskul as $e): ?>
                                    <option value="<?= (int)$e['id']; ?>"
                                        <?= ((int)$dataEdit['ekskul_id'] === (int)$e['id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($e['nama_ekskul']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Hari</label>
                            <select name="hari" class="form-select" required>
                                <option value="">Pilih Hari</option>
                                <?php foreach ($daftarHari as $h): ?>
                                    <option value="<?= $h; ?>"
                                        <?= ($dataEdit['hari'] === $h) ? 'selected' : ''; ?>>
                                        <?= $h; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Jam Mulai</label>
                            <input type="time" name="jam_mulai" class="form-control"
                                   value="<?= htmlspecialchars($dataEdit['jam_mulai'] ?? ''); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Jam Selesai</label>
                            <input type="time" name="jam_selesai" class="form-control"
                                   value="<?= htmlspecialchars($dataEdit['jam_selesai'] ?? ''); ?>" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Lokasi</label>
                            <input type="text" name="lokasi" class="form-control"
                                   value="<?= htmlspecialchars($dataEdit['lokasi'] ?? ''); ?>" required>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <a href="jadwal.php" class="btn-cancel">Batal</a>
                    <button type="submit" name="edit_jadwal" class="btn-save">
                        <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEdit = new bootstrap.Modal(document.getElementById('modalEdit'));
    modalEdit.show();
});
</script>

<?php endif; ?>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>