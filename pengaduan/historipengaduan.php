<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || $_SESSION['role'] !== 'masyarakat') {
    header("Location: ../auth/login.php");
    exit;
}
$id_user_login = $_SESSION['id_user'];
$hapus_sukses = false;

// LOGIKA HAPUS PENGADUAN
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    $cek_query = mysqli_query($koneksi, "SELECT foto FROM pengaduan WHERE id_pengaduan = '$id_hapus' AND id_user = '$id_user_login' AND status = 'menunggu'");
    
    if ($data_hapus = mysqli_fetch_assoc($cek_query)) {
        if (!empty($data_hapus['foto']) && file_exists(dirname(__DIR__) . '/uploads/' . $data_hapus['foto'])) {
            unlink(dirname(__DIR__) . '/uploads/' . $data_hapus['foto']);
        }
        mysqli_query($koneksi, "DELETE FROM pengaduan WHERE id_pengaduan = '$id_hapus'");
        $hapus_sukses = true;
    }
}

$search        = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, trim($_GET['search'])) : '';
$filter_kat    = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';
$filter_dari   = isset($_GET['tgl_dari']) ? $_GET['tgl_dari'] : '';
$filter_sampai = isset($_GET['tgl_sampai']) ? $_GET['tgl_sampai'] : '';

// Tambahan filter ke query total
$where_clause = " WHERE p.id_user = '$id_user_login'";
if ($search != '') {
    $where_clause .= " AND (p.judul_laporan LIKE '%$search%' OR p.isi_laporan LIKE '%$search%')";
}
if ($filter_kat != '' && $filter_kat != 'semua') {
    $where_clause .= " AND p.judul_laporan LIKE '$filter_kat%'";
}
if ($filter_dari != '') {
    $tgl_dari_safe = mysqli_real_escape_string($koneksi, $filter_dari);
    $where_clause .= " AND DATE(p.tgl_pengaduan) >= '$tgl_dari_safe'";
}
if ($filter_sampai != '') {
    $tgl_sampai_safe = mysqli_real_escape_string($koneksi, $filter_sampai);
    $where_clause .= " AND DATE(p.tgl_pengaduan) <= '$tgl_sampai_safe'";
}

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$total_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pengaduan p $where_clause");
$total_reports = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total_reports / $limit);
if ($total_pages < 1) $total_pages = 1;

$result = mysqli_query($koneksi, "SELECT p.*, t.isi_tanggapan FROM pengaduan p LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan $where_clause ORDER BY p.tgl_pengaduan DESC LIMIT $limit OFFSET $offset");

// Statistik total dashboard riwayat
$q_stats = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) AS menunggu,
        SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) AS diproses,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) AS selesai
    FROM pengaduan WHERE id_user = '$id_user_login'
"));

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
    <title>Riwayat Pengaduan - SIPELDA</title>
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
        }
        
        .brand-divider {
            max-width: 1000px;
            margin: 15px auto 25px;
            border-bottom: 2px solid #cbd5e1;
            opacity: 0.7;
        }

        .container { max-width: 1000px; margin: 0 auto 40px; padding: 0 20px; box-sizing: border-box; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 35px; }
        .stat-card { background: white; padding: 22px 25px; border-radius: 20px; display: flex; align-items: center; gap: 15px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .stat-icon { width: 55px; height: 55px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; }
        
        .history-cards { display: flex; flex-direction: column; gap: 30px; margin-bottom: 40px; }
        .history-card { 
            background: white; 
            border-radius: 20px; 
            padding: 30px; 
            border: 1px solid #e2e8f0; 
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); 
            display: flex;
            gap: 30px;
            flex-direction: column;
        }

        .card-top-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 18px; }
        .card-title-group { display: flex; gap: 15px; align-items: center; }
        .card-icon-wrapper { width: 50px; height: 50px; border-radius: 12px; background: #e0e7ff; display: flex; align-items: center; justify-content: center; font-size: 22px; color: #002855; }
        .card-title-group h4 { margin: 0 0 5px 0; font-size: 18px; color: #0f172a; font-weight: 800; }
        .card-title-group p { margin: 0; font-size: 13px; color: #64748b; font-weight: 600; }
        .card-action-group { display: flex; align-items: center; gap: 12px; }

        .btn-hapus { border: none; font-family: inherit; background-color: #fee2e2; color: #dc2626; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-hapus:hover { background-color: #dc2626; color: white; }

        .badge { padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 800; text-transform: uppercase; }
        .badge-menunggu { background: #fee2e2; color: #dc2626; }
        .badge-diproses { background: #fef3c7; color: #d97706; }
        .badge-selesai { background: #dcfce3; color: #16a34a; }

        .detail-body-container { display: flex; gap: 30px; }
        .detail-media { width: 320px; min-width: 320px; border-radius: 12px; overflow: hidden; background: #f1f5f9; display: flex; align-items: center; justify-content: center; }
        .detail-media img, .detail-media video { width: 100%; height: 100%; object-fit: cover; border-radius: 12px; }
        .detail-content-text { flex: 1; }

        .box-lokasi, .box-deskripsi, .box-koordinat { border-radius: 10px; padding: 14px 18px; font-size: 15px; margin-bottom: 16px; }
        .box-lokasi { background: #f8fafc; border: 1px solid #e2e8f0; color: #334155; display: flex; align-items: center; gap: 10px; }
        .box-deskripsi { background: white; border: 1px solid #e2e8f0; color: #334155; line-height: 1.6; }
        .box-koordinat { background: #eff6ff; border: 1px dashed #bfdbfe; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .box-koordinat a { color: #2563eb; font-weight: bold; }

        .report-tanggapan { border-left: 4px solid #002855; background: #f8fafc; padding: 16px 20px; border-radius: 0 10px 10px 0; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; }

        .mini-map {
            width: 100%;
            height: 180px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            margin-bottom: 16px;
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
        .lightbox-content { max-width: 90%; max-height: 85vh; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); object-fit: contain; animation: zoomIn 0.25s ease-out; }
        .lightbox-close { position: absolute; top: 25px; right: 35px; color: #ffffff; font-size: 44px; font-weight: bold; cursor: pointer; z-index: 100000; transition: 0.2s; line-height: 1; }
        .lightbox-close:hover { color: #f87171; transform: scale(1.15); }

        /* MODAL POPUP */
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
        .modal-icon-warning {
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
        .modal-box h3 { margin: 0 0 10px; color: #002855; font-size: 24px; font-weight: 800; }
        .modal-box p { color: #64748b; font-size: 15px; margin: 0 0 30px; line-height: 1.6; }
        .modal-button-group { display: flex; gap: 12px; }
        .btn-modal-cancel { flex: 1; padding: 14px; background-color: #f1f5f9; color: #475569; border-radius: 10px; font-size: 15px; font-weight: bold; border: 1px solid #cbd5e1; cursor: pointer; transition: 0.2s; }
        .btn-modal-cancel:hover { background-color: #e2e8f0; }
        .btn-modal-delete { flex: 1; padding: 14px; background-color: #dc2626; color: white; border-radius: 10px; font-size: 15px; font-weight: bold; text-decoration: none; display: inline-block; box-sizing: border-box; transition: 0.2s; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3); text-align: center; }
        .btn-modal-delete:hover { background-color: #b91c1c; }

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
        .btn-modal-close { display: block; width: 100%; padding: 16px; background-color: #002855; color: white; border-radius: 10px; font-size: 16px; font-weight: bold; text-decoration: none; box-sizing: border-box; text-align: center; }

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

        .pagination-container {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            margin-bottom: 40px;
        }

        .page-link {
            padding: 10px 18px;
            background: white;
            border: 1px solid #cbd5e1;
            color: #002855;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            transition: 0.2s;
        }
        .page-link:hover, .page-link.active {
            background: #002855;
            color: white;
            border-color: #002855;
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes zoomIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }

        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .detail-body-container { flex-direction: column; }
            .detail-media { width: 100%; min-width: 100%; height: 220px; }
        }
    </style>
</head>
<body>

    <!-- MODAL POPUP KONFIRMASI BATALKAN LAPORAN -->
    <div id="modal-confirm-hapus" class="modal-overlay" style="display: none;">
        <div class="modal-box">
            <div class="modal-icon-warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <h3>Batalkan Laporan Ini?</h3>
            <p>Laporan yang telah dibatalkan akan dihapus secara permanen dari sistem dan tidak dapat dikembalikan.</p>
            <div class="modal-button-group">
                <button type="button" class="btn-modal-cancel" onclick="closeConfirmHapus()">Batal</button>
                <a href="#" id="link-konfirmasi-hapus" class="btn-modal-delete">Ya, Batalkan</a>
            </div>
        </div>
    </div>

    <!-- MODAL SUKSES PEMBATALAN LAPORAN -->
    <?php if ($hapus_sukses): ?>
        <div class="modal-overlay">
            <div class="modal-box">
                <div class="modal-icon-success"><i class="fa-solid fa-circle-check"></i></div>
                <h3>Laporan Dibatalkan!</h3>
                <p>Laporan Anda telah berhasil dibatalkan dan dihapus dari riwayat pengaduan.</p>
                <a href="historipengaduan.php" class="btn-modal-close">Lanjut ke Riwayat <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    <?php endif; ?>

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

    <div class="container">
        <div class="page-title">
            <h2 style="color: #002855; font-size: 30px; font-weight: 800; margin: 0 0 10px 0;">Riwayat Pengaduan Saya</h2>
            <p style="color: #64748b; font-size: 16px; margin: 0 0 35px 0;">Pantau rincian lengkap status dan tanggapan dari semua laporan yang pernah Anda kirimkan.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:#002855;"><i class="fa-solid fa-clipboard-list"></i></div>
                <div>
                    <div style="font-size:14px; color:#64748b; font-weight:600;">Total Laporan</div>
                    <div style="font-size:26px; font-weight:800; color:#002855;"><?= $q_stats['total'] ?? 0 ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fee2e2; color:#dc2626;"><i class="fa-solid fa-clock"></i></div>
                <div>
                    <div style="font-size:14px; color:#64748b; font-weight:600;">Menunggu</div>
                    <div style="font-size:26px; font-weight:800; color:#dc2626;"><?= $q_stats['menunggu'] ?? 0 ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fef3c7; color:#d97706;"><i class="fas fa-spinner"></i></div>
                <div>
                    <div style="font-size:14px; color:#64748b; font-weight:600;">Sedang Diproses</div>
                    <div style="font-size:26px; font-weight:800; color:#d97706;"><?= $q_stats['diproses'] ?? 0 ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#dcfce3; color:#16a34a;"><i class="fa-solid fa-check-double"></i></div>
                <div>
                    <div style="font-size:14px; color:#64748b; font-weight:600;">Selesai</div>
                    <div style="font-size:26px; font-weight:800; color:#16a34a;"><?= $q_stats['selesai'] ?? 0 ?></div>
                </div>
            </div>
        </div>

        <!-- PANEL FILTER + SEARCH -->
        <form id="filter-form" method="GET" action="historipengaduan.php">
            <div style="background:white; border:1px solid #e2e8f0; border-radius:14px; padding:24px 28px; box-shadow:0 4px 12px rgba(0,0,0,0.04); margin-bottom:24px;">
                <!-- Baris 1: Search -->
                <div style="position:relative; margin-bottom:16px;">
                    <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:18px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:17px;"></i>
                    <input type="text" name="search" id="live-search"
                           value="<?= htmlspecialchars($search) ?>"
                           placeholder="Cari kata kunci riwayat laporan Anda..."
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
                    <a href="historipengaduan.php" style="padding:12px 20px; background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; border-radius:10px; font-size:15px; font-weight:700; text-decoration:none; display:flex; align-items:center; gap:8px; transition:0.2s;">
                        <i class="fa-solid fa-xmark"></i> Reset Filter
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <div class="history-cards">
            <?php 
            $mini_maps_script = [];
            if (mysqli_num_rows($result) > 0):
                while ($row = mysqli_fetch_assoc($result)):
                    $status = strtolower($row['status']);

                    $pecah_judul    = explode(' - ', str_replace([' [ANONIM]', ' [PRIVAT]'], '', $row['judul_laporan']), 2);
                    $kategori_murni = $pecah_judul[0];
                    $lokasi_detail  = $pecah_judul[1] ?? 'Lokasi tidak spesifik';

                    $pecah_isi       = explode("\n\n📍 Titik Koordinat Peta:\n", $row['isi_laporan']);
                    $deskripsi_murni = $pecah_isi[0];
                    $link_maps       = isset($pecah_isi[1]) ? trim($pecah_isi[1]) : '';

                    $card_lat = $card_lng = null;
                    if ($link_maps && preg_match('/q=(-?\d+\.\d+),(-?\d+\.\d+)/', $link_maps, $matches)) {
                        $card_lat = $matches[1];
                        $card_lng = $matches[2];
                        $mini_maps_script[] = [
                            'id'  => $row['id_pengaduan'],
                            'lat' => $card_lat,
                            'lng' => $card_lng
                        ];
                    }

                    $ext_file = strtolower(pathinfo($row['foto'], PATHINFO_EXTENSION));
                    $is_video = in_array($ext_file, ['mp4', 'webm', 'mov', 'mkv']);
            ?>
                    <div class="history-card">
                        <div class="card-top-header">
                            <div class="card-title-group">
                                <div class="card-icon-wrapper"><i class="fa-solid <?= getIkonKategori($kategori_murni) ?>"></i></div>
                                <div>
                                    <h4><?= htmlspecialchars($kategori_murni) ?> <?= strpos($row['judul_laporan'], '[PRIVAT]') !== false ? '<i class="fa-solid fa-lock" style="color:#dc3545; font-size:14px;" title="Privat"></i>' : '' ?></h4>
                                    <p><i class="fa-regular fa-calendar-days"></i> <?= date('d M Y, H:i', strtotime($row['tgl_pengaduan'])) ?> | <strong>#SPL-<?= $row['id_pengaduan'] ?></strong></p>
                                </div>
                            </div>
                            <div class="card-action-group">
                                <span class="badge badge-<?= $status ?>"><?= strtoupper($status) ?></span>
                                <?php if ($status === 'menunggu'): ?>
                                    <button type="button" class="btn-hapus" onclick="openConfirmHapus(<?= $row['id_pengaduan'] ?>)"><i class="fa-solid fa-trash-can"></i> Batalkan</button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="detail-body-container">
                            <div class="detail-media">
                                <?php if ($row['foto']): ?>
                                    <?php if ($is_video): ?>
                                        <video src="../uploads/<?= $row['foto'] ?>" controls style="width:100%; height:100%; border-radius:12px;"></video>
                                    <?php else: ?>
                                        <img src="../uploads/<?= $row['foto'] ?>" alt="Foto Kejadian" onclick="openLightbox('../uploads/<?= $row['foto'] ?>')" style="cursor:pointer;" title="Klik untuk memperbesar">
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div style="color: #94a3b8; font-size: 15px; text-align: center;"><i class="fa-regular fa-image" style="font-size: 36px; display:block; margin-bottom:10px;"></i>(Tanpa Bukti)</div>
                                <?php endif; ?>
                            </div>

                            <div class="detail-content-text">
                                <?php if ($card_lat && $card_lng): ?>
                                    <!-- Menampilkan Maps di Atas Alamat / Detail Lokasi -->
                                    <div id="card-map-<?= $row['id_pengaduan'] ?>" class="mini-map"></div>
                                <?php endif; ?>

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
                                    <h4 style="margin:0 0 8px; font-size:15px; color:#002855;"><i class="fa-regular fa-comment-dots"></i> Tanggapan Kelurahan</h4>
                                    <p style="margin:0; font-size:15px; font-style:<?= $row['isi_tanggapan'] ? 'normal' : 'italic' ?>; color:#475569;">
                                        <?= $row['isi_tanggapan'] ? htmlspecialchars($row['isi_tanggapan']) : 'Belum ada tanggapan.' ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding: 40px; background:white; border-radius:12px; border:1px solid #e2e8f0; color:#64748b;">Belum ada riwayat pengaduan yang sesuai.</div>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="historipengaduan.php?page=<?= $i ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($filter_kat) ?>&tgl_dari=<?= urlencode($filter_dari) ?>&tgl_sampai=<?= urlencode($filter_sampai) ?>" class="page-link <?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- FOOTBAR NAVIGATION BAR -->
    <nav class="footbar-nav">
        <a href="../index.php" class="footbar-item">
            <i class="fa-solid fa-house"></i>
            <span>Beranda</span>
        </a>
        <a href="historipengaduan.php" class="footbar-item active">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Riwayat</span>
        </a>
        <a href="../profil/profil.php" class="footbar-item">
            <i class="fa-solid fa-user"></i>
            <span>Profil</span>
        </a>
    </nav>

    <script>
        function openConfirmHapus(id) {
            document.getElementById('link-konfirmasi-hapus').href = 'historipengaduan.php?hapus=' + id;
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

        // === PETA MINI RIWAYAT CARD ===
        <?php foreach ($mini_maps_script as $m): ?>
            (function() {
                const mapId = 'card-map-<?= $m['id'] ?>';
                const lat = <?= $m['lat'] ?>;
                const lng = <?= $m['lng'] ?>;
                const mObj = L.map(mapId, { dragging: false, zoomControl: false, scrollWheelZoom: false, doubleClickZoom: false }).setView([lat, lng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mObj);
                L.marker([lat, lng]).addTo(mObj);
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

            // Fokus highlight border
            liveSearch.addEventListener('focus', function() {
                this.style.borderColor = '#002855';
                this.style.boxShadow = '0 0 0 4px rgba(0,40,85,0.1)';
            });

            liveSearch.addEventListener('blur', function() {
                this.style.borderColor = '#cbd5e1';
                this.style.boxShadow = 'none';
            });
        }
    </script>
</body>
</html>
