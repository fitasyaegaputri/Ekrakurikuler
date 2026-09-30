<?php

require_once __DIR__ . "/../config/config.php";

if (!isset($_GET['id'])) {
    die("ID tidak ditemukan.");
}

$id = (int) $_GET['id'];

$data = mysqli_query(
    $conn,
    "SELECT * FROM pendaftaran WHERE id = $id"
);

if (mysqli_num_rows($data) == 0) {
    die("Data tidak ditemukan.");
}

$row = mysqli_fetch_assoc($data);


if (isset($_POST['update'])) {

    $siswa_id = (int) $_POST['siswa_id'];
    $ekskul_id = (int) $_POST['ekskul_id'];
    $tanggal = $_POST['tanggal_daftar'];
    $status = $_POST['status'];

    $stmt = mysqli_prepare($conn, "

        UPDATE pendaftaran

        SET
            siswa_id = ?,
            ekskul_id = ?,
            tanggal_daftar = ?,
            status = ?

        WHERE id = ?

    ");

    mysqli_stmt_bind_param(
        $stmt,
        "iissi",
        $siswa_id,
        $ekskul_id,
        $tanggal,
        $status,
        $id
    );

    if (mysqli_stmt_execute($stmt)) {

        header("Location: daftar.php");
        exit;

    } else {

        die("Gagal update: " . mysqli_error($conn));

    }
}


$siswa = mysqli_query(
    $conn,
    "SELECT id, nama_siswa FROM siswa ORDER BY nama_siswa"
);

$ekskul = mysqli_query(
    $conn,
    "SELECT id, nama_ekskul FROM ekstrakurikuler ORDER BY nama_ekskul"
);

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>Edit Pendaftaran</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-warning">

<h4>Edit Pendaftaran</h4>

</div>

<div class="card-body">

<form method="POST">


<div class="mb-3">

<label>Nama Siswa</label>

<select name="siswa_id" class="form-select" required>

<?php while ($s = mysqli_fetch_assoc($siswa)): ?>

<option
value="<?= $s['id']; ?>"
<?= $s['id'] == $row['siswa_id'] ? 'selected' : ''; ?>
>

<?= htmlspecialchars($s['nama_siswa']); ?>

</option>

<?php endwhile; ?>

</select>

</div>


<div class="mb-3">

<label>Ekstrakurikuler</label>

<select name="ekskul_id" class="form-select" required>

<?php while ($e = mysqli_fetch_assoc($ekskul)): ?>

<option
value="<?= $e['id']; ?>"
<?= $e['id'] == $row['ekskul_id'] ? 'selected' : ''; ?>
>

<?= htmlspecialchars($e['nama_ekskul']); ?>

</option>

<?php endwhile; ?>

</select>

</div>


<div class="mb-3">

<label>Tanggal Daftar</label>

<input
type="date"
name="tanggal_daftar"
class="form-control"
value="<?= htmlspecialchars($row['tanggal_daftar']); ?>"
required>

</div>


<div class="mb-3">

<label>Status</label>

<select name="status" class="form-select">

<option
value="menunggu"
<?= $row['status'] == 'menunggu' ? 'selected' : ''; ?>
>
Menunggu
</option>

<option
value="diterima"
<?= $row['status'] == 'diterima' ? 'selected' : ''; ?>
>
Diterima
</option>

<option
value="ditolak"
<?= $row['status'] == 'ditolak' ? 'selected' : ''; ?>
>
Ditolak
</option>

</select>

</div>


<button
type="submit"
name="update"
class="btn btn-warning">

Update

</button>

<a
href="daftar.php"
class="btn btn-secondary">

Kembali

</a>

</form>

</div>

</div>

</div>

</body>
</html>