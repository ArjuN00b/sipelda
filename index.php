<?php
session_start();
require 'koneksi.php';

// Ambil 3 Laporan Terbaru saja dari database
$query_publik = mysqli_query($koneksi, "
    SELECT p.*, u.nama_lengkap, t.isi_tanggapan 
    FROM pengaduan p 
    JOIN users u ON p.id_user = u.id_user 
    LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan 
    WHERE p.judul_laporan NOT LIKE '%[PRIVAT]%' 
    ORDER BY p.tgl_pengaduan DESC LIMIT 3
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPELDA - Beranda</title>
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
            margin: 0; 
            background-color: #f4f7fb; 
            color: #1e293b; 
            font-size: 16px;
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
            transition: transform 0.2s;
        }

        .brand-header-centered a:hover { transform: scale(1.03); }

        .brand-divider {
            max-width: 950px;
            margin: 15px auto 25px;
            border-bottom: 2px solid #cbd5e1;
            opacity: 0.7;
        }
        
        .hero { 
            text-align: center; 
            padding: 80px 20px; 
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
            max-width: 910px;
            margin: 0 auto 40px;
        }
        .hero h1 { font-size: 42px; color: #002855; margin: 0 0 15px; font-weight: 800; }
        .hero p { font-size: 18px; color: #64748b; max-width: 600px; margin: 0 auto 35px; line-height: 1.6; }
        .btn-lapor { 
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background-color: #002855; 
            color: white; 
            padding: 18px 40px; 
            border-radius: 12px; 
            text-decoration: none; 
            font-size: 18px; 
            font-weight: bold; 
            box-shadow: 0 4px 15px rgba(0, 40, 85, 0.2); 
            transition: 0.2s;
        }
        .btn-lapor:hover { background-color: #001a3b; transform: translateY(-2px); }
        
        .feed-container { max-width: 950px; margin: 0 auto 40px; padding: 0 20px; box-sizing: border-box; }
        
        /* Samakan warna kontainer sesuai request */
        .box-laporan-warga-baru {
            background-color: #ffffff;
            border-radius: 20px;
            padding: 35px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        }

        .box-laporan-warga-baru h3 {
            color: #002855;
            font-size: 24px;
            font-weight: 800;
            margin: 0 0 25px 0;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 15px;
        }
        
        .report-card { 
            display: flex; 
            gap: 30px; 
            background: #f8fafc; 
            border-radius: 16px; 
            padding: 30px; 
            margin-bottom: 30px; 
            border: 1px solid #e2e8f0; 
            transition: 0.2s;
        }
        
        .report-img { 
            width: 320px; 
            min-width: 320px; 
            border-radius: 12px; 
            overflow: hidden; 
            background: #eee; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .report-img img, .report-img video { width: 100%; height: 100%; object-fit: cover; border-radius: 12px; }
        .report-content { flex: 1; display: flex; flex-direction: column; }
        
        .header-kategori { 
            font-size: 20px; 
            font-weight: 800; 
            color: #002855; 
            margin: 0 0 16px 0; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
        }
        .icon-kat { 
            width: 42px; 
            height: 42px; 
            background: #e0e7ff; 
            color: #002855; 
            border-radius: 10px; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            font-size: 18px; 
        }
        
        .box-lokasi, .box-deskripsi, .box-koordinat { border-radius: 10px; padding: 14px 18px; font-size: 15px; margin-bottom: 16px; }
        .box-lokasi { background: white; border: 1px solid #e2e8f0; color: #334155; display: flex; align-items: center; gap: 10px; }
        .box-deskripsi { background: white; border: 1px solid #e2e8f0; color: #334155; line-height: 1.6; }
        .box-koordinat { background: #eff6ff; border: 1px dashed #bfdbfe; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .box-koordinat a { color: #2563eb; text-decoration: none; font-weight: bold; }
        .box-koordinat a:hover { text-decoration: underline; }
        
        .report-tanggapan { border-left: 4px solid #002855; background: white; padding: 16px 20px; border-radius: 0 10px 10px 0; margin-bottom: 16px; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; }
        .report-footer { margin-top: auto; padding-top: 16px; font-size: 13px; color: #94a3b8; display: flex; justify-content: space-between; align-items: center; }
        
        .badge { padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 800; }
        .badge-menunggu { background: #fee2e2; color: #dc2626; }
        .badge-diproses { background: #fef3c7; color: #d97706; }
        .badge-selesai { background: #dcfce3; color: #16a34a; }

        .btn-view-all {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            background-color: #f1f5f9;
            color: #002855;
            border: 1px solid #cbd5e1;
            padding: 16px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
            box-sizing: border-box;
            margin-top: 10px;
        }
        .btn-view-all:hover {
            background-color: #e2e8f0;
        }

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

        .mini-map {
            width: 100%;
            height: 180px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            margin-bottom: 14px;
        }

        /* LIGHTBOX */
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
            top: 25px; right: 35px;
            color: #ffffff;
            font-size: 44px;
            font-weight: bold;
            cursor: pointer;
            z-index: 100000;
            transition: 0.2s;
            line-height: 1;
        }

        .lightbox-close:hover { color: #f87171; transform: scale(1.15); }

        @keyframes zoomIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        @media (max-width: 768px) {
            .report-card { flex-direction: column; }
            .report-img { width: 100%; min-width: 100%; height: 220px; }
        }
    </style>
</head>
<body>

    <!-- MODAL LIGHTBOX ZOOM FOTO -->
    <div id="image-lightbox-modal" class="lightbox-overlay" onclick="closeLightbox(event)">
        <span class="lightbox-close" onclick="closeLightboxDirect()">&times;</span>
        <img id="lightbox-img" class="lightbox-content" src="" alt="Zoom Foto">
    </div>

    <!-- HEADER SIPELDA TENGAH -->
    <div class="brand-header-centered">
        <a href="index.php">
            <i class="fa-solid fa-shield-halved" style="color: #002855;"></i> SIPELDA
        </a>
    </div>
    <div class="brand-divider"></div>

    <section class="hero">
        <h1>Layanan Pengaduan Warga<br>Kelurahan</h1>
        <p>Sampaikan aspirasi, keluhan, dan pantau penyelesaian masalah di sekitarmu secara transparan.</p>
        <a href="pengaduan/buatpengaduan.php" class="btn-lapor"><i class="fa-solid fa-bullhorn"></i> Kirim Aduan Sekarang</a>
    </section>

    <div class="feed-container">
        <div class="box-laporan-warga-baru">
            <h3>Laporan Terbaru Warga Sekitar</h3>

            <?php 
            $mini_maps_script = [];
            if (mysqli_num_rows($query_publik) > 0): 
                while ($row = mysqli_fetch_assoc($query_publik)):
                    $pecah_judul    = explode(' - ', str_replace([' [ANONIM]', ' [PRIVAT]'], '', $row['judul_laporan']), 2);
                    $kategori_murni = $pecah_judul[0];
                    $lokasi_detail  = $pecah_judul[1] ?? 'Lokasi tidak spesifik';

                    $pecah_isi       = explode("\n\n📍 Titik Koordinat Peta:\n", $row['isi_laporan']);
                    $deskripsi_murni = $pecah_isi[0];
                    $link_maps       = isset($pecah_isi[1]) ? trim($pecah_isi[1]) : '';

                    $card_lat = $card_lng = null;
                    if ($link_maps && preg_match('/q=(-?\d+\.\d+),(-?\d+\.\d+)/', $link_maps, $m)) {
                        $card_lat = $m[1];
                        $card_lng = $m[2];
                        $mini_maps_script[] = ['id' => $row['id_pengaduan'], 'lat' => $card_lat, 'lng' => $card_lng];
                    }

                    $kat_lower = strtolower($kategori_murni);
                    $icon_kat  = "fa-bullhorn";
                    $daftar_ikon = [
                        'penerangan' => 'fa-lightbulb', 'pju' => 'fa-lightbulb', 'jalan' => 'fa-road',
                        'sampah' => 'fa-trash-can', 'kebersihan' => 'fa-trash-can', 'kesehatan' => 'fa-notes-medical',
                        'lingkungan' => 'fa-notes-medical', 'keamanan' => 'fa-shield-halved', 'ketertiban' => 'fa-shield-halved',
                        'lalu lintas' => 'fa-car', 'parkir' => 'fa-car', 'administrasi' => 'fa-file-signature',
                        'birokrasi' => 'fa-file-signature', 'bantuan' => 'fa-handshake-angle', 'bansos' => 'fa-handshake-angle',
                        'bencana' => 'fa-triangle-exclamation', 'darurat' => 'fa-triangle-exclamation', 'fasilitas' => 'fa-building'
                    ];
                    foreach ($daftar_ikon as $kata => $ikon) {
                        if (strpos($kat_lower, $kata) !== false) { $icon_kat = $ikon; break; }
                    }

                    $ext_file = strtolower(pathinfo($row['foto'], PATHINFO_EXTENSION));
                    $is_video = in_array($ext_file, ['mp4', 'webm', 'mov', 'mkv']);
            ?>
                <div class="report-card">
                    <div class="report-img">
                        <?php if ($row['foto']): ?>
                            <?php if ($is_video): ?>
                                <video src="uploads/<?= $row['foto'] ?>" controls style="width:100%; height:100%; border-radius:12px;"></video>
                            <?php else: ?>
                                <img src="uploads/<?= $row['foto'] ?>" alt="Foto Kejadian" onclick="openLightbox('uploads/<?= $row['foto'] ?>')" style="cursor:pointer;" title="Klik untuk memperbesar">
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="color: #999; font-size: 15px; text-align: center;"><i class="fa-regular fa-image" style="font-size: 36px; display:block; margin-bottom:10px;"></i>(Tanpa Bukti)</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="report-content">
                        <div class="header-kategori">
                            <div class="icon-kat"><i class="fa-solid <?= $icon_kat ?>"></i></div>
                            <?= htmlspecialchars($kategori_murni) ?>
                        </div>
                        
                        <div class="box-lokasi">
                            <i class="fa-solid fa-location-dot" style="color: #dc3545;"></i> <?= htmlspecialchars($lokasi_detail) ?>
                        </div>
                        
                        <?php if ($card_lat && $card_lng): ?>
                            <div id="card-map-<?= $row['id_pengaduan'] ?>" class="mini-map"></div>
                        <?php endif; ?>
                        
                        <div class="box-deskripsi">
                            <strong>Deskripsi Masalah:</strong><br>
                            <?= nl2br(htmlspecialchars($deskripsi_murni)) ?>
                        </div>
                        
                        <?php if ($link_maps): ?>
                            <div class="box-koordinat">
                                <i class="fa-solid fa-map-location-dot" style="color: #2563eb;"></i>
                                <span>Titik Koordinat: <a href="<?= htmlspecialchars($link_maps) ?>" target="_blank">Lihat di Google Maps ↗</a></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($row['isi_tanggapan']): ?>
                            <div class="report-tanggapan">
                                <strong style="color: #002855; font-size: 14px; display: block; margin-bottom: 5px;"><i class="fa-regular fa-comment-dots"></i> Tanggapan Resmi Kelurahan:</strong>
                                <span style="font-size: 14px; color: #475569;"><?= htmlspecialchars($row['isi_tanggapan']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="report-footer">
                            <span><i class="fa-solid fa-user-pen"></i> <?= (strpos($row['judul_laporan'], '[ANONIM]') !== false) ? 'Masyarakat Anonim' : htmlspecialchars($row['nama_lengkap']) ?></span>
                            <span><i class="fa-regular fa-calendar-days"></i> <?= date('d M Y, H:i', strtotime($row['tgl_pengaduan'])) ?></span>
                            <span class="badge badge-<?= strtolower($row['status']) ?>"><?= strtoupper($row['status']) ?></span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
                
                <!-- TOMBOL LIHAT SEMUA ADUAN -->
                <a href="pengaduan/semuapengaduan.php" class="btn-view-all"><i class="fa-solid fa-layer-group"></i> Lihat Semua Laporan Warga</a>
            <?php else: ?>
                <p style="text-align:center; padding: 40px; color:#64748b;">Belum ada laporan pengaduan dari warga.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- FOOTBAR NAVIGATION BAR -->
    <nav class="footbar-nav">
        <a href="index.php" class="footbar-item active">
            <i class="fa-solid fa-house"></i>
            <span>Beranda</span>
        </a>
        <a href="pengaduan/historipengaduan.php" class="footbar-item">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Riwayat</span>
        </a>
        <a href="profil/profil.php" class="footbar-item">
            <i class="fa-solid fa-user"></i>
            <span>Profil</span>
        </a>
    </nav>

    <script>
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

        // === PETA MINI CARD ===
        <?php foreach ($mini_maps_script as $mm): ?>
            (function() {
                const mapObj = L.map('card-map-<?= $mm['id'] ?>', {
                    dragging: false, zoomControl: false,
                    scrollWheelZoom: false, doubleClickZoom: false
                }).setView([<?= $mm['lat'] ?>, <?= $mm['lng'] ?>], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapObj);
                L.marker([<?= $mm['lat'] ?>, <?= $mm['lng'] ?>]).addTo(mapObj);
            })();
        <?php endforeach; ?>
    </script>
</body>
</html>
