<?php
session_start();
require '../config/koneksi.php';

if (isset($_POST['cek_wa'])) {
    $no_telp = mysqli_real_escape_string($koneksi, $_POST['no_telp']);
    $query = mysqli_query($koneksi, "SELECT id_user, nama_lengkap FROM users WHERE no_telp = '$no_telp'");

    if ($user = mysqli_fetch_assoc($query)) {
        $_SESSION['reset_user_id'] = $user['id_user'];
        $_SESSION['reset_nama'] = $user['nama_lengkap'];
        header("Location: lupa_password.php?step=2");
        exit;
    } else {
        $error = "Nomor WhatsApp tidak terdaftar di sistem kami.";
    }
}

if (isset($_POST['reset_password'])) {
    if ($_POST['password_baru'] !== $_POST['konfirmasi']) {
        $error = "Kata sandi dan konfirmasi tidak cocok!";
    } else {
        $id_reset = $_SESSION['reset_user_id'];
        $hashed   = password_hash($_POST['password_baru'], PASSWORD_DEFAULT);

        if (mysqli_query($koneksi, "UPDATE users SET password = '$hashed' WHERE id_user = '$id_reset'")) {
            unset($_SESSION['reset_user_id'], $_SESSION['reset_nama']);
            $reset_sukses = true;
        }
    }
}

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Kata Sandi - SIPELDA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ANIMASI TRANSISI HALAMAN SMOOTH */
        @keyframes pageFadeIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f4f9;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
            animation: pageFadeIn 0.4s ease-out;
        }

        .card-reset {
            background-color: white;
            width: 100%;
            max-width: 500px;
            padding: 48px 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 40, 85, 0.08);
            box-sizing: border-box;
            border: 1px solid #e2e8f0;
        }

        .icon-top {
            width: 70px;
            height: 70px;
            background-color: #eef2f6;
            color: #002855;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 24px;
        }

        .card-reset h2 {
            text-align: center;
            color: #111827;
            margin: 0 0 12px;
            font-size: 26px;
            font-weight: 800;
        }

        .card-reset p {
            text-align: center;
            color: #6b7280;
            font-size: 15px;
            margin-bottom: 28px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 9px;
            font-size: 14px;
            font-weight: 700;
            color: #374151;
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon-wrapper i.icon-left {
            position: absolute;
            left: 16px;
            top: 17px;
            color: #9ca3af;
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 44px;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            box-sizing: border-box;
            outline: none;
            font-size: 15px;
            background-color: #fcfcfc;
            transition: 0.2s;
        }

        .form-control:focus {
            border-color: #002855;
            background-color: white;
            box-shadow: 0 0 0 4px rgba(0, 40, 85, 0.1);
        }

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            appearance: textfield;
        }

        .toggle-eye {
            position: absolute;
            right: 16px;
            top: 17px;
            cursor: pointer;
            color: #6b7280;
            font-size: 18px;
            transition: 0.2s;
        }

        .toggle-eye:hover {
            color: #002855;
        }

        .alert {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            text-align: center;
            border: 1px solid #fca5a5;
        }

        .btn-primary {
            width: 100%;
            background-color: #002855;
            color: white;
            border: none;
            padding: 16px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(0, 40, 85, 0.2);
        }

        .btn-primary:hover {
            background-color: #001a3b;
        }

        .footer-text {
            text-align: center;
            margin-top: 20px;
            font-size: 15px;
            color: #6b7280;
        }

        .footer-text a {
            color: #002855;
            text-decoration: none;
            font-weight: 700;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 20, 50, 0.7);
            backdrop-filter: blur(6px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .modal-box {
            background: #ffffff;
            padding: 40px;
            border-radius: 20px;
            max-width: 440px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
        }

        .modal-icon {
            width: 80px;
            height: 80px;
            background-color: #dcfce7;
            color: #16a34a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 20px;
        }

        .modal-box h3 {
            margin: 0 0 10px;
            color: #002855;
            font-size: 24px;
            font-weight: 800;
        }

        .modal-box p {
            color: #64748b;
            font-size: 15px;
            margin: 0 0 30px;
            line-height: 1.6;
        }

        .btn-lanjut {
            display: block;
            width: 100%;
            padding: 16px;
            background-color: #16a34a;
            color: white;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            box-sizing: border-box;
        }
    </style>
</head>

<body>

    <?php if (isset($reset_sukses) && $reset_sukses): ?>
        <div class="modal-overlay">
            <div class="modal-box">
                <div class="modal-icon"><i class="fa-solid fa-circle-check"></i></div>
                <h3>Sandi Berhasil Diubah!</h3>
                <p>Kata sandi baru Anda telah berhasil diperbarui. Silakan masuk menggunakan sandi baru Anda.</p>
                <a href="login.php" class="btn-lanjut">Masuk ke Login <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    <?php endif; ?>

    <div class="card-reset">
        <?php if ($step == '1'): ?>
            <div class="icon-top"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <h2>Atur Ulang Kata Sandi</h2>
            <p>Masukkan nomor WhatsApp yang terdaftar pada akun Anda untuk verifikasi identitas.</p>

            <?php if (isset($error)): ?><div class="alert"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div><?php endif; ?>

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

            <?php if (isset($error)): ?><div class="alert"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div><?php endif; ?>

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

                <div style="background:#eef2f6; color:#475569; padding:14px; font-size:13px; border-radius:8px; margin-bottom:20px; display:flex; gap:12px; align-items:center;">
                    <i class="fa-solid fa-circle-info" style="color:#002855; font-size:18px;"></i>
                    <span>Tips: Gunakan minimal 8 karakter dengan kombinasi huruf dan angka.</span>
                </div>

                <button type="submit" name="reset_password" class="btn-primary">Simpan Kata Sandi Baru</button>
            </form>

            <script>
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
