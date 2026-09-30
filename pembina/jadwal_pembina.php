<?php
/* =========================================================
   JADWAL PEMBINA - SISTEM INFORMASI EKSTRAKURIKULER
   Pembina lihat jadwal ekskul yang diampu
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


/* =========================================================
   AMBIL EKSKUL YANG DIAMPU
========================================================= */
$listEkskulPembina = [];

$stmtEks = mysqli_prepare($conn,
    "SELECT e.id, e.nama_ekskul, e.pembina, e.lokasi
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
   FILTER EKSKUL
========================================================= */
$filterEkskul = (int)($_GET['ekskul'] ?? 0);


/* =========================================================
   AMBIL DATA JADWAL
========================================================= */
$listJadwal = [];

if (count($ekskulIds) > 0) {

    /* Kalau ada filter, cek apakah filter = ekskul pembina */
    if ($filterEkskul > 0 && in_array($filterEkskul, $ekskulIds, true)) {

        $stmtJ = mysqli_prepare($conn,
            "SELECT j.id, j.ekskul_id, j.hari, j.jam_mulai, j.jam_selesai, j.lokasi,
                    e.nama_ekskul, e.pembina
             FROM jadwal j
             INNER JOIN ekstrakurikuler e ON j.ekskul_id = e.id
             WHERE j.ekskul_id = ?
             ORDER BY FIELD(j.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'),
                      j.jam_mulai ASC");

        if ($stmtJ) {
            mysqli_stmt_bind_param($stmtJ, "i", $filterEkskul);
            mysqli_stmt_execute($stmtJ);
            $resJ = mysqli_stmt_get_result($stmtJ);
            while ($r = mysqli_fetch_assoc($resJ)) {
                $listJadwal[] = $r;
            }
            mysqli_stmt_close($stmtJ);
        }

    } else {

        /* Tanpa filter — semua ekskul pembina */
        $placeholders = implode(",", array_fill(0, count($ekskulIds), "?"));
        $types = str_repeat("i", count($ekskulIds));

        $stmtJ = mysqli_prepare($conn,
            "SELECT j.id, j.ekskul_id, j.hari, j.jam_mulai, j.jam_selesai, j.lokasi,
                    e.nama_ekskul, e.pembina
             FROM jadwal j
             INNER JOIN ekstrakurikuler e ON j.ekskul_id = e.id
             WHERE j.ekskul_id IN ($placeholders)
             ORDER BY FIELD(j.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'),
                      j.jam_mulai ASC");

        if ($stmtJ) {
            mysqli_stmt_bind_param($stmtJ, $types, ...$ekskulIds);
            mysqli_stmt_execute($stmtJ);
            $resJ = mysqli_stmt_get_result($stmtJ);
            while ($r = mysqli_fetch_assoc($resJ)) {
                $listJadwal[] = $r;
            }
            mysqli_stmt_close($stmtJ);
        }
    }
}


/* =========================================================
   STATISTIK
========================================================= */
$totalEkskul = count($listEkskulPembina);
$totalJadwal = count($listJadwal);

/* Jadwal per hari */
$perHari = [];
foreach ($listJadwal as $j) {
    $h = $j['hari'] ?? '-';
    if (!isset($perHari[$h])) $perHari[$h] = 0;
    $perHari[$h]++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Jadwal - Pembina</title>

<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="dns-prefetch" href="https://cdn.jsdelivr.net">

<link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></noscript>

<link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"></noscript>

<style>
* { box-sizing: border-box; }
body { margin: 0; background: #f3f7fa; color: #172033; font-family: "Segoe UI", Arial, sans-serif; }

::-webkit-scrollbar { width: 0; height: 0; display: none; }
html { scrollbar-width: none; -ms-overflow-style: none; }
body::-webkit-scrollbar { display: none; }
.sidebar::-webkit-scrollbar { width: 0; display: none; }

.sidebar{position:fixed;left:0;top:0;width:255px;height:100vh;background:linear-gradient(180deg,#075985 0%,#0f766e 100%);color:#fff;padding:22px 15px;z-index:1000;box-shadow:8px 0 25px rgba(15,23,42,.08);overflow-y:auto;display:flex;flex-direction:column}
.brand{text-align:center;padding:8px 8px 23px;border-bottom:1px solid rgba(255,255,255,.15)}
.brand-icon{width:60px;height:60px;margin:auto;border-radius:17px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.14);font-size:27px}
.brand h4{margin:11px 0 3px;font-weight:700}.brand small{color:rgba(255,255,255,.85)}
.menu-title{margin:25px 11px 9px;color:rgba(255,255,255,.75);font-size:10px;text-transform:uppercase;letter-spacing:1px}
.sidebar a{display:flex;align-items:center;gap:13px;padding:12px 14px;margin-bottom:5px;border-radius:11px;color:rgba(255,255,255,.92);text-decoration:none;transition:.2s}
.sidebar a i{width:21px;text-align:center;font-size:17px}.sidebar a:hover{color:#fff;background:rgba(255,255,255,.12);transform:translateX(2px)}
.sidebar a.active{color:#fff;background:rgba(255,255,255,.18);box-shadow:inset 3px 0 0 #fff}
.logout{margin-top:auto;padding-top:20px;border-top:1px solid rgba(255,255,255,.15)}
.logout a{background:rgba(220,38,38,.9);color:#fff;display:flex;align-items:center;gap:13px;padding:12px 14px;border-radius:11px;text-decoration:none;font-weight:600;transition:.2s}
.logout a:hover{background:#dc2626;transform:translateX(2px)}

.content{margin-left:255px;padding:28px}

.topbar{background:white;min-height:72px;padding:14px 20px;border-radius:17px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 5px 22px rgba(15,23,42,.06);margin-bottom:22px}
.topbar-title h1{margin:0;font-size:18px;font-weight:700}
.topbar-title small{color:#475569}
.admin-area{display:flex;align-items:center;gap:11px}
.admin-info{text-align:right}
.admin-info strong{display:block;font-size:14px}
.admin-info small{color:#475569}
.admin-avatar{width:44px;height:44px;border-radius:13px;background:#e0f2fe;color:#0369a1;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:17px}

.welcome{position:relative;overflow:hidden;background:linear-gradient(135deg,#0284c7,#0f766e);color:white;border-radius:21px;padding:29px 31px;margin-bottom:22px;box-shadow:0 12px 30px rgba(14,116,144,.18)}
.welcome::before{content:"";position:absolute;width:220px;height:220px;border-radius:50%;background:rgba(255,255,255,.07);right:-65px;top:-100px}
.welcome-content{position:relative;z-index:2}
.welcome-badge{display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.16);padding:6px 11px;border-radius:20px;font-size:12px;margin-bottom:12px}
.welcome h2{margin:0 0 7px;font-size:27px;font-weight:700}
.welcome p{margin:0;color:rgba(255,255,255,.95)}

.stat-card{background:white;border-radius:17px;padding:20px;min-height:145px;box-shadow:0 5px 22px rgba(15,23,42,.055);border:1px solid #edf2f5}
.stat-top{display:flex;align-items:center;justify-content:space-between}
.stat-icon{width:45px;height:45px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:20px}
.icon-blue{background:#e0f2fe;color:#0284c7}
.icon-green{background:#dcfce7;color:#166534}
.icon-orange{background:#ffedd5;color:#c2410c}
.icon-teal{background:#ccfbf1;color:#0f766e}
.stat-number{font-size:27px;font-weight:750;margin-top:15px}
.stat-label{color:#475569;font-size:13px}

.section-card{background:white;border-radius:18px;box-shadow:0 5px 22px rgba(15,23,42,.055);border:1px solid #edf2f5;overflow:hidden}
.section-header{padding:18px 20px;border-bottom:1px solid #eef2f5;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px}
.section-header h2{margin:0;font-size:16px;font-weight:700;color:#172033}
.section-header small{color:#475569}

.filter-box{padding:20px;background:#f8fafc;border-bottom:1px solid #eef2f5}
.form-label{font-size:12px;font-weight:600;color:#475569}
.form-select{border-radius:10px;min-height:43px;font-size:13px}
.form-select:focus{border-color:#38bdf8;box-shadow:0 0 0 3px rgba(56,189,248,.12)}

.btn-primary-custom{display:inline-flex;align-items:center;gap:7px;border:none;border-radius:10px;padding:10px 15px;background:linear-gradient(135deg,#0284c7,#0f766e);color:white;font-size:13px;font-weight:600;text-decoration:none;transition:.2s;cursor:pointer}
.btn-primary-custom:hover{color:white;transform:translateY(-1px);box-shadow:0 8px 20px rgba(2,132,199,.25)}

.btn-reset{border:1px solid #e2e8f0;border-radius:10px;padding:10px 15px;background:white;color:#475569;font-size:13px;text-decoration:none;transition:.2s}
.btn-reset:hover{background:#f1f5f9;color:#334155}

.table{margin:0}
.table thead th{background:#f8fafc;border:none;color:#475569;font-size:11px;text-transform:uppercase;padding:13px 20px;white-space:nowrap}
.table tbody td{padding:14px 20px;vertical-align:middle;border-color:#f1f5f9;font-size:13px;color:#172033}
.table tbody tr:hover{background:#f8fafc}

.number-box{width:32px;height:32px;border-radius:10px;background:#f1f5f9;color:#475569;font-weight:600;font-size:12px;display:inline-flex;align-items:center;justify-content:center}

.ekskul-box{display:flex;align-items:center;gap:12px}
.ekskul-icon{width:40px;height:40px;flex-shrink:0;border-radius:11px;background:#e0f2fe;color:#0284c7;display:flex;align-items:center;justify-content:center;font-size:17px}
.ekskul-name{font-weight:600;font-size:13px;color:#172033}
.ekskul-sub{font-size:11px;color:#475569}

.hari-badge{display:inline-flex;align-items:center;background:#e0f2fe;color:#0369a1;padding:5px 10px;border-radius:20px;font-size:11px;font-weight:600}
.jam-box{display:inline-flex;align-items:center;gap:6px;color:#0f766e;font-weight:600;font-size:12px}
.lokasi-box{display:inline-flex;align-items:center;gap:6px;color:#475569;font-size:12px}

.empty-state{padding:45px 20px;text-align:center;color:#475569}
.empty-state i{font-size:35px;color:#94a3b8;display:block;margin-bottom:10px}
.empty-state strong{display:block;color:#172033;margin-bottom:4px}
.empty-state span{font-size:12px;color:#64748b}

@media (max-width: 992px) {
    .sidebar { width: 220px; }
    .content { margin-left: 220px; padding: 20px; }
}
@media (max-width: 768px) {
    .sidebar { position: relative; width: 100%; height: auto; min-height: auto; }
    .logout { margin-top: 20px; }
    .content { margin-left: 0; padding: 15px; }
    .topbar { flex-direction: column; align-items: flex-start; gap: 13px; }
    .admin-info { text-align: left; }
    .welcome h2 { font-size: 23px; }
    .section-header { flex-direction: column; align-items: flex-start; }
}
</style>
</head>
<body>

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

    <a href="absensi_pembina.php">
        <i class="bi bi-calendar-check-fill" aria-hidden="true"></i>
        <span>Absensi</span>
    </a>

    <a href="jadwal_pembina.php" class="active">
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
<main class="content">

    <header class="topbar">
        <div class="topbar-title">
            <h1 class="h5">Jadwal Ekskul</h1>
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
                <i class="bi bi-calendar-event-fill" aria-hidden="true"></i>
                Jadwal Kegiatan
            </div>
            <h2>Jadwal Ekstrakurikuler</h2>
            <p>Lihat jadwal kegiatan ekstrakurikuler yang Anda ampu.</p>
        </div>
    </section>


    <!-- STATISTIK -->
    <section class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-blue" aria-hidden="true"><i class="bi bi-stars"></i></div>
                </div>
                <div class="stat-number"><?= $totalEkskul; ?></div>
                <div class="stat-label">Ekskul Diampu</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-green" aria-hidden="true"><i class="bi bi-calendar-event-fill"></i></div>
                </div>
                <div class="stat-number"><?= $totalJadwal; ?></div>
                <div class="stat-label">Total Jadwal</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon icon-orange" aria-hidden="true"><i class="bi bi-calendar3"></i></div>
                </div>
                <div class="stat-number"><?= count($perHari); ?></div>
                <div class="stat-label">Hari Aktif</div>
            </div>
        </div>
    </section>


    <!-- FILTER -->
    <section class="section-card mb-4">
        <div class="section-header">
            <div>
                <h2><i class="bi bi-funnel-fill me-1" aria-hidden="true"></i> Filter Jadwal</h2>
                <small>Pilih ekskul untuk melihat jadwal tertentu</small>
            </div>
        </div>

        <div class="filter-box">
            <form method="GET" action="jadwal_pembina.php">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label" for="ekskul">Ekstrakurikuler</label>
                        <select name="ekskul" id="ekskul" class="form-select">
                            <option value="">Semua Ekskul</option>
                            <?php foreach ($listEkskulPembina as $e): ?>
                                <option value="<?= (int)$e['id']; ?>"
                                    <?= $filterEkskul === (int)$e['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($e['nama_ekskul']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn-primary-custom flex-grow-1">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            Tampilkan
                        </button>
                        <?php if ($filterEkskul > 0): ?>
                            <a href="jadwal_pembina.php" class="btn-reset d-flex align-items-center" title="Reset">
                                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </section>


    <!-- DAFTAR JADWAL -->
    <section class="section-card">
        <div class="section-header">
            <div>
                <h2><i class="bi bi-calendar-week me-1" aria-hidden="true"></i> Daftar Jadwal</h2>
                <small>Menampilkan <?= count($listJadwal); ?> jadwal</small>
            </div>
        </div>

        <?php if (count($listJadwal) > 0): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th width="60">No</th>
                        <th>Ekstrakurikuler</th>
                        <th>Hari</th>
                        <th>Waktu</th>
                        <th>Lokasi</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no = 1; foreach ($listJadwal as $j): ?>
                    <tr>
                        <td><div class="number-box"><?= $no++; ?></div></td>
                        <td>
                            <div class="ekskul-box">
                                <div class="ekskul-icon" aria-hidden="true"><i class="bi bi-stars"></i></div>
                                <div>
                                    <div class="ekskul-name"><?= htmlspecialchars($j['nama_ekskul']); ?></div>
                                    <div class="ekskul-sub">Pembina: <?= htmlspecialchars($j['pembina'] ?? '-'); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="hari-badge">
                                <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                                <?= htmlspecialchars($j['hari']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="jam-box">
                                <i class="bi bi-clock" aria-hidden="true"></i>
                                <span>
                                    <?= htmlspecialchars(substr($j['jam_mulai'], 0, 5)); ?>
                                    -
                                    <?= htmlspecialchars(substr($j['jam_selesai'], 0, 5)); ?>
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="lokasi-box">
                                <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                                <?= htmlspecialchars($j['lokasi']); ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-calendar-x" aria-hidden="true"></i>
            <strong>Belum Ada Jadwal</strong>
            <span>Belum ada jadwal untuk ekskul yang Anda ampu.</span>
        </div>
        <?php endif; ?>
    </section>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>