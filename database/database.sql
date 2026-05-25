CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    no_telp VARCHAR(15),
    role ENUM('admin', 'masyarakat') DEFAULT 'masyarakat'
);

CREATE TABLE pengaduan (
    id_pengaduan INT AUTO_INCREMENT PRIMARY KEY,
    tgl_pengaduan DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_user INT NOT NULL,
    judul_laporan VARCHAR(255) NOT NULL,
    isi_laporan TEXT NOT NULL,
    foto VARCHAR(255),
    status ENUM('menunggu', 'diproses', 'selesai') DEFAULT 'menunggu',
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
);

CREATE TABLE tanggapan (
    id_tanggapan INT AUTO_INCREMENT PRIMARY KEY,
    id_pengaduan INT NOT NULL,
    id_admin INT NOT NULL,
    tgl_tanggapan DATETIME DEFAULT CURRENT_TIMESTAMP,
    isi_tanggapan TEXT NOT NULL,
    FOREIGN KEY (id_pengaduan) REFERENCES pengaduan(id_pengaduan) ON DELETE CASCADE,
    FOREIGN KEY (id_admin) REFERENCES users(id_user)
);
