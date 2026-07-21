<?php
session_start();
require '../config/koneksi.php';

$id_pengaduan = $_GET['id'];
$query = "SELECT p.*, t.isi_tanggapan, t.tgl_tanggapan FROM pengaduan p LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan WHERE p.id_pengaduan = '$id_pengaduan'";
$result = mysqli_query($koneksi, $query);
$data = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html>

<head>
    <title>Detail Laporan</title>
</head>

<body style="background:#f4f7f6; padding:40px;">
    <div style="max-width: 800px; margin: auto; background: #fff; padding:30px; border-radius:10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <a href="historipengaduan.php">⬅ Kembali ke Riwayat</a>
        <h2 style="margin-top:20px;">Detail Laporan: #SPL-<?= $data['id_pengaduan'] ?></h2>

        <div style="display:flex; gap: 20px; margin-top:20px;">
            <div style="flex:1;">
                <?php if ($data['foto']): ?>
                    <img src="../uploads/<?= $data['foto'] ?>" style="width:100%; border-radius:8px;">
                <?php else: ?>
                    <div style="background:#eee; padding:50px; text-align:center;">Tidak ada foto</div>
                <?php endif; ?>
            </div>
            <div style="flex:1;">
                <p><strong>Status:</strong> <span style="padding:5px 10px; background:#ffc107; border-radius:5px; font-weight:bold;"><?= strtoupper($data['status']) ?></span></p>
                <p><strong>Kategori:</strong> <?= $data['judul_laporan'] ?></p>
                <p><strong>Lokasi (Detail):</strong> <?= $data['isi_laporan'] ?></p>
                <hr>
                <h4>Tanggapan Kelurahan:</h4>
                <?php if ($data['isi_tanggapan']): ?>
                    <div style="background:#d4edda; padding:15px; border-radius:5px;">
                        <p><?= $data['isi_tanggapan'] ?></p>
                        <small>Ditanggapi pada: <?= $data['tgl_tanggapan'] ?></small>
                    </div>
                <?php else: ?>
                    <div style="background:#f8d7da; padding:15px; border-radius:5px; color:#721c24;">
                        Belum ada tanggapan dari petugas kelurahan.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>
