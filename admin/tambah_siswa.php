<?php

require_once __DIR__ . "/../config/config.php";

/* =====================================================
   CEK KONEKSI
===================================================== */

if (!isset($conn) || !$conn) {
    die("Database tidak terhubung");
}


/* =====================================================
   PROSES SIMPAN DATA SISWA
===================================================== */

if (isset($_POST['simpan'])) {

    $nama_siswa = mysqli_real_escape_string(
        $conn,
        $_POST['nama_siswa']
    );

    $kelas = mysqli_real_escape_string(
        $conn,
        $_POST['kelas']
    );

    $jenis_kelamin = mysqli_real_escape_string(
        $conn,
        $_POST['jenis_kelamin']
    );

    $alamat = mysqli_real_escape_string(
        $conn,
        $_POST['alamat']
    );


    $query = mysqli_query(
        $conn,
        "
        INSERT INTO siswa
        (
            nama_siswa,
            kelas,
            jenis_kelamin,
            alamat
        )
        VALUES
        (
            '$nama_siswa',
            '$kelas',
            '$jenis_kelamin',
            '$alamat'
        )
        "
    );


    if ($query) {

        echo "
        <script>
            alert('Data siswa berhasil ditambahkan!');
            window.location='data_siswa.php';
        </script>
        ";

        exit;

    } else {

        die(
            "Gagal menambahkan data siswa: "
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

<title>Tambah Data Siswa - Admin Ekskul</title>


<!-- =====================================================
     BOOTSTRAP
===================================================== -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- =====================================================
     FONT AWESOME
===================================================== -->

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

    transition: 0.2s;

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

    justify-content: space-between;

    align-items: center;

    margin-bottom: 28px;

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
   ADMIN PROFILE
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

    color: #1e293b;

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

    font-size: 17px;

}


/* =====================================================
   FORM CARD
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


.form-card-header {

    padding:
        18px 22px;

    background: white;

    border-bottom:
        1px solid #e2e8f0;

}


.form-card-header h5 {

    margin: 0;

    font-size: 17px;

    font-weight: 700;

    color: #1e293b;

}


.form-card-header p {

    margin:
        5px 0 0;

    font-size: 13px;

    color: #64748b;

}


.form-card-body {

    padding: 25px;

}


/* =====================================================
   FORM
===================================================== */

.form-label {

    font-weight: 600;

    color: #334155;

    font-size: 14px;

}


.form-control,
.form-select {

    border:
        1px solid #cbd5e1;

    border-radius: 7px;

    padding:
        10px 12px;

    font-size: 14px;

}


.form-control:focus,
.form-select:focus {

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

    border: none;

    padding:
        10px 18px;

    border-radius: 7px;

    font-size: 14px;

    font-weight: 600;

    text-decoration: none;

    display: inline-block;

}


.btn-kembali:hover {

    background: #cbd5e1;

    color: #1e293b;

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


        <!-- EKSTRAKURIKULER -->

        <a href="ekstrakurikuler.php">

            <i class="fa fa-book"></i>

            <span>Ekstrakurikuler</span>

        </a>


        <!-- DATA SISWA AKTIF -->

        <a
            href="data_siswa.php"
            class="active"
        >

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
                Tambah Data Siswa
            </h2>

            <p>
                Tambahkan data siswa baru ke dalam sistem
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


    <!-- =================================================
         FORM TAMBAH SISWA
    ================================================== -->

    <div class="form-card">


        <!-- HEADER FORM -->

        <div class="form-card-header">

            <h5>

                <i
                    class="fa fa-user-plus me-2 text-primary"
                ></i>

                Form Tambah Siswa

            </h5>

            <p>
                Isi data siswa dengan lengkap dan benar.
            </p>

        </div>


        <!-- BODY FORM -->

        <div class="form-card-body">


            <form
                method="POST"
                action=""
            >


                <!-- NAMA -->

                <div class="mb-3">

                    <label
                        class="form-label"
                        for="nama_siswa"
                    >

                        Nama Siswa

                    </label>

                    <input
                        type="text"
                        name="nama_siswa"
                        id="nama_siswa"
                        class="form-control"
                        placeholder="Masukkan nama siswa"
                        required
                    >

                </div>


                <!-- KELAS -->

                <div class="mb-3">

                    <label
                        class="form-label"
                        for="kelas"
                    >

                        Kelas

                    </label>

                    <input
                        type="text"
                        name="kelas"
                        id="kelas"
                        class="form-control"
                        placeholder="Contoh: XI RPL 1"
                        required
                    >

                </div>


                <!-- JENIS KELAMIN -->

                <div class="mb-3">

                    <label
                        class="form-label"
                        for="jenis_kelamin"
                    >

                        Jenis Kelamin

                    </label>

                    <select
                        name="jenis_kelamin"
                        id="jenis_kelamin"
                        class="form-select"
                        required
                    >

                        <option value="">
                            -- Pilih Jenis Kelamin --
                        </option>

                        <option value="Laki-laki">
                            Laki-laki
                        </option>

                        <option value="Perempuan">
                            Perempuan
                        </option>

                    </select>

                </div>


                <!-- ALAMAT -->

                <div class="mb-4">

                    <label
                        class="form-label"
                        for="alamat"
                    >

                        Alamat

                    </label>

                    <textarea
                        name="alamat"
                        id="alamat"
                        class="form-control"
                        rows="4"
                        placeholder="Masukkan alamat siswa"
                        required
                    ></textarea>

                </div>


                <!-- BUTTON -->

                <div>

                    <button
                        type="submit"
                        name="simpan"
                        class="btn-simpan"
                    >

                        <i
                            class="fa fa-save me-1"
                        ></i>

                        Simpan

                    </button>


                    <a
                        href="data_siswa.php"
                        class="btn-kembali ms-1"
                    >

                        <i
                            class="fa fa-arrow-left me-1"
                        ></i>

                        Kembali

                    </a>

                </div>


            </form>


        </div>


    </div>


</div>


<!-- =====================================================
     BOOTSTRAP JS
===================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>