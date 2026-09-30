<?php
/* =========================================================
   PEMBINA - HALAMAN ADMIN
   Nama pembina otomatis dari data ekstrakurikuler
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

$pesanPembina = "";
$tipePembina  = "";


/* =========================================================
   DETEKSI TABEL pembina_ekskul
========================================================= */
$adaTabelRelasi = false;
$cekTabel = mysqli_query($conn, "SHOW TABLES LIKE 'pembina_ekskul'");
if ($cekTabel && mysqli_num_rows($cekTabel) > 0) {
    $adaTabelRelasi = true;
}


/* =========================================================
   TAMBAH PEMBINA
========================================================= */
if (isset($_POST['tambah_pembina'])) {

    $ekskul_id = (int)($_POST['ekskul_id'] ?? 0);
    $email     = trim($_POST['email_pembina'] ?? '');
    $password  = $_POST['password_pembina'] ?? '';

    $namaPembina = "";
    if ($ekskul_id > 0) {
        $stmtNama = mysqli_prepare($conn,
            "SELECT pembina FROM ekstrakurikuler WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtNama, "i", $ekskul_id);
        mysqli_stmt_execute($stmtNama);
        $resNama = mysqli_stmt_get_result($stmtNama);
        $rowNama = mysqli_fetch_assoc($resNama);
        mysqli_stmt_close($stmtNama);
        $namaPembina = trim($rowNama['pembina'] ?? '');
    }

    if ($ekskul_id <= 0 || $email === '' || $password === '' || $namaPembina === '') {
        $pesanPembina = "Semua data harus diisi.";
        $tipePembina  = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesanPembina = "Format email tidak valid.";
        $tipePembina  = "danger";
    } elseif (strlen($password) < 6) {
        $pesanPembina = "Password minimal 6 karakter.";
        $tipePembina  = "danger";
    } else {

        $cek = mysqli_prepare($conn, "SELECT id FROM `user` WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($cek, "s", $email);
        mysqli_stmt_execute($cek);
        $hasilCek = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($hasilCek) > 0) {
            $pesanPembina = "Email tersebut sudah digunakan.";
            $tipePembina  = "danger";
            mysqli_stmt_close($cek);
        } else {
            mysqli_stmt_close($cek);

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $simpanUser = mysqli_prepare($conn,
                "INSERT INTO `user` (nama, email, password, role) VALUES (?, ?, ?, 'pembina')");
            mysqli_stmt_bind_param($simpanUser, "sss", $namaPembina, $email, $passwordHash);

            if (mysqli_stmt_execute($simpanUser)) {
                $user_id = mysqli_insert_id($conn);
                mysqli_stmt_close($simpanUser);

                if ($adaTabelRelasi) {
                    $simpanRelasi = mysqli_prepare($conn,
                        "INSERT INTO pembina_ekskul (user_id, ekskul_id) VALUES (?, ?)");
                    mysqli_stmt_bind_param($simpanRelasi, "ii", $user_id, $ekskul_id);
                    mysqli_stmt_execute($simpanRelasi);
                    mysqli_stmt_close($simpanRelasi);
                }

                $pesanPembina = "Akun pembina <strong>" . htmlspecialchars($namaPembina) . "</strong> berhasil dibuat.";
                $tipePembina  = "success";
            } else {
                $pesanPembina = "Gagal membuat akun pembina: " . mysqli_error($conn);
                $tipePembina  = "danger";
                mysqli_stmt_close($simpanUser);
            }
        }
    }
}


/* =========================================================
   EDIT PEMBINA
========================================================= */
if (isset($_POST['edit_pembina'])) {

    $user_id  = (int)($_POST['user_id'] ?? 0);
    $nama     = trim($_POST['nama_pembina'] ?? '');
    $email    = trim($_POST['email_pembina'] ?? '');
    $password = $_POST['password_pembina'] ?? '';

    if ($user_id <= 0 || $nama === '' || $email === '') {
        $pesanPembina = "Data tidak lengkap.";
        $tipePembina  = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesanPembina = "Format email tidak valid.";
        $tipePembina  = "danger";
    } else {

        $cek = mysqli_prepare($conn,
            "SELECT id FROM `user` WHERE email = ? AND id != ? LIMIT 1");
        mysqli_stmt_bind_param($cek, "si", $email, $user_id);
        mysqli_stmt_execute($cek);
        $hasilCek = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($hasilCek) > 0) {
            $pesanPembina = "Email tersebut sudah digunakan pembina lain.";
            $tipePembina  = "danger";
            mysqli_stmt_close($cek);
        } else {
            mysqli_stmt_close($cek);

            if ($password !== '') {
                if (strlen($password) < 6) {
                    $pesanPembina = "Password minimal 6 karakter.";
                    $tipePembina  = "danger";
                    goto skip_edit_pembina;
                }

                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn,
                    "UPDATE `user` SET nama = ?, email = ?, password = ? WHERE id = ? AND role = 'pembina'");
                mysqli_stmt_bind_param($stmt, "sssi", $nama, $email, $passwordHash, $user_id);
            } else {
                $stmt = mysqli_prepare($conn,
                    "UPDATE `user` SET nama = ?, email = ? WHERE id = ? AND role = 'pembina'");
                mysqli_stmt_bind_param($stmt, "ssi", $nama, $email, $user_id);
            }

            if (mysqli_stmt_execute($stmt)) {
                $pesanPembina = "Data pembina berhasil diperbarui.";
                $tipePembina  = "success";
            } else {
                $pesanPembina = "Gagal memperbarui data: " . mysqli_error($conn);
                $tipePembina  = "danger";
            }
            mysqli_stmt_close($stmt);
        }

        skip_edit_pembina:
    }
}


/* =========================================================
   HAPUS HUBUNGAN
========================================================= */
if (isset($_POST['hapus_pembina'])) {

    $user_id   = (int)($_POST['user_id'] ?? 0);
    $ekskul_id = (int)($_POST['ekskul_id'] ?? 0);

    if ($adaTabelRelasi && $user_id > 0 && $ekskul_id > 0) {
        $hapus = mysqli_prepare($conn,
            "DELETE FROM pembina_ekskul WHERE user_id = ? AND ekskul_id = ?");
        mysqli_stmt_bind_param($hapus, "ii", $user_id, $ekskul_id);

        if (mysqli_stmt_execute($hapus)) {
            $pesanPembina = "Hubungan pembina berhasil dihapus.";
            $tipePembina  = "success";
        } else {
            $pesanPembina = "Gagal menghapus hubungan.";
            $tipePembina  = "danger";
        }
        mysqli_stmt_close($hapus);
    }
}


/* =========================================================
   HAPUS AKUN PEMBINA
========================================================= */
if (isset($_POST['hapus_akun_pembina'])) {

    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($user_id > 0) {

        if ($adaTabelRelasi) {
            $stmtRel = mysqli_prepare($conn, "DELETE FROM pembina_ekskul WHERE user_id = ?");
            mysqli_stmt_bind_param($stmtRel, "i", $user_id);
            mysqli_stmt_execute($stmtRel);
            mysqli_stmt_close($stmtRel);
        }

        $stmt = mysqli_prepare($conn, "DELETE FROM `user` WHERE id = ? AND role = 'pembina'");
        mysqli_stmt_bind_param($stmt, "i", $user_id);

        if (mysqli_stmt_execute($stmt)) {
            $pesanPembina = "Akun pembina berhasil dihapus.";
            $tipePembina  = "success";
        } else {
            $pesanPembina = "Gagal menghapus akun: " . mysqli_error($conn);
            $tipePembina  = "danger";
        }
        mysqli_stmt_close($stmt);
    }
}


/* =========================================================
   DATA EKSKUL
========================================================= */
$listEkskulPembina = [];
$qEks = mysqli_query($conn,
    "SELECT id, nama_ekskul, pembina FROM ekstrakurikuler
     WHERE pembina IS NOT NULL AND pembina != '' ORDER BY nama_ekskul ASC");

if ($qEks) {
    while ($r = mysqli_fetch_assoc($qEks)) {
        $listEkskulPembina[] = $r;
    }
}
$totalEkskulPembina = count($listEkskulPembina);


/* =========================================================
   PENCARIAN
========================================================= */
$cari = trim($_GET['cari'] ?? "");


/* =========================================================
   DATA PEMBINA
========================================================= */
$listPembina  = [];
$totalPembina = 0;

if ($adaTabelRelasi) {

    if ($cari !== "") {
        $kw = "%" . $cari . "%";
        $stmtP = mysqli_prepare($conn,
            "SELECT u.id AS user_id, u.nama, u.email,
                e.id AS ekskul_id, e.nama_ekskul
             FROM `user` u
             LEFT JOIN pembina_ekskul pe ON pe.user_id = u.id
             LEFT JOIN ekstrakurikuler e ON e.id = pe.ekskul_id
             WHERE u.role = 'pembina'
               AND (u.nama LIKE ? OR u.email LIKE ? OR e.nama_ekskul LIKE ?)
             ORDER BY u.nama ASC");
        if ($stmtP) {
            mysqli_stmt_bind_param($stmtP, "sss", $kw, $kw, $kw);
            mysqli_stmt_execute($stmtP);
            $resP = mysqli_stmt_get_result($stmtP);
            if ($resP) {
                while ($r = mysqli_fetch_assoc($resP)) $listPembina[] = $r;
            }
            mysqli_stmt_close($stmtP);
        }
    } else {
        $qP = mysqli_query($conn,
            "SELECT u.id AS user_id, u.nama, u.email,
                e.id AS ekskul_id, e.nama_ekskul
             FROM `user` u
             LEFT JOIN pembina_ekskul pe ON pe.user_id = u.id
             LEFT JOIN ekstrakurikuler e ON e.id = pe.ekskul_id
             WHERE u.role = 'pembina'
             ORDER BY u.nama ASC");
        if ($qP) {
            while ($r = mysqli_fetch_assoc($qP)) $listPembina[] = $r;
        }
    }

} else {
    $qP = mysqli_query($conn,
        "SELECT u.id AS user_id, u.nama, u.email,
            NULL AS ekskul_id, NULL AS nama_ekskul
         FROM `user` u
         WHERE u.role = 'pembina'
         ORDER BY u.nama ASC");
    if ($qP) {
        while ($r = mysqli_fetch_assoc($qP)) $listPembina[] = $r;
    }
}

$totalPembina = count($listPembina);

$pembinaUnik = [];
foreach ($listPembina as $p) {
    $pembinaUnik[$p['user_id']] = true;
}
$totalPembinaUnik = count($pembinaUnik);
?>


<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Pembina - Sistem Informasi Ekstrakurikuler</title>

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
   SEARCH
===================================================== */

.search-area {
    padding: 18px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #eef2f5;
}

.search-input { position: relative; }

.search-input i {
    position: absolute;
    left: 13px; top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}

.search-input input {
    padding-left: 39px;
    min-height: 43px;
    border: 1px solid #e2e8f0;
    border-radius: 11px;
}

.search-input input:focus {
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
   BUTTON
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

.btn-add:disabled {
    opacity: .5;
    cursor: not-allowed;
    transform: none;
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

.ekskul-box { display: flex; align-items: center; gap: 12px; }

.ekskul-icon {
    width: 40px; height: 40px;
    flex-shrink: 0;
    border-radius: 11px;
    background: #e0f2fe;
    color: #0284c7;
    display: flex;
    align-items: center; justify-content: center;
    font-size: 17px;
}

.ekskul-name { font-weight: 600; font-size: 13px; }


/* =====================================================
   ACTION
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

.btn-edit { background: #e0f2fe; color: #0284c7; }
.btn-edit:hover { background: #0284c7; color: white; }

.btn-warning { background: #fef3c7; color: #b45309; }
.btn-warning:hover { background: #f59e0b; color: white; }

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

.btn-save:disabled {
    opacity: .5;
    cursor: not-allowed;
}

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
   ALERT
===================================================== */

.alert-custom {
    border: none;
    border-radius: 14px;
    box-shadow: 0 5px 22px rgba(15,23,42,.05);
}

.info-pembina {
    background: #f0fdfa;
    border-radius: 10px;
    padding: 10px 14px;
    margin-top: 8px;
    font-size: 12px;
    color: #0f766e;
    display: none;
    align-items: center;
    gap: 8px;
}

.info-pembina.show { display: flex; }


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
    <a href="absensi.php"><i class="bi bi-calendar-check-fill"></i><span>Absensi</span></a>
    <a href="laporan.php"><i class="bi bi-bar-chart-fill"></i><span>Laporan</span></a>
    <a href="pembina.php" class="active"><i class="bi bi-person-workspace"></i><span>Pembina</span></a>

    <div class="logout">
        <a href="../logout.php"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
    </div>

</div>


<!-- CONTENT -->
<div class="content">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-title">
            <h5>Pembina</h5>
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
                <i class="bi bi-person-workspace"></i>
                Manajemen Pembina
            </div>
            <h2>Kelola Akun Pembina</h2>
            <p>
                Pilih ekstrakurikuler — nama pembina akan terisi
                otomatis dari data Ekstrakurikuler.
            </p>
        </div>
    </div>


    <!-- INFO -->
    <div class="alert alert-info alert-dismissible fade show alert-custom" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i>
        Nama pembina <strong>diambil otomatis</strong> dari data Ekstrakurikuler.
        Pastikan nama pembina sudah diisi di menu
        <a href="ekstrakurikuler.php" class="alert-link">Ekstrakurikuler</a>.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>


    <!-- PESAN -->
    <?php if (!empty($pesanPembina)): ?>
    <div class="alert alert-<?= htmlspecialchars($tipePembina); ?> alert-dismissible fade show alert-custom" role="alert">
        <i class="bi <?= $tipePembina === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
        <?= $pesanPembina; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>


    <!-- STATISTIK -->
    <div class="row g-3 mb-4">

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-blue"><i class="bi bi-person-workspace"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalPembinaUnik; ?></div>
                <div class="stat-label">Total Akun Pembina</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-green"><i class="bi bi-stars"></i></div>
                    <div class="stat-arrow"><i class="bi bi-three-dots"></i></div>
                </div>
                <div class="stat-number"><?= $totalEkskulPembina; ?></div>
                <div class="stat-label">Ekstrakurikuler Berpembina</div>
            </div>
        </div>

    </div>


    <!-- DAFTAR PEMBINA -->
    <div class="section-card">

        <div class="section-header">

            <div>
                <h5><i class="bi bi-people-fill me-1"></i> Daftar Pembina</h5>
                <small>Menampilkan <?= count($listPembina); ?> data pembina</small>
            </div>

            <button type="button" class="btn-add"
                    data-bs-toggle="modal" data-bs-target="#modalTambahPembina"
                    <?= $totalEkskulPembina == 0 ? 'disabled' : ''; ?>>
                <i class="bi bi-person-plus-fill me-1"></i>
                Tambah Pembina
            </button>

        </div>


        <!-- SEARCH -->
        <div class="search-area">
            <form method="GET" action="pembina.php">
                <div class="row g-2">

                    <div class="col-lg-9">
                        <div class="search-input">
                            <i class="bi bi-search"></i>
                            <input type="text" name="cari" class="form-control"
                                   placeholder="Cari nama pembina, email, atau ekstrakurikuler..."
                                   value="<?= htmlspecialchars($cari); ?>">
                        </div>
                    </div>

                    <div class="col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1"
                                style="height:43px;border-radius:11px;">
                            <i class="bi bi-search me-1"></i> Cari
                        </button>

                        <?php if ($cari !== ""): ?>
                            <a href="pembina.php" class="btn-reset d-flex align-items-center" title="Reset">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                        <?php endif; ?>
                    </div>

                </div>
            </form>
        </div>


        <?php if ($totalPembina > 0): ?>

        <div class="table-responsive">
            <table class="table">

                <thead>
                    <tr>
                        <th style="width:60px;">No</th>
                        <th>Nama Pembina</th>
                        <th>Email</th>
                        <th>Ekstrakurikuler</th>
                        <th class="text-center" style="width:150px;">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php $no = 1; foreach ($listPembina as $row): ?>

                    <tr>

                        <td><div class="number-box"><?= $no++; ?></div></td>

                        <td>
                            <div class="student-box">
                                <div class="student-avatar"><i class="bi bi-person-fill"></i></div>
                                <div class="student-info">
                                    <strong><?= htmlspecialchars($row['nama']); ?></strong>
                                    <small>Pembina</small>
                                </div>
                            </div>
                        </td>

                        <td><?= htmlspecialchars($row['email']); ?></td>

                        <td>
                            <?php if (!empty($row['nama_ekskul'])): ?>
                                <div class="ekskul-box">
                                    <div class="ekskul-icon"><i class="bi bi-stars"></i></div>
                                    <div class="ekskul-name"><?= htmlspecialchars($row['nama_ekskul']); ?></div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>

                        <td class="text-center">
                            <div class="action-buttons justify-content-center">

                                <button type="button" class="btn-action btn-edit" title="Edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditPembina<?= (int)$row['user_id']; ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </button>

                                <?php if (!empty($row['ekskul_id'])): ?>
                                <form method="POST" action="pembina.php" style="display:inline;"
                                      onsubmit="return confirm('Hapus hubungan pembina dengan ekstrakurikuler ini?');">
                                    <input type="hidden" name="user_id" value="<?= (int)$row['user_id']; ?>">
                                    <input type="hidden" name="ekskul_id" value="<?= (int)$row['ekskul_id']; ?>">
                                    <button type="submit" name="hapus_pembina"
                                            class="btn-action btn-warning" title="Hapus Hubungan">
                                        <i class="bi bi-link-45deg"></i>
                                    </button>
                                </form>
                                <?php endif; ?>

                                <form method="POST" action="pembina.php" style="display:inline;"
                                      onsubmit="return confirm('Hapus AKUN pembina ini secara permanen?');">
                                    <input type="hidden" name="user_id" value="<?= (int)$row['user_id']; ?>">
                                    <button type="submit" name="hapus_akun_pembina"
                                            class="btn-action btn-delete" title="Hapus Akun">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </form>

                            </div>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>
        </div>

        <?php else: ?>

        <div class="empty-state">
            <i class="bi bi-person-workspace"></i>
            <strong>Belum Ada Pembina</strong>
            <span>
                <?= $cari !== ""
                    ? "Tidak ada pembina yang cocok dengan pencarian."
                    : "Silakan tambahkan akun pembina terlebih dahulu."; ?>
            </span>
        </div>

        <?php endif; ?>

    </div>


</div>


<!-- MODAL TAMBAH PEMBINA -->
<div class="modal fade" id="modalTambahPembina" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-plus-fill me-2"></i>
                    Tambah Akun Pembina
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" action="pembina.php">

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-12">
                            <label class="form-label">Pilih Ekstrakurikuler</label>
                            <select name="ekskul_id" id="ekskul_id_tambah" class="form-select" required>
                                <option value="">-- Pilih Ekstrakurikuler --</option>
                                <?php foreach ($listEkskulPembina as $e): ?>
                                    <option value="<?= (int)$e['id']; ?>"
                                            data-pembina="<?= htmlspecialchars($e['pembina']); ?>">
                                        <?= htmlspecialchars($e['nama_ekskul']); ?>
                                        — <?= htmlspecialchars($e['pembina']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <div class="info-pembina" id="info-pembina-tambah">
                                <i class="bi bi-person-badge-fill"></i>
                                Nama pembina: <strong id="nama-pembina-preview">-</strong>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email_pembina" class="form-control"
                                   placeholder="Masukkan email" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="password_pembina" class="form-control"
                                   placeholder="Minimal 6 karakter" required>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_pembina" class="btn-save">
                        <i class="bi bi-save me-1"></i> Simpan
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>


<!-- MODAL EDIT PEMBINA -->
<?php
$pembinaUntukEdit = [];
foreach ($listPembina as $p) {
    if (!isset($pembinaUntukEdit[$p['user_id']])) {
        $pembinaUntukEdit[$p['user_id']] = $p;
    }
}
?>

<?php foreach ($pembinaUntukEdit as $p): ?>

<div class="modal fade" id="modalEditPembina<?= (int)$p['user_id']; ?>"
     tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square me-2"></i>
                    Edit Data Pembina
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" action="pembina.php">

                <div class="modal-body">

                    <input type="hidden" name="user_id" value="<?= (int)$p['user_id']; ?>">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Nama Pembina</label>
                            <input type="text" name="nama_pembina" class="form-control"
                                   value="<?= htmlspecialchars($p['nama']); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email_pembina" class="form-control"
                                   value="<?= htmlspecialchars($p['email']); ?>" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">
                                Password Baru
                                <small class="text-muted">(kosongkan jika tidak diganti)</small>
                            </label>
                            <input type="password" name="password_pembina" class="form-control"
                                   placeholder="Minimal 6 karakter">
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_pembina" class="btn-save">
                        <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php endforeach; ?>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* Preview nama pembina otomatis saat pilih ekstrakurikuler */
const ekskulTambah = document.getElementById('ekskul_id_tambah');
const infoPembina  = document.getElementById('info-pembina-tambah');
const namaPreview  = document.getElementById('nama-pembina-preview');

if (ekskulTambah && infoPembina && namaPreview) {
    ekskulTambah.addEventListener('change', function () {
        const opt  = this.options[this.selectedIndex];
        const nama = opt.getAttribute('data-pembina') || '';

        if (nama) {
            namaPreview.textContent = nama;
            infoPembina.classList.add('show');
        } else {
            infoPembina.classList.remove('show');
        }
    });
}
</script>

</body>
</html>