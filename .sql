CREATE TABLE pengaduans (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    foto_bukti VARCHAR(255) DEFAULT NULL,
    lokasi_kejadian VARCHAR(255) NOT NULL,
    kategori VARCHAR(255) NOT NULL,
    deskripsi TEXT NOT NULL,
    is_anonim TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('Menunggu', 'Diproses', 'Selesai') NOT NULL DEFAULT 'Menunggu',
    tanggapan_kelurahan TEXT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Membuat relasi ke tabel users
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
