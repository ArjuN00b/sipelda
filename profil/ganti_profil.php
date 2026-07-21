<?php
session_start();
require '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    header("Location: ../auth/login.php");
    exit;
}
$id_user = $_SESSION['id_user'];
$modal_pesan = "";

function hapusFotoLama($koneksi, $id_user)
{
    $q_user = mysqli_query($koneksi, "SELECT foto_profil FROM users WHERE id_user = '$id_user'");
    $user_lama = mysqli_fetch_assoc($q_user);

    if (!empty($user_lama['foto_profil'])) {
        $target_file = __DIR__ . '/../uploads/' . $user_lama['foto_profil'];
        if (file_exists($target_file)) {
            unlink($target_file);
        }
    }
}

// Upload foto profil secara langsung tanpa modal popup (hanya format gambar valid)
if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0) {
    $nama_file = $_FILES['foto_profil']['name'];
    $ekstensi  = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
    $ukuran    = $_FILES['foto_profil']['size'];
    $file_tmp  = $_FILES['foto_profil']['tmp_name'];
    $mime      = mime_content_type($file_tmp);

    $allowed_ext = ['png', 'jpg', 'jpeg', 'webp'];
    $allowed_mime = ['image/png', 'image/jpeg', 'image/pjpeg', 'image/webp'];

    if (in_array($ekstensi, $allowed_ext) && in_array($mime, $allowed_mime) && $ukuran <= 5242880) { // maks 5MB
        hapusFotoLama($koneksi, $id_user);

        $nama_foto_baru = 'avatar_' . time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $nama_file);
        $target_dir = __DIR__ . '/../uploads/';
        $target_file = $target_dir . $nama_foto_baru;

        if (move_uploaded_file($file_tmp, $target_file)) {
            mysqli_query($koneksi, "UPDATE users SET foto_profil='$nama_foto_baru' WHERE id_user='$id_user'");
            header("Location: ganti_profil.php");
            exit;
        } else {
            $error_pesan = "Gagal memindahkan file foto profil!";
        }
    } else {
        $error_pesan = "Hanya file foto berformat PNG, JPG, JPEG, atau WEBP yang diperbolehkan! Dokumen seperti PDF/Word tidak dapat diunggah.";
    }
}

// Hapus foto profil
if (isset($_POST['hapus_foto'])) {
    hapusFotoLama($koneksi, $id_user);
    mysqli_query($koneksi, "UPDATE users SET foto_profil = NULL WHERE id_user = '$id_user'");
    header("Location: ganti_profil.php");
    exit;
}

// MODAL POPUP HANYA MUNCUL SAAT SIMPAN PERUBAHAN SECARA KESELURUHAN
if (isset($_POST['update_profil'])) {
    $nama_lengkap  = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $username_baru = mysqli_real_escape_string($koneksi, $_POST['username']);
    $no_telp       = mysqli_real_escape_string($koneksi, $_POST['no_telp']);

    if (mysqli_num_rows(mysqli_query($koneksi, "SELECT id_user FROM users WHERE username='$username_baru' AND id_user != '$id_user'")) > 0) {
        $error_pesan = "Username sudah digunakan oleh pengguna lain!";
    } else {
        if (!empty($_POST['password_baru'])) {
            $pass_lama = $_POST['password_lama'];
            $pass_baru = password_hash($_POST['password_baru'], PASSWORD_DEFAULT);
            $data_pass = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT password FROM users WHERE id_user='$id_user'"));

            if (password_verify($pass_lama, $data_pass['password'])) {
                mysqli_query($koneksi, "UPDATE users SET nama_lengkap='$nama_lengkap', username='$username_baru', no_telp='$no_telp', password='$pass_baru' WHERE id_user='$id_user'");
                $_SESSION['username'] = $username_baru;
                $modal_pesan = "Informasi profil & kata sandi berhasil diperbarui secara keseluruhan!";
            } else {
                $error_pesan = "Kata sandi lama Anda salah!";
            }
        } else {
            mysqli_query($koneksi, "UPDATE users SET nama_lengkap='$nama_lengkap', username='$username_baru', no_telp='$no_telp' WHERE id_user='$id_user'");
            $_SESSION['username'] = $username_baru;
            $modal_pesan = "Data profil berhasil diperbarui secara keseluruhan!";
        }
    }
}

$user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_user'"));
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Profil & Sandi - SIPELDA</title>
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
            max-width: 850px;
            margin: 15px auto 25px;
            border-bottom: 2px solid #cbd5e1;
            opacity: 0.7;
        }

        .container {
            max-width: 850px;
            margin: 0 auto 40px;
            padding: 0 20px;
        }

        .main-card {
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
            text-align: center;
        }

        .sub-title {
            color: #64748b;
            margin-bottom: 30px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 16px;
            font-size: 15px;
            text-align: center;
        }

        .avatar-section-top {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 35px;
            background: #f8fafc;
            padding: 30px 20px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
        }

        .avatar-wrapper {
            width: 130px;
            height: 130px;
            min-width: 130px;
            min-height: 130px;
            border-radius: 50%;
            background: #002855;
            margin: 0 auto 16px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 44px;
            color: white;
            font-weight: 800;
            border: 4px solid #ffffff;
            box-shadow: 0 8px 24px rgba(0, 40, 85, 0.12);
            box-sizing: border-box;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .avatar-wrapper:hover {
            transform: scale(1.04);
            box-shadow: 0 10px 28px rgba(0, 40, 85, 0.2);
        }

        .avatar-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .avatar-section-top h3 {
            margin: 0 0 16px;
            color: #002855;
            font-size: 22px;
            font-weight: 800;
        }

        .action-photo-buttons {
            display: flex;
            align-items: center;
            gap: 16px;
            justify-content: center;
        }

        .btn-ganti-foto {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
            border: 1.5px solid #002855;
            color: #002855;
            background: white;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-ganti-foto:hover {
            background: #002855;
            color: white;
        }

        .btn-hapus-foto {
            color: #dc2626;
            background: transparent;
            border: none;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
        }

        .btn-hapus-foto:hover {
            color: #991b1b;
            text-decoration: underline;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 15px;
            border: 1px solid #fca5a5;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
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
            background-color: #ffffff;
            transition: 0.2s;
        }

        .form-control:focus {
            border-color: #002855;
            box-shadow: 0 0 0 4px rgba(0, 40, 85, 0.1);
        }

        .password-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 28px;
            border-radius: 14px;
            margin: 35px 0;
        }

        .password-box h4 {
            margin: 0 0 20px;
            color: #002855;
            font-size: 17px;
            font-weight: 800;
        }

        .password-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-container .form-control {
            padding-right: 45px;
        }

        .toggle-eye {
            position: absolute;
            right: 16px;
            cursor: pointer;
            color: #64748b;
            font-size: 18px;
        }

        .bottom-button-group {
            display: flex;
            gap: 16px;
            justify-content: flex-end;
            align-items: center;
            border-top: 2px solid #f1f5f9;
            padding-top: 28px;
        }

        .btn-kembali-bottom {
            padding: 16px 28px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            color: #475569;
            background: #f1f5f9;
            border: 1.5px solid #cbd5e1;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }

        .btn-kembali-bottom:hover {
            background: #e2e8f0;
            color: #002855;
        }

        .btn-save {
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

        .btn-save:hover {
            background: #001a3b;
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

        .btn-modal-close:hover {
            background-color: #001a3b;
        }

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
            .form-grid { grid-template-columns: 1fr; }
            .bottom-button-group { flex-direction: column-reverse; }
            .btn-kembali-bottom, .btn-save { width: 100%; justify-content: center; }
            .action-photo-buttons { flex-direction: column; width: 100%; }
            .btn-ganti-foto { width: 100%; justify-content: center; }
        }
    </style>
</head>

<body>
    <!-- MODAL SUKSES SIMPAN PERUBAHAN PROFIL -->
    <?php if (!empty($modal_pesan)): ?>
        <div class="modal-overlay">
            <div class="modal-box">
                <div class="modal-icon"><i class="fa-solid fa-circle-check"></i></div>
                <h3>Pembaruan Berhasil!</h3>
                <p><?= htmlspecialchars($modal_pesan) ?></p>
                <a href="profil.php" class="btn-modal-close">Kembali ke Profil <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    <?php endif; ?>

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
        <div class="main-card">
            <h2 class="main-title"><i class="fa-solid fa-user-gear" style="color: #002855;"></i> Ubah Profil & Sandi</h2>
            <p class="sub-title">Perbarui informasi dasar, nama pengguna, dan sandi Anda di bawah ini.</p>

            <?php if (isset($error_pesan)): ?>
                <div class="alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error_pesan) ?></div>
            <?php endif; ?>

            <!-- WADAH FOTO PROFIL DI ATAS NAMA LENGKAP -->
            <div class="avatar-section-top">
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

                <h3><?= htmlspecialchars($user['username'] ?? '') ?></h3>

                <div class="action-photo-buttons">
                    <form method="POST" id="form-upload-foto" enctype="multipart/form-data" style="margin:0;">
                        <!-- KHUSUS FILE GANTI FOTO PROFIL HANYA INPUT AKSEP GAMBAR VALID -->
                        <input type="file" name="foto_profil" id="file-avatar-input" accept="image/png, image/jpeg, image/jpg, image/webp" style="display: none;" onchange="validateAndSubmitAvatar(this)">
                        <button type="button" class="btn-ganti-foto" onclick="document.getElementById('file-avatar-input').click()">
                            <i class="fa-solid fa-camera"></i> Ganti Foto Profil
                        </button>
                    </form>

                    <?php if (!empty($user['foto_profil'])): ?>
                        <form method="POST" style="margin:0;">
                            <button type="submit" name="hapus_foto" class="btn-hapus-foto" onclick="return confirm('Yakin ingin menghapus foto profil?')">
                                <i class="fa-solid fa-trash-can"></i> Hapus Foto
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- FORM ISIAN INPUT DATA DI BAWAH FOTO PROFIL -->
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
                    <h4><i class="fa-solid fa-lock"></i> Ubah Kata Sandi <span style="font-size:13px; font-weight:normal; color:#888;">(Kosongkan jika tidak diubah)</span></h4>
                    <div class="form-grid">
                        <div>
                            <label>Kata Sandi Lama</label>
                            <div class="password-container">
                                <input type="password" id="pass-lama" name="password_lama" class="form-control" placeholder="Sandi lama">
                                <i class="fa-solid fa-eye toggle-eye" onclick="togglePass('pass-lama', this)"></i>
                            </div>
                        </div>
                        <div>
                            <label>Kata Sandi Baru</label>
                            <div class="password-container">
                                <input type="password" id="pass-baru" name="password_baru" class="form-control" placeholder="Sandi baru">
                                <i class="fa-solid fa-eye toggle-eye" onclick="togglePass('pass-baru', this)"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bottom-button-group">
                    <a href="profil.php" class="btn-kembali-bottom">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke Profil
                    </a>
                    <button type="submit" name="update_profil" class="btn-save">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
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
        function validateAndSubmitAvatar(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const validTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert('Hanya file foto (PNG, JPG, JPEG, WEBP) yang dapat diunggah! Dokumen seperti PDF atau Word tidak diperbolehkan.');
                    input.value = '';
                    return;
                }
                document.getElementById('form-upload-foto').submit();
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

        const togglePass = (id, icon) => {
            const el = document.getElementById(id);
            el.type = el.type === 'password' ? 'text' : 'password';
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        };

        document.getElementById('form-profil').addEventListener('submit', e => {
            if (document.getElementById('pass-baru').value && !document.getElementById('pass-lama').value) {
                e.preventDefault();
                alert('Silakan masukkan Kata Sandi Lama Anda untuk mengonfirmasi perubahan sandi!');
                document.getElementById('pass-lama').focus();
            }
        });
    </script>
</body>

</html>
