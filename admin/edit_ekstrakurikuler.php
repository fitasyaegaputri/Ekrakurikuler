<?php
require_once __DIR__ . "/../config/config.php";


$id=$_GET['id'];


$data=mysqli_query($conn,

"SELECT * FROM ekstrakurikuler WHERE id='$id'");


$row=mysqli_fetch_assoc($data);



if(isset($_POST['update'])){


mysqli_query($conn,

"UPDATE ekstrakurikuler SET

nama_ekskul='$_POST[nama]',
deskripsi='$_POST[deskripsi]',
pembina='$_POST[pembina]',
jadwal='$_POST[jadwal]',
lokasi='$_POST[lokasi]'

WHERE id='$id'

");


header("location:ekstrakurikuler.php");


}


?>


<form method="POST">


Nama

<input name="nama"
value="<?= $row['nama_ekskul']; ?>">


<br>


Deskripsi

<textarea name="deskripsi">

<?= $row['deskripsi']; ?>

</textarea>


<br>


Pembina

<input name="pembina"
value="<?= $row['pembina']; ?>">


<br>


Jadwal

<input name="jadwal"
value="<?= $row['jadwal']; ?>">


<br>


Lokasi

<input name="lokasi"
value="<?= $row['lokasi']; ?>">



<br>


<button name="update">

Update

</button>


</form>