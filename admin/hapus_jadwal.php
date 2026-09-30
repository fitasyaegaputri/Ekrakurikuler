<?php

require_once __DIR__ . "/../config/config.php";

if (!$conn) {
    die("Database tidak terhubung");
}


if (!isset($_GET['id'])) {

    die("ID jadwal tidak ditemukan");

}


$id = intval($_GET['id']);


$query = mysqli_query($conn, "
    DELETE FROM jadwal
    WHERE id = $id
");


if ($query) {

    header("Location: jadwal.php");
    exit;

} else {

    die("Gagal menghapus data : " . mysqli_error($conn));

}

?>