<?php
session_start();
require '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: admin.php");
    exit;
}

$id_admin = $_SESSION['id_user'];

// ============================================================
//  HAPUS LAPORAN (dari tombol hapus di halaman ini)
// ============================================================
if (isset($_GET['hapus_id'])) {
    $id_hapus = (int) $_GET['hapus_id'];
    $q_foto = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT foto FROM pengaduan WHERE id_pengaduan = '$id_hapus'"));
    if ($q_foto && !empty($q_foto['foto']) && file_exists('../uploads/' . $q_foto['foto'])) {
        unlink('../uploads/' . $q_foto['foto']);
    }
    mysqli_query($koneksi, "DELETE FROM tanggapan WHERE id_pengaduan = '$id_hapus'");
    mysqli_query($koneksi, "DELETE FROM pengaduan WHERE id_pengaduan = '$id_hapus'");
    header("Location: admin.php?hapus=sukses");
    exit;
}

// ============================================================
//  SIMPAN TANGGAPAN & UPDATE STATUS
// ============================================================
if (isset($_POST['kirim_tanggapan'])) {
    $status_baru   = $_POST['status'];
    $mode_tanggapan = $_POST['mode_tanggapan'] ?? 'auto'; // 'auto' | 'manual'
    $id_admin_ses  = $_SESSION['id_user'];

    // Tanggapan otomatis berdasarkan status
    $auto_tanggapan = [
        'menunggu' => 'Laporan Anda telah kami terima dan saat ini sedang dalam antrian evaluasi petugas kelurahan. Mohon tunggu kabar selanjutnya.',
        'diproses' => 'Laporan Anda sedang dalam proses penanganan aktif oleh tim petugas kelurahan. Kami akan segera menyelesaikan masalah ini.',
        'selesai'  => 'Laporan Anda telah berhasil ditangani dan diselesaikan oleh tim petugas kelurahan. Terima kasih atas partisipasi Anda!'
    ];

    if ($mode_tanggapan === 'manual') {
        $isi_tanggapan = mysqli_real_escape_string($koneksi, $_POST['tanggapan_manual']);
    } else {
        $isi_tanggapan = mysqli_real_escape_string($koneksi, $auto_tanggapan[$status_baru] ?? $auto_tanggapan['menunggu']);
    }

    mysqli_query($koneksi, "UPDATE pengaduan SET status='$status_baru' WHERE id_pengaduan='$id'");

    if (mysqli_num_rows(mysqli_query($koneksi, "SELECT id_tanggapan FROM tanggapan WHERE id_pengaduan='$id'")) > 0) {
        mysqli_query($koneksi, "UPDATE tanggapan SET isi_tanggapan='$isi_tanggapan', id_admin='$id_admin_ses', tgl_tanggapan=CURRENT_TIMESTAMP WHERE id_pengaduan='$id'");
    } else {
        mysqli_query($koneksi, "INSERT INTO tanggapan (id_pengaduan, id_admin, isi_tanggapan) VALUES ('$id', '$id_admin_ses', '$isi_tanggapan')");
    }

    header("Location: admin.php?update=sukses");
    exit;
}

// Ambil Detail Laporan
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT p.*, u.nama_lengkap, u.username, t.isi_tanggapan
    FROM pengaduan p
    JOIN users u ON p.id_user = u.id_user
    LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan
    WHERE p.id_pengaduan = '$id'
"));

if (!$data) {
    header("Location: admin.php");
    exit;
}

$pecah_judul    = explode(' - ', str_replace([' [ANONIM]', ' [PRIVAT]'], '', $data['judul_laporan']), 2);
$kategori_murni = $pecah_judul[0];
$lokasi_detail  = $pecah_judul[1] ?? '';

$pecah_isi       = explode("\n\n📍 Titik Koordinat Peta:\n", $data['isi_laporan']);
$deskripsi_murni = $pecah_isi[0];
$link_maps       = isset($pecah_isi[1]) ? trim($pecah_isi[1]) : '';

$card_lat = $card_lng = null;
if ($link_maps && preg_match('/q=(-?\d+\.\d+),(-?\d+\.\d+)/', $link_maps, $m)) {
    $card_lat = $m[1];
    $card_lng = $m[2];
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
foreach ($daftar_ikon as $kata_kunci => $ikon) {
    if (strpos($kat_lower, $kata_kunci) !== false) { $icon_kat = $ikon; break; }
}

$is_privat = strpos($data['judul_laporan'], '[PRIVAT]') !== false;
$ext_file  = strtolower(pathinfo($data['foto'], PATHINFO_EXTENSION));
$is_video  = in_array($ext_file, ['mp4', 'webm', 'mov', 'mkv']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Proses Laporan #SPL-<?= $id ?> - SIPELDA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        @keyframes pageFadeIn {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f4f7fb;
            margin: 0;
            display: flex;
            color: #1e293b;
            font-size: 16px;
        }

        .sidebar {
            width: 270px;
            height: 100vh;
            background: #002855;
            color: white;
            padding: 30px 20px;
            position: fixed;
            left: 0; top: 0;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }

        .sidebar .logo {
            font-size: 26px;
            font-weight: 800;
            text-align: center;
            margin-bottom: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .sidebar .logo i { font-size: 38px; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; flex: 1; }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 14px;
            color: #a9b9cc;
            text-decoration: none;
            padding: 14px 18px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: 0.3s;
            margin-bottom: 8px;
        }

        .sidebar-menu a i { font-size: 20px; width: 22px; text-align: center; }

        .sidebar-menu a.active,
        .sidebar-menu a:hover { background: rgba(255,255,255,0.12); color: white; }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 14px;
            color: #ff6b6b;
            text-decoration: none;
            padding: 14px 18px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 15px;
            transition: 0.3s;
            background: rgba(220,53,69,0.1);
        }

        .sidebar-footer a:hover { background: #dc3545; color: white; }

        .main-content {
            margin-left: 270px;
            padding: 35px 45px;
            width: calc(100% - 270px);
            box-sizing: border-box;
            animation: pageFadeIn 0.4s ease-out;
        }

        .page-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #002855;
            font-weight: 700;
            font-size: 16px;
            text-decoration: none;
            padding: 10px 20px;
            background: white;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            transition: 0.2s;
        }

        .btn-back:hover { background: #f1f5f9; }

        .btn-hapus-laporan {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #dc2626;
            font-weight: 700;
            font-size: 15px;
            text-decoration: none;
            padding: 10px 20px;
            background: #fee2e2;
            border-radius: 10px;
            border: 1px solid #fca5a5;
            transition: 0.2s;
        }

        .btn-hapus-laporan:hover { background: #dc2626; color: white; }

        .layout-grid {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 28px;
            align-items: start;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
            padding: 30px;
        }

        .card-title {
            font-size: 19px;
            font-weight: 800;
            color: #002855;
            margin: 0 0 22px;
            padding-bottom: 14px;
            border-bottom: 2px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .media-wrapper {
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: 0.2s;
        }

        .media-wrapper:hover { opacity: 0.9; }
        .media-wrapper img, .media-wrapper video { width: 100%; display: block; max-height: 350px; object-fit: cover; }

        .mini-map {
            width: 100%;
            height: 200px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            margin-bottom: 16px;
        }

        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 20px;
            font-size: 15px;
            line-height: 1.65;
            color: #334155;
            margin-bottom: 16px;
        }

        .info-box strong { color: #002855; }

        .maps-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 18px;
            background: #eff6ff;
            border: 1px dashed #bfdbfe;
            border-radius: 10px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 16px;
            transition: 0.2s;
        }

        .maps-link:hover { background: #dbeafe; }

        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 800;
        }

        .badge-menunggu { background: #fee2e2; color: #dc2626; }
        .badge-diproses { background: #fef3c7; color: #d97706; }
        .badge-selesai  { background: #dcfce3; color: #16a34a; }

        .form-group { margin-bottom: 22px; }

        .form-group label {
            display: block;
            font-weight: 700;
            font-size: 15px;
            color: #002855;
            margin-bottom: 10px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            font-family: inherit;
            font-size: 15px;
            box-sizing: border-box;
            transition: 0.2s;
            outline: none;
        }

        .form-control:focus { border-color: #002855; box-shadow: 0 0 0 3px rgba(0,40,85,0.1); }

        textarea.form-control { height: 130px; resize: vertical; }

        /* CHECKLIST TANGGAPAN MANUAL */
        .tanggapan-toggle {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            margin-bottom: 16px;
            transition: 0.2s;
        }

        .tanggapan-toggle:hover { border-color: #002855; background: #eff6ff; }

        .tanggapan-toggle input[type="checkbox"] {
            width: 22px;
            height: 22px;
            accent-color: #002855;
            cursor: pointer;
        }

        .tanggapan-toggle label {
            font-size: 15px;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
            margin: 0;
        }

        .manual-area { display: none; }

        .auto-preview {
            background: #eff6ff;
            border: 1px dashed #93c5fd;
            border-radius: 10px;
            padding: 14px 18px;
            font-size: 14px;
            color: #1d4ed8;
            line-height: 1.6;
            margin-bottom: 16px;
            font-style: italic;
        }

        .auto-preview strong { font-style: normal; color: #002855; }

        .btn-submit {
            width: 100%;
            background: #002855;
            color: white;
            border: none;
            padding: 16px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover { background: #001a3b; }

        /* LIGHTBOX */
        .lightbox-overlay {
            display: none;
            position: fixed;
            z-index: 99999;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.87);
            backdrop-filter: blur(8px);
            justify-content: center;
            align-items: center;
        }

        .lightbox-content {
            max-width: 90%;
            max-height: 86vh;
            border-radius: 12px;
            object-fit: contain;
        }

        .lightbox-close {
            position: absolute;
            top: 22px; right: 32px;
            color: white;
            font-size: 46px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
            line-height: 1;
        }

        .lightbox-close:hover { color: #f87171; transform: scale(1.15); }

        .pelapor-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 14px 18px;
            font-size: 15px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #15803d;
        }

        .tiket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .tiket-header h2 { margin: 0; font-size: 22px; color: #002855; font-weight: 800; }
    </style>
</head>
<body>

    <!-- LIGHTBOX -->
    <div id="lightbox-modal" class="lightbox-overlay" onclick="closeLightbox(event)">
        <span class="lightbox-close" onclick="document.getElementById('lightbox-modal').style.display='none'">&times;</span>
        <img id="lightbox-img" class="lightbox-content" src="" alt="">
    </div>

    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-shield-halved"></i>
            SIPELDA <span style="font-size:13px; color:#93c5fd; font-weight:500;">Portal Admin</span>
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin.php"><i class="fa-solid fa-border-all"></i> Dashboard</a></li>
            <li><a href="histori_admin.php"><i class="fa-solid fa-clock-rotate-left"></i> Histori Pengaduan</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="../auth/logout.php" onclick="return confirm('Yakin keluar dari panel admin SIPELDA?')">
                <i class="fa-solid fa-right-from-bracket"></i> Keluar
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-top-bar">
            <a href="admin.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>
            <a href="tanggapan_admin.php?id=<?= $id ?>&hapus_id=<?= $id ?>"
               onclick="return confirm('Yakin menghapus laporan #SPL-<?= $id ?> secara permanen? Tindakan ini tidak dapat dibatalkan.')"
               class="btn-hapus-laporan">
                <i class="fa-solid fa-trash-can"></i> Hapus Laporan Ini
            </a>
        </div>

        <div class="layout-grid">
            <!-- KOLOM KIRI: DETAIL LAPORAN -->
            <div class="card">
                <div class="tiket-header">
                    <h2><i class="fa-solid <?= $icon_kat ?>" style="color:#64748b;"></i> <?= htmlspecialchars($kategori_murni) ?></h2>
                    <span class="badge badge-<?= strtolower($data['status']) ?>"><?= strtoupper($data['status']) ?></span>
                </div>

                <div class="pelapor-box">
                    <i class="fa-solid fa-user-circle" style="font-size:22px;"></i>
                    <div>
                        <strong><?= htmlspecialchars($data['nama_lengkap']) ?></strong>
                        <span style="color:#64748b; font-size:14px; margin-left:8px;">@<?= htmlspecialchars($data['username']) ?></span>
                        <?= $is_privat ? '<span style="background:#1e293b; color:white; padding:2px 8px; border-radius:4px; font-size:12px; margin-left:8px;"><i class="fa-solid fa-lock"></i> PRIVAT</span>' : '' ?>
                        <div style="font-size:13px; color:#64748b; margin-top:3px;"><i class="fa-regular fa-calendar-days"></i> <?= date('d M Y, H:i', strtotime($data['tgl_pengaduan'])) ?> | <strong>#SPL-<?= $data['id_pengaduan'] ?></strong></div>
                    </div>
                </div>

                <?php if ($data['foto']): ?>
                    <div class="media-wrapper" onclick="openLightbox('../uploads/<?= $data['foto'] ?>')">
                        <?php if ($is_video): ?>
                            <video src="../uploads/<?= $data['foto'] ?>" controls style="width:100%;max-height:350px;"></video>
                        <?php else: ?>
                            <img src="../uploads/<?= $data['foto'] ?>" alt="Bukti Foto" title="Klik untuk memperbesar">
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="padding:40px; text-align:center; background:#f1f5f9; border-radius:12px; margin-bottom:20px; color:#94a3b8; font-size:16px;">
                        <i class="fa-regular fa-image" style="font-size:32px; display:block; margin-bottom:10px;"></i>
                        (Tidak melampirkan bukti foto)
                    </div>
                <?php endif; ?>

                <?php if ($card_lat && $card_lng): ?>
                    <div id="mini-map-detail" class="mini-map"></div>
                <?php endif; ?>

                <?php if ($lokasi_detail): ?>
                    <div class="info-box">
                        <strong><i class="fa-solid fa-location-dot" style="color:#dc3545;"></i> Alamat / Lokasi Kejadian:</strong><br>
                        <?= htmlspecialchars($lokasi_detail) ?>
                    </div>
                <?php endif; ?>

                <?php if ($link_maps): ?>
                    <a href="<?= htmlspecialchars($link_maps) ?>" target="_blank" class="maps-link">
                        <i class="fa-solid fa-map-location-dot"></i> Buka Titik GPS di Google Maps ↗
                    </a>
                <?php endif; ?>

                <div class="info-box">
                    <strong><i class="fa-regular fa-file-lines"></i> Kronologi / Deskripsi Kejadian:</strong><br><br>
                    <?= nl2br(htmlspecialchars($deskripsi_murni)) ?>
                </div>
            </div>

            <!-- KOLOM KANAN: FORM TINDAK LANJUT -->
            <div class="card">
                <div class="card-title"><i class="fa-solid fa-pen-to-square"></i> Form Tindak Lanjut</div>

                <form method="POST" action="" id="form-tanggapan">
                    <div class="form-group">
                        <label>Update Status Penanganan</label>
                        <select name="status" class="form-control" id="select-status" onchange="updateAutoPreview()">
                            <option value="menunggu" <?= ($data['status'] == 'menunggu') ? 'selected' : '' ?>>⏳ Menunggu (Belum diproses)</option>
                            <option value="diproses" <?= ($data['status'] == 'diproses') ? 'selected' : '' ?>>🔧 Diproses (Sedang ditangani)</option>
                            <option value="selesai"  <?= ($data['status'] == 'selesai')  ? 'selected' : '' ?>>✅ Selesai (Masalah tertangani)</option>
                        </select>
                    </div>

                    <!-- AUTO PREVIEW TANGGAPAN -->
                    <div id="auto-preview-box" class="auto-preview">
                        <strong>Preview Tanggapan Otomatis:</strong><br>
                        <span id="auto-preview-text"></span>
                    </div>

                    <!-- CHECKLIST UNTUK TANGGAPAN MANUAL -->
                    <div class="tanggapan-toggle">
                        <input type="checkbox" id="cb-manual" name="cb_manual" onchange="toggleManual(this)">
                        <label for="cb-manual"><i class="fa-solid fa-pen"></i> Tulis Tanggapan Manual (Opsional)</label>
                    </div>

                    <div class="manual-area" id="manual-area">
                        <div class="form-group">
                            <label>Tanggapan Manual / Khusus</label>
                            <textarea name="tanggapan_manual" class="form-control" id="textarea-manual"
                                      placeholder="Tuliskan keterangan khusus mengenai penanganan masalah ini..."><?= htmlspecialchars($data['isi_tanggapan'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <input type="hidden" name="mode_tanggapan" id="input-mode" value="auto">

                    <button type="submit" name="kirim_tanggapan" class="btn-submit">
                        <i class="fa-solid fa-paper-plane"></i> Simpan & Perbarui Status
                    </button>
                </form>

                <?php if (!empty($data['isi_tanggapan'])): ?>
                    <div class="info-box" style="margin-top: 22px; border-left: 4px solid #002855; border-radius: 0 10px 10px 0; background: #f8fafc;">
                        <strong><i class="fa-regular fa-comment-dots"></i> Tanggapan Aktif Saat Ini:</strong><br>
                        <span style="color:#64748b; font-style:italic; font-size:14px;">"<?= htmlspecialchars($data['isi_tanggapan']) ?>"</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const autoMessages = {
            'menunggu': 'Laporan Anda telah kami terima dan saat ini sedang dalam antrian evaluasi petugas kelurahan. Mohon tunggu kabar selanjutnya.',
            'diproses': 'Laporan Anda sedang dalam proses penanganan aktif oleh tim petugas kelurahan. Kami akan segera menyelesaikan masalah ini.',
            'selesai' : 'Laporan Anda telah berhasil ditangani dan diselesaikan oleh tim petugas kelurahan. Terima kasih atas partisipasi Anda!'
        };

        function updateAutoPreview() {
            const status = document.getElementById('select-status').value;
            document.getElementById('auto-preview-text').textContent = autoMessages[status] || '';
        }

        function toggleManual(cb) {
            const area  = document.getElementById('manual-area');
            const input = document.getElementById('input-mode');
            if (cb.checked) {
                area.style.display = 'block';
                input.value = 'manual';
            } else {
                area.style.display = 'none';
                input.value = 'auto';
            }
        }

        updateAutoPreview(); // Inisialisasi saat halaman load

        function openLightbox(src) {
            document.getElementById('lightbox-img').src = src;
            document.getElementById('lightbox-modal').style.display = 'flex';
        }

        function closeLightbox(e) {
            if (e.target.id === 'lightbox-modal') {
                document.getElementById('lightbox-modal').style.display = 'none';
            }
        }

        <?php if ($card_lat && $card_lng): ?>
            const adminMap = L.map('mini-map-detail', {
                dragging: false, zoomControl: false,
                scrollWheelZoom: false, doubleClickZoom: false
            }).setView([<?= $card_lat ?>, <?= $card_lng ?>], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(adminMap);
            L.marker([<?= $card_lat ?>, <?= $card_lng ?>]).addTo(adminMap);
        <?php endif; ?>
    </script>
</body>
</html>
