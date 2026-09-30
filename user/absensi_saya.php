<?php
session_start();
require_once __DIR__ . "/../config/config.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "siswa") {
    header("Location: ../login.php");
    exit();
}

$user_id = (int) $_SESSION['id'];
$nama_user = $_SESSION['nama'] ?? "Siswa";

$siswa = null;
$querySiswa = mysqli_query($conn, "SELECT * FROM siswa WHERE user_id = '$user_id' LIMIT 1");
if ($querySiswa && mysqli_num_rows($querySiswa) > 0) {
    $siswa = mysqli_fetch_assoc($querySiswa);
}

$namaTampilan = ($siswa && !empty($siswa['nama_siswa'])) ? $siswa['nama_siswa'] : $nama_user;
$siswa_id = $siswa ? (int)$siswa['id'] : 0;

$totalPendaftaran = 0;
$totalDiterima = 0;
$totalJadwal = 0;
$totalAbsensi = 0;

if ($siswa_id > 0) {
    $q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pendaftaran WHERE siswa_id = '$siswa_id'");
    if ($q) $totalPendaftaran = (int)(mysqli_fetch_assoc($q)['total'] ?? 0);

    $q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pendaftaran WHERE siswa_id = '$siswa_id' AND status = 'diterima'");
    if ($q) $totalDiterima = (int)(mysqli_fetch_assoc($q)['total'] ?? 0);

    $q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM jadwal j INNER JOIN pendaftaran p ON j.ekskul_id = p.ekskul_id WHERE p.siswa_id = '$siswa_id' AND p.status = 'diterima'");
    if ($q) $totalJadwal = (int)(mysqli_fetch_assoc($q)['total'] ?? 0);

    $q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM absensi WHERE siswa_id = '$siswa_id'");
    if ($q) $totalAbsensi = (int)(mysqli_fetch_assoc($q)['total'] ?? 0);
}

$dataEkskul = false;
if ($siswa_id > 0) {
    $dataEkskul = mysqli_query($conn, "SELECT e.id, e.nama_ekskul, e.pembina, p.status FROM pendaftaran p INNER JOIN ekstrakurikuler e ON p.ekskul_id = e.id WHERE p.siswa_id = '$siswa_id' AND p.status = 'diterima' ORDER BY p.id DESC LIMIT 3");
}

$dataJadwal = false;
if ($siswa_id > 0) {
    $dataJadwal = mysqli_query($conn, "SELECT j.id, j.ekskul_id, j.hari, j.jam_mulai, j.jam_selesai, j.lokasi, e.nama_ekskul FROM jadwal j INNER JOIN pendaftaran p ON j.ekskul_id = p.ekskul_id INNER JOIN ekstrakurikuler e ON j.ekskul_id = e.id WHERE p.siswa_id = '$siswa_id' AND p.status = 'diterima' ORDER BY j.id DESC LIMIT 5");
}

$inisial = strtoupper(substr(trim($namaTampilan), 0, 1));

/* =====================================================
   ROUTE DASHBOARD SISWA
===================================================== */

$page = $_GET['page'] ?? 'dashboard';
$isDaftarEkskul = ($page === 'daftar_ekskul');
$isPendaftaranSaya = ($page === 'pendaftaran_saya');
$isJadwalSaya = ($page === 'jadwal_saya');
$isAbsensiSaya = ($page === 'absensi_saya');

/* =====================================================
   DATA UNTUK HALAMAN DAFTAR EKSKUL
   Fungsi pendaftaran tetap memakai logika asli
===================================================== */

$success = "";
$error = "";
$dataEkskulForm = false;
$pendaftaranSaya = [];

if ($isDaftarEkskul) {

    /* -------------------------------------------------
       PROSES PENDAFTARAN
    ------------------------------------------------- */

    if (isset($_POST['daftar_ekskul'])) {

        if ($siswa_id <= 0) {

            $error = "Data siswa belum terhubung dengan akun ini.";

        } else {

            $ekskul_id = isset($_POST['ekskul_id'])
                ? (int) $_POST['ekskul_id']
                : 0;

            if ($ekskul_id <= 0) {

                $error = "Ekstrakurikuler tidak valid.";

            } else {

                $cekEkskul = mysqli_query(
                    $conn,
                    "SELECT id, nama_ekskul
                     FROM ekstrakurikuler
                     WHERE id = '$ekskul_id'
                     LIMIT 1"
                );

                if (!$cekEkskul || mysqli_num_rows($cekEkskul) == 0) {

                    $error = "Ekstrakurikuler tidak ditemukan.";

                } else {

                    $dataEkskulDaftar = mysqli_fetch_assoc($cekEkskul);

                    $cekPendaftaran = mysqli_query(
                        $conn,
                        "SELECT id, status
                         FROM pendaftaran
                         WHERE siswa_id = '$siswa_id'
                         AND ekskul_id = '$ekskul_id'
                         LIMIT 1"
                    );

                    if (!$cekPendaftaran) {

                        $error =
                            "Gagal mengecek pendaftaran: "
                            . mysqli_error($conn);

                    } elseif (mysqli_num_rows($cekPendaftaran) > 0) {

                        $dataPendaftaran =
                            mysqli_fetch_assoc($cekPendaftaran);

                        $status =
                            strtolower(
                                trim(
                                    $dataPendaftaran['status']
                                )
                            );

                        if ($status === "ditolak") {

                            $update = mysqli_query(
                                $conn,
                                "UPDATE pendaftaran
                                 SET status = 'menunggu'
                                 WHERE id = '"
                                 . (int) $dataPendaftaran['id']
                                 . "'"
                            );

                            if ($update) {

                                $success =
                                    "Pendaftaran berhasil dikirim kembali. "
                                    . "Silakan menunggu persetujuan admin.";

                            } else {

                                $error =
                                    "Pendaftaran gagal: "
                                    . mysqli_error($conn);

                            }

                        } else {

                            $error =
                                "Kamu sudah mendaftar "
                                . htmlspecialchars(
                                    $dataEkskulDaftar['nama_ekskul']
                                )
                                . ". Status pendaftaran: "
                                . htmlspecialchars(
                                    ucfirst($status)
                                )
                                . ".";

                        }

                    } else {

                        $insert = mysqli_query(
                            $conn,
                            "INSERT INTO pendaftaran
                            (siswa_id, ekskul_id, status)
                            VALUES
                            ('$siswa_id', '$ekskul_id', 'menunggu')"
                        );

                        if ($insert) {

                            $success =
                                "Berhasil mendaftar "
                                . htmlspecialchars(
                                    $dataEkskulDaftar['nama_ekskul']
                                )
                                . ". Silakan menunggu persetujuan admin.";

                        } else {

                            $error =
                                "Pendaftaran gagal: "
                                . mysqli_error($conn);

                        }
                    }
                }
            }
        }
    }

    /* -------------------------------------------------
       DATA SEMUA EKSKUL
    ------------------------------------------------- */

    $dataEkskulForm = mysqli_query(
        $conn,
        "SELECT *
         FROM ekstrakurikuler
         ORDER BY nama_ekskul ASC"
    );

    /* -------------------------------------------------
       PENDAFTARAN SISWA
    ------------------------------------------------- */

    if ($siswa_id > 0) {

        $querySaya = mysqli_query(
            $conn,
            "SELECT ekskul_id, status
             FROM pendaftaran
             WHERE siswa_id = '$siswa_id'"
        );

        if ($querySaya) {

            while ($row = mysqli_fetch_assoc($querySaya)) {

                $pendaftaranSaya[
                    (int) $row['ekskul_id']
                ] = strtolower(
                    trim(
                        $row['status']
                    )
                );
            }
        }
    }
}

/* =====================================================
   DATA HALAMAN JADWAL SAYA
   Mengikuti query asli jadwal_saya.php
===================================================== */

$jadwalSaya = false;
$pesanJadwalSaya = "";
$namaUserJadwal = $_SESSION['nama'] ?? "Siswa";

if ($isJadwalSaya) {

    $stmtSiswaJadwal = mysqli_prepare(
        $conn,
        "SELECT
            id,
            user_id,
            nama_siswa
         FROM siswa
         WHERE user_id = ?
         LIMIT 1"
    );

    if (!$stmtSiswaJadwal) {
        die(
            "Query data siswa gagal: " .
            mysqli_error($conn)
        );
    }

    mysqli_stmt_bind_param(
        $stmtSiswaJadwal,
        "i",
        $user_id
    );

    mysqli_stmt_execute($stmtSiswaJadwal);

    $resultSiswaJadwal =
        mysqli_stmt_get_result(
            $stmtSiswaJadwal
        );

    $dataSiswaJadwal =
        mysqli_fetch_assoc(
            $resultSiswaJadwal
        );

    if ($dataSiswaJadwal) {

        $siswa_id_jadwal =
            (int) $dataSiswaJadwal['id'];

        $namaSiswaJadwal =
            $dataSiswaJadwal['nama_siswa'];

        $stmtJadwalSaya = mysqli_prepare(
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

             WHERE
                p.siswa_id = ?
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

        if (!$stmtJadwalSaya) {
            die(
                "Query jadwal gagal: " .
                mysqli_error($conn)
            );
        }

        mysqli_stmt_bind_param(
            $stmtJadwalSaya,
            "i",
            $siswa_id_jadwal
        );

        mysqli_stmt_execute(
            $stmtJadwalSaya
        );

        $jadwalSaya =
            mysqli_stmt_get_result(
                $stmtJadwalSaya
            );

    } else {

        $pesanJadwalSaya =
            "Data siswa belum terhubung dengan akun ini.";
    }
}

/* =====================================================
   DATA HALAMAN PENDAFTARAN SAYA
   Fungsi/query mengikuti kode asli
===================================================== */

$totalMenungguPS = 0;
$totalDiterimaPS = 0;
$totalDitolakPS = 0;
$totalPendaftaranPS = 0;
$dataPendaftaranPS = false;

if ($isPendaftaranSaya) {

    if ($siswa_id > 0) {

        $queryTotalPS = mysqli_query(
            $conn,
            "SELECT COUNT(*) AS total
             FROM pendaftaran
             WHERE siswa_id = '$siswa_id'"
        );

        if ($queryTotalPS) {
            $dataPS = mysqli_fetch_assoc($queryTotalPS);
            $totalPendaftaranPS = (int) ($dataPS['total'] ?? 0);
        }

        $queryMenungguPS = mysqli_query(
            $conn,
            "SELECT COUNT(*) AS total
             FROM pendaftaran
             WHERE siswa_id = '$siswa_id'
             AND status = 'menunggu'"
        );

        if ($queryMenungguPS) {
            $dataPS = mysqli_fetch_assoc($queryMenungguPS);
            $totalMenungguPS = (int) ($dataPS['total'] ?? 0);
        }

        $queryDiterimaPS = mysqli_query(
            $conn,
            "SELECT COUNT(*) AS total
             FROM pendaftaran
             WHERE siswa_id = '$siswa_id'
             AND status = 'diterima'"
        );

        if ($queryDiterimaPS) {
            $dataPS = mysqli_fetch_assoc($queryDiterimaPS);
            $totalDiterimaPS = (int) ($dataPS['total'] ?? 0);
        }

        $queryDitolakPS = mysqli_query(
            $conn,
            "SELECT COUNT(*) AS total
             FROM pendaftaran
             WHERE siswa_id = '$siswa_id'
             AND status = 'ditolak'"
        );

        if ($queryDitolakPS) {
            $dataPS = mysqli_fetch_assoc($queryDitolakPS);
            $totalDitolakPS = (int) ($dataPS['total'] ?? 0);
        }

        $dataPendaftaranPS = mysqli_query(
            $conn,
            "SELECT
                p.id,
                p.siswa_id,
                p.ekskul_id,
                p.status,
                e.nama_ekskul,
                e.pembina
             FROM pendaftaran p
             LEFT JOIN ekstrakurikuler e
                ON p.ekskul_id = e.id
             WHERE p.siswa_id = '$siswa_id'
             ORDER BY p.id DESC"
        );

        if (!$dataPendaftaranPS) {
            die(
                "Query pendaftaran siswa error: " .
                mysqli_error($conn)
            );
        }
    }
}

/* =====================================================
   DATA HALAMAN ABSENSI SAYA
   Mengikuti logika asli absensi_saya.php
===================================================== */

$dataAbsensiAS = false;
$totalAbsensiAS = 0;
$totalHadirAS = 0;
$totalIzinAS = 0;
$totalSakitAS = 0;
$totalAlfaAS = 0;
$persentaseHadirAS = 0;

if ($isAbsensiSaya) {

    if ($siswa_id > 0) {

        $dataAbsensiAS = mysqli_query(
            $conn,
            "SELECT
                a.id,
                a.tanggal,
                a.status_hadir,
                e.nama_ekskul
             FROM absensi a
             INNER JOIN ekstrakurikuler e
                ON a.ekskul_id = e.id
             WHERE a.siswa_id = '$siswa_id'
             ORDER BY a.tanggal DESC, a.id DESC"
        );

        $queryStatAS = mysqli_query(
            $conn,
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status_hadir = 'hadir' THEN 1 ELSE 0 END) AS hadir,
                SUM(CASE WHEN status_hadir = 'izin' THEN 1 ELSE 0 END) AS izin,
                SUM(CASE WHEN status_hadir = 'sakit' THEN 1 ELSE 0 END) AS sakit,
                SUM(CASE WHEN status_hadir = 'alfa' THEN 1 ELSE 0 END) AS alfa
             FROM absensi
             WHERE siswa_id = '$siswa_id'"
        );

        if ($queryStatAS) {

            $statAS = mysqli_fetch_assoc($queryStatAS);

            $totalAbsensiAS = (int) ($statAS['total'] ?? 0);
            $totalHadirAS = (int) ($statAS['hadir'] ?? 0);
            $totalIzinAS = (int) ($statAS['izin'] ?? 0);
            $totalSakitAS = (int) ($statAS['sakit'] ?? 0);
            $totalAlfaAS = (int) ($statAS['alfa'] ?? 0);
        }

        if ($totalAbsensiAS > 0) {
            $persentaseHadirAS = round(
                ($totalHadirAS / $totalAbsensiAS) * 100
            );
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Siswa - Sistem Informasi Ekstrakurikuler</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
*{box-sizing:border-box}body{margin:0;background:#f3f7fa;color:#172033;font-family:"Segoe UI",Arial,sans-serif}
.sidebar{position:fixed;left:0;top:0;width:255px;height:100vh;background:linear-gradient(180deg,#075985 0%,#0f766e 100%);color:#fff;padding:22px 15px 80px;z-index:1000;box-shadow:8px 0 25px rgba(15,23,42,.08);overflow-y:auto}
.brand{text-align:center;padding:8px 8px 23px;border-bottom:1px solid rgba(255,255,255,.15)}
.brand-icon{width:60px;height:60px;margin:auto;border-radius:17px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.14);font-size:27px}
.brand h4{margin:11px 0 3px;font-weight:700}.brand small{color:rgba(255,255,255,.65)}
.menu-title{margin:25px 11px 9px;color:rgba(255,255,255,.55);font-size:10px;text-transform:uppercase;letter-spacing:1px}
.sidebar a{display:flex;align-items:center;gap:13px;padding:12px 14px;margin-bottom:5px;border-radius:11px;color:rgba(255,255,255,.84);text-decoration:none;transition:.2s}
.sidebar a i{width:21px;text-align:center;font-size:17px}.sidebar a:hover{color:#fff;background:rgba(255,255,255,.12);transform:translateX(2px)}
.sidebar a.active{color:#fff;background:rgba(255,255,255,.18);box-shadow:inset 3px 0 0 #fff}
.logout{position:absolute;left:15px;right:15px;bottom:18px}.logout a{background:rgba(220,38,38,.85);color:#fff}.logout a:hover{background:#dc2626}
.content{margin-left:255px;padding:28px}.topbar{background:#fff;min-height:72px;padding:14px 20px;border-radius:17px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 5px 22px rgba(15,23,42,.06);margin-bottom:22px}
.topbar-title h5{margin:0;font-size:18px;font-weight:700}.topbar-title small{color:#64748b}.user-area{display:flex;align-items:center;gap:11px}.user-text{text-align:right}.user-text strong{display:block;font-size:14px}.user-text small{color:#64748b}.avatar{width:44px;height:44px;border-radius:13px;background:#e0f2fe;color:#0369a1;display:flex;align-items:center;justify-content:center;font-weight:700}
.student-header{background:linear-gradient(135deg,#0284c7,#0f766e);color:#fff;border-radius:21px;padding:24px 27px;margin-bottom:22px;box-shadow:0 12px 30px rgba(14,116,144,.18)}
.student-header-row{display:flex;align-items:center;justify-content:space-between;gap:20px}.student-header-badge{display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.16);padding:6px 11px;border-radius:20px;font-size:12px;margin-bottom:10px}
.student-header h2{margin:0 0 6px;font-size:26px;font-weight:700}.student-header p{margin:0;color:rgba(255,255,255,.82)}.student-header-avatar{width:80px;height:80px;border-radius:24px;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:27px;font-weight:700}
.stat-card{background:#fff;border-radius:17px;padding:20px;min-height:145px;box-shadow:0 5px 22px rgba(15,23,42,.055);border:1px solid #edf2f5;transition:.2s}.stat-card:hover{transform:translateY(-4px);box-shadow:0 10px 28px rgba(15,23,42,.09)}
.stat-top{display:flex;align-items:center;justify-content:space-between}.stat-icon{width:45px;height:45px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:20px}.icon-blue{background:#e0f2fe;color:#0284c7}.icon-green{background:#dcfce7;color:#15803d}.icon-orange{background:#ffedd5;color:#ea580c}.icon-teal{background:#ccfbf1;color:#0f766e}.stat-arrow{color:#cbd5e1;font-size:19px}.stat-number{font-size:27px;font-weight:750;margin-top:15px}.stat-label{color:#64748b;font-size:13px}
.section-card{background:#fff;border-radius:18px;box-shadow:0 5px 22px rgba(15,23,42,.055);border:1px solid #edf2f5;overflow:hidden}.section-header{padding:18px 20px;border-bottom:1px solid #eef2f5;display:flex;align-items:center;justify-content:space-between}.section-header h5{margin:0;font-size:16px;font-weight:700}.section-header small{color:#64748b}
.ekskul-item{padding:15px 20px;display:flex;align-items:center;gap:13px;border-bottom:1px solid #f1f5f9}.ekskul-item:last-child{border-bottom:none}.ekskul-icon{width:43px;height:43px;border-radius:12px;background:#e0f2fe;color:#0284c7;display:flex;align-items:center;justify-content:center;font-size:19px}.ekskul-info{flex:1}.ekskul-info strong{display:block;font-size:14px}.ekskul-info small{color:#64748b}.status-badge{background:#dcfce7;color:#15803d;padding:5px 9px;border-radius:20px;font-size:11px;font-weight:600}
.schedule-item{padding:15px 20px;border-bottom:1px solid #f1f5f9}.schedule-item:last-child{border-bottom:none}.schedule-title{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}.schedule-title strong{font-size:14px}.schedule-meta{display:flex;gap:7px;flex-wrap:wrap}.schedule-meta span{background:#f0fdfa;color:#0f766e;padding:5px 8px;border-radius:7px;font-size:11px}
.quick-card{display:flex;align-items:center;gap:12px;padding:15px;background:#fff;border:1px solid #edf2f5;border-radius:14px;text-decoration:none;color:#172033;transition:.2s;height:100%}.quick-card:hover{color:#172033;border-color:#bae6fd;transform:translateY(-3px);box-shadow:0 8px 20px rgba(15,23,42,.07)}.quick-icon{width:43px;height:43px;flex-shrink:0;border-radius:12px;background:#e0f2fe;color:#0284c7;display:flex;align-items:center;justify-content:center;font-size:18px}.quick-card strong{display:block;font-size:13px}.quick-card small{color:#64748b;font-size:11px}
.profile-card{background:#fff;border-radius:18px;padding:20px;border:1px solid #edf2f5;box-shadow:0 5px 22px rgba(15,23,42,.055)}.profile-head{display:flex;align-items:center;gap:14px}.profile-avatar{width:57px;height:57px;border-radius:17px;background:linear-gradient(135deg,#0284c7,#0f766e);color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700}.profile-head strong{display:block;font-size:16px}.profile-head small{color:#64748b}.profile-detail{margin-top:18px;padding-top:15px;border-top:1px solid #eef2f5}.profile-detail-row{display:flex;justify-content:space-between;gap:10px;margin-bottom:9px;font-size:12px}.profile-detail-row span:first-child{color:#64748b}.btn-view{color:#0284c7;text-decoration:none;font-size:12px;font-weight:600}.btn-view:hover{color:#0369a1}.empty-state{padding:32px 20px;text-align:center;color:#94a3b8}.empty-state i{font-size:30px;display:block;margin-bottom:9px}
@media(max-width:992px){.sidebar{width:220px}.content{margin-left:220px;padding:20px}}
@media(max-width:768px){.sidebar{position:relative;width:100%;height:auto;min-height:auto}.logout{position:static;margin-top:20px}.content{margin-left:0;padding:15px}.topbar{flex-direction:column;align-items:flex-start;gap:13px}.user-text{text-align:left}.student-header-row{flex-direction:column;align-items:flex-start}.student-header-avatar{width:60px;height:60px;border-radius:18px}}

/* =====================================================
   TAMPILAN BARU DASHBOARD SISWA
   Warna tetap mengikuti dashboard admin
===================================================== */

body {
    background:
        radial-gradient(circle at 100% 0%, rgba(2,132,199,.07), transparent 28%),
        #f5f8fb;
}

/* Sidebar lebih clean */
.sidebar {
    background: linear-gradient(180deg, #064e78 0%, #0f766e 100%);
    padding: 18px 13px 82px;
    border-right: 1px solid rgba(255,255,255,.08);
}

.brand {
    padding: 8px 8px 18px;
}

.brand-icon {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    box-shadow: 0 8px 18px rgba(0,0,0,.08);
}

.brand h4 {
    font-size: 17px;
    margin-top: 9px;
}

.menu-title {
    margin-top: 22px;
}

.sidebar a {
    padding: 11px 13px;
    border-radius: 12px;
}

.sidebar a.active {
    background: rgba(255,255,255,.20);
    box-shadow: inset 3px 0 0 #fff, 0 6px 16px rgba(0,0,0,.05);
}

/* Konten */
.content {
    padding: 24px 28px 32px;
}

/* Topbar */
.topbar {
    min-height: 68px;
    margin-bottom: 18px;
    border: 1px solid #e8eef4;
    box-shadow: 0 8px 24px rgba(15,23,42,.05);
}

.topbar-title h5 {
    font-size: 17px;
}

.topbar-title small {
    font-size: 12px;
}

/* Header siswa dibuat lebih modern */
.student-header {
    padding: 24px 26px;
    border-radius: 20px;
    margin-bottom: 18px;
    background:
        linear-gradient(115deg, #075985 0%, #0284c7 50%, #0f766e 100%);
    box-shadow: 0 14px 30px rgba(2,132,199,.16);
}

.student-header h2 {
    font-size: 24px;
}

.student-header p {
    max-width: 620px;
}

.student-header-avatar {
    width: 72px;
    height: 72px;
    border-radius: 20px;
    background: rgba(255,255,255,.16);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.10);
}

/* Statistik jadi lebih kompak */
.stat-card {
    min-height: 125px;
    padding: 17px;
    border-radius: 16px;
    border: 1px solid #e9eef3;
}

.stat-top {
    margin-bottom: 4px;
}

.stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
}

.stat-number {
    margin-top: 8px;
    font-size: 25px;
}

/* Semua kartu */
.section-card,
.profile-card {
    border-radius: 17px;
    border: 1px solid #e8eef4;
    box-shadow: 0 7px 24px rgba(15,23,42,.05);
}

.section-header {
    padding: 16px 18px;
}

.section-header h5 {
    font-size: 15px;
}

/* Ekskul lebih rapi */
.ekskul-item {
    padding: 14px 18px;
}

.ekskul-icon {
    width: 40px;
    height: 40px;
    border-radius: 11px;
}

.ekskul-info strong {
    font-size: 13px;
}

/* Jadwal dibuat lebih jelas */
.schedule-item {
    padding: 14px 18px;
}

.schedule-title strong {
    font-size: 13px;
}

.schedule-meta span {
    background: #effcf9;
    border: 1px solid #d9f5ef;
}

/* Menu cepat lebih minimal */
.quick-card {
    padding: 13px;
    border-radius: 13px;
    min-height: 76px;
}

.quick-icon {
    width: 40px;
    height: 40px;
    border-radius: 11px;
}

.quick-card strong {
    font-size: 12px;
}

.quick-card small {
    font-size: 10.5px;
}

/* Profil */
.profile-card {
    padding: 18px;
}

.profile-avatar {
    width: 52px;
    height: 52px;
    border-radius: 15px;
}

.profile-detail {
    margin-top: 15px;
}

/* Logout tetap paling bawah */
.logout {
    bottom: 16px;
}

/* Responsive */
@media (max-width: 768px) {
    .content {
        padding: 14px;
    }

    .student-header {
        padding: 20px;
    }

    .student-header h2 {
        font-size: 21px;
    }
}


/* =====================================================
   HALAMAN DAFTAR EKSKUL DALAM DASHBOARD USER
===================================================== */

.page-header {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #0284c7, #0f766e);
    color: white;
    border-radius: 20px;
    padding: 24px 27px;
    margin-bottom: 20px;
    box-shadow: 0 12px 30px rgba(14,116,144,.16);
}

.page-header::before {
    content: "";
    position: absolute;
    width: 210px;
    height: 210px;
    border-radius: 50%;
    background: rgba(255,255,255,.07);
    right: -60px;
    top: -110px;
}

.page-header-content {
    position: relative;
    z-index: 2;
}

.page-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: rgba(255,255,255,.13);
    border: 1px solid rgba(255,255,255,.16);
    padding: 6px 11px;
    border-radius: 20px;
    font-size: 12px;
    margin-bottom: 10px;
}

.page-header h2 {
    margin: 0 0 6px;
    font-size: 24px;
    font-weight: 700;
}

.page-header p {
    margin: 0;
    color: rgba(255,255,255,.82);
    font-size: 13px;
}

.alert-box {
    border-radius: 13px;
    border: none;
    box-shadow: 0 4px 15px rgba(15,23,42,.04);
}

.profile-mini {
    background: linear-gradient(135deg, #ecfeff, #f0fdfa);
    border: 1px solid #ccfbf1;
    border-radius: 16px;
    padding: 15px 18px;
}

.profile-mini-head {
    display: flex;
    align-items: center;
    gap: 12px;
}

.profile-mini-avatar {
    width: 44px;
    height: 44px;
    border-radius: 13px;
    background: linear-gradient(135deg, #0284c7, #0f766e);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
}

.profile-mini strong {
    display: block;
    font-size: 14px;
}

.profile-mini small {
    color: #64748b;
}

.ekskul-card {
    height: 100%;
    background: white;
    border: 1px solid #e8eef2;
    border-radius: 16px;
    padding: 18px;
    transition: .2s;
    position: relative;
    overflow: hidden;
}

.ekskul-card:hover {
    transform: translateY(-4px);
    border-color: #bae6fd;
    box-shadow: 0 10px 25px rgba(15,23,42,.08);
}

.ekskul-card .ekskul-icon {
    width: 48px;
    height: 48px;
    border-radius: 13px;
    background: #e0f2fe;
    color: #0284c7;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
    margin-bottom: 14px;
}

.ekskul-card h5 {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 7px;
}

.ekskul-card p {
    color: #64748b;
    font-size: 12px;
    margin-bottom: 4px;
}

.ekskul-info {
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid #eef2f5;
}

.info-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 7px;
    color: #64748b;
    font-size: 11px;
}

.info-row i {
    width: 18px;
    color: #0284c7;
}

.btn-daftar,
.btn-status {
    width: 100%;
    border-radius: 10px;
    padding: 9px;
    margin-top: 9px;
    font-size: 12px;
    font-weight: 600;
}

.btn-daftar {
    border: none;
    background: linear-gradient(135deg, #0284c7, #0f766e);
    color: white;
}

.btn-daftar:hover {
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(2,132,199,.2);
}

.status-menunggu {
    background: #fef3c7;
    color: #b45309;
}

.status-diterima {
    background: #dcfce7;
    color: #15803d;
}

.status-ditolak {
    background: #fee2e2;
    color: #b91c1c;
}

.page-empty {
    padding: 45px 20px;
    text-align: center;
    color: #94a3b8;
}

.page-empty i {
    font-size: 38px;
    display: block;
    margin-bottom: 10px;
}

/* =====================================================
   HALAMAN ABSENSI SAYA DALAM DASHBOARD USER
===================================================== */

.absensi-table-wrap {
    overflow-x: auto;
}

.absensi-table {
    margin: 0;
    min-width: 650px;
}

.absensi-table thead th {
    background: #f8fafc;
    color: #475569;
    font-size: 12px;
    font-weight: 700;
    border-bottom: 1px solid #e2e8f0;
    padding: 13px 15px;
    white-space: nowrap;
}

.absensi-table tbody td {
    padding: 13px 15px;
    font-size: 13px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}

.absensi-table tbody tr:last-child td {
    border-bottom: none;
}

.absensi-table tbody tr:hover {
    background: #f8fafc;
}

.status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.status-hadir {
    background: #dcfce7;
    color: #15803d;
}

.status-izin {
    background: #fef3c7;
    color: #b45309;
}

.status-sakit {
    background: #dbeafe;
    color: #1d4ed8;
}

.status-alfa {
    background: #fee2e2;
    color: #dc2626;
}

.status-lain {
    background: #e2e8f0;
    color: #475569;
}

.absensi-date {
    white-space: nowrap;
}

.absensi-rate {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #d1fae5;
    border-radius: 999px;
    padding: 6px 10px;
    font-size: 11px;
    font-weight: 700;
}

.absensi-profile {
    margin-top: 18px;
}

@media (max-width: 768px) {
    .absensi-table {
        min-width: 620px;
    }
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
    <a href="?" class="<?= (!$isDaftarEkskul && !$isPendaftaranSaya && !$isJadwalSaya && !$isAbsensiSaya) ? "active" : ""; ?>"><i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span></a>
    <a href="?page=daftar_ekskul" class="<?= $isDaftarEkskul ? "active" : ""; ?>" onclick="window.location.href=this.href; return false;"><i class="bi bi-stars"></i><span>Daftar Ekskul</span></a>
    <a href="?page=pendaftaran_saya" class="<?= $isPendaftaranSaya ? "active" : ""; ?>"><i class="bi bi-person-check-fill"></i><span>Pendaftaran Saya</span></a>
    <a href="?page=jadwal_saya" class="<?= $isJadwalSaya ? "active" : ""; ?>"><i class="bi bi-calendar-event-fill"></i><span>Jadwal Saya</span></a>
    <a href="?page=absensi_saya" class="<?= $isAbsensiSaya ? "active" : ""; ?>"><i class="bi bi-calendar-check-fill"></i><span>Absensi Saya</span></a>
    <a href="profil.php"><i class="bi bi-person-fill"></i><span>Profil</span></a>
    <div class="logout"><a href="../logout.php"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a></div>
</div>

<div class="content">
    <div class="topbar">
        <div class="topbar-title">
            <?= $isDaftarEkskul ? "Daftar Ekstrakurikuler" : ($isPendaftaranSaya ? "Pendaftaran Saya" : ($isJadwalSaya ? "Jadwal Saya" : ($isAbsensiSaya ? "Absensi Saya" : "Dashboard Siswa"))); ?>
            <small>Sistem Informasi Ekstrakurikuler</small>
        </div>
        <div class="user-area">
            <div class="user-text"><strong><?= htmlspecialchars($namaTampilan); ?></strong><small>Siswa</small></div>
            <div class="avatar"><?= htmlspecialchars($inisial); ?></div>
        </div>
    </div>

<?php if ($isDaftarEkskul): ?>

    <!-- =================================================
         HEADER DAFTAR EKSKUL
    ================================================== -->

    <div class="page-header">

        <div class="page-header-content">

            <div class="page-badge">
                <i class="bi bi-stars"></i>
                Pilihan Ekstrakurikuler
            </div>

            <h2>
                Temukan Ekskul Favoritmu
            </h2>

            <p>
                Pilih ekstrakurikuler yang ingin kamu ikuti
                dan kirim pendaftaran kepada admin.
            </p>

        </div>

    </div>

    <!-- PESAN -->
    <?php if ($success !== ""): ?>

        <div class="alert alert-success alert-box mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= $success; ?>
        </div>

    <?php endif; ?>

    <?php if ($error !== ""): ?>

        <div class="alert alert-danger alert-box mb-4">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <?= $error; ?>
        </div>

    <?php endif; ?>

    <!-- PROFIL MINI -->
    <div class="profile-mini mb-4">

        <div class="profile-mini-head">

            <div class="profile-mini-avatar">
                <?= htmlspecialchars($inisial); ?>
            </div>

            <div>

                <strong>
                    <?= htmlspecialchars($namaTampilan); ?>
                </strong>

                <small>
                    <?= $siswa
                        ? htmlspecialchars($siswa['kelas'] ?? 'Siswa')
                        : 'Data siswa belum terhubung';
                    ?>
                </small>

            </div>

        </div>

    </div>

    <!-- DAFTAR EKSKUL -->
    <div class="section-card">

        <div class="section-header">

            <div>
                <h5>Daftar Ekskul</h5>

                <small>
                    Pilih salah satu ekstrakurikuler
                </small>
            </div>

            <span class="badge text-bg-light">
                <i class="bi bi-grid me-1"></i>
                Semua Ekskul
            </span>

        </div>

        <div class="p-3">

            <div class="row g-3">

                <?php if ($dataEkskulForm && mysqli_num_rows($dataEkskulForm) > 0): ?>

                    <?php while ($e = mysqli_fetch_assoc($dataEkskulForm)): ?>

                        <?php
                        $idEkskul = (int) $e['id'];
                        $namaEkskul = $e['nama_ekskul'] ?? 'Ekstrakurikuler';
                        $statusSaatIni = $pendaftaranSaya[$idEkskul] ?? null;
                        ?>

                        <div class="col-xl-4 col-md-6">

                            <div class="ekskul-card">

                                <div class="ekskul-icon">
                                    <i class="bi bi-stars"></i>
                                </div>

                                <h5>
                                    <?= htmlspecialchars($namaEkskul); ?>
                                </h5>

                                <p>
                                    Ekstrakurikuler sekolah
                                </p>

                                <div class="ekskul-info">

                                    <div class="info-row">
                                        <i class="bi bi-person-badge"></i>
                                        <span>
                                            Pembina:
                                            <?= htmlspecialchars($e['pembina'] ?? '-'); ?>
                                        </span>
                                    </div>

                                    <div class="info-row">
                                        <i class="bi bi-bookmark-star"></i>
                                        <span>
                                            Kegiatan ekstrakurikuler
                                        </span>
                                    </div>

                                </div>

                                <?php if ($statusSaatIni === null): ?>

                                    <form
                                        method="POST"
                                        action="?page=daftar_ekskul"
                                        onsubmit="return confirm('Apakah kamu yakin ingin mendaftar ekskul ini?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="ekskul_id"
                                            value="<?= $idEkskul; ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="daftar_ekskul"
                                            class="btn btn-daftar"
                                        >
                                            <i class="bi bi-plus-circle me-1"></i>
                                            Daftar Ekskul
                                        </button>

                                    </form>

                                <?php elseif ($statusSaatIni === "menunggu"): ?>

                                    <div class="btn-status status-menunggu">
                                        <i class="bi bi-hourglass-split me-1"></i>
                                        Menunggu Persetujuan
                                    </div>

                                <?php elseif ($statusSaatIni === "diterima"): ?>

                                    <div class="btn-status status-diterima">
                                        <i class="bi bi-check-circle-fill me-1"></i>
                                        Sudah Diterima
                                    </div>

                                <?php elseif ($statusSaatIni === "ditolak"): ?>

                                    <form
                                        method="POST"
                                        action="?page=daftar_ekskul"
                                        onsubmit="return confirm('Pendaftaran sebelumnya ditolak. Daftar kembali?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="ekskul_id"
                                            value="<?= $idEkskul; ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="daftar_ekskul"
                                            class="btn btn-daftar"
                                        >
                                            <i class="bi bi-arrow-repeat me-1"></i>
                                            Daftar Kembali
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <div class="btn-status status-menunggu">
                                        Status:
                                        <?= htmlspecialchars(ucfirst($statusSaatIni)); ?>
                                    </div>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="col-12">

                        <div class="page-empty">

                            <i class="bi bi-inbox"></i>

                            <strong>
                                Belum ada ekstrakurikuler
                            </strong>

                            <div class="mt-1">
                                Data ekstrakurikuler belum tersedia.
                            </div>

                        </div>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

    <div class="alert alert-info alert-box mt-4">

        <i class="bi bi-info-circle-fill me-2"></i>

        <strong>Informasi:</strong>

        Setelah kamu mendaftar, status pendaftaran akan menjadi
        <strong>Menunggu Persetujuan</strong>.
        Setelah admin menerima pendaftaran, ekskul tersebut
        otomatis menjadi ekskul yang kamu ikuti dan jadwalnya
        akan tampil di menu <strong>Jadwal Saya</strong>.

    </div>


<?php elseif ($isPendaftaranSaya): ?>

    <!-- =================================================
         HEADER PENDAFTARAN SAYA
    ================================================== -->

    <div class="page-header">

        <div class="page-header-content">

            <div class="page-badge">

                <i class="bi bi-person-check-fill"></i>

                Data Pendaftaran

            </div>

            <h2>Pendaftaran Saya</h2>

            <p>
                Pantau semua pendaftaran ekstrakurikuler
                yang telah kamu ajukan.
            </p>

        </div>

    </div>

    <!-- =================================================
         STATISTIK
    ================================================== -->

    <div class="row g-3 mb-4">

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">

                <div class="stat-top">
                    <div class="stat-icon icon-blue">
                        <i class="bi bi-clipboard-check"></i>
                    </div>

                    <div class="stat-arrow">
                        <i class="bi bi-three-dots"></i>
                    </div>
                </div>

                <div class="stat-number">
                    <?= $totalPendaftaranPS; ?>
                </div>

                <div class="stat-label">
                    Total Pendaftaran
                </div>

            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">

                <div class="stat-top">
                    <div class="stat-icon icon-orange">
                        <i class="bi bi-hourglass-split"></i>
                    </div>

                    <div class="stat-arrow">
                        <i class="bi bi-three-dots"></i>
                    </div>
                </div>

                <div class="stat-number">
                    <?= $totalMenungguPS; ?>
                </div>

                <div class="stat-label">
                    Menunggu Persetujuan
                </div>

            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">

                <div class="stat-top">
                    <div class="stat-icon icon-green">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>

                    <div class="stat-arrow">
                        <i class="bi bi-three-dots"></i>
                    </div>
                </div>

                <div class="stat-number">
                    <?= $totalDiterimaPS; ?>
                </div>

                <div class="stat-label">
                    Pendaftaran Diterima
                </div>

            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">

                <div class="stat-top">
                    <div class="stat-icon icon-red">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>

                    <div class="stat-arrow">
                        <i class="bi bi-three-dots"></i>
                    </div>
                </div>

                <div class="stat-number">
                    <?= $totalDitolakPS; ?>
                </div>

                <div class="stat-label">
                    Pendaftaran Ditolak
                </div>

            </div>
        </div>

    </div>

    <!-- =================================================
         RIWAYAT PENDAFTARAN
    ================================================== -->

    <div class="section-card mb-4">

        <div class="section-header">

            <div>
                <h5>
                    <i class="bi bi-list-check me-2"></i>
                    Riwayat Pendaftaran
                </h5>

                <small>
                    Data pendaftaran kamu
                </small>
            </div>

        </div>

        <div class="p-3">

            <?php if ($dataPendaftaranPS && mysqli_num_rows($dataPendaftaranPS) > 0): ?>

                <div class="table-responsive">

                    <table class="table align-middle">

                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Ekstrakurikuler</th>
                                <th>Pembina</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php
                            $noPS = 1;
                            while ($pPS = mysqli_fetch_assoc($dataPendaftaranPS)):
                                $statusPS = strtolower(
                                    trim($pPS['status'] ?? '')
                                );
                            ?>

                                <tr>

                                    <td>
                                        <?= $noPS++; ?>
                                    </td>

                                    <td>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $pPS['nama_ekskul']
                                                ?? 'Ekskul tidak ditemukan'
                                            ); ?>
                                        </strong>

                                        <div class="small text-muted mt-1">
                                            ID Pendaftaran:
                                            #<?= (int) $pPS['id']; ?>
                                        </div>

                                    </td>

                                    <td>

                                        <i class="bi bi-person-badge me-1"></i>

                                        <?= htmlspecialchars(
                                            $pPS['pembina'] ?? '-'
                                        ); ?>

                                    </td>

                                    <td>

                                        <?php if ($statusPS === "menunggu"): ?>

                                            <span class="status status-menunggu">
                                                <i class="bi bi-hourglass-split"></i>
                                                Menunggu
                                            </span>

                                        <?php elseif ($statusPS === "diterima"): ?>

                                            <span class="status status-diterima">
                                                <i class="bi bi-check-circle-fill"></i>
                                                Diterima
                                            </span>

                                        <?php elseif ($statusPS === "ditolak"): ?>

                                            <span class="status status-ditolak">
                                                <i class="bi bi-x-circle-fill"></i>
                                                Ditolak
                                            </span>

                                        <?php else: ?>

                                            <span class="status status-lain">
                                                <?= htmlspecialchars(
                                                    ucfirst($statusPS)
                                                ); ?>
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="page-empty">

                    <i class="bi bi-inbox"></i>

                    <strong>
                        Belum Ada Pendaftaran
                    </strong>

                    <div class="mt-2 mb-3">
                        Kamu belum mendaftar ekstrakurikuler apa pun.
                    </div>

                    <a
                        href="?page=daftar_ekskul"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-stars me-1"></i>
                        Daftar Ekskul
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </div>

    <div class="alert alert-info">

        <i class="bi bi-info-circle-fill me-2"></i>

        <strong>Informasi Pendaftaran</strong>

        <div class="mt-2">
            <strong>Menunggu</strong>
            berarti pendaftaran masih menunggu persetujuan admin.
        </div>

        <div>
            <strong>Diterima</strong>
            berarti kamu sudah resmi terdaftar sebagai anggota ekskul.
        </div>

        <div>
            <strong>Ditolak</strong>
            berarti pendaftaran belum disetujui admin.
        </div>

    </div>



<?php elseif ($isJadwalSaya): ?>

    <!-- =================================================
         HEADER JADWAL SAYA
    ================================================== -->

    <div class="page-header">

        <div class="page-header-content">

            <div class="page-badge">

                <i class="bi bi-calendar-event-fill"></i>

                Jadwal Kegiatan

            </div>

            <h2>
                Jadwal Ekstrakurikuler Saya
            </h2>

            <p>
                Jadwal kegiatan ekstrakurikuler
                yang sudah kamu daftarkan dan diterima.
            </p>

        </div>

    </div>

    <?php if ($pesanJadwalSaya !== ""): ?>

        <div class="alert alert-warning alert-box mb-4">

            <i class="bi bi-exclamation-circle-fill me-2"></i>

            <?= htmlspecialchars($pesanJadwalSaya); ?>

        </div>

    <?php endif; ?>

    <?php if ($dataSiswaJadwal ?? null): ?>

        <div class="profile-mini mb-4">

            <div class="profile-mini-head">

                <div class="profile-mini-avatar">

                    <?= htmlspecialchars(
                        strtoupper(
                            substr(
                                trim($namaSiswaJadwal),
                                0,
                                1
                            )
                        )
                    ); ?>

                </div>

                <div>

                    <strong>
                        <?= htmlspecialchars($namaSiswaJadwal); ?>
                    </strong>

                    <small>
                        Jadwal ekskul yang sudah diterima
                    </small>

                </div>

            </div>

        </div>

    <?php endif; ?>

    <?php if (
        $jadwalSaya &&
        mysqli_num_rows($jadwalSaya) > 0
    ): ?>

        <div class="row g-3">

            <?php while (
                $jadwal =
                mysqli_fetch_assoc($jadwalSaya)
            ): ?>

                <div class="col-lg-6">

                    <div class="section-card h-100">

                        <div class="p-3">

                            <div class="d-flex align-items-center gap-3">

                                <div class="ekskul-icon">

                                    <i class="bi bi-calendar-event-fill"></i>

                                </div>

                                <div>

                                    <h5 class="mb-1">

                                        <?= htmlspecialchars(
                                            $jadwal['nama_ekskul']
                                        ); ?>

                                    </h5>

                                    <div class="text-muted small">

                                        Pembina:
                                        <?= htmlspecialchars(
                                            $jadwal['pembina'] ?? '-'
                                        ); ?>

                                    </div>

                                </div>

                            </div>

                            <div class="schedule-meta mt-3">

                                <span>
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?= htmlspecialchars(
                                        $jadwal['hari']
                                    ); ?>
                                </span>

                                <span>
                                    <i class="bi bi-clock me-1"></i>
                                    <?= htmlspecialchars(
                                        substr(
                                            $jadwal['jam_mulai'],
                                            0,
                                            5
                                        )
                                    ); ?>
                                    -
                                    <?= htmlspecialchars(
                                        substr(
                                            $jadwal['jam_selesai'],
                                            0,
                                            5
                                        )
                                    ); ?>
                                </span>

                                <span>
                                    <i class="bi bi-geo-alt me-1"></i>
                                    <?= htmlspecialchars(
                                        $jadwal['lokasi']
                                    ); ?>
                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="page-empty">

            <i class="bi bi-calendar-x"></i>

            <strong>
                Belum Ada Jadwal
            </strong>

            <div class="mt-2 mb-3">

                Kamu belum memiliki pendaftaran
                ekstrakurikuler yang diterima
                atau belum ada jadwal untuk ekskul tersebut.

            </div>

            <a
                href="?page=daftar_ekskul"
                class="btn btn-primary"
            >

                <i class="bi bi-stars me-1"></i>

                Daftar Ekskul

            </a>

        </div>

    <?php endif; ?>


<?php elseif ($isAbsensiSaya): ?>

    <!-- =================================================
         HEADER ABSENSI SAYA
    ================================================== -->

    <div class="page-header">

        <div class="page-header-content">

            <div class="page-badge">

                <i class="bi bi-calendar-check-fill"></i>

                Riwayat Kehadiran

            </div>

            <h2>
                Absensi Saya
            </h2>

            <p>
                Lihat seluruh riwayat kehadiran kamu
                pada kegiatan ekstrakurikuler.
            </p>

        </div>

    </div>

    <!-- =================================================
         STATISTIK ABSENSI
    ================================================== -->

    <div class="row g-3 mb-4">

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">

                <div class="stat-top">
                    <div class="stat-icon icon-blue">
                        <i class="bi bi-calendar-check"></i>
                    </div>

                    <div class="stat-arrow">
                        <i class="bi bi-three-dots"></i>
                    </div>
                </div>

                <div class="stat-number">
                    <?= $totalAbsensiAS; ?>
                </div>

                <div class="stat-label">
                    Total Absensi
                </div>

            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">

                <div class="stat-top">
                    <div class="stat-icon icon-green">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>

                    <div class="stat-arrow">
                        <i class="bi bi-three-dots"></i>
                    </div>
                </div>

                <div class="stat-number">
                    <?= $totalHadirAS; ?>
                </div>

                <div class="stat-label">
                    Hadir
                </div>

            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">

                <div class="stat-top">
                    <div class="stat-icon icon-orange">
                        <i class="bi bi-envelope-paper-fill"></i>
                    </div>

                    <div class="stat-arrow">
                        <i class="bi bi-three-dots"></i>
                    </div>
                </div>

                <div class="stat-number">
                    <?= $totalIzinAS; ?>
                </div>

                <div class="stat-label">
                    Izin
                </div>

            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">

                <div class="stat-top">
                    <div class="stat-icon icon-red">
                        <i class="bi bi-exclamation-circle-fill"></i>
                    </div>

                    <div class="stat-arrow">
                        <i class="bi bi-three-dots"></i>
                    </div>
                </div>

                <div class="stat-number">
                    <?= $totalSakitAS + $totalAlfaAS; ?>
                </div>

                <div class="stat-label">
                    Sakit + Alfa
                </div>

            </div>
        </div>

    </div>

    <!-- =================================================
         RIWAYAT KEHADIRAN
    ================================================== -->

    <div class="section-card mb-4">

        <div class="section-header">

            <div>

                <h5>
                    <i class="bi bi-list-check me-2"></i>
                    Riwayat Kehadiran
                </h5>

                <small>
                    Data absensi berdasarkan akun kamu
                </small>

            </div>

            <span class="absensi-rate">
                Kehadiran <?= $persentaseHadirAS; ?>%
            </span>

        </div>

        <div class="absensi-table-wrap">

            <?php if ($dataAbsensiAS && mysqli_num_rows($dataAbsensiAS) > 0): ?>

                <table class="table absensi-table">

                    <thead>
                        <tr>
                            <th width="60">No</th>
                            <th>Tanggal</th>
                            <th>Ekstrakurikuler</th>
                            <th>Status Kehadiran</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php
                        $noAS = 1;

                        while ($aAS = mysqli_fetch_assoc($dataAbsensiAS)):

                            $statusAS = strtolower(
                                trim($aAS['status_hadir'] ?? '')
                            );

                            if ($statusAS === 'hadir') {
                                $statusClassAS = 'status-hadir';
                                $statusIconAS = 'bi-check-circle-fill';
                            } elseif ($statusAS === 'izin') {
                                $statusClassAS = 'status-izin';
                                $statusIconAS = 'bi-envelope-paper-fill';
                            } elseif ($statusAS === 'sakit') {
                                $statusClassAS = 'status-sakit';
                                $statusIconAS = 'bi-thermometer-half';
                            } elseif ($statusAS === 'alfa') {
                                $statusClassAS = 'status-alfa';
                                $statusIconAS = 'bi-x-circle-fill';
                            } else {
                                $statusClassAS = 'status-lain';
                                $statusIconAS = 'bi-question-circle-fill';
                            }
                        ?>

                            <tr>

                                <td>
                                    <?= $noAS++; ?>
                                </td>

                                <td class="absensi-date">

                                    <i class="bi bi-calendar3 me-1"></i>

                                    <?= htmlspecialchars(
                                        $aAS['tanggal'] ?? '-'
                                    ); ?>

                                </td>

                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $aAS['nama_ekskul'] ?? '-'
                                        ); ?>
                                    </strong>

                                </td>

                                <td>

                                    <span class="status <?= $statusClassAS; ?>">

                                        <i class="bi <?= $statusIconAS; ?>"></i>

                                        <?= ucfirst(
                                            htmlspecialchars(
                                                $aAS['status_hadir'] ?? '-'
                                            )
                                        ); ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <div class="page-empty">

                    <i class="bi bi-calendar-x"></i>

                    <strong>
                        Belum ada data absensi
                    </strong>

                    <div class="mt-1">
                        Riwayat kehadiran kamu belum tersedia.
                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>

    <!-- =================================================
         PROFIL SINGKAT
    ================================================== -->

    <div class="profile-mini absensi-profile">

        <div class="profile-mini-head">

            <div class="profile-mini-avatar">
                <?= htmlspecialchars($inisial); ?>
            </div>

            <div>

                <strong>
                    <?= htmlspecialchars($namaTampilan); ?>
                </strong>

                <small>
                    <?= $siswa
                        ? htmlspecialchars($siswa['kelas'] ?? 'Siswa')
                        : 'Data siswa belum diisi';
                    ?>
                </small>

            </div>

        </div>

    </div>


<?php else: ?>


    <div class="student-header">
        <div class="student-header-row">
            <div>
                <div class="student-header-badge"><i class="bi bi-stars"></i> Portal Siswa</div>
                <h2>Selamat Datang, <?= htmlspecialchars($namaTampilan); ?>!</h2>
                <p>Pantau kegiatan ekstrakurikuler, pendaftaran, jadwal, dan absensi kamu di satu tempat.</p>
            </div>
            <div class="student-header-avatar"><?= htmlspecialchars($inisial); ?></div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6"><div class="stat-card"><div class="stat-top"><div class="stat-icon icon-blue"><i class="bi bi-person-plus-fill"></i></div><div class="stat-arrow"><i class="bi bi-three-dots"></i></div></div><div class="stat-number"><?= $totalPendaftaran; ?></div><div class="stat-label">Total Pendaftaran</div></div></div>
        <div class="col-xl-3 col-md-6"><div class="stat-card"><div class="stat-top"><div class="stat-icon icon-green"><i class="bi bi-check-circle-fill"></i></div><div class="stat-arrow"><i class="bi bi-three-dots"></i></div></div><div class="stat-number"><?= $totalDiterima; ?></div><div class="stat-label">Ekskul Diterima</div></div></div>
        <div class="col-xl-3 col-md-6"><div class="stat-card"><div class="stat-top"><div class="stat-icon icon-orange"><i class="bi bi-calendar-event-fill"></i></div><div class="stat-arrow"><i class="bi bi-three-dots"></i></div></div><div class="stat-number"><?= $totalJadwal; ?></div><div class="stat-label">Jadwal Kegiatan</div></div></div>
        <div class="col-xl-3 col-md-6"><div class="stat-card"><div class="stat-top"><div class="stat-icon icon-teal"><i class="bi bi-calendar-check-fill"></i></div><div class="stat-arrow"><i class="bi bi-three-dots"></i></div></div><div class="stat-number"><?= $totalAbsensi; ?></div><div class="stat-label">Total Absensi</div></div></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="section-card">
                <div class="section-header"><div><h5>Ekskul Saya</h5><small>Ekstrakurikuler yang sudah diterima</small></div><a href="?page=pendaftaran_saya" class="btn-view">Lihat Semua <i class="bi bi-arrow-right"></i></a></div>
                <?php if ($dataEkskul && mysqli_num_rows($dataEkskul) > 0): ?>
                    <?php while ($e = mysqli_fetch_assoc($dataEkskul)): ?>
                        <div class="ekskul-item"><div class="ekskul-icon"><i class="bi bi-stars"></i></div><div class="ekskul-info"><strong><?= htmlspecialchars($e['nama_ekskul']); ?></strong><small>Pembina: <?= htmlspecialchars($e['pembina'] ?? '-'); ?></small></div><span class="status-badge">Diterima</span></div>
                    <?php endwhile; ?>
                <?php else: ?><div class="empty-state"><i class="bi bi-inbox"></i>Belum ada ekskul yang diterima.</div><?php endif; ?>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="section-card">
                <div class="section-header"><div><h5>Jadwal Kegiatan</h5><small>Jadwal ekskul kamu</small></div><a href="?page=jadwal_saya" class="btn-view">Lihat Semua <i class="bi bi-arrow-right"></i></a></div>
                <?php if ($dataJadwal && mysqli_num_rows($dataJadwal) > 0): ?>
                    <?php while ($j = mysqli_fetch_assoc($dataJadwal)): ?>
                        <div class="schedule-item"><div class="schedule-title"><strong><?= htmlspecialchars($j['nama_ekskul']); ?></strong></div><div class="schedule-meta"><span><i class="bi bi-calendar3"></i> <?= htmlspecialchars($j['hari']); ?></span><span><i class="bi bi-clock"></i> <?= htmlspecialchars(substr($j['jam_mulai'],0,5)); ?> - <?= htmlspecialchars(substr($j['jam_selesai'],0,5)); ?></span><span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($j['lokasi']); ?></span></div></div>
                    <?php endwhile; ?>
                <?php else: ?><div class="empty-state"><i class="bi bi-calendar-x"></i>Jadwal belum tersedia.</div><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="section-card mb-4">
        <div class="section-header"><div><h5>Menu Cepat</h5><small>Akses fitur utama dengan mudah</small></div></div>
        <div class="p-3"><div class="row g-3">
            <div class="col-lg-4 col-md-6"><a href="?page=daftar_ekskul" class="quick-card"><div class="quick-icon"><i class="bi bi-stars"></i></div><div><strong>Daftar Ekskul</strong><small>Pilih ekstrakurikuler</small></div></a></div>
            <div class="col-lg-4 col-md-6"><a href="?page=pendaftaran_saya" class="quick-card"><div class="quick-icon"><i class="bi bi-person-check-fill"></i></div><div><strong>Pendaftaran Saya</strong><small>Lihat status pendaftaran</small></div></a></div>
            <div class="col-lg-4 col-md-6"><a href="?page=jadwal_saya" class="quick-card"><div class="quick-icon"><i class="bi bi-calendar-event-fill"></i></div><div><strong>Jadwal Saya</strong><small>Lihat jadwal kegiatan</small></div></a></div>
            <div class="col-lg-4 col-md-6"><a href="?page=absensi_saya" class="quick-card"><div class="quick-icon"><i class="bi bi-calendar-check-fill"></i></div><div><strong>Absensi Saya</strong><small>Lihat riwayat kehadiran</small></div></a></div>
            <div class="col-lg-4 col-md-6"><a href="profil.php" class="quick-card"><div class="quick-icon"><i class="bi bi-person-fill"></i></div><div><strong>Profil Saya</strong><small>Kelola data diri</small></div></a></div>
            <div class="col-lg-4 col-md-6"><a href="?page=daftar_ekskul" class="quick-card"><div class="quick-icon"><i class="bi bi-plus-circle-fill"></i></div><div><strong>Daftar Sekarang</strong><small>Tambah ekstrakurikuler</small></div></a></div>
        </div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="profile-card">
                <div class="profile-head"><div class="profile-avatar"><?= htmlspecialchars($inisial); ?></div><div><strong><?= htmlspecialchars($namaTampilan); ?></strong><small><?= $siswa ? htmlspecialchars($siswa['kelas'] ?? 'Siswa') : 'Data siswa belum diisi'; ?></small></div></div>
                <?php if ($siswa): ?>
                    <div class="profile-detail">
                        <div class="profile-detail-row"><span>Nama</span><strong><?= htmlspecialchars($siswa['nama_siswa'] ?? '-'); ?></strong></div>
                        <div class="profile-detail-row"><span>Kelas</span><strong><?= htmlspecialchars($siswa['kelas'] ?? '-'); ?></strong></div>
                        <div class="profile-detail-row"><span>Jenis Kelamin</span><strong><?= htmlspecialchars($siswa['jenis_kelamin'] ?? '-'); ?></strong></div>
                        <div class="mt-3"><a href="profil.php" class="btn btn-sm btn-primary"><i class="bi bi-person-gear me-1"></i>Kelola Profil</a></div>
                    </div>
                <?php else: ?>
                    <div class="profile-detail"><div class="alert alert-warning mb-0"><i class="bi bi-exclamation-circle me-1"></i>Data siswa belum terhubung dengan akun ini.<div class="mt-2">Silakan lengkapi profil terlebih dahulu.</div></div></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="section-card h-100">
                <div class="section-header"><div><h5>Ringkasan Akun</h5><small>Status akun siswa</small></div></div>
                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3"><span class="text-muted small">Status akun</span><span class="badge rounded-pill text-bg-success">Aktif</span></div>
                    <div class="d-flex justify-content-between align-items-center mb-3"><span class="text-muted small">Ekskul diterima</span><strong><?= $totalDiterima; ?></strong></div>
                    <div class="d-flex justify-content-between align-items-center mb-3"><span class="text-muted small">Jadwal tersedia</span><strong><?= $totalJadwal; ?></strong></div>
                    <div class="d-flex justify-content-between align-items-center"><span class="text-muted small">Total absensi</span><strong><?= $totalAbsensi; ?></strong></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

</body>
</html>
