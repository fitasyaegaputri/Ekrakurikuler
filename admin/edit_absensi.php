<?php

require_once __DIR__ . "/../config/config.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID absensi tidak ditemukan.");
}

$id = (int) $_GET['id'];


// ==============================
// PROSES UPDATE
// ==============================

if (isset($_POST['update'])) {

    $user_id = (int) $_POST['user_id'];
    $ekskul_id = (int) $_POST['ekskul_id'];
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $jam_datang = !empty($_POST['jam_datang'])
        ? "'" . mysqli_real_escape_string($conn, $_POST['jam_datang']) . "'"
        : "NULL";

    $jam_pulang = !empty($_POST['jam_pulang'])
        ? "'" . mysqli_real_escape_string($conn, $_POST['jam_pulang']) . "'"
        : "NULL";

    $status_hadir = mysqli_real_escape_string(
        $conn,
        $_POST['status_hadir']
    );


    $query_update = "
        UPDATE absensi SET
            user_id = $user_id,
            ekskul_id = $ekskul_id,
            tanggal = '$tanggal',
            jam_datang = $jam_datang,
            jam_pulang = $jam_pulang,
            status_hadir = '$status_hadir'
        WHERE id = $id
    ";


    if (mysqli_query($conn, $query_update)) {

        header("Location: laporan.php");
        exit;

    } else {

        $error = "Gagal mengupdate absensi: "
               . mysqli_error($conn);

    }
}


// ==============================
// AMBIL DATA ABSENSI
// ==============================

$query_absensi = "
    SELECT *
    FROM absensi
    WHERE id = $id
";

$result_absensi = mysqli_query($conn, $query_absensi);

$data = mysqli_fetch_assoc($result_absensi);

if (!$data) {
    die("Data absensi tidak ditemukan.");
}


// ==============================
// DATA SISWA
// ==============================

$query_siswa = "
    SELECT id, nama_siswa
    FROM siswa
    ORDER BY nama_siswa ASC
";

$result_siswa = mysqli_query($conn, $query_siswa);


// ==============================
// DATA EKSTRAKURIKULER
// ==============================

$query_ekskul = "
    SELECT id, nama_ekskul
    FROM ekstrakurikuler
    ORDER BY nama_ekskul ASC
";

$result_ekskul = mysqli_query($conn, $query_ekskul);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Absensi</title>

</head>

<body>

<h2>Edit Absensi</h2>

<?php if (isset($error)) { ?>

    <p style="color:red;">
        <?= htmlspecialchars($error); ?>
    </p>

<?php } ?>


<form method="POST">

    <label>Nama Siswa</label><br>

    <select name="user_id" required>

        <option value="">
            -- Pilih Siswa --
        </option>

        <?php while ($siswa = mysqli_fetch_assoc($result_siswa)) { ?>

            <option
                value="<?= $siswa['id']; ?>"
                <?= ($data['user_id'] == $siswa['id'])
                    ? 'selected'
                    : ''; ?>
            >

                <?= htmlspecialchars(
                    $siswa['nama_siswa']
                ); ?>

            </option>

        <?php } ?>

    </select>

    <br><br>


    <label>Ekstrakurikuler</label><br>

    <select name="ekskul_id" required>

        <option value="">
            -- Pilih Ekstrakurikuler --
        </option>

        <?php while ($ekskul = mysqli_fetch_assoc($result_ekskul)) { ?>

            <option
                value="<?= $ekskul['id']; ?>"
                <?= ($data['ekskul_id'] == $ekskul['id'])
                    ? 'selected'
                    : ''; ?>
            >

                <?= htmlspecialchars(
                    $ekskul['nama_ekskul']
                ); ?>

            </option>

        <?php } ?>

    </select>

    <br><br>


    <label>Tanggal</label><br>

    <input
        type="date"
        name="tanggal"
        value="<?= htmlspecialchars($data['tanggal']); ?>"
        required
    >

    <br><br>


    <label>Jam Datang</label><br>

    <input
        type="time"
        name="jam_datang"
        value="<?= htmlspecialchars(
            $data['jam_datang'] ?? ''
        ); ?>"
    >

    <br><br>


    <label>Jam Pulang</label><br>

    <input
        type="time"
        name="jam_pulang"
        value="<?= htmlspecialchars(
            $data['jam_pulang'] ?? ''
        ); ?>"
    >

    <br><br>


    <label>Status Kehadiran</label><br>

    <select name="status_hadir" required>

        <option value="hadir"
            <?= $data['status_hadir'] == 'hadir'
                ? 'selected'
                : ''; ?>>
            Hadir
        </option>

        <option value="izin"
            <?= $data['status_hadir'] == 'izin'
                ? 'selected'
                : ''; ?>>
            Izin
        </option>

        <option value="sakit"
            <?= $data['status_hadir'] == 'sakit'
                ? 'selected'
                : ''; ?>>
            Sakit
        </option>

        <option value="alfa"
            <?= $data['status_hadir'] == 'alfa'
                ? 'selected'
                : ''; ?>>
            Alfa
        </option>

    </select>

    <br><br>


    <button type="submit" name="update">
        Update
    </button>

    <a href="laporan.php">
        Kembali
    </a>

</form>

</body>

</html>