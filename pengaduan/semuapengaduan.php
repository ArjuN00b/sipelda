<?php
session_start();
require 'koneksi.php';

$search        = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, trim($_GET['search'])) : '';
$filter_kat    = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';
$filter_dari   = isset($_GET['tgl_dari']) ? $_GET['tgl_dari'] : '';
$filter_sampai = isset($_GET['tgl_sampai']) ? $_GET['tgl_sampai'] : '';

$query_sql = "
    SELECT p.*, u.nama_lengkap, t.isi_tanggapan 
    FROM pengaduan p 
    JOIN users u ON p.id_user = u.id_user 
    LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan 
    WHERE p.judul_laporan NOT LIKE '%[PRIVAT]%'
";

if ($search != '') {
    $query_sql .= " AND (p.judul_laporan LIKE '%$search%' OR p.isi_laporan LIKE '%$search%' OR u.nama_lengkap LIKE '%$search%')";
}

if ($filter_kat != '' && $filter_kat != 'semua') {
    $query_sql .= " AND p.judul_laporan LIKE '$filter_kat%'";
}

if ($filter_dari != '') {
    $tgl_dari_safe = mysqli_real_escape_string($koneksi, $filter_dari);
    $query_sql .= " AND DATE(p.tgl_pengaduan) >= '$tgl_dari_safe'";
}

if ($filter_sampai != '') {
    $tgl_sampai_safe = mysqli_real_escape_string($koneksi, $filter_sampai);
    $query_sql .= " AND DATE(p.tgl_pengaduan) <= '$tgl_sampai_safe'";
}

$query_sql .= " ORDER BY p.tgl_pengaduan DESC";
$query_publik = mysqli_query($koneksi, $query_sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Laporan Warga - SIPELDA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        @keyframes pageFadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            transition: transform 0.2s;
        }

        .brand-header-centered a:hover { transform: scale(1.03); }

        .brand-divider {
            max-width: 1000px;
            margin: 15px auto 25px;
            border-bottom: 2px solid #cbd5e1;
            opacity: 0.7;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto 40px;
            padding: 0 20px;
        }

        .page-header {
            margin-bottom: 25px;
            text-align: center;
        }

        .page-header h2 {
            font-size: 32px;
            color: #002855;
            margin: 0 0 10px;
            font-weight: 800;
        }

        .page-header p {
            color: #64748b;
            font-size: 16px;
            margin: 0;
        }

        .report-card {
            display: flex;
            gap: 30px;
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }

        .report-card.scroll-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .report-img {
            width: 320px;
            min-width: 320px;
            border-radius: 12px;
            overflow: hidden;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .report-img img, .report-img video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
        }

        .report-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

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

        .box-lokasi, .box-deskripsi, .box-koordinat {
            border-radius: 10px;
            padding: 14px 18px;
            font-size: 15px;
            margin-bottom: 16px;
        }

        .box-lokasi {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .box-deskripsi {
            background: white;
            border: 1px solid #e2e8f0;
            color: #334155;
            line-height: 1.6;
        }

        .box-koordinat {
            background: #eff6ff;
            border: 1px dashed #bfdbfe;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .box-koordinat a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 700;
        }

        .report-tanggapan {
            border-left: 4px solid #002855;
            background: #f8fafc;
            padding: 16px 20px;
            border-radius: 0 10px 10px 0;
            margin-bottom: 16px;
        }

        .report-footer {
            margin-top: auto;
            padding-top: 16px;
            font-size: 13px;
            color: #94a3b8;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .badge {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 800;
        }

        .badge-menunggu { background: #fee2e2; color: #dc2626; }
        .badge-diproses { background: #fef3c7; color: #d97706; }
        .badge-selesai  { background: #dcfce3; color: #16a34a; }

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

    <div class="brand-header-centered">
        <a href="index.php">
            <i class="fa-solid fa-shield-halved" style="color: #002855;"></i> SIPELDA
        </a>
    </div>
    <div class="brand-divider"></div>

    <div class="container">
        <div class="page-header">
            <h2>Semua Laporan Publik Warga</h2>
            <p>Telusuri seluruh aduan dan aspirasi warga yang telah dikirimkan secara terbuka.</p>
        </div>

        <!-- PANEL FILTER + SEARCH -->
        <form id="filter-form" method="GET" action="semuapengaduan.php">
            <div style="background:white; border:1px solid #e2e8f0; border-radius:14px; padding:24px 28px; box-shadow:0 4px 12px rgba(0,0,0,0.04); margin-bottom:24px;">
                <!-- Baris 1: Search -->
                <div style="position:relative; margin-bottom:16px;">
                    <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:18px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:17px;"></i>
                    <input type="text" name="search" id="live-search"
                           value="<?= htmlspecialchars($search) ?>"
                           placeholder="Cari kata kunci laporan publik..."
                           style="width:100%; padding:15px 16px 15px 52px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:16px; outline:none; box-sizing:border-box; transition:0.2s;"
                           autocomplete="off">
                </div>

                <!-- Baris 2: Filter tambahan -->
                <div style="display:flex; gap:14px; flex-wrap:wrap; align-items:center;">
                    <!-- Kategori -->
                    <select name="kategori" style="flex:1; min-width:200px; padding:12px 14px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:15px; background:#f8fafc; color:#334155; outline:none; cursor:pointer;">
                        <option value="semua" <?= ($filter_kat == '' || $filter_kat == 'semua') ? 'selected' : '' ?>>🗂️ Semua Kategori</option>
                        <?php
                        $opsi_kat = [
                            'Jalan Rusak & Infrastruktur', 'Kebersihan & Sampah',
                            'Penerangan Jalan Umum (PJU)', 'Kesehatan & Lingkungan',
                            'Keamanan & Ketertiban', 'Ketertiban Lalu Lintas & Parkir',
                            'Pelayanan Administrasi', 'Bantuan Sosial (Bansos)',
                            'Kedaruratan & Bencana', 'Fasilitas Umum', 'Lainnya'
                        ];
                        foreach ($opsi_kat as $op) {
                            $sel = ($filter_kat === $op) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($op) . "' $sel>" . htmlspecialchars($op) . "</option>";
                        }
                        ?>
                    </select>

                    <!-- Tanggal Dari -->
                    <div style="display:flex; align-items:center; gap:8px; flex:0 0 auto;">
                        <label style="font-size:14px; color:#64748b; font-weight:600; white-space:nowrap;">Dari:</label>
                        <input type="date" name="tgl_dari" value="<?= htmlspecialchars($filter_dari) ?>" style="padding:11px 13px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:15px; outline:none; background:#f8fafc;">
                    </div>

                    <!-- Tanggal Sampai -->
                    <div style="display:flex; align-items:center; gap:8px; flex:0 0 auto;">
                        <label style="font-size:14px; color:#64748b; font-weight:600; white-space:nowrap;">Sampai:</label>
                        <input type="date" name="tgl_sampai" value="<?= htmlspecialchars($filter_sampai) ?>" style="padding:11px 13px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:15px; outline:none; background:#f8fafc;">
                    </div>

                    <!-- Tombol -->  
                    <button type="submit" style="padding:12px 22px; background:#002855; color:white; border:none; border-radius:10px; font-size:15px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:8px; transition:0.2s;" onmouseover="this.style.background='#001a3b'" onmouseout="this.style.background='#002855'">
                        <i class="fa-solid fa-filter"></i> Terapkan
                    </button>

                    <?php if ($search || ($filter_kat && $filter_kat != 'semua') || $filter_dari || $filter_sampai): ?>
                    <a href="semuapengaduan.php" style="padding:12px 20px; background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; border-radius:10px; font-size:15px; font-weight:700; text-decoration:none; display:flex; align-items:center; gap:8px; transition:0.2s;">
                        <i class="fa-solid fa-xmark"></i> Reset Filter
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <div id="reports-list">
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
                        'penerangan' => 'fa-lightbulb', 'pju' => 'fa-lightbulb',
                        'jalan' => 'fa-road', 'sampah' => 'fa-trash-can',
                        'kebersihan' => 'fa-trash-can', 'kesehatan' => 'fa-notes-medical',
                        'lingkungan' => 'fa-notes-medical', 'keamanan' => 'fa-shield-halved',
                        'ketertiban' => 'fa-shield-halved', 'lalu lintas' => 'fa-car',
                        'parkir' => 'fa-car', 'administrasi' => 'fa-file-signature',
                        'birokrasi' => 'fa-file-signature', 'bantuan' => 'fa-handshake-angle',
                        'bansos' => 'fa-handshake-angle', 'bencana' => 'fa-triangle-exclamation',
                        'darurat' => 'fa-triangle-exclamation', 'fasilitas' => 'fa-building'
                    ];
                    foreach ($daftar_ikon as $kata => $ikon) {
                        if (strpos($kat_lower, $kata) !== false) {
                            $icon_kat = $ikon;
                            break;
                        }
                    }

                    $ext_file = strtolower(pathinfo($row['foto'], PATHINFO_EXTENSION));
                    $is_video = in_array($ext_file, ['mp4', 'webm', 'mov', 'mkv']);
            ?>
                    <div class="report-card" data-searchable="<?= htmlspecialchars(strtolower($kategori_murni . ' ' . $lokasi_detail . ' ' . $deskripsi_murni)) ?>">
                        <div class="report-img">
                            <?php if ($row['foto']): ?>
                                <?php if ($is_video): ?>
                                    <video src="../uploads/<?= $row['foto'] ?>" controls style="width:100%; height:100%; border-radius:12px;"></video>
                                <?php else: ?>
                                    <img src="../uploads/<?= $row['foto'] ?>" alt="Bukti Kejadian" onclick="openLightbox('../uploads/<?= $row['foto'] ?>')" title="Klik untuk memperbesar gambar" style="cursor:pointer;">
                                <?php endif; ?>
                            <?php else: ?>
                                <div style="color: #94a3b8; font-size: 15px; text-align: center;"><i class="fa-regular fa-image" style="font-size: 36px; display:block; margin-bottom:10px;"></i>(Tanpa Foto/Video)</div>
                            <?php endif; ?>
                        </div>

                        <div class="report-content">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                <div class="header-kategori">
                                    <div class="icon-kat"><i class="fa-solid <?= $icon_kat ?>"></i></div>
                                    <?= htmlspecialchars($kategori_murni) ?>
                                </div>
                                <span class="badge badge-<?= strtolower($row['status']) ?>"><?= strtoupper($row['status']) ?></span>
                            </div>

                            <div class="box-lokasi">
                                <i class="fa-solid fa-location-dot" style="color: #dc3545;"></i> <?= htmlspecialchars($lokasi_detail) ?>
                            </div>

                            <?php if ($card_lat && $card_lng): ?>
                                <div id="card-map-<?= $row['id_pengaduan'] ?>" class="mini-map"></div>
                            <?php endif; ?>

                            <div class="box-deskripsi">
                                <strong>Deskripsi Kejadian:</strong><br>
                                <?= nl2br(htmlspecialchars($deskripsi_murni)) ?>
                            </div>

                            <?php if ($link_maps): ?>
                                <div class="box-koordinat">
                                    <i class="fa-solid fa-map-location-dot" style="color: #2563eb;"></i>
                                    <span>Titik GPS: <a href="<?= htmlspecialchars($link_maps) ?>" target="_blank">Buka di Google Maps ↗</a></span>
                                </div>
                            <?php endif; ?>

                            <div class="report-tanggapan">
                                <h4 style="margin:0 0 8px; font-size:15px; color:#002855;"><i class="fa-regular fa-comment-dots"></i> Tanggapan Kelurahan</h4>
                                <p style="margin:0; font-size:15px; font-style:<?= $row['isi_tanggapan'] ? 'normal' : 'italic' ?>; color:#475569;">
                                    <?= $row['isi_tanggapan'] ? htmlspecialchars($row['isi_tanggapan']) : 'Belum ada tanggapan.' ?>
                                </p>
                            </div>

                            <div class="report-footer">
                                <span><i class="fa-regular fa-calendar-days"></i> <?= date('d M Y, H:i', strtotime($row['tgl_pengaduan'])) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding: 60px; background:white; border-radius:16px; border:1px solid #e2e8f0; color:#64748b; font-size:16px;">
                    <i class="fa-solid fa-folder-open" style="font-size: 40px; color:#cbd5e1; display:block; margin-bottom:15px;"></i>
                    Belum ada laporan pengaduan yang sesuai.
                </div>
            <?php endif; ?>
        </div>
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

        // === DEBOUNCE SEARCH: submit form otomatis saat user berhenti mengetik (400ms) ===
        let debounceTimer = null;
        const liveSearch = document.getElementById('live-search');
        const filterForm = document.getElementById('filter-form');

        if (liveSearch) {
            liveSearch.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => filterForm.submit(), 400);
            });

            liveSearch.addEventListener('focus', function() {
                this.style.borderColor = '#002855';
                this.style.boxShadow = '0 0 0 4px rgba(0, 40, 85, 0.1)';
            });

            liveSearch.addEventListener('blur', function() {
                this.style.borderColor = '#cbd5e1';
                this.style.boxShadow = 'none';
            });
        }

        const reportCards = document.querySelectorAll('.report-card');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('scroll-visible');
                }
            });
        }, { threshold: 0.1 });

        reportCards.forEach(card => observer.observe(card));
    </script>
</body>
</html>
