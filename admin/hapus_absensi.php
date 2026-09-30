<?php

require_once __DIR__ . "/../config/config.php";


if (!isset($_GET['id']) || empty($_GET['id'])) {

    header("Location: absensi.php");
    exit;

}


$id = (int) $_GET['id'];


$query = "
    DELETE FROM absensi
    WHERE id = $id
";


$result = mysqli_query($conn, $query);


if ($result) {

    header("Location: absensi.php");
    exit;

} else {

    die(
        "Gagal menghapus data absensi: "
        . mysqli_error($conn)
    );

}

?>