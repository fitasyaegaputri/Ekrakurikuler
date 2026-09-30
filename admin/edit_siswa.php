<?php

session_start();

require_once __DIR__ . "/../config/config.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

/* Ambil ID siswa dari URL */
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    die("ID siswa tidak valid.");
}

/* Ambil data siswa */
$query = mysqli_query(
    $conn,
    "SELECT *
     FROM siswa
     WHERE id = '$id'
     LIMIT 1"
);

if (!$query || mysqli_num_rows($query) == 0) {
    die("Data siswa tidak ditemukan.");
}

$siswa = mysqli_fetch_assoc($query);

/* ================================
   SIMPAN PERUBAHAN
================================ */

if (isset($_POST['simpan'])) {

    $nama_siswa   = mysqli_real_escape_string($conn, trim($_POST['nama_siswa']));
    $nis          = mysqli_real_escape_string($conn, trim($_POST['nis']));
    $kelas        = mysqli_real_escape_string($conn, trim($_POST['kelas']));
    $jenis_kelamin = mysqli_real_escape_string($conn, trim($_POST['jenis_kelamin']));
    $alamat       = mysqli_real_escape_string($conn, trim($_POST['alamat']));

    if ($nama_siswa == "" || $kelas == "" || $jenis_kelamin == "") {

        $error = "Nama, kelas, dan jenis kelamin wajib diisi.";

    } else {

        $update = mysqli_query(
            $conn,
            "UPDATE siswa SET
                nama_siswa = '$nama_siswa',
                nis = '$nis',
                kelas = '$kelas',
                jenis_kelamin = '$jenis_kelamin',
                alamat = '$alamat'
             WHERE id = '$id'"
        );

        if ($update) {

            header("Location: data_siswa.php?success=Data berhasil diperbarui");
            exit();

        } else {

            $error = "Gagal memperbarui data: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit Data Siswa</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

</head>

<body>

<div class="container mt-5">

    <div class="card shadow">

        <div class="card-header bg-primary text-white">

            <h5 class="mb-0">
                Edit Data Siswa
            </h5>

        </div>

        <div class="card-body">

            <?php if (isset($error)) { ?>

                <div class="alert alert-danger">
                    <?= htmlspecialchars($error); ?>
                </div>

            <?php } ?>

            <form method="POST">

                <div class="mb-3">

                    <label class="form-label">
                        Nama Siswa
                    </label>

                    <input
                        type="text"
                        name="nama_siswa"
                        class="form-control"
                        value="<?= htmlspecialchars($siswa['nama_siswa'] ?? ''); ?>"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        NIS
                    </label>

                    <input
                        type="text"
                        name="nis"
                        class="form-control"
                        value="<?= htmlspecialchars($siswa['nis'] ?? ''); ?>"
                    >

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Kelas
                    </label>

                    <input
                        type="text"
                        name="kelas"
                        class="form-control"
                        value="<?= htmlspecialchars($siswa['kelas'] ?? ''); ?>"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Jenis Kelamin
                    </label>

                    <select
                        name="jenis_kelamin"
                        class="form-select"
                        required
                    >

                        <option value="">
                            -- Pilih Jenis Kelamin --
                        </option>

                        <option
                            value="Laki-laki"
                            <?= ($siswa['jenis_kelamin'] ?? '') == 'Laki-laki' ? 'selected' : ''; ?>
                        >
                            Laki-laki
                        </option>

                        <option
                            value="Perempuan"
                            <?= ($siswa['jenis_kelamin'] ?? '') == 'Perempuan' ? 'selected' : ''; ?>
                        >
                            Perempuan
                        </option>

                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Alamat
                    </label>

                    <textarea
                        name="alamat"
                        class="form-control"
                        rows="3"
                    ><?= htmlspecialchars($siswa['alamat'] ?? ''); ?></textarea>

                </div>

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        name="simpan"
                        class="btn btn-primary"
                    >
                        Simpan Perubahan
                    </button>

                    <a
                        href="data_siswa.php"
                        class="btn btn-secondary"
                    >
                        Kembali
                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

</body>

</html>