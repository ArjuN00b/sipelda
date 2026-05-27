<?php
session_start();

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    header("Location: login.php");
    exit;
}

// Menghubungkan ke database
require 'koneksi.php';

// Mengambil data user terbaru berdasarkan id_user yang tersimpan di session
$id_user = $_SESSION['id_user'];
$query   = "SELECT * FROM users WHERE id_user = '$id_user'";
$result  = mysqli_query($koneksi, $query);

if ($result && mysqli_num_rows($result) === 1) {
    $data_user = mysqli_fetch_assoc($result);
} else {
    echo "Data pengguna tidak ditemukan.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Profil - SIPELDA</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 40px;
            display: flex;
            justify-content: center;
        }
        .profile-container {
            background-color: #ffffff;
            width: 100%;
            max-width: 500px;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .profile-header .avatar-large {
            width: 80px;
            height: 80px;
            background-color: #007bff;
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 36px;
            margin: 0 auto 15px;
        }
        .profile-header h2 {
            margin: 0;
            color: #333;
        }
        .profile-header p {
            margin: 5px 0 0;
            color: #777;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 1px;
        }
        .profile-details {
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .detail-group {
            margin-bottom: 20px;
        }
        .detail-group label {
            display: block;
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .detail-group .value {
            font-size: 16px;
            color: #333;
            font-weight: bold;
            padding-bottom: 8px;
            border-bottom: 1px solid #f4f4f4;
        }
        .btn-back {
            display: inline-block;
            width: 100%;
            padding: 12px;
            background-color: #6c757d;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            font-size: 15px;
            box-sizing: border-box;
            margin-top: 10px;
            transition: background-color 0.2s;
        }
        .btn-back:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>

<div class="profile-container">
    <div class="profile-header">
        <div class="avatar-large">
            <?= strtoupper(substr($data_user['username'], 0, 1)); ?>
        </div>
        <h2><?= $data_user['nama_lengkap']; ?></h2>
        <p>Role: <?= $data_user['role']; ?></p>
    </div>

    <div class="profile-details">
        <div class="detail-group">
            <label>Nama Lengkap</label>
            <div class="value"><?= $data_user['nama_lengkap']; ?></div>
        </div>

        <div class="detail-group">
            <label>NIK</label>
            <div class="value"><?= $data_user['nik']; ?></div>
        </div>

        <div class="detail-group">
            <label>Username</label>
            <div class="value"><?= $data_user['username']; ?></div>
        </div>

        <div class="detail-group">
            <label>Hak Akses</label>
            <div class="value" style="text-transform: capitalize;"><?= $data_user['role']; ?></div>
        </div>
        
        <?php if ($data_user['role'] === 'admin') : ?>
            <a href="admin.php" class="btn-back">Kembali ke Dashboard</a>
        <?php else : ?>
            <a href="masyarakat.php" class="btn-back">Kembali ke Dashboard</a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
