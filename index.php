<?php
session_start();
require 'koneksi.php';

$foto_profil_nav = "";
if (isset($_SESSION['status']) && $_SESSION['status'] === 'login') {
    $id_user_nav = $_SESSION['id_user'];
    $q_nav = mysqli_query($koneksi, "SELECT foto_profil FROM users WHERE id_user = '$id_user_nav'");
    if ($q_nav && mysqli_num_rows($q_nav) > 0) {
        $foto_profil_nav = mysqli_fetch_assoc($q_nav)['foto_profil'];
    }
}

$query_publik = mysqli_query($koneksi, "
    SELECT p.*, u.nama_lengkap, t.isi_tanggapan 
    FROM pengaduan p 
    JOIN users u ON p.id_user = u.id_user 
    LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan 
    WHERE p.judul_laporan NOT LIKE '%[PRIVAT]%' 
    ORDER BY p.tgl_pengaduan DESC LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SIPELDA - Beranda</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS Dioptimalkan */
        body { font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; background-color: #f4f7fb; color: #333; }
        
        .navbar { background-color: #002855; color: white; padding: 25px 60px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .navbar .logo { font-size: 26px; font-weight: bold; color: white; text-decoration: none; }
        
        .nav-center { display: flex; gap: 40px; }
        .nav-center a { color: #a9b9cc; text-decoration: none; font-size: 16px; font-weight: 500; padding-bottom: 5px; transition: 0.2s; }
        .nav-center a:hover { color: white; }
        .nav-center a.active { color: white; border-bottom: 2px solid white; }
        
        .user-profile-btn { display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.1); padding: 8px 20px; border-radius: 30px; text-decoration: none; color: white; border: 1px solid rgba(255,255,255,0.2); }
        .nav-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.8); }
        
        .btn-login { background-color: #198754; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; }
        
        .hero { text-align: center; padding: 120px 20px; background: linear-gradient(to bottom, #e2eafc, #f4f7fb); }
        .hero h1 { font-size: 38px; color: #002855; margin-bottom: 15px; }
        .hero p { font-size: 16px; color: #555; max-width: 600px; margin: 0 auto 40px; line-height: 1.6; }
        .btn-lapor { background-color: #002855; color: white; padding: 18px 40px; border-radius: 8px; text-decoration: none; font-size: 18px; font-weight: bold; box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
        
        .feed-container { max-width: 950px; margin: 0 auto 80px; padding: 0 20px; }
        
        .report-card { display: flex; gap: 25px; background: white; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .report-img { width: 300px; min-width: 300px; border-radius: 8px; overflow: hidden; background: #eee; display: flex; align-items: center; justify-content: center; }
        .report-img img { width: 100%; height: 100%; object-fit: cover; }
        .report-content { flex: 1; display: flex; flex-direction: column; }
        
        .header-kategori { font-size: 18px; font-weight: bold; color: #002855; margin: 0 0 15px 0; display: flex; align-items: center; gap: 10px; }
        .icon-kat { width: 35px; height: 35px; background: #e0e7ff; color: #002855; border-radius: 8px; display: flex; justify-content: center; align-items: center; font-size: 16px; }
        
        .box-lokasi, .box-deskripsi, .box-koordinat { border-radius: 8px; padding: 12px 15px; font-size: 14px; margin-bottom: 15px; }
        .box-lokasi { background: #f8fafc; border: 1px solid #e2e8f0; color: #334155; display: flex; align-items: center; gap: 10px; }
        .box-deskripsi { background: white; border: 1px solid #e2e8f0; color: #334155; line-height: 1.5; }
        .box-koordinat { background: #eff6ff; border: 1px dashed #bfdbfe; font-size: 13px; display: flex; align-items: center; gap: 10px; }
        .box-koordinat a { color: #2563eb; text-decoration: none; font-weight: bold; }
        .box-koordinat a:hover { text-decoration: underline; }
        
        .report-tanggapan { border-left: 4px solid #002855; background: #f8fafc; padding: 15px; border-radius: 0 8px 8px 0; }
        .report-footer { margin-top: auto; padding-top: 15px; font-size: 12px; color: #94a3b8; display: flex; justify-content: space-between; align-items: flex-end; }
        
        .badge { padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: bold; }
        .badge-menunggu { background: #fee2e2; color: #dc2626; }
        .badge-diproses { background: #fef3c7; color: #d97706; }
        .badge-selesai { background: #dcfce3; color: #16a34a; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">SIPELDA</a>
        <div class="nav-center"><a href="index.php" class="active">Beranda</a><a href="historipengaduan.php">Riwayat</a></div>
        <div>
            <?php if (isset($_SESSION['status']) && $_SESSION['status'] === 'login'): ?>
                <a href="profil.php" class="user-profile-btn">
                    <span><?= htmlspecialchars($_SESSION['username']); ?></span>
                    <?php if (!empty($foto_profil_nav) && file_exists('uploads/' . $foto_profil_nav)): ?>
                        <img src="uploads/<?= $foto_profil_nav ?>" class="nav-avatar">
                    <?php else: ?>
                        <i class="fa-solid fa-circle-user" style="font-size: 24px; color: #cbd5e1;"></i>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <a href="login.php" class="btn-login"><i class="fa-solid fa-right-to-bracket"></i> Masuk / Daftar</a>
            <?php endif; ?>
        </div>
    </nav>

    <section class="hero">
        <h1>Layanan Pengaduan Warga<br>Kelurahan</h1>
        <p>Sampaikan aspirasi, keluhan, dan pantau penyelesaian masalah di sekitarmu secara transparan.</p>
        <a href="buatpengaduan.php" class="btn-lapor"><i class="fa-solid fa-bullhorn"></i> Kirim Aduan Sekarang</a>
    </section>

    <div class="feed-container">
        <h3 style="color: #002855; border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-bottom: 20px;">Laporan Terbaru Warga Sekitar</h3>

        <?php 
        if (mysqli_num_rows($query_publik) > 0): 
            while ($row = mysqli_fetch_assoc($query_publik)):
                // Parsing Judul & Lokasi
                $pecah_judul    = explode(' - ', str_replace([' [ANONIM]', ' [PRIVAT]'], '', $row['judul_laporan']), 2);
                $kategori_murni = $pecah_judul[0];
                $lokasi_detail  = $pecah_judul[1] ?? 'Lokasi tidak spesifik';

                // Parsing Deskripsi & Link Maps
                $pecah_isi       = explode("\n\n📍 Titik Koordinat Peta:\n", $row['isi_laporan']);
                $deskripsi_murni = $pecah_isi[0];
                $link_maps       = isset($pecah_isi[1]) ? trim($pecah_isi[1]) : '';

                // Penentuan Ikon Otomatis Menggunakan Array
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
        ?>
            <div class="report-card">
                <div class="report-img">
                    <?php if ($row['foto']): ?>
                        <img src="uploads/<?= $row['foto'] ?>" alt="Foto Kejadian">
                    <?php else: ?>
                        <div style="color: #999; font-size: 14px; text-align: center;"><i class="fa-regular fa-image" style="font-size: 30px; display:block; margin-bottom:10px;"></i>(Tanpa Foto)</div>
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
                        <h4 style="margin:0 0 8px; font-size:14px; color:#002855;"><i class="fa-regular fa-comment-dots"></i> Tanggapan Kelurahan</h4>
                        <p style="margin:0; font-size:14px; font-style:<?= $row['isi_tanggapan'] ? 'normal' : 'italic' ?>; color:#64748b;">
                            <?= $row['isi_tanggapan'] ? htmlspecialchars($row['isi_tanggapan']) : 'Belum ada tanggapan.' ?>
                        </p>
                    </div>

                    <div class="report-footer">
                        <span>Pelapor: <strong><?= strpos($row['judul_laporan'], '[ANONIM]') !== false ? 'Warga Anonim' : htmlspecialchars($row['nama_lengkap']) ?></strong></span>
                        <span><?= date('d M Y, H:i', strtotime($row['tgl_pengaduan'])) ?></span>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center; padding: 40px; color:#64748b;">Belum ada laporan pengaduan dari warga.</p>
        <?php endif; ?>
    </div>
</body>
</html>
