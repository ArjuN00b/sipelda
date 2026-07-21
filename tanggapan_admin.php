<?php
session_start();
require 'koneksi.php';

// Validasi Admin
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$id = (int) $_GET['id'];

// Proses Menyimpan Tanggapan Admin
if (isset($_POST['kirim_tanggapan'])) {
    $status    = $_POST['status'];
    $tanggapan = mysqli_real_escape_string($koneksi, $_POST['tanggapan']);
    $id_admin  = $_SESSION['id_user'];

    mysqli_query($koneksi, "UPDATE pengaduan SET status='$status' WHERE id_pengaduan='$id'");

    // Cek apakah sudah ada tanggapan sebelumnya
    if (mysqli_num_rows(mysqli_query($koneksi, "SELECT id_tanggapan FROM tanggapan WHERE id_pengaduan='$id'")) > 0) {
        mysqli_query($koneksi, "UPDATE tanggapan SET isi_tanggapan='$tanggapan', id_admin='$id_admin', tgl_tanggapan=CURRENT_TIMESTAMP WHERE id_pengaduan='$id'");
    } else {
        mysqli_query($koneksi, "INSERT INTO tanggapan (id_pengaduan, id_admin, isi_tanggapan) VALUES ('$id', '$id_admin', '$tanggapan')");
    }

    echo "<script>alert('Laporan berhasil ditanggapi dan status diperbarui!'); window.location.href='admin.php';</script>";
    exit;
}

// Ambil Detail Laporan Warga
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT p.*, u.nama_lengkap, t.isi_tanggapan 
    FROM pengaduan p JOIN users u ON p.id_user = u.id_user 
    LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan
    WHERE p.id_pengaduan = '$id'
"));

// Parsing Konten Laporan (Dioptimalkan dengan Null Coalescing ??)
$pecah_judul = explode(' - ', str_replace([' [ANONIM]', ' [PRIVAT]'], '', $data['judul_laporan']), 2);
$kategori_murni = $pecah_judul[0];
$lokasi_detail  = $pecah_judul[1] ?? '';

$pecah_isi = explode("\n\n📍 Titik Koordinat Peta:\n", $data['isi_laporan']);
$deskripsi_murni = $pecah_isi[0];
$link_maps = isset($pecah_isi[1]) ? trim($pecah_isi[1]) : '';

// Penentuan Ikon dengan Array (Lebih Rapi)
$kat_lower = strtolower($kategori_murni);
$icon_kat = "fa-bullhorn";
$daftar_ikon = [
    'penerangan' => 'fa-lightbulb', 'pju' => 'fa-lightbulb', 'jalan' => 'fa-road',
    'sampah' => 'fa-trash-can', 'kebersihan' => 'fa-trash-can', 'kesehatan' => 'fa-notes-medical',
    'lingkungan' => 'fa-notes-medical', 'keamanan' => 'fa-shield-halved', 'ketertiban' => 'fa-shield-halved',
    'lalu lintas' => 'fa-car', 'parkir' => 'fa-car', 'administrasi' => 'fa-file-signature',
    'birokrasi' => 'fa-file-signature', 'bantuan' => 'fa-handshake-angle', 'bansos' => 'fa-handshake-angle',
    'bencana' => 'fa-triangle-exclamation', 'darurat' => 'fa-triangle-exclamation', 'fasilitas' => 'fa-building'
];

foreach ($daftar_ikon as $kata_kunci => $ikon) {
    if (strpos($kat_lower, $kata_kunci) !== false) {
        $icon_kat = $ikon;
        break; // Hentikan pencarian jika sudah nemu ikon yang cocok
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Proses Laporan - SIPELDA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS Dioptimalkan */
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f7fb; margin: 0; padding: 40px; color: #333; }
        .container { max-width: 900px; margin: auto; }
        
        .btn-back { display: inline-flex; align-items: center; gap: 8px; color: #002855; font-weight: bold; text-decoration: none; margin-bottom: 20px; transition: 0.2s; }
        .btn-back:hover { color: #dc3545; }
        
        .main-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; display: flex; gap: 30px; margin-bottom: 30px; }
        .laporan-detail { flex: 1.2; border-right: 1px solid #e2e8f0; padding-right: 30px; }
        .form-tanggapan { flex: 1; }
        
        .laporan-img { width: 100%; border-radius: 8px; margin-bottom: 15px; border: 1px solid #cbd5e1; cursor: zoom-in; transition: 0.3s; }
        .laporan-img:hover { opacity: 0.9; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        
        .info-box { background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 15px; font-size: 14px; line-height: 1.6; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 8px; font-size: 14px; color: #002855; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 14px; box-sizing: border-box; }
        textarea.form-control { height: 150px; resize: vertical; }
        
        .btn-submit { width: 100%; background: #002855; color: white; border: none; padding: 15px; border-radius: 8px; font-weight: bold; font-size: 15px; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background: #001a3b; }
        
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge-menunggu { background: #fee2e2; color: #dc2626; }
        .badge-diproses { background: #fef3c7; color: #d97706; }
        .badge-selesai { background: #dcfce3; color: #16a34a; }
        
        /* Modal Image */
        .modal-img { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.85); align-items: center; justify-content: center; flex-direction: column; }
        .modal-content-img { max-width: 90%; max-height: 85vh; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.5); }
        .close-modal { color: #fff; font-size: 35px; font-weight: bold; position: absolute; top: 20px; right: 40px; cursor: pointer; transition: 0.2s; }
        .close-modal:hover { color: #dc3545; }
    </style>
</head>

<body>
    <div id="imageModal" class="modal-img">
        <span class="close-modal" onclick="document.getElementById('imageModal').style.display='none'">&times;</span>
        <img class="modal-content-img" id="fullImg">
        <div style="color:white; margin-top:15px; font-family:sans-serif;">Tekan Esc atau Klik Sembarang untuk Menutup</div>
    </div>

    <div class="container">
        <a href="admin.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>

        <div class="main-card">
            <div class="laporan-detail">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px;">
                    <h3 style="margin:0; color:#002855;">Detail Tiket #SPL-<?= $data['id_pengaduan'] ?></h3>
                    <span class="badge badge-<?= strtolower($data['status']) ?>"><?= strtoupper($data['status']) ?></span>
                </div>

                <?php if ($data['foto']): ?>
                    <img src="uploads/<?= $data['foto'] ?>" alt="Bukti Foto" class="laporan-img" onclick="document.getElementById('imageModal').style.display='flex'; document.getElementById('fullImg').src=this.src;" title="Klik untuk memperbesar">
                <?php else: ?>
                    <div style="padding:40px; text-align:center; background:#f1f5f9; border-radius:8px; margin-bottom:15px; color:#94a3b8;">(Tidak melampirkan foto)</div>
                <?php endif; ?>

                <div class="info-box">
                    <strong style="color:#002855; font-size:16px;">
                        <i class="fa-solid <?= $icon_kat ?>"></i> <?= htmlspecialchars($kategori_murni) ?>
                    </strong><br><br>

                    <?php if ($lokasi_detail): ?>
                        <strong style="color:#002855;"><i class="fa-solid fa-location-dot" style="color:#dc3545;"></i> Detail Lokasi:</strong><br>
                        <span style="color:#475569;"><?= htmlspecialchars($lokasi_detail) ?></span><br><br>
                    <?php endif; ?>

                    <strong style="color:#002855;"><i class="fa-regular fa-file-lines"></i> Kronologi Kejadian:</strong><br>
                    <span style="color:#475569;"><?= nl2br(htmlspecialchars($deskripsi_murni)) ?></span>
                </div>

                <?php if ($link_maps): ?>
                    <a href="<?= htmlspecialchars($link_maps) ?>" target="_blank" style="display:block; text-align:center; padding:10px; background:#eff6ff; color:#2563eb; text-decoration:none; border-radius:8px; font-weight:bold; border: 1px dashed #bfdbfe;">
                        <i class="fa-solid fa-map-location-dot"></i> Buka Titik Lokasi di Maps
                    </a>
                <?php endif; ?>
            </div>

            <div class="form-tanggapan">
                <h3 style="margin:0 0 20px; color:#002855; border-bottom: 2px solid #e2e8f0; padding-bottom:10px;">Form Tindak Lanjut</h3>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Update Status Penanganan</label>
                        <select name="status" class="form-control" required>
                            <option value="menunggu" <?= ($data['status'] == 'menunggu') ? 'selected' : '' ?>> Menunggu (Belum diproses)</option>
                            <option value="diproses" <?= ($data['status'] == 'diproses') ? 'selected' : '' ?>> Diproses (Sedang ditangani tim)</option>
                            <option value="selesai" <?= ($data['status'] == 'selesai') ? 'selected' : '' ?>> Selesai (Masalah tertangani)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tulis Tanggapan Resmi</label> 
                        <textarea name="tanggapan" class="form-control" placeholder="Tuliskan keterangan mengenai penanganan masalah ini ...." required><?= htmlspecialchars($data['isi_tanggapan'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" name="kirim_tanggapan" class="btn-submit">
                        <i class="fa-solid fa-paper-plane"></i> Simpan & Perbarui Laporan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // JS Dioptimalkan untuk fitur Modal (Zoom Gambar)
        const modal = document.getElementById("imageModal");
        const closeModal = () => modal.style.display = "none";

        modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === "Escape") closeModal(); });
    </script>
</body>
</html>
