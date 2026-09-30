<?php

require_once __DIR__ . "/../config/config.php";

if (!$conn) {

    die("Database tidak terhubung");

}


// CEK ID

if (!isset($_GET['id'])) {

    die("ID siswa tidak ditemukan");

}


$id = intval($_GET['id']);


// HAPUS DATA

$query = mysqli_query(
    $conn,
    "DELETE FROM siswa WHERE id = $id"
);


if ($query) {

    header("Location: data_siswa.php");

    exit;

} else {

    die(
        "Gagal menghapus data siswa: "
        . mysqli_error($conn)
    );

}

?>