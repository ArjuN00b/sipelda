<?php
session_start();

// Validasi sesi: Apakah sudah login dan apakah rolenya admin?
if (!isset($_SESSION['status']) || $_SESSION['role'] != 'admin') {
    echo "<script>
            alert('Akses Ditolak! Anda bukan Admin.');
            window.location.href = 'login.php';
            </script>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Admin</title>
</head>
<body>
    <h1>Selamat Datang, Admin <?php echo $_SESSION['username']; ?>!</h1>
    <p>Ini adalah halaman khusus administrator.</p>
    
    <a href="logout.php">Logout</a> </body>
</html>
