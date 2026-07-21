<?php
session_start();
require '../config/koneksi.php';

if (isset($_SESSION['status']) && $_SESSION['status'] === 'login') {
    header("Location: " . ($_SESSION['role'] === 'admin' ? "../admin/admin.php" : "../index.php"));
    exit;
}

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$username'");

    if ($data = mysqli_fetch_assoc($query)) {
        if (password_verify($_POST['password'], $data['password'])) {
            $_SESSION['id_user']  = $data['id_user'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['role']     = $data['role'];
            $_SESSION['status']   = "login";

            header("Location: " . ($data['role'] === 'admin' ? "../admin/admin.php" : "../index.php"));
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIPELDA</title>
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
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
            animation: pageFadeIn 0.4s ease-out;
        }

        .login-container {
            background-color: #ffffff;
            padding: 48px 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 40, 85, 0.08);
            width: 100%;
            max-width: 480px;
            box-sizing: border-box;
            border: 1px solid #e2e8f0;
        }

        .login-container h2 {
            margin: 0 0 8px;
            text-align: center;
            color: #002855;
            font-size: 34px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .login-container p {
            text-align: center;
            color: #64748b;
            margin: 0 0 32px;
            font-size: 15px;
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 9px;
            color: #1e293b;
            font-weight: 700;
            font-size: 15px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            box-sizing: border-box;
            outline: none;
            font-size: 15px;
            transition: all 0.2s ease-in-out;
            background-color: #f8fafc;
        }

        .form-group input:focus {
            border-color: #002855;
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(0, 40, 85, 0.1);
        }

        .toggle-eye {
            position: absolute;
            right: 16px;
            top: 44px;
            cursor: pointer;
            color: #64748b;
            font-size: 18px;
            transition: 0.2s;
        }

        .toggle-eye:hover {
            color: #002855;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background-color: #002855;
            border: none;
            color: white;
            font-size: 17px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.1s;
            box-shadow: 0 4px 12px rgba(0, 40, 85, 0.2);
        }

        .btn-login:hover {
            background-color: #001a3b;
        }

        .alert {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 15px;
            text-align: center;
            border: 1px solid #fca5a5;
        }

        .link-bawah {
            text-align: center;
            margin-top: 24px;
            font-size: 15px;
            color: #475569;
        }

        .link-bawah a {
            color: #002855;
            text-decoration: none;
            font-weight: 700;
        }

        .link-bawah a:hover {
            text-decoration: underline;
        }

        .forgot-pass {
            text-align: right;
            margin-bottom: 24px;
        }

        .forgot-pass a {
            font-size: 14px;
            color: #dc3545;
            text-decoration: none;
            font-weight: 600;
        }

        .forgot-pass a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="login-container">
        <h2>SIPELDA</h2>
        <p>Layanan Pengaduan Warga Kelurahan</p>

        <?php if (isset($error)) : ?><div class="alert"><i class="fa-solid fa-circle-exclamation"></i> <?= $error; ?></div><?php endif; ?>

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

            <button type="submit" name="login" class="btn-login"><i class="fa-solid fa-right-to-bracket"></i> Masuk ke Sistem</button>
        </form>

        <div class="link-bawah">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </div>
    </div>

    <script>
        document.querySelector('.toggle-eye').addEventListener('click', function() {
            const field = this.previousElementSibling;
            field.type = field.type === 'password' ? 'text' : 'password';
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>

</html>
