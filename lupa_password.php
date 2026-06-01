<?php
session_start();
require 'koneksi.php';

// TAHAP 1: Mengecek Nomor WA
if (isset($_POST['cek_wa'])) {
    $no_telp = mysqli_real_escape_string($koneksi, $_POST['no_telp']);
    $query = mysqli_query($koneksi, "SELECT id_user, nama_lengkap FROM users WHERE no_telp = '$no_telp'");

    // Cek dan ambil data dalam satu baris (Dioptimalkan)
    if ($user = mysqli_fetch_assoc($query)) {
        $_SESSION['reset_user_id'] = $user['id_user'];
        $_SESSION['reset_nama'] = $user['nama_lengkap'];
        header("Location: lupa_password.php?step=2");
        exit;
    } else {
        $error = "Nomor WhatsApp tidak terdaftar di sistem kami.";
    }
}

// TAHAP 2: Mengatur Kata Sandi Baru
if (isset($_POST['reset_password'])) {
    if ($_POST['password_baru'] !== $_POST['konfirmasi']) {
        $error = "Kata sandi dan konfirmasi tidak cocok!";
    } else {
        $id_reset = $_SESSION['reset_user_id'];
        $hashed   = password_hash($_POST['password_baru'], PASSWORD_DEFAULT);

        if (mysqli_query($koneksi, "UPDATE users SET password = '$hashed' WHERE id_user = '$id_reset'")) {
            unset($_SESSION['reset_user_id'], $_SESSION['reset_nama']); // Hapus session sekaligus
            echo "<script>alert('Kata sandi berhasil diatur ulang! Silakan masuk dengan kata sandi baru Anda.'); window.location.href='login.php';</script>";
            exit;
        }
    }
}

// Deteksi Tahap dari URL
$step = $_GET['step'] ?? '1';
if ($step == '2' && !isset($_SESSION['reset_user_id'])) {
    header("Location: lupa_password.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lupa Kata Sandi - SIPELDA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS Dioptimalkan */
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #f4f7fb; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        
        .card-reset { background-color: white; width: 100%; max-width: 450px; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); box-sizing: border-box; }
        
        .icon-top { width: 60px; height: 60px; background-color: #eef2f6; color: #002855; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 20px; }
        
        .card-reset h2 { text-align: center; color: #111827; margin: 0 0 10px; font-size: 22px; }
        .card-reset p { text-align: center; color: #6b7280; font-size: 14px; margin-bottom: 25px; line-height: 1.5; }
        
        .form-group { margin-bottom: 20px; position: relative; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: bold; color: #374151; }
        
        .input-icon-wrapper { position: relative; }
        .input-icon-wrapper i.icon-left { position: absolute; left: 12px; top: 14px; color: #9ca3af; }
        
        .form-control { width: 100%; padding: 12px 15px 12px 32px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; outline: none; font-size: 14px; background-color: #fcfcfc; transition: 0.2s; }
        .form-control:focus { border-color: #002855; background-color: white; }
        
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { appearance: textfield; }
        
        .toggle-eye { position: absolute; right: 15px; top: 14px; cursor: pointer; color: #6b7280; font-size: 16px; transition: 0.2s; }
        .toggle-eye:hover { color: #002855; }
        
        .alert { background-color: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; text-align: center; }
        
        .btn-primary { width: 100%; background-color: #002855; color: white; border: none; padding: 14px; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 5px; }
        .btn-primary:hover { background-color: #001a3b; }
        
        .footer-text { text-align: center; margin-top: 15px; font-size: 13px; color: #6b7280; }
        .footer-text a { color: #002855; text-decoration: none; font-weight: bold; transition: 0.2s; }
        .footer-text a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="card-reset">
        <?php if ($step == '1'): ?>
            <div class="icon-top"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <h2>Atur Ulang Kata Sandi</h2>
            <p>Masukkan nomor WhatsApp yang terdaftar pada akun Anda. Kami akan mengirimkan instruksi untuk mengatur ulang kata sandi Anda.</p>

            <?php if (isset($error)): ?><div class="alert"><?= $error ?></div><?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>No. WhatsApp / Telepon Terdaftar</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-mobile-screen icon-left"></i>
                        <input type="number" name="no_telp" class="form-control" placeholder="Contoh: 081234567890" required>
                    </div>
                </div>
                <button type="submit" name="cek_wa" class="btn-primary">Kirim Instruksi Reset Password</button>
            </form>

            <div class="footer-text">
                Ingat kata sandi Anda? <a href="login.php">Kembali ke Login</a>
            </div>

        <?php elseif ($step == '2'): ?>
            <div class="icon-top"><i class="fa-solid fa-lock"></i></div>
            <h2>Buat Kata Sandi Baru</h2>
            <p>Silakan masukkan kata sandi baru Anda yang kuat dan aman untuk mengamankan akun Anda kembali.</p>

            <?php if (isset($error)): ?><div class="alert"><?= $error ?></div><?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Kata Sandi Baru</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-lock icon-left"></i>
                        <input type="password" name="password_baru" class="form-control" placeholder="Masukkan kata sandi baru" required minlength="8">
                        <i class="fa-solid fa-eye-slash toggle-eye"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Kata Sandi Baru</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-lock icon-left"></i>
                        <input type="password" name="konfirmasi" class="form-control" placeholder="Ulangi kata sandi baru" required minlength="8">
                        <i class="fa-solid fa-eye-slash toggle-eye"></i>
                    </div>
                </div>

                <div style="background:#eef2f6; color:#475569; padding:12px; font-size:12px; border-radius:6px; margin-bottom:15px; display:flex; gap:10px; align-items:center;">
                    <i class="fa-solid fa-circle-info" style="color:#002855; font-size:16px;"></i>
                    <span>Tips: Gunakan minimal 8 karakter dengan kombinasi huruf dan angka.</span>
                </div>

                <button type="submit" name="reset_password" class="btn-primary">Simpan Kata Sandi Baru</button>
            </form>

            <script>
                // JS Dioptimalkan: Satu fungsi dinamis untuk semua input password
                document.querySelectorAll('.toggle-eye').forEach(icon => {
                    icon.addEventListener('click', function() {
                        const field = this.previousElementSibling;
                        field.type = field.type === 'password' ? 'text' : 'password';
                        this.classList.toggle('fa-eye');
                        this.classList.toggle('fa-eye-slash');
                    });
                });
            </script>
        <?php endif; ?>
    </div>

</body>
</html>
