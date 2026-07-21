# **SIPELDA (Sistem Pelaporan dan Pengaduan Warga)**

SIPELDA adalah sebuah aplikasi berbasis web yang dirancang untuk memfasilitasi masyarakat dalam menyampaikan aspirasi, keluhan, dan laporan kejadian di lingkungan sekitar kepada pihak administrasi desa/kelurahan. Aplikasi ini dilengkapi dengan fitur pelacakan lokasi berbasis GPS, manajemen status laporan, dan transparansi tanggapan.

---

## A. Fitur Utama

### 1. Fitur Masyarakat (Warga)
* **Autentikasi & Akun:** Pendaftaran akun baru (Registrasi) menggunakan Nama Lengkap, Username, dan No. WhatsApp/Telepon.
* **Akses:** Login dan fitur Lupa Kata Sandi.
* **Manajemen Profil:** Memperbarui data diri, mengubah kata sandi, serta mengunggah, mengganti, atau menghapus foto profil.

### 2. Pembuatan Laporan (Pengaduan)
* **Bukti Visual:** Unggah foto bukti kejadian (Maksimal 2MB, format JPG/PNG).
* **Lokasi Akurat:** Deteksi lokasi otomatis menggunakan GPS atau pencarian alamat manual via Peta Interaktif (Leaflet.js + OpenStreetMap).
* **Laporan Anonim:** Opsi laporan bersifat Anonim (menyembunyikan nama pelapor di publik).
* **Laporan Privat:** Opsi laporan bersifat Privat (hanya bisa dilihat oleh pelapor dan admin).
* **Riwayat Laporan:** Memantau status laporan (Menunggu, Diproses, Selesai) dan melihat balasan/tanggapan resmi dari petugas kelurahan.
* **Beranda Publik:** Melihat laporan-laporan dari warga lain yang bersifat publik secara transparan.

### 3. Fitur Administrator (Kelurahan)
* **Dashboard Statistik:** Ringkasan total aduan, aduan yang menunggu, diproses, dan selesai.
* **Tindak Lanjut & Manajemen Pengaduan:**
  * Melihat detail laporan beserta foto (dilengkapi fitur *Zoom Modal* gambar) dan titik koordinat Google Maps.
  * Memperbarui status penanganan laporan (Menunggu ➔ Diproses ➔ Selesai).
  * Memberikan tanggapan resmi/tindakan balasan atas laporan warga.
  * Menghapus laporan (sistem akan otomatis menghapus file foto terkait dari server).
* **Arsip/Histori:** Filter pencarian arsip laporan berdasarkan kata kunci, status, dan kategori masalah.

---

## B. Teknologi yang Digunakan

* **Frontend:** HTML5, CSS3, FontAwesome (Ikon), Leaflet.js (Maps).
* **Backend:** PHP (Native / Procedural).
* **Database:** MySQL / MariaDB.

---

## C. Panduan Instalasi

Ikuti langkah-langkah di bawah ini untuk menjalankan SIPELDA di komputer lokal (*Localhost*):

### 1. Persyaratan Sistem
* Web Server lokal (misalnya: XAMPP, Laragon, atau MAMP).
* PHP versi 7.4 atau lebih baru.
* MySQL / MariaDB.

### 2. Langkah Instalasi

1. **Pindahkan Folder:** Pindahkan folder *project* aplikasi ke dalam direktori web server Anda (contoh: folder `htdocs` untuk XAMPP atau folder `www` untuk Laragon).
   
2. **Siapkan Database:**
   * Buka phpMyAdmin (biasanya di `http://localhost/phpmyadmin`).
   * Buat database baru dengan nama `db_sipelda`.
   * Impor file database `db_sipelda.sql` yang telah disediakan ke dalam database tersebut.
     
3. **Konfigurasi Koneksi:**
   Buka file `koneksi.php` menggunakan *text editor* (seperti VS Code atau Sublime) dan sesuaikan kredensialnya jika diperlukan (secara *default* sudah di-setting untuk XAMPP):
   
   ```php
   <?php
   $host     = "localhost";
   $user     = "root"; 
   $password = "";     
   $db       = "db_sipelda";
   ?>
