<?php
// Memulai session untuk menyimpan data login pengguna
session_start();

// Menghubungkan ke database melalui file koneksi
require 'koneksi.php';

// Memeriksa apakah pengguna sudah login, jika sudah langsung dialihkan ke dashboard masing-masing
if (isset($_SESSION['status']) && $_SESSION['status'] === 'login') {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin.php");
        exit;
    } else if ($_SESSION['role'] === 'masyarakat') {
        header("Location: masyarakat.php");
        exit;
    }
}

// Memproses data ketika tombol login ditekan
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];

    // Query untuk mencari user berdasarkan username
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) === 1) {
        $data = mysqli_fetch_assoc($result);

        // Memverifikasi password yang dimasukkan dengan password terenkripsi di database
        if (password_verify($password, $data['password'])) {
            // Menyimpan data penting ke dalam session
            $_SESSION['id_user']  = $data['id_user'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['role']     = $data['role'];
            $_SESSION['status']   = "login";

            if ($data['role'] === 'admin') {
                header("Location: admin.php");
                exit;
            } else if ($data['role'] === 'masyarakat') {
                header("Location: masyarakat.php");
                exit;
            }
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIPELDA</title>
    <link rel="stylesheet" href="asets/css/style.css">
    <style>
        /* Styling sederhana untuk tampilan form login */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-container h2 {
            margin-bottom: 5px;
            text-align: center;
            color: #333;
        }
        .login-container p {
            text-align: center;
            color: #777;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-login {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-login:hover {
            background-color: #0056b3;
        }
        .alert {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
            text-align: center;
        }
        .register-link {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>SIPELDA</h2>
    <p>Layanan Pengaduan Warga Kelurahan</p>

    <?php if (isset($error)) : ?>
        <div class="alert">
            <?= $error; ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Masukkan username anda" required autocomplete="off">
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Masukkan password anda" required>
        </div>

        <button type="submit" name="login" class="btn-login">Login</button>
    </form>

    <div class="register-link">
        Belum punya akun? <a href="register.php">Daftar di sini</a>
    </div>
</div>

</body>
</html>
