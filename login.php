<?php
session_start();
require 'koneksi.php';

// Jika sudah login, langsung arahkan sesuai role
if (isset($_SESSION['status']) && $_SESSION['status'] === 'login') {
    header("Location: " . ($_SESSION['role'] === 'admin' ? "admin.php" : "index.php"));
    exit;
}

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$username'");

    // Cek ketersediaan user dan ambil datanya sekaligus
    if ($data = mysqli_fetch_assoc($query)) {
        if (password_verify($_POST['password'], $data['password'])) {
            $_SESSION['id_user']  = $data['id_user'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['role']     = $data['role'];
            $_SESSION['status']   = "login";

            header("Location: " . ($data['role'] === 'admin' ? "admin.php" : "index.php"));
            exit;
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
    <title>Login - SIPELDA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS Dioptimalkan */
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        
        .login-container { background-color: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); width: 100%; max-width: 400px; box-sizing: border-box; }
        .login-container h2 { margin: 0 0 5px; text-align: center; color: #002855; font-size: 28px; }
        .login-container p { text-align: center; color: #777; margin: 0 0 30px; font-size: 14px; }
        
        .form-group { margin-bottom: 20px; position: relative; }
        .form-group label { display: block; margin-bottom: 8px; color: #555; font-weight: bold; font-size: 14px; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; outline: none; transition: 0.2s; }
        .form-group input:focus { border-color: #002855; }
        
        .toggle-eye { position: absolute; right: 15px; top: 38px; cursor: pointer; color: #64748b; font-size: 16px; transition: 0.2s; }
        .toggle-eye:hover { color: #002855; }
        
        .btn-login { width: 100%; padding: 14px; background-color: #002855; border: none; color: white; font-size: 16px; font-weight: bold; border-radius: 6px; cursor: pointer; transition: 0.3s; }
        .btn-login:hover { background-color: #001a3b; }
        
        .alert { background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        
        .link-bawah { text-align: center; margin-top: 20px; font-size: 14px; }
        .link-bawah a { color: #002855; text-decoration: none; font-weight: bold; }
        .forgot-pass { text-align: right; margin-bottom: 20px; }
        .forgot-pass a { font-size: 13px; color: #dc3545; text-decoration: none; font-weight: 500; }
    </style>
</head>

<body>

    <div class="login-container">
        <h2>SIPELDA</h2>
        <p>Layanan Pengaduan Warga Kelurahan</p>

        <?php if (isset($error)) : ?><div class="alert"><?= $error; ?></div><?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Masukkan username Anda" required autocomplete="off">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Masukkan kata sandi Anda" required>
                <i class="fa-solid fa-eye toggle-eye"></i>
            </div>

            <div class="forgot-pass">
                <a href="lupa_password.php">Lupa Kata Sandi?</a>
            </div>

            <button type="submit" name="login" class="btn-login">Masuk ke Sistem</button>
        </form>

        <div class="link-bawah">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </div>
    </div>

    <script>
        // JS Dioptimalkan
        document.querySelector('.toggle-eye').addEventListener('click', function() {
            const field = this.previousElementSibling;
            field.type = field.type === 'password' ? 'text' : 'password';
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>
