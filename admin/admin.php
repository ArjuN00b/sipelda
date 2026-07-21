<?php
session_start();
require '../config/koneksi.php';

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

// Dashboard hanya tampilkan laporan AKTIF (bukan selesai)
$query = "SELECT p.*, u.nama_lengkap, u.username FROM pengaduan p JOIN users u ON p.id_user = u.id_user WHERE p.status != 'selesai' ORDER BY p.id_pengaduan DESC";
$result = mysqli_query($koneksi, $query);

function getIkonKategori(string $kategori): string
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
    <title>Dashboard Admin - SIPELDA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.12);
            color: white;
        }

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
            background: rgba(220, 53, 69, 0.1);
            cursor: pointer;
            border: none;
            width: 100%;
            box-sizing: border-box;
        }

        .sidebar-footer button i { font-size: 20px; }
        .sidebar-footer button:hover { background: #dc3545; color: white; }

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

        @keyframes popIn {
            from { transform: scale(0.5); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }

        .modal-box-admin h3 { margin: 0 0 10px; color: #002855; font-size: 24px; font-weight: 800; }
        .modal-box-admin p  { color: #64748b; font-size: 15px; margin: 0 0 28px; line-height: 1.65; }

        .modal-btn-group {
            display: flex;
            gap: 12px;
        }

        .btn-modal-batal {
            flex: 1;
            padding: 14px;
            background: #f1f5f9;
            color: #475569;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            border: 1px solid #cbd5e1;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-modal-batal:hover { background: #e2e8f0; }

        .btn-modal-keluar {
            flex: 1;
            padding: 14px;
            background: #dc2626;
            color: white;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            box-sizing: border-box;
            transition: 0.2s;
            box-shadow: 0 4px 12px rgba(220,38,38,0.3);
        }

        .btn-modal-keluar:hover { background: #b91c1c; }
        @keyframes fadeIn  { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

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

        .page-header h1 {
            margin: 0;
            font-size: 30px;
            color: #002855;
            font-weight: 800;
        }

        .page-header p { margin: 6px 0 0; color: #64748b; font-size: 15px; }

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
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .admin-badge i { font-size: 22px; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: white;
            padding: 28px 24px;
            border-radius: 16px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-info h3 { margin: 0 0 6px; font-size: 36px; color: #002855; font-weight: 800; }
        .stat-info p  { margin: 0; color: #64748b; font-size: 15px; font-weight: 600; }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .section-label {
            font-size: 20px;
            font-weight: 800;
            color: #002855;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-note {
            background: #eff6ff;
            border: 1px dashed #93c5fd;
            padding: 14px 20px;
            border-radius: 10px;
            font-size: 15px;
            color: #1d4ed8;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; }

        th {
            background: #f8fafc;
            color: #64748b;
            padding: 16px 22px;
            text-align: left;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 18px 22px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 15px;
            vertical-align: middle;
        }

        tr:hover { background: #f8fafc; }

        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .badge-menunggu { background: #fee2e2; color: #dc2626; }
        .badge-diproses { background: #fef3c7; color: #d97706; }
        .badge-selesai  { background: #dcfce3; color: #16a34a; }

        .badge-privat {
            background: #1e293b;
            color: white;
            margin-left: 5px;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
        }

        .btn-action {
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: 0.2s;
        }

        .btn-proses {
            background: #e0e7ff;
            color: #002855;
            border: 1px solid #c7d2fe;
        }

        .btn-proses:hover { background: #c7d2fe; }

        .empty-state {
            text-align: center;
            padding: 70px 20px;
            color: #94a3b8;
        }

        .empty-state i { font-size: 50px; display: block; margin-bottom: 18px; color: #cbd5e1; }
        .empty-state p { font-size: 17px; margin: 0; }
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

    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-shield-halved"></i>
            SIPELDA <span style="font-size: 13px; color: #93c5fd; font-weight: 500;">Portal Admin</span>
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin.php" class="active"><i class="fa-solid fa-border-all"></i> Dashboard</a></li>
            <li><a href="histori_admin.php"><i class="fa-solid fa-clock-rotate-left"></i> Histori Pengaduan</a></li>
        </ul>
        <div class="sidebar-footer">
            <a onclick="openLogoutModal()"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Dashboard Pengaduan</h1>
                <p>Kelola laporan aktif yang masih menunggu penanganan petugas</p>
            </div>
            <a href="../profil/profil.php" class="admin-badge">
                <i class="fa-solid fa-user-shield"></i> <?= htmlspecialchars($_SESSION['username']) ?>
            </a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?= $q_stats['total'] ?? 0 ?></h3>
                    <p>Total Pengaduan</p>
                </div>
                <div class="stat-icon" style="background:#e0e7ff; color:#002855;"><i class="fa-solid fa-clipboard-list"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?= $q_stats['menunggu'] ?? 0 ?></h3>
                    <p>Menunggu Respon</p>
                </div>
                <div class="stat-icon" style="background:#fee2e2; color:#dc2626;"><i class="fa-solid fa-clock"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?= $q_stats['diproses'] ?? 0 ?></h3>
                    <p>Sedang Diproses</p>
                </div>
                <div class="stat-icon" style="background:#fef3c7; color:#d97706;"><i class="fas fa-spinner"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?= $q_stats['selesai'] ?? 0 ?></h3>
                    <p>Laporan Selesai</p>
                </div>
                <div class="stat-icon" style="background:#dcfce3; color:#16a34a;"><i class="fa-solid fa-check-double"></i></div>
            </div>
        </div>

        <div class="section-label">
            <i class="fa-solid fa-list-check" style="color:#d97706;"></i>
            Laporan Aktif (Menunggu & Diproses)
        </div>

        <div class="info-note">
            <i class="fa-solid fa-circle-info"></i>
            Laporan yang statusnya diubah menjadi <strong>Selesai</strong> akan otomatis dipindahkan ke halaman <a href="histori_admin.php" style="color:#1d4ed8; font-weight:700;">Histori Pengaduan</a>.
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
                    <?php if (mysqli_num_rows($result) > 0):
                        while ($row = mysqli_fetch_assoc($result)):
                            $status = strtolower($row['status']);
                            $is_privat = strpos($row['judul_laporan'], '[PRIVAT]') !== false;
                            $pecah_judul    = explode(' - ', str_replace([' [ANONIM]', ' [PRIVAT]'], '', $row['judul_laporan']), 2);
                            $kategori_murni = $pecah_judul[0];
                            $lokasi_detail  = $pecah_judul[1] ?? '';
                            $icon_kat       = getIkonKategori($kategori_murni);
                    ?>
                        <tr>
                            <td>
                                <strong style="font-size:16px;">#SPL-<?= $row['id_pengaduan'] ?></strong><br>
                                <span style="color:#94a3b8; font-size:13px;"><?= date('d M Y, H:i', strtotime($row['tgl_pengaduan'])) ?></span>
                            </td>
                            <td>
                                <strong style="font-size:15px;"><?= htmlspecialchars($row['nama_lengkap']) ?></strong><br>
                                <span style="color:#94a3b8; font-size:13px;">@<?= htmlspecialchars($row['username']) ?></span>
                            </td>
                            <td>
                                <strong style="font-size:15px;">
                                    <i class="fa-solid <?= $icon_kat ?>" style="color:#94a3b8; margin-right:6px;"></i>
                                    <?= htmlspecialchars($kategori_murni) ?>
                                    <?= $is_privat ? '<span class="badge-privat"><i class="fa-solid fa-lock"></i> PRIVAT</span>' : '' ?>
                                </strong>
                                <?php if ($lokasi_detail): ?>
                                    <div style="font-size:13px; color:#64748b; margin-top:5px;">
                                        <i class="fa-solid fa-location-dot" style="color:#dc3545;"></i>
                                        <?= htmlspecialchars($lokasi_detail) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-<?= $status ?>"><?= strtoupper($status) ?></span></td>
                            <td>
                                <a href="tanggapan_admin.php?id=<?= $row['id_pengaduan'] ?>" class="btn-action btn-proses">
                                    <i class="fa-solid fa-pen-to-square"></i> Proses
                                </a>
                            </td>
                        </tr>
                    <?php endwhile;
                    else: ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fa-solid fa-circle-check" style="color:#16a34a;"></i>
                                    <p>Semua laporan telah selesai ditangani.<br>Tidak ada laporan aktif saat ini.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        function openLogoutModal() {
            document.getElementById('modal-logout-admin').style.display = 'flex';
        }
        function closeLogoutModal() {
            document.getElementById('modal-logout-admin').style.display = 'none';
        }
        document.getElementById('modal-logout-admin').addEventListener('click', function(e) {
            if (e.target === this) closeLogoutModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeLogoutModal();
        });
    </script>
</body>
</html>
