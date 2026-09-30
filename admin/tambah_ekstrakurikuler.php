<?php

require_once __DIR__ . "/../config/config.php";

/* =====================================================
   PROSES SIMPAN DATA
===================================================== */

if (isset($_POST['simpan'])) {

    $nama_ekskul = mysqli_real_escape_string(
        $conn,
        $_POST['nama_ekskul']
    );

    $deskripsi = mysqli_real_escape_string(
        $conn,
        $_POST['deskripsi']
    );

    $pembina = mysqli_real_escape_string(
        $conn,
        $_POST['pembina']
    );

    $jadwal = mysqli_real_escape_string(
        $conn,
        $_POST['jadwal']
    );

    $lokasi = mysqli_real_escape_string(
        $conn,
        $_POST['lokasi']
    );


    $sql = "INSERT INTO ekstrakurikuler
            (
                nama_ekskul,
                deskripsi,
                pembina,
                jadwal,
                lokasi
            )
            VALUES
            (
                '$nama_ekskul',
                '$deskripsi',
                '$pembina',
                '$jadwal',
                '$lokasi'
            )";


    $query = mysqli_query($conn, $sql);


    if ($query) {

        echo "<script>

            alert('Data ekstrakurikuler berhasil ditambahkan!');

            window.location='ekstrakurikuler.php';

        </script>";

        exit;

    } else {

        $error = mysqli_error($conn);

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

<title>Tambah Ekstrakurikuler - Admin</title>


<!-- BOOTSTRAP -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- FONT AWESOME -->

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
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

    background: #f5f7fb;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    color: #1e293b;

}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {

    position: fixed;

    top: 0;
    left: 0;

    width: 250px;

    height: 100vh;

    background: #1e40af;

    color: white;

    z-index: 1000;

    box-shadow:
        3px 0 15px rgba(0,0,0,0.08);

}


/* =====================================================
   LOGO
===================================================== */

.logo {

    height: 75px;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 10px;

    font-size: 21px;

    font-weight: bold;

    border-bottom:
        1px solid rgba(255,255,255,0.15);

}

.logo i {

    font-size: 24px;

}


/* =====================================================
   MENU
===================================================== */

.menu {

    padding: 20px 12px;

}


.menu-title {

    color:
        rgba(255,255,255,0.55);

    font-size: 11px;

    font-weight: bold;

    text-transform: uppercase;

    padding:
        5px 15px 10px;

    letter-spacing: 0.8px;

}


.menu a {

    display: flex;

    align-items: center;

    gap: 12px;

    color: white;

    padding:
        13px 15px;

    margin-bottom: 5px;

    border-radius: 8px;

    text-decoration: none;

    font-size: 14px;

    transition:
        all 0.2s ease;

}


.menu a:hover {

    background: #2563eb;

    transform:
        translateX(2px);

}


.menu a.active {

    background: #2563eb;

    font-weight: bold;

    box-shadow:
        0 4px 10px
        rgba(0,0,0,0.15);

}


.menu i {

    width: 22px;

    text-align: center;

    font-size: 16px;

}


/* =====================================================
   LOGOUT
===================================================== */

.logout {

    position: absolute;

    bottom: 20px;

    left: 12px;

    right: 12px;

}


.logout a {

    display: flex;

    justify-content: center;

    align-items: center;

    gap: 8px;

    background: #dc2626;

    color: white;

    padding: 12px;

    border-radius: 8px;

    text-decoration: none;

    font-size: 14px;

}


.logout a:hover {

    background: #b91c1c;

}


/* =====================================================
   CONTENT
===================================================== */

.content {

    margin-left: 250px;

    min-height: 100vh;

    padding: 30px;

}


/* =====================================================
   HEADER
===================================================== */

.page-header {

    display: flex;

    justify-content:
        space-between;

    align-items:
        center;

    margin-bottom: 25px;

}


.page-title h2 {

    margin: 0;

    font-size: 28px;

    font-weight: 700;

    color: #0f172a;

}


.page-title p {

    margin:
        6px 0 0;

    color: #64748b;

    font-size: 14px;

}


/* =====================================================
   ADMIN
===================================================== */

.admin-profile {

    display: flex;

    align-items: center;

    gap: 12px;

}


.admin-text {

    text-align: right;

}


.admin-text strong {

    display: block;

    font-size: 14px;

}


.admin-text span {

    font-size: 12px;

    color: #64748b;

}


.admin-icon {

    width: 45px;

    height: 45px;

    border-radius: 50%;

    background: #2563eb;

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

}


/* =====================================================
   CARD
===================================================== */

.form-card {

    background: white;

    border:
        1px solid #e2e8f0;

    border-radius: 12px;

    box-shadow:
        0 4px 12px
        rgba(15,23,42,0.05);

    overflow: hidden;

}


.form-header {

    padding: 18px 20px;

    background: white;

    border-bottom:
        1px solid #e2e8f0;

}


.form-header h5 {

    margin: 0;

    font-size: 17px;

    font-weight: 700;

}


.form-body {

    padding: 25px;

}


/* =====================================================
   FORM
===================================================== */

.form-label {

    font-size: 14px;

    font-weight: 600;

    color: #334155;

}


.form-control {

    border:
        1px solid #cbd5e1;

    border-radius: 7px;

    padding: 10px 12px;

}


.form-control:focus {

    border-color: #2563eb;

    box-shadow:
        0 0 0 3px
        rgba(37,99,235,0.12);

}


/* =====================================================
   BUTTON
===================================================== */

.btn-simpan {

    background: #16a34a;

    color: white;

    border: none;

    padding:
        10px 18px;

    border-radius: 7px;

    font-size: 14px;

    font-weight: 600;

}


.btn-simpan:hover {

    background: #15803d;

    color: white;

}


.btn-kembali {

    background: #e2e8f0;

    color: #334155;

    padding:
        10px 18px;

    border-radius: 7px;

    text-decoration: none;

    font-size: 14px;

    font-weight: 600;

}


.btn-kembali:hover {

    background: #cbd5e1;

    color: #1e293b;

}


/* =====================================================
   ERROR
===================================================== */

.error-box {

    background: #fee2e2;

    color: #b91c1c;

    border:
        1px solid #fecaca;

    border-radius: 7px;

    padding: 12px 15px;

    margin-bottom: 20px;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 992px) {

    .sidebar {

        width: 220px;

    }

    .content {

        margin-left: 220px;

        padding: 22px;

    }

}


@media (max-width: 768px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

    }

    .logout {

        position: relative;

        left: 0;

        right: 0;

        bottom: 0;

        padding:
            10px 12px 20px;

    }

    .content {

        margin-left: 0;

        padding: 20px;

    }

    .page-header {

        display: block;

    }

    .admin-profile {

        margin-top: 15px;

    }

    .admin-text {

        text-align: left;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar">


    <!-- LOGO -->

    <div class="logo">

        <i class="fa fa-graduation-cap"></i>

        <span>Admin Ekskul</span>

    </div>


    <!-- MENU -->

    <div class="menu">


        <div class="menu-title">

            Menu Utama

        </div>


        <!-- DASHBOARD -->

        <a href="dashboard_admin.php">

            <i class="fa fa-home"></i>

            <span>Dashboard</span>

        </a>


        <!-- EKSKUL AKTIF -->

        <a
            href="ekstrakurikuler.php"
            class="active"
        >

            <i class="fa fa-book"></i>

            <span>Ekstrakurikuler</span>

        </a>


        <!-- SISWA -->

        <a href="data_siswa.php">

            <i class="fa fa-users"></i>

            <span>Data Siswa</span>

        </a>


        <!-- DAFTAR -->

        <a href="pendaftaran.php">

            <i class="fa fa-user-plus"></i>

            <span>Daftar</span>

        </a>


        <!-- JADWAL -->

        <a href="jadwal.php">

            <i class="fa fa-calendar"></i>

            <span>Jadwal</span>

        </a>


        <!-- ABSENSI -->

        <a href="absensi.php">

            <i class="fa fa-check"></i>

            <span>Absensi</span>

        </a>


        <!-- LAPORAN -->

        <a href="laporan.php">

            <i class="fa fa-chart-bar"></i>

            <span>Laporan</span>

        </a>


    </div>


    <!-- LOGOUT -->

    <div class="logout">

        <a href="../logout.php">

            <i class="fa fa-sign-out"></i>

            <span>Logout</span>

        </a>

    </div>


</div>


<!-- =====================================================
     CONTENT
===================================================== -->

<div class="content">


    <!-- HEADER -->

    <div class="page-header">


        <div class="page-title">

            <h2>

                Tambah Ekstrakurikuler

            </h2>

            <p>

                Tambahkan data ekstrakurikuler baru

            </p>

        </div>


        <div class="admin-profile">


            <div class="admin-text">

                <strong>
                    Administrator
                </strong>

                <span>
                    Panel Admin
                </span>

            </div>


            <div class="admin-icon">

                <i class="fa fa-user"></i>

            </div>


        </div>


    </div>


    <!-- FORM CARD -->

    <div class="form-card">


        <!-- HEADER CARD -->

        <div class="form-header">

            <h5>

                <i
                    class="fa fa-plus-circle me-2 text-primary"
                ></i>

                Form Tambah Ekstrakurikuler

            </h5>

        </div>


        <!-- BODY -->

        <div class="form-body">


            <?php if (isset($error)) { ?>

                <div class="error-box">

                    <i class="fa fa-circle-exclamation me-2"></i>

                    Gagal menambahkan data:

                    <?= htmlspecialchars($error); ?>

                </div>

            <?php } ?>


            <form
                method="POST"
                action=""
            >


                <!-- NAMA -->

                <div class="mb-3">

                    <label class="form-label">

                        Nama Ekstrakurikuler

                    </label>

                    <input
                        type="text"
                        name="nama_ekskul"
                        class="form-control"
                        placeholder="Masukkan nama ekstrakurikuler"
                        required
                    >

                </div>


                <!-- DESKRIPSI -->

                <div class="mb-3">

                    <label class="form-label">

                        Deskripsi

                    </label>

                    <textarea
                        name="deskripsi"
                        class="form-control"
                        rows="4"
                        placeholder="Masukkan deskripsi ekstrakurikuler"
                        required
                    ></textarea>

                </div>


                <!-- PEMBINA -->

                <div class="mb-3">

                    <label class="form-label">

                        Pembina

                    </label>

                    <input
                        type="text"
                        name="pembina"
                        class="form-control"
                        placeholder="Masukkan nama pembina"
                        required
                    >

                </div>


                <!-- JADWAL -->

                <div class="mb-3">

                    <label class="form-label">

                        Jadwal

                    </label>

                    <input
                        type="text"
                        name="jadwal"
                        class="form-control"
                        placeholder="Contoh: Senin, 15:00 - 17:00"
                        required
                    >

                </div>


                <!-- LOKASI -->

                <div class="mb-4">

                    <label class="form-label">

                        Lokasi

                    </label>

                    <input
                        type="text"
                        name="lokasi"
                        class="form-control"
                        placeholder="Masukkan lokasi kegiatan"
                        required
                    >

                </div>


                <!-- BUTTON -->

                <div>


                    <button
                        type="submit"
                        name="simpan"
                        class="btn-simpan"
                    >

                        <i class="fa fa-save me-1"></i>

                        Simpan

                    </button>


                    <a
                        href="ekstrakurikuler.php"
                        class="btn-kembali ms-1"
                    >

                        <i class="fa fa-arrow-left me-1"></i>

                        Kembali

                    </a>


                </div>


            </form>


        </div>


    </div>


</div>


<!-- BOOTSTRAP JS -->

<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>