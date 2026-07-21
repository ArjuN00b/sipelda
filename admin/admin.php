<?php
session_start();
require 'koneksi.php';

// Validasi Admin
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$q_stats = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) AS menunggu,
        SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) AS diproses,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) AS selesai
    FROM pengaduan
"));

// Dashboard HANYA menampilkan laporan aktif (status 'menunggu' atau 'diproses')
$query = "SELECT p.*, u.nama_lengkap, u.username FROM pengaduan p JOIN users u ON p.id_user = u.id_user WHERE p.status IN ('menunggu', 'diproses') ORDER BY p.id_pengaduan DESC";
$result = mysqli_query($koneksi, $query);

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
    <title>Dashboard Admin - SIPELDA</title>
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
        .admin-profile i { font-size: 20px; }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 35px; }
        .stat-card { background: white; padding: 25px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; }
        .stat-info h3 { margin: 0; font-size: 36px; color: #002855; font-weight: 800; }
        .stat-info p { margin: 5px 0 0; color: #64748b; font-size: 15px; font-weight: bold; }
        .stat-icon { width: 55px; height: 55px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 26px; }
        
        .table-container { background: white; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; color: #475569; padding: 18px 24px; text-align: left; font-size: 14px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-weight: 800; }
        td { padding: 18px 24px; border-bottom: 1px solid #f1f5f9; font-size: 15px; vertical-align: middle; }
        tr:hover { background: #f8fafc; }
        
        .badge { padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: bold; letter-spacing: 0.5px; }
        .badge-menunggu { background: #fee2e2; color: #dc2626; }
        .badge-diproses { background: #fef3c7; color: #d97706; }
        
        .badge-privat { background: #1e293b; color: white; margin-left: 5px; padding: 2px 6px; border-radius: 4px; font-size: 11px; }
        .badge-anonim { background: #e2e8f0; color: #475569; margin-left: 5px; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
        
        .btn-action { text-decoration: none; padding: 10px 16px; border-radius: 8px; font-size: 14px; font-weight: bold; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; }
        .btn-tanggapi { background: #e0e7ff; color: #002855; border: 1px solid #c7d2fe; }
        .btn-tanggapi:hover { background: #c7d2fe; }

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
            <li><a href="admin.php" class="active"><i class="fa-solid fa-border-all"></i> Dashboard</a></li>
            <li><a href="histori_admin.php"><i class="fa-solid fa-clock-rotate-left"></i> Histori Pengaduan</a></li>
        </ul>
        <div class="sidebar-footer">
            <button type="button" onclick="openConfirmLogout()"><i class="fa-solid fa-right-from-bracket"></i> Keluar</button>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Dashboard Pengaduan Aktif</h1>
            <a href="../profil/profil.php" class="admin-profile">
                <i class="fa-solid fa-user-shield"></i><?= htmlspecialchars($_SESSION['username']); ?>
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
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)):
                            $status = strtolower($row['status']);
                            $is_privat = strpos($row['judul_laporan'], '[PRIVAT]') !== false;
                            $is_anonim = strpos($row['judul_laporan'], '[ANONIM]') !== false;
    
                            $pecah_judul    = explode(' - ', str_replace([' [ANONIM]', ' [PRIVAT]'], '', $row['judul_laporan']), 2);
                            $kategori_murni = $pecah_judul[0];
                            $lokasi_detail  = $pecah_judul[1] ?? '';
                            $icon_kat       = getIkonKategori($kategori_murni);
                        ?>
                            <tr>
                                <td>
                                    <strong>#SPL-<?= $row['id_pengaduan']; ?></strong><br>
                                    <span style="color:#94a3b8; font-size:13px;"><?= date('d M Y, H:i', strtotime($row['tgl_pengaduan'])); ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong>
                                    <?= $is_anonim ? '<span class="badge-anonim">ANONIM</span>' : '' ?>
                                </td>
                                <td>
                                    <strong><i class="fa-solid <?= $icon_kat ?>" style="color:#94a3b8; margin-right:6px;"></i> <?= htmlspecialchars($kategori_murni) ?></strong>
                                    <?= $is_privat ? '<span class="badge-privat"><i class="fa-solid fa-lock"></i></span>' : '' ?>
    
                                    <?php if ($lokasi_detail): ?>
                                        <div style="font-size:13px; color:#64748b; margin-top:5px;">
                                            <i class="fa-solid fa-location-dot" style="color:#dc3545;"></i> <?= htmlspecialchars($lokasi_detail) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-<?= $status ?>"><?= strtoupper($status) ?></span></td>
                                <td>
                                    <a href="tanggapan_admin.php?id=<?= $row['id_pengaduan']; ?>" class="btn-action btn-tanggapi"><i class="fa-solid fa-reply"></i> Kelola & Tanggapi</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 40px; color: #64748b; font-weight: bold;">
                                <i class="fa-solid fa-folder-open" style="font-size: 32px; display:block; margin-bottom:10px; color:#cbd5e1;"></i>
                                Tidak ada laporan penanganan aktif saat ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function openConfirmLogout() {
            document.getElementById('modal-confirm-logout').style.display = 'flex';
        }
        function closeConfirmLogout() {
            document.getElementById('modal-confirm-logout').style.display = 'none';
        }
    </script>
</body>
</html>
