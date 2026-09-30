<?php

require_once __DIR__ . "/../config/config.php";

if (!$conn) {
    die("Koneksi database gagal");
}

/* =====================================================
   PROSES SIMPAN PENDAFTARAN
===================================================== */

if (isset($_POST['simpan'])) {

    $siswa_id       = (int) $_POST['siswa_id'];
    $ekskul_id      = (int) $_POST['ekskul_id'];
    $tanggal_daftar = mysqli_real_escape_string(
        $conn,
        $_POST['tanggal_daftar']
    );
    $status         = mysqli_real_escape_string(
        $conn,
        $_POST['status']
    );

    /* Validasi */

    if ($siswa_id <= 0) {
        die("Siswa wajib dipilih.");
    }

    if ($ekskul_id <= 0) {
        die("Ekstrakurikuler wajib dipilih.");
    }

    /* =================================================
       CEK APAKAH SISWA SUDAH MENDAFTAR EKSKUL TERSEBUT
    ================================================= */

    $cek = mysqli_query(
        $conn,
        "SELECT id
         FROM pendaftaran
         WHERE siswa_id = $siswa_id
         AND ekskul_id = $ekskul_id
         LIMIT 1"
    );

    if (!$cek) {
        die(
            "Query pengecekan gagal: "
            . mysqli_error($conn)
        );
    }

    if (mysqli_num_rows($cek) > 0) {

        echo "<script>
                alert('Siswa tersebut sudah terdaftar pada ekstrakurikuler ini.');
                history.back();
              </script>";

        exit;
    }


    /* =================================================
       SIMPAN PENDAFTARAN
    ================================================= */

    $query = mysqli_query(
        $conn,
        "INSERT INTO pendaftaran
        (
            siswa_id,
            ekskul_id,
            tanggal_daftar,
            status
        )
        VALUES
        (
            $siswa_id,
            $ekskul_id,
            '$tanggal_daftar',
            '$status'
        )"
    );


    if ($query) {

        echo "<script>
                alert('Pendaftaran berhasil ditambahkan!');
                window.location='pendaftaran.php';
              </script>";

        exit;

    } else {

        die(
            "Gagal menyimpan pendaftaran: "
            . mysqli_error($conn)
        );
    }
}


/* =====================================================
   AMBIL DATA SISWA
===================================================== */

$dataSiswa = mysqli_query(
    $conn,
    "SELECT
        id,
        nama_siswa,
        kelas
     FROM siswa
     ORDER BY nama_siswa ASC"
);

if (!$dataSiswa) {

    die(
        "Data siswa error: "
        . mysqli_error($conn)
    );
}


/* =====================================================
   AMBIL DATA EKSTRAKURIKULER
===================================================== */

$dataEkskul = mysqli_query(
    $conn,
    "SELECT
        id,
        nama_ekskul
     FROM ekstrakurikuler
     ORDER BY nama_ekskul ASC"
);

if (!$dataEkskul) {

    die(
        "Data ekstrakurikuler error: "
        . mysqli_error($conn)
    );
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

<title>Tambah Pendaftaran</title>


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

    transition: all 0.2s ease;
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

.form-header {

    padding: 18px 20px;

    border-bottom:
        1px solid #e2e8f0;

    background: white;
}

.form-header h5 {

    margin: 0;

    font-size: 17px;

    font-weight: 700;

    color: #1e293b;
}

.form-body {

    padding: 25px;
}


/* =====================================================
   FORM
===================================================== */

.form-label {

    font-weight: 600;

    font-size: 14px;

    color: #334155;
}

.form-control,
.form-select {

    border:
        1px solid #cbd5e1;

    border-radius: 7px;

    padding: 10px 12px;

    font-size: 14px;
}

.form-control:focus,
.form-select:focus {

    border-color: #2563eb;

    box-shadow:
        0 0 0 0.2rem
        rgba(37,99,235,0.15);
}


/* =====================================================
   BUTTON
===================================================== */

.btn-save {

    background: #16a34a;

    color: white;

    border: none;

    padding:
        10px 16px;

    border-radius: 7px;

    font-size: 14px;

    font-weight: 600;
}

.btn-save:hover {

    background: #15803d;

    color: white;
}

.btn-back {

    background: #64748b;

    color: white;

    padding:
        10px 16px;

    border-radius: 7px;

    text-decoration: none;

    font-size: 14px;

    font-weight: 600;
}

.btn-back:hover {

    background: #475569;

    color: white;
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
     SIDEBAR / NAVBAR YANG SAMA
===================================================== -->

<div class="sidebar">


    <div class="logo">

        <i class="fa fa-graduation-cap"></i>

        <span>Admin Ekskul</span>

    </div>


    <div class="menu">


        <div class="menu-title">
            Menu Utama
        </div>


        <a href="dashboard_admin.php">

            <i class="fa fa-home"></i>

            <span>Dashboard</span>

        </a>


        <a href="ekstrakurikuler.php">

            <i class="fa fa-book"></i>

            <span>Ekstrakurikuler</span>

        </a>


        <a href="data_siswa.php">

            <i class="fa fa-users"></i>

            <span>Data Siswa</span>

        </a>


        <!-- AKTIF -->

        <a
            href="pendaftaran.php"
            class="active"
        >

            <i class="fa fa-user-plus"></i>

            <span>Daftar</span>

        </a>


        <a href="jadwal.php">

            <i class="fa fa-calendar"></i>

            <span>Jadwal</span>

        </a>


        <a href="absensi.php">

            <i class="fa fa-check"></i>

            <span>Absensi</span>

        </a>


        <a href="laporan.php">

            <i class="fa fa-chart-bar"></i>

            <span>Laporan</span>

        </a>


    </div>


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
                Tambah Pendaftaran
            </h2>

            <p>
                Tambahkan siswa ke ekstrakurikuler
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
         FORM
    ================================================== -->

    <div class="form-card">


        <div class="form-header">

            <h5>

                <i
                    class="fa fa-user-plus me-2 text-primary"
                ></i>

                Form Pendaftaran

            </h5>

        </div>


        <div class="form-body">


            <form method="POST">


                <!-- SISWA -->

                <div class="mb-4">

                    <label class="form-label">

                        Nama Siswa

                    </label>


                    <select
                        name="siswa_id"
                        class="form-select"
                        required
                    >

                        <option value="">

                            -- Pilih Siswa --

                        </option>


                        <?php

                        while (
                            $siswa =
                            mysqli_fetch_assoc($dataSiswa)
                        ) {

                        ?>

                            <option
                                value="<?= $siswa['id']; ?>"
                            >

                                <?= htmlspecialchars(
                                    $siswa['nama_siswa']
                                ); ?>

                                -

                                <?= htmlspecialchars(
                                    $siswa['kelas']
                                ); ?>

                            </option>

                        <?php

                        }

                        ?>

                    </select>


                    <small class="text-muted">

                        Pilih siswa yang sudah terdaftar
                        pada data siswa.

                    </small>

                </div>


                <!-- EKSKUL -->

                <div class="mb-4">

                    <label class="form-label">

                        Ekstrakurikuler

                    </label>


                    <select
                        name="ekskul_id"
                        class="form-select"
                        required
                    >

                        <option value="">

                            -- Pilih Ekstrakurikuler --

                        </option>


                        <?php

                        while (
                            $ekskul =
                            mysqli_fetch_assoc($dataEkskul)
                        ) {

                        ?>

                            <option
                                value="<?= $ekskul['id']; ?>"
                            >

                                <?= htmlspecialchars(
                                    $ekskul['nama_ekskul']
                                ); ?>

                            </option>

                        <?php

                        }

                        ?>

                    </select>

                </div>


                <!-- TANGGAL -->

                <div class="mb-4">

                    <label class="form-label">

                        Tanggal Daftar

                    </label>


                    <input
                        type="date"
                        name="tanggal_daftar"
                        class="form-control"
                        value="<?= date('Y-m-d'); ?>"
                        required
                    >

                </div>


                <!-- STATUS -->

                <div class="mb-4">

                    <label class="form-label">

                        Status

                    </label>


                    <select
                        name="status"
                        class="form-select"
                        required
                    >

                        <option value="menunggu">

                            Menunggu

                        </option>

                        <option value="diterima">

                            Diterima

                        </option>

                        <option value="ditolak">

                            Ditolak

                        </option>

                    </select>

                </div>


                <!-- BUTTON -->

                <div class="mt-4">


                    <button
                        type="submit"
                        name="simpan"
                        class="btn btn-save"
                    >

                        <i class="fa fa-save me-1"></i>

                        Simpan Pendaftaran

                    </button>


                    <a
                        href="pendaftaran.php"
                        class="btn btn-back ms-2"
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