<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || $_SESSION['role'] !== 'masyarakat') {
    header("Location: login.php");
    exit;
}
$id_user_login = $_SESSION['id_user'];

// Ambil foto profil (Dioptimalkan)
$foto_profil_nav = "";
$q_nav = mysqli_query($koneksi, "SELECT foto_profil FROM users WHERE id_user = '$id_user_login'");
if ($q_nav && mysqli_num_rows($q_nav) > 0) {
    $foto_profil_nav = mysqli_fetch_assoc($q_nav)['foto_profil'];
}

// LOGIKA HAPUS PENGADUAN
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    $cek_query = mysqli_query($koneksi, "SELECT foto FROM pengaduan WHERE id_pengaduan = '$id_hapus' AND id_user = '$id_user_login' AND status = 'menunggu'");
    
    if ($data_hapus = mysqli_fetch_assoc($cek_query)) {
        if (!empty($data_hapus['foto']) && file_exists('uploads/' . $data_hapus['foto'])) {
            unlink('uploads/' . $data_hapus['foto']);
        }
        mysqli_query($koneksi, "DELETE FROM pengaduan WHERE id_pengaduan = '$id_hapus'") ? 
            $msg = "Laporan berhasil dihapus!" : $msg = "Gagal menghapus laporan.";
        echo "<script>alert('$msg'); window.location.href='historipengaduan.php';</script>";
    } else {
        echo "<script>alert('Akses ditolak!'); window.location.href='historipengaduan.php';</script>";
    }
}

// OPTIMASI: Hitung semua statistik dalam 1 Query (Bukan 4 Query terpisah)
$q_stats = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) AS menunggu,
        SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) AS diproses,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) AS selesai
    FROM pengaduan WHERE id_user = '$id_user_login'
"));

// Ambil daftar riwayat
$result = mysqli_query($koneksi, "SELECT * FROM pengaduan WHERE id_user = '$id_user_login' ORDER BY tgl_pengaduan DESC");

// Ambil data detail jika diminta
$detail_data = null;
if (isset($_GET['detail'])) {
    $id_detail = mysqli_real_escape_string($koneksi, $_GET['detail']);
    $detail_data = mysqli_fetch_assoc(mysqli_query($koneksi, "
        SELECT p.*, t.isi_tanggapan FROM pengaduan p 
        LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan 
        WHERE p.id_pengaduan = '$id_detail' AND p.id_user = '$id_user_login'
    "));
}

function getIkonKategori(string $kategori): string {
    $kat = strtolower($kategori);
    $ikon_list = [
        'penerangan' => 'fa-lightbulb', 'pju' => 'fa-lightbulb', 'jalan' => 'fa-road',
        'sampah' => 'fa-trash-can', 'kebersihan' => 'fa-trash-can', 'kesehatan' => 'fa-notes-medical',
        'lingkungan' => 'fa-notes-medical', 'keamanan' => 'fa-shield-halved', 'ketertiban' => 'fa-shield-halved',
        'lalu lintas' => 'fa-car', 'parkir' => 'fa-car', 'administrasi' => 'fa-file-signature',
        'birokrasi' => 'fa-file-signature', 'bantuan' => 'fa-handshake-angle', 'bansos' => 'fa-handshake-angle',
        'bencana' => 'fa-triangle-exclamation', 'darurat' => 'fa-triangle-exclamation', 'fasilitas' => 'fa-building'
    ];
    foreach ($ikon_list as $kata => $ikon) {
        if (strpos($kat, $kata) !== false) return $ikon;
    }
    return "fa-bullhorn";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat - SIPELDA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS Dioptimalkan */
        body { font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; background-color: #f4f7fb; color: #333; }
        
        .navbar { background-color: #002855; color: white; padding: 25px 60px; display: flex; justify-content: space-between; align-items: center; }
        .navbar .logo { font-size: 26px; font-weight: bold; color: white; text-decoration: none; }
        
        .nav-center { display: flex; gap: 40px; }
        .nav-center a { color: #a9b9cc; text-decoration: none; font-size: 16px; font-weight: 500; padding-bottom: 5px; }
        .nav-center a.active { color: white; border-bottom: 2px solid white; }
        
        .user-profile-btn { display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.1); padding: 8px 20px; border-radius: 30px; text-decoration: none; color: white; border: 1px solid rgba(255,255,255,0.2); }
        .nav-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; }
        
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 35px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; display: flex; align-items: center; gap: 15px; border: 1px solid #e2e8f0; }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold; color: white; }
        
        .history-cards { display: flex; flex-direction: column; gap: 15px; margin-bottom: 40px; }
        .history-card { background: white; border-radius: 12px; padding: 20px 25px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        
        .card-left { display: flex; gap: 20px; align-items: center; }
        .card-icon-wrapper, .icon-kat { width: 50px; height: 50px; border-radius: 10px; background: #e0e7ff; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #002855; }
        .icon-kat { width: 35px; height: 35px; border-radius: 8px; font-size: 16px; }
        
        .card-info h4 { margin: 0 0 5px 0; font-size: 16px; color: #0f172a; }
        .card-info p { margin: 0; font-size: 13px; color: #64748b; }
        
        .card-right { display: flex; align-items: center; gap: 15px; }
        
        .btn-lihat, .btn-hapus { text-decoration: none; padding: 8px 15px; border-radius: 6px; font-size: 13px; font-weight: 600; transition: 0.2s; display: flex; align-items: center; gap: 5px; }
        .btn-lihat { background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-lihat:hover { background-color: #e2e8f0; color: #002855; }
        .btn-hapus { background-color: #fee2e2; color: #dc2626; border: 1px solid #f87171; }
        .btn-hapus:hover { background-color: #dc2626; color: white; }
        
        .badge { padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .badge-menunggu { background: #fee2e2; color: #dc2626; }
        .badge-diproses { background: #fef3c7; color: #d97706; }
        .badge-selesai { background: #dcfce3; color: #16a34a; }
        
        .detail-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 30px; display: flex; gap: 30px; }
        .detail-img { flex: 1; border-radius: 10px; overflow: hidden; background: #eee; }
        .detail-img img { width: 100%; height: 100%; object-fit: cover; }
        .detail-content { flex: 2; }
        
        .header-kategori { font-size: 18px; font-weight: bold; color: #002855; margin: 0 0 15px 0; display: flex; align-items: center; gap: 10px; }
        .box-lokasi, .box-deskripsi, .box-koordinat { border-radius: 8px; padding: 12px 15px; font-size: 14px; margin-bottom: 15px; }
        .box-lokasi { background: #f8fafc; border: 1px solid #e2e8f0; color: #334155; }
        .box-deskripsi { background: white; border: 1px solid #e2e8f0; color: #334155; line-height: 1.5; }
        .box-koordinat { background: #eff6ff; border: 1px dashed #bfdbfe; font-size: 13px; display: flex; align-items: center; gap: 10px; }
        .tanggapan-box { background: #f8fafc; border-left: 4px solid #002855; padding: 15px; border-radius: 4px; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">SIPELDA</a>
        <div class="nav-center">
            <a href="index.php">Beranda</a><a href="historipengaduan.php" class="active">Riwayat</a>
        </div>
        <a href="profil.php" class="user-profile-btn">
            <span><?= htmlspecialchars($_SESSION['username']); ?></span>
            <?php if (!empty($foto_profil_nav) && file_exists('uploads/' . $foto_profil_nav)): ?>
                <img src="uploads/<?= $foto_profil_nav ?>" class="nav-avatar">
            <?php else: ?>
                <i class="fa-solid fa-circle-user" style="font-size: 24px; color: #cbd5e1;"></i>
            <?php endif; ?>
        </a>
    </nav>

    <div class="container">
        <h2 style="color: #002855; margin-bottom: 5px;">Riwayat Pengaduan Saya</h2>
        <p style="color: #64748b; margin-bottom: 30px;">Pantau status dan tanggapan dari semua laporan yang telah Anda kirimkan.</p>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon" style="background:#002855;"><i class="fa-solid fa-clipboard-list"></i></div><div><div style="font-size:13px; color:#64748b;">Total Laporan</div><div style="font-size:24px; font-weight:bold; color:#002855;"><?= $q_stats['total'] ?? 0 ?></div></div></div>
            <div class="stat-card"><div class="stat-icon" style="background:#fee2e2; color:#dc2626;"><i class="fa-solid fa-clock"></i></div><div><div style="font-size:13px; color:#64748b;">Menunggu</div><div style="font-size:24px; font-weight:bold; color:#dc2626;"><?= $q_stats['menunggu'] ?? 0 ?></div></div></div>
            <div class="stat-card"><div class="stat-icon" style="background:#fef3c7; color:#d97706;"><i class="fas fa-cog"></i></div><div><div style="font-size:13px; color:#64748b;">Sedang Diproses</div><div style="font-size:24px; font-weight:bold; color:#d97706;"><?= $q_stats['diproses'] ?? 0 ?></div></div></div>
            <div class="stat-card"><div class="stat-icon" style="background:#dcfce3; color:#16a34a;"><i class="fa-solid fa-check"></i></div><div><div style="font-size:13px; color:#64748b;">Selesai</div><div style="font-size:24px; font-weight:bold; color:#16a34a;"><?= $q_stats['selesai'] ?? 0 ?></div></div></div>
        </div>

        <div class="history-cards">
            <?php if (mysqli_num_rows($result) > 0): 
                while ($row = mysqli_fetch_assoc($result)):
                    $status = strtolower($row['status']);
                    $kategori_murni = explode(' - ', str_replace([' [ANONIM]', ' [PRIVAT]'], '', $row['judul_laporan']))[0];
            ?>
                <div class="history-card">
                    <div class="card-left">
                        <div class="card-icon-wrapper"><i class="fa-solid <?= getIkonKategori($kategori_murni) ?>"></i></div>
                        <div class="card-info">
                            <h4><?= htmlspecialchars($kategori_murni) ?> <?= strpos($row['judul_laporan'], '[PRIVAT]') !== false ? '<i class="fa-solid fa-lock" style="color:#dc3545; font-size:12px;" title="Privat"></i>' : '' ?></h4>
                            <p><span><i class="fa-regular fa-calendar"></i> <?= date('d M Y', strtotime($row['tgl_pengaduan'])) ?></span> | <span>#SPL-<?= $row['id_pengaduan'] ?></span></p>
                        </div>
                    </div>
                    <div class="card-right">
                        <span class="badge badge-<?= $status ?>"><?= ucfirst($status) ?></span>
                        <a href="historipengaduan.php?detail=<?= $row['id_pengaduan'] ?>#detail-section" class="btn-lihat">Lihat Detail</a>
                        <?php if ($status === 'menunggu'): ?>
                            <a href="historipengaduan.php?hapus=<?= $row['id_pengaduan'] ?>" class="btn-hapus" onclick="return confirm('Yakin ingin membatalkan laporan ini?')"><i class="fa-solid fa-trash"></i> Hapus</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <div style="text-align:center; padding: 40px; background:white; border-radius:12px; border:1px solid #e2e8f0; color:#64748b;">Anda belum memiliki riwayat pengaduan.</div>
            <?php endif; ?>
        </div>

        <?php if ($detail_data): 
            $pecah_judul = explode(' - ', str_replace([' [ANONIM]', ' [PRIVAT]'], '', $detail_data['judul_laporan']), 2);
            $kategori_murni = $pecah_judul[0];
            $lokasi_detail = $pecah_judul[1] ?? 'Lokasi tidak spesifik';

            $pecah_isi = explode("\n\n📍 Titik Koordinat Peta:\n", $detail_data['isi_laporan']);
            $deskripsi_murni = $pecah_isi[0];
            $link_maps = $pecah_isi[1] ?? '';
        ?>
            <div id="detail-section" style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="color:#002855;">Detail Laporan #<?= $detail_data['id_pengaduan'] ?></h3>
                <span class="badge badge-<?= strtolower($detail_data['status']) ?>"><?= ucfirst($detail_data['status']) ?></span>
            </div>

            <div class="detail-card">
                <div class="detail-img">
                    <?php if ($detail_data['foto']): ?>
                        <img src="uploads/<?= $detail_data['foto'] ?>" alt="Foto Kejadian">
                    <?php else: ?>
                        <div style="padding: 100px 20px; text-align: center; color: #999;"><i class="fa-regular fa-image" style="font-size: 30px; display:block; margin-bottom:10px;"></i>(Tanpa Foto)</div>
                    <?php endif; ?>
                </div>

                <div class="detail-content">
                    <div class="header-kategori">
                        <div class="icon-kat"><i class="fa-solid <?= getIkonKategori($kategori_murni) ?>"></i></div>
                        <?= htmlspecialchars($kategori_murni) ?>
                    </div>

                    <div class="box-lokasi"><i class="fa-solid fa-location-dot" style="color: #dc3545;"></i> <?= htmlspecialchars($lokasi_detail) ?></div>
                    <div class="box-deskripsi"><strong>Deskripsi Kejadian:</strong><br><?= nl2br(htmlspecialchars($deskripsi_murni)) ?></div>

                    <?php if ($link_maps): ?>
                        <div class="box-koordinat">
                            <i class="fa-solid fa-map-location-dot" style="color: #2563eb;"></i>
                            <span>Titik GPS: <a href="<?= htmlspecialchars(trim($link_maps)) ?>" target="_blank" style="color:#2563eb; text-decoration:none; font-weight:bold;">Buka di Google Maps ↗</a></span>
                        </div>
                    <?php endif; ?>

                    <div class="tanggapan-box">
                        <h4 style="color: #002855; margin-bottom: 10px;">💬 Tanggapan Kelurahan</h4>
                        <p style="margin: 0; font-size: 14px; font-style: <?= $detail_data['isi_tanggapan'] ? 'normal' : 'italic' ?>; color: #334155;">
                            <?= $detail_data['isi_tanggapan'] ? htmlspecialchars($detail_data['isi_tanggapan']) : 'Laporan Anda sedang menunggu evaluasi.' ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
