<?php
session_start();
require 'koneksi.php';

// Pastikan user sudah login
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    header("Location: login.php");
    exit;
}
$id_user = $_SESSION['id_user'];

// PROSES UPDATE DATA PROFIL & KATA SANDI
if (isset($_POST['update_profil'])) {
    $nama_lengkap  = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $username_baru = mysqli_real_escape_string($koneksi, $_POST['username']);
    $no_telp       = mysqli_real_escape_string($koneksi, $_POST['no_telp']);

    // Cek username bentrok
    if (mysqli_num_rows(mysqli_query($koneksi, "SELECT id_user FROM users WHERE username='$username_baru' AND id_user != '$id_user'")) > 0) {
        echo "<script> alert('Username sudah digunakan! Pilih yang lain.');</script>";
    } else {
        // Jika password diubah
        if (!empty($_POST['password_baru'])) {
            $pass_lama = $_POST['password_lama'];
            $pass_baru = password_hash($_POST['password_baru'], PASSWORD_DEFAULT);
            $data_pass = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT password FROM users WHERE id_user='$id_user'"));

            if (password_verify($pass_lama, $data_pass['password'])) {
                mysqli_query($koneksi, "UPDATE users SET nama_lengkap='$nama_lengkap', username='$username_baru', no_telp='$no_telp', password='$pass_baru' WHERE id_user='$id_user'");
                $_SESSION['username'] = $username_baru;
                echo "<script>alert('Profil & Sandi berhasil diperbarui!'); window.location.href='profil.php';</script>";
                exit;
            } else {
                echo "<script>alert('Gagal! Sandi lama salah.'); window.history.back();</script>";
                exit;
            }
        } else {
            // Update tanpa password
            mysqli_query($koneksi, "UPDATE users SET nama_lengkap='$nama_lengkap', username='$username_baru', no_telp='$no_telp' WHERE id_user='$id_user'");
            $_SESSION['username'] = $username_baru;
            echo "<script>alert('Data profil berhasil disimpan!'); window.location.href='profil.php';</script>";
            exit;
        }
    }
}

// Ambil data user saat ini
$user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_user'"));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ubah Profil & Sandi - SIPELDA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        
        body { font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; background-color: #f4f7fb; }
        a { text-decoration: none; }

        .navbar { background-color: #002855; color: white; padding: 25px 60px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .navbar .logo { font-size: 26px; font-weight: bold; color: white; }
        .nav-center { display: flex; gap: 40px; }
        .nav-center a { color: #a9b9cc; font-size: 16px; font-weight: 500; transition: 0.3s; }
        .nav-center a:hover { color: white; }

        .user-profile-btn { display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.1); padding: 8px 20px; border-radius: 30px; color: white; border: 1px solid rgba(255,255,255,0.2); }
        .nav-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.8); }

        .container { max-width: 1000px; margin: 40px auto; display: flex; gap: 30px; padding: 0 20px; align-items: flex-start; }

        .sidebar { flex: 1; background: #ebf2fa; padding: 40px 20px; border-radius: 12px; text-align: center; border: 1px solid #d0e1f9; }
        .avatar { width: 120px; height: 120px; border-radius: 50%; background: #002855; margin: 0 auto 20px; overflow: hidden; display: flex; align-items: center; justify-content: center; font-size: 40px; color: white; font-weight: bold; border: 3px solid white; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .sidebar h3 { margin: 0 0 25px; color: #002855; font-size: 22px; }

        .btn-kembali { display: block; width: 100%; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 13px; box-sizing: border-box; border: 1px solid #002855; color: #002855; background: white; margin-bottom: 12px; transition: 0.2s; text-align: center; }
        .btn-kembali:hover { background: #002855; color: white; }
        .btn-logout-merah { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 35px; background: #dc3545; color: white; border: none; font-size: 14px; width: 100%; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; box-sizing: border-box; text-decoration: none; }

        .main-content { flex: 2.5; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .main-title { margin-top: 0; color: #002855; }
        .sub-title { color: #777; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 15px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .form-group-full { margin-bottom: 20px; }
        label { display: block; font-size: 13px; font-weight: bold; color: #333; margin-bottom: 8px; }

        .form-control { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; outline: none; font-size: 14px; }
        .form-control:focus { border-color: #002855; }

        .password-container { position: relative; display: flex; align-items: center; }
        .password-container .form-control { padding-right: 45px; }
        .toggle-eye { position: absolute; right: 15px; cursor: pointer; color: #64748b; font-size: 16px; }
        .password-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 25px; border-radius: 8px; margin: 30px 0; }
        .password-box h4 { margin: 0 0 20px; color: #333; }
        .password-box .form-grid { margin-bottom: 0; }

        .btn-save { background: #002855; color: white; border: none; padding: 14px 35px; border-radius: 6px; cursor: pointer; font-weight: bold; float: right; font-size: 14px; }
        .btn-save:hover { background: #003d7a; }

        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .sidebar { width: 100%; }
            .form-grid { grid-template-columns: 1fr; gap: 15px; }
            .navbar { padding: 20px; flex-wrap: wrap; gap: 15px; justify-content: center; }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <a href="index.php" class="logo">SIPELDA</a>
        <div class="nav-center">
            <a href="index.php">Beranda</a>
            <a href="historipengaduan.php">Riwayat</a>
        </div>
        <div class="user-profile-btn">
            <span><?= htmlspecialchars($user['username'] ?? ''); ?></span>
            <?php if (!empty($user['foto_profil']) && file_exists('uploads/' . $user['foto_profil'])): ?>
                <img src="uploads/<?= $user['foto_profil'] ?>" class="nav-avatar">
            <?php else: ?>
                <i class="fa-solid fa-circle-user" style="font-size: 24px; color: #cbd5e1;"></i>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <div class="sidebar">
            <div class="avatar">
                <?php if (!empty($user['foto_profil']) && file_exists('uploads/' . $user['foto_profil'])): ?>
                    <img src="uploads/<?= $user['foto_profil'] ?>" alt="Foto Profil">
                <?php else: ?>
                    <?= strtoupper(substr($user['username'], 0, 2)) ?>
                <?php endif; ?>
            </div>
            <h3><?= htmlspecialchars($user['username'] ?? '') ?></h3>

            <a href="profil.php" class="btn-kembali">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Profil
            </a>

            <a href="logout.php" class="btn-logout-merah" onclick="return confirm('Yakin ingin keluar?')">
                <i class="fa-solid fa-right-from-bracket"></i> Keluar dari Akun
            </a>
        </div>

        <div class="main-content">
            <h2 class="main-title">Ubah Profil & Sandi</h2>
            <p class="sub-title">Perbarui informasi dasar, nama pengguna, dan sandi Anda di sini.</p>

            <form method="POST" id="form-profil">
                <div class="form-group-full">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($user['nama_lengkap'] ?? '') ?>" required>
                </div>

                <div class="form-grid">
                    <div>
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                    </div>
                    <div>
                        <label>Nomor WhatsApp</label>
                        <input type="text" name="no_telp" class="form-control" value="<?= htmlspecialchars($user['no_telp'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="password-box">
                    <h4><i class="fa-solid fa-lock"></i> Ubah Kata Sandi <span style="font-size:12px; font-weight:normal; color:#888;">(Kosongkan jika tidak ingin mengubah)</span></h4>
                    <div class="form-grid">
                        <div>
                            <label>Kata Sandi Lama</label>
                            <div class="password-container">
                                <input type="password" id="pass-lama" name="password_lama" class="form-control" placeholder="Masukkan sandi lama">
                                <i class="fa-solid fa-eye toggle-eye" onclick="togglePass('pass-lama', this)"></i>
                            </div>
                        </div>
                        <div>
                            <label>Kata Sandi Baru</label>
                            <div class="password-container">
                                <input type="password" id="pass-baru" name="password_baru" class="form-control" placeholder="Masukkan sandi baru">
                                <i class="fa-solid fa-eye toggle-eye" onclick="togglePass('pass-baru', this)"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="border-top: 1px solid #eee; padding-top: 20px; overflow: auto;">
                    <button type="submit" name="update_profil" class="btn-save">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const togglePass = (id, icon) => {
            const el = document.getElementById(id);
            el.type = el.type === 'password' ? 'text' : 'password';
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        };

        document.getElementById('form-profil').addEventListener('submit', e => {
            if (document.getElementById('pass-baru').value && !document.getElementById('pass-lama').value) {
                e.preventDefault();
                alert('Silakan masukkan Kata Sandi Lama Anda untuk mengonfirmasi!');
                document.getElementById('pass-lama').focus();
            }
        });
    </script>
</body>
</html>
