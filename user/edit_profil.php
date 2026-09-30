<?php

session_start();

include "../config/config.php";


/* =====================================================
   CEK LOGIN
===================================================== */

if (!isset($_SESSION['id'])) {

    header("Location: ../login.php");
    exit;

}


if ($_SESSION['role'] !== 'siswa') {

    header("Location: ../login.php");
    exit;

}


$user_id = $_SESSION['id'];


/* =====================================================
   AMBIL DATA USER + SISWA
===================================================== */

$query = mysqli_query($conn, "

    SELECT

        u.id AS user_id,
        u.nama,
        u.email,
        u.role,

        s.id AS siswa_id,
        s.nama_siswa,
        s.kelas,
        s.jenis_kelamin,
        s.alamat

    FROM user u

    LEFT JOIN siswa s
        ON s.user_id = u.id

    WHERE u.id = '$user_id'

    LIMIT 1

");


if (!$query) {

    die(
        "Error mengambil data: "
        . mysqli_error($conn)
    );

}


$data = mysqli_fetch_assoc($query);


if (!$data) {

    die("Data pengguna tidak ditemukan.");

}


/* =====================================================
   PROSES UPDATE
===================================================== */

if (isset($_POST['simpan'])) {


    /* ================================
       DATA DARI FORM
    ================================= */

    $nama = mysqli_real_escape_string(
        $conn,
        $_POST['nama']
    );


    $email = mysqli_real_escape_string(
        $conn,
        $_POST['email']
    );


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


    /* ================================
       CEK EMAIL
    ================================= */

    $cekEmail = mysqli_query($conn, "

        SELECT id

        FROM user

        WHERE email = '$email'

        AND id != '$user_id'

        LIMIT 1

    ");


    if (mysqli_num_rows($cekEmail) > 0) {

        $error = "Email tersebut sudah digunakan akun lain.";

    } else {


        /* ================================
           UPDATE USER
        ================================= */

        $updateUser = mysqli_query($conn, "

            UPDATE user

            SET
                nama = '$nama',
                email = '$email'

            WHERE id = '$user_id'

        ");


        if (!$updateUser) {

            $error =
                "Gagal mengubah data akun: "
                . mysqli_error($conn);

        } else {


            /* ================================
               UPDATE SISWA
            ================================= */

            if ($data['siswa_id'] !== null) {


                $siswa_id = $data['siswa_id'];


                $updateSiswa = mysqli_query($conn, "

                    UPDATE siswa

                    SET
                        nama_siswa = '$nama_siswa',
                        kelas = '$kelas',
                        jenis_kelamin = '$jenis_kelamin',
                        alamat = '$alamat'

                    WHERE id = '$siswa_id'

                ");


                if (!$updateSiswa) {

                    $error =
                        "Akun berhasil diubah, "
                        . "tetapi data siswa gagal diubah: "
                        . mysqli_error($conn);

                }

            } else {


                /* ================================
                   JIKA DATA SISWA BELUM ADA
                ================================= */

                $buatSiswa = mysqli_query($conn, "

                    INSERT INTO siswa
                    (
                        nama_siswa,
                        kelas,
                        jenis_kelamin,
                        alamat,
                        user_id
                    )

                    VALUES
                    (
                        '$nama_siswa',
                        '$kelas',
                        '$jenis_kelamin',
                        '$alamat',
                        '$user_id'
                    )

                ");


                if (!$buatSiswa) {

                    $error =
                        "Akun berhasil diubah, "
                        . "tetapi data siswa gagal dibuat: "
                        . mysqli_error($conn);

                }

            }


            /* ================================
               JIKA BERHASIL
            ================================= */

            if (!isset($error)) {


                /* Update session nama */

                $_SESSION['nama'] = $nama;


                echo "

                <script>

                    alert('Profil berhasil diperbarui.');

                    window.location='profil.php';

                </script>

                ";

                exit;

            }

        }

    }

}

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1">

<title>Edit Profil</title>


<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">


<link
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
rel="stylesheet">


<style>

/* ==========================================
   GLOBAL
========================================== */

* {

    box-sizing: border-box;

}


body {

    margin: 0;

    background: #f5f7fb;

    font-family:
    "Segoe UI",
    Arial,
    sans-serif;

    color: #1e293b;

}


/* ==========================================
   SIDEBAR
========================================== */

.sidebar {

    position: fixed;

    left: 0;

    top: 0;

    width: 250px;

    height: 100vh;

    background:
    linear-gradient(
        180deg,
        #4f46e5,
        #4338ca
    );

    padding: 25px 15px;

    color: white;

}


.logo {

    text-align: center;

    margin-bottom: 35px;

}


.logo-icon {

    width: 65px;

    height: 65px;

    margin: auto;

    border-radius: 18px;

    background:
    rgba(255,255,255,.18);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 28px;

}


.logo h4 {

    margin-top: 12px;

    margin-bottom: 3px;

    font-weight: 700;

}


.logo small {

    opacity: .8;

}


.menu-title {

    font-size: 11px;

    text-transform: uppercase;

    opacity: .6;

    margin:
    20px 12px 8px;

    letter-spacing: 1px;

}


.sidebar a {

    display: flex;

    align-items: center;

    gap: 13px;

    padding: 13px 15px;

    margin-bottom: 5px;

    border-radius: 12px;

    color:
    rgba(255,255,255,.85);

    text-decoration: none;

    transition: .2s;

}


.sidebar a:hover,
.sidebar a.active {

    background:
    rgba(255,255,255,.17);

    color: white;

}


.sidebar a i {

    width: 20px;

    text-align: center;

}


.logout {

    position: absolute;

    bottom: 20px;

    left: 15px;

    right: 15px;

}


/* ==========================================
   CONTENT
========================================== */

.content {

    margin-left: 250px;

    padding: 30px;

}


/* ==========================================
   TOPBAR
========================================== */

.topbar {

    background: white;

    border-radius: 18px;

    padding: 18px 25px;

    display: flex;

    justify-content:
    space-between;

    align-items: center;

    margin-bottom: 25px;

    box-shadow:
    0 5px 20px
    rgba(15,23,42,.06);

}


.topbar h5 {

    margin: 0;

    font-weight: 700;

}


.topbar small {

    color: #64748b;

}


.user-info {

    display: flex;

    align-items: center;

    gap: 12px;

}


.avatar-small {

    width: 45px;

    height: 45px;

    border-radius: 50%;

    background: #eef2ff;

    color: #4f46e5;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 19px;

}


/* ==========================================
   PAGE HEADER
========================================== */

.page-header {

    background:
    linear-gradient(
        135deg,
        #4f46e5,
        #7c3aed
    );

    color: white;

    border-radius: 20px;

    padding: 28px;

    margin-bottom: 25px;

}


.page-header h2 {

    margin: 0 0 7px;

    font-weight: 700;

}


.page-header p {

    margin: 0;

    opacity: .85;

}


/* ==========================================
   FORM CARD
========================================== */

.form-card {

    max-width: 900px;

    margin: auto;

    background: white;

    border-radius: 22px;

    padding: 35px;

    box-shadow:
    0 8px 30px
    rgba(15,23,42,.08);

}


.section-title {

    font-size: 18px;

    font-weight: 700;

    margin-bottom: 20px;

    color: #1e293b;

}


.form-label {

    font-weight: 600;

    color: #475569;

}


.form-control,
.form-select {

    border-radius: 11px;

    min-height: 48px;

    border:
    1px solid #dbe2ea;

}


.form-control:focus,
.form-select:focus {

    border-color: #4f46e5;

    box-shadow:
    0 0 0 .2rem
    rgba(79,70,229,.12);

}


textarea.form-control {

    min-height: 110px;

}


/* ==========================================
   BUTTON
========================================== */

.btn-save {

    background: #4f46e5;

    border: none;

    color: white;

    border-radius: 10px;

    padding: 11px 22px;

    font-weight: 600;

}


.btn-save:hover {

    background: #4338ca;

    color: white;

}


.btn-cancel {

    background: #e2e8f0;

    color: #334155;

    border: none;

    border-radius: 10px;

    padding: 11px 22px;

    text-decoration: none;

    font-weight: 600;

}


.btn-cancel:hover {

    background: #cbd5e1;

    color: #334155;

}


/* ==========================================
   RESPONSIVE
========================================== */

@media(max-width:700px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

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

        gap: 15px;

    }

}

</style>

</head>


<body>


<!-- ==========================================
     SIDEBAR
========================================== -->

<div class="sidebar">


    <div class="logo">


        <div class="logo-icon">

            <i
            class="fa-solid fa-graduation-cap">
            </i>

        </div>


        <h4>

            EkskulKu

        </h4>


        <small>

            Portal Siswa

        </small>


    </div>


    <div class="menu-title">

        Menu Utama

    </div>


    <a href="dashboard_user.php">

        <i
        class="fa-solid fa-house">
        </i>

        Dashboard

    </a>


    <a href="daftar_ekskul.php">

        <i
        class="fa-solid fa-list">
        </i>

        Daftar Ekskul

    </a>


    <a href="pendaftaran_saya.php">

        <i
        class="fa-solid fa-user-plus">
        </i>

        Pendaftaran Saya

    </a>


    <a href="jadwal_saya.php">

        <i
        class="fa-solid fa-calendar-days">
        </i>

        Jadwal Saya

    </a>


    <a href="absensi_saya.php">

        <i
        class="fa-solid fa-calendar-check">
        </i>

        Absensi Saya

    </a>


    <a
    href="profil.php"
    class="active">

        <i
        class="fa-solid fa-user">
        </i>

        Profil

    </a>


    <div class="logout">

        <a href="../logout.php">

            <i
            class="fa-solid fa-right-from-bracket">
            </i>

            Logout

        </a>

    </div>


</div>


<!-- ==========================================
     CONTENT
========================================== -->

<div class="content">


    <!-- TOPBAR -->

    <div class="topbar">


        <div>

            <h5>

                Edit Profil

            </h5>


            <small>

                Ubah informasi data diri kamu

            </small>

        </div>


        <div class="user-info">


            <div>

                <strong>

                    <?= htmlspecialchars(
                        $data['nama']
                    ); ?>

                </strong>


                <br>


                <small>

                    Siswa

                </small>

            </div>


            <div class="avatar-small">

                <i
                class="fa-solid fa-user">
                </i>

            </div>


        </div>


    </div>


    <!-- PAGE HEADER -->

    <div class="page-header">


        <h2>

            <i
            class="fa-solid fa-pen-to-square me-2">
            </i>

            Edit Profil

        </h2>


        <p>

            Perbarui informasi akun dan biodata siswa kamu.

        </p>


    </div>


    <!-- FORM -->

    <div class="form-card">


        <?php

        if (isset($error)) {

        ?>

            <div class="alert alert-danger">

                <i
                class="fa-solid fa-circle-exclamation me-2">
                </i>

                <?= htmlspecialchars($error); ?>

            </div>

        <?php

        }

        ?>


        <form method="POST">


            <!-- ======================================
                 INFORMASI AKUN
            ======================================= -->

            <div class="section-title">

                <i
                class="fa-solid fa-user-lock text-primary me-2">
                </i>

                Informasi Akun

            </div>


            <div class="row g-4">


                <!-- NAMA -->

                <div class="col-md-6">


                    <label class="form-label">

                        Nama Akun

                    </label>


                    <input
                    type="text"
                    name="nama"
                    class="form-control"
                    value="<?= htmlspecialchars(
                        $data['nama']
                    ); ?>"
                    required>


                </div>


                <!-- EMAIL -->

                <div class="col-md-6">


                    <label class="form-label">

                        Email

                    </label>


                    <input
                    type="email"
                    name="email"
                    class="form-control"
                    value="<?= htmlspecialchars(
                        $data['email']
                    ); ?>"
                    required>


                </div>


            </div>


            <hr class="my-4">


            <!-- ======================================
                 BIODATA SISWA
            ======================================= -->

            <div class="section-title">

                <i
                class="fa-solid fa-id-card text-primary me-2">
                </i>

                Biodata Siswa

            </div>


            <div class="row g-4">


                <!-- NAMA SISWA -->

                <div class="col-md-6">


                    <label class="form-label">

                        Nama Lengkap

                    </label>


                    <input
                    type="text"
                    name="nama_siswa"
                    class="form-control"
                    value="<?= htmlspecialchars(
                        $data['nama_siswa'] ?? ''
                    ); ?>"
                    required>


                </div>


                <!-- KELAS -->

                <div class="col-md-6">


                    <label class="form-label">

                        Kelas

                    </label>


                    <input
                    type="text"
                    name="kelas"
                    class="form-control"
                    placeholder="Contoh: XII RPL 2"
                    value="<?= htmlspecialchars(
                        $data['kelas'] ?? ''
                    ); ?>"
                    required>


                </div>


                <!-- JENIS KELAMIN -->

                <div class="col-md-6">


                    <label class="form-label">

                        Jenis Kelamin

                    </label>


                    <select
                    name="jenis_kelamin"
                    class="form-select"
                    required>


                        <option value="">

                            -- Pilih Jenis Kelamin --

                        </option>


                        <option
                        value="Laki-laki"
                        <?= (
                            ($data['jenis_kelamin'] ?? '')
                            == 'Laki-laki'
                        )
                        ? 'selected'
                        : ''
                        ?>>

                            Laki-laki

                        </option>


                        <option
                        value="Perempuan"
                        <?= (
                            ($data['jenis_kelamin'] ?? '')
                            == 'Perempuan'
                        )
                        ? 'selected'
                        : ''
                        ?>>

                            Perempuan

                        </option>


                    </select>


                </div>


                <!-- ALAMAT -->

                <div class="col-12">


                    <label class="form-label">

                        Alamat

                    </label>


                    <textarea
                    name="alamat"
                    class="form-control"
                    placeholder="Masukkan alamat lengkap"
                    required><?= htmlspecialchars(
                        $data['alamat'] ?? ''
                    ); ?></textarea>


                </div>


            </div>


            <!-- ======================================
                 BUTTON
            ======================================= -->

            <div
            class="d-flex gap-2 justify-content-end mt-4">


                <a
                href="profil.php"
                class="btn-cancel">

                    <i
                    class="fa-solid fa-xmark me-1">
                    </i>

                    Batal

                </a>


                <button
                type="submit"
                name="simpan"
                class="btn-save">

                    <i
                    class="fa-solid fa-floppy-disk me-1">
                    </i>

                    Simpan Perubahan

                </button>


            </div>


        </form>


    </div>


</div>


</body>

</html>