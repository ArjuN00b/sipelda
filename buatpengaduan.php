<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || $_SESSION['role'] !== 'masyarakat') {
    header("Location: login.php");
    exit;
}

$foto_profil_nav = "";
$id_user_nav = $_SESSION['id_user'];
$q_nav = mysqli_query($koneksi, "SELECT foto_profil FROM users WHERE id_user = '$id_user_nav'");
if ($q_nav && mysqli_num_rows($q_nav) > 0) {
    $foto_profil_nav = mysqli_fetch_assoc($q_nav)['foto_profil'];
}

if (isset($_POST['kirim_pengaduan'])) {
    $id_user       = $_SESSION['id_user'];
    $kategori      = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $lokasi        = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $deskripsi     = mysqli_real_escape_string($koneksi, $_POST['isi_laporan']);
    $lat           = mysqli_real_escape_string($koneksi, $_POST['latitude']);
    $lng           = mysqli_real_escape_string($koneksi, $_POST['longitude']);
    $is_anonim     = isset($_POST['anonim']) ? true : false;
    $sifat_laporan = $_POST['privasi'];

    $judul_laporan = $kategori . " - " . $lokasi;
    if ($is_anonim) {
        $judul_laporan .= " [ANONIM]";
    }
    if ($sifat_laporan === 'privat') {
        $judul_laporan .= " [PRIVAT]";
    }

    $link_maps = "http://maps.google.com/?q=" . $lat . "," . $lng;
    $isi_laporan_lengkap = $deskripsi . "\n\n Titik Koordinat Peta:\n" . $link_maps;

    $nama_foto = "";

    // Validasi PHP (Server-side) tetap dipertahankan untuk keamanan ganda
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $ext_diizinkan = array('png', 'jpg', 'jpeg');
        $nama_file     = $_FILES['foto']['name'];
        $x             = explode('.', $nama_file);
        $ekstensi      = strtolower(end($x));
        $ukuran        = $_FILES['foto']['size'];
        $file_tmp      = $_FILES['foto']['tmp_name'];

        if (in_array($ekstensi, $ext_diizinkan) && $ukuran < 2048000) { 
            $nama_foto = time() . '_img_' . preg_replace("/[^a-zA-Z0-9.]/", "", $nama_file);
            move_uploaded_file($file_tmp, 'uploads/' . $nama_foto);
        } else {
            echo "<script>alert('Gagal! Format foto tidak valid atau ukuran lebih dari 2MB.'); window.history.back();</script>";
            exit; // Menghentikan proses eksekusi jika file tidak valid
        }
    }

    $query = "INSERT INTO pengaduan (id_user, judul_laporan, isi_laporan, foto, status) 
              VALUES ('$id_user', '$judul_laporan', '$isi_laporan_lengkap', '$nama_foto', 'menunggu')";

    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Laporan berhasil dikirim!'); window.location.href='historipengaduan.php';</script>";
    } else {
        echo "<script>alert('Gagal mengirim laporan!');</script>";
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
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fb;
            margin: 0;
            color: #333;
        }

        .navbar {
            background-color: #002855;
            color: white;
            padding: 25px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar .logo {
            font-size: 26px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }

        .nav-center {
            display: flex;
            gap: 40px;
        }

        .nav-center a {
            color: #a9b9cc;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            transition: 0.3s;
        }

        .nav-center a:hover {
            color: white;
        }

        .user-profile-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 20px;
            border-radius: 30px;
            text-decoration: none;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .nav-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.8);
        }

        .container {
            max-width: 750px;
            margin: 40px auto;
            padding: 40px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .header-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .header-title h2 {
            color: #002855;
            margin: 0 0 10px;
            font-size: 28px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label.main-label {
            display: block;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #dcdcdc;
            border-radius: 6px;
            box-sizing: border-box;
            outline: none;
            font-size: 14px;
        }

        .form-control[readonly] {
            background-color: #f1f5f9;
            color: #64748b;
            cursor: not-allowed;
        }

        textarea.form-control {
            height: 120px;
            resize: vertical;
        }

        .file-upload-wrapper {
            border: 2px dashed #002855;
            padding: 40px 20px;
            text-align: center;
            border-radius: 8px;
            background: #f8fbff;
            cursor: pointer;
            transition: 0.3s;
        }

        .file-upload-wrapper:hover {
            background: #eef5ff;
        }

        .preview-area {
            display: none;
            position: relative;
            width: 100%;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            padding: 10px;
            box-sizing: border-box;
        }

        .preview-area img {
            width: 100%;
            max-height: 400px;
            object-fit: contain;
            border-radius: 6px;
            display: block;
        }

        .btn-hapus-preview {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #dc3545;
            color: white;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        /* Styling Pilihan Lokasi (Radio) */
        .pilihan-lokasi {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .radio-lokasi {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #334155;
        }

        .radio-lokasi input[type="radio"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #002855;
        }

        #map {
            height: 300px;
            width: 100%;
            border-radius: 8px;
            margin-top: 10px;
            border: 1px solid #dcdcdc;
            z-index: 1;
        }

        .map-status {
            font-size: 13px;
            color: #198754;
            font-weight: bold;
            display: block;
            margin-bottom: 10px;
        }

        .search-results {
            position: absolute;
            top: 48px; 
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #dcdcdc;
            border-radius: 6px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .search-results div {
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        .search-results div:hover {
            background: #f4f7fb;
            color: #002855;
        }

        .search-results div:last-child {
            border-bottom: none;
        }

        .checkbox-area {
            background: #fef3c7;
            padding: 15px 20px;
            border-radius: 6px;
            border: 1px solid #fde68a;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
            font-size: 14px;
            font-weight: 500;
        }

        .btn-submit {
            width: 100%;
            background: #002855;
            color: white;
            padding: 15px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .btn-submit:hover {
            background: #001a38;
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
        <div>
            <a href="profil.php" class="user-profile-btn">
                <span>Halo, <?= htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                <?php if (!empty($foto_profil_nav) && file_exists('uploads/' . $foto_profil_nav)): ?>
                    <img src="uploads/<?= $foto_profil_nav ?>" class="nav-avatar">
                <?php else: ?>
                    <i class="fa-solid fa-circle-user" style="font-size: 24px; color: #cbd5e1;"></i>
                <?php endif; ?>
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="header-title">
            <h2>Buat Laporan Baru</h2>
        </div>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="main-label">1. Unggah Foto Bukti Kejadian <span style="color:red;">*</span></label>
                <div class="file-upload-wrapper" id="upload-wrapper" onclick="document.getElementById('input-foto').click()">
                    <i class="fa-solid fa-cloud-arrow-up" style="font-size:40px; color:#002855; display:block; margin-bottom:15px;"></i>
                    <strong style="color:#002855; font-size: 16px;">Klik di sini untuk memilih foto</strong>
                    <p style="margin: 5px 0 0; color: #64748b; font-size: 13px;">Format yang diizinkan: JPG, JPEG, PNG (Maks 2MB)</p>
                    <input type="file" name="foto" id="input-foto" accept="image/png, image/jpeg, image/jpg" style="display: none;" required>
                </div>
                <div class="preview-area" id="preview-wrapper">
                    <img id="preview-image" src="#" alt="Preview Gambar">
                    <button type="button" class="btn-hapus-preview" id="btn-hapus-preview" title="Hapus Foto"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>

            <div class="form-group">
                <label class="main-label">2. Kategori Masalah <span style="color:red;">*</span></label>
                <select name="kategori" class="form-control" required>
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
                    <input type="text" name="lokasi" id="input-lokasi" class="form-control" placeholder="Mencari alamat lokasi saat ini..." readonly required autocomplete="off">
                    <div id="search-results" class="search-results"></div>
                </div>

                <div id="map"></div>
                <input type="hidden" name="latitude" id="lat" required>
                <input type="hidden" name="longitude" id="lng" required>
            </div>

            <div class="form-group">
                <label class="main-label">4. Deskripsi Lengkap <span style="color:red;">*</span></label>
                <textarea name="isi_laporan" class="form-control" placeholder="Ceritakan kronologi secara lengkap..." required></textarea>
            </div>

            <div class="form-group">
                <label class="main-label">5. Sifat Laporan <span style="color:red;">*</span></label>
                <select name="privasi" class="form-control" required>
                    <option value="publik"> Publik (Semua warga dapat melihat laporan ini)</option>
                    <option value="privat"> Privat (Hanya Admin yang dapat mengakses laporan ini)</option>
                </select>
            </div>

            <div class="checkbox-area">
                <input type="checkbox" id="anonim" name="anonim" value="1" style="width: 18px; height: 18px;">
                <label for="anonim">Sembunyikan nama asli saya di halaman publik (Anonim)</label>
            </div>

            <button type="submit" name="kirim_pengaduan" class="btn-submit">Kirim Laporan Sekarang</button>
        </form>
    </div>

    <script>
        // ==========================================
        // 1. PREVIEW & VALIDASI FOTO UPLOAD
        // ==========================================
        const inputFoto = document.getElementById('input-foto');
        const uploadWrapper = document.getElementById('upload-wrapper');
        const previewWrapper = document.getElementById('preview-wrapper');
        const previewImage = document.getElementById('preview-image');
        const btnHapusPreview = document.getElementById('btn-hapus-preview');

        inputFoto.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                const maxSize = 2048000; // 2MB dalam bytes

                // 1. Validasi Tipe File (Mencegah upload PDF/Word dll)
                if (!allowedTypes.includes(file.type)) {
                    alert('Format foto ditolak! Harap unggah file berupa gambar (JPG, JPEG, atau PNG).');
                    this.value = ""; // Kosongkan input
                    return; // Hentikan proses
                }

                // 2. Validasi Ukuran File (Maksimal 2MB)
                if (file.size > maxSize) {
                    alert('Ukuran foto terlalu besar! Maksimal ukuran file adalah 2MB.');
                    this.value = ""; // Kosongkan input
                    return; // Hentikan proses
                }

                // Jika lolos validasi, tampilkan preview gambar
                const reader = new FileReader();
                reader.onload = function(event) {
                    previewImage.src = event.target.result;
                    uploadWrapper.style.display = 'none';
                    previewWrapper.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        btnHapusPreview.addEventListener('click', function() {
            inputFoto.value = "";
            previewImage.src = "#";
            previewWrapper.style.display = 'none';
            uploadWrapper.style.display = 'block';
        });

        // ==========================================
        // 2. LEAFLET MAPS & MODE LOKASI
        // ==========================================
        var latAwal = -7.250445; 
        var lngAwal = 112.768845;
        
        var map = L.map('map').setView([latAwal, lngAwal], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19
        }).addTo(map);
        
        var marker = L.marker([latAwal, lngAwal], { draggable: true }).addTo(map);
        document.getElementById('lat').value = latAwal;
        document.getElementById('lng').value = lngAwal;

        const mapStatus = document.getElementById('map-status');
        const inputLokasi = document.getElementById('input-lokasi');
        const searchResults = document.getElementById('search-results');
        const radioModeLokasi = document.querySelectorAll('input[name="mode_lokasi"]');
        let searchTimeout;

        // Fungsi Reverse Geocoding (Titik ke Teks Alamat)
        function getAddressFromCoords(lat, lng) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.display_name) {
                        inputLokasi.value = data.display_name;
                    }
                })
                .catch(err => console.log('Gagal mengambil nama jalan:', err));
        }

        // Fungsi Tarik Lokasi GPS
        function detectUserLocation() {
            mapStatus.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mendeteksi lokasi GPS...';
            mapStatus.style.color = "#002855";
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var userLat = position.coords.latitude;
                    var userLng = position.coords.longitude;
                    
                    map.setView([userLat, userLng], 17);
                    marker.setLatLng([userLat, userLng]);
                    
                    document.getElementById('lat').value = userLat;
                    document.getElementById('lng').value = userLng;
                    
                    mapStatus.innerHTML = '<i class="fa-solid fa-location-dot"></i> Lokasi GPS Anda berhasil ditemukan.';
                    mapStatus.style.color = "#198754";
                    
                    getAddressFromCoords(userLat, userLng);
                }, function(error) {
                    mapStatus.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Gagal mendeteksi GPS. Silakan gunakan mode manual.';
                    mapStatus.style.color = "#dc3545";
                }, 
                {
                    enableHighAccuracy: true,
                    maximumAge: 0
                });
            }
        }

        // Auto-detect saat web pertama dimuat
        detectUserLocation();

        // Ganti Mode Lokasi (Radio Button Event)
        radioModeLokasi.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'gps') {
                    // Mode GPS: Kunci input teks, sembunyikan dropdown, cari GPS lagi
                    inputLokasi.readOnly = true;
                    inputLokasi.placeholder = "Mencari alamat lokasi saat ini...";
                    searchResults.style.display = 'none';
                    detectUserLocation();
                } else {
                    // Mode Manual: Buka kunci input teks, izinkan pengetikan
                    inputLokasi.readOnly = false;
                    inputLokasi.placeholder = "Ketikkan nama jalan atau lokasi kejadian...";
                    mapStatus.innerHTML = '<i class="fa-solid fa-pen-to-square"></i>Silakan ketik alamat atau geser pin peta.';
                    mapStatus.style.color = "#002855";
                    inputLokasi.focus(); // Langsung arahkan kursor ke input
                }
            });
        });

        // Event saat pengguna menggeser pin merah secara manual
        marker.on('dragstart', function() {
            // Jika pin digeser, otomatis ubah mode ke "Manual"
            document.querySelector('input[value="manual"]').checked = true;
            inputLokasi.readOnly = false;
            mapStatus.innerHTML = '<i class="fa-solid fa-map-pin"></i> Pin digeser secara manual.';
            mapStatus.style.color = "#002855";
        });

        marker.on('dragend', function(e) {
            const position = marker.getLatLng();
            document.getElementById('lat').value = position.lat;
            document.getElementById('lng').value = position.lng;
            getAddressFromCoords(position.lat, position.lng);
        });

        // Fitur Autocomplete Pencarian Alamat saat user mengetik (Hanya di mode Manual)
        inputLokasi.addEventListener('input', function() {
            if (document.querySelector('input[name="mode_lokasi"]:checked').value === 'gps') {
                return; // Jangan cari kalau mode GPS (seharusnya readonly, tapi sbg antisipasi)
            }

            clearTimeout(searchTimeout);
            const query = this.value;

            if (query.length < 3) {
                searchResults.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}&limit=5&countrycodes=id`)
                    .then(response => response.json())
                    .then(data => {
                        searchResults.innerHTML = '';
                        if (data.length > 0) {
                            searchResults.style.display = 'block';
                            data.forEach(item => {
                                const div = document.createElement('div');
                                div.textContent = item.display_name;
                                
                                div.onclick = function() {
                                    const lat = parseFloat(item.lat);
                                    const lon = parseFloat(item.lon);
                                    
                                    map.setView([lat, lon], 17);
                                    marker.setLatLng([lat, lon]);
                                    
                                    document.getElementById('lat').value = lat;
                                    document.getElementById('lng').value = lon;
                                    inputLokasi.value = item.display_name;
                                    
                                    searchResults.style.display = 'none';
                                };
                                searchResults.appendChild(div);
                            });
                        } else {
                            searchResults.style.display = 'none';
                        }
                    });
            }, 600); 
        });

        // Sembunyikan dropdown hasil pencarian jika klik di luar
        document.addEventListener('click', function(e) {
            if (e.target !== inputLokasi && e.target !== searchResults) {
                searchResults.style.display = 'none';
            }
        });
    </script>
</body>
</html>
