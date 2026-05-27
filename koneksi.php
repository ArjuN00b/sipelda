<?php
$host     = "localhost";
$user     = "kelompok2"; // Default XAMPP/Laragon
$password = "12345";     // Kosongkan jika default
$db       = "db_sipelda"; // Ses_uaikan dengan nama database kamu


$koneksi = mysqli_connect($host, $user, $password, $db);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>
