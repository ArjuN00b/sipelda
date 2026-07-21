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
            $register_sukses = true;
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
        @keyframes pageFadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        body { 
            font-family: 'Segoe UI', Tahoma, sans-serif; 
            background-color: #f4f7fb; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
            animation: pageFadeIn 0.4s ease-out;
        }
        
        .top-nav { width: 100%; padding: 25px 40px; box-sizing: border-box; }
        .top-nav a { text-decoration: none; color: #002855; font-weight: bold; font-size: 16px; display: inline-flex; align-items: center; gap: 8px; }
        
        .register-card { 
            background-color: #fff; 
            width: 100%; 
            max-width: 580px; 
            padding: 50px 45px; 
            border-radius: 16px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
            margin-bottom: 40px; 
            box-sizing: border-box; 
        }
        .header-icon { 
            background-color: #e2e8f0; 
            color: #002855; 
            width: 80px; 
            height: 80px; 
            border-radius: 18px; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            margin: 0 auto 24px; 
            font-size: 36px; 
        }
        
        .register-card h2 { text-align: center; color: #002855; margin: 0 0 10px; font-size: 28px; font-weight: 800; }
        .register-card p.subtitle { text-align: center; color: #64748b; font-size: 15px; margin: 0 0 35px; }
        
        .alert { background-color: #fee2e2; color: #b91c1c; padding: 14px; border-radius: 10px; margin-bottom: 24px; font-size: 15px; text-align: center; }
        
        .form-group { margin-bottom: 22px; position: relative; }
        .form-group label { display: block; margin-bottom: 8px; color: #0f172a; font-size: 14px; font-weight: bold; }
        .form-control { 
            width: 100%; 
            padding: 15px; 
            border: 1.5px solid #cbd5e1; 
            border-radius: 10px; 
            font-size: 15px; 
            outline: none; 
            box-sizing: border-box; 
            background-color: #f8fafc; 
            transition: 0.2s; 
        }
        .form-control:focus { border-color: #002855; background-color: #fff; }
        
        .row-grid { display: flex; gap: 15px; }
        .row-grid .form-group { flex: 1; margin-bottom: 0; }
        
        .toggle-eye { position: absolute; right: 18px; top: 46px; cursor: pointer; color: #64748b; font-size: 16px; transition: 0.2s; }
        .toggle-eye:hover { color: #002855; }
        
        .btn-register { 
            width: 100%; 
            background-color: #002855; 
            color: white; 
            padding: 16px; 
            border: none; 
            border-radius: 10px; 
            font-size: 16px; 
            font-weight: bold; 
            cursor: pointer; 
            margin-top: 30px; 
            transition: 0.3s; 
        }
        .btn-register:hover { background-color: #001a3b; }
        
        /* MODAL POPUP */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0, 20, 50, 0.7);
            backdrop-filter: blur(6px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            animation: fadeIn 0.3s ease-in-out;
        }

        .modal-box {
            background: #ffffff;
            padding: 40px;
            border-radius: 20px;
            max-width: 440px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.25);
            animation: slideUp 0.3s ease-in-out;
        }

        .modal-icon-success {
            width: 80px; height: 80px;
            background-color: #dcfce7;
            color: #16a34a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            margin: 0 auto 20px;
        }

        .modal-box h3 { margin: 0 0 10px; color: #002855; font-size: 24px; font-weight: 800; }
        .modal-box p { color: #64748b; font-size: 15px; margin: 0 0 30px; line-height: 1.6; }

        .btn-modal-close {
            display: block;
            width: 100%;
            padding: 16px;
            background-color: #002855;
            color: white;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            box-sizing: border-box;
            transition: 0.2s;
        }
        .btn-modal-close:hover { background-color: #001a3b; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { appearance: textfield; }
    </style>
</head>
<body>

    <!-- MODAL POPUP SUKSES PENDAFTARAN -->
    <?php if (isset($register_sukses) && $register_sukses): ?>
        <div class="modal-overlay">
            <div class="modal-box">
                <div class="modal-icon-success"><i class="fa-solid fa-circle-check"></i></div>
                <h3>Pendaftaran Berhasil!</h3>
                <p>Akun warga Anda telah sukses terdaftar di sistem SIPELDA. Silakan lanjut untuk masuk ke aplikasi.</p>
                <a href="login.php" class="btn-modal-close">Lanjut ke Halaman Login</a>
            </div>
        </div>
    <?php endif; ?>

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
