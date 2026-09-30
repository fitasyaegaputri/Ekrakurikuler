<?php

session_start();

// hapus semua session
session_unset();

// hancurkan session
session_destroy();


// kembali ke halaman login
header("location:login.php");

exit;

?>