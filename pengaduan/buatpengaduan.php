<?php
session_start();
require '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || $_SESSION['role'] !== 'masyarakat') {
    header("Location: ../auth/login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

if (isset($_POST['kirim_pengaduan'])) {
    $kategori  = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $lokasi    = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['isi_laporan']);
    $lat       = mysqli_real_escape_string($koneksi, $_POST['latitude']);
    $lng       = mysqli_real_escape_string($koneksi, $_POST['longitude']);

    $judul_laporan = $kategori . " - " . $lokasi;
    $judul_laporan .= ($_POST['privasi'] === 'privat') ? " [PRIVAT]" : "";

    $isi_laporan_lengkap = $deskripsi . "\n\n📍 Titik Koordinat Peta:\nhttp://maps.google.com/?q=" . $lat . "," . $lng;
    // ===== VALIDASI SERVER-SIDE: SEMUA FIELD WAJIB =====
    $error_fields = [];
    if (empty(trim($_POST['kategori'])))   $error_fields[] = 'Kategori masalah wajib dipilih.';
    if (empty(trim($_POST['lokasi'])))     $error_fields[] = 'Lokasi kejadian wajib diisi.';
    if (empty(trim($_POST['latitude'])) || empty(trim($_POST['longitude']))) $error_fields[] = 'Titik lokasi pada peta wajib ditentukan.';
    if (empty(trim($_POST['isi_laporan']))) $error_fields[] = 'Deskripsi kejadian wajib diisi.';
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] != 0) $error_fields[] = 'Bukti foto wajib diunggah.';

    if (!empty($error_fields)) {
        $pesan_error = implode(' ', $error_fields);
        echo "<script>alert('Harap lengkapi semua data:\\n" . addslashes($pesan_error) . "'); window.history.back();</script>";
        exit;
    }

    $nama_foto = "";

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $nama_file = $_FILES['foto']['name'];
        $ekstensi  = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
        $ukuran    = $_FILES['foto']['size'];

        $allowed_ext = ['png', 'jpg', 'jpeg', 'webp'];

        if (in_array($ekstensi, $allowed_ext) && $ukuran <= 10485760) { // Maksimal 10 MB
            $nama_foto = time() . '_bukti_' . preg_replace("/[^a-zA-Z0-9.]/", "", $nama_file);
            $target_dir = __DIR__ . '/../uploads/';
            $target_file = $target_dir . $nama_foto;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                // Upload bukti foto sukses
            } else {
                echo "<script>alert('Gagal memindahkan file bukti kejadian!'); window.history.back();</script>";
                exit;
            }
        } else {
            echo "<script>alert('Gagal! Hanya format foto (PNG, JPG, JPEG, WEBP) yang diperbolehkan dan maksimal 10 MB.'); window.history.back();</script>";
            exit;
        }
    }

    $query = "INSERT INTO pengaduan (id_user, judul_laporan, isi_laporan, foto, status) 
                VALUES ('$id_user', '$judul_laporan', '$isi_laporan_lengkap', '$nama_foto', 'menunggu')";

    if (mysqli_query($koneksi, $query)) {
        $_SESSION['laporan_sukses'] = true;
        header("Location: buatpengaduan.php?kirim=sukses");
        exit;
    } else {
        $error_kirim = "Gagal mengirim laporan! Silakan coba lagi.";
    }
}

$modal_sukses_kirim = false;
if (isset($_GET['kirim']) && $_GET['kirim'] === 'sukses') {
    $modal_sukses_kirim = true;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Laporan Baru - SIPELDA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
            background-color: #f4f7fb;
            margin: 0;
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
            padding: 45px 40px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .header-title {
            text-align: center;
            margin-bottom: 35px;
        }

        .header-title h2 {
            color: #002855;
            margin: 0 0 10px;
            font-size: 32px;
            font-weight: 800;
        }

        .form-group {
            margin-bottom: 28px;
            position: relative;
        }

        .form-group label.main-label {
            display: block;
            font-weight: 700;
            margin-bottom: 12px;
            font-size: 16px;
            color: #002855;
        }

        .upload-choice-buttons {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
        }

        .btn-upload-choice {
            flex: 1;
            padding: 16px;
            border-radius: 12px;
            border: 2px solid #002855;
            background: #f8fbff;
            color: #002855;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: 0.2s;
        }

        .btn-upload-choice:hover {
            background: #002855;
            color: white;
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            box-sizing: border-box;
            outline: none;
            font-size: 15px;
            background: #ffffff;
        }

        .form-control[readonly] {
            background-color: #f1f5f9;
            color: #475569;
        }

        textarea.form-control {
            height: 140px;
            resize: vertical;
        }

        .preview-area {
            display: none;
            position: relative;
            width: 100%;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            padding: 12px;
            box-sizing: border-box;
            margin-top: 14px;
        }

        .preview-area img, .preview-area video {
            width: 100%;
            max-height: 400px;
            object-fit: contain;
            border-radius: 8px;
            display: block;
        }

        .btn-hapus-preview {
            position: absolute;
            top: 18px;
            right: 18px;
            background: #dc3545;
            color: white;
            border: none;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            z-index: 10;
        }

        .pilihan-lokasi {
            display: flex;
            gap: 20px;
            margin-bottom: 16px;
            background: #f8fafc;
            padding: 16px 20px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .radio-lokasi {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            color: #334155;
        }

        .radio-lokasi input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #002855;
        }

        /* AUTOCOMPLETE SEARCH DROPDOWN NOMINATIM */
        .suggestions-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            max-height: 250px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .suggestion-item {
            padding: 12px 18px;
            font-size: 14px;
            color: #334155;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .suggestion-item:last-child { border-bottom: none; }
        .suggestion-item:hover { background: #eff6ff; color: #002855; font-weight: 600; }

        #map {
            height: 320px;
            width: 100%;
            border-radius: 12px;
            margin-top: 14px;
            border: 1px solid #cbd5e1;
            z-index: 1;
        }

        .map-status {
            font-size: 14px;
            color: #16a34a;
            font-weight: 700;
            display: block;
            margin-bottom: 10px;
        }

        .btn-submit {
            width: 100%;
            background: #002855;
            color: white;
            padding: 18px;
            border: none;
            border-radius: 12px;
            font-size: 17px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 4px 15px rgba(0, 40, 85, 0.2);
            margin-top: 15px;
        }

        .btn-submit:hover {
            background: #001a38;
        }

        .error-msg {
            color: #dc3545;
            font-size: 14px;
            margin-top: 8px;
            display: none;
            font-weight: 600;
        }

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
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* MODAL KAMERA WEBCAM */
        .camera-modal-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.92);
            backdrop-filter: blur(8px);
            z-index: 99998;
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 20px;
        }

        .camera-modal-overlay.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        #camera-video-preview {
            width: min(90vw, 640px);
            max-height: 70vh;
            border-radius: 16px;
            background: #000;
            box-shadow: 0 10px 40px rgba(0,0,0,0.6);
            object-fit: cover;
        }

        .camera-controls {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .btn-shutter {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: white;
            border: 4px solid rgba(255,255,255,0.5);
            cursor: pointer;
            box-shadow: 0 0 0 6px rgba(255,255,255,0.2);
            transition: 0.2s;
            font-size: 0;
        }

        .btn-shutter:hover {
            transform: scale(1.08);
            background: #e0f2fe;
        }

        .btn-camera-close {
            position: absolute;
            top: 24px; right: 28px;
            color: white;
            font-size: 42px;
            font-weight: bold;
            cursor: pointer;
            z-index: 99999;
            line-height: 1;
            transition: 0.2s;
        }

        .btn-camera-close:hover { color: #f87171; transform: scale(1.15); }

        .camera-tip {
            color: rgba(255,255,255,0.75);
            font-size: 14px;
            text-align: center;
        }

        .btn-camera-action {
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-switch-camera {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1.5px solid rgba(255,255,255,0.3);
        }

        .btn-switch-camera:hover { background: rgba(255,255,255,0.25); }

        /* MODAL POPUP SUKSES KIRIM LAPORAN */
        .modal-sukses-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0, 20, 50, 0.75);
            backdrop-filter: blur(6px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 99997;
        }

        .modal-sukses-box {
            background: #ffffff;
            padding: 44px 40px;
            border-radius: 20px;
            max-width: 440px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.25);
            animation: slideUp 0.35s ease-out;
        }

        .modal-sukses-icon {
            width: 88px;
            height: 88px;
            background: #dcfce7;
            color: #16a34a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 44px;
            margin: 0 auto 22px;
            animation: popIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) 0.15s both;
        }

        @keyframes popIn {
            from { transform: scale(0.5); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-sukses-box h3 {
            margin: 0 0 10px;
            color: #002855;
            font-size: 26px;
            font-weight: 800;
        }

        .modal-sukses-box p {
            color: #64748b;
            font-size: 15px;
            margin: 0 0 30px;
            line-height: 1.65;
        }

        .btn-modal-sukses-lanjut {
            display: block;
            width: 100%;
            padding: 16px;
            background: #002855;
            color: white;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            box-sizing: border-box;
            transition: 0.2s;
        }

        .btn-modal-sukses-lanjut:hover { background: #001a3b; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

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
            .upload-choice-buttons { flex-direction: column; }
        }
    </style>
</head>

<body>
    <!-- MODAL POPUP SUKSES KIRIM LAPORAN -->
    <?php if ($modal_sukses_kirim): ?>
    <div class="modal-sukses-overlay">
        <div class="modal-sukses-box">
            <div class="modal-sukses-icon"><i class="fa-solid fa-circle-check"></i></div>
            <h3>Laporan Berhasil Dikirim!</h3>
            <p>Laporan pengaduan Anda telah berhasil kami terima dan akan segera diproses oleh petugas kelurahan.</p>
            <a href="historipengaduan.php" class="btn-modal-sukses-lanjut"><i class="fa-solid fa-clock-rotate-left"></i> Lihat Riwayat Laporan Saya</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- MODAL WEBCAM KAMERA -->
    <div class="camera-modal-overlay" id="camera-modal">
        <span class="btn-camera-close" onclick="tutupKamera()">&times;</span>
        <video id="camera-video-preview" autoplay playsinline muted></video>
        <div class="camera-controls">
            <button type="button" class="btn-camera-action btn-switch-camera" onclick="gantiKamera()"><i class="fa-solid fa-rotate"></i> Ganti Kamera</button>
            <button type="button" class="btn-shutter" id="btn-shutter" onclick="ambilFoto()" title="Ambil Foto"></button>
        </div>
        <div class="camera-tip"><i class="fa-regular fa-lightbulb"></i> Klik tombol putih untuk mengambil foto</div>
        <canvas id="camera-canvas" style="display:none;"></canvas>
    </div>

    <div class="loading-overlay" id="loading-overlay">
        <i class="fa-solid fa-circle-notch loading-spinner"></i>
        <div class="loading-text">Sedang Mengunggah Laporan...</div>
        <div style="font-size: 14px; color: #cbd5e1;">Mohon tunggu sebentar. File berukuran besar dapat memakan waktu.</div>
    </div>

    <div class="brand-header-centered">
        <a href="../index.php">
            <i class="fa-solid fa-shield-halved" style="color: #002855;"></i> SIPELDA
        </a>
    </div>
    <div class="brand-divider"></div>

    <div class="container">
        <div class="header-title">
            <h2>Buat Laporan Pengaduan Baru</h2>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" novalidate id="form-laporan">
            
            <!-- 1. UNGGAH BUKTI KEJADIAN (FOTO MAKSIMAL 10MB) -->
            <div class="form-group">
                <label class="main-label">1. Unggah Bukti Kejadian (Foto maks. 10MB) <span style="color:red;">*</span></label>
                
                <div class="upload-choice-buttons">
                    <button type="button" class="btn-upload-choice" onclick="bukaKamera()">
                        <i class="fa-solid fa-camera" style="font-size: 20px;"></i> Kamera
                    </button>
                    <button type="button" class="btn-upload-choice" onclick="document.getElementById('input-file').click()">
                        <i class="fa-solid fa-folder-open" style="font-size: 20px;"></i> Galeri / File
                    </button>
                </div>

                <!-- INPUT HIDDEN KAMERA (backup untuk device mobile) -->
                <input type="file" id="input-camera" accept="image/*" capture="environment" style="display:none;" onchange="handleBuktiUpload(this)">
                <!-- INPUT HIDDEN GALERI / FILE (utama untuk submit form) -->
                <input type="file" name="foto" id="input-file" accept="image/png,image/jpeg,image/jpg,image/webp" style="display:none;" onchange="handleBuktiUpload(this)">

                <div class="preview-area" id="preview-wrapper">
                    <div id="preview-container"></div>
                    <button type="button" class="btn-hapus-preview" id="btn-hapus-preview"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="error-msg" id="err-foto"><i class="fa-solid fa-circle-exclamation"></i> Harap unggah Bukti Kejadian (Foto/Video)!</div>
            </div>

            <!-- 2. KATEGORI MASALAH -->
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
                    <option value="Kedaruratan & Bencana">Kedaruratan & Bencana (Banjir/Pohon Tumbang)</option>
                    <option value="Fasilitas Umum">Fasilitas Umum</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
                <div class="error-msg" id="err-kategori"><i class="fa-solid fa-circle-exclamation"></i> Harap pilih Kategori Masalah!</div>
            </div>

            <!-- 3. LOKASI KEJADIAN DENGAN FIX AUTOCOMPLETE LOKASI MANUAL -->
            <div class="form-group">
                <label class="main-label">3. Lokasi Kejadian <span style="color:red;">*</span></label>

                <div class="pilihan-lokasi">
                    <label class="radio-lokasi">
                        <input type="radio" name="mode_lokasi" value="gps" checked onchange="toggleModeLokasi('gps')">
                        <span><i class="fa-solid fa-location-crosshairs"></i> Lokasi GPS Saat Ini</span>
                    </label>
                    <label class="radio-lokasi">
                        <input type="radio" name="mode_lokasi" value="manual" onchange="toggleModeLokasi('manual')">
                        <span><i class="fa-solid fa-pen-to-square"></i> Cari Lokasi Manual</span>
                    </label>
                </div>

                <span id="map-status" class="map-status"><i class="fa-solid fa-spinner fa-spin"></i> Mendeteksi lokasi GPS...</span>

                <div style="position: relative;">
                    <input type="text" name="lokasi" id="input-lokasi" class="form-control" placeholder="Mencari alamat lokasi saat ini..." readonly autocomplete="off">
                    <div id="suggestions-list" class="suggestions-dropdown"></div>
                </div>

                <div id="map"></div>
                <input type="hidden" name="latitude" id="lat"><input type="hidden" name="longitude" id="lng">

                <div class="error-msg" id="err-lokasi"><i class="fa-solid fa-circle-exclamation"></i> Harap pastikan lokasi kejadian telah diisi!</div>
            </div>

            <!-- 4. DESKRIPSI LENGKAP -->
            <div class="form-group">
                <label class="main-label">4. Deskripsi Lengkap <span style="color:red;">*</span></label>
                <textarea name="isi_laporan" id="input-deskripsi" class="form-control" placeholder="Ceritakan kronologi kejadian secara rinci..."></textarea>
                <div class="error-msg" id="err-deskripsi"><i class="fa-solid fa-circle-exclamation"></i> Harap isi deskripsi kejadian!</div>
            </div>

            <!-- 5. SIFAT LAPORAN -->
            <div class="form-group">
                <label class="main-label">5. Sifat Laporan <span style="color:red;">*</span></label>
                <select name="privasi" class="form-control">
                    <option value="publik"> Publik (Semua warga dapat melihat laporan ini)</option>
                    <option value="privat"> Privat (Hanya Admin yang dapat mengakses laporan ini)</option>
                </select>
            </div>

            <button type="submit" name="kirim_pengaduan" id="btn-submit" class="btn-submit"><i class="fa-solid fa-paper-plane"></i> Kirim Laporan Sekarang</button>
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
        const inputCamera = document.getElementById('input-camera');
        const inputFile   = document.getElementById('input-file');
        const previewWrapper   = document.getElementById('preview-wrapper');
        const previewContainer = document.getElementById('preview-container');

        // ======== MODUL WEBCAM / KAMERA ========
        let currentStream = null;
        let facingMode = 'environment'; // Kamera belakang default

        async function bukaKamera() {
            const modal = document.getElementById('camera-modal');
            const video = document.getElementById('camera-video-preview');

            try {
                if (currentStream) {
                    currentStream.getTracks().forEach(t => t.stop());
                }
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: facingMode, width: { ideal: 1280 }, height: { ideal: 720 } },
                    audio: false
                });
                currentStream = stream;
                video.srcObject = stream;
                modal.classList.add('active');
            } catch (err) {
                // Fallback ke input file capture jika getUserMedia tidak tersedia
                alert('Webcam tidak tersedia di browser ini. Menggunakan kamera bawaan perangkat...');
                inputCamera.click();
            }
        }

        function gantiKamera() {
            facingMode = (facingMode === 'environment') ? 'user' : 'environment';
            bukaKamera();
        }

        function tutupKamera() {
            const modal = document.getElementById('camera-modal');
            const video = document.getElementById('camera-video-preview');
            if (currentStream) {
                currentStream.getTracks().forEach(t => t.stop());
                currentStream = null;
            }
            video.srcObject = null;
            modal.classList.remove('active');
        }

        function ambilFoto() {
            const video  = document.getElementById('camera-video-preview');
            const canvas = document.getElementById('camera-canvas');
            canvas.width  = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            canvas.toBlob(blob => {
                const fotoFile = new File([blob], 'webcam_' + Date.now() + '.jpg', { type: 'image/jpeg' });

                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(fotoFile);
                inputFile.files = dataTransfer.files;

                const fileURL = URL.createObjectURL(blob);
                previewContainer.innerHTML = '';
                const imgEl = document.createElement('img');
                imgEl.src = fileURL;
                imgEl.style.maxWidth = '100%';
                imgEl.style.maxHeight = '400px';
                previewContainer.appendChild(imgEl);

                previewWrapper.style.display = 'block';
                document.getElementById('err-foto').style.display = 'none';

                tutupKamera();
            }, 'image/jpeg', 0.92);
        }
        // ======== AKHIR MODUL WEBCAM ========

        function handleBuktiUpload(inputEl) {
            if (inputEl.files && inputEl.files[0]) {
                const file = inputEl.files[0];
                const maxBytes = 10485760; // 10MB

                if (file.size > maxBytes) {
                    alert('Ukuran file melebihi batas maksimal 10 MB!');
                    inputEl.value = "";
                    return;
                }

                // Jika diunggah via input-camera, transfer file-nya ke input-file utama agar terikut saat submit form
                if (inputEl.id === 'input-camera') {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    inputFile.files = dataTransfer.files;
                }

                const fileURL = URL.createObjectURL(file);
                previewContainer.innerHTML = '';

                const imgEl = document.createElement('img');
                imgEl.src = fileURL;
                imgEl.style.maxWidth = '100%';
                imgEl.style.maxHeight = '400px';
                previewContainer.appendChild(imgEl);

                previewWrapper.style.display = 'block';
                document.getElementById('err-foto').style.display = 'none';
            }
        }

        document.getElementById('btn-hapus-preview').addEventListener('click', () => {
            inputCamera.value = "";
            inputFile.value = "";
            previewContainer.innerHTML = "";
            previewWrapper.style.display = 'none';
        });

        // LEAFLET MAP & GEOCODING AUTOCOMPLETE SEARCH
        const map = L.map('map').setView([-7.250445, 112.768845], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        const marker = L.marker([-7.250445, 112.768845], { draggable: true }).addTo(map);

        const inputLokasi = document.getElementById('input-lokasi');
        const mapStatus   = document.getElementById('map-status');
        const suggestionsDropdown = document.getElementById('suggestions-list');

        function setKoordinatDanNamaJalan(lat, lng) {
            document.getElementById('lat').value = lat;
            document.getElementById('lng').value = lng;
            map.setView([lat, lng], 17);
            marker.setLatLng([lat, lng]);

            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(res => res.json())
                .then(data => {
                    if (data.display_name) {
                        inputLokasi.value = data.display_name;
                        document.getElementById('err-lokasi').style.display = 'none';
                    }
                });
        }

        function detectUserLocation() {
            mapStatus.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mendeteksi lokasi GPS...';
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(pos => {
                    setKoordinatDanNamaJalan(pos.coords.latitude, pos.coords.longitude);
                    mapStatus.innerHTML = '<i class="fa-solid fa-location-dot"></i> Lokasi GPS berhasil ditemukan.';
                }, () => {
                    mapStatus.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Gagal melacak GPS. Silakan geser pin peta atau cari lokasi manual.';
                });
            }
        }

        detectUserLocation();

        marker.on('dragend', () => {
            const pos = marker.getLatLng();
            setKoordinatDanNamaJalan(pos.lat, pos.lng);
        });

        function toggleModeLokasi(mode) {
            if (mode === 'gps') {
                inputLokasi.readOnly = true;
                inputLokasi.placeholder = "Mencari alamat lokasi saat ini...";
                suggestionsDropdown.style.display = 'none';
                detectUserLocation();
            } else {
                inputLokasi.readOnly = false;
                inputLokasi.placeholder = "Ketik nama lokasi/stasiun/jalan di sini...";
                inputLokasi.value = "";
                mapStatus.innerHTML = '<i class="fa-solid fa-keyboard"></i> Ketik nama lokasi manual untuk mencari rekomendasi tempat.';
                inputLokasi.focus();
            }
        }

        // LIVE AUTOCOMPLETE NOMINATIM OPENSTREETMAP API SEARCH
        let debounceTimer;
        inputLokasi.addEventListener('input', function() {
            const query = this.value.trim();
            clearTimeout(debounceTimer);

            if (this.readOnly || query.length < 3) {
                suggestionsDropdown.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=id&limit=6`)
                    .then(res => res.json())
                    .then(data => {
                        suggestionsDropdown.innerHTML = '';
                        if (data && data.length > 0) {
                            data.forEach(item => {
                                const div = document.createElement('div');
                                div.className = 'suggestion-item';
                                div.innerHTML = `<i class="fa-solid fa-location-dot" style="color:#dc3545;"></i> <span>${item.display_name}</span>`;
                                div.addEventListener('click', () => {
                                    inputLokasi.value = item.display_name;
                                    document.getElementById('lat').value = item.lat;
                                    document.getElementById('lng').value = item.lon;
                                    map.setView([item.lat, item.lon], 17);
                                    marker.setLatLng([item.lat, item.lon]);
                                    suggestionsDropdown.style.display = 'none';
                                    document.getElementById('err-lokasi').style.display = 'none';
                                    mapStatus.innerHTML = '<i class="fa-solid fa-circle-check" style="color:#16a34a;"></i> Lokasi manual berhasil dipilih.';
                                });
                                suggestionsDropdown.appendChild(div);
                            });
                            suggestionsDropdown.style.display = 'block';
                        } else {
                            suggestionsDropdown.style.display = 'none';
                        }
                    });
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (!inputLokasi.contains(e.target) && !suggestionsDropdown.contains(e.target)) {
                suggestionsDropdown.style.display = 'none';
            }
        });

        document.getElementById('form-laporan').addEventListener('submit', function(e) {
            let isValid = true;

            // Cek foto
            const hasFoto = inputFile.files.length > 0;
            if (!hasFoto) {
                document.getElementById('err-foto').style.display = 'block';
                document.getElementById('err-foto').innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Bukti foto wajib diunggah!';
                isValid = false;
            } else {
                document.getElementById('err-foto').style.display = 'none';
            }

            // Cek kategori
            if (document.getElementById('input-kategori').value === '') {
                document.getElementById('err-kategori').style.display = 'block';
                isValid = false;
            } else {
                document.getElementById('err-kategori').style.display = 'none';
            }

            // Cek lokasi teks
            if (inputLokasi.value.trim() === '') {
                document.getElementById('err-lokasi').style.display = 'block';
                document.getElementById('err-lokasi').innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Harap isi atau pilih lokasi kejadian!';
                isValid = false;
            } else {
                document.getElementById('err-lokasi').style.display = 'none';
            }

            // Cek koordinat GPS (lat/lng wajib ada)
            const latVal = document.getElementById('lat').value.trim();
            const lngVal = document.getElementById('lng').value.trim();
            if (!latVal || !lngVal) {
                document.getElementById('err-lokasi').style.display = 'block';
                document.getElementById('err-lokasi').innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Titik lokasi di peta belum ditentukan. Aktifkan GPS atau pilih lokasi manual.';
                isValid = false;
            }

            // Cek deskripsi
            if (document.getElementById('input-deskripsi').value.trim() === '') {
                document.getElementById('err-deskripsi').style.display = 'block';
                isValid = false;
            } else {
                document.getElementById('err-deskripsi').style.display = 'none';
            }

            if (!isValid) {
                e.preventDefault();
                // Scroll ke elemen error pertama
                const firstErr = document.querySelector('.error-msg[style*="block"]');
                if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                document.getElementById('loading-overlay').style.display = 'flex';
            }
        });

    </script>
</body>

</html>
