<?php
session_start();
require '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    header("Location: ../auth/login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_user'"));
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - SIPELDA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
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
            margin: 0;
            background-color: #f4f7fb;
            color: #1e293b;
            padding-bottom: 90px;
            animation: pageFadeIn 0.4s ease-out;
        }

        a {
            text-decoration: none;
        }

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
            transition: transform 0.2s;
        }

        .brand-header-centered a:hover {
            transform: scale(1.03);
        }

        .brand-divider {
            max-width: 1000px;
            margin: 15px auto 25px;
            border-bottom: 2px solid #cbd5e1;
            opacity: 0.7;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto 40px;
            display: flex;
            gap: 35px;
            padding: 0 20px;
            align-items: stretch;
        }

        .sidebar {
            flex: 1;
            background: #ffffff;
            padding: 40px 30px;
            border-radius: 20px;
            text-align: center;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .avatar-wrapper {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: #002855;
            margin: 0 auto 20px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            font-weight: 800;
            border: 4px solid #ffffff;
            box-shadow: 0 8px 24px rgba(0, 40, 85, 0.15);
            position: relative;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .avatar-wrapper:hover {
            transform: scale(1.04);
        }

        .avatar-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .sidebar h3 {
            margin: 0 0 10px;
            color: #002855;
            font-size: 24px;
            font-weight: 800;
            text-align: center;
        }

        .role-tag-centered {
            margin: 0 auto 35px;
            color: #002855;
            font-size: 14px;
            font-weight: 700;
            background: #e0e7ff;
            padding: 6px 20px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-logout-merah {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 16px;
            border-radius: 12px;
            font-weight: bold;
            cursor: pointer;
            font-size: 15px;
            box-sizing: border-box;
            background: #fee2e2;
            color: #dc2626;
            border: 1.5px solid #fca5a5;
            margin-top: auto;
            transition: 0.3s;
        }

        .btn-logout-merah:hover {
            background: #dc2626;
            color: white;
        }

        .main-content {
            flex: 2.2;
            background: white;
            padding: 45px 40px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
        }

        .main-title {
            margin-top: 0;
            color: #002855;
            font-size: 26px;
            font-weight: 800;
        }

        .sub-title {
            color: #64748b;
            margin-bottom: 35px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 16px;
            font-size: 15px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 28px;
        }

        .form-group-full {
            margin-bottom: 24px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            box-sizing: border-box;
            outline: none;
            font-size: 15px;
            background-color: #f8fafc;
            color: #334155;
            font-weight: 600;
        }

        .btn-edit-profil {
            background: #002855;
            color: white;
            border: none;
            padding: 16px 36px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
            box-shadow: 0 4px 12px rgba(0, 40, 85, 0.2);
        }

        .btn-edit-profil:hover {
            background: #001a3b;
        }

        /* MODAL POPUP KONFIRMASI LOGOUT */
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
            animation: fadeIn 0.3s ease-in-out;
        }

        .modal-box {
            background: #ffffff;
            padding: 40px;
            border-radius: 20px;
            max-width: 440px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
            animation: slideUp 0.3s ease-in-out;
        }

        .modal-icon-logout {
            width: 80px;
            height: 80px;
            background-color: #fee2e2;
            color: #dc2626;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
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

        .modal-button-group {
            display: flex;
            gap: 12px;
        }

        .btn-modal-cancel {
            flex: 1;
            padding: 14px;
            background-color: #f1f5f9;
            color: #475569;
            border-radius: 10px;
            font-size: 15px;
            font-weight: bold;
            border: 1px solid #cbd5e1;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-modal-cancel:hover { background-color: #e2e8f0; }

        .btn-modal-logout-ya {
            flex: 1;
            padding: 14px;
            background-color: #dc2626;
            color: white;
            border-radius: 10px;
            font-size: 15px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            box-sizing: border-box;
            transition: 0.2s;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }

        .btn-modal-logout-ya:hover { background-color: #b91c1c; }

        /* MODAL LIGHTBOX ZOOM FOTO */
        .lightbox-overlay {
            display: none;
            position: fixed;
            z-index: 99999;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            justify-content: center;
            align-items: center;
        }

        .lightbox-content {
            max-width: 90%;
            max-height: 85vh;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            object-fit: contain;
            animation: zoomIn 0.25s ease-out;
        }

        .lightbox-close {
            position: absolute;
            top: 25px;
            right: 35px;
            color: #ffffff;
            font-size: 44px;
            font-weight: bold;
            cursor: pointer;
            z-index: 100000;
            transition: 0.2s;
            line-height: 1;
        }

        .lightbox-close:hover {
            color: #f87171;
            transform: scale(1.15);
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes zoomIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }

        .footbar-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
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
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

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

    <!-- MODAL LIGHTBOX ZOOM FOTO -->
    <div id="image-lightbox-modal" class="lightbox-overlay" onclick="closeLightbox(event)">
        <span class="lightbox-close" onclick="closeLightboxDirect()">&times;</span>
        <img id="lightbox-img" class="lightbox-content" src="" alt="Zoom Foto">
    </div>

    <div class="brand-header-centered">
        <a href="../index.php">
            <i class="fa-solid fa-shield-halved" style="color: #002855;"></i> SIPELDA
        </a>
    </div>
    <div class="brand-divider"></div>

    <div class="container">
        <div class="sidebar">
            <?php 
                $foto_src = (!empty($user['foto_profil']) && file_exists('../uploads/' . $user['foto_profil'])) ? '../uploads/' . $user['foto_profil'] : '';
            ?>
            <div class="avatar-wrapper" title="Klik untuk memperbesar foto" <?php if ($foto_src): ?>onclick="openLightbox('<?= $foto_src ?>')"<?php endif; ?>>
                <?php if ($foto_src): ?>
                    <img src="<?= $foto_src ?>" alt="Foto Profil">
                <?php else: ?>
                    <?= strtoupper(substr($user['username'], 0, 2)) ?>
                <?php endif; ?>
            </div>
            <h3><?= htmlspecialchars($user['nama_lengkap'] ?? $user['username']) ?></h3>
            
            <div class="role-tag-centered">
                <i class="fa-solid fa-user-check"></i> Warga Kelurahan
            </div>

            <button type="button" class="btn-logout-merah" onclick="openConfirmLogout()">
                <i class="fa-solid fa-right-from-bracket"></i> Keluar dari Akun
            </button>
        </div>

        <div class="main-content">
            <h2 class="main-title"><i class="fa-solid fa-id-card" style="color: #002855;"></i> Informasi Pribadi Saya</h2>
            <p class="sub-title">Detail data akun Anda yang terdaftar pada sistem SIPELDA.</p>

            <div class="form-group-full">
                <label>Nama Lengkap</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['nama_lengkap'] ?? '') ?>" readonly>
            </div>

            <div class="form-grid">
                <div>
                    <label>Username</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" readonly>
                </div>
                <div>
                    <label>Nomor WhatsApp / Telepon</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['no_telp'] ?? '') ?>" readonly>
                </div>
            </div>

            <div style="border-top: 2px solid #f1f5f9; padding-top: 28px; text-align: right;">
                <a href="ganti_profil.php" class="btn-edit-profil">
                    <i class="fa-solid fa-pen-to-square"></i> Ubah Profil, Foto & Sandi
                </a>
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
        function openConfirmLogout() {
            document.getElementById('modal-confirm-logout').style.display = 'flex';
        }
        function closeConfirmLogout() {
            document.getElementById('modal-confirm-logout').style.display = 'none';
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
