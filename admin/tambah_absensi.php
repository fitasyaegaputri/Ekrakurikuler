<?php
require_once __DIR__ . "/../config/config.php";

$pesan = "";
$tipe_pesan = "";

// PROSES SIMPAN
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Ambil data dari form
    $nama_siswa = trim($_POST['nama_siswa'] ?? '');
    $nama_ekskul = trim($_POST['nama_ekskul'] ?? '');
    $tanggal = $_POST['tanggal'] ?? '';
    $jam_datang = !empty($_POST['jam_datang']) ? $_POST['jam_datang'] : null;
    $jam_pulang = !empty($_POST['jam_pulang']) ? $_POST['jam_pulang'] : null;
    $status_hadir = $_POST['status_hadir'] ?? '';

    // Validasi
    if (
        empty($nama_siswa) ||
        empty($nama_ekskul) ||
        empty($tanggal) ||
        empty($status_hadir)
    ) {

        $pesan = "Semua data wajib diisi!";
        $tipe_pesan = "error";

    } else {

        // ==========================================
        // 1. CARI SISWA BERDASARKAN NAMA
        // ==========================================

        $stmt_siswa = mysqli_prepare(
            $conn,
            "SELECT id FROM siswa WHERE nama_siswa = ? LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $stmt_siswa,
            "s",
            $nama_siswa
        );

        mysqli_stmt_execute($stmt_siswa);

        $hasil_siswa = mysqli_stmt_get_result($stmt_siswa);

        if (mysqli_num_rows($hasil_siswa) > 0) {

            // Siswa sudah ada
            $data_siswa = mysqli_fetch_assoc($hasil_siswa);

            $user_id = $data_siswa['id'];

        } else {

            // ==========================================
            // SISWA BELUM ADA → TAMBAHKAN OTOMATIS
            // ==========================================

            $stmt_tambah_siswa = mysqli_prepare(
                $conn,
                "INSERT INTO siswa (nama_siswa) VALUES (?)"
            );

            mysqli_stmt_bind_param(
                $stmt_tambah_siswa,
                "s",
                $nama_siswa
            );

            if (mysqli_stmt_execute($stmt_tambah_siswa)) {

                $user_id = mysqli_insert_id($conn);

            } else {

                $pesan = "Gagal menambahkan siswa: " . mysqli_error($conn);
                $tipe_pesan = "error";

            }
        }


        // ==========================================
        // 2. CARI EKSTRAKURIKULER BERDASARKAN NAMA
        // ==========================================

        if (empty($pesan)) {

            $stmt_ekskul = mysqli_prepare(
                $conn,
                "SELECT id FROM ekstrakurikuler 
                 WHERE nama_ekskul = ? 
                 LIMIT 1"
            );

            mysqli_stmt_bind_param(
                $stmt_ekskul,
                "s",
                $nama_ekskul
            );

            mysqli_stmt_execute($stmt_ekskul);

            $hasil_ekskul = mysqli_stmt_get_result($stmt_ekskul);

            if (mysqli_num_rows($hasil_ekskul) > 0) {

                // Ekstrakurikuler sudah ada
                $data_ekskul = mysqli_fetch_assoc($hasil_ekskul);

                $ekskul_id = $data_ekskul['id'];

            } else {

                // ==========================================
                // EKSKUL BELUM ADA → TAMBAHKAN OTOMATIS
                // ==========================================

                $stmt_tambah_ekskul = mysqli_prepare(
                    $conn,
                    "INSERT INTO ekstrakurikuler (nama_ekskul)
                     VALUES (?)"
                );

                mysqli_stmt_bind_param(
                    $stmt_tambah_ekskul,
                    "s",
                    $nama_ekskul
                );

                if (mysqli_stmt_execute($stmt_tambah_ekskul)) {

                    $ekskul_id = mysqli_insert_id($conn);

                } else {

                    $pesan = "Gagal menambahkan ekstrakurikuler: " . mysqli_error($conn);
                    $tipe_pesan = "error";

                }
            }
        }


        // ==========================================
        // 3. SIMPAN DATA ABSENSI
        // ==========================================

        if (empty($pesan)) {

            $stmt_absensi = mysqli_prepare(
                $conn,
                "INSERT INTO absensi
                (
                    user_id,
                    ekskul_id,
                    tanggal,
                    jam_datang,
                    jam_pulang,
                    status_hadir
                )
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt_absensi,
                "iissss",
                $user_id,
                $ekskul_id,
                $tanggal,
                $jam_datang,
                $jam_pulang,
                $status_hadir
            );

            if (mysqli_stmt_execute($stmt_absensi)) {

                header("Location: absensi.php");
                exit;

            } else {

                $pesan = "Gagal menyimpan absensi: " . mysqli_error($conn);
                $tipe_pesan = "error";

            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>Tambah Absensi</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f1f5f9;
}

/* SIDEBAR */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 215px;
    height: 100vh;
    background: #2949b9;
    color: white;
}

.logo {
    height: 75px;
    display: flex;
    align-items: center;
    padding: 0 30px;
    font-size: 20px;
    font-weight: bold;
    border-bottom: 1px solid rgba(255,255,255,0.2);
}

.menu {
    padding-top: 20px;
}

.menu a {
    display: block;
    padding: 14px 20px;
    color: white;
    text-decoration: none;
    font-size: 15px;
}

.menu a:hover,
.menu a.active {
    background: #203d9d;
}

.logout {
    position: absolute;
    bottom: 18px;
    width: 100%;
    background: #ed2424;
    padding: 15px;
    text-align: center;
}

.logout a {
    color: white;
    text-decoration: none;
}

/* CONTENT */

.content {
    margin-left: 215px;
    padding: 26px;
}

.card {
    background: white;
    border-radius: 7px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    overflow: hidden;
}

.card-header {
    background: #1976f3;
    color: white;
    padding: 13px 15px;
    font-size: 18px;
    font-weight: bold;
}

.card-body {
    padding: 18px;
}

.form-group {
    margin-bottom: 16px;
}

label {
    display: block;
    margin-bottom: 7px;
    font-weight: 500;
}

input,
select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
}

input:focus,
select:focus {
    outline: none;
    border-color: #1976f3;
}

/* PESAN */

.alert-error {
    background: #f8d7da;
    color: #842029;
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 15px;
}

/* BUTTON */

.btn-simpan {
    background: #198754;
    color: white;
    padding: 10px 17px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
}

.btn-simpan:hover {
    background: #157347;
}

.btn-kembali {
    display: inline-block;
    background: #6c757d;
    color: white;
    padding: 10px 17px;
    border-radius: 5px;
    text-decoration: none;
    margin-left: 5px;
}

.btn-kembali:hover {
    background: #5c636a;
}

</style>

</head>

<body>


<!-- SIDEBAR -->

<div class="sidebar">

    <div class="logo">
        🎓 Admin Ekskul
    </div>

    <div class="menu">

        <a href="dashboard_admin.php">
            🏠 &nbsp; Dashboard
        </a>

        <a href="ekstrakurikuler.php">
            ▣ &nbsp; Ekstrakurikuler
        </a>

        <a href="siswa.php">
            👥 &nbsp; Data Siswa
        </a>

        <a href="pendaftaran.php">
            👤 &nbsp; Pendaftaran
        </a>

        <a href="jadwal.php">
            📅 &nbsp; Jadwal
        </a>

        <a href="absensi.php" class="active">
            ✓ &nbsp; Absensi
        </a>

        <a href="laporan.php">
            ▤ &nbsp; Laporan
        </a>

    </div>

    <div class="logout">

        <a href="../logout.php">
            ↪ Logout
        </a>

    </div>

</div>


<!-- CONTENT -->

<div class="content">

    <div class="card">

        <div class="card-header">
            Tambah Absensi
        </div>

        <div class="card-body">


            <?php if (!empty($pesan)) { ?>

                <div class="alert-error">
                    <?= htmlspecialchars($pesan); ?>
                </div>

            <?php } ?>


            <form method="POST">


                <!-- NAMA SISWA -->

                <div class="form-group">

                    <label>
                        Nama Siswa
                    </label>

                    <input
                        type="text"
                        name="nama_siswa"
                        placeholder="Ketik nama siswa"
                        value="<?= htmlspecialchars($_POST['nama_siswa'] ?? ''); ?>"
                        required
                    >

                </div>


                <!-- EKSTRAKURIKULER -->

                <div class="form-group">

                    <label>
                        Ekstrakurikuler
                    </label>

                    <input
                        type="text"
                        name="nama_ekskul"
                        placeholder="Ketik nama ekstrakurikuler"
                        value="<?= htmlspecialchars($_POST['nama_ekskul'] ?? ''); ?>"
                        required
                    >

                </div>


                <!-- TANGGAL -->

                <div class="form-group">

                    <label>
                        Tanggal
                    </label>

                    <input
                        type="date"
                        name="tanggal"
                        value="<?= htmlspecialchars($_POST['tanggal'] ?? ''); ?>"
                        required
                    >

                </div>


                <!-- JAM DATANG -->

                <div class="form-group">

                    <label>
                        Jam Datang
                    </label>

                    <input
                        type="time"
                        name="jam_datang"
                        value="<?= htmlspecialchars($_POST['jam_datang'] ?? ''); ?>"
                    >

                </div>


                <!-- JAM PULANG -->

                <div class="form-group">

                    <label>
                        Jam Pulang
                    </label>

                    <input
                        type="time"
                        name="jam_pulang"
                        value="<?= htmlspecialchars($_POST['jam_pulang'] ?? ''); ?>"
                    >

                </div>


                <!-- STATUS -->

                <div class="form-group">

                    <label>
                        Status Kehadiran
                    </label>

                    <select
                        name="status_hadir"
                        required
                    >

                        <option value="">
                            -- Pilih Status --
                        </option>

                        <option
                            value="hadir"
                            <?= (($_POST['status_hadir'] ?? '') == 'hadir') ? 'selected' : ''; ?>
                        >
                            Hadir
                        </option>

                        <option
                            value="izin"
                            <?= (($_POST['status_hadir'] ?? '') == 'izin') ? 'selected' : ''; ?>
                        >
                            Izin
                        </option>

                        <option
                            value="sakit"
                            <?= (($_POST['status_hadir'] ?? '') == 'sakit') ? 'selected' : ''; ?>
                        >
                            Sakit
                        </option>

                        <option
                            value="alfa"
                            <?= (($_POST['status_hadir'] ?? '') == 'alfa') ? 'selected' : ''; ?>
                        >
                            Alfa
                        </option>

                    </select>

                </div>


                <!-- BUTTON -->

                <button
                    type="submit"
                    class="btn-simpan"
                >
                    Simpan
                </button>

                <a
                    href="absensi.php"
                    class="btn-kembali"
                >
                    Kembali
                </a>


            </form>

        </div>

    </div>

</div>

</body>

</html>