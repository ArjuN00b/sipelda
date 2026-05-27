<?php
session_start();
require 'koneksi.php';

// 1. Proteksi Halaman
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    header("Location: login.php");
    exit;
}

// 2. Ambil ID Pengaduan dari URL (Metode GET)
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: historipengaduan.php");
    exit;
}

$id_pengaduan = mysqli_real_escape_string($koneksi, $_GET['id']);

// 3. Query untuk mengambil data pengaduan yang bersesuaian, digabung dengan data user pelapor
$query = "SELECT pengaduan.*, users.nama_lengkap FROM pengaduan 
          JOIN users ON pengaduan.id_user = users.id_user 
          WHERE pengaduan.id_pengaduan = '$id_pengaduan'";

$result = mysqli_query($koneksi, $query);

// Jika ID pengaduan tidak ada di database, kembalikan ke riwayat
if (mysqli_num_rows($result) === 0) {
    header("Location: historipengaduan.php");
    exit;
}

$data = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pengaduan #SPL-<?= $data['id_pengaduan']; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 40px; color: #1e293b; }
        .container { max-width: 700px; margin: 0 auto; background: white; padding: 35px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .header { border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .header h2 { margin: 0; color: #0c2d6b; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-weight: bold; font-size: 12px; text-transform: uppercase; }
        .badge-proses { background-color: #fef3c7; color: #d97706; }
        .badge-selesai { background-color: #dcfce7; color: #15803d; }
        
        .meta-info { font-size: 14px; color: #64748b; margin-bottom: 20px; background: #f1f5f9; padding: 12px; border-radius: 6px; }
        .meta-info p { margin: 5px 0; }
        
        .report-body { margin-bottom: 30px; }
        .report-body h4 { margin-bottom: 5px; color: #334155; }
        .report-body p { line-height: 1.6; color: #475569; margin-top: 0; }
        
        .image-box { text-align: center; margin: 20px 0; background: #f8fafc; padding: 15px; border: 1px solid #e2e8f0; border-radius: 8px; }
        .image-box img { max-width: 100%; max-height: 350px; border-radius: 6px; object-fit: cover; }
        
        .tanggapan-section { background-color: #f0fdf4; border: 1px solid #bbf7d0; padding: 20px; border-radius: 8px; margin-top: 25px; }
        .tanggapan-section h3 { margin-top: 0; color: #166534; font-size: 16px; display: flex; align-items: center; gap: 8px; }
        
        .btn-back { display: inline-block; background-color: #0c2d6b; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; font-size: 14px; margin-top: 20px; transition: 0.2s; }
        .btn-back:hover { background-color: #1e4088; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>Detail Laporan Pengaduan</h2>
        <span class="badge <?= ($data['status'] == 'selesai') ? 'badge-selesai' : 'badge-proses'; ?>">
            <?= $data['status']; ?>
        </span>
    </div>

    <div class="meta-info">
        <p><strong>ID Pengaduan:</strong> #SPL-<?= $data['id_pengaduan']; ?></p>
        <p><strong>Pelapor:</strong> <?= $data['nama_lengkap']; ?></p>
        <p><strong>Judul Laporan:</strong> <?= $data['judul_laporan']; ?></p>
    </div>

    <div class="report-body">
        <h4>Isi Laporan / Kronologi:</h4>
        <p><?= nl2br($data['isi_laporan']); ?></p>
    </div>

    <?php if (!empty($data['foto'])) : ?>
        <div class="image-box">
            <h4 style="text-align: left; margin-top: 0; color: #334155;">Foto Bukti Kejadian:</h4>
            <img src="asets/uploads/<?= $data['foto']; ?>" alt="Foto Bukti Pengaduan">
        </div>
    <?php endif; ?>

    <div class="tanggapan-section">
        <h3><i class="fa-solid fa-comments"></i> Tanggapan dari Petugas Kelurahan:</h3>
        <p style="margin: 0; color: #1e293b; line-height: 1.5;">
            <?php 
            // Jika kolom tanggapan di database belum diisi oleh petugas/admin
            if (empty($data['tanggapan'])) {
                echo "<span style='color: #94a3b8; italic;'>Laporan Anda sedang ditinjau. Mohon tunggu tanggapan resmi dari pihak kelurahan.</span>";
            } else {
                echo nl2br($data['tanggapan']);
            }
            ?>
        </p>
    </div>

    <a href="historipengaduan.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali ke Riwayat</a>
</div>

</body>
</html>
