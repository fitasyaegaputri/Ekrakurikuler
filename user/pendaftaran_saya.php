<?php

session_start();

require_once __DIR__ . "/../config/config.php";

/* =====================================================
   CEK LOGIN
===================================================== */

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

/* =====================================================
   CEK ROLE SISWA
===================================================== */

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "siswa") {
    header("Location: ../login.php");
    exit();
}

/* =====================================================
   DATA USER
===================================================== */

$user_id = (int) $_SESSION['id'];

$nama_user = $_SESSION['nama'] ?? "Siswa";

/* =====================================================
   DATA SISWA
===================================================== */

$siswa = null;
$siswa_id = 0;

$querySiswa = mysqli_query(
    $conn,
    "SELECT *
     FROM siswa
     WHERE user_id = '$user_id'
     LIMIT 1"
);

if (!$querySiswa) {
    die("Gagal mengambil data siswa: " . mysqli_error($conn));
}

if (mysqli_num_rows($querySiswa) > 0) {
    $siswa = mysqli_fetch_assoc($querySiswa);
    $siswa_id = (int) $siswa['id'];
}

/* =====================================================
   NAMA TAMPILAN
===================================================== */

$namaTampilan = $nama_user;

if ($siswa && !empty($siswa['nama_siswa'])) {
    $namaTampilan = $siswa['nama_siswa'];
}

$inisial = strtoupper(
    substr(trim($namaTampilan), 0, 1)
);

if ($inisial === '') {
    $inisial = 'S';
}

/* =====================================================
   STATISTIK PENDAFTARAN SAYA
===================================================== */

$totalPendaftaran = 0;
$totalMenunggu = 0;
$totalDiterima = 0;
$totalDitolak = 0;

if ($siswa_id > 0) {

    $queryStat = mysqli_query(
        $conn,
        "SELECT
            COUNT(*) AS total,
            COALESCE(SUM(status = 'menunggu'), 0) AS menunggu,
            COALESCE(SUM(status = 'diterima'), 0) AS diterima,
            COALESCE(SUM(status = 'ditolak'), 0) AS ditolak
         FROM pendaftaran
         WHERE siswa_id = '$siswa_id'"
    );

    if ($queryStat) {

        $stat = mysqli_fetch_assoc($queryStat);

        $totalPendaftaran = (int) ($stat['total'] ?? 0);
        $totalMenunggu = (int) ($stat['menunggu'] ?? 0);
        $totalDiterima = (int) ($stat['diterima'] ?? 0);
        $totalDitolak = (int) ($stat['ditolak'] ?? 0);
    }
}

/* =====================================================
   DATA PENDAFTARAN
===================================================== */

$dataPendaftaran = false;

if ($siswa_id > 0) {

    $dataPendaftaran = mysqli_query(
        $conn,
        "SELECT
            p.id,
            p.siswa_id,
            p.ekskul_id,
            p.status,
            e.nama_ekskul,
            e.pembina
         FROM pendaftaran p
         LEFT JOIN ekstrakurikuler e
            ON p.ekskul_id = e.id
         WHERE p.siswa_id = '$siswa_id'
         ORDER BY p.id DESC"
    );

    if (!$dataPendaftaran) {
        die(
            "Query pendaftaran siswa error: "
            . mysqli_error($conn)
        );
    }
}

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Pendaftaran Saya - Sistem Informasi Ekstrakurikuler</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    rel="stylesheet"
>

<style>

/* =====================================================
   GLOBAL
===================================================== */

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f3f7fa;
    color: #172033;
    font-family:
        "Segoe UI",
        Arial,
        sans-serif;
}

/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 255px;
    height: 100vh;
    background:
        linear-gradient(
            180deg,
            #075985 0%,
            #0f766e 100%
        );
    color: white;
    padding: 22px 15px;
    z-index: 1000;
    box-shadow:
        8px 0 25px
        rgba(15,23,42,.08);
}

.brand {
    padding: 8px 8px 23px;
    border-bottom:
        1px solid
        rgba(255,255,255,.15);
    text-align: center;
}

.brand-icon {
    width: 60px;
    height: 60px;
    margin: auto;
    border-radius: 17px;
    display: flex;
    align-items: center;
    justify-content: center;
    background:
        rgba(255,255,255,.14);
    font-size: 27px;
}

.brand h4 {
    margin: 11px 0 3px;
    font-weight: 700;
}

.brand small {
    color: rgba(255,255,255,.65);
}

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
    background:
        rgba(255,255,255,.12);
    transform:
        translateX(2px);
}

.sidebar a.active {
    color: white;
    background:
        rgba(255,255,255,.18);
    box-shadow:
        inset 3px 0 0 white;
}

/* =====================================================
   LOGOUT
===================================================== */

.logout {
    position: absolute;
    left: 15px;
    right: 15px;
    bottom: 18px;
}

.logout a {
    background:
        rgba(220,38,38,.85);
    color: white;
}

.logout a:hover {
    background: #dc2626;
}

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
    box-shadow:
        0 5px 22px
        rgba(15,23,42,.06);
    margin-bottom: 22px;
}

.topbar-title h5 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
}

.topbar-title small {
    color: #64748b;
}

.user-area {
    display: flex;
    align-items: center;
    gap: 11px;
}

.user-text {
    text-align: right;
}

.user-text strong {
    display: block;
    font-size: 14px;
}

.user-text small {
    color: #64748b;
}

.avatar {
    width: 44px;
    height: 44px;
    border-radius: 13px;
    background: #e0f2fe;
    color: #0369a1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
}

/* =====================================================
   PAGE HEADER
===================================================== */

.page-header {
    position: relative;
    overflow: hidden;
    background:
        linear-gradient(
            135deg,
            #0284c7,
            #0f766e
        );
    color: white;
    border-radius: 20px;
    padding: 27px 29px;
    margin-bottom: 22px;
    box-shadow:
        0 10px 28px
        rgba(14,116,144,.15);
}

.page-header::after {
    content: "";
    position: absolute;
    width: 210px;
    height: 210px;
    border-radius: 50%;
    background:
        rgba(255,255,255,.07);
    right: -65px;
    top: -105px;
}

.page-header-content {
    position: relative;
    z-index: 2;
}

.page-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 12px;
    margin-bottom: 13px;
    border-radius: 20px;
    background:
        rgba(255,255,255,.12);
    border:
        1px solid
        rgba(255,255,255,.15);
    font-size: 12px;
}

.page-header h2 {
    margin: 0 0 7px;
    font-size: 27px;
    font-weight: 700;
}

.page-header p {
    margin: 0;
    color: rgba(255,255,255,.82);
}

/* =====================================================
   STAT CARD
===================================================== */

.stat-card {
    background: white;
    border-radius: 17px;
    padding: 20px;
    border: 1px solid #edf2f5;
    box-shadow:
        0 5px 22px
        rgba(15,23,42,.055);
    height: 100%;
}

.stat-icon {
    width: 45px;
    height: 45px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin-bottom: 13px;
}

.icon-blue {
    background: #e0f2fe;
    color: #0284c7;
}

.icon-yellow {
    background: #fef3c7;
    color: #b45309;
}

.icon-green {
    background: #dcfce7;
    color: #15803d;
}

.icon-red {
    background: #fee2e2;
    color: #dc2626;
}

.stat-number {
    font-size: 26px;
    font-weight: 750;
}

.stat-label {
    color: #64748b;
    font-size: 13px;
}

/* =====================================================
   SECTION CARD
===================================================== */

.section-card {
    background: white;
    border-radius: 18px;
    border: 1px solid #edf2f5;
    box-shadow:
        0 5px 22px
        rgba(15,23,42,.055);
    overflow: hidden;
}

.section-header {
    padding: 18px 20px;
    border-bottom:
        1px solid #eef2f5;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.section-header h5 {
    margin: 0 0 3px;
    font-size: 16px;
    font-weight: 700;
}

.section-header small {
    color: #64748b;
}

/* =====================================================
   TABLE
===================================================== */

.table-wrapper {
    overflow-x: auto;
}

.table {
    margin: 0;
}

.table thead th {
    background: #f8fafc;
    color: #475569;
    font-size: 12px;
    font-weight: 700;
    border-bottom:
        1px solid #e2e8f0;
    padding: 14px 16px;
    white-space: nowrap;
}

.table tbody td {
    padding: 14px 16px;
    font-size: 13px;
    vertical-align: middle;
    border-bottom:
        1px solid #f1f5f9;
}

.table tbody tr:last-child td {
    border-bottom: none;
}

.table tbody tr:hover {
    background: #f8fafc;
}

/* =====================================================
   STATUS
===================================================== */

.status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.status-menunggu {
    background: #fef3c7;
    color: #b45309;
}

.status-diterima {
    background: #dcfce7;
    color: #15803d;
}

.status-ditolak {
    background: #fee2e2;
    color: #b91c1c;
}

.status-lain {
    background: #e2e8f0;
    color: #475569;
}

/* =====================================================
   INFO
===================================================== */

.info-box {
    border-radius: 16px;
    border: 1px solid #bfdbfe;
    background: #eff6ff;
    padding: 17px 19px;
    color: #475569;
    font-size: 13px;
}

.info-box strong {
    color: #1e3a8a;
}

/* =====================================================
   EMPTY
===================================================== */

.empty-state {
    padding: 45px 20px;
    text-align: center;
    color: #94a3b8;
}

.empty-state i {
    display: block;
    font-size: 42px;
    margin-bottom: 12px;
}

.empty-state strong {
    display: block;
    color: #475569;
}

/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width: 992px) {

    .sidebar {
        width: 220px;
    }

    .content {
        margin-left: 220px;
        padding: 20px;
    }
}

@media(max-width: 768px) {

    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        min-height: auto;
    }

    .logout {
        position: static;
        margin-top: 20px;
    }

    .content {
        margin-left: 0;
        padding: 15px;
    }

    .topbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 13px;
    }

    .user-text {
        text-align: left;
    }

    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
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

        <div class="brand-icon">
            <i class="bi bi-mortarboard-fill"></i>
        </div>

        <h4>Ekstrakurikuler</h4>

        <small>Portal Siswa</small>

    </div>

    <div class="menu-title">
        Menu Utama
    </div>

    <a href="dashboard_user.php">
        <i class="bi bi-grid-1x2-fill"></i>
        <span>Dashboard</span>
    </a>

    <a href="daftar_ekskul.php">
        <i class="bi bi-stars"></i>
        <span>Daftar Ekskul</span>
    </a>

    <a href="pendaftaran_saya.php" class="active">
        <i class="bi bi-person-check-fill"></i>
        <span>Pendaftaran Saya</span>
    </a>

    <a href="jadwal_saya.php">
        <i class="bi bi-calendar-event-fill"></i>
        <span>Jadwal Saya</span>
    </a>

    <a href="absensi_saya.php">
        <i class="bi bi-calendar-check-fill"></i>
        <span>Absensi Saya</span>
    </a>

    <a href="profil.php">
        <i class="bi bi-person-fill"></i>
        <span>Profil</span>
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

    <div class="topbar">

        <div class="topbar-title">

            <h5>Pendaftaran Saya</h5>

            <small>
                Sistem Informasi Ekstrakurikuler
            </small>

        </div>

        <div class="user-area">

            <div class="user-text">

                <strong>
                    <?= htmlspecialchars($namaTampilan); ?>
                </strong>

                <small>Siswa</small>

            </div>

            <div class="avatar">
                <?= htmlspecialchars($inisial); ?>
            </div>

        </div>

    </div>

    <!-- =====================================================
         PAGE HEADER
    ===================================================== -->

    <div class="page-header">

        <div class="page-header-content">

            <div class="page-badge">

                <i class="bi bi-person-check-fill"></i>

                Pendaftaran Saya

            </div>

            <h2>
                Riwayat Pendaftaran Ekstrakurikuler
            </h2>

            <p>
                Lihat status seluruh pendaftaran ekstrakurikuler
                yang kamu ajukan.
            </p>

        </div>

    </div>

    <!-- =====================================================
         STATISTIK
    ===================================================== -->

    <div class="row g-3 mb-4">

        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-icon icon-blue">
                    <i class="bi bi-person-plus-fill"></i>
                </div>

                <div class="stat-number">
                    <?= $totalPendaftaran; ?>
                </div>

                <div class="stat-label">
                    Total Pendaftaran
                </div>

            </div>

        </div>

        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-icon icon-yellow">
                    <i class="bi bi-hourglass-split"></i>
                </div>

                <div class="stat-number">
                    <?= $totalMenunggu; ?>
                </div>

                <div class="stat-label">
                    Menunggu
                </div>

            </div>

        </div>

        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-icon icon-green">
                    <i class="bi bi-check-circle-fill"></i>
                </div>

                <div class="stat-number">
                    <?= $totalDiterima; ?>
                </div>

                <div class="stat-label">
                    Diterima
                </div>

            </div>

        </div>

        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-icon icon-red">
                    <i class="bi bi-x-circle-fill"></i>
                </div>

                <div class="stat-number">
                    <?= $totalDitolak; ?>
                </div>

                <div class="stat-label">
                    Ditolak
                </div>

            </div>

        </div>

    </div>

    <!-- =====================================================
         DATA PENDAFTARAN
    ===================================================== -->

    <div class="section-card mb-4">

        <div class="section-header">

            <div>

                <h5>
                    Daftar Pendaftaran
                </h5>

                <small>
                    Status pendaftaran ekstrakurikuler kamu
                </small>

            </div>

            <span class="badge text-bg-light">
                <?= $totalPendaftaran; ?> Pendaftaran
            </span>

        </div>

        <div class="table-wrapper">

            <?php if (
                $dataPendaftaran &&
                mysqli_num_rows($dataPendaftaran) > 0
            ): ?>

                <table class="table">

                    <thead>

                        <tr>

                            <th width="60">
                                No
                            </th>

                            <th>
                                Ekstrakurikuler
                            </th>

                            <th>
                                Pembina
                            </th>

                            <th>
                                Status
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php $no = 1; ?>

                    <?php while (
                        $p = mysqli_fetch_assoc($dataPendaftaran)
                    ): ?>

                        <?php

                        $status = strtolower(
                            trim(
                                $p['status'] ?? ''
                            )
                        );

                        if ($status === 'menunggu') {
                            $statusClass = 'status-menunggu';
                            $statusIcon = 'bi-hourglass-split';
                            $statusText = 'Menunggu';
                        } elseif ($status === 'diterima') {
                            $statusClass = 'status-diterima';
                            $statusIcon = 'bi-check-circle-fill';
                            $statusText = 'Diterima';
                        } elseif ($status === 'ditolak') {
                            $statusClass = 'status-ditolak';
                            $statusIcon = 'bi-x-circle-fill';
                            $statusText = 'Ditolak';
                        } else {
                            $statusClass = 'status-lain';
                            $statusIcon = 'bi-question-circle-fill';
                            $statusText = ucfirst($status);
                        }

                        ?>

                        <tr>

                            <td>
                                <?= $no++; ?>
                            </td>

                            <td>

                                <strong>
                                    <?= htmlspecialchars(
                                        $p['nama_ekskul'] ?? '-'
                                    ); ?>
                                </strong>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    $p['pembina'] ?? '-'
                                ); ?>

                            </td>

                            <td>

                                <span
                                    class="status <?= $statusClass; ?>"
                                >

                                    <i
                                        class="bi <?= $statusIcon; ?>"
                                    ></i>

                                    <?= htmlspecialchars(
                                        $statusText
                                    ); ?>

                                </span>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <div class="empty-state">

                    <i class="bi bi-inbox"></i>

                    <strong>
                        Belum Ada Pendaftaran
                    </strong>

                    <div class="mt-2">
                        Kamu belum mendaftar
                        ekstrakurikuler apa pun.
                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>

    <!-- =====================================================
         INFORMASI
    ===================================================== -->

    <div class="info-box">

        <i class="bi bi-info-circle-fill me-2"></i>

        <strong>Informasi Pendaftaran</strong>

        <div class="mt-2">

            <strong>Menunggu</strong>
            berarti pendaftaran masih menunggu
            persetujuan admin.

        </div>

        <div>

            <strong>Diterima</strong>
            berarti kamu sudah resmi terdaftar
            sebagai anggota ekstrakurikuler.

        </div>

        <div>

            <strong>Ditolak</strong>
            berarti pendaftaran belum disetujui admin.

        </div>

    </div>

</div>

</body>

</html>
