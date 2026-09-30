<?php
/* =========================================================
   DATA SISWA - HALAMAN ADMIN
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
   TAMBAH SISWA
========================================================= */
if (isset($_POST['tambah_siswa'])) {

    $nama_siswa    = trim($_POST['nama_siswa'] ?? '');
    $username      = trim($_POST['username'] ?? '');
    $password      = $_POST['password'] ?? '';
    $nis           = trim($_POST['nis'] ?? '');
    $kelas         = trim($_POST['kelas'] ?? '');
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $alamat        = trim($_POST['alamat'] ?? '');

    if ($nama_siswa === '' || $username === '' || $password === '') {
        $pesan     = "Nama, username, dan password wajib diisi.";
        $tipePesan = "danger";

    } elseif (strlen($password) < 6) {
        $pesan     = "Password minimal 6 karakter.";
        $tipePesan = "danger";

    } else {

        $cekUsername = mysqli_prepare($conn,
            "SELECT id FROM siswa WHERE username = ?");
        mysqli_stmt_bind_param($cekUsername, "s", $username);
        mysqli_stmt_execute($cekUsername);
        $hasilUsername = mysqli_stmt_get_result($cekUsername);

        if (mysqli_num_rows($hasilUsername) > 0) {
            $pesan     = "Username sudah digunakan.";
            $tipePesan = "danger";
            mysqli_stmt_close($cekUsername);

        } else {
            mysqli_stmt_close($cekUsername);

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $queryTambah = mysqli_prepare($conn,
                "INSERT INTO siswa
                (nama_siswa, username, password, nis, kelas, jenis_kelamin, alamat)
                VALUES (?, ?, ?, ?, ?, ?, ?)");

            mysqli_stmt_bind_param($queryTambah, "sssssss",
                $nama_siswa, $username, $passwordHash,
                $nis, $kelas, $jenis_kelamin, $alamat);

            if (mysqli_stmt_execute($queryTambah)) {
                $pesan     = "Data siswa berhasil ditambahkan.";
                $tipePesan = "success";
            } else {
                $pesan     = "Gagal menambahkan siswa: " . mysqli_error($conn);
                $tipePesan = "danger";
            }
            mysqli_stmt_close($queryTambah);
        }
    }
}


/* =========================================================
   EDIT SISWA
========================================================= */
if (isset($_POST['edit_siswa'])) {

    $id            = (int)($_POST['id'] ?? 0);
    $nama_siswa    = trim($_POST['nama_siswa'] ?? '');
    $username      = trim($_POST['username'] ?? '');
    $password      = $_POST['password'] ?? '';
    $nis           = trim($_POST['nis'] ?? '');
    $kelas         = trim($_POST['kelas'] ?? '');
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $alamat        = trim($_POST['alamat'] ?? '');

    if ($id <= 0 || $nama_siswa === '' || $username === '') {
        $pesan     = "Data tidak lengkap.";
        $tipePesan = "danger";

    } else {

        $cekUser = mysqli_prepare($conn,
            "SELECT id FROM siswa WHERE username = ? AND id != ? LIMIT 1");
        mysqli_stmt_bind_param($cekUser, "si", $username, $id);
        mysqli_stmt_execute($cekUser);
        $hasilUser = mysqli_stmt_get_result($cekUser);

        if (mysqli_num_rows($hasilUser) > 0) {
            $pesan     = "Username sudah digunakan siswa lain.";
            $tipePesan = "danger";
            mysqli_stmt_close($cekUser);

        } else {
            mysqli_stmt_close($cekUser);

            if ($password !== '') {

                if (strlen($password) < 6) {
                    $pesan     = "Password minimal 6 karakter.";
                    $tipePesan = "danger";
                    goto skip_edit;
                }

                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $queryEdit = mysqli_prepare($conn,
                    "UPDATE siswa SET
                        nama_siswa = ?, username = ?, password = ?,
                        nis = ?, kelas = ?, jenis_kelamin = ?, alamat = ?
                     WHERE id = ?");
                mysqli_stmt_bind_param($queryEdit, "sssssssi",
                    $nama_siswa, $username, $passwordHash,
                    $nis, $kelas, $jenis_kelamin, $alamat, $id);

            } else {

                $queryEdit = mysqli_prepare($conn,
                    "UPDATE siswa SET
                        nama_siswa = ?, username = ?,
                        nis = ?, kelas = ?, jenis_kelamin = ?, alamat = ?
                     WHERE id = ?");
                mysqli_stmt_bind_param($queryEdit, "ssssssi",
                    $nama_siswa, $username,
                    $nis, $kelas, $jenis_kelamin, $alamat, $id);
            }

            if (mysqli_stmt_execute($queryEdit)) {
                $pesan     = "Data siswa berhasil diperbarui.";
                $tipePesan = "success";
            } else {
                $pesan     = "Gagal mengubah data siswa: " . mysqli_error($conn);
                $tipePesan = "danger";
            }
            mysqli_stmt_close($queryEdit);
        }

        skip_edit:
    }
}


/* =========================================================
   HAPUS SISWA
========================================================= */
if (isset($_GET['hapus'])) {

    $id = (int)($_GET['hapus'] ?? 0);

    if ($id > 0) {

        $queryHapus = mysqli_prepare($conn, "DELETE FROM siswa WHERE id = ?");
        mysqli_stmt_bind_param($queryHapus, "i", $id);

        if (mysqli_stmt_execute($queryHapus)) {
            $pesan     = "Data siswa berhasil dihapus.";
            $tipePesan = "success";
        } else {
            $pesan     = "Data siswa gagal dihapus. Kemungkinan masih terhubung dengan data pendaftaran/absensi.";
            $tipePesan = "danger";
        }
        mysqli_stmt_close($queryHapus);
    }
}


/* =========================================================
   PENCARIAN
========================================================= */
$cari = trim($_GET['cari'] ?? '');

$hasilSiswa = false;

if ($cari !== '') {

    $keyword = "%" . $cari . "%";

    $stmtCari = mysqli_prepare($conn,
        "SELECT * FROM siswa
         WHERE nama_siswa LIKE ?
            OR username LIKE ?
            OR nis LIKE ?
            OR kelas LIKE ?
         ORDER BY id DESC");

    if ($stmtCari) {
        mysqli_stmt_bind_param($stmtCari, "ssss",
            $keyword, $keyword, $keyword, $keyword);
        mysqli_stmt_execute($stmtCari);
        $hasilSiswa = mysqli_stmt_get_result($stmtCari);
    }

} else {

    $hasilSiswa = mysqli_query($conn,
        "SELECT * FROM siswa ORDER BY id DESC");
}

$rowsSiswa = [];
if ($hasilSiswa) {
    while ($r = mysqli_fetch_assoc($hasilSiswa)) {
        $rowsSiswa[] = $r;
    }
}

$totalSiswa = 0;
$qTotal = mysqli_query($conn, "SELECT COUNT(*) AS total FROM siswa");
if ($qTotal) {
    $dTotal = mysqli_fetch_assoc($qTotal);
    $totalSiswa = (int)($dTotal['total'] ?? 0);
}

$totalL = 0;
$totalP = 0;
foreach ($rowsSiswa as $r) {
    $jk = strtolower(trim($r['jenis_kelamin'] ?? ''));
    if ($jk === 'laki-laki' || $jk === 'l') $totalL++;
    if ($jk === 'perempuan' || $jk === 'p') $totalP++;
}
?>


<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Data Siswa - Sistem Informasi Ekstrakurikuler</title>

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
.icon-purple { background:#ede9fe; color:#7c3aed; }

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
   TOOLBAR / SEARCH
===================================================== */

.toolbar {
    padding: 18px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    background: #f8fafc;
    border-bottom: 1px solid #eef2f5;
}

.search-box {
    max-width: 420px;
    width: 100%;
    position: relative;
}

.search-box i {
    position: absolute;
    left: 14px; top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 14px;
}

.search-box input {
    padding-left: 40px;
    min-height: 43px;
    border-radius: 11px;
    border: 1px solid #e2e8f0;
    font-size: 13px;
}

.search-box input:focus {
    border-color: #38bdf8;
    box-shadow: 0 0 0 3px rgba(56,189,248,.12);
}

.btn-search {
    position: absolute;
    right: 5px; top: 50%;
    transform: translateY(-50%);
    background: linear-gradient(135deg, #0284c7, #0f766e);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 7px 12px;
    font-size: 12px;
    transition: .2s;
}

.btn-search:hover { color: white; opacity: .92; }

.btn-reset {
    padding: 10px 14px;
    border-radius: 10px;
    background: white;
    border: 1px solid #e2e8f0;
    color: #64748b;
    text-decoration: none;
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
   AVATAR
===================================================== */

.siswa-avatar {
    width: 42px; height: 42px;
    border-radius: 12px;
    background: #e0f2fe;
    color: #0284c7;
    display: flex;
    align-items: center; justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.siswa-box { display: flex; align-items: center; gap: 12px; }
.siswa-box strong { display: block; font-size: 13px; }
.siswa-box small { color: #94a3b8; font-size: 11px; }

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

.jk-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.jk-l { background: #dbeafe; color: #1d4ed8; }
.jk-p { background: #fce7f3; color: #be185d; }

.kelas-badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 10px;
    border-radius: 20px;
    background: #ccfbf1;
    color: #0f766e;
    font-size: 11px;
    font-weight: 600;
}


/* =====================================================
   ACTION BUTTON
===================================================== */

.action-buttons { display: flex; align-items: center; gap: 6px; }

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

.form-control, .form-select {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 12px;
    font-size: 13px;
    box-shadow: none;
    min-height: 43px;
}

.form-control:focus, .form-select:focus {
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
    .search-box { max-width: 100%; }
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

    .toolbar {
        flex-direction: column;
        align-items: stretch;
    }

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
    <a href="ekstrakurikuler.php"><i class="bi bi-stars"></i><span>Ekstrakurikuler</span></a>
    <a href="data_siswa.php" class="active"><i class="bi bi-people-fill"></i><span>Data Siswa</span></a>
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
            <h5>Data Siswa</h5>
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
                <i class="bi bi-people-fill"></i>
                Manajemen Siswa
            </div>
            <h2>Data Siswa</h2>
            <p>
                Kelola seluruh data siswa yang terdaftar
                di sistem informasi ekstrakurikuler sekolah.
            </p>
        </div>
    </div>


    <!-- STATISTIK -->
    <div class="row g-3 mb-4">

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-blue"><i class="bi bi-people-fill"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalSiswa; ?></div>
                <div class="stat-label">Total Siswa</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-blue"><i class="bi bi-gender-male"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalL; ?></div>
                <div class="stat-label">Laki-laki</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-orange"><i class="bi bi-gender-female"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalP; ?></div>
                <div class="stat-label">Perempuan</div>
            </div>
        </div>

    </div>


    <!-- ALERT -->
    <?php if (!empty($pesan)): ?>
    <div class="alert alert-<?= htmlspecialchars($tipePesan); ?> alert-dismissible fade show alert-custom" role="alert">
        <i class="bi <?= $tipePesan === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
        <?= htmlspecialchars($pesan); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>


    <!-- DATA SISWA -->
    <div class="section-card">

        <div class="section-header">
            <div>
                <h5><i class="bi bi-people-fill me-1"></i> Daftar Siswa</h5>
                <small>Menampilkan <?= count($rowsSiswa); ?> data siswa</small>
            </div>
        </div>


        <!-- TOOLBAR -->
        <div class="toolbar">

            <form method="GET" class="search-box">
                <i class="bi bi-search"></i>
                <input
                    type="text"
                    name="cari"
                    class="form-control"
                    placeholder="Cari nama, username, NIS, kelas..."
                    value="<?= htmlspecialchars($cari); ?>"
                >
                <button class="btn-search" type="submit">
                    <i class="bi bi-search"></i>
                </button>
            </form>

            <div class="d-flex gap-2">

                <?php if ($cari !== ""): ?>
                    <a href="data_siswa.php" class="btn-reset">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>
                        Reset
                    </a>
                <?php endif; ?>

                <button class="btn-add" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="bi bi-plus-lg"></i>
                    Tambah Siswa
                </button>

            </div>

        </div>


        <!-- TABLE -->
        <div class="table-responsive">

            <table class="table">

                <thead>
                    <tr>
                        <th style="width:60px;">No</th>
                        <th>Nama Siswa</th>
                        <th>NIS</th>
                        <th>Kelas</th>
                        <th>Jenis Kelamin</th>
                        <th style="width:120px;" class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($rowsSiswa) > 0): ?>

                    <?php $no = 1; foreach ($rowsSiswa as $siswa): ?>

                        <?php
                        $jk = trim($siswa['jenis_kelamin'] ?? '');
                        $jkLower = strtolower($jk);
                        $jkClass = "jk-l";
                        $jkIcon  = "bi-gender-male";
                        if ($jkLower === "perempuan" || $jkLower === "p") {
                            $jkClass = "jk-p";
                            $jkIcon  = "bi-gender-female";
                        }
                        ?>

                        <tr>

                            <td>
                                <div class="number-box"><?= $no++; ?></div>
                            </td>

                            <td>
                                <div class="siswa-box">
                                    <div class="siswa-avatar">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                    <div>
                                        <strong><?= htmlspecialchars($siswa['nama_siswa']); ?></strong>
                                        <small>@<?= htmlspecialchars($siswa['username']); ?></small>
                                    </div>
                                </div>
                            </td>

                            <td><?= htmlspecialchars($siswa['nis'] ?? '-'); ?></td>

                            <td>
                                <?php if (!empty($siswa['kelas'])): ?>
                                    <span class="kelas-badge">
                                        <i class="bi bi-bookmark-fill me-1"></i>
                                        <?= htmlspecialchars($siswa['kelas']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($jk !== ''): ?>
                                    <span class="jk-badge <?= $jkClass; ?>">
                                        <i class="bi <?= $jkIcon; ?>"></i>
                                        <?= htmlspecialchars($jk); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center">
                                <div class="action-buttons justify-content-center">

                                    <button type="button" class="btn-action btn-edit" title="Edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editSiswa<?= $siswa['id']; ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <a href="data_siswa.php?hapus=<?= $siswa['id']; ?>"
                                       class="btn-action btn-delete" title="Hapus"
                                       onclick="return confirm('Yakin ingin menghapus data siswa ini?');">
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
                                <i class="bi bi-people"></i>
                                <strong>Belum Ada Data Siswa</strong>
                                <span>
                                    <?= $cari !== ""
                                        ? "Tidak ada siswa yang cocok dengan pencarian."
                                        : "Silakan tambahkan data siswa terlebih dahulu."; ?>
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
    <?php foreach ($rowsSiswa as $siswa): ?>

    <div class="modal fade" id="editSiswa<?= $siswa['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2"></i>
                        Edit Data Siswa
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form method="POST">

                    <div class="modal-body">

                        <input type="hidden" name="id" value="<?= $siswa['id']; ?>">

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">Nama Siswa</label>
                                <input type="text" name="nama_siswa" class="form-control"
                                       value="<?= htmlspecialchars($siswa['nama_siswa']); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control"
                                       value="<?= htmlspecialchars($siswa['username']); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Password Baru
                                    <small class="text-muted">(kosongkan jika tidak diganti)</small>
                                </label>
                                <input type="password" name="password" class="form-control"
                                       placeholder="Minimal 6 karakter">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">NIS</label>
                                <input type="text" name="nis" class="form-control"
                                       value="<?= htmlspecialchars($siswa['nis'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Kelas</label>
                                <input type="text" name="kelas" class="form-control"
                                       value="<?= htmlspecialchars($siswa['kelas'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Jenis Kelamin</label>
                                <select name="jenis_kelamin" class="form-select">
                                    <option value="">Pilih</option>
                                    <option value="Laki-laki"
                                        <?= (($siswa['jenis_kelamin'] ?? '') === 'Laki-laki') ? 'selected' : ''; ?>>
                                        Laki-laki
                                    </option>
                                    <option value="Perempuan"
                                        <?= (($siswa['jenis_kelamin'] ?? '') === 'Perempuan') ? 'selected' : ''; ?>>
                                        Perempuan
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Alamat</label>
                                <textarea name="alamat" class="form-control" rows="3"><?= htmlspecialchars($siswa['alamat'] ?? ''); ?></textarea>
                            </div>

                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-cancel" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_siswa" class="btn-save">
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
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-plus-fill me-2"></i>
                    Tambah Siswa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST">

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Nama Siswa</label>
                            <input type="text" name="nama_siswa" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control"
                                   placeholder="Minimal 6 karakter" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">NIS</label>
                            <input type="text" name="nis" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Kelas</label>
                            <input type="text" name="kelas" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select">
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="3"></textarea>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_siswa" class="btn-save">
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