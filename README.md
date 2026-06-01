               SIPELDA (Sistem Pelaporan dan Pengaduan Warga) 

SIPELDA adalah sebuah aplikasi berbasis web yang dirancang untuk memfasilitasi masyarakat dalam menyampaikan aspirasi, keluhan, dan 
laporan kejadian di lingkungan sekitar kepada pihak administrasi desa/kelurahan. 
Aplikasi ini dilengkapi dengan fitur pelacakan lokasi berbasis GPS, manajemen status laporan, dan transparansi tanggapan.

A. Fitur Utama : 

1. Fitur Masyarakat (Warga)
a. Autentikasi & Akun: Pendaftaran akun baru (Registrasi) menggunakan Nama Lengkap, Username, dan No. WhatsApp/Telepon.
b. Login dan fitur Lupa Kata Sandi.
c. Manajemen Profil: Memperbarui data diri, mengubah kata sandi, serta mengunggah, mengganti, atau menghapus foto profil.

2. Pembuatan Laporan (Pengaduan):
a. Unggah foto bukti kejadian (Maksimal 2MB, format JPG/PNG).
b. Deteksi lokasi otomatis menggunakan GPS atau pencarian alamat manual via Peta Interaktif (Leaflet.js + OpenStreetMap).
c. Opsi laporan bersifat Anonim (menyembunyikan nama pelapor di publik).
d. Opsi laporan bersifat Privat (hanya bisa dilihat oleh pelapor dan admin).
e. Riwayat Laporan: Memantau status laporan (Menunggu, Diproses, Selesai) dan melihat balasan/tanggapan resmi dari petugas kelurahan.
f. Beranda Publik: Melihat laporan-laporan dari warga lain yang bersifat publik secara transparan.

3. Fitur Administrator (Kelurahan)
a. Dashboard Statistik: Ringkasan total aduan, aduan yang menunggu, diproses, dan selesai.
b. Tindak Lanjut & Manajemen Pengaduan:
      1. Melihat detail laporan beserta foto (dilengkapi fitur *Zoom Modal* gambar) dan titik koordinat Google Maps.
      2. Memperbarui status penanganan laporan (Menunggu ➔ Diproses ➔ Selesai).
      3. Memberikan tanggapan resmi/tindakan balasan atas laporan warga.
      4. Menghapus laporan (sistem akan otomatis menghapus file foto terkait dari server).
      5. Arsip/Histori:** Filter pencarian arsip laporan berdasarkan kata kunci, status, dan kategori masalah.


ATeknologi yang Digunakan
Frontend: HTML5, CSS3, FontAwesome (Ikon), Leaflet.js (Maps).
Backend: PHP (Native / Procedural).
Database: MySQL / MariaDB.



B. Panduan Instalasi

Ikuti langkah-langkah di bawah ini untuk menjalankan SIPELDA di komputer (Localhost):

1. Persyaratan Sistem
* Web Server lokal (misalnya: XAMPP, Laragon, atau MAMP).
* PHP versi 7.4 atau lebih baru.
* MySQL / MariaDB.

2. Langkah Instalasi
1. Pindahkan Folder: Pindahkan folder project aplikasi ke dalam direktori web server Anda (contoh: `htdocs` untuk XAMPP atau `www` untuk Laragon).
2. Siapkan Database:
   * Buka phpMyAdmin (biasanya di `http://localhost/phpmyadmin`).
   * Buat database baru dengan nama db_sipelda.
   * Impor file database db_sipelda.sql yang telah disediakan ke dalam database tersebut.
     
3. Konfigurasi Koneksi:
   Buka file `koneksi.php` menggunakan *text editor* dan sesuaikan kredensialnya jika diperlukan (secara *default* sudah disetting untuk XAMPP):
   ```php
   $host     = "localhost";
   $user     = "root"; 
   $password = "";     
   $db       = "db_sipelda";
