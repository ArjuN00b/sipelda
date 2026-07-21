<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || $_SESSION['role'] !== 'masyarakat') {
    header("Location: ../auth/login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$laporan_sukses = false;

if (isset($_POST['kirim_pengaduan'])) {
    $kategori  = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $lokasi    = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['isi_laporan']);
    $lat       = mysqli_real_escape_string($koneksi, $_POST['latitude']);
    $lng       = mysqli_real_escape_string($koneksi, $_POST['longitude']);
    
    // Validasi input wajib server-side
    $error_fields = [];
    if (empty($kategori)) $error_fields[] = "Kategori";
    if (empty($lokasi)) $error_fields[] = "Lokasi Kejadian";
    if (empty($deskripsi)) $error_fields[] = "Deskripsi Laporan";
    if (empty($lat) || empty($lng)) $error_fields[] = "Koordinat Peta";
    
    $nama_foto = "";
    
    // Periksa apakah upload webcam (base64) atau file input
    if (!empty($_POST['webcam_data'])) {
        $webcam_data = $_POST['webcam_data'];
        $image_parts = explode(";base64,", $webcam_data);
        $image_base64 = base64_decode($image_parts[1]);
        $nama_foto = time() . '_bukti_webcam.png';
        file_put_contents(dirname(__DIR__) . '/uploads/' . $nama_foto, $image_base64);
    } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $nama_file = $_FILES['foto']['name'];
        $ekstensi  = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
        $ukuran    = $_FILES['foto']['size'];

        if (in_array($ekstensi, ['png', 'jpg', 'jpeg', 'webp']) && $ukuran <= 10485760) { // Max 10MB
            $nama_foto = time() . '_bukti_' . preg_replace("/[^a-zA-Z0-9.]/", "", $nama_file);
            move_uploaded_file($_FILES['foto']['tmp_name'], dirname(__DIR__) . '/uploads/' . $nama_foto);
        } else {
            echo "<script>alert('Gagal! Format foto tidak valid atau ukuran lebih dari 10MB.'); window.history.back();</script>";
            exit;
        }
    } else {
        $error_fields[] = "Foto Bukti Kejadian";
    }

    if (!empty($error_fields)) {
        echo "<script>alert('Harap lengkapi semua field wajib: " . implode(', ', $error_fields) . "'); window.history.back();</script>";
        exit;
    }

    $judul_laporan = $kategori . " - " . $lokasi;
    $judul_laporan .= isset($_POST['anonim']) ? " [ANONIM]" : "";
    $judul_laporan .= ($_POST['privasi'] === 'privat') ? " [PRIVAT]" : "";

    $isi_laporan_lengkap = $deskripsi . "\n\n📍 Titik Koordinat Peta:\nhttp://maps.google.com/?q=" . $lat . "," . $lng;

    $query = "INSERT INTO pengaduan (id_user, judul_laporan, isi_laporan, foto, status) 
              VALUES ('$id_user', '$judul_laporan', '$isi_laporan_lengkap', '$nama_foto', 'menunggu')";

    if (mysqli_query($koneksi, $query)) {
        $laporan_sukses = true;
    } else {
        echo "<script>alert('Gagal mengirim laporan ke database!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buat Laporan Baru - SIPELDA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        @keyframes pageFadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        body { 
            font-family: 'Segoe UI', Tahoma, sans-serif; 
            background-color: #f4f7fb; 
            margin: 0; 
            color: #1e293b; 
            padding-bottom: 90px;
            animation: pageFadeIn 0.4s ease-out;
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
        }
        
        .brand-divider {
            max-width: 750px;
            margin: 15px auto 25px;
            border-bottom: 2px solid #cbd5e1;
            opacity: 0.7;
        }
        
        .container { 
            max-width: 750px; 
            margin: 0 auto 40px; 
            padding: 40px; 
            background: #fff; 
            border-radius: 20px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); 
            border: 1px solid #e2e8f0;
            box-sizing: border-box;
        }
        .header-title { text-align: center; margin-bottom: 35px; }
        .header-title h2 { color: #002855; margin: 0 0 10px; font-size: 30px; font-weight: 800; }
        
        .form-group { margin-bottom: 30px; position: relative; }
        .form-group label.main-label { display: block; font-weight: 700; margin-bottom: 12px; font-size: 15px; color: #0f172a; }
        .form-control { width: 100%; padding: 15px; border: 1.5px solid #cbd5e1; border-radius: 10px; box-sizing: border-box; outline: none; font-size: 15px; background: #f8fafc; transition: 0.2s; }
        .form-control:focus { border-color: #002855; background: white; }
        .form-control[readonly] { background-color: #e2e8f0; color: #64748b; cursor: not-allowed; }
        textarea.form-control { height: 130px; resize: vertical; }

        .upload-options { display: flex; gap: 15px; margin-bottom: 15px; }
        .btn-upload-opt { flex: 1; padding: 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; background: #f8fafc; font-weight: 700; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.2s; }
        .btn-upload-opt.active { background: #e0e7ff; color: #002855; border-color: #002855; }
        
        .file-upload-wrapper { border: 2px dashed #002855; padding: 40px 20px; text-align: center; border-radius: 10px; background: #f8fbff; cursor: pointer; transition: 0.3s; }
        .file-upload-wrapper:hover { background: #eef5ff; }

        .webcam-container { display: none; border: 2.5px dashed #002855; border-radius: 12px; padding: 15px; text-align: center; background: #f8fbff; }
        .webcam-stream { width: 100%; max-height: 380px; background: black; border-radius: 10px; object-fit: cover; }
        .btn-capture { background: #002855; color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: 700; margin-top: 15px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

        .preview-area { display: none; position: relative; width: 100%; border-radius: 10px; border: 1.5px solid #cbd5e1; background: #f8fafc; padding: 10px; box-sizing: border-box; }
        .preview-area img { width: 100%; max-height: 400px; object-fit: contain; border-radius: 8px; display: block; }
        .btn-hapus-preview { position: absolute; top: 15px; right: 15px; background: #dc3545; color: white; border: none; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.25); }
        
        .pilihan-lokasi { display: flex; gap: 20px; margin-bottom: 15px; background: #f8fafc; padding: 15px; border-radius: 10px; border: 1.5px solid #cbd5e1; }
        .radio-lokasi { display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 15px; font-weight: 600; color: #334155; }
        .radio-lokasi input[type="radio"] { width: 18px; height: 18px; cursor: pointer; accent-color: #002855; }
        #map { height: 300px; width: 100%; border-radius: 10px; margin-top: 10px; border: 1.5px solid #cbd5e1; z-index: 1; }
        .map-status { font-size: 14px; color: #198754; font-weight: 700; display: block; margin-bottom: 10px; }
        
        .search-results { position: absolute; top: 52px; left: 0; right: 0; background: white; border: 1.5px solid #cbd5e1; border-radius: 10px; max-height: 200px; overflow-y: auto; z-index: 1000; display: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .search-results div { padding: 12px 15px; cursor: pointer; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .search-results div:hover { background: #f1f5f9; color: #002855; }
        
        .checkbox-area { background: #fef3c7; padding: 16px 20px; border-radius: 10px; border: 1px solid #fcd34d; display: flex; align-items: center; gap: 10px; margin-bottom: 35px; font-size: 15px; font-weight: 600; }
        .btn-submit { width: 100%; background: #002855; color: white; padding: 16px; border: none; border-radius: 10px; font-size: 17px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background: #001a3b; }
        
        .error-msg { color: #dc2626; font-size: 14px; margin-top: 8px; display: none; font-weight: 600; }

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
            text-align: center;
        }

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
        .footbar-item:hover { color: #ffffff; background: rgba(255, 255, 255, 0.08); }
    </style>
</head>
<body>

    <!-- MODAL SUKSES IN-APP -->
    <?php if ($laporan_sukses): ?>
        <div class="modal-overlay">
            <div class="modal-box">
                <div class="modal-icon-success"><i class="fa-solid fa-circle-check"></i></div>
                <h3>Laporan Terkirim!</h3>
                <p>Laporan pengaduan Anda telah sukses dikirimkan ke sistem SIPELDA dan akan segera ditinjau oleh petugas kelurahan.</p>
                <a href="historipengaduan.php" class="btn-modal-close">Lanjut ke Riwayat Aduan</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="loading-overlay" id="loading-overlay">
        <i class="fa-solid fa-circle-notch loading-spinner"></i>
        <div style="font-size: 20px; font-weight: bold; margin-bottom: 10px;">Sedang Mengirim Laporan...</div>
        <div style="font-size: 14px; color: #cbd5e1;">Mohon tunggu, jangan tutup halaman ini.</div>
    </div>

    <!-- HEADER SIPELDA TENGAH -->
    <div class="brand-header-centered">
        <a href="index.php">
            <i class="fa-solid fa-shield-halved" style="color: #002855;"></i> SIPELDA
        </a>
    </div>
    <div class="brand-divider"></div>

    <div class="container">
        <div class="header-title"><h2>Buat Laporan Baru</h2></div>

        <form action="" method="POST" enctype="multipart/form-data" novalidate id="form-laporan">
            <input type="hidden" name="webcam_data" id="webcam-data">
            
            <div class="form-group">
                <label class="main-label">1. Unggah Bukti Kejadian <span style="color:red;">*</span></label>
                
                <div class="upload-options">
                    <button type="button" class="btn-upload-opt active" id="opt-file" onclick="setUploadMode('file')"><i class="fa-solid fa-images"></i> Dari File Galeri</button>
                    <button type="button" class="btn-upload-opt" id="opt-camera" onclick="setUploadMode('camera')"><i class="fa-solid fa-camera"></i> Ambil Kamera</button>
                </div>

                <!-- Input File -->
                <div class="file-upload-wrapper" id="upload-wrapper" onclick="document.getElementById('input-foto').click()">
                    <i class="fa-solid fa-cloud-arrow-up" style="font-size:40px; color:#002855; display:block; margin-bottom:15px;"></i>
                    <strong style="color:#002855; font-size: 16px;">Klik di sini untuk memilih foto</strong>
                    <p style="margin: 5px 0 0; color: #64748b; font-size: 13px;">Format: PNG, JPG, JPEG, WEBP (Maks 10MB)</p>
                    <input type="file" name="foto" id="input-foto" accept="image/png, image/jpeg, image/jpg, image/webp" style="display: none;">
                </div>

                <!-- Kamera Stream -->
                <div class="webcam-container" id="webcam-wrapper">
                    <video id="webcam" class="webcam-stream" autoplay playsinline></video>
                    <button type="button" class="btn-capture" onclick="takeSnapshot()"><i class="fa-solid fa-circle-dot"></i> Ambil Gambar</button>
                </div>

                <!-- Preview Area -->
                <div class="preview-area" id="preview-wrapper">
                    <img id="preview-image" src="#" alt="Preview">
                    <button type="button" class="btn-hapus-preview" id="btn-hapus-preview" onclick="resetUpload()"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="error-msg" id="err-foto"><i class="fa-solid fa-circle-exclamation"></i> Bukti foto wajib dilampirkan!</div>
            </div>

            <div class="form-group">
                <label class="main-label">2. Kategori Masalah <span style="color:red;">*</span></label>
                <select name="kategori" id="input-kategori" class="form-control">
                    <option value="">-- Pilih Kategori Permasalahan --</option>
                    <option value="Jalan Rusak & Infrastruktur">Jalan Rusak & Infrastruktur</option>
                    <option value="Kebersihan & Sampah">Kebersihan & Sampah</option>
                    <option value="Penerangan Jalan Umum (PJU)">Penerangan Jalan Umum (PJU)</option>
                    <option value="Kesehatan & Lingkungan">Kesehatan & Lingkungan</option>
                    <option value="Keamanan & Ketertiban">Keamanan & Ketertiban</option>
                    <option value="Ketertiban Lalu Lintas & Parkir">Ketertiban Lalu Lintas & Parkir</option>
                    <option value="Pelayanan Administrasi">Pelayanan Administrasi & Birokrasi</option>
                    <option value="Bantuan Sosial (Bansos)">Bantuan Sosial (Bansos)</option>
                    <option value="Kedaruratan & Bencana">Kedaruratan & Bencana</option>
                    <option value="Fasilitas Umum">Fasilitas Umum</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
                <div class="error-msg" id="err-kategori"><i class="fa-solid fa-circle-exclamation"></i> Harap pilih Kategori Masalah!</div>
            </div>

            <div class="form-group">
                <label class="main-label">3. Lokasi Kejadian <span style="color:red;">*</span></label>
                
                <div class="pilihan-lokasi">
                    <label class="radio-lokasi">
                        <input type="radio" name="mode_lokasi" value="gps" checked>
                        <span><i class="fa-solid fa-location-crosshairs"></i> Gunakan Lokasi Saat Ini</span>
                    </label>
                    <label class="radio-lokasi">
                        <input type="radio" name="mode_lokasi" value="manual">
                        <span><i class="fa-solid fa-pen-to-square"></i> Cari Lokasi Manual</span>
                    </label>
                </div>

                <span id="map-status" class="map-status"><i class="fa-solid fa-spinner fa-spin"></i> Mendeteksi lokasi GPS...</span>
                
                <div style="position: relative;">
                    <input type="text" name="lokasi" id="input-lokasi" class="form-control" placeholder="Mencari alamat lokasi saat ini..." readonly autocomplete="off">
                    <div id="search-results" class="search-results"></div>
                </div>

                <div id="map"></div>
                <input type="hidden" name="latitude" id="lat"><input type="hidden" name="longitude" id="lng">
                
                <div class="error-msg" id="err-lokasi"><i class="fa-solid fa-circle-exclamation"></i> Harap pastikan lokasi kejadian telah ditentukan!</div>
            </div>

            <div class="form-group">
                <label class="main-label">4. Deskripsi Lengkap <span style="color:red;">*</span></label>
                <textarea name="isi_laporan" id="input-deskripsi" class="form-control" placeholder="Ceritakan kronologi secara lengkap..."></textarea>
                <div class="error-msg" id="err-deskripsi"><i class="fa-solid fa-circle-exclamation"></i> Harap isi deskripsi kejadian secara lengkap!</div>
            </div>

            <div class="form-group">
                <label class="main-label">5. Sifat Laporan <span style="color:red;">*</span></label>
                <select name="privasi" class="form-control">
                    <option value="publik"> Publik (Semua warga dapat melihat laporan ini)</option>
                    <option value="privat"> Privat (Hanya Admin yang dapat mengakses laporan ini)</option>
                </select>
            </div>

            <div class="checkbox-area">
                <input type="checkbox" id="anonim" name="anonim" value="1" style="width: 18px; height: 18px;">
                <label for="anonim">Sembunyikan nama asli saya di halaman publik (Anonim)</label>
            </div>

            <button type="submit" name="kirim_pengaduan" id="btn-submit" class="btn-submit">Kirim Laporan Sekarang</button>
        </form>
    </div>

    <!-- FOOTBAR NAVIGATION BAR -->
    <nav class="footbar-nav">
        <a href="../index.php" class="footbar-item">
            <i class="fa-solid fa-house"></i>
            <span>Beranda</span>
        </a>
        <a href="historipengaduan.php" class="footbar-item">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Riwayat</span>
        </a>
        <a href="../profil/profil.php" class="footbar-item">
            <i class="fa-solid fa-user"></i>
            <span>Profil</span>
        </a>
    </nav>

    <script>
        const inputFoto = document.getElementById('input-foto');
        const previewImage = document.getElementById('preview-image');
        const uploadWrapper = document.getElementById('upload-wrapper');
        const previewWrapper = document.getElementById('preview-wrapper');
        const webcamWrapper = document.getElementById('webcam-wrapper');
        const video = document.getElementById('webcam');
        let stream = null;

        function setUploadMode(mode) {
            document.getElementById('opt-file').classList.toggle('active', mode === 'file');
            document.getElementById('opt-camera').classList.toggle('active', mode === 'camera');
            
            resetUpload();

            if (mode === 'camera') {
                uploadWrapper.style.display = 'none';
                webcamWrapper.style.display = 'block';
                navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
                    .then(s => { stream = s; video.srcObject = s; })
                    .catch(err => {
                        alert('Kamera tidak dapat diakses atau diblokir.');
                        setUploadMode('file');
                    });
            } else {
                webcamWrapper.style.display = 'none';
                uploadWrapper.style.display = 'block';
                stopWebcam();
            }
        }

        function stopWebcam() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
        }

        inputFoto.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                if (!['image/jpeg', 'image/jpg', 'image/png', 'image/webp'].includes(file.type) || file.size > 10485760) {
                    alert('Hanya diperbolehkan format Foto (PNG, JPG, JPEG, WEBP) maksimal 10MB!');
                    this.value = ""; return;
                }
                const reader = new FileReader();
                reader.onload = e => { 
                    previewImage.src = e.target.result; 
                    uploadWrapper.style.display = 'none'; 
                    previewWrapper.style.display = 'block'; 
                    document.getElementById('err-foto').style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });

        function takeSnapshot() {
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            const base64Data = canvas.toDataURL('image/png');
            
            document.getElementById('webcam-data').value = base64Data;
            previewImage.src = base64Data;
            
            webcamWrapper.style.display = 'none';
            previewWrapper.style.display = 'block';
            document.getElementById('err-foto').style.display = 'none';
            stopWebcam();
        }

        function resetUpload() {
            inputFoto.value = "";
            document.getElementById('webcam-data').value = "";
            previewImage.src = "#";
            previewWrapper.style.display = 'none';
            if (document.getElementById('opt-camera').classList.contains('active')) {
                webcamWrapper.style.display = 'block';
                if (!stream) {
                    navigator.mediaDevices.getUserMedia({ video: true }).then(s => { stream = s; video.srcObject = s; });
                }
            } else {
                uploadWrapper.style.display = 'block';
            }
        }

        const map = L.map('map').setView([-7.250445, 112.768845], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        const marker = L.marker([-7.250445, 112.768845], { draggable: true }).addTo(map);
        
        const inputLokasi = document.getElementById('input-lokasi');
        const mapStatus = document.getElementById('map-status');
        const searchResults = document.getElementById('search-results');
        const radioModeLokasi = document.querySelectorAll('input[name="mode_lokasi"]');
        let searchTimeout;

        function setKoordinatDanNamaJalan(lat, lng) {
            document.getElementById('lat').value = lat; 
            document.getElementById('lng').value = lng;
            map.setView([lat, lng], 17); 
            marker.setLatLng([lat, lng]);
            
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(res => res.json())
                .then(data => { 
                    if(data.display_name) {
                        inputLokasi.value = data.display_name; 
                        document.getElementById('err-lokasi').style.display = 'none';
                    }
                });
        }

        function detectUserLocation() {
            mapStatus.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mendeteksi lokasi GPS...';
            mapStatus.style.color = "#002855";
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(pos => {
                    setKoordinatDanNamaJalan(pos.coords.latitude, pos.coords.longitude);
                    mapStatus.innerHTML = '<i class="fa-solid fa-location-dot"></i> Lokasi GPS otomatis ditemukan.'; 
                    mapStatus.style.color = "#198754";
                }, () => {
                    mapStatus.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Gagal melacak GPS. Silakan gunakan mode manual.'; 
                    mapStatus.style.color = "#dc3545";
                }, { enableHighAccuracy: true });
            }
        }

        detectUserLocation();

        radioModeLokasi.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'gps') {
                    inputLokasi.readOnly = true;
                    inputLokasi.placeholder = "Mencari alamat lokasi saat ini...";
                    searchResults.style.display = 'none';
                    detectUserLocation();
                } else {
                    inputLokasi.readOnly = false;
                    inputLokasi.placeholder = "Ketikkan nama jalan atau lokasi kejadian...";
                    mapStatus.innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Silakan ketik alamat atau geser pin peta.';
                    mapStatus.style.color = "#002855";
                    inputLokasi.focus();
                }
            });
        });

        marker.on('dragstart', () => { 
            document.querySelector('input[value="manual"]').checked = true;
            inputLokasi.readOnly = false;
            mapStatus.innerHTML = '<i class="fa-solid fa-map-pin"></i> Pin digeser manual.'; 
            mapStatus.style.color = "#002855";
        });
        marker.on('dragend', () => { 
            const pos = marker.getLatLng(); 
            setKoordinatDanNamaJalan(pos.lat, pos.lng); 
        });

        inputLokasi.addEventListener('input', function() {
            if (document.querySelector('input[name="mode_lokasi"]:checked').value === 'gps') return;
            clearTimeout(searchTimeout);
            const query = this.value;
            if (query.length < 3) { searchResults.style.display = 'none'; return; }

            searchTimeout = setTimeout(() => {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}&limit=5&countrycodes=id`)
                    .then(res => res.json())
                    .then(data => {
                        searchResults.innerHTML = '';
                        if (data.length > 0) {
                            searchResults.style.display = 'block';
                            data.forEach(item => {
                                const div = document.createElement('div');
                                div.textContent = item.display_name;
                                div.onclick = () => {
                                    setKoordinatDanNamaJalan(parseFloat(item.lat), parseFloat(item.lon));
                                    inputLokasi.value = item.display_name;
                                    document.getElementById('err-lokasi').style.display = 'none';
                                    searchResults.style.display = 'none';
                                };
                                searchResults.appendChild(div);
                            });
                        } else { searchResults.style.display = 'none'; }
                    });
            }, 600);
        });

        document.addEventListener('click', e => {
            if (e.target !== inputLokasi && e.target !== searchResults) { searchResults.style.display = 'none'; }
        });

        // Validasi
        const valKategori = document.getElementById('input-kategori');
        const valDeskripsi = document.getElementById('input-deskripsi');

        valKategori.addEventListener('change', () => document.getElementById('err-kategori').style.display = 'none');
        inputLokasi.addEventListener('input', () => document.getElementById('err-lokasi').style.display = 'none');
        valDeskripsi.addEventListener('input', () => document.getElementById('err-deskripsi').style.display = 'none');

        document.getElementById('form-laporan').addEventListener('submit', function(e) {
            let isValid = true;
            document.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');

            const hasFoto = inputFoto.files.length > 0 || document.getElementById('webcam-data').value !== "";
            if (!hasFoto) { document.getElementById('err-foto').style.display = 'block'; isValid = false; }
            if (valKategori.value === "") { document.getElementById('err-kategori').style.display = 'block'; isValid = false; }
            if (inputLokasi.value.trim() === "") { document.getElementById('err-lokasi').style.display = 'block'; isValid = false; }
            if (valDeskripsi.value.trim() === "") { document.getElementById('err-deskripsi').style.display = 'block'; isValid = false; }

            const latVal = document.getElementById('lat').value;
            const lngVal = document.getElementById('lng').value;
            if (!latVal || !lngVal) {
                document.getElementById('err-lokasi').style.display = 'block';
                document.getElementById('err-lokasi').textContent = "Titik lokasi pada peta belum ditentukan!";
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                document.getElementById('loading-overlay').style.display = 'flex';
                document.getElementById('btn-submit').style.pointerEvents = 'none';
            }
        });
    </script>
</body>
</html>
