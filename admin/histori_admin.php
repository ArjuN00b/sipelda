<?php
session_start();
require '../config/koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$search        = isset($_GET['search'])       ? mysqli_real_escape_string($koneksi, trim($_GET['search']))  : '';
$filter_kat    = isset($_GET['kategori'])     ? mysqli_real_escape_string($koneksi, $_GET['kategori'])      : '';
$filter_dari   = isset($_GET['tgl_dari'])     ? $_GET['tgl_dari']    : '';
$filter_sampai = isset($_GET['tgl_sampai'])   ? $_GET['tgl_sampai']  : '';
$filter_privat = isset($_GET['privasi'])      ? $_GET['privasi']     : '';

// Histori hanya tampilkan laporan SELESAI
$query_sql = "
    SELECT p.*, u.nama_lengkap, u.username, t.isi_tanggapan
    FROM pengaduan p
    JOIN users u ON p.id_user = u.id_user
    LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan
    WHERE p.status = 'selesai'
";

// Filter: kata kunci (search)
if ($search != '') {
    $query_sql .= " AND (p.judul_laporan LIKE '%$search%' OR p.isi_laporan LIKE '%$search%' OR u.nama_lengkap LIKE '%$search%' OR u.username LIKE '%$search%')";
}

// Filter: kategori
if ($filter_kat != '' && $filter_kat != 'semua') {
    $query_sql .= " AND p.judul_laporan LIKE '$filter_kat%'";
}

// Filter: tanggal dari
if ($filter_dari != '') {
    $tgl_dari_safe = mysqli_real_escape_string($koneksi, $filter_dari);
    $query_sql .= " AND DATE(p.tgl_pengaduan) >= '$tgl_dari_safe'";
}

// Filter: tanggal sampai
if ($filter_sampai != '') {
    $tgl_sampai_safe = mysqli_real_escape_string($koneksi, $filter_sampai);
    $query_sql .= " AND DATE(p.tgl_pengaduan) <= '$tgl_sampai_safe'";
}

// Filter: sifat laporan (publik / privat)
if ($filter_privat === 'privat') {
    $query_sql .= " AND p.judul_laporan LIKE '%[PRIVAT]%'";
} elseif ($filter_privat === 'publik') {
    $query_sql .= " AND p.judul_laporan NOT LIKE '%[PRIVAT]%'";
}

$query_sql .= " ORDER BY p.tgl_pengaduan DESC";
$result = mysqli_query($koneksi, $query_sql);
$total_data = mysqli_num_rows($result);

$mini_maps_script = [];

function getIkonKategori($kategori)
{
    $kat = strtolower($kategori);
    $ikon_list = [
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
    <title>Histori Pengaduan - SIPELDA Admin</title>
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
        .sidebar-menu a.active, .sidebar-menu a:hover { background: rgba(255,255,255,0.12); color: white; }

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

        /* ===== MODAL POPUP KONFIRMASI LOGOUT ===== */
        .modal-overlay-admin {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0, 20, 50, 0.75);
            backdrop-filter: blur(6px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 99999;
            animation: fadeIn 0.3s ease-in-out;
        }

        .modal-box-admin {
            background: #ffffff;
            padding: 44px 40px;
            border-radius: 20px;
            max-width: 440px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
            animation: slideUp 0.35s ease-out;
        }

        .modal-icon-logout-admin {
            width: 84px; height: 84px;
            background: #fee2e2;
            color: #dc2626;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 22px;
            animation: popIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) 0.1s both;
        }

        @keyframes popIn  { from { transform: scale(0.5); opacity: 0; } to { transform: scale(1); opacity: 1; } }

        .modal-box-admin h3 { margin: 0 0 10px; color: #002855; font-size: 24px; font-weight: 800; }
        .modal-box-admin p  { color: #64748b; font-size: 15px; margin: 0 0 28px; line-height: 1.65; }

        .modal-btn-group { display: flex; gap: 12px; }

        .btn-modal-batal {
            flex: 1; padding: 14px; background: #f1f5f9; color: #475569;
            border-radius: 10px; font-size: 15px; font-weight: 700;
            border: 1px solid #cbd5e1; cursor: pointer; transition: 0.2s;
        }

        .btn-modal-batal:hover { background: #e2e8f0; }

        .btn-modal-keluar {
            flex: 1; padding: 14px; background: #dc2626; color: white;
            border-radius: 10px; font-size: 15px; font-weight: 700;
            text-decoration: none; display: inline-block; box-sizing: border-box;
            transition: 0.2s; box-shadow: 0 4px 12px rgba(220,38,38,0.3);
        }

        .btn-modal-keluar:hover { background: #b91c1c; }

        .main-content {
            margin-left: 270px;
            padding: 35px 45px;
            width: calc(100% - 270px);
            box-sizing: border-box;
            animation: pageFadeIn 0.4s ease-out;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .page-header h1 { margin: 0; font-size: 30px; color: #002855; font-weight: 800; }
        .page-header p  { margin: 6px 0 0; color: #64748b; font-size: 15px; }

        .admin-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            padding: 10px 18px;
            border-radius: 30px;
            border: 1px solid #e2e8f0;
            font-size: 15px;
            font-weight: 700;
            color: #002855;
            text-decoration: none;
        }

        .admin-badge i { font-size: 22px; }

        .search-bar {
            background: white;
            padding: 20px 24px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            margin-bottom: 30px;
        }

        .search-box { position: relative; }
        .search-box i { position: absolute; left: 18px; top: 16px; color: #94a3b8; font-size: 18px; }

        .search-box input {
            width: 100%;
            padding: 14px 16px 14px 50px;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            font-size: 16px;
            outline: none;
            box-sizing: border-box;
            background: #f8fafc;
            transition: 0.2s;
        }

        .search-box input:focus { border-color: #002855; background: white; box-shadow: 0 0 0 4px rgba(0,40,85,0.1); }

        .report-card {
            display: flex;
            gap: 28px;
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 28px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }

        .report-card.scroll-visible { opacity: 1; transform: translateY(0); }

        .report-img {
            width: 300px;
            min-width: 300px;
            border-radius: 12px;
            overflow: hidden;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .report-img img { width: 100%; height: 100%; object-fit: cover; border-radius: 12px; cursor: pointer; transition: 0.2s; }
        .report-img img:hover { opacity: 0.9; }

        .report-content { flex: 1; display: flex; flex-direction: column; }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f1f5f9;
        }

        .card-top h3 { margin: 0 0 6px; font-size: 20px; color: #002855; font-weight: 800; }
        .card-top p  { margin: 0; font-size: 14px; color: #64748b; }

        .badge { padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 800; }
        .badge-selesai { background: #dcfce3; color: #16a34a; }

        .mini-map { width: 100%; height: 180px; border-radius: 12px; border: 1px solid #cbd5e1; margin-bottom: 14px; }

        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 13px 16px;
            font-size: 15px;
            color: #334155;
            margin-bottom: 12px;
        }

        .info-row i { margin-top: 2px; flex-shrink: 0; }

        .tanggapan-box {
            border-left: 4px solid #002855;
            background: #f8fafc;
            padding: 14px 18px;
            border-radius: 0 10px 10px 0;
            margin-bottom: 14px;
        }

        .tanggapan-box h4 { margin: 0 0 6px; font-size: 15px; color: #002855; }
        .tanggapan-box p  { margin: 0; font-size: 15px; color: #334155; }

        .btn-detail { display: none; } /* Dihapus sesuai permintaan */


        .pelapor-info {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            color: #15803d;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .maps-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: #eff6ff;
            border: 1px dashed #bfdbfe;
            border-radius: 8px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 12px;
            transition: 0.2s;
        }

        .maps-link:hover { background: #dbeafe; }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            color: #94a3b8;
        }

        .empty-state i { font-size: 50px; display: block; margin-bottom: 18px; color: #cbd5e1; }
        .empty-state p { font-size: 17px; margin: 0; }

        .total-badge {
            background: #dcfce3;
            color: #16a34a;
            padding: 8px 18px;
            border-radius: 20px;
            font-size: 15px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

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

        .lightbox-content { max-width: 90%; max-height: 86vh; border-radius: 12px; object-fit: contain; }
        .lightbox-close {
            position: absolute;
            top: 22px; right: 32px;
            color: white;
            font-size: 46px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            transition: 0.2s;
        }

        .lightbox-close:hover { color: #f87171; }
    </style>
</head>
<body>

    <!-- MODAL POPUP KONFIRMASI KELUAR ADMIN -->
    <div id="modal-logout-admin" class="modal-overlay-admin" style="display:none;">
        <div class="modal-box-admin">
            <div class="modal-icon-logout-admin"><i class="fa-solid fa-right-from-bracket"></i></div>
            <h3>Keluar dari Panel Admin?</h3>
            <p>Sesi admin Anda akan diakhiri. Anda perlu masuk kembali untuk mengakses Panel Admin SIPELDA.</p>
            <div class="modal-btn-group">
                <button type="button" class="btn-modal-batal" onclick="closeLogoutModal()">Batal</button>
                <a href="../auth/logout.php" class="btn-modal-keluar"><i class="fa-solid fa-right-from-bracket"></i> Ya, Keluar</a>
            </div>
        </div>
    </div>

    <!-- LIGHTBOX -->
    <div id="lightbox-modal" class="lightbox-overlay" onclick="if(event.target.id==='lightbox-modal') this.style.display='none'">
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
            <li><a href="histori_admin.php" class="active"><i class="fa-solid fa-clock-rotate-left"></i> Histori Pengaduan</a></li>
        </ul>
        <div class="sidebar-footer">
            <a onclick="openLogoutModal()"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Histori Pengaduan</h1>
                <p>Arsip laporan warga yang telah berhasil diselesaikan oleh petugas kelurahan</p>
            </div>
            <a href="../profil/profil.php" class="admin-badge">
                <i class="fa-solid fa-user-shield"></i> <?= htmlspecialchars($_SESSION['username']) ?>
            </a>
        </div>

        <!-- PANEL FILTER + SEARCH -->
        <form id="filter-form" method="GET" action="histori_admin.php">
            <div style="background:white; border:1px solid #e2e8f0; border-radius:14px; padding:24px 28px; box-shadow:0 4px 12px rgba(0,0,0,0.04); margin-bottom:24px;">

                <!-- Baris 1: Search -->
                <div style="position:relative; margin-bottom:16px;">
                    <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:18px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:17px;"></i>
                    <input type="text" name="search" id="live-search"
                           value="<?= htmlspecialchars($search) ?>"
                           placeholder="Ketik nama pelapor, kategori, lokasi, atau deskripsi kejadian..."
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

                    <!-- Sifat Laporan -->
                    <select name="privasi" style="flex:0 0 170px; padding:12px 14px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:15px; background:#f8fafc; color:#334155; outline:none; cursor:pointer;">
                        <option value="" <?= $filter_privat == '' ? 'selected' : '' ?>>🔓 Semua Sifat</option>
                        <option value="publik"  <?= $filter_privat == 'publik'  ? 'selected' : '' ?>>🌐 Publik</option>
                        <option value="privat"  <?= $filter_privat == 'privat'  ? 'selected' : '' ?>>🔒 Privat</option>
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

                    <?php if ($search || ($filter_kat && $filter_kat != 'semua') || $filter_dari || $filter_sampai || $filter_privat): ?>
                    <a href="histori_admin.php" style="padding:12px 20px; background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; border-radius:10px; font-size:15px; font-weight:700; text-decoration:none; display:flex; align-items:center; gap:8px; transition:0.2s;">
                        <i class="fa-solid fa-xmark"></i> Reset Filter
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <div style="margin-bottom:20px; display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
            <span class="total-badge"><i class="fa-solid fa-check-double"></i> <?= $total_data ?> Laporan Terselesaikan Ditemukan</span>
            <?php if ($search || ($filter_kat && $filter_kat != 'semua') || $filter_dari || $filter_sampai || $filter_privat): ?>
            <span style="background:#fef3c7; color:#d97706; padding:8px 16px; border-radius:20px; font-size:14px; font-weight:700;"><i class="fa-solid fa-filter"></i> Filter Aktif</span>
            <?php endif; ?>
        </div>

        <?php
        if ($total_data > 0):
            while ($row = mysqli_fetch_assoc($result)):
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

                $icon_kat = getIkonKategori($kategori_murni);
                $is_privat = strpos($row['judul_laporan'], '[PRIVAT]') !== false;
                $ext_file = strtolower(pathinfo($row['foto'], PATHINFO_EXTENSION));
                $is_video = in_array($ext_file, ['mp4', 'webm', 'mov', 'mkv']);
        ?>
            <div class="report-card" data-searchable="<?= htmlspecialchars(strtolower($kategori_murni . ' ' . $lokasi_detail . ' ' . $deskripsi_murni . ' ' . $row['nama_lengkap'])) ?>">
                <div class="report-img">
                    <?php if ($row['foto']): ?>
                        <?php if ($is_video): ?>
                            <video src="../uploads/<?= $row['foto'] ?>" controls style="width:100%;height:100%;border-radius:12px;"></video>
                        <?php else: ?>
                            <img src="../uploads/<?= $row['foto'] ?>" alt="Bukti" onclick="document.getElementById('lightbox-img').src=this.src; document.getElementById('lightbox-modal').style.display='flex';" title="Klik untuk memperbesar">
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="color:#94a3b8; font-size:15px; text-align:center; padding: 40px 20px;">
                            <i class="fa-regular fa-image" style="font-size:36px; display:block; margin-bottom:10px;"></i>(Tanpa Bukti)
                        </div>
                    <?php endif; ?>
                </div>

                <div class="report-content">
                    <div class="card-top">
                        <div>
                            <h3><i class="fa-solid <?= $icon_kat ?>" style="color:#64748b; margin-right:8px;"></i>
                                <?= htmlspecialchars($kategori_murni) ?>
                                <?= $is_privat ? '<span style="background:#1e293b;color:white;padding:2px 8px;border-radius:4px;font-size:12px;margin-left:8px;"><i class="fa-solid fa-lock"></i></span>' : '' ?>
                            </h3>
                            <p><i class="fa-regular fa-calendar-days"></i> <?= date('d M Y, H:i', strtotime($row['tgl_pengaduan'])) ?> | <strong>#SPL-<?= $row['id_pengaduan'] ?></strong></p>
                        </div>
                        <span class="badge badge-selesai">✅ SELESAI</span>
                    </div>

                    <div class="pelapor-info">
                        <i class="fa-solid fa-user-check"></i>
                        <strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong>
                        <span style="color:#64748b; font-size:13px;">@<?= htmlspecialchars($row['username']) ?></span>
                    </div>

                    <?php if ($card_lat && $card_lng): ?>
                        <div id="hist-map-<?= $row['id_pengaduan'] ?>" class="mini-map"></div>
                    <?php endif; ?>

                    <div class="info-row">
                        <i class="fa-solid fa-location-dot" style="color:#dc3545;"></i>
                        <span><strong>Lokasi:</strong> <?= htmlspecialchars($lokasi_detail) ?></span>
                    </div>

                    <?php if ($link_maps): ?>
                        <a href="<?= htmlspecialchars($link_maps) ?>" target="_blank" class="maps-link">
                            <i class="fa-solid fa-map-location-dot"></i> Buka di Google Maps ↗
                        </a>
                    <?php endif; ?>

                    <div class="info-row">
                        <i class="fa-regular fa-file-lines" style="color:#002855;"></i>
                        <span><strong>Deskripsi:</strong> <?= nl2br(htmlspecialchars($deskripsi_murni)) ?></span>
                    </div>

                    <div class="tanggapan-box">
                        <h4><i class="fa-regular fa-comment-dots"></i> Tanggapan Resmi Kelurahan</h4>
                        <p><?= !empty($row['isi_tanggapan']) ? htmlspecialchars($row['isi_tanggapan']) : '<em style="color:#94a3b8;">Belum ada tanggapan tercatat.</em>' ?></p>
                    </div>

                </div>
            </div>

        <?php endwhile;
        else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-folder-open"></i>
                <p>Belum ada laporan yang diselesaikan.<br>Arsip akan terisi saat laporan diubah statusnya menjadi Selesai.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // === INISIALISASI PETA MINI ===
        <?php foreach ($mini_maps_script as $mm): ?>
            (function() {
                const mObj = L.map('hist-map-<?= $mm['id'] ?>', {
                    dragging: false, zoomControl: false,
                    scrollWheelZoom: false, doubleClickZoom: false
                }).setView([<?= $mm['lat'] ?>, <?= $mm['lng'] ?>], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mObj);
                L.marker([<?= $mm['lat'] ?>, <?= $mm['lng'] ?>]).addTo(mObj);
            })();
        <?php endforeach; ?>

        // === ANIMASI SCROLL (semua card langsung visible) ===
        const reportCards = document.querySelectorAll('.report-card');
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => { if (entry.isIntersecting) entry.target.classList.add('scroll-visible'); });
        }, { threshold: 0.04 });
        reportCards.forEach(card => { card.classList.add('scroll-visible'); observer.observe(card); });

        // === DEBOUNCE SEARCH: submit form otomatis saat user berhenti mengetik (400ms) ===
        let debounceTimer = null;
        const liveSearch = document.getElementById('live-search');
        const filterForm = document.getElementById('filter-form');

        liveSearch.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => filterForm.submit(), 400);
        });

        // Highlight border saat input fokus
        liveSearch.addEventListener('focus', function() {
            this.style.borderColor = '#002855';
            this.style.boxShadow = '0 0 0 4px rgba(0,40,85,0.1)';
        });

        liveSearch.addEventListener('blur', function() {
            this.style.borderColor = '#cbd5e1';
            this.style.boxShadow = 'none';
        });
        function openLogoutModal() { document.getElementById('modal-logout-admin').style.display = 'flex'; }
        function closeLogoutModal() { document.getElementById('modal-logout-admin').style.display = 'none'; }
        document.getElementById('modal-logout-admin').addEventListener('click', function(e) { if (e.target === this) closeLogoutModal(); });
        document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeLogoutModal(); });
    </script>
</body>
</html>
