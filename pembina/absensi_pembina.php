<?php
/* =========================================================
   ABSENSI PEMBINA - SISTEM INFORMASI EKSTRAKURIKULER
   Pembina input absensi untuk siswa di ekskulnya
   FIX: Kolom user_id diisi dari tabel siswa
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
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "pembina") {
    header("Location: ../login.php");
    exit();
}

$userId = (int) $_SESSION['id'];
$namaPembina = $_SESSION['nama'] ?? "Pembina";
$inisial = strtoupper(substr(trim($namaPembina), 0, 1));

$pesan = "";
$tipePesan = "";


/* =========================================================
   AMBIL EKSKUL YANG DIAMPU
========================================================= */
$listEkskulPembina = [];

$stmtEks = mysqli_prepare($conn,
    "SELECT e.id, e.nama_ekskul
     FROM ekstrakurikuler e
     INNER JOIN pembina_ekskul pe ON pe.ekskul_id = e.id
     WHERE pe.user_id = ?
     ORDER BY e.nama_ekskul ASC");

if ($stmtEks) {
    mysqli_stmt_bind_param($stmtEks, "i", $userId);
    mysqli_stmt_execute($stmtEks);
    $resEks = mysqli_stmt_get_result($stmtEks);
    while ($r = mysqli_fetch_assoc($resEks)) {
        $listEkskulPembina[] = $r;
    }
    mysqli_stmt_close($stmtEks);
}

$ekskulIds = array_map('intval', array_column($listEkskulPembina, 'id'));


/* =========================================================
   PROSES SIMPAN ABSENSI (MASSAL) — DENGAN user_id
========================================================= */
if (isset($_POST['simpan_absensi'])) {

    $ekskul_id = (int)($_POST['ekskul_id'] ?? 0);
    $tanggal   = trim($_POST['tanggal'] ?? '');
    $dataAbsen = $_POST['status'] ?? [];

    if ($ekskul_id <= 0 || $tanggal === '' || count($dataAbsen) === 0) {
        $pesan = "Data absensi belum lengkap.";
        $tipePesan = "danger";

    } elseif (!in_array($ekskul_id, $ekskulIds, true)) {
        $pesan = "Anda tidak berhak mengisi absensi ekskul ini.";
        $tipePesan = "danger";

    } else {

        $statusValid = ["hadir", "izin", "sakit", "alfa"];
        $sukses = 0;
        $gagal = 0;

        foreach ($dataAbsen as $siswa_id => $status) {

            $siswa_id = (int)$siswa_id;
            $status   = strtolower(trim($status));

            if ($siswa_id <= 0 || !in_array($status, $statusValid, true)) {
                $gagal++;
                continue;
            }

            /* AMBIL user_id DARI TABEL siswa */
            $user_id_siswa = 0;
            $stmtU = mysqli_prepare($conn,
                "SELECT user_id FROM siswa WHERE id = ? LIMIT 1");

            if ($stmtU) {
                mysqli_stmt_bind_param($stmtU, "i", $siswa_id);
                mysqli_stmt_execute($stmtU);
                $resU = mysqli_stmt_get_result($stmtU);
                $dU = mysqli_fetch_assoc($resU);
                $user_id_siswa = (int)($dU['user_id'] ?? 0);
                mysqli_stmt_close($stmtU);
            }

            if ($user_id_siswa <= 0) {
                $gagal++;
                continue;
            }

            /* CEK DUPLIKAT */
            $stmtCek = mysqli_prepare($conn,
                "SELECT id FROM absensi
                 WHERE siswa_id = ? AND ekskul_id = ? AND tanggal = ?
                 LIMIT 1");
            mysqli_stmt_bind_param($stmtCek, "iis", $siswa_id, $ekskul_id, $tanggal);
            mysqli_stmt_execute($stmtCek);
            $resCek = mysqli_stmt_get_result($stmtCek);
            $adaDuplikat = mysqli_num_rows($resCek) > 0;
            mysqli_stmt_close($stmtCek);

            if ($adaDuplikat) {
                /* UPDATE */
                $stmtUp = mysqli_prepare($conn,
                    "UPDATE absensi SET status_hadir = ?
                     WHERE siswa_id = ? AND ekskul_id = ? AND tanggal = ?");
                mysqli_stmt_bind_param($stmtUp, "siis",
                    $status, $siswa_id, $ekskul_id, $tanggal);

                if (mysqli_stmt_execute($stmtUp)) $sukses++;
                else $gagal++;
                mysqli_stmt_close($stmtUp);

            } else {
                /* INSERT — dengan user_id */
                $stmtIn = mysqli_prepare($conn,
                    "INSERT INTO absensi
                     (user_id, siswa_id, ekskul_id, tanggal, status_hadir)
                     VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmtIn, "iiiss",
                    $user_id_siswa, $siswa_id, $ekskul_id, $tanggal, $status);

                if (mysqli_stmt_execute($stmtIn)) $sukses++;
                else $gagal++;
                mysqli_stmt_close($stmtIn);
            }
        }

        if ($sukses > 0) {
            $pesan = "Absensi berhasil disimpan. ($sukses data)";
            $tipePesan = "success";
        }
        if ($gagal > 0) {
            $pesan .= " Ada $gagal data yang gagal.";
            $tipePesan = ($sukses > 0) ? "warning" : "danger";
        }
    }
}


/* =========================================================
   AMBIL SISWA YANG DITERIMA DI EKSKUL TERPILIH
========================================================= */
$ekskulTerpilih = (int)($_GET['ekskul'] ?? ($_POST['ekskul_id'] ?? 0));
$tanggalTerpilih = trim($_GET['tanggal'] ?? date('Y-m-d'));

$listSiswaDiterima = [];
$absensiTersimpan = [];

if ($ekskulTerpilih > 0 && in_array($ekskulTerpilih, $ekskulIds, true)) {

    $stmtSis = mysqli_prepare($conn,
        "SELECT s.id, s.nama_siswa, s.nis, s.kelas
         FROM pendaftaran p
         INNER JOIN siswa s ON p.siswa_id = s.id
         WHERE p.ekskul_id = ? AND p.status = 'diterima'
         ORDER BY s.nama_siswa ASC");

    if ($stmtSis) {
        mysqli_stmt_bind_param($stmtSis, "i", $ekskulTerpilih);
        mysqli_stmt_execute($stmtSis);
        $resSis = mysqli_stmt_get_result($stmtSis);
        while ($r = mysqli_fetch_assoc($resSis)) {
            $listSiswaDiterima[] = $r;
        }
        mysqli_stmt_close($stmtSis);
    }

    /* Absensi tersimpan untuk tanggal terpilih */
    $stmtAbs = mysqli_prepare($conn,
        "SELECT siswa_id, status_hadir
         FROM absensi
         WHERE ekskul_id = ? AND tanggal = ?");
    mysqli_stmt_bind_param($stmtAbs, "is", $ekskulTerpilih, $tanggalTerpilih);
    mysqli_stmt_execute($stmtAbs);
    $resAbs = mysqli_stmt_get_result($stmtAbs);
    while ($r = mysqli_fetch_assoc($resAbs)) {
        $absensiTersimpan[(int)$r['siswa_id']] = $r['status_hadir'];
    }
    mysqli_stmt_close($stmtAbs);
}


/* =========================================================
   RIWAYAT ABSENSI
========================================================= */
$riwayatAbsensi = [];

if ($ekskulTerpilih > 0 && in_array($ekskulTerpilih, $ekskulIds, true)) {

    $stmtR = mysqli_prepare($conn,
        "SELECT a.tanggal,
                SUM(CASE WHEN a.status_hadir = 'hadir' THEN 1 ELSE 0 END) AS hadir,
                SUM(CASE WHEN a.status_hadir = 'izin' THEN 1 ELSE 0 END) AS izin,
                SUM(CASE WHEN a.status_hadir = 'sakit' THEN 1 ELSE 0 END) AS sakit,
                SUM(CASE WHEN a.status_hadir = 'alfa' THEN 1 ELSE 0 END) AS alfa,
                COUNT(*) AS total
         FROM absensi a
         WHERE a.ekskul_id = ?
         GROUP BY a.tanggal
         ORDER BY a.tanggal DESC
         LIMIT 20");

    if ($stmtR) {
        mysqli_stmt_bind_param($stmtR, "i", $ekskulTerpilih);
        mysqli_stmt_execute($stmtR);
        $resR = mysqli_stmt_get_result($stmtR);
        while ($r = mysqli_fetch_assoc($resR)) {
            $riwayatAbsensi[] = $r;
        }
        mysqli_stmt_close($stmtR);
    }
}


/* =========================================================
   STATISTIK
========================================================= */
$totalSiswaDiterima = count($listSiswaDiterima);
$totalRiwayat = count($riwayatAbsensi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Absensi - Pembina</title>

<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="dns-prefetch" href="https://cdn.jsdelivr.net">

<link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></noscript>

<link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"></noscript>

<style>
::-webkit-scrollbar { width: 8px; height: 8px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: rgba(15, 118, 110, .25); border-radius: 10px; }
html { scrollbar-width: thin; scrollbar-color: rgba(15, 118, 110, .25) transparent; }
.sidebar::-webkit-scrollbar { width: 6px; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, .15); }

:focus-visible { outline: 2px solid #0284c7; outline-offset: 2px; }
.skip-link { position: absolute; top: -50px; left: 6px; background: #0284c7; color: white; padding: 10px 18px; border-radius: 8px; text-decoration: none; z-index: 99999; font-weight: 600; transition: top .2s; }
.skip-link:focus { top: 10px; color: white; }

* { box-sizing: border-box; }
body { margin: 0; background: #f3f7fa; color: #172033; font-family: "Segoe UI", Arial, sans-serif; }

.sidebar { position: fixed; left: 0; top: 0; width: 255px; height: 100vh; background: linear-gradient(180deg, #075985 0%, #0f766e 100%); color: white; padding: 22px 15px; z-index: 1000; overflow-y: auto; box-shadow: 8px 0 25px rgba(15,23,42,.08); }
.brand { text-align: center; padding: 8px 8px 23px; border-bottom: 1px solid rgba(255,255,255,.15); }
.brand-icon { width: 60px; height: 60px; margin: auto; border-radius: 17px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,.14); font-size: 27px; }
.brand h4 { margin: 11px 0 3px; font-weight: 700; }
.brand small { color: rgba(255,255,255,.85); }
.menu-title { margin: 25px 11px 9px; color: rgba(255,255,255,.75); font-size: 10px; text-transform: uppercase; letter-spacing: 1px; }
.sidebar a { display: flex; align-items: center; gap: 13px; padding: 12px 14px; margin-bottom: 5px; border-radius: 11px; color: rgba(255,255,255,.92); text-decoration: none; transition: .2s; }
.sidebar a i { width: 21px; text-align: center; font-size: 17px; }
.sidebar a:hover { color: #fff; background: rgba(255,255,255,.12); transform: translateX(2px); }
.sidebar a.active { color: #fff; background: rgba(255,255,255,.18); box-shadow: inset 3px 0 0 #fff; }
.logout { margin-top: 20px; }
.logout a { background: rgba(220,38,38,.9); color: #fff; }
.logout a:hover { background: #dc2626; }

.content { margin-left: 255px; padding: 28px; }

.topbar { background: white; min-height: 72px; padding: 14px 20px; border-radius: 17px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 5px 22px rgba(15,23,42,.06); margin-bottom: 22px; }
.topbar-title h1 { margin: 0; font-size: 18px; font-weight: 700; }
.topbar-title small { color: #475569; }
.admin-area { display: flex; align-items: center; gap: 11px; }
.admin-info { text-align: right; }
.admin-info strong { display: block; font-size: 14px; }
.admin-info small { color: #475569; }
.admin-avatar { width: 44px; height: 44px; border-radius: 13px; background: #e0f2fe; color: #0369a1; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 17px; }

.welcome { position: relative; overflow: hidden; background: linear-gradient(135deg, #0284c7, #0f766e); color: white; border-radius: 21px; padding: 29px 31px; margin-bottom: 22px; box-shadow: 0 12px 30px rgba(14,116,144,.18); }
.welcome::before { content: ""; position: absolute; width: 220px; height: 220px; border-radius: 50%; background: rgba(255,255,255,.07); right: -65px; top: -100px; }
.welcome-content { position: relative; z-index: 2; }
.welcome-badge { display: inline-flex; align-items: center; gap: 7px; background: rgba(255,255,255,.13); border: 1px solid rgba(255,255,255,.16); padding: 6px 11px; border-radius: 20px; font-size: 12px; margin-bottom: 12px; }
.welcome h2 { margin: 0 0 7px; font-size: 27px; font-weight: 700; }
.welcome p { margin: 0; color: rgba(255,255,255,.95); }

.stat-card { background: white; border-radius: 17px; padding: 20px; min-height: 145px; box-shadow: 0 5px 22px rgba(15,23,42,.055); border: 1px solid #edf2f5; }
.stat-top { display: flex; align-items: center; justify-content: space-between; }
.stat-icon { width: 45px; height: 45px; border-radius: 13px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
.icon-blue { background:#e0f2fe; color:#0284c7; }
.icon-green { background:#dcfce7; color:#166534; }
.icon-orange { background:#ffedd5; color:#c2410c; }
.icon-teal { background:#ccfbf1; color:#0f766e; }
.stat-number { font-size: 27px; font-weight: 750; margin-top: 15px; }
.stat-label { color: #475569; font-size: 13px; }

.section-card { background: white; border-radius: 18px; box-shadow: 0 5px 22px rgba(15,23,42,.055); border: 1px solid #edf2f5; overflow: hidden; }
.section-header { padding: 18px 20px; border-bottom: 1px solid #eef2f5; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; }
.section-header h2 { margin: 0; font-size: 16px; font-weight: 700; }
.section-header small { color: #475569; }

.form-label { font-size: 12px; font-weight: 600; color: #475569; }
.form-select, .form-control { border-radius: 10px; min-height: 43px; font-size: 13px; }
.form-select:focus, .form-control:focus { border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(56,189,248,.12); }

.btn-primary-custom { display: inline-flex; align-items: center; gap: 7px; border: none; border-radius: 10px; padding: 10px 15px; background: linear-gradient(135deg, #0284c7, #0f766e); color: white; font-size: 13px; font-weight: 600; text-decoration: none; transition: .2s; cursor: pointer; }
.btn-primary-custom:hover { color: white; transform: translateY(-1px); box-shadow: 0 8px 20px rgba(2,132,199,.25); }

.btn-reset { border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 15px; background: white; color: #475569; font-size: 13px; text-decoration: none; transition: .2s; }
.btn-reset:hover { background: #f1f5f9; color: #334155; }

.table { margin: 0; }
.table thead th { background: #f8fafc; border: none; color: #475569; font-size: 11px; text-transform: uppercase; padding: 13px 20px; white-space: nowrap; }
.table tbody td { padding: 14px 20px; vertical-align: middle; border-color: #f1f5f9; font-size: 13px; }
.table tbody tr:hover { background: #f8fafc; }

.number-box { width: 32px; height: 32px; border-radius: 10px; background: #f1f5f9; color: #475569; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; }

.student-box { display: flex; align-items: center; gap: 12px; }
.student-avatar { width: 42px; height: 42px; flex-shrink: 0; border-radius: 12px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 18px; }
.student-info strong { display: block; font-size: 13px; }
.student-info small { color: #475569; font-size: 11px; }

.status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.status-hadir { background:#dcfce7; color:#166534; }
.status-izin  { background:#fef3c7; color:#92400e; }
.status-sakit { background:#dbeafe; color:#1e40af; }
.status-alfa  { background:#fee2e2; color:#991b1b; }

.radio-absensi { display: inline-flex; gap: 6px; flex-wrap: wrap; }
.radio-absensi input[type="radio"] { display: none; }
.radio-absensi label {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 12px; border-radius: 8px;
    background: #f1f5f9; color: #475569;
    font-size: 12px; font-weight: 600;
    cursor: pointer; transition: .2s;
    border: 2px solid transparent;
}
.radio-absensi label:hover { background: #e2e8f0; }
.radio-absensi input[type="radio"]:checked + label.label-hadir { background: #16a34a; color: white; border-color: #166534; }
.radio-absensi input[type="radio"]:checked + label.label-izin  { background: #f59e0b; color: white; border-color: #b45309; }
.radio-absensi input[type="radio"]:checked + label.label-sakit { background: #3b82f6; color: white; border-color: #1e40af; }
.radio-absensi input[type="radio"]:checked + label.label-alfa  { background: #dc2626; color: white; border-color: #991b1b; }

.empty-state { padding: 45px 20px; text-align: center; color: #475569; }
.empty-state i { font-size: 35px; color: #94a3b8; display: block; margin-bottom: 10px; }
.empty-state strong { display: block; color: #172033; margin-bottom: 4px; }

.filter-box { padding: 20px; background: #f8fafc; border-bottom: 1px solid #eef2f5; }

.alert-custom { border: none; border-radius: 14px; box-shadow: 0 5px 22px rgba(15,23,42,.05); }

@media (max-width: 992px) {
    .sidebar { width: 220px; }
    .content { margin-left: 220px; padding: 20px; }
}
@media (max-width: 768px) {
    .sidebar { position: relative; width: 100%; height: auto; min-height: auto; }
    .logout { position: static; margin-top: 20px; }
    .content { margin-left: 0; padding: 15px; }
    .topbar { flex-direction: column; align-items: flex-start; gap: 13px; }
    .admin-info { text-align: left; }
    .welcome h2 { font-size: 23px; }
    .section-header { flex-direction: column; align-items: flex-start; }
    .radio-absensi { flex-direction: column; align-items: flex-start; }
}
</style>
</head>
<body>

<a href="#konten-utama" class="skip-link">Langsung ke konten</a>

<!-- SIDEBAR -->
<nav class="sidebar" aria-label="Menu utama">
    <div class="brand">
        <div class="brand-icon" aria-hidden="true"><i class="bi bi-mortarboard-fill"></i></div>
        <h4>Ekstrakurikuler</h4>
        <small>Portal Pembina</small>
    </div>

    <div class="menu-title">Menu Utama</div>

    <a href="dashboard_pembina.php">
        <i class="bi bi-grid-1x2-fill" aria-hidden="true"></i>
        <span>Dashboard</span>
    </a>

    <a href="dashboard_pembina.php?page=pendaftaran_masuk">
        <i class="bi bi-inbox-fill" aria-hidden="true"></i>
        <span>Pendaftaran Masuk</span>
    </a>

    <a href="absensi_pembina.php" class="active">
        <i class="bi bi-calendar-check-fill" aria-hidden="true"></i>
        <span>Absensi</span>
    </a>

    <a href="jadwal_pembina.php">
        <i class="bi bi-calendar-event-fill" aria-hidden="true"></i>
        <span>Jadwal</span>
    </a>

    <div class="logout">
        <a href="../logout.php">
            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            <span>Logout</span>
        </a>
    </div>
</nav>


<!-- CONTENT -->
<main class="content" id="konten-utama">

    <header class="topbar">
        <div class="topbar-title">
            <h1 class="h5">Absensi Ekskul</h1>
            <small>Sistem Informasi Ekstrakurikuler</small>
        </div>
        <div class="admin-area">
            <div class="admin-info">
                <strong><?= htmlspecialchars($namaPembina); ?></strong>
                <small>Pembina</small>
            </div>
            <div class="admin-avatar" aria-hidden="true"><?= htmlspecialchars($inisial); ?></div>
        </div>
    </header>

    <section class="welcome">
        <div class="welcome-content">
            <div class="welcome-badge">
                <i class="bi bi-calendar-check-fill" aria-hidden="true"></i>
                Kelola Absensi
            </div>
            <h2>Absensi Kegiatan</h2>
            <p>Input kehadiran siswa pada setiap kegiatan ekstrakurikuler yang Anda ampu.</p>
        </div>
    </section>

    <?php if ($pesan !== ""): ?>
    <div class="alert alert-<?= htmlspecialchars($tipePesan); ?> alert-dismissible fade show alert-custom" role="alert">
        <i class="bi <?= $tipePesan === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2" aria-hidden="true"></i>
        <?= htmlspecialchars($pesan); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>


    <!-- FILTER PILIH EKSKUL -->
    <section class="section-card mb-4">
        <div class="section-header">
            <div>
                <h2><i class="bi bi-funnel-fill me-1" aria-hidden="true"></i> Pilih Ekskul & Tanggal</h2>
                <small>Tentukan ekskul dan tanggal untuk input absensi</small>
            </div>
        </div>

        <div class="filter-box">
            <form method="GET" action="absensi_pembina.php">
                <div class="row g-3 align-items-end">

                    <div class="col-md-5">
                        <label class="form-label" for="ekskul">Ekstrakurikuler</label>
                        <select name="ekskul" id="ekskul" class="form-select" required>
                            <option value="">-- Pilih Ekstrakurikuler --</option>
                            <?php foreach ($listEkskulPembina as $e): ?>
                                <option value="<?= (int)$e['id']; ?>"
                                    <?= $ekskulTerpilih === (int)$e['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($e['nama_ekskul']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="tanggal">Tanggal</label>
                        <input type="date" name="tanggal" id="tanggal"
                               class="form-control"
                               value="<?= htmlspecialchars($tanggalTerpilih); ?>">
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn-primary-custom w-100">
                            <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                            Tampilkan
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </section>


<?php if ($ekskulTerpilih > 0 && in_array($ekskulTerpilih, $ekskulIds, true)): ?>

    <!-- STATISTIK -->
    <section class="row g-3 mb-4" aria-label="Statistik">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-green" aria-hidden="true"><i class="bi bi-people-fill"></i></div>
                </div>
                <div class="stat-number"><?= $totalSiswaDiterima; ?></div>
                <div class="stat-label">Siswa Aktif</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-blue" aria-hidden="true"><i class="bi bi-calendar-check-fill"></i></div>
                </div>
                <div class="stat-number"><?= $totalRiwayat; ?></div>
                <div class="stat-label">Hari Tercatat</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-orange" aria-hidden="true"><i class="bi bi-clock-history"></i></div>
                </div>
                <div class="stat-number"><?= htmlspecialchars(date('d M', strtotime($tanggalTerpilih))); ?></div>
                <div class="stat-label">Tanggal Dipilih</div>
            </div>
        </div>
    </section>


    <!-- FORM ABSENSI MASSAL -->
    <section class="section-card mb-4">
        <div class="section-header">
            <div>
                <h2>
                    <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                    Form Absensi
                </h2>
                <small>
                    <?= date('d F Y', strtotime($tanggalTerpilih)); ?> ·
                    <?php
                    foreach ($listEkskulPembina as $e) {
                        if ((int)$e['id'] === $ekskulTerpilih) {
                            echo htmlspecialchars($e['nama_ekskul']);
                            break;
                        }
                    }
                    ?>
                </small>
            </div>
        </div>

        <?php if (count($listSiswaDiterima) > 0): ?>
        <form method="POST" action="absensi_pembina.php">
            <input type="hidden" name="ekskul_id" value="<?= (int)$ekskulTerpilih; ?>">
            <input type="hidden" name="tanggal" value="<?= htmlspecialchars($tanggalTerpilih); ?>">

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="60">No</th>
                            <th>Siswa</th>
                            <th width="380">Status Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = 1;
                    foreach ($listSiswaDiterima as $siswa):
                        $siswaId = (int)$siswa['id'];
                        $statusSkrg = $absensiTersimpan[$siswaId] ?? '';
                    ?>
                        <tr>
                            <td><div class="number-box"><?= $no++; ?></div></td>
                            <td>
                                <div class="student-box">
                                    <div class="student-avatar" aria-hidden="true"><i class="bi bi-person-fill"></i></div>
                                    <div class="student-info">
                                        <strong><?= htmlspecialchars($siswa['nama_siswa']); ?></strong>
                                        <small>
                                            NIS: <?= htmlspecialchars($siswa['nis'] ?? '-'); ?>
                                            · Kelas: <?= htmlspecialchars($siswa['kelas'] ?? '-'); ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="radio-absensi">
                                    <input type="radio" name="status[<?= $siswaId; ?>]" id="h<?= $siswaId; ?>" value="hadir" <?= $statusSkrg === 'hadir' ? 'checked' : ''; ?>>
                                    <label for="h<?= $siswaId; ?>" class="label-hadir">
                                        <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Hadir
                                    </label>

                                    <input type="radio" name="status[<?= $siswaId; ?>]" id="i<?= $siswaId; ?>" value="izin" <?= $statusSkrg === 'izin' ? 'checked' : ''; ?>>
                                    <label for="i<?= $siswaId; ?>" class="label-izin">
                                        <i class="bi bi-envelope-paper-fill" aria-hidden="true"></i> Izin
                                    </label>

                                    <input type="radio" name="status[<?= $siswaId; ?>]" id="s<?= $siswaId; ?>" value="sakit" <?= $statusSkrg === 'sakit' ? 'checked' : ''; ?>>
                                    <label for="s<?= $siswaId; ?>" class="label-sakit">
                                        <i class="bi bi-thermometer-half" aria-hidden="true"></i> Sakit
                                    </label>

                                    <input type="radio" name="status[<?= $siswaId; ?>]" id="a<?= $siswaId; ?>" value="alfa" <?= $statusSkrg === 'alfa' ? 'checked' : ''; ?>>
                                    <label for="a<?= $siswaId; ?>" class="label-alfa">
                                        <i class="bi bi-x-circle-fill" aria-hidden="true"></i> Alfa
                                    </label>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top d-flex justify-content-end gap-2">
                <a href="absensi_pembina.php?ekskul=<?= (int)$ekskulTerpilih; ?>&tanggal=<?= htmlspecialchars($tanggalTerpilih); ?>"
                   class="btn-reset">
                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset
                </a>
                <button type="submit" name="simpan_absensi" class="btn-primary-custom">
                    <i class="bi bi-save" aria-hidden="true"></i>
                    Simpan Absensi
                </button>
            </div>
        </form>
        <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-people" aria-hidden="true"></i>
            <strong>Belum Ada Siswa Diterima</strong>
            <span>Belum ada siswa yang diterima di ekskul ini.</span>
        </div>
        <?php endif; ?>
    </section>


    <!-- RIWAYAT ABSENSI -->
    <section class="section-card">
        <div class="section-header">
            <div>
                <h2><i class="bi bi-clock-history me-1" aria-hidden="true"></i> Riwayat Absensi</h2>
                <small>20 tanggal terakhir yang sudah dicatat</small>
            </div>
        </div>

        <?php if (count($riwayatAbsensi) > 0): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th width="60">No</th>
                        <th>Tanggal</th>
                        <th class="text-center">Hadir</th>
                        <th class="text-center">Izin</th>
                        <th class="text-center">Sakit</th>
                        <th class="text-center">Alfa</th>
                        <th class="text-center">Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no = 1; foreach ($riwayatAbsensi as $r): ?>
                    <tr>
                        <td><div class="number-box"><?= $no++; ?></div></td>
                        <td>
                            <strong><?= date('d M Y', strtotime($r['tanggal'])); ?></strong>
                        </td>
                        <td class="text-center">
                            <span class="status-badge status-hadir"><?= (int)$r['hadir']; ?></span>
                        </td>
                        <td class="text-center">
                            <span class="status-badge status-izin"><?= (int)$r['izin']; ?></span>
                        </td>
                        <td class="text-center">
                            <span class="status-badge status-sakit"><?= (int)$r['sakit']; ?></span>
                        </td>
                        <td class="text-center">
                            <span class="status-badge status-alfa"><?= (int)$r['alfa']; ?></span>
                        </td>
                        <td class="text-center">
                            <strong><?= (int)$r['total']; ?></strong>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-calendar-x" aria-hidden="true"></i>
            <strong>Belum Ada Riwayat</strong>
            <span>Belum ada absensi yang dicatat untuk ekskul ini.</span>
        </div>
        <?php endif; ?>
    </section>


<?php else: ?>

    <section class="section-card">
        <div class="empty-state">
            <i class="bi bi-hand-index-thumb" aria-hidden="true"></i>
            <strong>Pilih Ekstrakurikuler Terlebih Dahulu</strong>
            <span>Pilih ekskul dan tanggal di atas, lalu klik "Tampilkan" untuk mulai input absensi.</span>
        </div>
    </section>

<?php endif; ?>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>