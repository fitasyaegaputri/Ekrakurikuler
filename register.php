<?php

include "config/config.php";

$error = "";

if (isset($_POST['register'])) {

    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $role = "siswa";

    /* ==========================================
       VALIDASI
    ========================================== */

    if ($nama === "" || $email === "" || $password === "") {

        $error = "Semua data wajib diisi.";

    } else {

        /* ==========================================
           CEK EMAIL
        ========================================== */

        $stmtCek = mysqli_prepare(
            $conn,
            "SELECT id
             FROM `user`
             WHERE email = ?
             LIMIT 1"
        );

        if (!$stmtCek) {

            $error =
                "Gagal mengecek email: "
                . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmtCek,
                "s",
                $email
            );

            mysqli_stmt_execute($stmtCek);

            $hasilCek =
                mysqli_stmt_get_result($stmtCek);

            if (mysqli_num_rows($hasilCek) > 0) {

                $error = "Email sudah terdaftar.";

            } else {

                /* ==========================================
                   PASSWORD
                ========================================== */

                $password_hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                /* ==========================================
                   MULAI TRANSAKSI
                ========================================== */

                mysqli_begin_transaction($conn);

                try {

                    /* ==========================================
                       SIMPAN USER
                    ========================================== */

                    $stmtUser = mysqli_prepare(
                        $conn,
                        "INSERT INTO `user`
                        (nama, email, password, role)
                        VALUES (?, ?, ?, ?)"
                    );

                    if (!$stmtUser) {
                        throw new Exception(
                            "Query user gagal: "
                            . mysqli_error($conn)
                        );
                    }

                    mysqli_stmt_bind_param(
                        $stmtUser,
                        "ssss",
                        $nama,
                        $email,
                        $password_hash,
                        $role
                    );

                    if (!mysqli_stmt_execute($stmtUser)) {

                        throw new Exception(
                            "Akun user gagal dibuat: "
                            . mysqli_stmt_error($stmtUser)
                        );
                    }

                    /* ==========================================
                       AMBIL ID USER BARU
                    ========================================== */

                    $user_id = mysqli_insert_id($conn);

                    if ($user_id <= 0) {

                        throw new Exception(
                            "ID user baru gagal diperoleh."
                        );
                    }

                    /* ==========================================
                       LANGSUNG BUAT DATA SISWA
                    ========================================== */

                    $stmtSiswa = mysqli_prepare(
                        $conn,
                        "INSERT INTO siswa
                        (
                            user_id,
                            nama_siswa
                        )
                        VALUES
                        (
                            ?,
                            ?
                        )"
                    );

                    if (!$stmtSiswa) {

                        throw new Exception(
                            "Query siswa gagal: "
                            . mysqli_error($conn)
                        );
                    }

                    mysqli_stmt_bind_param(
                        $stmtSiswa,
                        "is",
                        $user_id,
                        $nama
                    );

                    if (!mysqli_stmt_execute($stmtSiswa)) {

                        throw new Exception(
                            "Data siswa gagal dibuat: "
                            . mysqli_stmt_error($stmtSiswa)
                        );
                    }

                    /* ==========================================
                       BERHASIL → SIMPAN PERMANEN
                    ========================================== */

                    mysqli_commit($conn);

                    echo "
                    <script>
                        alert(
                            'Akun berhasil dibuat dan langsung terdaftar sebagai siswa.'
                        );

                        window.location.href = 'login.php';
                    </script>
                    ";

                    exit();

                } catch (Exception $e) {

                    /* ==========================================
                       GAGAL → BATalkan SEMUANYA
                    ========================================== */

                    mysqli_rollback($conn);

                    $error = $e->getMessage();
                }
            }

            mysqli_stmt_close($stmtCek);
        }
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

<title>Register Siswa - EkskulKu</title>


<!-- Bootstrap -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- Bootstrap Icons -->

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

    min-height: 100vh;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    background:
        linear-gradient(
            135deg,
            #075985 0%,
            #0f766e 100%
        );

    position: relative;

    overflow-x: hidden;

}


/* =====================================================
   BACKGROUND CIRCLE
===================================================== */

body::before {

    content: "";

    position: fixed;

    width: 420px;

    height: 420px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.07);

    top: -180px;

    left: -130px;

}


body::after {

    content: "";

    position: fixed;

    width: 500px;

    height: 500px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.06);

    right: -200px;

    bottom: -230px;

}


/* =====================================================
   CONTAINER
===================================================== */

.register-container {

    min-height: 100vh;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 35px 20px;

    position: relative;

    z-index: 2;

}


/* =====================================================
   CARD
===================================================== */

.register-card {

    width: 460px;

    max-width: 100%;

    background:
        rgba(255,255,255,.98);

    border-radius: 25px;

    padding: 34px;

    box-shadow:
        0 25px 60px
        rgba(0,0,0,.20);

    border:
        1px solid
        rgba(255,255,255,.5);

}


/* =====================================================
   LOGO
===================================================== */

.logo {

    width: 76px;

    height: 76px;

    margin: 0 auto;

    border-radius: 20px;

    background:
        linear-gradient(
            135deg,
            #0284c7,
            #0f766e
        );

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 33px;

    box-shadow:
        0 10px 22px
        rgba(2,132,199,.25);

}


/* =====================================================
   TITLE
===================================================== */

.register-title {

    color: #172033;

    font-weight: 750;

    font-size: 25px;

    margin-bottom: 7px;

}


.register-subtitle {

    color: #64748b;

    font-size: 13px;

    line-height: 1.6;

}


/* =====================================================
   FORM LABEL
===================================================== */

.form-label {

    color: #334155;

    font-size: 13px;

    font-weight: 600;

    margin-bottom: 7px;

}


/* =====================================================
   INPUT
===================================================== */

.input-group {

    box-shadow:
        0 3px 12px
        rgba(15,23,42,.04);

    border-radius: 12px;

}


.input-group-text {

    width: 48px;

    min-width: 48px;

    justify-content: center;

    background:
        #e0f2fe;

    color:
        #0284c7;

    border:
        1px solid #dbeafe;

    border-right: none;

    border-radius:
        12px 0 0 12px;

}


.form-control {

    height: 50px;

    border:
        1px solid #dbeafe;

    border-left: none;

    border-radius:
        0 12px 12px 0;

    color: #172033;

    font-size: 13px;

    box-shadow: none !important;

}


.form-control::placeholder {

    color: #94a3b8;

}


.form-control:focus {

    border-color:
        #38bdf8;

    box-shadow:
        none !important;

}


/* =====================================================
   ERROR
===================================================== */

.alert-danger {

    border: none;

    border-radius: 12px;

    background:
        #fef2f2;

    color:
        #b91c1c;

    font-size: 13px;

}


/* =====================================================
   REGISTER BUTTON
===================================================== */

.btn-register {

    height: 51px;

    border: none;

    border-radius: 13px;

    background:
        linear-gradient(
            135deg,
            #0284c7,
            #0f766e
        );

    color: white;

    font-weight: 650;

    font-size: 14px;

    box-shadow:
        0 8px 20px
        rgba(2,132,199,.20);

    transition: .2s;

}


.btn-register:hover {

    color: white;

    transform:
        translateY(-2px);

    box-shadow:
        0 12px 25px
        rgba(2,132,199,.28);

}


/* =====================================================
   LOGIN LINK
===================================================== */

.login-text {

    color: #64748b;

    font-size: 13px;

}


.login-text a {

    color: #0284c7;

    text-decoration: none;

    font-weight: 700;

}


.login-text a:hover {

    color: #0369a1;

}


/* =====================================================
   FOOTER
===================================================== */

.register-footer {

    text-align: center;

    margin-top: 20px;

    padding-top: 18px;

    border-top:
        1px solid #eef2f5;

    color: #94a3b8;

    font-size: 11px;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width: 500px) {

    .register-container {

        padding: 20px 14px;

    }


    .register-card {

        padding: 26px 21px;

        border-radius: 20px;

    }


    .register-title {

        font-size: 22px;

    }

}

</style>

</head>


<body>


<div class="register-container">


    <div class="register-card">


        <!-- LOGO -->

        <div class="logo mb-3">

            <i class="bi bi-person-plus-fill"></i>

        </div>


        <!-- TITLE -->

        <div class="text-center mb-4">

            <h3 class="register-title">

                Buat Akun Siswa

            </h3>


            <p class="register-subtitle mb-0">

                Daftarkan akun untuk mengakses
                Sistem Informasi Ekstrakurikuler

            </p>

        </div>


        <!-- ERROR -->

        <?php if ($error != "") { ?>

            <div class="alert alert-danger mb-4">

                <i class="bi bi-exclamation-circle me-2"></i>

                <?= htmlspecialchars($error); ?>

            </div>

        <?php } ?>


        <!-- FORM -->

        <form method="POST">


            <!-- NAMA -->

            <div class="mb-3">

                <label class="form-label">

                    Nama Siswa

                </label>


                <div class="input-group">

                    <span class="input-group-text">

                        <i class="bi bi-person"></i>

                    </span>


                    <input
                        type="text"
                        name="nama"
                        class="form-control"
                        placeholder="Masukkan nama lengkap"
                        required
                    >

                </div>

            </div>


            <!-- EMAIL -->

            <div class="mb-3">

                <label class="form-label">

                    Email

                </label>


                <div class="input-group">

                    <span class="input-group-text">

                        <i class="bi bi-envelope"></i>

                    </span>


                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        placeholder="Masukkan email"
                        required
                    >

                </div>

            </div>


            <!-- PASSWORD -->

            <div class="mb-4">

                <label class="form-label">

                    Password

                </label>


                <div class="input-group">

                    <span class="input-group-text">

                        <i class="bi bi-lock"></i>

                    </span>


                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        required
                    >

                </div>

            </div>


            <!-- BUTTON -->

            <button
                type="submit"
                name="register"
                class="btn btn-register w-100"
            >

                <i class="bi bi-person-plus me-2"></i>

                Buat Akun

            </button>


        </form>


        <!-- LOGIN -->

        <div class="text-center login-text mt-4">

            Sudah punya akun?

            <a href="login.php">

                Login di sini

            </a>

        </div>


        <!-- FOOTER -->

        <div class="register-footer">

            <i class="bi bi-mortarboard-fill me-1"></i>

            EkskulKu &nbsp;•&nbsp; Sistem Informasi Ekstrakurikuler

        </div>


    </div>


</div>


</body>

</html>