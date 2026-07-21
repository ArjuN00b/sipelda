<?php
session_start();
require 'koneksi.php';

// Validasi Admin
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$id = (int) $_GET['id'];
$tanggapan_sukses = false;
$hapus_sukses = false;

// Hapus Laporan dari Detail Tanggapan
if (isset($_POST['hapus_laporan'])) {
    // Hapus file gambar jika ada
    $q_foto = mysqli_query($koneksi, "SELECT foto FROM pengaduan WHERE id_pengaduan = '$id'");
    if ($data_foto = mysqli_fetch_assoc($q_foto)) {
        if (!empty($data_foto['foto']) && file_exists(dirname(__DIR__) . '/uploads/' . $data_foto['foto'])) {
            unlink(dirname(__DIR__) . '/uploads/' . $data_foto['foto']);
        }
    }
    // Hapus relasi
    mysqli_query($koneksi, "DELETE FROM tanggapan WHERE id_pengaduan = '$id'");
    mysqli_query($koneksi, "DELETE FROM pengaduan WHERE id_pengaduan = '$id'");
    $hapus_sukses = true;
}

// Proses Menyimpan Tanggapan Admin
if (isset($_POST['kirim_tanggapan'])) {
    $status    = $_POST['status'];
    $id_admin  = $_SESSION['id_user'];
    
    // Auto-tanggapan atau manual tanggapan
    if (isset($_POST['manual_tanggapan_check'])) {
        $tanggapan = mysqli_real_escape_string($koneksi, $_POST['tanggapan']);
    } else {
        if ($status === 'menunggu') {
            $tanggapan = "Laporan Anda telah masuk ke sistem antrean kelurahan dan sedang menunggu verifikasi dari petugas terkait.";
        } elseif ($status === 'diproses') {
            $tanggapan = "Laporan Anda sedang ditindaklanjuti dan dikoordinasikan dengan tim teknis lapangan kelurahan untuk penanganan secepatnya.";
        } else {
            $tanggapan = "Laporan pengaduan Anda telah sukses diselesaikan dan ditangani sepenuhnya oleh tim teknis kelurahan. Terima kasih atas partisipasi Anda.";
        }
    }

    mysqli_query($koneksi, "UPDATE pengaduan SET status='$status' WHERE id_pengaduan='$id'");

    // Cek apakah sudah ada tanggapan sebelumnya
    if (mysqli_num_rows(mysqli_query($koneksi, "SELECT id_tanggapan FROM tanggapan WHERE id_pengaduan='$id'")) > 0) {
        mysqli_query($koneksi, "UPDATE tanggapan SET isi_tanggapan='$tanggapan', id_admin='$id_admin', tgl_tanggapan=CURRENT_TIMESTAMP WHERE id_pengaduan='$id'");
    } else {
        mysqli_query($koneksi, "INSERT INTO tanggapan (id_pengaduan, id_admin, isi_tanggapan) VALUES ('$id', '$id_admin', '$tanggapan')");
    }

    $tanggapan_sukses = true;
}

// Ambil Detail Laporan Warga
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT p.*, u.nama_lengkap, t.isi_tanggapan 
    FROM pengaduan p JOIN users u ON p.id_user = u.id_user 
    LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan
    WHERE p.id_pengaduan = '$id'
"));

if (!$data && !$hapus_sukses) {
    echo "<script>alert('Laporan tidak ditemukan!'); window.location.href='admin.php';</script>";
    exit;
}

// Parsing Konten Laporan
$kategori_murni = "";
$lokasi_detail = "";
$deskripsi_murni = "";
$link_maps = "";
$map_lat = null;
$map_lng = null;

if ($data) {
    $pecah_judul = explode(' - ', str_replace([' [ANONIM]', ' [PRIVAT]'], '', $data['judul_laporan']), 2);
    $kategori_murni = $pecah_judul[0];
    $lokasi_detail  = $pecah_judul[1] ?? '';

    $pecah_isi = explode("\n\n📍 Titik Koordinat Peta:\n", $data['isi_laporan']);
    $deskripsi_murni = $pecah_isi[0];
    $link_maps = isset($pecah_isi[1]) ? trim($pecah_isi[1]) : '';

    if ($link_maps && preg_match('/q=(-?\d+\.\d+),(-?\d+\.\d+)/', $link_maps, $matches)) {
        $map_lat = $matches[1];
        $map_lng = $matches[2];
    }
}

// Penentuan Ikon
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
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Laporan - SIPELDA</title>
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
            background: #f4f7fb; 
            margin: 0; 
            padding: 40px; 
            color: #1e293b; 
            font-size: 16px;
            animation: pageFadeIn 0.4s ease-out;
        }

        .container { max-width: 1000px; margin: auto; }
        
        .btn-back { display: inline-flex; align-items: center; gap: 8px; color: #002855; font-weight: bold; text-decoration: none; margin-bottom: 25px; transition: 0.2s; font-size: 16px; }
        .btn-back:hover { color: #dc3545; }
        
        .main-card { background: white; padding: 35px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; display: flex; gap: 35px; margin-bottom: 30px; }
        .laporan-detail { flex: 1.2; border-right: 1px solid #e2e8f0; padding-right: 35px; }
        .form-tanggapan { flex: 1; }
        
        .laporan-img { width: 100%; border-radius: 12px; margin-bottom: 20px; border: 1px solid #cbd5e1; cursor: pointer; transition: 0.3s; }
        .laporan-img:hover { opacity: 0.9; box-shadow: 0 4px 15px rgba(0,0,0,0.15); }
        
        .info-box { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 20px; font-size: 15px; line-height: 1.6; }
        
        .form-group { margin-bottom: 22px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 8px; font-size: 15px; color: #002855; }
        .form-control { width: 100%; padding: 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-family: inherit; font-size: 15px; box-sizing: border-box; outline: none; background: #f8fafc; transition: 0.2s; }
        .form-control:focus { border-color: #002855; background: white; }
        textarea.form-control { height: 140px; resize: vertical; }

        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            background: #fffbeb;
            border: 1px solid #fef3c7;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .btn-submit { width: 100%; background: #002855; color: white; border: none; padding: 16px; border-radius: 10px; font-weight: bold; font-size: 16px; cursor: pointer; transition: 0.3s; margin-bottom: 12px; }
        .btn-submit:hover { background: #001a3b; }

        .btn-danger-action { width: 100%; background: #fee2e2; color: #dc2626; border: 1.5px solid #fecaca; padding: 14px; border-radius: 10px; font-weight: bold; font-size: 15px; cursor: pointer; transition: 0.2s; }
        .btn-danger-action:hover { background: #dc2626; color: white; }
        
        .badge { padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .badge-menunggu { background: #fee2e2; color: #dc2626; }
        .badge-diproses { background: #fef3c7; color: #d97706; }
        .badge-selesai { background: #dcfce3; color: #16a34a; }

        .map-wrapper {
            height: 200px;
            width: 100%;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            margin-bottom: 20px;
        }
        
        /* Lightbox Zoom */
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
        .lightbox-content { max-width: 90%; max-height: 85vh; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); object-fit: contain; animation: zoomIn 0.25s ease-out; }
        .lightbox-close { position: absolute; top: 25px; right: 35px; color: #ffffff; font-size: 44px; font-weight: bold; cursor: pointer; z-index: 100000; transition: 0.2s; line-height: 1; }
        .lightbox-close:hover { color: #f87171; transform: scale(1.15); }

        /* Modal success/delete */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0, 20, 50, 0.7);
            backdrop-filter: blur(6px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            animation: fadeIn 0.3s ease-in-out;
        }
        .modal-box {
            background: #ffffff;
            padding: 40px;
            border-radius: 20px;
            max-width: 440px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.25);
            animation: slideUp 0.3s ease-in-out;
        }
        .modal-icon-success {
            width: 80px; height: 80px;
            background-color: #dcfce7;
            color: #16a34a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            margin: 0 auto 20px;
        }
        .modal-icon-danger {
            width: 80px; height: 80px;
            background-color: #fee2e2;
            color: #dc2626;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            margin: 0 auto 20px;
        }
        .btn-modal-close { display: block; width: 100%; padding: 16px; background-color: #002855; color: white; border-radius: 10px; font-size: 16px; font-weight: bold; text-decoration: none; box-sizing: border-box; text-align: center; }
        .modal-button-group { display: flex; gap: 12px; }
        .btn-modal-cancel { flex: 1; padding: 14px; background-color: #f1f5f9; color: #475569; border-radius: 10px; font-size: 15px; font-weight: bold; border: 1px solid #cbd5e1; cursor: pointer; transition: 0.2s; }
        .btn-modal-delete { flex: 1; padding: 14px; background-color: #dc2626; color: white; border-radius: 10px; font-size: 15px; font-weight: bold; border: none; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3); }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes zoomIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }

        @media (max-width: 768px) {
            .main-card { flex-direction: column; }
            .laporan-detail { border-right: none; padding-right: 0; }
        }
    </style>
</head>
<body>

    <!-- MODAL SUKSES TANGGAPAN -->
    <?php if ($tanggapan_sukses): ?>
        <div class="modal-overlay">
            <div class="modal-box">
                <div class="modal-icon-success"><i class="fa-solid fa-circle-check"></i></div>
                <h3>Tanggapan Disimpan!</h3>
                <p>Status laporan telah diperbarui and tanggapan berhasil dikirimkan ke pelapor.</p>
                <a href="admin.php" class="btn-modal-close">Kembali ke Dashboard</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL SUKSES HAPUS -->
    <?php if ($hapus_sukses): ?>
        <div class="modal-overlay">
            <div class="modal-box">
                <div class="modal-icon-success"><i class="fa-solid fa-circle-check"></i></div>
                <h3>Laporan Dihapus!</h3>
                <p>Laporan pengaduan warga telah berhasil dihapus permanen dari basis data.</p>
                <a href="admin.php" class="btn-modal-close">Kembali ke Dashboard</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL CONFIRM HAPUS LAPORAN -->
    <div id="modal-confirm-hapus" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="modal-icon-danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <h3>Hapus Laporan Ini?</h3>
            <p>Tindakan ini akan menghapus laporan dan seluruh berkas pendukungnya secara permanen dari server.</p>
            <div class="modal-button-group">
                <button type="button" class="btn-modal-cancel" onclick="closeConfirmHapus()">Batal</button>
                <form method="POST" action="" style="flex:1;">
                    <button type="submit" name="hapus_laporan" class="btn-modal-delete">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>

    <!-- LIGHTBOX ZOOM -->
    <div id="image-lightbox-modal" class="lightbox-overlay" onclick="closeLightbox(event)">
        <span class="lightbox-close" onclick="closeLightboxDirect()">&times;</span>
        <img id="lightbox-img" class="lightbox-content" src="" alt="Zoom Foto">
    </div>

    <div class="container">
        <a href="admin.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>

        <?php if ($data): ?>
            <div class="main-card">
                <div class="laporan-detail">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                        <h3 style="margin:0; color:#002855; font-size:22px; font-weight:800;">Detail Tiket #SPL-<?= $data['id_pengaduan'] ?></h3>
                        <span class="badge badge-<?= strtolower($data['status']) ?>"><?= strtoupper($data['status']) ?></span>
                    </div>

                    <?php if ($data['foto']): ?>
                        <?php 
                            $ext_file = strtolower(pathinfo($data['foto'], PATHINFO_EXTENSION));
                            $is_video = in_array($ext_file, ['mp4', 'webm', 'mov', 'mkv']);
                        ?>
                        <?php if ($is_video): ?>
                            <video src="../uploads/<?= $data['foto'] ?>" controls style="width:100%; border-radius:12px; margin-bottom:20px; border: 1px solid #cbd5e1;"></video>
                        <?php else: ?>
                            <img src="../uploads/<?= $data['foto'] ?>" alt="Bukti Foto" class="laporan-img" onclick="openLightbox(this.src)" title="Klik untuk memperbesar">
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="padding:50px; text-align:center; background:#f1f5f9; border-radius:12px; margin-bottom:20px; color:#94a3b8; border: 1px dashed #cbd5e1;">(Tidak melampirkan foto bukti)</div>
                    <?php endif; ?>

                    <?php if ($map_lat && $map_lng): ?>
                        <div id="leaflet-detail-map" class="map-wrapper"></div>
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
                        <a href="<?= htmlspecialchars($link_maps) ?>" target="_blank" style="display:block; text-align:center; padding:12px; background:#eff6ff; color:#2563eb; text-decoration:none; border-radius:10px; font-weight:bold; border: 1px dashed #bfdbfe; font-size:14px;">
                            <i class="fa-solid fa-map-location-dot"></i> Buka Koordinat di Google Maps
                        </a>
                    <?php endif; ?>
                </div>

                <div class="form-tanggapan">
                    <h3 style="margin:0 0 20px; color:#002855; border-bottom: 2px solid #e2e8f0; padding-bottom:12px; font-weight:800;">Form Tindak Lanjut</h3>

                    <form method="POST" action="" id="tanggapan-form">
                        <div class="form-group">
                            <label>Update Status Penanganan</label>
                            <select name="status" id="status-select" class="form-control" required>
                                <option value="menunggu" <?= ($data['status'] == 'menunggu') ? 'selected' : '' ?>> Menunggu (Belum diproses)</option>
                                <option value="diproses" <?= ($data['status'] == 'diproses') ? 'selected' : '' ?>> Diproses (Sedang ditangani tim)</option>
                                <option value="selesai" <?= ($data['status'] == 'selesai') ? 'selected' : '' ?>> Selesai (Masalah tertangani)</option>
                            </select>
                        </div>

                        <!-- Checkbox Manual Tanggapan -->
                        <div class="checkbox-container">
                            <input type="checkbox" id="manual_tanggapan_check" name="manual_tanggapan_check" value="1" onchange="toggleManualTextArea(this)">
                            <label for="manual_tanggapan_check" style="cursor:pointer; color:#78350f;">Beri tanggapan kustom manual</label>
                        </div>

                        <div class="form-group" id="tanggapan-text-wrapper" style="display: none;">
                            <label>Tulis Tanggapan Resmi</label> 
                            <textarea name="tanggapan" id="tanggapan-textarea" class="form-control" placeholder="Tuliskan tanggapan atau instruksi tindak lanjut di sini..."><?= htmlspecialchars($data['isi_tanggapan'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" name="kirim_tanggapan" class="btn-submit">
                            <i class="fa-solid fa-paper-plane"></i> Simpan & Perbarui Laporan
                        </button>
                    </form>

                    <div style="border-top:2px solid #f1f5f9; padding-top:20px; margin-top:25px;">
                        <button type="button" class="btn-danger-action" onclick="openConfirmHapus()"><i class="fa-solid fa-trash-can"></i> Hapus Laporan Permanen</button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function openConfirmHapus() {
            document.getElementById('modal-confirm-hapus').style.display = 'flex';
        }
        function closeConfirmHapus() {
            document.getElementById('modal-confirm-hapus').style.display = 'none';
        }

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

        function toggleManualTextArea(checkbox) {
            const wrapper = document.getElementById('tanggapan-text-wrapper');
            const textarea = document.getElementById('tanggapan-textarea');
            if (checkbox.checked) {
                wrapper.style.display = 'block';
                textarea.required = true;
                textarea.focus();
            } else {
                wrapper.style.display = 'none';
                textarea.required = false;
            }
        }

        // Inisialisasi Map Leaflet Detail jika koordinat tersedia
        <?php if ($map_lat && $map_lng): ?>
            (function() {
                const lat = <?= $map_lat ?>;
                const lng = <?= $map_lng ?>;
                const detailMap = L.map('leaflet-detail-map').setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(detailMap);
                L.marker([lat, lng]).addTo(detailMap);
            })();
        <?php endif; ?>
    </script>
</body>
</html>
