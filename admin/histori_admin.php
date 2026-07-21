<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$search        = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, trim($_GET['search'])) : '';
$filter_kat    = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';

// Histori Admin HANYA menampilkan laporan yang statusnya selesai
$where_clause = " WHERE p.status = 'selesai'";
if ($search != '') {
    $where_clause .= " AND (p.judul_laporan LIKE '%$search%' OR p.isi_laporan LIKE '%$search%' OR u.nama_lengkap LIKE '%$search%')";
}
if ($filter_kat != '' && $filter_kat != 'semua') {
    $where_clause .= " AND p.judul_laporan LIKE '$filter_kat%'";
}

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$total_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pengaduan p JOIN users u ON p.id_user = u.id_user $where_clause");
$total_reports = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total_reports / $limit);
if ($total_pages < 1) $total_pages = 1;

$query_sql = "
    SELECT p.*, u.nama_lengkap, t.isi_tanggapan 
    FROM pengaduan p 
    JOIN users u ON p.id_user = u.id_user 
    LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan 
    $where_clause
    ORDER BY p.tgl_pengaduan DESC
    LIMIT $limit OFFSET $offset
";

$result = mysqli_query($koneksi, $query_sql);
$total_data = mysqli_num_rows($result);

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
    <title>Histori Pengaduan - SIPELDA Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @keyframes pageFadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        body { 
            font-family: 'Segoe UI', Tahoma, sans-serif; 
            background: #f4f7fb; 
            margin: 0; 
            display: flex; 
            color: #1e293b; 
            font-size: 16px;
            animation: pageFadeIn 0.4s ease-out;
        }
        
        .sidebar { width: 280px; height: 100vh; background: #002855; color: white; padding: 35px 25px; position: fixed; left: 0; top: 0; box-sizing: border-box; display: flex; flex-direction: column; }
        .sidebar .logo { font-size: 28px; font-weight: bold; text-align: center; margin-bottom: 45px; display: flex; flex-direction: column; align-items: center; gap: 8px; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; flex: 1; }
        .sidebar-menu a { display: flex; align-items: center; gap: 18px; color: #a9b9cc; text-decoration: none; padding: 14px 22px; border-radius: 10px; font-weight: 700; font-size: 17px; transition: 0.3s; margin-bottom: 12px; }
        .sidebar-menu a.active, .sidebar-menu a:hover { background: rgba(255,255,255,0.1); color: white; }
        
        .sidebar-footer button { display: flex; align-items: center; gap: 18px; color: #ff6b6b; text-decoration: none; padding: 14px 22px; border-radius: 10px; font-weight: bold; transition: 0.3s; background: rgba(220,53,69,0.1); border: none; width: 100%; font-size: 17px; cursor: pointer; text-align: left; }
        .sidebar-footer button:hover { background: #dc3545; color: white; }
        
        .main-content { margin-left: 280px; padding: 35px 45px; width: calc(100% - 280px); box-sizing: border-box; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; }
        .header h1 { margin: 0; font-size: 32px; color: #002855; font-weight: 800; }
        
        .admin-profile { display: flex; align-items: center; gap: 10px; background: white; padding: 10px 20px; border-radius: 30px; border: 1px solid #e2e8f0; font-size: 16px; font-weight: bold; color: #002855; text-decoration: none; }
        .desc-text { color: #64748b; font-size: 16px; margin-bottom: 25px; font-weight: 600; }
        
        .filter-area { display: flex; gap: 15px; background: white; padding: 22px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; margin-bottom: 25px; align-items: center; flex-wrap: wrap; }
        .input-group { position: relative; flex: 1.5; min-width: 250px; }
        .input-group i { position: absolute; left: 16px; top: 14px; color: #94a3b8; font-size: 18px; }
        .input-group input { width: 100%; padding: 13px 16px 13px 44px; border: 1.5px solid #cbd5e1; border-radius: 10px; outline: none; box-sizing: border-box; font-size: 15px; transition: 0.2s; }
        .input-group input:focus { border-color: #002855; }
        .filter-select { flex: 1; min-width: 180px; padding: 13px; border: 1.5px solid #cbd5e1; border-radius: 10px; outline: none; background: #f8fafc; cursor: pointer; color: #334155; font-size: 15px; }
        .btn-filter { background: #002855; color: white; border: none; padding: 13px 26px; border-radius: 10px; cursor: pointer; font-weight: bold; display: flex; align-items: center; gap: 8px; transition: 0.3s; font-size: 15px; }
        .btn-filter:hover { background: #001a3b; }
        
        .table-container { background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; color: #475569; padding: 18px 24px; text-align: left; font-size: 14px; font-weight: 800; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; }
        td { padding: 18px 24px; border-bottom: 1px solid #f1f5f9; font-size: 15px; vertical-align: middle; color: #334155; }
        
        .badge { padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: bold; }
        .badge-menunggu { background: #fff1f2; color: #dc2626; }
        .badge-diproses { background: #fffbeb; color: #d97706; }
        .badge-selesai { background: #f0fdf4; color: #16a34a; }
        
        .badge-anonim, .badge-privat { font-size: 11px; margin-left: 5px; padding: 3px 8px; border-radius: 4px; }
        .badge-anonim { background: #e2e8f0; color: #475569; }
        .badge-privat { background: #1e293b; color: white; }
        
        .tanggapan-text { font-size: 14px; color: #64748b; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .btn-lihat { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; text-decoration: none; padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: bold; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; }
        .btn-lihat:hover { background: #e2e8f0; color: #002855; }
        
        .footer-table { padding: 18px 24px; background: #f8fafc; display: flex; justify-content: space-between; font-size: 14px; color: #64748b; border-top: 1px solid #e2e8f0; font-weight: bold; }

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

        .modal-icon-logout {
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

        .btn-modal-logout-ya { flex: 1; padding: 14px; background-color: #dc2626; color: white; border-radius: 10px; font-size: 15px; font-weight: bold; text-decoration: none; display: inline-block; box-sizing: border-box; transition: 0.2s; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3); }
        .btn-modal-logout-ya:hover { background-color: #b91c1c; }

        .pagination-container {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
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
            font-size: 14px;
        }
        .page-link:hover, .page-link.active {
            background: #002855;
            color: white;
            border-color: #002855;
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body>

    <!-- MODAL POPUP KONFIRMASI KELUAR -->
    <div id="modal-confirm-logout" class="modal-overlay" style="display: none;">
        <div class="modal-box">
            <div class="modal-icon-logout"><i class="fa-solid fa-right-from-bracket"></i></div>
            <h3>Keluar dari Panel Admin?</h3>
            <p>Sesi admin Anda akan diakhiri. Anda harus masuk kembali untuk mengelola SIPELDA.</p>
            <div class="modal-button-group">
                <button type="button" class="btn-modal-cancel" onclick="closeConfirmLogout()">Batal</button>
                <a href="../auth/logout.php" class="btn-modal-logout-ya">Ya, Keluar</a>
            </div>
        </div>
    </div>

    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-shield-halved" style="font-size: 36px;"></i>
            SIPELDA <span style="font-size: 14px; color: #93c5fd; font-weight:normal;">Portal Admin</span>
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin.php"><i class="fa-solid fa-border-all"></i> Dashboard</a></li>
            <li><a href="histori_admin.php" class="active"><i class="fa-solid fa-clock-rotate-left"></i> Histori Pengaduan</a></li>
        </ul>
        <div class="sidebar-footer">
            <button type="button" onclick="openConfirmLogout()"><i class="fa-solid fa-right-from-bracket"></i> Keluar</button>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Histori & Arsip Pengaduan Warga</h1>
            <a href="../profil/profil.php" class="admin-profile">
                <i class="fa-solid fa-user-shield"></i> Admin <?= htmlspecialchars($_SESSION['username']); ?>
            </a>
        </div>

        <p class="desc-text">Rekapitulasi seluruh laporan warga, termasuk pencarian instan dan filter multi-opsi.</p>

        <form id="filter-form" method="GET" action="histori_admin.php" class="filter-area">
            <div class="input-group">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" id="live-search" placeholder="Cari nama pelapor, tiket, deskripsi..." value="<?= htmlspecialchars($search); ?>" autocomplete="off">
            </div>

            <select name="kategori" class="filter-select" onchange="this.form.submit()">
                <option value="semua" <?= ($filter_kat == 'semua' || $filter_kat == '') ? 'selected' : ''; ?>>Semua Kategori</option>
                <?php 
                $opsi_kat = [
                    'Jalan Rusak & Infrastruktur', 'Kebersihan & Sampah', 'Penerangan Jalan Umum (PJU)', 
                    'Kesehatan & Lingkungan', 'Keamanan & Ketertiban', 'Ketertiban Lalu Lintas & Parkir', 
                    'Pelayanan Administrasi & Birokrasi', 'Bantuan Sosial (Bansos)', 'Kedaruratan & Bencana', 
                    'Fasilitas Umum', 'Lainnya'
                ];
                foreach ($opsi_kat as $op) {
                    $selected = ($filter_kat == $op) ? 'selected' : '';
                    echo "<option value='$op' $selected>" . str_replace(' & Birokrasi', '', $op) . "</option>";
                }
                ?>
            </select>

            <button type="submit" class="btn-filter"><i class="fa-solid fa-filter"></i> Cari</button>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Tanggal</th>
                        <th>Pelapor</th>
                        <th>Kategori & Lokasi</th>
                        <th>Status</th>
                        <th>Tanggapan Resmi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = $offset + 1;
                    if ($total_data > 0):
                        while ($row = mysqli_fetch_assoc($result)):
                            $status_db = strtolower($row['status']);
                            $is_anonim = strpos($row['judul_laporan'], '[ANONIM]') !== false;
                            $is_privat = strpos($row['judul_laporan'], '[PRIVAT]') !== false;

                            $pecah_judul = explode(' - ', str_replace([' [ANONIM]', ' [PRIVAT]'], '', $row['judul_laporan']), 2);
                            $kategori_murni = $pecah_judul[0];
                            $lokasi_detail = $pecah_judul[1] ?? '';
                            $icon_kat = getIkonKategori($kategori_murni);
                    ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td>
                                    <strong><?= date('d M', strtotime($row['tgl_pengaduan'])); ?></strong><br>
                                    <span style="font-size:13px; color:#94a3b8;"><?= date('Y, H:i', strtotime($row['tgl_pengaduan'])); ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong>
                                    <?= $is_anonim ? '<span class="badge-anonim">ANONIM</span>' : '' ?>
                                </td>
                                <td>
                                    <strong><i class="fa-solid <?= $icon_kat ?>" style="color:#94a3b8; margin-right:5px;"></i> <?= htmlspecialchars($kategori_murni) ?></strong>
                                    <?= $is_privat ? '<span class="badge-privat"><i class="fa-solid fa-lock"></i></span>' : '' ?>
                                    
                                    <?php if ($lokasi_detail): ?>
                                        <div style="font-size:13px; color:#64748b; margin-top:5px;">
                                            <i class="fa-solid fa-location-dot" style="color:#dc3545;"></i> <?= htmlspecialchars($lokasi_detail) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-<?= $status_db ?>"><?= strtoupper($status_db) ?></span></td>
                                <td>
                                    <div class="tanggapan-text">
                                        <?= $row['isi_tanggapan'] ? htmlspecialchars($row['isi_tanggapan']) : '<span style="color:#cbd5e1; font-style:italic;">Belum ada tanggapan</span>'; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">Data arsip pengaduan tidak ditemukan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="footer-table">
                <div>Total Data: <?= $total_reports; ?> laporan</div>
            </div>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="histori_admin.php?page=<?= $i ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($filter_kat) ?>" class="page-link <?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function openConfirmLogout() {
            document.getElementById('modal-confirm-logout').style.display = 'flex';
        }
        function closeConfirmLogout() {
            document.getElementById('modal-confirm-logout').style.display = 'none';
        }

        // Live Search instan dengan debounce 400ms
        let debounceTimer = null;
        const liveSearch = document.getElementById('live-search');
        const filterForm = document.getElementById('filter-form');

        if (liveSearch) {
            liveSearch.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => filterForm.submit(), 400);
            });
        }
    </script>
</body>
</html>
