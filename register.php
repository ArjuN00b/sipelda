<?php
require 'koneksi.php';

if (isset($_POST['register'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];
    
    // Enkripsi password untuk keamanan
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Default role untuk yang register mandiri adalah masyarakat
    $role = 'masyarakat'; 

    $query = "INSERT INTO users (username, password, role) VALUES ('$username', '$hashed_password', '$role')";
    
    if (mysqli_query($koneksi, $query)) {
        echo "<script>
                alert('Registrasi berhasil! Silakan login.');
                window.location.href = 'login.php';
              </script>";
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registrasi</title>
</head>
<body>
    <h2>Halaman Registrasi</h2>
    <form method="POST" action="">
        <input type="text" name="username" placeholder="Username" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <button type="submit" name="register">Daftar</button>
    </form>
    <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
</body>
</html>
