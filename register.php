<?php
session_start();
require 'koneksi.php';

if (isset($_POST['register'])) {
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $username     = mysqli_real_escape_string($koneksi, $_POST['username']);
    $no_telp      = mysqli_real_escape_string($koneksi, $_POST['no_telp']);
    $password     = $_POST['password'];
    $konfirmasi   = $_POST['konfirmasi'];

    // Validasi satu pintu (Dioptimalkan)
    if ($password !== $konfirmasi) {
        $error = "Kata sandi dan konfirmasi tidak cocok!";
    } elseif (mysqli_num_rows(mysqli_query($koneksi, "SELECT username FROM users WHERE username = '$username'")) > 0) {
        $error = "Username sudah digunakan, silakan pilih yang lain!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (nama_lengkap, username, no_telp, password, role) 
                  VALUES ('$nama_lengkap', '$username', '$no_telp', '$hashed_password', 'masyarakat')";

        if (mysqli_query($koneksi, $query)) {
            echo "<script>alert('Pendaftaran berhasil! Silakan masuk dengan akun baru Anda.'); window.location.href = 'login.php';</script>";
            exit;
        } else {
            $error = "Terjadi kesalahan sistem!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pendaftaran Akun - SIPELDA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS Dioptimalkan */
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #f4f7fb; display: flex; flex-direction: column; align-items: center; min-height: 100vh; margin: 0; }
        
        .top-nav { width: 100%; padding: 20px 30px; box-sizing: border-box; }
        .top-nav a { text-decoration: none; color: #002855; font-weight: bold; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; }
        
        .register-card { background-color: #fff; width: 100%; max-width: 500px; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 40px; box-sizing: border-box; }
        .header-icon { background-color: #e2e8f0; color: #002855; width: 65px; height: 65px; border-radius: 14px; display: flex; justify-content: center; align-items: center; margin: 0 auto 20px; font-size: 28px; }
        
        .register-card h2 { text-align: center; color: #002855; margin: 0 0 8px; font-size: 22px; font-weight: 800; }
        .register-card p.subtitle { text-align: center; color: #64748b; font-size: 13px; margin: 0 0 30px; }
        
        .alert { background-color: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; text-align: center; }
        
        .form-group { margin-bottom: 18px; position: relative; }
        .form-group label { display: block; margin-bottom: 6px; color: #0f172a; font-size: 12px; font-weight: bold; }
        .form-control { width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; box-sizing: border-box; background-color: #f8fafc; transition: 0.2s; }
        .form-control:focus { border-color: #002855; background-color: #fff; }
        
        .row-grid { display: flex; gap: 15px; }
        .row-grid .form-group { flex: 1; margin-bottom: 0; }
        
        .toggle-eye { position: absolute; right: 15px; top: 34px; cursor: pointer; color: #64748b; font-size: 14px; transition: 0.2s; }
        .toggle-eye:hover { color: #002855; }
        
        .btn-register { width: 100%; background-color: #002855; color: white; padding: 14px; border: none; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; margin-top: 25px; transition: 0.3s; }
        .btn-register:hover { background-color: #001a3b; }
        
        /* Menghilangkan panah atas-bawah di input number */
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { appearance: textfield; }
    </style>
</head>
<body>

    <div class="top-nav">
        <a href="login.php"><i class="fa-solid fa-arrow-left"></i> Kembali ke Login</a>
    </div>

    <div class="register-card">
        <div class="header-icon"><i class="fa-solid fa-address-card"></i></div>
        <h2>Pendaftaran Akun Warga</h2>
        <p class="subtitle">Lengkapi data diri Anda untuk mengakses layanan.</p>

        <?php if (isset($error)) : ?><div class="alert"><?= $error; ?></div><?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-control" placeholder="Sesuai KTP" required>
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" placeholder="Pilih username unik" required autocomplete="off">
            </div>

            <div class="form-group">
                <label>No. WhatsApp / Telepon</label>
                <input type="number" name="no_telp" class="form-control" placeholder="Contoh: 081234567890" required>
            </div>

            <div class="row-grid">
                <div class="form-group">
                    <label>Kata Sandi</label>
                    <input type="password" name="password" class="form-control field-pass" placeholder="Minimal 8 karakter" required minlength="8">
                    <i class="fa-solid fa-eye toggle-eye"></i>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Sandi</label>
                    <input type="password" name="konfirmasi" class="form-control field-pass" placeholder="Ulangi sandi" required minlength="8">
                    <i class="fa-solid fa-eye toggle-eye"></i>
                </div>
            </div>

            <button type="submit" name="register" class="btn-register">Daftar Akun Sekarang</button>
        </form>
    </div>

    <script>
        // JS Dioptimalkan: Satu fungsi dinamis untuk semua tombol toggle mata
        document.querySelectorAll('.toggle-eye').forEach(icon => {
            icon.addEventListener('click', function() {
                // Mencari input yang posisinya persis sebelum ikon mata ini
                const field = this.previousElementSibling; 
                field.type = field.type === 'password' ? 'text' : 'password';
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>
</html>
