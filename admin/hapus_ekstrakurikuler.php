<?php
require_once __DIR__ . "/../config/config.php";


$id=$_GET['id'];


mysqli_query($conn,

"DELETE FROM ekstrakurikuler WHERE id='$id'");


header("location:ekstrakurikuler.php");

?>