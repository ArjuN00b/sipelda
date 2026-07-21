<?php
session_start();
require 'koneksi.php';

// Pastikan user sudah login
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    header("Location: ../auth/login.php");
    exit;
}
$id_user = $_SESSION['id_user'];
$update_sukses = false;
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
                $update_sukses = true;
            } else {
                echo "<script>alert('Gagal! Sandi lama salah.'); window.history.back();</script>";
                exit;
            }
        } else {
            // Update tanpa password
            mysqli_query($koneksi, "UPDATE users SET nama_lengkap='$nama_lengkap', username='$username_baru', no_telp='$no_telp' WHERE id_user='$id_user'");
            $_SESSION['username'] = $username_baru;
            $update_sukses = true;
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
        
        /* Modifikasi Avatar Simetris Tanpa sisa space */
        .avatar-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 25px;
        }
        .avatar { 
            width: 100%; 
            height: 100%; 
            border-radius: 50%; 
            background: #002855; 
            overflow: hidden; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 46px; 
            color: white; 
            font-weight: bold; 
            border: 3.5px solid white; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.15); 
            box-sizing: border-box;
        }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        
        .sidebar h3 { margin: 0 0 25px; color: #002855; font-size: 22px; font-weight: 800; }

        .btn-kembali { display: block; width: 100%; padding: 14px; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 14px; box-sizing: border-box; border: 1.5px solid #002855; color: #002855; background: white; margin-bottom: 12px; transition: 0.2s; text-align: center; }
        .btn-kembali:hover { background: #002855; color: white; }
        .btn-logout-merah { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 35px; background: #dc3545; color: white; border: none; font-size: 15px; width: 100%; padding: 14px; border-radius: 10px; font-weight: bold; cursor: pointer; box-sizing: border-box; transition: 0.2s; box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2); }
        .btn-logout-merah:hover { background-color: #c82333; }

        .main-content { flex: 2.5; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; box-sizing: border-box; }
        .main-title { margin-top: 0; color: #002855; font-size: 26px; font-weight: 800; }
        .sub-title { color: #64748b; margin-bottom: 30px; border-bottom: 1px solid #cbd5e1; padding-bottom: 15px; font-size: 15px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .form-group-full { margin-bottom: 20px; }
        label { display: block; font-size: 14px; font-weight: bold; color: #0f172a; margin-bottom: 8px; }

        .form-control { width: 100%; padding: 14px 16px; border: 1.5px solid #cbd5e1; border-radius: 10px; box-sizing: border-box; outline: none; font-size: 15px; background-color: #f8fafc; transition: 0.2s; }
        .form-control:focus { border-color: #002855; background-color: white; }

        .password-container { position: relative; display: flex; align-items: center; }
        .password-container .form-control { padding-right: 45px; }
        .toggle-eye { position: absolute; right: 15px; cursor: pointer; color: #64748b; font-size: 16px; }
        .password-box { background: #f8fafc; border: 1px solid #cbd5e1; padding: 25px; border-radius: 12px; margin: 30px 0; }
        .password-box h4 { margin: 0 0 20px; color: #002855; font-weight: 800; font-size: 16px; }
        .password-box .form-grid { margin-bottom: 0; }

        .btn-save { display: inline-flex; align-items: center; gap: 8px; background: #002855; color: white; border: none; padding: 15px 35px; border-radius: 10px; cursor: pointer; font-weight: bold; font-size: 15px; float: right; transition: 0.2s; }
        .btn-save:hover { background: #001a3b; }

        .btn-ganti-foto, .btn-logout-merah { display: block; width: 100%; padding: 14px; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 14px; box-sizing: border-box; transition: 0.2s; }
        .btn-ganti-foto { border: 1.5px solid #002855; color: #002855; background: white; margin-bottom: 12px; }
        .btn-ganti-foto:hover { background: #002855; color: white; }
        .btn-hapus-foto { color: #dc3545; background: transparent; border: none; font-weight: bold; cursor: pointer; font-size: 14px; margin-top: 10px; margin-bottom: 12px; }

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

        .btn-modal-close { display: block; width: 100%; padding: 16px; background-color: #002855; color: white; border-radius: 10px; font-size: 16px; font-weight: bold; text-decoration: none; box-sizing: border-box; text-align: center; }

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
        .modal-button-group { display: flex; gap: 12px; }
        .btn-modal-cancel { flex: 1; padding: 14px; background-color: #f1f5f9; color: #475569; border-radius: 10px; font-size: 15px; font-weight: bold; border: 1px solid #cbd5e1; cursor: pointer; transition: 0.2s; }
        .btn-modal-cancel:hover { background-color: #e2e8f0; }
        .btn-modal-logout-ya { flex: 1; padding: 14px; background-color: #dc2626; color: white; border-radius: 10px; font-size: 15px; font-weight: bold; text-decoration: none; display: inline-block; box-sizing: border-box; transition: 0.2s; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3); }
        .btn-modal-logout-ya:hover { background-color: #b91c1c; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

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

    <!-- MODAL SUKSES IN-APP (UPDATE FOTO/HAPUS FOTO) -->
    <?php if ($profil_sukses): ?>
        <div class="modal-overlay">
            <div class="modal-box">
                <div class="modal-icon-success"><i class="fa-solid fa-circle-check"></i></div>
                <h3>Berhasil!</h3>
                <p><?= $pesan_sukses ?></p>
                <a href="ganti_profil.php" class="btn-modal-close">OK</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL SUKSES IN-APP -->
    <?php if ($update_sukses): ?>
        <div class="modal-overlay">
            <div class="modal-box">
                <div class="modal-icon-success"><i class="fa-solid fa-circle-check"></i></div>
                <h3>Profil Diperbarui!</h3>
                <p>Data profil Anda telah sukses disimpan ke sistem.</p>
                <a href="profil.php" class="btn-modal-close">Lanjut ke Halaman Profil</a>
            </div>
        </div>
    <?php endif; ?>

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
            <div class="avatar-container">
                <div class="avatar">
                    <?php if (!empty($user['foto_profil']) && file_exists('../uploads/' . $user['foto_profil'])): ?>
                        <img src="../uploads/<?= $user['foto_profil'] ?>" alt="Foto Profil">
                    <?php else: ?>
                        <?= strtoupper(substr($user['username'], 0, 2)) ?>
                    <?php endif; ?>
                </div>
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

            <a href="profil.php" class="btn-kembali" style="margin-bottom:0;">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Profil
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

                <div style="border-top: 1px solid #eee; padding-top: 20px; display: flex; justify-content: flex-end; gap: 12px; align-items: center;">
                    <button type="button" class="btn-logout-merah" onclick="openConfirmLogout()" style="width: auto; margin-top: 0; padding: 14px 24px;">
                        <i class="fa-solid fa-right-from-bracket"></i> Keluar dari Akun
                    </button>
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
        <a href="profil.php" class="footbar-item">
            <i class="fa-solid fa-user"></i>
            <span>Profil</span>
        </a>
    </nav>

    <script>
        const togglePass = (id, icon) => {
            const el = document.getElementById(id);
            el.type = el.type === 'password' ? 'text' : 'password';
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        };

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
