<?php
/* =========================================================
   ABSENSI - HALAMAN ADMIN
   Konsisten dengan dashboard_admin.php
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

/* CEK ROLE ADMIN */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../login.php");
    exit();
}

/* DATA ADMIN */
$namaAdmin = $_SESSION['nama'] ?? "Administrator";
$inisial   = strtoupper(substr(trim($namaAdmin), 0, 1));

$error   = "";
$success = "";


/* =========================================================
   AJAX - AMBIL SISWA BERDASARKAN EKSTRAKURIKULER
========================================================= */
if (isset($_GET['ambil_siswa'])) {

    header('Content-Type: application/json; charset=utf-8');

    $ekskul_id = (int)($_GET['ambil_siswa'] ?? 0);

    if ($ekskul_id <= 0) {
        echo json_encode([
            "success" => false,
            "message" => "Ekstrakurikuler tidak valid.",
            "data"    => []
        ]);
        exit();
    }

    $stmt = mysqli_prepare($conn,
        "SELECT DISTINCT
            s.id,
            s.nama_siswa,
            s.nis,
            s.kelas
         FROM pendaftaran p
         INNER JOIN siswa s ON p.siswa_id = s.id
         WHERE p.ekskul_id = ?
           AND p.status = 'diterima'
         ORDER BY s.nama_siswa ASC");

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Query gagal dibuat: " . mysqli_error($conn),
            "data"    => []
        ]);
        exit();
    }

    mysqli_stmt_bind_param($stmt, "i", $ekskul_id);

    if (!mysqli_stmt_execute($stmt)) {
        echo json_encode([
            "success" => false,
            "message" => "Query gagal dijalankan: " . mysqli_stmt_error($stmt),
            "data"    => []
        ]);
        mysqli_stmt_close($stmt);
        exit();
    }

    $result = mysqli_stmt_get_result($stmt);
    $data   = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            "id"         => $row["id"],
            "nama_siswa" => $row["nama_siswa"],
            "nis"        => $row["nis"],
            "kelas"      => $row["kelas"]
        ];
    }

    mysqli_stmt_close($stmt);

    echo json_encode([
        "success" => true,
        "message" => "Berhasil",
        "data"    => $data
    ]);
    exit();
}


/* =========================================================
   SIMPAN ABSENSI
========================================================= */
if (isset($_POST['simpan_absensi'])) {

    $siswa_id     = (int)($_POST['siswa_id'] ?? 0);
    $ekskul_id    = (int)($_POST['ekskul_id'] ?? 0);
    $tanggal      = $_POST['tanggal'] ?? "";
    $status_hadir = $_POST['status_hadir'] ?? "";

    $statusValid = ["hadir", "izin", "sakit", "alfa"];

    if ($siswa_id <= 0 || $ekskul_id <= 0 || $tanggal === "" || $status_hadir === "") {
        $error = "Semua data absensi wajib diisi.";

    } elseif (!in_array($status_hadir, $statusValid, true)) {
        $error = "Status kehadiran tidak valid.";

    } else {

        /* CEK SISWA TERDAFTAR DITERIMA */
        $cek = mysqli_prepare($conn,
            "SELECT id FROM pendaftaran
             WHERE siswa_id = ? AND ekskul_id = ? AND status = 'diterima'
             LIMIT 1");
        mysqli_stmt_bind_param($cek, "ii", $siswa_id, $ekskul_id);
        mysqli_stmt_execute($cek);
        $hasilCek = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($hasilCek) == 0) {
            $error = "Siswa tersebut belum terdaftar atau belum diterima pada ekstrakurikuler tersebut.";
            mysqli_stmt_close($cek);

        } else {
            mysqli_stmt_close($cek);

            /* CEK DUPLIKAT */
            $cekAbsensi = mysqli_prepare($conn,
                "SELECT id FROM absensi
                 WHERE siswa_id = ? AND ekskul_id = ? AND tanggal = ?
                 LIMIT 1");
            mysqli_stmt_bind_param($cekAbsensi, "iis", $siswa_id, $ekskul_id, $tanggal);
            mysqli_stmt_execute($cekAbsensi);
            $hasilAbsensi = mysqli_stmt_get_result($cekAbsensi);

            if (mysqli_num_rows($hasilAbsensi) > 0) {
                $error = "Absensi siswa pada tanggal tersebut sudah ada.";
                mysqli_stmt_close($cekAbsensi);

            } else {
                mysqli_stmt_close($cekAbsensi);

                $simpan = mysqli_prepare($conn,
                    "INSERT INTO absensi (siswa_id, ekskul_id, tanggal, status_hadir)
                     VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($simpan, "iiss",
                    $siswa_id, $ekskul_id, $tanggal, $status_hadir);

                if (mysqli_stmt_execute($simpan)) {
                    $success = "Absensi berhasil disimpan.";
                } else {
                    $error = "Gagal menyimpan absensi: " . mysqli_stmt_error($simpan);
                }
                mysqli_stmt_close($simpan);
            }
        }
    }
}


/* =========================================================
   HAPUS ABSENSI
========================================================= */
if (isset($_GET['hapus'])) {

    $id = (int)($_GET['hapus'] ?? 0);

    if ($id > 0) {

        $stmt = mysqli_prepare($conn, "DELETE FROM absensi WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Data absensi berhasil dihapus.";
        } else {
            $error = "Gagal menghapus absensi: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}


/* =========================================================
   DATA EKSTRAKURIKULER
========================================================= */
$listEkskul = [];
$queryEkskul = mysqli_query($conn,
    "SELECT id, nama_ekskul, pembina FROM ekstrakurikuler ORDER BY nama_ekskul ASC");

if ($queryEkskul) {
    while ($r = mysqli_fetch_assoc($queryEkskul)) {
        $listEkskul[] = $r;
    }
}


/* =========================================================
   FILTER
========================================================= */
$filterEkskul = (int)($_GET['filter_ekskul'] ?? 0);
$filterTanggal = trim($_GET['filter_tanggal'] ?? "");

$where  = [];
$params = [];
$types  = "";

if ($filterEkskul > 0) {
    $where[]  = "a.ekskul_id = ?";
    $params[] = $filterEkskul;
    $types   .= "i";
}

if ($filterTanggal !== "") {
    $where[]  = "a.tanggal = ?";
    $params[] = $filterTanggal;
    $types   .= "s";
}

$whereSQL = "";
if (count($where) > 0) {
    $whereSQL = "WHERE " . implode(" AND ", $where);
}


/* =========================================================
   DATA ABSENSI (disimpan ke array)
========================================================= */
$listAbsensi = [];

if (count($params) > 0) {

    $stmtA = mysqli_prepare($conn,
        "SELECT
            a.id, a.tanggal, a.status_hadir,
            s.nama_siswa, s.nis, s.kelas,
            e.nama_ekskul
         FROM absensi a
         INNER JOIN siswa s ON a.siswa_id = s.id
         INNER JOIN ekstrakurikuler e ON a.ekskul_id = e.id
         $whereSQL
         ORDER BY a.tanggal DESC, a.id DESC");

    if ($stmtA) {
        mysqli_stmt_bind_param($stmtA, $types, ...$params);
        mysqli_stmt_execute($stmtA);
        $resA = mysqli_stmt_get_result($stmtA);

        if ($resA) {
            while ($r = mysqli_fetch_assoc($resA)) {
                $listAbsensi[] = $r;
            }
        }
        mysqli_stmt_close($stmtA);
    }

} else {

    $resA = mysqli_query($conn,
        "SELECT
            a.id, a.tanggal, a.status_hadir,
            s.nama_siswa, s.nis, s.kelas,
            e.nama_ekskul
         FROM absensi a
         INNER JOIN siswa s ON a.siswa_id = s.id
         INNER JOIN ekstrakurikuler e ON a.ekskul_id = e.id
         ORDER BY a.tanggal DESC, a.id DESC");

    if ($resA) {
        while ($r = mysqli_fetch_assoc($resA)) {
            $listAbsensi[] = $r;
        }
    }
}


/* =========================================================
   STATISTIK ABSENSI
========================================================= */
$totalAbsensi = 0;
$totalHadir   = 0;
$totalIzin    = 0;
$totalSakit   = 0;
$totalAlfa    = 0;

$queryStat = mysqli_query($conn,
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status_hadir = 'hadir' THEN 1 ELSE 0 END) AS hadir,
        SUM(CASE WHEN status_hadir = 'izin'  THEN 1 ELSE 0 END) AS izin,
        SUM(CASE WHEN status_hadir = 'sakit' THEN 1 ELSE 0 END) AS sakit,
        SUM(CASE WHEN status_hadir = 'alfa'  THEN 1 ELSE 0 END) AS alfa
     FROM absensi");

if ($queryStat) {
    $stat = mysqli_fetch_assoc($queryStat);
    $totalAbsensi = (int)($stat['total']  ?? 0);
    $totalHadir   = (int)($stat['hadir']  ?? 0);
    $totalIzin    = (int)($stat['izin']   ?? 0);
    $totalSakit   = (int)($stat['sakit']  ?? 0);
    $totalAlfa    = (int)($stat['alfa']   ?? 0);
}
?>


<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Absensi - Sistem Informasi Ekstrakurikuler</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
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

.brand h4 {
    margin: 11px 0 3px;
    font-weight: 700;
}

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

.sidebar a i {
    width: 21px;
    text-align: center;
    font-size: 17px;
}

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

.topbar-title h5 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
}

.topbar-title small { color: #64748b; }

.admin-area {
    display: flex;
    align-items: center;
    gap: 11px;
}

.admin-info { text-align: right; }

.admin-info strong {
    display: block;
    font-size: 14px;
}

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

.welcome h2 {
    margin: 0 0 7px;
    font-size: 27px;
    font-weight: 700;
}

.welcome p {
    margin: 0;
    color: rgba(255,255,255,.82);
}


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

.stat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

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

.stat-number {
    font-size: 27px;
    font-weight: 750;
    margin-top: 15px;
}

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

.section-header h5 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
}

.section-header small { color: #64748b; }


/* =====================================================
   SEARCH AREA
===================================================== */

.search-area {
    padding: 18px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #eef2f5;
}

.search-area .form-select,
.search-area .form-control {
    min-height: 43px;
    border-radius: 11px;
    border: 1px solid #e2e8f0;
    font-size: 13px;
}

.search-area .form-select:focus,
.search-area .form-control:focus {
    border-color: #38bdf8;
    box-shadow: 0 0 0 3px rgba(56,189,248,.12);
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

.btn-reset:hover {
    background: #f1f5f9;
    color: #475569;
}


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

.student-box {
    display: flex;
    align-items: center;
    gap: 12px;
}

.student-avatar {
    width: 42px; height: 42px;
    flex-shrink: 0;
    border-radius: 12px;
    background: #e0f2fe;
    color: #0284c7;
    display: flex;
    align-items: center; justify-content: center;
    font-size: 18px;
}

.student-info strong {
    display: block;
    font-size: 13px;
}

.student-info small {
    color: #64748b;
    font-size: 11px;
}

.ekskul-box {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ekskul-icon {
    width: 36px; height: 36px;
    flex-shrink: 0;
    border-radius: 10px;
    background: #e0f2fe;
    color: #0284c7;
    display: flex;
    align-items: center; justify-content: center;
    font-size: 15px;
}

.ekskul-name {
    font-weight: 600;
    font-size: 12px;
}

.tanggal-box {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #475569;
    font-size: 12px;
}

.tanggal-box i { color: #0f766e; }


/* =====================================================
   STATUS BADGE
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

.status-hadir { background:#dcfce7; color:#15803d; }
.status-izin  { background:#fef3c7; color:#b45309; }
.status-sakit { background:#dbeafe; color:#1d4ed8; }
.status-alfa  { background:#fee2e2; color:#dc2626; }


/* =====================================================
   ACTION BUTTON
===================================================== */

.action-buttons {
    display: flex;
    align-items: center;
    gap: 6px;
}

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

.btn-delete {
    background: #fee2e2;
    color: #dc2626;
}

.btn-delete:hover {
    background: #dc2626;
    color: white;
}


/* =====================================================
   EMPTY STATE
===================================================== */

.empty-state {
    padding: 45px 20px;
    text-align: center;
    color: #94a3b8;
}

.empty-state i {
    display: block;
    font-size: 35px;
    margin-bottom: 10px;
}

.empty-state strong {
    display: block;
    color: #475569;
    margin-bottom: 4px;
}

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

.modal-header .btn-close {
    filter: brightness(0) invert(1);
}

.modal-title {
    font-size: 16px;
    font-weight: 700;
}

.modal-body { padding: 22px; }

.modal-footer {
    border-top: 1px solid #f1f5f9;
    padding: 15px 22px;
}

.form-label {
    font-size: 12px;
    font-weight: 600;
    color: #475569;
}

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

.info-siswa {
    font-size: 12px;
    color: #64748b;
    margin-top: 6px;
}

.loading {
    color: #64748b;
    font-size: 13px;
}


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


<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar">

    <div class="brand">
        <div class="brand-icon"><i class="bi bi-mortarboard-fill"></i></div>
        <h4>Ekstrakurikuler</h4>
        <small>Administrator</small>
    </div>

    <div class="menu-title">Menu Utama</div>

    <a href="dashboard_admin.php">
        <i class="bi bi-grid-1x2-fill"></i>
        <span>Dashboard</span>
    </a>

    <a href="ekstrakurikuler.php">
        <i class="bi bi-stars"></i>
        <span>Ekstrakurikuler</span>
    </a>

    <a href="data_siswa.php">
        <i class="bi bi-people-fill"></i>
        <span>Data Siswa</span>
    </a>

    <a href="pendaftaran.php">
        <i class="bi bi-person-check-fill"></i>
        <span>Pendaftaran</span>
    </a>

    <a href="jadwal.php">
        <i class="bi bi-calendar-event-fill"></i>
        <span>Jadwal</span>
    </a>

    <a href="absensi.php" class="active">
        <i class="bi bi-calendar-check-fill"></i>
        <span>Absensi</span>
    </a>

    <a href="laporan.php">
        <i class="bi bi-bar-chart-fill"></i>
        <span>Laporan</span>
    </a>

    <a href="dashboard_admin.php?page=pembina">
        <i class="bi bi-person-workspace"></i>
        <span>Pembina</span>
    </a>

    <div class="logout">
        <a href="../logout.php">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>

</div>


<!-- =====================================================
     CONTENT
===================================================== -->

<div class="content">


    <!-- TOPBAR -->
    <div class="topbar">

        <div class="topbar-title">
            <h5>Absensi Siswa</h5>
            <small>Sistem Informasi Ekstrakurikuler</small>
        </div>

        <div class="admin-area">
            <div class="admin-info">
                <strong><?= htmlspecialchars($namaAdmin); ?></strong>
                <small>Administrator</small>
            </div>
            <div class="admin-avatar">
                <?= htmlspecialchars($inisial); ?>
            </div>
        </div>

    </div>


    <!-- WELCOME -->
    <div class="welcome">
        <div class="welcome-content">

            <div class="welcome-badge">
                <i class="bi bi-calendar-check-fill"></i>
                Manajemen Absensi
            </div>

            <h2>Data Absensi Siswa</h2>

            <p>
                Kelola kehadiran siswa pada setiap
                ekstrakurikuler sekolah.
            </p>

        </div>
    </div>


    <!-- PESAN -->
    <?php if ($error !== ""): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle-fill me-2"></i>
        <?= htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?= htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>


    <!-- STATISTIK -->
    <div class="row g-3 mb-4">

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-blue"><i class="bi bi-calendar-check-fill"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalAbsensi; ?></div>
                <div class="stat-label">Total Absensi</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-green"><i class="bi bi-check-circle-fill"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalHadir; ?></div>
                <div class="stat-label">Hadir</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-yellow"><i class="bi bi-info-circle-fill"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalIzin; ?></div>
                <div class="stat-label">Izin</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-indigo"><i class="bi bi-bandaid-fill"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalSakit; ?></div>
                <div class="stat-label">Sakit</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-red"><i class="bi bi-x-circle-fill"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalAlfa; ?></div>
                <div class="stat-label">Alfa</div>
            </div>
        </div>

    </div>


    <!-- DATA ABSENSI -->
    <div class="section-card">

        <div class="section-header">

            <div>
                <h5>
                    <i class="bi bi-calendar-check-fill me-1"></i>
                    Data Absensi
                </h5>
                <small>Menampilkan <?= count($listAbsensi); ?> data absensi</small>
            </div>

            <button type="button" class="btn-add"
                    data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-plus-lg me-1"></i>
                Tambah Absensi
            </button>

        </div>


        <!-- FILTER -->
        <div class="search-area">
            <form method="GET" action="absensi.php">
                <div class="row g-2">

                    <div class="col-lg-5">
                        <select name="filter_ekskul" class="form-select">
                            <option value="">Semua Ekstrakurikuler</option>
                            <?php foreach ($listEkskul as $e): ?>
                                <option value="<?= (int)$e['id']; ?>"
                                    <?= $filterEkskul === (int)$e['id'] ? "selected" : ""; ?>>
                                    <?= htmlspecialchars($e['nama_ekskul']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-lg-4">
                        <input type="date" name="filter_tanggal" class="form-control"
                               value="<?= htmlspecialchars($filterTanggal); ?>">
                    </div>

                    <div class="col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1"
                                style="height:43px;border-radius:11px;">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>

                        <?php if ($filterEkskul > 0 || $filterTanggal !== ""): ?>
                            <a href="absensi.php" class="btn-reset d-flex align-items-center"
                               title="Reset">
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
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Ekstrakurikuler</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th class="text-center" style="width:90px;">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($listAbsensi) > 0): ?>

                    <?php $no = 1; foreach ($listAbsensi as $row): ?>

                        <?php
                        $status = strtolower(trim($row['status_hadir']));
                        $statusLabel = ucfirst($status);
                        $statusIcon  = "bi-info-circle-fill";

                        if ($status === "hadir") {
                            $statusIcon = "bi-check-circle-fill";
                        } elseif ($status === "izin") {
                            $statusIcon = "bi-info-circle-fill";
                        } elseif ($status === "sakit") {
                            $statusIcon = "bi-bandaid-fill";
                        } elseif ($status === "alfa") {
                            $statusIcon = "bi-x-circle-fill";
                        }
                        ?>

                        <tr>

                            <td>
                                <div class="number-box"><?= $no++; ?></div>
                            </td>

                            <td>
                                <div class="student-box">
                                    <div class="student-avatar">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                    <div class="student-info">
                                        <strong><?= htmlspecialchars($row['nama_siswa']); ?></strong>
                                        <small>NIS: <?= htmlspecialchars($row['nis'] ?? '-'); ?></small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['kelas'] ?? '-'); ?>
                            </td>

                            <td>
                                <div class="ekskul-box">
                                    <div class="ekskul-icon">
                                        <i class="bi bi-stars"></i>
                                    </div>
                                    <div class="ekskul-name">
                                        <?= htmlspecialchars($row['nama_ekskul']); ?>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="tanggal-box">
                                    <i class="bi bi-calendar3"></i>
                                    <?= htmlspecialchars($row['tanggal']); ?>
                                </div>
                            </td>

                            <td>
                                <span class="status-badge status-<?= htmlspecialchars($status); ?>">
                                    <i class="bi <?= $statusIcon; ?>"></i>
                                    <?= $statusLabel; ?>
                                </span>
                            </td>

                            <td class="text-center">
                                <div class="action-buttons justify-content-center">
                                    <a href="absensi.php?hapus=<?= (int)$row['id']; ?>"
                                       class="btn-action btn-delete" title="Hapus"
                                       onclick="return confirm('Yakin ingin menghapus data absensi ini?');">
                                        <i class="bi bi-trash3-fill"></i>
                                    </a>
                                </div>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="bi bi-calendar-x"></i>
                                <strong>Belum Ada Data Absensi</strong>
                                <span>
                                    <?= ($filterEkskul > 0 || $filterTanggal !== "")
                                        ? "Tidak ada absensi yang cocok dengan filter."
                                        : "Silakan tambahkan absensi terlebih dahulu."; ?>
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


<!-- =====================================================
     MODAL TAMBAH ABSENSI
===================================================== -->

<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>
                    Tambah Absensi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" id="formAbsensi">

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Ekstrakurikuler</label>
                            <select name="ekskul_id" id="ekskul_id" class="form-select" required>
                                <option value="">-- Pilih Ekstrakurikuler --</option>
                                <?php foreach ($listEkskul as $e): ?>
                                    <option value="<?= (int)$e['id']; ?>">
                                        <?= htmlspecialchars($e['nama_ekskul']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nama Siswa</label>
                            <select name="siswa_id" id="siswa_id" class="form-select" required disabled>
                                <option value="">-- Pilih Ekstrakurikuler Terlebih Dahulu --</option>
                            </select>
                            <div id="infoSiswa" class="info-siswa"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control"
                                   value="<?= date('Y-m-d'); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status Kehadiran</label>
                            <select name="status_hadir" class="form-select" required>
                                <option value="">-- Pilih Status --</option>
                                <option value="hadir">Hadir</option>
                                <option value="izin">Izin</option>
                                <option value="sakit">Sakit</option>
                                <option value="alfa">Alfa</option>
                            </select>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="simpan_absensi" class="btn-save">
                        <i class="bi bi-save me-1"></i>
                        Simpan Absensi
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>


<!-- BOOTSTRAP JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* =====================================================
   AJAX: Ambil siswa berdasarkan ekstrakurikuler
===================================================== */
const ekskulSelect = document.getElementById("ekskul_id");
const siswaSelect  = document.getElementById("siswa_id");
const infoSiswa    = document.getElementById("infoSiswa");

if (ekskulSelect && siswaSelect && infoSiswa) {

    ekskulSelect.addEventListener("change", function () {

        const ekskulId = this.value;

        siswaSelect.innerHTML = '<option value="">-- Pilih Siswa --</option>';
        siswaSelect.disabled = true;
        infoSiswa.innerHTML = "";

        if (ekskulId === "") {
            siswaSelect.innerHTML = '<option value="">-- Pilih Ekstrakurikuler Terlebih Dahulu --</option>';
            return;
        }

        siswaSelect.innerHTML = '<option value="">Mengambil data siswa...</option>';
        infoSiswa.innerHTML = '<span class="loading"><i class="bi bi-arrow-repeat"></i> Mengambil siswa yang sudah diterima...</span>';

        fetch("absensi.php?ambil_siswa=" + encodeURIComponent(ekskulId))
            .then(function (response) {
                if (!response.ok) throw new Error("HTTP Error " + response.status);
                return response.json();
            })
            .then(function (result) {

                siswaSelect.innerHTML = '<option value="">-- Pilih Siswa --</option>';

                if (!result.success) {
                    infoSiswa.innerHTML = '<span class="text-danger">' + result.message + '</span>';
                    return;
                }

                if (result.data.length === 0) {
                    siswaSelect.innerHTML = '<option value="">-- Tidak ada siswa diterima --</option>';
                    siswaSelect.disabled = true;
                    infoSiswa.innerHTML = '<span class="text-warning">Belum ada siswa yang diterima pada ekstrakurikuler ini.</span>';
                    return;
                }

                result.data.forEach(function (siswa) {
                    const option = document.createElement("option");
                    option.value = siswa.id;
                    option.textContent = siswa.nama_siswa + (siswa.kelas ? " - " + siswa.kelas : "");
                    siswaSelect.appendChild(option);
                });

                siswaSelect.disabled = false;
                infoSiswa.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> ' + result.data.length + ' siswa ditemukan.</span>';
            })
            .catch(function (error) {
                console.error("Error:", error);
                siswaSelect.innerHTML = '<option value="">-- Gagal mengambil data siswa --</option>';
                siswaSelect.disabled = true;
                infoSiswa.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle"></i> Gagal mengambil data siswa.</span>';
            });
    });
}
</script>

</body>
</html><?php
/* =========================================================
   ABSENSI - HALAMAN ADMIN
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

$error   = "";
$success = "";


/* =========================================================
   AJAX - AMBIL SISWA
========================================================= */
if (isset($_GET['ambil_siswa'])) {

    header('Content-Type: application/json; charset=utf-8');

    $ekskul_id = (int)($_GET['ambil_siswa'] ?? 0);

    if ($ekskul_id <= 0) {
        echo json_encode(["success" => false, "message" => "Ekstrakurikuler tidak valid.", "data" => []]);
        exit();
    }

    $stmt = mysqli_prepare($conn,
        "SELECT DISTINCT s.id, s.nama_siswa, s.nis, s.kelas
         FROM pendaftaran p
         INNER JOIN siswa s ON p.siswa_id = s.id
         WHERE p.ekskul_id = ? AND p.status = 'diterima'
         ORDER BY s.nama_siswa ASC");

    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Query gagal: " . mysqli_error($conn), "data" => []]);
        exit();
    }

    mysqli_stmt_bind_param($stmt, "i", $ekskul_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data   = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            "id"         => $row["id"],
            "nama_siswa" => $row["nama_siswa"],
            "nis"        => $row["nis"],
            "kelas"      => $row["kelas"]
        ];
    }
    mysqli_stmt_close($stmt);

    echo json_encode(["success" => true, "message" => "Berhasil", "data" => $data]);
    exit();
}


/* =========================================================
   SIMPAN ABSENSI
========================================================= */
if (isset($_POST['simpan_absensi'])) {

    $siswa_id     = (int)($_POST['siswa_id'] ?? 0);
    $ekskul_id    = (int)($_POST['ekskul_id'] ?? 0);
    $tanggal      = $_POST['tanggal'] ?? "";
    $status_hadir = $_POST['status_hadir'] ?? "";

    $statusValid = ["hadir", "izin", "sakit", "alfa"];

    if ($siswa_id <= 0 || $ekskul_id <= 0 || $tanggal === "" || $status_hadir === "") {
        $error = "Semua data absensi wajib diisi.";
    } elseif (!in_array($status_hadir, $statusValid, true)) {
        $error = "Status kehadiran tidak valid.";
    } else {

        $cek = mysqli_prepare($conn,
            "SELECT id FROM pendaftaran
             WHERE siswa_id = ? AND ekskul_id = ? AND status = 'diterima' LIMIT 1");
        mysqli_stmt_bind_param($cek, "ii", $siswa_id, $ekskul_id);
        mysqli_stmt_execute($cek);
        $hasilCek = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($hasilCek) == 0) {
            $error = "Siswa tersebut belum terdaftar atau belum diterima pada ekstrakurikuler tersebut.";
            mysqli_stmt_close($cek);
        } else {
            mysqli_stmt_close($cek);

            $cekAbsensi = mysqli_prepare($conn,
                "SELECT id FROM absensi WHERE siswa_id = ? AND ekskul_id = ? AND tanggal = ? LIMIT 1");
            mysqli_stmt_bind_param($cekAbsensi, "iis", $siswa_id, $ekskul_id, $tanggal);
            mysqli_stmt_execute($cekAbsensi);
            $hasilAbsensi = mysqli_stmt_get_result($cekAbsensi);

            if (mysqli_num_rows($hasilAbsensi) > 0) {
                $error = "Absensi siswa pada tanggal tersebut sudah ada.";
                mysqli_stmt_close($cekAbsensi);
            } else {
                mysqli_stmt_close($cekAbsensi);

                $simpan = mysqli_prepare($conn,
                    "INSERT INTO absensi (siswa_id, ekskul_id, tanggal, status_hadir) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($simpan, "iiss", $siswa_id, $ekskul_id, $tanggal, $status_hadir);

                if (mysqli_stmt_execute($simpan)) {
                    $success = "Absensi berhasil disimpan.";
                } else {
                    $error = "Gagal menyimpan absensi: " . mysqli_stmt_error($simpan);
                }
                mysqli_stmt_close($simpan);
            }
        }
    }
}


/* =========================================================
   HAPUS ABSENSI
========================================================= */
if (isset($_GET['hapus'])) {
    $id = (int)($_GET['hapus'] ?? 0);

    if ($id > 0) {
        $stmt = mysqli_prepare($conn, "DELETE FROM absensi WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Data absensi berhasil dihapus.";
        } else {
            $error = "Gagal menghapus absensi: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}


/* =========================================================
   DATA EKSKUL
========================================================= */
$listEkskul = [];
$queryEkskul = mysqli_query($conn,
    "SELECT id, nama_ekskul, pembina FROM ekstrakurikuler ORDER BY nama_ekskul ASC");

if ($queryEkskul) {
    while ($r = mysqli_fetch_assoc($queryEkskul)) {
        $listEkskul[] = $r;
    }
}


/* =========================================================
   FILTER
========================================================= */
$filterEkskul  = (int)($_GET['filter_ekskul'] ?? 0);
$filterTanggal = trim($_GET['filter_tanggal'] ?? "");

$where  = [];
$params = [];
$types  = "";

if ($filterEkskul > 0) {
    $where[]  = "a.ekskul_id = ?";
    $params[] = $filterEkskul;
    $types   .= "i";
}

if ($filterTanggal !== "") {
    $where[]  = "a.tanggal = ?";
    $params[] = $filterTanggal;
    $types   .= "s";
}

$whereSQL = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";


/* =========================================================
   DATA ABSENSI
========================================================= */
$listAbsensi = [];

if (count($params) > 0) {

    $stmtA = mysqli_prepare($conn,
        "SELECT a.id, a.tanggal, a.status_hadir,
            s.nama_siswa, s.nis, s.kelas, e.nama_ekskul
         FROM absensi a
         INNER JOIN siswa s ON a.siswa_id = s.id
         INNER JOIN ekstrakurikuler e ON a.ekskul_id = e.id
         $whereSQL
         ORDER BY a.tanggal DESC, a.id DESC");

    if ($stmtA) {
        mysqli_stmt_bind_param($stmtA, $types, ...$params);
        mysqli_stmt_execute($stmtA);
        $resA = mysqli_stmt_get_result($stmtA);
        if ($resA) {
            while ($r = mysqli_fetch_assoc($resA)) $listAbsensi[] = $r;
        }
        mysqli_stmt_close($stmtA);
    }

} else {

    $resA = mysqli_query($conn,
        "SELECT a.id, a.tanggal, a.status_hadir,
            s.nama_siswa, s.nis, s.kelas, e.nama_ekskul
         FROM absensi a
         INNER JOIN siswa s ON a.siswa_id = s.id
         INNER JOIN ekstrakurikuler e ON a.ekskul_id = e.id
         ORDER BY a.tanggal DESC, a.id DESC");

    if ($resA) {
        while ($r = mysqli_fetch_assoc($resA)) $listAbsensi[] = $r;
    }
}


/* =========================================================
   STATISTIK
========================================================= */
$totalAbsensi = 0;
$totalHadir   = 0;
$totalIzin    = 0;
$totalSakit   = 0;
$totalAlfa    = 0;

$queryStat = mysqli_query($conn,
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status_hadir = 'hadir' THEN 1 ELSE 0 END) AS hadir,
        SUM(CASE WHEN status_hadir = 'izin'  THEN 1 ELSE 0 END) AS izin,
        SUM(CASE WHEN status_hadir = 'sakit' THEN 1 ELSE 0 END) AS sakit,
        SUM(CASE WHEN status_hadir = 'alfa'  THEN 1 ELSE 0 END) AS alfa
     FROM absensi");

if ($queryStat) {
    $stat = mysqli_fetch_assoc($queryStat);
    $totalAbsensi = (int)($stat['total']  ?? 0);
    $totalHadir   = (int)($stat['hadir']  ?? 0);
    $totalIzin    = (int)($stat['izin']   ?? 0);
    $totalSakit   = (int)($stat['sakit']  ?? 0);
    $totalAlfa    = (int)($stat['alfa']   ?? 0);
}
?>


<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Absensi - Sistem Informasi Ekstrakurikuler</title>

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
   SEARCH AREA
===================================================== */

.search-area {
    padding: 18px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #eef2f5;
}

.search-area .form-select,
.search-area .form-control {
    min-height: 43px;
    border-radius: 11px;
    border: 1px solid #e2e8f0;
    font-size: 13px;
}

.search-area .form-select:focus,
.search-area .form-control:focus {
    border-color: #38bdf8;
    box-shadow: 0 0 0 3px rgba(56,189,248,.12);
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

.student-box { display: flex; align-items: center; gap: 12px; }

.student-avatar {
    width: 42px; height: 42px;
    flex-shrink: 0;
    border-radius: 12px;
    background: #e0f2fe;
    color: #0284c7;
    display: flex;
    align-items: center; justify-content: center;
    font-size: 18px;
}

.student-info strong { display: block; font-size: 13px; }
.student-info small { color: #64748b; font-size: 11px; }

.ekskul-box { display: flex; align-items: center; gap: 10px; }

.ekskul-icon {
    width: 36px; height: 36px;
    flex-shrink: 0;
    border-radius: 10px;
    background: #e0f2fe;
    color: #0284c7;
    display: flex;
    align-items: center; justify-content: center;
    font-size: 15px;
}

.ekskul-name { font-weight: 600; font-size: 12px; }

.tanggal-box {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #475569;
    font-size: 12px;
}

.tanggal-box i { color: #0f766e; }


/* =====================================================
   STATUS BADGE
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

.status-hadir { background:#dcfce7; color:#15803d; }
.status-izin  { background:#fef3c7; color:#b45309; }
.status-sakit { background:#dbeafe; color:#1d4ed8; }
.status-alfa  { background:#fee2e2; color:#dc2626; }


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

.info-siswa { font-size: 12px; color: #64748b; margin-top: 6px; }
.loading { color: #64748b; font-size: 13px; }


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
    <a href="jadwal.php"><i class="bi bi-calendar-event-fill"></i><span>Jadwal</span></a>
    <a href="absensi.php" class="active"><i class="bi bi-calendar-check-fill"></i><span>Absensi</span></a>
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
            <h5>Absensi Siswa</h5>
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
                <i class="bi bi-calendar-check-fill"></i>
                Manajemen Absensi
            </div>
            <h2>Data Absensi Siswa</h2>
            <p>Kelola kehadiran siswa pada setiap ekstrakurikuler sekolah.</p>
        </div>
    </div>


    <!-- PESAN -->
    <?php if ($error !== ""): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle-fill me-2"></i>
        <?= htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?= htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>


    <!-- STATISTIK -->
    <div class="row g-3 mb-4">

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-blue"><i class="bi bi-calendar-check-fill"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalAbsensi; ?></div>
                <div class="stat-label">Total Absensi</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-green"><i class="bi bi-check-circle-fill"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalHadir; ?></div>
                <div class="stat-label">Hadir</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-yellow"><i class="bi bi-info-circle-fill"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalIzin; ?></div>
                <div class="stat-label">Izin</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-indigo"><i class="bi bi-bandaid-fill"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalSakit; ?></div>
                <div class="stat-label">Sakit</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-red"><i class="bi bi-x-circle-fill"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalAlfa; ?></div>
                <div class="stat-label">Alfa</div>
            </div>
        </div>

    </div>


    <!-- DATA ABSENSI -->
    <div class="section-card">

        <div class="section-header">
            <div>
                <h5><i class="bi bi-calendar-check-fill me-1"></i> Data Absensi</h5>
                <small>Menampilkan <?= count($listAbsensi); ?> data absensi</small>
            </div>

            <button type="button" class="btn-add"
                    data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-plus-lg me-1"></i>
                Tambah Absensi
            </button>
        </div>


        <!-- FILTER -->
        <div class="search-area">
            <form method="GET" action="absensi.php">
                <div class="row g-2">

                    <div class="col-lg-5">
                        <select name="filter_ekskul" class="form-select">
                            <option value="">Semua Ekstrakurikuler</option>
                            <?php foreach ($listEkskul as $e): ?>
                                <option value="<?= (int)$e['id']; ?>"
                                    <?= $filterEkskul === (int)$e['id'] ? "selected" : ""; ?>>
                                    <?= htmlspecialchars($e['nama_ekskul']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-lg-4">
                        <input type="date" name="filter_tanggal" class="form-control"
                               value="<?= htmlspecialchars($filterTanggal); ?>">
                    </div>

                    <div class="col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1"
                                style="height:43px;border-radius:11px;">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>

                        <?php if ($filterEkskul > 0 || $filterTanggal !== ""): ?>
                            <a href="absensi.php" class="btn-reset d-flex align-items-center" title="Reset">
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
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Ekstrakurikuler</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th class="text-center" style="width:90px;">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($listAbsensi) > 0): ?>

                    <?php $no = 1; foreach ($listAbsensi as $row): ?>

                        <?php
                        $status = strtolower(trim($row['status_hadir']));
                        $statusLabel = ucfirst($status);
                        $statusIcon  = "bi-info-circle-fill";

                        if ($status === "hadir")       $statusIcon = "bi-check-circle-fill";
                        elseif ($status === "izin")    $statusIcon = "bi-info-circle-fill";
                        elseif ($status === "sakit")   $statusIcon = "bi-bandaid-fill";
                        elseif ($status === "alfa")    $statusIcon = "bi-x-circle-fill";
                        ?>

                        <tr>

                            <td><div class="number-box"><?= $no++; ?></div></td>

                            <td>
                                <div class="student-box">
                                    <div class="student-avatar"><i class="bi bi-person-fill"></i></div>
                                    <div class="student-info">
                                        <strong><?= htmlspecialchars($row['nama_siswa']); ?></strong>
                                        <small>NIS: <?= htmlspecialchars($row['nis'] ?? '-'); ?></small>
                                    </div>
                                </div>
                            </td>

                            <td><?= htmlspecialchars($row['kelas'] ?? '-'); ?></td>

                            <td>
                                <div class="ekskul-box">
                                    <div class="ekskul-icon"><i class="bi bi-stars"></i></div>
                                    <div class="ekskul-name"><?= htmlspecialchars($row['nama_ekskul']); ?></div>
                                </div>
                            </td>

                            <td>
                                <div class="tanggal-box">
                                    <i class="bi bi-calendar3"></i>
                                    <?= htmlspecialchars($row['tanggal']); ?>
                                </div>
                            </td>

                            <td>
                                <span class="status-badge status-<?= htmlspecialchars($status); ?>">
                                    <i class="bi <?= $statusIcon; ?>"></i>
                                    <?= $statusLabel; ?>
                                </span>
                            </td>

                            <td class="text-center">
                                <div class="action-buttons justify-content-center">
                                    <a href="absensi.php?hapus=<?= (int)$row['id']; ?>"
                                       class="btn-action btn-delete" title="Hapus"
                                       onclick="return confirm('Yakin ingin menghapus data absensi ini?');">
                                        <i class="bi bi-trash3-fill"></i>
                                    </a>
                                </div>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="bi bi-calendar-x"></i>
                                <strong>Belum Ada Data Absensi</strong>
                                <span>
                                    <?= ($filterEkskul > 0 || $filterTanggal !== "")
                                        ? "Tidak ada absensi yang cocok dengan filter."
                                        : "Silakan tambahkan absensi terlebih dahulu."; ?>
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
                    Tambah Absensi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" id="formAbsensi">

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Ekstrakurikuler</label>
                            <select name="ekskul_id" id="ekskul_id" class="form-select" required>
                                <option value="">-- Pilih Ekstrakurikuler --</option>
                                <?php foreach ($listEkskul as $e): ?>
                                    <option value="<?= (int)$e['id']; ?>">
                                        <?= htmlspecialchars($e['nama_ekskul']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nama Siswa</label>
                            <select name="siswa_id" id="siswa_id" class="form-select" required disabled>
                                <option value="">-- Pilih Ekstrakurikuler Terlebih Dahulu --</option>
                            </select>
                            <div id="infoSiswa" class="info-siswa"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control"
                                   value="<?= date('Y-m-d'); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status Kehadiran</label>
                            <select name="status_hadir" class="form-select" required>
                                <option value="">-- Pilih Status --</option>
                                <option value="hadir">Hadir</option>
                                <option value="izin">Izin</option>
                                <option value="sakit">Sakit</option>
                                <option value="alfa">Alfa</option>
                            </select>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="simpan_absensi" class="btn-save">
                        <i class="bi bi-save me-1"></i>
                        Simpan Absensi
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const ekskulSelect = document.getElementById("ekskul_id");
const siswaSelect  = document.getElementById("siswa_id");
const infoSiswa    = document.getElementById("infoSiswa");

if (ekskulSelect && siswaSelect && infoSiswa) {

    ekskulSelect.addEventListener("change", function () {

        const ekskulId = this.value;

        siswaSelect.innerHTML = '<option value="">-- Pilih Siswa --</option>';
        siswaSelect.disabled = true;
        infoSiswa.innerHTML = "";

        if (ekskulId === "") {
            siswaSelect.innerHTML = '<option value="">-- Pilih Ekstrakurikuler Terlebih Dahulu --</option>';
            return;
        }

        siswaSelect.innerHTML = '<option value="">Mengambil data siswa...</option>';
        infoSiswa.innerHTML = '<span class="loading"><i class="bi bi-arrow-repeat"></i> Mengambil siswa yang sudah diterima...</span>';

        fetch("absensi.php?ambil_siswa=" + encodeURIComponent(ekskulId))
            .then(function (response) {
                if (!response.ok) throw new Error("HTTP Error " + response.status);
                return response.json();
            })
            .then(function (result) {

                siswaSelect.innerHTML = '<option value="">-- Pilih Siswa --</option>';

                if (!result.success) {
                    infoSiswa.innerHTML = '<span class="text-danger">' + result.message + '</span>';
                    return;
                }

                if (result.data.length === 0) {
                    siswaSelect.innerHTML = '<option value="">-- Tidak ada siswa diterima --</option>';
                    siswaSelect.disabled = true;
                    infoSiswa.innerHTML = '<span class="text-warning">Belum ada siswa yang diterima pada ekstrakurikuler ini.</span>';
                    return;
                }

                result.data.forEach(function (siswa) {
                    const option = document.createElement("option");
                    option.value = siswa.id;
                    option.textContent = siswa.nama_siswa + (siswa.kelas ? " - " + siswa.kelas : "");
                    siswaSelect.appendChild(option);
                });

                siswaSelect.disabled = false;
                infoSiswa.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> ' + result.data.length + ' siswa ditemukan.</span>';
            })
            .catch(function (error) {
                console.error("Error:", error);
                siswaSelect.innerHTML = '<option value="">-- Gagal mengambil data siswa --</option>';
                siswaSelect.disabled = true;
                infoSiswa.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle"></i> Gagal mengambil data siswa.</span>';
            });
    });
}
</script>

</body>
</html>