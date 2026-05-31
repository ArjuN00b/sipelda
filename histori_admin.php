<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';

$query_sql = "
    SELECT p.*, u.nama_lengkap, t.isi_tanggapan 
    FROM pengaduan p 
    JOIN users u ON p.id_user = u.id_user 
    LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan 
    WHERE 1=1
";

if ($search != '') {
    $query_sql .= " AND (p.judul_laporan LIKE '%$search%' OR p.isi_laporan LIKE '%$search%')";
}
if ($status != '' && $status != 'Semua Status') {
    $query_sql .= " AND p.status = '$status'";
}
if ($kategori != '' && $kategori != 'Semua Kategori') {
    $query_sql .= " AND p.judul_laporan LIKE '$kategori%'";
}

$query_sql .= " ORDER BY p.tgl_pengaduan DESC";

$result = mysqli_query($koneksi, $query_sql);
$total_data = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Arsip Pengaduan - SIPELDA Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f7fb;
            margin: 0;
            display: flex;
            color: #333;
        }

        .sidebar {
            width: 260px;
            height: 100vh;
            background: #002855;
            color: white;
            padding: 30px 20px;
            position: fixed;
            left: 0;
            top: 0;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }

        .sidebar .logo {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 40px;
            letter-spacing: 1px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            flex: 1;
        }

        .sidebar-menu li {
            margin-bottom: 10px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #a9b9cc;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: 0.3s;
        }

        .sidebar-menu a.active,
        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #ff6b6b;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: bold;
            transition: 0.3s;
            background: rgba(220, 53, 69, 0.1);
        }

        .sidebar-footer a:hover {
            background: #dc3545;
            color: white;
        }

        .main-content {
            margin-left: 260px;
            padding: 30px 40px;
            width: calc(100% - 260px);
            box-sizing: border-box;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #002855;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            padding: 8px 15px;
            border-radius: 30px;
            border: 1px solid #e2e8f0;
            font-size: 14px;
            font-weight: bold;
            color: #002855;
            text-decoration: none;
        }

        .desc-text {
            color: #64748b;
            font-size: 15px;
            margin-bottom: 20px;
        }

        .filter-area {
            display: flex;
            gap: 15px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .input-group {
            position: relative;
            flex: 1.5;
            min-width: 250px;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 13px;
            color: #94a3b8;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            outline: none;
            box-sizing: border-box;
        }

        .filter-select {
            flex: 1;
            min-width: 180px;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            outline: none;
            background: #f8fafc;
            cursor: pointer;
            color: #334155;
        }

        .btn-filter {
            background: #002855;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }

        .btn-filter:hover {
            background: #001a3b;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8fafc;
            color: #64748b;
            padding: 15px 20px;
            text-align: left;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
            vertical-align: middle;
            color: #334155;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.5px;
            border: 1px solid transparent;
        }

        .badge-menunggu {
            background: #fff1f2;
            color: #dc2626;
            border-color: #fecdd3;
        }

        .badge-diproses {
            background: #fffbeb;
            color: #d97706;
            border-color: #fde68a;
        }

        .badge-selesai {
            background: #f0fdf4;
            color: #16a34a;
            border-color: #bbf7d0;
        }

        .badge-anonim {
            background: #e2e8f0;
            color: #475569;
            font-size: 10px;
            margin-left: 5px;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .badge-privat {
            background: #1e293b;
            color: white;
            margin-left: 5px;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .tanggapan-text {
            font-size: 13px;
            color: #64748b;
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .btn-lihat {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: 0.2s;
        }

        .btn-lihat:hover {
            background: #e2e8f0;
            color: #002855;
        }

        .footer-table {
            padding: 15px 20px;
            background: #f8fafc;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-shield-halved" style="font-size: 32px;"></i>
            SIPELDA <span style="font-size: 12px; color: #93c5fd; font-weight:normal;">Admin Portal</span>
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin.php"><i class="fa-solid fa-border-all"></i> Dashboard</a></li>
            <li><a href="histori_admin.php" class="active"><i class="fa-solid fa-clock-rotate-left"></i> Histori Pengaduan</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="logout.php" onclick="return confirm('Keluar dari panel admin?')"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Arsip Pengaduan Warga</h1>
            <a href="profil.php" class="admin-profile">
                <i class="fa-solid fa-user-shield" style="font-size: 20px;"></i> Admin <?= htmlspecialchars($_SESSION['username']); ?>
            </a>
        </div>

        <p class="desc-text">Rekapitulasi seluruh laporan warga, termasuk laporan yang sudah selesai dan diarsipkan oleh sistem.</p>

        <form method="GET" action="histori_admin.php" class="filter-area">
            <div class="input-group">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Cari kata kunci arsip..." value="<?= htmlspecialchars($search); ?>">
            </div>

            <select name="status" class="filter-select">
                <option value="Semua Status">Semua Status</option>
                <option value="menunggu" <?= ($status == 'menunggu') ? 'selected' : ''; ?>>🔴 Menunggu</option>
                <option value="diproses" <?= ($status == 'diproses') ? 'selected' : ''; ?>>🟡 Diproses</option>
                <option value="selesai" <?= ($status == 'selesai') ? 'selected' : ''; ?>>🟢 Selesai</option>
            </select>

            <select name="kategori" class="filter-select">
                <option value="Semua Kategori">Semua Kategori</option>
                <option value="Jalan Rusak & Infrastruktur" <?= ($kategori == 'Jalan Rusak & Infrastruktur') ? 'selected' : ''; ?>>Jalan Rusak & Infrastruktur</option>
                <option value="Kebersihan & Sampah" <?= ($kategori == 'Kebersihan & Sampah') ? 'selected' : ''; ?>>Kebersihan & Sampah</option>
                <option value="Penerangan Jalan Umum (PJU)" <?= ($kategori == 'Penerangan Jalan Umum (PJU)') ? 'selected' : ''; ?>>Penerangan Jalan Umum (PJU)</option>
                <option value="Kesehatan & Lingkungan" <?= ($kategori == 'Kesehatan & Lingkungan') ? 'selected' : ''; ?>>Kesehatan & Lingkungan</option>
                <option value="Keamanan & Ketertiban" <?= ($kategori == 'Keamanan & Ketertiban') ? 'selected' : ''; ?>>Keamanan & Ketertiban</option>
                <option value="Ketertiban Lalu Lintas & Parkir" <?= ($kategori == 'Ketertiban Lalu Lintas & Parkir') ? 'selected' : ''; ?>>Ketertiban Lalu Lintas & Parkir</option>
                <option value="Pelayanan Administrasi & Birokrasi" <?= ($kategori == 'Pelayanan Administrasi & Birokrasi') ? 'selected' : ''; ?>>Pelayanan Administrasi</option>
                <option value="Bantuan Sosial (Bansos)" <?= ($kategori == 'Bantuan Sosial (Bansos)') ? 'selected' : ''; ?>>Bantuan Sosial</option>
                <option value="Kedaruratan & Bencana" <?= ($kategori == 'Kedaruratan & Bencana') ? 'selected' : ''; ?>>Kedaruratan & Bencana</option>
                <option value="Fasilitas Umum" <?= ($kategori == 'Fasilitas Umum') ? 'selected' : ''; ?>>Fasilitas Umum</option>
                <option value="Lainnya" <?= ($kategori == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
            </select>

            <button type="submit" class="btn-filter"><i class="fa-solid fa-filter"></i> Terapkan</button>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>TANGGAL</th>
                        <th>PELAPOR</th>
                        <th>KATEGORI & LOKASI</th>
                        <th>STATUS</th>
                        <th>TANGGAPAN RESMI</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    if ($total_data > 0):
                        while ($row = mysqli_fetch_assoc($result)):
                            $status_db = strtolower($row['status']);
                            $is_anonim = strpos($row['judul_laporan'], '[ANONIM]') !== false;
                            $is_privat = strpos($row['judul_laporan'], '[PRIVAT]') !== false;

                            $judul_bersih = str_replace([' [ANONIM]', ' [PRIVAT]'], '', $row['judul_laporan']);
                            $pecah_judul = explode(' - ', $judul_bersih, 2);
                            $kategori_murni = $pecah_judul[0];
                            $lokasi_detail = isset($pecah_judul[1]) ? $pecah_judul[1] : '';

                            $kat_lower = strtolower($kategori_murni);
                            $icon_kat = "fa-bullhorn";
                            if (strpos($kat_lower, 'jalan') !== false && strpos($kat_lower, 'penerangan') === false) $icon_kat = "fa-road";
                            elseif (strpos($kat_lower, 'penerangan') !== false || strpos($kat_lower, 'pju') !== false) $icon_kat = "fa-lightbulb";
                            elseif (strpos($kat_lower, 'sampah') !== false || strpos($kat_lower, 'kebersihan') !== false) $icon_kat = "fa-trash-can";
                            elseif (strpos($kat_lower, 'kesehatan') !== false || strpos($kat_lower, 'lingkungan') !== false) $icon_kat = "fa-notes-medical";
                            elseif (strpos($kat_lower, 'keamanan') !== false || strpos($kat_lower, 'ketertiban') !== false) $icon_kat = "fa-shield-halved";
                            elseif (strpos($kat_lower, 'lalu lintas') !== false || strpos($kat_lower, 'parkir') !== false) $icon_kat = "fa-car";
                            elseif (strpos($kat_lower, 'administrasi') !== false || strpos($kat_lower, 'birokrasi') !== false) $icon_kat = "fa-file-signature";
                            elseif (strpos($kat_lower, 'bantuan') !== false || strpos($kat_lower, 'bansos') !== false) $icon_kat = "fa-handshake-angle";
                            elseif (strpos($kat_lower, 'bencana') !== false || strpos($kat_lower, 'darurat') !== false) $icon_kat = "fa-triangle-exclamation";
                            elseif (strpos($kat_lower, 'fasilitas') !== false) $icon_kat = "fa-building";
                    ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td>
                                    <strong><?= date('d M', strtotime($row['tgl_pengaduan'])); ?></strong><br>
                                    <span style="font-size:12px; color:#94a3b8;"><?= date('Y', strtotime($row['tgl_pengaduan'])); ?></span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($row['nama_lengkap']) ?>
                                    <?php if ($is_anonim): ?><span class="badge-anonim">ANONIM</span><?php endif; ?>
                                </td>
                                <td>
                                    <strong><i class="fa-solid <?= $icon_kat ?>" style="color:#94a3b8; margin-right:5px;"></i> <?= htmlspecialchars($kategori_murni) ?></strong>
                                    <?php if ($is_privat): ?><span class="badge-privat"><i class="fa-solid fa-lock"></i></span><?php endif; ?>

                                    <?php if ($lokasi_detail): ?>
                                        <div style="font-size:12px; color:#64748b; margin-top:4px;">
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
                                <td>
                                    <a href="tanggapan_admin.php?id=<?= $row['id_pengaduan']; ?>" class="btn-lihat"><i class="fa-regular fa-eye"></i> Lihat</a>
                                </td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">Data arsip pengaduan tidak ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="footer-table">
                <div>Menampilkan <?= $total_data; ?> arsip ditemukan</div>
            </div>
        </div>
    </div>

</body>

</html>
