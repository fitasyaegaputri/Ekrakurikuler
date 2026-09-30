<?php
/* =========================================================
   LOGIN - SISTEM INFORMASI EKSTRAKURIKULER
   Optimized: Accessibility + Performance
========================================================= */

session_start();

require_once __DIR__ . "/config/config.php";

/* CEK KONEKSI */
if (!$conn) {
    die("Koneksi database gagal.");
}


/* =========================================================
   KALAU SUDAH LOGIN → REDIRECT
========================================================= */
if (isset($_SESSION['id']) && isset($_SESSION['role'])) {

    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: admin/dashboard_admin.php");
            exit();
        case 'siswa':
            header("Location: user/dashboard_user.php");
            exit();
        case 'pembina':
            header("Location: pembina/dashboard_pembina.php");
            exit();
    }
}


/* =========================================================
   PROSES LOGIN
========================================================= */
$error = "";

if (isset($_POST['login'])) {

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Email dan password wajib diisi.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";

    } else {

        $stmt = mysqli_prepare($conn,
            "SELECT id, nama, email, password, role
             FROM `user`
             WHERE email = ?
             LIMIT 1");

        if (!$stmt) {
            $error = "Query login gagal: " . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {

                $data = mysqli_fetch_assoc($result);

                if (password_verify($password, $data['password'])) {

                    session_regenerate_id(true);

                    $_SESSION['id']   = $data['id'];
                    $_SESSION['nama'] = $data['nama'];
                    $_SESSION['role'] = $data['role'];

                    if ($data['role'] === "admin") {
                        header("Location: admin/dashboard_admin.php");
                        exit();
                    } elseif ($data['role'] === "siswa") {
                        header("Location: user/dashboard_user.php");
                        exit();
                    } elseif ($data['role'] === "pembina") {
                        header("Location: pembina/dashboard_pembina.php");
                        exit();
                    } else {
                        $error = "Role pengguna tidak valid.";
                    }

                } else {
                    $error = "Password salah.";
                }

            } else {
                $error = "Email tidak ditemukan.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Login Sistem Informasi Ekstrakurikuler">

<title>Login - Ekstrakurikuler</title>

<!-- PERFORMANCE -->
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="dns-prefetch" href="https://cdn.jsdelivr.net">

<link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></noscript>

<link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"></noscript>

<style>
/* =====================================================
   SCROLLBAR HIDDEN
===================================================== */
::-webkit-scrollbar {
    width: 0;
    height: 0;
    display: none;
}

html {
    scrollbar-width: none;
    -ms-overflow-style: none;
}

body::-webkit-scrollbar {
    display: none;
}


/* =====================================================
   ACCESSIBILITY
===================================================== */
:focus-visible {
    outline: 2px solid #0284c7;
    outline-offset: 2px;
}

button:focus-visible,
a:focus-visible,
input:focus-visible,
select:focus-visible {
    outline: 2px solid #0284c7;
    outline-offset: 2px;
}

.skip-link {
    position: absolute;
    top: -50px;
    left: 6px;
    background: #0284c7;
    color: white;
    padding: 10px 18px;
    border-radius: 8px;
    text-decoration: none;
    z-index: 99999;
    font-weight: 600;
    transition: top .2s;
}

.skip-link:focus {
    top: 10px;
    color: white;
}


/* =====================================================
   GLOBAL
===================================================== */
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    font-family: "Segoe UI", Arial, sans-serif;
    background: linear-gradient(135deg, #e0f2fe 0%, #f0fdfa 50%, #f8fafc 100%);
    color: #172033;
}


/* =====================================================
   BACKGROUND DECORATION
===================================================== */
.background-circle {
    position: fixed;
    border-radius: 50%;
    z-index: 0;
    pointer-events: none;
}

.circle-one {
    width: 330px; height: 330px;
    background: rgba(14, 165, 233, .12);
    top: -120px; left: -100px;
}

.circle-two {
    width: 300px; height: 300px;
    background: rgba(20, 184, 166, .12);
    bottom: -120px; right: -80px;
}

.circle-three {
    width: 130px; height: 130px;
    background: rgba(56, 189, 248, .08);
    top: 25%; right: 15%;
}


/* =====================================================
   LOGIN CONTAINER
===================================================== */
.login-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 30px;
    position: relative;
    z-index: 1;
}


/* =====================================================
   LOGIN CARD
===================================================== */
.login-card {
    width: 100%;
    max-width: 930px;
    min-height: 560px;
    background: white;
    border-radius: 25px;
    overflow: hidden;
    display: grid;
    grid-template-columns: 43% 57%;
    box-shadow: 0 20px 60px rgba(15, 23, 42, .12);
    border: 1px solid rgba(255,255,255,.8);
}


/* =====================================================
   LEFT PANEL
===================================================== */
.left-panel {
    position: relative;
    overflow: hidden;
    padding: 45px;
    color: white;
    background: linear-gradient(145deg, #075985, #0f766e);
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.left-panel::before {
    content: "";
    position: absolute;
    width: 260px; height: 260px;
    border-radius: 50%;
    background: rgba(255,255,255,.07);
    top: -110px; right: -90px;
}

.left-panel::after {
    content: "";
    position: absolute;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
    bottom: -90px; left: -70px;
}

.left-content { position: relative; z-index: 2; }

.brand-logo {
    width: 72px; height: 72px;
    border-radius: 19px;
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.18);
    display: flex;
    align-items: center; justify-content: center;
    font-size: 32px;
    margin-bottom: 25px;
}

.left-panel h2 {
    font-size: 34px;
    font-weight: 750;
    margin-bottom: 12px;
    color: white;
}

.left-panel p {
    color: rgba(255,255,255,.85);
    line-height: 1.7;
    font-size: 14px;
    margin-bottom: 28px;
}

.feature {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
    font-size: 13px;
    color: rgba(255,255,255,.92);
}

.feature-icon {
    width: 35px; height: 35px;
    border-radius: 10px;
    background: rgba(255,255,255,.13);
    display: flex;
    align-items: center; justify-content: center;
}


/* =====================================================
   RIGHT PANEL
===================================================== */
.right-panel {
    padding: 45px 55px;
    display: flex;
    align-items: center;
}

.login-content {
    width: 100%;
    max-width: 430px;
    margin: auto;
}

.login-heading { margin-bottom: 28px; }

.login-heading h1 {
    font-size: 28px;
    font-weight: 750;
    margin-bottom: 7px;
    color: #172033;
}

.login-heading p {
    margin: 0;
    color: #475569;
    font-size: 14px;
}


/* =====================================================
   ERROR BOX
===================================================== */
.error-box {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
    border-radius: 12px;
    padding: 11px 13px;
    margin-bottom: 20px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 9px;
}


/* =====================================================
   FORM
===================================================== */
.form-label {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 8px;
}

.input-group { position: relative; }

.input-group-text {
    width: 47px;
    justify-content: center;
    background: #f8fafc;
    color: #0284c7;
    border: 1px solid #dbe4ea;
    border-right: none;
    border-radius: 12px 0 0 12px;
}

.form-control {
    height: 50px;
    border: 1px solid #dbe4ea;
    border-left: none;
    border-radius: 0 12px 12px 0;
    font-size: 14px;
    color: #172033;
    box-shadow: none !important;
}

.form-control::placeholder { color: #64748b; }

.form-control:focus {
    border-color: #38bdf8;
    background: white;
}

.input-group:focus-within .input-group-text {
    border-color: #38bdf8;
    background: #f0f9ff;
}


/* =====================================================
   PASSWORD TOGGLE
===================================================== */
.password-wrapper { position: relative; }

.password-wrapper .form-control { padding-right: 48px; }

.password-toggle {
    position: absolute;
    right: 13px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    color: #475569;
    cursor: pointer;
    z-index: 5;
    padding: 8px;
    border-radius: 8px;
    transition: .2s;
}

.password-toggle:hover {
    color: #0284c7;
    background: #f0f9ff;
}


/* =====================================================
   LOGIN BUTTON
===================================================== */
.btn-login {
    height: 51px;
    border: none;
    border-radius: 13px;
    background: linear-gradient(135deg, #0284c7, #0f766e);
    color: white;
    font-weight: 650;
    font-size: 14px;
    box-shadow: 0 8px 18px rgba(2, 132, 199, .20);
    transition: .2s;
}

.btn-login:hover {
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 11px 23px rgba(2, 132, 199, .27);
}

.btn-login:active { transform: translateY(0); }


/* =====================================================
   REGISTER LINK
===================================================== */
.register-text {
    text-align: center;
    margin-top: 25px;
    color: #475569;
    font-size: 13px;
}

.register-text a {
    color: #0284c7;
    font-weight: 700;
    text-decoration: none;
}

.register-text a:hover {
    color: #0369a1;
    text-decoration: underline;
}


/* =====================================================
   FOOTER
===================================================== */
.login-footer {
    text-align: center;
    color: #64748b;
    font-size: 11px;
    margin-top: 25px;
}


/* =====================================================
   RESPONSIVE
===================================================== */
@media(max-width: 850px) {

    .login-card {
        grid-template-columns: 1fr;
        max-width: 500px;
    }

    .left-panel { display: none; }

    .right-panel { padding: 40px 35px; }
}

@media(max-width: 480px) {

    .login-container { padding: 15px; }

    .login-card { border-radius: 20px; }

    .right-panel { padding: 35px 25px; }

    .login-heading h1 { font-size: 25px; }
}
</style>

</head>


<body>

<!-- SKIP LINK -->
<a href="#konten-utama" class="skip-link">Langsung ke konten</a>

<!-- BACKGROUND -->
<div class="background-circle circle-one" aria-hidden="true"></div>
<div class="background-circle circle-two" aria-hidden="true"></div>
<div class="background-circle circle-three" aria-hidden="true"></div>


<!-- MAIN LANDMARK -->
<main class="login-container" id="konten-utama">

    <div class="login-card">

        <!-- LEFT PANEL -->
        <div class="left-panel">

            <div class="left-content">

                <div class="brand-logo" aria-hidden="true">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>

                <h2>Ekstrakurikuler</h2>

                <p>
                    Sistem Informasi Ekstrakurikuler
                    untuk membantu siswa mengelola
                    kegiatan, pendaftaran, jadwal,
                    dan absensi dengan lebih mudah.
                </p>

                <div class="feature">
                    <div class="feature-icon" aria-hidden="true">
                        <i class="bi bi-stars"></i>
                    </div>
                    <span>Kelola kegiatan ekstrakurikuler</span>
                </div>

                <div class="feature">
                    <div class="feature-icon" aria-hidden="true">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <span>Pantau jadwal kegiatan</span>
                </div>

                <div class="feature">
                    <div class="feature-icon" aria-hidden="true">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <span>Pantau riwayat kehadiran</span>
                </div>

            </div>

        </div>


        <!-- RIGHT PANEL -->
        <div class="right-panel">

            <div class="login-content">

                <div class="login-heading">
                    <h1>Selamat Datang</h1>
                    <p>Silakan masuk ke akun kamu untuk melanjutkan.</p>
                </div>


                <!-- ERROR -->
                <?php if (!empty($error)): ?>
                <div class="error-box" role="alert">
                    <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($error); ?></span>
                </div>
                <?php endif; ?>


                <!-- FORM LOGIN -->
                <form method="POST" action="login.php" autocomplete="off">

                    <!-- Honeypot anti-autofill -->
                    <input type="text" name="fake_email" style="display:none;" tabindex="-1" autocomplete="off" aria-hidden="true">
                    <input type="password" name="fake_password" style="display:none;" tabindex="-1" autocomplete="off" aria-hidden="true">

                    <!-- EMAIL -->
                    <div class="mb-4">

                        <label class="form-label" for="email">Email</label>

                        <div class="input-group">

                            <span class="input-group-text" aria-hidden="true">
                                <i class="bi bi-envelope-fill"></i>
                            </span>

                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control"
                                placeholder="Masukkan email"
                                autocomplete="off"
                                autocorrect="off"
                                autocapitalize="off"
                                spellcheck="false"
                                required
                            >

                        </div>

                    </div>

                    <!-- PASSWORD -->
                    <div class="mb-4">

                        <label class="form-label" for="password">Password</label>

                        <div class="password-wrapper">

                            <div class="input-group">

                                <span class="input-group-text" aria-hidden="true">
                                    <i class="bi bi-lock-fill"></i>
                                </span>

                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    class="form-control"
                                    placeholder="Masukkan password"
                                    autocomplete="new-password"
                                    required
                                >

                            </div>

                            <button
                                type="button"
                                class="password-toggle"
                                onclick="togglePassword()"
                                aria-label="Tampilkan atau sembunyikan password"
                            >
                                <i class="bi bi-eye" id="passwordIcon" aria-hidden="true"></i>
                            </button>

                        </div>

                    </div>

                    <!-- LOGIN BUTTON -->
                    <button
                        type="submit"
                        name="login"
                        class="btn btn-login w-100"
                    >
                        <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>
                        Masuk ke Sistem
                    </button>

                </form>


                <!-- REGISTER -->
                <div class="register-text">
                    Belum punya akun?
                    <a href="register.php">Daftar sekarang</a>
                </div>

                <!-- FOOTER -->
                <div class="login-footer">
                    Sistem Informasi Ekstrakurikuler
                    <br>
                    Ekstrakurikuler
                </div>

            </div>

        </div>

    </div>

</main>


<script>
/* =====================================================
   SHOW / HIDE PASSWORD
===================================================== */
function togglePassword() {

    const password = document.getElementById("password");
    const icon     = document.getElementById("passwordIcon");

    if (password.type === "password") {
        password.type = "text";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
    } else {
        password.type = "password";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
    }
}
</script>

</body>
</html>