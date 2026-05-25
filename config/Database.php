<?php
class Database {
    // Properti dasar untuk koneksi
    private $host = "localhost";
    private $user = "root";       // Default XAMPP/Laragon
    private $pass = "";           // Kosongkan jika pakai XAMPP default
    private $db_name = "db_pengaduan"; // Nama database kalian
    
    // Properti ini dibuat 'protected' agar bisa diwariskan (inheritance) ke class lain
    protected $conn;

    // Method yang otomatis berjalan saat class dipanggil
    public function __construct() {
        // Logika PHP Native untuk menyambungkan ke MySQL
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db_name);

        // Cek jika koneksi gagal
        if ($this->conn->connect_error) {
            die("Koneksi Database Gagal: " . $this->conn->connect_error);
        }
    }
}
?>
