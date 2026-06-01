<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || $_SESSION['role'] !== 'masyarakat') {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$foto_profil_nav = "";
$q_nav = mysqli_query($koneksi, "SELECT foto_profil FROM users WHERE id_user = '$id_user'");
if ($q_nav && mysqli_num_rows($q_nav) > 0) {
    $foto_profil_nav = mysqli_fetch_assoc($q_nav)['foto_profil'];
}

if (isset($_POST['kirim_pengaduan'])) {
    $kategori  = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $lokasi    = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['isi_laporan']);
    $lat       = mysqli_real_escape_string($koneksi, $_POST['latitude']);
    $lng       = mysqli_real_escape_string($koneksi, $_POST['longitude']);
    
    // Format Judul
    $judul_laporan = $kategori . " - " . $lokasi;
    $judul_laporan .= isset($_POST['anonim']) ? " [ANONIM]" : "";
    $judul_laporan .= ($_POST['privasi'] === 'privat') ? " [PRIVAT]" : "";

    $isi_laporan_lengkap = $deskripsi . "\n\n Titik Koordinat Peta:\nhttp://maps.google.com/?q=" . $lat . "," . $lng;
    $nama_foto = "";

    // Upload Foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $nama_file = $_FILES['foto']['name'];
        $ekstensi  = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));

        if (in_array($ekstensi, ['png', 'jpg', 'jpeg']) && $_FILES['foto']['size'] < 2048000) { 
            $nama_foto = time() . '_img_' . preg_replace("/[^a-zA-Z0-9.]/", "", $nama_file);
            move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $nama_foto);
        } else {
            echo "<script>alert('Gagal! Format foto tidak valid atau ukuran lebih dari 2MB.'); window.history.back();</script>";
            exit;
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
        /* CSS Disederhanakan & Rapi */
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #f4f7fb; margin: 0; color: #333; }
        
        .navbar { background-color: #002855; color: white; padding: 25px 60px; display: flex; justify-content: space-between; align-items: center; }
        .navbar .logo { font-size: 26px; font-weight: bold; color: white; text-decoration: none; }
        .nav-center { display: flex; gap: 40px; }
        .nav-center a { color: #a9b9cc; text-decoration: none; font-size: 16px; font-weight: 500; transition: 0.3s; }
        .nav-center a:hover { color: white; }
        .user-profile-btn { display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.1); padding: 8px 20px; border-radius: 30px; text-decoration: none; color: white; border: 1px solid rgba(255,255,255,0.2); }
        .nav-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; }
        
        .container { max-width: 750px; margin: 40px auto; padding: 40px; background: #fff; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header-title { text-align: center; margin-bottom: 30px; }
        .header-title h2 { color: #002855; margin: 0 0 10px; font-size: 28px; }
        
        .form-group { margin-bottom: 25px; position: relative; }
        .form-group label.main-label { display: block; font-weight: bold; margin-bottom: 10px; font-size: 14px; }
        .form-control { width: 100%; padding: 12px 15px; border: 1px solid #dcdcdc; border-radius: 6px; box-sizing: border-box; outline: none; font-size: 14px; }
        .form-control[readonly] { background-color: #f1f5f9; color: #64748b; cursor: not-allowed; }
        textarea.form-control { height: 120px; resize: vertical; }
        
        .file-upload-wrapper { border: 2px dashed #002855; padding: 40px 20px; text-align: center; border-radius: 8px; background: #f8fbff; cursor: pointer; transition: 0.3s; }
        .file-upload-wrapper:hover { background: #eef5ff; }
        .preview-area { display: none; position: relative; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; padding: 10px; box-sizing: border-box; }
        .preview-area img { width: 100%; max-height: 400px; object-fit: contain; border-radius: 6px; display: block; }
        .btn-hapus-preview { position: absolute; top: 15px; right: 15px; background: #dc3545; color: white; border: none; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.3); }
        
        /* CSS Pilihan Lokasi & Maps */
        .pilihan-lokasi { display: flex; gap: 20px; margin-bottom: 15px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; }
        .radio-lokasi { display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; font-weight: 500; color: #334155; }
        .radio-lokasi input[type="radio"] { width: 18px; height: 18px; cursor: pointer; accent-color: #002855; }
        #map { height: 300px; width: 100%; border-radius: 8px; margin-top: 10px; border: 1px solid #dcdcdc; z-index: 1; }
        .map-status { font-size: 13px; color: #198754; font-weight: bold; display: block; margin-bottom: 10px; }
        
        /* CSS Autocomplete Search */
        .search-results { position: absolute; top: 48px; left: 0; right: 0; background: white; border: 1px solid #dcdcdc; border-radius: 6px; max-height: 200px; overflow-y: auto; z-index: 1000; display: none; box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
        .search-results div { padding: 12px 15px; cursor: pointer; border-bottom: 1px solid #eee; font-size: 13px; }
        .search-results div:hover { background: #f4f7fb; color: #002855; }
        .search-results div:last-child { border-bottom: none; }
        
        .checkbox-area { background: #fef3c7; padding: 15px 20px; border-radius: 6px; border: 1px solid #fde68a; display: flex; align-items: center; gap: 10px; margin-bottom: 30px; font-size: 14px; font-weight: 500; }
        .btn-submit { width: 100%; background: #002855; color: white; padding: 15px; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background: #001a38; }
        
        .error-msg { color: #dc3545; font-size: 13px; margin-top: 8px; display: none; font-weight: 500; }
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
        <div class="header-title"><h2>Buat Laporan Baru</h2></div>

        <form action="" method="POST" enctype="multipart/form-data" novalidate id="form-laporan">
            <div class="form-group">
                <label class="main-label">1. Unggah Foto Bukti Kejadian <span style="color:red;">*</span></label>
                <div class="file-upload-wrapper" id="upload-wrapper" onclick="document.getElementById('input-foto').click()">
                    <i class="fa-solid fa-cloud-arrow-up" style="font-size:40px; color:#002855; display:block; margin-bottom:15px;"></i>
                    <strong style="color:#002855; font-size: 16px;">Klik di sini untuk memilih foto</strong>
                    <p style="margin: 5px 0 0; color: #64748b; font-size: 13px;">Format: JPG, JPEG, PNG (Maks 2MB)</p>
                    <input type="file" name="foto" id="input-foto" accept="image/png, image/jpeg, image/jpg" style="display: none;">
                </div>
                <div class="preview-area" id="preview-wrapper">
                    <img id="preview-image" src="#" alt="Preview">
                    <button type="button" class="btn-hapus-preview" id="btn-hapus-preview"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="error-msg" id="err-foto"><i class="fa-solid fa-circle-exclamation"></i> Harap unggah Foto Bukti Kejadian!</div>
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
                    <option value="Kedaruratan & Bencana">Kedaruratan & Bencana (Banjir/Pohon Tumbang)</option>
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
                
                <div class="error-msg" id="err-lokasi"><i class="fa-solid fa-circle-exclamation"></i> Harap pastikan lokasi kejadian telah diisi!</div>
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

            <button type="submit" name="kirim_pengaduan" class="btn-submit">Kirim Laporan Sekarang</button>
        </form>
    </div>

    <script>
        // --- 1. UPLOAD FOTO ---
        const inputFoto = document.getElementById('input-foto');
        const previewImage = document.getElementById('preview-image');
        
        inputFoto.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                if (!['image/jpeg', 'image/jpg', 'image/png'].includes(file.type) || file.size > 2048000) {
                    alert('Format foto ditolak atau ukuran lebih dari 2MB!');
                    this.value = ""; return;
                }
                const reader = new FileReader();
                reader.onload = e => { 
                    previewImage.src = e.target.result; 
                    document.getElementById('upload-wrapper').style.display = 'none'; 
                    document.getElementById('preview-wrapper').style.display = 'block'; 
                    document.getElementById('err-foto').style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('btn-hapus-preview').addEventListener('click', () => {
            inputFoto.value = ""; previewImage.src = "#";
            document.getElementById('preview-wrapper').style.display = 'none'; 
            document.getElementById('upload-wrapper').style.display = 'block';
        });

        // --- 2. MAPS, RADIO BUTTON, & AUTOCOMPLETE ---
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

        detectUserLocation(); // Panggil saat pertama kali dimuat

        // Logika Radio Button
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
                    mapStatus.innerHTML = '<i class="fa-solid fa-pen-to-square"></i>Silakan ketik alamat atau geser pin peta.';
                    mapStatus.style.color = "#002855";
                    inputLokasi.focus();
                }
            });
        });

        // Logika Drag Pin (Otomatis pindah ke mode manual)
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

        // Logika Autocomplete Pencarian
        inputLokasi.addEventListener('input', function() {
            if (document.querySelector('input[name="mode_lokasi"]:checked').value === 'gps') return;
            
            clearTimeout(searchTimeout);
            const query = this.value;
            
            if (query.length < 3) {
                searchResults.style.display = 'none';
                return;
            }

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
                        } else {
                            searchResults.style.display = 'none';
                        }
                    });
            }, 600);
        });

        // Sembunyikan hasil pencarian jika klik di luar
        document.addEventListener('click', e => {
            if (e.target !== inputLokasi && e.target !== searchResults) {
                searchResults.style.display = 'none';
            }
        });

        // --- 3. VALIDASI FORM INLINE ---
        const valKategori = document.getElementById('input-kategori');
        const valDeskripsi = document.getElementById('input-deskripsi');

        valKategori.addEventListener('change', () => document.getElementById('err-kategori').style.display = 'none');
        inputLokasi.addEventListener('input', () => document.getElementById('err-lokasi').style.display = 'none');
        valDeskripsi.addEventListener('input', () => document.getElementById('err-deskripsi').style.display = 'none');

        document.getElementById('form-laporan').addEventListener('submit', function(e) {
            let isValid = true;
            document.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');

            if (inputFoto.files.length === 0) { document.getElementById('err-foto').style.display = 'block'; isValid = false; }
            if (valKategori.value === "") { document.getElementById('err-kategori').style.display = 'block'; isValid = false; }
            if (inputLokasi.value.trim() === "") { document.getElementById('err-lokasi').style.display = 'block'; isValid = false; }
            if (valDeskripsi.value.trim() === "") { document.getElementById('err-deskripsi').style.display = 'block'; isValid = false; }

            if (!isValid) {
                e.preventDefault();
                if (inputFoto.files.length === 0) window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    </script>
</body>
</html>
