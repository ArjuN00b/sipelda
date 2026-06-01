<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// OPTIMASI: Peringkas variabel GET menggunakan ??
$search   = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$status   = $_GET['status'] ?? '';
$kategori = $_GET['kategori'] ?? '';

$query_sql = "
    SELECT p.*, u.nama_lengkap, t.isi_tanggapan 
    FROM pengaduan p 
    JOIN users u ON p.id_user = u.id_user 
    LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan 
    WHERE 1=1
";

if ($search != '') $query_sql .= " AND (p.judul_laporan LIKE '%$search%' OR p.isi_laporan LIKE '%$search%')";
if ($status != '' && $status != 'Semua Status') $query_sql .= " AND p.status = '$status'";
if ($kategori != '' && $kategori != 'Semua Kategori') $query_sql .= " AND p.judul_laporan LIKE '$kategori%'";

$query_sql .= " ORDER BY p.tgl_pengaduan DESC";

$result = mysqli_query($koneksi, $query_sql);
$total_data = mysqli_num_rows($result);

// OPTIMASI: Fungsi Reusable untuk Icon (Menghindari penulisan if-else berulang dalam loop)
function getIkonKategori($kategori) {
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
    <title>Arsip Pengaduan - SIPELDA Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS Dioptimalkan */
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f7fb; margin: 0; display: flex; color: #333; }
        
        .sidebar { width: 260px; height: 100vh; background: #002855; color: white; padding: 30px 20px; position: fixed; left: 0; top: 0; box-sizing: border-box; display: flex; flex-direction: column; }
        .sidebar .logo { font-size: 24px; font-weight: bold; text-align: center; margin-bottom: 40px; display: flex; flex-direction: column; align-items: center; gap: 5px; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; flex: 1; }
        .sidebar-menu a { display: flex; align-items: center; gap: 15px; color: #a9b9cc; text-decoration: none; padding: 12px 20px; border-radius: 8px; font-weight: 500; transition: 0.3s; margin-bottom: 10px;}
        .sidebar-menu a.active, .sidebar-menu a:hover { background: rgba(255,255,255,0.1); color: white; }
        .sidebar-footer a { display: flex; align-items: center; gap: 15px; color: #ff6b6b; text-decoration: none; padding: 12px 20px; border-radius: 8px; font-weight: bold; transition: 0.3s; background: rgba(220,53,69,0.1); }
        .sidebar-footer a:hover { background: #dc3545; color: white; }
        
        .main-content { margin-left: 260px; padding: 30px 40px; width: calc(100% - 260px); box-sizing: border-box; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 24px; color: #002855; }
        
        .admin-profile { display: flex; align-items: center; gap: 10px; background: white; padding: 8px 15px; border-radius: 30px; border: 1px solid #e2e8f0; font-size: 14px; font-weight: bold; color: #002855; text-decoration: none; }
        .desc-text { color: #64748b; font-size: 15px; margin-bottom: 20px; }
        
        .filter-area { display: flex; gap: 15px; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; margin-bottom: 20px; align-items: center; flex-wrap: wrap; }
        .input-group { position: relative; flex: 1.5; min-width: 250px; }
        .input-group i { position: absolute; left: 15px; top: 13px; color: #94a3b8; }
        .input-group input { width: 100%; padding: 12px 15px 12px 40px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; box-sizing: border-box; }
        .filter-select { flex: 1; min-width: 180px; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; background: #f8fafc; cursor: pointer; color: #334155; }
        .btn-filter { background: #002855; color: white; border: none; padding: 12px 25px; border-radius: 6px; cursor: pointer; font-weight: bold; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-filter:hover { background: #001a3b; }
        
        .table-container { background: white; border-radius: 10px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.03); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; color: #64748b; padding: 15px 20px; text-align: left; font-size: 12px; font-weight: bold; border-bottom: 1px solid #e2e8f0; }
        td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: middle; color: #334155; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; border: 1px solid transparent; }
        .badge-menunggu { background: #fff1f2; color: #dc2626; border-color: #fecdd3; }
        .badge-diproses { background: #fffbeb; color: #d97706; border-color: #fde68a; }
        .badge-selesai { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
        
        .badge-anonim, .badge-privat { font-size: 10px; margin-left: 5px; padding: 2px 6px; border-radius: 4px; }
        .badge-anonim { background: #e2e8f0; color: #475569; }
        .badge-privat { background: #1e293b; color: white; }
        
        .tanggapan-text { font-size: 13px; color: #64748b; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .btn-lihat { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: bold; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s; }
        .btn-lihat:hover { background: #e2e8f0; color: #002855; }
        
        .footer-table { padding: 15px 20px; background: #f8fafc; display: flex; justify-content: space-between; font-size: 13px; color: #64748b; border-top: 1px solid #e2e8f0; }
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
                <?php 
                $opsi_kat = [
                    'Jalan Rusak & Infrastruktur', 'Kebersihan & Sampah', 'Penerangan Jalan Umum (PJU)', 
                    'Kesehatan & Lingkungan', 'Keamanan & Ketertiban', 'Ketertiban Lalu Lintas & Parkir', 
                    'Pelayanan Administrasi & Birokrasi', 'Bantuan Sosial (Bansos)', 'Kedaruratan & Bencana', 
                    'Fasilitas Umum', 'Lainnya'
                ];
                foreach ($opsi_kat as $op) {
                    $selected = ($kategori == $op) ? 'selected' : '';
                    echo "<option value='$op' $selected>" . str_replace(' & Birokrasi', '', $op) . "</option>";
                }
                ?>
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

                            $pecah_judul = explode(' - ', str_replace([' [ANONIM]', ' [PRIVAT]'], '', $row['judul_laporan']), 2);
                            $kategori_murni = $pecah_judul[0];
                            $lokasi_detail = $pecah_judul[1] ?? '';
                            $icon_kat = getIkonKategori($kategori_murni); // Panggil fungsi Helper Ikon
                    ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td>
                                    <strong><?= date('d M', strtotime($row['tgl_pengaduan'])); ?></strong><br>
                                    <span style="font-size:12px; color:#94a3b8;"><?= date('Y', strtotime($row['tgl_pengaduan'])); ?></span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($row['nama_lengkap']) ?>
                                    <?= $is_anonim ? '<span class="badge-anonim">ANONIM</span>' : '' ?>
                                </td>
                                <td>
                                    <strong><i class="fa-solid <?= $icon_kat ?>" style="color:#94a3b8; margin-right:5px;"></i> <?= htmlspecialchars($kategori_murni) ?></strong>
                                    <?= $is_privat ? '<span class="badge-privat"><i class="fa-solid fa-lock"></i></span>' : '' ?>
                                    
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
                        <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">Data arsip pengaduan tidak ditemukan.</td></tr>
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
