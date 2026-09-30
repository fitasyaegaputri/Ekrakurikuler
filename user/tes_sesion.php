<?php

session_start();

echo "<h2>CEK SESSION</h2>";

echo "ID: ";
echo $_SESSION['id'] ?? 'TIDAK ADA';

echo "<br>";

echo "Nama: ";
echo $_SESSION['nama'] ?? 'TIDAK ADA';

echo "<br>";

echo "Role: ";
echo $_SESSION['role'] ?? 'TIDAK ADA';

?>