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
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "siswa") {
    header("Location: ../login.php");
    exit();
}

/* =====================================================
   DATA USER
===================================================== */
$user_id = (int) $_SESSION['id'];
$nama_user = $_SESSION['nama'] ?? "Siswa";

/* =====================================================
   DATA SISWA
===================================================== */
$stmtSiswa = mysqli_prepare(
    $conn,
    "SELECT id, user_id, nama_siswa, nis, kelas
     FROM siswa
     WHERE user_id = ?
     LIMIT 1"
);

$siswa = null;
$pesan = "";

if ($stmtSiswa) {
    mysqli_stmt_bind_param($stmtSiswa, "i", $user_id);
    mysqli_stmt_execute($stmtSiswa);
    $hasilSiswa = mysqli_stmt_get_result($stmtSiswa);
    $siswa = mysqli_fetch_assoc($hasilSiswa) ?: null;
    mysqli_stmt_close($stmtSiswa);
}

if (!$siswa) {
    $pesan = "Data siswa belum terhubung dengan akun ini.";
}

$siswa_id = $siswa ? (int) $siswa['id'] : 0;
$namaTampilan = $siswa['nama_siswa'] ?? $nama_user;
$inisial = strtoupper(substr(trim($namaTampilan), 0, 1));
$inisial = $inisial !== '' ? $inisial : 'S';

/* =====================================================
   DATA JADWAL SISWA
   Hanya jadwal dari pendaftaran yang diterima.
===================================================== */
$jadwalSaya = [];

if ($siswa_id > 0) {

    $stmtJadwal = mysqli_prepare(
        $conn,
        "SELECT
            j.id,
            j.ekskul_id,
            j.hari,
            j.jam_mulai,
            j.jam_selesai,
            j.lokasi,
            e.nama_ekskul,
            e.pembina
         FROM pendaftaran p
         INNER JOIN jadwal j
            ON p.ekskul_id = j.ekskul_id
         INNER JOIN ekstrakurikuler e
            ON j.ekskul_id = e.id
         WHERE p.siswa_id = ?
           AND p.status = 'diterima'
         ORDER BY
            FIELD(
                j.hari,
                'Senin',
                'Selasa',
                'Rabu',
                'Kamis',
                'Jumat',
                'Sabtu',
                'Minggu'
            ),
            j.jam_mulai ASC"
    );

    if ($stmtJadwal) {
        mysqli_stmt_bind_param($stmtJadwal, "i", $siswa_id);
        mysqli_stmt_execute($stmtJadwal);
        $hasilJadwal = mysqli_stmt_get_result($stmtJadwal);

        while ($row = mysqli_fetch_assoc($hasilJadwal)) {
            $jadwalSaya[] = $row;
        }

        mysqli_stmt_close($stmtJadwal);
    }
}

$totalJadwal = count($jadwalSaya);

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Jadwal Saya - Sistem Informasi Ekstrakurikuler</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
*{box-sizing:border-box}

body{
    margin:0;
    background:#f4f8fb;
    color:#172033;
    font-family:"Segoe UI",Arial,sans-serif;
}

/* SIDEBAR */
.sidebar{
    position:fixed;
    left:0;
    top:0;
    width:255px;
    height:100vh;
    padding:22px 15px;
    background:linear-gradient(180deg,#075985 0%,#0f766e 100%);
    color:#fff;
    z-index:1000;
    box-shadow:8px 0 25px rgba(15,23,42,.08);
}

.brand{
    padding:8px 8px 22px;
    border-bottom:1px solid rgba(255,255,255,.15);
    text-align:center;
}

.brand-icon{
    width:60px;
    height:60px;
    margin:auto;
    border-radius:18px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:rgba(255,255,255,.14);
    font-size:27px;
}

.brand h4{margin:11px 0 3px;font-size:18px;font-weight:700}
.brand small{color:rgba(255,255,255,.68)}

.menu-title{
    margin:25px 11px 9px;
    color:rgba(255,255,255,.55);
    font-size:10px;
    text-transform:uppercase;
    letter-spacing:1px;
}

.sidebar a{
    display:flex;
    align-items:center;
    gap:13px;
    padding:12px 14px;
    margin-bottom:5px;
    border-radius:11px;
    color:rgba(255,255,255,.84);
    text-decoration:none;
    transition:.2s;
}

.sidebar a i{width:21px;text-align:center;font-size:17px}
.sidebar a:hover{color:#fff;background:rgba(255,255,255,.12);transform:translateX(2px)}
.sidebar a.active{color:#fff;background:rgba(255,255,255,.18);box-shadow:inset 3px 0 0 #fff}

.logout{position:absolute;left:15px;right:15px;bottom:18px}
.logout a{background:rgba(220,38,38,.88);color:#fff}
.logout a:hover{background:#dc2626}

/* CONTENT */
.content{
    margin-left:255px;
    padding:28px;
    min-height:100vh;
}

.topbar{
    background:#fff;
    min-height:72px;
    padding:14px 20px;
    border-radius:18px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    box-shadow:0 5px 22px rgba(15,23,42,.06);
    margin-bottom:22px;
}

.topbar-title h5{margin:0 0 2px;font-size:18px;font-weight:700}
.topbar-title small{color:#64748b}

.user-area{display:flex;align-items:center;gap:11px}
.user-info{text-align:right}
.user-info strong{display:block;font-size:14px}
.user-info small{color:#64748b}

.avatar{
    width:44px;
    height:44px;
    border-radius:13px;
    background:#e0f2fe;
    color:#0369a1;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
    font-size:16px;
}

/* HERO */
.page-header{
    position:relative;
    overflow:hidden;
    border-radius:22px;
    padding:29px 31px;
    margin-bottom:20px;
    color:#fff;
    background:linear-gradient(135deg,#0284c7 0%,#0f766e 100%);
    box-shadow:0 12px 30px rgba(14,116,144,.16);
}

.page-header::before{
    content:"";
    position:absolute;
    width:240px;
    height:240px;
    border-radius:50%;
    background:rgba(255,255,255,.07);
    right:-70px;
    top:-115px;
}

.page-header::after{
    content:"";
    position:absolute;
    width:120px;
    height:120px;
    border-radius:50%;
    background:rgba(255,255,255,.06);
    right:115px;
    bottom:-75px;
}

.page-header-content{position:relative;z-index:2}

.page-badge{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:8px 13px;
    border-radius:20px;
    background:rgba(255,255,255,.11);
    border:1px solid rgba(255,255,255,.2);
    font-size:12px;
    margin-bottom:13px;
}

.page-header h2{margin:0 0 6px;font-size:28px;font-weight:750}
.page-header p{margin:0;color:rgba(255,255,255,.84);font-size:14px;max-width:720px}

/* SUMMARY */
.summary-row{display:grid;grid-template-columns:1.2fr .8fr;gap:16px;margin-bottom:20px}

.profile-mini{
    background:linear-gradient(135deg,#ecfeff,#f0fdfa);
    border:1px solid #c8f1eb;
    border-radius:18px;
    padding:18px 20px;
}

.profile-mini-head{display:flex;align-items:center;gap:13px}
.profile-mini-avatar{
    width:52px;height:52px;border-radius:15px;
    background:linear-gradient(135deg,#0284c7,#0f766e);
    color:#fff;display:flex;align-items:center;justify-content:center;
    font-weight:700;font-size:19px;flex:0 0 auto;
}
.profile-mini strong{display:block;font-size:15px}
.profile-mini small{display:block;color:#64748b;margin-top:2px}

.total-card{
    background:#fff;
    border:1px solid #e8eef3;
    border-radius:18px;
    padding:18px 20px;
    box-shadow:0 5px 22px rgba(15,23,42,.045);
    display:flex;
    align-items:center;
    gap:15px;
}

.total-icon{
    width:52px;height:52px;border-radius:15px;
    display:flex;align-items:center;justify-content:center;
    background:#e0f2fe;color:#0284c7;font-size:21px;flex:0 0 auto;
}
.total-number{font-size:25px;font-weight:750;line-height:1}
.total-label{font-size:12px;color:#64748b;margin-top:4px}

/* SCHEDULE WRAPPER */
.section-card{
    background:#fff;
    border:1px solid #e8eef3;
    border-radius:20px;
    box-shadow:0 6px 24px rgba(15,23,42,.05);
    overflow:hidden;
}

.section-head{
    padding:19px 22px;
    border-bottom:1px solid #eef2f5;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
}

.section-head h5{margin:0;font-size:17px;font-weight:700}
.section-head p{margin:3px 0 0;color:#64748b;font-size:12px}

.schedule-list{padding:18px}

/* SCHEDULE CARD */
.schedule-card{
    position:relative;
    height:100%;
    border:1px solid #e7eef3;
    border-radius:18px;
    padding:20px;
    background:linear-gradient(180deg,#fff 0%,#fbfdfe 100%);
    box-shadow:0 5px 18px rgba(15,23,42,.045);
    transition:.2s;
}

.schedule-card:hover{
    transform:translateY(-3px);
    box-shadow:0 12px 26px rgba(15,23,42,.09);
    border-color:#cfe7ef;
}

.schedule-top{display:flex;align-items:flex-start;gap:13px}

.schedule-icon{
    width:50px;height:50px;border-radius:15px;
    background:#e0f2fe;color:#0284c7;
    display:flex;align-items:center;justify-content:center;
    font-size:22px;flex:0 0 auto;
}

.schedule-title{min-width:0;flex:1}
.schedule-title h5{margin:1px 0 4px;font-size:18px;font-weight:750;word-break:break-word}
.schedule-title small{color:#64748b;font-size:12px}

.day-badge{
    display:inline-flex;align-items:center;gap:5px;
    padding:6px 9px;border-radius:10px;
    background:#ecfeff;color:#0f766e;
    font-size:11px;font-weight:700;white-space:nowrap;
}

.schedule-divider{height:1px;background:#eef2f5;margin:17px 0 15px}

.schedule-meta{display:grid;grid-template-columns:1fr;gap:10px}

.meta-item{
    display:flex;align-items:flex-start;gap:10px;
    padding:10px 11px;border-radius:11px;background:#f8fafc;
}

.meta-icon{
    width:30px;height:30px;border-radius:9px;
    display:flex;align-items:center;justify-content:center;
    background:#fff;color:#0284c7;font-size:13px;flex:0 0 auto;
}

.meta-text small{display:block;font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px}
.meta-text strong{display:block;margin-top:2px;font-size:12px;color:#334155;word-break:break-word}

.empty-state{text-align:center;padding:60px 25px;color:#94a3b8}
.empty-state i{display:block;font-size:48px;margin-bottom:12px;color:#cbd5e1}
.empty-state strong{display:block;color:#475569;font-size:16px}
.empty-state p{margin:6px 0 18px;font-size:13px}

.btn-daftar{
    display:inline-flex;align-items:center;gap:6px;
    border:none;border-radius:10px;padding:10px 15px;
    background:linear-gradient(135deg,#0284c7,#0f766e);
    color:#fff;text-decoration:none;font-size:12px;font-weight:650;
}
.btn-daftar:hover{color:#fff;box-shadow:0 7px 18px rgba(2,132,199,.2)}

.info-bar{
    margin-top:20px;
    padding:15px 17px;
    border-radius:14px;
    background:#eff6ff;
    border:1px solid #dbeafe;
    color:#475569;
    font-size:12px;
}

/* RESPONSIVE */
@media(max-width:1100px){
    .summary-row{grid-template-columns:1fr}
}

@media(max-width:992px){
    .sidebar{width:220px}
    .content{margin-left:220px;padding:20px}
}

@media(max-width:768px){
    .sidebar{position:relative;width:100%;height:auto;min-height:auto}
    .logout{position:static;margin-top:20px}
    .content{margin-left:0;padding:15px}
    .topbar{flex-direction:column;align-items:flex-start;gap:13px}
    .user-info{text-align:left}
    .page-header{padding:24px 22px}
    .page-header h2{font-size:23px}
    .schedule-list{padding:14px}
}
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
        <i class="bi bi-grid-1x2-fill"></i>
        <span>Dashboard</span>
    </a>

    <a href="daftar_ekskul.php">
        <i class="bi bi-stars"></i>
        <span>Daftar Ekskul</span>
    </a>

    <a href="pendaftaran_saya.php">
        <i class="bi bi-person-check-fill"></i>
        <span>Pendaftaran Saya</span>
    </a>

    <a href="jadwal_saya.php" class="active">
        <i class="bi bi-calendar-event-fill"></i>
        <span>Jadwal Saya</span>
    </a>

    <a href="absensi_saya.php">
        <i class="bi bi-calendar-check-fill"></i>
        <span>Absensi Saya</span>
    </a>

    <a href="profil.php">
        <i class="bi bi-person-fill"></i>
        <span>Profil</span>
    </a>

    <div class="logout">
        <a href="../logout.php">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<div class="content">

    <div class="topbar">
        <div class="topbar-title">
            <h5>Jadwal Saya</h5>
            <small>Sistem Informasi Ekstrakurikuler</small>
        </div>

        <div class="user-area">
            <div class="user-info">
                <strong><?= htmlspecialchars($namaTampilan); ?></strong>
                <small>Siswa</small>
            </div>
            <div class="avatar"><?= htmlspecialchars($inisial); ?></div>
        </div>
    </div>

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-badge">
                <i class="bi bi-calendar-event-fill"></i>
                Jadwal Kegiatan
            </div>
            <h2>Jadwal Ekstrakurikuler Saya</h2>
            <p>Lihat jadwal kegiatan ekstrakurikuler yang sudah kamu daftarkan dan diterima oleh admin.</p>
        </div>
    </div>

    <?php if ($pesan !== ''): ?>
        <div class="alert alert-warning border-0 rounded-4 mb-4">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <?= htmlspecialchars($pesan); ?>
        </div>
    <?php endif; ?>

    <div class="summary-row">
        <div class="profile-mini">
            <div class="profile-mini-head">
                <div class="profile-mini-avatar"><?= htmlspecialchars($inisial); ?></div>
                <div>
                    <strong><?= htmlspecialchars($namaTampilan); ?></strong>
                    <small>
                        <?= !empty($siswa['kelas']) ? htmlspecialchars($siswa['kelas']) : 'Siswa'; ?>
                        <?php if (!empty($siswa['nis'])): ?>
                            &nbsp;•&nbsp; NIS <?= htmlspecialchars($siswa['nis']); ?>
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>

        <div class="total-card">
            <div class="total-icon"><i class="bi bi-calendar2-week-fill"></i></div>
            <div>
                <div class="total-number"><?= $totalJadwal; ?></div>
                <div class="total-label">Jadwal yang kamu miliki</div>
            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-head">
            <div>
                <h5>Jadwal Kegiatan</h5>
                <p>Jadwal hanya menampilkan ekstrakurikuler yang status pendaftarannya <strong>diterima</strong>.</p>
            </div>
            <span class="badge rounded-pill text-bg-light px-3 py-2">
                <?= $totalJadwal; ?> Jadwal
            </span>
        </div>

        <div class="schedule-list">
            <?php if ($totalJadwal > 0): ?>
                <div class="row g-3">
                    <?php foreach ($jadwalSaya as $jadwal): ?>
                        <div class="col-xl-4 col-md-6">
                            <div class="schedule-card">
                                <div class="schedule-top">
                                    <div class="schedule-icon">
                                        <i class="bi bi-calendar-event-fill"></i>
                                    </div>
                                    <div class="schedule-title">
                                        <h5><?= htmlspecialchars($jadwal['nama_ekskul'] ?? '-'); ?></h5>
                                        <small>
                                            <i class="bi bi-person-badge me-1"></i>
                                            Pembina: <?= htmlspecialchars($jadwal['pembina'] ?? '-'); ?>
                                        </small>
                                    </div>
                                </div>

                                <div class="schedule-divider"></div>

                                <div class="schedule-meta">
                                    <div class="meta-item">
                                        <div class="meta-icon"><i class="bi bi-calendar3"></i></div>
                                        <div class="meta-text">
                                            <small>Hari</small>
                                            <strong><?= htmlspecialchars($jadwal['hari'] ?? '-'); ?></strong>
                                        </div>
                                    </div>

                                    <div class="meta-item">
                                        <div class="meta-icon"><i class="bi bi-clock-fill"></i></div>
                                        <div class="meta-text">
                                            <small>Waktu</small>
                                            <strong>
                                                <?= htmlspecialchars(substr($jadwal['jam_mulai'] ?? '', 0, 5)); ?>
                                                -
                                                <?= htmlspecialchars(substr($jadwal['jam_selesai'] ?? '', 0, 5)); ?>
                                            </strong>
                                        </div>
                                    </div>

                                    <div class="meta-item">
                                        <div class="meta-icon"><i class="bi bi-geo-alt-fill"></i></div>
                                        <div class="meta-text">
                                            <small>Lokasi</small>
                                            <strong><?= htmlspecialchars($jadwal['lokasi'] ?? '-'); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <strong>Belum Ada Jadwal</strong>
                    <p>Kamu belum memiliki jadwal ekstrakurikuler yang diterima.</p>
                    <a href="daftar_ekskul.php" class="btn-daftar">
                        <i class="bi bi-stars"></i>
                        Daftar Ekskul
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="info-bar">
        <i class="bi bi-info-circle-fill me-2"></i>
        Jadwal akan otomatis muncul setelah pendaftaran ekstrakurikuler kamu disetujui oleh admin.
    </div>

</div>

</body>
</html>
