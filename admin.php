<?php
session_start();
require 'koneksi.php';

// Validasi Admin
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// LOGIKA HAPUS PENGADUAN (Dioptimalkan)
if (isset($_GET['hapus_id'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus_id']);

    // Ambil data foto lalu hapus filenya jika ada
    $q_foto = mysqli_query($koneksi, "SELECT foto FROM pengaduan WHERE id_pengaduan = '$id_hapus'");
    if ($data_foto = mysqli_fetch_assoc($q_foto)) {
        if (!empty($data_foto['foto']) && file_exists('uploads/' . $data_foto['foto'])) {
            unlink('uploads/' . $data_foto['foto']);
        }
    }

    // Hapus dari database
    mysqli_query($koneksi, "DELETE FROM tanggapan WHERE id_pengaduan = '$id_hapus'");
    mysqli_query($koneksi, "DELETE FROM pengaduan WHERE id_pengaduan = '$id_hapus'");
    
    echo "<script>alert('Laporan berhasil dihapus!'); window.location.href='admin.php';</script>";
    exit;
}

// OPTIMASI: Hitung semua statistik dalam 1 Query (Bukan 4 Query terpisah)
$q_stats = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) AS menunggu,
        SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) AS diproses,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) AS selesai
    FROM pengaduan
"));

// Ambil daftar laporan
$query = "SELECT p.*, u.nama_lengkap, u.username FROM pengaduan p JOIN users u ON p.id_user = u.id_user ORDER BY p.id_pengaduan DESC";
$result = mysqli_query($koneksi, $query);

// OPTIMASI: Fungsi Reusable untuk Icon (Menghindari penulisan if-else berulang dalam loop)
function getIkonKategori($kategori) {
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
    <title>Dashboard Admin - SIPELDA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS Dioptimalkan */
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f7fb; margin: 0; display: flex; color: #333; }
        
        .sidebar { width: 260px; height: 100vh; background: #002855; color: white; padding: 30px 20px; position: fixed; left: 0; top: 0; box-sizing: border-box; display: flex; flex-direction: column; }
        .sidebar .logo { font-size: 24px; font-weight: bold; text-align: center; margin-bottom: 40px; display: flex; flex-direction: column; align-items: center; gap: 5px; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; flex: 1; }
        .sidebar-menu a { display: flex; align-items: center; gap: 15px; color: #a9b9cc; text-decoration: none; padding: 12px 20px; border-radius: 8px; font-weight: 500; transition: 0.3s; margin-bottom: 10px; }
        .sidebar-menu a.active, .sidebar-menu a:hover { background: rgba(255,255,255,0.1); color: white; }
        .sidebar-footer a { display: flex; align-items: center; gap: 15px; color: #ff6b6b; text-decoration: none; padding: 12px 20px; border-radius: 8px; font-weight: bold; transition: 0.3s; background: rgba(220,53,69,0.1); }
        .sidebar-footer a:hover { background: #dc3545; color: white; }
        
        .main-content { margin-left: 260px; padding: 30px 40px; width: calc(100% - 260px); box-sizing: border-box; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 24px; color: #002855; }
        
        .admin-profile { display: flex; align-items: center; gap: 10px; background: white; padding: 8px 15px; border-radius: 30px; border: 1px solid #e2e8f0; font-size: 14px; font-weight: bold; color: #002855; text-decoration: none; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; }
        .stat-info h3 { margin: 0; font-size: 30px; color: #002855; }
        .stat-info p { margin: 5px 0 0; color: #64748b; font-size: 14px; font-weight: 500; }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        
        .table-container { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; color: #64748b; padding: 15px 20px; text-align: left; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: middle; }
        tr:hover { background: #f8fafc; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; letter-spacing: 0.5px; }
        .badge-menunggu { background: #fee2e2; color: #dc2626; }
        .badge-diproses { background: #fef3c7; color: #d97706; }
        .badge-selesai { background: #dcfce3; color: #16a34a; }
        
        .badge-privat { background: #1e293b; color: white; margin-left: 5px; padding: 2px 6px; border-radius: 4px; font-size: 10px; }
        .badge-anonim { background: #e2e8f0; color: #475569; margin-left: 5px; padding: 3px 8px; border-radius: 4px; font-size: 10px; }
        
        .btn-action { text-decoration: none; padding: 8px 12px; border-radius: 6px; font-size: 13px; font-weight: bold; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s; }
        .btn-tanggapi { background: #e0e7ff; color: #002855; border: 1px solid #c7d2fe; }
        .btn-tanggapi:hover { background: #c7d2fe; }
        .btn-hapus { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .btn-hapus:hover { background: #fecaca; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-shield-halved" style="font-size: 32px;"></i>
            SIPELDA <span style="font-size: 12px; color: #93c5fd; font-weight:normal;">Portal Admin</span>
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin.php" class="active"><i class="fa-solid fa-border-all"></i> Dashboard</a></li>
            <li><a href="histori_admin.php"><i class="fa-solid fa-clock-rotate-left"></i> Histori Pengaduan</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="logout.php" onclick="return confirm('Keluar dari panel admin?')"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Dashboard Pengaduan Warga</h1>
            <a href="profil.php" class="admin-profile">
                <i class="fa-solid fa-user-shield" style="font-size: 20px;"></i><?= htmlspecialchars($_SESSION['username']); ?>
            </a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info"><h3><?= $q_stats['total'] ?? 0; ?></h3><p>Total Pengaduan</p></div>
                <div class="stat-icon" style="background:#e0e7ff; color:#002855;"><i class="fa-solid fa-clipboard-list"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info"><h3><?= $q_stats['menunggu'] ?? 0; ?></h3><p>Menunggu Respon</p></div>
                <div class="stat-icon" style="background:#fee2e2; color:#dc2626;"><i class="fa-solid fa-clock"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info"><h3><?= $q_stats['diproses'] ?? 0; ?></h3><p>Sedang Diproses</p></div>
                <div class="stat-icon" style="background:#fef3c7; color:#d97706;"><i class="fas fa-spinner"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info"><h3><?= $q_stats['selesai'] ?? 0; ?></h3><p>Laporan Selesai</p></div>
                <div class="stat-icon" style="background:#dcfce3; color:#16a34a;"><i class="fa-solid fa-check-double"></i></div>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tiket & Tanggal</th>
                        <th>Pelapor</th>
                        <th>Kategori & Lokasi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)):
                        $status = strtolower($row['status']);
                        $is_privat = strpos($row['judul_laporan'], '[PRIVAT]') !== false;
                        $is_anonim = strpos($row['judul_laporan'], '[ANONIM]') !== false;

                        // Parsing Judul (Dioptimalkan)
                        $pecah_judul    = explode(' - ', str_replace([' [ANONIM]', ' [PRIVAT]'], '', $row['judul_laporan']), 2);
                        $kategori_murni = $pecah_judul[0];
                        $lokasi_detail  = $pecah_judul[1] ?? '';
                        $icon_kat       = getIkonKategori($kategori_murni); // Panggil fungsi Helper Ikon
                    ?>
                        <tr>
                            <td>
                                <strong>#SPL-<?= $row['id_pengaduan']; ?></strong><br>
                                <span style="color:#94a3b8; font-size:12px;"><?= date('d M Y, H:i', strtotime($row['tgl_pengaduan'])); ?></span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong>
                                <?= $is_anonim ? '<span class="badge-anonim">ANONIM</span>' : '' ?>
                            </td>
                            <td>
                                <strong><i class="fa-solid <?= $icon_kat ?>" style="color:#94a3b8; margin-right:5px;"></i> <?= htmlspecialchars($kategori_murni) ?></strong>
                                <?= $is_privat ? '<span class="badge-privat"><i class="fa-solid fa-lock"></i></span>' : '' ?>

                                <?php if ($lokasi_detail): ?>
                                    <div style="font-size:12px; color:#64748b; margin-top:4px;">
                                        <i class="fa-solid fa-location-dot" style="color:#dc3545;"></i> <?= htmlspecialchars($lokasi_detail) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-<?= $status ?>"><?= strtoupper($status) ?></span></td>
                            <td>
                                <div style="display:flex; gap:8px;">
                                    <a href="tanggapan_admin.php?id=<?= $row['id_pengaduan']; ?>" class="btn-action btn-tanggapi"><i class="fa-solid fa-reply"></i> Proses</a>
                                    <a href="admin.php?hapus_id=<?= $row['id_pengaduan']; ?>" onclick="return confirm('Yakin ingin menghapus laporan ini secara permanen?')" class="btn-action btn-hapus"><i class="fa-solid fa-trash-can"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
