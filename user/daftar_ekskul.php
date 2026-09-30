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
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../login.php");
    exit();
}

/* =====================================================
   DATA USER
===================================================== */
$user_id = (int) $_SESSION['id'];
$nama_user = $_SESSION['nama'] ?? 'Siswa';

/* =====================================================
   DATA SISWA
===================================================== */
$siswa = null;
$siswa_id = 0;

$stmtSiswa = mysqli_prepare(
    $conn,
    "SELECT id, user_id, nama_siswa, nis, kelas, jenis_kelamin, alamat
     FROM siswa
     WHERE user_id = ?
     LIMIT 1"
);

if ($stmtSiswa) {
    mysqli_stmt_bind_param($stmtSiswa, "i", $user_id);
    mysqli_stmt_execute($stmtSiswa);
    $hasilSiswa = mysqli_stmt_get_result($stmtSiswa);

    if ($hasilSiswa && mysqli_num_rows($hasilSiswa) > 0) {
        $siswa = mysqli_fetch_assoc($hasilSiswa);
        $siswa_id = (int) $siswa['id'];
    }

    mysqli_stmt_close($stmtSiswa);
}

$namaTampilan = $siswa['nama_siswa'] ?? $nama_user;
$inisial = strtoupper(substr(trim($namaTampilan), 0, 1));
if ($inisial === '') {
    $inisial = 'S';
}

/* =====================================================
   PESAN
===================================================== */
$success = '';
$error = '';

/* =====================================================
   PROSES PENDAFTARAN EKSKUL
===================================================== */
if (isset($_POST['daftar_ekskul'])) {

    $ekskul_id = (int) ($_POST['ekskul_id'] ?? 0);

    if ($siswa_id <= 0) {
        $error = "Data siswa belum terhubung dengan akun ini.";

    } elseif ($ekskul_id <= 0) {
        $error = "Ekstrakurikuler tidak valid.";

    } else {

        /* Cek ekstrakurikuler */
        $stmtEkskul = mysqli_prepare(
            $conn,
            "SELECT id, nama_ekskul, pembina
             FROM ekstrakurikuler
             WHERE id = ?
             LIMIT 1"
        );

        if (!$stmtEkskul) {
            $error = "Gagal mengecek ekstrakurikuler: " . mysqli_error($conn);
        } else {

            mysqli_stmt_bind_param($stmtEkskul, "i", $ekskul_id);
            mysqli_stmt_execute($stmtEkskul);
            $hasilEkskul = mysqli_stmt_get_result($stmtEkskul);

            if (!$hasilEkskul || mysqli_num_rows($hasilEkskul) === 0) {
                $error = "Ekstrakurikuler tidak ditemukan.";
            } else {

                $dataEkskulDaftar = mysqli_fetch_assoc($hasilEkskul);

                /* Cek pendaftaran sebelumnya */
                $cek = mysqli_prepare(
                    $conn,
                    "SELECT id, status
                     FROM pendaftaran
                     WHERE siswa_id = ?
                     AND ekskul_id = ?
                     LIMIT 1"
                );

                if (!$cek) {
                    $error = "Gagal mengecek pendaftaran: " . mysqli_error($conn);
                } else {

                    mysqli_stmt_bind_param($cek, "ii", $siswa_id, $ekskul_id);
                    mysqli_stmt_execute($cek);
                    $hasilCek = mysqli_stmt_get_result($cek);

                    if ($hasilCek && mysqli_num_rows($hasilCek) > 0) {

                        $dataPendaftaran = mysqli_fetch_assoc($hasilCek);
                        $status = strtolower(trim($dataPendaftaran['status'] ?? ''));

                        if ($status === 'ditolak') {

                            $update = mysqli_prepare(
                                $conn,
                                "UPDATE pendaftaran
                                 SET status = 'menunggu'
                                 WHERE id = ?"
                            );

                            if ($update) {
                                $pendaftaran_id = (int) $dataPendaftaran['id'];
                                mysqli_stmt_bind_param($update, "i", $pendaftaran_id);

                                if (mysqli_stmt_execute($update)) {
                                    $success =
                                        "Pendaftaran berhasil dikirim kembali. Silakan menunggu persetujuan admin.";
                                } else {
                                    $error =
                                        "Pendaftaran gagal: " . mysqli_stmt_error($update);
                                }

                                mysqli_stmt_close($update);
                            } else {
                                $error = "Query pendaftaran gagal: " . mysqli_error($conn);
                            }

                        } else {

                            $error =
                                "Kamu sudah mendaftar "
                                . htmlspecialchars($dataEkskulDaftar['nama_ekskul'])
                                . ". Status pendaftaran: "
                                . htmlspecialchars(ucfirst($status))
                                . ".";
                        }

                    } else {

                        /*
                         * Struktur yang digunakan:
                         * pendaftaran.siswa_id -> siswa.id
                         * pendaftaran.ekskul_id -> ekstrakurikuler.id
                         */
                        $insert = mysqli_prepare(
                            $conn,
                            "INSERT INTO pendaftaran
                             (siswa_id, ekskul_id, status)
                             VALUES (?, ?, 'menunggu')"
                        );

                        if ($insert) {
                            mysqli_stmt_bind_param($insert, "ii", $siswa_id, $ekskul_id);

                            if (mysqli_stmt_execute($insert)) {
                                $success =
                                    "Berhasil mendaftar "
                                    . htmlspecialchars($dataEkskulDaftar['nama_ekskul'])
                                    . ". Silakan menunggu persetujuan admin.";
                            } else {
                                $error =
                                    "Pendaftaran gagal: " . mysqli_stmt_error($insert);
                            }

                            mysqli_stmt_close($insert);
                        } else {
                            $error = "Query pendaftaran gagal: " . mysqli_error($conn);
                        }
                    }

                    mysqli_stmt_close($cek);
                }
            }

            mysqli_stmt_close($stmtEkskul);
        }
    }
}

/* =====================================================
   DATA SEMUA EKSKUL
===================================================== */
$dataEkskul = mysqli_query(
    $conn,
    "SELECT id, nama_ekskul, pembina
     FROM ekstrakurikuler
     ORDER BY nama_ekskul ASC"
);

/* =====================================================
   DATA PENDAFTARAN SAYA
===================================================== */
$pendaftaranSaya = [];

if ($siswa_id > 0) {
    $stmtSaya = mysqli_prepare(
        $conn,
        "SELECT ekskul_id, status
         FROM pendaftaran
         WHERE siswa_id = ?"
    );

    if ($stmtSaya) {
        mysqli_stmt_bind_param($stmtSaya, "i", $siswa_id);
        mysqli_stmt_execute($stmtSaya);
        $hasilSaya = mysqli_stmt_get_result($stmtSaya);

        if ($hasilSaya) {
            while ($row = mysqli_fetch_assoc($hasilSaya)) {
                $pendaftaranSaya[(int) $row['ekskul_id']] = strtolower(trim($row['status'] ?? ''));
            }
        }

        mysqli_stmt_close($stmtSaya);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Ekskul - EkskulKu</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
*{box-sizing:border-box}
body{margin:0;background:#f3f7fa;color:#172033;font-family:"Segoe UI",Arial,sans-serif}
.sidebar{position:fixed;left:0;top:0;width:255px;height:100vh;background:linear-gradient(180deg,#075985 0%,#0f766e 100%);color:#fff;padding:22px 15px;z-index:1000;box-shadow:8px 0 25px rgba(15,23,42,.08)}
.brand{padding:8px 8px 23px;border-bottom:1px solid rgba(255,255,255,.15);text-align:center}
.brand-icon{width:60px;height:60px;margin:auto;border-radius:17px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.14);font-size:27px}
.brand h4{margin:11px 0 3px;font-weight:700}.brand small{color:rgba(255,255,255,.65)}
.menu-title{margin:25px 11px 9px;color:rgba(255,255,255,.55);font-size:10px;text-transform:uppercase;letter-spacing:1px}
.sidebar a{display:flex;align-items:center;gap:13px;padding:12px 14px;margin-bottom:5px;border-radius:11px;color:rgba(255,255,255,.84);text-decoration:none;transition:.2s}
.sidebar a i{width:21px;text-align:center;font-size:17px}.sidebar a:hover{color:#fff;background:rgba(255,255,255,.12);transform:translateX(2px)}
.sidebar a.active{color:#fff;background:rgba(255,255,255,.18);box-shadow:inset 3px 0 0 #fff}
.logout{position:absolute;left:15px;right:15px;bottom:18px}.logout a{background:rgba(220,38,38,.85);color:#fff}.logout a:hover{background:#dc2626}
.content{margin-left:255px;padding:28px}.topbar{background:#fff;min-height:72px;padding:14px 20px;border-radius:17px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 5px 22px rgba(15,23,42,.06);margin-bottom:22px}
.topbar-title h5{margin:0;font-size:18px;font-weight:700}.topbar-title small{color:#64748b}.user-area{display:flex;align-items:center;gap:11px}.user-text{text-align:right}.user-text strong{display:block;font-size:14px}.user-text small{color:#64748b}
.avatar{width:44px;height:44px;border-radius:13px;background:#e0f2fe;color:#0369a1;display:flex;align-items:center;justify-content:center;font-weight:700}
.page-header{position:relative;overflow:hidden;background:linear-gradient(135deg,#0284c7,#0f766e);color:#fff;border-radius:20px;padding:26px 28px;margin-bottom:22px;box-shadow:0 10px 28px rgba(14,116,144,.15)}
.page-header h2{margin:0 0 6px;font-size:25px;font-weight:700}.page-header p{margin:0;color:rgba(255,255,255,.82)}
.profile-mini{background:linear-gradient(135deg,#ecfeff,#f0fdfa);border:1px solid #ccfbf1;border-radius:18px;padding:18px 20px;margin-bottom:20px}.profile-mini-head{display:flex;align-items:center;gap:13px}.profile-mini-avatar{width:52px;height:52px;border-radius:15px;background:linear-gradient(135deg,#0284c7,#0f766e);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700}.profile-mini strong{display:block}.profile-mini small{color:#64748b}
.section-card{background:#fff;border-radius:18px;border:1px solid #edf2f5;box-shadow:0 5px 22px rgba(15,23,42,.055);overflow:hidden}.section-header{padding:18px 20px;border-bottom:1px solid #eef2f5;display:flex;align-items:center;justify-content:space-between}.section-header h5{margin:0;font-size:16px;font-weight:700}.section-header small{color:#64748b}
.ekskul-card{height:100%;background:#fff;border:1px solid #edf2f5;border-radius:17px;padding:20px;box-shadow:0 5px 18px rgba(15,23,42,.05);transition:.2s}.ekskul-card:hover{transform:translateY(-3px);box-shadow:0 10px 25px rgba(15,23,42,.08)}
.ekskul-icon{width:48px;height:48px;border-radius:14px;background:#e0f2fe;color:#0284c7;display:flex;align-items:center;justify-content:center;font-size:21px;margin-bottom:13px}.ekskul-card h5{margin:0 0 4px;font-size:17px}.ekskul-card p{margin:0;color:#94a3b8;font-size:12px}.ekskul-info{margin-top:16px}.info-row{display:flex;gap:9px;align-items:flex-start;color:#64748b;font-size:12px;margin-bottom:8px}.info-row i{width:18px;color:#0284c7}.btn-daftar,.btn-status{width:100%;border-radius:10px;padding:9px;margin-top:9px;font-size:12px;font-weight:600}.btn-daftar{border:none;background:linear-gradient(135deg,#0284c7,#0f766e);color:#fff}.btn-daftar:hover{color:#fff;box-shadow:0 5px 15px rgba(2,132,199,.2)}
.status-menunggu{background:#fef3c7;color:#b45309}.status-diterima{background:#dcfce7;color:#15803d}.status-ditolak{background:#fee2e2;color:#b91c1c}.alert-box{border:none;border-radius:13px}
.empty-state{padding:45px 20px;text-align:center;color:#94a3b8}.empty-state i{display:block;font-size:40px;margin-bottom:10px}
@media(max-width:992px){.sidebar{width:220px}.content{margin-left:220px;padding:20px}}
@media(max-width:768px){.sidebar{position:relative;width:100%;height:auto}.logout{position:static;margin-top:20px}.content{margin-left:0;padding:15px}.topbar{flex-direction:column;align-items:flex-start;gap:13px}.user-text{text-align:left}}
</style>
</head>
<body>
<div class="sidebar">
    <div class="brand">
        <div class="brand-icon"><i class="bi bi-mortarboard-fill"></i></div>
        <h4>Ekstrakurikuler</h4>
        <small>Portal Siswa</small>
    </div>

    <div class="menu-title">Menu Utama</div>

    <a href="dashboard_user.php">
        <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
    </a>

    <a href="daftar_ekskul.php" class="active">
        <i class="bi bi-stars"></i><span>Daftar Ekskul</span>
    </a>

    <a href="pendaftaran_saya.php">
        <i class="bi bi-person-check-fill"></i><span>Pendaftaran Saya</span>
    </a>

    <a href="jadwal_saya.php">
        <i class="bi bi-calendar-event-fill"></i><span>Jadwal Saya</span>
    </a>

    <a href="absensi_saya.php">
        <i class="bi bi-calendar-check-fill"></i><span>Absensi Saya</span>
    </a>

    <a href="profil.php">
        <i class="bi bi-person-fill"></i><span>Profil</span>
    </a>

    <div class="logout">
        <a href="../logout.php">
            <i class="bi bi-box-arrow-right"></i><span>Logout</span>
        </a>
    </div>
</div>

<div class="content">
    <div class="topbar">
        <div class="topbar-title">
            <h5>Daftar Ekskul</h5>
            <small>Pilih ekstrakurikuler yang ingin kamu ikuti</small>
        </div>
        <div class="user-area">
            <div class="user-text">
                <strong><?= htmlspecialchars($namaTampilan); ?></strong>
                <small>Siswa</small>
            </div>
            <div class="avatar"><?= htmlspecialchars($inisial); ?></div>
        </div>
    </div>

    <div class="page-header">
        <h2>Temukan Ekskul Favoritmu</h2>
        <p>Pilih ekstrakurikuler yang ingin kamu ikuti dan kirim pendaftaran kepada admin.</p>
    </div>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success alert-box mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger alert-box mb-4">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <?= $error; ?>
        </div>
    <?php endif; ?>

    <div class="profile-mini">
        <div class="profile-mini-head">
            <div class="profile-mini-avatar"><?= htmlspecialchars($inisial); ?></div>
            <div>
                <strong><?= htmlspecialchars($namaTampilan); ?></strong>
                <small>
                    <?= $siswa ? htmlspecialchars($siswa['kelas'] ?? 'Siswa') : 'Data siswa belum terhubung'; ?>
                </small>
            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-header">
            <div>
                <h5>Daftar Ekstrakurikuler</h5>
                <small>Pilih salah satu ekstrakurikuler</small>
            </div>
            <span class="badge text-bg-light">
                <i class="bi bi-grid me-1"></i> Semua Ekskul
            </span>
        </div>

        <div class="p-3">
            <div class="row g-3">

                <?php if ($dataEkskul && mysqli_num_rows($dataEkskul) > 0): ?>

                    <?php while ($e = mysqli_fetch_assoc($dataEkskul)): ?>
                        <?php
                        $idEkskul = (int) $e['id'];
                        $statusSaatIni = $pendaftaranSaya[$idEkskul] ?? null;
                        ?>

                        <div class="col-xl-4 col-md-6">
                            <div class="ekskul-card">
                                <div class="ekskul-icon"><i class="bi bi-stars"></i></div>

                                <h5><?= htmlspecialchars($e['nama_ekskul'] ?? '-'); ?></h5>
                                <p>Ekstrakurikuler sekolah</p>

                                <div class="ekskul-info">
                                    <div class="info-row">
                                        <i class="bi bi-person-badge"></i>
                                        <span>Pembina: <?= htmlspecialchars($e['pembina'] ?? '-'); ?></span>
                                    </div>

                                    <div class="info-row">
                                        <i class="bi bi-bookmark-star"></i>
                                        <span>Kegiatan ekstrakurikuler</span>
                                    </div>
                                </div>

                                <?php if ($statusSaatIni === null): ?>
                                    <form method="POST" action="daftar_ekskul.php" onsubmit="return confirm('Apakah kamu yakin ingin mendaftar ekskul ini?');">
                                        <input type="hidden" name="ekskul_id" value="<?= $idEkskul; ?>">
                                        <button type="submit" name="daftar_ekskul" class="btn btn-daftar">
                                            <i class="bi bi-plus-circle me-1"></i> Daftar Ekskul
                                        </button>
                                    </form>

                                <?php elseif ($statusSaatIni === 'menunggu'): ?>
                                    <div class="btn-status status-menunggu">
                                        <i class="bi bi-hourglass-split me-1"></i>
                                        Menunggu Persetujuan
                                    </div>

                                <?php elseif ($statusSaatIni === 'diterima'): ?>
                                    <div class="btn-status status-diterima">
                                        <i class="bi bi-check-circle-fill me-1"></i>
                                        Sudah Diterima
                                    </div>

                                <?php elseif ($statusSaatIni === 'ditolak'): ?>
                                    <form method="POST" action="daftar_ekskul.php" onsubmit="return confirm('Pendaftaran sebelumnya ditolak. Daftar kembali?');">
                                        <input type="hidden" name="ekskul_id" value="<?= $idEkskul; ?>">
                                        <button type="submit" name="daftar_ekskul" class="btn btn-daftar">
                                            <i class="bi bi-arrow-repeat me-1"></i> Daftar Kembali
                                        </button>
                                    </form>

                                <?php else: ?>
                                    <div class="btn-status status-menunggu">
                                        Status: <?= htmlspecialchars(ucfirst($statusSaatIni)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>

                <?php else: ?>
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <strong>Belum ada ekstrakurikuler</strong>
                            <div class="mt-1">Data ekstrakurikuler belum tersedia.</div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <div class="alert alert-info alert-box mt-4">
        <i class="bi bi-info-circle-fill me-2"></i>
        <strong>Informasi:</strong>
        Setelah kamu mendaftar, status pendaftaran menjadi <strong>Menunggu Persetujuan</strong>.
        Setelah admin menerima pendaftaran, ekskul tersebut akan tampil di <strong>Pendaftaran Saya</strong>
        dan jadwalnya akan tampil di <strong>Jadwal Saya</strong>.
    </div>
</div>
</body>
</html>
