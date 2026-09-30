<?php

require_once __DIR__ . "/../config/config.php";

if (!isset($_GET['id'])) {
    die("ID tidak ditemukan.");
}

$id = (int) $_GET['id'];

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM pendaftaran WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

header("Location: daftar.php");
exit;