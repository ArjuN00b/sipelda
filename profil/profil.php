<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    header("Location: ../auth/login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$profil_sukses = false;
$pesan_sukses = "";

// Fungsi Hapus Foto dengan Absolute Path
function hapusFotoLama($koneksi, $id_user) {
    $q_user = mysqli_query($koneksi, "SELECT foto_profil FROM users WHERE id_user = '$id_user'");
    $user_lama = mysqli_fetch_assoc($q_user);
    
    if (!empty($user_lama['foto_profil'])) {
        $target_file = dirname(__DIR__) . '/uploads/' . $user_lama['foto_profil'];
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

    if (in_array($ekstensi, ['png', 'jpg', 'jpeg', 'webp']) && $ukuran <= 10485760) {
        hapusFotoLama($koneksi, $id_user); 
        
        $nama_foto_baru = 'avatar_' . time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $nama_file);
        $target_file = dirname(__DIR__) . '/uploads/' . $nama_foto_baru;

        if (move_uploaded_file($file_tmp, $target_file)) {
            mysqli_query($koneksi, "UPDATE users SET foto_profil='$nama_foto_baru' WHERE id_user='$id_user'");
            $profil_sukses = true;
            $pesan_sukses = "Foto profil Anda berhasil diperbarui!";
        } else {
            echo "<script>alert('Gagal memindahkan file avatar.'); window.history.back();</script>";
            exit;
        }
    } else {
        echo "<script>alert('Hanya diperbolehkan format Foto (PNG, JPG, JPEG, WEBP) maksimal 10MB!'); window.history.back();</script>";
        exit;
    }
}

// 2. PROSES HAPUS FOTO
if (isset($_POST['hapus_foto'])) {
    hapusFotoLama($koneksi, $id_user);
    mysqli_query($koneksi, "UPDATE users SET foto_profil = NULL WHERE id_user = '$id_user'");
    $profil_sukses = true;
    $pesan_sukses = "Foto profil Anda berhasil dihapus!";
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
        @keyframes pageFadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        body { 
            font-family: 'Segoe UI', Tahoma, sans-serif; 
            margin: 0; 
            background-color: #f4f7fb; 
            color: #1e293b; 
            padding-bottom: 90px;
            animation: pageFadeIn 0.4s ease-out;
        }

        a { text-decoration: none; }

        .brand-header-centered {
            text-align: center;
            padding-top: 30px;
            padding-bottom: 10px;
        }

        .brand-header-centered a {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 32px;
            font-weight: 800;
            color: #002855;
            text-decoration: none;
            letter-spacing: -0.5px;
        }
        
        .brand-divider {
            max-width: 1000px;
            margin: 15px auto 25px;
            border-bottom: 2px solid #cbd5e1;
            opacity: 0.7;
        }

        .container { max-width: 1000px; margin: 0 auto 40px; display: flex; gap: 30px; padding: 0 20px; align-items: flex-start; box-sizing: border-box; }
        
        .sidebar { flex: 1; background: #ebf2fa; padding: 40px 20px; border-radius: 20px; text-align: center; border: 1px solid #d0e1f9; box-sizing: border-box; }
        .avatar { width: 130px; height: 130px; border-radius: 50%; background: #002855; margin: 0 auto 20px; overflow: hidden; display: flex; align-items: center; justify-content: center; font-size: 40px; color: white; font-weight: bold; border: 3px solid white; box-shadow: 0 4px 6px rgba(0,0,0,0.1); cursor: pointer; transition: 0.2s; }
        .avatar:hover { transform: scale(1.05); }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .sidebar h3 { margin: 0 0 25px; color: #002855; font-size: 22px; font-weight: 800; }
        
        .btn-ganti-foto, .btn-logout-merah { display: block; width: 100%; padding: 14px; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 14px; box-sizing: border-box; transition: 0.2s; }
        .btn-ganti-foto { border: 1.5px solid #002855; color: #002855; background: white; margin-bottom: 12px; }
        .btn-ganti-foto:hover { background: #002855; color: white; }
        .btn-hapus-foto { color: #dc3545; background: transparent; border: none; font-weight: bold; cursor: pointer; font-size: 14px; margin-top: 10px; }
        .btn-logout-merah { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 35px; background: #dc3545; color: white; border: none; font-size: 15px; box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2); }
        .btn-logout-merah:hover { background-color: #c82333; }

        .main-content { flex: 2.5; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; box-sizing: border-box; }
        .main-title { margin-top: 0; color: #002855; font-size: 26px; font-weight: 800; }
        .sub-title { color: #64748b; margin-bottom: 30px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; font-size: 15px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .form-group-full { margin-bottom: 20px; }
        label { display: block; font-size: 14px; font-weight: bold; color: #0f172a; margin-bottom: 8px; }
        
        .form-control { width: 100%; padding: 14px 16px; border: 1.5px solid #cbd5e1; border-radius: 10px; box-sizing: border-box; outline: none; font-size: 15px; }
        .bg-gray { background: #e2e8f0; color: #475569; } 
        
        .btn-save { display: inline-flex; align-items: center; gap: 8px; background: #002855; color: white; border: none; padding: 14px 30px; border-radius: 10px; cursor: pointer; font-weight: bold; font-size: 15px; float: right; transition: 0.2s; }
        .btn-save:hover { background-color: #001a3b; }

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

        .modal-icon-logout {
            width: 80px; height: 80px;
            background-color: #fee2e2;
            color: #dc2626;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            margin: 0 auto 20px;
        }

        .modal-box h3 { margin: 0 0 10px; color: #002855; font-size: 24px; font-weight: 800; }
        .modal-box p { color: #64748b; font-size: 15px; margin: 0 0 30px; line-height: 1.6; }

        .modal-button-group { display: flex; gap: 12px; }
        .btn-modal-cancel { flex: 1; padding: 14px; background-color: #f1f5f9; color: #475569; border-radius: 10px; font-size: 15px; font-weight: bold; border: 1px solid #cbd5e1; cursor: pointer; transition: 0.2s; }
        .btn-modal-cancel:hover { background-color: #e2e8f0; }

        .btn-modal-logout-ya { flex: 1; padding: 14px; background-color: #dc2626; color: white; border-radius: 10px; font-size: 15px; font-weight: bold; text-decoration: none; display: inline-block; box-sizing: border-box; transition: 0.2s; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3); }
        .btn-modal-logout-ya:hover { background-color: #b91c1c; }

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
        .btn-modal-close { display: block; width: 100%; padding: 16px; background-color: #002855; color: white; border-radius: 10px; font-size: 16px; font-weight: bold; text-decoration: none; box-sizing: border-box; text-align: center; }

        /* LIGHTBOX ZOOM FOTO */
        .lightbox-overlay {
            display: none;
            position: fixed;
            z-index: 99999;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            justify-content: center;
            align-items: center;
        }
        .lightbox-content { max-width: 90%; max-height: 85vh; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); object-fit: contain; animation: zoomIn 0.25s ease-out; }
        .lightbox-close { position: absolute; top: 25px; right: 35px; color: #ffffff; font-size: 44px; font-weight: bold; cursor: pointer; z-index: 100000; transition: 0.2s; line-height: 1; }
        .lightbox-close:hover { color: #f87171; transform: scale(1.15); }

        .loading-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
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
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes zoomIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }

        .footbar-nav {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            height: 70px;
            background-color: #002855;
            display: flex;
            justify-content: space-around;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footbar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            gap: 4px;
            width: 33.33%;
            height: 100%;
            transition: 0.2s;
        }

        .footbar-item i { font-size: 22px; }
        .footbar-item:hover, .footbar-item.active { color: #ffffff; background: rgba(255, 255, 255, 0.08); }
        .footbar-item.active i { color: #38bdf8; }

        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .sidebar { width: 100%; }
            .form-grid { grid-template-columns: 1fr; gap: 15px; }
        }
    </style>
</head>
<body>

    <!-- MODAL SUKSES IN-APP -->
    <?php if ($profil_sukses): ?>
        <div class="modal-overlay">
            <div class="modal-box">
                <div class="modal-icon-success"><i class="fa-solid fa-circle-check"></i></div>
                <h3>Berhasil!</h3>
                <p><?= $pesan_sukses ?></p>
                <a href="profil.php" class="btn-modal-close">OK</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL LIGHTBOX ZOOM FOTO -->
    <div id="image-lightbox-modal" class="lightbox-overlay" onclick="closeLightbox(event)">
        <span class="lightbox-close" onclick="closeLightboxDirect()">&times;</span>
        <img id="lightbox-img" class="lightbox-content" src="" alt="Zoom Foto">
    </div>

    <!-- MODAL POPUP KONFIRMASI KELUAR AKUN (LOGOUT) -->
    <div id="modal-confirm-logout" class="modal-overlay" style="display: none;">
        <div class="modal-box">
            <div class="modal-icon-logout"><i class="fa-solid fa-right-from-bracket"></i></div>
            <h3>Keluar dari Akun?</h3>
            <p>Anda harus masuk kembali menggunakan username dan kata sandi untuk mengakses akun SIPELDA ini.</p>
            <div class="modal-button-group">
                <button type="button" class="btn-modal-cancel" onclick="closeConfirmLogout()">Batal</button>
                <a href="../auth/logout.php" class="btn-modal-logout-ya">Ya, Keluar Akun</a>
            </div>
        </div>
    </div>

    <!-- MODAL KONFIRMASI HAPUS FOTO -->
    <div id="modal-confirm-hapus-foto" class="modal-overlay" style="display: none;">
        <div class="modal-box">
            <div class="modal-icon-logout"><i class="fa-solid fa-trash-can"></i></div>
            <h3>Hapus Foto Profil?</h3>
            <p>Foto profil Anda saat ini akan dihapus permanen dari sistem.</p>
            <div class="modal-button-group">
                <button type="button" class="btn-modal-cancel" onclick="closeConfirmHapusFoto()">Batal</button>
                <form method="POST" action="" style="flex:1;">
                    <button type="submit" name="hapus_foto" class="btn-modal-logout-ya" style="width:100%; border:none; cursor:pointer;">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>

    <div class="loading-overlay" id="loading-overlay">
        <i class="fa-solid fa-circle-notch loading-spinner"></i>
        <div class="loading-text">Memperbarui Foto Profil...</div>
        <div class="loading-subtext">Mohon tunggu sebentar.</div>
    </div>

    <!-- HEADER SIPELDA TENGAH -->
    <div class="brand-header-centered">
        <a href="../index.php">
            <i class="fa-solid fa-shield-halved" style="color: #002855;"></i> SIPELDA
        </a>
    </div>
    <div class="brand-divider"></div>

    <div class="container">
        <div class="sidebar">
            <div class="avatar" onclick="zoomFoto(this)">
                <?php if (!empty($user['foto_profil']) && file_exists('../uploads/' . $user['foto_profil'])): ?>
                    <img src="../uploads/<?= $user['foto_profil'] ?>" alt="Foto Profil" id="avatar-img-view">
                <?php else: ?>
                    <?= strtoupper(substr($user['username'], 0, 2)) ?>
                <?php endif; ?>
            </div>
            <h3><?= htmlspecialchars($user['username'] ?? '') ?></h3>

            <form method="POST" id="form-upload-foto" enctype="multipart/form-data">
                <input type="file" name="foto_profil" id="file-avatar-input" accept="image/png, image/jpeg, image/jpg, image/webp" style="display: none;" onchange="prosesUpload()">
                <button type="button" class="btn-ganti-foto" onclick="document.getElementById('file-avatar-input').click()">
                    <i class="fa-solid fa-camera"></i> Ganti Foto Profil
                </button>
            </form>

            <?php if (!empty($user['foto_profil'])): ?>
                <button type="button" class="btn-hapus-foto" onclick="openConfirmHapusFoto()">
                    <i class="fa-solid fa-trash-can"></i> Hapus Foto
                </button>
            <?php endif; ?>

            <button type="button" class="btn-logout-merah" onclick="openConfirmLogout()">
                <i class="fa-solid fa-right-from-bracket"></i> Keluar dari Akun
            </button>
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

    <!-- FOOTBAR NAVIGATION BAR -->
    <nav class="footbar-nav">
        <a href="../index.php" class="footbar-item">
            <i class="fa-solid fa-house"></i>
            <span>Beranda</span>
        </a>
        <a href="../pengaduan/historipengaduan.php" class="footbar-item">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Riwayat</span>
        </a>
        <a href="profil.php" class="footbar-item active">
            <i class="fa-solid fa-user"></i>
            <span>Profil</span>
        </a>
    </nav>

    <script>
        function prosesUpload() {
            const fileInput = document.getElementById('file-avatar-input');
            if (fileInput.files.length > 0) {
                tampilkanLoading('Memperbarui Foto Profil...');
                document.getElementById('form-upload-foto').submit();
            }
        }

        function tampilkanLoading(pesan) {
            document.getElementById('loading-overlay').style.display = 'flex';
            if (pesan) {
                document.querySelector('.loading-text').innerText = pesan;
            }
        }

        function openConfirmLogout() {
            document.getElementById('modal-confirm-logout').style.display = 'flex';
        }
        function closeConfirmLogout() {
            document.getElementById('modal-confirm-logout').style.display = 'none';
        }

        function openConfirmHapusFoto() {
            document.getElementById('modal-confirm-hapus-foto').style.display = 'flex';
        }
        function closeConfirmHapusFoto() {
            document.getElementById('modal-confirm-hapus-foto').style.display = 'none';
        }

        function zoomFoto(element) {
            const img = element.querySelector('img');
            if (img) {
                openLightbox(img.src);
            }
        }
        function openLightbox(src) {
            document.getElementById('lightbox-img').src = src;
            document.getElementById('image-lightbox-modal').style.display = 'flex';
        }
        function closeLightboxDirect() {
            document.getElementById('image-lightbox-modal').style.display = 'none';
        }
        function closeLightbox(e) {
            if (e.target.id === 'image-lightbox-modal') {
                closeLightboxDirect();
            }
        }
    </script>
</body>
</html>
