<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// Fungsi Hapus Foto dengan Absolute Path
function hapusFotoLama($koneksi, $id_user) {
    $q_user = mysqli_query($koneksi, "SELECT foto_profil FROM users WHERE id_user = '$id_user'");
    $user_lama = mysqli_fetch_assoc($q_user);
    
    if (!empty($user_lama['foto_profil'])) {
        $target_file = __DIR__ . '/uploads/' . $user_lama['foto_profil'];
        if (file_exists($target_file)) {
            unlink($target_file);
        }
    }
}

// 1. PROSES UPLOAD FOTO
if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0) {
    $nama_file = $_FILES['foto_profil']['name'];
    $ekstensi  = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
    $ukuran    = $_FILES['foto_profil']['size'];
    $file_tmp  = $_FILES['foto_profil']['tmp_name'];

    if (in_array($ekstensi, ['png', 'jpg', 'jpeg']) && $ukuran < 2048000) {
        hapusFotoLama($koneksi, $id_user); 
        
        $nama_foto_baru = 'avatar_' . time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $nama_file);
        $target_dir = __DIR__ . '/uploads/';
        $target_file = $target_dir . $nama_foto_baru;

        // Proses pindah file dengan error handling
        if (move_uploaded_file($file_tmp, $target_file)) {
            mysqli_query($koneksi, "UPDATE users SET foto_profil='$nama_foto_baru' WHERE id_user='$id_user'");
            echo "<script>alert('Foto profil berhasil diperbarui!'); window.location.href='profil.php';</script>";
            exit;
        } else {
            echo "<script>alert('Gagal memindahkan file. Cek permission folder uploads.'); window.history.back();</script>";
            exit;
        }
    } else {
        echo "<script>alert('Gagal! Pastikan format JPG/PNG dan ukuran maksimal 2MB.');</script>";
    }
}

// 2. PROSES HAPUS FOTO
if (isset($_POST['hapus_foto'])) {
    hapusFotoLama($koneksi, $id_user);
    mysqli_query($koneksi, "UPDATE users SET foto_profil = NULL WHERE id_user = '$id_user'");
    echo "<script>alert('Foto profil berhasil dihapus!'); window.location.href='profil.php';</script>";
    exit;
}

// Ambil data user saat ini
$user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_user'"));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Saya - SIPELDA</title>
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
        
        .btn-ganti-foto, .btn-logout-merah { display: block; width: 100%; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 13px; box-sizing: border-box; }
        .btn-ganti-foto { border: 1px solid #002855; color: #002855; background: white; margin-bottom: 12px; transition: 0.2s; }
        .btn-ganti-foto:hover { background: #002855; color: white; }
        .btn-hapus-foto { color: #dc3545; background: transparent; border: none; font-weight: bold; cursor: pointer; font-size: 13px; margin-top: 5px; }
        .btn-logout-merah { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 35px; background: #dc3545; color: white; border: none; font-size: 14px; }

        .main-content { flex: 2.5; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .main-title { margin-top: 0; color: #002855; }
        .sub-title { color: #777; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 15px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .form-group-full { margin-bottom: 20px; }
        label { display: block; font-size: 13px; font-weight: bold; color: #333; margin-bottom: 8px; }
        
        .form-control { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; outline: none; font-size: 14px; }
        .form-control:focus { border-color: #002855; }
        .bg-gray { background: #eef2f6; } 
        
        .password-container { position: relative; display: flex; align-items: center; }
        .password-container .form-control { padding-right: 45px; }
        .toggle-eye { position: absolute; right: 15px; cursor: pointer; color: #64748b; font-size: 16px; }
        .password-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 25px; border-radius: 8px; margin: 30px 0; }
        .password-box h4 { margin: 0 0 20px; color:#333; }

        .btn-save { background: #002855; color: white; border: none; padding: 14px 35px; border-radius: 6px; cursor: pointer; font-weight: bold; float: right; font-size: 14px; }

        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .sidebar { width: 100%; }
            .form-grid { grid-template-columns: 1fr; gap: 15px; }
            .navbar { padding: 20px; flex-wrap: wrap; gap: 15px; justify-content: center; }
        }

        /* --- CSS LOADING OVERLAY --- */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 40, 85, 0.85);
            z-index: 9999;
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            backdrop-filter: blur(5px);
        }
        .loading-spinner { font-size: 60px; margin-bottom: 20px; animation: spin 1s linear infinite; }
        .loading-text { font-size: 20px; font-weight: bold; margin-bottom: 10px; }
        .loading-subtext { font-size: 14px; color: #cbd5e1; }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <div class="loading-overlay" id="loading-overlay">
        <i class="fa-solid fa-circle-notch loading-spinner"></i>
        <div class="loading-text">Memperbarui Foto Profil...</div>
        <div class="loading-subtext">Mohon tunggu sebentar.</div>
    </div>

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

            <form method="POST" id="form-upload-foto" enctype="multipart/form-data">
                <input type="file" name="foto_profil" id="file-avatar-input" accept="image/png, image/jpeg, image/jpg" style="display: none;" onchange="prosesUpload()">
                <button type="button" class="btn-ganti-foto" onclick="document.getElementById('file-avatar-input').click()">
                    <i class="fa-solid fa-camera"></i> Ganti Foto Profil
                </button>
            </form>

            <?php if (!empty($user['foto_profil'])): ?>
                <form method="POST" id="form-hapus-foto" onsubmit="tampilkanLoading('Menghapus Foto Profil...')">
                    <button type="submit" name="hapus_foto" class="btn-hapus-foto" onclick="return confirm('Yakin ingin menghapus foto?')">
                        <i class="fa-solid fa-trash-can"></i> Hapus Foto
                    </button>
                </form>
            <?php endif; ?>

            <a href="logout.php" class="btn-logout-merah" onclick="return confirm('Yakin ingin keluar?')">
                <i class="fa-solid fa-right-from-bracket"></i> Keluar dari Akun
            </a>
        </div>

        <div class="main-content">
            <h2 class="main-title">Informasi Pribadi</h2>
            <p class="sub-title">Informasi akun Anda.</p>
            
            <div class="form-group-full">
                <label>Nama Lengkap</label>
                <input type="text" class="form-control bg-gray" value="<?= htmlspecialchars($user['nama_lengkap'] ?? '') ?>" readonly>
            </div>

            <div class="form-grid">
                <div>
                    <label>Username</label>
                    <input type="text" class="form-control bg-gray" value="<?= htmlspecialchars($user['username'] ?? '') ?>" readonly>
                </div>
                <div>
                    <label>Nomor WhatsApp</label>
                    <input type="text" class="form-control bg-gray" value="<?= htmlspecialchars($user['no_telp'] ?? '') ?>" readonly>
                </div>
            </div>

            <div style="border-top: 1px solid #eee; padding-top: 20px; overflow: auto;">
                <button type="button" class="btn-save" onclick="window.location.href='ganti_profil.php'">
                    <i class="fa-solid fa-pen-to-square"></i> Ubah Profil & Sandi
                </button>
            </div>
        </div>
    </div>

    <script>
        // Memunculkan Loading Overlay saat input file dipilih dan langsung disubmit
        function prosesUpload() {
            const fileInput = document.getElementById('file-avatar-input');
            if (fileInput.files.length > 0) {
                tampilkanLoading('Memperbarui Foto Profil...');
                document.getElementById('form-upload-foto').submit();
            }
        }

        // Fungsi untuk mengaktifkan Loading Overlay dengan teks dinamis
        function tampilkanLoading(pesan) {
            document.getElementById('loading-overlay').style.display = 'flex';
            if (pesan) {
                document.querySelector('.loading-text').innerText = pesan;
            }
        }
    </script>
</body>
</html>
