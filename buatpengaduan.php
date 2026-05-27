<?php
session_start();
require 'koneksi.php';

// 1. Proteksi Halaman Khusus Masyarakat
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || $_SESSION['role'] !== 'masyarakat') {
    header("Location: login.php");
    exit;
}

// 2. Logika Pemrosesan Form Pengaduan
if (isset($_POST['kirim_pengaduan'])) {
    $id_user     = $_SESSION['id_user'];
    $lokasi      = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $kategori    = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $isi_laporan = mysqli_real_escape_string($koneksi, $_POST['isi_laporan']);
    $is_anonim   = isset($_POST['anonim']) ? true : false;

    // Trik Database: Menggabungkan Kategori dan Lokasi menjadi Judul Laporan
    $judul_laporan = $kategori . " - " . $lokasi;
    if ($is_anonim) {
        $judul_laporan .= " [ANONIM]"; // Menambahkan tag anonim di judul agar admin tahu
    }

    $nama_foto = ""; // Default jika tidak ada foto
    $upload_sukses = true;

    // 3. Logika Upload Foto (Jika ada file yang diunggah)
    if (isset($_FILES['foto']['name']) && $_FILES['foto']['name'] != '') {
        $file_name = $_FILES['foto']['name'];
        $file_size = $_FILES['foto']['size'];
        $file_tmp  = $_FILES['foto']['tmp_name'];
        
        $ext_allow = array('jpg', 'jpeg', 'png');
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validasi Ekstensi dan Ukuran (Maks 2MB)
        if (in_array($file_ext, $ext_allow)) {
            if ($file_size <= 2097152) { // 2MB dalam bytes
                // Membuat nama file unik dengan timestamp agar tidak tertimpa
                $nama_foto = time() . '_' . $file_name; 
                $path = 'asets/uploads/' . $nama_foto;
                
                // Pindahkan file dari memori sementara ke folder proyek
                move_uploaded_file($file_tmp, $path);
            } else {
                $error = "Ukuran foto terlalu besar. Maksimal 2MB!";
                $upload_sukses = false;
            }
        } else {
            $error = "Format foto tidak valid. Hanya JPG, JPEG, dan PNG yang diperbolehkan!";
            $upload_sukses = false;
        }
    }

    // 4. Simpan ke Database jika upload lolos validasi
    if ($upload_sukses && !isset($error)) {
        $query = "INSERT INTO pengaduan (id_user, judul_laporan, isi_laporan, foto) 
                    VALUES ('$id_user', '$judul_laporan', '$isi_laporan', '$nama_foto')";
        
        if (mysqli_query($koneksi, $query)) {
            echo "<script>
                    alert('Laporan pengaduan Anda berhasil dikirim!');
                    window.location.href = 'historipengaduan.php';
                    </script>";
            exit;
        } else {
            $error = "Gagal menyimpan ke database: " . mysqli_error($koneksi);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Pengaduan - SIPELDA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; color: #1e293b; }

        /* Top Navbar */
        .navbar { background-color: #0c2d6b; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; color: white; }
        .navbar-brand { font-size: 20px; font-weight: bold; }
        .navbar-links a { color: #cbd5e1; text-decoration: none; margin: 0 15px; font-size: 14px; transition: 0.2s; }
        .navbar-links a:hover, .navbar-links a.active { color: white; font-weight: 600; }
        .navbar-user { display: flex; align-items: center; gap: 15px; }
        .navbar-user span { font-size: 14px; }
        .avatar { width: 35px; height: 35px; background-color: #cbd5e1; border-radius: 50%; }
        .btn-logout { background-color: #dc2626; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 12px; font-weight: bold; }

        /* Main Container */
        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .form-card { background-color: white; border-radius: 12px; padding: 40px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .form-card h2 { font-size: 24px; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px; }

        /* Form Elements */
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 10px; color: #334155; }
        .form-group label span { color: #ef4444; } /* Red asterisk */

        /* Upload Area */
        .upload-area { border: 2px dashed #cbd5e1; background-color: #f8fafc; border-radius: 8px; padding: 40px 20px; text-align: center; cursor: pointer; transition: 0.2s; position: relative; }
        .upload-area:hover { border-color: #94a3b8; background-color: #f1f5f9; }
        .upload-icon { font-size: 30px; color: #0c2d6b; background-color: #e2e8f0; width: 60px; height: 60px; line-height: 60px; border-radius: 12px; margin: 0 auto 15px; }
        .upload-text { font-size: 14px; color: #64748b; }
        .upload-text small { display: block; margin-top: 5px; color: #94a3b8; }
        .upload-area input[type="file"] { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }

        /* Inputs & Selects */
        .input-group { position: relative; }
        .input-group i { position: absolute; left: 15px; top: 14px; color: #94a3b8; }
        .form-control { width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #1e293b; outline: none; transition: 0.2s; }
        .input-group .form-control { padding-left: 40px; }
        .form-control:focus { border-color: #0c2d6b; box-shadow: 0 0 0 3px rgba(12, 45, 107, 0.1); }
        textarea.form-control { resize: vertical; min-height: 120px; }

        /* Checkbox Anonim */
        .checkbox-area { background-color: #f1f5f9; padding: 15px; border-radius: 6px; display: flex; align-items: center; gap: 10px; border: 1px solid #e2e8f0; }
        .checkbox-area input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
        .checkbox-area label { margin-bottom: 0; font-weight: normal; font-size: 14px; cursor: pointer; }

        /* Submit Button */
        .btn-submit { width: 100%; background-color: #0c2d6b; color: white; border: none; padding: 15px; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 10px; display: flex; justify-content: center; align-items: center; gap: 10px; transition: 0.2s; }
        .btn-submit:hover { background-color: #1e4088; }

        /* Alert */
        .alert { background-color: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 15px; border-radius: 6px; margin-bottom: 25px; font-size: 14px; }

        /* Footer Helpers */
        .helpers { display: flex; justify-content: center; gap: 15px; margin-top: 30px; }
        .btn-outline { background-color: transparent; border: 1px solid #cbd5e1; color: #0c2d6b; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; background-color: white; }
        .btn-gray { background-color: #e2e8f0; border: 1px solid #e2e8f0; color: #475569; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; }
    </style>
</head>
<body>

<div class="navbar">
        <div class="navbar-brand">SIPELDA</div>
        <div class="navbar-links">
            <a href="masyarakat.php">Beranda</a>
            <a href="historipengaduan.php">Riwayat</a>
        </div>
        <div class="navbar-user">
            <span>Halo, <?= $_SESSION['username']; ?></span>
            <div class="avatar"></div>
            <a href="logout.php" class="btn-logout">Keluar</a>
        </div>
    </div>

    <div class="container">
        <div class="form-card">
            <h2>Formulir Pengaduan Baru</h2>

            <?php if (isset($error)) : ?>
                <div class="alert"><i class="fa-solid fa-triangle-exclamation"></i> <?= $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                
<div class="form-group">
    <label>Unggah Foto Bukti <span>*</span></label>
    <div class="upload-area">
        <input type="file" name="foto" accept="image/jpeg, image/png, image/jpg" required id="file-input">
        
        <div id="preview-container" style="display: none; position: relative; display: inline-block; max-width: 100%; margin-bottom: 15px; z-index: 15;">            <img id="image-preview" src="#" alt="Pratinjau Foto" style="max-width: 100%; max-height: 200px; border-radius: 8px; object-fit: cover; border: 2px solid #cbd5e1;">
            <button type="button" id="btn-cancel" style="position: absolute; top: -10px; right: -10px; background-color: black; color: white; border: none; width: 25px; height: 25px; border-radius: 50%; font-size: 16px; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2); transition: 0.2s;">&times;</button>
        </div>
        
        <div class="upload-icon" id="preview-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
        <div class="upload-text" id="file-name">
            Ambil atau pilih foto kejadian terlebih dahulu.<small>Maksimal 2MB (JPG, JPEG, PNG)</small>
        </div>
    </div>
</div>
                <div class="form-group">
                    <label>Lokasi Kejadian / Patokan <span>*</span></label>
                    <div class="input-group">
                        <i class="fa-solid fa-location-dot"></i>
                        <input type="text" name="lokasi" class="form-control" placeholder="Contoh: Depan warung Bu Eem RT 02/RW 04, tiang listrik roboh" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Kategori Laporan <span>*</span></label>
                    <select name="kategori" class="form-control" required>
                        <option value="" disabled selected>Pilih kategori yang sesuai</option>
                        <option value="Jalan & Infrastruktur">Jalan & Infrastruktur</option>
                        <option value="Kebersihan & Sampah">Kebersihan & Sampah</option>
                        <option value="Fasilitas Umum">Fasilitas Umum</option>
                        <option value="Keamanan & Ketertiban">Keamanan & Ketertiban</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Detail Laporan / Deskripsi <span>*</span></label>
                    <textarea name="isi_laporan" class="form-control" placeholder="Ceritakan detail kejadian secara jelas dan rinci..." required></textarea>
                </div>

                <div class="form-group">
                    <div class="checkbox-area">
                        <input type="checkbox" id="anonim" name="anonim" value="1">
                        <label for="anonim">Sembunyikan nama saya di halaman publik (Anonim)</label>
                    </div>
                </div>

                <button type="submit" name="kirim_pengaduan" class="btn-submit">
                    <i class="fa-solid fa-paper-plane"></i> Kirim Pengaduan Sekarang
                </button>
            </form>
        </div>

        <div class="helpers">
            <a href="#" class="btn-outline"><i class="fa-solid fa-book"></i> Lihat Tutorial Cara Melapor</a>
            <a href="#" class="btn-gray"><i class="fa-solid fa-envelope"></i> Hubungi Kami via Email</a>
        </div>
    </div>

<script>
    const fileInput = document.getElementById('file-input');
    const fileNameDisplay = document.getElementById('file-name');
    const previewContainer = document.getElementById('preview-container');
    const imagePreview = document.getElementById('image-preview');
    const previewIcon = document.getElementById('preview-icon');
    const btnCancel = document.getElementById('btn-cancel');

    // 1. Logika ketika user MEMILIH FOTO
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                previewContainer.style.display = 'inline-block'; // Munculkan wadah gambar & tombol silang
                previewIcon.style.display = 'none';   
                
                fileNameDisplay.innerHTML = `
                    <span style="color: #28a745; font-weight: bold;"><i class="fa-solid fa-circle-check"></i> Foto berhasil dimuat:</span><br>
                    <span style="color: #0c2d6b; font-size: 13px; font-weight: 600;">${file.name}</span>
                `;
            }
            
            reader.readAsDataURL(file);
        }
    });

    // 2. Logika ketika user KLIK TOMBOL SILANG (Batal/Hapus Foto)
    btnCancel.addEventListener('click', function(e) {
        e.preventDefault(); // Mencegah form tersubmit tidak sengaja
        e.stopPropagation(); // Mencegah trigger klik pada input file di belakangnya

        fileInput.value = ""; // Mengosongkan kembali isi input file HTML (wajib required)
        previewContainer.style.display = 'none'; // Sembunyikan gambar dan tombol silang
        previewIcon.style.display = 'block'; // Memunculkan kembali ikon cloud semula
        
        // Kembalikan teks instruksi asal
        fileNameDisplay.innerHTML = `Ambil atau pilih foto kejadian terlebih dahulu.<br><small>Maksimal 2MB (JPG, JPEG, PNG)</small>`;
    });
</script>

</body>
