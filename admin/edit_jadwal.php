<?php

require_once __DIR__ . "/../config/config.php";

if (!$conn) {
    die("Koneksi database gagal");
}


/* =====================================================
   CEK ID JADWAL
===================================================== */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    header("Location: jadwal.php");
    exit;

}

$id = (int) $_GET['id'];


/* =====================================================
   PROSES UPDATE JADWAL
===================================================== */

if (isset($_POST['update'])) {

    $ekskul_id   = (int) $_POST['ekskul_id'];
    $hari        = mysqli_real_escape_string(
        $conn,
        $_POST['hari']
    );

    $jam_mulai   = mysqli_real_escape_string(
        $conn,
        $_POST['jam_mulai']
    );

    $jam_selesai = mysqli_real_escape_string(
        $conn,
        $_POST['jam_selesai']
    );

    $lokasi      = mysqli_real_escape_string(
        $conn,
        $_POST['lokasi']
    );


    /* ================================
       VALIDASI
    ================================= */

    if ($ekskul_id <= 0) {

        $error = "Ekstrakurikuler wajib dipilih.";

    } elseif (empty($hari)) {

        $error = "Hari wajib dipilih.";

    } elseif (empty($jam_mulai)) {

        $error = "Jam mulai wajib diisi.";

    } elseif (empty($jam_selesai)) {

        $error = "Jam selesai wajib diisi.";

    } elseif (empty($lokasi)) {

        $error = "Lokasi wajib diisi.";

    } elseif ($jam_selesai <= $jam_mulai) {

        $error = "Jam selesai harus lebih besar dari jam mulai.";

    } else {


        /* ================================
           UPDATE DATABASE
        ================================= */

        $queryUpdate = mysqli_query(
            $conn,

            "UPDATE jadwal SET

                ekskul_id = '$ekskul_id',
                hari = '$hari',
                jam_mulai = '$jam_mulai',
                jam_selesai = '$jam_selesai',
                lokasi = '$lokasi'

             WHERE id = '$id'"
        );


        if ($queryUpdate) {

            header("Location: jadwal.php");
            exit;

        } else {

            $error =
                "Gagal mengubah jadwal: "
                . mysqli_error($conn);

        }

    }

}


/* =====================================================
   AMBIL DATA JADWAL
===================================================== */

$queryJadwal = mysqli_query(
    $conn,

    "SELECT *
     FROM jadwal
     WHERE id = '$id'
     LIMIT 1"
);


if (!$queryJadwal) {

    die(
        "Query jadwal error: "
        . mysqli_error($conn)
    );

}


if (mysqli_num_rows($queryJadwal) == 0) {

    die("Data jadwal tidak ditemukan.");

}


$jadwal = mysqli_fetch_assoc($queryJadwal);


/* =====================================================
   DATA EKSTRAKURIKULER
===================================================== */

$dataEkskul = mysqli_query(
    $conn,

    "SELECT id, nama_ekskul
     FROM ekstrakurikuler
     ORDER BY nama_ekskul ASC"
);


if (!$dataEkskul) {

    die(
        "Query ekstrakurikuler error: "
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

<title>Edit Jadwal Ekstrakurikuler</title>


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

    transition: 0.2s;

}


.menu a:hover {

    background: #2563eb;

}


.menu a.active {

    background: #2563eb;

    font-weight: bold;

}


.menu i {

    width: 22px;

    text-align: center;

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

    margin-bottom: 25px;

}


.page-title h2 {

    margin: 0;

    font-size: 28px;

    font-weight: bold;

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

.dashboard-card {

    background: white;

    border:
        1px solid #e2e8f0;

    border-radius: 12px;

    box-shadow:
        0 4px 12px rgba(15,23,42,0.05);

    overflow: hidden;

}


.dashboard-card-header {

    padding:
        18px 20px;

    border-bottom:
        1px solid #e2e8f0;

}


.dashboard-card-header h5 {

    margin: 0;

    font-size: 17px;

    font-weight: bold;

}


.dashboard-card-body {

    padding: 25px;

}


/* =====================================================
   FORM
===================================================== */

.form-label {

    font-weight: 600;

    color: #334155;

}


.form-control,
.form-select {

    border-radius: 8px;

    padding: 11px 13px;

    border:
        1px solid #cbd5e1;

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

.btn {

    border-radius: 8px;

    padding:
        10px 18px;

}


.btn-primary {

    background: #2563eb;

    border-color: #2563eb;

}


.btn-primary:hover {

    background: #1d4ed8;

}


/* =====================================================
   RESPONSIVE
===================================================== */

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


        <a href="pendaftaran.php">

            <i class="fa fa-user-plus"></i>

            <span>Daftar</span>

        </a>


        <a
            href="jadwal.php"
            class="active"
        >

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

                Edit Jadwal

            </h2>


            <p>

                Ubah informasi jadwal ekstrakurikuler

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


    <!-- ERROR -->

    <?php if (isset($error)) { ?>

        <div
            class="alert alert-danger"
            role="alert"
        >

            <i class="fa fa-circle-exclamation me-2"></i>

            <?= htmlspecialchars($error); ?>

        </div>

    <?php } ?>


    <!-- FORM EDIT -->

    <div class="dashboard-card">


        <div class="dashboard-card-header">

            <h5>

                <i
                    class="fa fa-calendar-pen text-primary me-2"
                ></i>

                Form Edit Jadwal

            </h5>

        </div>


        <div class="dashboard-card-body">


            <form method="POST">


                <div class="row">


                    <!-- EKSTRAKURIKULER -->

                    <div class="col-md-6 mb-3">


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
                                $e =
                                mysqli_fetch_assoc($dataEkskul)
                            ) {

                            ?>


                                <option
                                    value="<?= $e['id']; ?>"
                                    <?= (
                                        $e['id']
                                        ==
                                        $jadwal['ekskul_id']
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= htmlspecialchars(
                                        $e['nama_ekskul']
                                    ); ?>

                                </option>


                            <?php

                            }

                            ?>


                        </select>

                    </div>


                    <!-- HARI -->

                    <div class="col-md-6 mb-3">


                        <label class="form-label">

                            Hari

                        </label>


                        <select
                            name="hari"
                            class="form-select"
                            required
                        >


                            <option value="">

                                -- Pilih Hari --

                            </option>


                            <option
                                value="Senin"
                                <?= $jadwal['hari'] == 'Senin'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Senin
                            </option>


                            <option
                                value="Selasa"
                                <?= $jadwal['hari'] == 'Selasa'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Selasa
                            </option>


                            <option
                                value="Rabu"
                                <?= $jadwal['hari'] == 'Rabu'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Rabu
                            </option>


                            <option
                                value="Kamis"
                                <?= $jadwal['hari'] == 'Kamis'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Kamis
                            </option>


                            <option
                                value="Jumat"
                                <?= $jadwal['hari'] == 'Jumat'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Jumat
                            </option>


                            <option
                                value="Sabtu"
                                <?= $jadwal['hari'] == 'Sabtu'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Sabtu
                            </option>


                        </select>

                    </div>


                    <!-- JAM MULAI -->

                    <div class="col-md-6 mb-3">


                        <label class="form-label">

                            Jam Mulai

                        </label>


                        <input
                            type="time"
                            name="jam_mulai"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $jadwal['jam_mulai']
                            ); ?>"
                            required
                        >

                    </div>


                    <!-- JAM SELESAI -->

                    <div class="col-md-6 mb-3">


                        <label class="form-label">

                            Jam Selesai

                        </label>


                        <input
                            type="time"
                            name="jam_selesai"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $jadwal['jam_selesai']
                            ); ?>"
                            required
                        >

                    </div>


                    <!-- LOKASI -->

                    <div class="col-md-12 mb-4">


                        <label class="form-label">

                            Lokasi

                        </label>


                        <input
                            type="text"
                            name="lokasi"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $jadwal['lokasi']
                            ); ?>"
                            placeholder="Contoh: Lapangan Sekolah"
                            required
                        >

                    </div>


                </div>


                <!-- BUTTON -->

                <div
                    class="d-flex gap-2"
                >


                    <button
                        type="submit"
                        name="update"
                        class="btn btn-primary"
                    >

                        <i class="fa fa-save me-1"></i>

                        Simpan Perubahan

                    </button>


                    <a
                        href="jadwal.php"
                        class="btn btn-secondary"
                    >

                        <i class="fa fa-arrow-left me-1"></i>

                        Kembali

                    </a>


                </div>


            </form>


        </div>

    </div>


</div>


</body>

</html>